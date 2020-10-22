<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2014	Florian Henry	<florian.henry@open-concept.pro>
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
 * \file agefodd/session/trainer.php
 * \ingroup agefodd
 * \brief card of trainer session
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once '../class/agsession.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once '../class/agefodd_session_formateur.class.php';
require_once '../class/agefodd_session_formateur_calendrier.class.php';
require_once '../class/html.formagefodd.class.php';
require_once '../lib/agefodd.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once '../class/agefodd_session_calendrier.class.php';

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$confirm = GETPOST('confirm', 'alpha');
$form_update_x = GETPOST('form_update_x', 'alpha');
$form_add_x = GETPOST('form_add_x', 'alpha');
$period_add = GETPOST('period_add_x', 'alpha');
$period_update = GETPOST('period_update_x', 'alpha');
$newform_var = GETPOST('newform', 'none');
$opsid_var = GETPOST('opsid', 'none');
$form_remove_var = GETPOST('form_remove', 'none');
$period_remove = GETPOST('period_remove', 'none');
$newperiod = GETPOST('newperiod', 'none');
$formid = GETPOST('formid', 'int');
if ($formid == - 1) {
	$formid = 0;
}

$calendrier = new Agefodd_sesscalendar($db);
if (! empty($id)) {
	$result = $calendrier->fetch_all($id);
	if ($result<0) {
		setEventMessages(null,$calendrier->errors,'errors');

	}
}


$delete_calsel = GETPOST('deletecalsel_x', 'alpha');
if (! empty($delete_calsel)) {
	$action = 'delete_calsel';
}

/*
 * Actions delete formateur
 */

if ($action == 'confirm_delete_form' && $confirm == "yes" && $user->rights->agefodd->modifier) {
	$obsid = GETPOST('opsid', 'int');

	$agf = new Agefodd_session_formateur($db);
	$result = $agf->remove($obsid);

	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id . '&action=edit');
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

