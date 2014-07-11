<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2014 Florian Henry <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file agefodd/site/list.php
 * \ingroup agefodd
 * \brief list of place
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agefodd_place.class.php');
require_once ('../lib/agefodd.lib.php');

// Security check
if (! $user->rights->agefodd->agefodd_place->lire)
	accessforbidden();

llxHeader('', $langs->trans("AgfSessPlace"));

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$arch = GETPOST('arch', 'int');

$search_soc = GETPOST("search_soc");
$search_ref_interne = GETPOST("search_ref_interne");

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x")) {
	$search_soc = '';
	$search_ref_interne = '';
}

$filter = array ();
if (! empty($search_soc)) {
	$filter ['s.nom'] = $search_soc;
	$option .= "&search_soc=" . $search_soc;
}
if (! empty($search_ref_interne)) {
	$filter ['p.ref_interne'] = $search_ref_interne;
	$option .= "&search_ref_interne=" . $search_ref_interne;
}
if ($arch != '') {
	$filter ['p.archive'] = $arch;
	$option .= "&arch=" . $arch;
}

if (empty($sortorder))
	$sortorder = "ASC";
if (empty($sortfield))
	$sortfield = "p.ref_interne";


if ($page == - 1) {
	$page = 0;
}

$limit = $conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$agf = new Agefodd_place($db);

$result = $agf->fetch_all($sortorder, $sortfield, $limit, $offset, $filter);

$linenum = count($agf->lines);

print_barre_liste($langs->trans("AgfSessPlace"), $page, $_SERVER ['PHP_SELF'], "&arch=" . $arch, $sortfield, $sortorder, "", $linenum);

print '<div width="100%" align="right">';
if ($arch == 2) {
	print '<a href="' . $_SERVER ['PHP_SELF'] . '?arch=0">' . $langs->trans("AgfCacherPlaceArchives") . '</a>' . "\n";
} else {
	print '<a href="' . $_SERVER ['PHP_SELF'] . '?arch=2">' . $langs->trans("AgfAfficherPlaceArchives") . '</a>' . "\n";
}

print '<a href="' . $_SERVER ['PHP_SELF'] . '?arch=' . $arch . '">' . $txt . '</a>' . "\n";


print '<form method="get" action="' . $_SERVER ['PHP_SELF'] . '" name="search_form">' . "\n";
if (! empty($sortfield))
	print '<input type="hidden" name="sortfield" value="' . $sortfield . '"/>';
if (! empty($sortorder))
	print '<input type="hidden" name="sortorder" value="' . $sortorder . '"/>';
if (! empty($page))
	print '<input type="hidden" name="page" value="' . $page . '"/>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Id"), $_SERVER ['PHP_SELF'], "p.rowid", '',  $option, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("AgfIntitule"), $_SERVER ['PHP_SELF'], "p.ref_interne", '',  $option, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Company"), $_SERVER ['PHP_SELF'], "s.nom", '',  $option, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Phone"), $_SERVER ['PHP_SELF'], "p.tel", "",  $option, '', $sortfield, $sortorder);
print "</tr>\n";


//Filter
print '<tr class="liste_titre">';

print '<td>&nbsp;</td>';

print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_ref_interne" value="' . $search_ref_interne . '" size="20">';
print '</td>';

print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_soc" value="' . $search_soc . '" size="20">';
print '</td>';

print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
print '&nbsp; ';
print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
print '</td>';

print "</tr>\n";


if ($result > 0) {
	$var = true;
	$i = 0;
	while ( $i < $linenum ) {
		// Affichage liste des sites de formation
		$var = ! $var;
		($agf->lines [$i]->archive == 1) ? $style = ' style="color:gray;"' : $style = '';
		print "<tr $bc[$var]>";
		print '<td><span style="background-color:' . $bgcolor . ';"><a href="card.php?id=' . $agf->lines [$i]->id . '"' . $style . '>' . img_object($langs->trans("AgfEditerFichePlace"), "company") . ' ' . $agf->lines [$i]->id . '</a></span></td>' . "\n";
		print '<td' . $style . '>' . $agf->lines [$i]->ref_interne . '</td>' . "\n";
		print '<td><a href="' . DOL_URL_ROOT . '/comm/fiche.php?socid=' . $agf->lines [$i]->socid . '"  alt="' . $langs->trans("AgfEditerFicheCompany") . '" title="' . $langs->trans("AgfEditerFicheCompany") . '"' . $style . '>' . $agf->lines [$i]->socname . '</td>' . "\n";
		print '<td' . $style . '>' . dol_print_phone($agf->lines [$i]->tel) . '</td>' . "\n";
		print '</tr>' . "\n";
		
		$i ++;
	}
} else {
	setEventMessage($agf->error, 'errors');
}
print "</table>";
print '</form>';

print '<div class="tabsAction">';
if ($action != 'create' && $action != 'edit') {
	if ($user->rights->agefodd->agefodd_place->creer) {
		print '<a class="butAction" href="card.php?action=create">' . $langs->trans('Create') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Create') . '</a>';
	}
}

print '</div>';

llxFooter();
$db->close();