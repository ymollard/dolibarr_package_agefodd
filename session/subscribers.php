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
 * 	\file		/agefodd/session/subscribers.php
 * 	\brief		Page présentant la liste des documents administratif disponibles dans Agefodd
 */

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/session/class/agefodd_session.class.php');
dol_include_once('/contact/class/contact.class.php');
dol_include_once('/agefodd/core/class/html.formagefodd.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$action=GETPOST('action','alpha');
$id=GETPOST('id','int');
$confirm=GETPOST('confirm','alpha');
$stag_update_x=GETPOST('stag_update_x','alpha');
$stag_add_x=GETPOST('stag_add_x','alpha');

$mesg = '';

if ($action=='edit' && $user->rights->agefodd->creer) {
	
	if($stag_update_x  > 0) {
		$agf = new Agefodd_session($db);
	
		$agf->id = GETPOST('stagerowid','int');
		$agf->sessid = GETPOST('sessid','int');
		$agf->stagiaire = GETPOST('stagiaire','int');
		$agf->type = GETPOST('stagiaire_type','int');
		$result = $agf->update_stag_in_session($user->id);
	
		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd:session:subscribers error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	}
	
	if($stag_add_x > 0) {
		
		$agf = new Agefodd_session($db);
	
		$agf->sessid = GETPOST('sessid','int');
		$agf->stagiaire = GETPOST('stagiaire','int');
		$agf->stagiaire_type = GETPOST('stagiaire_type','int');
		$result = $agf->create_stag_in_session($user->id);
	
		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd:session:subscribers error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	}
}


/*
 * Actions delete stagiaire
*/

if ($action == 'confirm_delete_stag' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	$stagerowid=GETPOST('stagerowid','int');

	$agf = new Agefodd_session($db);
	$result = $agf->remove_stagiaire($stagerowid);

	if ($result > 0)
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
		exit;
		
	}
	else
	{
		dol_syslog("agefodd:session:subscribers error=".$agf->error, LOG_ERR);
		$mesg = '<div class="error">'.$agf->error.'</div>';
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
	$agf = new Agefodd_session($db);
	$result = $agf->fetch($id);
	
	$head = session_prepare_head($agf);
		
	dol_fiche_head($head, 'subscribers', $langs->trans("AgfSessionDetail"), 0, 'group');	
	
	if ($action == 'edit')
	{
		
		/*
		 * Confirmation de la suppression
		 */
		if ($_POST["stag_remove_x"])
		{
			// Param url = id de la ligne stagiaire dans session - id session 
			$ret=$form->form_confirm($_SERVER['PHP_SELF']."?stagerowid=".$_POST["stagerowid"].'&id='.$id,$langs->trans("AgfDeleteStag"),$langs->trans("AgfConfirmDeleteStag"),"confirm_delete_stag",'','',1);
			if ($ret == 'html') print '<br>';
		}
		
		print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
		print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
		print '</div>';
		
		print '<table class="border" width="100%">';
	
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td>'.$form->showrefnav($agf,'id','',1,'rowid','id').'</td></tr>';
		
		print '<tr><td>'.$langs->trans("AgfFormIntitule").'</td>';
		print '<td><a href="'.dol_buildpath('/agefodd/training/card.php',1).'?id='.$agf->fk_formation_catalogue.'">'.$agf->formintitule.'</a></td></tr>';
		
		print '<tr><td>'.$langs->trans("AgfFormCodeInterne").'</td>';
		print '<td>'.$agf->formref.'</td></tr>';
			
		print '<tr><td>'.$langs->trans("AgfSessionCommercial").'</td>';
		print '<td><a href="'.dol_buildpath('/user/fiche.php',1).'?id='.$agf->commercialid.'">'.$agf->commercialname.'</a></td></tr>';
			
		print '<tr><td>'.$langs->trans("AgfDuree").'</td>';
		print '<td>'.$agf->duree.' heure(s)</td></tr>';
			
		print '<tr><td>'.$langs->trans("AgfDateDebut").'</td>';
		print '<td>'.dol_print_date($agf->dated,'daytext').'</td></tr>';
		
		print '<tr><td>'.$langs->trans("AgfDateFin").'</td>';
		print '<td>'.dol_print_date($agf->datef,'daytext').'</td></tr>';
			
		print '<tr><td>'.$langs->trans("AgfSessionContact").'</td>';
		print '<td><a href="'.dol_buildpath('/agefodd/contact/card.php',1).'?id='.$agf->contactid.'">'.$agf->contactname.'</a></td></tr>';
		
		print '<tr><td>'.$langs->trans("AgfLieu").'</td>';
		print '<td><a href="'.dol_buildpath('/agefodd/site/card.php',1).'?id='.$agf->placeid.'">'.$agf->placecode.'</a></td></tr>';
		
		print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td>';
		if (!empty($agf->notes)) $notes = nl2br($agf->notes);
		else $notes =  $langs->trans("AgfUndefinedNote");
		print '<td>'.stripslashes($notes).'</td></tr>';
		
		print '<tr><td>'.$langs->trans("AgfDateResTrainer").'</td>';
		if ($agf->is_date_res_trainer) {
			print '<td>'.dol_print_date($agf->date_res_trainer,'daytext').'</td></tr>';
		}
		else {
			print '<td>'.$langs->trans("AgfNoDefined").'</td></tr>';
		}
		
			
		print '<tr><td>'.$langs->trans("AgfDateResSite").'</td>';
		if ($agf->is_date_res_site) {
			print '<td>'.dol_print_date($agf->date_res_site,'daytext').'</td></tr>';
		}
		else {
			print '<td>'.$langs->trans("AgfNoDefined").'</td></tr>';
		}
			
			
		print '</table>';
			
		print '&nbsp';
		
	
		print '<div class="tabBar">';
		print '<table class="border" width="100%">';
	
		// Bloc d'affichage et de modification des stagiaires
		$stagiaires = new Agefodd_session($db);
		$stagiaires->fetch_stagiaire_per_session($agf->id);
		$nbstag = count($stagiaires->line);
		if ($nbstag > 0)
		{
			for ($i=0; $i < $nbstag; $i++)
			{
				if ($stagiaires->line[$i]->id == $_POST["modstagid"] && $_POST["stag_remove_x"]) print '<tr bgcolor="#d5baa8">';
				else print '<tr>';
				print '<form name="obj_update_'.$i.'" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
				print '<input type="hidden" name="sessid" value="'.$stagiaires->line[$i]->sessid.'">'."\n";
				print '<input type="hidden" name="stagerowid" value="'.$stagiaires->line[$i]->stagerowid.'">'."\n";
				print '<input type="hidden" name="modstagid" value="'.$stagiaires->line[$i]->id.'">'."\n";
			
				print '<td width="20px" align="center">'.($i+1).'</td>';
			
				if ($stagiaires->line[$i]->id == $_POST["modstagid"] && ! $_POST["stag_remove_x"])
				{
					print '<td colspan="2" width="500px">';
					print $formAgefodd->select_stagiaire($stagiaires->line[$i]->id, 'stagiaire', '(s.rowid NOT IN (SELECT fk_stagiaire FROM '.MAIN_DB_PREFIX.'agefodd_session_stagiaire WHERE fk_session_agefodd='.$id.')) OR (s.rowid='.$stagiaires->line[$i]->id.')');
					
					if (!empty($conf->global->AGF_USE_STAGIAIRE_TYPE))
					{
						print $formAgefodd->select_type_stagiaire($stagiaires->line[$i]->typeid,'stagiaire_type','',1);
					}
					if ($user->rights->agefodd->modifier)
					{
						print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="stag_update" alt="'.$langs->trans("AgfModSave").'" ">';
					}
					print '</td>';
					}
				else
				{
					print '<td width="300px" style="border-right: 0px;">';
					// info stagiaire
					if (strtolower($stagiaires->line[$i]->nom) == "undefined")
					{
						print $langs->trans("AgfUndefinedStagiaire");
					}
					else
					{
						print '<a href="'.dol_buildpath('/agefodd/trainee/card.php',1).'?id='.$stagiaires->line[$i]->id.'">';
						print img_object($langs->trans("ShowContact"),"contact").' ';
						print strtoupper($stagiaires->line[$i]->nom).' '.ucfirst($stagiaires->line[$i]->prenom).'</a>';
						
						$contact_static= new Contact($db);
						$contact_static->civilite_id = $stagiaires->line[$i]->civilite;
						print ' ('.$contact_static->getCivilityLabel().')';
	           		}
	   				print '</td>';
					print '<td width="150px" style="border-left: 0px;">';
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
					if (!empty($conf->global->AGF_USE_STAGIAIRE_TYPE))
					{
						print '</td><td width="150px" style="border-left: 0px;">'.stripslashes($stagiaires->line[$i]->type);
					}
					print '</td><td>';
					
					
					if ($user->rights->agefodd->modifier)
					{
						print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" name="stag_edit" alt="'.$langs->trans("AgfModSave").'">';
					}
					print '&nbsp;';
					if ($user->rights->agefodd->creer)
					{
						print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" border="0" name="stag_remove" alt="'.$langs->trans("AgfModSave").'">';
					}
					print '</td>'."\n";
				}
				print '</form>'."\n";
				print '</tr>'."\n";
			}
		}
					
		// Champs nouveau stagiaire
		if (isset($_POST["newstag"]))
		{
			print '<tr>';
			print '<form name="obj_update_'.($i + 1).'" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
			print '<input type="hidden" name="sessid" value="'.$agf->id.'">'."\n";
			print '<input type="hidden" name="stagerowid" value="'.$stagiaires->line[$i]->stagerowid.'">'."\n";
			print '<td width="20px" align="center">'.($i+1).'</td>';
			print '<td colspan="2" width="500px">';
			print $formAgefodd->select_stagiaire('','stagiaire', 's.rowid NOT IN (SELECT fk_stagiaire FROM '.MAIN_DB_PREFIX.'agefodd_session_stagiaire WHERE fk_session_agefodd='.$id.')',1);
			
			if (!empty($conf->global->AGF_USE_STAGIAIRE_TYPE))
			{
				print $formAgefodd->select_type_stagiaire($conf->global->AGF_DEFAULT_STAGIAIRE_TYPE,'stagiaire_type');
			}
			if ($user->rights->agefodd->modifier)
			{
				print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="stag_add" alt="'.$langs->trans("AgfModSave").'" ">';
			}
			print '</td>';
			print '</form>';
			print '</tr>'."\n";
		} 
	
		print '</table>';
		if (!isset($_POST["newstag"]))
		{
			print '</div>';
			//print '&nbsp';
			print '<table style="border:0;" width="100%">';
			print '<tr><td align="right">';
			print '<form name="newstag" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
			print '<input type="hidden" name="action" value="edit">'."\n";
			print '<input type="hidden" name="newstag" value="1">'."\n";
			print '<input type="submit" class="butAction" value="'.$langs->trans("AgfStagiaireAdd").'">';
			print '</td></tr>';
			print '</form>';
		}
	
		print '</table>';
		print '</div>';
	}
	else {
		// Affichage en mode "consultation"
		
		print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
		print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
		print '</div>';
		
		print '<table class="border" width="100%">';
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td>'.$form->showrefnav($agf,'id','',1,'rowid','id').'</td></tr>';
		
		print '<tr><td>'.$langs->trans("AgfFormIntitule").'</td>';
		print '<td><a href="'.dol_buildpath('/agefodd/training/card.php',1).'?id='.$agf->fk_formation_catalogue.'">'.$agf->formintitule.'</a></td></tr>';
		
		print '<tr><td>'.$langs->trans("AgfFormCodeInterne").'</td>';
		print '<td>'.$agf->formref.'</td></tr>';
			
		print '<tr><td>'.$langs->trans("AgfSessionCommercial").'</td>';
		print '<td><a href="'.dol_buildpath('/user/fiche.php',1).'?id='.$agf->commercialid.'">'.$agf->commercialname.'</a></td></tr>';
			
		print '<tr><td>'.$langs->trans("AgfDuree").'</td>';
		print '<td>'.$agf->duree.' heure(s)</td></tr>';
			
		print '<tr><td>'.$langs->trans("AgfDateDebut").'</td>';
		print '<td>'.dol_print_date($agf->dated,'daytext').'</td></tr>';
		
		print '<tr><td>'.$langs->trans("AgfDateFin").'</td>';
		print '<td>'.dol_print_date($agf->datef,'daytext').'</td></tr>';
			
		print '<tr><td>'.$langs->trans("AgfSessionContact").'</td>';
		print '<td><a href="'.dol_buildpath('/agefodd/contact/card.php',1).'?id='.$agf->contactid.'">'.$agf->contactname.'</a></td></tr>';
		
		print '<tr><td>'.$langs->trans("AgfLieu").'</td>';
		print '<td><a href="'.dol_buildpath('/agefodd/site/card.php',1).'?id='.$agf->placeid.'">'.$agf->placecode.'</a></td></tr>';
		
		print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td>';
		if (!empty($agf->notes)) $notes = nl2br($agf->notes);
		else $notes =  $langs->trans("AgfUndefinedNote");
		print '<td>'.stripslashes($notes).'</td></tr>';
		
		print '<tr><td>'.$langs->trans("AgfDateResTrainer").'</td>';
		if ($agf->is_date_res_trainer) {
			print '<td>'.dol_print_date($agf->date_res_trainer,'daytext').'</td></tr>';
		}
		else {
			print '<td>'.$langs->trans("AgfNoDefined").'</td></tr>';
		}
		
			
		print '<tr><td>'.$langs->trans("AgfDateResSite").'</td>';
		if ($agf->is_date_res_site) {
			print '<td>'.dol_print_date($agf->date_res_site,'daytext').'</td></tr>';
		}
		else {
			print '<td>'.$langs->trans("AgfNoDefined").'</td></tr>';
		}
			
			
		print '</table>';
			
		print '&nbsp';
		
		/*
		 * Gestion des stagiaires
		*/
		
		print '&nbsp';
		print '<table class="border" width="100%">';
			
		$stagiaires = new Agefodd_session($db);
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
				print '<td width="300px"style="border-right: 0px;">';
				// Infos stagiaires
				if (strtolower($stagiaires->line[$i]->nom) == "undefined")	{
				print $langs->trans("AgfUndefinedStagiaire");
				}
				else {
					print '<a href="'.dol_buildpath('/agefodd/trainee/card.php',1).'?id='.$stagiaires->line[$i]->id.'">';
					print img_object($langs->trans("ShowContact"),"contact").' ';
					print strtoupper($stagiaires->line[$i]->nom).' '.ucfirst($stagiaires->line[$i]->prenom).'</a>';
				
					$contact_static= new Contact($db);
					$contact_static->civilite_id = $stagiaires->line[$i]->civilite;
					print ' ('.$contact_static->getCivilityLabel().')';
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
				// Infos mode de financement
				if ($stagiaires->line[$i]->type) {
					print '<div class=adminaction>';
					print $langs->trans("AgfStagiaireModeFinancement");
					print '-<span>'.stripslashes($stagiaires->line[$i]->type).'</span></div>';
				}
				else {
					print '&nbsp;';
				}
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
