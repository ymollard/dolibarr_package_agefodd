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
 * \file agefodd/core/modules/agefodd/pdf/pdf_fiche_presence_societe.modules.php
 * \ingroup agefodd
 * \brief PDF for attendees sheet
 */
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_convention.class.php');
dol_include_once('/agefodd/class/agefodd_place.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
dol_include_once('/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/core/lib/company.lib.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');

// TODO faire étendre pdf_fiche_presence
class pdf_fiche_presence_societe extends ModelePDFAgefodd {
	var $emetteur; // Objet societe qui emet

	// Definition des couleurs utilisées de façon globales dans le document (charte)
	protected $colorfooter;
	protected $colortext;
	protected $colorhead;
	protected $colorheaderBg;
	protected $colorheaderText;
	protected $colorLine;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	function __construct($db)
	{
		global $conf, $langs, $mysoc;

		$this->db = $db;
		$this->name = "fiche_presence";
		$this->description = $langs->trans('AgfModPDFFichePres');

		// Dimension page pour format A4 en paysage
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width']; // use standard but reverse width and height to get Landscape format
		$this->page_hauteur = $formatarray['height']; // use standard but reverse width and height to get Landscape format
		$this->format = array (
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

		$this->header_vertical_margin = 3;

		$this->formation_widthcol1 = 20;
		$this->formation_widthcol2 = 80;
		$this->formation_widthcol3 = 27;
		$this->formation_widthcol4 = 60;

		$this->trainer_widthcol1 = 44;
		$this->trainer_widthcol2 = 140;
		$this->trainer_widthtimeslot = 24;

		$this->trainee_widthcol1 = 40;
		$this->trainee_widthcol2 = 40;
		if (empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
			$this->trainee_widthtimeslot = 18;
		} else {
			$this->trainee_widthtimeslot = 24.7;
		}

		$this->nbtimeslots = 6;
	}

	/**
	 * @param Object $agf  Session
	 * @param Translate $outputlangs $outputlangs
	 * @param string $file file
	 * @param int $socid socid
	 * @return int
	 */
	function write_file($agf, $outputlangs, $file, $socid)
	{
		global $user, $langs, $conf, $hookmanager;

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
			$this->pdf = pdf_getInstance($this->format, $this->unit, $this->orientation);
			$this->pdf->ref_object = $agf;

			if (class_exists('TCPDF')) {
				$this->pdf->setPrintHeader(false);
				$this->pdf->setPrintFooter(false);
			}

			$this->pdf->Open();

			$this->pdf->SetTitle($this->outputlangs->convToOutputCharset($this->outputlangs->transnoentities('AgfPDFFichePres1') . " " . $agf->ref));
			$this->pdf->SetSubject($this->outputlangs->transnoentities("Invoice"));
			$this->pdf->SetCreator("Dolibarr " . DOL_VERSION . ' (Agefodd module)');
			$this->pdf->SetAuthor($this->outputlangs->convToOutputCharset($user->fullname));
			$this->pdf->SetKeyWords($this->outputlangs->convToOutputCharset($agf->ref) . " " . $this->outputlangs->transnoentities("Document"));
			if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION)
				$this->pdf->SetCompression(false);

			$this->pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
			$this->pdf->SetAutoPageBreak(1, 0);

			// On recupere les infos societe
			$agf_soc = new Societe($this->db);
			$result = $agf_soc->fetch($socid);

			if ($result) {
				$this->_pagebody($this->pdf, $agf, $this->outputlangs);
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
			$parameters=array('file'=>$file,'object'=>$agf,'outputlangs'=>$this->outputlangs);
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
	 * Show header of page
	 * @param TCPDF $pdf Object PDF
	 * @param object $agf Object invoice
	 * @param Translate $outputlangs Object lang for output
	 * @return void
	 */
	function _pagebody(&$pdf, $agf, $outputlangs)
	{
		global $conf, $mysoc;

		$nbsta_index=1;

		// Set path to the background PDF File
		if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->AGF_ADD_PDF_BACKGROUND_P))
		{
			$pagecount = $this->pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P);
			$tplidx = $this->pdf->importPage(1);
		}

		$height_for_footer = 35;
		if (!empty($conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER)) $height_for_footer = $conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER;

		if (!empty($conf->multicompany->enabled)) {
			dol_include_once('/multicompany/class/dao_multicompany.class.php');
			$dao = new DaoMulticompany($this->db);
			$dao->getEntities();
		}

		$session_hours=array();
		$tmp_array=array();
		$agf_date = new Agefodd_sesscalendar($this->db);
		$resql = $agf_date->fetch_all($agf->id);
		if (! $resql) {
			setEventMessage($agf_date->error, 'errors');
		}
		if (is_array($agf_date->lines) && count($agf_date->lines)>$this->nbtimeslots) {
			for($i = 0; $i < count($agf_date->lines); $i ++) {
				$tmp_array[]=$agf_date->lines[$i];
				if(count($tmp_array)>=$this->nbtimeslots || $i==count($agf_date->lines)-1) {
					$session_hours[]=$tmp_array;
					$tmp_array=array();
				}
			}
		} else {
			$session_hours[]=$agf_date->lines;
		}

		//On récupère l'id des sociétés des participants
		$agfstaglobal = new Agefodd_session_stagiaire($this->db);
		$resql = $agfstaglobal->fetch_stagiaire_per_session($agf->id);
		$socstagiaires = array();

		$TStagiaireStatusToExclude = array();

		if (! empty($conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES)) {
			$TStagiaireStatusToExclude = explode(',', $conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES);
		}

		foreach ($agfstaglobal->lines as $line) {
			if (! empty($TStagiaireStatusToExclude) && in_array($line->status_in_session, $TStagiaireStatusToExclude)) {
				continue;
			}

			if (! isset($socstagiaires[$line->socid])) {
				$socstagiaires[$line->socid] = new stdClass();
				$socstagiaires[$line->socid]->lines = array();
			}

			$socstagiaires[$line->socid]->lines[] = $line;
		}

		//Pour chaque société ayant un participant à afficher, on crée une série de feuilles de présence
		foreach($socstagiaires as $socstagiaires_id => $agfsta) {

			foreach ($session_hours as $key => $lines_array) {
				// New page
				$this->pdf->AddPage();
				if (!empty($tplidx)) $this->pdf->useTemplate($tplidx);
				$this->_pagehead($this->pdf, $this->outputlangs);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
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
						} else {
							$posX = $this->page_largeur - $this->marge_droite - 55;
						}
						$this->pdf->Image($logo, $posX, $posY, 0, $height);
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
				if ($conf->multicompany->enabled && !empty($conf->global->AGF_MULTICOMPANY_MULTILOGO)) {
					$sql = 'SELECT value FROM ' . MAIN_DB_PREFIX . 'const WHERE name =\'MAIN_INFO_SOCIETE_LOGO\' AND entity=1';
					$resql = $this->db->query($sql);
					if (!$resql) {
						setEventMessage($this->db->lasterror, 'errors');
					} else {
						$obj = $this->db->fetch_object($resql);
						$image_name = $obj->value;
					}
					if (!empty($image_name)) {
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

				$posY = $this->marge_haute;
				$posX = $this->marge_gauche;

				$hautcadre = 30;
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);

				// Show sender name
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont('', 'B', $this->default_font_size - 2);
				$this->pdf->MultiCell(80, 4, $this->outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
				$posY = $this->pdf->GetY();

				// Show sender information
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont('', '', $this->default_font_size - 3);
				$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
				$posY = $this->pdf->GetY();
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont('', '', $this->default_font_size - 3);
				$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->zip . ' ' . $this->emetteur->town), 0, 'L');
				$posY = $this->pdf->GetY();
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont('', '', $this->default_font_size - 3);
				$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
				$posY = $this->pdf->GetY();
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont('', '', $this->default_font_size - 3);
				$this->pdf->MultiCell(70, 4, $this->outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');
				$posY = $this->pdf->GetY();

				printRefIntForma($this->db, $this->outputlangs, $agf, $this->default_font_size - 3, $this->pdf, $posX, $posY, 'L', true);

				// Affichage du logo commanditaire (optionnel)
				if ($conf->global->AGF_USE_LOGO_CLIENT) {
					$staticsoc = new Societe($this->db);
					$staticsoc->fetch($agf->socid);
					$dir = $conf->societe->multidir_output[$staticsoc->entity] . '/' . $staticsoc->id . '/logos/';
					if (!empty($staticsoc->logo)) {
						$logo_client = $dir . $staticsoc->logo;
						if (file_exists($logo_client) && is_readable($logo_client))
							$this->pdf->Image($logo_client, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 30, $this->marge_haute, 40);
					}
				}

				$posY = $this->pdf->GetY() + 10;
				if ($conf->global->AGF_PRINT_INTERNAL_REF_ON_PDF) $posY -= 4;

				$this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
				$this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);

				$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);

				// Mise en page de la baseline
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 18);
				$str = $this->outputlangs->transnoentities($mysoc->url);
				$width = $this->pdf->GetStringWidth($str);

				// alignement du bord droit du container avec le haut de la page
				$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $width;
				$baseline_x = 8;
				$baseline_y = $this->espaceV_dispo - $baseline_ecart + 30;
				$this->pdf->SetXY($baseline_x, $baseline_y);

				/*
				 * Corps de page
				 */
				$posX = $this->marge_gauche;
				$posY = $posY + $this->header_vertical_margin;

				// Titre
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 18);
				$this->pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres1');
				$this->pdf->Cell(0, 6, $this->outputlangs->convToOutputCharset($str), 0, 2, "C", 0);
				$posY += 6 + 4;

				// Intro
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 8);
				$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres2') . ' « ' . $mysoc->name . ' »,' . $this->outputlangs->transnoentities('AgfPDFFichePres3') . ' ';
				$str .= str_replace(array('<br>', '<br />', "\n", "\r"), array(' ', ' ', ' ', ' '), $mysoc->address) . ' ';
				$str .= $mysoc->zip . ' ' . $mysoc->town;
				$str .= $this->outputlangs->transnoentities('AgfPDFFichePres4') . ' ' . $conf->global->AGF_ORGANISME_REPRESENTANT . ",\n";
				$str .= $this->outputlangs->transnoentities('AgfPDFFichePres5');
				$this->pdf->MultiCell(0, 0, $this->outputlangs->convToOutputCharset($str), 0, 'C');
				$posY = $this->pdf->GetY() + 1;

				/**
				 * *** Bloc formation ****
				 */
				 // TODO à remplacer par la méthode printSessionSummary
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'BI', 9);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres23');
				$this->pdf->Cell(0, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
				$posY += 5;

				$cadre_tableau = array(
					$posX,
					$posY
				);

				$posX += 2;
				$posY += 2;
				$posYintitule = $posY;

				$haut_col2 = 0;
				$haut_col4 = 0;

				// Intitulé
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 9);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres6');
				$this->pdf->Cell($this->formation_widthcol1, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

				$this->pdf->SetXY($posX + $this->formation_widthcol1, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);

				if (empty($this->pdf->ref_object->intitule_custo)) {
					$str = '« ' . $this->pdf->ref_object->formintitule . ' »';
				} else {
					$str = '« ' . $this->pdf->ref_object->intitule_custo . ' »';
				}
				$this->pdf->MultiCell($this->formation_widthcol2, 4, $this->outputlangs->convToOutputCharset($str), 0, 'L');

				$hauteur = dol_nboflines_bis($str, 50) * 4;
				$haut_col2 += $hauteur + 2;

				// Période
				$posY = $this->pdf->GetY() + 2;
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 9);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres7');
				$this->pdf->Cell($this->formation_widthcol1, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

				$str = $this->pdf->ref_object->libSessionDate('daytext');

				$str .= ' (' . $this->pdf->ref_object->duree_session . ' h)';

				$this->pdf->SetXY($posX + $this->formation_widthcol1, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
				$this->pdf->MultiCell($this->formation_widthcol2, 4, $this->outputlangs->convToOutputCharset($str), 0, 'L');
				$hauteur = dol_nboflines_bis($str, 50) * 4;
				$haut_col2 += $hauteur + 2;

				//Session
				$posY = $this->pdf->GetY() + 2;
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 9);
				$str = $this->outputlangs->transnoentities('Session')." :";
				$this->pdf->Cell($this->formation_widthcol1, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

				$this->pdf->SetXY($posX + $this->formation_widthcol1, $posY);
				$str = $this->pdf->ref_object->id . '#' . $this->pdf->ref_object->ref;
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
				$this->pdf->MultiCell($this->formation_widthcol2, 4, $this->outputlangs->convToOutputCharset($str), 0, 'L');
				$hauteur = dol_nboflines_bis($str, 50) * 4;
				$haut_col2 += $hauteur + 2;

				// Lieu
				$this->pdf->SetXY($posX + $this->formation_widthcol1 + $this->formation_widthcol2, $posYintitule);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres11');
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 9);
				$this->pdf->Cell($this->formation_widthcol3, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

				$agf_place = new Agefodd_place($this->db);
				$resql = $agf_place->fetch($this->pdf->ref_object->placeid);

				$this->pdf->SetXY($posX + $this->formation_widthcol1 + $this->formation_widthcol2 + $this->formation_widthcol3 +2 , $posYintitule);
				$str = $agf_place->ref_interne . "\n" . $agf_place->adresse . "\n" . $agf_place->cp . " " . $agf_place->ville;
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
				$this->pdf->MultiCell($this->formation_widthcol4, 4, $this->outputlangs->convToOutputCharset($str), 0, 'L');
				$hauteur = dol_nboflines_bis($str, 50) * 4;
				$haut_col4 += $hauteur + 2;

				// Cadre
				($haut_col4 > $haut_col2) ? $haut_table = $haut_col4 : $haut_table = $haut_col2;
				$posY = $posYintitule + $haut_table + 4;
				$this->pdf->Rect($cadre_tableau[0], $cadre_tableau[1], $this->espaceH_dispo, $haut_table + $this->summaryPaddingBottom);

				/**
				 * *** Bloc formateur ****
				 */

				$this->pdf->SetXY($posX - 2, $posY - 2);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'BI', 9);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres12');
				$this->pdf->Cell(0, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
				$posY += 2;

				$larg_col1 = $this->trainer_widthcol1;
				$h_ligne = 7;

				// Entête
				// Cadre
				$this->pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $h_ligne + 8);
				// Nom
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres16');
				$this->pdf->Cell($larg_col1, $h_ligne + 8, $this->outputlangs->convToOutputCharset($str), 'R', 2, "C", 0);
				// Signature
				$this->pdf->SetXY($posX + $larg_col1, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres18');
				$this->pdf->Cell(0, 5, $this->outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);

				$this->pdf->SetXY($posX + $larg_col1, $posY + 3);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'I', 7);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres13');
				$this->pdf->Cell(0, 5, $this->outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);
				$posY += $h_ligne;

				// Date

				$largeur_date = $this->trainer_widthtimeslot;
				$last_day = '';
				$same_day = 0;
				for ($y = 0; $y < $this->nbtimeslots; $y++) {
					// Jour
					$this->pdf->SetXY($posX + $larg_col1 + (20 * $y), $posY);
					$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 8);
					if ($lines_array[$y]->date_session) {
						$date = dol_print_date($lines_array[$y]->date_session, 'daytextshort');
					} else {
						$date = '';
					}
					$str = $date;
					if ($last_day == $lines_array[$y]->date_session) {
						$same_day += 1;
						$this->pdf->SetFillColor(255, 255, 255);
					} else {
						$same_day = 0;
					}
					$this->pdf->SetXY($posX + $larg_col1 + ($largeur_date * $y) - ($largeur_date * ($same_day)), $posY);
					$this->pdf->Cell($largeur_date * ($same_day + 1), 4, $this->outputlangs->convToOutputCharset($str), 1, 2, "C", $same_day);

					// horaires
					$this->pdf->SetXY($posX + $larg_col1 + ($largeur_date * $y), $posY + 4);
					if ($lines_array[$y]->heured && $lines_array[$y]->heuref) {
						$str = dol_print_date($lines_array[$y]->heured, 'hour') . ' - ' . dol_print_date($lines_array[$y]->heuref, 'hour');
					} else {
						$str = '';
					}
					$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);
					$this->pdf->Cell($largeur_date, 4, $this->outputlangs->convToOutputCharset($str), 1, 2, "C", 0);

					$last_day = $lines_array[$y]->date_session;
				}
				$posY = $this->pdf->GetY();

				$formateurs = new Agefodd_session_formateur($this->db);
				$nbform = $formateurs->fetch_formateur_per_session($agf->id);
				if ($nbform > 0) {
					foreach ($formateurs->lines as $trainerlines) {

						// Cadre
						$this->pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $h_ligne);

						// Nom
						$this->pdf->SetXY($posX - 2, $posY);
						$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);
						$str = strtoupper($trainerlines->lastname) . ' ' . ucfirst($trainerlines->firstname);
						$this->pdf->MultiCell($larg_col1 + 2, $h_ligne, $this->outputlangs->convToOutputCharset($str), 1, "L", false, 1, '', '', true, 0, false, false, $h_ligne, 'M');

						for ($i = 0; $i < $this->nbtimeslots - 1; $i++) {
							$this->pdf->Rect($posX + $larg_col1 + $largeur_date * $i, $posY, $largeur_date, $h_ligne);
						}

						$posY = $this->pdf->GetY();
						if ($posY > $this->page_hauteur - $height_for_footer) {
							$this->pdf->AddPage();
							if (!empty($tplidx)) $this->pdf->useTemplate($tplidx);
							$posY = $this->marge_haute;
						}
					}
				}

				$posY = $this->pdf->GetY() + 2;

				/**
				 * *** Bloc stagiaire ****
				 */
				$this->pdf->SetXY($posX - 2, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'BI', 9);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres15');
				$this->pdf->Cell(0, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
				$posY = $this->pdf->GetY();

				$larg_col1 = $this->trainee_widthcol1;
				$larg_col2 = $this->trainee_widthcol2;
				$h_ligne = 7;

				// Entête
				// Cadre
				$this->pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $h_ligne + 8);
				// Nom
				$this->pdf->SetXY($posX, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres16');
				$this->pdf->Cell($larg_col1, $h_ligne + 8, $this->outputlangs->convToOutputCharset($str), 'R', 2, "C", 0);
				// Société
				if (empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
					$this->pdf->SetXY($posX + $larg_col1, $posY);
					$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
					$str = $this->outputlangs->transnoentities('AgfPDFFichePres17');
					$this->pdf->Cell($larg_col2, $h_ligne + 8, $this->outputlangs->convToOutputCharset($str), 0, 2, "C", 0);
				} else {
					$larg_col2 = 0;
				}
				// Signature
				$this->pdf->SetXY($posX + $larg_col1 + $larg_col2, $posY);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres18');
				$this->pdf->Cell(0, 5, $this->outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);

				$this->pdf->SetXY($posX + $larg_col1 + $larg_col2, $posY + 3);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'I', 7);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres19');
				$this->pdf->Cell(0, 5, $this->outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);
				$posY += $h_ligne;

				// Date
				$agf_date = new Agefodd_sesscalendar($this->db);
				$resql = $agf_date->fetch_all($agf->id);
				if (!$resql) {
					setEventMessages($agf_date->error, 'errors');
				}
				$largeur_date = $this->trainee_widthtimeslot;
				$last_day = '';
				for ($y = 0; $y < $this->nbtimeslots; $y++) {
					// Jour
					$this->pdf->SetXY($posX + $larg_col1 + $larg_col2 + (20 * $y), $posY);
					$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 8);
					if ($lines_array[$y]->date_session) {
						$date = dol_print_date($lines_array[$y]->date_session, 'daytextshort');
					} else {
						$date = '';
					}
					$str = $date;
					if ($last_day == $lines_array[$y]->date_session) {
						$same_day += 1;
						$this->pdf->SetFillColor(255, 255, 255);
					} else {
						$same_day = 0;
					}
					$this->pdf->SetXY($posX + $larg_col1 + $larg_col2 + ($largeur_date * $y) - ($largeur_date * ($same_day)), $posY);
					$this->pdf->Cell($largeur_date * ($same_day + 1), 4, $this->outputlangs->convToOutputCharset($str), 1, 2, "C", $same_day);

					// horaires
					$this->pdf->SetXY($posX + $larg_col1 + $larg_col2 + ($largeur_date * $y), $posY + 4);
					if ($lines_array[$y]->heured && $lines_array[$y]->heuref) {
						$str = dol_print_date($lines_array[$y]->heured, 'hour') . ' - ' . dol_print_date($lines_array[$y]->heuref, 'hour');
					} else {
						$str = '';
					}
					$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);
					$this->pdf->Cell($largeur_date, 4, $this->outputlangs->convToOutputCharset($str), 1, 2, "C", 0);

					$last_day = $lines_array[$y]->date_session;
				}
				$posY = $this->pdf->GetY();

				// ligne
				$h_ligne = 7;
				if (is_object($dao) && $conf->global->AGF_ADD_ENTITYNAME_FICHEPRES) {
					$h_ligne = $h_ligne + 3;
				}
				if (!empty($conf->global->AGF_ADD_DTBIRTH_FICHEPRES)) {
					$h_ligne = $h_ligne + 3;
				}
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);


				foreach ($agfsta->lines as $line) {

					if (!empty($conf->global->AGF_ADD_INDEX_TRAINEE)) {
						$str = $nbsta_index . '. ';
					} else {
						$str = '';
					}

					$nbsta_index++;
					// Cadre
					$this->pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $h_ligne);

					// Nom
					$this->pdf->SetXY($posX - 2, $posY);
					$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);

					if (!empty($line->civilite)) {
						if ($line->civilite == 'MR') {
							$str .= 'M. ';
						} elseif ($line->civilite == 'MME' || $line->civilite == 'MLE') {
							$str .= 'Mme. ';
						} else {
							$str .= $line->civilite . ' ';
						}
					}
					$str .= $line->nom . ' ' . $line->prenom;
					if (!empty($line->poste)) {
						$str .= ' (' . $line->poste . ')';
					}
					if (!empty($line->date_birth) && !empty($conf->global->AGF_ADD_DTBIRTH_FICHEPRES)) {
						$this->outputlangs->load("other");
						$str .= "\n" . $this->outputlangs->trans('DateToBirth') . ' : ' . dol_print_date($line->date_birth, 'day');
					}

					if (!empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
						if (!empty($line->socname)) {
							$str .= '-' . dol_trunc($line->socname, 27);
						}
					}

					if (is_object($dao) && $conf->global->AGF_ADD_ENTITYNAME_FICHEPRES) {
						$c = new Societe($this->db);
						$c->fetch($line->socid);

						if (count($dao->entities) > 0) {
							foreach ($dao->entities as $e) {
								if ($e->id == $c->entity) {
									$str .= "\n" . $this->outputlangs->trans('Entity') . ' : ' . $e->label;
									break;
								}
							}
						}
					}
					$this->pdf->MultiCell($larg_col1 + 2, $h_ligne, $this->outputlangs->convToOutputCharset($str), 1, "L", false, 1, '', '', true, 0, false, false, $h_ligne, 'M');

					// Société
					if (empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
						$this->pdf->SetXY($posX + $larg_col1, $posY);
						$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);
						$str = dol_trunc($line->socname, 27);
						$this->pdf->MultiCell($larg_col2, $h_ligne, $this->outputlangs->convToOutputCharset($str), 1, "C", false, 1, '', '', true, 0, false, false, $h_ligne, 'M');
					}

					for ($i = 0; $i < $this->nbtimeslots - 1; $i++) {
						$this->pdf->Rect($posX + $larg_col1 + $larg_col2 + $largeur_date * $i, $posY, $largeur_date, $h_ligne);
					}

					$posY = $this->pdf->GetY();
					if ($posY > $this->page_hauteur - $height_for_footer) {
						$this->pdf->AddPage();
						if (!empty($tplidx)) $this->pdf->useTemplate($tplidx);
						$posY = $this->marge_haute;
					}
				}

				// Cachet et signature
				$posY += 2;
				$posX -= 2;
				$this->pdf->SetXY($posX, $posY);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres20');
				$this->pdf->Cell(50, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

				$this->pdf->SetXY($posX + 55, $posY);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres21').dol_print_date($agf->datef);
				$this->pdf->Cell(20, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

				$this->pdf->SetXY($posX + 92, $posY);
				$str = $this->outputlangs->transnoentities('AgfPDFFichePres22');
				$this->pdf->Cell(50, 4, $this->outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

				$posY = $this->pdf->GetY();

				// Incrustation image tampon
				if ($conf->global->AGF_INFO_TAMPON) {
					$dir = $conf->agefodd->dir_output . '/images/';
					$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
					if (file_exists($img_tampon))
						$this->pdf->Image($img_tampon, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 50, $posY, 50);
				}

				// Pied de page
				$this->_pagefoot($this->pdf, $agf, $this->outputlangs);
				if (method_exists($this->pdf, 'AliasNbPages')) {
					$this->pdf->AliasNbPages();
				}
			}
		}
	}

	/**
	 * @param TCPDF $pdf pdf
	 * @param Translate $outputlangs outputlangs
	 * @return void
	 */
	function _pagehead(&$pdf, $outputlangs)
	{
		$this->outputlangs->load("main");

		// Fill header with background color
		$this->pdf->SetFillColor($this->colorheaderBg[0], $this->colorheaderBg[1], $this->colorheaderBg[2]);
		$this->pdf->MultiCell($this->page_largeur, 40, '', 0, 'L', true, 1, 0, 0);

		pdf_pagehead($this->pdf, $this->outputlangs, $this->pdf->page_hauteur);
	}

	/**
	 * @param TCPDF $pdf pdf
	 * @param Object $object object
	 * @param Translate $outputlangs outputlangs
	 * @return int int
	 */
	function _pagefoot(&$pdf, $object, $outputlangs)
	{
		$this->pdf->SetDrawColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		$this->pdf->SetTextColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		return pdf_agfpagefoot($this->pdf, $this->outputlangs, '', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, 1, 0);
	}
}
