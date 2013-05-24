<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
* Copyright (C) 2012		Florian Henry	<florian.henry@open-concept.pro>
* Copyright (C) 2012		JF FERRY	<jfefe@aternatik.fr>
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
 * 	\file		/agefodd/session/subscribers.php
* 	\brief		Page présentant la liste des documents administratif disponibles dans Agefodd
*/


$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory
if (! $res) die("Include of main fails");

dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_stagiaire_certif.class.php');
dol_include_once('/contact/class/contact.class.php');
dol_include_once('/agefodd/class/html.formagefodd.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');

// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$action=GETPOST('action','alpha');
$id=GETPOST('id','int');
$confirm=GETPOST('confirm','alpha');
$certif_save_x=GETPOST('certif_save_x','alpha');

$mesg = '';

if ($action=='edit' && $user->rights->agefodd->creer) {

	$certif_sta_id=GETPOST('modstaid','int');
	$certif_session_sta_id=GETPOST('sessionstarowid','int');

	$certif_code=GETPOST('certif_code','alpha');
	$certif_label=GETPOST('certif_label','alpha');
	$certif_dt_start=dol_mktime(0,0,0,GETPOST('dt_startmonth','int'),GETPOST('dt_startday','int'),GETPOST('dt_startyear','int'));
	$certif_dt_end=dol_mktime(0,0,0,GETPOST('dt_endmonth','int'),GETPOST('dt_endday','int'),GETPOST('dt_endyear','int'));

	if (!empty($certif_save_x)) {
		$agf_certif = new Agefodd_stagiaire_certif($db);
		$result=$agf_certif->fetch(0,$certif_sta_id,$id,$certif_session_sta_id);
		if ($result<0) {
			dol_syslog("agefodd:session:subscribers error=".$agf_certif->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf_certif->error.'</div>';
		}else {

			$agf_certif->certif_code=$certif_code;
			$agf_certif->certif_label=$certif_label;
			$agf_certif->certif_dt_start=$certif_dt_start;
			$agf_certif->certif_dt_end=$certif_dt_end;

			//Existing certification
			if (!empty($agf_certif->id)) {
				$result=$agf_certif->update($user);
				if ($result<0) {
					dol_syslog("agefodd:session:subscribers_certif error=".$agf_certif->error, LOG_ERR);
					$mesg = '<div class="error">'.$agf_certif->error.'</div>';
				}else {
					Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
					exit;
				}
			}else {
				//New cerficiation
				$agf_certif->fk_session_agefodd=$id;
				$agf_certif->fk_session_stagiaire=$certif_session_sta_id;
				$agf_certif->fk_stagiaire=$certif_sta_id;

				$result=$agf_certif->create($user);
				if ($result<0) {
					dol_syslog("agefodd:session:subscribers_certif error=".$agf_certif->error, LOG_ERR);
					$mesg = '<div class="error">'.$agf_certif->error.'</div>';
				}else {
					Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
					exit;
				}
			}
		}
	}
}


/*
 * Actions delete certif
*/

if ($action == 'confirm_delete_certif' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	$certifrowid = GETPOST('certifrowid','int');

	$agf_certif = new Agefodd_stagiaire_certif($db);
	$result=$agf_certif->fetch($certifrowid);
	if ($result<0) {
		dol_syslog("agefodd:session:subscribers_certif error=".$agf_certif->error, LOG_ERR);
		$mesg = '<div class="error">'.$agf_certif->error.'</div>';
	}else {
		if (!empty($agf_certif->id)) {
			$result=$agf_certif->delete($user);
			if ($result<0) {
				dol_syslog("agefodd:session:subscribers_certif error=".$agf_certif->error, LOG_ERR);
				$mesg = '<div class="error">'.$agf_certif->error.'</div>';
			}else {
				Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
				exit;
			}
		}
	}
}


/*
 * View
*/
$arrayofcss = array('/agefodd/css/agefodd.css');
llxHeader($head, $langs->trans("AgfCertificate"),'','','','','',$arrayofcss,'');

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

dol_htmloutput_mesg($mesg);

if (!empty($id))
{
	$agf = new Agsession($db);
	$result = $agf->fetch($id);

	$head = session_prepare_head($agf);

	dol_fiche_head($head, 'certificate', $langs->trans("AgfCertificate"), 0, 'group');



	/*
	 * Confirmation de la suppression
	*/
	if ($_POST["certif_remove_x"])
	{
		// Param url = id de la ligne stagiaire dans session - id session
		$ret=$form->form_confirm($_SERVER['PHP_SELF']."?certifrowid=".$_POST["certifrowid"].'&id='.$id,$langs->trans("AgfDeleteCertif"),$langs->trans("AgfConfirmDeleteCertif"),"confirm_delete_certif",'','',1);
		if ($ret == 'html') print '<br>';
	}

	print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
	print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
	print '</div>';

	// Print session card
	$agf->printSessionInfo();

	print '&nbsp';

	print '<div class="tabBar">'."\n";
	print '<table class="border" width="100%">';

	/*
	 *  Bloc d'affichage et de modification des infos sur les stagiaires
	*
	*/
	$stagiaires = new Agsession($db);
	$stagiaires->fetch_stagiaire_per_session($agf->id);
	$nbstag = count($stagiaires->line);
	if ($nbstag > 0)
	{
		for ($i=0; $i < $nbstag; $i++)
		{
			if ($stagiaires->line[$i]->id == $_POST["modstaid"] && $_POST["certif_remove_x"]  && ($action == 'edit')) print '<tr bgcolor="#d5baa8">'."\n";
			else print '<tr>'."\n";

			print '<td width="3%" align="center">'.($i+1).'</td>'."\n";

			print '<td width="40%">'."\n";
			if (strtolower($stagiaires->line[$i]->nom) == "undefined")
			{
				print $langs->trans("AgfUndefinedStagiaire");
			}
			else
			{
				$trainee_info = '<a href="'.dol_buildpath('/agefodd/trainee/card.php',1).'?id='.$stagiaires->line[$i]->id.'">';
				$trainee_info .= img_object($langs->trans("ShowContact"),"contact").' ';
				$trainee_info .= strtoupper($stagiaires->line[$i]->nom).' '.ucfirst($stagiaires->line[$i]->prenom).'</a>';
				$contact_static= new Contact($db);
				$contact_static->civilite_id = $stagiaires->line[$i]->civilite;
				$trainee_info .= ' ('.$contact_static->getCivilityLabel().')';
					
				print'<label for="'.$htmlname.'" style="width:45%; display: inline-block;margin-left:5px;">'.$trainee_info.'</label>';
			}

				
			print '</td>'."\n";
			print '<td>';
				
			$agf_certif = new Agefodd_stagiaire_certif($db);
			$agf_certif->fetch(0,$stagiaires->line[$i]->id,$stagiaires->line[$i]->sessid,$stagiaires->line[$i]->stagerowid);
				
			if ($stagiaires->line[$i]->id == $_POST["modstaid"] && ! $_POST["certif_remove_x"] && ($action == 'edit'))
			{

				print '<form name="obj_update_'.$i.'" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
				print '<input type="hidden" name="sessionstarowid" value="'.$stagiaires->line[$i]->stagerowid.'">'."\n";
				print '<input type="hidden" name="modstaid" value="'.$stagiaires->line[$i]->id.'">'."\n";
				print '<table class="nobordernopadding">';
					
				print '<tr><td>'.$langs->trans('AgfCertifCode').'</td><td><input type="text" size="10" name="certif_code" value="'.$agf_certif->certif_code.'"></td></tr>'."\n";
				print '<tr><td>'.$langs->trans('AgfCertifLabel').'</td><td><input type="text" size="10" name="certif_label"  value="'.$agf_certif->certif_label.'"></td></tr>'."\n";
				print '<tr><td>'.$langs->trans('AgfCertifDateSt').'</td><td>';
				print $form->select_date($agf_certif->certif_dt_start, 'dt_start','','',1,'obj_update_'.$i,1,1);
				print '</td></tr>'."\n";
				print '<tr><td>'.$langs->trans('AgfCertifDateEnd').'</td><td>';
				print $form->select_date($agf_certif->certif_dt_end, 'dt_end','','',1,'obj_update_'.$i,1,1);
				print '</td></tr>'."\n";
				print '</table>'."\n";
				print '</td>';

				print '</td>'."\n";
				print '<td>'."\n";
				if ($user->rights->agefodd->modifier)
				{
					print '<input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="certif_save" alt="'.$langs->trans("AgfModSave").'" ">';
				}
				print '</td>';
				print '</form>';

			}
			elseif ($action == 'edit')
			{
				print '<form name="obj_update_'.$i.'" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
				print '<input type="hidden" name="sessionstarowid" value="'.$stagiaires->line[$i]->stagerowid.'">'."\n";
				print '<input type="hidden" name="modstaid" value="'.$stagiaires->line[$i]->id.'">'."\n";
				print '<input type="hidden" name="certifrowid" value="'.$agf_certif->id.'">'."\n";
				if (!empty($agf_certif->id)) {
					print '<table class="nobordernopadding" width="100%">'."\n";
	
					print '<tr class="pair"><td>'.$langs->trans('AgfCertifCode').':</td><td>'.$agf_certif->certif_code.'</td></tr>'."\n";
					print '<tr class="impair"><td>'.$langs->trans('AgfCertifLabel').':</td><td>'.$agf_certif->certif_label.'</td></tr>'."\n";
					print '<tr class="pair"><td>'.$langs->trans('AgfCertifDateSt').':</td><td>';
					print dol_print_date($agf_certif->certif_dt_start,'daytext');
					print '</td></tr>'."\n";
					print '<tr class="impair"><td>'.$langs->trans('AgfCertifDateEnd').':</td><td>';
					print dol_print_date($agf_certif->certif_dt_end,'daytext');
					print '</td></tr>'."\n";
						
					print '</table>'."\n";
				}
				else {
					print $langs->trans('AgfNoCertif');
				}

				print '</td>'."\n";
				print '<td>';
				if ($user->rights->agefodd->modifier)
				{
					print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" name="certif_edit" alt="'.$langs->trans("AgfModSave").'">';
				}
				print '&nbsp;';
				if ($user->rights->agefodd->creer)
				{
					print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" border="0" name="certif_remove" alt="'.$langs->trans("AgfModSave").'">';
				}
				print '</td>';

				print '</form>'."\n";
			}
			else {
				if (!empty($agf_certif->id)) {
					print '<table class="nobordernopadding" width="100%">'."\n";
	
					print '<tr class="pair"><td>'.$langs->trans('AgfCertifCode').':</td><td>'.$agf_certif->certif_code.'</td></tr>'."\n";
					print '<tr class="impair"><td>'.$langs->trans('AgfCertifLabel').':</td><td>'.$agf_certif->certif_label.'</td></tr>'."\n";
					print '<tr class="pair"><td>'.$langs->trans('AgfCertifDateSt').':</td><td>';
					print dol_print_date($agf_certif->certif_dt_start,'daytext');
					print '</td></tr>'."\n";
					print '<tr class="impair"><td>'.$langs->trans('AgfCertifDateEnd').':</td><td>';
					print dol_print_date($agf_certif->certif_dt_end,'daytext');
					print '</td></tr>'."\n";
						
					print '</table>'."\n";
				}
				else {
					print $langs->trans('AgfNoCertif');
				}
				print '</td>'."\n";
			}
			print '</tr>'."\n";
		}

	}

}

print '</table>';
print '</div>';


/*
 * Barre d'actions
*
*/

print '<div class="tabsAction">';

if ($action != 'edit' && (!empty($agf->id)))
{
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'">'.$langs->trans('AgfModifyCertificate').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('AgfModifyCertificate').'</a>';
	}

}

print '</div>';

$db->close();
llxFooter();