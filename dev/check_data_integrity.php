<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file /agefodd/dev/check_data_integrity.php
 * \brief dev part
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

llxHeader('', $langs->trans('AgefoddShort'));


//agefodd_session_formateur
$sql = 'SELECT fk_session as fk_session FROM '.MAIN_DB_PREFIX.'agefodd_session_formateur WHERE fk_session NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session '.$obj->fk_session.' dans '.MAIN_DB_PREFIX.'agefodd_session_formateur et non dans '.MAIN_DB_PREFIX.'agefodd_session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_formateur WHERE fk_session NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}

//agefodd_session_stagiaire
$sql = 'SELECT fk_session_agefodd as fk_session FROM '.MAIN_DB_PREFIX.'agefodd_session_stagiaire WHERE fk_session_agefodd NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session '.$obj->fk_session.' dans '.MAIN_DB_PREFIX.'agefodd_session_stagiaire et non dans '.MAIN_DB_PREFIX.'agefodd_session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_stagiaire WHERE fk_session_agefodd NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}

//agefodd_session_formateur
$sql = 'SELECT  fk_agefodd_formateur as  fk_agefodd_formateur FROM '.MAIN_DB_PREFIX.'agefodd_session_formateur WHERE  fk_agefodd_formateur NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_formateur)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Foramteur '.$obj-> 	fk_agefodd_formateur.' dans '.MAIN_DB_PREFIX.'agefodd_session_formateur et non dans '.MAIN_DB_PREFIX.'agefodd_session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_formateur WHERE fk_agefodd_formateur NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_formateur)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}



//agefodd_session_adminsitu
$sql = 'SELECT fk_agefodd_session as fk_session FROM '.MAIN_DB_PREFIX.'agefodd_session_adminsitu WHERE fk_agefodd_session NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session '.$obj->fk_session.' dans '.MAIN_DB_PREFIX.'agefodd_session_adminsitu et non dans '.MAIN_DB_PREFIX.'agefodd_session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_adminsitu WHERE fk_agefodd_session NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}


//agefodd_session_commercial
$sql = 'SELECT fk_session_agefodd as fk_session FROM '.MAIN_DB_PREFIX.'agefodd_session_commercial WHERE fk_session_agefodd NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session '.$obj->fk_session.' dans '.MAIN_DB_PREFIX.'agefodd_session_commercial et non dans '.MAIN_DB_PREFIX.'agefodd_session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_commercial WHERE fk_session_agefodd NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}

//agefodd_session_calendrier
$sql = 'SELECT fk_agefodd_session as fk_session FROM '.MAIN_DB_PREFIX.'agefodd_session_calendrier WHERE fk_agefodd_session NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session '.$obj->fk_session.' dans '.MAIN_DB_PREFIX.'agefodd_session_calendrier et non dans '.MAIN_DB_PREFIX.'agefodd_session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_calendrier WHERE fk_agefodd_session NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}



//agefodd_session_calendrier
$sql = 'SELECT fk_agefodd_session as fk_session FROM '.MAIN_DB_PREFIX.'agefodd_session_calendrier WHERE fk_actioncomm NOT IN (SELECT id FROM '.MAIN_DB_PREFIX.'actioncomm)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session '.$obj->fk_session.' dans '.MAIN_DB_PREFIX.'agefodd_session_calendrier et non dans '.MAIN_DB_PREFIX.'actioncomm<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_calendrier WHERE fk_actioncomm NOT IN (SELECT id FROM '.MAIN_DB_PREFIX.'actioncomm)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}

//agefodd_session_calendrier
$sql = 'SELECT fk_category as fk_category FROM '.MAIN_DB_PREFIX.'agefodd_formateur_category WHERE fk_category NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_formateur_category_dict)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session '.$obj->fk_category.' dans '.MAIN_DB_PREFIX.'agefodd_formateur_category et non dans '.MAIN_DB_PREFIX.'agefodd_formateur_category_dict<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_formateur_category WHERE fk_category NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_formateur_category_dict)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}

