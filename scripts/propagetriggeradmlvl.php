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

$res = @include ('../../main.inc.php'); // For root directory
if (! $res)
	$res = @include ('../../../main.inc.php'); // For 'custom' directory
if (! $res)
	die('Include of main fails');



$resql = $db->query('UPDATE '.MAIN_DB_PREFIX.'agefodd_training_admlevel as ata '
	. 'LEFT JOIN '.MAIN_DB_PREFIX.'agefodd_session_admlevel as asa ON (asa.rowid = ata.fk_agefodd_training_admlevel) '
	. 'SET ata.trigger_name = asa.trigger_name '
	. 'WHERE asa.trigger_name IS NOT NULL');

if($resql) print 'Propagation sur les formations effectuée ';
else  print 'Erreur lors de la propagation sur les formations : '.$db->error();

print ' <br/>';


$resql = $db->query('UPDATE '.MAIN_DB_PREFIX.'agefodd_session_adminsitu as ata '
	. 'LEFT JOIN '.MAIN_DB_PREFIX.'agefodd_training_admlevel as asa ON (asa.rowid = ata.fk_agefodd_session_admlevel) '
	. 'SET ata.trigger_name = asa.trigger_name  '
	. 'WHERE asa.trigger_name IS NOT NULL');
			
if($resql) print 'Propagation sur les sessions effectuée';
else  print 'Erreur lors de la propagation sur les sessions : '.$db->error();
