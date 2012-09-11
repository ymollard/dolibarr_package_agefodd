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

$limit = $conf->global->AGF_NUM_LIST;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (empty($arch)) $arch = 0;

$agf = new Agefodd($db);

$resql = $agf->fetch_all($sortorder, $sortfield, $limit, $offset, $arch);

print_barre_liste($langs->trans("AgfMenuCat"), $page, $_SERVER['PHP_SELF'],'&arch='.$arch, $sortfield, $sortorder,'', $num);

$i = 0;
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print_liste_field_titre($langs->trans("Id"),$_SERVER['PHP_SELF'],"c.rowid","",'&arch='.$arch,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("AgfIntitule"),$_SERVER['PHP_SELF'],"c.intitule","",'&arch='.$arch,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("AgfRefInterne"),$_SERVER['PHP_SELF'],"c.ref","",'&arch='.$arch,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("AgfDateC"),$_SERVER['PHP_SELF'],"c.datec","",'&arch='.$arch,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("AgfDuree"),$_SERVER['PHP_SELF'],"c.duree","",'&arch='.$arch,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("AgfDateLastAction"),$_SERVER['PHP_SELF'],"a.dated","",'&arch='.$arch,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("AgfNbreAction"),$_SERVER['PHP_SELF'],'' ,'&arch='.$arch,'',$sortfield,$sortorder);
print "</tr>\n";

$var=true;
if ($resql)
{
	foreach ($agf->line as $line)
	{
			
		// Affichage tableau des formations
		$var=!$var;
		print "<tr $bc[$var]>";
		print '<td><a href="card.php?id='.$line->rowid.'">'.img_object($langs->trans("AgfShowDetails"),"service").' '.$line->rowid.'</a></td>';
		print '<td>'.stripslashes($line->intitule).'</td>';
		print '<td>'.$line->ref.'</td>';
		print '<td>'.dol_print_date($line->datec,'daytext').'</td>';
		print '<td>'.$line->duree.'</td>';
		print '<td>'.dol_print_date($line->lastsession,'daytext').'</td>';
		print '<td>'.$line->nbsession.'</td>';
		print "</tr>\n";
		
		$i++;
	}
}
else
{
	dol_syslog("agefodd::trainer::list ".$agf->error, LOG_ERR);
}
    
print "</table>";

llxFooter('$Date: 2010-03-30 07:39:02 +0200 (mar. 30 mars 2010) $ - $Revision: 53 $');
?>
