<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2017	Florian Henry	<florian.henry@open-concept.pro>
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
 * \file /agefodd/admin/admin_options.php
 * \ingroup agefodd
 * \brief agefood module setup page
 */
$res = @include "../../main.inc.php"; // For root directory
if (! $res) $res = @include "../../../main.inc.php"; // For "custom" directory
if (! $res) die("Include of main fails");

require_once '../class/agefodd_formation_catalogue.class.php';
require_once '../class/agefodd_session_admlevel.class.php';
require_once '../class/agefodd_calendrier.class.php';
require_once '../class/html.formagefodd.class.php';
require_once '../lib/agefodd.lib.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/class/html.formmail.class.php';

$langs->load("admin");
$langs->load('agefodd@agefodd');

if (! $user->rights->agefodd->admin && ! $user->admin)
    accessforbidden();

$action = GETPOST('action', 'alpha');

if ($action == 'setvarother') {

	$error= 0;
    if (empty($conf->use_javascript_ajax))
    {

        $logo_client = GETPOST('AGF_USE_LOGO_CLIENT', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_USE_LOGO_CLIENT', $logo_client, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $use_fac_without_order = GETPOST('AGF_USE_FAC_WITHOUT_ORDER', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_USE_FAC_WITHOUT_ORDER', $use_fac_without_order, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $use_dol_contact = GETPOST('AGF_CONTACT_DOL_SESSION', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_CONTACT_DOL_SESSION', $use_dol_contact, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $usesearch_contact = GETPOST('AGF_CONTACT_USE_SEARCH_TO_SELECT', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_CONTACT_USE_SEARCH_TO_SELECT', $usesearch_contact, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;




        $usesearch_stagstype = GETPOST('AGF_STAGTYPE_USE_SEARCH_TO_SELECT', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_STAGTYPE_USE_SEARCH_TO_SELECT', $usesearch_stagstype, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $usedolibarr_agenda = GETPOST('AGF_DOL_AGENDA', 'alpha');
        if ($usedolibarr_agenda && ! $conf->global->MAIN_MODULE_AGENDA) {
            setEventMessage($langs->trans("AgfAgendaModuleNedeed"), 'errors');
            $error ++;
        } else {
            $res = dolibarr_set_const($db, 'AGF_DOL_AGENDA', $usedolibarr_agenda, 'chaine', 0, '', $conf->entity);
        }
        if (! $res > 0)
            $error ++;

        $use_trainer_agenda = GETPOST('AGF_DOL_TRAINER_AGENDA', 'alpha');
        if ($use_trainer_agenda && ! $conf->global->MAIN_MODULE_AGENDA) {
            setEventMessage($langs->trans("AgfAgendaModuleNedeed"), 'errors');
            $error ++;
        } else {
            $res = dolibarr_set_const($db, 'AGF_DOL_TRAINER_AGENDA', $use_trainer_agenda, 'chaine', 0, '', $conf->entity);
        }
        if (! $res > 0)
            $error ++;

        $use_dol_company_name = GETPOST('MAIN_USE_COMPANY_NAME_OF_CONTACT', 'alpha');
        $res = dolibarr_set_const($db, 'MAIN_USE_COMPANY_NAME_OF_CONTACT', $use_dol_company_name, 'chaine', 1, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $use_managecertif = GETPOST('AGF_MANAGE_CERTIF', 'int');
        $res = dolibarr_set_const($db, 'AGF_MANAGE_CERTIF', $use_managecertif, 'yesno', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $use_manageopca = GETPOST('AGF_MANAGE_OPCA', 'int');
        $res = dolibarr_set_const($db, 'AGF_MANAGE_OPCA', $use_manageopca, 'yesno', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $add_OPCA_link_contact = GETPOST('AGF_LINK_OPCA_ADRR_TO_CONTACT', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_LINK_OPCA_ADRR_TO_CONTACT', $add_OPCA_link_contact, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $useWISIYGtraining = GETPOST('AGF_FCKEDITOR_ENABLE_TRAINING', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_FCKEDITOR_ENABLE_TRAINING', $useWISIYGtraining, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $usesessiontraineeauto = GETPOST('AGF_SESSION_TRAINEE_STATUS_AUTO', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_SESSION_TRAINEE_STATUS_AUTO', $usesessiontraineeauto, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $usecostmanamgemnt = GETPOST('AGF_ADVANCE_COST_MANAGEMENT', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_ADVANCE_COST_MANAGEMENT', $usecostmanamgemnt, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        if (! empty($usecostmanamgemnt)) {
            $res = dolibarr_set_const($db, 'AGF_DOL_TRAINER_AGENDA', $usecostmanamgemnt, 'chaine', 0, '', $conf->entity);
            if (! $res > 0)
                $error ++;
        }

        $useavgcost = GETPOST('AGF_ADD_AVGPRICE_DOCPROPODR', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_ADD_AVGPRICE_DOCPROPODR', $useavgcost, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $contactsessionmandatory = GETPOST('AGF_CONTACT_NOT_MANDATORY_ON_SESSION', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_CONTACT_NOT_MANDATORY_ON_SESSION', $contactsessionmandatory, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $filtertraining = GETPOST('AGF_FILTER_TRAINER_TRAINING', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_FILTER_TRAINER_TRAINING', $filtertraining, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $refpropalauto = GETPOST('AGF_REF_PROPAL_AUTO', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_REF_PROPAL_AUTO', $refpropalauto, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $use_managebpf = GETPOST('AGF_MANAGE_BPF', 'int');
        $res = dolibarr_set_const($db, 'AGF_MANAGE_BPF', $use_managebpf, 'yesno', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $add_progrm_to_conv = GETPOST('AGF_ADD_PROGRAM_TO_CONV', 'int');
        $res = dolibarr_set_const($db, 'AGF_ADD_PROGRAM_TO_CONV', $add_progrm_to_conv, 'yesno', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $add_img_to_conv = GETPOST('AGF_ADD_SIGN_TO_CONVOC', 'int');
        $res = dolibarr_set_const($db, 'AGF_ADD_SIGN_TO_CONVOC', $add_img_to_conv, 'yesno', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $add_progrm_to_convmail = GETPOST('AGF_ADD_PROGRAM_TO_CONVMAIL', 'int');
        $res = dolibarr_set_const($db, 'AGF_ADD_PROGRAM_TO_CONVMAIL', $add_progrm_to_convmail, 'yesno', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $add_dtbirth_fichepres = GETPOST('AGF_ADD_DTBIRTH_FICHEPRES', 'int');
        $res = dolibarr_set_const($db, 'AGF_ADD_DTBIRTH_FICHEPRES', $add_dtbirth_fichepres, 'yesno', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $add_entityname_fichepres = GETPOST('AGF_ADD_ENTITYNAME_FICHEPRES', 'int');
        $res = dolibarr_set_const($db, 'AGF_ADD_ENTITYNAME_FICHEPRES', $add_entityname_fichepres, 'yesno', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $hide_legal_text_below_signature = GETPOST('AGF_FICHE_PRES_HIDE_LEGAL_MEANING_BELOW_SIGNATURE_HEADER', 'int');
        $res = dolibarr_set_const($db, 'AGF_FICHE_PRES_HIDE_LEGAL_MEANING_BELOW_SIGNATURE_HEADER', $hide_legal_text_below_signature, 'yesno', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $add_hide_dt_info = GETPOST('AGF_HIDE_REF_PROPAL_DT_INFO', 'int');
        $res = dolibarr_set_const($db, 'AGF_HIDE_REF_PROPAL_DT_INFO', $add_hide_dt_info, 'yesno', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;
    }else {
		$usesearch_trainer = GETPOST('AGF_TRAINER_USE_SEARCH_TO_SELECT', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_TRAINER_USE_SEARCH_TO_SELECT', $usesearch_trainer, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $usesearch_trainee = GETPOST('AGF_TRAINEE_USE_SEARCH_TO_SELECT', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_TRAINEE_USE_SEARCH_TO_SELECT', $usesearch_trainee, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $usesearch_site = GETPOST('AGF_SITE_USE_SEARCH_TO_SELECT', 'alpha');
        $res = dolibarr_set_const($db, 'AGF_SITE_USE_SEARCH_TO_SELECT', $usesearch_site, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

		$usesearch_training = GETPOST('AGF_TRAINING_USE_SEARCH_TO_SELECT', 'alpha');
		$res = dolibarr_set_const($db, 'AGF_TRAINING_USE_SEARCH_TO_SELECT', $usesearch_training, 'chaine', 0, '', $conf->entity);
        if (! $res > 0)
            $error ++;

        $usesiteinagenda = GETPOST('AGF_USE_SITE_IN_AGENDA', 'int');
        $res = dolibarr_set_const($db, 'AGF_USE_SITE_IN_AGENDA', $usesiteinagenda, 'chaine', 0, '', $conf->entity);
        if (! $res > 0) {
        	$error ++;
        }  else {

            $extrafield = new ExtraFields($db);
            $extrafield->fetch_name_optionals_label('actioncomm', true);
            $key = 'agf_site';

            if(empty($extrafield->attributes['actioncomm']['type'][$key]))
            {
                // Ajout d’un champs
                $e=new ExtraFields($db);
                $param= unserialize('a:1:{s:7:"options";a:1:{s:33:"agefodd_place:ref_interne:rowid::";N;}}');
                $label = 'Lieu de formation';

                $e->addExtraField($key, $label, 'sellist', 100, '', 'actioncomm',0,0,'',$param, 1);
            }

        	if (empty($usesiteinagenda)) {

        	    if(floatval(DOL_VERSION) < 9 ){
        	        $sqlIsHidden = " list=0,ishidden=1 ";
                }
                else{
                    $sqlIsHidden = " list=0 ";
                }

        		$sql='UPDATE '.MAIN_DB_PREFIX.'extrafields SET '.$sqlIsHidden.' WHERE name =\'agf_site\' AND  elementtype=\'actioncomm\' AND entity='.$conf->entity;
        	} else {

                if(floatval(DOL_VERSION) < 9 ){
                    $sqlIsHidden = " list=1,ishidden=0 ";
                }
                else{
                    $sqlIsHidden = " list=1 ";
                }

        		$sql='UPDATE '.MAIN_DB_PREFIX.'extrafields SET '.$sqlIsHidden.' WHERE name =\'agf_site\' AND  elementtype=\'actioncomm\' AND entity='.$conf->entity;
        	}
        	$resUpdate = $db->query($sql);
        	if(! $resUpdate) {
        		setEventMessage($db->lasterror,'errors');
        	}
        }
	}

    $usesearch_cronmailmodel = GETPOST('AGF_SENDAGENDATOTRAINEE_DEFAULT_MAILMODEL', 'alpha');
    $res = dolibarr_set_const($db, 'AGF_SENDAGENDATOTRAINEE_DEFAULT_MAILMODEL', $usesearch_cronmailmodel, 'chaine', 0, '', $conf->entity);
    if (! $res > 0)
        $error ++;


    $fieldsOrder = GETPOST('AGF_CUSTOM_ORDER', 'none');
    $res = dolibarr_set_const($db, 'AGF_CUSTOM_ORDER', $fieldsOrder, 'chaine', 0, '', $conf->entity);
    if (! $res > 0)
        $error ++;


	$fieldsOrder = GETPOST('RELATION_LINK_SELECTED_ON_THIRDPARTY_TRAINING_SESSION', 'none');
	$res = dolibarr_set_const($db, 'RELATION_LINK_SELECTED_ON_THIRDPARTY_TRAINING_SESSION', $fieldsOrder, 'chaine', 0, '', $conf->entity);
	if (! $res > 0)
		$error ++;

	$field = GETPOST('AGF_NO_TRAINER_CHECK_SCHEDULE_TYPE', 'none');
	$res = dolibarr_set_const($db, 'AGF_NO_TRAINER_CHECK_SCHEDULE_TYPE', json_encode($field), 'chaine', 0, '', $conf->entity);
	if (! $res > 0)
		$error ++;

	$TStagiaireStatusToExclude = GETPOST('TStagiaire_session_status', 'none');
	if(is_array($TStagiaireStatusToExclude)) {
		$TStagiaireStatusToExclude = implode(',', $TStagiaireStatusToExclude);
		$TStagiaireStatusToExclude = strtr($TStagiaireStatusToExclude, array('prosp'=>'0'));
	}
	$res = dolibarr_set_const($db, 'AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES', $TStagiaireStatusToExclude, 'chaine', 0, '', $conf->entity);

    if (! $error) {
        setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    } else {
        setEventMessage($langs->trans("Error"), 'errors');
    }

}

/*
 *  Admin Form
 *
 */

llxHeader('', $langs->trans('AgefoddSetupDesc'));

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother=new FormOther($db);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("AgefoddSetupDesc"), $linkback, 'setup');


// Configuration header
$head = agefodd_admin_prepare_head();
dol_fiche_head($head, 'options', $langs->trans("Module103000Name"), 0, "agefodd@agefodd");
print '</div>';

if ($conf->use_javascript_ajax) {
    print ' <script type="text/javascript">';
    print 'window.fnDisplayOPCAAdrr=function() {$( "#OPCAAdrr" ).show();};' . "\n";
    print 'window.fnHideOPCAAdrr=function() {$( "#OPCAAdrr" ).hide();};' . "\n";
    print 'window.fnDisplayCertifAutoAdd=function() {$( "#CertifAutoAdd" ).show();};' . "\n";
    print 'window.fnHideCertifAutoAdd=function() {$( "#CertifAutoAdd" ).hide();};' . "\n";
    print 'window.fnDisplayContactAjaxAdd=function() {$( "#ContactAjaxAdd" ).show();};' . "\n";
    print 'window.fnHideContactAjaxAdd=function() {$( "#ContactAjaxAdd" ).hide();};' . "\n";
    print ' </script>';
}

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="setvarother">';

print '<table class="noborder liste" width="100%">';

print '<tr class="liste_titre"><td colspan="3">' . $langs->trans("PDF") . '</td>';
print '</tr>';

// Trainee status to hide on emargement
$sess_sta = new Agefodd_session_stagiaire($db);
print '<tr class="class="oddeven""><td>' . $langs->trans("AgfTraineeStatusToExcludeToFichePres") . '</td>';
print '<td align="right">';
$TStagiaireStatusToExclude = $conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES;
if(strpos($TStagiaireStatusToExclude, '0') !== false) {
	$TStagiaireStatusToExclude = strtr($conf->global->AGF_STAGIAIRE_STATUS_TO_EXCLUDE_TO_FICHEPRES, array('0'=>'prosp'));
	$sess_sta->labelstatut['prosp'] = $sess_sta->labelstatut[0];
	unset($sess_sta->labelstatut[0]);
}
print $formAgefodd->multiselectarray('TStagiaire_session_status', $sess_sta->labelstatut, explode(',', $TStagiaireStatusToExclude));
print '<td align="center">';
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AGF_FICHEPRES_SHOW_OPCO_NUMBERS") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_FICHEPRES_SHOW_OPCO_NUMBERS');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AGF_FICHEPRES_SHOW_TIME_FOR_PAGE") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_FICHEPRES_SHOW_TIME_FOR_PAGE');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AGF_HIDE_CACHET_FICHEPRES") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_HIDE_CACHET_FICHEPRES');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfMergeAdviseAndConvoc") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_MERGE_ADVISE_AND_CONVOC');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfAddIndexOnTraineePresencePDF") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_ADD_INDEX_TRAINEE');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfHideLegalTextBelowSignatureFichePres") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_FICHE_PRES_HIDE_LEGAL_MEANING_BELOW_SIGNATURE_HEADER');
print '</td>';
print '<td></td>';
print '</tr>';

if (!empty($conf->multicompany->enabled)){
	print '<tr class="oddeven"><td>' . $langs->trans("AgfDisplayEntityNameFichePres") . '</td>';
	print '<td align="right">';
	print ajax_constantonoff('AGF_ADD_ENTITYNAME_FICHEPRES');
	print '</td>';
	print '<td></td>';
	print '</tr>';
}

print '<tr class="oddeven"><td>' . $langs->trans("AgfHideCompanyFichePres") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_HIDE_SOCIETE_FICHEPRES');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfDisplayBirthDateFichePres") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_ADD_DTBIRTH_FICHEPRES');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfPrintInternalRefOnPDF") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_PRINT_INTERNAL_REF_ON_PDF');
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_PRINT_INTERNAL_REF_ON_PDF", $arrval, $conf->global->AGF_PRINT_INTERNAL_REF_ON_PDF);
}
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfPrintTrainingTitleAndSessInfoOnPDF") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_PRINT_TRAINING_LABEL_REF_INTERNE_AND_SESS_ID_DATES');
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_PRINT_TRAINING_LABEL_REF_INTERNE_AND_SESS_ID_DATES", $arrval, $conf->global->AGF_PRINT_TRAINING_LABEL_REF_INTERNE_AND_SESS_ID_DATES);
}
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

// Update global variable AGF_ALLOW_CONV_WITHOUT_FINNACIAL_DOC
print '<tr class="oddeven"><td>' . $langs->trans("AgfAllowConventionWithoutFinancial") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_ALLOW_CONV_WITHOUT_FINNACIAL_DOC');
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_ALLOW_CONV_WITHOUT_FINNACIAL_DOC", $arrval, $conf->global->AGF_ALLOW_CONV_WITHOUT_FINNACIAL_DOC);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfAllowConventionWithoutFinancialHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Update global variable AGF_ADD_PROGRAM_TO_CONV
print '<tr class="oddeven"><td>' . $langs->trans("AgfAddProgramToConv") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_ADD_PROGRAM_TO_CONV');
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_ADD_PROGRAM_TO_CONV", $arrval, $conf->global->AGF_ADD_PROGRAM_TO_CONV);
}
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

// Update global variable AGF_ADD_PROGRAM_TO_CONVMAIL
print '<tr class="oddeven"><td>' . $langs->trans("AgfAddProgramToConvMail") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_ADD_PROGRAM_TO_CONVMAIL');
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_ADD_PROGRAM_TO_CONVMAIL", $arrval, $conf->global->AGF_ADD_PROGRAM_TO_CONVMAIL);
}
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

// Update global variable AGF_ADD_SIGN_TO_CONVOC
print '<tr class="oddeven"><td>' . $langs->trans("AgfAddSignImageToConvoc") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_ADD_SIGN_TO_CONVOC');
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_ADD_SIGN_TO_CONVOC", $arrval, $conf->global->AGF_ADD_SIGN_TO_CONVOC);
}
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

// Affichage du logo commanditaire
print '<tr class="oddeven"><td>' . $langs->trans("AgfUseCustomerLogo") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('AGF_USE_LOGO_CLIENT');
} else {
    $arrval = array (
        '0' => $langs->trans("No"),
        '1' => $langs->trans("Yes")
    );
    print $form->selectarray("AGF_USE_LOGO_CLIENT", $arrval, $conf->global->AGF_USE_LOGO_CLIENT);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfUseCustomerLogoHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Update global variable AGF_ADD_TRAINEE_NAME_INTO_DOCPROPODR
print '<tr class="oddeven"><td>' . $langs->trans("AgfAddTraineeNameIntoDoc") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_ADD_TRAINEE_NAME_INTO_DOCPROPODR');
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_ADD_TRAINEE_NAME_INTO_DOCPROPODR", $arrval, $conf->global->AGF_ADD_TRAINEE_NAME_INTO_DOCPROPODR);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfAddTraineeNameIntoDocHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Update global variable AGF_ADD_AVGPRICE_DOCPROPODR
print '<tr class="oddeven"><td>' . $langs->trans("AgfDisplayAvgPricePropalInvoice") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_ADD_AVGPRICE_DOCPROPODR');
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_ADD_AVGPRICE_DOCPROPODR", $arrval, $conf->global->AGF_ADD_AVGPRICE_DOCPROPODR);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfContacCustMandatoryHelp"), 1, 'help');
print '</td>';
print '</tr>';

print '<tr class="liste_titre"><td colspan="3">' . $langs->trans("AgfDocFin") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfDoNotAutoLinkInvoice") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_NOT_AUTO_LINK_INVOICE');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfHidePropalDtInfo") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_HIDE_REF_PROPAL_DT_INFO');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfDoNotAutoLinkCommande") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_NOT_AUTO_LINK_COMMANDE');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfHideInvoiceDtInfo") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_HIDE_REF_INVOICE_DT_INFO');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfInvoiceCalcAmountDivByQty") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_INVOICE_BY_QTY');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfExtendSessionAssociationToNonRelatedSessions") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_ASSOCIATE_PROPAL_WITH_NON_RELATED_SESSIONS');
print '</td>';
print '<td></td>';
print '</tr>';

// Update global variable AGF_REF_PROPAL_AUTO
print '<tr class="oddeven"><td>' . $langs->trans("AgfRefPropalAuto") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_REF_PROPAL_AUTO');
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_REF_PROPAL_AUTO", $arrval, $conf->global->AGF_REF_PROPAL_AUTO);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfRefPropalAutoHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Update global variable AGF_ADVANCE_COST_MANAGEMENT
print '<tr class="oddeven"><td>' . $langs->trans("AgfManageCost") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	$input_array = array (
		'set' => array (
			'AGF_DOL_TRAINER_AGENDA' => 1
		)
	);

	print ajax_constantonoff('AGF_ADVANCE_COST_MANAGEMENT', $input_array);
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_ADVANCE_COST_MANAGEMENT", $arrval, $conf->global->AGF_MANAGE_CURSUS);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfManageCostHelp"), 1, 'help');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfManageSessionCalendarFacturation") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_MANAGE_SESSION_CALENDAR_FACTURATION');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfViewTripAnsMissionCostPerParticipant") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_VIEW_TRIP_AND_MISSION_COST_PER_PARTICIPANT');
print '</td>';
print '<td></td>';
print '</tr>';

