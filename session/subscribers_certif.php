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

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);

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
$stag_update_x=GETPOST('certif_update_x','alpha');
$stag_add_x=GETPOST('certif_add_x','alpha');

$mesg = '';
if ($action=='edit' && $user->rights->agefodd->creer) {


}


/*
 * Actions delete stagiaire
*/

if ($action == 'confirm_delete_certif' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	
}

/*
 * Action update info OPCA
*/
if ($action == 'update_certif' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"])
	{
		$error=0;

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

	if ($action == 'edit')
	{

		/*
		 * Confirmation de la suppression
		*/
		if ($_POST["certif_remove_x"])
		{
			// Param url = id de la ligne stagiaire dans session - id session
			$ret=$form->form_confirm($_SERVER['PHP_SELF']."?stagerowid=".$_POST["stagerowid"].'&id='.$id,$langs->trans("AgfDeleteCertif"),$langs->trans("AgfConfirmDeleteStag"),"confirm_delete_certif",'','',1);
			if ($ret == 'html') print '<br>';
		}

		print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
		print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
		print '</div>';

		// Print session card
		$agf->printSessionInfo();

		print '&nbsp';

		print '<div class="tabBar">';
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
				if ($stagiaires->line[$i]->id == $_POST["modcertgid"] && $_POST["certif_remove_x"]) print '<tr bgcolor="#d5baa8">';
				else print '<tr>';
				print '<form name="obj_update_'.$i.'" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
				print '<input type="hidden" name="sessid" value="'.$stagiaires->line[$i]->sessid.'">'."\n";
				print '<input type="hidden" name="stagerowid" value="'.$stagiaires->line[$i]->stagerowid.'">'."\n";
				print '<input type="hidden" name="modcertgid" value="'.$stagiaires->line[$i]->id.'">'."\n";
				print '<td width="3%" align="center">'.($i+1).'</td>';

				if ($stagiaires->line[$i]->id == $_POST["modcertgid"] && ! $_POST["certif_remove_x"])
				{
					print '<td>';
					$trainee_info = '<a href="'.dol_buildpath('/agefodd/trainee/card.php',1).'?id='.$stagiaires->line[$i]->id.'">';
					$trainee_info .= img_object($langs->trans("ShowContact"),"contact").' ';
					$trainee_info .= strtoupper($stagiaires->line[$i]->nom).' '.ucfirst($stagiaires->line[$i]->prenom).'</a>';
					$contact_static= new Contact($db);
					$contact_static->civilite_id = $stagiaires->line[$i]->civilite;
					$trainee_info .= ' ('.$contact_static->getCivilityLabel().')';
					
					print'<label for="'.$htmlname.'" style="width:45%; display: inline-block;margin-left:5px;">'.$trainee_info.'</label>';

					$agf_certif = new Agefodd_stagiaire_certif($db);
					$agf_certif->fetch(0,0,0,$stagiaires->line[$i]->stagerowid);
					print '</td>';
					print '<td>';
					print '<table class="nobordernopadding">';
					print '<tr><td>'.$langs->trans('AgfCertifCode').':<input type="text" size="10" name="certif_code" value="'.$agf_certif->certif_code.'"></td></tr>';
					print '<tr><td>'.$langs->trans('AgfCertifLabel').':<input type="text" size="10" name="certif_label"  value="'.$agf_certif->certif_label.'"></td></tr>';
					print '<tr><td>'.$langs->trans('AgfCertifdebSt').':'.$form->select_date($agf_certif->certif_dt_start, 'dt_start','','',1,'obj_update_'.$i,1,1).'</td></tr>';
					print '<tr><td>'.$langs->trans('AgfCertifdebEnd').':'.$form->select_date($agf_certif->certif_dt_end, 'dt_end','','',1,'obj_update_'.$i,1,1).'</td></tr>';
					print '</table>';
					print '</td>';

					
					if ($user->rights->agefodd->modifier)
					{
						print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="stag_update" alt="'.$langs->trans("AgfModSave").'" ">';
					}
					print '</td>';
				}
				else
				{
					print '<td width="40%">';
					// info stagiaire
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

						print $trainee_info;
					}
					print '</td>';
					print '<td width="30%" style="border-left: 0px;">';
					// Affichage de l'organisme auquel est rattaché le stagiaire
					if ($stagiaires->line[$i]->socid)
					{
						print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$stagiaires->line[$i]->socid.'">';
						print img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($stagiaires->line[$i]->socname,20).'</a>';
					}
					else
					{
						print '&nbsp;';
					}
					print '</td><td>';


					if ($user->rights->agefodd->modifier)
					{
						print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" name="certif_edit" alt="'.$langs->trans("AgfModSave").'">';
					}
					print '&nbsp;';
					if ($user->rights->agefodd->creer)
					{
						print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" border="0" name="certif_remove" alt="'.$langs->trans("AgfModSave").'">';
					}
					print '</td>'."\n";
				}
				print '</form>'."\n";
				print '</tr>'."\n";
			}
		}

		

		//print '</table>';

		print '</table>';
		print '</div>';
	}
	else {
		// Affichage en mode "consultation"

		print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
		print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
		print '</div>';

		// Print session card
		$agf->printSessionInfo();

		print '&nbsp';

		/*
		 * Gestion des stagiaires
		*/

		print '&nbsp';
		print '<table class="border" width="100%">';

		$stagiaires = new Agsession($db);
		$stagiaires->fetch_stagiaire_per_session($agf->id);
		$nbstag = count($stagiaires->line);
		print '<tr><td  width="20%" valign="top" ';
		if ($nbstag < 1)
		{
			print '>'.$langs->trans("AgfParticipants").'</td>';
			print '<td style="text-decoration: blink;">'.$langs->trans("AgfNobody").'</td></tr>';
		}
		else
		{
			print ' rowspan='.($nbstag).'>'.$langs->trans("AgfParticipants");
			if ($nbstag > 1) print ' ('.$nbstag.')';
			print '</td>';

			for ($i=0; $i < $nbstag; $i++)
			{
				print '<td witdth="20px" align="center">'.($i+1).'</td>';
				print '<td width="400px"style="border-right: 0px;">';
				// Infos stagiaires
				if (strtolower($stagiaires->line[$i]->nom) == "undefined")	{
					print $langs->trans("AgfUndefinedStagiaire");
				}
				else {
					$trainee_info = '<a href="'.dol_buildpath('/agefodd/trainee/card.php',1).'?id='.$stagiaires->line[$i]->id.'">';
					$trainee_info .= img_object($langs->trans("ShowContact"),"contact").' ';
					$trainee_info .= strtoupper($stagiaires->line[$i]->nom).' '.ucfirst($stagiaires->line[$i]->prenom).'</a>';
					$contact_static= new Contact($db);
					$contact_static->civilite_id = $stagiaires->line[$i]->civilite;
					$trainee_info .= ' ('.$contact_static->getCivilityLabel().')';

					print $trainee_info;
				}
				print '</td>';
				print '<td style="border-left: 0px; border-right: 0px;">';
				// Infos organisme de rattachement
				if ($stagiaires->line[$i]->socid) {
					print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$stagiaires->line[$i]->socid.'">';
					print img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($stagiaires->line[$i]->socname,20).'</a>';
				}
				else {
					print '&nbsp;';
				}
				print '</td>';
				print '<td style="border-left: 0px;">';
				
				print '</td>';
				print "</tr>\n";
			}
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

llxFooter('');
?>
