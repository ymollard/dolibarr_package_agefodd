<?php
/*
 * Copyright (C) 2007-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file agefodd/class/agefodd_sessadm.class.php
 * \ingroup agefodd
 * \brief Manage Administrative Task object
 */
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 * Administrative task level Class
 */
class Agefodd_sessadm extends CommonObject {
	public $error;
	public $errors = array ();
	public $element = 'agefodd';
	public $table_element = 'agefodd_session_adminsitu';
	public $id;
	public $fk_agefodd_session_admlevel;
	public $fk_agefodd_session;
	public $level_rank;
	public $fk_parent_level;
	public $indice;
	public $intitule;
	public $delais_alerte;
	public $delais_alerte_end;
	public $dated;
	public $datef;
	public $datea;
	public $notes;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $tms = '';
	public $archive;
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
	 * @param User $user that create
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		$this->fk_agefodd_session_admlevel = trim($this->fk_agefodd_session_admlevel);
		$this->fk_agefodd_session = trim($this->fk_agefodd_session);
		$this->intitule = $this->db->escape(trim($this->intitule));
		$this->indice = trim($this->indice);
		$this->notes = $this->db->escape(trim($this->notes));
		$this->fk_user_author = trim($this->fk_user_author);
		$this->trigger_name = trim($this->trigger_name);
		// Check parameters
		// Put here code to add control on parameters value

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_session_adminsitu (";
		$sql .= "fk_agefodd_session_admlevel, fk_agefodd_session, intitule, delais_alerte";
		$sql .= ", delais_alerte_end";
		$sql .= ",indice, level_rank, fk_parent_level, dated, datef, datea, notes,archive,fk_user_author,fk_user_mod, datec";
		$sql .= ",trigger_name";
		$sql .= ") VALUES (";
		$sql .= $this->fk_agefodd_session_admlevel . ", ";
		$sql .= $this->fk_agefodd_session . ", ";
		$sql .= "'" . $this->intitule . "', ";
		$sql .= $this->delais_alerte . ", ";
		$sql .= intval($this->delais_alerte_end) . ", ";
		$sql .= $this->indice . ", ";
		$sql .= $this->level_rank . ", ";
		$sql .= $this->fk_parent_level . ", ";
		$sql .= " " . (! isset($this->dated) || dol_strlen($this->dated) == 0 ? 'NULL' : "'" . $this->db->idate($this->dated) . "'") . ",";
		$sql .= " " . (! isset($this->datef) || dol_strlen($this->datef) == 0 ? 'NULL' : "'" . $this->db->idate($this->datef) . "'") . ",";
		$sql .= " " . (! isset($this->datea) || dol_strlen($this->datea) == 0 ? 'NULL' : "'" . $this->db->idate($this->datea) . "'") . ",";
		$sql .= "'" . $this->db->escape($this->notes) . "', ";
		$sql .= $this->archive . ',';
		$sql .= " " . $user->id . ", ";
		$sql .= " " . $user->id . ", ";
		$sql .= "'" . $this->db->idate(dol_now()) . "', ";
		$sql .= " " . (! isset($this->trigger_name) ? 'NULL' : "'" . $this->trigger_name . "'");
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}
		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_session_adminsitu");
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.

				// // Call triggers
				// include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
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
		// $this->delais_alerte = trim($this->delais_alerte);
		$this->dated = trim($this->dated);
		$this->datef = trim($this->datef);
		$this->datea = trim($this->datea);
		$this->notes = $this->db->escape(trim($this->notes));
		$this->trigger_name = $this->db->escape(trim($this->trigger_name));
		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		if (! isset($this->archive))
			$this->archive = 0;
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_adminsitu SET";
		$sql .= " delais_alerte=" . $this->delais_alerte . ",";
		$sql .= " delais_alerte_end=" . $this->delais_alerte_end . ",";
		$sql .= " dated=" . (! isset($this->dated) || dol_strlen($this->dated) == 0 ? 'NULL' : "'" . $this->db->idate($this->dated) . "'") . ",";
		$sql .= " datef=" . (! isset($this->datef) || dol_strlen($this->datef) == 0 ? 'NULL' : "'" . $this->db->idate($this->datef) . "'") . ",";
		$sql .= " datea=" . (! isset($this->datea) || dol_strlen($this->datea) == 0 ? 'NULL' : "'" . $this->db->idate($this->datea) . "'") . ",";
		$sql .= " fk_user_mod=" . $user->id . ",";
		$sql .= " notes='" . $this->notes . "',";
		$sql .= " archive=" . $this->archive . ",";
		$sql .= " level_rank=" . $this->level_rank . ",";
		$sql .= " trigger_name=" . (isset($this->trigger_name) ? "'" . $this->db->escape($this->trigger_name) . "'" : "null") . ",";
		$sql .= " fk_parent_level=" . $this->fk_parent_level;
		$sql .= " WHERE rowid = " . $this->id;

