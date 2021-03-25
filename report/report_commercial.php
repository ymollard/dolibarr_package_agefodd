<?php
/*
 * Copyright (C) 2019 Marc de Lima Lucio <marc@atm-consulting.fr>
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
 * \file		/agefodd/report/report_commercial.php
 * \brief		report part
 * (Agefodd).
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agsession.class.php');
require_once ('../lib/agefodd.lib.php');
require_once ('../class/html.formagefodd.class.php');
require_once ('../class/agefodd_formateur.class.php');
require_once ('../class/report_commercial.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$search_year = GETPOST('search_year','int');
$search_nb_years = GETPOST('search_nb_years','int');
$search_accounting_date = GETPOST('search_accounting_date', 'none');
if(empty($search_accounting_date))  $search_accounting_date = 'invoice';
$search_sale = GETPOST('search_sale', 'int');
$search_type_session = GETPOST("search_type_session", 'int');
$search_parent = GETPOST('search_parent', 'int');
if ($search_parent == - 1)
	$search_parent = '';
$search_soc_requester = GETPOST('search_soc_requester', 'none');
$search_soc = GETPOST('search_soc', 'array');
$search_invoice_status = GETPOST('search_invoice_status', 'none');
$search_only_active = $action == 'builddoc' ? isset($_REQUEST['search_only_active']) : true;
$search_created_during_selected_period = isset($_REQUEST['search_created_during_selected_period']);
$search_client_prospect = GETPOST('search_client_prospect', 'array');
if($action != 'builddoc' && empty($search_client_prospect))
{
	$search_client_prospect = array();

	if(empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))
	{
		$search_client_prospect[] = 1;

		if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTSCUSTOMERS))
		{
			$search_client_prospect[] = 3;
		}
	}
}
$search_detail = GETPOST('search_detail', 'none');

//$ts_logistique = GETPOST('options_ts_logistique', 'int');
//$search_session_status=GETPOST('search_session_status','array');

$modelexport = GETPOST('modelexport', 'alpha');
$lang_id = GETPOST('lang_id', 'none');

$langs->load('agefodd@agefodd');
$langs->load('bills');
$langs->load("exports");

$extrajs = array (
		'/agefodd/includes/multiselect/js/ui.multiselect.js'
);
$extracss = array (
		'/agefodd/includes/multiselect/css/ui.multiselect.css',
		'/agefodd/css/agefodd.css'
);

llxHeader('', $langs->trans('AgfMenuReportCommercial'), '', '', '', '', $extrajs, $extracss);
$upload_dir = $conf->agefodd->dir_output . '/report/commercial/';

$agf = new Agsession($db);

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

$filter = array(
	'startyear' => $search_year
	, 'nbyears' => $search_nb_years
	, 'accounting_date' => $search_accounting_date
);

if ($search_type_session != '' && $search_type_session != - 1)
{
	$filter['s.type_session'] = $search_type_session;
}

if (! empty($search_sale))
{
	$filter['sale.fk_user'] = $search_sale;
}

if (! empty($search_soc))
{
	$filter['soc.rowid'] = $search_soc;
}

if(! empty($search_client_prospect))
{
	$filter['s.client'] = $search_client_prospect;
}

if(! empty($search_only_active))
{
	$filter['s.active'] = true;
}

if(! empty($search_created_during_selected_period))
{
	$filter['s.created_during_selected_period'] = true;
}

if(! empty($search_detail))
{
	$filter['detail'] = true;
}

/*
 * Actions
 */
if ($action == 'builddoc')
{
	if(empty($filter['s.client']))
	{
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('ProspectCustomer')), 'errors');
	}
	else
	{
		$outputlangs = $langs;
		$newlang = $lang_id;
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->client->default_lang;
		if (! empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}

		$outputlangs->load('agefodd@agefodd');

		$report = new ReportCommercial($db, $outputlangs);

		$file_sub_title = $report->getSubTitlFileName($filter);
		$report->file = $upload_dir . 'reportcommercial' . $file_sub_title . '.xlsx';


		$result = $report->write_file($filter);
		if ($result < 0) {
			setEventMessage($report->error, 'errors');
		} elseif ($result == 0) {
			setEventMessage($langs->trans("NoData"), 'warnings');
		} else {
			setEventMessage($langs->trans("FileSuccessfullyBuilt"));
		}
	}
}
elseif ($action == 'remove_file')
{
	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

	$langs->load("other");
	$file = $upload_dir . '/' . GETPOST('file', 'none');
	$ret = dol_delete_file($file, 0, 0, 0, '');
	if ($ret)
		setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile', 'none')));
	else
		setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile', 'none')), 'errors');
	$action = '';
}

