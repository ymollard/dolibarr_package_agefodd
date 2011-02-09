<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009	Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
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
 *  \file       	$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/s_fiche.php $
 *  \brief      	Page de gestion des tâches administratives (session de formation)
 *  \version		$Id: s_fiche.php 54 2010-03-30 18:58:28Z ebullier $
 */
require("./pre.inc.php");
require_once("./agefodd_sessadm.class.php");
require_once("./lib/lib.php");

$langs->load("@agefodd");

// Security check
if (!$user->rights->agefodd->lire) accessforbidden();


$mesg = '';

$db->begin();

/*
 * Actions delete
 */
if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes" && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_sessadm($db);
	$result = $agf->remove($_GET["actid"]);
	
	if ($result > 0)
	{
		$db->commit();
		Header ( "Location: s_adm.php?id=".$_GET["id"]);
		exit;
	}
	else
	{
		$db->rollback();
		dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
	}

}


/*
 * Action update
 */
if ($_POST["action"] == 'update' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"] && ! $_POST["delete"])
	{
		$agf = new Agefodd_sessadm($db);

		//$result = $agf->fetch($_POST["id"]);

		$agf->datea = $_POST["dateayear"].'-'.$_POST["dateamonth"].'-'.$_POST["dateaday"];
		$agf->dated = $_POST["dadyear"].'-'.$_POST["dadmonth"].'-'.$_POST["dadday"];
		$agf->datef = $_POST["dafyear"].'-'.$_POST["dafmonth"].'-'.$_POST["dafday"];
		$agf->notes = $_POST["notes"];
		$agf->id = $_POST["sessadmid"];
		($agf->datef > '0000-00-00 00:00:00') ? $agf->archive = 1 : $agf->archive = 0;
		$result = $agf->update($user->id);

		if ($result > 0)
		{
			$db->commit();
			Header ( "Location: s_adm.php?id=".$_POST["id"]);
			exit;
		}
		else
		{
			$db->rollback();
			dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
		}
	}
	elseif ($_POST["delete"])
	{
		Header ( "Location: s_adm.php?id=".$_GET["id"]."&action=edit&delete=1&actid=".$_POST["sessadmid"]);
		exit;
	}
	else
	{
		Header ( "Location: s_adm.php?id=".$_POST["id"]);
		exit;
	}
}

/*
 * Action create
 */

