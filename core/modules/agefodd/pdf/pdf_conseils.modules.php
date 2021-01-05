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
 * \file agefodd/core/modules/agefodd/pdf/pdf_conseils.modules.php
 * \ingroup agefodd
 * \brief PDF for advise
 */
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_contact.class.php');
dol_include_once('/agefodd/class/agefodd_place.class.php');
dol_include_once('/agefodd/class/agefodd_reginterieur.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
class pdf_conseils extends ModelePDFAgefodd {
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
	function pdf_conseils($db) {
		global $conf, $langs, $mysoc;

		$langs->load("agefodd@agefodd");

		$this->db = $db;
		$this->name = 'conseil';
		$this->description = $langs->trans('AgfModPDFConseil');

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
		$this->orientation = $this->oriantation;
		$this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
		$this->milieu = $this->espaceH_dispo / 2;
		$this->espaceV_dispo = $this->page_hauteur - ($this->marge_haute + $this->marge_basse);
		$this->default_font_size=12;


		// gestion des marge en fonction de l'orientation : fonction du fond PDF
		if($conf->global->{'AGF_MARGE_GAUCHE_'.$this->orientation}){
			$this->marge_gauche = $conf->global->{'AGF_MARGE_GAUCHE_'.$this->orientation};
		}
		if($conf->global->{'AGF_MARGE_DROITE_'.$this->orientation}){
			$this->marge_droite = $conf->global->{'AGF_MARGE_DROITE_'.$this->orientation};
		}
		if($conf->global->{'AGF_MARGE_HAUTE_'.$this->orientation}){
			$this->marge_haute = $conf->global->{'AGF_MARGE_HAUTE_'.$this->orientation};
		}
		if($conf->global->{'AGF_MARGE_BASSE_'.$this->orientation}){
			$this->marge_basse = $conf->global->{'AGF_MARGE_BASSE_'.$this->orientation};
		}

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
	 * \param agf		Objet document a generer (ou id si ancienne methode)
	 * outputlangs	Lang object for output language
	 * file		Name of file to generate
	 * \return int 1=ok, 0=ko
	 */
	function write_file($agf, $outputlangs, $file, $socid, $courrier) {
		global $user, $langs, $conf, $mysoc;

		if (! is_object($outputlangs))
			$outputlangs = $langs;

		if (! is_object($agf)) {
			$id = $agf;
			$agf_session = new Agsession($this->db);
			$ret = $agf_session->fetch($id);
			if ($ret) {
				$agf = new Formation($this->db);
				$agf->fetch($agf_session->formid);

				$agf_place = new Agefodd_place($this->db);
				$agf_place->fetch($agf_session->placeid);
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
			$pdf->SetAutoPageBreak(1, $this->marge_basse);

			// Set path to the background PDF File
			if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->AGF_ADD_PDF_BACKGROUND_P))
			{
				$pagecount = $pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P);
				$tplidx = $pdf->importPage(1);
			}

			// On recupere les infos societe
			$agf_soc = new Societe($this->db);
			$result = $agf_soc->fetch($socid);

			$ishtml = 1; // $conf->global->FCKEDITOR_ENABLE_SOCIETE ? 1 : 0;  il est préférable de partir du principe qu'il est tjr activé car il n'y à pas que cette conf il y a aussi les substitutions (doc edit). Il faudra alors gérer le cas inverse qui est plus rare mais au cas par cas, une detection html avant multicell peut-être ?

			if ($result) {
				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pdf->SetAutoPageBreak(1, $this->marge_basse);

				$pagenb ++;
				$this->_pagehead($pdf, $agf, 1, $outputlangs);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
				$pdf->SetTextColor($this->colorheaderText [0], $this->colorheaderText [1], $this->colorheaderText [2]);


				$posY=$this->marge_haute;
				$posX=$this->page_largeur-$this->marge_droite-55;

				// Logo
				$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
				if ($this->emetteur->logo)
				{
					if (is_readable($logo))
					{
						$height=pdf_getHeightForLogo($logo);
						$width_logo=pdf_getWidthForLogo($logo);
						if ($width_logo>0) {
							$posX=$this->page_largeur-$this->marge_droite-$width_logo;
						} else {
							$posX=$this->page_largeur-$this->marge_droite-55;
						}
						$pdf->Image($logo, $posX, $posY, 0, $height);
					}
					else
					{
						$pdf->SetTextColor(200,0,0);
						$pdf->SetFont('','B',$this->default_font_size - 2);
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

				$posy=$this->marge_haute;
				$posx=$this->marge_gauche;

				$hautcadre=30;
				$pdf->SetXY($posx,$posy);
				$pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);

				// Show sender name
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','B', $this->default_font_size -2);
				$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
				$posy=$pdf->GetY();

				// Show sender information
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $this->default_font_size - 3);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
				$posy=$pdf->GetY();
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $this->default_font_size - 3);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->zip.' '.$this->emetteur->town), 0, 'L');
				$posy=$pdf->GetY();
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $this->default_font_size - 3);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
				$posy=$pdf->GetY();
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $this->default_font_size - 3);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');
				$posy=$pdf->GetY();

				printRefIntForma($this->db, $outputlangs, $agf, $this->default_font_size - 3, $pdf, $posx, $posy, 'L');


				$pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);

				$posY = $pdf->GetY() + 10;
				if ($conf->global->AGF_PRINT_INTERNAL_REF_ON_PDF) $posY -= 5;


				$pdf->SetDrawColor($this->colorLine [0], $this->colorLine [1], $this->colorLine [2]);
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
				$posY += 5;

				/**
				 * *** Titre ****
				 */
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size+3);
				$pdf->SetTextColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
				$pdf->SetXY($posX, $posY);
				$this->str = $langs->trans("AgfConseilsPratique");
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');
				$posY = $pdf->GetY() + 10;

				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
				$pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);
				$this->str = $agf_session->intitule_custo;

				$hauteur = dol_nboflines_bis($this->str, 50) * 4;

				// cadre
				$pdf->SetFillColor(255);
				$pdf->Rect($posX, $posY - 1, $this->espaceH_dispo, $hauteur + 3);
				// texte
				$pdf->SetXY($posX, $posY);
				$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'C');
				$posY = $pdf->GetY() + 10;

				/**
				 * *** Doucment required ****
				 */

				if (!empty($agf->note1)) {
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size); // $pdf->SetFont('Arial','B',9);
					$pdf->SetXY($posX, $posY);
					$this->str = $langs->transnoentities("AgfDocNeeded");
					$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'L', false, 1, '', '', true, 0,  $ishtml);
					$posY += 5;

					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
					$this->str = ucfirst($agf->note1);
					$pdf->SetXY($posX, $posY);

					$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', '', '2', '', '', '', '', $ishtml);

					$posY = $pdf->GetY() + 8;
				}

				/**
				 * *** Equipement required ****
				 */

				if (!empty($agf->note2)) {
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size); // $pdf->SetFont('Arial','B',9);
					$pdf->SetXY($posX, $posY);
					$this->str = $langs->transnoentities("AgfEquiNeeded");
					$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'L', false, 1, '', '', true, 0,  $ishtml);
					$posY += 5;

					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
					$this->str = ucfirst($agf->note2);

					$pdf->SetXY($posX, $posY);
					$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', '', '2', '', '', '', '', $ishtml);
					$posY = $pdf->GetY() + 8;
				}

				/**
				 * *** Site ****
				 */

				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size); // $pdf->SetFont('Arial','B',9);
				$pdf->SetXY($posX, $posY);
				$this->str = $langs->transnoentities("AgfLieu");
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'L');
				$posY += 5;

				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
				$this->str = ucfirst($agf_session->placecode);

				$pdf->SetXY($posX, $posY);
				$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', false, 1, '', '', true, 0,  $ishtml);

				$posY = $pdf->GetY() + 2;

				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
				$this->str = $agf_place->adresse . ' - ' . $agf_place->cp . ' ' . $agf_place->ville;

				$pdf->SetXY($posX, $posY);
				$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', false, 1, '', '', true, 0,  $ishtml);
				$posY = $pdf->GetY() + 8;

				/**
				 * *** Acces au sites ****
				 */

				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size); // $pdf->SetFont('Arial','B',9);
				$pdf->SetXY($posX, $posY);
				$pdf->startTransaction();
				$pageposBeforeSiteAccess = $pdf->getPage();
				$posYBeforeSiteAccess = $posY;

				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
				$this->str = '<strong>'.$langs->transnoentities("AgfAccesSite").'</strong><br/>'.$this->str;
				$this->str.= strtr($agf_place->acces_site, array('src="'.dol_buildpath('viewimage.php', 1) => 'src="'.dol_buildpath('viewimage.php', 2), '&amp;'=>'&'));

				$pdf->SetXY($posX, $posY);

				$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', '', '2', '', '', '', '', $ishtml);
				$posY = $pdf->GetY() + 8;

				$pageposAfterSiteAccess=$pdf->getPage();
				if($pageposBeforeSiteAccess < $pageposAfterSiteAccess){
					$pdf->rollbackTransaction(true);
					$posY = $posYBeforeSiteAccess;
					$pagenb = $pdf->getPage();

					// prepar pages to receive site access
					while ($pagenb < $pageposAfterSiteAccess) {
						$pdf->AddPage();
						$pagenb++;

						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$pdf->SetAutoPageBreak(1, $this->marge_basse);
						$this->_pagehead($pdf, $agf, 1, $outputlangs);
					}

					// back to start
					$pdf->setPage($pageposBeforeSiteAccess);
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);
					$pdf->SetXY($posX, $posY);
					$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', '', '2', '', '', '', '', $ishtml);

					$posY = $pdf->GetY() + 8;
				}
				else{
					$pdf->commitTransaction();
				}
				/**
				 * *** Divers ****
				 */

				$pdf->startTransaction();
				$pageposBeforeDivers = $pdf->getPage();
				$posYBeforeDivers = $pdf->GetY();

				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size);
				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);

				$this->str = '<strong>'.$langs->transnoentities("AgfPlaceNote1").'</strong></br>';
				$this->str.= strtr($agf_place->note1, array('src="'.dol_buildpath('viewimage.php', 1) => 'src="'.dol_buildpath('viewimage.php', 2), '&amp;'=>'&'));;

				$pdf->SetXY($posX, $posY);
				$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', '', '2', '', '', '', '', 1);
				$posY = $pdf->GetY() + 8;


				$pageposAfterDivers=$pdf->getPage();
				if($pageposBeforeDivers < $pageposAfterDivers) {
					$pdf->rollbackTransaction(true);
					$pagenb = $pdf->getPage();
					$posY = $posYBeforeDivers;

					// prepar pages to receive Divers
					while ($pagenb < $pageposAfterDivers) {
						$pdf->AddPage();
						$pagenb++;

						if (!empty($tplidx)) $pdf->useTemplate($tplidx);
						$pdf->SetAutoPageBreak(1, $this->marge_basse);
						$this->_pagehead($pdf, $agf, 1, $outputlangs);
					}
					$pdf->SetFillColor(255,255,0);
					// back to start
					$pdf->setPage($pageposBeforeDivers);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->default_font_size);
					$pdf->SetXY($posX, $posY);

					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size);

					$pdf->SetXY($posX, $posY);
					$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L', 0, '2', '', '', '', '', $ishtml);
					$posY = $pdf->GetY() + 8;

				}
				else{
					$pdf->commitTransaction();
				}


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
	 * \param outputlangs		Object lang for output
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
	 * \brief		Show footer of page
	 * \param		pdf PDF factory
	 * \param		object			Object invoice
	 * \param		outputlang		Object lang for output
	 * \remarks	Need this->emetteur object
	 */
	function _pagefoot(&$pdf, $object, $outputlangs) {
		global $conf, $langs, $mysoc;

		$pdf->SetAutoPageBreak(0, 0);
		$pdf->SetDrawColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
		$pdf->SetTextColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
		$pdf_agfpagefoot = pdf_agfpagefoot($pdf,$outputlangs,'',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,1);
		$pdf->SetAutoPageBreak(1, 0);
		return $pdf_agfpagefoot;
	}

}
