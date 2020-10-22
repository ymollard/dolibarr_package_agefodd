
<?php
/* Copyright (C) 2013 Florian Henry  <florian.henry@open-concept.pro>
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
 * \file /agefodd/scripts/usdate_session_cost_price.php
 * \brief Generate script
 */
if (! defined('NOTOKENRENEWAL'))
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))
	define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))
	define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))
	define('NOREQUIREAJAX', '1');
if (! defined('NOLOGIN'))
	define('NOLOGIN', '1');

$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

dol_include_once('/user/class/user.class.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_session_element.class.php');

$userlogin = GETPOST('login', 'none');
$key = GETPOST('key', 'alpha');

// Security test
if ($key != $conf->global->WEBSERVICES_KEY) {
	print - 1;
	exit();
}

$user = new User($db);
$result = $user->fetch('', $userlogin);
if (empty($user->id)) {
	print - 1;
	exit();
}

if ($result > 0 && ! empty($user->id)) {

	//Set to client facture ou session confirmée dans la dernière année (J – 400 jours pour être un peu plus large)
	$sql = "UPDATE " . MAIN_DB_PREFIX . "societe SET client=1, tms=tms ";
	$sql .= " WHERE	fournisseur=0 ";
	$sql .= " AND client<>1 ";
	$sql .= " AND ((rowid IN (SELECT fk_soc FROM " . MAIN_DB_PREFIX . "facture WHERE date_valid > DATE_ADD(NOW(), INTERVAL -400 DAY))) ";
	$sql .= " OR (rowid IN (SELECT fk_soc_requester FROM " . MAIN_DB_PREFIX . "agefodd_session WHERE dated > DATE_ADD(NOW(), INTERVAL -400 DAY) AND status NOT IN (1,3))) ";
	$sql .= " OR (rowid IN (SELECT fk_soc FROM " . MAIN_DB_PREFIX . "agefodd_session WHERE dated > DATE_ADD(NOW(), INTERVAL -400 DAY) AND status NOT IN (1,3)))) ";

	dol_syslog('/agefodd/scripts/update_customer_status.php:Set to client facture ou session confirmée dans la dernière année (J – 400 jours pour être un peu plus large): sql='.$sql,LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		print 1;
	} else {
		print '/agefodd/scripts/update_customer_status.php:Set to client facture ou session confirmée dans la dernière année (J – 400 jours pour être un peu plus large): sql='.$sql;
		print - 1 . $db->lasterror();
	}

	//Prospect/Client facture ou session confirmée depuis plus d’un an (J + 400 jours)
	$sql = "UPDATE " . MAIN_DB_PREFIX . "societe SET client=3, tms=tms  ";
	$sql .= "WHERE fournisseur=0  ";
	$sql .= "AND client<>3  ";
	$sql .= "AND (rowid IN (SELECT " . MAIN_DB_PREFIX . "facture.fk_soc FROM " . MAIN_DB_PREFIX . "facture WHERE " . MAIN_DB_PREFIX . "societe.rowid=" . MAIN_DB_PREFIX . "facture.fk_soc GROUP BY " . MAIN_DB_PREFIX . "facture.fk_soc HAVING MAX(" . MAIN_DB_PREFIX . "facture.date_valid)< DATE_ADD(NOW(), INTERVAL -400 DAY))  ";
	$sql .= "OR rowid IN (SELECT fk_soc_requester FROM " . MAIN_DB_PREFIX . "agefodd_session WHERE " . MAIN_DB_PREFIX . "societe.rowid=" . MAIN_DB_PREFIX . "agefodd_session.fk_soc_requester AND " . MAIN_DB_PREFIX . "agefodd_session.status NOT IN (1,3) GROUP BY " . MAIN_DB_PREFIX . "agefodd_session.fk_soc_requester HAVING MAX(" . MAIN_DB_PREFIX . "agefodd_session.dated)< DATE_ADD(NOW(), INTERVAL -400 DAY))  ";
	$sql .= "OR rowid IN (SELECT fk_soc FROM " . MAIN_DB_PREFIX . "agefodd_session WHERE " . MAIN_DB_PREFIX . "societe.rowid=" . MAIN_DB_PREFIX . "agefodd_session.fk_soc AND " . MAIN_DB_PREFIX . "agefodd_session.status NOT IN (1,3) GROUP BY " . MAIN_DB_PREFIX . "agefodd_session.fk_soc HAVING MAX(" . MAIN_DB_PREFIX . "agefodd_session.dated)< DATE_ADD(NOW(), INTERVAL -400 DAY))  ";
	$sql .= "AND rowid NOT IN (SELECT " . MAIN_DB_PREFIX . "facture.fk_soc FROM " . MAIN_DB_PREFIX . "facture WHERE " . MAIN_DB_PREFIX . "societe.rowid=" . MAIN_DB_PREFIX . "facture.fk_soc GROUP BY " . MAIN_DB_PREFIX . "facture.fk_soc HAVING MAX(" . MAIN_DB_PREFIX . "facture.date_valid) > DATE_ADD(NOW(), INTERVAL -400 DAY)))";

	dol_syslog('/agefodd/scripts/update_customer_status.php:Prospect/Client facture ou session confirmée depuis plus d’un an (J + 400 jours): sql='.$sql,LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		print 1;
	} else {
		print '/agefodd/scripts/update_customer_status.php:Prospect/Client facture ou session confirmée depuis plus d’un an (J + 400 jours): sql='.$sql;
		print - 1 . $db->lasterror();
	}

	//Prospect : pas de facture ni de session confirmée
	$sql = "UPDATE " . MAIN_DB_PREFIX . "societe SET client=2, tms=tms ";
	$sql .= " WHERE	fournisseur=0 ";
	$sql .= " AND client<>2 ";
	$sql .= " AND (rowid  NOT IN (SELECT fk_soc FROM " . MAIN_DB_PREFIX . "facture) ";
	$sql .= " AND (rowid  NOT IN (SELECT fk_soc_requester FROM " . MAIN_DB_PREFIX . "agefodd_session WHERE status IN (2)) ";
	$sql .= " AND (rowid NOT IN (SELECT fk_soc FROM " . MAIN_DB_PREFIX . "agefodd_session WHERE status IN (2))))) ";

	dol_syslog('/agefodd/scripts/update_customer_status.php:Prospect : pas de facture ni de session confirmée: sql='.$sql,LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		print 1;
	} else {
		print '/agefodd/scripts/update_customer_status.php:Prospect : pas de facture ni de session confirmée: sql='.$sql;
		print - 1 . $db->lasterror();
	}

	//Update trainee status to confirm if session s in the past
	$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_stagiaire SET status_in_session=3 ";
	$sql .= " WHERE status_in_session NOT IN (5,6) ";
	$sql .= " AND fk_session_agefodd IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "agefodd_session where datef<NOW() AND status=2) ";
	// UPDATE llx_agefodd_session_stagiaire SET status_in_session =3 WHERE status_in_session NOT IN (5,6) AND fk_session_agefodd IN (SELECT rowid FROM llx_agefodd_session where datef<NOW() AND status=2)


	dol_syslog('/agefodd/scripts/update_customer_status.php:Update trainee status to confirm if session s in the past: sql='.$sql,LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		print '1';
	} else {
		print '/agefodd/scripts/update_customer_status.php:Update trainee status to confirm if session s in the past: sql='.$sql;
		print - 1 . $db->lasterror();
	}

	//Update trainer status to confirm if session s in the past
	$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_formateur SET trainer_status=3 ";
	$sql .= " WHERE trainer_status NOT IN (5,6) ";
	$sql .= " AND fk_session IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "agefodd_session where datef<NOW() AND status=2) ";
	// UPDATE llx_agefodd_session_formateur SET trainer_status=3 WHERE trainer_status NOT IN (5,6) AND fk_session IN (SELECT rowid FROM llx_agefodd_session where datef<NOW() AND status=2)

	dol_syslog('/agefodd/scripts/update_customer_status.php:Update trainer status to confirm if session s in the past: sql='.$sql,LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		print 1;
	} else {
		print '/agefodd/scripts/update_customer_status.php:Update trainer status to confirm if session s in the past: sql='.$sql;
		print - 1 . $db->lasterror();
	}

	//Maison mére si fille
	$sql = "UPDATE " . MAIN_DB_PREFIX . "societe_extrafields SET ts_maison=1 ";
	$sql .= " WHERE fk_object IN (SELECT DISTINCT parent FROM " . MAIN_DB_PREFIX . "societe)";

	dol_syslog('/agefodd/scripts/update_customer_status.php:Maison mére si fille: sql='.$sql,LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		print 1;
	} else {
		print '/agefodd/scripts/update_customer_status.php:Maison mére si fille: sql='.$sql;
		print - 1 . $db->lasterror();
	}

	//PAs maison mére si pas fille
	/*$sql = "UPDATE " . MAIN_DB_PREFIX . "societe_extrafields SET ts_maison=NULL ";
	$sql .= " WHERE fk_object  NOT IN (SELECT DISTINCT parent FROM " . MAIN_DB_PREFIX . "societe WHERE parent IS NOT NULL)";

	dol_syslog('/agefodd/scripts/update_customer_status.php:PAs maison mére si pas fille: sql='.$sql,LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		print 1;
	} else {
		print '/agefodd/scripts/update_customer_status.php:PAs maison mére si pas fille: sql='.$sql;
		print - 1 . $db->lasterror();
	}*/

	//cocher de manière automatique "Contact principal" quand un contact est demandeur.
	$sql = "UPDATE " . MAIN_DB_PREFIX . "socpeople_extrafields SET ct_principal=1 ";
	$sql .= " WHERE fk_object IN (SELECT DISTINCT fk_socpeople_requester FROM " . MAIN_DB_PREFIX . "agefodd_session WHERE fk_socpeople_requester IS NOT NULL)";

	dol_syslog('/agefodd/scripts/update_customer_status.php:cocher de manière automatique "Contact principal" quand un contact est demandeur.: sql='.$sql,LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		print 1;
	} else {
		print '/agefodd/scripts/update_customer_status.php:Contact client session => Destinataire catalogue: sql='.$sql;
		print - 1 . $db->lasterror();
	}

	//Contact client session => Destinataire catalogue
	$sql = "UPDATE " . MAIN_DB_PREFIX . "socpeople_extrafields SET ct_catalogue=1 ";
	$sql .= " WHERE fk_object IN (SELECT DISTINCT fk_socpeople FROM " . MAIN_DB_PREFIX . "agefodd_contact as cnt INNER JOIN ".MAIN_DB_PREFIX."agefodd_session_contact as sesscnt ON sesscnt.fk_agefodd_contact=cnt.rowid)";

	dol_syslog('/agefodd/scripts/update_customer_status.php:Contact client session => Destinataire catalogue: sql='.$sql,LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		print 1;
	} else {
		print '/agefodd/scripts/update_customer_status.php:Contact client session => Destinataire catalogue: sql='.$sql;
		print - 1 . $db->lasterror();
	}

	//Contact client suivi propal => Destinataire catalogue
	$sql = "UPDATE " . MAIN_DB_PREFIX . "socpeople_extrafields SET ct_catalogue=1 ";
	$sql .= " WHERE fk_object IN ( ";
	$sql .= " SELECT elemcnt.fk_socpeople FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_element as sesselem ON sesselem.fk_session_agefodd=sess.rowid AND sesselem.element_type='propal' ";
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "element_contact as elemcnt ON elemcnt.element_id=sesselem.fk_element AND elemcnt.fk_c_type_contact=41)";

	dol_syslog('/agefodd/scripts/update_customer_status.php:Contact client suivi propal => Destinataire catalogue: sql='.$sql,LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		print 1;
	} else {
		print '/agefodd/scripts/update_customer_status.php:Contact client suivi propal => Destinataire catalogue: sql='.$sql;
		print - 1 . $db->lasterror();
	}


	//Contact client suivi pédagogique => Contact principal
	$sql = "UPDATE " . MAIN_DB_PREFIX . "socpeople_extrafields SET ct_principal=1 ";
	$sql .= " WHERE fk_object IN ( ";
	$sql .= " SELECT elemcnt.fk_socpeople FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_element as sesselem ON sesselem.fk_session_agefodd=sess.rowid AND sesselem.element_type='propal' ";
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "element_contact as elemcnt ON elemcnt.element_id=sesselem.fk_element AND elemcnt.fk_c_type_contact=192 ";
	$sql .= " WHERE sess.type_session=1)";

	dol_syslog('/agefodd/scripts/update_customer_status.php:Contact client suivi pédagogique => Contact principal: sql='.$sql,LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		print 1;
	} else {
		print '/agefodd/scripts/update_customer_status.php:Contact client suivi pédagogique => Contact principal: sql='.sql;
		print - 1 . $db->lasterror();
	}

	/*
	//Désactivé l'emailing de masse pour tous les contacts des Tiers qui ne sont ni client, ni prospect, ni client/prospect
	$sql = "UPDATE " . MAIN_DB_PREFIX . "socpeople SET no_email=1 WHERE fk_soc IS NOT NULL AND email IS NOT NULL AND fk_soc IN (SELECT rowid from " . MAIN_DB_PREFIX . "societe where (client NOT IN (1,2,3) OR fk_typent IN (103,3) OR fournisseur=1)";

	dol_syslog('/agefodd/scripts/update_customer_status.php:cocher de manière automatique "Contact principal" quand un contact est demandeur.: sql='.$sql,LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		print 1;
	} else {
		print - 1 . $db->lasterror();
	}*/


} else {
	print "user not found";
}