// Forcer la liaison d'une facture sans nécessiter de bon de commande
print '<tr class="oddeven"><td>' . $langs->trans("AgfUseFacWhithoutOrder") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('AGF_USE_FAC_WITHOUT_ORDER');
} else {
    $arrval = array (
        '0' => $langs->trans("No"),
        '1' => $langs->trans("Yes")
    );
    print $form->selectarray("AGF_USE_FAC_WITHOUT_ORDER", $arrval, $conf->global->AGF_USE_FAC_WITHOUT_ORDER);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfUseFacWhithoutOrderHelp"), 1, 'help');
print '</td>';
print '</tr>';


print '<tr class="liste_titre"><td colspan="3">' . $langs->trans("Other") . '</td>';
print '</tr>';

$sql = 'SELECT code, label FROM ' . MAIN_DB_PREFIX . 'c_agefodd_session_calendrier_type WHERE active = 1 AND entity IN (0,' . $conf->entity.')';
$resql = $db->query($sql);
$TScheduleType = array();
if ($resql)
{
	while ($obj = $db->fetch_object($resql)) $TScheduleType[$obj->code] = $obj->label;
}

print '<tr class="oddeven"><td>' . $langs->trans("AGF_NO_TRAINER_CHECK_SCHEDULE_TYPE") . '</td>';
print '<td align="right">';
print $form->multiselectarray('AGF_NO_TRAINER_CHECK_SCHEDULE_TYPE', $TScheduleType, json_decode($conf->global->AGF_NO_TRAINER_CHECK_SCHEDULE_TYPE));
print '</td>';
print '<td></td>';
print '</tr>';

if(empty($conf->global->RELATION_LINK_SELECTED_ON_THIRDPARTY_TRAINING_SESSION)){
	$conf->global->RELATION_LINK_SELECTED_ON_THIRDPARTY_TRAINING_SESSION = 'thirdparty';
	$res = dolibarr_set_const($db, 'RELATION_LINK_SELECTED_ON_THIRDPARTY_TRAINING_SESSION', 'thirdparty', 'chaine', 0, '', $conf->entity);
}
// type de relation par défaut dans l'onglet session de formation d'un tier
print '<tr class="oddeven"><td>' . $langs->trans("AgfDefaultRelationLinkSelectedOnThirdPartyTrainingSession") . '</td>';
print '<td align="right">';
print $formAgefodd->select_type_affect($conf->global->RELATION_LINK_SELECTED_ON_THIRDPARTY_TRAINING_SESSION, 'RELATION_LINK_SELECTED_ON_THIRDPARTY_TRAINING_SESSION');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfCreateContactDefautNewTrainee") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_NEW_TRAINEE_CREATE_CONTACT_DEFAULT');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfUseSiteInAgendaStd") . '</td>';
print '<td align="right">';
$arrval = array (
	'0' => $langs->trans("No"),
	'1' => $langs->trans("Yes")
);
print $form->selectarray("AGF_USE_SITE_IN_AGENDA", $arrval, $conf->global->AGF_USE_SITE_IN_AGENDA);
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfFilterSessionListOnCourantMonth") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_FILTER_SESSION_LIST_ON_COURANT_MONTH');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfPrintFieldsWithCustomOrder") . '</td>';
print '<td align="right">';
print '<input type="text" id="AGF_CUSTOM_ORDER" name="AGF_CUSTOM_ORDER" size="75%" value="'.$conf->global->AGF_CUSTOM_ORDER.'"/>';
print '</td>';
print '<td>';
print $form->textwithpicto('', $langs->trans('AgfPrintFieldsWithCustomOrderHelp'), 1, 0);
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfAddCustomColumnsOnFilter") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_ADD_CUSTOM_COLUMNS_ON_FILTER');
print '</td>';
print '<td></td>';
print '</tr>';

