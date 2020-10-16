<?php
/*
 * Copyright (C) 2012-2016 Florian Henry <florian.henry@open-concept.pro>
 * Copyright (C) 2012		JF FERRY	<jfefe@aternatik.fr>
 * Copyright (C) 2014-2015 Philippe Grand  <philippe.grand@atoo-net.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file agefodd/lib/agefodd.lib.php
 * \ingroup agefodd
 * \brief Some display function
 */
dol_include_once('/core/lib/files.lib.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_stagiaire_certif.class.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur_calendrier.class.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue_modules.class.php');
dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');

$langs->load('agefodd@agefodd');

/**
 * Return head table for training tabs screen
 *
 * @param object $object training
 * @return array head table of tabs
 *
 */
function training_prepare_head($object)
{
	global $langs, $conf, $user, $db;

	$h = 0;
	$head = array();

	$head [$h] [0] = dol_buildpath('/agefodd/training/card.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Card");
	$head [$h] [2] = 'card';
	$hselected = $h;
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/session/list.php', 1) . '?training_view=1&search_training_ref=' . urlencode($object->ref_obj);
	$head [$h] [1] = $langs->trans("AgfMenuSess");

	$sess = new Agsession($db);
	$TbadgeNbSess = array();
	$filt['s.fk_formation_catalogue'] = $object->id;
	$badgeNbSess = $sess->fetch_all('', '', 0, 0, $filt);
	if ($badgeNbSess > 0) {
		foreach ($sess->lines as $key => $val) {
			$TbadgeNbSess[$val->id] = $val->id;
		}
	}
	if (!empty($badgeNbSess))
		$head [$h] [1] .= " <span class='badge'>" . count($TbadgeNbSess) . "</span>";

	$head [$h] [2] = 'sessions';
	$hselected = $h;
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/training/training_adm.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("AgftrainingAdmTask");
	$head [$h] [2] = 'trainingadmtask';
	$hselected = $h;
	$h++;

	if ($conf->global->AGF_FILTER_TRAINER_TRAINING) {
		$head [$h] [0] = dol_buildpath('/agefodd/training/trainer.php', 1) . '?id=' . $object->id;
		$head [$h] [1] = $langs->trans("AgfTrainingTrainer");
		$badgenbform = $object->fetchTrainer();
		if (!empty($badgenbform))
			$head [$h] [1] .= " <span class='badge'>" . $badgenbform . "</span>";
		$head [$h] [2] = 'trainingtrainer';
		$hselected = $h;
		$h++;
	}

	if (!empty($conf->global->AGF_USE_TRAINING_MODULE)) {
		$head [$h] [0] = dol_buildpath('/agefodd/training/modules.php', 1) . '?id=' . $object->id;
		$head [$h] [1] = $langs->trans("AgfTrainingModule");
	}

	$object_modules = new Agefoddformationcataloguemodules($db);
	$badgeNbModules = $object_modules->fetchAll('ASC', 'sort_order', 0, 0, array('t.fk_formation_catalogue' => $object->id));
	if (!empty($badgeNbModules))
		$head [$h] [1] .= " <span class='badge'>" . $badgeNbModules . "</span>";

	$head [$h] [2] = 'trainingmodule';
	$hselected = $h;
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/training/note.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("AgfCatalogNote");
	$nbNotes = 0;
	if (!empty($object->note_private))
		$nbNotes++;
	if (!empty($object->note_public))
		$nbNotes++;
	if (!empty($nbNotes))
		$head[$h][1] .= ' <span class="badge">' . $nbNotes . '</span>';
	$head [$h] [2] = 'notes';
	$hselected = $h;
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/training/document_files.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Documents");
	$badgeFiles = countFiles($object);
	if (!empty($badgeFiles))
		$head [$h] [1] .= " <span class='badge'>" . $badgeFiles . "</span>";
	$head [$h] [2] = 'documentfiles';
	$hselected = $h;
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/training/info.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Info");
	$head [$h] [2] = 'info';
	$hselected = $h;
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_training');

	return $head;
}

/**
 * Return head table for session tabs screen
 *
 * @param object $object session
 * @param int $showconv if convention tabs have to be shown
 * @return array head table of tabs
 */
function session_prepare_head($object, $showconv = 0)
{
	global $langs, $conf, $user, $db;

	$h = 0;
	$head = array();

	if ($showconv) {
		$id = $object->sessid;
	} else {
		$id = $object->id;
	}

	$head [$h] [0] = dol_buildpath('/agefodd/session/card.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("Card");
	$head [$h] [2] = 'card';
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/session/calendar.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("AgfCalendrier");
	$session_calendar = new Agefodd_sesscalendar($db);
	$badgeNbCal = $session_calendar->fetch_all($id);
	if (!empty($badgeNbCal))
		$head [$h] [1] .= " <span class='badge'>" . $badgeNbCal . "</span>";

	$head [$h] [2] = 'calendar';
	$h++;

	if (!empty($conf->fullcalendarscheduler->enabled)) {
		$head [$h] [0] = dol_buildpath('/agefodd/session/scheduler.php', 1) . '?id=' . $id;
		$head [$h] [1] = $langs->trans("AgfScheduler");
		$head [$h] [2] = 'scheduler';
		$h++;
	}

	$head [$h] [0] = dol_buildpath('/agefodd/session/planningpertrainee.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("AgfPlanningPerTrainee");
	$head [$h] [2] = 'planningpertrainee';
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/session/subscribers.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("AgfParticipant");

	$stagiaires = new Agefodd_session_stagiaire($db);
	$badgenbtrainee = $stagiaires->fetch_stagiaire_per_session($id);
	if (!empty($badgenbtrainee))
		$head [$h] [1] .= " <span class='badge'>" . $badgenbtrainee . "</span>";

	$head [$h] [2] = 'subscribers';
	$h++;

	if ($conf->global->AGF_MANAGE_CERTIF) {
		$head [$h] [0] = dol_buildpath('/agefodd/session/subscribers_certif.php', 1) . '?id=' . $id;
		$head [$h] [1] = $langs->trans("AgfCertificate");

		$agf_certif = new Agefodd_stagiaire_certif($db);
		$badgeNbCertif = $agf_certif->fetch_all('', '', 0, 0, array('t.fk_session_agefodd' => $id));
		if (!empty($badgeNbCertif))
			$head [$h] [1] .= " <span class='badge'>" . $badgeNbCertif . "</span>";

		$head [$h] [2] = 'certificate';
		$h++;
	}

	if (!$user->rights->agefodd->session->trainer) {
		$head [$h] [0] = dol_buildpath('/agefodd/session/trainer.php', 1) . '?action=edit&id=' . $id;
		$head [$h] [1] = $langs->trans("AgfFormateur");

		$formateurs = new Agefodd_session_formateur($db);
		$badgenbform = $formateurs->fetch_formateur_per_session($id);
		if (!empty($badgenbform))
			$head [$h] [1] .= " <span class='badge'>" . $badgenbform . "</span>";
		$head [$h] [2] = 'trainers';
		$h++;
	}

	/*$head[$h][0] = DOL_URL_ROOT.'/agefodd/s_fpresence.php?id='.$object->id;
	 $head[$h][1] = $langs->trans("AgfFichePresence");
	$head[$h][2] = 'presence';
	$h++;*/
	// TODO fiche de presence

	if (!$user->rights->agefodd->session->trainer) {
		$head [$h] [0] = dol_buildpath('/agefodd/session/administrative.php', 1) . '?id=' . $id;
		$head [$h] [1] = $langs->trans("AgfAdmSuivi");
		$head [$h] [2] = 'administrative';
		$h++;
	}

	$head [$h] [0] = dol_buildpath('/agefodd/session/document.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("AgfLinkedDocuments");
	$head [$h] [2] = 'document';
	$h++;

	if (!$user->rights->agefodd->session->trainer) {
		$head [$h] [0] = dol_buildpath('/agefodd/session/document_trainee.php', 1) . '?id=' . $id;
		$head [$h] [1] = $langs->trans("AgfLinkedDocumentsByTrainee");
		$head [$h] [2] = 'document_trainee';
		$h++;
	}

	if (!$user->rights->agefodd->session->trainer) {
		$head [$h] [0] = dol_buildpath('/agefodd/session/history.php', 1) . '?id=' . $id;
		$head[$h][1] .= $langs->trans("Events");
		if (!empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read))) {
			$head[$h][1] .= '/';
			$head[$h][1] .= $langs->trans("Agenda");
		}
		$head [$h] [2] = 'agenda';
		$h++;
	}

	$head [$h] [0] = dol_buildpath('/agefodd/session/document_files.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("Documents");
	$badgeFiles = countFiles($object);
	if (!empty($badgeFiles))
		$head [$h] [1] .= " <span class='badge'>" . $badgeFiles . "</span>";
	$head [$h] [2] = 'documentfiles';
	$h++;

	if ($showconv) {
		$head [$h] [0] = dol_buildpath('/agefodd/session/convention.php', 1) . '?id=' . $object->id . '&sessid=' . $object->sessid;
		$head [$h] [1] = $langs->trans("AgfConvention");
		$head [$h] [2] = 'convention';
		$h++;
	}

	if (!empty($conf->global->AGF_ADVANCE_COST_MANAGEMENT) && (!$user->rights->agefodd->session->trainer)) {

		$head [$h] [0] = dol_buildpath('/agefodd/session/cost.php', 1) . '?id=' . $id;
		$head [$h] [1] = $langs->trans("AgfCostManagement");
		$head [$h] [2] = 'cost';
		$h++;
	}

	$head [$h] [0] = dol_buildpath('/agefodd/session/info.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("Info");
	$head [$h] [2] = 'info';
	$h++;


	if (!empty($conf->questionnaire->enabled)) {
		$langs->load("questionnaire@questionnaire");
		$head [$h] [0] = dol_buildpath('/agefodd/session/questionnaire.php', 1) . '?id=' . $id;
		$head [$h] [1] = $langs->trans("agfQuestionnaireTabTitle");
		$head [$h] [2] = 'survey';
		$h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_session');

	return $head;
}

/**
 * Return head table for trainee tabs screen
 *
 * @param object $object trainee
 * @return array head table of tabs
 */
function trainee_prepare_head($object, $showcursus = 0)
{
	global $langs, $conf, $user, $db;

	$h = 0;
	$head = array();

	$head [$h] [0] = dol_buildpath('/agefodd/trainee/card.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Card");
	$head [$h] [2] = 'card';
	$h++;

	if ($conf->global->AGF_MANAGE_CERTIF) {
		$head [$h] [0] = dol_buildpath('/agefodd/trainee/certificate.php', 1) . '?id=' . $object->id;
		$head [$h] [1] = $langs->trans("AgfCertificate");

		$agf_certif = new Agefodd_stagiaire_certif($db);
		$badgeNbCertif = $agf_certif->fetch_all_by_trainee($object->id);
		if (!empty($badgeNbCertif))
			$head [$h] [1] .= " <span class='badge'>" . $badgeNbCertif . "</span>";

		$head [$h] [2] = 'certificate';
		$h++;
	}

	$head [$h] [0] = dol_buildpath('/agefodd/trainee/session.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("AgfSessionDetail");

	$sess = new Agsession($db);
	$badgeNbSess = $sess->fetch_session_per_trainee($object->id);
	if (!empty($badgeNbSess))
		$head [$h] [1] .= " <span class='badge'>" . $badgeNbSess . "</span>";

	$head [$h] [2] = 'sessionlist';
	$h++;

	if ($conf->global->AGF_MANAGE_CURSUS) {
		$head [$h] [0] = dol_buildpath('/agefodd/trainee/cursus.php', 1) . '?id=' . $object->id;
		$head [$h] [1] = $langs->trans("AgfMenuCursus");
		$head [$h] [2] = 'cursus';
		$h++;

		if (!empty($showcursus)) {
			$head [$h] [0] = dol_buildpath('/agefodd/trainee/cursus_detail.php', 1) . '?id=' . $object->id . '&cursus_id=' . $object->cursus_id;
			$head [$h] [1] = $langs->trans("AgfCursusDetail");
			$head [$h] [2] = 'cursusdetail';
			$h++;
		}
	}

	$head [$h] [0] = dol_buildpath('/agefodd/trainee/document_files.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Documents");
	$badgeFiles = countFiles($object);
	if (!empty($badgeFiles))
		$head [$h] [1] .= " <span class='badge'>" . $badgeFiles . "</span>";
	$head [$h] [2] = 'documentfiles';
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/trainee/info.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Info");
	$head [$h] [2] = 'info';
	$h++;

	if (!empty($conf->sendinblue->enabled)) {
		$head [$h] [0] = dol_buildpath('/agefodd/sendinblue/trainee_tab.php', 1) . '?id=' . $object->id;
		$head [$h] [1] = $langs->trans("Sendinblue");
		$head [$h] [2] = 'seninblue';
		$h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_trainee');

	return $head;
}

/**
 * Return head table for trainer tabs screen
 *
 * @param object $object trainer
 * @return array head table of tabs
 */
function trainer_prepare_head($object)
{
	global $langs, $conf, $user, $db;

	$h = 0;
	$head = array();

	$head [$h] [0] = dol_buildpath('/agefodd/trainer/card.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Card");
	$head [$h] [2] = 'card';
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/trainer/session.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("AgfSessionDetail");

	$sess = new Agsession($db);
	$badgeNbSess = $sess->fetch_session_per_trainer($object->id);
	if (!empty($badgeNbSess))
		$head [$h] [1] .= " <span class='badge'>" . $badgeNbSess . "</span>";

	$head [$h] [2] = 'sessionlist';
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/trainer/document_files.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Documents");
	$badgeFiles = countFiles($object);
	if (!empty($badgeFiles))
		$head [$h] [1] .= " <span class='badge'>" . $badgeFiles . "</span>";
	$head [$h] [2] = 'documentfiles';
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/trainer/info.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Info");
	$head [$h] [2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_trainer');

	return $head;
}

/**
 * Return head table for contact tabs screen
 *
 * @param object $object contact
 * @return array head table of tabs
 */

function agefodd_contact_prepare_head($object)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head [$h] [0] = dol_buildpath('/agefodd/contact/card.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Card");
	$head [$h] [2] = 'card';
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/contact/info.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Info");
	$head [$h] [2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_contact');

	return $head;
}

/**
 * Return head table for site tabs screen
 *
 * @param object $object site
 * @return array head table of tabs
 */
function site_prepare_head($object)
{
	global $langs, $conf, $user, $db;

	$h = 0;
	$head = array();

	$head [$h] [0] = dol_buildpath('/agefodd/site/card.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Card");
	$head [$h] [2] = 'card';
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/site/reg_int.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("AgfRegInt");
	$head [$h] [2] = 'reg_int_tab';
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/site/document_files.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Documents");
	$badgeFiles = countFiles($object);
	if (!empty($badgeFiles))
		$head [$h] [1] .= " <span class='badge'>" . $badgeFiles . "</span>";
	$head [$h] [2] = 'documentfiles';
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/session/list.php', 1) . '?site_view=1&search_site=' . $object->id;
	$head [$h] [1] = $langs->trans("AgfMenuSess");

	$sess = new Agsession($db);
	$filt['s.fk_session_place'] = $object->id;
	$badgeNbSess = $sess->fetch_all('', '', 0, 0, $filt);
	if (!empty($badgeNbSess))
		$head [$h] [1] .= " <span class='badge'>" . $badgeNbSess . "</span>";

	$head [$h] [2] = 'sessions';
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/site/info.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Info");
	$head [$h] [2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_site');

	return $head;
}

/**
 * Return head table for program tabs screen
 *
 * @param object $object program
 * @return array head table of tabs
 */
function cursus_prepare_head($object)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head [$h] [0] = dol_buildpath('/agefodd/cursus/card.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Card");
	$head [$h] [2] = 'card';
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/cursus/card_trainee.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("AgfMenuActStagiaire");
	$head [$h] [2] = 'trainee';
	$h++;

	$head [$h] [0] = dol_buildpath('/agefodd/cursus/info.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Info");
	$head [$h] [2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_cursus');

	return $head;
}

/**
 * Return head table for admin tabs screen
 *
 * @return array head table of tabs
 */
function agefodd_admin_prepare_head()
{
	global $langs, $conf;

	$langs->load("agefodd@agefodd");

	$h = 0;
	$head = array();

	$head [$h] [0] = dol_buildpath("/agefodd/admin/admin_agefodd.php", 1);
	$head [$h] [1] = '<i class="fa fa-graduation-cap" aria-hidden="true"></i> ' . $langs->trans("Settings");
	$head [$h] [2] = 'settings';
	$h++;

	$head [$h] [0] = dol_buildpath("/agefodd/admin/admin_options.php", 1);
	$head [$h] [1] = '<i class="fa fa-cogs" aria-hidden="true"></i> ' . $langs->trans("Options");
	$head [$h] [2] = 'options';
	$h++;

	$head [$h] [0] = dol_buildpath("/agefodd/admin/admin_administrativetasks.php", 1);
	$head [$h] [1] = '<i class="fa fa-tasks" aria-hidden="true"></i> ' . $langs->trans("AgftrainingAdmTask");
	$head [$h] [2] = 'administrativetasks';
	$h++;

	$head [$h] [0] = dol_buildpath("/agefodd/admin/admin_session_time.php", 1);
	$head [$h] [1] = '<i class="fa fa-clock-o" aria-hidden="true"></i> ' . $langs->trans("AgfAdmSessionTime");
	$head [$h] [2] = 'sessiontime';
	$h++;

	$head [$h] [0] = dol_buildpath("/agefodd/admin/formation_catalogue_extrafields.php", 1);
	$head [$h] [1] = '<i class="fa fa-list" aria-hidden="true"></i> ' . $langs->trans("ExtraFieldsTraining");
	$head [$h] [2] = 'attributetraining';
	$h++;

	$head [$h] [0] = dol_buildpath("/agefodd/admin/session_extrafields.php", 1);
	$head [$h] [1] = '<i class="fa fa-list" aria-hidden="true"></i> ' . $langs->trans("ExtraFieldsSessions");
	$head [$h] [2] = 'attributesession';
	$h++;

	if (!empty($conf->global->AGF_MANAGE_CURSUS)) {
		$head [$h] [0] = dol_buildpath("/agefodd/admin/cursus_extrafields.php", 1);
		$head [$h] [1] = $langs->trans("ExtraFieldsCursus");
		$head [$h] [2] = 'attributecursus';
		$h++;
	}

	$head [$h] [0] = dol_buildpath("/agefodd/admin/stagiaire_extrafields.php", 1);
	$head [$h] [1] = '<i class="fa fa-list" aria-hidden="true"></i> ' . $langs->trans("ExtraFieldsTrainee");
	$head [$h] [2] = 'attributetrainee';
	$h++;

	$head [$h] [0] = dol_buildpath("/agefodd/admin/admin_catcost.php", 1);
	$head [$h] [1] = '<i class="fa fa-money" aria-hidden="true"></i> ' . $langs->trans("AgfCategCostCateg");
	$head [$h] [2] = 'catcost';
	$h++;
	if (!empty($conf->multicompany->enabled)) {
		$head [$h] [0] = dol_buildpath("/agefodd/admin/multicompany_agefodd.php", 1);
		$head [$h] [1] = $langs->trans("AgefoddSetupMulticompany");
		$head [$h] [2] = 'multicompany';
		$h++;
	}
	if (!empty($conf->global->AGF_MANAGE_BPF)) {
		$head [$h] [0] = dol_buildpath("/agefodd/admin/admin_catbpf.php", 1);
		$head [$h] [1] = $langs->trans("AgfReportBPFCategTabTitle");
		$head [$h] [2] = 'catbpf';
		$h++;
	}
	if (!empty($conf->externalaccess->enabled)) {
		$head [$h] [0] = dol_buildpath("/agefodd/admin/admin_external.php", 1);
		$head [$h] [1] = '<i class="fa fa-globe" aria-hidden="true"></i> ' . $langs->trans("AgfExternalAccess");
		$head [$h] [2] = 'external';
		$h++;
	}

	$head [$h] [0] = dol_buildpath("/agefodd/admin/about.php", 1);
	$head [$h] [1] = $langs->trans("About");
	$head [$h] [2] = 'about';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd');

	return $head;
}

/**
 * Define head array for tabs of agenda setup pages
 *
 * @param string $param add to url
 * @return array Array of head
 */
function agf_calendars_prepare_head($param)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	if ($user->rights->agefodd->agenda) {
		$head[$h][0] = dol_buildpath("/agefodd/agenda/index.php", 1) . '?action=show_month' . ($param ? '&' . $param : '');
		$head[$h][1] = $langs->trans("AgfMenuAgenda");
		$head[$h][2] = 'cardmonth';
		$h++;
	}

	if ($user->rights->agefodd->agenda) {
		$head[$h][0] = dol_buildpath("/agefodd/agenda/index.php", 1) . '?action=show_week' . ($param ? '&' . $param : '');
		$head[$h][1] = $langs->trans("AgfMenuAgendaViewWeek");
		$head[$h][2] = 'cardweek';
		$h++;
	}

	if ($user->rights->agefodd->agenda) {
		$head[$h][0] = dol_buildpath("/agefodd/agenda/index.php", 1) . '?action=show_day' . ($param ? '&' . $param : '');
		$head[$h][1] = $langs->trans("AgfMenuAgendaViewDay");
		$head[$h][2] = 'cardday';
		$h++;
	}

	if ($user->rights->agefodd->agendatrainer) {
		$head[$h][0] = dol_buildpath("/agefodd/agenda/pertrainer.php", 1) . ($param ? '?' . $param : '');
		$head[$h][1] = $langs->trans("AgfMenuAgendaViewPerUser");
		$head[$h][2] = 'cardperuser';
		$h++;
	}

	if ($user->rights->agefodd->agenda) {
		$head[$h][0] = dol_buildpath("/agefodd/agenda/listactions.php", 1) . ($param ? '?' . $param : '');
		$head[$h][1] = $langs->trans("AgfMenuAgendaViewList");
		$head[$h][2] = 'cardlist';
		$h++;
	}

	if ($user->rights->agefodd->agendalocation) {
		$head[$h][0] = dol_buildpath("/agefodd/agenda/perlocation.php", 1) . ($param ? '?' . $param : '');
		$head[$h][1] = $langs->trans("AgfMenuAgendaPerLocation");
		$head[$h][2] = 'cardperlocation';
		$h++;
	}

	$object = new stdClass();

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_agenda');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_agenda', 'remove');

	return $head;
}


/**
 * Define head array for tabs of revenue report
 *
 * @return array Array of head
 */
function agf_report_revenue_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/agefodd/report/report_ca.php", 1);
	$head[$h][1] = $langs->trans("AgfMenuReportCA");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = dol_buildpath("/agefodd/report/report_ca_help.php", 1);
	$head[$h][1] = $langs->trans("Help");
	$head[$h][2] = 'help';
	$h++;

	$object = new stdClass();

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_report_revenue');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_report_revenue', 'remove');

	return $head;
}


/**
 * Define head array for tabs of revenue ventilated report
 *
 * @return array Array of head
 */
function agf_report_revenue_ventilated_prepare_head() {
	global $langs, $conf, $user;

	$h = 0;
	$head = array ();

	$head[$h][0] = dol_buildpath("/agefodd/report/report_ca_ventilated.php", 1);
	$head[$h][1] = $langs->trans("AgfMenuReportCAVentilated");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = dol_buildpath("/agefodd/report/report_ca_ventilated_help.php", 1);
	$head[$h][1] = $langs->trans("Help");
	$head[$h][2] = 'help';
	$h++;

	$object=new stdClass();

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'agefodd_report_revenue_ventilated');

	complete_head_from_modules($conf,$langs,$object,$head,$h,'agefodd_report_revenue_ventilated','remove');

	return $head;
}

/**
 * Define head array for tabs of customer report
 *
 * @return array Array of head
 */
function agf_report_by_customer_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/agefodd/report/report_by_customer.php", 1);
	$head[$h][1] = $langs->trans("AgfMenuReportByCustomer");
	$head[$h][2] = 'AgfMenuReportByCustomer';
	$h++;

	$head[$h][0] = dol_buildpath("/agefodd/report/report_by_customer_help.php", 1);
	$head[$h][1] = $langs->trans("Help");
	$head[$h][2] = 'help';
	$h++;

	$object = new stdClass();

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'AgfMenuReportByCustomer');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'AgfMenuReportByCustomer', 'remove');

	return $head;
}

function agf_report_calendar_by_customer_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/agefodd/report/report_calendar_by_customer.php", 1);
	$head[$h][1] = $langs->trans("AgfMenuReportCalendarByCustomer");
	$head[$h][2] = 'AgfMenuReportCalendarByCustomer';
	$h++;

	$head[$h][0] = dol_buildpath("/agefodd/report/report_calendar_by_customer_help.php", 1);
	$head[$h][1] = $langs->trans("Help");
	$head[$h][2] = 'help';
	$h++;

	$object = new stdClass();

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'AgfMenuReportCalendarByCustomer');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'AgfMenuReportCalendarByCustomer', 'remove');

	return $head;
}


/**
 * Define head array for tabs of commercial report
 *
 * @return array Array of head
 */
function agf_report_commercial_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/agefodd/report/report_commercial.php", 1);
	$head[$h][1] = $langs->trans("AgfMenuReport");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = dol_buildpath("/agefodd/report/report_commercial_help.php", 1);
	$head[$h][1] = $langs->trans("Help");
	$head[$h][2] = 'help';
	$h++;

	$object = new stdClass();

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_report_commercial');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_report_commercial', 'remove');

	return $head;
}

/**
 * Define head array for tabs of commercial report
 *
 * @return array Array of head
 */
function agf_report_time_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/agefodd/report/report_time.php", 1);
	$head[$h][1] = $langs->trans("AgfMenuReportTime");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = dol_buildpath("/agefodd/report/report_time_help.php", 1);
	$head[$h][1] = $langs->trans("Help");
	$head[$h][2] = 'help';
	$h++;

	$object = new stdClass();

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_report_time');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_report_time', 'remove');

	return $head;
}


/**
 *  renvoi le nombre de fichiers joints
 */
function countFiles(&$object)
{
	global $conf;

	switch ($object->element) {
		case 'agefodd_formation_catalogue' :
			$upload_dir = $conf->agefodd->dir_output . "/training/" . $object->id;
			break;

		case 'agefodd_place' :
			$upload_dir = $conf->agefodd->dir_output . "/place/" . $object->id;
			break;

		case 'agefodd_formateur' :
			$upload_dir = $conf->agefodd->dir_output . "/trainer/" . $object->id;
			break;

		case 'agefodd' :
			$upload_dir = $conf->agefodd->dir_output . "/trainee/" . $object->id;
			break;

		Default :
			$upload_dir = $conf->agefodd->dir_output . "/" . $object->id;
	}
	if ($object->element == "agefodd_agsession")
		$upload_dir = $conf->agefodd->dir_output . "/" . $object->id;

	$filearray = dol_dir_list($upload_dir, "files");

	return count($filearray);
}

/**
 * Calcule le nombre de regroupement par premier niveau des tâches adminsitratives
 *
 * @return int nbre de niveaux
 */
function ebi_get_adm_level_number()
{
	global $db;

	$sql = "SELECT l.rowid, l.level_rank";
	$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_admlevel as l";
	$sql .= " WHERE l.level_rank = 0";

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$db->free($result);
		return $num;
	} else {
		$error = "Error " . $db->lasterror();
		return -1;
	}
}

