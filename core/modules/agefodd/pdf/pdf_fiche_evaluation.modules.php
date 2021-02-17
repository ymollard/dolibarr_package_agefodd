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
 * \file agefodd/core/modules/agefodd/pdf/pdf_fiche_evaluation.modules.php
 * \ingroup agefodd
 * \brief PDF for satisfaction sheet
 */
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
dol_include_once('/agefodd/class/agefodd_contact.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
class pdf_fiche_evaluation extends ModelePDFAgefodd {
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
		$this->name = "fiche_evaluation";
		$this->description = $langs->trans('AgfModPDFFicheEval');

		// Dimension page pour format A4 en portrait
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
		$this->espaceV_dispo = $this->page_hauteur - ($this->marge_haute + $this->marge_basse);

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
	 * \outputlangs		Lang object for output language
	 * \file			Name of file to generate
	 * \return int 1=ok, 0=ko
	 */
	function write_file($agf, $outputlangs, $file, $socid, $courrier) {
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

			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}

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
			if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->AGF_ADD_PDF_BACKGROUND_P))
			{
				$pagecount = $pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P);
				$tplidx = $pdf->importPage(1);
			}

			// On recupere les infos societe
			$agf_soc = new Societe($this->db);
			$result = $agf_soc->fetch($socid);

			if ($result) {
				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);

				$pagenb ++;
				$this->_pagehead($pdf, $agf, 1, $outputlangs);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
				$pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3
				$pdf->SetTextColor($this->colorheaderText[0], $this->colorheaderText[1], $this->colorheaderText[2]);

				$default_font_size = pdf_getPDFFontSize($outputlangs);

				$posY = $this->marge_haute;
				$posX = $this->page_largeur - $this->marge_droite - 55;

				// Logo
				$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
				$width_logo = pdf_getWidthForLogo($logo);
				if ($this->emetteur->logo) {
					if (is_readable($logo)) {
						$height = pdf_getHeightForLogo($logo);
						$width_logo=pdf_getWidthForLogo($logo);
						if ($width_logo>0) {
							$posX=$this->page_largeur-$this->marge_droite-$width_logo;
						} else {
							$posX=$this->page_largeur-$this->marge_droite-55;
						}
						$pdf->Image($logo, $posX, $posY, 0, $height);
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
							$logo_height=pdf_getHeightForLogo($otherlogo);
							$width_otherlogo=pdf_getWidthForLogo($otherlogo);
							if ($width_otherlogo>0 && $width_logo>0) {
								$posX=$this->page_largeur-$this->marge_droite-$width_otherlogo-$width_logo-10;
							} else {
								$posX=$this->marge_gauche+100;
							}

							$pdf->Image($otherlogo, $posX, $posY, 0, $logo_height);
						}
					}
				}

				// Affichage du logo commanditaire (optionnel)
				if ($conf->global->AGF_USE_LOGO_CLIENT) {
					$staticsoc = new Societe($this->db);
					$staticsoc->fetch($agf->socid);
					$dir = $conf->societe->multidir_output[$staticsoc->entity] . '/' . $staticsoc->id . '/logos/';
					if (! empty($staticsoc->logo)) {
						$logo_client = $dir . $staticsoc->logo;
						if (file_exists($logo_client) && is_readable($logo_client)){
							$heightlogo = pdf_getHeightForLogo($logo_client);
							$pdf->Image($logo_client, $this->page_largeur - $this->marge_gauche - $this->marge_droite - ( $width_logo * 1.5), $this->marge_haute, $heightlogo);
						}
					}
				}

				// Sender properties
				// Show sender
				$posY = $this->marge_haute;
				$posX = $this->marge_gauche;

				$hautcadre = 30;
				$pdf->SetXY($posX, $posY);
				$pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);

				// Show sender name
				$pdf->SetXY($posX, $posY);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
				$posY = $pdf->GetY();

				// Show sender information
				$pdf->SetXY($posX, $posY);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
				$posY = $pdf->GetY();
				$pdf->SetXY($posX, $posY);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->zip . ' ' . $this->emetteur->town), 0, 'L');
				$posY = $pdf->GetY();
				$pdf->SetXY($posX, $posY);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
				$posY = $pdf->GetY();
				$pdf->SetXY($posX, $posY);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');

				$posY = $pdf->GetY();
				printRefIntForma($this->db, $outputlangs, $agf, $default_font_size - 1, $pdf, $posX, $posY, 'L');

				$posY = $pdf->GetY() + 10;

				$pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
				$pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);

				// Mise en page de la baseline
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 18);
				$this->str = $outputlangs->transnoentities($mysoc->url);
				$this->width = $pdf->GetStringWidth($this->str);

				// alignement du bord droit du container avec le haut de la page
				$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->width;
				$baseline_angle = (M_PI / 2); // angle droit
				$baseline_x = 8;
				$baseline_y = $this->espaceV_dispo - $baseline_ecart + 30;
				$baseline_width = $this->width;
				$pdf->SetXY($baseline_x, $baseline_y);

				/*
				 * Corps de page
				 */

				$posX = $this->marge_gauche;
				$posY = $posY + 5;

				/**
				 * *** Titre ****
				 */
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 15);
				$pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
				$pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfFicheEval');
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');
				$posY += 10;

				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
				$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
				$this->str = $agf->intitule_custo;
				$hauteur = dol_nboflines_bis($this->str, 50) * 4;
				// cadre
				$pdf->SetFillColor(255);
				$pdf->Rect($posX, $posY - 1, $this->espaceH_dispo, $hauteur + 3);
				// texte
				$pdf->SetXY($posX, $posY);
				$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'C');
				$posY += $hauteur + 3;

				/**
				 * *** Date et formateur ****
				 */

				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', 9);

				$pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfPDFFicheEval1') . " ";
				if ($agf->dated == $agf->datef)
					$this->str .= dol_print_date($agf->dated);
				else
					$this->str .= ' ' . dol_print_date($agf->dated) . ' au ' . dol_print_date($agf->datef);
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');
				$posY += 4;

				$formateurs = new Agefodd_session_formateur($this->db);
				$nbform = $formateurs->fetch_formateur_per_session($agf->id);
				$form_str = "";
				for($i = 0; $i < $nbform; $i ++) {
					// Infos formateurs
					$forma_str .= strtoupper($formateurs->lines[$i]->lastname) . ' ' . ucfirst($formateurs->lines[$i]->firstname);
					if ($i < ($nbform - 1))
						$forma_str .= ', ';
				}

				$pdf->SetXY($posX, $posY);
				($nbform > 1) ? $this->str = $outputlangs->transnoentities('AgfPDFFicheEval2') . " " : $this->str = $outputlangs->transnoentities('AgfPDFFicheEval3') . " ";
				$this->str .= $forma_str;
				$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'C', 0);
				$posY = $pdf->GetY() + 5;

				/**
				 * *** Trainee Information ************
				 */

				$pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfPDFFicheEvalNameTrainee') . ' : .....................';
				$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', 0);
				$this->str = $outputlangs->transnoentities('AgfPDFFicheEvalCompany') . ' : .....................';
				$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', 0);
				$this->str = $outputlangs->transnoentities('AgfPDFFicheEvalEmailTrainee') . ' : .....................';
				$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', 0);
				$posY = $pdf->GetY() + 5;

				/**
				 * *** Objectifs pedagogique de la formation ****
				 */

				// Récuperation
				$agf_op = new Formation($this->db, "", $id);
				$result2 = $agf_op->fetch_objpeda_per_formation($agf->formid);

				$width = 160;
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 10);
				$pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfPDFFicheEval4');
				$pdf->MultiCell($width, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', 0);
				$posY = $pdf->GetY() + 1;

				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 10);
				if (is_array($agf_op->line)) {
					for ($y = 0; $y < count($agf_op->line); $y++) {
						// Intitulé
						$posY = $pdf->GetY();
						$pdf->SetXY($posX, $posY);
						$pdf->MultiCell($width, 0, $outputlangs->transnoentities($agf_op->line[$y]->intitule), 1, 'L', 0);
						$posY_after = $pdf->GetY();
						$hauteur = ($posY_after - $posY);

						// Oui
						$pdf->SetXY($posX + $width, $posY);
						$this->str = $outputlangs->transnoentities('AgfPDFFicheEvalYes');
						$pdf->MultiCell(10, $hauteur, $outputlangs->convToOutputCharset($this->str), 0, 'C', 0);
						$pdf->Rect($posX + $width, $posY, 10, $hauteur);

						// Non
						$pdf->SetXY($posX + $width + 10, $posY);
						$this->str = $outputlangs->transnoentities('AgfPDFFicheEvalNo');
						$pdf->MultiCell(10, $hauteur, $outputlangs->convToOutputCharset($this->str), 0, 'C', 0);
						$pdf->Rect($posX + $width + 10, $posY, 10, $hauteur);
					}
				}
				$posY = $pdf->GetY() + 5;

				/**
				 * *** présentation echelle de notation ****
				 */

				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 10);
				$pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfPDFFicheEval5');
				$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'C', 0);
				$posY = $pdf->GetY() + 1;

				$col_larg = $this->espaceH_dispo / 5;

				// ligne 1
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 10);
				$pdf->SetXY($posX, $posY);
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("1"), 1, 0, 'C');
				$pdf->SetXY($posX + (1 * $col_larg), $posY);
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("2"), 1, 0, 'C');
				$pdf->SetXY($posX + (2 * $col_larg), $posY);
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("3"), 1, 0, 'C');
				$pdf->SetXY($posX + (3 * $col_larg), $posY);
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("4"), 1, 0, 'C');
				$pdf->SetXY($posX + (4 * $col_larg), $posY);
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("5"), 1, 0, 'C');
				$posY += 5;

				// ligne 2
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
				$pdf->SetXY($posX, $posY);
				$pdf->Cell($col_larg, 5, $outputlangs->transnoentities('AgfPDFFicheEval6'), 1, 0, 'C');
				$pdf->Cell($col_larg, 5, $outputlangs->transnoentities('AgfPDFFicheEval7'), 1, 0, 'C');
				$pdf->Cell($col_larg, 5, $outputlangs->transnoentities('AgfPDFFicheEval8'), 1, 0, 'C');
				$pdf->Cell($col_larg, 5, $outputlangs->transnoentities('AgfPDFFicheEval9'), 1, 0, 'C');
				$pdf->Cell($col_larg, 5, $outputlangs->transnoentities('AgfPDFFicheEval10'), 1, 0, 'C');
				$posY += 5 + 10;

				/**
				 * *** lignes d'évaluations ****
				 */

				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 10);
				$hauteur_ligne = 6;

				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->transnoentities('AgfPDFFicheEval11'), 1, 0, 'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""), 1, 0, 'C');
				$posY += $hauteur_ligne;

				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->transnoentities('AgfPDFFicheEval12'), 1, 0, 'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""), 1, 0, 'C');
				$posY += $hauteur_ligne;

				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->transnoentities('AgfPDFFicheEval13'), 1, 0, 'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""), 1, 0, 'C');
				$posY += $hauteur_ligne;

				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->transnoentities('AgfPDFFicheEval14'), 1, 0, 'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""), 1, 0, 'C');
				$posY += $hauteur_ligne;

				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->transnoentities('AgfPDFFicheEval15'), 1, 0, 'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""), 1, 0, 'C');
				$posY += $hauteur_ligne;

				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->transnoentities('AgfPDFFicheEval16'), 1, 0, 'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""), 1, 0, 'C');
				$posY += $hauteur_ligne;

				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->transnoentities('AgfPDFFicheEval17'), 1, 0, 'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""), 1, 0, 'C');
				$posY += $hauteur_ligne;

				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->transnoentities('AgfPDFFicheEval18'), 1, 0, 'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""), 1, 0, 'C');
				$posY += $hauteur_ligne;

				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->transnoentities('AgfPDFFicheEval19'), 1, 0, 'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""), 1, 0, 'C');
				$posY += $hauteur_ligne;

				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->transnoentities('AgfPDFFicheEval20'), 1, 0, 'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""), 1, 0, 'C');
				$posY += $hauteur_ligne;

				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->transnoentities('AgfPDFFicheEval21'), 1, 0, 'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""), 1, 0, 'C');
				$posY += 5 + $hauteur_ligne;

				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', 11);
				$pdf->SetXY($posX, $posY);
				$pdf->Cell(0, $hauteur_ligne, $outputlangs->transnoentities('AgfPDFFicheEval22'), 0, 0, 'C');
				$posY += $hauteur_ligne;

				/**
				 * *** bloc commentaire ****
				 */

				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 10);
				$pdf->SetXY($posX, $posY);
				$pdf->Cell(0, 5, $outputlangs->transnoentities('AgfPDFFicheEval23'), 0, 0, 'L');

				$hauteur = $this->page_hauteur - 20 - $posY - 5;

				$pdf->Rect($posX, $posY, $this->espaceH_dispo, $hauteur);

				// Pied de page
				$this->_pagefoot($pdf, $agf, $outputlangs);
				// FPDI::AliasNbPages() is undefined method into Dolibarr 3.5
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}
			}
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
	 * \brief Show header of page
	 * \param pdf Object PDF
	 * \param object Object invoice
	 * \param showaddress 0=no, 1=yes
	 * \param outputlangs Object lang for output
	 */
	function _pagehead(&$pdf, $object, $showaddress = 1, $outputlangs) {
		global $conf, $langs;

		$outputlangs->load("main");

		// Fill header with background color
		$pdf->SetFillColor($this->colorheaderBg[0], $this->colorheaderBg[1], $this->colorheaderBg[2]);
		$pdf->MultiCell($this->page_largeur, 40, '', 0, 'L', true, 1, 0, 0);


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

		$pdf->SetTextColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		$pdf->SetDrawColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		return pdf_agfpagefoot($pdf, $outputlangs, '', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, 1, $hidefreetext);
	}
}
