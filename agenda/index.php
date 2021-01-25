<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Cedric GROSS         <c.gross@kreiz-it.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 * \file agefodd/agenda/index.php
 * \ingroup agefodd
 * \brief Home page of calendar events
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
require_once '../class/agefodd_formateur.class.php';
require_once '../lib/agefodd.lib.php';
require_once '../class/html.formagefodd.class.php';
require_once '../class/agefodd_session_formateur.class.php';

if (! isset($conf->global->AGENDA_MAX_EVENTS_DAY_VIEW)) $conf->global->AGENDA_MAX_EVENTS_DAY_VIEW=3;

if (empty($conf->global->AGENDA_EXT_NB)) $conf->global->AGENDA_EXT_NB=5;
$MAXAGENDA=$conf->global->AGENDA_EXT_NB;

$filter_commercial = GETPOST('commercial', 'int');
$filter_customer = GETPOST('fk_soc', 'int');
$filter_contact = GETPOST('contact', 'int');
$filter_trainer = GETPOST('trainerid', 'int');
$filter_trainee = GETPOST('traineeid', 'int');
$filter_type_session = GETPOST('type_session', 'int');
$filter_location = GETPOST('location', 'int');
$display_only_trainer_filter = GETPOST('displayonlytrainerfilter', 'int');
$filter_session_status=GETPOST('search_session_status','array');

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
$type = GETPOST('type', 'none');
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = (int) GETPOST("page", "int");
if ($page == -1) { $page = 0; }
$limit = $conf->liste_limit;
$offset = $limit * $page;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="a.datec";

$canedit = 1;

if ($user->rights->agefodd->agendatrainer && ! $user->rights->agefodd->agenda) {
	header("Location: ".dol_buildpath('/agefodd/agenda/pertrainer.php', 1));
	exit;
}

if ($user->rights->agefodd->session->trainer) {
	$type = 'trainer';
}
if ($type == 'trainer') {
	$canedit = 0;

	$agf_trainer = new Agefodd_teacher($db);
	$result=$agf_trainer->fetch_all('', '', '', '', 0, array('f.fk_user'=>$user->id));
	if ($result<0) {
		setEventMessages(null,$agf_trainer->errors,'errors');
	} else {
		if (is_array($agf_trainer->lines)&& count($agf_trainer->lines)>0) {
			$filter_trainer=$agf_trainer->lines[0]->id;
		} else {
			accessforbidden();
		}
	}
} else {
	if (! $user->rights->agefodd->agenda)
		accessforbidden();
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

$onlysession = GETPOST('onlysession', 'int');
if ($onlysession != '0') {
	$onlysession = 1;
}

$action = GETPOST('action', 'alpha');
$year = GETPOST("year", "int") ? GETPOST("year", "int") : date("Y");
$month = GETPOST("month", "int") ? GETPOST("month", "int") : date("m");
$week = GETPOST("week", "int") ? GETPOST("week", "int") : date("W");
$day = GETPOST("day", "int") ? GETPOST("day", "int") : 0;

$maxprint = (isset($_GET ["maxprint"]) ? GETPOST("maxprint", 'none') : $conf->global->AGENDA_MAX_EVENTS_DAY_VIEW);

if ($actioncode == '') $actioncode=(empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE)?'':$conf->global->AGENDA_DEFAULT_FILTER_TYPE);
if ($status == ''   && ! isset($_GET['status']) && ! isset($_POST['status'])) $status=(empty($conf->global->AGENDA_DEFAULT_FILTER_STATUS)?'':$conf->global->AGENDA_DEFAULT_FILTER_STATUS);
if (empty($action) && ! isset($_GET['action']) && ! isset($_POST['action'])) $action=(empty($conf->global->AGENDA_DEFAULT_VIEW)?'show_month':$conf->global->AGENDA_DEFAULT_VIEW);

if (GETPOST('viewcal', 'none') && $action != 'show_day' && $action != 'show_week')  {
    $action='show_month'; $day='';
} // View by month
if (GETPOST('viewweek', 'none') || $action == 'show_week') {
    $action='show_week'; $week=($week?$week:date("W")); $day=($day?$day:date("d"));
} // View by week
if (GETPOST('viewday', 'none') || $action == 'show_day')  {
    $action='show_day'; $day=($day?$day:date("d"));
} // View by day


$langs->load("agenda");
$langs->load("other");
$langs->load("commercial");
$langs->load("agefodd@agefodd");

/*
 * Actions
 */

if (GETPOST("viewlist", 'none') || $action == 'show_list')
{
	$param = '';
    foreach($_POST as $key => $val)
    {
        if ($key=='token') continue;
		$param .= '&' . $key . '=' . urlencode($val);
	}
	// print $param;
	header("Location: " . dol_buildpath('/agefodd/agenda/listactions.php', 1) . '?' . $param);
	exit();
}

if (GETPOST("viewperuser", 'none') || $action == 'show_peruser')
{
    $param='';
    foreach($_POST as $key => $val)
    {
        if ($key=='token') continue;
        $param.='&'.$key.'='.urlencode($val);
    }
    //print $param;
    header("Location: ".dol_buildpath('/agefodd/agenda/pertrainer.php', 1).'?'.$param);
    exit;
}

if ($action =='delete_action')
{
	$event = new ActionComm($db);
	$event->fetch($actionid);
	$result = $event->delete();
}



/*
 * View
 */

$help_url = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:M&oacute;dulo_Agenda';

$form = new Form($db);
$formagefodd = new FormAgefodd($db);
$companystatic = new Societe($db);
$contactstatic = new Contact($db);

$now = dol_now();

