<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Cedric GROSS         <c.gross@kreiz-it.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/comm/action/peruser.php
 * \ingroup agenda
 * \brief Tab of calendar events per user
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once '../lib/agefodd.lib.php';
if (! empty($conf->projet->enabled))
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once '../class/html.formagefodd.class.php';
require_once '../class/agefodd_formateur.class.php';

if (! isset($conf->global->AGENDA_MAX_EVENTS_DAY_VIEW))
	$conf->global->AGENDA_MAX_EVENTS_DAY_VIEW = 3;

$filter = GETPOST("filter", '', 3);
$usergroup = GETPOST("usergroup", "int", 3);

$showbirthday = 0;

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = (int) GETPOST("page", "int");

if ($page == - 1) {
	$page = 0;
}
$limit = $conf->liste_limit;
$offset = $limit * $page;
if (! $sortorder)
	$sortorder = "ASC";
if (! $sortfield)
	$sortfield = "a.datec";

	// Security check
$socid = GETPOST("socid", "int");
if (! $user->rights->agefodd->agendatrainer) {
	accessforbidden();
}


$type = GETPOST("type", 'none');
$canedit = 1;

// $action=GETPOST('action','alpha');
$action = 'show_peruser'; // We use 'show_week' mode
                          // $year=GETPOST("year", 'none');
$year = GETPOST("year", "int") ? GETPOST("year", "int") : date("Y");
$month = GETPOST("month", "int") ? GETPOST("month", "int") : date("m");
$week = GETPOST("week", "int") ? GETPOST("week", "int") : date("W");
$day = GETPOST("day", "int") ? GETPOST("day", "int") : date("d");
$pid = GETPOST("projectid", "int", 3);
$status = GETPOST("status", 'none');

$filter_commercial = GETPOST('commercial', 'int');
$filter_customer = GETPOST('fk_soc', 'int');
$filter_contact = GETPOST('contact', 'int');
$filter_trainer = GETPOST('trainerid', 'int');
$filter_type_session = GETPOST('type_session', 'int');
$filter_location = GETPOST('location', 'int');
$display_only_trainer_filter = GETPOST('displayonlytrainerfilter', 'int');
$filter_trainee = GETPOST('traineeid', 'int');
$filter_session_status=GETPOST('search_session_status','array');

if ($type == 'trainer' || $type == 'trainerext') {
	$canedit = 0;
	$filter_trainer=$user->id;
} else {
	if (! $user->rights->agefodd->agenda) {
		accessforbidden();
	}
}
if ($type == 'trainerext' && !empty($user->contact_id)) {
	//In this case this is an external trainer
	$agf_trainer = new Agefodd_teacher($db);
	$result=$agf_trainer->fetch_all('', '', '', '', 0, array('f.fk_socpeople'=>$user->contact_id));
	if ($result<0) {
		setEventMessages(null,$agf_trainer->errors,'errors');
	} else {
		if (is_array($agf_trainer->lines)&& count($agf_trainer->lines)>0) {
			$filter_trainer=$agf_trainer->lines[0]->id;
		} else {
			accessforbidden();
		}
	}
}

if ($filter_commercial == - 1) {
	$filter_commercial = 0;
}
if ($filter_customer == - 1) {
	$filter_customer = 0;
}
if ($filter_contact == - 1) {
	$filter_contact = 0;
}
if ($filter_trainer == - 1) {
	$filter_trainer = 0;
}
if ($filter_type_session == - 1) {
	$filter_type_session = '';
}
if ($filter_location == - 1) {
	$filter_location = '';
}
if ($filter_trainee == -1) {
	$filter_trainee=0;
}

$onlysession = 1;
$issetOnlySession = GETPOSTISSET('onlysession');
if (!empty($issetOnlySession)) {
	$onlysession = GETPOST('onlysession','int');
	$onlysession = intval($onlysession);
}

$filterdatestart = dol_mktime(0, 0, 0, GETPOST('dt_start_filtermonth', 'int'), GETPOST('dt_start_filterday', 'int'), GETPOST('dt_start_filteryear', 'int'));

$maxprint = (isset($_GET["maxprint"]) ? GETPOST("maxprint", 'none') : $conf->global->AGENDA_MAX_EVENTS_DAY_VIEW);
$actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode", 'none') == '0' ? '0' : '');

$dateselect = dol_mktime(0, 0, 0, GETPOST('dateselectmonth', 'none'), GETPOST('dateselectday', 'none'), GETPOST('dateselectyear', 'none'));
if ($dateselect > 0) {
	$day = GETPOST('dateselectday', 'none');
	$month = GETPOST('dateselectmonth', 'none');
	$year = GETPOST('dateselectyear', 'none');
} else {
	if ($filterdatestart > 0) {
		$day = GETPOST('dt_start_filterday', 'int');
		$month = GETPOST('dt_start_filtermonth', 'int');
		$year = GETPOST('dt_start_filteryear', 'int');
	}
}

$tmp = empty($conf->global->MAIN_DEFAULT_WORKING_HOURS) ? '9-18' : $conf->global->MAIN_DEFAULT_WORKING_HOURS;
$tmparray = explode('-', $tmp);
$begin_h = GETPOST('begin_h', 'none') != '' ? GETPOST('begin_h', 'int') : ($tmparray[0] != '' ? $tmparray[0] : 9);
$end_h = GETPOST('end_h', 'none') ? GETPOST('end_h', 'none') : ($tmparray[1] != '' ? $tmparray[1] : 18);
if ($begin_h < 0 || $begin_h > 23)
	$begin_h = 9;
if ($end_h < 1 || $end_h > 24)
	$end_h = 18;
if ($end_h <= $begin_h)
	$end_h = $begin_h + 1;

