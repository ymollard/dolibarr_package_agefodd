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
 * \file agefodd/session/convention.php
 * \ingroup agefodd
 * \brief Manage convention template
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../lib/agefodd.lib.php');
require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_session_calendrier.class.php');
require_once ('../class/agefodd_formation_catalogue.class.php');
require_once ('../class/agefodd_session_element.class.php');
require_once ('../class/agefodd_session_formateur.class.php');
require_once ('../class/agefodd_convention.class.php');
require_once ('../class/agefodd_contact.class.php');
require_once ('../class/agefodd_place.class.php');
require_once ('../class/html.formagefodd.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');
require_once ('../class/agefodd_session_stagiaire.class.php');
require_once ('../class/agefodd_stagiaire.class.php');
require_once ('../core/modules/agefodd/modules_agefodd.php');
require_once (DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
require_once (DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php');
require_once (DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$langs->load('propal');
$langs->load('bills');
$langs->load('orders');

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$socid = GETPOST('socid', 'int');
$sessid = GETPOST('sessid', 'int');
$arch = GETPOST('arch', 'int');
$model_doc = GETPOST('model_doc', 'alpha');

$langs->load("companies");

/*
 * Actions delete
 */
if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->creer) {

	$agf = new Agefodd_convention($db);
	$result = $agf->remove($id);

	if ($result > 0) {
		Header('Location: document.php?id=' . $sessid);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

/*
 * Actions archive/active (convention de formation)
 */
if ($action == 'arch_confirm_delete' && $user->rights->agefodd->creer) {
	if ($_POST["confirm"] == "yes") {
		$agf = new Agefodd_convention($db);

		$result = $agf->fetch(0, 0, $id);

		$agf->archive = $arch;
		$result = $agf->update($user);

		if ($result > 0) {
			Header('Location: ' . $_SERVER['PHP_SELF'] . '?sessid=' . $sessid . '&socid=' . $agf->socid);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	} else {
		Header('Location: ' . $_SERVER['PHP_SELF'] . '?sessid=' . $sessid);
		exit();
	}
}

/*
 * Action generate fiche pédagogique
 */
if ($action == 'builddoc' && $user->rights->agefodd->creer) {
	$agf = new Agefodd_convention($db);

	$result = $agf->fetch(0, 0, $id);

	// Define output language
	$newlang = (!empty($conf->global->MAIN_MULTILANGS)?$agf->doc_lang:'');
	if (! empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	} else {
		$outputlangs=$langs;
	}
	$model = $agf->model_doc;
	$model = str_replace('pdf_', '', $model);

	$file = 'convention' . '_' . $agf->sessid . '_' . $agf->socid . '_' . $agf->id . '.pdf';

	$field = 'id';
	if (strpos($agf->model_doc, 'rfltr_agefodd') !== false) {
		$path_external_model = '/referenceletters/core/modules/referenceletters/pdf/pdf_rfltr_agefodd.modules.php';
		$id_model_rfltr = ( int ) strtr($agf->model_doc, array(
				'rfltr_agefodd_' => ''
		));
		$field = 'sessid'; // Si on est sur un modèle externe module courrier, on charge toujours l'objet session dans lequel se trouvent toutes les données
	}

	$result = agf_pdf_create($db, $agf->{$field}, '', $model, $outputlangs, $file, $agf->socid, '', $path_external_model, $id_model_rfltr, $agf);

	if ($result > 0) {
		Header("Location: " . dol_buildpath('/agefodd/session/document.php', 1) . "?id=" . $agf->sessid . '&socid=' . $agf->socid);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

/*
 * Action update (convention de formation)
 */
if ($action == 'update' && $user->rights->agefodd->creer) {
	if (! $_POST["cancel"]) {
		$agf = new Agefodd_convention($db);

		$result = $agf->fetch(0, 0, $id);

		$intro1 = GETPOST('intro1', 'none');
		$intro2 = GETPOST('intro2', 'none');
		$art1 = GETPOST('art1', 'none');
		$art2 = GETPOST('art2', 'none');
		$art3 = GETPOST('art3', 'none');
		$art4 = GETPOST('art4', 'none');
		$art5 = GETPOST('art5', 'none');
		$art6 = GETPOST('art6', 'none');
		$art7 = GETPOST('art7', 'none');
		$art8 = GETPOST('art8', 'none');
		$art9 = GETPOST('art9', 'none');
		$sig = GETPOST('sig', 'none');
		$notes = GETPOST('notes', 'none');
		$model_doc = GETPOST('model_doc', 'alpha');
		$doc_lang = GETPOST('doc_lang', 'alpha');
		$only_product_session = GETPOST('only_product_session', 'int');
		$traine_list = GETPOST('trainee_id', 'array');

		$idtypeelement = GETPOST('idtypelement', 'alpha');
		if (! empty($idtypeelement)) {
			$idtypeelement_array = explode(':', $idtypeelement);
			$fk_element = $idtypeelement_array[0];
			$element_type = $idtypeelement_array[1];
		}

		if (! empty($intro1))
			$agf->intro1 = $intro1;
		if (! empty($intro2))
			$agf->intro2 = $intro2;
		if (! empty($art1))
			$agf->art1 = $art1;
		if (! empty($art2))
			$agf->art2 = $art2;
		if (! empty($art3))
			$agf->art3 = $art3;
		if (! empty($art4))
			$agf->art4 = $art4;
		if (! empty($art5))
			$agf->art5 = $art5;
		if (! empty($art6))
			$agf->art6 = $art6;
		if (! empty($art7))
			$agf->art7 = $art7;
		if (! empty($art8))
			$agf->art8 = $art8;
		if (! empty($art9))
			$agf->art9 = $art9;
		if (! empty($sig))
			$agf->sig = $sig;
		if (! empty($fk_element))
			$agf->fk_element = $fk_element;
		if (! empty($element_type))
			$agf->element_type = $element_type;
		if (! empty($model_doc))
			$agf->model_doc = $model_doc;
		if (! empty($doc_lang))
			$agf->doc_lang = $doc_lang;

		$agf->only_product_session = $only_product_session;
		$agf->notes = $notes;
		$agf->socid = $socid;
		$agf->sessid = $sessid;
		$agf->line_trainee = $traine_list;

		$result = $agf->update($user);

		if ($result > 0) {
			Header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
			exit();
		} else {
			setEventMessage($agf->error, 'errors');
		}
	} else {
		Header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
		exit();
	}
}

/*
 * Action create (training contract)
 */

if ($action == 'create_confirm' && $user->rights->agefodd->creer) {
	if (! $_POST["cancel"]) {
		$agf = new Agefodd_convention($db);

		$intro1 = GETPOST('intro1', 'none');
		$intro2 = GETPOST('intro2', 'none');
		$art1 = GETPOST('art1', 'none');
		$art2 = GETPOST('art2', 'none');
		$art3 = GETPOST('art3', 'none');
		$art4 = GETPOST('art4', 'none');
		$art5 = GETPOST('art5', 'none');
		$art6 = GETPOST('art6', 'none');
		$art7 = GETPOST('art7', 'none');
		$art8 = GETPOST('art8', 'none');
		$art9 = GETPOST('art9', 'none');
		$sig = GETPOST('sig', 'none');
		$notes = GETPOST('notes', 'none');
		$model_doc = GETPOST('model_doc', 'alpha');
		$doc_lang = GETPOST('doc_lang', 'alpha');
		$traine_list = GETPOST('trainee_id', 'array');
		$only_product_session = GETPOST('only_product_session', 'int');

		$idtypeelement = GETPOST('idtypelement', 'alpha');
		if (! empty($idtypeelement)) {
			$idtypeelement_array = explode(':', $idtypeelement);
			$fk_element = $idtypeelement_array[0];
			$element_type = $idtypeelement_array[1];
		}

		if (empty($fk_element) && empty($conf->global->AGF_ALLOW_CONV_WITHOUT_FINNACIAL_DOC)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentities('AgfElementToUse')), 'errors');
			$action = 'create';
		} else {

			if (! empty($intro1))
				$agf->intro1 = $intro1;
			if (! empty($intro2))
				$agf->intro2 = $intro2;
			if (! empty($art1))
				$agf->art1 = $art1;
			if (! empty($art2))
				$agf->art2 = $art2;
			if (! empty($art3))
				$agf->art3 = $art3;
			if (! empty($art4))
				$agf->art4 = $art4;
			if (! empty($art5))
				$agf->art5 = $art5;
			if (! empty($art6))
				$agf->art6 = $art6;
			if (! empty($art7))
				$agf->art7 = $art7;
			if (! empty($art8))
				$agf->art8 = $art8;
			if (! empty($art9))
				$agf->art9 = $art9;
			if (! empty($sig))
				$agf->sig = $sig;
			if (! empty($notes))
				$agf->notes = $notes;
			if (! empty($fk_element))
				$agf->fk_element = $fk_element;
			if (! empty($element_type))
				$agf->element_type = $element_type;
			if (! empty($model_doc))
				$agf->model_doc = $model_doc;
			if (! empty($doc_lang))
				$agf->doc_lang = $doc_lang;

			$agf->only_product_session = $only_product_session;
			$agf->socid = $socid;
			$agf->sessid = $sessid;
			$agf->line_trainee = $traine_list;

			$result = $agf->create($user);

			if ($result > 0) {
				Header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $result);
				exit();
			} else {
				setEventMessage($agf->error, 'errors');
			}
		}
	} else {
		Header('Location: ' . $_SERVER['PHP_SELF'] . '?sessid=' . $sessid);
		exit();
	}
}

if ((empty($id)) && (empty($socid)) && (empty($action))) {
	Header('Location: ' . $_SERVER['PHP_SELF'] . '?sessid=' . $sessid . '&action=create');
	exit();
}

/*
 * View
 */

$extrajs = array(
		'/agefodd/includes/multiselect/js/ui.multiselect.js'
);
$extracss = array(
		'/agefodd/includes/multiselect/css/ui.multiselect.css',
		'/agefodd/css/agefodd.css'
);

llxHeader('', $langs->trans("AgfConvention"), '', '', '', '', $extrajs, $extracss);

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

/*
 * Affichage de la fiche convention en mode création
 */
if ($action == 'create' && $user->rights->agefodd->creer) {

	$agf = new Agsession($db);
	$resql = $agf->fetch($sessid);

	// We try to find is a convetion have already been done for this customers
	// If yes we retrieve the old value
	// else we use default
	$agf_last = new Agefodd_convention($db);
	$result = $agf_last->fetch_last_conv_per_socity($socid);
	if ($result > 0) {
		$agf_conv = new Agefodd_convention($db);
		if (! empty($agf_last->sessid)) {
			$result = $agf_conv->fetch($agf_last->sessid, $socid);
			if ($agf_last->sessid) {
				$last_conv = 'ok';
			}
		}
	}

	// intro1
	$statut = getFormeJuridiqueLabel($mysoc->forme_juridique_code);
	$intro1 = $langs->trans('AgfConvIntro1_1') . ' ' . $mysoc->name . ', ' . $statut;
	if (! empty($mysoc->capital)) {
		$intro1 .= ' ' . $langs->trans('AgfConvIntro1_2');
		$intro1 .= ' ' . $mysoc->capital . ' ' . $langs->trans("Currency" . $conf->currency);
	}

	$addr = preg_replace("/\r|\n/", " ", $mysoc->address . ', ' . $mysoc->zip . ' ' . $mysoc->town);
	$intro1 .= $langs->trans('AgfConvIntro1_3') . ' ' . $addr;
	if (! empty($mysoc->idprof1)) {
		$intro1 .= $langs->trans('AgfConvIntro1_4') . ' ' . $mysoc->idprof1;
	}
	if (empty($conf->global->AGF_ORGANISME_NUM)) {
		$intro1 .= ' ' . $langs->trans('AgfConvIntro1_5') . ' ' . $conf->global->AGF_ORGANISME_PREF;
	} else {
		$intro1 .= ' ' . $langs->trans('AgfConvIntro1_6');
		$intro1 .= ' ' . $conf->global->AGF_ORGANISME_PREF . ' ' . $langs->trans('AgfConvIntro1_7') . ' ' . $conf->global->AGF_ORGANISME_NUM . ' ' . $langs->trans('AgfConvOrg1');
	}
	if (! empty($conf->global->AGF_ORGANISME_REPRESENTANT)) {
		$intro1 .= $langs->trans('AgfConvIntro1_8') . ' ' . $conf->global->AGF_ORGANISME_REPRESENTANT . $langs->trans('AgfConvIntro1_9');
	}

	// intro2
	// Get trhidparty info
	$agf_soc = new Societe($db);
	$result = $agf_soc->fetch($socid);

	// intro2
	$addr = preg_replace("/\r|\n/", " ", $agf_soc->address . ", " . $agf_soc->zip . " " . $agf_soc->town);
	$intro2 = $langs->trans('AgfConvIntro2_1') . ' ' . $agf_soc->name;
	if (! empty($addr)) {
		$intro2 .= ", " . $langs->trans('AgfConvIntro2_2') . ' ' . $addr;
	}
	if (! empty($agf_soc->idprof2)) {
		$intro2 .= ", " . $langs->trans('AgfConvIntro2_3') . ' ' . $agf_soc->idprof2;
	}

	$signataire = '';
	$contactname = trim($agf->contactname);
	if (! empty($contactname)) {
		$intro2 .= ', ' . $langs->trans('AgfConvIntro2_4') . ' ';
		$intro2 .= ucfirst(strtolower($agf->contactcivilite)) . ' ' . $agf->contactname;
		$intro2 .= ' ' . $langs->trans('AgfConvIntro2_5');
	} else {

		// Trainee link to the company convention
		$stagiaires = new Agefodd_session_stagiaire($db);
		$result = $stagiaires->fetch_stagiaire_per_session($sessid, $socid, 1);
		if ($result < 0) {
			setEventMessage($stagiaires->error, 'errors');
		} else {
			if (is_array($stagiaires->lines) && count($stagiaires->lines) > 0) {

				foreach ( $stagiaires->lines as $line ) {
					if (! empty($line->fk_socpeople_sign)) {
						$socpsign = new Contact($db);
						$socpsign->fetch($line->fk_socpeople_sign);
						$signataire = $socpsign->getFullName($langs) . ' ';
					}
				}
				if (! empty($signataire)) {
					$intro2 .= ', ' . $langs->trans('AgfConvIntro2_4') . ' ' . $signataire . ' ' . $langs->trans('AgfConvIntro2_5');
				}
			}
		}
	}

	// article 1
	// Mise en page (Cf. fonction "liste_a_puce()" du fichier pdf_convention_modele.php)
	// Si la ligne commence par:
	// '!# ' aucune puce ne sera générée, la ligne commence sur la magre gauche
	// '# ', une puce de premier niveau est mis en place
	// '## ', une puce de second niveau est mis en place
	// '### ', une puce de troisième niveau est mis en place
	$art1 = $langs->trans('AgfConvArt1_1') . "\n";
	$art1 .= $langs->trans('AgfConvArt1_2') . ' ' . $agf->formintitule . ' ' . $langs->trans('AgfConvArt1_3') . " \n" . "\n";

	$obj_peda = new Formation($db);
	$resql = $obj_peda->fetch_objpeda_per_formation($agf->formid);
	if (count($obj_peda->lines) > 0) {
		$art1 .= $langs->trans('AgfConvArt1_4') . "\n";
	}
	foreach ( $obj_peda->lines as $line ) {
		$art1 .= "-	" . $line->intitule . "\n";
	}
	if (count($obj_peda->lines) > 0) {
		$art1 .= "\n";
	}
	$art1 .= $langs->trans('AgfConvArt1_6') . "\n" . "\n";
	$art1 .= $langs->trans('AgfConvArt1_7');

	if ($agf->dated == $agf->datef)
		$art1 .= $langs->trans('AgfConvArt1_8') . ' ' . dol_print_date($agf->datef);
	else
		$art1 .= $langs->trans('AgfConvArt1_9') . ' ' . dol_print_date($agf->dated) . ' ' . $langs->trans('AgfConvArt1_10') . ' ' . dol_print_date($agf->datef);

	$art1 .= "\n";

	// Durée de formation
	$art1 .= $langs->trans('AgfConvArt1_11') . ' ' . $agf->duree_session . ' ' . $langs->trans('AgfConvArt1_12') . ' ' . "\n";

	$calendrier = new Agefodd_sesscalendar($db);
	$resql = $calendrier->fetch_all($sessid);
	$blocNumber = count($calendrier->lines);
	$old_date = 0;
	$duree = 0;
	for($i = 0; $i < $blocNumber; $i ++) {
		if ($calendrier->lines[$i]->date_session != $old_date) {
			if ($i > 0)
				$art1 .= "), ";
			$art1 .= dol_print_date($calendrier->lines[$i]->date_session, 'daytext') . ' (';
		} else
			$art1 .= '/';
		$art1 .= dol_print_date($calendrier->lines[$i]->heured, 'hour');
		$art1 .= ' - ';
		$art1 .= dol_print_date($calendrier->lines[$i]->heuref, 'hour');
		if ($i == $blocNumber - 1)
			$art1 .= ').' . "\n";

		$old_date = $calendrier->lines[$i]->date_session;
	}

	// Formateur
	$formateurs = new Agefodd_session_formateur($db);
	$nbform = $formateurs->fetch_formateur_per_session($agf->id);
	foreach ( $formateurs->lines as $trainer ) {
		$TTrainer[] = $trainer->firstname . ' ' . $trainer->lastname;
	}
	if ($nbform > 0) {
		$art1 .= "\n" . $langs->trans('AgfTrainingTrainer') . ' : ' . implode(', ', $TTrainer) . "\n";
	}

	$art1 .= "\n" . $langs->trans('AgfConvArt1_13');

	if ($conf->global->AGF_CONV_ADD_SANCTION) {
		$training = new Formation($db);
		$training->fetch($agf->formid);
		if (!empty($training->sanction)) {
			$art1 .=  "\n".$training->sanction;
		}
	}
	$art1 .= "\n" . "\n";

	$art1 .= $langs->trans('AgfConvArt1_14') . ' Nb_participants';
	$art1 .= $langs->trans('AgfConvArt1_17') . "\n" . "\n";
	// Adresse lieu de formation
	$agf_place = new Agefodd_place($db);
	$resql3 = $agf_place->fetch($agf->placeid);
	$addr = preg_replace("/\r|\n/", " ", $agf_place->adresse . ", " . $agf_place->cp . " " . $agf_place->ville);
	$art1 .= $langs->trans('AgfConvArt1_18') . $agf_place->ref_interne . $langs->trans('AgfConvArt1_19') . ' ' . $addr . '.';

	// texte 2
	if ($agf_conv->art2)
		$art2 = $agf_conv->art2;
	else {
		$art2 = $langs->trans('AgfConvArt2_1');
	}

	// texte3
	$art3 = $langs->trans('AgfConvArt3_1');

	// texte 4
	if (empty($conf->global->FACTURE_TVAOPTION)) {
		$art4 = $langs->trans('AgfConvArt4_1');
	} else {
		$art4 = $langs->trans('AgfConvArt4_3');
	}
	$art4 .= "\n" . $langs->trans('AgfConvArt4_2');
	if (!empty($conf->global->AGF_CONV_SOUSTRAITANCE)) {
		$art4 .= "\n" . $langs->trans('AgfConvArt4_4',$mysoc->name);
	}

	// texte 5
	if ($agf_conv->art5)
		$art5 = $agf_conv->art5;
	else {
		$listOPCA = '';

		if (! empty($agf->type_session)) { // Session inter-entreprises : OPCA gérés par participant
			dol_include_once('/agefodd/class/agefodd_opca.class.php');
			$stagiaires = new Agefodd_session_stagiaire($db);
			$resulttrainee = $stagiaires->fetch_stagiaire_per_session($agf->id);

			foreach ( $stagiaires->lines as $line ) {

				$opca = new Agefodd_opca($db);
				$opca->getOpcaForTraineeInSession($line->socid, $agf->id, $line->stagerowid);

				if (! empty($opca->soc_OPCA_name)) { // Au moins un participant avec un OPCA
					$listOPCA = ' (' . $langs->trans('AgfMailTypeContactOPCA') . ' : List_OPCA)';
					break;
				}
			}
		} elseif (! empty($agf->fk_soc_OPCA)) {
			$listOPCA = ' (' . $langs->trans('AgfMailTypeContactOPCA') . ' : List_OPCA)';
		}

		$art5 = $langs->trans('AgfConvArt5_1', $listOPCA);
	}

	// texte 9
	if ($agf_conv->art9)
		$art9 = $agf_conv->art9;
	else {
		$art9 = $langs->trans('AgfConvArt9_1') . "\n";
		$art9 .= $langs->trans('AgfConvArt9_2');
	}

	// article 6
	if ($agf_conv->art6) {
		$art6 = $agf_conv->art6;
	} else {
		$art6 = $langs->trans('AgfConvArt6_1') . "\n" . "\n";
		$art6 .= $langs->trans('AgfConvArt6_2') . "\n" . "\n";
		$art6 .= $langs->trans('AgfConvArt6_3') . "\n" . "\n";
		$art6 .= $langs->trans('AgfConvArt6_4') . "\n" . "\n";
	}

	// article 7
	if ($agf_conv->art7)
		$art7 = $agf_conv->art7;
	else {
		$art7 = $langs->trans('AgfConvArt7_1') . ' ';
		$art7 .= $langs->trans('AgfConvArt7_2') . ' ' . $mysoc->town . ".";
	}

	// Signature du client
	$sig = $agf_soc->name . "\n";
	$sig .= $langs->trans('AgfConvArtSigCli') . ' ';
	// $sig .= ucfirst(strtolower($agf_contact->civilite)) . ' ' . $agf_contact->firstname . ' ' . $agf_contact->lastname . " (*)";
	$contactname = trim($agf->contactname);
	if (! empty($contactname)) {
		$sig .= $agf->contactname;
	} elseif (! empty($signataire)) {
		$sig .= $signataire;
	}
	$sig .= " (*)";

	print_fiche_titre($langs->trans("AgfNewConv"));

	print '<div class="warning">';
	($last_conv == 'ok') ? print $langs->trans("AgfConvLastWarning") : print $langs->trans("AgfConvDefaultWarning");
	print '</div>' . "\n";
	print '<form name="create" action="convention.php" method="post">' . "\n";
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
	print '<input type="hidden" name="action" value="create_confirm">' . "\n";
	print '<input type="hidden" name="sessid" value="' . $sessid . '">' . "\n";
	print '<input type="hidden" name="socid" value="' . $socid . '">' . "\n";

	print '<table class="border" width="100%">' . "\n";

	print '<tr><td valign="top" width="200px">' . $langs->trans("Company") . '</td>';
	print '<td>' . $agf_soc->name . '</td></tr>';

	print '<tr><td valign="top" width="200px">' . $langs->trans("AgfElementToUse") . '</td>';
	print '<td>';

	$agf_element = new Agefodd_session_element($db);
	$result = $agf_element->fetch_by_session_by_thirdparty($sessid, $socid);
	if ($result < 0) {
		setEventMessage($agf_element->error, 'errors');
	}
	print '<select id="idtypelement" name="idtypelement" class="flat">';
	foreach ( $agf_element->lines as $line ) {

		if ($line->element_type == 'propal' && ! empty($line->propalref)) {
			$propal = new Propal($db);
			$propal->fetch($line->fk_element);
			// Propal cannot be draft or not signed to generate a convention
			if (($propal->statut != 3) && ($propal->statut != 0)) {
				print '<option value="' . $line->fk_element . ':' . $line->element_type . '">' . $langs->trans('Proposal') . ' ' . $line->propalref . ' ' . $propal->getLibStatut(1) . '</option>';
			}
		}
		if ($line->element_type == 'order' && ! empty($line->comref)) {
			$order = new Commande($db);
			$order->fetch($line->fk_element);
			// Order cannot be cancelled or draft to generate a convention
			if (($order->statut != - 1) && ($order->statut != 0)) {
				print '<option value="' . $line->fk_element . ':' . $line->element_type . '">' . $langs->trans('Order') . ' ' . $line->comref . ' ' . $order->getLibStatut(1) . '</option>';
			}
		}
		if ($line->element_type == 'invoice' && ! empty($line->facnumber)) {
			$invoice = new Facture($db);
			$invoice->fetch($line->fk_element);
			// Order cannot be draft to generate a convention
			if ($invoice->statut != 0) {
				print '<option value="' . $line->fk_element . ':' . $line->element_type . '">' . $langs->trans('Invoice') . ' ' . $line->facnumber . ' ' . $invoice->getLibStatut(1) . '</option>';
			}
		}
	}
	print '</select>';
	if (! empty($agf->fk_product)) {
		print '<BR>';
		print '<input type="checkbox" value="1" name="only_product_session" id="only_product_session">' . $langs->trans('AgfOutputOnlySessionProductInConv');
	}
	print '</td></tr>';

	print '<tr><td valign="top" width="200px">' . $langs->trans("AgfConvModelDoc") . '</td>';
	print '<td>';
	print $formAgefodd->select_conv_model('', 'model_doc');
	print '</td></tr>';

	if (!empty($conf->global->MAIN_MULTILANGS)) {
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
		$formadmin=new FormAdmin($db);
		$langs->load('admin');
		if (empty($agf->doc_lang)) {
			if (!empty($agf_soc->default_lang)) {
				$defaultlang = $agf_soc->default_lang;
			} else {
				$defaultlang = $langs->getDefaultLang();
			}
		}
		$morecss='maxwidth150';
		if (! empty($conf->browser->phone)) $morecss='maxwidth100';
		print '<tr><td valign="top" width="200px">' . $langs->trans("DefaultLanguage") . '</td>';
		print '<td>';
		print $formadmin->select_language($defaultlang, 'doc_lang', 0, 0, 0, 0, 0, $morecss);
		print '</td></tr>';
	}

	print '<tr><td valign="top" width="200px">' . $langs->trans("AgfConvTrainees") . '</td>';
	print '<td>';

	$options_trainee_array = array();
	$options_trainee_array_id = array();

	$stagiaires = new Agefodd_session_stagiaire($db);
	// Trainee link to thhe company convention
	$stagiaires->fetch_stagiaire_per_session($sessid, $socid, 1);

	foreach ( $stagiaires->lines as $traine_line ) {
		// $options_trainee_array_selected [$traine_line->stagerowid] = $traine_line->nom . ' ' . $traine_line->prenom . ' (' . $traine_line->socname . ')';
		$options_trainee_array_selected_id[] = $traine_line->stagerowid;
	}

	$stagiaires->fetch_stagiaire_per_session($sessid);
	foreach ( $stagiaires->lines as $traine_line ) {
		// if (!array_key_exists($traine_line->stagerowid,$options_trainee_array_selected)) {
		$options_trainee_array[$traine_line->stagerowid] = $traine_line->nom . ' ' . $traine_line->prenom . ' (' . $traine_line->socname . ')';
		// $options_trainee_array_id [] = $traine_line->stagerowid;
		// }
	}

	print $formAgefodd->agfmultiselectarray('trainee_id', $options_trainee_array, $options_trainee_array_selected_id);
	print '</td></tr>';

	print '<tr class="standardConventionModel"><td valign="top" width="200px">' . $langs->trans("AgfConventionIntro1") . '</td>';
	print '<td><textarea name="intro1" rows="7" cols="5" class="flat" style="width:560px;">' . $intro1 . '</textarea></td></tr>';

	print '<tr class="standardConventionModel"><td valign="top">' . $langs->trans("AgfConventionIntro2") . '</td>';
	print '<td><textarea name="intro2" rows="7" cols="5" class="flat" style="width:560px;">' . $intro2 . '</textarea></td></tr>';

	$chapter = 1;

	print '<tr class="standardConventionModel"><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
	print '<td><textarea name="art1" rows="7" cols="5" class="flat" style="width:560px;">' . $art1 . '</textarea>';
	print img_picto($langs->trans('AgfExplainNbparticipants'), 'info') . $langs->trans('AgfExplainNbparticipants');
	print '</td></tr>';

	if (! empty($conf->global->AGF_ADD_PROGRAM_TO_CONV)) {
		$chapter ++;
		print '<tr class="standardConventionModel"><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
		print '<td><textarea name="art2" rows="7" cols="5" class="flat" style="width:560px;">' . $art2 . '</textarea></td></tr>';
	}

	$chapter ++;
	print '<tr class="standardConventionModel"><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
	print '<td><textarea name="art3" rows="7" cols="5" class="flat" style="width:560px;">' . $art3 . '</textarea>';
	print img_picto($langs->trans('AgfExplainListTrainee'), 'info') . $langs->trans('AgfExplainListTrainee');
	print '</td></tr>';

	$chapter ++;
	print '<tr class="standardConventionModel"><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
	print '<td><textarea name="art4" rows="7" cols="5" class="flat" style="width:560px;">' . $art4 . '</textarea></td></tr>';

	$chapter ++;
	print '<tr class="standardConventionModel"><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
	print '<td><textarea name="art5" rows="7" cols="5" class="flat" style="width:560px;">' . $art5 . '</textarea>';
	print img_picto($langs->trans('AgfExplainListOPCA'), 'info') . $langs->trans('AgfExplainListOPCA');
	print '</td></tr>';

	$chapter ++;
	print '<tr class="standardConventionModel"><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
	print '<td><textarea name="art9" rows="7" cols="5" class="flat" style="width:560px;">' . $art9 . '</textarea></td></tr>';

	$chapter ++;
	print '<tr class="standardConventionModel"><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
	print '<td><textarea name="art6" rows="7" cols="5" class="flat" style="width:560px;">' . $art6 . '</textarea></td></tr>';

	$chapter ++;
	print '<tr class="standardConventionModel"><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
	print '<td><textarea name="art7" rows="7" cols="5" class="flat" style="width:560px;">' . $art7 . '</textarea></td></tr>';

	print '<tr class="standardConventionModel"><td valign="top">' . $langs->trans("AgfConventionSig") . '</td>';
	print '<td><textarea name="sig" rows="7" cols="5" class="flat" style="width:560px;">' . $sig . '</textarea></td></tr>';

	print '<tr><td valign="top">' . $langs->trans("AgfNote") . '<br /><span style=" font-size:smaller; font-style:italic;">' . $langs->trans("AgfConvNotesExplic") . '</span></td>';
	print '<td><textarea name="notes" rows="7" cols="5" class="flat" style="width:560px;"></textarea></td></tr>';
	print '</table>';
	print '</div>';

	print '<table style=noborder align="right">';
	print '<tr><td align="center" colspan=2>';
	print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
	print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
	print '</td></tr>';
	print '</table>';
	print '</form>';
} else {
	// Affichage de la fiche convention
	$agf = new Agefodd_convention($db);
	if (! empty($id))
		$result = $agf->fetch(0, 0, $id);

	// Modèles personnalisés referenceletters
	if (! empty($conf->referenceletters->enabled) && strpos($agf->model_doc, 'rfltr_agefodd') !== false) {
		$id_model_rfltr = ( int ) strtr($agf->model_doc, array(
				'rfltr_agefodd_' => ''
		));
	}

	if ($result) {
		$agf_session = new Agsession($db);
		$agf_session->fetch($agf->sessid);

		$head = session_prepare_head($agf, 1);

		$agf_soc = new Societe($db);
		$result = $agf_soc->fetch($agf->socid);

		$hselected = 'convention';

		dol_fiche_head($head, $hselected, $langs->trans("AgfSessionDetail"), 0, 'bill');

		// Affichage en mode "édition"
		if ($action == 'edit') {
			print '<form name="update" action="convention.php" method="post">' . "\n";
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
			print '<input type="hidden" name="action" value="update">' . "\n";
			print '<input type="hidden" name="id" value="' . $id . '">' . "\n";
			print '<input type="hidden" name="socid" value="' . $agf->socid . '">' . "\n";
			print '<input type="hidden" name="sessid" value="' . $agf->sessid . '">' . "\n";

			print '<table class="border" width="100%">' . "\n";

			print '<tr><td valign="top" width="200px">' . $langs->trans("Company") . '</td>';
			print '<td>' . $agf->socname . '</td></tr>';

			print '<tr><td valign="top" width="200px">' . $langs->trans("AgfElementToUse") . '</td>';
			print '<td>';
			print '<select id="idtypelement" name="idtypelement" class="flat">';
			$agf_element = new Agefodd_session_element($db);
			$agf_element->fetch_by_session_by_thirdparty($agf->sessid, $agf->socid);
			foreach ( $agf_element->lines as $line ) {

				if ($agf->fk_element == $line->fk_element && $agf->element_type == $line->element_type) {
					$selected = 'selected="selected"';
				} else {
					$selected = '';
				}

				if ($line->element_type == 'propal' && ! empty($line->propalref)) {
					$propal = new Propal($db);
					$propal->fetch($line->fk_element);
					// Propal cannot be draft or not signed to generate a convention
					if (($propal->statut != 3) && ($propal->statut != 0)) {
						print '<option value="' . $line->fk_element . ':' . $line->element_type . '" ' . $selected . '>' . $langs->trans('Proposal') . ' ' . $line->propalref . ' ' . $propal->getLibStatut(1) . '</option>';
					}
				}
				if ($line->element_type == 'order' && ! empty($line->comref)) {
					$order = new Commande($db);
					$order->fetch($line->fk_element);
					// Order cannot be cancelled or draft to generate a convention
					if (($order->statut != - 1) && ($order->statut != 0)) {
						print '<option value="' . $line->fk_element . ':' . $line->element_type . '" ' . $selected . '>' . $langs->trans('Order') . ' ' . $line->comref . ' ' . $order->getLibStatut(1) . '</option>';
					}
				}
				if ($line->element_type == 'invoice' && ! empty($line->facnumber)) {
					$invoice = new Facture($db);
					$invoice->fetch($line->fk_element);
					// Order cannot be draft to generate a convention
					if ($invoice->statut != 0) {
						print '<option value="' . $line->fk_element . ':' . $line->element_type . '" ' . $selected . '>' . $langs->trans('Invoice') . ' ' . $line->facnumber . ' ' . $invoice->getLibStatut(1) . '</option>';
					}
				}
			}
			print '</select>';
			if (! empty($agf_session->fk_product)) {
				print '<BR>';
				print '<input type="checkbox" value="1" name="only_product_session"  id="only_product_session" ' . (empty($agf->only_product_session) ? '' : 'checked="checked"') . '>' . $langs->trans('AgfOutputOnlySessionProductInConv');
			}
			print '</td></tr>';

			print '<tr><td valign="top" width="200px">' . $langs->trans("AgfConvModelDoc") . '</td>';
			print '<td>';
			print $formAgefodd->select_conv_model($agf->model_doc, 'model_doc');
			print '</td></tr>';

			if (!empty($conf->global->MAIN_MULTILANGS)) {
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
				$formadmin=new FormAdmin($db);
				$langs->load('admin');
				if (empty($agf->doc_lang)) {
					if (!empty($agf_soc->default_lang)) {
						$agf->doc_lang = $agf_soc->default_lang;
					} else {
						$agf->doc_lang = $langs->getDefaultLang();
					}
				}
				$morecss='maxwidth150';
				if (! empty($conf->browser->phone)) $morecss='maxwidth100';
				print '<tr><td valign="top" width="200px">' . $langs->trans("DefaultLanguage") . '</td>';
				print '<td>';
				print $formadmin->select_language($agf->doc_lang, 'doc_lang', 0, 0, 0, 0, 0, $morecss);
				print '</td></tr>';
			}

			print '<tr><td valign="top" width="200px">' . $langs->trans("AgfConvTrainees") . '</td>';
			print '<td>';

			$options_trainee_array = array();
			$options_trainee_array_selected = $agf->line_trainee;
			$stagiaires = new Agefodd_session_stagiaire($db);
			$nbstag = $stagiaires->fetch_stagiaire_per_session($agf->sessid);
			foreach ( $stagiaires->lines as $traine_line ) {
				$options_trainee_array[$traine_line->stagerowid] = $traine_line->nom . ' ' . $traine_line->prenom . ' (' . $traine_line->socname . ')';
			}

			print $formAgefodd->agfmultiselectarray('trainee_id', $options_trainee_array, $options_trainee_array_selected);
			print '</td></tr>';

			print '<tr class="standardConventionModel" ' . (empty($id_model_rfltr) ? '' : 'style="display:none;"') . '><td valign="top" width="200px">' . $langs->trans("AgfConventionIntro1") . '</td>';
			print '<td><textarea name="intro1" rows="7" cols="5" class="flat" style="width:560px;">' . $agf->intro1 . '</textarea></td></tr>';

			print '<tr class="standardConventionModel" ' . (empty($id_model_rfltr) ? '' : 'style="display:none;"') . '><td valign="top">' . $langs->trans("AgfConventionIntro2") . '</td>';
			print '<td><textarea name="intro2" rows="7" cols="5" class="flat" style="width:560px;">' . $agf->intro2 . '</textarea></td></tr>';

			$chapter = 1;
			print '<tr class="standardConventionModel" ' . (empty($id_model_rfltr) ? '' : 'style="display:none;"') . '><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
			print '<td><textarea name="art1" rows="7" cols="5" class="flat" style="width:560px;">' . $agf->art1 . '</textarea></td></tr>';

			if (! empty($conf->global->AGF_ADD_PROGRAM_TO_CONV)) {
				$chapter ++;
				print '<tr class="standardConventionModel" ' . (empty($id_model_rfltr) ? '' : 'style="display:none;"') . '><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
				print '<td><textarea name="art2" rows="7" cols="5" class="flat" style="width:560px;">' . $agf->art2 . '</textarea></td></tr>';
			}

			$chapter ++;
			print '<tr class="standardConventionModel" ' . (empty($id_model_rfltr) ? '' : 'style="display:none;"') . '><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
			print '<td><textarea name="art3" rows="7" cols="5" class="flat" style="width:560px;">' . $agf->art3 . '</textarea>';
			print img_picto($langs->trans('AgfExplainListTrainee'), 'info') . $langs->trans('AgfExplainListTrainee');
			print '</td></tr>';

			$chapter ++;
			print '<tr class="standardConventionModel" ' . (empty($id_model_rfltr) ? '' : 'style="display:none;"') . '><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
			print '<td><textarea name="art4" rows="7" cols="5" class="flat" style="width:560px;">' . $agf->art4 . '</textarea></td></tr>';

			$chapter ++;
			print '<tr class="standardConventionModel" ' . (empty($id_model_rfltr) ? '' : 'style="display:none;"') . '><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
			print '<td><textarea name="art5" rows="7" cols="5" class="flat" style="width:560px;">' . $agf->art5 . '</textarea></td></tr>';

			$chapter ++;
			print '<tr class="standardConventionModel" ' . (empty($id_model_rfltr) ? '' : 'style="display:none;"') . '><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
			print '<td><textarea name="art9" rows="7" cols="5" class="flat" style="width:560px;">' . $agf->art9 . '</textarea></td></tr>';

			$chapter ++;
			print '<tr class="standardConventionModel" ' . (empty($id_model_rfltr) ? '' : 'style="display:none;"') . '><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
			print '<td><textarea name="art6" rows="7" cols="5" class="flat" style="width:560px;">' . $agf->art6 . '</textarea></td></tr>';

			$chapter ++;
			print '<tr class="standardConventionModel" ' . (empty($id_model_rfltr) ? '' : 'style="display:none;"') . '><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
			print '<td><textarea name="art7" rows="7" cols="5" class="flat" style="width:560px;">' . $agf->art7 . '</textarea></td></tr>';

			print '<tr class="standardConventionModel" ' . (empty($id_model_rfltr) ? '' : 'style="display:none;"') . '><td valign="top">' . $langs->trans("AgfConventionSig") . '</td>';
			print '<td><textarea name="sig" rows="7" cols="5" class="flat" style="width:560px;">' . $agf->sig . '</textarea></td></tr>';

			print '<tr><td valign="top">' . $langs->trans("AgfNote") . '<br /><span style=" font-size:smaller; font-style:italic;">' . $langs->trans("AgfConvNotesExplic") . '</span></td>';
			print '<td><textarea name="notes" rows="7" cols="5" class="flat" style="width:560px;">' . $agf->notes . '</textarea></td></tr>';

			print '</table>';
			print '</div>';
			print '<table style=noborder align="right">';
			print '<tr><td align="center" colspan=2>';
			print '<input type="submit" class="butAction" value="' . $langs->trans("Save") . '"> &nbsp; ';
			print '<input type="submit" name="cancel" class="butActionDelete" value="' . $langs->trans("Cancel") . '">';
			print '<a class="butActionDelete" href="convention.php?action=delete&id=' . $id . '">' . $langs->trans('Delete') . '</a>';
			print '</td></tr>';
			print '</table>';
			print '</form>';

			print '</div>' . "\n";
		} else {

			/*
			 * Confirmation de la suppression
			 */
			if ($action == 'delete') {
				print $form->formconfirm("convention.php?id=" . $id . '&sessid=' . $agf->sessid, $langs->trans("AgfDeleteConvention"), $langs->trans("AgfConfirmDeleteConvention"), "confirm_delete", '', '', 1);
			}
			/*
			 * Confirmation de l'archivage/activation suppression
			 */
			if (isset($_GET["arch"])) {
				print $form->formconfirm("convention.php?arch=" . $_GET["arch"] . "&id=" . $id, $langs->trans("AgfFormationArchiveChange"), $langs->trans("AgfConfirmArchiveChange"), "arch_confirm_delete", '', '', 1);
			}

			// Create a list of customer for each convention
			// $agf_sess= new Agsession($db);
			// $result_sess_soc = $agf_sess->fetch_societe_per_session($sessid);
			// $result = $agf->fetch($sessid, $agf_sess->line[0]->socid, 0);

			print '<table class="border" width="100%">' . "\n";

			print '<tr><td valign="top" width="200px">' . $langs->trans("Company") . '</td>';
			print '<td>';
			print $agf->socname;

			/*if ($result_sess_soc >= 1)
			 {
			 print '<form name="update" action="convention_fiche.php?id='.$id.'" method="GET">'."\n";
			 print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
			 print '<input type="hidden" name="id" value="'.$id.'">'."\n";
			 print '<input type="hidden" name="sessid" value="'.$sessid.'">'."\n";
			 print '<select name="socid">';
			 foreach ($agf_sess->line as $line)
			 {
			 print '<option value="'.$line->socid.'">'.$line->socname.'</option>';
			 }
			 print '</select>';
			 print '<input type="button" value="voir"/>';
			 print '</form>';
			 }*/
			print '</td></tr>';

			print '<tr><td valign="top" width="200px">' . $langs->trans("AgfElementToUse") . '</td>';
			print '<td>';

			if ($agf->element_type == 'propal') {
				$propal = new Propal($db);
				$propal->fetch($agf->fk_element);
				print $propal->getNomUrl(1);
			}
			if ($agf->element_type == 'order') {
				$order = new Commande($db);
				$order->fetch($agf->fk_element);
				print $order->getNomUrl(1);
			}
			if ($agf->element_type == 'invoice') {
				$invoice = new Facture($db);
				$invoice->fetch($agf->fk_element);
				print $invoice->getNomUrl(1);
			}

			print '</td></tr>';

			print '<tr><td valign="top" width="200px">' . $langs->trans("AgfConvModelDoc") . '</td>';
			print '<td>';
			if (! empty($agf->model_doc)) {

				if (! empty($id_model_rfltr)) {
					dol_include_once('/referenceletters/class/referenceletters.class.php');
					if (class_exists('ReferenceLetters')) {
						$model_rfltr = new ReferenceLetters($db);
						$model_rfltr->fetch($id_model_rfltr);
						print $model_rfltr->title;
					}
				} else {
					$dir = dol_buildpath("/agefodd/core/modules/agefodd/pdf/");
					$file = $agf->model_doc . '.modules.php';
					$class = $agf->model_doc;
					if (file_exists($dir . $file) || file_exists($dir . 'override/' . $file)) {
						if (file_exists($dir . $file))
							require_once ($dir . $file);
						else
							require_once ($dir . 'override/' . $file);
						$module = new $class($db);
						print $module->description;
					}
				}
			}
			print print '</td></tr>';

			if (!empty($conf->global->MAIN_MULTILANGS)) {
				$langs->load('admin');
				$defaultlang=$agf->doc_lang?$agf->doc_lang:$langs->getDefaultLang();
				print '<tr><td valign="top" width="200px">' . $langs->trans("DefaultLanguage") . '</td>';
				print '<td>';
				$langs_available=$langs->get_available_languages(DOL_DOCUMENT_ROOT,12);
				print $langs_available[$defaultlang];
				print '</td></tr>';
			}

			print '<tr><td valign="top" width="200px">' . $langs->trans("AgfConvTrainees") . '</td>';
			print '<td>';

			$stagiaires_session = new Agefodd_session_stagiaire($db);
			if (is_array($agf->line_trainee) && count($agf->line_trainee) > 0) {
				foreach ( $agf->line_trainee as $trainee_session_id ) {
					$result = $stagiaires_session->fetch($trainee_session_id);
					if ($result < 0) {
						setEventMessage($stagiaires->error, 'errors');
					}
					$stagiaire = new Agefodd_stagiaire($db);
					$result = $stagiaire->fetch($stagiaires_session->fk_stagiaire);
					if ($result < 0) {
						setEventMessage($stagiaires->error, 'errors');
					}

					print $stagiaire->nom . ' ' . $stagiaire->prenom . ' (' . $stagiaire->socname . ')<BR>';
				}
			}

			print '</td></tr>';

			if (empty($id_model_rfltr)) {

				print '<tr><td valign="top" width="200px">' . $langs->trans("AgfConventionIntro1") . '</td>';
				print '<td>' . nl2br($agf->intro1) . '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfConventionIntro2") . '</td>';
				print '<td>' . nl2br($agf->intro2) . '</td></tr>';

				$chapter = 1;
				print '<tr><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
				print '<td>' . ebi_liste_a_puce($agf->art1, true) . '</td></tr>';

				if (! empty($conf->global->AGF_ADD_PROGRAM_TO_CONV)) {
					$chapter ++;
					print '<tr><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
					print '<td>' . nl2br($agf->art2) . '</td></tr>';
				}

				$chapter ++;
				print '<tr><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
				print '<td>' . nl2br($agf->art3) . '</td></tr>';

				$chapter ++;
				print '<tr><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
				print '<td>' . nl2br($agf->art4) . '</td></tr>';

				$chapter ++;
				print '<tr><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
				print '<td>' . nl2br($agf->art5) . '</td></tr>';

				$chapter ++;
				print '<tr><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
				print '<td>' . nl2br($agf->art9) . '</td></tr>';

				$chapter ++;
				print '<tr><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
				print '<td>' . nl2br($agf->art6) . '</td></tr>';

				$chapter ++;
				print '<tr><td valign="top">' . $langs->trans("AgfConventionArt" . $chapter) . '</td>';
				print '<td>' . nl2br($agf->art7) . '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfConventionSig") . '</td>';
				print '<td>' . nl2br($agf->sig) . '</td></tr>';
			}

			print '<tr><td valign="top">' . $langs->trans("AgfNote") . '<br /><span style=" font-size:smaller; font-style:italic;">' . $langs->trans("AgfConvNotesExplic") . '</span></td>';
			print '<td valign="top">' . nl2br($agf->notes) . '</td></tr>';

			print '</table>';
			print '</div>';
		}
	}
}

/*
 * Action tabs
 *
 */

print '<div class="tabsAction">';

if ($action != 'create' && $action != 'edit' && $action != 'nfcontact') {
	if ($user->rights->agefodd->creer) {
		print '<a class="butAction" href="convention.php?action=edit&id=' . $id . '">' . $langs->trans('Modify') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';
	}
	if ($user->rights->agefodd->creer) {
		print '<a class="butActionDelete" href="convention.php?action=delete&id=' . $id . '">' . $langs->trans('Delete') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Delete') . '</a>';
	}
	if ($user->rights->agefodd->creer) {
		print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=builddoc&id=' . $id . '">' . $langs->trans('AgfDocCreate') . ' ' . $langs->trans('AgfConvention') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfDocCreate') . ' ' . $langs->trans('AgfConvention') . '</a>';
	}
}

print '</div>';

?>

<script type="text/javascript">

	$(document).ready(function() {
		var res = $('#model_doc').val().indexOf("rfltr_agefodd_");
		if(res > -1) { // Oui
			$('.standardConventionModel').hide();
		}else { // Non
			$('.standardConventionModel').show();
		}
		$('#model_doc').change(function() {
			var res = $(this).val().indexOf("rfltr_agefodd_"); // Est-ce un modèle externe reference letters ?
			if(res > -1) { // Oui
				$('.standardConventionModel').hide();
			} else { // Non
				$('.standardConventionModel').show();
			}
		});
	});

</script>

<?php

llxFooter();
$db->close();
