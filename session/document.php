<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
* Copyright (C) 2012       Florian Henry   <florian.henry@open-concept.pro>
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
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
 * 	\file		/agefodd/session/document.php
 * 	\brief		Page présentant la liste des documents administratif disponibles dans Agefodd
*/

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
dol_include_once('/agefodd/class/agefodd_facture.class.php');
dol_include_once('/agefodd/class/agefodd_convention.class.php');
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/class/html.formagefodd.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/commande/class/commande.class.php');
dol_include_once('/agefodd/lib/agefodd_document.lib.php');


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$action=GETPOST('action','alpha');
$id=GETPOST('id','int');
$socid=GETPOST('socid','int');


$mesg = '';

// lie une facture ou un bon de commande à la session
if($action == 'link_confirm' && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_facture($db);
	$result = $agf->fetch($id, $socid);

	// si existe déjà, on met à jour
	if ($agf->id)
	{
		if ($_POST["type"] == 'bc') $agf->comid=$_POST["select"];
		if ($_POST["type"] == 'fac') $agf->facid=$_POST["select"];
		$result2 = $agf->update($user);
	}
	// si nouveau, on créé
	else
	{
		if ($_POST["type"] == 'bc')
		{
			$agf->comid=$_POST["select"];
			$agf->facid="";
		}
		$agf->sessid = $id;
		$agf->socid = $socid;
		$result2 = $agf->create($user);
	}

	if ($result2)
	{
		Header( 'Location: '.$_SERVER['PHP_SELF'].'?id='.$id);
		exit;
	}
	else
	{
		dol_syslog("CommonObject::agefodd error=".$agf->error, LOG_ERR);
		$mesg = "Document linked error" . $agf->error;
	}
}

// Casse le lien entre une facture ou un bon de commande et la session
if($action == 'unlink' && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_facture($db);
	$result = $agf->fetch($id, $socid);

	// si existe déjà, on met à jour
	if ($agf->id)
	{
		if ($_GET["type"] == 'bc') $agf->comid="";
		if ($_GET["type"] == 'fac') $agf->facid="";
		$result2 = $agf->update($user);
	}
	if ($result2)
	{
		Header( 'Location: '.$_SERVER['PHP_SELF'].'?id='.$id);
		exit;
	}
	else
	{
		dol_syslog("CommonObject::agefodd error=".$agf->error, LOG_ERR);
		$mesg = "Document unlink error".$agf->error;

	}

}


/*
 * View
*/

/*
 * Action create and refresh pdf document
*/
if (($action == 'create' || $action == 'refresh' ) && $user->rights->agefodd->creer)
{
	$cour=GETPOST('cour','alpha');
	$model=GETPOST('model','alpha');
	$idform=GETPOST('idform','alpha');

	// Define output language
	$outputlangs = $langs;
	$newlang=GETPOST('lang_id','alpha');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	$id_tmp= $id;
	if (!empty($cour)) $file = $model.'-'.$cour.'_'.$id.'_'.$socid.'.pdf';
	elseif(!empty($socid)) $file = $model.'_'.$id.'_'.$socid.'.pdf';
	elseif ($model=='fiche_pedago') {
		$file=$model.'_'.$idform.'.pdf';
		$id_tmp=$idform;
	}
	else $file = $model.'_'.$id.'.pdf';
	$result = agf_pdf_create($db, $id_tmp, '', $model, $outputlangs, $file, $socid, $cour);
}

