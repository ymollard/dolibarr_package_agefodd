<?php
/* Copyright (C) 2012-2014		Florian Henry			<florian.henry@open-concept.pro>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
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
 * \file agefodd/class/agefodd_formateur.class.php
 * \ingroup agefodd
 * \brief Manage trainer
 */
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 * Trainner Class
 */
class Agefodd_teacher extends CommonObject {
	var $db;
	var $error;
	var $errors = array ();
	var $element = 'agefodd_formateur';
	var $table_element = 'agefodd_formateur';
	var $id;
	var $type_trainer_def = array ();
	protected $ismultientitymanaged = 1; // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	var $entity;
	var $fk_socpeople;
	var $fk_user;
	var $type_trainer;
	var $archive;
	var $fk_user_author;
	var $datec = '';
	var $fk_user_mod;
	var $lines = array ();
	
	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	function __construct($DB) {
		$this->db = $DB;
		$this->type_trainer_def = array (
				0 => 'user',
				1 => 'socpeople' 
		);
		return 1;
	}
	
	/**
	 * Create object into database
	 *
	 * @param User $user that create
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	function create($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		if (isset($this->entity))
			$this->entity = trim($this->entity);
		if (isset($this->fk_socpeople))
			$this->fk_socpeople = trim($this->fk_socpeople);
		if (isset($this->fk_user))
			$this->fk_user = trim($this->fk_user);
		if (isset($this->type_trainer))
			$this->type_trainer = trim($this->type_trainer);
		if (isset($this->archive))
			$this->archive = trim($this->archive);
		if (isset($this->fk_user_author))
			$this->fk_user_author = trim($this->fk_user_author);
		if (isset($this->fk_user_mod))
			$this->fk_user_mod = trim($this->fk_user_mod);
			
			// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_formateur(";
		$sql .= "fk_socpeople,fk_user, type_trainer, fk_user_author, fk_user_mod, entity, datec";
		$sql .= ") VALUES (";
		// trainer is user
		if ($this->type_trainer == $this->type_trainer_def [0]) {
			$sql .= 'NULL, ';
			$sql .= " " . $this->fk_user . ", ";
			$sql .= "'" . $this->type_trainer_def [0] . "', ";
		} 		// trainer is Dolibarr contact
		elseif ($this->type_trainer == $this->type_trainer_def [1]) {
			$sql .= " " . $this->spid . ", ";
			$sql .= 'NULL, ';
			$sql .= "'" . $this->type_trainer_def [1] . "', ";
		}
		$sql .= " " . $user->id . ",";
		$sql .= " " . $user->id . ",";
		$sql .= " " . $conf->entity . ",";
		$sql .= $this->db->idate(dol_now());
		$sql .= ")";
		
		$this->db->begin();
		
		dol_syslog(get_class($this) . "::create ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror();
		}
		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_formateur");
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
	 * Load object in memory from database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch($id, $arch = 0) {
		global $langs;
		
		$sql = "SELECT";
		$sql .= " f.rowid, f.fk_socpeople, f.fk_user, f.type_trainer,  f.archive,";
		$sql .= " s.rowid as spid , s.lastname as sp_name, s.firstname as sp_firstname, s.civility as sp_civilite, ";
		$sql .= " s.phone as sp_phone, s.email as sp_email, s.phone_mobile as sp_phone_mobile, ";
		$sql .= " u.lastname as u_name, u.firstname as u_firstname, u.civility as u_civilite, ";
		$sql .= " u.office_phone as u_phone, u.email as u_email, u.user_mobile as u_phone_mobile";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formateur as f";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as s ON f.fk_socpeople = s.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON f.fk_user = u.rowid";
		$sql .= " WHERE f.rowid = " . $id;
		$sql .= " AND f.entity IN (" . getEntity('agsession') . ")";
		
		dol_syslog(get_class($this) . "::fetch ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->ref = $obj->rowid; // Use for show_next_prev
				$this->archive = $obj->archive;
				$this->type_trainer = $obj->type_trainer;
				
				// trainer is user
				if ($this->type_trainer == $this->type_trainer_def [0]) {
					$this->fk_user = $obj->fk_user;
					$this->name = $obj->u_name;
					$this->firstname = $obj->u_firstname;
					$this->civilite = $obj->u_civilite;
					$this->phone = $obj->u_phone;
					$this->email = $obj->u_email;
					$this->phone_mobile = $obj->u_phone_mobile;
				} 				// trainer is Dolibarr contact
				elseif ($this->type_trainer == $this->type_trainer_def [1]) {
					$this->spid = $obj->spid;
					$this->fk_socpeople = $obj->fk_socpeople;
					$this->name = $obj->sp_name;
					$this->firstname = $obj->sp_firstname;
					$this->civilite = $obj->sp_civilite;
					$this->phone = $obj->sp_phone;
					$this->email = $obj->sp_email;
					$this->phone_mobile = $obj->sp_phone_mobile;
				}
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
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit offset limit
	 * @param int $offset offset limit
	 * @param int $arch archive
	 * @param array $filter array of filter
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_all($sortorder, $sortfield, $limit, $offset, $arch = 0, $filter = array()) {
		global $langs;
		
		$sql = "SELECT";
		$sql .= " f.rowid, f.fk_socpeople, f.fk_user, f.type_trainer,  f.archive,";
		$sql .= " s.rowid as spid , s.lastname as sp_name, s.firstname as sp_firstname, s.civility as sp_civilite, ";
		$sql .= " s.phone as sp_phone, s.email as sp_email, s.phone_mobile as sp_phone_mobile, ";
		$sql .= " u.lastname as u_name, u.firstname as u_firstname, u.civility as u_civilite, ";
		$sql .= " u.office_phone as u_phone, u.email as u_email, u.user_mobile as u_phone_mobile";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formateur as f";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as s ON f.fk_socpeople = s.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON f.fk_user = u.rowid";
		$sql .= " WHERE f.entity IN (" . getEntity('agsession') . ")";
		if ($arch == 0 || $arch == 1)
			$sql .= " AND f.archive = " . $arch;
			
			// Manage filter
		if (count($filter) > 0) {
			foreach ( $filter as $key => $value ) {
				if ($key == 'f.rowid') {
					$sql .= ' AND ' . $key . '=' . $value;
				} elseif ($key == 'lastname') {
					$sql .= ' AND ((s.lastname LIKE \'%' . $this->db->escape($value) . '%\') ';
					$sql .= ' OR (u.lastname LIKE \'%' . $this->db->escape($value) . '%\'))';
				} elseif ($key == 'firstname') {
					$sql .= ' AND ((s.firstname LIKE \'%' . $this->db->escape($value) . '%\') ';
					$sql .= ' OR (u.firstname LIKE \'%' . $this->db->escape($value) . '%\'))';
				} elseif ($key == 'mail') {
					$sql .= ' AND ((s.email LIKE \'%' . $this->db->escape($value) . '%\') ';
					$sql .= ' OR (u.email LIKE \'%' . $this->db->escape($value) . '%\'))';
				} else {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		
		$sql .= " ORDER BY " . $sortfield . " " . $sortorder . " ";
		if (! empty($limit)) {
			$sql .= $this->db->plimit($limit + 1, $offset);
		}
		
		dol_syslog(get_class($this) . "::fetch_all ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->line = array ();
			$num = $this->db->num_rows($resql);
			$i = 0;
			
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);
					
					$line = new AgfTrainerLine();
					
					$line->id = $obj->rowid;
					$line->type_trainer = $obj->type_trainer;
					$line->archive = $obj->archive;
					// trainer is user
					if ($line->type_trainer == $this->type_trainer_def [0]) {
						$line->fk_user = $obj->fk_user;
						$line->name = $obj->u_name;
						$line->firstname = $obj->u_firstname;
						$line->civilite = $obj->u_civilite;
						$line->phone = $obj->u_phone;
						$line->email = $obj->u_email;
						$line->phone_mobile = $obj->u_phone_mobile;
						$line->fk_socpeople = $obj->fk_socpeople;
					} 					// trainer is Dolibarr contact
					elseif ($line->type_trainer == $this->type_trainer_def [1]) {
						$line->spid = $obj->spid;
						$line->name = $obj->sp_name;
						$line->firstname = $obj->sp_firstname;
						$line->civilite = $obj->sp_civilite;
						$line->phone = $obj->sp_phone;
						$line->email = $obj->sp_email;
						$line->phone_mobile = $obj->sp_phone_mobile;
						$line->fk_socpeople = $obj->fk_socpeople;
					}
					
					$this->lines [$i] = $line;
					$i ++;
				}
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
	 * Give information on the object
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	function info($id) {
		global $langs;
		
		$sql = "SELECT";
		$sql .= " f.rowid, f.datec, f.tms, f.fk_user_mod, f.fk_user_author";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formateur as f";
		$sql .= " WHERE f.rowid = " . $id;
		
		dol_syslog(get_class($this) . "::fetch ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
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
	 * Update object into database
	 *
	 * @param User $user that modify
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function update($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		
		// Check parameters
		// Put here code to add control on parameters values
		
		// Update request
		if (! isset($this->archive))
			$this->archive = 0;
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_formateur SET";
		$sql .= " fk_user_mod=" . $user->id . " ,";
		$sql .= " archive=" . $this->archive . " ";
		$sql .= " WHERE rowid = " . $this->id;
		
		$this->db->begin();
		
		dol_syslog(get_class($this) . "::update ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror();
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
	 * Delete object in database
	 *
	 * @param User $user that delete
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function remove($id) {
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_formateur";
		$sql .= " WHERE rowid = " . $id;
		
		dol_syslog(get_class($this) . "::remove ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		
		if ($resql) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return - 1;
		}
	}
}
class AgfTrainerLine {
	var $id;
	var $type_trainer;
	var $archive;
	var $fk_user;
	var $name;
	var $firstname;
	var $civilite;
	var $phone;
	var $email;
	var $phone_mobile;
	var $fk_socpeople;
	function __construct() {
		return 1;
	}
}