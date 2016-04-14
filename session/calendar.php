<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2014	Florian Henry	<florian.henry@open-concept.pro>
 * Copyright (C) 2012		JF FERRY	<jfefe@aternatik.fr>
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
 * \file agefodd/session/card.php
 * \ingroup agefodd
 * \brief card of session
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_sessadm.class.php');
require_once ('../class/agefodd_session_admlevel.class.php');
require_once ('../class/html.formagefodd.class.php');
require_once ('../class/agefodd_session_calendrier.class.php');
require_once ('../class/agefodd_calendrier.class.php');
require_once ('../class/agefodd_session_formateur.class.php');
require_once ('../class/agefodd_session_stagiaire.class.php');
require_once ('../class/agefodd_session_element.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
require_once ('../class/agefodd_formation_catalogue.class.php');
require_once ('../class/agefodd_opca.class.php');

// Security check
if (! $user->rights->agefodd->lire) {
	accessforbidden();
}

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$arch = GETPOST('arch', 'int');
$anchor = GETPOST('anchor');

$agf = new Agsession($db);
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($agf->table_element);

/*
 * Actions delete session
 */

$period_update = GETPOST('period_update', 'int');
$cancel = GETPOST('cancel');
$saveandclose = GETPOST('saveandclose');
$modperiod = GETPOST('modperiod', 'int');
$period_remove = GETPOST('period_remove', 'int');
$period_remove_all = GETPOST('period_remove_all', 'int');

if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->creer) {
	$agf = new Agsession($db);
	$result = $agf->remove($id);

	if ($result > 0) {
		Header("Location: list.php");
		exit();
	} else {
		setEventMessage($langs->trans("AgfDeleteErr") . ':' . $agf->error, 'errors');
	}
}

/*
 * Actions delete period
 */

