<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2012-2016 Florian Henry <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file agefodd/core/modules/agefodd/pdf/pdf_acceuil_cloture.module.php
 * \ingroup agefodd
 * \brief PDF for close (end session) letter
 */
$posX = 100;
$posY = 110;

/*
 *  Rubrique "Objet"
*/

// Recuperation des dates de formation
$agf = new Agsession($this->db);
$ret = $agf->fetch($id);

$this->date .= $agf->libSessionDate('daytext');

$pdf->SetXY($posX - 77, $posY);
$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 11);
$pdf->Cell(30, 6, $outputlangs->transnoentities('AgfPDFCourrierAcceuil1'), 0, 0, "R", 0);

$pdf->SetXY($posX - 47, $posY);
$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
$this->str = $outputlangs->transnoentities('AgfPDFCourrierAcceuil2') . " " . $this->date;
$pdf->Cell(0, 6, $outputlangs->convToOutputCharset($this->str), 0, 0, "L", 0);
$posY += 6;

/*
 *  Rubrique "Pièces jointes"
*/

$pdf->SetXY($posX - 77, $posY);
$pdf->SetFont(pdf_getPDFFont($outputlangs), 'B', 11);
$pdf->Cell(30, 5, $outputlangs->transnoentities('AgfPDFCourrierAcceuil3'), 0, 0, "R", 0);

// Recuperation de la réference de la facture
$agf_fac = new Agefodd_session_element($this->db);
$ret = $agf_fac->fetch_by_session_by_thirdparty($id, $socid,'invoice');
$facnum='';
$facnum_array=array();
foreach($agf_fac->lines as $line) {
	$facnum_array[]= $line->facnumber;
}
$facnum=implode(',',$facnum_array);

if (!empty($facnum)) {
	$pdf->SetXY($posX - 47, $posY);
	$this->str = $outputlangs->transnoentities('AgfPDFCourrierCloture1') . ' ' . $facnum . "\n";
}
// Recuperation des stagiaires participant à la formation
$agf_stag = new Agefodd_session_stagiaire($this->db);
$result = $agf_stag->fetch_stagiaire_per_session($id, $socid);
$stagiaires = "";
$num=0;
foreach($agf_stag->lines as $line) {
	if ($line->status_in_session==3) {
		$num ++;
	}
}


($num > 1) ? $this->str = "\n".$outputlangs->transnoentities('AgfPDFCourrierCloture2') . $num . ")" : $this->str = $outputlangs->transnoentities('AgfPDFCourrierCloture3');
$this->str .= "\n";
$this->str .= $outputlangs->transnoentities('AgfPDFCourrierCloture4') . "\n";
$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
$posY += 36;

/*
 *  Corps de lettre
*/
if ($num > 6) {
	$stagiaires .= $num . ' ' . $outputlangs->transnoentities('AgfPDFCourrierCloture5') . " ";
} else {
	for($i = 0; $i < count($agf_stag->lines); $i ++) {
		if ($agf_stag->lines [$i]->status_in_session==3) {
		if ($i < (count($agf_stag->lines) - 1) && $i > 0)
			$stagiaires .= ', ';
		if ($i == (count($agf_stag->lines) - 1) && $i > 0)
			$stagiaires .= ' ' . $outputlangs->transnoentities('AgfPDFCourrierCloture6') . ' ';

		$contact_static = new Contact($this->db);
		$contact_static->civility_id = $agf_stag->lines[$i]->civilite;
		$stagiaires .= ucfirst(strtolower($contact_static->getCivilityLabel())) . ' ' . $agf_stag->lines [$i]->prenom . ' ' . $agf_stag->lines [$i]->nom;
		if ($i == (count($agf_stag->lines) - 1))
			$stagiaires .= '.';
		}
	}
}
$stagiaires .= "\n\n";

$pdf->SetXY($posX - 80, $posY);

$this->str = $outputlangs->transnoentities('AgfPDFCourrierAcceuil4') . "\n\n\n";

$this->str .= $outputlangs->transnoentities('AgfPDFCourrierCloture7') . " ";
$this->str .= '« ' . $agf->intitule_custo . " » " . $outputlangs->transnoentities('AgfPDFCourrierCloture8') . " ";
$this->str .= $stagiaires;
$this->str .= $outputlangs->transnoentities('AgfPDFCourrierAcceuil11') . "\n\n";
$this->str .= $outputlangs->transnoentities('AgfPDFCourrierAcceuil13');

$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');

$hauteur = dol_nboflines_bis($this->str, 50) * 4;

$posY += $hauteur + 6;
