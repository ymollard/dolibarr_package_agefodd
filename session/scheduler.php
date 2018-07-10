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

require_once ('../class/agsession.class.php');
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
//require_once ('../class/agefodd_sessadm.class.php');
//require_once ('../class/agefodd_session_admlevel.class.php');
//require_once ('../class/html.formagefodd.class.php');
//require_once ('../class/agefodd_session_calendrier.class.php');
//require_once ('../class/agefodd_calendrier.class.php');
//require_once ('../class/agefodd_session_formateur.class.php');
//require_once ('../class/agefodd_session_stagiaire.class.php');
//require_once ('../class/agefodd_session_element.class.php');
//require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../lib/agefodd.lib.php');
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
);



$langs->load('main');
$langs->load('fullcalendarscheduler@fullcalendarscheduler');


llxHeader('', $langs->trans("Agenda"), '', '', 0, 0, $morejs, $morecss);

$head = session_prepare_head($object);
dol_fiche_head($head, 'scheduler', $langs->trans('SessionScheduler'), 0, 'action');


echo '<div id="agf_session_scheduler"></div>';




/**
 * Instance des variables utiles pour le formulaire de création d'un événement
 */
$formactions=new FormActions($db);
$form=new Form($db);

ob_start();
$formactions->select_type_actions('AC_AGF_SESS', 'type_code', "' AND type = 'agefodd"); // + petit hack hahahahahaaaha T_T
$select_type_action = '<div style="display:none;">'.ob_get_clean().'</div>'; // Je le cache car le standard agefodd type les event agenda avec le code AC_AGF_SESS

$input_title_action = '<input type="text" name="label" placeholder="'.$langs->transnoentitiesnoconv('Title').'" style="width:300px" />';

// on intègre la notion de fulldayevent ??? $langs->trans("EventOnFullDay")   <input type="checkbox" id="fullday" name="fullday" '.(GETPOST('fullday')?' checked':'').' />
$input_fulldayevent = '<label for="fullday">'.$langs->trans("EventOnFullDay").'</label> <input type="checkbox" id="fullday" name="fullday" value="1" />';

ob_start();
echo '<label>'.$langs->trans("DateActionStart").'</label> ';
$form->select_date(null,'date_start',1,1,1,"action",1,1,0,0,'fulldaystart');
$select_date_start = ob_get_clean();

ob_start();
echo '<label>'.$langs->trans("DateActionEnd").'</label> ';
$form->select_date(null,'date_end',1,1,1,"action",1,1,0,0,'fulldayend');
$select_date_end = ob_get_clean();

/*
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
$doleditor=new DolEditor('note',(GETPOST('note')?GETPOST('note'):$object->note),'',180,'dolibarr_notes','In',true,true,$conf->fckeditor->enabled,ROWS_6,90);
$doleditor->Create();
*/
$input_note = '<textarea name="note" value="" placeholder="'.$langs->trans('Description').'" rows="3" class="minwidth300"></textarea>';
$options = array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')));
$select_company = '<label for="fk_soc">'.$langs->transnoentitiesnoconv('ThirdParty').'</label>'.$form->select_company('', 'fk_soc', '', 1, 0, 0, $options, 0, 'minwidth300');

ob_start();
echo '<label for="contactid">'.$langs->transnoentitiesnoconv('Contact').'</label>';
$form->select_contacts(-1, -1, 'contactid', 1, '', '', 0, 'minwidth200'); // contactid car nom non pris en compte par l'ajax en vers.<3.9
$select_contact = ob_get_clean();

$select_user = '<label for="fk_user">'.$langs->transnoentitiesnoconv('User').'</label>'.$form->select_dolusers($user->id, 'fk_user');

//$select_service = '<label for="fk_product">'.$langs->transnoentitiesnoconv('Service').'</label>'.$form->select_produits_list('', 'fk_product', 1);
ob_start();
echo '<label for="fk_service">'.$langs->transnoentitiesnoconv('Service').'</label>';
$form->select_produits('', 'fk_service', 1);
$select_service = ob_get_clean();


$TExtraToPrint = '<table id="extrafield_to_replace" class="extrafields" width="100%">';

$extrafields = new ExtraFields($db);
$extralabels=$extrafields->fetch_name_optionals_label($actioncomm->table_element);
if (!empty($extrafields->attribute_label))
{
	$TExtraToPrint.= $actioncomm->showOptionals($extrafields, 'edit');
}
$TExtraToPrint.= '</table>';
/**/

// fullcalendarscheduler_TColorCivility
echo '
<script type="text/javascript">
	fk_agefodd_session = '.$object->id.';
	fk_user = '.$user->id.';
	fullcalendarscheduler_interface = "'.dol_buildpath('/agefodd/scripts/session_scheduler.php', 1).'";
//	fullcalendarscheduler_interface = "'.dol_buildpath('/fullcalendarscheduler/script/interface.php', 1).'";
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
	
	fullcalendarscheduler_title_dialog_create_event = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_title_dialog_create_event').'";
	fullcalendarscheduler_title_dialog_update_event = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_title_dialog_update_event').'";
	fullcalendarscheduler_title_dialog_delete_event = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_title_dialog_delete_event').'";
	fullcalendarscheduler_title_dialog_show_detail_event = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_title_dialog_show_detail_event').'";
	fullcalendarscheduler_button_dialog_add = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_button_dialog_add').'";
	fullcalendarscheduler_button_dialog_update = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_button_dialog_update').'";
	fullcalendarscheduler_button_dialog_cancel = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_button_dialog_cancel').'";
	fullcalendarscheduler_button_dialog_confirm = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_button_dialog_confirm').'";
	fullcalendarscheduler_content_dialog_delete = "'.$langs->transnoentitiesnoconv('fullcalendarscheduler_content_dialog_delete').'";
	
	fullcalendarscheduler_date_format = "'.$langs->trans("FormatDateShortJavaInput").'";
	
	fullcalendarscheduler_div = $(\'<form id="form_add_event" action="#"></form>\');
	fullcalendarscheduler_div	.append("<p>"+'.json_encode($select_type_action).'+"</p>")
								.append("<p>"+'.json_encode($input_title_action).'+"</p>")
								.append("<p>"+'.json_encode($input_fulldayevent).'+"</p>")
								.append("<p>"+'.json_encode($select_date_start).'+"</p>")
								.append("<p>"+'.json_encode($select_date_end).'+"</p>")
								.append("<p>"+'.json_encode($input_note).'+"</p>")
								.append("<p>"+'.json_encode($select_company).'+"</p>")
								.append("<p>"+'.json_encode($select_contact).'+"</p>")
								.append("<p>"+'.json_encode($select_user).'+"</p>")
								.append("<p>"+'.json_encode($select_service).'+"</p>")
								.append('.json_encode($TExtraToPrint).');					
								
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
	
	'.(!empty($conf->global->FULLCALENDARSCHEDULER_ROW_HEIGHT) ? '.fc-agendaWeek-view tr { height: '.$conf->global->FULLCALENDARSCHEDULER_ROW_HEIGHT.'; }' : '').'
</style>
';

$parameters=array();
$reshook=$hookmanager->executeHooks('addMoreContent', $parameters, $object, $action);







dol_fiche_end();

llxFooter();
$db->close();