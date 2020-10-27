<?php
/* Agefodd
 * Copyright (C) 2015  HENRY Florian  florian.henry@open-concept.pro
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
/**
 * \file		agefodd/training/module.php
 * \ingroup	refferenceletters
 * \brief		chapter pages
 */

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
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$fk_formation_catalogue = GETPOST('fk_formation_catalogue', 'int');
$confirm = GETPOST('confirm', 'alpha');
$moduletitle = GETPOST('moduletitle', 'alpha');

// Security check
if (! $user->rights->agefodd->agefodd_formation_catalogue->lire)
	accessforbidden();

	// Load translation files required by the page
$langs->load("agefodd@agefodd");

$object = new Agefoddformationcataloguemodules($db);
$object_training = new Formation($db);

$error = 0;

/*
 * Actions
*/

if ($action == "add") {

	$object->entity=$conf->entity;
	$object->fk_formation_catalogue = $fk_formation_catalogue;
	$object->title = GETPOST('moduletitle', 'none');
	$object->content_text = GETPOST('content_text', 'none');
	$object->duration = GETPOST('duration', 'none');
	$object->obj_peda = GETPOST('obj_peda', 'none');
	$object->sort_order = GETPOST('sort_order', 'none');
	$object->status=1;

	$result = $object->create($user);
	if ($result < 0) {
		$action = 'create';
		setEventMessage($object->errors[0], 'errors');
	} else {
		header('Location:' . dol_buildpath('/agefodd/training/modules.php', 1) . '?id=' . $fk_formation_catalogue);
	}
} elseif ($action == "update") {
	$result = $object->fetch($id);
	if ($result < 0) {
		$action = 'edit';
		setEventMessage($object->error, 'errors');
	}

	$object->title = GETPOST('moduletitle', 'none');
	$object->content_text = GETPOST('content_text', 'none');
	$object->duration = GETPOST('duration', 'none');
	$object->obj_peda = GETPOST('obj_peda', 'none');
	$object->sort_order = GETPOST('sort_order', 'none');

	$result = $object->update($user);
	if ($result < 0) {
		$action = 'edit';
		setEventMessage($object->error, 'errors');
	} else {
		header('Location:' . dol_buildpath('/agefodd/training/modules.php', 1) . '?id=' . $fk_formation_catalogue);
	}
} elseif ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->agefodd->agefodd_formation_catalogue->supprimer) {
	$result = $object->fetch($id);
	if ($result < 0) {
		$action = 'delete';
		setEventMessage($object->error, 'errors');
	}
	$result = $object->delete($user);
	if ($result < 0) {
		setEventMessage($object->errors, 'errors');
	} else {
		header('Location:' . dol_buildpath('/agefodd/training/modules.php', 1) . '?id=' . $fk_formation_catalogue);
	}
}

/*
 * VIEW
*/

$title = $langs->trans('AgfTrainingModule');
if ($action == 'create') {

	if (! empty($fk_formation_catalogue)) {
		$result = $object_training->fetch($fk_formation_catalogue);
		if ($result < 0) {
			setEventMessage($object->error, 'errors');
		}
		$object->fk_formation_catalogue = $fk_formation_catalogue;
	} else {
		setEventMessage('Page call with wrong argument', 'errors');
	}

	$subtitle = $langs->trans("AgfTrainingModule") . ' - ' . $object_training->title;
	$button_text = 'Create';
	$action_next = 'add';
} elseif ($action == 'edit' || $action == 'delete') {

	if (! empty($id)) {
		$result = $object->fetch($id);
		if ($result < 0) {
			setEventMessage($object->error, 'errors');
		}
		$result = $object_training->fetch($object->fk_formation_catalogue);
		if ($result < 0) {
			setEventMessage($object->error, 'errors');
		}
	}

	$button_text = 'Modify';
	$action_next = 'update';
}

llxHeader('', $title);

$form = new Form($db);
$formagefodd = new FormAgefodd($db);
$formadmin = new FormAdmin($db);

$now = dol_now();

if (($action == 'create' || $action == 'edit' || $action == 'delete') && $user->rights->agefodd->agefodd_formation_catalogue->creer) {

	// Confirm form
	if ($action == 'delete') {
		print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id.'&fk_formation_catalogue='.$fk_formation_catalogue, $langs->trans('AgfDeleteTrainingModule'), $langs->trans('AgfConfirmDeleteModule'), 'confirm_delete', '', 0, 1);
	}

	print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="fk_formation_catalogue" value="' . $fk_formation_catalogue . '">';
	print '<input type="hidden" name="action" value="' . $action_next . '">';
	print '<input type="hidden" name="id" value="' . $id . '">';

	print '<table class="border" width="100%">';
	print '<tr>';

	print '<td class="fieldrequired"  width="20%">';
	print $langs->trans('AgfPosition');
	print '</td>';
	print '<td>';
	if (empty($object->sort_order)) {
		$result = $object->findMaxSortOrder();
		if ($result < 0) {
			setEventMessage($object->error, 'errors');
		} else {
			$object->sort_order = $result;
		}
	}
	print '<input type="text" name="sort_order" size="2" value="' . $object->sort_order . '"/>';
	print '</td>';
	print '</tr>';

	print '<td class="fieldrequired"  width="20%">';
	print $langs->trans('Title');
	print '</td>';
	print '<td>';
	print '<input type="text" name="moduletitle" size="50" value="' . $object->title . '"/>';
	print '</td>';
	print '</tr>';

	print '<td width="20%">';
	print $langs->trans('AgfPDFFichePeda1');
	print '</td>';
	print '<td>';
	print '<input type="text" name="duration" size="2" value="' . $object->duration . '"/>';
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td width="20%">';
	print $langs->trans('AgfObjPeda');
	print '</td>';
	print '<td>';
	require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
	$nbrows = ROWS_2;
	if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT))
		$nbrows = $conf->global->MAIN_INPUT_DESC_HEIGHT;
	$enable = (isset($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING) ? $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING : 0);
	$doleditor = new DolEditor('obj_peda', $object->obj_peda, '', 150, 'dolibarr_notes_encoded', '', false, true, $enable, $nbrows, 70);
	$doleditor->Create();
	print '</td>';
	print '</tr>';


	print '<tr>';
	print '<td class="fieldrequired"  width="20%">';
	print $langs->trans('AgfContenu');
	print '</td>';
	print '<td>';
	require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
	$nbrows = ROWS_2;
	if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT))
		$nbrows = $conf->global->MAIN_INPUT_DESC_HEIGHT;
	$enable = (isset($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING) ? $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING : 0);
	$doleditor = new DolEditor('content_text', $object->content_text, '', 150, 'dolibarr_notes_encoded', '', false, true, $enable, $nbrows, 70);
	$doleditor->Create();
	print '</td>';
	print '</tr>';

	print '</table>';

	print '<center>';
	print '<input type="submit" class="button" value="' . $langs->trans($button_text) . '">';
	print '&nbsp;<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</center>';

	print '</form>';
}

// Page end
llxFooter();
$db->close();