/**
 * Calcule le nombre de regroupement par premier niveau des tâches adminsitratives
 *
 * @return int nbre de niveaux
 */
function ebi_get_adm_training_level_number()
{
	global $db;

	$sql = "SELECT l.rowid, l.level_rank";
	$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_training_admlevel as l";
	$sql .= " WHERE l.level_rank = 0";

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$db->free($result);
		return $num;
	} else {
		$error = "Error " . $db->lasterror();
		return -1;
	}
}

/**
 * Calcule le nombre de regroupement par premier niveau des tâches par session
 *
 * @param int $session de la session
 * @return int nbre de niveaux
 */
function ebi_get_level_number($session)
{
	global $db;

	$sql = "SELECT l.rowid, l.level_rank";
	$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as l";
	$sql .= " WHERE l.level_rank = 0 AND l.fk_agefodd_session=" . $session;

	dol_syslog("ebi_get_level_number sql=" . $sql, LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$db->free($result);
		return $num;
	} else {
		$error = "Error " . $db->lasterror();
		return -1;
	}
}

/**
 * Calcule le nombre de regroupement par premier niveau terminés pour une session donnée
 *
 * @param int $sessid de la session
 * @return int nbre de niveaux
 */
function ebi_get_adm_lastFinishLevel($sessid)
{
	global $db;

	$totaldone = 0;

	$sql = "SELECT rowid";
	$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as s";
	$sql .= ' WHERE s.level_rank =0 ';
	$sql .= " AND fk_agefodd_session = " . $sessid;

	dol_syslog("ebi_get_adm_lastFinishLevel sql=" . $sql, LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		if (!empty($num)) {
			while ($obj = $db->fetch_object($result)) {

				$sqlinner = "SELECT count(*) as cnt";
				$sqlinner .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as s";
				$sqlinner .= ' WHERE s.level_rank <>0 ';
				$sqlinner .= " AND fk_parent_level = " . $obj->rowid . " AND fk_agefodd_session = " . $sessid;
				$sqlinner .= " AND archive = 1";

				dol_syslog("ebi_get_adm_lastFinishLevel sqlinner=" . $sqlinner, LOG_DEBUG);
				$resultinner = $db->query($sqlinner);
				if ($resultinner) {
					$objinner = $db->fetch_object($resultinner);

					$nbtaskdone = $objinner->cnt;

					$db->free($resultinner);
				} else {
					$error = "Error " . $db->lasterror();
					// print $error;
					return -1;
				}

				$sqlinner = "SELECT count(*) as cnt";
				$sqlinner .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as s";
				$sqlinner .= ' WHERE s.level_rank <>0 ';
				$sqlinner .= " AND fk_parent_level = " . $obj->rowid . " AND fk_agefodd_session = " . $sessid;

				dol_syslog("ebi_get_adm_lastFinishLevel sqlinner=" . $sqlinner, LOG_DEBUG);
				$resultinner = $db->query($sqlinner);
				if ($resultinner) {
					$objinner = $db->fetch_object($resultinner);

					$nbtotaltask = $objinner->cnt;

					$db->free($resultinner);
				} else {
					$error = "Error " . $db->lasterror();
					// print $error;
					return -1;
				}

				dol_syslog("ebi_get_adm_lastFinishLevel nbtotaltask=" . $nbtotaltask, LOG_DEBUG);
				// No child check status
				if ($nbtotaltask == 0) {
					$sqlinner = "SELECT count(*) as cnt";
					$sqlinner .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as s";
					$sqlinner .= " WHERE rowid=" . $obj->rowid;
					$sqlinner .= " AND archive = 1";

					dol_syslog("ebi_get_adm_lastFinishLevel sqlinner=" . $sqlinner, LOG_DEBUG);
					$resultinner = $db->query($sqlinner);
					if ($resultinner) {
						$objinner = $db->fetch_object($resultinner);

						$nbtaskdone = $objinner->cnt;

						$db->free($resultinner);
					} else {
						$error = "Error " . $db->lasterror();
						// print $error;
						return -1;
					}

					$sqlinner = "SELECT count(*) as cnt";
					$sqlinner .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as s";
					$sqlinner .= " WHERE rowid=" . $obj->rowid;

					dol_syslog("ebi_get_adm_lastFinishLevel sqlinner=" . $sqlinner, LOG_DEBUG);
					$resultinner = $db->query($sqlinner);
					if ($resultinner) {
						$objinner = $db->fetch_object($resultinner);

						$nbtotaltask = $objinner->cnt;

						$db->free($resultinner);
					} else {
						$error = "Error " . $db->lasterror();
						// print $error;
						return -1;
					}
				}

				dol_syslog("ebi_get_adm_lastFinishLevel nbtaskdone=" . $nbtaskdone . " nbtotaltask=" . $nbtotaltask, LOG_DEBUG);
				// If number task done = nb task to do or no child level
				if (($nbtaskdone == $nbtotaltask))
					$totaldone++;
			}
		}
		$db->free($result);
		dol_syslog("ebi_get_adm_lastFinishLevel totaldone=" . $totaldone, LOG_DEBUG);
		return $totaldone;
	} else {
		$error = "Error " . $db->lasterror();
		// print $error;
		return -1;
	}
}