if (empty($action) || $action=='show_month')
{
	$prev = dol_get_prev_month($month, $year);
	$prev_year = $prev ['year'];
	$prev_month = $prev ['month'];
	$next = dol_get_next_month($month, $year);
	$next_year = $next ['year'];
	$next_month = $next ['month'];

    $max_day_in_prev_month = date("t",dol_mktime(0,0,0,$prev_month,1,$prev_year));  // Nb of days in previous month
	$max_day_in_month = date("t", dol_mktime(0, 0, 0, $month, 1, $year)); // Nb of days in next month
    // tmpday is a negative or null cursor to know how many days before the 1st to show on month view (if tmpday=0, 1st is monday)
    $tmpday = -date("w",dol_mktime(12,0,0,$month,1,$year,true))+2;		// date('w') is 0 fo sunday
	$tmpday += ((isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : 1) - 1);
    if ($tmpday >= 1) $tmpday -= 7;	// If tmpday is 0 we start with sunday, if -6, we start with monday of previous week.
    // Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
	$firstdaytoshow = dol_mktime(0, 0, 0, $prev_month, $max_day_in_prev_month + $tmpday, $prev_year);
	$next_day = 7 - ($max_day_in_month + 1 - $tmpday) % 7;
    if ($next_day < 6) $next_day+=7;
	$lastdaytoshow = dol_mktime(0, 0, 0, $next_month, $next_day, $next_year);
}
if ($action=='show_week')
{
	$prev = dol_get_first_day_week($day, $month, $year);
	$prev_year = $prev ['prev_year'];
	$prev_month = $prev ['prev_month'];
	$prev_day = $prev ['prev_day'];
	$first_day = $prev ['first_day'];
    $first_month= $prev['first_month'];
    $first_year = $prev['first_year'];

	$week = $prev ['week'];

	$day = ( int ) $day;
    $next = dol_get_next_week($first_day, $week, $first_month, $first_year);
	$next_year = $next ['year'];
	$next_month = $next ['month'];
	$next_day = $next ['day'];

    // Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
    $firstdaytoshow=dol_mktime(0,0,0,$first_month,$first_day,$first_year);
	$lastdaytoshow=dol_time_plus_duree($firstdaytoshow, 7, 'd');

	$max_day_in_month = date("t", dol_mktime(0, 0, 0, $month, 1, $year));

	$tmpday = $first_day;
}
if ($action == 'show_day')
{
	$prev = dol_get_prev_day($day, $month, $year);
	$prev_year = $prev ['year'];
	$prev_month = $prev ['month'];
	$prev_day = $prev ['day'];
	$next = dol_get_next_day($day, $month, $year);
	$next_year = $next ['year'];
	$next_month = $next ['month'];
	$next_day = $next ['day'];

    // Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
	$firstdaytoshow = dol_mktime(0, 0, 0, $prev_month, $prev_day, $prev_year);
	$lastdaytoshow = dol_mktime(0, 0, 0, $next_month, $next_day, $next_year);
}

$title = $langs->trans("DoneAndToDoActions");


$param = '';
$region = '';
if ($filter_commercial) {
	$param .= "&commercial=" . $filter_commercial;
}

if ($filter_customer) {
	$param .= "&fk_soc=" . $filter_customer;
}
if ($filter_contact) {
	$param .= "&contact=" . $filter_contact;
}
if ($filter_trainer) {
	$param .= "&trainerid=" . $filter_trainer;
}
if ($filter_trainee) {
	$param .= "&traineeid=" . $filter_trainee;
}
if ($type) {
	$param .= "&type=" . $type;
}
if ($action == 'show_day' || $action == 'show_week' || $action == 'show_month') {
	$param .= '&action=' . $action;
}

if ($filter_type_session != '') {
	$param .= '&type_session=' . $filter_type_session;
}
if ($display_only_trainer_filter != '') {
	$param .= '&displayonlytrainerfilter=' . $display_only_trainer_filter;
}
if ($filter_location){
	$param .= '&location=' . $filter_location;
}
if (is_array($filter_session_status) && count($filter_session_status)>0){
	foreach($filter_session_status as $val) {
		$param .= '&search_session_status[]=' . $val;
	}

}
$param .= "&maxprint=" . $maxprint;


llxHeader('', $langs->trans("Agenda"), $help_url, '',0,0,'','',$param);

// Show navigation bar
if (empty($action) || $action=='show_month')
{
	if (DOL_VERSION < 6.0) {
		$nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month.$param."\">".img_previous($langs->trans("Previous"))."</a>\n";
	} else {
		$nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month.$param."\"><i class=\"fa fa-chevron-left\"></i></a> &nbsp;\n";
	}
	$nav .= " <span id=\"month_name\">" . dol_print_date(dol_mktime(0, 0, 0, $month, 1, $year), "%b %Y");
	$nav .= " </span>\n";
	if (DOL_VERSION < 6.0) {
	    $nav.="<a href=\"?year=".$next_year."&amp;month=".$next_month.$param."\">".img_next($langs->trans("Next"))."</a>\n";
	} else {
		$nav.=" &nbsp; <a href=\"?year=".$next_year."&amp;month=".$next_month.$param."\"><i class=\"fa fa-chevron-right\"></i></a>\n";
	}
    $nav.=" &nbsp; (<a href=\"?year=".$nowyear."&amp;month=".$nowmonth.$param."\">".$langs->trans("Today")."</a>)";
	$picto = 'calendar';
}
if ($action=='show_week')
{
	if (DOL_VERSION < 6.0) {
   		$nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month."&amp;day=".$prev_day.$param."\">".img_previous($langs->trans("Previous"))."</a>\n";
	} else {
		$nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month."&amp;day=".$prev_day.$param."\"><i class=\"fa fa-chevron-left\" title=\"".dol_escape_htmltag($langs->trans("Previous"))."\"></i></a> &nbsp;\n";
	}
    $nav.=" <span id=\"month_name\">".dol_print_date(dol_mktime(0,0,0,$first_month,$first_day,$first_year),"%Y").", ".$langs->trans("Week")." ".$week;
	$nav .= " </span>\n";
	if (DOL_VERSION < 6.0) {
    	$nav.="<a href=\"?year=".$next_year."&amp;month=".$next_month."&amp;day=".$next_day.$param."\">".img_next($langs->trans("Next"))."</a>\n";
	} else {
		$nav.=" &nbsp; <a href=\"?year=".$next_year."&amp;month=".$next_month."&amp;day=".$next_day.$param."\"><i class=\"fa fa-chevron-right\" title=\"".dol_escape_htmltag($langs->trans("Next"))."\"></i></a>\n";
	}
    $nav.=" &nbsp; (<a href=\"?year=".$nowyear."&amp;month=".$nowmonth."&amp;day=".$nowday.$param."\">".$langs->trans("Today")."</a>)";
	$picto = 'calendarweek';
}
if ($action=='show_day')
{

	if (DOL_VERSION < 6.0) {
   		$nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month."&amp;day=".$prev_day.$param."\">".img_previous($langs->trans("Previous"))."</a>\n";
	} else {
		$nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month."&amp;day=".$prev_day.$param."\"><i class=\"fa fa-chevron-left\"></i></a> &nbsp;\n";
	}
	$nav .= " <span id=\"month_name\">" . dol_print_date(dol_mktime(0, 0, 0, $month, $day, $year), "daytextshort");
	$nav .= " </span>\n";
	if (DOL_VERSION < 6.0) {
   		$nav.="<a href=\"?year=".$next_year."&amp;month=".$next_month."&amp;day=".$next_day.$param."\">".img_next($langs->trans("Next"))."</a>\n";
	} else {
		$nav.=" &nbsp; <a href=\"?year=".$next_year."&amp;month=".$next_month."&amp;day=".$next_day.$param."\"><i class=\"fa fa-chevron-right\"></i></a>\n";
	}
    $nav.=" &nbsp; (<a href=\"?year=".$nowyear."&amp;month=".$nowmonth."&amp;day=".$nowday.$param."\">".$langs->trans("Today")."</a>)";
	$picto = 'calendarday';
}

