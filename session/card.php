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
 *  \file       	/agefodd/session/card.php
 *  \brief      	Page fiche session de formation
 *  \version		$Id$
 */

error_reporting(E_ALL);
 ini_set('display_errors', true);
ini_set('html_errors', false);

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/session/class/agefodd_session.class.php');
dol_include_once('/agefodd/session/class/agefodd_sessadm.class.php');
dol_include_once('/agefodd/admin/class/agefodd_session_admlevel.class.php');
dol_include_once('/agefodd/core/class/html.formagefodd.class.php');
dol_include_once('/agefodd/session/class/agefodd_session_calendrier.class.php');
dol_include_once('/agefodd/session/class/agefodd_session_formateur.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/core/lib/date.lib.php');

//TODO : Use stagiaire type

// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$mesg = '';

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('id','int');
$arch=GETPOST('arch','int');

/*
 * Actions delete session
 */

if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_session($db);
	$result = $agf->remove($id);
	
	if ($result > 0)
	{
		Header ( "Location: list.php");
		exit;
	}
	else
	{
		dol_syslog("agefodd:session:card error=".$error, LOG_ERR);
		$mesg = '<div class="error">'.$langs->trans("AgfDeleteErr").':'.$agf->error.'</div>';
	}
}


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
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
	else
	{
		dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
		$mesg = '<div class="error">'.$agf->error.'</div>';
	}
}

/*
 * Actions delete period
 */

if ($action == 'confirm_delete_period' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	$modperiod=GETPOST('modperiod','int');
	
	$agf = new Agefodd_sesscalendar($db);
	$result = $agf->remove($modperiod);
	
	if ($result > 0)
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
	else
	{
		dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
		$mesg = '<div class="error">'.$agf->error.'</div>';
	}
}

/*
 * Actions archive/active
 */

if ($action == 'arch_confirm_delete' && $user->rights->agefodd->creer)
{
	if ($confirm == "yes")
	{		
		$agf = new Agefodd_session($db);
	
		$result = $agf->fetch($id);
		$agf->formateur = $agf->teacherid;
		$agf->fk_session_place = $agf->placeid;
		$agf->archive = $_GET["arch"];
		$result = $agf->update($user->id);
	
		if ($result > 0)
		{
			// Si la mise a jour s'est bien passée, on effectue le nettoyage des templates pdf
			foreach (glob($conf->agefodd->dir_output."/*_".$id."_*.pdf") as $filename) {
			    //echo "$filename effacé <br>";
			    if(is_file($filename)) unlink("$filename");
			}
			
			Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	
	}
	else
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
}


/*
 * Action update (fiche session)
 */
if ($action == 'update' && $user->rights->agefodd->creer && ! $_POST["stag_update_x"] && ! $_POST["period_update_x"])
{
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd_session($db);

		$result = $agf->fetch($id);

		$agf->formateur = GETPOST('formateur','int');
		$agf->dated = dol_mktime(0,0,0,GETPOST('dadmonth','int'),GETPOST('dadday','int'),GETPOST('dadyear','int'));
		$agf->datef = dol_mktime(0,0,0,GETPOST('dafmonth','int'),GETPOST('dafday','int'),GETPOST('dafyear','int'));
		$agf->fk_session_place = GETPOST('place','int');
		$agf->notes = GETPOST('notes','alpha');
		$result = $agf->update($user->id);

		if ($result > 0)
		{
			// Si OK et maj des formateurs
			$error = 0;
			$nbf=GETPOST('nbf','int');
			if (!(empty($nbf)))
			{
				$agf = new Agefodd_session_formateur($db);

				for ($i = 0; $i < $nbf; $i++)
				{
					$agf->formateur = GETPOST('formateur','int');
					$result = $agf->update($user->id);
				}
				if ($result > 0) $db->commit();
				else
				{
					$error = 1;
					dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
					$mesg = '<div class="error">'.$agf->error.'</div>';
				}
			}
			Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}

	}
	else
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
}


/*
 * Action update
 * - changement ou ajout stagiaire dans fiche session
 * - changement ou ajout periode dans fiche session
 * - changement ou ajout formateur dans fiche session
 */
