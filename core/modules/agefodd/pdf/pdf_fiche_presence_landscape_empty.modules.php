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
        } else {
            $this->trainer_widthtimeslot = 23.3;
            $this->trainee_widthtimeslot = 23.9;
        }

		$this->nbtimeslots = 10;

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

}
