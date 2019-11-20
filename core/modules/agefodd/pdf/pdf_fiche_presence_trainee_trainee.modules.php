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
 * @file agefodd/core/modules/agefodd/pdf/pdf_fiche_presence_trainee_trainee.modules.php
 * @ingroup agefodd
 * @brief PDF for training attendees session sheet by trainee
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


// The name of the class may be surprising
class pdf_fiche_presence_trainee_trainee extends ModelePDFAgefodd
{
    var $emetteur; // Objet societe qui emet
    /** @var Translate $outputlangs */
    var $outputlangs;

    /** @var Agsession $session */
    var $session;

    var $orientation;

    /** @var TCPDF $pdf */
    var $pdf;

    /** @var Agefodd_session_stagiaire $agfSessionTrainee */
    var $agfSessionTrainee;
    /** @var Agefodd_stagiaire $agfTrainee */
    var $agfTrainee;
    /** @var Societe $agfTraineeSoc */
    var $agfTraineeSoc;
    /** @var Agefodd_session_formateur[] */
    var $TAgfTrainer;
    /** @var Agefodd_sesscalendar $agfSessionCalendar */
    var $agfSessionCalendar;
    /** @var DaoMulticompany $dao */
    var $dao;
    /** @var int $maxDateSlotsPerRow  How many date cells we can fit in one row */
    var $maxDateSlotsPerRow;

    /** @var float $pointByMillimeter  Unit conversion helper */
    var $pointByMillimeter = 2.83465;

    /** @var int $dateColMinWidth */
    var $dateColMinWidth = 25;

    // Definition des couleurs utilisées de façon globales dans le document (charte)
    protected $colorfooter;
    protected $colortext;
    protected $colorhead;
    protected $colorheaderBg;
    protected $colorheaderText;
    protected $colorLine;

    /**
     * @param DoliDB $db Database handler
     */
    function __construct($db) {
        /**
         @var Societe $mysoc
         @var Translate $langs
         */
        global $conf, $langs, $mysoc;

        $this->db = $db;
        $this->type = 'pdf';
        $this->name = "fiche_presence_trainee_trainee";
        $this->trainer_widthcol1 = $this->trainee_widthcol1 = 55;
        $this->description = $langs->trans('AgfModPDFFichePres');

        // Dimension page pour format A4 en paysage
        $this->_setOrientation('L');
        $this->marge_gauche = 10;
        $this->marge_droite = 10;
        $this->marge_haute = 10;
        $this->marge_basse = 10;
        $this->unit = 'mm';
        $this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
        $this->milieu = $this->espaceH_dispo / 2;
        $this->espaceV_dispo = $this->page_hauteur - ($this->marge_haute + $this->marge_basse);
        $this->default_font_size = 9;

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
     * @param int       $sessionId  rowid of llx_agefodd_session (Agsession)
     * @param Translate $outputlangs
     * @param string    $fileName
     * @param int       $sessionTraineeId rowid of llx_agefodd_session_stagiaire
     * @return int
     */
    function write_file($sessionId, $outputlangs, $fileName, $sessionTraineeId) {
        global $langs, $conf, $mysoc;
        $this->outputlangs = is_object($outputlangs) ? $outputlangs : $langs;
        $this->session = new Agsession($this->db);
        if ($this->session->fetch($sessionId) <= 0) {
            $this->error = $langs->trans('AgfErrorUnableToFetchSession', $sessionId);
            return 0;
        };

        // Definition of $dir and $file
        $dir = $conf->agefodd->dir_output;
        if (empty($dir)) {
            $this->error = $langs->trans("ErrorConstantNotDefined", "AGF_OUTPUTDIR");
            return 0;
        }
        $file = $dir . '/' . $fileName;
        if (!file_exists($dir) && dol_mkdir($dir) < 0) {
            $this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
            return 0;
        }

        $this->pdf = pdf_getInstance_agefodd($this->session, $this, $this->format, $this->unit, $this->orientation);
        $this->pdf->Open();
        $this->_setMetaData();
        $this->pdf->SetAutoPageBreak(1, $this->getRealHeightLine('foot'));
        $this->pdf->SetFont(pdf_getPDFFont($this->outputlangs));
        $this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);
        if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) {$this->pdf->SetCompression(false);}

