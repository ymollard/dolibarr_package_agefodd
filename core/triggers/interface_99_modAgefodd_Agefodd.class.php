<?php

/**
 * Copyright (C) 2005-2011 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin <regis@dolibarr.fr>
 * Copyright (C) 2012 Florian Henry <florian.henry@open-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file agefodd/core/triggers/interface_90_agefodd.class.php
 * \ingroup agefodd
 * \brief Trigger fired Dolibarr catch by agefodd
 */

/**
 * Class of triggers Agefodd
 */
class InterfaceAgefodd {
	var $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	function __construct($db) {

		$this->db = $db;
		
		$this->name = preg_replace ( '/^Interface/i', '', get_class ( $this ) );
		$this->family = "agefodd";
		$this->description = "When action (agenda event)link to session is changed to session calendar is changed to";
		$this->version = 'dolibarr'; // 'development', 'experimental', 'dolibarr' or version
		$this->picto = 'technic';
	}

	/**
	 * Return name of trigger file
	 *
	 * @return string Name of trigger file
	 */
	function getName() {

		return $this->name;
	}

	/**
	 * Return description of trigger file
	 *
	 * @return string Description of trigger file
	 */
	function getDesc() {

		return $this->description;
	}

	/**
	 * Return version of trigger file
	 *
	 * @return string Version of trigger file
	 */
	function getVersion() {

		global $langs;
		$langs->load ( "admin" );
		
		if ($this->version == 'development')
			return $langs->trans ( "Development" );
		elseif ($this->version == 'experimental')
			return $langs->trans ( "Experimental" );
		elseif ($this->version == 'dolibarr')
			return DOL_VERSION;
		elseif ($this->version)
			return $this->version;
		else
			return $langs->trans ( "Unknown" );
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
	 *
	 * @param string $action code
	 * @param Object $object
	 * @param User $user user
	 * @param Translate $langs langs
	 * @param conf $conf conf
	 * @return int <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	function run_trigger($action, $object, $user, $langs, $conf) {

		dol_include_once ( '/comm/action/class/actioncomm.class.php' );
		dol_include_once ( '/agefodd/class/agefodd_session_calendrier.class.php' );
		dol_include_once ( '/agefodd/class/agefodd_session_formateur_calendrier.class.php' );
		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action
		
		// Users
		if ($action == 'ACTION_MODIFY') {
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			if ($object->type_code == 'AC_AGF_SESS') {
				
				$action = new ActionComm ( $this->db );
				$result = $action->fetch ( $object->id );
				
				if ($result != - 1) {
					
					if ($object->id == $action->id) {
						
						$agf_cal = new Agefodd_sesscalendar ( $this->db );
						$result = $agf_cal->fetch_by_action ( $action->id );
						if ($result != - 1) {
							
							$dt_array = getdate ( $action->datep );
							$agf_cal->date_session = dol_mktime ( 0, 0, 0, $dt_array ['mon'], $dt_array ['mday'], $dt_array ['year'] );
							$agf_cal->heured = $action->datep;
							$agf_cal->heuref = $action->datef;
							
							$result = $agf_cal->update ( $user, 1 );
							
							if ($result == - 1) {
								dol_syslog ( get_class ( $this ) . "::run_trigger " . $agf_cal->error, LOG_ERR );
								return - 1;
							}
						}
					}
				}
			}
			if ($object->type_code == 'AC_AGF_SESST') {
				
				$action = new ActionComm ( $this->db );
				$result = $action->fetch ( $object->id );
				
				if ($result != - 1) {
					
					if ($object->id == $action->id) {
						
						$agf_cal = new Agefoddsessionformateurcalendrier ( $this->db );
						$result = $agf_cal->fetch_by_action ( $action->id );
						if ($result != - 1) {
							
							$dt_array = getdate ( $action->datep );
							$agf_cal->date_session = dol_mktime ( 0, 0, 0, $dt_array ['mon'], $dt_array ['mday'], $dt_array ['year'] );
							$agf_cal->heured = $action->datep;
							$agf_cal->heuref = $action->datef;
							$agf_cal->trainer_cost = $agf_cal->trainer_cost;
							
							$result = $agf_cal->update ( $user, 1 );
							
							if ($result == - 1) {
								dol_syslog ( get_class ( $this ) . "::run_trigger " . $agf_cal->error, LOG_ERR );
								return - 1;
							}
						}
					}
				}
			}
			
			return 1;
		} 		// Envoi fiche pédago par mail
		elseif ($action == 'FICHEPEDAGO_SENTBYMAIL') {
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			if ($object->actiontypecode == 'AC_AGF_PEDAG') {
				
				dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id );
				$langs->load ( "agefodd@agefodd" );
				$langs->load ( "agenda" );
				
				if (empty ( $object->actionmsg2 ))
					$object->actionmsg2 = $langs->transnoentities ( "AgfFichePedaSentByEMail", $object->ref );
				if (empty ( $object->actionmsg )) {
					$object->actionmsg = $langs->transnoentities ( "AgfFichePedaSentByEMail", $object->ref );
					$object->actionmsg .= "\n" . $langs->transnoentities ( "Author" ) . ': ' . $user->login;
				}
				
				$ok = 1;
			}
		} 		// Envoi fiche présence par mail
		elseif ($action == 'FICHEPRESENCE_SENTBYMAIL') {
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			if ($object->actiontypecode == 'AC_AGF_PRES') {
				
				dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id );
				$langs->load ( "agefodd@agefodd" );
				$langs->load ( "agenda" );
				
				if (empty ( $object->actionmsg2 ))
					$object->actionmsg2 = $langs->transnoentities ( "AgfFichePresenceSentByEMail", $object->ref );
				if (empty ( $object->actionmsg )) {
					$object->actionmsg = $langs->transnoentities ( "AgfFichePresenceSentByEMail", $object->ref );
					$object->actionmsg .= "\n" . $langs->transnoentities ( "Author" ) . ': ' . $user->login;
				}
				
				$ok = 1;
			}
		} 		// Envoi convention par mail
		elseif ($action == 'CONVENTION_SENTBYMAIL') {
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			if ($object->actiontypecode == 'AC_AGF_CONVE') {
				
				dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id );
				$langs->load ( "agefodd@agefodd" );
				$langs->load ( "agenda" );
				
				if (empty ( $object->actionmsg2 ))
					$object->actionmsg2 = $langs->transnoentities ( "AgfConventionSentByEMail", $object->ref );
				if (empty ( $object->actionmsg )) {
					$object->actionmsg = $langs->transnoentities ( "AgfConventionSentByEMail", $object->ref );
					$object->actionmsg .= "\n" . $langs->transnoentities ( "Author" ) . ': ' . $user->login;
				}
				
				$ok = 1;
			}
		} 		// Envoi attestation par mail
		elseif ($action == 'ATTESTATION_SENTBYMAIL') {
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			if ($object->actiontypecode == 'AC_AGF_ATTES') {
				
				dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id );
				$langs->load ( "agefodd@agefodd" );
				$langs->load ( "agenda" );
				
				if (empty ( $object->actionmsg2 ))
					$object->actionmsg2 = $langs->transnoentities ( "AgfConventionSentByEMail", $object->ref );
				if (empty ( $object->actionmsg )) {
					$object->actionmsg = $langs->transnoentities ( "AgfConventionSentByEMail", $object->ref );
					$object->actionmsg .= "\n" . $langs->transnoentities ( "Author" ) . ': ' . $user->login;
				}
				
				$ok = 1;
			}
		} elseif ($action == 'CLOTURE_SENTBYMAIL') {
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			if ($object->actiontypecode == 'AC_AGF_CLOT') {
				
				dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id );
				$langs->load ( "agefodd@agefodd" );
				$langs->load ( "agenda" );
				
				if (empty ( $object->actionmsg2 ))
					$object->actionmsg2 = $langs->transnoentities ( "AgfClotureSentByEmail", $object->ref );
				if (empty ( $object->actionmsg )) {
					$object->actionmsg = $langs->transnoentities ( "AgfClotureSentByEmail", $object->ref );
					$object->actionmsg .= "\n" . $langs->transnoentities ( "Author" ) . ': ' . $user->login;
				}
				
				$ok = 1;
			}
		} elseif ($action == 'CONVOCATION_SENTBYMAIL') {
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			if ($object->actiontypecode == 'AC_AGF_CONVO') {
				
				dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id );
				$langs->load ( "agefodd@agefodd" );
				$langs->load ( "agenda" );
				
				if (empty ( $object->actionmsg2 ))
					$object->actionmsg2 = $langs->transnoentities ( "AgfConvocationByEmail", $object->ref );
				if (empty ( $object->actionmsg )) {
					$object->actionmsg = $langs->transnoentities ( "AgfConvocationByEmail", $object->ref );
					$object->actionmsg .= "\n" . $langs->transnoentities ( "Author" ) . ': ' . $user->login;
				}
				
				$ok = 1;
			}
		} elseif ($action == 'CONSEILS_SENTBYMAIL') {
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			if ($object->actiontypecode == 'AC_AGF_CONSE') {
				
				dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id );
				$langs->load ( "agefodd@agefodd" );
				$langs->load ( "agenda" );
				
				if (empty ( $object->actionmsg2 ))
					$object->actionmsg2 = $langs->transnoentities ( "AgfConseilsPratiqueByEmail", $object->ref );
				if (empty ( $object->actionmsg )) {
					$object->actionmsg = $langs->transnoentities ( "AgfConseilsPratiqueByEmail", $object->ref );
					$object->actionmsg .= "\n" . $langs->transnoentities ( "Author" ) . ': ' . $user->login;
				}
				
				$ok = 1;
			}
		} elseif ($action == 'ACCUEIL_SENTBYMAIL') {
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			if ($object->actiontypecode == 'AC_AGF_ACCUE') {
				
				dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id );
				$langs->load ( "agefodd@agefodd" );
				$langs->load ( "agenda" );
				
				if (empty ( $object->actionmsg2 ))
					$object->actionmsg2 = $langs->transnoentities ( "AgfCourrierAcceuilByEmail", $object->ref );
				if (empty ( $object->actionmsg )) {
					$object->actionmsg = $langs->transnoentities ( "AgfCourrierAcceuilByEmail", $object->ref );
					$object->actionmsg .= "\n" . $langs->transnoentities ( "Author" ) . ': ' . $user->login;
				}
				
				$ok = 1;
			}
		}
		
		// Add entry in event table
		if ($ok) {
			$now = dol_now ();
			
			require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
			require_once (DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php');
			$contactforaction = new Contact ( $this->db );
			$societeforaction = new Societe ( $this->db );
			if ($object->sendtoid > 0)
				$contactforaction->fetch ( $object->sendtoid );
			if ($object->socid > 0)
				$societeforaction->fetch ( $object->socid );
				
				// Insertion action
			require_once (DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php');
			
			$actioncomm = new ActionComm ( $this->db );
			$actioncomm->type_code = $object->actiontypecode;
			$actioncomm->label = $object->actionmsg2;
			$actioncomm->note = $object->actionmsg;
			$actioncomm->datep = $now;
			$actioncomm->datef = $now;
			$actioncomm->durationp = 0;
			$actioncomm->punctual = 1;
			$actioncomm->percentage = - 1; // Not applicable
			$actioncomm->contact = $contactforaction;
			$actioncomm->societe = $societeforaction;
			$actioncomm->author = $user; // User saving action
			                             // $actioncomm->usertodo = $user; // User affected to action
			$actioncomm->userdone = $user; // User doing action
			$actioncomm->fk_element = $object->id;
			$actioncomm->elementtype = $object->element;
			$ret = $actioncomm->add ( $user ); // User qui saisit l'action
			if ($ret > 0) {
				return 1;
			} else {
				$error = "Failed to insert : " . $actioncomm->error . " ";
				$this->error = $error;
				
				dol_syslog ( "interface_modAgefodd_Agefodd.class.php: " . $this->error, LOG_ERR );
				return - 1;
			}
		}
		
		// Update action label if training is change on a session
		if ($action == 'AGSESSION_UPDATE') {
			// Change Trainning session actino if needed
			require_once (DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php');
			
			$actioncomm = new ActionComm ( $this->db );
			$actioncomm->getActions ( 0, $object->id, 'agefodd_agsession' );
			
			dol_include_once ( '/agefodd/class/agefodd_formation_catalogue.class.php' );
			
			$agftraincat = new Agefodd ( $this->db );
			$agftraincat->fetch ( $object->fk_formation_catalogue );
			
			$num = count ( $actioncomm->actions );
			if ($num) {
				foreach ( $actioncomm->actions as $action ) {
					if (strpos ( $action->label, $agftraincat->intitule ) === false) {
						$action->label = $agftraincat->intitule . '(' . $agftraincat->ref_obj . ')';
						$ret = $action->update ( $user );
						
						if ($ret < 0) {
							$error = "Failed to update : " . $action->error . " ";
							$this->error = $error;
							
							dol_syslog ( "interface_modAgefodd_Agefodd.class.php: " . $this->error, LOG_ERR );
							return - 1;
						}
					}
				}
			}
			
			return 1;
		} elseif ($action == 'CONTACT_MODIFY') {
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			dol_include_once ( '/agefodd/class/agefodd_stagiaire.class.php' );
			
			// Find trainee link with this contact
			$sql = "SELECT";
			$sql .= " s.rowid,  s.fk_socpeople";
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire as s";
			$sql .= " WHERE s.fk_socpeople=" . $object->id;
			
			dol_syslog ( 'interface_modAgefodd_Agefodd.class.php: $sql=' . $sql, LOG_DEBUG );
			$resql = $this->db->query ( $sql );
			if ($resql) {
				if ($this->db->num_rows ( $resql )) {
					$sta = new Agefodd_stagiaire ( $this->db );
					
					$obj = $this->db->fetch_object ( $resql );
					
					$sta->id = $obj->rowid;
					$sta->nom = $object->lastname;
					$sta->prenom = $object->firstname;
					$sta->civilite = $object->civilite_id;
					$sta->socid = $object->socid;
					$sta->fonction = $object->poste;
					$sta->tel1 = $object->phone_pro;
					$sta->tel2 = $object->phone_mobile;
					$sta->mail = $object->email;
					$sta->fk_socpeople = $object->id;
					$sta->date_birth = $object->birthday;
					
					$result = $sta->update ( $user );
					if ($result < 0) {
						$error = "Failed to update trainee : " . $sta->error . " ";
						$this->error = $error;
						
						dol_syslog ( "interface_modAgefodd_Agefodd.class.php: " . $this->error, LOG_ERR );
						return - 1;
					}
				}
			} else {
				$error = "Failed to update find link to contact : " . $this->db->lasterror () . " ";
				$this->error = $error;
				
				dol_syslog ( "interface_modAgefodd_Agefodd.class.php: " . $this->error, LOG_ERR );
				return - 1;
			}
			$this->db->free ( $resql );
			
			return 1;
		} elseif ($action == 'BILL_CREATE') {
			
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			dol_include_once ( '/agefodd/class/agefodd_session_element.class.php' );
			
			$object->fetchObjectLinked ();
			
			foreach ( $object->linkedObjects as $objecttype => $objectslinked ) {
				
				if ($objectslinked [0]->element == 'propal' || $objectslinked [0]->element == 'commande') {
					
					$agf_fin = new Agefodd_session_element ( $this->db );
					
					$result = $agf_fin->add_invoice ( $user, $objectslinked [0]->id, $objectslinked [0]->element, $object->id );
					
					if ($result < 0) {
						$error = "Failed to add agefodd invoice link : " . $agf_fin->error . " ";
						$this->error = $error;
						
						dol_syslog ( "interface_modAgefodd_Agefodd.class.php: " . $this->error, LOG_ERR );
						return - 1;
					}
				}
			}
			
			return 1;
		} elseif ($action == 'PROPAL_CLOSE_SIGNED') {
			
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			dol_include_once ( '/agefodd/class/agefodd_session_element.class.php' );
			$agf_fin = new Agefodd_session_element ( $this->db );
			$agf_fin->fetch_element_by_id ( $object->id, 'prop' );
			
			if (count ( $agf_fin->lines ) > 0) {
				dol_include_once ( '/agefodd/class/agefodd_session_stagiaire.class.php' );
				
				$session_sta = new Agefodd_session_stagiaire ( $this->db );
				$session_sta->fk_session_agefodd = $agf_fin->lines [0]->fk_session_agefodd;
				// Set trainee status to confirm
				$session_sta->update_status_by_soc ( $user, 0, $object->socid, 2 );
				
				$agf_fin->fk_session_agefodd = $agf_fin->lines [0]->fk_session_agefodd;
				// $agf_fin->updateSellingPrice($user,$object->total_ht,'propal');
				$agf_fin->updateSellingPrice ( $user );
			}
			
			return 1;
		} elseif ($action == 'PROPAL_CLOSE_REFUSED') {
			
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			dol_include_once ( '/agefodd/class/agefodd_session_element.class.php' );
			$agf_fin = new Agefodd_session_element ( $this->db );
			$agf_fin->fetch_element_by_id ( $object->id, 'prop' );
			
			if (count ( $agf_fin->lines ) > 0) {
				dol_include_once ( '/agefodd/class/agefodd_session_stagiaire.class.php' );
				
				$session_sta = new Agefodd_session_stagiaire ( $this->db );
				$session_sta->fk_session_agefodd = $agf_fin->lines [0]->fk_session_agefodd;
				$session_sta->update_status_by_soc ( $user, 0, $object->socid, 6 );
			}
			
			return 1;
		} elseif ($action == 'PROPAL_REOPEN') {
			
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			dol_include_once ( '/agefodd/class/agefodd_session_element.class.php' );
			$agf_fin = new Agefodd_session_element ( $this->db );
			$agf_fin->fetch_element_by_id ( $object->id, 'prop' );
			
			if (count ( $agf_fin->lines ) > 0) {
				dol_include_once ( '/agefodd/class/agefodd_session_stagiaire.class.php' );
				
				$session_sta = new Agefodd_session_stagiaire ( $this->db );
				$session_sta->fk_session_agefodd = $agf_fin->lines [0]->fk_session_agefodd;
				$session_sta->update_status_by_soc ( $user, 0, $object->socid, 0 );
			}
			
			return 1;
		} elseif ($action == 'BILL_SUPPLIER_DELETE' && $conf->global->AGF_ADVANCE_COST_MANAGEMENT) {
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			dol_include_once ( '/agefodd/class/agefodd_session_element.class.php' );
			$agf_fin = new Agefodd_session_element ( $this->db );
			$agf_fin->fetch_element_by_id ( $object->id, 'invoice_supplier_trainer' );
			if (count ( $agf_fin->lines ) > 0) {
				$agf_fin->id = $agf_fin->lines [0]->id;
				$agf_fin->delete ( $user );
			}
			
			return 1;
		} elseif ($action == 'PROPAL_DELETE') {
			
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			dol_include_once ( '/agefodd/class/agefodd_session_element.class.php' );
			$agf_fin = new Agefodd_session_element ( $this->db );
			$agf_fin->fetch_element_by_id ( $object->id, 'prop' );
			if (count ( $agf_fin->lines ) > 0) {
				$agf_fin->id = $agf_fin->lines [0]->id;
				$agf_fin->fk_session_agefodd = $agf_fin->lines [0]->fk_session_agefodd;
				$agf_fin->delete ( $user );
			}
			
			return 1;
		} elseif ($action == 'BILL_DELETE') {
			
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			dol_include_once ( '/agefodd/class/agefodd_session_element.class.php' );
			$agf_fin = new Agefodd_session_element ( $this->db );
			$agf_fin->fetch_element_by_id ( $object->id, 'fac' );
			if (count ( $agf_fin->lines ) > 0) {
				$agf_fin->id = $agf_fin->lines [0]->id;
				$agf_fin->fk_session_agefodd = $agf_fin->lines [0]->fk_session_agefodd;
				$agf_fin->delete ( $user );
			}
			
			return 1;
		} elseif ($action == 'ORDER_DELETE') {
			
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			dol_include_once ( '/agefodd/class/agefodd_session_element.class.php' );
			$agf_fin = new Agefodd_session_element ( $this->db );
			$agf_fin->fetch_element_by_id ( $object->id, 'bc' );
			if (count ( $agf_fin->lines ) > 0) {
				$agf_fin->id = $agf_fin->lines [0]->id;
				$agf_fin->fk_session_agefodd = $agf_fin->lines [0]->fk_session_agefodd;
				$agf_fin->delete ( $user );
			}
			
			return 1;
		} elseif ($action == 'LINEBILL_INSERT') {
			
			dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
			
			// Retrieve all inforamtion form session to update invoice line with current session inforamtion
			dol_include_once ( '/agefodd/class/agefodd_session_element.class.php' );
			$agf_fin = new Agefodd_session_element ( $this->db );
			$agf_fin->fetch_element_by_id ( $object->fk_facture, 'fac' );
			if (is_array ( $agf_fin->lines ) && count ( $agf_fin->lines ) > 0) {
				dol_include_once ( '/agefodd/class/agsession.class.php' );
				dol_include_once ( '/agefodd/class/agefodd_session_stagiaire.class.php' );
				$agfsession = new Agsession ( $this->db );
				$agfsession->fetch ( $agf_fin->lines [0]->fk_session_agefodd );
				
				if ($object->fk_product == $agfsession->fk_product && (! empty ( $agfsession->id ))) {
					$desc = '';
					if (! empty ( $agfsession->intitule_custo )) {
						$desc = $agfsession->intitule_custo . "\n" ;
					} else {
						$desc = $agfsession->formintitule . "\n" ;
					}
					$desc .="\n" . dol_print_date ( $agfsession->dated, 'day' );
					if ($agfsession->datef != $agfsession->dated) {
						$desc .= '-' . dol_print_date ( $agfsession->datef, 'day' );
					}
					if (! empty ( $agfsession->duree_session )) {
						$desc .= "\n" . $langs->transnoentities ( 'AgfPDFFichePeda1' ) . ': ' . $agfsession->duree_session . ' ' . $langs->trans ( 'Hour' ) . '(s)';
					}
					if (! empty ( $agfsession->placecode )) {
						$desc .= "\n" . $langs->trans ( 'AgfLieu' ) . ': ' . $agfsession->placecode;
					}
					$session_trainee = new Agefodd_session_stagiaire ( $this->db );
					if ($this->type_session == 0) {
						// For Intra entreprise you take all trainne
						$session_trainee->fetch_stagiaire_per_session ( $agfsession->id );
					} else {
						// For inter entreprise you tkae only trainee link with this OPCA
						$session_trainee->fetch_stagiaire_per_session_per_OPCA ( $agfsession->id, $object->socid );
					}
					if (count ( $session_trainee->lines ) > 0) {
						
						$desc_trainee = "\n" . count ( $session_trainee->lines ) . ' ';
						if (count ( $session_trainee->lines ) >= 1) {
							$desc_trainee .= $langs->trans ( 'AgfParticipant' );
						} else {
							$desc_trainee .= $langs->trans ( 'AgfParticipants' );
						}
						if ($conf->global->AGF_ADD_TRAINEE_NAME_INTO_DOCPROPODR) {
							$desc_trainee .= "\n";
							foreach ( $session_trainee->lines as $line ) {
								
								// Do not output not present or cancelled trainee
								if ($line->status_in_session != 5 && $line->status_in_session != 6) {
									$sessionOPCA = new Agsession ( $this->db );
									if ($this->type_session == 1) {
										$sessionOPCA->getOpcaForTraineeInSession ( $line->socid, $this->id );
									} else {
										$sessionOPCA->num_OPCA_file = $agfsession->num_OPCA_file;
									}
									
									if (! empty ( $sessionOPCA->num_OPCA_file )) {
										$desc_OPCA = "\n" . 'Num dossier OPCA: ' . $sessionOPCA->num_OPCA_file . ' pour ' . $line->socname;
									}
									$desc_trainee .= dol_strtoupper ( $line->nom ) . ' ' . $line->prenom . "\n";
								}
							}
						}
						$desc .= ' ' . $desc_OPCA . $desc_trainee;
					}
					
					$object->desc = $desc;
					
					$result = $object->update ( $user, 1 );
					if ($result < 0) {
						$error = "Failed to update invoice line : " . $invoiceline_current->error . " ";
						$this->error = $error;
						
						dol_syslog ( "interface_modAgefodd_Agefodd.class.php: " . $invoiceline_current->error, LOG_ERR );
						return - 1;
					}
				}
			}
		}
		elseif ($action == 'LINEBILL_SUPPLIER_UPDATE') {
			
				dol_syslog ( "Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id );
				
				dol_include_once ( '/agefodd/class/agefodd_session_element.class.php' );
				$agf_fin = new Agefodd_session_element ( $this->db );
				$agf_fin->fetch_element_by_id ( $object->id, 'invoice_supplier' );
				if (count ( $agf_fin->lines ) > 0) {
					$agf_fin->fk_session_agefodd = $agf_fin->lines[0]->fk_session_agefodd;
					$agf_fin->updateSellingPrice($user);
				}
				
				return 1;
			}
		
		return 0;
	}
}