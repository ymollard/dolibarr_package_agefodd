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
 * \file agefodd/contact/session_list.php
 * \ingroup agefodd
 * \brief list of session
 */
$res = @include('../../main.inc.php'); // For root directory
if (!$res)  $res = @include('../../../main.inc.php'); // For "custom" directory
if (!$res)  die('Include of main fails');

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/contact.lib.php';
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/agefodd/class/html.formagefodd.class.php');

// Security check
if (!$user->rights->agefodd->lire)
	accessforbidden();

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'agfcontactsessionlist';

// search criteria
$search_ref = GETPOST('search_ref', 'alpha');
$search_company_name = GETPOST('search_company_name', 'alpha');
$search_training_ref = GETPOST('search_training_ref', 'alpha');
$search_type_session = GETPOST('search_type_session', 'int');
$search_dated_start = dol_mktime(0, 0, 0, GETPOST('search_dated_start_month', 'int'), GETPOST('search_dated_start_day', 'int'), GETPOST('search_dated_start_year', 'int'));
$search_dated_end = dol_mktime(23, 59, 59, GETPOST('search_dated_end_month', 'int'), GETPOST('search_dated_end_day', 'int'), GETPOST('search_dated_end_year', 'int'));
$search_datef_start = dol_mktime(0, 0, 0, GETPOST('search_datef_start_month', 'int'), GETPOST('search_datef_start_day', 'int'), GETPOST('search_datef_start_year', 'int'));
$search_datef_end = dol_mktime(23, 59, 59, GETPOST('search_datef_end_month', 'int'), GETPOST('search_datef_end_day', 'int'), GETPOST('search_datef_end_year', 'int'));
$search_trainee_status_in_session = GETPOST('search_trainee_status_in_session', 'int');
$search_notes = GETPOST('search_notes', 'alpha');
$search_status = GETPOST('search_status', 'int');

$optioncss = GETPOST('optioncss', 'alpha');
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
if (!$sortorder)    $sortorder = 'DESC';
if (!$sortfield)    $sortfield = 's.dated';
if (empty($page) || $page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Agsession($db);
$hookmanager->initHooks(array('agfcontactsessionlist'));

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

$arrayfields = array(
    's.ref'                => array('label' => 'Ref', 'checked' => 1, 'position' => 10),
    'so.nom'               => array('label' => 'Company', 'checked' => 1, 'position' => 20),
    'c.ref'                => array('label' => 'AgfTrainingRef', 'checked' => 1, 'position' => 30),
    's.type_session'       => array('label' => 'AgfFormTypeSession', 'checked' => 1, 'position' => 40),
    's.dated'              => array('label' => 'AgfDateDebut', 'checked' => 1, 'position' => 50),
    's.datef'              => array('label' => 'AgfDateFin', 'checked' => 1, 'position' => 51),
    'ss.status_in_session' => array('label' => 'AgfTraineeStatus', 'checked' => 1, 'position' => 60),
    's.notes'              => array('label' => 'AgfNotes', 'checked' => 1, 'position' => 70),
    's.status'             => array('label' => 'Status', 'checked' => 1, 'position' => 1000),
);
// extra fields
// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0)
{
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key=>$label)
	{
		// skip separation
		if ($extrafields->attributes[$object->table_element]['type'][$key] == 'separate'){
			continue;
		}
		// skip hidden
		if(!empty($extrafields->attributes[$object->table_element]['hidden'][$key])){
			continue;
		}

		$visibility = 1;
		if ($visibility && isset($extrafields->attributes[$object->table_element]['list'][$key]))
		{
			$visibility = dol_eval($extrafields->attributes[$object->table_element]['list'][$key], 1);
		}
		if (abs($visibility) != 1 && abs($visibility) != 2 && abs($visibility) != 5) continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list

		$perms = 1;
		if ($perms && isset($extrafields->attributes[$object->table_element]['perms'][$key]))
		{
			$perms = dol_eval($extrafields->attributes[$object->table_element]['perms'][$key], 1);
		}
		if (empty($perms)) continue;

		// Load language if required
		if (!empty($extrafields->attributes[$object->table_element]['langfile'][$key]))
			$langs->load($extrafields->attributes[$object->table_element]['langfile'][$key]);

		$arrayfields["ef." . $key] = array(
			'label' => $langs->trans($label),
			'checked' => $extrafields->attributes[$object->table_element]['list'][$key],
			'position' => $extrafields->attributes[$object->table_element]['pos'][$key]
		);
	}
}

