<?php
/*
 * Copyright (C) 2012-2014  Florian Henry   <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 * \file agefodd/session/list_ope.php
 * \ingroup agefodd
 * \brief list of session
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
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;

// Search criteria
$search_trainning_name = GETPOST("search_trainning_name", 'none');
$search_soc = GETPOST("search_soc", 'none');
$search_teacher_id = GETPOST("search_teacher_id", 'none');
$search_training_ref = GETPOST("search_training_ref", 'alpha');
$search_start_date = dol_mktime(0, 0, 0, GETPOST('search_start_datemonth', 'int'), GETPOST('search_start_dateday', 'int'), GETPOST('search_start_dateyear', 'int'));
$search_end_date = dol_mktime(0, 0, 0, GETPOST('search_end_datemonth', 'int'), GETPOST('search_end_dateday', 'int'), GETPOST('search_end_dateyear', 'int'));
$search_site = GETPOST("search_site", 'none');
$search_training_ref_interne = GETPOST('search_training_ref_interne', 'alpha');
$search_type_session = GETPOST("search_type_session", 'int');
$training_view = GETPOST("training_view", 'int');
$site_view = GETPOST('site_view', 'int');
$search_sale = GETPOST('search_sale', 'int');
$search_id = GETPOST('search_id', 'int');
$search_soc_requester = GETPOST('search_soc_requester', 'alpha');
$search_alert = GETPOST('search_alert', 'alpha');
$search_session_ref = GETPOST('search_session_ref', 'alpha');

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x", 'none')) {
	$search_trainning_name = '';
	$search_soc = '';
	$search_teacher_id = "";
	$search_training_ref = '';
	$search_start_date = "";
	$search_end_date = "";
	$search_site = "";
	$search_training_ref_interne = "";
	$search_type_session = "";
	$search_id = '';
	$search_soc_requester = '';
	$search_alert='';
}

$filter = array ();
if (! empty($search_trainning_name)) {
	$filter ['c.intitule'] = $search_trainning_name;
	$option .= '&search_trainning_name=' . $search_trainning_name;
}
if (! empty($search_session_ref)) {
	$filter['s.ref'] = $search_session_ref;
	$option .= '&search_session_ref=' . $search_session_ref;
}
if (! empty($search_sale)) {
	$filter ['sale.fk_user_com'] = $search_sale;
	$option .= '&search_sale=' . $search_sale;
}
if (! empty($search_soc_requester)) {
	$filter ['sorequester.nom'] = $search_soc_requester;
	$option .= '&search_soc_requester=' . $search_soc_requester;
}
if (! empty($search_soc)) {
	$filter ['so.nom'] = $search_soc;
	$option .= '&search_soc=' . $search_soc;
}
if (! empty($search_teacher_id)  && $search_teacher_id != - 1) {
	$filter ['f.rowid'] = $search_teacher_id;
	$option .= '&search_teacher_id=' . $search_teacher_id;
}
if (! empty($search_training_ref)) {
	$filter ['c.ref'] = $search_training_ref;
	$option .= '&search_training_ref=' . $search_training_ref;
}
if (! empty($search_start_date)) {
	$filter ['s.dated'] = $db->idate($search_start_date);
	$option .= '&search_start_datemonth=' . dol_print_date($search_start_date, '%m') . '&search_start_dateday=' . dol_print_date($search_start_date, '%d') . '&search_start_dateyear=' . dol_print_date($search_start_date, '%Y');
}
if (! empty($search_end_date)) {
	$filter ['s.datef'] = $db->idate($search_end_date);
	$option .= '&search_end_datemonth=' . dol_print_date($search_end_date, '%m') . '&search_end_dateday=' . dol_print_date($search_end_date, '%d') . '&search_end_dateyear=' . dol_print_date($search_end_date, '%Y');
}
if (! empty($search_site) && $search_site != - 1) {
	$filter ['s.fk_session_place'] = $search_site;
	$option .= '&search_site=' . $search_site;
}
if (! empty($search_training_ref_interne)) {
	$filter ['c.ref_interne'] = $search_training_ref_interne;
	$option .= '&search_training_ref_interne=' . $search_training_ref_interne;
}
if ($search_type_session != '' && $search_type_session != - 1) {
	$filter ['s.type_session'] = $search_type_session;
	$option .= '&search_type_session=' . $search_type_session;
}
if (! empty($search_id)) {
	$filter ['s.rowid'] = $search_id;
	$option .= '&search_id=' . $search_id;
}
if (! empty($search_alert)) {
	$filter ['alert'] = $search_alert;
	$option .= '&search_alert=' . $search_alert;
}
if (!empty($limit)) {
	$option .= '&limit=' . $limit;
}

if (empty($sortorder)) {
	$sortorder = "DESC";
}
if (empty($sortfield)) {
	$sortfield = "s.dated";
}

if (empty($page) || $page == -1) { $page = 0; }


$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

//$hookmanager->initHooks(array('sessionopelist'));

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother = new FormOther($db);

$title = $langs->trans("AgfMenuSessListOpe");
llxHeader('', $title);

if ($training_view && ! empty($search_training_ref)) {
	$agf = new Formation($db);
	$result = $agf->fetch('', $search_training_ref);

	$head = training_prepare_head($agf);

	dol_fiche_head($head, 'sessions', $langs->trans("AgfCatalogDetail"), 0, 'label');

	$agf->printFormationInfo();
	print '</div>';
}

if ($site_view) {
	$agf = new Agefodd_place($db);
	$result = $agf->fetch($search_site);

	if ($result) {
		$head = site_prepare_head($agf);

		dol_fiche_head($head, 'sessions', $langs->trans("AgfSessPlace"), 0, 'address');
	}

	$agf->printPlaceInfo();
	print '</div>';
}

$agf = new Agsession($db);

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $agf->fetch_all_with_task_state($sortorder, $sortfield, 0, 0, $filter, $user);
}
$resql = $agf->fetch_all_with_task_state($sortorder, $sortfield, $limit, $offset, $filter, $user);

if ($resql != - 1) {
	$num = $resql;


	print '<form method="post" action="' . $_SERVER ['PHP_SELF'] .'" name="search_form">' . "\n";
	if (! empty($sortfield)) {
		print '<input type="hidden" name="sortfield" value="' . $sortfield . '"/>';
	}
	if (! empty($sortorder)) {
		print '<input type="hidden" name="sortorder" value="' . $sortorder . '"/>';
	}
	if (! empty($page)) {
		print '<input type="hidden" name="page" value="' . $page . '"/>';
	}
	if (! empty($limit)) {
		print '<input type="hidden" name="limit" value="' . $limit . '"/>';
	}

	print_barre_liste($title, $page, $_SERVER ['PHP_SELF'], $option, $sortfield, $sortorder, '', $num, $nbtotalofrecords,'title_generic.png', 0, '', '', $limit);

	// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid) {
		$moreforfilter .= $langs->trans('SalesRepresentatives') . ': ';
		$moreforfilter .= $formother->select_salesrepresentatives($search_sale, 'search_sale', $user);
	}
	if ($moreforfilter) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$i = 0;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';

	print_liste_field_titre($langs->trans("Id"), $_SERVER ['PHP_SELF'], "s.rowid", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("SessionRef"), $_SERVER['PHP_SELF'], "s.ref", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfDateDebut"), $_SERVER ['PHP_SELF'], "s.dated", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfDateFin"), $_SERVER ['PHP_SELF'], "s.datef", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfIntitule"), $_SERVER ['PHP_SELF'], "c.intitule", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfFormateur"), $_SERVER ['PHP_SELF'], "", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfLieu"), $_SERVER ['PHP_SELF'], "p.ref_interne", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Company"), $_SERVER ['PHP_SELF'], "so.nom", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfTypeRequester"), $_SERVER ['PHP_SELF'], "sorequester.nom", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfAlertDay"), $_SERVER ['PHP_SELF'], "", '', $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfYDaysBeforeAlert"), $_SERVER ['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfXDaysBeforeAlert"), $_SERVER ['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfZDaysBeforeAlert"), $_SERVER ['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfAlertLevel3Short"), $_SERVER ['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfNbreParticipants"), $_SERVER ['PHP_SELF'], "s.nb_stagiaire", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfFormTypeSession"), $_SERVER ['PHP_SELF'], "s.type_session", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Comment."), $_SERVER ['PHP_SELF'], "", '', $option, '', $sortfield, $sortorder);
	print "</tr>\n";

	print '<tr class="liste_titre">';

	print '<td class="liste_titre"><input type="text" class="flat" name="search_id" value="' . $search_id . '" size="2"></td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_session_ref" id="search_session_ref" value="' . $search_session_ref . '" size="15">';
	print '</td>';

	print '<td class="liste_titre">';
	print $form->select_date($search_start_date, 'search_start_date', 0, 0, 1, 'search_form');
	print '</td>';

	print '<td class="liste_titre">';
	print $form->select_date($search_end_date, 'search_end_date', 0, 0, 1, 'search_form');
	print '</td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_trainning_name" value="' . $search_trainning_name . '" size="20">';
	print '</td>';

	print '<td class="liste_titre">';
	print $formAgefodd->select_formateur($search_teacher_id, 'search_teacher_id', '', 1);
	print '</td>';

	print '<td class="liste_titre">';
	print $formAgefodd->select_site_forma($search_site, 'search_site', 1);
	print '</td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_soc" value="' . $search_soc . '" size="20">';
	print '</td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_soc_requester" value="' . $search_soc_requester . '" size="20">';
	print '</td>';

	// task
	print '<td class="liste_titre">';
	print '</td>';
	print '<td class="liste_titre">';
	print '</td>';
	print '<td class="liste_titre">';
	print '</td>';
	print '<td class="liste_titre">';
	print '</td>';
	print '<td class="liste_titre">';
	print '</td>';
	// NbParticipant
	print '<td class="liste_titre">';
	print '</td>';

	print '<td class="liste_titre">';
	print $formAgefodd->select_type_session('search_type_session', $search_type_session, 1);
	print '</td>';

	// Comment
	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	print '</td>';

	print "</tr>\n";


	$var = true;
    $oldid = null;
	foreach ( $agf->lines as $line ) {

		if ($line->rowid != $oldid) {
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

			print '<td  style="background:#' . $line->color . '"><a' . $color_a . ' href="administrative.php?id=' . $line->rowid . '">' . img_object($langs->trans("AgfShowDetails"), "service") . ' ' . $line->rowid . '</a></td>';
			print '<td  style="background:#' . $line->color . '"><a' . $color_a . ' href="administrative.php?id=' . $line->rowid . '">' . img_object($langs->trans("AgfShowDetails"), "service") . ' ' . $line->sessionref . '</a></td>';
			print '<td>' . dol_print_date($line->dated, 'daytext') . '</td>';
			print '<td>' . dol_print_date($line->datef, 'daytext') . '</td>';
			print '<td>' . stripslashes(dol_trunc($line->intitule, 60)) . '</td>';
			// trainer
			print '<td>';
			$trainer = new Agefodd_teacher($db);
			if (! empty($line->trainerrowid)) {
				$trainer->fetch($line->trainerrowid);
			}
			if (! empty($trainer->id)) {
				print ucfirst(strtolower($trainer->civilite)) . ' ' . strtoupper($trainer->name) . ' ' . ucfirst(strtolower($trainer->firstname));
			} else {
				print '&nbsp;';
			}
			print '</td>';

			print '<td><a href="'. dol_buildpath('/agefodd/site/card.php?id=', 1) . $line->fk_session_place . '">' . stripslashes($line->ref_interne) . '</a></td>';

			print '<td>';
			if (! empty($line->socid) && $line->socid != - 1) {
				$soc = new Societe($db);
				$soc->fetch($line->socid);
				print $soc->getNomURL(1);
			} else {
				print '&nbsp;';
			}
			print '</td>';
			// Demandeur
			print '<td>';
			if (! empty($line->socrequesterid) && $line->socrequesterid != - 1) {
				$soc = new Societe($db);
				$soc->fetch($line->socrequesterid);
				print $soc->getNomURL(1);
			} else {
				print '&nbsp;';
			}
			print '</td>';

			print '<td>' . $line->task0 . '</td>';
			print '<td>' . $line->task1 . '</td>';
			print '<td>' . $line->task2 . '</td>';
			print '<td>' . $line->morethanzday . '</td>';
			print '<td>' . $line->task3 . '</td>';

            $line->type_session = intval($line->type_session);

			print '<td>' . $line->nb_stagiaire . '</td>';
			print '<td>' . (!empty($line->type_session) ? $langs->trans('AgfFormTypeSessionInter') : $langs->trans('AgfFormTypeSessionIntra')) . '</td>';
			print '<td title="' . stripslashes($line->notes) . '">' . stripslashes(dol_trunc($line->notes, 60)) . '</td>';

			print "</tr>\n";
		} else {
			print "<tr $bc[$var]>";
			print '<td></td>'; // id
			print '<td></td>'; // ref
			print '<td></td>'; // dates
			print '<td></td>'; // datef
			print '<td></td>'; // intitule
			                   // trainer
			print '<td>';
			$trainer = new Agefodd_teacher($db);
			if (! empty($line->trainerrowid)) {
				$trainer->fetch($line->trainerrowid);
			}
			if (! empty($trainer->id)) {
				print ucfirst(strtolower($trainer->civilite)) . ' ' . strtoupper($trainer->name) . ' ' . ucfirst(strtolower($trainer->firstname));
			} else {
				print '&nbsp;';
			}
			print '</td>';
			print '<td></td>'; // lieu

			print '<td></td>'; // soc
			print '<td></td>'; // demandeur
			print '<td></td>'; // task0
			print '<td></td>'; // task1
			print '<td></td>'; // task2
			print '<td></td>'; // task3
			print '<td></td>'; // nbtrainee
			print '<td></td>'; // type session
			print "</tr>\n";
		}

		$oldid = $line->rowid;

		$i ++;
	}

	print "</table>";
	print '</form>';
} else {
	setEventMessage($agf->error, 'errors');
}

llxFooter();
$db->close();
