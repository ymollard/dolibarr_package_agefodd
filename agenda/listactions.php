<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013-2014 Florian HEnry		<florian.henry@open-concept.pro>
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
 * \file agefodd/agenda/listactions.php
 * \ingroup agefodd
 * \brief Page to list actions
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

require_once '../lib/agefodd.lib.php';
require_once '../class/html.formagefodd.class.php';
require_once '../class/agefodd_session_formateur.class.php';
require_once '../class/agefodd_formateur.class.php';

$langs->load("companies");
$langs->load("agenda");
$langs->load("commercial");
$langs->load("agefodd@agefodd");

$action = GETPOST('action', 'alpha');
$year = GETPOST("year", 'int');
$month = GETPOST("month", 'int');
$day = GETPOST("day", 'int');
$actioncode = GETPOST("actioncode", "alpha", 3);
$pid = GETPOST("projectid", 'int', 3);
$status = GETPOST("status", 'alpha');
$type = GETPOST('type', 'none');
$actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode", 'none') == '0' ? '0' : (empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 'AC_OTH' : ''));
$dateselect = dol_mktime(0, 0, 0, GETPOST('dateselectmonth', 'none'), GETPOST('dateselectday', 'none'), GETPOST('dateselectyear', 'none'));
$datestart = dol_mktime(0, 0, 0, GETPOST('datestartmonth', 'none'), GETPOST('datestartday', 'none'), GETPOST('datestartyear', 'none'));
$dateend = dol_mktime(0, 0, 0, GETPOST('dateendmonth', 'none'), GETPOST('dateendday', 'none'), GETPOST('dateendyear', 'none'));

if ($actioncode == '')
	$actioncode = (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE);
if ($status == '' && ! isset($_GET['status']) && ! isset($_POST['status']))
	$status = (empty($conf->global->AGENDA_DEFAULT_FILTER_STATUS) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_STATUS);
if (empty($action) && ! isset($_GET['action']) && ! isset($_POST['action']))
	$action = (empty($conf->global->AGENDA_DEFAULT_VIEW) ? 'show_month' : $conf->global->AGENDA_DEFAULT_VIEW);
$display_only_trainer_filter = GETPOST('displayonlytrainerfilter', 'int');

$filterdatestart = dol_mktime(0, 0, 0, GETPOST('dt_start_filtermonth', 'int'), GETPOST('dt_start_filterday', 'int'), GETPOST('dt_start_filteryear', 'int'));
$filterdatesend = dol_mktime(0, 0, 0, GETPOST('dt_end_filtermonth', 'int'), GETPOST('dt_end_filterday', 'int'), GETPOST('dt_end_filteryear', 'int'));
$onlysession = GETPOST('onlysession', 'int');
if ($onlysession != '0') {
	$onlysession = 1;
}

$filter = GETPOST("filter", '', 3);
$filter_commercial = GETPOST('commercial', 'int');
$filter_customer = GETPOST('fk_soc', 'int');
$filter_contact = GETPOST('contact', 'int');
$filter_trainer = GETPOST('trainerid', 'int');
$filter_trainee = GETPOST('traineeid', 'int');
$filter_type_session = GETPOST('type_session', 'int');
$filter_location = GETPOST('location', 'int');
$filter_session_status = GETPOST('search_session_status', 'array');
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
if ($filter_trainee == - 1) {
	$filter_trainee = 0;
}
if ($filter_type_session == - 1) {
	$filter_type_session = '';
}
if ($filter_location == - 1) {
	$filter_location = '';
}
$showbirthday = empty($conf->use_javascript_ajax) ? GETPOST("showbirthday", "int") : 1;

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;

if (empty($page) || $page == - 1) {
	$page = 0;
}

