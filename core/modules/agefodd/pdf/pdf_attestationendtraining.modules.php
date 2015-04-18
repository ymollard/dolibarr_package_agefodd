<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2012-2014 Florian Henry <florian.henry@open-concept.pro>
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
 * \file agefodd/core/modules/agefodd/pdf/pdf_convocation.modules.php
 * \ingroup agefodd
 * \brief PDF for convocation
 */
dol_include_once('/agefodd/core/modules/agefodd/agefodd_modules.php');
require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_formation_catalogue.class.php');
require_once ('../class/agefodd_session_calendrier.class.php');
require_once ('../class/agefodd_place.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
require_once ('../lib/agefodd.lib.php');
require_once ('../class/agefodd_session_stagiaire.class.php');
class pdf_convocation extends ModelePDFAgefodd {
	var $emetteur; // Objet societe qui emet
	               
	// Definition des couleurs utilisées de façon globales dans le document (charte)
	protected $colorfooter;
	protected $colortext;
	protected $colorhead;
	
	/**
	 * \brief		Constructor
	 * \param		db		Database handler
	 */
	function pdf_convocation($db) {
		global $conf, $langs, $mysoc;
		
		$langs->load("agefodd@agefodd");
		
		$this->db = $db;
		$this->name = 'attestationendtraining';
		$this->description = $langs->trans('AgfAttestationEndTraining');
		
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
		$this->marge_basse = 10;
		$this->defaultFontSize = 13;
		$this->unit = 'mm';
		$this->oriantation = 'P';
		$this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
		$this->milieu = $this->espaceH_dispo / 2;
		$this->espaceV_dispo = $this->page_hauteur - ($this->marge_haute + $this->marge_basse);
		
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
	function write_file($agf, $outputlangs, $file, $socid) {
		global $user, $langs, $conf, $mysoc, $mc;
		
		if (! is_object($outputlangs))
			$outputlangs = $langs;
		
		if (! is_object($agf)) {
			$id = $agf;
			$agf = new Agsession($this->db);
			$ret = $agf->fetch($id);
			if ($ret) {
				$agf_calendrier = new Agefodd_sesscalendar($this->db);
				$agf_calendrier->fetch_all($id);
				
				$agf_place = new Agefodd_place($this->db);
				$agf_place->fetch($agf->placeid);
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
			$pdf = pdf_getInstance($this->format, $this->unit, $this->orientation);
			
			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}
			
			$pdf->Open();
			$pagenb = 0;
			
			$pdf->SetTitle($outputlangs->convToOutputCharset($agf->ref_interne));
			$pdf->SetSubject($outputlangs->transnoentities("AgfAttestationEndTraining"));
			$pdf->SetCreator("Dolibarr " . DOL_VERSION . ' (Agefodd module)');
			$pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($agf->ref_interne) . " " . $outputlangs->transnoentities("Document"));
			if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION)
				$pdf->SetCompression(false);
			
			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
			$pdf->SetAutoPageBreak(1, 0);
			
			// Recuperation des stagiaires participant à la formation
			$agf2 = new Agefodd_session_stagiaire($this->db);
			$result = $agf2->fetch_stagiaire_per_session($id, $socid);
			
			if (($result && $ret)) {
				for($i = 0; $i < count($agf2->lines); $i ++) {
					// New page
					$pdf->AddPage();
					$pagenb ++;
					$this->_pagehead($pdf, $agf, 1, $outputlangs);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
					$pdf->MultiCell(0, 3, '', 0, 'J');
					$pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);
					
					$posY = $this->marge_haute;
					$posX = $this->marge_gauche;
					
					/*
					 * Header société
				 */
					
					// Logo en haut à gauche
					$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
					if ($this->emetteur->logo) {
						if (is_readable($logo)) {
							$heightLogo = pdf_getHeightForLogo($logo);
							include_once (DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php');
							$tmp = dol_getImageSize($logo);
							if ($tmp ['width']) {
								$widthLogo = $tmp ['width'];
							}
							
							if ($conf->global->AGF_USE_LOGO_CLIENT) {
								$decal=70;
							} else {
								$decal=50;
							}
							
							$pdf->Image($logo, $this->page_largeur - $this->marge_gauche - $this->marge_droite - $decal, $this->marge_haute, 0, $heightLogo, '', '', '', true, 300, '', false, false, 0, false, false, true); // width=0

							//Find global logo of the company
							if ($conf->multicompany->enabled) {
								
								//Find logo of master company
								$sql =" SELECT value FROM ".MAIN_DB_PREFIX." WHERE name='MAIN_INFO_SOCIETE_LOGO' AND entity=1";
								
								
								$logo = DOL_DATA_ROOT . '/mycompany/logos/' . $this->emetteur->logo;
							}
							                                                                                                                                                                                              // (auto)
						} else {
							$pdf->SetTextColor(200, 0, 0);
							$pdf->SetFont('', 'B', pdf_getPDFFontSize($outputlangs) - 2);
							$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'R');
							$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'R');
						}
					} else {
						$text = $this->emetteur->name;
						$pdf->SetTextColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
						$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 11);
						$pdf->MultiCell(150, 3, $outputlangs->convToOutputCharset($text), 0, 'R');
					}
					

					// Affichage du logo commanditaire (optionnel)
					if ($conf->global->AGF_USE_LOGO_CLIENT) {
						$staticsoc = new Societe($this->db);
						$staticsoc->fetch($agf->socid);
						$dir = $conf->societe->multidir_output [$staticsoc->entity] . '/' . $staticsoc->id . '/logos/';
						if (! empty($staticsoc->logo)) {
							$logo_client = $dir . $staticsoc->logo;
							if (file_exists($logo_client) && is_readable($logo_client))
								$pdf->Image($logo_client, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 30, $this->marge_haute, 40);
						}
					}
					
					// $posX += $this->page_largeur - $this->marge_droite - 65;
					
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
					$pdf->SetTextColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
					$pdf->SetXY($posX, $posY - 1);
					$pdf->Cell(0, 5, $mysoc->name, 0, 0, 'L');
					
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);
					$pdf->SetXY($posX, $posY + 3);
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
					
					$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					
					$posY = $pdf->GetY() + 10;
					
					$pdf->SetDrawColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
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
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 15);
					$pdf->SetTextColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
					$pdf->SetXY($posX, $posY);
					$this->str = $outputlangs->transnoentities('AgfPDFConvocation');
					$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');
					$posY += 14;
					
					/**
					 * *** Text Convocation ****
					 */
					
					$pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
					$this->str = $mysoc->name . ' ' . $outputlangs->transnoentities('AgfPDFConvocation1');
					$pdf->Cell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, 0);
					$posY += 8;
					
					$pdf->SetXY($posX + 10, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize);
					$this->str = ucfirst(strtolower($agf2->lines [$i]->civilitel)) . " " . $outputlangs->transnoentities($agf2->lines [$i]->prenom . ' ' . $agf2->lines [$i]->nom);
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 8;
					
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
					$this->str = $outputlangs->transnoentities('AgfPDFConvocation2');
					$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 2;
					
					$pdf->SetXY($posX + 10, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize + 3);
					
					$this->str = $agf->formintitule;
					if (! empty($agf->intitule_custo))
						$this->str = $agf->intitule_custo;
					$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 8;
					
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
					$this->str = ' ' . $outputlangs->transnoentities('AgfPDFConvocation3') . ' ';
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 3;
					
					foreach ( $agf_calendrier->lines as $line ) {
						$pdf->SetXY($posX + 10, $posY);
						$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize);
						$this->str = dol_print_date($line->date_session, 'daytext') . ' ' . $outputlangs->transnoentities('AgfPDFConvocation4') . ' ' . dol_print_date($line->heured, 'hour') . ' ' . $outputlangs->transnoentities('AgfPDFConvocation5') . ' ' . dol_print_date($line->heuref, 'hour');
						$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
						$posY = $pdf->GetY() + 2;
					}
					
					$posY = $pdf->GetY() + 8;
					
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
					$this->str = ' ' . $outputlangs->transnoentities('AgfPDFConvocation6') . ' ';
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 3;
					
					$pdf->SetXY($posX + 10, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize);
					$this->str = $agf_place->ref_interne;
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 2;
					
					$pdf->SetXY($posX + 10, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize);
					$this->str = $agf_place->adresse;
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 2;
					
					$pdf->SetXY($posX + 10, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize);
					$this->str = $agf_place->cp;
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 2;
					
					$pdf->SetXY($posX + 10, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize);
					$this->str = $agf_place->ville;
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 10;
					
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
					$this->str = $outputlangs->transnoentities('AgfPDFConvocation7');
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 8;
					
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
					$this->str = $outputlangs->transnoentities('AgfPDFConvocation8');
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 8;
					
					// Incrustation image tampon
					if ($conf->global->AGF_INFO_TAMPON) {
						$dir = $conf->agefodd->dir_output . '/images/';
						$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
						if (file_exists($img_tampon))
							$pdf->Image($img_tampon, 120, $posY, 50);
					}
					
					// Pied de page
					$this->_pagefoot($pdf, $agf, $outputlangs);
					// FPDI::AliasNbPages() is undefined method into Dolibarr 3.5
					if (method_exists($pdf, 'AliasNbPages')) {
						$pdf->AliasNbPages();
					}
				}
			}
			$pdf->Close();
			$pdf->Output($file, 'F');
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
	function _pagehead(&$pdf, $object, $showaddress = 1, $outputlangs) {
		global $conf, $langs;
		
		$outputlangs->load("main");
		
		pdf_pagehead($pdf, $outputlangs, $pdf->page_hauteur);
	}
	
	/**
	 * \brief		Show footer of page
	 * \param		pdf PDF factory
	 * \param		object			Object invoice
	 * \param		outputlang		Object lang for output
	 * \remarks	Need this->emetteur object
	 */
	function _pagefoot(&$pdf, $object, $outputlangs) {
		global $conf, $langs, $mysoc;
		
		$pdf->SetDrawColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
		$pdf->Line($this->marge_gauche, $this->page_hauteur - 20, $this->page_largeur - $this->marge_droite, $this->page_hauteur - 20);
		
		$this->str = $mysoc->name;
		
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
		$pdf->SetTextColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
		$pdf->SetXY($this->marge_gauche, $this->page_hauteur - 20);
		$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');
		
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
		
		$pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', 7);
		$pdf->SetXY($this->marge_gauche, $this->page_hauteur - 16);
		$pdf->MultiCell(0, 3, $outputlangs->convToOutputCharset($this->str), 0, 'C');
	}
}