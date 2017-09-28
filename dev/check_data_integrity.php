<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
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
 * \file /agefodd/dev/check_data_integrity.php
 * \brief dev part
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

llxHeader('', $langs->trans('AgefoddShort'));


//agefodd_session_formateur
$sql = 'SELECT fk_session as fk_session FROM '.MAIN_DB_PREFIX.'agefodd_session_formateur WHERE fk_session NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session '.$obj->fk_session.' dans '.MAIN_DB_PREFIX.'agefodd_session_formateur et non dans '.MAIN_DB_PREFIX.'agefodd_session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_formateur WHERE fk_session NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}

//agefodd_session_formateur
$sql = 'SELECT  fk_agefodd_formateur as  fk_agefodd_formateur FROM '.MAIN_DB_PREFIX.'agefodd_session_formateur WHERE  fk_agefodd_formateur NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_formateur)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Foramteur '.$obj-> 	fk_agefodd_formateur.' dans '.MAIN_DB_PREFIX.'agefodd_session_formateur et non dans '.MAIN_DB_PREFIX.'agefodd_session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_formateur WHERE fk_agefodd_formateur NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_formateur)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}



//agefodd_session_adminsitu
$sql = 'SELECT fk_agefodd_session as fk_session FROM '.MAIN_DB_PREFIX.'agefodd_session_adminsitu WHERE fk_agefodd_session NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session '.$obj->fk_session.' dans '.MAIN_DB_PREFIX.'agefodd_session_adminsitu et non dans '.MAIN_DB_PREFIX.'agefodd_session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_adminsitu WHERE fk_agefodd_session NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}


//agefodd_session_commercial
$sql = 'SELECT fk_session_agefodd as fk_session FROM '.MAIN_DB_PREFIX.'agefodd_session_commercial WHERE fk_session_agefodd NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session '.$obj->fk_session.' dans '.MAIN_DB_PREFIX.'agefodd_session_commercial et non dans '.MAIN_DB_PREFIX.'agefodd_session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_commercial WHERE fk_session_agefodd NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}

//agefodd_session_calendrier
$sql = 'SELECT fk_agefodd_session as fk_session FROM '.MAIN_DB_PREFIX.'agefodd_session_calendrier WHERE fk_agefodd_session NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session '.$obj->fk_session.' dans '.MAIN_DB_PREFIX.'agefodd_session_calendrier et non dans '.MAIN_DB_PREFIX.'agefodd_session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_calendrier WHERE fk_agefodd_session NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}



//agefodd_session_calendrier
$sql = 'SELECT fk_agefodd_session as fk_session FROM '.MAIN_DB_PREFIX.'agefodd_session_calendrier WHERE fk_actioncomm NOT IN (SELECT id FROM '.MAIN_DB_PREFIX.'actioncomm)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session '.$obj->fk_session.' dans '.MAIN_DB_PREFIX.'agefodd_session_calendrier et non dans '.MAIN_DB_PREFIX.'actioncomm<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_calendrier WHERE fk_actioncomm NOT IN (SELECT id FROM '.MAIN_DB_PREFIX.'actioncomm)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}

//agefodd_session_calendrier
$sql = 'SELECT fk_category as fk_category FROM '.MAIN_DB_PREFIX.'agefodd_formateur_category WHERE fk_category NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_formateur_category_dict)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session '.$obj->fk_category.' dans '.MAIN_DB_PREFIX.'agefodd_formateur_category et non dans '.MAIN_DB_PREFIX.'agefodd_formateur_category_dict<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_formateur_category WHERE fk_category NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_formateur_category_dict)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}




llxFooter();
$db->close();