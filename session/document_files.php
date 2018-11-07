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
 * \file /agefodd/session/document_files.php
 * \ingroup agefodd
 * \brief files linked to session
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once '../lib/agefodd.lib.php';
require_once ('../class/agsession.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once ('../class/html.formagefodd.class.php');

$langs->load("companies");
$langs->load('other');

$action = GETPOST('action');
$confirm = GETPOST('confirm');
$id = (GETPOST('socid', 'int') ? GETPOST('socid', 'int') : GETPOST('id', 'int'));
$ref = GETPOST('ref', 'alpha');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();


$hookmanager->initHooks(array(
		'agefoddsessionlinkedfiles'
));

	// Get parameters
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = (int) GETPOST("page", 'int');
if ($page == - 1) {
	$page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder)
	$sortorder = "ASC";
if (! $sortfield)
	$sortfield = "name";

$object = new Agsession($db);
$result = $object->fetch($id);

if ($result < 0) {
	setEventMessage($object->error, 'errors');
} else {
	$upload_dir = $conf->agefodd->dir_output . "/" . $object->id;
}

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0)
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

/*
 * Actions
*/

include_once DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

/*
// Post file
if (GETPOST ( 'sendit' ) && ! empty ( $conf->global->MAIN_UPLOAD_DOC )) {
	if ($object->id) {
		dol_add_file_process ( $upload_dir, 0, 1, 'userfile' );
	}
}

// Delete file
if ($action == 'confirm_deletefile' && $confirm == 'yes') {
	if ($object->id) {
		$file = $upload_dir . "/" . GETPOST ( 'urlfile' ); // Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).

		$ret = dol_delete_file ( $file, 0, 0, 0, $object );
		if ($ret)
			setEventMessage ( $langs->trans ( "FileWasRemoved", GETPOST ( 'urlfile' ) ) );
		else
			setEventMessage ( $langs->trans ( "ErrorFailToDeleteFile", GETPOST ( 'urlfile' ) ), 'errors' );
		header ( 'Location: ' . $_SERVER ["PHP_SELF"] . '?id=' . $object->id );
		exit ();
	}
}
*/

/*
 * View
*/

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

$help_url = '';
llxHeader('', $langs->trans("AgfSessionDocuments") . ' - ' . $langs->trans("Files"), $help_url);

if ($object->id) {
	/*
	 * Affichage onglets
	*/
	if (! empty($conf->notification->enabled))
		$langs->load("mails");
	$head = session_prepare_head($object);

	$form = new Form($db);

	dol_fiche_head($head, 'documentfiles', $langs->trans("AgfSessionDocuments"), 0, 'bill');

	dol_agefodd_banner_tab($object, 'id');
	print '<div class="underbanner clearboth"></div>';

	// Construit liste des fichiers
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ( $filearray as $key => $file ) {
		$totalsize += $file ['size'];
	}

	$modulepart = 'agefodd';
	$permission = ($user->rights->agefodd->creer || $user->rights->agefodd->modifier);
	$param = '&id=' . $object->id;
	$object->ref=$object->id; // Hack moche mais cool !
	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';

} else {
	accessforbidden('', 0, 0);
}

llxFooter();
$db->close();
