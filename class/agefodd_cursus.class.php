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
 * \file agefodd/class/agefodd_curus.class.php
 * \ingroup agefodd
 * \brief class to manage 'training program' on agefodd module
 */

// Put here all includes required by your class file
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
// require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
// require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

/**
 * Put here description of your class
 */
class Agefodd_cursus extends CommonObject {
	public $error; // !< To return error code (or message)
	public $errors = array (); // !< To return several error codes (or messages)
	public $element = 'agefodd_cursus'; // !< Id that identify managed objects
	public $table_element = 'agefodd_cursus'; // !< Name of table without prefix where object is stored
	public $ismultientitymanaged = 1; // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	public $id;
	public $ref_interne;
	public $entity;
	public $intitule;
	public $archive;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $note_private;
	public $note_public;
	public $tms = '';
	public $lines = array ();
	public $fk_stagiaire;

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

		if (isset($this->ref_interne))
			$this->ref_interne = trim($this->ref_interne);
		if (isset($this->intitule))
			$this->intitule = trim($this->intitule);
		if (isset($this->note_private))
			$this->note_private = trim($this->note_private);
		if (isset($this->note_public))
			$this->note_public = trim($this->note_public);

			// Check parameters
			// Put here code to add control on parameters values

		if (empty($this->ref_interne)) {
			$error ++;
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AgfRefInterne"));
		}
		if (empty($this->intitule)) {
			$error ++;
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AgfIntitule"));
		}

