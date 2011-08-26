<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * 
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

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

require_once("./class/agefodd_session.class.php");
require_once("./class/agefodd_sessadm.class.php");
require_once("./class/agefodd_session_calendrier.class.php");
require_once("./class/agefodd_session_formateur.class.php");

require_once("../lib/agefodd.lib.php");


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();


$mesg = '';

$db->begin();


/*
 * Actions delete session
 */

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes" && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_session($db);
	$result = $agf->remove($_GET["id"]);
	
	if ($result > 0)
	{
		$db->commit();
		Header ( "Location: list.php");
		exit;
	}
	else
	{
		$db->rollback();
		dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
	}
}


/*
 * Actions delete formateur
 */

if ($_POST["action"] == 'confirm_delete_form' && $_POST["confirm"] == "yes" && $user->rights->agefodd->creer)
{
	$GET_array = explode('-',$_GET["id"]);

	$agf = new Agefodd_session_formateur($db);
	$result = $agf->remove($GET_array[0]);

	if ($result > 0)
	{
		$db->commit();
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$GET_array[1]);
		exit;
	}
	else
	{
		$db->rollback();
		dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
	}
}


/*
 * Actions delete stagiaire
 */

if ($_POST["action"] == 'confirm_delete_stag' && $_POST["confirm"] == "yes" && $user->rights->agefodd->creer)
{
	$GET_array = explode('-',$_GET["id"]);

	$agf = new Agefodd_session($db);
	$result = $agf->remove_stagiaire($GET_array[0]);

	if ($result > 0)
	{
		$db->commit();
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$GET_array[1]);
		exit;
	}
	else
	{
		$db->rollback();
		dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
	}
}

/*
 * Actions delete period
 */

if ($_POST["action"] == 'confirm_delete_period' && $_POST["confirm"] == "yes" && $user->rights->agefodd->creer)
{
	$GET_array = explode('-',$_GET["id"]);

	$agf = new Agefodd_sesscalendar($db);
	$result = $agf->remove($GET_array[0]);
	
	if ($result > 0)
	{
		$db->commit();
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$GET_array[1]);
		exit;
	}
	else
	{
		$db->rollback();
		dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
	}
}

/*
 * Actions archive/active
 */

if ($_POST["action"] == 'arch_confirm_delete' && $user->rights->agefodd->creer)
{
	if ($_POST["confirm"] == "yes")
	{
		//$GET_array = explode('-',$_GET["id"]);
		
		$agf = new Agefodd_session($db);
	
		$result = $agf->fetch($_GET["id"]);
		$agf->formateur = $agf->teacherid;
		$agf->fk_session_place = $agf->placeid;
		$agf->archive = $_GET["arch"];
		$result = $agf->update($user->id);
	
		if ($result > 0)
		{
			$db->commit();
			
			// Si la mise a jour s'est bien passée, on effectue le nettoyage des templates pdf
			foreach (glob($conf->agefodd->dir_output."/*_".$_GET["id"]."_*.pdf") as $filename) {
			    //echo "$filename effacé <br>";
			    if(is_file($filename)) unlink("$filename");
			}
			
			Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$_GET["id"]);
			exit;
		}
		else
		{
			$db->rollback();
			dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
		}
	
	}
	else
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$_GET["id"]);
		exit;
	}
}


/*
 * Action update (fiche session)
 */
if ($_POST["action"] == 'update' && $user->rights->agefodd->creer && ! $_POST["stag_update_x"] && ! $_POST["period_update_x"])
{
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd_session($db);

		$result = $agf->fetch($_POST["id"]);

		$agf->formateur = $_POST["formateur"];
		$agf->dated = $_POST["dadyear"].'-'.$_POST["dadmonth"].'-'.$_POST["dadday"];
		$agf->datef = $_POST["dafyear"].'-'.$_POST["dafmonth"].'-'.$_POST["dafday"];
		$agf->fk_session_place = $_POST["place"];
		$agf->notes = $_POST["notes"];
		$result = $agf->update($user->id);

		if ($result > 0)
		{
			$db->commit();
			// Si OK et maj des formateurs
			$error = 0;
			if ($_POST["nbf"])
			{
				$agf = new Agefodd_session_formateur($db);

				for ($i = 0; $i < $_POST["nbf"]; $i++)
				{
					$agf->formateur = $_POST["formateur"];
					$result = $agf->update($user->id);
				}
				if ($result > 0) $db->commit();
				else
				{
					$error = 1;
					$db->rollback();
					dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
				}
			}
			Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$_POST["id"]);
			exit;
		}
		else
		{
			$db->rollback();
			dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
		}

	}
	else
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$_POST["id"]);
		exit;
	}
}


