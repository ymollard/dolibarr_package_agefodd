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
require_once (DOL_DOCUMENT_ROOT . '/product/class/product.class.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_session_element.class.php');
dol_include_once('/agefodd/class/agefodd_place.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/agefodd/class/html.formagefodd.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');
dol_include_once('/agefodd/class/agefodd_formateur.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$hookmanager->initHooks(array(
		'agefoddsessionlist'
));

$parameters = array(
		'from' => 'original'
);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;

$massaction = GETPOST('massaction', 'alpha');
$toselect = GETPOST('toselect', 'array');

// Massactions
if (! empty($massaction) && strpos('set_statut', $massaction) == 0 && ! empty($toselect)) {
	$newStatut = substr($massaction, 10);
	$error = 0;

	$sess = new Agsession($db);
	foreach ( $toselect as $idsess ) {
		$sess->fetch($idsess);

		$sess->status = $newStatut;
		$result = $sess->update($user);

		if ($result < 0) {
			$error ++;
			setEventMessage($sess->error, 'errors');
		}
	}

	if (! $error)
		setEventMessage($langs->trans('AgfChangeStatutSuccess'), 'mesgs');

	$toselect = array();
}

// Search criteria
$search_trainning_name = GETPOST("search_trainning_name", 'none');
$search_soc = GETPOST("search_soc", 'none');
$search_teacher_id = GETPOST("search_teacher_id", 'none');
$search_training_ref = GETPOST("search_training_ref", 'alpha');
$search_start_date = dol_mktime(0, 0, 0, GETPOST('search_start_datemonth', 'int'), GETPOST('search_start_dateday', 'int'), GETPOST('search_start_dateyear', 'int'));
$search_start_date_2 = dol_mktime(23, 59, 59, GETPOST('search_start_date2month', 'int'), GETPOST('search_start_date2day', 'int'), GETPOST('search_start_date2year', 'int'));
$search_end_date = dol_mktime(0, 0, 0, GETPOST('search_end_datemonth', 'int'), GETPOST('search_end_dateday', 'int'), GETPOST('search_end_dateyear', 'int'));
$search_end_date_2 = dol_mktime(0, 0, 0, GETPOST('search_end_date2month', 'int'), GETPOST('search_end_date2day', 'int'), GETPOST('search_end_date2year', 'int'));
$search_site = GETPOST("search_site", 'none');
$search_training_ref_interne = GETPOST('search_training_ref_interne', 'alpha');
$search_type_session = GETPOST("search_type_session", 'int');
$training_view = GETPOST("training_view", 'int');
$site_view = GETPOST('site_view', 'int');
$status_view = GETPOST('status', 'int');
$search_id = GETPOST('search_id', 'int');
$search_session_ref = GETPOST('search_session_ref', 'alpha');
$search_month = GETPOST('search_month', 'aplha');
$search_year = GETPOST('search_year', 'int');
$search_socpeople_presta = GETPOST('search_socpeople_presta', 'alpha');
$search_soc_employer = GETPOST('search_soc_employer', 'alpha');
$search_soc_requester = GETPOST('search_soc_requester', 'alpha');
$search_session_status = GETPOST('search_session_status', 'none');
$search_session_status_before_archive = GETPOST('search_session_status_before_archive', 'none');
$search_product = GETPOST('search_product', 'none');
$search_intitule_custo = GETPOST('search_intitule_custo', 'none');

$search_sale = GETPOST('search_sale', 'int');

if (empty($status_view))
	$status_view = $search_session_status; // retrocompatibilité

// Banner function
$idforma = GETPOST('idforma', 'int'); // id formation catalogue
if (! empty($idforma)) {
	$agformation = new Formation($db);
	$agformation->fetch($idforma);

	$training_view = 1;
	$search_training_ref = $agformation->ref_obj;
}

// prefilter the list if defined
if (! empty($conf->global->AGF_FILTER_SESSION_LIST_ON_COURANT_MONTH)) {
	$button_removefilter_x = GETPOST("button_removefilter_x", 'none');
	$button_search = GETPOST("button_search_x", 'none');
	if (empty($button_removefilter_x) && empty($button_search)) {
		$search_month = date("m");
		$search_year = date("Y");
	}
}

//Since 8.0 sall get parameters is sent with rapid search
$search_by=GETPOST('search_by', 'alpha');
if (!empty($search_by)) {
    if ($search_by=="search_id") {
        $sall=GETPOST('sall', 'int');
    }else{
        $sall=GETPOST('sall', 'alpha');
    }
	if (!empty($sall)) {
		${$search_by}=$sall;
	}
	$search_month='';
	$search_year='';
}

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x", 'none')) {
	$search_trainning_name = '';
	$search_soc = '';
	$search_teacher_id = "";
	$search_training_ref = '';
	$search_start_date = "";
	$search_start_date_2 = "";
	$search_end_date = "";
	$search_end_date_2 = "";
	$search_site = "";
	$search_training_ref_interne = "";
	$search_type_session = "";
	$search_id = '';
	$search_session_ref = '';
	$search_month = '';
	$search_year = '';
	$search_sale = '';
	$search_socpeople_presta = '';
	$search_soc_employer = '';
	$search_soc_requester = '';
	$search_session_status = '';
	$search_product = '';
	$search_intitule_custo = '';
	$search_session_status_before_archive = '';
}

$hookmanager->initHooks(array(
		'sessionlist'
));

$contextpage = 'listsession' . $status_view;

include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

$agf_session = new Agsession($db);
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($agf_session->table_element, true);
$search_array_options = $extrafields->getOptionalsFromPost('agefodd_session', '', 'search_');

if (empty($search_array_options)) $search_array_options = array();

$arrayfields = array(
		's.rowid' => array(
				'label' => "Id",
				'checked' => 1,
				'enabled' => (! $user->rights->agefodd->session->trainer)
		),
		's.ref' => array(
				'label' => "SessionRef",
				'checked' => 1
		),
		'so.nom' => array(
				'label' => "Company",
				'checked' => 1
		),
		'f.rowid' => array(
				'label' => "AgfFormateur",
				'checked' => 1
		),
		'sale.fk_user_com' => array(
				'label' => "AgfSessionCommercial",
				'checked' => 1
		),
		'c.intitule' => array(
				'label' => "AgfIntitule",
				'checked' => 1
		),
		's.intitule_custo' => array(
			'label' => "AgfFormIntituleCust",
			'checked' => 0
		),
		'c.ref' => array(
				'label' => "Ref",
				'checked' => 1,
				'enabled' => (! $user->rights->agefodd->session->trainer)
		),
		'c.ref_interne' => array(
				'label' => "AgfRefInterne",
				'checked' => 0,
				'enabled' => (! $user->rights->agefodd->session->trainer)
		),
		's.type_session' => array(
				'label' => "AgfFormTypeSession",
				'checked' => 1,
				'enabled' => (! $user->rights->agefodd->session->trainer)
		),
		's.dated' => array(
				'label' => "AgfDateDebut",
				'checked' => 1
		),
		's.datef' => array(
				'label' => "AgfDateFin",
				'checked' => 1
		),
		'dicstatus.intitule' => array(
				'label' => "AgfStatusSession",
				'checked' => 1
		),
		's.status_before_archive' => array(
			'label' => 'AgfStatusBeforeArchiveSession',
			'checked' => 1,
			'enabled' => 1
		),
		'p.ref_interne' => array(
				'label' => "AgfLieu",
				'checked' => 1,
				'enabled' => (! $user->rights->agefodd->session->trainer)
		),
		's.sell_price' => array(
				'label' => "AgfCoutFormation",
				'checked' => 1,
				'enabled' => $user->rights->agefodd->session->margin
		),
		'AgfAmoutHTHF' => array(
				'label' => "AgfAmoutHTHF",
				'checked' => 1,
				'enabled' => $user->rights->agefodd->session->margin
		),
		's.cost_trainer' => array(
				'label' => "AgfCostTrainer",
				'checked' => 1,
				'enabled' => $user->rights->agefodd->session->margin
		),
		'AgfCostOther' => array(
				'label' => "AgfCostOther",
				'checked' => 1,
				'enabled' => $user->rights->agefodd->session->margin
		),
		'AgfFactAmount' => array(
				'label' => "AgfFactAmount",
				'checked' => 1,
				'enabled' => $user->rights->agefodd->session->margin
		),
		'AgfFactAmountHT' => array(
				'label' => "AgfFactAmountHT",
				'checked' => 1,
				'enabled' => $user->rights->agefodd->session->margin
		),
		'AgfMargin' => array(
				'label' => "AgfMargin",
				'checked' => 1,
				'enabled' => $user->rights->agefodd->session->margin
		),
		's.sell_price_planned' => array(
				'label' => "AgfCoutFormationPlanned",
				'checked' => 1,
				'enabled' => $user->rights->agefodd->session->margin
		),
		'AgfCostOtherPlanned' => array(
				'label' => "AgfCostOtherPlanned",
				'checked' => 1,
				'enabled' => $user->rights->agefodd->session->margin
		),
		's.cost_trainer_planned' => array(
				'label' => "AgfCostTrainerPlanned",
				'checked' => 1,
				'enabled' => $user->rights->agefodd->session->margin
		),
		'AgfMarginPlanned' => array(
				'label' => "AgfMarginPlanned",
				'checked' => 1,
				'enabled' => $user->rights->agefodd->session->margin
		),
		's.nb_stagiaire' => array(
				'label' => "AgfNbreParticipants",
				'checked' => 1,
				'enabled' => (! $user->rights->agefodd->session->trainer)
		),
		's.duree_session' => array(
				'label' => "AgfDuree",
				'checked' => 1
		),
		's.notes' => array(
				'label' => "AgfNote",
				'checked' => 1,
				'enabled' => (! $user->rights->agefodd->session->trainer)
		),
		's.fk_socpeople_presta' => array(
				'label' => 'AgfTypePresta',
				'checked' => 0,
				'enabled' => (! $user->rights->agefodd->session->trainer)
		),
		's.fk_soc_employer' => array(
				'label' => 'AgfTypeEmployee',
				'checked' => 0,
				'enabled' => (! $user->rights->agefodd->session->trainer)
		),
		's.fk_soc_requester' => array(
				'label' => 'AgfTypeRequester',
				'checked' => 0,
				'enabled' => (! $user->rights->agefodd->session->trainer)
		),
		'AgfListParticipantsStatus' => array(
				'label' => "AgfListParticipantsStatus",
				'checked' => 1,
				'enabled' => (! $user->rights->agefodd->session->trainer)
		),
        'AgfSheduleBillingState' => array(
                'label' => "AgfSheduleBillingState",
                'checked' => 1,
                'enabled' => (!empty($conf->global->AGF_MANAGE_SESSION_CALENDAR_FACTURATION))
        ),
		's.fk_product' => array(
				'label' => "AgfProductServiceLinked",
				'checked' => 0,
				'enabled' => (! $user->rights->agefodd->session->trainer)
		)
);