// Update global variable AGF_CONTACT_NOT_MANDATORY_ON_SESSION
print '<tr class="oddeven"><td>' . $langs->trans("AgfContacCustMandatory") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_CONTACT_NOT_MANDATORY_ON_SESSION', $input_array);
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_CONTACT_NOT_MANDATORY_ON_SESSION", $arrval, $conf->global->AGF_CONTACT_NOT_MANDATORY_ON_SESSION);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfContacCustMandatoryHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Update global variable AGF_FCKEDITOR_ENABLE_TRAINING
print '<tr class="oddeven"><td>' . $langs->trans("AgfUseWISIWYGTraining") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_FCKEDITOR_ENABLE_TRAINING');
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_FCKEDITOR_ENABLE_TRAINING", $arrval, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING);
}
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

// Utilisation du contact agefodd ou dolibarr a la creation de la session
print '<tr class="oddeven"><td>' . $langs->trans("AgfUseSessionDolContact") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {

    $input_array = array (
        'alert' => array (
            'set' => array (
                'content' => $langs->trans('AgfConfirmChangeState'),
                'title' => $langs->trans('AgfConfirmChangeState'),
                'method' => 'fnHideContactAjaxAdd',
                'yesButton' => $langs->trans('Yes'),
                'noButton' => $langs->trans('No')
            ),
            'del' => array (
                'content' => $langs->trans('AgfConfirmChangeState'),
                'title' => $langs->trans('AgfConfirmChangeState'),
                'method' => 'fnDisplayContactAjaxAdd',
                'yesButton' => $langs->trans('Yes'),
                'noButton' => $langs->trans('No')
            )
        )
    );

    print ajax_constantonoff('AGF_CONTACT_DOL_SESSION',$input_array);
} else {
    $arrval = array (
        '0' => $langs->trans("No"),
        '1' => $langs->trans("Yes")
    );
    print $form->selectarray("AGF_CONTACT_DOL_SESSION", $arrval, $conf->global->AGF_CONTACT_DOL_SESSION);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfUseSessionDolContactHelp"), 1, 'help');
print '</td>';
print '</tr>';

// use ajax combo box for contact
print '<tr class="oddeven" id="ContactAjaxAdd">';
print '<td>' . $langs->trans("AgfUseSearchToSelectContact") . '</td>';
if (! $conf->use_javascript_ajax || empty($conf->global->CONTACT_USE_SEARCH_TO_SELECT)) {
    print '<td nowrap="nowrap" align="right" colspan="2">';
    print $langs->trans("NotAvailableWhenAjaxDisabledOrContactComboBox");
    print '</td>';
    print '<td align="center">';
    print '</td>';
} else {
    print '<td align="right">';
    if ($conf->use_javascript_ajax) {
        print ajax_constantonoff('AGF_CONTACT_USE_SEARCH_TO_SELECT');

        if (! empty($conf->global->AGF_CONTACT_DOL_SESSION)) {
            print ' <script type="text/javascript">';
            print '$( "#ContactAjaxAdd" ).hide()';
            print ' </script>';
        } else {
            print ' <script type="text/javascript">';
            print '$( "#ContactAjaxAdd" ).show()';
            print ' </script>';
        }

    } else {
        if (! empty($conf->global->AGF_CONTACT_DOL_SESSION)) {
            $arrval = array (
                '0' => $langs->trans("No"),
                '1' => $langs->trans("Yes")
            );
            print $form->selectarray("AGF_CONTACT_USE_SEARCH_TO_SELECT", $arrval, $conf->global->AGF_CONTACT_USE_SEARCH_TO_SELECT);
        }
    }
    print '</td>';
    print '<td align="center">';
    print '</td>';
}
print '</tr>';

// utilisation formulaire Ajax sur choix training
print '<tr class="oddeven">';
print '<td>'.$form->textwithpicto($langs->trans("AgfUseSearchToSelectTraining"),$langs->trans('UseSearchToSelectPictoAgefodd'),1).'</td>';
if (empty($conf->use_javascript_ajax))
{
	print '<td class="nowrap" align="right" colspan="2">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print '</td>';
}
else
{
	print '<td width="60" align="right">';
	$arrval=array(
		'0'=>$langs->trans("No"),
		'1'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",1).')',
	    '2'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",2).')',
	    '3'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",3).')',
	);
	print $form->selectarray("AGF_TRAINING_USE_SEARCH_TO_SELECT",$arrval,$conf->global->AGF_TRAINING_USE_SEARCH_TO_SELECT);
	print '</td>';
}
print '<td>&nbsp;</td>';
print '</tr>';


// utilisation formulaire Ajax sur choix trainer
print '<tr class="oddeven">';
print '<td>'.$form->textwithpicto($langs->trans("AgfUseSearchToSelectTrainer"),$langs->trans('UseSearchToSelectPictoAgefodd'),1).'</td>';
if (empty($conf->use_javascript_ajax))
{
	print '<td class="nowrap" align="right" colspan="2">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print '</td>';
}
else
{
	print '<td width="60" align="right">';
	$arrval=array(
		'0'=>$langs->trans("No"),
		'1'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",1).')',
	    '2'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",2).')',
	    '3'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",3).')',
	);
	print $form->selectarray("AGF_TRAINER_USE_SEARCH_TO_SELECT",$arrval,$conf->global->AGF_TRAINER_USE_SEARCH_TO_SELECT);
	print '</td>';
}
print '<td>&nbsp;</td>';
print '</tr>';

// utilisation formulaire Ajax sur choix trainee
print '<tr class="oddeven">';
print '<td>'.$form->textwithpicto($langs->trans("AgfUseSearchToSelectTrainee"),$langs->trans('UseSearchToSelectPictoAgefodd'),1).'</td>';
if (empty($conf->use_javascript_ajax))
{
	print '<td class="nowrap" align="right" colspan="2">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print '</td>';
}
else
{
	print '<td width="60" align="right">';
	$arrval=array(
		'0'=>$langs->trans("No"),
		'1'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",1).')',
	    '2'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",2).')',
	    '3'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch",3).')',
	);
	print $form->selectarray("AGF_TRAINEE_USE_SEARCH_TO_SELECT",$arrval,$conf->global->AGF_TRAINEE_USE_SEARCH_TO_SELECT);
	print '</td>';
}
print '<td>&nbsp;</td>';
print '</tr>';



// utilisation formulaire Ajax sur choix site
print '<tr class="oddeven">';
print '<td>'.$form->textwithpicto($langs->trans("AgfUseSearchToSelectSite"), $langs->trans('UseSearchToSelectPictoAgefodd'), 1).'</td>';
if (empty($conf->use_javascript_ajax))
{
	print '<td class="nowrap" align="right" colspan="2">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print '</td>';
}
else
{
	print '<td width="60" align="right">';
	$arrval=array(
		'0'=>$langs->trans("No"),
		'1'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch", 1).')',
	    '2'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch", 2).')',
	    '3'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch", 3).')',
	);
	print $form->selectarray("AGF_SITE_USE_SEARCH_TO_SELECT", $arrval, $conf->global->AGF_SITE_USE_SEARCH_TO_SELECT);
	print '</td>';
}
print '<td>&nbsp;</td>';
print '</tr>';

if ($conf->global->AGF_USE_STAGIAIRE_TYPE) {
    // utilisation formulaire Ajax sur choix type de stagiaire
    print '<tr class="oddeven">';
    print '<td>' . $langs->trans("AgfUseSearchToSelectStagType") . '</td>';
    if (! $conf->use_javascript_ajax) {
        print '<td nowrap="nowrap" align="right" colspan="2">';
        print $langs->trans("NotAvailableWhenAjaxDisabled");
        print '</td>';
    } else {
        print '<td align="right">';
        if ($conf->use_javascript_ajax) {
            print ajax_constantonoff('AGF_STAGTYPE_USE_SEARCH_TO_SELECT');
        } else {
            $arrval = array (
                '0' => $langs->trans("No"),
                '1' => $langs->trans("Yes")
            );
            print $form->selectarray("AGF_STAGTYPE_USE_SEARCH_TO_SELECT", $arrval, $conf->global->AGF_STAGTYPE_USE_SEARCH_TO_SELECT);
        }
        print '</td>';
    }
    print '<td>&nbsp;</td>';
    print '</tr>';
}

// Update global variable MAIN_USE_COMPANY_NAME_OF_CONTACT
print '<tr class="oddeven"><td>' . $langs->trans("AgfUseMainNameOfContact") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_USE_COMPANY_NAME_OF_CONTACT');
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("MAIN_USE_COMPANY_NAME_OF_CONTACT", $arrval, $conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfUseMainNameOfContactHelp"), 1, 'help');
print '</td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfUseTrainingProgramModule") . '</td>';
print '<td align="right">';
print ajax_constantonoff('AGF_USE_TRAINING_MODULE');
print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="liste_titre"><td colspan="3">' . $langs->trans("AgfTimeManagement") . '</td>';
print '</tr>';

// Update global variable AGF_USE_REAL_HOURS
print '<tr class="oddeven"><td>' . $langs->trans("AgfUseRealHours") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_USE_REAL_HOURS');
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_USE_REAL_HOURS", $arrval, $conf->global->AGF_USE_REAL_HOURS);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfUseRealHoursHelp"), 1, 'help');
print '</td>';
print '</tr>';

