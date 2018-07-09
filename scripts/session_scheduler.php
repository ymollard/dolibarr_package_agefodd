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

$response = new interfaceResponse;
$get = GETPOST('get', 'alpha');


switch ($get) {
	case 'getEventsFromDatesAgefoddSession':
		_getEventsFromDatesAgefoddSession(GETPOST('fk_agefodd_session'), GETPOST('dateStart'), GETPOST('dateEnd'), GETPOST('code'), GETPOST('fk_user'));
		echo json_encode( $response );
		break;
}



/**
 * Fonction qui retourne une liste d'events agenda pour une date ou un plage de date et éventuellement avec un type
 * @param date $date_s	format Y-m-d H:i:s
 * @param date $date_e	format Y-m-d H:i:s
 */
function _getEventsFromDatesAgefoddSession($fk_agefodd_session, $date_s, $date_e, $c_actioncomm_code, $fk_user)
{
	global $db, $response, $conf;
	
	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
	
	$actioncomm = new ActionComm($db);
	$extrafields = new ExtraFields($db);
	$extralabels=$extrafields->fetch_name_optionals_label($actioncomm->table_element);
	
	$sql = 'SELECT a.id AS fk_actioncomm, ca.code AS type_code';
	$sql.= ', a.label, a.note, a.fk_soc, s.nom AS company_name, a.datep, a.datep2, a.fulldayevent';
	$sql.= ', sp.rowid AS fk_socpeople, sp.civility, sp.lastname, sp.firstname, sp.email AS contact_email, sp.address AS contact_address, sp.zip AS contact_zip, sp.town AS contact_town, sp.phone_mobile AS contact_phone_mobile';
	$sql.= ', agsc.fk_agefodd_session as fk_agefodd_session';
	foreach ($extralabels as $key => $label)
	{
		$sql .= ', ae.'.$key.' AS extra_'.$key;
	}
	$sql.= ' FROM '.MAIN_DB_PREFIX.'actioncomm a';
	$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'c_actioncomm ca ON (ca.id = a.fk_action)';
	
	// Je veux les events agenda aux quels l'utilisateur est associé
	$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'actioncomm_resources ar ON (ar.fk_actioncomm = a.id AND ar.element_type=\'user\' AND ar.fk_element = '.$fk_user.')';
	
	// Cette jointure me permet de connaitre le fk_agefodd_session dans le select (si null, alors il ne s'agit pas de la session courante donc bg en gris)
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'agefodd_session_calendrier agsc ON (agsc.fk_actioncomm = a.id)';
	
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe s ON (s.rowid = a.fk_soc)';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'socpeople sp ON (sp.rowid = a.fk_contact)';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'actioncomm_extrafields ae ON (ae.fk_object = a.id)';
	
	$sql.= ' WHERE a.entity = '.$conf->entity;
	if (empty($date_e)) $sql.= ' AND DATE_FORMAT(a.datep, "%Y-%m-%d") = \''.date('Y-m-d', $date_s).'\'';
	else {
		$sql.= ' AND a.datep >= '.$db->idate($date_s);
		$sql.= ' AND a.datep2 <= '.$db->idate($date_e);
	}
	if (!empty($c_actioncomm_code)) $sql.= ' AND ca.code = \''.$c_actioncomm_code.'\'';
	
	dol_syslog("interface.php::_getEventsFromDates", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql)
	{
		$societe = new Societe($db);
		$contact = new Contact($db);
		while ($obj = $db->fetch_object($resql))
		{
			$actioncomm->fetch($obj->fk_actioncomm);
			$actioncomm->fetch_optionals();
			
			$societe->id = $obj->fk_soc;
			$societe->nom = $societe->name = $obj->company_name;
			
			$contact->id = $obj->fk_socpeople;
			$contact->firstname = $obj->firstname;
			$contact->lastname = $obj->lastname;
			$contact->email = $obj->contact_email;
			$contact->phone_mobile = $obj->contact_phone_mobile;
			$contact->address = $obj->contact_address;
			$contact->zip = $obj->contact_zip;
			$contact->town = $obj->contact_town;
			
			$response->data->TEvent[] = array(
				'id' => $obj->fk_actioncomm
				,'type_code' => $obj->type_code
				,'title' => $obj->label
				,'desc' => !empty($obj->note) ? $obj->note : ''
				,'fk_soc' => $obj->fk_soc
				,'company_name' => $obj->company_name
				,'link_company' => !empty($societe->id) ? $societe->getNomUrl(1) : ''
				,'fk_socpeople' => $obj->fk_socpeople
				,'contact_civility' => $obj->civility
				,'contact_lastname' => $obj->lastname
				,'contact_firstname' => $obj->firstname
				,'link_contact' => !empty($contact->id) ? $contact->getNomUrl(1) : ''
				,'start' => !empty($obj->fulldayevent) ? dol_print_date($obj->datep, '%Y-%m-%d') : dol_print_date($obj->datep, '%Y-%m-%dT%H:%M:%S', 'gmt') // TODO
				,'end' => !empty($obj->fulldayevent) ? dol_print_date($obj->datep2, '%Y-%m-%d') : dol_print_date($obj->datep2, '%Y-%m-%dT%H:%M:%S', 'gmt')
				,'allDay' => (boolean) $obj->fulldayevent // TODO à voir si on garde pour que l'event aparaisse en haut
				,'showOptionals' => !empty($extralabels) ? customShowOptionals($actioncomm, $extrafields) : ''
				,'editOptionals' => !empty($extralabels) ? '<table id="extrafield_to_replace" class="extrafields" width="100%">'.$actioncomm->showOptionals($extrafields, 'edit').'</table>' : ''
				,'fk_agefodd_session' => $obj->fk_agefodd_session
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