$contact = new Contact($db);
$res = $contact->fetch($id, $user);
if ($res < 0) {
    setEventMessage($contact->error, 'errors');
    exit();
}
$res = $contact->fetch_optionals();

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $contact, $action);
if ($reshook < 0)   setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    // selection of new fields
    include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

    // do we click on purge search criteria ?
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // all tests are required to be compatible with all browsers
    {
        $search_ref = '';
        $search_company_name = '';
        $search_training_ref = '';
        $search_type_session = '';
        $search_dated_start = '';
        $search_dated_end = '';
        $search_datef_start = '';
        $search_datef_end = '';
        $search_trainee_status_in_session = '';
        $search_notes = '';
        $search_status = '';
    }
}


/*
 * View
 */

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$sessionTrainee = new Agefodd_session_stagiaire($db);

$title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans('Contacts') : $langs->trans('ContactsAddresses'));
llxHeader('', $title);

$head = contact_prepare_head($contact);

dol_fiche_head($head, 'tabAgefoddSessionList', $title, -1, 'contact');

$linkback = '<a href="' . DOL_URL_ROOT . '/contact/list.php?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';

$morehtmlref = '<div class="refidno">';
if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
    $objsoc = new Societe($db);
    $objsoc->fetch($contact->socid);
    // Thirdparty
    $morehtmlref .= $langs->trans('ThirdParty') . ' : ';
    if ($objsoc->id > 0)
        $morehtmlref .= $objsoc->getNomUrl(1);
    else
        $morehtmlref .= $langs->trans("ContactNotLinkedToCompany");
}
$morehtmlref .= '</div>';

dol_banner_tab($contact, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

dol_fiche_end();
print '<br>';

$sql  = "SELECT";
$sql .= " s.rowid";
$sql .= ", s.ref";
$sql .= ", s.color";
$sql .= ", s.fk_soc";
$sql .= ", so.nom as company_name";
$sql .= ", c.ref as training_ref";
$sql .= ", s.type_session";
$sql .= ", s.dated";
$sql .= ", s.datef";
$sql .= ", ss.status_in_session as trainee_status_in_session";
$sql .= ", s.notes";
$sql .= ", s.status";
$sql .= ", sst.intitule as session_status_label";
// add fields from extrafields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $label) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef." . $key . ' as options_' . $key : '');
	}
}
// add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);
$sql .= $hookmanager->resPrint;

$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire as trainee";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss ON ss.fk_stagiaire = trainee.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session as s ON s.rowid = ss.fk_session_agefodd";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_extrafields as ef ON ef.fk_object = s.rowid";
}
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = s.fk_soc";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c ON c.rowid = s.fk_formation_catalogue";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as sst ON sst.rowid = s.status";

$sql .= " WHERE s.entity IN (" . getEntity('agefodd') . ")";
$sql .= " AND trainee.fk_socpeople = " . $contact->id;
if ($search_ref)                                                $sql .= natural_search('s.ref', $search_ref);
if ($search_company_name)                                       $sql .= natural_search('so.nom', $search_company_name);
if ($search_training_ref)                                       $sql .= natural_search('c.ref', $search_training_ref);
if ($search_type_session != '' && $search_type_session != - 1) {
    $sql .= natural_search('s.type_session', $search_type_session);
}
if ($search_dated_start)                                        $sql .= " AND s.dated >= '" . $db->idate($search_dated_start) . "'";
if ($search_dated_end)                                          $sql .= " AND s.dated <= '" . $db->idate($search_dated_end) . "'";
if ($search_datef_start)      	                                $sql .= " AND s.datef >= '" . $db->idate($search_datef_start) . "'";
if ($search_datef_end)                                          $sql .= " AND s.datef <= '" . $db->idate($search_datef_end) . "'";
if ($search_trainee_status_in_session != '' && $search_trainee_status_in_session != -1) {
    $sql .= natural_search('ss.status_in_session', $search_trainee_status_in_session);
}
if ($search_notes)                                              $sql .= natural_search('s.notes', $search_notes);
if ($search_status)                                             $sql .= natural_search('s.status', $search_status);
// add where from extra fields
include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
// add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

