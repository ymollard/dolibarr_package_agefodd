<?php
/*
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
 * \file		/agefodd/report/report_by_customer.php
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
require_once ('../class/report_by_customer.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$invoice_dt_st = dol_mktime(0, 0, 0, GETPOST('invoice_dt_stmonth', 'int'), GETPOST('invoice_dt_stday', 'int'), GETPOST('invoice_dt_styear', 'int'));
$invoice_dt_end = dol_mktime(23, 59, 59, GETPOST('invoice_dt_endmonth', 'int'), GETPOST('invoice_dt_endday', 'int'), GETPOST('invoice_dt_endyear', 'int'));
$session_dt_st = dol_mktime(0, 0, 0, GETPOST('session_dt_stmonth', 'int'), GETPOST('session_dt_stday', 'int'), GETPOST('session_dt_styear', 'int'));
$session_dt_end = dol_mktime(23, 59, 59, GETPOST('session_dt_endmonth', 'int'), GETPOST('session_dt_endday', 'int'), GETPOST('session_dt_endyear', 'int'));

$search_sale = GETPOST('search_sale', 'int');
$ts_logistique = GETPOST('options_ts_logistique', 'int');
$search_type_session = GETPOST("search_type_session", 'int');
$search_parent = GETPOST('search_parent', 'int');
if ($search_parent == - 1)
	$search_parent = '';
$search_soc_requester = GETPOST('search_soc_requester', 'none');
$search_soc = GETPOST("search_soc", 'none');
$search_session_status=GETPOST('search_session_status','array');
$avoidNotLinked = GETPOST('avoidNotLinked', 'none');

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

llxHeader('', $langs->trans('AgfMenuReportByCustomer'), '', '', '', '', $extrajs, $extracss);
$upload_dir = $conf->agefodd->dir_output . '/report/bycust/';

$agf = new Agsession($db);

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

$filter = array ();
if (! empty($invoice_dt_st)) {
	$filter['f.datef']['start'] = $invoice_dt_st;
}
if (! empty($invoice_dt_end)) {
	$filter['f.datef']['end'] = $invoice_dt_end;
}
if (! empty($session_dt_st)) {
	$filter['sesscal.date_session']['start'] = $session_dt_st;
}
if (! empty($session_dt_end)) {
	$filter['sesscal.date_session']['end'] = $session_dt_end;
}
if (! empty($search_sale)) {
	$filter['sale.fk_user_com'] = $search_sale;
}
if ($search_type_session != '' && $search_type_session != - 1) {
	$filter['s.type_session'] = $search_type_session;
}
if (! empty($ts_logistique)) {
	$filter['extra.ts_logistique'] = $ts_logistique;
}
if (! empty($search_parent)) {
	$filter['so.parent|sorequester.parent'] = $search_parent;
}
if (! empty($search_soc)) {
	$filter['so.nom'] = $search_soc;
}
if (! empty($search_soc_requester)) {
	$filter['socrequester.nom'] = $search_soc_requester;
}
if (! empty($search_session_status) && count($search_session_status)>0) {
	$filter['s.status'] = $search_session_status;
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

	$report_by_cust = new ReportByCustomer($db, $outputlangs);

	if(!empty($avoidNotLinked)) $report_by_cust->avoidNotLinkedInvoices = 1;

	$file_sub_title=$report_by_cust->getSubTitlFileName($filter);
	$report_by_cust->file = $upload_dir . 'reportbycust-' . $file_sub_title . '.xlsx';

	$result = $report_by_cust->write_file($filter);
	if ($result < 0) {
		setEventMessage($report_by_cust->error, 'errors');
	} elseif ($result == 0) {
		setEventMessage($langs->trans("NoData"), 'warnings');
	} else {
		setEventMessage($langs->trans("FileSuccessfullyBuilt"));
	}
	} else {
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


$head = agf_report_by_customer_prepare_head(http_build_query($_REQUEST));
dol_fiche_head($head, 'AgfMenuReportByCustomer', $langs->trans("AgfMenuReportByCustomer"), 0, 'bill');

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="search_form">' . "\n";

print '<table class="border" width="100%">';

$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($agf->table_element, true);
if (is_array($extralabels) && key_exists('ts_logistique', $extralabels)) {
	print '<tr>';
	print '<td width="15%">' . $extralabels['ts_logistique'] . '</td>';
	print '<td>' . $extrafields->showInputField('ts_logistique', $ts_logistique) . '</td>';
	print '</tr>';
}

print '<tr>';
print '<td>' . $langs->trans('InvoiceCustomer').'</td>';
print '<td>';
print $langs->trans("AgfDateDebut") . ':'.$form->select_date($invoice_dt_st, 'invoice_dt_st',0,0,1,'search_form',1,1,1);
print ' ' . $langs->trans("AgfDateFin") . ' ' . $form->select_date($invoice_dt_end, 'invoice_dt_end',0,0,1,'search_form',1,1,1);
print '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('AgfSessionDetail') . '</td>';
print '<td>';
print $langs->trans("AgfDateDebut") . ':'.$form->select_date($session_dt_st, 'session_dt_st',0,0,1,'search_form',1,1,1);
print ' ' . $langs->trans("AgfDateFin") . ' ' . $form->select_date($session_dt_end, 'session_dt_end',0,0,1,'search_form',1,1,1);
print '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('Company') . '</td>';
print '<td><input type="text" class="flat" name="search_soc" value="' . $search_soc . '" size="20"></td>';
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
print '<td>' . $form->select_company($search_parent, 'search_parent', $filter, 1) . '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('AgfTypeRequester') . '</td>';
print '<td><input type="text" class="flat" name="search_soc_requester" value="' . $search_soc_requester . '" size="20"></td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('SalesRepresentatives') . '</td>';
print '<td>' . $formother->select_salesrepresentatives($search_sale, 'search_sale', $user) . '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('Type') . '</td>';
print '<td>' . $formAgefodd->select_type_session('search_type_session', $search_type_session, 1) . '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('AgfStatusSession') . '</td>';
print '<td>' . $formAgefodd->multiselect_session_status('search_session_status',$search_session_status,'t.active=1') . '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('AgfNotLinkedInvoices') . '</td>';
print '<td>';
print '&nbsp;<input type="checkbox" name="avoidNotLinked" id="avoidNotLinked" '.(!empty($avoidNotLinked) ? 'checked' : '').'>&nbsp;<label for="avoidNotLinked">' . $langs->trans('AgfAvoidNotLinkedInvoices') . '</label>';
print '</td>';
print '</tr>';

print '</table>' . "\n";

$liste = array (
		'excel2007' => 'Excel 2007'
);
dol_fiche_end();
print $formfile->showdocuments('export', '', $upload_dir, $_SERVER["PHP_SELF"], $liste, 1, (! empty($modelexport) ? $modelexport : 'excel2007'), 1, 0, 0, 150, 1);

// TODO : Hack to update link on document beacuse merge unpaid is always link to unpaid invoice ...
echo '<script type="text/javascript">
		jQuery(document).ready(function () {
                    	jQuery(function() {
                        	$("a[data-ajax|=\'false\'][href*=\'export\']")
								.each(function()
								   {
								      this.href = this.href.replace(/export/,
								         "agefodd");
									  this.href =this.href.replace(/file=/,
								         "file=/report/bycust/")
								   });
							});
							jQuery(function() {
							$(".documentdownload[href*=\'export\']")
								.each(function()
								   {
								      this.href = this.href.replace(/export/,
								         "agefodd");
									  this.href =this.href.replace(/file=/,
								         "file=/report/bycust/")
								   });
                        	});
                    });
		</script>';
print '</form>' . "\n";

llxFooter();
$db->close();

