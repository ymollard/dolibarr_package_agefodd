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
 * \file agefodd/core/modules/agefodd/pdf/pdf_fiche_presence_landscape.module.php
 * \ingroup agefodd
 * \brief PDF for landscape format training attendees session sheet
 */

dol_include_once('/agefodd/core/modules/agefodd/pdf/pdf_fiche_presence_empty.modules.php');

class pdf_fiche_presence_landscape_empty extends pdf_fiche_presence_empty
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
     * Constructor
     *
     * @param DoliDb $db handler
     */
    function __construct($db)
    {
        global $conf, $langs;

        parent::__construct($db);
        $this->name = "fiche_presence_landscape";
        $this->description = $langs->trans('AgfModPDFFichePres');
        $formatarray = pdf_getFormat();
        $this->page_largeur = $formatarray ['height']; // use standard but reverse width and height to get Landscape format
        $this->page_hauteur = $formatarray ['width']; // use standard but reverse width and height to get Landscape format
        $this->format = array (
            $this->page_largeur,
            $this->page_hauteur
        );
        $this->marge_haute = 2;
        $this->marge_gauche = 15;
        $this->marge_droite = 15;
        $this->oriantation = 'l'; // use Landscape format
        $this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
        $this->milieu = $this->espaceH_dispo / 2;
        $this->espaceV_dispo = $this->page_hauteur - ($this->marge_haute + $this->marge_basse);

        $this->formation_widthcol1 = 20;
        $this->formation_widthcol2 = 130;
        $this->formation_widthcol3 = 35;
        $this->formation_widthcol4 = 82;

        $this->trainer_widthcol1 = 55;
        $this->trainer_widthcol2 = 145;

        $this->trainee_widthcol1 = 50;
        $this->trainee_widthcol2 = 45;
        if (empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
            $this->trainer_widthtimeslot = 21;
            $this->trainee_widthtimeslot = 17;
            $this->nbtimeslots = 10;
        } else {
            $this->trainer_widthtimeslot = 23.3;
            $this->trainee_widthtimeslot = 23.9;
            $this->nbtimeslots = 9;
        }

		$this->largeur_date_trainer = 22.1;
		$this->largeur_date_trainee = 18.5;

		$this->predefline=4;
		if ($conf->global->AGF_INFO_TAMPON) {
			$dir = $conf->agefodd->dir_output . '/images/';
			$img_tampon = $dir . $conf->global->AGF_INFO_TAMPON;
			if (file_exists($img_tampon))
				$this->predefline=3;
		}
    }

