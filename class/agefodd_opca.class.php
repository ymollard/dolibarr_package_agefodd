<?php
/*
 * Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012-2014  Florian Henry <florian.henry@open-concept.pro>
 * Copyright (C) 2012 		JF FERRY <jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * \file agefodd/class/agefodd_opca.class.php
 * \ingroup agefodd
 * \brief class to manage 'OPCA' on agefodd module
 */

// Put here all includes required by your class file
// require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once ('agsession.class.php');

/**
 * Put here description of your class
 */
class Agefodd_opca extends CommonObject {
	public $error; // !< To return error code (or message)
	public $errors = array (); // !< To return several error codes (or messages)
	public $element = 'agefodd_opca'; // !< Id that identify managed objects
	public $table_element = 'agefodd_opca'; // !< Name of table without prefix where object is stored
	public $id;
	public $fk_session_trainee;
	public $fk_soc_trainee;
	public $fk_session_agefodd;
	public $date_ask_OPCA = '';
	public $is_OPCA;
	public $fk_soc_OPCA;
	public $fk_socpeople_OPCA;
	public $num_OPCA_soc;
	public $num_OPCA_file;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $tms = '';
	public $lines = array ();

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
		if (isset($this->fk_session_trainee))
			$this->fk_session_trainee = trim($this->fk_session_trainee);
		if (isset($this->fk_soc_trainee))
			$this->fk_soc_trainee = trim($this->fk_soc_trainee);
		if (isset($this->fk_session_agefodd))
			$this->fk_session_agefodd = trim($this->fk_session_agefodd);
		if (isset($this->is_OPCA))
			$this->is_OPCA = trim($this->is_OPCA);
		if (isset($this->fk_soc_OPCA))
			$this->fk_soc_OPCA = trim($this->fk_soc_OPCA);
		if (isset($this->fk_socpeople_OPCA))
			$this->fk_socpeople_OPCA = trim($this->fk_socpeople_OPCA);
		if (isset($this->num_OPCA_soc))
			$this->num_OPCA_soc = trim($this->num_OPCA_soc);
		if (isset($this->num_OPCA_file))
			$this->num_OPCA_file = trim($this->num_OPCA_file);

			// Check parameters
			// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_opca(";

		$sql .= "fk_session_trainee,";
		$sql .= "fk_soc_trainee,";
		$sql .= "fk_session_agefodd,";
		$sql .= "date_ask_OPCA,";
		$sql .= "is_OPCA,";
		$sql .= "fk_soc_OPCA,";
		$sql .= "fk_socpeople_OPCA,";
		$sql .= "num_OPCA_soc,";
		$sql .= "num_OPCA_file,";
		$sql .= "fk_user_author,";
		$sql .= "datec,";
		$sql .= "fk_user_mod";

		$sql .= ") VALUES (";

