<?php
/*
 * Copyright (C) 2007-2012 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012-2016 Florian Henry <florian.henry@open-concept.pro>
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
 * \file agefodd/class/agefodd_session_admlevel.class.php
 * \ingroup agefodd
 * \brief Manage Session administrative task object
 */
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 * Administrative task by session Class
 */
class Agefodd_session_admlevel extends CommonObject {
	public $error; // !< To return error code (or message)
	public $errors = array (); // !< To return several error codes (or messages)
	public $element = 'agefodd'; // !< Id that identify managed objects
	public $table_element = 'agefodd_session_admlevel'; // !< Name of table without prefix where object is stored
	public $id;
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
	public $lines = array ();
	public $trigger_name = 'AGEFODD_SESSION_ADMLEVEL';

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
		if (isset($this->trigger_name))
			$this->trigger_name = trim($this->trigger_name);

			// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_session_admlevel(";

		$sql .= "level_rank,";
		$sql .= "fk_parent_level,";
		$sql .= "indice,";
		$sql .= "intitule,";
		$sql .= "delais_alerte,";
		$sql .= "delais_alerte_end,";
		$sql .= "fk_user_author,";
		$sql .= "fk_user_mod,";
		$sql .= "trigger_name,";
		$sql .= "datec";

		$sql .= ") VALUES (";

		$sql .= " " . (! isset($this->level_rank) ? 'NULL' : "'" . $this->level_rank . "'") . ",";
		$sql .= " " . (! isset($this->fk_parent_level) ? 'NULL' : "'" . $this->fk_parent_level . "'") . ",";
		$sql .= " " . (! isset($this->indice) ? 'NULL' : "'" . $this->indice . "'") . ",";
		$sql .= " " . (! isset($this->intitule) ? 'NULL' : "'" . $this->db->escape($this->intitule) . "'") . ",";
		$sql .= " " . (! isset($this->delais_alerte) ? '0' : "'" . $this->delais_alerte . "'") . ",";
		$sql .= " " . (! isset($this->delais_alerte_end) ? 'NULL' : "'" . $this->delais_alerte_end . "'") . ",";
		$sql .= " " . $user->id . ",";
		$sql .= " " . $user->id . ",";
		$sql .= " " . (! isset($this->trigger_name) ? 'NULL' : "'" . $this->trigger_name . "'") . ",";
		$sql .= " '" . $this->db->idate(dol_now()) . "'";

		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_session_admlevel");

