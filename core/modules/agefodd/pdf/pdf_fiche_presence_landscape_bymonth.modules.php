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

		foreach ($this->datesByMonth as $monthYear => $TTSessionDate) {
			foreach ($TTSessionDate as $dates_array) {
				// New page
				$this->pdf->AddPage();
				//$this->heightForHeader = $this->prepareNewPage($this->pdf, true, $dates_array);
				//$posY = $this->heightForHeader;

				$this->maxSlot = count($dates_array);
				$this->trainer_widthtimeslot = ($this->espaceH_dispo - $this->trainer_widthcol1 -2) / $this->maxSlot;
				$this->trainee_widthtimeslot = ($this->espaceH_dispo - $this->trainee_widthcol1 -2) / $this->maxSlot;

				list($posX, $posY) = $this->_pagehead($agf, 1, $this->outputlangs);

				$this->setSummaryTime($dates_array);
				list($posX, $posY) = $this->printSessionSummary($posX, $posY);

				list($posX, $posY) = $this->showTrainerBloc(array($this->marge_gauche, $posY, $dates_array));
				list($posX, $posY) = $this->showTraineeBloc(array($this->marge_gauche, $posY, $dates_array));

				// Pied de page
				$this->_pagefoot($this->pdf->ref_object, $this->outputlangs);
				if (method_exists($this->pdf, 'AliasNbPages')) {
					$this->pdf->AliasNbPages();
				}
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
			list($posX, $posY) = $this->printTrainerBlockHeader($posX, $posY, $dates_array);
			list($posX, $posY) = $this->printTrainerBlockLines($posX, $posY, $dates_array, $this->session);
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
			list($posX, $posY) = $this->printTraineeBlockHeader($posX, $posY, $dates_array);
			list($posX, $posY) = $this->printTraineeBlockLines($posX, $posY, $dates_array, $agf);
		}

		return array($posX, $posY);

	}

	public function printSignatureBloc($posX, $posY)
	{
		global $conf;

		$posX+= 2;

//		$this->_resetColorsAndStyle();

		// Cachet et signature
		if (empty($conf->global->AGF_HIDE_CACHET_FICHEPRES)) {
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

		}
		$posY = $this->pdf->GetY() - 2;

		// Incrustation image tampon
		if ($conf->global->AGF_INFO_TAMPON) {
			$dir = $conf->agefodd->dir_output . '/images/';
			$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
			if (file_exists($img_tampon))
			{
				$imgHeight = pdf_getHeightForLogo($img_tampon);
				$this->pdf->Image($img_tampon, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 50, $posY, 50);
				$posY+=$imgHeight;
			}
		}

		return array($posX, $posY);
	}

	/**
	 * @param TCPDF $pdf pdf
	 * @param Translate $outputlangs outputlangs
	 * @return void
	 */