/**
 * Calcule le nombre de d'action filles
 *
 * @param int $id du niveaux
 * @return int nbre d'action filles
 */
function ebi_get_adm_indice_action_child($id)
{
	global $db;

	$sql = "SELECT MAX(s.indice) as nb_action";
	$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_admlevel as s";
	$sql .= " WHERE fk_parent_level=" . $id;

	dol_syslog("agefodd:lib:ebi_get_adm_indice_action_child sql=" . $sql, LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$obj = $db->fetch_object($result);

		$db->free($result);
		return $obj->nb_action;
	} else {
		$error = "Error " . $db->lasterror();
		return -1;
	}
}

/**
 * Calcule l'indice min ou max d'un niveau
 *
 * @param int $lvl_rank des actions a tester
 * @param int $parent_level parent
 * @param int $type MIN ou MAX
 * @return int indice
 */
function ebi_get_adm_indice_per_rank($lvl_rank, $parent_level = '', $type = 'MIN')
{
	global $db;

	$sql = "SELECT ";
	if ($type == 'MIN') {
		$sql .= ' MIN(s.indice) ';
	} else {
		$sql .= ' MAX(s.indice) ';
	}
	$sql .= " as indice";
	$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_admlevel as s";
	$sql .= " WHERE s.level_rank=" . $lvl_rank;
	if ($parent_level != '') {
		$sql .= " AND s.fk_parent_level=" . $parent_level;
	}

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$obj = $db->fetch_object($result);

		$db->free($result);
		return $obj->indice;
	} else {
		$error = "Error " . $db->lasterror();
		return -1;
	}
}

