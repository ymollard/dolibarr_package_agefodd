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
 * \file		/agefodd/index.php
 * \brief		Tableau de bord du module de formation pro.
 * (Agefodd).
 * \Version	$Id$
 */
$res = @include ("../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

dol_include_once('/agefodd/class/agefodd_index.class.php');
dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/core/lib/date.lib.php');
dol_include_once('/agefodd/core/boxes/box_agefodd_stats.php');
dol_include_once('/agefodd/core/boxes/box_agefodd_board.php');
dol_include_once('/agefodd/core/boxes/box_agefodd_lastsession.php');
dol_include_once('/agefodd/core/boxes/box_agefodd_preferedtraining.php');

// Security check
if (! $user->rights->agefodd->lire) {
	accessforbidden();
}


$langs->load('agefodd@agefodd');

llxHeader('', $langs->trans('AgefoddShort'),$conf->global->AGF_HELP_LINK);

print_barre_liste($langs->trans("AgfBilanGlobal"),0,'','', '', '', '', 0);

print '<table class="noborder">';
print '<tr>';
print '<td>';
$box = new box_agefodd_stats($db);
$box->loadBox();
$box->showBox();
print '</td>';
print '<td>';
$box = new box_agefodd_board($db);
$box->loadBox();
$box->showBox();
print '</td>';
print '</tr>';
print '<tr>';
print '<td>';
$box = new box_agefodd_lastsession($db);

$box->loadBox();
$box->showBox();
print '</td>';
print '<td>';
$box = new box_agefodd_preferedtraining($db);

$box->loadBox();
$box->showBox();
print '</td>';
print '</tr>';

print '</table>';
if (! empty($conf->global->AGF_MANAGE_CERTIF)) {
	$agf = new Agefodd_index($db);

	$time_expiration = GETPOST('certif_time', 'int');
	if (empty($time_expiration)) {
		$time_expiration = 6;
	}

	$filter_month_array = array (
			1,
			2,
			3,
			6,
			12
	);

	print '<form name="search_certif" action="' . $_SERVER ['PHP_SELF'] . '" method="POST">' . "\n";
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
	print '<div style="overflow:auto; height: 200px; overflow-x: hidden;">';
	print '<table class="noborder">';
	print '<tr class="liste_titre"><th>' . $langs->trans("AgfIndexCertif");
	print '<select name="certif_time">';
	foreach ( $filter_month_array as $i ) {

		if ($time_expiration == $i) {
			$selected = 'selected="selected"';
		} else {
			$selected = '';
		}
		print '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
	}
	print '</select>' . $langs->trans('Month');
	print '<input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '</th></tr>';

	// List de stagaire concerné

	$result = $agf->fetch_certif_expire($time_expiration);
	if ($result && (count($agf->lines) > 0)) {

		$style = 'impair';
		foreach ( $agf->lines as $line ) {
			if ($style == 'pair') {
				$style = 'impair';
			} else {
				$style = 'pair';
			}

			print '<tr class="' . $style . '"><td>';
			if (DOL_VERSION < 6.0) {
				print '<a href="' . dol_buildpath('/societe/soc.php', 1) . '?socid=' . $line->customer_id . '">' . $line->customer_name . '</a>';
			} else {
				print '<a href="' . dol_buildpath('/societe/card.php', 1) . '?socid=' . $line->customer_id . '">' . $line->customer_name . '</a>';
			}
			print '&nbsp;-&nbsp;<a href="' . dol_buildpath('/agefodd/certificate/list.php', 1) . '?socid=' . $line->customer_id . '&search_training_ref=' . $line->fromref . '">' . $line->fromintitule . '</a>';
			print '</td></tr>';
		}
	} else {
		print '<tr class="pair"><td>' . $langs->trans('AgfNoCertif') . '</td></tr>';
	}

	print '</table>';
	print '</div>';
	print '</form>';
}


llxFooter();
$db->close();