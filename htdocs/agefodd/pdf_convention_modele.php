<?php
/* Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
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
	\version	$Id: s_liste.php 54 2010-03-30 18:58:28Z ebullier $
*/
require_once('./pre.inc.php');
require_once('./pdf_document.php');
require_once('./agefodd_session.class.php');
require_once('./agefodd_formation_catalogue.class.php');
require_once('./agefodd_facture.class.php');
require_once('./agefodd_contact.class.php');
require_once('./agefodd_convention.class.php');
require_once('./agefodd_session_place.class.php');

require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');
require_once(DOL_DOCUMENT_ROOT.'/societe.class.php');

class agf_pdf_document extends FPDF
{
	var $emetteur;	// Objet societe qui emet
	
	// Definition des couleurs utilisées de façon globales dans le document (charte)
	protected $color1 = array('190','190','190');	// gris clair
	protected $color2 = array('203', '70', '25');	// marron/orangé

	/**
	 *	\brief		Constructor
	 *	\param		db		Database handler
	 */
	function agf_pdf_document($db)
	{
		global $conf,$langs;
		

		$this->db = $db;
		$this->name = "ebic";
		$this->description = $langs->trans('Modèle de convention de formation');

		// Dimension page pour format A4 en paysage
		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=15;
		$this->marge_droite=15;
		$this->marge_haute=12;
		$this->marge_basse=10;
		$this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
		$this->milieu = $this->espaceH_dispo / 2;

		$this->hApresTitreArticle = 8;
		$this->hApresCorpsArticle = 6;

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
	
		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		
		if (! is_object($agf))
		{
			$id = $agf;
			$agf = new Agefodd_session($this->db,"",$id);
			$ret = $agf->fetch($id);
		}

		// Definition of $dir and $file
		$dir = DOL_DOCUMENT_ROOT.'/agefodd/documents';
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
			// Protection et encryption du pdf
			if ($conf->global->PDF_SECURITY_ENCRYPTION)
			{
				$pdf=new FPDI_Protection('P','mm',$this->format);
				$pdfrights = array('print'); // Ne permet que l'impression du document
				$pdfuserpass = ''; // Mot de passe pour l'utilisateur final
				$pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
				$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
			}
			else
			{
				$pdf=new FPDI('P','mm',$this->format);
			}

			//On ajoute les polices "maisons"
			define('FPDF_FONTPATH','../../../../agefodd/font/');
			$pdf->AddFont('URWPalladioL-Ital','','p052023l.php');
			$pdf->AddFont('URWPalladioL-BoldItal','','p052024l.php');
			$pdf->AddFont('Nasalization','','nasalization.php');
			$pdf->AddFont('Borg9','','BORG9.php');
			//$pdf->AddFont('Borg9','I','BORG9i.php');

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
				$pdf->SetFont('Arial','', 9);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);
				
				$posY = $this->marge_haute;
				$posX = $this->marge_gauche;
				
				
				// Header société
				$pdf->SetFont('Nasalization','',9);
				$pdf->SetTextColor($this->color2[0], $this->color2[1], $this->color2[2]);
				$pdf->SetXY( $this->marge_gauche + 25, $this->marge_haute -1);
				$pdf->Cell(0, 5, $conf->global->MAIN_INFO_SOCIETE_NOM,0,0,'L');
				
