<?php
/*
 * Copyright (C) 2005-2011 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin <regis@dolibarr.fr>
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
// TODO Les triggers peuvent étendre la classe abstraite DolibarrTriggers disponible depuis la 3.7, à voir quand faire la bascule
class InterfaceAgefodd {
	/** @var DoliDB $db */
	public $db;
	public $name;
	public $family;
	public $description;
	public $version;
	public $picto;
	public $error;
	public $errors = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	function __construct($db) {
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
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
		$langs->load("admin");

		if ($this->version == 'development')
			return $langs->trans("Development");
		elseif ($this->version == 'experimental')
			return $langs->trans("Experimental");
		elseif ($this->version == 'dolibarr')
			return DOL_VERSION;
		elseif ($this->version)
			return $this->version;
		else
			return $langs->trans("Unknown");
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
	function runTrigger($action, $object, $user, $langs, $conf) {
		//For 8.0 remove warning
		return $this->run_trigger($action, $object, $user, $langs, $conf);
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
		dol_include_once('/comm/action/class/actioncomm.class.php');
		dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
		dol_include_once('/agefodd/class/agefodd_session_formateur_calendrier.class.php');
		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

		global $conf, $mc;

		if (empty($conf->agefodd->enabled)) return 0;

		// multicompagny tweak
		if (is_object($mc))
		{
			/** @var ActionsMulticompany $mc */
			if(is_array($mc->sharingelements) && !in_array('agefodd', $mc->sharingelements)){
		        $mc->sharingelements[] = 'agefodd';
		    }

		    if(!isset($mc->sharingobjects['agefodd'])){
		        $mc->sharingobjects['agefodd'] = array('element'=>'agefodd');
		    }

			$mc->setValues($conf);
		}

		$ok = 0;

		// Users
		if ($action == 'ACTION_MODIFY') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if ($object->type_code == 'AC_AGF_SESS') {

				$action = new ActionComm($this->db);
				$result = $action->fetch($object->id);

				if ($result != - 1) {

					if ($object->id == $action->id) {

						$agf_cal = new Agefodd_sesscalendar($this->db);
						$result = $agf_cal->fetch_by_action($action->id);
						if ($result > 0) {

							$dt_array = getdate($action->datep);
							$agf_cal->date_session = dol_mktime(0, 0, 0, $dt_array['mon'], $dt_array['mday'], $dt_array['year']);
							$agf_cal->heured = $action->datep;
							$agf_cal->heuref = $action->datef;

							$result = $agf_cal->update($user, 1);

							if ($result == - 1) {
								dol_syslog(get_class($this) . "::run_trigger " . $agf_cal->error, LOG_ERR);
								return - 1;
							}
						}
						elseif(empty($result))
                        {
                            setEventMessage('ActionComAssociatedSessionNotFound', 'warnings');
                        }
						else{
                            dol_syslog(get_class($this) . "::run_trigger " . $agf_cal->error, LOG_ERR);
                            return - 1;
                        }
					}
				}
			}
			if ($object->type_code == 'AC_AGF_SESST') {

				$action = new ActionComm($this->db);
				$result = $action->fetch($object->id);

				if ($result != - 1) {

					if ($object->id == $action->id) {

						$agf_cal = new Agefoddsessionformateurcalendrier($this->db);
						$result = $agf_cal->fetch_by_action($action->id);
                        if ($result > 0) {

							$dt_array = getdate($action->datep);
							$agf_cal->date_session = dol_mktime(0, 0, 0, $dt_array['mon'], $dt_array['mday'], $dt_array['year']);
							$agf_cal->heured = $action->datep;
							$agf_cal->heuref = $action->datef;

							$result = $agf_cal->update($user, 1);

							if ($result == - 1) {
								dol_syslog(get_class($this) . "::run_trigger " . $agf_cal->error, LOG_ERR);
								return - 1;
							}
						}
                        elseif(empty($result))
                        {
                            setEventMessage('ActionComAssociatedSessionNotFound', 'warnings');
                        }
                        else{
                            dol_syslog(get_class($this) . "::run_trigger " . $agf_cal->error, LOG_ERR);
                            return - 1;
                        }
					}
				}
			}

			return 1;
		} 		// Envoi fiche pédago par mail
		elseif ($action == 'FICHEPEDAGO_SENTBYMAIL') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if ($object->actiontypecode == 'AC_AGF_PEDAG') {

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$langs->load("agefodd@agefodd");
				$langs->load("agenda");

				if (empty($object->actionmsg2))
					$object->actionmsg2 = $langs->transnoentities("AgfFichePedaSentByEMail", $object->ref);
				if (empty($object->actionmsg)) {
					$object->actionmsg = $langs->transnoentities("AgfFichePedaSentByEMail", $object->ref);
					$object->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;
				}

				$ok = 1;
			}
		} elseif ($action == 'MISTR_SENTBYMAIL') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if ($object->actiontypecode == 'AC_AGF_MISTR') {

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$langs->load("agefodd@agefodd");
				$langs->load("agenda");

				if (empty($object->actionmsg2))
					$object->actionmsg2 = $langs->transnoentities("AgfMissionTrainerSentByEMail", $object->ref);
				if (empty($object->actionmsg)) {
					$object->actionmsg = $langs->transnoentities("AgfMissionTrainerSentByEMail", $object->ref);
					$object->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;
				}

				$ok = 1;
			}
		}
			// Envoi fiche présence par mail
		elseif ($action == 'FICHEPRESENCE_SENTBYMAIL') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if ($object->actiontypecode == 'AC_AGF_PRES') {

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$langs->load("agefodd@agefodd");
				$langs->load("agenda");

				if (empty($object->actionmsg2))
					$object->actionmsg2 = $langs->transnoentities("AgfFichePresenceSentByEMail", $object->ref);
				if (empty($object->actionmsg)) {
					$object->actionmsg = $langs->transnoentities("AgfFichePresenceSentByEMail", $object->ref);
					$object->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;
				}

				$ok = 1;
			}
		} 		// Envoi convention par mail
		elseif ($action == 'CONVENTION_SENTBYMAIL') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if ($object->actiontypecode == 'AC_AGF_CONVE') {

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$langs->load("agefodd@agefodd");
				$langs->load("agenda");

				if (empty($object->actionmsg2))
					$object->actionmsg2 = $langs->transnoentities("AgfConventionSentByEMail", $object->ref);
				if (empty($object->actionmsg)) {
					$object->actionmsg = $langs->transnoentities("AgfConventionSentByEMail", $object->ref);
					$object->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;
				}

				$ok = 1;

				if (! empty($conf->global->AGF_AUTO_ACT_ADMIN_UPD)) {
					dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
					$admintask = new Agefodd_sessadm($this->db);
					$result = $admintask->updateByTriggerName($user, $object->id, 'AGF_CONV_SEND');
					// TODO Gestion d'erreurs
				}
			}
		} 		// Envoi attestation par mail
		elseif ($action == 'ATTESTATION_SENTBYMAIL') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if ($object->actiontypecode == 'AC_AGF_ATTES') {

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$langs->load("agefodd@agefodd");
				$langs->load("agenda");

				if (empty($object->actionmsg2))
					$object->actionmsg2 = $langs->transnoentities("AgfConventionSentByEMail", $object->ref);
				if (empty($object->actionmsg)) {
					$object->actionmsg = $langs->transnoentities("AgfConventionSentByEMail", $object->ref);
					$object->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;
				}

				$ok = 1;
			}
		} elseif ($action == 'CLOTURE_SENTBYMAIL') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if ($object->actiontypecode == 'AC_AGF_CLOT') {

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$langs->load("agefodd@agefodd");
				$langs->load("agenda");

				if (empty($object->actionmsg2))
					$object->actionmsg2 = $langs->transnoentities("AgfClotureSentByEmail", $object->ref);
				if (empty($object->actionmsg)) {
					$object->actionmsg = $langs->transnoentities("AgfClotureSentByEmail", $object->ref);
					$object->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;
				}

				$ok = 1;
			}
		} elseif ($action == 'ATTESTATION_PRESENCE_TRAINING_SENTBYMAIL') {
                        dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

                        if ($object->actiontypecode == 'AC_AGF_ATTEP') {

                                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                                $langs->load("agefodd@agefodd");
                                $langs->load("agenda");

                                if (empty($object->actionmsg2))
                                        $object->actionmsg2 = $langs->transnoentities("AgfConventionSentByEMail", $object->ref);
                                if (empty($object->actionmsg)) {
                                        $object->actionmsg = $langs->transnoentities("AgfConventionSentByEMail", $object->ref);
                                        $object->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;
                                }

                                $ok = 1;
                        }
		} elseif ($action == 'CONVOCATION_SENTBYMAIL') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if ($object->actiontypecode == 'AC_AGF_CONVO') {

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$langs->load("agefodd@agefodd");
				$langs->load("agenda");

				if (empty($object->actionmsg2))
					$object->actionmsg2 = $langs->transnoentities("AgfConvocationByEmail", $object->ref);
				if (empty($object->actionmsg)) {
					$object->actionmsg = $langs->transnoentities("AgfConvocationByEmail", $object->ref);
					$object->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;
				}

				$ok = 1;
			}
		} elseif ($action == 'CONSEILS_SENTBYMAIL') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if ($object->actiontypecode == 'AC_AGF_CONSE') {

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$langs->load("agefodd@agefodd");
				$langs->load("agenda");

				if (empty($object->actionmsg2))
					$object->actionmsg2 = $langs->transnoentities("AgfConseilsPratiqueByEmail", $object->ref);
				if (empty($object->actionmsg)) {
					$object->actionmsg = $langs->transnoentities("AgfConseilsPratiqueByEmail", $object->ref);
					$object->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;
				}

				$ok = 1;
			}
		} elseif ($action == 'ACCUEIL_SENTBYMAIL') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if ($object->actiontypecode == 'AC_AGF_ACCUE') {

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$langs->load("agefodd@agefodd");
				$langs->load("agenda");

				if (empty($object->actionmsg2))
					$object->actionmsg2 = $langs->transnoentities("AgfCourrierAcceuilByEmail", $object->ref);
				if (empty($object->actionmsg)) {
					$object->actionmsg = $langs->transnoentities("AgfCourrierAcceuilByEmail", $object->ref);
					$object->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;
				}

				$ok = 1;
			}
		} elseif ($action == 'DOCTR_SENTBYMAIL') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if ($object->actiontypecode == 'AC_AGF_DOCTR') {

				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$langs->load("agefodd@agefodd");
				$langs->load("agenda");

				if (empty($object->actionmsg2)) {
					$object->actionmsg2 = $langs->transnoentities("AgfDocTrainerByEmail", $object->ref);
				}
				if (empty($object->actionmsg)) {
					$object->actionmsg = $langs->transnoentities("AgfDocTrainerByEmail", $object->ref);
					$object->actionmsg .= "\n" . $langs->transnoentities("Author") . ': ' . $user->login;
				}

				$ok = 1;
			}
		} elseif ($action == 'ATTESTATIONENDTRAINING_SENTBYMAIL') {
		    dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

		    if ($object->actiontypecode == 'AC_AGF_ATTES') {

		        dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
		        $langs->load("agefodd@agefodd");
		        $langs->load("agenda");

		        if (empty($object->actionmsg2)) {
		            $object->actionmsg2 = $langs->trans('ActionATTESTATION_SENTBYMAIL');
		        }
		        if (empty($object->actionmsg)) {
		            $object->actionmsg = $langs->trans('MailSentBy') . ' ' . $object->from . ' ' . $langs->trans('To') . ' ' . $object->send_email . ".\n";

		        }

		        $ok = 1;
		    }
		}

		// Add entry in event table
		if ($ok) {
			$now = dol_now();

			require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
			require_once (DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php');

				// Insertion action
			require_once (DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php');

			$actioncomm = new ActionComm($this->db);
			$actioncomm->type_code = $object->actiontypecode;
			$actioncomm->label = $object->actionmsg2.'('.$object->id.')';
			$actioncomm->note = $object->actionmsg;
			$actioncomm->datep = $now;
			$actioncomm->datef = $now;
			$actioncomm->durationp = 0;
			$actioncomm->punctual = 1;
			$actioncomm->percentage = - 1; // Not applicable
			$actioncomm->contactid = $object->sendtoid;
			$actioncomm->socid = $object->socid;
			$actioncomm->author = $user; // User saving action
			                             // $actioncomm->usertodo = $user; // User affected to action
			$actioncomm->userdone = $user; // User doing action
			$actioncomm->fk_element = $object->id;
			$actioncomm->elementtype = $object->element;
			$actioncomm->userownerid=$user->id;

			$ret = method_exists($actioncomm, 'create') ? $actioncomm->create($user) : $actioncomm->add($user); // User qui saisit l'action
			if ($ret > 0) {
				return 1;
			} else {
				$error = "Failed to insert : " . $actioncomm->error . " ";
				$this->error = $error;

				dol_syslog("interface_modAgefodd_Agefodd.class.php: " . $this->error, LOG_ERR);
				return - 1;
			}
		}

		// Update action label if training is change on a session
		if ($action == 'AGSESSION_UPDATE') {
			// Change Trainning session actino if needed
			require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

			$actioncomm = new ActionComm($this->db);
			$actioncomm->getActions($this->db, $object->id, 'agefodd_agsession');

			dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');

			$agftraincat = new Formation($this->db);
			$agftraincat->fetch($object->fk_formation_catalogue);

			$num = count($actioncomm->actions);
			if ($num) {
				foreach ( $actioncomm->actions as $action ) {
					if (strpos($action->label, $agftraincat->intitule) === false) {
						$action->label = $agftraincat->intitule . '(' . $agftraincat->ref_obj . ')';
						$ret = $action->update($user);

						if ($ret < 0) {
							$error = "Failed to update : " . $action->error . " ";
							$this->error = $error;

							dol_syslog("interface_modAgefodd_Agefodd.class.php: " . $this->error, LOG_ERR);
							return - 1;
						}
					}
				}
			}

			return 1;
		} elseif ($action == 'CONTACT_MODIFY') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');

			// Find trainee link with this contact
			$sql = "SELECT";
			$sql .= " s.rowid,  s.fk_socpeople";
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire as s";
			$sql .= " WHERE s.fk_socpeople=" . $object->id;

			dol_syslog('interface_modAgefodd_Agefodd.class.php: $sql=' . $sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					$sta = new Agefodd_stagiaire($this->db);

					$obj = $this->db->fetch_object($resql);
					$sta->fetch($obj->rowid);

					$sta->nom = $object->lastname;
					$sta->prenom = $object->firstname;
					$sta->civilite = (empty($object->civility_id)?$object->civility_code:$object->civility_id);
					$sta->socid = $object->socid;
					$sta->fonction = $object->poste;
					$sta->tel1 = $object->phone_pro;
					$sta->tel2 = $object->phone_mobile;
					$sta->mail = $object->email;
					$sta->fk_socpeople = $object->id;
					$sta->date_birth = $object->birthday;

					$result = $sta->update($user);
					if ($result < 0) {
						$error = "Failed to update trainee : " . $sta->error . " ";
						$this->error = $error;

						dol_syslog("interface_modAgefodd_Agefodd.class.php: " . $this->error, LOG_ERR);
						return - 1;
					}
				}
			} else {
				$error = "Failed to update find link to contact : " . $this->db->lasterror() . " ";
				$this->error = $error;

				dol_syslog("interface_modAgefodd_Agefodd.class.php: " . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);

			return 1;
		} elseif ($action == 'BILL_CREATE') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if (empty($conf->global->AGF_NOT_AUTO_LINK_INVOICE)) {

				dol_include_once('/agefodd/class/agefodd_session_element.class.php');
				$object->fetchObjectLinked();
				foreach ( $object->linkedObjects as $objecttype => $objectslinked ) {
					$objectlinked=reset($objectslinked);

					if (($objectlinked->element == 'propal' || $objectlinked->element == 'commande') && ($objectlinked->socid==$object->socid)) {

						$agf_fin = new Agefodd_session_element($this->db);

						$result = $agf_fin->add_invoice($user, $objectlinked->id, $objectlinked->element, $object->id);

						if ($result < 0) {
							$error = "Failed to add agefodd invoice link : " . $agf_fin->error . " ";
							$this->error = $error;

							dol_syslog("interface_modAgefodd_Agefodd.class.php: " . $this->error, LOG_ERR);
							return - 1;
						} elseif($result > 0) {
							dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
							$admintask = new Agefodd_sessadm($this->db);

							$admintask->updateByTriggerName($user, $agf_fin->fk_session_agefodd, 'AGF_BILL_CREATE');
						}
					}
				}

				//If credit note is created from invoice link to the session, link de credit note to the session also
				if($object->element == 'facture' && $object->type==$object::TYPE_CREDIT_NOTE && !empty($object->fk_facture_source)) {

					$origin_invoice=new Facture($this->db);
					$result=$origin_invoice->fetch($object->fk_facture_source);
					if ($result<0) {
						dol_syslog("interface_modAgefodd_Agefodd.class.php: " . $origin_invoice->error, LOG_ERR);
						return - 1;
					}
					// if ($origin_invoice->fk_soc==$object->fk_soc) {
					if ($origin_invoice->socid == $object->socid) { // C'était déjà socid et pas fk_soc en 2.8...
						$agf_fin = new Agefodd_session_element($this->db);
						$result = $agf_fin->add_invoice($user, $object->fk_facture_source, $object->element, $object->id);

						if ($result < 0) {
							$error = "Failed to add agefodd invoice link : " . $agf_fin->error . " ";
							$this->error = $error;

							dol_syslog("interface_modAgefodd_Agefodd.class.php: " . $this->error, LOG_ERR);
							return -1;
						}
					}
				}
			}

			return 1;
		} elseif ($action == 'BILL_SUPPLIER_CREATE') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			dol_include_once('/agefodd/class/agefodd_session_element.class.php');

			$object->fetchObjectLinked();

			foreach ( $object->linkedObjects as $objecttype => $objectslinked ) {
				$objectlinked=reset($objectslinked);
				if ($objectlinked->element == 'order_supplier') {

					$agf_fin = new Agefodd_session_element($this->db);
					//If propal is link to session
					$result = $agf_fin->fetch_element_by_id($objectlinked->id, 'order_supplier');

					if ($result < 0) {
						$this->error = $agf_fin->error;
						dol_syslog(get_class($this).":: error in trigger" . $this->error, LOG_ERR);
						return - 1;
					} else {
						if (is_array($agf_fin->lines) && count($agf_fin->lines)>0) {
							foreach($agf_fin->lines as $elment) {
								$agf_fin->fk_session_agefodd=$elment->fk_session_agefodd;
								$agf_fin->fk_soc=$elment->socid;
								$agf_fin->element_type=str_replace('order', 'invoice', $elment->element_type);
								$agf_fin->fk_element=$object->id;
								$result=$agf_fin->create($user);
								if ($result < 0) {
									$this->error = $agf_fin->error;
									dol_syslog(get_class($this).":: error in trigger" . $this->error, LOG_ERR);
									return - 1;
								}
							}
						}
					}
				}
			}

			return 1;
		}elseif ($action == 'ORDER_CREATE') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);
			if (empty($conf->global->AGF_NOT_AUTO_LINK_COMMANDE)) {
				dol_include_once('/agefodd/class/agefodd_session_element.class.php');

				$object->fetchObjectLinked();

				foreach ( $object->linkedObjects as $objecttype => $objectslinked ) {
					$objectlinked=reset($objectslinked);
					if ($objectlinked->element == 'propal') {

						$agf_fin = new Agefodd_session_element($this->db);
						//If propal is link to session
						$result = $agf_fin->fetch_element_by_id($objectlinked->id, 'propal');

						if ($result < 0) {
							$this->error = $agf_fin->error;
							dol_syslog(get_class($this).":: error in trigger" . $this->error, LOG_ERR);
							return - 1;
						} else {
							if (is_array($agf_fin->lines) && count($agf_fin->lines)>0) {
								$elment = reset($agf_fin->lines);
								$agf_fin->fk_session_agefodd=$elment->fk_session_agefodd;
								$agf_fin->fk_soc=$elment->socid;
								$agf_fin->element_type='order';
								$agf_fin->fk_element=$object->id;
								$result=$agf_fin->create($user);
								if ($result < 0) {
									$this->error = $agf_fin->error;
									dol_syslog(get_class($this).":: error in trigger" . $this->error, LOG_ERR);
									return - 1;
								}
							}
						}
					}
				}
			}

			return 1;
		} elseif ($action == 'PROPAL_CLOSE_SIGNED') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			$agf_fin = new Agefodd_session_element($this->db);
			$agf_fin->fetch_element_by_id($object->id, 'prop');

			if (count($agf_fin->lines) > 0) {

				if (!empty($conf->global->AGF_SESSION_TRAINEE_STATUS_AUTO)) {
					dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
					$session_sta = new Agefodd_session_stagiaire($this->db);
					$session_sta->fk_session_agefodd = $agf_fin->lines[0]->fk_session_agefodd;
					// Set trainee status to confirm
					$session_sta->update_status_by_soc($user, 0, $object->socid, 2);
				}

				$agf_fin->fk_session_agefodd = $agf_fin->lines[0]->fk_session_agefodd;
				// $agf_fin->updateSellingPrice($user,$object->total_ht,'propal');
				$agf_fin->updateSellingPrice($user);
			}

			return 1;
		} elseif ($action == 'PROPAL_CLOSE_REFUSED') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);
				dol_include_once('/agefodd/class/agefodd_session_element.class.php');
				$agf_fin = new Agefodd_session_element($this->db);
				$agf_fin->fetch_element_by_id($object->id, 'prop');

				if (count($agf_fin->lines) > 0) {

					if (!empty($conf->global->AGF_SESSION_TRAINEE_STATUS_AUTO)) {
						dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
						$session_sta = new Agefodd_session_stagiaire($this->db);
						$session_sta->fk_session_agefodd = $agf_fin->lines[0]->fk_session_agefodd;
						$session_sta->update_status_by_soc($user, 0, $object->socid, 6);
					}

					$agf_fin->fk_session_agefodd = $agf_fin->lines[0]->fk_session_agefodd;
					// $agf_fin->updateSellingPrice($user,$object->total_ht,'propal');
					$agf_fin->updateSellingPrice($user);
				}

			return 1;
		} elseif ($action == 'PROPAL_REOPEN') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			$agf_fin = new Agefodd_session_element($this->db);
			$agf_fin->fetch_element_by_id($object->id, 'prop');

			if (count($agf_fin->lines) > 0) {
				if (!empty($conf->global->AGF_SESSION_TRAINEE_STATUS_AUTO)) {
					dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');

					$session_sta = new Agefodd_session_stagiaire($this->db);
					$session_sta->fk_session_agefodd = $agf_fin->lines[0]->fk_session_agefodd;
					$session_sta->update_status_by_soc($user, 0, $object->socid, 0);
				}

				$agf_fin->fk_session_agefodd = $agf_fin->lines[0]->fk_session_agefodd;
				// $agf_fin->updateSellingPrice($user,$object->total_ht,'propal');
				$agf_fin->updateSellingPrice($user);
			}

			return 1;
		} elseif ($action == 'BILL_SUPPLIER_DELETE' && $conf->global->AGF_ADVANCE_COST_MANAGEMENT) {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			$agf_fin = new Agefodd_session_element($this->db);
			//On delete chaque element ligne lié à une session
			foreach($object->lines as $line){
				$agf_fin->fetch_element_by_id($line->id, 'invoice_supplierline');

				if (count($agf_fin->lines) > 0) {
					foreach($agf_fin->lines as $lineAgf){
						$agf_fin->id = $lineAgf->id;
						$agf_fin->fk_session_agefodd = $lineAgf->fk_session_agefodd;
						$agf_fin->delete($user);
					}
				}
			}
			//Puis on delete les elements factures
			$agf_fin->fetch_element_by_id($object->id, 'invoice_supplier');

			if (count($agf_fin->lines) > 0) {
				foreach($agf_fin->lines as $line){
					$agf_fin->id = $line->id;
					$agf_fin->fk_session_agefodd = $line->fk_session_agefodd;
					$agf_fin->delete($user);
				}
			}

			return 1;
		} elseif ($action == 'PROPAL_DELETE') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			$agf_fin = new Agefodd_session_element($this->db);
			$agf_fin->fetch_element_by_id($object->id, 'prop');
			if (count($agf_fin->lines) > 0) {
				$agf_fin->id = $agf_fin->lines[0]->id;
				$agf_fin->fk_session_agefodd = $agf_fin->lines[0]->fk_session_agefodd;
				$agf_fin->delete($user);
			}

			return 1;
		} elseif ($action == 'BILL_DELETE') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			$agf_fin = new Agefodd_session_element($this->db);
			$agf_fin->fetch_element_by_id($object->id, 'fac');
			if (count($agf_fin->lines) > 0) {
				$agf_fin->id = $agf_fin->lines[0]->id;
				$agf_fin->fk_session_agefodd = $agf_fin->lines[0]->fk_session_agefodd;
				$agf_fin->delete($user);
			}

			return 1;
		} elseif ($action == 'ORDER_DELETE') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			$agf_fin = new Agefodd_session_element($this->db);
			$agf_fin->fetch_element_by_id($object->id, 'bc');
			if (count($agf_fin->lines) > 0) {
				$agf_fin->id = $agf_fin->lines[0]->id;
				$agf_fin->fk_session_agefodd = $agf_fin->lines[0]->fk_session_agefodd;
				$agf_fin->delete($user);
			}

			return 1;
		} elseif ($action == 'LINEBILL_INSERT') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);
			dol_include_once('/compta/facture/class/facture.class.php');

			if ($object->type!=Facture::TYPE_DEPOSIT && empty($conf->global->AGF_GET_ORIGIN_LINE_INFO)) {
				// Retrieve all inforamtion form session to update invoice line with current session inforamtion
				dol_include_once('/agefodd/class/agefodd_session_element.class.php');
				$agf_fin = new Agefodd_session_element($this->db);
				$agf_fin->fetch_element_by_id($object->fk_facture, 'fac');
				if (is_array($agf_fin->lines) && count($agf_fin->lines) > 0) {
					dol_include_once('/agefodd/class/agsession.class.php');
					dol_include_once('/agefodd/class/agefodd_opca.class.php');
					dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
					$agfsession = new Agsession($this->db);
					$agfsession->fetch($agf_fin->lines[0]->fk_session_agefodd);

					if ($object->fk_product == $agfsession->fk_product && (!empty($agfsession->id)) && !empty($agfsession->fk_product)) {
						if (!empty($agfsession->intitule_custo)) {
							$desc = $agfsession->intitule_custo . "\n";
						} else {
							$desc = $agfsession->formintitule . "\n";
						}

						if (empty($conf->global->AGF_HIDE_REF_INVOICE_DT_INFO)) {
							$desc .= "\n" . dol_print_date($agfsession->dated, 'day');
							if ($agfsession->datef != $agfsession->dated) {
								$desc .= '-' . dol_print_date($agfsession->datef, 'day');
							}
						}
						if (!empty($agfsession->duree_session)) {
							$desc .= "\n" . $langs->transnoentities('AgfPDFFichePeda1') . ': ' . $agfsession->duree_session . ' ' . $langs->trans('Hour') . '(s)';
						}
						if (!empty($agfsession->placecode)) {
							$desc .= "\n" . $langs->trans('AgfLieu') . ': ' . $agfsession->placecode;
						}
						$session_trainee = new Agefodd_session_stagiaire($this->db);

						//Determine if we are doing update invoice line for thridparty as OPCA in session or just customer
						// For Intra entreprise you take all trainne
						$sessionOPCA = new Agefodd_opca($this->db);
						$find_trainee_by_OPCA = false;
						if (empty($conf->global->AGF_MANAGE_OPCA) || $agfsession->type_session == 0) {
							// For Intra entreprise you take all trainne
							$sessionOPCA->num_OPCA_file = $agfsession->num_OPCA_file;

						} elseif ($agfsession->type_session == 1) {
							// For inter entreprise you tkae only trainee link with this OPCA

							$invoice = new Facture($this->db);
							$result = $invoice->fetch($object->fk_facture);
							if ($result < 0) {
								$this->error = $invoice->error;
								dol_syslog("interface_modAgefodd_Agefodd.class.php: " . $this->error, LOG_ERR);
								return -1;
							}

							$result = $sessionOPCA->getOpcaSession($agf_fin->lines[0]->fk_session_agefodd);
							if ($result < 0) {
								$this->error = $sessionOPCA->error;
								dol_syslog("interface_modAgefodd_Agefodd.class.php: " . $this->error, LOG_ERR);
								return -1;
							}
							if (is_array($sessionOPCA->lines) && count($sessionOPCA->lines) > 0) {
								foreach ($sessionOPCA->lines as $line) {
									if ($line->fk_soc_OPCA == $invoice->socid) {
										$find_trainee_by_OPCA = true;
										break;
									}
								}
							}
						}

						if ($find_trainee_by_OPCA) {
							$session_trainee->fetch_stagiaire_per_session_per_OPCA($agfsession->id, $invoice->socid);
						} else {
							$session_trainee->fetch_stagiaire_per_session($agfsession->id, 0, 1);
						}

						$nbtrainee = count($session_trainee->lines);

						if ($nbtrainee > 0) {
							$desc_OPCA = '';
							$desc_trainee = '';

							if ($conf->global->AGF_ADD_TRAINEE_NAME_INTO_DOCPROPODR) {
								$desc_trainee .= "\n";
								$num_OPCA_file_array = array();
								//$nbtrainee=0;
								foreach ($session_trainee->lines as $line) {

									// Do not output not present or cancelled trainee
									if ($line->status_in_session != 5 && $line->status_in_session != 6) {
										if ($find_trainee_by_OPCA) {
											$sessionOPCA->getOpcaForTraineeInSession($line->socid, $agfsession->id, $line->stagerowid);
										}
										if (!empty($sessionOPCA->num_OPCA_file)) {
											if (!array_key_exists($sessionOPCA->num_OPCA_file, $num_OPCA_file_array)) {
												$desc_OPCA .= "\n" . $langs->trans('AgfNumDossier') . ' : ' . $sessionOPCA->num_OPCA_file . ' ' . $langs->trans('AgfInTheNameOf') . ' ' . $line->socname;
												$num_OPCA_file_array[$sessionOPCA->num_OPCA_file] = $line->socname;
											}
										}
										//if ($line->socid==$invoice->socid) {
											//$nbtrainee++;
											$desc_trainee .= dol_strtoupper($line->nom) . ' ' . $line->prenom . "\n";
										//}
									}
								}
							}

							$desc_trainee_head = "\n" . $nbtrainee . ' ';
							if ($nbtrainee > 1) {
								$desc_trainee_head .= $langs->trans('AgfParticipants');
							} else {
								$desc_trainee_head .= $langs->trans('AgfParticipant');
							}

							$desc .= ' ' . $desc_OPCA . $desc_trainee_head . $desc_trainee;
						}


						// Add average price on all line concern by session training product
						if ($conf->global->AGF_ADD_AVGPRICE_DOCPROPODR) {
							$result = $agfsession->getAvgPrice($object->total_ht, $object->total_ttc);
							if ($result > 0) {
								$desc .= $agfsession->avgpricedesc;
							}
						}


						$object->desc = $desc;

						$result = $object->update($user, 1);
						if ($result < 0) {
							$error = "Failed to update invoice line : " . $object->error . " ";
							$this->error = $error;

							dol_syslog("interface_modAgefodd_Agefodd.class.php: " . $object->error, LOG_ERR);
							return -1;
						}
					}
				}
			}
		} elseif ($action == 'LINEBILL_SUPPLIER_UPDATE') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			//Ligne
			$agf_fin = new Agefodd_session_element($this->db);
			$agf_fin->fetch_element_by_id($object->id, 'invoice_supplierline');
			if (count($agf_fin->lines) > 0) {
				$agf_fin->fk_session_agefodd = $agf_fin->lines[0]->fk_session_agefodd;
				$agf_fin->updateSellingPrice($user);
			}
			//Facture
			$agf_fin = new Agefodd_session_element($this->db);
			$agf_fin->fetch_element_by_id($object->fk_facture_fourn, 'invoice_supplier');
			if (count($agf_fin->lines) > 0) {
				foreach($agf_fin->lines as $line){
					$agf_fin->fk_session_agefodd =$line->fk_session_agefodd;
					$agf_fin->updateSellingPrice($user);
				}
			}

			return 1;
		}	elseif ($action == 'LINEBILL_SUPPLIER_DELETE')
		{

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			$agf_fin = new Agefodd_session_element($this->db);
			$agf_fin->fetch_element_by_id($object->rowid, 'invoice_supplierline');

			if (count($agf_fin->lines) > 0) {
				foreach($agf_fin->lines as $line){
					$agf_fin->id = $line->id;
					$agf_fin->fk_session_agefodd = $line->fk_session_agefodd;
					$agf_fin->delete($user);
				}
			}
			$agf_fin = new Agefodd_session_element($this->db);
			$agf_fin->fetch_element_by_id($object->fk_facture_fourn, 'invoice_supplier');

			if (count($agf_fin->lines) > 0)
			{
				foreach($agf_fin->lines as $line){
					$agf_fin->fk_session_agefodd =$line->fk_session_agefodd;
					$actionPage = GETPOST('action', 'none');
					$lineid = GETPOST('lineid', 'none');
					$agf_fin->updateSellingPrice($user,$actionPage,$lineid);
				}
			}


			return 1;
		}elseif ($action == 'LINEBILL_SUPPLIER_CREATE')
		{

			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".$user->id.". id=".$object->id);

			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			$agf_fin = new Agefodd_session_element($this->db);
			$agf_fin->fetch_element_by_id($object->fk_facture_fourn, 'invoice_supplier');


			if (count($agf_fin->lines) > 0)
			{
				foreach($agf_fin->lines as $line){
					$agf_fin->fk_session_agefodd =$line->fk_session_agefodd;
					$agf_fin->updateSellingPrice($user);
				}
			}

			return 1;
		}
		elseif ($action == 'BILL_VALIDATE') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			$agf_fin = new Agefodd_session_element($this->db);
			$agf_fin->fetch_element_by_id($object->id, 'fac');

			if (count($agf_fin->lines) > 0) {
				$agf_fin->fk_session_agefodd = $agf_fin->lines[0]->fk_session_agefodd;
				$agf_fin->updateSellingPrice($user);

				if (! empty($conf->global->AGF_AUTO_ACT_ADMIN_UPD)) {
					$result = $agf_fin->check_all_invoice_validate($agf_fin->lines[0]->fk_session_agefodd);
					if ($result == 1) {
						dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
						$admintask = new Agefodd_sessadm($this->db);
						$admintask->updateByTriggerName($user, $agf_fin->lines[0]->fk_session_agefodd, 'AGF_INV_CUST_VALID');
					}
				}
			}

			return 1;
		} elseif ($action == 'BILL_SUPPLIER_VALIDATE') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			$agf_fin = new Agefodd_session_element($this->db);

			$agf_fin->fetch_element_by_id($object->id, 'invoice_supplier_trainer');
			if (count($agf_fin->lines) > 0) {
				if (! empty($conf->global->AGF_AUTO_ACT_ADMIN_UPD)) {
					dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
					$admintask = new Agefodd_sessadm($this->db);
					$admintask->updateByTriggerName($user, $agf_fin->lines[0]->fk_session_agefodd, 'AGF_INV_TRAINER_VALID');
				}
			}

			$agf_fin->fetch_element_by_id($object->id, 'invoice_supplier_room');
			if (count($agf_fin->lines) > 0) {
				if (! empty($conf->global->AGF_AUTO_ACT_ADMIN_UPD)) {
					dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
					$admintask = new Agefodd_sessadm($this->db);
					$admintask->updateByTriggerName($user, $agf_fin->lines[0]->fk_session_agefodd, 'AGF_INV_ROOM_VALID');
				}
			}

			$agf_fin->fetch_element_by_id($object->id, 'invoice_supplier_missions');
			if (count($agf_fin->lines) > 0) {
				if (! empty($conf->global->AGF_AUTO_ACT_ADMIN_UPD)) {
					dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
					$admintask = new Agefodd_sessadm($this->db);
					$admintask->updateByTriggerName($user, $agf_fin->lines[0]->fk_session_agefodd, 'AGF_INV_TRIP_VALID');
				}
			}

			return 1;
		} elseif ($action =='LINEPROPAL_UPDATE') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if ($conf->global->AGF_ADD_AVGPRICE_DOCPROPODR) {
				dol_include_once('/agefodd/class/agefodd_session_element.class.php');
				$agf_fin = new Agefodd_session_element($this->db);
				$agf_fin->fetch_element_by_id($object->oldline->fk_propal, 'prop');
				if (is_array($agf_fin->lines) && count($agf_fin->lines) > 0) {
					dol_include_once('/agefodd/class/agsession.class.php');
					$agfsession = new Agsession($this->db);
					$agfsession->fetch($agf_fin->lines[0]->fk_session_agefodd);

					if ($object->oldline->fk_product == $agfsession->fk_product && (! empty($agfsession->id))) {
						$result = $agfsession->getAvgPrice($object->total_ht, $object->total_ttc);
						if ($result > 0) {
							$pattern='/\n'.$langs->trans('AgfTaxHourHT').'.*\n'.$langs->trans('AgfTaxHourTTC').'.*'.$langs->getCurrencySymbol($conf->currency).'/';
							$object->desc = preg_replace($pattern, $agfsession->avgpricedesc, $object->desc);
						}
					}

					$result = $object->update($user, 1);
					if ($result < 0) {
						$error = "Failed to update propal line : " . $object->error . " ";
						$this->error = $error;

						dol_syslog("interface_modAgefodd_Agefodd.class.php: " . $object->error, LOG_ERR);
						return - 1;
					}
				}
			}
		} elseif ($action =='LINEORDER_UPDATE') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if ($conf->global->AGF_ADD_AVGPRICE_DOCPROPODR) {
				dol_include_once('/agefodd/class/agefodd_session_element.class.php');
				$agf_fin = new Agefodd_session_element($this->db);
				$agf_fin->fetch_element_by_id($object->oldline->fk_commande, 'bc');
				if (is_array($agf_fin->lines) && count($agf_fin->lines) > 0) {
					dol_include_once('/agefodd/class/agsession.class.php');
					$agfsession = new Agsession($this->db);
					$agfsession->fetch($agf_fin->lines[0]->fk_session_agefodd);

					if ($object->oldline->fk_product == $agfsession->fk_product && (! empty($agfsession->id))) {
						$result = $agfsession->getAvgPrice($object->total_ht, $object->total_ttc);
						if ($result > 0) {
							$pattern='/\n'.$langs->trans('AgfTaxHourHT').'.*\n'.$langs->trans('AgfTaxHourTTC').'.*'.$langs->getCurrencySymbol($conf->currency).'/';
							$object->desc = preg_replace($pattern, $agfsession->avgpricedesc, $object->desc);
						}
					}

					$result = $object->update($user, 1);
					if ($result < 0) {
						$error = "Failed to update propal line : " . $object->error . " ";
						$this->error = $error;

						dol_syslog("interface_modAgefodd_Agefodd.class.php: " . $object->error, LOG_ERR);
						return - 1;
					}
				}
			}
		}elseif ($action =='LINEBILL_UPDATE') {

			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . $user->id . ". id=" . $object->id);

			if ($conf->global->AGF_ADD_AVGPRICE_DOCPROPODR) {
				dol_include_once('/agefodd/class/agefodd_session_element.class.php');
				$agf_fin = new Agefodd_session_element($this->db);
				$agf_fin->fetch_element_by_id($object->oldline->fk_facture, 'fac');
				if (is_array($agf_fin->lines) && count($agf_fin->lines) > 0) {
					dol_include_once('/agefodd/class/agsession.class.php');
					dol_include_once('/agefodd/class/agefodd_opca.class.php');
					dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
					$agfsession = new Agsession($this->db);
					$agfsession->fetch($agf_fin->lines[0]->fk_session_agefodd);

					if ($object->oldline->fk_product == $agfsession->fk_product && (! empty($agfsession->id))) {
						$result = $agfsession->getAvgPrice($object->total_ht, $object->total_ttc);
						if ($result > 0) {
							$pattern='/\n'.$langs->trans('AgfTaxHourHT').'.*\n'.$langs->trans('AgfTaxHourTTC').'.*'.$langs->getCurrencySymbol($conf->currency).'/';
							$object->desc = preg_replace($pattern, $agfsession->avgpricedesc, $object->desc);
						}
					}

					$result = $object->update($user, 1);
					if ($result < 0) {
						$error = "Failed to update propal line : " . $object->error . " ";
						$this->error = $error;

						dol_syslog("interface_modAgefodd_Agefodd.class.php: " . $object->error, LOG_ERR);
						return - 1;
					}
				}
			}
		}elseif($action == 'CONTACT_DELETE') {
			$this->db->query('UPDATE '.MAIN_DB_PREFIX.'agefodd_stagiaire SET fk_socpeople = NULL WHERE fk_socpeople NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'socpeople)');
		}
		elseif ($action == 'USER_MODIFY')
        {
            if ((empty($object->contactid) && GETPOST('contactid', 'none') > 0) || (!empty($object->contactid) && GETPOST('contactid', 'none') == 0))
            {
                dol_include_once('agefodd/class/agefodd_formateur.class.php');
                // Nous avons peut être un formateur de déclaré avec cet utilisateur, puis il est changé en tant que contact externe
                $formateur = new Agefodd_teacher($this->db);
                $formateur->fetchByUser($object);
                if (!empty($formateur->id))
                {
                    // Cas 1 : utilisateur interne qui passe à externe
                    if (empty($object->contactid))
                    {
                        $formateur->type_trainer = 'socpeople';
                        $formateur->fk_socpeople = GETPOST('contactid', 'none');
                        $formateur->update($user);
                    }
                    // Cas 2 : utilisateur externe qui passe à interne
                    elseif (empty($object->socid))
                    {
                        $formateur->type_trainer = 'user';
                        $formateur->fk_socpeople = null;
                        $formateur->fk_user = $object->id;
                        $formateur->update($user);
                    }
                }
            }
        }

		return 0;
    }
}
