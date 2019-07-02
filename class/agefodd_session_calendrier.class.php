<?php
/*
 * Copyright (C) 2007-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2012-2014  Florian Henry <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file agefodd/class/agefodd_session_calendrier.class.php
 * \ingroup agefodd
 * \brief Manage location object
 */
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 * Session calendar class
 */
class Agefodd_sesscalendar extends CommonObject{
	public $error;
	public $errors = array ();
	public $element = 'agefodd';
	public $table_element = 'agefodd';
	public $id;
	public $date_session;
	public $heured;
	public $heuref;
	public $sessid;
	public $fk_actioncomm;
	public $calendrier_type;
	public $status = 0;
	public $billed = 0;
	public $lines = array ();


	const STATUS_DRAFT = 0;
	const STATUS_CONFIRMED = 1;
	const STATUS_MISSING = 2;
	const STATUS_CANCELED = -1;
	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	public function __construct($db) {
		$this->db = $db;
		return 1;
	}

	/**
	 * Create object into database
	 *
	 * @param User $user that create
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (!is_numeric($this->status)) $this->status = 0;
		// Check parameters
		// Put here code to add control on parameters value

		if ($conf->global->AGF_DOL_AGENDA) {
			$result = $this->createAction($user);
			if ($result <= 0) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			} else {
				$this->fk_actioncomm = $result;
			}
		}

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_session_calendrier(";
		$sql .= "fk_agefodd_session, date_session, heured, heuref,fk_actioncomm, fk_user_author,fk_user_mod, datec, calendrier_type, status, billed";
		$sql .= ") VALUES (";
		$sql .= " " . $this->sessid . ", ";
		$sql .= "'" . $this->db->idate($this->date_session) . "', ";
		$sql .= "'" . $this->db->idate($this->heured) . "', ";
		$sql .= "'" . $this->db->idate($this->heuref) . "', ";
		$sql .= " " . (! isset($this->fk_actioncomm) ? 'NULL' : "'" . $this->db->escape($this->fk_actioncomm) . "'") . ",";
		$sql .= ' ' . $user->id . ', ';
		$sql .= ' ' . $user->id . ', ';
		$sql .= "'" . $this->db->idate(dol_now()) . "', ";
		$sql .= "'" . $this->db->escape($this->calendrier_type) . "', ";
		$sql .= " " . $this->status . ", ";
		$sql .= " " . $this->billed;
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}
		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_session_calendrier");
			if (! $notrigger) {
				$result=$this->call_trigger('AGF_SESSION_CAL_CREATE',$user);
				if ($result < 0) { $error++; }
			}
		}

		if (! $error) {
			if (! empty($conf->global->AGF_AUTO_ACT_ADMIN_UPD)) {
				dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
				$admintask = new Agefodd_sessadm($this->db);
				$result = $admintask->updateByTriggerName($user, $this->sessid, 'AGF_DT_CONFIRM');
				if ($result < 0) {
					$error ++;
					$this->errors[] = "Error " . $admintask->error;
				}
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $actionid object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id) {
		global $langs;

		$sql = "SELECT";
		$sql .= " s.rowid, s.date_session, s.heured, s.heuref, s.fk_actioncomm, s.fk_agefodd_session, s.calendrier_type, s.status, d.label as calendrier_type_label, s.billed ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_calendrier as s";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.'c_agefodd_session_calendrier_type as d ON s.calendrier_type = d.code';
		$sql .= " WHERE s.rowid = " . $id;

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->date_session = $this->db->jdate($obj->date_session);
				$this->heured = $this->db->jdate($obj->heured);
				$this->heuref = $this->db->jdate($obj->heuref);
				$this->sessid = $obj->fk_agefodd_session;
				$this->fk_actioncomm = $obj->fk_actioncomm;
				$this->calendrier_type = $obj->calendrier_type;
				$this->calendrier_type_label = $obj->calendrier_type_label;
				$this->status = $obj->status;
				$this->billed = $obj->billed;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			$this->errors[] = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $actionid object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_by_action($actionid) {
		global $langs;

		$sql = "SELECT";
		$sql .= " s.rowid, s.date_session, s.heured, s.heuref, s.fk_actioncomm, s.fk_agefodd_session, s.calendrier_type, s.status, d.label as 'calendrier_type_label', s.billed ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_calendrier as s";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.'c_agefodd_session_calendrier_type as d ON (s.calendrier_type = d.code)';
		$sql .= " WHERE s.fk_actioncomm = " . $actionid;

		dol_syslog(get_class($this) . "::fetch_by_action", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->date_session = $this->db->jdate($obj->date_session);
				$this->heured = $this->db->jdate($obj->heured);
				$this->heuref = $this->db->jdate($obj->heuref);
				$this->sessid = $obj->fk_agefodd_session;
				$this->fk_actioncomm = $obj->fk_actioncomm;
				$this->calendrier_type = $obj->calendrier_type;
				$this->calendrier_type_label = $obj->calendrier_type_label;
				$this->status = $obj->status;
				$this->billed = $obj->billed;
			}
			$this->db->free($resql);

			if ($num > 0) return 1;
			else return 0;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_by_action " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $id of session
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_all($id)
	{
		$sql = "SELECT";
		$sql .= " DISTINCT s.rowid, s.date_session, s.heured";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_calendrier as s";
		$sql .= " WHERE s.fk_agefodd_session = " . $id;
		$sql .= " GROUP BY s.rowid, s.date_session, s.heured";
		$sql .= " ORDER BY s.date_session ASC, s.heured ASC";

		dol_syslog(get_class($this) . "::fetch_all", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			while ($obj = $this->db->fetch_object($resql))
			{
				$line = new Agefodd_sesscalendar($this->db);
				$line->fetch($obj->rowid);

				$this->lines[] = $line;
			}

			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			$this->errors[] = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Give information on the object
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function info($id) {
		global $langs;

		$sql = "SELECT";
		$sql .= " s.rowid, s.datec, s.tms, s.fk_user_author, s.fk_user_mod, s.calendrier_type, s.status, d.label as 'calendrier_type_label', s.billed ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_calendrier as s";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.'c_agefodd_session_calendrier_type as d ON (s.calendrier_type = d.code)';
		$sql .= " WHERE s.rowid = " . $id;

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->tms = $obj->tms;
				$this->user_creation = $obj->fk_user_author;
				$this->user_modification = $obj->fk_user_mod;
				$this->calendrier_type = $obj->calendrier_type;
				$this->calendrier_type_label = $obj->calendrier_type_label;
				$this->status = $obj->status;
				$this->billed = $obj->billed;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param User $user that modify
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (!is_numeric($this->status)) $this->status = 0;
		if (!is_numeric($this->billed)) $this->billed = 0;
		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_calendrier SET";
		$sql .= " date_session='" . $this->db->idate($this->date_session) . "',";
		$sql .= " heured='" . $this->db->idate($this->heured) . "',";
		$sql .= " heuref='" . $this->db->idate($this->heuref) . "',";
		$sql .= " fk_user_mod=" . $user->id . ", ";
		$sql .= " calendrier_type='" . $this->db->escape($this->calendrier_type) . "', ";
		$sql .= " status=" . $this->status . ", ";
		$sql .= " billed=" . $this->billed;
		$sql .= " WHERE rowid = " . $this->id;

		$this->db->begin();

		dol_syslog(get_class($this) . "::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}
		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.

				// Update Action is needed
				if (! empty($this->fk_actioncomm) && $conf->global->AGF_DOL_AGENDA) {
					$result = $this->updateAction($user);
					if ($result == - 1) {
						$error ++;
						$this->errors[] = "Error " . $this->db->lasterror();
					}
				}

				// // Call triggers
				// include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 * Renvoie un tableau contenant $duree_declared => Somme des heures déclarées sur les participant
	 * puis $duree_max => qui est le temps du créneau multiplié par le nombre de participant
	 *
	 * @param null $fk_stagiaire
	 * @return array
	 */
	public function getSumDureePresence($fk_stagiaire=null)
	{
		$duree_declared = $duree_max = 0;

		$agfssh = new Agefoddsessionstagiaireheures($this->db);
		$agfssh->fetchAllBy($this->id, 'fk_calendrier');
		if (!empty($agfssh->lines))
		{
			foreach ($agfssh->lines as &$line)
			{
				if (!empty($fk_stagiaire) && $line->fk_stagiaire != $fk_stagiaire) continue;
				$duree_declared += $line->heures;
			}

			$duree_max = (($this->heuref - $this->heured) / 60 / 60) * count($agfssh->lines);
		}

		return array($duree_declared, $duree_max);
	}

