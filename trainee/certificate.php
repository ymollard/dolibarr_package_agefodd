<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2016 Florian Henry <florian.henry@open-concept.pro>
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
 * \file agefodd/trainee/certificate.php
 * \ingroup agefodd
 * \brief certificate of trainee
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agefodd_stagiaire.class.php');
require_once ('../class/agefodd_stagiaire_certif.class.php');
require_once ('../lib/agefodd.lib.php');
require_once ('../class/agsession.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$certif_save_x = GETPOST('certif_save_x', 'alpha');
$certif_edit_x = GETPOST('certif_edit_x', 'alpha');
$certifid = GETPOST('certifid', 'int');

if ($action == 'edit' && $user->rights->agefodd->creer) {

	$certif_sta_id = GETPOST('modstaid', 'int');
	$certif_session_sta_id = GETPOST('sessionstarowid', 'int');
	$certif_id = GETPOST('certifid', 'int');

	$certif_code = GETPOST('certif_code', 'alpha');
	$certif_label = GETPOST('certif_label', 'alpha');
	$certif_dt_start = dol_mktime(0, 0, 0, GETPOST('dt_startmonth', 'int'), GETPOST('dt_startday', 'int'), GETPOST('dt_startyear', 'int'));
	$certif_dt_end = dol_mktime(0, 0, 0, GETPOST('dt_endmonth', 'int'), GETPOST('dt_endday', 'int'), GETPOST('dt_endyear', 'int'));
	$certif_dt_warning = dol_mktime(0, 0, 0, GETPOST('dt_warningmonth', 'int'), GETPOST('dt_warningday', 'int'), GETPOST('dt_warningyear', 'int'));

	if (! empty($certif_save_x)) {
		$agf_certif = new Agefodd_stagiaire_certif($db);
		$result = $agf_certif->fetch($certifid);
		if ($result < 0) {
			setEventMessage($agf_certif->error, 'errors');
		} else {

			$agf_certif->certif_code = $certif_code;
			$agf_certif->certif_label = $certif_label;
			$agf_certif->certif_dt_start = $certif_dt_start;
			$agf_certif->certif_dt_end = $certif_dt_end;
			$agf_certif->certif_dt_warning = $certif_dt_warning;

			// Existing certification
			if (! empty($agf_certif->id)) {
				$result = $agf_certif->update($user);
				if ($result < 0) {
					setEventMessage($agf_certif->error, 'errors');
				} else {

					$certif_type_array = $agf_certif->get_certif_type();

					if (is_array($certif_type_array) && count($certif_type_array) > 0) {
						foreach ( $certif_type_array as $certif_type_id => $certif_type_label ) {
							$certif_state = GETPOST('certifstate_' . $certif_type_id, 'none');
							$result = $agf_certif->set_certif_state($user, $certif_id, $certif_type_id, $certif_state);
							if ($result < 0) {
								setEventMessage($agf_certif->error, 'errors');
							}
						}
					}

					Header("Location: " . $_SERVER ['PHP_SELF'] . "?id=" . $id);
					exit();
				}
			}
		}
	}
}

if ($action == 'confirm_delete_certif' && $confirm == "yes" && $user->rights->agefodd->creer) {
	$certifrowid = GETPOST('certifrowid', 'int');

	$agf_certif = new Agefodd_stagiaire_certif($db);
	$result = $agf_certif->fetch($certifrowid);
	if ($result < 0) {
		setEventMessage($agf_certif->error, 'errors');
	} else {
		if (! empty($agf_certif->id)) {
			$result = $agf_certif->delete($user);
			if ($result < 0) {
				setEventMessage($agf_certif->error, 'errors');
			} else {
				Header("Location: " . $_SERVER ['PHP_SELF'] . "?action=edit&id=" . $id);
				exit();
			}
		}
	}
}

/*
 * View
*/

llxHeader('', $langs->trans("AgfStagiaireDetail"));

// Affichage de la fiche "stagiaire"
if ($id) {
	$agf = new Agefodd_stagiaire($db);
	$result = $agf->fetch($id);

	if ($result) {
		$agf_certif = new Agefodd_stagiaire_certif($db);
		$result = $agf_certif->fetch_all_by_trainee($id);
		if ($result < 0) {
			setEventMessage($agf_certif->error, 'errors');
		}

		$form = new Form($db);

		/*
		 * Confirmation de la suppression
		*/
		if ($_POST ["certif_remove_x"]) {
			// Param url = id de la ligne stagiaire dans session - id session
			print $form->formconfirm($_SERVER ['PHP_SELF'] . "?certifrowid=" . GETPOST('certifid', 'none') . '&id=' . $id, $langs->trans("AgfDeleteCertif"), $langs->trans("AgfConfirmDeleteCertif"), "confirm_delete_certif", '', '', 1);
		}

		$head = trainee_prepare_head($agf);

		dol_fiche_head($head, 'certificate', $langs->trans("AgfStagiaireDetailCertificate"), 0, 'user');

		dol_agefodd_banner_tab($agf, 'id');
		print '<div class="underbanner clearboth"></div>';

		print_fiche_titre($langs->trans("AgfCertificate"));

		if (count($agf_certif->lines) > 0) {
			print '<table class="noborder"  width="100%">';
			print '<tr class="liste_titre">';
			print '<th class="liste_titre" width="10%">' . $langs->trans('AgfMenuSess') . '</th>';
			print '<th class="liste_titre" width="10%">' . $langs->trans('AgfIntitule') . '</th>';
			print '<th class="liste_titre" width="20%">' . $langs->trans('AgfDebutSession') . '</th>';
			print '<th class="liste_titre">' . $langs->trans('AgfCertifCode') . '</th>';
			print '<th class="liste_titre">' . $langs->trans('AgfCertifLabel') . '</th>';
			print '<th class="liste_titre">' . $langs->trans('AgfCertifDateSt') . '</th>';
			print '<th class="liste_titre">' . $langs->trans('AgfCertifDateEnd') . '</th>';
			print '<th class="liste_titre">' . $langs->trans('AgfCertifDateWarning') . '</th>';
			print '<th class="liste_titre">' . $langs->trans('AgfCertifType') . '</th>';
			print '<th width="5%"></th>';
			print '</tr>';

			$style = 'impair';
			foreach ( $agf_certif->lines as $line ) {
				if ($style == 'class="pair"') {
					$style = 'class="impair"';
				} else {
					$style = 'class="pair"';
				}

				if (GETPOST('certif_remove_x', 'none') != '' && GETPOST('certifid', 'none') == $line->id)
					$style = 'bgcolor="#d5baa8"';

				if ($certifid == $line->id && (! empty($certif_edit_x))) {
					print '<form name="obj_update_' . $line->id . '" action="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '"  method="POST">' . "\n";
					print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">' . "\n";
					print '<input type="hidden" name="certifid" value="' . $line->id . '">' . "\n";
					print '<input type="hidden" name="action" value="edit">' . "\n";
					print '<tr ' . $style . '>';
					$agf_session = new Agsession($db);
					$agf_session->fetch($line->fk_session_agefodd);
					print '<td><a href="' . dol_buildpath('/agefodd/session/subscribers_certif.php', 1) . '?id=' . $line->fk_session_agefodd . '">' . $line->fk_session_agefodd . '</a></td>';
					print '<td><a href="' . dol_buildpath('/agefodd/session/subscribers_certif.php', 1) . '?id=' . $line->fk_session_agefodd . '">' . $agf_session->formintitule . '</a></td>';
					print '<td>';
					print dol_print_date($agf_session->dated, 'daytext');

					print '</td>';
					print '<td><input type="hidden" name="certif_code" value="' . $line->certif_code . '">' . $line->certif_code . '</td>';
					print '<td><input type="text" size="10" name="certif_label"  value="' . $line->certif_label . '"></td>';
					print '<td>';
					print $form->select_date($line->certif_dt_start, 'dt_start', '', '', 1, 'obj_update_' . $line->id, 1, 1);
					print '</td>';
					print '<td>';
					print $form->select_date($line->certif_dt_end, 'dt_end', '', '', 1, 'obj_update_' . $line->id, 1, 1);
					print '</td>';
					print '<td>';
					print $form->select_date($line->certif_dt_warning, 'dt_warning', '', '', 1, 'obj_update_' . $line->id, 1, 1);
					print '</td>';
					print '<td>';
					if (is_array($line->lines_state) && count($line->lines_state) > 0) {
						print '<table calss="nobordernopadding">';
						foreach ( $line->lines_state as $linestate ) {
							print '<tr><td>';
							print $linestate->certif_type . ':' . $form->selectyesno('certifstate_' . $linestate->fk_certif_type, $linestate->certif_state, 1);
							print '</td></tr>' . "\n";
						}
						print '</table>';
					}
					print '</td>';
					print '<td>';
					if ($user->rights->agefodd->modifier) {
						print '<input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" align="absmiddle" name="certif_save" alt="' . $langs->trans("Save") . '" ">';
					}
					print '</td>';
					print '</tr>';
					print '</form>';
				} else {

					print '<form name="obj_see_' . $line->id . '" action="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '"  method="POST">' . "\n";
					print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">' . "\n";
					print '<input type="hidden" name="certifid" value="' . $line->id . '">' . "\n";
					print '<tr ' . $style . '>';
					$agf_session = new Agsession($db);
					$agf_session->fetch($line->fk_session_agefodd);
					print '<td><a href="' . dol_buildpath('/agefodd/session/subscribers_certif.php', 1) . '?id=' . $line->fk_session_agefodd . '">' . $line->fk_session_agefodd . '</a></td>';
					print '<td><a href="' . dol_buildpath('/agefodd/session/subscribers_certif.php', 1) . '?id=' . $line->fk_session_agefodd . '">' . $agf_session->formintitule . '</a></td>';
					print '<td>' . dol_print_date($agf_session->dated, 'daytext') . '</td>';
					print '<td>' . $line->certif_code . '</td>';
					print '<td>' . $line->certif_label . '</td>';
					print '<td>' . dol_print_date($line->certif_dt_start, 'daytext') . '</td>';
					print '<td>' . dol_print_date($line->certif_dt_end, 'daytext') . '</td>';
					print '<td>' . dol_print_date($line->certif_dt_warning, 'daytext') . '</td>';
					print '<td>';
					if (is_array($line->lines_state) && count($line->lines_state) > 0) {
						print '<table calss="nobordernopadding">';
						foreach ( $line->lines_state as $linestate ) {
							print '<tr><td>';
							print $linestate->certif_type . ':' . yn($linestate->certif_state, 0, 1);
							print '</td></tr>' . "\n";
						}
						print '</table>';
					}
					print '</td>';
					print '<td>';
					if ($user->rights->agefodd->modifier) {
						print '<input type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/edit.png" border="0" name="certif_edit" alt="' . $langs->trans("Modify") . '">';
					}
					print '&nbsp;';
					if ($user->rights->agefodd->creer) {
						print '<input type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/delete.png" border="0" name="certif_remove" alt="' . $langs->trans("Delete") . '">';
					}
					print '</td>';
					print '</tr>';
					print '</form>';
				}
			}
			print '</table>';
		} else {
			print '<div style="text-align:center">' .$langs->trans('AgfNoCertif').'</div>';
		}
	}
}

llxFooter();
$db->close();
