<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2017	Florian Henry	<florian.henry@open-concept.pro>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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
 * \file /agefodd/admin/admin_options.php
 * \ingroup agefodd
 * \brief agefood module setup page
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res) $res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res) die("Include of main fails");

require_once '../class/agefodd_formation_catalogue.class.php';
require_once '../class/agefodd_session_admlevel.class.php';
require_once '../class/agefodd_calendrier.class.php';
require_once '../class/html.formagefodd.class.php';
require_once '../lib/agefodd.lib.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("admin");
$langs->load('agefodd@agefodd');

if (! $user->rights->agefodd->admin && ! $user->admin)
    accessforbidden();

$action = GETPOST('action', 'alpha');

if ($action == 'sessioncalendar_create') {
    $tmpl_calendar = new Agefoddcalendrier($db);
    $tmpl_calendar->day_session = GETPOST('newday', 'int');
    $tmpl_calendar->heured = GETPOST('periodstart', 'alpha');
    $tmpl_calendar->heuref = GETPOST('periodend', 'alpha');

    $result = $tmpl_calendar->create($user);
    if ($result != 1) {
        setEventMessage($tmpl_calendar->error, 'errors');
    }
}

if ($action == 'sessioncalendar_delete') {
    $tmpl_calendar = new Agefoddcalendrier($db);
    $tmpl_calendar->id = GETPOST('id', 'int');
    $result = $tmpl_calendar->delete($user);
    if ($result != 1) {
        setEventMessage($tmpl_calendar->error, 'errors');
    }
}

if ($action == 'updatedaytodate') {

    $weekday=GETPOST('AGF_WEEKADAY','array');
    foreach(array(1,2,3,4,5,6,0) as $daynum) {
        if (in_array($daynum, $weekday)) {
            $res = dolibarr_set_const($db, 'AGF_WEEKADAY'.$daynum, '1', 'yesno', 0, '', $conf->entity);
            if (! $res > 0)
                $error ++;
        } else {
            $res = dolibarr_set_const($db, 'AGF_WEEKADAY'.$daynum, '0', 'yesno', 0, '', $conf->entity);
            if (! $res > 0)
                $error ++;
        }
    }

    foreach(array(1,2,3,4) as $shiftnum) {
        $val=GETPOST('AGF_'.$shiftnum.'DAYSHIFT', 'none');
        $res = dolibarr_set_const($db, 'AGF_'.$shiftnum.'DAYSHIFT', $val, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;
    }

    if (! $error) {
        setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    } else {
        setEventMessage($langs->trans("Error") . " " . $msg, 'errors');
    }
}

/*
 *  Admin Form
 *
 */

llxHeader('', $langs->trans('AgefoddSetupDesc'));

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother=new FormOther($db);

dol_htmloutput_mesg($mesg);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans("AgefoddSetupDesc"), $linkback, 'setup');

// Configuration header
$head = agefodd_admin_prepare_head();
dol_fiche_head($head, 'sessiontime', $langs->trans("Module103000Name"), 0, "agefodd@agefodd");

print_titre($langs->trans("AgfAdminCalendarTemplate"));

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("AgfAdminCalendarTemplate") . '</td>';
print "</tr>\n";
print '<tr><td>';
print '<table class="noborder" width="100%">';
print '<tr>';
print '<td>' . $langs->trans("AgfDaySession") . '</td>';
print '<td>' . $langs->trans("AgfPeriodTimeB") . '</td>';
print '<td>' . $langs->trans("AgfPeriodTimeE") . '</td>';
print '<td></td>';
print '</tr>';

$tmpl_calendar = new Agefoddcalendrier($db);
$tmpl_calendar->fetch_all();
foreach ( $tmpl_calendar->lines as $line ) {

    print '<form name="SessionCalendar_' . $line->id . '" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
    print '<input type="hidden" name="action" value="sessioncalendar_delete">' . "\n";
    print '<input type="hidden" name="id" value="' . $line->id . '">' . "\n";
    print '<tr>';
    print '<td>' . $line->day_session . '</td>';
    print '<td>' . $line->heured . '</td>';
    print '<td>' . $line->heuref . '</td>';
    print '<td><input type="image" src="'.img_picto($langs->trans("Delete"), 'delete','',false,1).'" border="0" name="sessioncalendar_delete" alt="' . $langs->trans("Delete") . '"></td>';
    print '</tr>';
    print '</form>';
}

print '<form name="SessionCalendar_new" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
print '<input type="hidden" name="action" value="sessioncalendar_create">' . "\n";
print '<tr>';
print '<td><select id="newday" class="flat" name="newday">';
for($i = 1; $i <= 200; $i ++) {
    print '<option value="' . $i . '">' . $i . '</option>';
}
print '</select></td>';
print '<td>' . $formAgefodd->select_time('', 'periodstart') . '</td>';
print '<td>' . $formAgefodd->select_time('', 'periodend') . '</td>';
print '<td><input type="image" src="'. img_picto($langs->trans("Save"), 'edit_add','',false,1).'" border="0" name="sessioncalendar_create" alt="' . $langs->trans("Save") . '"></td>';
print '</tr>';
print '</table>';
print '</td></tr>';
print '</table>';
print '</form>';

print_titre($langs->trans("AgfAdminCalendarDayToDate"));
print '<form name="daytodate" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
print '<input type="hidden" name="action" value="updatedaytodate">' . "\n";
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="20%">' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Valeur") . '</td>';
print "</tr>\n";

print '<tr class="pair"><td>' . $langs->trans("AgfDayWeek") . '</td>';
print '<td align="left">';
foreach(array(1,2,3,4,5,6,0) as $daynum) {
    if ($conf->global->{'AGF_WEEKADAY'.$daynum}==1) {
        $checked=' checked="checked" ';
    } else {
        $checked='';
    }
    print '<input type="checkbox" '.$checked.' name="AGF_WEEKADAY[]" id="AGF_WEEKADAY" value="'.$daynum.'"/>'.$langs->trans('Day'.$daynum);
}
print '</td>';
print '</tr>';

print '<tr class="pair"><td>' . $langs->trans("Agf1DayShift") . '</td>';
print '<td align="left">';
print $formAgefodd->select_time($conf->global->AGF_1DAYSHIFT, 'AGF_1DAYSHIFT');
print '</td>';
print '</tr>';
print '<tr class="impair"><td>' . $langs->trans("Agf2DayShift") . '</td>';
print '<td align="left">';
print $formAgefodd->select_time($conf->global->AGF_2DAYSHIFT, 'AGF_2DAYSHIFT');
print '</td>';
print '</tr>';
print '<tr class="pair"><td>' . $langs->trans("Agf3DayShift") . '</td>';
print '<td align="left">';
print $formAgefodd->select_time($conf->global->AGF_3DAYSHIFT, 'AGF_3DAYSHIFT');
print '</td>';
print '</tr>';
print '<tr class="impair"><td>' . $langs->trans("Agf4DayShift") . '</td>';
print '<td align="left">';
print $formAgefodd->select_time($conf->global->AGF_4DAYSHIFT, 'AGF_4DAYSHIFT');
print '</td>';
print '</tr>';

print '<tr class="impair"><td colspan="2" align="right"><input type="submit" class="button" name="updatedaytodate" value="' . $langs->trans("Save") . '"></td>';

print '</table>';
print '</form>';

llxFooter();
$db->close();