$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (! $sortorder) {
	$sortorder = "DESC";
	if ($status == 'todo')
		$sortorder = "ASC";
	if ($status == 'done')
		$sortorder = "DESC";
}
if (! $sortfield) {
	$sortfield = "a.datep,a.datep2";
	if ($status == 'todo')
		$sortfield = "a.datep";
	if ($status == 'done')
		$sortfield = "a.datep2";
}

$canedit = 1;
// Security check
if (! $user->rights->agefodd->agenda) {
	accessforbidden();
}

$canedit = 1;

if ($user->rights->agefodd->agendatrainer && ! $user->rights->agefodd->agenda) {
	header("Location: " . dol_buildpath('/agefodd/agenda/pertrainer.php', 1));
	exit();
}

if ($user->rights->agefodd->session->trainer) {
	$type = 'trainer';
}
if ($type == 'trainer') {
	$canedit = 0;

	$agf_trainer = new Agefodd_teacher($db);
	$result = $agf_trainer->fetch_all('', '', '', '', 0, array(
			'f.fk_user' => $user->id
	));
	if ($result < 0) {
		setEventMessages(null, $agf_trainer->errors, 'errors');
	} else {
		if (is_array($agf_trainer->lines) && count($agf_trainer->lines) > 0) {
			$filter_trainer = $agf_trainer->lines[0]->id;
		} else {
			accessforbidden();
		}
	}
} else {
	if (! $user->rights->agefodd->agenda)
		accessforbidden();
}

if ($type == 'trainerext' && ! empty($user->contact_id)) {
	// In this case this is an external trainer
	$agf_trainer = new Agefodd_teacher($db);
	$result = $agf_trainer->fetch_all('', '', '', '', 0, array(
			'f.fk_socpeople' => $user->contact_id
	));
	if ($result < 0) {
		setEventMessages(null, $agf_trainer->errors, 'errors');
	} else {
		if (is_array($agf_trainer->lines) && count($agf_trainer->lines) > 0) {
			$filter_trainer = $agf_trainer->lines[0]->id;
		} else {
			accessforbidden();
		}
	}
}

/*
 *	Actions
 */

if (GETPOST("viewcal", 'none') || GETPOST("viewweek", 'none') || GETPOST("viewday", 'none')) {
	$param = '';
	foreach ( $_POST as $key => $val ) {
		$param .= '&' . $key . '=' . urlencode($val);
	}
	// print $param;
	header("Location: " . dol_buildpath('/agefodd/agenda/index.php', 1) . '?' . $param);
	exit();
}

/*
 *  View
 */

$now = dol_now();

$help_url = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda';

$form = new Form($db);

// Define list of all external calendars
$listofextcals = array();
/*if (empty($conf->global->AGENDA_DISABLE_EXT) && $conf->global->AGENDA_EXT_NB > 0)
 {
 $i=0;
 while($i < $conf->global->AGENDA_EXT_NB)
 {
 $i++;
 $paramkey='AGENDA_EXT_SRC'.$i;
 $url=$conf->global->$paramkey;
 $paramkey='AGENDA_EXT_NAME'.$i;
 $namecal = $conf->global->$paramkey;
 $paramkey='AGENDA_EXT_COLOR'.$i;
 $colorcal = $conf->global->$paramkey;
 if ($url && $namecal) $listofextcals[]=array('src'=>$url,'name'=>$namecal,'color'=>$colorcal);
 }
 }
 */

