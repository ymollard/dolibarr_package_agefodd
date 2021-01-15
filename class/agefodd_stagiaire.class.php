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
 * \file agefodd/class/agefodd_stagiaire.class.php
 * \ingroup agefodd
 * \brief Manage trainee object
 */
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once (DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");

/**
 * Trainee Class
 */
class Agefodd_stagiaire extends CommonObject {
	public $error;
	public $errors = array ();
	public $element = 'agefodd';
	public $table_element = 'agefodd_stagiaire';
	public $id;
	public $entity;
	public $ismultientitymanaged = 1; // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	public $nom;
	public $prenom;
	public $civilite;
	public $fonction;
	public $tel1;
	public $tel2;
	public $mail;
	public $note;
	public $date_birth;
	public $place_birth;
	public $socid;
	public $socname;
	public $socaddr;
	public $soczip;
	public $soctown;
	public $fk_socpeople;
	public $lines = array ();
	public $disable_auto_mail;

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
		if (isset($this->nom))
			$this->nom = $this->db->escape(trim($this->nom));
		if (isset($this->prenom))
			$this->prenom = $this->db->escape(trim($this->prenom));
		if (isset($this->fonction))
			$this->fonction = $this->db->escape(trim($this->fonction));
		if (isset($this->tel1))
			$this->tel1 = $this->db->escape(trim($this->tel1));
		if (isset($this->tel2))
			$this->tel2 = $this->db->escape(trim($this->tel2));
		if (isset($this->mail))
			$this->mail = $this->db->escape(trim($this->mail));
		if (isset($this->note))
			$this->note = $this->db->escape(trim($this->note));
		if (isset($this->place_birth))
			$this->place_birth = $this->db->escape(trim($this->place_birth));

			// Check parameters
			// Put here code to add control on parameters value
		$this->nom = mb_strtoupper($this->nom, 'UTF-8');
		$this->prenom = mb_strtolower($this->prenom, 'UTF-8');

        /*
         * Format firstname
         *
         * jean paul => Jean-Paul
         * jean-paul => Jean-Paul
         */
        $Tab = preg_split('/[\-| ]+/', $this->prenom);
        $this->prenom = implode('-', $Tab);
        $this->prenom = ucwords($this->prenom, '-');

		if (empty($this->civilite)) {
			$error ++;
			$this->errors[] = $langs->trans("AgfCiviliteMandatory");
		}

		if (empty($this->socid)) {
			$error ++;
			$this->errors[] = $langs->trans("AgfThirdpartyOfTraineeMandatory");
		}

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_stagiaire(";
		$sql .= "nom, prenom, civilite, fk_user_author,fk_user_mod, datec, ";
		$sql .= "fk_soc, fonction, tel1, tel2, mail, note,fk_socpeople";
		$sql .= ",entity";
		$sql .= ",date_birth";
		$sql .= ",place_birth";
		$sql .= ",disable_auto_mail";
		$sql .= ") VALUES (";

		$sql .= " " . (isset($this->nom) ? "'" . $this->nom . "'" : "null") . ", ";
		$sql .= " " . (isset($this->prenom) ? "'" . $this->prenom . "'" : "null") . ", ";
		$sql .= " " . (isset($this->civilite) ? "'" . $this->civilite . "'" : "null") . ", ";
		$sql .= ' ' . $user->id . ", ";
		$sql .= ' ' . $user->id . ", ";
		$sql .= "'" . $this->db->idate(dol_now()) . "', ";
		$sql .= " " . (isset($this->socid) ? $this->db->escape($this->socid) : "null") . ", ";
		$sql .= " " . (isset($this->fonction) ? "'" . $this->fonction . "'" : "null") . ", ";
		$sql .= " " . (isset($this->tel1) ? "'" . $this->tel1 . "'" : "null") . ", ";
		$sql .= " " . (isset($this->tel2) ? "'" . $this->tel2 . "'" : "null") . ", ";
		$sql .= " " . (isset($this->mail) ? "'" . $this->mail . "'" : "null") . ", ";
		$sql .= " " . (isset($this->note) ? "'" . $this->note . "'" : "null") . ", ";
		$sql .= " " . (isset($this->fk_socpeople) ? $this->db->escape($this->fk_socpeople) : "null") . ", ";
		$sql .= " " . $conf->entity . ",";
		$sql .= " " . (! isset($this->date_birth) || dol_strlen($this->date_birth) == 0 ? 'NULL' : "'" . $this->db->idate($this->date_birth) . "'") . ", ";
		$sql .= " " . (isset($this->place_birth) ? "'" . $this->place_birth . "'" : "null"). ", ";
		$sql .= " " . (isset($this->disable_auto_mail) ? intval($this->disable_auto_mail) : 0 );
		$sql .= ")";

		if (! $error) {
			$this->db->begin();

			dol_syslog(get_class($this) . "::create", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}

			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_stagiaire");
			if (! $notrigger) {
				 // Call triggers
                 if(is_file(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php")){
                     include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                 }else{ // For backward compatibility
                     include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                 }
				 $interface=new Interfaces($this->db);
				 $result=$interface->run_triggers('AGEFODD_STAGIAIRE_CREATE',$this,$user,$langs,$conf);
				 if ($result < 0) { $error++; $this->errors=$interface->errors; }
				 // End call triggers
			}

			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error ++;
			}
			//Historisation fk_soc
			$result = $this->historizeSoc(true);
			if ($result < 0) {
				$error++;
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
	 * Function to historize soc of trainee
	 * @param bool $onCreate
	 * @return int
	 */
	public function historizeSoc($onCreate = false) {
		dol_include_once('/agefodd/class/agefodd_stagiaire_soc_history.class.php');
		$socHistory = new Agefodd_stagiaire_soc_history($this->db);
		$socHistory->fk_stagiaire = $this->id;
		$result = $socHistory->historize($this->socid, $onCreate);
		return $result;
	}


    /**
     * Load object in memory from database
     *
     * @param int $id object
     * @return int <0 if KO, >0 if OK
     */
    public function fetch_by_contact($id) {

        $sql = "SELECT";
        $sql .= " s.rowid as id";
        $sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire as s";
        $sql .= " WHERE s.fk_socpeople = " . intval($id);
        $sql .= " AND s.entity IN (" . getEntity('agefodd'/*agsession*/) . ")";

        dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);

        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                return $this->fetch($obj->id);
            }
            else{
                return 0;
            }
        } else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
            return - 1;
        }
    }


	/**
	 * Load object in memory from database
	 *
	 * @param  int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id) {
		$sql = "SELECT";
		$sql .= " so.rowid as socid, so.nom as socname,";
		$sql .= " civ.code as civilite_code,";
		$sql .= " s.rowid, s.entity, s.nom, s.prenom, s.civilite, s.fk_soc, s.fonction,";
		$sql .= " s.tel1, s.tel2, s.mail, s.note, s.fk_socpeople, s.date_birth, s.place_birth, s.disable_auto_mail";
		$sql .= " ,so.address as socaddr, so.zip as soczip, so.town as soctown";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON s.fk_soc = so.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_civility as civ";
		$sql .= " ON s.civilite = civ.code";
		$sql .= " WHERE s.rowid = " . $id;
		$sql .= " AND s.entity IN (" . getEntity('agefodd') . ")";

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			if ($this->db->num_rows($resql)>0) {
				$obj = $this->db->fetch_object($resql);

				if (! (empty($obj->fk_socpeople))) {
					$contact = new Contact($this->db);
					$result = $contact->fetch($obj->fk_socpeople);
					$this->id = $obj->rowid;
					$this->entity = $obj->entity;
					if ($result > 0) {
						$this->ref = $obj->rowid; // use for next prev refs
						$this->nom = $contact->lastname;
						$this->prenom = $contact->firstname;
						$this->civilite = $contact->civility_code;
						$this->socid = $contact->socid;
						$this->socname = $contact->socname;
						$this->fonction = $contact->poste;
						$this->tel1 = $contact->phone_pro;
						$this->tel2 = $contact->phone_mobile;
						$this->mail = $contact->email;
						$this->note = $obj->note;
						$this->fk_socpeople = $obj->fk_socpeople;
						$this->date_birth = $contact->birthday;
						$this->place_birth = $obj->place_birth;
					}
				} else {
					$this->id = $obj->rowid;
					$this->entity = $obj->entity;
					$this->ref = $obj->rowid; // use for next prev refs
					$this->nom = $obj->nom;
					$this->prenom = $obj->prenom;
					$this->civilite = $obj->civilite;
					$this->socid = $obj->socid;
					$this->socname = $obj->socname;
					$this->fonction = $obj->fonction;
					$this->tel1 = $obj->tel1;
					$this->tel2 = $obj->tel2;
					$this->mail = $obj->mail;
					$this->note = $obj->note;
					$this->place_birth = $obj->place_birth;
					$this->fk_socpeople = 0;
					$this->date_birth = $this->db->jdate($obj->date_birth);
				}
				$this->socaddr = $obj->socaddr;
				$this->soczip = $obj->soczip;
				$this->soctown = $obj->soctown;
				$this->disable_auto_mail = $obj->disable_auto_mail;
			} else {
			    return 0;
			}

			require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
			$extrafields = new ExtraFields($this->db);
			$extralabels = $extrafields->fetch_name_optionals_label($this->table_element, true);
			if (count($extralabels) > 0) {
				$this->fetch_optionals($this->id, $extralabels);
			}

			$this->db->free($resql);

			$this->fetch_thirdparty();

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
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
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_all($sortorder, $sortfield, $limit = 0, $offset = 0, $filter = array()) {
		global $langs;

		require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
		$extrafields = new ExtraFields($this->db);
		$extralabels = $extrafields->fetch_name_optionals_label($this->table_element, true);
		$array_options_keys=array();
		foreach($extrafields->attribute_type as $name=>$type) {
			if ($type!='separate') {
				$array_options_keys[]=$name;
			}
		}
		$sql = "SELECT";
		$sql .= " so.rowid as socid, so.nom as socname,";
		$sql .= " civ.code as civilitecode,";
		$sql .= " s.rowid, s.entity, s.nom, s.prenom, s.civilite, s.fk_soc, s.fonction,";
		$sql .= " s.tel1, s.tel2, s.mail, s.note, s.fk_socpeople, s.date_birth, s.place_birth, s.disable_auto_mail";
		foreach ($array_options_keys as $key)
		{
			$sql.= ',ef.'.$key;
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON s.fk_soc = so.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire_extrafields as ef";
		$sql .= " ON s.rowid = ef.fk_object";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_civility as civ";
		$sql .= " ON s.civilite = civ.code";
		$sql .= " WHERE s.entity IN (" . getEntity('agefodd') . ")";

		// Manage filter
		if (! empty($filter)) {
			foreach ( $filter as $key => $value ) {
				if ($key == 'naturalsearch') {
					$sql .= ' AND (s.nom LIKE \'%' . $this->db->escape($value) . '%\' OR s.prenom LIKE \'%' . $this->db->escape($value) . '%\')';
				} elseif ($key == 's.fk_socpeople' || $key == 's.fk_soc') {
					$sql .= ' AND ' . $key . ' = ' . $this->db->escape($value) . '';
				} elseif ($key != 's.tel1' && $key != 's.tel2' ) {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				} elseif (strpos($key,'ef.')!==false){
					$sql.= $value;
				} else {
					$sql .= ' AND ' . $key . ' = \'' . $this->db->escape($value) . '\'';
				}
			}
		}

		if (!empty($sortfield)) {
			$sql .= " ORDER BY " . $sortfield . " " . $sortorder . " ";
		}
		if (! empty($limit)) {
			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);

					$line = new AgfTraineeLine();

					// Manage filter for telephone to remove all space from result to filter correctly
					/* FIXME Les filtres sur cette requête ont été rajoutés le 05/07/2012 par le commit
					 * 1b75896564965575c8e414b823df9704e42cb140. Depuis ce beau jour d'été, plus de 7 ans en arrière :
					 *     - les trois embranchements qui suivent sont identiques à l'ordre des lignes près (J'ai fait
					 *       un Meld sur le code de l'époque ET sur le code actuel pour m'en assurer) :S
					 *     - la variable $pos est non déclarée (heureusement, le test null !== false est strict donc
					 *       il vaut true et les assignations se font comme attendu)
					 *
					 * C'est trop beau, je laisse ça tel quel, mais en vrai, ça devrait dégager :D - MdLL, 09/04/2020
					 */
					if (! empty($filter)) {
						if (array_key_exists('s.tel1', $filter)) {
							$value = $filter['s.tel1'];
							if (! empty($value)) {
								if ($pos !== false) {
									$line->socid = $obj->socid;
									$line->socname = $obj->socname;
									$line->civilitecode = $obj->civilitecode;
									$line->rowid = $obj->rowid;
									$line->id = $obj->rowid;
									$line->entity = $obj->entity;
									$line->nom = $obj->nom;
									$line->prenom = $obj->prenom;
									$line->civilite = $obj->civilite;
									$line->fk_soc = $obj->fk_soc;
									$line->fonction = $obj->fonction;
									$line->tel1 = $obj->tel1;
									$line->tel2 = $obj->tel2;
									$line->mail = $obj->mail;
									$line->note = $obj->note;
									$line->place_birth = $obj->place_birth;
									$line->fk_socpeople = $obj->fk_socpeople;
									$line->date_birth = $this->db->jdate($obj->date_birth);
								}
							}
						} else {
							$line->socid = $obj->socid;
							$line->socname = $obj->socname;
							$line->civilitecode = $obj->civilitecode;
							$line->rowid = $obj->rowid;
							$line->id = $obj->rowid;
							$line->entity = $obj->entity;
							$line->nom = $obj->nom;
							$line->prenom = $obj->prenom;
							$line->civilite = $obj->civilite;
							$line->fk_soc = $obj->fk_soc;
							$line->fonction = $obj->fonction;
							$line->tel1 = $obj->tel1;
							$line->tel2 = $obj->tel2;
							$line->mail = $obj->mail;
							$line->note = $obj->note;
							$line->fk_socpeople = $obj->fk_socpeople;
							$line->date_birth = $this->db->jdate($obj->date_birth);
							$line->place_birth = $obj->place_birth;
						}
					} else {
						$line->socid = $obj->socid;
						$line->socname = $obj->socname;
						$line->civilitecode = $obj->civilitecode;
						$line->rowid = $obj->rowid;
						$line->id = $obj->rowid;
						$line->entity = $obj->entity;
						$line->nom = $obj->nom;
						$line->prenom = $obj->prenom;
						$line->civilite = $obj->civilite;
						$line->fk_soc = $obj->fk_soc;
						$line->fonction = $obj->fonction;
						$line->tel1 = $obj->tel1;
						$line->tel2 = $obj->tel2;
						$line->mail = $obj->mail;
						$line->note = $obj->note;
						$line->fk_socpeople = $obj->fk_socpeople;
						$line->date_birth = $this->db->jdate($obj->date_birth);
						$line->place_birth = $obj->place_birth;
					}

					$line->disable_auto_mail = $obj->disable_auto_mail;

					if (count($extralabels) > 0) {
						$statictrainee=new self($this->db);
						$statictrainee->fetch_optionals($line->id, $extralabels);
						$line->array_options=$statictrainee->array_options;
					}

					$this->lines[$i] = $line;

					$i ++;
				}
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::". __METHOD__. ' '. $this->error, LOG_ERR);
			return - 1;
		}
	}

	public function fetch_all_id_by($attribute)
	{
		$TRes = array();

		$sql = "SELECT";
		$sql .= " so.rowid as socid, so.nom as socname,";
		$sql .= " civ.code as civilitecode,";
		$sql .= " s.rowid, s.nom, s.prenom, s.civilite, s.fk_soc, s.fonction,";
		$sql .= " s.tel1, s.tel2, s.mail, s.note, s.fk_socpeople, s.date_birth, s.place_birth";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON s.fk_soc = so.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_civility as civ";
		$sql .= " ON s.civilite = civ.code";
		$sql .= " WHERE s.entity IN (" . getEntity('agefodd') . ")";

		dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
				$TRes[$obj->{$attribute}] = $obj->rowid;
			}

			$this->db->free($resql);
			return $TRes;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::".__METHOD__.' '. $this->error, LOG_ERR);
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
		$sql .= " s.rowid, s.entity, s.datec, s.tms, s.fk_user_author, s.fk_user_mod";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire as s";
		$sql .= " WHERE s.rowid = " . $id;

		dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
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
			dol_syslog(get_class($this) . "::".__METHOD__ .' '. $this->error, LOG_ERR);
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
		if (isset($this->nom))
			$this->nom = $this->db->escape(trim($this->nom));
		if (isset($this->prenom))
			$this->prenom = $this->db->escape(trim($this->prenom));
		if (isset($this->fonction))
			$this->fonction = $this->db->escape(trim($this->fonction));
		if (isset($this->tel1))
			$this->tel1 = $this->db->escape(trim($this->tel1));
		if (isset($this->tel2))
			$this->tel2 = $this->db->escape(trim($this->tel2));
		if (isset($this->mail))
			$this->mail = $this->db->escape(trim($this->mail));
		if (isset($this->note))
			$this->note = $this->db->escape(trim($this->note));
		if (isset($this->place_birth))
			$this->place_birth = $this->db->escape(trim($this->place_birth));

			// Check parameters
			// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_stagiaire SET";
		$sql .= " nom=" . (isset($this->nom) ? "'" . $this->nom . "'" : "null") . ",";
		$sql .= " prenom=" . (isset($this->prenom) ? "'" . $this->prenom . "'" : "null") . ",";
		$sql .= " civilite=" . (isset($this->civilite) ? "'" . $this->civilite . "'" : "null") . ",";
		$sql .= " fk_user_mod=" . $user->id . ",";
		$sql .= " fk_soc=" . (isset($this->socid) ? $this->socid : "null") . ",";
		$sql .= " fonction=" . (isset($this->fonction) ? "'" . $this->fonction . "'" : "null") . ",";
		$sql .= " tel1=" . (isset($this->tel1) ? "'" . $this->tel1 . "'" : "null") . ",";
		$sql .= " tel2=" . (isset($this->tel2) ? "'" . $this->tel2 . "'" : "null") . ",";
		$sql .= " mail=" . (isset($this->mail) ? "'" . $this->mail . "'" : "null") . ",";
		$sql .= " note=" . (isset($this->note) ? "'" . $this->note . "'" : "null") . ",";
		$sql .= " fk_socpeople=" . (isset($this->fk_socpeople) ? $this->fk_socpeople : "null") . ", ";
		$sql .= " date_birth=" . (! isset($this->date_birth) || dol_strlen($this->date_birth) == 0 ? "null" : "'" . $this->db->idate($this->date_birth) . "'");
		$sql .= " ,place_birth=" . (isset($this->place_birth) ? "'" . $this->place_birth . "'" : "null");
		$sql .= " ,disable_auto_mail=" . (isset($this->disable_auto_mail) ? "'" . $this->disable_auto_mail . "'" : "null");
		$sql .= " WHERE rowid = " . $this->id;

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
                 if(is_file(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php")){
                     include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                 }else{ // For backward compatibility
                     include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
                 }
				 $interface=new Interfaces($this->db);
				 $result=$interface->run_triggers('AGEFODD_STAGIAIRE_MODIFY',$this,$user,$langs,$conf);
				 if ($result < 0) { $error++; $this->errors=$interface->errors; }
				 // End call triggers
			}

			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error ++;
			}

			//Historisation fk_soc
			$result = $this->historizeSoc();
			if ($result < 0) {
				$error++;
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
	 * @param int $id Id of agefodd_stagiaire to delete
	 * @return int <0 if KO, >0 if OK
	 */
	public function remove($id) {
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire";
		$sql .= " WHERE rowid = " . $id;

		$error = 0;

		$this->db->begin();

		dol_syslog(get_class($this) . "::remove", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {

			$this->id = $id;
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error ++;
				dol_syslog(get_class($this) . "::delete erreur " . $error . " " . $this->error, LOG_ERR);
			}

			//Delete historisation
			dol_include_once('/agefodd/class/agefodd_stagiaire_soc_history.class.php');
			$socHistory = new Agefodd_stagiaire_soc_history($this->db);
			$socHistory->deleteByStagiaire($this->id);
			if ($result < 0) {
				$error ++;
				dol_syslog(get_class($this) . "::delete erreur " . $error . " " . $this->error, LOG_ERR);
			}

			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this) . "::remove " . $this->error, LOG_ERR);
			$this->db->rollback();
			return - 1;
		}
	}

	/**
	 * Search trainee
	 *
	 * @param string $lastname lastname
	 * @param string $firstname firstname
	 * @param int $socid thirdparty id
	 * @return int <0 if KO, >0 if OK
	 */
	public function searchByLastNameFirstNameSoc($lastname, $firstname, $socid) {
		global $conf;

		$sql = "SELECT";
		$sql .= " s.rowid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire as s";
		$sql .= " WHERE (s.fk_soc=" . $socid;
		// contact is in a company witch child $socid
		$sql .= " OR (s.fk_soc IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "societe WHERE parent=" . $socid . "))";
		// contact is in a company witch share the same mother company than $socid
		$sql .= " OR (s.fk_soc IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "societe WHERE parent IN (SELECT parent FROM " . MAIN_DB_PREFIX . "societe WHERE rowid=" . $socid . "))))";
		$sql .= " AND UPPER(s.nom)='" . strtoupper(trim($this->db->escape($lastname))) . "'";
		$sql .= " AND UPPER(s.prenom)='" . strtoupper(trim($this->db->escape($firstname))) . "'";
		$sql .= " AND s.entity IN (" . $conf->entity . ')';

		$num = 0;

		dol_syslog(get_class($this) . "::searchByLastNameFirstNameSoc");
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this) . "::searchByLastNameFirstNameSoc " . $this->error, LOG_ERR);
			return - 1;
		}

		$this->db->free($resql);

		$sql = "SELECT";
		$sql .= " s.rowid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "socpeople as s";
		$sql .= " WHERE s.fk_soc=" . $socid;
		$sql .= " AND s.entity IN (" . getEntity('agefodd'/*agsession*/) . ')';
		$sql .= " AND UPPER(s.lastname)='" . strtoupper($this->db->escape($lastname)) . "'";
		$sql .= " AND UPPER(s.firstname)='" . strtoupper($this->db->escape($firstname)) . "'";

		dol_syslog(get_class($this) . "::searchByLastNameFirstNameSoc");
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$num = + $this->db->num_rows($resql);
			}
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this) . "::searchByLastNameFirstNameSoc " . $this->error, LOG_ERR);
			return - 1;
		}
		dol_syslog(get_class($this) . "::searchByLastNameFirstNameSoc num=" . $num);
		return $num;
	}
}
class AgfTraineeLine {
	public $id;
	public $entity;
	public $socid;
	public $socname;
	public $civilitecode;
	public $rowid;
	public $nom;
	public $prenom;
	public $civilite;
	public $fk_soc;
	public $fonction;
	public $tel1;
	public $tel2;
	public $mail;
	public $note;
	public $fk_socpeople;
	public $date_birth;
	public $place_birth;
	public $disable_auto_mail;
	public $array_options = array();
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
		$link = dol_buildpath('/agefodd/trainee/'.$type.'.php', 1);
		if ($label == 'name') {
			return '<a href="' . $link . '?id=' . $this->id . '">' . $this->nom . ' ' . $this->prenom . '</a>';
		} else {
			return '<a href="' . $link . '?id=' . $this->id . '">' . $this->$label . '</a>';
		}
	}
}
