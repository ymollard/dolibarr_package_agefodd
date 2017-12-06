<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2014  Florian Henry <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file agefodd/training/list.php
 * \ingroup agefodd
 * \brief list of training
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once (DOL_DOCUMENT_ROOT . '/product/class/product.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

require_once '../class/agefodd_formation_catalogue.class.php';
require_once '../class/html.formagefodd.class.php';

// Security check
if (! $user->rights->agefodd->agefodd_formation_catalogue->lire)
	accessforbidden();

$langs->load('agefodd@agefodd');

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'alpha');
$arch = GETPOST('arch', 'int');

if (empty($sortorder))
	$sortorder = "DESC";
if (empty($sortfield))
	$sortfield = "c.rowid";

if ($page == - 1) {
	$page = 0;
}

$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (empty($arch)) {
	$arch = 0;
} else {
	$option .= '&arch=' . $arch;
}

	// Search criteria
$search_intitule = GETPOST("search_intitule");
$search_ref = GETPOST("search_ref");
$search_ref_interne = GETPOST("search_ref_interne");
$search_datec = dol_mktime(0, 0, 0, GETPOST('search_datecmonth', 'int'), GETPOST('search_datecday', 'int'), GETPOST('search_datecyear', 'int'));
$search_duree = GETPOST('search_duree');
// $search_dated = dol_mktime ( 0, 0, 0, GETPOST ( 'search_datedmonth', 'int' ), GETPOST ( 'search_datedday', 'int' ), GETPOST ( 'search_datedyear',
// 'int' ) );
$search_id = GETPOST('search_id', 'int');
$search_categ = GETPOST('search_categ', 'int');
if ($search_categ == - 1) {
	$search_categ = '';
}
$search_categ_bpf = GETPOST('search_categ_bpf', 'int');
if ($search_categ_bpf == - 1) {
	$search_categ_bpf = '';
}

	// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x")) {
	$search_intitule = '';
	$search_ref = '';
	$search_ref_interne = "";
	$search_datec = '';
	$search_duree = "";
	// $search_dated = "";
	$search_id = '';
	$search_categ = '';
}
include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

$arrayfields=array(
    'c.rowid'			=>array('label'=>"Id", 'checked'=>1),

    'c.intitule'		=>array('label'=>"AgfIntitule", 'checked'=>1),
    'c.ref'				=>array('label'=>"Ref", 'checked'=>1),
    'c.ref_interne'		=>array('label'=>"AgfRefInterne", 'checked'=>1),

    'dictcat.code'=>array('label'=>"AgfTrainingCateg", 'checked'=>1),
	'dictcatbpf.code'		=>array('label'=>"AgfTrainingCategBPF", 'checked'=>1),
    'c.datec'	=>array('label'=>"AgfDateC", 'checked'=>1),

    'c.duree'		=>array('label'=>"AgfDuree", 'checked'=>1),
    'a.dated'	=>array('label'=>"AgfDateLastAction", 'checked'=>1),
	'AgfNbreAction'		=>array('label'=>"AgfNbreAction", 'checked'=>1),
	'AgfProductServiceLinked'	=>array('label'=>'AgfProductServiceLinked', 'checked'=>1),

);



llxHeader('', $langs->trans('AgfMenuCat'));

$agf = new Agefodd($db);
$form = new Form($db);
$formagefodd = new FormAgefodd($db);
$formfile = new FormFile($db);

$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($agf->table_element, true);

// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
   foreach($extrafields->attribute_label as $key => $val)
   {
       $arrayfields["extra.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key]);
   }
}