$param = '';
if ($status) {
	$param = "&status=" . $status;
}
if ($filter) {
	$param .= "&filter=" . $filter;
}
if ($filtera) {
	$param .= "&filtera=" . $filtera;
}
if ($filterd) {
	$param .= "&filterd=" . $filterd;
}
if ($socid) {
	$param .= "&socid=" . $socid;
}
if ($showbirthday) {
	$param .= "&showbirthday=1";
}
if ($pid) {
	$param .= "&projectid=" . $pid;
}
if ($type) {
	$param .= "&type=" . $type;
}
if ($actioncode) {
	$param .= "&actioncode=" . $actioncode;
}
if ($filter_type_session != '') {
	$param .= '&type_session=' . $filter_type_session;
}
if ($filter_location != - 1) {
	$param .= '&location=' . $filter_location;
}
if (! empty($filter_trainer)) {
	$param .= '&trainerid=' . $filter_trainer;
}
if (! empty($filter_trainee)) {
	$param .= '&traineeid=' . $filter_trainee;
}
if (is_array($filter_session_status) && count($filter_session_status) > 0) {
	foreach ( $filter_session_status as $val ) {
		$param .= '&search_session_status[]=' . $val;
	}
}
if (! empty($filterdatestart))
	$param .= "&dt_start_filtermonth=" . GETPOST('dt_start_filtermonth', 'int') . '&dt_start_filterday=' . GETPOST('dt_start_filterday', 'int') . '&dt_start_filteryear=' . GETPOST('dt_start_filteryear', 'int');
if (! empty($filterdatesend))
	$param .= "&dt_end_filtermonth=" . GETPOST('dt_end_filtermonth', 'int') . '&dt_end_filterday=' . GETPOST('dt_end_filterday', 'int') . '&dt_end_filteryear=' . GETPOST('dt_end_filteryear', 'int');
if (! empty($onlysession))
	$param .= "&onlysession=" . $onlysession;

llxHeader('', $langs->trans("Agenda"), $help_url, '', 0, 0, '', '', $param);

$sql = "SELECT DISTINCT s.nom as societe, s.rowid as socid, s.client,";
$sql .= " a.id, a.datep as dp, a.datep2 as dp2,";
$sql .= " a.fk_contact, a.note, a.label, a.percent as percent,";
$sql .= " c.code as acode, c.libelle,";
$sql .= " ua.login as loginauthor, ua.rowid as useridauthor,";
$sql .= " ut.login as logintodo, ut.rowid as useridtodo,";
$sql .= " ud.login as logindone, ud.rowid as useriddone,";
$sql .= " sp.lastname, sp.firstname";
$sql .= ' ,agf.rowid as sessionid';
$sql .= ' ,agf_status.code as sessionstatus';
if (! empty($filter_trainer)) {
	$sql .= ' ,socsess.rowid as socid';
	$sql .= ' ,socsess.nom as societe';
	$sql .= ', socsess.client';
} else {
	$sql .= ' ,s.rowid as socid';
	$sql .= ' ,s.nom as societe';
	$sql .= ' ,s.client';
}

$sql .= " FROM " . MAIN_DB_PREFIX . "c_actioncomm as c,";
$sql .= " " . MAIN_DB_PREFIX . 'user as u,';
$sql .= " " . MAIN_DB_PREFIX . "actioncomm as a";
if (! $user->rights->societe->client->voir && ! $socid)
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON a.fk_soc = s.rowid";
$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'c_actioncomm as ca ON a.fk_action = ca.id';
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sp ON a.fk_contact = sp.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as ua ON a.fk_user_author = ua.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as ut ON a.fk_user_action = ut.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as ud ON a.fk_user_done = ud.rowid";
$sql .= " INNER JOIN " . MAIN_DB_PREFIX . 'agefodd_session as agf ON agf.rowid = a.fk_element AND a.elementtype=\'agefodd_agsession\'';
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
$sql .= " WHERE c.id = a.fk_action";
$sql .= ' AND a.fk_user_author = u.rowid';
$sql .= ' AND a.entity IN (' . getEntity('agefodd' /*'session'*/
) . ')'; // To limit to entity
if ($pid)
	$sql .= " AND a.fk_project=" . $db->escape($pid);
if (! $user->rights->societe->client->voir && ! $socid)
	$sql .= " AND (a.fk_soc IS NULL OR sc.fk_user = " . $user->id . ")";
