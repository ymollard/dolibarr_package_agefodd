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
$res = @include ("../../main.inc.php"); // For root directory
if (! $res) $res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res) die("Include of main fails");

require_once '../class/agefodd_formation_catalogue.class.php';
require_once '../class/agefodd_session_admlevel.class.php';
require_once '../class/agefodd_calendrier.class.php';
require_once '../class/agefodd_session_calendrier.class.php';
require_once '../class/html.formagefodd.class.php';
require_once '../lib/agefodd.lib.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("admin");
$langs->load('agefodd@agefodd');

if (! $user->rights->agefodd->admin && ! $user->admin)
    accessforbidden();

$action = GETPOST('action', 'alpha');

if ($action == 'setvarother') {

    if (empty($conf->use_javascript_ajax))
    {
        // Active l'accés externe pour agefodd
        $activAccesexterne = GETPOST('AGF_EACCESS_ACTIVATE');
        $res = dolibarr_set_const($db, 'AGF_EACCESS_ACTIVATE', $activAccesexterne, 'chaine', 0, '', $conf->entity);
        if ($res < 0) $error++;
        
        // Active l'accés formateur
        $activeAccesFormateur = GETPOST('AGF_EA_TRAINER_ENABLED');
        $res = dolibarr_set_const($db, 'AGF_EA_TRAINER_ENABLED', $activeAccesFormateur, 'chaine', 0, '', $conf->entity);
        if ($res < 0) $error++;
        
        // Vue éclatée des heures participant sur la liste des sessions
        $heuresEclatee = GETPOST('AGF_EA_ECLATE_HEURES_PAR_TYPE');
        $res = dolibarr_set_const($db, 'AGF_EA_ECLATE_HEURES_PAR_TYPE', $heuresEclatee, 'chaine', 0, '', $conf->entity);
        if ($res < 0) $error++;
        
    }
    
    // Vue éclatée des heures participant sur la liste des sessions
    $heuresEclateeExclues = serialize(GETPOST('AGF_EA_ECLATE_HEURES_EXCLUES'));
    $res = dolibarr_set_const($db, 'AGF_EA_ECLATE_HEURES_EXCLUES', $heuresEclateeExclues, 'chaine', 0, '', $conf->entity);
    if ($res < 0) $error++;
    
    if (! $error) {
        setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    } else {
        setEventMessage($langs->trans("Error") . " " . $msg, 'errors');
    }

}

if (empty($conf->externalaccess->enabled)) accessforbidden($langs->trans('AgfErrorEANotActivated'));

/*
 *  Admin Form
 *
 */

llxHeader('', $langs->trans('AgefoddSetupDesc'));

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother=new FormOther($db);

dol_htmloutput_mesg($mesg);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans("AgefoddSetupDesc"), $linkback, 'setup');

// Configuration header
$head = agefodd_admin_prepare_head();
dol_fiche_head($head, 'external', $langs->trans("Module103000Name"), 0, "agefodd@agefodd");

if ($conf->use_javascript_ajax) {
    print ' <script type="text/javascript">';
    print 'window.fnHideExternalOptions=function() {$( "#externaloption" ).prev().hide(); $( "#externaloption" ).hide();};' . "\n";
    print 'window.fnDisplayExternalOptions=function() {$( "#externaloption" ).prev().show(); $( "#externaloption" ).show();};' . "\n";
    print ' </script>';
}

print_titre($langs->trans("Options"));
print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="setvarother">';

print '<table class="noborder" width="100%">';
$var = true;