if (! $conf->use_javascript_ajax) {
	print '<tr class="oddeven"><td colspan="3" align="right"><input type="submit" class="button" value="' . $langs->trans("Save") . '"></td>';
	print '</tr>';
}

// Update global variable AGF_GROUP_BY_DAY_CAL
print '<tr class="oddeven"><td>' . $langs->trans("AgfGroupEventByDayInCalendar") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_GROUP_BY_DAY_CAL');
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_GROUP_BY_DAY_CAL", $arrval, $conf->global->AGF_GROUP_BY_DAY_CAL);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfGroupEventByDayInCalendarHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Lors de la creation de session -> creation d'un evenement dans l'agenda Dolibarr
print '<tr class="oddeven"><td>' . $langs->trans("AgfAgendaModuleUse") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('AGF_DOL_AGENDA');
} else {
    $arrval = array (
        '0' => $langs->trans("No"),
        '1' => $langs->trans("Yes")
    );
    print $form->selectarray("AGF_DOL_AGENDA", $arrval, $conf->global->AGF_DOL_AGENDA);
}
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

// Active la gestion du temps formateur
print '<tr class="oddeven"><td>' . $langs->trans("AgfAgendaUseForTrainer") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('AGF_DOL_TRAINER_AGENDA');
} else {
    $arrval = array (
        '0' => $langs->trans("No"),
        '1' => $langs->trans("Yes")
    );
    print $form->selectarray("AGF_DOL_TRAINER_AGENDA", $arrval, $conf->global->AGF_DOL_TRAINER_AGENDA);
}
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

