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
dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');
dol_include_once('agefodd/lib/agefodd.lib.php');

$langs->load('agefodd@agefodd');
$response = new interfaceResponse;
$get = GETPOST('get', 'alpha');
$put = GETPOST('put', 'alpha');


switch ($get) {
	case 'getAgefoddSessionCalendrier':
		_getAgefoddSessionCalendrier(GETPOST('fk_agefodd_session', 'none'), GETPOST('dateStart', 'none'), GETPOST('dateEnd', 'none'));
		echo json_encode( $response );
		break;
}

switch ($put) {
	case 'deleteCalendrier':
		_deleteCalendrier(GETPOST('fk_agefodd_session_calendrier', 'int'), GETPOST('delete_cal_formateur', 'int'));
		echo json_encode( $response );
		break;
	case 'updateTimeSlotCalendrier':
		_updateTimeSlotCalendrier(GETPOST('fk_agefodd_session_calendrier', 'int'), GETPOST('start', 'none'), GETPOST('end', 'none'), GETPOST('deltaInSecond', 'int'));
		echo json_encode( $response );
		break;
	case 'createOrUpdateCalendrier':
		$time_start = dol_mktime(GETPOST('date_starthour', 'none'), GETPOST('date_startmin', 'none'), 0, GETPOST('date_startmonth', 'none'), GETPOST('date_startday', 'none'), GETPOST('date_startyear', 'none'));
		$time_end = dol_mktime(GETPOST('date_endhour', 'none'), GETPOST('date_endmin', 'none'), 0, GETPOST('date_endmonth', 'none'), GETPOST('date_endday', 'none'), GETPOST('date_endyear', 'none'));
		_createOrUpdateCalendrier(GETPOST('fk_agefodd_session_calendrier', 'int'), GETPOST('fk_agefodd_session', 'int'), GETPOST('TFormateurId', 'array'), GETPOST('TRealHour', 'array'), GETPOST('calendrier_type', 'none'), $time_start, $time_end, GETPOST('TFormateurHeured', 'none'), GETPOST('TFormateurHeuref', 'none'), GETPOST('calendrier_status', 'none'));
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
	$sql.= ' WHERE s.entity IN ('.getEntity('agefodd').') AND agsc.fk_agefodd_session = '.$fk_agefodd_session;
	$sql.= ' AND agsc.heured >= \''.$date_s.'\'';
	$sql.= ' AND agsc.heuref <= \''.$date_e.'\'';

	dol_syslog("session_scheduler.php::_getAgefoddSessionCalendrier SQL=".$sql, LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql)
	{
		$now = dol_now();
		while ($obj = $db->fetch_object($resql))
		{
			$agf_calendrier = new Agefodd_sesscalendar($db);
			$agf_calendrier->fetch($obj->rowid);

			$response->data->TEvent[] = _formatEventAsArray($agf_calendrier, $now);

		}
		return count($response->data->TEvent);
	}
	else
	{
		$response->TError[] = $db->lasterror;
		return 0;
	}
}