        $headerHeight = $this->getRealHeightLine('head');
        // Left, Top, Right
        $this->pdf->SetMargins($this->marge_gauche, $headerHeight + 10, $this->marge_droite, 1);

        // Load multicompany entities
        if (!empty($conf->multicompany->enabled)) {
            dol_include_once('/multicompany/class/dao_multicompany.class.php');
            $this->dao = new DaoMulticompany($this->db);
            $this->dao->getEntities();
        }
        // START LOAD AGEFODD DATA
        $this->agfSessionTrainee =  new Agefodd_session_stagiaire($this->db);
        $this->agfTrainee =         new Agefodd_stagiaire($this->db);
        $agfTrainer =               new Agefodd_session_formateur($this->db);
        $this->agfTraineeSoc =      new Societe($this->db);
        $this->agfSessionCalendar = new Agefodd_sesscalendar($this->db);

        $this->error = '';
        if ($this->agfSessionTrainee->fetch($sessionTraineeId) <= 0) {
            $this->error = $langs->trans('AgfErrorUnableToFetchSessionTrainee', $sessionTraineeId);
        } elseif ($this->agfSessionTrainee->fk_session_agefodd != $sessionId) {
            $this->error = $langs->trans('AgfErrorSessionIdMismatch', $sessionId, $this->agfSessionTrainee->fk_session_agefodd);
        } elseif ($this->agfTrainee->fetch($this->agfSessionTrainee->fk_stagiaire) <= 0) {
            $this->error = $langs->trans('AgfErrorUnableToFetchTrainee', $this->agfSessionTrainee->fk_stagiaire);
        } elseif ($agfTrainer->fetch_formateur_per_session($this->session->id) <= 0) {
            $this->error = $langs->trans('AgfErrorUnableToFetchTrainer');
        } elseif ($this->agfTraineeSoc->fetch($this->agfTrainee->socid) <= 0) {
            $this->error = $langs->trans('AgfErrorUnableToFetchTraineeSoc', $this->agfTrainee->socid);
        } elseif ($this->agfSessionCalendar->fetch_all($this->session->id) <= 0) {
            $this->error = $langs->trans('AgfErrorUnableToFetchCalendar');
        } elseif (!count($agfTrainer->lines)) {
            $this->error = $langs->trans('AgfErrorNoTrainersFound');
        }
        $this->TAgfTrainer = $agfTrainer->lines;
        if (!empty($this->error)) return 0;
        // END LOAD AGEFODD DATA

        // compute how many date slots we can fit in one row (depends on the length of the data in the first column)
        $firstColWidth = $this->trainer_widthcol1; // TODO: compute dynamically (according to contents)
        $this->dateColMinWidth = 30;
        $this->maxDateSlotsPerRow = intval(($this->espaceH_dispo - $firstColWidth) / $this->dateColMinWidth);

        $datesByMonth = array();
        /* data structure (assuming maxDateSlotsPerRow = 3):
        Each array at the deepest level represents what will be printed on a separate PDF page.

        $datesByMonth = array (
            '12/2019' => array (
                array (<agf date session>, <agf date session>, <agf date session>),
                array (<agf date session>, <agf date session>)
            ),
            '01/2020' => array (
                array (<agf date session>, <agf date session>, <agf date session>),
                array (<agf date session>)
            ),
            '02/2020' => array (
                array (<agf date session>, <agf date session>)
            )
        );
         */

        foreach ($this->agfSessionCalendar->lines as $dateSlot) {
            $dateTms = $dateSlot->date_session;
            $monthYear = dol_print_date($dateTms, '%m/%Y');

            if (!isset($datesByMonth[$monthYear])) $datesByMonth[$monthYear] = array(array());
            $nbChunks = count($datesByMonth[$monthYear]); // at least 1
            $nbDatesInLastChunk = count($datesByMonth[$monthYear][$nbChunks-1]);
            if ($nbDatesInLastChunk == $this->maxDateSlotsPerRow) {
                $datesByMonth[$monthYear][] = array();
                $nbChunks = count($datesByMonth[$monthYear]);
                $nbDatesInLastChunk = 0;
            }
            $datesByMonth[$monthYear][$nbChunks-1][] = $dateSlot;
        }

