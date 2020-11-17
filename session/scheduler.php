<?php
/* Copyright (C) 2018		Pierre-Henry Favre	<phf@atm-consulting.fr>
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
 * \file agefodd/session/scheduler.php
 * \ingroup agefodd
 * \brief card of session
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

dol_include_once('/agefodd/class/agsession.class.php');
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
//require_once ('../class/agefodd_sessadm.class.php');
//require_once ('../class/agefodd_session_admlevel.class.php');
dol_include_once('/agefodd/class/html.formagefodd.class.php');
//require_once ('../class/agefodd_session_calendrier.class.php');
//require_once ('../class/agefodd_calendrier.class.php');
//require_once ('../class/agefodd_session_formateur.class.php');
//require_once ('../class/agefodd_session_stagiaire.class.php');
//require_once ('../class/agefodd_session_element.class.php');
//require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
//require_once (DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php');
//require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
//require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
//require_once ('../class/agefodd_formation_catalogue.class.php');
//require_once ('../class/agefodd_opca.class.php');

// Security check
if (! $user->rights->agefodd->lire || empty($conf->fullcalendarscheduler->enabled)) {
	accessforbidden();
}

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');

$object = new Agsession($db);
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

$hookmanager->initHooks(array('agefodd_session_scheduler'));

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (!empty($id) && empty($object->id))
{
	$res=$object->fetch($id);
	if ($object->id <= 0) dol_print_error($db, $object->error);
}

$stagiaires = new Agefodd_session_stagiaire($db);
$formateurs = new Agefodd_session_formateur($db);


if (!empty($object->id))
{
	$stagiaires->fetch_stagiaire_per_session($object->id);
	$formateurs->fetch_formateur_per_session($object->id);
}


$morejs = array(
	'/fullcalendarscheduler/js/moment.min.js'
	,'/fullcalendarscheduler/js/fullcalendar.js'
	,'/fullcalendarscheduler/js/scheduler.min.js' // TODO swap for scheduler.min.js
//	,'/fullcalendarscheduler/js/fullcalendarscheduler.js'
	,'/agefodd/js/session_scheduler.js'
	,'/fullcalendarscheduler/js/langs/locale-all.js'
);
$morecss = array(
	'/fullcalendarscheduler/css/fullcalendarscheduler.css'
	,'/fullcalendarscheduler/css/fullcalendar.min.css'
	,'/fullcalendarscheduler/css/scheduler.min.css'
	,'/agefodd/css/session_scheduler.css'
);



$langs->load('main');
$langs->load('fullcalendarscheduler@fullcalendarscheduler');


llxHeader('', $langs->trans("AgfSessionDetail"), '', '', 0, 0, $morejs, $morecss);

$head = session_prepare_head($object);
dol_fiche_head($head, 'scheduler', $langs->trans('AgfSessionDetail'), 0, 'action');

dol_agefodd_banner_tab($object, 'id');

echo '<div id="agf_session_scheduler"></div>';




/**
 * Instance des variables utiles pour le formulaire de création d'un événement
 */
$formagefodd = new FormAgefodd($db);
$form=new Form($db);

$inputs_hidden = '<input type="hidden" name="fk_agefodd_session" value="'.$object->id.'" />';
//$inputs_hidden.= '';

$select_calendrier_type = '<label>'.$langs->trans("AgfCalendarType").'</label> '.$formagefodd->select_calendrier_type('', 'calendrier_type');

$TStatus = Agefodd_sesscalendar::getListStatus();
$select_calendrier_status = $langs->trans('Status') . '&nbsp;' . $form->selectarray('calendrier_status', $TStatus);

ob_start();
echo '<label>'.$langs->trans("DateActionStart").'</label> ';
print $form->selectDate(null,'date_start',1,1,1,"action",1,1,0,0,'fulldaystart');
$select_date_start = ob_get_clean();

ob_start();
echo '<label>'.$langs->trans("DateActionEnd").'</label> ';
print $form->selectDate(null,'date_end',1,1,1,"action",1,1,0,0,'fulldayend');
$select_date_end = ob_get_clean();

$html_participants = '';
// Uniquement si la conf de saisie des temps réels par participant est actif, autrement ça sert à rien d'afficher la liste des participants
if (!empty($conf->global->AGF_USE_REAL_HOURS))
{

	$content_participants = '<div class="liste_participants">';
	$nb_wrong_def = 0;
	if (count($stagiaires->lines) > 8) $float_class = 'fleft';
	else $float_class = '';

	foreach ($stagiaires->lines as &$line)
	{
		if ($line->id < 0)
		{
			$nb_wrong_def++;
		}
		else
		{
			$input = '<input class="type_hour" type="text" name="TRealHour[' . $line->id . ']" size="3" value="" /> '.$langs->transnoentitiesnoconv('Hours');
			$content_participants.= '<div class="item_participant '.$float_class.'">';
			$content_participants.= '<label class="item_participant_label">'.strtoupper($line->nom) . ' ' . ucfirst($line->prenom).'</label>';
			$content_participants.= '<span class="item_participant_hours">'.$input.'</span>';
			$content_participants.= '</div>';
		}
	}

	$content_participants.= '<div class="clearboth"></div></div>';

	$html_participants.= '<div class="titre">'.$langs->transnoentitiesnoconv('AgfMenuActStagiaire');
	if ($nb_wrong_def > 0) $html_participants.= ' <small class="error">('.$langs->transnoentitiesnoconv('AgfWarning_wrong_def_participant', $nb_wrong_def).')</small>';
	$html_participants.='</div>';
	$html_participants.= $content_participants;
}


