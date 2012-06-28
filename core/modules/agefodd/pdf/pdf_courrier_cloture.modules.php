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
	\brief		Contenu du fichier pdf "courrier accompagnant l'envoi du dossier de clotûre"
	\version	$Id$
*/

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
$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', 11);
$pdf->Cell(30, 6, "Objet :",0,0,"R",0);

$pdf->SetXY($posX - 47, $posY);
$pdf->SetFont(pdf_getPDFFont($outputlangs),'', 11);
$this->str = "Formation professionnelle réalisée ".$this->date;
$pdf->Cell(0, 6, $outputlangs->convToOutputCharset($this->str) ,0,0,"L",0);
$posY += 6;

/*
 *  Rubrique "Pièces jointes"
 */

$pdf->SetXY($posX - 77, $posY);
$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', 11);
$pdf->Cell(30, 5, $outputlangs->convToOutputCharset("Pièces jointes :"),0,0,"R",0);


// Recuperation de la réference de la facture
$agf_fac = new Agefodd_facture($this->db);
$ret = $agf_fac->fetch($id, $socid);
$facnum = $agf_fac->facnumber;

$pdf->SetXY($posX - 47, $posY);
$this->str = "Facture n° ".$facnum."\n";

// Recuperation des stagiaires participant à la formation
$agf_stag = new Agefodd_session($this->db);
$result = $agf_stag->fetch_stagiaire_per_session($id, $socid);
$stagiaires = "";
$num = count($agf_stag->line);

($num > 1) ? $this->str.= "Attestations de formation (x".$num.")" : $this->str.= "Attestation de formation";
$this->str.= "\n";
$this->str.= "Copie de la feuille d'émargement\n";
$pdf->SetFont(pdf_getPDFFont($outputlangs),'', 11);
$pdf->MultiCell(0,5, $outputlangs->convToOutputCharset($this->str));
$posY += 36;


/*
 *  Corps de lettre
 */

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
}
$stagiaires.="\n\n";

$pdf->SetXY($posX - 80, $posY);

$this->str = "Madame, Monsieur,\n\n\n";

$this->str.= "Veuillez trouver ci-joint les documents administratifs relatifs à la formation ";
$this->str.= '« '.$agf->formintitule." » suivie par ";
$this->str.= $stagiaires;
$this->str.= "Vous en souhaitant bonne réception.\n\nCordialement,";

$pdf->MultiCell(0,4, $outputlangs->convToOutputCharset($this->str));

$hauteur = dol_nboflines_bis($this->str,50)*4;

$posY += $hauteur + 6;
# llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