// configuration external access
if(!empty($conf->externalaccess->enabled))
{
    // Active l'accés externe pour agefodd
    print '<tr '.$bc[$var].'><td>' . $langs->trans("AgfActivateExternalAccessForAgefodd") . '</td>';
    print '<td align="left">';
    if ($conf->use_javascript_ajax) {
        $input_array = array (
            'alert' => array (
                'set' => array (
                    'content' => $langs->trans('AgfConfirmChangeState'),
                    'title' => $langs->trans('AgfConfirmChangeState'),
                    'method' => 'fnDisplayExternalOptions',
                    'yesButton' => $langs->trans('Yes'),
                    'noButton' => $langs->trans('No')
                ),
                'del' => array (
                    'content' => $langs->trans('AgfConfirmChangeState'),
                    'title' => $langs->trans('AgfConfirmChangeState'),
                    'method' => 'fnHideExternalOptions',
                    'yesButton' => $langs->trans('Yes'),
                    'noButton' => $langs->trans('No')
                )
            )
        );
        
        print ajax_constantonoff('AGF_EACCESS_ACTIVATE', $input_array);
    } else {
        $arrval = array (
            '0' => $langs->trans("No"),
            '1' => $langs->trans("Yes")
        );
        print $form->selectarray("AGF_EACCESS_ACTIVATE", $arrval, $conf->global->AGF_EACCESS_ACTIVATE);
    }
    print '</td>';
    print '<td></td>';
    print '</tr>';
    $var=!$var;
    print '</table>';
    
    print_titre($langs->trans("Options")." ".$langs->trans('AgfExternalAccess'));
    print '<table class="noborder" width="100%" id="externaloption">';
    
    // Active l'accés formateur
    print '<tr '.$bc[$var].'"><td>' . $langs->trans("AgfActivateAccessForTrainers") . '</td>';
    print '<td align="left">';
    if ($conf->use_javascript_ajax) {
        print ajax_constantonoff('AGF_EA_TRAINER_ENABLED');
    } else {
        $arrval = array (
            '0' => $langs->trans("No"),
            '1' => $langs->trans("Yes")
        );
        print $form->selectarray("AGF_EA_TRAINER_ENABLED", $arrval, $conf->global->AGF_EA_TRAINER_ENABLED);
    }
    print '</td>';
    print '<td></td>';
    print '</tr>';
    $var=!$var;
    
    // Vue éclatée des heures participant sur la liste des sessions
    print '<tr '.$bc[$var].'><td>' . $langs->trans("AgfHeuresDeclareesEclateesParType") . '</td>';
    print '<td align="left">';
    if ($conf->use_javascript_ajax) {
        print ajax_constantonoff('AGF_EA_ECLATE_HEURES_PAR_TYPE');
    } else {
        $arrval = array (
            '0' => $langs->trans("No"),
            '1' => $langs->trans("Yes")
        );
        print $form->selectarray("AGF_EA_ECLATE_HEURES_PAR_TYPE", $arrval, $conf->global->AGF_EA_ECLATE_HEURES_PAR_TYPE);
    }
    print '</td>';
    print '<td></td>';
    print '</tr>';
    $var=!$var;
    
    // status à exclure des heures éclatées
    print '<tr '.$bc[$var].'><td>' . $langs->trans("AgfHeuresDeclareesEclateesStatusExclus") . '</td>';
    print '<td align="left">';
    //AGF_EA_ECLATE_HEURES_EXCLUES
    $arrval = array (
        Agefodd_sesscalendar::STATUS_DRAFT => $langs->trans('AgfStatusCalendar_previsionnel'),
        Agefodd_sesscalendar::STATUS_CONFIRMED => $langs->trans('AgfStatusCalendar_confirmed'),
        Agefodd_sesscalendar::STATUS_CANCELED => $langs->trans('AgfStatusCalendar_canceled'),
        Agefodd_sesscalendar::STATUS_MISSING => $langs->trans('AgfStatusCalendar_missing'),
    );
    print $form->multiselectarray('AGF_EA_ECLATE_HEURES_EXCLUES', $arrval, unserialize($conf->global->AGF_EA_ECLATE_HEURES_EXCLUES));
    
    // Petit hack parce qu'évidemment le multiselectarray ne prend pas en compte le valeur 0 => STATUS_DRAFT
    if (is_array(unserialize($conf->global->AGF_EA_ECLATE_HEURES_EXCLUES)) && in_array(0, unserialize($conf->global->AGF_EA_ECLATE_HEURES_EXCLUES)))
    {
        ?>
        <script>
		$(document).ready(function(){
			$('#AGF_EA_ECLATE_HEURES_EXCLUES > option[value=0]').attr('selected', true);
			var first = $('#AGF_EA_ECLATE_HEURES_EXCLUES').next().find('ul li').first();
			$('<li class="select2-selection__choice" title="Prévisionnel"><span class="select2-selection__choice__remove" role="presentation">×</span>Prévisionnel</li>').insertBefore(first);
		});
        </script>
        <?php
    }
    
    print '</td>';
    print '<td></td>';
    print '</tr>';
    $var=!$var;
}

// if (empty($conf->use_javascript_ajax))
// {
    print '<tr '.$bc[$var].'><td colspan="3" align="right"><input type="submit" class="button" value="' . $langs->trans("Save") . '"></td></tr>';
// }

if (!empty($conf->use_javascript_ajax) && empty($conf->global->AGF_EACCESS_ACTIVATE))
{
    print "<script>fnHideExternalOptions()</script>";
}
print '</table><br>';
print '</form>';

llxFooter();
$db->close();