// Must be after the nav definition
$param .= '&year=' . $year . '&month=' . $month . ($day ? '&day=' . $day : '');
// print 'x'.$param;




$tabactive='';
if ($action == 'show_month') $tabactive='cardmonth';
if ($action == 'show_week') $tabactive='cardweek';
if ($action == 'show_day')  $tabactive='cardday';
if ($action == 'show_list') $tabactive='cardlist';

$paramnoaction=preg_replace('/action=[a-z_]+/','',$param);

$head = agf_calendars_prepare_head($paramnoaction);
dol_fiche_head($head, $tabactive, $langs->trans('AgfMenuAgenda'), 0, $picto);
$formagefodd->agenda_filter($form, $year, $month, $day, $filter_commercial, $filter_customer, $filter_contact, $filter_trainer, $canedit, '', '', $onlysession, $filter_type_session, $display_only_trainer_filter, $filter_location, $action,$filter_session_status,$filter_trainee);
dol_fiche_end();

$link = '';

print_fiche_titre($s,$link.' &nbsp; &nbsp; '.$nav, '');


// Get event in an array
$eventarray = array ();

$sql = 'SELECT ';
$sql.=" DISTINCT";
$sql.= ' a.id, a.label,';
$sql .= ' a.datep,';
$sql .= ' a.datep2,';
$sql .= ' a.percent,';
$sql.= ' a.fk_user_author,a.fk_user_action,';
$sql.= ' a.transparency, a.priority, a.fulldayevent, a.location,';
$sql.= ' a.fk_soc, a.fk_contact,';
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
if (! empty($filter_trainer)) {
	$sql .= ' ,trainer_session.trainer_status';
}
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
if (! empty($filter_trainer)) {
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'agefodd_session_formateur as trainer_session ON agf.rowid = trainer_session.fk_session ';
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as trainer ON trainer_session.fk_agefodd_formateur = trainer.rowid ";
	if (! empty($conf->global->AGF_DOL_TRAINER_AGENDA)) {
		$sql .= " AND ca.code='AC_AGF_SESST' ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur_calendrier as trainercal ON trainercal.fk_agefodd_session_formateur = trainer_session.rowid ";
	}
	$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . 'societe as socsess ON agf.fk_soc = socsess.rowid ';
}
if (! empty($filter_trainee)) {
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'agefodd_session_stagiaire as trainee_session ON agf.rowid = trainee_session.fk_session_agefodd ';
}

$sql .= ' WHERE agf.entity IN (' . getEntity('agefodd') . ')';
$sql .= ' AND a.elementtype=\'agefodd_agsession\'';
if ($action == 'show_day') {
	$sql .= " AND (";
    $sql.= " (a.datep BETWEEN '".$db->idate(dol_mktime(0,0,0,$month,$day,$year))."'";
	$sql .= " AND '" . $db->idate(dol_mktime(23, 59, 59, $month, $day, $year)) . "')";
	$sql .= " OR ";
    $sql.= " (a.datep2 BETWEEN '".$db->idate(dol_mktime(0,0,0,$month,$day,$year))."'";
	$sql .= " AND '" . $db->idate(dol_mktime(23, 59, 59, $month, $day, $year)) . "')";
	$sql .= " OR ";
    $sql.= " (a.datep < '".$db->idate(dol_mktime(0,0,0,$month,$day,$year))."'";
    $sql.= " AND a.datep2 > '".$db->idate(dol_mktime(23,59,59,$month,$day,$year))."')";
	$sql .= ')';
}
else
{
	// To limit array
	$sql .= " AND (";
    $sql.= " (a.datep BETWEEN '".$db->idate(dol_mktime(0,0,0,$month,1,$year)-(60*60*24*7))."'";   // Start 7 days before
    $sql.= " AND '".$db->idate(dol_mktime(23,59,59,$month,28,$year)+(60*60*24*10))."')";            // End 7 days after + 3 to go from 28 to 31
	$sql .= " OR ";
    $sql.= " (a.datep2 BETWEEN '".$db->idate(dol_mktime(0,0,0,$month,1,$year)-(60*60*24*7))."'";
	$sql .= " AND '" . $db->idate(dol_mktime(23, 59, 59, $month, 28, $year) + (60 * 60 * 24 * 10)) . "')";
	$sql .= " OR ";
    $sql.= " (a.datep < '".$db->idate(dol_mktime(0,0,0,$month,1,$year)-(60*60*24*7))."'";
    $sql.= " AND a.datep2 > '".$db->idate(dol_mktime(23,59,59,$month,28,$year)+(60*60*24*10))."')";
	$sql .= ')';
}

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
	$sql .= " AND trainer_session.fk_agefodd_formateur=" . $filter_trainer;
} else {
	$sql .= " AND ca.code<>'AC_AGF_SESST'";
}
if (! empty($onlysession) && empty($filter_trainer)) {
	$sql .= " AND ca.code='AC_AGF_SESS'";
}
if ($filter_type_session != '') {
	$sql .= " AND agf.type_session=" . $filter_type_session;
}
if (! empty($filter_location)) {
	$sql .= " AND agf.fk_session_place=" . $filter_location;
}
if (! empty($filter_session_status)) {
	$sql .= " AND agf.status IN (" . implode(',',$filter_session_status).")";
}
if (! empty($filter_trainee)) {
	$sql .= " AND trainee_session.fk_stagiaire=".$filter_trainee;
}

// Sort on date
$sql .= ' ORDER BY datep, agf.rowid';
// print $sql;