// Update global variable AGF_SESSION_TRAINEE_STATUS_AUTO
print '<tr class="oddeven"><td>' . $langs->trans("AgfUseSubscriptionStatusAuto") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_SESSION_TRAINEE_STATUS_AUTO');
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_SESSION_TRAINEE_STATUS_AUTO", $arrval, $conf->global->AGF_SESSION_TRAINEE_STATUS_AUTO);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfUseSubscriptionStatusAutoHelp"), 1, 'help');
print '</td>';
print '</tr>';


print '<tr class="liste_titre"><td colspan="3">' . $langs->trans("AgfManageCertification") . '</td>';
print '</tr>';

// Update global variable AGF_MANAGE_CERTIF
print '<tr class="oddeven"><td>' . $langs->trans("AgfManageCertification") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
    $input_array = array (
        'alert' => array (
            'set' => array (
                'content' => $langs->trans('AgfConfirmChangeState'),
                'title' => $langs->trans('AgfConfirmChangeState'),
                'method' => 'fnDisplayCertifAutoAdd',
                'yesButton' => $langs->trans('Yes'),
                'noButton' => $langs->trans('No')
            ),
            'del' => array (
                'content' => $langs->trans('AgfConfirmChangeState'),
                'title' => $langs->trans('AgfConfirmChangeState'),
                'method' => 'fnHideCertifAutoAdd',
                'yesButton' => $langs->trans('Yes'),
                'noButton' => $langs->trans('No')
            )
        )
    );

    print ajax_constantonoff('AGF_MANAGE_CERTIF', $input_array);
} else {
    $arrval = array (
        '0' => $langs->trans("No"),
        '1' => $langs->trans("Yes")
    );
    print $form->selectarray("AGF_MANAGE_CERTIF", $arrval, $conf->global->AGF_MANAGE_CERTIF);
}

