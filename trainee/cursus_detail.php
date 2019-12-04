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
 * \file agefodd/trainee/cursus_detail.php
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
require_once ('../class/agefodd_stagiaire_cursus.class.php');
require_once ('../class/agefodd_cursus.class.php');
require_once ('../core/modules/agefodd/modules_agefodd.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$id = GETPOST('id', 'int');
$cursus_id = GETPOST('cursus_id', 'int');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');



$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');

if ($page == - 1 || empty($page)) {
	$page = 0;
}
$limit = $conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (empty($sortorder))
	$sortorder = "DESC";
if (empty($sortfield))
	$sortfield = "s.dated";

	/*
 * Action generate certificate cursus
*/
if ($action == 'builddoc' && $user->rights->agefodd->creer) {
	// Define output language
	$outputlangs = $langs;
	$newlang = GETPOST('lang_id', 'alpha');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))
		$newlang = $object->thirdparty->default_lang;
	if (! empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	}
	$model = 'attestation_cursus';
	$file = $model . '_' . $cursus_id . '_' . $id . '.pdf';

	// this configuration variable is designed like
	// standard_model_name:new_model_name&standard_model_name:new_model_name&....
	if (! empty($conf->global->AGF_PDF_MODEL_OVERRIDE) && ($model != 'convention')) {
		$modelarray = explode('&', $conf->global->AGF_PDF_MODEL_OVERRIDE);
		if (is_array($modelarray) && count($modelarray) > 0) {
			foreach ( $modelarray as $modeloveride ) {
				$modeloverridearray = explode(':', $modeloveride);
				if (is_array($modeloverridearray) && count($modeloverridearray) > 0) {
					if ($modeloverridearray[0] == $model) {
						$model = $modeloverridearray[1];
					}
				}
			}
		}
	}

	$agf_cursus = new Agefodd_cursus($db);
	$result = $agf_cursus->fetch($cursus_id);
	if ($result < 0) {
		setEventMessage($agf->error, 'errors');
	} else {
		$agf_cursus->fk_stagiaire = $id;
		$result = agf_pdf_create($db, $agf_cursus, '', $model, $outputlangs, $file, 0);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		}
	}
}
elseif ($action == 'confirm_deldoc' && $confirm == "yes" && $user->rights->agefodd->creer) {
    $file = $conf->agefodd->dir_output . '/attestation_cursus_' . $cursus_id . '_' . $id . '.pdf';

    if (is_file($file))
        unlink($file);
    else {
        $error = $file . ' : ' . $langs->trans("AgfDocDelError");
        setEventMessage($error, 'errors');
    }
}

/*
 * View
*/

llxHeader('', $langs->trans("AgfCursusDetail"));