/*
 * Action update
 * - changement ou ajout stagiaire dans fiche session
 * - changement ou ajout periode dans fiche session
 * - changement ou ajout formateur dans fiche session
 */
if ($_POST["action"] == 'edit' && $user->rights->agefodd->creer)
{
	if($_POST["stag_update_x"])
	{
		$agf = new Agefodd_session($db);
		
		$agf->id = $_POST["stagerowid"];
		$agf->sessid = $_POST["sessid"];
		$agf->stagiaire = $_POST["stagiaire"];
		$agf->type = $_POST["stagiaire_type"];
		$result = $agf->update_stag_in_session($user->id);
	
		if ($result > 0)
		{
			$db->commit();
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$_GET["id"]);
			exit;
		}
		else
		{
			$db->rollback();
			dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
		}
	}
	
	if($_POST["stag_add_x"])
	{
		$agf = new Agefodd_session($db);
		
		$agf->sessid = $_POST["sessid"];
		$agf->stagiaire = $_POST["stagiaire"];
		$agf->datec = $db->idate(mktime());
		$result = $agf->create_stag_in_session($user->id);
	
		if ($result > 0)
		{
			$db->commit();
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$_GET["id"]);
			exit;
		}
		else
		{
			$db->rollback();
			dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
		}
	}

	if($_POST["period_update_x"])
	{
		$agf = new Agefodd_sesscalendar($db);
		$result = $agf->fetch($_POST["modperiod"]);

		if(!empty($_POST["modperiod"])) $agf->id = $_POST["modperiod"];
		if(!empty($_POST["dateyear"])) $agf->date = $_POST["dateyear"].':'.$_POST["datemonth"].':'.$_POST["dateday"];
		if(!empty($_POST["heured"])) $agf->heured = $_POST["heured"].':00';
		if(!empty($_POST["heuref"])) $agf->heuref = $_POST["heuref"].':00';
		$result = $agf->update($user->id);
	
		if ($result > 0)
		{
			$db->commit();
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$_GET["id"]);
			exit;
		}
		else
		{
			$db->rollback();
			dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
		}
	}
	
	if($_POST["period_add_x"])
	{
		$agf = new Agefodd_sesscalendar($db);
				
		$agf->sessid = $_POST["sessid"];
		$agf->date = $_POST["dateyear"].':'.$_POST["datemonth"].':'.$_POST["dateday"];
		$agf->heured = $_POST["heured"].':00';
		$agf->heuref = $_POST["heuref"].':00';
		$agf->datec = $db->idate(mktime());
		$result = $agf->create($user->id);
	
		if ($result > 0)
		{
			$db->commit();
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$_GET["id"]);
			exit;
		}
		else
		{
			$db->rollback();
			dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
		}
	}

	if($_POST["form_update_x"])
	{
		$agf = new Agefodd_session_formateur($db);
		
		$agf->opsid = $_POST["opsid"];
		$agf->formid = $_POST["formid"];
		$result = $agf->update($user->id);
	
		if ($result > 0)
		{
			$db->commit();
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$_GET["id"]);
			exit;
		}
		else
		{
			$db->rollback();
			dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
		}
	}
	
	if($_POST["form_add_x"])
	{
		$agf = new Agefodd_session_formateur($db);
		
		$agf->sessid = $_POST["sessid"];
		$agf->formid = $_POST["formid"];
		$agf->datec = $db->idate(mktime());
		$result = $agf->create($user->id);
	
		if ($result > 0)
		{
			$db->commit();
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$_GET["id"]);
			exit;
		}
		else
		{
			$db->rollback();
			dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
		}
	}

}

/*
 * Action create (nouvelle session de formation)
 */

