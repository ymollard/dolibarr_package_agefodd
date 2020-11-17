<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013-2016 Florian Henry <florian.henry@open-concept.pro>
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
 * \file agefodd/class/agefodd_stagiaire_curus.class.php
 * \ingroup agefodd
 * \brief class to manage 'training program' link to trainee on agefodd module
 */

// Put here all includes required by your class file
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
// require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
// require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

/**
 * Put here description of your class
 */
class Agefodd_stagiaire_cursus extends CommonObject {
	public $error; // !< To return error code (or message)
	public $errors = array (); // !< To return several error codes (or messages)
	public $element = 'agefodd_stagiaire_cursus'; // !< Id that identify managed objects
	public $table_element = 'agefodd_stagiaire_cursus'; // !< Name of table without prefix where object is stored
	public $ismultientitymanaged = 1; // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	public $id;
	public $entity;
	public $fk_stagiaire;
	public $fk_cursus;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $tms = '';

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

		if (isset($this->fk_stagiaire))
			$this->fk_stagiaire = trim($this->fk_stagiaire);
		if (isset($this->fk_cursus))
			$this->fk_cursus = trim($this->fk_cursus);
		if (isset($this->fk_user_author))
			$this->fk_user_author = trim($this->fk_user_author);
		if (isset($this->fk_user_mod))
			$this->fk_user_mod = trim($this->fk_user_mod);

			// Check parameters
			// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_stagiaire_cursus(";

		$sql .= "entity,";
		$sql .= "fk_stagiaire,";
		$sql .= "fk_cursus,";
		$sql .= "fk_user_author,";
		$sql .= "datec,";
		$sql .= "fk_user_mod";

		$sql .= ") VALUES (";

		$sql .= " " . $conf->entity . ",";
		$sql .= " " . (! isset($this->fk_stagiaire) ? 'NULL' : "'" . $this->fk_stagiaire . "'") . ",";
		$sql .= " " . (! isset($this->fk_cursus) ? 'NULL' : "'" . $this->fk_cursus . "'") . ",";
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
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_stagiaire_cursus");

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

