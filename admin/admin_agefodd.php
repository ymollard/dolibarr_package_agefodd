<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2017	Florian Henry	<florian.henry@open-concept.pro>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file /agefodd/admin/admin_agefodd.php
 * \ingroup agefodd
 * \brief agefood module setup page
 */
$res = @include("../../main.inc.php"); // For root directory
if (!$res)
	$res = @include("../../../main.inc.php"); // For "custom" directory
if (!$res)
	die("Include of main fails");

require_once '../class/agefodd_formation_catalogue.class.php';
require_once '../class/agefodd_session_admlevel.class.php';
require_once '../class/agefodd_calendrier.class.php';
require_once '../class/html.formagefodd.class.php';
require_once '../lib/agefodd.lib.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

$langs->load("admin");
$langs->load('agefodd@agefodd');

$showHiddenConf = GETPOST('showhiddenconf', 'int');

if (!$user->rights->agefodd->admin && !$user->admin)
	accessforbidden();

$action = GETPOST('action', 'alpha');
$updatedaytodate = GETPOST('updatedaytodate', 'none');
if (!empty($updatedaytodate)) {
	$action = 'updatedaytodate';
}

if ($action == 'updateMaskType') {
	$masktype = GETPOST('value', 'none');

	if ($masktype)
		$res = dolibarr_set_const($db, 'AGF_ADDON', $masktype, 'chaine', 0, '', $conf->entity);

	if (!$res > 0)
		$error++;

	if (!$error) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	} else {
		setEventMessage($langs->trans("Error"), 'errors');
	}
}

if ($action == 'updateMask') {
	$mask = GETPOST('maskagefodd', 'none');

	$res = dolibarr_set_const($db, 'AGF_UNIVERSAL_MASK', $mask, 'chaine', 0, '', $conf->entity);

	if (!$res > 0)
		$error++;

	if (!$error) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	} else {
		setEventMessage($langs->trans("Error"), 'errors');
	}
}

if ($action == 'updateMaskCertifType') {
	$masktype = GETPOST('value', 'none');

	if ($masktype)
		$res = dolibarr_set_const($db, 'AGF_CERTIF_ADDON', $masktype, 'chaine', 0, '', $conf->entity);

	if (!$res > 0)
		$error++;

	if (!$error) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	} else {
		setEventMessage($langs->trans("Error"), 'errors');
	}
}

if ($action == 'updateMaskCertif') {
	$mask = GETPOST('maskagefoddcertif', 'none');

	$res = dolibarr_set_const($db, 'AGF_CERTIF_UNIVERSAL_MASK', $mask, 'chaine', 0, '', $conf->entity);

	if (!$res > 0)
		$error++;

	if (!$error) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	} else {
		setEventMessage($langs->trans("Error"), 'errors');
	}
}
if ($action == 'updateMaskSessionType') {
	$masktype = GETPOST('value', 'none');

	if ($masktype)
		$res = dolibarr_set_const($db, 'AGF_SESSION_ADDON', $masktype, 'chaine', 0, '', $conf->entity);

	if (!$res > 0)
		$error++;

	if (!$error) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	} else {
		setEventMessage($langs->trans("Error"), 'errors');
	}
}

if ($action == 'updateMaskSession') {
	$mask = GETPOST('maskagefoddsession', 'none');

	$res = dolibarr_set_const($db, 'AGF_SESSION_UNIVERSAL_MASK', $mask, 'chaine', 0, '', $conf->entity);

	if (!$res > 0)
		$error++;

	if (!$error) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	} else {
		setEventMessage($langs->trans("Error"), 'errors');
	}
}

