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
 * \file /agefodd/scripts/updatealltaskadmnistrative.php
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

dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/user/class/user.class.php');
dol_include_once('/agefodd/class/agefodd_training_admlevel.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
dol_include_once('/agefodd/class/agefodd_session_admlevel.class.php');

$userlogin = GETPOST('login', 'none');
$id = GETPOST('id', 'none');
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

print 'TRAINNING<BR>';
$sql = "SELECT s.rowid";
$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as s";
$sql .= " WHERE s.archive<>1";
$resql = $db->query($sql);
if ($resql) {

	while ( $obj = $db->fetch_object($resql) ) {
		$agf_adminlevel = new Agefodd_training_admlevel($db);
		$agf_adminlevel->fk_training = $obj->rowid;
		$result = $agf_adminlevel->delete_training_task($user);
		if ($result < 0) {
			print ' delete_task training_id =' . $obj->rowid . ' ERROR=' . $agf_adminlevel->error . '<br>';
		} else {
			print ' delete_task training_id =' . $obj->rowid . ' OK <br>';
		}

		$agf = new Formation($db);
		$result = $agf->fetch($obj->rowid);
		$result = $agf->createAdmLevelForTraining($user);
		if ($result < 0) {
			print ' create_task training_id =' . $obj->rowid . ' ERROR=' . $agf_adminlevel->error . '<br>';
		} else {
			print ' create_task training_id =' . $obj->rowid . ' OK <br>';
		}

		print 'SESSION<BR>';
		$sqlsession = "SELECT s.rowid";
		$sqlsession .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sqlsession .= " WHERE s.status<>4 AND fk_formation_catalogue=" . $obj->rowid;
		$resqlsession = $db->query($sqlsession);
		if ($resqlsession) {

			while ( $objsession = $db->fetch_object($resqlsession) ) {
				$agf_level = new Agefodd_sessadm($db);
				$result = $agf_level->remove_all($objsession->rowid);
				if ($result < 0) {
					print ' remove_all session_id =' . $objsession->rowid . ' ERROR=' . $agf_level->error . '<br>';
				} else {
					print ' remove_all session_id =' . $objsession->rowid . ' OK <br>';
				}

				$agf_session = new Agsession($db);
				$res = $agf_session->fetch($objsession->rowid);
				$result = $agf_session->createAdmLevelForSession($user);
				if ($result < 0) {
					print ' createAdmLevelForSession session_id =' . $objsession->rowid . ' ERROR=' . $agf_session->error . '<br>';
				} else {
					print ' createAdmLevelForSession session_id =' . $objsession->rowid . ' OK <br>';
				}
			}
		} else {
			print 'Erreur sql=' . $sql . '<br>';
			print $db->lasterror();
		}

		ob_flush();
	}
} else {
	print 'Erreur sql=' . $sql . '<br>';
	print $db->lasterror();
}




