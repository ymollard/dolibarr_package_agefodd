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
	\brief		Page permettant la création de la fiche pedagogique d'une formation au format pdf
	\version	$Id$
*/
require_once(DOL_DOCUMENT_ROOT."/agefodd/core/models/pdf/pdf_document.php");
require_once(DOL_DOCUMENT_ROOT."/agefodd/class/agefodd_session.class.php");
require_once(DOL_DOCUMENT_ROOT."/agefodd/class/agefodd_formation_catalogue.class.php");
require_once(DOL_DOCUMENT_ROOT."/agefodd/class/agefodd_facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/agefodd/class/agefodd_contact.class.php");


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
		$this->description = $langs->trans('Modèle de document pour les fiches pédagogiques');

		// Dimension page pour format A4 en paysage
		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=15;
		$this->marge_droite=15;
		$this->marge_haute=10;
		$this->marge_basse=10;
		$this->espaceH_dispo = $this->page_largeur - ($this->marge_gauche + $this->marge_droite);
		$this->milieu = $this->espaceH_dispo / 2;
		$this->espaceV_dispo = $this->page_hauteur - ($this->marge_haute + $this->marge_basse);
		
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
			//define('FPDF_FONTPATH','../../../../agefodd/font/');
			//$pdf->AddFont('URWPalladioL-Ital','','p052023l.php');
			//$pdf->AddFont('URWPalladioL-BoldItal','','p052024l.php');
			//$pdf->AddFont('Nasalization','','nasalization.php');
			//$pdf->AddFont('Borg9','','BORG9.php');
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
			
			// On recupere les infos societe
			$agf_soc = new Societe($this->db);
			$result = $agf_soc->fetch($socid);

			if ($result)
			{
				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $agf, 1, $outputlangs);
				$pdf->SetFont('Arial','', 9);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);
				
				$posY = $this->marge_haute;
				$posX = $this->marge_gauche;
				

				
				/*
				 * Header société
				 */

				$pdf->SetFont('Arial','',9);
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
				$pdf->SetFont('Arial','',18);
				$this->str = $outputlangs->transnoentities(AGF_ORGANISME_BASELINE);
				$this->width = $pdf->GetStringWidth($this->str);
				// alignement du bord droit du container avec le haut de la page
				$baseline_ecart = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->width; 
				$baseline_angle = (M_PI/2); //angle droit
				$baseline_x = 8;
				$baseline_y = $this->espaceV_dispo - $baseline_ecart + 30;
				$baseline_width = $this->width;
				$pdf->SetTextColor($this->color1[0], $this->color1[1], $this->color1[2]);
				//rotate
				$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($baseline_angle),sin($baseline_angle),-sin($baseline_angle),cos($baseline_angle),$baseline_x*$pdf->k,($pdf->h-$baseline_y)*$pdf->k,-$baseline_x*$pdf->k,-($pdf->h-$baseline_y)*$pdf->k));
				$pdf->SetXY($baseline_x, $baseline_y);
				//print
				$pdf->Cell($baseline_width,0,$this->str,0,2,"L",0);
				//antirotate
				$pdf->_out('Q');
				

				/*
				 * Corps de page
				 */

				$posX = $this->marge_gauche;
				$posY = 30;
				
				/***** Titre *****/
				$pdf->SetFont('Arial','',15);
				$pdf->SetTextColor($this->color2[0], $this->color2[1], $this->color2[2]);
				$pdf->SetXY($posX, $posY);
				$this->str = "Fiche pédagogique";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'C');
				$posY+= 15;

				$pdf->SetFont('','',12);
				$pdf->SetTextColor(0,0,0);
				$this->str = $agf->formintitule;
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str), 5);
				// cadre
				$pdf->SetFillColor(255);
				$this->RoundedRect($pdf, $posX, $posY, $this->espaceH_dispo, $hauteur, 1.5, 'DF');
				// texte
				$pdf->SetXY( $posX, $posY);
				$pdf->MultiCell(0,5, $outputlangs->convToOutputCharset($this->str), 0, 'C');
				$posY+= $hauteur + 10;

				/***** Objectifs pedagogique de la formation *****/
				
				// Récuperation
				$agf_op = new Agefodd($this->db,"",$id);
				$result2 = $agf_op->fetch_objpeda_per_formation($agf->formid);
				
				$pdf->SetFont('Arial','B',9);
				$pdf->SetXY($posX, $posY);
				$this->str = "Objectifs pédagogiques";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'L');
				$posY+= 5;
				
				
				$pdf->SetFont('Arial','',9);
				$hauteur = 0;
				$width = 160;
				for ( $y = 0; $y < count($agf_op->line); $y++)
				{
					if ($y > 0) $posY+= $hauteur;
					$pdf->SetXY ($posX, $posY);
					$hauteur = $this->NbLines($pdf, $width, $outputlangs->transnoentities($agf_op->line[$y]->intitule), 4);
					//$StringWidth = $pdf->GetStringWidth($agf_op->line[$y]->intitule);
					//if ($StringWidth > $width) $nblines = ceil($StringWidth/$width);
					//else $nblines = 1;
					//$hauteur = $nblines * 5;
					$pdf->Cell(10, 4, $agf_op->line[$y]->priorite.'. ', 0, 0, 'L', 0);
					$pdf->MultiCell($width, 4, 
					$outputlangs->transnoentities($agf_op->line[$y]->intitule), 0,'L',0);
					
				}
				$posY+= 8;
				
				/***** Public *****/
				
				// Récuperation
				$agf_op->fetch($agf->formid);
				
				$pdf->SetFont('','B','');
				$pdf->SetXY($posX, $posY);
				$this->str = "Publics";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'L');
				$posY+= 5;
				
				$pdf->SetFont('','','');
				$this->str = ucfirst($agf_op->public);
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str), 5);
				$pdf->SetXY( $posX, $posY);
				$pdf->MultiCell(0,5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY+= $hauteur + 8;

				
				/***** Pré requis *****/
				
				$pdf->SetFont('','B','');
				$pdf->SetXY($posX, $posY);
				$this->str = "Pré-requis";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'L');
				$posY+= 5;
				
				$pdf->SetFont('','','');
				$this->str = $agf_op->prerequis;
				if (empty($this->str)) $this->str = "Aucun";
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str), 5);
				$pdf->SetXY( $posX, $posY);
				$pdf->MultiCell(0,5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY+= $hauteur + 8;

				
				/***** Programme *****/
				
				// Récuperation
				//$agf_op->fetch($agf->formid);
				
				$pdf->SetFont('','B','');
				$pdf->SetXY($posX, $posY);
				$this->str = "Programme";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'L');
				$posY+= 5;
				
				$pdf->SetFont('','','');
				$this->str = $this->liste_a_puce($agf_op->programme);
				
				$hauteur_ligne_dans_col = 5;
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str),$hauteur_ligne_dans_col);
				
				$hauteur_col = $hauteur / 2;
				$hauteur_nb_lines = ($hauteur / $hauteur_ligne_dans_col)  /2;
				$espace_entre_col = 10; // ici 1cm 
				$largeur_col = ($this->espaceH_dispo - $espace_entre_col) / 2;
				
				$pdf->SetXY( $posX, $posY);
				$txt = $this->MultiCell_C($pdf, $largeur_col, $hauteur_ligne_dans_col,$outputlangs->transnoentities($this->str),0,'J',0, $hauteur_nb_lines);
				
				$pdf->Line ($this->milieu + $this->marge_gauche, $posY, $this->milieu  + $this->marge_gauche, $posY + $hauteur_col);
				
				$pdf->SetXY( $posX + $largeur_col + $espace_entre_col, $posY);
				$txt = $this->MultiCell_C($pdf, $largeur_col, $hauteur_ligne_dans_col, $txt,0,'J',0, $hauteur_nb_lines);
				
				// Nbre de ligne * hauteur ligne + decallage titre niv 2
				$posY += $hauteur_col + 8;

				
				/***** Methode pedago *****/
				
				// Récuperation
				$agf_op->fetch($agf->formid);
				
				$pdf->SetFont('','B','');
				$pdf->SetXY($posX, $posY);
				$this->str = "Méthode pédagogique";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'L');
				$posY+= 5;
				
				$pdf->SetFont('','','');
				$this->str = $agf_op->methode;
				$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str), 5);
				$pdf->SetXY( $posX, $posY);
				$pdf->MultiCell(0,5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY+= $hauteur + 8;
				

				/***** Duree *****/
				
				// Durée
				//$agf_op->fetch($agf->formid);
				
				$pdf->SetFont('','B','');
				$pdf->SetXY($posX, $posY);
				$this->str = "Durée";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'L');
				$posY+= 5;
				
				$pdf->SetFont('','','');
				// calcul de la duree en nbre de jours
				$jour = $agf_op->duree / 7;
				if ($jour < 1) $this->str = $agf_op->duree.' heures.';
				else
				{
					$this->str = $agf_op->duree.' heures ('.ceil($jour).' jour';
					if (ceil($jour) > 1) $this->str.='s';
					$this->str.=').';
				}
				$pdf->SetXY( $posX, $posY);
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'L');
				$posY+= 5 + 8;




				// Pied de page	
				$this->_pagefoot($pdf,$agf,$outputlangs);
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
	 *	\brief		Dessine un rectangle aux coins arrondis
	 *	\param		pdf	pdf object
	 *	\param		y	coin supérieur gauche du rectangle.
	 *	\param		w	largeur.
	 *	\param		h	hauteur.
	 *	\param		r	rayon des coins arrondis.
	 *	\param		style	comme celui de Rect() : F, D (valeur par défaut), FD ou DF
	 */

	/**
	 *  Exemple d'utilisation:
	 *	$pdf->SetLineWidth(0.5);
	 *	$pdf->SetFillColor(192);
	 *	$pdf->RoundedRect(70, 30, 68, 46, 3.5, 'DF');
	 */

	function RoundedRect(&$pdf, $x, $y, $w, $h, $r, $style = '')
	{
		$k = $pdf->k;
		$hp = $pdf->h;
		if($style=='F')
		$op='f';
		elseif($style=='FD' || $style=='DF')
		$op='B';
		else
		$op='S';
		$MyArc = 4/3 * (sqrt(2) - 1);
		$pdf->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));
		$xc = $x+$w-$r ;
		$yc = $y+$r;
		$pdf->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));
	
		$this->_Arc($pdf, $xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
		$xc = $x+$w-$r ;
		$yc = $y+$h-$r;
		$pdf->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
		$this->_Arc($pdf, $xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
		$xc = $x+$r ;
		$yc = $y+$h-$r;
		$pdf->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
		$this->_Arc($pdf, $xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
		$xc = $x+$r ;
		$yc = $y+$r;
		$pdf->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
		$this->_Arc($pdf, $xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
		$pdf->_out($op);
	}

	function _Arc(&$pdf, $x1, $y1, $x2, $y2, $x3, $y3)
	{
		$h = $pdf->h;
		$pdf->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$pdf->k, ($h-$y1)*$pdf->k,$x2*$pdf->k, ($h-$y2)*$pdf->k, $x3*$pdf->k, ($h-$y3)*$pdf->k));
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
		$pdf->SetFont('Arial','',9);
		$pdf->SetTextColor($this->color1[0], $this->color1[1], $this->color1[2]);
		$pdf->SetXY( $this->marge_gauche, $this->page_hauteur - 20);
		$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'C');
		
		$statut = getFormeJuridiqueLabel($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
		$this->str = $statut." au capital de ".$conf->global->MAIN_INFO_CAPITAL." euros";
		$this->str.= " - SIRET ".$conf->global->MAIN_INFO_SIRET;
		$this->str.= " - RCS ".$conf->global->MAIN_INFO_RCS;
		$this->str.= " - Code APE ".$conf->global->MAIN_INFO_APE;
		$this->str.= " - TVA intracommunautaire ".$conf->global->MAIN_INFO_TVAINTRA;

		$pdf->SetFont('Arial','I',7);
		$pdf->SetXY( $this->marge_gauche, $this->page_hauteur - 16);
		$pdf->MultiCell(0, 3, $outputlangs->convToOutputCharset($this->str),0,'C');

	}


	/**
	 *   	\brief		Output text with automatic or explicit line breaks, at most $maxline lines
	 *   	\param		pdf     		PDF factory
	 *	\param		w	largeur.
	 *	\param		h	hauteur.
	 *	\param		txt	text.
	 * 	\param		border	border or no
	 *      \param		aligne
	 * 	\param		fill	fill or no
	 * 	\param		maxline max number of line (before breaking)
	 */
	function MultiCell_C(&$pdf, $w, $h, $txt, $border=0, $align='J', $fill=false, $maxline=0)
	{
		//Output text with automatic or explicit line breaks, at most $maxline lines
		$cw=&$pdf->CurrentFont['cw'];
		if($w==0) $w=$pdf->w - $pdf->rMargin - $pdf->x;
		$wmax=($w-2 * $pdf->cMargin) * 1000 / $pdf->FontSize;
		$s=str_replace("\r",'',$txt);
		$nb=strlen($s);
		if($nb>0 && $s[$nb-1]=="\n") $nb--;
		$b=0;
		if($border)
		{
			if($border==1)
			{
				$border='LTRB';
				$b='LRT';
				$b2='LR';
			}
			else
			{
				$b2='';
				if(is_int(strpos($border,'L'))) $b2.='L';
				if(is_int(strpos($border,'R'))) $b2.='R';
				$b=is_int(strpos($border,'T')) ? $b2.'T' : $b2;
			}
		}
		$sep=-1;
		$i=0;
		$j=0;
		$l=0;
		$ns=0;
		$nl=1;
		while($i<$nb)
		{
			//Get next character
			$c=$s[$i];
			if($c=="\n")
			{
				//Explicit line break
				if($pdf->ws>0)
				{
					$pdf->ws=0;
					$pdf->_out('0 Tw');
				}
				$pdf->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
				$i++;
				$sep=-1;
				$j=$i;
				$l=0;
				$ns=0;
				$nl++;
				if($border && $nl==2) $b=$b2;
				if ( $maxline && $nl > $maxline ) return substr($s,$i);
				continue;
			}
			if($c==' ')
			{
				$sep=$i;
				$ls=$l;
				$ns++;
			}
			$l+=$cw[$c];
			if($l>$wmax)
			{
				//Automatic line break
				if($sep==-1)
				{
					if($i==$j) $i++;
					if($pdf->ws>0)
					{
						$pdf->ws=0;
						$pdf->_out('0 Tw');
					}
					$pdf->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
				}
				else
				{
					if($align=='J')
					{
						$pdf->ws=($ns>1) ? ($wmax-$ls)/1000 * $pdf->FontSize/($ns-1) : 0;
						$pdf->_out(sprintf('%.3F Tw',$pdf->ws * $pdf->k));
					}
					$pdf->Cell($w,$h,substr($s,$j,$sep-$j),$b,2,$align,$fill);
					$i=$sep+1;
				}
				$sep=-1;
				$j=$i;
				$l=0;
				$ns=0;
				$nl++;
				if ($border && $nl==2) $b=$b2;
				if ($maxline && $nl > $maxline) return substr($s,$i);
			}
			else $i++;
		}
		//Last chunk
		if($pdf->ws>0)
		{
			$pdf->ws=0;
			$pdf->_out('0 Tw');
		}
		if($border && is_int(strpos($border,'B'))) $b.='B';
		$pdf->Cell($w,$h,substr($s,$j,$i-$j),$b,2,$align,$fill);
		$pdf->x=$pdf->lMargin;
		return '';
		
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
