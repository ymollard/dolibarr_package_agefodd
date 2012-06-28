<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012       Florian Henry   	<florian.henry@open-concept.pro>
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
 * 	\file		/agefodd/trainee/list.php
 * 	\brief		Page présentant la liste des stagiaires enregistrés
 * 	\version	$Id$
 */

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/trainee/class/agefodd_stagiaire.class.php');
dol_include_once('/contact/class/contact.class.php');

// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

llxHeader();

$sortorder=GETPOST('sortorder','alpha');
$sortfield=GETPOST('sortfield','alpha');
$page=GETPOST('page','alpha');

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="s.rowid";


if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$agf = new Agefodd_stagiaire($db);
$result = $agf->fetch_all($sortorder, $sortfield, $limit, $offset);

if ($result != -1)
{
    
    print_barre_liste($langs->trans("AgfStagiaireList"), $page, $_SERVER['PHP_SELF'],"&socid=$socid", $sortfield, $sortorder,'', $result);

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Id"),$_SERVER['PHP_SELF'],"s.rowid","","&socid=$socid",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfNomPrenom"),$_SERVER['PHP_SELF'],"s.nom","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfCivilite"),$_SERVER['PHP_SELF'],"civ.code","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"),$_SERVER['PHP_SELF'],"so.nom","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Phone"),$_SERVER['PHP_SELF'],"s.tel1","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Mail"),$_SERVER['PHP_SELF'],"s.mail","","",'',$sortfield,$sortorder);
    print "</tr>\n";

    $var=true;
    foreach ($agf->line as $line)
    {
	
		// Affichage liste des stagiaires
		$var=!$var;
		print "<tr $bc[$var]>";
		print '<td><a href="card.php?id='.$line->rowid.'">'.img_object($langs->trans("AgfShowDetails"),"user").' '.$line->rowid.'</a></td>';
		print '<td>'.strtoupper($line->nom).' '.ucfirst($line->prenom).'</td>';
		
		$contact_static= new Contact($db);
		$contact_static->civilite_id = $line->civilite;
		
		print '<td>'.$contact_static->getCivilityLabel().'</td>';
		print '<td>';
		if ($line->socid)
		{
			print '<a href="'.dol_buildpath('/comm/fiche.php',1).'?socid='.$line->socid.'">';
			print img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($line->socname,20).'</a>';
		}
		else
		{
			print '&nbsp;';
		}
		print '</td>';
		print '<td>'.dol_print_phone($line->tel1).'</td>';
		print '<td>'.dol_print_email($line->mail, $line->rowid, $line->socid,'AC_EMAIL',25).'</td>';
		print "</tr>\n";
	
    }
    
    print "</table>";
}
else
{
	dol_print_error($db);
}

llxFooter('$Date: 2010-03-28 19:06:42 +0200 (dim. 28 mars 2010) $ - $Revision: 51 $');
?>
