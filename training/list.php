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
 * 	\file		/agefodd/training/list.php
 * 	\brief		Page présentant la liste des formation enregistrées (passées, actuelles et à venir
 * 	\version	$Id$
 */

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

llxHeader();

$sortorder=GETPOST('sortorder','alpha');
$sortfield=GETPOST('sortfield','alpha');
$page=GETPOST('page','alpha');
$arch=GETPOST('arch','int');

if (empty($sortorder)) $sortorder="DESC";
if (empty($sortfield)) $sortfield="c.rowid";


if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (empty($arch)) $arch = 0;

// TODO move sql query to Model Class
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
    dol_syslog("agefodd::training::list::query sql=".$sql, LOG_DEBUG);
    $num = $db->num_rows($resql);

    print_barre_liste($langs->trans("AgfFormationList"), $page, $_SERVER['PHP_SELF'],'', $sortfield, $sortorder,'', $num);

    $i = 0;
    print '<table class="noborder" width="100%">';
    print "<tr class=\"liste_titre\">";
    print_liste_field_titre($langs->trans("Id"),$_SERVER['PHP_SELF'],"c.rowid","","&socid=$socid",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfIntitule"),$_SERVER['PHP_SELF'],"c.intitule","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfRefInterne"),$_SERVER['PHP_SELF'],"c.ref_interne","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfDateC"),$_SERVER['PHP_SELF'],"c.datec","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfDuree"),$_SERVER['PHP_SELF'],"c.duree","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfDateLastAction"),$_SERVER['PHP_SELF'],"a.dated","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfNbreAction"),$_SERVER['PHP_SELF'],'' ,'','',$sortfield,$sortorder);
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
	    dol_syslog("agefodd::training::list::num_rows sql=".$sql2, LOG_DEBUG);
	}
	else 
	{
	    $db->rollback();
	    dol_print_error($db);
	    dol_syslog("agefodd::training::list::num_rows ".$errmsg, LOG_ERR);
	}
	
	// Affichage tableau des formations
	$var=!$var;
	print "<tr $bc[$var]>";
	print '<td><a href="card.php?id='.$objp->rowid.'">'.img_object($langs->trans("AgfShowDetails"),"service").' '.$objp->rowid.'</a></td>';
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
    $db->free($resql2);
    $db->free($resql);
}
else
{
    dol_print_error($db);
    dol_syslog("agefodd::training::list::query::update ".$errmsg, LOG_ERR);
}

$db->close();

llxFooter('$Date: 2010-03-30 07:39:02 +0200 (mar. 30 mars 2010) $ - $Revision: 53 $');
?>
