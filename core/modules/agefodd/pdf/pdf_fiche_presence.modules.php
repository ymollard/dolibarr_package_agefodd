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
 * \file agefodd/core/modules/agefodd/pdf/pdf_fiche_presence.modules.php
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

class pdf_fiche_presence extends ModelePDFAgefodd {
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
		$this->formation_widthcol4 = 82;

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

				$pdf->SetTitle($outputlangs->convToOutputCharset($outputlangs->transnoentities('AgfPDFFichePres1') . " " . $agf->ref));
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
						$this->_pagebody($pdf, $agf, $outputlangs);
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
			$pagecount = $pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P);
			$tplidx = $pdf->importPage(1);
		}

		$height_for_footer = 20;
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

		foreach($session_hours as $key=>$lines_array) {
			// New page
			$pdf->AddPage();
			if (! empty($tplidx)) $pdf->useTemplate($tplidx);
			$this->_pagehead($pdf, $outputlangs);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
			$pdf->SetTextColor($this->colorheaderText[0], $this->colorheaderText[1], $this->colorheaderText[2]);

			$posY = $this->marge_haute;
			$posX = $this->page_largeur - $this->marge_droite - 55;

			// Logo
			$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
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

			$posY = $this->marge_haute;
			$posX = $this->marge_gauche;

			$hautcadre = 30;
			$pdf->SetXY($posX, $posY);
			$pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);

			// Show sender name
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont('', 'B', $this->default_font_size - 2);
			$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posY = $pdf->GetY();

			// Show sender information
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont('', '', $this->default_font_size - 3);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
			$posY = $pdf->GetY();
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont('', '', $this->default_font_size - 3);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->zip . ' ' . $this->emetteur->town), 0, 'L');
			$posY = $pdf->GetY();
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont('', '', $this->default_font_size - 3);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
			$posY = $pdf->GetY();
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont('', '', $this->default_font_size - 3);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');
			$posY = $pdf->GetY();

			printRefIntForma($this->db, $outputlangs, $agf, $this->default_font_size - 3, $pdf, $posX, $posY, 'L');

			// Affichage du logo commanditaire (optionnel)
			if ($conf->global->AGF_USE_LOGO_CLIENT) {
				$staticsoc = new Societe($this->db);
				$staticsoc->fetch($agf->socid);
				$dir = $conf->societe->multidir_output[$staticsoc->entity] . '/' . $staticsoc->id . '/logos/';
				if (! empty($staticsoc->logo)) {
					$logo_client = $dir . $staticsoc->logo;
					if (file_exists($logo_client) && is_readable($logo_client))
						$pdf->Image($logo_client, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 30, $this->marge_haute, 40);
				}
			}

			$posY = $pdf->GetY() + 10;
			if ($conf->global->AGF_PRINT_INTERNAL_REF_ON_PDF) $posY -= 4;

			$pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
			$pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);

			$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);

			// Mise en page de la baseline
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 18);
			$str = $outputlangs->transnoentities($mysoc->url);
			$width = $pdf->GetStringWidth($str);

			// alignement du bord droit du container avec le haut de la page
			$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $width;
			$baseline_x = 8;
			$baseline_y = $this->espaceV_dispo - $baseline_ecart + 30;
			$pdf->SetXY($baseline_x, $baseline_y);

			/*
			 * Corps de page
			 */
			$posX = $this->marge_gauche;
			$posY = $posY + $this->header_vertical_margin;

			// Titre
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 18);
			$pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
			$str = $outputlangs->transnoentities('AgfPDFFichePres1');
			$pdf->Cell(0, 6, $outputlangs->convToOutputCharset($str), 0, 2, "C", 0);
			$posY += 6 + 4;

			// Intro
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
			$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
			$str = $outputlangs->transnoentities('AgfPDFFichePres2') . ' « ' . $mysoc->name . ' »,' . $outputlangs->transnoentities('AgfPDFFichePres3') . ' ';
			$str .= str_replace(array( '<br>', '<br />', "\n", "\r" ), array( ' ', ' ', ' ', ' ' ), $mysoc->address) . ' ';
			$str .= $mysoc->zip . ' ' . $mysoc->town;
			$str .= $outputlangs->transnoentities('AgfPDFFichePres4') . ' ' . $conf->global->AGF_ORGANISME_REPRESENTANT . ",\n";
			$str .= $outputlangs->transnoentities('AgfPDFFichePres5');
			$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($str), 0, 'C');
			$posY  = $pdf->GetY()+1;

			/**
			 * *** Bloc formation ****
			 */
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'BI', 9);
			$str = $outputlangs->transnoentities('AgfPDFFichePres23');
			$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
			$posY += 4;

			$cadre_tableau = array (
					$posX,
					$posY
			);

			$posX += 2;
			$posY += 2;
			$posYintitule = $posY;

			$larg_col1 = $this->formation_widthcol1;
			$larg_col2 = $this->formation_widthcol2;
			$larg_col3 = $this->formation_widthcol3;
			$larg_col4 = $this->formation_widthcol4;
			$haut_col2 = 0;
			$haut_col4 = 0;

			// Intitulé
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
			$str = $outputlangs->transnoentities('AgfPDFFichePres6');
			$pdf->Cell($larg_col1, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

			$pdf->SetXY($posX + $larg_col1, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 9);

			if (empty($agf->intitule_custo)) {
				$str = '« ' . $agf->formintitule . ' »';
			} else {
				$str = '« ' . $agf->intitule_custo . ' »';
			}
			$pdf->MultiCell($larg_col2, 4, $outputlangs->convToOutputCharset($str), 0, 'L');

			$posY = $pdf->GetY() + 2;
			//$haut_col2 += $hauteur;

			// Période
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
			$str = $outputlangs->transnoentities('AgfPDFFichePres7');
			$pdf->Cell($larg_col1, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

			$str = $agf->libSessionDate('daytext');

			$str .= ' (' . $agf->duree_session. ' h)';

			$pdf->SetXY($posX + $larg_col1, $posY);
			$pdf->MultiCell($larg_col2, 4, $outputlangs->convToOutputCharset($str), 0, 'L');
			$hauteur = dol_nboflines_bis($str, 50) * 4;
			$haut_col2 += $hauteur + 2;

			// Lieu
			$pdf->SetXY($posX + $larg_col1 + $larg_col2, $posYintitule);
			$str = $outputlangs->transnoentities('AgfPDFFichePres11');
			$pdf->Cell($larg_col3, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

			$agf_place = new Agefodd_place($this->db);
			$resql = $agf_place->fetch($agf->placeid);

			$pdf->SetXY($posX + $larg_col1 + $larg_col2 + $larg_col3, $posYintitule);
			$str = $agf_place->ref_interne . "\n" . $agf_place->adresse . "\n" . $agf_place->cp . " " . $agf_place->ville;
			$pdf->MultiCell($larg_col4, 4, $outputlangs->convToOutputCharset($str), 0, 'L');
			$hauteur = dol_nboflines_bis($str, 50) * 4;
			$posY += $hauteur + 3;
			$haut_col4 += $hauteur + 7;

			// Cadre
			($haut_col4 > $haut_col2) ? $haut_table = $haut_col4 : $haut_table = $haut_col2;
			$pdf->Rect($cadre_tableau[0], $cadre_tableau[1], $this->espaceH_dispo, $haut_table);

			/**
			 * *** Bloc formateur ****
			 */

			$pdf->SetXY($posX - 2, $posY - 2);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'BI', 9);
			$str = $outputlangs->transnoentities('AgfPDFFichePres12');
			$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
			$posY += 2;

			$larg_col1 = $this->trainer_widthcol1;
			$h_ligne = 7;

			// Entête
			// Cadre
			$pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $h_ligne + 8);
			// Nom
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
			$str = $outputlangs->transnoentities('AgfPDFFichePres16');
			$pdf->Cell($larg_col1, $h_ligne + 8, $outputlangs->convToOutputCharset($str), 'R', 2, "C", 0);
			// Signature
			$pdf->SetXY($posX + $larg_col1, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
			$str = $outputlangs->transnoentities('AgfPDFFichePres18');
			$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);

			$pdf->SetXY($posX + $larg_col1, $posY + 3);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', 7);
			$str = $outputlangs->transnoentities('AgfPDFFichePres13');
			$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);
			$posY += $h_ligne;

			// Date

			$largeur_date = $this->trainer_widthtimeslot;
			$last_day='';
			$same_day = 0;
			for($y = 0; $y < $this->nbtimeslots; $y ++) {
				// Jour
				$pdf->SetXY($posX + $larg_col1 + (20 * $y), $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
				if ($lines_array[$y]->date_session) {
					$date = dol_print_date($lines_array[$y]->date_session, 'daytextshort');
				} else {
					$date = '';
				}
				$str = $date;
				if ($last_day == $lines_array[$y]->date_session) {
					$same_day += 1;
					$pdf->SetFillColor(255, 255, 255);
				} else {
					$same_day = 0;
				}
				$pdf->SetXY($posX + $larg_col1 + ($largeur_date * $y) - ($largeur_date * ($same_day)), $posY);
				$pdf->Cell($largeur_date * ($same_day + 1), 4, $outputlangs->convToOutputCharset($str), 1, 2, "C", $same_day);

				// horaires
				$pdf->SetXY($posX + $larg_col1 + ($largeur_date * $y), $posY + 4);
				if ($lines_array[$y]->heured && $lines_array[$y]->heuref) {
					$str = dol_print_date($lines_array[$y]->heured, 'hour') . ' - ' . dol_print_date($lines_array[$y]->heuref, 'hour');
				} else {
					$str = '';
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);
				$pdf->Cell($largeur_date, 4, $outputlangs->convToOutputCharset($str), 1, 2, "C", 0);

				$last_day = $lines_array[$y]->date_session;
			}
			$posY = $pdf->GetY();

			$formateurs = new Agefodd_session_formateur($this->db);
			$nbform = $formateurs->fetch_formateur_per_session($agf->id);
			if ($nbform>0) {
				foreach ( $formateurs->lines as $trainerlines ) {

					// Cadre
					$pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $h_ligne);

					// Nom
					$pdf->SetXY($posX - 2, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);
					$str = strtoupper($trainerlines->lastname) . ' ' . ucfirst($trainerlines->firstname);
					$pdf->MultiCell($larg_col1 + 2, $h_ligne, $outputlangs->convToOutputCharset($str), 1, "L", false, 1, '', '', true, 0, false, false, $h_ligne, 'M');

					for($i = 0; $i < $this->nbtimeslots - 1; $i ++) {
						$pdf->Rect($posX + $larg_col1 + $largeur_date * $i, $posY, $largeur_date, $h_ligne);
					}

					$posY = $pdf->GetY();
					if ($posY > $this->page_hauteur - $height_for_footer) {
						$pdf->AddPage();
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$posY = $this->marge_haute;
					}
				}
			}

			$posY = $pdf->GetY() + 2;

			/**
			 * *** Bloc stagiaire ****
			 */
			$agfsta = new Agefodd_session_stagiaire($this->db);
			$resql = $agfsta->fetch_stagiaire_per_session($agf->id);

			$pdf->SetXY($posX - 2, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'BI', 9);
			$str = $outputlangs->transnoentities('AgfPDFFichePres15');
			$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
			$posY = $pdf->GetY();

			$larg_col1 = $this->trainee_widthcol1;
			$larg_col2 = $this->trainee_widthcol2;
			$h_ligne = 7;

			// Entête
			// Cadre
			$pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $h_ligne + 8);
			// Nom
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
			$str = $outputlangs->transnoentities('AgfPDFFichePres16');
			$pdf->Cell($larg_col1, $h_ligne + 8, $outputlangs->convToOutputCharset($str), 'R', 2, "C", 0);
			// Société
			if (empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
				$pdf->SetXY($posX + $larg_col1, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
				$str = $outputlangs->transnoentities('AgfPDFFichePres17');
				$pdf->Cell($larg_col2, $h_ligne + 8, $outputlangs->convToOutputCharset($str), 0, 2, "C", 0);
			} else {
				$larg_col2=0;
			}
			// Signature
			$pdf->SetXY($posX + $larg_col1 + $larg_col2, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
			$str = $outputlangs->transnoentities('AgfPDFFichePres18');
			$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);

			$pdf->SetXY($posX + $larg_col1 + $larg_col2, $posY + 3);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', 7);
			$str = $outputlangs->transnoentities('AgfPDFFichePres19');
			$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);
			$posY += $h_ligne;

			// Date
			$agf_date = new Agefodd_sesscalendar($this->db);
			$resql = $agf_date->fetch_all($agf->id);
			if (!$resql) {
				setEventMessages($agf_date->error,'errors');
			}
			$largeur_date = $this->trainee_widthtimeslot;
			$last_day='';
			for($y = 0; $y < $this->nbtimeslots; $y ++) {
				// Jour
				$pdf->SetXY($posX + $larg_col1 + $larg_col2 + (20 * $y), $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
				if ($lines_array[$y]->date_session) {
					$date = dol_print_date($lines_array[$y]->date_session, 'daytextshort');
				} else {
					$date = '';
				}
				$str = $date;
				if ($last_day == $lines_array[$y]->date_session) {
					$same_day += 1;
					$pdf->SetFillColor(255, 255, 255);
				} else {
					$same_day = 0;
				}
				$pdf->SetXY($posX + $larg_col1 + $larg_col2 + ($largeur_date * $y) - ($largeur_date * ($same_day)), $posY);
				$pdf->Cell($largeur_date * ($same_day + 1), 4, $outputlangs->convToOutputCharset($str), 1, 2, "C", $same_day);

				// horaires
				$pdf->SetXY($posX + $larg_col1 + $larg_col2 + ($largeur_date * $y), $posY + 4);
				if ($lines_array[$y]->heured && $lines_array[$y]->heuref) {
					$str = dol_print_date($lines_array[$y]->heured, 'hour') . ' - ' . dol_print_date($lines_array[$y]->heuref, 'hour');
				} else {
					$str = '';
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);
				$pdf->Cell($largeur_date, 4, $outputlangs->convToOutputCharset($str), 1, 2, "C", 0);

				$last_day = $lines_array[$y]->date_session;
			}
			$posY = $pdf->GetY();

			// ligne
			$h_ligne = 7;
			if (is_object($dao) && $conf->global->AGF_ADD_ENTITYNAME_FICHEPRES) {
				$h_ligne = $h_ligne + 3;
			}
			if (!empty($conf->global->AGF_ADD_DTBIRTH_FICHEPRES)) {
				$h_ligne = $h_ligne + 3;
			}
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);


			foreach ( $agfsta->lines as $line ) {

				if(!empty($conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES)) {
					$TStagiaireStatusToExclude = explode(',', $conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES);
					$status_stagiaire = (int) $line->status_in_session;
					if(in_array($status_stagiaire, $TStagiaireStatusToExclude)) continue;
				}

				if (!empty($conf->global->AGF_ADD_INDEX_TRAINEE)) {
					$str=$nbsta_index.'. ';
				} else {
					$str='';
				}

				$nbsta_index++;
				// Cadre
				$pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $h_ligne);

				// Nom
				$pdf->SetXY($posX - 2, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);

				if (! empty($line->civilite)) {
					if ($line->civilite=='MR') {
						$str .='M. ';
					}elseif ($line->civilite=='MME' || $line->civilite=='MLE') {
						$str .='Mme. ';
					} else {
						$str .=$line->civilite. ' ';
					}
				}
				$str .= $line->nom . ' ' . $line->prenom;
				if (! empty($line->poste) && empty($conf->global->AGF_HIDE_POSTE_FICHEPRES)) {
					$str .= ' (' . $line->poste . ')';
				}
				if (! empty($line->date_birth) && !empty($conf->global->AGF_ADD_DTBIRTH_FICHEPRES)) {
					$outputlangs->load("other");
					$str .= "\n". $outputlangs->trans('DateToBirth').' : '. dol_print_date($line->date_birth,'day');
				}

				if (!empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
					if (!empty($line->socname)) {
						$str .= '-' . dol_trunc($line->socname, 27);
					}
				}

				if (is_object($dao) && $conf->global->AGF_ADD_ENTITYNAME_FICHEPRES) {
				    $c = new Societe($this->db);
				    $c->fetch($line->socid);

				    if (count($dao->entities)>0){
				        foreach ($dao->entities as $e){
				            if ($e->id == $c->entity) {
				                $str .= "\n". $outputlangs->trans('Entity').' : '. $e->label;
				                break;
				            }
				        }
				    }
				}
				$pdf->MultiCell($larg_col1 + 2, $h_ligne, $outputlangs->convToOutputCharset($str), 1, "L", false, 1, '', '', true, 0, false, false, $h_ligne, 'M');

				// Société
				if (empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
					$pdf->SetXY($posX + $larg_col1, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);
					$str = dol_trunc($line->socname, 27);
					$pdf->MultiCell($larg_col2, $h_ligne, $outputlangs->convToOutputCharset($str), 1, "C", false, 1, '', '', true, 0, false, false, $h_ligne, 'M');
				}

				for($i = 0; $i < $this->nbtimeslots - 1; $i ++) {
					$pdf->Rect($posX + $larg_col1 + $larg_col2 + $largeur_date * $i, $posY, $largeur_date, $h_ligne);
				}

				$posY = $pdf->GetY();
				if ($posY > $this->page_hauteur - $height_for_footer) {
					$pdf->AddPage();
					if (! empty($tplidx)) $pdf->useTemplate($tplidx);
					$posY = $this->marge_haute;
				}
			}

			// Cachet et signature
			$posY += 2;
			$posX -= 2;
			$pdf->SetXY($posX, $posY);
			$str = $outputlangs->transnoentities('AgfPDFFichePres20');
			$pdf->Cell(50, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

			$pdf->SetXY($posX + 55, $posY);
			$str = $outputlangs->transnoentities('AgfPDFFichePres21');
			$pdf->Cell(20, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

			$pdf->SetXY($posX + 92, $posY);
			$str = $outputlangs->transnoentities('AgfPDFFichePres22');
			$pdf->Cell(50, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);

			$posY = $pdf->GetY();

			// Incrustation image tampon
			if ($conf->global->AGF_INFO_TAMPON) {
				$dir = $conf->agefodd->dir_output . '/images/';
				$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
				if (file_exists($img_tampon))
					$pdf->Image($img_tampon, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 50, $posY, 50);
			}

			// Pied de page
			$this->_pagefoot($pdf, $agf, $outputlangs);
			if (method_exists($pdf, 'AliasNbPages')) {
				$pdf->AliasNbPages();
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
		$outputlangs->load("main");

		// Fill header with background color
		$pdf->SetFillColor($this->colorheaderBg[0], $this->colorheaderBg[1], $this->colorheaderBg[2]);
		$pdf->MultiCell($this->page_largeur, 40, '', 0, 'L', true, 1, 0, 0);

		pdf_pagehead($pdf, $outputlangs, $pdf->page_hauteur);
	}

	/**
	 * @param TCPDF $pdf pdf
	 * @param Object $object object
	 * @param Translate $outputlangs outputlangs
	 * @return int int
	 */
	function _pagefoot(&$pdf, $object, $outputlangs)
	{
		$pdf->SetDrawColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		$pdf->SetTextColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		return pdf_agfpagefoot($pdf, $outputlangs, '', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, 1, 0);
	}
}
