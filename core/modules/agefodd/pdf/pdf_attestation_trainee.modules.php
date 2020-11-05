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
 * \file agefodd/core/modules/agefodd/pdf/pdf_attestation.modules.php
 * \ingroup agefodd
 * \brief PDF for certificate (attestation)
 */
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
class pdf_attestation_trainee extends ModelePDFAgefodd {
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
		$this->name = $langs->trans('AgfPDFAttestationTrainee');
		$this->description = $langs->trans('AgfPDFAttestationTrainee');

		// Dimension page pour format A4 en paysage
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray ['height'];
		$this->page_hauteur = $formatarray ['width'];
		$this->format = array (
				$this->page_largeur,
				$this->page_hauteur
		);
		$this->marge_gauche = 15;
		$this->marge_droite = 15;
		$this->marge_haute = 10;
		$this->marge_basse = 10;
		$this->unit = 'mm';
		$this->oriantation = 'l';
		$this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
		$this->milieu = $this->espaceH_dispo / 2;

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
	function write_file($agf, $outputlangs, $file, $session_trainee_id) {
		global $user, $langs, $conf, $mysoc;

		if (! is_object($outputlangs))
			$outputlangs = $langs;

		if (! is_object($agf)) {
			$id = $agf;
			$agf = new Agsession($this->db);
			$ret = $agf->fetch($id);
		}

		$agf_session_trainee = new Agefodd_session_stagiaire($this->db);
		$agf_session_trainee->fetch($session_trainee_id);

		$agf_trainee = new Agefodd_stagiaire($this->db);
		$agf_trainee->fetch($agf_session_trainee->fk_stagiaire);

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
			$pdf->SetSubject($outputlangs->transnoentities("AgfPDFAttestationTrainee"));
			$pdf->SetCreator("Dolibarr " . DOL_VERSION . ' (Agefodd module)');
			$pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($agf->ref) . " " . $outputlangs->transnoentities("Document"));
			if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION)
				$pdf->SetCompression(false);

			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
			$pdf->SetAutoPageBreak(1, 0);

			// Set path to the background PDF File
			if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->AGF_ADD_PDF_BACKGROUND_L))
			{
				$pagecount = $pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_L);
				$tplidx = $pdf->importPage(1);
			}

			// Récuperation des objectifs pedagogique de la formation
			$agf_op = new Formation($this->db);
			$result2 = $agf_op->fetch_objpeda_per_formation($agf->fk_formation_catalogue);

			// Récupération de la duree de la formation
			$agf_duree = new Formation($this->db);
			$result = $agf_duree->fetch($agf->fk_formation_catalogue);

			// New page
			$pdf->AddPage();
			if (! empty($tplidx)) $pdf->useTemplate($tplidx);

			$pagenb ++;
			$this->_pagehead($pdf, $agf, 1, $outputlangs);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
			$pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3

			if (empty($tplidx)) {
				// On met en place le cadre
				$pdf->SetDrawColor($this->colorLine [0], $this->colorLine [1], $this->colorLine [2]);
				$ep_line1 = 1;
				$pdf->SetLineWidth($ep_line1);
				// Haut
				$pdf->Line($this->marge_gauche, $this->marge_haute, $this->page_largeur - $this->marge_droite, $this->marge_haute);
				// Droite
				$pdf->Line($this->page_largeur - $this->marge_droite, $this->marge_haute, $this->page_largeur - $this->marge_droite, $this->page_hauteur - $this->marge_basse);
				// Bas
				$pdf->Line($this->marge_gauche, $this->page_hauteur - $this->marge_basse, $this->page_largeur - $this->marge_gauche, $this->page_hauteur - $this->marge_basse);
				// Gauche
				$pdf->Line($this->marge_gauche, $this->marge_haute, $this->marge_gauche, $this->page_hauteur - $this->marge_basse);

				$pdf->SetLineWidth(0.3);
				$decallage = 1.2;
				// Haut
				$pdf->Line($this->marge_gauche + $decallage, $this->marge_haute + $decallage, $this->page_largeur - $this->marge_droite - $decallage, $this->marge_haute + $decallage);
				// Droite
				$pdf->Line($this->page_largeur - $this->marge_droite - $decallage, $this->marge_haute + $decallage, $this->page_largeur - $this->marge_droite - $decallage, $this->page_hauteur - $this->marge_basse - $decallage);
				// Bas
				$pdf->Line($this->marge_gauche + $decallage, $this->page_hauteur - $this->marge_basse - $decallage, $this->page_largeur - $this->marge_gauche - $decallage, $this->page_hauteur - $this->marge_basse - $decallage);
				// Gauche
				$pdf->Line($this->marge_gauche + $decallage, $this->marge_haute + $decallage, $this->marge_gauche + $decallage, $this->page_hauteur - $this->marge_basse - $decallage);
			}
			// Logo en haut à gauche
			$logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
			// Logo en haut à gauche
			if ($this->emetteur->logo) {
				if (is_readable($logo))
					$pdf->Image($logo, $this->marge_gauche + 3, $this->marge_haute + 3, 40);
			}

			// Affichage du logo commanditaire (optionnel)
			if ($conf->global->AGF_USE_LOGO_CLIENT) {
				$staticsoc = new Societe($this->db);
				$staticsoc->fetch($agf->socid);
				$dir = $conf->societe->multidir_output [$staticsoc->entity] . '/' . $staticsoc->id . '/logos/';
				if (! empty($staticsoc->logo)) {
					$logo_client = $dir . $staticsoc->logo;
					if (file_exists($logo_client) && is_readable($logo_client))
						$pdf->Image($logo_client, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 30, $this->marge_haute + 10, 40);
				}
			}

			$newY = $this->marge_haute + 30;
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$pdf->SetTextColor($this->colorhead [0], $this->colorhead [1], $this->colorhead [2]);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 20);
			$pdf->Cell(0, 0, $outputlangs->transnoentities('AgfPDFAttestation1'), 0, 0, 'C', 0);

			$newY = $newY + 10;
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$pdf->SetTextColor($this->colortext [0], $this->colortext [1], $this->colortext [2]);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);

			$contact_static = new Contact($this->db);
			$contact_static->civility_id = $agf_trainee->civilite;

			$this->str1 = $outputlangs->transnoentities('AgfPDFAttestation2') . " " . ucfirst(strtolower($contact_static->getCivilityLabel())) . ' ';
			$this->width1 = $pdf->GetStringWidth($this->str1);

			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 16);
			$this->str2 = $outputlangs->transnoentities($agf_trainee->prenom . ' ' . $agf_trainee->nom);
			$this->width2 = $pdf->GetStringWidth($this->str2);

			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
			$this->debut_cell = ($this->marge_gauche + 1) + ($this->milieu - (($this->width1 + $this->width2) / 2));
			$newY = $newY + 10;
			$pdf->SetXY($this->debut_cell, $newY);
			$pdf->Cell($this->width1, 0, $this->str1, 0, 0, 'C', 0);
			$pdf->SetXY($pdf->GetX(), $newY - 1.5);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 16);
			$pdf->Cell($this->width2, - 3, $this->str2, 0, 0, 'C', 0);

			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
			$newY = $newY + 6;
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$this->str = ' ' . $outputlangs->transnoentities('AgfPDFAttestation3');
			$pdf->Cell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C', 0);

			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 18);
			$newY = $newY + 10;
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$pdf->Cell(0, 0, $outputlangs->transnoentities('« ' . $agf->intitule_custo . ' »'), 0, 0, 'C', 0);

			$this->str = $outputlangs->transnoentities('AgfPDFAttestation4') . " ";
			$this->str .= $agf->libSessionDate();

			if (! empty($conf->global->AGF_USE_REAL_HOURS)) {
				dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');
				$agfssh = new Agefoddsessionstagiaireheures($this->db);
				$duree_session=$agfssh->heures_stagiaire($agf->id, $agf_session_trainee->fk_stagiaire);
			} else {
				$duree_session=$agf->duree_session;
			}

			$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFAttestation5') . " " . $duree_session . $outputlangs->transnoentities('AgfPDFAttestation6');
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
			$newY = $newY + 10;
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$pdf->Cell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C', 0);

			$newY = $pdf->GetY();
			// Bloc objectifs pedagogiques
			if (count($agf_op->lines) > 0) {

				$this->str = $outputlangs->transnoentities('AgfPDFAttestation7');
				$newY = $newY + 10;
				$pdf->SetXY($this->marge_gauche + 1, $newY);
				$pdf->Cell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, 1, 'C', 0);

				$pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', 12);
				$hauteur = 0;
				for($y = 0; $y < count($agf_op->lines); $y ++) {
					$newY = $pdf->GetY() + 1;
					$pdf->SetXY($this->marge_gauche + 62, $pdf->GetY());
					$width = 160;
					$StringWidth = $pdf->GetStringWidth($agf_op->lines [$y]->intitule);
					if ($StringWidth > $width)
						$nblines = ceil($StringWidth / $width);
					else
						$nblines = 1;
					$hauteur = $nblines * 5;
					$pdf->Cell(10, 5, $agf_op->lines [$y]->priorite . '. ', 0, 0, 'R', 0);
					$pdf->MultiCell($width, 0, $outputlangs->transnoentities($agf_op->lines [$y]->intitule), 0, 'L', 0);
				}
			}

			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
			$newY = $newY + 20;
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$this->str = $outputlangs->transnoentities('AgfPDFAttestation8') . " " . $mysoc->name . ",";
			$pdf->Cell(0, 0, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C', 0);

			$newY = $newY + 20;
			$pdf->SetXY($this->marge_gauche + 1, $newY);
			$this->str = $outputlangs->transnoentities('AgfPDFConv20') . " " . $mysoc->town . ", " . $outputlangs->transnoentities('AgfPDFFichePres8');
			$pdf->Cell(80, 0, $outputlangs->convToOutputCharset($this->str), 0, 0, 'R', 0);

			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
			$this->str = date("d/m/Y");
			$this->str = dol_print_date($agf->datef);
			$this->width = $pdf->GetStringWidth($this->str);
			$pdf->Cell($this->width, 0, $this->str, 0, 0, 'L', 0);

			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
			$this->str = $conf->global->AGF_ORGANISME_REPRESENTANT;
			$pdf->Cell(100, 0, $this->str, 0, 0, 'R', 0);

			// Incrustation image tampon
			if ($conf->global->AGF_INFO_TAMPON) {
				$dir = $conf->agefodd->dir_output . '/images/';
				$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
				if (file_exists($img_tampon))
					$pdf->Image($img_tampon, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 85, $newY + 5, 50);
			}

			// Pied de page $pdf->SetFont(pdf_getPDFFont($outputlangs),'', 10);
			$this->_pagefoot($pdf, $agf, $outputlangs);

			// Mise en place du copyright
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
			$this->str = $outputlangs->transnoentities('copyright ' . date("Y") . ' - ' . $mysoc->name);
			$this->width = $pdf->GetStringWidth($this->str);
			// alignement du bord droit du container avec le haut de la page
			$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->width;
			$baseline_angle = (M_PI / 2); // angle droit
			$baseline_x = $this->page_largeur - $this->marge_gauche - 12;
			$baseline_y = $baseline_ecart + 30;
			$baseline_width = $this->width;

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

		if (empty($conf->global->AGF_HIDE_DOC_FOOTER)) {
			$this->str = $mysoc->name;
			$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFFoot12') . ' ';
			if (! empty($conf->global->AGF_ORGANISME_PREF)) {
				$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFFoot10') . ' ' . $conf->global->AGF_ORGANISME_PREF;
			}
			if (! empty($conf->global->AGF_ORGANISME_NUM)) {
				$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFFoot11',$conf->global->AGF_ORGANISME_NUM);
			}

			$pdf->SetXY($this->marge_gauche + 1, $this->page_hauteur - $this->marge_basse);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', 8);
			$pdf->SetTextColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);
			$pdf->Cell(0, 6, $outputlangs->convToOutputCharset($this->str), 0, 0, 'C', 0);
		}
	}
}
