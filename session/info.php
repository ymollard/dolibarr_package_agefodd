<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
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
 *  \file       	/agefodd/session/info.php
 *  \brief      	Page fiche d'une operation sur CCA
 *  \version		$Id$
 */

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

require_once("./class/agefodd_session.class.php");
require_once("../lib/agefodd.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$mesg = '';

$db->begin();

/*
 * View
 */

llxHeader();

$agf = new Agefodd_session($db);
$agf->info($_GET["id"]);

$head = session_prepare_head($agf);

dol_fiche_head($head, 'info', $langs->trans("AgfSessionDetail"), 0, 'user');


print '<table width="100%">';
print '<table class="border" width="100%">';

print "<tr>";
print '<td width="20%">'.$langs->trans("Ref").'</td><td>'.$agf->id.'</td></tr>';

$userstatic1 = new User($db);
$userstatic1->id = $agf->fk_userc;
$userstatic1->fetch();
print '<tr><td>'.$langs->trans("CreatedBy").'</td><td>';
print $userstatic1->getNomUrl(1).' ';
print $langs->trans("AgfLe").' '.dol_print_date($agf->datec).'</td></tr>';


$userstatic2 = new User($db);
$userstatic2->id = $agf->fk_userm;
$userstatic2->fetch();
if (!$agf->fk_userm)
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

llxFooter('$Date: 2010-03-30 07:39:02 +0200 (mar. 30 mars 2010) $ - $Revision: 53 $');
?>
