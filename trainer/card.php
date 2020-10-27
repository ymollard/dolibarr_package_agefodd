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
 * \file agefodd/traineer/card.php
 * \ingroup agefodd
 * \brief card of traineer
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

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$arch = GETPOST('arch', 'int');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$hookmanager->initHooks(array(
	'agefoddsessiontrainer'
));

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $agf, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


/*
 * Actions delete
 */
if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->creer) {
	$agf = new Agefodd_teacher($db);
	$result = $agf->remove($id);

	if ($result > 0) {
		Header("Location: list.php");
		exit();
	} else {
		setEventMessage($langs->trans("AgfDeleteFormErr") . ':' . $agf->error, 'errors');
	}
}

/*
 * Actions archive/active
 */
if ($action == 'arch_confirm_delete' && $user->rights->agefodd->creer && $confirm == "yes") {
	$agf = new Agefodd_teacher($db);

	$result = $agf->fetch($id);

	$agf->archive = $arch;
	$result = $agf->update($user);

	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}


/*
 * Action create from contact (card trainer : CARREFULL, Dolibarr contact must exists)
 */

if ($action == 'create_confirm_contact' && $user->rights->agefodd->creer) {
	if (! $_POST["cancel"]) {
		$agf = new Agefodd_teacher($db);

		$agf->spid = GETPOST('spid', 'none');
		$agf->type_trainer = $agf->type_trainer_def[1];
		$result = $agf->create($user);

		if ($result > 0) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $result);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	} else {
		Header("Location: list.php");
		exit();
	}
}

/*
 * Action create from users (card trainer : CARREFULL, Dolibarr users must exists)
 */

if ($action == 'create_confirm_user' && $user->rights->agefodd->creer) {
	if (! $_POST["cancel"]) {
		$agf = new Agefodd_teacher($db);

		$agf->fk_user = GETPOST('fk_user', 'int');
		$agf->type_trainer = $agf->type_trainer_def[0];
		$result = $agf->create($user);

		if ($result > 0) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $result);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	} else {
		Header("Location: list.php");
		exit();
	}
}

if ($action == 'updatecat' && $user->rights->agefodd->creer) {
	$trainet_cat = GETPOST('trainercat', 'array');
	$agf = new Agefodd_teacher($db);
	$agf->id = $id;
	$result = $agf->setTrainerCat($trainet_cat,$user);
	if ($result < 0) {
		$action = 'editcategory';
		setEventMessage($agf->error, 'errors');
	} else {
		$action = '';
	}
}

if ($action == 'updatetraining' && $user->rights->agefodd->creer) {
	$trainer_training = GETPOST('trainertraining', 'array');
	$agf = new Agefodd_teacher($db);
	$agf->id = $id;
	$result = $agf->setTrainerTraining($trainer_training,$user);
	if ($result < 0) {
		$action = 'edittraining';
		setEventMessage($agf->error, 'errors');
	} else {
		$action = '';
	}
}

if (!empty($id) && $action == 'send')
{
	$object = new Agefodd_teacher($db);
	$result = $object->fetch($id);

	if($result>0){
		// Actions to send emails
		$actiontypecode = 'AC_OTH_AUTO';
		$trigger_name = 'AGFTRAINER_SENTBYMAIL';
		$autocopy = 'MAIN_MAIL_AUTOCOPY_AGFTRAINER_TO';
		$trackid = 'agftrainer' . $object->id;
		include __DIR__.'/../actions_sendmails.inc.php';
	}

}

/*
 * View
 */
$title = ($action == 'create' ? $langs->trans("AgfFormateurAdd") : $langs->trans("AgfTeacher"));

$extrajs = array (
		'/agefodd/includes/multiselect/js/ui.multiselect.js'
);
$extracss = array (
		'/agefodd/includes/multiselect/css/ui.multiselect.css',
		'/agefodd/css/agefodd.css'
);

llxHeader('', $title, '', '', '', '', $extrajs, $extracss);

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

/*
 * Action create
 */
