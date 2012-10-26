<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
* Copyright (C) 2012		Florian Henry	<florian.henry@open-concept.pro>
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
 * 	\file		/agefodd/session/subscribers.php
 * 	\brief		Page présentant la liste des documents administratif disponibles dans Agefodd
*/

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/contact/class/contact.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
dol_include_once('/agefodd/class/html.formagefodd.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$action=GETPOST('action','alpha');
$id=GETPOST('id','int');
$confirm=GETPOST('confirm','alpha');
$form_update_x=GETPOST('form_update_x','alpha');
$form_add_x=GETPOST('form_add_x','alpha');

$mesg = '';


/*
 * Actions delete formateur
*/

if ($action == 'confirm_delete_form' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	$obsid=GETPOST('opsid','int');

	$agf = new Agefodd_session_formateur($db);
	$result = $agf->remove($obsid);

	if ($result > 0)
	{
		Header ("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
	else
	{
		dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
		$mesg = '<div class="error">'.$agf->error.'</div>';
	}
}


if ($action=='edit' && $user->rights->agefodd->creer) {

	if($form_update_x > 0)
	{
		$agf = new Agefodd_session_formateur($db);

		$agf->opsid = GETPOST('opsid','int');
		$agf->formid = GETPOST('formid','int');
		$result = $agf->update($user);

		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	}

	if($form_add_x > 0)
	{
		$agf = new Agefodd_session_formateur($db);

		$agf->sessid = GETPOST('sessid','int');
		$agf->formid = GETPOST('formid','int');
		$result = $agf->create($user);

		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd:session:trainer error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	}
}


/*
 * View
*/

llxHeader();

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

dol_htmloutput_mesg($mesg);

if (!empty($id))
{
	$agf = new Agsession($db);
	$result = $agf->fetch($id);

	$head = session_prepare_head($agf);

	dol_fiche_head($head, 'trainers', $langs->trans("AgfSessionDetail"), 0, 'group');

	print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
	print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
	print '</div>';

	// Print session card
	$agf->printSessionInfo();

	print '&nbsp';

	if ($action == 'edit')
	{
		print_barre_liste($langs->trans("AgfFormateur"),"", "","","","",'',0);

		/*
		 * Confirmation de la suppression
		*/
		if ($_POST["form_remove_x"]){
			// Param url = id de la ligne formateur dans session - id session
			$ret=$form->form_confirm($_SERVER['PHP_SELF']."?opsid=".$_POST["opsid"].'&id='.$id,$langs->trans("AgfDeleteForm"),$langs->trans("AgfConfirmDeleteForm"),"confirm_delete_form",'','',1);
			if ($ret == 'html') print '<br>';
		}

		print '<div class="tabBar">';
		print '<table class="border" width="100%">';

		// Bloc d'affichage et de modification des formateurs
		$formateurs = new Agefodd_session_formateur($db);
		$nbform = $formateurs->fetch_formateur_per_session($agf->id);
		if ($nbform > 0) {
			for ($i=0; $i < $nbform; $i++)	{
				if ($formateurs->line[$i]->opsid == $_POST["opsid"] && $_POST["form_remove_x"]) print '<tr bgcolor="#d5baa8">';
				else print '<tr>';
				print '<form name="form_update_'.$i.'" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
				print '<input type="hidden" name="action" value="edit">'."\n";
				print '<input type="hidden" name="sessid" value="'.$formateurs->line[$i]->sessid.'">'."\n";
				print '<input type="hidden" name="opsid" value="'.$formateurs->line[$i]->opsid.'">'."\n";
					
				print '<td width="20px" align="center">'.($i+1).'</td>';
					
				if ($formateurs->line[$i]->opsid == $_POST["opsid"] && ! $_POST["form_remove_x"])
				{
					print '<td width="300px" style="border-right: 0px">';
					print $formAgefodd->select_formateur($formateurs->line[$i]->formid, "formid");
					if ($user->rights->agefodd->modifier)
					{
						print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="form_update" alt="'.$langs->trans("AgfModSave").'" ">';
					}
					print '</td>';
				}
				else
				{
					print '<td width="300px"style="border-right: 0px;">';
					// info formateur
					if (strtolower($formateurs->line[$i]->name) == "undefined")	{
						print $langs->trans("AgfUndefinedStagiaire");
					}
					else {
						print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$formateurs->line[$i]->socpeopleid.'">';
						print img_object($langs->trans("ShowContact"),"contact").' ';
						print strtoupper($formateurs->line[$i]->name).' '.ucfirst($formateurs->line[$i]->firstname).'</a>';
					}
					print '</td>';
					print '<td>';
						
						
					if ($user->rights->agefodd->modifier)
					{
						print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" name="form_edit" alt="'.$langs->trans("AgfModSave").'">';
					}
					print '&nbsp;';
					if ($user->rights->agefodd->creer)
					{
						print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" border="0" name="form_remove" alt="'.$langs->trans("AgfModSave").'">';
					}
					print '</td>'."\n";
				}
				print '</form>'."\n";
				print '</tr>'."\n";
			}
		}
			
		// Champs nouveau formateur
		if (isset($_POST["newform"])) {
			print '<tr>';
			print '<form name="form_update_'.($i + 1).'" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
			print '<input type="hidden" name="action" value="edit">'."\n";
			print '<input type="hidden" name="sessid" value="'.$agf->id.'">'."\n";
			print '<td width="20px" align="center">'.($i+1).'</td>';
			print '<td>';
			print $formAgefodd->select_formateur($formateurs->line[$i]->formid, "formid", 's.rowid NOT IN (SELECT fk_agefodd_formateur FROM '.MAIN_DB_PREFIX.'agefodd_session_formateur WHERE fk_session='.$id.')',1);
			if ($user->rights->agefodd->modifier) {
				print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="form_add" alt="'.$langs->trans("AgfModSave").'">';
			}
			print '</td>';
			print '</form>';
			print '</tr>'."\n";
		}

		print '</table>';
		if (!isset($_POST["newform"]))	{
			print '</div>';
			//print '&nbsp';
			print '<table style="border:0;" width="100%">';
			print '<tr><td align="right">';
			print '<form name="newform" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
			print '<input type="hidden" name="action" value="edit">'."\n";
			print '<input type="hidden" name="newform" value="1">'."\n";
			print '<input type="submit" class="butAction" value="'.$langs->trans("AgfFormateurAdd").'">';
			print '</td></tr>';
			print '</form>';
			print '</table>';
		}
		print '</div>';
	}
	else {
		// Affichage en mode "consultation"
		print '&nbsp';
		print '<table class="border" width="100%">';
			
		$formateurs = new Agefodd_session_formateur($db);
		$nbform = $formateurs->fetch_formateur_per_session($agf->id);
		print '<tr><td width="20%" valign="top">';
		print $langs->trans("AgfFormateur");
		if ($nbform > 0) print ' ('.$nbform.')';
		print '</td>';
		if ($nbform < 1)
		{
			print '<td style="text-decoration: blink;">'.$langs->trans("AgfNobody").'</td></tr>';
		}
		else
		{
			print '<td>';
			for ($i=0; $i < $nbform; $i++) {
				// Infos formateurs
				print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$formateurs->line[$i]->socpeopleid.'">';
				print img_object($langs->trans("ShowContact"),"contact").' ';
				print strtoupper($formateurs->line[$i]->name).' '.ucfirst($formateurs->line[$i]->firstname).'</a>';
				if ($i < ($nbform - 1)) print ',&nbsp;&nbsp;';
			}
			print '</td>';
			print "</tr>\n";
		}
		print "</table>";
		print '</div>';
	}
}

/*
 * Barre d'actions
*
*/

print '<div class="tabsAction">';

if ($action != 'create' && $action != 'edit' && (!empty($agf->id)))
{
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'">'.$langs->trans('Modify').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Modify').'</a>';
	}
}

print '</div>';

llxFooter('');
?>
