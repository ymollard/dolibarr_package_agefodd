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
 * \file agefodd/session/list.php
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
require_once ('../class/agefodd_session_element.class.php');
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

// Search criteria
$search_trainning_name = GETPOST("search_trainning_name");
$search_soc = GETPOST("search_soc");
$search_teacher_id = GETPOST("search_teacher_id");
$search_training_ref = GETPOST("search_training_ref", 'alpha');
$search_start_date = dol_mktime(0, 0, 0, GETPOST('search_start_datemonth', 'int'), GETPOST('search_start_dateday', 'int'), GETPOST('search_start_dateyear', 'int'));
$search_end_date = dol_mktime(0, 0, 0, GETPOST('search_end_datemonth', 'int'), GETPOST('search_end_dateday', 'int'), GETPOST('search_end_dateyear', 'int'));
$search_site = GETPOST("search_site");
$search_training_ref_interne = GETPOST('search_training_ref_interne', 'alpha');
$search_type_session = GETPOST("search_type_session", 'int');
$training_view = GETPOST("training_view", 'int');
$site_view = GETPOST('site_view', 'int');
$status_view = GETPOST('status', 'int');
$search_id = GETPOST('search_id', 'int');
$search_month = GETPOST('search_month', 'aplha');
$search_year = GETPOST('search_year', 'int');
$search_soc_requester = GETPOST('search_soc_requester', 'alpha');
$search_session_status = GETPOST('search_session_status');

$search_sale = GETPOST('search_sale', 'int');
$search_session_status = GETPOST('search_session_status');

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x")) {
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
	$search_month = '';
	$search_year = '';
	$search_sale = '';
	$search_soc_requester = '';
	$search_session_status = '';
}

$filter = array ();
if (! empty($search_trainning_name)) {
	$filter ['c.intitule'] = $search_trainning_name;
}
if (! empty($search_soc)) {
	$filter ['so.nom'] = $search_soc;
}
if (! empty($search_soc_requester)) {
	$filter ['sorequester.nom'] = $search_soc_requester;
}
if (! empty($search_sale)) {
	$filter ['sale.fk_user_com'] = $search_sale;
}
if (! empty($search_teacher_id) && $search_teacher_id != - 1) {
	$filter ['f.rowid'] = $search_teacher_id;
}
if (! empty($search_training_ref)) {
	$filter ['c.ref'] = $search_training_ref;
}
if (! empty($search_start_date)) {
	$filter ['s.dated'] = $db->idate($search_start_date);
}
if (! empty($search_end_date)) {
	$filter ['s.datef'] = $db->idate($search_end_date);
}
if (! empty($search_site) && $search_site != - 1) {
	$filter ['s.fk_session_place'] = $search_site;

	if (empty($sortorder)) {
		$sortorder = "DESC";
	}

}
if (! empty($search_training_ref_interne)) {
	$filter ['c.ref_interne'] = $search_training_ref_interne;
}
if ($search_type_session != '' && $search_type_session != - 1) {
	$filter ['s.type_session'] = $search_type_session;
}
if (! empty($status_view)) {
	$filter ['s.status'] = $status_view;
	$search_session_status=$status_view;
}
if (! empty($search_id)) {
	$filter ['s.rowid'] = $search_id;
}

if (! empty($search_month)) {
	$filter ['MONTH(s.dated)'] = $search_month;
}

if (! empty($search_year)) {
	$filter ['YEAR(s.dated)'] = $search_year;
}
if (! empty($search_session_status)) {
	$filter ['s.status'] = $search_session_status;
}

if (empty($sortorder)) {
	$sortorder = "ASC";
}
if (empty($sortfield)) {
	$sortfield = "s.dated";
}



if ($page == - 1) {
	$page = 0;
}

$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother = new FormOther($db);

if ($status_view == 1) {
	$title = $langs->trans("AgfMenuSessDraftList");
	if (empty($sortorder)) {
		$sortorder = "ASC";
	}
	if (empty($sortfield)) {
		$sortfield = "s.datec";
	}
} elseif ($status_view == 2) {
	$title = $langs->trans("AgfMenuSessConfList");
} elseif ($status_view == 3) {
	$title = $langs->trans("AgfMenuSessNotDoneList");
} elseif ($status_view == 4) {
	$title = $langs->trans("AgfMenuSessArch");
	if (empty($sortorder))
		$sortorder = "DESC";
	if (empty($sortfield))
		$sortfield = "s.datec";
} else {
	$title = $langs->trans("AgfMenuSess");
}

llxHeader('', $title);