/**
 * Calcule l'indice min ou max d'un niveau
 *
 * @param int $lvl_rank des actions a tester
 * @param int $parent_level parent
 * @param int $type MIN ou MAX
 * @return int indice
 */
function ebi_get_adm_training_indice_per_rank($lvl_rank, $parent_level = '', $type = 'MIN')
{
	global $db;

	$sql = "SELECT ";
	if ($type == 'MIN') {
		$sql .= ' MIN(s.indice) ';
	} else {
		$sql .= ' MAX(s.indice) ';
	}
	$sql .= " as indice";
	$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_training_admlevel as s";
	$sql .= " WHERE s.level_rank=" . $lvl_rank;
	if ($parent_level != '') {
		$sql .= " AND s.fk_parent_level=" . $parent_level;
	}

	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$obj = $db->fetch_object($result);

		$db->free($result);
		return $obj->indice;
	} else {
		$error = "Error " . $db->lasterror();
		return -1;
	}
}

/**
 * Formatage d'une liste à puce
 *
 * @param string $text chaine
 * @param boolean $form sortie au format html (true) ou texte (false)
 * @return string la chaine formater
 */
function ebi_liste_a_puce($text, $form = false)
{
	// 1er niveau: remplacement de '# ' en debut de ligne par une puce de niv 1 (petit rond noir)
	// 2éme niveau: remplacement de '## ' en début de ligne par une puce de niv 2 (tiret)
	// 3éme niveau: remplacement de '### ' en début de ligne par une puce de niv 3 (>)
	// Pour annuler le formatage (début de ligne sur la mage gauche : '!#'
	$str = "";
	$line = explode("\n", $text);
	$level = 0;
	foreach ($line as $row) {
		if ($form) {
			if (preg_match('/^\!# /', $row)) {
				if ($level == 1)
					$str .= '</ul>' . "\n";
				if ($level == 2)
					$str .= '<ul>' . "\n" . '</ul>' . "\n";
				if ($level == 3)
					$str .= '</ul>' . "\n" . '</ul>' . "\n" . '</ul>' . "\n";
				$str .= preg_replace('/^\!# /', '', $row . '<br />') . "\n";
			} elseif (preg_match('/^# /', $row)) {
				if ($level == 0)
					$str .= '<ul>';
				if ($level == 2)
					$str .= '</ul>' . "\n";
				if ($level == 3)
					$str .= '</ul>' . "\n" . '</ul>' . "\n";
				$str .= '<li>' . preg_replace('/^# /', '', $row) . '</li>' . "\n";
				$level = 1;
			} elseif (preg_match('/^## /', $row)) {
				if ($level == 1)
					$str .= '<ul>';
				if ($level == 3)
					$str .= '</ul>' . "\n";
				$str .= '<li>' . preg_replace('/^## /', '', $row) . '</li>' . "\n";
				$level = 2;
			} elseif (preg_match('/^### /', $row)) {
				if ($level == 2)
					$str .= '<ul>';
				$str .= '<li>' . preg_replace('/^### /', '', $row) . '</li>' . "\n";
				$level = 3;
			} else
				$str .= '   ' . $row . '<br />' . "\n";
		} else {
			if (preg_match('/^\!# /', $row))
				$str .= preg_replace('/^\!# /', '', $row) . "\n";
			elseif (preg_match('/^# /', $row))
				$str .= chr(149) . ' ' . preg_replace('/^#/', '', $row) . "\n";
			elseif (preg_match('/^## /', $row))
				$str .= '   ' . '-' . preg_replace('/^##/', '', $row) . "\n";
			elseif (preg_match('/^### /', $row))
				$str .= '   ' . '  ' . chr(155) . ' ' . preg_replace('/^###/', '', $row) . "\n";
			else
				$str .= '   ' . $row . "\n";
		}
	}
	return $str;
}

/**
 * Calcule le next number d'indice pour une action (ecran conf module)
 *
 * @param int $id du niveaux
 * @return int action next number
 */
function ebi_get_adm_get_next_indice_action($id)
{
	global $db;

	$sql = "SELECT MAX(s.indice) as nb_action";
	$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_admlevel as s";
	$sql .= " WHERE fk_parent_level=" . $id;

	dol_syslog("agefodd:lib:ebi_get_adm_get_next_indice_action sql=" . $sql, LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$obj = $db->fetch_object($result);

		$db->free($result);
		if (!empty($obj->nb_action)) {
			return intval(intval($obj->nb_action) + 1);
		} else {

			$sql = "SELECT s.indice as parentindice FROM " . MAIN_DB_PREFIX . "agefodd_session_admlevel as s";
			$sql .= " WHERE rowid = " . $id;

			dol_syslog("agefodd:lib:ebi_get_adm_get_next_indice_action sql=" . $sql, LOG_DEBUG);
			$result = $db->query($sql);

			if ($result) {
				$obj = $db->fetch_object($result);
				$parentIndice = $obj->parentindice;
				return intval(intval($parentIndice) + 1);
			} else {

				$error = "Error " . $db->lasterror();
				return -1;
			}

		}
	} else {

		$error = "Error " . $db->lasterror();
		return -1;
	}
}

/**
 * Calcule le next number d'indice pour une action (ecran conf module)
 *
 * @param int $id du niveaux
 * @return int action next number
 */
function ebi_get_adm_training_get_next_indice_action($id)
{
	global $db;

	$sql = "SELECT MAX(s.indice) as nb_action";
	$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_training_admlevel as s";
	$sql .= " WHERE fk_parent_level=" . $id;

	dol_syslog("ebi_get_adm_training_get_next_indice_action sql=" . $sql, LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$obj = $db->fetch_object($result);
		$db->free($result);
		if (!empty($obj->nb_action)) {
			return intval(intval($obj->nb_action) + 1);
		} else {
			$sql = "SELECT MAX(s.indice) as nb_action";
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_training_admlevel as s";
			$sql .= " WHERE fk_parent_level=(SELECT fk_parent_level FROM " . MAIN_DB_PREFIX . "agefodd_training_admlevel WHERE rowid=" . $id . ")";

			dol_syslog("ebi_get_adm_training_get_next_indice_action sql=" . $sql, LOG_DEBUG);
			$result = $db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);
				$obj = $db->fetch_object($result);

				$db->free($result);
				return intval(intval($obj->nb_action) + 1);
			} else {

				$error = "Error " . $db->lasterror();
				return -1;
			}
		}
	} else {

		$error = "Error " . $db->lasterror();
		return -1;
	}
}

