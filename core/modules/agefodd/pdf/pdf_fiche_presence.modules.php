<?php
/* Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2012       Florian Henry   <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */


/**
	\file		$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/s_liste.php $
	\brief		Page permettant la création de la fiche de présence d'une format donnée au format pdf
	\version	$Id$
*/

dol_include_once('/agefodd/core/modules/agefodd/agefodd_modules.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_convention.class.php');
dol_include_once('/agefodd/class/agefodd_place.class.php');
dol_include_once('/agefodd/trainer/class/agefodd_place.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
dol_include_once('/core/lib/pdf.lib.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/core/lib/company.lib.php');



class pdf_fiche_presence extends ModelePDFAgefodd
{
	var $emetteur;	// Objet societe qui emet

	// Definition des couleurs utilisées de façon globales dans le document (charte)
	protected $color1 = array('190','190','190');	// gris clair
	protected $color2 = array('19', '19', '19');	// Gris très foncé
	protected $color3 = array('118', '146', '60');	// Vert flashi

	/**
	 *	\brief		Constructor
	 *	\param		db		Database handler
	 */
	function pdf_fiche_presence($db)
	{
		global $conf,$langs,$mysoc;


		$this->db = $db;
		$this->name = "fiche_presence";
		$this->description = $langs->trans('AgfModPDFFichePres');

		// Dimension page pour format A4 en paysage
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width']; // use standard but reverse width and height to get Landscape format
		$this->page_hauteur = $formatarray['height']; // use standard but reverse width and height to get Landscape format
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;
		$this->unit='mm';
		$this->oriantation='P';
		$this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
		$this->milieu = $this->espaceH_dispo / 2;
		$this->espaceV_dispo = $this->page_hauteur - ($this->marge_haute + $this->marge_basse);

		// Get source company
		$this->emetteur=$mysoc;
		if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if was not defined

	}


	/**
	 *	\brief      	Fonction generant le document sur le disque
	 *	\param	    	agf		Objet document a generer (ou id si ancienne methode)
	*			outputlangs	Lang object for output language
	 *			file		Name of file to generate
	 *	\return	    	int     	1=ok, 0=ko
	 */
	function write_file($agf, $outputlangs, $file, $socid, $courrier)
	{
		global $user,$langs,$conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		if (! is_object($outputlangs)) $outputlangs=$langs;

		if (! is_object($agf))
		{
			$id = $agf;
			$agf = new Agsession($this->db);
			$ret = $agf->fetch($id);
		}

		// Definition of $dir and $file
		$dir = $conf->agefodd->dir_output;
		$file = $dir.'/'.$file;

		if (! file_exists($dir))
		{
			if (create_exdir($dir) < 0)
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}

		if (file_exists($dir))
		{
			$pdf=pdf_getInstance($this->format,$this->unit,$this->orientation);

			if (class_exists('TCPDF'))
			{
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}

			$pdf->Open();
			$pagenb=0;

			$pdf->SetDrawColor(128,128,128);
			$pdf->SetTitle($outputlangs->convToOutputCharset("Feuille de présence ".$agf->ref));
			$pdf->SetSubject($outputlangs->transnoentities("Invoice"));
			$pdf->SetCreator("Dolibarr ".DOL_VERSION.' (Agefodd module)');
			$pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($agf->ref)." ".$outputlangs->transnoentities("Document"));
			if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
			$pdf->SetAutoPageBreak(1,0);

			// On recupere les infos societe
			$agf_soc = new Societe($this->db);
			$result = $agf_soc->fetch($socid);

			if ($result)
			{
				$resql = $agf->fetch_stagiaire_per_session($agf->id);
				$nbsta=count($agf->line);

				//$blocsta=0;
				for ($blocsta=0; $blocsta <= (intval($nbsta/10)); $blocsta++)	{
					$this->_pagebody($pdf, $agf, 1, $outputlangs,$blocsta);
				}
			}

			$pdf->Close();
			$pdf->Output($file,'F');
			if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

			return 1;   // Pas d'erreur
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","AGF_OUTPUTDIR");
			return 0;
		}
		$this->error=$langs->trans("ErrorUnknown");
		return 0;   // Erreur par defaut
	}

	/**
	 *   	\brief      Show header of page
	 *      \param      pdf             	Object PDF
	 *      \param      object          	Object invoice
	 *      \param      showaddress     	0=no, 1=yes
	 *      \param      outputlangs		Object lang for output
	 *      \param      $blocsta		Number of stagiaire bloc to display
	 */
	function _pagebody(&$pdf, $agf, $showaddress=1, $outputlangs, $blocsta=0)
	{
		global $user,$langs,$conf;

		// New page
		$pdf->AddPage();
		$pagenb++;
		$this->_pagehead($pdf, $agf, 1, $outputlangs);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
		$pdf->SetTextColor(0,0,0);

		$posY = $this->marge_haute;
		$posX = $this->marge_gauche;

		/*
		 * Header société
		*/

		// Logo en haut à gauche
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
				$heightLogo=pdf_getHeightForLogo($logo);
						include_once(DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php');
						$tmp=dol_getImageSize($logo);
						if ($tmp['width'])
						{
							$widthLogo = $tmp['width'];
						}
						$marge_logo =  (($widthLogo*25.4)/72);
						$pdf->Image($logo, $this->marge_gauche + $marge_logo, $this->marge_haute, 0, $heightLogo);	// width=0 (auto)
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B', pdf_getPDFFontSize($outputlangs) - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		//$posX += $this->page_largeur - $this->marge_droite - 65;

				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',11);
				$pdf->SetTextColor($this->color2[0], $this->color2[1], $this->color2[2]);
				$pdf->SetXY($posX, $posY -1);
				$pdf->Cell(0, 5, $conf->global->MAIN_INFO_SOCIETE_NOM,0,0,'L');

				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',7);
				$pdf->SetXY($posX, $posY +3);
				$this->str = $conf->global->MAIN_INFO_SOCIETE_ADRESSE."\n";
				$this->str.= $conf->global->MAIN_INFO_SOCIETE_CP.' '.$conf->global->MAIN_INFO_SOCIETE_VILLE;
				$this->str.= ' - FRANCE'."\n";
				$this->str.= 'tél : '.$conf->global->MAIN_INFO_SOCIETE_TEL."";
				if($conf->global->MAIN_INFO_SOCIETE_FAX)
					$this->str.= '- fax : '.$conf->global->MAIN_INFO_SOCIETE_FAX."\n";
				else
					$this->str.= "\n";
				//$this->str.= 'courriel : '.$conf->global->MAIN_INFO_SOCIETE_MAIL."\n";
				$this->str.= 'site web : '.$conf->global->MAIN_INFO_SOCIETE_WEB."\n";

				$pdf->SetTextColor($this->color3[0], $this->color3[1], $this->color3[2]);
				$pdf->MultiCell(100,3, $outputlangs->convToOutputCharset($this->str), 0, 'L');

				$hauteur = dol_nboflines_bis($this->str,50)*4;
				$posY += $hauteur + 2;

				// Plateau technique
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
				$pdf->SetTextColor($this->color2[0], $this->color2[1], $this->color2[2]);
				$pdf->SetXY($posX, $posY -1);
				$pdf->Cell(0, 5, 'Centre et plateau technique',0,0,'L');

				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',7);
				$pdf->SetXY($posX, $posY +3);
				$this->str = "3 rue Jean Marie David\n";
				$this->str.= '35740 PACE RENNES';
				$this->str.= ' - FRANCE'."\n";
				$pdf->SetTextColor($this->color3[0], $this->color3[1], $this->color3[2]);
				$pdf->MultiCell(100,3, $outputlangs->convToOutputCharset($this->str), 0, 'L');

				$hauteur = dol_nboflines_bis($this->str,50)*4;
				$posY += $hauteur + 5;

				$pdf->SetDrawColor($this->color3[0], $this->color3[1], $this->color3[2]);
				$pdf->Line ($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);

				// Mise en page de la baseline
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',18);
		$this->str = $outputlangs->transnoentities($conf->global->MAIN_INFO_SOCIETE_WEB);
		$this->width = $pdf->GetStringWidth($this->str);

		// alignement du bord droit du container avec le haut de la page
		$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->width;
		$baseline_angle = (M_PI/2); //angle droit
		$baseline_x = 8;
		$baseline_y = $this->espaceV_dispo - $baseline_ecart + 30;
		$baseline_width = $this->width;
		$pdf->SetTextColor($this->color1[0], $this->color1[1], $this->color1[2]);
		$pdf->SetXY($baseline_x, $baseline_y);

		/*
		 * Corps de page
		*/
		$posX = $this->marge_gauche;
		$posY = $posY + 5;

		// Titre
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'B',18);
		$pdf->SetTextColor($this->color2[0], $this->color2[1], $this->color2[2]);
		$this->str = "FICHE DE PRESENCE";
		$pdf->Cell(0,6, $outputlangs->convToOutputCharset($this->str),0,2,"C",0);
		$posY+= 6 + 4;

		// Intro
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$pdf->SetTextColor(0, 0, 0);
		$this->str = "Nous, « ".$conf->global->MAIN_INFO_SOCIETE_NOM." », demeurant ";
		$this->str.= $conf->global->MAIN_INFO_SOCIETE_ADRESSE.' ';
		$this->str.= $conf->global->MAIN_INFO_SOCIETE_CP.' '.$conf->global->MAIN_INFO_SOCIETE_VILLE;
		$this->str.=", représentés par ".AGF_ORGANISME_REPRESENTANT.",\n";
		$this->str.="attestons par la présente de la réalité des informations portées ci-dessous à votre connaissance.";
		$pdf->MultiCell(0,4, $outputlangs->convToOutputCharset($this->str),0,'C');
		$hauteur = dol_nboflines_bis($this->str,50)*2;
		$posY += $hauteur + 2;

		/***** Bloc formation *****/
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'BI',9);
		$this->str = "La formation";
		$pdf->Cell(0,4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);
		$posY+= 4;

		//$pdf->Line($posX, $posY, $this->page_largeur - $this->marge_droite, $posY);
		$cadre_tableau=array($posX, $posY);

		$posX+= 2;
		$posY+= 2;

		$larg_col1 = 20;
		$larg_col2 = 90;
		$larg_col3 = 30;
		$larg_col4 = 82;
		$haut_col2 = 0;
		$haut_col4 = 0;

		// Intitulé
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$this->str = "Intitulé :";
		$pdf->Cell($larg_col1, 4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

		$pdf->SetXY($posX + $larg_col1, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'B',9);
		$this->str = '« '.$agf->formintitule.' »';
		$pdf->MultiCell($larg_col2, 4, $outputlangs->convToOutputCharset($this->str),0,'L');
		$hauteur = dol_nboflines_bis($this->str,50)*4;
		$posY += $hauteur;
		$haut_col2 += $hauteur;

		// Période
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$this->str = "Période :";
		$pdf->Cell($larg_col1, 4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

		if ($agf->dated == $agf->datef) $this->str = "le ".dol_print_date($agf->datef,'daytext');
		else $this->str = "du ".dol_print_date($agf->dated).' au '.dol_print_date($agf->datef,'daytext');
		$pdf->SetXY($posX + $larg_col1, $posY);
		$pdf->MultiCell($larg_col2,4, $outputlangs->convToOutputCharset($this->str),0,'L');
		$hauteur = dol_nboflines_bis($this->str,50)*4;
		$haut_col2 += $hauteur + 2;

		// Lieu
		$pdf->SetXY($posX + $larg_col1 + $larg_col2 , $posY - $hauteur);
		$this->str = "Lieu de formation :";
		$pdf->Cell($larg_col3, 4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

		$agf_place = new Agefodd_place($this->db);
		$resql = $agf_place->fetch($agf->placeid);

		$pdf->SetXY($posX + $larg_col1 + $larg_col2  + $larg_col3, $posY - $hauteur);
		$this->str = $agf_place->ref_interne."\n". $agf_place->adresse."\n".$agf_place->cp." ".$agf_place->ville;
		$pdf->MultiCell($larg_col4, 4, $outputlangs->convToOutputCharset($this->str),0,'L');
		$hauteur = dol_nboflines_bis($this->str,50)*4;
		$posY += $hauteur+5;
		$haut_col4 += $hauteur +7;

		// Cadre
		($haut_col4 > $haut_col2) ? $haut_table = $haut_col4 : $haut_table = $haut_col2;
		$pdf->Rect($cadre_tableau[0], $cadre_tableau[1], $this->espaceH_dispo, $haut_table);


		/***** Bloc formateur *****/

		$pdf->SetXY($posX - 2, $posY -2);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'BI',9);
		$this->str = "Le(s) formateur(s)";
		$pdf->Cell(0,4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);
		$posY+= 2;

		$cadre_tableau = array($posX - 2 , $posY);
		$h_ligne = 6;

		$larg_col1 = 10;
		$larg_col2 = 30;
		$larg_col3 = 120;
		$larg_col4 = 60;
		$haut_col2 = 0;
		$haut_col4 = 0;

		$formateurs = new Agefodd_session_formateur($this->db);
		$nbform = $formateurs->fetch_formateur_per_session($agf->id);

		for ($i=0; $i < $nbform; $i++)
		{
		// Nom
			//$pdf->SetXY($posX, $posY);
			//$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
			//$this->str = "Nom :";
			//$pdf->Cell($larg_col1, $h_ligne, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

			$pdf->SetXY($posX, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
			//$this->str = $agf->teachername;
			$this->str = strtoupper($formateurs->line[$i]->name).' '.ucfirst($formateurs->line[$i]->firstname);;
			$pdf->Cell($larg_col2, $h_ligne, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

			$pdf->SetXY($posX + $larg_col1 + $larg_col2, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
			$this->str = "le formateur atteste par la présente avoir dispensé la formation ci-dessus nommée";
			$pdf->Cell($larg_col2, $h_ligne, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

			$pdf->SetXY($posX + $larg_col1 + $larg_col2 + $larg_col3, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
			$this->str = "signature: ";
			$pdf->Cell($larg_col4, $h_ligne, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

			// Cadre
			($haut_col4 > $haut_col2) ? $haut_table = $haut_col4 : $haut_table = $haut_col2;
			$pdf->Rect($cadre_tableau[0], $cadre_tableau[1], $this->espaceH_dispo, $h_ligne);

			$cadre_tableau[1] += 6;
			$posY += +6;

		}

		$posY+= 4;


		/***** Bloc stagiaire *****/

		$resql = $agf->fetch_stagiaire_per_session($agf->id);

		$pdf->SetXY($posX -2 , $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'BI',9);
		$this->str = "Les stagaires";
		$pdf->Cell(0,4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);
		$posY+= 4;

		$cadre_tableau=array($posX -2 , $posY );


		$larg_col1 = 40;
		$larg_col2 = 40;
		$larg_col3 = 50;
		$larg_col4 = 112;
		$haut_col2 = 0;
		$haut_col4 = 0;
		$h_ligne = 7;
		$haut_cadre = 0;

		// Entête
		// Cadre
		$pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $h_ligne +8);
		// Nom
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$this->str = "Nom et prénom";
		$pdf->Cell($larg_col1, $h_ligne + 8, $outputlangs->convToOutputCharset($this->str),R,2,"C",0);
		// Société
		$pdf->SetXY($posX + $larg_col1, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$this->str = "Société";
		$pdf->Cell($larg_col2, $h_ligne + 8, $outputlangs->convToOutputCharset($this->str),0,2,"C",0);
		// Signature
		$pdf->SetXY($posX + $larg_col1 + $larg_col2, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$this->str = "Signature";
		$pdf->Cell(0, 5 , $outputlangs->convToOutputCharset($this->str),LR,2,"C",0);

		$pdf->SetXY($posX + $larg_col1 + $larg_col2, $posY+ 3);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'I',7);
		$this->str = "(j'atteste par la présente avoir reçu la formation ci-dessus nommée)";
		$pdf->Cell(0, 5 , $outputlangs->convToOutputCharset($this->str),LR,2,"C",0);
		$posY += $h_ligne;

		// Date
		$agf_date = new Agefodd_sesscalendar($this->db);
		$resql = $agf_date->fetch_all($agf->id);
		//count($agf_date->line)
		$largeur_date = 16;
		for ($y = 0; $y < 10; $y++)	{
			// Jour
			$pdf->SetXY($posX + $larg_col1 + $larg_col2 +( 20 * $y), $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs),'',8);
			if ($agf_date->line[$y]->date_session) {
				$date = dol_print_date($agf_date->line[$y]->date_session,'daytext');
			}
			else {
				$date = '';
			}
			//$this->str = dol_print_date($agf_date->line[$y]->date);
			$this->str = $date;
			if ($last_day == $agf_date->line[$y]->date_session)	{
				$same_day += 1;
				$pdf->SetFillColor(255,255,255);
				$pdf->SetXY($posX + $larg_col1 + $larg_col2 + ( $largeur_date * $y) - ( $largeur_date * ($same_day)), $posY);
				$pdf->Cell($largeur_date * ($same_day + 1), 4, $outputlangs->convToOutputCharset($this->str),1,2,"C",1);
			}
			else {
				$same_day = 0;
				$pdf->SetXY($posX + $larg_col1 + $larg_col2 +( $largeur_date * $y), $posY);
				$pdf->Cell($largeur_date, 4, $outputlangs->convToOutputCharset($this->str),1,2,"C",0);
			}
			// horaires
			$pdf->SetXY($posX + $larg_col1 + $larg_col2 +( $largeur_date * $y), $posY + 4);
			if ($agf_date->line[$y]->heured && $agf_date->line[$y]->heuref)	{
				$this->str =  dol_print_date($agf_date->line[$y]->heured,'hour').' - '.dol_print_date($agf_date->line[$y]->heuref,'hour');
			}
			else {
				$this->str = '';
			}
			$pdf->SetFont(pdf_getPDFFont($outputlangs),'',7);
			$pdf->Cell($largeur_date, 4, $outputlangs->convToOutputCharset($this->str),1,2,"C",0);

			$last_day = $agf_date->line[$y]->date_session;
		}
		$posY += 8;

		// ligne
		$h_ligne = 7;
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);

		$nbsta=count($agf->line);
		if (intval($blocsta+10)<$nbsta){
			$limitsta=intval($blocsta+10);
		}else{
			$limitsta=$nbsta;
		}

		for ($y = intval($blocsta*10); $y <= $limitsta+15; $y++)
		{
			// Cadre
			$pdf->Rect($posX - 2, $posY, $this->espaceH_dispo, $h_ligne);

			// Nom
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
			$this->str = $agf->line[$y]->nom.' '.$agf->line[$y]->prenom;
			$pdf->Cell($larg_col1, $h_ligne, $outputlangs->convToOutputCharset($this->str),R,2,"L",0);

			// Société
			$pdf->SetXY($posX + $larg_col1, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
			$this->str = dol_trunc($agf->line[$y]->socname, 27);
			$pdf->Cell($larg_col2, $h_ligne, $outputlangs->convToOutputCharset($this->str),0,2,"C",0);

			for ($i = 0; $i < 5; $i++)
			{
				$pdf->Rect($posX  + $larg_col1  + $larg_col2 + $largeur_date * $i, $posY, $largeur_date, $h_ligne);
			}
			$posY += $h_ligne;
		}

		// Cachet et signature
		$posY += 2;
		$posX -=2;
		$pdf->SetXY($posX, $posY);
		$this->str = "Fait pour valoir ce que de droit";
		$pdf->Cell(50, 4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

		$pdf->SetXY($posX + 55, $posY);
		$this->str = "date :";
		$pdf->Cell(20, 4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

		$pdf->SetXY($posX + 92, $posY);
		$this->str = "cachet de l'organisme de formation et signature de son représentant :";
		$pdf->Cell(50, 4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

		// Pied de page
		$this->_pagefoot($pdf,$agf,$outputlangs);
		$pdf->AliasNbPages();

		// Repere de pliage
		$pdf->SetDrawColor(220,220,220);
		$pdf->Line(3,($this->page_hauteur)/3,6,($this->page_hauteur)/3);
	}


	/**
	 *   	\brief      Show header of page
	 *      \param      pdf             	Object PDF
	 *      \param      object          	Object invoice
	 *      \param      showaddress     	0=no, 1=yes
	 *      \param      outputlangs		Object lang for output
	 */
	function _pagehead(&$pdf, $object, $showaddress=1, $outputlangs)
	{
		global $conf,$langs;

		$outputlangs->load("main");

		pdf_pagehead($pdf,$outputlangs,$pdf->page_hauteur);
	}


	/**
	 *   	\brief		Show footer of page
	 *   	\param		pdf     		PDF factory
	 * 	\param		object			Object invoice
	 *      \param		outputlang		Object lang for output
	 * 	\remarks	Need this->emetteur object
	 */
	function _pagefoot(&$pdf,$object,$outputlangs)
	{
		global $conf,$langs;

		$pdf->SetDrawColor($this->color1[0], $this->color1[1], $this->color1[2]);
		$pdf->Line ($this->marge_gauche, $this->page_hauteur - 15, $this->page_largeur - $this->marge_droite, $this->page_hauteur - 15);

		$this->str = $conf->global->MAIN_INFO_SOCIETE_NOM;
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$pdf->SetTextColor($this->color1[0], $this->color1[1], $this->color1[2]);
		$pdf->SetXY( $this->marge_gauche, $this->page_hauteur - 15);
		$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'C');

		$statut = getFormeJuridiqueLabel($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
		$this->str = $statut." au capital de ".$conf->global->MAIN_INFO_CAPITAL." euros";
		$this->str.= " - SIRET ".$conf->global->MAIN_INFO_SIRET;
		$this->str.= " - RCS ".$conf->global->MAIN_INFO_RCS;
		$this->str.= " - Code APE ".$conf->global->MAIN_INFO_APE;
		$this->str.= " - TVA intracommunautaire ".$conf->global->MAIN_INFO_TVAINTRA;

		$pdf->SetFont(pdf_getPDFFont($outputlangs),'I',7);
		$pdf->SetXY( $this->marge_gauche, $this->page_hauteur - 11);
		$pdf->MultiCell(0, 3, $outputlangs->convToOutputCharset($this->str),0,'C');


	}
}

# llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>