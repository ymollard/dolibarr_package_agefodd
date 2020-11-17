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
 * \file		/agefodd/report/report_ca_ventilated_help.php
 * \brief		report part
 * (Agefodd).
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../lib/agefodd.lib.php');
require_once ('../class/report_time.class.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();


$langs->load('agefodd@agefodd');
$langs->load('bills');
$langs->load("exports");

llxHeader('', $langs->trans('AgfMenuReportTime'), '', '', '', '', $extrajs, $extracss);


/*
 * View
 */

$head = agf_report_time_prepare_head(implode('&amp;', $_REQUEST));
dol_fiche_head($head, 'help', $langs->trans("AgfMenuReportTime"), 0, 'bill');


print '<h2>'.$langs->trans('AgfReportTimeHelpAvailableFilters').'</h2>';

$TFilters = array('AgfReportTimeTypeReport', 'AgfReportTimeCalTime', 'Type', 'AgfStatusSession');

print '<table class="centpercent">';

foreach($TFilters as $filterKey) {
	print '<tr><td class="titlefieldcreate">'.$langs->trans($filterKey).'</td><td>'.$langs->trans('AgfReportTimeHelpFilter'.$filterKey).'</td></tr>';
}

print '</table>';

print '<h2>'.$langs->trans('AgfReportTimeHelpReportStructure').'</h2>';

$rptTime = new ReportTime($db,$langs);
$columnHeader=$rptTime->getArrayColumnHeader();
if (is_array($columnHeader) && count($columnHeader)>0) {
	foreach ($columnHeader as $typeReport=>$dataCol) {
		print '<h3>'.$langs->trans($rptTime->TType_report[$typeReport]).'</h3>';
		print '<ul>';
		foreach ($dataCol as $colInfo) {
			print '<li>' . $langs->trans($colInfo['title']) . '</li>';
		}
		print '</ul>';
	}
}

print '</table>';

llxFooter();
$db->close();