$sql = 'SELECT fk_cursus as fk_cursus FROM '.MAIN_DB_PREFIX.'agefodd_stagiaire_cursus WHERE fk_cursus NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_cursus)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Cursus '.$obj->fk_cursus.' dans '.MAIN_DB_PREFIX.'agefodd_formateur_category et non dans '.MAIN_DB_PREFIX.'agefodd_formateur_category_dict<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_stagiaire_cursus WHERE fk_cursus NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_cursus)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}


$sql = 'SELECT rowid,nom,prenom,civilite FROM '.MAIN_DB_PREFIX.'agefodd_stagiaire WHERE civilite NOT IN (SELECT code FROM '.MAIN_DB_PREFIX.'c_civility)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Civité '.$obj->civilite.' du participant id:'.$obj->rowid.'- nom:'.$obj->nom.' prenom:'.$obj->prenom.' n existe pas dans le dictionnaire des civilité<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_stagiaire_cursus WHERE fk_cursus NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_cursus)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}


$sql = 'SELECT fk_formation_catalogue FROM '.MAIN_DB_PREFIX.'agefodd_formation_objectifs_peda WHERE fk_formation_catalogue NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_formation_catalogue)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Formation id:'.$obj->fk_formation_catalogue.' n existe pas dans la table  agefodd_formation_catalogue<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_formation_objectifs_peda WHERE fk_formation_catalogue NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_formation_catalogue)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}


$sql = 'SELECT fk_session_agefodd FROM '.MAIN_DB_PREFIX.'agefodd_session_contact WHERE fk_session_agefodd NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session id:'.$obj->fk_formation_catalogue.' n existe pas dans la table  agefodd_Session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_contact WHERE fk_session_agefodd NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}


$sql = 'SELECT fk_agefodd_session FROM '.MAIN_DB_PREFIX.'agefodd_convention WHERE fk_agefodd_session NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Session id:'.$obj->fk_formation_catalogue.' n existe pas dans la table  agefodd_Session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_convention WHERE fk_agefodd_session NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}


$sql = 'SELECT fk_user_com FROM '.MAIN_DB_PREFIX.'agefodd_session_commercial WHERE fk_user_com NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'user)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'USer id:'.$obj->fk_user_com.' n existe pas dans la table  user<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_commercial WHERE fk_user_com NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'user)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}


$sql = 'SELECT fk_stagiaire FROM '.MAIN_DB_PREFIX.'agefodd_session_stagiaire WHERE fk_stagiaire NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_stagiaire)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'Stagiaire id:'.$obj->fk_stagiaire.' n existe pas dans la table  agefodd_Session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_stagiaire WHERE fk_stagiaire NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_stagiaire)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}

$sql = 'SELECT fk_session_agefodd FROM '.MAIN_DB_PREFIX.'agefodd_session_element WHERE fk_session_agefodd NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'session id:'.$obj->fk_session_agefodd.' n existe pas dans la table  agefodd_Session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_session_element WHERE fk_session_agefodd NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_session)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}


$sql = 'SELECT fk_training FROM '.MAIN_DB_PREFIX.'agefodd_training_admlevel WHERE fk_training NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_formation_catalogue)';

$resql = $db->query($sql);
if ($resql) {
	if ($db->num_rows($resql)) {
		print '<BR><BR>';
		while ( $obj = $db->fetch_object($resql) ) {
			print 'training id:'.$obj->fk_training.' n existe pas dans la table agefodd_Session<BR>';
		}
		print '<BR><BR><BR>Suggestion de correction : DELETE FROM '.MAIN_DB_PREFIX.'agefodd_training_admlevel WHERE fk_training NOT IN (SELECT rowid FROM '.MAIN_DB_PREFIX.'agefodd_formation_catalogue)<BR><BR><BR>';
	}
}else {
	dol_print_error($db);
}



print 'Si pas de message, normalement tout est bon, sinon appliquer les recommendations en conscience ;-)';

llxFooter();
$db->close();