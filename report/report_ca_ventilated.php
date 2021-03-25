<?php
/*
 * Copyright (C) 2012-2020 Grégory Blémand <gregory.blemand@atm-consulting.fr>
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
 * \file		/agefodd/report/report_ca_ventilated.php
 * \brief		report part
 * (Agefodd).
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agsession.class.php');
require_once ('../lib/agefodd.lib.php');
require_once ('../class/html.formagefodd.class.php');
require_once ('../class/agefodd_formateur.class.php');
require_once ('../class/report_ca_ventilated.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$action = GETPOST('action', 'alpha');

// Search filters
$date_start = dol_mktime(0, 0, 0, GETPOST('date_startmonth', 'int'), GETPOST('date_startday', 'int'), GETPOST('date_startyear', 'int'));;
$date_end = dol_mktime(23, 59, 59, GETPOST('date_endmonth', 'int'), GETPOST('date_endday', 'int'), GETPOST('date_endyear', 'int'));;
$search_soc = GETPOST("search_soc", 'none'); // client de la session
$search_soc_requester = GETPOST('search_soc_requester', 'none'); // demandeur
$search_soc_buyer=GETPOST('search_soc_buyer', 'none'); // tiers payeur
$search_sale = GETPOST('search_sale', 'array');
$search_parent = GETPOST('search_parent', 'array');
if ($search_parent == - 1)
	$search_parent = '';

//$search_invoice_status = GETPOST('search_invoice_status', 'none');

//$ts_logistique = GETPOST('options_ts_logistique', 'int');
//$search_session_status=GETPOST('search_session_status','array');

$modelexport = GETPOST('modelexport', 'alpha');
$lang_id = GETPOST('lang_id', 'none');

$langs->load('agefodd@agefodd');
$langs->load('bills');
$langs->load("exports");

$extrajs = array (
	'/agefodd/includes/multiselect/js/ui.multiselect.js'
);
$extracss = array (
	'/agefodd/includes/multiselect/css/ui.multiselect.css',
	'/agefodd/css/agefodd.css'
);

llxHeader('', $langs->trans('AgfMenuReportCAVentilated'), '', '', '', '', $extrajs, $extracss);
$upload_dir = $conf->agefodd->dir_output . '/report/ca_ventilated/';
if (!is_dir($upload_dir)) dol_mkdir($upload_dir);

$agf = new Agsession($db);

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

$filter = array ();
if (! empty($date_start)) {
	$filter['f.datef']['start'] = $date_start;
}

if (! empty($date_end)) {
	$filter['f.datef']['end'] = $date_end;
}

// client session
if (! empty($search_soc)) {
	$filter['sessclient.nom'] = $search_soc;
}

// demandeur
if (! empty($search_soc_requester)) {
	$filter['socrequester.nom'] = $search_soc_requester;
}

// payeur
if (! empty($search_soc_buyer)) {
	$filter['so.nom'] = $search_soc_buyer;
}

// maisons mere
if (! empty($search_parent)) {
	$filter['so.parent|sorequester.parent'] = $search_parent;
}

if (! empty($search_sale)) {
	$filter['sale.fk_user'] = $search_sale;
}


/*
 * Actions
 */
if ($action == 'builddoc') {

	if (count($filter)>0) {

		$outputlangs = $langs;
		$newlang = $lang_id;
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->client->default_lang;
		if (! empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}

		$outputlangs->load('agefodd@agefodd');

		$report_ca = new ReportCAVentilated($db, $outputlangs);

		//$report_by_cust->file = $upload_dir . 'reportbycust-' . dol_print_date(dol_now(), 'dayhourlog') . '.xlsx';
		$file_sub_title=$report_ca->getSubTitlFileName($filter);
		$report_ca->file = $upload_dir . 'reportcaventilated-' . $file_sub_title . '.xlsx';


		$result = $report_ca->write_file($filter);
		if ($result < 0) {
			setEventMessage($report_ca->error, 'errors');
		} elseif ($result == 0) {
			setEventMessage($langs->trans("NoData"), 'warnings');
		} else {
			setEventMessage($langs->trans("FileSuccessfullyBuilt"));
		}
	} else {
		$langs->load('errors');
		setEventMessage($langs->trans("AgfRptSelectAtLeastOneCriteria"), 'errors');
	}
} elseif ($action == 'remove_file') {

	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

	$langs->load("other");
	$file = $upload_dir . '/' . GETPOST('file', 'none');
	$ret = dol_delete_file($file, 0, 0, 0, '');
	if ($ret)
		setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile', 'none')));
	else
		setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile', 'none')), 'errors');
	$action = '';
}

$report = new ReportCAVentilated($db, $langs);

