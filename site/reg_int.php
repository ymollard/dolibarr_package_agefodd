<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
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
 * \file agefodd/site/reg_int.php
 * \ingroup agefodd
 * \brief Internal rule pages for locations
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agefodd_place.class.php');
require_once ('../class/agefodd_reginterieur.class.php');
require_once ('../lib/agefodd.lib.php');

require_once (DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php');

// Security check
if (! $user->rights->agefodd->agefodd_place->lire)
	accessforbidden();

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$idreg = GETPOST('idreg', 'int');

/*
 * Actions delete
*/
if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->agefodd_place->creer) {
	$agf = new Agefodd_reg_interieur($db);
	$agf->id = $idreg;
	$result = $agf->delete($user);

	if ($result > 0) {
		$agf_place = new Agefodd_place($db);
		$agf_place->id = $id;
		$result = $agf_place->remove_reg_int($user);

		if ($result > 0) {
			Header("Location: " . $_SERVER ['PHP_SELF'] . "?action=edit&id=" . $id);
			exit();
		} else {
			setEventMessage($agf_place->error, 'errors');
		}
	} else {
		setEventMessage($langs->trans("AgfDeleteErr") . ':' . $agf_place->error, 'errors');
	}
}

/*
 * Action update (Location internal rules)
*/
if ($action == 'update' && $user->rights->agefodd->agefodd_place->creer) {
	if (! $_POST ["cancel"]) {
		$agf = new Agefodd_reg_interieur($db);

		$result = $agf->fetch($idreg);
		if ($result > 0) {
			if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
				$agf->reg_int = dol_htmlcleanlastbr(GETPOST('reg_int', 'none'));
			} else {
				$agf->reg_int = GETPOST('reg_int', 'none');
			}

			$agf->notes = GETPOST('notes', 'none');
			$result = $agf->update($user);

			if ($result > 0) {
				Header("Location: " . $_SERVER ['PHP_SELF'] . "?id=" . $id);
				exit();
			} else {
				setEventMessage($agf->error, 'errors');
			}
		} else {
			setEventMessage($agf->error, 'errors');
		}
	} else {
		Header("Location: " . $_SERVER ['PHP_SELF'] . "?id=" . $id);
		exit();
	}
}

/*
 * Action create (Location internal rules)
*/

if ($action == 'create_confirm' && $user->rights->agefodd->agefodd_place->creer) {
	if (! $_POST ["cancel"]) {
		$agf = new Agefodd_reg_interieur($db);

		if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
			$agf->reg_int = dol_htmlcleanlastbr(GETPOST('reg_int', 'none'));
		} else {
			$agf->reg_int = GETPOST('reg_int', 'none');
		}
		$agf->notes = GETPOST('notes', 'none');
		$result = $agf->create($user);

		if ($result > 0) {

			$agf_place = new Agefodd_place($db);
			$result_place = $agf_place->fetch($id);
			$agf_place->fk_reg_interieur = $result;
			$result = $agf_place->update($user);

			if ($result > 0) {
				Header("Location: " . $_SERVER ['PHP_SELF'] . "?id=" . $id);
				exit();
			} else {
				setEventMessage($agf_place->error, 'errors');
			}
		} else {
			setEventMessage($agf->error, 'errors');
		}
	} else {
		Header("Location: list.php");
		exit();
	}
}

/*
 * View
*/

llxHeader('', $langs->trans("AgfRegInt"));

$form = new Form($db);

$agf_place = new Agefodd_place($db);
$result_place = $agf_place->fetch($id);
if ($result_place < 0) {
	setEventMessage($agf_place->error, 'errors');
}

if ($agf_place->fk_reg_interieur) {
	$agf = new Agefodd_reg_interieur($db);
	$result_regint = $agf->fetch($agf_place->fk_reg_interieur);
	if ($result_regint < 0) {
		setEventMessage($agf->error, 'errors');
	}
} else {
	$action = 'create';
}

$head = site_prepare_head($agf_place);

dol_fiche_head($head, 'reg_int_tab', $langs->trans("AgfSessPlace"), 0, 'address');

