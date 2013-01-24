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
 \file		agefodd/core/modules/agefodd/pdf/pdf_fiche_presence_trainee.modules.php
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



class pdf_fiche_presence_trainee extends ModelePDFAgefodd
{
	var $emetteur;	// Objet societe qui emet

	// Definition des couleurs utilisées de façon globales dans le document (charte)
	protected $colorfooter;
	protected $colortext;
	protected $colorhead;

	/**
	 *	\brief		Constructor
	 *	\param		db		Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;


		$this->db = $db;
		$this->name = "fiche_presence_trainee";
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

		$this->colorfooter = agf_hex2rgb($conf->global->AGF_FOOT_COLOR);
		$this->colortext = agf_hex2rgb($conf->global->AGF_TEXT_COLOR);
		$this->colorhead = agf_hex2rgb($conf->global->AGF_HEAD_COLOR);

		// Get source company
		$this->emetteur=$mysoc;
		if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if was not defined

	}


	/**
	 *  Fonction generant le document sur le disque
	 *
	 *  @param	object	$agf        	Objet document a generer (ou id si ancienne methode)
	 *  @param  object	$outputlangs	Lang object for output language
	 *  @param  string	$file			Name of file to generate
	 *  @param	int		$socid			thirdparty id
	 *  @return int      		   		<0 if KO, Id of created object if OK
	 */
	function write_file($agf, $outputlangs, $file, $socid, $courrier)
	{
		global $user,$langs,$conf,$mysoc;

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

			$pdf->SetTitle($outputlangs->convToOutputCharset($outputlangs->transnoentities('AgfPDFFichePres1')." ".$agf->ref));
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
				foreach ($agf->line as $line)	{
					$this->_pagebody($pdf, $agf, 1, $outputlangs, $line);
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
	 *      \param      $line			Trainee object
	 */
	function _pagebody(&$pdf, $agf, $showaddress=1, $outputlangs, $line)
	{
		global $user,$langs,$conf, $mysoc;

		// New page
		$pdf->AddPage();
		$pagenb++;
		$this->_pagehead($pdf, $agf, 1, $outputlangs);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
		$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);

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
				// Calcul de la largeur du logo en mm en fonction de la résolution (300dpi)
				// 1 inch = 25.4mm
				$marge_logo =  (($widthLogo*25.4)/300);
				$pdf->Image($logo, $this->page_largeur - $this->marge_gauche - $this->marge_droite - $marge_logo, $this->marge_haute, 0, $heightLogo);	// width=0 (auto)
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
		
		// Affichage du logo commanditaire (optionnel)
		if($conf->global->AGF_USE_LOGO_CLIENT)
		{
			$staticsoc = new Societe($this->db);
			$staticsoc->fetch($agf->socid);
			$dir=$conf->societe->multidir_output[$staticsoc->entity].'/'.$staticsoc->id.'/logos/';
			$logo_client=$dir.$staticsoc->logo;
			if (file_exists($logo_client))
				$pdf->Image($logo_client, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 90, $this->marge_haute + 10, 40);
		}

		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',11);
		$pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
		$pdf->SetXY($posX, $posY -1);
		$pdf->Cell(0, 5, $mysoc->name,0,0,'L');

		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',7);
		$pdf->SetXY($posX, $posY +3);
		$this->str = $mysoc->address."\n";
		$this->str.= $mysoc->zip.' '.$mysoc->town;
		$this->str.= ' - '.$mysoc->country."\n";
		$this->str.= ' - '.$mysoc->country."\n";
		if ($mysoc->phone) {$this->str.= $outputlangs->transnoentities('AgfPDFHead1').' '.$mysoc->phone."\n";}
		if ($mysoc->fax) {$this->str.= $outputlangs->transnoentities('AgfPDFHead2').' '.$mysoc->fax."\n";}
		if ($mysoc->email) {$this->str.= $outputlangs->transnoentities('AgfPDFHead3').' '.$mysoc->email."\n";}
		if ($mysoc->url) {$this->str.= $outputlangs->transnoentities('AgfPDFHead4').' '.$mysoc->url."\n";}

		$pdf->MultiCell(100,3, $outputlangs->convToOutputCharset($this->str), 0, 'L');

		$posY = $pdf->GetY() + 10;

		$pdf->SetDrawColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
		$pdf->Line ($this->marge_gauche + 0.5, $posY, $this->page_largeur - $this->marge_droite, $posY);

		// Mise en page de la baseline
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',18);
		$this->str = $outputlangs->transnoentities($mysoc->url);
		$this->width = $pdf->GetStringWidth($this->str);

		// alignement du bord droit du container avec le haut de la page
		$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->width;
		$baseline_angle = (M_PI/2); //angle droit
		$baseline_x = 8;
		$baseline_y = $this->espaceV_dispo - $baseline_ecart + 30;
		$baseline_width = $this->width;
		$pdf->SetXY($baseline_x, $baseline_y);

		/*
		 * Corps de page
		*/
		$posX = $this->marge_gauche;
		$posY = $posY + 5;

		// Titre
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'B',18);
		$pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres1');
		$pdf->Cell(0,6, $outputlangs->convToOutputCharset($this->str),0,2,"C",0);
		$posY+= 6 + 4;

		// Intro
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres2').' « '.$mysoc->name.' »,'. $outputlangs->transnoentities('AgfPDFFichePres3').' ';
		$this->str.= $mysoc->address.' ';
		$this->str.= $mysoc->zip.' '.$mysoc->town;
		$this->str.=$outputlangs->transnoentities('AgfPDFFichePres4').' '.AGF_ORGANISME_REPRESENTANT.",\n";
		$this->str.=$outputlangs->transnoentities('AgfPDFFichePres5');
		$pdf->MultiCell(0,4, $outputlangs->convToOutputCharset($this->str),0,'C');
		$hauteur = dol_nboflines_bis($this->str,50)*2;
		$posY += $hauteur + 2;

		/***** Bloc formation *****/
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'BI',9);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres23');
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
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres6');
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
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres7');
		$pdf->Cell($larg_col1, 4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

		if ($agf->dated == $agf->datef) $this->str = $outputlangs->transnoentities('AgfPDFFichePres8')." ".dol_print_date($agf->datef,'daytext');
		else $this->str = $outputlangs->transnoentities('AgfPDFFichePres9')." ".dol_print_date($agf->dated).' '.$outputlangs->transnoentities('AgfPDFFichePres10').' '.dol_print_date($agf->datef,'daytext');
		$pdf->SetXY($posX + $larg_col1, $posY);
		$pdf->MultiCell($larg_col2,4, $outputlangs->convToOutputCharset($this->str),0,'L');
		$hauteur = dol_nboflines_bis($this->str,50)*4;
		$haut_col2 += $hauteur + 2;

		// Lieu
		$pdf->SetXY($posX + $larg_col1 + $larg_col2 , $posY - $hauteur);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres11');
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
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres12');
		$pdf->Cell(0,4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);
		$posY+= 2;

		$cadre_tableau = array($posX - 2 , $posY);
		$h_ligne = 6;

		$larg_col1 = 10;
		$larg_col2 = 29;
		$larg_col3 = 120;
		$larg_col4 = 60;
		$haut_col2 = 0;
		$haut_col4 = 0;

		$formateurs = new Agefodd_session_formateur($this->db);
		$nbform = $formateurs->fetch_formateur_per_session($agf->id);

		for ($i=0; $i < $nbform; $i++)
		{
			// Nom
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
			$this->str = strtoupper($formateurs->line[$i]->name).' '.ucfirst($formateurs->line[$i]->firstname);
			$pdf->Cell($larg_col2, $h_ligne, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

			$pdf->SetXY($posX + $larg_col1 + $larg_col2, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
			$this->str = $outputlangs->transnoentities('AgfPDFFichePres13');
			$pdf->Cell($larg_col2, $h_ligne, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

			$pdf->SetXY($posX + $larg_col1 + $larg_col2 + $larg_col3, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
			$this->str = $outputlangs->transnoentities('AgfPDFFichePres14')." ";
			$pdf->Cell($larg_col4, $h_ligne, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

			// Cadre
			($haut_col4 > $haut_col2) ? $haut_table = $haut_col4 : $haut_table = $haut_col2;
			$pdf->Rect($cadre_tableau[0], $cadre_tableau[1], $this->espaceH_dispo, $h_ligne);

			$cadre_tableau[1] += 6;
			$posY += +6;

		}

		$posY = $pdf->GetY() + 4;


		/***** Bloc stagiaire *****/

		//$resql = $agf->fetch_stagiaire_per_session($agf->id);

		$pdf->SetXY($posX -2 , $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'BI',9);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres26');
		$pdf->Cell(0,4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);
		$posY+= 4;

		$larg_col1 = 30;
		$larg_col2 = 30;
		$larg_col3 = 50;
		$larg_col4 = 112;
		$haut_col2 = 0;
		$haut_col4 = 0;
		$h_ligne = 7;
		$haut_cadre = 0;

		// Entête
		
		// Date
		$pdf->SetXY($posX, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres24');
		$pdf->Cell($larg_col1, $h_ligne + 8, $outputlangs->convToOutputCharset($this->str),1,2,"C",0);
		// Horarie
		$pdf->SetXY($posX + $larg_col1, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres25');
		$pdf->Cell($larg_col2, $h_ligne + 8, $outputlangs->convToOutputCharset($this->str),1,2,"C",0);
		
		//Trainee
		$pdf->SetXY($posX + $larg_col1 + $larg_col2, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'B',9);
		$this->str = $this->str = $line->nom.' '.$line->prenom.' - '.dol_trunc($line->socname, 27);
		$pdf->Cell(0, 5 , $outputlangs->convToOutputCharset($this->str),TR,2,"C",0);
		
		$posY= $pdf->GetY();
		
		// Signature
		$pdf->SetXY($posX + $larg_col1 + $larg_col2, $posY);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres18');
		$pdf->Cell(0, 5 , $outputlangs->convToOutputCharset($this->str),R,2,"C",0);
		$pdf->SetXY($posX + $larg_col1 + $larg_col2, $posY+ 3);
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'I',7);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres19');
		$pdf->Cell(0, 7, $outputlangs->convToOutputCharset($this->str),BR,2,"C",0);
		$posY= $pdf->GetY();
		
		

		// ligne
		$h_ligne = 9;
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		
		// Date
		$agf_date = new Agefodd_sesscalendar($this->db);
		$resql = $agf_date->fetch_all($agf->id);
		foreach ($agf_date->line as $linedate)	{
			// Jour
			$pdf->SetFont(pdf_getPDFFont($outputlangs),'',8);
			if ($linedate->date_session) {
				$date = dol_print_date($linedate->date_session,'daytextshort');
			}
			else {
				$date = '';
			}
			$this->str = $date;
			$pdf->SetXY($posX, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
			$pdf->Cell($larg_col1, $h_ligne, $outputlangs->convToOutputCharset($this->str),1,2,"C",0);
		
			// horaires
			if ($linedate->heured && $linedate->heuref)	{
				$this->str =  dol_print_date($linedate->heured,'hour').' - '.dol_print_date($linedate->heuref,'hour');
			}
			else {
				$this->str = '';
			}
			$pdf->SetXY($posX+$larg_col1, $posY);
			$pdf->SetFont(pdf_getPDFFont($outputlangs),'',7);
			$pdf->Cell($larg_col2, $h_ligne, $outputlangs->convToOutputCharset($this->str),1,2,"C",0);
			
			// Cadre pour signature
			$pdf->Rect($posX+$larg_col1+$larg_col2, $posY, 128, $h_ligne);
			
			$posY= $pdf->GetY();
		}
		$posY += 8;

		
		// Incrustation image tampon
		if($conf->global->AGF_INFO_TAMPON)
		{
			$dir=$conf->agefodd->dir_output.'/images/';
			$img_tampon=$dir.$conf->global->AGF_INFO_TAMPON;
			if (file_exists($img_tampon))
				$pdf->Image($img_tampon, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 50, $posY, 50);
		}

		// Cachet et signature
		$posY += 2;
		$posX -=2;
		$pdf->SetXY($posX, $posY);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres20');
		$pdf->Cell(50, 4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

		$pdf->SetXY($posX + 55, $posY);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres21');
		$pdf->Cell(20, 4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

		$pdf->SetXY($posX + 92, $posY);
		$this->str = $outputlangs->transnoentities('AgfPDFFichePres22');
		$pdf->Cell(50, 4, $outputlangs->convToOutputCharset($this->str),0,2,"L",0);

		// Pied de page
		$this->_pagefoot($pdf,$agf,$outputlangs);
		$pdf->AliasNbPages();

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
		global $conf,$langs,$mysoc;

		$pdf->SetDrawColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		$pdf->Line ($this->marge_gauche, $this->page_hauteur - 20, $this->page_largeur - $this->marge_droite, $this->page_hauteur - 20);

		$this->str = $mysoc->name;

		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$pdf->SetTextColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		$pdf->SetXY( $this->marge_gauche, $this->page_hauteur - 20);
		$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'C');

		$this->str = $mysoc->address." ";
		$this->str.= $mysoc->zip.' '.$mysoc->town;
		$this->str.= ' - '.$mysoc->country;
		$this->str.= ' '.$outputlangs->transnoentities('AgfPDFFoot1').' '.$mysoc->phone;
		$this->str.= ' '.$outputlangs->transnoentities('AgfPDFFoot2').' '.$mysoc->email."\n";

		$statut = getFormeJuridiqueLabel($mysoc->forme_juridique_code);
		$this->str.= $statut;
		if (!empty($mysoc->capital)) {$this->str.=' '.$outputlangs->transnoentities('AgfPDFFoot3').' '.$mysoc->capital.' '.$langs->trans("Currency".$conf->currency);}
		if (!empty($mysoc->idprof2)) {$this->str.= ' '.$outputlangs->transnoentities('AgfPDFFoot4').' '.$mysoc->idprof2;}
		if (!empty($mysoc->idprof4)) {$this->str.= ' '.$outputlangs->transnoentities('AgfPDFFoot5').' '.$mysoc->idprof4;}
		if (!empty($mysoc->idprof3)) {$this->str.= ' '.$outputlangs->transnoentities('AgfPDFFoot6').' '.$mysoc->idprof3;}
		$this->str.="\n";
		if (!empty($conf->global->AGF_ORGANISME_NUM)) {$this->str.= ' '.$outputlangs->transnoentities('AgfPDFFoot7').' '.$conf->global->AGF_ORGANISME_NUM;}
		if (!empty($conf->global->AGF_ORGANISME_PREF)) {$this->str.= ' '.$outputlangs->transnoentities('AgfPDFFoot8').' '.$conf->global->AGF_ORGANISME_PREF;}
		if (!empty($mysoc->tva_intra)) {$this->str.=' '.$outputlangs->transnoentities('AgfPDFFoot9').' '.$mysoc->tva_intra;}

		$pdf->SetFont(pdf_getPDFFont($outputlangs),'I',7);
		$pdf->SetXY( $this->marge_gauche, $this->page_hauteur - 16);
		$pdf->MultiCell(0, 3, $outputlangs->convToOutputCharset($this->str),0,'C');


	}
}

# llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>