<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009	Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
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
 *  \file       	$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/f_info.php $
 *  \brief      	Page fiche d'info sur site de formation
 *  \version		$Id: f_info.php 51 2010-03-28 17:06:42Z ebullier $
 */
require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/agefodd/class/agefodd_formateur.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");

$langs->load("@agefodd");


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$mesg = '';

$db->begin();

/*
 * View
 */

llxHeader();

$agf = new Agefodd_teacher($db);
$agf->info($_GET["id"]);

$h=0;

$head[$h][0] = DOL_URL_ROOT."/agefodd/s_teacher_fiche.php?id=$agf->id";
$head[$h][1] = $langs->trans("Card");
$hselected = $h;
$h++;

$head[$h][0] = DOL_URL_ROOT."/agefodd/s_teacher_info.php?id=$agf->id";
$head[$h][1] = $langs->trans("Info");
$hselected = $h;
$h++;

dol_fiche_head($head, $hselected, $langs->trans("AgfTeacherDetail"), 0, 'bill');

print '<table class="border" width="100%">';
print "<tr>";
print '<td width="20%">'.$langs->trans("Ref").'</td><td>'.$agf->id.'</td></tr>';

$userstatic1 = new User($db);
$userstatic1->id = $agf->fk_user_author;
$userstatic1->fetch();
print '<tr><td>'.$langs->trans("CreatedBy").'</td><td>';
print $userstatic1->getNomUrl(1).' ';
print $langs->trans("AgfLe").' '.dol_print_date($agf->datec).'</td></tr>';


$userstatic2 = new User($db);
$userstatic2->id = $agf->fk_user_mod;
$userstatic2->fetch();
if (!$agf->fk_user_mod)
{
    print '<tr><td>'.$langs->trans("DateLastModification").'</td><td>';
    print $langs->trans("AgfNoMod").'</td></tr>';
}
else 
{
    
    print '<tr><td>'.$langs->trans("ModifiedBy").'</td><td>';
    print $userstatic2->getNomUrl(1).' ';
    print $langs->trans("AgfLe").' '.dol_print_date($agf->tms,"dayhourtext").'</td></tr>';
}

print '</table>';
print '</div>';

$db->close();

#llxFooter('$Date: 2010-03-28 19:06:42 +0200 (dim. 28 mars 2010) $ - $Revision: 51 $');
?>
