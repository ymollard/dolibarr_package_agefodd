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
require_once ('../class/agefodd_place.class.php');
require_once ('../class/agefodd_session_stagiaire.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../lib/agefodd.lib.php');
require_once ('../class/html.formagefodd.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');
require_once ('../class/agefodd_formateur.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once ('../class/agefodd_session_element.class.php');
require_once (DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php');


// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$socid = GETPOST('socid', 'int');

// Search criteria
$search_trainning_name = GETPOST("search_trainning_name", 'none');
$search_teacher_id = GETPOST("search_teacher_id", 'none');
$search_training_ref = GETPOST("search_training_ref", 'alpha');
$search_start_date = dol_mktime(0, 0, 0, GETPOST('search_start_datemonth', 'int'), GETPOST('search_start_dateday', 'int'), GETPOST('search_start_dateyear', 'int'));
$search_end_date = dol_mktime(0, 0, 0, GETPOST('search_end_datemonth', 'int'), GETPOST('search_end_dateday', 'int'), GETPOST('search_end_dateyear', 'int'));
$search_site = GETPOST("search_site", 'none');
$search_sale = GETPOST("search_sale", 'int');
$search_training_ref_interne = GETPOST('search_training_ref_interne', 'alpha');
$search_type_session = GETPOST("search_type_session", 'int');
$training_view = GETPOST("training_view", 'int');
$site_view = GETPOST('site_view', 'int');
$status_view = GETPOST('status', 'array');
$search_type_affect = GETPOST('search_type_affect', 'alpha');
if(empty($search_type_affect) && !empty($conf->global->RELATION_LINK_SELECTED_ON_THIRDPARTY_TRAINING_SESSION)){
	$search_type_affect = $conf->global->RELATION_LINK_SELECTED_ON_THIRDPARTY_TRAINING_SESSION;
}

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x", 'none')) {
	$search_trainning_name = '';
	$search_teacher_id = "";
	$search_training_ref = '';
	$search_start_date = "";
	$search_end_date = "";
	$search_site = "";
	$search_training_ref_interne = "";
	$search_type_session = "";
	$status_view = "";
	$search_type_affect = "";
	$search_sale = "";
}

if (empty($search_type_affect)) {
	$search_type_affect = 'thirdparty';
}

$hookmanager->initHooks(array('sessionsoclist'));

$filter = array ();
$option='';
if (! empty($search_trainning_name)) {
	$filter ['c.intitule'] = $search_trainning_name;
	$option.='&search_trainning_name='.$search_trainning_name;
}
if (! empty($search_teacher_id)  && $search_teacher_id != - 1) {
	$filter ['f.rowid'] = $search_teacher_id;
	$option.='&search_teacher_id='.$search_teacher_id;
}
if (! empty($search_training_ref)) {
	$filter ['c.ref'] = $search_training_ref;
	$option.='&search_training_ref='.$search_training_ref;
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
	$option.='&search_site='.$search_site;
}
if (! empty($search_training_ref_interne)) {
	$filter ['c.ref_interne'] = $search_training_ref_interne;
	$option.='&search_training_ref_interne='.$search_training_ref_interne;
}
if ($search_type_session != '' && $search_type_session != - 1) {
	$filter ['s.type_session'] = $search_type_session;
	$option.='&search_type_session='.$search_type_session;
}
if (! empty($status_view)) {
	$filter ['s.status'] = $status_view;
	$option.='&search_type_session='.$status_view;
}
if (! empty($search_type_affect)) {
	$filter ['type_affect'] = $search_type_affect;
	$option.='&search_type_affect='.$search_type_affect;
}
if (! empty($search_sale) && $search_sale > 0)
{
	$filter ['sale.fk_user_com'] = $search_sale;
	$option.='&search_sale='.$search_sale;
}
if (! empty($socid)) {
	$option.='&socid='.$socid;
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

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$agf = new Agsession($db);

$title = $langs->trans("AgfMenuSess");
llxHeader('', $title);

$object = new Societe($db);
$object->fetch($socid);
if ($object->id <= 0) {
	setEventMessage($object->error, 'errors');
}

$head = societe_prepare_head($object);

dol_fiche_head($head, 'tabAgefodd', $langs->trans("ThirdParty"), 0, 'company');

print '<table class="border" width="100%">';

print '<tr><td width="25%">' . $langs->trans("ThirdPartyName") . '</td><td colspan="3">';
print $form->showrefnav($object, 'socid', '', ($user->societe_id ? 0 : 1), 'rowid', 'nom');
print '</td></tr>';

if (! empty($conf->global->SOCIETE_USEPREFIX)) // Old not used prefix field
{
	print '<tr><td>' . $langs->trans('Prefix') . '</td><td colspan="3">' . $object->prefix_comm . '</td></tr>';
}

if ($object->thirdparty) {
	print '<tr><td>';
	print $langs->trans('CustomerCode') . '</td><td colspan="3">';
	print $object->code_client;
	if ($object->check_codeclient() != 0)
		print ' <font class="error">(' . $langs->trans("WrongCustomerCode") . ')</font>';
	print '</td></tr>';
}

if ($object->fournisseur) {
	print '<tr><td>';
	print $langs->trans('SupplierCode') . '</td><td colspan="3">';
	print $object->code_fournisseur;
	if ($object->check_codefournisseur() != 0)
		print ' <font class="error">(' . $langs->trans("WrongSupplierCode") . ')</font>';
	print '</td></tr>';
}

if (! empty($conf->barcode->enabled)) {
	print '<tr><td>' . $langs->trans('Gencod') . '</td><td colspan="3">' . $soc->barcode . '</td></tr>';
}

print "<tr><td valign=\"top\">" . $langs->trans('Address') . "</td><td colspan=\"3\">";
dol_print_address($object->address, 'gmap', 'thirdparty', $soc->id);
print "</td></tr>";

// Zip / Town
print '<tr><td width="25%">' . $langs->trans('Zip') . '</td><td width="25%">' . $object->zip . "</td>";
print '<td width="25%">' . $langs->trans('Town') . '</td><td width="25%">' . $object->town . "</td></tr>";

// Country
if ($object->country) {
	print '<tr><td>' . $langs->trans('Country') . '</td><td colspan="3">';
	$img = picto_from_langcode($object->country_code);
	print($img ? $img . ' ' : '');
	print $object->country;
	print '</td></tr>';
}

// EMail
print '<tr><td>' . $langs->trans('EMail') . '</td><td colspan="3">';
print dol_print_email($object->email, 0, $object->id, 'AC_EMAIL');
print '</td></tr>';

// Web
print '<tr><td>' . $langs->trans('Web') . '</td><td colspan="3">';
print dol_print_url($object->url);
print '</td></tr>';

// Phone / Fax
print '<tr><td>' . $langs->trans('Phone') . '</td><td>' . dol_print_phone($object->tel, $object->country_code, 0, $object->id, 'AC_TEL') . '</td>';
print '<td>' . $langs->trans('Fax') . '</td><td>' . dol_print_phone($object->fax, $object->country_code, 0, $object->id, 'AC_FAX') . '</td></tr>';

print '</table>';

print '</div>';

print '<div class="tabsAction">';

if ($user->rights->agefodd->creer) {
	print '<a class="butAction" href="' . dol_buildpath('/agefodd/session/card.php', 1) . '?mainmenu=agefodd&action=create&fk_soc=' . $object->id . '">' . $langs->trans('AgfMenuSessNew') . '</a>';
} else {
	print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfMenuSessNew') . '</a>';
}

print '</div>';

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $agf->fetch_all_by_soc($object->id, $sortorder, $sortfield, 0, 0, $filter);
}
$result = $agf->fetch_all_by_soc($object->id, $sortorder, $sortfield, $limit, $offset, $filter);

if ($result >= 0) {
	$num = $result;

	print '<form method="get" action="' . $_SERVER ['PHP_SELF'] . '" name="search_form">' . "\n";
	print '<input type="hidden" name="socid" value="' . $socid . '" >';
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

	print_barre_liste($title, $page, $_SERVER ['PHP_SELF'], $option, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_generic.png', 0, '', '', $limit);

	$i = 0;
	print '<table class="tagtable liste listwithfilterbefore" width="100%">';
	print '<tr class="liste_titre">';
	print '<td></td>';
	print_liste_field_titre($langs->trans("AgfTypeRessource"), $_SERVER ['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Id"), $_SERVER ['PHP_SELF'], "s.rowid", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Ref"), $_SERVER ['PHP_SELF'], "s.ref", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Company"), $_SERVER ['PHP_SELF'], "so.nom", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfFormateur"), $_SERVER ['PHP_SELF'], "socpf.lastname", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfSessionCommercial"), $_SERVER ['PHP_SELF'], "ucom.lastname", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfIntitule"), $_SERVER ['PHP_SELF'], "c.intitule", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Ref"), $_SERVER ['PHP_SELF'], "c.ref", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfRefInterne"), $_SERVER ['PHP_SELF'], "c.ref_interne", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfFormTypeSession"), $_SERVER ['PHP_SELF'], "s.type_session", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfDateDebut"), $_SERVER ['PHP_SELF'], "s.dated", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfDateFin"), $_SERVER ['PHP_SELF'], "s.datef", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfLieu"), $_SERVER ['PHP_SELF'], "p.ref_interne", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Status"), $_SERVER ['PHP_SELF'], 's.status', '', $option, '', $sortfield, $sortorder);
	if(! empty($conf->global->AGF_ADD_CUSTOM_COLUMNS_ON_FILTER) && $search_type_affect == 'trainee') {
		print_liste_field_titre($langs->trans("AgfParticipantsWithTotal"), $_SERVER ['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("AgfSessionCostPerTrainee"), $_SERVER ['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("AgfSessionCostForThirdparty"), $_SERVER ['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
	}
	print '<td></td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';

	print '<td class="liste_titre" align="right">';
	if(method_exists($form, 'showFilterButtons')) {
		$searchpicto=$form->showFilterButtons();

		print $searchpicto;
	} else {
		print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
		print '&nbsp; ';
		print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	}
	print '</td>';

	print '<td class="liste_titre">';
	print $formAgefodd->select_type_affect($search_type_affect, 'search_type_affect');
	print '</td>';

	print '<td class="liste_titre">';
	print '</td>';

	//Id
	print '<td class="liste_titre">';
	print '</td>';

	//Ref
	print '<td class="liste_titre">';
	print '</td>';

	print '<td class="liste_titre">';
	print $formAgefodd->select_formateur($search_teacher_id, 'search_teacher_id', '', 1);
	print '</td>';

	print '<td class="liste_titre">';
	print $form->select_dolusers($search_sale, 'search_sale', 1);
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
	print $formAgefodd->select_site_forma($search_site, 'search_site', 1);
	print '</td>';

	print '<td class="liste_titre">';
	print $formAgefodd->select_session_status($status_view, 'status', '', 1, 0, array(), '', true);
	print '</td>';

	if(! empty($conf->global->AGF_ADD_CUSTOM_COLUMNS_ON_FILTER) && $search_type_affect == 'trainee') {
		print '<td></td><td></td><td></td>';
	}

	print '<td class="liste_titre" align="right">';
	if(method_exists($form, 'showFilterButtons')) {
		$searchpicto=$form->showFilterButtons();

		print $searchpicto;
	} else {
		print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
		print '&nbsp; ';
		print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	}
	print '</td>';

	print "</tr>\n";
	print '</form>';

	$var = true;
	$total = 0;
	$totalforthirdparty = 0;
	foreach ( $agf->lines as $line ) {
		if($i >= $limit) break;

		$agf->fetch($line->rowid);
		$coutTotalLigne = $agf->cost_trainer + $agf->cost_site + $agf->cost_trip;

		if(! empty($conf->global->AGF_ADD_CUSTOM_COLUMNS_ON_FILTER) && $search_type_affect == 'trainee')
		{
			// on recalcule les couts
			$agf_fin = new Agefodd_session_element($db);
			$agf_fin->fetch_by_session_by_thirdparty($line->rowid, '', array('\'invoice_supplier_trainer\'', '\'invoice_supplierline_trainer\''));
			if (!empty($agf_fin->lines))
			{
				$coutTotalLigne = 0;
				foreach ( $agf_fin->lines as $line_fin ) {
					switch ($line_fin->element_type)
					{
						case 'invoice_supplier_trainer':
						case 'invoice_supplier_room':
						case 'invoice_supplier_missions':
							$agf->fetch_all_by_order_invoice_propal('', '','','','','','',$line_fin->fk_element,'');
							$suplier_invoice = new FactureFournisseur($db);
							$suplier_invoice->fetch($line_fin->fk_element);
							$count = count($agf->lines);

							if ($count > 1){
								$coutTotalLigne += $suplier_invoice->total_ht/$count;
							}
							else
							{
								$coutTotalLigne += $suplier_invoice->total_ht;
							}
							break;

						case 'invoice_supplierline_trainer':
						case 'invoice_supplierline_room':
						case 'invoice_supplierline_missions':
							$supplier_invoiceline = new SupplierInvoiceLine($db);
							$supplier_invoiceline->fetch($line_fin->fk_element);
							$coutTotalLigne += $supplier_invoiceline->total_ht;

					}
				}
			}
		}

		// Retrouve tous les stagiaires d'une même société présents à une session de formation
		$agfS = new Agefodd_session_stagiaire($db);
		$agfS->fetch_stagiaire_per_session($line->rowid, $socid);
		$nbSocParticipant = count($agfS->lines);

		if ($line->rowid != $oldid) {

			// Affichage tableau des sessions
			$var = ! $var;

			($line->status == 4) ? $style_archive = ' style="background: gray"' : $style_archive = '';

			if (empty($style_archive)) {
				$style = $bc [$var];
			} else {
				$style = $style_archive;
			}

			print "<tr " . $style . ">";
			print '<td></td>';
			print '<td>' . stripslashes($line->type_affect) . '</td>';
			// Calcul de la couleur du lien en fonction de la couleur définie sur la session
			// http://www.w3.org/TR/AERT#color-contrast
			// SI ((Red value X 299) + (Green value X 587) + (Blue value X 114)) / 1000 < 125 ALORS
			// AFFICHER DU BLANC (#FFF)
			$couleur_rgb = agf_hex2rgb($line->color);
			$color_a = '';
			if ($line->color && ((($couleur_rgb [0] * 299) + ($couleur_rgb [1] * 587) + ($couleur_rgb [2] * 114)) / 1000) < 125)
				$color_a = ' style="color: #FFFFFF;"';

			if ($conf->global->AGF_NEW_BROWSER_WINDOWS_ON_LINK) {
				$target = ' target="_blanck" ';
			} else {
				$target = '';
			}

			print '<td  style="background: #' . $line->color . '"><a' . $color_a . ' href="card.php?id=' . $line->rowid . '"' . $target . '>' . img_object($langs->trans("AgfShowDetails"), "service") . ' ' . $line->rowid . '</a></td>';
			print '<td  style="background: #' . $line->color . '"><a' . $color_a . ' href="card.php?id=' . $line->rowid . '"' . $target . '>' . img_object($langs->trans("AgfShowDetails"), "service") . ' ' . $line->sessionref . '</a></td>';

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
				print ucfirst(strtolower($trainer->civilite)) . ' ' . strtoupper($trainer->name) . ' ' . ucfirst(strtolower($trainer->firstname));
			} else {
				print '&nbsp;';
			}
			print '</td>';

			print '<td>';
			$commercial = new User($db);
			if (! empty($line->fk_user_com)){
				$commercial->fetch($line->fk_user_com);
			}
			if (! empty($commercial->id))
			{
				print $commercial->getNomUrl();
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';

			print '<td>' . stripslashes(dol_trunc($line->intitule, 60)) . '</td>';
			print '<td>' . $line->ref . '</td>';
			print '<td>' . $line->training_ref_interne . '</td>';
			print '<td>' . ($line->type_session ? $langs->trans('AgfFormTypeSessionInter') : $langs->trans('AgfFormTypeSessionIntra')) . '</td>';
			print '<td>' . dol_print_date($line->dated, 'daytext') . '</td>';
			print '<td>' . dol_print_date($line->datef, 'daytext') . '</td>';
			print '<td>' . stripslashes($line->ref_interne) . '</td>';
			print '<td>' . stripslashes($line->statuslib) . '</td>';
			if(! empty($conf->global->AGF_ADD_CUSTOM_COLUMNS_ON_FILTER) && $search_type_affect == 'trainee') {
				$pertrainee = $coutTotalLigne / $line->nb_stagiaire;
			//	$coutTotalLigne *= $nbSocParticipant;
				$total += $coutTotalLigne;
				$costBySoc = ($coutTotalLigne / $line->nb_stagiaire) * $nbSocParticipant;
				$totalforthirdparty += $costBySoc;

				print '<td>' . $nbSocParticipant . ' / ' . $line->nb_stagiaire . '</td>';
				print '<td>' . price(round($pertrainee,2)) . ' ' . $langs->trans('Currency' . $conf->currency) . '</td>';

				print '<td>' . price(round($costBySoc,2) ) . ' ' . $langs->trans('Currency' . $conf->currency) . '</td>';
			}
			print '<td></td>';
			print "</tr>\n";
		} else {
			print "<tr " . $style . ">";
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
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
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			if(! empty($conf->global->AGF_ADD_CUSTOM_COLUMNS_ON_FILTER) && $search_type_affect == 'trainee') {
				print '<td></td>';
				print '<td></td>';
			}
			print "</tr>\n";
		}

		$oldid = $line->rowid;

		$i ++;
	}

	if(! empty($conf->global->AGF_ADD_CUSTOM_COLUMNS_ON_FILTER) && $search_type_affect == 'trainee') {
		print '<tr class="liste_total">';

		print '<td align="right" colspan="14"><strong>Total :</strong></td>';
		//print '<td><strong>' . price(round($total,2)) . ' ' . $langs->trans('Currency' . $conf->currency) . '</strong></td>';
		print '<td></td>';
		print '<td><strong>' . price(round($totalforthirdparty,2)) . ' ' . $langs->trans('Currency' . $conf->currency) . '</strong></td>';


		print '</tr>';
	}
	print "</table>";
} elseif ($result == 0) {
	print $langs->trans('AgfNoSession');
} else {
	setEventMessage($agf->error, 'errors');
}

print '<div class="tabsAction">';

if ($user->rights->agefodd->creer) {
	print '<a class="butAction" href="' . dol_buildpath('/agefodd/session/card.php', 1) . '?mainmenu=agefodd&action=create&fk_soc=' . $object->id . '">' . $langs->trans('AgfMenuSessNew') . '</a>';
} else {
	print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfMenuSessNew') . '</a>';
}

print '</div>';

llxFooter();
$db->close();