if ($action == 'remove_cust' && $user->rights->agefodd->modifier) {

	$agf = new Agsession($db);
	$result = $agf->fetch($id);
	unset($agf->fk_soc);
	$agf->contactid = 0;
	$agf->sourcecontactid = 0;
	$result = $agf->update($user);

	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

if ($action == 'confirm_delete_period' && $confirm == "yes" && $user->rights->agefodd->creer) {

	$agf = new Agefodd_sesscalendar($db);
	$result = $agf->remove($modperiod);

	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id . '&anchor=period');
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

if ($action == 'confirm_delete_period_all' && $confirm == "yes" && $user->rights->agefodd->creer) {

	$agf = new Agefodd_sesscalendar($db);
	$result = $agf->fetch_all($id);
	if ($result < 0) {
		setEventMessage($agf->error, 'errors');
	} else {
		foreach ( $agf->lines as $line ) {
			$agf_line = new Agefodd_sesscalendar($db);
			$result = $agf_line->remove($line->id);
			if ($result < 0) {
				setEventMessage($agf_line->error, 'errors');
			}
		}
	}

	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id . '&anchor=period');
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

/*
 * Actions archive/active
 */

if ($action == 'arch_confirm_delete' && $user->rights->agefodd->creer) {
	if ($confirm == "yes") {
		$agf = new Agsession($db);

		$result = $agf->fetch($id);
		$arch = GETPOST("arch", 'int');

		if (empty($arch)) {
			$agf->status = 1;
		} else {
			$agf->status = 4;
		}

		$result = $agf->updateArchive($user);

		if ($result > 0) {
			// If update are OK we delete related files
			foreach ( glob($conf->agefodd->dir_output . "/*_" . $id . "_*.pdf") as $filename ) {
				if (is_file($filename))
					unlink("$filename");
			}

			Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	} else {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	}
}

/*
 * Action update (fiche session)
 */
if ($action == 'update' && ($user->rights->agefodd->creer || $user->rights->agefodd->modifier) && empty($period_update)) {
	if (empty($cancel)) {
		$error = 0;

		$agf = new Agsession($db);

		$fk_session_place = GETPOST('place', 'int');
		if (($fk_session_place == - 1) || (empty($fk_session_place))) {
			setEventMessage($langs->trans('AgfPlaceMandatory'), 'errors');
			$error ++;
		}

		$result = $agf->fetch_other_session_sameplacedate();
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
			$error ++;
		} else {

			if (is_array($agf->lines_place) && count($agf->lines_place) > 0) {
				$sessionplaceerror = '';
				foreach ( $agf->lines_place as $linesess ) {
					$sessionplaceerror .= $langs->trans('AgfPlaceUseInOtherSession') . '<a href=' . dol_buildpath('/agefodd/session/list.php', 1) . '?site_view=1&search_id=' . $linesess->rowid . '&search_site=' . $fk_session_place . ' target="_blanck">' . $linesess->rowid . '</a><br>';
				}
				setEventMessage($sessionplaceerror, 'warnings');
			}
		}

		// If customer is selected contact is required
		$custid = GETPOST('fk_soc', 'int');
		$contactclientid = GETPOST('contact', 'int');
		if (empty($conf->global->AGF_CONTACT_NOT_MANDATORY_ON_SESSION)) {
			if (((($custid != - 1) && (! empty($custid))) && (($contactclientid == - 1) || (empty($contactclientid))))) {
				$error ++;
				setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AgfSessionContact")), 'errors');
			}
		}

		$result = $agf->fetch($id);

		if ($agf->fk_formation_catalogue != GETPOST('formation', 'int')) {
			$training_session = new Agefodd($db);
			$result = $training_session->fetch(GETPOST('formation', 'int'));
			if ($result > 0) {
				$agf->nb_subscribe_min = $training_session->nb_subscribe_min;
				$agf->duree_session = $training_session->duree;
				$agf->intitule_custo = $training_session->intitule;
				$agf->fk_product = $training_session->fk_product;
			}
		} else {
			$agf->nb_subscribe_min = GETPOST('nbmintarget', 'int');
			$agf->fk_product = GETPOST('productid', 'int');
			$agf->duree_session = GETPOST('duree_session', 'int');
			$agf->intitule_custo = GETPOST('intitule_custo', 'alpha');
		}

		$agf->fk_formation_catalogue = GETPOST('formation', 'int');

		$agf->dated = dol_mktime(0, 0, 0, GETPOST('dadmonth', 'int'), GETPOST('dadday', 'int'), GETPOST('dadyear', 'int'));
		$agf->datef = dol_mktime(0, 0, 0, GETPOST('dafmonth', 'int'), GETPOST('dafday', 'int'), GETPOST('dafyear', 'int'));

		if ($agf->dated > $agf->datef) {
			$error ++;
			setEventMessage($langs->trans('AgfSessionDateErrors'), 'errors');
		}

		$agf->fk_session_place = $fk_session_place;
		$agf->type_session = GETPOST('type_session', 'int');
		$agf->commercialid = GETPOST('commercial', 'int');
		$agf->contactid = GETPOST('contact', 'int');

		if ($conf->global->AGF_CONTACT_DOL_SESSION) {
			$agf->sourcecontactid = $agf->contactid;
		}
		$agf->notes = GETPOST('notes', 'alpha');
		$agf->status = GETPOST('session_status', 'int');

		$agf->cost_trainer = GETPOST('costtrainer', 'alpha');
		$agf->cost_site = GETPOST('costsite', 'alpha');
		$agf->sell_price = GETPOST('sellprice', 'alpha');

		$agf->date_res_site = dol_mktime(0, 0, 0, GETPOST('res_sitemonth', 'int'), GETPOST('res_siteday', 'int'), GETPOST('res_siteyear', 'int'));
		$agf->date_res_trainer = dol_mktime(0, 0, 0, GETPOST('res_trainmonth', 'int'), GETPOST('res_trainday', 'int'), GETPOST('res_trainyear', 'int'));
		$agf->date_res_confirm_site = dol_mktime(0, 0, 0, GETPOST('res_siteconfirmmonth', 'int'), GETPOST('res_siteconfirmday', 'int'), GETPOST('res_siteconfirmyear', 'int'));

		if ($agf->date_res_site == '') {
			$isdateressite = 0;
		} else {
			$isdateressite = GETPOST('isdateressite', 'alpha');
		}

		if ($agf->date_res_trainer == '') {
			$isdaterestrainer = 0;
		} else {
			$isdaterestrainer = GETPOST('isdaterestrainer', 'alpha');
		}

		if ($agf->date_res_confirm_site == '') {
			$isdateresconfirmsite = 0;
		} else {
			$isdateresconfirmsite = GETPOST('isdateresconfirmsite', 'alpha');
		}

		if ($isdateressite == 1 && $agf->date_res_site != '') {
			$agf->is_date_res_site = 1;
		} else {
			$agf->is_date_res_site = 0;
			$agf->date_res_site = '';
		}

		if ($isdaterestrainer == 1 && $agf->date_res_trainer != '') {
			$agf->is_date_res_trainer = 1;
		} else {
			$agf->is_date_res_trainer = 0;
			$agf->date_res_trainer = '';
		}

		if ($isdateresconfirmsite == 1 && $agf->date_res_confirm_site != '') {
			$agf->is_date_res_confirm_site = 1;
		} else {
			$agf->is_date_res_confirm_site = 0;
			$agf->date_res_confirm_site = '';
		}

		$fk_soc = GETPOST('fk_soc', 'int');
		$fk_soc_requester = GETPOST('fk_soc_requester', 'int');
		$fk_socpeople_requester = GETPOST('fk_socpeople_requester', 'int');
		$fk_socpeople_presta = GETPOST('fk_socpeople_presta', 'int');
		$color = GETPOST('color', 'alpha');
		$nb_place = GETPOST('nb_place', 'int');
		$nb_stagiaire = GETPOST('nb_stagiaire', 'int');
		$force_nb_stagiaire = GETPOST('force_nb_stagiaire', 'int');

		if ($force_nb_stagiaire == 1 && $agf->force_nb_stagiaire != '') {
			$agf->force_nb_stagiaire = 1;
		} else {
			$agf->force_nb_stagiaire = 0;
		}

		$cost_trip = GETPOST('costtrip', 'alpha');

		if (! empty($fk_soc))
			$agf->fk_soc = $fk_soc;
		if (! empty($fk_soc_requester))
			$agf->fk_soc_requester = $fk_soc_requester;
		if (! empty($fk_socpeople_requester))
			$agf->fk_socpeople_requester = $fk_socpeople_requester;
		if (! empty($fk_socpeople_presta))
			$agf->fk_socpeople_presta = $fk_socpeople_presta;
		if (! empty($color))
			$agf->color = $color;
		if (! empty($nb_place))
			$agf->nb_place = $nb_place;
		if (! empty($nb_stagiaire))
			$agf->nb_stagiaire = $nb_stagiaire;
		if (! empty($force_nb_stagiaire))
			$agf->force_nb_stagiaire = $force_nb_stagiaire;
		if (! empty($cost_trip))
			$agf->cost_trip = $cost_trip;

		if ($error == 0) {
			$extrafields->setOptionalsFromPost($extralabels, $agf);

			$result = $agf->update($user);
			if ($result > 0) {

				if (! empty($saveandclose)) {
					Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
				} else {
					setEventMessage($langs->trans('Save'), 'mesgs');
					Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
				}
				exit();
			} else {
				setEventMessage($agf->error, 'errors');
			}
		} else {
			if (! empty($saveandclose)) {
				$action = '';
			} else {
				$action = 'edit';
			}
		}
	} else {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	}
}

/*
 * Action update
 * - Calendar update
 * - trainer update
 */
if ($action == 'edit' && ($user->rights->agefodd->creer || $user->rights->agefodd->modifier)) {

	if (! empty($period_update)) {

		$date_session = dol_mktime(0, 0, 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));

		$heure_tmp_arr = array ();

		$heured_tmp = GETPOST('dated', 'alpha');
		if (! empty($heured_tmp)) {
			$heure_tmp_arr = explode(':', $heured_tmp);
			$heured = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));
		}

		$heuref_tmp = GETPOST('datef', 'alpha');
		if (! empty($heuref_tmp)) {
			$heure_tmp_arr = explode(':', $heuref_tmp);
			$heuref = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));
		}

		$agf = new Agefodd_sesscalendar($db);
		$result = $agf->fetch($modperiod);

		if (! empty($modperiod))
			$agf->id = $modperiod;
		if (! empty($date_session))
			$agf->date_session = $date_session;
		if (! empty($heured))
			$agf->heured = $heured;
		if (! empty($heuref))
			$agf->heuref = $heuref;

		$result = $agf->update($user);

		if ($result > 0) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id . "&anchor=period");
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	}

	$period_add = GETPOST('period_add_x');
	if (! empty($period_add)) {
		$error = 0;
		$error_message = '';

		// From template
		$idtemplate_array = GETPOST('fromtemplate');
		if (is_array($idtemplate_array)) {
			foreach ( $idtemplate_array as $idtemplate ) {

				$agf = new Agefodd_sesscalendar($db);

				$agf->sessid = GETPOST('sessid', 'int');
				$agf->date_session = dol_mktime(0, 0, 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));

				$tmpl_calendar = new Agefoddcalendrier($db);
				$result = $tmpl_calendar->fetch($idtemplate);
				$tmpldate = dol_mktime(0, 0, 0, GETPOST('datetmplmonth', 'int'), GETPOST('datetmplday', 'int'), GETPOST('datetmplyear', 'int'));
				if ($tmpl_calendar->day_session != 1) {
					$tmpldate = dol_time_plus_duree($tmpldate, (($tmpl_calendar->day_session) - 1), 'd');
				}

				$agf->date_session = $tmpldate;

				$heure_tmp_arr = explode(':', $tmpl_calendar->heured);
				$agf->heured = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, dol_print_date($agf->date_session, "%m"), dol_print_date($agf->date_session, "%d"), dol_print_date($agf->date_session, "%Y"));

				$heure_tmp_arr = explode(':', $tmpl_calendar->heuref);
				$agf->heuref = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, dol_print_date($agf->date_session, "%m"), dol_print_date($agf->date_session, "%d"), dol_print_date($agf->date_session, "%Y"));

				$result = $agf->create($user);
				if ($result < 0) {
					$error ++;
					$error_message .= $agf->error;
				}
			}
		} else {

			$agf = new Agefodd_sesscalendar($db);

			$agf->sessid = GETPOST('sessid', 'int');
			$agf->date_session = dol_mktime(0, 0, 0, GETPOST('datenewmonth', 'int'), GETPOST('datenewday', 'int'), GETPOST('datenewyear', 'int'));

			// From calendar selection
			$heure_tmp_arr = array ();

			$heured_tmp = GETPOST('datenewd', 'alpha');
			if (! empty($heured_tmp)) {
				$heure_tmp_arr = explode(':', $heured_tmp);
				$agf->heured = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, GETPOST('datenewmonth', 'int'), GETPOST('datenewday', 'int'), GETPOST('datenewyear', 'int'));
			}

			$heuref_tmp = GETPOST('datenewf', 'alpha');
			if (! empty($heuref_tmp)) {
				$heure_tmp_arr = explode(':', $heuref_tmp);
				$agf->heuref = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, GETPOST('datenewmonth', 'int'), GETPOST('datenewday', 'int'), GETPOST('datenewyear', 'int'));
			}

			$result = $agf->create($user);
			if ($result < 0) {
				$error ++;
				$error_message = $agf->error;
			}
		}

		if (! $error) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id . "&anchor=period");
			exit();
		} else {
			setEventMessage($error_message, 'errors');
		}
	}

	$period_daytodate = GETPOST('period_daytodate_x');
	if (! empty($period_daytodate)) {
		require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

		$error = 0;
		$error_message = '';

		// From template
		$weekday = GETPOST('fromdaytodate', 'array');
		$datedaytodate1d = GETPOST('datedaytodate1d', 'alpha');
		$datedaytodate1f = GETPOST('datedaytodate1f', 'alpha');
		$datedaytodate2d = GETPOST('datedaytodate2d', 'alpha');
		$datedaytodate2f = GETPOST('datedaytodate2f', 'alpha');
		$fromdaytodate = GETPOST('fromdaytodate', 'array');
		if (is_array($weekday)) {

			$datestart = dol_mktime(0, 0, 0, GETPOST('datedaytodatestartmonth', 'int'), GETPOST('datedaytodatestartday', 'int'), GETPOST('datedaytodatestartyear', 'int'));
			$dateend = dol_mktime(0, 0, 0, GETPOST('datedaytodateendmonth', 'int'), GETPOST('datedaytodateendday', 'int'), GETPOST('datedaytodateendyear', 'int'));

			$treatmentdate = $datestart;
			while ( $treatmentdate <= $dateend ) {
				var_dump($treatmentdate);
				$weekday_num = dol_print_date($treatmentdate, '%w');
				if (in_array($weekday_num, $weekday)) {
					$agf = new Agefodd_sesscalendar($db);
					$agf->sessid = GETPOST('sessid', 'int');
					$agf->date_session = $treatmentdate;
					if (! empty($datedaytodate1d)) {
						$heure_tmp_arr = explode(':', $datedaytodate1d);
						$agf->heured = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, dol_print_date($treatmentdate, "%m"), dol_print_date($treatmentdate, "%d"), dol_print_date($treatmentdate, "%Y"));
					}
					if (! empty($datedaytodate1f)) {
						$heure_tmp_arr = explode(':', $datedaytodate1f);
						$agf->heuref = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, dol_print_date($treatmentdate, "%m"), dol_print_date($treatmentdate, "%d"), dol_print_date($treatmentdate, "%Y"));
					}
					$result = $agf->create($user);
					if ($result < 0) {
						$error ++;
						$error_message .= $agf->error;
					}

					if (! empty($datedaytodate2d) && ! empty($datedaytodate2f)) {
						$agf = new Agefodd_sesscalendar($db);
						$agf->sessid = GETPOST('sessid', 'int');
						$agf->date_session = $treatmentdate;
						$heure_tmp_arr = explode(':', $datedaytodate2d);
						$agf->heured = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, dol_print_date($treatmentdate, "%m"), dol_print_date($treatmentdate, "%d"), dol_print_date($treatmentdate, "%Y"));
						$heure_tmp_arr = explode(':', $datedaytodate2f);
						$agf->heuref = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, dol_print_date($treatmentdate, "%m"), dol_print_date($treatmentdate, "%d"), dol_print_date($treatmentdate, "%Y"));

						$result = $agf->create($user);
						if ($result < 0) {
							$error ++;
							$error_message .= $agf->error;
						}
					}
				}
				$treatmentdate = dol_time_plus_duree($treatmentdate, '1', 'd');
			}
		}

		if (! $error) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id . "&anchor=period");
			exit();
		} else {
			setEventMessage($error_message, 'errors');
		}
	}
}

