<?php
/*
 * Copyright (C) 2012-2016 Florian Henry <florian.henry@open-concept.pro>
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
 * \file agefodd/trainee/session.php
 * \ingroup agefodd
 * \brief session of trainee
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agefodd_formateur.class.php');
require_once ('../lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php');
require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_session_formateur.class.php');
require_once ('../class/agefodd_session_formateur_calendrier.class.php');
require_once ('../class/agefodd_session_calendrier.class.php');
require_once ('../class/html.formagefodd.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once ('../class/agefodd_session_element.class.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$id = GETPOST('id', 'int');
$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = (int) GETPOST('page', 'int');
$optioncss = GETPOST('optioncss', 'alpha');
$search_id = GETPOST('search_id', 'int');
$search_ref = GETPOST('search_ref', 'alpha');
$search_intitule = GETPOST('search_intitule', 'alpha');
$search_month = GETPOST('search_month', 'int');
$search_year = GETPOST('search_year', 'int');
$search_status_in_session = GETPOST('search_status_in_session', 'alpha');
if ($search_status_in_session == - 1)
	$search_status_in_session = '';
$search_archive = GETPOST('search_archive', 'int');
$search_company = GETPOST('search_company', 'alpha');
$search_sale = GETPOST('search_sale', 'none');
$search_type_session = GETPOST("search_type_session", 'int');

if (empty($sortorder))
	$sortorder = "DESC";
if (empty($sortfield))
	$sortfield = "s.dated";

if ($page == - 1) {
	$page = 0;
}

$limit = GETPOST('limit', 'none');
if (empty($limit)) $limit = $conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$option = '&id=' . $id;
if ($limit > 0 && $limit != $conf->liste_limit) $option.='&limit='.urlencode($limit);

$formAgefodd = new FormAgefodd($db);
$form = new Form($db);
$formother = new FormOther($db);

$hookmanager->initHooks(array('sessiontrainerlist'));

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x", 'none')) {
	$search_id = '';
	$search_ref = '';
	$search_intitule = '';
	$search_month = "";
	$search_year = '';
	$search_status_in_session = '';
	$search_archive = '';
	$search_company = '';
	$search_sale = '';
	$search_type_session = '';
}

$filter = array ();
if (! empty($search_id)) {
	$filter ['s.rowid'] = $search_id;
	$option .= '&search_id=' . $search_id;
}
if (! empty($search_ref)) {
	$filter ['s.ref'] = $search_ref;
	$option .= '&search_ref=' . $search_ref;
}
if (! empty($search_intitule)) {
	$filter ['c.intitule'] = $search_intitule;
	$option .= '&search_intitule=' . $search_intitule;
}
if (! empty($search_month)) {
	$filter ['MONTH(s.dated)'] = $search_month;
	$option .= '&search_month=' . $search_month;
}
if (! empty($search_year)) {
	$filter ['YEAR(s.dated)'] = $search_year;
	$option .= '&search_year=' . $search_year;
}
if ($search_status_in_session != '') {
	$filter ['sf.trainer_status'] = $search_status_in_session;
	$option .= '&search_status_in_session=' . $search_status_in_session;
}
if (! empty($search_archive)) {
	$filter ['!s.status'] = 4;
	// $option .= '&search_archive=' . $search_archive;
}
if (! empty($search_company)) {
	$filter ['so.nom'] = $search_company;
	$option .= '&search_company=' . $search_company;
}
if (! empty($search_sale)) {
	$filter ['sale.fk_user_com'] = $search_sale;
	$option .= '&search_sale=' . $search_sale;
}
if ($search_type_session != '' && $search_type_session != - 1) {
	$filter ['s.type_session'] = $search_type_session;
	$option .= '&search_type_session=' . $search_sale;
}

/*
 * View
*/

llxHeader('', $langs->trans("AgfTeacher"));