/**
 * Calcule le next number d'indice pour une action (pour une session)
 *
 * @param int $id du niveaux
 * @param int $sessionid de la session
 * @return int action next number
 */
function ebi_get_next_indice_action($id, $sessionid)
{
	global $db;

	$sql = "SELECT MAX(s.indice) as nb_action";
	$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as s";
	$sql .= " WHERE fk_parent_level=" . $id;
	$sql .= " AND fk_agefodd_session=" . $sessionid;

	dol_syslog("ebi_get_get_next_indice_action sql=" . $sql, LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		$obj = $db->fetch_object($result);
		$db->free($result);
		if (!empty($obj->nb_action)) {
			return intval(intval($obj->nb_action) + 1);
		} else {
			$sql = "SELECT MAX(s.indice) as nb_action";
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as s";
			$sql .= " WHERE fk_parent_level=(SELECT fk_parent_level FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu WHERE rowid=" . $id . " AND fk_agefodd_session=" . $sessionid . ")";
			$sql .= " AND fk_agefodd_session=" . $sessionid;

			dol_syslog("ebi_get_get_next_indice_action sql=" . $sql, LOG_DEBUG);
			$result = $db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);
				$obj = $db->fetch_object($result);

				$db->free($result);
				return intval(intval($obj->nb_action) + 1);
			} else {

				$error = "Error " . $db->lasterror();
				return -1;
			}
		}
	} else {

		$error = "Error " . $db->lasterror();
		return -1;
	}
}

/**
 * Converti un code couleur hexa en tableau des couleurs RGB
 *
 * @param string $hex hexadecimale
 * @return array définition RGB
 */
function agf_hex2rgb($hex)
{
	$hex = str_replace("#", "", $hex);

	if (strlen($hex) == 3) {
		$r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
		$g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
		$b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
	} else {
		$r = hexdec(substr($hex, 0, 2));
		$g = hexdec(substr($hex, 2, 2));
		$b = hexdec(substr($hex, 4, 2));
	}
	$rgb = array(
		$r,
		$g,
		$b
	);
	// return implode(",", $rgb); // returns the rgb values separated by commas
	return $rgb; // returns an array with the rgb values
}

/**
 *  Show footer of page for PDF generation
 *
 * @param PDF            &$pdf The PDF factory
 * @param Translate $outputlangs Object lang for output
 * @param string $paramfreetext Constant name of free text
 * @param Societe $fromcompany Object company
 * @param int $marge_basse Margin bottom we use for the autobreak
 * @param int $marge_gauche Margin left (no more used)
 * @param int $page_hauteur Page height (no more used)
 * @param Object $object Object shown in PDF
 * @param int $showdetails Show company details into footer. This param seems to not be used by standard version.
 * @param int $hidefreetext 1=Hide free text, 0=Show free text
 * @return    int                            Return height of bottom margin including footer text
 */
function pdf_agfpagefoot(&$pdf, $outputlangs, $paramfreetext, $fromcompany, $marge_basse, $marge_gauche, $page_hauteur, $object, $showdetails = 0, $hidefreetext = 1)
{
	global $conf, $user;

	$outputlangs->load("dict");
	$outputlangs->load("companies");
	$line = '';

	$dims = $pdf->getPageDimensions();

	if (!empty($conf->global->AGF_HIDE_DOC_FOOTER)) {
		return 0;
	}

	// Line of free text
	if (empty($hidefreetext) && !empty($conf->global->$paramfreetext)) {
		// Make substitution
		$substitutionarray = array(
			'__FROM_NAME__'  => $fromcompany->nom,
			'__FROM_EMAIL__' => $fromcompany->email,
			'__TOTAL_TTC__'  => $object->total_ttc,
			'__TOTAL_HT__'   => $object->total_ht,
			'__TOTAL_VAT__'  => $object->total_vat
		);
		complete_substitutions_array($substitutionarray, $outputlangs, $object);
		$newfreetext = make_substitutions($conf->global->$paramfreetext, $substitutionarray);
		$line .= $outputlangs->convToOutputCharset($newfreetext);
	}

	// First line of company infos

	if ($showdetails) {
		$line1 = "";
		// Company name
		if ($fromcompany->name) {
			$line1 .= ($line1 ? " - " : "") . $fromcompany->name;
		}

		$line2 = "";
		// Address
		if ($fromcompany->address) {
			$line2 .= ($line2 ? " - " : "") . str_replace(array('<br>', '<br />', "\n", "\r"), array(' ', ' ', ' ', ' '), $fromcompany->address);
		}
		// Zip code
		if ($fromcompany->zip) {
			$line2 .= ($line2 ? " - " : "") . $fromcompany->zip;
		}
		// Town
		if ($fromcompany->town) {
			$line2 .= ($line2 ? " " : "") . $fromcompany->town;
		}
		// country
		if ($fromcompany->country) {
			$line2 .= ($line2 ? " " : "") . $fromcompany->country;
		}
		// Phone
		if ($fromcompany->phone) {
			$line2 .= ($line2 ? " - " : "") . $outputlangs->transnoentities("Tel") . ": " . $fromcompany->phone;
		}
		// Mail
		if ($fromcompany->email) {
			$line2 .= ($line2 ? " - " : "") . $outputlangs->transnoentities("Mail") . ": " . $fromcompany->email;
		}
		// Juridical status
		if ($fromcompany->forme_juridique_code) {
			$line2 .= ($line2 ? " - " : "") . $outputlangs->convToOutputCharset(getFormeJuridiqueLabel($fromcompany->forme_juridique_code));
		}
		// Capital
		if ($fromcompany->capital) {
			$line2 .= ($line2 ? " - " : "") . $outputlangs->transnoentities("CapitalOf", $fromcompany->capital) . " " . $outputlangs->transnoentities("Currency" . $conf->currency);
		}
	}

	// Line 3 of company infos
	$line3 = "";

	if ($fromcompany->idprof1 && ($fromcompany->country_code != 'FR' || !$fromcompany->idprof2)) {
		$field = $outputlangs->transcountrynoentities("ProfId1", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg))
			$field = $reg[1];
		$line3 .= ($line3 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof1);
	}
	// Prof Id 2
	if ($fromcompany->idprof2) {
		$field = $outputlangs->transcountrynoentities("ProfId2", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg))
			$field = $reg[1];
		$line3 .= ($line3 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof2);
	}
	// Prof Id 3
	if ($fromcompany->idprof3) {
		$field = $outputlangs->transcountrynoentities("ProfId3", $fromcompany->country_code);
		if (preg_match('/\((.*)\)/i', $field, $reg))
			$field = $reg[1];
		$line3 .= ($line3 ? " - " : "") . $field . ": " . $outputlangs->convToOutputCharset($fromcompany->idprof3);
	}
	if (!empty($conf->global->AGF_ORGANISME_PREF)) {
		$field = $outputlangs->transnoentities('AgfPDFFoot7', $conf->global->AGF_ORGANISME_NUM) . ' - ' . $outputlangs->transnoentities('AgfPDFFoot8');
		if (preg_match('/(.*)/i', $field, $reg))
			$field = $reg[1];
		$line3 .= ($line3 ? " - " : "") . $field . " " . $conf->global->AGF_ORGANISME_PREF;
	}

	// Line 4 of company infos
	$line4 = "";

	// Prof Id 3
	if ($fromcompany->tva_intra != '') {
		$line4 .= ($line4 ? " - " : "") . $outputlangs->transnoentities("VATIntraShort") . ": " . $outputlangs->convToOutputCharset($fromcompany->tva_intra);
	}

	$pdf->SetFont('', '', 7);
	$pdf->SetDrawColor(224, 224, 224);

	// The start of the bottom of this page footer is positioned according to # of lines
	$freetextheight = 0;
	if ($line)    // Free text
	{
		//$line="eee<br>\nfd<strong>sf</strong>sdf<br>\nghfghg<br>";
		if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT)) {
			$width = 20000;
			$align = 'L';    // By default, ask a manual break: We use a large value 20000, to not have automatic wrap. This make user understand, he need to add CR on its text.
			if (!empty($conf->global->MAIN_USE_AUTOWRAP_ON_FREETEXT)) {
				$width = 200;
				$align = 'C';
			}
			$freetextheight = $pdf->getStringHeight($width, $line);
		} else {
			$freetextheight = pdfGetHeightForHtmlContent($pdf, dol_htmlentitiesbr($line, 1, 'UTF-8', 0));      // New method (works for HTML content)
			//print '<br>'.$freetextheight;exit;
		}
	}

	$marginwithfooter = $marge_basse + $freetextheight + (!empty($line1) ? 3 : 0) + (!empty($line2) ? 3 : 0) + (!empty($line3) ? 3 : 0) + (!empty($line4) ? 3 : 0);
	$posy = $marginwithfooter + 0;

	if ($line)    // Free text
	{
		$pdf->SetXY($dims['lm'], -$posy);
		if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))   // by default
		{
			$pdf->MultiCell(0, 3, $line, 0, $align, 0);
		} else {
			$pdf->writeHTMLCell($pdf->page_largeur - $pdf->margin_left - $pdf->margin_right, $freetextheight, $dims['lm'], $dims['hk'] - $marginwithfooter, dol_htmlentitiesbr($line, 1, 'UTF-8', 0));
		}
		$posy -= $freetextheight;
	}

	$pdf->SetY(-$posy);
	$pdf->line($dims['lm'], $dims['hk'] - $posy, $dims['wk'] - $dims['rm'], $dims['hk'] - $posy);
	$posy--;

	if (!empty($line1)) {
		$pdf->SetFont('', 'B', 7);
		$pdf->SetXY($dims['lm'], -$posy + 4);
		$pdf->MultiCell($dims['wk'] - $dims['rm'], 2, $line1, 0, 'C', 0);
		$posy -= 7;
	}

	if (!empty($line2)) {
		$pdf->SetFont('', 'I', 6);
		$pdf->SetXY($dims['lm'] - 6, -$posy);
		$pdf->MultiCell($dims['wk'] - $dims['rm'], 2, $line2, 0, 'C', 0);
		$posy -= 3;
	}

	if (!empty($line3)) {
		$pdf->SetFont('', 'I', 6);
		$pdf->SetXY($dims['lm'] - 6, -$posy);
		$pdf->MultiCell($dims['wk'] - $dims['rm'], 2, $line3, 0, 'C', 0);
	}

	if (!empty($line4)) {
		$posy -= 3;
		$pdf->SetXY($dims['lm'], -$posy);
		$pdf->MultiCell($dims['wk'] - $dims['rm'], 2, $line4, 0, 'C', 0);
	}

	// Show page nb only on iso languages (so default Helvetica font)
	if (strtolower(pdf_getPDFFont($outputlangs)) == 'helvetica') {
		$pdf->SetXY(-20, -$posy);
		//print 'xxx'.$pdf->PageNo().'-'.$pdf->getAliasNbPages().'-'.$pdf->getAliasNumPage();exit;
		if (empty($conf->global->MAIN_USE_FPDF))
			$pdf->MultiCell(13, 2, $pdf->PageNo() . '/' . $pdf->getAliasNbPages(), 0, 'R', 0);
		else $pdf->MultiCell(13, 2, $pdf->PageNo() . '/{nb}', 0, 'R', 0);
	}

	$posy -= 3;
	$pdf->SetXY($dims['lm'], -$posy);

	return $marginwithfooter;
}

