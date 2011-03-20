<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne		<eric.seigne@ryxeo.com>
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
	\file		$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/s_liste.php $
	\brief		Page présentant la liste des formateurs
	\version	$Id$
*/

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/agefodd/class/agefodd_contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/agefodd/lib/agefodd.lib.php");


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

llxHeader();

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$page=$_GET["page"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.name, s.firstname";


if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!isset($_GET["arch"])) $arch = 0;
else $arch = $_GET["arch"];

$db->begin();

$agf = new Agefodd_contact($db);

$resql = $agf->fetch_all($sortorder, $sortfield, $limit, $offset, $arch);

$linenum = count($agf->line);


if ($resql)
{

	print_barre_liste($langs->trans("AgfContact"), $page, "c_liste.php","prout", $sortfield, $sortorder, "", $linenum);
	
	print '<div width=100%" align="right">';
	if ($arch == 2)
	{
		print '<a href="'.DOL_URL_ROOT.'/agefodd/c_liste.php?arch=0">'.$langs->trans("AgfCacherFormateursArchives").'</a>'."\n";
	}
	else
	{
		print '<a href="'.DOL_URL_ROOT.'/agefodd/c_liste.php?arch=2">'.$langs->trans("AgfAfficherFormateursArchives").'</a>'."\n";
	
	}
	print '<a href="'.DOL_URL_ROOT.'/agefodd/c_liste.php?arch='.$arch.'">'.$txt.'</a>'."\n";
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Id"),"c_liste.php","s.rowid","&arch=".$arch,"",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Name"),"c_liste.php","s.name","", "",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Firstname"),"c_liste.php","s.firstname","","",'',$sortfield,$sortorder);	print_liste_field_titre($langs->trans("AgfCivilite"),"c_liste.php","s.civilite","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),"c_liste.php","soc.nom","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Phone"),"c_liste.php","s.phone","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("PhoneMobile"),"c_liste.php","s.phone","","",'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Mail"),"c_liste
	.php","s.email","","",'',$sortfield,$sortorder);
	print "</tr>\n";
	
	$var=true;
	$i = 0;
	while ($i < $linenum)
	{
		// Affichage liste des formateurs
		$var=!$var;
		( $agf->line[$i]->archive == 1 ) ? $style = ' style="color:gray;"' : $style = '';
		print "<tr $bc[$var]>";
		print '<td><span style="background-color:'.$bgcolor.';"><a href="c_fiche.php?id='.$agf->line[$i]->id.'"'.$style.'>'.img_object($langs->trans("AgfEditerFicheFormateur"),"user").' '.$agf->line[$i]->id.'</a></span></td>';
		print '<td'.$style.'>'.$agf->line[$i]->name.'</td>';
		print '<td'.$style.'>'.$agf->line[$i]->firstname.'</td>';
		print '<td'.$style.'>'.$agf->line[$i]->civilite.'</td>';
		print '<td'.$style.'>';
		if ($agf->line[$i]->socid)
        	{
			print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$agf->line[$i]->socid.'">';
			print img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($agf->line[$i]->socname,20).'</a>';
		}
		else print '&nbsp;';
		print '</td>';
		print '<td'.$style.'>'.dol_print_phone($agf->line[$i]->phone).'</td>';
		print '<td'.$style.'>'.dol_print_phone($agf->line[$i]->phone_mobile).'</td>';
		print '<td'.$style.'>';
		if ($agf->line[$i]->archive == 0) print dol_print_email($agf->line[$i]->email, $agf->line[$i]->spid, "", 'AC_EMAIL', 25);
		else print '<a href="mailto:'.$agf->line[$i]->email.'"'.$style.'>'.$agf->line[$i]->email.'</a>';
		print '</td>';
		print "</tr>\n";
	
		$i++;
	}
	
	print "</table>";
}
print '<div class="tabsAction">';


if ($_GET["action"] != 'create' && $_GET["action"] != 'edit')
{
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butAction" href="c_fiche.php?action=create">'.$langs->trans('Create').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Create').'</a>';
	}
}

print '</div>';

$db->close();

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
