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
	\brief		Page permettant la création de la fiche d'évaluation propre à une formation au format pdf
	\version	$Id$
*/

dol_include_once('/agefodd/core/modules/agefodd/agefodd_modules.php');
dol_include_once('/agefodd/session/class/agefodd_session.class.php');
dol_include_once('/agefodd/training/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/session/class/agefodd_session_formateur.class.php');
dol_include_once('/agefodd/contact/class/agefodd_contact.class.php');
dol_include_once('/core/lib/company.lib.php');
dol_include_once('/core/lib/pdf.lib.php');


class pdf_fiche_evaluation extends ModelePDFAgefodd
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
	function pdf_fiche_evaluation($db)
	{
		global $conf,$langs,$mysoc;
		

		$this->db = $db;
		$this->name = "fiche_evaluation";
		$this->description = $langs->trans('AgfModPDFFicheEval');

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
				
				/*
				 * Corps de page
				 */

				$posX = $this->marge_gauche;
				$posY = $posY + 5;
				
				
				/***** Titre *****/
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',15);
				$pdf->SetTextColor($this->color2[0], $this->color2[1], $this->color2[2]);
				$pdf->SetXY($posX, $posY);
				$this->str = "Fiche d'évaluation de la formation";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'C');
				$posY+= 10;

				$pdf->SetFont('','',12);
				$pdf->SetTextColor(0,0,0);
				$this->str = $agf->formintitule;
				$hauteur = dol_nboflines_bis($this->str,50)*4;
				// cadre
				$pdf->SetFillColor(255);
				$pdf->Rect($posX, $posY, $this->espaceH_dispo, $hauteur+3);
				// texte
				$pdf->SetXY( $posX, $posY);
				$pdf->MultiCell(0,5, $outputlangs->convToOutputCharset($this->str), 0, 'C');
				$posY+= $hauteur + 3;

				
				/***** Date et formateur *****/
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'I',9);

				$pdf->SetXY($posX, $posY);
				$this->str = "Session du ";
				if ($agf->dated == $agf->dated) $this->str .= dol_print_date($agf->dated);
				else $this->str .= dol_print_date($agf->dated).' au '.dol_print_date($agf->datef);
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'C');
				$posY+= 4;

				$formateurs = new Agefodd_session_formateur($this->db);
				$nbform = $formateurs->fetch_formateur_per_session($agf->id);
				$form_str = "";
				for ($i=0; $i < $nbform; $i++)
				{
				    // Infos formateurs
				    $forma_str .= strtoupper($formateurs->line[$i]->name).' '.ucfirst($formateurs->line[$i]->firstname);
				    if ($i < ($nbform - 1)) $forma_str .= ', ';
				}
				
				$pdf->SetXY($posX, $posY);
				//$this->str = "formateur: ".$agf->teachername;
				($nbform > 1) ? $this->str = "formateurs : " : $this->str = "formateur :";
				$this->str .= $forma_str;
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'C');
				$posY+= 10;

				
				/***** Objectifs pedagogique de la formation *****/
				
				// Récuperation
				$agf_op = new Agefodd($this->db,"",$id);
				$result2 = $agf_op->fetch_objpeda_per_formation($agf->formid);
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',10);
				$pdf->SetXY($posX, $posY);
				$this->str = "Les objectifs sont-ils atteints?";
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset($this->str),0,0,'L');
				$posY+= 5 + 1;
				
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',10);
				for ( $y = 0; $y < count($agf_op->line); $y++)
				{
					// Intitulé
					if ($y > 0) $posY+= $hauteur;
					$pdf->SetXY ($posX, $posY);
					$width = 160;
					$hauteur = dol_nboflines_bis($this->str,50)*4;
					$pdf->MultiCell(160, 4, $outputlangs->transnoentities($agf_op->line[$y]->intitule), 1,'L',0);

					// Oui
					$pdf->SetXY ($posX + 160, $posY);
					$pdf->Cell(10, 5, $outputlangs->convToOutputCharset("oui"),0,0,'C');
					$pdf->Rect($posX + 160, $posY, 10, $hauteur);
				
					// Non
					$pdf->SetXY ($posX + 160 + 10, $posY);
					$pdf->Cell(10, 5, $outputlangs->convToOutputCharset("non"),0,0,'C');
					$pdf->Rect($posX + 160 + 10, $posY, 10, $hauteur);
				
					
				}
				$posY+= 15;
				
				
				/***** présentation echelle de notation *****/

				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B',10);
				$pdf->SetXY($posX, $posY);
				$this->str = "Indiquez votre degré d'accord envers chacun des énnoncés présentés ci-dessous, en utilisant l'échelle suivante (pour chaque affirmation, indiquez le chiffre correspondant le plus à votre appréciation):";
				$pdf->MultiCell(0, 4, $outputlangs->transnoentities($this->str), 0,'C',0);
				$hauteur = dol_nboflines_bis($this->str,50)*4;
				$posY+= $hauteur + 1;
				

				$col_larg = $this->espaceH_dispo / 5;
				
				// ligne 1
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B',10);
				$pdf->SetXY($posX, $posY);
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("1"),1,0,'C');
				$pdf->SetXY($posX + (1 * $col_larg), $posY);
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("2"),1,0,'C');
				$pdf->SetXY($posX + (2 * $col_larg), $posY);
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("3"),1,0,'C');
				$pdf->SetXY($posX + (3 * $col_larg), $posY);
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("4"),1,0,'C');
				$pdf->SetXY($posX + (4 * $col_larg), $posY);
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("5"),1,0,'C');
				$posY+= 5;
				
				// ligne 2
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',9);
				$pdf->SetXY($posX, $posY);
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("pas du tout d'accord"),1,0,'C');
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("en désaccord partiel"),1,0,'C');
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("plus ou moins d'accord"),1,0,'C');
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("en accord partiel"),1,0,'C');
				$pdf->Cell($col_larg, 5, $outputlangs->convToOutputCharset("tout à fait d'accord"),1,0,'C');
				$posY+= 5 + 10;

				
				/***** lignes d'évaluations *****/

				$pdf->SetFont(pdf_getPDFFont($outputlangs),'',10);
				$hauteur_ligne = 6;

				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->convToOutputCharset("J'étais motivé pour suivre ce stage."),1,0,'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""),1,0,'C');
				$posY+= $hauteur_ligne;

				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->convToOutputCharset("Les objectifs de la formation étaient clairs et précis."),1,0,'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""),1,0,'C');
				$posY+= $hauteur_ligne;
				
				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->convToOutputCharset("Le contenu de la formation correspondait à mes besoins et à mes préoccupations."),1,0,'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""),1,0,'C');
				$posY+= $hauteur_ligne;
				
				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->convToOutputCharset("L'enchainement des modules a favorisé mon apprentissage."),1,0,'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""),1,0,'C');
				$posY+= $hauteur_ligne;
				
				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->convToOutputCharset("Les exercices et les activités étaient pertinents."),1,0,'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""),1,0,'C');
				$posY+= $hauteur_ligne;
				
				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->convToOutputCharset("Le(s) formateur(s) communiquai(en)t de façon claire et dynamique."),1,0,'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""),1,0,'C');
				$posY+= $hauteur_ligne;
				
				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->convToOutputCharset("Le déroulement de la formation a respecté le rythme d'apprentissage des participants."),1,0,'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""),1,0,'C');
				$posY+= $hauteur_ligne;
				
				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->convToOutputCharset("Cette formation m'a permit d'augmenter mon niveau de connaissance et de compétence."),1,0,'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""),1,0,'C');
				$posY+= $hauteur_ligne;
				
				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->convToOutputCharset("Si cela était possible, je serai en mesure d'utiliser ces compétences dès mon retour au travail."),1,0,'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""),1,0,'C');
				$posY+= $hauteur_ligne;
				
				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->convToOutputCharset("Je parlerai positivement de cette formation à mon entourage."),1,0,'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""),1,0,'C');
				$posY+= $hauteur_ligne;
				
				$pdf->SetXY($posX, $posY);
				$pdf->Cell(170, $hauteur_ligne, $outputlangs->convToOutputCharset("Je suis satisfait des conditions matérielles dans lesquelles s'est déroulée la formation."),1,0,'L');
				$pdf->Cell(10, $hauteur_ligne, $outputlangs->convToOutputCharset(""),1,0,'C');
				$posY+= 5 + $hauteur_ligne;
				
				$pdf->SetFont(pdf_getPDFFont($outputlangs),'I',11);
				$pdf->SetXY($posX, $posY);
				$pdf->Cell(0, $hauteur_ligne, $outputlangs->convToOutputCharset("Merci de bien vouloir commenter chacun des points dont le score est inférieur ou égal à 3."),0,0,'C');
				$posY+= $hauteur_ligne;


				/***** bloc commentaire *****/

				$pdf->SetFont(pdf_getPDFFont($outputlangs),'B',10);
				$pdf->SetXY($posX, $posY);
				$pdf->Cell(0, 5, $outputlangs->convToOutputCharset("Commentaires et/ou recommandations"),0,0,'L');

				$hauteur = $this->page_hauteur - 20 - $posY - 5;

				$pdf->Rect($posX, $posY, $this->espaceH_dispo, $hauteur);

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
