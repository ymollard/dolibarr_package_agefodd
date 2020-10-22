<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2014   Florian Henry   <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file agefodd/session/document.php
 * \ingroup agefodd
 * \brief list of document
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once '../class/agsession.class.php';
require_once '../class/agefodd_opca.class.php';
require_once '../class/agefodd_sessadm.class.php';
require_once '../class/agefodd_session_element.class.php';
require_once '../class/agefodd_session_formateur.class.php';
require_once '../class/agefodd_formation_catalogue.class.php';
require_once '../class/agefodd_convention.class.php';
require_once '../core/modules/agefodd/modules_agefodd.php';
require_once '../class/html.formagefodd.class.php';
require_once '../lib/agefodd.lib.php';
require_once '../lib/agefodd_document.lib.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

$langs->load('propal');
$langs->load('bills');
$langs->load('orders');

// Security check
if (! $user->rights->agefodd->lire) {
	accessforbidden();
}

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$id_external_model = GETPOST('id_external_model', 'none');
$socid = GETPOST('socid', 'int');
$confirm = GETPOST('confirm', 'alpha');
$sessiontrainerid = GETPOST('sessiontrainerid', 'int');

$type_link = GETPOST('type', 'alpha');
$idelement = GETPOST('idelement', 'int');

// Link invoice or order to session/customer
if ($action == 'link_confirm' && $user->rights->agefodd->creer) {
	$agf = new Agefodd_session_element($db);
	$agf->fk_element = GETPOST('select', 'int');
	$agf->fk_session_agefodd = $id;
	$agf->fk_soc = $socid;

	if ($type_link == 'bc')
		$agf->element_type = 'order';
	if ($type_link == 'fac')
	{
		$agf->element_type = 'invoice';
		dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
		$admintask = new Agefodd_sessadm($db);
		$admintask->updateByTriggerName($user, $id, 'AGF_BILL_LINK');
	}
	if ($type_link == 'prop')
		$agf->element_type = 'propal';

	$result = $agf->create($user);

	if ($result < 0) {
		setEventMessage($agf->error, 'errors');
	}
}

