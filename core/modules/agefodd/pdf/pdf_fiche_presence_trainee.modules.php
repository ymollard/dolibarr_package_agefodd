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
dol_include_once('/agefodd/core/modules/agefodd/pdf/pdf_fiche_presence.modules.php');
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
class pdf_fiche_presence_trainee extends pdf_fiche_presence
{
	var $emetteur; // Objet societe qui emet

	// Definition des couleurs utilisées de façon globales dans le document (charte)
	protected $colorfooter;
	protected $colortext;
	protected $colorhead;
	protected $colorheaderBg;
	protected $colorheaderText;
	protected $colorLine;
	protected $formateurs;
	protected $nbFormateurs;
	protected $dates;
	protected $dao;
	protected $height_for_footer;
	/** @var TCPDF $pdf */
	protected $pdf;

	/**
	 * \brief Constructor
	 * \param db Database handler
	 */
	function __construct($db) {

		parent::__construct($db);

		$this->name = "fiche_presence_trainee";

		$this->height_for_footer = 20;

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

		$this->outputlangs = $outputlangs;

		$default_font_size = pdf_getPDFFontSize($this->outputlangs);

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
			if (!empty($conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER))
				$this->height_for_footer = $conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER;

			$realFooterHeight = $this->getRealHeightLine('foot');
			$this->height_for_footer = max($this->height_for_footer, $realFooterHeight);
			$this->pdf->SetAutoPageBreak(1, $this->height_for_footer);

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
				// récupération des participants
				$agfsta = new Agefodd_session_stagiaire($this->db);
				$resql = $agfsta->fetch_stagiaire_per_session($this->ref_object->id);
				$nbsta = count($agfsta->lines);

				// récupération des formateurs
				$this->formateurs = new Agefodd_session_formateur($this->db);
				$this->nbFormateurs = $this->formateurs->fetch_formateur_per_session($this->ref_object->id);

				// récupération des dates
				$this->dates = new Agefodd_sesscalendar($this->db);
				$resql = $this->dates->fetch_all($this->ref_object->id);

				// spécifique multicompany
				if (!empty($conf->multicompany->enabled)) {
					dol_include_once('/multicompany/class/dao_multicompany.class.php');
					$this->dao = new DaoMulticompany($this->db);
					$this->dao->getEntities();
				}

				if ($nbsta > 0) {
					// $blocsta=0;
					foreach ( $agfsta->lines as $line ) {
						if ($line->status_in_session !=6){
							$this->line = $line;
							$this->_pagebody();
						}
					}
				} else {
					$this->_pagefoot();
					$this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
					$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);

					$this->pdf->AddPage();
					list($posX, $posY) = $this->_pagehead($this->pdf->ref_object);

					/**
					 * *** Bloc formation ****
					 */
					list($posX, $posY) = $this->printSessionSummary($posX, $posY);

					$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
					$this->pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3
					$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);

					$posY = $this->marge_haute;
					$posX = $this->marge_gauche;

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
		list($posX, $posY) = $this->_pagehead($this->pdf->ref_object);

