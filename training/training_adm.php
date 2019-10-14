<?php
/*
 * Copyright (C) 2013-2016 Florian Henry <florian.henry@open-concept.pro>
 *
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
 * \file /agefodd/training/training_adm.php
 * \ingroup agefodd
 * \brief agefood agefodd admin training task by trainig
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory

require_once ('../class/agefodd_session_admlevel.class.php');
require_once ('../class/agefodd_training_admlevel.class.php');
require_once ('../class/agefodd_formation_catalogue.class.php');
require_once ('../class/html.formagefodd.class.php');
require_once ('../lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");
require_once __DIR__ .'/../lib/retroCompatibility.lib.php';
$langs->load("admin");
$langs->load('agefodd@agefodd');

// Security check
if (! $user->rights->agefodd->agefodd_formation_catalogue->lire)
	accessforbidden();

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$trainingid = GETPOST('trainingid', 'int');
$parent_level = GETPOST('parent_level', 'int');

if (empty($trainingid)) {
	$trainingid = $id;
}

if ($action == 'sessionlevel_create') {
	$agf = new Agefodd_training_admlevel($db);

	if (! empty($parent_level)) {
		$agf->fk_parent_level = $parent_level;

		$agf_static = new Agefodd_training_admlevel($db);
		$result_stat = $agf_static->fetch($agf->fk_parent_level);

		if ($result_stat > 0) {
			if (! empty($agf_static->id)) {
				$agf->level_rank = $agf_static->level_rank + 1;
				$agf->indice = ebi_get_adm_training_get_next_indice_action($agf_static->id);
			} else { // no parent : This case may not occur but we never know
				$agf->indice = (ebi_get_adm_training_level_number() + 1) . '00';
				$agf->level_rank = 0;
			}
		} else {
			setEventMessage($agf_static->error, 'errors');
		}
	} else {
		// no parent
		$agf->fk_parent_level = 0;
		$agf->indice = (ebi_get_adm_training_level_number() + 1) . '00';
		$agf->level_rank = 0;
	}

	$agf->fk_training = $trainingid;
	$agf->intitule = GETPOST('intitule', 'alpha');
	$agf->delais_alerte = GETPOST('delai', 'int');
	$agf->delais_alerte_end = GETPOST('delai_end', 'int');

	if ($agf->level_rank > 3) {
		setEventMessage($langs->trans("AgfAdminNoMoreThan3Level"), 'errors');
	} else {
		$result = $agf->create($user);

		if ($result1 != 1) {
			setEventMessage($agf->error, 'errors');
		}
	}
}

if ($action == 'replicateconfadmin') {
	$agf_adminlevel = new Agefodd_training_admlevel($db);
	$agf_adminlevel->fk_training = $id;
	$result = $agf_adminlevel->delete_training_task($user);
	if ($result < 0) {
		setEventMessage($agf_adminlevel->error, 'errors');
	}

	$agf = new Formation($db);
	$result = $agf->fetch($trainingid);
	$result = $agf->createAdmLevelForTraining($user);
	if ($result < 0) {
		setEventMessage($agf->error, 'errors');
	}
}
$updatedRowId = 0;
if ($action == 'sessionlevel_update') {
	$agf = new Agefodd_training_admlevel($db);

	$result = $agf->fetch($id);

	if ($result > 0) {
		// Up level of action
		if (GETPOST('sesslevel_up_x') || GETPOST('sesslevel_up')) {
			$result2 = $agf->shift_indice($user, 'less');
			$updatedRowId = $id;
			if ($result1 != 1){
				setEventMessage($agf->error, 'errors');
			}
		}

		// Down level of action
		if (GETPOST('sesslevel_down_x') || GETPOST('sesslevel_down')) {
			$result1 = $agf->shift_indice($user, 'more');
			$updatedRowId = $id;
			if ($result1 != 1) {
				setEventMessage($agf->error, 'errors');
			}
		}

		// Update action
		if (GETPOST('sesslevel_update_x')) {
			$agf->intitule = GETPOST('intitule', 'alpha');
			$agf->delais_alerte = GETPOST('delai', 'int');
			$agf->delais_alerte_end = GETPOST('delai_end', 'int');
			$updatedRowId = $id;
			if (! empty($parent_level)) {
				if ($parent_level != $agf->fk_parent_level) {
					$agf->fk_parent_level = $parent_level;

					$agf_static = new Agefodd_training_admlevel($db);
					$result_stat = $agf_static->fetch($agf->fk_parent_level);

					if ($result_stat > 0) {
						if (! empty($agf_static->id)) {
							$agf->level_rank = $agf_static->level_rank + 1;
							$agf->indice = ebi_get_adm_training_get_next_indice_action($agf_static->id);
						} else { // no parent : This case may not occur but we never know
							$agf->indice = (ebi_get_adm_training_level_number() + 1) . '00';
							$agf->level_rank = 0;
						}
					} else {
						setEventMessage($agf_static->error, 'errors');
					}
				}
			} else {
				// no parent
				$agf->fk_parent_level = 0;
				$agf->level_rank = 0;
			}

			if ($agf->level_rank > 3) {
				setEventMessage($langs->trans("AgfAdminNoMoreThan3Level"), 'errors');
			} else {
				$result1 = $agf->update($user);
				if ($result1 != 1) {
					setEventMessage($agf_static->error, 'errors');
				}
			}
		}

		// Delete action
		if (GETPOST('sesslevel_remove_x')) {

			$result = $agf->delete($user);
			if ($result != 1) {
				setEventMessage($agf_static->error, 'errors');
			}
		}
	} else {
		setEventMessage('This action do not exists', 'errors');
	}
}

/*
 * View
*/
$title = $langs->trans("AgfCatalogAdminTask");
llxHeader('', $title);

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

