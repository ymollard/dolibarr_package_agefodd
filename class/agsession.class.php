<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2016 Florian Henry <florian.henry@open-concept.pro>
 * Copyright (C) 2012		JF FERRY	<jfefe@aternatik.fr>
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
 * \file agefodd/class/agsession.class.php
 * \ingroup agefodd
 * \brief Manage Session object
 */
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 * Session Class
 */
class Agsession extends CommonObject
{
	public $error;
	public $errors = array ();
	public $element = 'agefodd_agsession';
	public $table_element = 'agefodd_session';
	protected $ismultientitymanaged = 1; // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	public $id;
	public $fk_soc;
	public $client;
	public $socid;
	public $fk_formation_catalogue;
	public $fk_session_place;
	public $nb_place;
	public $nb_stagiaire;
	public $force_nb_stagiaire;
	public $type_session; // type formation entreprise : 0 intra / 1 inter
	public $dated = '';
	public $datef = '';
	public $notes;
	public $color;
	public $cost_trainer;
	public $cost_site;
	public $cost_trip;
	public $sell_price;
	public $invoice_amount;
	public $cost_buy_charges;
	public $cost_sell_charges;
	public $date_res_site = '';
	public $is_date_res_site;
	public $date_res_confirm_site = '';
	public $is_date_res_confirm_site;
	public $date_res_trainer = '';
	public $is_date_res_trainer;
	public $date_ask_OPCA = '';
	public $is_date_ask_OPCA;
	public $is_OPCA;
	public $fk_soc_OPCA;
	public $soc_OPCA_name;
	public $fk_socpeople_OPCA;
	public $contact_name_OPCA;
	public $OPCA_contact_adress;
	public $OPCA_adress;
	public $num_OPCA_soc;
	public $num_OPCA_file;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $tms = '';
	public $lines = array ();
	public $commercialid;
	public $commercialname;
	public $contactid;
	public $contactname;
	public $sourcecontactid;
	public $fk_actioncomm;
	public $fk_product;
	public $formintitule;
	public $formid;
	public $formref;
	public $duree;
	public $nb_subscribe_min;
	public $status;
	public $statuscode;
	public $statuslib;
	public $contactcivilite;
	public $duree_session;
	public $intitule_custo;
	public $placecode;
	public $placeid;
	public $commercialemail;
	public $commercialphone;
	public $fk_soc_requester;
	public $fk_socpeople_requester;
	public $socname;
	public $fk_session_trainee;
	public $avgpricedesc;
	public $fk_soc_presta;
	public $fk_socpeople_presta;
	public $fk_soc_employer;
	public $formrefint;

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
		require_once ('agefodd_formation_catalogue.class.php');

