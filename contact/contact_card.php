<?php
/*
 * Copyright (C) 2012-2019 Florian Henry <florian.henry@atm-consulting.fr>
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
 * \file agefodd/contact/contact_card.php
 * \ingroup agefodd
 * \brief link of contact into agefodd (trainer or trainnee)
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agsession.class.php');
require_once ('../lib/agefodd.lib.php');
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/contact.lib.php';

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('contactcard'));

$id = GETPOST('id', 'int');

$langs->load("companies");
$langs->load("agefodd@agefodd");

$title = (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
llxHeader('', $title);

$object = new Contact($db);
$res = $object->fetch($id, $user);
if ($res < 0) {
	setEventMessage($object->error, 'errors');
	exit();
}
$res = $object->fetch_optionals();

// Show tabs
$head = contact_prepare_head($object);

dol_fiche_head($head, 'tabAgefodd', $title, - 1, 'contact');

$linkback = '<a href="' . DOL_URL_ROOT . '/contact/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

$morehtmlref = '<div class="refidno">';
if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
	$objsoc = new Societe($db);
	$objsoc->fetch($object->socid);
	// Thirdparty
	$morehtmlref .= $langs->trans('ThirdParty') . ' : ';
	if ($objsoc->id > 0)
		$morehtmlref .= $objsoc->getNomUrl(1);
	else
		$morehtmlref .= $langs->trans("ContactNotLinkedToCompany");
}
$morehtmlref .= '</div>';

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref);

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent">';

// link to Trainer
dol_include_once('/agefodd/class/agefodd_formateur.class.php');
$trainer = new Agefodd_teacher($db);
$nb_trainer = $trainer->fetch_all('', '', 0, 0, - 1, array(
		'f.fk_socpeople' => $object->id
));
if ($nb_trainer < 0) {
	setEventMessage('From hook completeTabsHead agefodd trainer :' . $trainer->error, 'errors');
} elseif ($nb_trainer>0) {
	print '<tr><td class="titlefield">' . $langs->trans("AgfTeacher") . '</td><td>';
	foreach ( $trainer->lines as $line ) {
		print $line->getNomUrl('name','session');
	}
	print '</td></tr>';
}

dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
$trainee = new Agefodd_stagiaire($db);
$nb_trainee = $trainee->fetch_all('', '', 0, 0, array(
		's.fk_socpeople' => $object->id
));
if ($nb_trainee < 0) {
	setEventMessage('From hook completeTabsHead agefodd trainee:' . $trainee->error, 'errors');
} elseif ($nb_trainee>0) {
	print '<tr><td class="titlefield">' . $langs->trans("AgfStagiaireDetail") . '</td><td>';
	foreach ( $trainee->lines as $line ) {
		print $line->getNomUrl('name','session');
	}
	print '</td></tr>';
}

print "</table>";

print '</div>';
llxFooter();
$db->close();