$tmp = empty($conf->global->MAIN_DEFAULT_WORKING_DAYS) ? '1-5' : $conf->global->MAIN_DEFAULT_WORKING_DAYS;
$tmparray = explode('-', $tmp);
$begin_d = GETPOST('begin_d', 'none') ? GETPOST('begin_d', 'int') : ($tmparray[0] != '' ? $tmparray[0] : 1);
$end_d = GETPOST('end_d', 'none') ? GETPOST('end_d', 'none') : ($tmparray[1] != '' ? $tmparray[1] : 5);
if ($begin_d < 1 || $begin_d > 7)
	$begin_d = 1;
if ($end_d < 1 || $end_d > 7)
	$end_d = 7;
if ($end_d < $begin_d)
	$end_d = $begin_d + 1;

if ($actioncode == '')
	$actioncode = (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE);
if ($status == '' && ! isset($_GET['status']) && ! isset($_POST['status']))
	$status = (empty($conf->global->AGENDA_DEFAULT_FILTER_STATUS) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_STATUS);
if (empty($action) && ! isset($_GET['action']) && ! isset($_POST['action']))
	$action = (empty($conf->global->AGENDA_DEFAULT_VIEW) ? 'show_month' : $conf->global->AGENDA_DEFAULT_VIEW);

if (GETPOST('viewcal', 'none') && $action != 'show_day' && $action != 'show_week' && $action != 'show_peruser') {
	$action = 'show_month';
	$day = '';
} // View by month
if (GETPOST('viewweek', 'none') || $action == 'show_week') {
	$action = 'show_week';
	$week = ($week ? $week : date("W"));
	$day = ($day ? $day : date("d"));
} // View by week
if (GETPOST('viewday', 'none') || $action == 'show_day') {
	$action = 'show_day';
	$day = ($day ? $day : date("d"));
} // View by day

$langs->load("users");
$langs->load("agenda");
$langs->load("other");
$langs->load("commercial");

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array (
		'agenda'
));

/*
 * Actions
 */

if ($action == 'delete_action') {
	$event = new ActionComm($db);
	$event->fetch($actionid);
	$result = $event->delete();
}

/*
 * View
 */

$help_url = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:M&oacute;dulo_Agenda';

$form = new Form($db);
$companystatic = new Societe($db);
$formagefodd = new FormAgefodd($db);

$now = dol_now();
$nowarray = dol_getdate($now);
$nowyear = $nowarray['year'];
$nowmonth = $nowarray['mon'];
$nowday = $nowarray['mday'];

// Define list of all external calendars (global setup)
$listofextcals = array ();

$prev = dol_get_first_day_week($day, $month, $year);
$first_day = $prev['first_day'];
$first_month = $prev['first_month'];
$first_year = $prev['first_year'];

$week = $prev['week'];

$day = ( int ) $day;
$next = dol_get_next_week($day, $week, $month, $year);
$next_year = $next['year'];
$next_month = $next['month'];
$next_day = $next['day'];

$max_day_in_month = date("t", dol_mktime(0, 0, 0, $month, 1, $year));

$tmpday = $first_day;
// print 'xx'.$prev_year.'-'.$prev_month.'-'.$prev_day;
// print 'xx'.$next_year.'-'.$next_month.'-'.$next_day;

$title = $langs->trans("DoneAndToDoActions");
if ($status == 'done')
	$title = $langs->trans("DoneActions");
if ($status == 'todo')
	$title = $langs->trans("ToDoActions");

$param = '';
if ($actioncode || isset($_GET['actioncode']) || isset($_POST['actioncode']))
	$param .= "&amp;actioncode=" . $actioncode;
if ($status || isset($_GET['status']) || isset($_POST['status']))
	$param .= "&amp;status=" . $status;
if ($filter)
	$param .= "&amp;filter=" . $filter;
if ($usergroup)
	$param .= "&amp;usergroup=" . $usergroup;
if ($socid)
	$param .= "&amp;socid=" . $socid;
if ($showbirthday)
	$param .= "&amp;showbirthday=1";
if ($pid)
	$param .= "&amp;projectid=" . $pid;
if ($type)
	$param .= "&amp;type=" . $type;
if ($action == 'show_day' || $action == 'show_week' || $action == 'show_month' || $action != 'show_peruser')
	$param .= '&amp;action=' . $action;
if (!empty($filter_commercial)) {
	$param .= "&amp;commercial=" . $filter_commercial;
}
if (!empty($filter_customer)) {
	$param .= "&amp;fk_soc=" . $filter_customer;
}
if (!empty($filter_contact)) {
	$param .= "&amp;contact=" . $filter_contact;
}
if (!empty($filter_commercial)) {
	$param .= "&amp;commercial=" . $filter_commercial;
}
if (!empty($filter_trainer)) {
	$param .= "&amp;trainerid=" . $filter_trainer;
}
if (!empty($filter_type_session)) {
	$param .= "&amp;type_session=" . $filter_type_session;
}
if (!empty($filter_location)) {
	$param .= "&amp;location=" . $filter_location;
}
if (! empty($filter_trainee)) {
	$param .= '&traineeid=' . $filter_trainee;
}
if (is_array($filter_session_status) && count($filter_session_status)>0){
	foreach($filter_session_status as $val) {
		$param .= '&search_session_status[]=' . $val;
	}

}
$param .= "&amp;maxprint=" . $maxprint;
$param .= "&amp;onlysession=" . $onlysession;

$prev = dol_get_first_day_week($day, $month, $year);
// print "day=".$day." month=".$month." year=".$year;
// var_dump($prev); exit;
$prev_year = $prev['prev_year'];
$prev_month = $prev['prev_month'];
$prev_day = $prev['prev_day'];
$first_day = $prev['first_day'];
$first_month = $prev['first_month'];
$first_year = $prev['first_year'];

$week = $prev['week'];