if ($action == 'edit' && $user->rights->agefodd->creer)
{
	if($_POST["stag_update_x"])
	{
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
			dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	}
	
	if($_POST["stag_add_x"])
	{
		$agf = new Agefodd_session($db);
		
		$agf->sessid = GETPOST('sessid','int');
		$agf->stagiaire = GETPOST('stagiaire','int');
		$result = $agf->create_stag_in_session($user->id);
	
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

	if($_POST["period_update_x"])
	{
		
		$modperiod=GETPOST('modperiod','int');
		$dateyear = dol_mktime(0,0,0,GETPOST('datemonth','int'),GETPOST('dateday','int'),GETPOST('dateyear','int'));
		$heured = dol_mktime(GETPOST('datedhour','int'),GETPOST('datedmin','int'),0,GETPOST('datedmonth','int'),GETPOST('datedday','int'),GETPOST('datedyear','int'));
		$heuref = dol_mktime(GETPOST('datefhour','int'),GETPOST('datefmin','int'),0,GETPOST('datefmonth','int'),GETPOST('datefday','int'),GETPOST('datefyear','int'));

		$agf = new Agefodd_sesscalendar($db);
		$result = $agf->fetch($modperiod);
		
		if(!empty($modperiod)) $agf->id = $modperiod;
		if(!empty($dateyear)) $agf->date = $dateyear;
		if(!empty($heured)) $agf->heured = $heured;
		if(!empty($heuref)) $agf->heuref =  $heuref;
		$result = $agf->update($user->id);
	
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
	
	if($_POST["period_add_x"])
	{
		$agf = new Agefodd_sesscalendar($db);
		
		$agf->sessid = GETPOST('sessid','int');
		$agf->date = dol_mktime(0,0,0,GETPOST('datemonth','int'),GETPOST('dateday','int'),GETPOST('dateyear','int'));
		$agf->heured = dol_mktime(GETPOST('datedhour','int'),GETPOST('datedmin','int'),0,GETPOST('datedmonth','int'),GETPOST('datedday','int'),GETPOST('datedyear','int'));
		$agf->heuref = dol_mktime(GETPOST('datefhour','int'),GETPOST('datefmin','int'),0,GETPOST('datefmonth','int'),GETPOST('datefday','int'),GETPOST('datefyear','int'));
		$result = $agf->create($user->id);
	
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

	if($_POST["form_update_x"])
	{
		$agf = new Agefodd_session_formateur($db);
		
		$agf->opsid = GETPOST('opsid','int');
		$agf->formid = GETPOST('formid','int');
		$result = $agf->update($user->id);
	
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
	
	if($_POST["form_add_x"])
	{
		$agf = new Agefodd_session_formateur($db);
		
		$agf->sessid = GETPOST('sessid','int');
		$agf->formid = GETPOST('formid','int');
		$result = $agf->create($user->id);
	
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

}

/*
 * Action create (nouvelle session de formation)
 */

if ($action == 'add_confirm' && $user->rights->agefodd->creer)
{
	$error=0;
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd_session($db);

		$agf->formid = GETPOST('formation','int');
		$agf->place = GETPOST('place','int');
		$agf->dated = dol_mktime(0,0,0,GETPOST('dadmonth','int'),GETPOST('dadday','int'),GETPOST('dadyear','int'));
		$agf->datef = dol_mktime(0,0,0,GETPOST('dafmonth','int'),GETPOST('dafday','int'),GETPOST('dafyear','int'));
		$agf->notes = GETPOST('notes','alpha');
		$result = $agf->create($user->id);
		
		if ($result > 0)
		{
			// Si la création de la session s'est bien passée, 
			// on crée automatiquement toutes les tâches administratives associées...
			$admlevel = new Agefodd_session_admlevel($db);
			$result2 = $admlevel->fetch_all();
				
			if ($result2 > 0)
			{
				foreach ($admlevel->line as $line)
				{
					$actions = new Agefodd_sessadm($db);

					$actions->datea = dol_time_plus_duree($agf->dated,$line->alerte,'d');
					$actions->dated = dol_time_plus_duree($actions->datea,-7,'d');
					
					if ($actions->datea > $agf->datef)
					{
						$actions->datef = dol_time_plus_duree($actions->datea,7,'d');
					}
					else
					{
						$actions->datef = $agf->datef;
					}

					$actions->fk_agefodd_session_admlevel = $line->rowid;
					$actions->fk_agefodd_session = $agf->id;
					$actions->delais_alerte = $line->alerte;
					$actions->intitule = $line->intitule;
					$actions->indice = $line->indice;
					$actions->archive = 0;
					$actions->level_rank = $line->level_rank;
					$actions->fk_parent_level = $line->fk_parent_level;  //Treatement to calculate the new parent level is after
					$result3 = $actions->create($user->id);

					if ($result3 < 0) {
						dol_syslog("agefodd:session:card error=".$actions->error, LOG_ERR);
						$mesg .= $actions->error;
						$error++;
					}
				}
				
				//Caculate the new parent level
				$action_static = new Agefodd_sessadm($db);
				$result4 = $action_static->setParentActionId($user->id,$agf->id);
				if ($result4 < 0) {
					dol_syslog("agefodd:session:card error=".$action_static->error, LOG_ERR);
					$mesg .= $action_static->error;
					$error++;
				}
			}
			else
			{
				dol_syslog("agefodd:session:card error=".$admlevel->error, LOG_ERR);
				$mesg .= $admlevel->error;
				$error++;
			}
		}
		else
		{
			dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
			$mesg .= $agf->error;
			$error++;
		}
		
		if ($error==0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$agf->id.'&nbf='.$_POST["nb_formateur"]);
			exit;
		}
		else
		{
			$mesg='<div class="error">'.$mesg.'</div>';
		}
	}
	else
	{
		Header ( "Location: list.php");
		exit;
	}
}



/*
 * View
 */

llxHeader();

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

dol_htmloutput_mesg($mesg);

/*
 * Action create
 */
if ($action == 'create' && $user->rights->agefodd->creer)
{
	print_fiche_titre($langs->trans("AgfMenuSessNew"));

	print '<form name="add" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add_confirm">';

	print '<table class="border" width="100%">';

	print '<tr><td><span class="fieldrequired">'.$langs->trans("AgfFormIntitule").'</span></td>';
	print '<td>'.$formAgefodd->select_formation("", 'formation','intitule',1).'</a></td></tr>';

	//TODO : check nb formateur
	//print '<tr><td>'.$langs->trans("AgfFormateurNb").'</td><td>';
	//print ebi_select_number('nb_formateur',1);
	//print ebi_select_formateur("", 'formateur');
	//print ' '.img_picto($langs->trans("AgfFormateurSelectHelp"),"help", 'align="absmiddle"');
	//print '</td></tr>';
	
	print '<tr><td><span class="fieldrequired">'.$langs->trans("AgfDateDebut").'</span></td><td>';
	$form->select_date("", 'dad','','','','add');
	print '</td></tr>';

	print '<tr><td><span class="fieldrequired">'.$langs->trans("AgfDateFin").'</span></td><td>';
	$form->select_date("", 'daf','','','','add');
	print '</td></tr>';

	print '<tr><td><span class="fieldrequired">'.$langs->trans("AgfLieu").'</span></td>';
	print '<td>';
	print $formAgefodd->select_site_forma("",'place',1);
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td>';
	print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';

	print '</table>';
	print '</div>';

	print '<table style=noborder align="right">';
	print '<tr><td align="center" colspan=2>';
	print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'"> &nbsp; ';
	print '<input type="submit" name="cancel" class="butActionDelete" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';

	print '</table>';
	print '</form>';

}
else
{
	// Affichage de la fiche "session"
	if ($id)
	{
		$agf = new Agefodd_session($db);
		$result = $agf->fetch($id);

		if ($result)
		{
			if (!(empty($agf->id)))
			{
				$head = session_prepare_head($agf);
				
				dol_fiche_head($head, 'card', $langs->trans("AgfSessionDetail"), 0, 'calendarday');
				
				// Affichage en mode "édition"
				if ($action == 'edit')
				{
					print '<form name="update" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="action" value="update">';
					print '<input type="hidden" name="id" value="'.$id.'">';
					print '<input type="hidden" name="action" value="update">';
					
	
					print '<table class="border" width="100%">';
					print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
					print '<td>'.$agf->id.'</td></tr>';
	
					print '<tr><td>'.$langs->trans("AgfFormIntitule").'</td>';
					print '<td>'.$formAgefodd->select_formation($agf->formid, 'formation');
					print '</td></tr>';
	
	
					print '<tr><td>'.$langs->trans("AgfFormCodeInterne").'</td>';
					print '<td>'.$agf->formref.'</a></td></tr>';
					
					print '</tr>';
					
					print '<tr><td>'.$langs->trans("AgfDateDebut").'</td><td>';
					$form->select_date($agf->dated, 'dad','','','','update');
					print '</td></tr>';
	
					print '<tr><td>'.$langs->trans("AgfDateFin").'</td><td>';
					$form->select_date($agf->datef, 'daf','','','','update');
					print '</td></tr>';
	
					print '<tr><td>'.$langs->trans("AgfLieu").'</td>';
					print '<td>';
					print $formAgefodd->select_site_forma($agf->placeid,'place');
					print '</td></tr>';
	
					print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td>';
					if (!empty($agf->note)) $notes = nl2br($agf->note);
					else $notes =  $langs->trans("AgfUndefinedNote");
					print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;">'.stripslashes($agf->notes).'</textarea></td></tr>';
	
					print '</table>';
					print '</div>';
	
					print '<table style=noborder align="right">';
					print '<tr><td align="center" colspan=2>';
					print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'"> &nbsp; ';
					print '<input type="submit" name="cancel" class="butActionDelete" value="'.$langs->trans("Cancel").'">';
					print '</td></tr>';
	
					print '</table>';
					print '</form>';
	
	
	
					/*
					 * Gestion formateur
					 */
	
					print_barre_liste($langs->trans("AgfFormateur"),"", "","","","",'',0);
	
					/*
					 * Confirmation de la suppression
					 */
					if ($_POST["form_remove_x"])
					{
						// Param url = id de la ligne formateur dans session - id session 
						$ret=$form->form_confirm($_SERVER['PHP_SELF']."?opsid=".$_POST["opsid"].'&id='.$id,$langs->trans("AgfDeleteForm"),$langs->trans("AgfConfirmDeleteForm"),"confirm_delete_form",'','',1);
						if ($ret == 'html') print '<br>';
					}
	
					print '<div class="tabBar">';
					print '<table class="border" width="100%">';
	
					// Bloc d'affichage et de modification des formateurs
					$formateurs = new Agefodd_session_formateur($db);
					$nbform = $formateurs->fetch_formateur_per_session($agf->id);
					if ($nbform > 0)
					{
						for ($i=0; $i < $nbform; $i++)
						{
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
								if (strtolower($formateurs->line[$i]->name) == "undefined")
								{
									print $langs->trans("AgfUndefinedStagiaire");
								}
								else
								{
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
					if (isset($_POST["newform"]))
					{
						print '<tr>';
						print '<form name="form_update_'.($i + 1).'" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
						print '<input type="hidden" name="action" value="edit">'."\n";
						print '<input type="hidden" name="sessid" value="'.$agf->id.'">'."\n";
						print '<td width="20px" align="center">'.($i+1).'</td>';
						print '<td>';
						print $formAgefodd->select_formateur($formateurs->line[$i]->formid, "formid", 's.rowid NOT IN (SELECT fk_agefodd_formateur FROM '.MAIN_DB_PREFIX.'agefodd_session_formateur WHERE fk_session='.$id.')',1);
						if ($user->rights->agefodd->modifier)
						{
							print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="form_add" alt="'.$langs->trans("AgfModSave").'">';
						}
						print '</td>';
						print '</form>';
						print '</tr>'."\n";
					} 
	
					print '</table>';
					if (!isset($_POST["newform"]))
					{
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
					
			
	
					/*
					 * Gestion Calendrier
					 */
					
					print_barre_liste($langs->trans("AgfCalendrier"),"", "","","","",'',0);
					
					/*
					 * Confirmation de la suppression
					 */
					if ($_POST["period_remove_x"])
					{
						// Param url = id de la periode à supprimer - id session 
						$ret=$form->form_confirm($_SERVER['PHP_SELF'].'?modperiod='.$_POST["modperiod"].'&id='.$id,$langs->trans("AgfDeletePeriod"),$langs->trans("AgfConfirmDeletePeriod"),"confirm_delete_period",'','',1);
						if ($ret == 'html') print '<br>';
					}
					print '<div class="tabBar">';
					print '<table class="border" width="100%">';
									
					$calendrier = new Agefodd_sesscalendar($db);
					$calendrier->fetch_all($agf->id);
					$blocNumber = count($calendrier->line);
					if ($blocNumber < 1 && !isset($_POST["newperiod"]))
					{
						print '<tr>';
						print '<td  colpsan=1 style="color:red; text-decoration: blink;">'.$langs->trans("AgfNoCalendar").'</td></tr>';
					}
					else
					{
						$old_date = 0;
						$duree = 0;
						for ($i = 0; $i < $blocNumber; $i++)
						{
							print '<tr>'."\n";;
							print '<form name="obj_update_'.$i.'" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
							print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
							print '<input type="hidden" name="action" value="edit">'."\n";
							print '<input type="hidden" name="sessid" value="'.$calendrier->line[$i]->sessid.'">'."\n";
							print '<input type="hidden" name="modperiod" value="'.$calendrier->line[$i]->id.'">'."\n";
							
							if ($calendrier->line[$i]->id == $_POST["modperiod"] && ! $_POST["period_remove_x"])
							{
								print '<td  width="20%">'.$langs->trans("AgfPeriodDate").' ';
								$form->select_date($calendrier->line[$i]->date, 'date','','','','obj_update_'.$i);
								print '</td>';
								print '<td width="150px" nowrap>'.$langs->trans("AgfPeriodTimeB").' ';
								$form->select_date($calendrier->line[$i]->heured, 'dated',1,1,0,'obj_update_'.$i,1);
								print ' - '.$langs->trans("AgfPeriodTimeE").' ';
								$form->select_date($calendrier->line[$i]->heuref, 'datef',1,1,0,'obj_update_'.$i,1);
								print '</td>';
						
								if ($user->rights->agefodd->modifier)
								{
									print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="period_update" alt="'.$langs->trans("AgfModSave").'" ">';
								}
							}
							else
							{
								print '<td width="20%">'.dol_print_date($calendrier->line[$i]->date,'daytext').'</td>';
								print '<td  width="150px">'.dol_print_date($calendrier->line[$i]->heured,'hour').' - '.dol_print_date($calendrier->line[$i]->heuref,'hour');
								if ($user->rights->agefodd->modifier)
								{
									print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" name="period_edit" alt="'.$langs->trans("AgfModSave").'">';
								}
								print '&nbsp;';
								if ($user->rights->agefodd->creer)
								{
									print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" border="0" name="period_remove" alt="'.$langs->trans("AgfModSave").'">';
								}
							}
							print '</td>' ;
							
							// On calcule la duree totale du calendrier
							$duree += ($calendrier->line[$i]->heuref - $calendrier->line[$i]->heured);
	
							print '</form>'."\n";
							print '</tr>'."\n";
						}
						if (($agf->duree * 3600) != $duree)
						{
							print '<tr><td colspan=5 align="center"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/recent.png" border="0" align="absmiddle" hspace="6px" >';
							if (($agf->duree * 3600) < $duree) print $langs->trans("AgfCalendarSup");
							if (($agf->duree * 3600) > $duree) print $langs->trans("AgfCalendarInf");
							$rsec = sprintf("%02d",$duree % 60); 
							$min = floor($duree/60) ;
							$rmin = sprintf("%02d", $min %60) ;
							$hour = floor($min/60);
							print ' ('.$langs->trans("AgfCalendarDureeProgrammee").': '.$hour.':'.$rmin.', ';
							print $langs->trans("AgfCalendarDureeThéorique").' : '.($agf->duree).':00).</td></tr>';
						}
					}
					
					// Champs nouvelle periode
	
					if (!isset($_POST["newperiod"]))
					{
						print "</table></div>";
						//print '&nbsp';
						print '<table style="border:0;" width="100%">';
						print '<tr><td align="right">';
						print '<form name="newperiod" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
						print '<input type="hidden" name="action" value="edit">'."\n";
						print '<input type="hidden" name="newperiod" value="1">'."\n";
						print '<input type="submit" class="butAction" value="'.$langs->trans("AgfPeriodAdd").'">';
						print '</td></tr>';
						print '</form>';
					}
					else
					{
						print '<tr>';
						print '<form name="obj_update_'.($i + 1).'" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
						print '<input type="hidden" name="action" value="edit">'."\n";
						print '<input type="hidden" name="sessid" value="'.$agf->id.'">'."\n";
						print '<input type="hidden" name="periodid" value="'.$stagiaires->line[$i]->stagerowid.'">'."\n";
						print '<td  width="300px">'.$langs->trans("AgfPeriodDate").' ';
						$form->select_date($agf->dated, 'date','','','','newperiod');
						print '</td>';
						print '<td width="400px">'.$langs->trans("AgfPeriodTimeB").' ';
						$form->select_date($agf->dated, 'dated',1,1,0,'newperiod',1);
						print '</td>';
						print '<td width="400px">'.$langs->trans("AgfPeriodTimeE").' ';
						$form->select_date($agf->dated, 'datef',1,1,0,'newperiod',1);
						print '</td>';
						if ($user->rights->agefodd->modifier)
						{
							print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="period_add" alt="'.$langs->trans("AgfModSave").'" ">';
						}
						print '</td>';
						print '</form>';
						print '</tr>'."\n";
					} 
	
					print '</table>';
					print '</div>';
	
	
					/*
					 * Gestion stagiaire
					 */
	
					print_barre_liste($langs->trans("AgfParticipants"),"", "","","","",'',0);
	
					/*
					 * Confirmation de la suppression
					 */
					if ($_POST["stag_remove_x"])
					{
						// Param url = id de la ligne stagiaire dans session - id session 
						$ret=$form->form_confirm($_SERVER['PHP_SELF']."?stagerowid=".$_POST["stagerowid"].'&id='.$id,$langs->trans("AgfDeleteStag"),$langs->trans("AgfConfirmDeleteStag"),"confirm_delete_stag",'','',1);
						if ($ret == 'html') print '<br>';
					}
	
	
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
							print '<input type="hidden" name="action" value="edit">'."\n";
							print '<input type="hidden" name="sessid" value="'.$stagiaires->line[$i]->sessid.'">'."\n";
							print '<input type="hidden" name="stagerowid" value="'.$stagiaires->line[$i]->stagerowid.'">'."\n";
							print '<input type="hidden" name="modstagid" value="'.$stagiaires->line[$i]->id.'">'."\n";
						
							print '<td width="20px" align="center">'.($i+1).'</td>';
						
							if ($stagiaires->line[$i]->id == $_POST["modstagid"] && ! $_POST["stag_remove_x"])
							{
								print '<td colspan=2>';
								print $formAgefodd->select_stagiaire($stagiaires->line[$i]->id, 'stagiaire', 's.rowid NOT IN (SELECT fk_stagiaire FROM '.MAIN_DB_PREFIX.'agefodd_session_stagiaire WHERE fk_session='.$id.')');
								
								if (USE_STAGIAIRE_TYPE == 'OK')
								{   // TODO : type stagiaire
									print '<br /> '.$langs->trans("AgfStagiaireModeFinancement").': ';
									//print ebi_select_type_stagiaire($stagiaires->line[$i]->typeid);
								}
								if ($user->rights->agefodd->modifier)
								{
									print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="stag_update" alt="'.$langs->trans("AgfModSave").'" ">';
								}
								print '</td>';
							}
							else
							{
								print '<td width="300px"style="border-right: 0px;">';
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
									print ' ('.ucfirst(strtolower($stagiaires->line[$i]->civilite)).')';
	                                        		}
	                                        		print '</td>';
								print '<td width="200px" style="border-left: 0px;">';
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
						print '<input type="hidden" name="action" value="edit">'."\n";
						print '<input type="hidden" name="sessid" value="'.$agf->id.'">'."\n";
						print '<input type="hidden" name="stagerowid" value="'.$stagiaires->line[$i]->stagerowid.'">'."\n";
						print '<td width="20px" align="center">'.($i+1).'</td>';
						print '<td colspan=2>';
						print $formAgefodd->select_stagiaire('','stagiaire', 's.rowid NOT IN (SELECT fk_stagiaire FROM '.MAIN_DB_PREFIX.'agefodd_session_stagiaire WHERE fk_session='.$id.')',1);
						//TODO : Stagiaire type
						/*if (USE_STAGIAIRE_TYPE == 'OK')
						{
							print $formAgefodd->select_type_stagiaire(DEFAULT_STAGIAIRE_TYPE);
						}*/
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
				else
				{
					// Affichage en mode "consultation"
					/*
					 * Confirmation de la suppression
					 */
					if ($action == 'delete')
					{
						$ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$id,$langs->trans("AgfDeleteOps"),$langs->trans("AgfConfirmDeleteOps"),"confirm_delete",'','',1);
						if ($ret == 'html') print '<br>';
					}
	
					/*
					* Confirmation de l'archivage/activation suppression
					*/
					if (isset($_GET["arch"]))
					{
						$ret=$form->form_confirm($_SERVER['PHP_SELF']."?arch=".$_GET["arch"]."&id=".$id,$langs->trans("AgfFormationArchiveChange"),$langs->trans("AgfConfirmArchiveChange"),"arch_confirm_delete",'','',1);
						if ($ret == 'html') print '<br>';
					}
	
					print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
					print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_adm_level_number(), $langs->trans("AgfAdmLevel"));
					print '</div>';
	
	
	
					print '<table class="border" width="100%">';
	
					print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
					print '<td>'.$form->showrefnav($agf,'id','',1,'rowid','id').'</td></tr>';
	
					print '<tr><td>'.$langs->trans("AgfFormIntitule").'</td>';
					print '<td><a href="'.dol_buildpath('/agefodd/training/card.php',1).'?id='.$agf->formid.'">'.$agf->formintitule.'</a></td></tr>';
	
					print '<tr><td>'.$langs->trans("AgfFormCodeInterne").'</td>';
					print '<td>'.$agf->formref.'</td></tr>';
	
					//print '<tr><td>'.$langs->trans("AgfFormateur").'</td>';
					//print '<td><a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$agf->teacherid.'">'.$agf->teachername.'</a>';
					
					print '<tr><td>'.$langs->trans("AgfDateDebut").'</td>';
					print '<td>'.dol_print_date($agf->dated,'daytext').'</td></tr>';
	
					print '<tr><td>'.$langs->trans("AgfDateFin").'</td>';
					print '<td>'.dol_print_date($agf->datef,'daytext').'</td></tr>';
	
					print '<tr><td>'.$langs->trans("AgfLieu").'</td>';
					print '<td><a href="'.dol_buildpath('/agefodd/site/card.php',1).'?id='.$agf->placeid.'">'.$agf->placecode.'</a></td></tr>';
	
					print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td>';
					if (!empty($agf->notes)) $notes = nl2br($agf->notes);
					else $notes =  $langs->trans("AgfUndefinedNote");
					print '<td>'.stripslashes($notes).'</td></tr>';
	
					print '</table>';
					
					print '&nbsp';
	
	
					/*
					 * Gestion des formateurs
					 */
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
					    for ($i=0; $i < $nbform; $i++)
					    {
						// Infos formateurs
						print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$formateurs->line[$i]->socpeopleid.'">';
						//print img_object($langs->trans("ShowContact"),"contact").' ';
						print strtoupper($formateurs->line[$i]->name).' '.ucfirst($formateurs->line[$i]->firstname).'</a>';
						if ($i < ($nbform - 1)) print ',&nbsp;&nbsp;';
	                                        
					    }
	                                    print '</td>';
					    print "</tr>\n";
					}
					print "</table>";
	
					/*
					 * Gestion du calendrier
					 */
					
					print '&nbsp';
					print '<table class="border" width="100%">';
					print '<tr>';
					
					$calendrier = new Agefodd_sesscalendar($db);
					$calendrier->fetch_all($agf->id);
					$blocNumber = count($calendrier->line);
					if ($blocNumber < 1)
					{
						print '<td  width="20%" valign="top" >'.$langs->trans("AgfCalendrier").'</td>';
						print '<td style="color:red; text-decoration: blink;">'.$langs->trans("AgfNoCalendar").'</td></tr>';
					}
					else
					{
						print '<td  width="20%" valign="top" style="border-bottom:0px;">'.$langs->trans("AgfCalendrier").'</td>';
						$old_date = 0;
						$duree = 0;
						for ($i = 0; $i < $blocNumber; $i++)
						{
							if ($calendrier->line[$i]->date != $old_date)
							{
								if ($i > 0 )print '</tr><tr><td width="150px" style="border:0px;">&nbsp;</td>';
								print '<td width="150px">';
								print dol_print_date($calendrier->line[$i]->date,'daytext').'</td><td>';
							}
							else print ', ';
							print dol_print_date($calendrier->line[$i]->heured,'hour').' - '.dol_print_date($calendrier->line[$i]->heuref,'hour');
							if ($i == $blocNumber -1 ) print '</td></tr>';
							
							$old_date = $calendrier->line[$i]->date;
							
							// On calcule la duree totale du calendrier
							// pour mémoire: mktime(heures, minutes, secondes, mois, jour, année);
							$duree += ($calendrier->line[$i]->heuref - $calendrier->line[$i]->heured);
						}
						if (($agf->duree * 3600) != $duree)
						{
							print '<tr><td>&nbsp;</td><td colspan=2><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/recent.png" border="0" align="absmiddle" hspace="6px" >';
							if (($agf->duree * 3600) < $duree) print $langs->trans("AgfCalendarSup");
							if (($agf->duree * 3600) > $duree) print $langs->trans("AgfCalendarInf");
							$rsec = sprintf("%02d",$duree % 60); 
							$min = floor($duree/60) ;
							$rmin = sprintf("%02d", $min %60) ;
							$hour = floor($min/60);
							print ' ('.$langs->trans("AgfCalendarDureeProgrammee").': '.$hour.':'.$rmin.', ';
							print $langs->trans("AgfCalendarDureeThéorique").' : '.($agf->duree).':00).</td></tr>';
						}
					}
					print '</tr>';
					print "</table>";
	
	
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
						if (strtolower($stagiaires->line[$i]->nom) == "undefined")
						{
							print $langs->trans("AgfUndefinedStagiaire");
						}
						else
						{
							print '<a href="'.DOL_URL_ROOT.'/agefodd/u_fiche.php?id='.$stagiaires->line[$i]->id.'">';
							print img_object($langs->trans("ShowContact"),"contact").' ';
							print strtoupper($stagiaires->line[$i]->nom).' '.ucfirst($stagiaires->line[$i]->prenom).'</a>';
							print ' ('.ucfirst(strtolower($stagiaires->line[$i]->civilite)).')';
	                                        }
	                                        print '</td>';
						print '<td style="border-left: 0px; border-right: 0px;">';
						// Infos organisme de rattachement
						if ($stagiaires->line[$i]->socid)
						{
							print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$stagiaires->line[$i]->socid.'">';
							print img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($stagiaires->line[$i]->socname,20).'</a>';
						}
						else
						{
							print '&nbsp;';
						}
						print '</td>';
						print '<td style="border-left: 0px;">';
						// Infos mode de financement
						if ($stagiaires->line[$i]->type)
						{
							print '<div class=adminaction><a href="# ">';
							print $langs->trans("AgfStagiaireModeFinancement");
							print '<span>'.stripslashes($stagiaires->line[$i]->type).'</span></a></div>';
						}
						else
						{
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
			else
			{
				print $langs->trans('AgfNoSession');
			}
		}
		else
		{
			
			dol_print_error($db);
		}
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
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
	}
	if ($agf->archive == 0)
	{
		$button = $langs->trans('AgfArchiver');
		$arch = 1;
	}
	else
	{
		$button = $langs->trans('AgfActiver');
		$arch = 0;
	}
	if ($user->rights->agefodd->modifier)
	{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?arch='.$arch.'&id='.$id.'">'.$button.'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$button.'</a>';
	}
}

print '</div>';

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>

