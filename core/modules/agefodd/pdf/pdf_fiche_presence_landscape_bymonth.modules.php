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

		$this->session 				= new Agsession($this->db);
		$this->sessionTrainee 		= new Agefodd_session_stagiaire($this->db);
		$this->sessionCalendar 		= new Agefodd_sesscalendar($this->db);
	}

	/**
	 * @param Object $agf Session
	 * @param Translate $outputlangs $outputlangs
	 * @param string $file file
	 * @param int $socid socid
	 * @return int
	 */
	function write_file($agf, $outputlangs, $file, $socid, $courrier)
	{
		global $user, $langs, $conf, $hookmanager;

		if (!is_object($outputlangs))
			$this->outputlangs = $langs;
		else $this->outputlangs = $outputlangs;

		if (!is_object($agf)) {
			$id = $agf;
			if ($this->session->fetch($id) <= 0) {
				$this->error = $langs->trans('AgfErrorUnableToFetchSession', $id);
				return 0;
			};
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
		elseif ($this->sessionTrainee->fetch_stagiaire_per_session($this->session->id) < 0)
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
//		if (empty($this->agfSessionCalendar->lines)) {
//			// if there are no dates for the session, we create an undefined (empty) date.
//			$dateSlot = new Agefodd_sesscalendar($this->db);
//			$this->agfSessionCalendar->lines = array($dateSlot);
//		}

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

		$this->_pagebody_custom($this->pdf, $this->session, $this->outputlangs);

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
			'outputlangs'=>$outputlangs
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
	 * @param TCPDF $pdf Object PDF
	 * @param object $agf Object invoice
	 * @param Translate $outputlangs Object lang for output
	 * @return void
	 */
	function _pagebody_custom(&$pdf, $agf, $outputlangs)
	{
		global $conf, $mysoc;

		// Set path to the background PDF File
		if (empty($conf->global->MAIN_DISABLE_FPDI) && !empty($conf->global->AGF_ADD_PDF_BACKGROUND_P)) {
			$pagecount = $this->pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P);
			$tplidx = $this->pdf->importPage(1);
		}

		$height_for_footer = 40;
		if (!empty($conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER))
			$height_for_footer = $conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER;

		if (!empty($conf->multicompany->enabled) && !isset($this->dao)) {
			dol_include_once('/multicompany/class/dao_multicompany.class.php');
			$this->dao = new DaoMulticompany($this->db);
			$this->dao->getEntities();
		}

		foreach ($this->datesByMonth as $monthYear => $TTSessionDate) {
			foreach ($TTSessionDate as $lines_array) {
				// New page
				$this->pdf->AddPage();
				$this->prepareNewPage($pdf, true);
				$posY = $this->heightForHeader;

				$this->maxSlot = count($lines_array);
				$this->trainer_widthtimeslot = ($this->espaceH_dispo - $this->trainer_widthcol1 -2) / $this->maxSlot;
				$this->trainee_widthtimeslot = ($this->espaceH_dispo - $this->trainee_widthcol1 -2) / $this->maxSlot;

//				if (empty($this->heightForHeader))
//				{
//					$this->pdf->startTransaction();
//					list($this->heightForHeader, $dummy) = $this->_pagehead($this->pdf, $this->outputlangs, $this->session, array(array()), 1);
//					$this->heightForFooter = $this->_pagefoot($this->pdf, $this->session, $this->outputlangs);
//					$this->pdf->rollbackTransaction(true);
//				}
//
//				if (!empty($tplidx))
//					$this->pdf->useTemplate($tplidx);
//				list($posY, $posX) = $this->_pagehead($this->pdf, $outputlangs, $agf, $lines_array);

				$posY = $this->pdfPrintCallback($this->pdf, array($this,'showTrainerBloc'), true, array($this->marge_gauche, $posY + 2, $lines_array));
				$posY = $this->pdfPrintCallback($this->pdf, array($this,'showTraineeBloc'), true, array($this->marge_gauche, $this->pdf->GetY() + 1, $lines_array));
				$posY = $this->pdfPrintCallback($this->pdf, array($this,'showSignatureBloc'), true, array($this->marge_gauche, $this->pdf->GetY() + 1));


				// Pied de page
//				$this->_pagefoot($this->pdf, $agf, $outputlangs);
//				if (method_exists($this->pdf, 'AliasNbPages')) {
//					$this->pdf->AliasNbPages();
//				}
			}
		}

	}

	public function showTrainerBloc(&$pdf, $params = array())
	{
		global $outputlangs, $conf;
		/**
		 * *** Bloc formateur ****
		 */
		list($posX, $posY, $lines_array) = $params;
		$posX+= 2;
		$posY+= 5;

		$this->_resetColorsAndStyle();

		if (empty($noTrainer)) {
			$pdf->SetXY($posX - 2, $posY - 2);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'BI', 9);
			$str = $outputlangs->transnoentities('AgfPDFFichePres12');
			$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
			$posY += 2;

			// Entête
			// Cadre
			$pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $this->h_ligne + 8);
			// Nom
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
			$str = $outputlangs->transnoentities('AgfPDFFichePres16');
			$pdf->Cell($this->trainer_widthcol1, $this->h_ligne + 8, $outputlangs->convToOutputCharset($str), 'R', 2, "C", 0);
			// Signature
			$pdf->SetXY($posX + $this->trainer_widthcol1, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
			$str = $outputlangs->transnoentities('AgfPDFFichePres18');
			$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);

			$pdf->SetXY($posX + $this->trainer_widthcol1, $posY + 3);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', 7);
            $showAdditionalText = empty($conf->global->AGF_FICHE_PRES_HIDE_LEGAL_MEANING_BELOW_SIGNATURE_HEADER);
            if ($showAdditionalText) {
                $str = $outputlangs->transnoentities('AgfPDFFichePres13');
                $pdf->Cell(0, 5, $outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);
            }
            $posY += $this->h_ligne;

			// Date

			$last_day = '';
			$same_day = 0;

			for ($y = 0; $y < $this->maxSlot; $y++) {
				// Jour
				$pdf->SetXY($posX + $this->trainer_widthcol1 + (20 * $y), $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
				if ($lines_array[$y]->date_session) {
					$date = dol_print_date($lines_array[$y]->date_session, 'daytextshort');
				} else {
					$date = '';
				}
				$str = $date;
				if ($last_day == $lines_array[$y]->date_session) {
					$same_day += 1;
					$pdf->SetFillColor(255, 255, 255);
				} else {
					$same_day = 0;
				}
				$pdf->SetXY($posX + $this->trainer_widthcol1 + ($this->trainer_widthtimeslot * $y) - ($this->trainer_widthtimeslot * ($same_day)), $posY);
				$pdf->Cell($this->trainer_widthtimeslot * ($same_day + 1), 4, $outputlangs->convToOutputCharset($str), 1, 2, "C", $same_day);

				// horaires
				$pdf->SetXY($posX + $this->trainer_widthcol1 + ($this->trainer_widthtimeslot * $y), $posY + 4);
				if ($lines_array[$y]->heured && $lines_array[$y]->heuref) {
					$str = dol_print_date($lines_array[$y]->heured, 'hour') . ' - ' . dol_print_date($lines_array[$y]->heuref, 'hour');
				} else {
					$str = '';
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);
				$pdf->Cell($this->trainer_widthtimeslot, 4, $outputlangs->convToOutputCharset($str), 1, 2, "C", 0);

				$last_day = $lines_array[$y]->date_session;
			}
			$posY = $pdf->GetY();

			$nbform = count($this->session->TTrainer);//$formateurs->fetch_formateur_per_session($agf->id);

			if ($nbform > 0) {
				foreach ($this->session->TTrainer as $trainerlines) {

					// Cadre
					//$pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $this->h_ligne);

					// Nom
					$pdf->SetXY($posX - 2, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);
					$str = strtoupper($trainerlines->name) . ' ' . ucfirst($trainerlines->firstname);
					$pdf->MultiCell($this->trainer_widthcol1 + 2, $this->h_ligne, $outputlangs->convToOutputCharset($str), 1, "L", false, 1, '', '', true, 0, false, false, $this->h_ligne, 'M');

					for ($i = 0; $i < $this->maxSlot; $i++) {
						$pdf->Rect($posX + $this->trainer_widthcol1 + $this->trainer_widthtimeslot * $i, $posY, $this->trainer_widthtimeslot, $this->h_ligne);
					}

					$posY = $pdf->GetY();
				}
			}

			$posY = $pdf->GetY() + 2;
		}
	}

	public function showTraineeBloc(&$pdf, $params = array())
	{
		global $conf, $outputlangs;
		/**
		 * *** Bloc stagiaire ****
		 */

		list($posX, $posY, $lines_array) = $params;
		$posX+= 2;
		$posY = $this->pdf->GetY() + 5;

		$this->_resetColorsAndStyle();

		$pdf->SetXY($posX - 2, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), 'BI', 9);
		$str = $outputlangs->transnoentities('AgfPDFFichePres15');
		$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
		$posY = $pdf->GetY();

		// Entête
		// Cadre
		$pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $this->h_ligne + 8);
		// Nom
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
		$str = $outputlangs->transnoentities('AgfPDFFichePres16');
		$pdf->Cell($this->trainee_widthcol1, $this->h_ligne + 8, $outputlangs->convToOutputCharset($str), 'R', 2, "C", 0);
		// Société
		if (empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
			$pdf->SetXY($posX + $this->trainee_widthcol1, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
			$str = $outputlangs->transnoentities('AgfPDFFichePres17');
			$pdf->Cell($this->trainee_widthcol2, $this->h_ligne + 8, $outputlangs->convToOutputCharset($str), 0, 2, "C", 0);
		} else {
			$this->trainee_widthcol2 = 0;
		}

		// Signature
		$pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
		$str = $outputlangs->transnoentities('AgfPDFFichePres18');
		$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);

		$pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2, $posY + 3);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', 7);
        $showAdditionalText = empty($conf->global->AGF_FICHE_PRES_HIDE_LEGAL_MEANING_BELOW_SIGNATURE_HEADER);
        if ($showAdditionalText) {
            $str = $outputlangs->transnoentities('AgfPDFFichePres19');
            $pdf->Cell(0, 5, $outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);
        }
		$posY += $this->h_ligne;

		// Date

		$last_day = '';
		for ($y = 0; $y < $this->maxSlot; $y++) {
			// Jour
			$pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2 + (20 * $y), $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
			if ($lines_array[$y]->date_session) {
				$date = dol_print_date($lines_array[$y]->date_session, 'daytextshort');
			} else {
				$date = '';
			}
			$str = $date;
			if ($last_day == $lines_array[$y]->date_session) {
				$same_day += 1;
				$pdf->SetFillColor(255, 255, 255);
			} else {
				$same_day = 0;
			}
			$pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2 + ($this->trainee_widthtimeslot * $y) - ($this->trainee_widthtimeslot * ($same_day)), $posY);
			$pdf->Cell($this->trainee_widthtimeslot * ($same_day + 1), 4, $outputlangs->convToOutputCharset($str), 1, 2, "C", $same_day);

			// horaires
			$pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2 + ($this->trainee_widthtimeslot * $y), $posY + 4);
			if ($lines_array[$y]->heured && $lines_array[$y]->heuref) {
				$str = dol_print_date($lines_array[$y]->heured, 'hour') . ' - ' . dol_print_date($lines_array[$y]->heuref, 'hour');
			} else {
				$str = '';
			}
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);
			$pdf->Cell($this->trainee_widthtimeslot, 4, $outputlangs->convToOutputCharset($str), 1, 2, "C", 0);

			$last_day = $lines_array[$y]->date_session;
		}

		$posY = $pdf->GetY();

		$nbsta_index = 1;

		// ligne
		if (is_object($this->dao) && $conf->global->AGF_ADD_ENTITYNAME_FICHEPRES) {
			$this->h_ligne = $this->h_ligne + 3;
		}
		if (!empty($conf->global->AGF_ADD_DTBIRTH_FICHEPRES)) {
			$this->h_ligne = $this->h_ligne + 3;
		}
		$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);

		foreach ($this->sessionTrainee->lines as $line) {

			if (!empty($conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES)) {
				$TStagiaireStatusToExclude = explode(',', $conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES);
				$status_stagiaire = (int)$line->status_in_session;
				if (in_array($status_stagiaire, $TStagiaireStatusToExclude))
					continue;
			}

			if (!empty($conf->global->AGF_ADD_INDEX_TRAINEE)) {
				$str = $nbsta_index . '. ';
			} else {
				$str = '';
			}

			$nbsta_index++;
			// Cadre
			$this->pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $this->h_ligne);

			// Nom
			$this->pdf->SetXY($posX - 2, $posY);
			$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);

			if (!empty($line->civilite)) {
				if ($line->civilite == 'MR') {
					$str .= 'M. ';
				} elseif ($line->civilite == 'MME' || $line->civilite == 'MLE') {
					$str .= 'Mme. ';
				} else {
					$str .= $line->civilite . ' ';
				}
			}
			$str .= $line->nom . ' ' . $line->prenom;
			if (!empty($line->poste) && empty($conf->global->AGF_HIDE_POSTE_FICHEPRES)) {
				$str .= ' (' . $line->poste . ')';
			}
			if (!empty($line->date_birth) && !empty($conf->global->AGF_ADD_DTBIRTH_FICHEPRES)) {
				$outputlangs->load("other");
				$str .= "\n" . $outputlangs->trans('DateToBirth') . ' : ' . dol_print_date($line->date_birth, 'day');
			}

			if (!empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
				if (!empty($line->socname)) {
					$str .= '-' . dol_trunc($line->socname, 27);
				}
			}

			if (is_object($this->dao) && $conf->global->AGF_ADD_ENTITYNAME_FICHEPRES) {
				$c = new Societe($this->db);
				$c->fetch($line->socid);

				if (count($this->dao->entities) > 0) {
					foreach ($this->dao->entities as $e) {
						if ($e->id == $c->entity) {
							$str .= "\n" . $outputlangs->trans('Entity') . ' : ' . $e->label;
							break;
						}
					}
				}
			}
			$this->pdf->MultiCell($this->trainee_widthcol1 + 2, $this->h_ligne, $outputlangs->convToOutputCharset($str), 1, "L", false, 1, '', '', true, 0, false, false, $this->h_ligne, 'M');

			// Société
			if (empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
				$this->pdf->SetXY($posX + $this->trainee_widthcol1, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);
				$str = dol_trunc($line->socname, 27);
				$this->pdf->MultiCell($this->trainee_widthcol2, $this->h_ligne, $outputlangs->convToOutputCharset($str), 1, "C", false, 1, '', '', true, 0, false, false, $this->h_ligne, 'M');
			}

			for ($i = 0; $i < $this->maxSlot; $i++) {
				$this->pdf->Rect($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2 + $this->trainee_widthtimeslot * $i, $posY, $this->trainee_widthtimeslot, $this->h_ligne);
			}

			$posY = $this->pdf->GetY();
			$this->pdf->SetY($posY);
		}
	}

	public function showSignatureBloc(&$pdf, $params = array())
	{
		global $conf;

		list($posX, $posY) = $params;
		$posX = $this->pdf->GetX() +2;
		$posY = $this->pdf->GetY() +5;

		$this->_resetColorsAndStyle();

		// Cachet et signature
		$posY += 2;
		$posX -= 2;
		$this->pdf->SetXY($posX, $posY);
		$str = $this->outputlangs->transnoentities('AgfPDFFichePres20');
		$this->pdf->Cell(50, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

		$this->pdf->SetXY($posX + 55, $posY);
		$str = $this->outputlangs->transnoentities('AgfPDFFichePres21') . dol_print_date($this->session->datef);
		$this->pdf->Cell(20, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

		$this->pdf->SetXY($posX + 92, $posY);
		$str = $this->outputlangs->transnoentities('AgfPDFFichePres22');
		$this->pdf->Cell(50, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

		$posY = $this->pdf->GetY();

		// Incrustation image tampon
		if ($conf->global->AGF_INFO_TAMPON) {
			$dir = $conf->agefodd->dir_output . '/images/';
			$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
			if (file_exists($img_tampon))
				$this->pdf->Image($img_tampon, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 50, $posY, 50);
		}
	}

	/**
	 * @param TCPDF $pdf pdf
	 * @param Translate $outputlangs outputlangs
	 * @return void
	 */
	function _pagehead_custom(&$pdf, $outputlangs, $agf, $lines_array, $noTrainer = 0)
	{
		global $conf, $mysoc;

		$outputlangs->load("main");

		// Fill header with background color
		$pdf->SetFillColor($this->colorheaderBg[0], $this->colorheaderBg[1], $this->colorheaderBg[2]);
		$pdf->MultiCell($this->page_largeur, 40, '', 0, 'L', true, 1, 0, 0);

		pdf_pagehead($pdf, $outputlangs, $pdf->page_hauteur);

		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
		$pdf->SetTextColor($this->colorheaderText[0], $this->colorheaderText[1], $this->colorheaderText[2]);

		$posY = $this->marge_haute;
		$posX = $this->page_largeur - $this->marge_droite - 55;

		// Logo
		$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
		if ($this->emetteur->logo) {
			if (is_readable($logo)) {
				$height = pdf_getHeightForLogo($logo);
				$width_logo = pdf_getWidthForLogo($logo);
				if ($width_logo > 0) {
					$posX = $this->page_largeur - $this->marge_droite - $width_logo;
				} else {
					$posX = $this->page_largeur - $this->marge_droite - 55;
				}
				$pdf->Image($logo, $posX, $posY, 0, $height);
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $this->default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$text = $this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}
		$posY = $this->marge_haute;
		$posX = $this->marge_gauche;

		$hautcadre = 30;
		$pdf->SetXY($posX, $posY);
		$pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);

		// Show sender name
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont('', 'B', $this->default_font_size - 2);
		$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
		$posY = $pdf->GetY();

		// Show sender information
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont('', '', $this->default_font_size - 3);
		$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
		$posY = $pdf->GetY();
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont('', '', $this->default_font_size - 3);
		$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->zip . ' ' . $this->emetteur->town), 0, 'L');
		$posY = $pdf->GetY();
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont('', '', $this->default_font_size - 3);
		$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
		$posY = $pdf->GetY();
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont('', '', $this->default_font_size - 3);
		$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');
		$posY = $pdf->GetY();

		printRefIntForma($this->db, $outputlangs, $agf, $this->default_font_size - 3, $pdf, $posX, $posY, 'L');

		// Affichage du logo commanditaire (optionnel)
		if ($conf->global->AGF_USE_LOGO_CLIENT) {
			$staticsoc = new Societe($this->db);
			$staticsoc->fetch($agf->socid);
			$dir = $conf->societe->multidir_output[$staticsoc->entity] . '/' . $staticsoc->id . '/logos/';
			if (!empty($staticsoc->logo)) {
				$logo_client = $dir . $staticsoc->logo;
				if (file_exists($logo_client) && is_readable($logo_client))
					$pdf->Image($logo_client, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 30, $this->marge_haute, 40);
			}
		}

		$posY = $pdf->GetY() + 10;
		if ($conf->global->AGF_PRINT_INTERNAL_REF_ON_PDF)
			$posY -= 4;

		$pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
		$pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);

		$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);

		// Mise en page de la baseline
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 18);
		$str = $outputlangs->transnoentities($mysoc->url);
		$width = $pdf->GetStringWidth($str);

		// alignement du bord droit du container avec le haut de la page
		$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $width;
		$baseline_x = 8;
		$baseline_y = $this->espaceV_dispo - $baseline_ecart + 30;
		$pdf->SetXY($baseline_x, $baseline_y);

		/*
		 * Corps de page
		 */
		$posX = $this->marge_gauche;
		$posY = $posY + $this->header_vertical_margin;

		// Titre
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 18);
		$pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
		$str = $outputlangs->transnoentities('AgfPDFFichePres1');
		$pdf->Cell(0, 6, $outputlangs->convToOutputCharset($str), 0, 2, "C", 0);
		$posY += 6 + 4;

		// Intro
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
		$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
		$str = $outputlangs->transnoentities('AgfPDFFichePres2') . ' « ' . $mysoc->name . ' », ' . $outputlangs->transnoentities('AgfPDFFichePres3') . ' ';
		$str .= str_replace(array('<br>', '<br />', "\n", "\r"), array(' ', ' ', ' ', ' '), $mysoc->address) . ' ';
		$str .= $mysoc->zip . ' ' . $mysoc->town;
		$str .= $outputlangs->transnoentities('AgfPDFFichePres4') . ' ' . $conf->global->AGF_ORGANISME_REPRESENTANT . ",\n";
		$str .= $outputlangs->transnoentities('AgfPDFFichePres5');
		$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($str), 0, 'C');
		$posY = $pdf->GetY() + 1;

		/**
		 * *** Bloc formation ****
		 */
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), 'BI', 9);
		$str = $outputlangs->transnoentities('AgfPDFFichePres23');
		$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
		$posY += 4;

		$cadre_tableau = array(
			$posX,
			$posY
		);

		$posX += 2;
		$posY += 2;
		$posYintitule = $posY;

		$haut_col2 = 0;
		$haut_col4 = 0;

		// Intitulé
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
		$str = $outputlangs->transnoentities('AgfPDFFichePres6');
		$pdf->Cell($this->formation_widthcol1, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

		$pdf->SetXY($posX + $this->formation_widthcol1, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 9);

		if (empty($agf->intitule_custo)) {
			$str = '« ' . $agf->formintitule . ' »';
		} else {
			$str = '« ' . $agf->intitule_custo . ' »';
		}
		$pdf->MultiCell($this->formation_widthcol2, 4, $outputlangs->convToOutputCharset($str), 0, 'L');

		$posY = $pdf->GetY() + 2;

		// Période
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
		$str = $outputlangs->transnoentities('AgfPDFFichePres7');
		$pdf->Cell($this->formation_widthcol1, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

		$str = $agf->libSessionDate('daytext');

		$str .= ' (' . $agf->duree_session . ' h)';

		$pdf->SetXY($posX + $this->formation_widthcol1, $posY);
		$pdf->MultiCell($this->formation_widthcol2, 4, $outputlangs->convToOutputCharset($str), 0, 'L');
		$hauteur = dol_nboflines_bis($str, 50) * 4;
		$haut_col2 += $hauteur + 2;

		// Lieu
		$pdf->SetXY($posX + $this->formation_widthcol1 + $this->formation_widthcol2, $posYintitule);
		$str = $outputlangs->transnoentities('AgfPDFFichePres11');
		$pdf->Cell($this->formation_widthcol3, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

		$agf_place = new Agefodd_place($this->db);
		$resql = $agf_place->fetch($agf->placeid);

		$pdf->SetXY($posX + $this->formation_widthcol1 + $this->formation_widthcol2 + $this->formation_widthcol3, $posYintitule);
		$str = $agf_place->ref_interne . "\n" . $agf_place->adresse . "\n" . $agf_place->cp . " " . $agf_place->ville;
		$pdf->MultiCell($this->formation_widthcol4, 4, $outputlangs->convToOutputCharset($str), 0, 'L');
		$hauteur = dol_nboflines_bis($str, 50) * 4;
		$posY += $hauteur + 3;
		$haut_col4 += $hauteur + 7;

		// Cadre
		($haut_col4 > $haut_col2) ? $haut_table = $haut_col4 : $haut_table = $haut_col2;
		$pdf->Rect($cadre_tableau[0], $cadre_tableau[1], $this->espaceH_dispo, $haut_table);

		$posY = $pdf->GetY();
		$this->heightForHeader = $posY;
		return array($posY, $posX);
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
	 * A convenient method for PDF pagebreak
	 *
	 * @param 	TCPDF 	$pdf TCPDF object, this is also passed as first parameter of $callback function
	 * @param 	callable $callback a  callable callback function
	 * @param 	bool 	$autoPageBreak enable page jump
	 * @param 	array 	$param this is passed to seccond parametter of $callback function
	 * @return 	float 	Y position
	 */
	public function pdfPrintCallback(&$pdf, callable $callback, $autoPageBreak = true, $param = array())
	{
		global $conf, $outputlangs;

		$posY = $posYBefore = $pdf->GetY();

		if (is_callable($callback))
		{
			$pdf->startTransaction();
			$pageposBefore=$pdf->getPage();

			// START FIRST TRY
			$res = call_user_func_array($callback, array(&$pdf, $param));
			$pageposAfter=$pdf->getPage();
			$posY = $posYAfter = $pdf->GetY();
			// END FIRST TRY

			if($autoPageBreak && ($pageposAfter > $pageposBefore || ($pageposAfter == $pageposBefore && $posYAfter > ($this->page_hauteur - $this->heightForFooter))) )
			{
				$pagenb = $pageposBefore;
				$pdf->rollbackTransaction(true);
				$posY = $posYBefore;
				// prepare pages to receive content
				while ($pagenb < $pageposAfter) {
					$pdf->AddPage();
					$pagenb++;
					$this->prepareNewPage($pdf);
				}

				// BACK TO START
//				if ($pageposAfter == $pageposBefore && $posYAfter > ($this->page_hauteur - $this->heightForFooter)) {
//					$this->_pagefoot($pdf, $this->session, $outputlangs);
//					$pdf->AddPage();
//					$this->prepareNewPage($pdf);
//					$pageposAfter++;
//				}
				$pdf->setPage($pageposAfter);
				$pdf->SetY($this->heightForHeader);

				// RESTART DISPLAY BLOCK - without auto page break
				$posY = $this->pdfPrintCallback($pdf, $callback, false, $param);
			}
			else // No pagebreak
			{
				$pdf->commitTransaction();
			}
		}

		return $posY;
	}

	/**
	 * Prepare new page with header, footer, margin ...
	 * @param TCPDF $pdf
	 * @return float Y position
	 */
	public function prepareNewPage(&$pdf, $forceHead = false)
	{
		global $conf, $outputlangs, $lines_array;

		// Set path to the background PDF File
		if (! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
		{
			$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
			$tplidx = $pdf->importPage(1);
		}

		if (! empty($tplidx)) $pdf->useTemplate($tplidx);

		if ($forceHead || empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead_custom($pdf, $outputlangs, $this->session, $lines_array, 1);

		$topY = $pdf->GetY() + 20;
		$pdf->SetMargins($this->marge_gauche, $topY, $this->marge_droite); // Left, Top, Right

		$pdf->setPageOrientation('', 0, 0);
		$pdf->SetAutoPageBreak(0, 0); // to prevent footer creating page
		$footerheight = $this->_pagefoot_custom($pdf,$this->object, $outputlangs);
		$pdf->SetAutoPageBreak(1, $footerheight);

		// The only function to edit the bottom margin of current page to set it.
		$pdf->setPageOrientation('', 1, $footerheight);

		$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
		$pdf->SetY($tab_top_newpage);
		return empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10;
	}

	/**
	 * @param TCPDF $pdf pdf
	 * @param Object $object object
	 * @param Translate $outputlangs outputlangs
	 * @return int int
	 */
	function _pagefoot_custom(&$pdf, $object, $outputlangs)
	{
		$pdf->SetDrawColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		$pdf->SetTextColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		return pdf_agfpagefoot($pdf, $outputlangs, '', $this->emetteur, $this->marge_basse +5, $this->marge_gauche, $this->page_hauteur, $object, 1, 0);
	}
}