$day = ( int ) $day;
$next = dol_get_next_week($first_day, $week, $first_month, $first_year);
$next_year = $next['year'];
$next_month = $next['month'];
$next_day = $next['day'];

// Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
$firstdaytoshow = dol_mktime(0, 0, 0, $first_month, $first_day, $first_year);
$lastdaytoshow = dol_time_plus_duree($firstdaytoshow, 7, 'd');
// print $firstday.'-'.$first_month.'-'.$first_year;
// print dol_print_date($firstdaytoshow,'dayhour');
// print dol_print_date($lastdaytoshow,'dayhour');

$max_day_in_month = date("t", dol_mktime(0, 0, 0, $month, 1, $year));

$tmpday = $first_day;

$nav = "<a href=\"?year=" . $prev_year . "&amp;month=" . $prev_month . "&amp;day=" . $prev_day . $param . "\">" . img_previous($langs->trans("Previous")) . "</a>\n";
$nav .= " <span id=\"month_name\">" . dol_print_date(dol_mktime(0, 0, 0, $first_month, $first_day, $first_year), "%Y") . ", " . $langs->trans("Week") . " " . $week;
$nav .= " </span>\n";
$nav .= "<a href=\"?year=" . $next_year . "&amp;month=" . $next_month . "&amp;day=" . $next_day . $param . "\">" . img_next($langs->trans("Next")) . "</a>\n";
$nav .= " &nbsp; (<a href=\"?year=" . $nowyear . "&amp;month=" . $nowmonth . "&amp;day=" . $nowday . $param . "\">" . $langs->trans("Today") . "</a>)";
$picto = 'calendarweek';

// Must be after the nav definition
$param .= '&year=' . $year . '&month=' . $month . ($day ? '&day=' . $day : '');
// print 'x'.$param;


llxHeader('', $langs->trans("Agenda"), $help_url, '',0,0,'','',$param);

$tabactive = '';
if ($action == 'show_month')
	$tabactive = 'cardmonth';
if ($action == 'show_week')
	$tabactive = 'cardweek';
if ($action == 'show_day')
	$tabactive = 'cardday';
if ($action == 'show_list')
	$tabactive = 'cardlist';
if ($action == 'show_peruser')
	$tabactive = 'cardperuser';

$paramnoaction = preg_replace('/action=[a-z_]+/', '', $param);

$head = agf_calendars_prepare_head($paramnoaction);

dol_fiche_head($head, $tabactive, $langs->trans('AgfMenuAgenda'), 0, 'action');
$formagefodd->agenda_filter($form, $year, $month, $day, $filter_commercial, $filter_customer, $filter_contact, $filter_trainer, $canedit, $filterdatestart, '', $onlysession, $filter_type_session, $display_only_trainer_filter, $filter_location, $action,$filter_session_status,$filter_trainee);
dol_fiche_end();

$showextcals = $listofextcals;
// Legend

$link = '';
print_fiche_titre('', $link . ' &nbsp; &nbsp; ' . $nav, '');

// Get event in an array
$eventarray = array ();

$sql = 'SELECT ';
$sql .= " DISTINCT";
$sql .= ' a.id, a.label,';
$sql .= ' a.datep,';
$sql .= ' a.datep2,';
$sql .= ' a.percent,';
$sql .= ' a.fk_user_author,a.fk_user_action,';
$sql .= ' a.transparency, a.priority, a.fulldayevent, a.location,';
$sql .= ' a.fk_soc, a.fk_contact,';
$sql .= ' a.priority, a.fulldayevent, a.location,';
if (! empty($filter_trainer)) {
	$sql .= ' socsess.rowid as fk_soc,';
} else {
	$sql .= ' a.fk_soc,';
}
$sql .= '  a.fk_contact,';
$sql .= ' ca.code';
$sql .= ' ,agf.rowid as sessionid';
$sql .= ' ,agf.type_session as sessiontype';
$sql .= ' ,agf_status.code as sessionstatus';
$sql .= ' ,trainer_session.trainer_status';
$sql .= ' ,trainer.rowid as trainerid';
$sql .= " FROM " . MAIN_DB_PREFIX . "actioncomm as a";
$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'c_actioncomm as ca ON a.fk_action = ca.id';
$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'user as u ON a.fk_user_author = u.rowid ';
$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'agefodd_session as agf ON agf.rowid = a.fk_element ';
$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'agefodd_session_status_type as agf_status ON agf.status = agf_status.rowid';
if (! empty($filter_commercial)) {
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'agefodd_session_commercial as salesman ON agf.rowid = salesman.fk_session_agefodd ';
}
if (! empty($filter_contact)) {
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'agefodd_session_contact as contact_session ON agf.rowid = contact_session.fk_session_agefodd ';
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'agefodd_contact as contact ON contact_session.fk_agefodd_contact = contact.rowid ';
}
$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'agefodd_session_formateur as trainer_session ON agf.rowid = trainer_session.fk_session ';
$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as trainer ON trainer_session.fk_agefodd_formateur = trainer.rowid ";
if (! empty($conf->global->AGF_DOL_TRAINER_AGENDA)) {
	if (!empty($onlysession)) {
		$sql .= " AND ca.code='AC_AGF_SESST' ";
	}
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur_calendrier as trainercal ON trainercal.fk_agefodd_session_formateur = trainer_session.rowid ";
	if (!empty($onlysession)) {
		$sql .=' AND trainercal.fk_actioncomm=a.id ';
	}
}
if (! empty($filter_trainee)) {
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'agefodd_session_stagiaire as trainee_session ON agf.rowid = trainee_session.fk_session_agefodd ';
}
$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . 'societe as socsess ON agf.fk_soc = socsess.rowid ';

