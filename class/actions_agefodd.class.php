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
		// global $langs,$conf,$user;
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
}