		// print $sql;
		// exit;
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
	 * Load object in memory from database
	 *
	 * @param int $id action (in table agefodd_session_adminsitu)
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id) {
		$sql = "SELECT";
		$sql .= " s.rowid, s.fk_agefodd_session_admlevel, s.fk_agefodd_session, s.intitule,";
		$sql .= " s.level_rank, s.fk_parent_level, s.indice, s.dated, s.datea, s.datef, s.notes, s.delais_alerte, s.archive";
		$sql .= ',s.fk_user_mod,s.trigger_name';
		$sql .= ',s.delais_alerte_end';
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as s";
		$sql .= " WHERE s.rowid = '" . $id . "'";

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->level = $obj->fk_agefodd_session_admlevel;
				$this->sessid = $obj->fk_agefodd_session;
				$this->intitule = $obj->intitule;
				$this->indice = $obj->indice;
				$this->level_rank = $obj->level_rank;
				$this->fk_parent_level = $obj->fk_parent_level;
				$this->delais_alerte = $obj->delais_alerte;
				$this->delais_alerte_end = $obj->delais_alerte_end;
				$this->dated = $this->db->jdate($obj->dated);
				$this->datef = $this->db->jdate($obj->datef);
				$this->datea = $this->db->jdate($obj->datea);
				$this->notes = $obj->notes;
				$this->archive = $obj->archive;
				$this->trigger_name = $obj->trigger_name;
				$this->fk_user_mod = $obj->fk_user_mod;
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
	 * Load action into memory per session
	 *
	 * @param int $sess_id Id
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_all($sess_id) {
		$sql = "SELECT";
		$sql .= " s.rowid, s.fk_agefodd_session_admlevel, s.fk_agefodd_session, s.intitule, s.delais_alerte_end,";
		$sql .= " s.level_rank, s.fk_parent_level, s.indice, s.dated, s.datea, s.datef, s.notes, s.delais_alerte, s.archive";
		$sql .= ',s.fk_user_mod,s.trigger_name';
		$sql .= ', s.delais_alerte_end';
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as s";
		$sql .= " WHERE s.fk_agefodd_session = " . $sess_id;
		$sql .= " ORDER BY s.indice";

		dol_syslog(get_class($this) . "::fetch_all", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new AgfSessAdm();

				$line->id = $obj->rowid;
				$line->level = $obj->fk_agefodd_session_admlevel;
				$line->sessid = $obj->fk_agefodd_session;
				$line->intitule = $obj->intitule;
				$line->indice = $obj->indice;
				$line->level_rank = $obj->level_rank;
				$line->fk_parent_level = $obj->fk_parent_level;
				$line->delais_alerte = $obj->delais_alerte;
				$line->delais_alerte_end = $obj->delais_alerte_end;
				$line->dated = $this->db->jdate($obj->dated);
				$line->datef = $this->db->jdate($obj->datef);
				$line->datea = $this->db->jdate($obj->datea);
				$line->notes = $obj->notes;
				$line->archive = $obj->archive;
				$line->trigger_name = $obj->trigger_name;
				$line->fk_user_mod = $obj->fk_user_mod;

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
	 * Give information on the object
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function info($id) {
		$sql = "SELECT";
		$sql .= " s.rowid, s.datec, s.tms, s.fk_user_author, s.fk_user_mod";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " WHERE s.rowid = " . $id;

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
				$this->user_creation = $obj->fk_user_author;
				$this->user_modification = $obj->fk_user_mod;
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
	 * Delete object in database
	 *
	 * @param int $id delete
	 * @return int if KO, >0 if OK
	 */
	public function remove($id) {
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu";
		$sql .= " WHERE rowid = " . $id;

		dol_syslog(get_class($this) . "::remove", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return - 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param int $id delete
	 * @return int if KO, >0 if OK
	 */
	public function remove_all($id) {
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu";
		$sql .= " WHERE fk_agefodd_session = " . $id;

		dol_syslog(get_class($this) . "::remove", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return - 1;
		}
	}

	/**
	 * Load Date of session in memory
	 *
	 * @param int $sessid agefodd_session id
	 * @return int if KO, >0 if OK
	 */
	public function get_session_dated($sessid) {
		$sql = "SELECT";
		$sql .= " s.dated, s.datef";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " WHERE s.rowid = " . $sessid;

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->dated = $this->db->jdate($obj->dated);
				$this->datef = $this->db->jdate($obj->datef);
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
	 * Load Date of session in memory
	 *
	 * @param int $id delete
	 * @return int if KO, >0 if OK
	 */
	public function has_child($id) {
		$sql = "SELECT";
		$sql .= " s.rowid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as s";
		$sql .= " WHERE s.fk_parent_level = " . $id;

		dol_syslog(get_class($this) . "::has_child", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$retrun = 1;
			} else {
				$retrun = 0;
			}

			$this->db->free($resql);

			return $retrun;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::has_child " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * After a creation set the good parent id for action session
	 *
	 * @param $user User id that modify
	 * @param $session_id int session to update
	 * @return int <0 if KO, >0 if OK
	 */
	public function setParentActionId($user, $session_id) {
		$error = 0;

		// Update request
		if ($this->db->type == 'pgsql') {
			$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session_adminsitu as upd';
			$sql .= ' SET fk_parent_level=ori.rowid ';
			$sql .= ' FROM  ' . MAIN_DB_PREFIX . 'agefodd_session_adminsitu as ori';
			$sql .= ' WHERE upd.fk_parent_level=ori.fk_agefodd_session_admlevel AND upd.level_rank<>0 AND upd.fk_agefodd_session=ori.fk_agefodd_session';
			$sql .= ' AND upd.fk_agefodd_session=' . $session_id;
		} else {
			$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session_adminsitu as ori, ' . MAIN_DB_PREFIX . 'agefodd_session_adminsitu as upd ';
			$sql .= ' SET upd.fk_parent_level=ori.rowid ';
			$sql .= ' WHERE upd.fk_parent_level=ori.fk_agefodd_session_admlevel AND upd.level_rank<>0 AND upd.fk_agefodd_session=ori.fk_agefodd_session';
			$sql .= ' AND upd.fk_agefodd_session=' . $session_id;
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
	 * Update by trigger name
	 *
	 * @param $user User id that modify
	 * @param $session_id int session to update
	 * @param $trigger_name string name of the trigger to update
	 * @param $status int status to set
	 * @return int <0 if KO, >0 if OK
	 */
	public function updateByTriggerName($user, $session_id, $trigger_name, $status = 1) {
		$error = 0;

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session_adminsitu';
		$sql .= " SET archive=" . $status . ",  fk_user_mod=" . $user->id;
		$sql .= ' WHERE fk_agefodd_session=' . $session_id;
		$sql .= " AND trigger_name LIKE '%" . $trigger_name . "%'";

		// print $sql;
		// exit;
		$this->db->begin();

		dol_syslog(get_class($this) . "::updateByTriggerName", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::updateByTriggerName " . $errmsg, LOG_ERR);
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
	 * setAllStasus
	 *
	 * @param $user User id that modify
	 * @param $session_id int session to update
	 * @param  $status int status to set
	 * @return number
	 */
	public function setAllStatus($user, $session_id, $status) {
		$error = 0;

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session_adminsitu';
		$sql .= " SET archive=" . $status . ",  fk_user_mod=" . $user->id;
		if ($status == 1) {
			$sql .= ",datef='" . $this->db->idate(dol_now()) . "'";
		}

		$sql .= ' WHERE fk_agefodd_session=' . $session_id;

		$this->db->begin();

		dol_syslog(get_class($this) . "::" . __METHOD__ . " sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::setAllStasus " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}
}

/**
 * Session admnistrative line Class
 */
class AgfSessAdm {
	public $id;
	public $level;
	public $sessid;
	public $intitule;
	public $indice;
	public $level_rank;
	public $fk_parent_level;
	public $delais_alerte;
	public $delais_alerte_end;
	public $dated;
	public $datef;
	public $datea;
	public $notes;
	public $archive;
	public $trigger_name;
	public $fk_user_mod;
	public function __construct() {
		return 1;
	}
}