		$sql .= " " . (empty($this->fk_session_trainee) ? 'NULL' : $this->fk_session_trainee) . ",";
		$sql .= " " . (empty($this->fk_soc_trainee) ? 'NULL' : $this->fk_soc_trainee) . ",";
		$sql .= " " . (empty($this->fk_session_agefodd) ? 'NULL' : $this->fk_session_agefodd) . ",";
		$sql .= " " . (empty($this->date_ask_OPCA) || dol_strlen($this->date_ask_OPCA) == 0 ? 'NULL' : "'" . $this->db->idate($this->date_ask_OPCA) . "'") . ",";
		$sql .= " " . (empty($this->is_OPCA) ? '0' : $this->is_OPCA) . ",";
		$sql .= " " . (empty($this->fk_soc_OPCA) ? 'NULL' : $this->fk_soc_OPCA) . ",";
		$sql .= " " . (empty($this->fk_socpeople_OPCA) ? 'NULL' : $this->fk_socpeople_OPCA) . ",";
		$sql .= " " . (empty($this->num_OPCA_soc) ? 'NULL' : "'" . $this->db->escape($this->num_OPCA_soc) . "'") . ",";
		$sql .= " " . (empty($this->num_OPCA_file) ? 'NULL' : "'" . $this->db->escape($this->num_OPCA_file) . "'") . ",";
		$sql .= " " . $user->id . ",";
		$sql .= " '" . $this->db->idate(dol_now()) . "',";
		$sql .= " " . $user->id;

		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_opca");

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
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id) {
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_session_trainee,";
		$sql .= " t.fk_soc_trainee,";
		$sql .= " t.fk_session_agefodd,";
		$sql .= " t.date_ask_OPCA,";
		$sql .= " t.is_OPCA,";
		$sql .= " t.fk_soc_OPCA,";
		$sql .= " t.fk_socpeople_OPCA,";
		$sql .= " t.num_OPCA_soc,";
		$sql .= " t.num_OPCA_file,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_opca as t";
		$sql .= " WHERE t.rowid = " . $id;

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->fk_session_trainee = $obj->fk_session_trainee;
				$this->fk_soc_trainee = $obj->fk_soc_trainee;
				$this->fk_session_agefodd = $obj->fk_session_agefodd;
				$this->date_ask_OPCA = $this->db->jdate($obj->date_ask_OPCA);
				$this->is_OPCA = $obj->is_OPCA;
				$this->fk_soc_OPCA = $obj->fk_soc_OPCA;
				$this->fk_socpeople_OPCA = $obj->fk_socpeople_OPCA;
				$this->num_OPCA_soc = $obj->num_OPCA_soc;
				$this->num_OPCA_file = $obj->num_OPCA_file;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
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

		if (isset($this->fk_session_trainee))
			$this->fk_session_trainee = trim($this->fk_session_trainee);
		if (isset($this->fk_soc_trainee))
			$this->fk_soc_trainee = trim($this->fk_soc_trainee);
		if (isset($this->fk_session_agefodd))
			$this->fk_session_agefodd = trim($this->fk_session_agefodd);
		if (isset($this->is_OPCA))
			$this->is_OPCA = trim($this->is_OPCA);
		if (isset($this->fk_soc_OPCA))
			$this->fk_soc_OPCA = trim($this->fk_soc_OPCA);
		if (isset($this->fk_socpeople_OPCA))
			$this->fk_socpeople_OPCA = trim($this->fk_socpeople_OPCA);
		if (isset($this->num_OPCA_soc))
			$this->num_OPCA_soc = trim($this->num_OPCA_soc);
		if (isset($this->num_OPCA_file))
			$this->num_OPCA_file = trim($this->num_OPCA_file);

			// Check parameters
			// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_opca SET";

		$sql .= " fk_session_trainee=" . (! empty($this->fk_session_trainee) ? $this->fk_session_trainee : "null") . ",";
		$sql .= " fk_soc_trainee=" . (isset($this->fk_soc_trainee) ? $this->fk_soc_trainee : "null") . ",";
		$sql .= " fk_session_agefodd=" . (isset($this->fk_session_agefodd) ? $this->fk_session_agefodd : "null") . ",";
		$sql .= " date_ask_OPCA=" . (dol_strlen($this->date_ask_OPCA) != 0 ? "'" . $this->db->idate($this->date_ask_OPCA) . "'" : 'null') . ",";
		$sql .= " is_OPCA=" . (! empty($this->is_OPCA) ? $this->is_OPCA : "0") . ",";
		$sql .= " fk_soc_OPCA=" . (! empty($this->fk_soc_OPCA) ? $this->fk_soc_OPCA : "null") . ",";
		$sql .= " fk_socpeople_OPCA=" . (! empty($this->fk_socpeople_OPCA) ? $this->fk_socpeople_OPCA : "null") . ",";
		$sql .= " num_OPCA_soc=" . (isset($this->num_OPCA_soc) ? "'" . $this->db->escape($this->num_OPCA_soc) . "'" : "null") . ",";
		$sql .= " num_OPCA_file=" . (isset($this->num_OPCA_file) ? "'" . $this->db->escape($this->num_OPCA_file) . "'" : "null") . ",";
		$sql .= " datec=" . (dol_strlen($this->datec) != 0 ? "'" . $this->db->idate($this->datec) . "'" : 'null') . ",";
		$sql .= " fk_user_mod=" . $user->id . ",";
		$sql .= " tms=" . (dol_strlen($this->tms) != 0 ? "'" . $this->db->idate($this->tms) . "'" : 'null') . "";

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
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_opca";
			$sql .= " WHERE rowid=" . $this->id;

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
	 * Load an object from its id and create a new one in database
	 *
	 * @param int $fromid of object to clone
	 * @return int id of clone
	 */
	public function createFromClone($fromid) {
		global $user;

		$error = 0;

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id = 0;
		$object->statut = 0;

		// Clear fields
		// ...

		// Create clone
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$error ++;
		}

		if (! $error) {
		}

		// End
		if (! $error) {
			$this->db->commit();
			return $object->id;
		} else {
			$this->db->rollback();
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

		$this->fk_soc_trainee = '';
		$this->fk_session_agefodd = '';
		$this->date_ask_OPCA = '';
		$this->is_OPCA = '';
		$this->fk_soc_OPCA = '';
		$this->fk_socpeople_OPCA = '';
		$this->num_OPCA_soc = '';
		$this->num_OPCA_file = '';
		$this->fk_user_author = '';
		$this->datec = '';
		$this->fk_user_mod = '';
		$this->tms = '';
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $fk_soc_trainee soc trainee in session
	 * @param int $id_session session
	 * @param int $fk_trainee_session trainee in session
	 * @return int <0 if KO, >0 if OK (rowid)
	 */
	public function getOpcaForTraineeInSession($fk_soc_trainee, $id_session, $fk_trainee_session = 0) {
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_session_trainee,";
		$sql .= " t.fk_soc_trainee,";
		$sql .= " t.fk_session_agefodd,";
		$sql .= " t.date_ask_OPCA as date_ask_opca,";
		$sql .= " t.is_OPCA as is_opca,";
		$sql .= " t.fk_soc_OPCA as fk_soc_opca,";
		$sql .= " t.fk_socpeople_OPCA as fk_socpeople_opca,";
		$sql .= " concactOPCA.lastname as concact_opca_name, concactOPCA.firstname as concact_opca_firstname,";
		$sql .= " t.num_OPCA_soc as num_opca_soc,";
		$sql .= " t.num_OPCA_file as num_opca_file,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_opca as t";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as concactOPCA ";
		$sql .= " ON t.fk_socpeople_OPCA = concactOPCA.rowid";

		$sql .= " WHERE t.fk_soc_trainee = " . $fk_soc_trainee;
		$sql .= " AND t.fk_session_agefodd = " . $id_session;
		if (! empty($fk_trainee_session)) {
			$sql .= " AND (t.fk_session_trainee=" . $fk_trainee_session . ' OR t.fk_session_trainee IS NULL)';
			$sql .= ' ORDER BY t.fk_session_trainee';
		}

		dol_syslog(get_class($this) . "::getOpcaForTraineeInSession", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->opca_rowid = $obj->rowid;
				$this->fk_session_trainee = $obj->fk_session_trainee;
				$this->fk_soc_trainee = $obj->fk_soc_trainee;
				$this->fk_session_agefodd = $obj->fk_session_agefodd;
				$this->date_ask_OPCA = $this->db->jdate($obj->date_ask_opca);
				$this->is_OPCA = $obj->is_opca;
				$this->fk_soc_OPCA = $obj->fk_soc_opca;
				$this->fk_socpeople_OPCA = $obj->fk_socpeople_opca;
				$this->num_OPCA_soc = $obj->num_opca_soc;
				$this->num_OPCA_file = $obj->num_opca_file;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);

				$this->soc_OPCA_name = $this->getValueFrom('societe', $this->fk_soc_OPCA, 'nom');
				$this->contact_name_OPCA = $obj->concact_opca_name . ' ' . $obj->concact_opca_firstname;
			} else {
				$this->opca_rowid = '';
				$this->fk_soc_trainee = '';
				$this->fk_session_agefodd = '';
				$this->date_ask_OPCA = '';
				$this->is_OPCA = 0;
				$this->fk_soc_OPCA = '';
				$this->fk_socpeople_OPCA = '';
				$this->num_OPCA_soc = '';
				$this->num_OPCA_file = '';
				$this->fk_user_author = '';
				$this->datec = '';
				$this->fk_user_mod = '';
				$this->tms = '';
				$this->soc_OPCA_name = '';
				$this->contact_name_OPCA = '';
			}
			$this->db->free($resql);

			return $this->opca_rowid;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::getOpcaForTraineeInSession " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $id_session session
	 * @return int <0 if KO, >0 if OK (rowid)
	 */
	public function getOpcaSession($id_session) {
		$sql = "SELECT DISTINCT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_session_trainee,";
		$sql .= " t.fk_soc_trainee,";
		$sql .= " t.fk_session_agefodd,";
		$sql .= " t.date_ask_OPCA as date_ask_opca,";
		$sql .= " t.is_OPCA as is_opca,";
		$sql .= " t.fk_soc_OPCA as fk_soc_opca,";
		$sql .= " t.fk_socpeople_OPCA as fk_socpeople_opca,";
		$sql .= " concactOPCA.lastname as concact_opca_name, concactOPCA.firstname as concact_opca_firstname,";
		$sql .= " t.num_OPCA_soc as num_opca_soc,";
		$sql .= " t.num_OPCA_file as num_opca_file,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_opca as t";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as concactOPCA ";
		$sql .= " ON t.fk_socpeople_OPCA = concactOPCA.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as sessta ON sessta.rowid=t.fk_session_trainee";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sessta.fk_stagiaire AND sta.fk_soc=t.fk_soc_trainee";

		$sql .= " WHERE t.fk_session_agefodd = " . $id_session;
		dol_syslog(get_class($this) . "::getOpcaSession", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {

					$line = new AgefoddOpcaLine();

					$line->opca_rowid = $obj->rowid;
					$line->fk_session_trainee = $obj->fk_session_trainee;
					$line->fk_soc_trainee = $obj->fk_soc_trainee;
					$line->fk_session_agefodd = $obj->fk_session_agefodd;
					$line->date_ask_OPCA = $this->db->jdate($obj->date_ask_opca);
					$line->is_OPCA = $obj->is_opca;
					$line->fk_soc_OPCA = $obj->fk_soc_opca;
					$line->fk_socpeople_OPCA = $obj->fk_socpeople_opca;
					$line->num_OPCA_soc = $obj->num_opca_soc;
					$line->num_OPCA_file = $obj->num_opca_file;
					$line->fk_user_author = $obj->fk_user_author;
					$line->datec = $this->db->jdate($obj->datec);
					$line->fk_user_mod = $obj->fk_user_mod;
					$line->tms = $this->db->jdate($obj->tms);

					$line->soc_OPCA_name = $this->getValueFrom('societe', $line->fk_soc_OPCA, 'nom');
					$line->contact_name_OPCA = $obj->concact_opca_name . ' ' . $obj->concact_opca_firstname;

					$this->lines[] = $line;
				}
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::getOpcaSession " . $this->error, LOG_ERR);
			return - 1;
		}
	}
}
class AgefoddOpcaLine {
	public $id;
	public $opca_rowid;
	public $fk_session_trainee;
	public $fk_soc_trainee;
	public $fk_session_agefodd;
	public $date_ask_OPCA = '';
	public $is_OPCA;
	public $fk_soc_OPCA;
	public $fk_socpeople_OPCA;
	public $num_OPCA_soc;
	public $num_OPCA_file;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $tms = '';
	public $soc_OPCA_name;
	public $contact_name_OPCA;
}