/*
 * Action delete pdf document
*/
if ($action == 'del' && $user->rights->agefodd->creer)
{
	$cour=GETPOST('cour','alpha');
	$model=GETPOST('model','alpha');
	$idform=GETPOST('idform','alpha');

	if (!empty($cour))
		$file = $conf->agefodd->dir_output.'/'.$model.'-'.$cour.'_'.$id.'_'.$socid.'.pdf';
	elseif (!empty($socid))
	$file = $conf->agefodd->dir_output.'/'.$model.'_'.$id.'_'.$socid.'.pdf';
	elseif ($model=='fiche_pedago') {
		$file = $conf->agefodd->dir_output.'/'.$model.'_'.$idform.'.pdf';
	}
	else
		$file = $conf->agefodd->dir_output.'/'.$model.'_'.$id.'.pdf';

	if (is_file($file)) unlink($file);
	else
	{
		$error = $file.' : '.$langs->trans("AgfDocDelError");
		dol_syslog("Agefodd::document::del error=".$error, LOG_ERR);
	}
}


llxHeader('',$langs->trans("AgfSessionDetail"));

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

dol_htmloutput_mesg($mesg);


// Selection du bon de commande ou de la facture à lier
if (($action == 'link' ) && $user->rights->agefodd->creer)
{
	$agf = new Agsession($db);
	$agf->fetch($id);

	$head = session_prepare_head($agf);

	dol_fiche_head($head, 'document', $langs->trans("AgfSessionDetail"), 0, 'user');

	print '<div width=100% align="center" style="margin: 0 0 3px 0;">'."\n";
	print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
	print '</div>'."\n";

	print '<table class="border" width="100%">'."\n";

	print '<tr class="liste_titre">'."\n";
	print '<td colspan=3>';
	print  '<a href="#">'.$langs->trans("AgfCommonDocs").'</a></td>'."\n";
	print '</tr>'."\n";

	print '<tr class="liste">'."\n";

	// creation de la liste de choix
	$agf_liste = new Agefodd_facture($db);
	$result = $agf_liste->fetch_fac_per_soc($_GET["socid"], $_GET["type"]);
	$num = count($agf_liste->line);
	if ($num > 0)
	{
		print '<form name="fact_link" action="document.php?action=link_confirm&id='.$id.'"  method="post">'."\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
		print '<input type="hidden" name="socid" value="'.$_GET["socid"].'">'."\n";
		print '<input type="hidden" name="type" value="'.$_GET["type"].'">'."\n";

		$var=True;
		$options = '<option value=""></option>'."\n";;
		for ($i = 0; $i < $num; $i++)
		{
			$options .= '<option value="'.$agf_liste->line[$i]->id.'">'.$agf_liste->line[$i]->ref.'</option>'."\n";
		}
		$select = '<select class="flat" name="select">'."\n".$options."\n".'</select>'."\n";

		print '<td width="250px">';
		($_GET["type"] == 'bc') ? print $langs->trans("AgfFactureBcSelectList") : print $langs->trans("AgfFactureFacSelectList");
		print '</td>'."\n";
		print '<td>'.$select.'</td>'."\n";
		if ($user->rights->agefodd->modifier)
		{
			print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="bt_save" alt="'.$langs->trans("AgfModSave").'"></td>'."\n";
		}
		print '</form>';
	}
	else
	{
		print '<td colspan=3>';
		($_GET["type"] == 'bc') ? print $langs->trans("AgfFactureBcNoResult") : print $langs->trans("AgfFactureFacNoResult");
		print '</td>';
	}
	print '</tr>'."\n";

	print '</div>'."\n";
	exit;
}

