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
	\brief		Page permettant la création d'un courrier type au format pdf
			Le paramètre "courrier" transmis à la fonction "write_file" permet de préciser
			le contenu (body) à y inclure ( /agefodd/core/models/pdf/pdf_courrier-'.$courrier.'_modele.php').
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
		$this->description = $langs->trans('Modèle de document pour les courriers');

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
				

				
				// Header société
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
				//$pdf->SetTextColor('120', '120', '120');
				//rotate
				$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($baseline_angle),sin($baseline_angle),-sin($baseline_angle),cos($baseline_angle),$baseline_x*$pdf->k,($pdf->h-$baseline_y)*$pdf->k,-$baseline_x*$pdf->k,-($pdf->h-$baseline_y)*$pdf->k));
				$pdf->SetXY($baseline_x, $baseline_y);
				//print
				$pdf->Cell($baseline_width,0,$this->str,0,2,"L",0);
				//antirotate
				$pdf->_out('Q');
				

				$posX = 100;
				$posY = 42;
				//$pdf->rect($posX, $posY, 90, 40,);

				// Destinataire
				
				// Show recipient name
				$pdf->SetTextColor(0,0,0);
				$pdf->SetXY($posX+2,$posY+3);
				$pdf->SetFont('Arial','B',12);
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
				$pdf->SetFont('Arial','',11);
				$posY = $pdf->GetY()-9; //Auto Y coord readjust for multiline name
				$pdf->SetXY($posX+2,$posY+10);
				$pdf->MultiCell(86,5, $outputlangs->convToOutputCharset($this->str), 0, 'L');

				// Date
				$posY = $posY + 50;
				setlocale(LC_TIME, 'fr_FR', 'fr_FR.utf8', 'fr');
				$this->str = ucfirst(strtolower(AGF_ORGANISME_SIEGE)).', le '.strftime("%A %d %B %Y");
				$pdf->SetXY($posX+2,$posY+6);
				$pdf->Cell(0,0,$outputlangs->convToOutputCharset($this->str),0,0,"R",0);
				
				// Corps du courrier
				$posY = $this->_body($pdf, $agf, $outputlangs, $courrier, $id, $socid);
				
				// Signataire
				$pdf->SetXY($posX + 10, $posY + 10);
				$this->str = AGF_ORGANISME_RESPONSABLE."\nresponsable formation";
				$pdf->MultiCell(50,4, $outputlangs->convToOutputCharset($this->str),0,'C');

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
		
		require(DOL_DOCUMENT_ROOT.'/agefodd/core/models/pdf/pdf_courrier-'.$courrier.'_modele.php');
		
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


		//return pdf_pagefoot($pdf,$outputlangs,'FACTURE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object);
	}
}

# llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
