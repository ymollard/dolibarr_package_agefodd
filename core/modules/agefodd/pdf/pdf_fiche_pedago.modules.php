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
	\brief		Page permettant la création de la fiche pedagogique d'une formation au format pdf
	\version	$Id$
*/
dol_include_once('/agefodd/core/modules/agefodd/agefodd_modules.php');
dol_include_once('/agefodd/session/class/agefodd_session.class.php');
dol_include_once('/agefodd/training/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/contact/class/agefodd_contact.class.php');
dol_include_once('/core/lib/company.lib.php');
dol_include_once('/core/lib/pdf.lib.php');


class pdf_fiche_pedago extends ModelePDFAgefodd
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
	function pdf_fiche_pedago($db)
	{
		global $conf,$langs,$mysoc;
		
		$langs->load("agefodd@agefodd");
		
		$this->db = $db;
		$this->name = 'fiche_pedago';
		$this->description = $langs->trans('AgfModPDFFichePeda');

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
			$agf_session = new Agefodd_session($this->db);
			$ret = $agf_session->fetch($id);
			if ($ret)
			{
				$agf= new Agefodd($this->db);
				$agf->fetch($agf_session->formid);
			}
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
			$pdf->SetTitle($outputlangs->convToOutputCharset($agf->ref_interne));
			$pdf->SetSubject($outputlangs->transnoentities("Invoice"));
			$pdf->SetCreator("Dolibarr ".DOL_VERSION.' (Agefodd module)');
			$pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
			$pdf->SetKeyWords($outputlangs->convToOutputCharset($agf->ref_interne)." ".$outputlangs->transnoentities("Document"));
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
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
				$pdf->MultiCell(0, 3, '', 0, 'J');
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
					$pdf->SetTextColor(200,0,0);
					$pdf->SetFont('','B', pdf_getPDFFontSize($outputlangs) - 2);
					$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($text), 0, 'L');
				}
				
				$posX += $this->page_largeur - $this->marge_droite - 50;
				
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
				//print
				$pdf->Cell($baseline_width,0,$this->str,0,2,"L",0);
				

				/*
				 * Corps de page
				 */

				$posX = $this->marge_gauche;
				$posY = $posY + 5;
				
				/***** Titre *****/
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',15);
				$pdf->SetTextColor($this->color2[0], $this->color2[1], $this->color2[2]);
				$pdf->SetXY($posX, $posY);
				$this->str = "Fiche pédagogique";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'C');
				$posY+= 10;

				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',12);
				$pdf->SetTextColor(0,0,0);
				$this->str = $agf->intitule;
				$hauteur = dol_nboflines_bis($this->str,50)*4;
				
				// cadre
				$pdf->SetFillColor(255);
				$pdf->Rect($posX, $posY, $this->espaceH_dispo, $hauteur+3);
				// texte
				$pdf->SetXY( $posX, $posY);
				$pdf->MultiCell(0,5, $outputlangs->convToOutputCharset($this->str), 0, 'C');
				$posY+= $hauteur + 10;

				/***** Objectifs pedagogique de la formation *****/
				
				// Récuperation
				$agf_op = new Agefodd($this->db);
				$result2 = $agf_op->fetch_objpeda_per_formation($agf->id);
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B',9);//$pdf->SetFont('Arial','B',9);
				$pdf->SetXY($posX, $posY);
				$this->str = "Objectifs pédagogiques";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'L');
				$posY+= 5;
				
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);//$pdf->SetFont('Arial','',9);
				$hauteur = 0;
				$width = 160;
				for ( $y = 0; $y < count($agf_op->line); $y++)
				{
					if ($y > 0) $posY+= $hauteur;
					$pdf->SetXY ($posX, $posY);
					$hauteur = dol_nboflines_bis($this->str,50)*4;
					
					$pdf->Cell(10, 4, $agf_op->line[$y]->priorite.'. ', 0, 0, 'L', 0);
					$pdf->MultiCell($width, 4, $outputlangs->transnoentities($agf_op->line[$y]->intitule), 0,'L',0);
					
				}
				$posY+= 8;
				
				/***** Public *****/
				
				// Récuperation
				$agf_op->fetch($agf->id);
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B','');
				$pdf->SetXY($posX, $posY);
				$this->str = "Publics";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'L');
				$posY+= 5;
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'','');
				$this->str = ucfirst($agf_op->public);
				
				$hauteur = dol_nboflines_bis($this->str,50)*4;
				
				$pdf->SetXY( $posX, $posY);
				$pdf->MultiCell(0,5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY+= $hauteur + 8;

				
				/***** Pré requis *****/
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B','');
				$pdf->SetXY($posX, $posY);
				$this->str = "Pré-requis";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'L');
				$posY+= 5;
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'','');
				$this->str = $agf_op->prerequis;
				if (empty($this->str)) $this->str = "Aucun";
				
				$hauteur = dol_nboflines_bis($this->str,50)*4;
				
				$pdf->SetXY( $posX, $posY);
				$pdf->MultiCell(0,5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY+= $hauteur + 8;

				
				/***** Programme *****/
				
				// Récuperation
				//$agf_op->fetch($agf->formid);
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B','');
				$pdf->SetXY($posX, $posY);
				$this->str = "Programme";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'L');
				$posY+= 5;
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'','');
				$this->str =$agf_op->programme;
				$hauteur_ligne_dans_col = 5;				
				$hauteur=dol_nboflines_bis($this->str,50)*4;
				
				
				$hauteur_col = $hauteur / 2;
				$hauteur_nb_lines = ($hauteur / $hauteur_ligne_dans_col)  /2;
				$espace_entre_col = 10; // ici 1cm 
				$largeur_col = ($this->espaceH_dispo - $espace_entre_col) / 2;
				
				$pdf->SetXY( $posX, $posY);
				$pdf->MultiCell($largeur_col, $hauteur_ligne_dans_col,$outputlangs->transnoentities($this->str),0,'J');
				
				// Nbre de ligne * hauteur ligne + decallage titre niv 2
				$posY += $hauteur + 8;

				
				/***** Methode pedago *****/
				
				// Récuperation
				$agf_op->fetch($agf->id);
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B','');
				$pdf->SetXY($posX, $posY);
				$this->str = "Méthode pédagogique";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'L');
				$posY+= 5;
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'','');
				$this->str = $agf_op->methode;
				$hauteur = dol_nboflines_bis($this->str,50)*4;
				$pdf->SetXY( $posX, $posY);
				$pdf->MultiCell(0,5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
				$posY+= $hauteur + 8;
				

				/***** Duree *****/
				
				// Durée
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B','');
				$pdf->SetXY($posX, $posY);
				$this->str = "Durée";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'L');
				$posY+= 5;
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'','');
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
		$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);//$pdf->SetFont('Arial','',9);
		$pdf->SetTextColor($this->color1[0], $this->color1[1], $this->color1[2]);
		$pdf->SetXY( $this->marge_gauche, $this->page_hauteur - 20);
		$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'C');
		
		
		$statut = getFormeJuridiqueLabel($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
		$this->str = $statut." au capital de ".$conf->global->MAIN_INFO_CAPITAL." euros";
		$this->str.= " - SIRET ".$conf->global->MAIN_INFO_SIRET;
		$this->str.= " - RCS ".$conf->global->MAIN_INFO_RCS;
		$this->str.= " - Code APE ".$conf->global->MAIN_INFO_APE;
		$this->str.= " - TVA intracommunautaire ".$conf->global->MAIN_INFO_TVAINTRA;

		$pdf->SetFont(pdf_getPDFFont($outputlangs),'I',7);//$pdf->SetFont('Arial','I',7);
		$pdf->SetXY( $this->marge_gauche, $this->page_hauteur - 16);
		$pdf->MultiCell(0, 3, $outputlangs->convToOutputCharset($this->str),0,'C');

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