/*
 * Action create (new training session)
 */

if ($action == 'add_confirm' && $user->rights->agefodd->creer) {
	$error = 0;
	if (empty($cancel)) {
		$agf = new Agsession($db);

		$fk_session_place = GETPOST('place', 'int');
		if (($fk_session_place == - 1) || (empty($fk_session_place))) {
			$error ++;
			setEventMessage($langs->trans('AgfPlaceMandatory'), 'errors');
		}

		// If customer is selected contact is required
		$custid = GETPOST('fk_soc', 'int');
		$contactclientid = GETPOST('contact', 'int');
		if (empty($conf->global->AGF_CONTACT_NOT_MANDATORY_ON_SESSION)) {
			if (((($custid != - 1) && (! empty($custid))) && (($contactclientid == - 1) || (empty($contactclientid))))) {
				$error ++;
				setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AgfSessionContact")), 'errors');
			}
		}

		$training_id = GETPOST('formation', 'int');
		if (($training_id == - 1) || (empty($training_id))) {
			$error ++;
			setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AgfFormIntitule")), 'errors');
		}

		$agf->fk_soc_requester = GETPOST('fk_soc_requester', 'int');
		$agf->fk_socpeople_requester = GETPOST('fk_socpeople_requester', 'int');
		// If customer and requester are the same and contact requester is empty and contact is not empty then contact request is the same as contact
		if ($agf->fk_soc_requester == $custid && (empty($agf->fk_socpeople_requester) || $agf->fk_socpeople_requester == - 1) && (! empty($contactclientid) || $contactclientid != - 1) && ! empty($conf->global->AGF_CONTACT_DOL_SESSION)) {
			$agf->fk_socpeople_requester = $contactclientid;
		}
		$agf->fk_socpeople_presta = GETPOST('fk_socpeople_presta', 'int');

		$agf->fk_formation_catalogue = $training_id;
		$agf->fk_session_place = $fk_session_place;
		$agf->nb_place = GETPOST('nb_place', 'int');
		$agf->type_session = GETPOST('type_session', 'int');
		$agf->nb_place = GETPOST('nb_place', 'int');
		$agf->status = GETPOST('session_status', 'int');

		$agf->fk_soc = GETPOST('fk_soc', 'int');
		$agf->dated = dol_mktime(0, 0, 0, GETPOST('dadmonth', 'int'), GETPOST('dadday', 'int'), GETPOST('dadyear', 'int'));
		$agf->datef = dol_mktime(0, 0, 0, GETPOST('dafmonth', 'int'), GETPOST('dafday', 'int'), GETPOST('dafyear', 'int'));

		if ($agf->dated > $agf->datef) {
			$error ++;
			setEventMessage($langs->trans('AgfSessionDateErrors'), 'errors');
		}

		$agf->notes = GETPOST('notes', 'alpha');
		$agf->commercialid = GETPOST('commercial', 'int');

		// If custid not empty but commercialid empty, set commercial as first saleman of thirdparty
		if ((empty($agf->commercialid) || $agf->commercialid == - 1) && ! empty($custid)) {
			$sql_saleman = 'SELECT fk_user FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux WHERE fk_soc=' . $custid . ' LIMIT 1';
			$resql_saleman = $db->query($sql_saleman);
			if (! $resql_saleman) {
				setEventMessage($db->lasterror, 'erros');
			} else {
				$obj_saleman = $db->fetch_object($resql_saleman);
				if (! empty($obj_saleman->fk_user)) {
					$agf->commercialid = $obj_saleman->fk_user;
				}
			}
		}

		$agf->contactid = GETPOST('contact', 'int');

		$agf->fk_product = GETPOST('productid', 'int');

		$agf->duree_session = GETPOST('duree_session', 'int');
		$agf->intitule_custo = GETPOST('intitule_custo', 'alpha');

		if ($error == 0) {

			$extrafields->setOptionalsFromPost($extralabels, $agf);

			$result = $agf->create($user);
			$new_session_id = $result;

			if ($result > 0) {
				// If session creation are ok
				// We create admnistrative task associated
				$result = $agf->createAdmLevelForSession($user);
				if ($result > 0) {
					setEventMessage($agf->error, 'errors');
					$error ++;
				}
			} else {
				setEventMessage($agf->error, 'errors');
				$error ++;
			}
		}

		$result = $agf->fetch_other_session_sameplacedate();
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
			$error ++;
		} else {

			if (is_array($agf->lines_place) && count($agf->lines_place) > 0) {
				$sessionplaceerror = '';
				foreach ( $agf->lines_place as $linesess ) {
					if ($linesess->rowid != $new_session_id) {
						$sessionplaceerror .= $langs->trans('AgfPlaceUseInOtherSession') . '<a href=' . dol_buildpath('/agefodd/session/list.php', 1) . '?site_view=1&search_id=' . $linesess->rowid . '&search_site=' . $fk_session_place . ' target="_blanck">' . $linesess->rowid . '</a><br>';
					}
				}
				if (! empty($sessionplaceerror)) {
					setEventMessage($sessionplaceerror, 'warnings');
				}
			}
		}

		if ($error == 0) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $agf->id);
			exit();
		}

		else {
			$action = 'create';
		}
	} else {
		Header("Location: list.php");
		exit();
	}
}

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes') {
	/*$clone_content = GETPOST ( 'clone_content' );
	 print 'clone_content='.$clone_content;
	 if (empty ( $clone_content )) {
	 setEventMessage ( $langs->trans ( "NoCloneOptionsSpecified" ), 'errors' );
	 } else {*/
	$agf = new Agsession($db);
	if ($agf->fetch($id) > 0) {
		$result = $agf->createFromClone($id, $hookmanager);
		if ($result > 0) {
			if (GETPOST('clone_calendar')) {
				// clone calendar information
				$calendrierstat = new Agefodd_sesscalendar($db);
				$calendrier = new Agefodd_sesscalendar($db);
				$calendrier->fetch_all($id);
				$blocNumber = count($calendrier->lines);
				if ($blocNumber > 0) {
					$old_date = 0;
					$duree = 0;
					for($i = 0; $i < $blocNumber; $i ++) {
						$calendrierstat->sessid = $result;
						$calendrierstat->date_session = $calendrier->lines[$i]->date_session;
						$calendrierstat->heured = $calendrier->lines[$i]->heured;
						$calendrierstat->heuref = $calendrier->lines[$i]->heuref;

						$result1 = $calendrierstat->create($user);
					}
				}
			}
			if (GETPOST('clone_trainee')) {
				// Clone trainee information
				$traineestat = new Agefodd_session_stagiaire($db);
				$session_trainee = new Agefodd_session_stagiaire($db);
				$session_trainee->fetch_stagiaire_per_session($id);
				$blocNumber = count($session_trainee->lines);
				if ($blocNumber > 0) {
					foreach ( $session_trainee->lines as $line ) {
						$traineestat->fk_session_agefodd = $result;
						$traineestat->fk_stagiaire = $line->id;
						$traineestat->fk_agefodd_stagiaire_type = $line->fk_agefodd_stagiaire_type;

						$result1 = $traineestat->create($user);
					}
				}
			}

			if (GETPOST('clone_trainer')) {
				// Clone trainer information
				$trainerstat = new Agefodd_session_formateur($db);
				$session_trainer = new Agefodd_session_formateur($db);
				$session_trainer->fetch_formateur_per_session($id);
				$blocNumber = count($session_trainer->lines);
				if ($blocNumber > 0) {
					foreach ( $session_trainer->lines as $line ) {
						$trainerstat->sessid = $result;
						$trainerstat->formid = $line->formid;
						$trainerstat->trainer_type = $line->trainer_type;
						$result1 = $trainerstat->create($user);
					}
				}
			}
			header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $result);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
			$action = '';
		}
	}
	// }
}

