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
 * \file agefodd/training/card.php
 * \ingroup agefodd
 * \brief info of traineer
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once '../class/agefodd_formation_catalogue.class.php';
require_once '../core/modules/agefodd/modules_agefodd.php';
require_once '../class/html.formagefodd.class.php';
require_once '../lib/agefodd.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once '../class/agefodd_formation_catalogue_modules.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

// Security check
if (! $user->rights->agefodd->agefodd_formation_catalogue->lire)
	accessforbidden();

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$arch = GETPOST('arch', 'int');
$objpedamodif = GETPOST('objpedamodif', 'int');
$objc = GETPOST('objc', 'int');

$categid = GETPOST('categid', 'int');
$categidbpf= GETPOST('categidbpf', 'int');

$agf = new Formation($db);
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($agf->table_element);


// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('agftrainingcard','globalcard'));


$parameters=array('id'=>$id);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$agf,$action);     // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)){

    $error = 0;

/*
 * Actions delete
 */
if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->agefodd_formation_catalogue->supprimer) {
	$agf = new Formation($db);
	$agf->id = $id;
	$result = $agf->remove($id);

	if ($result > 0) {
		Header("Location: list.php");
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

if ($action == 'arch_confirm_delete' && $confirm == "yes" && $user->rights->agefodd->agefodd_formation_catalogue->creer) {
	$agf = new Formation($db);

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
 * Action update (fiche de formation)
 */
if ($action == 'update' && $user->rights->agefodd->agefodd_formation_catalogue->creer) {
	if (! $_POST["cancel"]) {
		$agf = new Formation($db);

		$result = $agf->fetch($id);

		$intitule = GETPOST('intitule', 'no_html');
		$agf->intitule = $intitule;
		if (empty($agf->intitule)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AgfIntitule")), 'errors');
			$action = 'edit';
			$error ++;
		}

		$agf->ref_obj = GETPOST('ref', 'alpha');
		if (empty($agf->ref_obj)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AgfRefInterne")), 'errors');
			$action = 'edit';
			$error ++;
		}

		$agf->duree = GETPOST('duree', 'int');
		if (empty($agf->duree)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AgfDuree")), 'errors');
			$action = 'edit';
			$error ++;
		}

		if (empty($error)) {

			$agf->ref_interne = GETPOST('ref_interne', 'alpha');
			$agf->nb_subscribe_min = GETPOST('nbmintarget', 'int');
			$agf->fk_product = GETPOST('productid', 'int');
			$agf->fk_c_category = $categid;
			$agf->fk_c_category_bpf = $categidbpf;
			$agf->color = GETPOST('color', 'alpha');
			$agf->qr_code_info = GETPOST('qr_code_info', 'none');
			$agf->nb_place = GETPOST('nb_place', 'int');

			if (! empty($conf->global->AGF_MANAGE_CERTIF)) {
				$certif_year = GETPOST('certif_year', 'int');
				$certif_month = GETPOST('certif_month', 'int');
				$certif_day = GETPOST('certif_day', 'int');
				$agf->certif_duration = $certif_year . ':' . $certif_month . ':' . $certif_day;
			}

			if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
				$agf->public = dol_htmlcleanlastbr(GETPOST('public', 'none'));
				$agf->methode = dol_htmlcleanlastbr(GETPOST('methode', 'none'));
				$agf->note1 = dol_htmlcleanlastbr(GETPOST('note1', 'none'));
				$agf->note2 = dol_htmlcleanlastbr(GETPOST('note2', 'none'));
				$agf->prerequis = dol_htmlcleanlastbr(GETPOST('prerequis', 'none'));
				$agf->but = dol_htmlcleanlastbr(GETPOST('but', 'none'));
				$agf->programme = dol_htmlcleanlastbr(GETPOST('programme', 'none'));
				$agf->pedago_usage = dol_htmlcleanlastbr(GETPOST('pedago_usage', 'none'));
				$agf->sanction = dol_htmlcleanlastbr(GETPOST('sanction', 'none'));
			} else {
				$agf->public = GETPOST('public', 'alpha');
				$agf->methode = GETPOST('methode', 'alpha');
				$agf->note1 = GETPOST('note1', 'alpha');
				$agf->note2 = GETPOST('note2', 'alpha');
				$agf->prerequis = GETPOST('prerequis', 'alpha');
				$agf->but = GETPOST('but', 'alpha');
				$agf->programme = GETPOST('programme', 'alpha');
				$agf->pedago_usage = GETPOST('pedago_usage', 'alpha');
				$agf->sanction = GETPOST('sanction', 'alpha');
			}

			$extrafields->setOptionalsFromPost($extralabels, $agf);
			$result = $agf->update($user);

			if ($result > 0) {
				Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
				exit();
			} else {
				setEventMessage($agf->error, 'errors');
			}
		}
	} else {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	}
}

/*
 * Action create (fiche formation)
 */
if ($action == 'create_confirm' && $user->rights->agefodd->agefodd_formation_catalogue->creer) {
	if (! $_POST["cancel"]) {
		$agf = new Formation($db);

		$intitule = GETPOST('intitule', 'no_html');
		$agf->intitule = $intitule;
		if (empty($agf->intitule)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AgfIntitule")), 'errors');
			$action = 'create';
			$error ++;
		}

		$agf->ref_obj = GETPOST('ref', 'alpha');
		if (empty($agf->ref_obj)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AgfRefInterne")), 'errors');
			$action = 'create';
			$error ++;
		}

		$agf->duree = GETPOST('duree', 'int');
		if (empty($agf->duree)) {
			setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AgfDuree")), 'errors');
			$action = 'create';
			$error ++;
		}

		if (empty($error)) {
			$agf->ref_obj = GETPOST('ref', 'alpha');
			$agf->ref_interne = GETPOST('ref_interne', 'alpha');
			$agf->duree = GETPOST('duree', 'int');
			$agf->nb_place = GETPOST('nb_place', 'int');
			$agf->nb_subscribe_min = GETPOST('nbmintarget', 'int');
			$agf->fk_product = GETPOST('productid', 'int');
			$agf->fk_c_category = $categid;
			$agf->fk_c_category_bpf = $categidbpf;
			$agf->qr_code_info = GETPOST('qr_code_info', 'none');

			if (! empty($conf->global->AGF_MANAGE_CERTIF)) {
				$certif_year = GETPOST('certif_year', 'int');
				$certif_month = GETPOST('certif_month', 'int');
				$certif_day = GETPOST('certif_day', 'int');
				$agf->certif_duration = $certif_year . ':' . $certif_month . ':' . $certif_day;
			}

			if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
				$agf->public = dol_htmlcleanlastbr(GETPOST('public', 'none'));
				$agf->methode = dol_htmlcleanlastbr(GETPOST('methode', 'none'));
				$agf->note1 = dol_htmlcleanlastbr(GETPOST('note1', 'none'));
				$agf->note2 = dol_htmlcleanlastbr(GETPOST('note2', 'none'));
				$agf->prerequis = dol_htmlcleanlastbr(GETPOST('prerequis', 'none'));
				$agf->but = dol_htmlcleanlastbr(GETPOST('but', 'none'));
				$agf->programme = dol_htmlcleanlastbr(GETPOST('programme', 'none'));
				$agf->pedago_usage = dol_htmlcleanlastbr(GETPOST('pedago_usage', 'none'));
				$agf->sanction = dol_htmlcleanlastbr(GETPOST('sanction', 'none'));
			} else {
				$agf->public = GETPOST('public', 'alpha');
				$agf->methode = GETPOST('methode', 'alpha');
				$agf->note1 = GETPOST('note1', 'alpha');
				$agf->note2 = GETPOST('note2', 'alpha');
				$agf->prerequis = GETPOST('prerequis', 'alpha');
				$agf->but = GETPOST('but', 'alpha');
				$agf->programme = GETPOST('programme', 'alpha');
				$agf->pedago_usage = GETPOST('pedago_usage', 'alpha');
				$agf->sanction = GETPOST('sanction', 'alpha');
			}

			$extrafields->setOptionalsFromPost($extralabels, $agf);

			$newid = $agf->create($user);

			if ($newid > 0) {
				$result = $agf->createAdmLevelForTraining($user);
				if ($result > 0) {
					$action = 'create';
					setEventMessage($agf->error, 'errors');
					$error ++;
				}
			} else {
				$action = 'create';
				setEventMessage($agf->error, 'errors');
				$error ++;
			}

			if (! $error) {
				Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $newid);
				exit();
			} else {
				$action = 'create';
				setEventMessage($agf->error, 'errors');
			}
		}
	} else {
		Header("Location: list.php");
		exit();
	}
}

