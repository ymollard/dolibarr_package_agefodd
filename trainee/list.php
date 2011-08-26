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
 * 	\file		/agefodd/trainee/list.php
 * 	\brief		Page présentant la liste des stagiaires enregistrés
 * 	\version	$Id$
 */

$res=@include("../../../main.inc.php");									// For "custom" directory
if (! $res) $res=@include("../../main.inc.php");						// For root directory
if (! $res) @include("../../../../../../dolibarr/htdocs/main.inc.php");	// Used on dev env only

require_once("./class/agefodd_stagiaire.class.php");


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
    
    print_barre_liste($langs->trans("AgfStagiaireList"), $page, $_SERVER['PHP_SELF'],"&socid=$socid", $sortfield, $sortorder,'', $num);

    $i = 0;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Id"),$_SERVER['PHP_SELF'],"s.rowid","","&socid=$socid",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfNomPrenom"),$_SERVER['PHP_SELF'],"s.nom","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("AgfCivilite"),$_SERVER['PHP_SELF'],"civ.code","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"),$_SERVER['PHP_SELF'],"so.nom","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Phone"),$_SERVER['PHP_SELF'],"s.tel1","","",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Mail"),$_SERVER['PHP_SELF'],"s.mail","","",'',$sortfield,$sortorder);
    print "</tr>\n";

    $var=true;
    while ($i < $num)
    {
	$agf = $db->fetch_object($resql);

	// Affichage liste des stagiaires
	$var=!$var;
	print "<tr $bc[$var]>";
	print '<td><a href="card.php?id='.$agf->rowid.'">'.img_object($langs->trans("AgfShowDetails"),"user").' '.$agf->rowid.'</a></td>';
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
