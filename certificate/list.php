<?php
/* Copyright (C) 2012-2014		Florian Henry			<florian.henry@open-concept.pro>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file agefodd/certificate/list.php
 * \ingroup agefodd
 * \brief list of certificate
 */

$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once (DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php');
require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_formation_catalogue.class.php');
require_once ('../class/agefodd_place.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../lib/agefodd.lib.php');
require_once ('../class/html.formagefodd.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');
require_once ('../class/agefodd_formateur.class.php');
require_once ('../class/agefodd_stagiaire_certif.class.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$socid = GETPOST('socid', 'int');

// Search criteria
$search_soc = GETPOST("search_soc", 'none');
$search_trainning_name = GETPOST("search_trainning_name", 'none');
$search_teacher_id = GETPOST("search_teacher_id", 'none');
$search_training_ref = GETPOST("search_training_ref", 'alpha');
$search_start_date = dol_mktime(0, 0, 0, GETPOST('search_start_datemonth', 'int'), GETPOST('search_start_dateday', 'int'), GETPOST('search_start_dateyear', 'int'));
$search_start_date2 = dol_mktime(0, 0, 0, GETPOST('search_start_date2month', 'int'), GETPOST('search_start_date2day', 'int'), GETPOST('search_start_date2year', 'int'));
$search_end_date = dol_mktime(0, 0, 0, GETPOST('search_end_datemonth', 'int'), GETPOST('search_end_dateday', 'int'), GETPOST('search_end_dateyear', 'int'));
$search_end_date2 = dol_mktime(0, 0, 0, GETPOST('search_end_date2month', 'int'), GETPOST('search_end_date2day', 'int'), GETPOST('search_end_date2year', 'int'));
$search_site = GETPOST("search_site", 'none');
$search_training_ref_interne = GETPOST('search_training_ref_interne', 'alpha');
$search_type_session = GETPOST("search_type_session", 'int');
$training_view = GETPOST("training_view", 'int');
$site_view = GETPOST('site_view', 'int');
$search_certif_start_date = dol_mktime(0, 0, 0, GETPOST('search_certif_start_datemonth', 'int'), GETPOST('search_certif_start_dateday', 'int'), GETPOST('search_certif_start_dateyear', 'int'));
$search_certif_start_date2 = dol_mktime(0, 0, 0, GETPOST('search_certif_start_date2month', 'int'), GETPOST('search_certif_start_date2day', 'int'), GETPOST('search_certif_start_date2year', 'int'));
$search_certif_end_date = dol_mktime(0, 0, 0, GETPOST('search_certif_end_datemonth', 'int'), GETPOST('search_certif_end_dateday', 'int'), GETPOST('search_certif_end_dateyear', 'int'));
$search_certif_end_date2 = dol_mktime(0, 0, 0, GETPOST('search_certif_end_date2month', 'int'), GETPOST('search_certif_end_date2day', 'int'), GETPOST('search_certif_end_date2year', 'int'));

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x", 'none')) {
    $search_soc = '';
	$search_trainning_name = '';
	$search_teacher_id = "";
	$search_training_ref = '';
	$search_start_date = "";
	$search_start_date2 = "";
	$search_end_date = "";
	$search_end_date2 = "";
	$search_site = "";
	$search_training_ref_interne = "";
	$search_type_session = "";
	$search_certif_start_date = "";
	$search_certif_start_date2 = "";
	$search_certif_end_date = "";
	$search_certif_end_date2 = "";
}

$filter = array ();
if (! empty($search_soc)) {
    $filter ['soc.nom'] = $search_soc;
    $option .= "&search_soc=" . $search_soc;
}
if (! empty($search_trainning_name)) {
	$filter ['c.intitule'] = $search_trainning_name;
}
if (! empty($search_teacher_id)) {
	$filter ['f.rowid'] = $search_teacher_id;
}
if (! empty($search_training_ref)) {
	$filter ['c.ref'] = $search_training_ref;
}
if (! empty($search_start_date)) {
	$filter ['s.dated'] = $db->idate($search_start_date);
}
if (! empty($search_start_date2)) {
    $filter ['s.dated2'] = $db->idate($search_start_date2);
}
if (! empty($search_end_date)) {
    $filter ['s.datef'] =  $db->idate($search_end_date);
}
if (! empty($search_end_date)) {
    $filter ['s.datef2'] =  $db->idate($search_end_date2);
}
if (! empty($search_certif_start_date)) {
    $filter ['certif.certif_dt_start'] = $db->idate($search_certif_start_date);
}
if (! empty($search_certif_start_date2)) {
    $filter ['certif.certif_dt_start2'] = $db->idate($search_certif_start_date2);
}
if (! empty($search_certif_end_date)) {
    $filter ['certif.certif_dt_end'] = $db->idate($search_certif_end_date);
}
if (! empty($search_certif_end_date2)) {
    $filter ['certif.certif_dt_end2'] = $db->idate($search_certif_end_date2);
}
if (! empty($search_site) && $search_site != - 1) {
	$filter ['s.fk_session_place'] = $search_site;
}
if (! empty($search_training_ref_interne)) {
	$filter ['c.ref_interne'] = $search_training_ref_interne;
}
if ($search_type_session != '' && $search_type_session != - 1) {
	$filter ['s.type_session'] = $search_type_session;
}

if (empty($sortorder))
	$sortorder = "DESC";
if (empty($sortfield))
	$sortfield = "certif.certif_dt_end";
if (empty($arch))
	$arch = 0;

if (empty($page) || $page == -1) { $page = 0; }

$limit = GETPOST("limit", 'none')?GETPOST("limit","int"):$conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

$hookmanager->initHooks(array('certificatlist'));

$title = $langs->trans("AgfListCertificate");

llxHeader('', $title);

$agf = new Agefodd_stagiaire_certif($db);

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $agf->fetch_certif_customer($socid, $sortorder, $sortfield, 0, 0, $filter);
}
$resql = $agf->fetch_certif_customer($socid, $sortorder, $sortfield, $limit, $offset, $filter);
print '<div width="100%" align="right">';
if ($resql>=0) {
	$num = $resql;

	print '<form method="get" action="' . $url_form . '" name="searchFormList" id="searchFormList">' . "\n";
	print '<input type="hidden" name="socid" value="' . $socid . '"/>';

	$option = '&socid=' . $socid . '&search_trainning_name=' . $search_trainning_name . '&search_soc=' . $search_soc . '&search_teacher_name=' . $search_teacher_name . '&search_training_ref=' . $search_training_ref . '&search_start_date=' . $search_start_date . '&search_start_end=' . $search_start_end . '&search_site=' . $search_site;
	print_barre_liste($title, $page, $_SERVER ['PHP_SELF'], $option, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_generic.png', 0, '', '', $limit);

	$i = 0;
	print '<table class="noborder tagtable liste listwithfilterbefore" width="100%">';
	print '<tr class="liste_titre_filter">';

	print '<td>&nbsp;</td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_soc" value="' . $search_soc . '">';
	print '</td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_trainning_name" value="' . $search_trainning_name . '" size="20">';
	print '</td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_training_ref" value="' . $search_training_ref . '" size="10">';
	print '</td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_training_ref_interne" value="' . $search_training_ref_interne . '" size="10">';
	print '</td>';

	print '<td class="liste_titre">';
	// trainee
	print '</td>';

	print '<td class="liste_titre">';
	// trainee email
	print '</td>';

	print '<td class="liste_titre">' . $langs->trans('From') . ' ';
	print $form->select_date($search_start_date, 'search_start_date', 0, 0, 1, 'search_form').'<BR>'.$langs->trans('to').' ';
	print $form->select_date($search_start_date2, 'search_start_date2', 0, 0, 1, 'search_form');
	print '</td>';

	print '<td class="liste_titre">' . $langs->trans('From') . ' ';
	print $form->select_date($search_end_date, 'search_end_date', 0, 0, 1, 'search_form').'<BR>'.$langs->trans('to').' ';
	print $form->select_date($search_end_date2, 'search_end_date2', 0, 0, 1, 'search_form');
	print '</td>';

	print '<td class="liste_titre">';
	print '</td>';

	print '<td class="liste_titre">';
	print '</td>';

	print '<td class="liste_titre">' . $langs->trans('From') . ' ';
	print $form->select_date($search_certif_start_date, 'search_certif_start_date', 0, 0, 1, 'search_form').'<BR>'.$langs->trans('to').' ';
	print $form->select_date($search_certif_start_date2, 'search_certif_start_date2', 0, 0, 1, 'search_form');
	print '</td>';

	print '<td class="liste_titre">' . $langs->trans('From') . ' ';
	print $form->select_date($search_certif_end_date, 'search_certif_end_date', 0, 0, 1, 'search_form').'<BR>'.$langs->trans('to').' ';
	print $form->select_date($search_certif_end_date2, 'search_certif_end_date2', 0, 0, 1, 'search_form');
	print '</td>';

	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	print '</td>';

	print "</tr>\n";


	print '<tr class="liste_titre">';
	$arg_url = '&page=' . $page . '&socid=' . $socid . '&search_trainning_name=' . $search_trainning_name . '&search_soc=' . $search_soc . '&search_teacher_name=' . $search_teacher_name . '&search_training_ref=' . $search_training_ref . '&search_start_date=' . $search_start_date . '&search_start_end=' . $search_start_end . '&search_site=' . $search_site;
	print_liste_field_titre($langs->trans("Id"), $_SERVER ['PHP_SELF'], "certif.fk_session_agefodd", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Company"), $_SERVER ['PHP_SELF'], "soc.nom", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfIntitule"), $_SERVER ['PHP_SELF'], "c.intitule", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Ref"), $_SERVER ['PHP_SELF'], "c.ref", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfRefInterne"), $_SERVER ['PHP_SELF'], "c.ref_interne", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfParticipant"), $_SERVER ['PHP_SELF'], "sta.nom", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Email"), $_SERVER ['PHP_SELF'], "", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfDateDebut"), $_SERVER ['PHP_SELF'], "s.dated", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfDateFin"), $_SERVER ['PHP_SELF'], "s.datef", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfCertifCode"), $_SERVER ['PHP_SELF'], 'certif.certif_code', '', $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfCertifLabel"), $_SERVER ['PHP_SELF'], 'certif.certif_label', '', $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfCertifDateSt"), $_SERVER ['PHP_SELF'], "certif.certif_dt_start", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfCertifDateEnd"), $_SERVER ['PHP_SELF'], "certif.certif_dt_end", '', $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfCertifDateWarning").' '.$form->textwithpicto('', $langs->trans("AgfCertifLegend"), 1, 'help'), $_SERVER ['PHP_SELF'], "certif.certif_dt_warning", '', $arg_url, '', $sortfield, $sortorder);
	print "</tr>\n";
	//AgfCertificateValidity
	// Search bar
	$url_form = $_SERVER ["PHP_SELF"];
	$addcriteria = false;
	if (! empty($sortorder)) {
		$url_form .= '?sortorder=' . $sortorder;
		$addcriteria = true;
	}
	if (! empty($sortfield)) {
		if ($addcriteria) {
			$url_form .= '&sortfield=' . $sortfield;
		} else {
			$url_form .= '?sortfield=' . $sortfield;
		}
		$addcriteria = true;
	}
	if (! empty($page)) {
		if ($addcriteria) {
			$url_form .= '&page=' . $page;
		} else {
			$url_form .= '?page=' . $page;
		}
		$addcriteria = true;
	}

	$var = true;
	foreach ( $agf->lines as $line ) {

		// Affichage tableau des sessions
		$var = ! $var;
		print "<tr $bc[$var]>";
		// Calcul de la couleur du lien en fonction de la couleur définie sur la session
		// http://www.w3.org/TR/AERT#color-contrast
		// SI ((Red value X 299) + (Green value X 587) + (Blue value X 114)) / 1000 < 125 ALORS
		// AFFICHER DU BLANC (#FFF)
		$couleur_rgb = agf_hex2rgb($line->color);
		$color_a = '';
		if ($line->color && ((($couleur_rgb [0] * 299) + ($couleur_rgb [1] * 587) + ($couleur_rgb [2] * 114)) / 1000) < 125)
			$color_a = ' style="color: #FFFFFF;"';

		print '<td  style="background: #' . $line->color . '"><a' . $color_a . ' href="../session/subscribers_certif.php?id=' . $line->id_session . '">' . img_object($langs->trans("AgfShowDetails"), "service") . ' ' . $line->id_session . '</a></td>';
		print '<td>';

		if (! empty($line->customer_id) && $line->customer_id != - 1) {
			$soc = new Societe($db);
			$soc->fetch($line->customer_id);
			print $soc->getNomURL(1);
		} else {
			print '&nbsp;';
		}
		print '</td>';
		print '<td>' . stripslashes(dol_trunc($line->fromintitule, 60)) . '</td>';
		print '<td>' . $line->fromref . '</td>';
		print '<td>' . $line->fromrefinterne . '</td>';
		print '<td><a href="' . dol_buildpath('/agefodd/trainee/session.php', 1) . '?id=' . $line->trainee_id . '">' . $line->trainee_name . ' ' . $line->trainee_firstname . '</a></td>';
		print '<td>' . dol_print_email($line->trainee_mail).'</td>';
		print '<td>' . dol_print_date($line->dated, 'daytext') . '</td>';
		print '<td>' . dol_print_date($line->datef, 'daytext') . '</td>';
		print '<td>' . $line->certif_code . '</td>';

		print '<td>' . $line->certif_label . '</td>';

		print '<td>' . dol_print_date($line->certif_dt_start, 'daytextshort') . '</td>';

		print '<td>' . dol_print_date($line->certif_dt_end, 'daytextshort') . '</td>';

		if(!empty($line->certif_dt_end) && $line->certif_dt_end < dol_now()) $style = "background-color:red;";
		elseif(!empty($line->certif_dt_warning) && $line->certif_dt_warning < dol_now()) $style = "background-color:orange;";
		else $style = "background-color:green;";

		print '<td style="'.$style.'">'.dol_print_date($line->certif_dt_warning, 'daytextshort').'</td>';
		print "</tr>\n";

		$oldid = $line->rowid;

		$i ++;
	}
	print '</form>';
	print "</table>";
} else {
	setEventMessage($agf->error, 'errors');
}
print '</div>';

llxFooter();
$db->close();