if ($socid) {
	$sql .= " AND s.rowid = " . $socid;
}
if ($status == 'done') {
	$sql .= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep2 <= '" . $db->idate($now) . "'))";
}
if ($status == 'todo') {
	$sql .= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep2 > '" . $db->idate($now) . "'))";
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

	if ($type == 'trainer') {
		$sql .= " AND trainer.fk_user=" . $filter_trainer;
	} else {
		$sql .= " AND trainer_session.fk_agefodd_formateur=" . $filter_trainer;
	}
} else {
	$sql .= " AND ca.code<>'AC_AGF_SESST'";
}
if (! empty($filterdatestart)) {
	$sql .= ' AND a.datep>=\'' . $db->idate($filterdatestart) . '\'';
}
if (! empty($filterdatesend)) {
	$sql .= ' AND a.datep2<=\'' . $db->idate($filterdatesend) . '\'';
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
	$sql .= " AND agf.status IN (" . implode(',', $filter_session_status) . ")";
}
if (! empty($filter_trainee)) {
	$sql .= " AND trainee_session.fk_stagiaire=" . $filter_trainee;
}
$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);
	if ($resql) {
		$nbtotalofrecords = $db->num_rows($resql);
	}
}

$sql .= $db->plimit($limit + 1, $offset);
// print $sql;

