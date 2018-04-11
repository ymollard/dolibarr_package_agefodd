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
 * \file agefodd/core/modules/agefodd/pdf/pdf_fiche_presence_trainee.modules.php
 * \ingroup agefodd
 * \brief PDF for training attendees session sheet by trainee
 */
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_formation_catalogue.class.php');
require_once ('../class/agefodd_convention.class.php');
require_once ('../class/agefodd_place.class.php');
require_once ('../class/agefodd_session_formateur.class.php');
require_once ('../class/agefodd_session_calendrier.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
require_once ('../lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once ('../class/agefodd_session_stagiaire.class.php');
class pdf_fiche_presence_trainee extends ModelePDFAgefodd
{
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
		$this->name = "fiche_presence_trainee";
		$this->description = $langs->trans('AgfModPDFFichePres');

		// Dimension page pour format A4 en paysage
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width']; // use standard but reverse width and height to get Landscape format
		$this->page_hauteur = $formatarray['height']; // use standard but reverse width and height to get Landscape format
		$this->format = array(
				$this->page_largeur,
				$this->page_hauteur
		);
		$this->marge_gauche = 10;
		$this->marge_droite = 10;
		$this->marge_haute = 10;
		$this->marge_basse = 10;
		$this->unit = 'mm';
		$this->oriantation = 'P';
		$this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
		$this->milieu = $this->espaceH_dispo / 2;
		$this->espaceV_dispo = $this->page_hauteur - ($this->marge_haute + $this->marge_basse);
		$this->default_font_size = 12;

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
	 * Fonction generant le document sur le disque
	 *
	 * @param object $agf document a generer (ou id si ancienne methode)
	 * @param object $this->outputlangs for output language
	 * @param string $file file to generate
	 * @param int $socid
	 * @return int <0 if KO, Id of created object if OK
	 */
	function write_file($agf, $outputlangs, $file, $socid, $courrier) {
		global $user, $langs, $conf, $mysoc;

		$default_font_size = pdf_getPDFFontSize($this->outputlangs);

		$this->outputlangs = $outputlangs;

		if (! is_object($this->outputlangs))
			$this->outputlangs = $langs;

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
				$this->pdf = pdf_getInstance_agefodd($agf, $this, $this->format, $this->unit, $this->orientation);

				$this->ref_object=$agf;

				$this->pdf->setPrintHeader(true);
				$this->pdf->setPrintFooter(true);

				// Set calculation of header and footer high line
				// footer high
				$height = $this->getRealHeightLine('foot');
				$this->pdf->SetAutoPageBreak(1, $height);

				$this->pdf->setPrintHeader(true);
				$this->pdf->setPrintFooter(true);

				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs));

				$this->pdf->Open();
				$this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);

				$this->pdf->SetTitle($this->outputlangs->convToOutputCharset($this->outputlangs->transnoentities('AgfPDFFichePres1') . " " . $this->ref_object->ref));
				$this->pdf->SetSubject($this->outputlangs->transnoentities("Invoice"));
				$this->pdf->SetCreator("Dolibarr " . DOL_VERSION . ' (Agefodd module)');
				$this->pdf->SetAuthor($this->outputlangs->convToOutputCharset($user->fullname));
				$this->pdf->SetKeyWords($this->outputlangs->convToOutputCharset($this->ref_object->ref) . " " . $this->outputlangs->transnoentities("Document"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) {
					$this->pdf->SetCompression(false);
				}

				// Set calculation of header and footer high line
				// Header high
				$height = $this->getRealHeightLine('head');
				// Left, Top, Right
				$this->pdf->SetMargins($this->marge_gauche, $height + 10, $this->marge_droite, 1);

				$this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);

				// On recupere les infos societe
				$agf_soc = new Societe($this->db);
				$result = $agf_soc->fetch($socid);

				if ($result) {
					$agfsta = new Agefodd_session_stagiaire($this->db);
					$resql = $agfsta->fetch_stagiaire_per_session($this->ref_object->id);
					$nbsta = count($agfsta->lines);

					if ($nbsta > 0) {
						// $blocsta=0;
						foreach ( $agfsta->lines as $line ) {
							if ($line->status_in_session !=6){
								$this->line = $line;
								$this->_pagebody();
							}
						}
					} else {
						$this->pdf->AddPage();
						$pagenb ++;
						$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
						$this->pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3
						$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);

						$this->posY = $this->marge_haute;
						$this->posX = $this->marge_gauche;

						$this->pdf->MultiCell(100, 3, $this->outputlangs->transnoentities("No Trainee"), 0, 'R');
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
	 * \param object Object invoice
	 * \param showaddress 0=no, 1=yes
	 * \param outputlangs Object lang for output
	 * \param $line Trainee object
	 */
	function _pagebody() {
		global $user, $langs, $conf, $mysoc;
		
		// New page
		$this->pdf->AddPage();

		$formateurs = new Agefodd_session_formateur($this->db);
		$nbform = $formateurs->fetch_formateur_per_session($this->ref_object->id);

		$this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
		$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
		
		// Date
		$agf_date = new Agefodd_sesscalendar($this->db);
		$resql = $agf_date->fetch_all($this->ref_object->id);
		$nbpage=0;
		foreach ( $agf_date->lines as $linedate ) {
			$nbpage++;
			// Jour
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 8);
			if ($linedate->date_session) {
				$date = dol_print_date($linedate->date_session, 'daytextshort');
			} else {
				$date = '';
			}
			$this->str = $date;
			$this->pdf->SetXY($this->posX, $this->posY);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
			$this->pdf->MultiCell($this->larg_col1, $this->h_ligne, $this->outputlangs->convToOutputCharset($this->str), 0, "C", false, 1, '', '', true, 0, false, false, $this->h_ligne, 'M');

			// horaires
			if ($linedate->heured && $linedate->heuref) {
				$this->str = dol_print_date($linedate->heured, 'hour') . ' - ' . dol_print_date($linedate->heuref, 'hour');
			} else {
				$this->str = '';
			}
			$this->pdf->SetXY($this->posX + $this->larg_col1, $this->posY);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);
			$this->pdf->MultiCell($this->larg_col2, $this->h_ligne, $this->outputlangs->convToOutputCharset($this->str), 1, "C", false, 1, '', '', true, 0, false, false, $this->h_ligne, 'M');

			// Cadre pour signature
			$this->pdf->Rect($this->posX + $this->larg_col1 + $this->larg_col2, $this->posY, $this->larg_col3, $this->h_ligne);

			$this->posX_trainer = $first_trainer_posx;
			foreach ( $formateurs->lines as $trainer_line ) {
				$this->pdf->SetXY($this->posX_trainer, $this->posY);
				$this->pdf->MultiCell(0, $this->h_ligne, " ", 1, "C", false, 1, $this->posX_trainer, $this->posY);

				// $this->pdf->Rect($first_trainer_posx, $this->posY, $this->posX_trainer, $this->h_ligne);
				$this->posX_trainer += 30;
			}

			$this->posY = $this->pdf->GetY();
			if ($this->posY > $this->page_hauteur - 20) {
				$this->pdf->AddPage();
				$pagenb ++;
				$this->posY = $this->marge_haute;
			}

			if ($nbpage>14) {
				$nbpage=0;
				$this->pdf->AddPage();
			}
		}
		$this->posY = $this->pdf->GetY();

		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);

		// Cachet et signature
		$this->posY += 2;
		$this->posX -= 2;
		$this->pdf->SetXY($this->posX, $this->posY);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres20');
		$this->pdf->Cell(50, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);

		$this->pdf->SetXY($this->posX + 55, $this->posY);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres21');
		$this->pdf->Cell(20, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);

		$this->pdf->SetXY($this->posX + 92, $this->posY);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres22');
		$this->pdf->Cell(50, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);

		$this->posY = $this->pdf->GetY();

		// Incrustation image tampon
		if ($conf->global->AGF_INFO_TAMPON) {
			$dir = $conf->agefodd->dir_output . '/images/';
			$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
			if (file_exists($img_tampon))
				$this->pdf->Image($img_tampon, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 50, $this->posY, 50);
		}

		// Pied de page
		//$this->_pagefoot();
		// FPDI::AliasNbPages() is undefined method into Dolibarr 3.5
		if (method_exists($this->pdf, 'AliasNbPages')) {
			$this->pdf->AliasNbPages();
		}
	}

	/**
	 * \brief Show header of page
	 * \param object Object invoice
	 * \param showaddress 0=no, 1=yes
	 * \param outputlangs Object lang for output
	 */
	function _pagehead() {
		global $conf, $langs, $mysoc;

		$this->outputlangs->load("main");

		$this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);

		// spécifique multicompany
		if (!empty($conf->multicompany->enabled)) {
		    dol_include_once('/multicompany/class/dao_multicompany.class.php');
		    $dao = new DaoMulticompany($this->db);
		    $dao->getEntities();
		}
		
		// Fill header with background color
		$this->pdf->SetFillColor($this->colorheaderBg[0], $this->colorheaderBg[1], $this->colorheaderBg[2]);
		$this->pdf->MultiCell($this->page_largeur, 40, '', 0, 'L', true, 1, 0, 0);

		pdf_pagehead($this->pdf, $this->outputlangs, $this->pdf->page_hauteur);

		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->pdf->SetTextColor($this->colorheaderText[0], $this->colorheaderText[1], $this->colorheaderText[2]);

		$this->posY = $this->marge_haute;
		$this->posX = $this->page_largeur - $this->marge_droite - 55;

		// Logo
		$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
		if ($this->emetteur->logo) {
			if (is_readable($logo)) {
				$height = pdf_getHeightForLogo($logo);
				$width_logo = pdf_getWidthForLogo($logo);
				if ($width_logo > 0) {
					$this->posX = $this->page_largeur - $this->marge_droite - $width_logo;
				} else {
					$this->posX = $this->page_largeur - $this->marge_droite - 55;
				}
				$this->pdf->Image($logo, $this->posX, $this->posY, 0, $height);
			} else {
				$this->pdf->SetTextColor(200, 0, 0);
				$this->pdf->SetFont('', 'B', $this->default_font_size - 2);
				$this->pdf->MultiCell(100, 3, $this->outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$this->pdf->MultiCell(100, 3, $this->outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$text = $this->emetteur->name;
			$this->pdf->MultiCell(100, 4, $this->outputlangs->convToOutputCharset($text), 0, 'L');
		}
		// Other Logo
		if (!empty($conf->multicompany->enabled) && ! empty($conf->global->AGF_MULTICOMPANY_MULTILOGO)) {
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
						$this->posX = $this->page_largeur - $this->marge_droite - $width_otherlogo - $width_logo - 10;
					} else {
						$this->posX = $this->marge_gauche + 100;
					}

					$this->pdf->Image($otherlogo, $this->posX, $this->posY, 0, $logo_height);
				}
			}
		}

		$this->posY = $this->marge_haute;
		$this->posX = $this->marge_gauche;

		$hautcadre = 30;
		$this->pdf->SetXY($this->posX, $this->posY);
		$this->pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);

		// Show sender name
		$this->pdf->SetXY($this->posX, $this->posY);
		$this->pdf->SetFont('', 'B', $this->default_font_size - 2);
		$this->pdf->MultiCell(80, 4, $this->outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
		$this->posY = $this->pdf->GetY();

		// Show sender information
		$this->pdf->SetXY($this->posX, $this->posY);
		$this->pdf->SetFont('', '', $this->default_font_size - 3);
		$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
		$this->posY = $this->pdf->GetY();
		$this->pdf->SetXY($this->posX, $this->posY);
		$this->pdf->SetFont('', '', $this->default_font_size - 3);
		$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->zip . ' ' . $this->emetteur->town), 0, 'L');
		$this->posY = $this->pdf->GetY();
		$this->pdf->SetXY($this->posX, $this->posY);
		$this->pdf->SetFont('', '', $this->default_font_size - 3);
		$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
		$this->posY = $this->pdf->GetY();
		$this->pdf->SetXY($this->posX, $this->posY);
		$this->pdf->SetFont('', '', $this->default_font_size - 3);
		$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');
		$this->posY = $this->pdf->GetY();

		printRefIntForma($this->db, $this->outputlangs, $this->ref_object, $this->default_font_size - 3, $this->pdf, $this->posX, $this->posY, 'L');

		// Affichage du logo commanditaire (optionnel)
		if ($conf->global->AGF_USE_LOGO_CLIENT) {
			$staticsoc = new Societe($this->db);
			$staticsoc->fetch($this->ref_object->socid);
			$dir = $conf->societe->multidir_output[$staticsoc->entity] . '/' . $staticsoc->id . '/logos/';
			if (! empty($staticsoc->logo)) {
				$logo_client = $dir . $staticsoc->logo;
				if (file_exists($logo_client) && is_readable($logo_client))
					$this->pdf->Image($logo_client, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 30, $this->marge_haute, 40);
			}
		}

		$this->posY = $this->pdf->GetY() + 10;
		if ($conf->global->AGF_PRINT_INTERNAL_REF_ON_PDF) $this->posY -= 4;

		$this->pdf->Line($this->marge_gauche + 0.5, $this->posY, $this->page_largeur - $this->marge_droite, $this->posY);

		// Mise en page de la baseline
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 18);
		$this->str = $this->outputlangs->transnoentities($mysoc->url);
		$this->width = $this->pdf->GetStringWidth($this->str);

		// alignement du bord droit du container avec le haut de la page
		$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->width;
		$baseline_angle = (M_PI / 2); // angle droit
		$baseline_x = 8;
		$baseline_y = $this->espaceV_dispo - $baseline_ecart + 30;
		$baseline_width = $this->width;
		$this->pdf->SetXY($baseline_x, $baseline_y);

		/*
		 * Corps de page
		 */
		$this->posX = $this->marge_gauche;
		$this->posY = $this->posY + 5;

		// Titre
		$this->pdf->SetXY($this->posX, $this->posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 18);
		$this->pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres1');
		$this->pdf->Cell(0, 6, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "C", 0);
		$this->posY += 6 + 4;

		// Intro
		$this->pdf->SetXY($this->posX, $this->posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres2') . ' « ' . $mysoc->name . ' »,' . $this->outputlangs->transnoentities('AgfPDFFichePres3') . ' ';
		$this->str .= $mysoc->address . ' ';
		$this->str .= $mysoc->zip . ' ' . $mysoc->town;
		$this->str .= $this->outputlangs->transnoentities('AgfPDFFichePres4') . ' ' . $conf->global->AGF_ORGANISME_REPRESENTANT . ",\n";
		$this->str .= $this->outputlangs->transnoentities('AgfPDFFichePres5');
		$this->pdf->MultiCell(0, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 'C');
		$hauteur = dol_nboflines_bis($this->str, 50) * 2;
		$this->posY += $hauteur + 2;

		/**
		 * *** Bloc formation ****
		 */
		$this->pdf->SetXY($this->posX, $this->posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'BI', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres23');
		$this->pdf->Cell(0, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);
		$this->posY += 4;


		// $this->pdf->Line($this->posX, $this->posY, $this->page_largeur - $this->marge_droite, $this->posY);
		$cadre_tableau = array(
				$this->posX,
				$this->posY
		);

		$this->posX += 2;
		$this->posY += 2;
		$this->posYintitule = $this->posY;

		$this->larg_col1 = 20;
		$this->larg_col2 = 80;
		$this->larg_col3 = 27;
		$this->larg_col4 = 82;
		$this->haut_col2 = 0;
		$this->haut_col4 = 0;

		// Intitulé
		$this->pdf->SetXY($this->posX, $this->posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres6');
		$this->pdf->Cell($this->larg_col1, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);

		$this->pdf->SetXY($this->posX + $this->larg_col1, $this->posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 9);

		if (empty($this->ref_object->intitule_custo)) {
			$this->str = '« ' . $this->ref_object->formintitule . ' »';
		} else {
			$this->str = '« ' . $this->ref_object->intitule_custo . ' »';
		}
		$this->pdf->MultiCell($this->larg_col2, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 'L');

		$this->posY = $this->pdf->GetY() + 2;
		$this->haut_col2 += $hauteur;

		// Période
		$this->pdf->SetXY($this->posX, $this->posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres7');
		$this->pdf->Cell($this->larg_col1, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);

		if ($this->ref_object->dated == $this->ref_object->datef) {
			$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres8') . " " . dol_print_date($this->ref_object->datef, 'daytext');
		} else {
			$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres9') . " " . dol_print_date($this->ref_object->dated) . ' ' . $this->outputlangs->transnoentities('AgfPDFFichePres10') . ' ' . dol_print_date($this->ref_object->datef, 'daytext');
		}
		$this->pdf->SetXY($this->posX + $this->larg_col1, $this->posY);
		$this->pdf->MultiCell($this->larg_col2, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 'L');
		$hauteur = dol_nboflines_bis($this->str, 50) * 4;
		$this->haut_col2 += $hauteur + 2;

		//Session
		$this->posY = $this->pdf->GetY() + 2;
		$this->pdf->SetXY($this->posX, $this->posY);
		$this->str = $this->outputlangs->transnoentities('Session')." :";
		$this->pdf->MultiCell($this->larg_col2, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 'L');
		$this->pdf->SetXY($this->posX + $this->larg_col1, $this->posY);
		$this->pdf->MultiCell($this->larg_col2, 4, $this->outputlangs->convToOutputCharset($this->ref_object->id), 0, 'L');
		$this->haut_col2 += $hauteur + 1;
		// Lieu
		$this->pdf->SetXY($this->posX + $this->larg_col1 + $this->larg_col2, $this->posYintitule);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres11');
		$this->pdf->Cell($this->larg_col3, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);

		$agf_place = new Agefodd_place($this->db);
		$resql = $agf_place->fetch($this->ref_object->placeid);

		$this->pdf->SetXY($this->posX + $this->larg_col1 + $this->larg_col2 + $this->larg_col3, $this->posYintitule);
		$this->str = $agf_place->ref_interne . "\n" . $agf_place->adresse . "\n" . $agf_place->cp . " " . $agf_place->ville;
		$this->pdf->MultiCell($this->larg_col4, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 'L');
		$hauteur = dol_nboflines_bis($this->str, 50) * 4;
		$this->posY += $hauteur + 5;
		$this->haut_col4 += $hauteur + 7;

		// Cadre
		($this->haut_col4 > $this->haut_col2) ? $haut_table = $this->haut_col4 : $haut_table = $this->haut_col2;

		$this->pdf->Rect($cadre_tableau[0], $cadre_tableau[1], $this->espaceH_dispo, $haut_table);

		$this->posY = $this->pdf->GetY() + 10;

		/**
		 * *** Bloc stagiaire ****
		 * et participants
		 */

		$formateurs = new Agefodd_session_formateur($this->db);
		$nbform = $formateurs->fetch_formateur_per_session($this->ref_object->id);

		$this->pdf->SetXY($this->posX - 2, $this->posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'BI', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres26');
		$this->pdf->Cell(0, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);
		$this->posY += 4;

		$this->larg_col1 = 30;
		$this->larg_col2 = 30;
		$this->larg_col3 = 60;
		$this->larg_col4 = 112;
		$this->haut_col2 = 0;
		$this->haut_col4 = 0;
		$this->h_ligne = 7;
		$this->haut_cadre = 0;

		// Entête

		// Date
		$this->pdf->SetXY($this->posX-2, $this->posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres24');
		$this->pdf->Cell($this->larg_col1+2, $this->h_ligne + 8, $this->outputlangs->convToOutputCharset($this->str), TLR, 2, "C", 0);
		// Horaire
		$this->pdf->SetXY($this->posX + $this->larg_col1, $this->posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres25');
		$this->pdf->Cell($this->larg_col2, $this->h_ligne + 8, $this->outputlangs->convToOutputCharset($this->str), TLR, 2, "C", 0);

		// Trainee
		$this->posY_trainee = $this->posY;
		$this->pdf->SetXY($this->posX + $this->larg_col1 + $this->larg_col2, $this->posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 7);
		$this->str = $this->line->nom . ' ' . $this->line->prenom . ' - ' . dol_trunc($this->line->socname, 27);
 		
		if (! empty($this->line->poste)) {
		    $this->str .= "\n".' (' . $this->line->poste . ')';
		}
		if (is_object($dao) && $conf->global->AGF_ADD_ENTITYNAME_FICHEPRES) {
		    $c = new Societe($this->db);
		    $c->fetch($this->line->socid);
		    
		    $entityName = '';
		    if (count($dao->entities)>0){
		        foreach ($dao->entities as $e){
		            if ($e->id == $c->entity){
		                $entityName = $e->label;
		                $this->str .= "\n". $this->outputlangs->trans('Entity').' : '. $e->label;
		                break;
		            }
		        }
		    }
		}
		
		$this->pdf->MultiCell($this->larg_col3, $this->h_ligne, $this->outputlangs->convToOutputCharset($this->str), 'T', 'C', false, 1, $this->posX + $this->larg_col1 + $this->larg_col2, $this->posY, true, 1, false, true, $this->h_ligne, 'T', true);

		$this->posY = $this->pdf->GetY() - 1;

		// Signature
		$this->pdf->SetXY($this->posX + $this->larg_col1 + $this->larg_col2, $this->posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres18');
		$this->pdf->Cell($this->larg_col3, 5, $this->outputlangs->convToOutputCharset($this->str), R, 2, "C", 0);
		$this->pdf->SetXY($this->posX + $this->larg_col1 + $this->larg_col2, $this->posY + 3);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'I', 5);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres19');
		$this->pdf->Cell($this->larg_col3, 5, $this->outputlangs->convToOutputCharset($this->str), R, 2, "C", 0);
		$this->posY = $this->pdf->GetY();

		// Trainer
		$this->pdf->SetXY($this->posX + $this->larg_col1 + $this->larg_col2 + $this->larg_col3, $this->posY_trainee);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres12'); // ."\n".$this->outputlangs->transnoentities('AgfPDFFichePres13');
		$this->pdf->MultiCell(0, 2, $this->outputlangs->convToOutputCharset($this->str), TLR, "C");
		$this->posY_trainer = $this->pdf->GetY();
		$this->pdf->SetXY($this->posX + $this->larg_col1 + $this->larg_col2 + $this->larg_col3, $this->posY_trainer);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'I', 5);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres13'); // ."\n".$this->outputlangs->transnoentities('AgfPDFFichePres13');
		$this->pdf->MultiCell(0, 2, $this->outputlangs->convToOutputCharset($this->str), BLR, "C");

		$this->posY_trainer = $this->pdf->GetY();
		$this->posX_trainer = $this->posX + $this->larg_col1 + $this->larg_col2 + $this->larg_col3;
		$first_trainer_posx = $this->posX_trainer;
		foreach ( $formateurs->lines as $trainer_line ) {
			$this->pdf->SetXY($this->posX_trainer, $this->posY_trainer);
			$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 7);
			$this->str = strtoupper($trainer_line->lastname) . "\n" . ucfirst($trainer_line->firstname);
			$this->pdf->MultiCell(0, 3, $this->outputlangs->convToOutputCharset($this->str), LR, "L", false, 1, $this->posX_trainer, $this->posY_trainer);
			// $w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y=''

			$this->posY = $this->pdf->GetY();

			$this->posY = $this->pdf->GetY();
			$this->posX_trainer += 30;
		}

		// ligne
		$this->h_ligne = 9;
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
	}

	/**
	 * \brief Show footer of page
	 * \param pdf PDF factory
	 * \param object Object invoice
	 * \param outputlang Object lang for output
	 * \remarks Need this->emetteur object
	 */
	function _pagefoot() {
		$this->pdf->SetTextColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		$this->pdf->SetDrawColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		$this->pdf->SetAutoPageBreak(0);
		return pdf_agfpagefoot($this->pdf, $this->outputlangs, '', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $this->ref_object, 1, $hidefreetext);
	}
}