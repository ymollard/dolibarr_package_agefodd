<?php
/*
 * Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2010 Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2013-2017 Florian Henry <florian.henry@open-concept.pro>
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
 * \file /agefodd/trainee/document_files.php
 * \ingroup agefodd
 * \brief files linked to session
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once '../lib/agefodd.lib.php';
require_once ('../class/agefodd_stagiaire.class.php');
require_once ('../class/agefodd_stagiaire_soc_history.class.php');
require_once ('../class/html.formagefodd.class.php');

$langs->load("companies");
$langs->load('other');
$langs->load('agefodd@agefodd');

$action     = GETPOST('action', 'aZ09')?GETPOST('action', 'aZ09'):'view';				// The action 'add', 'create', 'edit', 'update', 'view', ...
$confirm    = GETPOST('confirm', 'alpha');												// Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha');												// We click on a Cancel button
$toselect   = GETPOST('toselect', 'array');												// Array of ids of elements selected into a list
$contextpage= GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'soc_history';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');											// Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ');												// Option for the css output (always '' except when 'print')

$id			= GETPOST('id', 'int');

// Load variable for pagination
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
// Security check
if (! $user->rights->agefodd->lire) {
	accessforbidden();
}



$object= new Agefodd_stagiaire($db);
$result = $object->fetch($id);
$socHistory = new Agefodd_stagiaire_soc_history($db);

// Default sort order (if not yet defined by previous GETPOST)
if (! $sortfield) $sortfield="t.".key($socHistory->fields);   // Set here default search field. By default 1st field in definition.
if (! $sortorder) $sortorder="DESC";

$search=array();
$TLink = array('fk_soc','fk_stagiaire','fk_user_creat');
foreach($socHistory->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key]=GETPOST('search_'.$key, 'alpha');
		if($val['type'] == 'date') $search[$key] = dol_mktime(0,0,0,GETPOST('search_'.$key.'month'),GETPOST('search_'.$key.'day'),GETPOST('search_'.$key.'year'));
		if(in_array($key,$TLink) && $search[$key] == -1) unset($search[$key]) ;
	}

}
// Definition of fields for list
$arrayfields=array();
foreach($socHistory->fields as $key => $val)
{
	// If $val['visible']==0, then we never show the field
	if (! empty($val['visible'])) $arrayfields['t.'.$key]=array('label'=>$val['label'], 'checked'=>(($val['visible']<0)?0:1), 'enabled'=>$val['enabled'], 'position'=>$val['position']);
}

$socHistory->fields = dol_sort_array($socHistory->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');
/*
 * Actions
 */
if (GETPOST('cancel', 'alpha')) { $action='list'; $massaction=''; }

// Selection of new fields
include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') ||GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	foreach($socHistory->fields as $key => $val)
	{
		unset($search[$key]);
	}
	$toselect='';
	$search_array_options=array();
}



/*
 * View
 */

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

$now=dol_now();
// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT ';
foreach($socHistory->fields as $key => $val)
{
	$sql.='t.'.$key.', ';
}

$sql=preg_replace('/, $/', '', $sql);
$sql.= " FROM ".MAIN_DB_PREFIX.$socHistory->table_element." as t";
$sql.=" WHERE t.fk_stagiaire=".$id;
foreach($search as $key => $val)
{
	if ($key == 'status' && $search[$key] == -1) continue;
	$mode_search=(($socHistory->isInt($socHistory->fields[$key]) || $socHistory->isFloat($socHistory->fields[$key]))?1:0);
	if($socHistory->fields[$key]['type'] == 'date') $sql.=natural_search($key, date('Y-m-d',$search[$key]), (($key == 'status')?2:$mode_search));
	else if ($search[$key] != '') $sql.=natural_search($key, $search[$key], (($key == 'status')?2:$mode_search));
}