if (!empty($id))
{
	$agf = new Agsession($db);
	$agf->fetch($id);

	$result = $agf->fetch_societe_per_session($id);

	if ($result)
	{
		$idform = $agf->formid;

		// Affichage en mode "consultation"
		$head = session_prepare_head($agf);

		dol_fiche_head($head, 'document', $langs->trans("AgfSessionDetail"), 0, 'generic');


		/*
		 * Confirmation de la suppression
		*/
		if ($action == 'delete')
		{
			$ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$id,$langs->trans("AgfDeleteOps"),$langs->trans("AgfConfirmDeleteOps"),"confirm_delete");
			if ($ret == 'html') print '<br>';
		}

		print '<div width=100% align="center" style="margin: 0 0 3px 0;">'."\n";
		print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
		print '</div>'."\n";

		print '<table class="border" width="100%">'."\n";

		print '<tr class="liste_titre">'."\n";
		print '<td colspan=3>';
		print $langs->trans("AgfCommonDocs").'</td>'."\n";
		print '</tr>'."\n";


		print '<tr><td colspan=3 style="background-color:#d5baa8;">'.$langs->trans("AgfBeforeTraining").'</td></tr>'."\n";
		document_line("Fiche pédagogique", 2, 'fiche_pedago');
		document_line("Conseils pratiques", 2, 'conseils');

		// Pendant la formation
		print '<tr><td colspan=3 style="background-color:#d5baa8;">'.$langs->trans("AgfDuringTraining").'</td></tr>'."\n";
		document_line("Fiche de présence", 2, "fiche_presence");
		document_line("Fiche d'évaluation", 2, "fiche_evaluation");

		print '</table>'."\n";
		print '&nbsp;'."\n";

		$linecount = count($agf->line);

		for ($i=0; $i < $linecount ; $i++)
		{
			if (!empty($agf->line[$i]->socid))
			{
				$ext = '_'.$id.'_'.$agf->line[$i]->socid.'.pdf';

				${
					'flag_bc_'.$agf->line[$i]->socid} = 0;

					print '<table class="border" width="100%">'."\n";

					print '<tr class="liste_titre">'."\n";
					print '<td colspan=3>';
					print  '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$agf->line[$i]->socid.'">'.$agf->line[$i]->socname.'</a></td>'."\n";
					print '</tr>'."\n";

					// Avant la formation
					print '<tr><td colspan=3 style="background-color:#d5baa8;">Avant la formation</td></tr>'."\n";
					document_line("Bon de commande", 2, "bc", $agf->line[$i]->socid);
					document_line("Convention de formation", 2, "convention", $agf->line[$i]->socid);
					document_line("Convocation", 2, 'convocation', $agf->line[$i]->socid);
					document_line("Courrier accompagnant l'envoi des conventions de formation", 2, "courrier", $agf->line[$i]->socid,'convention');
					document_line("Courrier accompagnant l'envoi du dossier d'accueil", 2, "courrier", $agf->line[$i]->socid, 'accueil');

					// Après la formation
					print '<tr><td colspan=3 style="background-color:#d5baa8;">Après la formation</td></tr>'."\n";
					document_line("Attestations de formation", 2, "attestation", $agf->line[$i]->socid);

					$text_fac = "Facture";
					if($agf->line[$i]->type_session) { // session inter
						$agfstat = new Agsession($db);
						// load les infos OPCA pour la session
						$agfstat->getOpcaForTraineeInSession($agf->line[$i]->socid,$agf->line[$i]->sessid);
						// Facture à l'OPCA si subrogation
						$soc_to_select = ($agfstat->is_OPCA?$agfstat->fk_soc_OPCA:$agf->line[$i]->socid);

						// Si subrogation et info renseigné
						if ($soc_to_select > 0 && $agfstat->is_OPCA)
						{
							$text_fac.=' (ajouter contact OPCA en tant que subrogation)';
						}
						elseif(!$agfstat->is_OPCA) // Pas de subrogation
						{
							$text_fac.=" (pas de subrogation)";
						}
						else // OPCA non renseignée
						{
							$text_fac.= ' <span class="error">subrogation : aucun tiers indiqué pour le contact facturation de l\'OPCA. <a href="'.dol_buildpath("/agefodd/session/subscribers.php",1).'?action=edit&id='.$id.'">'.$langs->trans('AgfModifySubscribersAndSubrogation').'</a></span>';
						}
					}
					document_line($text_fac, 2, "fac",$agf->line[$i]->socid);

					document_line("Courrier accompagnant l'envoi du dossier de clôture", 2, "courrier", $agf->line[$i]->socid, 'cloture');
					print '</table>';
					if ($i < $linecount) print '&nbsp;'."\n";
			}
		}
		print '</div>'."\n";
	}

}

llxFooter('');
?>
