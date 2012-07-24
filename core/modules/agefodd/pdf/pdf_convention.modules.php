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
	\brief		Page permettant la création de la convention de formation au format pdf pour uen structure donnée.
	\version	$Id$
*/

dol_include_once('/agefodd/session/class/agefodd_session.class.php');
dol_include_once('/agefodd/training/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_facture.class.php');
dol_include_once('/agefodd/contact/class/agefodd_contact.class.php');
dol_include_once('/agefodd/session/class/agefodd_convention.class.php');
dol_include_once('/agefodd/site/class/agefodd_place.class.php');
dol_include_once('/agefodd/core/modules/agefodd/agefodd_modules.php');
dol_include_once('/core/lib/pdf.lib.php');
dol_include_once('/core/lib/company.lib.php');


class pdf_convention extends ModelePDFAgefodd
{
	var $emetteur;	// Objet societe qui emet
	
	// Definition des couleurs utilisées de façon globales dans le document (charte)
	protected $color1 = array('190','190','190');	// gris clair
	protected $color2 = array('203', '70', '25');	// marron/orangé

	/**
	 *	\brief		Constructor
	 *	\param		db		Database handler
	 */
	function pdf_convention($db)
	{
		global $conf,$langs,$mysoc;
		
		$langs->load("agefodd@agefodd");
		
		$this->db = $db;
		$this->name = "convention";
		$this->description = $langs->trans('AgfModPDFConvention');

		// Dimension page pour format A4 en portrait
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=15;
		$this->marge_droite=15;
		$this->marge_haute=10;
		$this->marge_basse=10;
		$this->unit='mm';
		$this->oriantation='P';
		$this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
		$this->milieu = $this->espaceH_dispo / 2;

		$this->hApresTitreArticle = 8;
		$this->hApresCorpsArticle = 2;
		
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
			$agf = new Agefodd_session($this->db,"",$id);
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
			$pdf->SetFont(pdf_getPDFFont($outputlangs));
			
			$pdf->Open();
			$pagenb=0;
			
			$pdf->SetDrawColor(128,128,128);
			$pdf->SetTitle($outputlangs->convToOutputCharset($agf->ref));
			$pdf->SetSubject($outputlangs->transnoentities("Invoice"));
			$pdf->SetCreator("Dolibarr ".DOL_VERSION.' (Agefodd module)');
			$pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($agf->ref)." ".$outputlangs->transnoentities("Document"));
			if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
			$pdf->SetAutoPageBreak(1,0);
			
			// On récupere le contenu de la convention
			$agf_conv = new Agefodd_convention($this->db);
			$result = $agf_conv->fetch($id, $socid);

			// On récupére le contenu du bon de commande
			$agf_comid= new Agefodd_facture($this->db);
			$result = $agf_comid->fetch($id,$socid);

			$agf_comdetails= new Agefodd_convention($this->db);
			$result = $agf_comdetails->fetch_commande_lines($agf_comid->comid);

			if ($result)
			{
				/*
				 * Page de garde
				 */
				
				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $agf, 1, $outputlangs);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', 9);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);
				
				$posY = $this->marge_haute;
				$posX = $this->marge_gauche;
				