function _getTFormateur(&$agf_calendrier, $fk_agefodd_session)
{
	global $db, $response;

	$TFormateur = array();
	$TNomUrl = array();

	$sql = 'SELECT af.rowid, agsf.rowid AS opsid, agsfc.heured, agsfc.heuref FROM '.MAIN_DB_PREFIX.'agefodd_session_formateur agsf';
	$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'agefodd_session_formateur_calendrier agsfc ON (agsf.rowid = agsfc.fk_agefodd_session_formateur)';
	$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'agefodd_formateur af ON (af.rowid = agsf.fk_agefodd_formateur)';
	$sql.= ' WHERE agsf.fk_session = '.$fk_agefodd_session;
	$sql.= ' AND agsfc.heured < \''.date('Y-m-d H:i:s', $agf_calendrier->heuref).'\'';
	$sql.= ' AND agsfc.heuref > \''.date('Y-m-d H:i:s', $agf_calendrier->heured).'\'';

	$resql = $db->query($sql);
	if ($resql)
	{
		while ($obj = $db->fetch_object($resql))
		{
			$obj->heured = $db->jdate($obj->heured);
			$obj->heuref = $db->jdate($obj->heuref);

			$formateur = new Agefodd_teacher($db);
			$formateur->fetch($obj->rowid);
			$formateur->getnomurl = $formateur->getNomUrl();
			$formateur->opsid = $obj->opsid;
			$formateur->heured_formated = dol_print_date($obj->heured, 'hour');
			$formateur->heuref_formated = dol_print_date($obj->heuref, 'hour');
			$TFormateur[] = $formateur;

			$nomUrl = $formateur->getnomurl;
			if ($agf_calendrier->heured != $obj->heured || $agf_calendrier->heuref != $obj->heuref) $nomUrl.= ' ('.dol_print_date($obj->heured, '%H:%M').' - '.dol_print_date($obj->heuref, '%H:%M').')';

			$TNomUrl[] = $nomUrl;
		}
	}
	else
	{
		$response->TError[] = $db->lasterror;
	}

	return array($TFormateur, $TNomUrl);
}



function _deleteCalendrier($fk_agefodd_session_calendrier, $delete_cal_formateur=0)
{
	global $db,$response;

	$agf_calendrier = new Agefodd_sesscalendar($db);
	if ($agf_calendrier->remove($fk_agefodd_session_calendrier) > 0)
	{
		$response->TSuccess[] = 'Delete calendrier id = '.$fk_agefodd_session_calendrier.' successful';
		if ($delete_cal_formateur)
		{
			$TCalendrierFormateur = _getCalendrierFormateurFromCalendrier($agf_calendrier);
			_deleteCalendrierFormateur($TCalendrierFormateur);
		}

	}
	else $response->TError[] = $agf_calendrier->error;
}

function _deleteCalendrierFormateur($TCalendrierFormateur)
{
	global $response;

	foreach ($TCalendrierFormateur as &$agf_calendrier_formateur)
	{
		if ($agf_calendrier_formateur->remove($agf_calendrier_formateur->id) > 0) $response->TSuccess[] = 'Delete calendrier formateur id = '.$agf_calendrier_formateur->id.' successful';
		else $response->TError[] = $agf_calendrier_formateur->error;
	}
}

function _updateTimeSlotCalendrier($fk_agefodd_session_calendrier, $date_start, $date_end, $deltaInSecond)
{
	global $db,$user,$response,$conf;

	$agf_calendrier = new Agefodd_sesscalendar($db);
	if ($agf_calendrier->fetch($fk_agefodd_session_calendrier) > 0)
	{
		$TCalendrierFormateur = _getCalendrierFormateurFromCalendrier($agf_calendrier);

		$agf_calendrier->heured = strtotime($date_start);
		if (!empty($date_end)) $agf_calendrier->heuref = strtotime($date_end);
		else $agf_calendrier->heuref += $deltaInSecond;

		$date_session = strtotime(date('Y-m-d', $agf_calendrier->heured));
		$agf_calendrier->date_session = $date_session;

		$r = $agf_calendrier->update($user);
		if ($r > 0)
		{
			$response->TSuccess[] = 'Update Agefodd_sesscalendar id '.$fk_agefodd_session_calendrier.' successfully';

			foreach ($TCalendrierFormateur as &$agf_calendrier_formateur)
			{
				if (!empty($conf->global->AGF_SCHEDULER_FORMATEUR_FORCE_HOURS_WITH_EVENT))
				{
					$agf_calendrier_formateur->heured = $agf_calendrier->heured;
					$agf_calendrier_formateur->heuref = $agf_calendrier->heuref;
				}
				else
				{
					$agf_calendrier_formateur->heured += $deltaInSecond;
					$agf_calendrier_formateur->heuref += $deltaInSecond;
				}

				$agf_calendrier_formateur->date_session = $date_session;
				$r = $agf_calendrier_formateur->update($user);
				if ($r > 0) $response->TSuccess[] = 'Update Agefoddsessionformateurcalendrier id '.$agf_calendrier_formateur->id.' successfully';
				else $response->TError[] = $agf_calendrier_formateur->error;
			}


			$response->data->event = _formatEventAsArray($agf_calendrier, dol_now());
		}
		else $response->TError[] = $agf_calendrier->error;
	}
	else
	{
		$response->TError[] = $agf_calendrier->error;
	}
}