if ($_POST["action"] == 'create' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"] )
	{
		$agf = new Agefodd_sessadm($db);

		$agf->fk_agefodd_session_admlevel = $_POST["admlevel"];
		$agf->fk_agefodd_session = $_GET["id"];
		$agf->delais_alerte = $_POST["alerte"];
		$agf->intitule = $_POST["intitule"];
		$agf->indice = $_POST["indice"];
		$agf->top_level = $_POST["toplevel"];
		$agf->datea = $_POST["dateayear"].'-'.$_POST["dateamonth"].'-'.$_POST["dateaday"];
		$agf->dated = $_POST["dadyear"].'-'.$_POST["dadmonth"].'-'.$_POST["dadday"];
		$agf->notes = $_POST["notes"];
		$result = $agf->create($user->id);

		if ($result > 0)
		{
			$db->commit();
			Header ( 'Location: s_adm.php?id='.$_GET["id"].'&action=edit&actid='.$agf->id);
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
		Header ( "Location: s_adm.php?id=".$_GET["id"]);
		exit;
	}
}


/*
 * View
 */

llxHeader();

$html = new Form($db);

$id = $_GET['id'];


/*
 * Action create
 */
if ($_GET["action"] == 'create' && $user->rights->agefodd->creer)
{
	$h=0;
	
	$head[$h][0] = DOL_URL_ROOT."/agefodd/s_fiche.php?id=".$id;
	$head[$h][1] = $langs->trans("Card");
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/agefodd/s_adm.php?id=".$id;
	$head[$h][1] = $langs->trans("AgfAdmSuivi");
	$hselected = $h;
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/agefodd/s_doc_fiche.php?id=$id";
	$head[$h][1] = $langs->trans("AgfLinkedDocuments");
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("AgfSessionDetail"), 0, 'user');
	
	$admlevel = new Agefodd_sessadm($db);
	$result = $admlevel->fetch_adminlevel_infos($_GET["admlevel"]);

	$sessinfos = new Agefodd_sessadm($db);
	$result2 = $sessinfos->get_session_dated($_GET["id"]);

	print '<form name="update" action="s_adm.php?id='.$id.'" method="post">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
	print '<input type="hidden" name="action" value="create">'."\n";
	print '<input type="hidden" name="admlevel" value="'.$admlevel->id.'">'."\n";
	print '<input type="hidden" name="intitule" value="'.$admlevel->intitule.'">'."\n";
	print '<input type="hidden" name="indice" value="'.$admlevel->indice.'">'."\n";
	print '<input type="hidden" name="toplevel" value="'.$admlevel->top_level.'">'."\n";
	print '<input type="hidden" name="delais" value="'.$admlevel->alerte.'">'."\n";

	// Calcul de la date d'alerte
	$sec_before_alert = ($admlevel->alerte * 86400);
	$alertday_mktime = (mysql2timestamp($sessinfos->dated) - $sec_before_alert);

	print '<table class="border" width="100%">';

	//print "<tr>";
	//print '<td width="20%">'.$langs->trans("Ref").'</td><td>'.$agf->id.'</td></tr>';
	
	print '<tr><td width="300px">'.$langs->trans("AgfSessAdmIntitule").'</td>';
	print '<td>'.$admlevel->intitule.'</a></td></tr>';

	print '<tr><td>'.$langs->trans("AgfDebutSession").'</td>';
	print '<td>'.dol_print_date($sessinfos->dated).'</td></tr>';
	
	print '<tr><td>'.$langs->trans("AgfSessAdmDateLimit").'</td><td>';
	$html->select_date(date("Y-m-d H:i:s", $alertday_mktime),'datea','','','','create');
	print '</td></tr>';
	
	print '<tr><td>'.$langs->trans("AgfSessDateDebut").' ('.$langs->trans("AgfPar").' '.$user->login.')</td><td>';
	$html->select_date("", 'dad','','','','create');
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
		$sessinfo = new Agefodd_sessadm($db);
		$result0 = $sessinfo->get_session_dated($id);

		if ($mesg) print $mesg."<br>";
		
		
		// Affichage en mode "édition"
		if ($_GET["action"] == 'edit')
		{
			$agf = new Agefodd_sessadm($db);
			$result = $agf->fetch_admin_action_rens_from_id($_GET["actid"]);

			$h=0;
			
			$head[$h][0] = DOL_URL_ROOT."/agefodd/s_fiche.php?id=".$id;
			$head[$h][1] = $langs->trans("Card");
			$h++;

			$head[$h][0] = DOL_URL_ROOT."/agefodd/s_adm.php?id=".$id;
			$head[$h][1] = $langs->trans("AgfAdmSuivi");
			$hselected = $h;
			$h++;

			$head[$h][0] = DOL_URL_ROOT."/agefodd/s_doc_fiche.php?id=$id";
			$head[$h][1] = $langs->trans("AgfLinkedDocuments");
			$h++;

			dol_fiche_head($head, $hselected, $langs->trans("AgfSessionDetail"), 0, 'user');
			
			/*
			* Confirmation de la suppression
			*/
			if ($_GET["delete"] == '1')
			{
				$ret = $html->form_confirm("s_adm.php?id=".$id."&actid=".$_GET["actid"], $langs->trans("AgfDeleteOps"),$langs->trans("AgfConfirmDeleteOps"),"confirm_delete");
				if ($ret == 'html') print '<br>';
			}
			print '<form name="update" action="s_adm.php?id='.$agf->sessid.'" method="post">'."\n";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
			print '<input type="hidden" name="action" value="update">'."\n";
			print '<input type="hidden" name="id" value="'.$id.'">'."\n";
			print '<input type="hidden" name="sessadmid" value="'.$agf->id.'">'."\n";

			print '<table class="border" width="100%">';

			print "<tr>";
			print '<td td width="300px">'.$langs->trans("Ref").'</td><td>'.$agf->id.'</td></tr>';
			
			print '<tr><td>'.$langs->trans("AgfSessAdmIntitule").'</td>';
			print '<td>'.$agf->intitule.'</a></td></tr>';

			/*//Date d'alerte
			$sec_before_alert = ($agf->delais_alerte * 86400);
			$today_mktime = mktime(0, 0, 0, date("m"), date("d"), date("y"));
			if ($sessinfo->dated > '0000-00-00 00:00:00') $alertday_mktime = (mysql2timestamp($sessinfo->dated) - $sec_before_alert);
			else $alertday_mktime = $today_mktime;
*/
			//print '<tr><td>'.$langs->trans("AgfDebutSession").'</td>';
			//print '<td>'.dol_print_date($agf->dated).'</td></tr>';
			
			print '<script type="text/javascript">'."\n";
			print 'function DivStatus( div_){'."\n";
			print '	var Obj = document.getElementById( div_);'."\n";
			print '	if( Obj.style.display=="none"){'."\n";
			print '		Obj.style.display ="block";'."\n";
			print '	}'."\n";
			print '	else{'."\n";
			print '		Obj.style.display="none";'."\n";
			print '	}'."\n";
			print '}'."\n";
			print '</script>'."\n";

			print '<tr><td valign="top">'.$langs->trans("AgfSessAdmDateLimit").'</td><td>';
			print dol_print_date($agf->datea);
			print '<a href="javascript:DivStatus(\'datea\');" title="afficher detail""> ('.$langs->trans("AgfDefinir").')</a>';
			print '<span id="datea" style="display:none;">';
			$html->select_date($agf->datea,'datea','','','','update');
			print '</span>';
			print '</td></tr>';
			
			print '<tr><td valign="top">'.$langs->trans("AgfSessDateDebut").' ('.$langs->trans("AgfPar").' '.$user->login.')</td><td>';
			$html->select_date($agf->dated, 'dad','','','','update');
			print '</td></tr>';
			print '<tr><td valign="top">'.$langs->trans("AgfSessDateFin").' ('.$langs->trans("AgfPar").' '.$user->login.')</td><td>';
			
			if ($agf->datef == '0000-00-00 00:00:00')
			{
			    print $langs->trans("AgfNoDefined");
			    print '<a href="javascript:DivStatus(\'datef\');" title="afficher detail""> ('.$langs->trans("AgfDefinir").')</a>';
			    print '<span id="datef" style="display:none;">';
			    $html->select_date($agf->datef, 'daf','','','','update');
			    print '</span>';
			
			}
			else
			{
			    $html->select_date($datef->datef, 'daf','','','','update');
			}
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
			print '<input type="submit" name="cancel" class="butActionDelete" value="'.$langs->trans("Cancel").'"> &nbsp; ';
			print '<input type="submit" name="delete" class="butActionDelete" value="'.$langs->trans("Delete").'">';
			print '</td></tr>';
			
			print '</table>';
			print '</form>';
			
		}
		else
		{
			// Affichage en mode "consultation"
			$agf = new Agefodd_sessadm($db);
			$result = $agf->get_admlevel_table();
		
			$h=0;
			
			$head[$h][0] = DOL_URL_ROOT."/agefodd/s_fiche.php?id=".$id;
			$head[$h][1] = $langs->trans("Card");
			$h++;

			$head[$h][0] = DOL_URL_ROOT."/agefodd/s_adm.php?id=".$id;
			$head[$h][1] = $langs->trans("AgfAdmSuivi");
			$hselected = $h;
			$h++;

			$head[$h][0] = DOL_URL_ROOT."/agefodd/s_doc_fiche.php?id=$id";
			$head[$h][1] = $langs->trans("AgfLinkedDocuments");
			$h++;

			dol_fiche_head($head, $hselected, $langs->trans("AgfSessionDetail"), 0, 'user');


			print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
			print ebi_level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_adm_level_number(), $langs->trans("AgfAdmLevel"));
			print '</div>';

			print '<table width="100%" style="border:0px">';

			//$line = new Agefodd_sessadm($db);
			//$result2 = $line->get_admlevel_table();
			//$line = new Agefodd_sessadm($db);
			//$result2 = $agf->get_admlevel_table();
				
			//if ($result2)
			if ($result)
			{
				for ($i=0; $i < $result; $i++)
				{
				
					$infos = new Agefodd_sessadm($db);
					$result3 = $infos->fetch_admin_action_rens($agf->line[$i]->rowid, $id);
				
					$bgcolor = '#d5baa8';
					
					if ($result3)
					{
						// Calcul de la date d'alerte
						$today_mktime = mktime(0, 0, 0, date("m"), date("d"), date("y"));
						if ($infos->datea) 
						{
							$alertday = $infos->datea;
							$alertday_mktime = (mysql2timestamp($infos->datea));
						}
						else
						{
							$sec_before_alert = ($agf->line[$i]->alerte * 86400);
							
							if ($sessinfo->dated > '0000-00-00 00:00:00') $alertday_mktime = (mysql2timestamp($sessinfo->dated) + $sec_before_alert);
							else $alertday_mktime = $today_mktime;
							
							$alertday = date("Y-m-d H:i:s", $alertday_mktime);

							// Si delais alerte = 0 (debut de la formation par exemple)
							if ($agf->line[$i]->alerte == 0) $alertday = $sessinfo->dated;
						}
						
						if (!empty($alertday_mktime))
						{
							if (($alertday_mktime - (8 * 86400)) < $today_mktime) $bgcolor = '#ffe27d';
							if (($alertday_mktime - (3 * 86400)) < $today_mktime) $bgcolor = 'orange';
							if ($alertday_mktime < $today_mktime) $bgcolor = 'red';
							
						}
						//if (empty($sessinfo->dated)) $bgcolor = 'yellow';
						if ($infos->dated > '0000-00-00 00:00:00' && $infos->datef > '0000-00-00 00:00:00')
						{
							$bgcolor = 'green';
							$verif = 'OK';
						}
						
						if ($agf->line[$i]->top_level == 'Y' && $i != 0 )
						{
							print '</table></td></tr>';
							print '<tr><td>&nbsp;</td></tr>';
							
						}
	
						if ($agf->line[$i]->top_level == 'Y')
						{
							print '<tr><td colspan=6><table width="100%">';
							print '<tr align="center">';
							print '<td colspan=4 >&nbsp;</td><td width="150px">'.$langs->trans("AgfLimitDate").'</td>';
							print '<td width="150px">'.$langs->trans("AgfDateDebut").'</td>';
							print '<td width="150px">'.$langs->trans("AgfDateFin").'</td></tr>';
							print '<table><table class="border" width="100%">';
						
						}
						print '<tr class="border">';
						
						// debug
						//print '<td>alertday_mkt :'.$alertday_mktime.'<br>today_mkt : '.$today_mktime.'<br>sessdated :'.$infosdate->sessdated.'<br>alertday :'.$alertday.'</td>';
						
						print '<td width="10px" bgcolor="'.$bgcolor.'">&nbsp;</td>';
						if ($agf->line[$i]->top_level == 'Y') print '<td colspan=2" style="border-right: 0px">&nbsp;';
						else print '<td  style="border-right: 0px" width="20px">&nbsp;</td><td style="border-left: 0px; border-right: 0px">';
						print '<a href="'.DOL_URL_ROOT.'/agefodd/s_adm.php?action=';
						if ($infos->id)
						{
							print 'edit&id='.$id.'&actid='.$infos->id;
						}
						else
						{
							print 'create&id='.$id.'&admlevel='.$agf->line[$i]->rowid;
						}
						print '">'.$agf->line[$i]->intitule.'</a></td>';
						
						// Affichage éventuelle des notes
						if (!empty($infos->notes)) 
						{
							print '<td class="adminaction" style="border-left: 0px; width: auto; text-align: right" valign="top"><a href="# ">';
							print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/recent.png" border="0" align="absmiddle" hspace="6px" >';
							print '<span>'.wordwrap(stripslashes($infos->notes),50,"<br />",1).'</span></a></td>';
						}
						else print '<td style="border-left: 0px; width:auto;">&nbsp;</td>';
						
						// Affichage des différentes dates
						print '<td width="150px" align="center" valign="top">';
						if ($bgcolor == 'red') print '<font style="color:'.$bgcolor.'">';
						print dol_print_date($alertday);
						if ($bgcolor == 'red') print '</font>';
						print '</td>';
						($infos->dated > '0000-00-00 00:00:00') ? $dated = dol_print_date($infos->dated) : $dated = $langs->trans("AgfNotDefined");
						($infos->datef > '0000-00-00 00:00:00') ? $datef = dol_print_date($infos->datef) : $datef = $langs->trans("AgfNotDefined");
						print '<td width="150px" align="center" valign="top">'.$dated.'</td>';
						print '<td width="150px" align="center" valign="top">'.$datef.'</td>';
					}
					print '</tr>';
				}
			}

			
			print '</table>';
			print '&nbsp;';
			
			print '<table align="center" noborder><tr>';
			print '<td width="10px" bgcolor="green"><td>'.$langs->trans("AgfTerminatedPoint").'&nbsp</td>';
			print '<td width="10px" bgcolor="#ffe27d"><td>'.$langs->trans("AgfXDaysBeforeAlert").'&nbsp;</td>';
			print '<td width="10px" bgcolor="orange"><td>'.$langs->trans("AgfYDaysBeforeAlert").'&nbsp</td>';
			print '<td width="10px" bgcolor="red"><td>'.$langs->trans("AgfAlertDay").'&nbsp</td>';
			print '</tr></table>';

			print '</div>';
		}
	}
}


/*
 * Barre d'actions
 *
 */

/*
print '<div class="tabsAction">';

if ($_GET["action"] != 'create' && $_GET["action"] != 'edit')
{
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butAction" href="s_fiche.php?action=edit&id='.$id.'">'.$langs->trans('Modify').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Modify').'</a>';
	}
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butActionDelete" href="s_fiche.php?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
	}
}

print '</div>';
*/
$db->close();

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