				// Logo en haut à gauche
				$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
				if ($this->emetteur->logo)
				{
					if (is_readable($logo))
					{
						$heightLogo=pdf_getHeightForLogo($logo);
						$pdf->Image($logo, $this->marge_gauche, $this->marge_haute, 0, $heightLogo);	// width=0 (auto)
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
				
				$posX += $this->page_largeur - $this->marge_droite - 65;
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
				$pdf->SetTextColor($this->color2[0], $this->color2[1], $this->color2[2]);
				$pdf->SetXY($posX, $posY -1);
				$pdf->Cell(0, 5, $conf->global->MAIN_INFO_SOCIETE_NOM,0,0,'L');
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',7);
				$pdf->SetXY($posX, $posY +3);
				$this->str = $conf->global->MAIN_INFO_SOCIETE_ADRESSE."\n";
				$this->str.= $conf->global->MAIN_INFO_SOCIETE_CP.' '.$conf->global->MAIN_INFO_SOCIETE_VILLE;
				$this->str.= ' - FRANCE'."\n";
				$this->str.= 'tél : '.$conf->global->MAIN_INFO_SOCIETE_TEL."\n";
				$this->str.= 'fax : '.$conf->global->MAIN_INFO_SOCIETE_FAX."\n";
				$this->str.= 'courriel : '.$conf->global->MAIN_INFO_SOCIETE_MAIL."\n";
				$this->str.= 'site web : '.$conf->global->MAIN_INFO_SOCIETE_WEB."\n";
				$pdf->MultiCell(100,3, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				
				$hauteur = dol_nboflines_bis($this->str,50)*4;
				$posY += $hauteur + 2;
				
				$pdf->SetDrawColor($this->color2[0], $this->color2[1], $this->color2[2]);
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
				
				//TItre page de garde
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',25);
				$pdf->SetTextColor($this->color2[0], $this->color2[1], $this->color2[2]);
				$pdf->SetXY( $this->marge_gauche, $this->marge_haute + 120);
				$pdf->Cell(0, 5, "Convention de formation",0,0,'C');
				
				
				// Pied de page
				$this->_pagefoot($pdf,$agf,$outputlangs);
				$pdf->AliasNbPages();
				
				// Repere de pliage
				$pdf->SetDrawColor(220,220,220);
				$pdf->Line(3,($this->page_hauteur)/3,6,($this->page_hauteur)/3);
				
				/*
				 * Page 1
				 */
				
				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $agf, 1, $outputlangs);
				$this->defaultFontSize = 9;
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);
				$posX = $this->marge_gauche;
				$posY = $this->marge_haute;
				
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize);
				$this->str = "Entre les soussignés :";
				$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str),0,0);
				$posY += 4 ;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $agf_conv->intro1;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$hauteur = dol_nboflines_bis($this->str,50)*2;
				$posY += $hauteur;
				
				$pdf->SetXY( $posX, $posY);
				$this->str = "Ci-après dénommée « l'organisme » d'une part,";
				$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str),0,0);
				$posY += 8;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize);
				$this->str = "Et";
				$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str),0,0);
				$posY += 4;
				
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str= $agf_conv->intro2; 
				//$this->str.= " dûment habilité à ce faire,";
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$hauteur = dol_nboflines_bis($this->str,50)*3;
				$posY += $hauteur + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$this->str = "Ci-après dénommée « le client » d'autre part,";	
				$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str),0,0);
				$posY += 4;


				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize);
				$this->str = "Est conclue la convention suivante, en application des dispositions du Livre IX du Code du Travail ";
				$this->str.= "portant organisation de la formation professionnelle continue dans le cadre de l'éducation permanente :";
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$hauteur = dol_nboflines_bis($this->str,50)*3;
				$posY += $hauteur + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$art = 0;
				$this->str = "Article ".++$art." - Objet";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;


				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = "La présente convention a pour objet la réalisation d'une prestation de formation";
				$this->str.= " par l'organisme auprès de membres du personnel du client";
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$hauteur = dol_nboflines_bis($this->str,50)*4;
				$posY += $hauteur + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = "Article ".++$art." - Détails du stage";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $this->liste_a_puce($agf_conv->art1);
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str));
				$hauteur = dol_nboflines_bis($this->str,50)*4;
				$posY += $hauteur + $this->hApresCorpsArticle;
				
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = "Article ".++$art." - Programme et méthode";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $agf_conv->art2;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$hauteur = dol_nboflines_bis($this->str,50)*4;
				$posY += $hauteur + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = "Article ".++$art." - Effectif formé";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $agf_conv->art3;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$hauteur = dol_nboflines_bis($this->str,50)*3;
				$posY += $hauteur + $this->hApresCorpsArticle;

				// Pied de page	
				$this->_pagefoot($pdf,$agf,$outputlangs);
				$pdf->AliasNbPages();


				/*
				 * Page 2
				 */
				
				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $agf, 1, $outputlangs);
				$this->defaultFontSize = 9;
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);
				$posX = $this->marge_gauche;
				$posY = $this->marge_haute;


				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = "Article ".++$art." - Dispositions financières";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $agf_conv->art4;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$hauteur = dol_nboflines_bis($this->str,50)*3;
				$posY += $hauteur + 2;
				
				
				// Tableau "bon de commande"
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFillColor($this->color1[0],$this->color1[1],$this->color1[2]);
				$pdf->SetFillColor(210,210,210);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize - 1 );
				$header=array($outputlangs->transnoentities('Désignation'), 'TVA', 'P.U. HT',$outputlangs->transnoentities('Qté'),'total HT','total TTC');
				$w=array(100,13,19,8,20,20);
				for($i=0;$i<count($header);$i++)
				{
					$pdf->Cell($w[$i],6,$header[$i],1,0,'C',1);
				}
				$posY += 6;
				$fill=false;
				$total_ht = 0;
				$total_tva = 0;
				$total_ttc = 0;
				for ($i = 0; $i < count($agf_comdetails->line); $i++)
				{
					$pdf->SetXY( $posX, $posY + 1);
					$pdf->MultiCell($w[0], 4, $outputlangs->transnoentities($agf_comdetails->line[$i]->description),0,'L',$fill);
					$hauteur = dol_nboflines_bis($agf_comdetails->line[$i]->description,50)*4;
					$pdf->SetXY( $posX + $w[0], $posY);
					$pdf->Cell($w[1],6,vatrate($agf_comdetails->line[$i]->tva_tx,1),0,0,'C',$fill);
					$pdf->Cell($w[2],6,price($agf_comdetails->line[$i]->price),0,0,'R',$fill);
					$pdf->Cell($w[3],6,$agf_comdetails->line[$i]->qty,0,0,'C',$fill);
					$pdf->Cell($w[4],6,price($agf_comdetails->line[$i]->total_ht),0,0,'R',$fill);
					$pdf->Cell($w[5],6,price($agf_comdetails->line[$i]->total_ttc),0,0,'R',$fill);
					
					$pdf->SetXY( $posX, $posY);
					$pdf->Cell($w[0],$hauteur,'','LRT',0,'R',$fill);
					$pdf->Cell($w[1],$hauteur,'','LRT',0,'R',$fill);
					$pdf->Cell($w[2],$hauteur,'','LRT',0,'R',$fill);
					$pdf->Cell($w[3],$hauteur,'','LRT',0,'R',$fill);
					$pdf->Cell($w[4],$hauteur,'','LRT',0,'R',$fill);
					$pdf->Cell($w[5],$hauteur,'','LRT',0,'R',$fill);
					
					$pdf->Ln();
					$posY += $hauteur;
					
					$total_ht += $agf_comdetails->line[$i]->total_ht;
					$total_tva += $agf_comdetails->line[$i]->total_tva;
					$total_ttc += $agf_comdetails->line[$i]->total_ttc;
				}
				
				$pdf->SetXY( $posX, $posY);
				$pdf->Cell(array_sum($w),0,'','T');
				$posY += 6;
				
				// total HT
				$pdf->SetXY($posX + array_sum($w) - $w[4] -$w[5], $posY);
				$pdf->Cell($w[4],5,'total HT ',0,0,'R',1);
				$pdf->Cell($w[5],5,price($total_ht),1,0,'R');
				$posY += 6;
				// total TVA
				$pdf->SetXY($posX + array_sum($w) - $w[4] - $w[5], $posY);
				$pdf->Cell($w[4],5,'total TVA ',0,0,'R',1);
				$pdf->Cell($w[5],5,price($total_tva),1,0,'R');
				$posY += 6;
				// total TTC
				$pdf->SetXY($posX + array_sum($w) - $w[4] - $w[5], $posY);
				$pdf->Cell($w[4],5,'total TTC ',0,0,'R',1);
				$pdf->Cell($w[5],5,price($total_ttc),1,0,'R');
				$posY += 5;
				// txt "montant euros"
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'I', $this->defaultFontSize - 2 );
				$pdf->Cell(0,4,$outputlangs->transnoentities('montants exprimés en euros'),0,0,'R',0);
				$posY += $this->hApresCorpsArticle + 4;
				
								
								
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = "Article ".++$art." - Conditions de règlement";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $agf_conv->art5;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str));
				$hauteur = dol_nboflines_bis($this->str,50)*3;
				$posY += $hauteur + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = "Article ".++$art." - Dédit ou abandon";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $agf_conv->art6;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$hauteur = dol_nboflines_bis($this->str,50)*2;
				$posY += $hauteur + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = "Article ".++$art." - Litiges et compétence d'attribution";
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $agf_conv->art7;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$hauteur = dol_nboflines_bis($this->str,50)*3;
				$posY += $hauteur + $this->hApresCorpsArticle;

				// Pied de page	
				$this->_pagefoot($pdf,$agf,$outputlangs);
				$pdf->AliasNbPages();


				/*
				 * Page 3
				 */
				
				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $agf, 1, $outputlangs);
				$this->defaultFontSize = 9;
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);
				$posX = $this->marge_gauche;
				$posY = $this->marge_haute;
				
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = "Signatures";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$literal = array('un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze');
				// Date
				
				$date = 'le '.dol_print_date(dol_now(),'daytext');
				
				$this->str = 'Fait à '.$conf->global->MAIN_INFO_SOCIETE_VILLE.', '.$date.' , en deux (2) exemplaires originaux, dont un remis ce jour au client. ';
				$nombre = $pdf->PageNo(); 	// page suivante = annexe1
				$this->str.= "Ce document comporte {nb} (".$literal[$nombre].") pages.";
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$hauteur = dol_nboflines_bis($this->str,50)*3;
				$posY += $hauteur + $this->hApresCorpsArticle;
				
				// Entete signature
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize);
				$this->str = "Pour l'Organisme de formation";	
				$pdf->Cell($this->espaceH_dispo/2, 4, $outputlangs->transnoentities($this->str),0,0,'C');
				
				$pdf->SetXY(  $this->milieu, $posY);
				$this->str = "Pour le Client";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0,'C');
				
				$posY += 6;
				
				//signature de l'organisme de formation
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $conf->global->MAIN_INFO_SOCIETE_NOM."\nreprésenté par ".AGF_ORGANISME_REPRESENTANT." (*)";
				$pdf->MultiCell($this->espaceH_dispo/2, 4, $outputlangs->transnoentities($this->str),0,'C');
				$hauteurA = dol_nboflines_bis($this->str,50)*3;
				
				// signature du client
				$pdf->SetXY( $this->milieu, $posY);
				if (!empty($agf_conv->sig)) $this->str = $agf_conv->sig;
				else
				{
					// if agefodd contact exist
					$this->str = $agf_soc->nom."\n";
					$this->str.= "représenté par ";
					$this->str.= ucfirst(strtolower($agf_contact->civilite)).' '.$agf_contact->firstname.' '.$agf_contact->name." (*)";
				}
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'C');
				$hauteurB = dol_nboflines_bis($this->str,50)*3;
				$hauteur = max($hauteurA, $hauteurB);
				$posY += $hauteur + 40;
				
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'I', $this->defaultFontSize -3);
				$this->str = "(*) Faire précéder la signature de la mention « lu et approuvé » après ";
				$this->str.= "avoir paraphé chaque page de la présente convention.";
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0,'C');
				$posY += 4 + $this->hApresCorpsArticle;


				// Pied de page	
				$this->_pagefoot($pdf,$agf,$outputlangs);
				$pdf->AliasNbPages();



				/*
				 * Page 4 (Annexe 1)
				 */
				
				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $agf, 1, $outputlangs);
				$this->defaultFontSize = 9;
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);
				$posX = $this->marge_gauche;
				$posY = $this->marge_haute;
		
				$infile = $conf->agefodd->dir_output.'/fiche_pedago_'.$id.'.pdf';
				if (is_file($infile))
				{
					$pdf->setSourceFile($infile);
					// import page 1
					$tplIdx = $pdf->importPage(1);
					// use the imported page and place it at point 10,10 with a width of 100 mm
					//$pdf->useTemplate($tplIdx, $this->marge_gauche, $this->marge_haute, $this->espaceH_dispo);
					$pdf->useTemplate($tplIdx, 0, 0, $this->page_largeur);
				}

				// Pied de page	
				//$this->_pagefoot($pdf,$agf,$outputlangs);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',6);
				$pdf->SetXY( $this->droite - 20, $this->page_hauteur - 12);
				$pdf->Cell(0, 3, 'page '.$pdf->PageNo().'/5',0, 0,'C');
				$pdf->AliasNbPages();
				
				// Repere de pliage
				$pdf->SetDrawColor(220,220,220);
				$pdf->Line(3,($this->page_hauteur)/3,6,($this->page_hauteur)/3);

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
		$pdf->Line ($this->marge_gauche, $this->page_hauteur - 20, $this->page_largeur - $this->marge_droite, $this->page_hauteur - 20);
		
		$this->str = $conf->global->MAIN_INFO_SOCIETE_NOM;
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$pdf->SetTextColor($this->color1[0], $this->color1[1], $this->color1[2]);
		$pdf->SetXY( $this->marge_gauche, $this->page_hauteur - 20);
		$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'C');
		
		$statut = getFormeJuridiqueLabel($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
		$this->str = $statut." au capital de ".$conf->global->MAIN_INFO_CAPITAL." euros";
		$this->str.= " - SIRET ".$conf->global->MAIN_INFO_SIRET;
		$this->str.= " - RCS ".$conf->global->MAIN_INFO_RCS;
		$this->str.= " - Code APE ".$conf->global->MAIN_INFO_APE;
		$this->str.= " - TVA intracommunautaire ".$conf->global->MAIN_INFO_TVAINTRA;

		$pdf->SetFont(pdf_getPDFFont($outputlangs),'I',7);
		$pdf->SetXY( $this->marge_gauche, $this->page_hauteur - 16);
		$pdf->MultiCell(0, 3, $outputlangs->convToOutputCharset($this->str),0,'C');

		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',6);
		$pdf->SetXY( $this->droite - 20, $this->page_hauteur - 12);
		$pdf->Cell(0, 3, 'page '.$pdf->PageNo().'/5',0, 0,'C');

	}

	
	/**
	 *   	\brief		Formatage d'une liste à puce hierarchisée
	 *   	\param		pdf     		PDF factory
	 *      \param		outputlang		Object lang for output
	 */
	function liste_a_puce($text)
	{
		// - 1er niveau: remplacement de '# ' en debut de ligne par une puce de niv 1 (petit rond noir)
		// - 2éme niveau: remplacement de '## ' en début de ligne par une puce de niv 2 (tiret)
		// - 3éme niveau: remplacement de '### ' en début de ligne par une puce de niv 3 (>)
		// Pour annuler le formatage (début de ligne sur la mage gauche : '!#'
		$str = "";
		$line = explode("\n", $text);
		foreach ($line as $row)
		{
			if (preg_match('/^\!# /', $row)) $str.= preg_replace('/^\!# /', '', $row)."\n";
			elseif (preg_match('/^# /', $row)) $str.= chr(149).' '.preg_replace('/^#/', '', $row)."\n";
			elseif (preg_match('/^## /', $row)) $str.= '   '.'-'.preg_replace('/^##/', '', $row)."\n";
			elseif (preg_match('/^### /', $row)) $str.= '   '.'  '.chr(155).' '.preg_replace('/^###/', '', $row)."\n";
			else $str.= '   '.$row."\n";
		}
		return $str;
	}

}

# llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
