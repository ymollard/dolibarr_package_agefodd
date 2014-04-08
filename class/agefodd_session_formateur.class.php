<?php
/* Copyright (C) 2007-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
* Copyright (C) 2012-213	Florian Henry		<florian.henry@open-concept.pro>
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
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

/**
 * \file agefodd/class/agefodd_session_formateur.class.php
 * \ingroup agefodd
 * \brief Manage traner session object
 */
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 * Manage traner session object
 */
class Agefodd_session_formateur {
	var $db;
	var $error;
	var $errors = array ();
	var $element = 'agefodd';
	var $table_element = 'agefodd';
	var $id;
	var $sessid;
	var $formid;
	var $lastname;
	var $firstname;
	var $trainer_status;
	var $lines = array ();
	var $labelstatut;
	var $labelstatut_short;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	function __construct($db) {

		global $langs;
		
		$this->db = $db;
		
		$this->labelstatut [0] = $langs->trans ( "TraineeSessionStatusProspect" );
		$this->labelstatut [1] = $langs->trans ( "TraineeSessionStatusVerbalAgreement" );
		$this->labelstatut [2] = $langs->trans ( "TraineeSessionStatusConfirm" );
		$this->labelstatut [3] = $langs->trans ( "TraineeSessionStatusPresent" );
		$this->labelstatut [4] = $langs->trans ( "TraineeSessionStatusPartPresent" );
		$this->labelstatut [5] = $langs->trans ( "TraineeSessionStatusNotPresent" );
		$this->labelstatut [6] = $langs->trans ( "TraineeSessionStatusCancelled" );
		
		$this->labelstatut_short [0] = $langs->trans ( "TraineeSessionStatusProspectShort" );
		$this->labelstatut_short [1] = $langs->trans ( "TraineeSessionStatusVerbalAgreementShort" );
		$this->labelstatut_short [2] = $langs->trans ( "TraineeSessionStatusConfirmShort" );
		$this->labelstatut_short [3] = $langs->trans ( "TraineeSessionStatusPresentShort" );
		$this->labelstatut_short [4] = $langs->trans ( "TraineeSessionStatusPartPresentShort" );
		$this->labelstatut_short [5] = $langs->trans ( "TraineeSessionStatusNotPresentShort" );
		$this->labelstatut_short [6] = $langs->trans ( "TraineeSessionStatusCancelledShort" );
		
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
		$this->sessid = trim ( $this->sessid );
		$this->formid = trim ( $this->formid );
		if (isset ( $this->trainer_status ))
			$this->trainer_status = trim ( $this->trainer_status );
			
			// Check parameters
			
		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_session_formateur(";
		$sql .= "fk_session,fk_agefodd_formateur, fk_user_author,fk_user_mod, datec";
		$sql .= ",trainer_status";
		$sql .= ") VALUES (";
		$sql .= " " . $this->sessid . ", ";
		$sql .= " " . $this->formid . ', ';
		$sql .= " " . $user->id . ', ';
		$sql .= " " . $user->id . ', ';
		$sql .= "'" . $this->db->idate ( dol_now () ) . "',";
		$sql .= " " . (! isset ( $this->trainer_status ) ? '0' : $this->db->escape ( $this->trainer_status ));
		$sql .= ")";
		
		$this->db->begin ();
		
		dol_syslog ( get_class ( $this ) . "::create sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror ();
		}
		if (! $error) {
			$this->id = $this->db->last_insert_id ( MAIN_DB_PREFIX . "agefodd_session_formateur" );
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
				dol_syslog ( get_class ( $this ) . "::create " . $errmsg, LOG_ERR );
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback ();
			return - 1 * $error;
		} else {
			$this->db->commit ();
			return $this->id;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch($id) {

		global $langs;
		
		$sql = "SELECT";
		$sql .= " sf.rowid, sf.fk_session, sf.fk_agefodd_formateur,";
		$sql .= " f.fk_socpeople,";
		$sql .= " sp.lastname, sp.firstname";
		$sql .= " ,sf.trainer_status";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f";
		$sql .= " ON sf.fk_agefodd_formateur = f.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sp";
		$sql .= " ON f.fk_socpeople = sp.rowid";
		$sql .= " WHERE sf.rowid = " . $id;
		
		dol_syslog ( get_class ( $this ) . "::fetch sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if ($resql) {
			if ($this->db->num_rows ( $resql )) {
				$obj = $this->db->fetch_object ( $resql );
				$this->id = $obj->rowid;
				$this->sessid = $obj->fk_session;
				$this->formid = $obj->fk_agefodd_formateur;
				$this->lastname = $obj->lastname;
				$this->firstname = $obj->firstname;
				$this->trainer_status = $obj->trainer_status;
			}
			$this->db->free ( $resql );
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::fetch " . $this->error, LOG_ERR );
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $id session object
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_formateur_per_session($id) {

		global $langs;
		
		$sql = "SELECT";
		$sql .= " sf.rowid, sf.fk_session, sf.fk_agefodd_formateur,";
		$sql .= " f.rowid as formid, f.fk_socpeople, f.fk_user,";
		$sql .= " sp.lastname as name_socp, sp.firstname as firstname_socp, sp.email as email_socp,";
		$sql .= " u.lastname as name_user, u.firstname as firstname_user, u.email as email_user";
		$sql .= " ,sf.trainer_status";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f";
		$sql .= " ON sf.fk_agefodd_formateur = f.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sp";
		$sql .= " ON f.fk_socpeople = sp.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u";
		$sql .= " ON f.fk_user = u.rowid";
		$sql .= " WHERE sf.fk_session = " . $id;
		$sql .= " ORDER BY sf.rowid ASC";
		
		dol_syslog ( get_class ( $this ) . "::fetch_formateur_per_session sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows ( $resql );
			$i = 0;
			
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object ( $resql );
					
					$line = new AgfSessionTrainer ();
					
					$line->opsid = $obj->rowid;
					if (! empty ( $obj->fk_socpeople )) {
						$line->lastname = $obj->name_socp;
						$line->firstname = $obj->firstname_socp;
						$line->email = $obj->email_socp;
					}
					if (! empty ( $obj->fk_user )) {
						$line->lastname = $obj->name_user;
						$line->firstname = $obj->firstname_user;
						$line->email = $obj->email_user;
					}
					
					$line->socpeopleid = $obj->fk_socpeople;
					$line->userid = $obj->fk_user;
					$line->formid = $obj->formid;
					$line->sessid = $obj->fk_session;
					$line->trainer_status = $obj->trainer_status;
					
					$this->lines [$i] = $line;
					
					$i ++;
				}
			}
			$this->db->free ( $resql );
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::fetch_formateur_per_session " . $this->error, LOG_ERR );
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
		$this->opsid = trim ( $this->opsid );
		$this->formid = trim ( $this->formid );
		
		// Check parameters
		// Put here code to add control on parameters values
		
		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_formateur SET";
		$sql .= " fk_agefodd_formateur=" . $this->formid . ",";
		$sql .= " trainer_status=" . (isset ( $this->trainer_status ) ? $this->trainer_status : "null") . ",";
		$sql .= " fk_user_mod=" . $user->id . " ";
		$sql .= " WHERE rowid = " . $this->opsid;
		
		$this->db->begin ();
		
		dol_syslog ( get_class ( $this ) . "::update sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror ();
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
				dol_syslog ( get_class ( $this ) . "::update " . $errmsg, LOG_ERR );
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback ();
			return - 1 * $error;
		} else {
			$this->db->commit ();
			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param int $id of object to delete
	 * @return int <0 if KO, >0 if OK
	 */
	function remove($id) {

		global $conf;
		
		$this->db->begin ();
		
		if ($conf->global->AGF_DOL_TRAINER_AGENDA) {
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'actioncomm WHERE id IN ';
			$sql .= '(SELECT fk_actioncomm FROM ' . MAIN_DB_PREFIX . 'agefodd_session_formateur_calendrier ';
			$sql .= 'WHERE fk_agefodd_session_formateur=' . $id . ')';
			
			dol_syslog ( get_class ( $this ) . "::remove sql=" . $sql, LOG_DEBUG );
			$resql = $this->db->query ( $sql );
			if (! $resql) {
				$error ++;
				$this->errors [] = "Error " . $this->db->lasterror ();
			}
			
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'agefodd_session_formateur_calendrier ';
			$sql .= 'WHERE fk_agefodd_session_formateur=' . $id;
			
			dol_syslog ( get_class ( $this ) . "::remove sql=" . $sql, LOG_DEBUG );
			$resql = $this->db->query ( $sql );
			if (! $resql) {
				$error ++;
				$this->errors [] = "Error " . $this->db->lasterror ();
			}
		}
		
		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_session_formateur";
		$sql .= " WHERE rowid = " . $id;
		
		dol_syslog ( get_class ( $this ) . "::remove sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog ( get_class ( $this ) . "::update " . $errmsg, LOG_ERR );
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback ();
			return - 1 * $error;
		} else {
			$this->db->commit ();
			return 1;
		}
	}

	/**
	 * Return label of status of trainer in session (on going, subcribe, confirm, present, patially present,not present,canceled)
	 *
	 * @param int $mode label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * @return string
	 */
	function getLibStatut($mode = 0) {

		return $this->LibStatut ( $this->trainer_status, $mode );
	}

	/**
	 * Return label of a status (draft, validated, .
	 * ..)
	 *
	 * @param int $statut
	 * @param int $mode label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * @return string
	 */
	function LibStatut($statut, $mode = 1) {

		global $langs;
		
		if (empty ( $statut ))
			$statut = 0;
		
		$langs->load ( "agefodd@agefodd" );
		
		if ($mode == 0) {
			
			return $this->labelstatut [$statut];
		}
		if ($mode == 1) {
			return $this->labelstatut_short [$statut];
		}
		if ($mode == 2) {
			if ($statut == 0)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusProspect' ), 'statut0' ) . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 1)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusVerbalAgreement' ), 'statut3' ) . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 2)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusConfirm' ), 'statut4' ) . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 3)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusPresent' ), 'statut6' ) . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 4)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusPartPresent' ), 'statut7' ) . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 5)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusNotPresent' ), 'statut9' ) . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 6)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusCancelled' ), 'statut8' ) . ' ' . $this->labelstatut_short [$statut];
		}
		if ($mode == 3) {
			if ($statut == 0)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusProspect' ), 'statut0' );
			if ($statut == 1)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusVerbalAgreement' ), 'statut3' );
			if ($statut == 2)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusConfirm' ), 'statut4' );
			if ($statut == 3)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusPresent' ), 'statut6' );
			if ($statut == 4)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusPartPresent' ), 'statut7' );
			if ($statut == 5)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusNotPresent' ), 'statut9' );
			if ($statut == 6)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusCancelled' ), 'statut8' );
		}
		if ($mode == 4) {
			if ($statut == 0)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusProspect' ), 'statut0' ) . ' ' . $this->labelstatut [$statut];
			if ($statut == 1)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusVerbalAgreement' ), 'statut3' ) . ' ' . $this->labelstatut [$statut];
			if ($statut == 2)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusConfirm' ), 'statut4' ) . ' ' . $this->labelstatut [$statut];
			if ($statut == 3)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusPresent' ), 'statut6' ) . ' ' . $this->labelstatut [$statut];
			if ($statut == 4)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusPartPresent' ), 'statut7' ) . ' ' . $this->labelstatut [$statut];
			if ($statut == 5)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusNotPresent' ), 'statut9' ) . ' ' . $this->labelstatut [$statut];
			if ($statut == 6)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusCancelled' ), 'statut8' ) . ' ' . $this->labelstatut [$statut];
		}
		if ($mode == 5) {
			if ($statut == 0)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto ( $langs->trans ( 'TraineeSessionStatusProspect' ), 'statut0' );
			if ($statut == 1)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto ( $langs->trans ( 'TraineeSessionStatusVerbalAgreement' ), 'statut3' );
			if ($statut == 2)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto ( $langs->trans ( 'TraineeSessionStatusConfirm' ), 'statut4' );
			if ($statut == 3)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto ( $langs->trans ( 'TraineeSessionStatusPresent' ), 'statut6' );
			if ($statut == 4)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto ( $langs->trans ( 'TraineeSessionStatusPartPresent' ), 'statut7' );
			if ($statut == 5)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto ( $langs->trans ( 'TraineeSessionStatusNotPresent' ), 'statut9' );
			if ($statut == 6)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto ( $langs->trans ( 'TraineeSessionStatusCancelled' ), 'statut8' );
		}
	}
}

