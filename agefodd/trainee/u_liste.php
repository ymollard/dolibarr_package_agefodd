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
	\file		$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/u_liste.php $
	\brief		Page présentant la liste des stagiaires enregistrés
	\version	$Id$
*/

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/agefodd/class/agefodd_stagiaire.class.php");


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

llxHeader();

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$page=$_GET["page"];

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="s.rowid";


if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


$db->begin();


//$agf = new Agefodd_stagiaire($db);
//$result = $agf->fetch_liste_globale($sortorder, $sortfield, $limit, $offset);

$sql = "SELECT";
$sql.= " so.rowid as socid, so.nom as socname,";
$sql.= " civ.code as civilite,";
$sql.= " s.rowid, s.nom, s.prenom, s.fk_c_civilite, s.fk_soc, s.fonction,";
$sql.= " s.tel1, s.tel2, s.mail, s.note";
$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_stagiaire as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as so";
$sql.= " ON s.fk_soc = so.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_civilite as civ";
$sql.= " ON s.fk_c_civilite = civ.rowid";
$sql.= " ORDER BY ".$sortfield." ".$sortorder." ".$db->plimit( $limit + 1 ,$offset);

$resql = $db->query($sql);

if ($resql)
{

    dol_syslog("agefodd::u_liste::query sql=".$sql, LOG_DEBUG);
    $num = $db->num_rows($resql);
    
    print_barre_liste($langs->trans("AgfStagiaireList"), $page, "u_liste.php","&socid=$socid", $sortfield, $sortorder,'', $num);

    $i = 0;
    print '<table class="noborder" width="100%">';
    print "<tr class=\"liste_titre\">";
    print_liste_field_titre($langs->trans("Id"),"u_liste.php","s.rowid","","&socid=$socid",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfNomPrenom"),"u_liste.php","s.nom","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfCivilite"),"u_liste.php","civ.code","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"),"u_liste.php","so.nom","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Phone"),"u_liste.php","s.tel1","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Mail"),"u_liste.php","s.mail","","",'',$sortfield,$sortorder);
    print "</tr>\n";

    $var=true;
    while ($i < $num)
    {
	$agf = $db->fetch_object($resql);

	// Affichage liste des stagiaires
	$var=!$var;
	print "<tr $bc[$var]>";
	print '<td><a href="u_fiche.php?id='.$agf->rowid.'">'.img_object($langs->trans("AgfShowDetails"),"user").' '.$agf->rowid.'</a></td>';
	print '<td>'.strtoupper($agf->nom).' '.ucfirst($agf->prenom).'</td>';
	print '<td>'.$agf->civilite.'</td>';
	print '<td>';
	if ($agf->socid)
        {
            print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$agf->socid.'">';
            print img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($agf->socname,20).'</a>';
        }
        else
        {
            print '&nbsp;';
        }
        print '</td>';
	print '<td>'.dol_print_phone($agf->tel1).'</td>';
	print '<td>'.dol_print_email($agf->mail, $agf->id, $agf->socid,'AC_EMAIL',25).'</td>';
	print "</tr>\n";

	$i++;
    }
    
    print "</table>";
}

$db->close();

llxFooter('$Date: 2010-03-28 19:06:42 +0200 (dim. 28 mars 2010) $ - $Revision: 51 $');
?>
