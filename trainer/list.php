<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2016 Florian Henry <florian.henry@open-concept.pro>
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
 * \file agefodd/traineer/list.php
 * \ingroup agefodd
 * \brief list of trainers
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agefodd_formateur.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../lib/agefodd.lib.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

llxHeader('', $langs->trans("AgfTeacher"));

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$arch = GETPOST('arch', 'int');
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$search_id = GETPOST('search_id', 'int');
$search_lastname = GETPOST('search_lastname', 'alpha');
$search_firstname = GETPOST('search_firstname', 'alpha');
$search_mail = GETPOST('search_mail', 'alpha');
$search_type_trainer = GETPOST('search_type_trainer', 'alpha');

if (empty($sortorder)) {
	$sortorder = "ASC";
}
if (empty($sortfield)) {
	$sortfield = "sp_name, sp_firstname";
}
if (empty($arch)) {
	$arch = 0;
}

if (empty($page) || $page == -1) { $page = 0; }

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x", 'none')) {
	$search_id = '';
	$search_lastname = '';
	$search_firstname = "";
	$search_mail = '';
	$search_type_trainer = '';
}

$filter = array ();
if (! empty($search_id)) {
	$filter ['f.rowid'] = $search_id;
	$option .= '&search_id=' . $search_id;
}
if (! empty($search_lastname)) {
	$filter ['lastname'] = $search_lastname;
	$option .= '&search_lastname=' . $search_lastname;
}
if (! empty($search_firstname)) {
	$filter ['firstname'] = $search_firstname;
	$option .= '&search_firstname=' . $search_firstname;
}
if (! empty($search_mail)) {
	$filter ['mail'] = $search_mail;
	$option .= '&search_mail=' . $search_mail;
}
if (! empty($search_type_trainer)) {
    $filter ['type_trainer'] = $search_type_trainer;
    $option .= '&search_type_trainer=' . $search_type_trainer;
}
if (!empty($limit)) {
	$option .= '&limit=' . $limit;
}


$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$agf = new Agefodd_teacher($db);

$hookmanager->initHooks(array('trainerlist'));

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $agf->fetch_all($sortorder, $sortfield, 0, 0, $arch, $filter);
}
$result = $agf->fetch_all($sortorder, $sortfield, $limit, $offset, $arch, $filter);
if ($result<0) {
	setEventMessage($agf->error,'errors');
}

$linenum = count($agf->lines);



print '<div width=100%" align="right">';
if ($arch == 2) {
	print '<a href="' . $_SERVER ['PHP_SELF'] . '?' . $option . '&arch=0">' . $langs->trans("AgfCacherFormateursArchives") . '</a>' . "\n";
} else {
	print '<a href="' . $_SERVER ['PHP_SELF'] . '?' . $option . '&arch=2">' . $langs->trans("AgfAfficherFormateursArchives") . '</a>' . "\n";
}

print '<form method="post" action="' . $_SERVER ['PHP_SELF'] . '" name="searchFormList" id="searchFormList">' . "\n";

print_barre_liste($langs->trans("AgfTeacher"), $page, $_SERVER ['PHP_SELF'], $option . "&arch=" . $arch, $sortfield, $sortorder, "", $linenum, $nbtotalofrecords,'title_generic.png', 0, '', '', $limit);

print '<table class="noborder  tagtable liste listwithfilterbefore" width="100%">';


// Filter
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_id" value="' . $search_id . '" size="4">';
print '</td>';
print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_lastname" value="' . $search_lastname . '" size="10">';
print '</td>';
print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_firstname" value="' . $search_firstname . '" size="10">';
print '</td>';
print '<td class="liste_titre">';
print '</td>';
print '<td class="liste_titre">';
print '</td>';
print '<td class="liste_titre">';
print '</td>';
print '<td class="liste_titre">';
print '<input type="text" class="flat" name="search_mail" value="' . $search_mail . '" size="10">';
print '</td>';
print '<td class="liste_titre">';
print '<select class="flat" name="search_type_trainer" >';
print '<option value=""></option>';
print '<option value="user" '.($search_type_trainer=='user'? 'selected="selected" ' : '').'>'.$langs->trans('AgfTrainerTypeUser').'</option>';
print '<option value="socpeople" '.($search_type_trainer=='socpeople'? 'selected="selected" ' : '').'>'.$langs->trans('AgfTrainerTypeSocpeople').'</option>';
print '</select>';
print '</td>';
print '<td></td>';
print "</tr>\n";

print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Id"), $_SERVER ['PHP_SELF'], "f.rowid", '', $option, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Name"), $_SERVER ['PHP_SELF'], "sp_name", "", $option, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Firstname"), $_SERVER ['PHP_SELF'], "sp_firstname", "", $option, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("AgfCivilite"), $_SERVER ['PHP_SELF'], "sp_civilite", "", $option, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Phone"), $_SERVER ['PHP_SELF'], "s.phone", "", $option, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("PhoneMobile"), $_SERVER ['PHP_SELF'], "s.phone", "", $option, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Mail"), $_SERVER ['PHP_SELF'], "s.email", "", $option, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans('AgfTrainerNature'), $_SERVER ['PHP_SELF'], "f.type_trainer", "", $option, '', $sortfield, $sortorder);
print '<th width="5%">';
print '<input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
print '&nbsp; ';
print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
print '</th>';
print "</tr>\n";



if ($result > 0) {
	$var = true;
	$i = 0;
	while ( $i < $linenum ) {
		// Affichage liste des formateurs
		$var = ! $var;
		($agf->lines [$i]->archive == 1) ? $style = ' style="color:gray;"' : $style = '';
		print "<tr $bc[$var]>";
		print '<td><span style="background-color:' . $bgcolor . ';"><a href="card.php?id=' . $agf->lines [$i]->id . '"' . $style . '>' . img_object($langs->trans("AgfEditerFicheFormateur"), "user") . ' ' . $agf->lines [$i]->id . '</a></span></td>';
		print '<td' . $style . '>' . $agf->lines [$i]->name . '</td>';
		print '<td' . $style . '>' . $agf->lines [$i]->firstname . '</td>';
		$contact_static = new Contact($db);
		$contact_static->civility_id = $agf->lines [$i]->civilite;
		print '<td' . $style . '>' . $contact_static->getCivilityLabel() . '</td>';
		print '<td' . $style . '>' . dol_print_phone($agf->lines [$i]->phone) . '</td>';
		print '<td' . $style . '>' . dol_print_phone($agf->lines [$i]->phone_mobile) . '</td>';
		print '<td' . $style . '>';
		if ($agf->lines [$i]->archive == 0)
			print dol_print_email($agf->lines [$i]->email, $agf->lines [$i]->spid, "", 'AC_EMAIL', 25);
		else
			print '<a href="mailto:' . $agf->lines [$i]->email . '"' . $style . '>' . $agf->lines [$i]->email . '</a>';
		print '</td>';
		print '<td>'.$langs->trans('AgfTrainerType'.ucfirst($agf->lines[$i]->type_trainer)).'</td>';
		print '<td></td>';
		print "</tr>\n";

		$i ++;
	}
} else {
	setEventMessage($agf->error, 'errors');
}

print "</table>";
print "</form>";
print '<div class="tabsAction">';

if ($_GET ["action"] != 'create' && $_GET ["action"] != 'edit') {
	if ($user->rights->agefodd->creer) {
		print '<a class="butAction" href="card.php?action=create">' . $langs->trans('Create') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Create') . '</a>';
	}
}

print '</div>';

llxFooter();
$db->close();
