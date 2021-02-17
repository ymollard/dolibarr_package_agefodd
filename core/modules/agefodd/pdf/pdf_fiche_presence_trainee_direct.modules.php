<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2012-2016 Florian Henry <florian.henry@open-concept.pro>
 * Copyright (C) 2014-2015 	Philippe Grand 	<philippe.grand@atoo-net.com>
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
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_convention.class.php');
dol_include_once('/agefodd/class/agefodd_place.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur_calendrier.class.php');
dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
dol_include_once('/agefodd/class/agefodd_opca.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
class pdf_fiche_presence_trainee_direct extends ModelePDFAgefodd {
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
	function __construct($db) {
		global $conf, $langs, $mysoc;

		$this->db = $db;
		$this->name = "fiche_presence_trainee";
		$this->description = $langs->trans('AgfPDFFichePresPers');

		// Dimension page pour format A4 en paysage
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray ['height']; // use standard but reverse width and height to get Landscape format
		$this->page_hauteur = $formatarray ['width']; // use standard but reverse width and height to get Landscape format
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

		// Define position of columns
		$this->posxtrainingname=$this->marge_gauche;
		$this->posxsecondcolumn=50;
		$this->posxstudentname=138.5;
		$this->posxforthcolumn=188.5;
		$this->posxsigndate=$this->marge_gauche;
		$this->posxsignature=40;
		$this->posxmodulename=120;
		$this->posxtrainername=180;
		$this->posxtrainersign=218;
		$this->posxstudenttime=254;

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
	 * @param object $outputlangs for output language
	 * @param string $file file to generate
	 * @param int $socid
	 * @return int <0 if KO, Id of created object if OK
	 */
	function write_file($agf, $outputlangs, $file, $socid, $courrier) {
		global $user, $langs, $conf, $mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		if (! is_object($outputlangs))
			$outputlangs = $langs;

		if (! is_object($agf)) {
			$id = $agf;
			$agf = new Agsession($this->db);
			$ret = $agf->fetch($id);
		}
		//var_dump($agf);exit;
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

			$pdf->SetTitle($outputlangs->convToOutputCharset($outputlangs->transnoentities('AgfPDFFichePresPers') . " " . $agf->ref));
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
				$agfsta = new Agefodd_session_stagiaire($this->db);
				$resql = $agfsta->fetch_stagiaire_per_session($agf->id);
				$nbsta = count($agfsta->lines);

				if ($nbsta > 0) {
					// $blocsta=0;
					foreach ( $agfsta->lines as $line ) {
						if ($conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES!=='') {
							$TStagiaireStatusToExclude = explode(',', $conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES);
							$status_stagiaire = (int) $line->status_in_session;
							if (in_array($status_stagiaire, $TStagiaireStatusToExclude)) {
								setEventMessage($langs->trans('AgfStaNotInStatusToOutput', $line->nom), 'warnings');
								continue;
							}
						}
						$this->_pagebody($pdf, $agf, 1, $outputlangs, $line);
					}
				} else {
					$pdf->AddPage();
					$pagenb ++;
					$this->_pagehead($pdf, $agf, 1, $outputlangs);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
					$pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3
					$pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);

					$posY = $this->marge_haute;
					$posX = $this->marge_gauche;

					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("No Trainee"), 0, 'R');
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
	 *   Show table for lines
	 *
	 *   @param		object			$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop=0, $hidebottom=0)
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetDrawColor(128,128,128);
		$pdf->SetFont('','',$default_font_size - 1);
		$tab_height=20;
		$tab_top=75;

		// Output Rect
		$this->printRect($pdf,$this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, $hidetop, $hidebottom);	// Rect prend une longueur en 3eme param et 4eme param

		//Training name
		$pdf->line($this->marge_gauche, $tab_top+$tab_height/2, $this->page_largeur-$this->marge_droite, $tab_top+$tab_height/2);	// line prend une position y en 2eme param et 4eme param
		$pdf->SetXY($this->posxtrainingname, $tab_top+$tab_height/4-2);
		$pdf->MultiCell(50,2, $outputlangs->transnoentities("AgfFormIntitule"),'','L');
		//Training number
		$pdf->SetXY($this->posxtrainingname, $tab_top+$tab_height*3/4-2);
		$pdf->MultiCell(50,2, $outputlangs->transnoentities("AgfFormNumber"),'','L');

		//second column
		$pdf->line($this->posxsecondcolumn-1, $tab_top, $this->posxsecondcolumn-1, $tab_top + $tab_height);
		$pdf->SetXY($this->posxsecondcolumn-3, $tab_top+$tab_height/4-2);
		$pdf->MultiCell($this->posxstudentname-$this->posxsecondcolumn+3,2, $outputlangs->transnoentities(""),'','C');

		//Student name
		$pdf->line($this->posxstudentname-1, $tab_top, $this->posxstudentname-1, $tab_top + $tab_height);
		$pdf->SetXY($this->posxstudentname-3, $tab_top+$tab_height/4-2);
		$pdf->MultiCell($this->posxforthcolumn-$this->posxstudentname+3,2, $outputlangs->transnoentities("AgfPDFFichePresPersNomTrainee"),'','C');
		//Customer or OPCA
		$pdf->SetXY($this->posxstudentname-3, $tab_top+$tab_height*3/4-2);
		$pdf->MultiCell($this->posxforthcolumn-$this->posxstudentname+3,2, $outputlangs->transnoentities("AgfPDFFichePresPersCustOPCA"),'','C');

		//forth column
		$pdf->line($this->posxforthcolumn-1, $tab_top, $this->posxforthcolumn-1, $tab_top + $tab_height);
		$pdf->SetXY($this->posxforthcolumn-3, $tab_top+$tab_height/4-2);
		$pdf->MultiCell($this->posxforthcolumn+3,2, $outputlangs->transnoentities(""),'','C');

		$tab_height=64;
		$tab_top=100;
		$h_ligne=6;

		// Output Rect
		$this->printRect($pdf,$this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, $hidetop, $hidebottom);	// Rect prend une longueur en 3eme param et 4eme param
		$pdf->line($this->posxsignature, $tab_top, $this->posxsignature, $tab_top + $tab_height-6);
		$pdf->line($this->posxmodulename/2+20, $tab_top+12, $this->posxmodulename/2+20, $tab_top + $tab_height-6);
		$pdf->line($this->posxmodulename, $tab_top, $this->posxmodulename, $tab_top + $tab_height-6);
		$pdf->line($this->posxtrainername, $tab_top, $this->posxtrainername, $tab_top + $tab_height);
		$pdf->line($this->posxtrainersign, $tab_top, $this->posxtrainersign, $tab_top + $tab_height-6);
		$pdf->line($this->posxstudenttime, $tab_top, $this->posxstudenttime, $tab_top + $tab_height);
		$pdf->line($this->posxsignature, $tab_top+12, $this->page_largeur-$this->marge_droite, $tab_top+12);
		$pdf->line($this->marge_gauche, $tab_top+22, $this->page_largeur-$this->marge_droite, $tab_top+22);
		$pdf->line($this->marge_gauche, $tab_top+28, $this->page_largeur-$this->marge_droite, $tab_top+28);
		$pdf->line($this->marge_gauche, $tab_top+34, $this->page_largeur-$this->marge_droite, $tab_top+34);
		$pdf->line($this->marge_gauche, $tab_top+40, $this->page_largeur-$this->marge_droite, $tab_top+40);
		$pdf->line($this->marge_gauche, $tab_top+46, $this->page_largeur-$this->marge_droite, $tab_top+46);
		$pdf->line($this->marge_gauche, $tab_top+52, $this->page_largeur-$this->marge_droite, $tab_top+52);
		$pdf->line($this->marge_gauche, $tab_top+58, $this->page_largeur-$this->marge_droite, $tab_top+58);
		$pdf->SetXY($this->marge_gauche, $tab_top+10);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres27'). "\n";
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres28'). "\n";
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres29');
		$pdf->MultiCell($this->posxsignature-$this->marge_gauche,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');
		$pdf->SetXY($this->posxsignature, $tab_top+5);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres30');
		$pdf->MultiCell($this->posxmodulename-$this->posxsignature,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');
		$pdf->SetXY($this->posxsignature, $tab_top+14);
		$this->str = $outputlangs->transnoentities('Matin'). "\n";
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres31');
		$pdf->MultiCell(($this->posxmodulename-$this->posxsignature)/2,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');
		$pdf->SetXY($this->posxsignature+20, $tab_top+14);
		$this->str = $outputlangs->transnoentities('Après - midi'). "\n";
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres31');
		$pdf->MultiCell($this->posxsignature+($this->posxmodulename-$this->posxsignature)/2,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');
		$pdf->SetXY($this->posxmodulename, $tab_top+2);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres32'). "\n";
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres33');
		$pdf->MultiCell($this->posxtrainername-$this->posxmodulename,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');
		$pdf->SetXY($this->posxtrainername, $tab_top+2);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres34'). "\n";
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres35');
		$pdf->MultiCell($this->posxtrainersign-$this->posxtrainername,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');
		$pdf->SetXY($this->posxtrainersign, $tab_top+2);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres36'). "\n";
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres35');
		$pdf->MultiCell($this->posxstudenttime-$this->posxtrainersign,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');
		$pdf->SetXY($this->posxstudenttime, $tab_top);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres37'). "\n";
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres38');
		$pdf->MultiCell($this->marge_droite-$this->posxstudenttime,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');
		$pdf->SetXY($this->posxtrainername, $tab_top+60);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres39');
		$pdf->MultiCell($this->posxstudenttime-$this->posxtrainername,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');
		$pdf->SetFont('','I',$default_font_size - 1);
		/*$pdf->SetTextColor(200,0,0);
		$pdf->SetXY($this->marge_gauche, $tab_top+60);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres40');
		$pdf->MultiCell($this->posxtrainername-$this->marge_gauche,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');
		$pdf->SetTextColor(0,0,0);*/

		// Output Rect for signature
		$this->printRect($pdf,$this->marge_gauche, $tab_top+66, $this->posxsignature+($this->posxmodulename-$this->posxsignature)/2, 24);

	}

	/**
	 * \brief Show header of page
	 * \param pdf Object PDF
	 * \param object Object invoice
	 * \param showaddress 0=no, 1=yes
	 * \param outputlangs		Object lang for output
	 * \param $line			Trainee object
	 */
	function _pagebody(&$pdf, $agf, $showaddress = 1, $outputlangs, $line) {
		global $user, $langs, $conf, $mysoc;

		// New page
		$pdf->AddPage();
		$pagenb ++;
		$this->_pagehead($pdf, $agf, 1, $outputlangs);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
		$pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3
		$pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);

		$posY = $this->marge_haute;
		$posX = $this->marge_gauche;

		/*
		 * Header société
		 */

		$posY = $pdf->GetY();

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
		$posY = $posY + 3;

		// Titre
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 18);
		$pdf->SetTextColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePresPers');
		$pdf->Cell(0, 6, $outputlangs->convToOutputCharset($this->str), 0, 2, "C", 0);
		$pdf->Line($this->marge_gauche + 0.5, $posY+10, $this->page_largeur - $this->marge_droite, $posY+10);
		$posY += 13;

		// Intro
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
		$pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres2') . ' « ' . $mysoc->name . ' », ' . $outputlangs->transnoentities('AgfPDFFichePres3') . ' ';
		$this->str .= $mysoc->address . ' ' .$mysoc->zip . ' ' . $mysoc->town;
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres4') . ' ' . $conf->global->AGF_ORGANISME_REPRESENTANT . ",\n";
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres5');
		$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'C');
		$hauteur = dol_nboflines_bis($this->str, 50) * 2;
		$posY += $hauteur + 2;

		/**
		 * *** Bloc formation ****
		 */
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), 'BI', 9);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres23');
		//$pdf->Cell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);
		$posY += 4;

		$tab_height=20;
		$tab_top=75;
		// Output Rect
		$this->_tableau($pdf, $tab_top, 40, 0, $outputlangs, 0, 0);

		// $pdf->Line($posX, $posY, $this->page_largeur - $this->marge_droite, $posY);
		$cadre_tableau = array (
				$posX,
				$posY
		);

		$posX += 2;

		$posYintitule = $posY;

		$larg_col1 = 20;
		$larg_col2 = 80;
		$larg_col3 = 27;
		$larg_col4 = 82;
		$haut_col2 = 0;
		$haut_col4 = 0;

		// Intitulé
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres6');
		//$pdf->Cell($larg_col1, 4, $outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);


		$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 9);

		if (empty($agf->intitule_custo)) {
			$this->str = '« ' . $agf->formintitule . ' »';
		} else {
			$this->str = '« ' . $agf->intitule_custo . ' »';
		}
		if (strlen($this->str)>46) {
			$pdf->SetXY($this->posxsecondcolumn+1, $tab_top+$tab_height/4-3);
		} else {
			$pdf->SetXY($this->posxsecondcolumn+1, $tab_top+$tab_height/4-2);
		}
		$pdf->MultiCell($this->posxstudentname-$this->posxsecondcolumn, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');

		//training number
		$pdf->SetXY($this->posxsecondcolumn+1, $tab_top+$tab_height/2 +3);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 9);
		$pdf->MultiCell($this->posxstudentname-$this->posxsecondcolumn, 4, $agf->id, 0, 'L');

		//trainee name
		$pdf->SetXY($this->posxforthcolumn+1, $tab_top+$tab_height/4-3);
		$this->str = $line->nom . ' ' . $line->prenom;
		if (! empty($line->poste) && empty($conf->global->AGF_HIDE_POSTE_FICHEPRES)) {
			$this->str .= ' (' . $line->poste . ')';
		}
		$pdf->MultiCell($this->posxstudentname-$this->posxsecondcolumn, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');

		//customer or OPCA
		$pdf->SetXY($this->posxforthcolumn+1, $tab_top+$tab_height/2 +3);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 9);

		$opca = new Agefodd_opca($this->db);
		$opca->getOpcaForTraineeInSession($line->socid,$agf->id,$line->stagerowid);
		if (!empty($opca->opca_rowid)) {
			$pdf->MultiCell($this->posxstudentname-$this->posxsecondcolumn, 4, $opca->soc_OPCA_name, 0, 'L');
		} elseif (!empty($agf->soc_OPCA_name)) {
			$pdf->MultiCell($this->posxstudentname-$this->posxsecondcolumn, 4, $agf->soc_OPCA_name, 0, 'L');
		} else {
			$pdf->MultiCell($this->posxstudentname-$this->posxsecondcolumn, 4, $line->socname, 0, 'L');
		}

		$posY = $pdf->GetY() + 2;
		$haut_col2 += $hauteur;

		// Période
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres7');
		//$pdf->Cell($larg_col1, 4, $outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);

		$this->str = $agf->libSessionDate('daytext');

		$pdf->SetXY($posX + $larg_col1, $posY);
		//$pdf->MultiCell($larg_col2, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
		$hauteur = dol_nboflines_bis($this->str, 50) * 4;
		$haut_col2 += $hauteur + 2;

		// Lieu
		$pdf->SetXY($posX + $larg_col1 + $larg_col2, $posYintitule);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres11');
		//$pdf->Cell($larg_col3, 4, $outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);

		$agf_place = new Agefodd_place($this->db);
		$resql = $agf_place->fetch($agf->placeid);

		$pdf->SetXY($posX + $larg_col1 + $larg_col2 + $larg_col3, $posYintitule);
		$this->str = $agf_place->ref_interne . "\n" . $agf_place->adresse . "\n" . $agf_place->cp . " " . $agf_place->ville;
		//$pdf->MultiCell($larg_col4, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
		$hauteur = dol_nboflines_bis($this->str, 50) * 4;
		$posY += $pdf->GetY() + 5;
		$haut_col4 += $hauteur + 7;


		/**
		 * *** Bloc stagiaire et formateur ****
		 * et participants
		 */

		$formateurs = new Agefodd_session_formateur($this->db);
		$nbform = $formateurs->fetch_formateur_per_session($agf->id);

		$posY = $pdf->GetY() + 40;

		$larg_col1 = $this->posxsecondcolumn - $this->marge_gauche;
		$larg_col2 = $this->posxmodulename/2+20 - $this->posxsecondcolumn;

		$larg_col3 = $larg_col2;
		$larg_col4 = $this->posxtrainername-$this->posxmodulename;
		$haut_col2 = 0;
		$haut_col4 = 0;
		$h_ligne = 7;
		$haut_cadre = 0;
		$tab_top=100;
		$posX=$this->marge_gauche;
		// Entête

		// Trainee
		$posy_trainee=$posY;
		$pdf->SetXY($posX + $larg_col1 + $larg_col2, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 9);
		$this->str = $line->nom . ' ' . $line->prenom . ' - ' . dol_trunc($line->socname, 27);
		if (! empty($line->poste) && empty($conf->global->AGF_HIDE_POSTE_FICHEPRES)) {
			$this->str .= ' (' . $line->poste . ')';
		}
		//$pdf->Cell($larg_col3, 5, $outputlangs->convToOutputCharset($this->str), TR, 2, "C", 0);

		$posY = $pdf->GetY();

		// ligne
		$h_ligne = 6;
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);

		// Date
		$agf_date = new Agefodd_sesscalendar($this->db);
		$resql = $agf_date->fetch_all($agf->id);
		if (count($agf_date->lines)>0) {
			$posY= $tab_top+22;
			$previousday=$agf_date->lines[0]->date_session;
			$day_output=false;
			$trainer_output_array=array();
			foreach ( $agf_date->lines as $linedate ) {

				if ($linedate->date_session!=$previousday) {
					$posY = $pdf->GetY()+$h_ligne;
					if ($posY > $this->page_hauteur - 20) {
						$pdf->AddPage();
						$pagenb ++;
						$posY = $this->marge_haute;
					}
					$day_output=false;
				}

				// horaires
				if ($linedate->heured && $linedate->heuref) {
					$this->str = dol_print_date($linedate->heured, 'hour') . ' - ' . dol_print_date($linedate->heuref, 'hour');
				} else {
					$this->str = '';
				}

				if ($linedate->date_session==$previousday && $day_output) {
					//sur le deuxième créneau horaire (après-midi)
					$pdf->SetXY($posX + $larg_col1+ $larg_col2, $posY);
				} else {
					//sur le premier (matin)
					$pdf->SetXY($larg_col1, $posY);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
				$pdf->MultiCell($larg_col2, $h_ligne, $outputlangs->convToOutputCharset($this->str), '', "C", false, 0, '', '', true, 0, false, false, $h_ligne, 'M');

				// Jour
				if (!$day_output) {
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
					if ($linedate->date_session) {
						$date = dol_print_date($linedate->date_session, 'daytextshort');
					} else {
						$date = '';
					}
					$this->str = $date;
					$pdf->SetXY($posX,$posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
					$pdf->MultiCell($larg_col2, $h_ligne, $outputlangs->convToOutputCharset($this->str), '', "C", false, 0, '', '', true, 0, false, false, $h_ligne, 'M');
					$day_output=true;

					// Training
					if (empty($agf->intitule_custo)) {
						$this->str = $agf->formintitule;
					} else {
						$this->str = $agf->intitule_custo;
					}
					$pdf->SetXY($this->posxmodulename,$posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 6);
					$pdf->MultiCell($larg_col4, $h_ligne, $outputlangs->convToOutputCharset($this->str), 0, 'L', false, 0, '', '', true, 0, false, false, $h_ligne, 'M');

				}

				//Trainer
				foreach($formateurs->lines as $trainer_line) {
					$this->str='';
					$trainer_calendar = new Agefoddsessionformateurcalendrier($this->db);
					$trainer_calendar->fetch_all($trainer_line->opsid);

					foreach($trainer_calendar->lines as $cal_lines){
						if ($cal_lines->date_session==$linedate->date_session &&
								$linedate->heured == $cal_lines->heured &&
								$linedate->heuref == $cal_lines->heuref) {

									$this->str .= strtoupper($trainer_line->lastname) . ' ' . ucfirst($trainer_line->firstname)."\n";
								}
					}
					if (((array_key_exists($linedate->date_session, $trainer_output_array ) &&
							!in_array($trainer_line->opsid,$trainer_output_array[$linedate->date_session])) ||
							!array_key_exists($linedate->date_session, $trainer_output_array )) && (!empty($this->str))) {

								$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);

								if ((array_key_exists($linedate->date_session, $trainer_output_array ) &&
										!in_array($trainer_line->opsid,$trainer_output_array[$linedate->date_session])))
								{
									$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, "L",false,0,$this->posxtrainername,$posY+3);
								} else {
									$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, "L",false,0,$this->posxtrainername,$posY);
								}
								$pdf->SetXY($this->posxtrainername,$posY);
								$trainer_output_array[$linedate->date_session][]=$trainer_line->opsid;
							}
				}

				$previousday=$linedate->date_session;
			}
		}
		//$posY = $pdf->GetY();

		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);

		// Cachet et signature
		$posY = $tab_top+66;
		$pdf->SetXY($this->marge_gauche+1, $posY);
		$this->str = $outputlangs->transnoentities('AgfCertifExactBy');
		$pdf->MultiCell($this->posxtrainername-$this->marge_gauche,$h_ligne, $outputlangs->convToOutputCharset($this->str).' '.$mysoc->name,'','L');
		$pdf->SetXY($this->marge_gauche+1, $posY+4);
		//$this->str = $outputlangs->transnoentities('Monsieur').' '.ucfirst($trainer_line->firstname).' '.strtoupper($trainer_line->lastname);
		$this->str = $conf->global->AGF_ORGANISME_REPRESENTANT;
		$pdf->MultiCell($this->posxtrainername-$this->marge_gauche,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','L');
		$posY = $pdf->GetY();
		$pdf->SetXY($this->marge_gauche+1, $posY);
		$pdf->MultiCell($this->posxtrainername-$this->marge_gauche,$h_ligne, $outputlangs->transnoentities("AgfPDFFichePres21").$date,'','L');
		$posY = $pdf->GetY();
		$pdf->SetXY($this->marge_gauche+1, $posY);
		$pdf->MultiCell($this->posxtrainername-$this->marge_gauche,$h_ligne, $outputlangs->transnoentities("AgfPDFFichePres18").' : ','','L');

		$posY = $pdf->GetY() - 17;
		$posX = $this->marge_gauche+30;

		// Incrustation image tampon
		if ($conf->global->AGF_INFO_TAMPON) {
			$dir = $conf->agefodd->dir_output . '/images/';
			$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
			if (file_exists($img_tampon) && is_readable($img_tampon))
			{
				$pdf->SetXY($posX, $posY);
				$tampon_height=pdf_getHeightForLogo($img_tampon,true);
				$pdf->Image($img_tampon, $posX, $posY, 0, $tampon_height);
			}
		}

		// Pied de page
		$this->_pagefoot($pdf, $agf, $outputlangs);
		// FPDI::AliasNbPages() is undefined method into Dolibarr 3.5
		if (method_exists($pdf, 'AliasNbPages')) {
			$pdf->AliasNbPages();
		}
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

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $pdf->page_hauteur);

		$pdf->SetTextColor($this->colorheaderText [0], $this->colorheaderText [1], $this->colorheaderText [2]);

		$posy=$this->marge_haute;
		$posx=$this->page_largeur-$this->marge_droite-55;

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		$width_logo = pdf_getWidthForLogo($logo);
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

		// Affichage du logo commanditaire (optionnel)
		if ($conf->global->AGF_USE_LOGO_CLIENT) {
			$staticsoc = new Societe($this->db);
			$staticsoc->fetch($object->socid);
			$dir = $conf->societe->multidir_output [$staticsoc->entity] . '/' . $staticsoc->id . '/logos/';
			if (! empty($staticsoc->logo)) {
				$logo_client = $dir . $staticsoc->logo;
				if (file_exists($logo_client) && is_readable($logo_client)){
					$heightlogo = pdf_getHeightForLogo($logo_client);
					$pdf->Image($logo_client, $this->page_largeur - $this->marge_gauche - $this->marge_droite - ( $width_logo * 1.5), $this->marge_haute, $heightlogo);
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
			$pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);

			// Show sender name
			$pdf->SetXY($posx,$posy);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy=$pdf->GetY();

			// Show sender information
			$pdf->SetXY($posx,$posy);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
			$posy=$pdf->GetY();
			$pdf->SetXY($posx,$posy);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->zip.' '.$this->emetteur->town), 0, 'L');
			$posy=$pdf->GetY();
			$pdf->SetXY($posx,$posy);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
			$posy=$pdf->GetY();
			$pdf->SetXY($posx,$posy);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');
			$posy=$pdf->GetY();

			printRefIntForma($this->db, $outputlangs, $object, $default_font_size - 1, $pdf, $posx, $posy, 'L');
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

		global $conf, $langs, $mysoc;
		$pdf->SetTextColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
		$pdf->SetDrawColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
		return pdf_agfpagefoot($pdf,$outputlangs,'',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,1,$hidefreetext);
	}
}
