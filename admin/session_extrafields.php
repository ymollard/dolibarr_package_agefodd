<?php
/* Copyright (C) 2012-2014		Florian Henry			<florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file /agefodd/admin/session_extrafield.php
 * \ingroup agefodd
 * \brief Page to setup extra fields of session
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory

require_once '../lib/agefodd.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

if (! $user->rights->agefodd->admin && ! $user->admin) {
	accessforbidden();
}

$langs->load("admin");
$langs->load("other");
$langs->load("agefodd@agefodd");

$extrafields = new ExtraFields($db);
$form = new Form($db);

// List of supported format
$tmptype2label = ExtraFields::$type2label;
$type2label = array(
	''
);
foreach ( $tmptype2label as $key => $val )
	$type2label [$key] = $langs->trans($val);

$action = GETPOST('action', 'alpha');
$attrname = GETPOST('attrname', 'alpha');
$elementtype = 'agefodd_session'; // Must be the $table_element of the class that manage extrafield


	/*
 * Actions
 */

require DOL_DOCUMENT_ROOT . '/core/actions_extrafields.inc.php';

/*
 * View
 */

llxHeader('', $langs->trans("AgefoddSetupDesc"));

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("AgefoddSetupDesc"), $linkback, 'setup');
print "<br>\n";

$head = agefodd_admin_prepare_head();

dol_fiche_head($head, 'attributesession', $langs->trans("Module103000Name"), 0, "agefodd@agefodd");

if (file_exists(DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_view.tpl.php')) {
	require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_view.tpl.php';
} else {
	print $langs->trans("DefineHereComplementaryAttributes", $langs->transnoentitiesnoconv("AgfMenuSess")) . '<br>' . "\n";
	print '<br>';

	// Load attribute_label
	$extrafields->fetch_name_optionals_label($elementtype);

	print "<table summary=\"listofattributes\" class=\"noborder\" width=\"100%\">";

	print '<tr class="liste_titre">';
	print '<td>' . $langs->trans("Label") . '</td>';
	print '<td>' . $langs->trans("AttributeCode") . '</td>';
	print '<td>' . $langs->trans("Type") . '</td>';
	print '<td align="right">' . $langs->trans("Size") . '</td>';
	print '<td align="center">' . $langs->trans("Unique") . '</td>';
	print '<td align="center">' . $langs->trans("Required") . '</td>';
	print '<td width="80">&nbsp;</td>';
	print "</tr>\n";

	$var = True;
	foreach ( $extrafields->attribute_type as $key => $value ) {
		$var = ! $var;
		print "<tr " . $bc [$var] . ">";
		print "<td>" . $extrafields->attribute_label [$key] . "</td>\n";
		print "<td>" . $key . "</td>\n";
		print "<td>" . $type2label [$extrafields->attribute_type [$key]] . "</td>\n";
		print '<td align="right">' . $extrafields->attribute_size [$key] . "</td>\n";
		print '<td align="center">' . yn($extrafields->attribute_unique [$key]) . "</td>\n";
		print '<td align="center">' . yn($extrafields->attribute_required [$key]) . "</td>\n";
		print '<td align="right"><a href="' . $_SERVER ["PHP_SELF"] . '?action=edit&attrname=' . $key . '">' . img_edit() . '</a>';
		print "&nbsp; <a href=\"" . $_SERVER ["PHP_SELF"] . "?action=delete&attrname=$key\">" . img_delete() . "</a></td>\n";
		print "</tr>";
	}

	print "</table>";
}
dol_fiche_end();

// Buttons
if ($action != 'create' && $action != 'edit') {
	print '<div class="tabsAction">';
	print "<a class=\"butAction\" href=\"" . $_SERVER ["PHP_SELF"] . "?action=create\">" . $langs->trans("NewAttribute") . "</a>";
	print "</div>";
}

/* ************************************************************************** */
/*                                                                            */
/* Creation d'un champ optionnel											  */
/*                                                                            */
/* ************************************************************************** */

if ($action == 'create') {
	print "<br>";
	print_titre($langs->trans('NewAttribute'));

	require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_add.tpl.php';
}

/* ************************************************************************** */
/*                                                                            */
/* Edition d'un champ optionnel                                               */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'edit' && ! empty($attrname)) {
	print "<br>";
	print_titre($langs->trans("FieldEdition", $attrname));

	require DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_edit.tpl.php';
}

llxFooter();
$db->close();