foreach ( $arrayfields as $colname => $fields ) {
	if (array_key_exists('enabled', $fields) && empty($fields['enabled'])) {
		unset($arrayfields[$colname]);
	}
}
// Extra fields
if (is_array($extrafields->attributes[$agf_session->table_element]['label']) && count($extrafields->attributes[$agf_session->table_element]['label']) > 0)
{
	foreach ($extrafields->attributes[$agf_session->table_element]['label'] as $key=>$label)
	{
		// skip separation
		if ($extrafields->attributes[$agf_session->table_element]['type'][$key] == 'separate'){
			continue;
		}
		// skip hidden
		if(!empty($extrafields->attributes[$agf_session->table_element]['hidden'][$key])){
			continue;
		}

		$visibility = 1;
		if ($visibility && isset($extrafields->attributes[$agf_session->table_element]['list'][$key]))
		{
			$visibility = dol_eval($extrafields->attributes[$agf_session->table_element]['list'][$key], 1);
		}
		if (abs($visibility) != 1 && abs($visibility) != 2 && abs($visibility) != 5) continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list

		$perms = 1;
		if ($perms && isset($extrafields->attributes[$agf_session->table_element]['perms'][$key]))
		{
			$perms = dol_eval($extrafields->attributes[$agf_session->table_element]['perms'][$key], 1);
		}
		if (empty($perms)) continue;

		// Load language if required
		if (!empty($extrafields->attributes[$agf_session->table_element]['langfile'][$key]))
			$langs->load($extrafields->attributes[$agf_session->table_element]['langfile'][$key]);

		$arrayfields["ef." . $key] = array(
				'label' => $langs->trans($label),
				'checked' => $extrafields->attributes[$agf_session->table_element]['list'][$key],
				'position' => $extrafields->attributes[$agf_session->table_element]['pos'][$key]
		);
	}
}





$filter = array();
$option = '';
if (! empty($search_trainning_name)) {
	$filter['c.intitule'] = $search_trainning_name;
	$option .= '&search_trainning_name=' . $search_trainning_name;
}
if (! empty($search_intitule_custo)) {
	$filter['s.intitule_custo'] = $search_intitule_custo;
	$option .= '&search_intitule_custo=' . $search_intitule_custo;
}
if (! empty($search_soc)) {
	$filter['so.nom'] = $search_soc;
	$option .= '&search_soc=' . $search_soc;
}
if (! empty($search_socpeople_presta)) {
	$filter['socppresta.name'] = $search_socpeople_presta;
}
if (! empty($search_soc_employer)) {
	$filter['soemployer.nom'] = $search_soc_employer;
}
if (! empty($search_soc_requester)) {
	$filter['sorequester.nom'] = $search_soc_requester;
}
if (! empty($search_sale)) {
	$filter['sale.fk_user_com'] = $search_sale;
	$option .= '&search_sale=' . $search_sale;
}
if (! empty($search_teacher_id) && $search_teacher_id != - 1) {
	$filter['f.rowid'] = $search_teacher_id;
	$option .= '&search_teacher_id=' . $search_teacher_id;
}
if (! empty($search_training_ref)) {
	$filter['c.ref'] = $search_training_ref;
	$option .= '&search_training_ref=' . $search_training_ref;
}
if (! empty($search_start_date)) {
	$filter['s.dated'] = $db->idate($search_start_date);
	$option .= '&search_start_datemonth=' . dol_print_date($search_start_date, '%m') . '&search_start_dateday=' . dol_print_date($search_start_date, '%d') . '&search_start_dateyear=' . dol_print_date($search_start_date, '%Y');
}
if (! empty($search_start_date_2)) {
	$filter['s.dated2'] = $db->idate($search_start_date_2);
	$option .= '&search_start_date2month=' . dol_print_date($search_start_date_2, '%m') . '&search_start_date2day=' . dol_print_date($search_start_date_2, '%d') . '&search_start_date2year=' . dol_print_date($search_start_date_2, '%Y');
}
if (! empty($search_end_date)) {
	$filter['s.datef'] = $db->idate($search_end_date);
	$option .= '&search_end_datemonth=' . dol_print_date($search_end_date, '%m') . '&search_end_dateday=' . dol_print_date($search_end_date, '%d') . '&search_end_dateyear=' . dol_print_date($search_end_date, '%Y');
}
if (! empty($search_end_date_2)) {
	$filter['s.datef2'] = $db->idate($search_end_date_2);
	$option .= '&search_end_date2month=' . dol_print_date($search_end_date_2, '%m') . '&search_end_date2day=' . dol_print_date($search_end_date_2, '%d') . '&search_end_date2year=' . dol_print_date($search_end_date_2, '%Y');
}
if (! empty($search_site) && $search_site != - 1) {
	$filter['s.fk_session_place'] = $search_site;
	$option .= '&search_site=' . $search_site;

	if (empty($sortorder)) {
		$sortorder = "DESC";
	}
}
if (! empty($search_training_ref_interne)) {
	$filter['c.ref_interne'] = $search_training_ref_interne;
	$option .= '&search_training_ref_interne=' . $search_training_ref_interne;
}
if ($search_type_session != '' && $search_type_session != - 1) {
	$filter['s.type_session'] = $search_type_session;
	$option .= '&search_type_session=' . $search_type_session;
}

if (!empty($search_session_status_before_archive))
{
	$filter['s.status_before_archive'] = $search_session_status_before_archive;
	$option .= '&search_session_status_before_archive='.$search_session_status_before_archive;
}

if (! empty($status_view)) {
	$filter['s.status'] = $status_view;
	$option .= '&status=' . $status_view;
}
if (! empty($search_id)) {
	$filter['s.rowid'] = $search_id;
	$option .= '&search_id=' . $search_id;
}
if (! empty($search_session_ref)) {
	$filter['s.ref'] = $search_session_ref;
	$option .= '&search_session_ref=' . $search_session_ref;
}
if (! empty($search_month)) {
	$filter['MONTH(s.dated)'] = $search_month;
	$option .= '&search_month=' . $search_month;
}
if (! empty($search_year)) {
	$filter['YEAR(s.dated)'] = $search_year;
	$option .= '&search_year=' . $search_year;
}
if (! empty($search_session_status)) {
	$filter['s.status'] = $search_session_status;
	$option .= '&search_session_status=' . $search_session_status;
}
if (! empty($search_product)) {
	$filter['s.fk_product'] = $search_product;
	$option .= '&search_product=' . $search_product;
}
if (! empty($limit)) {
	$option .= '&limit=' . $limit;
}

if (is_array($search_array_options) && count($search_array_options)>0) {
	foreach ( $search_array_options as $key => $val ) {
		$crit = $val;
		$tmpkey = preg_replace('/search_options_/', '', $key);
		$typ = $extrafields->attributes[$agf_session->table_element]['type'][$tmpkey];
		$mode_search = 0;
		if (in_array($typ, array('date')) && !empty($crit)) $crit=date('Y-m-d', $crit);
		if (in_array($typ, array(
				'int',
				'double',
				'real'
		)))
			$mode_search = 1; // Search on a numeric
		if (in_array($typ, array(
				'sellist',
				'link',
				'chkbxlst',
				'checkbox'
		)) && $crit != '0' && $crit != '-1')
			$mode_search = 2; // Search on a foreign key int
		if ($crit != '' && (! in_array($typ, array(
				'select',
				'sellist'
		)) || $crit != '0') && (! in_array($typ, array(
				'link'
		)) || $crit != '-1')) {

		    if(is_array($crit)){
	            $crit = implode(',',$crit);
	        }
			$filter['ef.' . $tmpkey] = natural_search('ef.' . $tmpkey, $crit, $mode_search);
			$option .= '&search_options_' . $tmpkey . '=' . $crit;
		}
	}
}

if (empty($sortorder)) {
	$sortorder = "ASC";
}
if (empty($sortfield)) {
	$sortfield = "s.dated";
}

if (empty($page) || $page == - 1) {
	$page = 0;
}