dol_agefodd_banner_tab($agf_place, 'id');
print '<div class="underbanner clearboth"></div>';

/*
 * Action create
*/

if ($action == 'create' && $user->rights->agefodd->agefodd_place->creer) {
	print '<form name="create" action="' . $_SERVER ['PHP_SELF'] . '" method="POST">' . "\n";
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">' . "\n";
	print '<input type="hidden" name="action" value="create_confirm">' . "\n";
	print '<input type="hidden" name="id" value="' . $id . '">' . "\n";

	print '<br>';
	print '<table class="border" width="100%">'. "\n";

	print '<tr><td valign="top">' . $langs->trans("AgfRegInt") . '</td><td>'. "\n";
	$doleditor = new DolEditor('reg_int', GETPOST('reg_int', 'none'), '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
	$doleditor->Create();
	print "</td></tr>". "\n";

	print '<tr><td valign="top">' . $langs->trans("AgfNote") . '</td>'. "\n";
	print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;"></textarea></td></tr>'. "\n";

	print '</table>'. "\n";
	print '</div>'. "\n";

	print '<table style=noborder align="right">'. "\n";
	print '<tr><td align="center" colspan="2">'. "\n";
	print '<input type="submit" name="importadress" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
	print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">'. "\n";
	print '</td></tr>'. "\n";
	print '</table>'. "\n";
	print '</form>'. "\n";
} else {
	// Card location
	if ($result_place > 0 && $result_regint > 0) {

		// Card location interal rules Edit mode
		if ($action == 'edit') {
			print '<form name="update" action="' . $_SERVER ['PHP_SELF'] . '" method="post">' . "\n";
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">' . "\n";
			print '<input type="hidden" name="action" value="update">' . "\n";
			print '<input type="hidden" name="id" value="' . $id . '">' . "\n";
			print '<input type="hidden" name="idreg" value="' . $agf_place->fk_reg_interieur . '">' . "\n";

			print '<table class="border" width="100%">';

			print '<tr><td valign="top">' . $langs->trans("AgfRegInt") . '</td><td>';
			$doleditor = new DolEditor('reg_int', $agf->reg_int, '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
			$doleditor->Create();
			print "</td></tr>";


			print '<tr><td valign="top">' . $langs->trans("AgfNote") . '</td>';
			print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;">' . $agf->notes . '</textarea></td></tr>';

			print '</table>';
			print '</div>';
			print '<table style=noborder align="right">';
			print '<tr><td align="center" colspan=2>';
			print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
			print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
			print '</td></tr>';
			print '</table>';

			print '</form>';

			print '</div>' . "\n";
		} else {
			// Card location interal rules View mode

			/*
			 * Delete confirm
			*/
			if ($action == 'delete') {
				print $form->formconfirm($_SERVER ['PHP_SELF'] . "?id=" . $id . '&idreg=' . $agf_place->fk_reg_interieur, $langs->trans("AgfDeleteRegint"), $langs->trans("AgfConfirmRegInt"), "confirm_delete", '', '', 1);
			}

			print '<table class="border" width="100%">';

			print '<tr><td valign="top">' . $langs->trans("AgfRegInt") . '</td>';
			print '<td>' . $agf->reg_int . '</td></tr>';

			print '<tr><td valign="top">' . $langs->trans("AgfNote") . '</td>';
			print '<td>' . $agf->notes . '</td></tr>';

			print '</table>';

			print '</div>';
		}
	}
}

/*
 * Actions tabs
*
*/


print '<div class="tabsAction">';

if ($action != 'create' && $action != 'edit' && $action != 'nfcontact') {
	if ($user->rights->agefodd->agefodd_place->creer) {
		print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?action=edit&id=' . $id . '">' . $langs->trans('Modify') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';
	}
	if ($user->rights->agefodd->agefodd_place->creer) {
		print '<a class="butActionDelete" href="' . $_SERVER ['PHP_SELF'] . '?action=delete&id=' . $id . '">' . $langs->trans('Delete') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Delete') . '</a>';
	}
}

print '</div>';

llxFooter();
$db->close();