// Affichage de la fiche "stagiaire"
if (! empty($id) && ! empty($cursus_id)) {
	$agf = new Agefodd_stagiaire($db);
	$result = $agf->fetch($id);

	if ($result > 0) {
		$stagiaires = new Agefodd_session_stagiaire($db);

		$form = new Form($db);
		if($action == "deldoc"){
		    print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id."&cursus_id=".$cursus_id, $langs->trans('AgfRemoveAttestationCursus'), $langs->trans('AgfRemoveAttestationCursus'), "confirm_deldoc", '', '', 1);
		}
		$agf->cursus_id = $cursus_id;
		$head = trainee_prepare_head($agf, 1);

		dol_fiche_head($head, 'cursusdetail', $langs->trans("AgfStagiaireDetail"), 0, 'user');

		dol_agefodd_banner_tab($agf, 'id');
		print '<div class="underbanner clearboth"></div>';

		//delete pagination
		print '<script>$("div.pagination").first().hide();</script>';

		// Cursus Detail
		$agf_cursus = new Agefodd_cursus($db);
		$result = $agf_cursus->fetch($cursus_id);

		print_fiche_titre($langs->trans("AgfCursusDetail"));

		print '<table class="border" width="100%">';

		print '<tr><td width="20%">' . $langs->trans("Id") . '</td>';
		print '<td>' . $agf_cursus->id . '</td></tr>';

		print '<tr><td>' . $langs->trans("AgfRefInterne") . '</td>';
		print '<td>' . $agf_cursus->ref_interne . '</td></tr>';

		print '<tr><td width="20%">' . $langs->trans("AgfIntitule") . '</td>';
		print '<td>' . $agf_cursus->intitule . '</td></tr>';

		print '<tr><td valign="top">' . $langs->trans("NotePublic") . '</td>';
		print '<td>' . $agf_cursus->note_public . '</td></tr>';

		print '<tr><td valign="top">' . $langs->trans("NotePrivate") . '</td>';
		print '<td>' . $agf_cursus->note_private . '</td></tr>';

		print "</table>";

		$agf_cursus = new Agefodd_stagiaire_cursus($db);
		$agf_cursus->fk_stagiaire = $id;
		$agf_cursus->fk_cursus = $cursus_id;
		$result = $agf_cursus->fetch_session_cursus_per_trainee($sortorder, $sortfield, $limit, $offset);
		if ($result < 0) {
			setEventMessage($agf_cursus->error, 'errors');
		}

		if (is_array($agf_cursus->lines)) {
			$numCusrsusline=count($agf_cursus->lines);
		}

		// Session list
		print_barre_liste($langs->trans("AgfSessionDetail"), $page, $_SERVER ['PHP_SELF'], "&arch=" . $arch, $sortfield, $sortorder, "", $numCusrsusline);

		if ($numCusrsusline > 0) {
			print '<table class="noborder"  width="100%">';
			print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans("AgfMenuSess"), $_SERVER ['PHP_SELF'], "s.rowid", '', '&id=' . $id . '&cursus_id=' . $cursus_id, '', $sortfield, $sortorder);
			print_liste_field_titre($langs->trans("AgfIntitule"), $_SERVER ['PHP_SELF'], "c.intitule", '', '&id=' . $id . '&cursus_id=' . $cursus_id, '', $sortfield, $sortorder);
			print_liste_field_titre($langs->trans("AgfDebutSession"), $_SERVER ['PHP_SELF'], "s.dated", '', '&id=' . $id . '&cursus_id=' . $cursus_id, '', $sortfield, $sortorder);
			print_liste_field_titre($langs->trans("AgfFinSession"), $_SERVER ['PHP_SELF'], "s.datef", '', '&id=' . $id . '&cursus_id=' . $cursus_id, '', $sortfield, $sortorder);
			print_liste_field_titre($langs->trans("AgfPDFFichePeda1"), $_SERVER ['PHP_SELF'], '', '', '&id=' . $id . '&cursus_id=' . $cursus_id, '', $sortfield, $sortorder);
			if (! empty($conf->global->AGF_USE_REAL_HOURS)) {
				print_liste_field_titre($langs->trans("AgfEffectiveDuration"), $_SERVER ['PHP_SELF'], '', '', '&id=' . $id . '&cursus_id=' . $cursus_id, '', $sortfield, $sortorder);
			}
			print_liste_field_titre($langs->trans("Status"), $_SERVER ['PHP_SELF'], "ss.status_in_session", '', '&id=' . $id . '&cursus_id=' . $cursus_id, '', $sortfield, $sortorder);
			print "</tr>\n";
			print '</tr>';

			$style = 'pair';

			$dureetotal = 0;
			foreach ( $agf_cursus->lines as $line ) {
				if ($style == 'pair') {
					$style = 'impair';
				} else {
					$style = 'pair';
				}

				print '<tr class="' . $style . '">';

				print '<td><a href="' . dol_buildpath('/agefodd/session/subscribers.php', 1) . '?id=' . $line->rowid . '">' . $line->rowid . '</a></td>';
				print '<td><a href="' . dol_buildpath('/agefodd/session/subscribers.php', 1) . '?id=' . $line->rowid . '">' . $line->intitule . '</a></td>';
				print '<td>' . dol_print_date($line->dated, 'daytext') . '</td>';
				print '<td>' . dol_print_date($line->datef, 'daytext') . '</td>';

				// Calculate time of session according calendar
				$calendrier = new Agefodd_sesscalendar($db);
				$calendrier->fetch_all($line->rowid);
				$duree=0;
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
					$timeinsession = $obj_time_effective->heures_stagiaire($line->rowid,$agf_cursus->fk_stagiaire);
					print '<td>' . $timeinsession . '</td>';
					$totaltimesession+=$timeinsession;
				}
				print '<td>' . $stagiaires->LibStatut($line->status_in_session, 4) . '</td>';
				print '</tr>';
			}

			print '<tr class="liste_total">';
			print '<td>' . $langs->trans('Total') . '</td>';
			print '<td></td>';
			print '<td></td>';
			print '<td></td>';

			$min = floor($dureetotal / 60);
			$rmin = sprintf("%02d", $min % 60);
			$hour = floor($min / 60);
			print '<td>' . $hour . ':' . $rmin . '</td>';
			if (! empty($conf->global->AGF_USE_REAL_HOURS)){
				print '<td>' . $totaltimesession . '</td>';
			}
			print '<td></td>';
			print '</tr>';
			print '</table>';
		} else {
			print $langs->trans('AgfNoSession');
		}
	} else {
		setEventMessage($agf->error, 'errors');
	}

	// Dispplay Session to plan
	$agf_cursus = new Agefodd_stagiaire_cursus($db);
	$agf_cursus->fk_stagiaire = $id;
	$agf_cursus->fk_cursus = $cursus_id;

	$result = $agf_cursus->fetch_training_session_to_plan();

	if ($result < 0) {
		setEventMessage($agf_cursus->error, 'errors');
	}

	if (is_array($agf_cursus->lines) && count($agf_cursus->lines) > 0) {
		print_fiche_titre($langs->trans("AgfSessionInCursusToPlan"));

		$style = 'pair';
		print '<table class="noborder"  width="100%">';
		foreach ( $agf_cursus->lines as $line ) {
			if ($style == 'pair') {
				$style = 'impair';
			} else {
				$style = 'pair';
			}

			print '<tr class="' . $style . '"><td>' . $line->ref . ' ' . $line->ref_interne . ' - ' . $line->intitule . '</td>';
			print '<td align="left">';
			if (empty($line->archive)) {
				print '<a href="../session/card.php?action=create&formation=' . $line->id . '"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/filenew.png" border="0"></a>';
			}
			print '</td></tr>';
		}
		print '</table>';
	}

	// PDF Cursus
	if (is_file($conf->agefodd->dir_output . '/attestation_cursus_' . $cursus_id . '_' . $id . '.pdf')) {
		print '&nbsp';
		print '<table class="border" width="100%">';
		print '<tr class="liste_titre"><td colspan=3>' . $langs->trans("AgfLinkedDocuments") . '</td></tr>';
		// afficher
		$legende = $langs->trans("AgfDocOpen");
		print '<tr><td width="200" align="center">' . $langs->trans("AgfAttestationCursus") . '</td><td> ';
		print '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=agefodd&file=attestation_cursus_' . $cursus_id . '_' . $id . '.pdf" alt="' . $legende . '" title="' . $legende . '">';
		print '<img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/pdf2.png" border="0" align="absmiddle" hspace="2px" ></a>';
		print '</td></tr></table>';

	}
}

/*
 * Action tabs
*
*/

print '<div class="tabsAction">';

if ($user->rights->agefodd->creer) {
	print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?action=builddoc&cursus_id=' . $cursus_id . '&id=' . $id . '">' . $langs->trans('AgfPrintAttestationCursus') . '</a>';
	if (is_file($conf->agefodd->dir_output . '/attestation_cursus_' . $cursus_id . '_' . $id . '.pdf')) print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?action=deldoc&cursus_id=' . $cursus_id . '&id=' . $id . '">' . $langs->trans('AgfRemoveAttestationCursus') . '</a>';
} else {
	print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfPrintAttestationCursus') . '</a>';
	if (is_file($conf->agefodd->dir_output . '/attestation_cursus_' . $cursus_id . '_' . $id . '.pdf')) print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfRemoveAttestationCursus') . '</a>';
}

print '</div>';

llxFooter();
$db->close();