/**
 * Return width to use for Logo onot PDF
 *
 * @param string $logo Full path to logo file to use
 * @param bool $url Image with url (true or false)
 * @return    number
 */
function pdf_getWidthForLogo($logo, $url = false)
{
	$height = 22;
	$maxwidth = 130;
	include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
	$tmp = dol_getImageSize($logo, $url);
	if ($tmp['height']) {
		$width = round($height * $tmp['width'] / $tmp['height']);
		//if ($width > $maxwidth) $height=$height*$maxwidth/$width;
	}
	//print $tmp['width'].' '.$tmp['height'].' '.$width; exit;
	return $width;
}

/**
 * Return a PDF instance object.
 * We create a FPDI instance that instantiate TCPDF.
 *
 * @param string $object Object
 * @param string $instance_letter Instance letters
 * @param string $format Array(width,height). Keep empty to use default setup.
 * @param string $metric Unit of format ('mm')
 * @param string $pagetype 'P' or 'l'
 * @return TCPDF PDF object
 */
function pdf_getInstance_agefodd($session, &$model, $format = '', $metric = 'mm', $pagetype = 'P')
{
	global $conf;

	dol_include_once('/agefodd/class/TCPDFAgegfodd.class.php');

	if ((!file_exists(TCPDF_PATH . 'tcpdf.php') && !class_exists('TCPDFAgefodd')) && !empty($conf->global->MAIN_USE_FPDF)) {
		print 'TCPDF Must be use for this module forget TCPDI or FPDF or other PDF class, plaese contact your admnistrator';
		exit();
	}

	// Define constant for TCPDF
	if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {
		define('K_TCPDF_EXTERNAL_CONFIG', 1); // this avoid using tcpdf_config file
		define('K_PATH_CACHE', DOL_DATA_ROOT . '/admin/temp/');
		define('K_PATH_URL_CACHE', DOL_DATA_ROOT . '/admin/temp/');
		dol_mkdir(K_PATH_CACHE);
		define('K_BLANK_IMAGE', '_blank.png');
		define('PDF_PAGE_FORMAT', 'A4');
		define('PDF_PAGE_ORIENTATION', 'P');
		define('PDF_CREATOR', 'TCPDF');
		define('PDF_AUTHOR', 'TCPDF');
		define('PDF_HEADER_TITLE', 'TCPDF Example');
		define('PDF_HEADER_STRING', "by Dolibarr ERP CRM");
		define('PDF_UNIT', 'mm');
		define('PDF_MARGIN_HEADER', 5);
		define('PDF_MARGIN_FOOTER', 10);
		define('PDF_MARGIN_TOP', 27);
		define('PDF_MARGIN_BOTTOM', 25);
		define('PDF_MARGIN_LEFT', 15);
		define('PDF_MARGIN_RIGHT', 15);
		define('PDF_FONT_NAME_MAIN', 'helvetica');
		define('PDF_FONT_SIZE_MAIN', 10);
		define('PDF_FONT_NAME_DATA', 'helvetica');
		define('PDF_FONT_SIZE_DATA', 8);
		define('PDF_FONT_MONOSPACED', 'courier');
		define('PDF_IMAGE_SCALE_RATIO', 1.25);
		define('HEAD_MAGNIFICATION', 1.1);
		define('K_CELL_HEIGHT_RATIO', 1.25);
		define('K_TITLE_MAGNIFICATION', 1.3);
		define('K_SMALL_RATIO', 2 / 3);
		define('K_THAI_TOPCHARS', true);
		define('K_TCPDF_CALLS_IN_HTML', true);
		define('K_TCPDF_THROW_EXCEPTION_ERROR', false);
	}

	require_once TCPDF_PATH . 'tcpdf.php';

	$pdf = new TCPDFAgefodd($pagetype, $metric, $format);
	$pdf->model = $model;
	$pdf->ref_object = $session;

	// We need to instantiate tcpdi or fpdi object (instead of tcpdf) to use merging features. But we can disable it (this will break all merge features).
	/*if (empty($conf->global->MAIN_DISABLE_TCPDI))
	 require_once TCPDI_PATH . 'tcpdi.php';
	 else if (empty($conf->global->MAIN_DISABLE_FPDI))
	 require_once FPDI_PATH . 'fpdi.php';*/

	// $arrayformat=pdf_getFormat();
	// $format=array($arrayformat['width'],$arrayformat['height']);
	// $metric=$arrayformat['unit'];

	// Protection and encryption of pdf
	/*if (empty($conf->global->MAIN_USE_FPDF) && ! empty($conf->global->PDF_SECURITY_ENCRYPTION))
	 {
	 // Permission supported by TCPDF
	 // - print : Print the document;
	 // - modify : Modify the contents of the document by operations other than those controlled by 'fill-forms', 'extract' and 'assemble';
	 // - copy : Copy or otherwise extract text and graphics from the document;
	 // - annot-forms : Add or modify text annotations, fill in interactive form fields, and, if 'modify' is also set, create or modify interactive form fields (including signature fields);
	 // - fill-forms : Fill in existing interactive form fields (including signature fields), even if 'annot-forms' is not specified;
	 // - extract : Extract text and graphics (in support of accessibility to users with disabilities or for other purposes);
	 // - assemble : Assemble the document (insert, rotate, or delete pages and create bookmarks or thumbnail images), even if 'modify' is not set;
	 // - print-high : Print the document to a representation from which a faithful digital copy of the PDF content could be generated. When this is not set, printing is limited to a low-level representation of the appearance, possibly of degraded quality.
	 // - owner : (inverted logic - only for public-key) when set permits change of encryption and enables all other permissions.
	 //
	 if (class_exists('TCPDI')) $pdf = new TCPDI($pagetype,$metric,$format);
	 else if (class_exists('FPDI')) $pdf = new FPDI($pagetype,$metric,$format);
	 else $pdf = new TCPDF($pagetype,$metric,$format);
	 //$pdf->ref_object= $object;
	 //$pdf->instance_letter= $instance_letter;

	 // For TCPDF, we specify permission we want to block
	 $pdfrights = array('modify','copy');

	 $pdfuserpass = ''; // Password for the end user
	 $pdfownerpass = NULL; // Password of the owner, created randomly if not defined
	 $pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
	 }
	 else
	 {
	 if (class_exists('TCPDI')) $pdf = new TCPDI($pagetype,$metric,$format);
	 else if (class_exists('FPDI')) $pdf = new FPDI($pagetype,$metric,$format);
	 else $pdf = new TCPDF($pagetype,$metric,$format,true, 'UTF-8', false, false);
	 //$pdf->ref_object= $object;
	 $pdf->instance_letter= $instance_letter;
	 }*/

	return $pdf;
}

/**
 * @param $db
 * @param Translate $outputlangs
 * @param $object
 * @param $font_size
 * @param TCPDF $pdf
 * @param $x
 * @param $y
 * @param $align
 * @param bool $noSessRef
 */
function printRefIntForma(&$db, $outputlangs, &$object, $font_size, &$pdf, $x, $y, $align, $noSessRef = false)
{
	global $conf;

	if ($conf->global->AGF_PRINT_INTERNAL_REF_ON_PDF && is_object($object)) {
		$forma_ref_int = null;
		$className = get_class($object);

		if ($className == "Agefodd")
			$forma_ref_int = $object->ref_interne;
		else if ($className == "Agsession") {    // $object est une session
			$agf = new Formation($db);
			$agf->fetch($object->fk_formation_catalogue);
			$forma_ref_int = $agf->ref_interne;
			if (empty($conf->global->AGF_HIDE_DATE_ON_HEADER)) {
				$forma_ref_int .= '(' . $object->libSessionDate() . ')';
				if (!$noSessRef) $forma_ref_int .= ' - ';
			}
			if (!$noSessRef) $forma_ref_int .= "\n".$object->id . '#' . $object->ref ;
		}

		if (!empty($forma_ref_int)) {
			$pdf->SetXY($x, $y);
			$pdf->SetFont(pdf_getPDFFont($outputlangs), '', $font_size);
			$pdf->MultiCell(70, 4, $outputlangs->transnoentities('AgfRefInterne') . ' : ' . $outputlangs->convToOutputCharset($forma_ref_int), 0, $align);
		}
	}
}

