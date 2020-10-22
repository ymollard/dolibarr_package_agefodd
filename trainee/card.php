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


$hookmanager->initHooks(array(
		'agefoddsessiontrainee'
));

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$url_back = GETPOST('url_back', 'alpha');
$session_id = GETPOST('session_id', 'int');
$importfrom = GETPOST('importfrom', 'alpha');

$name = GETPOST('nom', 'alpha');
$firstname = GETPOST('prenom', 'alpha');
$civility_id = GETPOST('civility_id', 'alpha');
$disable_auto_mail = GETPOST('disable_auto_mail', 'int');
$date_birth = dol_mktime(0, 0, 0, GETPOST('datebirthmonth', 'int'), GETPOST('datebirthday', 'int'), GETPOST('datebirthyear', 'int'));
$place_birth = GETPOST('place_birth', 'alpha');

$create_thirdparty = GETPOST('create_thirdparty', 'int');
if ($create_thirdparty==-1) {
	$create_thirdparty = 0;
}

$create_contact = GETPOST('create_contact', 'int');

$socid = GETPOST('societe', 'int');
$fonction = GETPOST('fonction', 'alpha');
$tel1 = GETPOST('tel1', 'alpha');
$tel2 = GETPOST('tel2', 'alpha');
$mail = GETPOST('mail', 'alpha');
$note = GETPOST('note', 'alpha');
$societe_name = GETPOST('societe_name', 'none');
$prospcli = GETPOST('prospcli', 'int');
$custcats = GETPOST('custcats', 'array');
$typent_id = GETPOST('typent_id', 'int');
$address = GETPOST('adresse', 'alpha');
$zip = GETPOST('zipcode', 'alpha');
$town = GETPOST('town', 'alpha');
$country_id = GETPOST('country_id', 'int');

$stagiaire_type = GETPOST('stagiaire_type', 'int');

$agf = new Agefodd_stagiaire($db);
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($agf->table_element);

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $agf, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0)
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


//Select mail models is same action as presend
if (GETPOST('modelselected', 'none')) $action = 'presend';

// Cancel action
if (isset($_POST['cancel'])) {
	$action = '';
	if (strlen($url_back) > 0)
	{
		Header("Location: ".$url_back);
		exit;
	}
}


$form = new Form($db);
$formcompany = new FormCompany($db);
$formAgefodd = new FormAgefodd($db);