$sql .= ' WHERE a.entity IN (' . getEntity('agefodd'/*'session'*/) . ')';
$sql .= ' AND a.elementtype IN (\'agefodd_agsession\', \'agefodd_formateur\' )';
if ($action == 'show_day') {
	$sql .= " AND (";
	$sql .= " (a.datep BETWEEN '" . $db->idate(dol_mktime(0, 0, 0, $month, $day, $year)) . "'";
	$sql .= " AND '" . $db->idate(dol_mktime(23, 59, 59, $month, $day, $year)) . "')";
	$sql .= " OR ";
	$sql .= " (a.datep2 BETWEEN '" . $db->idate(dol_mktime(0, 0, 0, $month, $day, $year)) . "'";
	$sql .= " AND '" . $db->idate(dol_mktime(23, 59, 59, $month, $day, $year)) . "')";
	$sql .= " OR ";
	$sql .= " (a.datep < '" . $db->idate(dol_mktime(0, 0, 0, $month, $day, $year)) . "'";
	$sql .= " AND a.datep2 > '" . $db->idate(dol_mktime(23, 59, 59, $month, $day, $year)) . "')";
	$sql .= ')';
} else {
	// To limit array
	$sql .= " AND (";
	$sql .= " (a.datep BETWEEN '" . $db->idate(dol_mktime(0, 0, 0, $month, 1, $year) - (60 * 60 * 24 * 7)) . "'"; // Start 7 days before
	$sql .= " AND '" . $db->idate(dol_mktime(23, 59, 59, $month, 28, $year) + (60 * 60 * 24 * 10)) . "')"; // End 7 days after + 3 to go from 28 to 31
	$sql .= " OR ";
	$sql .= " (a.datep2 BETWEEN '" . $db->idate(dol_mktime(0, 0, 0, $month, 1, $year) - (60 * 60 * 24 * 7)) . "'";
	$sql .= " AND '" . $db->idate(dol_mktime(23, 59, 59, $month, 28, $year) + (60 * 60 * 24 * 10)) . "')";
	$sql .= " OR ";
	$sql .= " (a.datep < '" . $db->idate(dol_mktime(0, 0, 0, $month, 1, $year) - (60 * 60 * 24 * 7)) . "'";
	$sql .= " AND a.datep2 > '" . $db->idate(dol_mktime(23, 59, 59, $month, 28, $year) + (60 * 60 * 24 * 10)) . "')";
	$sql .= ')';
}

// Fix indisponibilite formateur
$sql.= ' AND 1 = CASE ';
$sql.= ' 		WHEN ca.code = \'AC_AGF_NOTAV\' AND a.fk_element = trainer.rowid  AND a.elementtype = \'agefodd_formateur\' THEN 1  ';
$sql.= '		WHEN ca.code != \'AC_AGF_NOTAV\' THEN 1 ';
$sql.= '     ELSE 0 ';
$sql.= ' END ';

if (! empty($filter_commercial)) {
	$sql .= " AND salesman.fk_user_com=" . $filter_commercial;
}
if (! empty($filter_customer)) {
	$sql .= " AND agf.fk_soc=" . $filter_customer;
}
if (! empty($filter_contact)) {

	if ($conf->global->AGF_CONTACT_DOL_SESSION) {
		$sql .= " AND contact.fk_socpeople=" . $filter_contact;
	} else {
		$sql .= " AND contact.rowid=" . $filter_contact;
	}
}
if (! empty($filter_trainer)) {

	if ($type == 'trainer') {
		$sql .= " AND trainer.fk_user=" . $filter_trainer;
	} else {
		$sql .= " AND trainer_session.fk_agefodd_formateur=" . $filter_trainer;
	}
}

if (! empty($onlysession) && empty($conf->global->AGF_DOL_TRAINER_AGENDA)) {
	$sql .= " AND ca.code='AC_AGF_SESS'";
}
if ($filter_type_session != '') {
	$sql .= " AND agf.type_session=" . $filter_type_session;
}
if (! empty($filter_location)) {
	$sql .= " AND agf.fk_session_place=" . $filter_location;
}
if (! empty($filterdatestart)) {
	$sql .= ' AND a.datep>=\'' . $db->idate($filterdatestart) . '\'';
}
if (! empty($filter_session_status)) {
	$sql .= " AND agf.status IN (" . implode(',',$filter_session_status).")";
}
if (! empty($filter_trainee)) {
	$sql .= " AND trainee_session.fk_stagiaire=".$filter_trainee;
}
// Sort on date
$sql .= ' ORDER BY datep';