// Unlink propal/order/invoice with the session
if ($action == 'unlink_confirm' && $confirm == 'yes' && $user->rights->agefodd->creer) {
	$agf = new Agefodd_session_element($db);
	$result = $agf->fetch($idelement);

	$deleteobject = GETPOST('deleteobject', 'int');
	if (! empty($deleteobject)) {
		if ($type_link == 'bc' && $user->rights->commande->supprimer) {
			$obj_link = new Commande($db);
			$obj_link->fetch($agf->fk_element);
			$resultdel = $obj_link->delete($user);
		}
		if ($type_link == 'fac') {
			$obj_link = new Facture($db);
			$obj_link->fetch($agf->fk_element);
			if ($obj_link->is_erasable()>0) {
				if ($user->rights->facture->supprimer) {
					if (DOL_VERSION <= 4.0) {
						$resultdel = $obj_link->delete();
					} else {
						$resultdel = $obj_link->delete($user);
					}
				}
			} else {
				$resultdel=-1;
				$obj_link->error=$langs->trans('DisabledBecauseNotLastInvoice');
			}
		}
		if ($type_link == 'prop' && $user->rights->propal->supprimer) {
			$obj_link = new Propal($db);
			$obj_link->fetch($agf->fk_element);
			$resultdel = $obj_link->delete($user);
		}

		if ($resultdel < O) {
			setEventMessage($obj_link->error, 'errors');
		}
	}


	// If exists we update
	if ($agf->id && $resultdel>=0) {
		$result2 = $agf->delete($user);
	}
	if ($result2 > 0) {
		Header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&socid=' . $agf->fk_soc);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

/*
 * Action create and refresh pdf document
*/
if (($action == 'create' || $action == 'refresh') && ($user->rights->agefodd->creer || $user->rights->agefodd->modifier)) {
	$cour = GETPOST('cour', 'alpha');
	$model = GETPOST('model', 'alpha');
	$idform = GETPOST('idform', 'alpha');

	// Define output language
	$outputlangs = $langs;
	$newlang = GETPOST('lang_id', 'alpha');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
		$newlang = $object->thirdparty->default_lang;
	}
	if (! empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	}

	$id_tmp = $id;
	if (! empty($cour)) {
		$file = $model . '-' . $cour . '_' . $id . '_' . $socid . '.pdf';
	} elseif ($model == 'convention') {

		$soc_lang=new Societe($db);
		$soc_lang->fetch($socid);

		$convention = new Agefodd_convention($db);
		$convention->fetch(0, 0, GETPOST('convid', 'int'));

		$newlang = (!empty($conf->global->MAIN_MULTILANGS)?$convention->doc_lang:'');
		if (! empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		} else {
			$outputlangs=$langs;
		}

		$id_tmp = $convention->id;
		$model = $convention->model_doc;
		// Si on est sur un modèle externe module courrier, on charge toujours l'objet session dans lequel se trouvent toutes les données
		if(strpos($model, 'rfltr_agefodd') !== false) $id_tmp = $id;
		$model = str_replace('pdf_', '', $model);

		$file = 'convention' . '_' . $id . '_' . $socid . '_' . $convention->id . '.pdf';
	} elseif (! empty($socid)) {
		$file = $model . '_' . $id . '_' . $socid . '.pdf';
	} elseif (strpos($model, 'fiche_pedago') !== false) {
		$file = $model . '_' . $idform . '.pdf';
		$id_tmp = $idform;
		$cour = $id;
	} elseif (strpos($model, 'mission_trainer') !== false) {
		$file = $model . '_' . $sessiontrainerid . '.pdf';
		$socid = $sessiontrainerid;
		$id_tmp = $id;
	} elseif (strpos($model, 'contrat_trainer') !== false) {
		$file = $model . '_' . $sessiontrainerid . '.pdf';
		$socid = $sessiontrainerid;
		$id_tmp = $id;
	} else {
		$file = $model . '_' . $id . '.pdf';
	}

	// this configuration variable is designed like
	// standard_model_name:new_model_name&standard_model_name:new_model_name&....
	if (! empty($conf->global->AGF_PDF_MODEL_OVERRIDE) && ($model != 'convention')) {
		$modelarray = explode('&', $conf->global->AGF_PDF_MODEL_OVERRIDE);
		if (is_array($modelarray) && count($modelarray) > 0) {
			foreach ( $modelarray as $modeloveride ) {
				$modeloverridearray = explode(':', $modeloveride);
				if (is_array($modeloverridearray) && count($modeloverridearray) > 0) {
					if ($modeloverridearray[0] == $model) {
						$model = $modeloverridearray[1];
					}
				}
			}
		}
	}

	if (!empty($id_external_model) || strpos($model, 'rfltr_agefodd') !== false) {
		$path_external_model = '/referenceletters/core/modules/referenceletters/pdf/pdf_rfltr_agefodd.modules.php';
		if(strpos($model, 'rfltr_agefodd') !== false) $id_external_model= (int) strtr($model, array('rfltr_agefodd_'=>''));
	}
	if (strpos($model, 'fiche_pedago') !== false) {
		$agf = new Agsession($db);
		$agf->fetch($id);
		$agfTraining = new Formation($db);
		$agfTraining->fetch($agf->fk_formation_catalogue);
		$PDALink = $agfTraining->generatePDAByLink();
	}
	if (empty($PDALink))
	{
		$result = agf_pdf_create($db, $id_tmp, '', $model, $outputlangs, $file, $socid, $cour, $path_external_model, $id_external_model, $convention);


		if ($result == 1)
		{
			dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
			$admintask = new Agefodd_sessadm($db);
			$admintask->updateByTriggerName($user, $id, 'AGF_GEN_'.$model);
		}
	}
}

// Confirm create order
if (($action == 'createorder_confirm') && $confirm == 'yes' && $user->rights->agefodd->creer) {
	$frompropalid = GETPOST('propalid', 'int');
	$agf = new Agsession($db);
	$result = $agf->fetch($id);
	if ($result < 0) {
		setEventMessage($agf->error, 'errors');
	} else {
		$result = $agf->createOrder($user, $socid, $frompropalid);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		}
	}
	Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id . '&socid=' . $socid);
	exit();
}

