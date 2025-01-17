<?php
/*
 * Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2012 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012 Marcos García <marcosgdf@gmail.com>
 * Copyright (C) 2013-2016 Florian Henry  <florian.henry@open-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/compta/facture/stats/index.php
 * \ingroup facture
 * \brief Page des stats factures
 */
$res = @include ("../../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once (DOL_DOCUMENT_ROOT . "/core/class/dolgraph.class.php");
dol_include_once("/agefodd/class/sessionstats.class.php");
dol_include_once("/agefodd/class/html.formagefodd.class.php");

$WIDTH = 500;
$HEIGHT = 200;

$userid = GETPOST('userid', 'int');
if ($userid < 0)
	$userid = 0;
$socid = GETPOST('socid', 'int');
if ($socid < 0)
	$socid = 0;
$training_id = GETPOST('training_id', 'int');
if ($training_id < 0)
	$training_id = 0;

if (empty($userid))
	unset($userid);

	// Security check
if ($user->societe_id > 0) {
	$action = '';
	$socid = $user->societe_id;
}

$nowyear = strftime("%Y", dol_now());
$year = GETPOST('year', 'none') > 0 ? GETPOST('year', 'none') : $nowyear;
// $startyear=$year-2;
$startyear = $year - 1;
$endyear = $year;

/*
 * View
*/

$langs->load("agefodd@agefodd");

$form = new Form($db);
$formagf = new FormAgefodd($db);

$title = $langs->trans("AgfSessionStatistics");

llxHeader('', $title);

$dir = $conf->agefodd->dir_temp;

print_fiche_titre($title, $mesg);

dol_mkdir($dir);

$stats = new SessionStats($db, $socid, $mode, ($userid > 0 ? $userid : 0), ($training_id > 0 ? $training_id : 0));

// Build graphic number of object
// $data = array(array('Lib',val1,val2,val3),...)
$data = $stats->getNbByMonthWithPrevYear($endyear, $startyear);

// dol_syslog(var_export($data,true));

$filenamenb = $dir . "/sessionsnbinyear-" . $year . ".png";

$px1 = new DolGraph();
$mesg = $px1->isGraphKo();
if (! $mesg) {
	$px1->SetData($data);
	if(floatval(DOL_VERSION) <= 11.0) $px1->SetPrecisionY(0);
	$i = $startyear;
	$legend = array ();
	while ( $i <= $endyear ) {
		$legend [] = $i;
		$i ++;
	}
	$px1->SetLegend($legend);
	$px1->SetMaxValue($px1->GetCeilMaxValue());
	$px1->SetWidth($WIDTH);
	$px1->SetHeight($HEIGHT);
	$px1->SetYLabel($langs->trans("AgfNumberOfSessions"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	if(floatval(DOL_VERSION) <= 11.0) $px1->SetPrecisionY(0);
	$px1->mode = 'depth';
	$px1->SetTitle($langs->trans("AgfNumberOfSessionsByMonth"));

	$px1->draw($filenamenb);
} else {
	setEventMessage($mesg, 'errors');
}

// Build graphic amount of object
$data = $stats->getAmountByMonthWithPrevYear($endyear, $startyear);
// var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

$filenameamount = $dir . "/sessionsamountinyear-" . $year . ".png";
if ($mode == 'customer')
	$fileurlamount = DOL_URL_ROOT . '/viewimage.php?modulepart=billstats&amp;file=sessionsamountinyear-' . $year . '.png';
if ($mode == 'supplier')
	$fileurlamount = DOL_URL_ROOT . '/viewimage.php?modulepart=billstatssupplier&amp;file=sessionsamountinyear-' . $year . '.png';

$px2 = new DolGraph();
$mesg = $px2->isGraphKo();
if (! $mesg) {
	$px2->SetData($data);
	$i = $startyear;
	$legend = array ();
	while ( $i <= $endyear ) {
		$legend [] = $i;
		$i ++;
	}
	$px2->SetLegend($legend);
	$px2->SetMaxValue($px2->GetCeilMaxValue());
	$px2->SetMinValue(min(0, $px2->GetFloorMinValue()));
	$px2->SetWidth($WIDTH);
	$px2->SetHeight($HEIGHT);
	$px2->SetYLabel($langs->trans("AmountOfTrainings"));
	$px2->SetShading(3);
	$px2->SetHorizTickIncrement(1);
	if(floatval(DOL_VERSION) <= 11.0) $px2->SetPrecisionY(0);
	$px2->mode = 'depth';

	$px2->SetTitle($langs->trans("AmountOfSessionsByMonthHT"));

	$px2->draw($filenameamount);
}

$data = $stats->getAverageByMonthWithPrevYear($endyear, $startyear);

$filename_avg = $dir . "/sessionsavg-" . $year . ".png";

$px3 = new DolGraph();
$mesg = $px3->isGraphKo();
if (! $mesg) {
	$px3->SetData($data);
	$i = $startyear;
	$legend = array ();
	while ( $i <= $endyear ) {
		$legend [] = $i;
		$i ++;
	}
	$px3->SetLegend($legend);
	$px3->SetYLabel($langs->trans("AmountAverage"));
	$px3->SetMaxValue($px3->GetCeilMaxValue());
	$px3->SetMinValue($px3->GetFloorMinValue());
	$px3->SetWidth($WIDTH);
	$px3->SetHeight($HEIGHT);
	$px3->SetShading(3);
	$px3->SetHorizTickIncrement(1);
	if(floatval(DOL_VERSION) <= 11.0) $px3->SetPrecisionY(0);
	$px3->mode = 'depth';
	$px3->SetTitle($langs->trans("AmountAverage"));

	$px3->draw($filename_avg);
}

// Show array
$data = $stats->getAllByYear();
$arrayyears = array ();
foreach ( $data as $val ) {
	$arrayyears [$val ['year']] = $val ['year'];
}
if (! count($arrayyears))
	$arrayyears [$nowyear] = $nowyear;

$h = 0;
$head = array ();
$head [$h] [0] = dol_buildpath('/agefodd/session/stats/index.php', 1);
$head [$h] [1] = $langs->trans("ByMonthYear");
$head [$h] [2] = 'byyear';
$h ++;

complete_head_from_modules($conf, $langs, $object, $head, $h, $type);

dol_fiche_head($head, 'byyear', $langs->trans("Statistics"));

if (empty($socid)) {
	print '<table class="notopnoleftnopadd" width="100%"><tr>';
	print '<td align="center" valign="top">';

	// Show filter box
	print '<form name="stats" method="POST" action="' . $_SERVER ["PHP_SELF"] . '">';
	print '<input type="hidden" name="mode" value="' . $mode . '">';
	print '<table class="border" width="100%">';
	print '<tr><td class="liste_titre" colspan="2">' . $langs->trans("Filter") . '</td></tr>';
	// Company
	print '<tr><td>' . $langs->trans("ThirdParty") . '</td><td>';
	if ($mode == 'customer') {
		$filter = 's.client in (1,2,3)';
	}
	if ($mode == 'supplier') {
		$filter = 's.fournisseur = 1';
	}
	print $form->select_thirdparty_list($socid, 'socid', $filter, 'SelectThirdParty');
	print '</td></tr>';
	// User
	print '<tr><td>' . $langs->trans("User") . '/' . $langs->trans("SalesRepresentative") . '</td><td>';
	print $form->select_users($userid, 'userid', 1);
	print '</td></tr>';

	// Formation$
	print '<tr><td>' . $langs->trans("AgfTraining") . '</td><td>';
	print $formagf->select_formation($training_id, 'training_id', 'intitule', 1);
	print '</td></tr>';

	// Year
	print '<tr><td>' . $langs->trans("Year") . '</td><td>';
	if (! in_array($year, $arrayyears))
		$arrayyears [$year] = $year;
	arsort($arrayyears);
	print $form->selectarray('year', $arrayyears, $year, 0);
	print '</td></tr>';
	print '<tr><td align="center" colspan="2"><input type="submit" name="submit" class="button" value="' . $langs->trans("Refresh") . '"></td></tr>';
	print '</table>';
	print '</form>';
	print '<br><br>';
}

print '<table class="border" width="100%">';
print '<tr height="24">';
print '<td align="center">' . $langs->trans("Year") . '</td>';
print '<td align="center">' . $langs->trans("AgfNumberOfSessions") . '</td>';
print '<td align="center">' . $langs->trans("AmountTotal") . '</td>';
print '<td align="center">' . $langs->trans("AmountAverage") . '</td>';
print '</tr>';

$oldyear = 0;
foreach ( $data as $val ) {
	$year = $val ['year'];
	while ( $year && $oldyear > $year + 1 ) { // If we have empty year
		$oldyear --;
		print '<tr height="24">';
		print '<td align="center"><a href="' . $_SERVER ["PHP_SELF"] . '?year=' . $oldyear . '&amp;mode=' . $mode . '">' . $oldyear . '</a></td>';
		print '<td align="right">0</td>';
		print '<td align="right">0</td>';
		print '<td align="right">0</td>';
		print '</tr>';
	}
	print '<tr height="24">';
	print '<td align="center"><a href="' . $_SERVER ["PHP_SELF"] . '?year=' . $year . '&amp;mode=' . $mode . '">' . $year . '</a></td>';
	print '<td align="right">' . $val ['nb'] . '</td>';
	print '<td align="right">' . price(price2num($val ['total'], 'MT'), 1) . '</td>';
	print '<td align="right">' . price(price2num($val ['avg'], 'MT'), 1) . '</td>';
	print '</tr>';
	$oldyear = $year;
}

print '</table>';

print '</td>';
print '<td align="center" valign="top">';

// Show graphs
print '<table class="border" width="100%"><tr valign="top"><td align="center">';
if ($mesg) {
	print $mesg;
} else {
	print $px1->show();
	print "<br>\n";
	print $px2->show();
	print "<br>\n";
	print $px3->show();
}
print '</td></tr></table>';

print '</td></tr></table>';

dol_fiche_end();

llxFooter();
$db->close();
