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
 * \file agefodd/trainee/card.php
 * \ingroup agefodd
 * \brief card of trainee
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agefodd_stagiaire.class.php');
require_once ('../class/agsession.class.php');
require_once ('../class/html.formagefodd.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');
require_once ('../lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../class/agefodd_session_stagiaire.class.php');

$langs->load("other");
$langs->load("companies");

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$arch = GETPOST('arch', 'int');
$url_back = GETPOST('url_back', 'alpha');
$session_id = GETPOST('session_id', 'int');
$importfrom = GETPOST('importfrom', 'alpha');

/*
 * Actions delete
*/
if ($action == 'confirm_delete' && $confirm == "yes" && ($user->rights->agefodd->creer || $user->rights->agefodd->modifier)) {
	$agf = new Agefodd_stagiaire($db);
	$result = $agf->remove($id);

	if ($result > 0) {
		Header("Location: list.php");
		exit();
	} else {
		if (strpos($agf->error, 'agefodd_session_stagiaire_ibfk_2')) {
			$agf->error = $langs->trans("AgfErrorTraineeInSession");
		}
		setEventMessage($agf->error, 'errors');
	}
}

/*
 * Action update (fiche rens stagiaire)
*/
if ($action == 'update' && ($user->rights->agefodd->creer || $user->rights->agefodd->modifier)) {
	if (! $_POST["cancel"]) {
		$agf = new Agefodd_stagiaire($db);

		$result = $agf->fetch($id);
		if ($result > 0) {
			setEventMessage($agf->error, 'errors');
		}
		
		$socid = GETPOST('societe', 'int');
		if ($socid==-1) {
			$socid=0;
		}
		
		if (empty($socid)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentities('Company')), 'errors');
			$error ++;
		}
		
		if (empty($error)) {
			$fk_socpeople = GETPOST('fk_socpeople', 'int');
	
			$agf->nom = GETPOST('nom', 'alpha');
			$agf->prenom = GETPOST('prenom', 'alpha');
			$agf->civilite = GETPOST('civility_id', 'alpha');
			$agf->socid = GETPOST('societe', 'int');
			$agf->fonction = GETPOST('fonction', 'alpha');
			$agf->tel1 = GETPOST('tel1', 'alpha');
			$agf->tel2 = GETPOST('tel2', 'alpha');
			$agf->mail = GETPOST('mail', 'alpha');
			$agf->note = GETPOST('note', 'alpha');
			$agf->date_birth = dol_mktime(0, 0, 0, GETPOST('datebirthmonth', 'int'), GETPOST('datebirthday', 'int'), GETPOST('datebirthyear', 'int'));
			if (! empty($fk_socpeople))
				$agf->fk_socpeople = $fk_socpeople;
			$agf->place_birth = GETPOST('place_birth', 'alpha');
			$result = $agf->update($user);
	
			if ($result > 0) {
				Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
				exit();
			} else {
				setEventMessage($agf->error, 'errors');
			}
		} else {
			$action='edit';
		}
	} else {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	}
}

/*
 * Action create (fiche formation)
*/

