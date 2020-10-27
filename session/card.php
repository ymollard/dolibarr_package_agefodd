<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2014	Florian Henry	<florian.henry@open-concept.pro>
 * Copyright (C) 2012		JF FERRY	<jfefe@aternatik.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file agefodd/session/card.php
 * \ingroup agefodd
 * \brief card of session
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_sessadm.class.php');
require_once ('../class/agefodd_session_admlevel.class.php');
require_once ('../class/html.formagefodd.class.php');
require_once ('../class/agefodd_session_calendrier.class.php');
require_once ('../class/agefodd_calendrier.class.php');
require_once ('../class/agefodd_session_formateur.class.php');
require_once ('../class/agefodd_session_stagiaire.class.php');
require_once ('../class/agefodd_session_element.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once ('../class/agefodd_formation_catalogue.class.php');
require_once ('../class/agefodd_opca.class.php');

// Security check
if (! $user->rights->agefodd->lire) {
	accessforbidden();
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array(
		'agefoddsessioncard'
		,'globalcard'
));

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$arch = GETPOST('arch', 'int');
$anchor = GETPOST('anchor', 'none');
$agf = new Agsession($db);
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($agf->table_element);

$parameters = array(
		'id' => $id,
		'action' => $action,
		'confirm' => $confirm,
		'arch' => $arch
);
//var_dump($agf->context->action); exit;
$reshook = $hookmanager->executeHooks('doActions', $parameters, $agf, $action);// Note that $action and $object may have been modified by some hooks
if ($reshook < 0)
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if (empty($reshook)){

/*
 * Actions delete session
 */

$period_update = GETPOST('period_update', 'int');
$cancel = GETPOST('cancel', 'none');
$saveandclose = GETPOST('saveandclose', 'none');
$modperiod = GETPOST('modperiod', 'int');
$period_remove = GETPOST('period_remove', 'int');
$period_remove_all = GETPOST('period_remove_all', 'int');

if ($action == 'confirm_validateregistrants' && $confirm == "yes" && $user->rights->agefodd->creer) {


	//On récupère tous les formateurs de la session courante
	$formateurs = new Agefodd_session_formateur($db);
	$resultsf = $formateurs->fetch_formateur_per_session($id);

	//On récupère tous les stagiaires de la session courante
	$stagiaires = new Agefodd_session_stagiaire($db);
	$resultss = $stagiaires->fetch_stagiaire_per_session($id);

//	var_dump(!empty($resultsf) && !empty($resultss)); exit;

	if(!empty($resultsf) && !empty($resultss)) {		//On confirme tous les inscrits seulement si il y a au moins un stagiaire et un formateur

		foreach ($formateurs->lines as $formateurlines) {
			$sessionformateur = new Agefodd_session_formateur($db);
			$sessionformateur->fetch($formateurlines->opsid);
			$sessionformateur->trainer_status = '2';	//Statut "2" correspond à "Confirmé"
			$sessionformateur->opsid = $formateurlines->opsid;
			$sessionformateur->update($user);
		}

		foreach ($stagiaires->lines as $stagiairelines) {
			$sessionstagiaire = new Agefodd_session_stagiaire($db);
			$sessionstagiaire->fetch($stagiairelines->stagerowid);
			$sessionstagiaire->status_in_session = '2';		//Statut "2" correspond à "Confirmé"
			$sessionstagiaire->update($user);
		}

		seteventMessage($langs->trans('SessionRegistrantsConfirm'));
		Header("Location: card.php?id=" . $id);
		exit();

	} else {	//Sinon on affiche les erreurs

		if(empty($resultss) && !empty($resultsf)){
			setEventMessage($langs->trans('AgfErrorSessionNoTrainee'), 'errors');
		}
		elseif(!empty($resultss) && empty($resultsf)){
			setEventMessage($langs->trans('AgfErrorSessionNoTrainer'), 'errors');
		} else {
			setEventMessage($langs->trans('AgfErrorSessionNoRegistrant'), 'errors');
		}
	}
}

if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->creer) {
	$agf = new Agsession($db);
	$result = $agf->remove($id);

	if ($result > 0) {
		Header("Location: list.php");
		exit();
	} else {
		setEventMessage($langs->trans("AgfDeleteErr") . ':' . $agf->error, 'errors');
	}
}

/*
 * Actions remove thirdparty
 */

if ($action == 'remove_cust' && $user->rights->agefodd->modifier) {

	$agf = new Agsession($db);
	$result = $agf->fetch($id);
	unset($agf->fk_soc);
	$agf->contactid = 0;
	$agf->sourcecontactid = 0;
	$result = $agf->update($user);

	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

if ($action == 'remove_requester' && $user->rights->agefodd->modifier) {

	$agf = new Agsession($db);
	$result = $agf->fetch($id);
	unset($agf->fk_soc_requester);
	$result = $agf->update($user);

	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

if ($action == 'remove_contact' && $user->rights->agefodd->modifier) {

	$agf = new Agsession($db);
	$result = $agf->fetch($id);
	$agf->contactid = 0;
	$agf->sourcecontactid = 0;
	$result = $agf->update($user);

	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

if ($action == 'remove_contactrequester' && $user->rights->agefodd->modifier) {

	$agf = new Agsession($db);
	$result = $agf->fetch($id);
	unset($agf->fk_socpeople_requester);
	$result = $agf->update($user);

	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

if ($action == 'remove_contactpresta' && $user->rights->agefodd->modifier) {

	$agf = new Agsession($db);
	$result = $agf->fetch($id);
	unset($agf->fk_socpeople_presta);
	$result = $agf->update($user);

	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

if ($action == 'remove_employer' && $user->rights->agefodd->modifier) {

	$agf = new Agsession($db);
	$result = $agf->fetch($id);
	unset($agf->fk_soc_employer);
	$result = $agf->update($user);

	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

/*
 * Actions archive/active
 */

if ($action == 'arch_confirm_delete' && $user->rights->agefodd->creer) {
	if ($confirm == "yes") {
		$agf = new Agsession($db);

		$result = $agf->fetch($id);
		$arch = GETPOST("arch", 'int');

		if (empty($arch)) {
			$agf->status = 1;
			if (!empty($agf->status_before_archive)) $agf->status = $agf->status_before_archive;
		} else {
			$agf->status_before_archive = $agf->status;
			$agf->status = 4;
		}

		$result = $agf->updateArchive($user);

		if ($result > 0) {
			// If update are OK we delete related files
//			foreach ( glob($conf->agefodd->dir_output . "/*_" . $id . "_*.pdf") as $filename ) {
//				if (is_file($filename))
//					unlink("$filename");
//			}

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
 * Action update (fiche session)
 */
if ($action == 'update' && ($user->rights->agefodd->creer || $user->rights->agefodd->modifier) && empty($period_update)) {
	if (empty($cancel)) {
		$error = 0;

		$agf = new Agsession($db);
		$result = $agf->fetch($id);

		$fk_session_place = GETPOST('place', 'int');
		if (($fk_session_place == - 1) || (empty($fk_session_place))) {
			setEventMessage($langs->trans('AgfPlaceMandatory'), 'errors');
			$error ++;
		}

        $TMessage = $agf->checkOtherSessionSamePlaceDate();
        if (!empty($agf->error)) setEventMessage($agf->error, 'errors');
        elseif (!empty($TMessage)) setEventMessage($TMessage, 'warnings');

		// If customer is selected contact is required
		$custid = GETPOST('fk_soc', 'int');
		$contactclientid = GETPOST('contact', 'int');
		if (empty($conf->global->AGF_CONTACT_NOT_MANDATORY_ON_SESSION)) {
			if (((($custid != - 1) && (! empty($custid))) && (($contactclientid == - 1) || (empty($contactclientid))))) {
				$error ++;
				setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AgfSessionContact")), 'errors');
			}
		}
		$training_id = GETPOST('formation', 'int');
		if (($training_id == - 1) || (empty($training_id))) {
			$error ++;
			setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AgfFormIntitule")), 'errors');
		}

		if ($agf->fk_formation_catalogue != $training_id) {
			$training_session = new Formation($db);
			$result = $training_session->fetch($training_id);
			if ($result > 0) {
				$agf->nb_subscribe_min = $training_session->nb_subscribe_min;
				$agf->duree_session = $training_session->duree;
				$agf->intitule_custo = $training_session->intitule;
				$agf->fk_product = $training_session->fk_product;
			}
		} else {
			$agf->nb_subscribe_min = GETPOST('nbmintarget', 'int');
			$agf->fk_product = GETPOST('productid', 'int');
			$agf->duree_session = GETPOST('duree_session', 'int');
			$agf->intitule_custo = GETPOST('intitule_custo', 'alpha');
		}

		$agf->fk_formation_catalogue = $training_id;

		$agf->dated = dol_mktime(12, 0, 0, GETPOST('dadmonth', 'int'), GETPOST('dadday', 'int'), GETPOST('dadyear', 'int'));
		$agf->datef = dol_mktime(12, 0, 0, GETPOST('dafmonth', 'int'), GETPOST('dafday', 'int'), GETPOST('dafyear', 'int'));
		if ($agf->dated > $agf->datef) {
			$error ++;
			setEventMessage($langs->trans('AgfSessionDateErrors'), 'errors');
		}

		$agf->fk_session_place = $fk_session_place;
		$agf->type_session = GETPOST('type_session', 'int');
		$agf->commercialid = GETPOST('commercial', 'int');
		$agf->contactid = GETPOST('contact', 'int');

		$agf->trainer_ext_information = dol_htmlcleanlastbr(GETPOST('trainer_ext_information', 'none'));

		if ($conf->global->AGF_CONTACT_DOL_SESSION) {
			$agf->sourcecontactid = $agf->contactid;
		}
		$agf->notes = GETPOST('notes', 'alpha');
		$agf->status = GETPOST('session_status', 'int');

		if ($user->rights->agefodd->session->margin) {
			$agf->cost_trainer_planned = GETPOST('costtrainer', 'alpha');
			$agf->cost_site_planned = GETPOST('costsite', 'alpha');
			$agf->sell_price_planned = GETPOST('sellprice', 'alpha');
		}

		$agf->date_res_site = dol_mktime(12, 0, 0, GETPOST('res_sitemonth', 'int'), GETPOST('res_siteday', 'int'), GETPOST('res_siteyear', 'int'));
		$agf->date_res_trainer = dol_mktime(12, 0, 0, GETPOST('res_trainmonth', 'int'), GETPOST('res_trainday', 'int'), GETPOST('res_trainyear', 'int'));
		$agf->date_res_confirm_site = dol_mktime(12, 0, 0, GETPOST('res_siteconfirmmonth', 'int'), GETPOST('res_siteconfirmday', 'int'), GETPOST('res_siteconfirmyear', 'int'));

		$fk_soc = GETPOST('fk_soc', 'int');
		$fk_soc_requester = GETPOST('fk_soc_requester', 'int');
		$fk_socpeople_requester = GETPOST('fk_socpeople_requester', 'int');
		$fk_socpeople_presta = GETPOST('fk_socpeople_presta', 'int');
		$fk_soc_employer = GETPOST('fk_soc_employer', 'int');
		$color = GETPOST('color', 'alpha');
		$nb_place = GETPOST('nb_place', 'int');
		$nb_stagiaire = GETPOST('nb_stagiaire', 'int');
		$force_nb_stagiaire = GETPOST('force_nb_stagiaire', 'int');

		if ($force_nb_stagiaire == 1 && $agf->force_nb_stagiaire != '') {
			$agf->force_nb_stagiaire = 1;
		} else {
			$agf->force_nb_stagiaire = 0;
		}

		$cost_trip = GETPOST('costtrip', 'alpha');

		if (! empty($fk_soc))
			$agf->fk_soc = $fk_soc;
		if (! empty($fk_soc_requester))
			$agf->fk_soc_requester = $fk_soc_requester;
		if (! empty($fk_soc_employer))
			$agf->fk_soc_employer = $fk_soc_employer;

		if (! empty($fk_socpeople_requester))
			$agf->fk_socpeople_requester = $fk_socpeople_requester;
		else { // If empty, maybe we don't need a socpeople requester
			unset($agf->fk_socpeople_requester);
		}

		if (! empty($fk_socpeople_presta))
			$agf->fk_socpeople_presta = $fk_socpeople_presta;
		if (! empty($color))
			$agf->color = $color;
		if (! empty($nb_place))
			$agf->nb_place = $nb_place;
		if (! empty($nb_stagiaire))
			$agf->nb_stagiaire = $nb_stagiaire;
		if (! empty($force_nb_stagiaire))
			$agf->force_nb_stagiaire = $force_nb_stagiaire;
		if (! empty($cost_trip))
			$agf->cost_trip_planned = $cost_trip;

		if ($error == 0) {
			$extrafields->setOptionalsFromPost($extralabels, $agf);

			$result = $agf->update($user);
			if ($result > 0) {

				if (! empty($saveandclose)) {
					Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
				} else {
					setEventMessage($langs->trans('Save'), 'mesgs');
					Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $id);
				}
				exit();
			} else {
				setEventMessage($agf->error, 'errors');
			}
		} else {
			if (! empty($saveandclose)) {
				$action = '';
			} else {
				$action = 'edit';
			}
		}
	} else {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	}
}

/*
 * Action create (new training session)
 */

if ($action == 'add_confirm' && $user->rights->agefodd->creer) {
	$error = 0;
	if (empty($cancel)) {
		$agf = new Agsession($db);

		$fk_session_place = GETPOST('place', 'int');
		if (($fk_session_place == - 1) || (empty($fk_session_place))) {
			$error ++;
			setEventMessage($langs->trans('AgfPlaceMandatory'), 'errors');
		}

		// If customer is selected contact is required
		$custid = GETPOST('fk_soc', 'int');
		$contactclientid = GETPOST('contact', 'int');
		if (empty($conf->global->AGF_CONTACT_NOT_MANDATORY_ON_SESSION)) {
			if (((($custid != - 1) && (! empty($custid))) && (($contactclientid == - 1) || (empty($contactclientid))))) {
				$error ++;
				setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AgfSessionContact")), 'errors');
			}
		}

		$training_id = GETPOST('formation', 'int');
		if (($training_id == - 1) || (empty($training_id))) {
			$error ++;
			setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AgfFormIntitule")), 'errors');
		}

		$agf->fk_soc_requester = GETPOST('fk_soc_requester', 'int');
		$agf->fk_socpeople_requester = GETPOST('fk_socpeople_requester', 'int');
		// If customer and requester are the same and contact requester is empty and contact is not empty then contact request is the same as contact
		if ($agf->fk_soc_requester == $custid && (empty($agf->fk_socpeople_requester) || $agf->fk_socpeople_requester == - 1) && (! empty($contactclientid) || $contactclientid != - 1) && ! empty($conf->global->AGF_CONTACT_DOL_SESSION)) {
			$agf->fk_socpeople_requester = $contactclientid;
		}
		$agf->fk_socpeople_presta = GETPOST('fk_socpeople_presta', 'int');
		$agf->fk_soc_employer = GETPOST('fk_soc_employer', 'int');

		$agf->fk_formation_catalogue = $training_id;
		$agf->fk_session_place = $fk_session_place;
		$agf->nb_place = GETPOST('nb_place', 'int');
		$agf->type_session = GETPOST('type_session', 'int');
		$agf->nb_place = GETPOST('nb_place', 'int');
		$agf->status = GETPOST('session_status', 'int');
		$agf->color = GETPOST('color', 'none');
		$agf->fk_soc = GETPOST('fk_soc', 'int');
		$agf->dated = dol_mktime(12, 0, 0, GETPOST('dadmonth', 'int'), GETPOST('dadday', 'int'), GETPOST('dadyear', 'int'));
		$agf->datef = dol_mktime(12, 0, 0, GETPOST('dafmonth', 'int'), GETPOST('dafday', 'int'), GETPOST('dafyear', 'int'));

		if ($agf->dated > $agf->datef) {
			$error ++;
			setEventMessage($langs->trans('AgfSessionDateErrors'), 'errors');
		}

		$agf->notes = GETPOST('notes', 'alpha');
		$agf->commercialid = GETPOST('commercial', 'int');

		// If custid not empty but commercialid empty, set commercial as first saleman of thirdparty
		if ((empty($agf->commercialid) || $agf->commercialid == - 1) && ! empty($custid)) {
			$sql_saleman = 'SELECT fk_user FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux WHERE fk_soc=' . $custid . ' LIMIT 1';
			$resql_saleman = $db->query($sql_saleman);
			if (! $resql_saleman) {
				setEventMessage($db->lasterror, 'erros');
			} else {
				$obj_saleman = $db->fetch_object($resql_saleman);
				if (! empty($obj_saleman->fk_user)) {
					$agf->commercialid = $obj_saleman->fk_user;
				}
			}
		}

		$agf->contactid = GETPOST('contact', 'int');

		$agf->fk_product = GETPOST('productid', 'int');

		$agf->duree_session = GETPOST('duree_session', 'int');
		$agf->intitule_custo = GETPOST('intitule_custo', 'alpha');

		$fk_propal = GETPOST('fk_propal', 'int');
		$fk_order = GETPOST('fk_order', 'int');

		if ($error == 0) {

			$extrafields->setOptionalsFromPost($extralabels, $agf);

			$result = $agf->create($user);

			$new_session_id = $result;

			if ($result > 0) {
				// If session creation are ok
				// We create admnistrative task associated
				$result = $agf->createAdmLevelForSession($user);
				if ($result < 0) {
					setEventMessage($agf->error, 'errors');
					$error ++;
				}
			} else {
				setEventMessage($agf->error, 'errors');
				$error ++;
			}

			$TMessage = $agf->checkOtherSessionSamePlaceDate();
			if (!empty($agf->error)) setEventMessage($agf->error, 'errors');
			elseif (!empty($TMessage)) setEventMessage($TMessage, 'warnings');
		}


		if ($error == 0 && ! empty($fk_propal)) {
			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			$agf_elem = new Agefodd_session_element($db);
			$agf_elem->fk_element = $fk_propal;
			$agf_elem->fk_session_agefodd = $agf->id;
			$agf_elem->fk_soc = $custid;
			$agf_elem->element_type = 'propal';

			$result = $agf_elem->create($user);

			if ($result < 0) {
				setEventMessage($agf_elem->error, 'errors');
				$error ++;
			}
		}

		if ($error == 0 && ! empty($fk_order)) {
			dol_include_once('/agefodd/class/agefodd_session_element.class.php');
			$agf_elem = new Agefodd_session_element($db);
			$agf_elem->fk_element = $fk_order;
			$agf_elem->fk_session_agefodd = $agf->id;
			$agf_elem->fk_soc = $custid;
			$agf_elem->element_type = 'order';

			$result = $agf_elem->create($user);

			if ($result < 0) {
				setEventMessage($agf_elem->error, 'errors');
				$error ++;
			}
		}

		if ($error == 0) {
			Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $agf->id);
			exit();
		} else {
			$action = 'create';
		}
	} else {
		Header("Location: list.php");
		exit();
	}
}

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes') {
	/*$clone_content = GETPOST ( 'clone_content' , 'none');
	 print 'clone_content='.$clone_content;
	 if (empty ( $clone_content )) {
	 setEventMessage ( $langs->trans ( "NoCloneOptionsSpecified" ), 'errors' );
	 } else {*/
	$agf = new Agsession($db);
	if ($agf->fetch($id) > 0) {
		$result = $agf->createFromClone($id, $hookmanager);
		if ($result > 0) {
			if (GETPOST('clone_calendar', 'none')) {
				// clone calendar information
				$calendrierstat = new Agefodd_sesscalendar($db);
				$calendrier = new Agefodd_sesscalendar($db);
				$calendrier->fetch_all($id);
				$blocNumber = count($calendrier->lines);
				if ($blocNumber > 0) {
					$old_date = 0;
					$duree = 0;
					for($i = 0; $i < $blocNumber; $i ++) {
						$calendrierstat->sessid = $result;
						$calendrierstat->date_session = $calendrier->lines[$i]->date_session;
						$calendrierstat->heured = $calendrier->lines[$i]->heured;
						$calendrierstat->heuref = $calendrier->lines[$i]->heuref;

						$result1 = $calendrierstat->create($user);
					}
				}
			}
			if (GETPOST('clone_trainee', 'none')) {
				// Clone trainee information
				$traineestat = new Agefodd_session_stagiaire($db);
				$session_trainee = new Agefodd_session_stagiaire($db);
				$session_trainee->fetch_stagiaire_per_session($id);
				$blocNumber = count($session_trainee->lines);
				if ($blocNumber > 0) {
					foreach ( $session_trainee->lines as $line ) {
						$traineestat->fk_session_agefodd = $result;
						$traineestat->fk_stagiaire = $line->id;
						$traineestat->fk_agefodd_stagiaire_type = $line->fk_agefodd_stagiaire_type;

						$result1 = $traineestat->create($user);
					}
				}
			}

			if (GETPOST('clone_trainer', 'none')) {
				// Clone trainer information
				$trainerstat = new Agefodd_session_formateur($db);
				$session_trainer = new Agefodd_session_formateur($db);
				$session_trainer->fetch_formateur_per_session($id);
				$blocNumber = count($session_trainer->lines);
				if ($blocNumber > 0) {
					foreach ( $session_trainer->lines as $line ) {
						$trainerstat->sessid = $result;
						$trainerstat->formid = $line->formid;
						$trainerstat->trainer_type = $line->trainer_type;
						$result1 = $trainerstat->create($user);
					}
				}
			}

			if (GETPOST('clone_linkedFiles', 'none'))
			{
				$upload_dir = $conf->agefodd->dir_output . "/" . $agf->id;
				$dest_dir = $conf->agefodd->dir_output . "/" . $result;
				dol_mkdir($dest_dir);

				$filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', "name", SORT_ASC, 1);
				if (!empty($filearray))
				{
					foreach ($filearray as $file_orig)
					{
						$res_copy = dol_copy($file_orig['fullname'], $dest_dir.'/'.$file_orig['name']);
						if ($res_copy <= 0) { var_dump($res_copy, $file_orig['fullname'], $dest_dir.'/'.$file_orig['name']); exit;}
					}
				}

			}
			header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $result);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
			$action = '';
		}
	}
	// }
}

}

/*
 * View
 */

llxHeader('', $langs->trans("AgfSessionDetail"), '', '', '', '', array(
		'/agefodd/includes/lib.js'
), array());
$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother = new FormOther($db);

/*
 * Action create
 */
if ($action == 'create' && $user->rights->agefodd->creer) {

	$fk_soc_crea = GETPOST('fk_soc', 'int');
	$fk_propal = GETPOST('fk_propal', 'int');
	$fk_order = GETPOST('fk_order', 'int');
	$urlreturnsite = '';

	print_fiche_titre($langs->trans("AgfMenuSessNew"));

	print '<form name="add" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="add_confirm">';
	if (! empty($fk_propal)) {
		print '<input type="hidden" name="fk_propal" value="' . $fk_propal . '">';
		$urlreturnsite = '&fk_propal=' . $fk_propal;
	}
	if (! empty($fk_order)) {
		print '<input type="hidden" name="fk_order" value="' . $fk_order . '">';
		$urlreturnsite = '&fk_order=' . $fk_order;
	}

	print '<div>';
	print '<table id="session_card" class="border tableforfield" width="100%">';

	print '<tr class="order_place"><td><span class="fieldrequired">' . $langs->trans("AgfLieu") . '</span></td>';
	print '<td><table class="nobordernopadding"><tr><td>';
	print $formAgefodd->select_site_forma(GETPOST('place', 'int'), 'place', 1);
	print '</td>';
	print '<td> <a href="' . dol_buildpath('/agefodd/site/card.php', 1) . '?action=create&url_return=' . urlencode($_SERVER['PHP_SELF'] . '?action=create' . $urlreturnsite) . '" title="' . $langs->trans('AgfCreateNewSite') . '">' . $langs->trans('AgfCreateNewSite') . '</a>';
	print '</td><td>' . $form->textwithpicto('', $langs->trans("AgfCreateNewSiteHelp"), 1, 'help') . '</td></tr></table>';
	print '</td></tr>';

	print '<tr class="order_intitule"><td><span class="fieldrequired">' . $langs->trans("AgfFormIntitule") . '</span></td>';
	print '<td>' . $formAgefodd->select_formation(GETPOST('formation', 'int'), 'formation', 'intitule', 1) . '</td></tr>';

	print '<tr class="order_intituleCusto"><td>' . $langs->trans("AgfFormIntituleCust") . '</td>';
	print '<td><input size="30" type="text" class="flat" id="intitule_custo" name="intitule_custo" value="' . dol_escape_htmltag($agf->intitule_custo) . '" /></td></tr>';

	print '<tr class="order_type"><td>' . $langs->trans("AgfFormTypeSession") . '</td>';
	print '<td>' . $formAgefodd->select_type_session('type_session', $conf->global->AGF_DEFAULT_SESSION_TYPE) . '</td></tr>';

	print '<tr  class="order_sessionColor"><td>' . $langs->trans("Color") . '</td>';
        print '<td>';
        print $formother->selectColor($agf->color, 'color');
        print '</td></tr>';

	print '<tr class="order_sessionCommercial"><td>' . $langs->trans("AgfSessionCommercial") . '</td>';
	print '<td>';
	$commercial = GETPOST('commercial', 'int');
	if (empty($conf->global->AGF_ALLOW_ADMIN_COMMERCIAL)) {
		$exclude_array = array(
				1
		);
	} else {
		$exclude_array = array();
	}
	print $form->select_dolusers((empty($commercial) ? $user->id : $commercial), 'commercial', 1, $exclude_array);
	print '</td></tr>';

	print '<tr class="order_dated"><td><span class="fieldrequired">' . $langs->trans("AgfDateDebut") . '</span></td><td>';
	$form->select_date(dol_mktime(0, 0, 0, GETPOST('dadmonth', 'int'), GETPOST('dadday', 'int'), GETPOST('dadyear', 'int')), 'dad', '', '', '', 'add');
	print '</td></tr>';

	print '<tr class="order_datef"><td><span class="fieldrequired">' . $langs->trans("AgfDateFin") . '</span></td><td>';
	$form->select_date(dol_mktime(0, 0, 0, GETPOST('dafmonth', 'int'), GETPOST('dafday', 'int'), GETPOST('dafyear', 'int')), 'daf', '', '', '', 'add');
	print '</td></tr>';

	print '<tr class="order_customer"><td>' . $langs->trans("Customer") . '</td>';
	print '<td>';
	if ($conf->global->AGF_CONTACT_DOL_SESSION) {
		$events = array();
		$events[] = array(
				'method' => 'getContacts',
				'url' => dol_buildpath('/core/ajax/contacts.php', 1),
				'htmlname' => 'contact',
				'showempty' => '1',
				'params' => array(
						'add-customer-contact' => 'disabled'
				)
		);
		print $form->select_company($fk_soc_crea, 'fk_soc', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth100','','',2);
	} else {
		print $form->select_company($fk_soc_crea, 'fk_soc', '', 'SelectThirdParty', 1, 0, array(), 0, 'minwidth100','','',2);
	}
	print '</td></tr>';

	if ($conf->global->AGF_CONTACT_DOL_SESSION) {
		print '<tr class="order_sessionContact"><td>' . $langs->trans("AgfSessionContact") . '</td>';
		print '<td><table class="nobordernopadding"><tr><td>';
		if (! empty($fk_soc_crea)) {
			$formAgefodd->select_contacts_custom($fk_soc_crea, '', 'contact', 1, '', '', 1, '', 1);
		} else {
			$formAgefodd->select_contacts_custom(0, '', 'contact', 1, '', 1000, 1, '', 1);
		}
		print '</td>';
		print '<td>' . $form->textwithpicto('', $langs->trans("AgfAgefoddDolContactHelp"), 1, 'help') . '</td></tr></table>';
		print '</td></tr>';
	} else {
		print '<tr class="order_sessionContact"><td>' . $langs->trans("AgfSessionContact") . '</td>';
		print '<td><table class="nobordernopadding"><tr><td>';
		print $formAgefodd->select_agefodd_contact(GETPOST('contact', 'int'), 'contact', '', 1);
		print '</td>';
		print '<td>' . $form->textwithpicto('', $langs->trans("AgfAgefoddContactHelp"), 1, 'help') . '</td></tr></table>';
		print '</td></tr>';
	}

	print '<tr class="order_typeRequester"><td>' . $langs->trans("AgfTypeRequester") . '</td>';
	print '<td>';
	$events = array();
	$events[] = array(
			'method' => 'getContacts',
			'url' => dol_buildpath('/core/ajax/contacts.php', 1),
			'htmlname' => 'fk_socpeople_requester',
			'showempty' => '1',
			'params' => array(
					'add-customer-contact' => 'disabled'
			)
	);
	print $form->select_company($fk_soc_crea, 'fk_soc_requester', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth100','','',2);
	print '</td></tr>';

	print '<tr class="order_typeRequesterContact"><td>' . $langs->trans("AgfTypeRequesterContact") . '</td>';
	print '<td><table class="nobordernopadding"><tr><td>';
	if (! empty($fk_soc_crea)) {
		$formAgefodd->select_contacts_custom($fk_soc_crea, '', 'fk_socpeople_requester', 1, '', '', 1, '', 1);
	} else {
		$formAgefodd->select_contacts_custom(0, '', 'fk_socpeople_requester', 1, '', 1000, 1, '', 1);
	}
	print '</td>';
	print '<td>' . $form->textwithpicto('', $langs->trans("AgfAgefoddDolRequesterHelp"), 1, 'help') . '</td></tr></table>';
	print '</td></tr>';

	print '<tr class="order_typePresta"><td>' . $langs->trans("AgfTypePresta") . $form->textwithpicto('', $langs->trans("AgfTypePrestaHelp"), 1, 'help') . '</td>';
	print '<td>';
	$formAgefodd->select_contacts_custom(0, GETPOST('fk_socpeople_presta', 'int'), 'fk_socpeople_presta', 1, '', '', 1, '', 1, 0, array(), false, 1);
	print '</td></tr>';

	print '<tr class="order_typeEmployee"><td>' . $langs->trans("AgfTypeEmployee") . $form->textwithpicto('', $langs->trans("AgfTypeEmployeeHelp"), 1, 'help') . '</td>';
	print '<td>';
	print $form->select_company($fk_soc_employer, 'fk_soc_employer', '', 'SelectThirdParty', 1, 0, array(), 0, 'minwidth100','','',2);
	print '</td></tr>';

	print '<tr class="order_product"><td width="20%">' . $langs->trans("AgfProductServiceLinked") . '</td><td>';
	print $form->select_produits($agf->fk_product, 'productid', '', 10000, 0, 1, 2, '', 0, array());
	print "</td></tr>";

	print '<tr class="order_duration"><td>' . $langs->trans("AgfDuree") . '</td>';
	print '<td><input size="4" type="text" class="flat" id="duree_session" name="duree_session" value="' . $agf->duree_session . '" /></td></tr>';

	print '<tr class="order_nbplaceavailable"><td>' . $langs->trans("AgfNumberPlaceAvailable") . '</td>';
	print '<td>';
	print '<input type="text" class="flat" name="nb_place" size="4" value="' . GETPOST('nb_place', 'int') . '"/>';
	print '</td></tr>';

	print '<tr class="order_note"><td valign="top">' . $langs->trans("AgfNote") . '</td>';
	print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;">' . GETPOST('notes', 'aplha') . '</textarea></td></tr>';

	print '<tr class="order_status"><td valign="top">' . $langs->trans("AgfStatusSession") . '</td>';
	print '<td>';
	$defstat = GETPOST('AGF_DEFAULT_SESSION_STATUS', 'none');
	if (empty($defstat))
		$defstat = $conf->global->AGF_DEFAULT_SESSION_STATUS;
	print $formAgefodd->select_session_status($defstat, "session_status", 't.active=1');
	print '</td></tr>';

	if (! empty($extrafields->attribute_label)) {
		print $agf->showOptionals($extrafields, 'edit');
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $agf, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print '</table>';
	print '</div>';

	print '<table style=noborder align="right">';
	print '<tr><td align="center" colspan=2>';
	print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
	print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
	print '</td></tr>';

	print '</table>';
	print '</form>';

	?>
<script type="text/javascript">
	$(document).ready(function () {
		<!--

		$("body").on('#productid','change',function(){
			$(this).val(result.fk_product);
			});
		-->
		$("body").on('change','#place',function () {
			var fk_place = $(this).val();
			var fk_training = $('#formation').val();
			data = {"action":"get_nb_place","fk_training":fk_training,"fk_place":fk_place};
			ajax_set_nbplace(data);

		});

		$("body").on('change','#formation',function () {
			var fk_training = $(this).val();
			data = {"action":"get_duration_and_product","fk_training":fk_training};
			ajax_set_duration_and_product(data);
			var option_txt = $(this).find('option[value='+$(this).val()+']').text();
			$('#intitule_custo').val(option_txt);
			var fk_place = $('#place').val();
			data = {"action":"get_nb_place","fk_training":fk_training,"fk_place":fk_place};
			ajax_set_nbplace(data);
		});
	});


	function ajax_set_duration_and_product(data)
    	{
    		$.ajax({
    		    url: "<?php echo dol_buildpath('/agefodd/scripts/interface.php', 1) ; ?>",
    		    type: "POST",
    		    dataType: "json",
    		    data: data,
    		    success: function(result){
					if((result.duree)!= null){
						$("#duree_session").val(result.duree);
					}else {
						$("#duree_session").val("");
					}
					if((result.fk_product)!= null ){
						$("#productid").val(result.fk_product).change();
					}else{
						$("#productid").val(0).change();
					}
					if($('#search_productid').length != 0 ){
						$('#search_productid').val(result.ref_product).change();
					}
				},
    		    error: function(error){
    		    	$.jnotify('AjaxError',"error");
    		    }
    		});
    	}
	function ajax_set_nbplace(data)
    	{
    		$.ajax({
    		    url: "<?php echo dol_buildpath('/agefodd/scripts/interface.php', 1) ; ?>",
    		    type: "POST",
    		    dataType: "json",
    		    data: data,
    		    success: function(result){
					if((result.nb_place)!= null){
						$("input[name='nb_place']").val(result.nb_place);
					}else {
						$("input[name='nb_place']").val("");
					}
				},
    		    error: function(error){
    		    	$.jnotify('AjaxError',"error");
    		    }
    		});
    	}
</script>

<?php
	printSessionFieldsWithCustomOrder();
} else {
	// Display session card
	if ($id) {
		$agf = new Agsession($db);
		$result = $agf->fetch($id);

		if ($result > 0) {
			if (! (empty($agf->id))) {
				$head = session_prepare_head($agf);

				if ($agf->type_session == 1) {
					$styledisplay = ' style="display:none" ';
				}

				dol_fiche_head($head, 'card', $langs->trans("AgfSessionDetail"), 0, 'calendarday');

				$agf_fact = new Agefodd_session_element($db);
				$agf_fact->fetch_by_session($agf->id);

				$cost_trainer_engaged = $agf_fact->trainer_engaged_amount;
				$cost_site_engaged = $agf_fact->room_engaged_amount;
				$cost_trip_engaged = $agf_fact->trip_engaged_amount;

				$engaged_revenue = $agf_fact->propal_sign_amount;
				$paied_revenue = $agf_fact->invoice_payed_amount + $agf_fact->invoice_ongoing_amount;
				$other_amount = '(' . $langs->trans('AgfProposalAmountSigned') . ' ' . $agf_fact->propal_sign_amount . ' ' . $langs->trans('Currency' . $conf->currency);
				if (! empty($conf->commande->enabled)) {
					$other_amount .= '/' . $langs->trans('AgfOrderAmount') . ' ' . $agf_fact->order_amount . ' ' . $langs->trans('Currency' . $conf->currency);
				}
				$other_amount .= '/' . $langs->trans('AgfInvoiceAmountWaiting') . ' ' . $agf_fact->invoice_ongoing_amount . ' ' . $langs->trans('Currency' . $conf->currency);
				$other_amount .= '/' . $langs->trans('AgfInvoiceAmountPayed') . ' ' . $agf_fact->invoice_payed_amount . ' ' . $langs->trans('Currency' . $conf->currency) . ')';

				/*
				 *
				 * Display edit mode
				 *
				 */
				if ($action == 'edit') {

					$newperiod = GETPOST('newperiod', 'int');

					if ($anchor == 'period') {
						print '<script type="text/javascript">
						jQuery(document).ready(function () {
							jQuery(function() {
								$(\'html, body\').animate({scrollTop: $("#period").offset().top}, 500,\'easeInOutCubic\');
							});
						});
						</script> ';
					}

					print '<form name="update" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
					print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
					print '<input type="hidden" name="action" value="update">';
					print '<input type="hidden" name="id" value="' . $id . '">';
					print '<input type="hidden" name="action" value="update">';

					print '<table id="session_card" class="border tableforfield" width="100%">';
					print '<tr class="order_ref"><td width="20%">' . $langs->trans("Ref") . '</td>';
					print '<td>' . $agf->id . '</td></tr>';

					print '<tr class="order_intitule"><td>' . $langs->trans("AgfFormIntitule") . '</td>';
					print '<td>' . $formAgefodd->select_formation($agf->formid, 'formation');
					print '</td></tr>';

					print '<tr class="order_intituleCusto"><td>' . $langs->trans("AgfFormIntituleCust") . '</td>';
					print '<td><input size="30" type="text" class="flat" id="intitule_custo" name="intitule_custo" value="' . dol_escape_htmltag($agf->intitule_custo) . '" /></td></tr>';

					print '<tr class="order_type"><td>' . $langs->trans("AgfFormTypeSession") . '</td>';
					print '<td>' . $formAgefodd->select_type_session('type_session', $agf->type_session) . '</td></tr>';

					print '<tr class="order_formRef"><td>' . $langs->trans("AgfFormRef") . '</td>';
					print '<td>' . $agf->formref . '</td></tr>';

					print '<tr  class="order_sessionColor"><td>' . $langs->trans("Color") . '</td>';
					print '<td>';
					print $formother->selectColor($agf->color, 'color');
					print '</td></tr>';

					print '<tr class="order_sessionCommercial"><td>' . $langs->trans("AgfSessionCommercial") . '</td>';
					print '<td>';
					if (empty($conf->global->AGF_ALLOW_ADMIN_COMMERCIAL)) {
						$exclude_array = array(
								1
						);
					} else {
						$exclude_array = array();
					}
					print $form->select_dolusers($agf->commercialid, 'commercial', 1, $exclude_array);
					print '</td></tr>';

					print '<tr class="order_duration"><td>' . $langs->trans("AgfDuree") . '</td>';
					print '<td><input size="4" type="text" class="flat" id="duree_session" name="duree_session" value="' . $agf->duree_session . '" /></td></tr>';

					print '<tr class="order_product"><td width="20%">' . $langs->trans("AgfProductServiceLinked") . '</td><td>';
					print $form->select_produits($agf->fk_product, 'productid', '', 10000, 0, 1, 2, '', 0, array(), $agf->fk_soc);
					print "</td></tr>";

					print '<tr class="order_dated"><td>' . $langs->trans("AgfDateDebut") . '</td><td>';
					$form->select_date($agf->dated, 'dad', '', '', '', 'update');
					print '</td></tr>';

					print '<tr class="order_datef"><td>' . $langs->trans("AgfDateFin") . '</td><td>';
					$form->select_date($agf->datef, 'daf', '', '', '', 'update');
					print '</td></tr>';

					print '<tr class="order_customer"><td>' . $langs->trans("Customer") . '</td>';
					print '<td>';
					if ($conf->global->AGF_CONTACT_DOL_SESSION) {
						$events = array();
						$events[] = array(
								'method' => 'getContacts',
								'url' => dol_buildpath('/core/ajax/contacts.php', 1),
								'htmlname' => 'contact',
								'params' => array(
										'add-customer-contact' => 'disabled'
								)
						);
						print $form->select_company($agf->fk_soc, 'fk_soc', '', 'SelectThirdParty', 1, 0, $events);
					} else {
						print $form->select_company($agf->fk_soc, 'fk_soc', '', 'SelectThirdParty', 1);
					}
					if (! empty($agf->fk_soc) && ! empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT)) {
						print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $agf->id . '&amp;action=remove_cust">' . img_delete($langs->trans('Delete')) . '</a>';
					}
					print '</td></tr>';

					if ($conf->global->AGF_CONTACT_DOL_SESSION) {
						print '<tr class="order_sessionContact"><td>' . $langs->trans("AgfSessionContact") . '</td>';
						print '<td><table class="nobordernopadding"><tr><td>';
						if (! empty($agf->fk_soc)) {
							$formAgefodd->select_contacts_custom($agf->fk_soc, $agf->sourcecontactid, 'contact', 1, '', '', 1, '', 1);
						} else {
							$formAgefodd->select_contacts_custom(0, $agf->sourcecontactid, 'contact', 1, '', '', 1, '', 1);
						}
						print '</td>';
						print '<td>' . $form->textwithpicto('', $langs->trans("AgfAgefoddDolContactHelp"), 1, 'help') . '</td></tr></table>';
						if (! empty($agf->sourcecontactid) && ! empty($conf->global->CONTACT_USE_SEARCH_TO_SELECT)) {
							print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $agf->id . '&amp;action=remove_contact">' . img_delete($langs->trans('Delete')) . '</a>';
						}
						print '</td></tr>';
					} else {
						print '<tr class="order_sessionContact"><td>' . $langs->trans("AgfSessionContact") . '</td>';
						print '<td><table class="nobordernopadding"><tr><td>';
						print $formAgefodd->select_agefodd_contact($agf->contactid, 'contact', '', 1);
						print '</td><td>' . $form->textwithpicto('', $langs->trans("AgfAgefoddContactHelp"), 1, 'help') . '</td></tr></table>';
						if (! empty($agf->contactid) && ! empty($conf->global->CONTACT_USE_SEARCH_TO_SELECT)) {
							print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $agf->id . '&amp;action=remove_contact">' . img_delete($langs->trans('Delete')) . '</a>';
						}
						print '</td></tr>';
					}

					if (empty($conf->global->AGF_DOT_NOT_MANAGE_REQUESTER)) {
						print '<tr class="order_typeRequester"><td>' . $langs->trans("AgfTypeRequester") . '</td>';
						print '<td>';
						$events = array();
						$events[] = array(
								'method' => 'getContacts',
								'url' => dol_buildpath('/core/ajax/contacts.php', 1),
								'htmlname' => 'fk_socpeople_requester',
								'params' => array(
										'add-customer-contact' => 'disabled'
								)
						);
						print $form->select_company($agf->fk_soc_requester, 'fk_soc_requester', '', 'SelectThirdParty', 1, 0, $events);
						if (! empty($agf->fk_soc_requester) && ! empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT)) {
							print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $agf->id . '&amp;action=remove_requester">' . img_delete($langs->trans('Delete')) . '</a>';
						}
						print '</td></tr>';

						print '<tr class="order_typeRequesterContact"><td>' . $langs->trans("AgfTypeRequesterContact") . '</td>';
						print '<td><table class="nobordernopadding"><tr><td>';
						if (! empty($agf->fk_soc_requester)) {
							$formAgefodd->select_contacts_custom($agf->fk_soc_requester, $agf->fk_socpeople_requester, 'fk_socpeople_requester', 1, '', '', 1, '', 1);
						} else {
							$formAgefodd->select_contacts_custom(0, $agf->fk_socpeople_requester, 'fk_socpeople_requester', 1, '', '', 1, '', 1);
						}
						print '</td>';
						print '<td>' . $form->textwithpicto('', $langs->trans("AgfAgefoddDolRequesterHelp"), 1, 'help') . '</td></tr></table>';
						if (! empty($agf->fk_socpeople_requester) && ! empty($conf->global->CONTACT_USE_SEARCH_TO_SELECT)) {
							print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $agf->id . '&amp;action=remove_contactrequester">' . img_delete($langs->trans('Delete')) . '</a>';
						}
						print '</td></tr>';
					}
					print '<tr class="order_typePresta"><td>' . $langs->trans("AgfTypePresta") . '</td>';
					print '<td><table class="nobordernopadding"><tr><td>';
					$formAgefodd->select_contacts_custom(0, $agf->fk_socpeople_presta, 'fk_socpeople_presta', 1, '', '', 1, '', 1, 0, array(), false, 1);
					print '</td>';
					print '<td>' . $form->textwithpicto('', $langs->trans("AgfTypePrestaHelp"), 1, 'help') . '</td></tr></table>';
					if (! empty($agf->fk_socpeople_presta) && ! empty($conf->global->CONTACT_USE_SEARCH_TO_SELECT)) {
						print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $agf->id . '&amp;action=remove_contactpresta">' . img_delete($langs->trans('Delete')) . '</a>';
					}
					print '</td></tr>';

					print '<tr class="order_typeEmployee"><td>' . $langs->trans("AgfTypeEmployee") . '</td>';
					print '<td><table class="nobordernopadding"><tr><td>';
					print $form->select_company($agf->fk_soc_employer, 'fk_soc_employer', '', 'SelectThirdParty', 1, 0, array());
					print '</td>';
					print '<td>' . $form->textwithpicto('', $langs->trans("AgfTypeEmployeeHelp"), 1, 'help') . '</td></tr></table>';
					if (! empty($agf->fk_soc_employer) && ! empty($conf->global->COMPANY_USE_SEARCH_TO_SELECT)) {
						print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $agf->id . '&amp;action=remove_employer">' . img_delete($langs->trans('Delete')) . '</a>';
					}
					print '</td></tr>';

					print '<tr class="order_place"><td>' . $langs->trans("AgfLieu") . '</td>';
					print '<td>';
					print $formAgefodd->select_site_forma($agf->placeid, 'place');
					print '</td></tr>';

					print '<tr class="order_nbplaceavailable"><td width="20%">' . $langs->trans("AgfNumberPlaceAvailable") . '</td>';
					print '<td><input size="4" type="text" class="flat" name="nb_place" value="' . $agf->nb_place . '" />' . '</td></tr>';

					if ($agf->force_nb_stagiaire == 0 || empty($agf->force_nb_stagiaire)) {
						$disabled = 'disabled="disabled"';
						$checked = '';
					} else {
						$disabled = '';
						$checked = 'checked="checked"';
					}
					// if not force we must input values
					print '<tr class="order_nbplaceparticipants"><td width="20%">' . $langs->trans("AgfNbreParticipants") . '</td>';
					print '<td><input size="4" type="text" class="flat" id="nb_stagiaire" name="nb_stagiaire" ' . $disabled . ' value="' . ($agf->nb_stagiaire > 0 ? $agf->nb_stagiaire : '0') . '" /></td></tr>';

					print '<tr class="order_force_nb_stagiaire"><td width="20%">' . $langs->trans("AgfForceNbreParticipants") . '</td>';
					print '<td>';
					print '<input size="4" type="checkbox" ' . $checked . ' name="force_nb_stagiaire" value="1" onclick="fnForceUpdate(this);" />' . '</td></tr>';

					print '<tr class="order_note"><td valign="top">' . $langs->trans("AgfNote") . '</td>';
					if (! empty($agf->note))
						$notes = nl2br($agf->note);
					else
						$notes = $langs->trans("AgfUndefinedNote");
					print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;">' . stripslashes($agf->notes) . '</textarea></td></tr>';

					// Date res trainer
					print '<tr class="order_dateResTrainer">
					<td>' . $langs->trans("AgfDateResTrainer") . '</td><td><table class="nobordernopadding"><tr><td>';
					$form->select_date($agf->date_res_trainer, 'res_train', '', '', 1, 'update', 1, 1);
					print '</td><td>';
					print $form->textwithpicto('', $langs->trans("AgfDateCheckbox"));
					print '</td></tr></table>';
					print '</td></tr>';

					// Date res site
					print '<tr class="order_dateResSite"><td>' . $langs->trans("AgfDateResSite") . '</td><td><table class="nobordernopadding"><tr><td>';
					$form->select_date($agf->date_res_site, 'res_site', '', '', 1, 'update', 1, 1);
					print '</td><td>';
					print $form->textwithpicto('', $langs->trans("AgfDateCheckbox"));
					print '</td></tr></table>';

					// Date confirm site
					print '<tr class="order_dateResConfirmSite"><td>' . $langs->trans("AgfDateResConfirmSite") . '</td><td><table class="nobordernopadding"><tr><td>';
					$form->select_date($agf->date_res_confirm_site, 'res_siteconfirm', '', '', 1, 'update', 1, 1);
					print '</td><td>';
					print $form->textwithpicto('', $langs->trans("AgfDateCheckbox"));
					print '</td></tr></table>';

					print '<tr class="order_nbMintarget"><td width="20%">' . $langs->trans("AgfNbMintarget") . '</td><td>';
					print '<input name="nbmintarget" class="flat" size="5" value="' . $agf->nb_subscribe_min . '"></td></tr>';

					print '<tr class="order_status"><td valign="top">' . $langs->trans("AgfStatusSession") . '</td>';
					print '<td>';
					print $formAgefodd->select_session_status($agf->status, "session_status", 't.active=1');
					print '</td></tr>';

					if (!empty($conf->externalaccess->enabled)) {
						require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
						print '<tr class="order_trainer_ext_information"><td valign="top">' . $langs->trans("AgfTrainerExternalMessage") . '</td>';
						print '<td>';
						$doleditor = new DolEditor('trainer_ext_information', $agf->trainer_ext_information, '', 160, 'dolibarr_notes', 'In', true, false, 1, 4, 90);
						$doleditor->Create();
						print '</td></tr>';
					}

					if (! empty($extrafields->attribute_label)) {
						print $agf->showOptionals($extrafields, 'edit');
					}

					$parameters = array();
					$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $agf, $action); // Note that $action and $object may have been modified by hook
					print $hookmanager->resPrint;

					print '</table>';

					/*
					 * Cost management
					 */
					if ($user->rights->agefodd->session->margin) {
						print_barre_liste($langs->trans("AgfCost"), "", "", "", "", "", '', 0);
						// print '<div class="tabBar">';
						print '<table class="border" width="100%">';

						print '<tr><td width="20%">' . $langs->trans("AgfCoutFormation") . '</td>';
						print '<td><input size="6" type="text" class="flat" name="sellprice" value="' . price($agf->sell_price_planned) . '" />' . ' ' . $langs->getCurrencySymbol($conf->currency) . ' ' . $other_amount . '</td></tr>';

						print '<tr><td width="20%">' . $langs->trans("AgfCoutFormateur") . '</td>';
						print '<td><input size="6" type="text" class="flat" name="costtrainer" value="' . price($agf->cost_trainer_planned) . '" />' . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td></tr>';

						print '<tr><td width="20%">' . $langs->trans("AgfCoutSalle") . '</td>';
						print '<td><input size="6" type="text" class="flat" name="costsite" value="' . price($agf->cost_site_planned) . '" />' . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td></tr>';
						print '<tr><td width="20%">' . $langs->trans("AgfCoutDeplacement") . '</td>';
						print '<td><input size="6" type="text" class="flat" name="costtrip" value="' . price($agf->cost_trip_planned) . '" />' . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td></tr>';

						print '</table>';
						// print '</div>';
					}

					print '<table style="nobordernopadding" align="right">';
					print '<tr><td align="center" colspan=2><br/><br/>';
					print '<input type="submit" class="butAction" name="saveandclose" value="' . $langs->trans("Save") . '"> &nbsp; ';
					print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
					print '</td></tr>';
					print '</table>';

					print '</form>';
					print '</div>';

					printSessionFieldsWithCustomOrder();
				} else {
					// Display view mode

					dol_agefodd_banner_tab($agf, 'id');
					print '<div class="underbanner clearboth"></div>';

					/*
					 * Confirm validate
					 */
					if ($action == 'validateregistrants') {
						print $form->formconfirm($_SERVER['PHP_SELF'] . "?id=" . $id, $langs->trans("AgfValidateRegistrantsOps"), $langs->trans("AgfConfirmValidateRegistrantsSession"), "confirm_validateregistrants", '', '', 1);
					}

					/*
					 * Confirm delete
					 */
					if ($action == 'delete') {
						print $form->formconfirm($_SERVER['PHP_SELF'] . "?id=" . $id, $langs->trans("AgfDeleteOps"), $langs->trans("AgfConfirmDeleteSession"), "confirm_delete", '', '', 1);
					}

					/*
					 * confirm archive update status
					 */
					if (isset($_GET["arch"])) {
						print $form->formconfirm($_SERVER['PHP_SELF'] . "?arch=" . $_GET["arch"] . "&id=" . $id, $langs->trans("AgfFormationArchiveChange"), $langs->trans("AgfConfirmArchiveChange"), "arch_confirm_delete", '', '', 1);
					}

					// Confirm clone
					if ($action == 'clone') {
						$formquestion = array(
								'text' => $langs->trans("ConfirmClone"),
								array(
										'type' => 'checkbox',
										'name' => 'clone_calendar',
										'label' => $langs->trans("AgfCloneSessionCalendar"),
										'value' => 1
								),
								array(
										'type' => 'checkbox',
										'name' => 'clone_trainee',
										'label' => $langs->trans("AgfCloneSessionTrainee"),
										'value' => 1
								),
								array(
										'type' => 'checkbox',
										'name' => 'clone_trainer',
										'label' => $langs->trans("AgfCloneSessionTrainer"),
										'value' => 1
								),
								array(
										'type' => 'checkbox',
										'name' => 'clone_linkedFiles',
										'label' => $langs->trans("AgfCloneLinkedFiles"),
										'value' => 0
								)
						);
						print $form->formconfirm($_SERVER['PHP_SELF'] . "?id=" . $id, $langs->trans("CloneSession"), $langs->trans("ConfirmCloneSession"), "confirm_clone", $formquestion, '', 1);
					}
					/*
					 print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
					 print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
					 print '</div>';
					 */

					$parameters = array('id' => $id);
					$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $agf, $action); // Note that $action and $object may have been modified by hook
					if (empty($reshook)) $formconfirm=$hookmanager->resPrint;

					// Print form confirm
					if (!empty($formconfirm)) {
						print $formconfirm;
					}

					printSessionFieldsWithCustomOrder();
					print '<div class="fichecenter">';
					// print '<table id="session_card" class="border" width="100%">';
					// Print session card
					$agf->printSessionInfo(false);

					/*
					 * Manage funding resources depend on type inter-enterprise or extra-enterprise
					 */
					if (! $agf->type_session > 0 && ! empty($conf->global->AGF_MANAGE_OPCA)) {
						print '<tr class="tr_order_OPCA"><td colspan="4">';

						print '<table class="border order_OPCA" width="100%">';
						print '<tr><td>' . $langs->trans("AgfSubrocation") . '</td>';
						if ($agf->is_OPCA == 1) {
							$isOPCA = ' checked="checked" ';
						} else {
							$isOPCA = '';
						}
						print '<td><input type="checkbox" class="flat" disabled="disabled" readonly="readonly" ' . $isOPCA . '/></td></tr>';

						print '<tr><td width="20%">' . $langs->trans("AgfOPCAName") . '</td>';
						print '	<td>';
						if (DOL_VERSION < 6.0) {
							print '<a href="' . dol_buildpath('/societe/soc.php', 1) . '?socid=' . $agf->fk_soc_OPCA . '">' . $agf->soc_OPCA_name . '</a>';
						} else {
							print '<a href="' . dol_buildpath('/societe/card.php', 1) . '?socid=' . $agf->fk_soc_OPCA . '">' . $agf->soc_OPCA_name . '</a>';
						}
						print '</td></tr>';

						print '<tr><td width="20%">' . $langs->trans("AgfOPCAAdress") . '</td>';
						print '	<td>';
						print dol_print_address($agf->OPCA_adress, 'gmap', 'thirdparty', 0);
						print '</td></tr>';

						print '<tr><td width="20%">' . $langs->trans("AgfOPCAContact") . '</td>';
						print '	<td>';
						print '<a href="' . dol_buildpath('/contact/card.php', 1) . '?id=' . $agf->fk_socpeople_OPCA . '">' . $agf->contact_name_OPCA . '</a>';
						print '</td></tr>';

						print '<tr><td width="20%">' . $langs->trans("AgfOPCANumClient") . '</td>';
						print '<td>';
						print $agf->num_OPCA_soc;
						print '</td></tr>';

						print '<tr><td width="20%">' . $langs->trans("AgfOPCADateDemande") . '</td>';

						print '<td>';
						print dol_print_date($agf->date_ask_OPCA, 'daytext');
						print '</td></tr>';

						print '<tr><td width="20%">' . $langs->trans("AgfOPCANumFile") . '</td>';
						print '<td>';
						print $agf->num_OPCA_file;
						print '</td></tr>';

						print '</table>';

						print '</td></tr>';
					}

					/*
					 * Cost management
					 */
					if ($user->rights->agefodd->session->margin) {
						$spend_cost = 0;
						$spend_cost_planned = 0;
						$spend_cost_engaged = 0;
						$cashed_cost = 0;

						print '<tr class="tr_order_cost"><td colspan="4">';
						print '<table class="border order_cost" width="100%">';
						print '<tr class="liste_titre">'."\n";
						print '		<td></td>'."\n";
						print '		<td></td>'."\n";
						print '		<td width="20%">' . $langs->trans("Planned") . '</td>'."\n";
						print '		<td width="20%">' . $langs->trans("Engaged") . '</td>'."\n";
						print '		<td width="20%">' . $langs->trans("Done") . '</td>'."\n";
						print '		<td width="20%">' . $langs->trans("Result") . '</td>'."\n";
						print '</tr>';

						// Le calcul du réalisé formateur doit tenir compte du fait que certaines factures fournisseurs règlent les prestations faites pour plusieurs session
						$agf_formateurs = new Agefodd_session_formateur($db);
						$nbform = $agf_formateurs->fetch_formateur_per_session($id);
						$invoice_trainer_array=array();
						if(!empty($agf_formateurs->lines)) {
							foreach ( $agf_formateurs->lines as $line_trainer ) {
								$contact_stat = new Contact($db);
								$contact_stat->fetch($line_trainer->socpeopleid);
								$contact_stat->fetch_thirdparty();
								$soc_trainer_id = $contact_stat->thirdparty->id;

								$agf_finn = new Agefodd_session_element($db);
								$agf_finn->fetch_by_session_by_thirdparty($id,$soc_trainer_id,array('\'invoice_supplier_trainer\'','\'invoice_supplierline_trainer\''));
								$invoice_trainer_array=array_merge($invoice_trainer_array,$agf_finn->lines);
							}
						}

						if(!empty($invoice_trainer_array)) {
							$cost_trainer_for_session=0;
							foreach($invoice_trainer_array as &$objj) {
								$fourninvoice = new FactureFournisseur($db);
								$fourninvoice->fetch($objj->fk_element);
								$agff = new Agsession($db);
								$res = $agff->fetch_all_by_order_invoice_propal('', '', '', '', '', '', '', $fourninvoice->id, '');
								if ($res<0) {
									setEventMessage($agff->error, 'errors');
								} else {
									$cost_trainer_for_session += price2num($fourninvoice->total_ht / count($agff->lines), 'MT');
								}
							}
						}

						print '<tr>'."\n";
						print '		<td ><strong>' . $langs->trans("TaxRevenue") . '</strong></td>'."\n";
						print '		<td >' . $langs->trans("AgfCoutFormation") . '</td>';
						print '		<td>' . price($agf->sell_price_planned) . '</td>'."\n";
						print '		<td>' . price($engaged_revenue) . '</td>'."\n";
						print '		<td>' . price($paied_revenue) . '</td>'."\n";
						print '		<td>' . price($paied_revenue - $agf->sell_price_planned) . '</td>'."\n";
						print '</tr>';

						print '<tr>'."\n";
						print '		<td rowspan="4" ><strong>' . $langs->trans("Expense") . '</strong></td>'."\n";
						print '		<td width="20%">' . $langs->trans("AgfCoutFormateur") . '</td>'."\n";
						print '		<td>' . price($agf->cost_trainer_planned) . '</td>'."\n";
						print '		<td>' . price($cost_trainer_engaged) . '</td>'."\n";
						print '		<td>' . price(/*$agf->cost_trainer*/$cost_trainer_for_session) . '</td>'."\n";
						print '		<td>' .price($agf->cost_trainer_planned - $cost_trainer_for_session) . '</td>'."\n";
						print '		</td>'."\n";
						print '</tr>';

						$spend_cost += $cost_trainer_for_session;
						$spend_cost_planned += $agf->cost_trainer_planned;
						$spend_cost_engaged += $cost_trainer_engaged;

						print '<tr>'."\n";
						print '		<td width="20%">' . $langs->trans("AgfCoutSalle") . '</td>';
						print '		<td>' . price($agf->cost_site_planned) . '</td>'."\n";
						print '		<td>' . price($cost_site_engaged) . '</td>'."\n";
						print '		<td>' . price($agf->cost_site) . '</td>'."\n";
						print '		<td>' . price($agf->cost_site_planned - $agf->cost_site) . '</td>'."\n";
						print '</tr>';
						$spend_cost += $agf->cost_site;
						$spend_cost_planned += $agf->cost_site_planned;
						$spend_cost_engaged += $cost_site_engaged;


						print '<tr>'."\n";
						print '		<td width="20%">' . $langs->trans("AgfCoutDeplacement") . '</td>';
						if (! empty($conf->global->AGF_VIEW_TRIP_AND_MISSION_COST_PER_PARTICIPANT))
						{
							if (! empty($agf->nb_stagiaire)) {
								$costparticipantplanned = price2num($agf->cost_trip_planned / $agf->nb_stagiaire, 'MT');
								$costparticipantengaged = price2num($cost_trip_engaged / $agf->nb_stagiaire, 'MT');
								$costparticipant = price2num($agf->cost_trip / $agf->nb_stagiaire, 'MT');
							} else {
								$costparticipantplanned = price2num($agf->cost_trip_planned, 'MT');
								$costparticipantengaged = price2num($cost_trip_engaged, 'MT');
								$costparticipant = price2num($agf->cost_trip, 'MT');
							}

							print '		<td>' . price($costparticipantplanned) . '</td>'."\n";
							print '		<td>' . price($costparticipantengaged) . '</td>'."\n";
							print '		<td>' . price($costparticipant) . '</td>'."\n";
							print '		<td>' . price($costparticipantplanned - $costparticipant) . '</td>'."\n";

							$spend_cost += $costparticipant;
							$spend_cost_planned += $costparticipantplanned;
							$spend_cost_engaged += $costparticipantengaged;
						} else {
							print '		<td>' . price($agf->cost_trip_planned) . '</td>'."\n";
							print '		<td>' . price($cost_trip_engaged) . '</td>'."\n";
							print '		<td>' . price($agf->cost_trip) . '</td>'."\n";
							print '		<td>' . price($agf->cost_trip_planned - $agf->cost_trip) . '</td>'."\n";
							$spend_cost += $agf->cost_trip;
							$spend_cost_planned += $agf->cost_trip_planned;
							$spend_cost_engaged += $cost_trip_engaged;
						}

						print '</tr>';


						print '<tr class="liste_total">'."\n";
						print '		<td width="20%"><strong>' . $langs->trans("AgfCoutTotal") . '</strong></td>';
						if ($agf->nb_stagiaire > 0) {
							$traineeCost = ' (' . $langs->trans('AgfTraineeCost') . ':' . price($spend_cost / $agf->nb_stagiaire) . ' ' . $langs->trans('Currency' . $conf->currency) . ')';
						}
						print '		<td><strong>' . price($spend_cost_planned) . '</strong></td>'."\n";
						print '		<td><strong>' . price($spend_cost_engaged) . '</strong></td>'."\n";
						print '		<td><strong>' . price($spend_cost) . '</strong></td>'."\n";
						print '		<td><strong>' . price($spend_cost_planned - $spend_cost) . '</strong></td>'."\n";
						print '</tr>';


						print '<tr class="liste_total">'."\n";
						print '		<td width="20%"><strong>' . $langs->trans("Benefits") . '</strong></td>'."\n";
						print '		<td></td>';

						print '		<td><strong>' . price($agf->sell_price_planned - $spend_cost_planned) . '</strong> (' . calcul_margin_percent($agf->sell_price_planned, $spend_cost_planned) . ')</td>';
						print '		<td><strong>' . price($engaged_revenue - $spend_cost_engaged) . '</strong> (' . calcul_margin_percent($engaged_revenue, $spend_cost_engaged) . ')</td>';
						print '		<td><strong>' . price($paied_revenue - $spend_cost) . '</strong> (' . calcul_margin_percent($paied_revenue, $spend_cost) . ')</td>';
						print '		<td></td>';

						print '		</tr>'."\n";
						print '</table>';

						print '</td></tr>';
					}



					/*
					 * Manage trainers
					 */
					print '<tr class="tr_order_trainer"><td colspan="4">';

					print '<table class="border order_trainer" width="100%">';

					$formateurs = new Agefodd_session_formateur($db);
					$nbform = $formateurs->fetch_formateur_per_session($agf->id);
					print '<tr><td width="20%" valign="top">';
					print $langs->trans("AgfFormateur");
					if ($nbform > 0)
						print ' (' . $nbform . ')';
					print '</td>';
					if ($nbform < 1) {
						print '<td style="text-decoration: blink;">' . $langs->trans("AgfNobody") . '</td></tr>';
					} else {
						print '<td>';

						print '<table class="nobordernopadding">';
						for($i = 0; $i < $nbform; $i ++) {
							print '<tr><td width="50%">';
							// Infos trainers
							print '<a href="' . dol_buildpath('/agefodd/trainer/card.php', 1) . '?id=' . $formateurs->lines[$i]->formid . '">';
							print img_object($langs->trans("ShowContact"), "contact") . ' ';
							print strtoupper($formateurs->lines[$i]->lastname) . ' ' . ucfirst($formateurs->lines[$i]->firstname) . '</a>';
							print ' ' . $formateurs->lines[$i]->getLibStatut(3);
							print '</td>';

							// Print trainer calendar
							if ($conf->global->AGF_DOL_TRAINER_AGENDA) {

								$alertday = false;
								require_once ('../class/agefodd_session_formateur_calendrier.class.php');
								$trainer_calendar = new Agefoddsessionformateurcalendrier($db);
								$result = $trainer_calendar->fetch_all($formateurs->lines[$i]->opsid);
								if ($result < 0) {
									setEventMessage($trainer_calendar->error, 'errors');
								}
								$blocNumber = count($trainer_calendar->lines);
								$old_date = 0;
								if (! empty($trainer_calendar->lines)) {
									print '<td>';

									print '<table class="nobordernopadding">';

									for($j = 0; $j < $blocNumber; $j ++) {
										if ((empty($agf->dated) || $trainer_calendar->lines[$j]->date_session < strtotime(date('Y-m-d', $agf->dated).' 00:00:00')) || (empty($agf->datef) || $trainer_calendar->lines[$j]->date_session > strtotime(date('Y-m-d', $agf->datef).' 00:00:00'))) {
											$alertday = true;
										}

										if ($j > 6) {
											$styledisplay = " style=\"display:none\" class=\"otherdatetrainer\" ";
										} else {
											$styledisplay = " ";
										}
										if ($trainer_calendar->lines[$j]->date_session != $old_date) {
											if ($j > 0) {
												print '<tr ' . $styledisplay . '>';
											}
											print '<td>';
											print dol_print_date($trainer_calendar->lines[$j]->date_session, 'daytext') . '&nbsp';
										} else {
											print ', ';
										}
										if (! $user->rights->agefodd->session->trainer) {
											print dol_print_date($trainer_calendar->lines[$j]->heured, 'hour') . ' - ' . dol_print_date($trainer_calendar->lines[$j]->heuref, 'hour');
										}

										if ($j == $blocNumber - 1) {
											print '</td></tr>';
										}

										$old_date = $trainer_calendar->lines[$j]->date_session;
									}

									// Print warning message if trainer calendar date are not set within session date
									if ($alertday) {
										print img_warning($langs->trans("AgfCalendarDayOutOfScopeTrainer"));
										print $langs->trans("AgfCalendarDayOutOfScopeTrainer");
										setEventMessage($langs->trans("AgfCalendarDayOutOfScopeTrainer"), 'warnings');
									}
									if ($blocNumber > 6) {
										print '<tr><td style="font-weight: bold; font-size:150%; cursor:pointer" class="switchtimetrainer">+</td></tr>';
									}

									print '</table>';

									print '</td>';

								}

							}
							print '</tr>';
						}

						print '</table>';
						print '<script>' . "\n";
						print '$(document).ready(function () { ' . "\n";
						print '		$(".switchtimetrainer").click(function(){' . "\n";
						print '         $(this).parent().parent().find(".otherdatetrainer").each(function(){$(this).toggle()});';
						print '			if ($(this).text()==\'+\') { ' . "\n";
						print '				$(this).text(\'-\'); ' . "\n";
						print '			}else { ' . "\n";
						print '				$(this).text(\'+\'); ' . "\n";
						print '			} ' . "\n";
						print '			' . "\n";
						print '		});' . "\n";
						print '});' . "\n";
						print '</script>' . "\n";
						print '</td>';
						print "</tr>\n";
					}
					print "</table>";

					print '<td></tr>';
					/*
					 * Display trainees
					 */

					print '<tr class="tr_order_trainee"><td colspan="4">';

					print '<table class="border order_trainee" width="100%">';
					$agf_opca = new Agefodd_opca($db);
					$stagiaires = new Agefodd_session_stagiaire($db);
					if (! empty($conf->global->AGF_DISPLAY_TRAINEE_GROUP_BY_STATUS)) {
						$resulttrainee = $stagiaires->fetch_stagiaire_per_session($agf->id, null, 0, 'ss.status_in_session,sa.nom');
					} else {
						$resulttrainee = $stagiaires->fetch_stagiaire_per_session($agf->id);
					}

					if ($resulttrainee < 0) {
						setEventMessage($stagiaires->error, 'errors');
					}
					$nbstag = count($stagiaires->lines);
					print '<tr><td  width="20%" valign="top" ';
					if ($nbstag < 1) {
						print '>' . $langs->trans("AgfParticipants") . '</td>';
						print '<td style="color:red;">' . $langs->trans("AgfNobody") . '</td></tr>'."\n";
					} else {
						print ' rowspan=' . ($nbstag) . '>' . $langs->trans("AgfParticipants");
						if ($nbstag > 1)
							print ' (' . $nbstag . ')';
						print '</td>'."\n";

						for($i = 0; $i < $nbstag; $i ++) {
							print '<td width="20px" align="center">' . ($i + 1) . '</td>'."\n";
							print '<td width="400px" style="border-right: 0px;">';
							// Infos stagiaires
							if (strtolower($stagiaires->lines[$i]->nom) == "undefined") {
								print $langs->trans("AgfUndefinedStagiaire");
							} else {
								$trainee_info = '<a href="' . dol_buildpath('/agefodd/trainee/card.php', 1) . '?id=' . $stagiaires->lines[$i]->id . '">';
								$trainee_info .= img_object($langs->trans("ShowContact"), "contact") . ' ';
								$trainee_info .= strtoupper($stagiaires->lines[$i]->nom) . ' ' . ucfirst($stagiaires->lines[$i]->prenom) . '</a>';
								$contact_static = new Contact($db);
								$contact_static->civility_id = $stagiaires->lines[$i]->civilite;
								$contact_static->civility_code = $stagiaires->lines[$i]->civilite;
								$trainee_info .= ' (' . $contact_static->getCivilityLabel() . ')';

								if ($agf->type_session == 1 && ! empty($conf->global->AGF_MANAGE_OPCA)) {
									print '<table class="nobordernopadding" width="100%"><tr class="noborder"><td colspan="2">';
									print $trainee_info . ' ' . $stagiaires->LibStatut($stagiaires->lines[$i]->status_in_session, 4);
									print '</td></tr>'."\n";

									$agf_opca->getOpcaForTraineeInSession($stagiaires->lines[$i]->socid, $agf->id, $stagiaires->lines[$i]->stagerowid);
									print '<tr class="noborder"><td class="noborder" width="45%">' . $langs->trans("AgfSubrocation") . '</td>';
									if ($agf_opca->is_OPCA == 1) {
										$chckisOPCA = 'checked="checked"';
									} else {
										$chckisOPCA = '';
									}
									print '<td><input type="checkbox" class="flat" name="isOPCA" value="1" ' . $chckisOPCA . ' disabled="disabled" readonly="readonly"/></td></tr>'."\n";

									print '<tr><td>' . $langs->trans("AgfOPCAName") . '</td>'."\n";
									print '	<td>';
									if (DOL_VERSION < 6.0) {
										print '<a href="' . dol_buildpath('/societe/soc.php', 1) . '?socid=' . $agf_opca->fk_soc_OPCA . '">' . $agf_opca->soc_OPCA_name . '</a>';
									} else {
										print '<a href="' . dol_buildpath('/societe/card.php', 1) . '?socid=' . $agf_opca->fk_soc_OPCA . '">' . $agf_opca->soc_OPCA_name . '</a>';
									}
									print '</td></tr>'."\n";

									print '<tr><td>' . $langs->trans("AgfOPCAContact") . '</td>';
									print '	<td>';
									print '<a href="' . dol_buildpath('/contact/card.php', 1) . '?id=' . $agf_opca->fk_socpeople_OPCA . '">' . $agf_opca->contact_name_OPCA . '</a>';
									print '</td></tr>'."\n";

									print '<tr><td width="20%">' . $langs->trans("AgfOPCANumClient") . '</td>';
									print '<td>' . $agf_opca->num_OPCA_soc . '</td></tr>';

									print '<tr><td width="20%">' . $langs->trans("AgfOPCADateDemande") . '</td>';

									print '<td><table class="nobordernopadding"><tr>';

									print '<td>';
									print dol_print_date($agf_opca->date_ask_OPCA, 'daytext');
									print '</td><td>';
									print '</td></tr>'."\n";
									print "</table>"."\n";

									print '</td></tr>'."\n";

									print '<tr><td width="20%">' . $langs->trans("AgfOPCANumFile") . '</td>';
									print '<td>' . $agf_opca->num_OPCA_file . '</td></tr>';

									print '</table>'."\n";
								} else {
									print $trainee_info . ' ' . $stagiaires->LibStatut($stagiaires->lines[$i]->status_in_session, 4);
									if (! empty($stagiaires->lines[$i]->hour_foad) && ! empty($conf->global->AGF_MANAGE_BPF)) {
										print '<br>' . $langs->trans('AgfHourFOAD') . ' : ' . $stagiaires->lines[$i]->hour_foad . ' ' . $langs->trans('Hour') . '(s)';
									}
								}
							}
							print '</td>'."\n";
							print '<td style="border-left: 0px; border-right: 0px;">';
							// Infos organisme de rattachement
							if ($stagiaires->lines[$i]->socid) {
								$socstatic = new Societe($db);
								$socstatic->fetch($stagiaires->lines[$i]->socid);
								if (! empty($socstatic->id)) {
									print $socstatic->getNomUrl(1);
								}
								unset($socstatic);
							} else {
								print '&nbsp;';
							}
							print '</td>'."\n";
							print '<td style="border-left: 0px;" class="traineefin">';
							// Infos mode de financement
							if (($stagiaires->lines[$i]->type) && (! empty($conf->global->AGF_USE_STAGIAIRE_TYPE))) {
								print '<div class=adminaction>';
								print '<span>' . stripslashes($stagiaires->lines[$i]->type) . '</span></div>';
							} else {
								print '&nbsp;';
							}
							print '</td>'."\n";

							// Infos thirdparty linked for doc
							print '<td style="border-left: 0px;" class="traineefk_soc_link">';
							if (! empty($stagiaires->lines[$i]->fk_soc_link)) {
								$socstatic = new Societe($db);
								$socstatic->fetch($stagiaires->lines[$i]->fk_soc_link);
								if (! empty($socstatic->id)) {
									print $langs->trans('AgfTraineeSocDocUse') . ':' . $socstatic->getNomUrl(1);
								}
								unset($socstatic);
							} else {
								print '&nbsp;';
							}
							if (! empty($stagiaires->lines[$i]->fk_soc_requester)) {
								$socstatic = new Societe($db);
								$socstatic->fetch($stagiaires->lines[$i]->fk_soc_requester);
								if (! empty($socstatic->id)) {
									print '<br>' . $langs->trans('AgfTypeRequester') . ':' . $socstatic->getNomUrl(1);
								}
								unset($socstatic);
							} else {
								print '&nbsp;';
							}
							if (! empty($stagiaires->lines[$i]->fk_socpeople_sign)) {
								$contactstatic = new Contact($db);
								$contactstatic->fetch($stagiaires->lines[$i]->fk_socpeople_sign);
								if (! empty($contactstatic->id)) {
									print '<br>' . $langs->trans('AgfContactSign') . ':' . $contactstatic->getNomUrl(1);
								}
							} else {
								print '&nbsp;';
							}
							print '</td>';
							print "</tr>"."\n";
						}
					}
					print "</table>"."\n";

					print '</td></tr>';
					print '</table>';

					$parameters = array();
					$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $agf, $action); // Note that $action and $object may have been modified by hook
					print $hookmanager->resPrint;

					print '</div>';
					print '</div>';
				}
			} else {
				print $langs->trans('AgfNoSession');
			}
		} else {
			setEventMessage($agf->error, 'errors');
		}
	}
}

/*
 * Action tabs
 *
 */

print '<div class="tabsAction">';

if ($action != 'create' && $action != 'edit' && (! empty($agf->id))) {

	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $agf, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		if ($user->rights->agefodd->creer) {
			print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=validateregistrants&id=' . $id . '">' . $langs->trans('AgfValidateRegistrants') . '</a>';
		} else {
			print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfValidateRegistrants') . '</a>';
		}
		if ($user->rights->agefodd->modifier && ! $user->rights->agefodd->session->trainer) {
			print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&id=' . $id . '">' . $langs->trans('Modify') . '</a>';
		} else {
			print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';
		}
		if ($user->rights->agefodd->creer) {
			print '<a class="butAction" href="subscribers.php?action=edit&id=' . $id . '">' . $langs->trans('AgfModifySubscribersAndSubrogation') . '</a>';
		} else {
			print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfModifySubscribersAndSubrogation') . '</a>';
		}
		if ($user->rights->agefodd->creer) {
			print '<a class="butAction" href="calendar.php?action=edit&id=' . $id . '">' . $langs->trans('AgfModifyCalendar') . '</a>';
		} else {
			print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfModifyCalendar') . '</a>';
		}

		if ($user->rights->agefodd->creer) {
			print '<a class="butAction" href="trainer.php?action=edit&id=' . $id . '">' . $langs->trans('AgfModifyTrainer') . '</a>';
		} else {
			print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfModifyTrainer') . '</a>';
		}

		if ($user->rights->agefodd->supprimer && ! $user->rights->agefodd->session->trainer) {
			print '<a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?action=delete&id=' . $id . '">' . $langs->trans('Delete') . '</a>';
		} else {
			print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Delete') . '</a>';
		}
		if (! $user->rights->agefodd->session->trainer) {
			if ($agf->status != 4) {
				$button = $langs->trans('AgfArchiver');
				$arch = 1;
			} else {
				$button = $langs->trans('AgfActiver');
				$arch = 0;
			}
			if ($user->rights->agefodd->modifier) {
				print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?arch=' . $arch . '&id=' . $id . '">' . $button . '</a>';
			} else {
				print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $button . '</a>';
			}
		}
		if ($user->rights->agefodd->creer) {
			print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=clone&id=' . $id . '">' . $langs->trans('ToClone') . '</a>';
		}
		if (! $user->rights->agefodd->session->trainer) {
			print '<a class="butAction" href="' . dol_buildpath('/agefodd/session/history.php', 1) . '?id=' . $id . '">' . $langs->trans('AgfViewActioncomm') . '</a>';
		}
	}
}

print '</div>';

llxFooter();
$db->close();