$offset = $limit * $page;
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
	// Tri J-2
	if (empty($search_month) && empty($search_year) && empty($search_id) && $conf->global->AGF_FILTER_NEAREST_SESSION) {
		$filter['s.dated>'] = '3';
	}
} elseif ($status_view == 3) {
	$title = $langs->trans("AgfMenuSessNotDoneList");
} elseif ($status_view == 4) {
	$title = $langs->trans("AgfMenuSessArch");
	if (empty($sortorder))
		$sortorder = "DESC";
	if (empty($sortfield))
		$sortfield = "s.datec";
} elseif ($status_view == 5) {
	$title = $langs->trans('AgfMenuSessDone');
} elseif ($status_view == 6) {
	$title = $langs->trans('AgfMenuSessInProgress');
} elseif (! empty($site_view)) {
	$title = $langs->trans("AgfSessPlace");
} elseif (! empty($training_view)) {
	$title = $langs->trans("AgfCatalogDetail");
} else {
	$title = $langs->trans("AgfMenuSess");
}

llxHeader('', $title);

if (empty($sortorder)) {
	$sortorder = "ASC";
}
if (empty($sortfield)) {
	$sortfield = "s.dated";
}

if ($training_view && ! empty($search_training_ref)) {

	$option .= '&training_view=' . $training_view;

	$agf = new Formation($db);
	$result = $agf->fetch('', $search_training_ref);

	$head = training_prepare_head($agf);

	dol_fiche_head($head, 'sessions', $langs->trans("AgfCatalogDetail"), 0, 'label');
	dol_agefodd_banner_tab($agf, 'idforma');
	dol_fiche_end();
}

if (! empty($site_view)) {

	$option .= '&site_view=' . $site_view;

	$agf = new Agefodd_place($db);
	$result = $agf->fetch($search_site);

	if ($result) {
		$head = site_prepare_head($agf);
		dol_fiche_head($head, 'sessions', $langs->trans("AgfSessPlace"), 0, 'address');
		dol_agefodd_banner_tab($agf, 'site_view=1&search_site');
		dol_fiche_end();
	}
}

$agf = new Agsession($db);
//Since dolibarr 11 extrafield is global so reload extrafield to avoid extrafeild for non agesession
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($agf->table_element, true);

// Count total nb of records
$nbtotalofrecords = 0;

if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $agf->fetch_all($sortorder, $sortfield, 0, 0, $filter, $user);
	if ($user->rights->agefodd->session->margin) {

		$total_sellprice = 0;
		$total_costtrainer = 0;
		$total_costother = 0;
		$total_margin = 0;
		$total_percentmargin = '';
		$total_trainee = 0;
		$total_duration = 0;
		$total_sellprice_planned = 0;
		$total_costtrainer_planned = 0;
		$total_costother_planned = 0;
		$total_margin_planned = 0;
		$total_percentmargin_planned = '';

		$total_propal_ht = 0;
		$total_propal_hthf = 0;
		$total_invoice_ht = 0;
		$total_invoice_hthf = 0;

		// Loop on session array to calculate easyly normalized amount
		foreach ( $agf->lines as $line ) {
			if ($line->rowid != $oldid) {
				$total_sellprice += $line->sell_price;
				$total_costtrainer += $line->cost_trainer;
				$total_costother += $line->cost_other;
				$total_sellprice_planned += $line->sell_price_planned;
				$total_costtrainer_planned += $line->cost_trainer_planned;
				$total_costother_planned += $line->cost_other_planned;
				$total_trainee += $line->nb_stagiaire;
				$total_duration += $line->duree_session;
				$oldid = $line->rowid;
			}
		}

		$result = $agf->getTTotalBySession(1);
		if ($result > 0) {
			$TTotalBySession = $agf->TTotalBySession;

			// Loop on sum array to calculate other indicators
			if (is_array($TTotalBySession) && count($TTotalBySession) > 0) {
				foreach ( $TTotalBySession as $key => $data ) {
					$total_propal_ht += $data['propal']['total_ht'];
					$total_propal_hthf += ($data['propal']['total_ht'] - $data['propal']['total_ht_onlycharges']);
					$total_invoice_ht += $data['invoice']['total_ht'];
					$total_invoice_hthf += ($data['invoice']['total_ht'] - $data['invoice']['total_ht_onlycharges']);
				}
			}
		} else {
			setEventMessages(null, $agf->errors, 'errors');
		}

		$total_margin = $total_invoice_ht - ($total_costtrainer + $total_costother);

		if (! empty($total_invoice_ht)) {
			$total_percentmargin = price((($total_margin * 100) / $total_invoice_ht), 0, $langs, 1, 0, 1) . '%';
		} else {
			$total_percentmargin = 'n/a';
		}

		$total_margin_planned = $total_sellprice_planned - ($total_costtrainer_planned + $total_costother_planned);
		if (! empty($total_sellprice_planned)) {
			$total_percentmargin_planned = price((($total_margin_planned * 100) / $total_sellprice_planned), 0, $langs, 1, 0, 1) . '%';
		} else {
			$total_percentmargin_planned = 'n/a';
		}

	}
}
$resql = $agf->fetch_all($sortorder, $sortfield, $limit, $offset, $filter, $user, array_keys($extrafields->attribute_label), 1);

