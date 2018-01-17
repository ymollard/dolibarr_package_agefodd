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
 * \file agefodd/core/modules/agefodd/pdf/pdf_acceuil_courrier.module.php
 * \ingroup agefodd
 * \brief PDF for welcome letter
 */
$posX = 100;
$posY = 110;

/*
 *  Rubrique "Objet"
*/

// Recuperation des dates de formation
$agf = new Agsession($this->db);
$ret = $agf->fetch($id);
$this->date .=  $agf->libSessionDate('daytext');

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

$pdf->SetXY($posX - 47, $posY);
$pdf->SetFont(pdf_getPDFFont($outputlangs), '', 11);
$this->str = $outputlangs->transnoentities('AgfPDFConvocation') . "\n";
$this->str .= $outputlangs->transnoentities('AgfProgramme') . "\n";
$this->str .= $outputlangs->transnoentities('AgfFichePedagogique') . "\n";
$this->str .= $outputlangs->transnoentities('AgfConseilsPratique');
$pdf->MultiCell(0, 5, $outputlangs->convToOutputCharset($this->str), 0, 'L');
$posY += 36;

/*
 *  Corps de lettre
*/

// Recuperation des stagiaires participant à la formation
$agf_stag = new Agefodd_session_stagiaire($this->db);
$result = $agf_stag->fetch_stagiaire_per_session($id, $socid);
$stagiaires = "";
$num = count($agf_stag->lines);
if ($num > 6) {
	$stagiaires .= $num . ' ' . $outputlangs->transnoentities('AgfPDFCourrierAcceuil12') . " ";
} else {
	for($i = 0; $i < $num; $i ++) {
		if ($i < ($num - 1) && $i > 0)
			$stagiaires .= ', ';
		if ($i == ($num - 1) && $i > 0)
			$stagiaires .= ' et ';

		$contact_static = new Contact($this->db);
		$contact_static->civility_id = $agf_stag->lines[$i]->civilite;

		$civilite = $contact_static->getCivilityLabel();

		$stagiaires .= ucfirst(strtolower($civilite)) . ' ' . $agf_stag->lines [$i]->prenom . ' ' . $agf_stag->lines [$i]->nom;
		if ($i == ($num - 1))
			$stagiaires .= '.';
	}
	$stagiaires .= "\n\n";
}
$pdf->SetXY($posX - 80, $posY);

$this->str = $outputlangs->transnoentities('AgfPDFCourrierAcceuil4') . "\n\n\n";

$this->str .= $outputlangs->transnoentities('AgfPDFCourrierAcceuil5') . " " . $stagiaires;
$this->str .= $outputlangs->transnoentities('AgfPDFCourrierAcceuil6') . " " . $this->date;
$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFCourrierAcceuil7') . " " . $agf->placecode . ".\n";
$this->str .= $outputlangs->transnoentities('AgfPDFCourrierAcceuil8') . "\n";
$this->str .= $outputlangs->transnoentities('AgfPDFCourrierAcceuil9');
$this->str .= ' ' . $outputlangs->transnoentities('AgfPDFCourrierAcceuil10') . "\n\n";
$this->str .= $outputlangs->transnoentities('AgfPDFCourrierAcceuil11') . "\n\n";
$this->str .= $outputlangs->transnoentities('AgfPDFCourrierAcceuil13');
$pdf->MultiCell(0, 4, $outputlangs->convToOutputCharset($this->str), 0, 'L');

$hauteur = dol_nboflines_bis($this->str, 50) * 4;
$posY += $hauteur + 6;