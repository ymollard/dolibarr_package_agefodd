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
dol_include_once('/agefodd/class/agefodd_place.class.php');
dol_include_once('/agefodd/class/agefodd_contact.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
class pdf_fiche_remise_eval extends ModelePDFAgefodd {
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
	 * outputlangs Lang object for output language
	 * file Name of file to generate
	 * \return int 1=ok, 0=ko
	 */
	function write_file($agf, $outputlangs, $file, $socid, $courrier) {
		global $user, $langs, $conf, $mysoc;

		if (! is_object($outputlangs))
			$outputlangs = $langs;

		$outputlangs->load("companies");

		if (! is_object($agf)) {
			$id = $agf;
			$agf = new Agsession($this->db);
			$ret = $agf->fetch($id);
		}

		// Recuperation des informations des formateurs
		$agf_session_trainer = new Agefodd_session_formateur($this->db);
		$agf_session_trainer->fetch_formateur_per_session($id);

		$agf_place = new Agefodd_place($this->db);
		$agf_place->fetch($agf->placeid);

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

			// New page
			$pdf->AddPage();
			if (! empty($tplidx)) $pdf->useTemplate($tplidx);
			$pagenb ++;
			$this->_pagehead($pdf, $agf, 1, $outputlangs);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
			$pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3
			$pdf->SetTextColor($this->colorheaderText[0], $this->colorheaderText[1], $this->colorheaderText[2]);

			$default_font_size = pdf_getPDFFontSize($outputlangs);

			$posy = $this->marge_haute;
			$posx = $this->page_largeur - $this->marge_droite - 55;

			// Logo
			$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
			$width_logo = pdf_getWidthForLogo($logo);
			if ($this->emetteur->logo) {
				if (is_readable($logo)) {
					$height = pdf_getHeightForLogo($logo);
					$width_logo = pdf_getWidthForLogo($logo);
					if ($width_logo > 0) {
						$posx = $this->page_largeur - $this->marge_droite - $width_logo;
					} else {
						$posx = $this->page_largeur - $this->marge_droite - 55;
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
					if (is_readable($otherlogo) && $otherlogo != $logo) {
						$logo_height = pdf_getHeightForLogo($otherlogo);
						$width_otherlogo = pdf_getWidthForLogo($otherlogo);
						if ($width_otherlogo > 0 && $width_logo > 0) {
							$posx = $this->page_largeur - $this->marge_droite - $width_otherlogo - $width_logo - 10;
						} else {
							$posx = $this->marge_gauche + 100;
						}

						$pdf->Image($otherlogo, $posx, $posy, 0, $logo_height);
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

			$posY = $pdf->GetY() + 10;

			printRefIntForma($this->db, $outputlangs, $agf, $default_font_size - 1, $pdf, $posx, $posy, 'L');

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
			$posY = $posY + 3;

			/**
			 * *** Titre ****
			 */
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 15);
			$pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
			$pdf->SetXY($posX, $posY);
			$this->str = $outputlangs->transnoentities('AgfRemiseEvalPDF');
			$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'C');
			$pdf->Line($this->marge_gauche + 0.5, $posY + 10, $this->page_largeur - $this->marge_droite, $posY + 10);
			$posy = $pdf->GetY() + 10;

			$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);

			$posx = $this->marge_droite;
			$pdf->SetXY($posx, $posy);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $default_font_size);
			$this->str = $outputlangs->transnoentities('AgfNumTraining') . ' : ' . $agf->id;
			$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
			$posy = $pdf->GetY() + 1;

			$pdf->SetXY($posx, $posy);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $default_font_size);
			$this->str = $outputlangs->transnoentities('AgfTraining') . ' : ' . $agf->intitule_custo;
			$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
			$posy = $pdf->GetY() + 1;

			$pdf->SetXY($posx, $posy);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $default_font_size);
			$this->str = $outputlangs->transnoentities('AgfPDFFichePres24') . ': ';
			if ($agf->dated == $agf->datef)
				$this->str .= dol_print_date($agf->dated);
			else
				$this->str .= ' ' . dol_print_date($agf->dated) . ' au ' . dol_print_date($agf->datef);
			$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
			$posy = $pdf->GetY() + 1;

			$pdf->SetXY($posx, $posy);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $default_font_size);
			$this->str = $outputlangs->transnoentities('AgfPDFFichePres11') . ' ' . $agf->placecode . ' ' . $agf_place->adresse . ' ' . $agf_place->cp . ' ' . $agf_place->ville;
			$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
			$posy = $pdf->GetY() + 10;

			$pdf->SetXY($posx, $posy);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $default_font_size);
			$this->str = $outputlangs->transnoentities('AgfPDFRemiseEvalText');
			$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
			$posy = $pdf->GetY() + 10;

			$posy = $this->_tableau($pdf, $posy, 7, $outputlangs);

			// Lieu
			$posy += 5;
			$pdf->SetXY($this->marge_gauche + 1, $posy);
			$this->str = $mysoc->town . ", " . $outputlangs->transnoentities('AgfPDFFichePres8');
			$this->str2 = date("d/m/Y");
			$this->str2 = dol_print_date($agf->datef);
			$pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->str) . ' ' . $outputlangs->convToOutputCharset($this->str2), 0, 'L', 0);

			$posy = $pdf->GetY() + 5;
			// Formateur
			$trainer_arr = array ();
			foreach ( $agf_session_trainer->lines as $trainer ) {
				$trainer_arr[] = $trainer->firstname . " " . $trainer->lastname;
				// $pdf->MultiCell(80, 0, $outputlangs->convToOutputCharset($this->str), 0, 0, 'R', 0);
			}
			$pdf->SetXY($this->page_largeur - $this->marge_gauche - $this->marge_droite - 55, $posy);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
			$trainer_str = implode("\n", $trainer_arr);
			$pdf->MultiCell(80, 0, $outputlangs->transnoentities('AgfTrainerPDF') . ':' . "\n" . $trainer_str, 0, 'L', 0);

			// Incrustation image tampon
			$tampon_exitst = 1;
			if ($conf->global->AGF_INFO_TAMPON) {
				$dir = $conf->agefodd->dir_output . '/images/';
				$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
				if (file_exists($img_tampon)) {
					$posy = $pdf->GetY();
					$pdf->Image($img_tampon, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 55, $posy, 50);
					$tampon_exitst = 22;
				}
			}

			// Pied de page
			$this->_pagefoot($pdf, $agf, $outputlangs);
			// FPDI::AliasNbPages() is undefined method into Dolibarr 3.5
			if (method_exists($pdf, 'AliasNbPages')) {
				$pdf->AliasNbPages();
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


			return 1;
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

	/**
	 * Show table for lines
	 *
	 * @param object $pdf Object PDF
	 * @param string $tab_top Top position of table
	 * @param string $tab_height Height of table (rectangle)
	 * @param int $nexY Y (not used)
	 * @param Translate $outputlangs Langs object
	 * @return void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $outputlangs) {
		global $conf;
		// $tab_height=80;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Output Rect
		$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect prend une longueur en 3eme param et 4eme param

		// Prenom
		$pdf->SetXY($this->marge_droite, $tab_top + 1);
		$pdf->MultiCell(108, $tab_height, $outputlangs->convToOutputCharset(mb_strtoupper($outputlangs->transnoentities("Firstname"), 'UTF-8')), '', 'L');

		// Nom
		$pdf->line($this->marge_droite + 20, $tab_top, $this->marge_droite + 20, $tab_top + $tab_height);
		$pdf->SetXY($this->marge_droite + 22, $tab_top + 1);
		$pdf->MultiCell($this->marge_droite + 22, $tab_height, $outputlangs->convToOutputCharset(mb_strtoupper($outputlangs->transnoentities("Lastname"), 'UTF-8')), '', 'C');

		// Societe
		$pdf->line($this->marge_droite + 60, $tab_top, $this->marge_droite + 60, $tab_top + $tab_height);
		$pdf->SetXY($this->marge_droite + 62, $tab_top + 1);
		$pdf->MultiCell($this->marge_droite + 52, $tab_height, $outputlangs->convToOutputCharset(mb_strtoupper($outputlangs->transnoentities("Company"), 'UTF-8')), '', 'C');

		// Signature
		$pdf->line($this->marge_droite + 120, $tab_top, $this->marge_droite + 120, $tab_top + $tab_height);
		$pdf->SetXY($this->marge_droite + 122, $tab_top + 1);
		$pdf->MultiCell($this->marge_droite + 52, $tab_height, $outputlangs->convToOutputCharset(mb_strtoupper($outputlangs->transnoentities("AgfPDFRemiseEvalSing"), 'UTF-8')), '', 'C');

		$beforeY = $pdf->GetY();

		for($i = 1; $i <= 12; $i ++) {
			$this->printRect($pdf, $this->marge_gauche, $beforeY, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect prend une longueur en 3eme param et 4eme param
			$pdf->line($this->marge_droite + 20, $beforeY, $this->marge_droite + 20, $beforeY + $tab_height);
			$pdf->line($this->marge_droite + 60, $beforeY, $this->marge_droite + 60, $beforeY + $tab_height);
			$pdf->line($this->marge_droite + 120, $beforeY, $this->marge_droite + 120, $beforeY + $tab_height);
			$beforeY += $tab_height;
		}

		return $beforeY;
	}
}
