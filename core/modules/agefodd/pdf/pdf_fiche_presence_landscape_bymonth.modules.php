<?php

/**
 * \file agefodd/core/modules/agefodd/pdf/pdf_fiche_presence_landscape_bymonth.module.php
 * \ingroup agefodd
 * \brief PDF for landscape format training attendees session sheet
 */

dol_include_once('/agefodd/core/modules/agefodd/pdf/pdf_fiche_presence_landscape.modules.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
dol_include_once('/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');


class pdf_fiche_presence_landscape_bymonth extends pdf_fiche_presence_landscape
{

	public $session;
	public $sessionTrainee;
	public $sessionCalendar;

	/** @var int $maxDateSlotsPerRow  How many date cells we can fit in one row */
	var $maxDateSlotsPerRow;

	/** @var TCPDF $pdf */
	var $pdf;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	function __construct($db)
	{
		global $conf, $langs;

		parent::__construct($db);
		$this->name = "fiche_presence_landscape_bymonth";
		$this->description = $langs->trans('AgfModPDFFichePres');

		$this->session 			= new Agsession($this->db);
		$this->stagiaires 		= new Agefodd_session_stagiaire($this->db);
		$this->formateurs		= new Agefodd_session_formateur($this->db);
		$this->sessionCalendar 	= new Agefodd_sesscalendar($this->db);
	}

	/**
	 * @param Object $agf Session
	 * @param Translate $outputlangs $outputlangs
	 * @param string $file file
	 * @param int $socid socid
	 * @return int
	 */
	function write_file($agf, $outputlangs, $file, $socid, $courrier = '')
	{
		global $user, $langs, $conf, $hookmanager;

		if (!is_object($outputlangs))
			$this->outputlangs = $langs;
		else $this->outputlangs = $outputlangs;

		if (!is_object($agf)) {
			/** @var int $id  If $agf is not an object, we assume it is the ID of a session. */
			$id = $agf;
			$agf = new Agsession($this->db);
			$ret = $agf->fetch($id);
			if ($ret <= 0)  {
				$this->error = $langs->trans('AgfErrorUnableToFetchSession', $id);
				return 0;
			}
			$this->session=$agf;
		}
		else $this->session = $agf;

		// Definition of $dir and $file
		$dir = $conf->agefodd->dir_output;
		if (empty($dir)) {
			$this->error = $langs->trans("ErrorConstantNotDefined", "AGF_OUTPUTDIR");
			return 0;
		}
		$file = $dir . '/' . $file;
		if (!file_exists($dir) && dol_mkdir($dir) < 0) {
			$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
			return 0;
		}

//		$this->pdf = pdf_getInstance_agefodd($this->session, $this, $this->format, $this->unit, $this->orientation);
		$this->pdf = pdf_getInstance($this->format, $this->unit, $this->orientation);
		$this->pdf->ref_object = $this->session;

		if (class_exists('TCPDF')) {
			$this->pdf->setPrintHeader(false);
			$this->pdf->setPrintFooter(false);
		}

		$this->pdf->Open();

		// set Metadata
		$this->pdf->SetTitle($this->outputlangs->convToOutputCharset($this->outputlangs->transnoentities('AgfPDFFichePres1') . " " . $this->session->ref));
		$this->pdf->SetSubject($this->outputlangs->transnoentities("AgfPDFFichePres1"));
		$this->pdf->SetCreator("Dolibarr " . DOL_VERSION . ' (Agefodd module)');
		$this->pdf->SetAuthor($this->outputlangs->convToOutputCharset($user->fullname));
		$this->pdf->SetKeyWords($this->outputlangs->convToOutputCharset($this->session->ref) . " " . $this->outputlangs->transnoentities("Document"));
		if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION)
			$this->pdf->SetCompression(false);

		// Load multicompany entities
		if (!empty($conf->multicompany->enabled)) {
			dol_include_once('/multicompany/class/dao_multicompany.class.php');
			$this->dao = new DaoMulticompany($this->db);
			$this->dao->getEntities();
		}

		// START LOAD AGEFODD DATA
		// load trainers
		if ($this->session->fetchTrainers() < 0)
			$this->error = $langs->trans('AgfErrorUnableToFetchTrainer');
		// load trainees
		elseif ($this->stagiaires->fetch_stagiaire_per_session($this->session->id) < 0)
			$this->error = $langs->trans('AgfErrorUnableToFetchTrainees');
		// load session calendar
		elseif ($this->sessionCalendar->fetch_all($this->session->id) < 0)
			$this->error = $langs->trans('AgfErrorUnableToFetchCalendar');
		// Verify trainer count
		elseif (!count($this->session->TTrainer)) {
			$this->error = $langs->trans('AgfErrorNoTrainersFound');
		}
		if (!empty($this->error)) return 0;

		// END LOAD AGEFODD DATA

		$this->pdf->setPageOrientation($this->orientation, 1, $this->marge_basse);
		$this->_resetColorsAndStyle();

		// Left, Top, Right
		$this->pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

		// compute how many date slots we can fit in one row (depends on the length of the data in the first column)
		$firstColWidth = $this->trainer_widthcol1; // TODO: compute dynamically (according to contents)
		$this->dateColMinWidth = 30;
		$this->maxDateSlotsPerRow = intval(($this->espaceH_dispo - $firstColWidth) / $this->dateColMinWidth);

		$this->datesByMonth = array();
		/* data structure (assuming maxDateSlotsPerRow = 3):
		Each array at the deepest level represents what will be printed on a separate PDF page.

		$this->datesByMonth = array (
			'12/2019' => array (
				array (<agf date session>, <agf date session>, <agf date session>),
				array (<agf date session>, <agf date session>)
			),
			'01/2020' => array (
				array (<agf date session>, <agf date session>, <agf date session>),
				array (<agf date session>)
			),
			'02/2020' => array (
				array (<agf date session>, <agf date session>)
			)
		);
		 */

		foreach ($this->sessionCalendar->lines as $dateSlot) {
			$dateTms = $dateSlot->date_session;
			$monthYear = dol_print_date($dateTms, '%m/%Y');

			if (!isset($this->datesByMonth[$monthYear])) $this->datesByMonth[$monthYear] = array(array());
			$nbChunks = count($this->datesByMonth[$monthYear]); // at least 1
			$nbDatesInLastChunk = count($this->datesByMonth[$monthYear][$nbChunks-1]);
			if ($nbDatesInLastChunk == $this->maxDateSlotsPerRow) {
				$this->datesByMonth[$monthYear][] = array();
				$nbChunks = count($this->datesByMonth[$monthYear]);
			}
			$this->datesByMonth[$monthYear][$nbChunks-1][] = $dateSlot;
		}

		$this->_pagebody($this->session, $this->outputlangs);

		$this->pdf->Close();
		$this->pdf->Output($file, 'F');
		if (! empty($conf->global->MAIN_UMASK))
			@chmod($file, octdec($conf->global->MAIN_UMASK));


		// Add pdfgeneration hook
		if (!isset($hookmanager) || !is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager=new HookManager($this->db);
		}

		$hookmanager->initHooks(array('pdfgeneration'));
		$parameters = array(
			'file'=>$file,
			'object'=>$this->session,
			'outputlangs'=>$this->outputlangs
		);
		global $action;
		$reshook=$hookmanager->executeHooks(
			'afterPDFCreation',
			$parameters,
			$this,
			$action
		);    // Note that $action and $object may have been modified by some hooks
		return 1; // Pas d'erreur

	}

	/**
	 * Show header of page
	 * @param object $agf Object invoice
	 * @param Translate $outputlangs Object lang for output
	 * @return void
	 */
	function _pagebody($agf, $outputlangs)
	{
		global $conf, $mysoc;

		// Set path to the background PDF File
		if (empty($conf->global->MAIN_DISABLE_FPDI) && !empty($conf->global->AGF_ADD_PDF_BACKGROUND_P)) {
			$pagecount = $this->pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P);
			$tplidx = $this->pdf->importPage(1);
		}

		$this->height_for_footer = 40;
		if (!empty($conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER))
			$this->height_for_footer = $conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER;

		$realFooterHeight = $this->getRealHeightLine('foot');
		$this->height_for_footer = max($this->height_for_footer, $realFooterHeight);

		if (!empty($conf->multicompany->enabled) && !isset($this->dao)) {
			dol_include_once('/multicompany/class/dao_multicompany.class.php');
			$this->dao = new DaoMulticompany($this->db);
			$this->dao->getEntities();
		}

		if (!empty($conf->global->AGF_FICHEPRES_SHOW_OPCO_NUMBERS))
		{
			$this->TOpco = array();
			if (!empty($this->stagiaires->lines))
			{
				foreach ($this->stagiaires->lines as $line)
				{
					//OPCO du participant
					$agf_opca = new Agefodd_opca($this->db);
					$id_opca = $agf_opca->getOpcaForTraineeInSession($line->socid, $this->pdf->ref_object->id, $line->stagerowid);
					if($id_opca)  $res = $agf_opca->fetch($id_opca);
					if($res && !array_key_exists($agf_opca->num_OPCA_file, $this->TOpco)) $this->TOpco[$agf_opca->num_OPCA_file] = $agf_opca;
				}
			}
		}

		foreach ($this->datesByMonth as $monthYear => $TTSessionDate) {
			foreach ($TTSessionDate as $dates_array) {
				// New page
				$this->pdf->AddPage();
				$this->setupNewPage();

				$this->maxSlot = count($dates_array);
				$this->trainer_widthtimeslot = ($this->espaceH_dispo - $this->trainer_widthcol1 -2) / $this->maxSlot;
				$this->trainee_widthtimeslot = ($this->espaceH_dispo - $this->trainee_widthcol1 -2) / $this->maxSlot;

				list($posX, $posY) = $this->_pagehead($agf, 1, $this->outputlangs);

				if (!empty($conf->global->AGF_FICHEPRES_SHOW_TIME_FOR_PAGE)) $this->setSummaryTime($dates_array);
				list($posX, $posY) = $this->printSessionSummary($posX, $posY);

				list($posX, $posY) = $this->showTrainerBloc(array($this->marge_gauche, $posY, $dates_array));
				list($posX, $posY) = $this->showTraineeBloc(array($this->marge_gauche, $posY, $dates_array));
			}
		}

	}

	public function showTrainerBloc($params = array())
	{
		global $conf;
		/**
		 * *** Bloc formateur ****
		 */
		list($posX, $posY, $dates_array) = $params;

		$posX+= 2;

		$this->_resetColorsAndStyle();

		if (!empty($this->session->TTrainer))
		{
			$this->formateurs->lines = $this->session->TTrainer;
			$this->printPersonsBlock('formateurs', $this->formateurs->lines, $dates_array);
//			list($posX, $posY) = $this->printTrainerBlockHeader($dates_array);
//			list($posX, $posY) = $this->printTrainerBlockLines($posX, $posY, $dates_array, $this->session);
		}

		return array($posX, $posY);

	}

	public function showTraineeBloc($params = array())
	{
		global $conf;
		/**
		 * *** Bloc stagiaire ****
		 */

		list($posX, $posY, $dates_array) = $params;
		$posX+= 2;

		$this->_resetColorsAndStyle();

		if (!empty($this->stagiaires->lines))
		{
			$this->printPersonsBlock('stagiaires', $this->stagiaires->lines, $dates_array);
//			list($posX, $posY) = $this->printTraineeBlockHeader($posX, $posY, $dates_array);
//			list($posX, $posY) = $this->printTraineeBlockLines($posX, $posY, $dates_array, $agf);
		}

		return array($posX, $posY);

	}

	/**
	 * Reset text color, draw color, line style and font.
	 */
	public function _resetColorsAndStyle()
	{
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs));
		$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
		$this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
		$this->pdf->SetLineStyle(array(
			'width' => 0.05,
		));
	}

	/**
	 * @param TCPDF $pdf pdf
	 * @param Object $object object
	 * @param Translate $outputlangs outputlangs
	 * @return int int
	 */
	function _pagefoot($object, $outputlangs)
	{
		$this->pdf->SetDrawColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		$this->pdf->SetTextColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		return pdf_agfpagefoot($this->pdf, $outputlangs, '', $this->emetteur, $this->marge_basse +5, $this->marge_gauche, $this->page_hauteur, $object, 1, 0);
	}
}