$agf = new Formation($db);
$result = $agf->fetch($trainingid);

$head = training_prepare_head($agf);

dol_fiche_head($head, 'trainingadmtask', $langs->trans("AgfCatalogDetail"), 0, 'label');

dol_agefodd_banner_tab($agf, 'id');
dol_fiche_end(0);
print '<div class="underbanner clearboth"></div>';

$admlevel = new Agefodd_training_admlevel($db);
$result0 = $admlevel->fetch_all($trainingid);

$url = $_SERVER ['PHP_SELF'].'?token=' . $_SESSION ['newtoken'].'&trainingid=' . $trainingid;
$morehtmlright = '';

if ($result0 > 0) {
	// ne sert à rien si aucun resultats
	if ($action != 'sort' && function_exists('dolGetButtonTitle')) {
		$morehtmlright .= dolGetButtonTitle($langs->trans('AgfSortMode'), '', 'fa fa-sort', $url . '&action=sort');
	}

	if ($action === 'sort' && function_exists('dolGetButtonTitle')) {
		$morehtmlright .= dolGetButtonTitle($langs->trans('AgfViewMode'), '', 'fa fa-sort', $url . '&action=view');
	}
}

print load_fiche_titre($langs->trans("AgfAdminTrainingLevel"), $morehtmlright);

print '<style type="text/css">
.button-no-style{
	border:none;
	background: none;
	margin: 0;
	cursor: pointer ;
}
tr.updated-row,tr.updated-row td{
	background: #d1e9f1 !important;
}
td[class*="col-lvl-"] {
  max-width: 15px;
  padding: 0 0 0 0 !important;
}

/* My addition to github theme */

html, body, ul, li { margin:0; padding:0; }

ul.agf-sortable-list,ul.agf-sortable-list ul,ul.agf-sortable-list li {
	list-style-type:none;
	color:#6e6e6e;
	border:1px solid #c3c3c3;
}

