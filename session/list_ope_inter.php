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
 * \file agefodd/session/list_ope_inter.php
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
require_once ('../class/agefodd_session_formateur.class.php');
require_once ('../class/agefodd_place.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../lib/agefodd.lib.php');
require_once ('../class/html.formagefodd.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');
require_once ('../class/agefodd_formateur.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
require_once ('../class/agefodd_session_element.class.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$hookmanager->initHooks(array('agefoddsessionlistopeinter'));

$parameters=array('from'=>'original');
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;

// Search criteria
$search_status = GETPOST('search_status', 'none');
$search_month = GETPOST('search_month', 'aplha');
$search_year = GETPOST('search_year', 'int');
$search_teacher_id = GETPOST("search_teacher_id", 'none');
$search_teacher_status = GETPOST("search_teacher_status", 'none');
$search_site = GETPOST("search_site", 'none');
$search_room_status = GETPOST("search_room_status", 'none');
$search_id = GETPOST('search_id', 'none');
$search_trainning_name = GETPOST('search_trainning_name', 'none');

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x", 'none')) {
	$search_status = '';
	$search_month = '';
	$search_year = '';
	$search_teacher_id = '';
	$search_teacher_status = '';
	$search_site = '';
	$search_id = '';
	$search_room_status = '';
	$search_trainning_name = '';
}

$filter = array ();
$option='';
if (! empty($search_id)) {
	$filter ['s.rowid'] = $search_id;
	$option .= '&search_id=' . $search_id;
}
if (! empty($search_status)) {
	$filter ['s.status'] = $search_status;
	$option .= '&search_status=' . $search_status;
}
if (empty($search_status)) {
	$filter ['!s.status'] = '(3,4)';
}
if (! empty($search_month)) {
	$filter ['MONTH(s.dated)'] = $search_month;
	$option .= '&search_month=' . $search_month;
}
if (! empty($search_year)) {
	$filter ['YEAR(s.dated)'] = $search_year;
	$option .= '&search_year=' . $search_year;
}
if (! empty($search_teacher_id) && $search_teacher_id != - 1) {
	$filter ['sf.rowid'] = $search_teacher_id;
	$option .= '&search_teacher_id=' . $search_teacher_id;
}
if ($search_teacher_status != '' && $search_teacher_status != - 1) {
	$filter ['sf.trainer_status'] = $search_teacher_status;
	$option .= '&search_teacher_status=' . $search_teacher_status;
}
if (! empty($search_site) && $search_site != - 1) {
	$filter ['s.fk_session_place'] = $search_site;
	$option .= '&search_site=' . $search_site;
}
if (! empty($search_room_status) && $search_room_status != - 1) {
	$option .= '&search_room_status=' . $search_room_status;
	if ($search_room_status == 'option') {
		$filter ['s.date_res_site'] = 'IS NOT NULL';
		$filter ['s.date_res_confirm_site'] = 'IS NULL';
	}
	if ($search_room_status == 'confirm') {
		$filter ['s.date_res_confirm_site'] = 'IS NOT NULL';
	}
}
if (! empty($search_trainning_name)) {
	$filter ['c.intitule'] = $search_trainning_name;
	$option .= '&search_trainning_name=' . $search_trainning_name;
}
if (!empty($limit)) {
	$option .= '&limit=' . $limit;
}

if (empty($sortorder)) {
	$sortorder = "ASC";
}
if (empty($sortfield)) {
	$sortfield = "s.dated";
}

$parameters=array('from'=>'original', 'filter' => &$filter, 'option' => &$option, 'sortorder' => &$sortorder, 'sortfield' => &$sortfield);
$reshook=$hookmanager->executeHooks('overrideFilter',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($page) || $page == -1) { $page = 0; }


$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother = new FormOther($db);

$title = $langs->trans("Suivie inter");
llxHeader('', $title);

$agf = new Agsession($db);

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $agf->fetch_all_inter($sortorder, $sortfield, 0, 0, $filter, $user);
}
$resql = $agf->fetch_all_inter($sortorder, $sortfield, $limit, $offset, $filter, $user);

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

	$moreforfilter = '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('Period') . '(' . $langs->trans("AgfDateDebut") . ')' . ': ';
	$moreforfilter .= $langs->trans('Month') . ':<input class="flat" type="text" size="4" name="search_month" value="' . $search_month . '">';
	$moreforfilter .= $langs->trans('Year') . ':' . $formother->selectyear($search_year ? $search_year : - 1, 'search_year', 1, 20, 5);
	$moreforfilter.='</div>';

	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';

	$i = 0;
	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste listwithfilterbefore" width="100%">';

	// Search Bar
	print '<tr class="liste_titre_filter">';

	// Id
	print '<td class="liste_titre"><input type="text" class="flat" name="search_id" value="' . $search_id . '" size="2"></td>';

	// Trainer
	print '<td class="liste_titre">';
	print $formAgefodd->select_session_status($search_status, "search_status", 't.active=1', 1);
	print '</td>';

	// date start
	print '<td class="liste_titre">';
	print '</td>';

	// Intitule
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_trainning_name" value="' . $search_trainning_name . '" size="20">';
	print '</td>';

	// MontantHT
	print '<td class="liste_titre">';
	print '</td>';

	// Trainer
	print '<td class="liste_titre">';
	print $formAgefodd->select_formateur($search_teacher_id, 'search_teacher_id', '', 1);
	print '</td>';

	// Trainer status
	print '<td class="liste_titre">';
	print $formAgefodd->select_trainer_session_status('search_teacher_status', $search_teacher_status, array (
			0,
			1,
			2,
			3
	), 1);
	print '</td>';

	// Formateur RN
	print '<td class="liste_titre">';
	print '</td>';

	// Lieu
	print '<td class="liste_titre">';
	print $formAgefodd->select_site_forma($search_site, 'search_site', 1);
	print '</td>';

	// Lieu status
	print '<td class="liste_titre">';
	$array_room_status = array (
			'option' => 'Option',
			'confirm' => 'Confirmé'
	);
	print $form->selectarray('search_room_status', $array_room_status, $search_room_status, 1);
	print '</td>';

	// Nb Part /Soc /Sub
	print '<td class="liste_titre">';
	print '</td>';

	// Nb nb inscript/confirm/canceled
	print '<td class="liste_titre">';
	print '</td>';

	// Convoc
	print '<td class="liste_titre">';
	print '</td>';

	// Support
	print '<td class="liste_titre">';
	print '</td>';

	// FEE edit
	print '<td class="liste_titre">';
	print '</td>';

	// Fact Client
	print '<td class="liste_titre">';
	print '</td>';

	// Att RN
	print '<td class="liste_titre">';
	print '</td>';

	// FEE Env
	print '<td class="liste_titre">';
	print '</td>';

	// FAct Formateur
	print '<td class="liste_titre">';
	print '</td>';

	// Fact Lieu
	print '<td class="liste_titre">';
	print '</td>';

	// Comment
	print '<td class="liste_titre" align="right">';
	print '</td>';

	// Action column
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

	print '<tr class="liste_titre">';
	print '<th class="liste_titre"><div class="nowrap">';
	print '<input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	print '</div></th>';
	print '<th class="liste_titre" colspan="3"></th>';
	print '<th class="liste_titre nowrap" style="border: 1px solid black" id="totalamount"></th>'; // montnant HTHF
	print '<th class="liste_titre" colspan="10"></th>';
	print '<th class="liste_titre nowrap" style="border: 1px solid black" id="totalamountfact"></th>'; // fact HTHF
	print '<th class="liste_titre" colspan="6"></th>';
	print '</tr>';

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Id"), $_SERVER ['PHP_SELF'], "s.rowid", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Status"), $_SERVER ['PHP_SELF'], "s.status", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Date Début"), $_SERVER ['PHP_SELF'], "s.dated", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfIntitule"), $_SERVER ['PHP_SELF'], "c.intitule", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Montant HT"), $_SERVER ['PHP_SELF'], "s.sell_price", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfFormateur"), $_SERVER ['PHP_SELF'], "", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Status"), $_SERVER ['PHP_SELF'], "", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Formateur RN"), $_SERVER ['PHP_SELF'], "trainerrn", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfLieu"), $_SERVER ['PHP_SELF'], "p.ref_interne", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Status"), $_SERVER ['PHP_SELF'], "", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Part /Soc /Sub"), $_SERVER ['PHP_SELF'], "", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Prosp./ Ann."), $_SERVER ['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Convoc"), $_SERVER ['PHP_SELF'], 'convoc', '', $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Support"), $_SERVER ['PHP_SELF'], 'support', '', $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("FEE Edit"), $_SERVER ['PHP_SELF'], 'ffeedit', '', $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Fact/C"), $_SERVER ['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Att RN"), $_SERVER ['PHP_SELF'], "attrn", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("FEE Env."), $_SERVER ['PHP_SELF'], "ffeenv", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Fact/F"), $_SERVER ['PHP_SELF'], "invtrainer", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Fact/L"), $_SERVER ['PHP_SELF'], "invroom", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Comment."), $_SERVER ['PHP_SELF'], "", '', $option, '', $sortfield, $sortorder);
	print_liste_field_titre(''); // Action

	print "</tr>\n";

	$var = true;
	foreach ( $agf->lines as $line ) {

		if ($line->id != $oldid) {
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

			print '<td  style="background: #' . $line->color . '"><a' . $color_a . ' href="card.php?id=' . $line->id . '">' . img_object($langs->trans("AgfShowDetails"), "service") . ' ' . $line->id . '</a></td>';

			print '<td>' . $line->statuslib . '</td>';
			print '<td>' . dol_print_date($line->dated, 'daytext') . '</td>';
			print '<td>' . stripslashes(dol_trunc($line->intitule, 60)) . '</td>';

			// Montant HT
			$agf_fin = new Agefodd_session_element($db);
			$agf_fin->fetch_by_session($line->id);
			print '<td>' . price($agf_fin->propal_sign_amount) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>';

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

			// Trainer status
			print '<td>';
			$statictrainercal = new Agefodd_session_formateur($db);
			print $statictrainercal->LibStatut($line->trainer_status, 0);
			print '</td>';

			// Trainer RN
			if ($line->trainerrn) {
				$src_state = dol_buildpath('/agefodd/img/ok.png', 1);
				$txtalt = $langs->trans("AgfTerminatedNoPoint");
			} else {
				$src_state = dol_buildpath('/agefodd/img/no.png', 1);
				$txtalt = $langs->trans("AgfTerminatedPoint");
			}
			print '<td align="center"><img title="'.$line->trainerrn.'" alt="' . $txtalt . '" src="' . $src_state . '"/></td>';

			// Lieu
			print '<td>' . stripslashes($line->ref_interne) . '</td>';

			// Lieu status
			print '<td>';
			if (!empty($line->date_res_confirm_site)) {
				print 'Confirmé';
			} elseif (!empty($line->date_res_site)) {
				print 'Option';
			}
			print '</td>';

			// Nb Part /Soc /Sub
			if (! empty($line->nb_subscribe_min)) {
				if ($line->nb_confirm >= $line->nb_subscribe_min) {
					$style = 'style="background: green"';
				} else {
					$style = 'style="background: red"';
				}
			} else {
				$style = '';
			}
			print '<td ' . $style . '>';
			$agf_session = new Agsession($db);
			$agf_session->fetch_societe_per_session($line->id);
			$nbsoc = 0;
			$nbsubro = 0;
			if (is_array($agf_session->lines) && count($agf_session->lines) > 0) {
				foreach ( $agf_session->lines as $linesoc ) {
					if ($linesoc->typeline == 'trainee_soc') {
						$nbsoc ++;
					}
					if ($linesoc->typeline == 'trainee_OPCA' || $linesoc->typeline == 'OPCA') {
						$nbsubro ++;
					}
				}
			}
			print $line->nb_confirm . '/' . $nbsoc . '/' . $nbsubro;
			print '</td>';

			// Nb incrit/confirm/cancell

			print '<td>' . $line->nb_prospect . '/' . $line->nb_cancelled . '</td>';

			// Convoc
			if ($line->convoc) {
				$src_state = dol_buildpath('/agefodd/img/ok.png', 1);
				$txtalt = $langs->trans("AgfTerminatedNoPoint");
			} else {
				$src_state = dol_buildpath('/agefodd/img/no.png', 1);
				$txtalt = $langs->trans("AgfTerminatedPoint");
			}
			print '<td align="center"><img title="'.$line->convoc.'" alt="' . $txtalt . '" src="' . $src_state . '"/></td>';

			// Support
			if ($line->support) {
				$src_state = dol_buildpath('/agefodd/img/ok.png', 1);
				$txtalt = $langs->trans("AgfTerminatedNoPoint");
			} else {
				$src_state = dol_buildpath('/agefodd/img/no.png', 1);
				$txtalt = $langs->trans("AgfTerminatedPoint");
			}
			print '<td align="center"><img title="'.$line->support.'" alt="' . $txtalt . '" src="' . $src_state . '"/></td>';

			// FEE Edit
			if ($line->support) {
				$src_state = dol_buildpath('/agefodd/img/ok.png', 1);
				$txtalt = $langs->trans("AgfTerminatedNoPoint");
			} else {
				$src_state = dol_buildpath('/agefodd/img/no.png', 1);
				$txtalt = $langs->trans("AgfTerminatedPoint");
			}
			print '<td align="center"><img title="'.$line->support.'" alt="' . $txtalt . '" src="' . $src_state . '"/></td>';

			// Fact Clients
			print '<td nowrap="nowrap"	>';

			print price($agf_fin->invoice_ongoing_amount + $agf_fin->invoice_payed_amount) . $langs->getCurrencySymbol($conf->currency);
			print '</td>';

			$totalfactprice += $agf_fin->invoice_ongoing_amount + $agf_fin->invoice_payed_amount;

			// Att RN
			if ($line->attrn) {
				$src_state = dol_buildpath('/agefodd/img/ok.png', 1);
				$txtalt = $langs->trans("AgfTerminatedNoPoint");
			} else {
				$src_state = dol_buildpath('/agefodd/img/no.png', 1);
				$txtalt = $langs->trans("AgfTerminatedPoint");
			}
			print '<td align="center"><img title="'.$line->attrn.'" alt="' . $txtalt . '" src="' . $src_state . '"/></td>';

			// FEE Env.
			if ($line->ffeenv) {
				$src_state = dol_buildpath('/agefodd/img/ok.png', 1);
				$txtalt = $langs->trans("AgfTerminatedNoPoint");
			} else {
				$src_state = dol_buildpath('/agefodd/img/no.png', 1);
				$txtalt = $langs->trans("AgfTerminatedPoint");
			}
			print '<td align="center"><img title="'.$line->ffeenv.'" alt="' . $txtalt . '" src="' . $src_state . '"/></td>';

			// Fact Formateur
			if ($line->invtrainer) {
				$src_state = dol_buildpath('/agefodd/img/ok.png', 1);
				$txtalt = $langs->trans("AgfTerminatedNoPoint");
			} else {
				$src_state = dol_buildpath('/agefodd/img/no.png', 1);
				$txtalt = $langs->trans("AgfTerminatedPoint");
			}
			print '<td align="center"><img title="'.$line->invtrainer.'" alt="' . $txtalt . '" src="' . $src_state . '"/></td>';

			// Fact lieu
			if ($line->invroom) {
				$src_state = dol_buildpath('/agefodd/img/ok.png', 1);
				$txtalt = $langs->trans("AgfTerminatedNoPoint");
			} else {
				$src_state = dol_buildpath('/agefodd/img/no.png', 1);
				$txtalt = $langs->trans("AgfTerminatedPoint");
			}
			print '<td align="center"><img title="'.$line->invroom.'" alt="' . $txtalt . '" src="' . $src_state . '"/></td>';

			print '<td title="' . stripslashes($line->notes) . '">' . stripslashes(dol_trunc($line->notes, 30)) . '</td>';

			$totalsellprice += $agf_fin->propal_sign_amount;

			print '<td></td>'; // Action
			print "</tr>\n";
		} else {
			print "<tr $bc[$var]>";

			print '<td colspan="5"></td>';
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
			print '<td colspan="15"></td>';
			print '<td></td>'; // Action
			print "</tr>\n";
		}

		$oldid = $line->id;

		$i ++;
	}

	print "</table>";
	print '</div>';
	print '</form>';

	print '<script type="text/javascript" language="javascript">' . "\n";
	print '$(document).ready(function() {
						$("#totalamount").append("' . price($totalsellprice) . $langs->getCurrencySymbol($conf->currency) . '");
						$("#totalamountfact").append("' . price($totalfactprice) . $langs->getCurrencySymbol($conf->currency) . '");
				});';
	print "\n" . '</script>' . "\n";
} else {
	setEventMessage($agf->error, 'errors');
}

llxFooter();
$db->close();