if ($action == 'create' && $user->rights->agefodd->creer) {
	print_fiche_titre($langs->trans("AgfFormateurAdd"));

	print '<form name="create_contact" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
	print '<input type="hidden" name="action" value="create_confirm_contact">' . "\n";

	print '<div class="warning">' . $langs->trans("AgfFormateurAddContactHelp");
	print '<br>' . $langs->trans("AgfFormateurAddContactHelp1") . ' <a href="' . DOL_URL_ROOT . '/contact/card.php?action=create">' . $langs->trans("AgfFormateurAddContactHelp2") . '</a>. ' . $langs->trans("AgfFormateurAddContactHelp3") . '</div>';

	print '<table class="border" width="100%">' . "\n";

	print '<tr><td>' . $langs->trans("AgfContact") . '</td>';
	print '<td>';

	$agf_static = new Agefodd_teacher($db);
	$agf_static->fetch_all('ASC', 's.lastname, s.firstname', '', 0);
	$exclude_array = array ();
	if (is_array($agf_static->lines) && count($agf_static->lines) > 0) {
		foreach ( $agf_static->lines as $line ) {
			if (! empty($line->fk_socpeople)) {
				$exclude_array[] = $line->fk_socpeople;
			}
		}
	}
	$form->select_contacts(0, '', 'spid', 1, $exclude_array, '', 1, '', 1);
	print '</td></tr>';

	print '</table>';

	print '<table class="noborder" style="text-align: right;">';
	print '<tr><td style="text-align: right;" colspan=2>';
	print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
	print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
	print '</td></tr>';
	print '</table>';
	print '</form>';

	print '<br>';
	print '<br>';
	print '<br>';

	print '<form name="create_user" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
	print '<input type="hidden" name="action" value="create_confirm_user">' . "\n";

	print '<div class="warning">' . $langs->trans("AgfFormateurAddUserHelp");
	print '<br>' . $langs->trans("AgfFormateurAddUserHelp1") . ' <a href="' . DOL_URL_ROOT . '/user/card.php?action=create">' . $langs->trans("AgfFormateurAddUserHelp2") . '</a>. ' . $langs->trans("AgfFormateurAddUserHelp3") . '</div>';

	print '<table class="border" width="100%">' . "\n";

	print '<tr><td>' . $langs->trans("AgfUser") . '</td>';
	print '<td>';

	$agf_static = new Agefodd_teacher($db);
	$agf_static->fetch_all('ASC', 's.lastname, s.firstname', '', 0);
	$exclude_array = array ();
	if (is_array($agf_static->lines) && count($agf_static->lines) > 0) {
		foreach ( $agf_static->lines as $line ) {
			if ((! empty($line->fk_user)) && (! in_array($line->fk_user, $exclude_array))) {
				$exclude_array[] = $line->fk_user;
			}
		}
	}
	$form->select_users('', 'fk_user', 1, $exclude_array);
	print '</td></tr>';

	print '</table>';

	print '<table style=noborder align="right">';
	print '<tr><td align="center" colspan=2>';
	print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
	print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
	print '</td></tr>';
	print '</table>';
	print '</form>';

	print '</div>';
} else {
	// Display trainer card
	if ($id) {
		$agf = new Agefodd_teacher($db);
		$result = $agf->fetch($id);

		if ($result) {
			if ($mesg)
				print $mesg . "<br>";

				// View mode

			$head = trainer_prepare_head($agf);

			dol_fiche_head($head, 'card', $langs->trans("AgfTeacher"), 0, 'user');

			dol_agefodd_banner_tab($agf, 'id');
			print '<div class="underbanner clearboth"></div>';

			/*
			 * Delete confirm
			 */
			if ($action == 'delete') {
				print $form->formconfirm($_SERVER['PHP_SELF'] . "?id=" . $id, $langs->trans("AgfDeleteTeacher"), $langs->trans("AgfConfirmDeleteTeacher"), "confirm_delete", '', '', 1);
			}

			/*
			 * Confirm archive status change
			 */
			if ($action == 'archive' || $action == 'active') {
				if ($action == 'archive')
					$value = 1;
				if ($action == 'active')
					$value = 0;

				print $form->formconfirm($_SERVER['PHP_SELF'] . "?arch=" . $value . "&id=" . $id, $langs->trans("AgfFormationArchiveChange"), $langs->trans("AgfConfirmArchiveChange"), "arch_confirm_delete", '', '', 1);
			}

			print '<form name="create_contact" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
			if ($action == 'editcategory') {
				print '<input type="hidden" name="action" value="updatecat">' . "\n";
			}
			if ($action == 'edittraining') {
				print '<input type="hidden" name="action" value="updatetraining">' . "\n";
			}
			print '<input type="hidden" name="id" value="' . $agf->id . '">' . "\n";

			print '<table class="border" width="100%">';

			print '<tr><td>' . $langs->trans("AgfTrainerCategory");
			if ($action != 'editcategory' && $user->rights->agefodd->creer) {
				print '<a href="' . dol_buildpath('/agefodd/trainer/card.php', 1) . '?id=' . $agf->id . '&action=editcategory" style="text-align:right">' . img_picto($langs->trans('Edit'), 'edit') . '</a>';
			}
			print $form->textwithpicto('', $langs->trans("AgfTrainerCategoryDictHelp"), 1, 'help');
			print '</td>';

			print '<td>';

			if ($action == 'editcategory') {
				$option_cat = array ();
				$selected_cat = array ();
				// Build selected categorie
				if (is_array($agf->categories) && count($agf->categories) > 0) {
					foreach ( $agf->categories as $cat ) {
						$selected_cat[] = $cat->dictid;
					}
				}
				// Build all categorie available
				$result = $agf->fetchAllCategories();
				if ($result < 0) {
					setEventMessage($agf->error, 'errors');
				}
				foreach ( $agf->dict_categories as $cat ) {
					$option_cat[$cat->dictid] = $cat->code . '-' . $cat->label;
				}
				print $formAgefodd->agfmultiselectarray('trainercat', $option_cat, $selected_cat);
				print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
			} else {
				if (is_array($agf->categories) && count($agf->categories) > 0) {
					print '<ul>';
					foreach ( $agf->categories as $cat ) {
						print '<li>';
						print $cat->code . '-' . $cat->label . '(' . dol_trunc($cat->description, 50) . ')';
						print '</li>';
					}
					print '</ul>';
				}
			}
			print '</td>';
			print '</tr>';

			if ($conf->global->AGF_FILTER_TRAINER_TRAINING) {
				print '<tr><td>' . $langs->trans("AgfTrainerTraining");
				if ($action != 'edittraining' && $user->rights->agefodd->creer) {
					print '<a href="' . dol_buildpath('/agefodd/trainer/card.php', 1) . '?id=' . $agf->id . '&action=edittraining" style="text-align:right">' . img_picto($langs->trans('Edit'), 'edit') . '</a>';
				}
				print '</td>';

				print '<td>';
				if ($action == 'edittraining') {
					$option_cat = array ();
					$selected_cat = array ();
					// Build selected categorie
					if (is_array($agf->trainings) && count($agf->trainings) > 0) {
						foreach ( $agf->trainings as $training ) {
							$selected_training[] = $training->trainingid;
						}
					}
					require_once '../class/agefodd_formation_catalogue.class.php';
					$trainingcat = new Formation($db);
					$result = $trainingcat->fetch_all('ASC', 'c.ref', 0, 0);
					if ($result < 0) {
						setEventMessage($trainingcat->error, 'errors');
					}
					foreach ( $trainingcat->lines as $line ) {
						$option_training[$line->rowid] = $line->ref . '-' . $line->intitule;
					}
					print $formAgefodd->agfmultiselectarray('trainertraining', $option_training, $selected_training);
					print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
				} else {
					if (is_array($agf->trainings) && count($agf->trainings) > 0) {
						$training_det = new Formation($db);
						print '<ul>';
						foreach ( $agf->trainings as $training ) {
							print '<li>';
							$training_det->fetch($training->trainingid);
							print $training_det->getNomUrl();
							print '</li>';
						}
						print '</ul>';
					}
				}
				print '</td>';
				print '</tr>';
			}

			// Trainer type
			print '<tr><td>'.$langs->trans('AgfTrainerNature').'</td>';
			print '<td>'.$langs->trans('AgfTrainerType'.ucfirst($agf->type_trainer)).'</td>';
			print '</tr>';

			// See trainer
			if ($agf->type_trainer == $agf->type_trainer_def[1]) {
			    if ($user->rights->societe->contact->creer) {
			        require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
			        $contact = new Contact($db);
			        $contact->fetch($agf->spid);
			        print '<tr><td>'.$langs->trans('AgfSeeTrainer').'</td><td>'.$contact->getNomUrl(1);
				    if (!empty($contact->socid)) {
					    $contact->fetch_thirdparty();
					    print '<br>'.$contact->thirdparty->getNomUrl(1);
				    }
			        print '</td></tr>';
			    }
			} elseif ($agf->type_trainer == $agf->type_trainer_def[0]) {
			    if ($user->rights->user->creer) {
			        require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
			        $u = new User($db);
			        $u->fetch($agf->fk_user);
			        print '<tr><td>'.$langs->trans('AgfSeeTrainer').'</td><td>'.$u->getNomUrl(1).'</td></tr>';
			    }
			}

			print "</table>";
			print '</form>';

			print '</div>';
		} else {
			setEventMessage($agf->error, 'errors');
		}
	}
}

