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
	\brief		Page permettant la création du fichier pdf contenant les attestations de formation de 
			l'ensemble des stagiaires d'une structure pour une session donnée.
	\version	$Id: s_liste.php 54 2010-03-30 18:58:28Z ebullier $
*/
require_once('./pre.inc.php');
require_once('./pdf_document.php');
require_once('./agefodd_session.class.php');
require_once('./agefodd_formation_catalogue.class.php');

require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');


class agf_pdf_document extends FPDF
{
	var $emetteur;	// Objet societe qui emet
	
	// Definition des couleurs utilisées de façon globales dans le document (charte)
	// gris clair
	protected $color1 = array('190','190','190');
	// marron/orangé
	protected $color2 = array('203', '70', '25');


	/**
	 *	\brief		Constructor
	 *	\param		db		Database handler
	 */
	function agf_pdf_document($db)
	{
		global $conf,$langs;
		

		$this->db = $db;
		$this->name = "ebic";
		$this->description = $langs->trans('Modèle de document pour les attestatiions de formation');

		// Dimension page pour format A4 en paysage
		$this->type = 'pdf';
		$this->page_largeur = 297;
		$this->page_hauteur = 210;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=15;
		$this->marge_droite=15;
		$this->marge_haute=10;
		$this->marge_basse=10;
		$this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
		$this->milieu = $this->espaceH_dispo / 2; 

	}
	

	/**
	 *	\brief      	Fonction generant le document sur le disque
	 *	\param	    	agf		Objet document a generer (ou id si ancienne methode)
	*			outputlangs	Lang object for output language
	 *			file		Name of file to generate
	 *	\return	    	int     	1=ok, 0=ko
	 */
	function write_file($agf,$outputlangs, $file, $socid)
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
			
			// Récuperation des objectifs pedagogique de la formation
			$agf_op = new Agefodd($this->db,"",$id);
			$result2 = $agf_op->fetch_objpeda_per_formation($agf->formid);

			// Récupération de la duree de la formation
			$agf_duree = new Agefodd($this->db);
			$result = $agf_duree->fetch($agf->formid);
			
			// Recuperation des stagiaires participant à la formation
			$agf2 = new Agefodd_session($this->db,"",$id);
			$result = $agf2->fetch_stagiaire_per_session($id, $socid);

			if ($result)
			{
				for ($i = 0; $i < count($agf2->line); $i++ )
				{
					// New page
					$pdf->AddPage();
					$pagenb++;
					$this->_pagehead($pdf, $agf, 1, $outputlangs);
					$pdf->SetFont('Arial','', 9);
					$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
					$pdf->SetTextColor(0,0,0);
					
					// On met en place le cadre
					$pdf->SetDrawColor($this->color2[0], $this->color2[1], $this->color2[2]);
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
					
					// Logo en haut à gauche
					if (is_file(AGF_ORGANISME_LOGO)) $pdf->Image(AGF_ORGANISME_LOGO, $this->marge_gauche + 3, $this->marge_haute + 3, 40);
					
					$newY = $this->marge_haute + 30;
					$pdf->SetXY ($this->marge_gauche + 1, $newY);
					$pdf->SetTextColor(76,76,76);
					$pdf->SetFont('Arial','B', 20);
					$pdf->Cell(0, 0, "Attestation de formation", 0, 0,'C', 0);
					$pdf->SetTextColor('','','');
					
					$newY = $newY + 10;
					$pdf->SetXY ($this->marge_gauche + 1, $newY);
					$pdf->SetFont('Arial','', 12);
					$this->str1 = "Ce document atteste que  " .ucfirst(strtolower($agf2->line[$i]->civilitel)).' ';
					$this->width1 = $pdf->GetStringWidth($this->str1);
					
					$pdf->SetFont('URWPalladioL-Ital','', 16);
					$this->str2 = $outputlangs->transnoentities($agf2->line[$i]->prenom.' '.$agf2->line[$i]->nom);
					$this->width2 = $pdf->GetStringWidth($this->str2);
					
					$pdf->SetFont('Arial','', 12);
					$this->debut_cell = ($this->marge_gauche + 1) + ($this->milieu - (($this->width1 + $this->width2)/2));
					$newY = $newY + 10;
					$pdf->SetXY ($this->debut_cell , $newY);
					$pdf->Cell($this->width1, 0, $this->str1, 0, 0, 'C', 0);
					
					$pdf->SetFont('URWPalladioL-Ital','', 16);
					$pdf->Cell($this->width2, -1, $this->str2, 0, 0, 'C', 0);
					
					$pdf->SetFont('Arial','', 12);
					$newY = $newY + 6;
					$pdf->SetXY ($this->marge_gauche + 1, $newY);
					$this->str = 'a effectivement suivi avec assiduité le module de formation intitulé';
					$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str), 0, 0, 'C', 0);
					
					$pdf->SetFont('URWPalladioL-BoldItal','', 18);
					$newY = $newY + 10;
					$pdf->SetXY ($this->marge_gauche + 1, $newY);
					$pdf->Cell(0, 0, $outputlangs->transnoentities('« '.$agf->formintitule.' »'), 0, 0, 'C', 0);
					
