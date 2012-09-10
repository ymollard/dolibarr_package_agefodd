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
 * 	\file		/agefodd/session/list.php
 * 	\brief		Page présentant la liste des formation enregistrées (passées, actuelles et à venir
 * 	\version	$Id$
 */

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_place.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/agefodd/class/html.formagefodd.class.php');

// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

llxHeader();

$sortorder=GETPOST('sortorder','alpha');
$sortfield=GETPOST('sortfield','alpha');
$page=GETPOST('page','int');
$arch=GETPOST('arch','int');

//Search criteria
$search_trainning_name=GETPOST("search_trainning_name");
$search_training_ref=GETPOST("search_training_ref",'alpha');
$search_start_date=dol_mktime(0,0,0,GETPOST('search_start_datemonth','int'),GETPOST('search_start_dateday','int'),GETPOST('search_start_dateyear','int'));
$search_end_date=dol_mktime(0,0,0,GETPOST('search_end_datemonth','int'),GETPOST('search_end_dateday','int'),GETPOST('search_end_dateyear','int'));
$search_site=GETPOST("search_site");

$training_view=GETPOST("training_view",'int');
$site_view=GETPOST('site_view','int');

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x"))
{
	$search_trainning_name='';
	$search_training_ref='';
	$search_start_date="";
	$search_end_date="";
	$search_site="";
}

$filter=array();
if (!empty($search_trainning_name)) {$filter['c.intitule']=$search_trainning_name;}
if (!empty($search_training_ref)) {$filter['c.ref']=$search_training_ref;}
if (!empty($search_start_date)) {$filter['s.dated']=$search_start_date;}
if (!empty($search_end_date)) {$filter['s.datef']=$search_end_date;}
if (!empty($search_site) && $search_site!=-1) {$filter['s.fk_session_place']=$search_site;}

if (empty($sortorder)) $sortorder="DESC";
if (empty($sortfield)) $sortfield="c.rowid";
if (empty($arch)) $arch = 0;

if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

if($training_view && !empty($search_training_ref) ) {
	$agf = new Agefodd($db);
	$result = $agf->fetch('',$search_training_ref);

	$head = training_prepare_head($agf);

	dol_fiche_head($head, 'sessions', $langs->trans("AgfCatalogDetail"), 0, 'label');

	$agf->printFormationInfo();
	print '</div>';
}

if($site_view ) {
	$agf = new Agefodd_place($db);
	$result = $agf->fetch($search_site);

	if ($result)
	{
		$head = site_prepare_head($agf);

		dol_fiche_head($head, 'sessions', $langs->trans("AgfSessPlace"), 0, 'address');
	}

	$agf->printPlaceInfo();
	print '</div>';
}

$agf = new Agsession($db);
$resql = $agf->fetch_all($sortorder, $sortfield, $limit, $offset, $arch, $filter);

if ($resql != -1)
{
	$num = $resql;

	if (empty($arch)) $menu = $langs->trans("AgfMenuSessAct");
	elseif ($arch == 2 ) $menu = $langs->trans("AgfMenuSessArchReady");
	else $menu = $langs->trans("AgfMenuSessArch");
	print_barre_liste($menu, $page, $_SERVEUR['PHP_SELF'],'&arch='.$arch.'&search_trainning_name='.$search_trainning_name.'&search_training_ref='.$search_training_ref.'&search_start_date='.$search_start_date.'&search_start_end='.$search_start_end.'&search_site='.$search_site, $sortfield, $sortorder,'', $num);

	$i = 0;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	$arg_url='&page='.$page.'&arch='.$arch.'&search_trainning_name='.$search_trainning_name.'&search_training_ref='.$search_training_ref.'&search_start_date='.$search_start_date.'&search_start_end='.$search_start_end.'&search_site='.$search_site;
	print_liste_field_titre($langs->trans("Id"),$_SERVEUR['PHP_SELF'],"s.rowid","",$arg_url,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfIntitule"),$_SERVEUR['PHP_SELF'],"c.intitule","",$arg_url,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfRefInterne"),$_SERVEUR['PHP_SELF'],"c.ref","",$arg_url,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfDateDebut"),$_SERVEUR['PHP_SELF'],"s.dated","",$arg_url,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfDateFin"),$_SERVEUR['PHP_SELF'],"s.datef","",$arg_url,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfLieu"),$_SERVEUR['PHP_SELF'],"p.ref_interne","",$arg_url,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AgfNbreParticipants"),$_SERVEUR['PHP_SELF'],"num",'' ,$arg_url,'',$sortfield,$sortorder);
	print "</tr>\n";


	//Search bar
	$url_form=$_SERVER["PHP_SELF"];
	$addcriteria=false;
	if (!empty($sortorder)){
		$url_form.='?sortorder='.$sortorder;
		$addcriteria=true;
	}
	if (!empty($sortfield)){
		if ($addcriteria){$url_form.='&sortfield='.$sortfield;}
		else {$url_form.='?sortfield='.$sortfield;}
		$addcriteria=true;
	}
	if (!empty($page)){
		if ($addcriteria){$url_form.='&page='.$page;}
		else {$url_form.='?page='.$page;}
		$addcriteria=true;
	}
	if (!empty($arch)){
		if ($addcriteria){$url_form.='&arch='.$arch;}
		else {$url_form.='?arch='.$arch;}
		$addcriteria=true;
	}

	print '<form method="get" action="'.$url_form.'" name="search_form">'."\n";
	print '<input type="hidden" name="arch" value="'.$arch.'" >';
	print '<tr class="liste_titre">';

	print '<td>&nbsp;</td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_trainning_name" value="'.$search_trainning_name.'" size="20">';
	print '</td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_training_ref" value="'.$search_training_ref.'" size="20">';
	print '</td>';

	print '<td class="liste_titre">';
	print $form->select_date($search_start_date,'search_start_date',0,0,1,'search_form');
	print '</td>';

	print '<td class="liste_titre">';
	print $form->select_date($search_end_date,'search_end_date',0,0,1,'search_form');
	print '</td>';

	print '<td class="liste_titre">';
	print $formAgefodd->select_site_forma($search_site,'search_site',1);
	print '</td>';

	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';

	print "</tr>\n";
	print '</form>';

	$var=true;
	foreach ($agf->line as $line)
	{

		// Affichage tableau des sessions
		$var=!$var;
		print "<tr $bc[$var]>";
		// Calcul de la couleur du lien en fonction de la couleur définie sur la session
		// http://www.w3.org/TR/AERT#color-contrast
		// SI ((Red value X 299) + (Green value X 587) + (Blue value X 114)) / 1000 < 125 ALORS AFFICHER DU BLANC (#FFF)
		$couleur_rgb = agf_hex2rgb($line->color);
		$color_a = '';
		if( $line->color && ((($couleur_rgb[0]*299) + ($couleur_rgb[1]*587) + ($couleur_rgb[2]*114)) /1000) < 125)
			$color_a = ' style="color: #FFFFFF;"';

		print '<td  style="background: #'.$line->color.'"><a'.$color_a.' href="card.php?id='.$line->rowid.'">'.img_object($langs->trans("AgfShowDetails"),"service").' '.$line->rowid.'</a></td>';
		print '<td>'.stripslashes(dol_trunc($line->intitule, 60)).'</td>';
		print '<td>'.$line->ref.'</td>';
		print '<td>'.dol_print_date($line->dated,'daytext').'</td>';
		print '<td>'.dol_print_date($line->datef,'daytext').'</td>';
		print '<td>'.stripslashes($line->ref_interne).'</td>';
		print '<td>';
		print $line->force_nb_stagiaire?$line->nb_stagiaire:$line->num;
		print '</td>';
		print "</tr>\n";

		$i++;
	}

	print "</table>";
}
else
{
    dol_print_error($db);
    dol_syslog("agefodd::session:list::query: ".$errmsg, LOG_ERR);
}


llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