ul.agf-sortable-list{ padding:0; background-color:#f9f9f9; }

ul.agf-sortable-list li{
	padding-left:50px;
	margin:5px;
    border: 1px solid #c3c3c3;
    background-color: #dcdcdc;
}

ul.agf-sortable-list li div {
	padding:7px;
	background-color:#fff;
}
ul.agf-sortable-list li div.move {
	cursor: move;
}

.sortableListsOpener{
	cursor: pointer !important;
}

';
$datacolor = array(array(136,102,136), array(0,130,110), array(140,140,220), array(190,120,120), array(190,190,100), array(115,125,150), array(100,170,20), array(250,190,30), array(150,135,125), array(85,135,150), array(150,135,80), array(150,80,150));
foreach ($datacolor as $key => $color) {
	print '.button-no-style.color-level-'.$key.'{ color: rgb('.implode(',',$color).'); }';
}
print '</style>';

if($action==='sort'){
	$TNested = $admlevel->fetch_all_children_nested($trainingid, 'sortablelist');

	print _displayNestedItems($TNested, 'sortableLists');

	print '<script src="'.dol_buildpath('agefodd/js/jquery-sortable-lists.min.js',1).'" ></script>';
	print '	
	<script type="text/javascript">
	$(function()
	{
		var options = {
			placeholderCss: {\'background-color\': \'#ff8\'},
			hintCss: {\'background-color\':\'#bbf\'},
			onChange: function( cEl )
			{
				console.log( \'onChange\' );
			},
			complete: function( cEl )
			{
				console.log( \'complete\' );
			},
			isAllowed: function( cEl, hint, target )
			{
				// Be carefull if you test some ul/ol elements here.
				// Sometimes ul/ols are dynamically generated and so they have not some attributes as natural ul/ols.
				// Be careful also if the hint is not visible. It has only display none so it is at the previouse place where it was before(excluding first moves before showing).
				if( target.data(\'module\') === \'c\' && cEl.data(\'module\') !== \'c\' )
				{
					hint.css(\'background-color\', \'#ff9999\');
					return false;
				}
				else
				{
					hint.css(\'background-color\', \'#99ff99\');
					return true;
				}
			},
			opener: {
				active: true,
				as: \'html\',  // if as is not set plugin uses background image
				close: \'<i class="fa fa-minus c3"></i>\',  // or \'fa-minus c3\',  // or \'./imgs/Remove2.png\',
				open: \'<i class="fa fa-plus"></i>\',  // or \'fa-plus\',  // or\'./imgs/Add2.png\',
				openerCss: {
					\'display\': \'inline-block\',
					//\'width\': \'18px\', \'height\': \'18px\',
					\'float\': \'left\',
					\'margin-left\': \'-35px\',
					\'margin-right\': \'5px\',
					//\'background-position\': \'center center\', \'background-repeat\': \'no-repeat\',
					\'font-size\': \'1.1em\'
				}
			},
			ignoreClass: \'clickable\'
		};
		var optionsPlus = {
			insertZonePlus: true,
			placeholderCss: {\'background-color\': \'#ff8\'},
			hintCss: {\'background-color\':\'#bbf\'},
			opener: {
				active: true,
				as: \'html\',  // if as is not set plugin uses background image
				close: \'<i class="fa fa-minus c3"></i>\',
				open: \'<i class="fa fa-plus"></i>\',
				openerCss: {
					\'display\': \'inline-block\',
					\'float\': \'left\',
					\'margin-left\': \'-35px\',
					\'margin-right\': \'5px\',
					\'font-size\': \'1.1em\'
				}
			}
		};
	
		$(\'#sortableLists\').sortableLists( options );
		//$(\'#sTreePlus\').sortableLists( optionsPlus );

	
	});

	</script>';

}
else{

	print '<table class="noborder noshadow" width="100%">';

	if ($result0 > 0) {

		$maxLevel = 0;
		foreach ($admlevel->lines as $line) {
			$maxLevel = max($line->level_rank, $maxLevel);
		}

		print '<thead>';
		print '<tr class="liste_titre nodrag nodrop">';
		print '<th></th>';
		print '<th colspan="' . $maxLevel . '"  >' . $langs->trans("AgfIntitule") . '</th>';
		print '<th>' . $langs->trans("AgfParentLevel") . '</th>';
		print '<th>' . $langs->trans("AgfDelaiSessionLevel") . '</th>';
		print '<th>' . $langs->trans("AgfDelaiSessionLevelEnd") . '</th>';
		print '<th></th>';
		print "</tr>\n";
		print '</thead>';

		print '<tbody>';
		$var = true;
		foreach ($admlevel->lines as $line) {
			/**
			 * @var $line Agefodd_training_admlevel
			 */
			$var = !$var;
			$toplevel = '';

			$rowClass = '';
			if ($updatedRowId == $line->rowid) {
				$rowClass = 'updated-row';
			}

			print '<tr id="row-' . $line->rowid . '" class="oddeven ' . $rowClass . '" data-rowid="' . $line->rowid . '" >';
			print '<form name="SessionLevel_update_' . $line->rowid . '" action="' . $_SERVER ['PHP_SELF'] . '#row-' . $line->rowid . '" method="POST">' . "\n";
			print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">' . "\n";
			print '<input type="hidden" name="id" value="' . $line->rowid . '">' . "\n";
			print '<input type="hidden" name="action" value="sessionlevel_update">' . "\n";
			print '<input type="hidden" name="trainingid" value="' . $trainingid . '">' . "\n";

			$i = 0;
			while ($maxLevel > $i) {
				if (intval($line->level_rank) === $i) {
					print '<td class="col-lvl-' . $i . '">';
					if ($line->indice != ebi_get_adm_training_indice_per_rank($line->level_rank, $line->fk_parent_level, 'MIN')) {
						//print '<input type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/sort_asc.png" border="0" name="sesslevel_up" alt="' . $langs->trans("Up") . '">';
						$iconClass = 'fa fa-caret-up';
						if (empty($line->level_rank)) {
							$iconClass = 'fa fa-chevron-up';
						}

						print '<button type="submit" class="classfortooltip button-no-style color-level-' . $line->level_rank . '" data-level="' . $line->level_rank . '" name="sesslevel_up" value="1" title="' . $langs->trans("AgfUp") . '"><i class="' . $iconClass . '"></i></button>';
					}
					if ($line->indice != ebi_get_adm_training_indice_per_rank($line->level_rank, $line->fk_parent_level, 'MAX')) {
						//print '<input type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/sort_desc.png" border="0" name="sesslevel_down" alt="' . $langs->trans("Down") . '">';
						$iconClass = 'fa fa-caret-down';
						if (empty($line->level_rank)) {
							$iconClass = 'fa fa-chevron-down';
						}
						print '<button type="submit" class="classfortooltip button-no-style color-level-' . $line->level_rank . '" data-level="' . $line->level_rank . '" name="sesslevel_down" value="1"  title="' . $langs->trans("AgfDown") . '"><i class="' . $iconClass . '"></i></button>';
					}
					print '</td>';
				} else {
					print '<td class="col-lvl-' . $i . '"></td>';
				}

				$i++;
			}

			print '<td>';

			print str_repeat('&nbsp;&nbsp;&nbsp;', $line->level_rank);
			if (!empty($line->level_rank)) {
				print '&#8627;';
			}
			print '<input type="text" name="intitule" value="' . $line->intitule . '" size="30"/></td>';
			print '<td>' . $formAgefodd->select_action_training_adm($line->fk_parent_level, 'parent_level', $line->rowid, $trainingid) . '</td>';
			print '<td><input type="text" name="delai" value="' . $line->alerte . '"/></td>';
			print '<td><input type="text" name="delai_end" value="' . $line->alerte_end . '"/></td>';
			print '<td class="right"><input type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/edit.png" border="0" name="sesslevel_update" alt="' . $langs->trans("Save") . '">';
			print '<input type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/delete.png" border="0" name="sesslevel_remove" alt="' . $langs->trans("Delete") . '"></td>';
			print '</form>';
			print '</tr>';
		}
		print '</tbody>';
	}

	print '<tfoot>';
	print '<tr class="liste_titre nodrag nodrop">';
	print '<th></th>';
	print '<th colspan="' . $maxLevel . '" >' . $langs->trans("AgfIntitule") . '</th>';
	print '<th>' . $langs->trans("AgfParentLevel") . '</th>';
	print '<th>' . $langs->trans("AgfDelaiSessionLevel") . '</th>';
	print '<th>' . $langs->trans("AgfDelaiSessionLevelEnd") . '</th>';
	print '<th></th>';
	print "</tr>\n";
	print '<tr class="oddeven nodrag nodrop">';
	print '<form name="SessionLevel_create" action="' . $_SERVER ['PHP_SELF'] . '" method="POST">' . "\n";
	print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">' . "\n";
	print '<input type="hidden" name="action" value="sessionlevel_create">' . "\n";
	print '<input type="hidden" name="trainingid" value="' . $trainingid . '">' . "\n";
	print '<td ></td>';
	print '<td colspan="' . $maxLevel . '" >' . $langs->trans("Add") . ' <input type="text" name="intitule" value="" size="30"/></td>';
	print '<td>' . $formAgefodd->select_action_training_adm('', 'parent_level', 0, $trainingid) . '</td>';
	print '<td><input type="text" name="delai" value=""/></td>';
	print '<td><input type="text" name="delai_end" value=""/></td>';
	print '<td><input type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/edit_add.png" border="0" name="sesslevel_update" alt="' . $langs->trans("Save") . '"></td>';
	print '</form>';
	print '</tr>';
	print '</tfoot>';
	print '</table><br>';

	print '<div class="tabsAction">';
	print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?action=replicateconfadmin&id=' . $trainingid . '" title="' . $langs->trans('AgfReplaceByAdminLevelHelp') . '">' . $langs->trans('AgfReplaceByAdminLevel') . '</a>';
	print '</div>';

}

llxFooter();
$db->close();

function _displayNestedItems($TNested, $htmlId=''){
	if(!empty($TNested) && is_array($TNested)){
		$out = '<ul id="'.$htmlId.'" class="agf-sortable-list" >';
		foreach ($TNested as $k => $v){
			$object = $v['object'];
			/**
			 * @var $object Agefodd_training_admlevel
			 */
			$out.= '<li>';
			$out.= '<div class="move">'.dol_htmlentities($object->intitule).'</div>';
			$out.= _displayNestedItems($v['children']);
			$out.= '</li>';
		}
		$out.= '</ul>';
		return $out;
	}
	else{
		return '';
	}
}
