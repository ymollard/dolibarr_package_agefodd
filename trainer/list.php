<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012       Florian Henry   <florian.henry@open-concept.pro>
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
 * 	\file		/agefodd/trainer/list.php
 * 	\brief		Page présentant la liste des formateurs
 * 	\version	$Id$
 */

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/trainer/class/agefodd_formateur.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

llxHeader();

$sortorder=GETPOST('sortorder','alpha');
$sortfield=GETPOST('sortfield','alpha');
$page=GETPOST('page','int');
$arch=GETPOST('arch','int');

if (empty($sortorder)) $sortorder="ASC";
if (empty($sortfield)) $sortfield="s.name, s.firstname";
if (empty($arch)) $arch = 0;

if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$agf = new Agefodd_teacher($db);

$resql = $agf->fetch_all($sortorder, $sortfield, $limit, $offset, $arch);

$linenum = count($agf->line);


print_barre_liste($langs->trans("AgfTeacher"), $page, $_SERVER['PHP_SELF'],'', $sortfield, $sortorder, "", $linenum);

print '<div width=100%" align="right">';
if ($arch == 2)
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?arch=0">'.$langs->trans("AgfCacherFormateursArchives").'</a>'."\n";
}
else
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?arch=2">'.$langs->trans("AgfAfficherFormateursArchives").'</a>'."\n";

}
print '<a href="'.$_SERVER['PHP_SELF'].'?arch='.$arch.'">'.$txt.'</a>'."\n";
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Id"),$_SERVER['PHP_SELF'],"s.rowid","&arch=".$arch,"",'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Name"),$_SERVER['PHP_SELF'],"s.name","", "",'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Firstname"),$_SERVER['PHP_SELF'],"s.firstname","","",'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("AgfCivilite"),$_SERVER['PHP_SELF'],"s.civilite","","",'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Phone"),$_SERVER['PHP_SELF'],"s.phone","","",'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("PhoneMobile"),$_SERVER['PHP_SELF'],"s.phone","","",'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Mail"),$_SERVER['PHP_SELF'],"s.email","","",'',$sortfield,$sortorder);
print "</tr>\n";
	
if ($resql)
{
	$var=true;
	$i = 0;
	while ($i < $linenum)
	{
		// Affichage liste des formateurs
		$var=!$var;
		( $agf->line[$i]->archive == 1 ) ? $style = ' style="color:gray;"' : $style = '';
		print "<tr $bc[$var]>";
		print '<td><span style="background-color:'.$bgcolor.';"><a href="card.php?id='.$agf->line[$i]->id.'"'.$style.'>'.img_object($langs->trans("AgfEditerFicheFormateur"),"user").' '.$agf->line[$i]->id.'</a></span></td>';
		print '<td'.$style.'>'.$agf->line[$i]->name.'</td>';
		print '<td'.$style.'>'.$agf->line[$i]->firstname.'</td>';
		print '<td'.$style.'>'.$agf->line[$i]->civilite.'</td>';
		print '<td'.$style.'>'.dol_print_phone($agf->line[$i]->phone).'</td>';
		print '<td'.$style.'>'.dol_print_phone($agf->line[$i]->phone_mobile).'</td>';
		print '<td'.$style.'>';
		if ($agf->line[$i]->archive == 0) print dol_print_email($agf->line[$i]->email, $agf->line[$i]->spid, "", 'AC_EMAIL', 25);
		else print '<a href="mailto:'.$agf->line[$i]->email.'"'.$style.'>'.$agf->line[$i]->email.'</a>';
		print '</td>';
		print "</tr>\n";
	
		$i++;
	}
}
else
{
    dol_syslog("agefodd::trainer::list ".$agf->error, LOG_ERR);
}

print "</table>";
print '<div class="tabsAction">';


if ($_GET["action"] != 'create' && $_GET["action"] != 'edit')
{
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butAction" href="card.php?action=create">'.$langs->trans('Create').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Create').'</a>';
	}
}

print '</div>';

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