function _createOrUpdateCalendrier($fk_agefodd_session_calendrier, $fk_agefodd_session, $TFormateurId, $TRealHour, $calendrier_type, $time_start, $time_end, $TFormateurHeured, $TFormateurHeuref, $calendrier_status)
{
	global $db, $user, $response, $conf;
	$agf_calendrier = new Agefodd_sesscalendar($db);
	if (!empty($fk_agefodd_session_calendrier) && $fk_agefodd_session_calendrier > 0) $agf_calendrier->fetch($fk_agefodd_session_calendrier);

	$agsession = new Agsession($db);
	$agsession->fetch($fk_agefodd_session);

	$agf_calendrier->sessid = $fk_agefodd_session;
	$agf_calendrier->date_session = strtotime(date('Y-m-d', $time_start));
	$agf_calendrier->heured = $time_start;
	$agf_calendrier->heuref = $time_end;
	$agf_calendrier->calendrier_type = $calendrier_type;
	$agf_calendrier->status = $calendrier_status;
//	$agf_calendrier->status = 0;

	if (!empty($agf_calendrier->id))
	{
		if ($agf_calendrier->update($user) <= 0) $response->TError[] = $agf_calendrier->error;
	}
	else
	{
		if ($agf_calendrier->create($user) <= 0) $response->TError[] = $agf_calendrier->error;
	}

	// TODO penser à gérer la création des objets Agefoddsessionformateurcalendrier pour les formateurs
	// Puis de prendre en compte $TRealHour pour faire la saisie de temps de présence
	//	var_dump($agf_calendrier->id, $TFormateurId, $TRealHour);
	//	exit;
	if (!empty($TRealHour))
	{
		$dureeCalendrier = ((float) $agf_calendrier->heuref - (float) $agf_calendrier->heured) / 3600;

		dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');
		foreach ($TRealHour as $fk_stagiaire => $heures)
		{
			$agfstagiaireheure = new Agefoddsessionstagiaireheures($db);
			if ($agfstagiaireheure->fetch_by_session($fk_agefodd_session, $fk_stagiaire, $agf_calendrier->id) > 0)
			{
				$agfstagiaireheure->heures = $heures;
				$agfstagiaireheure->update();
			}
			else
			{
				$agfstagiaireheure->fk_stagiaire = $fk_stagiaire;
				$agfstagiaireheure->fk_session = $fk_agefodd_session;
				$agfstagiaireheure->fk_calendrier = $agf_calendrier->id;
				$agfstagiaireheure->heures = $heures;
				$agfstagiaireheure->create($user);
			}

			$agfstagiaireheure->setStatusAccordingTime($user,$fk_agefodd_session,$fk_stagiaire);
		}
	}

	dol_include_once('/agefodd/class/agefodd_session_formateur_calendrier.class.php');
	$calendriers = _getCalendrierFormateurFromCalendrier($agf_calendrier);
	if (!empty($TFormateurId))
	{
		foreach ($TFormateurId as $fk_trainer)
		{
			$is_update = 0;
			if (!empty($calendriers))
			{
				foreach ($calendriers as $calendrier)
				{
					if ($calendrier->fk_agefodd_session_formateur == $fk_trainer)
					{
						$calendrier->fk_agefodd_session_formateur = $fk_trainer;
						$calendrier->date_session = strtotime(date('Y-m-d', $time_start));
						$calendrier->heured = !empty($TFormateurHeured[$fk_trainer]) ? strtotime(date('Y-m-d '.$TFormateurHeured[$fk_trainer], $time_start)) : $time_start;
						$calendrier->heuref = !empty($TFormateurHeuref[$fk_trainer]) ? strtotime(date('Y-m-d '.$TFormateurHeuref[$fk_trainer], $time_start)) : $time_end;
						$calendrier->trainercost = 0;
						$calendrier->status=$calendrier_status;
						$calendrier->sessid = $fk_agefodd_session;
						$is_update = 1;
						$calendrier->update($user);
					}
				}
			}

			if (empty($is_update))
			{
				$agftrainercalendar = new Agefoddsessionformateurcalendrier($db);
				$agftrainercalendar->fk_agefodd_session_formateur = $fk_trainer;
				$agftrainercalendar->date_session = strtotime(date('Y-m-d', $time_start));
				$agftrainercalendar->heured = !empty($TFormateurHeured[$fk_trainer]) ? strtotime(date('Y-m-d '.$TFormateurHeured[$fk_trainer], $time_start)) : $time_start;
				$agftrainercalendar->heuref = !empty($TFormateurHeuref[$fk_trainer]) ? strtotime(date('Y-m-d '.$TFormateurHeuref[$fk_trainer], $time_start)) : $time_end;
				$agftrainercalendar->trainercost = 0;
				$agftrainercalendar->status=$calendrier_status;
				$agftrainercalendar->sessid = $fk_agefodd_session;
				//$agftrainercalendar->fk_actioncomm = 5;
				$agftrainercalendar->create($user);
			}
		}
	}

	// Suppression des dates des formateurs qui ne sont pas cochés
	foreach ($calendriers as $calendrier)
	{
		if (!in_array($calendrier->fk_agefodd_session_formateur, $TFormateurId))
		{
			$calendrier->delete($user);
		}
	}

	_getAgefoddSessionCalendrier($fk_agefodd_session, GETPOST('dateStart', 'none'), GETPOST('dateEnd', 'none'));
}

