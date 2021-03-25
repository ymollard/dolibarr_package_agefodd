<?php
/* Copyright (C) 2007-2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file        htdocs/modulebuilder/template/class/agefodd_session_stagiaire_heures.class.php
 * \brief       This file is a CRUD class file for Agefoddsessionstagiaireheures (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once 'agsession.class.php';
require_once 'agefodd_session_stagiaire.class.php';
require_once 'agefodd_session_calendrier.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Agefoddsessionstagiaireheures
 */
class Agefoddsessionstagiaireheures extends CommonObject
{
    public $error; // !< To return error code (or message)
    public $errors = array (); // !< To return several error codes (or messages)
    public $element = 'agefodd_session_stagiaire_heures';
    public $table_element = 'agefodd_session_stagiaire_heures';
    public $id;
    public $entity;
    public $fk_stagiaire;
    public $nom_stagiaire;
    public $datec = '';
    public $fk_user_author;
    public $tms = '';
    public $lines = array();
    public $fk_calendrier;
    public $fk_session;
    public $heures;
    public $mail_sended = 0;
    public $planned_absence = 0;

    public $warning='';

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param User $user that creates
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
	    global $conf, $langs;
	    $error = 0;

	    // Clean parameters

	    if (isset($this->fk_stagiaire))
	        $this->fk_stagiaire = trim($this->fk_stagiaire);
	    if (isset($this->fk_calendrier))
	        $this->fk_calendrier = trim($this->fk_calendrier);
	    if (isset($this->fk_session))
            $this->fk_session = trim($this->fk_session);
        if (isset($this->heures))
            $this->heures = (float) $this->heures;
        if (isset($this->fk_user_author))
            $this->fk_user_author = trim($this->fk_user_author);

        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element ."(";
        $sql .= "entity,";
        $sql .= "fk_stagiaire,";
        $sql .= "fk_session,";
        $sql .= "fk_calendrier,";
        $sql .= "heures,";
        $sql .= "fk_user_author,";
        $sql .= "datec,";
        $sql .= "mail_sended,";
        $sql .= "planned_absence";
        $sql .= ") VALUES (";

        $sql .= " '" . $conf->entity . "',";
        $sql .= " " . (! isset($this->fk_stagiaire) ? 'NULL' : "'" . $this->fk_stagiaire . "'") . ",";
        $sql .= " " . (! isset($this->fk_session) ? 'NULL' : "'" . $this->fk_session . "'") . ",";
        $sql .= " " . (! isset($this->fk_calendrier) ? 'NULL' : "'" . $this->fk_calendrier . "'") . ",";
        $sql .= " " . (! isset($this->heures) || dol_strlen($this->heures) == 0 ? 'NULL' : "'" . $this->db->escape($this->heures) . "'") . ",";
        $sql .= " " . (! isset($this->fk_user_author) ? $user->id : "'" . $this->fk_user_author . "'") . ",";
        $sql .= " '" . (! isset($this->datec) || dol_strlen($this->datec) == 0 ? $this->db->idate(dol_now()) : $this->db->idate($this->datec)) . "', ";
        $sql .= intval($this->mail_sended) . ",";
        $sql .= " " . (! isset($this->planned_absence) ? 'NULL' : "'" . $this->planned_absence . "'");

        $sql .= ")";
        $this->db->begin();