// Confirm create propal
if (($action == 'createproposal') && $user->rights->agefodd->creer) {
	$agf = new Agsession($db);
	$result = $agf->fetch($id);
	if ($result < 0) {
		setEventMessage($agf->error, 'errors');
	} else {
		$result = $agf->createProposal($user, $socid);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		}
	}
	Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id . '&socid=' . $socid);
	exit();
}

// Confirm create propal
if (($action == 'createinvoice_confirm') && $user->rights->agefodd->creer) {
	$agf = new Agsession($db);
	$financialdoc = GETPOST('financialid', 'alpha');
	$financialdoc_array = explode('_',$financialdoc);
	if (is_array($financialdoc_array) && count($financialdoc_array)>0) {
		$financial_type = $financialdoc_array[0];
		$financial_id = $financialdoc_array[1];
	}
	$amount = GETPOST('amount', 'none');
	$result = $agf->fetch($id);
	if ($result < 0) {
		setEventMessage($agf->error, 'errors');
	} else {
		$result = $agf->createInvoice($user, $socid, $financial_id, $amount, $financial_type);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		}
	}
	Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id . '&socid=' . $socid);
	exit();
}

/*
 * Action delete pdf document
*/
if ($action == 'del' && $user->rights->agefodd->creer) {
	$cour = GETPOST('cour', 'alpha');
	$model = GETPOST('model', 'alpha');
	$idform = GETPOST('idform', 'alpha');

	if (! empty($cour)) {
		$file = $conf->agefodd->dir_output . '/' . $model . '-' . $cour . '_' . $id . '_' . $socid . '.pdf';
	} elseif ($model == 'convention') {
		// For backwoard compatibilty check convention file name with id of convention
		if (is_file($conf->agefodd->dir_output . '/' . $model . '_' . $id . '_' . $socid . '.pdf')) {
			$file = $conf->agefodd->dir_output . '/' . $model . '_' . $id . '_' . $socid . '.pdf';
		} else {
			$file = $conf->agefodd->dir_output . '/' . $model . '_' . $id . '_' . $socid . '_' . GETPOST('convid', 'int') . '.pdf';
		}
	} elseif (! empty($socid)) {
		$file = $conf->agefodd->dir_output . '/' . $model . '_' . $id . '_' . $socid . '.pdf';
	} elseif ($model == 'fiche_pedago') {
	    $file = $conf->agefodd->dir_output . '/' . $model . '_' . $idform . '.pdf';
	} elseif ($model == 'fiche_pedago_modules') {
	    $file = $conf->agefodd->dir_output . '/' . $model . '_' . $idform . '.pdf';
	} elseif (strpos($model, 'mission_trainer') !== false || strpos($model, 'contrat_trainer') !== false) {
		$file = $conf->agefodd->dir_output . '/' . $model . '_' . $sessiontrainerid . '.pdf';
	} else {
		$file = $conf->agefodd->dir_output . '/' . $model . '_' . $id . '.pdf';
	}

	if (is_file($file))
		unlink($file);
	else {
		$error = $file . ' : ' . $langs->trans("AgfDocDelError");
		setEventMessage($error, 'errors');
	}
}

/*
 * View
*/

llxHeader('', $langs->trans("AgfSessionDetail"));

if(!empty($conf->referenceletters->enabled)) {
	dol_include_once('/referenceletters/class/referenceletters_tools.class.php');
	if (class_exists('RfltrTools') && method_exists('RfltrTools','print_js_external_models')) {
		RfltrTools::print_js_external_models();
	}
}

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

