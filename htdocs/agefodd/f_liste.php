<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne		<eric.seigne@ryxeo.com>
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
	\file		$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/f_liste.php $
	\brief		Page présentant la liste des formation enregsitrées (passées, actuelles et à venir
	\version	$Id: f_liste.php 53 2010-03-30 05:39:02Z ebullier $
*/

require("./pre.inc.php");
require_once("./agefodd_formation_catalogue.class.php");


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

llxHeader();

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$page=$_GET["page"];

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="c.rowid";


if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!isset($_GET["arch"])) $arch = 0;
else $arch = $_GET["arch"];

$db->begin();

$sql = "SELECT c.rowid, c.intitule, c.ref_interne, c.datec, c.duree,";
$sql.= " IF(a.archive LIKE '1',c.datec, '') as lastsession,";
$sql.= " a.dated";
$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session as a";
$sql.= " ON c.rowid = a.fk_formation_catalogue";
$sql.= " WHERE c.archive LIKE ".$arch;

$sql.= " GROUP BY c.ref_interne";
$sql.= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit + 1 ,$offset);
$resql = $db->query($sql);

if ($resql)
{
    dol_syslog("agefodd::f_liste::query sql=".$sql, LOG_DEBUG);
    $num = $db->num_rows($resql);

    print_barre_liste($langs->trans("AgfFormationList"), $page, "f_liste.php","&socid=$socid", $sortfield, $sortorder,'', $num);

    $i = 0;
    print '<table class="noborder" width="100%">';
    print "<tr class=\"liste_titre\">";
    print_liste_field_titre($langs->trans("Id"),"f_liste.php","c.rowid","","&socid=$socid",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfIntitule"),"f_liste.php","c.intitule","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfRefInterne"),"f_liste.php","c.ref_interne","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfDateC"),"f_liste.php","c.datec","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfDuree"),"f_liste.php","c.duree","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfDateLastAction"),"f_liste.php","a.dated","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfNbreAction"),"f_liste.php",'' ,'','',$sortfield,$sortorder);
    print "</tr>\n";

    $var=true;
    while ($i < $num)
    {
	$objp = $db->fetch_object($resql);


	// Totalisation des sessions réalisées par type de formation
	$sql2 = "SELECT a.rowid";
	$sql2.= " FROM ".MAIN_DB_PREFIX."agefodd_session as a";
	$sql2.= " WHERE fk_formation_catalogue = ".$objp->rowid;
	$sql2.= " AND archive LIKE '1'";
	
	$resql2 = $db->query($sql2);
	if ($resql2) {
	    $count = $db->num_rows($resql2);
	    dol_syslog("agefodd::f_liste::num_rows sql=".$sql2, LOG_DEBUG);
	}
	else 
	{
	    $db->roolback();
	    dol_print_error($db);
	    dol_syslog("agefodd::f_liste::num_rows ".$errmsg, LOG_ERR);
	}
	
	// Affichage tableau des formations
	$var=!$var;
	print "<tr $bc[$var]>";
	print '<td><a href="f_fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans("AgfShowDetails"),"service").' '.$objp->rowid.'</a></td>';
	print '<td>'.stripslashes($objp->intitule).'</td>';
	print '<td>'.$objp->ref_interne.'</td>';
	print '<td>'.dol_print_date($objp->datec,'day').'</td>';
	print '<td>'.$objp->duree.'</td>';
	//print '<td>'.dol_print_date($objp->dated,'day').'</td>';
	print '<td>'.dol_print_date($objp->lastsession,'day').'</td>';
	print '<td>'.$count.'</td>';
	print "</tr>\n";

	$i++;
    }
    
    print "</table>";
    $db->free($resql);
}
else
{
    dol_print_error($db);
    dol_syslog("agefodd::f_liste::query::update ".$errmsg, LOG_ERR);
}

$db->close();

llxFooter('$Date: 2010-03-30 07:39:02 +0200 (mar. 30 mars 2010) $ - $Revision: 53 $');
?>