					$this->str = "Cette formation s'est déroulée ";
					if ($agf->dated == $agf->datef) $this->str.= "le ".dol_print_date($agf->datef);
					else $this->str.= "du ".dol_print_date($agf->dated).' au '.dol_print_date($agf->datef);
					$this->str.= " (pour un total de ".$agf_duree->duree."h effectives).";
					$pdf->SetFont('Arial','', 12);
					$newY = $newY + 10;
					$pdf->SetXY ($this->marge_gauche + 1, $newY);
					$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str), 0, 0, 'C', 0);
					
					$this->str = "A l'issue de cette formation, le stagiaire est arrivé aux objectifs suivants :";
					$newY = $newY + 10;
					$pdf->SetXY ($this->marge_gauche + 1, $newY);
					$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str), 0, 0, 'C', 0);
					
					// Bloc objectifs pedagogiques
					$newY = $newY + 5;
					$pdf->SetFont('Arial','I', 12);
					$hauteur = 0;
					for ( $y = 0; $y < count($agf_op->line); $y++)
					{
						$newY = $newY + $hauteur;
						$pdf->SetXY ($this->marge_gauche + 50, $newY);
						$width = 160;
						$StringWidth = $pdf->GetStringWidth($agf_op->line[$y]->intitule);
						if ($StringWidth > $width) $nblines = ceil($StringWidth/$width);
						else $nblines = 1;
						$hauteur = $nblines * 5;
						$pdf->Cell(10, 5, $agf_op->line[$y]->priorite.'. ', 0, 0, 'R', 0);
						$pdf->MultiCell($width, 5, 
						$outputlangs->transnoentities($agf_op->line[$y]->intitule), 0,'L',0);
					
					}
					
					$pdf->SetFont('Arial','', 11);
					$newY = $newY + 20;
					$pdf->SetXY ($this->marge_gauche + 1, $newY);
					$this->str = "Avec les félicitations du pôle formation de ".AGF_ORGANISME_NAME.",";
					$pdf->Cell(0, 0, $outputlangs->transnoentities($this->str), 0, 0, 'C', 0);
					
					
					$newY = $newY + 20;
					$pdf->SetXY ($this->marge_gauche + 1, $newY);
					$this->str = "fait à ".AGF_ORGANISME_SIEGE.", le ";
					$pdf->Cell(80, 0, $outputlangs->transnoentities($this->str), 0, 0, 'R', 0);
					
					$pdf->SetFont('URWPalladioL-Ital','', 12);
					$this->str = date("d/m/Y");
					$this->str = dol_print_date($agf->datef);
					$this->width = $pdf->GetStringWidth($this->str);
					$pdf->Cell($this->width, 0, $this->str, 0, 0, 'L', 0);
					
					$pdf->SetFont('Arial','', 12);
					$this->str = AGF_ORGANISME_REPRESENTANT;
					$pdf->Cell(100, 0, $this->str, 0, 0, 'R', 0);
					
					
					// Pied de page		$pdf->SetFont('Arial','', 10);
					$this->_pagefoot($pdf,$agf,$outputlangs);
					$pdf->AliasNbPages();

					// Mise en place du copyright
					$pdf->SetFont('Arial','',8);
					$this->str = $outputlangs->transnoentities('copyright '.date("Y").' - '.AGF_ORGANISME_NAME);
					$this->width = $pdf->GetStringWidth($this->str);
					// alignement du bord droit du container avec le haut de la page
					$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->width;
					$baseline_angle = (M_PI/2); //angle droit
					$baseline_x = $this->page_largeur - $this->marge-gauche - 12;
					$baseline_y = $baseline_ecart + 30;
					$baseline_width = $this->width;
					$pdf->SetTextColor($this->color1[0], $this->color1[1], $this->color1[2]);
					//rotate
					$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($baseline_angle),sin($baseline_angle),-sin($baseline_angle),cos($baseline_angle),$baseline_x*$pdf->k,($pdf->h-$baseline_y)*$pdf->k,-$baseline_x*$pdf->k,-($pdf->h-$baseline_y)*$pdf->k));
					$pdf->SetXY($baseline_x, $baseline_y);
					//print
					$pdf->Cell($baseline_width,0,$this->str,0,2,"L",0);
					//antirotate
					$pdf->_out('Q');

				}
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

		$this->str = AGF_ORGANISME_NAME." - Organisme de formation enregistré à la préfecture de ".AGF_ORGANISME_PREF." sous le n° ".AGF_ORGANISME_NUM;
		$pdf->SetXY ($this->marge_gauche +1, $this->page_hauteur - $this->marge_basse);
		$pdf->SetFont('Arial','I', 8);
		$pdf->SetTextColor($this->color1[0], $this->color1[1], $this->color1[2]);
		$pdf->Cell(0, 6, $outputlangs->transnoentities($this->str),0, 0, 'C', 0);
	}
}

# llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