if ($_POST["action"] == 'add' && $user->rights->agefodd->creer)
{
	
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd_session($db);

		$agf->formid = $_POST["formation"];
		$agf->place = $_POST["place"];
		//$agf->formateur = $_POST["formateur"];
		$agf->dated = $_POST["dadyear"].'-'.$_POST["dadmonth"].'-'.$_POST["dadday"];
		$agf->datef = $_POST["dafyear"].'-'.$_POST["dafmonth"].'-'.$_POST["dafday"];
		$agf->notes = $_POST["notes"];
		$agf->datec= $db->idate(mktime());
		$result = $agf->create($user->id);

		if ($result > 0)
		{
			$db->commit();
			
			// Si la création de la session s'est bien passée, 
			// on crée automatiquement toutes les tâches administratives associées...
			$admlevel = new Agefodd_sessadm($db);
			$result2 = $admlevel->get_admlevel_table();
				
			if ($result2)
			{
				for ($i=0; $i < $result2; $i++)
				{
					$actions = new Agefodd_sessadm($db);

					// Calcul de la date d'alerte
					$sec_before_alert = ($admlevel->line[$i]->alerte * 86400);
					//print '$sec_before_alert = '.$sec_before_alert;
					$today_mktime = mktime(0, 0, 0, date("m"), date("d"), date("y"));
						
					($admlevel->line[$i]->alerte > 0) ? $date_ref = $agf->datef : $date_ref = $agf->dated;
					
					if ($date_ref > '0000-00-00 00:00:00') $alertday_mktime = (mysql2timestamp($date_ref.' 00:00:00') + $sec_before_alert);
					else $alertday_mktime = $today_mktime;
					//print '$alertday_mktime = '.$alertday_mktime;	
					$alertday = date("Y-m-d H:i:s", $alertday_mktime);
					//print '$alertday = '.$alertday;
					//exit;
					
					$actions->fk_agefodd_session_admlevel = $admlevel->line[$i]->rowid;
					$actions->fk_agefodd_session = $agf->id;
					$actions->delais_alerte = $admlevel->line[$i]->alerte;
					$actions->intitule = $admlevel->line[$i]->intitule;
					$actions->indice = $admlevel->line[$i]->indice;
					$actions->top_level = $admlevel->line[$i]->top_level;
					$actions->datea = $alertday;
					$result3 = $actions->create($user->id);

					if ($result3 > 0) {
						$db->commit();
					}
					else
					{
						$db->rollback();
						dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
					}
				
				}
			}
			
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$agf->id.'&nbf='.$_POST["nb_formateur"]);
			exit;
		}
		else
		{
			$db->rollback();
			dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
		}

	}
	else
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$_POST["id"]);
		exit;
	}
}



/*
 * View
 */

llxHeader();

$html = new Form($db);

if (preg_match('/\-/', $_GET['id']))
{
	$GET_array = explode('-', $_GET['id']);
	$id = $GET_array[1];
}
else $id = $id = $_GET['id'];


/*
 * Action create
 */