$filter = array ();
if (! empty($search_intitule)) {
	$filter ['c.intitule'] = $db->escape($search_intitule);
	$option .= '&search_intitule=' . $search_intitule;
}
if (! empty($search_ref)) {
	$filter ['c.ref'] = $search_ref;
	$option .= '&search_ref=' . $search_ref;
}
if (! empty($search_ref_interne)) {
	$filter ['c.ref_interne'] = $search_ref_interne;
	$option .= '&search_ref_interne=' . $search_ref_interne;
}
if (! empty($search_datec)) {
	$filter ['c.datec'] = $db->idate($search_datec);
	$option .= '&search_datecmonth=' . dol_print_date($search_datec,'%m').'&search_datecday='.dol_print_date($search_datec,'%d').'&search_datecyear='.dol_print_date($search_datec,'%Y');
}
if (! empty($search_duree)) {
	$filter ['c.duree'] = $search_duree;
	$option .= '&search_duree=' . $search_duree;
}
if (! empty($search_id)) {
	$filter ['c.rowid'] = $search_id;
	$option .= '&search_id=' . $search_id;
}
if (! empty($search_categ)) {
	$filter ['c.fk_c_category'] = $search_categ;
	$option .= '&search_categ=' . $search_categ;
}
if (! empty($search_categ_bpf)) {
	$filter ['c.fk_c_category_bpf'] = $search_categ_bpf;
	$option .= '&search_categ_bpf=' . $search_categ_bpf;
}
$resql = $agf->fetch_all($sortorder, $sortfield, $limit, $offset, $arch, $filter, array_keys($extrafields->attribute_label));



$i = 0;

print '<form method="get" action="' . $url_form . '" name="search_form">' . "\n";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="arch" value="' . $arch . '" >';
if (! empty($sortfield))
	print '<input type="hidden" name="sortfield" value="' . $sortfield . '"/>';
if (! empty($sortorder))
	print '<input type="hidden" name="sortorder" value="' . $sortorder . '"/>';
if (! empty($page))
	print '<input type="hidden" name="page" value="' . $page . '"/>';

	print_barre_liste($langs->trans("AgfMenuCat"), $page, $_SERVER ['PHP_SELF'], '&arch=' . $arch, $sortfield, $sortorder, '', $resql,$resql,'title_generic.png', 0, '', '', $limit);


$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);



print '<table class="tagtable liste listwithfilterbefore" width="100%">';
print '<tr class="liste_titre">';
if (! empty($arrayfields['c.rowid']['checked']))print '<td><input type="text" class="flat" name="search_id" value="' . $search_id . '" size="2"></td>';

if (! empty($arrayfields['c.intitule']['checked'])){
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_intitule" value="' . $search_intitule . '" size="20">';
	print '</td>';
}
if (! empty($arrayfields['c.ref']['checked'])){
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="' . $search_ref . '" size="20">';
	print '</td>';
}
if (! empty($arrayfields['c.ref_interne']['checked'])){
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref_interne" value="' . $search_ref_interne . '" size="20">';
	print '</td>';
}
if (! empty($arrayfields['dictcat.code']['checked'])){
	print '<td class="liste_titre">';
	print $formagefodd->select_training_categ($search_categ, 'search_categ', 't.active=1');
	print '</td>';
}
if (! empty($arrayfields['dictcatbpf.code']['checked'])){
	print '<td class="liste_titre">';
	print $formagefodd->select_training_categ_bpf($search_categ_bpf, 'search_categ_bpf', 't.active=1');
	print '</td>';
}
if (! empty($arrayfields['c.datec']['checked'])){

	print '<td class="liste_titre">';
	print $form->select_date($search_datec, 'search_datec', 0, 0, 1, 'search_form');
	print '</td>';
}
if (! empty($arrayfields['c.duree']['checked'])){
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_duree" value="' . $search_duree . '" size="5">';
	print '</td>';
}
if (! empty($arrayfields['a.dated']['checked'])){
	print '<td class="liste_titre">';
	// print $form->select_date ( $search_dated, 'search_dated', 0, 0, 1, 'search_form' );
	print '</td>';
}
if (! empty($arrayfields['AgfNbreAction']['checked'])){
	print '<td class="liste_titre">';
	print '</td>';
}

if (! empty($arrayfields['AgfProductServiceLinked']['checked'])) print '<td class="liste_titre"></td>';

// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
   foreach($extrafields->attribute_label as $key => $val)
   {
		if (! empty($arrayfields["extra.".$key]['checked'])) print '<td class="liste_titre"></td>';
   }
}

