<?php
/*
 * Copyright (C) 2012-2014	Florian Henry		<florian.henry@open-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file agefodd/class/agefodd_stagiaire_certif.class.php
 * \ingroup agefodd
 * \brief Manage certificate
 */

// Put here all includes required by your class file
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 * Manage certificate
 */
class Agefodd_stagiaire_certif extends CommonObject {
	public $error; // !< To return error code (or message)
	public $errors = array (); // !< To return several error codes (or messages)
	public $element = 'agfstacertif'; // !< Id that identify managed objects
	public $table_element = 'agefodd_stagiaire_certif'; // !< Name of table without prefix where object is stored
	public $id;
	public $entity;
	public $fk_user_author = '';
	public $fk_user_mod = '';
	public $datec = '';
	public $tms = '';
	public $fk_stagiaire;
	public $fk_session_agefodd;
	public $fk_session_stagiaire;
	public $certif_code;
	public $certif_label;
	public $certif_dt_start = '';
	public $certif_dt_end = '';
	public $certif_dt_warning = '';
	public $mark = '';
	public $lines = array ();
	public $lines_state = array ();

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
		if (isset($this->fk_stagiaire))
			$this->fk_stagiaire = trim($this->fk_stagiaire);
		if (isset($this->fk_session_agefodd))
			$this->fk_session_agefodd = trim($this->fk_session_agefodd);
		if (isset($this->fk_session_stagiaire))
			$this->fk_session_stagiaire = trim($this->fk_session_stagiaire);
		if (isset($this->certif_code))
			$this->certif_code = trim($this->certif_code);
		if (isset($this->certif_label))
			$this->certif_label = trim($this->certif_label);
		if (isset($this->mark))
			$this->mark = trim($this->mark);

			// Check parameters
			// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_stagiaire_certif(";

		$sql .= "entity,";
		$sql .= "fk_user_author,";
		$sql .= "fk_user_mod,";
		$sql .= "datec,";
		$sql .= "fk_stagiaire,";
		$sql .= "fk_session_agefodd,";
		$sql .= "fk_session_stagiaire,";
		$sql .= "certif_code,";
		$sql .= "certif_label,";
		$sql .= "certif_dt_start,";
		$sql .= "certif_dt_end,";
		$sql .= "certif_dt_warning,";
		$sql .= "mark";

		$sql .= ") VALUES (";

		$sql .= " " . $conf->entity . ",";
		$sql .= " '" . $user->id . "',";
		$sql .= " '" . $user->id . "',";
		$sql .= "'" . $this->db->idate(dol_now()) . "',";
		$sql .= " " . (! isset($this->fk_stagiaire) ? 'NULL' : "'" . $this->fk_stagiaire . "'") . ",";
		$sql .= " " . (! isset($this->fk_session_agefodd) ? 'NULL' : "'" . $this->fk_session_agefodd . "'") . ",";
		$sql .= " " . (! isset($this->fk_session_stagiaire) ? 'NULL' : "'" . $this->fk_session_stagiaire . "'") . ",";
		$sql .= " " . (! isset($this->certif_code) ? 'NULL' : "'" . $this->db->escape($this->certif_code) . "'") . ",";
		$sql .= " " . (! isset($this->certif_label) ? 'NULL' : "'" . $this->db->escape($this->certif_label) . "'") . ",";
		$sql .= " " . (! isset($this->certif_dt_start) || dol_strlen($this->certif_dt_start) == 0 ? 'NULL' : "'" . $this->db->idate($this->certif_dt_start) . "'") . ",";
		$sql .= " " . (! isset($this->certif_dt_end) || dol_strlen($this->certif_dt_end) == 0 ? 'NULL' : "'" . $this->db->idate($this->certif_dt_end) . "'") . ",";
		$sql .= " " . (! isset($this->certif_dt_warning) || dol_strlen($this->certif_dt_warning) == 0 ? 'NULL' : "'" . $this->db->idate($this->certif_dt_warning) . "'") . ",";
		$sql .= " " . (empty($this->mark) ? 'NULL' : "'" . $this->db->escape($this->mark) . "'") . "";

		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_stagiaire_certif");

			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.