// Selection du bon de commande ou de la facture à lier
if (($action == 'link') && $user->rights->agefodd->creer) {
	$agf = new Agsession($db);
	$agf->fetch($id);

	$head = session_prepare_head($agf);

	dol_fiche_head($head, 'document', $langs->trans("AgfSessionDetail"), -1, 'user');

	dol_agefodd_banner_tab($agf, 'id');
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">' . "\n";

	print '<tr class="liste_titre">' . "\n";

	print '<td colspan=3>';
	print '<a href="#">' . $langs->trans("AgfCommonDocs") . '</a></td>' . "\n";
	print '</tr>' . "\n";

	print '<tr class="liste">' . "\n";

	// creation de la liste de choix
	$agf_liste = new Agefodd_session_element($db);
	$result = $agf_liste->fetch_element_per_soc($socid, $type_link);
	if ($result<0) {
		setEventMessage($agf_liste->error,'errors');
	}
	$num = count($agf_liste->lines);
	if ($num > 0) {
		print '<form name="fact_link" action="document.php?action=link_confirm&id=' . $id . '"  method="post">' . "\n";
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
		print '<input type="hidden" name="socid" value="' . $socid . '">' . "\n";
		print '<input type="hidden" name="type" value="' . $type_link . '">' . "\n";

		$var = True;
		$options = '<option value=""></option>' . "\n";
		;
		for($i = 0; $i < $num; $i ++) {
			$options .= '<option value="' . $agf_liste->lines[$i]->id . '">' . $agf_liste->lines[$i]->ref .'-'. dol_print_date($agf_liste->lines[$i]->date).'-';
			$options .= '-'.$agf_liste->lines[$i]->socname.'-';
			$options .= $langs->trans('TotalTTC').':'.price($agf_liste->lines[$i]->amount).'-'.$agf_liste->lines[$i]->status.'</option>' . "\n";
		}
		$select = '<select class="flat" name="select">' . "\n" . $options . "\n" . '</select>' . "\n";

		print '<td width="250px">';
		if ($type_link == 'bc')
			print $langs->trans("AgfFactureBcSelectList");
		if ($type_link == 'fac')
			print $langs->trans("AgfFactureFacSelectList");
		if ($type_link == 'prop')
			print $langs->trans("AgfFacturePropSelectList");
		print '</td>' . "\n";
		print '<td>' . $select . '</td>' . "\n";
		if ($user->rights->agefodd->modifier) {
			print '</td><td><input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" align="absmiddle" name="bt_save" alt="' . $langs->trans("AgfModSave") . '"></td>' . "\n";
		}
		print '</form>';
	} else {
		print '<td colspan=3>';
		if ($type_link == 'bc') {
			print $langs->trans("AgfFactureBcNoResult");
		}
		if ($type_link == 'fac') {
			print $langs->trans("AgfFactureFacNoResult");
		}
		if ($type_link == 'prop') {
			print $langs->trans("AgfFacturePropNoResult");
		}
		print '</td>';
	}
	print '</tr>' . "\n";

	print '</div>' . "\n";
	llxFooter();
	exit();

}