/*
 * View
 */

llxHeader('', $langs->trans("AgfSessionDetail"), '', '', '', '', array (
		'/agefodd/includes/jquery/plugins/colorpicker/js/colorpicker.js',
		'/agefodd/includes/lib.js'
), array (
		'/agefodd/includes/jquery/plugins/colorpicker/css/colorpicker.css'
));
$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

/*
 * Action create
 */

// Display session card
if ($id) {
	$agf = new Agsession($db);
	$result = $agf->fetch($id);

	if ($result > 0) {
		if (! (empty($agf->id))) {
			$head = session_prepare_head($agf);

			dol_fiche_head($head, 'card', $langs->trans("AgfSessionDetail"), 0, 'calendarday');

			$agf_fact = new Agefodd_session_element($db);
			$agf_fact->fetch_by_session($agf->id);
			$other_amount = '(' . $langs->trans('AgfProposalAmountSigned') . ' ' . $agf_fact->propal_sign_amount . ' ' . $langs->trans('Currency' . $conf->currency);
			if (! empty($conf->global->MAIN_MODULE_COMMANDE)) {
				$other_amount .= '/' . $langs->trans('AgfOrderAmount') . ' ' . $agf_fact->order_amount . ' ' . $langs->trans('Currency' . $conf->currency);
			}
			$other_amount .= '/' . $langs->trans('AgfInvoiceAmountWaiting') . ' ' . $agf_fact->invoice_ongoing_amount . ' ' . $langs->trans('Currency' . $conf->currency);
			$other_amount .= '/' . $langs->trans('AgfInvoiceAmountPayed') . ' ' . $agf_fact->invoice_payed_amount . ' ' . $langs->trans('Currency' . $conf->currency) . ')';

			print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
			print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
			print '</div>';

			// Print session card
			$agf->printSessionInfo();

			print '&nbsp';

			/*
			 * Calendar management
			 */
			print_barre_liste($langs->trans("AgfCalendrier"), "", "", "", "", "", '', 0);
			print '<span id="period"></span>';
			/*
			 * Confirm delete calendar
			 */
			if (! empty($period_remove)) {
				// Param url = id de la periode à supprimer - id session
				$ret = $form->form_confirm($_SERVER['PHP_SELF'] . '?modperiod=' . $modperiod . '&id=' . $id, $langs->trans("AgfDeletePeriod"), $langs->trans("AgfConfirmDeletePeriod"), "confirm_delete_period", '', '', 1);
				if ($ret == 'html')
					print '<br>';
			}
			if (! empty($period_remove_all)) {
				// Param url = id de la periode à supprimer - id session
				$ret = $form->form_confirm($_SERVER['PHP_SELF'] . '?id=' . $id, $langs->trans("AgfAllDeletePeriod"), $langs->trans("AgfConfirmAllDeletePeriod"), "confirm_delete_period_all", '', '', 1);
				if ($ret == 'html')
					print '<br>';
			}
			print '<div class="tabBar">';
			print '<form name="obj_update" action="' . $_SERVER['PHP_SELF'] . '?action=edit&id=' . $id . '"  method="POST">' . "\n";
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
			print '<input type="hidden" name="action" value="edit">' . "\n";
			print '<input type="hidden" name="sessid" value="' . $agf->id . '">' . "\n";

			print '<table class="border" width="100%" id="period">';

			$calendrier = new Agefodd_sesscalendar($db);
			$calendrier->fetch_all($agf->id);
			$blocNumber = count($calendrier->lines);
			if ($blocNumber < 1 && ! (empty($newperiod))) {
				print '<tr>';
				print '<td  colpsan=1 style="color:red; text-decoration: blink;">' . $langs->trans("AgfNoCalendar") . '</td></tr>';
			} else {
				$old_date = 0;
				$duree = 0;
				for($i = 0; $i < $blocNumber; $i ++) {
					if ($calendrier->lines[$i]->id == $modperiod && ! empty($period_remove))
						print '<tr bgcolor="#d5baa8">' . "\n";
					else
						print '<tr>' . "\n";

						// print '<input type="hidden" name="modperiod" value="' . . '">' . "\n";
						// print '<input type="hidden" name="anchor" value="period">' . "\n";

					if ($calendrier->lines[$i]->id == $modperiod && ! ! empty($period_remove)) {
						print '<td  width="20%">' . $langs->trans("AgfPeriodDate") . ' ';
						$form->select_date($calendrier->lines[$i]->date_session, 'date', '', '', '', 'obj_update_' . $i);
						print '</td>';
						print '<td width="150px" nowrap>' . $langs->trans("AgfPeriodTimeB") . ' ';
						print $formAgefodd->select_time(dol_print_date($calendrier->lines[$i]->heured, 'hour'), 'dated');
						print ' - ' . $langs->trans("AgfPeriodTimeE") . ' ';
						print $formAgefodd->select_time(dol_print_date($calendrier->lines[$i]->heuref, 'hour'), 'datef');

						if ($user->rights->agefodd->modifier) {
							print '<input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" align="absmiddle" name="period_update" alt="' . $langs->trans("AgfModSave") . '">';
							print '<input type="hidden" name="modperiod" value="' . $calendrier->lines[$i]->id . '">';
							print '<input type="hidden" name="period_update" value="1">';
						}
						print '</td>';
					} else {
						print '<td width="20%">' . dol_print_date($calendrier->lines[$i]->date_session, 'daytext') . '</td>';
						print '<td  width="150px">' . dol_print_date($calendrier->lines[$i]->heured, 'hour') . ' - ' . dol_print_date($calendrier->lines[$i]->heuref, 'hour');
						if ($user->rights->agefodd->modifier) {
							print '<a href="' . $_SERVER['PHP_SELF'] . '?action=edit&amp;id=' . $id . '&amp;modperiod=' . $calendrier->lines[$i]->id . '&amp;anchor=period">' . img_picto($langs->trans("Save"), 'edit') . '</a>';
						}
						print '&nbsp;';
						if ($user->rights->agefodd->modifier) {
							print '<a href="' . $_SERVER['PHP_SELF'] . '?action=edit&amp;id=' . $id . '&amp;period_remove=1&amp;modperiod=' . $calendrier->lines[$i]->id . '">' . img_picto($langs->trans("Delete"), 'delete') . '</a>';
						}
						print '</td>';
					}

					if (empty($i)) {
						print '<td colspan="2" rowspan="' . count($calendrier->lines) . '">';
						if ($user->rights->agefodd->creer) {
							print '<a href="' . $_SERVER['PHP_SELF'] . '?action=edit&amp;id=' . $id . '&amp;period_remove_all=1">' . $langs->trans('AgfAllDeletePeriod') . img_picto($langs->trans("AgfAllDeletePeriod"), 'delete') . '</a>';
						}
						print '</td>';
					}

					// We calculated the total session duration time
					$duree += ($calendrier->lines[$i]->heuref - $calendrier->lines[$i]->heured);

					print '</tr>' . "\n";
				}
				if ((($agf->duree_session * 3600) != $duree) && (empty($conf->global->AGF_NOT_DISPLAY_WARNING_TIME_SESSION))) {
					print '<tr><td colspan="4" align="center"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/recent.png" border="0" align="absmiddle" hspace="6px" >';
					if (($agf->duree_session * 3600) < $duree)
						print $langs->trans("AgfCalendarSup");
					if (($agf->duree_session * 3600) > $duree)
						print $langs->trans("AgfCalendarInf");
					$min = floor($duree / 60);
					$rmin = sprintf("%02d", $min % 60);
					$hour = floor($min / 60);
					print ' (' . $langs->trans("AgfCalendarDureeProgrammee") . ': ' . $hour . ':' . $rmin . ', ';
					print $langs->trans("AgfCalendarDureeThéorique") . ' : ' . ($agf->duree_session) . ':00).</td></tr>';
				}
			}

			// Fiels for new periodes

			if (! empty($newperiod)) {
				print "</table></div>";
				print '<table style="border:0;" width="100%">';
				print '<tr><td align="right" colspan="4">';
				print '<input type="hidden" name="newperiod" value="1">' . "\n";
				print '<input type="submit" class="butAction" value="' . $langs->trans("AgfPeriodAdd") . '">';
				print '</td></tr>';
			} else {

				// Add new line from template
				$tmpl_calendar = new Agefoddcalendrier($db);
				$result = $tmpl_calendar->fetch_all();
				if ($result) {
					print '<tr>';
					print '<td colspan="4">';

					print '<input type="hidden" name="periodid" value="' . $stagiaires->lines[$i]->stagerowid . '">' . "\n";
					print '<input type="hidden" id="datetmplday"   name="datetmplday"   value="' . dol_print_date($agf->dated, "%d") . '">' . "\n";
					print '<input type="hidden" id="datetmplmonth" name="datetmplmonth" value="' . dol_print_date($agf->dated, "%m") . '">' . "\n";
					print '<input type="hidden" id="datetmplyear"  name="datetmplyear"  value="' . dol_print_date($agf->dated, "%Y") . '">' . "\n";

					print '<br><strong>' . $langs->trans('AgfCalendarFromTemplate') . '</strong>';
					print '<table class="nobordernopadding">';
					$tmli = 0;
					foreach ( $tmpl_calendar->lines as $line ) {
						if (empty($agf->dated)) {
							$dated = dol_now();
						} else {
							$dated = $agf->dated;
						}
						if ($line->day_session != 1) {
							$tmpldate = dol_time_plus_duree($dated, (($line->day_session) - 1), 'd');
						} else {
							$tmpldate = $dated;
						}

						if ($tmpldate <= $agf->datef) {
							print '<tr>';
							print '<td width="20%" nowrap="nowrap">';
							print '<input type="checkbox" name="fromtemplate[]" id="fromtemplate" value="' . $line->id . '"/>' . dol_print_date($tmpldate, 'daytext') . ' ' . $line->heured . ' - ' . $line->heuref;
							print '</td>';
							if ($user->rights->agefodd->modifier && empty($tmli)) {
								print '<td rowspan="' . count($tmpl_calendar->lines) . '"><input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" align="absmiddle" name="period_add" alt="' . $langs->trans("AgfModSave") . '"></td>';
							}
							print '</tr>';
						}
						$tmli ++;
					}
					print '</table>';
					print '</td>';

					print '</tr>' . "\n";
				}

				print '<tr>';
				print '<td colspan="4"><br><strong>' . $langs->trans('AgfNewPeriodDayToDate') . '</strong></td>';
				print '</tr>';

				print '<tr>';
				print '<td colspan="4">';

				print '<table class="nobordernopadding">';
				print '<tr>';
				print '<td>';
				print $langs->trans('AgfWeekdayModels');
				foreach ( array (
						1,
						2,
						3,
						4,
						5,
						6,
						0
				) as $daynum ) {

					if ($conf->global->{'AGF_WEEKADAY' . $daynum} == 1) {
						$checked = ' checked="checked" ';
					} else {
						$checked = '';
					}

					print '<input type="checkbox" ' . $checked . ' name="fromdaytodate[]" id="fromdaytodate" value="' . $daynum . '"/>' . $langs->trans('Day' . $daynum);
				}
				print '</td>';
				print '<td rowspan="3">';
				print '<input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" align="absmiddle" name="period_daytodate" alt="' . $langs->trans("AgfModSave") . '">';
				print '</td>';
				print '</tr>';

				print '<tr>';
				print '<td>' . $langs->trans("AgfPDFFichePres9") . ' ';
				$form->select_date($agf->dated, 'datedaytodatestart');
				print $langs->trans("AgfPDFFichePres10") . ' ';
				$form->select_date($agf->datef, 'datedaytodateend');
				print '</td>';
				print '</tr>';

				print '<tr>';
				print '<td>' . $langs->trans("AgfPeriodTimeB") . ' ';
				print $formAgefodd->select_time(empty($datedaytodate1d) ? $conf->global->AGF_1DAYSHIFT : $datedaytodate1d, 'datedaytodate1d');

				print $langs->trans("AgfPeriodTimeE");
				print $formAgefodd->select_time(empty($datedaytodate1f) ? $conf->global->AGF_2DAYSHIFT : $datedaytodate1f, 'datedaytodate1f');
				print '</td>';
				print '</tr>';

				print '<tr>';
				print '<td>';
				print $langs->trans("AgfPeriodTimeB") . ' ';
				print $formAgefodd->select_time(empty($datedaytodate2d) ? $conf->global->AGF_3DAYSHIFT : $datedaytodate2d, 'datedaytodate2d');

				print $langs->trans("AgfPeriodTimeE");
				print $formAgefodd->select_time(empty($datedaytodate2f) ? $conf->global->AGF_4DAYSHIFT : $datedaytodate2f, 'datedaytodate2f');

				print '</td>';
				print '</tr>';
				print '</table>';

				print '</td>';
				print '</tr>' . "\n";

				print '<tr>';
				print '<td colspan="4"><br><strong>' . $langs->trans('AgfNewPeriodFromScratch') . '</strong></td>';
				print '</tr>';

				print '<tr>';
				print '<td  width="300px">' . $langs->trans("AgfPeriodDate") . ' ';
				$form->select_date($agf->dated, 'datenew', '', '', '', 'newperiod');
				print '</td>';
				print '<td width="400px">' . $langs->trans("AgfPeriodTimeB") . ' ';
				print $formAgefodd->select_time('08:00', 'datenewd');
				print '</td>';
				print '<td width="400px">' . $langs->trans("AgfPeriodTimeE") . ' ';
				print $formAgefodd->select_time('18:00', 'datenewf');
				print '</td>';
				if ($user->rights->agefodd->modifier) {
					print '<td>';
					print '<input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" align="absmiddle" name="period_add" alt="' . $langs->trans("AgfModSave") . '">';
					print '</td>';
				}

				print '</tr>' . "\n";
			}

			print '</table>';
			print '</form>';
			print '</div>';
		} else {

			print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
			print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
			print '</div>';

			// Print session card
			$agf->printSessionInfo();

			print '&nbsp';

			/*
			 * Manage founding ressources depend type inter-enterprise or extra-enterprise
			 */
			if (! $agf->type_session > 0 && ! empty($conf->global->AGF_MANAGE_OPCA)) {
				print '&nbsp;';
				print '<table class="border" width="100%">';
				print '<tr><td>' . $langs->trans("AgfSubrocation") . '</td>';
				if ($agf->is_OPCA == 1) {
					$isOPCA = ' checked="checked" ';
				} else {
					$isOPCA = '';
				}
				print '<td><input type="checkbox" class="flat" disabled="disabled" readonly="readonly" ' . $isOPCA . '/></td></tr>';

				print '<tr><td width="20%">' . $langs->trans("AgfOPCAName") . '</td>';
				print '	<td>';
				print '<a href="' . dol_buildpath('/societe/soc.php', 1) . '?socid=' . $agf->fk_soc_OPCA . '">' . $agf->soc_OPCA_name . '</a>';
				print '</td></tr>';

				print '<tr><td width="20%">' . $langs->trans("AgfOPCAAdress") . '</td>';
				print '	<td>';
				print dol_print_address($agf->OPCA_adress, 'gmap', 'thirdparty', 0);
				print '</td></tr>';

				print '<tr><td width="20%">' . $langs->trans("AgfOPCAContact") . '</td>';
				print '	<td>';
				print '<a href="' . dol_buildpath('/contact/fiche.php', 1) . '?id=' . $agf->fk_socpeople_OPCA . '">' . $agf->contact_name_OPCA . '</a>';
				print '</td></tr>';

				print '<tr><td width="20%">' . $langs->trans("AgfOPCANumClient") . '</td>';
				print '<td>';
				print $agf->num_OPCA_soc;
				print '</td></tr>';

				print '<tr><td width="20%">' . $langs->trans("AgfOPCADateDemande") . '</td>';
				if ($agf->is_date_ask_OPCA == 1) {
					$chckisDtOPCA = 'checked="checked"';
				} else {
					$chckisDtOPCA = '';
				}
				print '<td><input type="checkbox" class="flat" disabled="disabled" readonly="readonly" name="isdateaskOPCA" value="1" ' . $chckisDtOPCA . ' />';
				print dol_print_date($agf->date_ask_OPCA, 'daytext');
				print '</td></tr>';

				print '<tr><td width="20%">' . $langs->trans("AgfOPCANumFile") . '</td>';
				print '<td>';
				print $agf->num_OPCA_file;
				print '</td></tr>';

				print '</table>';
			}

			/*
			 * Cost management
			 */
			$spend_cost = 0;
			$cashed_cost = 0;

			print '&nbsp;';
			print '<table class="border" width="100%">';
			print '<tr><td width="20%">' . $langs->trans("AgfCoutFormateur") . '</td>';
			print '<td>' . price($agf->cost_trainer) . ' ' . $langs->trans('Currency' . $conf->currency) . '</td></tr>';
			$spend_cost += $agf->cost_trainer;

			print '<tr><td width="20%">' . $langs->trans("AgfCoutSalle") . '</td>';
			print '<td>' . price($agf->cost_site) . ' ' . $langs->trans('Currency' . $conf->currency) . '</td></tr>';
			$spend_cost += $agf->cost_site;

			print '<tr><td width="20%">' . $langs->trans("AgfCoutDeplacement") . '</td>';
			print '<td>' . price($agf->cost_trip) . ' ' . $langs->trans('Currency' . $conf->currency) . '</td></tr>';
			$spend_cost += $agf->cost_trip;

			print '<tr><td width="20%"><strong>' . $langs->trans("AgfCoutTotal") . '</strong></td>';
			print '<td><strong>' . price($spend_cost) . ' ' . $langs->trans('Currency' . $conf->currency) . '</strong></td></tr>';

			print '<tr><td width="20%">' . $langs->trans("AgfCoutFormation") . '</td>';
			print '<td>' . price($agf->sell_price) . ' ' . $langs->trans('Currency' . $conf->currency) . ' ' . $other_amount . '</td></tr>';
			$cashed_cost += $agf->sell_price;

			print '<tr><td width="20%"><strong>' . $langs->trans("AgfCoutRevient") . '</strong></td>';
			if ($cashed_cost > 0) {
				$percentmargin = price(((($cashed_cost - $spend_cost) * 100) / $cashed_cost), 0, $langs, 1, 0, 1) . '%';
			} else {
				$percentmargin = "n/a";
			}

			print '<td><strong>' . price($cashed_cost - $spend_cost) . ' ' . $langs->trans('Currency' . $conf->currency) . '</strong> (' . $percentmargin . ')</td></tr>';

			print '</table>';

			/*
			 * Manage trainers
			 */
			print '&nbsp;';
			print '<table class="border" width="100%">';

			$formateurs = new Agefodd_session_formateur($db);
			$nbform = $formateurs->fetch_formateur_per_session($agf->id);
			print '<tr><td width="20%" valign="top">';
			print $langs->trans("AgfFormateur");
			if ($nbform > 0)
				print ' (' . $nbform . ')';
			print '</td>';
			if ($nbform < 1) {
				print '<td style="text-decoration: blink;">' . $langs->trans("AgfNobody") . '</td></tr>';
			} else {
				print '<td>';

				print '<table class="nobordernopadding">';
				for($i = 0; $i < $nbform; $i ++) {
					print '<tr><td width="50%">';
					// Infos trainers
					print '<a href="' . dol_buildpath('/agefodd/trainer/card.php', 1) . '?id=' . $formateurs->lines[$i]->formid . '">';
					print img_object($langs->trans("ShowContact"), "contact") . ' ';
					print strtoupper($formateurs->lines[$i]->lastname) . ' ' . ucfirst($formateurs->lines[$i]->firstname) . '</a>';
					print ' ' . $formateurs->lines[$i]->getLibStatut(3);
					print '</td>';

					// Print trainer calendar
					if ($conf->global->AGF_DOL_TRAINER_AGENDA) {

						print '<td>';

						print '<table class="nobordernopadding">';

						$alertday = false;
						require_once ('../class/agefodd_session_formateur_calendrier.class.php');
						$trainer_calendar = new Agefoddsessionformateurcalendrier($db);
						$result = $trainer_calendar->fetch_all($formateurs->lines[$i]->opsid);
						if ($result < 0) {
							setEventMessage($trainer_calendar->error, 'errors');
						}
						foreach ( $trainer_calendar->lines as $line ) {
							if (($line->date_session < $agf->dated) || ($line->date_session > $agf->datef))
								$alertday = true;
							print '<tr><td>';
							print dol_print_date($line->heured, 'dayhourtext');
							print '</td></tr>';
							if ($line->heuref != $line->heured) {
								print '<tr><td>';
								print dol_print_date($line->heuref, 'dayhourtext');
								print '</td></tr>';
							}
						}
						// Print warning message if trainer calendar date are not set within session date
						if ($alertday) {
							print img_warning($langs->trans("AgfCalendarDayOutOfScope"));
							print $langs->trans("AgfCalendarDayOutOfScope");
							setEventMessage($langs->trans("AgfCalendarDayOutOfScope"), 'warnings');
						}

						print '</table>';
						print '</td>';
					}
					print '</tr>';
				}

				print '</table>';

				print '</td>';
				print "</tr>\n";
			}
			print "</table>";

			/*
			 * Display trainees
			 */

			print '&nbsp;';
			print '<table class="border" width="100%">';

			$stagiaires = new Agefodd_session_stagiaire($db);
			$resulttrainee = $stagiaires->fetch_stagiaire_per_session($agf->id);
			if ($resulttrainee < 0) {
				setEventMessage($stagiaires->error, 'errors');
			}
			$nbstag = count($stagiaires->lines);
			print '<tr><td  width="20%" valign="top" ';
			if ($nbstag < 1) {
				print '>' . $langs->trans("AgfParticipants") . '</td>';
				print '<td style="text-decoration: blink;">' . $langs->trans("AgfNobody") . '</td></tr>';
			} else {
				print ' rowspan=' . ($nbstag) . '>' . $langs->trans("AgfParticipants");
				if ($nbstag > 1)
					print ' (' . $nbstag . ')';
				print '</td>';

				for($i = 0; $i < $nbstag; $i ++) {
					print '<td witdth="20px" align="center">' . ($i + 1) . '</td>';
					print '<td width="400px" style="border-right: 0px;">';
					// Infos trainee
					if (strtolower($stagiaires->lines[$i]->nom) == "undefined") {
						print $langs->trans("AgfUndefinedStagiaire");
					} else {
						$trainee_info = '<a href="' . dol_buildpath('/agefodd/trainee/card.php', 1) . '?id=' . $stagiaires->lines[$i]->id . '">';
						$trainee_info .= img_object($langs->trans("ShowContact"), "contact") . ' ';
						$trainee_info .= strtoupper($stagiaires->lines[$i]->nom) . ' ' . ucfirst($stagiaires->lines[$i]->prenom) . '</a>';
						$contact_static = new Contact($db);
						$contact_static->civility_id = $stagiaires->lines[$i]->civilite;
						$trainee_info .= ' (' . $contact_static->getCivilityLabel() . ')';

						if ($agf->type_session == 1 && ! empty($conf->global->AGF_MANAGE_OPCA)) {
							print '<table class="nobordernopadding" width="100%"><tr><td colspan="2">';
							print $trainee_info . ' ' . $stagiaires->LibStatut($stagiaires->lines[$i]->status_in_session, 4);
							print '</td></tr>';
							$opca = new Agefodd_opca($db);

							$opca->getOpcaForTraineeInSession($stagiaires->lines[$i]->socid, $agf->id, $stagiaires->lines[$i]->stagerowid);
							print '<tr><td width="45%">' . $langs->trans("AgfSubrocation") . '</td>';
							if ($opca->is_OPCA == 1) {
								$chckisOPCA = 'checked="checked"';
							} else {
								$chckisOPCA = '';
							}
							print '<td><input type="checkbox" class="flat" name="isOPCA" value="1" ' . $chckisOPCA . ' disabled="disabled" readonly="readonly"></td></tr>';

							print '<tr><td>' . $langs->trans("AgfOPCAName") . '</td>';
							print '	<td>';
							print '<a href="' . dol_buildpath('/societe/soc.php', 1) . '?socid=' . $opca->fk_soc_OPCA . '">' . $opca->soc_OPCA_name . '</a>';
							print '</td></tr>';

							print '<tr><td>' . $langs->trans("AgfOPCAContact") . '</td>';
							print '	<td>';
							print '<a href="' . dol_buildpath('/contact/fiche.php', 1) . '?id=' . $opca->fk_socpeople_OPCA . '">' . $opca->contact_name_OPCA . '</a>';
							print '</td></tr>';

							print '<tr><td width="20%">' . $langs->trans("AgfOPCANumClient") . '</td>';
							print '<td>' . $opca->num_OPCA_soc . '</td></tr>';

							print '<tr><td width="20%">' . $langs->trans("AgfOPCADateDemande") . '</td>';
							if ($opca->is_date_ask_OPCA == 1) {
								$chckisDtOPCA = 'checked="checked"';
							} else {
								$chckisDtOPCA = '';
							}
							print '<td><table class="nobordernopadding"><tr><td>';
							print '<input type="checkbox" class="flat" name="isdateaskOPCA" disabled="disabled" readonly="readonly" value="1" ' . $chckisDtOPCA . ' /></td>';
							print '<td>';
							print dol_print_date($opca->date_ask_OPCA, 'daytext');
							print '</td><td>';
							print '</td></tr></table>';
							print '</td></tr>';

							print '<tr><td width="20%">' . $langs->trans("AgfOPCANumFile") . '</td>';
							print '<td>' . $opca->num_OPCA_file . '</td></tr>';

							print '</table>';
						} else {
							print $trainee_info . ' ' . $stagiaires->LibStatut($stagiaires->lines[$i]->status_in_session, 4);
						}
					}
					print '</td>';
					print '<td style="border-left: 0px; border-right: 0px;">';
					// Info funding company
					if ($stagiaires->lines[$i]->socid) {
						print '<a href="' . DOL_URL_ROOT . '/comm/fiche.php?socid=' . $stagiaires->lines[$i]->socid . '">';
						print img_object($langs->trans("ShowCompany"), "company");
						if (! empty($stagiaires->lines[$i]->soccode))
							print ' ' . $stagiaires->lines[$i]->soccode . '-';
						print ' ' . dol_trunc($stagiaires->lines[$i]->socname, 20) . '</a>';
					} else {
						print '&nbsp;';
					}
					print '</td>';
					print '<td style="border-left: 0px;">';
					// Info funding type
					if ($stagiaires->lines[$i]->type && (! empty($conf->global->AGF_USE_STAGIAIRE_TYPE))) {
						print '<div class=adminaction>';
						print $langs->trans("AgfStagiaireModeFinancement");
						print '-<span>' . stripslashes($stagiaires->lines[$i]->type) . '</span></div>';
					} else {
						print '&nbsp;';
					}
					print '</td>';
					print "</tr>\n";
				}
			}
			print "</table>";
			print '</div>';
		}
	} else {
		print $langs->trans('AgfNoSession');
	}
} else {
	setEventMessage($agf->error, 'errors');
}

