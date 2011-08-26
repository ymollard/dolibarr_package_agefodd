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
 * 	\file		/agefodd/s_adm_liste.php
 * 	\brief		Page présentant la liste des tâches administratives de gestion des actions de formation en cours
 * 	\version	$Id$
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory

require_once("./session/class/agefodd_sessadm.class.php");
require_once("./lib/agefodd.lib.php");


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

llxHeader();

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$page=$_GET["page"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.datea";


if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!isset($_GET["arch"])) $arch = 0;
else $arch = $_GET["arch"];

$db->begin();

$agf = new Agefodd_sessadm($db);

if ( $_GET["filtre"] == '0') 
{
	// Dates d'alerte atteinte ou dépasée
	$date1=1;
	$date2=-365;
	$resql = $agf->fetch_session_per_dateLimit($sortorder, $sortfield, $limit, $offset, $date1, $date2);
	$bgcolor = 'red';
	$legende = $langs->trans("AgfAlertLevel0");
	//$legende = "Dates d'alerte dans ".$date2." à ".$date1." jours";
}
elseif ( $_GET["filtre"] == '1') 
{
	// Dates d'alertes dans 3 à 1 jours
	$date1=3;
	$date2=1;
	$bgcolor = 'orange';
	$resql = $agf->fetch_session_per_dateLimit($sortorder, $sortfield, $limit, $offset, $date1, $date2);
	$legende = $langs->trans("AgfAlertLevel1");
	//$legende = "Dates d'alerte dans ".$date2." à ".$date1." jours";
}
elseif ( $_GET["filtre"] == '2')
{
	// Dates d'alerte dans 8 a 3 jours
	$date1=8;
	$date2=3;
	$bgcolor = '#ffe27d';
	$resql = $agf->fetch_session_per_dateLimit($sortorder, $sortfield, $limit, $offset, $date1, $date2);
	$legende = $langs->trans("AgfAlertLevel2");
	//$legende = "Dates d'alerte dans ".$date2." à ".$date1." jours";
}
elseif ( $_GET["filtre"] == '3')
{
	//toutes dates d'alerte
	$date1=0;
	$date2=0;
	$resql = $agf->fetch_session_per_dateLimit($sortorder, $sortfield, $limit, $offset, $date1, $date2);
	$legende = $langs->trans("AgfAlertLevel3");
}
else
{
	$resql = $agf->fetch_session_to_archive($sortorder, $sortfield, $limit, $offset);
	$legende = $langs->trans("AgfAlertLevel4");
}

$linenum = count($agf->line);

if ($resql)
{
	dol_syslog("agefodd::s_adm_liste::query sql=".$sql, LOG_DEBUG);
	
	print_barre_liste($langs->trans("AgfSessAdmList"), $page, $_SERVER['PHP_SELF'],"&filtre=".$_GET["filtre"], $sortfield, $sortorder,'', $linenum);
	
	print $linenum.' '.$legende;
	$i = 0;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Id"),$_SERVER['PHP_SELF'],"s.rowid","","&filtre=".$_GET["filtre"],'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfIntitule"),$_SERVER['PHP_SELF'],"s.intitule","", "&filtre=".$_GET["filtre"],'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfLimitDate"),$_SERVER['PHP_SELF'],"s.datea","","&filtre=".$_GET["filtre"],'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfSessionDetail"),$_SERVER['PHP_SELF'],"f.intitule","","&filtre=".$_GET["filtre"],'',$sortfield,$sortorder);
	print "</tr>\n";
	
	$var=true;
	while ($i < $linenum)
	{
		// Affichage tableau des sessions
		$var=!$var;
		print "<tr $bc[$var]>";
		print '<td><span style="background-color:'.$bgcolor.';"><a href="'.dol_buildpath('/agefodd/session/administrative.php',1).'?action=edit&id='.$agf->line[$i]->sessid.'&actid='.$agf->line[$i]->rowid.'">'.img_object($langs->trans("AgfShowDetails"),"service").' '.$agf->line[$i]->rowid.'</a></span></td>';
		print '<td>'.dol_trunc($agf->line[$i]->intitule, 60).'</td>';
		print '<td>'.dol_print_date($agf->line[$i]->datea).'</td>';
		print '<td>'.$agf->line[$i]->titre.' (du '.dol_print_date($agf->line[$i]->sessdated).' au '.dol_print_date($agf->line[$i]->sessdatef).')</td>';
		print "</tr>\n";
	
		$i++;
    }
    
    print "</table>";
}
else
{
    dol_print_error($db);
    dol_syslog("agefodd::s_adm_liste::query::update ".$errmsg, LOG_ERR);
}

$db->close();

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