				$pdf->SetFont('Arial','',7);
				$pdf->SetXY( $this->marge_gauche + 25, $this->marge_haute +3);
				$this->str = $conf->global->MAIN_INFO_SOCIETE_ADRESSE."\n";
				$this->str.= $conf->global->MAIN_INFO_SOCIETE_CP.' '.$conf->global->MAIN_INFO_SOCIETE_VILLE;
				$this->str.= ' - FRANCE'."\n";
				$this->str.= 'tél : '.$conf->global->MAIN_INFO_SOCIETE_TEL."\n";
				$this->str.= 'fax : '.$conf->global->MAIN_INFO_SOCIETE_FAX."\n";
				$pdf->MultiCell(100,3, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				
				$pdf->SetXY( $this->page_largeur - $this->marge_droite - 100, $this->marge_haute +9);
				$this->str = 'courriel : '.$conf->global->MAIN_INFO_SOCIETE_MAIL."\n";
				$this->str.= 'site web : '.$conf->global->MAIN_INFO_SOCIETE_WEB."\n";
				$pdf->MultiCell(100,3, $outputlangs->convToOutputCharset($this->str), 0, 'R');
				
				$pdf->SetDrawColor($this->color2[0], $this->color2[1], $this->color2[2]);
				$pdf->Line ($this->marge_gauche + 0.5, $this->marge_haute + 15.7, $this->page_largeur - $this->marge_droite, $this->marge_haute + 15.7);
				
				// Logo en haut à gauche
				if (is_file(AGF_ORGANISME_LOGO)) $pdf->Image(AGF_ORGANISME_LOGO, $posX, $this->marge_haute, 20);
				
				// Mise en page de la baseline
				$pdf->SetFont('Borg9','',18);
				$this->str = $outputlangs->transnoentities(AGF_ORGANISME_BASELINE);
				$this->width = $pdf->GetStringWidth($this->str);
				// alignement du bord droit du container avec le haut de la page
				$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->width; 
				$baseline_angle = (M_PI/2); //angle droit
				$baseline_x = 8;
				$baseline_y = $baseline_ecart - 10;
				$baseline_width = $this->width;
				$pdf->SetTextColor($this->color1[0], $this->color1[1], $this->color1[2]);
				//$pdf->SetTextColor('120', '120', '120');
				//rotate
				$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($baseline_angle),sin($baseline_angle),-sin($baseline_angle),cos($baseline_angle),$baseline_x*$pdf->k,($pdf->h-$baseline_y)*$pdf->k,-$baseline_x*$pdf->k,-($pdf->h-$baseline_y)*$pdf->k));
				$pdf->SetXY($baseline_x, $baseline_y);
				//print
				$pdf->Cell($baseline_width,0,$this->str,0,2,"L",0);
				//antirotate
				$pdf->_out('Q');
				
				//TItre page de garde
				$pdf->SetFont('Nasalization','',25);
				$pdf->SetTextColor($this->color2[0], $this->color2[1], $this->color2[2]);
				$pdf->SetXY( $this->marge_gauche, $this->marge_haute + 60);
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
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);
				$posX = $this->marge_gauche;
				$posY = $this->marge_haute;
				
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','B', $this->defaultFontSize);
				$this->str = "Entre les soussignés :";
				$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str),0,0);
				$posY += 4 ;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$this->str = $agf_conv->intro1;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str));
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str), 4);
				$posY += $hauteur + $this->hApresCorpsArticle;
				
				$pdf->SetXY( $posX, $posY);
				$this->str = "Ci-après dénommée « l'organisme » d'une part,";
				$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str),0,0);
				$posY += 8;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','B', $this->defaultFontSize);
				$this->str = "Et";
				$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str),0,0);
				$posY += 4;
				
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$this->str= $agf_conv->intro2; 
				//$this->str.= " dûment habilité à ce faire,";
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str));
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str), 4);
				$posY += $hauteur + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$this->str = "Ci-après dénommée « le client » d'autre part,";	
				$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str),0,0);
				$posY += 4;


				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','B', $this->defaultFontSize);
				$this->str = "Est conclue la convention suivante, en application des dispositions du Livre IX du Code du Travail ";
				$this->str.= "portant organisation de la formation professionnelle continue dans le cadre de l'éducation permanente :";
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str));
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str), 4);
				$posY += $hauteur + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','B', $this->defaultFontSize + 3);
				$art = 0;
				$this->str = "Article ".++$art." - Objet";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;


				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$this->str = "La présente convention a pour objet la réalisation d'une prestation de formation";
				$this->str.= " par l'organisme auprès de membres du personnel du client";
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str));
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str),4);
				$posY += $hauteur + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','B', $this->defaultFontSize + 3);
				$this->str = "Article ".++$art." - Détails du stage";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;


				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$this->str = $this->liste_a_puce($agf_conv->art1);
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str));
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str),4);
				$posY += $hauteur + $this->hApresCorpsArticle;
				
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','B', $this->defaultFontSize + 3);
				$this->str = "Article ".++$art." - Programme et méthode";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$this->str = $agf_conv->art2;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str));
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str),4);
				$posY += $hauteur + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','B', $this->defaultFontSize + 3);
				$this->str = "Article ".++$art." - Effectif formé";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$this->str = $agf_conv->art3;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str));
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str),4);
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
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);
				$posX = $this->marge_gauche;
				$posY = $this->marge_haute;


				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','B', $this->defaultFontSize + 3);
				$this->str = "Article ".++$art." - Dispositions financières";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$this->str = $agf_conv->art4;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str));
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str),4);
				$posY += $hauteur + 2;
				
				
				// Tableau "bon de commande"
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFillColor($this->color1[0],$this->color1[1],$this->color1[2]);
				$pdf->SetFillColor(210,210,210);
				$pdf->SetFont('Arial','', $this->defaultFontSize - 1 );
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
					$hauteur = $this->NbLines($pdf, $w[0],$outputlangs->transnoentities($agf_comdetails->line[$i]->description),5);
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
				$posY += 2;
				
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
				$pdf->SetFont('Arial','I', $this->defaultFontSize - 2 );
				$pdf->Cell(0,4,$outputlangs->transnoentities('montants exprimés en euros'),0,0,'R',0);
				$posY += $this->hApresCorpsArticle + 4;
				
								
								
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','B', $this->defaultFontSize + 3);
				$this->str = "Article ".++$art." - Conditions de règlement";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$this->str = $agf_conv->art5;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str));
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str),4);
				$posY += $hauteur + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','B', $this->defaultFontSize + 3);
				$this->str = "Article ".++$art." - Dédit ou abandon";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$this->str = $agf_conv->art6;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str));
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str),4);
				$posY += $hauteur + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','B', $this->defaultFontSize + 3);
				$this->str = "Article ".++$art." - Litiges et compétence d'attribution";
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$this->str = $agf_conv->art7;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str));
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str),4);
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
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);
				$posX = $this->marge_gauche;
				$posY = $this->marge_haute;
				
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','B', $this->defaultFontSize + 3);
				$this->str = "Signatures";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$literal = array('un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze');
				// Date
				setlocale(LC_TIME, 'fr_FR', 'fr_FR.utf8', 'fr');
				$date = 'le '.strftime("%A %d %B %Y");
				
				$this->str = "Fait à  	Gigean, ".$date." , en deux (2) exemplaires originaux, dont un remis ce jour au client. ";
				$nombre = $pdf->PageNo(); 	// page suivante = annexe1
				$this->str.= "Ce document comporte {nb} (".$literal[$nombre].") pages.";
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str));
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str),4);
				$posY += $hauteur + $this->hApresCorpsArticle;
				
				// Entete signature
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','B', $this->defaultFontSize);
				$this->str = "Pour l'Organisme de formation";	
				$pdf->Cell($this->espaceH_dispo/2, 4, $outputlangs->transnoentities($this->str),0,0,'C');
				
				$pdf->SetXY(  $this->milieu, $posY);
				$this->str = "Pour le Client";	
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0,'C');
				
				$posY += 6;
				
				//signature de l'organisme de formation
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$this->str = $conf->global->MAIN_INFO_SOCIETE_NOM."\nreprésenté par ".AGF_ORGANISME_REPRESENTANT." (*)";
				$pdf->MultiCell($this->espaceH_dispo/2, 4, $outputlangs->transnoentities($this->str),0,'C');
				$hauteurA = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str),4);
				
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
				$hauteurB = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str),4);
				$hauteur = max($hauteurA, $hauteurB);
				$posY += $hauteur + 40;
				
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont('Arial','I', $this->defaultFontSize -3);
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
				$pdf->SetFont('Arial','', $this->defaultFontSize);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);
				$posX = $this->marge_gauche;
				$posY = $this->marge_haute;
		
				$infile = DOL_DOCUMENT_ROOT.'/agefodd/documents/fiche-pedago_'.$id.'_.pdf';
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
				$pdf->SetFont('Arial','',6);
				$pdf->SetXY( $this->droite - 20, $this->page_hauteur - 12);
				$pdf->Cell(0, 3, 'page '.$pdf->PageNo().'/{nb}',0, 0,'C');
				$pdf->AliasNbPages();
				
				// Repere de pliage
				$pdf->SetDrawColor(220,220,220);
				$pdf->Line(3,($this->page_hauteur)/3,6,($this->page_hauteur)/3);

			}
			$pdf->Close();
			$pdf->Output($file);
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
	 *	\brief		Calcule le nombre de lignes qu'occupe un MultiCell
	 *	\param		pdf		pdf object
	 *	\param		w		multicell width
	 *	\param		txt		text in the multicell
	 *	\param		hight		line hight ine the multicell (param 2 in multicell call)
	 */
	function NbLines(&$pdf, $w, $txt, $hight)
	{

		$cw = &$pdf->CurrentFont['cw'];
		if($w == 0) $w=$pdf->w-$pdf->rMargin-$pdf->x;
		$wmax = ($w-2*$pdf->cMargin)*1000/$pdf->FontSize;
		$s = str_replace("\r",'',$txt);
		$nb = strlen($s);
		if($nb>0 && $s[$nb-1] == "\n") $nb--;
		$sep = -1;
		$i = 0;
		$j = 0;
		$l = 0;
		$nl = 1;
		while($i < $nb)
		{
			$c = $s[$i];
			if($c=="\n")
			{
				$i++;
				$sep = -1;
				$j = $i;
				$l = 0;
				$nl++;
				continue;
			}
			if($c == ' ') $sep = $i;
			$l += $cw[$c];
			if($l > $wmax)
			{
				if($sep == -1)
				{
					if($i == $j) $i++;
				}
				else $i = $sep + 1;
				$sep = -1;
				$j = $i;
				$l = 0;
				$nl++;
			}
			else $i++;
		}
		return ($nl * $hight);
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
		$pdf->SetFont('Nasalization','',9);
		$pdf->SetTextColor($this->color1[0], $this->color1[1], $this->color1[2]);
		$pdf->SetXY( $this->marge_gauche, $this->page_hauteur - 20);
		$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'C');
		
		//$statut = getFormeJuridiqueLabel($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
		$statut = 'EURL';
		$this->str = $statut." au capital de ".$conf->global->MAIN_INFO_CAPITAL." euros";
		$this->str.= " - SIRET ".$conf->global->MAIN_INFO_SIRET;
		$this->str.= " - RCS ".$conf->global->MAIN_INFO_RCS;
		$this->str.= " - Code APE ".$conf->global->MAIN_INFO_APE;
		$this->str.= " - TVA intracommunautaire ".$conf->global->MAIN_INFO_TVAINTRA;

		$pdf->SetFont('Arial','I',7);
		$pdf->SetXY( $this->marge_gauche, $this->page_hauteur - 16);
		$pdf->MultiCell(0, 3, $outputlangs->convToOutputCharset($this->str),0,'C');

		$pdf->SetFont('Arial','',6);
		$pdf->SetXY( $this->droite - 20, $this->page_hauteur - 12);
		$pdf->Cell(0, 3, 'page '.$pdf->PageNo().'/{nb}',0, 0,'C');

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
			if (preg_match('/^\!# /', $row)) $str.= ereg_replace('!# ', '', $row)."\n";
			elseif (preg_match('/^# /', $row)) $str.= chr(149).' '.ereg_replace('#', '', $row)."\n";
			elseif (preg_match('/^## /', $row)) $str.= '   '.'-'.ereg_replace('##', '', $row)."\n";
			elseif (preg_match('/^### /', $row)) $str.= '   '.'  '.chr(155).' '.ereg_replace('###', '', $row)."\n";
			else $str.= '   '.$row."\n";
		}
		return $str;
	}

}

# llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