if (! empty($id)) {
	$agf = new Agsession($db);
	$agf->fetch($id);

	$result = $agf->fetch_societe_per_session($id);

	$idform = $agf->formid;

	// Display View mode
	$head = session_prepare_head($agf);

	dol_fiche_head($head, 'document', $langs->trans("AgfSessionDetail"), -1, 'generic');

	dol_agefodd_banner_tab($agf, 'id');
	print '<div class="underbanner clearboth"></div>';

	if ($result > 0) {
		// Put user on the right action block after reload
		if (((! empty($socid) || ! empty($sessiontrainerid)) && $action != 'unlink' && $action != 'createorder' && $action != 'createinvoice')
				|| ($socid==0 && ($action=='create' ||  $action=='refresh'))) {

			print '<script type="text/javascript">
					jQuery(document).ready(function () {
						jQuery(function() {'."\n";
			if (! empty($sessiontrainerid)) {
				print '				 $(\'html, body\').animate({scrollTop: $("#trainerid' . $sessiontrainerid . '").offset().top}, 500,\'easeInOutCubic\');';
			} elseif (! empty($socid)) {
				print '				 $(\'html, body\').animate({scrollTop: $("#socid' . $socid . '").offset().top}, 500,\'easeInOutCubic\');';
			} elseif ($socid==0 && ($action=='create' ||  $action=='refresh')) {
				print '				 $(\'html, body\').animate({scrollTop: $("#commondoc").offset().top-20}, 500,\'easeInOutCubic\');';
			}

			print '			});
					});
					</script> ';
		}

		/*
		 * Confirm delete
		*/
		if ($action == 'delete') {
			print $form->formconfirm($_SERVER['PHP_SELF'] . "?id=" . $id, $langs->trans("AgfDeleteOps"), $langs->trans("AgfConfirmDeleteOps"), "confirm_delete", '', '', 1);
		}

		/*
		 * Confirm create order
		*/
		if ($action == 'createorder') {

			$agf_liste = new Agefodd_session_element($db);
			$result = $agf_liste->fetch_by_session_by_thirdparty($id, $socid);
			$propal_array = array (
					'0' => $langs->trans('AgfFromScratch')
			);

			foreach ( $agf_liste->lines as $line ) {
				if ($line->element_type == 'propal') {
					$propal_array[$line->fk_element] = $langs->trans('AgfFromObject') . ' ' . $line->propalref;
				}
			}

			$form_question = array ();
			$form_question[] = array (
					'label' => $langs->trans("AgfCreateOrderFromPropal"),
					'type' => 'radio',
					'values' => $propal_array,
					'name' => 'propalid'
			);

			print $form->formconfirm($_SERVER['PHP_SELF'] . "?socid=" . $socid . "&id=" . $id, $langs->trans("AgfCreateOrderFromSession"), '', "createorder_confirm", $form_question, '', 1);
		}

		/*
		 * Confirm create invoice
		*/
		if ($action == 'createinvoice') {

			$agf_liste = new Agefodd_session_element($db);
			$result = $agf_liste->fetch_by_session_by_thirdparty($id);
			$fin_array = array (
					'0' => $langs->trans('AgfFromScratchInvoice')
			);

			foreach ( $agf_liste->lines as $line ) {
				if ($line->element_type == 'propal') {
					$fin_array['propal_'.$line->fk_element] = $langs->trans('AgfFromObject') . ' ' . $line->propalref;
				}
				if ($line->element_type == 'order') {
					$fin_array['order_'.$line->fk_element] = $langs->trans('AgfFromObject') . ' ' . $line->comref;
				}
			}

			?>
			<script type="text/javascript">
				$(document).ready(function(){
					$tramount = $('#dialog-confirm #amount').parent().parent();
					$tramount.hide(0);
					$('#dialog-confirm').on('change', '#financialid', function(){
						if($(this).val() == '0') {
							$tramount.show(0);
						}else{
							$tramount.hide(0);
						}
					});
				});
			</script>
			<?php

			$form_question = array ();
			$form_question[] = array (
					'label' => $langs->trans("AgfCreateInvoiceFromPropalOrder"),
					'type' => 'radio',
					'values' => $fin_array,
					'name' => 'financialid'
			);
			$form_question[] = array (
					'label' => $langs->trans("Amount"),
					'type' => 'text',
					'values' => '',
					'name' => 'amount'
			);

			print $form->formconfirm($_SERVER['PHP_SELF'] . "?socid=" . $socid . "&id=" . $id, $langs->trans("AgfCreateInvoiceOPCAFromSession"), '', "createinvoice_confirm", $form_question, '', 1);
		}

		/*
		 * Confirm unlink
		*/
		if ($action == 'unlink') {

			$agf_liste = new Agefodd_session_element($db);
			$result = $agf_liste->fetch($idelement);
			if ($type_link == 'bc') {
				$ref = $agf_liste->comref;
			}
			if ($type_link == 'fac') {
				$ref = $agf_liste->facnumber;
			}
			if ($type_link == 'prop') {
				$ref = $agf_liste->propalref;
			}
			if (! empty($agf_liste->id)) {
				$form_question = array ();
				$form_question[] = array (
						'label' => $langs->trans("AgfDeleteObjectAlso", $ref),
						'type' => 'radio',
						'values' => array (
								'0' => $langs->trans('No'),
								'1' => $langs->trans('Yes')
						),
						'name' => 'deleteobject'
				);
			}
			print $form->formconfirm($_SERVER['PHP_SELF'] . '?type=' . $type_link . '&socid=' . $socid . '&id=' . $id . '&idelement=' . $idelement, $langs->trans("AgfConfirmUnlink"), '', "unlink_confirm", $form_question, '', 1);
		}

		print '<div class="fichecenter"><table class="border" width="100%">' . "\n";

		print '<tr class="liste_titre">' . "\n";
		print '<td colspan=3>';
		print $langs->trans("AgfCommonDocs") . '<a name="commondoc" id="commondoc"></a></td>' . "\n";
		print '</tr>' . "\n";

		print '<tr><td colspan=3 style="background-color:#d5baa8;">' . $langs->trans("AgfBeforeTraining") . '</td></tr>' . "\n";
		document_line($langs->trans("AgfFichePedagogique"), 'fiche_pedago');
		if (! $user->rights->agefodd->session->trainer) {
			if (!empty($conf->global->AGF_USE_TRAINING_MODULE)) {
				document_line($langs->trans("AgfFichePedagogiqueModule"), 'fiche_pedago_modules');
			}
			if (empty($conf->global->AGF_MERGE_ADVISE_AND_CONVOC)) {
				document_line($langs->trans("AgfConseilsPratique"), 'conseils');
			}
		}

		// During training

		print '<tr><td colspan=3 style="background-color:#d5baa8;">' . $langs->trans("AgfDuringTraining") . '</td></tr>' . "\n";

			if (!$user->rights->agefodd->session->trainer) {
				document_line($langs->trans("AgfFichePresence"), "fiche_presence");
				document_line($langs->trans("AgfFichePresenceCompany"), "fiche_presence_societe");
			}
			document_line($langs->trans("AgfFichePresenceLandscapeByMonth"), "fiche_presence_landscape_bymonth");
			document_line($langs->trans("AgfFichePresenceDirect"), "fiche_presence_direct");
			document_line($langs->trans("AgfFichePresenceDirectCompany"), "fiche_presence_direct_societe");
			if (!$user->rights->agefodd->session->trainer) {
				document_line($langs->trans("AgfFichePresenceEmpty"), "fiche_presence_empty");
				document_line($langs->trans("AgfFichePresenceTrainee"), "fiche_presence_trainee");
				document_line($langs->trans("AgfFichePresenceTraineeDirect"), "fiche_presence_trainee_direct");
				document_line($langs->trans("AgfFichePresenceTraineeLandscape"), "fiche_presence_landscape");
                document_line($langs->trans("AgfFichePresenceTraineeLandscapeEmpty"), "fiche_presence_landscape_empty");
				document_line($langs->trans("AgfFichePresenceTraineeLandscapeCompany"), "fiche_presence_landscape_societe");
				document_line($langs->trans("AgfFicheEval"), "fiche_evaluation");
				document_line($langs->trans("AgfRemiseEval"), "fiche_remise_eval");
				document_line($langs->trans("AgfAttestationEndTrainingEmpty"), "attestationendtraining_empty");
				document_line($langs->trans("AgfChevalet"), "chevalet");
			}

		print '</table>' . "\n";

		$agf_fin = new Agefodd_session_element($db);
		$agf_fin->fetch_element_by_session($id);
		$doclinkwithoutcust = array ();
		if (is_array($agf_fin->lines) && count($agf_fin->lines) > 0) {

			// Build array with
			$array_soc = array ();
			if (is_array($agf->lines) && count($agf->lines) > 0) {
				foreach ( $agf->lines as $line ) {
					$array_soc[] = $line->socid;
				}
			}
			// Build doc list
			foreach ( $agf_fin->lines as $linedoc ) {
				if (! in_array($linedoc->fk_soc, $array_soc) && ! empty($linedoc->urllink)) {
					$doclinkwithoutcust[] = $linedoc->urllink;
				}
			}
		}

		if (count($doclinkwithoutcust) > 0) {
			print '<table class="border" width="100%">' . "\n";

			print '<tr class="liste_titre">' . "\n";
			print '<td>' . $langs->trans("AgfDocLinkWitoutCustomerLink") . '</td>' . "\n";
			print '</tr>' . "\n";
			print '<tr>' . "\n";
			print '<td>' . implode($doclinkwithoutcust, ',') . '</td>' . "\n";
			print '</tr>' . "\n";
			print '</table>' . "\n";
		}

		print '&nbsp;' . "\n";

		$linecount = count($agf->lines);

		for($i = 0; $i < $linecount; $i ++) {
			if (! empty($agf->lines[$i]->socid)) {
				print '<table class="border" width="100%">' . "\n";

				print '<tr class="liste_titre">' . "\n";
				print '<td colspan=3>';

				if ($agf->lines[$i]->typeline == 'customer')
					$type_link_label = $langs->trans('ThirdParty');
				if ($agf->lines[$i]->typeline == 'trainee_soc')
					$type_link_label = $langs->trans('AgfParticipant');
				if ($agf->lines[$i]->typeline == 'trainee_doc')
					$type_link_label = $langs->trans('AgfTraineeSocDocUse');
				if ($agf->lines[$i]->typeline == 'OPCA')
					$type_link_label = $langs->trans('AgfMailTypeContactOPCA');
				if ($agf->lines[$i]->typeline == 'trainee_OPCA')
					$type_link_label = $langs->trans('AgfMailTypeContactOPCA');
				if ($agf->lines[$i]->typeline == 'trainee_requester')
					$type_link_label = $langs->trans('AgfTypeTraineeRequester');
				if ($agf->lines[$i]->typeline == 'trainee_presta')
					$type_link_label = $langs->trans('AgfTypePresta');

				$societe = new Societe($db);
				$societe->fetch($agf->lines[$i]->socid);

				// print '<a href="' . DOL_URL_ROOT . '/comm/card.php?socid=' . $agf->lines [$i]->socid . '" name="socid' . $agf->lines [$i]->socid . '" id="socid' . $agf->lines [$i]->socid . '">' . $agf->lines [$i]->code_client . ' - ' . $agf->lines [$i]->socname . ' (' . $type_link_label . ')</a></td>' . "\n";
				print $societe->getNomUrl(1) . ' (' . $type_link_label . ')';
				// Anchor
				print '<a name="socid' . $agf->lines[$i]->socid . '" id="socid' . $agf->lines[$i]->socid . '"></a>';
				print '</td>';

				print '</tr>' . "\n";
				if (is_array($agf->lines[$i]->trainee_array) && count($agf->lines[$i]->trainee_array) > 0 && $agf->lines[$i]->typeline!='trainee_presta') {
					$trainee_string = array ();
					print '<tr class="liste_titre">' . "\n";
					print '<td colspan=3>';
					if (count($agf->lines[$i]->trainee_array) > 1) {
						print $langs->trans('AgfParticipants');
					} else {
						print $langs->trans('AgfParticipant');
					}
					print ' : ';
					$k = 0;
					foreach ( $agf->lines[$i]->trainee_array as $trainee_ar ) {
						$trainee_string[] = $trainee_ar['lastname'] . ' ' . $trainee_ar['firstname'];
						$k ++;
						if ($k > 7) {
							$trainee_string[] = '...';
							break;
						}
					}
					print implode(',', $trainee_string);
					print '</td>';
					print '</tr>' . "\n";
				}
				// For OPCA just dispaly line Invoice
				if (strpos($agf->lines[$i]->typeline, 'OPCA') === false && $agf->lines[$i]->typeline!='trainee_presta') {

					if (! $user->rights->agefodd->session->trainer) {
						// Before training session
						print '<tr><td colspan=3 style="background-color:#d5baa8;">' . $langs->trans("AgfBeforeTraining") . '</td></tr>' . "\n";
						if (! empty($conf->propal->enabled)) {
							document_line($langs->trans("Proposal"), "prop", $agf->lines[$i]->socid);
						}
						if (! empty($conf->commande->enabled)) {
							document_line($langs->trans("AgfBonCommande"), "bc", $agf->lines[$i]->socid);
						}
						document_line($langs->trans("AgfConvention"), "convention", $agf->lines[$i]->socid);
					}
					document_line($langs->trans("AgfPDFConvocation"), 'convocation', $agf->lines[$i]->socid);

					if (! $user->rights->agefodd->session->trainer) {
						document_line($langs->trans("AgfCourrierConv"), "courrier", $agf->lines[$i]->socid, 'convention');
						document_line($langs->trans("AgfCourrierAcceuil"), "courrier", $agf->lines[$i]->socid, 'accueil');
					}

					// After training session
					print '<tr><td colspan=3 style="background-color:#d5baa8;">' . $langs->trans("AgfAfterTraining") . '</td></tr>' . "\n";
					if (! $user->rights->agefodd->session->trainer) {
						document_line($langs->trans("AgfAttestationEndTraining"), "attestationendtraining", $agf->lines[$i]->socid);
					}
					document_line($langs->trans("AgfAttestationPresenceTraining"), "attestationpresencetraining", $agf->lines[$i]->socid);

					if (! $user->rights->agefodd->session->trainer) {
						document_line($langs->trans("AgfAttestationPresenceCollective"), "attestationpresencecollective", $agf->lines[$i]->socid);
						document_line($langs->trans("AgfSendAttestation"), "attestation", $agf->lines[$i]->socid);
					}

					$text_fac = $langs->trans("AgfFacture");
					if ($agf->lines[$i]->type_session && (! empty($conf->global->AGF_MANAGE_OPCA))) { // session inter
						$agfstat = new Agefodd_opca($db);
						// load les infos OPCA pour la session
						$agfstat->getOpcaForTraineeInSession($agf->lines[$i]->socid, $agf->lines[$i]->sessid);
						// invocie to OPCA if funding thridparty
						$soc_to_select = ($agfstat->is_OPCA ? $agfstat->fk_soc_OPCA : $agf->lines[$i]->socid);

						// If funding is fill
						/*	if ($soc_to_select > 0 && $agfstat->is_OPCA) {
							$text_fac .= ' ' . $langs->trans("AgfOPCASub1");
						} elseif (! $agfstat->is_OPCA) 						// No funding
						{
							$text_fac .= ' ' . $langs->trans("AgfOPCASub2");
						} else 						// No funding trhirdparty filled
						{
							$text_fac .= ' <span class="error">' . $langs->trans("AgfOPCASubErr") . ' <a href="' . dol_buildpath("/agefodd/session/subscribers.php", 1) . '?action=edit&id=' . $id . '">' . $langs->trans('AgfModifySubscribersAndSubrogation') . '</a></span>';
						}*/
					}
					if (! $user->rights->agefodd->session->trainer) {
						document_line($text_fac, "fac", $agf->lines[$i]->socid);
					}

					if (! $user->rights->agefodd->session->trainer) {
						document_line($langs->trans("AgfCourrierCloture"), "courrier", $agf->lines[$i]->socid, 'cloture');
					}
					if (! $user->rights->agefodd->session->trainer) {
						if (! empty($conf->global->AGF_MANAGE_CERTIF)) {
							document_line($langs->trans("AgfPDFCertificateA4"), "certificateA4", $agf->lines[$i]->socid);
							document_line($langs->trans("AgfPDFCertificateCard"), "certificatecard", $agf->lines [$i]->socid);
						}
					}
				} elseif ($agf->lines[$i]->typeline=='trainee_presta') {
					if (! $user->rights->agefodd->session->trainer) {
						document_line($langs->trans("AgfContratPrestation"), "contrat_presta", $agf->lines[$i]->socid);
					}
				} else {
					if (! $user->rights->agefodd->session->trainer) {
						document_line($langs->trans("AgfFacture"), "facopca", $agf->lines[$i]->socid);
					}
				}
				print '</table>';
				if ($i < $linecount)
					print '&nbsp;' . "\n";
			}
		}
		if (! $user->rights->agefodd->session->trainer) {
			$agf_trainer = new Agefodd_session_formateur($db);
			$agf_trainer->fetch_formateur_per_session($id);
			if (is_array($agf_trainer->lines) && count($agf_trainer->lines) > 0) {
				print '<table class="border" width="100%">' . "\n";
				foreach ( $agf_trainer->lines as $line ) {
					print '<tr class="liste_titre">' . "\n";
					print '<td colspan=3>';
					print '<a href="' . dol_buildpath('/agefodd/trainer/session.php', 1) . '?id=' . $line->formid . '" name="trainerid' . $line->opsid . '" id="trainerid' . $line->opsid . '">' . $line->lastname . ' ' . $line->fullname . '</a>';
					print '</td>' . "\n";
					print '</tr>' . "\n";
					document_line($langs->trans("AgfTrainerMissionLetter"), "mission_trainer", $line->opsid);
					$select_models = getSelectAgefoddModels("contrat_trainer", $socid); // Si la chaine est vide, aucun modèle de ce type n'existe
					if(!empty($select_models)) document_line($langs->trans("AgfContratTrainer"), "contrat_trainer", $line->opsid);
				}
				print '</table>';
			}
		}
		print '</div></div>' . "\n";
	} elseif ($result==0) {
	    print '<div style="text-align:center"><br>'.$langs->trans('AgfThirdparyMandatory').'</div>';
		setEventMessages($langs->trans('AgfThirdparyMandatory'), null, 'errors');
	} else {
		setEventMessages($agf->error, null, 'errors');
	}
}

llxFooter();
$db->close();
