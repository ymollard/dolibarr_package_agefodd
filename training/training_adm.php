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
$parent_level = GETPOST('fk_parent_level', 'int');

if (empty($trainingid)) {
	$trainingid = $id;
}

$url = $_SERVER ['PHP_SELF'].'?token=' . $_SESSION ['newtoken'].'&trainingid=' . $trainingid;

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

		if ($result != 1) {
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
		if (GETPOST('sesslevel_up', 'none')) {
			$result2 = $agf->shift_indice($user, 'less');
			$updatedRowId = $id;
			if ($result2 != 1){
				setEventMessage($agf->error, 'errors');
			}
		}

		// Down level of action
		if (GETPOST('sesslevel_down', 'none')) {
			$result1 = $agf->shift_indice($user, 'more');
			$updatedRowId = $id;
			if ($result1 != 1) {
				setEventMessage($agf->error, 'errors');
			}
		}

		// Update action
		if (GETPOST('sesslevel_update', 'none')) {
			$agf->intitule = GETPOST('intitule', 'alpha');
			$agf->delais_alerte = GETPOST('delai', 'int');
			$agf->delais_alerte_end = GETPOST('delai_end', 'int');
			$updatedRowId = $id;
			if (! empty($parent_level)){
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
		if (GETPOST('sesslevel_remove', 'none') && GETPOST('confirm', 'none') == 'yes') {
			$result = $agf->delete($user);
			if ($result != 1) {
				setEventMessage($agf_static->error, 'errors');
			}

			header('Location: '.$url);
			exit;
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


$morehtmlright = '';

if ($action != 'sort' && function_exists('dolGetButtonTitle')) {
	$reloadUrl = $_SERVER ['PHP_SELF'] . '?action=replicateconfadmin&id=' . $trainingid;
	$morehtmlright .= dolGetButtonTitle($langs->trans('AgfReplaceByAdminLevel'), $langs->trans('AgfReplaceByAdminLevelHelp'), 'fa fa-refresh', $reloadUrl);
}


if ($result0 > 0) {
	// ne sert à rien si aucun resultats
//	if ($action != 'sort' && function_exists('dolGetButtonTitle')) {
//		$morehtmlright .= dolGetButtonTitle($langs->trans('AgfSortMode'), '', 'fa fa-sort', $url . '&action=sort');
//	}
//
//	if ($action === 'sort' && function_exists('dolGetButtonTitle')) {
//		$morehtmlright .= dolGetButtonTitle($langs->trans('AgfViewMode'), '', 'fa fa-sort', $url . '&action=view');
//	}
}

print load_fiche_titre($langs->trans("AgfAdminTrainingLevel"), $morehtmlright);

$sesslevel_remove = GETPOST('sesslevel_remove', 'none');
if ($action == 'sessionlevel_update' && !empty($sesslevel_remove)){
	$deleteConfirmUrl = $url.'&sesslevel_remove=1&id='. GETPOST('id', 'int').'&sesslevel_remove_confirm=1';
	print $form->formconfirm($deleteConfirmUrl, $langs->trans('ConfirmDelete'), '', 'sessionlevel_update', '', 0, 1);
}


$TNested = $admlevel->fetch_all_children_nested($trainingid, 0);


// ADD FORM
print '<table class="noborder noshadow" width="100%">';
print '<tr class="liste_titre nodrag nodrop">';
print '<th>' . $langs->trans("AgfIntitule") . '</th>';
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
print '<td>' . $langs->trans("Add") . ' <input type="text" name="intitule" value="" size="30" placeholder="' . $langs->trans("AgfIntitule") . '"/></td>';
print '<td>' . $formAgefodd->select_action_training_adm('', 'parent_level', 0, $trainingid) . '</td>';
print '<td><input type="number" step="1" name="delai" value=""/></td>';
print '<td><input type="number" step="1" name="delai_end" value=""/></td>';
print '<td><input type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/edit_add.png" border="0" name="sesslevel_update" alt="' . $langs->trans("Save") . '"></td>';
print '</form>';
print '</tr>';
print '</tfoot>';
print '</table>';



// JS nested
print '<div id="ajaxResults" ></div>';
print _displaySortableNestedItems($TNested, 'sortableLists', true);
print '<script src="'.dol_buildpath('agefodd/js/jquery-sortable-lists.min.js',1).'" ></script>';
print '<link rel="stylesheet" href="'.dol_buildpath('agefodd/css/sortable.css',1).'" >';
print '<div id="dialog-form-edit" >'._displayFormField($admlevel).'</div>';

print '
<script type="text/javascript">
$(function()
{
	var options = {
		insertZone: 5, // This property defines the distance from the left, which determines if item will be inserted outside(before/after) or inside of another item.

		placeholderClass: \'agf-sortable-list__item--placeholder\',
		// or like a jQuery css object
		//placeholderCss: {\'background-color\': \'#ff8\'},
		hintClass: \'agf-sortable-list__item--hint\',
		// or like a jQuery css object
		//hintCss: {\'background-color\':\'#bbf\'},
		onChange: function( cEl )
		{

			$("#ajaxResults").html("");

			$.ajax({
				url: "'.dol_buildpath('agefodd/scripts/interface.php?action=setAgefoddTrainingAdmlevelHierarchy',1).'",
				method: "POST",
				data: {
					\'items\' : $(\'#sortableLists\').sortableListsToHierarchy()
				},
				dataType: "json",

				// La fonction à apeller si la requête aboutie
				success: function (data) {
					// Loading data
					console.log(data);
					if(data.result > 0 ){
					   // ok case
					   $("#ajaxResults").html(\'<span class="badge badge-success">\' + data.msg + \'</span>\');
					}
					else if(data.result < 0 ){
					   // error case
					   $("#ajaxResults").html(\'<span class="badge badge-danger">\' + data.errorMsg + \'</span>\');
					}
					else{
					   // nothing to do ?
					}
				},
				// La fonction à appeler si la requête n\'a pas abouti
				error: function( jqXHR, textStatus ) {
					alert( "Request failed: " + textStatus );
				}
			});
		},
		complete: function( cEl )
		{



		},
		isAllowed: function( cEl, hint, target )
		{

			return true;

			// Be carefull if you test some ul/ol elements here.
			// Sometimes ul/ols are dynamically generated and so they have not some attributes as natural ul/ols.
			// Be careful also if the hint is not visible. It has only display none so it is at the previouse place where it was before(excluding first moves before showing).
//				if( target.data(\'module\') === \'c\' && cEl.data(\'module\') !== \'c\' )
//				{
//					hint.css(\'background-color\', \'#ff9999\');
//					return false;
//				}
//				else
//				{
//					hint.css(\'background-color\', \'#99ff99\');
//					return true;
//				}
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
		ignoreClass: \'clickable\',

		insertZonePlus: true,
	};


	$(\'#sortableLists\').sortableLists( options );

	$(document).on("click", ".agf-sortable-list__item__title__button.-edit-btn", function(event) {
		event.preventDefault();
		var id = $(this).data("id");
		popTrainingAdmFormDialog(id);
	});



	var dialogBox = jQuery("#dialog-form-edit");
	var width = $(window).width();
	var height = $(window).height();
	if(width > 700){ width = 700; }
	if(height > 600){ height = 600; }
	//console.log(height);
	dialogBox.dialog({
	autoOpen: false,
	resizable: true,
//		height: height,
	width: width,
	modal: true,
	buttons: {
			"'.$langs->transnoentitiesnoconv('Update').'": function() {
				dialogBox.find("form").submit();
				jQuery(this).dialog(\'close\');
			}
		}
	});

	function popTrainingAdmFormDialog(id)
	{

		var item = $("#item_" + id);

		dialogBox.dialog({
		  title: $("#item_" + id).data("title")
		});

		dialogBox.find( "input[name=\'id\']" ).val(id);
		dialogBox.find( "input[name=\'intitule\']" ).val(item.data("title"));
		dialogBox.find( "input[name=\'delai\']" ).val(item.data("alert"));
		dialogBox.find( "input[name=\'delai_end\']" ).val(item.data("alert_end"));
		dialogBox.find( "input[name=\'fk_parent_level\']" ).val(item.data("parent_level"));


		dialogBox.dialog( "open" );
	}

});


</script>';


llxFooter();
$db->close();


/**
 * @param $training_admlevel Agefodd_training_admlevel
 */
function _displayFormField($training_admlevel)
{
	global $langs, $url, $trainingid;

	$outForm= '<form name="SessionLevel_update" action="' . $url . '" method="POST">' . "\n";
	$outForm.= '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">' . "\n";
	$outForm.= '<input type="hidden" name="id" value="' . $training_admlevel->id . '">' . "\n";
	$outForm.= '<input type="hidden" name="fk_parent_level" value="' . $training_admlevel->fk_parent_level . '">' . "\n";
	$outForm.= '<input type="hidden" name="action" value="sessionlevel_update">' . "\n";
	$outForm.= '<input type="hidden" name="trainingid" value="' . $trainingid . '">' . "\n";
	$outForm.= '<input type="hidden" name="sesslevel_update" value="1">' . "\n";

	// changed by JS
	$outForm.= '<p>';
	//$outForm.= '<label>' . $langs->trans("AgfIntitule") . '</label><br/>';
	$outForm.= '<input type="text" name="intitule" style="width:100%;max-width:900px" placeholder="' . dol_escape_htmltag($langs->trans("AgfIntitule")) . '" value="' . dol_escape_htmltag($training_admlevel->intitule) . '" size="30"/>';
	$outForm.= '</p>';

	$outForm.= '<p>';
	$outForm.= '<label>' . $langs->trans("AgfDelaiSessionLevel") . '</label><br/>';
	$outForm.= '<i class="fa fa-hourglass-start"></i> ';
	$outForm.= '<input type="number" step="1" name="delai" value="' . $training_admlevel->alerte . '"/>';
	$outForm.= ' '.$langs->trans('days');
	$outForm.= '</p>';

	$outForm.= '<p>';
	$outForm.= '<label>' . $langs->trans("AgfDelaiSessionLevelEnd") . '</label><br/>';
	$outForm.= '<i class="fa fa-hourglass-start"></i> ';
	$outForm.= '<input type="number" step="1" name="delai_end" value="' . $training_admlevel->alerte_end . '"/>';
	$outForm.= ' '.$langs->trans('days');
	$outForm.= '</p>';


	$outForm.= '</form>';

	return $outForm;
}

function _displaySortableNestedItems($TNested, $htmlId='', $open = true){
	global $langs, $url;
	if(!empty($TNested) && is_array($TNested)){
		$out = '<ul id="'.$htmlId.'" class="agf-sortable-list" >';
		foreach ($TNested as $k => $v){
			$object = $v['object'];
			/**
			 * @var $object Agefodd_training_admlevel
			 */

			if(empty($object->id)) $object->id = $object->rowid;

			$class = '';
			if($open){
				$class.= 'sortableListsClosed';
			}

			$out.= '<li id="item_'.$object->id.'" class="agf-sortable-list__item '.$class.'" ';
			$out.= ' data-id="'.$object->id.'" ';
			$out.= ' data-title="'.dol_escape_htmltag($object->intitule).'" ';
			$out.= ' data-alert="'.dol_escape_htmltag($object->alerte).'" ';
			$out.= ' data-parent_level="'.dol_escape_htmltag($object->fk_parent_level).'" ';
			$out.= ' data-alert_end="'.dol_escape_htmltag($object->alerte_end).'" ';
			$out.= '>';
			$out.= '<div class="agf-sortable-list__item__title  move">';
				$out.= '<div class="agf-sortable-list__item__title__flex">';

				$out.= '<div class="agf-sortable-list__item__title__col">';
				$out.= dol_htmlentities($object->intitule);
				$out.= '</div>';

				$out.= '<div class="agf-sortable-list__item__title__col -day-alert">';
					$out.= '<span class="classfortooltip" title="'.$langs->trans("AgfDelaiSessionLevel").'" >';
					$out.= '<i class="fa fa-hourglass-start"></i> ' . $object->alerte .' '.$langs->trans('days');
					$out.= '</span>';
				$out.= '</div>';

				$out.= '<div class="agf-sortable-list__item__title__col -day-alert">';
					$out.= '<span class="classfortooltip"  title="'.$langs->trans("AgfDelaiSessionLevelEnd").'">';
					$out.= '<i class="fa fa-hourglass-end"></i> ' . $object->alerte_end .' '.$langs->trans('days');
					$out.= '</span>';
				$out.= '</div>';

				$out.= '<div class="agf-sortable-list__item__title__col -action clickable">';

					$out.= '<a href="" class="classfortooltip agf-sortable-list__item__title__button clickable -edit-btn"  title="' . $langs->trans("Edit") . '" data-id="'.$object->id.'">';
					$out.= '<i class="fa fa-pencil clickable"></i>';
					$out.= '</a>';

					$deleteUrl = $url.'&amp;sesslevel_remove=1&amp;id='. $object->id.'&amp;action=sessionlevel_update&amp;sesslevel_remove=1';

					$out.= '<a href="'.$deleteUrl.'" class="classfortooltip agf-sortable-list__item__title__button clickable -delete-btn"  title="' . $langs->trans("Delete") . '"  data-id="'.$object->id.'">';
					$out.= '<i class="fa fa-trash clickable"></i>';
					$out.= '</a>';
				$out.= '</div>';

				$out.= '</div>';
            $out.= '</div>';
			$out.= _displaySortableNestedItems($v['children'], '', $open);
			$out.= '</li>';
		}
		$out.= '</ul>';
		return $out;
	}
	else{
		return '';
	}
}

/**
 * Display nested administrative level : deprecated
 * @param $TNested
 * @return string
 */
function _displayEditableNestedItems($TNested){
    global $updatedRowId, $formAgefodd, $conf, $langs, $trainingid;

    $out = '';

    if(!empty($TNested) && is_array($TNested)){

        foreach ($TNested as $k => $v){
            $line = $v['object'];
            /**
             * @var $object Agefodd_training_admlevel
             */

            if(empty($line->id)) $line->id = $line->rowid;

            /**
             * @var $line Agefodd_training_admlevel
             */

            $rowClass = '';
            if ($updatedRowId == $line->rowid) {
                $rowClass = 'updated-row';
            }

            $out.= '<tr id="row-' . $line->rowid . '" class="oddeven ' . $rowClass . '" data-rowid="' . $line->rowid . '" >';
            $out.= '<form name="SessionLevel_update_' . $line->rowid . '" action="' . $_SERVER ['PHP_SELF'] . '#row-' . $line->rowid . '" method="POST">' . "\n";
            $out.= '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">' . "\n";
            $out.= '<input type="hidden" name="id" value="' . $line->rowid . '">' . "\n";
            $out.= '<input type="hidden" name="action" value="sessionlevel_update">' . "\n";
            $out.= '<input type="hidden" name="trainingid" value="' . $trainingid . '">' . "\n";


            $out.= '<td colspan="2">';

            $out.= str_repeat('&nbsp;&nbsp;&nbsp;', $line->level_rank);
            if (!empty($line->level_rank)) {
                $out.= '&#8627;';
            }
            $out.= '<input type="text" name="intitule" value="' . $line->intitule . '" size="30"/></td>';
            //$out.= '<td>' . $formAgefodd->select_action_training_adm($line->fk_parent_level, 'parent_level', $line->rowid, $trainingid) . '</td>';
            $out.= '<td><input type="number" step="1" name="delai" value="' . $line->alerte . '"/></td>';
            $out.= '<td><input type="number" step="1" name="delai_end" value="' . $line->alerte_end . '"/></td>';
            $out.= '<td class="right"><input type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/edit.png" border="0" name="sesslevel_update" alt="' . $langs->trans("Save") . '">';
            $out.= '<input type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/delete.png" border="0" name="sesslevel_remove" alt="' . $langs->trans("Delete") . '"></td>';
            $out.= '</form>';
            $out.= '</tr>';

            $out.= _displayEditableNestedItems($v['children']);

        }

        return $out;
    }
    else{
        return '';
    }
}