function printSessionFieldsWithCustomOrder()
{
	global $conf;

	$customOrder = $conf->global->AGF_CUSTOM_ORDER;

	if (!empty($customOrder)) {
		$TClassName = explode(',', $customOrder);

		$order = '';
		foreach ($TClassName as $className) {
			$order .= '"' . trim($className) . '",';
		}
		$order = substr($order, 0, -1);

		if (!empty($TClassName)) {
			?>
			<script type="text/javascript">

                $(function () {
                    // Correspond aux premières lignes à afficher sur la fiche d'une session de formation
                    var agf_TClass = new Array(<?php print $order ?>); // "agefodd_agsession_extras_"+codeExtrafield, "order_intitule", "order_ref", "order_intituleCusto"

                    /**
                     * @param elem (HTMLElement)
                     * @return int  index of elem’s class in agf_TClass;
                     *              - if elem has more than one class, return the smallest index of those found in agf_TClass
                     *              - if elem has no class in agf_TClass, return agf_TClass.length
                     */
                    let getOrderIndex = function (elem) {
                        // index = agf_TClass.indexOf(elem.className) would be ok if we were certain that <tr> have only one class
                        let index = agf_TClass.findIndex((className) => elem.classList.contains(className));
                        return (index === -1) ? agf_TClass.length : index;
                    };

                    // le '>' est important pour éviter d’avoir les <tr> qui appartiennent à des tableaux imbriqués
                    let Tligne = Array.from(document.querySelectorAll('#session_card > tbody > tr'));
                    // trie les <tr> en fonction de l’ordre de leur(s) classe(s) dans agf_TClass
                    Tligne.sort((elemA, elemB) => getOrderIndex(elemA) - getOrderIndex(elemB));
                    // retire les <tr> de leur parent et les y rajoute dans l’ordre
                    let session_card_tbody = document.querySelector('#session_card > tbody');
                    Tligne.forEach((tr) => {
                        session_card_tbody.removeChild(tr);
                        session_card_tbody.appendChild(tr);
                    });
                });

			</script>
			<?php
		}
	}
}

function dol_agefodd_banner_tab($object, $paramid, $morehtml = '', $shownav = 1, $fieldid = 'rowid', $fieldref = 'ref', $morehtmlref = '', $moreparam = '', $nodbprefix = 0, $morehtmlleft = '', $morehtmlstatus = '', $onlybanner = 0, $morehtmlright = '')
{
	global $conf, $form, $user, $langs, $db;

	dol_include_once('/agefodd/class/html.formagefodd.class.php');
	$formAgefodd = new FormAgefodd($db);

	if (!empty($conf->global->AGF_FORCE_ID_AS_REF))
		$fieldref = 'id';

	$error = 0;

	$maxvisiblephotos = 1;
	$showimage = 1;
	$modulepart = "agefodd";
	if ($object->table_element == 'agefodd_stagiaire') {
		$modulepart = "contact";
	} elseif ($object->table_element == 'agefodd_formateur') {
		$modulepart = "userphoto";
	}

	if ($showimage) {
		if (!empty($object->table_element)) {
			/*
			$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">';
			$phototoshow = $form->showphoto($modulepart,$object,0,0,0,'photoref','small',1,0,$maxvisiblephotos);
			$morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">';
			$morehtmlleft.=$phototoshow;
			$morehtmlleft.='</div>';
			*/
			$phototoshow = '';
			// Check if a preview file is available
			if (in_array($modulepart, array('propal', 'commande', 'facture', 'ficheinter', 'contract', 'supplier_order', 'supplier_proposal', 'supplier_invoice', 'expensereport')) && class_exists("Imagick")) {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir_output = $conf->$modulepart->dir_output . "/";
				if (in_array($modulepart, array('invoice_supplier', 'supplier_invoice'))) {
					$subdir = get_exdir($object->id, 2, 0, 0, $object, $modulepart) . $objectref;
				} else {
					$subdir = get_exdir($object->id, 0, 0, 0, $object, $modulepart) . $objectref;
				}
				$filepath = $dir_output . $subdir . "/";
				$file = $filepath . $objectref . ".pdf";
				$relativepath = $subdir . '/' . $objectref . '.pdf';

				// Define path to preview pdf file (preview precompiled "file.ext" are "file.ext_preview.png")
				$fileimage = $file . '_preview.png';              // If PDF has 1 page
				$fileimagebis = $file . '_preview-0.png';         // If PDF has more than one page
				$relativepathimage = $relativepath . '_preview.png';

				// Si fichier PDF existe
				if (file_exists($file)) {
					$encfile = urlencode($file);
					// Conversion du PDF en image png si fichier png non existant
					if ((!file_exists($fileimage) || (filemtime($fileimage) < filemtime($file)))
						&& (!file_exists($fileimagebis) || (filemtime($fileimagebis) < filemtime($file)))
					) {
						if (empty($conf->global->MAIN_DISABLE_PDF_THUMBS))        // If you experience trouble with pdf thumb generation and imagick, you can disable here.
						{
							$ret = dol_convert_file($file, 'png', $fileimage);
							if ($ret < 0)
								$error++;
						}
					}

					$heightforphotref = 70;
					if (!empty($conf->dol_optimize_smallscreen))
						$heightforphotref = 60;
					// Si fichier png PDF d'1 page trouve
					if (file_exists($fileimage)) {
						$phototoshow = '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
						$phototoshow .= '<img height="' . $heightforphotref . '" class="photo photowithmargin photowithborder" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=apercu' . $modulepart . '&amp;file=' . urlencode($relativepathimage) . '">';
						$phototoshow .= '</div></div>';
					} // Si fichier png PDF de plus d'1 page trouve
					elseif (file_exists($fileimagebis)) {
						$preview = preg_replace('/\.png/', '', $relativepathimage) . "-0.png";
						$phototoshow = '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
						$phototoshow .= '<img height="' . $heightforphotref . '" class="photo photowithmargin photowithborder" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=apercu' . $modulepart . '&amp;file=' . urlencode($preview) . '"><p>';
						$phototoshow .= '</div></div>';
					}
				}
			} else if (!$phototoshow) {
				$phototoshow = $form->showphoto($modulepart, $object, 0, 0, 0, 'photoref', 'small', 1, 0, $maxvisiblephotos);
			}

			if ($object->table_element == 'agefodd_stagiaire' && !empty($object->fk_socpeople)) { // trainee from a contact
				dol_include_once('/contact/class/contact.class.php');

				$contact = new Contact($db);
				$contact->fetch($object->fk_socpeople);
				$phototoshow = $form->showphoto($modulepart, $contact, 0, 0, 0, 'photoref', 'small', 1, 0, $maxvisiblephotos);

			} elseif ($object->table_element == 'agefodd_formateur') {
				if ($object->type_trainer == 'socpeople') {
					dol_include_once('/contact/class/contact.class.php');

					$contact = new Contact($db);
					$contact->fetch($object->fk_socpeople);
					$phototoshow = $form->showphoto($modulepart, $contact, 0, 0, 0, 'photoref', 'small', 1, 0, $maxvisiblephotos);
				} else {
					$u = new User($db);
					$u->fetch($object->fk_user);
					$phototoshow = $form->showphoto($modulepart, $u, 0, 0, 0, 'photoref', 'small', 1, 0, $maxvisiblephotos);
				}
			} elseif ($object->table_element == 'agefodd_place') {
				$phototoshow = '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
				$phototoshow .= img_picto('', 'object_address');
				$phototoshow .= '</div></div>';
			} elseif ($object->table_element == 'agefodd_formation_catalogue') {
				$phototoshow = '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
				$phototoshow .= img_picto('', 'object_label');
				$phototoshow .= '</div></div>';
			} elseif ($object->table_element == 'agefodd_session') {
				$phototoshow = '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref">';
				$phototoshow .= img_picto('', 'object_calendarday');
				$phototoshow .= '</div></div>';
			}

			if ($phototoshow) {
				$morehtmlleft .= '<div class="floatleft inline-block valignmiddle divphotoref">';
				$morehtmlleft .= $phototoshow;
				$morehtmlleft .= '</div>';
			}

		}
	}

	// libstatut
	if ($object->table_element == 'agefodd_formation_catalogue') {

		$morehtmlstatus .= '<span class="statusrefsell">' . $object->getLibStatut(1) . '</span>';

	} elseif ($object->table_element == 'agefodd_session') {

		$morehtmlstatus .= '<div align="right">' . $object->getLibStatut(1) . "<br>";

		require_once(__DIR__ . '/../class/agefodd_sessadm.class.php');
		$sess_adm = new Agefodd_sessadm($db);
		$result = $sess_adm->fetch_all($object->id);

		if ($result > 0)
			$morehtmlstatus .= $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($object->id), ebi_get_level_number($object->id), $langs->trans("AgfAdmLevel"));
		if (!empty($conf->global->AGF_MANAGE_SESSION_CALENDAR_FACTURATION)) {
			$billed = Agefodd_sesscalendar::countBilledshedule($object->id);
			$total = Agefodd_sesscalendar::countTotalshedule($object->id);

			if (empty($total))
				$roundedBilled = 0;
			else $roundedBilled = round($billed * 100 / $total);

			$morehtmlstatus .= displayProgress($roundedBilled, $langs->trans('AgfSheduleBillingState') . " : ", $billed . "/" . $total, '9em');
		}
		if (!$user->rights->agefodd->session->trainer) {
			$morehtmlstatus .= $langs->trans("AgfFormTypeSession") . ' : ' . ($object->type_session ? $langs->trans('AgfFormTypeSessionInter') : $langs->trans('AgfFormTypeSessionIntra')) . "<br>";
		}
		if (!empty($object->fk_product)) {
			dol_include_once('/product/class/product.class.php');
			$prod = new Product($db);
			$result = $prod->fetch($object->fk_product);
			if ($result > 0) {
				$morehtmlstatus .= $langs->trans('AgfProductServiceLinked') . ' : ' . $prod->getNomUrl(1);
			}
		}
		$morehtmlstatus .= '</div>';

	} elseif ($object->table_element == 'agefodd_place') {

		$morehtmlstatus .= '<span class="statusrefsell">' . $object->getLibStatut(1) . '</span>';

	}

	// Other infos
	if ($object->table_element == 'agefodd_formation_catalogue') { // formation catalogue

		$morehtml .= '<a href="' . dol_buildpath('/agefodd/training/list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

		$morehtmlref .= '<div class="refidno">';
		$morehtmlref .= $langs->trans("AgfFormIntitule") . ' : ' . $object->intitule . '<br>';
		$morehtmlref .= $langs->trans("AgfFormRef") . ' : ' . $object->ref_obj;
		if (!empty($object->ref_interne))
			$morehtmlref .= '<br>' . $object->ref_interne;
		$morehtmlref .= '</div>';

	} elseif ($object->table_element == 'agefodd_session') { //session formation

		$morehtml .= '<a href="' . dol_buildpath('/agefodd/session/list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
		$morehtmlref .= '<div class="refidno">';
		dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
		$foramtion = new Formation($db);
		$foramtion->fetch($object->formid);
		$morehtmlref .= $foramtion->getNomUrl('intitule');
		if ($object->intitule_custo !== $foramtion->intitule) {
			$morehtmlref .= '<br/>' . $object->intitule_custo;
		}

		if (!empty($object->fk_soc)) {
			dol_include_once('/societe/class/societe.class.php');
			$soc = new Societe($db);
			$soc->fetch($object->fk_soc);
			$morehtmlref .= '<br>' . $langs->trans("Customer") . ' : ' . $soc->getNomUrl(1);
		}

		if (!empty($object->placeid) && (!$user->rights->agefodd->session->trainer)) {
			$morehtmlref .= '<br>' . $langs->trans("AgfLieu") . ' : ';
			$morehtmlref .= '<a href="' . dol_buildpath('/agefodd/site/card.php', 1) . '?id=' . $object->placeid . '">' . $object->placecode . '</a>';
		}

		if (!empty($object->dated)) {
			$morehtmlref .= '<br>' . $langs->trans("AgfDateDebut") . ' : ' . dol_print_date($object->dated, 'daytext');
		}

		if (!empty($object->datef)) {
			$morehtmlref .= '<br>' . $langs->trans("AgfDateFin") . ' : ' . dol_print_date($object->datef, 'daytext');
		}
		if (!empty($object->datef)) {
			$morehtmlref .= '<br>' . $langs->trans("AgfDuree") . ' : ' . $object->duree_session;
		}
		// var_dump($object);

		$morehtmlref .= '</div>';

	} elseif ($object->table_element == 'agefodd_stagiaire') { // trainee

		$morehtml .= '<a href="' . dol_buildpath('/agefodd/trainee/list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
		$morehtmlref .= '<div class="refidno">';

		$morehtmlref .= $langs->trans('Name') . ' : ';
		if (!empty($object->fk_socpeople)) { // trainee from a contact
			dol_include_once('/contact/class/contact.class.php');

			$contact = new Contact($db);
			$contact->fetch($object->fk_socpeople);
			$morehtmlref .= $contact->getNomUrl(1) . '<br>';
		} else {
			$morehtmlref .= ucfirst($object->prenom) . ' ' . strtoupper($object->nom) . '<br>';
		}

		$morehtmlref .= (!empty($object->thirdparty)) ? $langs->trans('Company') . ' : ' . $object->thirdparty->getNomUrl(1) : '';

		$morehtmlref .= '</div>';

	} elseif ($object->table_element == 'agefodd_formateur') { // trainer

		$morehtml .= '<a href="' . dol_buildpath('/agefodd/trainer/list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

		$morehtmlref .= '<div class="refidno">';
		$morehtmlref .= $langs->trans('Name') . ' : ' . ucfirst(strtolower($object->civilite)) . ' ' . strtoupper($object->name) . ' ' . ucfirst(strtolower($object->firstname));
		$morehtmlref .= '<br>' . $langs->trans('AgfTrainerNature') . ' : ' . $langs->trans('AgfTrainerType' . ucfirst($object->type_trainer));
		$morehtmlref .= '</div>';

	} elseif ($object->table_element == 'agefodd_place') { // Sites

		$morehtml .= '<a href="' . dol_buildpath('/agefodd/site/list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

		$morehtmlref .= '<div class="refidno">';
		$morehtmlref .= $langs->trans("Ref") . ' : ' . $object->ref_interne;

		if (!empty($object->socid)) {
			$soc = new Societe($db);
			$soc->fetch($object->socid);
			$morehtmlref .= '<br>' . $langs->trans("Company") . ' : ' . $soc->getNomUrl(1);
		}

		$morehtmlref .= '</div>';

	}

	if ($conf->multicompany->enabled)
		$object->element = 'agefodd';

	print '<div class="' . ($onlybanner ? 'arearefnobottom ' : 'arearef ') . 'heightref valignmiddle" width="100%">';
	$object->id_contact_ref = $object->id . ' # ' . $object->ref;
	if ($object->table_element == 'agefodd_session') { // to fix navigation that doesn't work
		$tmpref = $object->ref;
		$object->ref = $object->id;
	}
	print $form->showrefnav($object, $paramid, $morehtml, $shownav, $fieldid, 'id_contact_ref', $morehtmlref, $moreparam, $nodbprefix, $morehtmlleft, $morehtmlstatus, $morehtmlright);
	if ($object->table_element == 'agefodd_session')
		$object->ref = $tmpref;
	print '</div><br>';
	print '<div class="underrefbanner clearboth"></div>';
	//print '<div class="underbanner clearboth"></div>';
}

function calcul_margin_percent($cashed_cost, $spend_cost)
{
	global $langs;
	if ($cashed_cost > 0) {
		return price(((($cashed_cost - $spend_cost) * 100) / $cashed_cost), 0, $langs, 1, 0, 1) . '%';
	} else {
		return "n/a";
	}
}

/**
 * @param $agf_calendrier Agefodd_sesscalendar
 * @return Agefoddsessionformateurcalendrier[]
 */
function _getCalendrierFormateurFromCalendrier(Agefodd_sesscalendar &$agf_calendrier)
{
	global $db, $response;

	$TRes = array();

	$sql = 'SELECT agsfc.rowid, agsf.fk_agefodd_formateur FROM ' . MAIN_DB_PREFIX . 'agefodd_session_formateur agsf';
	$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_formateur_calendrier agsfc ON (agsf.rowid = agsfc.fk_agefodd_session_formateur)';
	$sql .= ' WHERE agsf.fk_session = ' . $agf_calendrier->sessid;
	$sql .= ' AND agsfc.heured <= \'' . date('Y-m-d H:i:s', $agf_calendrier->heuref) . '\'';
	$sql .= ' AND agsfc.heuref >= \'' . date('Y-m-d H:i:s', $agf_calendrier->heured) . '\'';

	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$agf_calendrier_formateur = new Agefoddsessionformateurcalendrier($db);
			$agf_calendrier_formateur->fetch($obj->rowid);
			$TRes[] = $agf_calendrier_formateur;
		}
	} else {
		$response->TError[] = $db->lasterror;
	}

	return $TRes;
}

function _getCalendrierFromCalendrierFormateur(&$agf_calendrier_formateur, $strict = true, $return_error = false)
{
	global $db;

	$TRes = array();
	if (empty($agf_calendrier_formateur->id))
		return $TRes;

	$sql = 'SELECT c.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session_calendrier c';
	//	$sql.= ' INNER JOIN '.MAIN_DB_PREFIX.'agefodd_session_formateur_calendrier agsfc ON (agsf.rowid = agsfc.fk_agefodd_session_formateur)';
	$sql .= ' WHERE c.fk_agefodd_session = ' . $agf_calendrier_formateur->sessid;
	if ($strict) {
		$sql .= ' AND c.heured = \'' . date('Y-m-d H:i:s', $agf_calendrier_formateur->heured) . '\'';
		$sql .= ' AND c.heuref = \'' . date('Y-m-d H:i:s', $agf_calendrier_formateur->heuref) . '\'';
	} else {
		$sql .= ' AND c.heured <= \'' . date('Y-m-d H:i:s', $agf_calendrier_formateur->heuref) . '\'';
		$sql .= ' AND c.heuref >= \'' . date('Y-m-d H:i:s', $agf_calendrier_formateur->heured) . '\'';
	}

	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$agf_calendrier = new Agefodd_sesscalendar($db);
			$agf_calendrier->fetch($obj->rowid);
			$TRes[] = $agf_calendrier;
		}
	} else {
		if ($return_error)
			return $db->lasterror();

		exit($db->lasterror());
	}

	return $TRes;
}