if (empty($sortorder))
	$sortorder = "ASC";
if (empty($sortfield))
	$sortfield = "s.dated";

if ($training_view && ! empty($search_training_ref)) {
	$agf = new Agefodd($db);
	$result = $agf->fetch('', $search_training_ref);

	$head = training_prepare_head($agf);

	dol_fiche_head($head, 'sessions', $langs->trans("AgfCatalogDetail"), 0, 'label');

	$agf->printFormationInfo();
	print '</div>';
}

if (! empty($site_view)) {
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
	$nbtotalofrecords = $agf->fetch_all($sortorder, $sortfield, 0, 0, $filter, $user);
	if ($user->rights->agefodd->session->margin) {
		$total_sellprice=0;
		$total_costtrainer=0;
		$total_costother=0;
		$total_facththf=0;
		$total_margin=0;
		$total_percentmargin=0;
		foreach ( $agf->lines as $line ) {
			if ($line->rowid != $oldid) {
				$total_sellprice+=$line->sell_price;
				$total_costtrainer+=$line->cost_trainer;
				$total_costother+=$line->cost_other;

				$amount_act_invoiced_less_charges=0;
				if (! empty($line->cost_sell_charges) && $line->cost_sell_charges <= $line->invoice_amount) {
					$amount_act_invoiced_less_charges = $line->invoice_amount - $line->cost_sell_charges;
				} else {
					$amount_act_invoiced_less_charges = $line->invoice_amount;
				}
				$total_facththf += $amount_act_invoiced_less_charges;


				if ($line->invoice_amount > 0) {
					$margin = $line->invoice_amount - ($line->cost_trainer + $line->cost_other);
				} else {
					$margin = 0;
				}
				$total_margin += $margin;

				$oldid=$line->rowid;
			}
		}
		if (!empty($total_facththf)) {
			$total_percentmargin=price((($total_margin * 100) / $total_facththf), 0, $langs, 1, 0, 1).'%';
		} else {
			$total_percentmargin='n/a';
		}
	}


}
$resql = $agf->fetch_all($sortorder, $sortfield, $conf->liste_limit, $offset, $filter, $user);

