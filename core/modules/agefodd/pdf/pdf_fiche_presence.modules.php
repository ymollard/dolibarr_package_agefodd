<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2012-2016 Florian Henry <florian.henry@open-concept.pro>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file agefodd/core/modules/agefodd/pdf/pdf_fiche_presence.modules.php
 * \ingroup agefodd
 * \brief PDF for attendees sheet
 */
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_convention.class.php');
dol_include_once('/agefodd/class/agefodd_place.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
dol_include_once('/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/core/lib/company.lib.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');

/**
 * Class pdf_fiche_presence
 *
 * Modèle PDF des feuilles d'émargement (fiches de présence) Agefodd.
 *
 * Cette classe est étendue par d'autres, notamment :
 *  - pdf_fiche_presence_landscape
 *  - pdf_fiche_presence_societe
 *  - pdf_fiche_presence_landscape_bymonth
 *
 */
class pdf_fiche_presence extends ModelePDFAgefodd
{
	var $emetteur; // Objet societe qui emet

	// Definition des couleurs utilisées de façon globales dans le document (charte)
	protected $colorfooter;
	protected $colortext;
	protected $colorhead;
	protected $colorheaderBg;
	protected $colorheaderText;
	protected $colorLine;
	/** @var TCPDF $pdf */
	protected $pdf;

	protected $h_ligne;
	protected $totalSecondsSessCalendar;
	/** @var Agsession $ref_object */
	protected $ref_object;
	/** @var Agsession $agf */
	protected $agf;

	protected $tplidx;
	/** @var Agefodd_sesscalendar $agf_date */
	protected $agf_date;
	/** @var Agefodd_opca[] $TOpco (associatif: la clé est le no. de dossier) */
	protected $TOpco;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	function __construct($db)
	{
		global $conf, $langs, $mysoc;

		$this->db = $db;
		$this->name = "fiche_presence";
		$this->description = $langs->trans('AgfModPDFFichePres');

		// Dimension page pour format A4 en paysage
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width']; // use standard but reverse width and height to get Landscape format
		$this->page_hauteur = $formatarray['height']; // use standard but reverse width and height to get Landscape format
		$this->format = array(
			$this->page_largeur,
			$this->page_hauteur
		);
		$this->marge_gauche = 10;
		$this->marge_droite = 10;
		$this->marge_haute = 10;
		$this->marge_basse = 10;
		$this->unit = 'mm';
		$this->oriantation = 'P';
		$this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
		$this->milieu = $this->espaceH_dispo / 2;
		$this->espaceV_dispo = $this->page_hauteur - ($this->marge_haute + $this->marge_basse);
		$this->default_font_size = 12;

		$this->colorfooter = agf_hex2rgb($conf->global->AGF_FOOT_COLOR);
		$this->colortext = agf_hex2rgb($conf->global->AGF_TEXT_COLOR);
		$this->colorhead = agf_hex2rgb($conf->global->AGF_HEAD_COLOR);
		$this->colorheaderBg = agf_hex2rgb($conf->global->AGF_HEADER_COLOR_BG);
		$this->colorheaderText = agf_hex2rgb($conf->global->AGF_HEADER_COLOR_TEXT);
		$this->colorLine = agf_hex2rgb($conf->global->AGF_COLOR_LINE);

		// Get source company
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code)
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined

		$this->header_vertical_margin = 3;
		$this->summaryPaddingBottom = 3;

		// nombre maximum de colonnes de dates de sessions qu'on fera tenir dans la largeur de la page.
		$this->nbtimeslots = 6;

		// "taquets" d'alignement des textes pour l'encadré "La formation"
		$this->formation_widthcol1 = 20; // titres "Intitulé", "Période", "Session"
		$this->formation_widthcol2 = 80; // valeurs pour intitulé, période, session
		$this->formation_widthcol3 = 27; // titre "Lieu de formation"
		$this->formation_widthcol4 = 65; // adresse du lieu de formation

		// largeur page = 210, les marges font 20 donc largeur utile = 190
		$page_largeur_utile = $this->page_largeur - $this->marge_gauche - $this->marge_droite;

		$this->trainer_widthcol1 = 44; // noms des formateurs

		// colonnes des dates des formateurs
		$this->trainer_widthtimeslot = ($page_largeur_utile - $this->trainer_widthcol1) / $this->nbtimeslots;

		$this->trainee_widthcol1 = 40; // noms des stagiaires
		$this->trainee_widthcol2 = 40; // sociétés des stagiaires
		if (!empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
			$this->trainee_widthcol2 = 0;
		}
		// colonnes des dates des stagiaires
		$this->trainee_widthtimeslot = ($page_largeur_utile - $this->trainee_widthcol1 - $this->trainee_widthcol2) / $this->nbtimeslots; // 110 = 190 - 2*40 car la colonne 2 est affichée

		$this->height_for_footer = isset($conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER) ? $conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER : 20;

		$this->h_ligne = 10;
		$this->totalSecondsSessCalendar = 0;
	}

	/**
	 * @param Agsession $agf Session
	 * @param Translate $outputlangs $outputlangs
	 * @param string    $file file
	 * @param int       $socid socid
	 * @param string    $courrier (deprecated?)
	 * @return int
	 */
	function write_file($agf, $outputlangs, $file, $socid, $courrier = '')
	{
		global $user, $langs, $conf, $hookmanager;

		$this->outputlangs = $outputlangs;

		if (!is_object($this->outputlangs))
			$this->outputlangs = $langs;

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

		// Definition of $dir and $file
		$dir = $conf->agefodd->dir_output;
		$file = $dir . '/' . $file;

		if (!file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		}

		if (file_exists($dir)) {
			$this->pdf = pdf_getInstance($this->format, $this->unit, $this->orientation);
			$this->ref_object=$agf;
			$this->pdf->ref_object = $agf;

			if (class_exists('TCPDF')) {
				$this->pdf->setPrintHeader(false);
				$this->pdf->setPrintFooter(false);
			}

			if (!empty($conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER))
				$this->height_for_footer = $conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER;

			$realFooterHeight = $this->getRealHeightLine('foot');

			// if the footer is larger than expected, use its real height.
			$this->height_for_footer = max($this->height_for_footer, $realFooterHeight);

			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs));

			$this->pdf->Open();

			$this->pdf->SetTitle($this->outputlangs->convToOutputCharset($this->outputlangs->transnoentities('AgfPDFFichePres1') . " " . $this->pdf->ref_object->ref));
			$this->pdf->SetSubject($this->outputlangs->transnoentities("TrainneePresence"));
			$this->pdf->SetCreator("Dolibarr " . DOL_VERSION . ' (Agefodd module)');
			$this->pdf->SetAuthor($this->outputlangs->convToOutputCharset($user->fullname));
			$this->pdf->SetKeyWords($this->outputlangs->convToOutputCharset($this->pdf->ref_object->ref) . " " . $this->outputlangs->transnoentities("Document"));
			if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION)
				$this->pdf->SetCompression(false);

			$this->pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
			$this->pdf->SetAutoPageBreak(1, 0);

			// On recupere les infos societe
			$agf_soc = new Societe($this->db);
			$result = $agf_soc->fetch($socid);

			if ($result) {
				if ($this->_pagebody($agf, $this->outputlangs) < 0)
					return -1;
			}

			$this->pdf->Close();
			$this->pdf->Output($file, 'F');
			if (!empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));


			// Add pdfgeneration hook
			if (!is_object($hookmanager)) {
				include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
				$hookmanager = new HookManager($this->db);
			}
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters = array('file' => $file, 'object' => $agf, 'outputlangs' => $this->outputlangs);
			global $action;
			$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks


			return 1; // Pas d'erreur
		} else {
			$this->error = $langs->trans("ErrorConstantNotDefined", "AGF_OUTPUTDIR");
			return 0;
		}
	}

	/**
	 * Show body of page
	 * @param object $agf Object session
	 * @param Translate $outputlangs Object lang for output
	 * @return int <0 = KO;  >0 = OK
	 */
	function _pagebody($agf, $outputlangs)
	{
		global $conf, $mysoc;

		// Set path to the background PDF File
		if (empty($conf->global->MAIN_DISABLE_FPDI) && !empty($conf->global->AGF_ADD_PDF_BACKGROUND_P)) {
			$pagecount = $this->pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P);
			$tplidx = $this->pdf->importPage(1);
			$this->tplidx = $tplidx;
		}

		if (!empty($conf->multicompany->enabled)) {
			dol_include_once('/multicompany/class/dao_multicompany.class.php');
			$this->dao = new DaoMulticompany($this->db);
			$this->dao->getEntities();
		}

		$session_hours = array();
		$tmp_array = array();

		// récupération des créneaux de session
		$this->agf_date = new Agefodd_sesscalendar($this->db);
		$resql = $this->agf_date->fetch_all($this->pdf->ref_object->id);
		if ($resql < 0) {
			$this->errors[] = $this->agf_date->error;
			return -1;
		}
		if (is_array($this->agf_date->lines) && count($this->agf_date->lines) > $this->nbtimeslots) {
			for ($i = 0; $i < count($this->agf_date->lines); $i++) {
				$tmp_array[] = $this->agf_date->lines[$i];
				if (count($tmp_array) >= $this->nbtimeslots || $i == count($this->agf_date->lines) - 1) {
					$session_hours[] = $tmp_array;
					$tmp_array = array();
				}
			}
		} else {
			$session_hours[] = $this->agf_date->lines;
		}

		// récupération des formateurs de la session
		$this->formateurs = new Agefodd_session_formateur($this->db);
		$nbform = $this->formateurs->fetch_formateur_per_session($this->pdf->ref_object->id);
		if ($nbform < 0) {
			$this->errors[] = $outputlangs->trans('AgfErrorUnableToFetchTrainer');
			return -1;
		}

		// récupération des stagiaires de la session
		$this->stagiaires = new Agefodd_session_stagiaire($this->db);
		$resfetch = $this->stagiaires->fetch_stagiaire_per_session($this->pdf->ref_object->id);
		if ($resfetch < 0) {
			$this->errors[] = $outputlangs->trans('AgfErrorUnableToFetchTrainees');
			return -1;
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

		// nom de l'entité
		if (is_object($this->dao) && $conf->global->AGF_ADD_ENTITYNAME_FICHEPRES) {
			$this->h_ligne = $this->h_ligne + 3;
		}

		// date d'anniversaire du stagiaire
		if (!empty($conf->global->AGF_ADD_DTBIRTH_FICHEPRES)) {
			$this->h_ligne = $this->h_ligne + 3;
		}

		$this->agf = $agf;
		foreach ($session_hours as $key => $dates_array) {
			// New page
			$this->pdf->AddPage();
			$this->setupNewPage();
			$posX = $this->marge_gauche;
			$this->pdf->SetX($this->marge_gauche);
			$posY = $this->pdf->GetY();

            if (!empty($conf->global->AGF_FICHEPRES_SHOW_TIME_FOR_PAGE)) $this->setSummaryTime($dates_array); // durée total des créneaux de la page
			$this->printSessionSummary($posX, $posY);
			foreach (array('formateurs', 'stagiaires') as $personType) {
				if (!empty($this->{$personType}->lines)) {
					$this->printPersonsBlock($personType, $this->{$personType}->lines, $dates_array);
				}
			}
		}
	}

	/**
	 * Affiche un bloc (formateur ou stagiaires) sur une ou plusieurs page(s)
	 *
	 * Un bloc peut être découpé sur plusieurs pages à condition que son en-tête ne soit
	 * pas seul en fin de bloc (un en-tête doit être suivi d’au moins une ligne sur la même page).
	 * @param string                                      $type  'stagiaires' ou 'formateurs'
	 * @param AgfTraineeSessionLine[]|AgfSessionTrainer[] $lines  stagiaires ou formateurs à afficher
	 * @param Agefodd_sesscalendar[]                      $dates_array dates à afficher en un seul bloc (il ne peut pas
	 *                                                             y en avoir plus que $this->nbtimeslots en une fois)
	 */
	function printPersonsBlock($type, $lines, $dates_array)
	{
		global $conf, $langs;
		$isNewPage = true;

		// exclusion de stagiaires de la fiche de présence en fonction de leur statut dans la session
		if ($type === 'stagiaires' && $conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES !== '') {
			$TStagiaireStatusToExclude = explode(',', $conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES);
			$includedLines = array();
			foreach ($lines as $line) {
				$status_stagiaire = (int) $line->status_in_session;
				if (in_array($status_stagiaire, $TStagiaireStatusToExclude)) {
				} else {
					$includedLines[] = $line;
				}
			}
			// on remplace
			$lines = $includedLines;
		}

		$lineN = 0;
		// ce while est un foreach "manuel" pour permettre de reboucler sur la *même* ligne en cas de rollback
		// (ça évite un GOTO)
		while ($line = current($lines)) {
			$isLastLine = $lineN === count($lines) - 1;

			// begin
			$pageBefore = $this->pdf->getPage();
			$this->pdf->startTransaction();

			if ($isNewPage) {
				$this->printHeader($type, $dates_array);
			}
			$this->printPersonLine($type, $line, $lineN, $dates_array);
			if ($isLastLine && $type == 'stagiaires') {
				$this->printSignatureBloc();
			}



			if ($this->pdf->getPage() == $pageBefore) {
				// commit et boucle sur la ligne suivante
				$this->pdf->commitTransaction();
				$isNewPage = false;
				next($lines);
				$lineN++;
			} else {
				// rollback et reboucle sur la même ligne (en ajoutant une nouvelle page)
				$this->pdf->rollbackTransaction(true);
				$this->pdf->AddPage();
				$this->pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
				$this->pdf->setPageOrientation($this->orientation, 1, $this->height_for_footer); // margin bottom
				$this->setupNewPage();
				$isNewPage = true;
			}
		}
	}

	/**
	 * Affiche une ligne (formateur ou stagiaire).
	 * Si la ligne est précédée (sur la même page) d'une ligne identique, on peut y aller.
	 * Si la ligne est précédée (sur la même page) d'une ligne d’en-tête, on peut y aller.
	 * Si la ligne est la dernière ligne du bloc stagiaires, elle doit être solidaire de la
	 * signature (la signature ne peut pas être toute seule sur une page).
	 *
	 * Sinon, ça veut dire que la ligne demandée est la première sur sa page, donc on la fait
	 * précéder d’une ligne d’en-tête puis on force son affichage.
	 *
	 * @param string                              $type
	 * @param Agefodd_stagiaire|AgfSessionTrainer $line
	 * @param Agefodd_sesscalendar[]              $dates_array
	 */
	function printPersonLine($type, $line, $n = 0, $dates_array = array())
	{
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$nbTimeSlots = count($dates_array);
		if ($type === 'formateurs') {
			$posY = $this->pdf->GetY();
			$timeSlotWidth = $this->trainer_widthtimeslot;

			$nbTimeSlots = $this->nbtimeslots;

			if (!empty($dates_array) && count($dates_array) < $this->nbtimeslots) {
				$nbTimeSlots = count($dates_array);
				$timeSlotWidth = ($this->espaceH_dispo - $this->trainer_widthcol1) / $nbTimeSlots;
			}
			// Cadre
			$this->pdf->Rect($this->pdf->GetX(), $this->pdf->GetY(), $this->espaceH_dispo, $this->h_ligne);

			// Nom
			$this->pdf->SetX($this->marge_gauche);

			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);
			$str = (!empty($line->lastname) ? strtoupper($line->lastname) : strtoupper($line->name)) . ' ' . ucfirst($line->firstname);
			$this->pdf->MultiCell($this->trainer_widthcol1, $this->h_ligne, $this->outputlangs->convToOutputCharset($str), 1, "L", false, 1, '', '', true, 0, false, false, $this->h_ligne, 'M');

			for ($i = 0; $i < $nbTimeSlots - 1; $i++) {
				$this->pdf->Rect($this->marge_gauche + $this->trainer_widthcol1 + $timeSlotWidth * $i, $posY, $timeSlotWidth, $this->h_ligne);
			}
		} elseif ($type === 'stagiaires') {
			$timeSlotWidth = $this->trainee_widthtimeslot;

			if (!empty($dates_array) && count($dates_array) < $this->nbtimeslots ) {
				$nbTimeSlots = count($dates_array);
				$timeSlotWidth = ($this->espaceH_dispo - $this->trainee_widthcol1 - (empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES) ? $this->trainee_widthcol2 : 0)) / $nbTimeSlots;
			}
			$this->printTraineeLine($line, $n, $nbTimeSlots, $timeSlotWidth);
		}
	}

	/**
	 * Affiche un en-tête de tableau (soit du tableau des formateurs, soit des stagiaires)
	 * @param string $type 'stagiaires' ou 'formateurs'
	 * @param Agefodd_sesscalendar[] $dates_array
	 */
	function printHeader($type, $dates_array)
	{
		$this->pdf->SetX($this->marge_gauche);
		$this->pdf->SetY($this->pdf->GetY() + 5);
		$this->pdf->SetTextColor(0, 0, 0);
		if ($type === 'formateurs') {
			$this->printTrainerBlockHeader($dates_array);
		} elseif ($type === 'stagiaires') {
			$this->printTraineeBlockHeader($dates_array);
		}
	}

	/**
	 * When a new page is created, automatically
	 *  - add the header and footer
	 *  - reset position X and Y to the top of the page body (= just below the header)
	 *  - enable auto page break (for page break detection)
	 *  - reset font and text color
	 */
	function setupNewPage()
	{
		$this->pdf->SetAutoPageBreak(false);
		if (!empty($this->tplidx)) $this->pdf->useTemplate($this->tplidx);
		list($posX, $posY) = $this->_pagehead($this->agf, 1, $this->outputlangs);

		$this->_pagefoot($this->pdf->ref_object, $this->outputlangs);
		if (method_exists($this->pdf, 'AliasNbPages')) {
			$this->pdf->AliasNbPages();
		}

		$this->pdf->SetXY($posX, $posY);

		// le second paramètre de SetAutoPageBreak fait qu'on n'a pas besoin de
		// refaire appel à SetPageOrientation() pour définir le seuil du saut de page.
		$this->pdf->SetAutoPageBreak(true, $this->height_for_footer);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
		$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
	}

	/**
	 * Affiche l'en-tête du tableau des stagiaires
	 * @param Agefodd_sesscalendar[] $dates_array
	 * @return array
	 */
	function printTrainerBlockHeader($dates_array)
	{
		global $conf;
		$posX = $this->pdf->GetX();
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'BI', 9);

		$str = $this->outputlangs->transnoentities('AgfPDFFichePres12');
		$this->pdf->Cell(0, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
		$posY = $this->pdf->GetY() + 3;

		// Entête
		// Cadre
		$this->pdf->Rect($posX, $posY, $this->espaceH_dispo, $this->h_ligne + 8);
		// Nom
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$str = $this->outputlangs->transnoentities('AgfPDFFichePres16');
		$this->pdf->Cell($this->trainer_widthcol1, $this->h_ligne + 8, $this->outputlangs->convToOutputCharset($str), 'LTRB', 2, "C", 0);
		// Signature
		$this->pdf->SetXY($posX + $this->trainer_widthcol1, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$str = $this->outputlangs->transnoentities('AgfPDFFichePres18');
		$this->pdf->Cell(0, 5, $this->outputlangs->convToOutputCharset($str), '', 2, "C", 0);

		if (empty($conf->global->AGF_FICHE_PRES_HIDE_LEGAL_MEANING_BELOW_SIGNATURE_HEADER))
		{
			$this->pdf->SetXY($posX + $this->trainer_widthcol1, $posY + 3);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'I', 7);
			$str = $this->outputlangs->transnoentities('AgfPDFFichePres13');
			$this->pdf->Cell(0, 5, $this->outputlangs->convToOutputCharset($str), '', 2, "C", 0);
		}
		$posY += $this->h_ligne;

		// Date

		$last_day = '';
		$same_day = 0;
		$nbTimeSlots = $this->nbtimeslots;
		$timeSlotWidth = $this->trainer_widthtimeslot;
		if (!empty($dates_array) && count($dates_array) < $this->nbtimeslots) {
			$nbTimeSlots = count($dates_array);
			$timeSlotWidth = ($this->espaceH_dispo - $this->trainer_widthcol1) / $nbTimeSlots;
		}
		for ($y = 0; $y < $nbTimeSlots; $y++) {
			// Jour
			$this->pdf->SetXY($posX + $this->trainer_widthcol1 + ($timeSlotWidth * $y), $posY);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 8);
			if ($dates_array[$y]->date_session) {
				$date = dol_print_date($dates_array[$y]->date_session, 'daytextshort');
			} else {
				$date = '';
			}
			$str = $date;
			if ($last_day == $dates_array[$y]->date_session) {
				$same_day += 1;
				$this->pdf->SetFillColor(255, 255, 255);
			} else {
				$same_day = 0;
			}
			$this->pdf->SetXY($posX + $this->trainer_widthcol1 + ($timeSlotWidth * $y) - ($timeSlotWidth * ($same_day)), $posY);
			$this->pdf->Cell($timeSlotWidth * ($same_day + 1), 4, $this->outputlangs->convToOutputCharset($str), 1, 2, "C", $same_day);

			// horaires
			$this->pdf->SetXY($posX + $this->trainer_widthcol1 + ($timeSlotWidth * $y), $posY + 4);
			if ($dates_array[$y]->heured && $dates_array[$y]->heuref) {
				$str = dol_print_date($dates_array[$y]->heured, 'hour') . ' - ' . dol_print_date($dates_array[$y]->heuref, 'hour');
			} else {
				$str = '';
			}
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);
			$this->pdf->Cell($timeSlotWidth, 4, $this->outputlangs->convToOutputCharset($str), 1, 2, "C", 0);

			$last_day = $dates_array[$y]->date_session;
		}
		$posY = $this->pdf->GetY();
		$this->pdf->SetX($this->marge_gauche);

		return array($posX, $posY);
	}

	/**
	 * Affiche l'en-tête du tableau des formateurs
	 * @param Agefodd_sesscalendar[] $dates_array
	 * @return array
	 */
	function printTraineeBlockHeader($dates_array)
	{
		global $conf;
		/**
		 * bloc trainee header
		 */

		$posX = $this->pdf->GetX();
		// title
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'BI', 9);
		$str = $this->outputlangs->transnoentities('AgfPDFFichePres15');
		$this->pdf->Cell(0, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
		$posY = $this->pdf->GetY() +1;

		// Entête
		// Cadre
		$this->pdf->Rect($posX, $posY, $this->espaceH_dispo, $this->h_ligne + 8);
		// Nom
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$str = $this->outputlangs->transnoentities('AgfPDFFichePres16');
		$this->pdf->Cell($this->trainee_widthcol1, $this->h_ligne + 8, $this->outputlangs->convToOutputCharset($str), 'LTRB', 2, "C", 0);
		// Société
		if (empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
			$this->pdf->SetXY($posX + $this->trainee_widthcol1, $posY);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
			$str = $this->outputlangs->transnoentities('AgfPDFFichePres17');
			$this->pdf->Cell($this->trainee_widthcol2, $this->h_ligne + 8, $this->outputlangs->convToOutputCharset($str), 'LTRB', 2, "C", 0);
		} else {
			$this->trainee_widthcol2 = 0;
		}

		// Signature
		$this->pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$str = $this->outputlangs->transnoentities('AgfPDFFichePres18');
		$this->pdf->Cell(0, 5, $this->outputlangs->convToOutputCharset($str), '', 2, "C", 0);

		if (empty($conf->global->AGF_FICHE_PRES_HIDE_LEGAL_MEANING_BELOW_SIGNATURE_HEADER))
		{
			$this->pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2, $posY + 3);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'I', 7);
			$str = $this->outputlangs->transnoentities('AgfPDFFichePres19');
			$this->pdf->Cell(0, 5, $this->outputlangs->convToOutputCharset($str), '', 2, "C", 0);
		}
		$posY += $this->h_ligne;

		// Date
		$last_day = '';
		$same_day = 0;
		$nbTimeSlots = $this->nbtimeslots;
		$timeSlotWidth = $this->trainee_widthtimeslot;
		if (!empty($dates_array) && count($dates_array) < $this->nbtimeslots ) {
			$nbTimeSlots = count($dates_array);
			$timeSlotWidth = ($this->espaceH_dispo - $this->trainee_widthcol1 - (empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES) ? $this->trainee_widthcol2 : 0)) / $nbTimeSlots;
		}
		for ($y = 0; $y < $nbTimeSlots; $y++) {
			// Jour
			$this->pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2 + ($timeSlotWidth * $y), $posY);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 8);
			if ($dates_array[$y]->date_session) {
				$date = dol_print_date($dates_array[$y]->date_session, 'daytextshort');
			} else {
				$date = '';
			}
			$str = $date;
			if ($last_day == $dates_array[$y]->date_session) {
				$same_day += 1;
				$this->pdf->SetFillColor(255, 255, 255);
			} else {
				$same_day = 0;
			}
			$this->pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2 + ($timeSlotWidth * $y) - ($timeSlotWidth * ($same_day)), $posY);
			$this->pdf->Cell($timeSlotWidth * ($same_day + 1), 4, $this->outputlangs->convToOutputCharset($str), 1, 2, "C", $same_day);

			// horaires
			$this->pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2 + ($timeSlotWidth * $y), $posY + 4);
			if ($dates_array[$y]->heured && $dates_array[$y]->heuref) {
				$str = dol_print_date($dates_array[$y]->heured, 'hour') . ' - ' . dol_print_date($dates_array[$y]->heuref, 'hour');
			} else {
				$str = '';
			}
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);
			$this->pdf->Cell($timeSlotWidth, 4, $this->outputlangs->convToOutputCharset($str), 1, 2, "C", 0);

			$last_day = $dates_array[$y]->date_session;
		}
		$posY = $this->pdf->GetY();
		$this->pdf->SetX($this->marge_gauche);

		return array($posX, $posY);
	}

	/**
	 * Affiche la ligne d'un stagiaire
	 *
	 * @param Agefodd_stagiaire $line stagiaire à afficher
	 * @param int               $nbsta_index numéro du stagiaire dans le tableau (pour la numérotation séquentielle)
	 * @param int               $nbTimeSlots nombre de créneaux sur ce bloc (toujours égal à $this->nbtimeslots sauf
	 *                                       pour le dernier bloc qui peut en avoir moins)
	 * @param int               $timeSlotWidth largeur de colonne pour un créneau
	 * @return array
	 */
	function printTraineeLine(&$line, $nbsta_index, $nbTimeSlots, $timeSlotWidth)
	{
		global $conf;
		$posX = $this->pdf->GetX();
		$posY = $this->pdf->GetY();

		if (!empty($conf->global->AGF_ADD_INDEX_TRAINEE)) {
			$str = $nbsta_index . '. ';
		} else {
			$str = '';
		}

		// Cadre
		$this->pdf->Rect($posX, $posY, $this->espaceH_dispo, $this->h_ligne);

		// Nom
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);

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
			$this->outputlangs->load("other");
			$str .= "\n" . $this->outputlangs->trans('DateToBirth') . ' : ' . dol_print_date($line->date_birth, 'day');
		}

		if (!empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
			if (!empty($line->socname)) {
				if ($line->nom . ' ' . $line->prenom!==$line->socname) {
					$str .= '-' . dol_trunc($line->socname, 27);
				}
			}
		}

		if (is_object($this->dao) && $conf->global->AGF_ADD_ENTITYNAME_FICHEPRES) {
			$c = new Societe($this->db);
			$c->fetch($line->socid);

			if (count($this->dao->entities) > 0) {
				foreach ($this->dao->entities as $e) {
					if ($e->id == $c->entity) {
						$str .= "\n" . $this->outputlangs->trans('Entity') . ' : ' . $e->label;
						break;
					}
				}
			}
		}
		$this->pdf->MultiCell($this->trainee_widthcol1, $this->h_ligne, $this->outputlangs->convToOutputCharset($str), 1, "L", false, 1, '', '', true, 0, false, false, $this->h_ligne, 'M');

		// Société
		if (empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
			$this->pdf->SetXY($posX + $this->trainee_widthcol1, $posY);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);
			$str = dol_trunc($line->socname, 27);
			$this->pdf->MultiCell($this->trainee_widthcol2, $this->h_ligne, $this->outputlangs->convToOutputCharset($str), 1, "C", false, 1, '', '', true, 0, false, false, $this->h_ligne, 'M');
		}

		for ($i = 0; $i < $nbTimeSlots - 1; $i++) {
			$this->pdf->Rect($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2 + $timeSlotWidth * $i, $posY, $timeSlotWidth, $this->h_ligne);
		}

		$posY = $this->pdf->GetY();

		return array($posX, $posY);
	}

	/**
	 * Affiche le tampon de l'organisme de formation ainsi que quelques informations (date…)
	 * @return array
	 */
	public function printSignatureBloc()
	{
		global $conf;

		$posX = $this->pdf->GetX();
		$posY = $this->pdf->GetY();
		// Cachet et signature
		if (empty($conf->global->AGF_HIDE_CACHET_FICHEPRES)) {
			$str = $this->outputlangs->transnoentities('AgfPDFFichePres20');
			$this->pdf->Cell(50, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

			$this->pdf->SetXY($posX + 55, $posY);
			$str = $this->outputlangs->transnoentities('AgfPDFFichePres21') . dol_print_date($this->session->datef);
			$this->pdf->Cell(20, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

			$this->pdf->SetXY($posX + 92, $posY);
			$str = $this->outputlangs->transnoentities('AgfPDFFichePres22');
			$this->pdf->Cell(50, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

		}
		$posY = $this->pdf->GetY() +2;

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
	 * @param array $dates_array
	 * @param false $searchOPCO
	 */
	function setSummaryTime($dates_array = array(), $searchOPCO = false)
	{
		if (!empty($dates_array))
		{
			//calcul de la durée totale (en seconde) des créneaux
			$this->totalSecondsSessCalendar = 0;
			foreach ($dates_array as $sess_calendar){
				$this->totalSecondsSessCalendar += $sess_calendar->getTime();
			}
		}
	}

	/**
	 * Affiche le bloc "La formation" (intitulé, lieu, période, session…)
	 * @param int $posX
	 * @param int $posY
	 * @return array
	 *
	 */
	function printSessionSummary($posX, $posY)
	{
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'BI', 9);
		$str = $this->outputlangs->transnoentities('AgfPDFFichePres23');
		$this->pdf->Cell(0, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
		$posY += 5;

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
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 9);
		$str = $this->outputlangs->transnoentities('AgfPDFFichePres6');
		$this->pdf->Cell($this->formation_widthcol1, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

		$this->pdf->SetXY($posX + $this->formation_widthcol1, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);

		if (empty($this->pdf->ref_object->intitule_custo)) {
			$str = $this->pdf->ref_object->formintitule;
		} else {
			$str = $this->pdf->ref_object->intitule_custo;
		}
		$this->pdf->MultiCell($this->formation_widthcol2, 4, $this->outputlangs->convToOutputCharset('« ' . $str . ' »'), 0, 'L');

		$hauteur = dol_nboflines_bis($str, 50) * 4;
		$haut_col2 += $hauteur + 2;

		// Période
		$posY = $this->pdf->GetY() + 2;
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 9);
		$str = $this->outputlangs->transnoentities('AgfPDFFichePres7');
		$this->pdf->Cell($this->formation_widthcol1, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

		$str = $this->pdf->ref_object->libSessionDate('daytext');

		$str .= ' (' . $this->pdf->ref_object->duree_session . ' h)';

		$this->pdf->SetXY($posX + $this->formation_widthcol1, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->pdf->MultiCell($this->formation_widthcol2, 4, $this->outputlangs->convToOutputCharset($str), 0, 'L');
		$hauteur = dol_nboflines_bis($str, 50) * 4;
		$haut_col2 += $hauteur + 2;

		//Session
		$posY = $this->pdf->GetY() + 2;
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 9);
		$str = $this->outputlangs->transnoentities('Session')." :";
		$this->pdf->Cell($this->formation_widthcol1, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

		$this->pdf->SetXY($posX + $this->formation_widthcol1, $posY);
		$str = $this->pdf->ref_object->id . '#' . $this->pdf->ref_object->ref;
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->pdf->MultiCell($this->formation_widthcol2, 4, $this->outputlangs->convToOutputCharset($str), 0, 'L');
		$hauteur = dol_nboflines_bis($str, 50) * 4;
		$haut_col2 += $hauteur + 2;

		//OPCO
		$opco_array = $this->TOpco;
		if(!empty($opco_array))
		{
			$posY = $this->pdf->GetY() + 2;
			$this->pdf->SetXY($posX, $posY);
			$str = $this->outputlangs->transnoentities('OPCO').' :';
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 9);
			$this->pdf->Cell($this->formation_widthcol3, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

			$i = 0;
			$str = '';
			foreach($opco_array as $opco_object){

				if(!empty($opco_object->num_OPCA_file)) {

					if($i != 0) $str .= ', ';

					$str .= $opco_object->num_OPCA_file;
				}
				else $str .= '';

				$i++;
			}

			$this->pdf->SetXY($posX + $this->formation_widthcol1, $posY);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
			$this->pdf->MultiCell($this->formation_widthcol4, 4, $this->outputlangs->convToOutputCharset($str), 0, 'L');
			$hauteur = dol_nboflines_bis($str, 50) * 4;
			$haut_col2 += $hauteur;
		}

		// Lieu
		$posY_col4 = $posYintitule;
		$this->pdf->SetXY($posX + $this->formation_widthcol1 + $this->formation_widthcol2, $posYintitule);
		$str = $this->outputlangs->transnoentities('AgfPDFFichePres11');
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 9);
		$this->pdf->Cell($this->formation_widthcol3, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

		$agf_place = new Agefodd_place($this->db);
		$resql = $agf_place->fetch($this->pdf->ref_object->placeid);

		$this->pdf->SetXY($posX + $this->formation_widthcol1 + $this->formation_widthcol2 + $this->formation_widthcol3 +2 , $posYintitule);
		$str = $agf_place->ref_interne . "\n" . $agf_place->adresse . "\n" . $agf_place->cp . " " . $agf_place->ville;
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->pdf->MultiCell($this->formation_widthcol4, 4, $this->outputlangs->convToOutputCharset($str), 0, 'L');
		$hauteur = dol_nboflines_bis($str, 50) * 4;
		$haut_col4 += $hauteur +2;
        $posY_col4 += $hauteur +2;

        //Total heures des créneaux de la page
        $time = $this->totalSecondsSessCalendar;
        if(!empty($time))
        {
            $this->pdf->SetXY($posX + $this->formation_widthcol1 + $this->formation_widthcol2, $posY_col4);
            $str = $this->outputlangs->transnoentities('Nombre d\'heures :');
            $this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 9);
            $this->pdf->Cell($this->formation_widthcol3, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

            $totalSessCalendarHours = intval($time / 3600);
            $totalSessCalendarMin = ($time - (3600 * $totalSessCalendarHours)) / 60;

            $this->pdf->SetXY($posX + $this->formation_widthcol1 + $this->formation_widthcol2 + $this->formation_widthcol3 + 2, $posY_col4);
            $str = str_pad($totalSessCalendarHours, 2, 0, STR_PAD_LEFT).':'.str_pad($totalSessCalendarMin, 2, 0, STR_PAD_LEFT);
            $this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
            $this->pdf->MultiCell($this->formation_widthcol4, 4, $this->outputlangs->convToOutputCharset($str), 0, 'L');
            $hauteur = dol_nboflines_bis($str, 50) * 4;
            $haut_col4 += $hauteur + 2;
        }
        $bottom = $this->pdf->GetY();

		// Cadre
		($haut_col4 > $haut_col2) ? $haut_table = $haut_col4 : $haut_table = $haut_col2;
		$posY = $posYintitule + $haut_table + 4;
		$this->pdf->Rect($cadre_tableau[0], $cadre_tableau[1], $this->espaceH_dispo, $haut_table + $this->summaryPaddingBottom);

		$this->pdf->SetXY($this->marge_gauche, $bottom+5);
		return array($posX, $posY);
	}

	/**
	 * @param Object $agf session
	 * @param int $dummy
	 * @param Translate $outputlangs outputlangs
	 * @return array (x, y)
	 */
	function _pagehead($agf, $dummy = 1, $outputlangs = '')
	{
		global $conf, $mysoc;

		$this->outputlangs->load("main");

		// Fill header with background color
		$this->pdf->SetFillColor($this->colorheaderBg[0], $this->colorheaderBg[1], $this->colorheaderBg[2]);
		$this->pdf->MultiCell($this->page_largeur, 40, '', 0, 'L', true, 1, 0, 0);

		pdf_pagehead($this->pdf, $this->outputlangs, $this->pdf->page_hauteur);

		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->pdf->SetTextColor($this->colorheaderText[0], $this->colorheaderText[1], $this->colorheaderText[2]);

		$posY = $this->marge_haute;

		// Logo
		$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
		$width_logo = pdf_getWidthForLogo($logo);
		if ($this->emetteur->logo) {
			if (is_readable($logo)) {
				$height = pdf_getHeightForLogo($logo);
				$width_logo = pdf_getWidthForLogo($logo);
				if ($width_logo > 0) {
					$posX = $this->page_largeur - $this->marge_droite - $width_logo;
				} else {
					$posX = $this->page_largeur - $this->marge_droite - 55;
				}
				$this->pdf->Image($logo, $posX, $posY, 0, $height);
			} else {
				$this->pdf->SetTextColor(200, 0, 0);
				$this->pdf->SetFont('', 'B', $this->default_font_size - 2);
				$this->pdf->MultiCell(100, 3, $this->outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$this->pdf->MultiCell(100, 3, $this->outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$text = $this->emetteur->name;
			$this->pdf->MultiCell(100, 4, $this->outputlangs->convToOutputCharset($text), 0, 'L');
		}
		$posY = $this->marge_haute;
		$posX = $this->marge_gauche;

//		$hautcadre = 30;
//		$this->pdf->SetXY($posX, $posY);
//		$this->pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);

		// Show sender name
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont('', 'B', $this->default_font_size - 2);
		$this->pdf->MultiCell(80, 4, $this->outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
		$posY = $this->pdf->GetY();

		// Show sender information
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont('', '', $this->default_font_size - 3);
		$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
		$posY = $this->pdf->GetY();
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont('', '', $this->default_font_size - 3);
		$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->zip . ' ' . $this->emetteur->town), 0, 'L');
		$posY = $this->pdf->GetY();
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont('', '', $this->default_font_size - 3);
		$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
		$posY = $this->pdf->GetY();
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont('', '', $this->default_font_size - 3);
		$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');
		$posY = $this->pdf->GetY();

		printRefIntForma($this->db, $this->outputlangs, $agf, $this->default_font_size - 3, $this->pdf, $posX, $posY, 'L', true);

		// Affichage du logo commanditaire (optionnel)
		if ($conf->global->AGF_USE_LOGO_CLIENT) {
			$staticsoc = new Societe($this->db);
			$staticsoc->fetch($this->pdf->ref_object->socid);
			$dir = $conf->societe->multidir_output[$staticsoc->entity] . '/' . $staticsoc->id . '/logos/';
			if (!empty($staticsoc->logo)) {
				$logo_client = $dir . $staticsoc->logo;
				if (file_exists($logo_client) && is_readable($logo_client)){
					$heightlogo = pdf_getHeightForLogo($logo_client);
					$this->pdf->Image($logo_client, $this->page_largeur - $this->marge_gauche - $this->marge_droite - ( $width_logo * 1.5), $this->marge_haute, $heightlogo);
				}
			}
		}

		$posY = $this->pdf->GetY() + 7;
		if ($conf->global->AGF_PRINT_INTERNAL_REF_ON_PDF)
			$posY -= 4;

		$this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
		$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);

		$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);

		// Mise en page de la baseline
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 18);
		$str = $this->outputlangs->transnoentities($mysoc->url);
		$width = $this->pdf->GetStringWidth($str);

		// alignement du bord droit du container avec le haut de la page
		$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $width;
		$baseline_x = 8;
		$baseline_y = $this->espaceV_dispo - $baseline_ecart + 30;
		$this->pdf->SetXY($baseline_x, $baseline_y);

		/*
		 * Corps de page
		 */
		$posX = $this->marge_gauche;
		$posY = $posY + $this->header_vertical_margin;

		// Titre
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 18);
		$this->pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
		$str = $this->outputlangs->transnoentities('AgfPDFFichePres1');
		$this->pdf->Cell(0, 6, $this->outputlangs->convToOutputCharset($str), 0, 2, "C", 0);
		$posY += 6 + 4;

		// Intro
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 8);
		$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
		$str = $this->outputlangs->transnoentities('AgfPDFFichePres2') . ' « ' . $mysoc->name . ' », ' . $this->outputlangs->transnoentities('AgfPDFFichePres3') . ' ';
		$str .= str_replace(array('<br>', '<br />', "\n", "\r"), array(' ', ' ', ' ', ' '), $mysoc->address) . ' ';
		$str .= $mysoc->zip . ' ' . $mysoc->town;
		$str .= $this->outputlangs->transnoentities('AgfPDFFichePres4') . ' ' . $conf->global->AGF_ORGANISME_REPRESENTANT . ",\n";
		$str .= $this->outputlangs->transnoentities('AgfPDFFichePres5');
		$this->pdf->MultiCell(0, 0, $this->outputlangs->convToOutputCharset($str), 0, 'C');
		$posY = $this->pdf->GetY() + 1;

		return array($posX, $posY);
	}

	/**
	 * @param Object $object object
	 * @param Translate $outputlangs outputlangs
	 * @return int int
	 */
	function _pagefoot($object, $outputlangs)
	{
		$this->pdf->SetDrawColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		$this->pdf->SetTextColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		return pdf_agfpagefoot($this->pdf, $this->outputlangs, '', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, 1, 0);
	}
}