print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

if ($conf->use_javascript_ajax) {

    // Update global variable AGF_DEFAULT_CREATE_CERTIF
    print '<tr id ="CertifAutoAdd" class="oddeven"><td>' . $langs->trans("AgfCertifAutoAdd") . '</td>';
    print '<td align="right">';
    print ajax_constantonoff('AGF_DEFAULT_CREATE_CERTIF');
    print '</td>';
    print '<td align="center">';
    print $form->textwithpicto('', $langs->trans("AgfCertifAutoAddHelp"), 1, 'help');
    print '</td>';
    print '</tr>';

    if (! empty($conf->global->AGF_MANAGE_CERTIF)) {
        print ' <script type="text/javascript">';
        print '$( "#CertifAutoAdd" ).show()';
        print ' </script>';
    } else {
        print ' <script type="text/javascript">';
        print '$( "#CertifAutoAdd" ).hide()';
        print ' </script>';
    }
} else {
    if (! empty($conf->global->AGF_MANAGE_CERTIF)) {
        // Update global variable AGF_DEFAULT_CREATE_CERTIF
        print '<tr id ="CertifAutoAdd" class="oddeven"><td>' . $langs->trans("AgfCertifAutoAdd") . '</td>';
        print '<td align="right">';
        $arrval = array (
            '0' => $langs->trans("No"),
            '1' => $langs->trans("Yes")
        );
        print $form->selectarray("AGF_DEFAULT_CREATE_CERTIF", $arrval, $conf->global->AGF_DEFAULT_CREATE_CERTIF);
        print '</td>';
        print '<td align="center">';
        print $form->textwithpicto('', $langs->trans("AgfCertifAutoAddHelp"), 1, 'help');
        print '</td>';
        print '</tr>';
    }
}

