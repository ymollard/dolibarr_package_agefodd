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
	public $error;
	public $errors = array ();
	public $element = 'agefodd_formateur';
	public $table_element = 'agefodd_formateur';
	public $id;
	public $type_trainer_def = array ();
	public $ismultientitymanaged = 1; // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	public $entity;
	public $fk_socpeople;
	public $fk_user;
	public $type_trainer;
	public $archive;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $lines = array ();
	public $categories = array ();
	public $dict_categories = array ();
	public $trainings = array ();
	public $thirdparty;
	public $agefodd_session_formateur;
	public $email;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	public function __construct($db) {
		$this->db = $db;
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
	public function create($user, $notrigger = 0) {
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

			// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_formateur(";
		$sql .= "fk_socpeople,fk_user, type_trainer, fk_user_author, fk_user_mod, entity, datec";
		$sql .= ") VALUES (";
		// trainer is user
		if ($this->type_trainer == $this->type_trainer_def[0]) {
			$sql .= 'NULL, ';
			$sql .= " " . $this->fk_user . ", ";
			$sql .= "'" . $this->type_trainer_def[0] . "', ";
		}
		// trainer is Dolibarr contact
		elseif ($this->type_trainer == $this->type_trainer_def[1]) {
			$sql .= " " . $this->spid . ", ";
			$sql .= 'NULL, ';
			$sql .= "'" . $this->type_trainer_def[1] . "', ";
		}
		$sql .= " " . $user->id . ",";
		$sql .= " " . $user->id . ",";
		$sql .= " " . $conf->entity . ",";
		$sql .= "'" .$this->db->idate(dol_now())."'" ;
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
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
	 * Load object in memory from user object
	 *
	 * @param object $user user object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchByUser($user) {
		global $conf;
		
		$error = 0;
		
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_formateur';
		$sql.= ' WHERE (fk_user = '.$user->id.' AND type_trainer = \'user\')';
		if (!empty($user->contactid)) $sql.= ' OR (fk_socpeople = '.$user->contactid.' AND type_trainer = \'socpeople\')';
		$sql.= ' AND entity = '.$conf->entity;
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$res = $this->fetch($obj->rowid);
				if ($res < 0) return $res;
			} else return 0;
			$this->db->free($resql);
		} else {
			$this->errors[] = "Error " . $this->db->lasterror();
			$error++;
		}
		
		if (empty($error)) {
			return 1;
		} else {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::" . __METHOD__ . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return - 1 * $error;
		}
	}
	
	/**
	 * Load object in memory from database
	 *
	 * @param int $id object id
	 * @param int $arch unused
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id, $arch = 0) {
		global $mysoc;
		
		$error = 0;

		$sql = "SELECT";
		$sql .= " f.rowid, f.entity, f.fk_socpeople, f.fk_user, f.type_trainer,  f.archive,";
		$sql .= " s.rowid as spid , s.lastname as sp_name, s.firstname as sp_firstname, s.civility as sp_civilite, ";
		$sql .= " s.phone as sp_phone, s.email as sp_email, s.phone_mobile as sp_phone_mobile, ";
		$sql .= " u.lastname as u_name, u.firstname as u_firstname, u.civility as u_civilite, ";
		$sql .= " u.office_phone as u_phone, u.email as u_email, u.user_mobile as u_phone_mobile";
		$sql .= " ,s.address as s_address, s.zip as s_zip, s.town as s_town";
		$sql .= " ,s.fk_soc as soctrainerid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formateur as f";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as s ON f.fk_socpeople = s.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON f.fk_user = u.rowid";
		$sql .= " WHERE f.rowid = " . $id;
		$sql .= " AND f.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";

		dol_syslog(get_class($this) . "::fetch ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->ref = $obj->rowid; // Use for show_next_prev
				$this->entity = $obj->entity;
				$this->archive = $obj->archive;
				$this->type_trainer = $obj->type_trainer;

				// trainer is user
				if ($this->type_trainer == $this->type_trainer_def[0]) {
					$this->fk_user = $obj->fk_user;
					$this->name = $obj->u_name;
					$this->firstname = $obj->u_firstname;
					$this->civilite = $obj->u_civilite;
					$this->phone = $obj->u_phone;
					$this->email = $obj->u_email;
					$this->phone_mobile = $obj->u_phone_mobile;
					$this->address = $mysoc->address;
					$this->zip = $mysoc->zip;
					$this->town = $mysoc->town;
					$this->thirdparty=$mysoc;
				}
				// trainer is Dolibarr contact
				elseif ($this->type_trainer == $this->type_trainer_def[1]) {
					$this->spid = $obj->spid;
					$this->fk_socpeople = $obj->fk_socpeople;
					$this->name = $obj->sp_name;
					$this->firstname = $obj->sp_firstname;
					$this->civilite = $obj->sp_civilite;
					$this->phone = $obj->sp_phone;
					$this->email = $obj->sp_email;
					$this->phone_mobile = $obj->sp_phone_mobile;
					$this->address = $obj->s_address;
					$this->zip = $obj->s_zip;
					$this->town = $obj->s_town;
					if (!empty($obj->soctrainerid)) {
						$soctrainer= new Societe($this->db);
						$soctrainer->fetch($obj->soctrainerid);
						$this->thirdparty=$soctrainer;
					}
				}


				$sql_inner='SELECT cat.rowid as catid, dict.rowid as dictid,dict.code,dict.label,dict.description ';
				$sql_inner.=' FROM '.MAIN_DB_PREFIX.'agefodd_formateur_category as cat';
				$sql_inner.=' INNER JOIN '.MAIN_DB_PREFIX.'agefodd_formateur_category_dict as dict';
				$sql_inner.=' ON cat.fk_category=dict.rowid AND cat.fk_trainer='.$obj->rowid;
				//$line->fk_socpeople
				dol_syslog(get_class($this) . "::fetch_all ", LOG_DEBUG);
				$resql_inner = $this->db->query($sql_inner);
				if ($resql_inner) {
					while ($objcat = $this->db->fetch_object($resql_inner) ) {
						$trainer_cat = new AgfTrainerCategorie();
						$trainer_cat->catid=$objcat->catid;
						$trainer_cat->dictid=$objcat->dictid;
						$trainer_cat->code=$objcat->code;
						$trainer_cat->label=$objcat->label;
						$trainer_cat->description=$objcat->description;
						$this->categories[]=$trainer_cat;
					}
				} else {
					$this->errors[] = "Error " . $this->db->lasterror();
					$error++;
				}


				$sql_inner='SELECT training.rowid as linkid, dict.rowid as trainingid,dict.ref,dict.ref_interne,dict.intitule ';
				$sql_inner.=' FROM '.MAIN_DB_PREFIX.'agefodd_formateur_training as training';
				$sql_inner.=' INNER JOIN '.MAIN_DB_PREFIX.'agefodd_formation_catalogue as dict';
				$sql_inner.=' ON training.fk_training=dict.rowid AND training.fk_trainer='.$obj->rowid;
				//$line->fk_socpeople
				dol_syslog(get_class($this) . "::fetch_all ", LOG_DEBUG);
				$resql_inner = $this->db->query($sql_inner);
				if ($resql_inner) {
					while ($objcat = $this->db->fetch_object($resql_inner) ) {
						$trainer_training = new AgfTrainerTraining();
						$trainer_training->linkid=$objcat->linkid;
						$trainer_training->trainingid=$objcat->trainingid;
						$trainer_training->ref=$objcat->ref;
						$trainer_training->ref_interne=$objcat->ref_interne;
						$trainer_training->intitule=$objcat->intitule;
						$this->trainings[]=$trainer_training;
					}
				} else {
					$this->errors[] = "Error " . $this->db->lasterror();
					$error++;
				}

			} else return 0;
			$this->db->free($resql);
		} else {
			$this->errors[] = "Error " . $this->db->lasterror();
			$error++;
		}


		if (empty($error)) {
			return 1;
		} else {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::" . __METHOD__ . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return - 1 * $error;
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
	public function fetch_all($sortorder, $sortfield, $limit, $offset, $arch = 0, $filter = array()) {
		global $mysoc;

		$error=0;

		$sql = "SELECT";
		$sql .= " f.rowid, f.entity, f.fk_socpeople, f.fk_user, f.type_trainer,  f.archive,";

		$sql .= " s.rowid as spid , ";

		//$sql .= " IF(u.lastname IS NULL, s.lastname, u.lastname) as sp_name,"; // TODO : remove this comment if all is ok after few tests with CASE style
		$sql .= " CASE WHEN u.lastname IS NULL THEN s.lastname ELSE u.lastname END  as sp_name,"; // FOR pgsql

		//$sql .= " IF(u.firstname IS NULL, s.firstname, u.firstname) as sp_firstname,"; // TODO : remove this comment if all is ok after few tests with CASE style
		$sql .= " CASE WHEN u.firstname IS NULL THEN s.firstname ELSE u.firstname END  as sp_firstname,"; // FOR pgsql

		//$sql .= " IF(u.civility IS NULL, s.civility, u.civility) as sp_civilite, "; // TODO : remove this comment if all is ok after few tests with CASE style
		$sql .= " CASE WHEN u.civility IS NULL THEN s.civility ELSE u.civility END  as sp_civilite,"; // FOR pgsql



		$sql .= " s.phone as sp_phone, s.email as sp_email, s.phone_mobile as sp_phone_mobile, ";
		$sql .= " u.lastname as u_name, u.firstname as u_firstname, u.civility as u_civilite, ";
		$sql .= " u.office_phone as u_phone, u.email as u_email, u.user_mobile as u_phone_mobile";
		$sql .= " ,s.fk_soc as soctrainerid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formateur as f";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as s ON f.fk_socpeople = s.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON f.fk_user = u.rowid";
		$sql .= " WHERE f.entity IN (" . getEntity('agefodd') . ")";
		if ($arch == 0 || $arch == 1) {
			$sql .= " AND f.archive = " . $arch;
		}

			// Manage filter
		if (count($filter) > 0) {
			foreach ( $filter as $key => $value ) {
				if ($key == 'f.rowid' || $key == 'f.fk_socpeople') {
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
		if (!empty($sortfield)) {
			$sql .= " ORDER BY " . $sortfield . " " . $sortorder . " ";
		}
		if (! empty($limit)) {
			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::fetch_all ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array();

			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);

					$line = new AgfTrainerLine();

					$line->thirdparty=new stdClass();

					$line->id = $obj->rowid;
					$this->entity = $obj->entity;
					$line->type_trainer = $obj->type_trainer;
					$line->archive = $obj->archive;
					// trainer is user
					if ($line->type_trainer == $this->type_trainer_def[0]) {
						$line->fk_user = $obj->fk_user;
						$line->name = $obj->u_name;
						$line->firstname = $obj->u_firstname;
						$line->civilite = $obj->u_civilite;
						$line->phone = $obj->u_phone;
						$line->email = $obj->u_email;
						$line->phone_mobile = $obj->u_phone_mobile;
						$line->fk_socpeople = $obj->fk_socpeople;
						$line->thirdparty=$mysoc;
					}
					// trainer is Dolibarr contact
					elseif ($line->type_trainer == $this->type_trainer_def[1]) {
						$line->spid = $obj->spid;
						$line->name = $obj->sp_name;
						$line->firstname = $obj->sp_firstname;
						$line->civilite = $obj->sp_civilite;
						$line->phone = $obj->sp_phone;
						$line->email = $obj->sp_email;
						$line->phone_mobile = $obj->sp_phone_mobile;
						$line->fk_socpeople = $obj->fk_socpeople;
						if (!empty($obj->soctrainerid)) {
							$soctrainer= new Societe($this->db);
							$soctrainer->fetch($obj->soctrainerid);
							$line->thirdparty=$soctrainer;
						}

					}

					$sql_inner='SELECT cat.rowid as catid, dict.rowid as dictid,dict.code,dict.label,dict.description ';
					$sql_inner.=' FROM '.MAIN_DB_PREFIX.'agefodd_formateur_category as cat';
					$sql_inner.=' INNER JOIN '.MAIN_DB_PREFIX.'agefodd_formateur_category_dict as dict';
					$sql_inner.=' ON cat.fk_category=dict.rowid AND cat.fk_trainer='.$obj->rowid;
					//$line->fk_socpeople
					dol_syslog(get_class($this) . "::fetch_all ", LOG_DEBUG);
					$resql_inner = $this->db->query($sql_inner);
					if ($resql_inner) {
						while ($objcat = $this->db->fetch_object($resql_inner) ) {
							$trainer_cat = new AgfTrainerCategorie();
							$trainer_cat->catid=$objcat->catid;
							$trainer_cat->dictid=$objcat->dictid;
							$trainer_cat->code=$objcat->code;
							$trainer_cat->label=$objcat->label;
							$trainer_cat->description=$objcat->description;
							$line->categories[]=$trainer_cat;
						}
					} else {
						$this->errors[] = "Error " . $this->db->lasterror();
						$error++;
					}

					$sql_inner='SELECT training.rowid as linkid, dict.rowid as trainingid,dict.ref,dict.ref_interne,dict.intitule ';
					$sql_inner.=' FROM '.MAIN_DB_PREFIX.'agefodd_formateur_training as training';
					$sql_inner.=' INNER JOIN '.MAIN_DB_PREFIX.'agefodd_formation_catalogue as dict';
					$sql_inner.=' ON training.fk_training=dict.rowid AND training.fk_trainer='.$obj->rowid;
					//$line->fk_socpeople
					dol_syslog(get_class($this) . "::fetch_all ", LOG_DEBUG);
					$resql_inner = $this->db->query($sql_inner);
					if ($resql_inner) {
						while ($objcat = $this->db->fetch_object($resql_inner) ) {
							$trainer_training = new AgfTrainerTraining();
							$trainer_training->linkid=$objcat->linkid;
							$trainer_training->trainingid=$objcat->trainingid;
							$trainer_training->ref=$objcat->ref;
							$trainer_training->ref_interne=$objcat->ref_interne;
							$trainer_training->intitule=$objcat->intitule;
							$line->trainings[]=$trainer_training;
						}
					} else {
						$this->errors[] = "Error " . $this->db->lasterror();
						$error++;
					}

					$this->lines[$i] = $line;
					$i ++;
				}
			}
			$this->db->free($resql);
		} else {
			$this->errors[] = "Error " . $this->db->lasterror();
			$error++;
		}


		if (empty($error)) {
			return $num;
		} else {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::" . __METHOD__ . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return - 1 * $error;
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
		$sql .= " f.rowid, f.entity, f.datec, f.tms, f.fk_user_mod, f.fk_user_author";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formateur as f";
		$sql .= " WHERE f.rowid = " . $id;

		dol_syslog(get_class($this) . "::fetch ", LOG_DEBUG);
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

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		if (! isset($this->archive))
			$this->archive = 0;
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_formateur SET";
		$sql .= " fk_socpeople =" . (!empty($this->fk_socpeople) ? $this->fk_socpeople : 'NULL') . " ,";
		$sql .= " fk_user =" . (!empty($this->fk_user) ? $this->fk_user : 'NULL') . " ,";
		$sql .= " type_trainer ='" . $this->db->escape($this->type_trainer) . "' ,";
		$sql .= " fk_user_mod=" . $user->id . " ,";
		$sql .= " archive=" . $this->archive . " ";
		$sql .= " WHERE rowid = " . $this->id;

		$this->db->begin();

		dol_syslog(get_class($this) . "::update ", LOG_DEBUG);
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
	 * Delete object in database
	 *
	 * @param int $id id of agefodd_formateur to delete
	 * @return int <0 if KO, >0 if OK
	 */
	public function remove($id) {
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

	/**
	 *
	 * @param string $label
	 * @return string
	 */
	public function getNomUrl($label = 'name') {
		$link = dol_buildpath('/agefodd/trainer/card.php', 1);
		if ($label == 'name') {
			return '<a href="' . $link . '?id=' . $this->id . '">' . $this->name . ' ' . $this->firstname . '</a>';
		} else {
			return '<a href="' . $link . '?id=' . $this->id . '">' . $this->$label . '</a>';
		}
	}

	/**
	 *
	 * @return number
	 */
	public function fetchAllCategories() {
		$sql = 'SELECT dict.rowid as dictid,dict.code,dict.label,dict.description ';
		$sql.=' FROM '.MAIN_DB_PREFIX.'agefodd_formateur_category_dict as dict WHERE dict.active=1';

		dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->dict_categories = array ();

			$num = $this->db->num_rows($resql);

			if ($num) {
				while ( $objcat = $this->db->fetch_object($resql) ) {
					$trainer_cat = new AgfTrainerCategorie();
					$trainer_cat->dictid=$objcat->dictid;
					$trainer_cat->code=$objcat->code;
					$trainer_cat->label=$objcat->label;
					$trainer_cat->description=$objcat->description;
					$this->dict_categories[]=$trainer_cat;
				}
			}
		}else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::".__METHOD__." ERROR :" . $this->error, LOG_ERR);
			return - 1;
		}

		return $num;
	}


	/**
	 *
	 * @param array $categories
	 * @param User $user
	 * @return number
	 */
	public function setTrainerCat($categories, $user) {
		$error=0;

		$this->db->begin();


		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'agefodd_formateur_category WHERE fk_trainer='.$this->id;

		dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::".__METHOD__." ERROR :" . $this->error, LOG_ERR);
			$error++;
		}

		if (empty($error) && count($categories)>0) {
			foreach($categories as $catid) {
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'agefodd_formateur_category(fk_trainer,fk_category,fk_user_author,datec,fk_user_mod,tms) ';
				$sql .= ' VALUES ('.$this->id.','.$catid.','.$user->id.',\''.$this->db->idate(dol_now()).'\','.$user->id.',\''.$this->db->idate(dol_now()).'\')';

				dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::".__METHOD__." ERROR :" . $this->error, LOG_ERR);
					$error++;
				}
			}
		}


		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::".__METHOD__ . $errmsg, LOG_ERR);
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
	 *
	 * @param array $training
	 * @param User $user
	 * @return number
	 */
	public function setTrainerTraining($training ,$user) {
		$error=0;

		$this->db->begin();


		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'agefodd_formateur_training WHERE fk_trainer='.$this->id;

		dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::".__METHOD__." ERROR :" . $this->error, LOG_ERR);
			$error++;
		}

		if (empty($error) && count($training)>0) {
			foreach($training as $key=>$trainingid) {
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'agefodd_formateur_training(fk_trainer,fk_training,fk_user_author,datec,fk_user_mod,tms) ';
				$sql .= ' VALUES ('.$this->id.','.$trainingid.','.$user->id.',\''.$this->db->idate(dol_now()).'\','.$user->id.',\''.$this->db->idate(dol_now()).'\')';

				dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::".__METHOD__." ERROR :" . $this->error, LOG_ERR);
					$error++;
				}
			}
		}


		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::".__METHOD__ . $errmsg, LOG_ERR);
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

class AgfTrainerLine {
	public $id;
	public $type_trainer;
	public $archive;
	public $fk_user;
	public $name;
	public $firstname;
	public $civilite;
	public $phone;
	public $email;
	public $phone_mobile;
	public $fk_socpeople;
	public $thirdparty;
	public $categories = array ();
	public $trainings = array ();
	public function __construct() {
		return 1;
	}
	/**
	 *
	 * @param string $label
	 * @param string $type
	 * @return string
	 */
	public function getNomUrl($label = 'name', $type='card') {
		$link = dol_buildpath('/agefodd/trainer/'.$type.'.php', 1);
		if ($label == 'name') {
			return '<a href="' . $link . '?id=' . $this->id . '">' . $this->name . ' ' . $this->firstname . '</a>';
		} else {
			return '<a href="' . $link . '?id=' . $this->id . '">' . $this->$label . '</a>';
		}
	}
}

class AgfTrainerCategorie {
	public $catid;
	public $dictid;
	public $code;
	public $label;
	public $description;
	public function __construct() {
		return 1;
	}
}

class AgfTrainerTraining {
	public $linkid;
	public $trainingid;
	public $ref;
	public $ref_interne;
	public $intitule;
	public function __construct() {
		return 1;
	}
}
