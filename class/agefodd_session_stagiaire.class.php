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
	public $error; // !< To return error code (or message)
	public $errors = array (); // !< To return several error codes (or messages)
	public $element = 'agfsessionsta'; // !< Id that identify managed objects
	public $table_element = 'agefodd_session_stagiaire'; // !< Name of table without prefix where object is stored
	public $id;
	public $entity;
	public $fk_session_agefodd;
	public $fk_stagiaire;
	public $fk_agefodd_stagiaire_type;
	public $fk_socpeople_sign;
	public $fk_soc_link;
	public $fk_soc_requester;
	public $fk_soc;

	/**
	 * 0 => TraineeSessionStatusProspect (Prospect)
	 * 1 => TraineeSessionStatusVerbalAgreement (Accord verbal)
	 * 2 => TraineeSessionStatusConfirm (Confirmé)
	 * 3 => TraineeSessionStatusPresent (Présent)
	 * 4 => TraineeSessionStatusPartPresent (Partiellement présent)
	 * 5 => TraineeSessionStatusNotPresent (Non présent)
	 * 6 => TraineeSessionStatusCancelled (Annulé)
	 * 7 => TraineeSessionStatusExcuse (Excusé)
	 *
	 * @var integer
	 */
	public $status_in_session;

	const STATUS_IN_SESSION_PROSPECT = 0;
	const STATUS_IN_SESSION_VERBAL_AGREEMENT = 1;
	const STATUS_IN_SESSION_CONFIRMED = 2;
	const STATUS_IN_SESSION_TOTALLY_PRESENT = 3;
	const STATUS_IN_SESSION_PARTIALLY_PRESENT = 4;
	const STATUS_IN_SESSION_NOT_PRESENT = 5;
	const STATUS_IN_SESSION_CANCELED = 6;
	const STATUS_IN_SESSION_EXCUSED = 7;

	public $labelstatut = array();
	public $labelstatut_short = array();
	public $fk_user_author = '';
	public $fk_user_mod = '';
	public $datec = '';
	public $tms = '';
	/**
	 * @var $lines AgfTraineeSessionLine[]
	 */
	public $lines = array ();
	public $hour_foad;

	public $statusAvalaibleForPast = array();
	public $statusAvalaibleForFuture = array();
	public $statusDeleteTime = array();

	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	public function __construct($db) {
		global $langs;
		$langs->trans('agefodd@agefodd');

		$this->db = $db;

		$this->labelstatut[self::STATUS_IN_SESSION_PROSPECT] = $langs->trans("TraineeSessionStatusProspect");
		$this->labelstatut[self::STATUS_IN_SESSION_VERBAL_AGREEMENT] = $langs->trans("TraineeSessionStatusVerbalAgreement");
		$this->labelstatut[self::STATUS_IN_SESSION_CONFIRMED] = $langs->trans("TraineeSessionStatusConfirm");
		$this->labelstatut[self::STATUS_IN_SESSION_TOTALLY_PRESENT] = $langs->trans("TraineeSessionStatusPresent");
		$this->labelstatut[self::STATUS_IN_SESSION_PARTIALLY_PRESENT] = $langs->trans("TraineeSessionStatusPartPresent");
		$this->labelstatut[self::STATUS_IN_SESSION_NOT_PRESENT] = $langs->trans("TraineeSessionStatusNotPresent");
		$this->labelstatut[self::STATUS_IN_SESSION_CANCELED] = $langs->trans("TraineeSessionStatusCancelled");
		$this->labelstatut[self::STATUS_IN_SESSION_EXCUSED] = $langs->trans('TraineeSessionStatusExcuse');

		$this->labelstatut_short[self::STATUS_IN_SESSION_PROSPECT] = $langs->trans("TraineeSessionStatusProspectShort");
		$this->labelstatut_short[self::STATUS_IN_SESSION_VERBAL_AGREEMENT] = $langs->trans("TraineeSessionStatusVerbalAgreementShort");
		$this->labelstatut_short[self::STATUS_IN_SESSION_CONFIRMED] = $langs->trans("TraineeSessionStatusConfirmShort");
		$this->labelstatut_short[self::STATUS_IN_SESSION_TOTALLY_PRESENT] = $langs->trans("TraineeSessionStatusPresentShort");
		$this->labelstatut_short[self::STATUS_IN_SESSION_PARTIALLY_PRESENT] = $langs->trans("TraineeSessionStatusPartPresentShort");
		$this->labelstatut_short[self::STATUS_IN_SESSION_NOT_PRESENT] = $langs->trans("TraineeSessionStatusNotPresentShort");
		$this->labelstatut_short[self::STATUS_IN_SESSION_CANCELED] = $langs->trans("TraineeSessionStatusCancelledShort");
		$this->labelstatut_short[self::STATUS_IN_SESSION_EXCUSED] = $langs->trans('TraineeSessionStatusExcuseShort');

		$this->statusAvalaibleForPast = array(
			self::STATUS_IN_SESSION_CONFIRMED,
			self::STATUS_IN_SESSION_TOTALLY_PRESENT,
			self::STATUS_IN_SESSION_PARTIALLY_PRESENT,
			self::STATUS_IN_SESSION_NOT_PRESENT,
			self::STATUS_IN_SESSION_CANCELED,
			self::STATUS_IN_SESSION_EXCUSED
		);
		$this->statusAvalaibleForFuture =array(self::STATUS_IN_SESSION_PROSPECT,
		                                        self::STATUS_IN_SESSION_VERBAL_AGREEMENT,
		                                        self::STATUS_IN_SESSION_CONFIRMED,
		                                       self::STATUS_IN_SESSION_CANCELED,
		                                       self::STATUS_IN_SESSION_EXCUSED);

		$this->statusDeleteTime =array (self::STATUS_IN_SESSION_NOT_PRESENT,
		                               self::STATUS_IN_SESSION_CANCELED,
		                               self::STATUS_IN_SESSION_EXCUSED);

		return 1;
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $id of session
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id) {
		$sql = "SELECT";
		$sql .= " rowid, fk_session_agefodd, fk_stagiaire, fk_agefodd_stagiaire_type, fk_user_author,fk_user_mod, datec, status_in_session";
		$sql .= " ,fk_soc_link";
		$sql .= " ,fk_soc_requester";
		$sql .= " ,fk_socpeople_sign";
		$sql .= " ,hour_foad";
		$sql .= " ,fk_soc";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire";
		$sql .= " WHERE rowid= " . $id;

		dol_syslog(get_class($this) . "::fetch_stagiaire_per_session", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {

		    $num = $this->db->num_rows($resql);
            if ($num){
                $obj = $this->db->fetch_object($resql);

    			$this->id = $obj->rowid;
    			$this->fk_session_agefodd = $obj->fk_session_agefodd;
    			$this->fk_stagiaire = $obj->fk_stagiaire;
    			$this->fk_agefodd_stagiaire_type = $obj->fk_agefodd_stagiaire_type;
    			$this->fk_soc_link = $obj->fk_soc_link;
    			$this->fk_soc_requester = $obj->fk_soc_requester;
    			$this->fk_socpeople_sign = $obj->fk_socpeople_sign;
    			$this->fk_user_author = $obj->fk_user_author;
    			$this->fk_user_mod = $obj->fk_user_mod;
    			$this->datec = $this->db->jdate($obj->datec);
    			$this->status_in_session = $obj->status_in_session;
    			$this->hour_foad= $obj->hour_foad;
    			$this->fk_soc= $obj->fk_soc;
            }

			$this->db->free($resql);

		    return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_stagiaire_per_session " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	public function fetch_by_trainee($sessid, $traineeid) {
	    $sql = "SELECT";
	    $sql .= " rowid, fk_session_agefodd, fk_stagiaire, fk_agefodd_stagiaire_type, fk_user_author,fk_user_mod, datec, status_in_session";
	    $sql .= " ,fk_soc_link";
	    $sql .= " ,fk_soc_requester";
	    $sql .= " ,fk_socpeople_sign";
	    $sql .= " ,hour_foad";
		$sql .= " ,fk_soc";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire";
	    $sql .= " WHERE fk_session_agefodd = " . $sessid;
	    $sql .= " AND fk_stagiaire = " . $traineeid;

	    dol_syslog(get_class($this) . "::fetch_stagiaire_per_session", LOG_DEBUG);
	    $resql = $this->db->query($sql);
	    if ($resql) {

	        $num = $this->db->num_rows($resql);
	        if ($num){
	            $obj = $this->db->fetch_object($resql);

    	        $this->id = $obj->rowid;
    	        $this->fk_session_agefodd = $obj->fk_session_agefodd;
    	        $this->fk_stagiaire = $obj->fk_stagiaire;
    	        $this->fk_agefodd_stagiaire_type = $obj->fk_agefodd_stagiaire_type;
    	        $this->fk_soc_link = $obj->fk_soc_link;
    	        $this->fk_soc_requester = $obj->fk_soc_requester;
    	        $this->fk_socpeople_sign = $obj->fk_socpeople_sign;
    	        $this->fk_user_author = $obj->fk_user_author;
    	        $this->fk_user_mod = $obj->fk_user_mod;
    	        $this->datec = $this->db->jdate($obj->datec);
    	        $this->status_in_session = $obj->status_in_session;
    	        $this->hour_foad= $obj->hour_foad;
    	        $this->fk_soc= $obj->fk_soc;
	        }

	        $this->db->free($resql);

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
	 * @param int $socid by thridparty
	 * @param int $searchAsLink search as soc link
	 * @param string $sortfield Sort Field
	 * @param string $sortorder Sort Order
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_stagiaire_per_session($id, $socid = null, $searchAsLink = 0, $sortfield='sa.nom', $sortorder='') {
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
		$sql .= ' so.nom as socname, ';
		$sql .= " st.rowid as typeid, st.intitule as type, sa.mail as stamail, sope.email as socpemail,";
		$sql .= " sope.phone as socpphone,";
		$sql .= " sope.phone_mobile as socpphone_mobile,";
		$sql .= " sa.tel1,";
		$sql .= " sa.tel2,";
		$sql .= " sope.phone_mobile as socpphone_mobile,";
		$sql .= " sa.date_birth,";
		$sql .= " sa.place_birth,";
		$sql .= " sa.fk_socpeople,";
		$sql .= " sope.birthday,";
		$sql .= " sope.poste,";
		$sql .= " sa.fonction";
		$sql .= " ,ss.fk_socpeople_sign";
		$sql .= " ,ss.hour_foad";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
		$sql .= " ON sa.rowid = ss.fk_stagiaire";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_civility as civ";
		$sql .= " ON civ.code = sa.civilite";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = ss.fk_soc";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sope";
		$sql .= " ON sope.rowid = sa.fk_socpeople";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire_type as st";
		$sql .= " ON st.rowid = ss.fk_agefodd_stagiaire_type";
		$sql .= " WHERE s.rowid = " . $id;
		if (! empty($socid))
			$sql .= " AND so.rowid = " . $socid;
		if (! empty($sortfield)) {
			$sql .= $this->db->order($sortfield,$sortorder);
		}

		dol_syslog(get_class($this) . "::fetch_stagiaire_per_session", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ( $obj = $this->db->fetch_object($resql) ) {

				if (! empty($searchAsLink)) {
					$linesadded[] = $obj->rowid;
				}

				$line = new AgfTraineeSessionLine();

				$line->stagerowid = $obj->rowid;
				$line->sessid = $obj->sessid;
				$line->id = $obj->fk_stagiaire;
				$line->nom = $obj->nom;
				$line->prenom = $obj->prenom;
				$line->civilite = $obj->civilite;
				$line->civilitel = $langs->trans($obj->civilitel);
				$line->socname = $obj->socname;
				$line->socid = $obj->socid;
				$line->soccode = $obj->soccode;
				$line->socname = $obj->socname;
				$line->fk_soc_link = $obj->fk_soc_link;
				$line->fk_soc_requester = $obj->fk_soc_requester;
				$line->typeid = $obj->typeid;
				$line->status_in_session = $obj->status_in_session;
				$line->hour_foad= $obj->hour_foad;
				$line->place_birth = $obj->place_birth;
				if (empty($obj->date_birth)) {
					$line->date_birth = $this->db->jdate($obj->birthday);
				} else {
					$line->date_birth = $this->db->jdate($obj->date_birth);
				}
				$line->datebirthformated = dol_print_date($line->date_birth,'%d/%m/%Y');

				$line->type = $obj->type;
				$line->fk_socpeople_sign = $obj->fk_socpeople_sign;
				$line->fk_socpeople = $obj->fk_socpeople;
				if (empty($obj->stamail)) {
					$line->email = $obj->socpemail;
				} else {
					$line->email = $obj->stamail;
				}
				if (!empty($line->fk_socpeople)) {
					$line->tel1 = $obj->socpphone;
					$line->tel2 = $obj->socpphone_mobile;
				} else {
					$line->tel1 = $obj->tel1;
					$line->tel2 = $obj->tel2;
				}

				if (empty($obj->poste)) {
					$line->poste = $obj->fonction;
				} else {
					$line->poste = $obj->poste;
				}

				$line->fk_agefodd_stagiaire_type = $obj->fk_agefodd_stagiaire_type;

				require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');

				$extrafields = new ExtraFields($this->db);
				$extralabels = $extrafields->fetch_name_optionals_label('agefodd_stagiaire', true);
				if (count($extralabels) > 0) {
					dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
					$stagiaire = new Agefodd_stagiaire($this->db);
					$result = $stagiaire->fetch($line->id);
					$line->agefodd_stagiaire=$stagiaire;
				}
				$this->lines[$i] = $line;
				// dol_syslog(get_class($this) . "::fetch_stagiaire_per_session line=".var_export($line,true));
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
				$sql .= " ,ss.fk_socpeople_sign";
				$sql .= " ,ss.hour_foad";
				$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
				$sql .= " ON s.rowid = ss.fk_session_agefodd";
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
				$sql .= " ON sa.rowid = ss.fk_stagiaire";
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_civility as civ";
				$sql .= " ON civ.code = sa.civilite";
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
				$sql .= " ON so.rowid = ss.fk_soc";
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
					while ( $obj = $this->db->fetch_object($resql) ) {

						if (! in_array($obj->rowid, $linesadded)) {
							$line = new AgfTraineeSessionLine();

							$line->stagerowid = $obj->rowid;
							$line->sessid = $obj->sessid;
							$line->id = $obj->fk_stagiaire;
							$line->nom = $obj->nom;
							$line->prenom = $obj->prenom;
							$line->civilite = $obj->civilite;
							$line->civilitel = $langs->trans($obj->civilitel);
							$line->socname = $obj->socname;
							$line->socid = $obj->socid;
							$line->soccode = $obj->soccode;
							$line->fk_soc_link = $obj->fk_soc_link;
							$line->fk_soc_requester = $obj->fk_soc_requester;
							$line->typeid = $obj->typeid;
							$line->status_in_session = $obj->status_in_session;
							$line->hour_foad= $obj->hour_foad;
							$line->place_birth = $obj->place_birth;
							if (empty($obj->date_birth)) {
								$line->date_birth = $this->db->jdate($obj->birthday);
							} else {
								$line->date_birth = $this->db->jdate($obj->date_birth);
							}
							$line->datebirthformated = dol_print_date($line->date_birth,'%y.%m.%d');

							$line->type = $obj->type;
							$line->fk_socpeople_sign = $obj->fk_socpeople_sign;
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

							$extrafields = new ExtraFields($this->db);
							$extralabels = $extrafields->fetch_name_optionals_label('agefodd_stagiaire', true);
							if (count($extralabels) > 0) {
								dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
								$stagiaire = new Agefodd_stagiaire($this->db);
								$result = $stagiaire->fetch($line->id);
								$line->agefodd_stagiaire=$stagiaire;
							}

							$this->lines[$line->stagerowid] = $line;

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
	public function fetch_stagiaire_per_session_per_OPCA($id, $socid = 0, $trainee_seesion_id = 0) {
		global $langs;
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
		$sql .= " ,ss.fk_socpeople_sign";
		$sql .= " ,ss.hour_foad";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss";
		$sql .= " ON s.rowid = ss.fk_session_agefodd";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_opca as staopca";
		$sql .= " ON s.rowid = staopca.fk_session_agefodd ";
		if (! empty($socid)) {
			$sql .= " AND staopca.fk_soc_OPCA=" . $socid . ' AND staopca.fk_session_trainee=ss.rowid';
		}
		if (! empty($trainee_seesion_id)) {
			$sql .= " AND staopca.fk_session_trainee=" . $trainee_seesion_id;
		}
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sa";
		$sql .= " ON sa.rowid = ss.fk_stagiaire AND sa.fk_soc=staopca.fk_soc_trainee";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_civility as civ";
		$sql .= " ON civ.code = sa.civilite";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = ss.fk_soc";
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
				$line->civilitel = $langs->trans($obj->civilitel);
				$line->socname = $obj->socname;
				$line->socid = $obj->socid;
				$line->soccode = $obj->soccode;
				$line->fk_soc_link = $obj->fk_soc_link;
				$line->fk_soc_requester = $obj->fk_soc_requester;
				$line->typeid = $obj->typeid;
				$line->status_in_session = $obj->status_in_session;
				$line->hour_foad= $obj->hour_foad;
				$line->place_birth = $obj->place_birth;
				if (empty($obj->date_birth)) {
					$line->date_birth = $this->db->jdate($obj->birthday);
				} else {
					$line->date_birth = $this->db->jdate($obj->date_birth);
				}

				$line->type = $obj->type;
				$line->fk_socpeople_sign = $obj->fk_socpeople_sign;
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

				$this->lines[$i] = $line;

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
	 * Retour la liste des financements possible pour un stagiaire
	 *
	 * @param string $filter SQL filter
	 * @return array|int
	 */
	public function fetch_type_fin($filter = '') {
	    $sql = "SELECT t.rowid, t.intitule";
	    $sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire_type as t";
	    if (! empty($filter)) {
	        $sql .= ' WHERE ' . $filter;
	    }
	    $sql .= " ORDER BY t.sort";

	    dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
	    $result = $this->db->query($sql);
	    if ($result) {

	        $TTypes = array();

	        $num = $this->db->num_rows($result);

	        if ($num) {
	            while ( $obj = $this->db->fetch_object($result) ) {

	                $label = stripslashes($obj->intitule);

	                $TTypes[$obj->rowid] = $label;

	            }
	        }

	        $this->db->free($result);
	        return $TTypes;
	    } else {
	        $this->error = "Error " . $this->db->lasterror();
	        dol_syslog(get_class($this) . "::select_type_stagiaire " . $this->error, LOG_ERR);
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
	public function create($user, $notrigger = 0) {
		global $conf;
		$error = 0;

		// Clean parameters
		$this->fk_session_agefodd = $this->db->escape(trim($this->fk_session_agefodd));
		$this->fk_stagiaire = $this->db->escape(trim($this->fk_stagiaire));
		$this->fk_agefodd_stagiaire_type = $this->db->escape(trim($this->fk_agefodd_stagiaire_type));
		$this->status_in_session = $this->db->escape(trim($this->status_in_session));
		$this->fk_soc_link = $this->db->escape(trim($this->fk_soc_link));
		$this->fk_soc_requester = $this->db->escape(trim($this->fk_soc_requester));
		$this->fk_socpeople_sign = $this->db->escape(trim($this->fk_socpeople_sign));
		$this->hour_foad= $this->db->escape(trim($this->hour_foad));
		$this->fk_soc= $this->db->escape(trim($this->fk_soc));

		// Check parameters
		// Put here code to add control on parameters value
		if (! $conf->global->AGF_USE_STAGIAIRE_TYPE) {
			$this->fk_agefodd_stagiaire_type = $conf->global->AGF_DEFAULT_STAGIAIRE_TYPE;
		}
		if (empty($this->status_in_session))
			$this->status_in_session = 0;

			// Determine for trainne subscrition if there already a propospal link with the customer signed
		if ($conf->global->AGF_SESSION_TRAINEE_STATUS_AUTO) {
			$sql = "SELECT propal.rowid ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "propal as propal ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_element as sesselem ";
			$sql .= " ON propal.rowid=sesselem.fk_element AND sesselem.element_type='propal' AND sesselem.fk_session_agefodd=" . $this->fk_session_agefodd . " AND propal.fk_statut=2 ";

			$sql_fk_soc_link = '';
			if (! empty($this->fk_soc_link)) {
				$sql_fk_soc_link = ' propal.fk_soc=' . $this->fk_soc_link . ' OR ';
			}

			$sql .= " WHERE (" . $sql_fk_soc_link . " propal.fk_soc IN (SELECT trainee.fk_soc FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire as trainee WHERE trainee.rowid=" . $this->fk_stagiaire . "))";

			dol_syslog(get_class($this) . "::create", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->lines = array ();
				$num = $this->db->num_rows($resql);
				if ($num > 0) {
					$obj = $this->db->fetch_object($resql);
					if (! empty($obj->rowid)) {
						$this->status_in_session = 2;
					}
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				return - 1;
			}
		}

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_session_stagiaire (";
		$sql .= "fk_session_agefodd, fk_stagiaire, fk_agefodd_stagiaire_type, status_in_session,fk_user_author,fk_user_mod, datec";
		$sql .= " ,fk_soc_link";
		$sql .= " ,fk_soc_requester";
		$sql .= " ,fk_socpeople_sign";
		$sql .= " ,hour_foad";
		$sql .= " ,fk_soc";
		$sql .= ") VALUES (";
		$sql .= $this->fk_session_agefodd . ', ';
		$sql .= $this->fk_stagiaire . ', ';
		$sql .= ((! empty($this->fk_agefodd_stagiaire_type)) ? $this->fk_agefodd_stagiaire_type : "0") . ', ';
		$sql .= $this->status_in_session . ', ';
		$sql .= $user->id . ",";
		$sql .= $user->id . ",";
		$sql .= "'" . $this->db->idate(dol_now()) . "',";
		$sql .= ((! empty($this->fk_soc_link)) ? $this->fk_soc_link : "NULL") . ",";
		$sql .= ((! empty($this->fk_soc_requester)) ? $this->fk_soc_requester : "NULL") . ",";
		$sql .= ((! empty($this->fk_socpeople_sign)) ? $this->fk_socpeople_sign : "NULL") . ",";
		$sql .= ((! empty($this->hour_foad)) ? price2num($this->hour_foad): "NULL"). ",";
		$sql .= ((! empty($this->fk_soc)) ? $this->fk_soc: "NULL");
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_session_stagiaire");
			if (! $notrigger) {

				if (! $notrigger)
				{
					// Call trigger
					$result=$this->call_trigger('AGF_SESSION_STA_CREATE',$user);
					if ($result < 0) { $error++; }
					// End call triggers
				}
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
			$agf_training = new Formation($this->db);
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
				$year = $duration_array[0];
				$month = $duration_array[1];
				$day = $duration_array[2];
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
				$this->errors[] = "Error " . $agf_certif->error;
			} else {

				$certif_type_array = $agf_certif->get_certif_type();

				if (is_array($certif_type_array) && count($certif_type_array) > 0) {
					foreach ( $certif_type_array as $certif_type_id => $certif_type_label ) {
						// Set Certification type to not passed yet
						$result = $agf_certif->set_certif_state($user, $resultcertif, $certif_type_id, 0);
						if ($result < 0) {
							$error ++;
							$this->errors[] = "Error " . $agf_certif->error;
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
	 * @param User $user User who deletes
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0) {
		$this->db->begin();

		$error = 0;

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_convention_stagiaire";
		$sql .= " WHERE fk_agefodd_session_stagiaire = " . $this->id;

		dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
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
					$this->errors[] = "Error " . $session->error;
				}
			}
		} else {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
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
	public function update($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		$this->fk_session_agefodd = $this->db->escape(trim($this->fk_session_agefodd));
		$this->fk_stagiaire = $this->db->escape(trim($this->fk_stagiaire));
		$this->fk_agefodd_stagiaire_type = $this->db->escape(trim($this->fk_agefodd_stagiaire_type));
		$this->fk_soc_link = $this->db->escape(trim($this->fk_soc_link));
		$this->fk_soc_requester = $this->db->escape(trim($this->fk_soc_requester));
		$this->fk_socpeople_sign = $this->db->escape(trim($this->fk_socpeople_sign));
		$this->hour_foad= $this->db->escape(trim($this->hour_foad));
		$this->fk_soc= $this->db->escape(trim($this->fk_soc));

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
		$sql .= " fk_soc_link=" . (!empty($this->fk_soc_link) ? $this->fk_soc_link : "null") . ",";
		$sql .= " fk_soc_requester=" . (!empty($this->fk_soc_requester) ? $this->fk_soc_requester : "null"). ",";
		$sql .= " fk_socpeople_sign=" . (!empty($this->fk_socpeople_sign) ? $this->fk_socpeople_sign : "null"). ",";
		$sql .= " hour_foad=" . (!empty($this->hour_foad) ? price2num($this->hour_foad): "null"). ",";
		$sql .= " fk_soc =" . (!empty($this->fk_soc) ? $this->fk_soc : "null");
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
	public function update_status_by_soc($user, $notrigger = 0, $socid = 0, $status = 0) {
		global $conf, $langs;
		$error = 0;

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_stagiaire SET";
		$sql .= " status_in_session=" . $status;
		$sql .= " ,fk_user_mod=" . $user->id;
		$sql .= " WHERE fk_session_agefodd = " . $this->fk_session_agefodd;
		if (! empty($socid)) {
			// For the same thirdparty as the trainee
			$sql .= ' AND ((fk_soc=' . $socid . '))';
			// For the trainne link with use trhidparty into doc
			$sql .= ' OR (fk_soc_link =' . $socid . '))';
		}

		$this->db->begin();

		dol_syslog(get_class($this) . "::update_status_by_soc", LOG_DEBUG);
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
	public function getLibStatut($mode = 0) {
		return $this->LibStatut($this->status_in_session, $mode);
	}

	/**
	 * Return label of a status (draft, validated, .
	 *
	 *
	 *
	 *
	 * ..)
	 *
	 * @param int $statut
	 * @param int $mode label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * @return string
	 */
	public function LibStatut($statut, $mode = 1) {
		global $langs;

		if (empty($statut))
			$statut = 0;

		$langs->load("agefodd@agefodd");
		if ($mode == 0) {

			return $this->labelstatut[$statut];
		}
		if ($mode == 1) {
			return $this->labelstatut_short[$statut];
		}
		if ($mode == 2) {
			if ($statut == self::STATUS_IN_SESSION_PROSPECT)
				return img_picto($langs->trans('TraineeSessionStatusProspect'), 'statut0') . ' ' . $this->labelstatut_short[$statut];
			if ($statut == self::STATUS_IN_SESSION_VERBAL_AGREEMENT)
				return img_picto($langs->trans('TraineeSessionStatusVerbalAgreement'), 'statut3') . ' ' . $this->labelstatut_short[$statut];
			if ($statut == self::STATUS_IN_SESSION_CONFIRMED)
				return img_picto($langs->trans('TraineeSessionStatusConfirm'), 'statut4') . ' ' . $this->labelstatut_short[$statut];
			if ($statut == self::STATUS_IN_SESSION_TOTALLY_PRESENT)
				return img_picto($langs->trans('TraineeSessionStatusPresent'), 'statut6') . ' ' . $this->labelstatut_short[$statut];
			if ($statut == self::STATUS_IN_SESSION_PARTIALLY_PRESENT)
				return img_picto($langs->trans('TraineeSessionStatusPartPresent'), 'statut7') . ' ' . $this->labelstatut_short[$statut];
			if ($statut == self::STATUS_IN_SESSION_NOT_PRESENT)
				return img_picto($langs->trans('TraineeSessionStatusNotPresent'), 'statut9') . ' ' . $this->labelstatut_short[$statut];
			if ($statut == self::STATUS_IN_SESSION_CANCELED)
				return img_picto($langs->trans('TraineeSessionStatusCancelled'), 'statut8') . ' ' . $this->labelstatut_short[$statut];
			if ($statut == self::STATUS_IN_SESSION_EXCUSED)
				return img_picto($langs->trans('TraineeSessionStatusExcuse'), 'statut9') . ' ' . $this->labelstatut_short[$statut];
		}
		if ($mode == 3) {
			if ($statut == self::STATUS_IN_SESSION_PROSPECT)
				return img_picto($langs->trans('TraineeSessionStatusProspect'), 'statut0');
			if ($statut == self::STATUS_IN_SESSION_VERBAL_AGREEMENT)
				return img_picto($langs->trans('TraineeSessionStatusVerbalAgreement'), 'statut3');
			if ($statut == self::STATUS_IN_SESSION_CONFIRMED)
				return img_picto($langs->trans('TraineeSessionStatusConfirm'), 'statut4');
			if ($statut == self::STATUS_IN_SESSION_TOTALLY_PRESENT)
				return img_picto($langs->trans('TraineeSessionStatusPresent'), 'statut6');
			if ($statut == self::STATUS_IN_SESSION_PARTIALLY_PRESENT)
				return img_picto($langs->trans('TraineeSessionStatusPartPresent'), 'statut7');
			if ($statut == self::STATUS_IN_SESSION_NOT_PRESENT)
				return img_picto($langs->trans('TraineeSessionStatusNotPresent'), 'statut9');
			if ($statut == self::STATUS_IN_SESSION_CANCELED)
				return img_picto($langs->trans('TraineeSessionStatusCancelled'), 'statut8');
			if ($statut == self::STATUS_IN_SESSION_EXCUSED)
				return img_picto($langs->trans('TraineeSessionStatusExcuse'), 'statut9');
		}
		if ($mode == 4) {
			if ($statut == self::STATUS_IN_SESSION_PROSPECT)
				return img_picto($langs->trans('TraineeSessionStatusProspect'), 'statut0') . ' ' . $this->labelstatut[$statut];
			if ($statut == self::STATUS_IN_SESSION_VERBAL_AGREEMENT)
				return img_picto($langs->trans('TraineeSessionStatusVerbalAgreement'), 'statut3') . ' ' . $this->labelstatut[$statut];
			if ($statut == self::STATUS_IN_SESSION_CONFIRMED)
				return img_picto($langs->trans('TraineeSessionStatusConfirm'), 'statut4') . ' ' . $this->labelstatut[$statut];
			if ($statut == self::STATUS_IN_SESSION_TOTALLY_PRESENT)
				return img_picto($langs->trans('TraineeSessionStatusPresent'), 'statut6') . ' ' . $this->labelstatut[$statut];
			if ($statut == self::STATUS_IN_SESSION_PARTIALLY_PRESENT)
				return img_picto($langs->trans('TraineeSessionStatusPartPresent'), 'statut7') . ' ' . $this->labelstatut[$statut];
			if ($statut == self::STATUS_IN_SESSION_NOT_PRESENT)
				return img_picto($langs->trans('TraineeSessionStatusNotPresent'), 'statut9') . ' ' . $this->labelstatut[$statut];
			if ($statut == self::STATUS_IN_SESSION_CANCELED)
				return img_picto($langs->trans('TraineeSessionStatusCancelled'), 'statut8') . ' ' . $this->labelstatut[$statut];
			if ($statut == self::STATUS_IN_SESSION_EXCUSED)
				return img_picto($langs->trans('TraineeSessionStatusExcuse'), 'statut9') . ' ' . $this->labelstatut[$statut];
		}
		if ($mode == 5) {
			if ($statut == self::STATUS_IN_SESSION_PROSPECT)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short[$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusProspect'), 'statut0');
			if ($statut == self::STATUS_IN_SESSION_VERBAL_AGREEMENT)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short[$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusVerbalAgreement'), 'statut3');
			if ($statut == self::STATUS_IN_SESSION_CONFIRMED)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short[$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusConfirm'), 'statut4');
			if ($statut == self::STATUS_IN_SESSION_TOTALLY_PRESENT)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short[$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusPresent'), 'statut6');
			if ($statut == self::STATUS_IN_SESSION_TOTALLY_PRESENT)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short[$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusPartPresent'), 'statut7');
			if ($statut == self::STATUS_IN_SESSION_NOT_PRESENT)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short[$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusNotPresent'), 'statut9');
			if ($statut == self::STATUS_IN_SESSION_CANCELED)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short[$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusCancelled'), 'statut8');
			if ($statut == self::STATUS_IN_SESSION_EXCUSED)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short[$statut] . ' </span>' . img_picto($langs->trans('TraineeSessionStatusExcuse'), 'statut9');
		}

		return '';
	}
}

/**
 * Session Trainee Link Class
 */
class AgfTraineeSessionLine extends CommonObject {
	public $stagerowid;
	public $sessid;
	public $id;
	public $nom;
	public $prenom;
	public $civilite;
	public $civilitel;
	public $socname;
	public $socid;
	public $typeid;
	public $type;
	public $email;
	public $tel1;
	public $tel2;
	public $fk_socpeople;
	public $date_birth;
	public $place_birth;
	public $status_in_session;
	public $fk_agefodd_stagiaire_type;
	public $poste;
	public $soccode;
	public $fk_soc_link;
	public $fk_soc_requester;
	public $fk_socpeople_sign;
	public $hour_foad;
	public $datebirthformated;
	public function __construct() {
		return 1;
	}
}
