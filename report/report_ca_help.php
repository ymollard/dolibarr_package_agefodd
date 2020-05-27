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
 * \file		/agefodd/report/report_ca_help.php
 * \brief		report part
 * (Agefodd).
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../lib/agefodd.lib.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();


$langs->load('agefodd@agefodd');
$langs->load('bills');
$langs->load("exports");

llxHeader('', $langs->trans('AgfMenuReportCA'), '', '', '', '', $extrajs, $extracss);


/*
 * View
 */

$head = agf_report_revenue_prepare_head(implode('&amp;', $_REQUEST));
dol_fiche_head($head, 'help', $langs->trans("AgfMenuReportCA"), 0, 'bill');


print '<h2>'.$langs->trans('AgfReportCAHelpAvailableFilters').'</h2>';

$TFilters = array('Year', 'Company', 'ParentCompany', 'AgfTypeRequester', 'SalesRepresentatives', 'Type', 'Status', 'AgfReportCASessionDetail');

print '<table class="centpercent">';

foreach($TFilters as $filterKey) {
	print '<tr><td class="titlefieldcreate">'.$langs->trans($filterKey).'</td><td>'.$langs->trans('AgfReportCAHelpFilter'.$filterKey).'</td></tr>';
}

print '</table>';

print '<p>'.img_warning().' '.$langs->trans('AgfReportCAHelpFilterWarning').'</p>';


print '<h2>'.$langs->trans('AgfReportCAHelpReportStructure').'</h2>';

$imghelp = '<img src="'.dol_buildpath('/agefodd/img/report_ca_help.png', 1).'"/>';

$TExplainations = array('HTHF', 'HT', 'Evolution', 'ByMonth', 'Totals', 'ByTrimester', 'Filters');
$i = 1;

print '<table class="centpercent">';

foreach($TExplainations as $explaination) {
	print '<tr>';
	if($i == 1) print '<td rowspan="'.count($TExplainations).'" style="width:2px;padding-right:10px">'.$imghelp.'</td>';
	print '<td style="width:30px">'.$i.'</td><td>'.$langs->trans('AgfReportCAHelpExplaination'.$explaination).'</td></tr>';
	$i++;
}

print '</table>';

llxFooter();
$db->close();