		$sql .= " t.entity,";
		$sql .= " t.fk_stagiaire,";
		$sql .= " t.fk_cursus,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire_cursus as t";
		$sql .= " WHERE t.rowid = " . $id;

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->entity = $obj->entity;
				$this->fk_stagiaire = $obj->fk_stagiaire;
				$this->fk_cursus = $obj->fk_cursus;
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
	 * @param User $user that modifies
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->fk_stagiaire))
			$this->fk_stagiaire = trim($this->fk_stagiaire);
		if (isset($this->fk_cursus))
			$this->fk_cursus = trim($this->fk_cursus);

			// Check parameters
			// Put here code to add a control on parameters values

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_stagiaire_cursus SET";

		$sql .= " entity=" . $this->entity/*$conf->entity*/ . ",";
		$sql .= " fk_stagiaire=" . (isset($this->fk_stagiaire) ? $this->fk_stagiaire : "null") . ",";
		$sql .= " fk_cursus=" . (isset($this->fk_cursus) ? $this->fk_cursus : "null") . ",";
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
	 * Delete object in database
	 *
	 * @param User $user that deletes
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
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire_cursus";
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

		$this->entity = '';
		$this->fk_stagiaire = '';
		$this->fk_cursus = '';
		$this->fk_user_author = '';
		$this->datec = '';
		$this->fk_user_mod = '';
		$this->tms = '';
	}

	/**
	 * Load object in memory from database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit offset limit
	 * @param int $offset offset limit
	 * @param array $filter filter array
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_stagiaire_per_cursus($sortorder, $sortfield, $limit, $offset, $filter = array()) {
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " so.rowid as socid, so.nom as socname,";
		$sql .= " civ.code as civilitecode,";
		$sql .= " sta.rowid as starowid, sta.nom, sta.prenom, sta.civilite, sta.fk_soc, sta.fonction,";
		$sql .= " sta.fk_socpeople";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire_cursus as t";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_cursus as c ON t.fk_cursus=c.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON t.fk_stagiaire=sta.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON sta.fk_soc = so.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_civility as civ";
		$sql .= " ON sta.civilite = civ.code";

		// Manage filter
		if (! empty($filter)) {
			$sqlwhere = '';

			$addcriteria = false;
			foreach ( $filter as $key => $value ) {
				if ($key == 'civ.code') {
					if ($addcriteria) {
						$sqlwhere .= ' AND ';
					}
					$sqlwhere .= $key . ' = \'' . $value . '\'';
					$addcriteria = true;
				} else {
					if ($addcriteria) {
						$sqlwhere .= ' AND ';
					}
					$sqlwhere .= $key . ' LIKE \'%' . $value . '%\'';
					$addcriteria = true;
				}
			}
			if (! empty($sqlwhere)) {
				$sql .= ' WHERE ' . $sqlwhere;
			}
		} else {
			$sql .= " WHERE c.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";
		}

		$sql .= " AND fk_cursus=" . $this->fk_cursus;
		$sql .= " ORDER BY " . $sortfield . " " . $sortorder;
		if(!empty($limit)) $sql .= " " . $this->db->plimit($limit + 1, $offset);
		
		dol_syslog(get_class($this) . "::fetch_stagiaire_per_cursus", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);

			while ( $obj = $this->db->fetch_object($resql) ) {
				$line = new AgfCursusTraineeLine();

				$line->id = $obj->rowid;

				$line->starowid = $obj->starowid;
				$line->socid = $obj->socid;
				$line->socname = $obj->socname;
				$line->civilitecode = $obj->civilitecode;
				$line->nom = $obj->nom;
				$line->prenom = $obj->prenom;
				$line->civilite = $obj->civilite;

				// Count how many session of cursus trainee was done
				$sqlsessdone = "SELECT";
				$sqlsessdone .= " count(DISTINCT formcur.fk_formation_catalogue) as countsess";

				$sqlsessdone .= " FROM " . MAIN_DB_PREFIX . "agefodd_cursus as cursus ";
				$sqlsessdone .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire_cursus as stacur ON stacur.fk_cursus=cursus.rowid AND stacur.fk_stagiaire=" . $line->starowid;
				$sqlsessdone .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_cursus as formcur ON formcur.fk_cursus=cursus.rowid";
				$sqlsessdone .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON stacur.fk_stagiaire=sta.rowid AND sta.rowid=" . $line->starowid;
				$sqlsessdone .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as form ON formcur.fk_formation_catalogue=form.rowid";
				$sqlsessdone .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as sess ON sess.fk_formation_catalogue=form.rowid";
				$sqlsessdone .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as sessta ON sessta.fk_session_agefodd=sess.rowid AND sessta.fk_stagiaire=sta.rowid";
				$sqlsessdone .= " WHERE cursus.rowid=" . $this->fk_cursus;

				dol_syslog(get_class($this) . "::fetch_stagiaire_per_cursus sqlsessdone", LOG_DEBUG);
				$resqlsessdone = $this->db->query($sqlsessdone);
				if ($resqlsessdone) {
					$objsessdone = $this->db->fetch_object($resqlsessdone);
					$line->nbsessdone = $objsessdone->countsess;
				} else {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::fetch_stagiaire_per_cursus " . $this->error, LOG_ERR);
					return - 1;
				}

				// Count how many session of cursus trainee was done
				$sqlsessdoto = "SELECT";
				$sqlsessdoto .= " count(formcur.fk_formation_catalogue) as nbtotalform";
				$sqlsessdoto .= " FROM " . MAIN_DB_PREFIX . "agefodd_formation_cursus as formcur WHERE formcur.fk_cursus=" . $this->fk_cursus;

				dol_syslog(get_class($this) . "::fetch_stagiaire_per_cursus sqlsessdoto", LOG_DEBUG);
				$resqlsesstodo = $this->db->query($sqlsessdoto);
				if ($resqlsesstodo) {
					$objsessdone = $this->db->fetch_object($resqlsesstodo);
					$line->nbsesstodo = $objsessdone->nbtotalform - $line->nbsessdone;
				} else {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::fetch_stagiaire_per_cursus " . $this->error, LOG_ERR);
					return - 1;
				}

				$this->db->free($resqlsessdone);
				$this->db->free($resqlsesstodo);

				$this->lines[] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_stagiaire_per_cursus " . $this->error, LOG_ERR);
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
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_session_cursus_per_trainee($sortorder, $sortfield, $limit, $offset) {
		$sql = "SELECT";
		$sql .= " s.rowid as sessid,";
		$sql .= " so.rowid as socid,";
		$sql .= " so.nom as socname,";
		$sql .= " s.type_session,";
		$sql .= " s.fk_session_place,";
		$sql .= " s.dated,";
		$sql .= " s.datef,";
		$sql .= " c.intitule,";
		$sql .= " c.ref,";
		$sql .= " c.ref_interne,";
		$sql .= " s.color,";
		$sql .= " ss.status_in_session,";
		$sql .= " c.fk_c_category,dictcat.code as catcode ,dictcat.intitule as catlib";
		$sql .= " ,p.rowid as placeid, p.ref_interne as placecode";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
		$sql .= " ON p.rowid = s.fk_session_place";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " ON c.rowid = s.fk_formation_catalogue";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_type as dictcat ";
		$sql .= " ON dictcat.rowid=c.fk_c_category";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
		$sql .= " ON sa.rowid = ss.fk_stagiaire";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_civility as civ";
		$sql .= " ON civ.code = sa.civilite";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = s.fk_soc";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sope";
		$sql .= " ON sope.rowid = sa.fk_socpeople";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_cursus as formcur ON formcur.fk_formation_catalogue=c.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire_cursus as stacur ON stacur.fk_stagiaire=sa.rowid AND stacur.fk_stagiaire=" . $this->fk_stagiaire;
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_cursus as cursus  ON stacur.fk_cursus=cursus.rowid AND formcur.fk_cursus=cursus.rowid AND cursus.rowid=" . $this->fk_cursus;
		$sql .= " ORDER BY " . $sortfield . " " . $sortorder . " ";
		if (! empty($limit)) {
			$this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::fetch_session_cursus_per_trainee", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new AgfSessionCursusLine();

				$line->rowid = $obj->sessid;
				$line->socid = $obj->socid;
				$line->socname = $obj->socname;
				$line->type_session = $obj->type_session;
				$line->fk_session_place = $obj->fk_session_place;
				$line->dated = $this->db->jdate($obj->dated);
				$line->datef = $this->db->jdate($obj->datef);
				$line->intitule = $obj->intitule;
				$line->ref = $obj->ref;
				$line->ref_interne = $obj->ref_interne;
				$line->color = $obj->color;
				$line->status_in_session = $obj->status_in_session;
				if (! empty($obj->catcode) || ! empty($obj->catlib)) {
					$line->category_lib = $obj->catcode . ' - ' . $obj->catlib;
				}
				$line->placecode = $obj->placecode;

				$this->lines[$i] = $line;

				$i ++;
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_session_cursus_per_trainee " . $this->error, LOG_ERR);
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
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_cursus_per_trainee($sortorder = 'ASC', $sortfield = 'c.rowid', $limit = 0, $offset = 0) {
		$sql = "SELECT";
		$sql .= " c.rowid,";
		$sql .= " c.ref_interne,";
		$sql .= " c.intitule,";
		$sql .= " c.archive";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire_cursus as t";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_cursus as c ON t.fk_cursus=c.rowid AND t.fk_stagiaire=" . $this->fk_stagiaire;
		$sql .= " ORDER BY " . $sortfield . " " . $sortorder;
		if(!empty($limit)) $sql .= " " . $this->db->plimit($limit + 1, $offset);

		dol_syslog(get_class($this) . "::fetch_cursus_per_trainee", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);

			while ( $obj = $this->db->fetch_object($resql) ) {
				$line = new AgfTraineeCursusLine();

				$line->id = $obj->rowid;

				$line->ref_interne = $obj->ref_interne;
				$line->intitule = $obj->intitule;
				$line->archive = $obj->archive;

				$this->lines[] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_cursus_per_trainee " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_training_session_to_plan() {
		$sql = "SELECT";
		$sql .= " c.rowid,";
		$sql .= " c.ref_interne,";
		$sql .= " c.ref,";
		$sql .= " c.intitule,";
		$sql .= " c.archive";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_cursus as formcur ON formcur.fk_formation_catalogue=c.rowid AND formcur.fk_cursus=" . $this->fk_cursus;
		$sql .= " WHERE c.rowid NOT IN ";
		$sql .= " (SELECT fk_formation_catalogue ";
		$sql .= " FROM  " . MAIN_DB_PREFIX . "agefodd_session as sess ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as sesssta ON sesssta.fk_session_agefodd=sess.rowid ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sesssta.fk_stagiaire AND sta.rowid=" . $this->fk_stagiaire . ")";

		dol_syslog(get_class($this) . "::fetch_cursus_per_trainee", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);

			while ( $obj = $this->db->fetch_object($resql) ) {
				$line = new AgfTrainingCursusLine();

				$line->id = $obj->rowid;

				$line->ref_interne = $obj->ref_interne;
				$line->ref = $obj->ref;
				$line->intitule = $obj->intitule;
				$line->archive = $obj->archive;

				$this->lines[] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_cursus_per_trainee " . $this->error, LOG_ERR);
			return - 1;
		}
	}
}
class AgfCursusTraineeLine {
	public $id;
	public $socid;
	public $socname;
	public $civilitecode;
	public $nom;
	public $prenom;
	public $civilite;
	public $starowid;
	public $nbsessdone;
	public $nbsesstodo;
	public function __construct() {
		return 1;
	}
}
class AgfTraineeCursusLine {
	public $id;
	public $ref_interne;
	public $intitule;
	public $archive;
	public function __construct() {
		return 1;
	}
}
class AgfTrainingCursusLine {
	public $id;
	public $ref_interne;
	public $ref;
	public $intitule;
	public $archive;
	public function __construct() {
		return 1;
	}
}

/**
 * Session line Class
 */
class AgfSessionCursusLine {
	public $rowid;
	public $socid;
	public $socname;
	public $type_session;
	public $fk_session_place;
	public $dated;
	public $datef;
	public $intitule;
	public $ref;
	public $ref_interne;
	public $color;
	public $nb_stagiaire;
	public $notes;
	public $nb_subscribe_min;
	public $statuscode;
	public $status_in_session;
	public $category_lib;
	public $placecode;
	public function __construct() {
		return 1;
	}
}