			if (! $notrigger) {
				// Call triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers($this->trigger_name . '_CREATE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// End call triggers
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
	 * @param $id int Id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id) {
		$sql = "SELECT";
		$sql .= " t.rowid,";
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
		$sql .= " t.trigger_name";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_admlevel as t";
		$sql .= " WHERE t.rowid = " . $id;

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->level_rank = $obj->level_rank;
				$this->fk_parent_level = $obj->fk_parent_level;
				$this->indice = $obj->indice;
				$this->intitule = $obj->intitule;
				$this->trigger_name = $obj->trigger_name;
				$this->delais_alerte = $obj->delais_alerte;
				$this->delais_alerte_end = $obj->delais_alerte_end;
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
	 * Load object in memory from database
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_all() {
		$sql = "SELECT";
		$sql .= " t.rowid,";
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
		$sql .= " t.trigger_name";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_admlevel as t";
		$sql .= " ORDER BY t.indice";

		dol_syslog(get_class($this) . "::fetch_all", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new AgfSessionAdmlvlLine();

				$line->rowid = $obj->rowid;
				$line->level_rank = $obj->level_rank;
				$line->fk_parent_level = $obj->fk_parent_level;
				$line->indice = $obj->indice;
				$line->intitule = $obj->intitule;
				$line->alerte = $obj->delais_alerte;
				$line->alerte_end = $obj->delais_alerte_end;
				$line->trigger_name = $obj->trigger_name;

				$this->lines[$i] = $line;
				$i ++;
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $fk_parent_level id of parent
	 * @return array|int array of object, <0 if error
	 */
	public function fetch_all_children_nested($fk_parent_level = 0) {

		$TNested = array();

		$sql = "SELECT";
		$sql .= " t.rowid,";
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
		$sql .= " t.trigger_name";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_admlevel as t";
		$sql .= " WHERE t.fk_parent_level=" . intval($fk_parent_level);

		$sql .= " ORDER BY t.indice ASC";

		dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new AgfSessionAdmlvlLine();

				$line->rowid = $obj->rowid;
				$line->level_rank = $obj->level_rank;
				$line->fk_parent_level = $obj->fk_parent_level;
				$line->indice = $obj->indice;
				$line->intitule = $obj->intitule;
				$line->alerte = $obj->delais_alerte;
				$line->alerte_end = $obj->delais_alerte_end;
				$line->trigger_name = $obj->trigger_name;

				$TNested[$i] = array(
					'object' => $line,
					'children' => $this->fetch_all_children_nested($line->rowid)
				);
				$i ++;
			}
			$this->db->free($resql);

			return $TNested;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::".__METHOD__. ' ' . $this->error, LOG_ERR);
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

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_admlevel SET";

		$sql .= " level_rank=" . (isset($this->level_rank) ? $this->level_rank : "null") . ",";
		$sql .= " fk_parent_level=" . (isset($this->fk_parent_level) ? $this->fk_parent_level : "null") . ",";
		$sql .= " indice=" . (isset($this->indice) ? $this->indice : "null") . ",";
		$sql .= " intitule=" . (isset($this->intitule) ? "'" . $this->db->escape($this->intitule) . "'" : "null") . ",";
		$sql .= " trigger_name=" . (isset($this->trigger_name) ? "'" . $this->db->escape($this->trigger_name) . "'" : "null") . ",";
		$sql .= " delais_alerte=" . (isset($this->delais_alerte) ? $this->delais_alerte : "0") . ",";
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
				// Call triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers($this->trigger_name . '_MODIFY',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// End call triggers
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
	 * @param User $user that delete
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0) {
		$this->delete_with_descendants($this->id, $notrigger);
	}

	/**
	 * shift indice object into database
	 *
	 * @param User $user that modify
	 * @param string $type 'less' for -1, 'more' for +1
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
				$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_admlevel SET";

				$sql .= " indice=" . (isset($this->indice) ? intval(intval($this->indice)) + 1 : "null") . ",";
				$sql .= " fk_user_author=" . $user->id . ",";
				$sql .= " fk_user_mod=" . $user->id;

				$sql .= " WHERE indice=" . $this->indice;

				dol_syslog(get_class($this) . ":shift_indice:less rank no 0", LOG_DEBUG);
				$resql1 = $this->db->query($sql);
				if (! $resql1) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}

				// Update request
				$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_admlevel SET";

				$sql .= " indice=" . (isset($this->indice) ? $this->indice : "null") . ",";
				$sql .= " fk_user_mod=" . $user->id;

				$sql .= " WHERE rowid=" . $this->id;

				dol_syslog(get_class($this) . ":shift_indice:update", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			} else {
				// Update request
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session_admlevel SET';

				$sql .= ' indice=indice+10000,';
				$sql .= ' fk_user_mod=' . $user->id;
				$sql .= ' WHERE indice>=' . $this->indice . ' AND indice<' . intval(intval($this->indice) + 100);

				dol_syslog(get_class($this) . ':shift_indice:less rank is 0', LOG_DEBUG);
				$resql1 = $this->db->query($sql);
				if (! $resql1) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}

				// Update request
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session_admlevel SET';

				$sql .= ' indice=indice+100,';
				$sql .= ' fk_user_mod=' . $user->id;
				$sql .= ' WHERE indice>=' . intval(intval($this->indice) - 100) . ' AND indice<' . $this->indice;

				dol_syslog(get_class($this) . ':shift_indice:less rank is 0 ', LOG_DEBUG);
				$resql1 = $this->db->query($sql);
				if (! $resql1) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}

				// Update request
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session_admlevel SET';

				$sql .= ' indice=indice-10100,';
				$sql .= ' fk_user_mod=' . $user->id;
				$sql .= ' WHERE indice>=' . intval(intval($this->indice) + 10000) . ' AND indice<' . intval(intval($this->indice) + 10100);

				dol_syslog(get_class($this) . ':shift_indice:less rank is 0', LOG_DEBUG);
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
				$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_admlevel SET";

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
				$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_admlevel SET";

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
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session_admlevel SET';

				$sql .= ' indice=indice+10000,';
				$sql .= ' fk_user_mod=' . $user->id;
				$sql .= ' WHERE indice>=' . $this->indice . ' AND indice<' . intval(intval($this->indice) + 100);

				dol_syslog(get_class($this) . ':shift_indice:more rank is 0', LOG_DEBUG);
				$resql1 = $this->db->query($sql);
				if (! $resql1) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}

				// Update request
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session_admlevel SET';

				$sql .= ' indice=indice-100,';
				$sql .= ' fk_user_mod=' . $user->id;
				$sql .= ' WHERE indice>=' . intval(intval($this->indice) + 100) . ' AND indice<' . intval(intval($this->indice) + 200);

				dol_syslog(get_class($this) . ':shift_indice:more rank is 0', LOG_DEBUG);
				$resql1 = $this->db->query($sql);
				if (! $resql1) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}

				// Update request
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session_admlevel SET';

				$sql .= ' indice=indice-9900,';
				$sql .= ' fk_user_mod=' . $user->id;
				$sql .= ' WHERE indice>=' . intval(intval($this->indice) + 10000) . ' AND indice<' . intval(intval($this->indice) + 10100);

				dol_syslog(get_class($this) . ':shift_indice:more rank is 0', LOG_DEBUG);
				$resql1 = $this->db->query($sql);
				if (! $resql1) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			}
		}

		if (! $error) {
			if (! $notrigger) {
				// Call triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers($this->trigger_name . '_MODIFY',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// End call triggers
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
	 * Recursively deletes the specified admin task and all its descendants.
	 * This may be done more cleanly using ON DELETE CASCADE
	 *
	 * @param int $id ID of the admin task; default to current object's ID
	 * @param int $notrigger Whether to disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete_with_descendants($id = null, $notrigger = 0)
	{
		global $conf, $langs, $user;

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
			} elseif (!$notrigger) {
				// Call triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers($this->trigger_name . '_DELETE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// End call triggers
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
class AgfSessionAdmlvlLine {
	public $rowid;
	public $level_rank;
	public $fk_parent_level;
	public $indice;
	public $intitule;
	public $alerte;
	public $alerte_end;
	public $trigger_name;
	public function __construct() {
		return 1;
	}
}