/**
 * View
 */

$report = new ReportCommercial($db, $langs);

$head = agf_report_commercial_prepare_head(http_build_query($_REQUEST));
dol_fiche_head($head, 'card', $langs->trans("AgfMenuReportCommercial"), 0, 'bill');



$TClientProspectChoices = array(0 => $langs->trans('NorProspectNorCustomer'));

if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
{
	$TClientProspectChoices[2] = $langs->trans('Prospect');
}

if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))
{
	$TClientProspectChoices[1] = $langs->trans('Customer');

	if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTSCUSTOMERS))
	{
		$TClientProspectChoices[3] = $langs->trans('ProspectCustomer');
	}
}


print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="search_form">' . "\n";

print '<table class="border" width="100%">';


print '<tr>';
print '<td class="fieldrequired">' . $langs->trans('AgfReportCommercialBaseYear').'</td>';
print '<td>';
print $formother->selectyear($search_year ? $search_year : -1, 'search_year', 0, 15, 0);
print '</td>';
print '</tr>';

$TYears = range(1, 15);
$TYears = array_combine($TYears, $TYears);

print '<tr>';
print '<td class="fieldrequired">' . $langs->trans('AgfReportCommercialNbYears').'</td>';
print '<td>';
print $form->selectarray('search_nb_years', $TYears, ! empty($search_nb_years) ? $search_nb_years : 4);
print '</td>';
print '</tr>';

print '<tr>';
print '<td class="fieldrequired">' . $langs->trans('AgfReportCommercialInvoiceAccountingDate').'</td>';
print '<td>';
print $form->selectarray('search_accounting_date', $report->T_ACCOUNTING_DATE_CHOICES, $search_accounting_date, 0, 0, 0, '', 1);
print '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('AgfReportCommercialSessionType') . '</td>';
print '<td>' . $formAgefodd->select_type_session('search_type_session', $search_type_session, 1) . '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('SalesRepresentative') . '</td>';
print '<td>' . $formother->select_salesrepresentatives($search_sale, 'search_sale', $user) . '</td>';
print '</tr>';


$TCompanies = $report->fetch_companies(array('s.client' => array_keys($TClientProspectChoices))); // Filtre sur client obligatoire => on sélectionne tout

$TCompaniesMultiSelect = array_map(function($elem)
{
	$out = $elem->nom;

	if(! empty($elem->code_client))
	{
		$out .= ' - ' . $elem->code_client;
	}

	return $out;
}, $TCompanies);


print '<tr>';
print '<td>' . $langs->trans('Companies') . '</td>';
print '<td>' . Form::multiselectarray('search_soc', $TCompaniesMultiSelect, ! empty($search_soc) ? $search_soc : array()) . '</td>';
print '</tr>';


print '<tr>';
print '<td class="fieldrequired">' . $langs->trans('ProspectCustomer') . '</td>';
print '<td>' . Form::multiselectarray('search_client_prospect', $TClientProspectChoices, $search_client_prospect) . '</td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('AgfReportCommercialOnlyActive') . '</td>';
print '<td><input type="checkbox" name="search_only_active" value="1"' . ($search_only_active ? ' checked' : '') . ' /></td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('AgfReportCommercialOnlyCreatedOnSelectedPeriod') . '</td>';
print '<td><input type="checkbox" name="search_created_during_selected_period" value="1"' . ($search_created_during_selected_period ? ' checked' : '') . ' /></td>';
print '</tr>';

print '<tr>';
print '<td>' . $langs->trans('AgfReportCommercialDetail') . '</td>';
print '<td><input type="checkbox" name="search_detail" value="1"' . ($search_detail ? ' checked' : '') . ' /></td>';
print '</tr>';


print '</table>' . "\n";

$liste = array (
	'excel2007' => 'Excel 2007'
);
dol_fiche_end();
print $formfile->showdocuments('export', '', $upload_dir, $_SERVER["PHP_SELF"], $liste, 1, (! empty($modelexport) ? $modelexport : 'excel2007'), 1, 0, 0, 150, 1);

// TODO : Hack to update link on document form because merge export is always link to export ...
echo '<script type="text/javascript">
		jQuery(document).ready(function () {
                    	jQuery(function() {
                        	$("a[data-ajax|=\'false\'][href*=\'export\'],a.documentdownload[href*=\'export\']") // data-ajax="false" before Dolibarr 6.0, class="documentdownload" after
								.each(function()
								   {
								      this.href = this.href.replace(/export/,
								         "agefodd");
									  this.href =this.href.replace(/file=/,
								         "file=/report/commercial/")
								   });
                        });
                    });
		</script>';

print '</form>' . "\n";

llxFooter();
$db->close();

