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
	\file		$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/liste.php $
	\brief		Page présentant la liste des formation enregsitrées (passées, actuelles et à venir
	\version	$Id: liste.php 42 2010-03-21 16:17:59Z ebullier $
*/

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/agefodd/class/formation_catalogue.class.php");

$langs->load("companies");
$langs->load("users");


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

llxHeader();

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$page=$_GET["page"];

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="c.rowid";


if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
//$limit = 1;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT c.rowid, c.amount, c.label, c.fk_user, c.date,";
$sql.= " c.tms, c.ref_comptable, u.name, u.firstname";
$sql.= " FROM ".MAIN_DB_PREFIX."cca as c";
$sql.= ", ".MAIN_DB_PREFIX."user as u";
$sql.= " WHERE c.fk_user = u.rowid";
$sql.= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit + 1 ,$offset);


//print $sql;
$resql=$db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);

  print_barre_liste($langs->trans("CcaList"), $page, "liste.php","&socid=$socid", $sortfield, $sortorder,'', $num);

  $i = 0;
  print '<table class="noborder" width="100%">';
  print "<tr class=\"liste_titre\">";
  print_liste_field_titre($langs->trans("Ref"),"liste.php","c.rowid","","&socid=$socid",'',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("CcaDescription"),"listex.php","c.label","","&socid=$socid",'',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("CcaRefComptable"),"liste.php","c.ref_comptable","","&socid=$socid",'',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("CcaDateValeur"),"liste.php","c.date","","&socid=$socid",'',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("CcaAssociate"),"liste.php","u.name","","&socid=$socid",'',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("CcaDebit"),"liste.php","c.amount","","&socid=$socid",'align="right"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("CcaCredit"),"liste.php","c.amount","","&socid=$socid",'align="right"',$sortfield,$sortorder);
  print "</tr>\n";

  $var=true;
  while ($i < $num)
    {
      $objp = $db->fetch_object($resql);

      $cca = new Cca($db);
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td><a href="fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans("CcaShowOps"),"bill").' '.$objp->rowid.'</a></td>';
      print '<td>'.$langs->trans($objp->label).'</td>';
      if (empty($objp->ref_comptable)) $objp->ref_comptable = 'aucune';
      print '<td>'.$objp->ref_comptable.'</td>';
      print '<td>'.dol_print_date($objp->date,'day').'</td>';
      //if ($objp->socid) print '<td>'.$soc->getNomUrl(1).'</td>';
      //else print '<td>&nbsp;</td>';
      print '<td align="left"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowUser"),"user").' '.$objp->firstname.' '.$objp->name.'</a></td>';
      // Débits
      print '<td align="right">';
      if ( $objp->amount < 0) print price(abs($objp->amount));
      print '</td>';
      print '<td align="right">';
      if ( $objp->amount > 0) print price($objp->amount);
      print'</td>';
      print "</tr>\n";
      
      $i++;
    }
  
  print "</table>";
  $db->free($resql);
}
else
{
  dol_print_error($db);
}
$db->close();

llxFooter('$Date: 2010-03-21 17:17:59 +0100 (dim. 21 mars 2010) $ - $Revision: 42 $');
?>
