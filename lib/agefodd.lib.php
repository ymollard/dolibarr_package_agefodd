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
$langs->load('agefodd@agefodd');

/**
 * Return head table for training tabs screen
 *
 * @param object $object training
 * @return array head table of tabs
 *
 */
function training_prepare_head($object) {
	global $langs, $conf, $user;

	$h = 0;
	$head = array ();

	$head [$h] [0] = dol_buildpath('/agefodd/training/card.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Card");
	$head [$h] [2] = 'card';
	$hselected = $h;
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/session/list.php', 1) . '?training_view=1&search_training_ref=' . urlencode($object->ref_obj);
	$head [$h] [1] = $langs->trans("AgfMenuSess");
	$head [$h] [2] = 'sessions';
	$hselected = $h;
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/training/training_adm.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("AgftrainingAdmTask");
	$head [$h] [2] = 'trainingadmtask';
	$hselected = $h;
	$h ++;

	if ($conf->global->AGF_FILTER_TRAINER_TRAINING) {
		$head [$h] [0] = dol_buildpath('/agefodd/training/trainer.php', 1) . '?id=' . $object->id;
		$head [$h] [1] = $langs->trans("AgfTrainingTrainer");
		$head [$h] [2] = 'trainingtrainer';
		$hselected = $h;
		$h ++;
	}

	$head [$h] [0] = dol_buildpath('/agefodd/training/modules.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("AgfTrainingModule");
	$head [$h] [2] = 'trainingmodule';
	$hselected = $h;
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/training/note.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("AgfCatalogNote");
	$nbNotes = 0;
	if (!empty($object->note_private)) $nbNotes++;
	if (!empty($object->note_public)) $nbNotes++;
	if (!empty($nbNotes)) $head[$h][1].= ' <span class="badge">'.$nbNotes.'</span>';
	$head [$h] [2] = 'notes';
	$hselected = $h;
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/training/document_files.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Documents");
	$head [$h] [2] = 'documentfiles';
	$hselected = $h;
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/training/info.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Info");
	$head [$h] [2] = 'info';
	$hselected = $h;
	$h ++;

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
function session_prepare_head($object, $showconv = 0) {
	global $langs, $conf, $user;

	$h = 0;
	$head = array ();

	if ($showconv) {
		$id = $object->sessid;
	} else {
		$id = $object->id;
	}

	$head [$h] [0] = dol_buildpath('/agefodd/session/card.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("Card");
	$head [$h] [2] = 'card';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/session/calendar.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("AgfCalendrier");
	$head [$h] [2] = 'calendar';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/session/subscribers.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("AgfParticipant");
	$head [$h] [2] = 'subscribers';
	$h ++;

	if ($conf->global->AGF_MANAGE_CERTIF) {
		$head [$h] [0] = dol_buildpath('/agefodd/session/subscribers_certif.php', 1) . '?id=' . $id;
		$head [$h] [1] = $langs->trans("AgfCertificate");
		$head [$h] [2] = 'certificate';
		$h ++;
	}

	$head [$h] [0] = dol_buildpath('/agefodd/session/trainer.php', 1) . '?action=edit&id=' . $id;
	$head [$h] [1] = $langs->trans("AgfFormateur");
	$head [$h] [2] = 'trainers';
	$h ++;

	/*$head[$h][0] = DOL_URL_ROOT.'/agefodd/s_fpresence.php?id='.$object->id;
	 $head[$h][1] = $langs->trans("AgfFichePresence");
	$head[$h][2] = 'presence';
	$h++;*/
	// TODO fiche de presence

	$head [$h] [0] = dol_buildpath('/agefodd/session/administrative.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("AgfAdmSuivi");
	$head [$h] [2] = 'administrative';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/session/document.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("AgfLinkedDocuments");
	$head [$h] [2] = 'document';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/session/document_trainee.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("AgfLinkedDocumentsByTrainee");
	$head [$h] [2] = 'document_trainee';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/session/send_docs.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("AgfSendDocuments");
	$head [$h] [2] = 'send_docs';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/session/document_files.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("Documents");
	$head [$h] [2] = 'documentfiles';
	$h ++;

	if ($showconv) {
		$head [$h] [0] = dol_buildpath('/agefodd/session/convention.php', 1) . '?id=' . $object->id . '&sessid=' . $object->sessid;
		$head [$h] [1] = $langs->trans("AgfConvention");
		$head [$h] [2] = 'convention';
		$h ++;
	}

	if (! empty($conf->global->AGF_ADVANCE_COST_MANAGEMENT)) {

		$head [$h] [0] = dol_buildpath('/agefodd/session/cost.php', 1) . '?id=' . $id;
		$head [$h] [1] = $langs->trans("AgfCostManagement");
		$head [$h] [2] = 'cost';
		$h ++;
	}

	$head [$h] [0] = dol_buildpath('/agefodd/session/info.php', 1) . '?id=' . $id;
	$head [$h] [1] = $langs->trans("Info");
	$head [$h] [2] = 'info';
	$h ++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_session');

	return $head;
}

/**
 * Return head table for trainee tabs screen
 *
 * @param object $object trainee
 * @return array head table of tabs
 */
function trainee_prepare_head($object, $showcursus = 0) {
	global $langs, $conf, $user;

	$h = 0;
	$head = array ();

	$head [$h] [0] = dol_buildpath('/agefodd/trainee/card.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Card");
	$head [$h] [2] = 'card';
	$h ++;

	if ($conf->global->AGF_MANAGE_CERTIF) {
		$head [$h] [0] = dol_buildpath('/agefodd/trainee/certificate.php', 1) . '?id=' . $object->id;
		$head [$h] [1] = $langs->trans("AgfCertificate");
		$head [$h] [2] = 'certificate';
		$h ++;
	}

	$head [$h] [0] = dol_buildpath('/agefodd/trainee/session.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("AgfSessionDetail");
	$head [$h] [2] = 'sessionlist';
	$h ++;

	if ($conf->global->AGF_MANAGE_CURSUS) {
		$head [$h] [0] = dol_buildpath('/agefodd/trainee/cursus.php', 1) . '?id=' . $object->id;
		$head [$h] [1] = $langs->trans("AgfMenuCursus");
		$head [$h] [2] = 'cursus';
		$h ++;

		if (! empty($showcursus)) {
			$head [$h] [0] = dol_buildpath('/agefodd/trainee/cursus_detail.php', 1) . '?id=' . $object->id . '&cursus_id=' . $object->cursus_id;
			$head [$h] [1] = $langs->trans("AgfCursusDetail");
			$head [$h] [2] = 'cursusdetail';
			$h ++;
		}
	}

	$head [$h] [0] = dol_buildpath('/agefodd/trainee/document_files.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Documents");
	$head [$h] [2] = 'documentfiles';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/trainee/info.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Info");
	$head [$h] [2] = 'info';
	$h ++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_trainee');

	return $head;
}

/**
 * Return head table for trainer tabs screen
 *
 * @param object $object trainer
 * @return array head table of tabs
 */
function trainer_prepare_head($object) {
	global $langs, $conf, $user;

	$h = 0;
	$head = array ();

	$head [$h] [0] = dol_buildpath('/agefodd/trainer/card.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Card");
	$head [$h] [2] = 'card';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/trainer/session.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("AgfSessionDetail");
	$head [$h] [2] = 'sessionlist';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/trainer/document_files.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Documents");
	$head [$h] [2] = 'documentfiles';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/trainer/info.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Info");
	$head [$h] [2] = 'info';
	$h ++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_trainer');

	return $head;
}

/**
 * Return head table for contact tabs screen
 *
 * @param object $object contact
 * @return array head table of tabs
 */
function contact_prepare_head($object) {
	global $langs, $conf, $user;

	$h = 0;
	$head = array ();

	$head [$h] [0] = dol_buildpath('/agefodd/contact/card.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Card");
	$head [$h] [2] = 'card';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/contact/info.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Info");
	$head [$h] [2] = 'info';
	$h ++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_contact');

	return $head;
}

/**
 * Return head table for site tabs screen
 *
 * @param object $object site
 * @return array head table of tabs
 */
function site_prepare_head($object) {
	global $langs, $conf, $user;

	$h = 0;
	$head = array ();

	$head [$h] [0] = dol_buildpath('/agefodd/site/card.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Card");
	$head [$h] [2] = 'card';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/site/reg_int.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("AgfRegInt");
	$head [$h] [2] = 'reg_int_tab';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/site/document_files.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Documents");
	$head [$h] [2] = 'documentfiles';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/session/list.php', 1) . '?site_view=1&search_site=' . $object->id;
	$head [$h] [1] = $langs->trans("AgfMenuSess");
	$head [$h] [2] = 'sessions';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/site/info.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Info");
	$head [$h] [2] = 'info';
	$h ++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_site');

	return $head;
}

/**
 * Return head table for program tabs screen
 *
 * @param object $object program
 * @return array head table of tabs
 */
function cursus_prepare_head($object) {
	global $langs, $conf, $user;

	$h = 0;
	$head = array ();

	$head [$h] [0] = dol_buildpath('/agefodd/cursus/card.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Card");
	$head [$h] [2] = 'card';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/cursus/card_trainee.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("AgfMenuActStagiaire");
	$head [$h] [2] = 'trainee';
	$h ++;

	$head [$h] [0] = dol_buildpath('/agefodd/cursus/info.php', 1) . '?id=' . $object->id;
	$head [$h] [1] = $langs->trans("Info");
	$head [$h] [2] = 'info';
	$h ++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd_cursus');

	return $head;
}

/**
 * Return head table for admin tabs screen
 *
 * @return array head table of tabs
 */
function agefodd_admin_prepare_head() {
	global $langs, $conf;

	$langs->load("agefodd@agefodd");

	$h = 0;
	$head = array ();

	$head [$h] [0] = dol_buildpath("/agefodd/admin/admin_agefodd.php", 1);
	$head [$h] [1] = $langs->trans("Settings");
	$head [$h] [2] = 'settings';
	$h ++;
	
	$head [$h] [0] = dol_buildpath("/agefodd/admin/admin_options.php", 1);
	$head [$h] [1] = $langs->trans("Options");
	$head [$h] [2] = 'options';
	$h ++;

	$head [$h] [0] = dol_buildpath("/agefodd/admin/admin_administrativetasks.php", 1);
	$head [$h] [1] = $langs->trans("AgftrainingAdmTask");
	$head [$h] [2] = 'administrativetasks';
	$h ++;
	
	$head [$h] [0] = dol_buildpath("/agefodd/admin/admin_session_time.php", 1);
	$head [$h] [1] = $langs->trans("AgfAdmSessionTime");
	$head [$h] [2] = 'sessiontime';
	$h ++;
	
	$head [$h] [0] = dol_buildpath("/agefodd/admin/formation_catalogue_extrafields.php", 1);
	$head [$h] [1] = $langs->trans("ExtraFieldsTraining");
	$head [$h] [2] = 'attributetraining';
	$h ++;

	$head [$h] [0] = dol_buildpath("/agefodd/admin/session_extrafields.php", 1);
	$head [$h] [1] = $langs->trans("ExtraFieldsSessions");
	$head [$h] [2] = 'attributesession';
	$h ++;

	if (!empty($conf->global->AGF_MANAGE_CURSUS)) {
		$head [$h] [0] = dol_buildpath("/agefodd/admin/cursus_extrafields.php", 1);
		$head [$h] [1] = $langs->trans("ExtraFieldsCursus");
		$head [$h] [2] = 'attributecursus';
		$h ++;
	}

	$head [$h] [0] = dol_buildpath("/agefodd/admin/stagiaire_extrafields.php", 1);
	$head [$h] [1] = $langs->trans("ExtraFieldsTrainee");
	$head [$h] [2] = 'attributetrainee';
	$h ++;

	$head [$h] [0] = dol_buildpath("/agefodd/admin/admin_catcost.php", 1);
	$head [$h] [1] = $langs->trans("AgfCategCostCateg");
	$head [$h] [2] = 'catcost';
	$h ++;

	if (!empty($conf->global->AGF_MANAGE_BPF)) {
		$head [$h] [0] = dol_buildpath("/agefodd/admin/admin_catbpf.php", 1);
		$head [$h] [1] = $langs->trans("AgfReportBPFCategTabTitle");
		$head [$h] [2] = 'catbpf';
		$h ++;
	}

	$head [$h] [0] = dol_buildpath("/agefodd/admin/about.php", 1);
	$head [$h] [1] = $langs->trans("About");
	$head [$h] [2] = 'about';
	$h ++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'agefodd');

	return $head;
}

/**
 * Define head array for tabs of agenda setup pages
 *
 * @param string $param add to url
 * @return array Array of head
 */
function agf_calendars_prepare_head($param) {
	global $langs, $conf, $user;

	$h = 0;
	$head = array ();

	$head[$h][0] = dol_buildpath("/agefodd/agenda/index.php", 1).'?action=show_month'.($param?'&'.$param:'');
	$head[$h][1] = $langs->trans("AgfMenuAgenda");
	$head[$h][2] = 'cardmonth';
	$h++;

	$head[$h][0] = dol_buildpath("/agefodd/agenda/index.php", 1).'?action=show_week'.($param?'&'.$param:'');
	$head[$h][1] = $langs->trans("AgfMenuAgendaViewWeek");
	$head[$h][2] = 'cardweek';
	$h++;

	//$paramday=$param;
	//if (preg_match('/&month=\d+/',$paramday) && ! preg_match('/&day=\d+/',$paramday)) $paramday.='&day=1';
	$head[$h][0] = dol_buildpath("/agefodd/agenda/index.php", 1).'?action=show_day'.($param?'&'.$param:'');
	$head[$h][1] = $langs->trans("AgfMenuAgendaViewDay");
	$head[$h][2] = 'cardday';
	$h++;

	$head[$h][0] = dol_buildpath("/agefodd/agenda/pertrainer.php", 1).($param?'?'.$param:'');
	$head[$h][1] = $langs->trans("AgfMenuAgendaViewPerUser");
	$head[$h][2] = 'cardperuser';
	$h++;

	$head[$h][0] = dol_buildpath("/agefodd/agenda/listactions.php", 1).($param?'?'.$param:'');
	$head[$h][1] = $langs->trans("AgfMenuAgendaViewList");
	$head[$h][2] = 'cardlist';
	$h++;

	$object=new stdClass();

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'agefodd_agenda');

	complete_head_from_modules($conf,$langs,$object,$head,$h,'agefodd_agenda','remove');

	return $head;
}

/**
 * Calcule le nombre de regroupement par premier niveau des tâches adminsitratives
 *
 * @return int nbre de niveaux
 */
function ebi_get_adm_level_number() {
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
		return - 1;
	}
}

/**
 * Calcule le nombre de regroupement par premier niveau des tâches adminsitratives
 *
 * @return int nbre de niveaux
 */
function ebi_get_adm_training_level_number() {
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
		return - 1;
	}
}

/**
 * Calcule le nombre de regroupement par premier niveau des tâches par session
 *
 * @param int $session de la session
 * @return int nbre de niveaux
 */
function ebi_get_level_number($session) {
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
		return - 1;
	}
}

/**
 * Calcule le nombre de regroupement par premier niveau terminés pour une session donnée
 *
 * @param int $sessid de la session
 * @return int nbre de niveaux
 */
function ebi_get_adm_lastFinishLevel($sessid) {
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
		if (! empty($num)) {
			while ( $obj = $db->fetch_object($result) ) {

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
					return - 1;
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
					return - 1;
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
						return - 1;
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
						return - 1;
					}
				}

				dol_syslog("ebi_get_adm_lastFinishLevel nbtaskdone=" . $nbtaskdone . " nbtotaltask=" . $nbtotaltask, LOG_DEBUG);
				// If number task done = nb task to do or no child level
				if (($nbtaskdone == $nbtotaltask))
					$totaldone ++;
			}
		}
		$db->free($result);
		dol_syslog("ebi_get_adm_lastFinishLevel totaldone=" . $totaldone, LOG_DEBUG);
		return $totaldone;
	} else {
		$error = "Error " . $db->lasterror();
		// print $error;
		return - 1;
	}
}

/**
 * Calcule le nombre de d'action filles
 *
 * @param int $id du niveaux
 * @return int nbre d'action filles
 */
function ebi_get_adm_indice_action_child($id) {
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
		return - 1;
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
function ebi_get_adm_indice_per_rank($lvl_rank, $parent_level = '', $type = 'MIN') {
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
		return - 1;
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
function ebi_get_adm_training_indice_per_rank($lvl_rank, $parent_level = '', $type = 'MIN') {
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
		return - 1;
	}
}

/**
 * Formatage d'une liste à puce
 *
 * @param string $text chaine
 * @param boolean $form sortie au format html (true) ou texte (false)
 * @return string la chaine formater
 */
function ebi_liste_a_puce($text, $form = false) {
	// 1er niveau: remplacement de '# ' en debut de ligne par une puce de niv 1 (petit rond noir)
	// 2éme niveau: remplacement de '## ' en début de ligne par une puce de niv 2 (tiret)
	// 3éme niveau: remplacement de '### ' en début de ligne par une puce de niv 3 (>)
	// Pour annuler le formatage (début de ligne sur la mage gauche : '!#'
	$str = "";
	$line = explode("\n", $text);
	$level = 0;
	foreach ( $line as $row ) {
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
function ebi_get_adm_get_next_indice_action($id) {
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
		if (! empty($obj->nb_action)) {
			return intval(intval($obj->nb_action) + 1);
		} else {
			
		    $sql = "SELECT s.indice as parentindice FROM " . MAIN_DB_PREFIX . "agefodd_session_admlevel as s";
		    $sql.= " WHERE rowid = " . $id;
		    
		    dol_syslog("agefodd:lib:ebi_get_adm_get_next_indice_action sql=" . $sql, LOG_DEBUG);
		    $result = $db->query($sql);
		    
		    if ($result) {
		        $obj = $db->fetch_object($result);
		        $parentIndice = $obj->parentindice;
		        return intval(intval($parentIndice) + 1);
		    } else {
		        
		        $error = "Error " . $db->lasterror();
		        return - 1;
		    }
		    
		}
	} else {

		$error = "Error " . $db->lasterror();
		return - 1;
	}
}

/**
 * Calcule le next number d'indice pour une action (ecran conf module)
 *
 * @param int $id du niveaux
 * @return int action next number
 */
function ebi_get_adm_training_get_next_indice_action($id) {
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
		if (! empty($obj->nb_action)) {
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
				return - 1;
			}
		}
	} else {

		$error = "Error " . $db->lasterror();
		return - 1;
	}
}

/**
 * Calcule le next number d'indice pour une action (pour une session)
 *
 * @param int $id du niveaux
 * @param int $sessionid de la session
 * @return int action next number
 */
function ebi_get_next_indice_action($id, $sessionid) {
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
		if (! empty($obj->nb_action)) {
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
				return - 1;
			}
		}
	} else {

		$error = "Error " . $db->lasterror();
		return - 1;
	}
}

/**
 * Converti un code couleur hexa en tableau des couleurs RGB
 *
 * @param string $hex hexadecimale
 * @return array définition RGB
 */
function agf_hex2rgb($hex) {
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
	$rgb = array (
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
 *	@param	PDF			&$pdf     		The PDF factory
 *  @param  Translate	$outputlangs	Object lang for output
 * 	@param	string		$paramfreetext	Constant name of free text
 * 	@param	Societe		$fromcompany	Object company
 * 	@param	int			$marge_basse	Margin bottom we use for the autobreak
 * 	@param	int			$marge_gauche	Margin left (no more used)
 * 	@param	int			$page_hauteur	Page height (no more used)
 * 	@param	Object		$object			Object shown in PDF
 * 	@param	int			$showdetails	Show company details into footer. This param seems to not be used by standard version.
 *  @param	int			$hidefreetext	1=Hide free text, 0=Show free text
 * 	@return	int							Return height of bottom margin including footer text
 */
function pdf_agfpagefoot(&$pdf,$outputlangs,$paramfreetext,$fromcompany,$marge_basse,$marge_gauche,$page_hauteur,$object,$showdetails=0,$hidefreetext=1)
{
	global $conf,$user;

	$outputlangs->load("dict");
	$outputlangs->load("companies");
	$line='';

	$dims=$pdf->getPageDimensions();

	if (!empty($conf->global->AGF_HIDE_DOC_FOOTER)) {
		return 0;
	}

	// Line of free text
	if (empty($hidefreetext) && ! empty($conf->global->$paramfreetext))
	{
		// Make substitution
		$substitutionarray=array(
		'__FROM_NAME__' => $fromcompany->nom,
		'__FROM_EMAIL__' => $fromcompany->email,
		'__TOTAL_TTC__' => $object->total_ttc,
		'__TOTAL_HT__' => $object->total_ht,
		'__TOTAL_VAT__' => $object->total_vat
		);
		complete_substitutions_array($substitutionarray,$outputlangs,$object);
		$newfreetext=make_substitutions($conf->global->$paramfreetext,$substitutionarray);
		$line.=$outputlangs->convToOutputCharset($newfreetext);
	}

	// First line of company infos

	if ($showdetails)
	{
		$line1="";
		// Company name
		if ($fromcompany->name)
		{
			$line1.=($line1?" - ":"").$fromcompany->name;
		}

		$line2="";
		// Address
		if ($fromcompany->address)
		{
			$fromcompany->address = str_replace(array( '<br>', '<br />', "\n", "\r" ), array( ' ', ' ', ' ', ' ' ), $fromcompany->address);
			$line2.=($line2?" - ":"").$fromcompany->address;
		}
		// Zip code
		if ($fromcompany->zip)
		{
			$line2.=($line2?" - ":"").$fromcompany->zip;
		}
		// Town
		if ($fromcompany->town)
		{
			$line2.=($line2?" ":"").$fromcompany->town;
		}
		// country
		if ($fromcompany->country)
		{
			$line2.=($line2?" ":"").$fromcompany->country;
		}
		// Phone
		if ($fromcompany->phone)
		{
			$line2.=($line2?" - ":"").$outputlangs->transnoentities("Tel").": ".$fromcompany->phone;
		}
		// Mail
		if ($fromcompany->email)
		{
			$line2.=($line2?" - ":"").$outputlangs->transnoentities("Mail").": ".$fromcompany->email;
		}
		// Juridical status
		if ($fromcompany->forme_juridique_code)
		{
			$line2.=($line2?" - ":"").$outputlangs->convToOutputCharset(getFormeJuridiqueLabel($fromcompany->forme_juridique_code));
		}
		// Capital
		if ($fromcompany->capital)
		{
			$line2.=($line2?" - ":"").$outputlangs->transnoentities("CapitalOf",$fromcompany->capital)." ".$outputlangs->transnoentities("Currency".$conf->currency);
		}
	}

		// Line 3 of company infos
		$line3="";
		// Prof Id 1
		if ($fromcompany->idprof1 && ($fromcompany->country_code != 'FR' || ! $fromcompany->idprof2))
		{
			$field=$outputlangs->transcountrynoentities("ProfId1",$fromcompany->country_code);
			if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
			$line3.=($line3?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof1);
		}
		// Prof Id 2
		if ($fromcompany->idprof2)
		{
			$field=$outputlangs->transcountrynoentities("ProfId2",$fromcompany->country_code);
			if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
			$line3.=($line3?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof2);
		}
		// Prof Id 3
		if ($fromcompany->idprof3)
		{
			$field=$outputlangs->transcountrynoentities("ProfId3",$fromcompany->country_code);
			if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
			$line3.=($line3?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof3);
		}
		if (! empty($conf->global->AGF_ORGANISME_PREF))
		{
			$field=$outputlangs->transnoentities('AgfPDFFoot7',$conf->global->AGF_ORGANISME_NUM).' - '.$outputlangs->transnoentities('AgfPDFFoot8');
			if (preg_match('/(.*)/i',$field,$reg)) $field=$reg[1];
			$line3.=($line3?" - ":"").$field." ".$conf->global->AGF_ORGANISME_PREF;
		}

	// Line 4 of company infos
	$line4="";

	// Prof Id 3
	if ($fromcompany->tva_intra != '')
	{
		$line4.=($line4?" - ":"").$outputlangs->transnoentities("VATIntraShort").": ".$outputlangs->convToOutputCharset($fromcompany->tva_intra);
	}

	// Set free text font size
	if (! empty($conf->global->ULTIMATEPDF_FREETEXT_FONT_SIZE)) {
		$freetextfontsize=$conf->global->ULTIMATEPDF_FREETEXT_FONT_SIZE;
	}
	$pdf->SetFont('','',$freetextfontsize);
	//$pdf->SetDrawColor($this->colorfooter [0], $this->colorfooter [1], $this->colorfooter [2]);

	// On positionne le debut du bas de page selon nbre de lignes de ce bas de page
	$freetextheight=0;
	if ($line)	// Free text
	{
		$width=20000; $align='L';	// By default, ask a manual break: We use a large value 20000, to not have automatic wrap. This make user understand, he need to add CR on its text.
		if (! empty($conf->global->MAIN_USE_AUTOWRAP_ON_FREETEXT)) {
			$width=$page_largeur-$marge_gauche-$marge_droite; $align='C';
		}
		$freetextheight=$pdf->getStringHeight($width,$line);
	}

	$marginwithfooter=$marge_basse + $freetextheight + (! empty($line1)?3:0) + (! empty($line2)?3:0) + (! empty($line3)?3:0) + (! empty($line4)?3:0);
	$posy=$marginwithfooter+0;

	if ($line)	// Free text
	{
		$pdf->SetXY($dims['lm'],-$posy);
		$pdf->MultiCell($width, 3, $line, 0, $align, 0);
		$posy-=$freetextheight;
	}
	$pdf->SetFont('','',7);
	$pdf->SetY(-$posy);
	$pdf->line($dims['lm'], $dims['hk']-$posy, $dims['wk']-$dims['rm'], $dims['hk']-$posy);
	$posy--;

	if (! empty($line1))
	{
		$pdf->SetFont('','B',7);
		$pdf->SetXY($dims['lm'],-$posy+4);
		$pdf->MultiCell($dims['wk']-$dims['rm'], 2, $line1, 0, 'C', 0);
		$posy-=7;
	}

	if (! empty($line2))
	{
		$pdf->SetFont('','I',6);
		$pdf->SetXY($dims['lm']-6,-$posy);
		$pdf->MultiCell($dims['wk']-$dims['rm'], 2, $line2, 0, 'C', 0);
		$posy-=3;
	}

	if (! empty($line3))
	{
		$pdf->SetFont('','I',6);
		$pdf->SetXY($dims['lm']-6,-$posy);
		$pdf->MultiCell($dims['wk']-$dims['rm'], 2, $line3, 0, 'C', 0);
	}

	if (! empty($line4))
	{
		$posy-=3;
		$pdf->SetXY($dims['lm'],-$posy);
		$pdf->MultiCell($dims['wk']-$dims['rm'], 2, $line4, 0, 'C', 0);
	}

	// Show page nb only on iso languages (so default Helvetica font)
	if (strtolower(pdf_getPDFFont($outputlangs)) == 'helvetica')
	{
		$pdf->SetXY(-20,-$posy);
		//print 'xxx'.$pdf->PageNo().'-'.$pdf->getAliasNbPages().'-'.$pdf->getAliasNumPage();exit;
		if (empty($conf->global->MAIN_USE_FPDF)) $pdf->MultiCell(13, 2, $pdf->PageNo().'/'.$pdf->getAliasNbPages(), 0, 'R', 0);
		else $pdf->MultiCell(13, 2, $pdf->PageNo().'/{nb}', 0, 'R', 0);
	}

	$posy-=3;
	$pdf->SetXY($dims['lm'],-$posy);

	return $marginwithfooter;
}

/**
 * Return width to use for Logo onot PDF
 *
 * @param	string		$logo		Full path to logo file to use
 * @param	bool		$url		Image with url (true or false)
 * @return	number
 */
function pdf_getWidthForLogo($logo, $url = false)
{
	$height=22; $maxwidth=130;
	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	$tmp=dol_getImageSize($logo, $url);
	if ($tmp['height'])
	{
		$width=round($height*$tmp['width']/$tmp['height']);
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
function pdf_getInstance_agefodd($session, &$model, $format = '', $metric = 'mm', $pagetype = 'P') {
	global $conf;

	dol_include_once('/agefodd/class/TCPDFAgegfodd.class.php');

	if ((! file_exists(TCPDF_PATH . 'tcpdf.php') && ! class_exists('TCPDFAgefodd')) && ! empty($conf->global->MAIN_USE_FPDF)) {
		print 'TCPDF Must be use for this module forget TCPDI or FPDF or other PDF class, plaese contact your admnistrator';
		exit();
	}

	// Define constant for TCPDF
	if (! defined('K_TCPDF_EXTERNAL_CONFIG')) {
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

function printRefIntForma(&$db, $outputlangs, &$object, $font_size, &$pdf, $x, $y, $align) {
	global $conf;

	if ($conf->global->AGF_PRINT_INTERNAL_REF_ON_PDF) {

		$forma_ref_int = null;
		$className = get_class($object);

		if ($className == "Agefodd") $forma_ref_int = $object->ref_interne;
		else if ($className == "Agsession") {	// $object est une session
			$agf = new Agefodd($db);
			$agf->fetch($object->fk_formation_catalogue);
			$forma_ref_int = $agf->ref_interne;
			$forma_ref_int .= '('.$object->libSessionDate().') - '.$object->id;
		}



		if ($forma_ref_int != null) {
			$pdf->SetXY($x, $y);
			$pdf->SetFont('', '', $font_size);
			$pdf->MultiCell(70, 4, $outputlangs->transnoentities('AgfRefInterne').' : '.$outputlangs->convToOutputCharset($forma_ref_int), 0, $align);
		}
	}
}

function printSessionFieldsWithCustomOrder() {
	global $conf;

	$customOrder = $conf->global->AGF_CUSTOM_ORDER;

	if(! empty($customOrder)) {
		$TClassName = explode(',', $customOrder);

		foreach($TClassName as $className) {
			$order .= '"'.trim($className).'",';
		}
		$order = substr($order, 0, -1);

		if (!empty($TClassName)) {
		?>
		<script type="text/javascript">

			$(function() {
				$('#session_card > tbody > tr div.select2-container').each(function(i, item) {
					let id = item.id.slice(5);
					$('#'+id).select2('destroy');
					$('#'+id).addClass('toSelect2');
				});

				// Correspond aux premières lignes à afficher sur la fiche d'une session de formation
				var agf_TClass = new Array(<?php print $order ?>); // "agefodd_agsession_extras_"+codeExtrafield, "order_intitule", "order_ref", "order_intituleCusto"
				var agf_tab_tr = $('#session_card > tbody > tr').clone(true);
				var TAgf_found = new Array();

				$('#session_card > tbody > tr').remove();

				for(let i in agf_TClass) {
					if($.isNumeric(i) === false) break;

					for(let j in agf_tab_tr) {
						if($.isNumeric(j) === false) break;

						if(agf_TClass[i] === agf_tab_tr[j].className) {
							$('#session_card > tbody').append(agf_tab_tr[j]);
							TAgf_found[j] = true;
						}
					}
				}

				// Ajoute le reste des TR non ordonnés à la suite
				for (let i in agf_tab_tr) {
					if($.isNumeric(i) === false) break;
					if (TAgf_found[i] === true) continue;
					$('#session_card > tbody').append(agf_tab_tr[i]);
				}

				$('.toSelect2').select2({
					width: 'element'
				});
			});

		</script>
		<?php
		}
	}
}

function dol_agefodd_banner_tab($object, $paramid, $morehtml='', $shownav=1, $fieldid='rowid', $fieldref='ref', $morehtmlref='', $moreparam='', $nodbprefix=0, $morehtmlleft='', $morehtmlstatus='', $onlybanner=0, $morehtmlright='')
{
    global $conf, $form, $user, $langs;
    
    $error = 0;
    
    $maxvisiblephotos=1;
    $showimage=1;
    $modulepart="product";
    
    if ($showimage)
    {
        if (!empty($object->element)){
            $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">';
            $phototoshow = $form->showphoto($modulepart,$object,0,0,0,'photoref','small',1,0,$maxvisiblephotos);
            $morehtmlleft.='<div class="floatleft inline-block valignmiddle divphotoref">';
            $morehtmlleft.=$phototoshow;
            $morehtmlleft.='</div>';

        }
    }
    
    // libstatut
    if ($object->element == 'agefodd_formation_catalogue'){
        $morehtmlstatus.='<span class="statusrefsell">'.$object->getLibStatut(1).'</span>';
    }
    
    // Other infos
    if ($object->element == 'agefodd_formation_catalogue'){
        $morehtmlref.='<div class="refidno">';
        $morehtmlref.=$object->intitule . '<br>' . $object->ref_obj;
        if(!empty($object->ref_interne)) $morehtmlref .= '<br>' . $object->ref_interne;
        $morehtmlref.='</div>';
    }
    
    print '<div class="'.($onlybanner?'arearefnobottom ':'arearef ').'heightref valignmiddle" width="100%">';
    print $form->showrefnav($object, $paramid, $morehtml, $shownav, $fieldid, $fieldref, $morehtmlref, $moreparam, $nodbprefix, $morehtmlleft, $morehtmlstatus, $morehtmlright);
    print '</div><br>';
    print '<div class="underrefbanner clearboth"></div>';
    //print '<div class="underbanner clearboth"></div>';
}
