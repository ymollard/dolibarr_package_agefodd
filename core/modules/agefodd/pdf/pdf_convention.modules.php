<?php
/* Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2012-2014       Florian Henry   <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file agefodd/core/modules/agefodd/pdf/pdf_convention.modules.php
 * \ingroup agefodd
 * \brief PDF for contract / convention
 */
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_session_element.class.php');
dol_include_once('/agefodd/class/agefodd_contact.class.php');
dol_include_once('/agefodd/class/agefodd_convention.class.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
dol_include_once('/agefodd/class/agefodd_place.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php');
class pdf_convention extends ModelePDFAgefodd
{
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

		$langs->load("agefodd@agefodd");

		$this->db = $db;
		$this->name = "convention";
		$this->description = $langs->trans('AgfModPDFConvention');

		// Dimension page pour format A4 en portrait
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array(
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

		$this->hApresTitreArticle = 7;
		$this->hApresCorpsArticle = 8;

		$this->colorfooter = agf_hex2rgb($conf->global->AGF_FOOT_COLOR);
		$this->colortext = agf_hex2rgb($conf->global->AGF_TEXT_COLOR);
		$this->colorhead = agf_hex2rgb($conf->global->AGF_HEAD_COLOR);
		$this->colorheaderBg = agf_hex2rgb($conf->global->AGF_HEADER_COLOR_BG);
		$this->colorheaderText = agf_hex2rgb($conf->global->AGF_HEADER_COLOR_TEXT);
		$this->colorLine = agf_hex2rgb($conf->global->AGF_COLOR_LINE);

		$this->defaultFontSize = 9;

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
	 * \return int 1=ok, 0=k
	 */
	function write_file($agf, $outputlangs, $file, $socid, $courrier) {
		global $user, $langs, $conf, $mysoc, $db;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		if (! is_object($outputlangs))
			$outputlangs = $langs;

		$outputlangs->load('agefodd@agefodd');

		if (! is_object($agf)) {
			$id = $agf;
			// On récupere le contenu de la convention
			$agf_conv = new Agefodd_convention($this->db);
			$result = $agf_conv->fetch(0, 0, $id);
			$agf = new Agsession($this->db);
			$ret = $agf->fetch($agf_conv->sessid);
		}

		// Definition of $dir and $file
		$dir = $conf->agefodd->dir_output;
		$fileori = $file;
		$file = $dir . '/' . $file;

		if (! file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		}

		if (file_exists($dir)) {
			$pdf = pdf_getInstance($this->format, $this->unit, $this->orientation);

			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}
			$pdf->SetFont(pdf_getPDFFont($outputlangs));

			$pdf->Open();
			$pagenb = 0;

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
			if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->AGF_ADD_PDF_BACKGROUND_P)) {
				$pagecount = $pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P);
				$tplidx = $pdf->importPage(1);
			}

			$result = true;
			$agf_comdetails = new Agefodd_convention($this->db);
			$agf_comdetails->fetch(0, 0, $agf_conv->id);
			if ($agf_conv->element_type == 'invoice') {
				$result = $agf_comdetails->fetch_invoice_lines($agf_conv->fk_element);
			}
			if ($agf_conv->element_type == 'order') {
				$result = $agf_comdetails->fetch_order_lines($agf_conv->fk_element);
			}
			if ($agf_conv->element_type == 'propal') {
				$result = $agf_comdetails->fetch_propal_lines($agf_conv->fk_element);
			}
			if (empty($agf_conv->element_type) && empty($conf->global->AGF_ALLOW_CONV_WITHOUT_FINNACIAL_DOC)) {
				$result = false;
			}
			if ($result) {
				/*
				 * Page de garde
				 */

				// New page
				$pdf->AddPage();

				if (! empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}

				$pagenb ++;

				// Fill header with background color
				$pdf->SetFillColor($this->colorheaderBg[0], $this->colorheaderBg[1], $this->colorheaderBg[2]);
				$pdf->MultiCell($this->page_largeur, 40, '', 0, 'L', true, 1, 0, 0);

				$this->_pagehead($pdf, $agf, 1, $outputlangs);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
				$pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3
				$pdf->SetTextColor($this->colorheaderText[0], $this->colorheaderText[1], $this->colorheaderText[2]);

				$posY = $this->marge_haute;
				$posX = $this->marge_gauche;

				// Logo en haut à gauche
				$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
				$width_logo = pdf_getWidthForLogo($logo);
				if ($this->emetteur->logo) {
					if (is_readable($logo)) {
						$height = pdf_getHeightForLogo($logo);
						$width_logo = pdf_getWidthForLogo($logo);
						if ($width_logo > 0) {
							$posX = $this->page_largeur - $this->marge_droite - $width_logo;
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

				// $posX += $this->page_largeur - $this->marge_droite - 65;

				$posy = $this->marge_haute;
				$posx = $this->marge_gauche;

				$hautcadre = 30;
				$pdf->SetXY($posx, $posy);
				$pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);

				// Show sender name
				$pdf->SetXY($posx, $posy);
				$pdf->SetFont('', 'B', $this->defaultFontSize);
				$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
				$posy = $pdf->GetY();

				// Show sender information
				$pdf->SetXY($posx, $posy);
				$pdf->SetFont('', '', $this->defaultFontSize - 1);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
				$posy = $pdf->GetY();
				$pdf->SetXY($posx, $posy);
				$pdf->SetFont('', '', $this->defaultFontSize - 1);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->zip . ' ' . $this->emetteur->town), 0, 'L');
				$posy = $pdf->GetY();
				$pdf->SetXY($posx, $posy);
				$pdf->SetFont('', '', $this->defaultFontSize - 1);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
				$posy = $pdf->GetY();
				$pdf->SetXY($posx, $posy);
				$pdf->SetFont('', '', $this->defaultFontSize - 1);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');
				$posy = $pdf->GetY();

				printRefIntForma($this->db, $outputlangs, $agf, $this->defaultFontSize - 1, $pdf, $posx, $posy, 'L');

				$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);

				$posY = $pdf->GetY() + 10;
				if ($conf->global->AGF_PRINT_INTERNAL_REF_ON_PDF) {
					$posY -= 4;
				}

				$pdf->SetTextColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
				$pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);

				// Mise en page de la baseline
				$pdf->SetFont('', '', $this->defaultFontSize - 1);
				$pdf->SetTextColor($this->colorheaderText[0], $this->colorheaderText[1], $this->colorheaderText[2]);
				$this->str = $outputlangs->transnoentities($mysoc->url);
				$pdf->MultiCell(70, 4, $this->str, 0, 'L');
				$this->width = $pdf->GetStringWidth($this->str);

				// alignement du bord droit du container avec le haut de la page
				$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->width;
				$baseline_angle = (M_PI / 2); // angle droit
				$baseline_x = 8;
				$baseline_y = $this->espaceV_dispo - $baseline_ecart + 30;
				$baseline_width = $this->width;
				$pdf->SetXY($baseline_x, $baseline_y);

				// Affichage du logo commanditaire (optionnel)
				if ($conf->global->AGF_USE_LOGO_CLIENT) {
					$staticsoc = new Societe($this->db);
					$staticsoc->fetch($agf_conv->socid);
					$dir = $conf->societe->multidir_output[$staticsoc->entity] . '/' . $staticsoc->id . '/logos/';
					if (! empty($staticsoc->logo)) {
						$logo_client = $dir . $staticsoc->logo;
						if (file_exists($logo_client) && is_readable($logo_client)) {
							$hlogo = pdf_getHeightForLogo($logo_client);
							$wlogo = pdf_getWidthForLogo($logo_client);
							$X =  ($this->page_largeur / 2) - ($wlogo / 2) ;
							$Y = $this->marge_haute;
							$pdf->Image($logo_client,$X ,$Y, $wlogo, $hlogo,'','','',true);
						}
					}
				}

				// TItre page de garde 1
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 25);
				$pdf->SetXY($this->marge_gauche, $this->marge_haute + 110);

				$customer = new Societe($this->db);
				$customer->fetch($socid);
				// If customer is personnal entity, the french low ask contrat and not convention
				if ($customer->typent_id == 8) {
					$titre = $outputlangs->transnoentities('AgfPDFConventionContrat');
				} else {
					$titre = $outputlangs->transnoentities('AgfPDFConvention');
				}
				$pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
				$pdf->MultiCell(0, 5, $titre, 0, 'C');

				// TItre page de garde 2
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 10);
				$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
				$pdf->SetXY($this->marge_gauche, $this->marge_haute + 130);

				// If customer is personnal entity, the french low ask contrat and not convention
				if ($customer->typent_id == 8) {
					$titre = $outputlangs->transnoentities('AgfPDFConventionContratLawNum');
				} else {
					$titre = $outputlangs->transnoentities('AgfPDFConventionLawNum');
				}
				$pdf->MultiCell(0, 5, $titre, 0, 'C');

				$this->str = $agf->intitule_custo;
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
				$pdf->SetXY($this->marge_gauche, $this->marge_haute + 180);
				$pdf->MultiCell(0, 5, $this->str, 0, 'C');

				if ($agf->dated != $agf->datef) {
					$this->str = dol_print_date($agf->dated, 'daytext') . ' - ' . dol_print_date($agf->datef, 'daytext');
				} else {
					$this->str = dol_print_date($agf->dated, 'daytext');
				}
				$pdf->SetY($pdf->getY() + 10);
				$pdf->SetX($this->marge_gauche);
				$pdf->MultiCell(0, 5, $this->str, 0, 'C');

				// If customer is personnal entity, the french low ask contrat and not convention
				if ($customer->typent_id == 8) {
					$this->str = $outputlangs->transnoentities('AgfConvTrainees') . ': ' . $customer->name; // Trainer Name;
				} else {
					$this->str = $customer->name; // Customer Name;
				}
				$pdf->SetXY($this->marge_gauche, $this->marge_haute + 190);
				$pdf->MultiCell(0, 5, $this->str, 0, 'C');

				$pdf->SetXY($this->marge_gauche, $this->marge_haute + 205);
				$pdf->MultiCell(0, 5, $outputlangs->trans('AgfConvention') . ' N°:' . str_replace('.pdf', '', str_replace('convention_', ' ', $fileori)), 0, 'C');

				// Determine the total number of page
				$agfTraining = new Formation($db);
				$agfTraining->fetch($agf->fk_formation_catalogue);
				$agfTraining->generatePDAByLink();
				$infile = $conf->agefodd->dir_output . '/fiche_pedago_' . $agf->fk_formation_catalogue . '.pdf';
				if (is_file($infile)) {
					$this->count_page_anexe = $pdf->setSourceFile($infile);
					$this->count_page_anexe = $this->count_page_anexe - 1;
				} else {
					$this->count_page_anexe = 0;
				}

				// Pied de page
				$hauteurpied = $this->_pagefoot($pdf, $agf, $outputlangs);

				/*
				 * Page 1
				 */

				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}

				$pagenb ++;
				$this->_pagehead($pdf, $agf, 1, $outputlangs);
				$this->defaultFontSize = 9;
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
				$pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3
				$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
				$posX = $this->marge_gauche;
				$posY = $this->marge_haute;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize);
				$this->str = $outputlangs->transnoentities('AgfPDFConv1');
				$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $pdf->GetY() + 1;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
				$this->str = $agf_conv->intro1;
				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $pdf->GetY() + 1;

				$pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfPDFConv2');
				$pdf->Cell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, 0);
				$posY += 8;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize);
				$this->str = $outputlangs->transnoentities('AgfPDFConv3');
				$pdf->Cell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, 0);
				$posY += 4;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
				$this->str = $agf_conv->intro2;
				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');

				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

				$pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfPDFConv4');
				$pdf->Cell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, 0);
				$posY += 4;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize);
				$this->str = $outputlangs->transnoentities('AgfPDFConv5') . " ";
				$this->str .= $outputlangs->transnoentities('AgfPDFConv6');
				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize + 3);
				$art = 0;
				$this->str = $outputlangs->transnoentities('AgfPDFConv7') . ' ' . ++ $art . " - " . $outputlangs->transnoentities('AgfPDFConv8') . ' ';
				$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
				$this->str = $outputlangs->transnoentities('AgfPDFConv9') . ' ';
				$this->str .= $outputlangs->transnoentities('AgfPDFConv10');
				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv7') . ' ' . ++ $art . " - " . $outputlangs->transnoentities('AgfPDFConv11');
				$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
				$this->str = $agf_conv->art1;

				if (preg_match('/Nb_participants/', $this->str)) {
					if (is_array($agf_conv->line_trainee) && count($agf_conv->line_trainee) > 0) {
						$nbstag = count($agf_conv->line_trainee);
						$nbstag .= ' ' . $langs->trans('AgfConvArt1_15');
						$this->str = str_replace('Nb_participants', $nbstag, $this->str);
					} else {
						$nbstag = $langs->transnoentities('AgfConvArt3_5');
						$this->str = str_replace('Nb_participants', $nbstag, $this->str);
					}
				}
				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

				if (! empty($conf->global->AGF_ADD_PROGRAM_TO_CONV)) {
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize + 3);
					$this->str = $outputlangs->transnoentities('AgfPDFConv7') . ' ' . ++ $art . " - " . $outputlangs->transnoentities('AgfPDFConv12');
					$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 0);
					$posY += $this->hApresTitreArticle;

					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
					$this->str = $agf_conv->art2;
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + $this->hApresCorpsArticle;
				}

				$pdf->startTransaction();
				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv7') . ' ' . ++ $art . " - " . $outputlangs->transnoentities('AgfPDFConv13');
				$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
				if (preg_match('/List_Participants/', $agf_conv->art3)) {
					if (is_array($agf_conv->line_trainee) && count($agf_conv->line_trainee) > 0) {
						$nbstag = count($agf_conv->line_trainee);
						$stagiaires_session_conv = new Agefodd_session_stagiaire($this->db);

						foreach ( $agf_conv->line_trainee as $trainee_session_id ) {
							$result = $stagiaires_session_conv->fetch($trainee_session_id);
							if ($result < 0) {
								setEventMessage($stagiaires->error, 'errors');
							}
							$stagiaire_conv = new Agefodd_stagiaire($this->db);
							$result = $stagiaire_conv->fetch($stagiaires_session_conv->fk_stagiaire);
							if ($result < 0) {
								setEventMessage($stagiaire_conv->error, 'errors');
							}
							$traine_list[] = $stagiaire_conv->nom . ' ' . $stagiaire_conv->prenom;
						}
					}
					if (count($traine_list) > 0) {
						if (count($traine_list) > 1) {
							$trainee_list_str = ' ' . $langs->trans('AgfConvArt3_2') . ' ' . implode(', ', $traine_list);
						} else {
							$trainee_list_str = ' ' . $langs->trans('AgfConvArt3_3') . ' ' . implode(', ', $traine_list);
						}
					} else if (count($traine_list) == 0) {
						$trainee_list_str = $langs->transnoentities('AgfConvArt3_5');
					}

					$art3 = str_replace('List_Participants', $trainee_list_str, $agf_conv->art3);
				} else {
					$art3 = $agf_conv->art3;
				}
				$this->str = $art3;
				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

				if ($posY > $this->page_hauteur - $hauteurpied) {
					$pdf = $pdf->rollbackTransaction();
					$art --;
					$this->_pagefoot($pdf, $agf, $outputlangs);

					// New page
					$pdf->AddPage();
					if (! empty($tplidx)) {
						$pdf->useTemplate($tplidx);
					}

					$pagenb ++;
					$this->_pagehead($pdf, $agf, 1, $outputlangs);
					$this->defaultFontSize = 9;
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
					$pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3
					$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
					$posX = $this->marge_gauche;
					$posY = $this->marge_haute;

					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize + 3);
					$this->str = $outputlangs->transnoentities('AgfPDFConv7') . ' ' . ++ $art . " - " . $outputlangs->transnoentities('AgfPDFConv13');
					$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 0);
					$posY += $this->hApresTitreArticle;

					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);

					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($art3), 0, 'L');
					$posY = $pdf->GetY() + $this->hApresCorpsArticle;
				} else {

					// Pied de page
					$this->_pagefoot($pdf, $agf, $outputlangs);

					/*
					 * Page 2
					 */

					// New page
					$pdf->AddPage();
					if (! empty($tplidx)) {
						$pdf->useTemplate($tplidx);
					}

					$pagenb ++;
					$this->_pagehead($pdf, $agf, 1, $outputlangs);
					$this->defaultFontSize = 9;
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
					$pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3
					$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
					$posX = $this->marge_gauche;
					$posY = $this->marge_haute;
				}

				if (empty($conf->global->AGF_ALLOW_CONV_WITHOUT_FINNACIAL_DOC)) {
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize + 3);
					$this->str = $outputlangs->transnoentities('AgfPDFConv7') . ' ' . ++ $art . ' - ' . $outputlangs->transnoentities('AgfPDFConv14');
					$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 0);
					$posY += $this->hApresTitreArticle;

					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
					$this->str = $agf_conv->art4;
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$hauteur = dol_nboflines_bis($this->str, 50) * 3;
					$posY += $hauteur + 2;

					// Tableau "bon de commande"
					$pdf->SetXY($posX, $posY);
					$pdf->SetFillColor($this->color1[0], $this->color1[1], $this->color1[2]);
					$pdf->SetFillColor(210, 210, 210);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize - 1);
					$header = array(
							$outputlangs->transnoentities("Designation"),
							$outputlangs->transnoentities("VAT"),
							$outputlangs->transnoentities("PriceUHT"),
							$outputlangs->transnoentities("ReductionShort"),
							$outputlangs->transnoentities('Qté'),
							$langs->transnoentities("TotalHT"),
							$langs->transnoentities("TotalTTC")
					);
					$w = array(
							80,
							13,
							19,
							13,
							8,
							20,
							20
					);
					for($i = 0; $i < count($header); $i ++) {
						$pdf->Cell($w[$i], 6, $header[$i], 1, 0, 'C', 1);
					}
					$posY += 6;
					$fill = false;
					$total_ht = 0;
					$total_tva = 0;
					$total_ttc = 0;
					for($i = 0; $i < count($agf_comdetails->lines); $i ++) {
						$pdf->SetXY($posX, $posY);
						$posY = $pdf->GetY();
						$pdf->writeHTMLCell($w[0], 0, $posX, $posY, $outputlangs->transnoentities($agf_comdetails->lines[$i]->description), 1, 1);
						$posY_after = $pdf->GetY();
						$hauteur = ($posY_after - $posY);

						$pdf->SetXY($posX + $w[0], $posY);
						$pdf->Cell($w[1], $hauteur, vatrate($agf_comdetails->lines[$i]->tva_tx, 1), 1, 0, 'C', $fill);
						$pdf->Cell($w[2], $hauteur, price($agf_comdetails->lines[$i]->price, 0, $outputlangs, 1, - 1, 2), 1, 0, 'R', $fill);
						$pdf->Cell($w[3], $hauteur, dol_print_reduction($agf_comdetails->lines[$i]->remise_percent, $outputlangs), 1, 0, 'R', $fill);
						$pdf->Cell($w[4], $hauteur, $agf_comdetails->lines[$i]->qty, 1, 0, 'C', $fill);
						$pdf->Cell($w[5], $hauteur, price($agf_comdetails->lines[$i]->total_ht, 0, $outputlangs), 1, 0, 'R', $fill);
						$pdf->Cell($w[6], $hauteur, price($agf_comdetails->lines[$i]->total_ttc, 0, $outputlangs), 1, 0, 'R', $fill);

						$pdf->Ln();
						$posY = $pdf->GetY();

						$total_ht += $agf_comdetails->lines[$i]->total_ht;
						$total_tva += $agf_comdetails->lines[$i]->total_tva;
						$total_ttc += $agf_comdetails->lines[$i]->total_ttc;
					}

					$pdf->SetXY($posX, $posY);
					$pdf->Cell(array_sum($w), 0, '', 'T');
					// $posY += 6;

					// total HT
					$pdf->SetXY($posX + array_sum($w) - $w[5] - $w[6], $posY);
					$pdf->Cell($w[5], 5, $langs->transnoentities("TotalHT"), 0, 0, 'R', 1);
					$pdf->Cell($w[6], 5, price($total_ht, 0, $outputlangs), 1, 0, 'R');
					$posY += 6;
					// total TVA
					$pdf->SetXY($posX + array_sum($w) - $w[5] - $w[6], $posY);
					$pdf->Cell($w[5], 5, $langs->transnoentities("TotalVAT"), 0, 0, 'R', 1);
					$pdf->Cell($w[6], 5, price($total_tva, 0, $outputlangs), 1, 0, 'R');
					$posY += 6;
					// total TTC
					$pdf->SetXY($posX + array_sum($w) - $w[5] - $w[6], $posY);
					$pdf->Cell($w[5], 5, $langs->transnoentities("TotalTTC"), 0, 0, 'R', 1);
					$pdf->Cell($w[6], 5, price($total_ttc, 0, $outputlangs), 1, 0, 'R');
					$posY += 5;
					// txt "montant euros"
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', $this->defaultFontSize - 2);
					$pdf->Cell(0, 4, $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency" . $conf->currency)), 0, 0, 'R', 0);
					$posY += $this->hApresCorpsArticle + 4;
				}

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv7') . ' ' . ++ $art . " - " . $outputlangs->transnoentities('AgfPDFConv28');
				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
				$this->str = $agf_conv->art9;
				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv7') . ' ' . ++ $art . " - " . $outputlangs->transnoentities('AgfPDFConv15');
				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
				$this->str = $agf_conv->art5;

				if (preg_match('/List_OPCA/', $this->str)) {

					$TOPCA = array();

					if (! empty($agf->type_session)) { // Session inter-entreprises : OPCA gérés par participant
						dol_include_once('/agefodd/class/agefodd_opca.class.php');

						foreach ( $agf_conv->line_trainee as $idSessionTrainee ) {
							$sessionTrainee = new Agefodd_session_stagiaire($db);
							$sessionTrainee->fetch($idSessionTrainee);

							$trainee = new Agefodd_stagiaire($db);
							$trainee->fetch($sessionTrainee->fk_stagiaire);

							$opca = new Agefodd_opca($db);
							$opca->getOpcaForTraineeInSession($trainee->socid, $agf->id, $idSessionTrainee);

							if (! empty($opca->soc_OPCA_name)) {
								$TOPCA[] = $opca->soc_OPCA_name;
							}
						}
					} elseif (! empty($agf->soc_OPCA_name)) {
						$TOPCA[] = $agf->soc_OPCA_name;
					}

					if (! empty($TOPCA)) {
						$listOPCA = implode(', ', $TOPCA);
					} else {
						$listOPCA = $outputlangs->trans('AgfEmptyListOPCA');
					}

					$this->str = str_replace('List_OPCA', $listOPCA, $this->str);
				}

				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

				// Pied de page
				$this->_pagefoot($pdf, $agf, $outputlangs);

				/*
				 * Page 3
				 */

				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}

				$pagenb ++;
				$this->_pagehead($pdf, $agf, 1, $outputlangs);

				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
				$pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3
				$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
				$posX = $this->marge_gauche;
				$posY = $this->marge_haute;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv7') . ' ' . ++ $art . " - " . $outputlangs->transnoentities('AgfPDFConv16');
				$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
				$this->str = $agf_conv->art6;
				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'J');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv7') . ' ' . ++ $art . " - " . $outputlangs->transnoentities('AgfPDFConv17');
				$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
				$this->str = $agf_conv->art7;
				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv18');
				$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
				$literal = array(
						'un',
						'deux',
						'trois',
						'quatre',
						'cinq',
						'six',
						'sept',
						'huit',
						'neuf',
						'dix',
						'onze',
						'douze'
				);
				// Date

				$date = $outputlangs->transnoentities('AgfPDFConv19') . ' ' . dol_print_date(dol_now(), 'daytext') . ' ';

				$this->str = $outputlangs->transnoentities('AgfPDFConv20') . ' ' . $mysoc->town . ', ' . $date . $outputlangs->transnoentities('AgfPDFConv21') . ' ';
				$nombre = $pdf->PageNo(); // page suivante = annexe1
				$this->str .= $outputlangs->transnoentities('AgfPDFConv22') . " " . $literal[$nombre - 1] . " (" . $nombre . ") " . $outputlangs->transnoentities('AgfPDFConv23') . ' ';
				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

				// Entete signature
				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize);
				$this->str = $outputlangs->transnoentities('AgfPDFConv24');
				$pdf->Cell($this->espaceH_dispo / 2, 4, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');

				$pdf->SetXY($this->milieu, $posY);
				$this->str = $outputlangs->transnoentities('AgfPDFConv25');
				$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');

				$posY += 6;

				// signature de l'organisme de formation
				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
				$this->str = $mysoc->name . "\n" . $langs->transnoentities('AgfConvArtSigOrg') . " " . $conf->global->AGF_ORGANISME_REPRESENTANT . " (*)";
				$pdf->MultiCell($this->espaceH_dispo / 2, 4, $outputlangs->convToOutputCharset($this->str), 0, 'C');
				$hauteurA = dol_nboflines_bis($this->str, 50) * 3;

				// Incrustation image tampon
				if ($conf->global->AGF_INFO_TAMPON) {
					$dir = $conf->agefodd->dir_output . '/images/';
					$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
					if (file_exists($img_tampon))
						$pdf->Image($img_tampon, $posX + $this->marge_gauche, $pdf->GetY() + 6, 60);
				}

				// signature du client
				$pdf->SetXY($this->milieu, $posY);
				if (! empty($agf_conv->sig))
					$this->str = $agf_conv->sig;

				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'C');
				$hauteurB = dol_nboflines_bis($this->str, 50) * 3;
				$hauteur = max($hauteurA, $hauteurB);
				$posY += $hauteur + 40;

				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', $this->defaultFontSize - 3);
				$this->str = "(*) " . $outputlangs->transnoentities('AgfPDFConv26') . ' ';
				$this->str .= $outputlangs->transnoentities('AgfPDFConv27');
				$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');
				$posY += 4 + $this->hApresCorpsArticle;

				// Pied de page
				$this->_pagefoot($pdf, $agf, $outputlangs);

				/*
				 * Page 4 (Annexe 1)
				 */
				if (! empty($conf->global->AGF_ADD_PROGRAM_TO_CONV)) {
					$agfTraining = new Formation($db);
					$agfTraining->fetch($agf->fk_formation_catalogue);
					$agfTraining->generatePDAByLink();
                    $addFile = '';
					$infile = $conf->agefodd->dir_output . '/fiche_pedago_' . $agf->fk_formation_catalogue . '.pdf';
                    $infileModules = $conf->agefodd->dir_output . '/fiche_pedago_modules_' . $agf->fk_formation_catalogue . '.pdf';
                    if (is_file($infile)) {
                        $addFile = $infile;
                    } elseif (is_file($infileModules)) {
                        $addFile = $infileModules;
                    }
                    if (!empty($addFile)) {
						$count = $pdf->setSourceFile($addFile);
						// import all page
						for($i = 1; $i <= $count; $i ++) {
							// New page
							$pdf->AddPage();
							$pagenb ++;
							$this->_pagehead($pdf, $agf, 1, $outputlangs);
							$this->defaultFontSize = 9;
							$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
							$pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3
							$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
							$posX = $this->marge_gauche;
							$posY = $this->marge_haute;

							$tplIdx = $pdf->importPage($i);
							$pdf->useTemplate($tplIdx, 0, 0, $this->page_largeur);

							// Pied de page
							$pdf->SetTextColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
							$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 6);
							$pdf->SetXY($this->droite - 20, $this->page_hauteur - 10);
							$pdf->Cell(0, 3, 'page ' . $pdf->PageNo() . '/' . intval(5 + $this->count_page_anexe), 0, 0, 'C');
						}
					}
				}
			} elseif (! empty($conf->global->AGF_ALLOW_CONV_WITHOUT_FINNACIAL_DOC)) {
				$pdf->AddPage();
				if (! empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}

				$pagenb ++;
				$this->_pagehead($pdf, $agf, 1, $outputlangs);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
				$pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3
				$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);

				$posY = $this->marge_haute;
				$posX = $this->marge_gauche;

				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Choose a financial document into convention detail page"), 0, 'R');
			}

			$pdf->Close();
			$pdf->Output($file, 'F');
			if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

			// Add pdfgeneration hook
			if (! is_object($hookmanager)) {
				include_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
				$hookmanager = new HookManager($this->db);
			}
			$hookmanager->initHooks(array(
					'pdfgeneration'
			));
			$parameters = array(
					'file' => $file,
					'object' => $agf,
					'outputlangs' => $outputlangs
			);
			global $action;
			$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

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
	 * \param outputlangs Object lang for output
	 */
	function _pagehead(&$pdf, $object, $showaddress = 1, $outputlangs) {
		global $conf, $langs;

		$outputlangs->load("main");
		$outputlangs->load("agefodd@agefodd");

		pdf_pagehead($pdf, $outputlangs, $pdf->page_hauteur);
	}

	/**
	 * \brief Show footer of page
	 * \param pdf PDF factory
	 * \param object Object invoice
	 * \param outputlang Object lang for output
	 * \remarks Need this->emetteur object
	 */
	function _pagefoot(&$pdf, $object, $outputlangs) {
		global $conf, $langs, $mysoc;

		$outputlangs->load("agefodd@agefodd");

		$pdf->SetDrawColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		$pdf->SetTextColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		return pdf_agfpagefoot($pdf, $outputlangs, 'AGEFODD_CONVENTION_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, 1, $hidefreetext);
	}
}
