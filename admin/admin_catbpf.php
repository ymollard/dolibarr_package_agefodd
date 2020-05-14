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
 * \file /agefodd/admin/admin_catbpf.php
 * \ingroup agefodd
 * \brief agefood module setup page
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory

require_once '../class/html.formagefodd.class.php';
require_once '../class/report_bpf.class.php';
require_once '../lib/agefodd.lib.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT . "/categories/class/categorie.class.php";

if (! $user->rights->agefodd->admin && ! $user->admin) {
	accessforbidden();
}

$langs->load("admin");
$langs->load('agefodd@agefodd');

$action = GETPOST('action', 'alpha');

if ($action == 'setvar') {

	$categ = GETPOST('AGF_CAT_BPF_OPCA', 'array');
	if (empty($categ)) {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_OPCA', '', 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_OPCA', implode(',', $categ), 'chaine', 0, '', $conf->entity);
	}

	if (! $res > 0) {
		$error ++;
	}

	$categ = GETPOST('AGF_CAT_BPF_ADMINISTRATION', 'array');
	if (empty($categ)) {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_ADMINISTRATION', '', 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_ADMINISTRATION', implode(',', $categ), 'chaine', 0, '', $conf->entity);
	}

	if (! $res > 0) {
		$error ++;
	}

	$categ = GETPOST('AGF_CAT_BPF_FAF', 'array');
	if (empty($categ)) {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_FAF', '', 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_FAF', implode(',', $categ), 'chaine', 0, '', $conf->entity);
	}

	if (! $res > 0) {
		$error ++;
	}

	$categ = GETPOST('AGF_CAT_BPF_PARTICULIER', 'array');
	if (empty($categ)) {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_PARTICULIER', '', 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_PARTICULIER', implode(',', $categ), 'chaine', 0, '', $conf->entity);
	}

	if (! $res > 0) {
		$error ++;
	}

	$categ = GETPOST('AGF_CAT_BPF_PRESTA', 'array');
	if (empty($categ)) {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_PRESTA', '', 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_PRESTA', implode(',', $categ), 'chaine', 0, '', $conf->entity);
	}

	if (! $res > 0) {
		$error ++;
	}

	$categ = GETPOST('AGF_CAT_BPF_FOREIGNCOMP', 'array');
	if (empty($categ)) {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_FOREIGNCOMP', '', 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_FOREIGNCOMP', implode(',', $categ), 'chaine', 0, '', $conf->entity);
	}

	if (! $res > 0) {
		$error ++;
	}

	$categ = GETPOST('AGF_CAT_BPF_TOOLPEDA', 'array');
	if (empty($categ)) {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_TOOLPEDA', '', 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_TOOLPEDA', implode(',', $categ), 'chaine', 0, '', $conf->entity);
	}

	if (! $res > 0) {
		$error ++;
	}

	$categ = GETPOST('AGF_CAT_BPF_PRODPEDA', 'array');
	if (empty($categ)) {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_PRODPEDA', '', 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_PRODPEDA', implode(',', $categ), 'chaine', 0, '', $conf->entity);
	}

	if (! $res > 0) {
		$error ++;
	}


	$categ = GETPOST('AGF_CAT_BPF_FEEPRESTA', 'array');
	if (empty($categ)) {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_FEEPRESTA', '', 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_CAT_BPF_FEEPRESTA', implode(',', $categ), 'chaine', 0, '', $conf->entity);
	}

	if (! $res > 0) {
		$error ++;
	}

	if (! $error) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	} else {
		setEventMessage($langs->trans("Error") . " " . $msg, 'errors');
	}


	if (! $error) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	} else {
		setEventMessage($langs->trans("Error") . " " . $msg, 'errors');
	}
} elseif ($action=='createcateg') {
	$report_bpf = new ReportBPF($db, $langs);
	$result=$report_bpf->createDefaultCategAffectConst();
	if ($result<0) {
		setEventMessages(null,$report_bpf->errors, 'errors');
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
print load_fiche_titre($langs->trans("AgefoddSetupDesc"), $linkback, 'setup');

// Configuration header
$head = agefodd_admin_prepare_head();
dol_fiche_head($head, 'catbpf', $langs->trans("Module103000Name"), 0, "agefodd@agefodd");


print load_fiche_titre($langs->trans("AgfAdmBPFCreateCategorie"));

print '<table class="noborder" width="100%">';

print '<tr class="pair">';
print '<td width="10%"><div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=createcateg">' . $langs->trans("AgfAdmBPFCreateCategorie") . '</a></td>';
print '<td align="rigth">';
print $form->textwithpicto('', $langs->trans("AgfAdmBPFCreateCategorieHelp"), 1, 'help');
print '</td>';
print "</tr>\n";
print '</table>';

// Admin var of module
print load_fiche_titre($langs->trans("AgfAdmVar"));

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="setvar">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td width="400px">' . $langs->trans("Valeur") . '</td>';
print '<td></td>';
print "</tr>\n";

// Cat OPCA
print '<tr class="impair"><td>' . $langs->trans("AgfReportBPFCategOPCA") . '</td>';
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

// For backward compatibility
if (is_numeric(Categorie::TYPE_CUSTOMER)) {
	$typecustomer=Categorie::TYPE_CUSTOMER;
} else {
	foreach(Categorie::$MAP_ID_TO_CODE as $key=>$val) {
		if (Categorie::TYPE_CUSTOMER==$val) {
			$typecustomer=$key;
			break;
		}
	}
}

// For backward compatibility
if (is_numeric(Categorie::TYPE_SUPPLIER)) {
	$typesupplier=Categorie::TYPE_SUPPLIER;
} else {
	foreach(Categorie::$MAP_ID_TO_CODE as $key=>$val) {
		if (Categorie::TYPE_SUPPLIER==$val) {
			$typesupplier=$key;
			break;
		}
	}
}


$sql = ' SELECT rowid, label FROM ' . MAIN_DB_PREFIX . 'categorie WHERE type=' . $typecustomer . ' AND entity IN (' . getEntity('category', 1) . ')';
$resql = $db->query($sql);
if (! $resql) {
	setEventMessage($db->lasterror, 'errors');
} else {
	while ( $obj = $db->fetch_object($resql) ) {
		$option_categ[$obj->rowid] = $obj->label;
	}
}
if (! empty($conf->global->AGF_CAT_BPF_OPCA)) {
	$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_OPCA);
}

print $formAgefodd->agfmultiselectarray('AGF_CAT_BPF_OPCA', $option_categ, $selected_categ);
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfReportBPFCategOPCAHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Cat Administration
print '<tr class="pair"><td>' . $langs->trans("AgfReportBPFCategAdmnistration") . '</td>';
print '<td align="left">';
$option_categ = array ();
$selected_categ = array ();

$sql = ' SELECT rowid, label FROM ' . MAIN_DB_PREFIX . 'categorie WHERE type=' . $typecustomer . ' AND entity IN (' . getEntity('category', 1) . ')';
$resql = $db->query($sql);
if (! $resql) {
	setEventMessage($db->lasterror, 'errors');
} else {
	while ( $obj = $db->fetch_object($resql) ) {
		$option_categ[$obj->rowid] = $obj->label;
	}
}
if (! empty($conf->global->AGF_CAT_BPF_ADMINISTRATION)) {
	$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_ADMINISTRATION);
}

