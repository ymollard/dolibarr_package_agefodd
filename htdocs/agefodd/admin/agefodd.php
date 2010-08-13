<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009	Laurent Destailleur	<eldy@users.sourceforge.org>
 * Copyright (C) 2009-2010		Erick Bullier		<eb.dev@ebiconsulting.fr>
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

/**	\file       $HeadURL: https://192.168.22.4/dolidev/trunk/admin/agefodd_setup.php $
 *	\ingroup    agefodd
 *	\brief      agefood module setup page
 *	\version    $Id: agefodd_setup.php 46 2010-03-21 20:28:31Z ebullier $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");
$langs->load("@agefodd");

if (!$user->admin) accessforbidden();


if ($_POST["action"] == 'setvalue' && $user->admin)
{
	$result = dolibarr_set_const($db, "AGF_PRELEV_TRIGGER",$_POST["AGF_PRELEV_TRIGGER"],'chaine',0,'',$conf->entity);
  	if ($result >= 0)
  	{
  		$mesg='<div class="ok">'.$langs->trans("SetupSaved").'</div>';
  	}
  	else
  	{
		dol_print_error($db);
    }
}


/*
 *
 *
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("AgefoddSetupDesc"),$linkback,'setup');

print $langs->trans("AgefoddSetupParamChoice")."<br>\n";

$mesg = "La page de paramètrage du module Agefodd n'a pas encore été développée.";
$mesg.= "<br />Pour le configurer, il faut modifier à la main les variables globales du programme en éditant le fichier 'agefodd/pre.inc.php'";

if ($mesg) print '<br>'.$mesg;

/*
print '<br>';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

$var=true;

// Entête
print '<table summary="bookmarklist" class="notopnoleftnoright" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td>';

print $langs->trans("Traitement automatique sur prélévement").'</td>';
$selected = '';
print '<td><select class="flat" name="AGF_PRELEV_TRIGGER">\n';
print '<option value="">&nbsp;</option>\n';
if  ($conf->global->AGF_PRELEV_TRIGGER == "no") $selected = 'selected="true"';
print '<option value="no" '.$selected.'>'.$langs->trans("CcaSetupNo").'</option>\n';
if  ($conf->global->AGF_PRELEV_TRIGGER == "bank_only") $selected='selected="true"';
print '<option value="bank_only" '.$selected.'>'.$langs->trans("CcaSetupBankOnly").'</option>\n';
if  ($conf->global->AGF_PRELEV_TRIGGER == "ff_only") $selected='selected="true"';
print '<option value="ff_only" '.$selected.'>'.$langs->trans("CcaSetupFactureFournisseurOnly").'</option>\n';
print '</select>
print '</td></tr>';

print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table></form>';
*/

$db->close();

llxFooter('$Date: 2010-03-21 21:28:31 +0100 (dim. 21 mars 2010) $ - $Revision: 46 $');
?>