				// // Call triggers
				// include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
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
	 * Load object in memory from database
	 *
	 * @param int $id object
	 * @param int $id_trainee object
	 * @param int $id_session object
	 * @param int $id_sess_trainee object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id = 0, $id_trainee = 0, $id_session = 0, $id_sess_trainee = 0) {
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.fk_stagiaire,";
		$sql .= " t.fk_session_agefodd,";
		$sql .= " t.fk_session_stagiaire,";
		$sql .= " t.certif_code,";
		$sql .= " t.certif_label,";
		$sql .= " t.certif_dt_start,";
		$sql .= " t.certif_dt_end,";
		$sql .= " t.certif_dt_warning,";
		$sql .= " t.mark";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire_certif as t";

		if (! empty($id)) {
			$sql .= " WHERE t.rowid = " . $id;
		} else {
			$sqlwhere = array ();

			if (! empty($id_trainee)) {
				$sqlwhere[] = "  t.fk_stagiaire = " . $id_trainee;
			}
			if (! empty($id_session)) {
				$sqlwhere[] = " t.fk_session_agefodd = " . $id_session;
			}
			if (! empty($id_sess_trainee)) {
				$sqlwhere[] = " t.fk_session_stagiaire = " . $id_sess_trainee;
			}

			if (count($sqlwhere) > 0) {
				$sql .= " WHERE " . implode(' AND ', $sqlwhere);
			}
		}

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->entity = $obj->entity;
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_stagiaire = $obj->fk_stagiaire;
				$this->fk_session_agefodd = $obj->fk_session_agefodd;
				$this->fk_session_stagiaire = $obj->fk_session_stagiaire;
				$this->certif_code = $obj->certif_code;
				$this->certif_label = $obj->certif_label;
				$this->certif_dt_start = $this->db->jdate($obj->certif_dt_start);
				$this->certif_dt_end = $this->db->jdate($obj->certif_dt_end);
				$this->certif_dt_warning = $this->db->jdate($obj->certif_dt_warning);
				$this->mark = $obj->mark;

				$this->fetch_certif_state($this->id);
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
	 * Load object in memory from database
	 *
	 * @param int $socid
	 * @param string $sortorder order
	 * @param string $sortfield field
	 * @param int $limit page
	 * @param int $offset
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_certif_customer($socid, $sortorder, $sortfield, $limit, $offset, $filter = array()) {
		$sql = "SELECT ";
		$sql .= " DISTINCT ";
		$sql .= "certif.fk_stagiaire,";
		$sql .= "certif.fk_session_agefodd,";
		$sql .= "certif.certif_code,";
		$sql .= "certif.certif_label,";
		$sql .= "certif.certif_dt_end,";
		$sql .= "certif.certif_dt_start,";
		$sql .= "certif.certif_dt_warning,";
		$sql .= "certif.mark,";
		$sql .= "c.intitule as fromintitule,";
		$sql .= "c.ref as fromref,";
		$sql .= "c.ref_interne as fromrefinterne,";
		$sql .= "sta.nom as trainee_name,";
		$sql .= "sta.prenom as trainee_firstname,";
		$sql .= "sta.civilite,";
		$sql .= "sta.mail as trainee_mail,";
		$sql .= "soc.nom as customer_name,";
		$sql .= "soc.rowid as customer_id,";
		$sql .= "s.dated,";
		$sql .= "s.datef";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire_certif as certif";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as s ON certif.fk_session_agefodd=s.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c ON c.rowid = s.fk_formation_catalogue";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid = certif.fk_stagiaire";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as stasess ON sta.rowid = stasess.fk_stagiaire AND stasess.fk_session_agefodd=s.rowid  AND certif.fk_session_stagiaire=stasess.rowid";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "societe as soc ON soc.rowid = sta.fk_soc";

		$sql .= " WHERE s.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";

		// Manage filter
		if (count($filter) > 0) {
			foreach ( $filter as $key => $value ) {
				if (strpos($key, 'date') || strpos($key, 'certif_dt')) // To allow $filter['YEAR(s.dated)']=>$year
                {
                    if($key == "s.dated"){
                        if(!empty($filter['s.dated2'])) $sql .= ' AND ' . $key .' BETWEEN \'' . $value . '\' AND \'' . $filter['s.dated2'] . '\'';
                        else $sql .= ' AND ' . $key . ' = \'' . $value . '\'';
                    }
                    if($key == "s.datef"){
                        if(!empty($filter['s.datef2'])) $sql .= ' AND ' . $key .' BETWEEN \'' . $value . '\' AND \'' . $filter['s.datef2'] . '\'';
                        else $sql .= ' AND ' . $key . ' = \'' . $value . '\'';
                    }
                    if($key == 'certif.certif_dt_start'){
                        if(!empty($filter['certif.certif_dt_start2'])) $sql .= ' AND ' . $key .' BETWEEN \'' . $value . '\' AND \'' . $filter['certif.certif_dt_start2'] . '\'';
                        else $sql .= ' AND ' . $key . ' = \'' . $value . '\'';
                    }
                    if($key == 'certif.certif_dt_end'){
                        if(!empty($filter['certif.certif_dt_end2'])) $sql .= ' AND ' . $key .' BETWEEN \'' . $value . '\' AND \'' . $filter['certif.certif_dt_end2'] . '\'';
                        else $sql .= ' AND ' . $key . ' = \'' . $value . '\'';
                    }


				} elseif (($key == 's.fk_session_place') || ($key == 'f.rowid') || ($key == 's.type_session') || ($key == 's.status')) {
					$sql .= ' AND ' . $key . ' = ' . $value;
				} else {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		if (! empty($socid)) {
			$sql .= ' AND soc.rowid=' . $socid;
		}
		$sql .= " ORDER BY " . $sortfield . ' ' . $sortorder;
		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::fetch_certif_customer", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new Agefodd_CertifExpire_line();

				$line->id_session = $obj->fk_session_agefodd;
				$line->fromintitule = $obj->fromintitule;
				$line->fromref = $obj->fromref;
				$line->fromrefinterne = $obj->fromrefinterne;
				$line->trainee_id = $obj->fk_stagiaire;
				$line->trainee_name = $obj->trainee_name;
				$line->trainee_firstname = $obj->trainee_firstname;
				$line->trainee_mail = $obj->trainee_mail;
				$line->certif_code = $obj->certif_code;
				$line->certif_label = $obj->certif_label;
				$line->certif_dt_end = $this->db->jdate($obj->certif_dt_end);
				$line->certif_dt_start = $this->db->jdate($obj->certif_dt_start);
				$line->certif_dt_warning = $this->db->jdate($obj->certif_dt_warning);
				$line->customer_name = $obj->customer_name;
				$line->customer_id = $obj->customer_id;
				$line->dated = $obj->dated;
				$line->datef = $obj->datef;
				$line->mark = $obj->mark;

				$this->lines[$i] = $line;

				$i ++;
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_certif_customer " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load Certificateion state
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_certif_state($id) {
		$certif_type_array = $this->get_certif_type();

		if (is_array($certif_type_array) && count($certif_type_array) > 0) {
		    $i = 1;
			foreach ( $certif_type_array as $certif_type_id => $certif_type_label ) {
				$line = new AgfStagiaireCertifLineState();

				$line->certif_type = $certif_type_label;
				$line->fk_certif_type = $certif_type_id;
				$line->fk_certif = $id;

				$sqlinner = "SELECT";
				$sqlinner .= " t.rowid,";
				$sqlinner .= " t.certif_state";

				$sqlinner .= " FROM " . MAIN_DB_PREFIX . "agefodd_certif_state as t";
				$sqlinner .= " WHERE t.fk_certif_type=" . $line->fk_certif_type;
				$sqlinner .= " AND  t.fk_certif =" . $line->fk_certif;

				dol_syslog(get_class($this) . "::fetch_certif_state sqlinner", LOG_DEBUG);
				$resqlinner = $this->db->query($sqlinner);
				if ($resqlinner) {
					$numinner = $this->db->num_rows($resqlinner);
					if (! empty($numinner)) {
						$objinner = $this->db->fetch_object($resqlinner);
						$line->certif_state = $objinner->certif_state;

						dol_syslog(get_class($this) . "::fetch_certif_state objinner->certif_state=" . $objinner->certif_state, LOG_DEBUG);

						$line->id = $objinner->rowid;
					}

					$this->db->free($resqlinner);
				} else {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
					return - 1;
				}

				$this->lines_state[$i] = $line;

				$i ++;
			}

			return 1;
		} elseif ($certif_type_array == - 1) {
			dol_syslog(get_class($this) . "::fetch_certif_state " . $this->error, LOG_ERR);
			return - 1;
		}

		return 0;
	}

	/**
	 * Load object Certificate type
	 *
	 * @return array|int Array of certificate type, or <0 if KO
	 *
	 */
	public function get_certif_type() {
		$return_array = array ();
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.intitule,";
		$sql .= " t.active";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_certificate_type as t";
		$sql .= " WHERE t.active=1";
		$sql .= " ORDER BY t.sort";

		dol_syslog(get_class($this) . "::get_certif_type", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$return_array[$obj->rowid] = $obj->intitule;
			}

			return $return_array;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::get_certif_type " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Set Certificate State
	 *
	 * @param User $user User making the call
	 * @param int $certif_id Id object
	 * @param int $certif_type_id type Id object
	 * @param int $certif_state to set
	 *
	 * @return int <0 if KO, >0 if OK
	 *
	 */
	public function set_certif_state($user, $certif_id, $certif_type_id, $certif_state) {
		if (empty($certif_state)) {
			$certif_state = 0;
		}

		$sql = "SELECT";
		$sql .= " t.rowid";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_certif_state as t";
		$sql .= " WHERE t.fk_certif_type=" . $certif_type_id;
		$sql .= " AND  t.fk_certif =" . $certif_id;

		dol_syslog(get_class($this) . "::set_certif_state", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			// Certificate state exists = > We update
			if (! empty($num)) {
				$sqlop = "UPDATE " . MAIN_DB_PREFIX . "agefodd_certif_state SET ";
				$sqlop .= " certif_state=" . $certif_state;
				$sqlop .= ", fk_user_mod=" . $user->id;

				$sqlop .= " WHERE fk_certif_type=" . $certif_type_id;
				$sqlop .= " AND  fk_certif =" . $certif_id;

				dol_syslog(get_class($this) . "::set_certif_state sqlop", LOG_DEBUG);
				$resql = $this->db->query($sqlop);
				if (! $resql) {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::set_certif_state " . $this->error, LOG_ERR);
					return - 1;
				}
				
				return 1;
			} else {
				// Certificate state do not exist yet => we create it
				$sqlop = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_certif_state (fk_user_author,fk_user_mod,datec,fk_certif,fk_certif_type,certif_state) ";
				$sqlop .= " VALUES (";
				$sqlop .= $user->id . ",";
				$sqlop .= $user->id . ",";
				$sqlop .= "'" . $this->db->idate(dol_now()) . "',";
				$sqlop .= $certif_id . ",";
				$sqlop .= $certif_type_id . ",";
				$sqlop .= $certif_state . ")";

				dol_syslog(get_class($this) . "::set_certif_state sqlop", LOG_DEBUG);
				$resql = $this->db->query($sqlop);
				if (! $resql) {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::set_certif_state " . $this->error, LOG_ERR);
					return - 1;
				}
				
				return 1;
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::set_certif_state " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $idtrainee Id of trainee
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_all_by_trainee($idtrainee) {
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.fk_stagiaire,";
		$sql .= " t.fk_session_agefodd,";
		$sql .= " t.fk_session_stagiaire,";
		$sql .= " t.certif_code,";
		$sql .= " t.certif_label,";
		$sql .= " t.certif_dt_start,";
		$sql .= " t.certif_dt_end,";
		$sql .= " t.certif_dt_warning,";
		$sql .= " t.mark";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire_certif as t";

		$sql .= " WHERE t.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";
		$sql .= " AND t.fk_stagiaire='" . $idtrainee . "'";

		$sql .= " ORDER BY t.datec desc";

		dol_syslog(get_class($this) . "::fetch_all_by_trainee", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new AgfStagiaireCertifLine();

				$line->id = $obj->rowid;

				$line->entity = $obj->entity;
				$line->fk_user_author = $obj->fk_user_author;
				$line->fk_user_mod = $obj->fk_user_mod;
				$line->datec = $this->db->jdate($obj->datec);
				$line->tms = $this->db->jdate($obj->tms);
				$line->fk_stagiaire = $obj->fk_stagiaire;
				$line->fk_session_agefodd = $obj->fk_session_agefodd;
				$line->fk_session_stagiaire = $obj->fk_session_stagiaire;
				$line->certif_code = $obj->certif_code;
				$line->certif_label = $obj->certif_label;
				$line->certif_dt_start = $this->db->jdate($obj->certif_dt_start);
				$line->certif_dt_end = $this->db->jdate($obj->certif_dt_end);
				$line->certif_dt_warning = $this->db->jdate($obj->certif_dt_warning);
				$line->mark = $obj->mark;

				$this->fetch_certif_state($obj->rowid);
				$line->lines_state = $this->lines_state;

				$this->lines[$i] = $line;

				$i ++;
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all_by_trainee " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit offset limit
	 * @param int $offset offset limit
	 * @param array $filter array of filters
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_all($sortorder, $sortfield, $limit, $offset, array $filter = array()) {
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.fk_stagiaire,";
		$sql .= " t.fk_session_agefodd,";
		$sql .= " t.fk_session_stagiaire,";
		$sql .= " t.certif_code,";
		$sql .= " t.certif_label,";
		$sql .= " t.certif_dt_start,";
		$sql .= " t.certif_dt_end,";
		$sql .= " t.certif_dt_warning,";
		$sql .= " t.mark";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire_certif as t";

		$sql .= " WHERE t.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
		    foreach ($filter as $key => $value) {
		        if (strpos('t.fk', $key) == 0) {
		            $sqlwhere [] = $key . ' =' . $this->db->escape($value);
		        } else {
		            $sqlwhere [] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
		        }

		    }
		}
		if (count($sqlwhere) > 0) {
		    $sql .= ' AND ' . implode(' AND ', $sqlwhere);
		}

        if (!empty($sortfield)) $sql .= ' ORDER BY ' . $sortfield . ' ' . $sortorder . ' ';

		if (!empty($limit)) {
		    $sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::fetch_all", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new Agefodd_stagiaire_certif($this->db);

				$line->id = $obj->rowid;

				$line->entity = $obj->entity;
				$line->fk_user_author = $obj->fk_user_author;
				$line->fk_user_mod = $obj->fk_user_mod;
				$line->datec = $this->db->jdate($obj->datec);
				$line->tms = $this->db->jdate($obj->tms);
				$line->fk_stagiaire = $obj->fk_stagiaire;
				$line->fk_session_agefodd = $obj->fk_session_agefodd;
				$line->fk_session_stagiaire = $obj->fk_session_stagiaire;
				$line->certif_code = $obj->certif_code;
				$line->certif_label = $obj->certif_label;
				$line->certif_dt_start = $this->db->jdate($obj->certif_dt_start);
				$line->certif_dt_end = $this->db->jdate($obj->certif_dt_end);
				$line->certif_dt_warning = $this->db->jdate($obj->certif_dt_warning);
				$line->mark = $obj->mark;

				$this->lines[$i] = $line;

				$i ++;
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all " . $this->error, LOG_ERR);
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
		if (isset($this->fk_stagiaire))
			$this->fk_stagiaire = trim($this->fk_stagiaire);
		if (isset($this->fk_session_agefodd))
			$this->fk_session_agefodd = trim($this->fk_session_agefodd);
		if (isset($this->fk_session_stagiaire))
			$this->fk_session_stagiaire = trim($this->fk_session_stagiaire);
		if (isset($this->certif_code))
			$this->certif_code = trim($this->certif_code);
		if (isset($this->certif_label))
			$this->certif_label = trim($this->certif_label);
		if (isset($this->mark))
			$this->mark = trim($this->mark);

			// Check parameters
			// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_stagiaire_certif SET";

		$sql .= " entity=" . $this->entity/*$conf->entity*/ . ",";
		$sql .= " fk_user_mod=" . $user->id . ",";
		$sql .= " fk_stagiaire=" . (isset($this->fk_stagiaire) ? $this->fk_stagiaire : "null") . ",";
		$sql .= " fk_session_agefodd=" . (isset($this->fk_session_agefodd) ? $this->fk_session_agefodd : "null") . ",";
		$sql .= " fk_session_stagiaire=" . (isset($this->fk_session_stagiaire) ? $this->fk_session_stagiaire : "null") . ",";
		$sql .= " certif_code=" . (isset($this->certif_code) ? "'" . $this->db->escape($this->certif_code) . "'" : "null") . ",";
		$sql .= " certif_label=" . (isset($this->certif_label) ? "'" . $this->db->escape($this->certif_label) . "'" : "null") . ",";
		$sql .= " certif_dt_start=" . (dol_strlen($this->certif_dt_start) != 0 ? "'" . $this->db->idate($this->certif_dt_start) . "'" : 'null') . ",";
		$sql .= " certif_dt_end=" . (dol_strlen($this->certif_dt_end) != 0 ? "'" . $this->db->idate($this->certif_dt_end) . "'" : 'null') . ",";
		$sql .= " certif_dt_warning=" . (dol_strlen($this->certif_dt_warning) != 0 ? "'" . $this->db->idate($this->certif_dt_warning) . "'" : 'null') . ",";
		$sql .= " mark=" . (isset($this->mark) ? "'" . $this->db->escape($this->mark) . "'" : "null") . "";

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
				// want this action call a trigger.

				// // Call triggers
				// include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
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
	 * Delete object in database
	 *
	 * @param User $user that delete
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		$this->db->begin();

		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.

				// // Call triggers
				// include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}

		if (! $error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire_certif";
			$sql .= " WHERE rowid=" . $this->id;

			dol_syslog(get_class($this) . "::delete");
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}

			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_certif_state";
			$sql .= " WHERE fk_certif=" . $this->id;

			dol_syslog(get_class($this) . "::delete");
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
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
	 * Give information on the object
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function info($id) {
		$sql = "SELECT";
		$sql .= " p.rowid, p.entity, p.datec, p.tms, p.fk_user_mod, p.fk_user_author";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire_certif as p";
		$sql .= " WHERE p.rowid = " . $id;

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->entity = $obj->entity;
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
				$this->user_modification = $obj->fk_user_mod;
				$this->user_creation = $obj->fk_user_author;
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
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen() {
		$this->id = 0;

		$this->entity = '';
		$this->fk_user_author = '';
		$this->fk_user_mod = '';
		$this->datec = '';
		$this->tms = '';
		$this->fk_stagiaire = '';
		$this->fk_session_agefodd = '';
		$this->fk_session_stagiaire = '';
		$this->certif_code = '';
		$this->certif_label = '';
		$this->certif_dt_start = '';
		$this->certif_dt_end = '';
		$this->certif_dt_warning = '';
		$this->mark = '';
	}
}

/**
 * Session line Class
 */
class AgfStagiaireCertifLine {
	public $id;
	public $entity;
	public $fk_stagiaire;
	public $fk_session_agefodd;
	public $fk_session_stagiaire;
	public $certif_code;
	public $certif_label;
	public $mark;
	public $certif_dt_start = '';
	public $certif_dt_end = '';
	public $certif_dt_warning = '';
	public $lines_state = array ();
	public $fk_user_author;
	public $fk_user_mod;
	public $datec;
	public $tms;
	public function __construct() {
		return 1;
	}
}

/**
 * Session line Class
 */
class AgfStagiaireCertifLineState {
	public $id;
	public $fk_certif;
	public $fk_certif_type;
	public $certif_state;
	public $certif_type;
	public function __construct() {
		return 1;
	}
}

/**
 * Certif line
 */
class Agefodd_CertifExpire_line {
	public $id_session;
	public $fromintitule;
	public $fromref;
	public $fromrefinterne;
	public $trainee_id;
	public $trainee_name;
	public $trainee_firstname;
	public $trainee_mail;
	public $certif_dt_end;
	public $certif_dt_start;
	public $certif_dt_warning;
	public $certif_code;
	public $certif_label;
	public $customer_name;
	public $customer_id;
	public $dated;
	public $datef;
	public $mark;
}