dol_syslog("agefodd/agenda/index.php", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$group_date=array();
    while ($i < $num)
    {
		$obj = $db->fetch_object($resql);

        // Discard auto action if option is on
        if (! empty($conf->global->AGENDA_ALWAYS_HIDE_AUTO) && $obj->type_code == 'AC_OTH_AUTO')
        {
        	$i++;
        	continue;
        }

        if (!empty($conf->global->AGF_GROUP_BY_DAY_CAL)) {
	        if (array_key_exists($obj->sessionid,$group_date) && $group_date[$obj->sessionid]==$obj->datep2) {
	        	$i++;
	        	continue;
	        }
        }

		// Create a new object action
		$event = new ActionComm($db);
		$event->id = $obj->id;
        $event->datep=$db->jdate($obj->datep);      // datep and datef are GMT date

        if (!empty($conf->global->AGF_GROUP_BY_DAY_CAL)) {
	        $sql_group_date="SELECT MAX(datep2) as maxtimasameday FROM " . MAIN_DB_PREFIX . "actioncomm as a";
	        $sql_group_date.=' WHERE a.fk_element='.$obj->sessionid.' AND a.elementtype=\'agefodd_agsession\'';
	        $sql_group_date.= ' AND YEAR(datep)='.dol_print_date($db->jdate($obj->datep),'%Y').' AND MONTH(datep)='.dol_print_date($db->jdate($obj->datep),'%m');
	        $sql_group_date.= ' AND DAY(datep)='.dol_print_date($db->jdate($obj->datep),'%d');
	        dol_syslog("agefodd/agenda/index.php", LOG_DEBUG);
	        $resql_group_date = $db->query($sql_group_date);
	        if ($resql_group_date)
	        {
	        	$obj_group_date = $db->fetch_object($resql_group_date);
	        	if (!empty($obj_group_date->maxtimasameday)){
	        		$group_date[$obj->sessionid]=$obj_group_date->maxtimasameday;

	        		$obj->datep2=$obj_group_date->maxtimasameday;
	        	}
	        }
        }

		$event->datef = $db->jdate($obj->datep2);
        $event->type_code=$obj->type_code;
        $event->type_label=$obj->type_label;
		$event->libelle = $obj->label;
		$event->percentage = $obj->percent;
        $event->authorid=$obj->fk_user_author;		// user id of creator
        $event->userownerid=$obj->fk_user_action;	// user id of owner
        $event->fetch_userassigned();				// This load $event->userassigned

		$event->sessionid = $obj->sessionid;
		$event->sessiontype = $obj->sessiontype;
		$event->sessionstatus = $obj->sessionstatus;
		if (! empty($filter_trainer)) {
			$event->trainer_status = $obj->trainer_status;
		}
		$event->priority = $obj->priority;
		$event->fulldayevent = $obj->fulldayevent;
		$event->location = $obj->location;
        $event->transparency=$obj->transparency;

		$event->societe->id = $obj->fk_soc;
		$event->contact->id = $obj->fk_contact;

		// Defined date_start_in_calendar and date_end_in_calendar property
		// They are date start and end of action but modified to not be outside calendar view.
        if ($event->percentage <= 0)
        {
			$event->date_start_in_calendar = $event->datep;
            if ($event->datef != '' && $event->datef >= $event->datep) $event->date_end_in_calendar=$event->datef;
            else $event->date_end_in_calendar=$event->datep;
        }
			else
        {
			$event->date_start_in_calendar = $event->datep;
            if ($event->datef != '' && $event->datef >= $event->datep) $event->date_end_in_calendar=$event->datef;
            else $event->date_end_in_calendar=$event->datep;
		}
		// Define ponctual property
        if ($event->date_start_in_calendar == $event->date_end_in_calendar)
        {
			$event->ponctuel = 1;
		}

		// Check values
        if ($event->date_end_in_calendar < $firstdaytoshow ||
        $event->date_start_in_calendar >= $lastdaytoshow)
        {
			// This record is out of visible range
        }
        else
        {
            if ($event->date_start_in_calendar < $firstdaytoshow) $event->date_start_in_calendar=$firstdaytoshow;
            if ($event->date_end_in_calendar >= $lastdaytoshow) $event->date_end_in_calendar=($lastdaytoshow-1);

				// Add an entry in actionarray for each day
			$daycursor = $event->date_start_in_calendar;
			$annee = date('Y', $daycursor);
			$mois = date('m', $daycursor);
			$jour = date('d', $daycursor);

			// Loop on each day covered by action to prepare an index to show on calendar
            $loop=true; $j=0;
			$daykey = dol_mktime(0, 0, 0, $mois, $jour, $annee);
            do
            {
                //if ($event->id==408) print 'daykey='.$daykey.' '.$event->datep.' '.$event->datef.'<br>';

				$eventarray [$daykey] [] = $event;
				$j ++;

				$daykey += 60 * 60 * 24;
                if ($daykey > $event->date_end_in_calendar) $loop=false;
            }
            while ($loop);

            //print 'Event '.$i.' id='.$event->id.' (start='.dol_print_date($event->datep).'-end='.dol_print_date($event->datef);
            //print ' startincalendar='.dol_print_date($event->date_start_in_calendar).'-endincalendar='.dol_print_date($event->date_end_in_calendar).') was added in '.$j.' different index key of array<br>';
		}
		$i ++;

	}
}
else
{
	dol_print_error($db);
}

$maxnbofchar = 30;
$cachethirdparties = array ();
$cachecontacts = array ();
$cacheusers=array();

// Define theme_datacolor array
$color_file = DOL_DOCUMENT_ROOT . "/theme/" . $conf->theme . "/graph-color.php";
if (is_readable($color_file))
{
	include_once $color_file;
}
if (! is_array($theme_datacolor)) $theme_datacolor=array(array(120,130,150), array(200,160,180), array(190,190,220));


