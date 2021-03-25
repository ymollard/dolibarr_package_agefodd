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
 * \file agefodd/core/modules/agefodd/pdf/pdf_fiche_presence_societe.modules.php
 * \ingroup agefodd
 * \brief PDF for attendees sheet
 */
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/core/modules/agefodd/pdf/pdf_fiche_presence.modules.php');
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

// TODO faire étendre pdf_fiche_presence
class pdf_fiche_presence_societe extends pdf_fiche_presence {
	var $emetteur; // Objet societe qui emet

	// Definition des couleurs utilisées de façon globales dans le document (charte)
	protected $colorfooter;
	protected $colortext;
	protected $colorhead;
	protected $colorheaderBg;
	protected $colorheaderText;
	protected $colorLine;

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
		$this->format = array (
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
		$this->default_font_size=12;

		$this->colorfooter = agf_hex2rgb($conf->global->AGF_FOOT_COLOR);
		$this->colortext = agf_hex2rgb($conf->global->AGF_TEXT_COLOR);
		$this->colorhead = agf_hex2rgb($conf->global->AGF_HEAD_COLOR);
		$this->colorheaderBg = agf_hex2rgb($conf->global->AGF_HEADER_COLOR_BG);
		$this->colorheaderText = agf_hex2rgb($conf->global->AGF_HEADER_COLOR_TEXT);
		$this->colorLine = agf_hex2rgb($conf->global->AGF_COLOR_LINE);

		// Get source company
		$this->emetteur = $mysoc;
		if (! $this->emetteur->country_code)
			$this->emetteur->country_code = substr($langs->defaultlang, - 2); // By default, if was not defined

		$this->header_vertical_margin = 3;

		$this->formation_widthcol1 = 20;
		$this->formation_widthcol2 = 80;
		$this->formation_widthcol3 = 27;
		$this->formation_widthcol4 = 60;

		$this->trainer_widthcol1 = 44;
		$this->trainer_widthcol2 = 140;
		$this->trainer_widthtimeslot = 24;

		$this->trainee_widthcol1 = 40;
		$this->trainee_widthcol2 = 40;
		if (empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
			$this->trainee_widthtimeslot = 18;
		} else {
			$this->trainee_widthtimeslot = 24.7;
		}

		$this->nbtimeslots = 6;
		$this->height_for_footer = 40;
	}