if ($resql != - 1) {
	$num = $resql;

	if ($status_view == 1) {
		$menu = $langs->trans("AgfMenuSessDraftList");
	} elseif ($status_view == 2) {
		$menu = $langs->trans("AgfMenuSessConfList");
	} elseif ($status_view == 3) {
		$menu = $langs->trans("AgfMenuSessNotDoneList");
	} elseif ($status_view == 4) {
		$menu = $langs->trans("AgfMenuSessArch");
	} elseif (! empty($site_view)) {
		$menu = $langs->trans("AgfSessPlace");
	} elseif (! empty($training_view)) {
		$menu = $langs->trans("AgfCatalogDetail");
	} else {
		$menu = $langs->trans("AgfMenuSess");
	}

	if (! empty($search_trainning_name))
		$option .= '&search_trainning_name=' . $search_trainning_name;
	if (! empty($search_soc))
		$option .= '&search_soc=' . $search_soc;
	if (! empty($search_sale))
		$option .= '&search_sale=' . $search_sale;
	if (! empty($status_view))
		$option .= '&status=' . $status_view;
	if (! empty($search_session_status))
		$option .= '&search_session_status=' . $search_session_status;
	if (! empty($search_id))
		$option .= '&search_id=' . $search_id;
	if (! empty($search_month))
		$option .= '&search_month=' . $search_month;
	if (! empty($search_year))
		$option .= '&search_year=' . $search_year;
	if (! empty($training_view))
		$option .= '&training_view=' . $training_view;
	if (! empty($site_view))
		$option .= '&site_view=' . $site_view;
	if (! empty($search_teacher_id))
		$option .= '&search_teacher_id=' . $search_teacher_id;
	if (! empty($search_training_ref))
		$option .= '&search_training_ref=' . $search_training_ref;
	if (! empty($search_start_date))
		$option .= '&search_start_datemonth=' . dol_print_date($search_start_date, '%m') . '&search_start_dateday=' . dol_print_date($search_start_date, '%d') . '&search_start_dateyear=' . dol_print_date($search_start_date, '%Y');
	if (! empty($search_end_date))
		$option .= '&search_end_datemonth=' . dol_print_date($search_end_date, '%m') . '&search_end_dateday=' . dol_print_date($search_end_date, '%d') . '&search_end_dateyear=' . dol_print_date($search_end_date, '%Y');
	if (! empty($search_site) && $search_site != - 1)
		$option .= '&search_site=' . $search_site;
	if (! empty($search_training_ref_interne))
		$option .= '&search_training_ref_interne=' . $search_training_ref_interne;
	if ($search_type_session != '' && $search_type_session != - 1)
		$option .= '&search_type_session=' . $search_type_session;

	print_barre_liste($menu, $page, $_SERVEUR ['PHP_SELF'], $option, $sortfield, $sortorder, '', $num, $nbtotalofrecords);

	print '<form method="post" action="' . $_SERVER ['PHP_SELF'] . '" name="search_form">' . "\n";
	if (! empty($status_view))
		print '<input type="hidden" name="status" value="' . $status_view . '"/>';
	if (! empty($site_view))
		print '<input type="hidden" name="site_view" value="' . $site_view . '"/>';
	if (! empty($training_view))
		print '<input type="hidden" name="training_view" value="' . $training_view . '"/>';
	if (! empty($sortfield))
		print '<input type="hidden" name="sortfield" value="' . $sortfield . '"/>';
	if (! empty($sortorder))
		print '<input type="hidden" name="sortorder" value="' . $sortorder . '"/>';
	if (! empty($page))
		print '<input type="hidden" name="page" value="' . $page . '"/>';

		// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid) {
		$moreforfilter .= $langs->trans('SalesRepresentatives') . ': ';
		$moreforfilter .= $formother->select_salesrepresentatives($search_sale, 'search_sale', $user);
	}

	$moreforfilter .= $langs->trans('Period') . '(' . $langs->trans("AgfDateDebut") . ')' . ': ';
	$moreforfilter .= $langs->trans('Month') . ':<input class="flat" type="text" size="4" name="search_month" value="' . $search_month . '">';
	$moreforfilter .= $langs->trans('Year') . ':' . $formother->selectyear($search_year ? $search_year : - 1, 'search_year', 1, 20, 5);
	if ($user->rights->agefodd->session->margin) {
		$moreforfilter .= '<span id="showhidemargininfo" style="cursor:pointer">'.$langs->transnoentities('AgfDisplayMarginInfo').'</span>';
	}
	if ($moreforfilter) {
		print '<div class="liste_titre">';
		print $moreforfilter;
		print '</div>';
	}

	print '<script type="text/javascript">'."\n";
	print '$(document).ready(function () {'."\n";
	print '	$(\'#showhidemargininfo\').click(function(){'."\n";
	print '		$(\'[name*=margininfo]\').toggle();'."\n";
	print '		if ($(this).text()==\''.$langs->transnoentities('AgfDisplayMarginInfo').'\') {'."\n";
	print '			$(this).text(\''.$langs->transnoentities('AgfHideMarginInfo').'\');'."\n";
	print '		} else {'."\n";
	print '			$(this).text(\''.$langs->transnoentities('AgfDisplayMarginInfo').'\');'."\n";
	print '		}'."\n";
	print '	});'."\n";
	print '});'."\n";
	print '</script>'."\n";

	$i = 0;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Id"), $_SERVEUR ['PHP_SELF'], "s.rowid", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Company"), $_SERVER ['PHP_SELF'], "so.nom", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfFormateur"), $_SERVER ['PHP_SELF'], "", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfIntitule"), $_SERVEUR ['PHP_SELF'], "c.intitule", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Ref"), $_SERVEUR ['PHP_SELF'], "c.ref", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfRefInterne"), $_SERVEUR ['PHP_SELF'], "c.ref_interne", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfFormTypeSession"), $_SERVEUR ['PHP_SELF'], "s.type_session", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfDateDebut"), $_SERVEUR ['PHP_SELF'], "s.dated", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfDateFin"), $_SERVEUR ['PHP_SELF'], "s.datef", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfStatusSession"), $_SERVEUR ['PHP_SELF'], "dictstatus.intitule", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfLieu"), $_SERVEUR ['PHP_SELF'], "p.ref_interne", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfNbreParticipants"), $_SERVEUR ['PHP_SELF'], "s.nb_stagiaire", '', $option, '', $sortfield, $sortorder);
	if ($user->rights->agefodd->session->margin) {
		print_liste_field_titre($langs->trans("AgfAmoutHTHF"), $_SERVER ['PHP_SELF'], "s.sell_price", "", $option, ' name="margininfo1" style="display:none" ', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("AgfCostTrainer"), $_SERVER ['PHP_SELF'], "s.cost_trainer", "", $option, ' name="margininfo2" style="display:none" ', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("AgfCostOther"), $_SERVER ['PHP_SELF'], "", "", $option, ' name="margininfo3" style="display:none" ', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("AgfFactAmount"), $_SERVER ['PHP_SELF'], "", "", $option, ' name="margininfo4" style="display:none" ', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("AgfMargin"), $_SERVER ['PHP_SELF'], "", "", $option, ' name="margininfo5" style="display:none" ', $sortfield, $sortorder);
	}
	print_liste_field_titre($langs->trans("AgfListParticipantsStatus"), $_SERVEUR ['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
	print "</tr>\n";

	print '<tr class="liste_titre">';

	print '<td><input type="text" class="flat" name="search_id" value="' . $search_id . '" size="2"></td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_soc" value="' . $search_soc . '" size="20">';
	print '</td>';

	print '<td class="liste_titre">';
	print $formAgefodd->select_formateur($search_teacher_id, 'search_teacher_id', '', 1);
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
	print $formAgefodd->select_type_session('search_type_session', $search_type_session, 1);
	print '</td>';

	print '<td class="liste_titre">';
	print $form->select_date($search_start_date, 'search_start_date', 0, 0, 1, 'search_form');
	print '</td>';

	print '<td class="liste_titre">';
	print $form->select_date($search_end_date, 'search_end_date', 0, 0, 1, 'search_form');
	print '</td>';

	print '<td class="liste_titre">';
	print $formAgefodd->select_session_status($search_session_status, 'search_session_status', 't.active=1', 1);
	print '</td>';

	print '<td class="liste_titre">';
	print $formAgefodd->select_site_forma($search_site, 'search_site', 1);
	print '</td>';

	if ($user->rights->agefodd->session->margin) {
		print '<td class="liste_titre" name="margininfo6" style="display:none">';
		print '</td>';

		print '<td class="liste_titre" name="margininfo7"  style="display:none">';
		print '</td>';

		print '<td class="liste_titre" name="margininfo8"  style="display:none">';
		print '</td>';

		print '<td class="liste_titre" name="margininfo9"  style="display:none">';
		print '</td>';

		print '<td class="liste_titre" name="margininfo10"  style="display:none">';
		print '</td>';
	}

	print '<td class="liste_titre">';
	print '</td>';

	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	print '</td>';

	print "</tr>\n";
	print '</form>';

	$var = true;
	$oldid=0;
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

			print '<td  style="background: #' . $line->color . '"><a' . $color_a . ' href="card.php?id=' . $line->rowid . '">' . img_object($langs->trans("AgfShowDetails"), "service") . ' ' . $line->rowid . '</a></td>';
			print '<td>';

			if (! empty($line->socid) && $line->socid != - 1) {
				$soc = new Societe($db);
				$soc->fetch($line->socid);
				print $soc->getNomURL(1);
			} else {
				print '&nbsp;';
			}
			print '</td>';
			print '<td>';
			$trainer = new Agefodd_teacher($db);
			if (! empty($line->trainerrowid)) {
				$trainer->fetch($line->trainerrowid);
			}
			if (! empty($trainer->id)) {
				print $trainer->getNomUrl();
			} else {
				print '&nbsp;';
			}
			print '</td>';
			$couleur_rgb_training = agf_hex2rgb($line->trainingcolor);
			$color_training = '';
			if ($line->trainingcolor && ((($couleur_rgb_training [0] * 299) + ($couleur_rgb_training [1] * 587) + ($couleur_rgb_training [2] * 114)) / 1000) < 125) {
				$color_training = ' style="color: #FFFFFF;background: #' . $line->trainingcolor . '"';
			} else {
				$color_training=' style="background: #' . $line->trainingcolor . '" ';
			}

			print '<td ' . $color_training . '>' . stripslashes(dol_trunc($line->intitule, 60)) . '</td>';
			print '<td>' . $line->ref . '</td>';
			print '<td>' . $line->training_ref_interne . '</td>';
			print '<td>' . ($line->type_session ? $langs->trans('AgfFormTypeSessionInter') : $langs->trans('AgfFormTypeSessionIntra')) . '</td>';
			print '<td>' . dol_print_date($line->dated, 'daytextshort') . '</td>';
			print '<td>' . dol_print_date($line->datef, 'daytextshort') . '</td>';
			print '<td>';
			print $line->status_lib;
			print '</td>';
			print '<td>' . stripslashes($line->ref_interne) . '</td>';
			print '<td>' . $line->nb_stagiaire . '</td>';

			if ($user->rights->agefodd->session->margin) {
				print '<td  nowrap="nowrap" name="margininfoline1'.$line->rowid.'" style="display:none">' . price($line->sell_price,0, $langs, 1, -1, -1, 'auto') . '</td>';
				print '<td  nowrap="nowrap"  name="margininfoline2'.$line->rowid.'" style="display:none">' . price($line->cost_trainer,0, $langs, 1, -1, -1, 'auto') . '</td>';
				print '<td  nowrap="nowrap"  name="margininfoline3'.$line->rowid.'" style="display:none">' . price($line->cost_other,0, $langs, 1, -1, -1, 'auto') . '</td>';
				print '<td  nowrap="nowrap"  name="margininfoline4'.$line->rowid.'" style="display:none">';
				$amount_act_invoiced_less_charges=0;
				// Remove charges of product of category 'frais'
				if (! empty($line->cost_sell_charges) && $line->cost_sell_charges != - 1 && $line->cost_sell_charges <= $line->invoice_amount) {
					$amount_act_invoiced_less_charges = $line->invoice_amount - $line->cost_sell_charges;
				} else {
					$amount_act_invoiced_less_charges = $line->invoice_amount;
				}
				$totalfacththf += $amount_act_invoiced_less_charges;

				if ($amount_act_invoiced_less_charges == 0 && $line->sell_price!=0) {
					$bgfact = 'red';
				} else {
					$bgfact = '';
				}
				print '<font style="color:' . $bgfact . '">' . price($amount_act_invoiced_less_charges,0, $langs, 1, -1, -1, 'auto'). '</font>';
				print '</td>';


				print '<td nowrap="nowrap"  name="margininfoline5'.$line->rowid.'" style="display:none">';
				if ($line->invoice_amount > 0) {
					$margin = $line->invoice_amount - ($line->cost_trainer + $line->cost_other);
					$percentmargin = price((($margin * 100) / $line->invoice_amount), 0, $langs, 1, 0, 1) . '%';
				} else {
					$margin = 0;
					$percentmargin = "n/a";
				}
				print price($margin,0, '', 1, -1, -1, 'auto') . '(' . $percentmargin . ')';
				print '</td>';
			}

			if (! empty($line->nb_subscribe_min)) {
				if ($line->nb_confirm >= $line->nb_subscribe_min) {
					$style = 'style="background: green"';
				} else {
					$style = 'style="background: red"';
				}
			} else {
				$style = '';
			}

			print '<td ' . $style . '>' . $line->nb_prospect . '/' . $line->nb_confirm . '/' . $line->nb_cancelled . '</td>';
			print "</tr>\n";
		} else {
			print "<tr $bc[$var]>";
			print '<td></td>';
			print '<td></td>';
			print '<td>';
			$trainer = new Agefodd_teacher($db);
			if (! empty($line->trainerrowid)) {
				$trainer->fetch($line->trainerrowid);
			}
			if (! empty($trainer->id)) {
				print $trainer->getNomUrl();
			} else {
				print '&nbsp;';
			}
			print '</td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			if ($user->rights->agefodd->session->margin) {
				print '<td name="margininfolineb1'.$line->rowid.'" style="display:none"></td>';
				print '<td name="margininfolineb2'.$line->rowid.'" style="display:none"></td>';
				print '<td name="margininfolineb3'.$line->rowid.'" style="display:none"></td>';
				print '<td name="margininfolineb4'.$line->rowid.'" style="display:none"></td>';
				print '<td name="margininfolineb5'.$line->rowid.'" style="display:none"></td>';
			}
			print '<td></td>';
			print "</tr>\n";
		}

		$oldid = $line->rowid;

		$i ++;
	}
	if ($user->rights->agefodd->session->margin) {
		print '<tr class="liste_total" name="margininfototal" style="display:none">';
		print '<td>'.$langs->trans('Total').'</td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		if ($user->rights->agefodd->session->margin) {
			print '<td nowrap="nowrap">'.price($total_sellprice,0, '', 1, -1, -1, 'auto').'</td>';
			print '<td nowrap="nowrap">'.price($total_costtrainer,0, '', 1, -1, -1, 'auto').'</td>';
			print '<td nowrap="nowrap">'.price($total_costother,0, '', 1, -1, -1, 'auto').'</td>';
			print '<td nowrap="nowrap">'.price($total_facththf,0, '', 1, -1, -1, 'auto').'</td>';
			print '<td nowrap="nowrap">'.price($total_margin,0, '', 1, -1, -1, 'auto').'(' . $total_percentmargin . ')'.'</td>';
		}
		print '<td></td>';
		print "</tr>\n";
	}
	print "</table>";
} else {
	setEventMessage($agf->error, 'errors');
}

llxFooter();
$db->close();