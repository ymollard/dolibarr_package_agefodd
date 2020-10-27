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
 * \file agefodd/site/card.php
 * \ingroup agefodd
 * \brief card of location
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once '../class/agefodd_place.class.php';
require_once '../lib/agefodd.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once '../class/html.formagefodd.class.php';

// Security check
if (! $user->rights->agefodd->agefodd_place->lire)
	accessforbidden();

$langs->load('agefodd@agefodd');
$langs->load('companies');

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$arch = GETPOST('arch', 'int');
$societe = GETPOST('societe', 'int');

$url_return = GETPOST('url_return', 'alpha');

$same_adress_customer = GETPOST('same_adress_customer', 'int');

/*
 * Actions delete
 */
if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->agefodd_place->creer) {
	$agf = new Agefodd_place($db);
	$agf->id = $id;
	$result = $agf->remove($user);

	if ($result > 0) {
		Header("Location: list.php");
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

/*
 * Actions archive/active
 */
if ($action == 'arch_confirm_delete' && $user->rights->agefodd->agefodd_place->creer) {
	if ($confirm == "yes") {
		$agf = new Agefodd_place($db);

		$result = $agf->fetch($id);

		$agf->archive = $arch;
		$result = $agf->update($user);

		if ($result > 0) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	} else {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	}
}

/*
 * Action update (Location)
 */
if ($action == 'update' && $user->rights->agefodd->agefodd_place->creer) {

	$error = 0;

	if (! $_POST["cancel"] && ! $_POST["importadress"]) {
		$agf = new Agefodd_place($db);

		// thirdparty is not required (uncomment if needed)

		if ($societe < 1) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentities('Company')), 'errors');
			$error ++;
		}

		$label = GETPOST('ref_interne', 'alpha');
		if (empty($label)) {
		    setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentities('AgfSessPlaceCode')), 'errors');
			$error ++;
		}

		$result = $agf->fetch($id);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
			$error ++;
		}

		if (empty($error)) {
			$agf->ref_interne = $label;
			$agf->adresse = GETPOST('adresse', 'alpha');
			$agf->cp = GETPOST('zipcode', 'alpha');
			$agf->ville = GETPOST('town', 'alpha');
			$agf->fk_pays = GETPOST('country_id', 'int');
			$agf->tel = GETPOST('phone', 'alpha');
			$agf->fk_societe = $societe;
			$agf->fk_socpeople = GETPOST('contact', 'int');
			$agf->timeschedule = GETPOST('timeschedule', 'alpha');
			$agf->control_occupation = GETPOST('control_occupation', 'int');
			$agf->notes = GETPOST('notes', 'none');
			$agf->nb_place = GETPOST('nb_place', 'none');
			if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
				$agf->acces_site = dol_htmlcleanlastbr(GETPOST('acces_site', 'none'));
				$agf->note1 = dol_htmlcleanlastbr(GETPOST('note1', 'none'));
			} else {
				$agf->acces_site = GETPOST('acces_site', 'none');
				$agf->note1 = GETPOST('note1', 'none');
			}
			$result = $agf->update($user);

			if ($result > 0) {
				Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
				exit();
			} else {
				setEventMessage($agf->error, 'errors');
				$action = 'edit';
			}
		} else {
		    Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
		    exit();
		}
	} elseif (! $_POST["cancel"] && $_POST["importadress"]) {

		$agf = new Agefodd_place($db);

		$result = $agf->fetch($id);
		$result = $agf->import_customer_adress($user);

		if ($result > 0) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	} else {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	}
}

/*
 * Action create (Location)
 */

