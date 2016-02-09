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

require_once ('../class/agsession.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../class/agefodd_session_formateur.class.php');
require_once ('../class/agefodd_session_formateur_calendrier.class.php');
require_once ('../class/html.formagefodd.class.php');
require_once ('../lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php');
require_once ('../class/agefodd_session_calendrier.class.php');

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
$newform_var = GETPOST('newform');
$opsid_var = GETPOST('opsid');
$form_remove_var = GETPOST('form_remove');
$period_remove = GETPOST('period_remove');
$newperiod = GETPOST('newperiod');

$delete_calsel = GETPOST('deletecalsel_x', 'alpha');
if (! empty($delete_calsel)) {
	$action = 'delete_calsel';
}

/*
 * Actions delete formateur
 */

if ($action == 'confirm_delete_form' && $confirm == "yes" && $user->rights->agefodd->creer) {
	$obsid = GETPOST('opsid', 'int');
	
	$agf = new Agefodd_session_formateur($db);
	$result = $agf->remove($obsid);
	
	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

if ($action == 'edit' && $user->rights->agefodd->creer) {
	
	if ($form_update_x > 0) {
		$agf = new Agefodd_session_formateur($db);
		
		$agf->opsid = GETPOST('opsid', 'int');
		$agf->formid = GETPOST('formid', 'int');
		$agf->trainer_status = GETPOST('trainerstatus', 'int');
		$agf->trainer_type = GETPOST('trainertype', 'int');
		$result = $agf->update($user);
		
		if ($result > 0) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	}
	
	if ($form_add_x > 0) {
		$agf = new Agefodd_session_formateur($db);
		
		$agf->sessid = GETPOST('sessid', 'int');
		$agf->formid = GETPOST('formid', 'int');
		$agf->trainer_status = GETPOST('trainerstatus', 'int');
		$agf->trainer_type = GETPOST('trainertype', 'int');
		$result = $agf->create($user);
		
		if ($result > 0) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	}
}

if ($action == 'edit_calendrier' && $user->rights->agefodd->creer) {
	
	if (! empty($period_add)) {
		$error = 0;
		$error_message = '';
		
		$agf_cal = new Agefoddsessionformateurcalendrier($db);
		
		$agf_cal->sessid = GETPOST('sessid', 'int');
		$agf_cal->fk_agefodd_session_formateur = GETPOST('fk_agefodd_session_formateur', 'int');
		$agf_cal->trainer_cost = price2num(GETPOST('trainer_cost', 'alpha'), 'MU');
		$agf_cal->date_session = dol_mktime(0, 0, 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));
		
		// From calendar selection
		$heure_tmp_arr = array ();
		
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
		$result = $agf_cal->fetch_all_by_trainer(GETPOST('trainerid', 'int'));
		if ($result < 0) {
			$error ++;
			$error_message = $agf_cal->error;
		}
		
		foreach ( $agf_cal->lines as $line ) {
			if (($agf_cal->heured <= $line->heured && $agf_cal->heuref >= $line->heuref) || ($agf_cal->heured >= $line->heured && $agf_cal->heuref <= $line->heuref) || ($agf_cal->heured <= $line->heured && $agf_cal->heuref <= $line->heuref && $agf_cal->heuref > $line->heured) || ($agf_cal->heured >= $line->heured && $agf_cal->heuref >= $line->heuref && $agf_cal->heured < $line->heuref)) {
				$error ++;
				$error_message .= $langs->trans('AgfTrainerlAreadybookAtThisTime') . '(<a href=' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?id=' . $line->fk_session . ' target="_blanck">' . $line->fk_session . '</a>)<br>';
			}
		}
		if (! $error) {
			
			$result = $agf_cal->create($user);
			if ($result < 0) {
				$error ++;
				$error_message = $agf_cal->error;
			}
		}
		
		if (! $error) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit_calendrier&id=" . $id);
			exit();
		} else {
			setEventMessage($error_message, 'errors');
		}
	}
	
	if (! empty($period_update)) {
		
		$modperiod = GETPOST('modperiod', 'int');
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
		
		$trainer_cost = price2num(GETPOST('trainer_cost', 'alpha'), 'MU');
		$fk_agefodd_session_formateur = GETPOST('fk_agefodd_session_formateur', 'int');
		
		$agf_cal = new Agefoddsessionformateurcalendrier($db);
		$result = $agf_cal->fetch($modperiod);
		
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
			
			// Test if trainer is already book for another training
		$result = $agf_cal->fetch_all_by_trainer(GETPOST('trainerid', 'int'));
		if ($result < 0) {
			$error ++;
			$error_message = $agf_cal->error;
		} else {
			foreach ( $agf_cal->lines as $line ) {
				if (! empty($line->trainer_status) && $line->trainer_status != 6) {
					if ((($agf_cal->heured <= $line->heured && $agf_cal->heuref >= $line->heuref) || ($agf_cal->heured >= $line->heured && $agf_cal->heuref <= $line->heuref) || ($agf_cal->heured <= $line->heured && $agf_cal->heuref <= $line->heuref && $agf_cal->heuref > $line->heured) || ($agf_cal->heured >= $line->heured && $agf_cal->heuref >= $line->heuref && $agf_cal->heured < $line->heuref)) && $line->fk_session != $id) {
						$error ++;
						$error_message .= $langs->trans('AgfTrainerlAreadybookAtThisTime') . '(<a href=' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?id=' . $line->fk_session . ' target="_blanck">' . $line->fk_session . '</a>)<br>';
					}
				}
			}
		}
		
		if (! $error) {
			$result = $agf_cal->update($user);
			if ($result < 0) {
				$error ++;
				$error_message = $agf_cal->error;
			}
		}
		
		if (! $error) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit_calendrier&id=" . $id);
			exit();
		} else {
			setEventMessage($error_message, 'errors');
		}
	}
	
	$copysessioncalendar = GETPOST('copysessioncalendar');
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
		if (is_array($agf_session_cal->lines) && count($agf_session_cal) > 0) {
			foreach ( $agf_session_cal->lines as $line ) {
				
				$agf_cal = new Agefoddsessionformateurcalendrier($db);
				
				$agf_cal->sessid = $id;
				$agf_cal->fk_agefodd_session_formateur = $fk_agefodd_session_formateur;
				
				$agf_cal->date_session = $line->date_session;
				
				$agf_cal->heured = $line->heured;
				$agf_cal->heuref = $line->heuref;
				
				// Test if trainer is already book for another training
				$result = $agf_cal->fetch_all_by_trainer(GETPOST('trainerid', 'int'));
				if ($result < 0) {
					$error ++;
					$error_message = $agf_cal->error;
				}
				
				foreach ( $agf_cal->lines as $line ) {
					if (! empty($line->trainer_status) && $line->trainer_status != 6) {
						if (($agf_cal->heured <= $line->heured && $agf_cal->heuref >= $line->heuref) || ($agf_cal->heured >= $line->heured && $agf_cal->heuref <= $line->heuref) || ($agf_cal->heured <= $line->heured && $agf_cal->heuref <= $line->heuref && $agf_cal->heuref > $line->heured) || ($agf_cal->heured >= $line->heured && $agf_cal->heuref >= $line->heuref && $agf_cal->heured < $line->heuref)) {
							$error ++;
							$error_message .= $langs->trans('AgfTrainerlAreadybookAtThisTime') . '(<a href=' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?id=' . $line->fk_session . ' target="_blanck">' . $line->fk_session . '</a>)<br>';
						}
					}
				}
				if (! $error) {
					
					$result = $agf_cal->create($user);
					if ($result < 0) {
						$error ++;
						$error_message = $agf_cal->error;
					}
				}
			}
		}
		
		if (! $error) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit_calendrier&id=" . $id);
			exit();
		} else {
			setEventMessage($error_message, 'errors');
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

if ($action == 'confirm_delete_period' && $confirm == "yes" && $user->rights->agefodd->creer) {
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
	
	print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
	print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
	print '</div>';
	
	// Print session card
	$agf->printSessionInfo();
	
	print '&nbsp;</div>';
	print_barre_liste($langs->trans("AgfFormateur"), "", "", "", "", "", '', 0);
	
	/*
	 * Confirm delete calendar
	 */
	
	if (! empty($period_remove)) {
		// Param url = id de la periode à supprimer - id session
		$ret = $form->form_confirm($_SERVER['PHP_SELF'] . '?modperiod=' . GETPOST('modperiod') . '&id=' . $id, $langs->trans("AgfDeletePeriod"), $langs->trans("AgfConfirmDeletePeriod"), "confirm_delete_period", '', '', 1);
		if ($ret == 'html')
			print '<br>';
	}
	
	$rowf_var = GETPOST('rowf');
	$trainerid_var = GETPOST('trainerid');
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
			print '				 $(\'html, body\').animate({scrollTop: $("#anchoropsid' . GETPOST('opsid') . '").offset().top}, 500,\'easeInOutCubic\');';
		}
		print '			});
					});
					</script> ';
		
		/*
		 * Confirm Delete
		 */
		if (! empty($form_remove_var)) {
			// Param url = id de la ligne formateur dans session - id session
			$ret = $form->form_confirm($_SERVER['PHP_SELF'] . "?opsid=" . GETPOST('opsid') . '&id=' . $id, $langs->trans("AgfDeleteForm"), $langs->trans("AgfConfirmDeleteForm"), "confirm_delete_form", '', '', 1);
			if ($ret == 'html')
				print '<br>';
		}
		
		print '<div class="tabBar">';
		print '<form name="form_update" action="' . $_SERVER['PHP_SELF'] . '?action=edit&amp;id=' . $id . '"  method="POST">' . "\n";
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
		print '<input type="hidden" name="action" value="edit">' . "\n";
		print '<input type="hidden" name="sessid" value="' . $id . '">' . "\n";
		print '<table class="border" width="100%">';
		
		// Display edit and update trainer
		$formateurs = new Agefodd_session_formateur($db);
		$nbform = $formateurs->fetch_formateur_per_session($agf->id);
		if ($nbform > 0) {
			for($i = 0; $i < $nbform; $i ++) {
				if ($formateurs->lines[$i]->opsid == GETPOST('opsid') && ! empty($form_remove_var))
					print '<tr bgcolor="#d5baa8">';
				else
					print '<tr>';
				
				print '<td width="20px" align="center">' . ($i + 1);
				print '<a id="anchoropsid' . $formateurs->lines[$i]->opsid . '" name="anchoropsid' . $formateurs->lines[$i]->opsid . '" href="#anchoropsid' . $formateurs->lines[$i]->opsid . '"></a>';
				print '</td>';
				
				// Edit line
				
				if ($formateurs->lines[$i]->opsid == GETPOST('opsid') && empty($form_remove_var)) {
					print '<td width="600px" style="border-right: 0px">';
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
					print '&nbsp;';
					print $formAgefodd->select_trainer_session_status('trainerstatus', $formateurs->lines[$i]->trainer_status);
					
					if ($user->rights->agefodd->modifier) {
						print '</td><td><input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" align="absmiddle" name="form_update" alt="' . $langs->trans("Save") . '">';
					}
					print '</td>';
				} else {
					print '<td width="400px" style="border-right: 0px;">';
					// trainer info
					if (strtolower($formateurs->lines[$i]->lastname) == "undefined") {
						print $langs->trans("AgfUndefinedTrainer");
					} else {
						print '<table width="100%" class="nobordernopadding">';
						print '<tr><td width="50%">';
						
						print '<a href="' . dol_buildpath('/agefodd/trainer/card.php', 1) . '?id=' . $formateurs->lines[$i]->formid . '">';
						print img_object($langs->trans("ShowContact"), "contact") . ' ';
						print strtoupper($formateurs->lines[$i]->lastname) . ' ' . ucfirst($formateurs->lines[$i]->firstname) . '</a>';
						if (! empty($conf->global->AGF_USE_FORMATEUR_TYPE)) {
							print '<BR>';
							print $formateurs->lines[$i]->trainer_type_label;
						}
						print '<BR>';
						print $formateurs->lines[$i]->getLibStatut(2);
						print '</td>';
						
						$totaltimetrainer = '';
						$hourhtml = '';
						if ($conf->global->AGF_DOL_TRAINER_AGENDA) {
							
							$hourhtml .= '<td>';
							$hourhtml .= '<table class="nobordernopadding">';
							$hourhtml .= '<tr><td width="50%">';
							// Calculate time past in session
							$trainer_calendar = new Agefoddsessionformateurcalendrier($db);
							$result = $trainer_calendar->fetch_all($formateurs->lines[$i]->opsid);
							if ($result < 0) {
								setEventMessage($trainer_calendar->error, 'errors');
							}
							$totaltime = 0;
							foreach ( $trainer_calendar->lines as $line_trainer_calendar ) {
								$totaltime += $line_trainer_calendar->heuref - $line_trainer_calendar->heured;
								$hourhtml .= '<tr><td>';
								$hourhtml .= dol_print_date($line_trainer_calendar->heured, 'dayhourtext');
								$hourhtml .= '</td></tr>';
								if ($line_trainer_calendar->heured != $line_trainer_calendar->heuref) {
									$hourhtml .= '<tr><td>';
									$hourhtml .= dol_print_date($line_trainer_calendar->heuref, 'dayhourtext');
									$hourhtml .= '</td></tr>';
								}
							}
							
							$hourhtml .= '<tr></table>';
							
							$totaltimetrainer = '<td>(' . dol_print_date($totaltime, 'hourduration', 'tz') . ')</td>';
							
							$hourhtml .= '</td>';
							
							print $totaltimetrainer;
							print $hourhtml;
						}
						
						print '<tr></table>';
					}
					print '</td>';
					print '<td>';
					
					if ($user->rights->agefodd->modifier) {
						print '<a href="' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?action=edit&amp;sessid=' . $formateurs->lines[$i]->sessid . '&amp;opsid=' . $formateurs->lines[$i]->opsid . '&amp;id=' . $id . '&amp;form_edit=1">' . img_picto($langs->trans("Edit"), 'edit') . '</a>';
					}
					print '&nbsp;';
					if ($user->rights->agefodd->modifier) {
						print '<a href="' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?action=edit&amp;sessid=' . $formateurs->lines[$i]->sessid . '&amp;opsid=' . $formateurs->lines[$i]->opsid . '&amp;id=' . $id . '&amp;form_remove=1">' . img_picto($langs->trans("Delete"), 'delete') . '</a>';
					}
					if ($user->rights->agefodd->modifier && ! empty($conf->global->AGF_DOL_TRAINER_AGENDA)) {
						print '&nbsp;';
						print '<a href="' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?action=edit_calendrier&amp;id=' . $id . '&amp;rowf=' . $formateurs->lines[$i]->formid . '">' . img_picto($langs->trans('Time'), 'calendar') . '</a>';
					}
					print '</td>' . "\n";
				}
				
				print '</tr>' . "\n";
			}
		}
		
		// New trainers
		if (! empty($newform_var) && ! empty($user->rights->agefodd->modifier)) {
			print '<tr>';
			
			print '<td width="20px" align="center"><a id="anchornewform" name="anchornewform"/>' . ($i + 1) . '</td>';
			print '<td nowrap="nowrap">';
			
			$filterSQL = 's.rowid NOT IN (SELECT fk_agefodd_formateur FROM ' . MAIN_DB_PREFIX . 'agefodd_session_formateur WHERE fk_session=' . $id . ')';
			if ($conf->global->AGF_FILTER_TRAINER_TRAINING) {
				$filterSQL .= ' AND s.rowid IN (SELECT fk_trainer FROM ' . MAIN_DB_PREFIX . 'agefodd_formateur_training WHERE fk_training=' . $agf->formid . ')';
			}
			print $formAgefodd->select_formateur($formateurs->lines[$i]->formid, "formid", $filterSQL, 1);
			if (! empty($conf->global->AGF_USE_FORMATEUR_TYPE)) {
				print '&nbsp;';
				print $formAgefodd->select_type_formateur($conf->global->AGF_DEFAULT_FORMATEUR_TYPE, "trainertype", ' active=1 ');
			}
			print '&nbsp;';
			print $formAgefodd->select_trainer_session_status('trainerstatus', $formateurs->lines[$i]->trainer_status);
			if ($user->rights->agefodd->modifier) {
				print '</td><td><input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" align="absmiddle" name="form_add" alt="' . $langs->trans("Save") . '">';
			}
			print '</td>';
			
			print '</tr>' . "\n";
		}
		
		print '</table>';
		print '</form>' . "\n";
		if (empty($newform_var) && ! empty($user->rights->agefodd->modifier)) {
			print '</div>';
			print '<table style="border:0;" width="100%">';
			print '<tr><td align="right">';
			print '<form name="newform" action="' . $_SERVER['PHP_SELF'] . '?action=edit&amp;id=' . $id . '"  method="POST">' . "\n";
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
			print '<input type="hidden" name="action" value="edit">' . "\n";
			print '<input type="hidden" name="newform" value="1">' . "\n";
			print '<input type="submit" class="butAction" value="' . $langs->trans("AgfFormateurAdd") . '"></form>';
			if ($user->rights->agefodd->creer) {
				print '<a class="butAction" href="' . dol_buildpath('/agefodd/session/send_docs.php', 1) . '?id=' . $id . '&action=presend_trainer_doc&mode=init">' . $langs->trans('AgfSendDocuments') . '</a>';
			} else {
				print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfSendDocuments') . '</a>';
			}
			print '</td></tr>';
			print '</table>';
		}
		print '</div>';
	} else {
		// Display view mode
		print '&nbsp;';
		
		$formateurs = new Agefodd_session_formateur($db);
		$nbform = $formateurs->fetch_formateur_per_session($agf->id);
		print $langs->trans("AgfFormateur");
		if ($nbform > 0)
			print ' (' . $nbform . ')';
		
		if ($nbform < 1) {
			print '<td style="text-decoration: blink;"><BR><BR>' . $langs->trans("AgfNobody") . '</td></tr>';
			print '<table style="border:0;" width="100%">';
			print '<tr><td align="right">';
			print '<form name="newform" action="' . $_SERVER['PHP_SELF'] . '?action=edit&amp;id=' . $id . '"  method="POST">' . "\n";
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
			print '<input type="hidden" name="action" value="edit">' . "\n";
			print '<input type="hidden" name="newform" value="1">' . "\n";
			print '<input type="submit" class="butAction" value="' . $langs->trans("AgfFormateurAdd") . '">';
			print '</form></td></tr>';
			print '</table>';
		} else {
			print '<table class="border" width="100%">';
			
			for($i = 0; $i < $nbform; $i ++) {
				print '<tr><td width="20%" valign="top">';
				// Trainers info
				print '<a id="anchoropsid' . $formateurs->lines[$i]->opsid . '" name="anchoropsid' . $formateurs->lines[$i]->opsid . '" href="#anchoropsid' . $formateurs->lines[$i]->opsid . '"></a>';
				print '<a id="anchorrowf' . $formateurs->lines[$i]->formid . '" name="anchorrowf' . $formateurs->lines[$i]->formid . '" href="#anchorrowf' . $formateurs->lines[$i]->formid . '"></a>';
				print '<a href="' . dol_buildpath('/agefodd/trainer/card.php', 1) . '?id=' . $formateurs->lines[$i]->formid . '">';
				print img_object($langs->trans("ShowContact"), "contact") . ' ';
				print strtoupper($formateurs->lines[$i]->lastname) . ' ' . ucfirst($formateurs->lines[$i]->firstname) . '</a>';
				print '&nbsp;';
				print $formateurs->lines[$i]->getLibStatut(2);
				
				if (! empty($conf->global->AGF_DOL_TRAINER_AGENDA)) {
					print '&nbsp;';
					// Calculate time past in session
					$trainer_calendar = new Agefoddsessionformateurcalendrier($db);
					$result = $trainer_calendar->fetch_all($formateurs->lines[$i]->opsid);
					if ($result < 0) {
						setEventMessage($trainer_calendar->error, 'errors');
					}
					$totaltime = 0;
					foreach ( $trainer_calendar->lines as $line_trainer_calendar ) {
						$totaltime += $line_trainer_calendar->heuref - $line_trainer_calendar->heured;
					}
					
					print '(' . dol_print_date($totaltime, 'hourduration', 'tz') . ')';
				}
				print '</td>';
				
				if (! empty($conf->global->AGF_DOL_TRAINER_AGENDA)) {
					/* Time management */
					$calendrier = new Agefoddsessionformateurcalendrier($db);
					$calendrier->fetch_all($formateurs->lines[$i]->opsid);
					$blocNumber = count($calendrier->lines);
					
					if ($blocNumber < 1 && ! (empty($newperiod))) {
						
						print '<span style="color:red;">' . $langs->trans("AgfNoCalendar") . '</span>';
					} else {
						print '<td>';
						
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
						// Trainer cost is fully managed into cost management not here
						if (empty($conf->global->AGF_ADVANCE_COST_MANAGEMENT)) {
							print '<th class="liste_titre">' . $langs->trans('AgfTrainerCostHour') . '</th>';
						}
						print '<th class="liste_titre">' . $langs->trans('Edit') . '</th>';
						
						print '</tr>';
						
						$old_date = 0;
						$duree = 0;
						for($j = 0; $j < $blocNumber; $j ++) {
							if ($calendrier->lines[$j]->id == GETPOST('modperiod') && ! empty($period_remove))
								print '<tr bgcolor="#d5baa8">' . "\n";
							else
								print '<tr>' . "\n";
							
							if ($calendrier->lines[$j]->id == GETPOST('modperiod') && empty($period_remove)) {
								// Delete select case not display here
								print '<td></td>' . "\n";
								
								print '<td  width="20%">' . $langs->trans("AgfPeriodDate") . ' ' . "\n";
								$form->select_date($calendrier->lines[$j]->date_session, 'date', '', '', '', 'obj_update_' . $j);
								
								print '<input type="hidden" name="action" value="edit_calendrier">' . "\n";
								print '<input type="hidden" name="sessid" value="' . $id . '">' . "\n";
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
								print '<td  width="40%">' . dol_print_date($calendrier->lines[$j]->heured, 'hour') . ' - ' . dol_print_date($calendrier->lines[$j]->heuref, 'hour');
								print '</td>';
								
								// Trainer cost is fully managed into cost management not here
								if (empty($conf->global->AGF_ADVANCE_COST_MANAGEMENT)) {
									// Coût horaire
									print '<td>' . price($calendrier->lines[$j]->trainer_cost, 0, $langs) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>' . "\n";
								}
								
								print '<td width="30%;">';
								if ($user->rights->agefodd->modifier) {
									print '<a href="' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?action=edit_calendrier&amp;sessid=' . $id . '&amp;modperiod=' . $calendrier->lines[$j]->id . '&amp;trainerid=' . $formateurs->lines[$i]->formid . '&amp;id=' . $id . '&amp;period_edit=1">' . img_picto($langs->trans("Edit"), 'edit') . '</a>' . "\n";
								}
								print '&nbsp;';
								if ($user->rights->agefodd->creer) {
									print '<a href="' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?action=edit_calendrier&amp;sessid=' . $id . '&amp;modperiod=' . $calendrier->lines[$j]->id . '&amp;trainerid=' . $formateurs->lines[$i]->formid . '&amp;id=' . $id . '&amp;period_remove=1">' . img_picto($langs->trans("Delete"), 'delete') . '</a>' . "\n";
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
							if ($action == "edit_calendrier" && GETPOST('rowf') == $formateurs->lines[$i]->formid) {
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
								// print '</td>';
								// print '<td width="400px" >';
								print $langs->trans("AgfPeriodTimeE") . ' ';
								print $formAgefodd->select_time('18:00', 'datef');
								print '</td>';
								// Trainer cost is fully managed into cost management not here
								if (empty($conf->global->AGF_ADVANCE_COST_MANAGEMENT)) {
									// Coût horaire
									print '<td width="20%"><input type="text" size="10" name="trainer_cost" /></td>';
								}
								if ($user->rights->agefodd->modifier) {
									print '<td><input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" align="absmiddle" name="period_add" alt="' . $langs->trans("Save") . '"></td>' . "\n";
								}
								
								print '</tr>' . "\n";
								
								print '<tr><td colspan="4"><a href="' . $_SERVER['PHP_SELF'] . '?id=' . $agf->id . '">' . $langs->trans('Cancel') . '</a></td></tr>';
								print '<tr><td colspan="4"><input class="button" type="submit" value="' . $langs->trans('AgfEraseWithSessionCalendar') . '" name="copysessioncalendar"></td></tr>' . "\n";
							} else {
								print '<tr><td colspan="4"><a href="' . $_SERVER['PHP_SELF'] . '?action=edit_calendrier&amp;id=' . $agf->id . '&amp;rowf=' . $formateurs->lines[$i]->formid . '">' . "\n";
								print img_picto($langs->trans("Add"), dol_buildpath('/agefodd/img/new.png', 1), '', true, 0) . '</a>' . "\n";
								print '</td></tr>';
							}
						}
						print '</table>' . "\n";
						print '</form>' . "\n";
						print '</td>' . "\n";
					}
				}
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
	if ($user->rights->agefodd->creer) {
		print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&amp;id=' . $id . '">' . $langs->trans('Modify') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';
	}
	
	if ($user->rights->agefodd->creer) {
		print '<a class="butAction" href="' . dol_buildpath('/agefodd/session/send_docs.php', 1) . '?id=' . $id . '&action=presend_trainer_doc&mode=init">' . $langs->trans('AgfSendDocuments') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfSendDocuments') . '</a>';
	}
}

print '</div>';

llxFooter();
$db->close();