//	function _pagehead($agf,  $dummy = 1, $this->outputlangs, $dates_array, $noTrainer = 0)
//	{
//		global $conf, $mysoc;
//
//		$this->outputlangs->load("main");
//
//		// Fill header with background color
//		$this->pdf->SetFillColor($this->colorheaderBg[0], $this->colorheaderBg[1], $this->colorheaderBg[2]);
//		$this->pdf->MultiCell($this->page_largeur, 40, '', 0, 'L', true, 1, 0, 0);
//
//		pdf_pagehead($this->pdf, $this->outputlangs, $this->pdf->page_hauteur);
//
//		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
//		$this->pdf->SetTextColor($this->colorheaderText[0], $this->colorheaderText[1], $this->colorheaderText[2]);
//
//		$posY = $this->marge_haute;
//		$posX = $this->page_largeur - $this->marge_droite - 55;
//
//		// Logo
//		$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
//		if ($this->emetteur->logo) {
//			if (is_readable($logo)) {
//				$height = pdf_getHeightForLogo($logo);
//				$width_logo = pdf_getWidthForLogo($logo);
//				if ($width_logo > 0) {
//					$posX = $this->page_largeur - $this->marge_droite - $width_logo;
//				} else {
//					$posX = $this->page_largeur - $this->marge_droite - 55;
//				}
//				$this->pdf->Image($logo, $posX, $posY, 0, $height);
//			} else {
//				$this->pdf->SetTextColor(200, 0, 0);
//				$this->pdf->SetFont('', 'B', $this->default_font_size - 2);
//				$this->pdf->MultiCell(100, 3, $this->outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
//				$this->pdf->MultiCell(100, 3, $this->outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
//			}
//		} else {
//			$text = $this->emetteur->name;
//			$this->pdf->MultiCell(100, 4, $this->outputlangs->convToOutputCharset($text), 0, 'L');
//		}
//		$posY = $this->marge_haute;
//		$posX = $this->marge_gauche;
//
//		$hautcadre = 30;
//		$this->pdf->SetXY($posX, $posY);
//		$this->pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);
//
//		// Show sender name
//		$this->pdf->SetXY($posX, $posY);
//		$this->pdf->SetFont('', 'B', $this->default_font_size - 2);
//		$this->pdf->MultiCell(80, 4, $this->outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
//		$posY = $this->pdf->GetY();
//
//		// Show sender information
//		$this->pdf->SetXY($posX, $posY);
//		$this->pdf->SetFont('', '', $this->default_font_size - 3);
//		$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
//		$posY = $this->pdf->GetY();
//		$this->pdf->SetXY($posX, $posY);
//		$this->pdf->SetFont('', '', $this->default_font_size - 3);
//		$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->zip . ' ' . $this->emetteur->town), 0, 'L');
//		$posY = $this->pdf->GetY();
//		$this->pdf->SetXY($posX, $posY);
//		$this->pdf->SetFont('', '', $this->default_font_size - 3);
//		$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
//		$posY = $this->pdf->GetY();
//		$this->pdf->SetXY($posX, $posY);
//		$this->pdf->SetFont('', '', $this->default_font_size - 3);
//		$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');
//		$posY = $this->pdf->GetY();
//
//		printRefIntForma($this->db, $this->outputlangs, $agf, $this->default_font_size - 3, $this->pdf, $posX, $posY, 'L');
//
//		// Affichage du logo commanditaire (optionnel)
//		if ($conf->global->AGF_USE_LOGO_CLIENT) {
//			$staticsoc = new Societe($this->db);
//			$staticsoc->fetch($agf->socid);
//			$dir = $conf->societe->multidir_output[$staticsoc->entity] . '/' . $staticsoc->id . '/logos/';
//			if (!empty($staticsoc->logo)) {
//				$logo_client = $dir . $staticsoc->logo;
//				if (file_exists($logo_client) && is_readable($logo_client))
//					$this->pdf->Image($logo_client, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 30, $this->marge_haute, 40);
//			}
//		}
//
//		$posY = $this->pdf->GetY() + 10;
//		if ($conf->global->AGF_PRINT_INTERNAL_REF_ON_PDF)
//			$posY -= 4;
//
//		$this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
//		$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
//
//		$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
//
//		// Mise en page de la baseline
//		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 18);
//		$str = $this->outputlangs->transnoentities($mysoc->url);
//		$width = $this->pdf->GetStringWidth($str);
//
//		// alignement du bord droit du container avec le haut de la page
//		$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $width;
//		$baseline_x = 8;
//		$baseline_y = $this->espaceV_dispo - $baseline_ecart + 30;
//		$this->pdf->SetXY($baseline_x, $baseline_y);
//
//		/*
//		 * Corps de page
//		 */
//		$posX = $this->marge_gauche;
//		$posY = $posY + $this->header_vertical_margin;
//
//		// Titre
//		$this->pdf->SetXY($posX, $posY);
//		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 18);
//		$this->pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
//		$str = $this->outputlangs->transnoentities('AgfPDFFichePres1');
//		$this->pdf->Cell(0, 6, $this->outputlangs->convToOutputCharset($str), 0, 2, "C", 0);
//		$posY += 6 + 4;
//
//		// Intro
//		$this->pdf->SetXY($posX, $posY);
//		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 8);
//		$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
//		$str = $this->outputlangs->transnoentities('AgfPDFFichePres2') . ' « ' . $mysoc->name . ' »,' . $this->outputlangs->transnoentities('AgfPDFFichePres3') . ' ';
//		$str .= str_replace(array('<br>', '<br />', "\n", "\r"), array(' ', ' ', ' ', ' '), $mysoc->address) . ' ';
//		$str .= $mysoc->zip . ' ' . $mysoc->town;
//		$str .= $this->outputlangs->transnoentities('AgfPDFFichePres4') . ' ' . $conf->global->AGF_ORGANISME_REPRESENTANT . ",\n";
//		$str .= $this->outputlangs->transnoentities('AgfPDFFichePres5');
//		$this->pdf->MultiCell(0, 0, $this->outputlangs->convToOutputCharset($str), 0, 'C');
//		$posY = $this->pdf->GetY() + 1;
//
//		/**
//		 * *** Bloc formation ****
//		 */
//		$this->pdf->SetXY($posX, $posY);
//		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'BI', 9);
//		$str = $this->outputlangs->transnoentities('AgfPDFFichePres23');
//		$this->pdf->Cell(0, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
//		$posY += 4;
//
//		$cadre_tableau = array(
//			$posX,
//			$posY
//		);
//
//		$posX += 2;
//		$posY += 2;
//		$posYintitule = $posY;
//
//		$haut_col2 = 0;
//		$haut_col4 = 0;
//
//		// Intitulé
//		$this->pdf->SetXY($posX, $posY);
//		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
//		$str = $this->outputlangs->transnoentities('AgfPDFFichePres6');
//		$this->pdf->Cell($this->formation_widthcol1, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
//
//		$this->pdf->SetXY($posX + $this->formation_widthcol1, $posY);
//		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 9);
//
//		if (empty($agf->intitule_custo)) {
//			$str = '« ' . $agf->formintitule . ' »';
//		} else {
//			$str = '« ' . $agf->intitule_custo . ' »';
//		}
//		$this->pdf->MultiCell($this->formation_widthcol2, 4, $this->outputlangs->convToOutputCharset($str), 0, 'L');
//
//		$posY = $this->pdf->GetY() + 2;
//
//		// Période
//		$this->pdf->SetXY($posX, $posY);
//		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
//		$str = $this->outputlangs->transnoentities('AgfPDFFichePres7');
//		$this->pdf->Cell($this->formation_widthcol1, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
//
//		$str = $agf->libSessionDate('daytext');
//
//		$str .= ' (' . $agf->duree_session . ' h)';
//
//		$this->pdf->SetXY($posX + $this->formation_widthcol1, $posY);
//		$this->pdf->MultiCell($this->formation_widthcol2, 4, $this->outputlangs->convToOutputCharset($str), 0, 'L');
//		$hauteur = dol_nboflines_bis($str, 50) * 4;
//		$haut_col2 += $hauteur + 2;
//
//        // Session
//        $posY = $this->pdf->GetY() + 2;
//        $this->pdf->SetXY($posX, $posY);
//        $this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
//        $str = $this->outputlangs->transnoentities('Session');
//        $this->pdf->Cell($this->formation_widthcol1, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
//
//        $this->pdf->ref_object = $agf;
//        $str = $this->pdf->ref_object->id . '#' . $this->pdf->ref_object->ref;
//
//        $this->pdf->SetXY($posX + $this->formation_widthcol1, $posY);
//        $this->pdf->MultiCell($this->formation_widthcol2, 4, $this->outputlangs->convToOutputCharset($str), 0, 'L');
//        $hauteur = dol_nboflines_bis($str, 50) * 4;
//        $haut_col2 += $hauteur + 2;
//
//		// Lieu
//        $posY_col4 = $posYintitule;
//
//        $this->pdf->SetXY($posX + $this->formation_widthcol1 + $this->formation_widthcol2, $posYintitule);
//		$str = $this->outputlangs->transnoentities('AgfPDFFichePres11');
//		$this->pdf->Cell($this->formation_widthcol3, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
//
//		$agf_place = new Agefodd_place($this->db);
//		$resql = $agf_place->fetch($agf->placeid);
//
//		$this->pdf->SetXY($posX + $this->formation_widthcol1 + $this->formation_widthcol2 + $this->formation_widthcol3, $posYintitule);
//		$str = $agf_place->ref_interne . "\n" . $agf_place->adresse . "\n" . $agf_place->cp . " " . $agf_place->ville;
//		$this->pdf->MultiCell($this->formation_widthcol4, 4, $this->outputlangs->convToOutputCharset($str), 0, 'L');
//		$hauteur = dol_nboflines_bis($str, 50) * 4;
//		$posY += $hauteur + 3;
//		$haut_col4 += $hauteur + 7;
//        $posY_col4 += $hauteur;
//
//        //Total heures des créneaux
//        if(!empty($dates_array))
//        {
//            $this->pdf->SetXY($posX + $this->formation_widthcol1 + $this->formation_widthcol2, $posY_col4);
//            $str = $this->outputlangs->transnoentities('Nombre heures');
//            $this->pdf->Cell($this->formation_widthcol3, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
//
//            //calcul
//            $totalSecondsSessCalendar = 0;
//            foreach ($dates_array as $sess_calendar){
//                $totalSecondsSessCalendar += $sess_calendar->getTime();
//            }
//            $totalSessCalendarHours = intval($totalSecondsSessCalendar / 3600);
//            $totalSessCalendarMin = ($totalSecondsSessCalendar - (3600 * $totalSessCalendarHours)) / 60;
//
//            $this->pdf->SetXY($posX + $this->formation_widthcol1  + $this->formation_widthcol2 + $this->formation_widthcol3, $posY_col4);
//            $str = str_pad($totalSessCalendarHours, 2, 0, STR_PAD_LEFT).':'.str_pad($totalSessCalendarMin, 2, 0, STR_PAD_LEFT);
//            $this->pdf->MultiCell($this->formation_widthcol4, 4, $this->outputlangs->convToOutputCharset($str), 0, 'L');
//            $hauteur = dol_nboflines_bis($str, 50) * 4;
//            $haut_col4 += $hauteur;
//            $posY_col4 += $hauteur;
//        }
//
//		// Cadre
//		($haut_col4 > $haut_col2) ? $haut_table = $haut_col4 : $haut_table = $haut_col2;
//		$this->pdf->Rect($cadre_tableau[0], $cadre_tableau[1], $this->espaceH_dispo, $haut_table);
//
//		$posY = $this->pdf->GetY();
//		$this->heightForHeader = $posY;
//		return array($posY, $posX);
//	}

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

		$posY = $posYBefore = $this->pdf->GetY();

		if (is_callable($callback))
		{
			$this->pdf->startTransaction();
			$pageposBefore=$this->pdf->getPage();

			// START FIRST TRY
			$res = call_user_func_array($callback, array(&$this->pdf, $param));
			$pageposAfter=$this->pdf->getPage();
			$posY = $posYAfter = $this->pdf->GetY();
			// END FIRST TRY

			if($autoPageBreak && ($pageposAfter > $pageposBefore || ($pageposAfter == $pageposBefore && $posYAfter > ($this->page_hauteur - $this->heightForFooter))) )
			{
				$pagenb = $pageposBefore;
				$this->pdf->rollbackTransaction(true);
				$posY = $posYBefore;
				// prepare pages to receive content
				while ($pagenb < $pageposAfter) {
					$this->pdf->AddPage();
					$pagenb++;
					$this->heightForHeader = $this->prepareNewPage($this->pdf);
				}

				// BACK TO START
//				if ($pageposAfter == $pageposBefore && $posYAfter > ($this->page_hauteur - $this->heightForFooter)) {
//					$this->_pagefoot($this->pdf, $this->session, $outputlangs);
//					$this->pdf->AddPage();
//					$this->prepareNewPage($this->pdf);
//					$pageposAfter++;
//				}
				$this->pdf->setPage($pageposAfter);
				$this->pdf->SetY($this->heightForHeader);

				// RESTART DISPLAY BLOCK - without auto page break
				$posY = $this->pdfPrintCallback($this->pdf, $callback, false, $param);
			}
			else // No pagebreak
			{
				$this->pdf->commitTransaction();
			}
		}

		return $posY;
	}

	/**
	 * Prepare new page with header, footer, margin ...
	 * @param TCPDF $pdf
	 * @return float Y position
	 */
	public function prepareNewPage(&$pdf, $forceHead = false, $dates_array = '')
	{
		global $conf, $outputlangs;

		// Set path to the background PDF File
		if (! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
		{
			$pagecount = $this->pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
			$tplidx = $this->pdf->importPage(1);
		}

		if (! empty($tplidx)) $this->pdf->useTemplate($tplidx);

		if ($forceHead || empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD))
		{
			list($posX, $posY) = $this->_pagehead($this->session, 1, $outputlangs,  $dates_array, 1);
			$this->setSummaryTime($dates_array);
			list($posX, $posY) = $this->printSessionSummary($posX, $posY);
			$this->pdf->SetY($posY);
		}

		$topY = $this->pdf->GetY() + 20;
		$this->pdf->SetMargins($this->marge_gauche, $topY, $this->marge_droite); // Left, Top, Right

		$this->pdf->setPageOrientation('', 0, 0);
		$this->pdf->SetAutoPageBreak(0, 0); // to prevent footer creating page
		$footerheight = $this->_pagefoot($this->pdf,$this->object, $outputlangs);
		$this->pdf->SetAutoPageBreak(1, $footerheight);

		// The only function to edit the bottom margin of current page to set it.
		$this->pdf->setPageOrientation('', 1, $footerheight);

		$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
		$this->pdf->SetY($tab_top_newpage);
		return empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10;
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
