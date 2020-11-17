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
	dol_include_once('/agefodd/class/agefodd_place.class.php');

	$outjson = array();

	$object = new Agefodd_place($db);
	$ret = $object->fetch($id);
	if ($ret > 0)
	{
		$outref = $object->ref_interne;
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

	$idplace = (! empty($match[0]) ? $match[0] : '');

	if (GETPOST($htmlname,'alpha') == '' && (! $idplace || ! GETPOST($idplace, 'alpha')))
	{
		print json_encode(array());
	    return;
	}

	// When used from jQuery, the search term is added as GET param "term".
	$searchkey = (($idplace && GETPOST($idplace, 'alpha')) ? GETPOST($idplace, 'alpha') :  (GETPOST($htmlname, 'alpha') ? GETPOST($htmlname, 'alpha') : ''));

	$form = new FormAgefodd($db);

	$arrayresult = $form->select_site_forma_liste("", $htmlname,   0, 0, array(), '', 1, "(p.ref_interne LIKE '%$searchkey%') ".$filter);

	$db->close();

	if ($outjson)
		print json_encode($arrayresult);
}

