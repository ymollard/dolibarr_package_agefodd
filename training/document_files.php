<?php
/*
 * Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2010 Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2013-2016 Florian Henry <florian.henry@open-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file /agefodd/training/document_files.php
 * \ingroup agefodd
 * \brief files linked to session
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once '../lib/agefodd.lib.php';
require_once '../class/agefodd_formation_catalogue.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once '../class/html.formagefodd.class.php';

$langs->load("companies");
$langs->load('other');

$action = GETPOST('action', 'none');
$confirm = GETPOST('confirm', 'none');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$asfichepedago = GETPOST('asfichepedago','int');

// Security check
if (! $user->rights->agefodd->agefodd_formation_catalogue->lire) {
	accessforbidden();
}

	// Get parameters
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
if (! $sortorder)
	$sortorder = "ASC";
if (! $sortfield)
	$sortfield = "name";

$object = new Formation($db);
$result = $object->fetch($id);

if ($result < 0) {
	setEventMessage($object->error, 'errors');
} else {
	$upload_dir = $conf->agefodd->dir_output . "/training/". $object->id;
	$relativepathwithnofile="training/" . $object->id.'/';
}

/*
 * Actions
 */
//Rename training program file with trim whitespace to be enable to move it as training program pdf
// do_move user rename php function thaht do not work with white space in name
if (GETPOST('sendit','alpha') && ! empty($conf->global->MAIN_UPLOAD_DOC) && !empty($asfichepedago))
{
	if (! empty($_FILES))
	{
		if (is_array($_FILES['userfile']['name'])) $userfiles=$_FILES['userfile']['name'];
		else $userfiles=array($_FILES['userfile']['name']);
		foreach($userfiles as $key => $userfile)
		{
			$_FILES['userfile']['name'][$key]=preg_replace('/\s+/', '_', dol_sanitizeFileName($userfile));;
		}
	}
}
include_once DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';
//Copy file uploaded as a training program file
if (!empty($asfichepedago)) {
	if (is_array($_FILES['userfile']['name']) && count($_FILES['userfile']['name'])>0) {
		$filename=$_FILES['userfile']['name'][0];
	} else {
		$filename=$_FILES['userfile']['name'];
	}
	$destfile=$filename;
	$path_parts=pathinfo($destfile);
	$result=dol_copy($upload_dir.'/'.$destfile, $conf->agefodd->dir_output.'/'.'fiche_pedago_'.$object->id.'.'.$path_parts['extension']);
	if ($result<0) {
		setEventMessages($langs->trans('AgfErrorCopyFile'), null,'errors');
	}
}
//Copy file linked as a training program file
if(!empty($_REQUEST['label']) && !empty($_REQUEST['link']) && $_REQUEST['label']=="PRG"){

	$fopen = fopen($_REQUEST['link'], 'r');
	file_put_contents($conf->agefodd->dir_output.'/'.'fiche_pedago_'.$object->id.'.pdf', $fopen);

}
/*
 * View
 */

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

$help_url = '';
llxHeader('', $langs->trans("AgfCatalogDetail") . ' - ' . $langs->trans("Files"), $help_url);

if ($object->id) {
	/*
	 * Affichage onglets
	 */
	if (! empty($conf->notification->enabled)) {
		$langs->load("mails");
	}


	$out_js = '<script>' . "\n";
	$out_js .= '$(document).ready(function () { ' . "\n";
	$out_js .= '	$(\'#formuserfile > table > tbody:last-child\').append(\'<tr><td><input type="checkbox" value="1" name="asfichepedago" id="asfichepedago"/>'.$langs->trans('AgfLikeFichePedgao').'</td></tr>\'); ' . "\n";
	$out_js .= '});' . "\n";
	$out_js .= '</script>' . "\n";

	print $out_js;

	$head = training_prepare_head($object);

	dol_fiche_head($head, 'documentfiles', $langs->trans("AgfCatalogDetail"), 0, 'bill');

	$form = new Form($db);

	// Construit liste des fichiers
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ( $filearray as $key => $file ) {
		$totalsize += $file['size'];
	}

	dol_agefodd_banner_tab($object, 'id');
	print '<div class="underbanner clearboth"></div>';

	$modulepart = 'agefodd';
	$permission = ($user->rights->agefodd->agefodd_formation_catalogue->creer);
    $permtoedit = $user->rights->agefodd->agefodd_formation_catalogue->creer;
	$param = '&id=' . $object->id;

	//Avoid bug with Jquery multiselect form
	$conf->global->MAIN_USE_JQUERY_FILEUPLOAD=0;
	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';


} else {
	accessforbidden('', 0, 0);
}

llxFooter();
$db->close();