print $formAgefodd->agfmultiselectarray('AGF_CAT_BPF_ADMINISTRATION', $option_categ, $selected_categ);
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfReportBPFCategAdmnistrationHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Cat FAF
print '<tr class="pair"><td>' . $langs->trans("AgfReportBPFCategFAF") . '</td>';
print '<td align="left">';
$option_categ = array ();
$selected_categ = array ();

$sql = ' SELECT rowid, label FROM ' . MAIN_DB_PREFIX . 'categorie WHERE type=' . $typecustomer . ' AND entity IN (' . getEntity('category', 1) . ')';
$resql = $db->query($sql);
if (! $resql) {
	setEventMessage($db->lasterror, 'errors');
} else {
	while ( $obj = $db->fetch_object($resql) ) {
		$option_categ[$obj->rowid] = $obj->label;
	}
}
if (! empty($conf->global->AGF_CAT_BPF_FAF)) {
	$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_FAF);
}

print $formAgefodd->agfmultiselectarray('AGF_CAT_BPF_FAF', $option_categ, $selected_categ);
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfReportBPFCategFAFHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Cat Entrprise Etrangére
print '<tr class="impair"><td>' . $langs->trans("AgfReportBPFCategForeignComp") . '</td>';
print '<td align="left">';
$option_categ = array ();
$selected_categ = array ();

$sql = ' SELECT rowid, label FROM ' . MAIN_DB_PREFIX . 'categorie WHERE type=' . $typecustomer . ' AND entity IN (' . getEntity('category', 1) . ')';
$resql = $db->query($sql);
if (! $resql) {
	setEventMessage($db->lasterror, 'errors');
} else {
	while ( $obj = $db->fetch_object($resql) ) {
		$option_categ[$obj->rowid] = $obj->label;
	}
}
if (! empty($conf->global->AGF_CAT_BPF_FOREIGNCOMP)) {
	$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_FOREIGNCOMP);
}

print $formAgefodd->agfmultiselectarray('AGF_CAT_BPF_FOREIGNCOMP', $option_categ, $selected_categ);
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfReportBPFCategForeignCompHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Cat Particulier
print '<tr class="impair"><td>' . $langs->trans("AgfReportBPFCategParticulier") . '</td>';
print '<td align="left">';
$option_categ = array ();
$selected_categ = array ();