	/**
	 * @param Object $agf  Session
	 * @param Translate $outputlangs $outputlangs
	 * @param string $file file
	 * @param int $socid socid
	 * @return int
	 */
	function write_file($agf, $outputlangs, $file, $socid, $courrier)
	{
		global $user, $langs, $conf, $hookmanager;

		$this->outputlangs = $outputlangs;

		if (! is_object($this->outputlangs))
			$this->outputlangs = $langs;

		if (! is_object($agf)) {
			$id = $agf;
			$agf = new Agsession($this->db);
			$ret = $agf->fetch($id);
		}

		// Definition of $dir and $file
		$dir = $conf->agefodd->dir_output;
		$file = $dir . '/' . $file;

		if (! file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		}

		if (file_exists($dir)) {
			$this->pdf = pdf_getInstance($this->format, $this->unit, $this->orientation);
			$this->pdf->ref_object = $agf;

			if (class_exists('TCPDF')) {
				$this->pdf->setPrintHeader(false);
				$this->pdf->setPrintFooter(false);
			}

			if (!empty($conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER))
				$this->height_for_footer = $conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER;

			$realFooterHeight = $this->getRealHeightLine('foot');
			$this->height_for_footer = max($this->height_for_footer, $realFooterHeight);

			$this->pdf->Open();

			$this->pdf->SetTitle($this->outputlangs->convToOutputCharset($this->outputlangs->transnoentities('AgfPDFFichePres1') . " " . $this->pdf->ref_object->ref));
			$this->pdf->SetSubject($this->outputlangs->transnoentities("Invoice"));
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
				$this->_pagebody($this->pdf, $this->pdf->ref_object, $this->outputlangs);
			}

			$this->pdf->Close();
			$this->pdf->Output($file, 'F');
			if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));


			// Add pdfgeneration hook
			if (! is_object($hookmanager))
			{
				include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
				$hookmanager=new HookManager($this->db);
			}
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters=array('file'=>$file,'object'=>$this->pdf->ref_object,'outputlangs'=>$this->outputlangs);
			global $action;
			$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks


			return 1; // Pas d'erreur
		} else {
			$this->error = $langs->trans("ErrorConstantNotDefined", "AGF_OUTPUTDIR");
			return 0;
		}
		$this->error = $langs->trans("ErrorUnknown");
		return 0; // Erreur par defaut
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

		$nbsta_index=1;

		// Set path to the background PDF File
		if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->AGF_ADD_PDF_BACKGROUND_P))
		{
			$pagecount = $this->pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P);
			$tplidx = $this->pdf->importPage(1);
		}

		$height_for_footer = 35;
		if (!empty($conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER)) $height_for_footer = $conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER;

		if (!empty($conf->multicompany->enabled)) {
			dol_include_once('/multicompany/class/dao_multicompany.class.php');
			$dao = new DaoMulticompany($this->db);
			$dao->getEntities();
		}

		$session_hours=array();
		$tmp_array=array();
		$agf_date = new Agefodd_sesscalendar($this->db);
		$resql = $agf_date->fetch_all($this->pdf->ref_object->id);
		if (! $resql) {
			setEventMessage($agf_date->error, 'errors');
		}
		if (is_array($agf_date->lines) && count($agf_date->lines)>$this->nbtimeslots) {
			for($i = 0; $i < count($agf_date->lines); $i ++) {
				$tmp_array[]=$agf_date->lines[$i];
				if(count($tmp_array)>=$this->nbtimeslots || $i==count($agf_date->lines)-1) {
					$session_hours[]=$tmp_array;
					$tmp_array=array();
				}
			}
		} else {
			$session_hours[]=$agf_date->lines;
		}

		//On récupère l'id des sociétés des participants
		$agfstaglobal = new Agefodd_session_stagiaire($this->db);
		$resql = $agfstaglobal->fetch_stagiaire_per_session($this->pdf->ref_object->id);
		$socstagiaires = array();

		$TStagiaireStatusToExclude = array();

		if (! empty($conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES)) {
			$TStagiaireStatusToExclude = explode(',', $conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES);
		}

		foreach ($agfstaglobal->lines as $line) {
			if (! empty($TStagiaireStatusToExclude) && in_array($line->status_in_session, $TStagiaireStatusToExclude)) {
				continue;
			}

			if (! isset($socstagiaires[$line->socid])) {
				$socstagiaires[$line->socid] = new stdClass();
				$socstagiaires[$line->socid]->lines = array();
			}

			$socstagiaires[$line->socid]->lines[] = $line;
		}

		$this->formateurs = new Agefodd_session_formateur($this->db);
		$nbform = $this->formateurs->fetch_formateur_per_session($this->pdf->ref_object->id);

		//Pour chaque société ayant un participant à afficher, on crée une série de feuilles de présence
		foreach($socstagiaires as $socstagiaires_id => $agfsta) {

			$this->stagiaires = new stdClass();
			$this->stagiaires->lines = $agfsta->lines;

			foreach ($session_hours as $key => $dates_array) {
				// New page
				$this->pdf->AddPage();
				if (!empty($tplidx)) $this->pdf->useTemplate($tplidx);
				list($posX, $posY) = $this->_pagehead($this->pdf, 1, $this->outputlangs);

				/**
				 * *** Bloc formation ****
				 */
				list($posX, $posY) = $this->printSessionSummary($posX, $posY);

				/**
				 * *** Bloc formateur ****
				 */
				$this->h_ligne = 7;

				if (!empty($this->formateurs->lines))
				{
					list($posX, $posY) = $this->printTrainerBlockHeader($posX, $posY, $dates_array);
					list($posX, $posY) = $this->printTrainerBlockLines($posX, $posY, $dates_array, $agf);
				}

				/**
				 * *** Bloc stagiaire ****
				 */

				// ligne
				$this->h_ligne = 7;
				if (is_object($dao) && $conf->global->AGF_ADD_ENTITYNAME_FICHEPRES) {
					$this->h_ligne = $this->h_ligne + 3;
				}
				if (!empty($conf->global->AGF_ADD_DTBIRTH_FICHEPRES)) {
					$this->h_ligne = $this->h_ligne + 3;
				}
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);

				if (!empty($this->stagiaires->lines))
				{
					list($posX, $posY) = $this->printTraineeBlockHeader($posX, $posY, $dates_array);
					list($posX, $posY) = $this->printTraineeBlockLines($posX, $posY, $dates_array, $this->pdf->ref_object);
				}

				// Cachet et signature
				if (empty($conf->global->AGF_HIDE_CACHET_FICHEPRES))
				{

					$posY += 2;
					$posX -= 2;
					$this->pdf->SetXY($posX, $posY);
					$str = $this->outputlangs->transnoentities('AgfPDFFichePres20');
					$this->pdf->Cell(50, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

					$this->pdf->SetXY($posX + 55, $posY);
					$str = $this->outputlangs->transnoentities('AgfPDFFichePres21').dol_print_date($this->pdf->ref_object->datef);
					$this->pdf->Cell(20, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

					$this->pdf->SetXY($posX + 92, $posY);
					$str = $this->outputlangs->transnoentities('AgfPDFFichePres22');
					$this->pdf->Cell(50, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

				}

				$posY = $this->pdf->GetY();

				// Incrustation image tampon
				if ($conf->global->AGF_INFO_TAMPON) {
					$dir = $conf->agefodd->dir_output . '/images/';
					$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
					if (file_exists($img_tampon))
						$this->pdf->Image($img_tampon, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 50, $posY, 50);
				}

				// Pied de page
				$this->_pagefoot($this->pdf, $this->pdf->ref_object, $this->outputlangs);
				if (method_exists($this->pdf, 'AliasNbPages')) {
					$this->pdf->AliasNbPages();
				}
			}
		}
	}

}
