<?php
/* Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2012-2014  Florian Henry   <florian.henry@open-concept.pro>
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
 * \file agefodd/core/modules/agefodd/pdf/pdf_fiche_pedago.module.php
 * \ingroup agefodd
 * \brief PDF for explanation sheet
 */
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue_modules.class.php');
dol_include_once('/agefodd/class/agefodd_contact.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
class pdf_fiche_pedago_modules extends ModelePDFAgefodd {
	var $emetteur; // Objet societe qui emet

	// Definition des couleurs utilisées de façon globales dans le document (charte)
	protected $colorfooter;
	protected $colortext;
	protected $colorhead;
	protected $colorheaderBg;
	protected $colorheaderText;
	protected $colorLine;

	// pdf instance
	protected $pdf;
	protected $hearder_height_custom;

	/**
	 * \brief Constructor
	 * \param db Database handler
	 */
	function __construct($db) {
		global $conf, $langs, $mysoc;

		$langs->load("agefodd@agefodd");

		$this->db = $db;
		$this->name = 'fiche_pedago';
		$this->description = $langs->trans('AgfModPDFFichePeda');

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
		$this->unit = $formatarray['unit'];
		$this->oriantation = 'P';
		$this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
		$this->milieu = $this->espaceH_dispo / 2;
		$this->espaceV_dispo = $this->page_hauteur - ($this->marge_haute + $this->marge_basse);
		$this->espace_apres_corps_text = 4;
		$this->espace_apres_titre = 0;
		$this->default_font_size = 11;

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
	function write_file($agf, $outputlangs, $file, $socid, $courrier) {
		global $user, $langs, $conf, $mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		if (! is_object($outputlangs))
			$outputlangs = $langs;

		if (! is_object($agf)) {
			$id = $agf;
			$agf = new Formation($this->db);
			$agf->fetch($id);

			// Vilain hack : si !empty($courrier) alors c'est un id de session
			$agf_session = new Agsession($this->db);
			if (! empty($courrier)) {
				$agf_session->fetch($courrier);
			}

			$object_modules = new Agefoddformationcataloguemodules($this->db);
			$result = $object_modules->fetchAll('ASC', 'sort_order', 0, 0, array (
					't.fk_formation_catalogue' => $id
			));
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

			if (class_exists('TCPDF')) {
				$this->pdf->setPrintHeader(false);
				$this->pdf->setPrintFooter(false);
			}

			$this->pdf->Open();
			$pagenb = 0;

			$this->pdf->SetTitle($outputlangs->convToOutputCharset("AgfFichePedagogique " . $agf->ref_interne));
			$this->pdf->SetSubject($outputlangs->transnoentities("AgfFichePedagogique"));
			$this->pdf->SetCreator("Dolibarr " . DOL_VERSION . ' (Agefodd module)');
			$this->pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
			$this->pdf->SetKeyWords($outputlangs->convToOutputCharset($agf->ref_interne) . " " . $outputlangs->transnoentities("Document"));
			if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION)
				$this->pdf->SetCompression(false);

			$this->pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
			$this->pdf->SetAutoPageBreak(true, 0);

			// Set path to the background PDF File
			if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->AGF_ADD_PDF_BACKGROUND_P))
			{
				$pagecount = $this->pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P);
				$tplidx = $this->pdf->importPage(1);
			}

			// On recupere les infos societe
			$agf_soc = new Societe($this->db);
			$result = $agf_soc->fetch($socid);

			if ($result) {
				// New page
				$outputlangs->load("main");
				$this->pdf->AddPage();
				if (! empty($tplidx)) $this->pdf->useTemplate($tplidx);

				$this->_pagehead($agf, $outputlangs);
				
				$this->pdf->startTransaction();
				$footerheight = $this->_pagefoot($agf, $outputlangs);
				$this->page_limit = $this->page_hauteur - $footerheight;
				$this->pdf = $this->pdf->rollbackTransaction();
				
				/*
				 * Corps de page
				 */

				$posX = $this->marge_gauche;
				$posY = $this->pdf->GetY() + 20;

				/**
				 * *** Titre ****
				 */
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 15);
				$this->pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
				$this->pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfFichePedagogique');
				$this->pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');
				$posY = $this->pdf->GetY() + 10;

				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
				$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
				$this->str = $agf->intitule;
				$hauteur = dol_nboflines_bis($this->str, 50) * 4;
				// cadre
				$this->pdf->SetFillColor(255);
				$this->pdf->Rect($posX, $posY - 1, $this->espaceH_dispo, $hauteur + 3);
				// texte
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', '9');
				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'C');
				$posY += $hauteur + 10;

				/**
				 * *** But ****
				 */

				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size);
				$this->pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfBut');
				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $this->pdf->GetY();
				$this->pdf->SetDrawColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
				$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
				$posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;

				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size); // $this->pdf->SetFont('Arial','',9);
				$this->str = $agf->but;
				if (empty($this->str))
					$this->str = $outputlangs->transnoentities('AgfUndefinedBut');

				$this->pdf->SetXY($posX, $posY);
				$ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;

				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', '', '2', '', '', '', '', $ishtml);
				$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;

				/**
				 * *** Objectifs pedagogique de la formation ****
				 */

				// Récuperation
				$result2 = $agf->fetch_objpeda_per_formation($agf->id);
				if (count($agf->lines) > 0) {
					$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size + 1); // $this->pdf->SetFont('Arial','B',9);
					$this->pdf->SetXY($posX, $posY);
					$this->str = $outputlangs->transnoentities('AgfObjPeda');

					$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $this->pdf->GetY();
					$this->pdf->SetDrawColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
					$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
					$posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;

					$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size); // $this->pdf->SetFont('Arial','',9);
					$width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
					for($y = 0; $y < count($agf->lines); $y ++) {

						$this->pdf->SetXY($posX, $posY);
						$hauteur = dol_nboflines_bis($agf->lines[$y]->intitule, 100) * 4;

						$this->pdf->MultiCell($width, 0, $outputlangs->transnoentities($agf->lines[$y]->priorite . '.   ' . $agf->lines[$y]->intitule), 0, 'L');
						$posY = $this->pdf->GetY();
					}
					/*$obj_peda_array=array();
					 for($y = 0; $y < count($agf->lines); $y ++) {

					 $this->pdf->SetXY($posX, $posY);
					 $obj_peda_array[] = $agf->lines [$y]->priorite.'-'.$agf->lines [$y]->intitule;
					 }
					 if (count($obj_peda_array)>0) {
					 $obj_peda_array_str='  '.$this->pdf->unichr(149).' ';
					 $obj_peda_array_str.=implode('  '.$this->pdf->unichr(149).' ',$obj_peda_array);

					 $this->pdf->MultiCell($width, 0, $outputlangs->convToOutputCharset($obj_peda_array_str), 0, 'L');
					 }*/
					$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;
				}

				/**
				 * *** Pré requis ****
				 */

				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size + 1);
				$this->pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfPrerequis');
				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $this->pdf->GetY();
				$this->pdf->SetDrawColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
				$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
				$posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;

				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
				$this->str = $agf->prerequis;

				if (empty($this->str))
					$this->str = $outputlangs->transnoentities('AgfUndefinedPrerequis');

				$this->pdf->SetXY($posX, $posY);
				$ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;

				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', '', '2', '', '', '', '', $ishtml);
				$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;

				/**
				 * *** Public ****
				 */
				if (! empty($agf->public)) {
					$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size + 1);
					$this->pdf->SetXY($posX, $posY);
					$this->str = $outputlangs->transnoentities('AgfPublic');
					$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $this->pdf->GetY();
					$this->pdf->SetDrawColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
					$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
					$posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;

					$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
					$this->str = ucfirst($agf->public);

					$this->pdf->SetXY($posX, $posY);
					$ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;

					$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', '', '2', '', '', '', '', $ishtml);
					$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;
				}
				/**
				 * *** Moyens pédagogique ****
				 */

				if (! empty($agf->pedago_usage)) {
				    $this->pdf->startTransaction();
				    $posY = $this->_printMoyensPedago($posX, $posY, $outputlangs, $agf);
				    
				    if($posY > $this->page_limit)
				    {
				        $this->pdf = $this->pdf->rollbackTransaction();
				        $this->_pagefoot($agf, $outputlangs);
				        $this->pdf->AddPage();
				        if (! empty($tplidx)) $this->pdf->useTemplate($tplidx);
				        $this->_pagehead($agf, $outputlangs);
				        $posY = $this->pdf->GetY() + 5;
				        $this->_printMoyensPedago($posX, $posY, $outputlangs, $agf);
				    } else {
				        $this->pdf->commitTransaction();
				    }

				}
				/**
				 * *** Sanction ****
				 */
				if (! empty($agf->sanction)) {
				    $height = $this->getTotalHeightLine($agf->methode, $agf, $outputlangs);
				    
				    if ($height + $posY+10 > $this->page_limit) {
				        $this->_pagefoot($agf, $outputlangs);
				        $this->pdf->AddPage();
				        if (! empty($tplidx)) $this->pdf->useTemplate($tplidx);
				        
				        $this->_pagehead($agf, $outputlangs);
				        $posY = $this->pdf->GetY() + 5;
				    } else {
				        $posY = $this->pdf->GetY() + $this->espace_apres_corps_text;
				    }
				    
					$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size + 1);
					$this->pdf->SetXY($posX, $posY);
					$this->str = $outputlangs->transnoentities('AgfSanction');
					$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $this->pdf->GetY();
					$this->pdf->SetDrawColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
					$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
					$posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;

					$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
					$this->str = ucfirst($agf->sanction);

					$this->pdf->SetXY($posX, $posY);
					$ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;

					$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', '', '2', '', '', '', '', $ishtml);
					$posY = $this->pdf->GetY() + $this->espace_apres_corps_text + 2;
				}
				// Methode pedago ****

				if (! empty($agf->methode)) {
					$height = $this->getTotalHeightLine($agf->methode, $agf, $outputlangs, $fontsize);

					$height_left = $this->page_hauteur - $this->marge_basse - $posY;
					if ($height + $posY+10 > $this->page_limit) {
						$this->_pagefoot($agf, $outputlangs);
						$this->pdf->AddPage();
						if (! empty($tplidx)) $this->pdf->useTemplate($tplidx);

						$this->_pagehead($agf, $outputlangs);
						$posY = $this->pdf->GetY() + 5;
					} else {
						$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;
					}

					$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size + 1);

					$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
					$this->pdf->SetXY($posX, $posY);
					$this->str = $outputlangs->transnoentities('AgfMethode');
					$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $this->pdf->GetY();
					$this->pdf->SetDrawColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
					$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
					$posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;

					$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
					$this->str = $agf->methode;
					$hauteur = dol_nboflines_bis($this->str, 50) * 4;
					$this->pdf->SetXY($posX, $posY);
					$ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;

					$this->pdf->MultiCell(0, 5, $this->str, 0, 'L', '', '2', '', '', '', '', $ishtml);
					$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;
				}

				// Durée
				$this->pdf->startTransaction();
				$posY = $this->_printduree($posX, $posY, $outputlangs, $agf_session, $agf);
				
				if($posY > $this->page_limit)
				{
				    $this->pdf = $this->pdf->rollbackTransaction();
				    $this->_pagefoot($agf, $outputlangs);
				    $this->pdf->AddPage();
				    if (! empty($tplidx)) $this->pdf->useTemplate($tplidx);
				    $this->_pagehead($agf, $outputlangs);
				    $posY = $this->pdf->GetY() + 5;
				    $this->_printduree($posX, $posY, $outputlangs, $agf_session, $agf);
				} else {
				    $this->pdf->commitTransaction();
				}

				/**
				 * *** Programme ****
				 */
				$programme = '';
				if (is_array($object_modules->lines) && count($object_modules->lines) > 0) {
					$ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;
					$programme_array = array ();
					foreach ( $object_modules->lines as $line_chapter ) {
						$programme_array[] = '&bull; '.$line_chapter->title;
					}
					if (count($programme_array) > 0) {
						$programme .= implode('<br />', $programme_array);
					}
				} else {
					$programme = $agf->programme;
				}

				// Determine if jump pages is needed
				$height = $this->getRealHeightLine($programme) + 10 + $this->espace_apres_titre + 2;
				// print 'Real $height='.$height;
				// print '<BR>';

				$height_left = $this->page_hauteur - $this->marge_basse - $posY;

				$fontsize = $this->default_font_size;

				if ($height > $height_left) {

					// Save this value bacause reset into this method
					$header_height = $this->hearder_height_custom;

					// Check if needed to reduce text font size to fitt all in one page
					$height = $this->getTotalHeightLine($programme, $agf, $outputlangs, $fontsize);
					/*print 'TOTAL $height='.$height;
					 print '<BR>';
					 print ' $fontsize='.$fontsize;
					 print '<BR>';*/

					$total_height_left = $this->page_hauteur - $header_height - 80;

					// print ' $$total_height_left='.$total_height_left;
					// print '<BR>';
					if ($height > $total_height_left) {
						$allin_a_page = false;

						while ( $allin_a_page !== true && $fontsize > 0 ) {
							$fontsize --;

							$height = $this->getTotalHeightLine($programme, $agf, $outputlangs, $fontsize) + 10 + $this->espace_apres_titre + 2;
							/*print '$fontsize='.$fontsize;
							 print '$height='.$height;
							 print '<BR>';*/
							if ($height <= $total_height_left) {
								$allin_a_page = true;
							}
						}
					}
					$this->hearder_height_custom = $header_height;

					$this->_pagefoot($agf, $outputlangs);
					$this->pdf->AddPage();
					if (! empty($tplidx)) $this->pdf->useTemplate($tplidx);
					$this->_pagehead($agf, $outputlangs);
					$posY = $this->pdf->GetY() + 5;
				} else {
					$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;
				}

				/**
				 * *** Programme ****
				 */

				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size + 1);
				$this->pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfProgramme');
				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $this->pdf->GetY();
				$this->pdf->SetDrawColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
				$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);

				$posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $fontsize);
				$this->str = $programme;
				$ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;
				$this->pdf->MultiCell(0, 5, $this->str, 0, 'L', false, 1, $posX, $posY, true, 0, $ishtml);
				$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;

				// $this->pdf->SetXY($posX, $this->pdf->GetY());
				// Pied de page
				$this->_pagefoot($agf, $outputlangs);

				$this->pdf->AddPage();
				if (! empty($tplidx)) $this->pdf->useTemplate($tplidx);
				$this->_pagehead($agf, $outputlangs);
				$posY = $this->pdf->GetY() + 5;
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 13);
				$this->pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
				$this->pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfTrainingModule');
				$this->pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');
				$posY = $this->pdf->GetY() + 10;
				$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
				$this->pdf->SetDrawColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
				if (is_array($object_modules->lines) && count($object_modules->lines) > 0) {
					$ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;
					foreach ( $object_modules->lines as $line_chapter ) {

						$this->pdf->SetXY($posX, $posY);

						$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size + 2);

						if (! empty($line_chapter->duration)) {
							$str_duration = ' - ' . $line_chapter->duration . ' h';
						} else {
							$str_duration = '';
						}

						$this->pdf->MultiCell(0, 5, $line_chapter->title . $str_duration, 0, 'L', false, 1, $posX, $posY, true, 0, 0);
						$posY = $this->pdf->GetY();
						$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
						$posY = $this->pdf->GetY() + 2;

						if (! empty($line_chapter->obj_peda)) {
							$this->pdf->SetXY($posX, $posY);
							$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', $this->default_font_size);
							$this->pdf->MultiCell(0, 0, $outputlangs->transnoentities('AgfObjPeda'), 0, 'L', false, 1, $posX, $posY, true, 0, 0);
							$posY = $this->pdf->GetY() + 1;
							$this->pdf->SetXY($posX, $posY);
							$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
							$this->pdf->MultiCell(0, 0, $line_chapter->obj_peda, 0, 'L', false, 1, $posX, $posY, true, 0, $ishtml);
							$posY = $this->pdf->GetY() + 3;
						}

						if (! empty($line_chapter->content_text)) {
							$this->pdf->SetXY($posX, $posY);
							$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', $this->default_font_size);
							$this->pdf->MultiCell(0, 0, $outputlangs->transnoentities('AgfContenu'), 0, 'L', false, 1, $posX, $posY, true, 0, 0);
							$posY = $this->pdf->GetY() + 1;
							$this->pdf->SetXY($posX, $posY);
							$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
							$this->pdf->MultiCell(0, 0, $line_chapter->content_text, 0, 'L', false, 1, $posX, $posY, true, 0, $ishtml);
							$posY = $this->pdf->GetY() + 6;
						}
					}
				}
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
				$this->_pagefoot($agf, $outputlangs);

				// FPDI::AliasNbPages() is undefined method into Dolibarr 3.5
				if (method_exists($this->pdf, 'AliasNbPages')) {
					$this->pdf->AliasNbPages();
				}
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
	function _pagehead($object, $outputlangs) {
		global $conf, $mysoc;

		// Fill header with background color
		$this->pdf->SetFillColor($this->colorheaderBg[0], $this->colorheaderBg[1], $this->colorheaderBg[2]);
		$this->pdf->MultiCell($this->page_largeur, 40, '', 0, 'L', true, 1, 0, 0);


		pdf_pagehead($this->pdf, $outputlangs, $this->pdf->page_hauteur);

		$posY_ori = $this->pdf->GetY();
		$this->pdf->SetTextColor($this->colorheaderText[0], $this->colorheaderText[1], $this->colorheaderText[2]);

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
				}
				$this->pdf->Image($logo, $posX, $posY, 0, $height);
			} else {
				$this->pdf->SetTextColor(200, 0, 0);
				$this->pdf->SetFont('', 'B', $this->default_font_size - 2);
				$this->pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$this->pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$text = $this->emetteur->name;
			$this->pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
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
				if (is_readable($otherlogo) && $otherlogo != $logo) {
					$logo_height = pdf_getHeightForLogo($otherlogo);
					$width_otherlogo = pdf_getWidthForLogo($otherlogo);
					if ($width_otherlogo > 0 && $width_logo > 0) {
						$posX = $this->page_largeur - $this->marge_droite - $width_otherlogo - $width_logo - 10;
					} else {
						$posX = $this->marge_gauche + 100;
					}

					$this->pdf->Image($otherlogo, $posX, $posY, 0, $logo_height);
				}
			}
		}

		// Sender properties
		// Show sender
		$posy = $this->marge_haute;
		$posx = $this->marge_gauche;

		$hautcadre = 30;
		$this->pdf->SetXY($posx, $posy);
		$this->pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);

		// Show sender name
		$this->pdf->SetXY($posx, $posy);
		$this->pdf->SetFont('', 'B', $this->default_font_size - 2);
		$this->pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
		$posy = $this->pdf->GetY();

		// Show sender information
		$this->pdf->SetXY($posx, $posy);
		$this->pdf->SetFont('', '', $this->default_font_size - 3);
		$this->pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
		$posy = $this->pdf->GetY();
		$this->pdf->SetXY($posx, $posy);
		$this->pdf->SetFont('', '', $this->default_font_size - 3);
		$this->pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->zip . ' ' . $this->emetteur->town), 0, 'L');
		$posy = $this->pdf->GetY();
		$this->pdf->SetXY($posx, $posy);
		$this->pdf->SetFont('', '', $this->default_font_size - 3);
		$this->pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
		$posy = $this->pdf->GetY();
		$this->pdf->SetXY($posx, $posy);
		$this->pdf->SetFont('', '', $this->default_font_size - 3);
		$this->pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');
		$posy = $this->pdf->GetY();

		printRefIntForma($this->db, $outputlangs, $object, $this->default_font_size - 3, $this->pdf, $posx, $posy, 'L');

		$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
	}

	/**
	 * \brief Show footer of page
	 * \param pdf PDF factory
	 * \param object Object invoice
	 * \param outputlang Object lang for output
	 * \remarks Need this->emetteur object
	 */
	function _pagefoot($object, $outputlangs) {
		global $conf, $langs, $mysoc;

		$this->pdf->SetDrawColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		$this->pdf->SetTextColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		return pdf_agfpagefoot($this->pdf, $outputlangs, '', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, 1, $hidefreetext);
	}

	/**
	 *
	 * @param string $txt
	 */
	public function getRealHeightLine($txt='') {
		global $conf;
		// Determine if jump pages is needed
		$this->pdf->startTransaction();

		$ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;
		// store starting values
		$start_y = $this->pdf->GetY();
		// print '$start_y='.$start_y.'<br>';

		$start_page = $this->pdf->getPage();
		// print '$start_page='.$start_page.'<br>';
		// call your printing functions with your parameters
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		$this->pdf->MultiCell(0, 5, $txt, 0, 'L', false, 1, '', '', true, 0, $ishtml);
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get the new Y
		$end_y = $this->pdf->GetY();
		$end_page = $this->pdf->getPage() - 1;
		// calculate height
		// print '$end_y='.$end_y.'<br>';
		// print '$end_page='.$end_page.'<br>';

		$height = 0;
		if ($end_page == $start_page && $end_y > $start_y) {
			$height = $end_y - $start_y;
			// print 'aa$height='.$height.'<br>';
		} else {
			for($page = $start_page; $page <= $end_page; $page ++) {
				$this->pdf->setPage($page);
				// print '$page='.$page.'<br>';
				if ($page == $start_page) {
					// first page
					$height = $this->page_hauteur - $start_y - $this->marge_basse;
					// print '$height=$this->page_hauteur - $start_y - $this->marge_basse='.$this->page_hauteur .'-'. $start_y .'-'. $this->marge_basse.'='.$height.'<br>';
				} elseif ($page == $end_page) {
					// last page
					// print '$height='.$height.'<br>';
					$height += $end_y - $this->marge_haute;
					// print '$height += $end_y - $this->marge_haute='.$end_y.'-'. $this->marge_haute.'='.$height.'<br>';
				} else {
					// print '$height='.$height.'<br>';
					$height += $this->page_hauteur - $this->marge_haute - $this->marge_basse;
					// print '$height += $this->page_hauteur - $this->marge_haute - $this->marge_basse='.$this->page_hauteur .'-'. $this->marge_haute .'-'. $this->marge_basse.'='.$height.'<br>';
				}
			}
		} // restore previous object
		$this->pdf = $this->pdf->rollbackTransaction();
		// print '$heightfinnal='.$height.'<br>';

		// exit;
		return $height;
	}
	public function getTotalHeightLine($txt, $object, $outputlangs, $fontsize = 8) {
		global $conf;
		// Determine if jump pages is needed
		$this->pdf->startTransaction();

		$this->pdf->AddPage();
		$this->_pagehead($object, $outputlangs);
		$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $fontsize);
		// $this->_pagefoot($object, $outputlangs);

		$ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;
		// store starting values
		$start_y = $this->pdf->GetY();
		$start_page = $this->pdf->getPage();
		// call your printing functions with your parameters
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		$this->pdf->MultiCell(0, 5, $txt, 0, 'L', '', '2', '', '', '', '', $ishtml);
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get the new Y
		$end_y = $this->pdf->GetY();
		$end_page = $this->pdf->getPage();
		// calculate height

		/*print '$start_y='.$start_y.'<BR>';
		 print '$$start_page='.$start_page.'<BR>';
		 print '$$end_y='.$end_y.'<BR>';
		 print '$$end_page='.$end_page.'<BR>';*/

		$height = 0;
		if ($end_page == $start_page) {
			$height = $end_y - $start_y;
		} else {
			for($page = $start_page; $page <= $end_page; $page ++) {
				$this->pdf->setPage($page);
				if ($page == $start_page) {
					// first page
					$height = $this->page_hauteur - $start_y - $this->marge_basse;
				} elseif ($page == $end_page) {
					// last page
					$height += $end_y - $this->marge_haute;
				} else {
					$height += $this->page_hauteur - $this->marge_haute - $this->marge_basse;
				}
			}
		} // restore previous object
		$this->pdf = $this->pdf->rollbackTransaction();

		return $height;
	}
	
	function _printMoyensPedago($posX, $posY, &$outputlangs, &$agf)
	{
	    global $conf;
	    
	    $this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size + 1);
	    $this->pdf->SetXY($posX, $posY);
	    $this->str = $outputlangs->transnoentities('AgfPedagoUsage');
	    $this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
	    $posY = $this->pdf->GetY();
	    $this->pdf->SetDrawColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
	    $this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
	    $posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;
	    
	    $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
	    $this->str = ucfirst($agf->pedago_usage);
	    
	    $this->pdf->SetXY($posX, $posY);
	    $ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;
	    
	    $this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', '', '2', '', '', '', '', $ishtml);
	    $posY = $this->pdf->GetY() + $this->espace_apres_corps_text;
	    
	    return $posY;
	}
	
	function _printduree($posX, $posY, &$outputlangs, &$agf_session, &$agf)
	{
	    global $conf;
	    
	    $this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size + 1);
	    $this->pdf->SetXY($posX, $posY);
	    $this->str = $outputlangs->transnoentities('AgfPDFFichePeda1');
	    $this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
	    $posY = $this->pdf->GetY();
	    $this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
	    $this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
	    $posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;
	    
	    $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
	    // calcul de la duree en nbre de jours
	    
	    if (empty($agf_session->duree_session)) {
	        $duree = $agf->duree;
	    } else {
	        $duree = $agf_session->duree_session;
	    }
	    if (empty($conf->global->AGF_NB_HOUR_IN_DAYS)) {
	        $jour = $duree / 7;
	    } else {
	        $jour = $duree / $conf->global->AGF_NB_HOUR_IN_DAYS;
	    }
	    
	    
	    // $this->str = $agf->duree.' '.$outputlangs->transnoentities('AgfPDFFichePeda2').'.';
	    if ($jour < 1)
	        $this->str = $duree . ' ' . $outputlangs->transnoentities('AgfPDFFichePeda2') . '.';
	        else {
	            $this->str = $duree . ' ' . $outputlangs->transnoentities('AgfPDFFichePeda2') . ' (' . ceil($jour) . ' ' . $outputlangs->transnoentities('AgfPDFFichePeda3');
	            if (ceil($jour) > 1)
	                $this->str .= 's';
	                $this->str .= ').';
	        }
	        $this->pdf->SetXY($posX, $posY);
	        $this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
	        $posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;
	        
	        return $posY;
	}
}

