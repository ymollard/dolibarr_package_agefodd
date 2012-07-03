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

$agf = new Agefodd_session($db);

$resql = $agf->fetch_all($sortorder, $sortfield, $limit, $offset, $arch);

if ($resql != -1)
{
	$num = $resql;
	
	if (empty($arch)) $menu = $langs->trans("AgfMenuSessAct");
	elseif ($arch == 2 ) $menu = $langs->trans("AgfMenuSessArchReady");
	else $menu = $langs->trans("AgfMenuSessArch");
	print_barre_liste($menu, $page, $_SERVEUR['PHP_SELF'],"&socid=$socid", $sortfield, $sortorder,'', $num);
	
	$i = 0;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Id"),$_SERVEUR['PHP_SELF'],"s.rowid","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfIntitule"),$_SERVEUR['PHP_SELF'],"c.intitule","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfRefInterne"),$_SERVEUR['PHP_SELF'],"c.ref","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfDateDebut"),$_SERVEUR['PHP_SELF'],"s.dated","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfDateFin"),$_SERVEUR['PHP_SELF'],"s.datef","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfLieu"),$_SERVEUR['PHP_SELF'],"p.ref_interne","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfNbreParticipants"),$_SERVEUR['PHP_SELF'],"num",'' ,'','',$sortfield,$sortorder);
	print "</tr>\n";
	
	$var=true;
	foreach ($agf->line as $line)
	{
		
		// Affichage tableau des sessions
		$var=!$var;
		print "<tr $bc[$var]>";
		print '<td><a href="card.php?id='.$line->rowid.'">'.img_object($langs->trans("AgfShowDetails"),"service").' '.$line->rowid.'</a></td>';
		print '<td>'.stripslashes(dol_trunc($line->intitule, 60)).'</td>';
		print '<td>'.$line->ref.'</td>';
		print '<td>'.dol_print_date($line->dated,'daytext').'</td>';
		print '<td>'.dol_print_date($line->datef,'daytext').'</td>';
		print '<td>'.stripslashes($line->ref_interne).'</td>';
		print '<td>'.$line->num.'</td>';
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


llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