function displayProgress($percentProgress = 0, $title = '', $insideDisplay = "", $width = "")
{
	$out = '';

	if (!empty($title))
		$out .= $title;

	$out .= '<div class="agefodd-progress-group"';
	if (!empty($width))
		$out .= ' style="width:' . $width . ';"';
	$out .= '>
           <div class="agefodd-progress">
             <div class="agefodd-progress-bar" style="width: ' . $percentProgress . '%">' . (!empty($insideDisplay) ? $insideDisplay : '') . '</div>
           </div>
         </div><br/>';

	return $out;
}


/**
 * get template model of mail
 * @param $id
 * @return int|Object
 */
function agf_getMailTemplate($id)
{
	global $db;

	$sql = "SELECT rowid as rowid, label, type_template, private, position, topic, content_lines, content, active";
	$sql .= " FROM " . MAIN_DB_PREFIX . "c_email_templates";
	$sql .= " WHERE entity IN (" . getEntity('email_template') . ")";
	$sql .= " AND rowid = " . intval($id);


	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);
		if ($num > 0) {
			return $db->fetch_object($result);
		}
	} else {
		return -1;
	}

	return 0;
}


/**
 * Get all session contacts simple ex: for mailling
 * @param Agsession $agsession
 * @return array
 */
function get_agf_session_mails_infos(Agsession $agsession)
{
    global $db, $langs;

    if (empty($agsession->id)) {
        return 0;
    }

    $emailInfos = array();

    // Recupération des stagiaires
    dol_include_once('agefodd/class/agefodd_session_stagiaire.class.php');

    $session_stagiaire = new Agefodd_session_stagiaire($db);
    $session_stagiaire->fetch_stagiaire_per_session($agsession->id);

    if (!empty($session_stagiaire->lines)) {
        foreach ($session_stagiaire->lines as $sessionLine) {

            $infos = new stdClass();
            $infos->nom = $sessionLine->nom;
            $infos->prenom = $sessionLine->prenom;
            $infos->civilite = $langs->trans($sessionLine->civilitel);
            $infos->socname = $sessionLine->socname;
            $infos->email = $sessionLine->email;

            $emailInfos[] = $infos;
        }
    }


    // Recupération des stagiaires
    dol_include_once('agefodd/class/agefodd_session_formateur.class.php');

    $session_formateur = new Agefodd_session_formateur($db);
    $session_formateur->fetch_formateur_per_session($agsession->id);

    if (!empty($session_formateur->lines)) {
        foreach ($session_formateur->lines as $sessionLine) {


            $infos = new stdClass();
            $infos->nom = $sessionLine->name_user;
            $infos->prenom = $sessionLine->firstname_user;
            $infos->civilite = '';
            $infos->socname = '';
            $infos->email = $sessionLine->email;

            $emailInfos[] = $infos;

        }
    }


    return $emailInfos;

}