/*
 * Action ajax_obj_update (objectif pedagogique)
 */
if ($action == "ajax_obj_update" && $user->rights->agefodd->agefodd_formation_catalogue->creer) {
    $newObjectifs = GETPOST('pedago', 'none');

    $agf_peda = new Formation($db);
    $result_peda = $agf_peda->fetch_objpeda_per_formation($id);

    foreach ($agf_peda->lines as $line){
        $agf_peda->remove_objpeda($line->id);
    }
    if (!empty($newObjectifs)){
        foreach ($newObjectifs as $objectif){
            //$agf = new Formation($db);

            $agf_peda->intitule = $objectif['intitule'];
            $agf_peda->priorite = (int) $objectif['priorite'];
            $agf_peda->fk_formation_catalogue = $id;

            $result = $agf_peda->create_objpeda($user);

        }
    }

}

/*
 * Action create (objectif pedagogique)
 */

if ($action == "obj_update" && $user->rights->agefodd->agefodd_formation_catalogue->creer) {
	$agf = new Formation($db);

	$idforma = GETPOST('idforma', 'int');

	// Uate objectif pedagogique
	if (GETPOST('obj_update_x', 'none')) {
		$agf_peda = new Formation($db);

		$result_peda = $agf_peda->fetch_objpeda_per_formation($idforma);
		if ($result_peda < 0) {
			setEventMessage($agf_peda->error, 'errors');
		}
		foreach ( $agf_peda->lines as $line ) {
			$result = $agf->fetch_objpeda($line->id);

			$agf->intitule = GETPOST('intitule_' . $line->id, 'alpha');
			$agf->priorite = GETPOST('priorite_' . $line->id, 'alpha');
			$agf->fk_formation_catalogue = $idforma;
			$agf->id = $line->id;

			$result = $agf->update_objpeda($user);
			if ($result_peda < 0) {
				setEventMessage($agf->error, 'errors');
			}
		}
	}

	// Suppression d'un objectif pedagogique
	if (GETPOST("obj_remove_x", 'none')) {
		$result = $agf->remove_objpeda(GETPOST('objpedaid', 'int'));

		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		}
	}

	// Creation d'un nouvel objectif pedagogique
	if (GETPOST("obj_add_x", 'none')) {
		$agf->intitule = GETPOST('intitule_new', 'alpha');
		$agf->priorite = GETPOST('priorite_new', 'alpha');
		$agf->fk_formation_catalogue = $idforma;

		$result = $agf->create_objpeda($user);
	}

	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?action=edit&id=" . $idforma . "&objpedamodif=1");
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