//    function _pagehead($agf, $dummy, $outputlangs, &$pdf, $lines_array, $noTrainer = 0)
//    {
//        global $conf, $mysoc;
//
//        $outputlangs->load("main");
//
//        // Fill header with background color
//        $this->pdf->SetFillColor($this->colorheaderBg[0], $this->colorheaderBg[1], $this->colorheaderBg[2]);
//        $this->pdf->MultiCell($this->page_largeur, 40, '', 0, 'L', true, 1, 0, 0);
//
//        pdf_pagehead($this->pdf, $outputlangs, $this->pdf->page_hauteur);
//
//        $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
//        $this->pdf->SetTextColor($this->colorheaderText[0], $this->colorheaderText[1], $this->colorheaderText[2]);
//
//        $posY = $this->marge_haute;
//        $posX = $this->page_largeur - $this->marge_droite - 55;
//
//        // Logo
//        $logo = $conf->mycompany->dir_output . '/logos/' . $this->emetteur->logo;
//        if ($this->emetteur->logo) {
//            if (is_readable($logo)) {
//                $height = pdf_getHeightForLogo($logo);
//                $width_logo = pdf_getWidthForLogo($logo);
//                if ($width_logo > 0) {
//                    $posX = $this->page_largeur - $this->marge_droite - $width_logo;
//                } else {
//                    $posX = $this->page_largeur - $this->marge_droite - 55;
//                }
//                $this->pdf->Image($logo, $posX, $posY, 0, $height);
//            } else {
//                $this->pdf->SetTextColor(200, 0, 0);
//                $this->pdf->SetFont('', 'B', $this->default_font_size - 2);
//                $this->pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
//                $this->pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
//            }
//        } else {
//            $text = $this->emetteur->name;
//            $this->pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
//        }
//        $posY = $this->marge_haute;
//        $posX = $this->marge_gauche;
//
//        $hautcadre = 30;
//        $this->pdf->SetXY($posX, $posY);
//        $this->pdf->MultiCell(70, $hautcadre, "", 0, 'R', 1);
//
//        // Show sender name
//        $this->pdf->SetXY($posX, $posY);
//        $this->pdf->SetFont('', 'B', $this->default_font_size - 2);
//        $this->pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
//        $posY = $this->pdf->GetY();
//
//        // Show sender information
//        $this->pdf->SetXY($posX, $posY);
//        $this->pdf->SetFont('', '', $this->default_font_size - 3);
//        $this->pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->address), 0, 'L');
//        $posY = $this->pdf->GetY();
//        $this->pdf->SetXY($posX, $posY);
//        $this->pdf->SetFont('', '', $this->default_font_size - 3);
//        $this->pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->zip . ' ' . $this->emetteur->town), 0, 'L');
//        $posY = $this->pdf->GetY();
//        $this->pdf->SetXY($posX, $posY);
//        $this->pdf->SetFont('', '', $this->default_font_size - 3);
//        $this->pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->phone), 0, 'L');
//        $posY = $this->pdf->GetY();
//        $this->pdf->SetXY($posX, $posY);
//        $this->pdf->SetFont('', '', $this->default_font_size - 3);
//        $this->pdf->MultiCell(70, 4, $outputlangs->convToOutputCharset($this->emetteur->email), 0, 'L');
//        $posY = $this->pdf->GetY();
//
//        printRefIntForma($this->db, $outputlangs, $agf, $this->default_font_size - 3, $this->pdf, $posX, $posY, 'L');
//
//        // Affichage du logo commanditaire (optionnel)
//        if ($conf->global->AGF_USE_LOGO_CLIENT) {
//            $staticsoc = new Societe($this->db);
//            $staticsoc->fetch($agf->socid);
//            $dir = $conf->societe->multidir_output[$staticsoc->entity] . '/' . $staticsoc->id . '/logos/';
//            if (!empty($staticsoc->logo)) {
//                $logo_client = $dir . $staticsoc->logo;
//                if (file_exists($logo_client) && is_readable($logo_client))
//                    $this->pdf->Image($logo_client, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 30, $this->marge_haute, 40);
//            }
//        }
//
//        $posY = $this->pdf->GetY() + 10;
//        if ($conf->global->AGF_PRINT_INTERNAL_REF_ON_PDF)
//            $posY -= 4;
//
//        $this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
//        $this->pdf->Line($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);
//
//        $this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
//
//        // Mise en page de la baseline
//        $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 18);
//        $str = $outputlangs->transnoentities($mysoc->url);
//        $width = $this->pdf->GetStringWidth($str);
//
//        // alignement du bord droit du container avec le haut de la page
//        $baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $width;
//        $baseline_x = 8;
//        $baseline_y = $this->espaceV_dispo - $baseline_ecart + 30;
//        $this->pdf->SetXY($baseline_x, $baseline_y);
//
//        /*
//         * Corps de page
//         */
//        $posX = $this->marge_gauche;
//        $posY = $posY + $this->header_vertical_margin;
//
//        // Titre
//        $this->pdf->SetXY($posX, $posY);
//        $this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 18);
//        $this->pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
//        $str = $outputlangs->transnoentities('AgfPDFFichePres1');
//        $this->pdf->Cell(0, 6, $outputlangs->convToOutputCharset($str), 0, 2, "C", 0);
//        $posY += 6 + 4;
//
//        // Intro
//        $this->pdf->SetXY($posX, $posY);
//        $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
//        $this->pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
//        $str = $outputlangs->transnoentities('AgfPDFFichePres2') . ' « ' . $mysoc->name . ' »,' . $outputlangs->transnoentities('AgfPDFFichePres3') . ' ';
//        $str .= str_replace(array('<br>', '<br />', "\n", "\r"), array(' ', ' ', ' ', ' '), $mysoc->address) . ' ';
//        $str .= $mysoc->zip . ' ' . $mysoc->town;
//        $str .= $outputlangs->transnoentities('AgfPDFFichePres4') . ' ' . $conf->global->AGF_ORGANISME_REPRESENTANT . ",\n";
//        $str .= $outputlangs->transnoentities('AgfPDFFichePres5');
//        $this->pdf->MultiCell(0, 0, $outputlangs->convToOutputCharset($str), 0, 'C');
//        $posY = $this->pdf->GetY() + 1;
//
//        /**
//         * *** Bloc formation ****
//         */
//        $this->pdf->SetXY($posX, $posY);
//        $this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'BI', 9);
//        $str = $outputlangs->transnoentities('AgfPDFFichePres23');
//        $this->pdf->Cell(0, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
//        $posY += 4;
//
//        $cadre_tableau = array(
//            $posX,
//            $posY
//        );
//
//        $posX += 2;
//        $posY += 2;
//        $posYintitule = $posY;
//
//        $haut_col2 = 0;
//        $haut_col4 = 0;
//
//        // Intitulé
//        $this->pdf->SetXY($posX, $posY);
//        $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
//        $str = $outputlangs->transnoentities('AgfPDFFichePres6');
//        $this->pdf->Cell($this->formation_widthcol1, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
//
//        $this->pdf->SetXY($posX + $this->formation_widthcol1, $posY);
//        $this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 9);
//
//        if (empty($agf->intitule_custo)) {
//            $str = '« ' . $agf->formintitule . ' »';
//        } else {
//            $str = '« ' . $agf->intitule_custo . ' »';
//        }
//        $this->pdf->MultiCell($this->formation_widthcol2, 4, $outputlangs->convToOutputCharset($str), 0, 'L');
//
//        $posY = $this->pdf->GetY() + 2;
//
//        // Période
//        $this->pdf->SetXY($posX, $posY);
//        $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
//        $str = $outputlangs->transnoentities('AgfPDFFichePres7');
//        $this->pdf->Cell($this->formation_widthcol1, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
//
//        $str = $agf->libSessionDate('daytext');
//
//        $str .= ' (' . $agf->duree_session . ' h)';
//
//        $this->pdf->SetXY($posX + $this->formation_widthcol1, $posY);
//        $this->pdf->MultiCell($this->formation_widthcol2, 4, $outputlangs->convToOutputCharset($str), 0, 'L');
//        $hauteur = dol_nboflines_bis($str, 50) * 4;
//        $haut_col2 += $hauteur + 2;
//
//        // Lieu
//        $this->pdf->SetXY($posX + $this->formation_widthcol1 + $this->formation_widthcol2, $posYintitule);
//        $str = $outputlangs->transnoentities('AgfPDFFichePres11');
//        $this->pdf->Cell($this->formation_widthcol3, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
//
//        $agf_place = new Agefodd_place($this->db);
//        $resql = $agf_place->fetch($agf->placeid);
//
//        $this->pdf->SetXY($posX + $this->formation_widthcol1 + $this->formation_widthcol2 + $this->formation_widthcol3, $posYintitule);
//        $str = $agf_place->ref_interne . "\n" . $agf_place->adresse . "\n" . $agf_place->cp . " " . $agf_place->ville;
//        $this->pdf->MultiCell($this->formation_widthcol4, 4, $outputlangs->convToOutputCharset($str), 0, 'L');
//        $hauteur = dol_nboflines_bis($str, 50) * 4;
//        $posY += $hauteur + 3;
//        $haut_col4 += $hauteur + 7;
//
//        // Cadre
//        ($haut_col4 > $haut_col2) ? $haut_table = $haut_col4 : $haut_table = $haut_col2;
//        $this->pdf->Rect($cadre_tableau[0], $cadre_tableau[1], $this->espaceH_dispo, $haut_table);
//
//        /**
//         * *** Bloc formateur ****
//         */
//        if (empty($noTrainer)) {
//            $this->pdf->SetXY($posX - 2, $posY - 2);
//            $this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'BI', 9);
//            $str = $outputlangs->transnoentities('AgfPDFFichePres12');
//            $this->pdf->Cell(0, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
//            $posY += 2;
//
//            // Entête
//            // Cadre
//            $this->pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $this->h_ligne + 8);
//            // Nom
//            $this->pdf->SetXY($posX, $posY);
//            $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
//            $str = $outputlangs->transnoentities('AgfPDFFichePres16');
//            $this->pdf->Cell($this->trainer_widthcol1, $this->h_ligne + 8, $outputlangs->convToOutputCharset($str), 'R', 2, "C", 0);
//            // Signature
//            $this->pdf->SetXY($posX + $this->trainer_widthcol1, $posY);
//            $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
//            $str = $outputlangs->transnoentities('AgfPDFFichePres18');
//            $this->pdf->Cell(0, 5, $outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);
//
//            $this->pdf->SetXY($posX + $this->trainer_widthcol1, $posY + 3);
//            $this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', 7);
//            $showAdditionalText = empty($conf->global->AGF_FICHE_PRES_HIDE_LEGAL_MEANING_BELOW_SIGNATURE_HEADER);
//            if ($showAdditionalText) {
//                $str = $outputlangs->transnoentities('AgfPDFFichePres13');
//                $this->pdf->Cell(0, 5, $outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);
//            }
//            $posY += $this->h_ligne;
//
//            // Date
//
//            $last_day = '';
//            $same_day = 0;
//            for ($y = 0; $y < $this->nbtimeslots; $y++) {
//                // Jour
//                $this->pdf->SetXY($posX + $this->trainer_widthcol1 + (20 * $y), $posY);
//                $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
//
//                $str = '';
//                if ($last_day == $lines_array[$y]->date_session) {
//                    $same_day += 1;
//                    $this->pdf->SetFillColor(255, 255, 255);
//                } else {
//                    $same_day = 0;
//                }
//                $this->pdf->SetXY($posX + $this->trainer_widthcol1 + ($this->trainer_widthtimeslot * $y) - ($this->trainer_widthtimeslot * ($same_day)), $posY);
//                $this->pdf->Cell($this->trainer_widthtimeslot * ($same_day + 1), 4, $outputlangs->convToOutputCharset($str), 1, 2, "C", $same_day);
//
//                // horaires
//                $this->pdf->SetXY($posX + $this->trainer_widthcol1 + ($this->trainer_widthtimeslot * $y), $posY + 4);
//                $str = '';
//
//                $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);
//                $this->pdf->Cell($this->trainer_widthtimeslot, 4, $outputlangs->convToOutputCharset($str), 1, 2, "C", 0);
//
//                $last_day = $lines_array[$y]->date_session;
//            }
//            $posY = $this->pdf->GetY();
//
//            $formateurs = new Agefodd_session_formateur($this->db);
//            $nbform = $formateurs->fetch_formateur_per_session($agf->id);
//            if ($nbform > 0) {
//                foreach ($formateurs->lines as $trainerlines) {
//
//                    // Cadre
//                    $this->pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $this->h_ligne);
//
//                    // Nom
//                    $this->pdf->SetXY($posX - 2, $posY);
//                    $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);
//                    $str = strtoupper($trainerlines->lastname) . ' ' . ucfirst($trainerlines->firstname);
//                    $this->pdf->MultiCell($this->trainer_widthcol1 + 2, $this->h_ligne, $outputlangs->convToOutputCharset($str), 1, "L", false, 1, '', '', true, 0, false, false, $this->h_ligne, 'M');
//
//                    for ($i = 0; $i < $this->nbtimeslots - 1; $i++) {
//                        $this->pdf->Rect($posX + $this->trainer_widthcol1 + $this->trainer_widthtimeslot * $i, $posY, $this->trainer_widthtimeslot, $this->h_ligne);
//                    }
//
//                    $posY = $this->pdf->GetY();
//                }
//            }
//
//            $posY = $this->pdf->GetY() + 2;
//        }
//
//
//        $this->pdf->SetXY($posX - 2, $posY);
//        $this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'BI', 9);
//        $str = $outputlangs->transnoentities('AgfPDFFichePres15');
//        $this->pdf->Cell(0, 4, $outputlangs->convToOutputCharset($str), 0, 2, "L", 0);
//        $posY = $this->pdf->GetY();
//
//        // Entête
//        // Cadre
//        $this->pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $this->h_ligne + 8);
//        // Nom
//        $this->pdf->SetXY($posX, $posY);
//        $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
//        $str = $outputlangs->transnoentities('AgfPDFFichePres16');
//        $this->pdf->Cell($this->trainee_widthcol1, $this->h_ligne + 8, $outputlangs->convToOutputCharset($str), 'R', 2, "C", 0);
//        // Société
//        if (empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
//            $this->pdf->SetXY($posX + $this->trainee_widthcol1, $posY);
//            $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
//            $str = $outputlangs->transnoentities('AgfPDFFichePres17');
//            $this->pdf->Cell($this->trainee_widthcol2, $this->h_ligne + 8, $outputlangs->convToOutputCharset($str), 0, 2, "C", 0);
//        } else {
//            $this->trainee_widthcol2 = 0;
//        }
//
//        // Signature
//        $this->pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2, $posY);
//        $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 9);
//        $str = $outputlangs->transnoentities('AgfPDFFichePres18');
//        $this->pdf->Cell(0, 5, $outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);
//
//        $this->pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2, $posY + 3);
//        $this->pdf->SetFont(pdf_getPDFFont($outputlangs), 'I', 7);
//        $showAdditionalText = empty($conf->global->AGF_FICHE_PRES_HIDE_LEGAL_MEANING_BELOW_SIGNATURE_HEADER);
//        if ($showAdditionalText) {
//            $str = $outputlangs->transnoentities('AgfPDFFichePres19');
//            $this->pdf->Cell(0, 5, $outputlangs->convToOutputCharset($str), 'LR', 2, "C", 0);
//        }
//        $posY += $this->h_ligne;
//
//        // Date
//        $agf_date = new Agefodd_sesscalendar($this->db);
//        $resql = $agf_date->fetch_all($agf->id);
//        if (!$resql) {
//            setEventMessages($agf_date->error, 'errors');
//        }
//        $last_day = '';
//        for ($y = 0; $y < $this->nbtimeslots; $y++) {
//            // Jour
//            $this->pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2 + (20 * $y), $posY);
//            $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 8);
//            $str = '';
//            if ($last_day == $lines_array[$y]->date_session) {
//                $same_day += 1;
//                $this->pdf->SetFillColor(255, 255, 255);
//            } else {
//                $same_day = 0;
//            }
//            $this->pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2 + ($this->trainee_widthtimeslot * $y) - ($this->trainee_widthtimeslot * ($same_day)), $posY);
//            $this->pdf->Cell($this->trainee_widthtimeslot * ($same_day + 1), 4, $outputlangs->convToOutputCharset($str), 1, 2, "C", $same_day);
//
//            // horaires
//            $this->pdf->SetXY($posX + $this->trainee_widthcol1 + $this->trainee_widthcol2 + ($this->trainee_widthtimeslot * $y), $posY + 4);
//            $str = '';
//
//            $this->pdf->SetFont(pdf_getPDFFont($outputlangs), '', 7);
//            $this->pdf->Cell($this->trainee_widthtimeslot, 4, $outputlangs->convToOutputCharset($str), 1, 2, "C", 0);
//
//            $last_day = $lines_array[$y]->date_session;
//        }
//        $posY = $this->pdf->GetY();
//
//        return array($posY, $posX);
//    }
}