        foreach ($datesByMonth as $monthYear => $TTSessionDate) {
            foreach ($TTSessionDate as $TSessionDate) {
                $this->_pagebody($TSessionDate);
            }
        }

        $this->pdf->Close();
        $this->pdf->Output($file, 'F');
        if (! empty($conf->global->MAIN_UMASK))
            @chmod($file, octdec($conf->global->MAIN_UMASK));


        // Add pdfgeneration hook
        if (!isset($hookmanager) || !is_object($hookmanager)) {
            include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
            $hookmanager=new HookManager($this->db);
        }

        $hookmanager->initHooks(array('pdfgeneration'));
        $parameters = array(
            'file'=>$file,
            'object'=>$this->session,
            'outputlangs'=>$outputlangs
        );
        global $action;
        $reshook=$hookmanager->executeHooks(
            'afterPDFCreation',
            $parameters,
            $this,
            $action
        );    // Note that $action and $object may have been modified by some hooks
        return 1; // Pas d'erreur
    }

    /**
     * @param Agefodd_sesscalendar[] $TSessionDate  Array of session dates to be displayed on a single page
     * @return void
     */
    function _pagebody($TSessionDate)
    {
        global $conf, $mysoc;
        // Set path to the background PDF File
        if (empty($conf->global->MAIN_DISABLE_FPDI) && !empty($conf->global->AGF_ADD_PDF_BACKGROUND_P)) {
            $pagecount = $this->pdf->setSourceFile($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P);
            $tplidx = $this->pdf->importPage(1);
        }

        // New page
        $this->pdf->AddPage();
        if (!empty($tplidx))
            $this->pdf->useTemplate($tplidx);

        $height_for_footer = 40;
        if (!empty($conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER))
            $height_for_footer = $conf->global->AGEFODD_CUSTOM_HEIGHT_FOR_FOOTER;

        $this->pdf->SetFont(pdf_getPDFFont($this->outputlangs), '', 9);

        if (0) $this->_showTrainerTableForPage($TSessionDate);
        if (0) $this->_showTraineeTableForPage($TSessionDate);
        $this->_tryToPrint('_showTrainerTableForPage', 1, array($TSessionDate));
        $this->pdf->SetY($this->pdf->GetY() + 5);
        $this->_tryToPrint('_showTraineeTableForPage', 1, array($TSessionDate));
    }

    protected function _showHeaderRow($leftColWidth, $dateColWidth, $TSessionDate)
    {
        $subRow1Height = $this->_getYSpacing(2);
        $subRow2Height = $this->_getYSpacing(2);
        $rowHeight = $subRow1Height + $subRow2Height;
        $leftHeaderCellContent = 'nOM et pRÉNOM'; //TODO

        // cellule de gauche
        $this->pdf->MultiCell(
            $leftColWidth,
            $rowHeight,
            $leftHeaderCellContent,
            'LTRB',
            'C',
            0,
            0,
            '',
            '',
            true,
            0,
            false,
            true,
            $rowHeight,
            'M',
            false);
        $dateColStartX = $this->pdf->GetX();
        $this->pdf->MultiCell(
            $this->espaceH_dispo - $leftColWidth,
            $subRow1Height,
            'sIGNATURE', // todo
            'LTRB',
            'C',
            0,
            1,
            '',
            '',
            true,
            0,
            false,
            true,
            $subRow1Height,
            'M',
            false);
        $this->pdf->SetX($dateColStartX);

        // autres cellules
        $nbSlots = count($TSessionDate);
        $slotNum = 1;
        foreach ($TSessionDate as $dateSlot) {
            $ln = ($slotNum == $nbSlots) ? 1 : 0; // si dernière cellule de la ligne, on update Y, sinon X
            $this->pdf->MultiCell(
                $dateColWidth,
                $subRow2Height,
                $this->_getDateSlotContent($dateSlot),
                'LTRB',
                'C',
                0,
                $ln,
                '',
                '',
                true,
                0,
                false,
                true,
                $subRow2Height,
                'M',
                false);
            $slotNum++;
        }
        $this->pdf->SetY($this->pdf->GetY());
    }

    protected function _showBodyRow($leftColWidth, $dateColWidth, $leftHeaderCellContent, $TSessionDate)
    {
        $rowHeight = $this->_getYSpacing(1);

        // cellule de gauche
        $this->pdf->MultiCell(
            $leftColWidth,
            $rowHeight,
            $leftHeaderCellContent,
            'LTRB',
            'L',
            0,
            0,
            '',
            '',
            true,
            0,
            false,
            true,
            $rowHeight,
            'M',
            false);

        // autres cellules
        $nbSlots = count($TSessionDate);
        $slotNum = 1;
        foreach ($TSessionDate as $dateSlot) {
            $colStartX = $this->pdf->GetX();
            $ln = ($slotNum == $nbSlots) ? 1 : 0; // si dernière cellule de la ligne, on update Y, sinon X
            $this->pdf->MultiCell(
                $dateColWidth,
                $rowHeight,
                '',
                'LTRB',
                'C',
                0,
                $ln,
                '',
                '',
                true,
                0,
                false,
                true,
                $rowHeight,
                'M',
                false);
            $slotNum++;
        }
    }

    protected function _showTrainerTableForPage($TSessionDate)
    {
        $tableTitle = $this->_getTrainerTableTitleContent();
        $fontHeight = $this->pdf->getStringHeight(200, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')*2;

        $dateColWidth = $this->_getDateColWidth($this->trainer_widthcol1, count($TSessionDate));

        // Titre du tableau ('Les formateurs')
        $this->pdf->SetFont('', 'bi', $this->default_font_size - 1);
        $this->pdf->MultiCell($this->espaceH_dispo, $fontHeight, $tableTitle, '', 'L', 0, 1);
        $this->pdf->SetFont('', '-', $this->default_font_size);
        $this->pdf->SetY($this->pdf->GetY()-4);

        // Ligne des titres (≠ titre du tableau)
        $this->_showHeaderRow($this->trainer_widthcol1, $dateColWidth, $TSessionDate);

        // Lignes de contenu
        $trainerN = 0;
        foreach ($this->TAgfTrainer as $agfTrainer) {
            $this->_showBodyRow(
                $this->trainer_widthcol1,
                $dateColWidth,
                $this->_getTrainerNameCellContent($agfTrainer),
                $TSessionDate);
            $trainerN++;
        }
    }

    protected function _showTraineeTableForPage($TSessionDate)
    {
        $leftMostCellContent = $this->_getTraineeNameCellContent($this->agfTrainee);
        $tableTitle = $this->_getTraineeTableTitleContent();
        $espacementTables = 5;
        $fontHeight = $this->pdf->getStringHeight(200, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')*2;

        $dateColWidth = $this->_getDateColWidth($this->trainer_widthcol1, count($TSessionDate));

        $this->pdf->SetFont('', 'bi', $this->default_font_size - 1);
        $this->pdf->MultiCell($this->espaceH_dispo, $fontHeight, $tableTitle, '', 'L', 0, 1);
        $this->pdf->SetFont('', '-', $this->default_font_size);
        $this->pdf->SetY($this->pdf->GetY()-4);

        // Ligne des titres (≠ titre du tableau)
        $this->_showHeaderRow($this->trainer_widthcol1, $dateColWidth, $TSessionDate);

        // Ligne de contenu
        $this->_showBodyRow($this->trainer_widthcol1, $dateColWidth, $this->_getTraineeTableTitleContent(), $TSessionDate);
        $this->pdf->SetY($this->pdf->GetY() + $espacementTables);
    }

    /**
     * @return string  Content of the cell with the trainee's name + other trainee-related information
     */
    protected function _getTraineeNameCellContent($agfTrainee)
    {
        global $conf;
        $cellContent = '';

        if (!empty($agfTrainee->civilite)) {
            if ($this->agfTrainee->civilite == 'MR') {
                $cellContent .= 'M. ';
            } elseif ($this->agfTrainee->civilite == 'MME' || $this->agfTrainee->civilite == 'MLE') {
                $cellContent .= 'Mme. ';
            } else {
                $cellContent .= $this->agfTrainee->civilite . ' ';
            }
        }
        $cellContent .= $agfTrainee->nom . ' ' . $agfTrainee->prenom;
        if (!empty($agfTrainee->poste) && empty($conf->global->AGF_HIDE_POSTE_FICHEPRES)) {
            $cellContent .= ' (' . $this->agfTrainee->poste . ')';
        }
        if (!empty($agfTrainee->date_birth) && !empty($conf->global->AGF_ADD_DTBIRTH_FICHEPRES)) {
            $this->outputlangs->load("other");
            $cellContent .= "\n" . $this->outputlangs->trans('DateToBirth') . ' : ' . dol_print_date($this->agfTrainee->date_birth, 'day');
        }
        if (!empty($conf->global->AGF_HIDE_SOCIETE_FICHEPRES)) {
            if (!empty($this->agfTrainee->socname)) {
                $cellContent .= '-' . dol_trunc($this->agfTrainee->socname, 27);
            }
        }
        if (is_object($this->dao) && $conf->global->AGF_ADD_ENTITYNAME_FICHEPRES) {
            $c = new Societe($this->db);
            $c->fetch($this->agfTrainee->socid);

            if (count($this->dao->entities) > 0) {
                foreach ($this->dao->entities as $e) {
                    if ($e->id == $c->entity) {
                        $cellContent .= "\n" . $this->outputlangs->trans('Entity') . ' : ' . $e->label;
                        break;
                    }
                }
            }
        }
        return $cellContent;
    }

    protected function _getTrainerNameCellContent($agfTrainer)
    {
        return $agfTrainer->firstname . ' ' . $agfTrainer->lastname;
    }

    protected function _getTraineeTableTitleContent()
    {
        switch($this->agfTrainee->civilite) {
            case 'MLE': case 'MME':
                $translationKey = 'AgfFichePresByTraineeTraineeTitleF';
                break;
            case 'MR':
                $translationKey = 'AgfFichePresByTraineeTraineeTitleM';
                break;
            default:
                $translationKey = 'AgfFichePresByTraineeTraineeTitle';
                break;
        }
        return $this->outputlangs->trans($translationKey);
    }

    protected function _getTrainerTableTitleContent()
    {
        $translationKey = 'AgfFichePresByTraineeTrainerTitle';
        return $this->outputlangs->trans($translationKey);
    }

    protected function _getDateSlotContent($dateSlot)
    {
        return dol_print_date($dateSlot->date_session)
        . "\n" . dol_print_date($dateSlot->heured, '%H:%M')
        . '-' . dol_print_date($dateSlot->heuref, '%H:%M');
    }

    protected function _getDateColWidth($leftColWidth, $nSlots)
    {
        return ($this->espaceH_dispo - $leftColWidth) / $nSlots;
    }

    /**
     * @param $factor Approximately: the desired vertical spacing measured in "lines" of text using current font
     * @return float  Vertical spacing in millimeters that can be used in $this->pdf->SetY()
     */
    protected function _getYSpacing($factor)
    {
//        $fontBBox = $this->pdf->getFontBBox();
        $glyphHeight = $this->default_font_size * 0.352778;
        return $glyphHeight * $factor * 1.7;
    }

    protected function _setMetaData() {
        global $user;
        $this->pdf->SetTitle($this->outputlangs->convToOutputCharset($this->outputlangs->transnoentities('AgfPDFFichePres1') . " " . $this->session->ref));
        $this->pdf->SetSubject($this->outputlangs->transnoentities("AgfPDFFichePres1"));
        $this->pdf->SetCreator("Dolibarr " . DOL_VERSION . ' (Agefodd module)');
        $this->pdf->SetAuthor($this->outputlangs->convToOutputCharset($user->fullname));
        $this->pdf->SetKeyWords($this->outputlangs->convToOutputCharset($this->session->ref) . " " . $this->outputlangs->transnoentities("Document"));
    }

    /**
     * \brief Show header of page
     * \param object Object invoice
     * \param showaddress 0=no, 1=yes
     * \param outputlangs Object lang for output
     */
    function _pagehead() {
//        xdebug_print_function_stack();
        global $conf, $langs, $mysoc;

        $this->outputlangs->load("main");

//        $this->pdf->SetDrawColor($this->colorLine[0], $this->colorLine[1], $this->colorLine[2]);

        // spécifique multicompany
        if (!empty($conf->multicompany->enabled)) {
            dol_include_once('/multicompany/class/dao_multicompany.class.php');
            $dao = new DaoMulticompany($this->db);
            $dao->getEntities();
        }

        // Fill header with background color
        $this->pdf->SetFillColor($this->colorheaderBg[0], $this->colorheaderBg[1], $this->colorheaderBg[2]);
        $this->pdf->MultiCell($this->page_largeur, 40, '', 0, 'L', true, 1, 0, 0);

//        pdf_pagehead($this->pdf, $this->outputlangs, $this->pdf->page_hauteur);

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

        $this->pdf->SetY($this->posY + 1);
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

    /** @var TCPDF $pdf */
    /**
     * @param TCPDF  $pdf
     * @param string $method Name of a method to be called by _tryToPrint; the method will be called with $pdf as the first argument
     * @param bool   $autoPageBreak
     * @param array  $param (Optional) : additional parameters to be passed to the method
     * @return float New Y "cursor" position on the PDF document.
     */
    public function _tryToPrint($method, $autoPageBreak = true, $callbackParams = array())
    {
        global $conf, $outputlangs;

        $callback = array($this, $method);

        if (is_callable($callback))
        {

            $this->pdf->startTransaction();
            $posYBefore = $this->pdf->GetY();
            $pageposBefore=$this->pdf->getPage();

            // START FIRST TRY
            call_user_func_array($callback, $callbackParams);

            $pageposAfter=$this->pdf->getPage();
            $posYAfter = $this->pdf->GetY();

            // END FIRST TRY

            if($autoPageBreak && $pageposAfter > $pageposBefore )
            {
                $pagenb = $pageposBefore;
                $this->pdf->rollbackTransaction(true);
                $posY = $posYBefore;

                // prepare pages to receive content
                while ($pagenb < $pageposAfter) {
                    $this->pdf->AddPage();
                    $pagenb++;

                    if (! empty($tplidx)) $this->pdf->useTemplate($tplidx);

                    if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($this->pdf, $this->object, 0, $outputlangs);

                    $topY = $this->pdf->GetY() + 20;
                    $this->pdf->SetMargins($this->marge_gauche, $topY, $this->marge_droite); // Left, Top, Right

                    $this->pdf->SetAutoPageBreak(0, 0); // to prevent footer creating page
                    $footerheight = $this->_pagefoot($this->pdf,$this->object, $outputlangs);
                    $this->pdf->SetAutoPageBreak(1, $footerheight);

                    // The only function to edit the bottom margin of current page to set it.
                    $this->pdf->setPageOrientation('', 1, $footerheight);
                }

                // BACK TO START
                $this->pdf->setPage($pageposBefore);
                $this->pdf->SetY($posYBefore);

                // RESTART DISPLAY BLOCK - without auto page break
                return $this->_tryToPrint($method, false, $callbackParams);
            }
            else // No pagebreak
            {
                $this->pdf->commitTransaction();
            }
        }
        return $this->pdf->GetY();
    }

    public function _setOrientation($orientation='P') {

        $this->orientation = $this->oriantation = 'L';
        $formatarray = pdf_getFormat();
        $this->page_largeur = $formatarray['height']; // use standard but reverse width and height to get Landscape format
        $this->page_hauteur = $formatarray['width'];  // use standard but reverse width and height to get Landscape format
        if ($orientation === 'L') {
            $this->page_largeur = $formatarray['height'];
            $this->page_hauteur = $formatarray['width'];
        }
        $this->format = array($this->page_largeur, $this->page_hauteur);
    }
}