if ($action == 'setvar') {
	require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");

	$text_color = GETPOST('AGF_TEXT_COLOR', 'alpha');
	if (!empty($text_color)) {
		$res = dolibarr_set_const($db, 'AGF_TEXT_COLOR', $text_color, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_TEXT_COLOR', '000000', 'chaine', 0, '', $conf->entity);
	}
	if (!$res > 0)
		$error++;


	$head_color = GETPOST('AGF_HEAD_COLOR', 'alpha');
	if (!empty($head_color)) {
		$res = dolibarr_set_const($db, 'AGF_HEAD_COLOR', $head_color, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_HEAD_COLOR', 'CB4619', 'chaine', 0, '', $conf->entity);
	}
	if (!$res > 0)
		$error++;


	$head_color = GETPOST('AGF_HEADER_COLOR_BG', 'alpha');
	if (!empty($head_color)) {
		$res = dolibarr_set_const($db, 'AGF_HEADER_COLOR_BG', $head_color, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_HEADER_COLOR_BG', 'FFFFFF', 'chaine', 0, '', $conf->entity);
	}
	if (!$res > 0)
		$error++;

	$head_color = GETPOST('AGF_HEADER_COLOR_TEXT', 'alpha');
	if (!empty($head_color)) {
		$res = dolibarr_set_const($db, 'AGF_HEADER_COLOR_TEXT', $head_color, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_HEADER_COLOR_TEXT', 'FFFFFF', 'chaine', 0, '', $conf->entity);
	}
	if (!$res > 0)
		$error++;


	$head_color = GETPOST('AGF_COLOR_LINE', 'alpha');
	if (!empty($head_color)) {
		$res = dolibarr_set_const($db, 'AGF_COLOR_LINE', $head_color, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_COLOR_LINE', 'FFFFFF', 'chaine', 0, '', $conf->entity);
	}
	if (!$res > 0)
		$error++;

	$foot_color = GETPOST('AGF_FOOT_COLOR', 'alpha');
	if (!empty($foot_color)) {
		$res = dolibarr_set_const($db, 'AGF_FOOT_COLOR', $foot_color, 'chaine', 0, '', $conf->entity);
	} else {
		$res = dolibarr_set_const($db, 'AGF_FOOT_COLOR', 'BEBEBE', 'chaine', 0, '', $conf->entity);
	}
	if (!$res > 0)
		$error++;

	$use_typestag = GETPOST('AGF_USE_STAGIAIRE_TYPE', 'int');
	$res = dolibarr_set_const($db, 'AGF_USE_STAGIAIRE_TYPE', $use_typestag, 'yesno', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;

	$def_typestag = GETPOST('AGF_DEFAULT_STAGIAIRE_TYPE', 'int');
	if (!empty($def_typestag)) {
		$res = dolibarr_set_const($db, 'AGF_DEFAULT_STAGIAIRE_TYPE', $def_typestag, 'chaine', 0, '', $conf->entity);
		if (!$res > 0)
			$error++;
	}

	$use_typetrainer = GETPOST('AGF_USE_FORMATEUR_TYPE', 'int');
	$res = dolibarr_set_const($db, 'AGF_USE_FORMATEUR_TYPE', $use_typetrainer, 'yesno', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;

	$def_typetrainer = GETPOST('AGF_DEFAULT_FORMATEUR_TYPE', 'int');
	if (!empty($def_typetrainer)) {
		$res = dolibarr_set_const($db, 'AGF_DEFAULT_FORMATEUR_TYPE', $def_typetrainer, 'chaine', 0, '', $conf->entity);
		if (!$res > 0)
			$error++;
	}

	$pref_val = GETPOST('AGF_ORGANISME_PREF', 'alpha');
	$res = dolibarr_set_const($db, 'AGF_ORGANISME_PREF', $pref_val, 'chaine', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;

	$def_status = GETPOST('AGF_DEFAULT_SESSION_STATUS', 'alpha');
	$res = dolibarr_set_const($db, 'AGF_DEFAULT_SESSION_STATUS', $def_status, 'chaine', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;

	$def_type = GETPOST('AGF_DEFAULT_SESSION_TYPE', 'alpha');
	$res = dolibarr_set_const($db, 'AGF_DEFAULT_SESSION_TYPE', $def_type, 'chaine', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;

	$num_org = GETPOST('AGF_ORGANISME_NUM', 'alpha');
	$res = dolibarr_set_const($db, 'AGF_ORGANISME_NUM', $num_org, 'chaine', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;

	$org_rep = GETPOST('AGF_ORGANISME_REPRESENTANT', 'alpha');
	$res = dolibarr_set_const($db, 'AGF_ORGANISME_REPRESENTANT', $org_rep, 'chaine', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;

	$nb_hours_in_days = GETPOST('AGF_NB_HOUR_IN_DAYS', 'int');
	$res = dolibarr_set_const($db, 'AGF_NB_HOUR_IN_DAYS', $nb_hours_in_days, 'chaine', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;


	// Marges
	$TMarges = array('HAUTE', 'BASSE', 'GAUCHE', 'DROITE');
	$TOrientations = array('P', 'L');
	foreach ($TOrientations as $orientation) {
		foreach ($TMarges as $marge) {
			$margeConf = 'AGF_MARGE_' . $marge . '_' . $orientation;
			$margeValue = GETPOST($margeConf, 'int');
			$res = dolibarr_set_const($db, $margeConf, $margeValue, 'chaine', 0, '', $conf->entity);
			if (!$res > 0)
				$error++;
		}
	}


	$default_training_cat = GETPOST('AGF_DEFAULT_TRAINNING_CAT', 'int');
	$res = dolibarr_set_const($db, 'AGF_DEFAULT_TRAINNING_CAT', $default_training_cat, 'chaine', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;

	$default_training_cat_bpf = GETPOST('AGF_DEFAULT_TRAINNING_CAT_BPF', 'int');
	$res = dolibarr_set_const($db, 'AGF_DEFAULT_TRAINNING_CAT_BPF', $default_training_cat_bpf, 'chaine', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;

	$defaultstatuscalendar = GETPOST('AGF_DEFAULT_TRAINER_CALENDAR_STATUS', 'int');
	$res = dolibarr_set_const($db, 'AGF_DEFAULT_TRAINER_CALENDAR_STATUS', $defaultstatuscalendar, 'chaine', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;

	$defaultstatuscalendartrainer = GETPOST('AGF_DEFAULT_CALENDAR_STATUS', 'int');
	$res = dolibarr_set_const($db, 'AGF_DEFAULT_CALENDAR_STATUS', $defaultstatuscalendartrainer, 'chaine', 0, '', $conf->entity);
	if (!$res > 0)
		$error++;

	if ($_FILES["imagesup"]["tmp_name"]) {
		if (preg_match('/([^\\/:]+)$/i', $_FILES["imagesup"]["name"], $reg)) {
			$original_file = $reg[1];

			$isimage = image_format_supported($original_file);
			if ($isimage >= 0) {
				dol_syslog("Move file " . $_FILES["imagesup"]["tmp_name"] . " to " . $conf->agefodd->dir_output . '/images/' . $original_file);
				if (!is_dir($conf->agefodd->dir_output . '/images/')) {
					dol_mkdir($conf->agefodd->dir_output . '/images/');
				}
				$result = dol_move_uploaded_file($_FILES["imagesup"]["tmp_name"], $conf->agefodd->dir_output . '/images/' . $original_file, 1, 0, $_FILES['imagesup']['error']);
				if ($result > 0) {
					dolibarr_set_const($db, "AGF_INFO_TAMPON", $original_file, 'chaine', 0, '', $conf->entity);
				} else if (preg_match('/^ErrorFileIsInfectedWithAVirus/', $result)) {
					$langs->load("errors");
					$tmparray = explode(':', $result);
					setEventMessage($langs->trans('ErrorFileIsInfectedWithAVirus', $tmparray[1]), 'errors');
					$error++;
				} else {
					setEventMessage($langs->trans("ErrorFailedToSaveFile"), 'errors');
					$error++;
				}
			} else {
				setEventMessage($langs->trans("ErrorOnlyPngJpgSupported"), 'errors');
				$error++;
			}
		}
	}

	if ($_FILES["pdfbackgroundportrait"]["tmp_name"]) {
		if (preg_match('/([^\\/:]+)$/i', $_FILES["pdfbackgroundportrait"]["name"], $reg)) {
			$original_file = $reg[1];

			if (strpos($original_file, '.pdf') !== false) {

				dol_syslog("Move file " . $_FILES["pdfbackgroundportrait"]["tmp_name"] . " to " . $conf->agefodd->dir_output . '/background/' . $original_file);
				if (!is_dir($conf->agefodd->dir_output . '/background/')) {
					dol_mkdir($conf->agefodd->dir_output . '/background/');
				}
				$result = dol_move_uploaded_file($_FILES["pdfbackgroundportrait"]["tmp_name"], $conf->agefodd->dir_output . '/background/' . $original_file, 1, 0, $_FILES['pdfbackgroundportrait']['error']);
				if ($result > 0) {

					dolibarr_set_const($db, "AGF_ADD_PDF_BACKGROUND_P", $original_file, 'chaine', 0, '', $conf->entity);
				} else if (preg_match('/^ErrorFileIsInfectedWithAVirus/', $result)) {
					$langs->load("errors");
					$tmparray = explode(':', $result);
					setEventMessage($langs->trans('ErrorFileIsInfectedWithAVirus', $tmparray[1]), 'errors');
					$error++;
				} else {
					setEventMessage($langs->trans("ErrorFailedToSaveFile"), 'errors');
					$error++;
				}
			} else {
				setEventMessage($langs->trans("ErrorOnlyPDFSupported"), 'errors');
				$error++;
			}
		}
	}

	if ($_FILES["pdfbackgroundlandscape"]["tmp_name"]) {
		if (preg_match('/([^\\/:]+)$/i', $_FILES["pdfbackgroundlandscape"]["name"], $reg)) {
			$original_file = $reg[1];

			if (strpos($original_file, '.pdf') !== false) {

				dol_syslog("Move file " . $_FILES["pdfbackgroundlandscape"]["tmp_name"] . " to " . $conf->agefodd->dir_output . '/background/' . $original_file);
				if (!is_dir($conf->agefodd->dir_output . '/background/')) {
					dol_mkdir($conf->agefodd->dir_output . '/background/');
				}
				$result = dol_move_uploaded_file($_FILES["pdfbackgroundlandscape"]["tmp_name"], $conf->agefodd->dir_output . '/background/' . $original_file, 1, 0, $_FILES['pdfbackgroundlandscape']['error']);
				if ($result > 0) {
					dolibarr_set_const($db, "AGF_ADD_PDF_BACKGROUND_L", $original_file, 'chaine', 0, '', $conf->entity);
				} else if (preg_match('/^ErrorFileIsInfectedWithAVirus/', $result)) {
					$langs->load("errors");
					$tmparray = explode(':', $result);
					setEventMessage($langs->trans('ErrorFileIsInfectedWithAVirus', $tmparray[1]), 'errors');
					$error++;
				} else {
					setEventMessage($langs->trans("ErrorFailedToSaveFile"), 'errors');
					$error++;
				}
			} else {
				setEventMessage($langs->trans("ErrorOnlyPDFSupported"), 'errors');
				$error++;
			}
		}
	}

	if (!$error) {
		setEventMessage($langs->trans("SetupSaved"), 'mesgs');
	} else {
		setEventMessage($langs->trans("Error") . " " . $msg, 'errors');
	}
}

if ($action == 'removeimagesup') {
	require_once DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php";

	$logofile = $conf->agefodd->dir_output . '/images/' . $conf->global->AGF_INFO_TAMPON;
	dol_delete_file($logofile);
	dolibarr_del_const($db, "AGF_INFO_TAMPON", $conf->entity);
}

if ($action == 'removepdfbackgroundportrait') {
	require_once DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php";

	$logofile = $conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P;
	dol_delete_file($logofile);
	dolibarr_del_const($db, "AGF_ADD_PDF_BACKGROUND_P", $conf->entity);
}

if ($action == 'removepdfbackgroundlandscape') {
	require_once DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php";

	$logofile = $conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_L;
	dol_delete_file($logofile);
	dolibarr_del_const($db, "AGF_ADD_PDF_BACKGROUND_L", $conf->entity);
}

/*
 *  Admin Form
*
*/

llxHeader('', $langs->trans('AgefoddSetupDesc'));

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother = new FormOther($db);

dol_htmloutput_mesg($mesg);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print print load_fiche_titre($langs->trans("AgefoddSetupDesc"), $linkback, 'setup');

// Configuration header
$head = agefodd_admin_prepare_head();
dol_fiche_head($head, 'settings', $langs->trans("Module103000Name"), 0, "agefodd@agefodd");

// Agefodd numbering module
print load_fiche_titre($langs->trans("AgfAdminTrainingNumber"));
print '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100px">' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Example") . '</td>';
print '<td align="center" width="60px">' . $langs->trans("Activated") . '</td>';
print '<td align="center" width="80px">' . $langs->trans("Infos") . '</td>';
print "</tr>\n";

clearstatcache();

$dirmodels = array_merge(array(
	'/'
));

foreach ($dirmodels as $reldir) {

	$dir = dol_buildpath("/agefodd/core/modules/agefodd/");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			$var = true;

			while (($file = readdir($handle)) !== false) {
				if (preg_match('/^(mod_.*)\.php$/i', $file, $reg)) {
					$file = $reg[1];
					$classname = substr($file, 4);

					require_once($dir . $file . ".php");

					$module = new $file();

					// Show modules according to features level
					if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
						continue;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
						continue;

					if ($module->isEnabled()) {
						$var = !$var;
						print '<tr ' . $bc[$var] . '><td>' . $module->nom . "</td><td>\n";
						print $module->info();
						print '</td>';

						// Show example of numbering module
						print '<td nowrap="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp)) {
							$langs->load("errors");
							print '<div class="error">' . $langs->trans($tmp) . '</div>';
						} elseif ($tmp == 'NotConfigured')
							print $langs->trans($tmp);
						else
							print $tmp;
						print '</td>' . "\n";

						print '<td align="center">';
						if ($conf->global->AGF_ADDON == 'mod_' . $classname) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a href="' . $_SERVER["PHP_SELF"] . '?action=updateMaskType&amp;value=mod_' . $classname . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
						}
						print '</td>';

						$agf = new Formation($db);
						$agf->initAsSpecimen();

						// Info
						$htmltooltip = '';
						$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
						$nextval = $module->getNextValue($mysoc, $agf);
						// Keep " on nextval
						if ("$nextval" != $langs->trans("AgfNotAvailable")) {
							$htmltooltip .= '' . $langs->trans("NextValue") . ': ';
							if ($nextval) {
								$htmltooltip .= $nextval . '<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error) . '<br>';
							}
						}

						print '<td align="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						print '</td>';

						print '</tr>';
					}
				}
			}
			closedir($handle);
		}
	}
}

print '</table><br>';

if (!empty($conf->global->AGF_MANAGE_CERTIF)) {
	dol_include_once('/agefodd/class/agefodd_stagiaire_certif.class.php');

	// Agefodd Certification numbering module
	print load_fiche_titre($langs->trans("AgfAdminCertifNumber"));
	print '<br>';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td width="100px">' . $langs->trans("Name") . '</td>';
	print '<td>' . $langs->trans("Description") . '</td>';
	print '<td>' . $langs->trans("Example") . '</td>';
	print '<td align="center" width="60px">' . $langs->trans("Activated") . '</td>';
	print '<td align="center" width="80px">' . $langs->trans("Infos") . '</td>';
	print "</tr>\n";

	clearstatcache();

	$dirmodels = array_merge(array(
		'/'
	));

	foreach ($dirmodels as $reldir) {
		$dir = dol_buildpath("/agefodd/core/modules/agefodd/certificate/");

		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
				$var = true;

				while (($file = readdir($handle)) !== false) {
					if (preg_match('/^(mod_.*)\.php$/i', $file, $reg)) {
						$file = $reg[1];
						$classname = substr($file, 4);

						require_once $dir . $file . ".php";

						$module = new $file();

						// Show modules according to features level
						if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
							continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
							continue;

						if ($module->isEnabled()) {
							$var = !$var;
							print '<tr ' . $bc[$var] . '><td>' . $module->nom . "</td><td>\n";
							print $module->info();
							print '</td>';

							// Show example of numbering module
							print '<td nowrap="nowrap">';
							$tmp = $module->getExample();
							if (preg_match('/^Error/', $tmp)) {
								$langs->load("errors");
								print '<div class="error">' . $langs->trans($tmp) . '</div>';
							} elseif ($tmp == 'NotConfigured')
								print $langs->trans($tmp);
							else
								print $tmp;
							print '</td>' . "\n";

							print '<td align="center">';
							if ($conf->global->AGF_CERTIF_ADDON == 'mod_' . $classname) {
								print img_picto($langs->trans("Activated"), 'switch_on');
							} else {
								print '<a href="' . $_SERVER["PHP_SELF"] . '?action=updateMaskCertifType&amp;value=mod_' . $classname . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
							}
							print '</td>';

							$agf = new Agefodd_stagiaire_certif($db);
							$agf->initAsSpecimen();

							// Info
							$htmltooltip = '';
							$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
							$nextval = $module->getNextValue($mysoc, $agf);
							if ("$nextval" != $langs->trans("AgfNotAvailable")) // Keep " on nextval
							{
								$htmltooltip .= '' . $langs->trans("NextValue") . ': ';
								if ($nextval) {
									$htmltooltip .= $nextval . '<br>';
								} else {
									$htmltooltip .= $langs->trans($module->error) . '<br>';
								}
							}

							print '<td align="center">';
							print $form->textwithpicto('', $htmltooltip, 1, 0);
							print '</td>';

							print '</tr>';
						}
					}
				}
				closedir($handle);
			}
		}
	}

	print '</table><br>';
}

// Agefodd Session numbering module
print load_fiche_titre($langs->trans("AgfAdminSessionNumber"));
print '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100px">' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td>' . $langs->trans("Example") . '</td>';
print '<td align="center" width="60px">' . $langs->trans("Activated") . '</td>';
print '<td align="center" width="80px">' . $langs->trans("Infos") . '</td>';
print "</tr>\n";

clearstatcache();

$dirmodels = array_merge(array(
	'/'
));

foreach ($dirmodels as $reldir) {
	$dir = dol_buildpath("/agefodd/core/modules/agefodd/session/");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			$var = true;

			while (($file = readdir($handle)) !== false) {
				if (preg_match('/^(mod_.*)\.php$/i', $file, $reg)) {
					$file = $reg[1];
					$classname = substr($file, 4);

					require_once($dir . $file . ".php");

					$module = new $file();

					// Show modules according to features level
					if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
						continue;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
						continue;

					if ($module->isEnabled()) {
						$var = !$var;
						print '<tr ' . $bc[$var] . '><td>' . $module->nom . "</td><td>\n";
						print $module->info();
						print '</td>';

						// Show example of numbering module
						print '<td nowrap="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp)) {
							$langs->load("errors");
							print '<div class="error">' . $langs->trans($tmp) . '</div>';
						} elseif ($tmp == 'NotConfigured')
							print $langs->trans($tmp);
						else
							print $tmp;
						print '</td>' . "\n";

						print '<td align="center">';
						if ($conf->global->AGF_SESSION_ADDON == 'mod_' . $classname) {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a href="' . $_SERVER["PHP_SELF"] . '?action=updateMaskSessionType&amp;value=mod_' . $classname . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
						}
						print '</td>';

						$agf = new Agsession($db);
						$agf->initAsSpecimen();

						// Info
						$htmltooltip = '';
						$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
						$nextval = $module->getNextValue($mysoc, $agf);
						// Keep " on nextval
						if ("$nextval" != $langs->trans("AgfNotAvailable")) {
							$htmltooltip .= '' . $langs->trans("NextValue") . ': ';
							if ($nextval) {
								$htmltooltip .= $nextval . '<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error) . '<br>';
							}
						}

						print '<td align="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						print '</td>';

						print '</tr>';
					}
				}
			}
			closedir($handle);
		}
	}
}

print '</table><br>';

// Admin var of module
print load_fiche_titre($langs->trans("AgfAdmVar"));

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="setvar">';


print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td width="400px">' . $langs->trans("Valeur") . '</td>';
print '<td></td>';
print "</tr>\n";

// Prefecture d\'enregistrement
print '<tr class="pair"><td>' . $langs->trans("AgfPrefNom") . '</td>';
print '<td align="right">';
print '<input type="text"   name="AGF_ORGANISME_PREF" value="' . $conf->global->AGF_ORGANISME_PREF . '" size="20" ></td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfPrefNomHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Numerot d\'enregistrement a la prefecture
print '<tr class="impair"><td>' . $langs->trans("AgfPrefNum") . '</td>';
print '<td align="right">';
print '<input type="text"   name="AGF_ORGANISME_NUM" value="' . $conf->global->AGF_ORGANISME_NUM . '" size="20" ></td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfPrefNumHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Representant de la societé de formation
print '<tr class="pair"><td>' . $langs->trans("AgfRepresant") . '</td>';
print '<td align="right">';
print '<input type="text" name="AGF_ORGANISME_REPRESENTANT" value="' . $conf->global->AGF_ORGANISME_REPRESENTANT . '" size="20" ></td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfRepresantHelp"), 1, 'help');
print '</td>';
print '</tr>';

// PDF Base color
print '<tr class="pair"><td>' . $langs->trans("AgfPDFTextColor") . '</td>';
print '<td nowrap="nowrap" align="right">';
print $formother->selectColor($conf->global->AGF_TEXT_COLOR, "AGF_TEXT_COLOR");
print '</td>';
print '<td></td>';
print "</tr>";
print '<tr class="impair">';
print '<td>' . $langs->trans("AgfPDFHeadColor") . '</td>';
print '<td nowrap="nowrap" align="right">';
print $formother->selectColor($conf->global->AGF_HEAD_COLOR, "AGF_HEAD_COLOR");
print '</td>';
print '<td></td>';
print "</tr>";
// Background color for header
print '<tr class="pair">';
print '<td>' . $langs->trans("AgfPDFHeaderColorBg") . '</td>';
print '<td nowrap="nowrap" align="right">';
print $formother->selectColor($conf->global->AGF_HEADER_COLOR_BG, "AGF_HEADER_COLOR_BG");
print '</td>';
print '<td></td>';
print "</tr>";
// olor for header
print '<tr class="pair">';
print '<td>' . $langs->trans("AgfPDFHeaderColorText") . '</td>';
print '<td nowrap="nowrap" align="right">';
print $formother->selectColor($conf->global->AGF_HEADER_COLOR_TEXT, "AGF_HEADER_COLOR_TEXT");
print '</td>';
print '<td></td>';
print "</tr>";
// Color for lines
print '<tr class="impair">';
print '<td>' . $langs->trans("AgfPDFColorLines") . '</td>';
print '<td nowrap="nowrap" align="right">';
print $formother->selectColor($conf->global->AGF_COLOR_LINE, "AGF_COLOR_LINE");
print '</td>';
print '<td></td>';
print "</tr>";
print '<tr  class="pair">';
print '<td>' . $langs->trans("AgfPDFFootColor") . '</td>';
print '<td nowrap="nowrap" align="right">';
print $formother->selectColor($conf->global->AGF_FOOT_COLOR, "AGF_FOOT_COLOR");
print '</td>';
print '<td></td>';
print "</tr>";

// Utilisation d'un type de stagaire
print '<tr class="impair"><td>' . $langs->trans("AgfUseStagType") . '</td>';
print '<td align="right">';
$arrval = array(
	'0' => $langs->trans("No"),
	'1' => $langs->trans("Yes")
);
print $form->selectarray("AGF_USE_STAGIAIRE_TYPE", $arrval, $conf->global->AGF_USE_STAGIAIRE_TYPE);
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfUseStagTypeHelp"), 1, 'help');
print '</td>';
print '</tr>';

if (!empty($conf->global->AGF_USE_STAGIAIRE_TYPE)) {
	// Type de stagaire par defaut
	print '<tr class="impair"><td>' . $langs->trans("AgfUseStagTypeDefault") . '</td>';
	print '<td align="right">';
	print $formAgefodd->select_type_stagiaire($conf->global->AGF_DEFAULT_STAGIAIRE_TYPE, 'AGF_DEFAULT_STAGIAIRE_TYPE', ' active=1 ');
	print '</td>';
	print '<td align="center">';
	print '</td>';
	print '</tr>';
}

// Utilisation d'un type de formateur
print '<tr class="impair"><td>' . $langs->trans("AgfUseTrainerType") . '</td>';
print '<td align="right">';
$arrval = array(
	'0' => $langs->trans("No"),
	'1' => $langs->trans("Yes")
);
print $form->selectarray("AGF_USE_FORMATEUR_TYPE", $arrval, $conf->global->AGF_USE_FORMATEUR_TYPE);
print '</td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfUseTrainerTypeHelp"), 1, 'help');
print '</td>';
print '</tr>';

if (!empty($conf->global->AGF_USE_FORMATEUR_TYPE)) {
	// Type de stagaire par defaut
	print '<tr class="impair"><td>' . $langs->trans("AgfUseTrainerTypeDefault") . '</td>';
	print '<td align="right">';
	print $formAgefodd->select_type_formateur($conf->global->AGF_DEFAULT_FORMATEUR_TYPE, 'AGF_DEFAULT_FORMATEUR_TYPE', ' active=1 ');
	print '</td>';
	print '<td align="center">';
	print '</td>';
	print '</tr>';
}

// Image supplémentaire (tampon / signature)
print '<tr class="pair"><td>' . $langs->trans("AgfImageSupp") . ' (png,jpg) (H max 178px, L max 194px)</td><td>';
print '<table width="100%" class="nocellnopadd"><tr class="nocellnopadd"><td valign="middle" class="nocellnopadd">';
print '<input type="file" class="flat" name="imagesup" size="40">';
print '</td><td valign="middle" align="right">';
if ($conf->global->AGF_INFO_TAMPON) {
	if (file_exists($conf->agefodd->dir_output . '/images/' . $conf->global->AGF_INFO_TAMPON)) {
		print ' &nbsp; ';
		print '<img src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=agefodd&amp;file=' . urlencode('/images/' . $conf->global->AGF_INFO_TAMPON) . '" alt="AGF_INFO_TAMPON" />';
		print '<a href="' . $_SERVER["PHP_SELF"] . '?action=removeimagesup">' . img_delete($langs->trans("Delete")) . '</a>';
	}
} else {
	$nophoto = '/public/theme/common/nophoto.png';
	print '<img height="30" src="' . DOL_URL_ROOT . $nophoto . '">';
}
print '</td></tr></table>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfInfoTamponHelp"), 1, 'help');

print '</td></tr>';

// PDF de background portrait
print '<tr class="impair"><td>' . $langs->trans("AgfPDFBackgroundPortrait") . ' (pdf)</td><td>';
print '<table width="100%" class="nocellnopadd"><tr class="nocellnopadd"><td valign="middle" class="nocellnopadd">';
print '<input type="file" class="flat" name="pdfbackgroundportrait" size="40">';
print '</td><td valign="middle" align="right">';
if ($conf->global->AGF_ADD_PDF_BACKGROUND_P) {
	if (file_exists($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P)) {
		$documenturl = DOL_URL_ROOT . '/document.php';
		if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP))
			$documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP;    // To use another wrapper
		print ' &nbsp;';
		print '<a class="documentdownload" href="' . $documenturl . '?modulepart=' . 'agefodd' . '&amp;file=' . urlencode('/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_P) . '"';
		print ' target="_blank">';
		print img_mime($conf->global->AGF_ADD_PDF_BACKGROUND_P, $langs->trans("File") . ': ' . $conf->global->AGF_ADD_PDF_BACKGROUND_P) . ' ' . $conf->global->AGF_ADD_PDF_BACKGROUND_P;
		print '</a>' . "\n";

		print '<a href="' . $_SERVER["PHP_SELF"] . '?action=removepdfbackgroundportrait">' . img_delete($langs->trans("Delete")) . '</a>';
	}
}
print '</td></tr></table>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfPDFBackgroundPortrait") . ' ' . $langs->trans("ErrorOnlyPDFSupported"), 1, 'help');
print '</td></tr>';

// PDF de background landscape
print '<tr class="pair"><td>' . $langs->trans("AgfPDFBackgroundLandscape") . ' (pdf)</td><td>';
print '<table width="100%" class="nocellnopadd"><tr class="nocellnopadd"><td valign="middle" class="nocellnopadd">';
print '<input type="file" class="flat" name="pdfbackgroundlandscape" size="40">';
print '</td><td valign="middle" align="right">';
if ($conf->global->AGF_ADD_PDF_BACKGROUND_L) {
	if (file_exists($conf->agefodd->dir_output . '/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_L)) {
		$documenturl = DOL_URL_ROOT . '/document.php';
		if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP))
			$documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP;    // To use another wrapper
		print ' &nbsp;';
		print '<a class="documentdownload" href="' . $documenturl . '?modulepart=agefodd&amp;file=' . urlencode('/background/' . $conf->global->AGF_ADD_PDF_BACKGROUND_L) . '"';
		print ' target="_blank">';
		print img_mime($conf->global->AGF_ADD_PDF_BACKGROUND_L, $langs->trans("File") . ': ' . $conf->global->AGF_ADD_PDF_BACKGROUND_L) . ' ' . $conf->global->AGF_ADD_PDF_BACKGROUND_L;
		print '</a>' . "\n";

		print '<a href="' . $_SERVER["PHP_SELF"] . '?action=removepdfbackgroundlandscape">' . img_delete($langs->trans("Delete")) . '</a>';
	}
}
print '</td></tr></table>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfPDFBackgroundLandscape") . ' ' . $langs->trans("ErrorOnlyPDFSupported"), 1, 'help');
print '</td></tr>';


// Marges
if (!empty($conf->global->AGF_DISPLAY_MARGE_CONFIG) || !empty($showHiddenConf)) {
	// TODO : add this conf usable for all agefodd PDF : show pdf_conseils.modules.php, marge + $pdf->setPageOrientation
	$TMarges = array('HAUTE', 'BASSE', 'GAUCHE', 'DROITE');
	$TOrientations = array('P', 'L');
	foreach ($TOrientations as $orientation) {
		foreach ($TMarges as $marge) {
			print '<tr class="pair"><td>' . $langs->trans("AgfMarge" . ucfirst(strtolower($marge)) . $orientation) . '</td>';
			print '<td align="right">';
			print '<input type="text" name="AGF_MARGE_' . $marge . '_' . $orientation . '" value="' . $conf->global->{'AGF_MARGE_' . $marge . '_' . $orientation} . '" size="4" >' . $langs->trans('LengthUnitmm') . '</td>';
			print '<td align="center">';
			print $form->textwithpicto('', $langs->trans("AgfPDFMargeLeaveEmptyForDefault"), 1, 'help');
			print '</td></tr>';
		}
	}
}


// Default session status
print '<tr class="impair"><td>' . $langs->trans("AgfDefaultSessionStatus") . '</td>';
print '<td align="right">';
print $formAgefodd->select_session_status($conf->global->AGF_DEFAULT_SESSION_STATUS, "AGF_DEFAULT_SESSION_STATUS", 't.active=1');
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

// Default session status
print '<tr class="impair"><td>' . $langs->trans("AgfDefaultSessionType") . '</td>';
print '<td align="right">';
print $formAgefodd->select_type_session("AGF_DEFAULT_SESSION_TYPE", $conf->global->AGF_DEFAULT_SESSION_TYPE);
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

// Nb hours in days
print '<tr class="pair"><td>' . $langs->trans("AgfNbHourInDays") . '</td>';
print '<td align="right">';
print '<input type="text"   name="AGF_NB_HOUR_IN_DAYS" value="' . $conf->global->AGF_NB_HOUR_IN_DAYS . '" size="4" ></td>';
print '<td align="center">';
print $form->textwithpicto('', $langs->trans("AgfNbHourInDaysHelp"), 1, 'help');
print '</td>';
print '</tr>';

// Default calendar status
print '<tr class="impair"><td>' . $langs->trans("AgfDefaultCalendarStatus") . '</td>';
print '<td align="right">';
print $formAgefodd->select_calendrier_status($conf->global->AGF_DEFAULT_CALENDAR_STATUS, "AGF_DEFAULT_CALENDAR_STATUS");
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';

// Default Trainer status
print '<tr class="pair"><td>' . $langs->trans("AgfDefaultTrainerCalendarStatus") . '</td>';
print '<td align="right">';
print $formAgefodd->select_calendrier_status($conf->global->AGF_DEFAULT_TRAINER_CALENDAR_STATUS, "AGF_DEFAULT_TRAINER_CALENDAR_STATUS");
print '</td>';
print '<td align="center">';
print '</td>';
print '</tr>';


// Default training cat
print '<tr class="pair"><td>' . $langs->trans("AgfDefaultTrainingCat") . '</td>';
print '<td align="right">';
print $formAgefodd->select_training_categ($conf->global->AGF_DEFAULT_TRAINNING_CAT, 'AGF_DEFAULT_TRAINNING_CAT', 't.active=1', 1);
print '<td align="center">';
print '</td>';
print '</tr>';

// Default training cat BPF
print '<tr class="pair"><td>' . $langs->trans("AgfDefaultTrainingCatBPF") . '</td>';
print '<td align="right">';
print $formAgefodd->select_training_categ_bpf($conf->global->AGF_DEFAULT_TRAINNING_CAT_BPF, 'AGF_DEFAULT_TRAINNING_CAT_BPF', 't.active=1', 1);
print '<td align="center">';
print '</td>';
print '</tr>';


print '<tr class="impair"><td colspan="3" align="right"><input type="submit" class="button" value="' . $langs->trans("Save") . '"></td>';
print '</tr>';

print '</table><br>';
print '</form>';

llxFooter();
$db->close();