if ($resql != - 1) {

	if (is_array($agf->TTotalBySession) && count($agf->TTotalBySession) == 0) {
		$result = $agf->getTTotalBySession(1);
		if ($result > 0) {
			$TTotalBySession = $agf->TTotalBySession;
		} else {
			setEventMessages(null, $agf->errors, 'errors');
		}
	}

	$num = $resql;

	$arrayofselected = is_array($toselect) ? $toselect : array();

	print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="searchFormList" id="searchFormList">' . "\n";
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	if (! empty($status_view)) {
		print '<input type="hidden" name="status" value="' . $status_view . '"/>';
	}
	if (! empty($site_view)) {
		print '<input type="hidden" name="site_view" value="' . $site_view . '"/>';
	}
	if (! empty($training_view)) {
		print '<input type="hidden" name="training_view" value="' . $training_view . '"/>';
	}
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

	$massactionbutton = $formAgefodd->selectMassSessionsAction();
	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $option, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_generic.png', 0, '', '', $limit);

	$morefilter = '';
	// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid) {
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= $langs->trans('SalesRepresentatives') . ': ';
		$moreforfilter .= $formother->select_salesrepresentatives($search_sale, 'search_sale', $user);
		$moreforfilter .= '</div>';
	}

	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('Period') . '(' . $langs->trans("AgfDateDebut") . ')' . ': ';
	$moreforfilter .= '</div>';
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('Month') . ':<input class="flat" type="text" size="4" name="search_month" value="' . $search_month . '">';
	$moreforfilter .= '</div>';
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('Year') . ':' . $formother->selectyear($search_year ? $search_year : - 1, 'search_year', 1, 20, 5);
	$moreforfilter .= '</div>';

	if ($moreforfilter) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	if ($massactionbutton)
		$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

	$i = 0;
	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste listwithfilterbefore" width="100%">';

	print '<tr class="liste_titre_filter">';

	if (array_key_exists('s.rowid', $arrayfields) && ! empty($arrayfields['s.rowid']['checked'])) {
		print '<td class="liste_titre"><input type="text" class="flat" name="search_id" id="search_id" value="' . $search_id . '" size="2"></td>';
	}
	if (array_key_exists('s.ref', $arrayfields) && ! empty($arrayfields['s.ref']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_session_ref" id="search_session_ref" value="' . $search_session_ref . '" size="15">';
		print '</td>';
	}
	if (array_key_exists('so.nom', $arrayfields) && ! empty($arrayfields['so.nom']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_soc" id="search_soc" value="' . $search_soc . '" size="20">';
		print '</td>';
	}
	if (array_key_exists('f.rowid', $arrayfields) && ! empty($arrayfields['f.rowid']['checked'])) {
		print '<td class="liste_titre">';
		print $formAgefodd->select_formateur_liste($search_teacher_id, 'search_teacher_id', '', 1);
		print '</td>';
	}
	if (!empty($arrayfields['sale.fk_user_com']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	if (array_key_exists('c.intitule', $arrayfields) && ! empty($arrayfields['c.intitule']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_trainning_name" id="search_trainning_name" value="' . $search_trainning_name . '" size="20">';
		print '</td>';
	}
	if (array_key_exists('s.intitule_custo', $arrayfields) && ! empty($arrayfields['s.intitule_custo']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_intitule_custo" id="search_intitule_custo" value="' . $search_intitule_custo . '" size="20">';
		print '</td>';
	}
	if (array_key_exists('c.ref', $arrayfields) && ! empty($arrayfields['c.ref']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_training_ref" id="search_training_ref" value="' . $search_training_ref . '" size="10">';
		print '</td>';
	}
	if (array_key_exists('c.ref_interne', $arrayfields) && ! empty($arrayfields['c.ref_interne']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_training_ref_interne" id="search_training_ref_interne" value="' . $search_training_ref_interne . '" size="10">';
		print '</td>';
	}
	if (array_key_exists('s.type_session', $arrayfields) && ! empty($arrayfields['s.type_session']['checked'])) {
		print '<td class="liste_titre">';
		print $formAgefodd->select_type_session('search_type_session', $search_type_session, 1);
		print '</td>';
	}
	if (array_key_exists('s.dated', $arrayfields) && ! empty($arrayfields['s.dated']['checked'])) {
		print '<td class="liste_titre nowrap">';
		print $form->select_date($search_start_date, 'search_start_date', 0, 0, 1, 'search_form');
		print '<br />';
		print $form->select_date($search_start_date_2, 'search_start_date2', 0, 0, 1, 'search_form');
		print '</td>';
	}
	if (array_key_exists('s.datef', $arrayfields) && ! empty($arrayfields['s.datef']['checked'])) {
		print '<td class="liste_titre nowrap">';
		print $form->select_date($search_end_date, 'search_end_date', 0, 0, 1, 'search_form');
		print '<br />';
		print $form->select_date($search_end_date_2, 'search_end_date2', 0, 0, 1, 'search_form');
		print '</td>';
	}
	if (array_key_exists('dicstatus.intitule', $arrayfields) && ! empty($arrayfields['dicstatus.intitule']['checked'])) {
		print '<td class="liste_titre">';
		print $formAgefodd->select_session_status($search_session_status, 'search_session_status', 't.active=1', 1);
		print '</td>';
	}
	if (! empty($arrayfields['s.status_before_archive']['checked'])) {
		print '<td class="liste_titre">';
		print $formAgefodd->select_session_status($search_session_status_before_archive, 'search_session_status_before_archive', '', 1);
		print '</td>';
	}
	if (array_key_exists('p.ref_interne', $arrayfields) && ! empty($arrayfields['p.ref_interne']['checked'])) {
		print '<td class="liste_titre">';
		print $formAgefodd->select_site_forma($search_site, 'search_site', 1, 0, array(), 'maxwidth200');
		print '</td>';
	}
	if (array_key_exists('s.sell_price', $arrayfields) && ! empty($arrayfields['s.sell_price']['checked'])) {
		print '<td class="liste_titre" name="margininfo1" ></td>';
	}
	if (array_key_exists('AgfAmoutHTHF', $arrayfields) && ! empty($arrayfields['AgfAmoutHTHF']['checked'])) {
		print '<td class="liste_titre" name="margininfo2" ></td>';
	}
	if (array_key_exists('s.cost_trainer', $arrayfields) && ! empty($arrayfields['s.cost_trainer']['checked'])) {
		print '<td class="liste_titre" name="margininfo3"  ></td>';
	}
	if (array_key_exists('AgfCostOther', $arrayfields) && ! empty($arrayfields['AgfCostOther']['checked'])) {
		print '<td class="liste_titre" name="margininfo4"  ></td>';
	}
	if (array_key_exists('AgfFactAmount', $arrayfields) && ! empty($arrayfields['AgfFactAmount']['checked'])) {
		print '<td class="liste_titre" name="margininfo5"  ></td>';
	}
	if (array_key_exists('AgfFactAmountHT', $arrayfields) && ! empty($arrayfields['AgfFactAmountHT']['checked'])) {
		print '<td class="liste_titre" name="margininfo6"  ></td>';
	}
	if (array_key_exists('AgfMargin', $arrayfields) && ! empty($arrayfields['AgfMargin']['checked'])) {
		print '<td class="liste_titre" name="margininfo7"  ></td>';
	}
	if (array_key_exists('s.sell_price_planned', $arrayfields) && ! empty($arrayfields['s.sell_price_planned']['checked'])) {
		print '<td class="liste_titre" name="margininfo8" ></td>';
	}
	if (array_key_exists('AgfCostOtherPlanned', $arrayfields) && ! empty($arrayfields['AgfCostOtherPlanned']['checked'])) {
		print '<td class="liste_titre" name="margininfo9" ></td>';
	}
	if (array_key_exists('s.cost_trainer_planned', $arrayfields) && ! empty($arrayfields['s.cost_trainer_planned']['checked'])) {
		print '<td class="liste_titre" name="margininfo10" ></td>';
	}
	if (array_key_exists('AgfMarginPlanned', $arrayfields) && ! empty($arrayfields['AgfMarginPlanned']['checked'])) {
		print '<td class="liste_titre" name="margininfo11"  ></td>';
	}
	if (array_key_exists('s.nb_stagiaire', $arrayfields) && ! empty($arrayfields['s.nb_stagiaire']['checked'])) {
		print '<td class="liste_titre"></td>';
	}
	if (array_key_exists('s.duree_session', $arrayfields) && ! empty($arrayfields['s.duree_session']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	if (array_key_exists('s.notes', $arrayfields) && ! empty($arrayfields['s.notes']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	if (array_key_exists('s.fk_socpeople_presta', $arrayfields) && ! empty($arrayfields['s.fk_socpeople_presta']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_socpeople_presta" id="search_socpeople_presta" value="' . $search_socpeople_presta . '" size="15">';
		print '</td>';
	}
	if (array_key_exists('s.fk_soc_employer', $arrayfields) && ! empty($arrayfields['s.fk_soc_employer']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_soc_employer" id="search_soc_employer" value="' . $search_soc_employer . '" size="15">';
		print '</td>';
	}
	if (array_key_exists('s.fk_soc_requester', $arrayfields) && ! empty($arrayfields['s.fk_soc_requester']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_soc_requester" id="search_soc_requester" value="' . $search_soc_requester . '" size="15">';
		print '</td>';
	}
	if (array_key_exists('AgfListParticipantsStatus', $arrayfields) && ! empty($arrayfields['AgfListParticipantsStatus']['checked'])) {
		print '<td class="liste_titre"></td>';
	}
	if (array_key_exists('AgfSheduleBillingState', $arrayfields) && ! empty($arrayfields['AgfSheduleBillingState']['checked'])) {
	    print '<td class="liste_titre"></td>';
	}
	if (array_key_exists('s.fk_product', $arrayfields) && ! empty($arrayfields['s.fk_product']['checked'])) {
		print '<td class="liste_titre">';
		print $form->select_produits($search_product, 'search_product', '', 10000);
		print '</td>';
	}

	// Extra fields
	if (file_exists(DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php')) {
        $extrafieldsobjectkey = 'agefodd_session';
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';
	} else {
		if (is_array($extrafields->attributes[$agf_session->table_element]['label']) && count($extrafields->attributes[$agf_session->table_element]['label']) > 0) {
			foreach ($extrafields->attributes[$agf_session->table_element]['label'] as $key=>$label) {
				if (! empty($arrayfields["ef." . $key]['checked'])) {
					$align = $extrafields->getAlignFlag($key);
					$typeofextrafield = $extrafields->attributes[$agf_session->table_element]['type'][$key] ;
					print '<td class="liste_titre' . ($align ? ' ' . $align : '') . '">';
					if (in_array($typeofextrafield, array(
							'varchar',
							'int',
							'double',
							'select'
					)) && empty($extrafields->attributes[$agf_session->table_element]['computed'][$key])) {
						$crit = $val;
						$tmpkey = preg_replace('/search_options_/', '', $key);
						$searchclass = '';
						if (in_array($typeofextrafield, array(
								'varchar',
								'select'
						)))
							$searchclass = 'searchstring';
						if (in_array($typeofextrafield, array(
								'int',
								'double'
						)))
							$searchclass = 'searchnum';
						print '<input class="flat' . ($searchclass ? ' ' . $searchclass : '') . '" size="4" type="text" name="search_options_' . $tmpkey . '" id="search_options_' . $tmpkey . '" value="' . dol_escape_htmltag($search_array_options['search_options_' . $tmpkey]) . '">';
					} else {
						// for the type as 'checkbox', 'chkbxlst', 'sellist' we should use code instead of id (example: I declare a 'chkbxlst' to have a link with dictionnairy, I have to extend it with the 'code' instead 'rowid')
						echo $extrafields->showInputField($key, $search_array_options['search_options_' . $key], '', '', 'search_');
					}
					print '</td>';
				}
			}
		}
	}

	// Action column
	print '<td class="liste_titre" align="right">';
	if (method_exists($form, 'showFilterButtons')) {
		$searchpicto = $form->showFilterButtons();

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
	if (array_key_exists('s.rowid', $arrayfields) && ! empty($arrayfields['s.rowid']['checked'])) {
		print_liste_field_titre($langs->trans("Id"), $_SERVER['PHP_SELF'], "s.rowid", "", $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('s.ref', $arrayfields) && ! empty($arrayfields['s.ref']['checked'])) {
		print_liste_field_titre($langs->trans("SessionRef"), $_SERVER['PHP_SELF'], "s.ref", "", $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('so.nom', $arrayfields) && ! empty($arrayfields['so.nom']['checked'])) {
		print_liste_field_titre($langs->trans("Company"), $_SERVER['PHP_SELF'], "so.nom", "", $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('f.rowid', $arrayfields) && ! empty($arrayfields['f.rowid']['checked'])) {
		print_liste_field_titre($langs->trans("AgfFormateur"), $_SERVER['PHP_SELF'], "", "", $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('sale.fk_user_com', $arrayfields) && ! empty($arrayfields['sale.fk_user_com']['checked'])) {
		print_liste_field_titre($langs->trans("AgfSessionCommercial"), $_SERVER['PHP_SELF'], "", "", $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('c.intitule', $arrayfields) && ! empty($arrayfields['c.intitule']['checked'])) {
		print_liste_field_titre($langs->trans("AgfIntitule"), $_SERVER['PHP_SELF'], "c.intitule", "", $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('s.intitule_custo', $arrayfields) && ! empty($arrayfields['s.intitule_custo']['checked'])) {
		print_liste_field_titre($langs->trans("AgfFormIntituleCust"), $_SERVER['PHP_SELF'], "s.intitule_custo", "", $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('c.ref', $arrayfields) && ! empty($arrayfields['c.ref']['checked'])) {
		print_liste_field_titre($langs->trans("Ref"), $_SERVER['PHP_SELF'], "c.ref", "", $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('c.ref_interne', $arrayfields) && ! empty($arrayfields['c.ref_interne']['checked'])) {
		print_liste_field_titre($langs->trans("AgfRefInterne"), $_SERVER['PHP_SELF'], "c.ref_interne", "", $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('s.type_session', $arrayfields) && ! empty($arrayfields['s.type_session']['checked'])) {
		print_liste_field_titre($langs->trans("AgfFormTypeSession"), $_SERVER['PHP_SELF'], "s.type_session", "", $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('s.dated', $arrayfields) && ! empty($arrayfields['s.dated']['checked'])) {
		print_liste_field_titre($langs->trans("AgfDateDebut"), $_SERVER['PHP_SELF'], "s.dated", "", $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('s.datef', $arrayfields) && ! empty($arrayfields['s.datef']['checked'])) {
		print_liste_field_titre($langs->trans("AgfDateFin"), $_SERVER['PHP_SELF'], "s.datef", "", $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('dicstatus.intitule', $arrayfields) && ! empty($arrayfields['dicstatus.intitule']['checked'])) {
		print_liste_field_titre($langs->trans("AgfStatusSession"), $_SERVER['PHP_SELF'], "dictstatus.intitule", "", $option, '', $sortfield, $sortorder);
	}
	if (! empty($arrayfields['s.status_before_archive']['checked'])) {
		print_liste_field_titre($langs->trans("AgfStatusBeforeArchiveSession"), $_SERVER['PHP_SELF'], "s.status_before_archive", "", $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('p.ref_interne', $arrayfields) && ! empty($arrayfields['p.ref_interne']['checked'])) {
		print_liste_field_titre($langs->trans("AgfLieu"), $_SERVER['PHP_SELF'], "p.ref_interne", "", $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('s.sell_price', $arrayfields) && ! empty($arrayfields['s.sell_price']['checked'])) {
		print_liste_field_titre($langs->trans("AgfCoutFormation"), $_SERVER['PHP_SELF'], "s.sell_price", "", $option, ' name="margininfo1"  ', $sortfield, $sortorder);
	}
	if (array_key_exists('AgfAmoutHTHF', $arrayfields) && ! empty($arrayfields['AgfAmoutHTHF']['checked'])) {
		print_liste_field_titre($langs->trans("AgfAmoutHTHF"), $_SERVER['PHP_SELF'], "s.sell_price", "", $option, ' name="margininfo2"  ', $sortfield, $sortorder);
	}
	if (array_key_exists('s.cost_trainer', $arrayfields) && ! empty($arrayfields['s.cost_trainer']['checked'])) {
		print_liste_field_titre($langs->trans("AgfCostTrainer"), $_SERVER['PHP_SELF'], "s.cost_trainer", "", $option, ' name="margininfo3" ', $sortfield, $sortorder);
	}
	if (array_key_exists('AgfCostOther', $arrayfields) && ! empty($arrayfields['AgfCostOther']['checked'])) {
		print_liste_field_titre($langs->trans("AgfCostOther"), $_SERVER['PHP_SELF'], "", "", $option, ' name="margininfo4"  ', $sortfield, $sortorder);
	}
	if (array_key_exists('AgfFactAmount', $arrayfields) && ! empty($arrayfields['AgfFactAmount']['checked'])) {
		print_liste_field_titre($langs->trans("AgfFactAmount"), $_SERVER['PHP_SELF'], "", "", $option, ' name="margininfo5"  ', $sortfield, $sortorder);
	}
	if (array_key_exists('AgfFactAmountHT', $arrayfields) && ! empty($arrayfields['AgfFactAmountHT']['checked'])) {
		print_liste_field_titre($langs->trans("AgfFactAmountHT"), $_SERVER['PHP_SELF'], "", "", $option, ' name="margininfo6"  ', $sortfield, $sortorder);
	}
	if (array_key_exists('AgfMargin', $arrayfields) && ! empty($arrayfields['AgfMargin']['checked'])) {
		print_liste_field_titre($langs->trans("AgfMargin"), $_SERVER['PHP_SELF'], "", "", $option, ' name="margininfo7"  ', $sortfield, $sortorder);
	}
	if (array_key_exists('s.sell_price_planned', $arrayfields) && ! empty($arrayfields['s.sell_price_planned']['checked'])) {
		print_liste_field_titre($langs->trans("AgfCoutFormationPlanned"), $_SERVER['PHP_SELF'], "s.sell_price_planned", "", $option, ' name="margininfo8"  ', $sortfield, $sortorder);
	}
	if (array_key_exists('AgfCostOtherPlanned', $arrayfields) && ! empty($arrayfields['AgfCostOtherPlanned']['checked'])) {
		print_liste_field_titre($langs->trans("AgfCostOtherPlanned"), $_SERVER['PHP_SELF'], "", "", $option, ' name="margininfo9"  ', $sortfield, $sortorder);
	}
	if (array_key_exists('s.cost_trainer_planned', $arrayfields) && ! empty($arrayfields['s.cost_trainer_planned']['checked'])) {
		print_liste_field_titre($langs->trans("AgfCostTrainerPlanned"), $_SERVER['PHP_SELF'], "s.cost_trainer_planned", "", $option, ' name="margininfo10"  ', $sortfield, $sortorder);
	}
	if (array_key_exists('AgfMarginPlanned', $arrayfields) && ! empty($arrayfields['AgfMarginPlanned']['checked'])) {
		print_liste_field_titre($langs->trans("AgfMarginPlanned"), $_SERVER['PHP_SELF'], "", "", $option, ' name="margininfo11"  ', $sortfield, $sortorder);
	}
	if (array_key_exists('s.nb_stagiaire', $arrayfields) && ! empty($arrayfields['s.nb_stagiaire']['checked'])) {
		print_liste_field_titre($langs->trans("AgfNbreParticipants"), $_SERVER['PHP_SELF'], "s.nb_stagiaire", '', $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('s.duree_session', $arrayfields) && ! empty($arrayfields['s.duree_session']['checked'])) {
		print_liste_field_titre($langs->trans("AgfDuree"), $_SERVER['PHP_SELF'], "s.duree_session", '', $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('s.notes', $arrayfields) && ! empty($arrayfields['s.notes']['checked'])) {
		print_liste_field_titre($langs->trans("AgfNote"), $_SERVER['PHP_SELF'], "s.notes", '', $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('s.fk_socpeople_presta', $arrayfields) && ! empty($arrayfields['s.fk_socpeople_presta']['checked'])) {
		print_liste_field_titre($langs->trans("AgfTypePresta"), $_SERVER['PHP_SELF'], "s.fk_socpeople_presta", '', $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('s.fk_soc_employer', $arrayfields) && ! empty($arrayfields['s.fk_soc_employer']['checked'])) {
		print_liste_field_titre($langs->trans("AgfTypeEmployee"), $_SERVER['PHP_SELF'], "s.fk_soc_employer", '', $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('s.fk_soc_requester', $arrayfields) && ! empty($arrayfields['s.fk_soc_requester']['checked'])) {
		print_liste_field_titre($langs->trans("AgfTypeRequester"), $_SERVER['PHP_SELF'], "s.fk_soc_requester", '', $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('AgfListParticipantsStatus', $arrayfields) && ! empty($arrayfields['AgfListParticipantsStatus']['checked'])) {
		print_liste_field_titre($langs->trans("AgfListParticipantsStatus"), $_SERVER['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('AgfSheduleBillingState', $arrayfields) && ! empty($arrayfields['AgfSheduleBillingState']['checked'])) {
	    print_liste_field_titre($langs->trans("AgfSheduleBillingState"), $_SERVER['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
	}
	if (array_key_exists('s.fk_product', $arrayfields) && ! empty($arrayfields['s.fk_product']['checked'])) {
		print_liste_field_titre($langs->trans("AgfProductServiceLinked"), $_SERVER['PHP_SELF'], '', '', $option, '', $sortfield, $sortorder);
	}

	// Extra fields
	if (is_array($extrafields->attributes[$agf_session->table_element]['label']) && count($extrafields->attributes[$agf_session->table_element]['label']) > 0) {
		foreach ($extrafields->attributes[$agf_session->table_element]['label'] as $key=>$label)
		{
			if (! empty($arrayfields["ef." . $key]['checked'])) {
				$align = $extrafields->getAlignFlag($key);
				$sortonfield = "ef." . $key;
				if (! empty($extrafields->attributes[$agf_session->table_element]['computed'][$key]))
					$sortonfield = '';
				print getTitleFieldOfList($langs->trans($extrafields->attributes[$agf_session->table_element]['label'][$key]), 0, $_SERVER["PHP_SELF"], $sortonfield, "", $option, ($align ? 'align="' . $align . '"' : ''), $sortfield, $sortorder) . "\n";
			}
		}
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');

	print "</tr>\n";

	$propal_total_ht = $pv_total_ht = $invoice_total_ht = 0;

	$var = true;
	$oldid = 0;
	foreach ( $agf->lines as $line ) {

		if ($line->rowid != $oldid) {

			// Affichage tableau des sessions
			print '<tr class="oddeven">';
			// Calcul de la couleur du lien en fonction de la couleur définie sur la session
			// http://www.w3.org/TR/AERT#color-contrast
			// SI ((Red value X 299) + (Green value X 587) + (Blue value X 114)) / 1000 < 125 ALORS
			// AFFICHER DU BLANC (#FFF)
			$couleur_rgb = agf_hex2rgb($line->color);
			$color_a = '';
			if ($line->color && ((($couleur_rgb[0] * 299) + ($couleur_rgb[1] * 587) + ($couleur_rgb[2] * 114)) / 1000) < 125) {
				$color_a = ' style="color: #FFFFFF;"';
			}
			if (! empty($conf->global->AGF_SHOW_COLORED_LINK) && ! empty($line->admin_task_close_session)) { // Ancienne fonctionnalité perdue dans le néant
				$color_a = ' style="color: green;"';
			}
			if (array_key_exists('s.rowid', $arrayfields) && ! empty($arrayfields['s.rowid']['checked'])) {
				print '<td  style="background: #' . $line->color . '">';
				print $line->getNomUrl(1, '', 0, 'id', 1, $color_a);
				print '</td>';
			}
			if (array_key_exists('s.ref', $arrayfields) && ! empty($arrayfields['s.ref']['checked'])) {
				print '<td  style="background: #' . $line->color . '">';
				print $line->getNomUrl(1, '', 0, 'sessionref', 1, $color_a);
				print '</td>';
			}
			if (array_key_exists('so.nom', $arrayfields) && ! empty($arrayfields['so.nom']['checked'])) {
				print '<td>';
				if (! empty($line->socid) && $line->socid != - 1) {
					$soc = new Societe($db);
					$soc->fetch($line->socid);
					print $soc->getNomURL(1);
				} else {
					print '&nbsp;';
				}
				print '</td>';
			}
			if (array_key_exists('f.rowid', $arrayfields) && ! empty($arrayfields['f.rowid']['checked'])) {
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
			}
			if (!empty($arrayfields['sale.fk_user_com']['checked']))
			{
				print '<td>';
				$commercial = new User($db);
				if (!empty($line->fk_user_com))
				{
					$commercial->fetch($line->fk_user_com);
				}
				if (! empty($commercial->id)) {
					print $commercial->getNomUrl();
				} else {
					print '&nbsp;';
				}
				print '</td>';
			}
			if (array_key_exists('c.intitule', $arrayfields) && ! empty($arrayfields['c.intitule']['checked'])) {
				$couleur_rgb_training = agf_hex2rgb($line->trainingcolor);
				$color_training = '';
				if ($line->trainingcolor && ((($couleur_rgb_training[0] * 299) + ($couleur_rgb_training[1] * 587) + ($couleur_rgb_training[2] * 114)) / 1000) < 125) {
					$color_training = ' style="color: #FFFFFF;background: #' . $line->trainingcolor . '"';
				} else {
					$color_training = ' style="background: #' . $line->trainingcolor . '" ';
				}

				print '<td ' . $color_training . '>' . stripslashes(dol_trunc($line->intitule, 60)) . '</td>';
			}
			if (array_key_exists('s.intitule_custo', $arrayfields) && ! empty($arrayfields['s.intitule_custo']['checked'])) {
				print '<td>' . stripslashes($line->intitule_custo) . '</td>';
			}
			if (array_key_exists('c.ref', $arrayfields) && ! empty($arrayfields['c.ref']['checked'])) {
				print '<td>' . $line->ref . '</td>';
			}
			if (array_key_exists('c.ref_interne', $arrayfields) && ! empty($arrayfields['c.ref_interne']['checked'])) {
				print '<td>' . $line->training_ref_interne . '</td>';
			}
			if (array_key_exists('s.type_session', $arrayfields) && ! empty($arrayfields['s.type_session']['checked'])) {
				print '<td>' . ($line->type_session ? $langs->trans('AgfFormTypeSessionInter') : $langs->trans('AgfFormTypeSessionIntra')) . '</td>';
			}
			if (array_key_exists('s.dated', $arrayfields) && ! empty($arrayfields['s.dated']['checked'])) {
				print '<td>' . dol_print_date($line->dated, 'daytextshort') . '</td>';
			}
			if (array_key_exists('s.datef', $arrayfields) && ! empty($arrayfields['s.datef']['checked'])) {
				print '<td>' . dol_print_date($line->datef, 'daytextshort') . '</td>';
			}
			if (array_key_exists('dicstatus.intitule', $arrayfields) && ! empty($arrayfields['dicstatus.intitule']['checked'])) {
				print '<td>';
				print $line->statuslib;
				print '</td>';
			}
			if (! empty($arrayfields['s.status_before_archive']['checked'])) {
				print '<td>';
				print $line->archivestatuslib;
				print '</td>';
			}

			if (array_key_exists('p.ref_interne', $arrayfields) && ! empty($arrayfields['p.ref_interne']['checked'])) {
				print '<td><a href="' . dol_buildpath('/agefodd/site/card.php?id=', 1) . $line->fk_session_place . '">' . stripslashes($line->ref_interne) . '</a></td>';
			}

			if (array_key_exists('s.sell_price', $arrayfields) && ! empty($arrayfields['s.sell_price']['checked'])) {
				print '<td  nowrap="nowrap"  name="margininfoline1' . $line->rowid . '">' . price($line->sell_price, 0, $langs, 1, - 1, - 1, 'auto') . '</td>';
			}
			if (array_key_exists('AgfAmoutHTHF', $arrayfields) && ! empty($arrayfields['AgfAmoutHTHF']['checked'])) {
				$amount = 0;
				if (! empty($TTotalBySession[$line->rowid])) {
					if (! empty($TTotalBySession[$line->rowid]['propal']['total_ht']))
						$amount = $TTotalBySession[$line->rowid]['propal']['total_ht'];
					if (! empty($TTotalBySession[$line->rowid]['propal']['total_ht_onlycharges']))
						$amount -= $TTotalBySession[$line->rowid]['propal']['total_ht_onlycharges'];
				}

				print '<td  nowrap="nowrap" name="margininfoline2' . $line->rowid . '" >' . price($amount, 0, $langs, 1, - 1, - 1, 'auto') . '</td>';
			}
			if (array_key_exists('s.cost_trainer', $arrayfields) && ! empty($arrayfields['s.cost_trainer']['checked']))
				print '<td  nowrap="nowrap"  name="margininfoline3' . $line->rowid . '">' . price($line->cost_trainer, 0, $langs, 1, - 1, - 1, 'auto') . '</td>';
			if (array_key_exists('AgfCostOther', $arrayfields) && ! empty($arrayfields['AgfCostOther']['checked']))
				print '<td  nowrap="nowrap"  name="margininfoline4' . $line->rowid . '" >' . price($line->cost_other, 0, $langs, 1, - 1, - 1, 'auto') . '</td>';

			if (array_key_exists('AgfFactAmount', $arrayfields) && ! empty($arrayfields['AgfFactAmount']['checked'])) {
				print '<td  nowrap="nowrap"  name="margininfoline5' . $line->rowid . '" >';
				$amount = 0;
				if (! empty($TTotalBySession[$line->rowid])) {
					if (! empty($TTotalBySession[$line->rowid]['invoice']['total_ht']))
						$amount = $TTotalBySession[$line->rowid]['invoice']['total_ht'];
					if (! empty($TTotalBySession[$line->rowid]['invoice']['total_ht_onlycharges']))
						$amount -= $TTotalBySession[$line->rowid]['invoice']['total_ht_onlycharges'];
				}

				if ($amount == 0 && $line->sell_price != 0) {
					$bgfact = 'red';
				} else {
					$bgfact = '';
				}
				print '<font style="color:' . $bgfact . '">' . price($amount, 0, $langs, 1, - 1, - 1, 'auto') . '</font>';
				print '</td>';
			}

			if (array_key_exists('AgfFactAmountHT', $arrayfields) && ! empty($arrayfields['AgfFactAmountHT']['checked'])) {
				$amount = 0;

				if (! empty($TTotalBySession[$line->rowid])) {
					if (! empty($TTotalBySession[$line->rowid]['invoice']['total_ht'])) {
						$amount = $TTotalBySession[$line->rowid]['invoice']['total_ht'];
					}
				}
				print '<td  nowrap="nowrap"  name="margininfoline6' . $line->rowid . '" >' . price($amount, 0, $langs, 1, - 1, - 1, 'auto') . '</td>';
			}

			if (array_key_exists('AgfMargin', $arrayfields) && ! empty($arrayfields['AgfMargin']['checked'])) {
				print '<td nowrap="nowrap"  name="margininfoline7' . $line->rowid . '">';
				if ($TTotalBySession[$line->rowid]['invoice']['total_ht'] > 0) {
					$margin = $TTotalBySession[$line->rowid]['invoice']['total_ht'] - ($line->cost_trainer + $line->cost_other);
					$percentmargin = price((($margin * 100) / $TTotalBySession[$line->rowid]['invoice']['total_ht']), 0, $langs, 1, 0, 1) . '%';
				} else {
					$margin = 0;
					$percentmargin = "n/a";
				}
				print price($margin, 0, '', 1, - 1, - 1, 'auto') . '(' . $percentmargin . ')';
				print '</td>';
			}
			if (array_key_exists('s.sell_price_planned', $arrayfields) && ! empty($arrayfields['s.sell_price_planned']['checked'])) {
				print '<td  nowrap="nowrap"  name="margininfoline8' . $line->rowid . '">' . price($line->sell_price_planned, 0, $langs, 1, - 1, - 1, 'auto') . '</td>';
			}
			if (array_key_exists('AgfCostOtherPlanned', $arrayfields) && ! empty($arrayfields['AgfCostOtherPlanned']['checked'])) {
				print '<td  nowrap="nowrap"  name="margininfoline9' . $line->rowid . '">' . price($line->cost_other_planned, 0, $langs, 1, - 1, - 1, 'auto') . '</td>';
			}
			if (array_key_exists('s.cost_trainer_planned', $arrayfields) && ! empty($arrayfields['s.cost_trainer_planned']['checked'])) {
				print '<td  nowrap="nowrap"  name="margininfoline10' . $line->rowid . '">' . price($line->cost_trainer_planned, 0, $langs, 1, - 1, - 1, 'auto') . '</td>';
			}
			if (array_key_exists('AgfMarginPlanned', $arrayfields) && ! empty($arrayfields['AgfMarginPlanned']['checked'])) {
				print '<td nowrap="nowrap"  name="margininfoline11' . $line->rowid . '">';
				if ($line->sell_price_planned > 0) {
					$margin_planned = $line->sell_price_planned - ($line->cost_other_planned + $line->cost_trainer_planned);
					$percentmargin_planned = price((($margin_planned * 100) / $line->sell_price_planned), 0, $langs, 1, 0, 1) . '%';
				} else {
					$margin_planned = 0;
					$percentmargin_planned = "n/a";
				}
				print price($margin_planned, 0, '', 1, - 1, - 1, 'auto') . '(' . $percentmargin_planned . ')';
				print '</td>';
			}
			if (array_key_exists('s.nb_stagiaire', $arrayfields) && ! empty($arrayfields['s.nb_stagiaire']['checked'])) {
				print '<td>' . $line->nb_stagiaire . '</td>';
			}
			if (array_key_exists('s.duree_session', $arrayfields) && ! empty($arrayfields['s.duree_session']['checked'])) {
				print '<td>' . $line->duree_session . '</td>';
			}
			if (array_key_exists('s.notes', $arrayfields) && ! empty($arrayfields['s.notes']['checked'])) {
				print '<td>' . stripslashes(dol_trunc($line->notes, 60)) . '</td>';
			}
			if (array_key_exists('s.fk_socpeople_presta', $arrayfields) && ! empty($arrayfields['s.fk_socpeople_presta']['checked'])) {
				if ($line->fk_socpeople_presta > 0) {
					$contact = new Contact($db);
					$contact->fetch($line->fk_socpeople_presta);
					print '<td>' . $contact->getNomUrl(1) . '</td>';
					unset($contact);
				} else
					print '<td></td>';
			}

			if (array_key_exists('s.fk_soc_employer', $arrayfields) && ! empty($arrayfields['s.fk_soc_employer']['checked'])) {
				if ($line->fk_soc_employer > 0) {
					$soc = new Societe($db);
					$soc->fetch($line->fk_soc_employer);
					print '<td>' . $soc->getNomUrl(1) . '</td>';
					unset($soc);
				} else
					print '<td></td>';
			}

			if (array_key_exists('s.fk_soc_requester', $arrayfields) && ! empty($arrayfields['s.fk_soc_requester']['checked'])) {
				if ($line->socrequesterid > 0) {
					$soc = new Societe($db);
					$soc->fetch($line->socrequesterid);
					print '<td>' . $soc->getNomUrl(1) . '</td>';
					unset($soc);
				} else
					print '<td></td>';
			}

			if (array_key_exists('AgfListParticipantsStatus', $arrayfields) && ! empty($arrayfields['AgfListParticipantsStatus']['checked'])) {
				if (! empty($line->nb_subscribe_min)) {
					if ($line->nb_confirm >= $line->nb_subscribe_min) {
						$styleminstatus = 'style="background: green"';
					} else {
						$styleminstatus = 'style="background: red"';
					}
				} else {
					$styleminstatus = '';
				}
				print '<td ' . $styleminstatus . '>' . $line->nb_prospect . '/' . $line->nb_confirm . '/' . $line->nb_cancelled . '</td>';
			}

			if (array_key_exists('AgfSheduleBillingState', $arrayfields) && ! empty($arrayfields['AgfSheduleBillingState']['checked'])) {
			    $billed = Agefodd_sesscalendar::countBilledshedule($line->id);
			    $total = Agefodd_sesscalendar::countTotalshedule($line->id);

			    if (empty($total)) $roundedBilled = 0;
			    else $roundedBilled = round($billed*100/$total);

			    print '<td>';
			    print displayProgress($roundedBilled, '', $billed."/".$total, '6em');
			    print '</td>';
			}
			if (array_key_exists('s.fk_product', $arrayfields) && ! empty($arrayfields['s.fk_product']['checked'])) {
				if (! empty($line->fk_product)) {
					$product = new Product($db);
					$product->fetch($line->fk_product);
					$productlink = $product->getNomUrl(1);
				} else {
					$productlink = '';
				}
				print '<td>' . $productlink . '</td>';
			}

			// Extra fields
			if (is_array($extrafields->attributes[$agf_session->table_element]['label']) && count($extrafields->attributes[$agf_session->table_element]['label']) > 0) {
				foreach ($extrafields->attributes[$agf_session->table_element]['label'] as $key=>$label) {
					if (! empty($arrayfields["ef." . $key]['checked'])) {
						$align = $extrafields->getAlignFlag($key);
						print '<td';
						if ($align)
							print ' align="' . $align . '"';
						print '>';
						$tmpkey = 'options_' . $key;
						print $extrafields->showOutputField($key, $line->array_options[$tmpkey], '');
						print '</td>';
						if (! $i)
							$totalarray['nbfield'] ++;
						if (! empty($val['isameasure'])) {
							if (! $i)
								$totalarray['pos'][$totalarray['nbfield']] = 'ef.' . $tmpkey;
							$totalarray['val']['ef.' . $tmpkey] += $line->array_options[$tmpkey];
						}
					}
				}
			}

			// Action
			print '<td class="nowrap" align="center">';
			if ($massactionbutton || $massaction) // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			{
				$selected = 0;
				if (in_array($line->rowid, $arrayofselected))
					$selected = 1;
				print '<input id="cb' . $line->rowid . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $line->rowid . '"' . ($selected ? ' checked="checked"' : '') . '>';
			}
			print '</td>';
			if (! $i)
				$totalarray['nbfield'] ++;

			// Action column
			// print '<td>&nbsp;</td>';
			print "</tr>\n";
		} else {
			print '<tr class="oddeven">';
			if (array_key_exists('s.rowid', $arrayfields) && ! empty($arrayfields['s.rowid']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('s.ref', $arrayfields) && ! empty($arrayfields['s.ref']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('so.nom', $arrayfields) && ! empty($arrayfields['so.nom']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('f.rowid', $arrayfields) && ! empty($arrayfields['f.rowid']['checked'])) {
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
			}
			if (!empty($arrayfields['sale.fk_user_com']['checked']))
			{
				print '<td>';
				$commercial = new User($db);
				if (!empty($line->fk_user_com))
				{
					$commercial->fetch($line->fk_user_com);
				}
				if (! empty($commercial->id)) {
					print $commercial->getNomUrl();
				} else {
					print '&nbsp;';
				}
				print '</td>';
			}
			if (array_key_exists('c.intitule', $arrayfields) && ! empty($arrayfields['c.intitule']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('s.intitule_custo', $arrayfields) && ! empty($arrayfields['s.intitule_custo']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('c.ref', $arrayfields) && ! empty($arrayfields['c.ref']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('c.ref_interne', $arrayfields) && ! empty($arrayfields['c.ref_interne']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('s.type_session', $arrayfields) && ! empty($arrayfields['s.type_session']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('s.dated', $arrayfields) && ! empty($arrayfields['s.dated']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('s.datef', $arrayfields) && ! empty($arrayfields['s.datef']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('dicstatus.intitule', $arrayfields) && ! empty($arrayfields['dicstatus.intitule']['checked'])) {
				print '<td></td>';
			}
			if (! empty($arrayfields['s.status_before_archive']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('p.ref_interne', $arrayfields) && ! empty($arrayfields['p.ref_interne']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('s.sell_price', $arrayfields) && ! empty($arrayfields['s.sell_price']['checked'])) {
				print '<td name="margininfolineb1' . $line->rowid . '" ></td>';
			}
			if (array_key_exists('AgfAmoutHTHF', $arrayfields) && ! empty($arrayfields['AgfAmoutHTHF']['checked'])) {
				print '<td name="margininfolineb2' . $line->rowid . '" ></td>';
			}
			if (array_key_exists('s.cost_trainer', $arrayfields) && ! empty($arrayfields['s.cost_trainer']['checked'])) {
				print '<td name="margininfolineb3' . $line->rowid . '" ></td>';
			}
			if (array_key_exists('AgfCostOther', $arrayfields) && ! empty($arrayfields['AgfCostOther']['checked'])) {
				print '<td name="margininfolineb4' . $line->rowid . '" ></td>';
			}
			if (array_key_exists('AgfFactAmount', $arrayfields) && ! empty($arrayfields['AgfFactAmount']['checked'])) {
				print '<td name="margininfolineb5' . $line->rowid . '" ></td>';
			}
			if (array_key_exists('AgfFactAmountHT', $arrayfields) && ! empty($arrayfields['AgfFactAmountHT']['checked'])) {
				print '<td name="margininfolineb6' . $line->rowid . '" ></td>';
			}
			if (array_key_exists('AgfMargin', $arrayfields) && ! empty($arrayfields['AgfMargin']['checked'])) {
				print '<td name="margininfolineb7' . $line->rowid . '" ></td>';
			}
			if (array_key_exists('s.sell_price_planned', $arrayfields) && ! empty($arrayfields['s.sell_price_planned']['checked'])) {
				print '<td name="margininfolineb8' . $line->rowid . '" ></td>';
			}
			if (array_key_exists('AgfCostOtherPlanned', $arrayfields) && ! empty($arrayfields['AgfCostOtherPlanned']['checked'])) {
				print '<td name="margininfolineb9' . $line->rowid . '" ></td>';
			}
			if (array_key_exists('s.cost_trainer_planned', $arrayfields) && ! empty($arrayfields['s.cost_trainer_planned']['checked'])) {
				print '<td name="margininfolineb10' . $line->rowid . '" ></td>';
			}
			if (array_key_exists('AgfMarginPlanned', $arrayfields) && ! empty($arrayfields['AgfMarginPlanned']['checked'])) {
				print '<td name="margininfolineb111' . $line->rowid . '" ></td>';
			}
			if (array_key_exists('s.nb_stagiaire', $arrayfields) && ! empty($arrayfields['s.nb_stagiaire']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('s.duree_session', $arrayfields) && ! empty($arrayfields['s.duree_session']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('s.notes', $arrayfields) && ! empty($arrayfields['s.notes']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('s.fk_socpeople_presta', $arrayfields) && ! empty($arrayfields['s.fk_socpeople_presta']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('s.fk_soc_employer', $arrayfields) && ! empty($arrayfields['s.fk_soc_employer']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('s.fk_soc_requester', $arrayfields) && ! empty($arrayfields['s.fk_soc_requester']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('AgfListParticipantsStatus', $arrayfields) && ! empty($arrayfields['AgfListParticipantsStatus']['checked'])) {
				print '<td></td>';
			}
			if (array_key_exists('AgfSheduleBillingState', $arrayfields) && ! empty($arrayfields['AgfSheduleBillingState']['checked'])) {
			    print '<td></td>';
			}
			if (array_key_exists('s.fk_product', $arrayfields) && ! empty($arrayfields['s.fk_product']['checked'])) {
				print '<td></td>';
			}

			// Extra fields
			if (is_array($extrafields->attributes[$agf_session->table_element]['label']) && count($extrafields->attributes[$agf_session->table_element]['label']) > 0) {
				foreach ($extrafields->attributes[$agf_session->table_element]['label'] as $key=>$label) {
					if (! empty($arrayfields["ef." . $key]['checked'])) {
						print '<td></td>';
					}
				}
			}

			// Action column
			print '<td>&nbsp;</td>';
			print "</tr>\n";
		}
		$oldid = $line->rowid;

		$i ++;
	}

	print '<tr class="liste_total" name="margininfototal" >';
	print '<td>' . $langs->trans('Total') . '</td>';
	if (array_key_exists('s.rowid', $arrayfields) && ! empty($arrayfields['s.rowid']['checked']) && array_key_exists('s.ref', $arrayfields) && ! empty($arrayfields['s.ref']['checked'])) {
		print '<td></td>';
	}
	/*if (array_key_exists('s.ref', $arrayfields) && ! empty($arrayfields['s.ref']['checked'])) {
	 print '<td></td>';
	 }*/
	if (array_key_exists('so.nom', $arrayfields) && ! empty($arrayfields['so.nom']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('f.rowid', $arrayfields) && ! empty($arrayfields['f.rowid']['checked'])) {
		print '<td></td>';
	}
	if (!empty($arrayfields['sale.fk_user_com']['checked']))
	{
		print '<td></td>';
	}
	if (array_key_exists('c.intitule', $arrayfields) && ! empty($arrayfields['c.intitule']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('s.intitule_custo', $arrayfields) && ! empty($arrayfields['s.intitule_custo']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('c.ref', $arrayfields) && ! empty($arrayfields['c.ref']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('c.ref_interne', $arrayfields) && ! empty($arrayfields['c.ref_interne']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('s.type_session', $arrayfields) && ! empty($arrayfields['s.type_session']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('s.dated', $arrayfields) && ! empty($arrayfields['s.dated']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('s.datef', $arrayfields) && ! empty($arrayfields['s.datef']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('dicstatus.intitule', $arrayfields) && ! empty($arrayfields['dicstatus.intitule']['checked'])) {
		print '<td></td>';
	}
	if (! empty($arrayfields['s.status_before_archive']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('p.ref_interne', $arrayfields) && ! empty($arrayfields['p.ref_interne']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('s.sell_price', $arrayfields) && ! empty($arrayfields['s.sell_price']['checked'])) {
		print '<td nowrap="nowrap">' . price($total_sellprice, 0, '', 1, - 1, - 1, 'auto') . '</td>';
	}
	if (array_key_exists('AgfAmoutHTHF', $arrayfields) && ! empty($arrayfields['AgfAmoutHTHF']['checked'])) {
		print '<td nowrap="nowrap">' . price($total_propal_hthf, 0, '', 1, - 1, - 1, 'auto') . '</td>';
	}
	if (array_key_exists('s.cost_trainer', $arrayfields) && ! empty($arrayfields['s.cost_trainer']['checked'])) {
		print '<td nowrap="nowrap">' . price($total_costtrainer, 0, '', 1, - 1, - 1, 'auto') . '</td>';
	}
	if (array_key_exists('AgfCostOther', $arrayfields) && ! empty($arrayfields['AgfCostOther']['checked'])) {
		print '<td nowrap="nowrap">' . price($total_costother, 0, '', 1, - 1, - 1, 'auto') . '</td>';
	}
	if (array_key_exists('AgfFactAmount', $arrayfields) && ! empty($arrayfields['AgfFactAmount']['checked'])) {
		print '<td nowrap="nowrap">' . price($total_invoice_hthf, 0, '', 1, - 1, - 1, 'auto') . '</td>';
	}
	if (array_key_exists('AgfFactAmountHT', $arrayfields) && ! empty($arrayfields['AgfFactAmountHT']['checked'])) {
		print '<td nowrap="nowrap">' . price($total_invoice_ht, 0, '', 1, - 1, - 1, 'auto') . '</td>';
	}
	if (array_key_exists('AgfMargin', $arrayfields) && ! empty($arrayfields['AgfMargin']['checked'])) {
		print '<td nowrap="nowrap">' . price($total_margin, 0, '', 1, - 1, - 1, 'auto') . '(' . $total_percentmargin . ')' . '</td>';
	}
	if (array_key_exists('s.sell_price_planned', $arrayfields) && ! empty($arrayfields['s.sell_price_planned']['checked'])) {
		print '<td nowrap="nowrap">' . price($total_sellprice_planned, 0, '', 1, - 1, - 1, 'auto') . '</td>';
	}
	if (array_key_exists('AgfCostOtherPlanned', $arrayfields) && ! empty($arrayfields['AgfCostOtherPlanned']['checked'])) {
		print '<td nowrap="nowrap">' . price($total_costother_planned, 0, '', 1, - 1, - 1, 'auto') . '</td>';
	}
	if (array_key_exists('s.cost_trainer_planned', $arrayfields) && ! empty($arrayfields['s.cost_trainer_planned']['checked'])) {
		print '<td nowrap="nowrap">' . price($total_costtrainer_planned, 0, '', 1, - 1, - 1, 'auto') . '</td>';
	}
	if (array_key_exists('AgfMarginPlanned', $arrayfields) && ! empty($arrayfields['AgfMarginPlanned']['checked'])) {
		print '<td nowrap="nowrap">' . price($total_margin_planned, 0, '', 1, - 1, - 1, 'auto') . '(' . $total_percentmargin_planned . ')' . '</td>';
	}
	if (array_key_exists('s.nb_stagiaire', $arrayfields) && ! empty($arrayfields['s.nb_stagiaire']['checked'])) {
		print '<td>' . $total_trainee . '</td>';
	}
	if (array_key_exists('s.duree_session', $arrayfields) && ! empty($arrayfields['s.duree_session']['checked'])) {
		print '<td>' . $total_duration . '</td>';
	}
	if (array_key_exists('s.notes', $arrayfields) && ! empty($arrayfields['s.notes']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('s.fk_socpeople_presta', $arrayfields) && ! empty($arrayfields['s.fk_socpeople_presta']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('s.fk_soc_employer', $arrayfields) && ! empty($arrayfields['s.fk_soc_employer']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('s.fk_soc_requester', $arrayfields) && ! empty($arrayfields['s.fk_soc_requester']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('AgfListParticipantsStatus', $arrayfields) && ! empty($arrayfields['AgfListParticipantsStatus']['checked'])) {
		print '<td></td>';
	}
	if (array_key_exists('AgfSheduleBillingState', $arrayfields) && ! empty($arrayfields['AgfSheduleBillingState']['checked'])) {
	    print '<td></td>';
	}
	if (array_key_exists('s.fk_product', $arrayfields) && ! empty($arrayfields['s.fk_product']['checked'])) {
		print '<td></td>';
	}

	// Extra fields
	if (is_array($extrafields->attributes[$agf_session->table_element]['label']) && count($extrafields->attributes[$agf_session->table_element]['label']) > 0) {
		foreach ($extrafields->attributes[$agf_session->table_element]['label'] as $key=>$label) {
			if (! empty($arrayfields["ef." . $key]['checked'])) {
				print '<td></td>';
			}
		}
	}

	// Action column
	print '<td>&nbsp;</td>';
	print "</tr>\n";
	print "</table>";
	print "</div>";
} else {
	setEventMessage($agf->error, 'errors');
}

llxFooter();
$db->close();