if ($action == 'create_confirm' && ($user->rights->agefodd->creer || $user->rights->agefodd->modifier)) {
	if (! $_POST["cancel"]) {
		$error = 0;

		$agf = new Agefodd_stagiaire($db);

		if ($importfrom == 'create') {

			$name = GETPOST('nom', 'alpha');
			$firstname = GETPOST('prenom', 'alpha');
			$civility_id = GETPOST('civility_id', 'alpha');
			$socid = GETPOST('societe', 'int');
			

			$create_thirdparty = GETPOST('create_thirdparty', 'int');

			if ($socid==-1) {
				unset($socid);
			}
			if ($create_thirdparty==-1) {
				unset($create_thirdparty);
			}
			
			if (empty($name) || empty($firstname)) {
				setEventMessage($langs->trans('AgfNameRequiredForParticipant'), 'errors');
				$error ++;
			}
			if (empty($civility_id)) {
				setEventMessage($langs->trans('AgfCiviliteMandatory'), 'errors');
				$error ++;
			}
			if (empty($socid) && empty($create_thirdparty)) {
				setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentities('Company')), 'errors');
				$error ++;
			}

			// Test trainee already exists or not
			if (empty($error)) {
				$result = $agf->searchByLastNameFirstNameSoc($name, $firstname, GETPOST('societe', 'int'));
				if ($result > 0) {
					setEventMessage($langs->trans('AgfTraineeAlreadyExists'), 'errors');
					$error ++;
				} elseif ($result < 0) {
					setEventMessage($agf->error, 'errors');
					$error ++;
				}
			}
			if (empty($error)) {

				$create_contact = GETPOST('create_contact', 'int');

				$socid = GETPOST('societe', 'int');
				$fonction = GETPOST('fonction', 'alpha');
				$tel1 = GETPOST('tel1', 'alpha');
				$tel2 = GETPOST('tel2', 'alpha');
				$mail = GETPOST('mail', 'alpha');
				$note = GETPOST('note', 'alpha');
				$societe_name = GETPOST('societe_name');
				$address = GETPOST('adresse', 'alpha');
				$zip = GETPOST('zipcode', 'alpha');
				$town = GETPOST('town', 'alpha');

				$stagiaire_type = GETPOST('stagiaire_type', 'int');

				$date_birth = dol_mktime(0, 0, 0, GETPOST('datebirthmonth', 'int'), GETPOST('datebirthday', 'int'), GETPOST('datebirthyear', 'int'));
				$place_birth = GETPOST('place_birth', 'alpha');

				$agf->nom = $name;
				$agf->prenom = $firstname;
				$agf->civilite = $civility_id;
				$agf->socid = $socid;
				$agf->fonction = $fonction;
				$agf->tel1 = $tel1;
				$agf->tel2 = $tel2;
				$agf->mail = $mail;
				$agf->note = $note;
				$agf->date_birth = $date_birth;
				$agf->place_birth = $place_birth;

				// Création tiers demandé
				if ($create_thirdparty > 0) {
					$socstatic = new Societe($db);

					$socstatic->name = $societe_name;
					$socstatic->phone = $tel1;
					$socstatic->email = $mail;
					$socstatic->address = $address;
					$socstatic->zip = $zip;
					$socstatic->town = $town;
					$socstatic->client = 1;
					$socstatic->code_client = - 1;

					$result = $socstatic->create($user);

					if (! $result >= 0) {
						$error = $socstatic->error;
						$errors = $socstatic->errors;
					}

					$agf->socid = $socstatic->id;
				}

				// Création du contact si demandé
				if ($create_contact > 0) {

					$contact = new Contact($db);

					$contact->civility_id = $civility_id;
					$contact->lastname = $name;
					$contact->firstname = $firstname;
					$contact->address = $address;
					$contact->zip = $zip;
					$contact->town = $$town;
					$contact->state_id = $state_id;
					$contact->country_id = $objectcountry_id;
					$contact->socid = ($socstatic->id > 0 ? $socstatic->id : $socid); // fk_soc
					$contact->statut = 1;
					$contact->email = $mail;
					$contact->phone_pro = $tel1;
					$contact->phone_mobile = $tel2;
					$contact->poste = $fonction;
					$contact->priv = 0;
					$contact->birthday = $date_birth;

					$result = $contact->create($user);
					if (! $result >= 0) {
						$error = $contact->error;
						$errors = $contact->errors;
					}
					$agf->fk_socpeople = $contact->id;
				}

				$result = $agf->create($user);
			}
		} elseif ($importfrom == 'contact') {

			// traitement de l'import d'un contact
			$contact = new Contact($db);
			$contactid = GETPOST('contact', 'int');
			if (! empty($contactid)) {
				$result = $contact->fetch($contactid);
				if ($result < 0) {
					setEventMessage($contact->error, 'errors');
				}

				$agf->nom = $contact->lastname;
				$agf->prenom = $contact->firstname;
				$agf->civilite = $contact->civility_id;
				$agf->socid = $contact->socid;
				$agf->fonction = $contact->poste;
				$agf->tel1 = $contact->phone_pro;
				$agf->tel2 = $contact->phone_mobile;
				$agf->mail = $contact->email;
				$agf->note = $contact->note;
				$agf->fk_socpeople = $contact->id;
				$agf->date_birth = $contact->birthday;

				$result = $agf->create($user);
			} else {
				$result = - 1;
				$agf->error = 'Select a contact';
			}
		}

		if ($result > 0 && empty($error)) {

			// Inscrire dans la session
			if ($session_id > 0) {

				$fk_soc_requester=GETPOST('fk_soc_requester', 'int');
				if ($fk_soc_requester<0) {
					$fk_soc_requester=0;
				}
				$fk_soc_link=GETPOST('fk_soc_link', 'int');
				if ($fk_soc_link<0) {
					$fk_soc_link=0;
				}

				$sessionstat = new Agefodd_session_stagiaire($db);
				$sessionstat->fk_session_agefodd = GETPOST('session_id', 'int');
				$sessionstat->fk_stagiaire = $agf->id;
				$sessionstat->fk_agefodd_stagiaire_type = GETPOST('stagiaire_type', 'int');
				$sessionstat->fk_soc_link = $fk_soc_link;
				$sessionstat->status_in_session = GETPOST('status_in_session', 'int');
				$sessionstat->fk_soc_requester = $fk_soc_requester;
				$result = $sessionstat->create($user);

				if ($result > 0) {
					setEventMessage($langs->trans('SuccessCreateStagInSession'), 'mesgs');
					$url_back = dol_buildpath('/agefodd/session/subscribers.php', 1) . '?id=' . $session_id;
				} else {
					setEventMessage($sessionstat->error, 'errors');
				}
			}

			$saveandstay = GETPOST('saveandstay');
			if (! empty($saveandstay)) {
				Header("Location: " . $_SERVER['HTTP_REFERER']);
			} else {
				if (strlen($url_back) > 0) {
					Header("Location: " . $url_back);
				} else {
					Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $agf->id);
				}
			}
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}

		$action = 'create';
	} else {
		Header("Location: list.php");
		exit();
	}
}