if ($action == 'confirm_clone' && $confirm == 'yes') {
	$agf = new Formation($db);
    if ($agf->fetch($id) > 0) {
        $db->begin();

        $srcFkFormationCatalogue = $agf->id;
        $newFkFormationCatalogue = $agf->createFromClone($id);

        if ($newFkFormationCatalogue < 0) $error++;

        if (!$error) {
            if (GETPOST('clone_training_modules', 'none')) {
                // clone training modules
                $sql = "SELECT";
                $sql .= " t.rowid";
                $sql .= ", t.entity";
                $sql .= ", t.fk_formation_catalogue";
                $sql .= ", t.sort_order";
                $sql .= ", t.title";
                $sql .= ", t.content_text";
                $sql .= ", t.duration";
                $sql .= ", t.obj_peda";
                $sql .= ", t.status";
                $sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_modules as t";
                $sql .= " WHERE t.fk_formation_catalogue = " . $srcFkFormationCatalogue;

                $resql = $db->query($sql);
                if (!$resql) {
                    $error++;
                    $agf->errors[] = $db->lasterror();
                }

                if (!$error) {
                    while ($obj = $db->fetch_object($resql)) {
                        $agfFormationCatalogueModules = new Agefoddformationcataloguemodules($db);
                        $agfFormationCatalogueModules->entity = $obj->entity;
                        $agfFormationCatalogueModules->fk_formation_catalogue = $newFkFormationCatalogue;
                        $agfFormationCatalogueModules->sort_order = $obj->sort_order;
                        $agfFormationCatalogueModules->title = $obj->title;
                        $agfFormationCatalogueModules->content_text = $obj->content_text;
                        $agfFormationCatalogueModules->duration = $obj->duration;
                        $agfFormationCatalogueModules->obj_peda = $obj->obj_peda;
                        $agfFormationCatalogueModules->status = $obj->status;

                        $result = $agfFormationCatalogueModules->create($user);
                        if ($result < 0) {
                            $error++;
                            $agf->errors[] = $agfFormationCatalogueModules->errorsToString();
                            break;
                        }
                    }
                }
            }
        }

        if ($error) {
            $db->rollback();
        } else {
            $db->commit();
        }

        if ($error) {
            setEventMessages($agf->error, $agf->errors, 'errors');
            $action = '';
        } else {
            header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $newFkFormationCatalogue);
            exit();
        }
    }
}

/*
 * Action generate fiche pédagogique
 */
if ($action == 'fichepeda' && $user->rights->agefodd->agefodd_formation_catalogue->creer) {
	// Define output language
	$agf->fetch($id);

	$result = $agf->generatePDAByLink();


	if($result <= 0){
		$outputlangs = $langs;
		$newlang = GETPOST('lang_id', 'alpha');
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->thirdparty->default_lang;
		if (! empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		$model = 'fiche_pedago';
		$file = $model . '_' . $id . '.pdf';

		// this configuration variable is designed like
		// standard_model_name:new_model_name&standard_model_name:new_model_name&....
		if (! empty($conf->global->AGF_PDF_MODEL_OVERRIDE)) {
			$modelarray = explode('&', $conf->global->AGF_PDF_MODEL_OVERRIDE);
			if (is_array($modelarray) && count($modelarray) > 0) {
				foreach ( $modelarray as $modeloveride ) {
					$modeloverridearray = explode(':', $modeloveride);
					if (is_array($modeloverridearray) && count($modeloverridearray) > 0) {
						if ($modeloverridearray[0] == $model) {
							$model = $modeloverridearray[1];
						}
					}
				}
			}
		}

		$result = agf_pdf_create($db, $id, '', $model, $outputlangs, $file, 0);
	}
	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

if ($action == 'fichepedamodule' && $user->rights->agefodd->agefodd_formation_catalogue->creer) {
	// Define output language
	$outputlangs = $langs;
	$newlang = GETPOST('lang_id', 'alpha');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))
		$newlang = $object->thirdparty->default_lang;
	if (! empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	}
	$model = 'fiche_pedago_modules';
	$file = $model . '_' . $id . '.pdf';

	// this configuration variable is designed like
	// standard_model_name:new_model_name&standard_model_name:new_model_name&....
	if (! empty($conf->global->AGF_PDF_MODEL_OVERRIDE)) {
		$modelarray = explode('&', $conf->global->AGF_PDF_MODEL_OVERRIDE);
		if (is_array($modelarray) && count($modelarray) > 0) {
			foreach ( $modelarray as $modeloveride ) {
				$modeloverridearray = explode(':', $modeloveride);
				if (is_array($modeloverridearray) && count($modeloverridearray) > 0) {
					if ($modeloverridearray[0] == $model) {
						$model = $modeloverridearray[1];
					}
				}
			}
		}
	}

	$result = agf_pdf_create($db, $id, '', $model, $outputlangs, $file, 0);

	if ($result > 0) {
		Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}
    // Delete file
    if ($action == 'remove_file' && $user->rights->agefodd->agefodd_formation_catalogue->supprimer)
    {
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        if (empty($agf->id) || ! $agf->id > 0) {
            // Reload to get all modified line records and be ready for hooks
            $ret = $agf->fetch($id);
        }

        $langs->load('other');
        $filetodelete = GETPOST('file','alpha');
        $file =	$conf->agefodd->dir_output	. '/' .	$filetodelete;
        $ret = dol_delete_file($file,0,0,0, $agf);
        if ($ret) setEventMessages($langs->trans('FileWasRemoved', $filetodelete), null, 'mesgs');
        else setEventMessages($langs->trans('ErrorFailToDeleteFile', $filetodelete), null, 'errors');

        // Make a redirect to avoid to keep the remove_file into the url that create side effects
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $id);
        exit();
    }
}
/*
 * View
 */
$title = ($action == 'create' ? $langs->trans("AgfMenuCatNew") : $langs->trans("AgfCatalogDetail"));

llxHeader('', $title);

