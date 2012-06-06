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
	\brief		Page permettant la création d'un courrier type au format pdf
			Le paramètre "courrier" transmis à la fonction "write_file" permet de préciser
			le contenu (body) à y inclure ( /agefodd/core/models/pdf/pdf_courrier-'.$courrier.'_modele.php').
	\version	$Id$
*/

dol_include_once('/agefodd/core/modules/agefodd/agefodd_modules.php');
dol_include_once('/agefodd/session/class/agefodd_session.class.php');
dol_include_once('/agefodd/training/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/contact/class/agefodd_contact.class.php');
dol_include_once('/core/lib/company.lib.php');
dol_include_once('/core/lib/pdf.lib.php');



class pdf_courrier extends ModelePDFAgefodd
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
	function pdf_courrier($db)
	{
		global $conf,$langs,$mysoc;
		

		$this->db = $db;
		$this->name = "courrier";
		$this->description = $langs->trans('AgfModPDFCourrier');

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
	
		if (! is_object($outputlangs)) $outputlangs=$langs;
		
		if (! is_object($agf))
		{
			$id = $agf;
			$agf = new Agefodd_session($this->db);
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
				
				$posX = 100;
				$posY = 42;
				//$pdf->rect($posX, $posY, 90, 40,);

				// Destinataire
				
				// Show recipient name
				$pdf->SetTextColor(0,0,0);
				$pdf->SetXY($posX+2,$posY+3);
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B',12);
				$this->str = $agf_soc->nom;
				$pdf->MultiCell(96,4,$outputlangs->convToOutputCharset($this->str), 0, 'L');
				
				// Show recipient information
				
				// if agefodd contact exist
				$agf_contact = new Agefodd_contact($this->db);
				$result = $agf_contact->fetch($socid);
				// on en profite pour préparer la ligne "madame, monsieur"
				$this->madame_monsieur = 'Madame, Monsieur,';
				$this->str = '';
				if ($agf_contact->name) 
				{
					$this->str.= ucfirst(strtolower($agf_contact->civilite)).' '.$agf_contact->name.' '.$agf_contact->firstname."\n";
					if (!empty($agf_contact->address))
					{
						$this->str.= $agf_contact->address."\n".$agf_contact->cp.' '.$agf_contact->ville;
					}
					else $this->str.= $agf_soc->adresse_full;
					//$this->str.= $agf_contact->address."\n".$agf_contact->cp.' '.$agf_contact->ville;
					$civ = array("MR" => "Monsieur", "MME" => "Madame", "MLE" => "Mademoiselle");
					($civ[$agf_contact->civilite]) ? $this->madame_monsieur = $civ[$agf_contact->civilite].',' : $this->madame_monsieur;
				}
				// else socity contact
				else
				{
					$this->str = $agf_soc->adresse_full;
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',11);
				$posY = $pdf->GetY()-9; //Auto Y coord readjust for multiline name
				$pdf->SetXY($posX+2,$posY+10);
				$pdf->MultiCell(86,5, $outputlangs->convToOutputCharset($this->str), 0, 'L');

				// Date
				$posY = $posY + 50;
				$this->str = ucfirst(strtolower($conf->global->MAIN_INFO_SOCIETE_VILLE)).', le '.dol_print_date(dol_now(),'daytext');
				$pdf->SetXY($posX+2,$posY+6);
				$pdf->Cell(0,0,$outputlangs->convToOutputCharset($this->str),0,0,"R",0);
				
				// Corps du courrier
				$posY = $this->_body($pdf, $agf, $outputlangs, $courrier, $id, $socid);
				
				// Signataire
				$pdf->SetXY($posX + 10, $posY + 10);
				$this->str = $conf->global->AGF_ORGANISME_REPRESENTANT."\nresponsable formation";
				$pdf->MultiCell(50,4, $outputlangs->convToOutputCharset($this->str),0,'C');

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
	 *   	\brief      Show body of page
	 *      \param      pdf             	Object PDF
	 *      \param      object          	Object invoice
	 *      \param      outputlangs		Object lang for output
	 *      \param      courrier		Name of couurier type (for include)
	 *      \param      id			session id
	 */
	function _body(&$pdf, $object, $outputlangs, $courrier, $id, $socid)
	{
		global $user, $conf, $langs;
		
		require(dol_buildpath('/agefodd/core/modules/agefodd/pdf/pdf_courrier_'.$courrier.'.modules.php'));
		
		return $posY;
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


		//return pdf_pagefoot($pdf,$outputlangs,'FACTURE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object);
	}
}

# llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
