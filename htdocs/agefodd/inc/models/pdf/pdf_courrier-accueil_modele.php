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
	\brief		Contenu du fichier pdf "courrier accompagnant l'envoi du dossier d'accueil"
	\version	$Id: s_liste.php 54 2010-03-30 18:58:28Z ebullier $
*/

require_once('../../../../main.inc.php');

$posX = 100;
$posY = 110;


/*
 *  Rubrique "Objet"
 */

// Recuperation des dates de formation
$agf = new Agefodd_session($this->db);
$ret = $agf->fetch($id);
if ($agf->dated == $agf->datef) $this->date.= "le ".dol_print_date($agf->datef);
else $this->date.= "du ".dol_print_date($agf->dated).' au '.dol_print_date($agf->datef);

$pdf->SetXY($posX - 77, $posY);
$pdf->SetFont('Arial','B', 11);
$pdf->Cell(30, 6, "Objet :",0,0,"R",0);

$pdf->SetXY($posX - 47, $posY);
$pdf->SetFont('Arial','', 11);
$this->str = "Formation professionnelle réalisée ".$this->date;
$pdf->Cell(0, 6, $outputlangs->convToOutputCharset($this->str) ,0,0,"L",0);
$posY += 6;


/*
 *  Rubrique "Pièces jointes"
 */

$pdf->SetXY($posX - 77, $posY);
$pdf->SetFont('Arial','B', 11);
$pdf->Cell(30, 5, $outputlangs->convToOutputCharset("Pièces jointes :"),0,0,"R",0);


$pdf->SetXY($posX - 47, $posY);
$pdf->SetFont('Arial','', 11);
$this->str = "Convocation\n";
$this->str.= "Programme\n";
$this->str.= "Fiche pédagogique\n";
$this->str.= "Fiche de conseils pratiques";
$pdf->MultiCell(0,5, $outputlangs->convToOutputCharset($this->str));
$posY += 36;


/*
 *  Corps de lettre
 */

// Recuperation des stagiaires participant à la formation
$agf_stag = new Agefodd_session($this->db);
$result = $agf_stag->fetch_stagiaire_per_session($id, $socid);
$stagiaires = "";
$num = count($agf_stag->line);
if ($num > 6)
{
	$stagiaires.= $num." de vos collaborateurs. ";
}
else
{
	for ($i = 0; $i < $num; $i++)
	{
		if ($i < ($num - 1) && $i > 0 )  $stagiaires.= ', ';
		if ($i == ($num - 1) && $i > 0) $stagiaires.= ' et ';
		$stagiaires.= ucfirst(strtolower($agf_stag->line[$i]->civilitel)).' '.$agf_stag->line[$i]->prenom.' '.$agf_stag->line[$i]->nom;
		if ($i == ($num - 1)) $stagiaires.= '.';
	}
	$stagiaires.="\n\n";
}
$pdf->SetXY($posX - 80, $posY);

$this->str = "Madame, Monsieur,\n\n\n";

$this->str.= "Vous avez souhaité faire participer à la formation cité en objet ".$stagiaires;
$this->str.= "Cette formation se déroulera " . $this->date;
$this->str.= " dans les locaux du ".$agf->placecode.".\n";
$this->str.= "Nous vous prions de trouver en pièce jointe l'ensemble des documents utiles au bon déroulement de cette prestation.\n";
$this->str.= "Merci de bien vouloir les transmettre à chacun des participants: certains sont indispensables";
$this->str.= " au bon déroulement de la formation.\n\n";
$this->str.= "Vous en souhaitant bonne réception.\n\nCordialement,";
$pdf->MultiCell(0,4, $outputlangs->convToOutputCharset($this->str));

$hauteur = $this->NbLines($pdf, $this->espaceH_dispo, $outputlangs->transnoentities($this->str), 4);
$posY += $hauteur + 6;

# llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
