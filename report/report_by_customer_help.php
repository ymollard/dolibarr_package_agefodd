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
 * \file		/agefodd/report/report_by_customer_help.php
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

$head = agf_report_by_customer_prepare_head(implode('&amp;', $_REQUEST));
dol_fiche_head($head, 'help', $langs->trans("AgfMenuReportByCustomer"), 0, 'bill');


print '<h2>'.$langs->trans('AgfReportCAHelpAvailableFilters').'</h2>';

$TFilters = array('AgfRptInvoice', 'AgfRptSession', 'Company', 'ParentCompany', 'AgfTypeRequester', 'SalesRepresentatives', 'Type', 'Status');

print '<table class="centpercent">';

foreach($TFilters as $filterKey) {
	print '<tr><td class="titlefieldcreate">'.$langs->trans($filterKey).'</td><td>'.$langs->trans('AgfReportUserHelpFilter'.$filterKey).'</td></tr>';
}

print '</table>';



print '<h2>'.$langs->trans('AgfReportUserHelpReportStructure').'</h2>';

$imghelp = '<img  src="'.dol_buildpath('/agefodd/img/report_by_customer_help.png', 1).'"/>';

$TExplainations = array(1 => 'LineExported', 2 =>'ParticipantList', 3 => 'CaTotals', 4 => 'CaTotalsSum', 5 => 'Recap');
$i = 1;

print $imghelp;

print '<fieldset><legend>'.$langs->trans('Legend').'</legend>';
print '<table class="centpercent">';

foreach($TExplainations as $i => $explaination) {
	print '<tr>';
	print '<td style="width:30px" valign="top" >'.$i.'</td><td>'.$langs->trans('AgfReportUserHelpExplaination'.$explaination).'</td></tr>';

}

print '</table>';
print '</fieldset>';

llxFooter();
$db->close();