$head = agf_report_revenue_ventilated_prepare_head();
dol_fiche_head($head, 'card', $langs->trans("AgfMenuReportCAVentilated"), 0, 'bill');

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="search_form">' . "\n";

print '<table class="border" width="100%">';

print '<tr>';
print '<td>' . $langs->trans('DateInvoice').'</td>';
print '<td>';
print $langs->trans('From').' ';
print $form->selectDate($date_start, "date_start", 0,0,1);
print $langs->trans('to').' ';
print $form->selectDate($date_end, "date_end", 0,0,1);
print '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('AgfSessionCustomer') . '</td>';
print '<td><input type="text" class="flat" name="search_soc" value="' . $search_soc . '" size="20"></td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('AgfTypeRequester') . '</td>';
print '<td><input type="text" class="flat" name="search_soc_requester" value="' . $search_soc_requester . '" size="20"></td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('AgfSessionInvoicedThirdparty') . '</td>';
print '<td><input type="text" class="flat" name="search_soc_buyer" value="' . $search_soc_buyer . '" size="20"></td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('ParentCompany') . '</td>';
$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label('thirdparty');
if (is_array($extrafields->attributes['societe']) && array_key_exists('ts_maison',$extrafields->attributes['societe']['type'])) {

	$filter='extra.ts_maison=1';
} else {
	$filter='';
}
print '<td>';
$TCompaniesTmp = $form->select_thirdparty_list("", "socid", $filter, "", 0, 0, array(), "", 1);

$TCompaniesMere = array();
if (!empty($TCompaniesTmp))
{
	foreach ($TCompaniesTmp as $mere)
	{
		$TCompaniesMere[$mere['key']] = $mere['label'];
	}
}
print $form->multiselectarray("search_parent", $TCompaniesMere, $search_parent);
print '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('SalesRepresentatives') . '</td>';
$TabSales = getMultiSalesRepresentative();
print '<td>' . $form->multiselectarray('search_sale', $TabSales, $search_sale) .'</td>';
print '</tr>';

print '</table>' . "\n";
dol_fiche_end();
$liste = array (
	'excel2007' => 'Excel 2007'
);

print $formfile->showdocuments('export', '', $upload_dir, $_SERVER["PHP_SELF"], $liste, 1, (! empty($modelexport) ? $modelexport : 'excel2007'), 1, 0, 0, 150, 1);

// TODO : Hack to update link on document form because merge export is always link to export ...
echo '<script type="text/javascript">
		jQuery(document).ready(function () {
                    	jQuery(function() {
                        	$("a[data-ajax|=\'false\'][href*=\'export\'],a.documentdownload[href*=\'export\']") // data-ajax="false" before Dolibarr 6.0, class="documentdownload" after
								.each(function()
								   {
								      this.href = this.href.replace(/export/,
								         "agefodd");
									  this.href =this.href.replace(/file=/,
								         "file=/report/ca_ventilated/")
								   });
                        });
                    });
		</script>';

print '</form>' . "\n";

llxFooter();
$db->close();


function getMultiSalesRepresentative()
{
	global $db, $conf, $user;

	$Tab = array();

	$sql_usr = "SELECT u.rowid, u.lastname, u.firstname, u.statut, u.login";
	$sql_usr.= " FROM ".MAIN_DB_PREFIX."user as u";
	$sql_usr.= " WHERE u.entity IN (".getEntity('user').")";
	if (empty($user->rights->user->user->lire)) $sql_usr.=" AND u.rowid = ".$user->id;
	if (! empty($user->societe_id)) $sql_usr.=" AND u.fk_soc = ".$user->societe_id;
	// Add existing sales representatives of thirdparty of external user
	if (empty($user->rights->user->user->lire) && $user->societe_id)
	{
		$sql_usr.=" UNION ";
		$sql_usr.= "SELECT u2.rowid, u2.lastname, u2.firstname, u2.statut, u2.login";
		$sql_usr.= " FROM ".MAIN_DB_PREFIX."user as u2, ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql_usr.= " WHERE u2.entity IN (0,".$conf->entity.")";
		$sql_usr.= " AND u2.rowid = sc.fk_user AND sc.fk_soc=".$user->societe_id;
	}
	$sql_usr.= " ORDER BY statut DESC, lastname ASC";  // Do not use 'ORDER BY u.statut' here, not compatible with the UNION.
	//print $sql_usr;exit;

	$resql_usr = $db->query($sql_usr);
	if ($resql_usr)
	{
		while ($obj = $db->fetch_object($resql_usr))
		{
			$Tab[$obj->rowid] = dolGetFirstLastname($obj->firstname, $obj->lastname);
		}
	}

	return $Tab;
}
