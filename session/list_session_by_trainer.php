<?php
/*
 * Copyright (C) 2012-2014  Florian Henry   <florian.henry@open-concept.pro>
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
 * \file agefodd/session/list.php
 * \ingroup agefodd
 * \brief list of session
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

$ret = $langs->loadLangs(array("agefodd@agefodd"));

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

require_once __DIR__ . '/../class/agsession.class.php';
require_once __DIR__ . '/../class/agefodd_session_formateur.class.php';
require_once __DIR__ . '/../class/agefodd_session_stagiaire.class.php';
require_once __DIR__ . '/../class/agefodd_stagiaire.class.php';
require_once __DIR__ . '/../class/agefodd_formateur.class.php';
require_once __DIR__ . '/../class/agefodd_session_element.class.php';
require_once __DIR__ . '/../lib/agefodd.lib.php';

require_once __DIR__ . '/../class/abricot/class.listview.php';


// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();


$mesg=''; $error=0; $errors=array();

$action		= (GETPOST('action','alpha') ? GETPOST('action','alpha') : 'view');
$confirm	= GETPOST('confirm','alpha');
$backtopage = GETPOST('backtopage','alpha');
$id			= GETPOST('id','int');
$socid		= GETPOST('socid','int');
$listid		= GETPOST('listid','alpha');
$massaction = GETPOST('massaction', 'alpha');
$toselect = GETPOST('toselect', 'array');


if (isset($user->societe_id)) $socid=$user->societe_id;
if (isset($user->socid)) $socid=$user->socid;

// INIT HOOK
$hookmanager->initHooks(array('agefoddsessiontrainerlist'));

$parameters = array(
	'confirm'	=> $confirm,
	'backtopage'=> $backtopage,
	'id'		=> $id,
	'socid'		=> $socid,
	'listid' 	=> $listid,
	'massaction'=> $massaction,
	'toselect'  => $toselect,
);

$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}



/*
 *	Actions
*/

$parameters=array('id'=>$id);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
$error=$hookmanager->error; $errors=array_merge($errors, (array) $hookmanager->errors);

if (empty($reshook))
{

}


/*
* View
*/

llxHeader('', $langs->trans("AgefoddSessionTrainerList"));


$form = new Form($db);

$url = $_SERVER['PHP_SELF'];


print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="form_list_trainer_session" id="form_list_trainer_session">' . "\n";
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

$r = new Listview($db, 'list_trainer_session');



$sql = "SELECT";
$sql .= " s.ref refsession, s.rowid idsession, s.duree_session";
$sql .= " , fc.intitule as intituleformation , fc.rowid idformation ";
$sql .= " , sf.fk_agefodd_formateur ";
$sql .= " , SUM(HOUR(TIMEDIFF(sc.heuref, sc.heured))) as totalHour";
//$sql .= " , SUM(HOUR(TIMEDIFF(sfc.heuref, sfc.heured))) as totalHourPlanned";
$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session s";
$sql .= " JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue fc ON (fc.rowid = s.fk_formation_catalogue ) ";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur sf ON (sf.fk_session = s.rowid  ) ";
//$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur_calendrier sfc ON (sfc.fk_agefodd_session_formateur = sf.rowid ) ";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier sc ON (sc.fk_agefodd_session = s.rowid ) ";

$sql .= " WHERE 1 = 1 ";



$sql .= ' GROUP BY ';
$sql .= ' s.rowid , s.ref,  s.duree_session';
//$sql .= ' , sc.fk_agefodd_session';
//$sql .= ', fc.intitule, fc.rowid';
$sql .= ' ,sf.fk_agefodd_formateur ';



dol_include_once('/agefodd/class/html.formagefodd.class.php');
$formAgefodd = new FormAgefodd($db);
$selectFormateur = $formAgefodd->select_formateur(GETPOST('fk_agefodd_formateur'), 'fk_agefodd_formateur', '', 1);



$sessionCardUrl = dol_buildpath('agefodd/session/card.php',1).'?id=@idsession@';
$sessionCardLink = '<a href="'.$sessionCardUrl.'" >@val@</a>';

$formationCardUrl = dol_buildpath('agefodd/training/card.php',1).'?id=@idformation@';
$formationCardLink = '<a href="'.$formationCardUrl.'" >@val@</a>';

$param = array(
	'view_type' => 'list'
	//,'limit'=>array('nbLine' => 500)
,'allow-fields-select' => true
,'subQuery' => array()
,'link' => array(
		'rowid' =>  $trainneeCardLink
		,'prenom' =>  $trainneeCardLink
		,'refsession' => $sessionCardLink
		,'intituleformation' => $formationCardLink
	)
,'type' => array(
		'duree_session' => 'number', // [datetime], [hour], [money], [number], [integer]
		'totalHour' => 'number'
	)
,'search' => array(
		'refsession' => array('search_type' => true, 'table' => array('s', 's'), 'field' => array('ref')),
		'libelle' => array('search_type' => $goalLibelleList , 'table' => array('csg', 'csg'), 'field' => array('rowid')),
		'intituleformation' => array('search_type' => true, 'table' => array('fc', 'fc'), 'field' => array('intitule')),
		'fk_agefodd_formateur' => array('search_type' => 'override', 'override' => $selectFormateur),
		//'max_progress' => array('search_type' => getTraineeLevelProgress(false, true), 'field' => array('MAX(stg.progress)'), 'fieldas' => array('max_progress'),'fieldname' => 'max_progress'),
)
,'list' => array(
		'title' => $langs->trans('AgfSessionTrainerListTitle'),
	)
,'hide'=> array('rowid')
,'title'=>array(
	'intituleformation'  => $langs->trans('Formation')
	,'fk_agefodd_formateur'  => $langs->trans('Trainer')
	,'refsession' => $langs->trans('AgfRefSession')
	,'duree_session' => $langs->trans('AgfDuree')
	,'totalHour' => $langs->trans('AgfTotalCalendrierHour')
	,'totalHourPlanned' => $langs->trans('AgfTotalCalendrierHourPlanned')
	,'selectedfields' => ''
	)
,'eval'=>array(
	'fk_agefodd_formateur'  => '_getTrainerUrl("@val@")'
	)
);



echo $r->render($sql, $param);
if(!empty($db->lastqueryerror)){
	print '<div class="error" >';
	print $db->lasterror;
	print '<br>'.$db->lastqueryerror;
	print '</div>';
}

llxFooter();
$db->close();



function _getTrainerUrl($id) {
	global $db;

	$obj = new Agefodd_teacher($db);
	$obj->fetch($id);
	return $obj->getNomUrl();
}