function _formatEventAsArray(Agefodd_sesscalendar &$agf_calendrier, $now)
{
	global $langs,$db;

	$TRealHour = array();
	$agfssh = new Agefoddsessionstagiaireheures($db);
	$agfssh->fetchAllBy($agf_calendrier->id, 'fk_calendrier');
	foreach ($agfssh->lines as &$line)
	{
		$TRealHour[$line->fk_stagiaire] = $line->heures;
	}


	list($TFormateur, $TNomUrlFormateur) = _getTFormateur($agf_calendrier, $agf_calendrier->sessid);

	return array(
		'id' => $agf_calendrier->id
		,'title' => $langs->transnoentitiesnoconv('AgfCalendarDates')
		,'desc' => ''
		,'start' => dol_print_date($agf_calendrier->heured, '%Y-%m-%d %H:%M:%S') // TODO
		,'end' => dol_print_date($agf_calendrier->heuref, '%Y-%m-%d %H:%M:%S') // TODO
		,'allDay' => false
		,'fk_agefodd_session' => $agf_calendrier->sessid
		,'calendrier_type' => !empty($agf_calendrier->calendrier_type) ? $agf_calendrier->calendrier_type : ''
		,'calendrier_type_label' => !empty($agf_calendrier->calendrier_type_label) ? $agf_calendrier->calendrier_type_label : ''
	    ,'calendrier_status' => !empty($agf_calendrier->status) ? $agf_calendrier->status : 0
		,'startEditable' => $agf_calendrier->heuref < $now ? false : true // si la date de fin est dans le passé, alors plus le droit de déplcer l'event
//				,'color'=>'#ccc' // background
		,'TRealHour' => $TRealHour
		,'TFormateur' => $TFormateur
		,'TNomUrlFormateur' => $TNomUrlFormateur
	);
}