$sql = ' SELECT rowid, label FROM ' . MAIN_DB_PREFIX . 'categorie WHERE type=' . $typecustomer . ' AND entity IN (' . getEntity('category', 1) . ')';
$resql = $db->query($sql);
if (! $resql) {
	setEventMessage($db->lasterror, 'errors');
} else {
	while ( $obj = $db->fetch_object($resql) ) {
		$option_categ[$obj->rowid] = $obj->label;
	}
}
if (! empty($conf->global->AGF_CAT_BPF_PARTICULIER)) {
	$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_PARTICULIER);
}

print $formAgefodd->agfmultiselectarray('AGF_CAT_BPF_PARTICULIER', $option_categ, $selected_categ);
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfReportBPFCategParticulierHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Cat Prestataire de formation
print '<tr class="pair"><td>' . $langs->trans("AgfReportBPFCategPresta") . '</td>';
print '<td align="left">';
$option_categ = array ();
$selected_categ = array ();

$sql = ' SELECT rowid, label FROM ' . MAIN_DB_PREFIX . 'categorie WHERE type=' . $typesupplier . ' AND entity IN (' . getEntity('category', 1) . ')';
$resql = $db->query($sql);
if (! $resql) {
	setEventMessage($db->lasterror, 'errors');
} else {
	while ( $obj = $db->fetch_object($resql) ) {
		$option_categ[$obj->rowid] = $obj->label;
	}
}
if (! empty($conf->global->AGF_CAT_BPF_PRESTA)) {
	$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_PRESTA);
}

print $formAgefodd->agfmultiselectarray('AGF_CAT_BPF_PRESTA', $option_categ, $selected_categ);
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfReportBPFCategPrestaHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Tool Pedagogique
print '<tr class="impair"><td>' . $langs->trans("AgfReportBPFCategToolPeda") . '</td>';
print '<td align="left">';
$option_categ = array ();
$selected_categ = array ();

$sql = ' SELECT rowid, label FROM ' . MAIN_DB_PREFIX . 'categorie WHERE type=' . $typeproduct . ' AND entity IN (' . getEntity('category', 1) . ')';
$resql = $db->query($sql);
if (! $resql) {
	setEventMessage($db->lasterror, 'errors');
} else {
	while ( $obj = $db->fetch_object($resql) ) {
		$option_categ[$obj->rowid] = $obj->label;
	}
}
if (! empty($conf->global->AGF_CAT_BPF_TOOLPEDA)) {
	$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_TOOLPEDA);
}

print $formAgefodd->agfmultiselectarray('AGF_CAT_BPF_TOOLPEDA', $option_categ, $selected_categ);
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfReportBPFCategToolPedaHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Prod Pedagogique
print '<tr class="pair"><td>' . $langs->trans("AgfReportBPFCategProdPeda") . '</td>';
print '<td align="left">';
$option_categ = array ();
$selected_categ = array ();

$sql = ' SELECT rowid, label FROM ' . MAIN_DB_PREFIX . 'categorie WHERE type=' . $typeproduct . ' AND entity IN (' . getEntity('category', 1) . ')';
$resql = $db->query($sql);
if (! $resql) {
	setEventMessage($db->lasterror, 'errors');
} else {
	while ( $obj = $db->fetch_object($resql) ) {
		$option_categ[$obj->rowid] = $obj->label;
	}
}
if (! empty($conf->global->AGF_CAT_BPF_PRODPEDA)) {
	$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_PRODPEDA);
}

print $formAgefodd->agfmultiselectarray('AGF_CAT_BPF_PRODPEDA', $option_categ, $selected_categ);
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfReportBPFCategProdPedaHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Honoraire
print '<tr class="pair"><td>' . $langs->trans("AgfReportBPFCategFeePresta") . '</td>';
print '<td align="left">';
$option_categ = array ();
$selected_categ = array ();

$sql = ' SELECT rowid, label FROM ' . MAIN_DB_PREFIX . 'categorie WHERE type=' . $typeproduct . ' AND entity IN (' . getEntity('category', 1) . ')';
$resql = $db->query($sql);
if (! $resql) {
	setEventMessage($db->lasterror, 'errors');
} else {
	while ( $obj = $db->fetch_object($resql) ) {
		$option_categ[$obj->rowid] = $obj->label;
	}
}
if (! empty($conf->global->AGF_CAT_BPF_FEEPRESTA)) {
	$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_FEEPRESTA);
}

print $formAgefodd->agfmultiselectarray('AGF_CAT_BPF_FEEPRESTA', $option_categ, $selected_categ);
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfReportBPFCategFeePrestaHelp"), 1, 'help');
print '</td>';
print '</tr>';



print '</table>';
print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '">';

print '</form>';




llxFooter();
$db->close();