/**
 * Session trainer line Class
 */
class AgfSessionTrainer {
	var $opsid;
	var $lastname;
	var $firstname;
	var $email;
	var $socpeopleid;
	var $userid;
	var $formid;
	var $sessid;
	var $trainer_status;

	/**
	 * Return label of status of trainer in session (on going, subcribe, confirm, present, patially present,not present,canceled)
	 *
	 * @param int $mode label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * @return string
	 */
	function getLibStatut($mode = 0) {

		return $this->LibStatut ( $this->trainer_status, $mode );
	}

	/**
	 * Return label of a status (draft, validated, .
	 * ..)
	 *
	 * @param int $statut
	 * @param int $mode label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * @return string
	 */
	function LibStatut($statut, $mode = 1) {

		global $langs;
		
		if (empty ( $statut ))
			$statut = 0;
		
		$langs->load ( "agefodd@agefodd" );
		
		if ($mode == 0) {
			
			return $this->labelstatut [$statut];
		}
		if ($mode == 1) {
			return $this->labelstatut_short [$statut];
		}
		if ($mode == 2) {
			if ($statut == 0)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusProspect' ), 'statut0' ) . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 1)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusVerbalAgreement' ), 'statut3' ) . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 2)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusConfirm' ), 'statut4' ) . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 3)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusPresent' ), 'statut6' ) . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 4)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusPartPresent' ), 'statut7' ) . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 5)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusNotPresent' ), 'statut9' ) . ' ' . $this->labelstatut_short [$statut];
			if ($statut == 6)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusCancelled' ), 'statut8' ) . ' ' . $this->labelstatut_short [$statut];
		}
		if ($mode == 3) {
			if ($statut == 0)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusProspect' ), 'statut0' );
			if ($statut == 1)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusVerbalAgreement' ), 'statut3' );
			if ($statut == 2)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusConfirm' ), 'statut4' );
			if ($statut == 3)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusPresent' ), 'statut6' );
			if ($statut == 4)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusPartPresent' ), 'statut7' );
			if ($statut == 5)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusNotPresent' ), 'statut9' );
			if ($statut == 6)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusCancelled' ), 'statut8' );
		}
		if ($mode == 4) {
			if ($statut == 0)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusProspect' ), 'statut0' ) . ' ' . $this->labelstatut [$statut];
			if ($statut == 1)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusVerbalAgreement' ), 'statut3' ) . ' ' . $this->labelstatut [$statut];
			if ($statut == 2)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusConfirm' ), 'statut4' ) . ' ' . $this->labelstatut [$statut];
			if ($statut == 3)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusPresent' ), 'statut6' ) . ' ' . $this->labelstatut [$statut];
			if ($statut == 4)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusPartPresent' ), 'statut7' ) . ' ' . $this->labelstatut [$statut];
			if ($statut == 5)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusNotPresent' ), 'statut9' ) . ' ' . $this->labelstatut [$statut];
			if ($statut == 6)
				return img_picto ( $langs->trans ( 'TraineeSessionStatusCancelled' ), 'statut8' ) . ' ' . $this->labelstatut [$statut];
		}
		if ($mode == 5) {
			if ($statut == 0)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto ( $langs->trans ( 'TraineeSessionStatusProspect' ), 'statut0' );
			if ($statut == 1)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto ( $langs->trans ( 'TraineeSessionStatusVerbalAgreement' ), 'statut3' );
			if ($statut == 2)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto ( $langs->trans ( 'TraineeSessionStatusConfirm' ), 'statut4' );
			if ($statut == 3)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto ( $langs->trans ( 'TraineeSessionStatusPresent' ), 'statut6' );
			if ($statut == 4)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto ( $langs->trans ( 'TraineeSessionStatusPartPresent' ), 'statut7' );
			if ($statut == 5)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto ( $langs->trans ( 'TraineeSessionStatusNotPresent' ), 'statut9' );
			if ($statut == 6)
				return '<span class="hideonsmartphone">' . $this->labelstatut_short [$statut] . ' </span>' . img_picto ( $langs->trans ( 'TraineeSessionStatusCancelled' ), 'statut8' );
		}
	}

	function __construct() {

		global $langs;
		
		$this->labelstatut [0] = $langs->trans ( "TraineeSessionStatusProspect" );
		$this->labelstatut [1] = $langs->trans ( "TraineeSessionStatusVerbalAgreement" );
		$this->labelstatut [2] = $langs->trans ( "TraineeSessionStatusConfirm" );
		$this->labelstatut [3] = $langs->trans ( "TraineeSessionStatusPresent" );
		$this->labelstatut [4] = $langs->trans ( "TraineeSessionStatusPartPresent" );
		$this->labelstatut [5] = $langs->trans ( "TraineeSessionStatusNotPresent" );
		$this->labelstatut [6] = $langs->trans ( "TraineeSessionStatusCancelled" );
		
		$this->labelstatut_short [0] = $langs->trans ( "TraineeSessionStatusProspectShort" );
		$this->labelstatut_short [1] = $langs->trans ( "TraineeSessionStatusVerbalAgreementShort" );
		$this->labelstatut_short [2] = $langs->trans ( "TraineeSessionStatusConfirmShort" );
		$this->labelstatut_short [3] = $langs->trans ( "TraineeSessionStatusPresentShort" );
		$this->labelstatut_short [4] = $langs->trans ( "TraineeSessionStatusPartPresentShort" );
		$this->labelstatut_short [5] = $langs->trans ( "TraineeSessionStatusNotPresentShort" );
		$this->labelstatut_short [6] = $langs->trans ( "TraineeSessionStatusCancelledShort" );
		
		return 1;
	}
}