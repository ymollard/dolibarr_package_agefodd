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
dol_include_once('/agefodd/core/modules/agefodd/agefodd_modules.php');
require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_formation_catalogue.class.php');
require_once ('../class/agefodd_contact.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
require_once ('../lib/agefodd.lib.php');
class pdf_fiche_pedago extends ModelePDFAgefodd {
	var $emetteur; // Objet societe qui emet
	               
	// Definition des couleurs utilisées de façon globales dans le document (charte)
	protected $colorfooter;
	protected $colortext;
	protected $colorhead;
	
	//pdf instance
	protected $pdf;
	
	protected $hearder_height_custom;
	
	/**
	 * \brief		Constructor
	 * \param		db		Database handler
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

		$this->page_largeur = $formatarray ['width'];
		$this->page_hauteur = $formatarray ['height'];
		$this->format = array (
				$this->page_largeur,
				$this->page_hauteur 
		);
		$this->marge_gauche = 15;
		$this->marge_droite = 15;
		$this->marge_haute = 10;
		$this->marge_basse = 50;
		$this->unit = $formatarray['unit'];
		$this->oriantation = 'P';
		$this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
		$this->milieu = $this->espaceH_dispo / 2;
		$this->espaceV_dispo = $this->page_hauteur - ($this->marge_haute + $this->marge_basse);
		$this->espace_apres_corps_text = 4;
		$this->espace_apres_titre = 0;
		$this->default_font_size=9;
		
		$this->colorfooter = agf_hex2rgb($conf->global->AGF_FOOT_COLOR);
		$this->colortext = agf_hex2rgb($conf->global->AGF_TEXT_COLOR);
		$this->colorhead = agf_hex2rgb($conf->global->AGF_HEAD_COLOR);
		
		// Get source company
		$this->emetteur = $mysoc;
		if (! $this->emetteur->country_code)
			$this->emetteur->country_code = substr($langs->defaultlang, - 2); // By default, if was not defined
	}
	
	/**
	 * \brief Fonction generant le document sur le disque
	 * \param agf		Objet document a generer (ou id si ancienne methode)
	 * outputlangs	Lang object for output language
	 * file		Name of file to generate
	 * \return int 1=ok, 0=ko
	 */
	function write_file($agf, $outputlangs, $file, $socid, $courrier) {
		global $user, $langs, $conf, $mysoc;
		
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		
		if (! is_object($outputlangs))
			$outputlangs = $langs;
		
		if (! is_object($agf)) {
			$id = $agf;
			$agf = new Agefodd($this->db);
			$agf->fetch($id);
			
			// Vilain hack si !empty($courrier) alors c'est un id de session
			$agf_session = new Agsession($this->db);
			if (! empty($courrier)) {
				$agf_session->fetch($courrier);
			}
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
			                                                                                
			// On recupere les infos societe
			$agf_soc = new Societe($this->db);
			$result = $agf_soc->fetch($socid);
			
			if ($result) {
				// New page
				$outputlangs->load("main");
				$this->pdf->AddPage();	
				$this->_pagehead($agf, $outputlangs);
				/*
				 * Corps de page
				*/
				
				$posX = $this->marge_gauche;
				$posY = $this->pdf->GetY()+5;
				
				/**
				 * *** Titre ****
				 */
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 15);
				$this->pdf->SetTextColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
				$this->pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfFichePedagogique');
				$this->pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');
				$posY = $this->pdf->GetY()+10;
				
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
				$this->pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);
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
				
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 12);
				$this->pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfBut');
				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $this->pdf->GetY();
				$this->pdf->SetDrawColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
				$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
				$posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;
				
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size); // $this->pdf->SetFont('Arial','',9);
				$this->str = $agf->but;
				if (empty($this->str))
					$this->str = $outputlangs->transnoentities('AgfUndefinedBut');
				
				$this->pdf->SetXY($posX, $posY);
				$ishtml = $conf->global->FCKEDITOR_ENABLE_SOCIETE ? 1 : 0;
				
				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', '', '2', '', '', '', '', $ishtml);
				$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;
				
				/**
				 * *** Objectifs pedagogique de la formation ****
				 */
				
				// Récuperation
				$result2 = $agf->fetch_objpeda_per_formation($agf->id);
				
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', '12'); // $this->pdf->SetFont('Arial','B',9);
				$this->pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfObjPeda');
				
				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $this->pdf->GetY();
				$this->pdf->SetDrawColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
				$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
				$posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;
				
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size); // $this->pdf->SetFont('Arial','',9);
				$hauteur = 0;
				$width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
				for($y = 0; $y < count($agf->lines); $y ++) {
					if ($y > 0)
						$posY += $hauteur;
					$this->pdf->SetXY($posX, $posY);
					$hauteur = dol_nboflines_bis($agf->lines [$y]->intitule, 80) * 4;
					
					$this->pdf->Cell(10, 4, $agf->lines [$y]->priorite . '. ', 0, 0, 'L', 0);
					$this->pdf->MultiCell($width, 4, $outputlangs->transnoentities($agf->lines [$y]->intitule), 0, 'L');
				}
				$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;
				
				/**
				 * *** Pré requis ****
				 */
				
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', '12');
				$this->pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfPrerequis');
				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $this->pdf->GetY();
				$this->pdf->SetDrawColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
				$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
				$posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;
				
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', '9');
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
				
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', '12');
				$this->pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfPublic');
				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $this->pdf->GetY();
				$this->pdf->SetDrawColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
				$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
				$posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;
				
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
				$this->str = ucfirst($agf->public);
				
				$this->pdf->SetXY($posX, $posY);
				$ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;
				
				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', '', '2', '', '', '', '', $ishtml);
				$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;
				/**
				 * *** Programme ****
				 */
				
				//Determine if jump pages is needed
				$height = $this->getRealHeightLine($agf->programme);
				//print 'Real $height='.$height;
				//print '<BR>';
				
				$height_left = $this->page_hauteur-$this->marge_basse - $posY;
				
				$fontsize = $this->default_font_size;
				
				if ($height > $height_left) {
					
					//Save this value bacause reset into this method
					$header_height=$this->hearder_height_custom;
					
					//Check if needed to reduce text font size to fitt all in one page
					$height = $this->getTotalHeightLine($agf->programme,$agf, $outputlangs, $fontsize);
					/*print 'TOTAL $height='.$height;
					print '<BR>';
					print ' $fontsize='.$fontsize;
					print '<BR>';*/
					
					$total_height_left = $this->page_hauteur - $header_height - 80;
					
					//print ' $$total_height_left='.$total_height_left;
					//print '<BR>';
					if ($height>$total_height_left)	{
						$allin_a_page=false;
						
						while ($allin_a_page!==true && $fontsize>0) {
							$fontsize--;
							
							$height = $this->getTotalHeightLine($agf->programme,$agf, $outputlangs,$fontsize);
							/*print '$fontsize='.$fontsize;
							print '$height='.$height;
							print '<BR>';*/
							if ($height<=$total_height_left)	{
								$allin_a_page=true;
							} 							
						}
					}
					$this->hearder_height_custom=$header_height;
					
					$this->_pagefoot($agf, $outputlangs);
					$this->pdf->AddPage();
					$this->_pagehead($agf, $outputlangs);
					$posY = $this->pdf->GetY()+5;
				} else {
					$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;
				}
				
				/**
				 * *** Programme ****
				 */
				
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', '12');
				$this->pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfProgramme');
				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $this->pdf->GetY();
				$this->pdf->SetDrawColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
				$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
				
				
				$posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $fontsize+2);
				$this->str = $agf->programme;
				$ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;
				$this->pdf->MultiCell(0, 5, $this->str, 0, 'L', '', '2', '', '', '', '', $ishtml);
				$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;
				
				
				// Methode pedago ****
				
				$height = $this->getTotalHeightLine($agf->methode,$agf, $outputlangs,$fontsize);
					
				$height_left = $this->page_hauteur-$this->marge_basse - $posY;
				if ($height > $height_left) {
					$this->_pagefoot($agf, $outputlangs);
					$this->pdf->AddPage();
					$this->_pagehead($agf, $outputlangs);
					$posY = $this->pdf->GetY()+5;
				} else {
					$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;
				}
				
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', '12');
				
				$this->pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);
				$this->pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfMethode');
				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $this->pdf->GetY();
				$this->pdf->SetDrawColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
				$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
				$posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;
				
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', '9');
				$this->str = $agf->methode;
				$hauteur = dol_nboflines_bis($this->str, 50) * 4;
				$this->pdf->SetXY($posX, $posY);
				$ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;
				
				$this->pdf->MultiCell(0, 5, $this->str, 0, 'L', '', '2', '', '', '', '', $ishtml);
				$posY = $this->pdf->GetY() + $this->espace_apres_corps_text;

				
				// Durée
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', '12');
				$this->pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfPDFFichePeda1');
				$this->pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY = $this->pdf->GetY();
				$this->pdf->SetDrawColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
				$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
				$posY = $this->pdf->GetY() + $this->espace_apres_titre + 2;
				
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', '9');
				// calcul de la duree en nbre de jours
				
				if (empty($agf_session->duree_session)) {
					$duree = $agf->duree;
				} else {
					$duree = $agf_session->duree_session;
				}
				$jour = $duree / 7;
				
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
				
				// Pied de page
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
	function _pagehead($object, $outputlangs) {
		global $conf, $mysoc;
		
		pdf_pagehead($this->pdf, $outputlangs, $this->pdf->page_hauteur);
		
		$posY_ori = $this->pdf->getY();
		$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
		$this->pdf->MultiCell(0, 3, '', 0, 'J');
		$this->pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);
		
		$posY = $this->marge_haute;
		$posX = $this->marge_gauche;
		
		/*
		 * Header société
		*/
		
		// Logo en haut à droite
		$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
		if ($this->emetteur->logo) {
			if (is_readable($logo)) {
				$heightLogo = pdf_getHeightForLogo($logo);
				include_once (DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php');
				$tmp = dol_getImageSize($logo);
				if ($tmp ['width']) {
					$widthLogo = $tmp ['width'];
				}
				$this->pdf->Image($logo, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 50, $this->marge_haute, 0, $heightLogo, '', '', '', true, 300, '', false, false, 0, false, false, true); // width=0
				// (auto)
			} else {
				$this->pdf->SetTextColor(200, 0, 0);
				$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 8);
				$this->pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'R');
				$this->pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'R');
			}
		} else {
			$text = $this->emetteur->name;
			$this->pdf->SetTextColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
			$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 11);
			$this->pdf->MultiCell(150, 3, $outputlangs->convToOutputCharset($text), 0, 'R');
		}
		
		// $posX += $this->page_largeur - $this->marge_droite - 65;
		
		$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
		
		$this->pdf->SetTextColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
		$this->pdf->SetXY($posX, $posY - 1);
		$this->pdf->Cell(0, 5, $mysoc->name, 0, 0, 'L');
		
		$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);
		$this->pdf->SetXY($posX, $posY + 3);
		$this->str = $mysoc->address . "\n";
		$this->str .= $mysoc->zip . ' ' . $mysoc->town;
		$this->str .= ' - ' . $mysoc->country . "\n";
		if ($mysoc->phone) {
			$this->str .= $outputlangs->transnoentities('AgfPDFHead1') . ' ' . $mysoc->phone . "\n";
		}
		if ($mysoc->fax) {
			$this->str .= $outputlangs->transnoentities('AgfPDFHead2') . ' ' . $mysoc->fax . "\n";
		}
		if ($mysoc->email) {
			$this->str .= $outputlangs->transnoentities('AgfPDFHead3') . ' ' . $mysoc->email . "\n";
		}
		if ($mysoc->url) {
			$this->str .= $outputlangs->transnoentities('AgfPDFHead4') . ' ' . $mysoc->url . "\n";
		}
		
		$this->pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($this->str), 0, 'L');
		
		$posY = $this->pdf->getY()+5;
		
		$this->pdf->SetDrawColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
		$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
		
		
		$posY_end = $this->pdf->getY();
		
		$this->hearder_height_custom=$posY_end - $posY_ori;
		
		$this->pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);
		
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
		
		
		$this->pdf->SetDrawColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
		$this->pdf->Line($this->marge_gauche, $this->page_hauteur - 20, $this->page_largeur - $this->marge_droite, $this->page_hauteur - 20);
		
		$this->str = $mysoc->name;
		
		$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
		$this->pdf->SetTextColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
		$this->pdf->SetXY($this->marge_gauche, $this->page_hauteur - 20);
		$this->pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');
		
		$this->str = $mysoc->address . " ";
		$this->str .= $mysoc->zip . ' ' . $mysoc->town;
		$this->str .= ' - ' . $mysoc->country;
		$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFFoot1') . ' ' . $mysoc->phone;
		$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFFoot2') . ' ' . $mysoc->email . "\n";
		
		$statut = getFormeJuridiqueLabel($mysoc->forme_juridique_code);
		$this->str .= $statut;
		if (! empty($mysoc->capital)) {
			$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFFoot3') . ' ' . $mysoc->capital . ' ' . $langs->trans("Currency" . $conf->currency);
		}
		if (! empty($mysoc->idprof2)) {
			$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFFoot4') . ' ' . $mysoc->idprof2;
		}
		if (! empty($mysoc->idprof4)) {
			$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFFoot5') . ' ' . $mysoc->idprof4;
		}
		if (! empty($mysoc->idprof3)) {
			$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFFoot6') . ' ' . $mysoc->idprof3;
		}
		$this->str .= "\n";
		if (! empty($conf->global->AGF_ORGANISME_NUM)) {
			$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFFoot7') . ' ' . $conf->global->AGF_ORGANISME_NUM;
		}
		if (! empty($conf->global->AGF_ORGANISME_PREF)) {
			$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFFoot8') . ' ' . $conf->global->AGF_ORGANISME_PREF;
		}
		if (! empty($mysoc->tva_intra)) {
			$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFFoot9') . ' ' . $mysoc->tva_intra;
		}
		
		$this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', 7);
		$this->pdf->SetXY($this->marge_gauche, $this->page_hauteur - 16);
		$this->pdf->MultiCell(0, 3, $outputlangs->convToOutputCharset($this->str), 0, 'C');
		
		$this->pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);
	}
	
	
	public function getRealHeightLine($txt) {
		
		global $conf;
		//Determine if jump pages is needed
		$this->pdf->startTransaction(); 
		
		$ishtml = $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING ? 1 : 0;
		// store starting values
		$start_y = $this->pdf->GetY();
		//print '$start_y='.$start_y;
		
		$start_page = $this->pdf->getPage();
		//print '$start_page='.$start_page;
		// call your printing functions with your parameters
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		$this->pdf->MultiCell(0, 5, $txt, 0, 'L', '', '2', '', '', '', '', $ishtml);
		// - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
		// get the new Y
		$end_y = $this->pdf->GetY();
		$end_page = $this->pdf->getPage()-1;
		// calculate height
		//print '$end_y='.$end_y;
		//print '$end_page='.$end_page;
		
		
		$height = 0;
		if ($end_page == $start_page) {
			$height = $end_y - $start_y;
		} else {
			for($page = $start_page; $page <= $end_page;  $page ++) {
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
		//print '$height='.$height;
		
		//exit;
		return $height;
	}
	
	
	public function getTotalHeightLine($txt,$object,$outputlangs, $fontsize=8) {
	
		global $conf;
		//Determine if jump pages is needed
		$this->pdf->startTransaction();
		
		$this->pdf->AddPage();
		$this->_pagehead($object, $outputlangs);
		$this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', $fontsize);
		//$this->_pagefoot($object, $outputlangs);
	
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
			for($page = $start_page; $page <= $end_page;  $page ++) {
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
}