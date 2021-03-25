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
dol_include_once('/agefodd/class/agefodd_stagiaire_certif.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
class pdf_certificateA4 extends ModelePDFAgefodd
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
		$this->name = "attestation";
		$this->description = $langs->trans('AgfCertificate');

		// Dimension page pour format A4 en paysage
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['height'];
		$this->page_hauteur = $formatarray['width'];
		$this->format = array(
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
			if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->AGF_ADD_PDF_BACKGROUND_L)) {
				$pagecount = $pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_L);
				$tplidx = $pdf->importPage(1);
			}

			// Récuperation des objectifs pedagogique de la formation
			$agf_op = new Formation($this->db);
			$result2 = $agf_op->fetch_objpeda_per_formation($agf->fk_formation_catalogue);

			// Récupération de la duree de la formation
			$agf_duree = new Formation($this->db);
			$result = $agf_duree->fetch($agf->fk_formation_catalogue);

			// Recuperation des stagiaires participant à la formation
			$agf2 = new Agefodd_session_stagiaire($this->db);
			$result = $agf2->fetch_stagiaire_per_session($id, $socid);

			// get trainer
			$agf_trainer = new Agefodd_session_formateur($this->db);
			$agf_trainer->fetch_formateur_per_session($id);

			if ($result) {
				for($i = 0; $i < count($agf2->lines); $i ++) {
					if ($agf2->lines[$i]->status_in_session == 3) {

						$agf_certif = new Agefodd_stagiaire_certif($this->db);
						$agf_certif->fetch(0, $agf2->lines[$i]->traineeid, $id, $agf2->lines[$i]->stagerowid);
						if (! empty($agf_certif->id)) {
							// New page
							$pdf->AddPage();
							if (! empty($tplidx))
								$pdf->useTemplate($tplidx);

							$pagenb ++;
							$this->_pagehead($pdf, $agf, 1, $outputlangs);
							$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
							$pdf->MultiCell(0, 3, '', 0, 'J'); // Set interline to 3
							if (empty($tplidx)) {
								// On met en place le cadre
								$pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
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
							$width_logo = pdf_getWidthForLogo($logo);
							// Logo en haut à gauche
							if ($this->emetteur->logo) {
								if (is_readable($logo))
									$pdf->Image($logo, $this->marge_gauche + 3, $this->marge_haute + 3, 40);
							}

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

							$newY = $this->marge_haute + 30;
							$pdf->SetXY($this->marge_gauche + 1, $newY);
							$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
							$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 23);
							$pdf->MultiCell(0, 0, $outputlangs->transnoentities('AgfPDFCertificate10'), 0, 'C');
							// $w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false

							$newY = $pdf->GetY();
							$text = 'N°' . $agf_certif->certif_code . '/' . $agf_certif->certif_label;
							$pdf->SetXY($this->marge_gauche + 1, $newY);
							$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
							$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 10);
							$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($text), 0, 'C');

							$newY = $pdf->GetY() + 5;
							$text = $mysoc->name . ' - ' . $mysoc->address . ' - ' . $mysoc->zip . ' ' . $mysoc->town;
							$pdf->SetXY($this->marge_gauche + 1, $newY);
							$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
							$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($text), 0, 'C');

							$newY = $pdf->GetY();
							$text = $outputlangs->transnoentities('AgfPDFCertificate11');
							$pdf->SetXY($this->marge_gauche + 1, $newY);
							$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
							$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($text), 0, 'C');

							$newY = $pdf->GetY() + 5;
							$contact_static = new Contact($this->db);
							$contact_static->civility_id = $agf2->lines[$i]->civilite;
							$text = ucfirst(strtolower($contact_static->getCivilityLabel())) . ' ' . $agf2->lines[$i]->prenom . ' ' . $agf2->lines[$i]->nom;
							$pdf->SetXY($this->marge_gauche + 1, $newY);
							$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 13);
							$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($text), 0, 'C');

							$newY = $pdf->GetY();
							$text = $outputlangs->transnoentities('AgfPDFCertificate12') . ' ' . $agf2->lines[$i]->socname;
							$pdf->SetXY($this->marge_gauche + 1, $newY);
							$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
							$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($text), 0, 'C');

							$newY = $pdf->GetY() + 5;
							$text = $outputlangs->transnoentities('AgfPDFCertificate13');
							$pdf->SetXY($this->marge_gauche + 1, $newY);
							$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
							$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($text), 0, 'C');

							$newY = $pdf->GetY() + 5;
							$text = $agf->intitule_custo;
							$pdf->SetXY($this->marge_gauche + 1, $newY);
							$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 13);
							$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($text), 0, 'C');

							$newY = $pdf->GetY() + 5;
							$text = $outputlangs->transnoentities('AgfPDFCertificate14') . ' ' . dol_print_date($agf->datef, 'daytext', 'tzserver', $outputlangs);
							$pdf->SetXY($this->marge_gauche + 1, $newY);
							$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
							$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($text), 0, 'C');

							if (! empty($agf_certif->mark)) {
								$newY = $pdf->GetY() + 5;
								$text = $outputlangs->transnoentities('AgfPDFCertificate15') . ' ' . $agf_certif->mark;
								$pdf->SetXY($this->marge_gauche + 1, $newY);
								$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
								$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($text), 0, 'C');
							}

							$newY = $pdf->GetY() + 5;
							$text = $outputlangs->transnoentities('AgfPDFCertificate16') . ' ' . dol_print_date($agf_certif->certif_dt_end, 'daytext', 'tzserver', $outputlangs);
							$pdf->SetXY($this->marge_gauche + 1, $newY);
							$pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', 12);
							$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($text), 0, 'C');

							if (is_array($agf_trainer->lines) && count($agf_trainer->lines) > 0) {
								$text = $outputlangs->transnoentities('AgfPDFCertificate17');
								foreach ( $agf_trainer->lines as $trainer ) {
									$text .= ' ' . $trainer->lastname . ' ' . $trainer->firstname;
								}
							}
							$newY = $pdf->GetY() + 5;
							$pdf->SetXY($this->page_largeur - $this->marge_gauche - $this->marge_droite - 85, $newY);
							$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 12);
							$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($text), 0, 'L');
							$newY = $pdf->GetY();
							// Incrustation image tampon
							if ($conf->global->AGF_INFO_TAMPON) {
								$dir = $conf->agefodd->dir_output . '/images/';
								$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
								if (file_exists($img_tampon))
									$pdf->Image($img_tampon, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 85, $newY + 1, 50);
							}

							// Pied de page $pdf->SetFont(pdf_getPDFFont($outputlangs),'', 10);
							$this->_pagefoot($pdf, $agf, $outputlangs);
							// FPDI::AliasNbPages() is undefined method into Dolibarr 3.5
							if (method_exists($pdf, 'AliasNbPages')) {
								$pdf->AliasNbPages();
							}

							// Mise en place du copyright
							$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
							$this->str = $outputlangs->transnoentities('copyright ' . date("Y") . ' - ' . $mysoc->name);
							$this->width = $pdf->GetStringWidth($this->str);
							// alignement du bord droit du container avec le haut de la page
							$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->width;
							$baseline_angle = (M_PI / 2); // angle droit
							$baseline_x = $this->page_largeur - $this->marge - gauche - 12;
							$baseline_y = $baseline_ecart + 30;
							$baseline_width = $this->width;
						}
					} else {
						$outputlangs->load('companies');
						$this->str = $outputlangs->transnoentities('AgfOnlyPresentTraineeGetAttestation', $outputlangs->transnoentities('PL_NONE'));
						setEventMessage($this->str, 'warnings');
					}
				}
			}
			$pdf->Close();
			$pdf->Output($file, 'F');
			if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

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

		$text = $outputlangs->transnoentities('AgfPDFCertificate18') . ':' . $mysoc->phone . ' ' . $outputlangs->transnoentities('AgfPDFCertificate19') . ':' . $mysoc->fax;
		$text .= ' - ' . $mysoc->email . ' - ' . $mysoc->url;
		$pdf->SetXY($this->marge_gauche + 1, $this->page_hauteur - $this->marge_basse - 15);
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
		$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
		$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($text), 0, 'C');

		$text = $outputlangs->transnoentities('AgfPDFCertificate23') . ':';
		if (! empty($conf->global->AGF_ORGANISME_NUM)) {
			$text .= $outputlangs->transnoentities('AgfPDFCertificate20', $conf->global->AGF_ORGANISME_NUM);
		}
		if (! empty($conf->global->AGF_ORGANISME_PREF)) {
			$text .= ' ' . $outputlangs->transnoentities('AgfPDFCertificate21', $conf->global->AGF_ORGANISME_PREF);
		}
		$pdf->SetXY($this->marge_gauche + 1, $pdf->GetY());
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
		$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
		$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($text), 0, 'C');

		$text = $outputlangs->transnoentities('AgfPDFCertificate22');
		if (! empty($mysoc->forme_juridique_code)) {
			$text .= ' - ' . getFormeJuridiqueLabel($mysoc->forme_juridique_code);
		}
		if (! empty($mysoc->idprof2)) {
			$text .= ' - ' . $outputlangs->transnoentities(AgfPDFCertificate24) . ' ' . $mysoc->idprof2;
		}
		if (! empty($mysoc->idprof3)) {
			$text .= ' - ' . $outputlangs->transnoentities(AgfPDFCertificate25) . ' ' . $mysoc->idprof3;
		}
		$pdf->SetXY($this->marge_gauche + 1, $pdf->GetY());
		$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
		$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
		$pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($text), 0, 'C');
	}
}