if ($action == 'edit' && $user->rights->agefodd->modifier) {

	if (empty($formid) && ($form_update_x > 0 || $form_add_x > 0)) {
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->trans('AgfFormateur')), 'errors');
		$form_update_x = 0;
		$form_add_x = 0;
	}

	if ($form_update_x > 0) {

		$agf = new Agefodd_session_formateur($db);

		$agf->opsid = GETPOST('opsid', 'int');
		$agf->formid = $formid;
		$agf->trainer_status = GETPOST('trainerstatus', 'int');
		$agf->trainer_type = GETPOST('trainertype', 'int');
		$result = $agf->update($user);

		if ($result > 0) {
			header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	}

	if ($form_add_x > 0) {
		$agf = new Agefodd_session_formateur($db);

		$agf->sessid = GETPOST('sessid', 'int');
		$agf->formid = $formid;
		$agf->trainer_status = GETPOST('trainerstatus', 'int');
		$agf->trainer_type = GETPOST('trainertype', 'int');
		$result = $agf->create($user);

		if ($result > 0) {
			$TSessCalendarId = GETPOST('TSessCalendarId', 'array');
			if (! empty($TSessCalendarId)) {
				foreach ( $TSessCalendarId as $fk_agefodd_session_calendrier ) {
					$agefodd_sesscalendar = new Agefodd_sesscalendar($db);
					$agefodd_sesscalendar->fetch($fk_agefodd_session_calendrier);

					$agf_cal = new Agefoddsessionformateurcalendrier($db);
					$agf_cal->sessid = $agf->sessid;
					$agf_cal->fk_agefodd_session_formateur = $agf->id;
					$agf_cal->trainer_cost = 0; // price2num(GETPOST('trainer_cost', 'alpha'), 'MU');
					$agf_cal->date_session = $agefodd_sesscalendar->date_session;
					$agf_cal->status=$agefodd_sesscalendar->status;

					$agf_cal->heured = $agefodd_sesscalendar->heured;
					$agf_cal->heuref = $agefodd_sesscalendar->heuref;

					// Test if trainer is already book for another training
					if ($agf_cal->checkTrainerBook($agf->formid) == 0) {
						$result = $agf_cal->create($user);
						if ($result < 0) {
							setEventMessage($agf_cal->error, 'errors');
						}
					}

					if (! empty($agf_cal->errors))
						setEventMessage($agf_cal->errors, 'errors');
					if (! empty($agf_cal->warnings))
						setEventMessage($agf_cal->warnings, 'warnings');
				}
			}

			header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	}
}

if ($action == 'edit_calendrier' && $user->rights->agefodd->modifier) {

	if (! empty($period_add)) {
		$error = 0;
		$error_message = array();
		$warning_message = array();

		$agf_cal = new Agefoddsessionformateurcalendrier($db);

		$agf_cal->sessid = GETPOST('sessid', 'int');
		$agf_cal->fk_agefodd_session_formateur = GETPOST('fk_agefodd_session_formateur', 'int');
		$agf_cal->trainer_cost = price2num(GETPOST('trainer_cost', 'alpha'), 'MU');
		$agf_cal->date_session = dol_mktime(0, 0, 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));
		$agf_cal->status=GETPOST('calendar_trainer_status', 'int');

		// From calendar selection
		$heure_tmp_arr = array();

		$heured_tmp = GETPOST('dated', 'alpha');
		if (! empty($heured_tmp)) {
			$heure_tmp_arr = explode(':', $heured_tmp);
			$agf_cal->heured = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));
		}

		$heuref_tmp = GETPOST('datef', 'alpha');
		if (! empty($heuref_tmp)) {
			$heure_tmp_arr = explode(':', $heuref_tmp);
			$agf_cal->heuref = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));
		}

		// Test if trainer is already book for another training
		if ($agf_cal->checkTrainerBook(GETPOST('trainerid', 'int')) == 0) {
			$result = $agf_cal->create($user);
			if ($result < 0) {
				$error ++;
				setEventMessage($agf_cal->error, 'errors');
			}
		} else {
			$error ++;
		}

		if (! empty($agf_cal->errors)) {
			$error ++;
			setEventMessages(null, $agf_cal->errors, 'errors');
		}
		if (! empty($agf_cal->warnings)) {
			setEventMessages(null, $agf_cal->warnings, 'warnings');
		}

		if (! $error) {
			header('Location: ' . $_SERVER['PHP_SELF'] . '?action=edit_calendrier&id=' . $id);
			exit();
		}
	}

	if (! empty($period_update)) {

		$modperiod = GETPOST('modperiod', 'int');
		$date_session = dol_mktime(0, 0, 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));

		$heure_tmp_arr = array();

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

		$trainer_cost = price2num(GETPOST('trainer_cost', 'alpha'), 'MU');
		$fk_agefodd_session_formateur = GETPOST('fk_agefodd_session_formateur', 'int');

		$agf_cal = new Agefoddsessionformateurcalendrier($db);
		$result = $agf_cal->fetch($modperiod);

		$agf_cal->sessid = GETPOST('sessid', 'int');

        // Je récupère le/les calendrier participants avant modificatino du calendrier formateur
        $TCalendrier = _getCalendrierFromCalendrierFormateur($agf_cal, true, true);

        if (! empty($modperiod))
			$agf_cal->id = $modperiod;
		if (! empty($date_session))
			$agf_cal->date_session = $date_session;
		if (! empty($heured))
			$agf_cal->heured = $heured;
		if (! empty($heuref))
			$agf_cal->heuref = $heuref;
		if (! empty($trainer_cost))
			$agf_cal->trainer_cost = $trainer_cost;
		if (! empty($fk_agefodd_session_formateur))
			$agf_cal->fk_agefodd_session_formateur = $fk_agefodd_session_formateur;

		$agf_cal->status=GETPOST('calendar_trainer_status', 'int');

		// Test if trainer is already book for another training
		$result = $agf_cal->fetch_all_by_trainer(GETPOST('trainerid', 'int'));
		if ($result < 0) {
			$error ++;
			$error_message[] = $agf_cal->error;
		} else {
			foreach ( $agf_cal->lines as $line ) {
				if (! empty($line->trainer_status_in_session) && $line->trainer_status_in_session != 6) {
					if ((($agf_cal->heured <= $line->heured && $agf_cal->heuref >= $line->heuref) || ($agf_cal->heured >= $line->heured && $agf_cal->heuref <= $line->heuref) || ($agf_cal->heured <= $line->heured && $agf_cal->heuref <= $line->heuref && $agf_cal->heuref > $line->heured) || ($agf_cal->heured >= $line->heured && $agf_cal->heuref >= $line->heuref && $agf_cal->heured < $line->heuref)) && $line->fk_session != $id) {
						if (! empty($conf->global->AGF_ONLY_WARNING_ON_TRAINER_AVAILABILITY)) {
							$warning_message[] = $langs->trans('AgfTrainerlAreadybookAtThisTime') . '(<a href=' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?id=' . $line->fk_session . ' target="_blanck">' . $line->fk_session . '</a>)<br>';
						} else {
							$error ++;
							$error_message[] = $langs->trans('AgfTrainerlAreadybookAtThisTime') . '(<a href=' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?id=' . $line->fk_session . ' target="_blanck">' . $line->fk_session . '</a>)<br>';
						}
					}
				}
			}
		}

		if (! $error) {
			$result = $agf_cal->update($user);
			if ($result < 0) {
				$error ++;
				$error_message[] = $agf_cal->error;
			}
			else
            {
                if (!empty($TCalendrier) && is_array($TCalendrier))
                {
                    $agf_calendrier = $TCalendrier[0];
                    $agf_calendrier->date_session = $agf_cal->date_session;
                    $agf_calendrier->heured = $agf_cal->heured;
                    $agf_calendrier->heuref = $agf_cal->heuref;
                    $agf_calendrier->status = $agf_cal->status;
//                    $agf_calendrier->calendrier_type = $code_c_session_calendrier_type;
                    $r=$agf_calendrier->update($user);
                }
                elseif (is_string($TCalendrier))
                {
                    setEventMessage($TCalendrier, 'errors');
                }
            }
		}

		if (count($warning_message) > 0) {
			setEventMessages(null, $warning_message, 'warnings');
		}

		if (! $error) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit_calendrier&id=" . $id);
			exit();
		} else {
			setEventMessages(null, $error_message, 'errors');
		}
	}

	$copysessioncalendar = GETPOST('copysessioncalendar', 'none');
	if (! empty($copysessioncalendar)) {

		$fk_agefodd_session_formateur = GETPOST('fk_agefodd_session_formateur', 'int');

		// Delete all time already inputed
		$agf_cal = new Agefoddsessionformateurcalendrier($db);
		$agf_cal->fetch_all($fk_agefodd_session_formateur);
		if (is_array($agf_cal->lines) && count($agf_cal->lines) > 0) {
			foreach ( $agf_cal->lines as $line ) {
				$delteobject = new Agefoddsessionformateurcalendrier($db);
				$delteobject->remove($line->id);
			}
		}

		// Create as many as session caldendar
		$agf_session_cal = new Agefodd_sesscalendar($db);
		$agf_session_cal->fetch_all($id);
		if (is_array($agf_session_cal->lines) && count($agf_session_cal->lines) > 0) {
			foreach ( $agf_session_cal->lines as $line ) {

				$agf_cal = new Agefoddsessionformateurcalendrier($db);

				$agf_cal->sessid = $id;
				$agf_cal->fk_agefodd_session_formateur = $fk_agefodd_session_formateur;

				$agf_cal->date_session = $line->date_session;

				$agf_cal->heured = $line->heured;
				$agf_cal->heuref = $line->heuref;

				$agf_cal->status = $line->status;

				// Test if trainer is already book for another training
				$result = $agf_cal->fetch_all_by_trainer(GETPOST('trainerid', 'int'));
				if ($result < 0) {
					$error ++;
					$error_message[] = $agf_cal->error;
				}

				foreach ( $agf_cal->lines as $line ) {
					if (! empty($line->trainer_status_in_session) && $line->trainer_status_in_session != 6) {

						if (($agf_cal->heured <= $line->heured && $agf_cal->heuref >= $line->heuref) || ($agf_cal->heured >= $line->heured && $agf_cal->heuref <= $line->heuref) || ($agf_cal->heured <= $line->heured && $agf_cal->heuref <= $line->heuref && $agf_cal->heuref > $line->heured) || ($agf_cal->heured >= $line->heured && $agf_cal->heuref >= $line->heuref && $agf_cal->heured < $line->heuref)) {
							if (! empty($conf->global->AGF_ONLY_WARNING_ON_TRAINER_AVAILABILITY)) {
								$warning_message[] = $langs->trans('AgfTrainerlAreadybookAtThisTime') . '(<a href=' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?id=' . $line->fk_session . ' target="_blanck">' . $line->fk_session . '</a>)<br>';
							} else {
								$error ++;
								$error_message[] = $langs->trans('AgfTrainerlAreadybookAtThisTime') . '(<a href=' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?id=' . $line->fk_session . ' target="_blanck">' . $line->fk_session . '</a>)<br>';
							}
						}
					}
				}

				if (! $error) {

					$result = $agf_cal->create($user);
					if ($result < 0) {
						$error ++;
						$error_message[] = $agf_cal->error;
					}
				}
			}
		}

		if (! empty($warning_message)) {
			setEventMessages(null, $warning_message, 'warnings');
		}

		if (! $error) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit_calendrier&id=" . $id);
			exit();
		} else {
			setEventMessages(null, $error_message, 'errors');
		}
	}
}