$form = new Form($db);
$formagefodd = new FormAgefodd($db);
$formother = new FormOther($db);

/*
 * Action create
 */
if ($action == 'create' && $user->rights->agefodd->agefodd_formation_catalogue->creer) {
	print_fiche_titre($langs->trans("AgfMenuCatNew"));

	print '<form name="create" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="create_confirm">';

	print '<table class="border" width="100%">';

	print '<tr><td width="20%"><span class="fieldrequired">' . $langs->trans("AgfIntitule") . '</span></td><td>';

	$intitule = GETPOST('intitule', 'no_html');
	print '<input name="intitule" class="flat" size="50" value="'.dol_htmlentities($intitule, ENT_QUOTES).'"></td></tr>';

	$agf = new Formation($db);

	$defaultref = '';
	$obj = empty($conf->global->AGF_ADDON) ? 'mod_agefodd_simple' : $conf->global->AGF_ADDON;
	$path_rel = dol_buildpath('/agefodd/core/modules/agefodd/' . $conf->global->AGF_ADDON . '.php');
	if (! empty($conf->global->AGF_ADDON) && is_readable($path_rel)) {
		dol_include_once('/agefodd/core/modules/agefodd/' . $conf->global->AGF_ADDON . '.php');
		$modAgefodd = new $obj();
		$defaultref = $modAgefodd->getNextValue($soc, $agf);
	}

	if (is_numeric($defaultref) && $defaultref <= 0)
		$defaultref = '';
	$defaultref = GETPOST('ref', 'alpha') ? GETPOST('ref', 'none') : $defaultref;

	print '<tr><td width="20%"><span class="fieldrequired">' . $langs->trans("Ref") . '</span></td><td>';
	print '<input name="ref" class="flat" size="50" value="' . $defaultref . '"></td></tr>';

	print '<tr><td width="20%"><span>' . $langs->trans("AgfRefInterne") . '</span></td><td>';
	print '<input name="ref_interne" class="flat" size="50" value="' . GETPOST('ref_interne', 'alpha') . '"></td></tr>';

	print '<tr><td width="20%" class="fieldrequired">' . $langs->trans("AgfDuree") . '</td><td>';
	print '<input name="duree" class="flat" size="4" value="' . GETPOST('duree', 'int') . '"></td></tr>';
	print '<tr><td width="20%" class="">' . $langs->trans("AgfNbPlace") . '</td><td>';
	print '<input name="nb_place" class="flat" size="4" value="' . GETPOST('nb_place', 'int') . '"></td></tr>';

	if (! empty($conf->global->AGF_MANAGE_CERTIF)) {
		print '<tr><td width="20%">' . $langs->trans("AgfCertificateDuration") . '</td><td>';
		print $formagefodd->select_duration_agf($agf->certif_duration, 'certif');
		print '</td></tr>';
	}

	print '<tr><td width="20%">' . $langs->trans("AgfNbMintarget") . '</td><td>';
	print '<input name="nbmintarget" class="flat" size="5" value="' . GETPOST('nbmintarget', 'int') . '"></td></tr>';

	print '<tr><td width="20%">' . $langs->trans("AgfTrainingCateg") . '</td><td>';
	if (empty($categid) && !empty($conf->global->AGF_DEFAULT_TRAINNING_CAT)) {
		$categid=$conf->global->AGF_DEFAULT_TRAINNING_CAT;
	}
	print $formagefodd->select_training_categ($categid, 'categid', 't.active=1', 1);
	if ($user->admin)
		print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
	print "</td></tr>";

	print '<tr><td width="20%">' . $langs->trans("AgfTrainingCategBPF") . '</td><td>';
	if (empty($categidbpf) && !empty($conf->global->AGF_DEFAULT_TRAINNING_CAT_BPF)) {
		$categidbpf=$conf->global->AGF_DEFAULT_TRAINNING_CAT_BPF;
	}
	print $formagefodd->select_training_categ_bpf($categidbpf, 'categidbpf', 't.active=1', 1);
	if ($user->admin)
		print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
	print "</td></tr>";

	print '<tr><td width="20%">' . $langs->trans("AgfProductServiceLinked") . '</td><td>';
	print $form->select_produits(GETPOST('productid', 'none'), 'productid', '', 10000);
	print "</td></tr>";

	print '<tr>';
	print '<td valign="top">' . $langs->trans("AgfPublic") . '</td><td>';
	$doleditor = new DolEditor('public', GETPOST('public', 'none'), '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
	$doleditor->Create();
	print "</td></tr>";

	print '<tr><td valign="top">' . $langs->trans("AgfMethode") . '</td><td>';
	$doleditor = new DolEditor('methode', GETPOST('methode', 'none'), '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
	$doleditor->Create();
	print "</td></tr>";

	print '<tr><td valign="top">' . $langs->trans("AgfDocNeeded") . '</td><td>';
	$doleditor = new DolEditor('note1', GETPOST('note1', 'none'), '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
	$doleditor->Create();
	print "</td></tr>";

	print '<tr><td valign="top">' . $langs->trans("AgfEquiNeeded") . '</td><td>';
	$doleditor = new DolEditor('note2', GETPOST('note2', 'none'), '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
	$doleditor->Create();
	print "</td></tr>";

	print '<tr><td valign="top">' . $langs->trans("AgfPrerequis") . '</td><td>';
	$doleditor = new DolEditor('prerequis', GETPOST('prerequis', 'none'), '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
	$doleditor->Create();
	print "</td></tr>";

	print '<tr><td valign="top">' . $langs->trans("AgfBut") . '</td><td>';
	$doleditor = new DolEditor('but', GETPOST('but', 'none'), '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
	$doleditor->Create();
	print "</td></tr>";

	print '<tr><td valign="top">' . $langs->trans("AgfProgramme") . '</td><td>';
	$doleditor = new DolEditor('programme', GETPOST('programme', 'none'), '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
	$doleditor->Create();
	print "</td></tr>";

	print '<tr><td valign="top">' . $langs->trans("AgfPedagoUsage") . '</td><td>';
	$doleditor = new DolEditor('pedago_usage', GETPOST('pedago_usage', 'none'), '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
	$doleditor->Create();
	print "</td></tr>";

	print '<tr><td valign="top">' . $langs->trans("AgfSanction") . '</td><td>';
	$doleditor = new DolEditor('sanction', GETPOST('sanction', 'none'), '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
	$doleditor->Create();
	print "</td></tr>";

	if ($conf->global->AGF_MANAGE_CERTIF) {
		print '<tr><td valign="top">' . $langs->trans("AgfQRCodeCertifInfo") . '</td><td>';
		print '<input name="qr_code_info" class="flat" size="50" value="' . GETPOST('qr_code_info', 'none') . '"></td></tr>';
	}

	if (! empty($extrafields->attribute_label)) {
		print $agf->showOptionals($extrafields, 'edit');
	}

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
	// View training card
	if (! empty($id)) {
		if (empty($arch))
			$arch = 0;

		$agf = new Formation($db);
		$result = $agf->fetch($id);

		$head = training_prepare_head($agf);

		dol_fiche_head($head, 'card', $langs->trans("AgfCatalogDetail"), 0, 'label');

		if ($result) {

			$agf_peda = new Formation($db);
			$result_peda = $agf_peda->fetch_objpeda_per_formation($id);

			// Affichage en mode "édition"
			if ($action == 'edit') {

				if ($objpedamodif == 1) {
					// print 'toto;';
					print '<script type="text/javascript">
					jQuery(document).ready(function () {
						jQuery(function() {' . "\n";
					if (! empty($objc)) {
						print '		$(\'html, body\').animate({scrollTop: $("#priorite_new").offset().top}, 500,\'easeInOutCubic\');' . "\n";
					} else {
						print '		$(\'html, body\').animate({scrollTop: $("#obj_peda").offset().top}, 500,\'easeInOutCubic\');' . "\n";
					}
					print '	});
					});
					</script> ';
				}

				print '<form name="update" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
				print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
				print '<input type="hidden" name="action" value="update">';
				print '<input type="hidden" name="id" value="' . $id . '">';

				print '<table class="border" width="100%">';

				print "<tr>";
				print '<td width="20%">' . $langs->trans("Id") . '</td><td>';
				print $agf->id;
				print '</td></tr>';

				print '<tr><td width="20%" class="fieldrequired">' . $langs->trans("AgfIntitule") . '</td><td>';
				print '<input name="intitule" class="flat" size="50" value="' . dol_htmlentities($agf->intitule, ENT_QUOTES) . '"></td></tr>';

				print '<tr><td width="20%" class="fieldrequired">' . $langs->trans("Ref") . '</td><td>';
				print '<input name="ref" class="flat" size="50" value="' . $agf->ref_obj . '"></td></tr>';

				print '<tr><td width="20%">' . $langs->trans("AgfRefInterne") . '</td><td>';
				print '<input name="ref_interne" class="flat" size="50" value="' . $agf->ref_interne . '"></td></tr>';

				print '<tr><td width="20%" class="fieldrequired">' . $langs->trans("AgfDuree") . '</td><td>';
				print '<input name="duree" class="flat" size="4" value="' . $agf->duree . '"></td></tr>';
				print '<tr><td width="20%" class="">' . $langs->trans("AgfNbPlace") . '</td><td>';
				print '<input name="nb_place" class="flat" size="4" value="' . $agf->nb_place . '"></td></tr>';

				if (! empty($conf->global->AGF_MANAGE_CERTIF)) {
					print '<tr><td width="20%">' . $langs->trans("AgfCertificateDuration") . '</td><td>';
					print $formagefodd->select_duration_agf($agf->certif_duration, 'certif');
					print '</td></tr>';
				}

				print '<tr><td width="20%">' . $langs->trans("AgfNbMintarget") . '</td><td>';
				print '<input name="nbmintarget" class="flat" size="5" value="' . $agf->nb_subscribe_min . '"></td></tr>';

				print '<tr><td width="20%">' . $langs->trans("AgfTrainingCateg") . '</td><td>';
				print $formagefodd->select_training_categ($agf->fk_c_category, 'categid', 't.active=1' ,1);
				if ($user->admin)
					print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
				print "</td></tr>";

				print '<tr><td width="20%">' . $langs->trans("AgfTrainingCategBPF") . '</td><td>';
				print $formagefodd->select_training_categ_bpf($agf->fk_c_category_bpf, 'categidbpf', 't.active=1' ,1);
				if ($user->admin)
					print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
					print "</td></tr>";

				print '<tr><td width="20%">' . $langs->trans("AgfProductServiceLinked") . '</td><td>';
				print $form->select_produits($agf->fk_product, 'productid', '', 10000);
				print "</td></tr>";

				print '<tr><td>' . $langs->trans("Color") . '</td>';
				print '<td>';
				print $formother->selectColor($agf->color, 'color');
				print '</td></tr>';

				print '<tr>';
				print '<td valign="top">' . $langs->trans("AgfPublic") . '</td><td>';
				$doleditor = new DolEditor('public', $agf->public, '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
				$doleditor->Create();
				print '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfMethode") . '</td><td>';
				$doleditor = new DolEditor('methode', $agf->methode, '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
				$doleditor->Create();
				print '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfDocNeeded") . '</td><td>';
				$doleditor = new DolEditor('note1', $agf->note1, '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
				$doleditor->Create();
				print '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfEquiNeeded") . '</td><td>';
				$doleditor = new DolEditor('note2', $agf->note2, '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
				$doleditor->Create();
				print '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfPrerequis") . '</td><td>';
				$doleditor = new DolEditor('prerequis', $agf->prerequis, '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
				$doleditor->Create();
				print '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfBut") . '</td><td>';
				$doleditor = new DolEditor('but', $agf->but, '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
				$doleditor->Create();
				print '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfProgramme") . '</td><td colspan=3>';
				$doleditor = new DolEditor('programme', $agf->programme, '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
				$doleditor->Create();
				print "</td></tr>";

				print '<tr><td valign="top">' . $langs->trans("AgfPedagoUsage") . '</td><td>';
				$doleditor = new DolEditor('pedago_usage', $agf->pedago_usage, '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
				$doleditor->Create();
				print "</td></tr>";

				print '<tr><td valign="top">' . $langs->trans("AgfSanction") . '</td><td>';
				$doleditor = new DolEditor('sanction', $agf->sanction, '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->AGF_FCKEDITOR_ENABLE_TRAINING, 4, 90);
				$doleditor->Create();
				print "</td></tr>";

				if ($conf->global->AGF_MANAGE_CERTIF) {
					print '<tr><td valign="top">' . $langs->trans("AgfQRCodeCertifInfo") . '</td><td>';
					print '<input name="qr_code_info" class="flat" size="50" value="' . $agf->qr_code_info . '"></td></tr>';
				}


				if (! empty($extrafields->attribute_label)) {
					print $agf->showOptionals($extrafields, 'edit');
				}

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
				/*
				 * Display
				 */

			    dol_agefodd_banner_tab($agf, 'id');
			    print '<div class="underbanner clearboth"></div>';

				// confirm delete
				if ($action == 'delete') {
					print $form->formconfirm($_SERVER['PHP_SELF'] . "?id=" . $id, $langs->trans("AgfDeleteOps"), $langs->trans("AgfConfirmDeleteOps"), "confirm_delete", '', '', 1);
				}

				// Confirm clone
				if ($action == 'clone') {
                    $formquestion = '';

                    if (!empty($conf->global->AGF_USE_TRAINING_MODULE)) {
                        $formquestion = array('text' => $langs->trans("ConfirmClone"));
                        $formquestion[] = array(
                            'type' => 'checkbox',
                            'name' => 'clone_training_modules',
                            'label' => $langs->trans("AgfCloneTrainingModules"),
                            'value' => 0
                        );
                    }

					print $form->formconfirm($_SERVER['PHP_SELF'] . "?id=" . $id, $langs->trans("CloneTraining"), $langs->trans("ConfirmCloneTraining"), "confirm_clone", $formquestion, '', 1);
				}

				// confirm archive
				if ($action == 'archive' || $action == 'active') {
					if ($action == 'archive')
						$value = 1;
					if ($action == 'active')
						$value = 0;

					print $form->formconfirm($_SERVER['PHP_SELF'] . "?arch=" . $value . "&id=" . $id, $langs->trans("AgfFormationArchiveChange"), $langs->trans("AgfConfirmArchiveChange"), "arch_confirm_delete", '', '', 1);
				}

				print '<table class="border" width="100%">';

				print '<tr><td>' . $langs->trans("AgfDuree") . '</td><td colspan=2>';
				print $agf->duree . '</td></tr>';
				print '<tr><td>' . $langs->trans("AgfNbPlace") . '</td><td colspan=2>';
				print $agf->nb_place . '</td></tr>';

				if (! empty($conf->global->AGF_MANAGE_CERTIF)) {
					print '<tr><td width="20%">' . $langs->trans("AgfCertificateDuration") . '</td><td>';
					if (! empty($agf->certif_duration)) {
						$duration_array = explode(':', $agf->certif_duration);
						$year = $duration_array[0];
						$month = $duration_array[1];
						$day = $duration_array[2];
					} else {
						$year = $month = $day = 0;
					}

					print $year . ' ' . $langs->trans('Year') . '(s) ' . $month . ' ' . $langs->trans('Month') . '(s) ' . $day . ' ' . $langs->trans('Day') . '(s)';
					print '</td></tr>';
				}

				print '<tr><td>' . $langs->trans("AgfNbMintarget") . '</td><td colspan=2>';
				print $agf->nb_subscribe_min . '</td></tr>';

				print '<tr><td>' . $langs->trans("AgfTrainingCateg") . '</td><td  colspan=2>';
				print $agf->category_lib;
				print "</td></tr>";

				print '<tr><td>' . $langs->trans("AgfTrainingCategBPF") . '</td><td  colspan=2>';
				print $agf->category_lib_bpf;
				print "</td></tr>";

				print '<tr><td>' . $langs->trans("AgfProductServiceLinked") . '</td><td colspan=2>';
				if (! empty($agf->fk_product)) {
					$product = new Product($db);
					$result = $product->fetch($agf->fk_product);
					if ($result < 0) {
						setEventMessage($product->error, 'errors');
					}
					print $product->getNomUrl(1) . ' - ' . $product->label;
				}
				print "</td></tr>";

				print '<tr><td valign="top">' . $langs->trans("AgfPublic") . '</td><td colspan=2>';
				if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
					print $agf->public;
				} else {
					print stripslashes(nl2br($agf->public));
				}
				print '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfMethode") . '</td><td colspan=2>';
				if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
					print $agf->methode;
				} else {
					print stripslashes(nl2br($agf->methode));
				}
				print '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfDocNeeded") . '</td><td colspan=2>';
				if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
					print $agf->note1;
				} else {
					print stripslashes(nl2br($agf->note1));
				}
				print '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfEquiNeeded") . '</td><td colspan=2>';
				if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
					print $agf->note2;
				} else {
					print stripslashes(nl2br($agf->note2));
				}
				print '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfPrerequis") . '</td><td colspan=2>';
				if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
					$prerequis = $agf->prerequis;
				} else {
					$prerequis = stripslashes(nl2br($agf->prerequis));
				}
				if (empty($agf->prerequis))
					$prerequis = $langs->trans("AgfUndefinedPrerequis");
				print stripslashes($prerequis) . '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfBut") . '</td><td colspan=2>';
				if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
					$but = $agf->but;
				} else {
					$but = stripslashes(nl2br($agf->but));
				}
				if (empty($agf->but))
					$but = $langs->trans("AgfUndefinedBut");
				print $but . '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfPedagoUsage") . '</td><td colspan=2>';
				if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
					$but = $agf->pedago_usage;
				} else {
					$but = stripslashes(nl2br($agf->pedago_usage));
				}
				print $but . '</td></tr>';

				print '<tr><td valign="top">' . $langs->trans("AgfSanction") . '</td><td colspan=2>';
				if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
					$but = $agf->sanction;
				} else {
					$but = stripslashes(nl2br($agf->sanction));
				}
				print $but . '</td></tr>';

				if ($conf->global->AGF_MANAGE_CERTIF) {
					print '<tr><td>' . $langs->trans("AgfQRCodeCertifInfo") . '</td><td colspan=2>';
					if (!empty($agf->qr_code_info)) {
						dol_include_once('/agefodd/class/tcpdfbarcode_agefodd.modules.php');
						$qr_code = new modTcpdfbarcode_agefood;
						$qr_code->is2d=true;
						$result=$qr_code->writeBarCode($agf->qr_code_info,'QRCODE','Y',1,0,$agf->id);
						// Generate on the fly and output barcode with generator
						$url=DOL_URL_ROOT.'/viewimage.php?modulepart=barcode&amp;generator=tcpdfbarcode&amp;code='.urlencode($agf->qr_code_info).'&amp;encoding=QRCODE';
						//print $url;
						print '<img src="'.$url.'" title="'.$agf->qr_code_info.'" border="0">';
					}
					print '</td></tr>';
				}

				if (! empty($extrafields->attribute_label)) {
					print $agf->showOptionals($extrafields);
				}

				print '<script type="text/javascript">' . "\n";
				print 'function DivStatus( div_){' . "\n";
				print '	var Obj = document.getElementById( div_);' . "\n";
				print '	if( Obj.style.display=="none"){' . "\n";
				print '		Obj.style.display ="block";' . "\n";
				print '	}' . "\n";
				print '	else{' . "\n";
				print '		Obj.style.display="none";' . "\n";
				print '	}' . "\n";
				print '}' . "\n";
				print '</script>' . "\n";

				print '<tr class="liste_titre"><td valign="top">' . $langs->trans("AgfProgramme") . $form->textwithpicto('', $langs->trans("AgfProgrammeHelp"), 1, 'help') . '</td>';
				print '<td align="left" colspan=2>';
				print '<a href="javascript:DivStatus(\'prog\');" title="afficher detail" style="font-size:14px;">+</a></td></tr>';
				if (! empty($conf->global->AGF_FCKEDITOR_ENABLE_TRAINING)) {
					$programme = $agf->programme;
				} else {
					$programme = stripslashes(nl2br($agf->programme));
				}
				if (empty($agf->programme))
					$programme = $langs->trans("AgfUndefinedProg");
				print '<tr><td></td><td><div id="prog" style="display:none;">' . $programme . '</div></td></tr>';

				$object_modules = new Agefoddformationcataloguemodules($db);
				$result = $object_modules->fetchAll('ASC', 'sort_order', 0, 0, array(
						't.fk_formation_catalogue' => $id
				));
				if (count($object_modules->lines) > 0) {
					print '<script type="text/javascript">' . "\n";
					print 'function DivStatus( div_){' . "\n";
					print '	var Obj = document.getElementById( div_);' . "\n";
					print '	if( Obj.style.display=="none"){' . "\n";
					print '		Obj.style.display ="block";' . "\n";
					print '	}' . "\n";
					print '	else{' . "\n";
					print '		Obj.style.display="none";' . "\n";
					print '	}' . "\n";
					print '}' . "\n";
					print '</script>' . "\n";

					print '<tr class="liste_titre"><td valign="top">' . $langs->trans("AgfProgrammeModules") . $form->textwithpicto('', $langs->trans("AgfProgrammeModulesHelp"), 1, 'help') . '</td>';
					print '<td align="left" colspan=2>';
					print '<a href="javascript:DivStatus(\'progmod\');" title="afficher detail" style="font-size:14px;">+</a></td></tr>';
					$programme = '';
					foreach ( $object_modules->lines as $line_mod ) {
						$programme .= $line_mod->title . '<br />';
					}
					print '<tr><td></td><td><div id="progmod" style="display:none;">' . $programme . '</div></td></tr>';
				}

				print '</table>';
				print '&nbsp';
				print '<table class="border" width="100%">';
				print '<tr class="liste_titre"><td colspan=3>' . $langs->trans("AgfObjPeda") . '</td></tr>';

				foreach ( $agf_peda->lines as $line ) {

					print '<tr>';
					print '<td width="40" align="center">' . $line->priorite . '</td>';
					print '<td>' . stripslashes($line->intitule) . '</td>';
					print "</tr>\n";
				}

				print "</table>";
				?>
				<script>
				$(document).ready(function() {
					$('#modifyPedago').click(function(e) {
						e.preventDefault();
						listepedago();
					});

					function listepedago(){

						if($('#pedagoModal').length==0) {
							$('body').append('<div id="pedagoModal" title="<?php echo $langs->transnoentities('AgfObjPeda'); ?>"></div>');
						}

						$.ajax({
                            url : "<?php echo dol_buildpath('/agefodd/scripts/pedagoajax.php',1); ?>"
                            ,data:{
                                put: 'printform'
                                ,idTraining: '<?php echo $id; ?>'
                            }
                            ,method:"post"
                            ,dataType:'json'
                        }).done(function(data) {
                        	$('#pedagoModal').html(data.form);
                        });

						$('#pedagoModal').dialog({
							modal:true,
							width:'50%'
						});

					}
				});
				</script>
				<?php
				print '&nbsp';
				print '<table class="border" width="100%">';
				print '<tr class="liste_titre"><td colspan=3>' . $langs->trans("AgfLinkedDocuments") . '</td></tr>';

				$agf->generatePDAByLink();
                $filename = 'fiche_pedago_' . $id . '.pdf';
                $filedir  = $conf->agefodd->dir_output;
                $filepath = $filedir . '/' . $filename;
				if (is_file($filepath)) {
					// afficher
					$legende = $langs->trans("AgfDocOpen");
					print '<tr><td width="200" align="center">' . $langs->trans("AgfFichePedagogique") . '</td><td> ';
                    print '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=agefodd&file=' . $filename . '" alt="' . $legende . '" title="' . $legende . '">';
                    print img_picto($filename . ':' . $filename, 'pdf2', 'class="valignmiddle"') . '</a>';
					if (function_exists('getAdvancedPreviewUrl')) {
						$urladvanced = getAdvancedPreviewUrl('agefodd', 'fiche_pedago_' . $id . '.pdf');
						if ($urladvanced) print '&nbsp;&nbsp;<a data-ajax="false" href="'.$urladvanced.'" title="' . $langs->trans("Preview"). '">'.img_picto('', 'detail', 'class="valignmiddle"').'</a>';
					}
                    if ($user->rights->agefodd->agefodd_formation_catalogue->supprimer) {
                        print '<a href="' . $_SERVER["PHP_SELF"] . "?id=" . $agf->id . '&amp;action=remove_file&amp;file=' . urlencode($filename) . '">' . img_picto($langs->trans('Delete'), 'delete') . '</a>';
                    }
					print '</td></tr>';
				}

                $filename = 'fiche_pedago_modules_' . $id . '.pdf';
                $filedir  = $conf->agefodd->dir_output;
                $filepath = $filedir . '/' . $filename;
				if (is_file($filepath) && (!empty($conf->global->AGF_USE_TRAINING_MODULE))) {
					$legende = $langs->trans("AgfDocOpen");
					print '<tr><td width="200" align="center">' . $langs->trans("AgfFichePedagogiqueModule") . '</td><td> ';
                    print '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=agefodd&file=' . $filename . '" alt="' . $legende . '" title="' . $legende . '">';
                    print img_picto($filename . ':' . $filename, 'pdf2', 'class="valignmiddle"') . '</a>';
					if (function_exists('getAdvancedPreviewUrl')) {
						$urladvanced = getAdvancedPreviewUrl('agefodd', 'fiche_pedago_modules_' . $id . '.pdf');
						if ($urladvanced) print '&nbsp;&nbsp;<a data-ajax="false" href="'.$urladvanced.'" title="' . $langs->trans("Preview"). '">'.img_picto('', 'detail', 'class="valignmiddle"').'</a>';
					}
                    if ($user->rights->agefodd->agefodd_formation_catalogue->supprimer) {
                        print '<a href="' . $_SERVER["PHP_SELF"] . "?id=" . $agf->id . '&amp;action=remove_file&amp;file=' . urlencode($filename) . '">' . img_picto($langs->trans('Delete'), 'delete') . '</a>';
                    }
					print '</td></tr>';
				}

				print '</table>';

				print '</div>';
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
$parameters=array();
$reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$agf,$action);    // Note that $action and $object may have been modified by hook
if (empty($reshook)){


if ($action != 'create' && $action != 'edit') {

	if ($user->rights->agefodd->agefodd_formation_catalogue->creer) {
		print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&id=' . $id . '">' . $langs->trans('Modify') . '</a>';
		print '<a class="butAction" href="#" id="modifyPedago">' . $langs->trans('AgfUpdateObjPeda') . '</a>';
		print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=clone&id=' . $id . '">' . $langs->trans('ToClone') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfUpdateObjPeda') . '</a>';
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('ToClone') . '</a>';
	}

	if ($user->rights->agefodd->agefodd_formation_catalogue->creer) {
		print '<a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?action=delete&id=' . $id . '">' . $langs->trans('Delete') . '</a>';
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Delete') . '</a>';
	}

	if ($agf->archive == 0) {
		$button_action = $langs->trans('AgfArchiver');
		if ($user->rights->agefodd->agefodd_formation_catalogue->creer) {
			print '<a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?action=archive&id=' . $id . '">';
			print $button_action . '</a>';
		} else {
			print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $button_action . '</a>';
		}
	} else {
		$button_action = $langs->trans('AgfActiver');
		if ($user->rights->agefodd->agefodd_formation_catalogue->creer) {
			print '<a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?action=active&id=' . $id . '">';
			print $button_action . '</a>';
		} else {
			print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $button_action . '</a>';
		}
	}

	if ($user->rights->agefodd->agefodd_formation_catalogue->creer) {
		print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=fichepeda&id=' . $id . '">' . $langs->trans('AgfPrintFichePedago') . '</a>';
		if (!empty($conf->global->AGF_USE_TRAINING_MODULE)) {
			print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=fichepedamodule&id=' . $id . '">' . $langs->trans('AgfPrintFichePedagoModules') . '</a>';
		}
	} else {
		print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfPrintFichePedago') . '</a>';
	}
}

}

print '</div>';

llxFooter();
$db->close();