print '<tr class="liste_titre"><td colspan="3">' . $langs->trans("AgfManageOPCA") . '</td>';
print '</tr>';

// Update global variable AGF_MANAGE_OPCA
print '<tr class="oddeven"><td>' . $langs->trans("AgfManageOPCA") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
    $input_array = array (
        'alert' => array (
            'set' => array (
                'content' => $langs->trans('AgfConfirmChangeState'),
                'title' => $langs->trans('AgfConfirmChangeState'),
                'method' => 'fnDisplayOPCAAdrr',
                'yesButton' => $langs->trans('Yes'),
                'noButton' => $langs->trans('No')
            ),
            'del' => array (
                'content' => $langs->trans('AgfConfirmChangeState'),
                'title' => $langs->trans('AgfConfirmChangeState'),
                'method' => 'fnHideOPCAAdrr',
                'yesButton' => $langs->trans('Yes'),
                'noButton' => $langs->trans('No')
            )
        )
    );

    print ajax_constantonoff('AGF_MANAGE_OPCA', $input_array);
} else {
    $arrval = array (
        '0' => $langs->trans("No"),
        '1' => $langs->trans("Yes")
    );
    print $form->selectarray("AGF_MANAGE_OPCA", $arrval, $conf->global->AGF_MANAGE_OPCA);
}
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

if ($conf->use_javascript_ajax) {

    // Update global variable AGF_LINK_OPCA_ADRR_TO_CONTACT
    print '<tr id ="OPCAAdrr" class="oddeven"><td>' . $langs->trans("AgfLinkOPCAAddrToContact") . '</td>';
    print '<td align="right">';
    print ajax_constantonoff('AGF_LINK_OPCA_ADRR_TO_CONTACT');
    print '</td>';
    print '<td align="center">';
    print $form->textwithpicto('', $langs->trans("AgfLinkOPCAAddrToContactHelp"), 1, 'help');
    print '</td>';
    print '</tr>';

    if (! empty($conf->global->AGF_MANAGE_OPCA)) {
        print ' <script type="text/javascript">';
        print '$( "#OPCAAdrr" ).show()';
        print ' </script>';
    } else {
        print ' <script type="text/javascript">';
        print '$( "#OPCAAdrr" ).hide()';
        print ' </script>';
    }
} else {
    if (! empty($conf->global->AGF_MANAGE_OPCA)) {
        // Update global variable AGF_LINK_OPCA_ADRR_TO_CONTACT
        print '<tr id ="OPCAAdrr" class="oddeven"><td>' . $langs->trans("AgfLinkOPCAAddrToContact") . '</td>';
        print '<td align="right">';
        $arrval = array (
            '0' => $langs->trans("No"),
            '1' => $langs->trans("Yes")
        );
        print $form->selectarray("AGF_LINK_OPCA_ADRR_TO_CONTACT", $arrval, $conf->global->AGF_LINK_OPCA_ADRR_TO_CONTACT);
        print '</td>';
        print '<td align="center">';
        print $form->textwithpicto('', $langs->trans("AgfLinkOPCAAddrToContactHelp"), 1, 'help');
        print '</td>';
        print '</tr>';
    }
}

