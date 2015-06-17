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
 * \file agefodd/core/modules/agefodd/pdf/pdf_fiche_evaluation.modules.php
 * \ingroup agefodd
 * \brief PDF for satisfaction sheet
 */
dol_include_once('/agefodd/core/modules/agefodd/agefodd_modules.php');
require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_formation_catalogue.class.php');
require_once ('../class/agefodd_session_formateur.class.php');
require_once ('../class/agefodd_contact.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
require_once ('../lib/agefodd.lib.php');
class pdf_fiche_evaluation_irspp extends ModelePDFAgefodd {
	var $emetteur; // Objet societe qui emet
	               
	// Definition des couleurs utilisées de façon globales dans le document (charte)
	protected $colorfooter;
	protected $colortext;
	protected $colorhead;
	
	/**
	 * \brief		Constructor
	 * \param		db		Database handler
	 */
	function __construct($db) {
		global $conf, $langs, $mysoc;
		
		$this->db = $db;
		$this->name = "fiche_evaluation";
		$this->description = $langs->trans('AgfModPDFFicheEval');
		
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
	 * \brief 			Fonction generant le document sur le disque
	 * \param agf		Objet document a generer (ou id si ancienne methode)
	 * \outputlangs		Lang object for output language
	 * \file			Name of file to generate
	 * \return int 		1=ok, 0=ko
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
			
			// On recupere les infos societe
			$agf_soc = new Societe($this->db);
			$result = $agf_soc->fetch($socid);
			
			if ($result) {
				// New page
				//Ficher modéle a importer
				$infile = DOL_DATA_ROOT . '/agefodd/eval-IRSPP.pdf';

				//Le fichier existe 
				if (is_file($infile)) {

					//On compte le nombre de page du fichier source à importé
					$count = $pdf->setSourceFile($infile);

					// import all pages
					for($i = 1; $i <= $count; $i ++) {
						// New page
						$pdf->AddPage();

						$posX = $this->marge_gauche;
						$posY = $this->marge_haute;

						//Importer le document
						$tplIdx = $pdf->importPage($i);
						$pdf->useTemplate($tplIdx, 0, 0, $this->page_largeur);
					}

					//On se met sur la page importée
					$pdf->setPage(1);					
				}
				$pagenb ++;
				$this->_pagehead($pdf, $agf, 1, $outputlangs);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
				$pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);
				
				$posY = $this->marge_haute;
				$posX = $this->marge_gauche;
				
				/*
				 * Corps de page
				*/
				$posY = $pdf->GetY() + 10;
				$posX = $this->marge_gauche;
				
				/**
				 * *** Titre ****
				 */
				
				// Haut
				$this->marge_top=$this->marge_haute+30;
				$pdf->SetDrawColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
				$pdf->Line($this->marge_gauche, $this->marge_top, $this->page_largeur - $this->marge_droite, $this->marge_top);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B,I', 20);
				$pdf->SetTextColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
				$pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfFicheEval2');
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');
				$posY += 10;
				// Bas
				$pdf->Line($this->marge_gauche, $this->marge_top+20, $this->page_largeur - $this->marge_gauche, $this->marge_top+20);
				
				$posY = $pdf->GetY() + 15;
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
				$pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);
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
				//$this->str = $outputlangs->transnoentities('AgfPDFFicheEval1') . " ";
				if ($agf->dated == $agf->datef)
					$this->str .= dol_print_date($agf->dated);
				else
					$this->str .= ' ' . dol_print_date($agf->dated) . ' au ' . dol_print_date($agf->datef);
				//$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');
				$posY += 4;
				
				$formateurs = new Agefodd_session_formateur($this->db);
				$nbform = $formateurs->fetch_formateur_per_session($agf->id);
				$form_str = "";
				for($i = 0; $i < $nbform; $i ++) {
					// Infos formateurs
					$forma_str .= strtoupper($formateurs->lines [$i]->lastname) . ' ' . ucfirst($formateurs->lines [$i]->firstname);
					if ($i < ($nbform - 1))
						$forma_str .= ', ';
				}
				
				
				/**
				 * *** Trainee Information ************
				 */
				
				$pdf->SetXY($posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfPDFFicheEvalNameTrainee') . ' : ';
				$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', 0);
				$this->str = $outputlangs->transnoentities('AgfPDFFicheEvalCompany') . ' : '.$agf->socid;
				$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', 0);
				$this->str = $outputlangs->transnoentities('AgfPDFFichePres21') . '  ';
				if ($agf->dated == $agf->datef)
					$this->str .= dol_print_date($agf->dated);
				else
					$this->str .= ' ' . dol_print_date($agf->dated) . ' au ' . dol_print_date($agf->datef);
				$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', 0);
				
				$posY = $pdf->GetY() - 5;
				$pdf->SetXY($posX, $posY);
				($nbform > 1) ? $this->str = $outputlangs->transnoentities('AgfPDFFicheEval2') . " " : $this->str = $outputlangs->transnoentities('AgfPDFFicheEval3') . " ";
				$this->str .= $forma_str;
				$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'C', 0);
						
				
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
		
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		
		pdf_pagehead($pdf, $outputlangs, $pdf->page_hauteur);
		
		$pdf->SetTextColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
		
		$posy=$this->marge_haute;
		$posx=$this->page_largeur-$this->marge_droite-55;
		
		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
				$height = pdf_getHeightForLogo($logo);
				$width_logo=pdf_getWidthForLogo($logo);
				if ($width_logo>0) {
					$posx=$this->page_largeur-$this->marge_droite-$width_logo;
				} else {
					$posx=$this->page_largeur-$this->marge_droite-55;
				}
				$pdf->Image($logo, $posx, $posy, 0, $height);
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B',$default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}
		// Other Logo
		if ($conf->multicompany->enabled) {
			$sql = 'SELECT value FROM '.MAIN_DB_PREFIX.'const WHERE name =\'MAIN_INFO_SOCIETE_LOGO\' AND entity=1';
			$resql=$this->db->query($sql);
			if (!$resql) {
				setEventMessage($this->db->lasterror,'errors');
			} else {
				$obj=$this->db->fetch_object($resql);
				$image_name=$obj->value;
			}
			if (!empty($image_name)) {
				$otherlogo=DOL_DATA_ROOT . '/mycompany/logos/'.$image_name;
				if (is_readable($otherlogo))
				{
					$logo_height=pdf_getHeightForLogo($otherlogo);
					$width_otherlogo=pdf_getWidthForLogo($otherlogo);
					if ($width_otherlogo>0 && $width_logo>0) {
						$posx=$this->page_largeur-$this->marge_droite-$width_otherlogo-$width_logo-10;
					} else {
						$posx=$this->marge_gauche+100;
					}
					
					$pdf->Image($otherlogo, $posx, $posy, 0, $logo_height);	
				}
			}
		}
		if ($showaddress)
		{
			// Sender properties
			// Show sender
			$posy=$this->marge_haute;
		 	$posx=$this->marge_gauche;

			$hautcadre=30;
			$pdf->SetXY($posx,$posy);
			$pdf->SetFillColor(255,255,255);
			$pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);

			// Show sender name
			$pdf->SetXY($posx,$posy);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy=$pdf->getY();

			// Show sender information
			$pdf->SetXY($posx,$posy);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
			$posy=$pdf->getY();
			$pdf->SetXY($posx,$posy);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->zip.' '.$this->emetteur->town), 0, 'L');
			$posy=$pdf->getY();
			$pdf->SetXY($posx,$posy);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
			$posy=$pdf->getY();
			$pdf->SetXY($posx,$posy);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');
			$posy=$pdf->getY();
		}
	}
	
	/**
	 * \brief		Show footer of page
	 * \param		pdf PDF factory
	 * \param		object			Object invoice
	 * \param		outputlang		Object lang for output
	 * \remarks	Need this->emetteur object
	 */
	function _pagefoot(&$pdf, $object, $outputlangs) {
		$pdf->SetTextColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
		$pdf->SetDrawColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
		return pdf_agfpagefoot($pdf,$outputlangs,'',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,1,$hidefreetext);
	}
}