/*
 * Actions delete
*/
if ($action == 'confirm_delete' && $confirm == "yes" && ($user->rights->agefodd->creer || $user->rights->agefodd->modifier)) {
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

		$result = $agf->fetch($id);
		if ($result > 0) {
			setEventMessage($agf->error, 'errors');
		}

		if ($socid==-1) {
			$socid=0;
		}

		if (empty($socid)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentities('Company')), 'errors');
		}

		if (empty($error)) {
			$fk_socpeople = GETPOST('fk_socpeople', 'int');

			$agf->nom = $name;
			$agf->prenom = $firstname;
			$agf->civilite = $civility_id;
			$agf->socid = $socid;
			$agf->fonction = $fonction;
			$agf->tel1 = $tel1;
			$agf->tel2 = $tel2;
			$agf->mail = $mail;
			$agf->note = $note;
			$agf->disable_auto_mail = $disable_auto_mail;

			$agf->date_birth = $date_birth;
			if (! empty($fk_socpeople))
				$agf->fk_socpeople = $fk_socpeople;
			$agf->place_birth = $place_birth;

			$extrafields->setOptionalsFromPost($extralabels, $agf);
			$result = $agf->update($user);

			if ($result > 0) {
				Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
				exit();
			} else {
				$action='edit';
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

		if ($importfrom == 'create') {
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
			if (empty($error) && empty($create_thirdparty)) {
				$result = $agf->searchByLastNameFirstNameSoc($name, $firstname, $socid);
				if ($result > 0) {
					setEventMessage($langs->trans('AgfTraineeAlreadyExists'), 'errors');
					$error ++;
				} elseif ($result < 0) {
					setEventMessage($agf->error, 'errors');
					$error ++;
				}
			}
			if (empty($error)) {
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
				$agf->disable_auto_mail = $disable_auto_mail;

				// Création tiers demandé
				if ($create_thirdparty > 0) {
					$socstatic = new Societe($db);

					$socstatic->name = $societe_name;
					$socstatic->phone = $tel1;
					$socstatic->email = $mail;
					$socstatic->address = $address;
					$socstatic->zip = $zip;
					$socstatic->town = $town;
					$socstatic->country_id = $country_id;
					$socstatic->client = $prospcli;
					$socstatic->client = $prospcli;
					$socstatic->code_client = - 1;
					$socstatic->typent_id = $typent_id;

					$result = $socstatic->create($user);

					if ($result <= 0) {
						setEventMessages($socstatic->error, $socstatic->errors, 'errors');
					} else {
						$result = $socstatic->setCategories($custcats, 'customer');
						if ($result < 0)
						{
							$error++;
							setEventMessages($socstatic->error, $socstatic->errors, 'errors');
						}
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
					$contact->town = $town;
					$contact->country_id = $country_id;
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
						setEventMessages($contact->error, $contact->errors, 'errors');
					}
					$agf->fk_socpeople = $contact->id;
				}
				$extrafields->setOptionalsFromPost($extralabels, $agf);

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
				$agf->civilite = $contact->civility_code;
				$agf->socid = $contact->socid;
				$agf->fonction = $contact->poste;
				$agf->tel1 = $contact->phone_pro;
				$agf->tel2 = $contact->phone_mobile;
				$agf->mail = $contact->email;
				$agf->note = $contact->note_public;
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

				$fk_soc_requester = GETPOST('fk_soc_requester', 'int');
				if ($fk_soc_requester<0) {
					$fk_soc_requester=0;
				}
				$fk_soc_link = GETPOST('fk_soc_link', 'int');
				if ($fk_soc_link<0) {
					$fk_soc_link=0;
				}

				$sessionstat = new Agefodd_session_stagiaire($db);
				$sessionstat->fk_session_agefodd = $session_id;
				$sessionstat->fk_stagiaire = $agf->id;
				$sessionstat->fk_agefodd_stagiaire_type = $stagiaire_type;
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
			} else {
                setEventMessage('AgfSuccessCreateStag', 'mesgs');
            }

			$saveandstay = GETPOST('saveandstay', 'none');
            if (empty($saveandstay)) {
                if (strlen($url_back) > 0) {
                    header("Location: " . $url_back);
                } else {
                    header("Location: " . $_SERVER['PHP_SELF'] . "?session_id=" . $session_id.'&societe='.$socid);
                }
                exit();
            } else {
	            $name = '';
	            $firstname = '';
	            $civility_id = '';
	            $disable_auto_mail = '';
	            $date_birth = '';
	            $place_birth = '';
	            $fonction = '';
	            $tel1 = '';
	            $tel2 = '';
	            $mail = '';
	            $note = '';
	            $societe_name = '';
	            $prospcli = -1;
	            $custcats = array();
	            $typent_id = -1;
	            $address = '';
	            $zip = '';
	            $town = '';
	            $country_id = -1;
	            $stagiaire_type = 0;
	            $create_thirdparty=0;
            }
		} else {
			setEventMessage($agf->error, 'errors');
		}

		$action = 'create';
	} else {
		Header("Location: list.php");
		exit();
	}
}


if (!empty($id) && $action == 'send')
{
    $object = new Agefodd_stagiaire($db);
    $result = $object->fetch($id);
    if($result>0){
        // Actions to send emails
        $actiontypecode = 'AC_OTH_AUTO';
        $trigger_name = 'AGFTRAINEE_SENTBYMAIL';
        $autocopy = 'MAIN_MAIL_AUTOCOPY_AGFTRAINEE_TO';
        $trackid = 'agftrainee' . $object->id;
        include __DIR__.'/../actions_sendmails.inc.php';
    }

}

