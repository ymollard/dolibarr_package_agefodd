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
 * \file agefodd/trainee/session.php
 * \ingroup agefodd
 * \brief session of trainee
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agefodd_stagiaire.class.php');
require_once ('../lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php');
require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_session_stagiaire.class.php');
require_once ('../class/agefodd_session_calendrier.class.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$id = GETPOST('id', 'int');

$hookmanager->initHooks(array('sessiontaineelist'));

/*
 * View
*/

llxHeader('', $langs->trans("AgfStagiaireDetail"));

// Affichage de la fiche "stagiaire"
if ($id) {
	$agf = new Agefodd_stagiaire($db);
	$result = $agf->fetch($id);

	if ($result > 0) {
		$stagiaires = new Agefodd_session_stagiaire($db);

		$agf_session = new Agsession($db);
		$result = $agf_session->fetch_session_per_trainee($id);
		if ($result < 0) {
			setEventMessage($agf_session->error, 'errors');
		}

		$form = new Form($db);

		$head = trainee_prepare_head($agf);

		dol_fiche_head($head, 'sessionlist', $langs->trans("AgfStagiaireDetail"), 0, 'user');
		dol_agefodd_banner_tab($agf, 'id');

		dol_fiche_end();

		print_barre_liste($langs->trans("AgfSessionDetail"), 0, $_SERVER ['PHP_SELF'], '', '', '', '', $result, $result, 'title_generic.png', 0, '', '', 0, 1);

		if (count($agf_session->lines) > 0) {
			print '<table class="tagtable liste listwithfilterbefore"  width="100%">';
			print '<tr class="liste_titre">';
			print '<td class="liste_titre" width="10%">' . $langs->trans('AgfMenuSess') . '</td>';
			print '<td class="liste_titre" width="10%">' . $langs->trans('Ref') . '</td>';
			print '<td class="liste_titre" width="10%">' . $langs->trans('AgfIntitule') . '</td>';
			print '<td class="liste_titre" width="20%">' . $langs->trans('AgfDebutSession') . '</td>';
			print '<td class="liste_titre" width="20%">' . $langs->trans('AgfFinSession') . '</td>';
			print '<td class="liste_titre" width="20%">' . $langs->trans('AgfPDFFichePeda1') . '</td>';
			if (! empty($conf->global->AGF_USE_REAL_HOURS)) {
				print '<td class="liste_titre" width="20%">' . $langs->trans('AgfEffectiveDuration') . '</td>';
			}
			print '<td class="liste_titre" width="20%">' . $langs->trans('Status') . '</td>';

			print '</tr>';

			$style = 'pair';

			$dureetotal = 0;
			foreach ( $agf_session->lines as $line ) {
				$duree = 0;

				if ($style == 'pair') {
					$style = 'class="impair"';
				} else {
					$style = 'class="pair"';
				}

				if ($line->status == 4) {
					$style = ' style="background: gray"';
				}

				print '<tr ' . $style . '>';

				print '<td><a href="' . dol_buildpath('/agefodd/session/card.php', 1) . '?id=' . $line->rowid . '">' . $line->rowid . '</a></td>';
				print '<td><a href="' . dol_buildpath('/agefodd/session/card.php', 1) . '?id=' . $line->rowid . '">' . $line->sessionref . '</a></td>';
				print '<td><a href="' . dol_buildpath('/agefodd/session/card.php', 1) . '?id=' . $line->rowid . '">' . $line->intitule . '</a></td>';
				print '<td>' . dol_print_date($line->dated, 'daytext') . '</td>';
				print '<td>' . dol_print_date($line->datef, 'daytext') . '</td>';

				// Calculate time of session according calendar
				$calendrier = new Agefodd_sesscalendar($db);
				$calendrier->fetch_all($line->rowid);
				if (is_array($calendrier->lines) && count($calendrier->lines) > 0) {
					foreach ( $calendrier->lines as $linecal ) {
						$duree += ($linecal->heuref - $linecal->heured);
					}
				}
				$dureetotal += $duree;
				$min = floor($duree / 60);
				$rmin = sprintf("%02d", $min % 60);
				$hour = floor($min / 60);

				// print '<td>'.dol_print_date($line->realdurationsession,'hour').'</td>';
				print '<td>' . $hour . ':' . $rmin . '</td>';
				if (! empty($conf->global->AGF_USE_REAL_HOURS)){
					dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');
					$obj_time_effective=new Agefoddsessionstagiaireheures($db);
					$timeinsession = $obj_time_effective->heures_stagiaire($line->rowid,$agf->id);
					print '<td>' . $timeinsession . '</td>';
				}
				print '<td>' . $stagiaires->LibStatut($line->status_in_session, 4) . '</td>';
				print '</tr>';
			}

			print '<tr class="liste_total">';
			print '<td>' . $langs->trans('Total') . '</td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';

			$min = floor($dureetotal / 60);
			$rmin = sprintf("%02d", $min % 60);
			$hour = floor($min / 60);
			print '<td>' . $hour . ':' . $rmin . '</td>';
			if (! empty($conf->global->AGF_USE_REAL_HOURS)){
				print '<td>' . $obj_time_effective->heures_stagiaire_totales($agf->id) . '</td>';
			}
			print '<td></td>';
			print '<td></td>';
			print '</tr>';
			print '</table>';
		} else {
		    print '<div style="text-align:center">' . $langs->trans('AgfNoSessionFound') . '</div>';
		}
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

llxFooter();
$db->close();
