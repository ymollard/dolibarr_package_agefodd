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
 * \file		/agefodd/report/report_bpf.php
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
require_once ('../class/report_bpf.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$search_date_start= dol_mktime(0, 0, 0, GETPOST('search_date_startmonth', 'int'), GETPOST('search_date_startday', 'int'), GETPOST('search_date_startyear', 'int'));
$search_date_end= dol_mktime(0, 0, 0, GETPOST('search_date_endmonth', 'int'), GETPOST('search_date_endday', 'int'), GETPOST('search_date_endyear', 'int'));

$modelexport = GETPOST('modelexport', 'alpha');
$lang_id = GETPOST('lang_id', 'none');

$langs->load('agefodd@agefodd');
$langs->load('bills');
$langs->load("exports");


llxHeader('', $langs->trans('AgfMenuReportBPF'), '', '', '', '', $extrajs, $extracss);
$upload_dir = $conf->agefodd->dir_output . '/report/bpf/';


$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

$filter = array ();

if (! empty($search_date_start)) {
	$filter['search_date_start'] = $search_date_start;
}

if (! empty($search_date_end)) {
	$filter['search_date_end'] = $search_date_end;
}

/*
 * Actions
 */
if ($action == 'builddoc') {

	if (count($filter) > 0 && !empty($filter['search_date_start']) && !empty($filter['search_date_end'])) {

		$outputlangs = $langs;
		$newlang = $lang_id;
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->thirdparty->default_lang;
		if (! empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}

		$outputlangs->load('agefodd@agefodd');

		$report_bpf = new ReportBPF($db, $outputlangs);

		// $report_by_cust->file = $upload_dir . 'reportbycust-' . dol_print_date(dol_now(), 'dayhourlog') . '.xlsx';
		$file_sub_title = $report_bpf->getSubTitlFileName($filter);
		$report_bpf->file = $upload_dir . 'reportbpf-' . $file_sub_title . '.xlsx';

		$result = $report_bpf->write_file($filter);
		if ($result < 0) {
			setEventMessage($report_bpf->error, 'errors');
		} elseif ($result == 0) {
			setEventMessage($langs->trans("NoData"), 'warnings');
		} else {
			setEventMessage($langs->trans("FileSuccessfullyBuilt"));
		}
		if (count($report_bpf->warnings)>0) {
			setEventMessage($langs->trans("AgfReportBPFDataInconsistency"), 'errors');
			setEventMessages(null,$report_bpf->warnings, 'warnings');
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


print load_fiche_titre($langs->trans("AgfMenuReportBPF"));


print "<br>\n";

print ' <a href="'.dol_buildpath('/agefodd/report/report_bpf_help.php',1).'">'.$langs->trans('AgfMenuReportBPFHelp').'</a>';


print "<br>\n";

print "<br>\n";

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="search_form">' . "\n";

print '<table class="border" width="100%">';

if (empty($search_date_start)) {
	$search_date_start = dol_get_first_day(dol_print_date(dol_time_plus_duree(dol_now(), -1, 'y'),'%Y'));
}
if (empty($search_date_end)) {
	$search_date_end= dol_get_last_day(dol_print_date(dol_time_plus_duree(dol_now(), -1, 'y'),'%Y'));
}

print '<tr>';
print '<td>' . $langs->trans('From').'</td>';
print '<td>';
print $form->select_date($search_date_start,'search_date_start',0,0,0,'',1,1);
print '</td>';
print '</tr>';
print '<tr>';
print '<td>' . $langs->trans('to').'</td>';
print '<td>';
print $form->select_date($search_date_end,'search_date_end',0,0,0,'',1,1);
print '</td>';
print '</tr>';


print '</table>' . "\n";

$liste = array (
		'excel2007' => 'Excel 2007'
);
print $formfile->showdocuments('export', '', $upload_dir, $_SERVER["PHP_SELF"], $liste, 1, (! empty($modelexport) ? $modelexport : 'excel2007'), 1, 0, 0, 150, 1);

// TODO : Hack to update link on document form because merge export is always link to export ...
echo '<script type="text/javascript">
		jQuery(document).ready(function () {
                    	jQuery(function() {
                        	$("a[data-ajax|=\'false\'][href*=\'export\']")
								.each(function()
								   {
								      this.href = this.href.replace(/export/,
								         "agefodd");
									  this.href =this.href.replace(/file=/,
								         "file=/report/bpf/")
								   });
							});
							jQuery(function() {
							$(".documentdownload[href*=\'export\']")
								.each(function()
								   {
								      this.href = this.href.replace(/export/,
								         "agefodd");
									  this.href =this.href.replace(/file=/,
								         "file=/report/bpf/")
								   });
                        	});
                    });
		</script>';

print '</form>' . "\n";

llxFooter();
$db->close();

