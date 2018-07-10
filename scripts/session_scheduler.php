<?php
/* Copyright (C) 2018 Pierre-Henry Favre <phf@atm-consulting.fr>
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
 * \file /agefodd/scripts/session_scheduler.php
 * \brief Generate script
 */

if (! defined('NOTOKENRENEWAL'))
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))
	define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))
	define('NOREQUIREHTML', '1');
if (! defined('NOCSRFCHECK'))
	define('NOCSRFCHECK', '1');
if (! defined('NOREQUIREHTML'))
	define('NOREQUIREHTML', '1');
if (! defined('NOLOGIN'))
	define('NOLOGIN', '1');

$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

//var_dump($_GET);
define('INC_FROM_DOLIBARR', true);
ob_start();
dol_include_once('/fullcalendarscheduler/script/interface.php');
$print = ob_get_clean();

if (!empty($print))
{
	echo $print;
	exit;
}

dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur_calendrier.class.php');
dol_include_once('/agefodd/class/agefodd_formateur.class.php');

$langs->load('agefodd@agefodd');
$response = new interfaceResponse;
$get = GETPOST('get', 'alpha');


switch ($get) {
	case 'getAgefoddSessionCalendrier':
		_getAgefoddSessionCalendrier(GETPOST('fk_agefodd_session'), GETPOST('dateStart'), GETPOST('dateEnd'));
		echo json_encode( $response );
		break;
}



/**
 * Fonction qui retourne une liste d'events agenda pour une date ou un plage de date et éventuellement avec un type
 * @param date $date_s	format Y-m-d H:i:s
 * @param date $date_e	format Y-m-d H:i:s
 */
function _getAgefoddSessionCalendrier($fk_agefodd_session, $date_s, $date_e)
{
	global $db, $response, $conf, $langs;
	
	$sql = 'SELECT agsc.rowid';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'agefodd_session_calendrier agsc';
	$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'agefodd_session s ON (s.rowid = agsc.fk_agefodd_session)';
	$sql.= ' WHERE s.entity = '.$conf->entity.' AND agsc.fk_agefodd_session = '.$fk_agefodd_session;
	$sql.= ' AND agsc.heured >= '.$db->idate($date_s);
	$sql.= ' AND agsc.heuref <= '.$db->idate($date_e);
	
	dol_syslog("session_scheduler.php::_getAgefoddSessionCalendrier SQL=".$sql, LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql)
	{
		$now = dol_now();
		while ($obj = $db->fetch_object($resql))
		{
			$agf = new Agefodd_sesscalendar($db);
			$agf->fetch($obj->rowid);
			
			$TParticipant = array();
			list($TFormateur, $TNomUrlFormateur) = _getTFormateur($agf, $fk_agefodd_session);
			
			$response->data->TEvent[] = array(
				'id' => $agf->id
				,'title' => $langs->transnoentitiesnoconv('AgfCalendarDates')
				,'desc' => ''
				,'start' => dol_print_date($agf->heured, '%Y-%m-%dT%H:%M:%S', 'gmt') // TODO
				,'end' => dol_print_date($agf->heuref, '%Y-%m-%dT%H:%M:%S', 'gmt') // TODO
				,'allDay' => false
				,'fk_agefodd_session' => $obj->fk_agefodd_session
				,'startEditable' => $agf->heuref < $now ? false : true // si la date de fin est dans le passé, alors plus le droit de déplcer l'event
//				,'color'=>'#ccc' // background
				,'TParticipant' => $TParticipant
				,'TFormateur' => $TFormateur
				,'TNomUrlFormateur' => $TNomUrlFormateur
			);
			
		}
		
		return count($response->data->TEvent);
	}
	else
	{
		$response->TError[] = $db->lasterror;
		return 0;
	}
}

function _getTFormateur(&$agf, $fk_agefodd_session)
{
	global $db, $response;
	
	$TFormateur = array();
	$TNomUrl = array();
	
	$sql = 'SELECT af.rowid FROM '.MAIN_DB_PREFIX.'agefodd_session_formateur agsf';
	$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'agefodd_session_formateur_calendrier agsfc ON (agsf.rowid = agsfc.fk_agefodd_session_formateur)';
	$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'agefodd_formateur af ON (af.rowid = agsf.fk_agefodd_formateur)';
	$sql.= ' WHERE agsf.fk_session = '.$fk_agefodd_session;
	$sql.= ' AND agsfc.heured <= \''.date('Y-m-d H:i:s', $agf->heured).'\'';
	$sql.= ' AND agsfc.heuref >= \''.date('Y-m-d H:i:s', $agf->heuref).'\'';

	$resql = $db->query($sql);
	if ($resql)
	{
		while ($obj = $db->fetch_object($resql))
		{
			$formateur = new Agefodd_teacher($db);
			$formateur->fetch($obj->rowid);
			$formateur->getnomurl = $formateur->getNomUrl();
			$TFormateur[] = $formateur;
			$TNomUrl[] = $formateur->getnomurl;
		}
	}
	else
	{
		$response->TError[] = $db->lasterror;
	}
	
	return array($TFormateur, $TNomUrl);
}