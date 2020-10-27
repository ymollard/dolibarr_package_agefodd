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
 * \file /agefodd/class/actions_agefodd.class.php
 * \ingroup agefodd
 * \brief File of class to manage Session and trainee
 */

/**
 * \class ActionsAnnonce
 * \brief Class to manage Annonce
 */

dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_opca.class.php');
dol_include_once('/agefodd/class/agefodd_place.class.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
dol_include_once('/agefodd/class/agefodd_session_element.class.php');
dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
dol_include_once('/agefodd/class/agefodd_convention.class.php');
dol_include_once('/comm/propal/class/propal.class.php');
dol_include_once( '/agefodd/core/modules/agefodd/modules_agefodd.php');

class ActionsAgefodd
{
	/** @var DoliDB $db */
	protected $db;
	public $dao;
	public $error;
	public $errors = array();
	public $resprints = '';
	public $results = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->error = 0;
		$this->errors = array();
	}

	/**
	 * printSearchForm Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object $object Object to use hooks on
	 * @param string $action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param HookManager $hookmanager class instance
	 * @return int
	 */
	/*public function printSearchForm($parameters, &$object, &$action, $hookmanager) {
	 global $conf, $langs;
	 $langs->load('agefodd@agefodd');

	 if (empty($conf->global->AGEFODD_HIDE_QUICK_SEARCH) && DOL_VERSION <= 3.8) {
	 $out = printSearchForm(dol_buildpath('/agefodd/session/list.php', 1), dol_buildpath('/agefodd/session/list.php', 1), img_object('', 'agefodd@agefodd') . ' ' . $langs->trans("AgfSessionId"), 'agefodd', 'search_id');
	 $out .= printSearchForm(dol_buildpath('/agefodd/trainee/list.php', 1), dol_buildpath('/agefodd/trainee/list.php', 1), img_object('', 'contact') . ' ' . $langs->trans("AgfMenuActStagiaire"), 'agefodd', 'search_namefirstname');
	 }

	 $this->resprints = $out;
	 }*/

	/**
	 * printSearchForm Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object $object Object to use hooks on
	 * @param string $action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param HookManager $hookmanager class instance
	 * @return int
	 */
	public function updateSession($parameters, &$object, &$action, $hookmanager)
	{
		// hack for fileupload.php
		global $conf;
		$conf->agefodd_agsession = $conf->agefodd;

		return 0;
	}

	public function completeTabsHead($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		$contextarray = array('ordersuppliercard', 'propalcard', 'ordercard', 'invoicecard', 'invoicesuppliercard');
		$contextcurrent = explode(':', $parameters['context']);
		$current_obj = $parameters['object'];
		$res_array = array_intersect($contextarray, $contextcurrent);
		if (is_array($res_array) && count($res_array) > 0 && $parameters['mode'] == 'add') {
			$head = $parameters['head'];
			foreach ($head as $key => &$val) {
				if ($val[2] == 'tabAgefodd') {
					dol_include_once('/agefodd/class/agsession.class.php');
					$agf = new Agsession($this->db);
					$resql = $agf->fetch_all_by_order_invoice_propal('', '', 0, 0,
						get_class($current_obj) == 'Commande' ? $current_obj->id : 0,
						get_class($current_obj) == 'Facture' ? $current_obj->id : 0,
						get_class($current_obj) == 'Propal' ? $current_obj->id : 0,
						get_class($current_obj) == 'FactureFournisseur' ? $current_obj->id : 0,
						get_class($current_obj) == 'CommandeFournisseur' ? $current_obj->id : 0);
					if ($resql < 0) {
						setEventMessage('From hook completeTabsHead agefodd :' . $agf->error, 'errors');
					} else {
						$langs->load('agefodd@agefodd');
						if ($resql > 0) $val[1] .= ' <span class="badge">' . $resql . '</span>';
					}
				}
			}
			$this->results = $head;
			return 1;
		}
		$contextarray = array('contactcard');
		$res_array = array_intersect($contextarray, $contextcurrent);
		//var_dump($contextcurrent);
		if (is_array($res_array) && count($res_array) > 0 && $parameters['mode'] == 'add') {
			$head = $parameters['head'];
			foreach ($head as $key => &$val) {
				if ($val[2] == 'tabAgefodd') {
					dol_include_once('/agefodd/class/agefodd_formateur.class.php');
					$trainer = new Agefodd_teacher($this->db);
					$nb_trainer = $trainer->fetch_all('', '', 0, 0, -1, array('f.fk_socpeople' => $current_obj->id));
					if ($nb_trainer < 0) {
						setEventMessage('From hook completeTabsHead agefodd trainer :' . $trainer->error, 'errors');
					}

					dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
					$trainee = new Agefodd_stagiaire($this->db);
					$nb_trainee = $trainee->fetch_all('', '', 0, 0, array('s.fk_socpeople' => $current_obj->id));
					if ($nb_trainee < 0) {
						setEventMessage('From hook completeTabsHead agefodd trainee:' . $trainee->error, 'errors');
					}
					$nb_element = $nb_trainer + $nb_trainee;
					if ($nb_element > 0) {
						$langs->load('agefodd@agefodd');
						$val[1] .= ' <span class="badge">' . $nb_element . '</span>';
					}
				}
			}
			$this->results = $head;
			return 1;
		}

		return 0;
	}

	/**
	 * addSearchEntry Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object $object Object to use hooks on
	 * @param string $action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param HookManager $hookmanager class instance
	 * @return int
	 */
	public function addSearchEntry($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs, $user, $db;
		$langs->load('agefodd@agefodd');

		dol_include_once('/agefodd/core/modules/modAgefodd.class.php');
		$modAgefodd = new modAgefodd($db);

		$arrayresult = array();
		if (empty($conf->global->AGEFODD_HIDE_QUICK_SEARCH) && $user->rights->agefodd->lire && empty($user->societe_id)) {
			$str_search_id = '';
			if (DOL_VERSION < 8) {
				$str_search_id = '&search_id=' . urlencode($parameters['search_boxvalue']);
			}
			$str_search_ref = '';
			if (DOL_VERSION < 8) {
				$str_search_ref = '&search_session_ref=' . urlencode($parameters['search_boxvalue']);
			}
			$str_search_trainee = '';
			if (DOL_VERSION < 8) {
				$str_search_trainee = '&search_namefirstname=' . urlencode($parameters['search_boxvalue']);
			}
			$arrayresult['searchintoagefoddsession'] = array(
				'position' => $modAgefodd->numero,
				'text' => img_object('', 'agefodd@agefodd') . ' ' . $langs->trans("AgfSessionId"),
				'url' => dol_buildpath('/agefodd/session/list.php', 1) . '?search_by=search_id' . $str_search_id
			);
			if (!empty($conf->global->AGEFODD_POSITION_SEARCH_TO_AGEFODD_SESSION)) $arrayresult['searchintoagefoddsession']['position'] = $conf->global->AGEFODD_POSITION_SEARCH_TO_AGEFODD_SESSION;

			$arrayresult['searchintoagefoddsessionref'] = array(
				'position' => $modAgefodd->numero,
				'text' => img_object('', 'agefodd@agefodd') . ' ' . $langs->trans("AgfSessionRef"),
				'url' => dol_buildpath('/agefodd/session/list.php', 1) . '?search_by=search_session_ref' . $str_search_ref
			);
			if (!empty($conf->global->AGEFODD_POSITION_SEARCH_TO_AGEFODD_SESSION_REF)) $arrayresult['searchintoagefoddsessionref']['position'] = $conf->global->AGEFODD_POSITION_SEARCH_TO_AGEFODD_SESSION_REF;

			$arrayresult['searchintoagefoddtrainee'] = array(
				'position' => $modAgefodd->numero,
				'text' => img_object('', 'contact') . ' ' . $langs->trans("AgfMenuActStagiaire"),
				'url' => dol_buildpath('/agefodd/trainee/list.php', 1) . '?search_by=search_namefirstname' . $str_search_trainee
			);
			if (!empty($conf->global->AGEFODD_POSITION_SEARCH_TO_AGEFODD_TRAINEE)) $arrayresult['searchintoagefoddtrainee']['position'] = $conf->global->AGEFODD_POSITION_SEARCH_TO_AGEFODD_TRAINEE;
		}
		$this->results = $arrayresult;

		return 0;
	}

	/**
	 * DoAction Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object $object Object to use hooks on
	 * @param string $action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param HookManager $hookmanager class instance
	 * @return int
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user, $mc;

		if (is_object($mc)) {
			/** @var ActionsMulticompany $mc */
			if (!in_array('agefodd', $mc->sharingelements)) {
				$mc->sharingelements[] = 'agefodd';
			}
			if (!isset($mc->sharingobjects['agefodd'])) {
				$mc->sharingobjects['agefodd'] = array('element' => 'agefodd');
			}
			$mc->setValues($conf);
		}

		$TContext = explode(':', $parameters['context']);

		// AGENDA SECURITY CHECK For Dolibarr >= V9 only
		if (in_array('agendaexport', $TContext)) {
			$agftraineeid = GETPOST('agftraineeid', "int");
			$agftrainerid = GETPOST('agftrainerid', "int");
			$exportkey = GETPOST('exportkey', 'none');

			// replace dolibarr security check for ageffod agenda
			if(!empty($agftraineeid) || !empty($agftrainerid))
			{
				if(!empty($agftrainerid) && md5($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY.'agftrainerid'.$agftrainerid) === $exportkey){
					return 1;
				}
				elseif(!empty($agftraineeid) && md5($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY.'agftraineeid'.$agftraineeid) === $exportkey){
					return 1;
				}
			}

			return 0;
		}

		// EXTERNAL ACCESS MODULE : CUSTOMER GATE
		if (in_array('externalaccesspage', $TContext)) {
			dol_include_once('/agefodd/lib/agf_externalaccess.lib.php');
			dol_include_once('/agefodd/lib/agefodd.lib.php');
			dol_include_once('/agefodd/class/agsession.class.php');
			dol_include_once('/agefodd/class/agefodd_formateur.class.php');
			dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
			dol_include_once('/agefodd/class/agefodd_session_formateur_calendrier.class.php');
			dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');

			// TODO gérer ici les actions de mes pages pour update les données
			$context = Context::getInstance();

			$langs->load('agfexternalaccess@agefodd');

			if ($context->controller == 'agefodd_session_card') {
				if ($action == 'deleteCalendrierFormateur' && GETPOST('sessid', 'none') > 0 && GETPOST('fk_agefodd_session_formateur_calendrier', 'none') > 0) {
					$agsession = new Agsession($this->db);
					if ($agsession->fetch(GETPOST('sessid', 'none')) > 0) // Vérification que la session existe
					{
						$trainer = $agsession->getTrainerFromUser($user);
						if ($trainer) {
							$context->setControllerFound();

							$error = 0;
							$this->db->begin();
							$agf_calendrier_formateur = new Agefoddsessionformateurcalendrier($this->db);
							if ($agf_calendrier_formateur->fetch(GETPOST('fk_agefodd_session_formateur_calendrier', 'none')) > 0) {
								$TCalendrier = _getCalendrierFromCalendrierFormateur($agf_calendrier_formateur, true, true);
								if (is_string($TCalendrier)) {
									$error++;
									$context->setError($langs->trans('Agf_EA_error_sql'));
								} else {
									$billed = 0;
									$agf_calendrier = $TCalendrier[0];
									if (!empty($agf_calendrier)) {

										$billed = $agf_calendrier->billed; // pour un test un peu plus loin

										if (empty($agf_calendrier->billed)) {
											$r = $agf_calendrier->delete($user);
											if ($r < 0) $error++;
										}

									}

									if (empty($billed)) {
										$r = $agf_calendrier_formateur->delete($user);
										if ($r < 0) $error++;
									} else {
										$context->setEventMessages($langs->trans('AgfCantDeleteBilledElement'), 'errors');
										$error++;
									}
								}
							}


							if ($error > 0) {
								$this->db->rollback();
								$context->setError($langs->trans('AgfExternalAccessErrorDeleteCreneau'));
							} else {
								$this->db->commit();
								$context->setEventMessages($langs->transnoentities('AgfCreneauDeleted'));
							}

							$url = $context->getRootUrl(GETPOST('controller', 'none'), '&sessid=' . $agsession->id . '&fromAction=deleteCalendrierFormateur');
							header('Location: ' . $url);
							exit;
						}
					}

					header('Location: ' . $context->getRootUrl(GETPOST('controller', 'none')));
					exit;
				} elseif ($action == "uploadfile" && GETPOST('sessid', 'none') > 0) {
					if (GETPOST('sendit', 'alpha') && !empty($conf->global->MAIN_UPLOAD_DOC)) {
						$upload_dir = $conf->agefodd->dir_output . "/" . GETPOST('sessid', 'none');
						if (!empty($_FILES)) {
							if (is_array($_FILES['userfile']['tmp_name'])) $userfiles = $_FILES['userfile']['tmp_name'];
							else $userfiles = array($_FILES['userfile']['tmp_name']);

							$error = 0;

							foreach ($userfiles as $key => $userfile) {
								if (empty($_FILES['userfile']['tmp_name'][$key])) {
									if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2) {
										$error++;
										$context->setError($langs->trans('ErrorFileSizeTooLarge'));
									} else {
										$error++;
										$context->setError($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")));
									}
								}
							}

							if (!$error) {
								if (!empty($upload_dirold) && !empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
									$result = dol_add_file_process($upload_dirold, 0, 1, 'userfile', GETPOST('savingdocmask', 'alpha'));
								} else {
									$result = dol_add_file_process($upload_dir, 0, 1, 'userfile', GETPOST('savingdocmask', 'alpha'));
								}

								if ($result < 0) {
									$error++;
								}
							}

							$createShareLink=GETPOST("createsharelink_hid", 'int');
							if(! $error && $createShareLink) {
								require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
								$ecmfile = new ECMFiles($this->db);
								$result=$ecmfile->fetch(0, '', dol_osencode("agefodd/" .GETPOST('sessid', 'none') . "/" .$_FILES['userfile']['name'][0]));

								if ($result > 0)
								{
									if (empty($ecmfile->share))
									{
										require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
										$ecmfile->share = getRandomPassword(true);
									}
									$result = $ecmfile->update($user);
									if ($result < 0)
									{
										$context->setError($ecmfile->error);
									}
								} else {
									$context->setError($langs->trans("FailedToAddFileIntoDatabaseIndex"));
								}
							}

							// FIXME Gestion d'erreur si tout ça se passe mal. Comme je connais pas trop portail, je laisse à quelqu'un d'autre - MdLL 10/04/2020
						}
					}
				}
			} else if ($context->controller == 'agefodd_session_card_time_slot' && in_array($action, array('add', 'update')) && GETPOST('sessid', 'int') > 0) {
				$agsession = new Agsession($this->db);
				if ($agsession->fetch(GETPOST('sessid', 'none')) > 0) // Vérification que la session existe
				{
					$trainer = $agsession->getTrainerFromUser($user); // Est ce que mon user (formateur) est bien associé à la session ?
					if ($trainer) {
						$slotid = GETPOST('slotid', 'int');

						$agf_calendrier_formateur = new Agefoddsessionformateurcalendrier($this->db);
						if (!empty($slotid)) $agf_calendrier_formateur->fetch($slotid);

						// Est ce que mon calendrier appartient bien à ma session ? OU que l'id est vide pour un "add"
						if (($agf_calendrier_formateur->id > 0 && $agf_calendrier_formateur->sessid == $agsession->id) || empty($agf_calendrier_formateur->id)) {
							$date_session = GETPOST('date_session', 'none');
							$heured = GETPOST('heured', 'none');
							$heuref = GETPOST('heuref', 'none');
							$status = GETPOST('status', 'none');
							$code_c_session_calendrier_type = GETPOST('code_c_session_calendrier_type', 'none');
                            $note_private = GETPOST('note_private', 'none');

							if (!empty($date_session) && !empty($heured) && !empty($heuref)) {
								$context->setControllerFound();
								dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');

								$error = 0;

								// Je récupère le/les calendrier participants avant modificatino du calendrier formateur
								$TCalendrier = _getCalendrierFromCalendrierFormateur($agf_calendrier_formateur, true, true);
								if (is_string($TCalendrier)) {
									$context->setError($langs->trans('Agf_EA_error_sql'));
									$TCalendrier = array();
								}
								$this->db->begin();

								$agf_calendrier_formateur->sessid = $agsession->id;
								$agf_calendrier_formateur->date_session = strtotime($date_session);
								$agf_calendrier_formateur->heured = strtotime($date_session . ' ' . $heured);
								$agf_calendrier_formateur->heuref = strtotime($date_session . ' ' . $heuref);
								$agf_calendrier_formateur->fk_agefodd_session_formateur = $trainer->agefodd_session_formateur->id;
                                $agf_calendrier_formateur->note_private = $note_private;

								if (in_array($status, array(
									Agefoddsessionformateurcalendrier::STATUS_DRAFT,
									Agefoddsessionformateurcalendrier::STATUS_CONFIRMED,
									Agefoddsessionformateurcalendrier::STATUS_MISSING,
									Agefoddsessionformateurcalendrier::STATUS_CANCELED,
									Agefoddsessionformateurcalendrier::STATUS_FINISH
								))) {
									$old_status = $agf_calendrier_formateur->status;
									$agf_calendrier_formateur->status = $status;
								} else $agf_calendrier_formateur->status = 0;

								if (empty($agf_calendrier_formateur->id)) $r = $agf_calendrier_formateur->create($user);
								else $r = $agf_calendrier_formateur->update($user);

								if ($r <= 0) $error++;

								if (empty($TCalendrier)) {
									$agf_calendrier = new Agefodd_sesscalendar($this->db);
									$agf_calendrier->sessid = $agsession->id;
									$agf_calendrier->date_session = $agf_calendrier_formateur->date_session;
									$agf_calendrier->heured = $agf_calendrier_formateur->heured;
									$agf_calendrier->heuref = $agf_calendrier_formateur->heuref;
									$agf_calendrier->status = $agf_calendrier_formateur->status;
									$agf_calendrier->calendrier_type = $code_c_session_calendrier_type;

									$r = $agf_calendrier->create($user);
									if ($r <= 0) $error++;
									$TCalendrier[] = $agf_calendrier;
								} else {
									// TODO normalement je suis sensé avoir 1 seule valeur, mais le mode de fonctionnement fait qu'il est possible d'en avoir plusieurs
//									foreach ($TCalendrier as &$agf_calendrier)
//									{
									$agf_calendrier = $TCalendrier[0];
									$agf_calendrier->date_session = $agf_calendrier_formateur->date_session;
									$agf_calendrier->heured = $agf_calendrier_formateur->heured;
									$agf_calendrier->heuref = $agf_calendrier_formateur->heuref;
									$agf_calendrier->status = $agf_calendrier_formateur->status;
									$agf_calendrier->calendrier_type = $code_c_session_calendrier_type;
									$r = $agf_calendrier->update($user);
									if ($r <= 0) $error++;
//									}
								}

								$now = dol_now();
								$THour = GETPOST('hours', 'array');
								$stagiaires = new Agefodd_session_stagiaire($this->db);
								$stagiaires->fetch_stagiaire_per_session($agsession->id);
								$duree_session = ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
								foreach ($stagiaires->lines as &$stagiaire) {
									if ($stagiaire->id <= 0) continue;

									$agfssh = new Agefoddsessionstagiaireheures($this->db);
									$result = $agfssh->fetch_by_session($agsession->id, $stagiaire->id, $agf_calendrier->id);
									if ($result < 0) $error++;
									else {
										$duree = 0;
										$forceHoursSum = 0;
										if (!empty($conf->global->AGF_EA_FORCE_HOURS_ON_SAVE)) {
											$forceHoursSum = 1;
										}

										// Si l'absence est planifiée alors on ne decompte pas les heures
										if (!empty($agfssh->planned_absence)) {
											continue;
										}

										if ($forceHoursSum) {
											// CETTE PARTIE EST DEJA GEREE PAR LE JS MAIS JE GARDE LE CODE SOUS LE COUDE AU CAS OU
											// Si le statut passe à "absent", alors je force la saisie du compteur d'heure car c'est du consommé
											if ($agf_calendrier_formateur->status == Agefoddsessionformateurcalendrier::STATUS_MISSING) {
												$duree = $duree_session;
											} // si on passe le status du créneaux en confirmer sans saisir de temps stagiaire, on met le max
											elseif ($agf_calendrier_formateur->status == Agefoddsessionformateurcalendrier::STATUS_CONFIRMED
												&& $agf_calendrier_formateur->status !== $old_status
												&& $THour[$stagiaire->id] == '00:00'
											) {
												$duree = $duree_session;
											} // Si le statut passe à annulé, les heures participants doivent passer à 0 car la session n'a pas eu lieu
											elseif ($agf_calendrier_formateur->status == Agefoddsessionformateurcalendrier::STATUS_CANCELED) {
												$duree = 0;
											} else if ($agf_calendrier->date_session < $now && !empty($THour[$stagiaire->id])) {
												$forceHoursSum = 0;
											}
										}

										if (empty($forceHoursSum)) {
											$tmp = explode(':', $THour[$stagiaire->id]);
											$hours = $tmp[0];
											$minutes = $tmp[1];
											$duree = $hours + $minutes / 60;
										}

										$agfssh->heures = (float)$duree;
										if ($result) $r = $agfssh->update($user);
										else {
											$agfssh->fk_stagiaire = $stagiaire->id;
											$agfssh->fk_calendrier = $agf_calendrier->id;
											$agfssh->fk_session = $agsession->id;
											$r = $agfssh->create($user);
										}

										if ($r < 0) $error++;
										else {
											if ($duree > 0) {
												$r = $agfssh->setStatusAccordingTime($user,$agsession->id,$stagiaire->id);
												if ($r < 0) $error++;
											}
										}
									}
								}

								if (empty($error)) {
									$this->db->commit();

									$sendEmailAlertToTrainees = GETPOST('SendEmailAlertToTrainees', 'int');

									if (!empty($sendEmailAlertToTrainees)) {
										$errorsMsg = array();
										$sendRes = $this->sendCreneauEmailAlertToTrainees($agsession, $agf_calendrier, $stagiaires, $old_status, $errorsMsg);
										if ($sendRes > 0) {
											$context->setEventMessages($langs->trans('AgfNbEmailSended', $sendRes));
										} elseif ($sendRes < 0) {
											$error++;
											$context->setEventMessages($langs->trans('AgfEmailSendError') . $sendRes, 'errors');
										} else {
											$error++;
											$context->setEventMessages($langs->trans('AgfNoEmailSended'), 'warnings');
										}

										if (! empty($errorsMsg)) {
											$error++;
											$context->setEventMessages($errorsMsg, 'errors');
										}
									}
								} else {
									$this->db->rollback();
									$context->setError($langs->trans('AgfExternalAccessErrorCreateOrUpdateCreneau'));
								}

								$redirect = $context->getRootUrl('agefodd_session_card', '&sessid=' . $agsession->id.'&fromaction='.$action);
								if ($context->iframe || empty($conf->global->AGF_EA_FORCE_REDIRECT_TO_LIST_AFTER_SAVE_CRENEAU)) {
									$redirect = $context->getRootUrl('agefodd_session_card_time_slot', '&sessid=' . $agsession->id . '&slotid=' . $agf_calendrier_formateur->id.'&fromaction='.$action);
									if(empty($error)){
										$redirect.= '&action=view';
									}
								}

								$context->setEventMessages($langs->transnoentities('Saved'));

								header('Location: ' . $redirect);
								exit;
							} else {
								$context->setError($langs->trans('AgefoddMissingFieldRequired'));
							}

						}
					}
				}

			} elseif ($context->controller == 'agefodd_trainee_session_list' || $context->controller == 'agefodd_trainee_session_card') {
				$context->title = $langs->trans('AgfExternalAccess_PageTitle_TraineeSessions');
				$context->desc = $langs->trans('AgfExternalAccess_PageDesc_TraineeSessions');
				$context->menu_active[] = 'invoices';
			} elseif ($context->controller == 'agefodd_trainer_agenda') {
				$context->title = $langs->trans('AgfExternalAccess_PageTitle_Agenda');
				$context->desc = $langs->trans('AgfExternalAccess_PageDesc_Agenda');
				$context->menu_active[] = 'invoices';
			} elseif ($context->controller == 'agefodd_event_other') {
				if ($context->action == 'delete') {
					// DELETE
					$trainer = new Agefodd_teacher($this->db);
					if ($trainer->fetchByUser($user) <= 0) {
						$context->setEventMessages($langs->transnoentities('agfSaveEventFetchCurrentTeacher'), 'errors');
					} else {
						include_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
						$event = new ActionComm($this->db);

						// Id for delete
						$id = GETPOST('id', 'int');
						if (!empty($id)) {
							if ($event->fetch(intval($id)) > 0) {
								if ($event->code == 'AC_AGF_NOTAV'
									&& $event->elementid == $trainer->id
									&& $event->elementtype == 'agefodd_formateur'
								) {
									if ($event->delete() > 0) {
										$context->setEventMessages($langs->trans('agfEventDeleted'));
										$context->action = 'eventdeleted';
									} else {
										$context->setEventMessages($langs->trans('agfDeleteEventError'), 'errors');
									}
								} else {
									$context->setEventMessages($langs->trans('agfDeleteEventNotAuthorized'), 'errors');
								}
							} else {
								$context->setEventMessages($langs->trans('agfSaveEventFetchError'), 'errors');
							}
						}
					}
				} elseif ($context->action == 'save') {

					$errors = 0;

					include_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

					$event = new ActionComm($this->db);

					$trainer = new Agefodd_teacher($this->db);
					if ($trainer->fetchByUser($user) <= 0) {
						$errors++;
						$context->setEventMessages($langs->transnoentities('agfSaveEventFetchCurrentTeacher'), 'errors');
					}

					$event->fk_element = $trainer->id;    // Id of record
					$event->elementtype = $trainer->element;   // Type of record. This if property ->element of object linked to.

					// Id for update
					$id = GETPOST('id', 'int');
					if (!empty($id)) {
						if ($event->fetch(intval($id)) < 1) {
							$errors++;
							$context->setEventMessages($langs->trans('agfSaveEventFetchError'), 'errors');
						}
					}

					// Type
					$TAvailableType = getEnventOtherTAvailableType();

					$type = GETPOST('type', 'none');
					if (!empty($id)) {
						$type = $event->type_code; // on update, code could not be change
					}

					if (in_array($type, $TAvailableType)) {
						$typeTitle = $langs->transnoentities('AgfAgendaOtherType_' . $type);
						$event->code = $type;
					} else {
						$typeTitle = $langs->transnoentities('AgfAgendaOtherTypeNotValid');
						$context->setEventMessages($langs->trans('AgfAgendaOtherTypeNotValid'), 'errors');
						$errors++;
					}

					$event->percentage = -1;
					$event->type_code = $event->code;
					$event->label = $typeTitle;


					if ($event->type_code == 'AC_AGF_NOTAV' && $trainer->id > 0) {
						$event->label .= ' : ' . $trainer->firstname . ' ' . $trainer->name;
					}

					$event->note = GETPOST('note', 'nohtml');

					// Get start date
					$heured = GETPOST('heured', 'none');
					$heuredDate = GETPOST('heured-date', 'none'); // it's a fix for firefox and datetime-local
					$heuredTime = GETPOST('heured-time', 'none'); // it's a fix for firefox and datetime-local
					if(empty($heured) && !empty($heuredDate) && !empty($heuredTime)){
						$heured = $heuredDate.'T'.$heuredTime;
					}

					$startDate = parseFullCalendarDateTime($heured);

					if (!empty($startDate)) {
						$event->datep = $startDate->getTimestamp();
					} else {
						$context->setEventMessages($langs->transnoentities('agfSaveEventStartDateInvalid'), 'errors');
						$errors++;
					}

					// Get end date
					$heuref = GETPOST('heuref', 'none');
					$heurefDate = GETPOST('heuref-date', 'none');
					$heurefTime = GETPOST('heuref-time', 'none');
					if(empty($heuref) && !empty($heurefDate) && !empty($heurefTime)){
						$heuref = $heurefDate.'T'.$heurefTime;
					}

					$endDate = parseFullCalendarDateTime($heuref);
					if (!empty($endDate)) {
						$event->datef = $endDate->getTimestamp();
					} else {
						$context->setEventMessages($langs->transnoentities('agfSaveEventEndDateInvalid'), 'errors');
						$errors++;
					}


					// get date
					if ($event->datef <= $event->datep) {
						$context->setEventMessages($langs->transnoentities('agfSaveEventEndDateInvalid'), 'errors');
						$errors++;
					}

					if ($errors > 0) {
						$context->setEventMessages($langs->transnoentities('agfSaveEventOtherErrors'), 'errors');
						$context->action = 'edit';
					} else {
						// Save

						if ($event->id > 0) {
							$saveRes = $event->update($user);
						} else {

							$event->userownerid = $user->id;

							$saveRes = $event->create($user);
						}

						if ($saveRes > 0) {
							$context->setEventMessages($langs->transnoentities('Saved'));
							$context->action = 'saved';
						} else {


							$errors = is_array($event->errors) ? '<br/>' . implode('<br/>', $event->errors) : '';
							if (!empty($event->error)) {
								$errors .= '<br/>' . $event->error;
							}


							$context->setEventMessages($langs->transnoentities('agfSaveEventOtherActionErrors') . $errors, 'errors');
							$context->action = 'edit';
						}

						//$context->setEventMessages($langs->transnoentities('Saved'));

						//header('Location: '.$redirect);
						//exit;
					}
				}
			}

			if ($context->controller == 'agefodd_trainee_session_card' && in_array($action, array('setplannedAbsence')) && GETPOST('sessid', 'int') > 0) {

				include_once __DIR__ . '/agefodd_session_stagiaire.class.php';
				include_once __DIR__ . '/agefodd_stagiaire.class.php';
				include_once __DIR__ . '/agefodd_calendrier.class.php';

				$agsession = new Agsession($this->db);
				$sessid = GETPOST('sessid', 'int');
				$slotid = GETPOST('slotid', 'int');
				if ($agsession->fetch($sessid) > 0) // Vérification que la session existe
				{
					// Trainee exist ?
					$trainee = new Agefodd_stagiaire($this->db);
					if ($trainee->fetch_by_contact($user->contactid) > 0) {
						// Trainee is in session ?
						$sessionStagiaire = new Agefodd_session_stagiaire($this->db);
						if ($sessionStagiaire->fetch_by_trainee($agsession->id, $trainee->id) > 0) {
							$needCreate = true;
							$sessionstagiaireheures = new Agefoddsessionstagiaireheures($this->db);
							if ($sessionstagiaireheures->fetch_by_session($agsession->id, $trainee->id, $slotid) > 0) {
								$needCreate = false;
							}

							// vérification de la configuration
							$calendrier = new Agefodd_sesscalendar($this->db);
							if ($calendrier->fetch($slotid) > 0) {
								if (traineeCanChangeAbsenceStatus($calendrier->heured)) {

									if (GETPOST('plannedAbsence', 'none') == 'missing') {
										$sessionstagiaireheures->planned_absence = 1;
										$successMsg = $langs->trans('AgfSetPlannedAbsenceMissing');
									} else {
										$sessionstagiaireheures->planned_absence = 0;
										$successMsg = $langs->trans('AgfSetPlannedAbsencePresent');
									}

									// on re-crédite les heures disponibles au participants
									$sessionstagiaireheures->heures = 0;

									if ($needCreate) {

										$sessionstagiaireheures->entity = $conf->entity;
										$sessionstagiaireheures->fk_stagiaire = $trainee->id;
										$sessionstagiaireheures->fk_session = $agsession->id;
										$sessionstagiaireheures->fk_calendrier = $slotid;
										$sessionstagiaireheures->fk_user_author = $user->id;
										$sessionstagiaireheures->mail_sended = 0;

										$res = $sessionstagiaireheures->create($user);
									} else {
										$res = $sessionstagiaireheures->update($user);
									}

									if ($res > 0) {
										$context->setEventMessages($successMsg);


										// SEND EMAIL
										$errorsMsg = array();
										$sendRes = traineeSendMailAlertForAbsence($user, $agsession, $trainee, $sessionStagiaire, $calendrier, $sessionstagiaireheures, $errorsMsg);

										if ($sendRes > 0) {
											$context->setEventMessages($langs->trans('AgfNbEmailSended', $sendRes));
										} elseif ($sendRes < 0) {
											$context->setEventMessages($langs->trans('AgfEmailSendError') . $sendRes, 'errors');
										} else {
											$context->setEventMessages($langs->trans('AgfNoEmailSended'), 'warnings');
										}

										if (!empty($errorsMsg) and is_array($errorsMsg)) {
											$context->setEventMessages($errorsMsg, 'errors');
										}

										$redirect = $context->getRootUrl('agefodd_trainee_session_card') . '&sessid=' . $agsession->id . '&slotid=' . $slotid . '&save_lastsearch_values=1';
										header('Location: ' . $redirect);
										exit;

									} else {
										$context->setEventMessages($langs->trans('AgfSetPlannedAbsenceError'), 'errors');
									}
								} else {
									$context->setEventMessages($langs->trans('AgfSetPlannedAbsenceErrorNotAllowed', 'errors'));
								}
							} else {
								$context->setEventMessages($langs->trans('AgfSessionCreneauNotFound'), 'errors');
							}

						} else {
							$context->setEventMessages($langs->trans('AgfContactNotInSession'), 'errors');
						}
					} else {
						$context->setEventMessages($langs->trans('AgfTraineeNotExistOrUserNoTrainee'), 'errors');
					}
				} else {
					$context->setEventMessages($langs->trans('AgfSessionNotExist'), 'errors');
				}
			}

			return 1;
		}

		return 0;
	}

	/**
	 * Overloading the interface function : replacing the parent's function with the one below
	 * For external Access module
	 * @param array         $parameters     Hook metadatas (context, etc...)
	 * @param CommonObject    $object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string          $action Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActionInterface($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user;

		if (in_array('externalaccessinterface', explode(':', $parameters['context']))) {
			dol_include_once('/agefodd/lib/agf_externalaccess.lib.php');
			dol_include_once('/agefodd/class/agefodd_formateur.class.php');

			if ($action == "downloadSessionFile") {
				$file = GETPOST('file', 'none');
				$filename = $conf->agefodd->dir_output . '/' . $file;

				$this->_downloadSessionFile($filename);
			}

			if ($action == "downloadSessionAttachement") {
				require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
				$ecmfile = new ECMFiles($this->db);
				$result=$ecmfile->fetch(0, '', '', '', GETPOST('hashp','alpha'));
				if ($result > 0) {
					if (!empty($ecmfile->share)) {
						$filename = $conf->agefodd->dir_output . '/' . str_replace('agefodd/','', $ecmfile->filepath).'/'.$ecmfile->filename;
						$this->_downloadSessionFile($filename);
					}
				}
			}

			if ($action == "getSessionAgenda") {
				// Parse the start/end parameters.
				// These are assumed to be ISO8601 strings with no time nor timeZone, like "2013-12-29".
				// Since no timeZone will be present, they will parsed as UTC.

				$timeZone = GETPOST('timeZone', 'none');
				$agendaType = GETPOST('agendaType', 'none');
				$range_start = parseFullCalendarDateTime(GETPOST('start', 'none'), $timeZone);
				$range_end = parseFullCalendarDateTime(GETPOST('end', 'none'), $timeZone);

				$teacher = new Agefodd_teacher($this->db);
				$teacher->fetchByUser($user);

				if ($agendaType == 'session' && $teacher->id > 0) {
					print getAgefoddJsonAgendaFormateur($teacher->id, $range_start->getTimestamp(), $range_end->getTimestamp());
				} elseif ($agendaType == 'notAvailableRange' && $teacher->id > 0) {
					print getAgefoddJsonAgendaFormateurNotAvailable($teacher->id, $range_start->getTimestamp(), $range_end->getTimestamp());
				} else {
					print json_encode(array());
				}


				exit;
			} elseif ($action === 'downloadAgefoddTrainneeDoc') {
				downloadAgefoddTrainneeDoc();
			}

		}

		return 0;
	}


	/**
	 * Mes nouvelles pages pour l'accés au portail externe
	 * For external Access module
	 * @param array $parameters
	 * @param object $context
	 * @param string $action
	 * @param HookManager $hookmanager
	 * @return int
	 */
	public function PrintPageView($parameters, &$context, &$action, $hookmanager)
	{
		global $langs, $user, $conf;

		$TContext = explode(':', $parameters['context']);

		if (in_array('externalaccesspage', $TContext) && !empty($conf->global->AGF_EACCESS_ACTIVATE)) {

			$sessid = GETPOST('sessid', 'int');

			dol_include_once('/agefodd/lib/agf_externalaccess.lib.php');
			dol_include_once('/agefodd/class/agsession.class.php');
			dol_include_once('/agefodd/class/agefodd_formateur.class.php');
			dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
			dol_include_once('/agefodd/class/agefodd_session_formateur_calendrier.class.php');

			$langs->loadLangs(array('agefodd@agefodd', 'agfexternalaccess@agefodd'));

			if ($context->controller == 'agefodd') {
				$context->setControllerFound();
				print getMenuAgefoddExternalAccess();
			} elseif ($context->controller == 'agefodd_session_list') {
				// Trainer sessions list
				$context->setControllerFound();
				print getPageViewSessionListExternalAccess();
			} elseif ($context->controller == 'agefodd_session_card' && GETPOST('sessid', 'int') > 0) {

				// CLOSE IFRAME
				if ($context->iframe) {
					$fromAction = GETPOST('fromAction', 'none');
					if (!empty($fromAction) && $fromAction == 'deleteCalendrierFormateur') {
						print '<script >window.parent.closeModal();</script>';
					}
				}


				$agsession = new Agsession($this->db);
				if ($agsession->fetch(GETPOST('sessid', 'none')) > 0) // Vérification que la session existe
				{
					$trainer = $agsession->getTrainerFromUser($user);
					if ($trainer) {
						$context->setControllerFound();
						print getPageViewSessionCardExternalAccess($agsession, $trainer);
					}
				}

			} elseif ($context->controller == 'agefodd_trainee_session_list') {
				// Trainee sessions list
				$context->setControllerFound();
				print getPageViewTraineeSessionListExternalAccess();
			} elseif ($context->controller == 'agefodd_trainee_session_card' && GETPOST('sessid', 'int') > 0) {
				print getPageViewTraineeSessionCardExternalAccess();
			} elseif ($context->controller == 'agefodd_session_card_time_slot' && $sessid > 0) {
				$agsession = new Agsession($this->db);
				if ($agsession->fetch(GETPOST('sessid', 'none')) > 0) // Vérification que la session existe
				{
					$trainer = $agsession->getTrainerFromUser($user); // Est ce que mon user (formateur) est bien associé à la session ?
					if ($trainer) {
						$ok = true;
						$agf_calendrier_formateur = new Agefoddsessionformateurcalendrier($this->db);
						$soltid = GETPOST('slotid', 'int'); // Si vide, alors mode create
						if ($soltid > 0) {
							$agf_calendrier_formateur->fetch(GETPOST('slotid', 'none'));
							// Est ce que mon calendrier appartient bien à ma session
							if ($agf_calendrier_formateur->sessid != $agsession->id) $ok = false; // Tantative d'édition avec un calendrier qui n'appartient pas au formateur
						}

						// $ok = true par défaut pour du create, mais si j'ai un $soltid, alors j'ai vérifié que l'utilisateur a le droit
						if ($ok) {
							dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');

							$TCalendrier = _getCalendrierFromCalendrierFormateur($agf_calendrier_formateur, true, true);
							if (is_string($TCalendrier)) {
								$context->setError($langs->trans('Agf_EA_error_sql'));
								$TCalendrier = array();
							}
							if (!empty($TCalendrier)) $agf_calendrier = $TCalendrier[0];
							else $agf_calendrier = null;

							$context->setControllerFound();
							print getPageViewSessionCardCalendrierFormateurExternalAccess($agsession, $trainer, $agf_calendrier_formateur, $agf_calendrier, $action);
						}
					}
				}
			} elseif ($context->controller == 'agefodd_session_card_time_slot' && empty($sessid)) {
				print getPageViewSessionCardCalendrierFormateurAddFullCalendarEventExternalAccess($action);
				$context->setControllerFound();
			} elseif ($context->controller == 'agefodd_trainer_agenda') {
				print getPageViewAgendaFormateurExternalAccess();
				$context->setControllerFound();
			} elseif ($context->controller == 'agefodd_event_other') {
				print getPageViewAgendaOtherExternalAccess();
				$context->setControllerFound();
			}
		}
		return 0;
	}

	/**
	 * For external Access module
	 * @param array $parameters
	 * @param object $object
	 * @param string $action
	 * @param HookManager $hookmanager
	 * @return int
	 */
	public function PrintTopMenu($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf, $user;

		if (empty($conf->global->AGF_EACCESS_ACTIVATE)) return 0;

		$context = Context::getInstance();

		$this->results['agefodd'] = array(
			'id' => 'agefodd',
			'rank' => 90,
			'url' => $context->getRootUrl('agefodd'),
			'name' => $langs->trans('AgfTraining')
		);

		$this->results['agefodd']['children']['global'] = array(
			'id' => 'agefodd',
			'rank' => 10,
			'url' => $context->getRootUrl('agefodd'),
			'name' => $langs->trans('AgfTraining')
		);

		if ($user->rights->agefodd->external_trainer_read) {
			$this->results['agefodd']['children']['agefodd_session_list'] = array(
				'id' => 'agefodd',
				'rank' => 20,
				'url' => $context->getRootUrl('agefodd_session_list'),
				'name' => $langs->trans('AgfMenuSess')
			);
		}

		if ($user->rights->agefodd->external_trainer_agenda) {
			$this->results['agefodd']['children']['agefodd_trainer_agenda'] = array(
				'id' => 'agefodd',
				'rank' => 30,
				'url' => $context->getRootUrl('agefodd_trainer_agenda'),
				'name' => $langs->trans('AgfMenuAgendaFormateur')
			);
		}

		if ($user->rights->agefodd->external_trainee_read) {
			$this->results['agefodd']['children']['agefodd_trainee_session_list'] = array(
				'id' => 'agefodd',
				'rank' => 30,
				'url' => $context->getRootUrl('agefodd_trainee_session_list'),
				'name' => $langs->trans('AgfMenuSessTrainee')
			);
		}

		if (is_object($hookmanager))
		{
			$params = array (
				'menuList' => $this->results
			);
			$reshook = $hookmanager->executeHooks('addExternalTopMenu', $params);

			if (!empty($reshook)){
				// override full output
				$this->results = $hookmanager->resArray;
			}
			else{
				$this->results+= $hookmanager->resArray;
			}
		}

		return 0;
	}

	// For external Access module
	public function PrintServices($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf;

		$TContext = explode(':', $parameters['context']);

		if (in_array('externalaccesspage', $TContext) && !empty($conf->global->AGF_EACCESS_ACTIVATE)) {
			$langs->load('agefodd@agefodd');
			$context = Context::getInstance();

			$link = $context->getRootUrl('agefodd');
			$this->resprints .= getService($langs->trans('AgfTraining'), 'fa-graduation-cap', $link); // desc : $langs->trans('InvoicesDesc')

			$this->results[] = 1;
			return 0;
		}

		return 0;
	}

	/**
	 * elementList Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object $object Object to use hooks on
	 * @param string $action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param HookManager $hookmanager class instance
	 * @return int
	 */
	public function emailElementlist($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;
		$langs->load('agefodd@agefodd');

		$this->results['fiche_pedago'] = $langs->trans('AgfMailToSendFichePedago');
		$this->results['fiche_presence'] = $langs->trans('AgfMailToSendFichePresence');
		$this->results['mission_trainer'] = $langs->trans('AgfMailToSendMissionTrainer');
		$this->results['trainer_doc'] = $langs->trans('AgfMailToSendMissionTrainerDoc');
		$this->results['fiche_presence_direct'] = $langs->trans('AgfMailToSendFichePresenceDirect');
		$this->results['fiche_presence_empty'] = $langs->trans('AgfMailToSendFichePresenceEmpty');
		$this->results['convention'] = $langs->trans('AgfMailToSendConvention');
		$this->results['attestation'] = $langs->trans('AgfMailToSendAttestation');
		$this->results['cloture'] = $langs->trans('AgfMailToSendCloture');
		$this->results['conseils'] = $langs->trans('AgfMailToSendConseil');
		$this->results['convocation'] = $langs->trans('AgfMailToSendConvocation');
		$this->results['courrier-accueil'] = $langs->trans('AgfMailToSendCourrierAcceuil');
		$this->results['attestationendtraining'] = $langs->trans('AgfMailToSendAttestationEndTraining');
		$this->results['attestation_trainee'] = $langs->trans('AgfMailToSendAttestationParticipants');
		$this->results['convocation_trainee'] = $langs->trans('AgfMailToSendConventionParticipants');
		$this->results['attestationendtraining_trainee'] = $langs->trans('AgfMailToSendAttestationEndTrainingParticipants');
		$this->results['agf_trainee'] = $langs->trans('AgfMailToSendTrainee');
		$this->results['agf_trainer'] = $langs->trans('AgfMailToSendTrainer');
		$this->results['cron_session'] = $langs->trans('AgfMailToSendCronSession');
		$this->results['attestationpresencetraining'] = $langs->trans('AgfMailToSendAttestationPresence');

		return 0;
	}

	/**
	 * @param array $parameters
	 * @param Object $object
	 * @param string $action
	 * @param HookManager $hookmanager
	 * @return number
	 */
	function formBuilddocOptions($parameters, &$object, $action, $hookmanager)
	{
		global $conf, $langs, $bc, $var;

		if (in_array('propalcard', explode(':', $parameters['context']))) {

			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			dol_include_once('/agefodd/class/agsession.class.php');
			$agfsess = new Agefodd_session_element($object->db);
			$result = $agfsess->fetch_element_by_id($object->id, 'propal');

			$out = '';

			if ($result > 0) {
				if (is_array($agfsess->lines) && count($agfsess->lines) > 0) {
					$langs->load('agefodd@agefodd');
					foreach ($agfsess->lines as $key => $session) {

						$sessiondetail = new Agsession($object->db);
						$sessiondetail->fetch($session->fk_session_agefodd);

						if (is_file($conf->agefodd->dir_output . '/' . 'fiche_pedago_' . $sessiondetail->formid . '.pdf')) {
							$out .= '<tr ' . $bc[$var] . '>
			     			<td colspan="4" style="text-align: right">
			     				<label for="hideInnerLines">' . $langs->trans('AgfAddTrainingProgram', $session->fk_session_agefodd) . '</label>
			     				<input type="checkbox" id="progsession_' . $session->fk_session_agefodd . '" name="progsession[]" value="' . $sessiondetail->formid . '" />
			     			</td>
			     			</tr>';

							$var = -$var;
						} else {
							$out .= '<tr ' . $bc[$var] . '>
			     			<td colspan="4" style="text-align: right">
			     				<label for="hideInnerLines">' . $langs->trans('AgfAddTrainingProgramNotExists', $session->fk_session_agefodd) . '</label>
			     				<input type="checkbox" id="progsession_' . $session->fk_session_agefodd . '" name="progsession[]" value="' . $sessiondetail->formid . '" disabled="disabled" />
			     			</td>
			     			</tr>';
							$var = -$var;
						}

						if (is_file($conf->agefodd->dir_output . '/' . 'fiche_pedago_modules_' . $sessiondetail->formid . '.pdf')) {

							$out .= '<tr ' . $bc[$var] . '>
			     			<td colspan="4" style="text-align: right">
			     				<label for="hideInnerLines">' . $langs->trans('AgfAddTrainingProgramMod', $session->fk_session_agefodd) . '</label>
			     				<input type="checkbox" id="progsession_' . $session->fk_session_agefodd . '" name="progsessionmod[]" value="' . $sessiondetail->formid . '" />
			     			</td>
			     			</tr>';

							$var = -$var;
						} else {
							$out .= '<tr ' . $bc[$var] . '>
			     			<td colspan="4" style="text-align: right">
			     				<label for="hideInnerLines">' . $langs->trans('AgfAddTrainingProgramModNotExists', $session->fk_session_agefodd) . '</label>
			     				<input type="checkbox" id="progsession_' . $session->fk_session_agefodd . '" name="progsession[]" value="' . $sessiondetail->formid . '" disabled="disabled" />
			     			</td>
			     			</tr>';
							$var = -$var;
						}
					}
				}
			} else {
				dol_syslog(get_class($this) . '::' . __METHOD__ . ' ERR Agefodd_session_element: ' . $agfsess->error);
			}

			$this->resprints = $out;
		}

		return 1;
	}

	/**
	 * Execute action
	 *
	 * @param array $parameters Array of parameters
	 * @param Object $pdfhandler PDF builder handler
	 * @param string $action 'add', 'update', 'view'
	 * @param HookManager $hookmanager
	 * @return int <0 if KO,
	 *         =0 if OK but we want to process standard actions too,
	 *         >0 if OK and we want to replace standard actions.
	 */
	function afterPDFCreation($parameters, &$pdfhandler, &$action, $hookmanager)
	{
		global $conf;

		$outputlangs = $parameters['outputlangs'];

		$files = array();
		dol_syslog(get_class($this) . '::executeHooks action=' . $action);

		$object = $parameters['object'];

		if ($object->table_element == 'propal') {

			$pdf = pdf_getInstance();
			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}
			$pdf->SetFont(pdf_getPDFFont($outputlangs));

			if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION)
				$pdf->SetCompression(false);

			$mergeprogram = GETPOST('progsession', 'array');
			$mergeprogrammod = GETPOST('progsessionmod', 'array');

			if (is_array($mergeprogram) && count($mergeprogram) > 0) {
				dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
				$agf = new Formation($this->db);

				foreach ($mergeprogram as $training_id) {
					$agf->fetch($training_id);
					$agf->generatePDAByLink();
					$file = $conf->agefodd->dir_output . '/' . 'fiche_pedago_' . $training_id . '.pdf';
					if (is_file($file) && is_readable($file)) {
						$files[] = $file;
					}
				}
			}

			if (is_array($mergeprogrammod) && count($mergeprogrammod) > 0) {
				foreach ($mergeprogrammod as $training_id) {
					$file = $conf->agefodd->dir_output . '/' . 'fiche_pedago_modules_' . $training_id . '.pdf';
					if (is_file($file) && is_readable($file)) {
						$files[] = $file;
					}
				}
			}
			if (count($files) > 0) {
				array_unshift($files, $parameters['file']);
				$pagecount = $this->concat($pdf, $files);
				if ($pagecount) {
					$pdf->Output($parameters['file'], 'F');
					if (!empty($conf->global->MAIN_UMASK)) {
						@chmod($parameters['file'], octdec($conf->global->MAIN_UMASK));
					}
				}
			}
		}
		return 0;
	}

	/**
	 *
	 * @param TCPDF $pdf
	 * @param array $files
	 * @return int
	 */
	function concat(&$pdf, $files)
	{
		$pagecount = 0;

		foreach ($files as $file) {
			$pagecount += $pdf->setSourceFile($file);
			for ($i = 1; $i <= $pagecount; $i++) {
				$tplidx = $pdf->ImportPage($i);
				$s = $pdf->getTemplatesize($tplidx);
				$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
				$pdf->useTemplate($tplidx);
			}
		}

		return $pagecount;
	}

	/**
	 *
	 * @param array $parameters
	 * @param Object $object
	 * @param string $action
	 * @param HookManager $hookmanager
	 * @return int
	 */
	public function doUpgrade2($parameters, &$object, &$action, $hookmanager)
	{
		// TODO : see why Dolibarr do not execute this
		/*dol_include_once('/agefodd/core/modAgefodd.class.php');
		 $obj = new modAgefodd($db);
		 $obj->load_tables();*/

		return 0;
	}

	public function pdf_getLinkedObjects($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;

		if (empty($conf->global->AGF_PRINT_TRAINING_REF_AND_SESS_ID_ON_PDF) && empty($conf->global->AGF_PRINT_TRAINING_LABEL_REF_INTERNE_AND_SESS_ID_DATES))
			return 0;

		$TContext = explode(':', $parameters['context']);
		$intersec = array_intersect(array(
			'propalcard',
			'ordercard',
			'invoicecard'
		), $TContext);

		if (!empty($intersec)) {
			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			dol_include_once('/agefodd/class/agsession.class.php');
			dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');

			// $linkedobjects = $parameters['linkedobjects'];

			$outputlangs = $parameters['outputlangs'];
			$outputlangs->load('agefodd@agefodd');

			$element_type = $object->element;
			if ($element_type == 'commande')
				$element_type = 'order';
			elseif ($element_type == 'facture')
				$element_type = 'invoice';

			$agfsess = new Agefodd_session_element($object->db);
			$result = $agfsess->fetch_element_by_id($object->id, $element_type);
			if ($result >= 0) {

				// Keep old objects linked
				$this->results = $parameters['linkedobjects'];

				foreach ($agfsess->lines as $key => $session) {
					$sessiondetail = new Agsession($object->db);
					$result = $sessiondetail->fetch($session->fk_session_agefodd);
					if ($result > 0) {
						if (!empty($conf->global->AGF_PRINT_TRAINING_REF_AND_SESS_ID_ON_PDF)) {
							$ref_value = '';
							if (!empty($conf->global->AGF_HIDE_TRAININGREF_ON_PDF)) {
								$ref_value = $outputlangs->convToOutputCharset($sessiondetail->formref);
							}
							if (!empty($sessiondetail->formrefint)) {
								if (!empty($ref_value)) {
									$ref_value .= '/';
								}
								$ref_value .= $outputlangs->convToOutputCharset($sessiondetail->formrefint);
							}
							$ref_value .= ' '.$sessiondetail->id.'#'.$sessiondetail->ref.' ';
							$this->results[get_class($sessiondetail) . $sessiondetail->id . '_1'] = array(
								'ref_title' => $outputlangs->transnoentities("AgefoddRefFormationSessionId"),
								'ref_value' => $ref_value,
								'date_value' => ''
							);
						}

						if (!empty($conf->global->AGF_PRINT_TRAINING_LABEL_REF_INTERNE_AND_SESS_ID_DATES)) {
							$formation = new Formation($object->db);
							if ($formation->fetch($sessiondetail->fk_formation_catalogue) > 0) {
								$this->results[get_class($formation) . $formation->id] = array(
									'ref_title' => $outputlangs->transnoentities("AgefoddTitleAndCodeInt"),
									'ref_value' => $formation->intitule . ' / ' . (!empty($formation->ref_interne) ? $formation->ref_interne : '-'),
									'date_value' => ''
								);
							}

							$date_d = dol_print_date($sessiondetail->dated, '%d/%m/%Y');
							$date_f = dol_print_date($sessiondetail->datef, '%d/%m/%Y');
							$this->results[get_class($sessiondetail) . $sessiondetail->id . '_2'] = array(
								'ref_title' => $outputlangs->transnoentities("AgefoddSessIdAndDates"),
								'ref_value' => $sessiondetail->id .'#'. $sessiondetail->ref. ' / ' . $date_d . ' - ' . $date_f,
								'date_value' => ''
							);
						}

					} else {
						dol_print_error('', $agfsess->error);
					}
				}
			} else {
				dol_print_error('', $agfsess->error);
			}
		}

		return 0;
	}


	function printSearchForm($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $conf;

		$TContext = explode(':', $parameters['context']);
		if (!empty($user->rights->agefodd->lire) && !empty($conf->fullcalendarscheduler->enabled) && in_array('agefodd_session_scheduler', $TContext)) {
			// Add my mini calendar
			$this->resprints = '<div id="agf_session_scheduler_mini"></div>';
		}

		return 0;
	}

	// For external Access module
	function _downloadSessionFile($filename)
	{
		dol_include_once('/externalaccess/lib/externalaccess.lib.php');
		$forceDownload = GETPOST('forcedownload', 'int');

		downloadFile($filename, $forceDownload);
	}

	/**
	 * @param array $parameters
	 * @param object $object
	 * @param string $action
	 * @param HookManager $hookmanager
	 * @return int
	 */
	function printFieldListFrom($parameters, &$object, &$action, HookManager $hookmanager)
	{
		$TContext = explode(':', $parameters['context']);
		if (in_array('agendaexport', $TContext)) {
			$sql = '';
			$agftraineeid = GETPOST('agftraineeid', "int");
			$agftrainerid = GETPOST('agftrainerid', "int");

			if (!empty($agftraineeid)) {
				// agenda pour le stagiaire
				$sql .= ' JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_calendrier agf_sc ON (a.id = agf_sc.fk_actioncomm) ';
				$sql .= ' JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire agf_ss ON (agf_ss.fk_session_agefodd = agf_sc.fk_agefodd_session) ';
			} elseif (!empty($agftrainerid)) {
				// agenda pour le formateur
				$sql .= ' JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_formateur_calendrier agf_sfc ON (a.id = agf_sfc.fk_actioncomm) ';
				$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_formateur agf_sf ON (agf_sf.rowid = agf_sfc.fk_agefodd_session_formateur)';
			}

			$this->resprints = $sql;
			return 1;
		}

		return 0;
	}

	function addMoreEventsExport($parameters, &$object, &$action, HookManager $hookmanager)
	{
		global $db, $conf;

		$agftrainerid = GETPOST('agftrainerid', "int");
		if (!empty($agftrainerid) && empty($conf->global->AGF_DONT_ADD_TRAINER_INDISPO_IN_ICS))
		{
			$eventarray = &$parameters['eventarray'];
			$sql = "SELECT a.id,";
			$sql.= " a.datep,";		// Start
			$sql.= " a.datep2,";	// End
			$sql.= " a.durationp,";			// deprecated
			$sql.= " a.datec, a.tms as datem,";
			$sql.= " a.label, a.code, a.note, a.fk_action as type_id,";
			$sql.= " a.fk_soc,";
			$sql.= " a.fk_user_author, a.fk_user_mod,";
			$sql.= " a.fk_user_action,";
			$sql.= " a.fk_contact, a.percent as percentage,";
			$sql.= " a.fk_element, a.elementtype,";
			$sql.= " a.priority, a.fulldayevent, a.location, a.punctual, a.transparency,";
			$sql.= " c.id as type_id, c.code as type_code, c.libelle";
			$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON c.id = a.fk_action";

			$sql.= " WHERE a.entity IN (".getEntity('agenda').")";
			if (array_key_exists('notolderthan', $parameters['filters']) && $parameters['filters']['notolderthan'] != '') $sql.=" AND a.datep >= '".$this->db->idate(dol_now()-($parameters['filters']['notolderthan']*24*60*60))."'";
			$sql.= " AND a.code = 'AC_AGF_NOTAV'";
			$sql.= ' AND a.fk_element = '.intval($agftrainerid);
			$sql.= " AND a.elementtype = 'agefodd_formateur' ";

			$sql.= " ORDER BY a.datep";

			$resql = $db->query($sql);
			if ($resql)
			{
				if ($db->num_rows($resql))
				{
					$diff = 0;
					while ($obj=$this->db->fetch_object($resql))
					{
						global $dolibarr_main_url_root;

						$qualified=true;

						// 'eid','startdate','duration','enddate','title','summary','category','email','url','desc','author'
						$event=array();
						$event['uid']='dolibarragenda-'.$this->db->database_name.'-'.$obj->id."@".$_SERVER["SERVER_NAME"];
						$event['type']='event';
						$datestart=$this->db->jdate($obj->datep)-(empty($conf->global->AGENDA_EXPORT_FIX_TZ)?0:($conf->global->AGENDA_EXPORT_FIX_TZ*3600));
						$dateend=$this->db->jdate($obj->datep2)-(empty($conf->global->AGENDA_EXPORT_FIX_TZ)?0:($conf->global->AGENDA_EXPORT_FIX_TZ*3600));
						$duration=($datestart && $dateend)?($dateend - $datestart):0;
						$event['summary']=$obj->label.($obj->socname?" (".$obj->socname.")":"");
						$event['desc']=$obj->note;
						$event['startdate']=$datestart;
						$event['enddate']=$dateend;		// Not required with type 'journal'
						$event['duration']=$duration;	// Not required with type 'journal'
						$event['author']=dolGetFirstLastname($obj->firstname, $obj->lastname);
						$event['priority']=$obj->priority;
						$event['fulldayevent']=$obj->fulldayevent;
						$event['location']=$obj->location;
						$event['transparency']=(($obj->transparency > 0)?'OPAQUE':'TRANSPARENT');		// OPAQUE (busy) or TRANSPARENT (not busy)
						$event['punctual']=$obj->punctual;
						$event['category']=$obj->libelle;	// libelle type action
						// Define $urlwithroot
						$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
						$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;			// This is to use external domain name found into config file
						//$urlwithroot=DOL_MAIN_URL_ROOT;						// This is to use same domain name than current
						$url=$urlwithroot.'/comm/action/card.php?id='.$obj->id;
						$event['url']=$url;
						$event['created']=$this->db->jdate($obj->datec)-(empty($conf->global->AGENDA_EXPORT_FIX_TZ)?0:($conf->global->AGENDA_EXPORT_FIX_TZ*3600));
						$event['modified']=$this->db->jdate($obj->datem)-(empty($conf->global->AGENDA_EXPORT_FIX_TZ)?0:($conf->global->AGENDA_EXPORT_FIX_TZ*3600));

						if ($qualified && $datestart)
						{
							$eventarray[]=$event;
						}
						$diff++;
					}
				}
			}

			$this->results = $eventarray;
			return 0;
		}

		return 0;
	}

	/**
	 * @param array $parameters
	 * @param object $object
	 * @param string $action
	 * @param HookManager $hookmanager
	 * @return int
	 */
	function printFieldListWhere($parameters, &$object, &$action, HookManager $hookmanager)
	{
		$TContext = explode(':', $parameters['context']);
		if (in_array('agendaexport', $TContext)) {
			$sql = '';
			$agftraineeid = GETPOST('agftraineeid', "int");
			$agftrainerid = GETPOST('agftrainerid', "int");
			if (!empty($agftraineeid)) {
				$sql .= ' AND agf_ss.fk_stagiaire = ' . intval($agftraineeid);
			} elseif (!empty($agftrainerid)) {
				$sql .= ' AND agf_sf.fk_agefodd_formateur = ' . intval($agftrainerid);
			}

			$this->resprints = $sql;
			return 1;
		}

		if (in_array('agendalist', $TContext)) {
			$sql = '';
			$actioncode = GETPOST('search_actioncode', "alpha");
			if ($actioncode == 'AC_NON_AUTO') {
				$sql .= " AND c.code NOT IN ('AC_AGF_CONVO', 'AC_AGF_SESST', 'AC_AGF_SESS') ";
			}

			$this->resprints = $sql;
			return 1;
		}

		return 0;
	}

	/**
	 * @param array $parameters
	 * @param object $object
	 * @param string $action
	 * @param HookManager $hookmanager
	 * @return int
	 */
	public function overrideUploadOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf;

		$TContext = explode(':', $parameters['context']);
		if (in_array('fileupload', $TContext)) {
			if ($parameters['element'] == 'agefodd_agsession') {

				$parameters['options']['upload_dir'] = $conf->agefodd->dir_output . "/" . $object->id . "/";
				return 0;
			}
		}

		return 0;
	}

	function updateFullcalendarEvents($parameters, &$object, &$action, HookManager $hookmanager)
	{
		$TContexts = explode(':', $parameters['context']);

		if (in_array('agenda', $TContexts)) {
			global $langs;

			$langs->load('agefodd@agefodd');

			dol_include_once('/agefodd/class/agsession.class.php');
			dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
			dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');

			foreach ($object as &$event) {
				if ($event['object']->code != 'AC_AGF_SESS' && $event['object']->elementtype != 'agefodd_session') {
					continue;
				}

				$session = new Agsession($event['object']->db);
				$session->fetch($event['object']->elementid);

				if ($session->id <= 0) {
					continue;
				}

				$formateurs = new Agefodd_session_formateur($session->db);
				$nbform = $formateurs->fetch_formateur_per_session($session->id);

				if ($nbform > 0) {
					$event['title'] .= "\n\n" . $nbform . ' ' . $langs->trans('AgfTrainingTrainer');

					if ($nbform == 1) {
						$event['title'] .= ' : ' . strtoupper($formateurs->lines[0]->lastname) . ' ' . ucfirst($formateurs->lines[0]->firstname);
					} else {
						$event['note'] .= '<br /><br />' . $langs->trans('AgfFormateur') . ' :';

						for ($i = 0; $i < $nbform; $i++) {
							$event['note'] .= '<br /><a href="' . dol_buildpath('/agefodd/trainer/card.php', 1) . '?id=' . $formateurs->lines[$i]->formid . '">';
							$event['note'] .= img_object($langs->trans("ShowContact"), "contact") . ' ';
							$event['note'] .= strtoupper($formateurs->lines[$i]->lastname) . ' ' . ucfirst($formateurs->lines[$i]->firstname) . '</a>';
						}
					}
				}


				$stagiaires = new Agefodd_session_stagiaire($session->db);
				$resulttrainee = $stagiaires->fetch_stagiaire_per_session($session->id);


				$nbstag = count($stagiaires->lines);

				if ($nbstag > 0) {
					if ($nbstag == 1) {
						$event['title'] .= "\n\n" . $nbstag . ' ' . $langs->trans('AgfParticipant');
						$event['title'] .= ' : ' . strtoupper($stagiaires->lines[0]->nom) . ' ' . ucfirst($stagiaires->lines[0]->prenom);
					} else {
						$event['title'] .= "\n\n" . $nbstag . ' ' . $langs->trans('AgfParticipants');

						$event['note'] .= '<br /><br />' . $langs->trans('AgfParticipants') . ' :';

						for ($i = 0; $i < $nbstag; $i++) {
							$event['note'] .= '<br /><a href="' . dol_buildpath('/agefodd/trainee/card.php', 1) . '?id=' . $stagiaires->lines[$i]->id . '">';
							$event['note'] .= img_object($langs->trans("ShowContact"), "contact") . ' ';
							$event['note'] .= strtoupper($stagiaires->lines[$i]->nom) . ' ' . ucfirst($stagiaires->lines[$i]->prenom) . '</a>';
						}
					}

				}
			}
		}

		return 0;
	}

	/**
	 * @param Agsession $agsession
	 * @param Agefodd_sesscalendar $agf_calendrier
	 * @param Agefodd_session_stagiaire $stagiaires
	 * @param string $old_status
	 * @param array $errorsMsg
	 * @return int
	 */
	function sendCreneauEmailAlertToTrainees($agsession, $agf_calendrier, $stagiaires, $old_status, &$errorsMsg = array())
	{
		global $conf, $langs, $user;

		$nbMailSend = 0;
		$error = 0;

		// Check conf of module
		if (empty($conf->global->AGF_SEND_CREATE_CRENEAU_TO_TRAINEE_MAILMODEL) || empty($conf->global->AGF_SEND_SAVE_CRENEAU_TO_TRAINEE_MAILMODEL)) {
			$errorsMsg[] = $langs->trans('TemplateMailNotExist');
			return -1;
		}

		$fk_mailModel_create = $conf->global->AGF_SEND_CREATE_CRENEAU_TO_TRAINEE_MAILMODEL;
		$fk_mailModel_save = $conf->global->AGF_SEND_SAVE_CRENEAU_TO_TRAINEE_MAILMODEL;

		require_once(DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');

		$formateur = new Agefodd_teacher($this->db);
		$formateur->fetchByUser($user);

		// copy email to
		$addr_cc = "";
		if (!empty($conf->global->AGF_SEND_COPY_EMAIL_TO_TRAINER)) {
			if (!empty($formateur->id)) {
				$addr_cc = $formateur->email;
			}
		}

		foreach ($stagiaires->lines as &$stagiaire) {
			if ($stagiaire->id <= 0) {
				$errorsMsg[] = $langs->trans('AgfWarningStagiaireNoId');
				continue;
			}

			$agfssh = new Agefoddsessionstagiaireheures($this->db);
			$result = $agfssh->fetch_by_session($agsession->id, $stagiaire->id, $agf_calendrier->id);
			if ($result < 0) {
				$errorsMsg[] = $langs->trans('AgfErrorFetchingAgefoddsessionstagiaireheures');
				$error++;
			} else {

				// select mail template
				$fk_mailModel = $fk_mailModel_create;
				if (!empty($agfssh->mail_sended)) {
					$fk_mailModel = $fk_mailModel_save;
				}

				$mailTpl = agf_getMailTemplate($fk_mailModel);
				if ($mailTpl < 1) {
					$errorsMsg[] = $langs->trans('AgfEMailTemplateNotExist');
					return -2;
				}


				// PREPARE EMAIL

				if (!isset($arrayoffamiliestoexclude)) $arrayoffamiliestoexclude = null;

				// Make substitution in email content
				$substitutionarray = getCommonSubstitutionArray($langs, 0, $arrayoffamiliestoexclude, $agsession);

				complete_substitutions_array($substitutionarray, $langs, $agsession);

				$thisSubstitutionarray = $substitutionarray;

				$thisSubstitutionarray['__agfsendall_nom__'] = $stagiaire->nom;
				$thisSubstitutionarray['__agfsendall_prenom__'] = $stagiaire->prenom;
				$thisSubstitutionarray['__agfsendall_civilite__'] = $stagiaire->civilite;
				$thisSubstitutionarray['__agfsendall_socname__'] = $stagiaire->socname;
				$thisSubstitutionarray['__agfsendall_email__'] = $stagiaire->email;


				$thisSubstitutionarray['__agfcreneau_heured__'] = date('H:i', $agf_calendrier->heured);
				$thisSubstitutionarray['__agfcreneau_heuref__'] = date('H:i', $agf_calendrier->heuref);
				$thisSubstitutionarray['__agfcreneau_datesession__'] = dol_print_date($agf_calendrier->date_session);
				$thisSubstitutionarray['__agfcreneau_status__'] = $agf_calendrier->getLibStatut();

				// Add ICS link replacement to mails
				$downloadIcsLink = dol_buildpath('public/agenda/agendaexport.php', 2).'?format=ical&type=event';
				$thisSubstitutionarray['__AGENDAICS__'] = $downloadIcsLink.'&amp;agftraineeid='.$stagiaire->id;
				$thisSubstitutionarray['__AGENDAICS__'].= '&exportkey='.md5($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY.'agftraineeid'.$stagiaire->id);

				// Tableau des substitutions
				if (!empty($agsession->intitule_custo)) {
					$thisSubstitutionarray['__FORMINTITULE__'] = $agsession->intitule_custo;
				} else {
					$thisSubstitutionarray['__FORMINTITULE__'] = $agsession->formintitule;
				}

				$date_conv = $agsession->libSessionDate('daytext');
				$thisSubstitutionarray['__FORMDATESESSION__'] = $date_conv;

				$sendTopic = make_substitutions($mailTpl->topic, $thisSubstitutionarray);
				$sendContent = make_substitutions($mailTpl->content, $thisSubstitutionarray);

				$to = $stagiaire->email;

				if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
					// is not a valid email address
					$toMsg = empty($to) ? $langs->trans('AgfMailEmpty') : $to;
					$errorsMsg[] = $langs->trans('AgfInvalidAddressEmail', $toMsg);
					$error++;
					continue;
				}


				if (!empty($conf->global->AGF_CRENEAU_FORCE_EMAIL_TO) && filter_var($conf->global->AGF_CRENEAU_FORCE_EMAIL_TO, FILTER_VALIDATE_EMAIL)) {
					$to = $conf->global->AGF_CRENEAU_FORCE_EMAIL_TO;

					if (!empty($addr_cc)) {
						$addr_cc = $conf->global->AGF_CRENEAU_FORCE_EMAIL_TO;
					}
				}


				$from = getExternalAccessSendEmailFrom($user->email);
				$replyto = $user->email;
				if (!empty($formateur->id) && !empty($formateur->email) && filter_var($formateur->email, FILTER_VALIDATE_EMAIL)) {
                                	$replyto = $formateur->email;
                        	}

				$errors_to = $conf->global->MAIN_MAIL_ERRORS_TO;

				$cMailFile = new CMailFile($sendTopic, $to, $from, $sendContent, array(), array(), array(), $addr_cc, "", 0, 1, $errors_to, '', '', '', getExternalAccessSendEmailContext(), $replyto);

				if ($cMailFile->sendfile()) {
					$nbMailSend++;
				} else {
					$errorsMsg[] = $cMailFile->error . ' : ' . $to;
					$error++;
				}
			}
		}

		return $nbMailSend;
	}

	/**
	 * @param $parameters
	 * @param $object
	 * @param $action
	 * @param $hookmanager
	 * @return int
	 */
	function addStatisticLine($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		$boxstat = '';

		// nb stagiaires inscrits aux sessions
		$sql = "SELECT SUM(s.nb_stagiaire) as nb FROM " . MAIN_DB_PREFIX . "agefodd_session s WHERE s.entity in (" . getEntity('agefodd') . ")";
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$nb = $obj->nb;
			$text = $langs->trans('AgfNbreParticipants');
			$url = dol_buildpath('agefodd/session/list.php', 2);

			$boxstat .= $this->getStatBox($url, $nb, $text);
		}

		// nb total de participants en bdd
		$sql = "SELECT COUNT(rowid) as nb FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire WHERE entity in (" . getEntity('agefodd') . ")";
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$nb = $obj->nb;
			$text = $langs->trans('AgfReportBPFNbPart');
			$url = dol_buildpath('agefodd/trainee/list.php', 2);

			$boxstat .= $this->getStatBox($url, $nb, $text);
		}

		// nb tiers ayant déjà inscrits un participant à une session
		$sql = "SELECT count(DISTINCT s.rowid) as nb";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ass";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as stag ON stag.rowid = ass.fk_stagiaire";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = stag.fk_soc";
		$sql .= " WHERE stag.entity in (" . getEntity('agefodd') . ")";
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$nb = $obj->nb;
			$text = $langs->trans('AgfNbTiersPart');
			$url = dol_buildpath('agefodd/trainee/list.php', 2);

			$boxstat .= $this->getStatBox($url, $nb, $text);
		}

		// nb session excluant les non-réalisées et prenant en compte les anciens status des session archivées
		/*
		 * Status :
		 * 1 => Envisagée
		 * 2 => Confirmée
		 * 3 => Non réalisée
		 * 4 => Archivée
		 * 5 => Réalisée
		 * 6 => En Cours
		 */
		$sql = "SELECT
					SUM(
						CASE
							WHEN s.status <> 4 THEN 1
							WHEN s.status_before_archive IN (1, 2, 5, 6) THEN 1
							ELSE 0
						END
					) AS nb";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session AS s";
		$sql .= " WHERE s.status <> 3 AND s.entity IN(" . getEntity('agefodd') . ")";
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$nb = $obj->nb;
			$text = $langs->trans('AgfNbSessEffective');
			$url = dol_buildpath('agefodd/session/list.php', 2);

			$boxstat .= $this->getStatBox($url, $nb, $text);
		}

		$this->resprints = $boxstat;

		return 0;
	}

	function getStatBox($url = '#', $nb = 0, $text = '')
	{
		$box = '';
		$box .= '<a href="' . $url . '" class="boxstatsindicator thumbstat nobold nounderline">';
		$box .= '<div class="boxstats">';
		$box .= '<span class="boxstatstext" title="' . dol_escape_htmltag($text) . '">' . img_object("", 'generic') . ' ' . $text . '</span><br>';
		$box .= '<span class="boxstatsindicator">' . ($nb ? $nb : 0) . '</span>';
		$box .= '</div>';
		$box .= '</a>';

		return $box;
	}

	function replaceThirdparty($parameters, &$object, &$action, $hookmanager)		//Modifie la société des sessions liées au tiers fusionné
	{

		global $user, $conf, $langs;

		// FIXME Gestion d'erreurs, aucun retour de crud ou de agf_pdf_create() n'est testé, aucun message d'erreur n'est envoyé si $resql* vaut false

		//SOCIETE CHARGEE DE LA SESSION
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "agefodd_session WHERE fk_soc=" . $parameters['soc_origin'] . ";";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$session = new Agsession($this->db);
				$session->fetch($obj->rowid);
				$session->fk_soc = intval($parameters['soc_dest']);
				$session->update($user);
			}
		}

		//SOCIETE DEMANDEUSE
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "agefodd_session WHERE fk_soc_requester=" . $parameters['soc_origin'] . ";";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$session = new Agsession($this->db);
				$session->fetch($obj->rowid);
				$session->fk_soc_requester = intval($parameters['soc_dest']);
				$session->update($user);
			}
		}

		//STAGIAIRES LIES AUX SESSIONS
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire WHERE fk_soc_requester=" . $parameters['soc_origin'] . ";";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$agf_stagiaire = new Agefodd_session_stagiaire($this->db);
				$agf_stagiaire->fetch($obj->rowid);
				$agf_stagiaire->fk_soc_requester = intval($parameters['soc_dest']);
				$agf_stagiaire->update($user);
			}
		}

		//TOUS LES STAGIAIRES
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire WHERE fk_soc=" . $parameters['soc_origin'] . ";";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$agf_stagiaire = new Agefodd_stagiaire($this->db);
				$agf_stagiaire->fetch($obj->rowid);
				$agf_stagiaire->socid = intval($parameters['soc_dest']);
				$agf_stagiaire->update($user);
			}
		}

		//OPCA STAGIAIRES
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "agefodd_opca WHERE fk_soc_trainee=" . $parameters['soc_origin'] . ";";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$agf_opca = new Agefodd_opca ($this->db);
				$agf_opca->fetch($obj->rowid);
				$agf_opca->fk_soc_trainee = intval($parameters['soc_dest']);
				$agf_opca->update($user);
			}
		}

		//OPCA SOCIETE
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "agefodd_opca WHERE fk_soc_OPCA=" . $parameters['soc_origin'] . ";";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$agf_opca = new Agefodd_opca ($this->db);
				$agf_opca->fetch($obj->rowid);
				$agf_opca->fk_soc_OPCA = intval($parameters['soc_dest']);
				$agf_opca->update($user);
			}
		}

		//LIEUX DE FORMATION
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "agefodd_place WHERE fk_societe=" . $parameters['soc_origin'] . ";";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$agf_place = new Agefodd_place ($this->db);
				$agf_place->fetch($obj->rowid);
				$agf_place->fk_societe = intval($parameters['soc_dest']);
				$agf_place->update($user);
			}
		}

		// DOCUMENTS LIES (par session)
		$sql = "SELECT * FROM " . MAIN_DB_PREFIX . "agefodd_session WHERE fk_soc=" . $parameters['soc_dest'] . ";";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$agf = new Agsession($this->db);
				$session = $agf->fetch($obj->rowid);

				// ELEMENTS
				$sql2 = "SELECT * FROM " . MAIN_DB_PREFIX . "agefodd_session_element WHERE fk_session_agefodd=" . $obj->rowid . ";";
				$resql2 = $this->db->query($sql2);
				if ($resql2) {
					while ($obj2 = $this->db->fetch_object($resql2)) {
						$session_element = new Agefodd_session_element($this->db);
						$session_element->fetch($obj2->rowid);
						$session_element->fk_soc = intval($parameters['soc_dest']);
						$session_element->update($user);
					}
				}

				// PDF
				$outputlangs = $langs;
				$newlang = $object->thirdparty->default_lang;
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$id_tmp = $agf->id;
				$socid = $parameters['soc_dest'];

				if (file_exists($conf->agefodd->dir_output . '/convocation_' . $agf->id . '_' . $parameters['soc_origin'] . '.pdf')) {
					$model = 'convocation';
					$file = 'convocation_' . $agf->id . '_' . $parameters['soc_dest'] . '.pdf';
					$result = agf_pdf_create($this->db, $id_tmp, '', $model, $outputlangs, $file, $socid, '', '', '', '');
				}
				if (file_exists($conf->agefodd->dir_output . '/courrier-convention_' . $agf->id . '_' . $parameters['soc_origin'] . '.pdf')) {
					$model = 'courrier';
					$file = 'courrier-convention_' . $agf->id . '_' . $parameters['soc_dest'] . '.pdf';
					$result = agf_pdf_create($this->db, $id_tmp, '', $model, $outputlangs, $file, $socid, 'convention', '', '', '');
				}
				if (file_exists($conf->agefodd->dir_output . '/courrier-accueil_' . $agf->id . '_' . $parameters['soc_origin'] . '.pdf')) {
					$model = 'courrier';
					$file = 'courrier-accueil_' . $agf->id . '_' . $parameters['soc_dest'] . '.pdf';
					$result = agf_pdf_create($this->db, $id_tmp, '', $model, $outputlangs, $file, $socid, 'accueil', '', '', '');
				}
				if (file_exists($conf->agefodd->dir_output . '/attestationendtraining_' . $agf->id . '_' . $parameters['soc_origin'] . '.pdf')) {
					$model = 'attestationendtraining';
					$file = 'attestationendtraining_' . $agf->id . '_' . $parameters['soc_dest'] . '.pdf';
					$result = agf_pdf_create($this->db, $id_tmp, '', $model, $outputlangs, $file, $socid, '', '', '', '');
				}
				if (file_exists($conf->agefodd->dir_output . '/attestationpresencetraining_' . $agf->id . '_' . $parameters['soc_origin'] . '.pdf')) {
					$model = 'attestationpresencetraining';
					$file = 'attestationpresencetraining_' . $agf->id . '_' . $parameters['soc_dest'] . '.pdf';
					$result = agf_pdf_create($this->db, $id_tmp, '', $model, $outputlangs, $file, $socid, '', '', '', '');
				}
				if (file_exists($conf->agefodd->dir_output . '/attestationpresencecollective_' . $agf->id . '_' . $parameters['soc_origin'] . '.pdf')) {
					$model = 'attestationpresencecollective';
					$file = 'attestationpresencecollective_' . $agf->id . '_' . $parameters['soc_dest'] . '.pdf';
					$result = agf_pdf_create($this->db, $id_tmp, '', $model, $outputlangs, $file, $socid, '', '', '', '');
				}
				if (file_exists($conf->agefodd->dir_output . '/attestation_' . $agf->id . '_' . $parameters['soc_origin'] . '.pdf')) {
					$model = 'attestation';
					$file = 'attestation_' . $agf->id . '_' . $parameters['soc_dest'] . '.pdf';
					$result = agf_pdf_create($this->db, $id_tmp, '', $model, $outputlangs, $file, $socid, '', '', '', '');
				}
				if (file_exists($conf->agefodd->dir_output . '/courrier-cloture_' . $agf->id . '_' . $parameters['soc_origin'] . '.pdf')) {
					$model = 'courrier';
					$file = 'courrier-cloture_' . $agf->id . '_' . $parameters['soc_dest'] . '.pdf';
					$result = agf_pdf_create($this->db, $id_tmp, '', $model, $outputlangs, $file, $socid, 'cloture', '', '', '');
				}

				// CONVENTION
				$sql2 = "SELECT * FROM " . MAIN_DB_PREFIX . "agefodd_convention WHERE fk_societe=" . $parameters['soc_origin'] . ";";
				$resql2 = $this->db->query($sql2);
				if ($resql2) {
					while ($obj2 = $this->db->fetch_object($resql2)) {
						$convention = new Agefodd_convention($this->db);
						$convention->fetch($agf->id, $parameters['soc_origin']);
						$convention->socid = $parameters['soc_dest'];
						$convention->update($user);

						if (file_exists($conf->agefodd->dir_output . '/convention_' . $agf->id . '_' . $parameters['soc_origin'] . '_' . $obj2->rowid . '.pdf')) {
							$id_tmp = $obj2->rowid;
							$model = 'convention';
							$file = 'convention_' . $agf->id . '_' . $parameters['soc_dest'] . '_' . $obj2->rowid . '.pdf';
							$result = agf_pdf_create($this->db, $id_tmp, '', $model, $outputlangs, $file, $socid, '', '', '', $convention);
						}

					}
				}
			}
		}

		return 0;
	}

	/**
	 * @param $parameters
	 * @param $object
	 * @param $action
	 * @param $hookmanager
	 * @return int
	 * @throws \Luracast\Restler\RestException
	 */
	public function attachMoreFiles($parameters, &$object, &$action, $hookmanager) {
		global $conf,$langs;
		$documentToDealWith=array('Commande','Facture','Propal','FactureFournisseur','CommandeFournisseur');
		if ($conf->attachments->enabled && !empty($conf->global->ATTACHMENTS_INCLUDE_OBJECT_LINKED) && in_array(get_class($object),$documentToDealWith)) {
            $langs->load('agefodd@agefodd');
			dol_include_once('/agefodd/class/agsession.class.php');
			$agf = new Agsession($this->db);
			$result = $agf->fetch_all_by_order_invoice_propal('', '', 0, 0,
				get_class($object) == 'Commande' ? $object->id : 0,
				get_class($object) == 'Facture' ? $object->id : 0,
				get_class($object) == 'Propal' ? $object->id : 0,
				get_class($object) == 'FactureFournisseur' ? $object->id : 0,
				get_class($object) == 'CommandeFournisseur' ? $object->id : 0);


			if ($result < 0) {
				setEventMessage('From hook attachMoreFiles agefodd :' . $agf->error, 'errors');
			} elseif (is_array($agf->lines) && count($agf->lines)>0) {
				foreach($agf->lines as $session) {
					$TLinkDocuments=$agf->documentsSessionList($session->rowid, $object->socid );
					if (is_array($TLinkDocuments) && count($TLinkDocuments)>0) {
						foreach($TLinkDocuments as $document) {
							$fullname=$conf->agefodd->dir_output.'/'.$document;
							$fullname_md5 = md5($fullname);
							$name = pathinfo($fullname, PATHINFO_BASENAME);
							$doclist[$session->refsession][$fullname_md5]= array(
								'name' => $name
								,'path' => $conf->agefodd->dir_output
								,'fullname' => $fullname
								,'fullname_md5' => $fullname_md5);
						}
					}
				}
			}
			$this->results['AttachmentsTitleAgefodd'] = $doclist;
		}
		return 0;
	}
}
