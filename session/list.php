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
 * 	\file		/agefodd/session/list.php
 * 	\brief		Page présentant la liste des formation enregistrées (passées, actuelles et à venir
 * 	\version	$Id$
 */

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/session/class/agefodd_session.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

llxHeader();

$sortorder=GETPOST('sortorder','alpha');
$sortfield=GETPOST('sortfield','alpha');
$page=GETPOST('page','int');
$arch=GETPOST('arch','int');

if (empty($sortorder)) $sortorder="DESC";
if (empty($sortfield)) $sortfield="c.rowid";
if (empty($arch)) $arch = 0;

if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$db->begin();

$sql = "SELECT s.rowid, s.fk_session_place, s.dated, s.datef,";
$sql.= " c.intitule, c.ref,";
$sql.= " p.ref_interne,";
$sql.= " (SELECT count(*) FROM ".MAIN_DB_PREFIX."agefodd_session_stagiaire WHERE fk_session=s.rowid) as num";
$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c";
$sql.= " ON c.rowid = s.fk_formation_catalogue";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_place as p";
$sql.= " ON p.rowid = s.fk_session_place";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_stagiaire as ss";
$sql.= " ON s.rowid = ss.fk_session";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_adminsitu as sa";
$sql.= " ON s.rowid = sa.fk_agefodd_session";

if ($arch == 2)
{
	$sql.= " WHERE s.archive LIKE 0";
	$sql.= " AND sa.indice=";
	$sql.= "(";
	$sql.= " SELECT MAX(indice) FROM llx_agefodd_session_adminsitu WHERE level_rank=0";
	$sql.= ")";
	$sql.= " AND sa.archive LIKE 1 AND sa.datef > '0000-00-00 00:00:00'";
}
else $sql.= " WHERE s.archive LIKE ".$arch;
$sql.= " GROUP BY (s.rowid)";
$sql.= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit + 1 ,$offset);
$resql = $db->query($sql);

if ($resql)
{
	$num = $db->num_rows($resql);
	
	if (empty($arch)) $menu = $langs->trans("AgfMenuSessAct");
	elseif ($arch == 2 ) $menu = $langs->trans("AgfMenuSessArchReady");
	else $menu = $langs->trans("AgfMenuSessArch");
	print_barre_liste($menu, $page, $_SERVEUR['PHP_SELF'],"&socid=$socid", $sortfield, $sortorder,'', $num);
	
	$i = 0;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Id"),$_SERVEUR['PHP_SELF'],"s.rowid","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfIntitule"),$_SERVEUR['PHP_SELF'],"s.intitule","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfRefInterne"),$_SERVEUR['PHP_SELF'],"s.ref","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfDateDebut"),$_SERVEUR['PHP_SELF'],"s.dated","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfDateFin"),$_SERVEUR['PHP_SELF'],"s.datef","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfLieu"),$_SERVEUR['PHP_SELF'],"s.ref_interne","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfNbreParticipants"),$_SERVEUR['PHP_SELF'],"num",'' ,'','',$sortfield,$sortorder);
	print "</tr>\n";
	
	$var=true;
	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);
		
		
		// Affichage tableau des sessions
		$var=!$var;
		print "<tr $bc[$var]>";
		print '<td><a href="card.php?id='.$objp->rowid.'">'.img_object($langs->trans("AgfShowDetails"),"service").' '.$objp->rowid.'</a></td>';
		print '<td>'.stripslashes(dol_trunc($objp->intitule, 60)).'</td>';
		print '<td>'.$objp->ref_interne.'</td>';
		print '<td>'.dol_print_date($objp->dated,'daytext').'</td>';
		print '<td>'.dol_print_date($objp->datef,'daytext').'</td>';
		print '<td>'.stripslashes($objp->ref_interne).'</td>';
		print '<td>'.$objp->num.'</td>';
		print "</tr>\n";
		
		$i++;
	}
	
	print "</table>";
}
else
{
    dol_print_error($db);
    dol_syslog("agefodd::session:list::query: ".$errmsg, LOG_ERR);
}

$db->close();

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
