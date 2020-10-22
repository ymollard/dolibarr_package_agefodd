
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

$filter = array (
		's.status' => 1
);

$agf = new Agsession($db);
$result = $agf->fetch_all('', '', 0, 0, $filter, null);
if ($result < 0) {
	print - 1;
} else {
	if (is_array($agf->lines) && count($agf->lines) > 0) {
		foreach ( $agf->lines as $line ) {
			$agf_fin = new Agefodd_session_element($db);
			$agf_fin->fk_session_agefodd = $line->rowid;
			$result = $agf_fin->updateSellingPrice($user);
			if ($result < 0) {
				print "update_sessioncostprice:: error: " . $agf_fin->error;
				print - 1;
			} else {
				print $result;
			}
		}
	}
}

$filter = array (
		's.status' => 2
);

$agf = new Agsession($db);
$result = $agf->fetch_all('', '', 0, 0, $filter, null);
if ($result < 0) {
	print - 1;
} else {
	if (is_array($agf->lines) && count($agf->lines) > 0) {
		foreach ( $agf->lines as $line ) {
			$agf_fin = new Agefodd_session_element($db);
			$agf_fin->fk_session_agefodd = $line->rowid;
			$result = $agf_fin->updateSellingPrice($user);
			if ($result < 0) {
				print "update_sessioncostprice:: error: " . $agf_fin->error;
				print - 1;
			} else {
				print $result;
			}
		}
	}
}