print '<td class="liste_titre" align="right">';
if(method_exists($form, 'showFilterButtons')) {
	$searchpicto=$form->showFilterButtons();

	print $searchpicto;
} else {
	print '<input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
}
print '</td>';

print "</tr>\n";
print '</form>';
print '<tr class="liste_titre">';

if (! empty($arrayfields['c.rowid']['checked']))			print_liste_field_titre($langs->trans("Id"), $_SERVEUR ['PHP_SELF'], "c.rowid", "", $option, '', $sortfield, $sortorder);
if (! empty($arrayfields['c.intitule']['checked']))			print_liste_field_titre($langs->trans("AgfIntitule"), $_SERVEUR ['PHP_SELF'], "c.intitule", "", $option, '', $sortfield, $sortorder);
if (! empty($arrayfields['c.ref']['checked']))				print_liste_field_titre($langs->trans("Ref"), $_SERVEUR ['PHP_SELF'], "c.ref", "", $option, '', $sortfield, $sortorder);
if (! empty($arrayfields['c.ref_interne']['checked']))		print_liste_field_titre($langs->trans("AgfRefInterne"), $_SERVEUR ['PHP_SELF'], "c.ref_interne", "", $option, '', $sortfield, $sortorder);
if (! empty($arrayfields['dictcat.code']['checked']))		print_liste_field_titre($langs->trans("AgfTrainingCateg"), $_SERVEUR ['PHP_SELF'], "dictcat.code", "", $option, '', $sortfield, $sortorder);
if (! empty($arrayfields['dictcatbpf.code']['checked']))		print_liste_field_titre($langs->trans("AgfTrainingCategBPF"), $_SERVEUR ['PHP_SELF'], "dictcatbpf.code", "", $option, '', $sortfield, $sortorder);
if (! empty($arrayfields['c.datec']['checked']))		print_liste_field_titre($langs->trans("AgfDateC"), $_SERVEUR ['PHP_SELF'], "c.datec", "", $option, '', $sortfield, $sortorder);
if (! empty($arrayfields['c.duree']['checked']))		print_liste_field_titre($langs->trans("AgfDuree"), $_SERVEUR ['PHP_SELF'], "c.duree", "", $option, '', $sortfield, $sortorder);
if (! empty($arrayfields['a.dated']['checked']))		print_liste_field_titre($langs->trans("AgfDateLastAction"), $_SERVEUR ['PHP_SELF'], "a.dated", "", $option, '', $sortfield, $sortorder);
if (! empty($arrayfields['AgfNbreAction']['checked']))		print_liste_field_titre($langs->trans("AgfNbreAction"), $_SERVEUR ['PHP_SELF'], "", "", $option, '', $sortfield, $sortorder);
if (! empty($arrayfields['AgfProductServiceLinked']['checked'])) print_liste_field_titre($langs->trans("AgfProductServiceLinked"), $_SERVEUR ['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
	foreach($extrafields->attribute_label as $key => $val)
	{
		if (! empty($arrayfields["extra.".$key]['checked']))
		{
			$align=$extrafields->getAlignFlag($key);
			$sortonfield = "extra.".$key;
			print_liste_field_titre($extralabels[$key],$_SERVER["PHP_SELF"],'','',$option,($align?'align="'.$align.'"':''),$sortfield,$sortorder);
		}
	}
}

print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');

print "</tr>\n";


$var = true;
if ($resql > 0) {
	foreach ( $agf->lines as $line ) {

		// Affichage tableau des formations
		$var = ! $var;
		print "<tr $bc[$var]>";
		if (! empty($arrayfields['c.rowid']['checked']))	{
			print '<td>';
			print '<table class="nobordernopadding"><tr class="nocellnopadd">';

			print '<td class="nobordernopadding nowrap">';
			print '<a href="card.php?id=' . $line->rowid . '">' . img_object($langs->trans("AgfShowDetails"), "service") . ' ' . $line->rowid . '</a>';
			print '</td>';

			print '<td style="min-width: 20px" class="nobordernopadding nowrap">';
			$legende = $langs->trans("AgfDocOpen");
			$agfTraining = new Agefodd($db);
			$agfTraining->fetch($line->rowid);
			$agfTraining->generatePDAByLink();
			if (is_file($conf->agefodd->dir_output . '/fiche_pedago_' . $line->rowid . '.pdf')) {
				print '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=agefodd&file=fiche_pedago_' . $line->rowid . '.pdf" alt="' . $legende . '" title="' . $legende . '">';
				print img_picto('fiche_pedago_' . $line->rowid . '.pdf:fiche_pedago_' . $line->rowid . '.pdf', 'pdf2') . '</a>';
				if (function_exists('getAdvancedPreviewUrl')) {
					$urladvanced = getAdvancedPreviewUrl('agefodd', 'fiche_pedago_' . $line->rowid . '.pdf');
					if ($urladvanced) print '<a data-ajax="false" href="'.$urladvanced.'" title="' . $langs->trans("Preview"). '">'.img_picto('','detail').'</a>';
				}
			}
			if (is_file($conf->agefodd->dir_output . '/fiche_pedago_modules_' . $line->rowid . '.pdf')) {
				print '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=agefodd&file=fiche_pedago_modules_' . $line->rowid . '.pdf" alt="' . $legende . '" title="' . $legende . '">';
				print img_picto('fiche_pedago_modules_' . $line->rowid . '.pdf:fiche_pedago_modules_' . $line->rowid . '.pdf', 'pdf2') . '</a>';
				if (function_exists('getAdvancedPreviewUrl')) {
					$urladvanced = getAdvancedPreviewUrl('agefodd', 'fiche_pedago_modules_' . $line->rowid . '.pdf');
					if ($urladvanced) print '<a data-ajax="false" href="'.$urladvanced.'" title="' . $langs->trans("Preview"). '">'.img_picto('','detail').'</a>';
				}
			}
			print '</td>';
			print '</tr>';
			print '</table>';
			print '</td>';
		}
		if (! empty($arrayfields['c.intitule']['checked']))print '<td>' . stripslashes($line->intitule) . '</td>';
		if (! empty($arrayfields['c.ref']['checked']))	print '<td>' . $line->ref . '</td>';
		if (! empty($arrayfields['c.ref_interne']['checked']))print '<td>' . $line->ref_interne . '</td>';
		if (! empty($arrayfields['dictcat.code']['checked']))print '<td>' . dol_trunc($line->category_lib). '</td>';
		if (! empty($arrayfields['dictcatbpf.code']['checked']))print '<td>' . dol_trunc($line->category_lib_bpf). '</td>';
		if (! empty($arrayfields['c.datec']['checked']))print '<td>' . dol_print_date($line->datec, 'daytext') . '</td>';
		if (! empty($arrayfields['c.duree']['checked']))print '<td>' . $line->duree . '</td>';
		if (! empty($arrayfields['a.dated']['checked']))print '<td>' . dol_print_date($line->lastsession, 'daytext') . '</td>';
		if (! empty($arrayfields['AgfNbreAction']['checked']))print '<td>' . $line->nbsession . '</td>';
		if (! empty($arrayfields['AgfProductServiceLinked']['checked'])) {
				if(! empty($line->fk_product)) {
					$product = new Product($db);
					$product->fetch($line->fk_product);
					print '<td>'.$product->getNomUrl(1).'</td>';
				}
				else print '<td>&nbsp;</td>';
			}
		// Extra fields
		if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
		{
		   foreach($extrafields->attribute_label as $key => $val)
		   {
				if (! empty($arrayfields["extra.".$key]['checked']))
				{
					print '<td';
					$align=$extrafields->getAlignFlag($key);
					if ($align) print ' align="'.$align.'"';
					print '>';
					$tmpkey='options_'.$key;
					print $extrafields->showOutputField($key, $line->array_options[$tmpkey], '', 1);
					print '</td>';
					if (! $i) $totalarray['nbfield']++;
				}
		   }
		}
		print '<td>&nbsp;</td>';
		print "</tr>\n";

		$i ++;
	}
} else {
	setEventMessage($agf->error, 'errors');
}

print "</table>";

llxFooter();
$db->close();
