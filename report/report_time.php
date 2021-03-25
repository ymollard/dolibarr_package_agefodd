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
 * \file		/agefodd/report/report_time.php
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
require_once ('../class/report_time.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$caldt_st = dol_mktime(0, 0, 0, GETPOST('caldt_stmonth', 'int'), GETPOST('caldt_stday', 'int'), GETPOST('caldt_styear', 'int'));
$caldt_end = dol_mktime(23, 59, 59, GETPOST('caldt_endmonth', 'int'), GETPOST('caldt_endday', 'int'), GETPOST('caldt_endyear', 'int'));
$search_type_session = GETPOST("search_type_session", 'int');
$search_session_status = GETPOST("search_session_status", 'array');

$type_report = GETPOST("type_report", 'alpha');

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

llxHeader('', $langs->trans('AgfMenuReportTime'), '', '', '', '', $extrajs, $extracss);
$upload_dir = $conf->agefodd->dir_output . '/report/time/';

$agf = new Agsession($db);

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

$filter = array ();
if (! empty($caldt_st)) {
	$filter['secal.date_session']['start'] = $caldt_st;
}
if (! empty($caldt_end)) {
	$filter['secal.date_session']['end'] = $caldt_end;
}
if ($search_type_session != '' && $search_type_session != - 1) {
	$filter['s.type_session'] = $search_type_session;
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

		$report_time = new ReportTime($db, $outputlangs);
		$report_time->type_report=$type_report;
		$file_sub_title=$report_time->getSubTitlFileName($filter);
		$report_time->file = $upload_dir . 'reporttime-' . $file_sub_title . '.xlsx';


		$result = $report_time->write_file($filter);
		if ($result < 0) {
			setEventMessage($report_time->error, 'errors');
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

$report = new ReportTime($db, $langs);

$head = agf_report_time_prepare_head(http_build_query($_REQUEST));
dol_fiche_head($head, 'card', $langs->trans("AgfMenuReportTime"), 0, 'bill');

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="search_form">' . "\n";

print '<table class="border" width="100%">';

print '<tr>';
print '<td>' . $langs->trans('AgfReportTimeTypeReport').'</td>';
print '<td>';
print $form->selectarray('type_report', $report->TType_report, $type_report,0);
print '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('AgfReportTimeCalTime').'</td>';
print '<td>';
print $langs->trans('From').' ';
print $form->selectDate($caldt_st, "caldt_st", 0,0,1);
print $langs->trans('to').' ';
print $form->selectDate($caldt_end, "caldt_end", 0,0,1);
print '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('Type') . '</td>';
print '<td>' . $formAgefodd->select_type_session('search_type_session', $search_type_session, 1) . '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('AgfStatusSession') . '</td>';
print '<td>' . $formAgefodd->multiselect_session_status('search_session_status',$search_session_status,'t.active=1') . '</td>';
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
								         "file=/report/time/")
								   });
                        });
                    });
		</script>';

print '</form>' . "\n";

llxFooter();
$db->close();

