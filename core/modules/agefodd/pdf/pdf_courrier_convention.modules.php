<?php
/** Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2012-2013  Florian Henry   <florian.henry@open-concept.pro>
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 3 of the License, or
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
 *	\file       agefodd/core/modules/agefodd/pdf/pdf_courrier_convention.module.php
 *	\ingroup    agefodd
 *	\brief      PDF for convention/contract letter
 */


$posX = 100;
$posY = 110;


/*
 *  Rubrique "Objet"
*/

// Recuperation des dates de formation
$agf = new Agsession($this->db);
$ret = $agf->fetch($id);
if ($agf->dated == $agf->datef) $this->date.= $outputlangs->transnoentities('AgfPDFFichePres8')." ".dol_print_date($agf->datef);
else $this->date.= $outputlangs->transnoentities('AgfPDFFichePres9')." ".dol_print_date($agf->dated).' '.$outputlangs->transnoentities('AgfPDFFichePres10').' '.dol_print_date($agf->datef);


$pdf->SetXY($posX - 77, $posY);
$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', 11);
$pdf->Cell(30, 6, $outputlangs->transnoentities('AgfPDFCourrierAcceuil1'),0,0,"R",0);

$pdf->SetXY($posX - 47, $posY);
$pdf->SetFont(pdf_getPDFFont($outputlangs),'', 11);
$this->str = $outputlangs->transnoentities('AgfPDFCourrierAcceuil2')." ".$this->date;
$pdf->Cell(0, 6, $outputlangs->convToOutputCharset($this->str) ,0,0,"L",0);
$posY += 6;


/*
 *  Rubrique "Pièces jointes"
*/

$pdf->SetXY($posX - 77, $posY);
$pdf->SetFont(pdf_getPDFFont($outputlangs),'B', 11);
$pdf->Cell(30, 5, $outputlangs->transnoentities('AgfPDFCourrierAcceuil3'),0,0,"R",0);

$pdf->SetXY($posX - 47, $posY);
$pdf->SetFont(pdf_getPDFFont($outputlangs),'', 11);
$this->str = $outputlangs->transnoentities('AgfConvention').' (x2)';
$pdf->MultiCell(0,5, $outputlangs->convToOutputCharset($this->str),0,'L');
$posY += 16;


/*
 *  Corps de lettre
*/

$pdf->SetXY($posX - 80, $posY);

$this->str = $outputlangs->transnoentities('AgfPDFCourrierAcceuil4')."\n\n\n";

$this->str.= $outputlangs->transnoentities('AgfPDFCourrierConv1')."\n";
$this->str.= '« '.$agf->formintitule." » ".$outputlangs->transnoentities('AgfPDFCourrierConv2')." ".$this->date.".\n\n";
$this->str.= $outputlangs->transnoentities('AgfPDFCourrierConv3')."\n\n";

$this->str.= $outputlangs->transnoentities('AgfPDFCourrierAcceuil11')."\n\n";
$this->str.= $outputlangs->transnoentities('AgfPDFCourrierAcceuil13');
$pdf->MultiCell(0,4, $outputlangs->convToOutputCharset($this->str));

$hauteur = dol_nboflines_bis($this->str,50)*4;

$posY += $hauteur + 6;