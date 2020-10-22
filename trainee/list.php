<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2016 Florian Henry <florian.henry@open-concept.pro>
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
 * \file agefodd/trainee/list.php
 * \ingroup agefodd
 * \brief list of trainee
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agefodd_stagiaire.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');

$langs->load('agefodd@agefodd');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

llxHeader('', $langs->trans("AgfStagiaireList"));

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'alpha');
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$contextpage = 'traineelist';

// Search criteria
$search_name = GETPOST("search_name", 'none');
$search_firstname = GETPOST("search_firstname", 'none');
$search_civ = GETPOST("search_civ", 'none');
$search_soc = GETPOST("search_soc", 'none');
$search_tel = GETPOST("search_tel", 'none');
$search_tel2 = GETPOST("search_tel2", 'none');
$search_mail = GETPOST("search_mail", 'none');
$search_namefirstname = GETPOST("search_namefirstname", 'none');

//Since 8.0 sall get parameters is sent with rapid search
$search_by=GETPOST('search_by', 'alpha');
if (!empty($search_by)) {
	$sall=GETPOST('sall', 'alpha');
	if (!empty($sall)) {
		${$search_by}=$sall;
	}
}

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x", 'none')) {
	$search_name = '';
	$search_firstname = '';
	$search_civ = '';
	$search_soc = '';
	$search_tel = '';
	$search_tel2 = '';
	$search_mail = '';
}

$hookmanager->initHooks(array('traineelist'));

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

$agf = new Agefodd_stagiaire($db);
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($agf->table_element, true);

$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

$arrayfields=array(
		's.rowid'			=>array('label'=>"Id", 'checked'=>1),
		's.nom'			=>array('label'=>"AgfNomPrenom", 'checked'=>1),
		'civ.code'			=>array('label'=>"AgfCivilite", 'checked'=>1),
		'so.nom'			=>array('label'=>"Company", 'checked'=>1),
		's.tel1'			=>array('label'=>"Phone", 'checked'=>1),
		's.tel2'			=>array('label'=>"Mobile", 'checked'=>1),
		's.mail'			=>array('label'=>"Mail", 'checked'=>1),
);

// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
	foreach($extrafields->attribute_label as $key => $val)
	{
		if ($extrafields->attribute_type[$key]!='separate') {
			$arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]!==3)?0:1), 'position'=>$extrafields->attribute_pos[$key]);
		}
	}
}

$filter = array ();
$option='';
if (! empty($search_name)) {
	$filter ['s.nom'] = $search_name;
	$option .= '&search_name=' . $search_name;
}
if (! empty($search_firstname)) {
	$filter ['s.prenom'] = $search_firstname;
	$option .= '&search_firstname=' . $search_name;
}
if (! empty($search_civ)) {
	$filter ['civ.code'] = $search_civ;
	$option .= '&search_civ=' . $search_civ;
}
if (! empty($search_soc)) {
	$filter ['so.nom'] = $search_soc;
	$option .= '&search_soc=' . $search_soc;
}
if (! empty($search_tel)) {
	$filter ['s.tel1'] = $search_tel;
	$option .= '&search_tel=' . $search_tel;
}
if (! empty($search_tel2)) {
	$filter ['s.tel2'] = $search_tel2;
	$option .= '&search_tel2=' . $search_tel2;
}
if (! empty($search_mail)) {
	$filter ['s.mail'] = $search_mail;
	$option .= '&search_mail=' . $search_mail;
}
if (! empty($search_namefirstname)) {
	$filter ['naturalsearch'] = $search_namefirstname;
	$option .= '&search_namefirstname=' . $search_namefirstname;
}
if (!empty($limit)) {
	$option .= '&limit=' . $limit;
}

foreach ($search_array_options as $key => $val)
{
	$crit=$val;
	$tmpkey=preg_replace('/search_options_/','',$key);
	$typ=$extrafields->attribute_type[$tmpkey];
	$mode_search=0;
	if (in_array($typ, array('int','double','real'))) $mode_search=1;								// Search on a numeric
	if (in_array($typ, array('sellist','link','chkbxlst','checkbox')) && $crit != '0' && $crit != '-1') $mode_search=2;	// Search on a foreign key int
	if ($crit != '' && (! in_array($typ, array('select','sellist')) || $crit != '0') && (! in_array($typ, array('link')) || $crit != '-1'))
	{
		$filter['ef.'.$tmpkey]= natural_search('ef.'.$tmpkey, $crit, $mode_search);
		$option .= '&search_options_'.$tmpkey.'=' . $crit;
	}
}

if (! $sortorder) {
	$sortorder = "ASC";
}
if (! $sortfield) {
	$sortfield = "s.nom";
}

if (empty($page) || $page == -1) { $page = 0; }


$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$formcompagny = new FormCompany($db);

// Count total nb of records
$nbtotalofrecords = 0;