$html_formateurs.= '<div class="titre">'.$langs->transnoentitiesnoconv('AgfFormateur').'</div>';
foreach ($formateurs->lines as &$line)
{
	$input = '<input class="skip_disabled" style="vertical-align:middle" type="checkbox" value="'.$line->opsid.'" name="TFormateurId[]" />';
	$selects = $formagefodd->select_time('', 'TFormateurHeured['.$line->opsid.']', 1, false, 'skip_disabled').' '.$formagefodd->select_time('', 'TFormateurHeuref['.$line->opsid.']', 1, false, 'skip_disabled');
	$html_formateurs.= '<p style="margin:0 0 2px 0">'.$input.' <label>'.strtoupper($line->lastname) . ' ' . ucfirst($line->firstname).'</label> : '.$selects.'</p>';
}

/**/

// fullcalendarscheduler_TColorCivility
echo '
<script type="text/javascript">
	fk_agefodd_session = '.$object->id.';
	fk_user = '.$user->id.';
	fullcalendarscheduler_interface = "'.dol_buildpath('/agefodd/scripts/session_scheduler.php', 1).'";
	fullcalendarscheduler_initialLangCode = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_LOCALE_LANG) ? $conf->global->FULLCALENDARSCHEDULER_LOCALE_LANG : 'fr').'";
	fullcalendarscheduler_snapDuration = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_SNAP_DURATION) ? $conf->global->FULLCALENDARSCHEDULER_SNAP_DURATION : '00:30:00').'";
	fullcalendarscheduler_aspectRatio = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_ASPECT_RATIO) ? $conf->global->FULLCALENDARSCHEDULER_ASPECT_RATIO : '1.6').'";
	fullcalendarscheduler_minTime = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_MIN_TIME) ? $conf->global->FULLCALENDARSCHEDULER_MIN_TIME : '00:00').'";
	fullcalendarscheduler_maxTime = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_MAX_TIME) ? $conf->global->FULLCALENDARSCHEDULER_MAX_TIME : '23:00').'";


	fullcalendar_scheduler_resources_allowed = [];

	fullcalendar_scheduler_businessHours_week_start = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START : '08:00').'";
	fullcalendar_scheduler_businessHours_week_end = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END : '18:00').'";

	fullcalendar_scheduler_businessHours_weekend_start = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START : '10:00').'";
	fullcalendar_scheduler_businessHours_weekend_end = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END : '16:00').'";

	fullcalendarscheduler_title_dialog_create_event = "'.$langs->transnoentitiesnoconv('Agf_fullcalendarscheduler_title_dialog_create_event').'";
	fullcalendarscheduler_title_dialog_update_event = "'.$langs->transnoentitiesnoconv('Agf_fullcalendarscheduler_title_dialog_update_event').'";
	fullcalendarscheduler_title_dialog_delete_event = "'.$langs->transnoentitiesnoconv('Agf_fullcalendarscheduler_title_dialog_delete_event').'";
	fullcalendarscheduler_title_dialog_show_detail_event = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_title_dialog_show_detail_event').'";
	fullcalendarscheduler_button_dialog_add = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_button_dialog_add').'";
	fullcalendarscheduler_button_dialog_update = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_button_dialog_update').'";
	fullcalendarscheduler_button_dialog_cancel = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_button_dialog_cancel').'";
	fullcalendarscheduler_button_dialog_confirm = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_button_dialog_confirm').'";
	fullcalendarscheduler_content_dialog_delete = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_content_dialog_delete').' <br />'.$langs->transnoentitiesnoconv('Agf_fullcalendarscheduler_content_dialog_delete_for_calendrier_formateur').'<input type=\'checkbox\' name=\'delete_cal_formateur\' value=\'1\' />";

	fullcalendarscheduler_date_format = "'.$langs->trans("FormatDateShortJavaInput").'";

	fullcalendarscheduler_div = $(\'<form id="form_add_event" action="#"></form>\');
	fullcalendarscheduler_div	.append('.json_encode($inputs_hidden).')
								.append("<p>"+'.json_encode($select_calendrier_type).'+"</p>")
                                .append("<p>"+'.json_encode($select_calendrier_status).'+"</p>")
								.append("<p class=\'is_past\'>"+'.json_encode($select_date_start).'+"</p>")
								.append("<p class=\'is_past\'>"+'.json_encode($select_date_end).'+"</p>")
								.append("<div>"+'.json_encode($html_participants).'+"</div>")
								.append("<div class=\'is_past\'>"+'.json_encode($html_formateurs).'+"</div>");

	fullcalendarscheduler_picto_delete = "'.addslashes(img_delete()).'";

	fullcalendarscheduler_error_msg_allday_event_exists = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_error_msg_allday_event_exists').'";
</script>';

echo '
<style type="text/css">
	#agf_session_scheduler .ajaxtool {
		position:absolute;
		top:3px;
		right:2px;
	}

	#agf_session_scheduler .ajaxtool_link.need_to_be_adjust img {
		position:relative;
		top:-1px;
	}

	.ui-dialog { overflow: visible; }

	.fc-agendaWeek-view tr { height: '.(! empty($conf->global->FULLCALENDARSCHEDULER_ROW_HEIGHT) ? $conf->global->FULLCALENDARSCHEDULER_ROW_HEIGHT : '40px').'; }
</style>
';

$parameters=array();
$reshook=$hookmanager->executeHooks('addMoreContent', $parameters, $object, $action);







dol_fiche_end();

llxFooter();
$db->close();