// Update global variable AGF_MANAGE_CURSUS
print '<tr class="oddeven"><td>' . $langs->trans("AgfManageCursus") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('AGF_MANAGE_CURSUS');
} else {
    $arrval = array (
        '0' => $langs->trans("No"),
        '1' => $langs->trans("Yes")
    );
    print $form->selectarray("AGF_MANAGE_CURSUS", $arrval, $conf->global->AGF_MANAGE_CURSUS);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfManageCursusHelp"), 1, 'help');
print '</td>';
print '</tr>';



// Update global variable AGF_FILTER_TRAINER_TRAINING
print '<tr class="oddeven"><td>' . $langs->trans("AgfFilterTrainerTraining") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('AGF_FILTER_TRAINER_TRAINING', $input_array);
} else {
	$arrval = array (
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
	);
	print $form->selectarray("AGF_FILTER_TRAINER_TRAINING", $arrval, $conf->global->AGF_FILTER_TRAINER_TRAINING);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfFilterTrainerTrainingHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Update global variable AGF_MANAGE_BPF
print '<tr class="oddeven"><td>' . $langs->trans("AgfManageBPF") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('AGF_MANAGE_BPF');
} else {
    $arrval = array (
        '0' => $langs->trans("No"),
        '1' => $langs->trans("Yes")
    );
    print $form->selectarray("AGF_MANAGE_BPF", $arrval, $conf->global->AGF_MANAGE_BPF);
}
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfManageBPFHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Update global variable AGF_DISPLAY_TRAINEE_GROUP_BY_STATUS
print '<tr class="oddeven"><td>' . $langs->trans("AgfTraineeDisplayGroupByStatus") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('AGF_DISPLAY_TRAINEE_GROUP_BY_STATUS');
} else {
    $arrval = array (
        '0' => $langs->trans("No"),
        '1' => $langs->trans("Yes")
    );
    print $form->selectarray("AGF_DISPLAY_TRAINEE_GROUP_BY_STATUS", $arrval, $conf->global->AGF_DISPLAY_TRAINEE_GROUP_BY_STATUS);
}
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

if (! $conf->use_javascript_ajax) {
    print '<tr class="oddeven"><td colspan="3" align="right"><input type="submit" class="button" value="' . $langs->trans("Save") . '"></td>';
    print '</tr>';
}

// Update global variable AGF_ONLY_WARNING_ON_TRAINER_AVAILABILITY
print '<tr class="oddeven"><td>' . $langs->trans("AgfTrainerAvailabilityOnlyWarning") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('AGF_ONLY_WARNING_ON_TRAINER_AVAILABILITY');
} else {
    $arrval = array (
        '0' => $langs->trans("No"),
        '1' => $langs->trans("Yes")
    );
    print $form->selectarray("AGF_ONLY_WARNING_ON_TRAINER_AVAILABILITY", $arrval, $conf->global->AGF_ONLY_WARNING_ON_TRAINER_AVAILABILITY);
}
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

if (! $conf->use_javascript_ajax) {
    print '<tr class="oddeven"><td colspan="3" align="right"><input type="submit" class="button" value="' . $langs->trans("Save") . '"></td>';
    print '</tr>';
}


print '<tr class="oddeven"><td>' . $langs->trans("AgfPrintTrainingRefAndSessIdOnPDF") . '</td>';
print '<td align="right">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('AGF_PRINT_TRAINING_REF_AND_SESS_ID_ON_PDF');
} else {
    $arrval = array (
        '0' => $langs->trans("No"),
        '1' => $langs->trans("Yes")
    );
    print $form->selectarray("AGF_PRINT_TRAINING_REF_AND_SESS_ID_ON_PDF", $arrval, $conf->global->AGF_PRINT_TRAINING_REF_AND_SESS_ID_ON_PDF);
}
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';



$var = true;

$var=!$var;


$var=!$var;


$var=!$var;


$var=!$var;


$var=!$var;

$var=!$var;


$var=!$var;


$var=!$var;




$var=!$var;


$var=!$var;


$var=!$var;


$var=!$var;


$var=!$var;


$var=!$var;


$var=!$var;


$var=!$var;


$var=!$var;


$var=!$var;


$var=!$var;

print '<tr class="liste_titre"><td colspan="3">' . $langs->trans("Mail") . '</td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("AgfSendAgendaToTraineeDefaultMailModel") . '</td>';
print '<td align="right">';

$formMail = new FormMail($db);
$models = $formMail->fetchAllEMailTemplate('cron_session', $user, $langs);

if($models>0)
{
    foreach($formMail->lines_model as $line)
    {
        if (preg_match('/\((.*)\)/', $line->label, $reg)){
            $modelmail_array[$line->id]=$langs->trans($reg[1]);		// langs->trans when label is __(xxx)__
        }
        else{
            $modelmail_array[$line->id]=$line->label;
        }
        if ($line->lang) $modelmail_array[$line->id].=' ('.$line->lang.')';
        if ($line->private) $modelmail_array[$line->id].=' - '.$langs->trans("Private");
        //if ($line->fk_user != $user->id) $modelmail_array[$line->id].=' - '.$langs->trans("By").' ';
    }
}
print $formMail->selectarray('AGF_SENDAGENDATOTRAINEE_DEFAULT_MAILMODEL', $modelmail_array, $conf->global->AGF_SENDAGENDATOTRAINEE_DEFAULT_MAILMODEL, 1);

print '</td>';
print '<td></td>';
print '</tr>';

print '<tr class="oddeven"><td colspan="3" align="right"><input type="submit" class="button" value="' . $langs->trans("Save") . '"></td></tr>';

print '</table><br>';
print '</form>';

llxFooter();
$db->close();