		require_once (DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php");

		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->fk_formation_catalogue))
			$this->fk_formation_catalogue = trim($this->fk_formation_catalogue);
		if (isset($this->fk_session_place))
			$this->fk_session_place = trim($this->fk_session_place);
		if (isset($this->fk_soc))
			$this->fk_soc = trim($this->fk_soc);
		if ($this->fk_soc == - 1)
			unset($this->fk_soc);
		if (isset($this->nb_place))
			$this->nb_place = trim($this->nb_place);
		if (isset($this->notes))
			$this->notes = trim($this->notes);
		if (isset($this->status))
			$this->status = trim($this->status);
		if (empty($this->status))
			$this->status = $conf->global->AGF_DEFAULT_SESSION_STATUS;

			// Check parameters
			// Put here code to add control on parameters values
		if (empty($this->nb_place))
			$this->nb_place = 0;

			// find the nb_subscribe_min of training to set it into session
		$training = new Agefodd($this->db);
		$training->fetch($this->fk_formation_catalogue);
		$this->nb_subscribe_min = $training->nb_subscribe_min;
		if (empty($this->duree_session)) {
			$this->duree_session = $training->duree;
		}
		if (empty($this->intitule_custo)) {
			$this->intitule_custo = $training->intitule;
		}
		if (empty($this->fk_product)) {
			$this->fk_product = $training->fk_product;
		}

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_session(";
		$sql .= "fk_soc,";
		$sql .= "fk_soc_requester,";
		$sql .= "fk_socpeople_requester,";
		$sql .= "fk_socpeople_presta,";
		$sql .= "fk_soc_employer,";
		$sql .= "fk_formation_catalogue,";
		$sql .= "fk_session_place,";
		$sql .= "nb_place,";
		$sql .= "type_session,";
		$sql .= "dated,";
		$sql .= "datef,";
		$sql .= "notes,";
		$sql .= "nb_subscribe_min,";
		$sql .= "fk_user_author,";
		$sql .= "datec,";
		$sql .= "fk_user_mod,";
		$sql .= "entity,";
		$sql .= "fk_product,";
		$sql .= "status,";
		$sql .= "duree_session,";
		$sql .= "intitule_custo";
		$sql .= ") VALUES (";
		$sql .= " " . (! isset($this->fk_soc) ? 'NULL' : "'" . $this->fk_soc . "'") . ",";
		$sql .= " " . (! isset($this->fk_soc_requester) ? 'NULL' : "'" . $this->fk_soc_requester . "'") . ",";
		$sql .= " " . (empty($this->fk_socpeople_requester) ? 'NULL' : "'" . $this->fk_socpeople_requester . "'") . ",";
		$sql .= " " . (empty($this->fk_socpeople_presta) ? 'NULL' : "'" . $this->fk_socpeople_presta . "'") . ",";
		$sql .= " " . (empty($this->fk_soc_employer) ? 'NULL' : "'" . $this->fk_soc_employer . "'") . ",";
		$sql .= " " . (! isset($this->fk_formation_catalogue) ? 'NULL' : "'" . $this->fk_formation_catalogue . "'") . ",";
		$sql .= " " . (! isset($this->fk_session_place) ? 'NULL' : "'" . $this->fk_session_place . "'") . ",";
		$sql .= " " . (! isset($this->nb_place) ? 'NULL' : $this->nb_place) . ",";
		$sql .= " " . (! isset($this->type_session) ? '0' : "'" . $this->type_session . "'") . ",";
		$sql .= " " . (! isset($this->dated) || dol_strlen($this->dated) == 0 ? 'NULL' : "'" . $this->db->idate($this->dated) . "'") . ",";
		$sql .= " " . (! isset($this->datef) || dol_strlen($this->datef) == 0 ? 'NULL' : "'" . $this->db->idate($this->datef) . "'") . ",";
		$sql .= " " . (! isset($this->notes) ? 'NULL' : "'" . $this->db->escape($this->notes) . "'") . ",";
		$sql .= " " . (! isset($this->nb_subscribe_min) ? 'NULL' : $this->nb_subscribe_min) . ",";
		$sql .= " " . $this->db->escape($user->id) . ",";
		$sql .= " '" . $this->db->idate(dol_now()) . "',";
		$sql .= " " . $this->db->escape($user->id) . ",";
		$sql .= " " . $conf->entity . ",";
		$sql .= " " . (empty($this->fk_product) ? 'NULL' : $this->fk_product) . ",";
		$sql .= " " . (! isset($this->status) ? 'NULL' : "'" . $this->db->escape($this->status) . "'") . ",";
		$sql .= " " . (empty($this->duree_session) ? '0' : price2num($this->duree_session)) . ",";
		$sql .= " " . (! isset($this->intitule_custo) ? 'NULL' : "'" . $this->db->escape($this->intitule_custo) . "'") . "";
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_session");
			// Create or update line in session commercial table and get line number
			if (! empty($this->commercialid)) {
				$result = $this->setCommercialSession($this->commercialid, $user);
				if ($result <= 0) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			}

			// Create or update line in session contact table and get line number
			/*
			 * if ($conf->global->AGF_CONTACT_DOL_SESSION)	{ $contactid = $this->sourcecontactid; } else { $contactid = $this->contactid; }
			 */
			$contactid = $this->contactid;
			if ($contactid) {
				$result = $this->setContactSession($contactid, $user);
				if ($result <= 0) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
			}

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

			// For avoid conflicts if trigger used
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {

				// Fill session extrafields with customer extrafield if they are the same
				if (! empty($this->fk_soc)) {
					$soc = new Societe($this->db);
					$soc->fetch($this->fk_soc);
					if (! empty($soc->id)) {
						foreach ( $this->array_options as $key => $value ) {
							// If same extrafeild exists into customer=> Transfert it to session and value is not fill yet
							if (is_array($soc->array_options) && array_key_exists($key, $soc->array_options) && (! empty($soc->array_options[$key])) && (empty($this->array_options[$key]))) {
								$this->array_options[$key] = $soc->array_options[$key];
							}
						}
					}
				}

				if (! empty($this->fk_formation_catalogue)) {
					$training = new Agefodd($this->db);
					$training->fetch($this->fk_formation_catalogue);
					if (! empty($training->id)) {
						foreach ( $this->array_options as $key => $value ) {
							// If same extrafeild exists into customer=> Transfert it to session and value is not fill yet
							if (is_array($training->array_options) && array_key_exists($key, $training->array_options) && (! empty($training->array_options[$key])) && (empty($this->array_options[$key]))) {
								$this->array_options[$key] = $training->array_options[$key];
							}
						}
					}
				}

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
	 * Load an object from its id and create a new one in database
	 *
	 * @param int $fromid of object to clone
	 * @return int id of clone
	 */
	public function createFromClone($fromid) {
		global $user, $langs;

		$error = 0;

		$object = new Agsession($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		if (empty($conf->global->AGF_CONTACT_DOL_SESSION)) {
			$object->contactid = $object->sourcecontactid;
		}
		$object->id = 0;
		$object->statut = 0;
		$object->nb_stagiaire = 0;

		// Create clone
		$result = $object->create($user);

		$result = $object->createAdmLevelForSession($user);

		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$error ++;
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
	 * Create admin level for a session
	 */
	public function createAdmLevelForSession($user) {
		$error = '';

		require_once ('agefodd_sessadm.class.php');
		require_once (DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php");
		require_once ('agefodd_training_admlevel.class.php');
		$admlevel = new Agefodd_training_admlevel($this->db);
		$result2 = $admlevel->fetch_all($this->fk_formation_catalogue);

		if ($result2 > 0 && ! empty($this->dated)) {
			foreach ( $admlevel->lines as $line ) {
				$actions = new Agefodd_sessadm($this->db);

				if (!empty($line->alerte)) {
					$actions->datea = dol_time_plus_duree($this->dated, $line->alerte, 'd');
				}
				if (!empty($line->alerte_end)) {
					$actions->datea = dol_time_plus_duree($this->datef, $line->alerte_end, 'd');
				}
				if (dol_strlen($actions->dated) == 0) {
					$actions->datea=$this->dated;
				}
				$actions->dated = dol_time_plus_duree($actions->datea, - 7, 'd');

				if ($actions->datea > $this->datef) {
					$actions->datef = dol_time_plus_duree($actions->datea, 7, 'd');
				} else {
					$actions->datef = $this->datef;
				}

				$actions->fk_agefodd_session_admlevel = $line->rowid;
				$actions->fk_agefodd_session = $this->id;
				$actions->delais_alerte = $line->alerte;
				$actions->delais_alerte_end = $line->alerte_end;
				$actions->intitule = $line->intitule;
				$actions->indice = $line->indice;
				$actions->archive = 0;
				$actions->level_rank = $line->level_rank;
				$actions->fk_parent_level = $line->fk_parent_level; // Treatement to calculate the new parent level is after
				$actions->trigger_name = $line->trigger_name;
				$result3 = $actions->create($user);

				if ($result3 < 0) {
					dol_syslog(get_class($this) . "::createAdmLevelForSession error=" . $actions->error, LOG_ERR);
					$this->error = $actions->error;
					$error ++;
				}
			}

			// Caculate the new parent level
			$action_static = new Agefodd_sessadm($this->db);
			$result4 = $action_static->setParentActionId($user, $this->id);
			if ($result4 < 0) {
				dol_syslog(get_class($this) . "::createAdmLevelForSession error=" . $action_static->error, LOG_ERR);
				$this->error = $action_static->error;
				$error ++;
			}
		} elseif ($result2 < 0) {
			dol_syslog(get_class($this) . "::createAdmLevelForSession error=" . $admlevel->error, LOG_ERR);
			$this->error = $admlevel->error;
			$error ++;
		}

		return $error;
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id) {
		global $langs, $conf;

		$sql = "SELECT DISTINCT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_soc,";
		$sql .= " t.fk_soc_requester,";
		$sql .= " t.fk_socpeople_requester,";
		$sql .= " t.fk_socpeople_presta,";
		$sql .= " t.fk_soc_employer,";
		$sql .= " t.fk_formation_catalogue,";
		$sql .= " c.intitule as formintitule,";
		$sql .= " c.rowid as formid,";
		$sql .= " c.ref as formref,";
		$sql .= " c.ref_interne as formrefint,";
		$sql .= " c.duree,";
		$sql .= " t.fk_session_place,";
		$sql .= " t.nb_place,";
		$sql .= " t.nb_stagiaire,";
		$sql .= " t.force_nb_stagiaire,";
		$sql .= " t.type_session,";
		$sql .= " t.dated,";
		$sql .= " t.datef,";
		$sql .= " t.notes,";
		$sql .= " t.nb_subscribe_min,";
		$sql .= " t.color,";
		$sql .= " t.cost_trainer,";
		$sql .= " t.cost_site,";
		$sql .= " t.cost_trip,";
		$sql .= " t.sell_price,";
		$sql .= " t.invoice_amount,";
		$sql .= " t.cost_buy_charges,";
		$sql .= " t.cost_sell_charges,";
		$sql .= " t.date_res_site,";
		$sql .= " t.is_date_res_site,";
		$sql .= " t.date_res_confirm_site,";
		$sql .= " t.is_date_res_confirm_site,";
		$sql .= " t.date_res_trainer,";
		$sql .= " t.is_date_res_trainer,";
		$sql .= " t.date_ask_OPCA as date_ask_opca,";
		$sql .= " t.is_date_ask_OPCA as is_date_ask_opca,";
		$sql .= " t.is_OPCA as is_opca,";
		$sql .= " t.fk_soc_OPCA as fk_soc_opca,";
		$sql .= " t.fk_socpeople_OPCA as fk_socpeople_opca,";
		$sql .= " concactOPCA.lastname as concact_opca_name, concactOPCA.firstname as concact_opca_firstname,";
		$sql .= " t.num_OPCA_soc as num_opca_soc,";
		$sql .= " t.num_OPCA_file as num_opca_file,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms,";
		$sql .= " t.fk_product,";
		$sql .= " t.duree_session,";
		$sql .= " t.intitule_custo,";
		$sql .= " t.status,dictstatus.intitule as statuslib, dictstatus.code as statuscode,";
		$sql .= " p.rowid as placeid, p.ref_interne as placecode,";
		$sql .= " us.lastname as commercialname, us.firstname as commercialfirstname, ";
		$sql .= " us.email as commercialemail, ";
		$sql .= " us.office_phone as commercialphone, ";
		$sql .= " com.fk_user_com as commercialid, ";
		$sql .= " socp.lastname as contactname, socp.firstname as contactfirstname, socp.civility as contactcivilite,";
		$sql .= " agecont.fk_socpeople as sourcecontactid, ";
		$sql .= " agecont.rowid as contactid, ";
		$sql .= " socOPCA.address as opca_adress, socOPCA.zip as opca_cp, socOPCA.town as opca_ville, ";
		$sql .= " socOPCA.nom as soc_opca_name, ";
		$sql .= " concactOPCA.address as opca_contact_adress, concactOPCA.zip as opca_contact_cp, concactOPCA.town as opca_contact_ville ";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as t";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " ON c.rowid = t.fk_formation_catalogue";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
		$sql .= " ON p.rowid = t.fk_session_place";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON ss.fk_session_agefodd = c.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_commercial as com";
		$sql .= " ON com.fk_session_agefodd = t.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as us";
		$sql .= " ON com.fk_user_com = us.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_contact as scont";
		$sql .= " ON scont.fk_session_agefodd = t.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_contact as agecont";
		$sql .= " ON agecont.rowid = scont.fk_agefodd_contact";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socp ";
		$sql .= " ON agecont.fk_socpeople = socp.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as socOPCA ";
		$sql .= " ON t.fk_soc_OPCA = socOPCA.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as socEmployer ";
		$sql .= " ON t.fk_soc_employer = socEmployer.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as concactOPCA ";
		$sql .= " ON t.fk_socpeople_OPCA = concactOPCA.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as concactpresta ";
		$sql .= " ON t.fk_socpeople_presta = concactpresta.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as dictstatus";
		$sql .= " ON t.status = dictstatus.rowid";
		$sql .= " WHERE t.rowid = " . $id;
		$sql .= " AND t.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->ref = $obj->rowid; // Use for next prev ref
				$this->fk_soc = $obj->fk_soc; // don't work with fetch_thirdparty()
				$this->socid = $obj->fk_soc; // work with fetch_thirdparty()
				$this->fk_soc_requester = $obj->fk_soc_requester;
				$this->fk_socpeople_requester = $obj->fk_socpeople_requester;
				$this->fk_socpeople_presta = $obj->fk_socpeople_presta;
				$this->fk_soc_employer = $obj->fk_soc_employer;
				$this->fk_formation_catalogue = $obj->fk_formation_catalogue;
				$this->formintitule = $obj->formintitule;
				$this->formid = $obj->formid;
				$this->formref = $obj->formref;
				$this->formrefint = $obj->formrefint;
				$this->duree = $obj->duree;
				$this->fk_product = $obj->fk_product;
				$this->fk_session_place = $obj->fk_session_place;
				$this->nb_place = $obj->nb_place;
				$this->nb_stagiaire = $obj->nb_stagiaire;
				$this->force_nb_stagiaire = $obj->force_nb_stagiaire;
				$this->type_session = $obj->type_session;
				$this->placeid = $obj->placeid;
				$this->placecode = $obj->placecode;
				$this->dated = $this->db->jdate($obj->dated);
				$this->datef = $this->db->jdate($obj->datef);
				$this->notes = $obj->notes;
				$this->nb_subscribe_min = $obj->nb_subscribe_min;
				$this->color = $obj->color;
				$this->cost_trainer = $obj->cost_trainer;
				$this->cost_site = $obj->cost_site;
				$this->cost_trip = $obj->cost_trip;
				$this->sell_price = $obj->sell_price;
				$this->invoice_amount = $obj->invoice_amount;
				$this->cost_buy_charges = $obj->cost_buy_charges;
				$this->cost_sell_charges = $obj->cost_sell_charges;
				$this->date_res_site = $this->db->jdate($obj->date_res_site);
				$this->is_date_res_site = $obj->is_date_res_site;
				$this->date_res_confirm_site = $this->db->jdate($obj->date_res_confirm_site);
				$this->is_date_res_confirm_site = $obj->is_date_res_confirm_site;
				$this->date_res_trainer = $this->db->jdate($obj->date_res_trainer);
				$this->is_date_res_trainer = $obj->is_date_res_trainer;
				$this->date_ask_OPCA = $this->db->jdate($obj->date_ask_opca);
				$this->is_date_ask_OPCA = $obj->is_date_ask_opca;
				$this->is_OPCA = $obj->is_opca;
				$this->fk_soc_OPCA = $obj->fk_soc_opca;
				$this->soc_OPCA_name = $obj->soc_opca_name;
				if (($conf->global->AGF_LINK_OPCA_ADRR_TO_CONTACT) && (! empty($obj->opca_contact_adress))) {
					$this->OPCA_adress = $obj->opca_contact_adress . "\n" . $obj->opca_contact_cp . ' - ' . $obj->opca_contact_ville;
				} else {
					$this->OPCA_adress = $obj->opca_adress . "\n" . $obj->opca_cp . ' - ' . $obj->opca_ville;
				}
				$this->fk_socpeople_OPCA = $obj->fk_socpeople_opca;
				$this->contact_name_OPCA = $obj->concact_opca_name . ' ' . $obj->concact_opca_firstname;
				$this->num_OPCA_soc = $obj->num_opca_soc;
				$this->num_OPCA_file = $obj->num_opca_file;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);
				$this->commercialname = $obj->commercialname . ' ' . $obj->commercialfirstname;
				$this->commercialemail = $obj->commercialemail;
				$this->commercialphone = $obj->commercialphone;
				$this->commercialid = $obj->commercialid;
				$this->contactname = $obj->contactname . ' ' . $obj->contactfirstname;
				$this->contactcivilite = $obj->contactcivilite;
				$this->sourcecontactid = $obj->sourcecontactid;
				$this->contactid = $obj->contactid;
				$this->status = $obj->status;
				$this->statuscode = $obj->statuscode;
				if ($obj->statuslib == $langs->trans('AgfStatusSession_' . $obj->statuscode)) {
					$label = stripslashes($obj->statuslib);
				} else {
					$label = $langs->trans('AgfStatusSession_' . $obj->statuscode);
				}
				$this->statuslib = $label;
				$this->intitule_custo = $obj->intitule_custo;
				$this->duree_session = $obj->duree_session;
			}
			$this->db->free($resql);

			require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
			$extrafields = new ExtraFields($this->db);
			$extralabels = $extrafields->fetch_name_optionals_label($this->table_element, true);
			if (count($extralabels) > 0) {
				$this->fetch_optionals($this->id, $extralabels);
			}

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object (all trainee for one session) in memory from database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_session_per_trainee($id) {
		global $langs;

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
		$sql .= " s.status,";
		$sql .= " ss.status_in_session";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " ON c.rowid = s.fk_formation_catalogue";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
		$sql .= " ON sa.rowid = ss.fk_stagiaire";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_civility as civ";
		$sql .= " ON civ.code = sa.civilite";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = s.fk_soc";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sope";
		$sql .= " ON sope.rowid = sa.fk_socpeople";
		$sql .= " WHERE sa.rowid = " . $id;
		if (! empty($socid))
			$sql .= " AND so.rowid = " . $socid;
		$sql .= " ORDER BY sa.nom";

		dol_syslog(get_class($this) . "::fetch_session_per_trainee", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->line = array ();
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new AgfSessionLine();

				$line->rowid = $obj->sessid;
				$line->socid = $obj->socid;
				$line->status = $obj->status;
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

				$this->lines[$i] = $line;

				$i ++;
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_session_per_trainee " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object (all trainee for one session) in memory from database
	 *
	 * @param int $id object
	 * @param string $sortorder order
	 * @param string $sortfield field
	 * @param int $limit page
	 * @param int $offset
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_session_per_trainer($id, $sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = array()) {
		global $langs;

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
		$sql .= " s.status,";
		$sql .= " sf.trainer_status,";
		$sql .= " sf.rowid as trainersessionid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
		$sql .= " ON s.rowid = sf.fk_session";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " ON c.rowid = s.fk_formation_catalogue";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as trainer";
		$sql .= " ON trainer.rowid = sf.fk_agefodd_formateur";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = s.fk_soc";

		if (is_array($filter)) {
			if (key_exists('sale.fk_user_com', $filter)) {
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_commercial as sale";
				$sql .= " ON s.rowid = sale.fk_session_agefodd";
			}
		}

		$sql .= " WHERE trainer.rowid = " . $id;

		// Manage filter
		if (count($filter) > 0) {
			foreach ( $filter as $key => $value ) {
				if (($key == 'YEAR(s.dated)') || ($key == 'MONTH(s.dated)')) {
					$sql .= ' AND ' . $key . ' IN (' . $value . ')';
				} elseif (($key == 's.rowid') || ($key == 'sf.trainer_status') || ($key == 'sale.fk_user_com')) {
					$sql .= ' AND ' . $key . ' = ' . $value;
				} elseif ($key == '!s.status') {
					$sql .= ' AND s.status <> ' . $value;
				} elseif ($key == 'so.nom') {
					// Search for all thirdparty concern by the session
					$sql .= ' AND ((' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\') OR (s.rowid IN (SELECT innersess.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session as innersess ';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire as inserss ON innersess.rowid = inserss.fk_session_agefodd';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire as insersta ON insersta.rowid = inserss.fk_stagiaire ';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe as insersoc ON insersoc.rowid = insersta.fk_soc ';
					$sql .= ' WHERE insersoc.nom LIKE \'%' . $this->db->escape($value) . '%\' )))';
				} else {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}

		if (! empty($sortfield)) {
			$sql .= " ORDER BY " . $sortfield . ' ' . $sortorder;
		} else {
		}

		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::fetch_session_per_trainer", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->line = array ();
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new AgfSessionLine();

				$line->rowid = $obj->sessid;
				$line->socid = $obj->socid;
				$line->status = $obj->status;
				$line->socname = $obj->socname;
				$line->type_session = $obj->type_session;
				$line->fk_session_place = $obj->fk_session_place;
				$line->dated = $this->db->jdate($obj->dated);
				$line->datef = $this->db->jdate($obj->datef);
				$line->intitule = $obj->intitule;
				$line->ref = $obj->ref;
				$line->ref_interne = $obj->ref_interne;
				$line->color = $obj->color;
				$line->trainer_status = $obj->trainer_status;
				$line->trainersessionid = $obj->trainersessionid;

				$this->lines[$i] = $line;

				$i ++;
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_session_per_trainer " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object (company per session) in memory from database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_societe_per_session($id) {
		$error = 0;
		global $langs;

		$array_soc = array ();

		// Soc trainee
		$sql = "SELECT";
		$sql .= " DISTINCT so.rowid as socid,";
		$sql .= " s.rowid, s.type_session, s.is_OPCA as is_opca, s.fk_soc_OPCA as fk_soc_opca, so.nom as socname, so.code_client ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
		$sql .= " ON sa.rowid = ss.fk_stagiaire";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = sa.fk_soc";
		$sql .= " WHERE s.rowid = " . $id;
		$sql .= " AND so.rowid IS NOT NULL";
		$sql .= " ORDER BY socname";

		dol_syslog(get_class($this) . "::fetch_societe_per_session SocTrainee", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->line = array ();
			$num = $this->db->num_rows($resql);

			if ($num) {
				while ( $obj = $this->db->fetch_object($resql) ) {

					$newline = new AgfSocLine();

					$newline->sessid = $obj->rowid;
					$newline->socname = $obj->socname;
					$newline->code_client = $obj->code_client;
					$newline->socid = $obj->socid;
					$newline->type_session = $obj->type_session;
					$newline->is_OPCA = $obj->is_opca;
					$newline->fk_soc_OPCA = $obj->fk_soc_opca;
					$newline->typeline = 'trainee_soc';

					$sql_inner = "SELECT";
					$sql_inner .= " DISTINCT sa.rowid, sa.nom, sa.prenom ";
					$sql_inner .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
					$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
					$sql_inner .= " ON s.rowid = ss.fk_session_agefodd";
					$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
					$sql_inner .= " ON sa.rowid = ss.fk_stagiaire";
					$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so";
					$sql_inner .= " ON so.rowid = sa.fk_soc AND sa.fk_soc=" . $obj->socid;
					$sql_inner .= " WHERE s.rowid = " . $id;
					dol_syslog(get_class($this) . "::fetch_societe_per_session SocTrainee sql_inner", LOG_DEBUG);
					$resql_inner = $this->db->query($sql_inner);
					$array_trainnee = array ();
					if ($resql_inner) {
						$num_inner = $this->db->num_rows($resql_inner);

						if ($num_inner) {
							while ( $obj_inner = $this->db->fetch_object($resql_inner) ) {
								$array_trainnee[] = array (
										'id' => $obj_inner->rowid,
										'lastname' => $obj_inner->prenom,
										'firstname' => $obj_inner->nom
								);
							}
						}
					} else {
						$this->error = "Error " . $this->db->lasterror();
						dol_syslog(get_class($this) . "::fetch_societe_per_session " . $this->error, LOG_ERR);
						$error ++;
					}
					$newline->trainee_array = $array_trainnee;

					$array_soc[] = $obj->socid;

					$this->lines[] = $newline;
				}
			}

			$this->db->free($resql);
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_societe_per_session " . $this->error, LOG_ERR);
			$error ++;
		}

		// Get OPCA Soc
		$sql = "SELECT";
		$sql .= " DISTINCT so.rowid as socid,";
		$sql .= " s.rowid, s.type_session, s.is_OPCA as is_opca, s.fk_soc_OPCA as fk_soc_opca, so.nom as socname, so.code_client ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = s.fk_soc_OPCA";
		$sql .= " WHERE s.rowid = " . $id;
		$sql .= " ORDER BY socname";

		dol_syslog(get_class($this) . "::fetch_societe_per_session OPCA", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$add_soc = 0;
			$num_other = $this->db->num_rows($resql);

			if ($num_other) {
				$i = 0;
				while ( $i < $num_other ) {
					$obj = $this->db->fetch_object($resql);
					if (! empty($obj->fk_soc_opca)) {
						if (! in_array($obj->socid, $array_soc)) {
							$newline = new AgfSocLine();

							$newline->sessid = $obj->rowid;
							$newline->socname = $obj->socname;
							$newline->socid = $obj->socid;
							$newline->code_client = $obj->code_client;
							$newline->type_session = $obj->type_session;
							$newline->is_OPCA = $obj->is_opca;
							$newline->fk_soc_OPCA = $obj->fk_soc_opca;
							$newline->typeline = 'OPCA';

							$array_soc[] = $obj->socid;

							$sql_inner = "SELECT";
							$sql_inner .= " DISTINCT sa.rowid, sa.nom, sa.prenom ";
							$sql_inner .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
							$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
							$sql_inner .= " ON s.rowid = ss.fk_session_agefodd";
							$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
							$sql_inner .= " ON sa.rowid = ss.fk_stagiaire";
							$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so";
							$sql_inner .= " ON so.rowid = s.fk_soc_OPCA";
							$sql_inner .= " WHERE s.rowid = " . $id;
							dol_syslog(get_class($this) . "::fetch_societe_per_session SocTrainee sql_inner", LOG_DEBUG);
							$resql_inner = $this->db->query($sql_inner);
							$array_trainnee = array ();
							if ($resql_inner) {
								$num_inner = $this->db->num_rows($resql_inner);

								if ($num_inner) {
									while ( $obj_inner = $this->db->fetch_object($resql_inner) ) {
										$array_trainnee[] = array (
												'id' => $obj_inner->rowid,
												'lastname' => $obj_inner->prenom,
												'firstname' => $obj_inner->nom
										);
									}
								}
							} else {
								$this->error = "Error " . $this->db->lasterror();
								dol_syslog(get_class($this) . "::fetch_societe_per_session " . $this->error, LOG_ERR);
								$error ++;
							}
							$newline->trainee_array = $array_trainnee;

							$this->lines[] = $newline;
						}
					}
					$i ++;
				}
			}

			$this->db->free($resql);
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_societe_per_session OPCA " . $this->error, LOG_ERR);
			$error ++;
		}

		// Get OPCA Soc of trainee
		$sql = "SELECT";
		$sql .= " DISTINCT soOPCATrainee.rowid as socid,";
		$sql .= " s.rowid, s.type_session, s.is_OPCA as is_opca, s.fk_soc_OPCA as fk_soc_opca, soOPCATrainee.nom as socname, soOPCATrainee.code_client ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
		$sql .= " ON sa.rowid = ss.fk_stagiaire";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = sa.fk_soc";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_opca AS soOPCA ON soOPCA.fk_soc_trainee = so.rowid ";
		$sql .= " AND soOPCA.fk_session_agefodd = s.rowid ";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as soOPCATrainee";
		$sql .= " ON soOPCATrainee.rowid = soOPCA.fk_soc_OPCA";
		$sql .= " WHERE s.rowid = " . $id;
		$sql .= " AND soOPCATrainee.rowid IS NOT NULL";
		$sql .= " ORDER BY socname";

		dol_syslog(get_class($this) . "::fetch_societe_per_session OPCAtrainee", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$add_soc = 0;
			$num_other = $this->db->num_rows($resql);

			if ($num_other) {
				$i = 0;
				while ( $i < $num_other ) {
					$obj = $this->db->fetch_object($resql);

					if (! empty($obj->socid)) {
						if (! in_array($obj->socid, $array_soc)) {
							$newline = new AgfSocLine();
							$newline->sessid = $obj->rowid;
							$newline->socname = $obj->socname;
							$newline->socid = $obj->socid;
							$newline->code_client = $obj->code_client;
							$newline->type_session = $obj->type_session;
							$newline->is_OPCA = $obj->is_opca;
							$newline->fk_soc_OPCA = $obj->fk_soc_opca;

							$newline->typeline = 'trainee_OPCA';
							if (! empty($obj->socid)) {
								$sql_inner = "SELECT";
								$sql_inner .= " DISTINCT sa.rowid, sa.nom, sa.prenom ";
								$sql_inner .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
								$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
								$sql_inner .= " ON s.rowid = ss.fk_session_agefodd";
								$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
								$sql_inner .= " ON sa.rowid = ss.fk_stagiaire";
								$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so";
								$sql_inner .= " ON so.rowid = sa.fk_soc";
								$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_opca AS soOPCA ON soOPCA.fk_soc_trainee = so.rowid ";
								$sql_inner .= " AND soOPCA.fk_session_agefodd = s.rowid ";
								$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as soOPCATrainee";
								$sql_inner .= " ON soOPCATrainee.rowid = soOPCA.fk_soc_OPCA AND soOPCA.fk_soc_OPCA=" . $obj->socid;
								$sql_inner .= " WHERE s.rowid = " . $id;
								dol_syslog(get_class($this) . "::fetch_societe_per_session OPCAtrainee sql_inner", LOG_DEBUG);
								$resql_inner = $this->db->query($sql_inner);
								$array_trainnee = array ();
								if ($resql_inner) {
									$num_inner = $this->db->num_rows($resql_inner);

									if ($num_inner) {
										while ( $obj_inner = $this->db->fetch_object($resql_inner) ) {
											$array_trainnee[] = array (
													'id' => $obj_inner->rowid,
													'lastname' => $obj_inner->prenom,
													'firstname' => $obj_inner->nom
											);
										}
									}
								} else {
									$this->error = "Error " . $this->db->lasterror();
									dol_syslog(get_class($this) . "::fetch_societe_per_session " . $this->error, LOG_ERR);
									$error ++;
								}
								$newline->trainee_array = $array_trainnee;
							}

							$array_soc[] = $obj->socid;

							$this->lines[] = $newline;
						}
					}
					$i ++;
				}
			}

			$this->db->free($resql);
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_societe_per_session OPCAtrainee " . $this->error, LOG_ERR);
			$error ++;
		}

		// Get session customer
		$sql = "SELECT";
		$sql .= " DISTINCT s.fk_soc as socid,";
		$sql .= " s.rowid, s.type_session, s.is_OPCA as is_opca, s.fk_soc_OPCA as fk_soc_opca , so.nom as socname, so.code_client ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = s.fk_soc";
		$sql .= " WHERE s.rowid = " . $id;
		$sql .= " ORDER BY socname";

		dol_syslog(get_class($this) . "::fetch_societe_per_session Customer", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$add_soc = 0;
			$num_other = $this->db->num_rows($resql);

			if ($num_other) {
				$i = 0;
				while ( $i < $num_other ) {
					$obj = $this->db->fetch_object($resql);
					if (! empty($obj->socid)) {
						if (! in_array($obj->socid, $array_soc)) {
							$newline = new AgfSocLine();
							$newline->sessid = $obj->rowid;
							$newline->socname = $obj->socname;
							$newline->socid = $obj->socid;
							$newline->code_client = $obj->code_client;
							$newline->type_session = $obj->type_session;
							$newline->is_OPCA = $obj->is_opca;
							$newline->fk_soc_OPCA = $obj->fk_soc_opca;

							$newline->typeline = 'customer';

							if (! empty($obj->socid)) {
								$sql_inner = "SELECT";
								$sql_inner .= " DISTINCT sa.rowid, sa.nom, sa.prenom ";
								$sql_inner .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
								$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
								$sql_inner .= " ON s.rowid = ss.fk_session_agefodd";
								$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
								$sql_inner .= " ON sa.rowid = ss.fk_stagiaire";
								$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so";
								$sql_inner .= " ON so.rowid = s.fk_soc AND so.rowid=" . $obj->socid;
								$sql_inner .= " WHERE s.rowid = " . $id;
								dol_syslog(get_class($this) . "::fetch_societe_per_session Customer sql_inner", LOG_DEBUG);
								$resql_inner = $this->db->query($sql_inner);
								$array_trainnee = array ();
								if ($resql_inner) {
									$num_inner = $this->db->num_rows($resql_inner);

									if ($num_inner) {
										while ( $obj_inner = $this->db->fetch_object($resql_inner) ) {
											$array_trainnee[] = array (
													'id' => $obj_inner->rowid,
													'lastname' => $obj_inner->prenom,
													'firstname' => $obj_inner->nom
											);
										}
									}
								} else {
									$this->error = "Error " . $this->db->lasterror();
									dol_syslog(get_class($this) . "::fetch_societe_per_session " . $this->error, LOG_ERR);
									$error ++;
								}
								$newline->trainee_array = $array_trainnee;
							}

							$array_soc[] = $obj->socid;

							$this->lines[] = $newline;
						}
					}
					$i ++;
				}
			}

			$this->db->free($resql);
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_societe_per_session Customer " . $this->error, LOG_ERR);
			$error ++;
		}

		// Get session Trainee USe for doc
		$sql = "SELECT";
		$sql .= " DISTINCT so.rowid as socid,";
		$sql .= " s.rowid, s.type_session, s.is_OPCA as is_opca, s.fk_soc_OPCA as fk_soc_opca, so.nom as socname, so.code_client ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
		$sql .= " ON sa.rowid = ss.fk_stagiaire";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = ss.fk_soc_link";
		$sql .= " WHERE s.rowid = " . $id;
		$sql .= " ORDER BY socname";

		dol_syslog(get_class($this) . "::fetch_societe_per_session SocTraineeForDoc", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->line = array ();
			$num = $this->db->num_rows($resql);

			if ($num) {
				$i = 0;
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);
					if (! empty($obj->socid)) {
						if (! in_array($obj->socid, $array_soc)) {
							$newline = new AgfSocLine();

							$newline->sessid = $obj->rowid;
							$newline->socname = $obj->socname;
							$newline->code_client = $obj->code_client;
							$newline->socid = $obj->socid;
							$newline->type_session = $obj->type_session;
							$newline->is_OPCA = $obj->is_opca;
							$newline->fk_soc_OPCA = $obj->fk_soc_opca;

							$newline->typeline = 'trainee_doc';

							$array_soc[] = $obj->socid;

							if (! empty($obj->socid)) {
								$sql_inner = "SELECT";
								$sql_inner .= " DISTINCT sa.rowid, sa.nom, sa.prenom ";
								$sql_inner .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
								$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
								$sql_inner .= " ON s.rowid = ss.fk_session_agefodd";
								$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
								$sql_inner .= " ON sa.rowid = ss.fk_stagiaire";
								$sql_inner .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so";
								$sql_inner .= " ON so.rowid = ss.fk_soc_link AND ss.fk_soc_link=" . $obj->socid;
								$sql_inner .= " WHERE s.rowid = " . $id;
								dol_syslog(get_class($this) . "::fetch_societe_per_session SocTraineeForDoc sql_inner", LOG_DEBUG);
								$resql_inner = $this->db->query($sql_inner);
								$array_trainnee = array ();
								if ($resql_inner) {
									$num_inner = $this->db->num_rows($resql_inner);

									if ($num_inner) {
										while ( $obj_inner = $this->db->fetch_object($resql_inner) ) {
											$array_trainnee[] = array (
													'id' => $obj_inner->rowid,
													'lastname' => $obj_inner->prenom,
													'firstname' => $obj_inner->nom
											);
										}
									}
								} else {
									$this->error = "Error " . $this->db->lasterror();
									dol_syslog(get_class($this) . "::fetch_societe_per_session " . $this->error, LOG_ERR);
									$error ++;
								}
								$newline->trainee_array = $array_trainnee;
							}

							$this->lines[] = $newline;
						}
					}
					$i ++;
				}
			}

			$this->db->free($resql);
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_societe_per_session " . $this->error, LOG_ERR);
			$error ++;
		}

		// Get session requester trainee
		$sql = "SELECT";
		$sql .= " DISTINCT so.rowid as socid,";
		$sql .= " s.rowid, s.type_session, s.is_OPCA as is_opca, s.fk_soc_OPCA as fk_soc_opca, so.nom as socname, so.code_client ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
		$sql .= " ON sa.rowid = ss.fk_stagiaire";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = ss.fk_soc_requester";
		$sql .= " WHERE s.rowid = " . $id;
		$sql .= " ORDER BY socname";

		dol_syslog(get_class($this) . "::fetch_societe_per_session SessionRequester", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->line = array ();
			$num = $this->db->num_rows($resql);

			if ($num) {
				$i = 0;
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);
					if (! empty($obj->socid)) {
						if (! in_array($obj->socid, $array_soc)) {
							$newline = new AgfSocLine();

							$newline->sessid = $obj->rowid;
							$newline->socname = $obj->socname;
							$newline->code_client = $obj->code_client;
							$newline->socid = $obj->socid;
							$newline->type_session = $obj->type_session;
							$newline->is_OPCA = $obj->is_opca;
							$newline->fk_soc_OPCA = $obj->fk_soc_opca;

							$newline->typeline = 'trainee_requester';

							$array_soc[] = $obj->socid;

							if (! empty($obj->socid)) {
								$sql_inner = "SELECT";
								$sql_inner .= " DISTINCT sa.rowid, sa.nom, sa.prenom ";
								$sql_inner .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
								$sql_inner .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
								$sql_inner .= " ON s.rowid = ss.fk_session_agefodd";
								$sql_inner .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
								$sql_inner .= " ON sa.rowid = ss.fk_stagiaire";
								$sql_inner .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
								$sql_inner .= " ON so.rowid = ss.fk_soc_requester AND ss.fk_soc_requester=" . $obj->socid;
								$sql_inner .= " WHERE s.rowid = " . $id;
								dol_syslog(get_class($this) . "::fetch_societe_per_session SessionRequester sql_inner", LOG_DEBUG);
								$resql_inner = $this->db->query($sql_inner);
								$array_trainnee = array ();
								if ($resql_inner) {
									$num_inner = $this->db->num_rows($resql_inner);

									if ($num_inner) {
										while ( $obj_inner = $this->db->fetch_object($resql_inner) ) {
											$array_trainnee[] = array (
													'id' => $obj_inner->rowid,
													'lastname' => $obj_inner->prenom,
													'firstname' => $obj_inner->nom
											);
										}
									}
								} else {
									$this->error = "Error " . $this->db->lasterror();
									dol_syslog(get_class($this) . "::fetch_societe_per_session " . $this->error, LOG_ERR);
									$error ++;
								}
								$newline->trainee_array = $array_trainnee;
							}

							$this->lines[] = $newline;
						}
					}
					$i ++;
				}
			}

			$this->db->free($resql);
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_societe_per_session " . $this->error, LOG_ERR);
			$error ++;
		}

		// Get session preta trainee
		$sql = "SELECT";
		$sql .= " DISTINCT so.rowid as socid,";
		$sql .= " s.rowid, s.type_session, s.is_OPCA as is_opca, s.fk_soc_OPCA as fk_soc_opca, so.nom as socname, so.code_client ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
		$sql .= " ON sa.rowid = ss.fk_stagiaire";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socp";
		$sql .= " ON socp.rowid = s.fk_socpeople_presta";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = socp.fk_soc";
		$sql .= " WHERE s.rowid = " . $id;
		$sql .= " ORDER BY socname";

		dol_syslog(get_class($this) . "::fetch_societe_per_session Sessionpresta", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->line = array ();
			$num = $this->db->num_rows($resql);

			if ($num) {
				$i = 0;
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);
					if (! empty($obj->socid)) {
						if (! in_array($obj->socid, $array_soc)) {
							$newline = new AgfSocLine();

							$newline->sessid = $obj->rowid;
							$newline->socname = $obj->socname;
							$newline->code_client = $obj->code_client;
							$newline->socid = $obj->socid;
							$newline->type_session = $obj->type_session;
							$newline->is_OPCA = $obj->is_opca;
							$newline->fk_soc_OPCA = $obj->fk_soc_opca;

							$newline->typeline = 'trainee_presta';

							$array_soc[] = $obj->socid;

							if (! empty($obj->socid)) {
								$sql_inner = "SELECT";
								$sql_inner .= " DISTINCT sa.rowid, sa.nom, sa.prenom ";
								$sql_inner .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
								$sql_inner .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
								$sql_inner .= " ON s.rowid = ss.fk_session_agefodd";
								$sql_inner .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
								$sql_inner .= " ON sa.rowid = ss.fk_stagiaire";
								$sql_inner .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
								$sql_inner .= " ON so.rowid = ss.fk_soc_requester AND ss.fk_soc_requester=" . $obj->socid;
								$sql_inner .= " WHERE s.rowid = " . $id;
								dol_syslog(get_class($this) . "::fetch_societe_per_session SessionRequester sql_inner", LOG_DEBUG);
								$resql_inner = $this->db->query($sql_inner);
								$array_trainnee = array ();
								if ($resql_inner) {
									$num_inner = $this->db->num_rows($resql_inner);

									if ($num_inner) {
										while ( $obj_inner = $this->db->fetch_object($resql_inner) ) {
											$array_trainnee[] = array (
													'id' => $obj_inner->rowid,
													'lastname' => $obj_inner->prenom,
													'firstname' => $obj_inner->nom
											);
										}
									}
								} else {
									$this->error = "Error " . $this->db->lasterror();
									dol_syslog(get_class($this) . "::fetch_societe_per_session " . $this->error, LOG_ERR);
									$error ++;
								}
								$newline->trainee_array = $array_trainnee;
							}

							$this->lines[] = $newline;
						}
					}
					$i ++;
				}
			}

			$this->db->free($resql);
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_societe_per_session " . $this->error, LOG_ERR);
			$error ++;
		}

		if (! $error) {
			return count($this->lines);
		} else {
			return - 1;
		}
	}

	/**
	 * Load object (information) in memory from database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function info($id) {
		global $langs;

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
	 * Update only archive session into database
	 *
	 * @param User $user that modify
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function updateArchive($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session SET";
		$sql .= " fk_user_mod=" . $this->db->escape($user->id) . ",";
		$sql .= " status=" . (isset($this->status) ? $this->status : "1") . "";
		$sql .= " WHERE rowid=" . $this->id;

		$this->db->begin();

		dol_syslog(get_class($this) . "::updateArchive", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			dol_syslog(get_class($this) . "::updateArchive", LOG_ERR);
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
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
		require_once ('agefodd_session_stagiaire.class.php');

		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (isset($this->fk_soc))
			$this->fk_soc = trim($this->fk_soc);
		if ($this->fk_soc == - 1)
			unset($this->fk_soc);

		if (isset($this->fk_soc_requester))
			$this->fk_soc_requester = trim($this->fk_soc_requester);
		if ($this->fk_soc_requester == - 1)
			unset($this->fk_soc_requester);
		if (isset($this->fk_soc_employer))
			$this->fk_soc_employer = trim($this->fk_soc_employer);
		if ($this->fk_soc_employer == - 1)
			unset($this->fk_soc_employer);
		if (isset($this->fk_socpeople_requester))
			$this->fk_socpeople_requester = trim($this->fk_socpeople_requester);
		if (isset($this->fk_socpeople_presta))
			$this->fk_socpeople_presta = trim($this->fk_socpeople_presta);
		if (isset($this->fk_formation_catalogue))
			$this->fk_formation_catalogue = trim($this->fk_formation_catalogue);
		if (isset($this->fk_session_place))
			$this->fk_session_place = trim($this->fk_session_place);
		if (isset($this->nb_place))
			$this->nb_place = trim($this->nb_place);
		if (isset($this->nb_stagiaire))
			$this->nb_stagiaire = trim($this->nb_stagiaire);
		if (isset($this->force_nb_stagiaire))
			$this->force_nb_stagiaire = trim($this->force_nb_stagiaire);
		if (isset($this->type_session))
			$this->type_session = trim($this->type_session);
		if (isset($this->notes))
			$this->notes = trim($this->notes);
		if (isset($this->color))
			$this->color = trim($this->color);
		if (isset($this->cost_trainer))
			$this->cost_trainer = price2num(trim($this->cost_trainer));
		if (isset($this->cost_site))
			$this->cost_site = price2num(trim($this->cost_site));
		if (isset($this->cost_trip))
			$this->cost_trip = price2num(trim($this->cost_trip));
		if (isset($this->sell_price))
			$this->sell_price = price2num(trim($this->sell_price));
		if (isset($this->is_OPCA))
			$this->is_OPCA = trim($this->is_OPCA);
		if (isset($this->is_date_res_site))
			$this->is_date_res_site = trim($this->is_date_res_site);
		if (isset($this->is_date_res_trainer))
			$this->is_date_res_trainer = trim($this->is_date_res_trainer);
		if (isset($this->fk_soc_OPCA))
			$this->fk_soc_OPCA = trim($this->fk_soc_OPCA);
		if (isset($this->fk_socpeople_OPCA))
			$this->fk_socpeople_OPCA = trim($this->fk_socpeople_OPCA);
		if (isset($this->num_OPCA_soc))
			$this->num_OPCA_soc = trim($this->num_OPCA_soc);
		if (isset($this->num_OPCA_file))
			$this->num_OPCA_file = trim($this->num_OPCA_file);
		if (isset($this->fk_product))
			$this->fk_product = trim($this->fk_product);
		if (isset($this->status))
			$this->status = trim($this->status);
		if (isset($this->duree_session))
			$this->duree_session = trim($this->duree_session);
		if (isset($this->intitule_custo))
			$this->intitule_custo = trim($this->intitule_custo);

			// Create or update line in session commercial table and get line number
		$result = $this->setCommercialSession($this->commercialid, $user);
		if ($result <= 0) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Create or update line in session contact table and get line number
		if ($conf->global->AGF_CONTACT_DOL_SESSION) {
			$result = $this->setContactSession($this->sourcecontactid, $user);
		} else {
			$result = $this->setContactSession($this->contactid, $user);
		}

		if ($result <= 0) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (empty($this->force_nb_stagiaire)) {
			$session_sta = new Agefodd_session_stagiaire($this->db);
			$session_sta->fetch_stagiaire_per_session($this->id);
			$this->nb_stagiaire = count($session_sta->lines);
		}

		if ($error == 0) {
			// Check parameters
			// Put here code to add control on parameters values

			// Update request
			$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session SET";

			$sql .= " fk_soc=" . (isset($this->fk_soc) ? $this->fk_soc : "null") . ",";
			$sql .= " fk_soc_requester=" . (isset($this->fk_soc_requester) ? $this->fk_soc_requester : "null") . ",";
			$sql .= " fk_soc_employer=" . (isset($this->fk_soc_employer) ? $this->fk_soc_employer : "null") . ",";
			$sql .= " fk_socpeople_requester=" . (isset($this->fk_socpeople_requester) ? $this->fk_socpeople_requester : "null") . ",";
			$sql .= " fk_socpeople_presta=" . (isset($this->fk_socpeople_presta) ? $this->fk_socpeople_presta : "null") . ",";
			$sql .= " fk_formation_catalogue=" . (isset($this->fk_formation_catalogue) ? $this->fk_formation_catalogue : "null") . ",";
			$sql .= " fk_session_place=" . (isset($this->fk_session_place) ? $this->fk_session_place : "null") . ",";
			$sql .= " nb_place=" . (isset($this->nb_place) ? $this->nb_place : "null") . ",";
			$sql .= " nb_subscribe_min=" . (! empty($this->nb_subscribe_min) ? $this->nb_subscribe_min : "null") . ",";
			$sql .= " nb_stagiaire=" . (isset($this->nb_stagiaire) ? $this->nb_stagiaire : "null") . ",";
			$sql .= " force_nb_stagiaire=" . (isset($this->force_nb_stagiaire) ? $this->force_nb_stagiaire : "0") . ",";
			$sql .= " type_session=" . (isset($this->type_session) ? $this->type_session : "null") . ",";
			$sql .= " dated=" . (dol_strlen($this->dated) != 0 ? "'" . $this->db->idate($this->dated) . "'" : 'null') . ",";
			$sql .= " datef=" . (dol_strlen($this->datef) != 0 ? "'" . $this->db->idate($this->datef) . "'" : 'null') . ",";
			$sql .= " notes=" . (isset($this->notes) ? "'" . $this->db->escape($this->notes) . "'" : "null") . ",";
			$sql .= " color=" . (isset($this->color) ? "'" . $this->db->escape($this->color) . "'" : "null") . ",";

			$sql .= " cost_trainer=" . (isset($this->cost_trainer) ? $this->cost_trainer : "null") . ",";
			$sql .= " cost_site=" . (isset($this->cost_site) ? $this->cost_site : "null") . ",";
			$sql .= " cost_trip=" . (isset($this->cost_trip) ? $this->cost_trip : "null") . ",";
			$sql .= " sell_price=" . (isset($this->sell_price) ? $this->sell_price : "null") . ",";
			$sql .= " date_res_site=" . (dol_strlen($this->date_res_site) != 0 ? "'" . $this->db->idate($this->date_res_site) . "'" : 'null') . ",";
			$sql .= " date_res_confirm_site=" . (dol_strlen($this->date_res_confirm_site) != 0 ? "'" . $this->db->idate($this->date_res_confirm_site) . "'" : 'null') . ",";
			$sql .= " date_res_trainer=" . (dol_strlen($this->date_res_trainer) != 0 ? "'" . $this->db->idate($this->date_res_trainer) . "'" : 'null') . ",";
			$sql .= " date_ask_OPCA=" . (dol_strlen($this->date_ask_OPCA) != 0 ? "'" . $this->db->idate($this->date_ask_OPCA) . "'" : 'null') . ",";
			$sql .= " is_OPCA=" . (! empty($this->is_OPCA) ? $this->is_OPCA : "0") . ",";
			$sql .= " is_date_res_site=" . (! empty($this->is_date_res_site) ? $this->is_date_res_site : "0") . ",";
			$sql .= " is_date_res_confirm_site=" . (! empty($this->is_date_res_confirm_site) ? $this->is_date_res_confirm_site : "0") . ",";
			$sql .= " is_date_res_trainer=" . (! empty($this->is_date_res_trainer) ? $this->is_date_res_trainer : "0") . ",";
			$sql .= " is_date_ask_OPCA=" . (! empty($this->is_date_ask_OPCA) ? $this->is_date_ask_OPCA : "0") . ",";
			$sql .= " fk_soc_OPCA=" . (isset($this->fk_soc_OPCA) && $this->fk_soc_OPCA != - 1 ? $this->fk_soc_OPCA : "null") . ",";
			$sql .= " fk_socpeople_OPCA=" . (isset($this->fk_socpeople_OPCA) && $this->fk_socpeople_OPCA != 0 ? $this->fk_socpeople_OPCA : "null") . ",";
			$sql .= " num_OPCA_soc=" . (isset($this->num_OPCA_soc) ? "'" . $this->db->escape($this->num_OPCA_soc) . "'" : "null") . ",";
			$sql .= " num_OPCA_file=" . (isset($this->num_OPCA_file) ? "'" . $this->db->escape($this->num_OPCA_file) . "'" : "null") . ",";
			$sql .= " fk_user_mod=" . $this->db->escape($user->id) . ",";
			$sql .= " fk_product=" . (! empty($this->fk_product) ? $this->fk_product : "null") . ",";
			$sql .= " status=" . (isset($this->status) ? $this->status : "null") . ",";
			$sql .= " duree_session=" . (! empty($this->duree_session) ? price2num($this->duree_session) : "0") . ",";
			$sql .= " intitule_custo=" . (! empty($this->intitule_custo) ? "'" . $this->db->escape($this->intitule_custo) . "'" : "null") . "";

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
				// want this action call a trigger.

				// // Call triggers
				// include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}

		if (! $error) {
			if (! empty($conf->global->AGF_AUTO_ACT_ADMIN_UPD)) {
				if ((dol_strlen($this->date_res_site) != 0) && ($this->is_date_res_site)) {
					dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
					$admintask = new Agefodd_sessadm($this->db);
					$result = $admintask->updateByTriggerName($user, $this->id, 'AGF_ROOM_RESERVED');
					if ($result < 0) {
						$error ++;
						$this->errors[] = "Error " . $admintask->error;
					}
				}

				if ((dol_strlen($this->date_res_confirm_site) != 0) && ($this->is_date_res_confirm_site)) {
					dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
					$admintask = new Agefodd_sessadm($this->db);
					$result = $admintask->updateByTriggerName($user, $this->id, 'AGF_ROOM_CONFIRM');
					if ($result < 0) {
						$error ++;
						$this->errors[] = "Error " . $admintask->error;
					}
				}
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

		// Set all inside status to cancel
		if (! $error) {
			if ($this->status == 3) {
				$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_stagiaire SET status_in_session=6 WHERE fk_session_agefodd=" . $this->id;

				dol_syslog(get_class($this) . "::update", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}

				$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_formateur SET trainer_status=6 WHERE fk_session=" . $this->id;

				dol_syslog(get_class($this) . "::update", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
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
	 * Update object (commercial in session) into database
	 *
	 * @param int $userid User commercial to link to session
	 * @param User $user that modify
	 * @return int <0 if KO, >0 if OK
	 */
	public function setCommercialSession($userid, $user) {
		global $conf, $langs;
		$error = 0;
		$to_create = false;
		$to_update = false;
		$to_delete = false;

		if (empty($userid) || $userid == - 1) {
			$to_delete = true;
		} else {

			$sql = "SELECT com.rowid,com.fk_user_com as commercialid FROM " . MAIN_DB_PREFIX . "agefodd_session_commercial as com ";
			$sql .= " WHERE com.fk_session_agefodd=" . $this->db->escape($this->id);

			dol_syslog(get_class($this) . "::setCommercialSession", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					$obj = $this->db->fetch_object($resql);
					// metre a jour
					if ($obj->commercialid != $userid) {
						$to_update = true;
						$fk_commercial = $obj->rowid;
					} else {
						$this->commercialid = $obj->commercialid;
						$fk_commercial = $obj->rowid;
					}
				} else {
					// a crée
					$to_create = true;
				}

				$this->db->free($resql);
			} else {
				dol_syslog(get_class($this) . "::setCommercialSession " . $this->db->lasterror(), LOG_ERR);
				return - 1;
			}
		}

		if ($to_update) {

			// Update request
			$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session_commercial SET ';
			$sql .= ' fk_user_com=' . $this->db->escape($userid) . ',';
			$sql .= ' fk_user_mod=' . $this->db->escape($user->id);
			$sql .= ' WHERE rowid=' . $this->db->escape($fk_commercial);

			$this->db->begin();

			dol_syslog(get_class($this) . "::setCommercialSession update", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}

		if ($to_create) {

			// INSERT request
			$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'agefodd_session_commercial(fk_session_agefodd, fk_user_com, fk_user_author,fk_user_mod, datec)';
			$sql .= ' VALUES ( ';
			$sql .= $this->db->escape($this->id) . ',';
			$sql .= $this->db->escape($userid) . ',';
			$sql .= $this->db->escape($user->id) . ',';
			$sql .= $this->db->escape($user->id) . ',';
			$sql .= "'" . $this->db->idate(dol_now()) . "')";

			$this->db->begin();

			dol_syslog(get_class($this) . "::setCommercialSession insert", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}

		if ($to_delete) {

			// DELETE request
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_session_commercial";
			$sql .= " WHERE fk_session_agefodd = " . $this->id;

			$this->db->begin();

			dol_syslog(get_class($this) . "::setCommercialSession delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::setCommercialSession " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} elseif ($to_create || $to_update || $to_delete) {
			$this->db->commit();
			return 1;
		} else {
			return 1;
		}
	}

	/**
	 * Update object (contact in session) into database
	 *
	 * @param int $contactid User contact to link to session
	 * @param User $user that modify
	 * @return int <0 if KO, >0 if OK
	 */
	public function setContactSession($contactid, $user) {
		global $conf, $langs;
		$error = 0;
		$to_create = false;
		$to_update = false;
		$to_delete = false;

		if (empty($contactid) || $contactid == - 1) {
			$to_delete = true;
		} else {

			// Contact id can be dolibarr contactid (from llx_socpoeple) or contact of Agefodd (llx_agefodd_contact) according settings
			if ($conf->global->AGF_CONTACT_DOL_SESSION) {
				// Test if this dolibarr contact is already a Agefodd contact
				$sql = "SELECT agecont.rowid FROM " . MAIN_DB_PREFIX . "agefodd_contact as agecont ";
				$sql .= " WHERE agecont.fk_socpeople=" . $contactid;

				dol_syslog(get_class($this) . "::setContactSession", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					if ($this->db->num_rows($resql) > 0) {
						// if exists the contact id to set is the rowid of agefood contact
						$obj = $this->db->fetch_object($resql);
						$contactid = $obj->rowid;
					} else {
						// We need to create the agefodd contact
						dol_include_once('/agefodd/class/agefodd_contact.class.php');
						$contactAgefodd = new Agefodd_contact($this->db);
						$contactAgefodd->spid = $contactid;
						$result = $contactAgefodd->create($user);
						if ($result > 0) {
							$contactid = $result;
						} else {
							dol_syslog(get_class($this) . "::setContactSession Error agefodd_contact" . $contactAgefodd->error, LOG_ERR);
							$this->db->free($resql);
							return - 1;
						}
					}
				} else {
					dol_syslog(get_class($this) . "::setContactSession Error AGF_CONTACT_DOL_SESSION:" . $this->db->lasterror(), LOG_ERR);
					return - 1;
				}
			}

			$sql = "SELECT agecont.rowid,agecont.fk_agefodd_contact as contactid FROM " . MAIN_DB_PREFIX . "agefodd_session_contact as agecont ";
			$sql .= " WHERE agecont.fk_session_agefodd=" . $this->db->escape($this->id);

			dol_syslog(get_class($this) . "::setContactSession", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					$obj = $this->db->fetch_object($resql);
					// metre a jour
					if ($obj->contactid != $contactid) {
						$to_update = true;
						$fk_contact = $obj->rowid;
					} else {
						$this->contactid = $obj->contactid;
						$fk_contact = $obj->rowid;
					}
				} else {
					// a crée
					$to_create = true;
				}

				$this->db->free($resql);
			} else {
				dol_syslog(get_class($this) . "::setContactSession Error:" . $this->db->lasterror(), LOG_ERR);
				return - 1;
			}
		}

		dol_syslog(get_class($this) . "::setContactSession to_update:" . $to_update . ", to_create:" . $to_create . ", to_delete:" . $to_delete, LOG_DEBUG);

		if ($to_update) {

			// Update request
			$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session_contact SET ';
			$sql .= ' fk_agefodd_contact=' . $this->db->escape($contactid) . ',';
			$sql .= ' fk_user_mod=' . $this->db->escape($user->id);
			$sql .= ' WHERE rowid=' . $this->db->escape($fk_contact);

			$this->db->begin();

			dol_syslog(get_class($this) . "::setContactSession update", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}

		if ($to_create) {

			// INSERT request
			$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'agefodd_session_contact(fk_session_agefodd, fk_agefodd_contact, fk_user_mod, fk_user_author, datec)';
			$sql .= ' VALUES ( ';
			$sql .= $this->db->escape($this->id) . ',';
			$sql .= $this->db->escape($contactid) . ',';
			$sql .= $this->db->escape($user->id) . ',';
			$sql .= $this->db->escape($user->id) . ',';
			$sql .= "'" . $this->db->idate(dol_now()) . "')";

			$this->db->begin();

			dol_syslog(get_class($this) . "::setContactSession insert", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}

		if ($to_delete) {

			// DELETE request
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_session_contact";
			$sql .= " WHERE fk_session_agefodd = " . $this->id;

			$this->db->begin();

			dol_syslog(get_class($this) . "::setContactSession delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::setContactSession " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} elseif ($to_create || $to_update || $to_delete) {
			$this->db->commit();
			return 1;
		} else {
			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param int $id to delete
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function remove($id, $notrigger = 0) {
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

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_session";
		$sql .= " WHERE rowid = " . $id;

		dol_syslog(get_class($this) . "::remove", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			// Removed extrafields
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
{
				$this->id = $id;
				$result = $this->deleteExtraFields();
				if ($result < 0) {
					$error ++;
					dol_syslog(get_class($this) . "::delete erreur " . $error . " " . $this->error, LOG_ERR);
				}
			}
		}

		if (! $error) {
			// Delete event from agenda that are no more link to a session
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "actioncomm WHERE elementtype='agefodd_agsession' AND fk_element NOT IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "agefodd_session)";

			dol_syslog(get_class($this) . "::remove", LOG_DEBUG);
			$resql = $this->db->query($sql);

			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
		if (! $error) {
			$this->db->commit();
		} else {
			$this->db->rollback();
		}

		if (! $error) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return - 1;
		}
	}

	/**
	 * \brief Initialise object with example values
	 * \remarks id must be 0 if object instance is a specimen.
	 */
	public function initAsSpecimen() {
		$this->id = 0;
	}

	/**
	 * Return description of session
	 *
	 * @param int $type
	 * @return string translated description
	 */
	public function getToolTip($type) {
		global $conf;

		$langs->load("admin");

		$s = '';
		if (type == 'training') {
			dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');

			$agf_training = new Agefodd($db);
			$agf_training->fetch($this->formid);
			$s = $agf_training->getToolTip();
		}
		return $s;
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param string $sortorder order
	 * @param string $sortfield field
	 * @param int $limit page
	 * @param int $offset
	 * @param array $filter output
	 * @param user $user current user
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_all($sortorder, $sortfield, $limit, $offset, $filter = array(), $user = 0, $array_options_keys=array()) {
		global $langs, $conf;

		$sql = "SELECT s.rowid, s.fk_soc, s.fk_session_place, s.type_session, s.dated, s.datef, s.status, dictstatus.intitule as statuslib, dictstatus.code as statuscode, ";
		$sql .= " s.is_date_res_site, s.is_date_res_trainer, s.date_res_trainer, s.color, ";
		$sql .= " s.force_nb_stagiaire, s.nb_stagiaire,s.notes,";
		$sql .= " c.intitule, c.ref,c.ref_interne as trainingrefinterne,s.nb_subscribe_min,";
		$sql .= " p.ref_interne";
		$sql .= " ,so.nom as socname";
		$sql .= " ,f.rowid as trainerrowid";
		$sql .= " ,s.intitule_custo";
		$sql .= " ,s.fk_soc_employer";
		$sql .= " ,s.duree_session";
		$sql .= " ,socp.rowid as contactid";
		$sql .= " ,s.sell_price";
		$sql .= " ,s.invoice_amount";
		$sql .= " ,s.datec";
		$sql .= " ,s.cost_trainer";
		$sql .= " ,s.cost_site";
		$sql .= " ,s.cost_trip";
		$sql .= " ,s.cost_sell_charges";
		$sql .= " ,s.cost_buy_charges";
		$sql .= " ,s.fk_product";
		$sql .= " ,s.fk_soc_requester";
		$sql .= " ,s.fk_socpeople_requester";
		$sql .= " ,s.fk_socpeople_presta";
		$sql .= " ,sa.archive as closesessionstatus";
		$sql .= " ,sorequester.nom as socrequestername";
		$sql .= " ,c.color as trainingcolor,";
		// Avoid perf problem with too many trainnee into archive sessions
		if (is_array($filter) && key_exists('s.status', $filter) && $filter['s.status'] == '4') {
			$sql .= " 0 as nb_prospect,";
			$sql .= " 0 as nb_confirm,";
			$sql .= " 0 as nb_cancelled";
		} else {
			$sql .= " (SELECT count(rowid) FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire WHERE (status_in_session=0 OR status_in_session IS NULL) AND fk_session_agefodd=s.rowid) as nb_prospect,";
			$sql .= " (SELECT count(rowid) FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire WHERE (status_in_session=2 OR status_in_session=1 OR status_in_session=3) AND fk_session_agefodd=s.rowid) as nb_confirm,";
			$sql .= " (SELECT count(rowid) FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire WHERE status_in_session=6 AND fk_session_agefodd=s.rowid) as nb_cancelled";
		}

		foreach ($array_options_keys as $key)
		{
			$sql.= ',ef.'.$key;
		}

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " ON c.rowid = s.fk_formation_catalogue";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
		$sql .= " ON p.rowid = s.fk_session_place";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as sa";
		$sql .= " ON s.rowid = sa.fk_agefodd_session AND sa.trigger_name='AGF_SESSION_CLOSE'";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = s.fk_soc";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as sorequester";
		$sql .= " ON sorequester.rowid = s.fk_soc_requester";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
		$sql .= " ON sf.fk_session = s.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f";
		$sql .= " ON f.rowid = sf.fk_agefodd_formateur";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as dictstatus";
		$sql .= " ON s.status = dictstatus.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_contact as sessioncontact";
		$sql .= " ON s.rowid = sessioncontact.fk_session_agefodd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_contact as agefoddcontact";
		$sql .= " ON agefoddcontact.rowid = sessioncontact.fk_agefodd_contact";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socp";
		$sql .= " ON socp.rowid = agefoddcontact.fk_socpeople";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socppresta";
		$sql .= " ON socppresta.rowid = s.fk_socpeople_presta";

		$add_extrafield_link = true;
		if (is_array($filter)) {
			foreach ( $filter as $key => $value ) {
				if (strpos($key, 'ef.') !== false) {
					$add_extrafield_link = false;
					$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_extrafields as ef";
					$sql .= " ON s.rowid = ef.fk_object";
					break;
				}
			}

			if (key_exists('sale.fk_user_com', $filter)) {
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_commercial as sale";
				$sql .= " ON s.rowid = sale.fk_session_agefodd";
			}
		}

		if ($add_extrafield_link)
		{
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'agefodd_session_extrafields as ef ON (s.rowid = ef.fk_object)';
		}

		$sql .= " WHERE s.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";

		if (is_object($user) && ! empty($user->id) && empty($user->rights->agefodd->session->all) && empty($user->admin)) {
			// Saleman of session is current user
			$sql .= 'AND (s.rowid IN (SELECT rightsession.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session as rightsession, ';
			$sql .= MAIN_DB_PREFIX . 'agefodd_session_commercial as rightsalesman WHERE rightsession.rowid=rightsalesman.fk_session_agefodd AND rightsalesman.fk_user_com=' . $user->id . ')';
			$sql .= " OR ";
			// current user is saleman of customersession
			$sql .= ' (s.fk_soc IN (SELECT ' . MAIN_DB_PREFIX . 'societe_commerciaux.fk_soc FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux WHERE fk_user=' . $user->id . ')))';
		}

		// Manage filter
		if (count($filter) > 0) {
			foreach ( $filter as $key => $value ) {
				if (($key == 'YEAR(s.dated)') || ($key == 'MONTH(s.dated)')) {
					$sql .= ' AND ' . $key . ' IN (' . $value . ')';
				} elseif ($key == 's.dated>') {
					if ($this->db->type == 'pgsql') {
						$intervalday = "'" . $value . " DAYS'";
					} else {
						$intervalday = $value . ' DAY';
					}
					$sql .= ' AND s.dated>= DATE_ADD(NOW(), INTERVAL -' . $intervalday . ')';
				} elseif (strpos($key, 'date')) { // To allow $filter['YEAR(s.dated)']=>$year
					$sql .= ' AND ' . $key . ' = \'' . $value . '\'';
				} elseif (($key == 's.fk_session_place') || ($key == 'f.rowid') || ($key == 's.type_session') || ($key == 's.status') || ($key == 'sale.fk_user_com') || ($key == 's.rowid')) {
					$sql .= ' AND ' . $key . ' = ' . $value;
				} elseif ($key == '!s.status') {
					$sql .= ' AND s.status <> ' . $value;
				} elseif ($key == 'so.nom') {
					// Search for all thirdparty concern by the session
					$sql .= ' AND ((' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\') OR (s.rowid IN (SELECT innersess.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session as innersess ';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire as inserss ON innersess.rowid = inserss.fk_session_agefodd';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire as insersta ON insersta.rowid = inserss.fk_stagiaire ';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe as insersoc ON insersoc.rowid = insersta.fk_soc ';
					$sql .= ' WHERE insersoc.nom LIKE \'%' . $this->db->escape($value) . '%\' )))';
				} elseif ($key == 'so.parent|sorequester.parent') {

					$sql .= ' AND (';
					$sql .= '	(so.parent=' . $this->db->escape($value) . ' OR sorequester.parent=' . $this->db->escape($value);
					$sql .= ' OR so.rowid=' . $this->db->escape($value) . ' OR sorequester.rowid=' . $this->db->escape($value) . ')';
					// Parent company of trainnee into inter session
					$sql .= ' OR (  s.rowid IN (SELECT innersess.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session as innersess';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire as inserss ON innersess.rowid = inserss.fk_session_agefodd';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire as insersta ON insersta.rowid = inserss.fk_stagiaire';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe as insersoc ON insersoc.rowid = insersta.fk_soc';
					$sql .= ' WHERE insersoc.parent=' . $this->db->escape($value) . '))';
					// Parent company of trainnee soc requester
					$sql .= ' OR (  s.rowid IN (SELECT innersess.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session as innersess';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire as inserss ON innersess.rowid = inserss.fk_session_agefodd';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire as insersta ON insersta.rowid = inserss.fk_stagiaire';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe as insersoc ON insersoc.rowid = innersess.fk_soc_requester';
					$sql .= ' WHERE insersoc.parent=' . $this->db->escape($value) . ')) ';
					$sql .= ')';
				} elseif ($key == 's.rowid') {
					$sql .= ' AND ' . $key . '=' . $value;
				} elseif ($key == '!s.rowid') {
					$sql .= ' AND s.rowid NOT IN (' . $value . ')';
				}  elseif (strpos($key,'ef.')!==false){
					$sql.= $value;
				} else {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		$sql .= " GROUP BY s.rowid, s.fk_soc, s.fk_session_place, s.type_session, s.dated, s.datef,  s.status, dictstatus.intitule , dictstatus.code, s.is_date_res_site, s.is_date_res_trainer, s.date_res_trainer, s.color, s.force_nb_stagiaire, s.nb_stagiaire,s.notes,";
		$sql .= " p.ref_interne, c.intitule, c.ref,c.ref_interne, so.nom, f.rowid,socp.rowid,sa.archive,sorequester.nom,c.color";
		foreach ($array_options_keys as $key)
		{
			$sql.= ',ef.'.$key;
		}
		if (! empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}

		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::fetch_all", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array ();

			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);

					$line = new AgfSessionLine();

					$line->rowid = $obj->rowid;
					$line->socid = $obj->fk_soc;
					$line->socname = $obj->socname;
					$line->socrequesterid = $obj->fk_soc_requester;
					$line->socrequestername = $obj->socrequestername;
					$line->fk_socpeople_requester = $obj->fk_socpeople_requester;
					$line->fk_socpeople_presta = $obj->fk_socpeople_presta;
					$line->fk_soc_employer = $obj->fk_soc_employer;
					$line->trainerrowid = $obj->trainerrowid;
					$line->type_session = $obj->type_session;
					$line->is_date_res_site = $obj->is_date_res_site;
					$line->is_date_res_trainer = $obj->is_date_res_trainer;
					$line->date_res_trainer = $this->db->jdate($obj->date_res_trainer);
					$line->fk_session_place = $obj->fk_session_place;
					$line->dated = $this->db->jdate($obj->dated);
					$line->datef = $this->db->jdate($obj->datef);
					$line->intitule = $obj->intitule;
					$line->ref = $obj->ref;
					$line->training_ref_interne = $obj->trainingrefinterne;
					$line->ref_interne = $obj->ref_interne;
					$line->color = $obj->color;
					$line->nb_stagiaire = $obj->nb_stagiaire;
					$line->force_nb_stagiaire = $obj->force_nb_stagiaire;
					$line->notes = $obj->notes;
					$line->nb_subscribe_min = $obj->nb_subscribe_min;
					$line->nb_prospect = $obj->nb_prospect;
					$line->nb_confirm = $obj->nb_confirm;
					$line->nb_cancelled = $obj->nb_cancelled;
					$line->duree_session = $obj->duree_session;
					$line->intitule_custo = $obj->intitule_custo;
					$line->contactid = $obj->contactid;
					$line->sell_price = $obj->sell_price;
					$line->invoice_amount = $obj->invoice_amount;
					$line->datec = $this->db->jdate($obj->datec);
					$line->cost_trainer = $obj->cost_trainer;
					$line->cost_buy_charges = $obj->cost_buy_charges;
					$line->cost_sell_charges = $obj->cost_sell_charges;
					$line->cost_other = $obj->cost_trip + $obj->cost_site;
					$line->admin_task_close_session = $obj->closesessionstatus;
					$line->trainingcolor = $obj->trainingcolor;
					$line->fk_product = $obj->fk_product;

					if ($obj->statuslib == $langs->trans('AgfStatusSession_' . $obj->statuscode)) {
						$label = stripslashes($obj->statuslib);
					} else {
						$label = $langs->trans('AgfStatusSession_' . $obj->statuscode);
					}
					$line->status_lib = $label;

					// Formatage comme du Dolibarr standard pour ne pas être perdu
					$line->array_options = array();
					foreach ($array_options_keys as $key)
					{
						$line->array_options['options_'.$key] = $obj->{$key};
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
	 * Load all objects in memory from database
	 *
	 * @param string $sortorder order
	 * @param string $sortfield field
	 * @param int $limit page
	 * @param int $offset
	 * @param int $arch archive or not
	 * @param array $filter output
	 * @param user $user current user
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_all_with_task_state($sortorder, $sortfield, $limit, $offset, $filter = '', $user = 0) {
		global $langs;

		$interval0day = '0 DAY';
		$interval3day = '3 DAY';
		$interval8day = '8 DAY';

		if ($this->db->type == 'pgsql') {
			$interval0day = "'0 DAYS'";
			$interval3day = "'3 DAYS'";
			;
			$interval8day = "'8 DAYS'";
			;
		}

		$sql = "SELECT s.rowid, s.fk_soc, s.fk_session_place, s.type_session, s.dated, s.datef, s.status, dictstatus.intitule as statuslib, dictstatus.code as statuscode, ";
		$sql .= " s.is_date_res_site, s.is_date_res_trainer, s.date_res_trainer, s.color, ";
		$sql .= " s.force_nb_stagiaire, s.nb_stagiaire,s.notes,";
		$sql .= " c.intitule, c.ref,c.ref_interne as trainingrefinterne,s.nb_subscribe_min,";
		$sql .= " p.ref_interne";
		$sql .= " ,so.nom as socname";
		$sql .= " ,f.rowid as trainerrowid";
		$sql .= " ,s.intitule_custo";
		$sql .= " ,s.duree_session";
		$sql .= " ,s.fk_soc_requester";
		$sql .= " ,s.fk_soc_employer";
		$sql .= " ,sorequester.nom as socrequestername,";
		$sql .= " (SELECT count(rowid) FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE (datea - INTERVAL " . $interval0day . ") <= NOW() AND fk_agefodd_session=s.rowid AND rowid NOT IN (select fk_parent_level FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu) AND archive <> 1) as task0,";
		$sql .= " (SELECT count(rowid) FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE (  NOW() BETWEEN (datea - INTERVAL " . $interval3day . ") AND (datea) ) AND fk_agefodd_session=s.rowid AND rowid NOT IN (select fk_parent_level FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu)) as task1,";
		$sql .= " (SELECT count(rowid) FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE (  NOW() BETWEEN (datea - INTERVAL " . $interval8day . ") AND (datea - INTERVAL " . $interval3day . ") ) AND fk_agefodd_session=s.rowid AND rowid NOT IN (select fk_parent_level FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu)) as task2,";
		$sql .= " (SELECT count(rowid) FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE archive=0 AND fk_agefodd_session=s.rowid AND rowid NOT IN (select fk_parent_level FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu)) as task3";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " ON c.rowid = s.fk_formation_catalogue";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
		$sql .= " ON p.rowid = s.fk_session_place";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as sa";
		$sql .= " ON s.rowid = sa.fk_agefodd_session";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = s.fk_soc";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as sorequester";
		$sql .= " ON sorequester.rowid = s.fk_soc_requester";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
		$sql .= " ON sf.fk_session = s.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f";
		$sql .= " ON f.rowid = sf.fk_agefodd_formateur";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as dictstatus";
		$sql .= " ON s.status = dictstatus.rowid";

		foreach ( $filter as $key => $value ) {
			if (strpos($key, 'extra.') !== false) {
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_extrafields as extra";
				$sql .= " ON s.rowid = extra.fk_object";
				break;
			}
		}

		if (key_exists('sale.fk_user_com', $filter)) {
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_commercial as sale";
			$sql .= " ON s.rowid = sale.fk_session_agefodd";
		}

		$sql .= " WHERE s.status <> 4";
		$sql .= " AND s.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";
		$sql .= " AND (SELECT count(rowid) FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE archive=0 AND fk_agefodd_session=s.rowid)<>0";

		if (is_object($user) && ! empty($user->id) && empty($user->rights->agefodd->session->all) && empty($user->admin)) {
			// Saleman of session is current user
			$sql .= ' AND (s.rowid IN (SELECT rightsession.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session as rightsession, ';
			$sql .= MAIN_DB_PREFIX . 'agefodd_session_commercial as rightsalesman WHERE rightsession.rowid=rightsalesman.fk_session_agefodd AND rightsalesman.fk_user_com=' . $user->id . ')';
			$sql .= " OR ";
			// current user is saleman of customersession
			$sql .= ' (s.fk_soc IN (SELECT ' . MAIN_DB_PREFIX . 'societe_commerciaux.fk_soc FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux WHERE fk_user=' . $user->id . ')))';
		}

		// Manage filter
		if (! empty($filter)) {
			foreach ( $filter as $key => $value ) {
				if (strpos($key, 'date')) // To allow $filter['YEAR(s.dated)']=>$year
{
					$sql .= ' AND ' . $key . ' = \'' . $value . '\'';
				} elseif (($key == 's.fk_session_place') || ($key == 'f.rowid') || ($key == 's.type_session') || ($key == 's.status') || ($key == 'sale.fk_user_com')) {
					$sql .= ' AND ' . $key . ' = ' . $value;
				} else {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}

		$sql .= " GROUP BY s.rowid, s.fk_soc, s.fk_session_place, s.type_session, s.dated, s.datef,  s.status, dictstatus.intitule , dictstatus.code, s.is_date_res_site, s.is_date_res_trainer, s.date_res_trainer, s.color, s.force_nb_stagiaire, s.nb_stagiaire,s.notes,";
		$sql .= " p.ref_interne, c.intitule, c.ref,c.ref_interne, so.nom, f.rowid, sorequester.nom";
		$sql .= " ORDER BY " . $sortfield . ' ' . $sortorder;
		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::fetch_all_with_task_state", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array ();

			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);

					$line = new AgfSessionLineTask();

					$line->rowid = $obj->rowid;
					$line->socid = $obj->fk_soc;
					$line->socname = $obj->socname;
					$line->trainerrowid = $obj->trainerrowid;
					$line->type_session = $obj->type_session;
					$line->is_date_res_site = $obj->is_date_res_site;
					$line->is_date_res_trainer = $obj->is_date_res_trainer;
					$line->date_res_trainer = $this->db->jdate($obj->date_res_trainer);
					$line->fk_session_place = $obj->fk_session_place;
					$line->dated = $this->db->jdate($obj->dated);
					$line->datef = $this->db->jdate($obj->datef);
					$line->intitule = $obj->intitule;
					$line->ref = $obj->ref;
					$line->training_ref_interne = $obj->trainingrefinterne;
					$line->ref_interne = $obj->ref_interne;
					$line->color = $obj->color;
					$line->nb_stagiaire = $obj->nb_stagiaire;
					$line->force_nb_stagiaire = $obj->force_nb_stagiaire;
					$line->notes = $obj->notes;
					$line->task0 = $obj->task0;
					$line->task1 = $obj->task1;
					$line->task2 = $obj->task2;
					$line->task3 = $obj->task3;
					$line->duree_session = $obj->duree_session;
					$line->intitule_custo = $obj->intitule_custo;
					$line->socrequesterid = $obj->fk_soc_requester;
					$line->socrequestername = $obj->socrequestername;
					$line->fk_soc_employer = $obj->fk_soc_employer;

					if ($obj->statuslib == $langs->trans('AgfStatusSession_' . $obj->statuscode)) {
						$label = stripslashes($obj->statuslib);
					} else {
						$label = $langs->trans('AgfStatusSession_' . $obj->statuscode);
					}
					$line->status_lib = $obj->statuscode . ' - ' . $label;

					$this->lines[$i] = $line;
					$i ++;
				}
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all_with_task_state " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param string $sortorder order
	 * @param string $sortfield field
	 * @param int $limit page
	 * @param int $offset
	 * @param array $filter output
	 * @param user $user current user
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_all_inter($sortorder, $sortfield, $limit, $offset, $filter = '', $user = 0) {
		global $langs;

		$sql = "SELECT s.rowid, s.fk_session_place, s.dated, s.status, dictstatus.intitule as statuslib, dictstatus.code as statuscode, ";
		$sql .= " s.color, ";
		$sql .= " s.force_nb_stagiaire, s.nb_stagiaire,s.notes,";
		$sql .= " s.is_date_res_site, s.is_date_res_confirm_site,";
		$sql .= " c.intitule, c.ref,c.ref_interne as trainingrefinterne,s.nb_subscribe_min,";
		$sql .= " p.ref_interne";
		$sql .= " ,f.rowid as trainerrowid";
		$sql .= " ,s.intitule_custo";
		$sql .= " ,s.duree_session";
		$sql .= " ,sf.trainer_status";
		$sql .= " ,s.sell_price,";
		$sql .= " ,s.fk_soc_employer,";
		$sql .= " (SELECT count(rowid) FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire WHERE (status_in_session=0 OR status_in_session IS NULL) AND fk_session_agefodd=s.rowid) as nb_prospect,";
		$sql .= " (SELECT count(rowid) FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire WHERE (status_in_session=2 OR status_in_session=1 OR status_in_session=3) AND fk_session_agefodd=s.rowid) as nb_confirm,";
		$sql .= " (SELECT count(rowid) FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire WHERE status_in_session=6 AND fk_session_agefodd=s.rowid) as nb_cancelled,";
		$sql .= " (SELECT archive FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE fk_agefodd_session=s.rowid AND trigger_name='AGF_CONF_CONSULT_SEND') as trainerrn,";
		$sql .= " (SELECT archive FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE fk_agefodd_session=s.rowid AND trigger_name='AGF_CONV_TRAINEE_SEND') as convoc,";
		$sql .= " (SELECT archive FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE fk_agefodd_session=s.rowid AND trigger_name='AGF_SUPPORT_DONE') as support,";
		$sql .= " (SELECT archive FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE fk_agefodd_session=s.rowid AND trigger_name='AGF_PRESENCE_SHEET_DONE') as ffeedit,";
		$sql .= " (SELECT archive FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE fk_agefodd_session=s.rowid AND trigger_name='AGF_SUPPORT_INLINE') as attrn,";
		$sql .= " (SELECT archive FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE fk_agefodd_session=s.rowid AND trigger_name='AGF_PRESENCE_SHEET_SEND') as ffeenv,";
		$sql .= " (SELECT archive FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE fk_agefodd_session=s.rowid AND trigger_name='AGF_INV_TRAINER_VALID') as invtrainer,";
		$sql .= " (SELECT archive FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE fk_agefodd_session=s.rowid AND trigger_name='AGF_INV_ROOM_VALID') as invroom";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " ON c.rowid = s.fk_formation_catalogue";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
		$sql .= " ON p.rowid = s.fk_session_place";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
		$sql .= " ON sf.fk_session = s.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f";
		$sql .= " ON f.rowid = sf.fk_agefodd_formateur";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as dictstatus";
		$sql .= " ON s.status = dictstatus.rowid";
		$sql .= " WHERE ";
		$sql .= " s.type_session=1";
		$sql .= " AND s.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";
		$sql .= " AND (SELECT count(rowid) FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE archive=0 AND fk_agefodd_session=s.rowid)<>0";

		// Manage filter
		if (! empty($filter)) {
			foreach ( $filter as $key => $value ) {
				if (($key == 'YEAR(s.dated)') || ($key == 'MONTH(s.dated)')) {
					$sql .= ' AND ' . $key . ' IN (' . $value . ')';
				} elseif ($key == 's.dated>') {
					if ($this->db->type == 'pgsql') {
						$intervalday = "'" . $value . " DAYS'";
					} else {
						$intervalday = $value . ' DAY';
					}
					$sql .= ' AND s.dated >= DATE_ADD(NOW(), INTERVAL -' . $intervalday . ')';
				} elseif (strpos($key, 'date')) {
					// To allow $filter['YEAR(s.dated)']=>$year
					$sql .= ' AND ' . $key . ' = \'' . $value . '\'';
				} elseif ($key == '!s.status') {
					$sql .= ' AND s.status NOT IN ' . $value;
				} elseif (($key == 's.fk_session_place') || ($key == 'f.rowid') || ($key == 's.status') || ($key == 'sf.fk_agefodd_formateur') || ($key == 'sf.trainer_status')) {
					$sql .= ' AND ' . $key . ' = ' . $value;
				} else {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		$sql .= " ORDER BY " . $sortfield . ' ' . $sortorder;
		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::fetch_all_inter", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array ();

			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);

					$line = new AgfSessionLineInter();

					$line->id = $obj->rowid;
					$line->fk_session_place = $obj->fk_session_place;
					$line->dated = $this->db->jdate($obj->dated);
					$line->color = $obj->color;
					$line->force_nb_stagiaire = $obj->force_nb_stagiaire;
					$line->nb_stagiaire = $obj->nb_stagiaire;
					$line->notes = $obj->notes;
					$line->intitule = $obj->intitule;
					$line->ref = $obj->ref;
					$line->trainingrefinterne = $obj->trainingrefinterne;
					$line->nb_subscribe_min = $obj->nb_subscribe_min;
					$line->ref_interne = $obj->ref_interne;
					$line->trainerrowid = $obj->trainerrowid;
					$line->intitule_custo = $obj->intitule_custo;
					$line->duree_session = $obj->duree_session;
					$line->trainer_status = $obj->trainer_status;
					$line->nb_prospect = $obj->nb_prospect;
					$line->nb_confirm = $obj->nb_confirm;
					$line->nb_cancelled = $obj->nb_cancelled;
					$line->trainerrn = $obj->trainerrn;
					$line->convoc = $obj->convoc;
					$line->support = $obj->support;
					$line->ffeedit = $obj->ffeedit;
					$line->attrn = $obj->attrn;
					$line->ffeenv = $obj->ffeenv;
					$line->invtrainer = $obj->invtrainer;
					$line->invroom = $obj->invroom;
					$line->is_date_res_site = $obj->is_date_res_site;
					$line->is_date_res_confirm_site = $obj->is_date_res_confirm_site;
					$line->sell_price = $obj->sell_price;
					$line->fk_soc_employer = $obj->fk_soc_employer;

					if ($obj->statuslib == $langs->trans('AgfStatusSession_' . $obj->statuscode)) {
						$label = stripslashes($obj->statuslib);
					} else {
						$label = $langs->trans('AgfStatusSession_' . $obj->statuscode);
					}
					$line->status_lib = $label;

					$this->lines[$i] = $line;
					$i ++;
				}
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all_inter " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param int $socid socid filter
	 * @param string $sortorder order
	 * @param string $sortfield field
	 * @param int $limit page
	 * @param int $offset
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_all_by_soc($socid, $sortorder, $sortfield, $limit, $offset, $filter = '') {
		global $conf, $langs, $user;

		$sql = "SELECT DISTINCT s.rowid, s.fk_soc, s.fk_session_place, s.type_session, s.dated, s.datef, s.status, dictstatus.intitule as statuslib, dictstatus.code as statuscode, ";
		$sql .= " s.is_date_res_site, s.is_date_res_trainer, s.date_res_trainer, s.color, ";
		$sql .= " s.force_nb_stagiaire, s.nb_stagiaire,s.notes,";
		$sql .= " c.intitule, c.ref,c.ref_interne as trainingrefinterne,s.nb_subscribe_min,";
		$sql .= " p.ref_interne";
		$sql .= " ,so.nom as socname";
		$sql .= " ,f.rowid as trainerrowid";
		$sql .= " ,s.intitule_custo";
		$sql .= " ,s.duree_session";
		if ($filter['type_affect'] == 'thirdparty') {
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
			$sql .= " ON c.rowid = s.fk_formation_catalogue";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
			$sql .= " ON p.rowid = s.fk_session_place";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
			$sql .= " ON s.rowid = ss.fk_session_agefodd";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as sa";
			$sql .= " ON s.rowid = sa.fk_agefodd_session";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so";
			$sql .= " ON so.rowid = s.fk_soc AND s.fk_soc=" . $socid;
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
			$sql .= " ON sf.fk_session = s.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f";
			$sql .= " ON f.rowid = sf.fk_agefodd_formateur";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socpf";
			$sql .= " ON f.fk_socpeople = socpf.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as dictstatus";
			$sql .= " ON s.status = dictstatus.rowid";

			$type_affect = $langs->trans('ThirdParty');
		} elseif ($filter['type_affect'] == 'trainee') {
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
			$sql .= " ON c.rowid = s.fk_formation_catalogue";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
			$sql .= " ON p.rowid = s.fk_session_place";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
			$sql .= " ON s.rowid = ss.fk_session_agefodd";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta";
			$sql .= " ON ss.fk_stagiaire = sta.rowid AND sta.fk_soc=" . $socid;
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as sa";
			$sql .= " ON s.rowid = sa.fk_agefodd_session";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
			$sql .= " ON so.rowid = s.fk_soc";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
			$sql .= " ON sf.fk_session = s.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f";
			$sql .= " ON f.rowid = sf.fk_agefodd_formateur";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socpf";
			$sql .= " ON f.fk_socpeople = socpf.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as dictstatus";
			$sql .= " ON s.status = dictstatus.rowid";

			$type_affect = $langs->trans('AgfParticipant');
		} elseif ($filter['type_affect'] == 'opca') {
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
			$sql .= " ON c.rowid = s.fk_formation_catalogue";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
			$sql .= " ON p.rowid = s.fk_session_place";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
			$sql .= " ON s.rowid = ss.fk_session_agefodd";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as sa";
			$sql .= " ON s.rowid = sa.fk_agefodd_session";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
			$sql .= " ON so.rowid = s.fk_soc";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
			$sql .= " ON sf.fk_session = s.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f";
			$sql .= " ON f.rowid = sf.fk_agefodd_formateur";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socpf";
			$sql .= " ON f.fk_socpeople = socpf.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as dictstatus";
			$sql .= " ON s.status = dictstatus.rowid";

			$type_affect = $langs->trans('AgfMailTypeContactOPCA');
		} elseif ($filter['type_affect'] == 'requester') {
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
			$sql .= " ON c.rowid = s.fk_formation_catalogue";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
			$sql .= " ON p.rowid = s.fk_session_place";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
			$sql .= " ON s.rowid = ss.fk_session_agefodd";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as sa";
			$sql .= " ON s.rowid = sa.fk_agefodd_session";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so";
			$sql .= " ON so.rowid = s.fk_soc_requester AND so.rowid=" . $socid;
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
			$sql .= " ON sf.fk_session = s.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f";
			$sql .= " ON f.rowid = sf.fk_agefodd_formateur";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socpf";
			$sql .= " ON f.fk_socpeople = socpf.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as dictstatus";
			$sql .= " ON s.status = dictstatus.rowid";

			$type_affect = $langs->trans('AgfTypeRequester');
		} elseif ($filter['type_affect'] == 'presta') {
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
			$sql .= " ON c.rowid = s.fk_formation_catalogue";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
			$sql .= " ON p.rowid = s.fk_session_place";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
			$sql .= " ON s.rowid = ss.fk_session_agefodd";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as sa";
			$sql .= " ON s.rowid = sa.fk_agefodd_session";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "socpeople as socpp";
			$sql .= " ON socpp.rowid = s.fk_socpeople_presta";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so";
			$sql .= " ON so.rowid = socpp.fk_soc AND so.rowid=" . $socid;
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
			$sql .= " ON sf.fk_session = s.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f";
			$sql .= " ON f.rowid = sf.fk_agefodd_formateur";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socpf";
			$sql .= " ON f.fk_socpeople = socpf.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as dictstatus";
			$sql .= " ON s.status = dictstatus.rowid";

			$type_affect = $langs->trans('AgfTypePresta');
		} elseif ($filter['type_affect'] == 'trainer') {
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
			$sql .= " ON c.rowid = s.fk_formation_catalogue";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
			$sql .= " ON p.rowid = s.fk_session_place";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
			$sql .= " ON s.rowid = ss.fk_session_agefodd";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as sa";
			$sql .= " ON s.rowid = sa.fk_agefodd_session";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_element as elemnt";
			$sql .= " ON elemnt.fk_session_agefodd = s.rowid AND elemnt.element_type='invoice_supplier_trainer'";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn as factfourn";
			$sql .= " ON factfourn.rowid = elemnt.fk_element";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so";
			$sql .= " ON so.rowid = factfourn.fk_soc AND so.rowid=" . $socid;
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
			$sql .= " ON sf.fk_session = s.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f";
			$sql .= " ON f.rowid = sf.fk_agefodd_formateur";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socpf";
			$sql .= " ON f.fk_socpeople = socpf.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as dictstatus";
			$sql .= " ON s.status = dictstatus.rowid";

			$type_affect = $langs->trans('AgfFormateur');
		} elseif ($filter['type_affect'] == 'trainee_requester') {
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
			$sql .= " ON c.rowid = s.fk_formation_catalogue";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
			$sql .= " ON p.rowid = s.fk_session_place";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
			$sql .= " ON s.rowid = ss.fk_session_agefodd AND ss.fk_soc_requester=" . $socid;
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta";
			$sql .= " ON ss.fk_stagiaire = sta.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as sa";
			$sql .= " ON s.rowid = sa.fk_agefodd_session";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
			$sql .= " ON so.rowid = s.fk_soc";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
			$sql .= " ON sf.fk_session = s.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f";
			$sql .= " ON f.rowid = sf.fk_agefodd_formateur";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socpf";
			$sql .= " ON f.fk_socpeople = socpf.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as dictstatus";
			$sql .= " ON s.status = dictstatus.rowid";

			$type_affect = $langs->trans('AgfTypeTraineeRequester');
		} elseif ($filter['type_affect'] == 'employer') {
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
			$sql .= " ON c.rowid = s.fk_formation_catalogue";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
			$sql .= " ON p.rowid = s.fk_session_place";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
			$sql .= " ON s.rowid = ss.fk_session_agefodd";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as sa";
			$sql .= " ON s.rowid = sa.fk_agefodd_session";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so";
			$sql .= " ON so.rowid = s.fk_soc_employer AND s.fk_soc=" . $socid;
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
			$sql .= " ON sf.fk_session = s.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f";
			$sql .= " ON f.rowid = sf.fk_agefodd_formateur";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socpf";
			$sql .= " ON f.fk_socpeople = socpf.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as dictstatus";
			$sql .= " ON s.status = dictstatus.rowid";

			$type_affect = $langs->trans('AgfTypeEmployee');
		}

		$sql .= " WHERE s.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";

		if ($filter['type_affect'] == 'opca') {
			$sql .= ' AND (s.rowid IN (SELECT rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session WHERE is_OPCA=1 AND fk_soc_OPCA=' . $socid . ')';
			$sql .= ' OR s.rowid IN (SELECT innersess.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session as innersess';
			$sql .= '		INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_opca as opca';
			$sql .= '		ON opca.fk_session_agefodd=innersess.rowid AND opca.is_OPCA=1 AND opca.fk_soc_OPCA=' . $socid . '))';
		}

		if (is_object($user) && ! empty($user->id) && empty($user->rights->agefodd->session->all) && empty($user->admin)) {
			// Saleman of session is current user
			$sql .= 'AND (s.rowid IN (SELECT rightsession.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session as rightsession, ';
			$sql .= MAIN_DB_PREFIX . 'agefodd_session_commercial as rightsalesman WHERE rightsession.rowid=rightsalesman.fk_session_agefodd AND rightsalesman.fk_user_com=' . $user->id . ')';
			$sql .= " OR ";
			// current user is saleman of customersession
			$sql .= ' (s.fk_soc IN (SELECT ' . MAIN_DB_PREFIX . 'societe_commerciaux.fk_soc FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux WHERE fk_user=' . $user->id . ')))';

			//TODO : What is it for hard coded dependancy on other module...
			if ($conf->volvo->enabled) {

				$sql .= ' OR (s.rowid IN (SELECT sessform.fk_session FROM ' . MAIN_DB_PREFIX . 'agefodd_session_element as elem';
				$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'volvo_vehicule as veh ON veh.rowid=elem.fk_element AND elem.element_type = \'vehicule\'';
				$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_formateur as sessform ON sessform.fk_session=elem.fk_session_agefodd';
				$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_formateur as form ON form.rowid=sessform.fk_agefodd_formateur AND form.fk_user=' . $user->id . '))';
			}
		}

		// Manage filter
		if (! empty($filter)) {
			foreach ( $filter as $key => $value ) {
				if ($key != 'type_affect') {
					if (strpos($key, 'date')) {
						// To allow $filter['YEAR(s.dated)']=>$year
						$sql .= ' AND ' . $key . ' = \'' . $value . '\'';
					} elseif (($key == 's.fk_session_place') || ($key == 'f.rowid') || ($key == 's.type_session') || ($key == 's.status')) {
						$sql .= ' AND ' . $key . ' IN (' . implode(',', $value) . ')';
					} elseif ($key == '!s.rowid') {
						$sql .= ' AND s.rowid NOT IN (' . $value . ')';
					} else {
						$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
					}
				}
			}
		}
		$sql .= " ORDER BY " . $sortfield . ' ' . $sortorder;
		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::fetch_all_by_soc", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array ();

			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);

					$line = new AgfSessionLineSoc();

					$line->rowid = $obj->rowid;
					$line->socid = $obj->fk_soc;
					$line->socname = $obj->socname;
					$line->trainerrowid = $obj->trainerrowid;
					$line->type_session = $obj->type_session;
					$line->is_date_res_site = $obj->is_date_res_site;
					$line->is_date_res_trainer = $obj->is_date_res_trainer;
					$line->date_res_trainer = $this->db->jdate($obj->date_res_trainer);
					$line->fk_session_place = $obj->fk_session_place;
					$line->dated = $this->db->jdate($obj->dated);
					$line->datef = $this->db->jdate($obj->datef);
					$line->intitule = $obj->intitule;
					$line->ref = $obj->ref;
					$line->training_ref_interne = $obj->trainingrefinterne;
					$line->ref_interne = $obj->ref_interne;
					$line->color = $obj->color;
					$line->nb_stagiaire = $obj->nb_stagiaire;
					$line->force_nb_stagiaire = $obj->force_nb_stagiaire;
					$line->notes = $obj->notes;
					$line->nb_subscribe_min = $obj->nb_subscribe_min;
					$line->type_affect = $type_affect;
					$line->duree_session = $obj->duree_session;
					$line->intitule_custo = $obj->intitule_custo;

					if ($obj->statuslib == $langs->trans('AgfStatusSession_' . $obj->statuscode)) {
						$label = stripslashes($obj->statuslib);
					} else {
						$label = $langs->trans('AgfStatusSession_' . $obj->statuscode);
					}
					$line->status_lib = $label;

					$this->lines[$i] = $line;
					$i ++;
				}
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all_by_soc " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param string $sortorder order
	 * @param string $sortfield field
	 * @param int $limit page
	 * @param int $offset
	 * @param string $ordernum num linked
	 * @param string $invoicenum num linked
	 * @param string $propalid num linked
	 * @param string $fourninvoiceid num linked
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_all_by_order_invoice_propal($sortorder, $sortfield, $limit, $offset, $orderid = '', $invoiceid = '', $propalid = '', $fourninvoiceid = '') {
		global $langs;

		$sql = "SELECT s.rowid, s.fk_soc, s.fk_session_place, s.type_session, s.dated, s.datef, s.is_date_res_site, s.is_date_res_trainer, s.date_res_trainer, s.color, s.force_nb_stagiaire, s.nb_stagiaire,s.notes,";
		$sql .= " c.intitule, c.ref";
		$sql .= " ,s.intitule_custo";
		$sql .= " ,s.duree_session,";
		$sql .= " p.ref_interne";
		if (! empty($invoiceid)) {
			$sql .= " ,invoice.facnumber as invoiceref";
		}
		if (! empty($fourninvoiceid)) {
			$sql .= " ,fourninvoice.ref as fourninvoiceref";
		}
		if (! empty($orderid)) {
			$sql .= " ,order_dol.ref as orderref";
		}
		if (! empty($propalid)) {
			$sql .= " ,propal_dol.ref as propalref";
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " ON c.rowid = s.fk_formation_catalogue";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
		$sql .= " ON p.rowid = s.fk_session_place";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as sa";
		$sql .= " ON s.rowid = sa.fk_agefodd_session";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_element as ord_inv";
		$sql .= " ON s.rowid = ord_inv.fk_session_agefodd";

		if (! empty($invoiceid)) {
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture as invoice ";
			$sql .= " ON invoice.rowid = ord_inv.fk_element AND  ord_inv.element_type='invoice'";
			$sql .= ' AND invoice.rowid=' . $invoiceid;
		}

		if (! empty($fourninvoiceid)) {
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn as fourninvoice ";
			$sql .= " ON fourninvoice.rowid = ord_inv.fk_element AND  ord_inv.element_type LIKE 'invoice_supplier%'";
			$sql .= ' AND fourninvoice.rowid=' . $fourninvoiceid;
		}

		if (! empty($orderid)) {
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "commande as order_dol ";
			$sql .= " ON order_dol.rowid = ord_inv.fk_element AND  ord_inv.element_type='order'";
			$sql .= ' AND order_dol.rowid=' . $orderid;
		}

		if (! empty($propalid)) {
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "propal as propal_dol ";
			$sql .= " ON propal_dol.rowid = ord_inv.fk_element AND  ord_inv.element_type='propal'";
			$sql .= ' AND propal_dol.rowid=' . $propalid;
		}
		$sql .= " WHERE s.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";

		$sql .= " GROUP BY s.rowid,c.intitule,c.ref,p.ref_interne";

		if (! empty($invoiceid)) {
			$sql .= " ,invoice.facnumber ";
		}

		if (! empty($fourninvoiceid)) {
			$sql .= " ,fourninvoice.ref ";
		}

		if (! empty($orderid)) {
			$sql .= " ,order_dol.ref ";
		}

		if (! empty($propalid)) {
			$sql .= " ,propal_dol.ref ";
		}

		$sql .= " ORDER BY $sortfield $sortorder " . $this->db->plimit($limit + 1, $offset);

		dol_syslog(get_class($this) . "::fetch_all_by_order_invoice_propal", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->line = array ();
			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);

					$line = new AgfInvoiceOrder();

					$line->rowid = $obj->rowid;
					$line->socid = $obj->fk_soc;
					$line->type_session = $obj->type_session;
					$line->is_date_res_site = $obj->is_date_res_site;
					$line->is_date_res_trainer = $obj->is_date_res_trainer;
					$line->date_res_trainer = $this->db->jdate($obj->date_res_trainer);
					$line->fk_session_place = $obj->fk_session_place;
					$line->dated = $this->db->jdate($obj->dated);
					$line->datef = $this->db->jdate($obj->datef);
					$line->intitule = $obj->intitule;
					$line->ref = $obj->ref;
					$line->ref_interne = $obj->ref_interne;
					$line->color = $obj->color;
					$line->nb_stagiaire = $obj->nb_stagiaire;
					$line->force_nb_stagiaire = $obj->force_nb_stagiaire;
					$line->duree_session = $obj->duree_session;
					$line->intitule_custo = $obj->intitule_custo;
					$line->notes = $obj->notes;
					if (! empty($invoiceid)) {
						$line->invoiceref = $obj->invoiceref;
					}
					if (! empty($orderid)) {
						$line->orderref = $obj->orderref;
					}
					if (! empty($propalid)) {
						$line->propalref = $obj->propalref;
					}
					if (! empty($fourninvoiceid)) {
						$line->fourninvoiceref = $obj->fourninvoiceref;
					}

					$this->lines[$i] = $line;

					$i ++;
				}
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all_by_order_invoice " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Print table of session information
	 */
	public function printSessionInfo($width_table=true) {
		global $form, $langs, $conf, $user;

		require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
		require_once (DOL_DOCUMENT_ROOT . '/product/class/product.class.php');
		require_once (DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php');
		require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');


		$colspan=1;
		if (!$width_table) $colspan = 3;

		$action=GETPOST('action','alpha');

		if ($action=='setsession_status') {
			$this->status=GETPOST('session_status');
			$result=$this->update($user);
			if ($result<0) {
				setEventMessage($this->error,'errors');
			} else {
				$this->fetch($this->id);
			}

		}

		$socstatic = new Societe($this->db);
		$contactstatic = new Contact($this->db);

		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label($this->table_element);

		print '<div class="fichecenter"><table id="session_card" class="border tableforfield" width="100%">';

		print '<tr class="order_ref"><td width="20%">' . $langs->trans("Ref") . '</td>';
		print '<td colspan="'.$colspan.'">' . $form->showrefnav($this, 'id', '', 1, 'rowid', 'id') . '</td></tr>';

		print '<tr class="order_intitule"><td>' . $langs->trans("AgfFormIntitule") . '</td>';
		print '<td colspan="'.$colspan.'"><a href="' . dol_buildpath('/agefodd/training/card.php', 1) . '?id=' . $this->fk_formation_catalogue . '">' . $this->formintitule . '</a></td></tr>';

		print '<tr class="order_intituleCusto"><td>' . $langs->trans("AgfFormIntituleCust") . '</td>';
		print '<td colspan="'.$colspan.'"><a href="' . dol_buildpath('/agefodd/training/card.php', 1) . '?id=' . $this->fk_formation_catalogue . '">' . $this->intitule_custo . '</a></td></tr>';

		print '<tr class="order_formRef"><td>' . $langs->trans("AgfFormRef") . '</td>';
		print '<td colspan="'.$colspan.'">' . $this->formref . '</td></tr>';

		// Type de la session
		print '<tr class="order_type"><td>' . $langs->trans("AgfFormTypeSession") . '</td>';
		print '<td colspan="'.$colspan.'">' . ($this->type_session ? $langs->trans('AgfFormTypeSessionInter') : $langs->trans('AgfFormTypeSessionIntra')) . '</td></tr>';

		print '<tr class="order_sessionCommercial"><td>' . $langs->trans("AgfSessionCommercial") . '</td>';
		print '<td colspan="'.$colspan.'"><a href="' . dol_buildpath('/user/card.php', 1) . '?id=' . $this->commercialid . '">' . $this->commercialname . '</a></td></tr>';

		print '<tr class="order_duration"><td>' . $langs->trans("AgfDuree") . '</td>';
		print '<td colspan="'.$colspan.'">' . $this->duree_session . ' ' . $langs->trans('Hour') . '(s)</td></tr>';

		print '<tr class="order_product"><td>' . $langs->trans("AgfProductServiceLinked") . '</td>';
		print '<td colspan="'.$colspan.'">';
		if (! empty($this->fk_product)) {
			$product = new Product($this->db);
			$result = $product->fetch($this->fk_product);
			if ($result < 0) {
				setEventMessage($product->error, 'errors');
			}
			print $product->getNomUrl(1) . ' - ' . $product->label;
		}

		print "</td></tr>";

		print '<tr class="order_dated"><td>' . $langs->trans("AgfDateDebut") . '</td>';
		print '<td colspan="'.$colspan.'">' . dol_print_date($this->dated, 'daytext') . '</td></tr>';

		print '<tr class="order_datef"><td>' . $langs->trans("AgfDateFin") . '</td>';
		print '<td colspan="'.$colspan.'">' . dol_print_date($this->datef, 'daytext') . '</td></tr>';

		print '<tr class="order_customer"><td width="20%">' . $langs->trans("Customer") . '</td>';
		print '	<td colspan="'.$colspan.'">';
		if ((! empty($this->fk_soc)) && ($this->fk_soc > 0)) {

			$result = $socstatic->fetch($this->fk_soc);
			if ($result < 0) {
				setEventMessage($socstatic->error, 'errors');
			}
			print $socstatic->getNomUrl(1);
		}
		print '</td></tr>';

		print '<tr class="order_sessionContact"><td>' . $langs->trans("AgfSessionContact") . '</td>';
		if (! empty($this->sourcecontactid) && ! empty($conf->global->AGF_CONTACT_DOL_SESSION)) {
			print '<td colspan="'.$colspan.'"><a href="' . dol_buildpath('/contact/card.php', 1) . '?id=' . $this->sourcecontactid . '">' . $this->contactname . '</a></td></tr>';
		} else {
			print '<td colspan="'.$colspan.'"><a href="' . dol_buildpath('/agefodd/contact/card.php', 1) . '?id=' . $this->contactid . '">' . $this->contactname . '</a></td></tr>';
		}

		print '<tr class="order_typeRequester"><td width="20%">' . $langs->trans("AgfTypeRequester") . '</td>';
		print '	<td colspan="'.$colspan.'">';
		if ((! empty($this->fk_soc_requester)) && ($this->fk_soc_requester > 0)) {
			$result = $socstatic->fetch($this->fk_soc_requester);
			if ($result < 0) {
				setEventMessage($socstatic->error, 'errors');
			}
			print $socstatic->getNomUrl(1);
		}
		print '</td></tr>';

		print '<tr class="order_typeRequesterContact"><td>' . $langs->trans("AgfTypeRequesterContact") . '</td>';
		print '<td colspan="'.$colspan.'">';
		if ((! empty($this->fk_socpeople_requester)) && ($this->fk_socpeople_requester > 0)) {
			$result = $contactstatic->fetch($this->fk_socpeople_requester);
			if ($result < 0) {
				setEventMessage($contactstatic->error, 'errors');
			}
			print $contactstatic->getNomUrl(1);
		}
		print '</td></tr>';

		print '<tr class="order_typePresta"><td>' . $langs->trans("AgfTypePresta") . '</td>';
		print '<td colspan="'.$colspan.'">';
		if ((! empty($this->fk_socpeople_presta)) && ($this->fk_socpeople_presta > 0)) {
			$result = $contactstatic->fetch($this->fk_socpeople_presta);
			if ($result < 0) {
				setEventMessage($contactstatic->error, 'errors');
			}
			print $contactstatic->getNomUrl(1);
		}
		print '</td></tr>';

		print '<tr class="order_typeEmployee"><td>' . $langs->trans("AgfTypeEmployee") . '</td>';
		print '<td colspan="'.$colspan.'">';
		if ((! empty($this->fk_soc_employer)) && ($this->fk_soc_employer > 0)) {
			$result = $socstatic->fetch($this->fk_soc_employer);
			if ($result < 0) {
				setEventMessage($socstatic->error, 'errors');
			}
			print $socstatic->getNomUrl(1);
		}
		print '</td></tr>';

		print '<tr class="order_place"><td>' . $langs->trans("AgfLieu") . '</td>';
		print '<td colspan="'.$colspan.'"><a href="' . dol_buildpath('/agefodd/site/card.php', 1) . '?id=' . $this->placeid . '">' . $this->placecode . '</a></td></tr>';

		print '<tr class="order_note"><td valign="top">' . $langs->trans("AgfNote") . '</td>';
		if (! empty($this->notes))
			$notes = nl2br($this->notes);
		else
			$notes = $langs->trans("AgfUndefinedNote");
		print '<td colspan="'.$colspan.'">' . stripslashes($notes) . '</td></tr>';

		print '<tr class="order_dateResTrainer"><td>' . $langs->trans("AgfDateResTrainer") . '</td>';
		if ($this->is_date_res_trainer) {
			print '<td colspan="'.$colspan.'">' . dol_print_date($this->date_res_trainer, 'daytext') . '</td></tr>';
		} else {
			print '<td colspan="'.$colspan.'">' . $langs->trans("AgfNoDefined") . '</td></tr>';
		}

		print '<tr class="order_dateResSite"><td>' . $langs->trans("AgfDateResSite") . '</td>';
		if ($this->is_date_res_site) {
			print '<td colspan="'.$colspan.'">' . dol_print_date($this->date_res_site, 'daytext') . '</td></tr>';
		} else {
			print '<td colspan="'.$colspan.'">' . $langs->trans("AgfNoDefined") . '</td></tr>';
		}

		print '<tr class="order_dateResConfirmSite"><td>' . $langs->trans("AgfDateResConfirmSite") . '</td>';
		if ($this->is_date_res_confirm_site) {
			print '<td colspan="'.$colspan.'">' . dol_print_date($this->date_res_confirm_site, 'daytext') . '</td></tr>';
		} else {
			print '<td colspan="'.$colspan.'">' . $langs->trans("AgfNoDefined") . '</td></tr>';
		}

		print '<tr class="order_nbMintarget"><td>' . $langs->trans("AgfNbMintarget") . '</td><td colspan="'.$colspan.'">';
		print $this->nb_subscribe_min . '</td></tr>';

		print '<tr class="order_nbplaceavailable"><td width="20%">' . $langs->trans("AgfNumberPlaceAvailable") . '</td>';
		print '<td colspan="'.$colspan.'">' . ((($this->nb_place - $this->nb_stagiaire) > 0) ? ($this->nb_place - $this->nb_stagiaire) : 0) . '/' . $this->nb_place . '</td></tr>';


		print '<tr class="order_status">';
		print '<td>';
		print $form->editfieldkey("AgfStatusSession",'session_status',$this->status,$this,$user->rights->agefodd->modifier);
		print '</td>';
		print '<td colspan="'.$colspan.'">';
		if ($action=='editsession_status') {
			print '<script type="text/javascript">
						jQuery(document).ready(function () {
							jQuery(function() {' . "\n";
			print '				 $(\'html, body\').animate({scrollTop: $("#session_status").offset().top-20}, 500,\'easeInOutCubic\');';
			print '			});
					});
					</script> ';
			require_once ('../class/html.formagefodd.class.php');
			$formAgefodd = new FormAgefodd($this->db);
			print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$this->id.'">';
			print '<input type="hidden" name="action" value="setsession_status">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print $formAgefodd->select_session_status($this->status, "session_status", 't.active=1');
			print '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			print '</form>';
		} else {
			print $this->statuslib;
		}
		print '</td>';
		print '</tr>';

		/*print '<tr class="order_status"><td>' . $langs->trans("AgfStatusSession") . '</td><td>';
		print $this->statuslib . '</td></tr>';*/

		if (! empty($extrafields->attribute_label)) {
			print $this->showOptionals($extrafields);
		}

		if ($width_table) print '</table>';
		print '</div>';


		print '<BR/>';
		if ($width_table) print '<table class="border" width="100%">';
		print '<tr class="order_calendrier">';

		require_once 'agefodd_session_calendrier.class.php';
		$calendrier = new Agefodd_sesscalendar($this->db);
		$calendrier->fetch_all($this->id);
		$blocNumber = count($calendrier->lines);
		$alertday = false;
		if ($blocNumber < 1) {
			print '<td  width="20%" valign="top" >' . $langs->trans("AgfCalendrier") . '</td>';
			print '<td colspan="'.$colspan.'" style="color:red; text-decoration: blink;">' . $langs->trans("AgfNoCalendar") . '</td></tr>';
		} else {
			print '<td  width="20%" valign="top" style="border-bottom:0px;">' . $langs->trans("AgfCalendrier") . '</td>';
			$old_date = 0;
			$duree = 0;
			for($i = 0; $i < $blocNumber; $i ++) {
				if ($i > 6) {
					$styledisplay = " style=\"display:none\" class=\"otherdate\" ";
				} else {
					$styledisplay = " ";
				}
				if ($calendrier->lines[$i]->date_session != $old_date) {
					if ($i > 0) {
						print '</tr><tr ' . $styledisplay . '><td width="150px" style="border:0px;">&nbsp;</td>';
					}
					print '<td width="150px">';
					print dol_print_date($calendrier->lines[$i]->date_session, 'daytext') . '</td><td>';
				} else {
					print ', ';
				}
				print dol_print_date($calendrier->lines[$i]->heured, 'hour') . ' - ' . dol_print_date($calendrier->lines[$i]->heuref, 'hour');

				if (($calendrier->lines[$i]->date_session < $this->dated) || ($calendrier->lines[$i]->date_session > $this->datef))
					$alertday = true;
				if ($i == $blocNumber - 1)
					print '</td></tr>';

				$old_date = $calendrier->lines[$i]->date_session;

				// We calculate the total duration times
				// reminders: mktime(hours, minutes, secondes, month, day, year);
				$duree += ($calendrier->lines[$i]->heuref - $calendrier->lines[$i]->heured);
			}
			if ((($this->duree_session * 3600) != $duree) && (empty($conf->glogal->AGF_NOT_DISPLAY_WARNING_TIME_SESSION))) {
				print '<tr><td colspan=2>';
				if (($this->duree_session * 3600) < $duree)
					$textdurationwarning = $langs->trans("AgfCalendarSup");
					if (($this->duree_session * 3600) > $duree)
					$textdurationwarning = $langs->trans("AgfCalendarInf");
				$min = floor($duree / 60);
				$rmin = sprintf("%02d", $min % 60);
				$hour = floor($min / 60);
				$textdurationwarning.=' (' . $langs->trans("AgfCalendarDureeProgrammee") . ': ' . $hour . ':' . $rmin . ', ';
				$textdurationwarning.= $langs->trans("AgfCalendarDureeThéorique") . ' : ' . ($this->duree_session) . ':00)';
				print img_warning();
				print $textdurationwarning.'</td></tr>';
				setEventMessage($textdurationwarning, 'warnings');
			}
			if ($alertday) {
				print '<tr><td>&nbsp;</td><td colspan=2>';
				print img_warning($langs->trans("AgfCalendarDayOutOfScope"));
				print $langs->trans("AgfCalendarDayOutOfScope") . '</td></tr>';
				setEventMessage($langs->trans("AgfCalendarDayOutOfScope"), 'warnings');
			}
			if ($blocNumber > 6) {
				print '<tr><td>&nbsp;</td><td colspan="2" style="font-weight: bold; font-size:150%; cursor:pointer" id="switchtime">+</td></tr>';
				print '<script>' . "\n";
				print '$(document).ready(function () { ' . "\n";
				print '		$(\'#switchtime\').click(function(){' . "\n";
				print '			$(\'.otherdate\').toggle();' . "\n";
				print '			if ($(\'#switchtime\').text()==\'+\') { ' . "\n";
				print '				$(\'#switchtime\').text(\'-\'); ' . "\n";
				print '			}else { ' . "\n";
				print '				$(\'#switchtime\').text(\'+\'); ' . "\n";
				print '			} ' . "\n";
				print '			' . "\n";
				print '		});' . "\n";
				print '});' . "\n";
				print '</script>' . "\n";
			}
		}
		if ($width_table) print "</table>";
	}

	/**
	 * Return clicable link of object (with eventually picto)
	 *
	 * @param int $withpicto into link
	 * @param string $option the link
	 * @param int $maxlength ref
	 * @return string with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $maxlength = 0) {
		global $langs;

		$result = '';

		if (! $option) {
			$lien = '<a href="' . dol_buildpath('/agefodd/session/card.php', 1) . '?id=' . $this->id . '">';
			$lienfin = '</a>';
		}
		$newref = $this->formintitule;
		if ($maxlength)
			$newref = dol_trunc($newref, $maxlength, 'middle');

		if ($withpicto) {
			$result .= ($lien . img_object($langs->trans("ShowSession") . ' ' . $this->ref, 'agefodd@agefodd') . $lienfin . ' ');
		}
		$result .= $lien . $newref . $lienfin;
		return $result;
	}

	/**
	 * Set archive flag to 1 to session according to selected year
	 *
	 * @param int $year year
	 * @param User $user that modify
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function updateArchiveByYear($year, $user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Check parameters
		if (! isset($year)) {
			$error ++;
			$this->errors[] = "Error " . $langs->trans('ErrorParameterMustBeProvided', 'year');
		}

		// Update request
		if (! $error) {
			$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session SET";
			$sql .= " status=4,";
			$sql .= " fk_user_mod=" . $user->id . " ";
			$sql .= " WHERE YEAR(dated)='" . $year . "'";

			$this->db->begin();

			dol_syslog(get_class($this) . "::updateArchiveByYear", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
			if (! $error) {
				if (! $notrigger) {
					// // Call triggers
					// include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
					// $interface=new Interfaces($this->db);
					// $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
					// if ($result < 0) { $error++; $this->errors=$interface->errors; }
					// // End call triggers
				}
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::updateArchiveByYear " . $errmsg, LOG_ERR);
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
	 * Create order from session
	 *
	 * @param User $user that modify
	 * @param int $socid id
	 * @param int $frompropalid from proposal
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function createOrder($user, $socid, $frompropalid = 0) {
		require_once (DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
		require_once (DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php');
		require_once (DOL_DOCUMENT_ROOT . '/product/class/product.class.php');
		require_once ('agefodd_session_element.class.php');
		require_once ('agefodd_session_stagiaire.class.php');
		require_once ('agefodd_opca.class.php');

		global $langs, $mysoc, $conf;

		$error = 0;

		$order = new Commande($this->db);

		$this->db->begin();

		// Create order from proposal
		if (! empty($frompropalid)) {
			require_once (DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php');

			// Find proposal
			$propal = new Propal($this->db);
			$result = $propal->fetch($frompropalid);
			if ($result < 0 || empty($propal->id)) {
				$this->errors[] = $propal->error;
				$error ++;
			} elseif ($propal->statut != 2) {
				$this->errors[] = $langs->trans('AgfProposalMustBeSignToCreateOrderFrom');
				$error ++;
			} else {
				$neworderid = $order->createFromProposal($propal);
				if ($neworderid < 0) {
					$this->errors[] = $order->error;
					$error ++;
				}
			}
		} else {
			// Define new order from scratch
			$soc = new Societe($this->db);
			$result = $soc->fetch($socid);
			if ($result < 0 || empty($soc->id)) {
				$this->errors[] = $soc->error;
				$error ++;
			}

			$order->thirdparty = $soc;

			$order->socid = $socid;
			$order->date = dol_now();
			$order->modelpdf = $conf->global->COMMANDE_ADDON_PDF;

			if (! empty($this->fk_product)) {

				$product = new Product($this->db);
				$result = $product->fetch($this->fk_product);
				if ($result < 0 || empty($product->id)) {
					$this->errors[] = $product->error;
					$error ++;
				}

				$order->lines[0] = new OrderLine($db);
				$order->lines[0]->fk_product = $this->fk_product;

				if (! empty($this->intitule_custo)) {
					$desc = $this->intitule_custo . "\n";
				} else {
					$desc = $this->formintitule . "\n";
				}
				$desc .= "\n" . dol_print_date($this->dated, 'day');
				if ($this->datef != $this->dated) {
					$desc .= '-' . dol_print_date($this->datef, 'day');
				}
				if (! empty($this->duree_session)) {
					$desc .= "\n" . $langs->transnoentities('AgfPDFFichePeda1') . ': ' . $this->duree_session . ' ' . $langs->trans('Hour') . 's';
				}
				if (! empty($this->placecode)) {
					$desc .= "\n" . $langs->trans('AgfLieu') . ': ' . $this->placecode;
				}
				$session_trainee = new Agefodd_session_stagiaire($this->db);
				$session_trainee->fetch_stagiaire_per_session($this->id, $socid, 1);
				if (count($session_trainee->lines) > 0) {
					$desc_trainee = "\n" . count($session_trainee->lines) . ' ';
					if (count($session_trainee->lines) > 1) {
						$desc_trainee .= $langs->trans('AgfParticipants');
					} elseif (count($session_trainee->lines) == 1) {
						$desc_trainee .= $langs->trans('AgfParticipant');
					}
					if ($conf->global->AGF_ADD_TRAINEE_NAME_INTO_DOCPROPODR) {
						$desc_trainee .= "\n";
						foreach ( $session_trainee->lines as $line ) {

							if ($line->status_in_session != 5 && $line->status_in_session != 6) {
								$sessionOPCA = new Agefodd_opca($this->db);
								if ($this->type_session == 1) {
									$sessionOPCA->getOpcaForTraineeInSession($line->socid, $this->id);
								} else {
									$sessionOPCA->num_OPCA_file = $this->num_OPCA_file;
								}

								if (! empty($sessionOPCA->num_OPCA_file)) {
									$desc_trainee .= dol_strtoupper($line->nom) . ' ' . $line->prenom . '(' . $sessionOPCA->num_OPCA_file . ')' . "\n";
								} else {
									$desc_trainee .= dol_strtoupper($line->nom) . ' ' . $line->prenom . "\n";
								}
							}
						}
					}
					$desc .= ' ' . $desc_trainee;
				}
				$order->lines[0]->desc = $desc;

				// For session inter set the quantity to number of trainee
				if ($this->type_session == 1 && count($session_trainee->lines) >= 1) {
					$order->lines[0]->qty = count($session_trainee->lines);
				} else {
					$order->lines[0]->qty = 1;
				}

				// Calculate price
				$tva_tx = get_default_tva($mysoc, $order->thirdparty, $product->id);

				// multiprix
				if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($order->thirdparty->price_level)) {
					$pu_ht = $prod->multiprices[$order->thirdparty->price_level];
					$pu_ttc = $prod->multiprices_ttc[$order->thirdparty->price_level];
					$price_min = $prod->multiprices_min[$order->thirdparty->price_level];
					$price_base_type = $prod->multiprices_base_type[$order->thirdparty->price_level];
				} elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
					$sql = "SELECT ";
					$sql .= ' pcp.rowid as idprodcustprice, pcp.price as custprice, pcp.price_ttc as custprice_ttc, pcp.price_min as custprice_min,';
					$sql .= ' pcp.price_base_type as custprice_base_type, pcp.tva_tx  as custtva_tx';
					$sql .= " FROM " . MAIN_DB_PREFIX . "product_customer_price as pcp WHERE pcp.fk_soc=" . $soc->id . " AND pcp.fk_product=" . $this->fk_product;
					dol_syslog(get_class($this) . "::createOrder", LOG_DEBUG);
					$resql = $this->db->query($sql);
					if ($resql) {
						if ($this->db->num_rows($resql)) {
							$obj = $this->db->fetch_object($resql);
							$pu_ht = $obj->custprice;
							$pu_ttc = $obj->custprice_ttc;
							$price_min = $obj->custprice_min;
							$price_base_type = $obj->custprice_base_type;
							$tva_tx = $obj->custtva_tx;
						} else {
							$pu_ht = $product->price;
							$pu_ttc = $product->price_ttc;
							$price_min = $product->price_min;
							$price_base_type = $product->price_base_type;
						}
						$this->db->free($resql);
					} else {
						$pu_ht = $product->price;
						$pu_ttc = $product->price_ttc;
						$price_min = $product->price_min;
						$price_base_type = $product->price_base_type;
					}
				} else {
					$pu_ht = $product->price;
					$pu_ttc = $product->price_ttc;
					$price_min = $product->price_min;
					$price_base_type = $product->price_base_type;
				}

				$order->lines[0]->subprice = $pu_ht;
				$order->lines[0]->tva_tx = $tva_tx;

				// Add relative discount is exists on soc
				if (! empty($soc->remise_percent)) {
					$order->lines[0]->remise_percent = $soc->remise_percent;
				}
			}

			if (empty($error)) {
				$neworderid = $order->create($user);
				if ($neworderid < 0) {
					$this->errors[] = $order->error;
					$error ++;
				}
			}

			if (empty($error)) {

				// add contact to proposal
				if (! empty($this->sourcecontactid)) {
					// Contact client facturation commande
					$result = $order->add_contact($this->sourcecontactid, 100, 'external');
					if ($result < 0) {
						$this->errors[] = $order->error;
						$error ++;
					}
					// Contact client suivi commande
					$result = $order->add_contact($this->sourcecontactid, 101, 'external');
					if ($result < 0) {
						$this->errors[] = $order->error;
						$error ++;
					}
				}
				if (! empty($this->commercialid)) {
					// Responsable suivi commande client
					$result = $order->add_contact($this->commercialid, 91, 'internal');
					if ($result < 0) {
						$this->errors[] = $order->error;
						$error ++;
					}
				}
			}

			// Add average price
			if (empty($error)) {
				if ($conf->global->AGF_ADD_AVGPRICE_DOCPROPODR) {

					$order->fetch($neworderid);
					foreach ( $order->lines as $ordline ) {
						if ($ordline->fk_product == $this->fk_product) {
							$order_line = new OrderLine($this->db);
							$result = $order_line->fetch($ordline->id);
							if ($result < 0) {
								$this->errors[] = $order_line->error;
								$error ++;
							}
							// var_dump($order_line);
							// exit;
							$result = $this->getAvgPrice($order_line->total_ht, $order_line->total_ttc);
							if ($result < 0) {
								$error ++;
							}
							$order_line->desc .= $this->avgpricedesc;
							$result = $order_line->update(1);
							if ($result < 0) {
								$this->errors[] = $propal_line->error;
								$error ++;
							}
						}
					}
				}
			}
		}

		if (empty($error)) {

			// Link new order to the session/thridparty
			$agf = new Agefodd_session_element($this->db);
			$agf->fk_element = $neworderid;
			$agf->fk_session_agefodd = $this->id;
			$agf->fk_soc = $socid;
			$agf->element_type = 'order';

			$result = $agf->create($user);
			if ($result < 0) {
				$this->errors[] = $agf->error;
				$error ++;
			}
		}

		if (empty($error)) {
			$this->db->commit();
			return $neworderid;
		} else {
			$this->db->rollback();
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::" . __METHOD__ . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return - 1 * $error;
		}
	}

	/**
	 * Create order from session
	 *
	 * @param User $user that modify
	 * @param int $socid id
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function createProposal($user, $socid) {
		require_once (DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php');
		require_once (DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php');
		require_once (DOL_DOCUMENT_ROOT . '/product/class/product.class.php');
		require_once ('agefodd_session_element.class.php');
		require_once ('agefodd_session_stagiaire.class.php');
		require_once ('agefodd_opca.class.php');

		global $langs, $mysoc, $conf;

		$error = 0;

		$langs->load('agefodd@agefodd');
		$langs->load('main');

		// Define new propal
		$propal = new Propal($this->db);

		$soc = new Societe($this->db);
		$result = $soc->fetch($socid);
		if ($result < 0 || empty($soc->id)) {
			$this->error = $soc->error;
			return - 1;
		}

		$propal->thirdparty = $soc;
		$propal->socid = $socid;
		$propal->date = dol_now();
		if (! empty($soc->cond_reglement_id)) {
			$propal->cond_reglement_id = $soc->cond_reglement_id;
		} else {
			$propal->cond_reglement_id = 1;
		}
		if (! empty($soc->mode_reglement_id)) {
			$propal->mode_reglement_id = $soc->mode_reglement_id;
		} else {
			$propal->mode_reglement_id = 1;
		}
		$propal->duree_validite = $conf->global->PROPALE_VALIDITY_DURATION;
		$propal->modelpdf = $conf->global->PROPALE_ADDON_PDF;

		if (! empty($this->fk_product)) {

			$product = new Product($this->db);
			$result = $product->fetch($this->fk_product);
			if ($result < 0 || empty($product->id)) {
				$this->error = $product->error;
				return - 1;
			}

			$propal->lines[0] = new PropaleLigne($db);
			$propal->lines[0]->fk_product = $this->fk_product;

			if (! empty($this->intitule_custo)) {
				$desc = $this->intitule_custo . "\n";
			} else {
				$desc = $this->formintitule . "\n";
			}
			$refclient = dol_trunc($desc,35);

			$desc .= "\n" . dol_print_date($this->dated, 'day');

			$refclient .= "\n" . dol_print_date($this->dated, 'day');;
			if ($this->datef != $this->dated) {
				$desc .= '-' . dol_print_date($this->datef, 'day');
				$refclient .= '-' . dol_print_date($this->datef, 'day');
			}

			if (! empty($conf->global->AGF_REF_PROPAL_AUTO)) {
				$propal->ref_client = str_replace("\n",' ',$refclient);
			}

			if (! empty($this->duree_session)) {
				$desc .= "\n" . $langs->transnoentities('AgfPDFFichePeda1') . ': ' . $this->duree_session . ' ' . $langs->trans('Hour') . 's';
			}
			if (! empty($this->placecode)) {
				$desc .= "\n" . $langs->trans('AgfLieu') . ': ' . $this->placecode;
			}
			$session_trainee = new Agefodd_session_stagiaire($this->db);
			$session_trainee->fetch_stagiaire_per_session($this->id, $socid, 1);
			if (count($session_trainee->lines) > 0) {
				if ($conf->global->AGF_ADD_TRAINEE_NAME_INTO_DOCPROPODR) {
					$desc_trainee .= "\n";
					$nbtrainee = 0;
					foreach ( $session_trainee->lines as $line ) {

						if ($line->status_in_session != 5 && $line->status_in_session != 6) {
							$sessionOPCA = new Agefodd_opca($this->db);
							if ($this->type_session == 1) {
								$sessionOPCA->getOpcaForTraineeInSession($line->socid, $this->id);
							} else {
								$sessionOPCA->num_OPCA_file = $this->num_OPCA_file;
							}

							if (! empty($sessionOPCA->num_OPCA_file) && ! empty($conf->global->AGF_MANAGE_OPCA)) {
								$desc_trainee .= dol_strtoupper($line->nom) . ' ' . $line->prenom . '(' . $sessionOPCA->num_OPCA_file . ')' . "\n";
							} else {
								$desc_trainee .= dol_strtoupper($line->nom) . ' ' . $line->prenom . "\n";
							}
							$nbtrainee ++;
						}
					}

					$desc_trainee_head = "\n" . $nbtrainee . ' ';
					if ($nbtrainee > 1) {
						$desc_trainee_head .= $langs->trans('AgfParticipants');
					} else {
						$desc_trainee_head .= $langs->trans('AgfParticipant');
					}
				}
				$desc .= ' ' . $desc_trainee_head . ' ' . $desc_trainee;
			}

			$propal->lines[0]->desc = $desc;

			// For session inter set the quantity to number of trainee
			if ($this->type_session == 1 && count($session_trainee->lines) >= 1) {
				$propal->lines[0]->qty = count($session_trainee->lines);
			} else {
				$propal->lines[0]->qty = 1;
			}

			// Calculate price
			$tva_tx = get_default_tva($mysoc, $propal->thirdparty, $product->id);

			// multiprix
			if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($propal->thirdparty->price_level)) {
				$pu_ht = $product->multiprices[$propal->thirdparty->price_level];
				$pu_ttc = $product->multiprices_ttc[$propal->thirdparty->price_level];
				$price_min = $product->multiprices_min[$propal->thirdparty->price_level];
				$price_base_type = $product->multiprices_base_type[$propal->thirdparty->price_level];
			} elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
				$sql = "SELECT ";
				$sql .= ' pcp.rowid as idprodcustprice, pcp.price as custprice, pcp.price_ttc as custprice_ttc, pcp.price_min as custprice_min,';
				$sql .= ' pcp.price_base_type as custprice_base_type, pcp.tva_tx  as custtva_tx';
				$sql .= " FROM " . MAIN_DB_PREFIX . "product_customer_price as pcp WHERE pcp.fk_soc=" . $soc->id . " AND pcp.fk_product=" . $this->fk_product;
				dol_syslog(get_class($this) . "::createProposal", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					if ($this->db->num_rows($resql)) {
						$obj = $this->db->fetch_object($resql);
						$pu_ht = $obj->custprice;
						$pu_ttc = $obj->custprice_ttc;
						$price_min = $obj->custprice_min;
						$price_base_type = $obj->custprice_base_type;
						$tva_tx = $obj->custtva_tx;
					} else {
						$pu_ht = $product->price;
						$pu_ttc = $product->price_ttc;
						$price_min = $product->price_min;
						$price_base_type = $product->price_base_type;
					}
					$this->db->free($resql);
				} else {
					$pu_ht = $product->price;
					$pu_ttc = $product->price_ttc;
					$price_min = $product->price_min;
					$price_base_type = $product->price_base_type;
				}
			} else {
				$pu_ht = $product->price;
				$pu_ttc = $product->price_ttc;
				$price_min = $product->price_min;
				$price_base_type = $product->price_base_type;
			}

			$propal->lines[0]->subprice = $pu_ht;
			$propal->lines[0]->tva_tx = $tva_tx;

			// Add relative discount is exists on soc
			if (! empty($soc->remise_percent)) {
				$propal->lines[0]->remise_percent = $soc->remise_percent;
			}

			// dol_syslog ( get_class ( $this ) . "::createProposal propal->lines=" . var_export ( $propal->lines [0], true ), LOG_DEBUG );
		}

		$this->db->begin();

		$newpropalid = $propal->create($user);
		if ($newpropalid < 0) {
			$this->errors[] = $propal->error;
			$error ++;
		}

		if (empty($error)) {

			// add contact to proposal
			if (! empty($this->sourcecontactid)) {
				// Contact client facturation propale
				$result = $propal->add_contact($this->sourcecontactid, 40, 'external');
				if ($result < 0) {
					$this->errors[] = $propal->error;
					//$error ++;
				}
				// Contact client suivi propale
				$result = $propal->add_contact($this->sourcecontactid, 41, 'external');
				if ($result < 0) {
					$this->errors[] = $propal->error;
					//$error ++;
				}
			}
			if (! empty($this->commercialid)) {
				// Commercial suivi propale
				$result = $propal->add_contact($this->commercialid, 31, 'internal');
				if ($result < 0) {
					$this->errors[] = $propal->error;
					//$error ++;
				}
			}
		}

		if (empty($error)) {
			if ($conf->global->AGF_ADD_AVGPRICE_DOCPROPODR) {

				$propal->fetch($newpropalid);
				$propal_line = new PropaleLigne($this->db);
				$result = $propal_line->fetch($propal->lines[0]->rowid);
				if ($result < 0) {
					$this->errors[] = $propal_line->error;
					$error ++;
				}
				$result = $this->getAvgPrice($propal_line->total_ht, $propal_line->total_ttc);
				if ($result < 0) {
					$error ++;
				}
				$propal_line->desc .= $this->avgpricedesc;
				$result = $propal_line->update(1);
				if ($result < 0) {
					$this->errors[] = $propal_line->error;
					$error ++;
				}
			}
		}

		if (empty($error)) {

			// Link new order to the session/thridparty

			$agf = new Agefodd_session_element($this->db);
			$agf->fk_element = $newpropalid;
			$agf->fk_session_agefodd = $this->id;
			$agf->fk_soc = $socid;
			$agf->element_type = 'propal';

			$result = $agf->create($user);
			if ($result < 0) {
				$this->errors[] = $agf->error;
				$error ++;
			}
		}

		if (empty($error)) {
			$this->db->commit();
			return $propal->id;
		} else {
			$this->db->rollback();
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::createProposal " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return - 1 * $error;
		}
	}

	/**
	 * getAvgPrice
	 *
	 * @param number $priceht
	 * @param number $pricettc
	 * @return number
	 */
	public function getAvgPrice($priceht = 0, $pricettc = 0) {
		global $conf, $langs;
		// Calc nb hour of a session
		require_once 'agefodd_session_calendrier.class.php';
		$calendrier = new Agefodd_sesscalendar($this->db);
		$result = $calendrier->fetch_all($this->id);
		if ($result < 0) {
			$this->error = $calendrier->error;
			return - 1;
		}
		$duree = 0;
		for($i = 0; $i < count($calendrier->lines); $i ++) {
			$duree += ($calendrier->lines[$i]->heuref - $calendrier->lines[$i]->heured);
		}
		$min = floor($duree / 60);
		$rmin = sprintf("%02d", $min % 60);
		$hour = floor($min / 60);

		$this->avgpricedesc = '';
		if (! empty($hour)) {
			$this->avgpricedesc = "\n" . $langs->trans('AgfTaxHourHT') . ':' . price($priceht / $hour, 0, $langs, 1, - 1, 2) . $langs->getCurrencySymbol($conf->currency);
			$this->avgpricedesc .= "\n" . $langs->trans('AgfTaxHourTTC') . ':' . price($pricettc / $hour, 0, $langs, 1, - 1, 2) . $langs->getCurrencySymbol($conf->currency);
		} /*else {
		   $this->avgpricedesc="\n" .$langs->trans('AgfTaxHourHT').':N/A';
		   $this->avgpricedesc.="\n" .$langs->trans('AgfTaxHourTTC').':'.price($pricettc/$hour);
		   }*/

		return 1;
	}

	/**
	 * Create invoice from session
	 *
	 * @param User $user that modify
	 * @param int $socid id
	 * @param int $frompropalid from proposal
	 * @param number $amount to affect to session product
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function createInvoice($user, $socid, $frompropalid = 0, $amount = 0) {
		require_once (DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php');
		require_once (DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php');
		require_once (DOL_DOCUMENT_ROOT . '/product/class/product.class.php');
		require_once ('agefodd_session_element.class.php');
		require_once ('agefodd_session_stagiaire.class.php');
		require_once ('agefodd_opca.class.php');

		global $langs, $mysoc, $conf;

		$error = 0;

		// Define new invoice
		$invoice = new Facture($this->db);

		$soc = new Societe($this->db);
		$result = $soc->fetch($socid);
		if ($result < 0 || empty($soc->id)) {
			$this->errors[] = $soc->error;
			$error ++;
		}

		$this->db->begin();

		$invoice->thirdparty = $soc;

		$invoice->socid = $socid;
		$invoice->date = dol_now();
		if (! empty($soc->cond_reglement_id)) {
			$invoice->cond_reglement_id = $soc->cond_reglement_id;
		} else {
			$invoice->cond_reglement_id = 1;
		}
		if (! empty($soc->mode_reglement_id)) {
			$invoice->mode_reglement_id = $soc->mode_reglement_id;
		} else {
			$invoice->mode_reglement_id = 1;
		}
		// $invoice->duree_validite = $conf->global->PROPALE_VALIDITY_DURATION;
		$invoice->modelpdf = $conf->global->FACTURE_ADDON_PDF;

		if (! empty($this->fk_product)) {

			$product = new Product($this->db);
			$result = $product->fetch($this->fk_product);
			if ($result < 0 || empty($product->id)) {
				$this->error = $product->error;
				$error ++;
			}

			$invoice->lines[0] = new FactureLigne($this->db);
			$invoice->lines[0]->fk_product = $this->fk_product;

			if (! empty($this->intitule_custo)) {
				$desc = $this->intitule_custo . "\n";
			} else {
				$desc = $this->formintitule . "\n";
			}
			$desc .= "\n" . dol_print_date($this->dated, 'day');
			if ($this->datef != $this->dated) {
				$desc .= '-' . dol_print_date($this->datef, 'day');
			}
			if (! empty($this->duree_session)) {
				$desc .= "\n" . $langs->transnoentities('AgfPDFFichePeda1') . ': ' . $this->duree_session . ' ' . $langs->trans('Hour') . '(s)';
			}
			if (! empty($this->placecode)) {
				$desc .= "\n" . $langs->trans('AgfLieu') . ': ' . $this->placecode;
			}

			// Determine if we are doing update invoice line for thridparty as OPCA in session or just customer
			// For Intra entreprise you take all trainne
			$sessionOPCA = new Agefodd_opca($this->db);
			if (empty($conf->global->AGF_MANAGE_OPCA) || $this->type_session == 0) {
				// For Intra entreprise you take all trainne
				$find_trainee_by_OPCA = false;
				$sessionOPCA->num_OPCA_file = $agf->num_OPCA_file;
				$invoice_soc_id = null;
			} elseif ($this->type_session == 1) {

				$result = $sessionOPCA->getOpcaSession($this->id);
				if ($result < 0) {
					$this->errors[] = $sessionOPCA->error;
					$error ++;
				}
				if (is_array($sessionOPCA->lines) && count($sessionOPCA->lines) > 0) {
					foreach ( $sessionOPCA->lines as $line ) {
						if ($line->fk_soc_OPCA == $invoice->socid) {
							$find_trainee_by_OPCA = true;
							break;
						}
					}
				}

				$invoice_soc_id = $invoice->socid;
			}

			$session_trainee = new Agefodd_session_stagiaire($this->db);
			if ($find_trainee_by_OPCA) {
				$session_trainee->fetch_stagiaire_per_session_per_OPCA($this->id, $invoice_soc_id);
			} else {
				$session_trainee->fetch_stagiaire_per_session($this->id, $invoice_soc_id, 1);
			}

			if (count($session_trainee->lines) > 0) {

				if ($conf->global->AGF_ADD_TRAINEE_NAME_INTO_DOCPROPODR) {
					$desc_trainee .= "\n";
					$num_OPCA_file_array=array();
					foreach ( $session_trainee->lines as $line ) {

						// Do not output not present or cancelled trainee
						if ($line->status_in_session != 5 && $line->status_in_session != 6) {
							if ($this->type_session == 1) {
								$sessionOPCA->getOpcaForTraineeInSession($line->socid, $this->id, $line->stagerowid);
								$soc_name = $line->socname;
							} else {
								// For Intra entreprise get OPCA and customer of trainning
								$sessionOPCA->num_OPCA_file = $this->num_OPCA_file;
								$socsatic = new Societe($this->db);
								$result = $socsatic->fetch($this->socid);
								$soc_name = $socsatic->name;
							}
							if (! empty($sessionOPCA->num_OPCA_file) && ! empty($conf->global->AGF_MANAGE_OPCA)) {
								if (!array_key_exists($sessionOPCA->num_OPCA_file, $num_OPCA_file_array)) {
									$desc_OPCA .= "\n" . $langs->trans('AgfNumDossier') . ' : ' . $sessionOPCA->num_OPCA_file . ' ' . $langs->trans('AgfInTheNameOf') . ' ' . $soc_name;
									$num_OPCA_file_array[$sessionOPCA->num_OPCA_file]=$soc_name;
								}
							}
							$desc_trainee .= dol_strtoupper($line->nom) . ' ' . $line->prenom . "\n";
						}
					}
				}
				$desc .= ' ' . $desc_OPCA . $desc_trainee;
			}
			$invoice->lines[0]->desc = $desc;

			// For session inter set the quantity to number of trainee
			if ($this->type_session == 1 && count($session_trainee->lines) >= 1 && empty($amount)) {
				$invoice->lines[0]->qty = count($session_trainee->lines);
			} else {
				$invoice->lines[0]->qty = 1;
			}

			// Calculate price
			$tva_tx = get_default_tva($mysoc, $invoice->thirdparty, $product->id);

			if (! empty($frompropalid)) {
				require_once (DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php');
				$propal = new Propal($this->db);
				$propal->fetch($frompropalid);
				$soc->id = $propal->socid;

				if (! empty($propal->id) && is_array($propal->lines) && count($propal->lines) > 0) {
					foreach ( $propal->lines as $line ) {
						if ($line->fk_product == $product->id) {
							$amount = $line->total_ht;
							$invoice->lines[0]->qty = 1;

							dol_syslog(get_class($this) . "::createInvoice from propal amount=" . $amount, LOG_DEBUG);
						}
					}
				}
			}

			if (empty($amount)) {
				// multiprix
				if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($propal->thirdparty->price_level)) {
					$pu_ht = $product->multiprices[$invoice->thirdparty->price_level];
					$pu_ttc = $product->multiprices_ttc[$invoice->thirdparty->price_level];
					$price_min = $product->multiprices_min[$invoice->thirdparty->price_level];
					$price_base_type = $product->multiprices_base_type[$invoice->thirdparty->price_level];
				} elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
					$sql = "SELECT ";
					$sql .= ' pcp.rowid as idprodcustprice, pcp.price as custprice, pcp.price_ttc as custprice_ttc, pcp.price_min as custprice_min,';
					$sql .= ' pcp.price_base_type as custprice_base_type, pcp.tva_tx  as custtva_tx';
					$sql .= " FROM " . MAIN_DB_PREFIX . "product_customer_price as pcp WHERE pcp.fk_soc=" . $soc->id . " AND pcp.fk_product=" . $this->fk_product;
					dol_syslog(get_class($this) . "::createInvoice", LOG_DEBUG);
					$resql = $this->db->query($sql);
					if ($resql) {
						if ($this->db->num_rows($resql)) {
							$obj = $this->db->fetch_object($resql);
							$pu_ht = $obj->custprice;
							$pu_ttc = $obj->custprice_ttc;
							$price_min = $obj->custprice_min;
							$price_base_type = $obj->custprice_base_type;
							$tva_tx = $obj->custtva_tx;

							// dol_syslog ( get_class ( $this ) . "::createInvoice PRODUIT_CUSTOMER_PRICE pu_ttc=" . $pu_ttc, LOG_DEBUG );
						} else {
							$pu_ht = $product->price;
							$pu_ttc = $product->price_ttc;
							$price_min = $product->price_min;
							$price_base_type = $product->price_base_type;

							// dol_syslog ( get_class ( $this ) . "::createInvoice product=" . var_export ( $product, true ), LOG_DEBUG );
						}
						$this->db->free($resql);
					} else {
						$pu_ht = $product->price;
						$pu_ttc = $product->price_ttc;
						$price_min = $product->price_min;
						$price_base_type = $product->price_base_type;
						// dol_syslog ( get_class ( $this ) . "::createInvoice si PRODUIT_CUSTOMER_PRICE resql=false pu_ttc=" . $pu_ttc, LOG_DEBUG );
					}
				} else {
					$pu_ht = $product->price;
					$pu_ttc = $product->price_ttc;
					$price_min = $product->price_min;
					$price_base_type = $product->price_base_type;
					// dol_syslog ( get_class ( $this ) . "::createInvoice si NON PRODUIT_CUSTOMER_PRICE pu_ttc=" . $pu_ttc, LOG_DEBUG );
				}
			} else {
				$pu_ht = price2num($amount, 'MU');
				$pu_ttc = price2num(price2num($amount) + (($tva_tx * price2num($amount)) / 100), 'MU');
				$price_min = $product->price_min;
				$price_base_type = $product->price_base_type;
				// dol_syslog ( get_class ( $this ) . "::createInvoice si amount non empty comme from propal tva_tx=".$tva_tx." price2num(amount)=".price2num($amount)." pu_ttc=" . $pu_ttc, LOG_DEBUG );
			}

			$invoice->lines[0]->total_ht = $pu_ht * $invoice->lines[0]->qty;
			$invoice->lines[0]->total_ttc = $pu_ttc * $invoice->lines[0]->qty;
			$invoice->lines[0]->total_tva = $invoice->lines[0]->total_ttc - $invoice->lines[0]->total_ht;
			$invoice->lines[0]->subprice = $pu_ht;
			$invoice->lines[0]->tva_tx = $tva_tx;

			// Add relative discount is exists on soc
			if (! empty($soc->remise_percent)) {
				$invoice->lines[0]->remise_percent = $soc->remise_percent;
			}

			// dol_syslog ( get_class ( $this ) . "::createInvoice invoice->lines=" . var_export ( $invoice->lines [0], true ), LOG_DEBUG );
		}

		if (! empty($frompropalid)) {
			require_once (DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php');
			$propal = new Propal($this->db);
			$propal->fetch($frompropalid);

			if (! empty($propal->id) && is_array($propal->lines) && count($propal->lines) > 0) {
				foreach ( $propal->lines as $line ) {
					if ($line->fk_product != $product->id || empty($this->fk_product)) {
						$invoiceline = new FactureLigne($this->db);
						$invoiceline->fk_product = $line->fk_product;
						$invoiceline->qty = $line->qty;
						$invoiceline->desc = $line->desc;
						$invoiceline->total_ht = $line->total_ht;
						$invoiceline->total_ttc = $line->total_ttc;
						$invoiceline->total_tva = $line->total_tva;
						$invoiceline->subprice = $line->subprice;
						$invoiceline->tva_tx = $line->tva_tx;
						$invoice->lines[] = $invoiceline;
						dol_syslog(get_class($this) . "::createInvoice invoiceline=" . var_export($invoiceline, true), LOG_DEBUG);
					}
				}
			}

			$invoice->linked_objects=array('propal'=>$frompropalid);

			$invoice->note_public = $propal->note_public;
		}

		if (empty($error)) {
			$newinvoiceid = $invoice->create($user);
			if ($newinvoiceid < 0) {
				$this->errors[] = $invoice->error;
				$error ++;
			}
		}

		if (empty($error)) {

			if (! empty($this->commercialid)) {
				// Commercial suivi propale
				$result = $invoice->add_contact($this->commercialid, 50, 'internal');
				if ($result < 0) {
					$this->errors[] = $invoice->error;
					$error ++;
				}
			}
		}

		if (empty($error)) {
			// Link new order to the session/thridparty

			$agf = new Agefodd_session_element($this->db);
			$agf->fk_element = $newinvoiceid;
			$agf->fk_session_agefodd = $this->id;
			$agf->fk_soc = $socid;
			$agf->element_type = 'invoice';

			$result = $agf->create($user);
			if ($result < 0) {
				$this->errors[] = $agf->error;
				$error ++;
			}
		}

		// Add average price on all line concern by session training product
		if (empty($error)) {
			if ($conf->global->AGF_ADD_AVGPRICE_DOCPROPODR) {

				$invoice->fetch($newinvoiceid);

				foreach ( $invoice->lines as $invline ) {
					if ($invline->fk_product == $this->fk_product) {
						$invoice_line = new FactureLigne($this->db);
						$result = $invoice_line->fetch($invline->id);
						if ($result < 0) {
							$this->errors[] = $invoice_line->error;
							$error ++;
						}
						$result = $this->getAvgPrice($invoice_line->total_ht, $invoice_line->total_ttc);
						if ($result < 0) {
							$error ++;
						}
						$invoice_line->desc .= $this->avgpricedesc;

						//TODO : fix this into fetch from dolibarr
						if (empty($invoice_line->multicurrency_subprice)) $invoice_line->multicurrency_subprice=0;
						if (empty($invoice_line->multicurrency_total_ht)) $invoice_line->multicurrency_total_ht=0;
						if (empty($invoice_line->multicurrency_total_tva)) $invoice_line->multicurrency_total_tva=0;
						if (empty($invoice_line->multicurrency_total_ttc)) $invoice_line->multicurrency_total_ttc=0;
						$result = $invoice_line->update(1);
						if ($result < 0) {
							$this->errors[] = $invoice_line->error;
							$error ++;
						}
					}
				}
			}
		}

		if (empty($error)) {
			$this->db->commit();
			return $invoice->id;
		} else {
			$this->db->rollback();
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::createProposal " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return - 1 * $error;
		}
	}

	/**
	 * Return send by mail propal max date
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function findDateSendPropal() {
		$sql = "SELECT MAX(act.datep) as maxdate";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element as elem";
		$sql .= " INNER JOIN  " . MAIN_DB_PREFIX . "actioncomm as act ON act.fk_element=elem.fk_element ";
		$sql .= " AND elem.element_type='propal' AND act.elementtype='propal'";
		$sql .= " AND act.code='AC_PROPAL_SENTBYMAIL'";
		$sql .= " AND elem.fk_session_agefodd=" . $this->rowid;

		dol_syslog(get_class($this) . "::findDateSendPropal", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$returndate = $this->db->jdate($obj->maxdate);
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::findDateSendPropal " . $this->error, LOG_ERR);
			return - 1;
		}

		return $returndate;
	}

	/**
	 * Return send by sign propal max date
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function findDateSignPropal() {
		$sql = "SELECT MAX(propal.date_cloture) as maxdate";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element as elem";
		$sql .= " INNER JOIN  " . MAIN_DB_PREFIX . "propal as propal ON propal.rowid=elem.fk_element ";
		$sql .= " AND propal.fk_statut=2 AND elem.element_type='propal'";
		$sql .= " AND elem.fk_session_agefodd=" . $this->rowid;

		dol_syslog(get_class($this) . "::findDateSignPropal", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$returndate = $this->db->jdate($obj->maxdate);
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::findDateSignPropal " . $this->error, LOG_ERR);
			return - 1;
		}

		return $returndate;
	}

	/**
	 *
	 * @return string
	 */
	public function libSessionDate($dateformat=''){
		global $langs;

		$langs->load('agefodd@agefodd');

		$date_conv='';

		if ($this->dated == $this->datef) {
			$date_conv = $langs->transnoentities('AgfPDFFichePres8') . " " . dol_print_date($this->datef, $dateformat);
		} else {
			$date_conv = $langs->transnoentities('AgfPDFFichePres9') . " " . dol_print_date($this->dated, $dateformat) . ' ' . $langs->transnoentities('AgfPDFFichePres10') . ' ' . dol_print_date($this->datef, $dateformat);
		}

		return $date_conv;
	}

	/**
	 */
	public function fetchOtherSessionSameplacedate() {
		$this->lines_place = array ();

		$place_to_test = array ();

		$sql = 'SELECT rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_place WHERE control_occupation IS NOT NULL';
		dol_syslog(get_class($this) . "::" . __METHOD__ . " sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$place_to_test[] = $obj->rowid;
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}

		$sql = "SELECT ";
		$sql .= "DISTINCT agcal.date_session,agcal.heured,agcal.heuref FROM " . MAIN_DB_PREFIX . "agefodd_session_calendrier as agcal";
		$sql .= " WHERE  agcal.fk_agefodd_session=" . $this->id;
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {

				$date_to_test_array[] = array('dated'=>$this->db->jdate($obj->heured),'datef'=>$this->db->jdate($obj->heuref));
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}


		if (count($date_to_test_array) == 0) {
			$date_to_test_array[] = array (
					'dated' => $this->dated,
					'datef' => $this->datef
			);
		}

		if (! empty($this->id) && ! empty($this->fk_session_place) && in_array($this->fk_session_place, $place_to_test)) {
			foreach ( $date_to_test_array as $date_data ) {

				$sql = "SELECT ";
				$sql .= "DISTINCT ag.rowid FROM " . MAIN_DB_PREFIX . "agefodd_session as ag ";
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as agcal ON ag.rowid=agcal.fk_agefodd_session";
				$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_status_type as agf_status ON (ag.status = agf_status.rowid  AND agf_status.code<>\'NOT\')';
				$sql .= " WHERE ag.fk_session_place=" . $this->fk_session_place;
				$sql .= " AND (ag.dated BETWEEN '" . $this->db->idate($date_data['dated']) . "' AND '" . $this->db->idate($date_data['datef']) . "') ";
				$sql .= " AND (ag.datef BETWEEN '" . $this->db->idate($date_data['dated']) . "' AND '" . $this->db->idate($date_data['datef']) . "') ";

				dol_syslog(get_class($this) . "::" . __METHOD__ . " sql=" . $sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					while ( $obj = $this->db->fetch_object($resql) ) {
						$line = new AgfSessionLine();
						$line->rowid = $obj->rowid;
						//$line->typeevent='session';
						$this->lines_place[] = $line;
					}
				} else {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
					return - 1;
				}
			}

			//find event on calendar (not only session)
			/*foreach ( $date_to_test_array as $date_data ) {

				$sql = "SELECT ";
				$sql .= "DISTINCT actcomm.id as rowid FROM " . MAIN_DB_PREFIX . "actioncomm as actcomm ";
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "actioncomm_extrafields as actcomm_extra ON actcomm.id=actcomm_extra.fk_object";
				$sql .= " WHERE actcomm_extra.location=" . $this->fk_session_place;
				$sql .= " AND (actcomm.datep BETWEEN '" . $this->db->idate($date_data['dated']) . "' AND '" . $this->db->idate($date_data['datef']) . "') ";
				$sql .= " AND (actcomm.datep2 BETWEEN '" . $this->db->idate($date_data['dated']) . "' AND '" . $this->db->idate($date_data['datef']) . "') ";

				dol_syslog(get_class($this) . "::" . __METHOD__ . " sql=" . $sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					while ( $obj = $this->db->fetch_object($resql) ) {
						$line = new AgfSessionLine();
						$line->rowid = $obj->rowid;
						$line->typeevent='actioncomm';
						$this->lines_place[] = $line;
					}
				} else {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
					return - 1;
				}
			}*/

			return 1;
		} else {

			return 1;
		}
	}

	function load_all_data_agefodd_session(&$object_refletter, $socid='', $obj_agefodd_convention='', $print_r=false) {

		global $db, $conf;

		if($object_refletter->element_type === 'rfltr_agefodd_contrat_trainer' || $object_refletter->element_type === 'rfltr_agefodd_mission_trainer') $id_trainer = $socid;
		if($object_refletter->element_type === 'rfltr_agefodd_convocation_trainee' || $object_refletter->element_type === 'rfltr_agefodd_attestation_trainee' || $object_refletter->element_type === 'rfltr_agefodd_attestationendtraining_trainee') $id_trainee = $socid;
		//elseif($object_refletter->element_type === 'rfltr_agefodd_mission_trainer') $id_trainer = $socid; TODO quand on aura créé le modèle par participant

		// Chargement des participants
		if(empty($this->TStagiairesSession)) {
			dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
			$stagiaires = new Agefodd_session_stagiaire($db);
			$stagiaires->fetch_stagiaire_per_session($this->id);
			$this->TStagiairesSession = $stagiaires->lines;
		}

			// Chargement des spécifique participants
		if (! empty($obj_agefodd_convention) && $obj_agefodd_convention->id > 0) {
			dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
			if (is_array($obj_agefodd_convention->line_trainee) && count($obj_agefodd_convention->line_trainee) > 0) {
				$nbstag = count($obj_agefodd_convention->line_trainee);
				$stagiaires_session_conv = new Agefodd_session_stagiaire($this->db);

				foreach ($obj_agefodd_convention->line_trainee as $trainee_session_id) {
					$result = $stagiaires_session_conv->fetch($trainee_session_id);
					if ($result < 0) {
						setEventMessage($stagiaires->error, 'errors');
					}
					$stagiaire_conv = new Agefodd_stagiaire($this->db);
					$result = $stagiaire_conv->fetch($stagiaires_session_conv->fk_stagiaire);
					if ($result < 0) {
						setEventMessage($stagiaire_conv->error, 'errors');
					}
					$this->TStagiairesSessionConvention[]= $stagiaire_conv;
				}
			}
		}

		if(empty($this->TStagiairesSessionSoc)) {
			dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
			$stagiaires = new Agefodd_session_stagiaire($db);
			$stagiaires->fetch_stagiaire_per_session($this->id,$socid);
			$this->TStagiairesSessionSoc = $stagiaires->lines;
		}

		//Trainee link to the company convention
		$this->signataire_intra = ucfirst(strtolower($this->contactcivilite)) . ' ' . $this->contactname;
		$stagiaires = new Agefodd_session_stagiaire($db);
		$result=$stagiaires->fetch_stagiaire_per_session($this->id,$socid,1);
		if ($result<0) {
			setEventMessage($stagiaires->error,'errors');
		} else {
			$this->signataire_inter_array=array();
			if (is_array($stagiaires->lines) && count($stagiaires->lines)>0) {

				foreach ($stagiaires->lines as $line) {
					if (!empty($line->fk_socpeople_sign)) {
						$socpsign=new Contact($db);
						$socpsign->fetch($line->fk_socpeople_sign);
						$this->signataire_inter_array[$line->fk_socpeople_sign]= $socpsign->getFullName($langs).' ';
					}
				}

			}
			if (count($this->signataire_inter_array)>0) {
				$this->signataire_inter=implode(', ',$this->signataire_inter_array);
				unset($this->signataire_inter_array);
			}
		}

		if(empty($this->TStagiairesSessionSocMore)) {
			dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
			$stagiaires = new Agefodd_session_stagiaire($db);
			$stagiaires->fetch_stagiaire_per_session($this->id,$socid,1);
			$this->TStagiairesSessionSocMore = $stagiaires->lines;
		}

		// Chargement des horaires de la session
		if(empty($this->THorairesSession)) {
			dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
			$calendrier = new Agefodd_sesscalendar($db);
			$calendrier->fetch_all($this->id);
			$this->THorairesSession = $calendrier->lines;
			if (is_array($calendrier->lines) && count($calendrier->lines)>0) {
				foreach ($calendrier->lines as $line) {
					$dates[$line->date_session]=$line->date_session;
				}
				$this->trainer_day_cost=$this->cost_trainer / count($dates);
			}
		}

		if(empty($this->TFormateursSession)) {
			dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
			$formateurs = new Agefodd_session_formateur($db);
			$nbform = $formateurs->fetch_formateur_per_session($this->id);
			$this->TFormateursSession = $formateurs->lines;
		}

		if(empty($this->lieu)) {
			dol_include_once('/agefodd/class/agefodd_place.class.php');
			$agf_place= new Agefodd_place($db);
			$agf_place->fetch($this->placeid);
			$this->lieu = $agf_place;
		}

		if(empty($this->formation)){
		    dol_include_once('agefodd/class/agefodd_formation_catalogue.class.php');
		    $formation = new Agefodd($db);
		    $formation->fetch($this->fk_formation_catalogue);
		    $this->formation = $formation;
		}

		if(!empty($id_trainer)) {
		    dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');

			$agf_session_trainer = new Agefodd_session_formateur($this->db);
			$agf_session_trainer->fetch($id_trainer);

			$this->formateur_session = $agf_session_trainer;
			$this->formateur_session_societe = $agf_session_trainer->thirdparty;

		}

		if(!empty($id_trainee)) {
		    dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');

		    $trainee = new Agefodd_stagiaire($db);
		    $trainee->fetch($id_trainee);
		    $this->stagiaire = $trainee;
		}

		if(!empty($socid)) {
			$document_thirdparty = new Societe($db);
			$document_thirdparty->fetch($socid);
			$this->document_societe= $document_thirdparty;

		}

		foreach($conf->global as $conf_name=>$osef) {

			if(strpos($conf_name, 'AGF_') !== false) {
				$this->{$conf_name} = $conf->global->{$conf_name};
			}
		}

		if($print_r) {
			echo '<pre>';
			print_r($this);
			echo '</pre>';
			exit;
		}

	}
}

/**
 * Session Thridparty Link Class
 */
class AgfSocLine
{
	public $sessid;
	public $socname;
	public $socid;
	public $type_session;
	public $is_OPCA;
	public $fk_soc_OPCA;
	public $code_client;
	public $typeline;
	public $trainee_array = array ();
	public function __construct() {
		return 1;
	}
}

/**
 * Session Invoice Order Link Class
 */
class AgfInvoiceOrder
{
	public $rowid;
	public $socid;
	public $type_session;
	public $is_date_res_site;
	public $is_date_res_trainer;
	public $date_res_trainer;
	public $fk_session_place;
	public $dated;
	public $datef;
	public $intitule;
	public $ref;
	public $ref_interne;
	public $color;
	public $nb_stagiaire;
	public $force_nb_stagiaire;
	public $notes;
	public $invoiceref;
	public $orderref;
	public $propalref;
	public $duree_session;
	public $intitule_custom;
	public $fourninvoiceref;
	public function __construct() {
		return 1;
	}
}

/**
 * Session line Class
 */
class AgfSessionLine
{
	public $rowid;
	public $socid;
	public $socname;
	public $trainerrowid;
	public $type_session;
	public $is_date_res_site;
	public $is_date_res_confirm_site;
	public $is_date_res_trainer;
	public $date_res_trainer;
	public $fk_session_place;
	public $dated;
	public $datef;
	public $intitule;
	public $intitule_custom;
	public $ref;
	public $ref_interne;
	public $color;
	public $nb_stagiaire;
	public $force_nb_stagiaire;
	public $notes;
	public $nb_subscribe_min;
	public $nb_prospect;
	public $nb_confirm;
	public $nb_cancelled;
	public $statuslib;
	public $statuscode;
	public $status_in_session;
	public $realdurationsession;
	public $duree_session;
	public $status;
	public $trainer_status;
	public $trainersessionid;
	public $contactid;
	public $sell_price;
	public $invoice_amount;
	public $datec;
	public $cost_trainer;
	public $cost_other;
	public $cost_sell_charges;
	public $cost_buy_charges;
	public $socrequesterid;
	public $socrequestername;
	public $fk_socpeople_requester;
	public $admin_task_close_session;
	public $trainingcolor;
	public $fk_soc_employer;
	public function __construct() {
		return 1;
	}
}

/**
 * Session line Class
 */
class AgfSessionLineTask
{
	public $rowid;
	public $socid;
	public $socname;
	public $trainerrowid;
	public $type_session;
	public $is_date_res_site;
	public $is_date_res_trainer;
	public $date_res_trainer;
	public $fk_session_place;
	public $dated;
	public $datef;
	public $intitule;
	public $intitule_custom;
	public $ref;
	public $ref_interne;
	public $color;
	public $nb_stagiaire;
	public $force_nb_stagiaire;
	public $notes;
	public $task0;
	public $task1;
	public $task2;
	public $task3;
	public $statuslib;
	public $statuscode;
	public $status_in_session;
	public $realdurationsession;
	public $duree_session;
	public $socrequesterid;
	public $socrequestername;
	public $fk_soc_employer;
	public function __construct() {
		return 1;
	}
}

/**
 * Session line Class for list by soc
 */
class AgfSessionLineSoc
{
	public $rowid;
	public $socid;
	public $socname;
	public $trainerrowid;
	public $type_session;
	public $is_date_res_site;
	public $is_date_res_trainer;
	public $date_res_trainer;
	public $fk_session_place;
	public $dated;
	public $datef;
	public $intitule;
	public $ref;
	public $ref_interne;
	public $color;
	public $nb_stagiaire;
	public $force_nb_stagiaire;
	public $notes;
	public $nb_subscribe_min;
	public $type_affect;
	public $statuslib;
	public $statuscode;
	public $status_in_session;
	public $active;
	public $duree_session;
	public $intitule_custom;
	public function __construct() {
		return 1;
	}
}

/**
 * Session line Class for list by soc
 */
class AgfSessionLineInter
{
	public $id;
	public $fk_session_place;
	public $dated;
	public $status;
	public $statuslib;
	public $statuscode;
	public $color;
	public $force_nb_stagiaire;
	public $nb_stagiaire;
	public $notes;
	public $intitule;
	public $ref;
	public $trainingrefinterne;
	public $nb_subscribe_min;
	public $ref_interne;
	public $trainerrowid;
	public $intitule_custo;
	public $duree_session;
	public $trainer_status;
	public $nb_prospect;
	public $nb_confirm;
	public $nb_cancelled;
	public $trainerrn;
	public $convoc;
	public $support;
	public $ffeedit;
	public $attrn;
	public $ffeenv;
	public $invtrainer;
	public $invroom;
	public $is_date_res_site;
	public $is_date_res_confirm_site;
	public $sell_price;
	public $fk_soc_employer;
	public function __construct() {
		return 1;
	}
}
