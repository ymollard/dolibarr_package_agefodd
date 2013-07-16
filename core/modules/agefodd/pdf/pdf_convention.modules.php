<?php
/** Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2012       Florian Henry   <florian.henry@open-concept.pro>
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 3 of the License, or
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
 *	\file       agefodd/core/modules/agefodd/pdf/pdf_convention.modules.php
 *	\ingroup    agefodd
 *	\brief      PDF for contract / convention
 */

dol_include_once('/agefodd/core/modules/agefodd/agefodd_modules.php');
require_once('../class/agsession.class.php');
require_once('../class/agefodd_formation_catalogue.class.php');
require_once('../class/agefodd_facture.class.php');
require_once('../class/agefodd_contact.class.php');
require_once('../class/agefodd_convention.class.php');
require_once('../class/agefodd_place.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php');
require_once('../lib/agefodd.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');


class pdf_convention extends ModelePDFAgefodd
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

		$this->hApresTitreArticle = 10;
		$this->hApresCorpsArticle = 8;

		$this->colorfooter = agf_hex2rgb($conf->global->AGF_FOOT_COLOR);
		$this->colortext = agf_hex2rgb($conf->global->AGF_TEXT_COLOR);
		$this->colorhead = agf_hex2rgb($conf->global->AGF_HEAD_COLOR);

		$this->defaultFontSize = 9;

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
			$pdf->SetFont(pdf_getPDFFont($outputlangs));

			$pdf->Open();
			$pagenb=0;

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

			// On récupére le contenu du bon de commande ou de la facture
			$agf_comid= new Agefodd_facture($this->db);
			$result = $agf_comid->fetch($id,$socid);

			$agf_comdetails= new Agefodd_convention($this->db);
			if (!empty($agf_comid->facid)) {
				$result = $agf_comdetails->fetch_invoice_lines($agf_comid->facid);
			}
			elseif (!empty($agf_comid->comid)) {
				$result = $agf_comdetails->fetch_order_lines($agf_comid->comid);
			}elseif (!empty($agf_comid->propalid)) {
				$result = $agf_comdetails->fetch_propal_lines($agf_comid->propalid);
			} else {
				$result=false;
			}

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
				$pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);

				$posY = $this->marge_haute;
				$posX = $this->marge_gauche;

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
						$pdf->Image($logo, $this->page_largeur - $this->marge_gauche - $this->marge_droite - 50, $this->marge_haute, 0, $heightLogo, '', '', '', true, 300, '', false, false, 0, false, false, true);	// width=0 (auto)
					}
					else
					{
						$pdf->SetTextColor(200,0,0);
						$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', 8);
						$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'R');
						$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'R');
					}
				}
				else
				{
					$text=$this->emetteur->name;
					$pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
					$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', 11);
					$pdf->MultiCell(150, 3, $outputlangs->convToOutputCharset($text), 0, 'R');
				}

				//$posX += $this->page_largeur - $this->marge_droite - 65;

				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',11);
				$pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
				$pdf->SetXY($posX, $posY -1);
				$pdf->Cell(0, 5, $mysoc->name,0,0,'L');

				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',7);
				$pdf->SetXY($posX, $posY +3);
				$this->str = $mysoc->address."\n";
				$this->str.= $mysoc->zip.' '.$mysoc->town;
				$this->str.= ' - '.$mysoc->country."\n";
				if ($mysoc->phone) {
					$this->str.= $outputlangs->transnoentities('AgfPDFHead1').' '.$mysoc->phone."\n";
				}
				if ($mysoc->fax) {
					$this->str.= $outputlangs->transnoentities('AgfPDFHead2').' '.$mysoc->fax."\n";
				}
				if ($mysoc->email) {
					$this->str.= $outputlangs->transnoentities('AgfPDFHead3').' '.$mysoc->email."\n";
				}
				if ($mysoc->url) {
					$this->str.= $outputlangs->transnoentities('AgfPDFHead4').' '.$mysoc->url."\n";
				}

				$pdf->MultiCell(100,3, $outputlangs->convToOutputCharset($this->str), 0, 'L');

				$posY = $pdf->GetY() + 10;

				$pdf->SetTextColor($this->colorhead[0], $this->colorhead[1], $this->colorhead[2]);
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

				//TItre page de garde 1
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',25);
				$pdf->SetXY( $this->marge_gauche, $this->marge_haute + 110);

				$customer=new Societe($this->db);
				$customer->fetch($socid);
				// If customer is personnal entity, the french low ask contrat and not convention
				if ($customer->typent_id==8) {
					$titre = $outputlangs->transnoentities('AgfPDFConventionContrat');
				}
				else {
					$titre = $outputlangs->transnoentities('AgfPDFConvention');
				}

				$pdf->MultiCell(0, 5, $titre,0,'C');

				//TItre page de garde 2
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',10);
				$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
				$pdf->SetXY( $this->marge_gauche, $this->marge_haute + 130);

				// If customer is personnal entity, the french low ask contrat and not convention
				if ($customer->typent_id==8) {
					$titre = $outputlangs->transnoentities('AgfPDFConventionContratLawNum');
				}
				else {
					$titre = $outputlangs->transnoentities('AgfPDFConventionLawNum');
				}
				$pdf->MultiCell(0, 5, $titre,0,'C');

				$this->str = $agf->formintitule;
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',12);
				$pdf->SetXY( $this->marge_gauche, $this->marge_haute + 180);
				$pdf->MultiCell(0, 5, $this->str,0,'C');

				if ($agf->dated!=$agf->datef) {
					$this->str = dol_print_date($agf->dated,'daytext').' - '.dol_print_date($agf->datef,'daytext');
				}else {
					$this->str = dol_print_date($agf->dated,'daytext');
				}
				$pdf->SetXY( $this->marge_gauche, $this->marge_haute + 185);
				$pdf->MultiCell(0, 5, $this->str,0,'C');

				// If customer is personnal entity, the french low ask contrat and not convention
				if ($customer->typent_id==8) {
					$this->str = 'trainee:'.$customer->name;//Trainer NAme;
				}
				else {
					$this->str = $customer->name;//Customer NAme;
				}
				$pdf->SetXY( $this->marge_gauche, $this->marge_haute + 190);
				$pdf->MultiCell(0, 5, $this->str,0,'C');


				//Determine the total number of page
				$infile = $conf->agefodd->dir_output.'/fiche_pedago_'.$agf->fk_formation_catalogue.'.pdf';
				if (is_file($infile)) {
					$this->count_page_anexe = $pdf->setSourceFile($infile);
					$this->count_page_anexe = $this->count_page_anexe - 1;
				}
				else {
					$this->count_page_anexe = 0;
				}

				// Pied de page
				$this->_pagefoot($pdf,$agf,$outputlangs);
				$pdf->AliasNbPages();


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
				$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
				$posX = $this->marge_gauche;
				$posY = $this->marge_haute;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize);
				$this->str = $outputlangs->transnoentities('AgfPDFConv1');
				$pdf->MultiCell(0, 0, $outputlangs->transnoentities($this->str),0,'L');
				$posY = $pdf->GetY() + 1;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $agf_conv->intro1;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$posY = $pdf->GetY() + 1;

				$pdf->SetXY( $posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfPDFConv2');
				$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str),0,0);
				$posY += 8;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize);
				$this->str = $outputlangs->transnoentities('AgfPDFConv3');
				$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str),0,0);
				$posY += 4;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str= $agf_conv->intro2;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');

				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$this->str = $outputlangs->transnoentities('AgfPDFConv4');
				$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str),0,0);
				$posY += 4;


				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize);
				$this->str = $outputlangs->transnoentities('AgfPDFConv5')." ";
				$this->str.= $outputlangs->transnoentities('AgfPDFConv6');
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$art = 0;
				$this->str = $outputlangs->transnoentities('AgfPDFConv7').' '.++$art." - ".$outputlangs->transnoentities('AgfPDFConv8').' ';
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;


				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $outputlangs->transnoentities('AgfPDFConv9').' ';
				$this->str.= $outputlangs->transnoentities('AgfPDFConv10');
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv7').' '.++$art." - ".$outputlangs->transnoentities('AgfPDFConv11');
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $this->liste_a_puce($agf_conv->art1);
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;


				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv7').' '.++$art." - ".$outputlangs->transnoentities('AgfPDFConv12');
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $agf_conv->art2;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;


				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv7').' '.++$art." - ".$outputlangs->transnoentities('AgfPDFConv13');
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $agf_conv->art3;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

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
				$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
				$posX = $this->marge_gauche;
				$posY = $this->marge_haute;


				$pdf->SetXY($posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv7').' '.++$art.' - '.$outputlangs->transnoentities('AgfPDFConv14');
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY($posX, $posY);
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
				$header=array($outputlangs->transnoentities("Designation"), $outputlangs->transnoentities("VAT"), $outputlangs->transnoentities("PriceUHT"),$outputlangs->transnoentities("ReductionShort"),$outputlangs->transnoentities('Qté'),$langs->transnoentities("TotalHT"),$langs->transnoentities("TotalTTC"));
				$w=array(80,13,19,13,8,20,20);
				for($i=0;$i<count($header);$i++)
				{
					$pdf->Cell($w[$i],6,$header[$i],1,0,'C',1);
				}
				$posY += 6;
				$fill=false;
				$total_ht = 0;
				$total_tva = 0;
				$total_ttc = 0;
				for ($i = 0; $i < count($agf_comdetails->lines); $i++)
				{
					$pdf->SetXY($posX, $posY);
					$posY = $pdf->GetY();
					$pdf->writeHTMLCell($w[0], 0, $posX, $posY,$outputlangs->transnoentities($agf_comdetails->lines[$i]->description),1,1);
					$posY_after = $pdf->GetY();
					$hauteur=($posY_after-$posY);

					$pdf->SetXY($posX + $w[0], $posY);
					$pdf->Cell($w[1],$hauteur,vatrate($agf_comdetails->lines[$i]->tva_tx,1),1,0,'C',$fill);
					$pdf->Cell($w[2],$hauteur,price($agf_comdetails->lines[$i]->price,0,$outputlangs,1,-1,2),1,0,'R',$fill);
					$pdf->Cell($w[3],$hauteur,dol_print_reduction($agf_comdetails->lines[$i]->remise_percent,$outputlangs),1,0,'R',$fill);
					$pdf->Cell($w[4],$hauteur,$agf_comdetails->lines[$i]->qty,1,0,'C',$fill);
					$pdf->Cell($w[5],$hauteur,price($agf_comdetails->lines[$i]->total_ht,0,$outputlangs),1,0,'R',$fill);
					$pdf->Cell($w[6],$hauteur,price($agf_comdetails->lines[$i]->total_ttc,0,$outputlangs),1,0,'R',$fill);

					$pdf->Ln();
					$posY = $pdf->GetY();

					$total_ht += $agf_comdetails->lines[$i]->total_ht;
					$total_tva += $agf_comdetails->lines[$i]->total_tva;
					$total_ttc += $agf_comdetails->lines[$i]->total_ttc;
				}

				$pdf->SetXY( $posX, $posY);
				$pdf->Cell(array_sum($w),0,'','T');
				$posY += 6;

				// total HT
				$pdf->SetXY($posX + array_sum($w) - $w[5] -$w[6], $posY);
				$pdf->Cell($w[5],5,$langs->transnoentities("TotalHT"),0,0,'R',1);
				$pdf->Cell($w[6],5,price($total_ht,0,$outputlangs),1,0,'R');
				$posY += 6;
				// total TVA
				$pdf->SetXY($posX + array_sum($w) - $w[5] - $w[6], $posY);
				$pdf->Cell($w[5],5,$langs->transnoentities("TotalVAT"),0,0,'R',1);
				$pdf->Cell($w[6],5,price($total_tva,0,$outputlangs),1,0,'R');
				$posY += 6;
				// total TTC
				$pdf->SetXY($posX + array_sum($w) - $w[5] - $w[6], $posY);
				$pdf->Cell($w[5],5,$langs->transnoentities("TotalTTC"),0,0,'R',1);
				$pdf->Cell($w[6],5,price($total_ttc,0,$outputlangs),1,0,'R');
				$posY += 5;
				// txt "montant euros"
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'I', $this->defaultFontSize - 2 );
				$pdf->Cell(0,4,$outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$conf->currency)),0,0,'R',0);
				$posY += $this->hApresCorpsArticle + 4;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv7').' '.++$art." - ".$outputlangs->transnoentities('AgfPDFConv15');
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $agf_conv->art5;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;


				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv7').' '.++$art." - ".$outputlangs->transnoentities('AgfPDFConv16');
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $agf_conv->art6;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;



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

				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
				$posX = $this->marge_gauche;
				$posY = $this->marge_haute;


				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv7').' '.++$art." - ".$outputlangs->transnoentities('AgfPDFConv17');
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $agf_conv->art7;
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize + 3);
				$this->str = $outputlangs->transnoentities('AgfPDFConv18');
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0);
				$posY += $this->hApresTitreArticle;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$literal = array('un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze');
				// Date

				$date = $outputlangs->transnoentities('AgfPDFConv19').' '.dol_print_date(dol_now(),'daytext').' ';

				$this->str = $outputlangs->transnoentities('AgfPDFConv20').' '.$conf->global->MAIN_INFO_SOCIETE_VILLE.', '.$date.$outputlangs->transnoentities('AgfPDFConv21').' ';
				$nombre = $pdf->PageNo(); 	// page suivante = annexe1
				$this->str.= $outputlangs->transnoentities('AgfPDFConv22')." ".$nombre." (".$literal[$nombre-1].") ".$outputlangs->transnoentities('AgfPDFConv23').' ';
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'L');
				$posY = $pdf->GetY() + $this->hApresCorpsArticle;


				// Entete signature
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', $this->defaultFontSize);
				$this->str = $outputlangs->transnoentities('AgfPDFConv24');
				$pdf->Cell($this->espaceH_dispo/2, 4, $outputlangs->transnoentities($this->str),0,0,'C');

				$pdf->SetXY(  $this->milieu, $posY);
				$this->str = $outputlangs->transnoentities('AgfPDFConv25');
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0,'C');

				$posY += 6;

				//signature de l'organisme de formation
				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
				$this->str = $conf->global->MAIN_INFO_SOCIETE_NOM."\n". $langs->transnoentities('AgfConvArtSig')." ".$conf->global->AGF_ORGANISME_REPRESENTANT." (*)";
				$pdf->MultiCell($this->espaceH_dispo/2, 4, $outputlangs->transnoentities($this->str),0,'C');
				$hauteurA = dol_nboflines_bis($this->str,50)*3;
				
				// Incrustation image tampon
				if($conf->global->AGF_INFO_TAMPON)
				{
					$dir=$conf->agefodd->dir_output.'/images/';
					$img_tampon=$dir.$conf->global->AGF_INFO_TAMPON;
					if (file_exists($img_tampon))
						$pdf->Image($img_tampon, $posX + $this->marge_gauche, $pdf->GetY() + 6, 50);
				}

				// signature du client
				$pdf->SetXY( $this->milieu, $posY);
				if (!empty($agf_conv->sig)) $this->str = $agf_conv->sig;
				else
				{
					// if agefodd contact exist
					$this->str = $agf_soc->nom."\n";
					$this->str.= $langs->transnoentities('AgfConvArtSig').' ';
					$this->str.= ucfirst(strtolower($agf_contact->civilite)).' '.$agf_contact->firstname.' '.$agf_contact->name." (*)";
				}
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str),0,'C');
				$hauteurB = dol_nboflines_bis($this->str,50)*3;
				$hauteur = max($hauteurA, $hauteurB);
				$posY += $hauteur + 40;

				$pdf->SetXY( $posX, $posY);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'I', $this->defaultFontSize -3);
				$this->str = "(*) ".$outputlangs->transnoentities('AgfPDFConv26').' ';
				$this->str.= $outputlangs->transnoentities('AgfPDFConv27');
				$pdf->Cell(0, 4, $outputlangs->transnoentities($this->str),0,0,'C');
				$posY += 4 + $this->hApresCorpsArticle;


				// Pied de page
				$this->_pagefoot($pdf,$agf,$outputlangs);
				$pdf->AliasNbPages();



				/*
				 * Page 4 (Annexe 1)
				*/
				$infile = $conf->agefodd->dir_output.'/fiche_pedago_'.$agf->fk_formation_catalogue.'.pdf';
				if (is_file($infile)) {
					$count = $pdf->setSourceFile($infile);
					// import all page
					for ($i=1; $i<=$count; $i++) {
						// New page
						$pdf->AddPage();
						$pagenb++;
						$this->_pagehead($pdf, $agf, 1, $outputlangs);
						$this->defaultFontSize = 9;
						$pdf->SetFont(pdf_getPDFFont($outputlangs),'', $this->defaultFontSize);
						$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
						$pdf->SetTextColor($this->colortext[0], $this->colortext[1], $this->colortext[2]);
						$posX = $this->marge_gauche;
						$posY = $this->marge_haute;

						$tplIdx = $pdf->importPage($i);
						$pdf->useTemplate($tplIdx, 0, 0, $this->page_largeur);

						// Pied de page
						$pdf->SetTextColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
						$pdf->SetFont(pdf_getPDFFont($outputlangs),'',6);
						$pdf->SetXY( $this->droite - 20, $this->page_hauteur - 10);
						$pdf->Cell(0, 3, 'page '.$pdf->PageNo().'/'.intval(5+$this->count_page_anexe),0, 0,'C');
						$pdf->AliasNbPages();
					}
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
		$pdf->Line($this->marge_gauche, $this->page_hauteur - 20, $this->page_largeur - $this->marge_droite, $this->page_hauteur - 20);

		$this->str = $mysoc->name;

		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
		$pdf->SetTextColor($this->colorfooter[0], $this->colorfooter[1], $this->colorfooter[2]);
		$pdf->SetXY($this->marge_gauche, $this->page_hauteur - 20);
		$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'C');

		$this->str = $mysoc->address." ";
		$this->str.= $mysoc->zip.' '.$mysoc->town;
		$this->str.= ' - '.$mysoc->country;
		$this->str.= ' '.$outputlangs->transnoentities('AgfPDFFoot1').' '.$mysoc->phone;
		$this->str.= ' '.$outputlangs->transnoentities('AgfPDFFoot2').' '.$mysoc->email."\n";

		$statut = getFormeJuridiqueLabel($mysoc->forme_juridique_code);
		$this->str.= $statut;
		if (!empty($mysoc->capital)) {
			$this->str.=' '.$outputlangs->transnoentities('AgfPDFFoot3').' '.$mysoc->capital.' '.$langs->trans("Currency".$conf->currency);
		}
		if (!empty($mysoc->idprof2)) {
			$this->str.= ' '.$outputlangs->transnoentities('AgfPDFFoot4').' '.$mysoc->idprof2;
		}
		if (!empty($mysoc->idprof4)) {
			$this->str.= ' '.$outputlangs->transnoentities('AgfPDFFoot5').' '.$mysoc->idprof4;
		}
		if (!empty($mysoc->idprof3)) {
			$this->str.= ' '.$outputlangs->transnoentities('AgfPDFFoot6').' '.$mysoc->idprof3;
		}
		$this->str.="\n";
		if (!empty($conf->global->AGF_ORGANISME_NUM)) {
			$this->str.= ' '.$outputlangs->transnoentities('AgfPDFFoot7').' '.$conf->global->AGF_ORGANISME_NUM;
		}
		if (!empty($conf->global->AGF_ORGANISME_PREF)) {
			$this->str.= ' '.$outputlangs->transnoentities('AgfPDFFoot8').' '.$conf->global->AGF_ORGANISME_PREF;
		}
		if (!empty($mysoc->tva_intra)) {
			$this->str.=' '.$outputlangs->transnoentities('AgfPDFFoot9').' '.$mysoc->tva_intra;
		}

		$pdf->SetFont(pdf_getPDFFont($outputlangs),'I',7);
		$pdf->SetXY( $this->marge_gauche, $this->page_hauteur - 16);
		$pdf->MultiCell(0, 3, $outputlangs->convToOutputCharset($this->str),0,'C');

		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',6);
		$pdf->SetXY($this->droite - 20, $this->page_hauteur - 10);
		$pdf->Cell(0, 3, 'page '.$pdf->PageNo().'/'.intval(5+$this->count_page_anexe),0, 0,'C');

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