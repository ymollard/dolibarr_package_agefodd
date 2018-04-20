<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2015 Florian Henry <florian.henry@open-concept.pro>
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
 * \file agefodd/training/trainer.php
 * \ingroup agefodd
 * \brief info of traineer
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agefodd_formation_catalogue.class.php');
require_once ('../class/agefodd_formateur.class.php');
require_once ('../class/html.formagefodd.class.php');
require_once ('../lib/agefodd.lib.php');

require_once (DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php');

// Security check
if (! $user->rights->agefodd->agefodd_formation_catalogue->lire)
	accessforbidden();

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');

$agf = new Formation($db);
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($agf->table_element);

/*
 * Actions delete
 */

if ($action == 'updatetrainer' && $user->rights->agefodd->agefodd_formation_catalogue->creer) {
	$agf->id = $id;
	$training_trainer = GETPOST('trainertraining', 'array');
	$result = $agf->setTrainingTrainer($training_trainer,$user);
	if ($result < 0) {
		$action = 'edittrainer';
		setEventMessage($agf->error, 'errors');
	} else {
		$action = '';
	}
}
/*
 * View
 */
$title = $langs->trans("AgfCatalogDetail");

llxHeader('', $title, '', '', '', '', array (
		'/agefodd/includes/multiselect/js/ui.multiselect.js'
), array (
		'/agefodd/includes/multiselect/css/ui.multiselect.css',
		'/agefodd/css/agefodd.css'
));

$form = new Form($db);
$formagefodd = new FormAgefodd($db);

$result = $agf->fetch($id);
if ($result < 0) {
	setEventMessage($agf->error, 'errors');
}

$result = $agf->fetchTrainer();
if ($result < 0) {
	setEventMessage($agf->error, 'errors');
}

$head = training_prepare_head($agf);

dol_fiche_head($head, 'trainingtrainer', $langs->trans("AgfCatalogDetail"), 0, 'label');

dol_agefodd_banner_tab($agf, 'id');
print '<div class="underbanner clearboth"></div>';

print '<form name="create_contact" action="' . $_SERVER ['PHP_SELF'] . '" method="POST">' . "\n";
print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">' . "\n";
print '<input type="hidden" name="action" value="updatetrainer">' . "\n";
print '<input type="hidden" name="id" value="'.$id.'">' . "\n";


print '<table class="border" width="100%">';

print '<tr><td>' . $langs->trans("AgfTrainingTrainer");
if ($action != 'edittrainer' && $user->rights->agefodd->agefodd_formation_catalogue->creer) {
	print '<a href="' . dol_buildpath('/agefodd/training/trainer.php', 1) . '?id=' . $agf->id . '&action=edittrainer" style="text-align:right">' . img_picto($langs->trans('Edit'), 'edit') . '</a>';
}
print '</td>';

print '<td>';
if ($action == 'edittrainer' && $user->rights->agefodd->agefodd_formation_catalogue->creer) {
	$option_trainer = array ();
	$selected_trainer = array ();

	// Build selected categorie
	if (is_array($agf->trainers) && count($agf->trainers) > 0) {
		foreach ( $agf->trainers as $trainer ) {
			$selected_trainer[] = $trainer->id;
		}
	}
	$trainer = new Agefodd_teacher($db);
	$result = $trainer->fetch_all('ASC', 's.firstname', 0, 0);
	if ($result < 0) {
		setEventMessage($trainer->error, 'errors');
	}
	foreach ( $trainer->lines as $line ) {
		$option_trainer[$line->id] = $line->name . ' ' . $line->firstname;
	}
	print $formagefodd->agfmultiselectarray('trainertraining', $option_trainer, $selected_trainer);
	print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
} else {
	if (is_array($agf->trainers) && count($agf->trainers) > 0) {
		print '<ul>';
		foreach ( $agf->trainers as $trainer ) {
			print '<li>';
			print $trainer->getNomUrl();
			print '</li>';
		}
		print '</ul>';
	}
}
print '</td>';
print '</tr>';

print '</table>';
print '</form>';

print '</div>';

llxFooter();
$db->close();