if ($_GET["action"] == 'create' && $user->rights->agefodd->creer)
{
	$h=0;
	
	$head[$h][0] = "";
	$head[$h][1] = $langs->trans("Card");
	$hselected = $h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("AgfSessionDetail"), 0, 'user');

	print '<form name="add" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	print '<table class="border" width="100%">';

	print '<tr><td>'.$langs->trans("AgfFormIntitule").'</td>';
	print '<td>'.ebi_select_formation("", 'formation').'</a></td></tr>';

	//print '<tr><td>'.$langs->trans("AgfFormateurNb").'</td><td>';
	//print ebi_select_number('nb_formateur',1);
	//print ebi_select_formateur("", 'formateur');
	//print ' '.img_picto($langs->trans("AgfFormateurSelectHelp"),"help", 'align="absmiddle"');
	//print '</td></tr>';
	
	print '<tr><td>'.$langs->trans("AgfDateDebut").'</td><td>';
	$html->select_date("", 'dad','','','','add');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("AgfDateFin").'</td><td>';
	$html->select_date("", 'daf','','','','add');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("AgfLieu").'</td>';
	print '<td>';
	print ebi_select_site_forma("",'place');
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
			if ($mesg) print $mesg."<br>";
			
			
			// Affichage en mode "édition"
			if ($_GET["action"] == 'edit')
			{
				$h=0;
				
				$head[$h][0] = $_SERVER['PHP_SELF']."?id=$agf->id";
				$head[$h][1] = $langs->trans("Card");
				$hselected = $h;
				$h++;

				$head[$h][0] = dol_buildpath("/agefodd/session/info.php",1)."?id=$agf->id";
				$head[$h][1] = $langs->trans("Info");
				$h++;

				dol_fiche_head($head, $hselected, $langs->trans("AgfSessionDetail"), 0, 'user');

				print '<form name="update" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="update">';
				print '<input type="hidden" name="id" value="'.$id.'">';
				print '<input type="hidden" name="action" value="update">';
				

				print '<table class="border" width="100%">';
				print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
				print '<td>'.$agf->id.'</td></tr>';
				//print '<td>'.$html->showrefnav('session', $agf->id).'</td></tr>';

				print '<tr><td>'.$langs->trans("AgfFormIntitule").'</td>';
				//print '<td>'.ebi_select_formation($agf->id, 'formation').'</a></td></tr>';
				print '<td>'.$agf->formintitule.'</a></td></tr>';


				print '<tr><td>'.$langs->trans("AgfFormCodeInterne").'</td>';
				//print '<td>'.ebi_select_formation($agf->id, 'formation', 'code').'</a></td></tr>';
				print '<td>'.$agf->formref.'</a></td></tr>';
				
				/*
				print '<tr><td>'.$langs->trans("AgfFormateur").'</td><td>';
				//$html->select_users($agf->rowid);
				print ebi_select_formateur($agf->teacherid, 'formateur');
				print ' '.img_picto($langs->trans("AgfFormateurSelectHelp"),"help", 'align="absmiddle"');
				//print $html->textwithpicto("","test");
				print '</td>;
				*/
				print '</tr>';
				
				print '<tr><td>'.$langs->trans("AgfDateDebut").'</td><td>';
				$html->select_date($agf->dated, 'dad','','','','update');
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("AgfDateFin").'</td><td>';
				$html->select_date($agf->datef, 'daf','','','','update');
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("AgfLieu").'</td>';
				print '<td>';
				print ebi_select_site_forma($agf->placeid,'place');
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
					$ret=$html->form_confirm($_SERVER['PHP_SELF']."?id=".$_POST["opsid"].'-'.$id,$langs->trans("AgfDeleteForm"),$langs->trans("AgfConfirmDeleteForm"),"confirm_delete_form");
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
						//print '<input type="hidden" name="formid" value="'.$formateurs->line[$i]->formid.'">'."\n";
						print '<input type="hidden" name="opsid" value="'.$formateurs->line[$i]->opsid.'">'."\n";
					
						print '<td width="20px" align="center">'.($i+1).'</td>';
					
						if ($formateurs->line[$i]->opsid == $_POST["opsid"] && ! $_POST["form_remove_x"])
						{
							print '<td width="300px" style="border-right: 0px">';
							print ebi_select_formateur($formateurs->line[$i]->formid, "formid");
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
					//print '<input type="hidden" name="sessid" value="'.$stagiaires->line[$i]->sessid.'">'."\n";
					print '<input type="hidden" name="sessid" value="'.$agf->id.'">'."\n";
					//print '<input type="hidden" name="formid" value="'.$formateurs->line[$i]->formid.'">'."\n";
					print '<td width="20px" align="center">'.($i+1).'</td>';
					print '<td>';
					print ebi_select_formateur($formateurs->line[$i]->formid, "formid");
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
					$ret=$html->form_confirm($_SERVER['PHP_SELF'].'?id='.$_POST["modperiod"].'-'.$id,$langs->trans("AgfDeletePeriod"),$langs->trans("AgfConfirmDeletePeriod"),"confirm_delete_period");
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
							$html->select_date($calendrier->line[$i]->date, 'date','','','','date');
							print '</td>';
							print '<td width="150px" nowrap>'.$langs->trans("AgfPeriodTimeB").' ';
							print ebi_select_time("heured", $calendrier->line[$i]->heured);
							print ' - '.$langs->trans("AgfPeriodTimeE").' ';
							print ebi_select_time("heuref",$calendrier->line[$i]->heuref);
							print '</td>';
					
							if ($user->rights->agefodd->modifier)
							{
								print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="period_update" alt="'.$langs->trans("AgfModSave").'" ">';
							}
						}
						else
						{
							$arrayJour = explode('-', $calendrier->line[$i]->date);
							$mktime = mktime(0, 0, 0, $arrayJour[1], $arrayJour[2], $arrayJour[0]);
							setlocale(LC_TIME, 'fr_FR', 'fr_FR.utf8', 'fr');
							$jour = strftime("%A", $mktime);
							print '<td width="20%">'.$jour.' '.dol_print_date($calendrier->line[$i]->date).'</td>';
							print '<td  width="150px">';
							$heured = ebi_time_array($calendrier->line[$i]->heured);
							print $heured['h'].':'.$heured['m'] ;
							$heuref = ebi_time_array($calendrier->line[$i]->heuref);
							print ' - '.$heuref['h'].':'.$heuref['m'] ;
							print '</td><td colspan=2 width="auto">' ;
						
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
						// pour mémoire: mktime(heures, minutes, secondes, mois, jour, année);
						$heured = ebi_time_array($calendrier->line[$i]->heured);
						$heuref = ebi_time_array($calendrier->line[$i]->heuref);
						$arrayJour = explode('-', $calendrier->line[$i]->date);
						$tms_heured = mktime($heured['h'],$heured['m'], 0, $arrayJour[1], $arrayJour[2], $arrayJour[0]);
						$tms_heuref = mktime($heuref['h'],$heuref['m'], 0, $arrayJour[1], $arrayJour[2], $arrayJour[0]);
						$duree += ($tms_heuref - $tms_heured);

						print '</form>'."\n";
						print '</tr>'."\n";
					}
					if (($agf->duree * 3600) != $duree)
					{
						print '<tr><td colspan=5 style="text-decoration: blink;" align="center">';
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
					//print '<td width="20px" align="center">'.($i+1).'</td>';
					print '<td>'.$langs->trans("AgfPeriodDate").' ';
					$html->select_date($agf->dated, 'date','','','','date');
					print '</td>';
					print '<td width="150px">'.$langs->trans("AgfPeriodTimeB").' ';
					print ebi_select_time("heured");
					print '</td>';
					print '<td width="150px">'.$langs->trans("AgfPeriodTimeE").' ';
					print ebi_select_time("heuref");
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
					$ret=$html->form_confirm($_SERVER['PHP_SELF']."?id=".$_POST["stagerowid"].'-'.$id,$langs->trans("AgfDeleteStag"),$langs->trans("AgfConfirmDeleteStag"),"confirm_delete_stag");
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
							print ebi_select_stagiaire($stagiaires->line[$i]->id);
							
							if (USE_STAGIAIRE_TYPE == 'OK')
							{
								print '<br /> '.$langs->trans("AgfStagiaireModeFinancement").': ';
								print ebi_select_type_stagiaire($stagiaires->line[$i]->typeid);
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
								print '<a href="'.DOL_URL_ROOT.'/agefodd/u_fiche.php?id='.$stagiaires->line[$i]->id.'">';
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
					//print '<input type="hidden" name="sessid" value="'.$stagiaires->line[$i]->sessid.'">'."\n";
					print '<input type="hidden" name="sessid" value="'.$agf->id.'">'."\n";
					print '<input type="hidden" name="stagerowid" value="'.$stagiaires->line[$i]->stagerowid.'">'."\n";
					print '<td width="20px" align="center">'.($i+1).'</td>';
					print '<td colspan=2>';
						print ebi_select_stagiaire();
						if (USE_STAGIAIRE_TYPE == 'OK')
						{
							print ebi_select_type_stagiaire(DEFAULT_STAGIAIRE_TYPE);
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
			else
			{
				// Affichage en mode "consultation"
				
				$head = session_prepare_head($agf);

				dol_fiche_head($head, 'card', $langs->trans("AgfSessionDetail"), 0, 'user');

				/*
				 * Confirmation de la suppression
				 */
				if ($_GET["action"] == 'delete')
				{
					$ret=$html->form_confirm($_SERVER['PHP_SELF']."?id=".$id,$langs->trans("AgfDeleteOps"),$langs->trans("AgfConfirmDeleteOps"),"confirm_delete");
					if ($ret == 'html') print '<br>';
				}

				/*
				* Confirmation de l'archivage/activation suppression
				*/
				if (isset($_GET["arch"]))
				{
					$ret=$html->form_confirm($_SERVER['PHP_SELF']."?arch=".$_GET["arch"]."&id=".$id,$langs->trans("AgfFormationArchiveChange"),$langs->trans("AgfConfirmArchiveChange"),"arch_confirm_delete");
					if ($ret == 'html') print '<br>';
				}

				print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
				print ebi_level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_adm_level_number(), $langs->trans("AgfAdmLevel"));
				print '</div>';



				print '<table class="border" width="100%">';

				/*print '<tr class="liste_titre"><td colspan=5  align="center">';
				print ebi_level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_adm_level_number(), $langs->trans("AgfAdmLevel"));
				print '</td></tr>';*/

				print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
				print '<td>'.$agf->id.'</td></tr>';

				//print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
				//print '<td>'.$html->showrefnav($agf,$id).'</td></tr>';

				print '<tr><td>'.$langs->trans("AgfFormIntitule").'</td>';
				print '<td><a href="'.dol_buildpath('/agefodd/training/card.php',1).'?id='.$agf->formid.'">'.$agf->formintitule.'</a></td></tr>';

				print '<tr><td>'.$langs->trans("AgfFormCodeInterne").'</td>';
				print '<td>'.$agf->formref.'</td></tr>';

				//print '<tr><td>'.$langs->trans("AgfFormateur").'</td>';
				//print '<td><a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$agf->teacherid.'">'.$agf->teachername.'</a>';
				
				print '<tr><td>'.$langs->trans("AgfDateDebut").'</td>';
				print '<td>'.dol_print_date($agf->dated).'</td></tr>';

				print '<tr><td>'.$langs->trans("AgfDateFin").'</td>';
				print '<td>'.dol_print_date($agf->datef).'</td></tr>';

				print '<tr><td>'.$langs->trans("AgfLieu").'</td>';
				print '<td><a href="s_place_fiche.php?id='.$agf->placeid.'">'.$agf->placecode.'</a></td></tr>';

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
				//print '</div>';


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
							$arrayJour = explode('-', $calendrier->line[$i]->date);
							$mktime = mktime(0, 0, 0, $arrayJour[1], $arrayJour[2], $arrayJour[0]);
							setlocale(LC_TIME, 'fr_FR', 'fr_FR.utf8', 'fr');
							$jour = strftime("%A", $mktime);
							print $jour.' '.dol_print_date($calendrier->line[$i]->date).'</td><td>';
						}
						else print ', ';
						$heured = ebi_time_array($calendrier->line[$i]->heured);
						print $heured['h'].':'.$heured['m'] ;
						$heuref = ebi_time_array($calendrier->line[$i]->heuref);
						print ' - '.$heuref['h'].':'.$heuref['m'] ;
						if ($i == $blocNumber -1 ) print '</td></tr>';
						
						$old_date = $calendrier->line[$i]->date;
						
						// On calcule la duree totale du calendrier
						// pour mémoire: mktime(heures, minutes, secondes, mois, jour, année);
						$tms_heured = mktime($heured['h'],$heured['m'], 0, $arrayJour[1], $arrayJour[2], $arrayJour[0]);
						$tms_heuref = mktime($heuref['h'],$heuref['m'], 0, $arrayJour[1], $arrayJour[2], $arrayJour[0]);
						$duree += ($tms_heuref - $tms_heured);
					}
					if (($agf->duree * 3600) != $duree)
					{
						print '<tr><td>&nbsp;</td><td colspan=2 style="text-decoration: blink;">';
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
			dol_print_error($db);
		}
	}
}


/*
 * Barre d'actions
 *
 */

print '<div class="tabsAction">';

if ($_GET["action"] != 'create' && $_GET["action"] != 'edit')
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

$db->close();

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
