<?php
/*
 * Copyright (C) 2012-2014 Florian Henry <florian.henry@open-concept.pro>
 * Copyright (C) 2012		JF FERRY	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */



/**
 * \file agefodd/lib/agefodd_document.lib.php
 * \ingroup agefodd
 * \brief Some display function
 */
function show_conv($file, $socid, $nom_courrier) {
	
	global $langs, $conf, $db, $id, $form;
	
	require_once (DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
	require_once (DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php');
	require_once (DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php');
	
	$model = $file;
	$filename = $file;
	
	$agf = new Agefodd_convention($db);
	$result = $agf->fetch_all($id, $socid);
	
	$continue = true;
	// Get proposal/order/invoice informations
	$agf_comid = new Agefodd_session_element($db);
	$result = $agf_comid->fetch_by_session_by_thirdparty($id, $socid);
	
	$order_array = array ();
	$propal_array = array ();
	$invoice_array = array ();
	foreach ( $agf_comid->lines as $line ) {
		if ($line->element_type == 'order' && ! empty($line->comref)) {
			$order = new Commande($db);
			$order->fetch($line->fk_element);
			if (($order->statut != - 1) && ($order->statut != 0)) { 
				$order_array [$line->fk_element] = $line->comref;
			}
		}
		if ($line->element_type == 'propal' && ! empty($line->propalref)) {
			$propal = new Propal($db);
			$propal->fetch($line->fk_element);
			if (($propal->statut != 3) && ($propal->statut != 0)) {
				$propal_array [$line->fk_element] = $line->propalref;
			}
		}
		if ($line->element_type == 'invoice' && ! empty($line->facnumber)) {
			$invoice = new Facture($db);
			$invoice->fetch($line->fk_element);
			if ($invoice->statut != 0) {
				$invoice_array [$line->fk_element] = $line->facnumber;
			}
		}
	}

	//If order module is enabled, then we check if use is required
	if (! empty($conf->global->MAIN_MODULE_COMMANDE) && count($propal_array)==0) {
			if ((count($order_array) == 0) && (count($invoice_array) == 0) && empty($conf->global->AGF_USE_FAC_WITHOUT_ORDER)) {
				$mess = $form->textwithpicto('', $langs->trans("AgfFactureFacNoBonHelp"), 1, 'help');
				$continue = false;
			} elseif ((count($order_array) == 0) && (count($invoice_array) == 0) && $conf->global->AGF_USE_FAC_WITHOUT_ORDER) {
				$mess = $form->textwithpicto('', $langs->trans("AgfFactureFacNoBonHelpOpt"), 1, 'help');
				$continue = false;
			} elseif ((count($order_array) > 0) && (count($invoice_array) > 0) && empty($conf->global->AGF_USE_FAC_WITHOUT_ORDER)) {
				$mess = $form->textwithpicto('', $langs->trans("AgfFactureFacNoBonHelp"), 1, 'help');
				$continue = false;
			}
	} else {
		if (count($propal_array) == 0 && count($invoice_array)==0) {
			$mess = $form->textwithpicto('', $langs->trans("AgfFacturePropalHelp"), 1, 'help');
			$continue = false;
		}
	}
	if ((count($propal_array) == 0) && (count($order_array) == 0) && (count($invoice_array) == 0)) {
		$mess = $form->textwithpicto('', $langs->trans("AgfFacturePropalHelp"), 1, 'warning');
		$continue = false;
	}
	
	// If convention contract have already been set (database records exists)
	if ((count($agf->lines) > 0) && $continue) {
		$mess = '';
		foreach ( $agf->lines as $conv ) {
			
			$file = $filename . '_' . $id . '_' . $socid;
			// For backwoard compatibilty check convention file name with id of convention
			if (is_file($conf->agefodd->dir_output . '/' . $file . '.pdf')) {
				$file = $file . '.pdf';
			} elseif (is_file($conf->agefodd->dir_output . '/' . $file . '_' . $conv->id . '.pdf')) {
				$file = $file . '_' . $conv->id . '.pdf';
			} else {
				$file = '';
			}
			
			if (! empty($file)) {
				// Display
				$legende = $langs->trans("AgfDocOpen");
				$mess .= '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=agefodd&file=' . $file . '" alt="' . $legende . '" title="' . $legende . '">';
				$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/pdf2.png" border="0" align="absmiddle" hspace="2px" ></a>';
				
				// Regenerer
				$legende = $langs->trans("AgfDocRefresh");
				$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&socid=' . $socid . '&action=refresh&model=' . $model . '&cour=' . $nom_courrier . '&convid=' . $conv->id . '" alt="' . $legende . '" title="' . $legende . '">';
				$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/refresh.png" border="0" align="absmiddle" hspace="2px" ></a>';
				
				// Delete
				$legende = $langs->trans("AgfDocDel");
				$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&convid=' . $conv->id . '&socid=' . $socid . '&action=del&model=' . $model . '&cour=' . $nom_courrier . '" alt="' . $legende . '" title="' . $legende . '">';
				$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/editdelete.png" border="0" align="absmiddle" hspace="2px" ></a>';
			} else {
				// Create PDF document
				$legende = $langs->trans("AgfDocCreate");
				$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=create&socid=' . $socid . '&model=' . $model . '&cour=' . $nom_courrier . '&convid=' . $conv->id . '" alt="' . $legende . '" title="' . $legende . '">';
				$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
			}
			
			// Edit Convention
			$legende = $langs->trans("AgfDocEdit");
			$mess .= '<a href="' . dol_buildpath('/agefodd/session/convention.php', 1) . '?action=edit&sessid=' . $id . '&id=' . $conv->id . '" alt="' . $legende . '" title="' . $legende . '">';
			$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/edit.png" border="0" align="absmiddle" hspace="2px" ></a>';
			if (count($conv->line_trainee) > 0) {
				$mess .= '(' . count($conv->line_trainee) . ')';
			}
			$mess .= '<BR>';
		}
		// Allow to create another
		$legende = $langs->trans("AgfDocEdit");
		$mess .= '<a href="' . dol_buildpath('/agefodd/session/convention.php', 1) . '?action=create&sessid=' . $id . '&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
	} elseif ($continue) {
		// If not exists you should do it now
		$legende = $langs->trans("AgfDocEdit");
		$mess .= '<a href="' . dol_buildpath('/agefodd/session/convention.php', 1) . '?action=create&sessid=' . $id . '&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
	}
	
	return $mess;
}
function show_doc($file, $socid, $nom_courrier) {
	global $langs, $conf, $id, $form, $idform;
	
	$model = $file;
	if (! empty($nom_courrier))
		$file = $file . '-' . $nom_courrier . '_' . $id . '_' . $socid . '.pdf';
	elseif (! empty($socid))
		$file = $file . '_' . $id . '_' . $socid . '.pdf';
	elseif (strpos($model, 'fiche_pedago') !== false)
		$file = $file . '_' . $idform . '.pdf';
	else
		$file = $file . '_' . $id . '.pdf';
	
	if (is_file($conf->agefodd->dir_output . '/' . $file)) {
		// afficher
		$legende = $langs->trans("AgfDocOpen");
		$mess = '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=agefodd&file=' . $file . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/pdf2.png" border="0" align="absmiddle" hspace="2px" ></a>';
		
		// Regenerer
		$legende = $langs->trans("AgfDocRefresh");
		$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&socid=' . $socid . '&action=refresh&model=' . $model . '&cour=' . $nom_courrier . '&idform=' . $idform . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/refresh.png" border="0" align="absmiddle" hspace="2px" ></a>';
		
		// Supprimer
		$legende = $langs->trans("AgfDocDel");
		$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&socid=' . $socid . '&action=del&model=' . $model . '&cour=' . $nom_courrier . '&idform=' . $idform . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/editdelete.png" border="0" align="absmiddle" hspace="2px" ></a>';
	} else {
		// Génereration des documents
		if (file_exists(dol_buildpath('/agefodd/core/modules/agefodd/pdf/pdf_' . $model . '.modules.php'))) {
			$legende = $langs->trans("AgfDocCreate");
			$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=create&socid=' . $socid . '&model=' . $model . '&cour=' . $nom_courrier . '&idform=' . $idform . '" alt="' . $legende . '" title="' . $legende . '">';
			$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
		} else {
			$mess = $form->textwithpicto('', $langs->trans("AgfDocNoTemplate"), 1, 'warning');
		}
	}
	return $mess;
}
function show_convo_trainee($file, $session_traineeid) {
	global $langs, $conf, $id, $form, $idform;
	
	$model = 'convocation_trainee';
	$file = $model . '_' . $session_traineeid . '.pdf';
	
	if (is_file($conf->agefodd->dir_output . '/' . $file)) {
		// afficher
		$legende = $langs->trans("AgfDocOpen");
		$mess = '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=agefodd&file=' . $file . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/pdf2.png" border="0" align="absmiddle" hspace="2px" ></a>';
		
		// Regenerer
		$legende = $langs->trans("AgfDocRefresh");
		$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&sessiontraineeid=' . $session_traineeid . '&action=refresh&model=' . $model . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/refresh.png" border="0" align="absmiddle" hspace="2px" ></a>';
		
		// Supprimer
		$legende = $langs->trans("AgfDocDel");
		$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&sessiontraineeid=' . $session_traineeid . '&action=del&model=' . $model . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/editdelete.png" border="0" align="absmiddle" hspace="2px" ></a>';
		
		// Envoie par mail
		$legende = $langs->trans("AgfSendDoc");
		$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&sessiontraineeid=' . $session_traineeid . '&action=presend_convocation_trainee&mode=init" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" ></a>';
	} else {
		// Génereration des documents
		if (file_exists(dol_buildpath('/agefodd/core/modules/agefodd/pdf/pdf_' . $model . '.modules.php'))) {
			$legende = $langs->trans("AgfDocCreate");
			$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=create&sessiontraineeid=' . $session_traineeid . '&model=' . $model . '" alt="' . $legende . '" title="' . $legende . '">';
			$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
		} else {
			$mess = $form->textwithpicto('', $langs->trans("AgfDocNoTemplate"), 1, 'warning');
		}
	}
	return $mess;
}
function show_attestation_trainee($file, $session_traineeid) {
	global $langs, $conf, $id, $form, $idform;
	
	$model = 'attestation_trainee';
	$file = $model . '_' . $session_traineeid . '.pdf';
	
	if (is_file($conf->agefodd->dir_output . '/' . $file)) {
		// afficher
		$legende = $langs->trans("AgfDocOpen");
		$mess = '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=agefodd&file=' . $file . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/pdf2.png" border="0" align="absmiddle" hspace="2px" ></a>';
		
		// Regenerer
		$legende = $langs->trans("AgfDocRefresh");
		$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&sessiontraineeid=' . $session_traineeid . '&action=refresh&model=' . $model . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/refresh.png" border="0" align="absmiddle" hspace="2px" ></a>';
		
		// Supprimer
		$legende = $langs->trans("AgfDocDel");
		$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&sessiontraineeid=' . $session_traineeid . '&action=del&model=' . $model . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/editdelete.png" border="0" align="absmiddle" hspace="2px" ></a>';
		
		// Envoie par mail
		$legende = $langs->trans("AgfSendDoc");
		$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&sessiontraineeid=' . $session_traineeid . '&action=presend_attestation_trainee&mode=init" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" ></a>';
	} else {
		// Génereration des documents
		if (file_exists(dol_buildpath('/agefodd/core/modules/agefodd/pdf/pdf_' . $model . '.modules.php'))) {
			$legende = $langs->trans("AgfDocCreate");
			$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=create&sessiontraineeid=' . $session_traineeid . '&model=' . $model . '" alt="' . $legende . '" title="' . $legende . '">';
			$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
		} else {
			$mess = $form->textwithpicto('', $langs->trans("AgfDocNoTemplate"), 1, 'warning');
		}
	}
	return $mess;
}

function show_trainer_mission($session_trainerid) {
	global $langs, $conf, $id, $form, $idform;

	$model = 'mission_trainer';
	$file = $model . '_' . $session_trainerid . '.pdf';

	if (is_file($conf->agefodd->dir_output . '/' . $file)) {
		// afficher
		$legende = $langs->trans("AgfDocOpen");
		$mess = '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=agefodd&file=' . $file . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/pdf2.png" border="0" align="absmiddle" hspace="2px" ></a>';

		// Regenerer
		$legende = $langs->trans("AgfDocRefresh");
		$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&sessiontrainerid=' . $session_trainerid . '&action=refresh&model=' . $model . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/refresh.png" border="0" align="absmiddle" hspace="2px" ></a>';

		// Supprimer
		$legende = $langs->trans("AgfDocDel");
		$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&sessiontrainerid=' . $session_trainerid . '&action=del&model=' . $model . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/editdelete.png" border="0" align="absmiddle" hspace="2px" ></a>';

	} else {
		// Génereration des documents
		if (file_exists(dol_buildpath('/agefodd/core/modules/agefodd/pdf/pdf_' . $model . '.modules.php'))) {
			$legende = $langs->trans("AgfDocCreate");
			$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=create&sessiontrainerid=' . $session_trainerid . '&model=' . $model . '" alt="' . $legende . '" title="' . $legende . '">';
			$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
		} else {
			$mess = $form->textwithpicto('', $langs->trans("AgfDocNoTemplate"), 1, 'warning');
		}
	}
	return $mess;
}

function show_fac($file, $socid, $mdle) {
	global $langs, $conf, $db, $id, $form;
	
	if ($conf->global->AGF_NEW_BROWSER_WINDOWS_ON_LINK) {
		$target = ' target="_blanck" ';
	} else {
		$target = '';
	}
	
	$agf = new Agefodd_session_element($db);
	$agf_session = new Agsession($db);
	
	// Manage order
	if ($mdle == 'bc') {
		$result = $agf->fetch_by_session_by_thirdparty($id, $socid, 'order');
		$mess = '<table class="nobordernopadding">';
		foreach ( $agf->lines as $line ) {
			if ($line->element_type == 'order' && ! empty($line->comref)) {
				$mess .= '<tr><td colspan="2">';
				
				// Send order by mail
				$legende = $langs->trans("AgfFactureSeeBonMail", $line->comref);
				$mess .= '<a href="' . DOL_URL_ROOT . '/commande/fiche.php?mainmenu=commercial&id=' . $line->fk_element . '&action=presend&mode=init" alt="' . $legende . '" title="' . $legende . '" ' . $target . '>';
				$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" ></a>';
				
				// Unlink order
				$legende = $langs->trans("AgfFactureUnselectBon");
				$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?action=unlink&idelement=' . $line->id . '&id=' . $id . '&type=bc&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '">';
				$mess .= '<img src="' . dol_buildpath('/agefodd/img/unlink.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
				
				// See order card
				$legende = $langs->trans("AgfFactureSeeProp") . ' ' . $line->comref;
				$mess .= '<a href="' . DOL_URL_ROOT . '/commande/fiche.php?mainmenu=commercial&id=' . $line->fk_element . '" alt="' . $legende . '" title="' . $legende . '"  ' . $target . '>';
				$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/edit.png" border="0" align="absmiddle" hspace="2px" >' . $line->comref . '</a>';
				require_once (DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
				$order = new Commande($db);
				$order->fetch($line->fk_element);
				$mess .= $order->getLibStatut(2);
				
				$mess .= '</td></tr>';
			}
		}
		$mess .= '<tr><td  width="5%" nowrap="nowrap">';
		
		// Create Order
		$legende = $langs->trans("AgfFactureGenererBonAuto");
		$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?action=createorder&id=' . $id . '&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
		
		if (! empty($conf->global->AGF_NO_MANUAL_CREATION_DOC)) {
			// Generate order
			$legende = $langs->trans("AgfFactureGenererBon");
			$mess .= '<a href="' . DOL_URL_ROOT . '/commande/fiche.php?mainmenu=commercial&action=create&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '">';
			$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
		}
		
		// Link existing order
		$legende = $langs->trans("AgfFactureSelectBon");
		$mess .= '<a href="' . dol_buildpath('/agefodd/session/document.php', 1) . '?action=link&id=' . $id . '&type=bc&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . dol_buildpath('/agefodd/img/link.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
		
		$mess .= '<td>' . $form->textwithpicto('', $langs->trans("AgfFactureBonBeforeSelectHelp"), 1, 'help') . '</td>';
		
		$mess .= '</td></tr>';
		
		$mess .= '</table>';
	} 	// Manage Invoice
	elseif ($mdle == 'fac') {
		$order_array = array ();
		$propal_array = array ();
		
		$result = $agf->fetch_by_session_by_thirdparty($id, $socid, 'invoice');
		
		$mess = '<table class="nobordernopadding">';
		foreach ( $agf->lines as $line ) {
			if ($line->element_type == 'invoice' && ! empty($line->facnumber)) {
				$mess .= '<tr><td colspan="2">';
				
				// Go to send mail card
				$legende = $langs->trans("AgfFactureSeeFacMail", $line->facnumber);
				$mess .= '<a href="' . DOL_URL_ROOT . '/compta/facture.php?mainmenu=accountancy&id=' . $line->fk_element . '&action=presend&mode=init" alt="' . $legende . '" title="' . $legende . '" ' . $target . '>';
				$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" ></a>';
				
				// Unlink invoice
				$legende = $langs->trans("AgfFactureUnselectFac");
				$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?action=unlink&idelement=' . $line->id . '&id=' . $id . '&type=fac&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '">';
				$mess .= '<img src="' . dol_buildpath('/agefodd/img/unlink.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
				
				// See Invoice card
				$legende = $langs->trans("AgfFactureSeeFac") . ' ' . $line->facnumber;
				$mess .= '<a href="' . DOL_URL_ROOT . '/compta/facture.php?mainmenu=accountancy&facid=' . $line->fk_element . '"" alt="' . $legende . '" title="' . $legende . '" ' . $target . '>';
				$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/edit.png" border="0" align="absmiddle" hspace="2px" >' . $line->facnumber . '</a>';
				require_once (DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php');
				$invoice = new Facture($db);
				$invoice->fetch($line->fk_element);
				$mess .= $invoice->getLibStatut(2);
				$mess .= ' (' . price($invoice->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
				
				$mess .= '</td></tr>';
			}
			if ($line->element_type == 'order' && ! empty($line->comref)) {
				$order_array [$line->fk_element] = $line->comref;
			}
			if ($line->element_type == 'propal' && ! empty($line->propalref)) {
				$propal_array [$line->fk_element] = $line->propalref;
			}
		}
		
		$mess .= '<tr><td width="5%" nowrap="nowrap">';
		
		$result = $agf->fetch_by_session_by_thirdparty($id, $socid);
		foreach ( $agf->lines as $line ) {
			if ($line->element_type == 'order' && ! empty($line->comref) && ! key_exists($line->comref, $order_array)) {
				$order_array [$line->fk_element] = $line->comref;
			}
			if ($line->element_type == 'propal' && ! empty($line->propalref) && ! key_exists($line->propalref, $propal_array)) {
				$propal_array [$line->fk_element] = $line->propalref;
			}
		}
		
		if (! empty($conf->global->AGF_USE_FAC_WITHOUT_ORDER)) {
			// Create invoice from order if exists
			
			foreach ( $propal_array as $key => $val ) {
				$legende = $langs->trans("AgfFactureAddFacFromPropal") . ' ' . $val;
				$propal_static = new Propal($db);
				$propal_static->fetch($key);
				if ($propal_static->statut == 2) {
					$mess .= '<a href="' . DOL_URL_ROOT . '/compta/facture.php?mainmenu=accountancy&action=create&origin=' . $propal_static->element . '&originid=' . $key . '&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '" ' . $target . '>';
					$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
				} else {
					$mess .= img_picto($langs->trans("AgfFactureFacNoPropalSignedHelp"), 'warning');
				}
			}
			
			foreach ( $order_array as $key => $val ) {
				$commande_static = new Commande($db);
				$commande_static->fetch($key);
				if ($commande_static->statut == 1) {
					$legende = $langs->trans("AgfFactureAddFacFromOrder") . ' ' . $val;
					$mess .= '<a href="' . DOL_URL_ROOT . '/compta/facture.php?mainmenu=accountancy&action=create&origin=' . $commande_static->element . '&originid=' . $key . '&socid=' . $socid . '"  alt="' . $legende . '" title="' . $legende . '" ' . $target . '>';
					$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
				}else {
					$mess .= img_picto($langs->trans("AgfFactureFacNoOrderValidHelp"), 'warning');
				}
			}
			// link existing invoice
			$legende = $langs->trans("AgfFactureSelectFac");
			$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?action=link&id=' . $id . '&type=fac&socid=' . $socid . '" alt="' . $legende . '" alt="' . $legende . '" title="' . $legende . '">';
			$mess .= '<img src="' . dol_buildpath('/agefodd/img/link.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
		} elseif ($order_exist || $propal_exist) {
			$mess = '';
			
			foreach ( $propal_array as $key => $val ) {
				$legende = $langs->trans("AgfFactureAddFacFromPropal") . ' ' . $val;
				$propal_static = new Propal($db);
				$propal_static->fetch($key);
				if ($propal_static->statut == 2) {
					$mess .= '<a href="' . DOL_URL_ROOT . '/compta/facture.php?mainmenu=accountancy&action=create&origin=' . $propal_static->element . '&originid=' . $key . '&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '" ' . $target . '>';
					$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
				} else {
					$mess .= img_picto($langs->trans("AgfFactureFacNoPropalSignedHelp"), 'warning');
				}
				// $mess .= '<a href="' . DOL_URL_ROOT . '/compta/facture.php?mainmenu=accountancy&action=create&origin=' . $propal_static->element .
				// '&originid=' . $key . '&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '" '.$target.'>';
				// $mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px"
				// ></a>';
			}
			
			foreach ( $order_array as $key => $val ) {
				$legende = $langs->trans("AgfFactureAddFacFromOrder") . ' ' . $val;
				$commande_static = new Commande($db);
				$mess .= '<a href="' . DOL_URL_ROOT . '/compta/facture.php?mainmenu=accountancy&action=create&origin=' . $commande_static->element . '&originid=' . $key . '&socid=' . $socid . '"  alt="' . $legende . '" title="' . $legende . '" ' . $target . '>';
				$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
			}
			
			// link existing invoice
			$legende = $langs->trans("AgfFactureSelectFac");
			$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?action=link&id=' . $id . '&type=fac&socid=' . $socid . '" alt="' . $legende . '" alt="' . $legende . '" title="' . $legende . '">';
			$mess .= '<img src="' . dol_buildpath('/agefodd/img/link.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
		} else {
			$mess = $form->textwithpicto('', $langs->trans("AgfFactureFacNoBonHelp"), 1, 'help');
		}
		
		$mess .= '</td></tr>';
		
		$mess .= '</table>';
	} 	// Manage Proposal
	elseif ($mdle == 'prop') {
		
		$result = $agf->fetch_by_session_by_thirdparty($id, $socid, 'propal');
		$mess = '<table class="nobordernopadding">';
		foreach ( $agf->lines as $line ) {
			if ($line->element_type == 'propal' && ! empty($line->propalref)) {
				$mess .= '<tr><td colspan="2">';
				
				// Go to send mail card
				$legende = $langs->trans("AgfFactureSeePropMail", $line->propalref);
				$mess .= '<a href="' . DOL_URL_ROOT . '/comm/propal.php?id=' . $line->fk_element . '&mainmenu=commercial&action=presend&mode=init" alt="' . $legende . '" title="' . $legende . '" ' . $target . '>';
				$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" ></a>';
				
				// Unlink proposal
				$legende = $langs->trans("AgfFactureUnselectProp");
				$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?action=unlink&idelement=' . $line->id . '&id=' . $id . '&type=prop&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '">';
				$mess .= '<img src="' . dol_buildpath('/agefodd/img/unlink.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
				
				// See Proposal card
				$legende = $langs->trans("AgfFactureSeeProp") . ' ' . $line->propalref;
				$mess .= '<a href="' . DOL_URL_ROOT . '/comm/propal.php?id=' . $line->fk_element . '&mainmenu=commercial" alt="' . $legende . '" title="' . $legende . '" ' . $target . '>';
				$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/edit.png" border="0" align="absmiddle" hspace="2px" >' . $line->propalref . '</a>';
				require_once (DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php');
				$propal = new Propal($db);
				$propal->fetch($line->fk_element);
				$mess .= $propal->getLibStatut(2);
				$mess .= ' (' . price($propal->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
				
				$mess .= '</td></tr>';
			}
		}
		$mess .= '<tr><td width="5%" nowrap="nowrap">';
		
		$agf_session->fetch($id);
		if (empty($agf_session->fk_product)) {
			$mess .= img_picto($langs->trans("AgfFacturePropSelectProductHelp"), 'warning');
		} else {
			// Create Proposal
			$legende = $langs->trans("AgfFactureGenererPropAuto");
			$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?action=createproposal&id=' . $id . '&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '">';
			$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
		}
		
		if (empty($conf->global->AGF_NO_MANUAL_CREATION_DOC)) {
			// Generate Proposal
			$legende = $langs->trans("AgfFactureGenererProp");
			$mess .= '<a href="' . DOL_URL_ROOT . '/comm/propal.php?action=create&mainmenu=commercial&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '">';
			$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
		}
		
		// Link existing proposal
		$legende = $langs->trans("AgfFactureSelectProp");
		$mess .= '<a href="' . dol_buildpath('/agefodd/session/document.php', 1) . '?action=link&id=' . $id . '&type=prop&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '">';
		$mess .= '<img src="' . dol_buildpath('/agefodd/img/link.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
		
		$mess .= '<td>' . $form->textwithpicto('', $langs->trans("AgfFacturePropBeforeSelectHelp"), 1, 'help') . '</td>';
		
		$mess .= '</td></tr>';
		
		$mess .= '</table>';
	} else {
		$mess = 'error';
	}
	return $mess;
}
function show_facopca($file, $socid, $mdle) {
	global $langs, $conf, $db, $id, $form;
	
	if ($conf->global->AGF_NEW_BROWSER_WINDOWS_ON_LINK) {
		$target = ' target="_blanck" ';
	} else {
		$target = '';
	}
	
	$agf = new Agefodd_session_element($db);
	$agf_session = new Agsession($db);
	$result = $agf->fetch_by_session_by_thirdparty($id, $socid, 'invoice');
	$mess = '<table class="nobordernopadding">';
	foreach ( $agf->lines as $line ) {
		if ($line->element_type == 'invoice' && ! empty($line->facnumber)) {
			$mess .= '<tr><td>';
			
			// Go to send mail card
			$legende = $langs->trans("AgfFactureSeeFacMail", $line->facnumber);
			$mess .= '<a href="' . DOL_URL_ROOT . '/compta/facture.php?mainmenu=accountancy&id=' . $line->fk_element . '&action=presend&mode=init" alt="' . $legende . '" title="' . $legende . '" ' . $target . '>';
			$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" ></a>';
			
			// Unlink invoice
			$legende = $langs->trans("AgfFactureUnselectFac");
			$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?action=unlink&idelement=' . $line->id . '&id=' . $id . '&type=fac&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '">';
			$mess .= '<img src="' . dol_buildpath('/agefodd/img/unlink.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
			
			// See Invoice card
			$legende = $langs->trans("AgfFactureSeeFac") . ' ' . $line->facnumber;
			$mess .= '<a href="' . DOL_URL_ROOT . '/compta/facture.php?mainmenu=accountancy&facid=' . $line->fk_element . '"" alt="' . $legende . '" title="' . $legende . '" ' . $target . '>';
			$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/edit.png" border="0" align="absmiddle" hspace="2px" >' . $line->facnumber . '</a>';
			require_once (DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php');
			$invoice = new Facture($db);
			$invoice->fetch($line->fk_element);
			$mess .= $invoice->getLibStatut(2);
			$mess .= ' (' . price($invoice->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
			
			$mess .= '</td></tr>';
		}
	}
	
	$mess .= '<tr><td width="5%" nowrap="nowrap">';
	// Create Invoice for OPCA
	$legende = $langs->trans("AgfFactureGenererAuto");
	$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?action=createinvoice&id=' . $id . '&socid=' . $socid . '" alt="' . $legende . '" title="' . $legende . '">';
	$mess .= '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
	$mess .= '</td></tr>';
	$mess .= '</table>';
	
	return $mess;
}
/**
 * Dispaly Document line
 * 
 * @param string $intitule label
 * @param string $mdle model name
 * @param number $socid
 * @param string $nom_courrier
 */
function document_line($intitule, $mdle, $socid = 0, $nom_courrier = '') {
	print '<tr style="height:14px">' . "\n";

	// print '<td style="border:0px; width:10px">&nbsp;</td>'."\n";
	if ($mdle == 'bc' || $mdle == 'fac' || $mdle == 'prop') {
		print '<td style="width=250px;border-left:0px;" align="left">' . show_fac($mdle, $socid, $mdle) . '</td>' . "\n";
	} elseif ($mdle == 'convention') {
		print '<td style="border-left:0px; width:250px" align="left">' . show_conv($mdle, $socid, $nom_courrier) . '</td>' . "\n";
	} elseif ($mdle == 'facopca') {
		print '<td style="border-left:0px; width:250px" align="left">' . show_facopca($mdle, $socid, $nom_courrier) . '</td>' . "\n";
	} elseif ($mdle == 'convocation_trainee') {
		print '<td style="border-left:0px; width:250px" align="left">' . show_convo_trainee($mdle, $socid) . '</td>' . "\n";
	} elseif ($mdle == 'attestation_trainee') {
		print '<td style="border-left:0px; width:250px" align="left">' . show_attestation_trainee($mdle, $socid) . '</td>' . "\n";
	} elseif ($mdle == 'mission_trainer') {
		print '<td style="border-left:0px; width:250px" align="left">' . show_trainer_mission($socid).'</td>' . "\n";
	} else {
		print '<td style="border-left:0px; width:250px"  align="left">' . show_doc($mdle, $socid, $nom_courrier) . '</td>' . "\n";
	}
	print '<td style="border-right:0px;">';

	print $intitule;
	
	print '</td>' . "\n";
	
	print '</tr>';
}
function document_send_line($intitule, $mdle, $socid = 0, $nom_courrier = '') {
	global $conf, $langs, $id, $idform, $db;
	$langs->load('mails');
	print '<tr style="height:14px">' . "\n";
	print '<td style="border:0px; width:10px">&nbsp;</td>' . "\n";
	print '<td style="border-right:0px;">';
	print $intitule . '</td>' . "\n";
	if ($mdle == 'bc' || $mdle == 'fac') {
		print '<td style="border-left:0px;" align="right">' . show_fac($mdle, $socid, $mdle) . '</td></tr>' . "\n";
	} elseif ($mdle == 'convention') {
		print '<td style="border-left:0px; width:200px"  align="right">';
		$agf_conv = new Agefodd_convention($db);
		$result = $agf_conv->fetch_all($id, $socid);
		$mess = '';
		if ((count($agf_conv->lines) > 0)) {
			$mess = '';
			foreach ( $agf_conv->lines as $conv ) {
				
				$file = 'convention' . '_' . $id . '_' . $socid;
				// For backwoard compatibilty check convention file name with id of convention
				if (is_file($conf->agefodd->dir_output . '/' . $file . '.pdf')) {
					$file = $conf->agefodd->dir_output . '/' . $file . '.pdf';
				} elseif (is_file($conf->agefodd->dir_output . '/' . $file . '_' . $conv->id . '.pdf')) {
					$file = $conf->agefodd->dir_output . '/' . $file . '_' . $conv->id . '.pdf';
				} else {
					$file = '';
				}
				
				// Check if file exist
				/*$filename = 'convention_' . $id . '_' . $socid . '.pdf';
				$file = $conf->agefodd->dir_output . '/' . $filename;*/
				if (file_exists($file)) {
					$mess .= '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&socid=' . $socid . '&convid=' . $conv->id . '&action=presend_convention&mode=init"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" alt="send" /> ' . $langs->trans('SendMail') . '</a>';
				} else {
					$mess .= $langs->trans('AgfDocNotDefined');
				}
				$mess .= '<BR>';
			}
		} else {
			$mess .= $langs->trans('AgfDocNotDefined');
		}
		
		print $mess;
		
		print '</td></tr>' . "\n";
	} else if ($mdle == 'fiche_presence') {
		
		print '<td style="border-left:0px; width:200px"  align="right">';
		// Check if file exist
		// $filename = 'fiche_presence_'.$id.'_'.$socid.'.pdf';
		$filename = 'fiche_presence_' . $id . '.pdf';
		$file = $conf->agefodd->dir_output . '/' . $filename;
		if (file_exists($file)) {
			print '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=presend_presence&mode=init"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" alt="send" /> ' . $langs->trans('SendMail') . '</a>';
		} else
			print $langs->trans('AgfDocNotDefined');
		print '</td></tr>' . "\n";
	} else if ($mdle == 'attestation') {
		print '<td style="border-left:0px; width:200px"  align="right">';
		// Check if file exist
		$filename = 'attestation_' . $id . '_' . $socid . '.pdf';
		$file = $conf->agefodd->dir_output . '/' . $filename;
		if (file_exists($file)) {
			print '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&socid=' . $socid . '&action=presend_attestation&mode=init"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" alt="send" /> ' . $langs->trans('SendMail') . '</a>';
		} else
			print $langs->trans('AgfDocNotDefined');
		print '</td></tr>' . "\n";
	} elseif ($mdle == 'cloture') {
		print '<td style="border-left:0px; width:200px"  align="right">';
		// Check if file exist
		$filename = 'courrier-cloture_' . $id . '_' . $socid . '.pdf';
		$file = $conf->agefodd->dir_output . '/' . $filename;
		if (file_exists($file)) {
			print '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&socid=' . $socid . '&action=presend_cloture&mode=init"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" alt="send" /> ' . $langs->trans('SendMail') . '</a>';
		} else
			print $langs->trans('AgfDocNotDefined');
		print '</td></tr>' . "\n";
	} elseif ($mdle == 'accueil') {
		print '<td style="border-left:0px; width:200px"  align="right">';
		// Check if file exist
		$filename = 'courrier-accueil_' . $id . '_' . $socid . '.pdf';
		$file = $conf->agefodd->dir_output . '/' . $filename;
		if (file_exists($file)) {
			print '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&socid=' . $socid . '&action=presend_accueil&mode=init"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" alt="send" /> ' . $langs->trans('SendMail') . '</a>';
		} else
			print $langs->trans('AgfDocNotDefined');
		print '</td></tr>' . "\n";
	} elseif ($mdle == 'convocation') {
		print '<td style="border-left:0px; width:200px"  align="right">';
		// Check if file exist
		$filename = 'convocation_' . $id . '_' . $socid . '.pdf';
		$file = $conf->agefodd->dir_output . '/' . $filename;
		if (file_exists($file)) {
			print '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&socid=' . $socid . '&action=presend_convocation&mode=init"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" alt="send" /> ' . $langs->trans('SendMail') . '</a>';
		} else
			print $langs->trans('AgfDocNotDefined');
		print '</td></tr>' . "\n";
	} elseif ($mdle == 'conseils') {
		print '<td style="border-left:0px; width:200px"  align="right">';
		// Check if file exist
		$filename = 'conseils_' . $id . '.pdf';
		$file = $conf->agefodd->dir_output . '/' . $filename;
		if (file_exists($file)) {
			print '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=presend_conseils&mode=init"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" alt="send" /> ' . $langs->trans('SendMail') . '</a>';
		} else
			print $langs->trans('AgfDocNotDefined');
		print '</td></tr>' . "\n";
	} elseif ($mdle == 'fiche_pedago') {
		print '<td style="border-left:0px; width:200px"  align="right">';
		// Check if file exist
		$filename = 'fiche_pedago_' . $idform . '.pdf';
		$file = $conf->agefodd->dir_output . '/' . $filename;
		if (file_exists($file)) {
			print '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=presend_pedago&mode=init"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" alt="send" /> ' . $langs->trans('SendMail') . '</a>';
		} else
			print $langs->trans('AgfDocNotDefined');
		print '</td></tr>' . "\n";
	}elseif ($mdle == 'mission_trainer') {
		print '<td style="border-left:0px; width:200px"  align="right">';
		// Check if file exist
		$filename = 'mission_trainer_' . $socid . '.pdf';
		$file = $conf->agefodd->dir_output . '/' . $filename;
		if (file_exists($file)) {
			print '<a href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&sessiontrainerid='.$socid.'&action=presend_mission_trainer&mode=init"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/stcomm0.png" border="0" align="absmiddle" hspace="2px" alt="send" /> ' . $langs->trans('SendMail') . '</a>';
		} else
			print $langs->trans('AgfDocNotDefined');
		print '</td></tr>' . "\n";
	} else {
		print '<td style="border-left:0px; width:200px"  align="right">';
		// Check if file exist
		print $langs->trans('AgfDocNotDefined');
		print '</td></tr>' . "\n";
	}
}