if (empty($action) || $action == 'show_month') // View by month
{
	$newparam = $param; // newparam is for birthday links
	$newparam = preg_replace('/action=show_month&?/i', '', $newparam);
	$newparam = preg_replace('/action=show_week&?/i', '', $newparam);
	$newparam = preg_replace('/day=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/month=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/year=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/viewcal=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/type=trainer/i', '', $newparam);
	$newparam .= '&viewcal=1';
	if (DOL_VERSION < 6.0) {
		echo '<table width="100%" class="nocellnopadd cal_month">';
	} else {
		echo '<table width="100%" class="noborder nocellnopadd cal_pannel cal_month">';
	}

	echo ' <tr class="liste_titre">';
	$i = 0;
    while ($i < 7)
    {
		echo '  <td align="center">' . $langs->trans("Day" . (($i + (isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : 1)) % 7)) . "</td>\n";
		$i ++;
	}
	echo " </tr>\n";

	$todayarray = dol_getdate($now, 'fast');
	$todaytms = dol_mktime(0, 0, 0, $todayarray ['mon'], $todayarray ['mday'], $todayarray ['year']);

    // In loops, tmpday contains day nb in current month (can be zero or negative for days of previous month)
	// var_dump($eventarray);
    for ($iter_week = 0; $iter_week < 6 ; $iter_week++)
    {
		echo " <tr>\n";
        for ($iter_day = 0; $iter_day < 7; $iter_day++)
        {
			/* Show days before the beginning of the current month (previous month)  */
            if ($tmpday <= 0)
            {
				$style = 'cal_other_month cal_past';
        		if ($iter_day == 6) $style.=' cal_other_month_right';
				echo '  <td class="' . $style . ' nowrap" width="14%" valign="top">';
				show_day_events($db, $max_day_in_prev_month + $tmpday, $prev_month, $prev_year, $month, $style, $eventarray, $maxprint, $maxnbofchar, $newparam, 0, 60, $display_only_trainer_filter);
				echo "  </td>\n";
            }
				/* Show days of the current month */
            elseif ($tmpday <= $max_day_in_month)
            {
				$curtime = dol_mktime(0, 0, 0, $month, $tmpday, $year);
				$style = 'cal_current_month';
                if ($iter_day == 6) $style.=' cal_current_month_right';
				$today = 0;
                if ($todayarray['mday']==$tmpday && $todayarray['mon']==$month && $todayarray['year']==$year) $today=1;
                if ($today) $style='cal_today';
                if ($curtime < $todaytms) $style.=' cal_past';
				//var_dump($todayarray['mday']."==".$tmpday." && ".$todayarray['mon']."==".$month." && ".$todayarray['year']."==".$year.' -> '.$style);
				echo '  <td class="' . $style . ' nowrap" width="14%" valign="top">';
				show_day_events($db, $tmpday, $month, $year, $month, $style, $eventarray, $maxprint, $maxnbofchar, $newparam, 0, 60, $display_only_trainer_filter);
				echo "  </td>\n";
            }
				/* Show days after the current month (next month) */
            else
			{
				$style = 'cal_other_month';
                if ($iter_day == 6) $style.=' cal_other_month_right';
				echo '  <td class="' . $style . ' nowrap" width="14%" valign="top">';
				show_day_events($db, $tmpday - $max_day_in_month, $next_month, $next_year, $month, $style, $eventarray, $maxprint, $maxnbofchar, $newparam, 0, 60, $display_only_trainer_filter);
				echo "</td>\n";
			}
			$tmpday ++;
		}
		echo " </tr>\n";
	}
	echo "</table>\n";
    echo '<form id="move_event" action="" method="POST"><input type="hidden" name="action" value="mupdate">';
    echo '<input type="hidden" name="backtopage" value="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.dol_escape_htmltag($_SERVER['QUERY_STRING']).'">';
    echo '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    echo '<input type="hidden" name="newdate" id="newdate">' ;
    echo '</form>';

}
elseif ($action == 'show_week') // View by week
{
	$newparam = $param; // newparam is for birthday links
	$newparam = preg_replace('/action=show_month&?/i', '', $newparam);
	$newparam = preg_replace('/action=show_week&?/i', '', $newparam);
	$newparam = preg_replace('/day=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/month=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/year=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/viewweek=[0-9]+&?/i', '', $newparam);
	$newparam .= '&viewweek=1';
	if (DOL_VERSION < 6.0) {
		echo '<table width="100%" class="nocellnopadd cal_month">';
	} else {
		echo '<table width="100%" class="noborder nocellnopadd cal_pannel cal_month">';
	}
	echo ' <tr class="liste_titre">';
	$i = 0;
    while ($i < 7)
    {
		echo '  <td align="center">' . $langs->trans("Day" . (($i + (isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : 1)) % 7)) . "</td>\n";
		$i ++;
	}
	echo " </tr>\n";

	echo " <tr>\n";

    for ($iter_day = 0; $iter_day < 7; $iter_day++)
    {
			// Show days of the current week
		$curtime = dol_time_plus_duree($firstdaytoshow, $iter_day, 'd');
		$tmparray = dol_getdate($curtime, true);
		$tmpday = $tmparray['mday'];
		$tmpmonth = $tmparray['mon'];
		$tmpyear = $tmparray['year'];

			$style = 'cal_current_month';
        if ($iter_day == 6) $style.=' cal_other_month_right';
			$today = 0;
			$todayarray = dol_getdate($now, 'fast');
        if ($todayarray['mday']==$tmpday && $todayarray['mon']==$tmpmonth && $todayarray['year']==$tmpyear) $today=1;
        if ($today) $style='cal_today';

		echo '  <td class="'.$style.'" width="14%" valign="top">';
		show_day_events($db, $tmpday, $tmpmonth, $tmpyear, $month, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300, $display_only_trainer_filter);
		echo "  </td>\n";
	}
	echo " </tr>\n";

	echo "</table>\n";
    echo '<form id="move_event" action="" method="POST"><input type="hidden" name="action" value="mupdate">';
    echo '<input type="hidden" name="backtopage" value="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.dol_escape_htmltag($_SERVER['QUERY_STRING']).'">';
    echo '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    echo '<input type="hidden" name="newdate" id="newdate">' ;
    echo '</form>';
}
else    // View by day
{
	$newparam = $param; // newparam is for birthday links
	$newparam = preg_replace('/action=show_month&?/i', '', $newparam);
	$newparam = preg_replace('/action=show_week&?/i', '', $newparam);
	$newparam = preg_replace('/viewday=[0-9]+&?/i', '', $newparam);
	$newparam .= '&viewday=1';
	// Code to show just one day
    $style='cal_current_month cal_current_month_oneday';
	$today = 0;
	$todayarray = dol_getdate($now, 'fast');
    if ($todayarray['mday']==$day && $todayarray['mon']==$month && $todayarray['year']==$year) $today=1;
    //if ($today) $style='cal_today';

	$timestamp = dol_mktime(12, 0, 0, $month, $day, $year);
	$arraytimestamp = dol_getdate($timestamp);
	if (DOL_VERSION < 6.0) {
    	echo '<table width="100%" class="nocellnopadd cal_month">';
	} else {
		echo '<table width="100%" class="noborder nocellnopadd cal_pannel cal_month">';
	}
	echo ' <tr class="liste_titre">';
	echo '  <td align="center">' . $langs->trans("Day" . $arraytimestamp ['wday']) . "</td>\n";
	echo " </tr>\n";
	echo " <tr>\n";
    echo '  <td class="'.$style.'" width="14%" valign="top">';
	$maxnbofchar = 80;
	show_day_events($db, $day, $month, $year, $month, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300, $display_only_trainer_filter);
	echo "</td>\n";
	echo " </tr>\n";
	echo '</table>';
}

llxFooter();

$db->close();

/**
 * Show event of a particular day
 *
 * @param DoliDB $db handler
 * @param int $day
 * @param int $month
 * @param int $year
 * @param int $monthshown month shown in calendar view
 * @param string $style to use for this day
 * @param array	&$eventarray Array of events
 * @param int $maxprint of actions to show each day on month view (0 means no limit)
 * @param int $maxnbofchar of characters to show for event line
 * @param string $newparam on current URL
 * @param int $showinfo extended information (used by day view)
 * @param int $minheight height for each event. 60px by default.
 * @return void
 */