/*
 * View
*/
$title = ($action == 'nfcontact' || $action == 'create' ? $langs->trans("AgfMenuActStagiaireNew") : $langs->trans("AgfStagiaireDetail"));
llxHeader('', $title);

$form = new Form($db);
$formcompany = new FormCompany($db);
$formAgefodd = new FormAgefodd($db);

/*
 * Action create
*/
if ($action == 'create' && ($user->rights->agefodd->creer || $user->rights->agefodd->modifier)) {

	print "\n" . '<script type="text/javascript">
		$(document).ready(function () {
			$("input[type=radio][name=create_thirdparty]").change(function() {
				if($(this).val()==1) {
					$(".create_thirdparty_block").show();
					$(".select_thirdparty_block").hide();
				}else {
					$(".create_thirdparty_block").hide();
					$(".select_thirdparty_block").show();
				}
			});

			$("select[name=importfrom]").change(function() {
				if($(this).val()=="contact") {
					$("#fromcontact").show();
					//hack to force select display again
					$(\'#contact\').select2({dir: \'ltr\',width: \'resolve\',minimumInputLength: '.$conf->global->CONTACT_USE_SEARCH_TO_SELECT.'});
					$("#fromblanck").hide();
				}else {
					$("#fromcontact").hide();
					$("#fromblanck").show();
				}
			});

			if($("input[type=radio][name=create_thirdparty]:checked").val()==1) {
				$(".create_thirdparty_block").show();
				$(".select_thirdparty_block").hide();
			}else {
				$(".create_thirdparty_block").hide();
				$(".select_thirdparty_block").show();
			}

			if($("select[name=importfrom] option:selected").val()=="contact") {
				$("#fromcontact").show();
				$("#fromblanck").hide();
			}else {
				$("#fromcontact").hide();
				$("#fromblanck").show();
			}

		});';
	print "\n" . "</script>\n";

	print '<form name="create" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="create_confirm">';
	if ($url_back)
		print '<input type="hidden" name="url_back" value="' . $url_back . '">' . "\n";

	print '<b>' . $langs->trans("AgfSelectTraineeCreationForm") . ': </b>';

	print '<select name="importfrom" id="importfrom" class="flat">';
	$selected = '';
	if ($importfrom == 'contact')
		$selected = ' selected="selected" ';
	print '<option value="contact" ' . $selected . '>' . $langs->trans("AgfMenuActStagiaireNewFromContact") . '</option>';
	$selected = '';
	if ($importfrom == 'create' || empty($importfrom))
		$selected = ' selected="selected" ';
	print '<option value="create" ' . $selected . '>' . $langs->trans("AgfMenuActStagiaireNew") . '</option>';
	print '</select>';

	print '<div id="fromcontact">';
	print_fiche_titre($langs->trans("AgfMenuActStagiaireNewFromContact"));

	print '<table class="border" width="100%">';

	print '<tr><td width="20%">' . $langs->trans("AgfContactImportAsStagiaire") . '</td>';
	print '<td>';

	$agf_static = new Agefodd_stagiaire($db);
	$agf_static->fetch_all('DESC', 's.rowid', '', 0);
	$exclude_array = array ();
	if (is_array($agf_static->lines) && count($agf_static->lines) > 0) {
		foreach ( $agf_static->lines as $line ) {
			if (! empty($line->fk_socpeople)) {
				$exclude_array[] = $line->fk_socpeople;
			}
		}
	}
	$formAgefodd->select_contacts_custom(0, '', 'contact', 1, $exclude_array, '', 1, '', 1);
	print '</td></tr>';

	print '</table>';
	print '</div>';

	print '<div id="fromblanck">';
	print_fiche_titre($langs->trans("AgfMenuActStagiaireNew"));

	print '<table class="border" width="100%">';

	print '<tr class="liste_titre"><td colspan="4"><strong>' . $langs->trans("ThirdParty") . '</strong></td>';

	if (GETPOST('create_thirdparty', 'int') > 0) {
		$checkedYes = 'checked="checked"';
		$checkedNo = '';
	} else {
		$checkedYes = '';
		$checkedNo = 'checked="checked"';
	}

	print '<tr><td>' . $langs->trans('CreateANewThirPartyFromTraineeForm');
	print img_picto($langs->trans("CreateANewThirPartyFromTraineeFormInfo"), 'help');
	print '</td>';
	print '<td colspan="3">';
	print '<input type="radio" id="create_thirdparty_confirm" name="create_thirdparty" value="1" ' . $checkedYes . '/> <label for="create_thirdparty_confirm">' . $langs->trans('Yes') . '</label>';
	print '<input type="radio" id="create_thirdparty_cancel" name="create_thirdparty" ' . $checkedNo . ' value="-1"/> <label for="create_thirdparty_cancel">' . $langs->trans('no') . '</label>';
	print '</td>';
	print '	</tr>';
	print '<tr class="select_thirdparty_block"><td class="fieldrequired">' . $langs->trans("Company") . '</td><td colspan="3">';
	print $form->select_company(GETPOST('societe', 'int'), 'societe', '(s.client IN (1,3,2))', 1, 1);
	print '</td></tr>';

	print '<tr class="create_thirdparty_block"><td>' . $langs->trans("ThirdPartyName") . '</td>';
	print '<td colspan="3" ><input name="societe_name" class="flat" size="50" value="' . GETPOST('societe_name', 'alpha') . '"></td></tr>';

	// Address
	print '<tr class="create_thirdparty_block"><td valign="top">' . $langs->trans('Address') . '</td><td colspan="3"><textarea name="adresse" cols="40" rows="3" wrap="soft">';
	print $object->address;
	print '</textarea></td></tr>';

	// Zip / Town
	print '<tr class="create_thirdparty_block"><td>' . $langs->trans('Zip') . '</td><td>';
	print $formcompany->select_ziptown($object->zip, 'zipcode', array (
			'town',
			'selectcountry_id',
			'departement_id'
	), 6);
	print '</td><td>' . $langs->trans('Town') . '</td><td>';
	print $formcompany->select_ziptown($object->town, 'town', array (
			'zipcode',
			'selectcountry_id',
			'departement_id'
	));
	print '</td></tr>';

	// Infos participant
	print '<tr class="liste_titre"><td colspan="4"><strong>' . $langs->trans("AgfMailTypeContactTrainee") . '</strong></td>';

	print '<tr><td><span class="fieldrequired">' . $langs->trans("AgfCivilite") . '</span></td>';
	print '<td colspan="3">' . $formcompany->select_civility(GETPOST('civility_id')) . '</td>';
	print '</tr>';

	print '<tr><td><span class="fieldrequired">' . $langs->trans("Firstname") . '</span></td>';
	print '<td colspan="3"><input name="prenom" class="flat" size="50" value="' . GETPOST('prenom', 'alpha') . '"></td></tr>';

	print '<tr><td><span class="fieldrequired">' . $langs->trans("Lastname") . '</span></td>';
	print '<td colspan="3"><input name="nom" class="flat" size="50" value="' . GETPOST('nom', 'alpha') . '"></td></tr>';

	print '<tr><td>' . $langs->trans('CreateANewContactFromTraineeForm');
	print img_picto($langs->trans("CreateANewContactFromTraineeFormInfo"), 'help');
	print '</td>';
	print '<td colspan="3">';
	if (GETPOST('create_contact', 'int') > 0) {
		$checkedYes = 'checked="checked"';
		$checkedNo = '';
	} else {
		$checkedYes = '';
		$checkedNo = 'checked="checked"';
	}
	print '<input type="radio" id="create_contact_confirm" name="create_contact" value="1" ' . $checkedYes . '/> <label for="create_contact_confirm">' . $langs->trans('Yes') . '</label>';
	print '<input type="radio" id="create_contact_cancel" name="create_contact" ' . $checkedNo . ' value="-1"/> <label for="create_contact_cancel">' . $langs->trans('no') . '</label>';
	print '</td>';
	print '	</tr>';

	print '<tr><td>' . $langs->trans("AgfFonction") . '</td>';
	print '<td colspan="3"><input name="fonction" class="flat" size="50" value="' . GETPOST('fonction', 'alpha') . '"></td></tr>';

	print '<tr><td>' . $langs->trans("Phone") . '</td>';
	print '<td colspan="3"><input name="tel1" class="flat" size="50" value="' . GETPOST('tel1', 'alpha') . '"></td></tr>';

	print '<tr><td>' . $langs->trans("Mobile") . '</td>';
	print '<td colspan="3"><input name="tel2" class="flat" size="50" value="' . GETPOST('tel2', 'alpha') . '"></td></tr>';

	print '<tr><td>' . $langs->trans("Mail") . '</td>';
	print '<td colspan="3"><input name="mail" class="flat" size="50" value="' . GETPOST('mail', 'alpha') . '"></td></tr>';

	print '<tr><td>' . $langs->trans("DateToBirth") . '</td>';
	print '<td>';
	print $form->select_date('', 'datebirth', '', '', 1);
	print '</td></tr>';

	print '<tr><td>' . $langs->trans("AgfPlaceBirth") . '</td>';
	print '<td colspan="3"><input name="place_birth" class="flat" size="50" value=""></td></tr>';

	print '<tr><td valign="top">' . $langs->trans("AgfNote") . '</td>';
	print '<td colspan="3"><textarea name="note" rows="3" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';
	print '</table>';
	print '</div>';

	print '<table class="border" width="100%">';
	print '<tr class="liste_titre"><td colspan="4"><strong>' . $langs->trans("AgfSessionToRegister") . '</strong></td>';

	// Session
	if (empty($sortorder))
		$sortorder = "ASC";
	if (empty($sortfield))
		$sortfield = "s.dated";

	if (! empty($session_id)) {
		$filter['s.rowid'] = $session_id;
	}

	$agf = new Agsession($db);

	$resql = $agf->fetch_all($sortorder, $sortfield, 0, 0, $filter);
	$sessions = array ();
	foreach ( $agf->lines as $line ) {
		$sessions[$line->rowid] = $line->ref_interne . ' - ' . $line->intitule . ' - ' . dol_print_date($line->dated, 'daytext');
	}

	print '<tr class="agelfoddline">';
	print '<td>' . $langs->trans('AgfSelectAgefoddSession') . '</td>';
	print '<td colspan="3">';
	print $form->selectarray('session_id', $sessions, GETPOST('session_id'), 1);
	print '</td>';
	print '</tr>';

	if (! empty($conf->global->AGF_USE_STAGIAIRE_TYPE)) {
		// Public stagiaire
		$stagiaire_type = GETPOST('stagiaire_type', 'int');
		if (empty($stagiaire_type)) {
			$stagiaire_type = $conf->global->AGF_DEFAULT_STAGIAIRE_TYPE;
		}
		print '<tr class="agelfoddline"><td>' . $langs->trans("AgfPublic") . '</td><td colspan="3">';
		print $formAgefodd->select_type_stagiaire($stagiaire_type, 'stagiaire_type', 'active=1', 1);
		print '</td></tr>';
		print '<tr class="agelfoddline"><td>' . $langs->trans('AgfTraineeSocDocUse') . '</td><td colspan="3">';
		print $form->select_company(0, 'fk_soc_link', '', 1, 1, 0);
		print '</td></tr>';
		print '<tr class="agelfoddline"><td>' . $langs->trans('AgfTypeRequester') . '</td><td colspan="3">';
		print $form->select_company(0, 'fk_soc_requester', '', 1, 1, 0);
		print '</td></tr>';
		if (empty($conf->global->AGF_SESSION_TRAINEE_STATUS_AUTO)) {
			print '<tr class="agelfoddline"><td>' . $langs->trans('Status') . '</td><td colspan="3">';
			print $formAgefodd->select_stagiaire_session_status('status_in_session', 0);
			print '</td></tr>';
		}
	}

	print '</table>';
	print '</div>';

	print '<table style=noborder align="right">';
	print '<tr><td align="center" colspan=2>';
	print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
	print '<input type="submit" class="butAction" name="saveandstay" value="' . $langs->trans("AgfSaveAndStay") . '"> &nbsp; ';
	print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
	print '</td></tr>';
	print '</table>';
	print '</form>';
} else {
	// Affichage de la fiche "stagiaire"
	if ($id) {
		$agf = new Agefodd_stagiaire($db);
		$result = $agf->fetch($id);

		if ($result) {
			$head = trainee_prepare_head($agf);

			dol_fiche_head($head, 'card', $langs->trans("AgfStagiaireDetail"), 0, 'user');

			// Affichage en mode "édition"
			if ($action == 'edit') {
				print '<form name="update" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
				print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
				print '<input type="hidden" name="action" value="update">';

				print '<input type="hidden" name="id" value="' . $id . '">';

				print '<table class="border" width="100%">';
				print '<tr><td width="20%">' . $langs->trans("Ref") . '</td>';
				print '<td>' . $agf->id . '</td></tr>';

				// if contact trainee from contact then display contact inforamtion
				if (empty($agf->fk_socpeople)) {

					print '<tr><td>' . $langs->trans("Firstname") . '</td>';
					print '<td><input name="prenom" class="flat" size="50" value="' . ucfirst($agf->prenom) . '"></td></tr>';

					print '<tr><td>' . $langs->trans("Lastname") . '</td>';
					print '<td><input name="nom" class="flat" size="50" value="' . strtoupper($agf->nom) . '"></td></tr>';

					print '<tr><td>' . $langs->trans("AgfCivilite") . '</td>';

					print '<td>' . $formcompany->select_civility($agf->civilite) . '</td>';
					print '</tr>';

					print '<tr><td valign="top">' . $langs->trans("Company") . '</td><td>';

					print $form->select_company($agf->socid, 'societe', '(s.client IN (1,3,2))', 1, 1);

					print '</td></tr>';

					print '<tr><td>' . $langs->trans("AgfFonction") . '</td>';
					print '<td><input name="fonction" class="flat" size="50" value="' . $agf->fonction . '"></td></tr>';

					print '<tr><td>' . $langs->trans("Phone") . '</td>';
					print '<td><input name="tel1" class="flat" size="50" value="' . $agf->tel1 . '"></td></tr>';

					print '<tr><td>' . $langs->trans("Mobile") . '</td>';
					print '<td><input name="tel2" class="flat" size="50" value="' . $agf->tel2 . '"></td></tr>';

					print '<tr><td>' . $langs->trans("Mail") . '</td>';
					print '<td><input name="mail" class="flat" size="50" value="' . $agf->mail . '"></td></tr>';

					print '<tr><td>' . $langs->trans("DateToBirth") . '</td>';
					print '<td>';
					print $form->select_date($agf->date_birth, 'datebirth', 0, 0, 1, 'update');
					print '</td></tr>';
				} else {
					print '<input type="hidden" name="fk_socpeople" value="' . $agf->fk_socpeople . '">';
					print '<tr><td>' . $langs->trans("Lastname") . '</td>';
					print '<td><a href="' . dol_buildpath('/contact/card.php', 1) . '?id=' . $agf->fk_socpeople . '">' . strtoupper($agf->nom) . '</a></td></tr>';
					print '<input type="hidden" name="nom" value="' . $agf->nom . '">';

					print '<tr><td>' . $langs->trans("Firstname") . '</td>';
					print '<td>' . ucfirst($agf->prenom) . '</td></tr>';
					print '<input type="hidden" name="prenom" value="' . $agf->prenom . '">';

					print '<tr><td>' . $langs->trans("AgfCivilite") . '</td>';

					$contact_static = new Contact($db);
					$contact_static->civility_id = $agf->civilite;

					print '<td>' . $contact_static->getCivilityLabel() . '</td></tr>';
					print '<input type="hidden" name="civility_id" value="' . $agf->civilite . '">';

					print '<tr><td valign="top">' . $langs->trans("Company") . '</td><td>';
					if ($agf->socid) {
						print '<a href="' . dol_buildpath('/comm/card.php', 1) . '?socid=' . $agf->socid . '">';
						print '<input type="hidden" name="societe" value="' . $agf->socid . '">';
						print img_object($langs->trans("ShowCompany"), "company") . ' ' . dol_trunc($agf->socname, 20) . '</a>';
					} else {
						print '&nbsp;';
						print '<input type="hidden" name="societe" value="">';
					}
					print '</td></tr>';

					print '<tr><td>' . $langs->trans("AgfFonction") . '</td>';
					print '<td>' . $agf->fonction . '</td></tr>';
					print '<input type="hidden" name="fonction" value="' . $agf->fonction . '">';

					print '<tr><td>' . $langs->trans("Phone") . '</td>';
					print '<td>' . dol_print_phone($agf->tel1) . '</td></tr>';
					print '<input type="hidden" name="tel1" value="' . $agf->tel1 . '">';

					print '<tr><td>' . $langs->trans("Mobile") . '</td>';
					print '<td>' . dol_print_phone($agf->tel2) . '</td></tr>';
					print '<input type="hidden" name="tel2" value="' . $agf->tel1 . '">';

					print '<tr><td>' . $langs->trans("Mail") . '</td>';
					print '<td>' . dol_print_email($agf->mail, $agf->id, $agf->socid, 'AC_EMAIL', 25) . '</td></tr>';
					print '<input type="hidden" name="mail" value="' . $agf->mail . '">';

					print '<tr><td>' . $langs->trans("DateToBirth") . '</td>';
					print '<td>' . dol_print_date($agf->date_birth, "day");
					print '</td></tr>';
				}

				print '<tr><td>' . $langs->trans("AgfPlaceBirth") . '</td>';
				print '<td><input name="place_birth" class="flat" size="50" value="' . $agf->place_birth . '"></td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfNote") . '</td>';
				if (! empty($agf->note))
					$notes = nl2br($agf->note);
				else
					$notes = $langs->trans("AgfUndefinedNote");
				print '<td><textarea name="note" rows="3" cols="0" class="flat" style="width:360px;">' . stripslashes($agf->note) . '</textarea></td></tr>';

				print '</table>';
				print '</div>';
				print '<table style=noborder align="right">';
				print '<tr><td align="center" colspan=2>';
				print '<input type="submit" class="butAction" name="save" value="' . $langs->trans("Save") . '"> &nbsp; ';
				print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
				if (! empty($agf->fk_socpeople)) {
					print '<a class="butAction" href="' . dol_buildpath('/contact/card.php', 1) . '?id=' . $agf->fk_socpeople . '">' . $langs->trans('AgfModifierFicheContact') . '</a>';
				}
				print '</td></tr>';
				print '</table>';
				print '</form>';

				print '</div>' . "\n";
			} else {
				// Display in "view" mode
				/*
				* Confirmation de la suppression
				*/
				if ($action == 'delete') {
					$ret = $form->form_confirm($_SERVER['PHP_SELF'] . "?id=" . $id, $langs->trans("AgfDeleteOps"), $langs->trans("AgfConfirmDeleteTrainee"), "confirm_delete", '', '', 1);
					if ($ret == 'html')
						print '<br>';
				}

				print '<table class="border" width="100%">';

				print '<tr><td width="20%">' . $langs->trans("Ref") . '</td>';
				print '<td>' . $form->showrefnav($agf, 'id	', '', 1, 'rowid', 'id') . '</td></tr>';

				print '<tr><td>' . $langs->trans("Firstname") . '</td>';
				print '<td>' . ucfirst($agf->prenom) . '</td></tr>';

				if (! empty($agf->fk_socpeople)) {
					print '<tr><td>' . $langs->trans("Lastname") . '</td>';
					print '<td><a href="' . dol_buildpath('/contact/card.php', 1) . '?id=' . $agf->fk_socpeople . '">' . strtoupper($agf->nom) . '</a></td></tr>';
				} else {
					print '<tr><td>' . $langs->trans("Lastname") . '</td>';
					print '<td>' . strtoupper($agf->nom) . '</td></tr>';
				}

				print '<tr><td>' . $langs->trans("AgfCivilite") . '</td>';

				$contact_static = new Contact($db);
				$contact_static->civility_id = $agf->civilite;

				print '<td>' . $contact_static->getCivilityLabel() . '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("Company") . '</td><td>';
				if ($agf->socid) {
					$soc = new Societe($db);
					$soc->fetch($agf->socid);
					print $soc->getNomUrl(1);
				} else {
					print '&nbsp;';
				}
				print '</td></tr>';

				print '<tr><td>' . $langs->trans("AgfFonction") . '</td>';
				print '<td>' . $agf->fonction . '</td></tr>';

				print '<tr><td>' . $langs->trans("Phone") . '</td>';
				print '<td>' . dol_print_phone($agf->tel1) . '</td></tr>';

				print '<tr><td>' . $langs->trans("Mobile") . '</td>';
				print '<td>' . dol_print_phone($agf->tel2) . '</td></tr>';

				print '<tr><td>' . $langs->trans("Mail") . '</td>';
				print '<td>' . dol_print_email($agf->mail, $agf->id, $agf->socid, 'AC_EMAIL', 25) . '</td></tr>';

				print '<tr><td>' . $langs->trans("DateToBirth") . '</td>';
				print '<td>' . dol_print_date($agf->date_birth, "day") . '</td></tr>';

				print '<tr><td>' . $langs->trans("AgfPlaceBirth") . '</td>';
				print '<td>' . $agf->place_birth . '</td></tr>';

				print '<tr><td>' . $langs->trans("AgfNote") . '</td>';
				if (! empty($agf->note))
					$notes = nl2br($agf->note);
				else
					$notes = $langs->trans("AgfUndefinedNote");
				print '<td>' . stripslashes($notes) . '</td></tr>';

				print "</table>";

				print '</div>';
			}
		} else {
			setEventMessage($agf->error, 'errors');
		}
	}
}

/*
 * Barre d'actions
*
*/

print '<div class="tabsAction">';

if ($action != 'create' && $action != 'edit' && $action != 'nfcontact') {
	if ($user->rights->agefodd->creer || $user->rights->agefodd->modifier) {
		print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&id=' . $id . '">' . $langs->trans('Modify') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';
	}
	if ($user->rights->agefodd->creer || $user->rights->agefodd->modifier) {
		print '<a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?action=delete&id=' . $id . '">' . $langs->trans('Delete') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Delete') . '</a>';
	}
}

print '</div>';

llxFooter();
$db->close();