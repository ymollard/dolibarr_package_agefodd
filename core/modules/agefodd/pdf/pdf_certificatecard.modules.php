<?php
/*
 * Copyright (C) 2012-2014  Florian Henry <florian.henry@open-concept.pro>
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
 * \brief PDF for certificate (from certificate optionnal option credit card format)
 */
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_formation_catalogue.class.php');
require_once ('../class/agefodd_session_calendrier.class.php');
require_once ('../class/agefodd_stagiaire_certif.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
require_once ('../lib/agefodd.lib.php');
require_once ('../class/agefodd_session_stagiaire.class.php');
class pdf_certificatecard extends ModelePDFAgefodd {
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
		
		$langs->load("agefodd@agefodd");
		
		$this->db = $db;
		$this->name = 'conseil';
		$this->description = $langs->trans('AgfModPDFConvocation');
		
		// Dimension page pour format A4 en portrait
		$this->type = 'pdf';
		$this->page_largeur = 85.60;
		$this->page_hauteur = 53.89;
		$this->format = array (
				$this->page_largeur,
				$this->page_hauteur 
		);
		$this->marge_gauche = 3;
		$this->marge_droite = 10;
		$this->marge_haute = 3;
		$this->marge_basse = 10;
		$this->defaultFontSize = 10;
		$this->unit = 'mm';
		$this->oriantation = 'L';
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
		global $user, $langs, $conf, $mysoc;
		
		if (! is_object($outputlangs))
			$outputlangs = $langs;
		
		if (! is_object($agf)) {
			$id = $agf;
			$agf = new Agsession($this->db);
			$ret = $agf->fetch($id);
			if ($ret) {
				$agf_training = new Agefodd($this->db);
				$agf_training->fetch($agf->formid);
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
			$pdf->SetSubject($outputlangs->transnoentities("Conseils"));
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
					
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
					$pdf->MultiCell(0, 3, '', 0, 'J');
					$pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);
					
					$posY = $this->marge_haute;
					$posX = $this->marge_gauche;
					
					/*
					 * Corps de page
					*/
					
					$posX = $this->marge_gauche;
					$posY = $posY + 3;
					
					/**
					 * *** Text Certificate ****
					 */
					// Training name
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize + 2);
					$this->str = $agf_training->note_private;
					$pdf->writeHTMLCell(190, 3, $posX, $posY, dol_htmlentitiesbr($agf_training->note_private), 0, 1);
					$posY = $pdf->GetY() + 1;
					
					// Trainne Name
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize + - 2);
					$this->str = $outputlangs->transnoentities('AgfPDFCertificateCard1');
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					
					$pdf->SetXY($posX + 30, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize + - 2);
					$this->str = $outputlangs->transnoentities($agf2->lines [$i]->prenom . ' ' . ucfirst($agf2->lines [$i]->nom));
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 1;
					
					// Certificate number
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize + - 2);
					$this->str = $outputlangs->transnoentities('AgfPDFCertificateCard2');
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					
					$agf_certif = new Agefodd_stagiaire_certif($this->db);
					$agf_certif->fetch(0, $agf2->lines [$i]->traineeid, $agf2->lines [$i]->$id, $agf2->lines [$i]->stagerowid);
					
					$pdf->SetXY($posX + 30, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize + - 2);
					$this->str = $agf_certif->certif_code;
					$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 1;
					
					// Date of birth
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize + - 2);
					$this->str = $outputlangs->transnoentities('AgfDateBirth') . ":";
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					
					$date_birth = dol_print_date($agf2->lines [$i]->date_birth, 'daytext');
					$pdf->SetXY($posX + 30, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize + - 2);
					$this->str = $outputlangs->transnoentities($date_birth);
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 1;
					
					// Valid until
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize + - 2);
					$this->str = $outputlangs->transnoentities('AgfPDFCertificateCard3');
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					
					$pdf->SetXY($posX + 30, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize + - 2);
					$this->str = dol_print_date($agf_certif->certif_dt_end, 'daytext');
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
					$posY = $pdf->GetY() + 1;
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
}