		/**
		 * *** Bloc formation ****
		 */
		list($posX, $posY) = $this->printSessionSummary($posX, $posY);
		list($posX, $posY) = $this->printDateBlockHeader($posX, $posY);
		list($posX, $posY) = $this->printDateBlockLines($posX, $posY);

		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);

		// Cachet et signature
		$posY += 2;
		$posX -= 2;
		$this->pdf->SetXY($posX, $posY);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres20');
		$this->pdf->Cell(50, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);

		$this->pdf->SetXY($posX + 55, $posY);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres21').dol_print_date($this->ref_object->datef);
		$this->pdf->Cell(20, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);

		$this->pdf->SetXY($posX + 92, $posY);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres22');
		$this->pdf->Cell(50, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);

		$posY = $this->pdf->GetY();

		// Incrustation image tampon
		if ($conf->global->AGF_INFO_TAMPON) {
			$dir = $conf->agefodd->dir_output . '/images/';
			$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
			if (file_exists($img_tampon))
				$this->pdf->Image($img_tampon, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 50, $posY, 50);
		}

		// Pied de page
		//$this->_pagefoot();
		// FPDI::AliasNbPages() is undefined method into Dolibarr 3.5
		if (method_exists($this->pdf, 'AliasNbPages')) {
			$this->pdf->AliasNbPages();
		}
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

	/**
	 * @param $posX
	 * @param $posY
	 * @return array
	 */
	function printDateBlockHeader($posX, $posY)
	{
		global $conf;

		$this->larg_col1 = 25;
		$this->larg_col2 = 25;
		$this->larg_col3 = 55;
		$this->larg_col4 = 125;

		if ($this->nbFormateurs < 5)
		{
			$largReste = $this->espaceH_dispo - $this->larg_col1 - $this->larg_col2;
			$maxCellWidth = $largReste / ($this->nbFormateurs + 1);
			if ($maxCellWidth > $this->larg_col3)
			{
				$this->larg_col3 = $maxCellWidth;
			}
			$this->larg_col4 = $largReste - $this->larg_col3;
		}

		$this->haut_col2 = 0;
		$this->haut_col4 = 0;
		$this->h_ligne = 7;
		$this->haut_cadre = 0;

		$this->pdf->SetXY($posX - 2, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'BI', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres26');
		$this->pdf->Cell(0, 4, $this->outputlangs->convToOutputCharset($this->str), 0, 2, "L", 0);
		$posY += 5;

		// Date
		$this->pdf->SetXY($posX-2, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres24');
		$this->pdf->Cell($this->larg_col1+2, $this->h_ligne + 8, $this->outputlangs->convToOutputCharset($this->str), 'TLR', 2, "C", 0);
		// Horaire
		$this->pdf->SetXY($posX + $this->larg_col1, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres25');
		$this->pdf->Cell($this->larg_col2, $this->h_ligne + 8, $this->outputlangs->convToOutputCharset($this->str), 'TLR', 2, "C", 0);

		// Trainee
		$posY_trainee = $posY;
		$this->pdf->SetXY($posX + $this->larg_col1 + $this->larg_col2, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 7);
		$this->str = $this->line->nom . ' ' . $this->line->prenom . ' - ' . dol_trunc($this->line->socname, 27);

		if (! empty($this->line->poste)) {
			$this->str .= "\n".' (' . $this->line->poste . ')';
		}
		if (is_object($this->dao) && $conf->global->AGF_ADD_ENTITYNAME_FICHEPRES) {
			$c = new Societe($this->db);
			$c->fetch($this->line->socid);

			$entityName = '';
			if (count($this->dao->entities)>0){
				foreach ($this->dao->entities as $e){
					if ($e->id == $c->entity){
						$entityName = $e->label;
						$this->str .= "\n". $this->outputlangs->trans('Entity').' : '. $e->label;
						break;
					}
				}
			}
		}

		$this->pdf->MultiCell($this->larg_col3, $this->h_ligne, $this->outputlangs->convToOutputCharset($this->str), 'T', 'C', false, 1, $posX + $this->larg_col1 + $this->larg_col2, $posY, true, 1, false, true, $this->h_ligne, 'T', true);

		$posY = $this->pdf->GetY() - 1;

		// Signature
		$this->pdf->SetXY($posX + $this->larg_col1 + $this->larg_col2, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres18');
		$this->pdf->Cell($this->larg_col3, 5, $this->outputlangs->convToOutputCharset($this->str), 'R', 2, "C", 0);
		$this->pdf->SetXY($posX + $this->larg_col1 + $this->larg_col2, $posY + 3);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'I', 5);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres19');
		$this->pdf->Cell($this->larg_col3, 5, $this->outputlangs->convToOutputCharset($this->str), 'R', 2, "C", 0);
		$posY = $this->pdf->GetY();

		// Trainer
		$this->pdf->SetXY($posX + $this->larg_col1 + $this->larg_col2 + $this->larg_col3, $posY_trainee);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 9);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres12'); // ."\n".$this->outputlangs->transnoentities('AgfPDFFichePres13');
		$this->pdf->MultiCell(0, 2, $this->outputlangs->convToOutputCharset($this->str), 'TLR', "C");
		$posY_trainer = $this->pdf->GetY();
		$this->pdf->SetXY($posX + $this->larg_col1 + $this->larg_col2 + $this->larg_col3, $posY_trainer);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'I', 5);
		$this->str = $this->outputlangs->transnoentities('AgfPDFFichePres13'); // ."\n".$this->outputlangs->transnoentities('AgfPDFFichePres13');
		$this->pdf->MultiCell(0, 2, $this->outputlangs->convToOutputCharset($this->str), 'BLR', "C");

		$posY_trainer = $this->pdf->GetY();
		$posX_trainer = $posX + $this->larg_col1 + $this->larg_col2 + $this->larg_col3;
		if ($this->nbFormateurs > 0)
		{
			$nbForm = $this->nbFormateurs;
			if ($nbForm > 4) $nbForm = 4;
			$i = 1;
			foreach ( $this->formateurs->lines as $trainer_line ) {
				$this->pdf->SetXY($posX_trainer, $posY_trainer);
				$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), 'B', 7);
				$this->str = strtoupper($trainer_line->lastname) . "\n" . ucfirst($trainer_line->firstname);
				$this->pdf->MultiCell(0, 3, $this->outputlangs->convToOutputCharset($this->str), 'LR', "L", false, 1, $posX_trainer, $posY_trainer);
				// $w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y=''

				$posY = $this->pdf->GetY();
				$posX_trainer += $this->larg_col4/$nbForm;
				$i++;

				if ($i > $nbForm) break;
			}
		}

		return array($posX, $posY);
	}

	function printDateBlockLines($posX, $posY)
	{
		$nbpage=0;
		foreach ( $this->dates->lines as $linedate ) {
//			$nbpage++;

			$this->pdf->startTransaction();

			list($posX, $posY) = $this->printDateLine($posX, $posY, $linedate);

			if ($posY > $this->page_hauteur - $this->height_for_footer) {
				$this->pdf = $this->pdf->rollbackTransaction();
				$this->_pagefoot();
				$this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
				$this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);

				$this->pdf->AddPage();
				list($posX, $posY) = $this->_pagehead($this->pdf->ref_object);

				/**
				 * *** Bloc formation ****
				 */
				list($posX, $posY) = $this->printSessionSummary($posX, $posY);
				list($posX, $posY) = $this->printDateBlockHeader($posX, $posY);
				list($posX, $posY) = $this->printDateLine($posX, $posY, $linedate);
			}
			else
			{
				$this->pdf->commitTransaction();
			}

		}

		return array($posX, $posY);
	}

	/**
	 * @param $posX
	 * @param $posY
	 * @param $linedate
	 * @return array
	 */
	function printDateLine($posX, $posY, $linedate)
	{
		$this->h_ligne = 9;

		// Jour
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 8);
		if ($linedate->date_session) {
			$date = dol_print_date($linedate->date_session, 'daytextshort');
		} else {
			$date = '';
		}
		$this->str = $date;
		$this->pdf->SetXY($posX, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);
		$this->pdf->MultiCell($this->larg_col1, $this->h_ligne, $this->outputlangs->convToOutputCharset($this->str), 0, "C", false, 1, '', '', true, 0, false, false, $this->h_ligne, 'M');

		// horaires
		if ($linedate->heured && $linedate->heuref) {
			$this->str = dol_print_date($linedate->heured, 'hour') . ' - ' . dol_print_date($linedate->heuref, 'hour');
		} else {
			$this->str = '';
		}
		$this->pdf->SetXY($posX + $this->larg_col1, $posY);
		$this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 7);
		$this->pdf->MultiCell($this->larg_col2, $this->h_ligne, $this->outputlangs->convToOutputCharset($this->str), 1, "C", false, 1, '', '', true, 0, false, false, $this->h_ligne, 'M');

		// Cadre pour signature
		$this->pdf->Rect($posX + $this->larg_col1 + $this->larg_col2, $posY, $this->larg_col3, $this->h_ligne);

		$this->pdf->MultiCell(0, $this->h_ligne, " ", 1, "C", false, 1, $this->marge_gauche, $posY);

		$posX_trainer = $posX + $this->larg_col1 + $this->larg_col2 + $this->larg_col3;
		if ($this->nbFormateurs > 0)
		{
			$nbForm = $this->nbFormateurs;
			if ($nbForm > 4) $nbForm = 4;
			$i = 1;

			foreach ( $this->formateurs->lines as $trainer_line ) {
				$this->pdf->SetXY($posX_trainer, $posY);
				$this->pdf->MultiCell(0, $this->h_ligne, " ", 1, "C", false, 1, $posX_trainer, $posY);

				// $this->pdf->Rect($first_trainer_posx, $posY, $posX_trainer, $this->h_ligne);
				$posX_trainer += $this->larg_col4/$nbForm;
				$i++;

				if ($i > $nbForm) break;
			}
		}
		$posY = $this->pdf->GetY();

		return array($posX, $posY);
	}
}