if ($action == 'create_confirm' && $user->rights->agefodd->agefodd_place->creer) {

	$error = 0;

	if (! $_POST["cancel"]) {
		$agf = new Agefodd_place($db);
		if ($societe < 1) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentities('Company')), 'errors');
			$error ++;
		}

		$label = GETPOST('ref_interne', 'alpha');
		if (empty($label)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentities('AgfSessPlaceCode')), 'errors');
			$error ++;
		}

		if (empty($error)) {

			$agf->ref_interne = $label;
			$agf->fk_societe = $societe;
			$agf->fk_socpeople = GETPOST('contact', 'int');
			$agf->timeschedule = GETPOST('timeschedule', 'alpha');
			$agf->control_occupation = GETPOST('control_occupation', 'int');
			$agf->nb_place = GETPOST('nb_place', 'int');
			$agf->notes = GETPOST('notes', 'alpha');
			if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
				$agf->acces_site = dol_htmlcleanlastbr(GETPOST('acces_site', 'none'));
				$agf->note1 = dol_htmlcleanlastbr(GETPOST('note1', 'none'));
			} else {
				$agf->acces_site = GETPOST('acces_site', 'none');
				$agf->note1 = GETPOST('note1', 'none');
			}
			if ($same_adress_customer == - 1) {
				$agf->adresse = GETPOST('adresse', 'alpha');
				$agf->cp = GETPOST('zipcode', 'alpha');
				$agf->ville = GETPOST('town', 'alpha');
				$agf->fk_pays = GETPOST('country_id', 'int');
				$agf->tel = GETPOST('phone', 'alpha');
			}
			$result = $agf->create($user);
			$idplace = $result;

			if ($result > 0) {
				if ($same_adress_customer == 1) {
					$result = $agf->fetch($idplace);
					$result = $agf->import_customer_adress($user);
					if ($result < 0) {
						setEventMessage($agf->error, 'errors');
						$action='create';
						$error ++;
					}
				}

				if (empty($error)) {
					if ($url_return) {
						if (preg_match('/session\/card.php\?action=create$/', $url_return)) {
							$url_return .= '&place=' . $idplace;
							Header("Location: " . $url_return);
						} else {
							Header("Location: " . $url_return);
						}
					} else {
						Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $idplace);
					}
					exit();
				}
			} else {
				setEventMessage($agf->error, 'errors');
				$action='create';
			}
		} else {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=create");
			exit();
		}
	} else {
	    Header("Location: list.php");
	    exit();
	}
}

/*
 * View
 */
$title = ($action == 'create' ? $langs->trans("AgfCreatePlace") : $langs->trans("AgfSessPlace"));
llxHeader('', $title);

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

/*
 * Action create
 */