// count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
        $page = 0;
        $offset = 0;
    }
}

$sql .= $db->plimit($limit+1, $offset);

$resql = $db->query($sql);
if (!$resql) {
    setEventMessage($db->lasterror(), 'errors');
} else {
    $num = $db->num_rows($resql);

    $param = 'id=' . $id;
    if ($search_ref != '')              $param .= '&search_ref=' . urlencode($search_ref);
    if ($search_company_name != '')     $param .= '&search_company_name=' . urlencode($search_company_name);
    if ($search_training_ref != '')     $param .= '&search_training_ref=' . urlencode($search_training_ref);
    if ($search_type_session != '' && $search_type_session != - 1) {
        $param .= '&search_type_session=' . $search_type_session;
    }
    if (!empty($search_dated_start)) {
        $param .= '&search_dated_start_month=' . dol_print_date($search_dated_start, '%m') . '&search_dated_start_day=' . dol_print_date($search_dated_start, '%d') . '&search_dated_start_year=' . dol_print_date($search_dated_start, '%Y');
    }
    if (!empty($search_dated_end)) {
        $param .= '&search_dated_end_month=' . dol_print_date($search_dated_end, '%m') . '&search_dated_end_day=' . dol_print_date($search_dated_end, '%d') . '&search_dated_end_year=' . dol_print_date($search_dated_end, '%Y');
    }
    if (!empty($search_datef_start)) {
        $param .= '&search_datef_start_month=' . dol_print_date($search_datef_start, '%m') . '&search_datef_start_day=' . dol_print_date($search_datef_start, '%d') . '&search_datef_start_year=' . dol_print_date($search_datef_start, '%Y');
    }
    if (!empty($search_datef_end)) {
        $param .= '&search_datef_end_month=' . dol_print_date($search_datef_end, '%m') . '&search_datef_end_day=' . dol_print_date($search_dated_end, '%d') . '&search_datef_end_year=' . dol_print_date($search_datef_end, '%Y');
    }
    if ($search_trainee_status_in_session != '')    $param .= '&search_trainee_status_in_session=' . urlencode($search_trainee_status_in_session);
    if ($search_notes != '')                        $param .= '&search_notes=' . urlencode($search_notes);
    if ($search_status != '')                       $param .= '&search_status=' . urlencode($search_status);
    if ($optioncss != '')                           $param .= '&optioncss=' . urlencode($optioncss);
    // add $param from extra fields
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';

    print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="formfilter" autocomplete="off">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="' . $optioncss . '" />';
    print '<input type="hidden" name="token" value="'. newToken() . '" />';
    print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="id" value="' . $id . '" />';
    print '<input type="hidden" name="sortfield" value="' . $sortfield .'" />';
    print '<input type="hidden" name="sortorder" value="' . $sortorder .'" />';
    print '<input type="hidden" name="page" value="' . $page . '" />';
    print '<input type="hidden" name="contextpage" value="' . $contextpage . '" />';

    print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_generic.png', 0, '', '', $limit);

    // filters
    $moreforfilter = '';

    $varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
    $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// this also change content of $arrayfields

    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste' . ($moreforfilter ? ' listwithfilterbefore' : '') . '">';

    // fields title search
    print '<tr class="liste_titre_filter">';
    // session ref
    if (!empty($arrayfields['s.ref']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat searchstring maxwidth75imp" type="text" name="search_ref" size="10" value="' . dol_escape_htmltag($search_ref) . '" />';
        print '</td>';
    }
    // session company name
    if (!empty($arrayfields['so.nom']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat searchstring maxwidth75imp" type="text" name="search_company_name" size="10" value="' . dol_escape_htmltag($search_company_name) . '" />';
        print '</td>';
    }
    // training ref
    if (!empty($arrayfields['c.ref']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat searchstring maxwidth75imp" type="text" name="search_training_ref" size="10" value="' . dol_escape_htmltag($search_training_ref) . '" />';
        print '</td>';
    }
    // session type
    if (!empty($arrayfields['s.type_session']['checked'])) {
        print '<td class="liste_titre">';
        print $formAgefodd->select_type_session('search_type_session', $search_type_session, 1);
        print '</td>';
    }
    // session start date
    if (!empty($arrayfields['s.dated']['checked'])) {
        print '<td class="liste_titre center">';
        print '<div class="nowrap">';
        print $langs->trans('From') . ' ';
        print $form->selectDate($search_dated_start?$search_dated_start:-1, 'search_dated_start_', 0, 0, 1);
        print '</div>';
        print '<div class="nowrap">';
        print $langs->trans('to') . ' ';
        print $form->selectDate($search_dated_end?$search_dated_end:-1, 'search_dated_end_', 0, 0, 1);
        print '</div>';
        print '</td>';
    }
    // session end date
    if (!empty($arrayfields['s.datef']['checked'])) {
        print '<td class="liste_titre center">';
        print '<div class="nowrap">';
        print $langs->trans('From') . ' ';
        print $form->selectDate($search_datef_start?$search_datef_start:-1, 'search_datef_start_', 0, 0, 1);
        print '</div>';
        print '<div class="nowrap">';
        print $langs->trans('to') . ' ';
        print $form->selectDate($search_datef_end?$search_datef_end:-1, 'search_datef_end_', 0, 0, 1);
        print '</div>';
        print '</td>';
    }
    // trainee status in session
    if (!empty($arrayfields['ss.status_in_session']['checked'])) {
        print '<td class="liste_titre">';
        print $formAgefodd->select_stagiaire_session_status('search_trainee_status_in_session', $search_trainee_status_in_session, null, 1);
        print '</td>';
    }
    // session notes
    if (!empty($arrayfields['s.notes']['checked'])) {
        print '<td class="liste_titre">';
        print '<input class="flat searchstring maxwidth75imp" type="text" name="search_notes" size="10" value="' . dol_escape_htmltag($search_notes) . '" />';
        print '</td>';
    }
    // extra fields
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';
    // fields from hook
    $parameters = array('arrayfields' => $arrayfields);
    $reshook = $hookmanager->executeHooks('printFieldListOption', $parameters);
    print $hookmanager->resPrint;
    // session status
    if (!empty($arrayfields['s.status']['checked'])) {
        print '<td class="liste_titre">';
        print $formAgefodd->select_session_status($search_status, 'search_status', '', 1, 0, array(), '', false);
        print '</td>';
    }
    // action column
    print '<td class="liste_titre right">';
    $searchpicto = $form->showFilterButtons();
    print $searchpicto;
    print '</td>';
    print '</tr>';

    print '<tr class="liste_titre">';
    if (!empty($arrayfields['s.ref']['checked']))                   print_liste_field_titre($arrayfields['s.ref']['label'], $_SERVER ['PHP_SELF'], 's.ref', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['so.nom']['checked']))                  print_liste_field_titre($arrayfields['so.nom']['label'], $_SERVER ['PHP_SELF'], 'so.nom', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['c.ref']['checked']))                   print_liste_field_titre($arrayfields['c.ref']['label'], $_SERVER ['PHP_SELF'], 'c.ref', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['s.type_session']['checked']))          print_liste_field_titre($arrayfields['s.type_session']['label'], $_SERVER ['PHP_SELF'], 's.type_session', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['s.dated']['checked']))                 print_liste_field_titre($arrayfields['s.dated']['label'], $_SERVER ['PHP_SELF'], 's.dated', '', $param, 'align="center"', $sortfield, $sortorder);
    if (!empty($arrayfields['s.datef']['checked']))                 print_liste_field_titre($arrayfields['s.datef']['label'], $_SERVER ['PHP_SELF'], 's.datef', '', $param, 'align="center"', $sortfield, $sortorder);
    if (!empty($arrayfields['ss.status_in_session']['checked']))    print_liste_field_titre($arrayfields['ss.status_in_session']['label'], $_SERVER ['PHP_SELF'], 'ss.status_in_session', '', $param, '', $sortfield, $sortorder);
    if (!empty($arrayfields['s.notes']['checked']))                 print_liste_field_titre($arrayfields['s.notes']['label'], $_SERVER ['PHP_SELF'], 's.notes', '', $param, '', $sortfield, $sortorder);
    // extra fields
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';
    // hook fields
    $parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
    $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters);
    print $hookmanager->resPrint;
    if (!empty($arrayfields['s.status']['checked']))                print_liste_field_titre($arrayfields['s.status']['label'], $_SERVER ['PHP_SELF'], 's.status', '', $param, '', $sortfield, $sortorder);
    print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
    print '</tr>';

    print '</form>';

    $i = 0;
    $totalarray = array();
    while ($i < min($num, $limit)) {
        $obj = $db->fetch_object($resql);

        print '<tr class="oddeven">';
        // session ref
        if (!empty($arrayfields['s.ref']['checked'])) {
            $couleur_rgb = agf_hex2rgb($obj->color);
            $color_a = '';
            if ($obj->color && ((($couleur_rgb[0] * 299) + ($couleur_rgb[1] * 587) + ($couleur_rgb[2] * 114)) / 1000) < 125)
                $color_a = ' style="color: #FFFFFF;"';
            if ($conf->global->AGF_NEW_BROWSER_WINDOWS_ON_LINK) {
                $target = ' target="_blank" ';
            } else {
                $target = '';
            }
            print '<td style="background: #' . $obj->color . '"><a' . $color_a . ' href="' . dol_buildpath('/agefodd/session/card.php?id=' . $obj->rowid, 1) . '"' . $target . '>' . img_object($langs->trans('AgfShowDetails'), 'service') . ' ' . $obj->ref . '</a></td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // session company name
        if (!empty($arrayfields['so.nom']['checked'])) {
            print '<td>';
            if ($obj->fk_soc > 0) {
                $sessionCompany = new Societe($db);
                $sessionCompany->fetch($obj->fk_soc);
                print $sessionCompany->getNomURL(1);
            } else {
                print '&nbsp;';
            }
            print '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // training ref
        if (!empty($arrayfields['c.ref']['checked'])) {
            print '<td>' . $obj->training_ref . '</td>';
        }
        // session type
        if (!empty($arrayfields['s.type_session']['checked'])) {
            print '<td>' . ($obj->type_session ? $langs->trans('AgfFormTypeSessionInter') : $langs->trans('AgfFormTypeSessionIntra')) . '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // session start date
        if (!empty($arrayfields['s.dated']['checked'])) {
            print '<td>' . dol_print_date($db->jdate($obj->dated), 'daytext') . '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // session end date
        if (!empty($arrayfields['s.datef']['checked'])) {
            print '<td>' . dol_print_date($db->jdate($obj->datef), 'daytext') . '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // trainee status in session
        if (!empty($arrayfields['ss.status_in_session']['checked'])) {
            print '<td>' . $sessionTrainee->LibStatut($obj->trainee_status_in_session, 4) . '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // session notes
        if (!empty($arrayfields['s.notes']['checked'])) {
            print '<td>' . $obj->notes . '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // extra fields
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';
        // Fields from hook
        $parameters = array('arrayfields' => $arrayfields, 'obj' => $obj);
        $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters);
        print $hookmanager->resPrint;
        // session status label
        if (!empty($arrayfields['s.status']['checked'])) {
            print '<td>' . stripslashes($obj->session_status_label) . '</td>';
            if (!$i) $totalarray['nbfield']++;
        }
        // action column
        print '<td class="nowrap center">';
        print '</td>';
        if (!$i) $totalarray['nbfield']++;

        print '</tr>';
        $i++;
    }

    $db->free($resql);

    $parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
    $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters);
    print $hookmanager->resPrint;

    print '</table>';
    print '</div>';

    print '</form>';
}

llxFooter();
$db->close();