        dol_syslog(get_class($this) . "::create", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error ++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (! $error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

            if (! $notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.

                // // Call triggers
                 include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                 $interface=new Interfaces($this->db);
                 $result=$interface->run_triggers('AGEFODDSESSIONSTAGIAIREHEURES_CREATE',$this,$user,$langs,$conf);
                 if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // // End call triggers
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
	 * Delete object (trainne in session) in database
	 *
	 * @param User $user user object
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		$error = 0;
	    $this->db->begin();

	    $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
	    $sql .= " WHERE rowid = " . $this->id;

	    dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
	    $resql = $this->db->query($sql);

	    if ($resql) {
	        // ...
	    } else {
	        $error ++;
	        $this->errors[] = "Error " . $this->db->lasterror();
	    }

	    // Commit or rollback
	    if ($error) {
	        foreach ( $this->errors as $errmsg ) {
	            dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
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
	 * Update object into database
	 *
	 * @param User $user that modifies
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function update($user = 0, $notrigger = 0) {
	    global $conf, $langs;
	    $error = 0;

	    // Clean parameters

	    if (isset($this->fk_stagiaire))
	        $this->fk_stagiaire = trim($this->fk_stagiaire);
        if (isset($this->fk_calendrier))
            $this->fk_calendrier = trim($this->fk_calendrier);
        if (isset($this->fk_session))
            $this->fk_session = trim($this->fk_session);
        if (isset($this->heures))
            $this->heures = (float)$this->heures;

        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element ." SET";

        $sql .= " fk_stagiaire=" . (isset($this->fk_stagiaire) ? $this->fk_stagiaire : "null") . ",";
        $sql .= " fk_session=" . (isset($this->fk_session) ? $this->fk_session : "null") . ",";
        $sql .= " fk_calendrier=" . (isset($this->fk_calendrier) ? $this->fk_calendrier : "null") . ",";
        $sql .= " heures=" . (isset($this->heures) ? "'" . $this->heures . "'" : 'null') . ",";
        $sql .= " mail_sended=" . intval($this->mail_sended) . ",";
        $sql .= " planned_absence=" . (isset($this->planned_absence) ? $this->planned_absence : "null");


        $sql .= " WHERE rowid=" . $this->id;

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
                // want this action calls a trigger.

                // // Call triggers
                 include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                 $interface=new Interfaces($this->db);
                 $result=$interface->run_triggers('AGEFODDSESSIONSTAGIAIREHEURES_UPDATE',$this,$user,$langs,$conf);
                 if ($result < 0) { $error++; $this->errors=$interface->errors; }
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
	 * Load object in memory from the database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id) {
	    $sql = "SELECT";
	    $sql .= " t.rowid,";
	    $sql .= " t.entity,";
	    $sql .= " t.fk_stagiaire,";
	    $sql .= " t.fk_session,";
	    $sql .= " t.fk_calendrier,";
	    $sql .= " t.heures,";
	    $sql .= " t.fk_user_author,";
	    $sql .= " t.datec,";
	    $sql .= " t.tms,";
	    $sql .= " CONCAT(a.nom,' ', a.prenom) as nom_stagiaire, ";
	    $sql .= " t.mail_sended, ";
	    $sql .= " t.planned_absence";
	    $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
	    $sql .= " LEFT JOIN " .MAIN_DB_PREFIX . "agefodd_stagiaire as a ON a.rowid = t.fk_stagiaire";
	    $sql .= " WHERE t.rowid = " . $id;

	    dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
	    $resql = $this->db->query($sql);
	    if ($resql) {
	        if ($this->db->num_rows($resql)) {
	            $obj = $this->db->fetch_object($resql);

	            $this->id = $obj->rowid;
	            $this->entity = $obj->entity;
	            $this->fk_stagiaire = $obj->fk_stagiaire;
	            $this->nom_stagiaire = $obj->nom_stagiaire;
	            $this->fk_session = $obj->fk_session;
	            $this->fk_calendrier = $obj->fk_calendrier;
	            $this->heures= $obj->heures;
	            $this->planned_absence= $obj->planned_absence;
	            $this->fk_user_author = $obj->fk_user_author;
	            $this->datec = $this->db->jdate($obj->datec);
	            $this->tms = $this->db->jdate($obj->tms);
	        }
	        $this->db->free($resql);

	        return 1;
	    } else {
	        $this->error = "Error " . $this->db->lasterror();
	        dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
	        return - 1;
	    }
	}

	public function fetchAllBy($field_value, $field)
	{
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.$this->table_element.' WHERE '.$field.' = ';
		if (is_numeric($field_value)) $sql.= $field_value;
		else $sql.= "'".$this->db->escape($field_value)."'";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->lines = array();
			while ($obj = $this->db->fetch_object($resql))
			{
				$line = new Agefoddsessionstagiaireheures($this->db);
				$line->fetch($obj->rowid);

				$this->lines[] = $line;
			}

			return 1;
		}
		else
		{
			$this->error = "Error " . $this->db->lasterror();
	        dol_syslog(get_class($this) . "::fetchAllBy " . $this->error, LOG_ERR);
	        return -1;
		}
	}

	/**
	 * Retourne un créneaux horaire de la session indiquée
	 *
	 * @param int $id session
	 * @param int $trainee
	 * @param int $calendar
	 * @return int int <0 if KO, >0 if OK
	 */
	public function fetch_by_session($id, $trainee, $calendar)
	{
	    $sql = "SELECT t.rowid,";
	    $sql .= " t.entity,";
	    $sql .= " t.fk_stagiaire,";
	    $sql .= " t.fk_session,";
	    $sql .= " t.fk_calendrier,";
	    $sql .= " t.heures,";
	    $sql .= " t.fk_user_author,";
	    $sql .= " t.datec,";
	    $sql .= " t.tms,";
	    $sql .= " t.planned_absence,";
	    $sql .= " CONCAT(a.nom,' ', a.prenom) as nom_stagiaire";
	    $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
	    $sql .= " LEFT JOIN " .MAIN_DB_PREFIX . "agefodd_stagiaire as a ON a.rowid = t.fk_stagiaire";
	    $sql .= " WHERE t.fk_session = " . $id;
	    $sql .= " AND t.fk_stagiaire = " . $trainee;
	    $sql .= " AND t.fk_calendrier = " . $calendar;

	    dol_syslog(get_class($this) . "::fetch_by_session", LOG_DEBUG);
	    $resql = $this->db->query($sql);

	    if ($resql) {
	        if ($this->db->num_rows($resql)) {
	            $obj = $this->db->fetch_object($resql);

	            $this->id = $obj->rowid;
	            $this->entity = $obj->entity;
	            $this->fk_stagiaire = $obj->fk_stagiaire;
	            $this->nom_stagiaire = $obj->nom_stagiaire;
	            $this->fk_session = $obj->fk_session;
	            $this->fk_calendrier = $obj->fk_calendrier;
	            $this->heures= $obj->heures;
	            $this->fk_user_author = $obj->fk_user_author;
	            $this->datec = $this->db->jdate($obj->datec);
	            $this->tms = $this->db->jdate($obj->tms);
	            $this->planned_absence = $obj->planned_absence;
	        } else {
	            return 0;
	        }
	        $this->db->free($resql);

	        return 1;
	    } else {
	        $this->error = "Error " . $this->db->lasterror();
	        dol_syslog(get_class($this) . "::fetch_by_session " . $this->error, LOG_ERR);
	        return - 1;
	    }
	}

	/**
	 * Retourne tous les créneaux horaire de la session indiquée d'un stagiaire
	 *
	 * @param int $id session
	 * @param int $trainee trainee in session
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_all_by_session($id, $trainee)
	{
	    $sql = "SELECT t.rowid,";
	    $sql .= " t.entity,";
	    $sql .= " t.fk_stagiaire,";
	    $sql .= " t.fk_session,";
	    $sql .= " t.fk_calendrier,";
	    $sql .= " t.heures,";
	    $sql .= " t.fk_user_author,";
	    $sql .= " t.datec,";
	    $sql .= " t.tms,";
	    $sql .= " CONCAT(a.nom,' ', a.prenom) as nom_stagiaire";
	    $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
	    $sql .= " LEFT JOIN " .MAIN_DB_PREFIX . "agefodd_stagiaire as a ON a.rowid = t.fk_stagiaire";
	    $sql .= " WHERE t.fk_session = " . $id;
	    $sql .= " AND t.fk_stagiaire = " . $trainee;

	    dol_syslog(get_class($this) . "::".__METHOD__ , LOG_DEBUG);
	    $resql = $this->db->query($sql);

	    if ($resql) {
	        if ($this->db->num_rows($resql)) {
	            while($obj = $this->db->fetch_object($resql)){
	                $line = new Agefoddsessionstagiaireheuresline($this->db);
	                $line->id = $obj->rowid;
	                $line->entity = $obj->entity;
	                $line->fk_stagiaire = $obj->fk_stagiaire;
	                $line->nom_stagiaire = $obj->nom_stagiaire;
	                $line->fk_session = $obj->fk_session;
	                $line->fk_calendrier = $obj->fk_calendrier;
	                $line->heures= $obj->heures;
	                $line->fk_user_author = $obj->fk_user_author;
	                $line->datec = $this->db->jdate($obj->datec);
	                $line->tms = $this->db->jdate($obj->tms);

	                $this->lines[] = $line;
	            }
	        } else {
	            return 0;
	        }
	        $this->db->free($resql);

	        return 1;
	    } else {
	        $this->error = "Error " . $this->db->lasterror();
	        dol_syslog(get_class($this) . "::".__METHOD__ . ' Error ' . $this->error, LOG_ERR);
	        return - 1;
	    }
	}

	/**
	 * @param int $traineeid
	 * @return float total hours spent in all session by the trainee
	 */
	public function heures_stagiaire_totales($traineeid)
	{
	    $trainee = (int) $traineeid;
	    $sql = 'SELECT fk_session_agefodd as sessid FROM '.MAIN_DB_PREFIX.'agefodd_session_stagiaire';
	    $sql.= ' WHERE fk_stagiaire = ' . $trainee;

	    $resql = $this->db->query($sql);
	    $result = 0;
	    if ($resql) {
	        while($obj = $this->db->fetch_object($resql)) {
	            $result += $this->heures_stagiaire($obj->sessid, $trainee);
	        }
	    } else {
		    $this->error = "Error " . $this->db->lasterror();
		    dol_syslog(get_class($this) . ":: ".__METHOD__ . $this->error, LOG_ERR);
		    return - 1;
	    }

	    return $result;
	}

	/**
	 *
	 * @param int $sessid
	 * @param int $traineeid
	 * @return float total hours spent by the trainee on the session
	 */
	public function heures_stagiaire($sessid, $traineeid)
	{
        $sql = 'SELECT SUM(heures) as total FROM '.MAIN_DB_PREFIX.$this->table_element;
        $sql .= ' WHERE fk_stagiaire = ' . $traineeid;
        $sql .= ' AND fk_session = ' . $sessid;

        dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if (!empty($obj->total))
            {
	            return (float) $obj->total;
            } elseif ($obj->total===0) {
	            return 0;
            } else {
            	return null;
	        }
        }

        return 0;

	}

	/**
	 * @param $sessid
	 * @param $traineeid
	 * @return array|int
	 * @throws Exception
	 */
    public function fetch_heures_stagiaire_per_type($sessid, $traineeid)
    {
    	global $conf;

	    dol_include_once('/agefodd/lib/agefodd.lib.php');
	    dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
	    dol_include_once('/agefodd/class/agefodd_session_calendrier_formateur.class.php');

        $sql = 'SELECT c.heured, c.heuref, h.heures, c.calendrier_type FROM '.MAIN_DB_PREFIX.$this->table_element.' h';
        $sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'agefodd_session_calendrier c ON h.fk_calendrier = c.rowid';
        $sql .= ' WHERE fk_stagiaire = ' . $traineeid;
        $sql .= ' AND fk_session = ' . $sessid;
	    $sql .= $this->db->order('c.calendrier_type');

        $resql = $this->db->query($sql);

        if ($resql) {
            $TRes = array();
	        $calrem = new Agefodd_sesscalendar($this->db);
	        $calrem->sessid=$sessid;

            while($obj = $this->db->fetch_object($resql))
            {
	            $calrem->heured=$this->db->jdate($obj->heured);
	            $calrem->heuref=$this->db->jdate($obj->heuref);

	            $TTrainerCalendar = _getCalendrierFormateurFromCalendrier($calrem);

	            if (is_array($TTrainerCalendar) && count($TTrainerCalendar)>0) {
	            	foreach($TTrainerCalendar as $calItem) {
	            		if($calItem->status==Agefoddsessionformateurcalendrier::STATUS_FINISH) {
				            $TRes[$obj->calendrier_type][$calItem->fk_agefodd_session_formateur] += $obj->heures;
			            } /*else {
	            		    $TRes[$obj->calendrier_type][0] += $obj->heures;
	            		}*/
		            }
	            } else {
		            $TRes[$obj->calendrier_type][0] += $obj->heures;
	            }
            }

            return $TRes;

        } else {
        	$this->error = "Error " . $this->db->lasterror();
		    dol_syslog(get_class($this) . ":: ".__METHOD__ . $this->error, LOG_ERR);
		    return - 1;
        }

        return 0;
    }

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}

	/**
	 * Set Real Time according trainee status
	 * For not present or cancelled remove all time inputed
	 * For present or patially present fill blanks with missing date
	 *
	 * @param     $user
	 * @param int $sessId
	 * @param int $stagiaireId
	 * @return float|int
	 * @throws Exception
	 */
	public function setRealTimeAccordingTraineeStatus($user, $sessId = 0, $stagiaireId = 0)
	{
		global $conf;

		if (!empty($conf->global->AGF_USE_REAL_HOURS)) {

			$error = 0;
			$sessta = new Agefodd_session_stagiaire($this->db);
			$res = $sessta->fetch_by_trainee($sessId, $stagiaireId);
			if ($res < 0) {
				$this->errors[] = $sessta->error;
				$error++;
			} elseif ($res == 0) {
				return 0;
			}

			//Load all time input for this trainee in this session
			$res = $this->fetch_all_by_session($sessId, $stagiaireId);
			if ($res < 0) {
				return -1;
			}

			$this->db->begin();
			if (in_array($sessta->status_in_session, $sessta->statusDeleteTime)) {
				foreach ($this->lines as $creneaux) {

					$res = $creneaux->delete($user);
					if ($res < 0) {
						$error++;
					}
				}
			} elseif ($sessta->status_in_session == Agefodd_session_stagiaire::STATUS_IN_SESSION_TOTALLY_PRESENT) {
				//Time already input for this trainee in this session
				$TCrenauxSta = array();
				foreach ($this->lines as $creneauxSta) {
					$TCrenauxSta[$creneauxSta->fk_calendrier] = $creneauxSta->fk_calendrier;
				}

				$cal = new Agefodd_sesscalendar($this->db);
				$res = $cal->fetch_all($sessId);
				if ($res < 0) {
					$this->errors[] = $cal->error;
					$error++;
				} else {
					foreach ($cal->lines as $creneauxCal) {
						//We Compute, if real time trainee is not already input for this trainee we set it
						if (!array_key_exists($creneauxCal->id, $TCrenauxSta) && in_array($creneauxCal->status, $cal->statusCountTime) && $creneauxCal->date_session < dol_now()) {
							$new_heures = new self($this->db);
							$new_heures->fk_stagiaire = $stagiaireId;
							//$new_heures->nom_stagiaire = $creneaux->nom_stagiaire;
							$new_heures->fk_user_author = $user->id;
							$new_heures->fk_calendrier = $creneauxCal->id;
							$new_heures->fk_session = $sessId;
							$new_heures->heures = ($creneauxCal->heuref - $creneauxCal->heured) / 3600;
							$new_heures->datec = dol_now();
							$res = $new_heures->create($user);
							if ($res < 0) {
								$this->errors[] = $new_heures->error;
								$error++;
							}
						}
					}
				}
			}

			// Commit or rollback
			if ($error) {
				foreach ($this->errors as $errmsg) {
					dol_syslog(get_class($this) . "::" . __METHOD__ . ' ' . $errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			} else {
				$this->db->commit();
				return 1;
			}
		}
		return 0;
	}

	/**
	 * According inputed time, set trainee status
	 *
	 * @param     $user
	 * @param int $sessId
	 * @param int $stagiaireId
	 * @return float|int -1 if KO or status in session
	 * @throws Exception
	 */
	public function setStatusAccordingTime($user, $sessId = 0, $stagiaireId = 0) {

		global $conf, $langs;

		$error = 0;

		if (!empty($conf->global->AGF_USE_REAL_HOURS))
		{

			$cal = new Agefodd_sesscalendar($this->db);
			$res = $cal->fetch_all($sessId);
			if ($res < 0) {
				$this->errors[] = $cal->error;
				$error++;
			} else {

				//Reset trainee status according time set

				//Total time must have been done
				$dureeCalendrier=0;
				foreach ($cal->lines as $creneauxCal) {
					if (in_array($creneauxCal->status, $cal->statusCountTime)) {
						$dureeCalendrier += ($creneauxCal->heuref - $creneauxCal->heured) / 3600;
					}
				}

				$stagiaire = new Agefodd_session_stagiaire($this->db);
				$res = $stagiaire->fetch_by_trainee($sessId, $stagiaireId);
				if ($res < 0) {
					$this->errors[] = $stagiaire->error;
					$error++;
				}
				$orginStatut = $stagiaire->status_in_session;
				$totalheures = $this->heures_stagiaire($sessId, $stagiaireId);
				if (isset($totalheures)) {
					if ((float)$dureeCalendrier == (float)$totalheures) {
						// stagiaire entièrement présent
						$stagiaire->status_in_session = Agefodd_session_stagiaire::STATUS_IN_SESSION_TOTALLY_PRESENT;
					} elseif (!empty($totalheures)) {
						// stagiaire partiellement présent
						$stagiaire->status_in_session = Agefodd_session_stagiaire::STATUS_IN_SESSION_PARTIALLY_PRESENT;
					} elseif (empty($totalheures)) {
						//Not present
						$stagiaire->status_in_session = Agefodd_session_stagiaire::STATUS_IN_SESSION_NOT_PRESENT;
					}
					if ($orginStatut != $stagiaire->status_in_session) {
						$res = $stagiaire->update($user);
						if ($res < 0) {
							$this->errors[] = $stagiaire->error;
							$error++;
						}
					}
				}
			}

			if ($error) {
				foreach ($this->errors as $errmsg) {
					dol_syslog(get_class($this) . "::" . __METHOD__ . ' ' . $errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
				}
				return -1 * $error;
			} else {
				return $stagiaire->status_in_session;
			}
		}
		return 0;
	}
}

/**
 * Class Agefoddsessionstagiaireheuresline
 */
class Agefoddsessionstagiaireheuresline
{
	/**@var DoliDB $db */
	public $db;
    public $error; // !< To return error code (or message)
    public $errors = array (); // !< To return several error codes (or messages)
    public $id;
    public $entity;
    public $fk_stagiaire;
    public $nom_stagiaire;
    public $datec = '';
    public $fk_user_author;
    public $tms = '';
    public $fk_calendrier;
    public $fk_session;
    public $heures;


    public function __construct($db)
	{
		$this->db = $db;
	}

	/**
     * Delete object (trainne in session) in database
     *
     * @param User $user User who deletes
     * @param int $notrigger triggers after, 1=disable triggers
     * @return int <0 if KO, >0 if OK
     */
    public function delete($user, $notrigger = 0) {
        global $db;

        $error = 0;

        $db->begin();

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . 'agefodd_session_stagiaire_heures';
        $sql .= " WHERE rowid = " . $this->id;

        dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
        $resql = $db->query($sql);

        if ($resql) {
            // ...
        } else {
            $error ++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        // Commit or rollback
        if ($error) {
            foreach ( $this->errors as $errmsg ) {
                dol_syslog(get_class($this) . "::remove " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $db->rollback();
            return - 1 * $error;
        } else {
            $db->commit();
            return 1;
        }
    }
}
