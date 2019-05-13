<?php
/* Copyright (C) 2012-2014		Florian Henry			<florian.henry@open-concept.pro>
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
 * \file /agefodd/admin/about.php
 * \ingroup agefodd
 * \brief about agefood module page
 */
// Dolibarr environment
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/agefodd.lib.php';
require_once '../includes/php_markdown/markdown.php';

// Translations
$langs->load("agefodd@agefodd");


// Access control
if (! $user->rights->agefodd->admin && ! $user->admin) {
	accessforbidden();
}

	// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
*/

/*
 * View
*/
$page_name = "About";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print  load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = agefodd_admin_prepare_head();
dol_fiche_head($head, 'about', $langs->trans("Module103000Name"), 0, "agefodd@agefodd");

// About page goes here
print 'Version : ' . $conf->global->AGF_LAST_VERION_INSTALL;
print '<BR><a href="' . dol_buildpath('/agefodd/ChangeLog', 1) . '">Change Log</a>';

print '<BR><a href="' . dol_buildpath('/agefodd/dev/check_data_integrity.php', 1) . '">Check agefodd data integrity</a>';

print '<BR><BR><BR><BR>--------------------------------';
print '<BR><a href="http://wiki.atm-consulting.fr/index.php/Agefodd/Documentation_utilisateur" target="_blanck">Lien Documentation Utilisateur Français</a>';
print '<BR>--------------------------------';


$buffer .= file_get_contents(dol_buildpath('/agefodd/README.md', 0));
print Markdown($buffer);

print '<BR>';

print '<a href="' . dol_buildpath('/agefodd/COPYING', 1) . '">License GPL</a>';

llxFooter();
$db->close();
?>
