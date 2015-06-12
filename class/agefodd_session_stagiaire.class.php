<?php
/*
 * Copyright (C) 2012-2014	Florian Henry		<florian.henry@open-concept.pro>
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
 * \file agefodd/class/agefodd_session_stagiaire.class.php
 * \ingroup agefodd
 * \brief Manage trainee in session
 */

// Put here all includes required by your class file
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once (DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php');

/**
 * Manage certificate
 */
class Agefodd_session_stagiaire extends CommonObject {
	var $db; // !< To store db handler
	var $error; // !< To return error code (or message)
	var $errors = array (); // !< To return several error codes (or messages)
	var $element = 'agfsessionsta'; // !< Id that identify managed objects
	var $table_element = 'agefodd_session_stagiaire'; // !< Name of table without prefix where object is stored
	var $id;
	var $entity;
	var $fk_session_agefodd;
	var $fk_stagiaire;
	var $fk_agefodd_stagiaire_type;
	var $fk_soc_link;
	var $fk_soc_requester;
	var $status_in_session;
	var $labelstatut;
	var $labelstatut_short;
	var $fk_user_author = '';
	var $fk_user_mod = '';
	var $datec = '';
	var $tms = '';
	var $lines = array ();
	var $lines_state = array ();
	
	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	function __construct($db) {
		global $langs;
		$langs->trans('agefodd@agefodd');
		
		$this->db = $db;
		
		$this->labelstatut [0] = $langs->trans("TraineeSessionStatusProspect");
		$this->labelstatut [1] = $langs->trans("TraineeSessionStatusVerbalAgreement");
		$this->labelstatut [2] = $langs->trans("TraineeSessionStatusConfirm");
		$this->labelstatut [3] = $langs->trans("TraineeSessionStatusPresent");
		$this->labelstatut [4] = $langs->trans("TraineeSessionStatusPartPresent");
		$this->labelstatut [5] = $langs->trans("TraineeSessionStatusNotPresent");
		$this->labelstatut [6] = $langs->trans("TraineeSessionStatusCancelled");
		
		$this->labelstatut_short [0] = $langs->trans("TraineeSessionStatusProspectShort");
		$this->labelstatut_short [1] = $langs->trans("TraineeSessionStatusVerbalAgreementShort");
		$this->labelstatut_short [2] = $langs->trans("TraineeSessionStatusConfirmShort");
		$this->labelstatut_short [3] = $langs->trans("TraineeSessionStatusPresentShort");
		$this->labelstatut_short [4] = $langs->trans("TraineeSessionStatusPartPresentShort");
		$this->labelstatut_short [5] = $langs->trans("TraineeSessionStatusNotPresentShort");
		$this->labelstatut_short [6] = $langs->trans("TraineeSessionStatusCancelledShort");
		
		return 1;
	}
	
	/**
	 * Load object in memory from database
	 *
	 * @param int $id of session
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch($id) {
		$sql = "SELECT";
		$sql .= " fk_session_agefodd, fk_stagiaire, fk_agefodd_stagiaire_type, fk_user_author,fk_user_mod, datec, status_in_session";
		$sql .= " ,fk_soc_link";
		$sql .= " ,fk_soc_requester";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire";
		$sql .= " WHERE rowid= " . $id;
		
		dol_syslog(get_class($this) . "::fetch_stagiaire_per_session", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			
			$obj = $this->db->fetch_object($resql);
			
			$this->fk_session_agefodd = $obj->fk_session_agefodd;
			$this->fk_stagiaire = $obj->fk_stagiaire;
			$this->fk_agefodd_stagiaire_type = $obj->fk_agefodd_stagiaire_type;
			$this->fk_soc_link = $obj->fk_soc_link;
			$this->fk_soc_requester = $obj->fk_soc_requester;
			$this->fk_user_author = $obj->fk_user_author;
			$this->fk_user_mod = $obj->fk_user_mod;
			$this->datec = $this->db->jdate($obj->datec);
			$this->status_in_session = $obj->status_in_session;
			
			$this->db->free($resql);
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_stagiaire_per_session " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Load object (all trainee for one session) in memory from database
	 *
	 * @param int $id of session
	 * @param int $socid by thridparty
	 * @param int $searchAsLink search as soc link
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_stagiaire_per_session($id, $socid = null, $searchAsLink = 0) {
		global $langs;
		
		$linesadded = array ();
		
		$sql = "SELECT";
		$sql .= " s.rowid as sessid,";
		$sql .= " ss.rowid, ss.fk_stagiaire, ss.fk_agefodd_stagiaire_type,ss.status_in_session,";
		$sql .= " ss.fk_soc_link,";
		$sql .= " ss.fk_soc_requester,";
		$sql .= " sa.nom, sa.prenom,";
		$sql .= " civ.code as civilite, civ.label as civilitel,";
		$sql .= " so.nom as socname, so.rowid as socid,";
		$sql .= ' so.code_client as soccode, ';
		$sql .= " st.rowid as typeid, st.intitule as type, sa.mail as stamail, sope.email as socpemail,";
		$sql .= " sa.date_birth,";
		$sql .= " sa.place_birth,";
		$sql .= " sa.fk_socpeople,";
		$sql .= " sope.birthday,";
		$sql .= " sope.poste,";
		$sql .= " sa.fonction";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
		$sql .= " ON sa.rowid = ss.fk_stagiaire";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_civility as civ";
		$sql .= " ON civ.code = sa.civilite";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = sa.fk_soc";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sope";
		$sql .= " ON sope.rowid = sa.fk_socpeople";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire_type as st";
		$sql .= " ON st.rowid = ss.fk_agefodd_stagiaire_type";
		$sql .= " WHERE s.rowid = " . $id;
		if (! empty($socid))
			$sql .= " AND so.rowid = " . $socid;
		$sql .= " ORDER BY sa.nom";
		
		dol_syslog(get_class($this) . "::fetch_stagiaire_per_session", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);
			
			$i = 0;
			while ($obj = $this->db->fetch_object($resql)) {
				
				if (! empty($searchAsLink)) {
					$linesadded [] = $obj->rowid;
				}
				
				$line = new AgfTraineeSessionLine();
				
				$line->stagerowid = $obj->rowid;
				$line->sessid = $obj->sessid;
				$line->id = $obj->fk_stagiaire;
				$line->nom = $obj->nom;
				$line->prenom = $obj->prenom;
				$line->civilite = $obj->civilite;
				$line->civilitel = $obj->civilitel;
				$line->socname = $obj->socname;
				$line->socid = $obj->socid;
				$line->soccode = $obj->soccode;
				$line->fk_soc_link = $obj->fk_soc_link;
				$line->fk_soc_requester = $obj->fk_soc_requester;
				$line->typeid = $obj->typeid;
				$line->status_in_session = $obj->status_in_session;
				$line->place_birth = $obj->place_birth;
				if (empty($obj->date_birth)) {
					$line->date_birth = $this->db->jdate($obj->birthday);
				} else {
					$line->date_birth = $this->db->jdate($obj->date_birth);
				}
				
				$line->type = $obj->type;
				$line->fk_socpeople = $obj->fk_socpeople;
				if (empty($obj->stamail)) {
					$line->email = $obj->socpemail;
				} else {
					$line->email = $obj->stamail;
				}
				
				if (empty($obj->poste)) {
					$line->poste = $obj->fonction;
				} else {
					$line->poste = $obj->poste;
				}
				
				$line->fk_agefodd_stagiaire_type = $obj->fk_agefodd_stagiaire_type;
				
				$this->lines[$i] = $line;
				//dol_syslog(get_class($this) . "::fetch_stagiaire_per_session line=".var_export($line,true));
				$i ++;
			}
			$this->db->free($resql);
			
			// Add trainee with a link to this sociedte
			if ($searchAsLink == 1) {
				$sql = "SELECT";
				$sql .= " s.rowid as sessid,";
				$sql .= " ss.rowid, ss.fk_stagiaire, ss.fk_agefodd_stagiaire_type,ss.status_in_session,";
				$sql .= " ss.fk_soc_link,";
				$sql .= " ss.fk_soc_requester,";
				$sql .= " sa.nom, sa.prenom,";
				$sql .= " civ.code as civilite, civ.label as civilitel,";
				$sql .= " so.nom as socname, so.rowid as socid,";
				$sql .= ' so.code_client as soccode, ';
				$sql .= " st.rowid as typeid, st.intitule as type, sa.mail as stamail, sope.email as socpemail,";
				$sql .= " sa.date_birth,";
				$sql .= " sa.place_birth,";
				$sql .= " sa.fk_socpeople,";
				$sql .= " sope.birthday,";
				$sql .= " sope.poste,";
				$sql .= " sa.fonction";
				$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
				$sql .= " ON s.rowid = ss.fk_session_agefodd";
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
				$sql .= " ON sa.rowid = ss.fk_stagiaire";
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_civility as civ";
				$sql .= " ON civ.code = sa.civilite";
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
				$sql .= " ON so.rowid = sa.fk_soc";
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sope";
				$sql .= " ON sope.rowid = sa.fk_socpeople";
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire_type as st";
				$sql .= " ON st.rowid = ss.fk_agefodd_stagiaire_type";
				$sql .= " WHERE s.rowid = " . $id;
				if (! empty($socid))
					$sql .= " AND ss.fk_soc_link = " . $socid;
				$sql .= " ORDER BY sa.nom";
				
				dol_syslog(get_class($this) . "::fetch_stagiaire_per_session", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$num = + $this->db->num_rows($resql);
					
					$i = 0;
					while ($obj = $this->db->fetch_object($resql)) {
						
						if (! in_array($obj->rowid, $linesadded)) {
							$line = new AgfTraineeSessionLine();
							
							$line->stagerowid = $obj->rowid;
							$line->sessid = $obj->sessid;
							$line->id = $obj->fk_stagiaire;
							$line->nom = $obj->nom;
							$line->prenom = $obj->prenom;
							$line->civilite = $obj->civilite;
							$line->civilitel = $obj->civilitel;
							$line->socname = $obj->socname;
							$line->socid = $obj->socid;
							$line->soccode = $obj->soccode;
							$line->fk_soc_link = $obj->fk_soc_link;
							$line->fk_soc_requester = $obj->fk_soc_requester;
							$line->typeid = $obj->typeid;
							$line->status_in_session = $obj->status_in_session;
							$line->place_birth = $obj->place_birth;
							if (empty($obj->date_birth)) {
								$line->date_birth = $this->db->jdate($obj->birthday);
							} else {
								$line->date_birth = $this->db->jdate($obj->date_birth);
							}
							
							$line->type = $obj->type;
							$line->fk_socpeople = $obj->fk_socpeople;
							if (empty($obj->stamail)) {
								$line->email = $obj->socpemail;
							} else {
								$line->email = $obj->mail;
							}
							
							if (empty($obj->poste)) {
								$line->poste = $obj->fonction;
							} else {
								$line->poste = $obj->poste;
							}
							
							$line->fk_agefodd_stagiaire_type = $obj->fk_agefodd_stagiaire_type;
							
							$this->lines [$line->stagerowid] = $line;
							
							$i ++;
						}
					}
					$this->db->free($resql);
				} else {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::fetch_stagiaire_per_session " . $this->error, LOG_ERR);
					return - 1;
				}
			}
			
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_stagiaire_per_session " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Load object (all trainee for one session) in memory from database
	 *
	 * @param int $id of session
	 * @param int $socid of OPCA
	 * @param int $trainee_seesion_id Trainee session ID
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_stagiaire_per_session_per_OPCA($id, $socid = 0, $trainee_seesion_id=0) {
		global $langs;
		
		$linesadded = array ();
		
		$sql = "SELECT";
		$sql .= " s.rowid as sessid,";
		$sql .= " ss.rowid, ss.fk_stagiaire, ss.fk_agefodd_stagiaire_type,ss.status_in_session,";
		$sql .= " ss.fk_soc_link,";
		$sql .= " ss.fk_soc_requester,";
		$sql .= " sa.nom, sa.prenom,";
		$sql .= " civ.code as civilite, civ.label as civilitel,";
		$sql .= " so.nom as socname, so.rowid as socid,";
		$sql .= ' so.code_client as soccode, ';
		$sql .= " st.rowid as typeid, st.intitule as type, sa.mail as stamail, sope.email as socpemail,";
		$sql .= " sa.date_birth,";
		$sql .= " sa.place_birth,";
		$sql .= " sa.fk_socpeople,";
		$sql .= " sope.birthday,";
		$sql .= " sope.poste,";
		$sql .= " sa.fonction";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_opca as staopca";
		$sql .= " ON s.rowid = staopca.fk_session_agefodd ";
		if (!empty($socid)) {
			$sql .= " AND staopca.fk_soc_OPCA=" . $socid .' AND staopca.fk_session_trainee=ss.rowid';
		}
		if (!empty($trainee_seesion_id)) {
			$sql .= " AND staopca.fk_session_trainee=" . $trainee_seesion_id;
		}
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
		$sql .= " ON sa.rowid = ss.fk_stagiaire AND sa.fk_soc=staopca.fk_soc_trainee";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_civility as civ";
		$sql .= " ON civ.code = sa.civilite";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = sa.fk_soc";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sope";
		$sql .= " ON sope.rowid = sa.fk_socpeople";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire_type as st";
		$sql .= " ON st.rowid = ss.fk_agefodd_stagiaire_type";
		$sql .= " WHERE s.rowid = " . $id;
		$sql .= " ORDER BY sa.nom";
		
		dol_syslog(get_class($this) . "::fetch_stagiaire_per_session_per_OPCA", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);
			
			$i = 0;
			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);
				
				$line = new AgfTraineeSessionLine();
				
				$line->stagerowid = $obj->rowid;
				$line->sessid = $obj->sessid;
				$line->id = $obj->fk_stagiaire;
				$line->nom = $obj->nom;
				$line->prenom = $obj->prenom;
				$line->civilite = $obj->civilite;
				$line->civilitel = $obj->civilitel;
				$line->socname = $obj->socname;
				$line->socid = $obj->socid;
				$line->soccode = $obj->soccode;
				$line->fk_soc_link = $obj->fk_soc_link;
				$line->fk_soc_requester = $obj->fk_soc_requester;
				$line->typeid = $obj->typeid;
				$line->status_in_session = $obj->status_in_session;
				$line->place_birth = $obj->place_birth;
				if (empty($obj->date_birth)) {
					$line->date_birth = $this->db->jdate($obj->birthday);
				} else {
					$line->date_birth = $this->db->jdate($obj->date_birth);
				}
				
				$line->type = $obj->type;
				$line->fk_socpeople = $obj->fk_socpeople;
				if (empty($obj->stamail)) {
					$line->email = $obj->socpemail;
				} else {
					$line->email = $obj->mail;
				}
				
				if (empty($obj->poste)) {
					$line->poste = $obj->fonction;
				} else {
					$line->poste = $obj->poste;
				}
				
				$line->fk_agefodd_stagiaire_type = $obj->fk_agefodd_stagiaire_type;
				
				$this->lines [$i] = $line;
				
				$i ++;
			}
			$this->db->free($resql);
			
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_stagiaire_per_session_per_OPCA " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Create object (trainee in session) into database
	 *
	 * @param User $user that create
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	function create($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		$this->fk_session_agefodd = $this->db->escape(trim($this->fk_session_agefodd));
		$this->fk_stagiaire = $this->db->escape(trim($this->fk_stagiaire));
		$this->fk_agefodd_stagiaire_type = $this->db->escape(trim($this->fk_agefodd_stagiaire_type));
		$this->status_in_session = $this->db->escape(trim($this->status_in_session));
		$this->fk_soc_link = $this->db->escape(trim($this->fk_soc_link));
		$this->fk_soc_requester = $this->db->escape(trim($this->fk_soc_requester));
		
		// Check parameters
		// Put here code to add control on parameters value
		if (! $conf->global->AGF_USE_STAGIAIRE_TYPE) {
			$this->fk_agefodd_stagiaire_type = $conf->global->AGF_DEFAULT_STAGIAIRE_TYPE;
		}
		if (empty($this->status_in_session))
			$this->status_in_session = 0;
		
		//Determine for trainne subscrition if there already a propospal link with the customer signed
		if ($conf->global->AGF_SESSION_TRAINEE_STATUS_AUTO) {
			$sql = "SELECT propal.rowid ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "propal as propal ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_element as sesselem ";
			$sql .= " ON propal.rowid=sesselem.fk_element AND sesselem.element_type='propal' AND sesselem.fk_session_agefodd=".$this->fk_session_agefodd." AND propal.fk_statut=2 ";
			
			$sql_fk_soc_link='';
			if (!empty($this->fk_soc_link)) {
				$sql_fk_soc_link=' propal.fk_soc='.$this->fk_soc_link.' OR ';
			}
			
			$sql .= " WHERE (".$sql_fk_soc_link." propal.fk_soc IN (SELECT trainee.fk_soc FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire as trainee WHERE trainee.rowid=".$this->fk_stagiaire."))";
			
			dol_syslog(get_class($this) . "::create", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->lines = array ();
				$num = $this->db->num_rows($resql);
				if ($num>0) {
					$obj = $this->db->fetch_object($resql);
					if (!empty($obj->rowid)) {
						$this->status_in_session = 2;
					}
				}
			}else {
				$this->error= "Error " . $this->db->lasterror();
				return -1;
			}
		}
		
		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_session_stagiaire (";
		$sql .= "fk_session_agefodd, fk_stagiaire, fk_agefodd_stagiaire_type, status_in_session,fk_user_author,fk_user_mod, datec";
		$sql .= " ,fk_soc_link";
		$sql .= " ,fk_soc_requester";
		$sql .= ") VALUES (";
		$sql .= $this->fk_session_agefodd . ', ';
		$sql .= $this->fk_stagiaire . ', ';
		$sql .= ((! empty($this->fk_agefodd_stagiaire_type)) ? $this->fk_agefodd_stagiaire_type : "0") . ', ';
		$sql .= $this->status_in_session . ', ';
		$sql .= $user->id . ",";
		$sql .= $user->id . ",";
		$sql .= "'" . $this->db->idate(dol_now()) . "',";
		$sql .= ((! empty($this->fk_soc_link)) ? $this->fk_soc_link : "NULL") . ",";
		$sql .= ((! empty($this->fk_soc_requester)) ? $this->fk_soc_requester : "NULL");
		$sql .= ")";
		
		$this->db->begin();
		
		dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror();
		}
		
		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_session_stagiaire");
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
			
			// Recalculate number of trainee in session
			require_once 'agsession.class.php';
			$session = new Agsession($this->db);
			$session->fetch($this->fk_session_agefodd);
			if (empty($session->force_nb_stagiaire)) {
				$this->fetch_stagiaire_per_session($this->fk_session_agefodd);
				$session->nb_stagiaire = count($this->lines);
				$session->update($user);
			}
		}
		
		// Create auto certif if enabled
		if (! $error && ! empty($conf->global->AGF_MANAGE_CERTIF) && ! empty($conf->global->AGF_DEFAULT_CREATE_CERTIF)) {
			require_once 'agefodd_stagiaire_certif.class.php';
			
			$agf_certif = new Agefodd_stagiaire_certif($this->db);
			// New cerficiation
			
			require_once 'agefodd_formation_catalogue.class.php';
			// Find next certificate code
			$agf_training = new Agefodd($this->db);
			$agf_training->fetch($session->formid);
			$obj = empty($conf->global->AGF_CERTIF_ADDON) ? 'mod_agefoddcertif_simple' : $conf->global->AGF_CERTIF_ADDON;
			$path_rel = dol_buildpath('/agefodd/core/modules/agefodd/certificate/' . $conf->global->AGF_CERTIF_ADDON . '.php');
			if (! empty($conf->global->AGF_CERTIF_ADDON) && is_readable($path_rel) && (empty($agf_certif->certif_code))) {
				dol_include_once('/agefodd/core/modules/agefodd/certificate/' . $conf->global->AGF_CERTIF_ADDON . '.php');
				$modAgefodd = new $obj();
				$agf_certif->certif_code = $modAgefodd->getNextValue($agf_training, $session);
			}
			
			if (is_numeric($agf_certif->certif_code) && $agf_certif->certif_code <= 0)
				$agf_certif->certif_code = '';
			
			$agf_certif->fk_session_agefodd = $this->fk_session_agefodd;
			$agf_certif->fk_session_stagiaire = $this->id;
			$agf_certif->fk_stagiaire = $this->fk_stagiaire;
			$agf_certif->certif_label = $agf_certif->certif_code;
			
			// Start date in the end of session ot now if not set yet
			if (dol_strlen($session->datef) == 0) {
				$certif_dt_start = dol_now();
			} else {
				$certif_dt_start = $session->datef;
			}
			$agf_certif->certif_dt_start = $certif_dt_start;
			
			// End date is end of session more the time set in session
			if (! empty($agf_training->certif_duration)) {
				require_once (DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php');
				$duration_array = explode(':', $agf_training->certif_duration);
				$year = $duration_array [0];
				$month = $duration_array [1];
				$day = $duration_array [2];
				$certif_dt_end = dol_time_plus_duree($certif_dt_start, $year, 'y');
				$certif_dt_end = dol_time_plus_duree($certif_dt_end, $month, 'm');
				$certif_dt_end = dol_time_plus_duree($certif_dt_end, $day, 'd');
			} else {
				$certif_dt_end = $certif_dt_start;
			}
			
			$agf_certif->certif_dt_end = $certif_dt_end;
			$agf_certif->certif_dt_warning = dol_time_plus_duree($certif_dt_end, - 6, 'm');
			
			$resultcertif = $agf_certif->create($user);
			if ($resultcertif < 0) {
				$error ++;
				$this->errors [] = "Error " . $agf_certif->error;
			} else {
				
				$certif_type_array = $agf_certif->get_certif_type();
				
				if (is_array($certif_type_array) && count($certif_type_array) > 0) {
					foreach ( $certif_type_array as $certif_type_id => $certif_type_label ) {
						// Set Certification type to not passed yet
						$result = $agf_certif->set_certif_state($user, $resultcertif, $certif_type_id, 0);
						if ($result < 0) {
							$error ++;
							$this->errors [] = "Error " . $agf_certif->error;
						}
					}
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
	 * Delete object (trainne in session) in database
	 *
	 * @param int $id to delete
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger = 0) {
		$this->db->begin();
		
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_convention_stagiaire";
		$sql .= " WHERE fk_agefodd_session_stagiaire = " . $this->id;
		
		dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror();
		}
		
		$this->fetch($this->id);
		
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire";
		$sql .= " WHERE rowid = " . $this->id;
		
		dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		
		if ($resql) {
			require_once 'agsession.class.php';
			$session = new Agsession($this->db);
			$session->fetch($this->fk_session_agefodd);
			if (empty($session->force_nb_stagiaire)) {
				$this->fetch_stagiaire_per_session($this->fk_session_agefodd);
				$session->nb_stagiaire = count($this->lines);
				$result = $session->update($user);
				if ($result < 0) {
					$error ++;
					$this->errors [] = "Error " . $session->error;
				}
			}
		} else {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror();
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::remove " . $errmsg, LOG_ERR);
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
	 * Update object (trainee in session) into database
	 *
	 * @param User $user that modify
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function update($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		$this->fk_session_agefodd = $this->db->escape(trim($this->fk_session_agefodd));
		$this->fk_stagiaire = $this->db->escape(trim($this->fk_stagiaire));
		$this->fk_agefodd_stagiaire_type = $this->db->escape(trim($this->fk_agefodd_stagiaire_type));
		$this->fk_soc_link = $this->db->escape(trim($this->fk_soc_link));
		$this->fk_soc_requester = $this->db->escape(trim($this->fk_soc_requester));
		
		// Check parameters
		// Put here code to add control on parameters value
		if (! $conf->global->AGF_USE_STAGIAIRE_TYPE) {
			$this->fk_agefodd_stagiaire_type = $conf->global->AGF_DEFAULT_STAGIAIRE_TYPE;
		}
		
		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_stagiaire SET";
		$sql .= " fk_session_agefodd=" . (isset($this->fk_session_agefodd) ? $this->fk_session_agefodd : "null") . ",";
		$sql .= " fk_stagiaire=" . (isset($this->fk_stagiaire) ? $this->fk_stagiaire : "null") . ",";
		$sql .= " fk_user_mod=" . $user->id . ",";
		$sql .= " status_in_session=" . (! empty($this->status_in_session) ? $this->status_in_session : "0") . ",";
		$sql .= " fk_agefodd_stagiaire_type=" . (isset($this->fk_agefodd_stagiaire_type) ? $this->fk_agefodd_stagiaire_type : "0") . ",";
		$sql .= " fk_soc_link=" . (isset($this->fk_soc_link) ? $this->fk_soc_link : "null") . ",";
		$sql .= " fk_soc_requester=" . (isset($this->fk_soc_requester) ? $this->fk_soc_requester : "null");
		$sql .= " WHERE rowid = " . $this->id;
		
		$this->db->begin();
		
		dol_syslog(get_class($this) . "::update", LOG_DEBUG);
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
	 * Update status of trainee in session by soc
	 *
	 * @param User $user that modify
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @param int $socid id
	 * @param int $status status
	 * @return int <0 if KO, >0 if OK
	 */
	function update_status_by_soc($user, $notrigger = 0, $socid = 0, $status = 0) {
		global $conf, $langs;
		$error = 0;
		
		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_stagiaire SET";
		$sql .= " status_in_session=" . $status;
		$sql .= " WHERE fk_session_agefodd = " . $this->fk_session_agefodd;
		if (! empty($socid)) {
			// For the same thirdparty as the trainee
			$sql .= ' AND ((fk_stagiaire IN (SELECT rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_stagiaire WHERE fk_soc=' . $socid . '))';
			// For the trainne link with use trhidparty into doc
			$sql .= ' OR (fk_soc_link =' . $socid . '))';
		}
		
		$this->db->begin();
		
		dol_syslog(get_class($this) . "::update_status_by_soc", LOG_DEBUG);
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
				dol_syslog(get_class($this) . "::update_status_by_soc " . $errmsg, LOG_ERR);
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
	 * Return label of status of trainee in session (on going, subcribe, confirm, present, patially present,not present,canceled)
	 *
	 * @param int $mode label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * @return string
	 */
	function getLibStatut($mode = 0) {
		return $this->LibStatut($this->status_in_session, $mode);
	}
	
	/**
	 * Return label of a status (draft, validated, .
	 *
	 *
	 *
	 * ..)
	 *
	 * @param int $statut
	 * @param int $mode label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * @return string
	 */
	function LibStatut($statut, $mode = 1) {
		global $langs;
		
		if (empty($statut))
			$statut = 0;
		
		$langs->load("agefodd@agefodd");
		
		if ($mode == 0) {
			
			return $this->labelstatut [$statut];
		}
		if ($mode == 1) {
			return $this->labelstatut_short [$statut];
		}
		if ($mode == 2) {
			if ($statut == 0)
				return img_picto($langs->trans('TraineeSessionStatusProspect'), 'statut0') . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 1)
				return img_picto($langs->trans('TraineeSessionStatusVerbalAgreement'), 'statut3') . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 2)
				return img_picto($langs->trans('TraineeSessionStatusConfirm'), 'statut4') . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 3)
				return img_picto($langs->trans('TraineeSessionStatusPresent'), 'statut6') . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 4)
				return img_picto($langs->trans('TraineeSessionStatusPartPresent'), 'statut7') . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 5)
				return img_picto($langs->trans('TraineeSessionStatusNotPresent'), 'statut9') . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 6)
				return img_picto($langs->trans('TraineeSessionStatusCancelled'), 'statut8') . ' ' . $this->labelstatut_short [$statut];
		}
		if ($mode == 3) {
			if ($statut == 0)
				return img_picto($langs->trans('TraineeSessionStatusProspect'), 'statut0');
			if ($statut == 1)
				return img_picto($langs->trans('TraineeSessionStatusVerbalAgreement'), 'statut3');
			if ($statut == 2)
				return img_picto($langs->trans('TraineeSessionStatusConfirm'), 'statut4');
			if ($statut == 3)
				return img_picto($langs->trans('TraineeSessionStatusPresent'), 'statut6');
			if ($statut == 4)
				return img_picto($langs->trans('TraineeSessionStatusPartPresent'), 'statut7');
			if ($statut == 5)
				return img_picto($langs->trans('TraineeSessionStatusNotPresent'), 'statut9');
			if ($statut == 6)
				return img_picto($langs->trans('TraineeSessionStatusCancelled'), 'statut8');
		}
		if ($mode == 4) {
			if ($statut == 0)
				return img_picto($langs->trans('TraineeSessionStatusProspect'), 'statut0') . ' ' . $this->labelstatut [$statut];
			if ($statut == 1)
				return img_picto($langs->trans('TraineeSessionStatusVerbalAgreement'), 'statut3') . ' ' . $this->labelstatut [$statut];
			if ($statut == 2)
				return img_picto($langs->trans('TraineeSessionStatusConfirm'), 'statut4') . ' ' . $this->labelstatut [$statut];
			if ($statut == 3)
				return img_picto($langs->trans('TraineeSessionStatusPresent'), 'statut6') . ' ' . $this->labelstatut [$statut];
			if ($statut == 4)
				return img_picto($langs->trans('TraineeSessionStatusPartPresent'), 'statut7') . ' ' . $this->labelstatut [$statut];
			if ($statut == 5)
				return img_picto($langs->trans('TraineeSessionStatusNotPresent'), 'statut9') . ' ' . $this->labelstatut [$statut];
			if ($statut == 6)
				return img_picto($langs->trans('TraineeSessionStatusCancelled'), 'statut8') . ' ' . $this->labelstatut [$statut];
		}
		if ($mode == 5) {
			if ($statut == 0)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusProspect'), 'statut0');
			if ($statut == 1)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusVerbalAgreement'), 'statut3');
			if ($statut == 2)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusConfirm'), 'statut4');
			if ($statut == 3)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusPresent'), 'statut6');
			if ($statut == 4)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusPartPresent'), 'statut7');
			if ($statut == 5)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusNotPresent'), 'statut9');
			if ($statut == 6)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusCancelled'), 'statut8');
		}
	}
}

/**
 * Session Trainee Link Class
 */
class AgfTraineeSessionLine {
	var $stagerowid;
	var $sessid;
	var $id;
	var $nom;
	var $prenom;
	var $civilite;
	var $civilitel;
	var $socname;
	var $socid;
	var $typeid;
	var $type;
	var $email;
	var $fk_socpeople;
	var $date_birth;
	var $place_birth;
	var $status_in_session;
	var $fk_agefodd_stagiaire_type;
	var $poste;
	var $soccode;
	var $fk_soc_link;
	var $fk_soc_requester;
	function __construct() {
		return 1;
	}
}