/*
 * View
*/
if ($action == 'create' && ($user->rights->agefodd->creer || $user->rights->agefodd->modifier)) {
	llxHeader('', $langs->trans('AgfMenuActStagiaireNew'));
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
					$(\'#contact\').select2({dir: \'ltr\',width: \'resolve\',minimumInputLength: '.(empty($conf->global->CONTACT_USE_SEARCH_TO_SELECT)?0:$conf->global->CONTACT_USE_SEARCH_TO_SELECT).'});
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
	print '<input type="hidden" name="session_id" value="' . $session_id . '">';
	print '<input type="hidden" name="action" value="create_confirm">';
	if ($url_back)
		print '<input type="hidden" name="url_back" value="' . $url_back . '">' . "\n";

	print '<b>' . $langs->trans("AgfSelectTraineeCreationForm") . ': </b>';

	print '<select name="importfrom" id="importfrom" class="flat">';
	$selected = '';
	if (! $user->rights->agefodd->session->trainer) {
		if ($importfrom == 'contact')
			$selected = ' selected="selected" ';
		print '<option value="contact" ' . $selected . '>' . $langs->trans("AgfMenuActStagiaireNewFromContact") . '</option>';
	}
	$selected = '';
	if ($importfrom == 'create' || empty($importfrom))
		$selected = ' selected="selected" ';
	print '<option value="create" ' . $selected . '>' . $langs->trans("AgfMenuActStagiaireNew") . '</option>';
	print '</select>';

	print '<div id="fromcontact">';
	print load_fiche_titre($langs->trans("AgfMenuActStagiaireNewFromContact"));

	print '<table class="border" width="100%">';

	if (! $user->rights->agefodd->session->trainer) {

		$formAgefodd = new FormAgefodd($db);
		print '<tr><td width="20%">' . $langs->trans("AgfContactImportAsStagiaire") . '</td>';
		print '<td>';

		$agf_static = new Agefodd_stagiaire($db);
		$exclude_array = $agf_static->fetch_all_id_by('fk_socpeople');
		if (is_array($exclude_array))
		{
			unset($exclude_array['']);
			unset($exclude_array[0]);
			$exclude_array = array_keys($exclude_array);
		}
		else
		{
			$exclude_array = array();
		}
		$formAgefodd->select_contacts_custom(0, '', 'contact', 1, $exclude_array, '', 1, '', 1);
		print '</td></tr>';
	}
	print '</table>';
	print '</div>';

	print '<div id="fromblanck">';
	print load_fiche_titre($langs->trans("AgfMenuActStagiaireNew"));

	print '<table class="border" width="100%">';

	print '<tr class="liste_titre"><td colspan="4"><strong>' . $langs->trans("ThirdParty") . '</strong></td>';

	if ($create_thirdparty > 0) {
		$checkedYes = 'checked="checked"';
		$checkedNo = '';
	} else {
		$checkedYes = '';
		$checkedNo = 'checked="checked"';
	}

	print '<tr id="tr_create_thirdparty"><td>' . $langs->trans('CreateANewThirPartyFromTraineeForm');
	print img_picto($langs->trans("CreateANewThirPartyFromTraineeFormInfo"), 'help');
	print '</td>';
	print '<td colspan="3">';
	print '<input type="radio" id="create_thirdparty_confirm" name="create_thirdparty" value="1" ' . $checkedYes . '/> <label for="create_thirdparty_confirm">' . $langs->trans('Yes') . '</label>';
	print '<input type="radio" id="create_thirdparty_cancel" name="create_thirdparty" ' . $checkedNo . ' value="-1"/> <label for="create_thirdparty_cancel">' . $langs->trans('no') . '</label>';
	print '</td>';
	print '	</tr>';
	print '<tr class="select_thirdparty_block"><td class="fieldrequired">' . $langs->trans("Company") . '</td><td colspan="3">';
	if (!empty($conf->global->AGEFODD_USE_SELECT_WITH_AJAX)) print $form->select_company($socid, 'societe', '(s.client IN (1,3,2))', 'SelectThirdParty', 1);
	else print $form->select_thirdparty_list($socid, 'societe', '(s.client IN (1,3,2))', 'SelectThirdParty', 1);
	print '</td></tr>';

	print '<tr class="create_thirdparty_block"><td class="fieldrequired">' . $langs->trans("ThirdPartyName") . '</td>';
	print '<td colspan="3" ><input name="societe_name" class="flat" size="50" value="' . GETPOST('societe_name', 'alpha') . '"></td></tr>';

	// Prospect/Customer
	print '<tr class="create_thirdparty_block"><td>' . $langs->trans('ProspectCustomer') . '</td><td>';
	print $formcompany->selectProspectCustomerType($prospcli, 'prospcli', 'prospcli');
	print '</td></tr>';

	// Categories
	if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire))
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$langs->loadLangs(array('company', 'categories'));
		print '<tr class="create_thirdparty_block"><td>' . $langs->trans('CustomersProspectsCategoriesShort') . '</td><td>';
		// Customer
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_CUSTOMER, null, 'parent', null, null, 1);
		print $form->multiselectarray('custcats', $cate_arbo, $custcats, null, null, null, null, "90%");
		print '</td></tr>';
	}

	print '<tr class="create_thirdparty_block"><td>' . $langs->trans('ThirdPartyType') . '</td><td>';
	$sortparam = (empty($conf->global->SOCIETE_SORT_ON_TYPEENT) ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT); // NONE means we keep sort of original array, so we sort on position. ASC, means next function will sort on label.
	print $form->selectarray("typent_id", $formcompany->typent_array(0), $typent_id, 0, 0, 0, '', 0, 0, 0, $sortparam);
	print '</td></tr>';

	// Address
	print '<tr class="create_thirdparty_block"><td valign="top">' . $langs->trans('Address') . '</td><td colspan="3"><textarea name="adresse" cols="40" rows="3" wrap="soft">';
	print $address;
	print '</textarea></td></tr>';

	// Zip
	print '<tr class="create_thirdparty_block"><td>' . $langs->trans('Zip') . '</td><td>';
	print $formcompany->select_ziptown($zip, 'zipcode', array (
		'town',
		'selectcountry_id',
		'departement_id'
	), 6);
	print '</td></tr>';
	// Town
	print '<tr class="create_thirdparty_block"><td>'. $langs->trans('Town') . '</td><td>';
	print $formcompany->select_ziptown($town, 'town', array (
		'zipcode',
		'selectcountry_id',
		'departement_id'
	));
	print '</td></tr>';
	// Country
	print '<tr class="create_thirdparty_block"><td>'. $langs->trans('Country') . '</td><td>';
	print img_picto('', 'globe-americas', 'class="paddingrightonly"');
	print $form->select_country($country_id, 'country_id', '', 0, 'minwidth300 widthcentpercentminusx');
	if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
	print '</td></tr>';

	// Infos participant
	print '<tr class="liste_titre"><td colspan="4"><strong>' . $langs->trans("AgfMailTypeContactTrainee") . '</strong></td>';

	print '<tr><td><span class="fieldrequired">' . $langs->trans("AgfCivilite") . '</span></td>';
	print '<td colspan="3">' . $formcompany->select_civility($civility_id) . '</td>';
	print '</tr>';

	print '<tr><td><span class="fieldrequired">' . $langs->trans("Firstname") . '</span></td>';
	print '<td colspan="3"><input name="prenom" class="flat" size="50" value="' . $firstname . '"></td></tr>';

	print '<tr><td><span class="fieldrequired">' . $langs->trans("Lastname") . '</span></td>';
	print '<td colspan="3"><input name="nom" class="flat" size="50" value="' . $name . '"></td></tr>';

	print '<tr id="tr_create_contact"><td>' . $langs->trans('CreateANewContactFromTraineeForm');
	print img_picto($langs->trans("CreateANewContactFromTraineeFormInfo"), 'help');
	print '</td>';
	print '<td colspan="3">';
	if ($create_contact > 0 || !empty($conf->global->AGF_NEW_TRAINEE_CREATE_CONTACT_DEFAULT)) {
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

	print '<tr id="tr_fonction"><td>' . $langs->trans("AgfFonction") . '</td>';
	print '<td colspan="3"><input name="fonction" class="flat" size="50" value="' . $fonction . '"></td></tr>';

	print '<tr><td>' . $langs->trans("Phone") . '</td>';
	print '<td colspan="3"><input name="tel1" class="flat" size="50" value="' . GETPOST('tel1', 'alpha') . '"></td></tr>';

	print '<tr id="tr_tel2"><td>' . $langs->trans("Mobile") . '</td>';
	print '<td colspan="3"><input name="tel2" class="flat" size="50" value="' . GETPOST('tel2', 'alpha') . '"></td></tr>';

	print '<tr><td>' . $langs->trans("Mail") . '</td>';
	print '<td colspan="3"><input name="mail" class="flat" size="50" value="' . GETPOST('mail', 'alpha') . '"></td></tr>';

	print '<tr id="tr_datebirth"><td>' . $langs->trans("DateToBirth") . '</td>';
	print '<td>';
	print $form->select_date('', 'datebirth', '', '', 1);
	print '</td></tr>';

	print '<tr id="tr_place_birth"><td>' . $langs->trans("AgfPlaceBirth") . '</td>';
	print '<td colspan="3"><input name="place_birth" class="flat" size="50" value=""></td></tr>';

	print '<tr id="tr_note"><td valign="top">' . $langs->trans("AgfNote") . '</td>';
	print '<td colspan="3"><textarea name="note" rows="3" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';

	include_once DOL_DOCUMENT_ROOT .'/cron/class/cronjob.class.php';
	$status=3; // 3 is not a status so we select all
	$filtercron = array(
		'jobtype' => 'method',
		'classesname' => 'agefodd/cron/cron.php',
		'objectname' => 'cron_agefodd',
		'module_name' => 'agefodd',
		'methodename' => 'sendAgendaToTrainee',
	);
	$cronJob = new Cronjob($db);
	$cronJob->fetch_all('DESC', 't.rowid',0, 0, $status, $filtercron);
	if(!empty($cronJob->lines))
	{
		print '<tr><td>' . $form->textwithtooltip( $langs->trans("AgfSendAgendaMail") ,$langs->trans("AgfSendAgendaMailHelp"),2,1,img_help(1,'')). '</td>';
		$statutarray=array('0' => $langs->trans("Yes"), '1' => $langs->trans("No"));
		$postDisable_auto_mail = GETPOST('disable_auto_mail', 'none');
		print '<td>' . $form->selectarray('disable_auto_mail',$statutarray,!empty($postDisable_auto_mail)?$postDisable_auto_mail:'') . '</td></tr>';
	}

	if (! empty($extrafields->attribute_label)) {
		print $agf->showOptionals($extrafields, 'edit');
	}


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
	if ($resql<0) {
		setEventMessages(null,$agf->errors,'errors');
	}
	$sessions = array ();
	foreach ( $agf->lines as $line ) {
		$sessions[$line->rowid] = $line->ref_interne . ' - ' . $line->intitule . ' - ' . dol_print_date($line->dated, 'daytext');
	}

	print '<tr class="agelfoddline">';
	print '<td>' . $langs->trans('AgfSelectAgefoddSession') . '</td>';
	print '<td colspan="3">';
	print $form->selectarray('session_id', $sessions, $session_id, 1);
	print '</td>';
	print '</tr>';

	if (! empty($conf->global->AGF_USE_STAGIAIRE_TYPE)) {
		// Public stagiaire
		if (empty($stagiaire_type)) {
			$stagiaire_type = $conf->global->AGF_DEFAULT_STAGIAIRE_TYPE;
		}
		print '<tr class="agelfoddline"><td>' . $langs->trans("AgfPublicTrainee") . '</td><td colspan="3">';
		print $formAgefodd->select_type_stagiaire($stagiaire_type, 'stagiaire_type', 'active=1', 1);
		print '</td></tr>';
		print '<tr class="agelfoddline"><td>' . $langs->trans('AgfTraineeSocDocUse') . '</td><td colspan="3">';
		if (!empty($conf->global->AGEFODD_USE_SELECT_WITH_AJAX)) print $form->select_company(0, 'fk_soc_link', '', 'SelectThirdParty', 1);
		else print $form->select_thirdparty_list(0, 'fk_soc_link', '', 'SelectThirdParty', 1, 0);
		print '</td></tr>';
		print '<tr class="agelfoddline"><td>' . $langs->trans('AgfTypeRequester') . '</td><td colspan="3">';
		if (!empty($conf->global->AGEFODD_USE_SELECT_WITH_AJAX)) print $form->select_company(0, 'fk_soc_requester', '', 'SelectThirdParty', 1);
		else print $form->select_thirdparty_list(0, 'fk_soc_requester', '', 'SelectThirdParty', 1, 0);
		print '</td></tr>';
		if (empty($conf->global->AGF_SESSION_TRAINEE_STATUS_AUTO)) {
			print '<tr class="agelfoddline"><td>' . $langs->trans('Status') . '</td><td colspan="3">';
			print $formAgefodd->select_stagiaire_session_status('status_in_session', 0);
			print '</td></tr>';
		}
	}

	print '</table>';
	print '</div>';

	print '<div class="tabsAction">';
	print '<table style="noborder" align="right">';
	print '<tr><td align="center">';
	print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
	print '<input type="submit" class="butAction" name="saveandstay" value="' . $langs->trans("AgfSaveAndStay") . '"> &nbsp; ';
	print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
	print '</td></tr>';
	print '</table>';
	print '</div>';

	print '</form>';
}
else
{
	// Affichage de la fiche "stagiaire"
	if ($id) {
		$agf = new Agefodd_stagiaire($db);
		$result = $agf->fetch($id);

		$head = trainee_prepare_head($agf);

		if ($result > 0) {
			llxHeader('', $langs->trans('AgfStagiaireDetail'));
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

					if (!empty($conf->global->AGEFODD_USE_SELECT_WITH_AJAX)) print $form->select_company($agf->socid, 'societe', '(s.client IN (1,3,2))', 'SelectThirdParty', 1);
					else print $form->select_thirdparty_list($agf->socid, 'societe', '(s.client IN (1,3,2))', 'SelectThirdParty', 1);

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


				include_once DOL_DOCUMENT_ROOT .'/cron/class/cronjob.class.php';
				$status=3; // 3 is not a status so we select all
				$filter = array(
					'jobtype' => 'method',
					'classesname' => 'agefodd/cron/cron.php',
					'objectname' => 'cron_agefodd',
					'module_name' => 'agefodd',
					'methodename' => 'sendAgendaToTrainee',
				);
				$cronJob = new Cronjob($db);
				$cronJob->fetch_all('DESC', 't.rowid',0, 0, $status, $filter);

				if(!empty($cronJob->lines))
				{
					print '<tr><td>' . $form->textwithtooltip( $langs->trans("AgfSendAgendaMail") ,$langs->trans("AgfSendAgendaMailHelp"),2,1,img_help(1,'')). '</td>';
					$statutarray=array('0' => $langs->trans("Yes"), '1' => $langs->trans("No"));
					$postDisable_auto_mail = GETPOST('disable_auto_mail', 'none');
					print '<td>' . $form->selectarray('disable_auto_mail',$statutarray,!empty($postDisable_auto_mail)?$postDisable_auto_mail:$agf->disable_auto_mail) . '</td></tr>';
				}


				if (! empty($extrafields->attribute_label)) {
					print $agf->showOptionals($extrafields, 'edit');
				}

				print '</table>';
				print '</div>';

				print '<div class="tabsAction">';
				print '<table style="noborder" align="right">';
				print '<tr><td align="center">';
				print '<input type="submit" class="butAction" name="save" value="' . $langs->trans("Save") . '"> &nbsp; ';
				print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
				if (! empty($agf->fk_socpeople)) {
					print '<a class="butAction" href="' . dol_buildpath('/contact/card.php', 1) . '?id=' . $agf->fk_socpeople . '&action=edit">' . $langs->trans('AgfModifierFicheContact') . '</a>';
				}
				print '</td></tr>';
				print '</table>';
				print '</div>';


				print '</form>';

				print '</div>' . "\n";
			} else {
				// Display in "view" mode

				dol_agefodd_banner_tab($agf, 'id');
				print '<div class="underbanner clearboth"></div>';

				/*
				* Confirmation de la suppression
				*/
				if ($action == 'delete') {
					print $form->formconfirm($_SERVER['PHP_SELF'] . "?id=" . $id, $langs->trans("AgfDeleteOps"), $langs->trans("AgfConfirmDeleteTrainee"), "confirm_delete", '', '', 1);
				}

				print '<table class="border" width="100%">';

				print '<tr><td>' . $langs->trans("AgfCivilite") . '</td>';

				$contact_static = new Contact($db);
				$contact_static->civility_id = $agf->civilite;
				$contact_static->civility_code = $agf->civilite;

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

				include_once DOL_DOCUMENT_ROOT .'/cron/class/cronjob.class.php';
				$status=3; // 3 is not a status so we select all
				$filter = array(
					'jobtype' => 'method',
					'classesname' => 'agefodd/cron/cron.php',
					'objectname' => 'cron_agefodd',
					'module_name' => 'agefodd',
					'methodename' => 'sendAgendaToTrainee',
				);
				$cronJob = new Cronjob($db);
				$cronJob->fetch_all('DESC', 't.rowid',0, 0, $status, $filter);

				if(!empty($cronJob->lines))
				{
					print '<tr><td>' . $form->textwithtooltip( $langs->trans("AgfSendAgendaMail") ,$langs->trans("AgfSendAgendaMailHelp"),2,1,img_help(1,'')). '</td>';
					print '<td>' . (!empty($agf->disable_auto_mail)?$langs->trans("No"):$langs->trans("Yes")) . '</td></tr>';
				}

				if (! empty($conf->global->AGF_USE_REAL_HOURS)) {
					require_once ('../class/agefodd_session_stagiaire_heures.class.php');
					$agfssh = new Agefoddsessionstagiaireheures($db);
					print '<tr><td>' . $langs->trans('AgfTraineeHours') . '</td>';
					print '<td>' . $agfssh->heures_stagiaire_totales($agf->id) . ' h</td></tr>';
				}

				if (! empty($extrafields->attribute_label)) {
					print $agf->showOptionals($extrafields);
				}

				print "</table>";

				print '</div>';
			}

			/*
			 * Barre d'actions
			 *
			 */

			print '<div class="tabsAction">';

			if ($action != 'create' && $action != 'edit' && $action != 'nfcontact' && $action != 'presend') {

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



		} else {
			setEventMessage($agf->error, 'errors');
			Header("Location: list.php");
			exit();
		}
	}
}
$title = ($action == 'nfcontact' || $action == 'create' ? $langs->trans("AgfMenuActStagiaireNew") : $langs->trans("AgfStagiaireDetail"));


/*
 * Action create
*/

$parameters = array();
$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $agf, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</div>';


if ($id) {

	// Presend form
	$modelmail = 'agf_trainee';
	$defaulttopic = 'AgfSendEmailTrainee';
	$diroutput = $conf->agefodd->multidir_output[$agf->entity];
	$trackid = 'agftrainee' . $agf->id;

	if(!empty($agf->fk_socpeople) && (!isset($_POST['receiver']) || empty($_POST['receiver']))){
		$_POST['receiver']= array($agf->fk_socpeople); // Pas le choix
	}

	include __DIR__ . '/../tpl/card_presend.tpl.php';
}

llxFooter();
$db->close();
