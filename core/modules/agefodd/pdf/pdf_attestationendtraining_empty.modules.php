<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2012-2016 Florian Henry <florian.henry@open-concept.pro>
 * Copyright (C) 2014-2015 	Philippe Grand 	<philippe.grand@atoo-net.com>
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
 * \file agefodd/core/modules/agefodd/pdf/pdf_attestation.modules.php
 * \ingroup agefodd
 * \brief PDF for certificate (attestation)
 */
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
dol_include_once('/agefodd/class/agefodd_place.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
class pdf_attestationendtraining_empty extends ModelePDFAgefodd {
	var $emetteur; // Objet societe qui emet

	// Definition des couleurs utilisées de façon globales dans le document (charte)
	protected $colorfooter;
	protected $colortext;
	protected $colorhead;
	protected $colorheaderBg;
	protected $colorheaderText;
	protected $colorLine;

	/**
	 * \brief Constructor
	 * \param db Database handler
	 */
	function __construct($db) {
		global $conf, $langs, $mysoc;

		$this->db = $db;
		$this->name = "attestationendtrainng_trainee";
		$this->description = $langs->trans('AgfPDFAttestation');

		// Dimension page pour format A4 en paysage
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array (
				$this->page_largeur,
				$this->page_hauteur
		);
		$this->marge_gauche = 15;
		$this->marge_droite = 15;
		$this->marge_haute = 10;
		$this->marge_basse = 10;
		$this->unit = 'mm';
		$this->oriantation = 'P';
		$this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
		$this->milieu = $this->espaceH_dispo / 2;

		// Define position of columns
		$this->posxdesc = $this->marge_gauche + 1;
		$this->posxacquired = 115;
		$this->posxnonacquired = 135;
		$this->posxongoing = 155;
		$this->posxnotevaluated = 175;

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
	}

	/**
	 * \brief Fonction generant le document sur le disque
	 * \param agf Objet document a generer (ou id si ancienne methode)
	 * outputlangs Lang object for output language
	 * file Name of file to generate
	 * \return int 1=ok, 0=ko
	 */
	function write_file($agf, $outputlangs, $file, $session_trainee_id) {
		global $user, $langs, $conf, $mysoc;

		if (! is_object($outputlangs))
			$outputlangs = $langs;

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
			$pdf = pdf_getInstance($this->format, $this->unit, $this->orientation);

			$pdf->Open();
			$pagenb = 0;

			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}

			$pdf->SetTitle($outputlangs->convToOutputCharset($agf->ref));
			$pdf->SetSubject($outputlangs->transnoentities("Invoice"));
			$pdf->SetCreator("Dolibarr " . DOL_VERSION . ' (Agefodd module)');
			$pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($agf->ref) . " " . $outputlangs->transnoentities("Document"));
			if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION)
				$pdf->SetCompression(false);

			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
			$pdf->SetAutoPageBreak(1, 0);

			// Set path to the background PDF File
			if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->AGF_ADD_PDF_BACKGROUND_P))
			{
				$pagecount = $pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P);
				$tplidx = $pdf->importPage(1);
			}

			// Récuperation des objectifs pedagogique de la formation
			$agf_op = new Formation($this->db);
			$result2 = $agf_op->fetch_objpeda_per_formation($agf->fk_formation_catalogue);

			// Récupération de la duree de la formation
			$agf_duree = new Formation($this->db);
			$result = $agf_duree->fetch($agf->fk_formation_catalogue);

			// Recuperation des informations du lieu de la session
			$agf_place = new Agefodd_place($this->db);
			$result = $agf_place->fetch($agf->placeid);

			// Recuperation des informations des formateurs
			$agf_session_trainer = new Agefodd_session_formateur($this->db);
			$agf_session_trainer->fetch_formateur_per_session($id);


			$heightforfooter = $this->marge_basse + 8;

			// New page
			$pdf->AddPage();
			if (! empty($tplidx)) $pdf->useTemplate($tplidx);

			$pagenb ++;
			$this->_pagehead($pdf, $agf, 1, $outputlangs);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
			$pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3

			// On met en place le cadre
			$pdf->SetDrawColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
			//$ep_line1 = 0.5;
			//$pdf->SetLineWidth($ep_line1);

			$height = pdf_getHeightForLogo($logo);
			$hautcadre = 30;
			if ($height > $hautcadre)
				$height = $hautcadre;
			$this->marge_top = $this->marge_haute + $hautcadre;
			// Haut
			$pdf->Line($this->marge_gauche, $this->marge_top, $this->page_largeur - $this->marge_droite, $this->marge_top);
			// Droite
			// $pdf->Line($this->page_largeur - $this->marge_droite, $this->marge_top, $this->page_largeur - $this->marge_droite, $this->page_hauteur - $this->marge_basse-5);
			// Bas
			$pdf->Line($this->marge_gauche, $this->marge_top + 15, $this->page_largeur - $this->marge_gauche, $this->marge_top + 15);
			// Gauche
			// $pdf->Line($this->marge_gauche, $this->marge_top, $this->marge_gauche, $this->page_hauteur - $this->marge_basse-5);

			$pdf->SetLineWidth(0.0);
			$decallage = 0.0;
			// Haut
			// $pdf->Line($this->marge_gauche + $decallage, $this->marge_top + $decallage, $this->page_largeur - $this->marge_droite - $decallage, $this->marge_top + $decallage);
			// Droite
			// $pdf->Line($this->page_largeur - $this->marge_droite - $decallage, $this->marge_top + $decallage, $this->page_largeur - $this->marge_droite - $decallage, $this->page_hauteur - $this->marge_basse-5 - $decallage);
			// Bas
			// $pdf->Line($this->marge_gauche + $decallage, $this->page_hauteur - $this->marge_basse-5 - $decallage, $this->page_largeur - $this->marge_gauche - $decallage, $this->page_hauteur - $this->marge_basse-5 - $decallage);
			// Gauche
			// $pdf->Line($this->marge_gauche + $decallage, $this->marge_top + $decallage, $this->marge_gauche + $decallage, $this->page_hauteur - $this->marge_basse-5 - $decallage);

			$newY = $this->marge_haute + 32;
			$pdf->SetXY($this->marge_gauche + 20, $newY);
			$pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B,I', 20);
			$pdf->MultiCell(140, 3, $outputlangs->transnoentities('AgfPDFAttestationEnd1'), 0, 'C', 0);

			$newY=$pdf->GetY()+10;

			$pdf->SetFont('', '', 12);
			$pdf->SetXY($this->marge_gauche, $newY);
			$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
			$pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 4, $outputlangs->convToOutputCharset($this->emetteur->name) . $outputlangs->transnoentities('AgfPDFAttestationEnd2'), 0, 'L', 0);

			$newY = $newY + 5;
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);

			$contact_static = new Contact($this->db);
			$contact_static->civility_id = $agf_trainee->civilite;

			/*$this->str1 = ucfirst(strtolower($contact_static->getCivilityLabel())) . ' ';
			$this->width1 = $pdf->GetStringWidth($this->str1);

			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 16);
			$this->str2 = $outputlangs->transnoentities($agf_trainee->prenom . ' ' . $agf_trainee->nom);
			$this->width2 = $pdf->GetStringWidth($this->str2);*/

			/*$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
			$this->debut_cell = ($this->marge_gauche + 1) + ($this->milieu - (($this->width1 + $this->width2) / 2));
			$newY = $newY + 10;
			$pdf->SetXY($this->debut_cell, $newY);
			$pdf->Cell($this->width1, 0, $this->str1, 0, 0, 'C', 0);
			$pdf->SetXY($pdf->GetX(), $newY - 1.5);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 16);
			$pdf->Cell($this->width2, - 3, $this->str2, 0, 0, 'C', 0);*/

			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
			$newY = $newY + 10;
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$this->str = ' ' . $outputlangs->transnoentities('AgfPDFAttestation3');
			$pdf->Cell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C', 0);

			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 18);
			$newY = $newY + 7;
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 0, $outputlangs->transnoentities('« ' . $agf->intitule_custo . ' »'), 0, 'C', 0);
			$newY = $pdf->GetY();

			$this->str = $outputlangs->transnoentities('AgfPDFAttestation4') . " ";
			$this->str .= $agf->libSessionDate();
			$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFAttestation5') . " " . $agf->duree_session . $outputlangs->transnoentities('AgfPDFAttestation6');
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
			$newY = $newY + 7;
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$pdf->Cell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C', 0);

			if (count($agf_op->lines) > 0) {
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'U', 12);
				$this->str = $outputlangs->transnoentities('AgfPDFAttestationEndEval');
				$newY = $pdf->GetY() + 10;
				$pdf->SetXY($this->marge_gauche + 1, $newY);
				$pdf->Cell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, 0, 'L', 0);

				$tab_top=$newY+10;
				// Output Rect
				$pdf->SetLineWidth(0.0);
				$this->_tableau($pdf, $tab_top+3, 5, 0, $outputlangs, 0, 0);
			}

			$newY = $pdf->GetY();
			// Bloc objectifs pedagogiques
			if (count($agf_op->lines) > 0) {

				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', 10);
				$hauteur = 0;
				for($y = 0; $y < count($agf_op->lines); $y ++) {
					$strobj = $agf_op->lines[$y]->priorite . '. ' . $agf_op->lines[$y]->intitule;
					$pdf->SetXY($this->marge_gauche, $newY);
					$width = $this->posxacquired - $this->marge_gauche;
					$StringWidth = $pdf->GetStringWidth($strobj);
					if ($StringWidth > $width)
						$nblines = ceil($StringWidth / $width);
					else
						$nblines = 1;

					$beforeY = $pdf->GetY();
					$pdf->MultiCell($width, 0, $outputlangs->transnoentities($strobj), 0, 'L', 0);

					$afterY = $pdf->GetY();

					$height_obj=$afterY-$beforeY;
					$this->printRect($pdf,$this->marge_gauche, $beforeY, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_obj, $hidetop, $hidebottom);	// Rect prend une longueur en 3eme param et 4eme param
					$pdf->line($this->posxacquired-1, $beforeY, $this->posxacquired-1, $beforeY + $height_obj);

					$pdf->line($this->posxnonacquired-1, $beforeY, $this->posxnonacquired-1, $beforeY + $height_obj);
					$pdf->line($this->posxongoing-1, $beforeY, $this->posxongoing-1, $beforeY + $height_obj);
					$pdf->line($this->posxnotevaluated-1, $beforeY, $this->posxnotevaluated-1, $beforeY + $height_obj);

					$newY = $pdf->GetY();

					$pdf->SetXY($this->posxacquired+4, $beforeY);
					if (empty($conf->global->AGF_ATTESTION_PDF_DEFAULT_NOTAQUIS)) {
						$pdf->MultiCell($width, 0, $outputlangs->transnoentities('X'), 0, 'L', 0);
					} else {
						$pdf->MultiCell($width, 0, '', 0, 'L', 0);
					}

				}
			}

			// Lieu
			$newY = $pdf->GetY() + 10;
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'U', 12);
			$this->str = $outputlangs->transnoentities('Lieu :');
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$pdf->Cell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, 0, 'L', 0);
			$pdf->SetXY(50, $newY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
			$this->str = $agf_place->ref_interne . ", " . $agf_place->adresse . ", " . $agf_place->cp . ", " . $agf_place->ville;
			$pdf->MultiCell(60, 3, $outputlangs->convToOutputCharset($this->str), 0, 'C', 0);

			$newY = $pdf->GetY() + 10;
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'U', 12);
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$this->str = $outputlangs->transnoentities('AgfPDFAttestation10');
			$pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->str), 0, 'L', 0);

			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
			$newY = $pdf->GetY();
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$this->str = $outputlangs->transnoentities('AgfPDFAttestation8') . " " . $mysoc->name . ",";
			// $pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C', 0);

			$newY = $pdf->GetY() + 10;
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$this->str = $mysoc->town . ", " . $outputlangs->transnoentities('AgfPDFFichePres8');
			$this->str2 = date("d/m/Y");
			$this->str2 = dol_print_date($agf->datef);
			$pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->str) . ' ' . $outputlangs->convToOutputCharset($this->str2), 0, 'L', 0);

			$newY = $pdf->GetY()+10;
			$pdf->SetXY($this->page_largeur - $this->marge_gauche - $this->marge_droite - 55, $newY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
			//Formateur
			$trainer_arr=array();
			foreach($agf_session_trainer->lines as $trainer) {
				$trainer_arr[]=$trainer->firstname ." ". $trainer->lastname;
			}
			$trainer_str=implode("\n",$trainer_arr);
			$pdf->MultiCell(80, 0, $outputlangs->transnoentities('AgfTrainerPDF').':'.$trainer_str, 0, 'L', 0);

			// Incrustation image tampon
			$tampon_exitst = 1;
			if ($conf->global->AGF_INFO_TAMPON) {
				$dir = $conf->agefodd->dir_output . '/images/';
				$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
				if (file_exists($img_tampon)) {
					$newY = $pdf->GetY();
					$pdf->Image($img_tampon, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 55, $newY, 50);
					$tampon_exitst = 22;
				}
			}

			$newY = $pdf->GetY() + $tampon_exitst;

			$pdf->SetFont('', 'I', 9);
			$pdf->SetXY($this->marge_gauche, $newY);
			$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
			$pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 4, $outputlangs->transnoentities('AgfPDFAttestationEnd3'), 0, 'L', 0);
			$newY = $pdf->GetY() + 8;
			$pdf->SetFont('', 'I,B', 9);
			$pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 4, $outputlangs->transnoentities('AgfPDFAttestationEnd4'), 0, 'L', 0);

			// Pied de page $pdf->SetFont(pdf_getPDFFont($outputlangs),'', 10);
			$this->_pagefoot($pdf, $agf, $outputlangs);
			// FPDI::AliasNbPages() is undefined method into Dolibarr 3.5
			if (method_exists($pdf, 'AliasNbPages')) {
				$pdf->AliasNbPages();
			}

			// Mise en place du copyright
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
			$this->str = $outputlangs->transnoentities('copyright ' . date("Y") . ' - ' . $mysoc->name);
			$this->width = $pdf->GetStringWidth($this->str);
			// alignement du bord droit du container avec le haut de la page
			$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->width;
			$baseline_angle = (M_PI / 2); // angle droit
			$baseline_x = $this->page_largeur - $this->marge_gauche - 12;
			$baseline_y = $baseline_ecart + 30;
			$baseline_width = $this->width;

			$pdf->Close();
			$pdf->Output($file, 'F');
			if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));


			// Add pdfgeneration hook
			if (! is_object($hookmanager))
			{
				include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
				$hookmanager=new HookManager($this->db);
			}
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters=array('file'=>$file,'object'=>$agf,'outputlangs'=>$outputlangs);
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
	 * Show table for lines
	 *
	 * @param object $pdf PDF
	 * @param string $tab_top of table
	 * @param string $tab_height table (rectangle)
	 * @param int $nexY used)
	 * @param Translate $outputlangs
	 * @param int $hidetop bar of array and title, 0=Hide nothing, -1=Hide only title
	 * @param int $hidebottom bar of array
	 * @return void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0) {
		global $conf;
		// $tab_height=80;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Output Rect
		$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect prend une longueur en 3eme param et 4eme param

		// Objectifs (Objectif Peda)
		//$pdf->line($this->marge_gauche, $tab_top + 6, $this->page_largeur - $this->marge_droite, $tab_top + 6); // line prend une position y en 2eme param et 4eme param
		$pdf->SetXY($this->posxdesc - 1, $tab_top + 1);
		$pdf->MultiCell(108, 2, $outputlangs->transnoentities("AgfObjectifs"), '', 'L');

		// Acquired
		$pdf->line($this->posxacquired - 1, $tab_top, $this->posxacquired - 1, $tab_top + $tab_height);
		$pdf->SetXY($this->posxacquired - 3, $tab_top + 1);
		$pdf->MultiCell($this->posxnonacquired - $this->posxacquired + 3, 2, $outputlangs->transnoentities("AgfAcquis"), '', 'C');

		// Non Acquired
		$pdf->line($this->posxnonacquired - 1, $tab_top, $this->posxnonacquired - 1, $tab_top + $tab_height);
		$pdf->SetXY($this->posxnonacquired - 3, $tab_top + 1);
		$pdf->MultiCell($this->posxongoing - $this->posxnonacquired + 3, 2, $outputlangs->transnoentities("AgfNonAcquis"), '', 'C');

		// On going
		$pdf->line($this->posxongoing - 1, $tab_top, $this->posxongoing - 1, $tab_top + $tab_height);
		$pdf->SetXY($this->posxongoing - 3, $tab_top + 1);
		$pdf->MultiCell($this->posxnotevaluated - $this->posxongoing + 3, 2, $outputlangs->transnoentities("AgfEncours"), '', 'C');

		// Not evaluated
		$pdf->line($this->posxnotevaluated - 1, $tab_top, $this->posxnotevaluated - 1, $tab_top + $tab_height);
		$pdf->SetXY($this->posxnotevaluated - 1, $tab_top + 1);
		$pdf->MultiCell(20, 2, $outputlangs->transnoentities("AgfNotEvaluated"), '', 'C');
	}

	/**
	 * \brief Show header of page
	 * \param pdf Object PDF
	 * \param object Object invoice
	 * \param showaddress 0=no, 1=yes
	 * \param outputlangs Object lang for output
	 */
	function _pagehead(&$pdf, $object, $showaddress = 1, $outputlangs) {
		global $conf, $langs;

		$outputlangs->load("main");

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Fill header with background color
		$pdf->SetFillColor($this->colorheaderBg[0], $this->colorheaderBg[1], $this->colorheaderBg[2]);
		$pdf->MultiCell($this->page_largeur, 40, '', 0, 'L', true, 1, 0, 0);

		pdf_pagehead($pdf, $outputlangs, $pdf->page_hauteur);

		$pdf->SetTextColor($this->colorheaderText[0], $this->colorheaderText[1], $this->colorheaderText[2]);

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - 55;

		// Logo
		$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
		if ($this->emetteur->logo) {
			if (is_readable($logo)) {
				$height = pdf_getHeightForLogo($logo);
				$width_logo = pdf_getWidthForLogo($logo);
				if ($width_logo > 0) {
					$posx = $this->page_largeur - $this->marge_droite - $width_logo;
				} else {
					$posx = $this->page_largeur - $this->marge_droite - 55;
				}
				$pdf->Image($logo, $posx, $posy, 0, $height);
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$text = $this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}
		// Other Logo
		if ($conf->multicompany->enabled && !empty($conf->global->AGF_MULTICOMPANY_MULTILOGO)) {
			$sql = 'SELECT value FROM ' . MAIN_DB_PREFIX . 'const WHERE name =\'MAIN_INFO_SOCIETE_LOGO\' AND entity=1';
			$resql = $this->db->query($sql);
			if (! $resql) {
				setEventMessage($this->db->lasterror, 'errors');
			} else {
				$obj = $this->db->fetch_object($resql);
				$image_name = $obj->value;
			}
			if (! empty($image_name)) {
				$otherlogo = DOL_DATA_ROOT . '/mycompany/logos/' . $image_name;
				if (is_readable($otherlogo) && $otherlogo!=$logo) {
					$logo_height = pdf_getHeightForLogo($otherlogo, true);
					$pdf->Image($otherlogo, $this->marge_gauche + 80, $posy, 0, $logo_height);
				}
			}
		}
		if ($showaddress) {
			// Sender properties
			// Show sender
			$posy = $this->marge_haute;
			$posx = $this->marge_gauche;

			$hautcadre = 30;
			$pdf->SetXY($posx, $posy);
			$pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);

			// Show sender name
			$pdf->SetXY($posx, $posy);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy = $pdf->GetY();

			// Show sender information
			$pdf->SetXY($posx, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
			$posy = $pdf->GetY();
			$pdf->SetXY($posx, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->zip . ' ' . $this->emetteur->town), 0, 'L');
			$posy = $pdf->GetY();
			$pdf->SetXY($posx, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
			$posy = $pdf->GetY();
			$pdf->SetXY($posx, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');
			$posy = $pdf->GetY();

			printRefIntForma($this->db, $outputlangs, $object, $default_font_size - 1, $pdf, $posx, $posy, 'L');
		}
	}

	/**
	 * \brief Show footer of page
	 * \param pdf PDF factory
	 * \param object Object invoice
	 * \param outputlang Object lang for output
	 * \remarks Need this->emetteur object
	 */
	function _pagefoot(&$pdf, $object, $outputlangs) {
		$pdf->SetTextColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
		$pdf->SetDrawColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
		return pdf_agfpagefoot($pdf,$outputlangs,'',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,1,$hidefreetext);
	}
}