$sql.=$db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords)	// if total of record found is smaller than page * limit, goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbtotalofrecords) && $limit > $nbtotalofrecords)
{
	$num = $nbtotalofrecords;
}
else
{
	$sql.= $db->plimit($limit+1, $offset);

	$resql=$db->query($sql);
	if (! $resql)
	{
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}



//$help_url="EN:Module_BillOfMaterials|FR:Module_BillOfMaterials_FR|ES:Módulo_BillOfMaterials";
$help_url='';
$title = $langs->trans('AgfStagiaireSocHistory');

llxHeader('', $title, $help_url);
$arrayofselected=is_array($toselect)?$toselect:array();

$param.='&id='.$id;
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
foreach($search as $key => $val)
{
	$param.= '&search_'.$key.'='.urlencode($search[$key]);
}
if ($optioncss != '')     $param.='&optioncss='.urlencode($optioncss);
if ($object->id) {

	/*
	 * Affichage onglets
	 */
	if (! empty($conf->notification->enabled)) {
		$langs->load("mails");
	}

	$head = trainee_prepare_head($object);

	dol_fiche_head($head, 'soc_history', $langs->trans("AgfStagiaireSocHistory"), -1, 'bill');

	dol_agefodd_banner_tab($object, 'id');
	print '<div class="underbanner clearboth"></div>';


	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';


	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, 0, $num, $nbtotalofrecords, 'title_companies', 0, '', '', $limit);

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
	$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields





	print '<div class="div-table-responsive">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
	print '<table class="tagtable liste">'."\n";


	// Fields title search
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	foreach($socHistory->fields as $key => $val)
	{
		$cssforfield=(empty($val['css'])?'':$val['css']);
		if ($key == 'status') $cssforfield='center';
		elseif (in_array($val['type'], array('date','datetime','timestamp'))) $cssforfield.=($cssforfield?' ':'').'center';
		elseif (in_array($val['type'], array('timestamp'))) $cssforfield.=($cssforfield?' ':'').'nowrap';
		elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real'))) $cssforfield.=($cssforfield?' ':'').'right';
		if (! empty($arrayfields['t.'.$key]['checked']))
		{
			print '<td class="liste_titre'.($cssforfield?' '.$cssforfield:'').'">';
			print $socHistory->showInputField($val, $key, $search[$key], '', '', 'search_');
			print '</td>';
		}
	}

	// Action column
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto=$form->showFilterButtons();
	print $searchpicto;
	print '</td>';
	print '</tr>'."\n";


	// Fields title label
	// --------------------------------------------------------------------
	print '<tr class="liste_titre">';
	foreach($socHistory->fields as $key => $val)
	{
		$cssforfield=(empty($val['css'])?'':$val['css']);
		if ($key == 'status') $cssforfield.=($cssforfield?' ':'').'center';
		elseif (in_array($val['type'], array('date','datetime','timestamp'))) $cssforfield.=($cssforfield?' ':'').'center';
		elseif (in_array($val['type'], array('timestamp'))) $cssforfield.=($cssforfield?' ':'').'nowrap';
		elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real'))) $cssforfield.=($cssforfield?' ':'').'right';
		if (! empty($arrayfields['t.'.$key]['checked']))
		{
			print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield?'class="'.$cssforfield.'"':''), $sortfield, $sortorder, ($cssforfield?$cssforfield.' ':''))."\n";
		}
	}

	// Action column
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ')."\n";
	print '</tr>'."\n";



	// Loop on record
	// --------------------------------------------------------------------
	$i=0;
	$totalarray=array();
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);
		if (empty($obj)) break;		// Should not happen

		// Store properties in $socHistory
		$socHistory->id = $obj->rowid;
		foreach($socHistory->fields as $key => $val)
		{
			if (property_exists($obj, $key)) $socHistory->$key = $obj->$key;
		}

		// Show here line of result
		print '<tr class="oddeven">';
		foreach($socHistory->fields as $key => $val)
		{
			$cssforfield=(empty($val['css'])?'':$val['css']);
			if ($key == 'status') $cssforfield.=($cssforfield?' ':'').'center';
			elseif ($key == 'ref') $cssforfield.=($cssforfield?' ':'').'nowrap';
			elseif (in_array($val['type'], array('date','datetime','timestamp'))) $cssforfield.=($cssforfield?' ':'').'center';
			elseif (in_array($val['type'], array('timestamp'))) $cssforfield.=($cssforfield?' ':'').'nowrap';
			elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real'))) $cssforfield.=($cssforfield?' ':'').'right';

			if (! empty($arrayfields['t.'.$key]['checked']))
			{
				print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').'>';
				if ($key == 'status') print $socHistory->getLibStatut(5);
				elseif (in_array($val['type'], array('date','datetime','timestamp'))) print $socHistory->showOutputField($val, $key, $db->jdate($obj->$key), '');
				else print $socHistory->showOutputField($val, $key, $obj->$key, '');
				print '</td>';
				if (! $i) $totalarray['nbfield']++;
				if (! empty($val['isameasure']))
				{
					if (! $i) $totalarray['pos'][$totalarray['nbfield']]='t.'.$key;
					$totalarray['val']['t.'.$key] += $obj->$key;
				}
			}
		}


		// Action column
		print '<td class="nowrap" align="center">';

		print '</td>';
		if (! $i) $totalarray['nbfield']++;

		print '</tr>';

		$i++;
	}

	// If no record found
	if ($num == 0)
	{
		$colspan=1;
		foreach($arrayfields as $key => $val) { if (! empty($val['checked'])) $colspan++; }
		print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
	}


	$db->free($resql);


	print '</table>'."\n";
	print '</div>'."\n";

	print '</form>'."\n";
} else {
	accessforbidden('', 0, 0);
}

llxFooter();
$db->close();