if ($action == 'delete_calsel') {
	$deleteselcal = GETPOST('deleteselcal', 'array');
	if (count($deleteselcal) > 0) {
		foreach ( $deleteselcal as $lineid ) {
			$agf = new Agefoddsessionformateurcalendrier($db);
			$result = $agf->remove($lineid);
			if ($result < 0) {
				setEventMessage($agf->error, 'errors');
				$error ++;
			}
		}
	}
	if (! $error) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit_calendrier&id=" . $id);
		exit();
	}
}

/*
 * Actions delete period
 */

if ($action == 'confirm_delete_period' && $confirm == "yes" && $user->rights->agefodd->modifier) {
	$modperiod = GETPOST('modperiod', 'int');

	$agf = new Agefoddsessionformateurcalendrier($db);
	$result = $agf->remove($modperiod);

	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit_calendrier&id=" . $id);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

/*
 * View
 */

llxHeader('', $langs->trans("AgfSessionDetail"));

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

if (! empty($id)) {
	$agf = new Agsession($db);
	$result = $agf->fetch($id);

	$head = session_prepare_head($agf);

	dol_fiche_head($head, 'trainers', $langs->trans("AgfSessionDetail"), 0, 'group');

	dol_agefodd_banner_tab($agf, 'action=edit&id');
	dol_fiche_end();

	print load_fiche_titre($langs->trans("AgfFormateur"), '', '');

	/*
	 * Confirm delete calendar
	 */

	if (! empty($period_remove)) {
		// Param url = id de la periode à supprimer - id session
		print $form->formconfirm($_SERVER['PHP_SELF'] . '?modperiod=' . GETPOST('modperiod', 'none') . '&id=' . $id, $langs->trans("AgfDeletePeriod"), $langs->trans("AgfConfirmDeletePeriod"), "confirm_delete_period", '', '', 1);
	}

	$rowf_var = GETPOST('rowf', 'none');
	$trainerid_var = GETPOST('trainerid', 'none');
	if ($action == 'edit_calendrier' && (! empty($rowf_var) || ! empty($trainerid_var))) {

		$anchroid = empty($rowf_var) ? $trainerid_var : $rowf_var;

		print '<script type="text/javascript">
						jQuery(document).ready(function () {
							jQuery(function() {' . "\n";
		print '				 $(\'html, body\').animate({scrollTop: $("#anchorrowf' . $anchroid . '").offset().top-20}, 500,\'easeInOutCubic\');';
		print '			});
					});
					</script> ';
	}

	if ($action == 'edit') {

		print '<script type="text/javascript">
					jQuery(document).ready(function () {
						jQuery(function() {' . "\n";
		if (! empty($newform_var)) {
			print '				 $(\'html, body\').animate({scrollTop: $("#anchornewform").offset().top}, 500,\'easeInOutCubic\');';
		} elseif (! empty($opsid_var) && empty($form_remove_var)) {
			print '				 $(\'html, body\').animate({scrollTop: $("#anchoropsid' . GETPOST('opsid', 'none') . '").offset().top}, 500,\'easeInOutCubic\');';
		}
		print '			});
					});
					</script> ';

		/*
		 * Confirm Delete
		 */
		if (! empty($form_remove_var)) {
			// Param url = id de la ligne formateur dans session - id session
			print $form->formconfirm($_SERVER['PHP_SELF'] . "?opsid=" . GETPOST('opsid', 'none') . '&id=' . $id, $langs->trans("AgfDeleteForm"), $langs->trans("AgfConfirmDeleteForm"), "confirm_delete_form", '', '', 1);
		}

		print '<form name="form_update" action="' . $_SERVER['PHP_SELF'] . '?action=edit&amp;id=' . $id . '"  method="POST">' . "\n";
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
		print '<input type="hidden" name="action" value="edit">' . "\n";
		print '<input type="hidden" name="sessid" value="' . $id . '">' . "\n";

		print '<table class="noborder" width="100%">' . "\n";

		print '<tr class="liste_titre">';
		print '<th class="liste_titre">&nbsp;</th>';
		print '<th class="liste_titre name">Nom</th>';
		if (! $user->rights->agefodd->session->trainer) {
			print '<th class="liste_titre status">Statut</th>';
		}
		if (! empty($conf->global->AGF_DOL_TRAINER_AGENDA)) {
			print '<th class="liste_titre temps_total_prog">Temps total programme</th>';
			print '<th class="liste_titre temps_prog">Temps programme</th>';
		}
		print '<th class="liste_titre actions">&nbsp;</th>';
		print '</tr>' . "\n";

		// Create as many as session caldendar
		$agf_session_cal = new Agefodd_sesscalendar($db);
		$result = $agf_session_cal->fetch_all($agf->id);
		if ($result < 0) {
			setEventMessages(null, $agf_session_cal->errors, 'errors');
		}

		// Display edit and update trainer
		$formateurs = new Agefodd_session_formateur($db);
		$nbform = $formateurs->fetch_formateur_per_session($agf->id);
		if ($nbform > 0) {
			for($i = 0; $i < $nbform; $i ++) {
				if ($formateurs->lines[$i]->opsid == GETPOST('opsid', 'none') && ! empty($form_remove_var))
					print '<tr class="oddeven" style="background:#d5baa8">';
				else
					print '<tr class="oddeven">' . "\n";

				print '<td width="20px" align="center">' . ($i + 1);
				print '<a id="anchoropsid' . $formateurs->lines[$i]->opsid . '" name="anchoropsid' . $formateurs->lines[$i]->opsid . '" href="#anchoropsid' . $formateurs->lines[$i]->opsid . '"></a>';
				print '</td>' . "\n";

				// Edit line

				if ($formateurs->lines[$i]->opsid == GETPOST('opsid', 'none') && empty($form_remove_var)) {
					print '<td class="name">' . "\n";
					print '<input type="hidden" name="opsid" value="' . $formateurs->lines[$i]->opsid . '">' . "\n";

					$filterSQL = ' ((s.rowid NOT IN (SELECT fk_agefodd_formateur FROM ' . MAIN_DB_PREFIX . 'agefodd_session_formateur WHERE fk_session=' . $id . '))';
					if ($conf->global->AGF_FILTER_TRAINER_TRAINING) {
						$filterSQL .= ' AND (s.rowid IN (SELECT fk_trainer FROM ' . MAIN_DB_PREFIX . 'agefodd_formateur_training WHERE fk_training=' . $agf->formid . '))';
					}
					$filterSQL .= ') OR s.rowid=' . $formateurs->lines[$i]->formid;

					print $formAgefodd->select_formateur($formateurs->lines[$i]->formid, "formid", $filterSQL);
					if (! empty($conf->global->AGF_USE_FORMATEUR_TYPE)) {
						print '&nbsp;';
						print $formAgefodd->select_type_formateur($formateurs->lines[$i]->trainer_type, "trainertype", ' active=1 ');
					}
					print '</td>' . "\n";

					if (! $user->rights->agefodd->session->trainer) {
						print '<td class="status">' . "\n";
						print $formAgefodd->select_trainer_session_status('trainerstatus', $formateurs->lines[$i]->trainer_status);
					}

					print '</td>' . "\n";

					if (! empty($conf->global->AGF_DOL_TRAINER_AGENDA)) {
						print '<td class="temps_total_prog">&nbsp;</td>' . "\n";
						print '<td class="temps_prog">&nbsp;</td>' . "\n";
					}

					print '<td class="actions" align="right">' . "\n";
					if ($user->rights->agefodd->modifier) {
						print '<input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" align="absmiddle" name="form_update" alt="' . $langs->trans("Save") . '">' . "\n";
					}
					print '</td>' . "\n";
				} else {
					// trainer info
					if (strtolower($formateurs->lines[$i]->lastname) == "undefined") {
						print '<td class="name">' . $langs->trans("AgfUndefinedTrainer") . '</td>' . "\n";
						if (! $user->rights->agefodd->session->trainer) {
							print '<td class="status">&nbsp;</td>' . "\n";
						}
						print '<td class="temps_total_prog">&nbsp;</td>' . "\n";
						print '<td class="temps_prog">&nbsp;</td>' . "\n";
					} else {
						print '<td class="name">' . "\n";
						print '<a href="' . dol_buildpath('/agefodd/trainer/card.php', 1) . '?id=' . $formateurs->lines[$i]->formid . '">' . "\n";
						print img_object($langs->trans("ShowContact"), "contact") . ' ';
						print strtoupper($formateurs->lines[$i]->lastname) . ' ' . ucfirst($formateurs->lines[$i]->firstname) . '</a>' . "\n";

						if (! empty($conf->global->AGF_USE_FORMATEUR_TYPE)) {
							print '<BR>';
							print $formateurs->lines[$i]->trainer_type_label;
						}

						print '</td>' . "\n";

						if (! $user->rights->agefodd->session->trainer) {
							print '<td class="status">' . $formateurs->lines[$i]->getLibStatut(2) . '</td>' . "\n";
						}

						$totaltimetrainer = '';
						$hourhtml = '';
						if ($conf->global->AGF_DOL_TRAINER_AGENDA) {
							// Calculate time past in session
							$trainer_calendar = new Agefoddsessionformateurcalendrier($db);
							$result = $trainer_calendar->fetch_all($formateurs->lines[$i]->opsid);
							if ($result < 0) {
								setEventMessage($trainer_calendar->error, 'errors');
							}

							if (! empty($trainer_calendar->lines)) {

								$hourhtml .= '<table class="nobordernopadding">' . "\n";
								$blocNumber = count($trainer_calendar->lines);
								$old_date = 0;
								$totaltime = 0;

								for($j = 0; $j < $blocNumber; $j ++) {
									// Find if time is solo plateform for trainee
									$platform_time = false;
									if (is_array($agf_session_cal->lines) && count($agf_session_cal->lines) > 0) {
										foreach ( $agf_session_cal->lines as $line_cal ) {
											if (
												$line_cal->calendrier_type == 'AGF_TYPE_PLATF' &&
												(
													(
														$trainer_calendar->lines[$j]->heured <= $line_cal->heured &&
														$trainer_calendar->lines[$j]->heuref >= $line_cal->heuref
													)
													||
													(
														$trainer_calendar->lines[$j]->heured >= $line_cal->heured &&
														$trainer_calendar->lines[$j]->heuref <= $line_cal->heuref
													)
													||
													(
														$trainer_calendar->lines[$j]->heured <= $line_cal->heured &&
														$trainer_calendar->lines[$j]->heuref <= $line_cal->heuref &&
														$trainer_calendar->lines[$j]->heuref > $line_cal->heured
													)
													||
													(
														$trainer_calendar->lines[$j]->heured >= $line_cal->heured &&
														$trainer_calendar->lines[$j]->heuref >= $line_cal->heuref &&
														$trainer_calendar->lines[$j]->heured < $line_cal->heuref
													)
												)
											) {
												$platform_time = true;
												break;
											}
										}
									}

									if ($trainer_calendar->lines[$j]->status == Agefoddsessionformateurcalendrier::STATUS_CANCELED || $platform_time)
										continue;
									$totaltime += $trainer_calendar->lines[$j]->heuref - $trainer_calendar->lines[$j]->heured;

									if ($j > 6) {
										$styledisplay = " style=\"display:none\" class=\"otherdatetrainer\" ";
									} else {
										$styledisplay = " ";
									}

									$hourhtml .= '<tr ' . $styledisplay . '>' . "\n";
									$hourhtml .= '<td width="100px">' . "\n";
									$hourhtml .= dol_print_date($trainer_calendar->lines[$j]->date_session, 'daytextshort') . '</td>' . "\n";
									$hourhtml .= '<td width="100px">' . "\n";
									if (! $user->rights->agefodd->session->trainer) {
                                        $hourDisplay = dol_print_date($trainer_calendar->lines[$j]->heured, 'hour') . ' - ' . dol_print_date($trainer_calendar->lines[$j]->heuref, 'hour');
										$hourhtml .= _isTrainerFreeBadge($hourDisplay, $trainer_calendar->lines[$j], $formateurs->lines[$i]->formid);
									}
									$hourhtml.= '<td>'.Agefodd_sesscalendar::getStaticLibStatut($trainer_calendar->lines[$j]->status, 0).'</td>'."\n";
									$hourhtml .= '</td></tr>' . "\n";
								}

								if ($blocNumber > 6) {
									$hourhtml .= '<tr><td colapsn="2" style="font-weight: bold; font-size:150%; cursor:pointer" class="switchtimetrainer">+</td></tr>';
								}
								$hourhtml .= '</table>' . "\n";

								$min = floor($totaltime / 60);
								$rmin = sprintf("%02d", $min % 60);
								$hour = floor($min / 60);

								$totaltimetrainer = '(' . $hour . ':' . $rmin . ')';
							}
							print '<td class="temps_total_prog">' . $totaltimetrainer . '</td>' . "\n";
							print '<td class="temps_prog">' . $hourhtml . '</td>' . "\n";
						}
					}

					print '<td class="action" align="right">' . "\n";

					if ($user->rights->agefodd->modifier && ! $user->rights->agefodd->session->trainer) {
						print
								'<a href="' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?action=edit&amp;sessid=' . $formateurs->lines[$i]->sessid . '&amp;opsid=' . $formateurs->lines[$i]->opsid . '&amp;id=' . $id . '&amp;form_edit=1">' . img_picto($langs->trans("Edit"), 'edit') . '</a>' . "\n";
					}
					print '&nbsp;';
					if ($user->rights->agefodd->modifier && ! $user->rights->agefodd->session->trainer) {
						print
								'<a href="' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?action=edit&amp;sessid=' . $formateurs->lines[$i]->sessid . '&amp;opsid=' . $formateurs->lines[$i]->opsid . '&amp;id=' . $id . '&amp;form_remove=1">' . img_picto($langs->trans("Delete"), 'delete') . '</a>' . "\n";
					}
					if ($user->rights->agefodd->modifier && ! empty($conf->global->AGF_DOL_TRAINER_AGENDA) && ! $user->rights->agefodd->session->trainer) {
						print '&nbsp;';
						print '<a href="' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?action=edit_calendrier&amp;id=' . $id . '&amp;rowf=' . $formateurs->lines[$i]->formid . '">' . img_picto($langs->trans('Time'), 'calendar', '', false, 0, 0, '', 'valignmiddle') . '</a>' . "\n";
					}
					print '</td>' . "\n";
				}

				print '</tr>' . "\n";
			}

			print '<script>' . "\n";
			print '$(document).ready(function () { ' . "\n";
			print '		$(".switchtimetrainer").click(function(){' . "\n";
			print '         $(this).parent().parent().find(".otherdatetrainer").each(function(){$(this).toggle()});';
			print '			if ($(this).text()==\'+\') { ' . "\n";
			print '				$(this).text(\'-\'); ' . "\n";
			print '			}else { ' . "\n";
			print '				$(this).text(\'+\'); ' . "\n";
			print '			} ' . "\n";
			print '			' . "\n";
			print '		});' . "\n";
			print '});' . "\n";
			print '</script>' . "\n";
		}

		// New trainers
		if (! empty($newform_var) && ! empty($user->rights->agefodd->modifier)) {
			print '<tr class="oddeven newline">' . "\n";

			print '<td width="20px" align="center"><a id="anchornewform" name="anchornewform"/>' . ($i + 1) . '</td>';
			print '<td class="name nowrap">';

			$filterSQL = 's.rowid NOT IN (SELECT fk_agefodd_formateur FROM ' . MAIN_DB_PREFIX . 'agefodd_session_formateur WHERE fk_session=' . $id . ')';
			if ($conf->global->AGF_FILTER_TRAINER_TRAINING) {
				$filterSQL .= ' AND s.rowid IN (SELECT fk_trainer FROM ' . MAIN_DB_PREFIX . 'agefodd_formateur_training WHERE fk_training=' . $agf->formid . ')';
			}
			print $formAgefodd->select_formateur($formateurs->lines[$i]->formid, "formid", $filterSQL, 1);
			if (! empty($conf->global->AGF_USE_FORMATEUR_TYPE)) {
				print '&nbsp;';
				print $formAgefodd->select_type_formateur($conf->global->AGF_DEFAULT_FORMATEUR_TYPE, "trainertype", ' active=1 ');
			}
			print '</td>' . "\n";

			if (! $user->rights->agefodd->session->trainer) {
				print '<td class="status">' . "\n";
				print $formAgefodd->select_trainer_session_status('trainerstatus', $formateurs->lines[$i]->trainer_status);
				print '</td>' . "\n";
			}

			if (! empty($conf->global->AGF_DOL_TRAINER_AGENDA)) {
				print '<td class="temps_total_prog">&nbsp;</td>' . "\n";
				print '<td class="temps_prog">&nbsp;</td>' . "\n";
			}

			print '<td class="actions" align="right">';
			if ($user->rights->agefodd->modifier) {
				print '<input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" align="absmiddle" name="form_add" alt="' . $langs->trans("Save") . '">' . "\n";
			}
			print '</td>' . "\n";

			print '</tr>' . "\n";
			if ($calendrier->fetch_all($id) > 0) {
				print '<tr class="">' . "\n";
				$colspan = 3; // name / status / actions
				if (! empty($conf->global->AGF_DOL_TRAINER_AGENDA))
					$colspan += 2; // temps_total_prog / temps_prog
				if (! $user->rights->agefodd->session->trainer) {
					$colspan --;
				}
				print '<td><input type="checkbox" onclick="$(\'input[name^=TSessCalendarId\').prop(\'checked\', this.checked)" /></td>' . "\n";
				print '<td colspan="' . $colspan . '">' . "\n";

				print '<ul class="nocellnopadd">' . "\n"; // tmenu / nocellnopadd
				foreach ( $calendrier->lines as &$agefodd_sesscalendar ) {
					print
							'<li><input type="checkbox" name="TSessCalendarId[]" value="' . $agefodd_sesscalendar->id . '"> ' . dol_print_date($agefodd_sesscalendar->date_session, 'daytext') . ' [' . dol_print_date($agefodd_sesscalendar->heured, 'hour') . ' - ' . dol_print_date(
									$agefodd_sesscalendar->heuref, 'hour') . ']</li>';
				}
				print '</ul>' . "\n";

				print '</td>' . "\n";

				print '</tr>' . "\n";
			} else {
				setEventMessages(null, $calendrier->errors,'errors');
			}
		}

		print '</table>' . "\n";
	} else {
		// Display view mode
		print '<table class="noborder" width="100%">' . "\n";

		print '<tr class="liste_titre">' . "\n";
		print '<th class="liste_titre name">Nom</th>' . "\n";
		print '<th class="liste_titre status">Statut</th>' . "\n";
		if (! empty($conf->global->AGF_DOL_TRAINER_AGENDA)) {
			print '<th class="liste_titre temps_total_prog">&nbsp;</th>' . "\n";
			print '<th class="liste_titre temps_total">&nbsp;</th>' . "\n";
		}
		print '</tr>';

		$agf_session_cal = new Agefodd_sesscalendar($db);
		$result = $agf_session_cal->fetch_all($agf->id);
		if ($result < 0) {
			setEventMessages(null, $agf_session_cal->errors, 'errors');
		}

		$formateurs = new Agefodd_session_formateur($db);
		$nbform = $formateurs->fetch_formateur_per_session($agf->id);

		if ($nbform < 1) {
			print '<td style="text-decoration: blink;"><BR><BR>' . $langs->trans("AgfNobody") . '</td></tr>' . "\n";
			print '<table style="border:0;" width="100%">';
			print '<tr><td align="right">';
			print '<form name="newform" action="' . $_SERVER['PHP_SELF'] . '?action=edit&amp;id=' . $id . '"  method="POST">' . "\n";
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
			print '<input type="hidden" name="action" value="edit">' . "\n";
			print '<input type="hidden" name="newform" value="1">' . "\n";
			print '<input type="submit" class="butAction" value="' . $langs->trans("AgfFormateurAdd") . '">' . "\n";
			print '</form></td></tr>' . "\n";
			print '</table>' . "\n";
		} else {

			for($i = 0; $i < $nbform; $i ++) {
				print '<tr class="oddeven">';

				// Trainer name
				print '<td class="name">';
				print '<a id="anchoropsid' . $formateurs->lines[$i]->opsid . '" name="anchoropsid' . $formateurs->lines[$i]->opsid . '" href="#anchoropsid' . $formateurs->lines[$i]->opsid . '"></a>';
				print '<a id="anchorrowf' . $formateurs->lines[$i]->formid . '" name="anchorrowf' . $formateurs->lines[$i]->formid . '" href="#anchorrowf' . $formateurs->lines[$i]->formid . '"></a>';
				print '<a href="' . dol_buildpath('/agefodd/trainer/card.php', 1) . '?id=' . $formateurs->lines[$i]->formid . '">';
				print img_object($langs->trans("ShowContact"), "contact") . ' ';
				print strtoupper($formateurs->lines[$i]->lastname) . ' ' . ucfirst($formateurs->lines[$i]->firstname) . '</a>';
				print '</td>';

				// Trainer status
				print '<td class="status">' . $formateurs->lines[$i]->getLibStatut(2) . '</td>';

				if (! empty($conf->global->AGF_DOL_TRAINER_AGENDA)) {
					print '<td class="temps_total_prog">';
					// Calculate time past in session
					$trainer_calendar = new Agefoddsessionformateurcalendrier($db);
					$result = $trainer_calendar->fetch_all($formateurs->lines[$i]->opsid);
					if ($result < 0) {
						setEventMessage($trainer_calendar->error, 'errors');
					}

					$totaltime = 0;
					foreach ( $trainer_calendar->lines as $line_trainer_calendar ) {
						// Find if time is solo plateform for trainee
						$platform_time = false;
						if ($result > 0 && is_array($agf_session_cal->lines) && count($agf_session_cal->lines) > 0) {
							foreach ( $agf_session_cal->lines as $line_cal ) {
								if (
									$line_cal->calendrier_type == 'AGF_TYPE_PLATF' &&
									(
										(
											$line_trainer_calendar->heured <= $line_cal->heured &&
											$line_trainer_calendar->heuref >= $line_cal->heuref
										)
										||
										(
											$line_trainer_calendar->heured >= $line_cal->heured &&
											$line_trainer_calendar->heuref <= $line_cal->heuref
										)
										||
										(
											$line_trainer_calendar->heured <= $line_cal->heured &&
											$line_trainer_calendar->heuref <= $line_cal->heuref &&
											$line_trainer_calendar->heuref > $line_cal->heured
										)
										||
										(
											$line_trainer_calendar->heured >= $line_cal->heured &&
											$line_trainer_calendar->heuref >= $line_cal->heuref &&
											$line_trainer_calendar->heured < $line_cal->heuref
										)
									)
								) {
									$platform_time = true;
									break;
								}
							}
						}

						if (!$platform_time) $totaltime += $line_trainer_calendar->heuref - $line_trainer_calendar->heured;
					}
					$min = floor($totaltime / 60);
					$rmin = sprintf("%02d", $min % 60);
					$hour = floor($min / 60);

					print '(' . $hour . ':' . $rmin . ')';
					print '</td>';
				}

				print '<td class="edit_agenda">';
				if (! empty($conf->global->AGF_DOL_TRAINER_AGENDA)) {
					/* Time management */
					$calendrier = new Agefoddsessionformateurcalendrier($db);
					$calendrier->fetch_all($formateurs->lines[$i]->opsid);
					$blocNumber = count($calendrier->lines);

					if ($blocNumber < 1 && ! (empty($newperiod))) {
						print '<span style="color:red;">' . $langs->trans("AgfNoCalendar") . '</span>';
					} else {
						// print '<td>';
						print '<form name="trainer_calendrier_update" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '"  method="POST">' . "\n";
						print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
						print '<input type="hidden" name="sessid" value="' . $id . '">' . "\n";

						print '<table width="100%" class="border">';

						print '<tr class="liste_titre">';
						print '<th class="liste_titre">';
						if ($user->rights->agefodd->modifier) {
							print '<input type="image" src="' . img_picto($langs->trans("Delete"), 'delete', '', false, 1) . '" border="0" align="absmiddle" name="deletecalsel" title="' . $langs->trans("AgfDeleteOnlySelectedLines") . '" alt="' . $langs->trans("AgfDeleteOnlySelectedLines") . '">';
						}
						print '</th>';
						print '<th class="liste_titre">' . $langs->trans('Date') . '</th>';
						print '<th class="liste_titre">' . $langs->trans('Hours') . '</th>';
						print '<th class="liste_titre">' . $langs->trans('Status') . '</th>';
						// Trainer cost is fully managed into cost management not here
						if (empty($conf->global->AGF_ADVANCE_COST_MANAGEMENT)) {
							print '<th class="liste_titre">' . $langs->trans('AgfTrainerCostHour') . '</th>';
						}
						print '<th class="liste_titre">' . $langs->trans('Edit') . '</th>';

						print '</tr>';

						$old_date = 0;
						$duree = 0;
						for($j = 0; $j < $blocNumber; $j ++) {
							if ($calendrier->lines[$j]->id == GETPOST('modperiod', 'none') && ! empty($period_remove))
								print '<tr bgcolor="#d5baa8">' . "\n";
							else
								print '<tr>' . "\n";

							if ($calendrier->lines[$j]->id == GETPOST('modperiod', 'none') && empty($period_remove)) {
								// Delete select case not display here
								print '<td></td>' . "\n";

								print '<td  width="20%">' . $langs->trans("AgfPeriodDate") . ' ' . "\n";
								$form->select_date($calendrier->lines[$j]->date_session, 'date', '', '', '', 'obj_update_' . $j);

								print '<input type="hidden" name="action" value="edit_calendrier">' . "\n";
								print '<input type="hidden" name="fk_agefodd_session_formateur" value="' . $formateurs->lines[$i]->opsid . '">' . "\n";
								print '<input type="hidden" name="periodid" value="' . $calendrier->lines[$j]->stagerowid . '">' . "\n";
								print '<input type="hidden" name="trainerid" value="' . $formateurs->lines[$i]->formid . '">' . "\n";
								print '<input type="hidden" name="modperiod" value="' . $calendrier->lines[$j]->id . '">' . "\n";

								print '</td>' . "\n";
								print '<td width="40%;" >' . $langs->trans("AgfPeriodTimeB") . ' ' . "\n";
								print $formAgefodd->select_time(dol_print_date($calendrier->lines[$j]->heured, 'hour'), 'dated');
								print ' - ' . $langs->trans("AgfPeriodTimeE") . ' ';
								print $formAgefodd->select_time(dol_print_date($calendrier->lines[$j]->heuref, 'hour'), 'datef');
								print '</td>' . "\n";
								print '<td>' . $formAgefodd->select_calendrier_status($calendrier->lines[$j]->status, 'calendar_trainer_status') . '</td>';

								// Trainer cost is fully managed into cost management not here
								if (empty($conf->global->AGF_ADVANCE_COST_MANAGEMENT)) {
									// Coût horaire
									print '<td width="20%"> <input type="text" size="10" name="trainer_cost" value="' . price($calendrier->lines[$i]->trainer_cost) . '"/>' . $langs->getCurrencySymbol($conf->currency) . '</td>' . "\n";
								}
								if ($user->rights->agefodd->modifier) {
									print '<td width="30%;"><input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" align="absmiddle" name="period_update" alt="' . $langs->trans("Save") . '"></td>' . "\n";
								}
							} else {
								print '<td width="1%;">';
								if ($user->rights->agefodd->modifier) {
									print '<input type="checkbox" name="deleteselcal[]" value="' . $calendrier->lines[$j]->id . '"/>';
								}
								print '</td>' . "\n";
								print '<td width="20%">' . dol_print_date($calendrier->lines[$j]->date_session, 'daytext') . '</td>' . "\n";
                                $hourDisplay = dol_print_date($calendrier->lines[$j]->heured, 'hour') . ' - ' . dol_print_date($calendrier->lines[$j]->heuref, 'hour');
                                $hourDisplay = _isTrainerFreeBadge($hourDisplay, $calendrier->lines[$j], $formateurs->lines[$i]->opsid);
								print '<td  width="40%">' . $hourDisplay  . '</td>';
								print '<td>' . Agefodd_sesscalendar::getStaticLibStatut($calendrier->lines[$j]->status, 0) . '</td>';

								// Trainer cost is fully managed into cost management not here
								if (empty($conf->global->AGF_ADVANCE_COST_MANAGEMENT)) {
									// Coût horaire
									print '<td>' . price($calendrier->lines[$j]->trainer_cost, 0, $langs) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>' . "\n";
								}

								print '<td width="30%;">';
								if ($user->rights->agefodd->modifier) {
									print
											'<a href="' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?action=edit_calendrier&amp;sessid=' . $id . '&amp;modperiod=' . $calendrier->lines[$j]->id . '&amp;trainerid=' . $formateurs->lines[$i]->formid . '&amp;id=' . $id . '&amp;period_edit=1">' . img_picto(
													$langs->trans("Edit"), 'edit') . '</a>' . "\n";
								}
								print '&nbsp;';
								if ($user->rights->agefodd->creer) {
									print
											'<a href="' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?action=edit_calendrier&amp;sessid=' . $id . '&amp;modperiod=' . $calendrier->lines[$j]->id . '&amp;trainerid=' . $formateurs->lines[$i]->formid . '&amp;id=' . $id . '&amp;period_remove=1">' . img_picto(
													$langs->trans("Delete"), 'delete') . '</a>' . "\n";
								}
								print '</td>' . "\n";
							}

							// We calculated the total session duration time
							$duree += ($calendrier->lines[$j]->heuref - $calendrier->lines[$j]->heured);

							print '</tr>' . "\n";
						}

						// Fiels for new periodes
						if (! empty($newperiod)) {
							print '<td align="right">';
							print '<form name="newperiod" action="' . $_SERVER['PHP_SELF'] . '?action=edit_calendrier&id=' . $id . '"  method="POST">' . "\n";
							print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
							print '<input type="hidden" name="action" value="edit_calendrier">' . "\n";
							print '<input type="hidden" name="newperiod" value="1">' . "\n";
							print '<input type="submit" class="butAction" value="' . $langs->trans("AgfPeriodAdd") . '">' . "\n";
							print '</form>' . "\n";
							print '</td>' . "\n";
						} else {
							if ($action == "edit_calendrier" && GETPOST('rowf', 'none') == $formateurs->lines[$i]->formid) {
								print '<tr>';
								print '<td></td>';
								print '<td  width="300px">';
								print '<input type="hidden" name="action" value="edit_calendrier">' . "\n";
								print '<input type="hidden" name="sessid" value="' . $agf->id . '">' . "\n";
								print '<input type="hidden" name="fk_agefodd_session_formateur" value="' . $formateurs->lines[$i]->opsid . '">' . "\n";
								print '<input type="hidden" name="periodid" value="' . $calendrier->lines[$j]->stagerowid . '">' . "\n";
								print '<input type="hidden" name="trainerid" value="' . $formateurs->lines[$i]->formid . '">' . "\n";
								print '<input type="hidden" id="datetmplday"   name="datetmplday"   value="' . dol_print_date($agf->dated, "%d") . '">' . "\n";
								print '<input type="hidden" id="datetmplmonth" name="datetmplmonth" value="' . dol_print_date($agf->dated, "%m") . '">' . "\n";
								print '<input type="hidden" id="datetmplyear"  name="datetmplyear"  value="' . dol_print_date($agf->dated, "%Y") . '">' . "\n";
								$form->select_date($agf->dated, 'date', '', '', '', 'newperiod');
								print '</td>';
								print '<td width="400px">' . $langs->trans("AgfPeriodTimeB") . ' ';
								print $formAgefodd->select_time('08:00', 'dated');
								print $langs->trans("AgfPeriodTimeE") . ' ';
								print $formAgefodd->select_time('18:00', 'datef');
								print '</td>';
								print '<td>' . $formAgefodd->select_calendrier_status($conf->global->AGF_DEFAULT_TRAINER_CALENDAR_STATUS, 'calendar_trainer_status') . '</td>';
								// Trainer cost is fully managed into cost management not here
								if (empty($conf->global->AGF_ADVANCE_COST_MANAGEMENT)) {
									// Coût horaire
									print '<td width="20%"><input type="text" size="10" name="trainer_cost" /></td>';
								}
								if ($user->rights->agefodd->modifier) {
									print '<td><input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" align="absmiddle" name="period_add" alt="' . $langs->trans("Save") . '"></td>' . "\n";
								}

								print '</tr>' . "\n";
								print '<tr><td colspan="5"><input class="button" type="submit" value="' . $langs->trans('AgfEraseWithSessionCalendar') . '" name="copysessioncalendar"></td></tr>' . "\n";
							} else {
								print '<tr><td colspan="5"><a href="' . $_SERVER['PHP_SELF'] . '?action=edit_calendrier&amp;id=' . $agf->id . '&amp;rowf=' . $formateurs->lines[$i]->formid . '">' . "\n";
								print img_picto($langs->trans("Add"), dol_buildpath('/agefodd/img/new.png', 1), '', true, 0) . '</a>' . "\n";
								print '</td></tr>';
							}
						}
						print '</table>' . "\n";
						print '</form>' . "\n";
						// print '</td>' . "\n";
					}
				}
				print '</td>';

				print "</tr>\n";
			}
		}
		print "</table>" . "\n";
		print '</div>' . "\n";
	}
}

/*
 * Action tabs
 *
 */

print '<div class="tabsAction">';

if ($action != 'create' && $action != 'edit' && (! empty($agf->id)) && $nbform >= 1) {
	if ($user->rights->agefodd->modifier) {
		print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&amp;id=' . $id . '">' . $langs->trans('Cancel') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';
	}

	if (! $user->rights->agefodd->session->trainer) {
		if ($user->rights->agefodd->modifier) {
			print '<a class="butAction" href="' . dol_buildpath('/agefodd/session/send_docs.php', 1) . '?id=' . $id . '&action=presend_trainer_doc&mode=init">' . $langs->trans('AgfSendDocuments') . '</a>';
		} else {
			print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfSendDocuments') . '</a>';
		}
	}
}
if ($action == 'edit' && $newform_var < 1) {
	if (! $user->rights->agefodd->session->trainer) {
		if ($user->rights->agefodd->modifier) {
			print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&amp;id=' . $id . '&newform=1">' . $langs->trans("AgfFormateurAdd") . '</a>';
		} else {
			print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';
		}
	}

	if (! $user->rights->agefodd->session->trainer) {
		if ($user->rights->agefodd->modifier) {
			print '<a class="butAction" href="' . dol_buildpath('/agefodd/session/send_docs.php', 1) . '?id=' . $id . '&action=presend_trainer_doc&mode=init">' . $langs->trans('AgfSendDocuments') . '</a>';
		} else {
			print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfSendDocuments') . '</a>';
		}
	}
}
if ($action == 'edit' && $newform_var >= 1) {
	print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&amp;id=' . $id . '">' . $langs->trans('Cancel') . '</a>';
}
print '</div>';

llxFooter();
$db->close();


function _isTrainerFreeBadge($hourDisplay, $line, $fk_trainer)
{
    global $langs;

    $errorsStatus = $warningsStatus = 'default';
    if($line->status != Agefoddsessionformateurcalendrier::STATUS_DRAFT){
        $warningsStatus = array();
    }

    $isTrainerFree = Agefoddsessionformateurcalendrier::isTrainerFree($fk_trainer, $line->heured, $line->heuref, $line->id, $errorsStatus, $warningsStatus);
    if(!$isTrainerFree->isFree)
    {
        if($isTrainerFree->errors > 0){
            $hourDisplay = '<span class="classfortooltip badge badge-danger" title="'.$langs->trans('TrainerNotFree').'" ><i class="fa fa-exclamation-circle"></i> '.$hourDisplay .'</span>';
        } elseif ($isTrainerFree->warnings > 0){
            $hourDisplay = '<span class="classfortooltip badge badge-warning" title="'.$langs->trans('TrainerCouldBeNotFree').'" ><i class="fa fa-exclamation-triangle"></i> '.$hourDisplay .'</span>';
        }
    }

    return $hourDisplay;
}
