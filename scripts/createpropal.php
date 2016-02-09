<?php
/* Copyright (C) 2013-2016 Florian Henry  <florian.henry@open-concept.pro>
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
 * \file /agefodd/scripts/createtaskadmin.php
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
dol_include_once('/societe/class/societe.class.php');
dol_include_once('/agefodd/class/agsession.class.php');

$userlogin = GETPOST('login', 'alpha');
$session_id = GETPOST('sessid', 'int');
$socid = GETPOST('socid', 'int');
$key = GETPOST('key', 'alpha');

$error=0;

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

$agf = new Agsession($db);
$result = $agf->fetch($session_id);
if ($result < 0) {
	print - 1;
	print 'Session ERROR='.$agf->error;
	$error ++;
}

if (!empty($socid)) {
	$thridparty=new Societe($db);
	$result= $thridparty->fetch($socid);
	if ($result < 0) {
		print - 1;
		print 'Thridparty ERROR='.$thridparty->error;
		$error ++;
	}
	
	
	if (! empty($conf->global->MAIN_MULTILANGS)) {
		$langs = new Translate("", $conf);
		$new_tranlaste=$thridparty->default_lang;
		if (empty($new_tranlaste)) $new_tranlaste = 'fr_FR';
		$langs->setDefaultLang($new_tranlaste);
	}
	
}

if (empty($error)) {
	$result = $agf->createProposal($user, $socid);
	if ($result < 0) {
		print - 1;
		print 'Session Create Propal ERROR='.$agf->error;
	} else {
		print $result;
	}
}

			