dol_syslog("agefodd/agenda/pertrainer.php", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ( $i < $num ) {
		$obj = $db->fetch_object($resql);

		// Discard auto action if option is on
		if (! empty($conf->global->AGENDA_ALWAYS_HIDE_AUTO) && $obj->code == 'AC_OTH_AUTO') {
			$i ++;
			continue;
		}

		// Create a new object action
		$event = new ActionComm($db);
		$event->id = $obj->id;
		$event->datep = $db->jdate($obj->datep); // datep and datef are GMT date
		$event->datef = $db->jdate($obj->datep2);
		$event->type_code = $obj->code;
		$event->type_color = $obj->color;
		// $event->libelle=$obj->label; // deprecated
		$event->label = $obj->label;
		$event->percentage = $obj->percent;
		// $event->author->id=$obj->fk_user_author; // user id of creator
		$event->authorid = $obj->fk_user_author; // user id of creator
		$event->userownerid = $obj->fk_user_action; // user id of owner
		$event->fetch_userassigned(); // This load $event->userassigned
		$event->priority = $obj->priority;
		$event->fulldayevent = $obj->fulldayevent;
		$event->location = $obj->location;
		$event->transparency = $obj->transparency;

		$event->sessionid = $obj->sessionid;
		$event->sessiontype = $obj->sessiontype;
		$event->sessionstatus = $obj->sessionstatus;
		$event->trainer_status = $obj->trainer_status;
		$event->trainerid = $obj->trainerid;

		$event->socid = $obj->fk_soc;
		$event->contactid = $obj->fk_contact;
		// $event->societe->id=$obj->fk_soc; // deprecated
		// $event->contact->id=$obj->fk_contact; // deprecated

		$event->fk_element = $obj->fk_element;
		$event->elementtype = $obj->elementtype;

        if ($obj->code == 'AC_AGF_NOTAV') {
            $event->type_color = $obj->color;
        }


		// Defined date_start_in_calendar and date_end_in_calendar property
		// They are date start and end of action but modified to not be outside calendar view.
		if ($event->percentage <= 0) {
			$event->date_start_in_calendar = $event->datep;
			if ($event->datef != '' && $event->datef >= $event->datep)
				$event->date_end_in_calendar = $event->datef;
			else
				$event->date_end_in_calendar = $event->datep;
		} else {
			$event->date_start_in_calendar = $event->datep;
			if ($event->datef != '' && $event->datef >= $event->datep)
				$event->date_end_in_calendar = $event->datef;
			else
				$event->date_end_in_calendar = $event->datep;
		}
		// Define ponctual property
		if ($event->date_start_in_calendar == $event->date_end_in_calendar) {
			$event->ponctuel = 1;
		}

		// Check values
		if ($event->date_end_in_calendar < $firstdaytoshow || $event->date_start_in_calendar >= $lastdaytoshow) {
			// This record is out of visible range
		} else {
			if ($event->date_start_in_calendar < $firstdaytoshow)
				$event->date_start_in_calendar = $firstdaytoshow;
			if ($event->date_end_in_calendar >= $lastdaytoshow)
				$event->date_end_in_calendar = ($lastdaytoshow - 1);

				// Add an entry in actionarray for each day
			$daycursor = $event->date_start_in_calendar;
			$annee = date('Y', $daycursor);
			$mois = date('m', $daycursor);
			$jour = date('d', $daycursor);

			// Loop on each day covered by action to prepare an index to show on calendar
			$loop = true;
			$j = 0;
			$daykey = dol_mktime(0, 0, 0, $mois, $jour, $annee);
			do {
				// if ($event->id==408) print 'daykey='.$daykey.' '.$event->datep.' '.$event->datef.'<br>';

				$eventarray[$daykey][] = $event;
				$j ++;

				$daykey += 60 * 60 * 24;
				if ($daykey > $event->date_end_in_calendar)
					$loop = false;
			} while ( $loop );

			// print 'Event '.$i.' id='.$event->id.' (start='.dol_print_date($event->datep).'-end='.dol_print_date($event->datef);
			// print ' startincalendar='.dol_print_date($event->date_start_in_calendar).'-endincalendar='.dol_print_date($event->date_end_in_calendar).') was added in '.$j.' different index key of array<br>';
		}
		$i ++;
	}
} else {
	dol_print_error($db);
}

$maxnbofchar = 18;
$cachethirdparties = array ();
$cachecontacts = array ();

// Define theme_datacolor array
$color_file = DOL_DOCUMENT_ROOT . "/theme/" . $conf->theme . "/graph-color.php";
if (is_readable($color_file)) {
	include_once $color_file;
}
if (! is_array($theme_datacolor))
	$theme_datacolor = array (
			array (
					120,
					130,
					150
			),
			array (
					200,
					160,
					180
			),
			array (
					190,
					190,
					220
			)
	);

$newparam = $param; // newparam is for birthday links
$newparam = preg_replace('/showbirthday=/i', 'showbirthday_=', $newparam); // To avoid replacement when replace day= is done
$newparam = preg_replace('/action=show_month&?/i', '', $newparam);
$newparam = preg_replace('/action=show_week&?/i', '', $newparam);
$newparam = preg_replace('/day=[0-9]+&?/i', '', $newparam);
$newparam = preg_replace('/month=[0-9]+&?/i', '', $newparam);
$newparam = preg_replace('/year=[0-9]+&?/i', '', $newparam);
$newparam = preg_replace('/viewweek=[0-9]+&?/i', '', $newparam);
$newparam = preg_replace('/showbirthday_=/i', 'showbirthday=', $newparam); // Restore correct parameter
$newparam .= '&viewweek=1';

echo '<form id="move_event" action="" method="POST"><input type="hidden" name="action" value="mupdate">';
echo '<input type="hidden" name="backtopage" value="' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . '">';
echo '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
echo '<input type="hidden" name="newdate" id="newdate">';
echo '</form>';

// Line header with list of days

// print "begin_d=".$begin_d." end_d=".$end_d;

echo '<table width="100%" class="nocellnopadd cal_month">';

echo '<tr class="liste_titre">';
echo '<td></td>';
$i = 0; // 0 = sunday,
while ( $i < 7 ) {
	if (($i + 1) < $begin_d || ($i + 1) > $end_d) {
		$i ++;
		continue;
	}
	echo '<td align="center" colspan="' . ($end_h - $begin_h) . '">';
	echo $langs->trans("Day" . (($i + (isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : 1)) % 7));
	print "<br>";
	if ($i)
		print dol_print_date(dol_time_plus_duree($firstdaytoshow, $i, 'd'), 'day');
	else
		print dol_print_date($firstdaytoshow, 'day');
	echo "</td>\n";
	$i ++;
}
echo "</tr>\n";

echo '<tr class="liste_titre">';
echo '<td></td>';
$i = 0;
while ( $i < 7 ) {
	if (($i + 1) < $begin_d || ($i + 1) > $end_d) {
		$i ++;
		continue;
	}
	for($h = $begin_h; $h < $end_h; $h ++) {
		echo '<td align="center">';
		print '<small style="font-family: courier">' . sprintf("%02d", $h) . '</small>';
		print "</td>";
	}
	echo "</td>\n";
	$i ++;
}
echo "</tr>\n";

