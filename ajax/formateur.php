<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


if (! defined('NOTOKENRENEWAL'))
	define('NOTOKENRENEWAL', 1); // Disables token renewal
if (! defined('NOREQUIREMENU'))
	define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))
	define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))
	define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC'))
	define('NOREQUIRESOC', '1');
if (! defined('NOCSRFCHECK'))
	define('NOCSRFCHECK', '1');
if (empty($_GET ['keysearch']) && ! defined('NOREQUIREHTML'))
	define('NOREQUIREHTML', '1');

// Dolibarr environment
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory


$htmlname = GETPOST('htmlname', 'alpha');
$outjson = (GETPOST('outjson', 'int') ? GETPOST('outjson', 'int') : 0);
$filter = (GETPOST('filter', 'alpha'));

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
if(!empty($filter))
	$filter = 'AND '.str_replace('TOREPLACE', 'FROM', $filter);



/*
 * View
 */

// print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

dol_syslog(join(',', $_GET));
// print_r($_GET);

if (! empty($action) && $action == 'fetch' && ! empty($id))
{
	dol_include_once('/agefodd/class/agefodd_formateur.class.php');

	$outjson = array();

	$object = new Agefodd_formateur($db);
	$ret = $object->fetch($id);
	if ($ret > 0)
	{
		$outref = $object->name.' '.$object->firstname;
		

	//	$found = false;

		

		

		$outjson = array('ref' => $outref);
	}

	echo json_encode($outjson);
}
else
{
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
	dol_include_once('/agefodd/class/html.formagefodd.class.php');

	$langs->load("agefodd@agefodd");
	$langs->load("main");

	top_httphead();

	if (empty($htmlname))
	{
		print json_encode(array());
	    return;
	}

	$match = preg_grep('/(' . $htmlname . '[0-9]+)/', array_keys($_GET));
	sort($match);

	$idtrainer = (! empty($match[0]) ? $match[0] : '');

	if (GETPOST($htmlname,'alpha') == '' && (! $idtrainer || ! GETPOST($idtrainer,'alpha')))
	{
		print json_encode(array());
	    return;
	}
	if(!empty($filter)){
		
	}

	// When used from jQuery, the search term is added as GET param "term".
	$searchkey = (($idtrainer && GETPOST($idtrainer,'alpha')) ? GETPOST($idtrainer,'alpha') :  (GETPOST($htmlname, 'alpha') ? GETPOST($htmlname, 'alpha') : ''));
	
	$form = new FormAgefodd($db);
	
	$arrayresult = $form->select_formateur_liste("", $htmlname,  "(u.lastname LIKE '%$searchkey%' OR u.firstname LIKE '%$searchkey%' OR sp.lastname LIKE '%$searchkey%' OR sp.firstname LIKE '%$searchkey%') ".$filter, 0, 0, array(),1);
	
	$db->close();

	if ($outjson)
		print json_encode($arrayresult);
}