		if (! $error) {

			// Insert request
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_cursus(";

			$sql .= "ref_interne,";
			$sql .= "entity,";
			$sql .= "intitule,";
			$sql .= "archive,";
			$sql .= "fk_user_author,";
			$sql .= "datec,";
			$sql .= "fk_user_mod,";
			$sql .= "note_private,";
			$sql .= "note_public";

			$sql .= ") VALUES (";

			$sql .= " " . (! isset($this->ref_interne) ? 'NULL' : "'" . $this->db->escape($this->ref_interne) . "'") . ",";
			$sql .= " " . $conf->entity . ",";
			$sql .= " " . (! isset($this->intitule) ? 'NULL' : "'" . $this->db->escape($this->intitule) . "'") . ",";
			$sql .= " " . (! isset($this->archive) ? '0' : $this->archive) . ",";
			$sql .= " " . $user->id . ",";
			$sql .= " '" . $this->db->idate(dol_now()) . "',";
			$sql .= " " . $user->id . ",";
			$sql .= " " . (! isset($this->note_private) ? 'NULL' : "'" . $this->db->escape($this->note_private) . "'") . ",";
			$sql .= " " . (! isset($this->note_public) ? 'NULL' : "'" . $this->db->escape($this->note_public) . "'");

			$sql .= ")";

			$this->db->begin();

			dol_syslog(get_class($this) . "::create", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_cursus");

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
		if (! $error) {
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
{
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error ++;
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
	 * Load object in memory from the database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id) {
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.ref_interne,";
		$sql .= " t.entity,";
		$sql .= " t.intitule,";
		$sql .= " t.archive,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.note_private,";
		$sql .= " t.note_public,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_cursus as t";
		$sql .= " WHERE t.rowid = " . $id;
		$sql .= " AND t.entity IN (" . getEntity('agefodd'/*'agsession'*/) . ")";

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
		    $num = $this->db->num_rows($resql);
		    
			if ($num) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->ref = $obj->rowid; // Needed for show_next_prev

				$this->ref_interne = $obj->ref_interne;
				$this->entity = $obj->entity;
				$this->intitule = $obj->intitule;
				$this->archive = $obj->archive;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->note_private = $obj->note_private;
				$this->note_public = $obj->note_public;
				$this->tms = $this->db->jdate($obj->tms);
				
				require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
				$extrafields = new ExtraFields($this->db);
				$extralabels = $extrafields->fetch_name_optionals_label($this->table_element, true);
				if (count($extralabels) > 0) {
				    $this->fetch_optionals($this->id, $extralabels);
				}
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
	 * Update object into database
	 *
	 * @param User $user that modifies
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->ref_interne))
			$this->ref_interne = trim($this->ref_interne);
		if (isset($this->intitule))
			$this->intitule = trim($this->intitule);
		if (isset($this->note_private))
			$this->note_private = trim($this->note_private);
		if (isset($this->note_public))
			$this->note_public = trim($this->note_public);

		if (empty($this->ref_interne)) {
			$error ++;
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AgfRefInterne"));
		}
		
		if (empty($this->intitule)) {
			$error ++;
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AgfIntitule"));
		}
		
		if (! $error) {
			// Check parameters
			// Put here code to add a control on parameters values

			// Update request
			$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_cursus SET";

			$sql .= " ref_interne=" . (isset($this->ref_interne) ? "'" . $this->db->escape($this->ref_interne) . "'" : "null") . ",";
			$sql .= " entity=" . $this->entity/*$conf->entity*/ . ",";
			$sql .= " intitule=" . (isset($this->intitule) ? "'" . $this->db->escape($this->intitule) . "'" : "null") . ",";
			$sql .= " archive=" . (isset($this->archive) ? $this->archive : "0") . ",";
			$sql .= " fk_user_mod=" . $user->id . ",";
			$sql .= " note_private=" . (isset($this->note_private) ? "'" . $this->db->escape($this->note_private) . "'" : "null") . ",";
			$sql .= " note_public=" . (isset($this->note_public) ? "'" . $this->db->escape($this->note_public) . "'" : "null");

			$sql .= " WHERE rowid=" . $this->id;
			
			$this->db->begin();

			dol_syslog(get_class($this) . "::update", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
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

		if (! $error) {
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
{
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error ++;
				}
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
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_cursus";
			$sql .= " WHERE rowid=" . $this->id;

			dol_syslog(get_class($this) . "::delete");
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}

		if (! $error) {
			// Removed extrafields
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
{
				$result = $this->deleteExtraFields();
				if ($result < 0) {
					$error ++;
					dol_syslog(get_class($this) . "::delete erreur " . $error . " " . $this->error, LOG_ERR);
				}
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
	public function info($id) {
		$sql = "SELECT";
		$sql .= " c.rowid, c.entity, c.datec, c.tms, c.fk_user_mod, c.fk_user_author";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_cursus as c";
		$sql .= " WHERE c.rowid = " . $id;

		dol_syslog(get_class($this) . "::info ", LOG_DEBUG);
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
			dol_syslog(get_class($this) . "::info " . $this->error, LOG_ERR);
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

		$this->ref_interne = '';
		$this->entity = '';
		$this->intitule = '';
		$this->fk_user_author = '';
		$this->datec = '';
		$this->fk_user_mod = '';
		$this->note_private = '';
		$this->note_public = '';
		$this->tms = '';
	}

	/**
	 * Load object in memory from database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit offset limit
	 * @param int $offset offset limit
	 * @param int $arch archive
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_all($sortorder, $sortfield, $limit, $offset, $arch = 0) {
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.ref_interne,";
		$sql .= " t.entity,";
		$sql .= " t.intitule,";
		$sql .= " t.archive,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.note_private,";
		$sql .= " t.note_public,";
		$sql .= " t.tms";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_cursus as t";
		$sql .= " WHERE t.entity IN (" . getEntity('agefodd'/*agcursus*/) . ")";
		if ($arch == 0 || $arch == 1) {
			$sql .= " AND t.archive = " . $arch;
		}

		if (! empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}

		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::fetch ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {

			$this->lines = array ();
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new AgfCursusLine();

				$line->id = $obj->rowid;

				$line->ref_interne = $obj->ref_interne;
				$line->entity = $obj->entity;
				$line->intitule = $obj->intitule;
				$line->archive = $obj->archive;
				$line->fk_user_author = $obj->fk_user_author;
				$line->datec = $this->db->jdate($obj->datec);
				$line->fk_user_mod = $obj->fk_user_mod;
				$line->note_private = $obj->note_private;
				$line->note_public = $obj->note_public;
				$line->tms = $this->db->jdate($obj->tms);

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
}
class AgfCursusLine {
	public $id;
	public $ref_interne;
	public $entity;
	public $intitule;
	public $archive;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $note_private;
	public $note_public;
	public $tms = '';
	public function __construct() {
		return 1;
	}
}
