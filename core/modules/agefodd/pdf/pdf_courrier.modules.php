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
 * \file agefodd/core/modules/agefodd/pdf/pdf_courrier.module.php
 * \ingroup agefodd
 * \brief PDF for core body for letters
 */
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_contact.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
class pdf_courrier extends ModelePDFAgefodd {
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
	function pdf_courrier($db) {
		global $conf, $langs, $mysoc;

		$this->db = $db;
		$this->name = "courrier";
		$this->description = $langs->trans('AgfModPDFCourrier');

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
						$pdf->SetFont('', 'B', $this->default_font_size - 2);
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

				$posy = $this->marge_haute;
				$posx = $this->marge_gauche;

				$hautcadre = 30;
				$pdf->SetXY($posx, $posy);
				$pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);

				// Show sender name
				$pdf->SetXY($posx, $posy);
				$pdf->SetFont('', 'B', $this->default_font_size - 2);
				$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
				$posy = $pdf->GetY();

				// Show sender information
				$pdf->SetXY($posx, $posy);
				$pdf->SetFont('', '', $this->default_font_size - 3);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
				$posy = $pdf->GetY();
				$pdf->SetXY($posx, $posy);
				$pdf->SetFont('', '', $this->default_font_size - 3);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->zip . ' ' . $this->emetteur->town), 0, 'L');
				$posy = $pdf->GetY();
				$pdf->SetXY($posx, $posy);
				$pdf->SetFont('', '', $this->default_font_size - 3);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
				$posy = $pdf->GetY();
				$pdf->SetXY($posx, $posy);
				$pdf->SetFont('', '', $this->default_font_size - 3);
				$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');
				$posy = $pdf->GetY();

				printRefIntForma($this->db, $outputlangs, $agf, $this->default_font_size - 3, $pdf, $posx, $posy, 'L');

				// Affichage du logo commanditaire (optionnel)
				if ($conf->global->AGF_USE_LOGO_CLIENT) {
					$staticsoc = new Societe($this->db);
					$staticsoc->fetch($agf->socid);
					$dir = $conf->societe->multidir_output[$staticsoc->entity] . '/' . $staticsoc->id . '/logos/';
					if (! empty($staticsoc->logo)) {
						$logo_client = $dir . $staticsoc->logo;
						if (file_exists($logo_client) && is_readable($logo_client)){
							$hlogo = pdf_getHeightForLogo($logo_client);
							$wlogo = pdf_getWidthForLogo($logo_client);
							$X =  ($this->page_largeur / 2) - ($wlogo / 2) ;
							$Y = $this->marge_haute;
							$pdf->Image($logo_client,$X ,$Y, $wlogo, $hlogo,'','','',true);
						}
					}
				}

				$posY = $pdf->GetY() + 10;

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

				$posX = 100;
				$posY = 42;

				// Destinataire

				// Show recipient name
				$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
				$pdf->SetXY($posX + 20, $posY + 3);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 12);
				$this->str = $agf_soc->name;
				$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');

				// Show recipient information

				$this->madame_monsieur = $outputlangs->transnoentities('AgfPDFCourrierAcceuil4');
				$this->str='';
				$findadress=false;

				//If session contact is set we set receipents as contact
				$agf_contact = new Agefodd_contact($this->db);
				if(!empty($agf->contactid)) {
					$result = $agf_contact->fetch($agf->contactid, 'contact');
					$contact_static = new Contact($this->db);
					$contact_static->fetch($agf_contact->spid);
					$this->madame_monsieur = $contact_static->getCivilityLabel();

					$this->str = $contact_static->getFullName($outputlangs). "\n";
					$this->str .= $contact_static->address . "\n" . $contact_static->zip . ' ' . $contact_static->town;
					$findadress =true;
				} else {
					//If session contact is not set we try to find a sta signataire convention
					$sta = new Agefodd_session_stagiaire($this->db);
					$result=$sta->fetch_stagiaire_per_session($agf->id,$socid);
					if ($result>0) {
						foreach($sta->lines as $line_sta) {
							if (!empty($line_sta->fk_socpeople_sign)) {
								$contact_static = new Contact($this->db);
								$contact_static->fetch($line_sta->fk_socpeople_sign);
								$this->madame_monsieur = $contact_static->getCivilityLabel();

								$this->str = $contact_static->getFullName($outputlangs). "\n";
								$this->str .= $contact_static->address . "\n" . $contact_static->zip . ' ' . $contact_static->town;
								$findadress =true;
								break;
							}
						}
					}
				}
				//else we output just customer adress
				if (!$findadress) {
					$this->str = $agf_soc->address . "\n" . $agf_soc->zip . ' ' . $agf_soc->town;
				}

				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
				$posY = $pdf->GetY() - 9; // Auto Y coord readjust for multiline name
				$pdf->SetXY($posX + 20, $posY + 10);
				$pdf->MultiCell(86, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');

				// Date
				$posY = $posY + 50;
				$this->str = ucfirst(strtolower($mysoc->town)) . ', ' . $outputlangs->transnoentities('AgfPDFFichePres8') . ' ' . dol_print_date(dol_now(), 'daytext');
				$pdf->SetXY($posX + 20, $posY);
				$pdf->MultiCell(96, 4, $outputlangs->convToOutputCharset($this->str), 0, "L");

				// Corps du courrier
				$posY = $this->_body($pdf, $agf, $outputlangs, $courrier, $id, $socid);

				// Signataire
				$pdf->SetXY($posX + 10, $posY + 10);
				$this->str = $conf->global->AGF_ORGANISME_REPRESENTANT . "\n" . $outputlangs->transnoentities('AgfPDFCourrierRep');
				$pdf->MultiCell(50, 4, $outputlangs->convToOutputCharset($this->str), 0, 'C');

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
	 * \brief Show body of page
	 * \param pdf Object PDF
	 * \param object Object invoice
	 * \param outputlangs Object lang for output
	 * \param courrier Name of couurier type (for include)
	 * \param id session id
	 */
	function _body(&$pdf, $object, $outputlangs, $courrier, $id, $socid) {
		global $user, $conf, $langs;

		$override = false;
		if (! empty($conf->global->AGF_PDF_MODEL_OVERRIDE))
		{
			$modelarray = explode('&', $conf->global->AGF_PDF_MODEL_OVERRIDE);
			if (is_array($modelarray) && count($modelarray) > 0) {
					foreach ( $modelarray as $modeloveride ) {
							$modeloverridearray = explode(':', $modeloveride);
							if (is_array($modeloverridearray) && count($modeloverridearray) > 0) {
									if ($modeloverridearray[0] == $courrier) {
											$courrier = $modeloverridearray[1];
											$override = true;
											break;
									}
							}
					}
			}
		}

		if ($override) require (dol_buildpath('/agefodd/core/modules/agefodd/pdf/override/pdf_courrier_' . $courrier . '.modules.php'));
		else require (dol_buildpath('/agefodd/core/modules/agefodd/pdf/pdf_courrier_' . $courrier . '.modules.php'));

		return $posY;
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
