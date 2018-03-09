<?php
/*
 * Copyright (C) 2012-2014		Florian Henry			<florian.henry@open-concept.pro>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file /agefodd/admin/admin_catcost.php
 * \ingroup agefodd
 * \brief agefood module setup page
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory

require_once ('../class/html.formagefodd.class.php');
require_once ('../lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");
require_once (DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php");
require_once DOL_DOCUMENT_ROOT . "/categories/class/categorie.class.php";

if (! $user->rights->agefodd->admin && ! $user->admin) {
	accessforbidden();
}

$langs->load("admin");
$langs->load('agefodd@agefodd');

$action = GETPOST('action', 'alpha');


if ($action == 'setvar') {

	$categ = GETPOST('AGF_CAT_PRODUCT_CHARGES', 'array');
	if (empty($categ)) {
		$res = dolibarr_set_const($db, 'AGF_CAT_PRODUCT_CHARGES', '', 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_CAT_PRODUCT_CHARGES', implode(',', $categ), 'chaine', 0, '', $conf->entity);
	}

	if (! $res > 0)
		$error ++;

	if (! $error) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	} else {
		setEventMessage($langs->trans("Error") . " " . $msg, 'errors');
	}
}

/*
 *  Admin Form
*
*/

llxHeader('', $langs->trans('AgefoddSetupDesc'), '', '', '', '', array (
		'/agefodd/includes/multiselect/js/ui.multiselect.js'
), array (
		'/agefodd/includes/multiselect/css/ui.multiselect.css',
		'/agefodd/css/agefodd.css'
));


$form = new Form($db);
$formAgefodd = new FormAgefodd($db);


$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans("AgefoddSetupDesc"), $linkback, 'setup');

// Configuration header
$head = agefodd_admin_prepare_head();
dol_fiche_head($head, 'catcost', $langs->trans("Module103000Name"), 0, "agefodd@agefodd");


// Admin var of module
print_titre($langs->trans("AgfAdmVar"));

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="setvar">';


print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td width="400px">' . $langs->trans("Valeur") . '</td>';
print '<td></td>';
print "</tr>\n";

// Prefecture d\'enregistrement
print '<tr class="pair"><td>' . $langs->trans("AgfCategOverheadCost") . '</td>';
print '<td align="left">';
$option_categ = array ();
$selected_categ = array ();

// For backward compatibility
if (is_numeric(Categorie::TYPE_PRODUCT)) {
	$typeproduct=Categorie::TYPE_PRODUCT;
} else {
	foreach(Categorie::$MAP_ID_TO_CODE as $key=>$val) {
		if (Categorie::TYPE_PRODUCT==$val) {
			$typeproduct=$key;
			break;
		}
	}
}

$sql = ' SELECT rowid, label FROM '.MAIN_DB_PREFIX.'categorie WHERE type='.$typeproduct.' AND entity IN ('.getEntity('category',1).')';
$resql= $db->query($sql);
if (!$resql) {
	setEventMessage($db->lasterror,'errors');
} else {
	while ($obj=$db->fetch_object($resql)) {
		$option_categ[$obj->rowid]=$obj->label;
	}
}
if (!empty($conf->global->AGF_CAT_PRODUCT_CHARGES)) {
	$selected_categ=explode(',', $conf->global->AGF_CAT_PRODUCT_CHARGES);
}

print $formAgefodd->agfmultiselectarray('AGF_CAT_PRODUCT_CHARGES', $option_categ, $selected_categ);
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfCategOverheadCostHelp"), 1, 'help');
print '</td>';
print '</tr>';

print '</table>';
print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '">';

print '</form>';

llxFooter();
$db->close();
