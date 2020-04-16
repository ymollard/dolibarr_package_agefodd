<?php
/* Copyright (C) 2007-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2012-2014		Florian Henry			<florian.henry@open-concept.pro>
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
 * \file agefodd/class/agefodd_foramtion_catalogue.class.php
 * \ingroup agefodd
 * \brief Manage training object
 */
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 * trainning Class
 */
class Formation extends CommonObject {
	public $error;
	public $errors = array ();
	public $element = 'agefodd_formation_catalogue';
	public $table_element = 'agefodd_formation_catalogue';
	public $ismultientitymanaged = 1; // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	public $id;
	public $entity;
	public $ref;
	public $ref_obj;
	public $ref_interne;
	public $intitule;
	public $duree;
	public $public;
	public $methode;
	public $prerequis;
	public $but;
	public $programme;
	public $pedago_usage;
	public $sanction;
	public $note1;
	public $note2;
	public $archive;
	public $note_private;
	public $note_public;
	public $fk_product;
	public $nb_subscribe_min;
	public $fk_formation_catalogue;
	public $priorite;
	public $fk_c_category;
	public $fk_c_category_bpf;
	public $category_lib;
	public $category_lib_bpf;
	public $certif_duration;
	public $colors;
	public $qr_code_info;
	public $lines = array ();
	public $trainers = array ();
	public $nb_place;

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
		if (isset($this->intitule))
			$this->intitule = $this->db->escape(trim($this->intitule));
		if (isset($this->public))
			$this->public = $this->db->escape(trim($this->public));
		if (isset($this->methode))
			$this->methode = $this->db->escape(trim($this->methode));
		if (isset($this->prerequis))
			$this->prerequis = $this->db->escape(trim($this->prerequis));
		if (isset($this->but))
			$this->but = $this->db->escape(trim($this->but));
		if (isset($this->note1))
			$this->note1 = $this->db->escape(trim($this->note1));
		if (isset($this->note2))
			$this->note2 = $this->db->escape(trim($this->note2));
		if (isset($this->programme))
			$this->programme = $this->db->escape(trim($this->programme));
		if (isset($this->pedago_usage))
			$this->pedago_usage = $this->db->escape(trim($this->pedago_usage));
		if (isset($this->sanction))
			$this->sanction = $this->db->escape(trim($this->sanction));
		if (isset($this->certif_duration))
			$this->certif_duration = $this->db->escape(trim($this->certif_duration));
		if (isset($this->ref_interne))
			$this->ref_interne = $this->db->escape(trim($this->ref_interne));
		if (isset($this->qr_code_info))
			$this->qr_code_info = $this->db->escape(trim($this->qr_code_info));

		if (empty($this->duree))
			$this->duree = 0;

		if ($this->fk_c_category == - 1)
			$this->fk_c_category = 0;

		if ($this->fk_c_category_bpf == - 1)
			$this->fk_c_category_bpf = 0;

			// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_formation_catalogue(";
		$sql .= "datec, ref,ref_interne,intitule, duree, nb_place, public, methode, prerequis, but,";
		$sql .= "programme, note1, note2, fk_user_author,fk_user_mod,entity,";
		$sql .= "fk_product,nb_subscribe_min,fk_c_category,certif_duration";
		$sql .= ",pedago_usage";
		$sql .= ",sanction";
		$sql .= ",qr_code_info";
		$sql .= ",fk_c_category_bpf";
		$sql .= ") VALUES (";
		$sql .= "'" . $this->db->idate(dol_now()) . "', ";
		$sql .= " " . (! isset($this->ref_obj) ? 'NULL' : "'" . $this->ref_obj . "'") . ",";
		$sql .= " " . (! isset($this->ref_interne) ? 'NULL' : "'" . $this->ref_interne . "'") . ",";
		$sql .= " " . (! isset($this->intitule) ? 'NULL' : "'" . $this->intitule . "'") . ",";
		$sql .= " " . (! isset($this->duree) ? 'NULL' : $this->duree) . ",";
		$sql .= " " . (empty($this->nb_place) ? 'NULL' : $this->nb_place) . ",";
		$sql .= " " . (! isset($this->public) ? 'NULL' : "'" . $this->public . "'") . ",";
		$sql .= " " . (! isset($this->methode) ? 'NULL' : "'" . $this->methode . "'") . ",";
		$sql .= " " . (! isset($this->prerequis) ? 'NULL' : "'" . $this->prerequis . "'") . ",";
		$sql .= " " . (! isset($this->but) ? 'NULL' : "'" . $this->but . "'") . ",";
		$sql .= " " . (! isset($this->programme) ? 'NULL' : "'" . $this->programme . "'") . ",";
		$sql .= " " . (! isset($this->note1) ? 'NULL' : "'" . $this->note1 . "'") . ",";
		$sql .= " " . (! isset($this->note2) ? 'NULL' : "'" . $this->note2 . "'") . ",";
		$sql .= " " . $user->id . ',';
		$sql .= " " . $user->id . ',';
		$sql .= " " . $conf->entity . ', ';
		$sql .= " " . (empty($this->fk_product) ? 'null' : $this->fk_product) . ', ';
		$sql .= " " . (empty($this->nb_subscribe_min) ? "null" : $this->nb_subscribe_min) . ', ';
		$sql .= " " . (empty($this->fk_c_category) ? "null" : $this->fk_c_category) . ', ';
		$sql .= " " . (empty($this->certif_duration) ? "null" : "'" . $this->certif_duration . "'") . ', ';
		$sql .= " " . (empty($this->pedago_usage) ? "null" : "'" . $this->pedago_usage . "'") . ', ';
		$sql .= " " . (empty($this->sanction) ? "null" : "'" . $this->sanction . "'") . ', ';
		$sql .= " " . (empty($this->qr_code_info) ? "null" : "'" . $this->qr_code_info . "'") . ', ';
		$sql .= " " . (empty($this->fk_c_category_bpf) ? "null" : $this->fk_c_category_bpf);
		$sql .= ")";
		$this->db->begin();
		dol_syslog(get_class($this) . "::create ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}
		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_formation_catalogue");
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
		// For avoid conflicts if trigger used
		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error ++;
			}
		}

		if (! $error && ! $notrigger)
		{
			// Call trigger
			$result=$this->call_trigger('AGEFODD_FORMATION_CATALOGUE_CREATE',$user);
			if ($result < 0) { $error++; }
			// End call triggers
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
	 * @param string $ref
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id, $ref = '') {
		$sql = "SELECT";
		$sql .= " c.rowid, c.entity, c.ref, c.ref_interne, c.intitule, c.duree, c.nb_place,";
		$sql .= " c.public, c.methode, c.prerequis, but, c.programme, c.archive, c.note1, c.note2 ";
		$sql .= " ,c.note_private, c.note_public, c.fk_product,c.nb_subscribe_min,c.fk_c_category,dictcat.code as catcode ,dictcat.intitule as catlib ";
		$sql .= " ,c.certif_duration";
		$sql .= " ,c.pedago_usage";
		$sql .= " ,c.sanction";
		$sql .= " ,c.color";
		$sql .= " ,c.qr_code_info";
		$sql .= " ,c.fk_c_category_bpf";
		$sql .= " ,dictcatbpf.code as catcodebpf ,dictcatbpf.intitule as catlibbpf";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_type as dictcat ON dictcat.rowid=c.fk_c_category";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_type_bpf as dictcatbpf ON dictcatbpf.rowid=c.fk_c_category_bpf";
		if ($id && ! $ref)
			$sql .= " WHERE c.rowid = " . $id;
		if (! $id && $ref)
			$sql .= " WHERE c.ref = '" . $ref . "'";
		$sql .= " AND c.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";

		dol_syslog(get_class($this) . "::fetch ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				// I know twice affactation...
				$this->rowid = $obj->rowid;
				// use for next prev ref
				$this->ref = $obj->rowid;
				// use for next prev ref
				$this->ref_obj = $obj->ref;
				$this->entity = $obj->entity;
				$this->ref_interne = $obj->ref_interne;
				$this->intitule = stripslashes($obj->intitule);
				$this->duree = $obj->duree;
				$this->nb_place = $obj->nb_place;
				$this->public = stripslashes($obj->public);
				$this->methode = stripslashes($obj->methode);
				$this->prerequis = stripslashes($obj->prerequis);
				$this->but = stripslashes($obj->but);
				$this->programme = stripslashes($obj->programme);
				$this->note1 = stripslashes($obj->note1);
				$this->note2 = stripslashes($obj->note2);
				$this->archive = $obj->archive;
				$this->note_private = $obj->note_private;
				$this->note_public = $obj->note_public;
				$this->fk_product = $obj->fk_product;
				$this->nb_subscribe_min = $obj->nb_subscribe_min;
				$this->fk_c_category = $obj->fk_c_category;
				if (! empty($obj->catcode) || ! empty($obj->catlib)) {
					$this->category_lib = $obj->catcode . ' - ' . $obj->catlib;
				}
				$this->fk_c_category_bpf = $obj->fk_c_category_bpf;
				if (! empty($obj->catcodebpf) || ! empty($obj->catlibbpf)) {
					$this->category_lib_bpf = $obj->catcodebpf. ' - ' . $obj->catlibbpf;
				}
				$this->certif_duration = $obj->certif_duration;
				$this->pedago_usage = $obj->pedago_usage;
				$this->sanction = $obj->sanction;
				$this->color = $obj->color;
				$this->qr_code_info = $obj->qr_code_info;

				require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
    			$extrafields = new ExtraFields($this->db);
    			$extralabels = $extrafields->fetch_name_optionals_label($this->table_element, true);
    			if (count($extralabels) > 0) {
    				$this->fetch_optionals($this->id, $extralabels);
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
	 * Give information on the object
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function info($id) {
		$sql = "SELECT";
		$sql .= " c.rowid, c.entity, c.datec, c.tms, c.fk_user_author, c.fk_user_mod ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " WHERE c.rowid = " . $id;

		dol_syslog(get_class($this) . "::fetch ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->entity = $obj->entity;
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
		$this->intitule = $this->db->escape(trim($this->intitule));
		$this->ref_obj = $this->db->escape(trim($this->ref_obj));
		$this->ref_interne = $this->db->escape(trim($this->ref_interne));
		$this->public = $this->db->escape(trim($this->public));
		$this->methode = $this->db->escape(trim($this->methode));
		$this->prerequis = $this->db->escape(trim($this->prerequis));
		$this->but = $this->db->escape(trim($this->but));
		$this->programme = $this->db->escape(trim($this->programme));
		$this->pedago_usage = $this->db->escape(trim($this->pedago_usage));
		$this->sanction = $this->db->escape(trim($this->sanction));
		$this->note1 = $this->db->escape(trim($this->note1));
		$this->note2 = $this->db->escape(trim($this->note2));
		$this->certif_duration = $this->db->escape(trim($this->certif_duration));
		if (isset($this->color)) {
			$this->color = trim($this->color);
		}
		if (isset($this->qr_code_info)) {
			$this->qr_code_info = trim($this->qr_code_info);
		}
		if ($this->fk_c_category == - 1) {
			$this->fk_c_category = 0;
		}
		if ($this->fk_c_category_bpf == - 1) {
			$this->fk_c_category_bpf= 0;
		}

		$this->fk_c_category = $this->db->escape(trim($this->fk_c_category));
		$this->fk_c_category_bpf= $this->db->escape(trim($this->fk_c_category_bpf));

		// Check parameters
		// Put here code to add control on parameters values
		if (empty($this->duree))
			$this->duree = 0;

			// Update request
		if (! isset($this->archive))
			$this->archive = 0;
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_formation_catalogue SET";
		$sql .= " ref=" . (isset($this->ref_obj) ? "'" . $this->ref_obj . "'" : "null") . ",";
		$sql .= " ref_interne=" . (isset($this->ref_interne) ? "'" . $this->ref_interne . "'" : "null") . ",";
		$sql .= " intitule=" . (isset($this->intitule) ? "'" . $this->intitule . "'" : "null") . ",";
		$sql .= " duree=" . (isset($this->duree) ? price2num($this->duree) : "null") . ",";
		$sql .= " nb_place=" . (!empty($this->nb_place) ? ($this->nb_place) : "null") . ",";
		$sql .= " public=" . (isset($this->public) ? "'" . $this->public . "'" : "null") . ",";
		$sql .= " methode=" . (isset($this->methode) ? "'" . $this->methode . "'" : "null") . ",";
		$sql .= " prerequis=" . (isset($this->prerequis) ? "'" . $this->prerequis . "'" : "null") . ",";
		$sql .= " but=" . (isset($this->but) ? "'" . $this->but . "'" : "null") . ",";
		$sql .= " programme=" . (isset($this->programme) ? "'" . $this->programme . "'" : "null") . ",";
		$sql .= " pedago_usage=" . (isset($this->pedago_usage) ? "'" . $this->pedago_usage . "'" : "null") . ",";
		$sql .= " sanction=" . (isset($this->sanction) ? "'" . $this->sanction . "'" : "null") . ",";
		$sql .= " note1=" . (isset($this->note1) ? "'" . $this->note1 . "'" : "null") . ",";
		$sql .= " note2=" . (isset($this->note2) ? "'" . $this->note2 . "'" : "null") . ",";
		$sql .= " fk_user_mod=" . $user->id . ",";
		$sql .= " archive=" . $this->archive . ",";
		$sql .= " fk_product=" . (! empty($this->fk_product) ? $this->fk_product : "null") . ",";
		$sql .= " nb_subscribe_min=" . (! empty($this->nb_subscribe_min) ? $this->nb_subscribe_min : "null") . ",";
		$sql .= " fk_c_category=" . (! empty($this->fk_c_category) ? $this->fk_c_category : "null") . ",";
		$sql .= " fk_c_category_bpf=" . (! empty($this->fk_c_category_bpf) ? $this->fk_c_category_bpf : "null") . ",";
		$sql .= " certif_duration=" . (! empty($this->certif_duration) ? "'" . $this->certif_duration . "'" : "null") . ",";
		$sql .= " color=" . (! empty($this->color) ? "'" . $this->color . "'" : "null"). ",";
		$sql .= " qr_code_info=" . (! empty($this->qr_code_info) ? "'" . $this->qr_code_info . "'" : "null");
		$sql .= " WHERE rowid = " . $this->id;

		$this->db->begin();

		dol_syslog(get_class($this) . "::update ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// For avoid conflicts if trigger used
		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error ++;
			}
		}

		if (! $error && ! $notrigger)
		{
			// Call trigger
			$result=$this->call_trigger('AGEFODD_FORMATION_CATALOGUE_UPDATE',$user);
			if ($result < 0) { $error++; }
			// End call triggers
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
	 * @param int $id to delete
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int if KO, >0 if OK
	 */
	public function remove($id, $notrigger = 0) {
		global $conf, $user;
		
		$error = 0;

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_formation_catalogue";
		$sql .= " WHERE rowid = " . $id;

		dol_syslog(get_class($this) . "::remove ", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Removed extrafields
		if (! $error) {
			// For avoid conflicts if trigger used
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
				$this->id = $id;
				$result = $this->deleteExtraFields();
				if ($result < 0) {
					$error ++;
					dol_syslog(get_class($this) . "::delete erreur " . $error . " " . $this->error, LOG_ERR);
				}
			}
		}

		if (! $error && ! $notrigger)
		{
			// Call trigger
			$result=$this->call_trigger('AGEFODD_FORMATION_CATALOGUE_DELETE',$user);
			if ($result < 0) { $error++; }
			// End call triggers
		}

		if (! $error) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return - 1;
		}
	}

	/**
	 * Create pegagogic goal
	 *
	 * @param User $user that delete
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function create_objpeda($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		$this->intitule = $this->db->escape($this->intitule);

		// Check parameters
		// Put here code to add control on parameters value

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_formation_objectifs_peda(";
		$sql .= "fk_formation_catalogue, intitule, priorite, fk_user_author,fk_user_mod,datec";
		$sql .= ") VALUES (";
		$sql .= " " . $this->fk_formation_catalogue . ', ';
		$sql .= "'" . $this->db->escape($this->intitule) . "', ";
		$sql .= " " . $this->db->escape($this->priorite) . ", ";
		$sql .= " " . $user->id . ',';
		$sql .= " " . $user->id . ',';
		$sql .= "'" . $this->db->idate(dol_now()) . "'";
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}
		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_formation_objectifs_peda");
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
	 * @param int $id of object
	 * @return int if KO, >0 if OK
	 */
	public function fetch_objpeda($id) {
		$sql = "SELECT";
		$sql .= " o.intitule, o.priorite, o.fk_formation_catalogue";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formation_objectifs_peda";
		$sql .= " as o";
		$sql .= " WHERE o.rowid = " . $id;

		dol_syslog(get_class($this) . "::fetch ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $id;
				$this->fk_formation_catalogue = $obj->fk_formation_catalogue;
				$this->intitule = stripslashes($obj->intitule);
				$this->priorite = $obj->priorite;
			} else return 0;
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
	 * @param int $id_formation concern by objectif peda
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_objpeda_per_formation($id_formation) {
		$sql = "SELECT";
		$sql .= " o.rowid, o.intitule, o.priorite, o.fk_formation_catalogue, o.tms, o.fk_user_author";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formation_objectifs_peda AS o";
		$sql .= " WHERE o.fk_formation_catalogue = " . $id_formation;
		$sql .= " ORDER BY o.priorite ASC";

		dol_syslog(get_class($this) . "::fetch_objpeda_per_formation ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new AgfObjPedaLine();

				$line->id = $obj->rowid;
				$line->fk_formation_catalogue = $obj->fk_formation_catalogue;
				$line->intitule = stripslashes($obj->intitule);
				$line->priorite = $obj->priorite;

				$this->lines[$i] = $line;

				$i ++;
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_objpeda_per_formation " . $this->error, LOG_ERR);
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
	public function update_objpeda($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		$this->intitule = $this->db->escape(trim($this->intitule));

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_formation_objectifs_peda SET";
		$sql .= " fk_formation_catalogue=" . $this->fk_formation_catalogue . ",";
		$sql .= " intitule='" . $this->intitule . "',";
		$sql .= " fk_user_mod=" . $user->id . ",";
		$sql .= " priorite=" . $this->priorite . " ";
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
	 * @param int $id to delete
	 * @return int if KO, >0 if OK
	 */
	public function remove_objpeda($id) {
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_formation_objectifs_peda";
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
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen() {
		$this->id = 0;
		$this->ref = '';
		$this->intitule = '';
		$this->duree = '';
		$this->public = '';
		$this->methode = '';
		$this->prerequis = '';
		$this->programme = '';
		$this->pedago_usage = '';
		$this->sanction = '';
		$this->archive = '';
	}

	/**
	 * Return description of training
	 *
	 * @return string translated description
	 */
	public function getToolTip() {
		global $langs;

		$langs->load("admin");
		$langs->load("agefodd@agefodd");
		
		$s  = '<b>' . $langs->trans("AgfTraining") . '</b>:<u>' . $this->intitule . ':</u><br>';
		$s .= '<br>';
		$s .= $langs->trans("AgfDuree") . ' : ' . $this->duree . ' H <br>';
		$s .= $langs->trans("AgfPublic") . ' : ' . $this->public . '<br>';
		$s .= $langs->trans("AgfMethode") . ' : ' . $this->methode . '<br>';

		$s .= '<br>';

		return $s;
	}

	/**
	 * Load object in memory from database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit offset limit
	 * @param int $offset offset limit
	 * @param int $arch archive
	 * @param array $filter array of filter where clause
	 * @param array $array_options_keys extrafields to fetch
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_all($sortorder, $sortfield, $limit, $offset, $arch = 0, $filter = array(), $array_options_keys=array()) {
		if (empty($array_options_keys)) {
			require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
			$extrafields = new ExtraFields($this->db);
			$extrafields->fetch_name_optionals_label($this->table_element);
			if (is_array($extrafields->attributes[$this->table_element]['label'])) {
				$array_options_keys = array_keys($extrafields->attributes[$this->table_element]['label']);
			}
		}

		$sql = "SELECT c.rowid, c.entity, c.intitule, c.ref_interne, c.ref, c.datec, c.duree,c.nb_place, c.fk_product, c.nb_subscribe_min, dictcat.code as catcode ,dictcat.intitule as catlib, ";
		$sql .= "dictcatbpf.code as catcodebpf ,dictcatbpf.intitule as catlibbpf,";
		$sql .= " (SELECT MAX(sess1.datef) FROM " . MAIN_DB_PREFIX . "agefodd_session as sess1 WHERE sess1.fk_formation_catalogue=c.rowid AND sess1.status IN (4,5)) as lastsession,";
		$sql .= " (SELECT count(rowid) FROM " . MAIN_DB_PREFIX . "agefodd_session as sess WHERE sess.fk_formation_catalogue=c.rowid AND sess.status IN (4,5)) as nbsession";
		if (is_array($array_options_keys) && count($array_options_keys) > 0) {
			foreach ($array_options_keys as $key) {
				$sql .= ', ef.' . $key;
			}
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session as a";
		$sql .= " ON c.rowid = a.fk_formation_catalogue";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_type as dictcat";
		$sql .= " ON dictcat.rowid = c.fk_c_category";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_type_bpf as dictcatbpf";
		$sql .= " ON dictcatbpf.rowid = c.fk_c_category_bpf";

		$add_extrafield_link = true;
		foreach ( $filter as $key => $value ) {
			if (strpos($key, 'ef.') !== false) {
				$add_extrafield_link = false;
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_extrafields as ef";
				$sql .= " ON c.rowid = ef.fk_object";
				break;
			}
		}

		if ($add_extrafield_link)
		{
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'agefodd_formation_catalogue_extrafields as ef ON (c.rowid = ef.fk_object)';
		}

		$sql .= " WHERE c.archive = " . $arch;
		$sql .= " AND c.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";
		// Manage filter
		if (! empty($filter)) {
			foreach ( $filter as $key => $value ) {
				// To allow $filter['YEAR(s.dated)']=>$year
				if ($key == 'c.datec') {
					$sql .= ' AND DATE_FORMAT(' . $key . ',\'%Y-%m-%d\') = \'' . dol_print_date($value, '%Y-%m-%d') . '\'';
				} elseif ($key == 'c.duree' || $key == 'c.fk_c_category' || $key == 'c.fk_c_category_bpf') {
					$sql .= ' AND ' . $key . ' = ' . $value;
				} elseif (strpos($key,'ef.')!==false){
					$sql.= $value;
				} else {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $value . '%\'';
				}
			}
		}

		$sql .= " GROUP BY c.ref,c.ref_interne,c.rowid, dictcat.code, dictcat.intitule, dictcatbpf.code, dictcatbpf.intitule";
		foreach ($array_options_keys as $key)
		{
			$sql.= ',ef.'.$key;
		}
		if (! empty($sortfield)) {
			$sql .= ' ORDER BY ' . $sortfield . ' ' . $sortorder;
		}
		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::fetch_all ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);

					$line = new AgfTrainingLine();

					$line->rowid = $obj->rowid;
					$line->entity = $obj->entity;
					$line->intitule = $obj->intitule;
					$line->ref = $obj->ref;
					$line->ref_interne = $obj->ref_interne;
					$line->datec = $this->db->jdate($obj->datec);
					$line->duree = $obj->duree;
					$line->nb_place = $obj->nb_place;
					$line->lastsession = $obj->lastsession;
					$line->nbsession = $obj->nbsession;
					$line->fk_product = $obj->fk_product;
					$line->nb_subscribe_min = $obj->nb_subscribe_min;
					$line->category_lib = $obj->catcode . ' - ' . $obj->catlib;
					$line->category_lib_bpf = $obj->catcodebpf . ' - ' . $obj->catlibbpf;

					// Formatage comme du Dolibarr standard pour ne pas être perdu
					$line->array_options = array();
					if (is_array($array_options_keys) && count($array_options_keys) > 0) {
						foreach ($array_options_keys as $key) {
							$line->array_options['options_' . $key] = $obj->{$key};
						}
					}

					$this->lines[$i] = $line;

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
	 * Create admin level for a session
	 *
	 * @param $user User
	 * @return int <0 if KO, >0 if OK
	 */
	public function createAdmLevelForTraining($user) {
		$error = '';

		require_once ('agefodd_sessadm.class.php');
		require_once ('agefodd_session_admlevel.class.php');
		require_once ('agefodd_training_admlevel.class.php');
		require_once (DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");
		$admlevel = new Agefodd_session_admlevel($this->db);
		$result2 = $admlevel->fetch_all();

		if ($result2 > 0) {
			foreach ( $admlevel->lines as $line ) {
				$actions = new Agefodd_training_admlevel($this->db);

				$actions->fk_agefodd_training_admlevel = $line->rowid;
				$actions->fk_training = $this->id;
				$actions->delais_alerte = $line->alerte;
				$actions->delais_alerte_end = $line->alerte_end;
				$actions->intitule = $line->intitule;
				$actions->indice = $line->indice;
				$actions->level_rank = $line->level_rank;
				$actions->fk_parent_level = $line->fk_parent_level; // Treatement to calculate the new parent level is after
				$actions->trigger_name = $line->trigger_name;
				$result3 = $actions->create($user);

				if ($result3 < 0) {
					dol_syslog(get_class($this) . "::createAdmLevelForTraining error=" . $actions->error, LOG_ERR);
					$this->error = $actions->error;
					$error ++;
				}
			}

			// Caculate the new parent level
			$action_static = new Agefodd_training_admlevel($this->db);
			$result4 = $action_static->setParentActionId($user, $this->id);
			if ($result4 < 0) {
				dol_syslog(get_class($this) . "::createAdmLevelForTraining error=" . $action_static->error, LOG_ERR);
				$this->error = $action_static->error;
				$error ++;
			}
		} else {
			dol_syslog(get_class($this) . "::createAdmLevelForTraining error=" . $admlevel->error, LOG_ERR);
			$this->error = $admlevel->error;
			$error ++;
		}

		return $error;
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param int $fromid of object to clone
	 * @return int id of clone
	 */
	public function createFromClone($fromid) {
		global $user, $conf;

		$error = 0;

		$object = new Formation($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetch($fromid);
		if ($result < 0) {
			$this->error = $object->error;
			$error ++;
		}

		$defaultref = '';
		$obj = empty($conf->global->AGF_ADDON) ? 'mod_agefodd_simple' : $conf->global->AGF_ADDON;
		$path_rel = dol_buildpath('/agefodd/core/modules/agefodd/' . $conf->global->AGF_ADDON . '.php');
		if (! empty($conf->global->AGF_ADDON) && is_readable($path_rel)) {
			dol_include_once('/agefodd/core/modules/agefodd/' . $conf->global->AGF_ADDON . '.php');
			$modAgefodd = new $obj();
			$defaultref = $modAgefodd->getNextValue(null, $this);
		}

		if (is_numeric($defaultref) && $defaultref <= 0)
			$defaultref = '';

		$object->ref_obj = $defaultref;

		// Create clone
		$result = $object->create($user);
		// Other options
		if ($result < 0) {
			$this->errors[] = $object->error;
			$error ++;
		}

		$newid = $object->id;

		$result = $object->createAdmLevelForTraining($user);
		// Other options
		if ($result < 0) {
			$this->errors[] = $object->error;
			$error ++;
		}

		$source = new Formation($this->db);
		$result_peda = $source->fetch_objpeda_per_formation($fromid);
		if ($result_peda < 0) {
			$this->errors[] = $source->error;
			$error ++;
		}
		foreach ( $source->lines as $line ) {

			$object->intitule = $line->intitule;
			$object->priorite = $line->priorite;
			$object->fk_formation_catalogue = $newid;

			$result_peda = $object->create_objpeda($user);
			if ($result_peda < 0) {
				$this->errors[] = $object->error;
				$error ++;
			}
		}

		if ($conf->global->AGF_FILTER_TRAINER_TRAINING) {
			$source->id = $fromid;
			$result_trainer = $source->fetchTrainer();
			if ($result_trainer < 0) {
				$this->errors[] = $source->error;
				$error ++;
			}
			$trainer_array = array();
			foreach ( $source->trainers as $trainer ) {
				$trainer_array[$trainer->id] = $trainer->id;
			}
			$object->id = $newid;
			$result_trainer = $object->setTrainingTrainer($trainer_array, $user);
			if ($result_trainer < 0) {
				$this->errors[] = $object->error;
				$error ++;
			}
		}

		// End
		if (empty($error)) {
			$this->db->commit();
			return $newid;
		} else {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::createFromClone " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1;
		}
	}

	/**
	 *
	 * @param string $label
	 * @return string
	 */
	public function getNomUrl($label = 'all') {
		$link = dol_buildpath('/agefodd/training/card.php', 1);
		if ($label == 'all') {
			return '<a href="' . $link . '?id=' . $this->id . '">' . $this->ref . ((! empty($this->ref_interne)) ? ' (' . $this->ref_interne . ') ' : ' ') . $this->intitule . '</a>';
		} else {
			return '<a href="' . $link . '?id=' . $this->id . '">' . $this->$label . '</a>';
		}
	}


	/**
	 *
	 * @param array $trainers
	 * @param User $user
	 * @return number
	 */
	public function setTrainingTrainer($trainers, $user) {
		$error = 0;

		$this->db->begin();

		$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'agefodd_formateur_training WHERE fk_training=' . $this->id;

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . " ERROR :" . $this->error, LOG_ERR);
			$error ++;
		}
		if (empty($error) && count($trainers) > 0) {
			foreach ( $trainers as $key => $trainerid ) {
				$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'agefodd_formateur_training(fk_trainer,fk_training,fk_user_author,datec,fk_user_mod,tms) ';
				$sql .= ' VALUES (' . $trainerid . ',' . $this->id . ','.$user->id.',\''.$this->db->idate(dol_now()).'\','.$user->id.',\''.$this->db->idate(dol_now()).'\')';

				dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql) {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::" . __METHOD__ . " ERROR :" . $this->error, LOG_ERR);
					$error ++;
				}
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::" . __METHOD__ . $errmsg, LOG_ERR);
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
	 * @return number
	 */
	public function fetchTrainer() {
		require_once 'agefodd_formateur.class.php';
		
		$error = 0;

		$sql = 'SELECT link.rowid as linkid, f.rowid as fk_trainer ';
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formateur as f";
		$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_formateur_training as link';
		$sql .= ' ON f.rowid=link.fk_trainer AND link.fk_training=' . $this->id;
		$sql .= " WHERE f.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";
		$this->trainers = array();
		// $line->fk_socpeople
		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ( $obj = $this->db->fetch_object($resql) ) {
				$trainer = new Agefodd_teacher($this->db);
				$result = $trainer->fetch($obj->fk_trainer);
				if ($result < 0) {
					$this->errors[] = "Error " . $this->db->lasterror();
					$error ++;
				}
				$this->trainers[] = $trainer;
			}
		} else {
			$this->errors[] = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			$error ++;
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


	/*
	 * Function to generate pdf program by link
	 */
	function generatePDAByLink(){
		global $conf;
		require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
        $link = new Link($this->db);
        $links = array();
		$link->fetchAll($links, $this->element, $this->id);

		if(!empty($links)){
			foreach ($links as $link)
			{
				if($link->label=="PRG"){
					$fopen = fopen($link->url, 'r');
					if ($fopen !== false) {
						file_put_contents($conf->agefodd->dir_output . '/' . 'fiche_pedago_' . $this->id . '.pdf', $fopen);
					}
					return 1;
				}
			}
		}
		return 0;
	}

	/**
     *  Update note of element
     *
     *  @param      string		$note		New value for note
     *  @param		string		$suffix		'', '_public' or '_private'
     *  @return     int      		   		<0 if KO, >0 if OK
     */
    function update_note($note,$suffix='')
    {

        global $user;
    	if (! $this->table_element)
    	{
    		dol_syslog(get_class($this)."::update_note was called on objet with property table_element not defined", LOG_ERR);
    		return -1;
    	}
		if (! in_array($suffix,array('','_public','_private')))
		{
    		dol_syslog(get_class($this)."::update_note Parameter suffix must be empty, '_private' or '_public'", LOG_ERR);
			return -2;
		}
        // Special cas
        //var_dump($this->table_element);exit;
		if ($this->table_element == 'product') $suffix='';
    	$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
    	$sql.= " SET note".$suffix." = ".(!empty($note)?("'".$this->db->escape($note)."'"):"NULL");
    	$sql.= " ,fk_user_mod = ".$user->id;
    	$sql.= " WHERE rowid =". $this->id;
    	dol_syslog(get_class($this)."::update_note", LOG_DEBUG);
    	if ($this->db->query($sql))
    	{
    		if ($suffix == '_public') $this->note_public = $note;
    		else if ($suffix == '_private') $this->note_private = $note;
    		else
    		{
    		    $this->note = $note;      // deprecated
    		    $this->note_private = $note;
    		}
    		return 1;
    	}
    	else
    	{
    		$this->error=$this->db->lasterror();
    		return -1;
    	}
    }

    function getLibStatut($mode = 0){
        global $langs;

        if($this->archive){
            $picto = 'statut5';
            $statut = $langs->trans("AgfCatArchivee");
        } else {
            $picto = 'statut4';
            $statut = $langs->trans("AgfCatActive");
        }

        switch ($mode){
            case 0 :
                return $statut;
                break;
            case 1 :
                return $statut . "&nbsp;" . img_picto('', $picto);
                break;
            default:
                return $statut . "&nbsp;" . img_picto('', $picto);
        }
    }
}
class AgfObjPedaLine {
	public $id;
	public $fk_formation_catalogue;
	public $intitule;
	public $priorite;
	public function __construct() {
		return 1;
	}
}
class AgfTrainingLine {
	public $rowid;
	public $entity;
	public $intitule;
	public $ref_interne;
	public $ref;
	public $datec;
	public $duree;
	public $nb_place;
	public $lastsession;
	public $nbsession;
	public $fk_product;
	public $nb_subscribe_min;
	public $category_lib;
	public $category_lib_bpf;
	public $array_options = array();
	public function __construct() {
		return 1;
	}

	/**
	 *
	 * @param string $label
	 * @return string
	 */
	public function getNomUrl($label = 'all') {
		$link = dol_buildpath('/agefodd/training/card.php', 1);
		if ($label == 'all') {
			return '<a href="' . $link . '?id=' . $this->rowid . '">' . $this->ref . ((! empty($this->ref_interne)) ? ' (' . $this->ref_interne . ') ' : ' ') . $this->intitule . '</a>';
		} else {
			return '<a href="' . $link . '?id=' . $this->rowid . '">' . $this->$label . '</a>';
		}
	}


}
