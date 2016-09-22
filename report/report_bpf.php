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

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$search_year = GETPOST('search_year');

$modelexport = GETPOST('modelexport', 'alpha');
$lang_id = GETPOST('lang_id');

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

if (! empty($search_year)) {
	$filter['search_year'] = $search_year;
}

/*
 * Actions
 */
if ($action == 'builddoc') {

	if (count($filter) > 0 && !empty($filter['search_year'])) {

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
	} else {
		setEventMessage($langs->trans("AgfRptSelectAtLeastOneCriteria"), 'errors');
	}
} elseif ($action == 'remove_file') {

	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

	$langs->load("other");
	$file = $upload_dir . '/' . GETPOST('file');
	$ret = dol_delete_file($file, 0, 0, 0, '');
	if ($ret)
		setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
	else
		setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
	$action = '';
}

dol_fiche_head($head, 'AgfMenuReportBPF', $langs->trans("AgfMenuReportBPF"), 0, 'bill');

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="search_form">' . "\n";

print '<table class="border" width="100%">';

print '<tr>';
print '<td>' . $langs->trans('Year').'</td>';
print '<td>';
print $formother->selectyear($search_year ? $search_year : - 1, 'search_year', 1, 15, 0);
print '</td>';
print '</tr>';


print '</table>' . "\n";

$liste = array (
		'excel2007' => 'Excel 2007'
);
$formfile->show_documents('export', '', $upload_dir, $_SERVER["PHP_SELF"], $liste, 1, (! empty($modelexport) ? $modelexport : 'excel2007'), 1, 0, 0, 150, 1);

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
                    });
		</script>';

print '</form>' . "\n";

llxFooter();
$db->close();

