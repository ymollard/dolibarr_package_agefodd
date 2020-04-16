<?php
/*
 * Copyright (C) 2007-2012 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2013-2016 Florian Henry <florian.henry@open-concept.pro>
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
 * \file dev/skeletons/agefoddtrainingadmlevel.class.php
 * \ingroup mymodule othermodule1 othermodule2
 * \brief This file is an example for a CRUD class file (Create/Read/Update/Delete)
 * Initialy built by build_class_from_table on 2013-07-03 15:18
 */

// Put here all includes required by your class file
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 * Administrative task related to a training object.
 */
class Agefodd_training_admlevel extends CommonObject {
	public $error; // !< To return error code (or message)
	public $errors = array (); // !< To return several error codes (or messages)
	public $element = 'agefodd_training_admlevel'; // !< Id that identify managed objects
	public $table_element = 'agefodd_training_admlevel'; // !< Name of table without prefix where object is stored
	public $id;
	public $fk_training;
	public $level_rank;
	public $fk_parent_level;
	public $indice;
	public $intitule;
	public $delais_alerte;
	public $delais_alerte_end;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $tms = '';
	public $fk_agefodd_training_admlevel;
	public $lines = array ();
	public $trigger_name;

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
	 * @param User $user that creates
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->fk_training))
			$this->fk_training = trim($this->fk_training);
		if (isset($this->level_rank))
			$this->level_rank = trim($this->level_rank);
		if (isset($this->fk_parent_level))
			$this->fk_parent_level = trim($this->fk_parent_level);
		if (isset($this->indice))
			$this->indice = trim($this->indice);
		if (isset($this->intitule))
			$this->intitule = trim($this->intitule);
		if (isset($this->delais_alerte))
			$this->delais_alerte = trim($this->delais_alerte);
		if (isset($this->delais_alerte_end))
			$this->delais_alerte_end = trim($this->delais_alerte_end);
		if (isset($this->trigger_name))
			$this->trigger_name = trim($this->trigger_name);

			// Check parameters
			// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_training_admlevel(";

		$sql .= "fk_agefodd_training_admlevel,";
		$sql .= "fk_training,";
		$sql .= "level_rank,";
		$sql .= "fk_parent_level,";
		$sql .= "indice,";
		$sql .= "intitule,";
		$sql .= "delais_alerte,";
		$sql .= "delais_alerte_end,";
		$sql .= "fk_user_author,";
		$sql .= "datec,";
		$sql .= "fk_user_mod,";
		$sql .= "trigger_name";

		$sql .= ") VALUES (";

		$sql .= " " . (empty($this->fk_agefodd_training_admlevel) ? '0' : "'" . $this->fk_agefodd_training_admlevel . "'") . ",";
		$sql .= " " . (! isset($this->fk_training) ? 'NULL' : "'" . $this->fk_training . "'") . ",";
		$sql .= " " . (! isset($this->level_rank) ? 'NULL' : "'" . $this->level_rank . "'") . ",";
		$sql .= " " . (! isset($this->fk_parent_level) ? 'NULL' : "'" . $this->fk_parent_level . "'") . ",";
		$sql .= " " . (empty($this->indice) ? '0' : "'" . $this->indice . "'") . ",";
		$sql .= " " . (! isset($this->intitule) ? 'NULL' : "'" . $this->db->escape($this->intitule) . "'") . ",";
		$sql .= " " . (empty($this->delais_alerte) ? '0' : $this->delais_alerte) . ",";
		$sql .= " " . (empty($this->delais_alerte_end) ? '0' : $this->delais_alerte_end) . ",";
		$sql .= " " . $user->id . ",";
		$sql .= " '" . $this->db->idate(dol_now()) . "',";
		$sql .= " " . $user->id . ",";
		$sql .= " " . (! isset($this->trigger_name) ? 'NULL' : "'" . $this->db->escape($this->trigger_name) . "'");
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_training_admlevel");

			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
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
	 * Load object in memory from the database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id) {
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.fk_training,";
		$sql .= " t.level_rank,";
		$sql .= " t.fk_parent_level,";
		$sql .= " t.indice,";
		$sql .= " t.intitule,";
		$sql .= " t.delais_alerte,";
		$sql .= " t.delais_alerte_end,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms,";
		$sql .= " t.fk_agefodd_training_admlevel,";
		$sql .= " t.trigger_name";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_training_admlevel as t";
		$sql .= " WHERE t.rowid = " . $id;

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->fk_training = $obj->fk_training;
				$this->level_rank = $obj->level_rank;
				$this->fk_parent_level = $obj->fk_parent_level;
				$this->indice = $obj->indice;
				$this->intitule = $obj->intitule;
				$this->delais_alerte = $obj->delais_alerte;
				$this->delais_alerte_end = $obj->delais_alerte_end;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_agefodd_training_admlevel = $obj->fk_agefodd_training_admlevel;
				$this->trigger_name = $obj->trigger_name;
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
	 * @param int $training_id object
	 * @return int int <0 if KO, >0 if OK
	 */
	public function fetch_all($training_id) {
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_training,";
		$sql .= " t.level_rank,";
		$sql .= " t.fk_parent_level,";
		$sql .= " t.indice,";
		$sql .= " t.intitule,";
		$sql .= " t.delais_alerte,";
		$sql .= " t.delais_alerte_end,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms,";
		$sql .= " t.trigger_name,";
		$sql .= " t.fk_agefodd_training_admlevel";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_training_admlevel as t";
		$sql .= " WHERE t.fk_training=" . $training_id;
		$sql .= " ORDER BY t.indice";

		dol_syslog(get_class($this) . "::fetch_all", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new AgfTrainingAdmlvlLine();

				$line->rowid = $obj->rowid;
				$line->fk_training = $obj->fk_training;
				$line->level_rank = $obj->level_rank;
				$line->fk_parent_level = $obj->fk_parent_level;
				$line->indice = $obj->indice;
				$line->intitule = $obj->intitule;
				$line->alerte = $obj->delais_alerte;
				$line->alerte_end = $obj->delais_alerte_end;
				$line->fk_agefodd_training_admlevel = $obj->fk_agefodd_training_admlevel;
				$line->trigger_name = $obj->trigger_name;

				$this->lines[$i] = $line;
				$i ++;
			}
			$this->db->free($resql);
			return $num;
			// return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param User $user that modifies
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->fk_training))
			$this->fk_training = trim($this->fk_training);
		if (isset($this->level_rank))
			$this->level_rank = trim($this->level_rank);
		if (isset($this->fk_parent_level))
			$this->fk_parent_level = trim($this->fk_parent_level);
		if (isset($this->indice))
			$this->indice = trim($this->indice);
		if (isset($this->intitule))
			$this->intitule = trim($this->intitule);
		if (isset($this->delais_alerte))
			$this->delais_alerte = trim($this->delais_alerte);
		if (isset($this->delais_alerte_end))
			$this->delais_alerte_end = trim($this->delais_alerte_end);
		if (isset($this->trigger_name))
			$this->trigger_name = trim($this->trigger_name);

			// Check parameters
			// Put here code to add a control on parameters values

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_training_admlevel SET";

		$sql .= " fk_training=" . (isset($this->fk_training) ? $this->fk_training : "null") . ",";
		$sql .= " fk_agefodd_training_admlevel=" . (isset($this->fk_agefodd_training_admlevel) ? $this->fk_agefodd_training_admlevel : "null") . ",";
		$sql .= " level_rank=" . (isset($this->level_rank) ? $this->level_rank : "null") . ",";
		$sql .= " fk_parent_level=" . (isset($this->fk_parent_level) ? $this->fk_parent_level : "null") . ",";
		$sql .= " indice=" . (isset($this->indice) ? $this->indice : "null") . ",";
		$sql .= " intitule=" . (isset($this->intitule) ? "'" . $this->db->escape($this->intitule) . "'" : "null") . ",";
		$sql .= " trigger_name=" . (isset($this->trigger_name) ? "'" . $this->db->escape($this->trigger_name) . "'" : "null") . ",";
		$sql .= " delais_alerte=" . (isset($this->delais_alerte) ? $this->delais_alerte : "null") . ",";
		$sql .= " delais_alerte_end=" . (isset($this->delais_alerte_end) ? $this->delais_alerte_end : "null") . ",";
		$sql .= " fk_user_mod=" . $user->id;

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
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
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
	 * Delete object in database including all its descendants
	 *
	 * @param User $user that deletes
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0) {
		return $this->delete_with_descendants();
	}

	/**
	 * Deletes all admin tasks with the same fk_training as the current object’s
	 *
	 * @param User $user that deletes
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete_training_task($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		$this->db->begin();

		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}

		if (! $error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_training_admlevel";
			$sql .= " WHERE fk_training=" . $this->fk_training;

			dol_syslog(get_class($this) . "::delete_training_task");
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::delete_training_task " . $errmsg, LOG_ERR);
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

		$this->fk_training = '';
		$this->level_rank = '';
		$this->fk_parent_level = '';
		$this->indice = '';
		$this->intitule = '';
		$this->delais_alerte = '';
		$this->fk_user_author = '';
		$this->datec = '';
		$this->fk_user_mod = '';
		$this->tms = '';
	}

	/**
	 * shift indice object into database
	 *
	 * @param User $user that modify
	 * @param string $type for -1 more for +1
	 * @param $notrigger int 0=launch triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function shift_indice($user, $type = '', $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (isset($this->indice))
			$this->indice = trim($this->indice);

		$this->db->begin();

		if ($type == 'less') {
			if ($this->level_rank != '0') {
				$this->indice = intval(intval($this->indice) - 1);
				// Update request
				$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_training_admlevel SET";

				$sql .= " indice=" . (isset($this->indice) ? intval(intval($this->indice)) + 1 : "null") . ",";
				$sql .= " fk_user_author=" . $user->id . ",";
				$sql .= " fk_user_mod=" . $user->id;

				$sql .= " WHERE indice=" . $this->indice;
				$sql .= " AND fk_training=" . $this->fk_training;

				dol_syslog(get_class($this) . ":shift_indice:less rank no 0", LOG_DEBUG);
				$resql1 = $this->db->query($sql);
				if (! $resql1) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}

				// Update request
				$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_training_admlevel SET";

				$sql .= " indice=" . (isset($this->indice) ? $this->indice : "null") . ",";
				$sql .= " fk_user_mod=" . $user->id;

				$sql .= " WHERE rowid=" . $this->id;
				$sql .= " AND fk_training=" . $this->fk_training;

				dol_syslog(get_class($this) . ":shift_indice:update", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			} else {
				// Update request
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_training_admlevel SET';

				$sql .= ' indice=indice+10000,';
				$sql .= ' fk_user_mod=' . $user->id;
				$sql .= ' WHERE indice>=' . $this->indice . ' AND indice<' . intval(intval($this->indice) + 100);
				$sql .= " AND fk_training=" . $this->fk_training;

				dol_syslog(get_class($this) . ':shift_indice:less rank is 0 ', LOG_DEBUG);
				$resql1 = $this->db->query($sql);
				if (! $resql1) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}

				// Update request
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_training_admlevel SET';

				$sql .= ' indice=indice+100,';
				$sql .= ' fk_user_mod=' . $user->id;
				$sql .= ' WHERE indice>=' . intval(intval($this->indice) - 100) . ' AND indice<' . $this->indice;
				$sql .= " AND fk_training=" . $this->fk_training;

				dol_syslog(get_class($this) . ':shift_indice:less rank is 0 ', LOG_DEBUG);
				$resql1 = $this->db->query($sql);
				if (! $resql1) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}

				// Update request
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_training_admlevel SET';

				$sql .= ' indice=indice-10100,';
				$sql .= ' fk_user_mod=' . $user->id;
				$sql .= ' WHERE indice>=' . intval(intval($this->indice) + 10000) . ' AND indice<' . intval(intval($this->indice) + 10100);

				dol_syslog(get_class($this) . ':shift_indice:less rank is 0 ', LOG_DEBUG);
				$resql1 = $this->db->query($sql);
				if (! $resql1) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			}
		}

		if ($type == 'more') {
			if ($this->level_rank != 0) {
				$this->indice = intval(intval($this->indice) + 1);
				// Update request
				$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_training_admlevel SET";

				$sql .= " indice=" . (isset($this->indice) ? intval(intval($this->indice) - 1) : "null") . ",";
				$sql .= ' fk_user_mod=' . $user->id;
				$sql .= " WHERE indice=" . $this->indice;

				dol_syslog(get_class($this) . ":shift_indice:more rank no 0", LOG_DEBUG);
				$resql1 = $this->db->query($sql);
				if (! $resql1) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}

				// Update request
				$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_training_admlevel SET";

				$sql .= " indice=" . (isset($this->indice) ? $this->indice : "null") . ",";
				$sql .= ' fk_user_mod=' . $user->id;
				$sql .= " WHERE rowid=" . $this->id;

				dol_syslog(get_class($this) . ":shift_indice:update", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			} else {
				// Update request
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_training_admlevel SET';

				$sql .= ' indice=indice+10000,';
				$sql .= ' fk_user_mod=' . $user->id;
				$sql .= ' WHERE indice>=' . $this->indice . ' AND indice<' . intval(intval($this->indice) + 100);

				dol_syslog(get_class($this) . ':shift_indice:more rank is 0 ', LOG_DEBUG);
				$resql1 = $this->db->query($sql);
				if (! $resql1) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}

				// Update request
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_training_admlevel SET';

				$sql .= ' indice=indice-100,';
				$sql .= ' fk_user_mod=' . $user->id;
				$sql .= ' WHERE indice>=' . intval(intval($this->indice) + 100) . ' AND indice<' . intval(intval($this->indice) + 200);

				dol_syslog(get_class($this) . ':shift_indice:more rank is 0 ', LOG_DEBUG);
				$resql1 = $this->db->query($sql);
				if (! $resql1) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}

				// Update request
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_training_admlevel SET';

				$sql .= ' indice=indice-9900,';
				$sql .= ' fk_user_mod=' . $user->id;
				$sql .= ' WHERE indice>=' . intval(intval($this->indice) + 10000) . ' AND indice<' . intval(intval($this->indice) + 10100);

				dol_syslog(get_class($this) . ':shift_indice:more rank is 0 ', LOG_DEBUG);
				$resql1 = $this->db->query($sql);
				if (! $resql1) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			}
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
	 * After a creation set the good parent id for action session
	 *
	 * @param $user User id that modify
	 * @param $training_id int to update
	 * @return int <0 if KO, >0 if OK
	 */
	public function setParentActionId($user, $training_id) {
		$error = 0;

		// Update request
		if ($this->db->type == 'pgsql') {
			$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_training_admlevel as upd';
			$sql .= ' SET fk_parent_level=ori.rowid,';
			$sql .= ' fk_user_mod=' . $user->id;
			$sql .= ' FROM  ' . MAIN_DB_PREFIX . 'agefodd_training_admlevel as ori';
			$sql .= ' WHERE upd.fk_parent_level=ori.fk_agefodd_training_admlevel AND upd.level_rank<>0 AND upd.fk_training=ori.fk_training';
			$sql .= ' AND upd.fk_training=' . $training_id;
		} else {
			$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_training_admlevel as ori, ' . MAIN_DB_PREFIX . 'agefodd_training_admlevel as upd ';
			$sql .= ' SET upd.fk_parent_level=ori.rowid,';
			$sql .= ' upd.fk_user_mod=' . $user->id;
			$sql .= ' WHERE upd.fk_parent_level=ori.fk_agefodd_training_admlevel AND upd.level_rank<>0 AND upd.fk_training=ori.fk_training';
			$sql .= ' AND upd.fk_training=' . $training_id;
		}

		// print $sql;
		// exit;
		$this->db->begin();

		dol_syslog(get_class($this) . "::setParentActionId", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::setParentActionId " . $errmsg, LOG_ERR);
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
	 * Load object in memory from database
	 *
	 * @param int $training_id object
	 * @param int $fk_parent_level id of parent
	 * @return array|int array of object, or <0 if KO
	 */
	public function fetch_all_children_nested($training_id, $fk_parent_level = 0) {

		$TNested = array();

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_training,";
		$sql .= " t.level_rank,";
		$sql .= " t.fk_parent_level,";
		$sql .= " t.indice,";
		$sql .= " t.intitule,";
		$sql .= " t.delais_alerte,";
		$sql .= " t.delais_alerte_end,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms,";
		$sql .= " t.trigger_name,";
		$sql .= " t.fk_agefodd_training_admlevel";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_training_admlevel as t";
		$sql .= " WHERE t.fk_training=" . intval($training_id);
        $sql .= " AND t.fk_parent_level=" . intval($fk_parent_level);

		$sql .= " ORDER BY t.indice ASC";

		dol_syslog(get_class($this) . "::fetch_all", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new AgfTrainingAdmlvlLine();

				$line->rowid = $obj->rowid;
				$line->fk_training = $obj->fk_training;
				$line->level_rank = $obj->level_rank;
				$line->fk_parent_level = $obj->fk_parent_level;
				$line->indice = $obj->indice;
				$line->intitule = $obj->intitule;
				$line->alerte = $obj->delais_alerte;
				$line->alerte_end = $obj->delais_alerte_end;
				$line->fk_agefodd_training_admlevel = $obj->fk_agefodd_training_admlevel;
				$line->trigger_name = $obj->trigger_name;

				$TNested[$i] = array(
					'object' => $line,
					'children' => $this->fetch_all_children_nested($training_id, $line->rowid)
				);
				$i ++;
			}
			$this->db->free($resql);

			return $TNested;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Recursively deletes the specified admin task and all its descendants.
	 * This may be done more cleanly using ON DELETE CASCADE
	 *
	 * @param int $id ID of the admin task; default to current object's ID
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete_with_descendants($id = null)
	{
		if ($id === null) $id = $this->id;
		$id = intval($id);

		$error = 0;

		if (empty($id)) {
			// setEventMessages($langs->trans('EmptyID'), array(), 'errors');
			return -1;
		}

		$this->db->begin();

		$sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . $this->table_element . ' WHERE fk_parent_level = ' . $id;
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		} else {
			$num_rows = $this->db->num_rows($resql);
			for ($i = 0; $i < $num_rows; $i++) {
				$obj = $this->db->fetch_object($resql);
				if (!$obj) break;
				$this->delete_with_descendants($obj->rowid);
			}
		}

		if (!$error) {
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $this->table_element . ' WHERE rowid = ' . $id;

			dol_syslog(get_class($this) . "::delete");
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
		$this->db->commit();
		return 1;
	}
}

/**
 * line Class
 */
class AgfTrainingAdmlvlLine {
	public $rowid;
	public $fk_training;
	public $level_rank;
	public $fk_parent_level;
	public $indice;
	public $intitule;
	public $alerte;
	public $alerte_end;
	public $fk_agefodd_training_admlevel;
	public $trigger_name;
	public function __construct() {
		return 1;
	}
}
