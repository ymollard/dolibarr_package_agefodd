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
 * \file agefodd/core/modules/agefodd/pdf/pdf_convocation.modules.php
 * \ingroup agefodd
 * \brief PDF for convocation
 */
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
dol_include_once('/agefodd/class/agefodd_place.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
class pdf_convocation extends ModelePDFAgefodd {
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
		$this->name = 'convocation';
		$this->description = $langs->trans('AgfModPDFConvocation');

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
		$this->defaultFontSize = 12;
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
	function write_file($agf, $outputlangs, $file, $socid) {
		global $user, $langs, $conf, $mysoc;

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
			$pdf->SetSubject($outputlangs->transnoentities("AgfModPDFConvocation"));
			$pdf->SetCreator("Dolibarr " . DOL_VERSION . ' (Agefodd module)');
			$pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($agf->ref_interne) . " " . $outputlangs->transnoentities("Document"));
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

			// Recuperation des stagiaires participant à la formation
			$agf2 = new Agefodd_session_stagiaire($this->db);
			$result = $agf2->fetch_stagiaire_per_session($id, $socid);
			$nbtraineePage= 0;

			if (($result && $ret)) {
				for($i = 0; $i < count($agf2->lines); $i ++) {
					if ($conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES!=='') {
						$TStagiaireStatusToExclude = explode(',', $conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES);
						$status_stagiaire = (int) $agf2->lines[$i]->status_in_session;
						if (in_array($status_stagiaire, $TStagiaireStatusToExclude)) {
							setEventMessage($langs->trans('AgfStaNotInStatusToOutput', $agf2->lines[$i]->nom), 'warnings');
							continue;
						}
					}
					$nbtraineePage++;
					// New page
					$pdf->AddPage();
					if (! empty($tplidx)) $pdf->useTemplate($tplidx);

					$pagenb ++;
					$this->_pagehead($pdf, $agf, 1, $outputlangs);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
					$pdf->MultiCell(0, 3, '', 0, 'J');

					$outputlangs->load("main");

					$default_font_size = pdf_getPDFFontSize($outputlangs);

					pdf_pagehead($pdf, $outputlangs, $pdf->page_hauteur);

					$pdf->SetTextColor($this->colorheaderText[0], $this->colorheaderText[1], $this->colorheaderText[2]);

					$posy = $this->marge_haute;
					$posx = $this->page_largeur - $this->marge_droite - 55;

					// Logo
					$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
					if ($this->emetteur->logo) {
						if (is_readable($logo)) {
							$height = pdf_getHeightForLogo($logo);
							$width_logo=pdf_getWidthForLogo($logo);
							if ($width_logo>0) {
								$posx=$this->page_largeur-$this->marge_droite-$width_logo;
							} else {
								$posx=$this->page_largeur-$this->marge_droite-55;
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

					printRefIntForma($this->db, $outputlangs, $agf, $default_font_size - 1, $pdf, $posx, $posy, 'L');

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
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 15);
					$pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
					$pdf->SetXY($posX, $posY);
					$this->str = $outputlangs->transnoentities('AgfPDFConvocation');
					$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C');
					$posY += 14;

					/**
					 * *** Text Convocation ****
					 */

					$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->defaultFontSize);
					$this->str = $mysoc->name . ' ' . $outputlangs->transnoentities('AgfPDFConvocation1');
					$pdf->Cell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, 0);
					$posY += 8;

					$pdf->SetXY($posX + 10, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize);
					$contact_static = new Contact($this->db);
					$contact_static->civility_id = $agf2->lines[$i]->civilite;
					$this->str = ucfirst(strtolower($contact_static->getCivilityLabel())) . " " . $outputlangs->transnoentities($agf2->lines[$i]->prenom . ' ' . $agf2->lines[$i]->nom);
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
					$posY = $pdf->GetY();

					$this->str ='';
					$old_date='';
					foreach ($agf_calendrier->lines as $line) {
						if ($line->date_session != $old_date) {
							$this->str .= "\n";
							$this->str .= dol_print_date($line->date_session, 'daytext') . ' ' . $outputlangs->transnoentities('AgfPDFConvocation4') . ' ' . dol_print_date($line->heured, 'hour') . ' ' . $outputlangs->transnoentities('AgfPDFConvocation5') . ' ' . dol_print_date($line->heuref, 'hour');
						} else {
							$this->str .= ', ';
							$this->str .= dol_print_date($line->heured, 'hour') . ' - ' . dol_print_date($line->heuref, 'hour');
						}
						$old_date = $line->date_session;
					}

					$pdf->SetXY($posX + 10, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', $this->defaultFontSize);
					//$this->str = dol_print_date($line->date_session, 'daytext') . ' ' . $outputlangs->transnoentities('AgfPDFConvocation4') . ' ' . dol_print_date($line->heured, 'hour') . ' ' . $outputlangs->transnoentities('AgfPDFConvocation5') . ' ' . dol_print_date($line->heuref, 'hour');
					$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');

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
					$this->str = $agf_place->cp . ' ' . $agf_place->ville;
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

					if (!empty($conf->global->AGF_ADD_SIGN_TO_CONVOC)) {
						// Incrustation image tampon
						if ($conf->global->AGF_INFO_TAMPON) {
							$dir = $conf->agefodd->dir_output . '/images/';
							$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
							if (file_exists($img_tampon))
								$pdf->Image($img_tampon, 120, $posY, 50);
						}
					}

					// Pied de page
					$this->_pagefoot($pdf, $agf, $outputlangs);

					/*
					 * Page 4 (Annexe 1)
					 */
					if (! empty($conf->global->AGF_MERGE_ADVISE_AND_CONVOC)) {

						// this configuration variable is designed like
						// standard_model_name:new_model_name&standard_model_name:new_model_name&....
						$model='conseils';
						$fileconseils = $model . '_' . $agf->id . '.pdf';
						if (! empty($conf->global->AGF_PDF_MODEL_OVERRIDE) && ($model != 'convention')) {
							$modelarray = explode('&', $conf->global->AGF_PDF_MODEL_OVERRIDE);
							if (is_array($modelarray) && count($modelarray) > 0) {
								foreach ( $modelarray as $modeloveride ) {
									$modeloverridearray = explode(':', $modeloveride);
									if (is_array($modeloverridearray) && count($modeloverridearray) > 0) {
										if ($modeloverridearray[0] == $model) {
											$model = $modeloverridearray[1];
										}
									}
								}
							}
						}
						$result = agf_pdf_create($this->db, $agf->id, '', $model, $outputlangs, $fileconseils, 0);

						$infileconseil = $conf->agefodd->dir_output.'/'. $fileconseils;
						if (is_file($infileconseil)) {
							$countconseil = $pdf->setSourceFile($infileconseil);
							// import all page
							for($iconseil = 1; $iconseil <= $countconseil; $iconseil ++) {
								// New page
								$pdf->AddPage();
								$tplIdxconseil = $pdf->importPage($iconseil);
								$pdf->useTemplate($tplIdxconseil);
							}
						}
					}

					if (method_exists($pdf, 'AliasNbPages')) {
						$pdf->AliasNbPages();
					}
				}
			}

			$pdf->Close();
			if ($nbtraineePage>0) {
				$pdf->Output($file, 'F');
				if (!empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));
			}


			// Add pdfgeneration hook
			if (! is_object($hookmanager))
			{
				include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
				$hookmanager=new HookManager($this->db);
			}
			$hookmanager->initHooks(array('pdfgeneration'));
			$parameters=array('file'=>$file,'object'=>$agf,'outputlangs'=>$outputlangs);
			global $action;
			$reshook=$hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks


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
		$pdf->SetTextColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
		$pdf->SetDrawColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
		return pdf_agfpagefoot($pdf,$outputlangs,'',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,1,$hidefreetext);
	}
}