if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $agf->fetch_all($sortorder, $sortfield, 0, 0, $filter);
}

$result = $agf->fetch_all($sortorder, $sortfield, $limit, $offset, $filter);

if ($result >= 0) {

	print '<form method="get" action="' . $_SERVER ['PHP_SELF'] . '" name="searchFormList" id="searchFormList">' . "\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	if (! empty($sortfield)) {
		print '<input type="hidden" name="sortfield" value="' . $sortfield . '"/>';
	}
	if (! empty($sortorder)) {
		print '<input type="hidden" name="sortorder" value="' . $sortorder . '"/>';
	}
	if (! empty($page)) {
		print '<input type="hidden" name="page" value="' . $page . '"/>';
	}
	if (! empty($limit)) {
		print '<input type="hidden" name="limit" value="' . $limit . '"/>';
	}

	print_barre_liste($langs->trans("AgfStagiaireList"), $page, $_SERVER ['PHP_SELF'], $option, $sortfield, $sortorder, '', $result, $nbtotalofrecords, 'title_generic.png', 0, '', '', $limit);

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
	$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
	print '<table class="noborder tagtable liste listwithfilterbefore" width="100%">';

	print '<tr class="liste_titre_filter">';

	if (! empty($arrayfields['s.rowid']['checked'])) {
		print '<td>&nbsp;</td>';
	}

	if (! empty($arrayfields['s.nom']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_name" value="' . $search_name . '" size="10">';
		print '<input type="text" class="flat" name="search_firstname" value="' . $search_firstname . '" size="10">';
		print '</td>';
	}

	if (! empty($arrayfields['civ.code']['checked'])) {
		print '<td class="liste_titre">';
		print $formcompagny->select_civility($search_civ, 'search_civ');
		print '</td>';
	}

	if (! empty($arrayfields['so.nom']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_soc" value="' . $search_soc . '" size="20">';
		print '</td>';
	}

	if (! empty($arrayfields['s.tel1']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_tel" value="' . $search_tel . '" size="10">';
		print '</td>';
	}

	if (! empty($arrayfields['s.tel2']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_tel2" value="' . $search_tel2 . '" size="10">';
		print '</td>';
	}

	if (! empty($arrayfields['s.mail']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_mail" value="' . $search_mail . '" size="20">';
		print '</td>';
	}

	// Extra fields
	if (file_exists(DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php')) {
		$object=$agf;
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';
	} else {
		if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
			foreach ($extrafields->attribute_label as $key => $val) {
				if (! empty($arrayfields["ef." . $key]['checked'])) {
					$align = $extrafields->getAlignFlag($key);
					$typeofextrafield = $extrafields->attribute_type[$key];
					print '<td class="liste_titre' . ($align ? ' ' . $align : '') . '">';
					if (in_array($typeofextrafield, array(
							'varchar',
							'int',
							'double',
							'select'
					)) && empty($extrafields->attribute_computed[$key])) {
						$crit = $val;
						$tmpkey = preg_replace('/search_options_/', '', $key);
						$searchclass = '';
						if (in_array($typeofextrafield, array(
								'varchar',
								'select'
						)))
							$searchclass = 'searchstring';
							if (in_array($typeofextrafield, array(
									'int',
									'double'
							)))
								$searchclass = 'searchnum';
								print '<input class="flat' . ($searchclass ? ' ' . $searchclass : '') . '" size="4" type="text" name="search_options_' . $tmpkey . '" id="search_options_' . $tmpkey . '" value="' . dol_escape_htmltag($search_array_options['search_options_' . $tmpkey]) . '">';
					} else {
						// for the type as 'checkbox', 'chkbxlst', 'sellist' we should use code instead of id (example: I declare a 'chkbxlst' to have a link with dictionnairy, I have to extend it with the 'code' instead 'rowid')
						echo $extrafields->showInputField($key, $search_array_options['search_options_' . $key], '', '', 'search_');
					}
					print '</td>';
				}
			}
		}
	}

	// Action column
	print '<td class="liste_titre" align="right">';
	if(method_exists($form, 'showFilterButtons')) {
		$searchpicto=$form->showFilterButtons();

		print $searchpicto;
	} else {
		print '<input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
		print '&nbsp; ';
		print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	}
	print '</td>';

	print "</tr>\n";


	print '<tr class="liste_titre">';
	if (! empty($arrayfields['s.rowid']['checked'])) {
		print_liste_field_titre($langs->trans("Id"), $_SERVER ['PHP_SELF'], "s.rowid", "", $option, '', $sortfield, $sortorder);
	}
	if (! empty($arrayfields['s.nom']['checked'])) {
		print_liste_field_titre($langs->trans("AgfNomPrenom"), $_SERVER ['PHP_SELF'], "s.nom", "", $option, '', $sortfield, $sortorder);
	}
	if (! empty($arrayfields['civ.code']['checked'])) {
		print_liste_field_titre($langs->trans("AgfCivilite"), $_SERVER ['PHP_SELF'], "civ.code", "", $option, '', $sortfield, $sortorder);
	}
	if (! empty($arrayfields['so.nom']['checked'])) {
		print_liste_field_titre($langs->trans("Company"), $_SERVER ['PHP_SELF'], "so.nom", "", $option, '', $sortfield, $sortorder);
	}
	if (! empty($arrayfields['s.tel1']['checked'])) {
		print_liste_field_titre($langs->trans("Phone"), $_SERVER ['PHP_SELF'], "s.tel1", "", $option, '', $sortfield, $sortorder);
	}
	if (! empty($arrayfields['s.tel2']['checked'])) {
		print_liste_field_titre($langs->trans("Mobile"), $_SERVER ['PHP_SELF'], "s.tel2", "", $option, '', $sortfield, $sortorder);
	}
	if (! empty($arrayfields['s.mail']['checked'])) {
		print_liste_field_titre($langs->trans("Mail"), $_SERVER ['PHP_SELF'], "s.mail", "", $option, '', $sortfield, $sortorder);
	}

	// Extra fields
	if (file_exists(DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php')) {
		$object=$agf;
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';
	} else {
		if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
			foreach ($extrafields->attribute_label as $key => $val) {
				if (! empty($arrayfields["ef." . $key]['checked'])) {
					$align = $extrafields->getAlignFlag($key);
					$sortonfield = "ef." . $key;
					if (! empty($extrafields->attribute_computed[$key]))
						$sortonfield = '';
						print getTitleFieldOfList($langs->trans($extralabels[$key]), 0, $_SERVER["PHP_SELF"], $sortonfield, "", $option, ($align ? 'align="' . $align . '"' : ''), $sortfield, $sortorder) . "\n";
				}
			}
		}
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"', $sortfield, $sortorder,'maxwidthsearch ');
	print "</tr>\n";

	$var = true;
	foreach ( $agf->lines as $line ) {

		// Affichage liste des stagiaires
		$var = ! $var;
		print "<tr $bc[$var]>";
		if (! empty($arrayfields['s.rowid']['checked'])) {
			print '<td><a href="card.php?id=' . $line->rowid . '">' . img_object($langs->trans("AgfShowDetails"), "user") . ' ' . $line->rowid . '</a></td>';
		}

		if (! empty($arrayfields['s.nom']['checked'])) {
			print '<td><a href="card.php?id=' . $line->rowid . '">' . strtoupper($line->nom) . ' ' . ucfirst($line->prenom) . '</a></td>';
		}

		if (! empty($arrayfields['civ.code']['checked'])) {
			$contact_static = new Contact($db);
			$contact_static->civility_id = $line->civilite;
			$contact_static->civility_code = $line->civilite;

			print '<td>' . $contact_static->getCivilityLabel() . '</td>';
		}

		if (! empty($arrayfields['so.nom']['checked'])) {
			print '<td>';
			if ($line->socid) {
				print '<a href="' . dol_buildpath('/comm/card.php', 1) . '?socid=' . $line->socid . '">';
				print img_object($langs->trans("ShowCompany"), "company") . ' ' . dol_trunc($line->socname, 20) . '</a>';
			} else {
				print '&nbsp;';
			}
			print '</td>';
		}
		if (! empty($arrayfields['s.tel1']['checked'])) {
			print '<td>' . dol_print_phone($line->tel1) . '</td>';
		}
		if (! empty($arrayfields['s.tel2']['checked'])) {
			print '<td>' . dol_print_phone($line->tel2) . '</td>';
		}
		if (! empty($arrayfields['s.mail']['checked'])) {
			print '<td>' . dol_print_email($line->mail, $line->rowid, $line->socid, 'AC_EMAIL', 25) . '</td>';
		}

		// Extra fields
		if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) {
			foreach ( $extrafields->attribute_label as $key => $val ) {
				if (! empty($arrayfields["ef." . $key]['checked'])) {
					$align = $extrafields->getAlignFlag($key);
					print '<td';
					if ($align)
						print ' align="' . $align . '"';
						print '>';
						$tmpkey = 'options_' . $key;
						print $extrafields->showOutputField($key, $line->array_options[$tmpkey], '');
						print '</td>';
						if (! $i)
							$totalarray['nbfield'] ++;
							if (! empty($val['isameasure'])) {
								if (! $i)
									$totalarray['pos'][$totalarray['nbfield']] = 'ef.' . $tmpkey;
									$totalarray['val']['ef.' . $tmpkey] += $line->array_options[$tmpkey];
							}
				}
			}
		}
		print '<td>&nbsp;</td>';
		print "</tr>\n";
	}

	print "</table>";
	print '</form>';
} else {
	setEventMessage($agf->error, 'errors');
}

llxFooter();
$db->close();
