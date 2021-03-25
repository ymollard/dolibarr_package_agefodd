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
 * \file		/agefodd/report/report_calendar_by_customer.php
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
require_once ('../class/report_calendar_by_customer.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$session_dt_st = dol_mktime(0, 0, 0, GETPOST('session_dt_stmonth', 'int'), GETPOST('session_dt_stday', 'int'), GETPOST('session_dt_styear', 'int'));
$session_dt_end = dol_mktime(0, 0, 0, GETPOST('session_dt_endmonth', 'int'), GETPOST('session_dt_endday', 'int'), GETPOST('session_dt_endyear', 'int'));

$search_soc = GETPOST("search_soc", 'none');

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

llxHeader('', $langs->trans('AgfMenuReportCalendarByCustomer'), '', '', '', '', $extrajs, $extracss);
$upload_dir = $conf->agefodd->dir_output . '/report/calendarbycust/';

$agf = new Agsession($db);

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

$filter = array ();
if (! empty($session_dt_st)) {
	$filter['sesscal.date_session']['start'] = $session_dt_st;
}
if (! empty($session_dt_end)) {
	$filter['sesscal.date_session']['end'] = $session_dt_end;
}
if (! empty($search_soc)) {
	$filter['so.rowid'] = $search_soc;
}

/*
 * Actions
 */
if ($action == 'builddoc') {

	if (count($filter)>0) {
	    if (empty($filter['so.rowid']))
	    {
	        setEventMessage($langs->trans("AgfRptSelectACustomer"), 'errors');
	    }
	    else
	    {
        	$outputlangs = $langs;
        	$newlang = $lang_id;
        	if ($conf->global->MAIN_MULTILANGS && empty($newlang))
        		$newlang = $object->client->default_lang;
        	if (! empty($newlang)) {
        		$outputlangs = new Translate("", $conf);
        		$outputlangs->setDefaultLang($newlang);
        	}

        	$outputlangs->load('agefodd@agefodd');

        	$report_calendar_by_cust = new ReportCalendarByCustomer($db, $outputlangs);

        	if (empty($session_dt_end)) $filter['sesscal.date_session']['end'] = dol_now();

        	$file_sub_title=$report_calendar_by_cust->getSubTitlFileName($filter);
        	$report_calendar_by_cust->file = $upload_dir . 'reportcalendarbycust-' . $file_sub_title . '.xlsx';

        	$result = $report_calendar_by_cust->write_file($filter);
        	if ($result < 0) {
        		setEventMessage($report_calendar_by_cust->error, 'errors');
        	} elseif ($result == 0) {
        		setEventMessage($langs->trans("NoData"), 'warnings');
        	} else {
        		setEventMessage($langs->trans("FileSuccessfullyBuilt"));
        	}
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


$head = agf_report_calendar_by_customer_prepare_head(http_build_query($_REQUEST));
dol_fiche_head($head, 'AgfMenuReportCalendarByCustomer', $langs->trans("AgfMenuReportCalendarByCustomer"), 0, 'bill');

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="search_form">' . "\n";

print '<table class="border" width="100%">';

print '<tr>';
print '<td class="fieldrequired">' . $langs->trans('Company') . '</td>';
print '<td>' . $form->select_company($search_soc, 'search_soc', '', 1) . '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('AgfSessionDetail') . '</td>';
print '<td>';
print $langs->trans("AgfDateDebut") . ':'.$form->select_date($session_dt_st, 'session_dt_st',0,0,1,'search_form',1,1,1);
print ' ' . $langs->trans("AgfDateFin") . ' ' . $form->select_date($session_dt_end, 'session_dt_end',0,0,1,'search_form',1,1,1);
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
								         "file=/report/calendarbycust/")
								   });
							});
							jQuery(function() {
							$(".documentdownload[href*=\'export\']")
								.each(function()
								   {
								      this.href = this.href.replace(/export/,
								         "agefodd");
									  this.href =this.href.replace(/file=/,
								         "file=/report/calendarbycust/")
								   });
                        	});
                    });
		</script>';
print '</form>' . "\n";

llxFooter();
$db->close();