dol_syslog("agefodd/agenda/listactions.php");
$resql = $db->query($sql);
if ($resql) {
	$actionstatic = new ActionComm($db);
	$societestatic = new Societe($db);
	$formagefodd = new FormAgefodd($db);

	$num = $db->num_rows($resql);

	$title = $langs->trans("DoneAndToDoActions");
	if ($status == 'done')
		$title = $langs->trans("DoneActions");
	if ($status == 'todo')
		$title = $langs->trans("ToDoActions");

	if ($socid) {
		$societe = new Societe($db);
		$societe->fetch($socid);
		$newtitle = $langs->trans($title) . ' ' . $langs->trans("For") . ' ' . $societe->nom;
	} else {
		$newtitle = $langs->trans($title);
	}

	$head = agf_calendars_prepare_head($paramnoaction);

	dol_fiche_head($head, 'cardlist', $langs->trans('AgfMenuAgenda'), 0, $picto);
	$formagefodd->agenda_filter($form, $year, $month, $day, $filter_commercial, $filter_customer, $filter_contact, $filter_trainer, $canedit, $filterdatestart, $filterdatesend, $onlysession, $filter_type_session, $display_only_trainer_filter, $filter_location, $action, $filter_session_status,
			$filter_trainee);
	dol_fiche_end();

	// Add link to show birthdays
	$link = '';

	print_barre_liste($newtitle, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $link, $num, $nbtotalofrecords, '', 0, '', '', $limit);
	// print '<br>';

	$i = 0;
	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Action"), $_SERVER["PHP_SELF"], "a.label", $param, "", "", $sortfield, $sortorder);
	// print_liste_field_titre($langs->trans("Title"),$_SERVER["PHP_SELF"],"a.label",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateStart"), $_SERVER["PHP_SELF"], "a.datep,a.datep2", $param, '', 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("DateEnd"), $_SERVER["PHP_SELF"], "a.datep2", $param, '', 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Company"), $_SERVER["PHP_SELF"], "s.nom", $param, "", "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Contact"), $_SERVER["PHP_SELF"], "a.fk_contact", $param, "", "", $sortfield, $sortorder);
	// print_liste_field_titre($langs->trans("ActionUserAsk"), $_SERVER["PHP_SELF"], "ua.login", $param, "", "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AffectedTo"), $_SERVER["PHP_SELF"], "ut.login", $param, "", "", $sortfield, $sortorder);
	// print_liste_field_titre($langs->trans("DoneBy"), $_SERVER["PHP_SELF"], "ud.login", $param, "", "", $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"], "a.percent", $param, "", 'align="right"', $sortfield, $sortorder);
	print "</tr>\n";

	$contactstatic = new Contact($db);
	$now = dol_now();
	$delay_warning = $conf->global->MAIN_DELAY_ACTIONS_TODO * 24 * 60 * 60;

	$var = true;
	while ( $i < min($num, $limit) ) {
		$obj = $db->fetch_object($resql);

		$var = ! $var;

		print "<tr $bc[$var]>";

		// Action (type)
		print '<td>';
		print '<a href="../session/card.php?id=' . $obj->sessionid . '">' . $obj->sessionid . '</a> - ';
		$actionstatic->id = $obj->id;
		$actionstatic->type_code = $obj->acode;
		$actionstatic->libelle = $obj->label;
		print $actionstatic->getNomUrl(1);
		print '</td>';

		// Titre
		// print '<td>';
		// print dol_trunc($obj->label,12);
		// print '</td>';

		print '<td align="center" class="nowrap">';
		print dol_print_date($db->jdate($obj->dp), "dayhour");
		$late = 0;
		if ($obj->percent == 0 && $obj->dp && $db->jdate($obj->dp) < ($now - $delay_warning))
			$late = 1;
		if ($obj->percent == 0 && ! $obj->dp && $obj->dp2 && $db->jdate($obj->dp) < ($now - $delay_warning))
			$late = 1;
		if ($obj->percent > 0 && $obj->percent < 100 && $obj->dp2 && $db->jdate($obj->dp2) < ($now - $delay_warning))
			$late = 1;
		if ($obj->percent > 0 && $obj->percent < 100 && ! $obj->dp2 && $obj->dp && $db->jdate($obj->dp) < ($now - $delay_warning))
			$late = 1;
		if ($late)
			print img_warning($langs->trans("Late")) . ' ';
		print '</td>';

		print '<td align="center" class="nowrap">';
		print dol_print_date($db->jdate($obj->dp2), "dayhour");
		print '</td>';

		// Third party
		print '<td>';
		if ($obj->socid) {
			$societestatic->id = $obj->socid;
			$societestatic->client = $obj->client;
			$societestatic->nom = $obj->societe;
			print $societestatic->getNomUrl(1, '', 10);
		} else
			print '&nbsp;';
		print '</td>';

		// Contact
		print '<td>';
		if ($obj->fk_contact > 0) {
			$contactstatic->lastname = $obj->lastname;
			$contactstatic->firstname = $obj->firstname;
			$contactstatic->id = $obj->fk_contact;
			print $contactstatic->getNomUrl(1, '', 10);
		} else {
			print "&nbsp;";
		}
		print '</td>';

		// User author
		/*print '<td align="left">';
		 if ($obj->useridauthor) {
		 $userstatic = new User($db);
		 $userstatic->id = $obj->useridauthor;
		 $userstatic->login = $obj->loginauthor;
		 print $userstatic->getLoginUrl(1);
		 } else
		 print '&nbsp;';
		 print '</td>';*/

		// User to do
		print '<td align="left">';
		if ($obj->useridtodo) {
			$userstatic = new User($db);
			$userstatic->id = $obj->useridtodo;
			$userstatic->login = $obj->logintodo;
			print $userstatic->getLoginUrl(1);
		} else
			print '&nbsp;';
		print '</td>';

		/*// User did
		 print '<td align="left">';
		 if ($obj->useriddone) {
		 $userstatic = new User($db);
		 $userstatic->id = $obj->useriddone;
		 $userstatic->login = $obj->logindone;
		 print $userstatic->getLoginUrl(1);
		 } else
		 print '&nbsp;';
		 print '</td>';*/

		// Status/Percent
		print '<td align="right" class="nowrap">' . $langs->trans('AgfStatusSession_' . $obj->sessionstatus) . '</td>';

		print "</tr>\n";
		$i ++;
	}
	print "</table>";
	$db->free($resql);
} else {
	dol_print_error($db);
}

$db->close();
llxFooter();