	public function delete($user)
	{
		$error = 0;

		dol_syslog(get_class($this) . "::delete", LOG_DEBUG);

		$this->db->begin();

		// Event agenda rattaché
		if (! empty($this->fk_actioncomm))
		{
			dol_include_once('/comm/action/class/actioncomm.class.php');

			$action = new ActionComm($this->db);
			$action->id = $this->fk_actioncomm;
			$r=$action->delete();
			if ($r < 0) $error++;
		}

		if (!$error)
		{
			dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');
			// Les heures saisies pour les participants
			$agfssh = new Agefoddsessionstagiaireheures($this->db);
			$agfssh->fetchAllBy($this->id, 'fk_calendrier');
			if (!empty($agfssh->lines))
			{
				foreach ($agfssh->lines as &$line)
				{
					$line->delete($user);
					if ($r < 0) $error++;
				}
			}
		}

		if (!$error)
		{
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_session_calendrier";
			$sql .= " WHERE rowid = " . $this->id;
			$resql = $this->db->query($sql);
			if (!$resql)
			{
				$error++;
				$this->error = $this->db->lasterror();
			}
		}

		if (!$error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -1 * $error;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param int $id to delete
	 * @return int <0 if KO, >0 if OK
	 */
	public function remove($id) {
		global $user;

		dol_syslog(get_class($this) . "::remove", LOG_DEBUG);
		$result = $this->fetch($id);
		return $this->delete($user);
	}

	/**
	 * Create Action in Dolibarr Agenda
	 *
	 * @param int			fk_session_place Location of session
	 * @param User $user that modify
	 */
	public function createAction($user) {
		global $conf, $langs;

		$error = 0;

		dol_include_once('/comm/action/class/actioncomm.class.php');
		dol_include_once('/agefodd/class/agsession.class.php');

		$action = new ActionComm($this->db);
		$session = new Agsession($this->db);

		$result = $session->fetch($this->sessid);
		if ($result < 0) {
			$error ++;
		}

		$label = $session->intitule_custo;

		if(empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && ! empty($conf->global->AGF_EXTRAFIELD_FOR_TRAINING_LABEL))
		{
			$fullExtrafieldKey = 'options_' . $conf->global->AGF_EXTRAFIELD_FOR_TRAINING_LABEL;

			if(is_array($session->array_options) && array_key_exists($fullExtrafieldKey, $session->array_options) && ! empty($session->array_options[$fullExtrafieldKey]))
			{
				$label = $session->array_options[$fullExtrafieldKey];
			}
		}

		$action->label = $label . ' - ' . $langs->trans('AgfSessionDetail') . ' ' . $session->ref;
		$action->location = $session->placecode;
		$action->datep = $this->heured;
		$action->datef = $this->heuref;
		$action->author = $user; // User saving action
		$action->fk_element = $session->id;
		$action->elementtype = $session->element;
		$action->type_code = 'AC_AGF_SESS';
		$action->percentage = - 1;
		$action->userownerid = $user->id;
		if (! empty($session->fk_soc)) {
			$action->societe->id = $session->fk_soc;
			$action->socid = $session->fk_soc;
		}

		if ($error == 0) {
			$result = $action->create($user);

			if ($result < 0) {
				$error ++;
				dol_syslog(get_class($this) . "::createAction " . $action->error, LOG_ERR);
				return - 1;
			} else {
				return $result;
			}
		} else {
			dol_syslog(get_class($this) . "::createAction " . $action->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * update Action in Dolibarr Agenda
	 *
	 * @param User $user that modify
	 */
	public function updateAction($user) {
		global $conf, $langs;

		$error = 0;

		dol_include_once('/comm/action/class/actioncomm.class.php');
		dol_include_once('/agefodd/class/agsession.class.php');

		$action = new ActionComm($this->db);
		$session = new Agsession($this->db);

		$result = $session->fetch($this->sessid);
		if ($result < 0) {
			$error ++;
		}

		$result = $action->fetch($this->fk_actioncomm);
		if ($result < 0) {
			$error ++;
		}
        elseif ($result > 0) {
            $result = $action->fetch_userassigned();
            if ($result < 0) {
                $error ++;
            }
        }

		if ($error == 0) {

			if ($action->id == $this->fk_actioncomm) {
				$label = $session->intitule_custo;

				if(empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && ! empty($conf->global->AGF_EXTRAFIELD_FOR_TRAINING_LABEL))
				{
					$fullExtrafieldKey = 'options_' . $conf->global->AGF_EXTRAFIELD_FOR_TRAINING_LABEL;

					if(is_array($session->array_options) && array_key_exists($fullExtrafieldKey, $session->array_options) && ! empty($session->array_options[$fullExtrafieldKey]))
					{
						$label = $session->array_options[$fullExtrafieldKey];
					}

				}

				$action->label = $label . ' - ' . $langs->trans('AgfSessionDetail') . ' ' . $session->ref;
				$action->location = $session->placecode;
				$action->datep = $this->heured;
				$action->datef = $this->heuref;
				$action->type_code = 'AC_AGF_SESS';

				$result = $action->update($user);
			} else {
				$result = $this->createAction($user);
			}

			if ($result < 0) {
				$error ++;

				dol_syslog(get_class($this) . "::updateAction " . $action->error, LOG_ERR);
				return - 1;
			} else {
				return 1;
			}
		} else {
			dol_syslog(get_class($this) . "::updateAction " . $action->error, LOG_ERR);
			return - 1;
		}
	}

	public static function getStaticLibStatut($status, $mode=0)
	{
	    global $langs;

	    $out = '';
	    if ($status == self::STATUS_DRAFT)
	    {
	        if ($mode == 1) $out.= img_picto('', 'statut0').' ';
	        $out.= $langs->trans('AgfStatusCalendar_previsionnel');
	    }
	    else if ($status == self::STATUS_CONFIRMED)
	    {
	        if ($mode == 1) $out.= img_picto('', 'statut4').' ';
	        $out.= $langs->trans('AgfStatusCalendar_confirmed');
	    }
	    else if ($status == self::STATUS_CANCELED)
	    {
	        if ($mode == 1) $out.= img_picto('', 'statut6').' ';
	        $out.= $langs->trans('AgfStatusCalendar_canceled');
	    }
	    else if ($status == self::STATUS_MISSING)
	    {
	        if ($mode == 1) $out.= img_picto('', 'statut8').' ';
	        $out.= $langs->trans('AgfStatusCalendar_missing');
	    }

	    return $out;
	}

	public function getLibStatutBilled()
	{
	    global $langs;

	    $langs->load('bills');
	    $out = '';

	    if (empty($this->billed)) $out .= img_picto($langs->trans('ToBill'), 'statut1');
	    else $out .= img_picto($langs->trans('Billed'), 'statut4');

	    return $out;
	}

	/**
	 * @param int $sessid Id de la session
	 * Retourne le nombre de créneaux du calendrier marqués "facturé"
	 */
	public static function countBilledshedule($sessid)
	{
	    global $db;

	    if (empty($sessid)) return -1;

	    $sql = "SELECT count(billed) as billed FROM ".MAIN_DB_PREFIX."agefodd_session_calendrier WHERE billed = 1 AND fk_agefodd_session =  ".$sessid;
	    $res = $db->query($sql);
	    if ($res)
	    {
	        $obj = $db->fetch_object($res);
	        if ($obj) return $obj->billed;
	        else return 0;
	    }
	    else return -1;
	}

	/**
	 * @param int $sessid Id de la session
	 * Retourne le nombre de créneaux du calendrier de session
	 */
	public static function countTotalshedule($sessid)
	{
	    global $db;

	    if (empty($sessid)) return -1;

	    $sql = "SELECT COUNT(rowid) as total FROM ".MAIN_DB_PREFIX."agefodd_session_calendrier WHERE fk_agefodd_session = ".$sessid;
	    $res = $db->query($sql);
	    if ($res)
	    {
	        $obj = $db->fetch_object($res);
	        if ($obj) return $obj->total;
	        else return 0;
	    }
	    else return -1;
	}
}
class Agefodd_sesscalendar_line {
	public $id;
	public $date_session;
	public $heured;
	public $heuref;
	public $sessid;
	public function __construct() {
		return 1;
	}
}
