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
class ActionsAgefodd
{
	protected $db;
	public $dao;
	public $error;
	public $errors = array();
	public $resprints = '';

	/**
	 * Constructor
	 *
	 * @param DoliDB $db
	 */
	public function __construct($db) {
		$this->db = $db;
		$this->error = 0;
		$this->errors = array();
	}

	/**
	 * printSearchForm Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object &$object Object to use hooks on
	 * @param string &$action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
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

	public function completeTabsHead($parameters, &$object, &$action, $hookmanager) {

		global $conf, $langs, $bc, $var;

		$contextarray=array('ordersuppliercard','propalcard','ordercard','invoicecard','invoicesuppliercard');
		$contextcurrent=explode(':', $parameters['context']);
		$current_obj=$parameters['object'];
		$res_array=array_intersect($contextarray, $contextcurrent);
		if (is_array($res_array) && count($res_array)>0 && $parameters['mode']=='add') {
			$head = $parameters['head'];
			foreach ( $head as $key=>&$val) {
				if ($val[2]=='tabAgefodd') {
					dol_include_once('/agefodd/class/agsession.class.php');
					$agf = new Agsession($this->db);
					$resql = $agf->fetch_all_by_order_invoice_propal('', '', 0, 0,
							get_class($current_obj)=='Commande'?$current_obj->id:0,
							get_class($current_obj)=='Facture'?$current_obj->id:0,
							get_class($current_obj)=='Propal'?$current_obj->id:0,
							get_class($current_obj)=='FactureFournisseur'?$current_obj->id:0,
							get_class($current_obj)=='CommandeFournisseur'?$current_obj->id:0);
					if ($resql <0) {
						setEventMessage('From hook completeTabsHead agefodd :'.$agf->error,'errors');
					} else {
						$langs->load('agefodd@agefodd');
						if ($resql > 0) $val[1].= ' <span class="badge">'.$resql.'</span>';
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
	 * @param Object &$object Object to use hooks on
	 * @param string &$action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	public function addSearchEntry($parameters, &$object, &$action, $hookmanager) {
		global $conf, $langs, $user;
		$langs->load('agefodd@agefodd');

		$arrayresult = array();
		if (empty($conf->global->AGEFODD_HIDE_QUICK_SEARCH) && $user->rights->agefodd->lire && empty($user->societe_id)) {
			$arrayresult['searchintoagefoddsession'] = array(
					'text' => img_object('', 'agefodd@agefodd') . ' ' . $langs->trans("AgfSessionId"),
					'url' => dol_buildpath('/agefodd/session/list.php', 1) . '?search_id=' . urlencode($parameters['search_boxvalue'])
			);
			$arrayresult['searchintoagefoddtrainee'] = array(
					'text' => img_object('', 'contact') . ' ' . $langs->trans("AgfMenuActStagiaire"),
					'url' => dol_buildpath('/agefodd/trainee/list.php', 1) . '?search_namefirstname=' . urlencode($parameters['search_boxvalue'])
			);
		}
		$this->results = $arrayresult;

		return 0;
	}

	/**
	 * formObjectOptions Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object &$object Object to use hooks on
	 * @param string &$action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	public function formObjectOptions($parameters, &$object, &$action, $hookmanager) {
		global $langs, $conf, $user;

		// dol_syslog(get_class($this).':: formObjectOptions',LOG_DEBUG);

		return 0;
	}

	/**
	 * DoAction Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object &$object Object to use hooks on
	 * @param string &$action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager) {
		global $langs, $conf, $user;
		$TContext = explode(':', $parameters['context']);
		
		
		if (in_array('externalaccesspage', $TContext))
		{
			dol_include_once('/agefodd/lib/agf_externalaccess.lib.php');
			dol_include_once('/agefodd/lib/agefodd.lib.php');
			dol_include_once('/agefodd/class/agsession.class.php');
			dol_include_once('/agefodd/class/agefodd_formateur.class.php');
			dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
			dol_include_once('/agefodd/class/agefodd_session_formateur_calendrier.class.php');
			dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');
			
			// TODO gérer ici les actions de mes pages pour update les données
			$context = Context::getInstance();
			if ($context->controller == 'agefodd_session_card')
			{
				if ($action == 'deleteCalendrierFormateur' && GETPOST('sessid') > 0 && GETPOST('fk_agefodd_session_formateur_calendrier') > 0)
				{
					$agsession = new Agsession($this->db);
					if ($agsession->fetch(GETPOST('sessid')) > 0) // Vérification que la session existe
					{
						$trainer = $agsession->getTrainerFromUser($user);
						if ($trainer)
						{
							$context->setControllerFound();
							// TODO 
							// Faire la suppression du calendrier formateur ainsi que ceux des participants et de leurs saisie de temps
							$error = 0;
							$this->db->begin();
							$agf_calendrier_formateur = new Agefoddsessionformateurcalendrier($this->db);
							if ($agf_calendrier_formateur->fetch(GETPOST('fk_agefodd_session_formateur_calendrier')) > 0)
							{
								$TCalendrier = _getCalendrierFromCalendrierFormateur($agf_calendrier_formateur);
								$agf_calendrier = $TCalendrier[0];
								$r=$agf_calendrier->delete($user);
								if ($r < 0) $error++;
								
								$r=$agf_calendrier_formateur->delete($user);
								if ($r < 0) $error++;
							}
							
							
							if ($error > 0)
							{
								$this->db->rollback();
								$context->setError($langs->trans('AgfExternalAccessErrorDeleteCreneau'));
							}
							else
							{
								$this->db->commit();
							}

							$url = $context->getRootUrl(GETPOST('controller'), '&sessid='.$agsession->id);
							header('Location: '.$url);
							exit;
						}
					}
					
					header('Location: '.$context->getRootUrl(GETPOST('controller')));
					exit;
				}
			}
			else if ($context->controller == 'agefodd_session_card_time_slot' && in_array($action, array('add', 'update')) && GETPOST('sessid','int') > 0)
			{
				$agsession = new Agsession($this->db);
				if ($agsession->fetch(GETPOST('sessid')) > 0) // Vérification que la session existe
				{
					$trainer = $agsession->getTrainerFromUser($user); // Est ce que mon user (formateur) est bien associé à la session ?
					if ($trainer)
					{
						$slotid = GETPOST('slotid', 'int');
						
						$agf_calendrier_formateur = new Agefoddsessionformateurcalendrier($this->db);
						if (!empty($slotid)) $agf_calendrier_formateur->fetch($slotid);
						
						// Est ce que mon calendrier appartient bien à ma session ? OU que l'id est vide pour un "add"
						if (($agf_calendrier_formateur->id > 0 && $agf_calendrier_formateur->sessid == $agsession->id) || empty($agf_calendrier_formateur->id))
						{
							$date_session = GETPOST('date_session');
							$heured = GETPOST('heured');
							$heuref = GETPOST('heuref');
							$status = GETPOST('status');
							
							if (!empty($date_session) && !empty($heured) && !empty($heuref))
							{
								$context->setControllerFound();
								dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');
								
								$error = 0;
								
								// Je récupère le/les calendrier participants avant modificatino du calendrier formateur
								$TCalendrier = _getCalendrierFromCalendrierFormateur($agf_calendrier_formateur);
								
								$this->db->begin();
								
								$agf_calendrier_formateur->sessid = $agsession->id;
								$agf_calendrier_formateur->date_session = strtotime($date_session);
								$agf_calendrier_formateur->heured = strtotime($date_session.' '.$heured);
								$agf_calendrier_formateur->heuref = strtotime($date_session.' '.$heuref);
								$agf_calendrier_formateur->fk_agefodd_session_formateur = $trainer->agefodd_session_formateur->id;
								
								if (in_array($status, array(Agefoddsessionformateurcalendrier::STATUS_DRAFT, Agefoddsessionformateurcalendrier::STATUS_CONFIRMED, Agefoddsessionformateurcalendrier::STATUS_CANCELED)))
								{
									$agf_calendrier_formateur->status = $status;
								}
								else $agf_calendrier_formateur->status = 0;
								
								if (empty($agf_calendrier_formateur->id)) $r=$agf_calendrier_formateur->create($user);
								else $r=$agf_calendrier_formateur->update($user);
								
								if ($r <= 0) $error++;
								
								if (empty($TCalendrier))
								{
									$agf_calendrier = new Agefodd_sesscalendar($this->db);
									$agf_calendrier->sessid = $agsession->id;
									$agf_calendrier->date_session = $agf_calendrier_formateur->date_session;
									$agf_calendrier->heured = $agf_calendrier_formateur->heured;
									$agf_calendrier->heuref = $agf_calendrier_formateur->heuref;
									$agf_calendrier->status = $agf_calendrier_formateur->status;
									$r=$agf_calendrier->create($user);
									if ($r <= 0) $error++;
									$TCalendrier[] = $agf_calendrier;
								}
								else
								{
									// TODO normalement je suis sensé avoir 1 seule valeur, mais le mode de fonctionnement fait qu'il est possible d'en avoir plusieurs
//									foreach ($TCalendrier as &$agf_calendrier)
//									{
										$agf_calendrier = $TCalendrier[0];
										$agf_calendrier->date_session = $agf_calendrier_formateur->date_session;
										$agf_calendrier->heured = $agf_calendrier_formateur->heured;
										$agf_calendrier->heuref = $agf_calendrier_formateur->heuref;
										$agf_calendrier->status = $agf_calendrier_formateur->status;
										$r=$agf_calendrier->update($user);
										if ($r <= 0) $error++;
//									}
								}
								
								$now = dol_now();
								$THour = GETPOST('hours', 'array');
								$stagiaires = new Agefodd_session_stagiaire($this->db);
								$stagiaires->fetch_stagiaire_per_session($agsession->id);
								foreach ($stagiaires->lines as &$stagiaire)
								{
									if ($stagiaire->id <= 0) continue;

									$agfssh = new Agefoddsessionstagiaireheures($this->db);
									$result = $agfssh->fetch_by_session($agsession->id, $stagiaire->id, $agf_calendrier->id);
									if ($result < 0) $error++;
									else
									{
										$duree = 0;
										// Si le statut passe à "annulé", alors je force la saisie du compteur d'heure car c'est du consommé
										if ($agf_calendrier_formateur->status == Agefoddsessionformateurcalendrier::STATUS_CANCELED)
										{
											$duree = ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
										}
										else if ($agf_calendrier->date_session < $now && !empty($THour[$stagiaire->id]))
										{
											list($hours, $minutes) = explode(':', $THour[$stagiaire->id]);
											$duree = $hours + $minutes / 60;
										}

										$agfssh->heures = (float) $duree;
										if ($result) $r=$agfssh->update($user);
										else
										{
											$agfssh->fk_stagiaire = $stagiaire->id;
											$agfssh->fk_calendrier = $agf_calendrier->id;
											$agfssh->fk_session = $agsession->id;
											$r=$agfssh->create($user);
										}

										if ($r < 0) $error++;
									}
								}
								
								if (empty($error)) $this->db->commit();
								else 
								{
									$this->db->rollback();
									$context->setError($langs->trans('AgfExternalAccessErrorCreateOrUpdateCreneau'));
								}
								
								header('Location: '.$context->getRootUrl('agefodd_session_card', '&sessid='.$agsession->id));
								exit;
							}
							else
							{
								$context->setError($langs->trans('AgefoddMissingFieldRequired'));
							}
							
						}
					}
				}
				
			}
			
			
			return 1;
		}
		
		return 0;
	}
	
	/**
	 * Mes nouvelles pages pour l'accés au portail externe
	 * 
	 * @param type $parameters
	 * @param type $object
	 * @param type $action
	 * @param type $hookmanager
	 * @return int
	 */
	public function PrintPageView($parameters, &$object, &$action, $hookmanager)
	{
		global $langs,$user;
		
		$TContext = explode(':', $parameters['context']);
		
		if (in_array('externalaccesspage', $TContext))
		{
			dol_include_once('/agefodd/lib/agf_externalaccess.lib.php');
			dol_include_once('/agefodd/class/agsession.class.php');
			dol_include_once('/agefodd/class/agefodd_formateur.class.php');
			dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
			dol_include_once('/agefodd/class/agefodd_session_formateur_calendrier.class.php');
		
			$langs->load('agefodd@agefodd');
			$context = Context::getInstance();
			
			if ($context->controller == 'agefodd')
			{
				$context->setControllerFound();
				print getMenuAgefoddExternalAccess();
			}
			else if ($context->controller == 'agefodd_session_list')
			{
				$context->setControllerFound();
				print getPageViewSessionListExternalAccess();
				
			}
			else if ($context->controller == 'agefodd_session_card' && GETPOST('sessid', 'int') > 0)
			{
				$agsession = new Agsession($this->db);
				if ($agsession->fetch(GETPOST('sessid')) > 0) // Vérification que la session existe
				{
					$trainer = $agsession->getTrainerFromUser($user);
					if ($trainer)
					{
						$context->setControllerFound();
						print getPageViewSessionCardExternalAccess($agsession, $trainer);
					}
				}
				
			}
			else if ($context->controller == 'agefodd_session_card_time_slot' && GETPOST('sessid','int') > 0)
			{
				$agsession = new Agsession($this->db);
				if ($agsession->fetch(GETPOST('sessid')) > 0) // Vérification que la session existe
				{
					$trainer = $agsession->getTrainerFromUser($user); // Est ce que mon user (formateur) est bien associé à la session ?
					if ($trainer)
					{
						$ok = true;
						$agf_calendrier_formateur = new Agefoddsessionformateurcalendrier($this->db);
						$soltid = GETPOST('slotid', 'int'); // Si vide, alors mode create
						if ($soltid > 0)
						{
							$agf_calendrier_formateur->fetch(GETPOST('slotid'));
							// Est ce que mon calendrier appartient bien à ma session
							if ($agf_calendrier_formateur->sessid != $agsession->id) $ok = false; // Tantative d'édition avec un calendrier qui n'appartient pas au formateur
						}
						
						// $ok = true par défaut pour du create, mais si j'ai un $soltid, alors j'ai vérifié que l'utilisateur a le droit
						if ($ok)
						{
							dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');
							
							$context->setControllerFound();
							print getPageViewSessionCardCalendrierFormateurExternalAccess($agsession, $trainer, $agf_calendrier_formateur, $action);
						}
					} 
				}
			}
		}
		return 0;
	}
	
	
	public function PrintServices($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		$TContext = explode(':', $parameters['context']);

		if (in_array('externalaccesspage', $TContext))
		{
			$langs->load('agefodd@agefodd');
			$context = Context::getInstance();

			$link = $context->getRootUrl('agefodd');
			$this->resprints.= getService($langs->trans('AgfTraining'),'fa-calendar',$link); // desc : $langs->trans('InvoicesDesc')

			$this->results[] = 1;
			return 0;
		}

		return 0;
	}
	