if ($id) {
	$agf = new Agefodd_teacher($db);
	$result = $agf->fetch($id);

	if ($result > 0) {
		$trainer = new Agefodd_session_formateur($db);

		$agf_session = new Agsession($db);
		// Count total nb of records
		$nbtotalofrecords = 0;
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
			$nbtotalofrecords = $agf_session->fetch_session_per_trainer($id, $sortorder, $sortfield, 0, 0, $filter);
		}
		$result = $agf_session->fetch_session_per_trainer($id, $sortorder, $sortfield, $limit, $offset, $filter);
		if ($result < 0) {
			setEventMessage($agf_session->error, 'errors');
		}

		$form = new Form($db);

		$head = trainer_prepare_head($agf);

		dol_fiche_head($head, 'sessionlist', $langs->trans("AgfTeacher"), 0, 'user');
		dol_fiche_end();
		dol_agefodd_banner_tab($agf, 'id');

		dol_fiche_end();

		print '<form method="post" action="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&optioncss=' . GETPOST('optioncss', 'none') . '" name="searchFormList" id="searchFormList">' . "\n";
		print '<input type="hidden" name="optioncss" value="' . $optioncss . '">' . "\n";

		print_barre_liste($langs->trans("AgfSessionDetail"), $page, $_SERVER ['PHP_SELF'], $option, $sortfield, $sortorder, "", $result, $nbtotalofrecords, 'title_generic.png', 0, '', '', $limit);
		if (empty($search_archive)) {
			print '<a href="' . $_SERVER ['PHP_SELF'] . '?' . $option . '&search_archive=1">' . $langs->trans("AgfCacherSessionArchives") . '</a>' . "\n";
		} else {
			print '<a href="' . $_SERVER ['PHP_SELF'] . '?' . $option . '">' . $langs->trans("AgfAfficherSessionArchives") . '</a>' . "\n";
		}

		$moreforfilter .= '<div class="divsearchfield">'.$langs->trans('SalesRepresentatives') . ': ';
		$moreforfilter .= $formother->select_salesrepresentatives($search_sale, 'search_sale', $user).'</div>';

		$moreforfilter .= '<div class="divsearchfield">'.$langs->trans('Period') . '(' . $langs->trans("AgfDateDebut") . ')' . ': ';
		$moreforfilter .= $langs->trans('Month') . ':<input class="flat" type="text" size="4" name="search_month" value="' . $search_month . '">';
		$moreforfilter .= $langs->trans('Year') . ':' . $formother->selectyear($search_year ? $search_year : - 1, 'search_year', 1, 20, 5).'</div>';

		if ($moreforfilter) {
			print '<div class="liste_titre liste_titre_bydiv">';
			print $moreforfilter;
			print '</div>';
		}

		print '<table class="noborder tagtable liste listwithfilterbefore"  width="100%">';

		// Filter
		print '<tr class="liste_titre_filter">';
		// Id session
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_id" value="' . $search_id . '" size="4">';
		print '</td>';
		// Ref
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_ref" value="' . $search_ref . '" size="20">';
		print '</td>';
		// intitule
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_intitule" value="' . $search_intitule . '" size="20">';
		print '</td>';
		// Customer
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_company" value="' . $search_company . '" size="20">';
		print '</td>';
		// Type session
		print '<td class="liste_titre">';
		print $formAgefodd->select_type_session('search_type_session', $search_type_session, 1);
		print '</td>';
		// Period date de debut
		print '<td class="liste_titre">';
		print '</td>';
		// Date de fin
		print '<td class="liste_titre">';
		print '</td>';
		// durrée
		print '<td class="liste_titre">';
		print '</td>';
		// durrée jours
		print '<td class="liste_titre">';
		print '</td>';
		if (!empty($user->rights->agefodd->session->margin)) {
			// Montant trainer
			print '<td class="liste_titre">';
			print '</td>';
			// Montant HT & HF
			print '<td class="liste_titre">';
			print '</td>';
		}
		// Status
		print '<td class="liste_titre">';
		print $formAgefodd->select_trainer_session_status('search_status_in_session', $search_status_in_session, array (), 1);
		print '</td>';
		print '<td class="liste_titre" width="2%">';
		print '<div class="nowrap">';
		print '<input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
		print '&nbsp; ';
		print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
		print '</div>';
		print '</td>';
		print "</tr>\n";


		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("AgfMenuSess"), $_SERVER ['PHP_SELF'], "s.rowid", '', $option, 'width="10%"', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Ref"), $_SERVER ['PHP_SELF'], "s.ref", '', $option, 'width="10%"', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("AgfIntitule"), $_SERVER ['PHP_SELF'], "c.intitule", '', $option, '', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Customer"), $_SERVER ['PHP_SELF'], "so.nom", '', $option, '', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Type"), $_SERVER ['PHP_SELF'], "s.type_session", "", $option, '', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("AgfDebutSession"), $_SERVER ['PHP_SELF'], "s.dated", '', $option, '', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("AgfFinSession"), $_SERVER ['PHP_SELF'], "s.datef", '', $option, '', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("AgfPDFFichePeda1"), $_SERVER ['PHP_SELF'], "", '', $option, '', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("AgfDays"), $_SERVER ['PHP_SELF'], "", '', $option, '', $sortfield, $sortorder);
		if (!empty($user->rights->agefodd->session->margin)) {
			print_liste_field_titre($langs->trans("AgfEuroTrainerHF"), $_SERVER ['PHP_SELF'], "", '', $option, '', $sortfield, $sortorder);
			print_liste_field_titre($langs->trans("AgfEuroSessionHTHF"), $_SERVER ['PHP_SELF'], "", '', $option, '', $sortfield, $sortorder);
		}
		print_liste_field_titre($langs->trans("Status"), $_SERVER ['PHP_SELF'], "sf.trainer_status", '', $option, '', $sortfield, $sortorder);
		print '<th></th>';
		print '</tr>';

		$style = 'pair';
		if (count($agf_session->lines) > 0) {
			$dureetotal = 0;
			foreach ( $agf_session->lines as $line ) {
				$duree = 0;

				if ($style != 'class="impair"') {
					$style = 'class="impair"';
				} else {
					$style = 'class="pair"';
				}

				if (!empty($conf->global->AGEFODD_USE_BG_GRAY) && $line->status == 4) {
					$style = ' style="background: gray"';
				}

				print '<tr ' . $style . '>';

				print '<td><a href="' . dol_buildpath('/agefodd/session/card.php', 1) . '?id=' . $line->rowid . '">' . $line->rowid . '</a></td>';
				print '<td><a href="' . dol_buildpath('/agefodd/session/card.php', 1) . '?id=' . $line->rowid . '">' . $line->sessionref . '</a></td>';
				print '<td>'.$line->intitule . '</td>';
				print '<td>';
				if (! empty($line->socid) && $line->socid != - 1) {
					$soc = new Societe($db);
					$soc->fetch($line->socid);
					print $soc->getNomURL(1);
				} else {
					print '&nbsp;';
				}
				print '</td>';
				print '<td>' . ($line->type_session ? $langs->trans('AgfFormTypeSessionInter') : $langs->trans('AgfFormTypeSessionIntra')) . '</td>';
				print '<td>' . dol_print_date($line->dated, 'daytext') . '</td>';
				print '<td>' . dol_print_date($line->datef, 'daytext') . '</td>';

				if (empty($conf->global->AGF_DOL_TRAINER_AGENDA)) {
					// Calculate time of session according calendar
					$calendrier = new Agefodd_sesscalendar($db);
					$calendrier->fetch_all($line->rowid);
					if (is_array($calendrier->lines) && count($calendrier->lines) > 0) {
						foreach ( $calendrier->lines as $linecal ) {
							$duree += ($linecal->heuref - $linecal->heured);
						}
					}
					$dureetotal += $duree;
					$min = floor($duree / 60);
					$rmin = sprintf("%02d", $min % 60);
					$hour = floor($min / 60);
				} else {
					// Calculate time of session according session trainer calendar
					$calendrier = new Agefoddsessionformateurcalendrier($db);
					$calendrier->fetch_all($line->trainersessionid);
					if (is_array($calendrier->lines) && count($calendrier->lines) > 0) {
						foreach ( $calendrier->lines as $linecal ) {
							$duree += ($linecal->heuref - $linecal->heured);
						}
					}
					$dureetotal += $duree;
					$min = floor($duree / 60);
					$rmin = sprintf("%02d", $min % 60);
					$hour = floor($min / 60);
				}

				// print '<td>'.dol_print_date($line->realdurationsession,'hour').'</td>';
				print '<td>' . $hour . ':' . $rmin . '</td>';

				// durrée Jours
				$duration_days = round($duree / 7 / 3600,1);
				/*if ($duration_days==0 && $duree!=0) {
					$duration_days=0.5;
				}*/
				$duree_jour_total += $duration_days;
				print '<td>' . $duration_days . '</td>';

				if (!empty($user->rights->agefodd->session->margin)) {
					// Montant trainer HF
					$agf_fin = new Agefodd_session_element($db);
					if (!empty($contact->socid)) {

						//TODO manage multi contact
						if ($conf->companycontacts->enabled) {

							$agf_fin->fk_soc_array=array();

							$sql_innercontact = "SELECT c.fk_soc_source ";
							$sql_innercontact.= " FROM ".MAIN_DB_PREFIX."company_contacts as c";
							$sql_innercontact.= " WHERE c.fk_contact=".$contact->id;

							$resql_innercontact = $db->query($sql_innercontact);
							if ($resql_innercontact)
							{
								while ($obj_innercontact = $db->fetch_object($resql_innercontact)) {
									$agf_fin->fk_soc_array[$obj_innercontact->fk_soc_source]=$obj_innercontact->fk_soc_source;
								}

							} else {
								setEventMessage($db->lasterror,'errors');
							}

							$agf_fin->fk_soc_array[$contact->socid]=$contact->socid;
						}

						$agf_fin->fk_soc=$contact->socid;
					}
					$agf_fin->fetch_by_session($line->rowid,$line->trainersessionid);
					$sellprice = $agf_fin->trainer_cost_amount;

					// Remove charges of product of category 'frais'
					$result = $agf_fin->get_charges_amount($line->rowid, '66,67', 'invoice_supplier_trainer',$line->trainersessionid);
					if (! empty($result) && $result != - 1) {
						$sellprice -= $result;
					}
					$totalsellprice += $sellprice;

					print '<td>' . price($sellprice) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>';

					$agf_fin->fetch_by_session($line->rowid);
					// Montant facturé ht & hors frais
					$amount_act_invoiced = 0;
					$amount_act_invoiced = $agf_fin->invoice_ongoing_amount + $agf_fin->invoice_payed_amount;

					// Remove charges of product of category 'frais'
					$result = $agf_fin->get_charges_amount($line->rowid, $conf->global->AGF_CAT_PRODUCT_CHARGES, 'invoice');
					if (! empty($result) && $result != - 1 && $result <= $amount_act_invoiced) {
						$amount_act_invoiced_less_charges = $amount_act_invoiced - $result;
					} else {
						$amount_act_invoiced_less_charges = $amount_act_invoiced;
					}
					$totalsellprice_invoice += $amount_act_invoiced_less_charges;

					print '<td>' . price($amount_act_invoiced_less_charges) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>';
				}

				print '<td>' . $trainer->LibStatut($line->trainer_status, 4) . '</td>';
				print '<td></td>';
				print '</tr>';
			}
		}

		print '<tr class="liste_total">';
		print '<td>' . $langs->trans('Total') . '</td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';

		$min = floor($dureetotal / 60);
		$rmin = sprintf("%02d", $min % 60);
		$hour = floor($min / 60);
		print '<td>' . $hour . ':' . $rmin . '</td>';
		print '<td>' . $duree_jour_total . '</td>';
		if (!empty($user->rights->agefodd->session->margin)) {
			print '<td>' . price($totalsellprice) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>';
			print '<td>' . price($totalsellprice_invoice) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>';
		}
		print '<td></td>';
		print '<td></td>';
		print '</tr>';
		print '</table>';
		print '</form>';
	} else {
		$langs->trans('AgfNoSession');
	}
} else {
	setEventMessage('Select Trainer', 'errors');
}

llxFooter();
$db->close();