if ($action == 'create' && $user->rights->agefodd->agefodd_place->creer) {

	if ($conf->use_javascript_ajax) {
		print "\n" . '<script type="text/javascript">
		$(document).ready(function () {

			$(".specific_adress").hide();

			$("input[type=radio][name=same_adress_customer]").change(function() {
				if($(this).val()==1) {
					$(".specific_adress").hide();
				}else {
					$(".specific_adress").show();
				}
			});
		});
		';
		print "\n" . "</script>\n";
	}

	$formcompany = new FormCompany($db);
	print_fiche_titre($langs->trans("AgfCreatePlace"));

	print '<form name="create" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
	print '<input type="hidden" name="action" value="create_confirm">' . "\n";

	print '<input type="hidden" name="url_return" value="' . $url_return . '">' . "\n";

	print '<table class="border" width="100%">' . "\n";

	print '<tr><td width="20%"><span class="fieldrequired">' . $langs->trans("AgfSessPlaceCode") . '</span></td>';
	print '<td><input name="ref_interne" class="flat" size="50" value=""></td></tr>';

	print '<tr><td><span class="fieldrequired">' . $langs->trans("Company") . '</span></td>';
	$events = array ();
	$events[] = array (
			'method' => 'getContacts',
			'url' => dol_buildpath('/core/ajax/contacts.php', 1),
			'htmlname' => 'contact',
			'showempty' => '1',
			'params' => array (
					'add-customer-contact' => 'disabled'
			)
	);
	print '<td>' . $form->select_thirdparty_list('', 'societe', '((s.client IN (1,2,3)) OR (s.fournisseur=1))', 'SelectThirdParty', 1, 0, $events) . '</td></tr>';

	print '<tr><td>' . $langs->trans("Contact") . '</td>';
	print '<td>';
	if (! empty($societe)) {
		$formAgefodd->select_contacts_custom($societe, '', 'contact', 1, '', '', 1, '', 1);
	} else {
		$formAgefodd->select_contacts_custom(0, '', 'contact', 1, '', 1000, 1, '', 1);
	}
	print '</td></tr>';

	print '<tr><td>' . $langs->trans('AgfImportCustomerAdress') . '</td><td>';
	print '<input type="radio" id="same_adress_customer_yes" name="same_adress_customer" value="1" checked="checked"/> <label for="same_adress_customer_yes">' . $langs->trans('Yes') . '</label>';
	print '<input type="radio" id="same_adress_customer_no" name="same_adress_customer" value="-1"/> <label for="same_adress_customer_no">' . $langs->trans('no') . '</label>';
	print '</td></tr>';

	print '<tr class="specific_adress"><td>' . $langs->trans("Address") . '</td>';
	print '<td><input name="adresse" class="flat" size="50" value="' . GETPOST('adresse', 'alpha') . '"></td></tr>';

	print '<tr class="specific_adress"><td>' . $langs->trans('Zip') . '</td><td>';
	print $formcompany->select_ziptown(GETPOST('zipcode', 'alpha'), 'zipcode', array (
			'town',
			'selectcountry_id'
	), 6) . '</tr>';
	print '<tr class="specific_adress"><td>' . $langs->trans('Town') . '</td><td>';
	print $formcompany->select_ziptown(GETPOST('town', 'alpha'), 'town', array (
			'zipcode',
			'selectcountry_id'
	)) . '</td></tr>';

	print '<tr class="specific_adress"><td>' . $langs->trans("Country") . '</td>';
	print '<td>' . $form->select_country(GETPOST('country_id', 'int'), 'country_id') . '</td></tr>';

	print '<tr class="specific_adress"><td>' . $langs->trans("Phone") . '</td>';
	print '<td><input name="phone" class="flat" size="50" value="' . GETPOST('phone', 'alpha') . '"></td></tr>';

	print '<tr><td valign="top">' . $langs->trans("AgfNote") . '</td>';
	print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';
	print '<tr><td valign="top">' . $langs->trans("AgfNbPlace") . '</td>';
	print '<td><input name="nb_place"  class="flat" style="width:360px;"></input></td></tr>';

	print '<tr><td valign="top">' . $langs->trans("AgfTimeSchedule") . '</td>';
	print '<td><input name="timeschedule" class="flat" size="50" value="' . GETPOST('timeschedule', 'alpha') . '"></td></tr>';

	$timeschedule=GETPOST('timeschedule', 'alpha');
	if ($timeschedule == 0 || empty($timeschedule)) {
		$checked = '';
	} else {
		$checked = 'checked="checked"';
	}
	print '<tr><td valign="top">' . $langs->trans("AgfOccupationControl") . '</td>';
	print '<td><input name="control_occupation" type="checkbox" ' . $checked . ' class="flat" value="1"></td></tr>';


	print '<tr>';
	print '<td valign="top">' . $langs->trans("AgfAccesSite") . '</td><td>';
	$doleditor = new DolEditor('acces_site', GETPOST('acces_site', 'none'), '', 160, 'dolibarr_notes', 'In', true, true, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
	$doleditor->Create();
	print "</td></tr>";

	print '<tr>';
	print '<td valign="top">' . $langs->trans("AgfPlaceNote1") . '</td><td>';
	$doleditor = new DolEditor('note1', GETPOST('note1', 'none'), '', 160, 'dolibarr_notes', 'In', true, true, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
	$doleditor->Create();
	print "</td></tr>";

	print '</table>';
	print '</div>';

	print '<table style=noborder align="right">';
	print '<tr><td align="center" colspan=2>';
	print '<input type="submit" name="importadress" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
	print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
	print '</td></tr>';
	print '</table>';
	print '</form>';
} else {
	// Card
	if ($id) {
		$agf = new Agefodd_place($db);
		$result = $agf->fetch($id);
		$result2 = $agf->fetch_thirdparty();

		if ($result > 0) {
			$head = site_prepare_head($agf);

			dol_fiche_head($head, 'card', $langs->trans("AgfSessPlace"), 0, 'address');

			// Card in edit mode
			if ($action == 'edit') {

				$formcompany = new FormCompany($db);

				print '<form name="update" action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n";
				print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
				print '<input type="hidden" name="action" value="update">' . "\n";
				print '<input type="hidden" name="id" value="' . $id . '">' . "\n";

				print '<table class="border" width="100%">' . "\n";
				print '<tr><td width="20%">' . $langs->trans("Id") . '</td>';
				print '<td>' . $agf->id . '</td></tr>';

				print '<tr><td class="fieldrequired">' . $langs->trans("AgfSessPlaceCode") . '</td>';
				print '<td><input name="ref_interne" class="flat" size="50" value="' . $agf->ref_interne . '"></td></tr>';

				print '<tr><td>' . $langs->trans("Company") . '</td>';
				$events = array ();
				$events[] = array (
						'method' => 'getContacts',
						'url' => dol_buildpath('/core/ajax/contacts.php', 1),
						'htmlname' => 'contact',
						'showempty' => '1',
						'params' => array (
								'add-customer-contact' => 'disabled'
						)
				);
				print '<td>' . $form->select_thirdparty_list($agf->socid, 'societe', '((s.client IN (1,2,3)) OR (s.fournisseur=1))', 'SelectThirdParty', 1, 0,$events) . '</td></tr>';

				print '<tr><td>' . $langs->trans("Contact") . '</td>';
				print '<td>';
				$formAgefodd->select_contacts_custom($agf->socid, $agf->fk_socpeople, 'contact', 1, '', '', 1, '', 1);
				print '</td></tr>';

				print '<tr><td>' . $langs->trans("Address") . '</td>';
				print '<td><input name="adresse" class="flat" size="50" value="' . $agf->adresse . '"></td></tr>';

				print '<tr><td>' . $langs->trans('Zip') . '</td><td>';
				print $formcompany->select_ziptown($agf->cp, 'zipcode', array (
						'town',
						'selectcountry_id'
				), 6) . '</tr>';
				print '<tr></td><td>' . $langs->trans('Town') . '</td><td>';
				print $formcompany->select_ziptown($agf->ville, 'town', array (
						'zipcode',
						'selectcountry_id'
				)) . '</tr>';

				print '<tr><td>' . $langs->trans("Country") . '</td>';
				print '<td>' . $form->select_country($agf->fk_pays, 'country_id') . '</td></tr>';

				print '<tr><td>' . $langs->trans("Phone") . '</td>';
				print '<td><input name="phone" class="flat" size="50" value="' . $agf->tel . '"></td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfNote") . '</td>';
				print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;">' . $agf->notes . '</textarea></td></tr>';
				print '<tr><td valign="top">' . $langs->trans("AgfNbPlace") . '</td>';
				print '<td><input name="nb_place"  class="flat" style="width:360px;" value="'. $agf->nb_place .'"></input></td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfTimeSchedule") . '</td>';
				print '<td><input name="timeschedule" class="flat" size="50" value="' . $agf->timeschedule . '"></td></tr>';

				if ($agf->control_occupation == 0 || empty($agf->control_occupation)) {
					$checked = '';
				} else {
					$checked = 'checked="checked"';
				}
				print '<tr><td valign="top">' . $langs->trans("AgfOccupationControl") . '</td>';
				print '<td><input name="control_occupation" type="checkbox" ' . $checked . ' class="flat" value="1"></td></tr>';

				print '<tr>';
				print '<td valign="top">' . $langs->trans("AgfAccesSite") . '</td><td>';
				$doleditor = new DolEditor('acces_site', $agf->acces_site, '', 160, 'dolibarr_notes', 'In', true, true, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
				$doleditor->Create();
				print "</td></tr>";

				print '<tr>';
				print '<td valign="top">' . $langs->trans("AgfPlaceNote1") . '</td><td>';
				$doleditor = new DolEditor('note1', $agf->note1, '', 160, 'dolibarr_notes', 'In', true, true, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
				$doleditor->Create();
				print "</td></tr>";

				print '</table>';
				print '</div>';
				print '<table style=noborder align="right">';
				print '<tr><td align="center" colspan=2>';
				print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
				print '<input type="submit" name="importadress" class="butAction" value="' . $langs->trans("AgfImportCustomerAdress") . '"> &nbsp; ';
				print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
				print '</td></tr>';
				print '</table>';
				print '</form>';

				print '</div>' . "\n";
			} else {
				// Display View mode

			    dol_agefodd_banner_tab($agf, 'id');
			    print '<div class="underbanner clearboth"></div>';

				/*
				 * Confirm delete
				 */
				if ($action == 'delete') {
					print $form->formconfirm($_SERVER['PHP_SELF'] . "?id=" . $id, $langs->trans("AgfDeletePlace"), $langs->trans("AgfConfirmDeletePlace"), "confirm_delete", '', '', 1);
				}
				/*
				 * Confirm archive
				 */
				if ($action == 'archive' || $action == 'active') {
					if ($action == 'archive')
						$value = 1;
					if ($action == 'active')
						$value = 0;

					print $form->formconfirm($_SERVER['PHP_SELF'] . "?arch=" . $value . "&id=" . $id, $langs->trans("AgfFormationArchiveChange"), $langs->trans("AgfConfirmArchiveChange"), "arch_confirm_delete", '', '', 1);

				}

				print '<table class="border" width="100%">';

				print '<tr><td valign="top">' . $langs->trans("Company") . '</td><td>';
				if ($agf->socid) {
					$soc = new Societe($db);
					$soc->fetch($agf->socid);
					print $soc->getNomUrl(1);
				} else {
					print '&nbsp;';
				}
				print '</tr>';

				print '<tr><td valign="top">' . $langs->trans("Contact") . '</td><td>';
				$contact = new Contact($db);
				$contact->fetch($agf->fk_socpeople);
				print $contact->getNomUrl();
				print '</tr>';

				print '<tr><td rowspan=3 valign="top">' . $langs->trans("Address") . '</td>';
				print '<td>' . $agf->adresse . '</td></tr>';

				print '<tr>';
				print '<td>' . $agf->cp . ' - ' . $agf->ville . '</td></tr>';

				print '<tr>';
				print '<td>';
				$img = picto_from_langcode($agf->country_code);
				if (method_exists($agf->thirdparty, 'isInEEC') && $agf->thirdparty->isInEEC())
					print $form->textwithpicto(($img ? $img . ' ' : '') . $agf->country, $langs->trans("CountryIsInEEC"), 1, 0);
				else
					print ($img ? $img . ' ' : '') . $agf->country;
				print '</td></tr>';

				print '</td></tr>';

				print '<tr><td>' . $langs->trans("Phone") . '</td>';
				print '<td>' . dol_print_phone($agf->tel) . '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfNotes") . '</td>';
				print '<td>' . nl2br($agf->notes) . '</td></tr>';
				print '<tr><td valign="top">' . $langs->trans("AgfNbPlace") . '</td>';
				print '<td>' . ($agf->nb_place) . '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfTimeSchedule") . '</td>';
				print '<td>' . $agf->timeschedule . '</td></tr>';

				if ($agf->control_occupation == 0 || empty($agf->control_occupation)) {
					$checked = '';
				} else {
					$checked = 'checked="checked"';
				}
				print '<tr><td valign="top">' . $langs->trans("AgfOccupationControl") . '</td>';
				print '<td><input name="control_occupation" type="checkbox" disabled="disabled" readonly="readonly" ' . $checked . ' class="flat" value="1"></td></tr>';

				if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
					$acces_site = $agf->acces_site;
				} else {
					$acces_site = stripslashes(nl2br($agf->acces_site));
				}

				print '<tr><td valign="top">' . $langs->trans("AgfAccesSite") . '</td>';
				print '<td>' . $acces_site . '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfPlaceNote1") . '</td>';

				if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
					$note1 = $agf->note1;
				} else {
					$note1 = stripslashes(nl2br($agf->note1));
				}

				print '<td>' . $note1 . '</td></tr>';

				print "</table>";

				print '</div>';
			}
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

if ($action != 'create' && $action != 'edit' && $action != 'nfcontact') {
	if ($user->rights->agefodd->agefodd_place->creer) {
		print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&id=' . $id . '">' . $langs->trans('Modify') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';
	}
	if ($user->rights->agefodd->agefodd_place->creer) {
		print '<a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?action=delete&id=' . $id . '">' . $langs->trans('Delete') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Delete') . '</a>';
	}
	if ($user->rights->agefodd->agefodd_place->creer) {
		if ($agf->archive == 0) {
			print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=archive&id=' . $id . '">' . $langs->trans('AgfArchiver') . '</a>';
		} else {
			print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=active&id=' . $id . '">' . $langs->trans('AgfActiver') . '</a>';
		}
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfArchiver') . '/' . $langs->trans('AgfActiver') . '</a>';
	}
}

print '</div>';

llxFooter();
$db->close();