	/**
	 * elementList Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object &$object Object to use hooks on
	 * @param string &$action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	public function emailElementlist($parameters, &$object, &$action, $hookmanager) {
		global $langs, $conf, $user;
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

		return 0;
	}

	/**
	 *
	 * @param string $parameters
	 * @param Object $object
	 * @param string $action
	 * @param Hookmanager $hookmanager
	 * @return number
	 */
	function formBuilddocOptions($parameters, &$object, $action, $hookmanager) {
		global $conf, $langs, $bc, $var;

		if (in_array('propalcard', explode(':', $parameters['context']))) {

			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			dol_include_once('/agefodd/class/agsession.class.php');
			$agfsess = new Agefodd_session_element($object->db);
			$result = $agfsess->fetch_element_by_id($object->id, 'propal');
			if ($result > 0) {
				if (is_array($agfsess->lines) && count($agfsess->lines) > 0) {
					$langs->load('agefodd@agefodd');
					foreach ( $agfsess->lines as $key => $session ) {

						$sessiondetail = new Agsession($object->db);
						$sessiondetail->fetch($session->fk_session_agefodd);

						if (is_file($conf->agefodd->dir_output . '/' . 'fiche_pedago_' . $sessiondetail->formid . '.pdf')) {
							$out .= '<tr ' . $bc[$var] . '>
			     			<td colspan="4" align="right">
			     				<label for="hideInnerLines">' . $langs->trans('AgfAddTrainingProgram', $session->fk_session_agefodd) . '</label>
			     				<input type="checkbox" id="progsession_' . $session->fk_session_agefodd . '" name="progsession[]" value="' . $sessiondetail->formid . '" />
			     			</td>
			     			</tr>';

							$var = - $var;
						} else {
							$out .= '<tr ' . $bc[$var] . '>
			     			<td colspan="4" align="right">
			     				<label for="hideInnerLines">' . $langs->trans('AgfAddTrainingProgramNotExists', $session->fk_session_agefodd) . '</label>
			     				<input type="checkbox" id="progsession_' . $session->fk_session_agefodd . '" name="progsession[]" value="' . $sessiondetail->formid . '" disabled="disabled" />
			     			</td>
			     			</tr>';
							$var = - $var;
						}

						if (is_file($conf->agefodd->dir_output . '/' . 'fiche_pedago_modules_' . $sessiondetail->formid . '.pdf')) {

							$out .= '<tr ' . $bc[$var] . '>
			     			<td colspan="4" align="right">
			     				<label for="hideInnerLines">' . $langs->trans('AgfAddTrainingProgramMod', $session->fk_session_agefodd) . '</label>
			     				<input type="checkbox" id="progsession_' . $session->fk_session_agefodd . '" name="progsessionmod[]" value="' . $sessiondetail->formid . '" />
			     			</td>
			     			</tr>';

							$var = - $var;
						} else {
							$out .= '<tr ' . $bc[$var] . '>
			     			<td colspan="4" align="right">
			     				<label for="hideInnerLines">' . $langs->trans('AgfAddTrainingProgramModNotExists', $session->fk_session_agefodd) . '</label>
			     				<input type="checkbox" id="progsession_' . $session->fk_session_agefodd . '" name="progsession[]" value="' . $sessiondetail->formid . '" disabled="disabled" />
			     			</td>
			     			</tr>';
							$var = - $var;
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
	 * @param Object &$pdfhandler PDF builder handler
	 * @param string $action 'add', 'update', 'view'
	 * @return int <0 if KO,
	 *         =0 if OK but we want to process standard actions too,
	 *         >0 if OK and we want to replace standard actions.
	 */
	function afterPDFCreation($parameters, &$pdfhandler, &$action) {
		global $langs, $conf, $db;
		global $hookmanager;

		$outputlangs = $parameters['outputlangs'];

		$ret = 0;
		$pagecount = 0;
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
				$agf = new Formation($db);

				foreach ( $mergeprogram as $training_id ) {
					$agf->fetch($training_id);
					$agf->generatePDAByLink();
					$file = $conf->agefodd->dir_output . '/' . 'fiche_pedago_' . $training_id . '.pdf';
					if (is_file($file) && is_readable($file)) {
						$files[] = $file;
					}
				}
			}

			if (is_array($mergeprogrammod) && count($mergeprogrammod) > 0) {
				foreach ( $mergeprogrammod as $training_id ) {
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
					if (! empty($conf->global->MAIN_UMASK)) {
						@chmod($file, octdec($conf->global->MAIN_UMASK));
					}
				}
			}
		}
		return 0;
	}

	/**
	 *
	 * @param object $pdf
	 * @param array $files
	 */
	function concat(&$pdf, $files) {
		foreach ( $files as $file ) {
			$pagecount = $pdf->setSourceFile($file);
			for($i = 1; $i <= $pagecount; $i ++) {
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
	 * @param string $parameters
	 * @param Object $object
	 * @param string $action
	 * @param Hookmanager $hookmanager
	 * @return number
	 */
	public function doUpgrade2($parameters, &$object, &$action, $hookmanager) {
		// TODO : see why Dolibarr do not execute this
		/*dol_include_once('/agefodd/core/modAgefodd.class.php');
		 $obj = new modAgefodd($db);
		 $obj->load_tables();*/
	}
	public function pdf_getLinkedObjects($parameters, &$object, &$action, $hookmanager) {
		global $conf;

		if (empty($conf->global->AGF_PRINT_TRAINING_REF_AND_SESS_ID_ON_PDF) && empty($conf->global->AGF_PRINT_TRAINING_LABEL_REF_INTERNE_AND_SESS_ID_DATES))
			return 0;

		$TContext = explode(':', $parameters['context']);
		$intersec = array_intersect(array(
				'propalcard',
				'ordercard',
				'invoicecard'
		), $TContext);

		if (! empty($intersec)) {
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
			if ($result > 0) {

				foreach ( $agfsess->lines as $key => $session ) {
					$sessiondetail = new Agsession($object->db);
					$result = $sessiondetail->fetch($session->fk_session_agefodd);
					if ($result > 0) {
						if (!empty($conf->global->AGF_PRINT_TRAINING_REF_AND_SESS_ID_ON_PDF))
						{
							$ref_value = '';
							if (!empty($conf->global->AGF_HIDE_TRAININGREF_ON_PDF)) {
								$ref_value = $outputlangs->convToOutputCharset($sessiondetail->formref);
							}
							if (! empty($sessiondetail->formrefint)) {
								if (!empty($ref_value)) {
									$ref_value .= '/';
								}
								$ref_value .= $outputlangs->convToOutputCharset($sessiondetail->formrefint);
							}
							$ref_value .= ' (' . $sessiondetail->id . ')';
							$this->results[get_class($sessiondetail) . $sessiondetail->id.'_1'] = array(
									'ref_title' => $outputlangs->transnoentities("AgefoddRefFormationSessionId"),
									'ref_value' => $ref_value,
									'date_value' => ''
							);
						}

						if (!empty($conf->global->AGF_PRINT_TRAINING_LABEL_REF_INTERNE_AND_SESS_ID_DATES))
						{
							$formation = new Formation($object->db);
							if ($formation->fetch($sessiondetail->fk_formation_catalogue) > 0)
							{
								$this->results[get_class($formation) . $formation->id] = array(
									'ref_title' => $outputlangs->transnoentities("AgefoddTitleAndCodeInt"),
									'ref_value' => $formation->intitule.' / '.(!empty($formation->ref_interne) ? $formation->ref_interne : '-'),
									'date_value' => ''
								);
							}

							$date_d = dol_print_date($sessiondetail->dated, '%d/%m/%Y');
							$date_f = dol_print_date($sessiondetail->datef, '%d/%m/%Y');
							$this->results[get_class($sessiondetail) . $sessiondetail->id.'_2'] = array(
									'ref_title' => $outputlangs->transnoentities("AgefoddSessIdAndDates"),
									'ref_value' => $sessiondetail->id.' / '.$date_d.' - '.$date_f,
									'date_value' => ''
							);
						}

					} else {
						dol_print_error('', $agfsess->error);
					}
				}

				// if (is_array($linkedobjects)) $this->results = $linkedobjects + $this->results;
			} else {
				dol_print_error('', $agfsess->error);
			}
		}

		return 0;
	}
	
	
	function printSearchForm($parameters, &$object, &$action, $hookmanager)
	{
		global $user,$conf;
		
		$TContext = explode(':', $parameters['context']);
		if (!empty($user->rights->agefodd->lire) && !empty($conf->fullcalendarscheduler->enabled) && in_array('agefodd_session_scheduler', $TContext))
		{
			// Add my mini calendar
			$this->resprints = '<div id="agf_session_scheduler_mini"></div>';
		}
		
		return 0;
	}
}