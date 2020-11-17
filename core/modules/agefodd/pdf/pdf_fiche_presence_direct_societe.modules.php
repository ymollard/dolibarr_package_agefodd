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
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
class pdf_fiche_presence_direct_societe extends ModelePDFAgefodd {
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
		$this->name = "fiche_presence";
		$this->description = $langs->trans('AgfModPDFFichePres');

		// Dimension page pour format A4 en paysage
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray ['width']; // use standard but reverse width and height to get Landscape format
		$this->page_hauteur = $formatarray ['height']; // use standard but reverse width and height to get Landscape format
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
		$this->posxtrainingaddress=100;
		$this->posxforthcolumn=150;
		$this->posxsigndate=$this->marge_gauche;
		$this->posxsignature=50;
		$this->posxmiddlesignature=105;
		$this->posxstudenttime=160;

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

			$pdf->SetTitle($outputlangs->convToOutputCharset($outputlangs->transnoentities('AgfPDFFichePres41') . " " . $agf->ref));
			$pdf->SetSubject($outputlangs->transnoentities("Invoice"));
			$pdf->SetCreator("Dolibarr " . DOL_VERSION . ' (Agefodd module)');
			$pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($agf->ref) . " " . $outputlangs->transnoentities("Document"));
			if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION)
				$pdf->SetCompression(false);

			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
			$pdf->SetAutoPageBreak(1, 0);

			//On récupère l'id des sociétés des participants
			$agfstaglobal = new Agefodd_session_stagiaire($this->db);
			$resql = $agfstaglobal->fetch_stagiaire_per_session($agf->id);
			$socstagiaires = array();

			$TStagiaireStatusToExclude = array();

			if ($conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES!=='') {
				$TStagiaireStatusToExclude = explode(',', $conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES);
			}

			foreach ($agfstaglobal->lines as $line) {
				if (! empty($TStagiaireStatusToExclude) && in_array($line->status_in_session, $TStagiaireStatusToExclude)) {
					setEventMessage($langs->trans('AgfStaNotInStatusToOutput', $line->nom), 'warnings');
					continue;
				}

				if (! isset($socstagiaires[$line->socid])) {
					$socstagiaires[$line->socid] = new stdClass();
					$socstagiaires[$line->socid]->lines = array();
				}

				$socstagiaires[$line->socid]->lines[] = $line;
			}

			//Pour chaque société, on crée une série de feuilles de présence
			foreach($socstagiaires as $socstagiaires_id => $agfsta) {

				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $agf, 1, $outputlangs);

				// Output Rect
				$this->_tableau($pdf, $tab_top, 40, 0, $outputlangs, 0, 0);

				//signature and tampon
				$this->_agreement($pdf, $agf, $posy, $outputlangs);

				// Pied de page
				$this->_pagefoot($pdf, $agf, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}

				$tab_height = 30;
				$tab_top = 80;


				$posX = $this->marge_gauche;
				$posY = $tab_top;
				$posYintitule = $posY;

				$larg_col1 = $this->posxsecondcolumn - $this->marge_gauche;
				$larg_col2 = $this->posxtrainingaddress - $this->posxsecondcolumn;
				$larg_col3 = $this->posxforthcolumn - $this->posxtrainingaddress;
				$larg_col4 = 50;
				$haut_col2 = 0;
				$haut_col4 = 0;

				/**
				 * *** Bloc formation ****
				 */

				$this->_training($pdf, $agf, $posy, $outputlangs);

				/**
				 * *** Bloc stagiaire ****
				 */
				$posY = $pdf->GetY() + 25;

				$larg_col1 = 40;
				$larg_col2 = 40;
				$larg_col3 = 50;
				$larg_col4 = 112;
				$haut_col2 = 0;
				$haut_col4 = 0;
				$h_ligne = 8;

				$posY += $h_ligne;

				// Date
				$agf_date = new Agefodd_sesscalendar($this->db);
				$resql = $agf_date->fetch_all($agf->id);
				$largeur_date = 18;
				for ($y = 0; $y < 6; $y++) {
					// Jour
					$pdf->SetXY($posX + $larg_col1 + $larg_col2 + (20 * $y), $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size - 4);
					if ($agf_date->lines [$y]->date_session) {
						$date = dol_print_date($agf_date->lines [$y]->date_session, 'daytextshort');
					} else {
						$date = '';
					}
					$this->str = $date;
					if ($last_day == $agf_date->lines [$y]->date_session) {
						$same_day += 1;
						$pdf->SetFillColor(255, 255, 255);
					} else {
						$same_day = 0;
					}
					$pdf->SetXY($posX + $larg_col1 + $larg_col2 + ($largeur_date * $y) - ($largeur_date * ($same_day)), $posY);
					//$pdf->Cell($largeur_date * ($same_day + 1), 4, $outputlangs->convToOutputCharset($this->str), 1, 2, "C", $same_day);

					// horaires
					$pdf->SetXY($posX + $larg_col1 + $larg_col2 + ($largeur_date * $y), $posY + 4);
					if ($agf_date->lines [$y]->heured && $agf_date->lines [$y]->heuref) {
						$this->str = dol_print_date($agf_date->lines [$y]->heured, 'hour') . ' - ' . dol_print_date($agf_date->lines [$y]->heuref, 'hour');
					} else {
						$this->str = '';
					}
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size - 5);
					//$pdf->Cell($largeur_date, 4, $outputlangs->convToOutputCharset($this->str), 1, 2, "C", 0);

					$last_day = $agf_date->lines [$y]->date_session;
				}
				// lines
				$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size - 3);
				$posY = $pdf->GetY();
				$posYstart = $posY;

				foreach ($agfsta->lines as $line) {

					// Nom
					$pdf->SetXY($posX, $posY);
					$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size - 5);
					$this->str = $line->nom . ' ' . $line->prenom;
					if (!empty($line->poste)) {
						$this->str .= ' (' . $line->poste . ')';
					}
					$pdf->MultiCell($larg_col1 + 2, $h_ligne, $outputlangs->convToOutputCharset($this->str), 0, "L", false, 1, '', '', true, 0, false, false, $h_ligne, 'M');
					//Loop for lines
					$nexY = $pdf->GetY();
					$pdf->line($this->marge_gauche, $nexY, $this->page_largeur - $this->marge_droite, $nexY);

					$posY = $pdf->GetY();
					if ($posY >= 215) {
						$pdf->AddPage();
						$this->_pagehead($pdf, $agf, 1, $outputlangs);
						$this->_tableau($pdf, $tab_top, 40, 0, $outputlangs, 0, 0);
						$this->_training($pdf, $agf, $posy, $outputlangs);
						$this->_agreement($pdf, $agf, $posy, $outputlangs);
						$pagenb++;
						$posY = $posYstart;
					}
				}

				// Pied de page
				$this->_pagefoot($pdf, $agf, $outputlangs);
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

	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop=0, $hidebottom=0)
	{
		global $conf;

		//$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetDrawColor(128,128,128);
		$pdf->SetFont('','',$this->default_font_size - 3);
		$tab_height=30;
		$tab_top=80;

		// Output Rect
		$this->printRect($pdf,$this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, $hidetop, $hidebottom);	// Rect prend une longueur en 3eme param et 4eme param

		//Training name
		$pdf->line($this->marge_gauche, $tab_top+$tab_height/3, $this->page_largeur-$this->marge_droite, $tab_top+$tab_height/3);	// line prend une position y en 2eme param et 4eme param
		$pdf->line($this->marge_gauche, $tab_top+2*$tab_height/3, $this->page_largeur-$this->marge_droite, $tab_top+2*$tab_height/3);	// line prend une position y en 2eme param et 4eme param
		$pdf->SetXY($this->posxtrainingname, $tab_top+$tab_height/6-2);
		$pdf->MultiCell(50,2, $outputlangs->transnoentities("AgfFormIntitule"),'','L');
		//Training number
		$pdf->SetXY($this->posxtrainingname, $tab_top+$tab_height*1/3+3);
		$pdf->MultiCell(50,2, $outputlangs->transnoentities("AgfFormNumber"),'','L');
		// Période
		$pdf->SetXY($this->posxtrainingname, $tab_top+$tab_height*2/3+4);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres12');
		$pdf->Cell(50, 4, $outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);

		//second column
		$pdf->line($this->posxsecondcolumn-1, $tab_top, $this->posxsecondcolumn-1, $tab_top + $tab_height);
		$pdf->SetXY($this->posxsecondcolumn-3, $tab_top+$tab_height/4);
		$pdf->MultiCell($this->posxtrainingaddress-$this->posxsecondcolumn+3,2, $outputlangs->transnoentities(""),'','C');

		//Training address
		$pdf->line($this->posxtrainingaddress-1, $tab_top, $this->posxtrainingaddress-1, $tab_top + $tab_height);
		$pdf->SetXY($this->posxtrainingaddress+3, $tab_top+4);
		$pdf->MultiCell($this->posxforthcolumn-$this->posxtrainingaddress+3,2, $outputlangs->transnoentities('AgfPDFFichePres11'),'','L');
		//Training date
		$pdf->SetXY($this->posxtrainingaddress+3, $tab_top+$tab_height*1/3+4);
		$pdf->MultiCell($this->posxforthcolumn-$this->posxtrainingaddress+3,2, $outputlangs->transnoentities("AgfPDFFichePres7bis"),'','L');
		//Customer or OPCA
		$pdf->SetXY($this->posxtrainingaddress+3, $tab_top+$tab_height*3/4);
		$pdf->MultiCell($this->posxforthcolumn-$this->posxtrainingaddress+3,2, $outputlangs->transnoentities("AgfPDFFichePresPersCustOPCA"),'','L');

		//forth column
		$pdf->line($this->posxforthcolumn-1, $tab_top, $this->posxforthcolumn-1, $tab_top + $tab_height);
		$pdf->SetXY($this->posxforthcolumn+3, $tab_top+$tab_height/4);
		$pdf->MultiCell($this->posxforthcolumn+3,2, $outputlangs->transnoentities(""),'','L');

		$tab_height=110;
		$tab_top=$tab_top + 40;
		$h_ligne=6;

		// Output Rect
		$this->printRect($pdf,$this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, $hidetop, $hidebottom);	// Rect prend une longueur en 3eme param et 4eme param
		$pdf->line($this->posxsignature, $tab_top, $this->posxsignature, $tab_top + $tab_height-6);
		$pdf->line($this->posxmiddlesignature, $tab_top+12, $this->posxmiddlesignature, $tab_top + $tab_height-6);
		$pdf->line($this->posxstudenttime, $tab_top, $this->posxstudenttime, $tab_top + $tab_height);
		$pdf->line($this->posxsignature, $tab_top+12, $this->page_largeur-$this->marge_droite, $tab_top+12);
		$pdf->line($this->marge_gauche, $tab_top+22, $this->page_largeur-$this->marge_droite, $tab_top+22);
		$pdf->line($this->marge_gauche, $tab_top+$tab_height-$h_ligne, $this->page_largeur-$this->marge_droite, $tab_top+$tab_height-$h_ligne);
		$pdf->SetXY($this->marge_gauche, $tab_top+10);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres42');
		$pdf->MultiCell($this->posxsignature-$this->marge_gauche,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');
		$pdf->SetXY($this->posxsignature, $tab_top+5);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres30');
		$pdf->MultiCell($this->posxstudenttime-$this->posxsignature,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');
		$pdf->SetXY($this->posxsignature, $tab_top+14);
		$this->str = $outputlangs->transnoentities('Matin'). "\n";
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres31');
		$pdf->MultiCell($this->posxmiddlesignature-$this->posxsignature,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');
		$pdf->SetXY($this->posxmiddlesignature, $tab_top+14);
		$this->str = $outputlangs->transnoentities('Après - midi'). "\n";
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres31');
		$pdf->MultiCell($this->posxstudenttime-$this->posxmiddlesignature,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');


		$pdf->SetXY($this->posxstudenttime, $tab_top+2);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres37'). "\n";
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres38');
		$pdf->MultiCell($this->marge_droite-$this->posxstudenttime,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','C');
		$pdf->SetXY($this->posxtrainername, $tab_top+$tab_height-$h_ligne+2);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres39');
		$pdf->MultiCell($this->posxstudenttime-$this->posxtrainername,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','R');
	}

	/**
	 *	Show training information
	 *
	 *	@param	PDF			&$pdf           Object PDF
	 *  @param	Object		$agf			Object to show
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _training(&$pdf, $agf, $posy, $outputlangs)
	{
		global $conf,$langs;
		//Bloc formation
		$tab_height=30;
		$tab_top=80;

		$posX = $this->marge_gauche;
		$posY = $tab_top;
		$posYintitule = $posY;

		$larg_col1 = $this->posxsecondcolumn-$this->marge_gauche;
		$larg_col2 = $this->posxtrainingaddress-$this->posxsecondcolumn;
		$larg_col3 = $this->posxforthcolumn-$this->posxtrainingaddress;
		$larg_col4 = 50;
		$haut_col2 = 0;
		$haut_col4 = 0;

		// Intitulé
		$pdf->SetXY($posX + $larg_col1, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size-3);
		$pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);

		if (empty($agf->intitule_custo)) {
			$this->str = '« ' . $agf->formintitule . ' »';
		} else {
			$this->str = '« ' . $agf->intitule_custo . ' »';
		}
		if (strlen($this->str)>46) {
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size-5);
		} else {
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size-3);
		}

		$pdf->MultiCell($larg_col2, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');

		$posY = $pdf->GetY() + 2;

		//training number
		$pdf->SetXY($this->posxsecondcolumn+1, $tab_top+$tab_height*1/3+4);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size-3);
		$pdf->MultiCell($this->posxtrainingaddress-$this->posxsecondcolumn, 4, $agf->id, 0, 'L');





		//Trainers
		$formateurs = new Agefodd_session_formateur($this->db);
		$nbform = $formateurs->fetch_formateur_per_session($agf->id);
		$trainer_output=array();
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size-5);
		foreach($formateurs->lines as $trainerlines) {
			// Name
			$h_ligne=4;
			if (!in_array($trainerlines->opsid,$trainer_output)) {
				$pdf->SetXY($this->posxsecondcolumn+1, $tab_top+$tab_height*2/3+$h_ligne* count($trainer_output));
				$this->str = strtoupper($trainerlines->lastname) . ' ' . ucfirst($trainerlines->firstname);
				$pdf->MultiCell($this->posxtrainingaddress-$this->posxsecondcolumn, $h_ligne, $outputlangs->convToOutputCharset($this->str), 0, "L", false, 1, '', '', true, 0, false, false, $h_ligne, 'M');
				$trainer_output[]=$trainerlines->opsid;
			}
		}

		// Training address
		$pdf->SetXY($posX + $larg_col1 + $larg_col2, $posYintitule);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size-4);

		$agf_place = new Agefodd_place($this->db);
		$resql = $agf_place->fetch($agf->placeid);

		$pdf->SetXY($posX + $larg_col1 + $larg_col2 + $larg_col3, $posYintitule);
		$this->str = $agf_place->ref_interne . "\n" . $agf_place->adresse . " " . $agf_place->cp . " " . $agf_place->ville;
		$pdf->MultiCell($larg_col4, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');
		$hauteur = dol_nboflines_bis($this->str, 50) * 4;
		$posY += $hauteur + 5;
		$haut_col4 += $hauteur + 7;

		// Période
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size-3);

		$this->str = $agf->libSessionDate('daytextshort');

		$pdf->SetXY($posX + $larg_col1 + $larg_col2 + $larg_col3, $tab_top+$tab_height*1/3+2);
		$pdf->MultiCell($larg_col2, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');


		//customer or OPCA
		$pdf->SetXY($posX + $larg_col1 + $larg_col2 + $larg_col3, $tab_top+$tab_height/2 +6);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size-3);
		// On recupere les infos societe
		$agf_soc = new Societe($this->db);
		$result = $agf_soc->fetch($agf->fk_soc);

		if (!empty($agf->soc_OPCA_name)) {
			$pdf->MultiCell($larg_col2, 4, $agf->soc_OPCA_name, 0, 'L');
		} else {
			$pdf->MultiCell($larg_col2, 4, $agf_soc->name, 0, 'L');
		}
	}

	/**
	 *	Show tampon and signature
	 *
	 *	@param	PDF			&$pdf           Object PDF
	 *  @param	Object		$agf			Object to show
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _agreement(&$pdf, $agf, $posy, $outputlangs)
	{
		global $conf,$langs;

		$posY = 240;
		$h_ligne=6;

		// Output Rect for signature
		$this->printRect($pdf,$this->marge_gauche, $posY, $this->posxmiddlesignature-15, 30);
		$this->printRect($pdf,$this->marge_gauche+$this->posxmiddlesignature-5, $posY, $this->posxmiddlesignature-15, 30);

		$posX = 55;
		// Incrustation image tampon
		if ($conf->global->AGF_INFO_TAMPON) {
			$dir = $conf->agefodd->dir_output . '/images/';
			$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
			if (file_exists($img_tampon) && is_readable($img_tampon))
			{
				$pdf->SetXY($posX, $posY);
				$tampon_height=pdf_getHeightForLogo($img_tampon,true);
				$pdf->Image($img_tampon, $posX, $posY+2, 0, $tampon_height);
			}
		}

		$pdf->SetXY($this->marge_gauche+1, $posY+2);
		$this->str = $outputlangs->transnoentities('AgfCertifExactBy');
		$pdf->MultiCell($this->posxtrainername-$this->marge_gauche,$h_ligne, $outputlangs->convToOutputCharset($this->str).' '.$mysoc->name,'','L');
		$posY = $pdf->GetY()-4;
		$pdf->SetXY($this->posxmiddlesignature+8, $posY);
		$pdf->MultiCell(100,$h_ligne, $outputlangs->transnoentities("AgfPDFFichePres16").' '.$outputlangs->transnoentities("AgfPDFFichePres43"),'','L');
		$pdf->SetXY($this->marge_gauche+1, $posY+4);
		//$this->str = $outputlangs->transnoentities('Monsieur').' '.ucfirst($trainer_line->firstname).' '.strtoupper($trainer_line->lastname);
		$this->str = $conf->global->AGF_ORGANISME_REPRESENTANT;
		$pdf->MultiCell($this->posxtrainername-$this->marge_gauche,$h_ligne, $outputlangs->convToOutputCharset($this->str),'','L');
		$posY = $pdf->GetY();
		$pdf->SetXY($this->marge_gauche+1, $posY);
		$pdf->MultiCell($this->posxtrainername-$this->marge_gauche,$h_ligne, $outputlangs->transnoentities("AgfPDFFichePres21").$date,'','L');
		$pdf->SetXY($this->posxmiddlesignature+8, $posY);
		$pdf->MultiCell(50,$h_ligne, $outputlangs->transnoentities("AgfPDFFichePres21").$date,'','L');
		$posY = $pdf->GetY();
		$pdf->SetXY($this->marge_gauche+1, $posY);
		$pdf->MultiCell($this->posxtrainername-$this->marge_gauche,$h_ligne, $outputlangs->transnoentities("AgfPDFFichePres18").' : ','','L');
		$pdf->SetXY($this->posxmiddlesignature+8, $posY);
		$pdf->MultiCell(50,$h_ligne, $outputlangs->transnoentities("AgfPDFFichePres18").' : ','','L');

		$posY = $pdf->GetY();
	}

	/**
	 * \brief Show header of page
	 * \param pdf Object PDF
	 * \param object Object invoice
	 * \param showaddress 0=no, 1=yes
	 * \param outputlangs		Object lang for output
	 */
	function _pagehead(&$pdf, $object, $showaddress = 1, $outputlangs) {
		global $conf, $langs, $mysoc;

		$outputlangs->load("main");

		// Fill header with background color
		$pdf->SetFillColor($this->colorheaderBg[0], $this->colorheaderBg[1], $this->colorheaderBg[2]);
		$pdf->MultiCell($this->page_largeur, 40, '', 0, 'L', true, 1, 0, 0);

		pdf_pagehead($pdf, $outputlangs, $pdf->page_hauteur);

		$pdf->SetTextColor($this->colorheaderText [0], $this->colorheaderText [1], $this->colorheaderText [2]);

		$posy=$this->marge_haute;
		$posx=$this->page_largeur-$this->marge_droite-55;

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
				$height=pdf_getHeightForLogo($logo);
				$width_logo=pdf_getWidthForLogo($logo);
				if ($width_logo>0) {
					$posx=$this->page_largeur-$this->marge_droite-$width_logo;
				}else {
					$posx=$this->page_largeur-$this->marge_droite-55;
				}
				$pdf->Image($logo, $posx, $posy, 0, $height);
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B',$this->default_font_size - 2);
				$posx=$this->page_largeur/2;
				$posy=$this->marge_haute;
				$pdf->SetXY($posx, $posy);
				$pdf->MultiCell(100, 6, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'R');
				$posy = $pdf->GetY();
				$pdf->SetXY($posx, $posy+2);
				$pdf->MultiCell(100, 6, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'R');
			}
		}
		else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}
		// Other Logo
		if ($conf->multicompany->enabled && !empty($conf->global->AGF_MULTICOMPANY_MULTILOGO)) {
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
				if (is_readable($otherlogo) && $otherlogo!=$logo)
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
		// Affichage du logo commanditaire (optionnel)
		if ($conf->global->AGF_USE_LOGO_CLIENT) {
			$staticsoc = new Societe($this->db);
			$staticsoc->fetch($agf->socid);
			$dir = $conf->societe->multidir_output [$staticsoc->entity] . '/' . $staticsoc->id . '/logos/';
			if (! empty($staticsoc->logo)) {
				$logo_client = $dir . $staticsoc->logo;
				if (file_exists($logo_client) && is_readable($logo_client))
					$pdf->Image($logo_client, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 30, $this->marge_haute, 40);
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
			printRefIntForma($this->db, $outputlangs, $object, $this->default_font_size - 3, $pdf, $posx, $posy, 'L');
			$this->marge_haute+=5;
		}

		/*
		 * Corps de page
		 */
		$posX = $this->marge_gauche;

		// Haut
		$this->marge_top=$this->marge_haute+30;
		$posY = $this->marge_top+5;
		$pdf->SetDrawColor($this->colorLine [0], $this->colorLine [1], $this->colorLine [2]);
		$pdf->Line($this->marge_gauche, $this->marge_top, $this->page_largeur - $this->marge_droite, $this->marge_top);
		// Titre
		$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B,I', $this->default_font_size+8);
		$pdf->SetTextColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
		$pdf->SetXY($posX, $posY);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres41');
		$pdf->Cell(0, 6, $outputlangs->convToOutputCharset($this->str), 0, 2, "C", 0);
		$posY += 10;
		// Bas
		$pdf->Line($this->marge_gauche, $this->marge_top+20, $this->page_largeur - $this->marge_gauche, $this->marge_top+20);

		// Intro
		$posY = $pdf->GetY() + 10;
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $this->default_font_size-3);
		$pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres2') . ' « ' . $mysoc->name . ' », ' . $outputlangs->transnoentities('AgfPDFFichePres3') . ' ';
		$this->str .= $mysoc->address . ' ';
		$this->str .= $mysoc->zip . ' ' . $mysoc->town;
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres4') . ' ' . $conf->global->AGF_ORGANISME_REPRESENTANT . ",\n";
		$this->str .= $outputlangs->transnoentities('AgfPDFFichePres5');
		$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'C');
		$hauteur = dol_nboflines_bis($this->str, 50) * 2;
		$posY += $hauteur + 2;
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
