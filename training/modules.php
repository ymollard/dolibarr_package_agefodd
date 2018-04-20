<?php
/* Agefodd
 * Copyright (C) 20145 HENRY Florian  florian.henry@open-concept.pro
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Load environment
// Load environment
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agefodd_formation_catalogue.class.php');
require_once ('../core/modules/agefodd/modules_agefodd.php');
require_once ('../class/html.formagefodd.class.php');
require_once ('../lib/agefodd.lib.php');
require_once ('../class/agefodd_formation_catalogue_modules.class.php');

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$confirm = GETPOST('confirm', 'alpha');

$moduletitle = GETPOST('moduletitle', 'alpha');

// Security check
if (! $user->rights->agefodd->agefodd_formation_catalogue->lire)
	accessforbidden();

	// Load translation files required by the page
$langs->load("agefodd@agefodd");

$object = new Formation($db);
$object_modules = new Agefoddformationcataloguemodules($db);

if (! empty($id)) {
	$result = $object->fetch($id);
	if ($result < 0) {
		setEventMessage($object->error, 'errors');
	}
	$result = $object_modules->fetchAll('ASC', 'sort_order', 0, 0, array (
			't.fk_formation_catalogue' => $id
	));
	if ($result < 0) {
		setEventMessage($object->error, 'errors');
	}
}

$extrafields = new ExtraFields($db);

$error = 0;

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

/*
 * Actions
*/

if ($action == "add") {
	$object->title = $moduletitle;

	//$extrafields->setOptionalsFromPost($extralabels, $object);

	$result = $object->create($user);
	if ($result < 0) {
		$action = 'create';
		setEventMessage($object->error, 'errors');
	} else {
		header('Location:' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
	}
} elseif ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->agefodd->agefodd_formation_catalogue->supprimer) {
	$result = $object->delete($user);
	if ($result < 0) {
		setEventMessage($object->errors, 'errors');
	} else {
		header('Location:' . $_SERVER["PHP_SELF"] . '?id=' . $id);
	}
} elseif ($action == 'setmoduletitle') {
	$object->title = $moduletitle;
	$result = $object->update($user);
	if ($result < 0) {
		$action = 'editmoduletitle';
		setEventMessage($object->error, 'errors');
	} else {
		header('Location:' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
	}
}

/*
 * VIEW
*/
$title = $langs->trans('AgfTrainingModule');

llxHeader('', $title);

$form = new Form($db);

$now = dol_now();

/*
	 * Show object in view mode
	*/
$head = training_prepare_head($object);

dol_fiche_head($head, 'trainingmodule', $langs->trans("AgfTrainingModule"), 0, 'label');

// Confirm form
if ($action == 'delete') {
	print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('RefLtrDelete'), $langs->trans('RefLtrConfirmDelete'), 'confirm_delete', '', 0, 1);
}

dol_agefodd_banner_tab($object, 'id');
print '<div class="underbanner clearboth"></div><br>';

print_fiche_titre($langs->trans("AgfTrainingModule"));
if (is_array($object_modules->lines) && count($object_modules->lines) > 0) {
	foreach ( $object_modules->lines as $line_chapter ) {
		print '<table class="border" width="100%">';

		if ($user->rights->agefodd->agefodd_formation_catalogue->creer) {
			print '<tr><td rowspan="5" width="20px">';
			print '<a href="' . dol_buildpath('/agefodd/training/modules_chapters.php', 1) . '?id=' . $line_chapter->id . '&fk_formation_catalogue='.$object->id.'&action=edit">' . img_picto($langs->trans('Edit'), 'edit') . '</a>';
			print '<a href="' . dol_buildpath('/agefodd/training/modules_chapters.php', 1) . '?id=' . $line_chapter->id . '&fk_formation_catalogue='.$object->id.'&action=delete">' . img_picto($langs->trans('Delete'), 'delete') . '</a>';
			print '</td></tr>';
		}

		print '<tr>';
		print '<td  width="20%">';
		print $langs->trans('Title');
		print '</td>';
		print '<td>';
		print $line_chapter->title;
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td  width="20%">';
		print $langs->trans('AgfPDFFichePeda1');
		print '</td>';
		print '<td>';
		print price($line_chapter->duration);
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td  width="20%">';
		print $langs->trans('AgfObjPeda');
		print '</td>';
		print '<td>';
		print $line_chapter->obj_peda;
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td  width="20%">';
		print $langs->trans('AgfContenu');
		print '</td>';
		print '<td>';
		print $line_chapter->content_text;
		print '</td>';
		print '</tr>';

		print '</table>';
	}
}

print '<div class="tabsAction">';
print '<div class="inline-block divButAction"><a class="butAction" href="' . dol_buildpath('/agefodd/training/modules_chapters.php', 1) . '?action=create&fk_formation_catalogue=' . $object->id . '">' . $langs->trans("Create") . ' ' . $langs->trans("AgfTrainingModule") . "</a></div>\n";
print '</div>';

print "</div>\n";

// Page end
llxFooter();
$db->close();