function show_day_events($db, $day, $month, $year, $monthshown, $style, &$eventarray, $maxprint = 0, $maxnbofchar = 16, $newparam = '', $showinfo = 0, $minheight = 60, $display_only_trainer_filter = 0) {
	global $user, $conf, $langs;
	global $action, $filter, $status, $actioncode, $filter_trainer;
	global $theme_datacolor;
    global $cachethirdparties, $cachecontacts, $cacheusers, $colorindexused;

	if (is_null($colorindexused)) $colorindexused = array();

    if (DOL_VERSION < 6.0) {
    	print "\n".'<div id="dayevent_'.sprintf("%04d",$year).sprintf("%02d",$month).sprintf("%02d",$day).'" class="dayevent">';
    } else {
    	$dateint = sprintf("%04d",$year).sprintf("%02d",$month).sprintf("%02d",$day);

    	print "\n";
    }

    // Line with title of day
	$curtime = dol_mktime(0, 0, 0, $month, $day, $year);
	if (DOL_VERSION < 6.0) {
	    print '<table class="nobordernopadding" width="100%">'."\n";
	} else {
		print '<div id="dayevent_'.$dateint.'" class="dayevent tagtable centpercent nobordernopadding">'."\n";
	}

	if (DOL_VERSION < 6.0) {
		print '<tr><td align="left" class="nowrap">';
	} else {
		print '<div class="tagtr"><div class="nowrap float">';
	}
	print '<a href="' . $_SERVER ['PHP_SELF'];
	print '?action=show_day&day=' . str_pad($day, 2, "0", STR_PAD_LEFT) . '&month=' . str_pad($month, 2, "0", STR_PAD_LEFT) . '&year=' . $year;
	print $newparam;
	print '">';
    if ($showinfo) print dol_print_date($curtime,'daytext');
    else print dol_print_date($curtime,'%d');
	print '</a>';
	if (DOL_VERSION < 6.0) {
		print '</td><td align="right" class="nowrap">';
	} else {
		print '</div><div class="floatright nowrap">';
	}
    if ($user->rights->agenda->myactions->create || $user->rights->agenda->allactions->create)
    {
		$newparam .= '&month=' . str_pad($month, 2, "0", STR_PAD_LEFT) . '&year=' . $year;

		// $param='month='.$monthshown.'&year='.$year;
		$hourminsec = '100000';
        print '<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&datep='.sprintf("%04d%02d%02d",$year,$month,$day).$hourminsec.'&backtopage='.urlencode($_SERVER["PHP_SELF"].($newparam?'?'.$newparam:'')).'">';
		print img_picto($langs->trans("NewAction"), 'edit_add.png');
		print '</a>';
	}
	if (DOL_VERSION < 6.0) {
	    print '</td></tr>'."\n";
	} else {
		print '</div></div>'."\n";
	}

    // Line with td contains all div of each events
	if (DOL_VERSION < 6.0) {
		print '<tr height="'.$minheight.'"><td valign="top" colspan="2" class="sortable" style="padding-bottom: 2px;">';
		print '<div style="width: 100%; position: relative;">';
	} else {
		print '<div class="tagtr">';
		print '<div class="tagtd centpercent agendacell sortable">';
	}

	// $curtime = dol_mktime (0, 0, 0, $month, $day, $year);
    $i=0; $nummytasks=0; $numother=0; $numbirthday=0; $numical=0; $numicals=array();
	$ymd = sprintf("%04d", $year) . sprintf("%02d", $month) . sprintf("%02d", $day);

    $nextindextouse=count($colorindexused);	// At first run this is 0, so fist user has 0, next 1, ...
	// print $nextindextouse;

    foreach ($eventarray as $daykey => $notused)
    {
		$annee = date('Y', $daykey);
		$mois = date('m', $daykey);
		$jour = date('d', $daykey);
        if ($day==$jour && $month==$mois && $year==$annee)
        {
            foreach ($eventarray[$daykey] as $index => $event)
            {
                if ($i < $maxprint || $maxprint == 0 || ! empty($conf->global->MAIN_JS_SWITCH_AGENDA))
                {
					$keysofuserassigned=array_keys($event->userassigned);

					$ponct = ($event->date_start_in_calendar == $event->date_end_in_calendar);

                    // Define $color (Hex string like '0088FF') and $cssclass of event
                    $color=-1; $colorindex=-1;
       				if (in_array($user->id, $keysofuserassigned))
					{
						$nummytasks++; $cssclass='family_mytasks';

						if (empty($cacheusers[$event->userownerid]))
						{
							$newuser=new User($db);
							$newuser->fetch($event->userownerid);
							$cacheusers[$event->userownerid]=$newuser;
						}
						//var_dump($cacheusers[$event->userownerid]->color);

                    	// We decide to choose color of owner of event (event->userownerid is user id of owner, event->userassigned contains all users assigned to event)
                    	if (! empty($cacheusers[$event->userownerid]->color)) $color=$cacheusers[$event->userownerid]->color;
                    }
                    /*else if ($event->type_code == 'ICALEVENT')
                    {
						$numical ++;
						if (! empty($event->icalname)) {
							if (! isset($numicals [dol_string_nospecial($event->icalname)])) {
								$numicals [dol_string_nospecial($event->icalname)] = 0;
							}
							$numicals [dol_string_nospecial($event->icalname)] ++;
						}
						$color = $event->icalcolor;
                    	$cssclass=(! empty($event->icalname)?'family_ext'.md5($event->icalname):'family_other unmovable');
                    }*/
                    else if ($event->type_code == 'BIRTHDAY')
                    {
                    	$numbirthday++; $colorindex=2; $cssclass='family_birthday unmovable'; $color=sprintf("%02x%02x%02x",$theme_datacolor[$colorindex][0],$theme_datacolor[$colorindex][1],$theme_datacolor[$colorindex][2]);
                    }
                    else
                 	{
                 		$numother++; $cssclass='family_other';

						if (empty($cacheusers[$event->userownerid]))
						{
							$newuser=new User($db);
							$newuser->fetch($event->userownerid);
							$cacheusers[$event->userownerid]=$newuser;
						}
						//var_dump($cacheusers[$event->userownerid]->color);

                    	// We decide to choose color of owner of event (event->userownerid is user id of owner, event->userassigned contains all users assigned to event)
                    	if (! empty($cacheusers[$event->userownerid]->color)) $color=$cacheusers[$event->userownerid]->color;
					}
					//if ($color == - 1) 					// Color was not forced. Set color according to color index.
					//{
					if (isset($event->trainer_status)) {
						if ($event->trainer_status == 0)
							$color = 'ffffcc';
						if ($event->trainer_status == 1)
							$color = '66ff99';
						if ($event->trainer_status == 2)
							$color = '33ff33';
						if ($event->trainer_status == 3)
							$color = '3366ff';
						if ($event->trainer_status == 4)
							$color = '33ccff';
						if ($event->trainer_status == 5)
							$color = 'cc6600';
						if ($event->trainer_status == 6)
							$color = 'cc0000';
					}
					if (isset($event->sessionstatus)) {
						if ($event->sessionstatus == 'ENV') {
							$colorbis = 'ffcc66';
						}
						elseif ($event->sessionstatus == 'CONF' || $event->sessionstatus == 'ONGOING') {
							$colorbis= '33cc00';
						}
						elseif ($event->sessionstatus == 'NOT') {
							$colorbis= 'ff6600';
						}
						elseif ($event->sessionstatus == 'ARCH') {
							$colorbis= 'c0c0c0';
						}
						elseif ($event->sessionstatus == 'DONE') {
							$colorbis= '4562c0';
						}
					} else {
						$colorbis= 'c0c0c0';
					}
					if ($color==-1) {
						$color='c0c0c0';
					}
					if (empty($colorbis)) {
						$colorbis=$color;
					}

					//}
					$cssclass = $cssclass . ' ' . $cssclass . '_day_' . $ymd;

                    // Defined style to disable drag and drop feature
                    if ($event->type_code =='AC_OTH_AUTO')
                    {
                        $cssclass.= " unmovable";
                    }
                    else if ($event->date_end_in_calendar && date('Ymd',$event->date_start_in_calendar) != date('Ymd',$event->date_end_in_calendar))
                    {
                        $tmpyearend    = date('Y',$event->date_end_in_calendar);
                        $tmpmonthend   = date('m',$event->date_end_in_calendar);
                        $tmpdayend     = date('d',$event->date_end_in_calendar);
                        if ($tmpyearend == $annee && $tmpmonthend == $mois && $tmpdayend == $jour)
                        {
                            $cssclass.= " unmovable";
                        }
                    }
                    else $cssclass.= "unmovable";

                    $h=''; $nowrapontd=1;
                    if ($action == 'show_day')  { $h='height: 100%; '; $nowrapontd=0; }
                    if ($action == 'show_week') { $h='height: 100%; '; $nowrapontd=0; }

					// Show rect of event
                    print "\n";

                    print '<!-- start event '.$i.' -->'."\n";
                    print '<div id="event_'.$ymd.'_'.$i.'" class="event '.$cssclass.'"';
                    //print ' style="height: 100px;';
                    //print ' position: absolute; top: 40px; width: 50%;';
                    //print '"';
                    print '>';
                    if (DOL_VERSION < 6.0) {
	                    print '<ul class="cal_event" style="'.$h.'">';	// always 1 li per ul, 1 ul per event
						print '<li class="cal_event" style="'.$h.'">';
						print '<table class="cal_event'.(empty($event->transparency)?'':' cal_event_busy').'" style="'.$h;
						print 'background: #'.$color.'; background: -webkit-gradient(linear, left top, left bottom, from(#'.$color.'), to(#'.$colorbis.'));';
                    } else {
                    	print '<table class="centpercent cal_event'.(empty($event->transparency)?'':' cal_event_busy').'" style="'.$h;
                    	print 'background: #'.$color.';';
                    	print 'background: -webkit-gradient(linear, left top, left bottom, from(#'.$color.'), to(#'.$colorbis.'));';
                    }
                    //if (! empty($event->transparency)) print 'background: #'.$color.'; background: -webkit-gradient(linear, left top, left bottom, from(#'.$color.'), to(#'.dol_color_minus($color,1).'));';
                    //else print 'background-color: transparent !important; background: none; border: 1px solid #bbb;';

                    if (DOL_VERSION < 6.0) {
                    	print ' -moz-border-radius:4px;" width="100%"><tr>';
                    	print '<td class="'.($nowrapontd?'nowrap ':'').'cal_event'.($event->type_code == 'BIRTHDAY'?' cal_event_birthday':'').'">';
                    } else {
                    	//print ' -moz-border-radius:4px;"';
                    	//print 'border: 1px solid #ccc" width="100%"';
                    	print '">';
                    	print '<tr>';
                    	print '<td class="tdoverflow nobottom centpercent '.($nowrapontd?'nowrap ':'').'cal_event'.($event->type_code == 'BIRTHDAY'?' cal_event_birthday':'').'">';

                    	$daterange='';
                    }
					if ($event->type_code == 'BIRTHDAY') 					// It's a birthday
					{
						print $event->getNomUrl(1, $maxnbofchar, 'cal_event', 'birthday', 'contact');
					}
                    if ($event->type_code != 'BIRTHDAY')
                    {
						// Picto
                        if (empty($event->fulldayevent))
                        {
							// print $event->getNomUrl(2).' ';
						}

						// Date
                        if (empty($event->fulldayevent))
                        {
							// print '<strong>';
							$daterange = '';

							// Show hours (start ... end)
							$tmpyearstart = date('Y', $event->date_start_in_calendar);
							$tmpmonthstart = date('m', $event->date_start_in_calendar);
							$tmpdaystart = date('d', $event->date_start_in_calendar);
							$tmpyearend = date('Y', $event->date_end_in_calendar);
							$tmpmonthend = date('m', $event->date_end_in_calendar);
							$tmpdayend = date('d', $event->date_end_in_calendar);
							// Hour start
                            if ($tmpyearstart == $annee && $tmpmonthstart == $mois && $tmpdaystart == $jour)
                            {
								$daterange .= dol_print_date($event->date_start_in_calendar, '%H:%M');
                                if ($event->date_end_in_calendar && $event->date_start_in_calendar != $event->date_end_in_calendar)
                                {
									if ($tmpyearstart == $tmpyearend && $tmpmonthstart == $tmpmonthend && $tmpdaystart == $tmpdayend)
										$daterange .= '-';
									// else
									// print '...';
								}
							}
                            if ($event->date_end_in_calendar && $event->date_start_in_calendar != $event->date_end_in_calendar)
                            {
                                if ($tmpyearstart != $tmpyearend || $tmpmonthstart != $tmpmonthend || $tmpdaystart != $tmpdayend)
                                {
									$daterange .= '...';
								}
							}
							// Hour end
                            if ($event->date_end_in_calendar && $event->date_start_in_calendar != $event->date_end_in_calendar)
                            {
								if ($tmpyearend == $annee && $tmpmonthend == $mois && $tmpdayend == $jour)
									$daterange .= dol_print_date($event->date_end_in_calendar, '%H:%M');
							}
							// print $daterange;
                            if ($event->type_code != 'ICALEVENT')
                            {
								$savlabel = $event->libelle;
								$event->libelle = $daterange;

								if (! empty($display_only_trainer_filter)) {
									if ($event->sessiontype == 0) {
										$event->libelle .= '-' . $langs->trans('AgfFormTypeSessionIntra');
									} else {
										$event->libelle .= '-' . $langs->trans('AgfFormTypeSessionInter');
									}
								}
								if (empty($filter_trainer) && ! empty($event->sessionid)) {
									$agf_trainer = new Agefodd_session_formateur($db);
									$result = $agf_trainer->fetch_formateur_per_session($event->sessionid);
									if ($result < 0) {
										setEventMessage($agf->error, 'errors');
									}

									$event->libelle .= '<BR>';
									if (is_array($agf_trainer->lines) && count($agf_trainer->lines) > 0) {
										$event->libelle .= '&nbsp;' . $langs->trans('AgfFormateur') . ':';
										foreach ( $agf_trainer->lines as $line ) {
											$event->libelle .= strtoupper($line->lastname) . ' ' . ucfirst($line->firstname) . ',';
										}
									} else {
										$event->libelle .= $langs->trans('AgfNobodyTrainer');
									}
								}

								print $event->getNomUrl(0);

								$event->libelle = $savlabel;
                            }
                            else
                            {
								print $daterange;
							}
							// print '</strong> ';
							print "<br>\n";
                        }
                        else
						{
                            if ($showinfo)
                            {
								print $langs->trans("EventOnFullDay") . "<br>\n";
							}
						}

						// Show title
						if ($event->type_code == 'ICALEVENT')
							print dol_trunc($event->libelle, $maxnbofchar);
						else
							print '<a href="../session/card.php?id=' . $event->sessionid . '">' . $event->sessionid . '</a> - ' . $event->getNomUrl(0, $maxnbofchar, 'cal_event');

						if ($event->type_code == 'ICALEVENT')
							print '<br>(' . dol_trunc($event->icalname, $maxnbofchar) . ')';

							// If action related to company / contact
                        $linerelatedto='';$length=16;
                        if (! empty($event->societe->id) && ! empty($event->contact->id)) $length=round($length/2);
                        if (! empty($event->societe->id) && $event->societe->id > 0)
                        {
                            if (! isset($cachethirdparties[$event->societe->id]) || ! is_object($cachethirdparties[$event->societe->id]))
                            {
								$thirdparty = new Societe($db);
								$thirdparty->fetch($event->societe->id);
								$cachethirdparties [$event->societe->id] = $thirdparty;
							}
                            else $thirdparty=$cachethirdparties[$event->societe->id];
                            if (! empty($thirdparty->id)) $linerelatedto.=$thirdparty->getNomUrl(1,'',$length);
                        }
                        if (! empty($event->contact->id) && $event->contact->id > 0)
                        {
                            if (! is_object($cachecontacts[$event->contact->id]))
                            {
								$contact = new Contact($db);
								$contact->fetch($event->contact->id);
								$cachecontacts [$event->contact->id] = $contact;
							}
                            else $contact=$cachecontacts[$event->contact->id];
                            if ($linerelatedto) $linerelatedto.=' / ';
                            if (! empty($contact->id)) $linerelatedto.=$contact->getNomUrl(1,'',$length);
                        }
                        if ($linerelatedto) print '<br>'.$linerelatedto;
					}

					// Show location
                    if ($showinfo)
                    {
                        if ($event->location)
                        {
							print '<br>';
							print $langs->trans("Location") . ': ' . $event->location;
						}
					}

					print '</td>';
					// Status - Percent
					print '<td align="right" class="nowrap">';

					if (! empty($event->sessionid) && $showinfo) {

						require_once '../class/agsession.class.php';
						$agf = new Agsession($db);
						$result = $agf->fetch($event->sessionid);
						if ($result < 0) {
							setEventMessage($agf->error, 'errors');
						}

						if (empty($extralabels)) {
							$extrafields = new ExtraFields($db);
							$extralabels = $extrafields->fetch_name_optionals_label($agf->table_element, true);
						}

						require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
						$product = new Product($db);
						if (! empty($agf->fk_product)) {
							$result = $product->fetch($agf->fk_product);
							if ($result < 0) {
								setEventMessage($agf->error, 'errors');
							}
						}

						require_once '../class/agefodd_session_formateur.class.php';
						$agf_trainer = new Agefodd_session_formateur($db);
						$result = $agf_trainer->fetch_formateur_per_session($event->sessionid);
						if ($result < 0) {
							setEventMessage($agf->error, 'errors');
						}

						if (! empty($product->id)) {
							print $langs->trans("Product") . ': ' . $product->getNomUrl(1);
						}
						print '&nbsp;' . $langs->trans('AgfDuree') . ':' . $agf->duree_session;
						print '<BR>';
						print '&nbsp;' . $langs->trans('AgfParticipants') . ':' . $agf->nb_stagiaire;
						print '&nbsp;' . $langs->trans('AgfSessionCommercial') . ':' . $agf->commercialname;

						print '<BR>';

						if (is_array($agf_trainer->lines) && count($agf_trainer->lines) > 0) {
							print '&nbsp;' . $langs->trans('AgfFormateur') . ':';
							foreach ( $agf_trainer->lines as $line ) {
								print strtoupper($line->lastname) . ' ' . ucfirst($line->firstname) . ',';
							}
						}
					}
					print '</td></tr></table>';
					if (DOL_VERSION < 6.0) {
                    	print '</li>';
                    	print '</ul>';
					}
					print '</div><!-- end event '.$i.' -->'."\n";
					$i ++;
				}
				else
				{
					print '<a href="' . $_SERVER ['PHP_SELF'] . '?month=' . $monthshown . '&year=' . $year;
					print ($status ? '&status=' . $status : '') . ($filter ? '&filter=' . $filter : '');
					$newparam = preg_replace('/maxprint=[0-9]+&?/i', 'maxprint=0', $newparam);
					print $newparam;
					print '">' . img_picto("all", "1downarrow_selected.png") . ' ...';
					print ' +' . (count($eventarray [$daykey]) - $maxprint);
					print '</a>';
					break;
					// $ok=false; // To avoid to show twice the link
				}
			}

			break;
		}
	}
    if (! $i) print '&nbsp;';

    if (! empty($conf->global->MAIN_JS_SWITCH_AGENDA) && $i > $maxprint && $maxprint)
    {
		print '<div id="more_' . $ymd . '">' . img_picto("all", "1downarrow_selected.png") . ' +' . $langs->trans("More") . '...</div>';
		// print ' +'.(count($eventarray[$daykey])-$maxprint);
		print '<script type="text/javascript">' . "\n";
		print 'jQuery(document).ready(function () {' . "\n";
		print 'jQuery("#more_' . $ymd . '").click(function() { reinit_day_' . $ymd . '(); });' . "\n";

		print 'function reinit_day_' . $ymd . '() {' . "\n";
		print 'var nb=0;' . "\n";
		// TODO Loop on each element of day $ymd and start to toggle once $maxprint has been reached
		print 'jQuery(".family_mytasks_day_' . $ymd . '").toggle();';
		print '}' . "\n";

		print '});' . "\n";

		print '</script>' . "\n";
	}

	if (DOL_VERSION < 6.0) {
    	print '</div>';
		print '</td></tr></table>';
	} else {
		print '</div></div>';       // td tr
	}

	print '</div>';             // table
	print "\n";
}


/**
 * Change color with a delta
 *
 * @param	string	$color		Color
 * @param 	int		$minus		Delta
 * @return	string				New color
 */
function dol_color_minus($color, $minus)
{
	$newcolor = $color;
	$newcolor [0] = ((hexdec($newcolor [0]) - $minus) < 0) ? 0 : dechex((hexdec($newcolor [0]) - $minus));
	$newcolor [2] = ((hexdec($newcolor [2]) - $minus) < 0) ? 0 : dechex((hexdec($newcolor [2]) - $minus));
	$newcolor [4] = ((hexdec($newcolor [4]) - $minus) < 0) ? 0 : dechex((hexdec($newcolor [4]) - $minus));
	return $newcolor;
}