// Define $usernames
$agf_trainer = new Agefodd_teacher($db);
if (! empty($filter_trainer)) {
	$filter_trainner_req = array (
			'f.rowid' => $filter_trainer
	);
} else {
	$filter_trainner_req = array ();
}
$result = $agf_trainer->fetch_all('ASC', 'u.lastname,s.lastname', 0, 0, 0, $filter_trainner_req);
if ($result < 0) {
	setEventMessages($agf_trainer->error, null, 'errors');
}

// Load array of colors by type
$colorsbytype = array ();
$labelbytype = array ();
$sql = "SELECT code, color, libelle FROM " . MAIN_DB_PREFIX . "c_actioncomm";
$resql = $db->query($sql);
while ( $obj = $db->fetch_object($resql) ) {
	$colorsbytype[$obj->code] = $obj->color;
	$labelbytype[$obj->code] = $obj->libelle;
}

// Loop on each user to show calendar
$todayarray = dol_getdate($now, 'fast');
$sav = $tmpday;
$showheader = true;
$var = false;
foreach ( $agf_trainer->lines as $trainer ) {
	$var = ! $var;
	echo "<tr>";
	echo '<td class="cal_current_month cal_peruserviewname"' . ($var ? ' style="background: #F8F8F8"' : '') . '>' . $trainer->getNomUrl('name') . '</td>';
	$tmpday = $sav;

	// Lopp on each day of week
	$i = 0;
	for($iter_day = 0; $iter_day < 8; $iter_day ++) {
		if (($i + 1) < $begin_d || ($i + 1) > $end_d) {
			$i ++;
			continue;
		}

		// Show days of the current week
		$curtime = dol_time_plus_duree($firstdaytoshow, $iter_day, 'd');
		$tmparray = dol_getdate($curtime, 'fast');
		$tmpday = $tmparray['mday'];
		$tmpmonth = $tmparray['mon'];
		$tmpyear = $tmparray['year'];

		$style = 'cal_current_month';
		if ($iter_day == 6)
			$style .= ' cal_other_month';
		$today = 0;
		if ($todayarray['mday'] == $tmpday && $todayarray['mon'] == $tmpmonth && $todayarray['year'] == $tmpyear)
			$today = 1;
		if ($today)
			$style = 'cal_today_peruser';

		show_day_events2($trainer, $tmpday, $tmpmonth, $tmpyear, $monthshown, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300, $showheader, $colorsbytype, $var);

		$i ++;
	}
	echo "</tr>\n";
	$showheader = false;
}

echo "</table>\n";

if (! empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
	$langs->load("commercial");
	print '<br>' . $langs->trans("Legend") . ': <br>';
	foreach ( $colorsbytype as $code => $color ) {
		if ($color) {
			print '<div style="float: left; padding: 2px; margin-right: 6px;"><div style="' . ($color ? 'background: #' . $color . ';' : '') . 'width:16px; float: left; margin-right: 4px;">&nbsp;</div>';
			print $langs->trans("Action" . $code) != "Action" . $code ? $langs->trans("Action" . $code) : $labelbytype[$code];
			// print $code;
			print '</div>';
		}
	}
	// $color=sprintf("%02x%02x%02x",$theme_datacolor[0][0],$theme_datacolor[0][1],$theme_datacolor[0][2]);
	print '<div style="float: left; padding: 2px; margin-right: 6px;"><div class="peruser_busy" style="width:16px; float: left; margin-right: 4px;">&nbsp;</div>';
	print $langs->trans("Other");
	print '</div>';
	/* TODO Show this if at least one cumulated event
	 print '<div style="float: left; padding: 2px; margin-right: 6px;"><div style="background: #222222; width:16px; float: left; margin-right: 4px;">&nbsp;</div>';
	 print $langs->trans("SeveralEvents");
	 print '</div>';
	 */
}

// Add js code to manage click on a box
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	jQuery(".onclickopenref").click(function() {
		var ref=$(this).attr(\'ref\');
		var res = ref.split("_");
		var userid = res[1];
		var year = res[2];
		var month = res[3];
		var day = res[4];
		var hour = res[5];
		var min = res[6];
		var ids = res[7];
		if (ids == \'none\') /* No event */
		{
			/* alert(\'no event\'); */
			url = "' . DOL_URL_ROOT . '/comm/action/card.php?action=create&assignedtouser="+userid+"&datep="+year+month+day+hour+min+"00&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?year=' . $year . '&month=' . $month . '&day=' . $day) . '"
			window.location.href = url;
		}
		else if (ids.indexOf(",") > -1)	/* There is several events */
		{
			/* alert(\'several events\'); */
			url = "' . dol_buildpath('/agefodd/agenda/listactions.php', 1) . '?trainerid="+userid+"&dt_start_filteryear="+year+"&dt_start_filtermonth="+month+"&dt_start_filterday="+day;
			window.location.href = url;
		}
		else	/* One event */
		{
			/* alert(\'one event\'); */
			url = "' . DOL_URL_ROOT . '/comm/action/card.php?action=view&id="+ids
			window.location.href = url;
		}
	});
});
</script>';

llxFooter();

$db->close();

/**
 * Show event of a particular day for a user
 *
 * @param string $username
 * @param int $day
 * @param int $month
 * @param int $year
 * @param int $monthshown month shown in calendar view
 * @param string $style to use for this day
 * @param array $eventarray of events
 * @param int $maxprint of actions to show each day on month view (0 means no limit)
 * @param int $maxnbofchar of characters to show for event line
 * @param string $newparam on current URL
 * @param int $showinfo extended information (used by day view)
 * @param int $minheight height for each event. 60px by default.
 * @param boolean $showheader
 * @param array $colorsbytype colors by type
 * @param bool $var false for alternat style on tr/td
 * @return void
 */