/*
 * Actions tabs
 *
 */

print '<div class="tabsAction">';
if ($action != 'create' && $action != 'edit' && $action != 'nfcontact' && $action != 'editcategory' && $action != 'edittraining') {

	// Send
	if (($user->rights->agefodd->creer || $user->rights->agefodd->modifier) && floatval(DOL_VERSION) > 8) {
		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $agf->id . '&action=presend&mode=init#formmailbeforetitle">' . $langs->trans('SendMail') . '</a></div>';
	} else {
		$class = "";
		$title = "";
		if(floatval(DOL_VERSION) < 9){
			$class = "classfortooltip";
			$title = $langs->trans("AGF_ForDoliVersionXMinOnly", 9);
		}

		print '<div class="inline-block divButAction"><a class="butActionRefused '.$class.'" href="#" title="'.$title.'">' . $langs->trans('SendMail') . '</a></div>';
	}


    $href = '';
    if ($agf->type_trainer == 'socpeople'){
        if(DOL_VERSION > 3.6){
            $href = dol_buildpath('/contact/card.php?id='.$agf->spid, 1).'&action=edit';
        } else {
            $href = dol_buildpath('/contact/fiche.php?id='.$agf->spid, 1).'&action=edit';
        }
    } else {
        if(DOL_VERSION > 3.6){
            $href = dol_buildpath('/user/card.php?id='.$agf->fk_user, 1).'&action=edit';
        } else {
            $href = dol_buildpath('/user/fiche.php?id='.$agf->fk_user, 1).'&action=edit';
        }
    }

	if ($user->rights->agefodd->creer) {
	    print '<a class="butAction" href="' . $href . '">' . $langs->trans('Modify') . '</a>';
		print '<a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?action=delete&id=' . $id . '">' . $langs->trans('Delete') . '</a>';
	} else {
	    print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Delete') . '</a>';
	}

	if ($user->rights->agefodd->modifier && ! $user->rights->agefodd->session->trainer) {
		if ($agf->archive == 0) {
			print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=archive&id=' . $id . '">' . $langs->trans('AgfArchiver') . '</a>';
		} else {
			print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=active&id=' . $id . '">' . $langs->trans('AgfActiver') . '</a>';
		}
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfArchiver') . '/' . $langs->trans('AgfActiver') . '</a>';
	}
}

/*
 * Action create
*/

$parameters = array();
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $agf, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;


print '</div>';

if ($id) {

	// Presend form
	$modelmail = 'agf_trainer';
	$defaulttopic = 'AgfSendEmailTrainer';
	$diroutput = $conf->agefodd->multidir_output[$agf->entity];
	$trackid = 'agftrainer' . $agf->id;

	include __DIR__ . '/../tpl/card_presend.tpl.php';
}

llxFooter();
$db->close();