/*
 * Action tabs
 *
 */

print '<div class="tabsAction">';

if ($action != 'create' && $action != 'edit' && (! empty($agf->id))) {
	if ($user->rights->agefodd->modifier) {
		print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&id=' . $id . '">' . $langs->trans('Modify') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';
	}
	if ($user->rights->agefodd->creer) {
		print '<a class="butAction" href="subscribers.php?action=edit&id=' . $id . '">' . $langs->trans('AgfModifySubscribersAndSubrogation') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfModifySubscribersAndSubrogation') . '</a>';
	}

	if ($user->rights->agefodd->creer) {
		print '<a class="butAction" href="trainer.php?action=edit&id=' . $id . '">' . $langs->trans('AgfModifyTrainer') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfModifyTrainer') . '</a>';
	}

	if ($user->rights->agefodd->creer) {
		print '<a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?action=delete&id=' . $id . '">' . $langs->trans('Delete') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Delete') . '</a>';
	}
	if ($agf->status != 4) {
		$button = $langs->trans('AgfArchiver');
		$arch = 1;
	} else {
		$button = $langs->trans('AgfActiver');
		$arch = 0;
	}
	if ($user->rights->agefodd->modifier) {
		print '<a class="butAction" href="' . dol_buildpath('/agefodd/session/send_docs.php', 1) . '?action=view_actioncomm&id=' . $id . '">' . $langs->trans('AgfViewActioncomm') . '</a>';
		print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=clone&id=' . $id . '">' . $langs->trans('ToClone') . '</a>';
		print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?arch=' . $arch . '&id=' . $id . '">' . $button . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $button . '</a>';
	}
}

print '</div>';

llxFooter();
$db->close();
