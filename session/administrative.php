<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
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
 *  \file       	/agefodd/session/administrative.php
 *  \brief      	Page de gestion des tâches administratives (session de formation)
 *  \version		$Id$
 */

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);


$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/session/class/agefodd_sessadm.class.php');
dol_include_once('/agefodd/admin/class/agefodd_session_admlevel.class.php');
dol_include_once('/agefodd/session/class/agefodd_session.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$mesg = '';

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('id','int');
$actid=GETPOST('actid','int');

/*
 * Actions delete
 */
if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_sessadm($db);
	$result = $agf->remove($actid);
	
	if ($result > 0)
	{
		Header ("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
	else
	{
		dol_syslog("Agefodd:administrative:agefodd error=".$agf->error, LOG_ERR);
		$mesg = '<div class="error">'.$agf->error.'</div>';
	}

}


/*
 * Action update
 */
if ($action == 'update' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"] && ! $_POST["delete"])
	{
		$agf = new Agefodd_sessadm($db);

		$result = $agf->fetch($id);

		$agf->datea = GETPOST('dateayear','int').'-'.GETPOST('dateamonth','int').'-'.GETPOST('dateaday','int');
		$agf->dated = GETPOST('dadyear','int').'-'.GETPOST('dadmonth','int').'-'.GETPOST('dadday','int');
		$agf->datef = GETPOST('dafyear','int').'-'.GETPOST('dafmonth','int').'-'.GETPOST('dafday','int');
		$agf->notes = GETPOST('notes','alpha');
		$agf->id = $actid;
		($agf->datef > '0000-00-00 00:00:00') ? $agf->archive = 1 : $agf->archive = 0;
		$result = $agf->update($user->id);

		if ($result > 0)
		{
			Header ("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
		else
		{
			dol_syslog("Agefodd:administrative:agefodd error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	}
	elseif ($_POST["delete"])
	{
		Header ( 'Location:'. $_SERVER['PHP_SELF'].'?id='.$id.'&action=edit&delete=1&actid='.$actid);
		exit;
	}
	else
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
}

/*
 * Action create
 */

if ($action == 'create' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"] )
	{
		$agf = new Agefodd_sessadm($db);

		$agf->fk_agefodd_session_admlevel = GETPOST('admlevel','int');
		$agf->fk_agefodd_session = $id;
		$agf->delais_alerte = GETPOST('alerte','alpha');
		$agf->intitule = GETPOST('intitule','alpha');
		$agf->indice = GETPOST('indice','int');
		$agf->top_level = GETPOST('toplevel','int');
		$agf->datea = GETPOST('dateayear','int').'-'.GETPOST('dateamonth','int').'-'.GETPOST('dateaday','int');
		$agf->dated = GETPOST('dadyear','int').'-'.GETPOST('dadmonth','int').'-'.GETPOST('dadday','int');
		$agf->notes = GETPOST('notes','alpha');
		$result = $agf->create($user->id);

		if ($result > 0)
		{
			Header ( 'Location: administrative.php?id='.$id.'&action=edit&actid='.$agf->id);
			exit;
		}
		else
		{
			dol_syslog("Agefodd:administrative:agefodd error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}

	}
	else
	{
		Header ( "Location: administrative.php?id=".$id);
		exit;
	}
}


/*
 * View
 */

llxHeader();

$form = new Form($db);

dol_htmloutput_mesg($mesg);

if ($user->rights->agefodd->creer)
{
	// Affichage de la fiche "session"
	if ($id)
	{		
		// Affichage en mode "consultation"
		$agf_session = new Agefodd_session($db);
		$res = $agf_session->fetch($id);

		$head = session_prepare_head($agf_session);
			
		dol_fiche_head($head, 'administrative', $langs->trans("AgfSessionDetail"), 0, 'bill');
		
		$agf = new Agefodd_sessadm($db);
		
		// Affichage en mode "édition"
		if ($action == 'edit')
		{
			$result = $agf->fetch_admin_action_rens_from_id($actid);
			
			/*
			* Confirmation de la suppression
			*/
			if ($_GET["delete"] == '1')
			{
				$ret = $form->form_confirm("administrative.php?id=".$id."&actid=".$actid, $langs->trans("AgfDeleteOps"),$langs->trans("AgfConfirmDeleteOps"),"confirm_delete",'','',1);
				if ($ret == 'html') print '<br>';
			}
			print '<form name="update" action="administrative.php?id='.$agf->sessid.'" method="post">'."\n";
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
			$form->select_date($agf->datea,'datea','','','','update');
			print '</span>';
			print '</td></tr>';
			
			print '<tr><td valign="top">'.$langs->trans("AgfSessDateDebut").' ('.$langs->trans("AgfPar").' '.$user->login.')</td><td>';
			$form->select_date($agf->dated, 'dad','','','','update');
			print '</td></tr>';
			print '<tr><td valign="top">'.$langs->trans("AgfSessDateFin").' ('.$langs->trans("AgfPar").' '.$user->login.')</td><td>';
			
			if ($agf->datef == '0000-00-00 00:00:00')
			{
			    print $langs->trans("AgfNoDefined");
			    print '<a href="javascript:DivStatus(\'datef\');" title="afficher detail""> ('.$langs->trans("AgfDefinir").')</a>';
			    print '<span id="datef" style="display:none;">';
			    $form->select_date($agf->datef, 'daf','','','','update');
			    print '</span>';
			
			}
			else
			{
			    $form->select_date($datef->datef, 'daf','','','','update');
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
			$admlevel = new Agefodd_session_admlevel($db);
			$result = $admlevel->fetch_all();
			
			print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
			print ebi_level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_adm_level_number(), $langs->trans("AgfAdmLevel"));
			print '</div>';

			print '<table width="100%" style="border:0px">';

			if ($result)
			{
				for ($i=0; $i < $result; $i++)
				{
				
					$infos = new Agefodd_sessadm($db);
					$result3 = $infos->fetch_admin_action_rens($admlevel->line[$i]->rowid, $id);
				
					$bgcolor = '#d5baa8';
					
					if ($result3)
					{
						// Calcul de la date d'alerte
						$today_mktime = dol_mktime(0, 0, 0, date("m"), date("d"), date("y"));
						if ($infos->datea) 
						{
							$alertday = $infos->datea;
							$alertday_mktime = ($infos->datea);
						}
						else
						{
							$sec_before_alert = ($admlevel->line[$i]->alerte * 86400);
							
							if ($sessinfo->dated > '0000-00-00 00:00:00') $alertday_mktime = (mysql2timestamp($sessinfo->dated) + $sec_before_alert);
							else $alertday_mktime = $today_mktime;
							
							$alertday = date("Y-m-d H:i:s", $alertday_mktime);

							// Si delais alerte = 0 (debut de la formation par exemple)
							if ($admlevel->line[$i]->alerte == 0) $alertday = $sessinfo->dated;
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
						
						if ($admlevel->line[$i]->level_rank == '0' && $i != 0 )
						{
							print '</table></td></tr>';
							print '<tr><td>&nbsp;</td></tr>';
							
						}
	
						if ($admlevel->line[$i]->level_rank == '0')
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
						if ($admlevel->line[$i]->level_rank == '0') print '<td colspan=2" style="border-right: 0px">&nbsp;';
						else print '<td  style="border-right: 0px" width="20px">&nbsp;</td><td style="border-left: 0px; border-right: 0px">';
						print '<a href="'.dol_buildpath('/agefodd/session/administrative.php',1).'?action=edit&id='.$id.'&actid='.$infos->id.'">'.$admlevel->line[$i]->intitule.'</a></td>';
						
						/*else
						{ //TODO : create new action
							print 'create&id='.$id.'&admlevel='.$admlevel->line[$i]->rowid;
						}*/
						
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

if ($action != 'create' && $action != 'edit')
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