function show_day_events2($username, $day, $month, $year, $monthshown, $style, &$eventarray, $maxprint = 0, $maxnbofchar = 16, $newparam = '', $showinfo = 0, $minheight = 60, $showheader = false, $colorsbytype = array(), $var = false) {
	global $db;
	global $user, $conf, $langs, $hookmanager, $action;
	global $filter, $status, $actioncode; // Filters used into search form
	global $theme_datacolor; // Array with a list of different we can use (come from theme)
	global $cachethirdparties, $cachecontacts, $colorindexused;
	global $begin_h, $end_h;

	$cases1 = array (); // Color first half hour
	$cases2 = array (); // Color second half hour

	$curtime = dol_mktime(0, 0, 0, $month, $day, $year);

	$i = 0;
	$nummytasks = 0;
	$numother = 0;
	$numbirthday = 0;
	$numical = 0;
	$numicals = array ();
	$ymd = sprintf("%04d", $year) . sprintf("%02d", $month) . sprintf("%02d", $day);

	$nextindextouse = is_array($colorindexused) ? count($colorindexused) : 0; // At first run, this is 0, so fist user has 0, next 1, ...
	                                          // if ($username->id && $day==1) var_dump($eventarray);

	// We are in a particular day for $username, now we scan all events
	foreach ( $eventarray as $daykey => $notused ) {
		$annee = date('Y', $daykey);
		$mois = date('m', $daykey);
		$jour = date('d', $daykey);
		// print $annee.'-'.$mois.'-'.$jour.' '.$year.'-'.$month.'-'.$day."<br>\n";

		// Is it the day we are looking for when calling function ?
		if ($day == $jour && $month == $mois && $year == $annee) {
			// Scan all event for this date
			foreach ( $eventarray[$daykey] as $index => $event ) {
				// $keysofuserassigned=array_keys($event->userassigned);

				if ($username->id != $event->trainerid)
					continue; // We discard record if event is from another user than user we want to show
						          // if ($username->id != $event->userownerid) continue; // We discard record if event is from another user than user we want to show

				// Define $color (Hex string like '0088FF') and $cssclass of event
				$statusColor = array(
				    0 => 'F8F816',
                    1 => '66ff99',
                    2 => '33ff33',
                    3 => '3366ff',
                    4 => '33ccff',
                    5 => 'cc6600',
                    6 => 'cc0000',
                );

				if($event->type_code == 'AC_AGF_NOTAV')
                {
                    $color = str_replace("#", "", $colorsbytype[$event->type_code]);
                }
				elseif(isset($event->trainer_status)){
                    $color = $statusColor[$event->trainer_status];
                }

					// $cssclass=$cssclass.' '.$cssclass.'_day_'.$ymd;

				// Define all rects with event (cases1 is first half hour, cases2 is second half hour)
					// var_dump($event);
				for($h = $begin_h; $h < $end_h; $h ++) {
					// if ($username->id == 1 && $day==1) print 'h='.$h;
					$newcolor = ''; // init
					if (empty($event->fulldayevent)) {
						$a = dol_mktime(( int ) $h, 0, 0, $month, $day, $year, false, false);
						$b = dol_mktime(( int ) $h, 30, 0, $month, $day, $year, false, false);
						$c = dol_mktime(( int ) $h + 1, 0, 0, $month, $day, $year, false, false);

						$dateendtouse = $event->date_end_in_calendar;
						if ($dateendtouse == $event->date_start_in_calendar)
							$dateendtouse ++;

							// print dol_print_date($event->date_start_in_calendar,'dayhour').'-'.dol_print_date($a,'dayhour').'-'.dol_print_date($b,'dayhour').'<br>';

						if ($event->date_start_in_calendar < $b && $dateendtouse > $a) {
							$busy = $event->transparency;
							$cases1[$h][$event->id]['busy'] = $busy;
							$cases1[$h][$event->id]['string'] = dol_print_date($event->date_start_in_calendar, 'dayhour');
							if ($event->date_end_in_calendar && $event->date_end_in_calendar != $event->date_start_in_calendar) {
								$tmpa = dol_getdate($event->date_start_in_calendar, true);
								$tmpb = dol_getdate($event->date_end_in_calendar, true);
								if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year'])
									$cases1[$h][$event->id]['string'] .= '-' . dol_print_date($event->date_end_in_calendar, 'hour');
								else
									$cases1[$h][$event->id]['string'] .= '-' . dol_print_date($event->date_end_in_calendar, 'dayhour');
							}
							$cases1[$h][$event->id]['string'] .= ' - ' . $event->label;
							$cases1[$h][$event->id]['typecode'] = $event->type_code;
							if ($event->socid) {
								// $cases1[$h][$event->id]['string'].='xxx';
							}
							$cases1[$h][$event->id]['color'] = $color;
						}
						if ($event->date_start_in_calendar < $c && $dateendtouse > $b) {
							$busy = $event->transparency;
							$cases2[$h][$event->id]['busy'] = $busy;
							$cases2[$h][$event->id]['string'] = dol_print_date($event->date_start_in_calendar, 'dayhour');
							if ($event->date_end_in_calendar && $event->date_end_in_calendar != $event->date_start_in_calendar) {
								$tmpa = dol_getdate($event->date_start_in_calendar, true);
								$tmpb = dol_getdate($event->date_end_in_calendar, true);
								if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year'])
									$cases2[$h][$event->id]['string'] .= '-' . dol_print_date($event->date_end_in_calendar, 'hour');
								else
									$cases2[$h][$event->id]['string'] .= '-' . dol_print_date($event->date_end_in_calendar, 'dayhour');
							}
							$cases2[$h][$event->id]['string'] .= ' - ' . $event->label;
							$cases2[$h][$event->id]['typecode'] = $event->type_code;
							if ($event->socid) {
								// $cases2[$h][$event->id]['string'].='xxx';
							}
							$cases2[$h][$event->id]['color'] = $color;
						}
					} else {
						$busy = $event->transparency;
						$cases1[$h][$event->id]['busy'] = $busy;
						$cases2[$h][$event->id]['busy'] = $busy;
						$cases1[$h][$event->id]['string'] = $event->label;
						$cases2[$h][$event->id]['string'] = $event->label;
						$cases1[$h][$event->id]['typecode'] = $event->type_code;
						$cases2[$h][$event->id]['typecode'] = $event->type_code;
						$cases1[$h][$event->id]['color'] = $color;
						$cases2[$h][$event->id]['color'] = $color;
					}
				}
			}

			break; // We found the date we were looking for. No need to search anymore.
		}
	}

	for($h = $begin_h; $h < $end_h; $h ++) {
		$color1 = '';
		$color2 = '';
		$style1 = '';
		$style2 = '';
		$string1 = '&nbsp;';
		$string2 = '&nbsp;';
		$title1 = '';
		$title2 = '';

		if (isset($cases1[$h]) && $cases1[$h] != '') {
			// $title1.=count($cases1[$h]).' '.(count($cases1[$h])==1?$langs->trans("Event"):$langs->trans("Events"));
			if (count($cases1[$h]) > 1)
				$title1 .= count($cases1[$h]) . ' ' . (count($cases1[$h]) == 1 ? $langs->trans("Event") : $langs->trans("Events"));
			$string1 = '&nbsp;';
			if (empty($conf->global->AGENDA_NO_TRANSPARENT_ON_NOT_BUSY))
				$style1 = 'peruser_notbusy';
			else
				$style1 = 'peruser_busy';
			foreach ( $cases1[$h] as $id => $ev ) {
				if ($ev['busy'])
					$style1 = 'peruser_busy';
			}
		}
		if (isset($cases2[$h]) && $cases2[$h] != '') {
			// $title2.=count($cases2[$h]).' '.(count($cases2[$h])==1?$langs->trans("Event"):$langs->trans("Events"));
			if (count($cases2[$h]) > 1)
				$title2 .= count($cases2[$h]) . ' ' . (count($cases2[$h]) == 1 ? $langs->trans("Event") : $langs->trans("Events"));
			$string2 = '&nbsp;';
			if (empty($conf->global->AGENDA_NO_TRANSPARENT_ON_NOT_BUSY))
				$style2 = 'peruser_notbusy';
			else
				$style2 = 'peruser_busy';
			foreach ( $cases2[$h] as $id => $ev ) {
				if ($ev['busy'])
					$style2 = 'peruser_busy';
			}
		}

		if ($h == $begin_h)
			echo '<td class="' . $style . '_peruserleft cal_peruser"' . ($var ? ' style="background: #F8F8F8"' : '') . '>';
		else
			echo '<td class="' . $style . ' cal_peruser"' . ($var ? ' style="background: #F8F8F8"' : '') . '>';

		//var_dump($cases1[$h]);
		// 1 seul evenement
		if (is_array($cases1[$h]) && count($cases1[$h]) == 1) {
			$ids = array_keys($cases1[$h]);
			$output = array_slice($cases1[$h], 0, 1);
			if ($output[0]['string'])
				$title1 .= ($title1 ? ' - ' : '') . $output[0]['string'];
			if ($output[0]['color'])
				$color1 = $output[0]['color'];
		} else if (is_array($cases1[$h]) && count($cases1[$h]) > 1)
			$color1 = '222222';

			// 1 seul evenement
		if (is_array($cases2[$h]) && count($cases2[$h]) == 1) {
			$ids = array_keys($cases2[$h]);
			$output = array_slice($cases2[$h], 0, 1);
			if ($output[0]['string'])
				$title2 .= ($title2 ? ' - ' : '') . $output[0]['string'];
			if ($output[0]['color'])
				$color2 = $output[0]['color'];
		} else if (is_array($cases2[$h]) && count($cases2[$h]) > 1)
			$color2 = '222222';
		$ids1 = '';
		$ids2 = '';
		if (is_array($cases1[$h]) && count($cases1[$h]) && array_keys($cases1[$h]))
			$ids1 = join(',', array_keys($cases1[$h]));
		if (is_array($cases2[$h]) && count($cases2[$h]) && array_keys($cases2[$h]))
			$ids2 = join(',', array_keys($cases2[$h]));
			// var_dump($cases1[$h]);
		print '<table class="nobordernopadding" width="100%">';
		print '<tr><td ' . ($color1 ? 'style="background: #' . $color1 . ';"' : '') . 'class="' . ($style1 ? $style1 . ' ' : '') . 'onclickopenref' . ($title1 ? ' cursorpointer' : '') . '" ref="ref_' . $username->id . '_' . sprintf("%04d", $year) . '_' . sprintf("%02d", $month) . '_' . sprintf("%02d", $day) . '_' . sprintf("%02d", $h) . '_00_' . ($ids1 ? $ids1 : 'none') . '"' . ($title1 ? ' title="' . $title1 . '"' : '') . '>';
		print $string1;
		print '</td><td ' . ($color2 ? 'style="background: #' . $color2 . ';"' : '') . 'class="' . ($style2 ? $style2 . ' ' : '') . 'onclickopenref' . ($title1 ? ' cursorpointer' : '') . '" ref="ref_' . $username->id . '_' . sprintf("%04d", $year) . '_' . sprintf("%02d", $month) . '_' . sprintf("%02d", $day) . '_' . sprintf("%02d", $h) . '_30_' . ($ids2 ? $ids2 : 'none') . '"' . ($title2 ? ' title="' . $title2 . '"' : '') . '>';
		print $string2;
		print '</td></tr>';
		print '</table>';
		print '</td>';
	}
}
