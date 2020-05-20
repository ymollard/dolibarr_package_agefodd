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
 * \file agefodd/core/modules/agefodd/pdf/pdf_fiche_presence_empty.modules.php
 * \ingroup agefodd
 * \brief PDF for empty training attendees session sheet
 */
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/core/modules/agefodd/pdf/pdf_fiche_presence.modules.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_convention.class.php');
dol_include_once('/agefodd/class/agefodd_place.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
class pdf_fiche_presence_empty extends pdf_fiche_presence {
	var $emetteur; // Objet societe qui emet

	// Definition des couleurs utilisées de façon globales dans le document (charte)
	protected $colorfooter;
	protected $colortext;
	protected $colorhead;
	protected $colorheaderBg;
	protected $colorheaderText;
	protected $colorLine;

	/**
	 * \brief		Constructor
	 * \param		db		Database handler
	 */
	function __construct($db) {
		global $conf, $langs, $mysoc;

		parent::__construct($db);

		$this->db = $db;
		$this->name = "fiche_presence_empty";
		$this->description = $langs->trans('AgfModPDFFichePres');

		// Dimension page pour format A4 en paysage
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray ['width']; // use standard but reverse width and height to get Landscape format
		$this->page_hauteur = $formatarray ['height']; // use standard but reverse width and height to get Landscape format
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

		$this->formation_widthcol1 = 20;
		$this->formation_widthcol2 = 80;
		$this->formation_widthcol3 = 27;
		$this->formation_widthcol4 = 65;

		$this->largeur_date_trainee = 18;
		$this->largeur_date_trainer = 24;

		$this->predefline=11;
		if ($conf->global->AGF_INFO_TAMPON) {
			$dir = $conf->agefodd->dir_output . '/images/';
			$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
			if (file_exists($img_tampon))
				$this->predefline=10;
		}
	}

	/**
	 * \brief Fonction generant le document sur le disque
	 * \param agf		Objet document a generer (ou id si ancienne methode)
	 * outputlangs	Lang object for output language
	 * file		Name of file to generate
	 * \return int 1=ok, 0=ko
	 */
	function write_file($agf, $outputlangs, $file, $socid, $courrier = '') {
		global $user, $langs, $conf, $mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$this->outputlangs = $outputlangs;

		if (!is_object($this->outputlangs))
			$this->outputlangs = $langs;

		if (!is_object($agf)) {
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

			$this->pdf->Open();
			$pagenb = 0;

			$this->pdf->SetTitle($this->outputlangs->convToOutputCharset($this->outputlangs->transnoentities('AgfPDFFichePres1') . " " . $agf->ref));
			$this->pdf->SetSubject($this->outputlangs->transnoentities("Invoice"));
			$this->pdf->SetCreator("Dolibarr " . DOL_VERSION . ' (Agefodd module)');
			$this->pdf->SetAuthor($this->outputlangs->convToOutputCharset($user->fullname));
			$this->pdf->SetKeyWords($this->outputlangs->convToOutputCharset($agf->ref) . " " . $this->outputlangs->transnoentities("Document"));
			if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION)
				$this->pdf->SetCompression(false);

			$this->pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
			$this->pdf->SetAutoPageBreak(1, 0);

			// On recupere les infos societe
			$agf_soc = new Societe($this->db);
			$result = $agf_soc->fetch($socid);

			if ($result) {
				$this->_pagebody($agf, 1, $this->outputlangs);
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
			$parameters=array('file'=>$file,'object'=>$agf,'outputlangs'=>$this->outputlangs);
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
	 * \brief Show header of page
	 * \param pdf Object PDF
	 * \param object Object invoice
	 * \param showaddress 0=no, 1=yes
	 * \param outputlangs		Object lang for output
	 */
	function _pagebody($agf, $outputlangs) {
		global $user, $langs, $conf, $mysoc;

		// Set path to the background PDF File
		if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->AGF_ADD_PDF_BACKGROUND_P))
		{
			$pagecount = $this->pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P);
			$tplidx = $this->pdf->importPage(1);
		}

		// New page
		$this->pdf->AddPage();
		if (! empty($tplidx)) $this->pdf->useTemplate($tplidx);
		$pagenb ++;
		list($posX, $posY) = $this->_pagehead($agf, 1, $this->outputlangs);

		/**
		 * *** Bloc formation ****
		 */
		list($posX, $posY) = $this->printSessionSummary($posX, $posY);

		/**
		 * *** Bloc formateur ****
		 */

		$this->pdf->SetXY($posX - 2, $posY - 2);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'BI', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres12');
		$this->pdf->Cell(0, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);
		$posY += 2;

		$cadre_tableau = array (
				$posX - 2,
				$posY
		);

		$larg_col1 = 44;
		$larg_col2 = 140;
		$haut_col2 = 0;
		$haut_col3 = 0;
		$h_ligne = 8;
		$haut_cadre = 0;

		// Entête
		// Cadre
		$this->pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $h_ligne + 8);
		// Nom
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres16');
		$this->pdf->Cell($larg_col1, $h_ligne + 8, $this->outputlangs->convToOutputCharset($this->str), 'R', 2, "C", 0);
		// Signature
		$this->pdf->SetXY($posX + $larg_col1, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres18');
		$this->pdf->Cell(0, 5, $this->outputlangs->convToOutputCharset($this->str), 'LR', 2, "C", 0);

		if (empty($conf->global->AGF_FICHE_PRES_HIDE_LEGAL_MEANING_BELOW_SIGNATURE_HEADER))
		{
			$this->pdf->SetXY($posX + $larg_col1, $posY + 3);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'I', 7);
			$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres13');
			$this->pdf->Cell(0, 5, $this->outputlangs->convToOutputCharset($this->str), 'LR', 2, "C", 0);
		}
		$posY += $h_ligne;

		//Date
		$this->str = '';
		for($y = 0; $y < $this->nbtimeslots; $y ++) {
			// Jour
			$this->pdf->SetXY($posX + $larg_col1 + (20 * $y), $posY);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 8);
			$this->pdf->SetXY($posX + $larg_col1 + ($this->largeur_date_trainer * $y) - ($this->largeur_date_trainer * ($same_day)), $posY);
			$this->pdf->Cell($this->largeur_date_trainer * ($same_day + 1), 4, $this->outputlangs->convToOutputCharset($this->str), 1, 2, "C", $same_day);

			// horaires
			$this->pdf->SetXY($posX + $larg_col1 + ($this->largeur_date_trainer * $y), $posY + 4);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);
			$this->pdf->Cell($this->largeur_date_trainer, 4, $this->outputlangs->convToOutputCharset($this->str), 1, 2, "C", 0);
		}
		$posY = $this->pdf->GetY();

		$formateurs = new Agefodd_session_formateur($this->db);
		$nbform = $formateurs->fetch_formateur_per_session($agf->id);
		foreach($formateurs->lines as $trainerlines) {

			// Cadre
			$this->pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $h_ligne);

			// Nom
			$this->pdf->SetXY($posX - 2, $posY);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);
			$this->str = strtoupper($trainerlines->lastname) . ' ' . ucfirst($trainerlines->firstname);
			$this->pdf->MultiCell($larg_col1 + 2, $h_ligne, $this->outputlangs->convToOutputCharset($this->str), 1, "L", false, 1, '', '', true, 0, false, false, $h_ligne, 'M');


			for($i = 0; $i < $this->nbtimeslots; $i ++) {
				$this->pdf->Rect($posX + $larg_col1 + $this->largeur_date_trainer * $i, $posY, $this->largeur_date_trainer, $h_ligne);
			}

			$posY = $this->pdf->GetY();
			if ($posY > $this->page_hauteur - 20) {

				$this->pdf->AddPage();
				$pagenb ++;
				$posY = $this->marge_haute;
			}

		}

		$posY = $this->pdf->GetY() + 4;

		/**
		 * *** Bloc stagiaire ****
		 */

		$this->pdf->SetXY($posX - 2, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'BI', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres15');
		$this->pdf->Cell(0, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);
		$posY = $this->pdf->GetY() + 2;

		$cadre_tableau = array (
				$posX - 2,
				$posY
		);

		$larg_col1 = 40;
		$larg_col2 = 40;
		$larg_col3 = 50;
		$larg_col4 = 112;
		$haut_col2 = 0;
		$haut_col4 = 0;
		$h_ligne = 8;
		$haut_cadre = 0;

		// Entête
		// Cadre
		$this->pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $h_ligne + 8);
		// Nom
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres16');
		$this->pdf->Cell($larg_col1, $h_ligne + 8, $this->outputlangs->convToOutputCharset($this->str), 'R', 2, "C", 0);
		// Société
		$this->pdf->SetXY($posX + $larg_col1, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres17');
		$this->pdf->Cell($larg_col2, $h_ligne + 8, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "C", 0);
		// Signature
		$this->pdf->SetXY($posX + $larg_col1 + $larg_col2, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres18');
		$this->pdf->Cell(0, 5, $this->outputlangs->convToOutputCharset($this->str), 'LR', 2, "C", 0);

		if (empty($conf->global->AGF_FICHE_PRES_HIDE_LEGAL_MEANING_BELOW_SIGNATURE_HEADER))
		{
			$this->pdf->SetXY($posX + $larg_col1 + $larg_col2, $posY + 3);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'I', 7);
			$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres19');
			$this->pdf->Cell(0, 5, $this->outputlangs->convToOutputCharset($this->str), 'LR', 2, "C", 0);
		}
		$posY += $h_ligne;

		// Date
		$this->str = '';
		for($y = 0; $y < $this->nbtimeslots; $y ++) {
			// Jour
			$this->pdf->SetXY($posX + $larg_col1 + $larg_col2 + (20 * $y), $posY);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 8);
			$this->pdf->SetXY($posX + $larg_col1 + $larg_col2 + ($this->largeur_date_trainee * $y) - ($this->largeur_date_trainee * ($same_day)), $posY);
			$this->pdf->Cell($this->largeur_date_trainee * ($same_day + 1), 4, $this->outputlangs->convToOutputCharset($this->str), 1, 2, "C", $same_day);

			// horaires
			$this->pdf->SetXY($posX + $larg_col1 + $larg_col2 + ($this->largeur_date_trainee * $y), $posY + 4);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);
			$this->pdf->Cell($this->largeur_date_trainee, 4, $this->outputlangs->convToOutputCharset($this->str), 1, 2, "C", 0);
		}
		$posY = $this->pdf->GetY();

		// ligne
		$h_ligne = 8;
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);

		for($y = 0; $y < $this->predefline; $y ++) {
			// Cadre
			$this->pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $h_ligne);

			// Nom
			$this->pdf->SetXY($posX, $posY);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
			$this->pdf->Cell($larg_col1, $h_ligne, $this->outputlangs->convToOutputCharset(''), 'R', 2, "L", 0);

			// Société
			$this->pdf->SetXY($posX + $larg_col1, $posY);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
			$this->pdf->Cell($larg_col2, $h_ligne, $this->outputlangs->convToOutputCharset(''), 0, 2, "C", 0);

			for($i = 0; $i < $this->nbtimeslots; $i ++) {
				$this->pdf->Rect($posX + $larg_col1 + $larg_col2 + $this->largeur_date_trainee * $i, $posY, $this->largeur_date_trainee, $h_ligne);
			}
			$posY = $this->pdf->GetY();
		}



		// Cachet et signature
		if (empty($conf->global->AGF_HIDE_CACHET_FICHEPRES))
		{
			$posY += 2;
			$posX -= 2;
			$this->pdf->SetXY($posX, $posY);
			$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres20');
			$this->pdf->Cell(50, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);

			$this->pdf->SetXY($posX + 55, $posY);
			$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres21').dol_print_date($agf->datef);
			$this->pdf->Cell(20, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);

			$this->pdf->SetXY($posX + 92, $posY);
			$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres22');
			$this->pdf->Cell(50, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);
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
		$this->_pagefoot($agf, $this->outputlangs);
		// FPDI::AliasNbPages() is undefined method into Dolibarr 3.5
		if (method_exists($this->pdf, 'AliasNbPages')) {
			$this->pdf->AliasNbPages();
		}
	}

	/**
	 * \brief		Show footer of page
	 * \param		pdf PDF factory
	 * \param		object			Object invoice
	 * \param		outputlang		Object lang for output
	 * \remarks	Need this->emetteur object
	 */
	function _pagefoot($object, $outputlangs) {
		global $conf, $langs, $mysoc;

		$this->pdf->SetDrawColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		$this->pdf->SetTextColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		return pdf_agfpagefoot($this->pdf, $this->outputlangs, '', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, 1, $hidefreetext);
	}
}
