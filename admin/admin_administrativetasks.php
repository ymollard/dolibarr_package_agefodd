<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2017	Florian Henry	<florian.henry@open-concept.pro>
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
 * \file /agefodd/admin/admin_administrativetasks.php
 * \ingroup agefodd
 * \brief agefood module setup page
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res) $res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res) die("Include of main fails");

require_once '../class/agefodd_formation_catalogue.class.php';
require_once '../class/agefodd_session_admlevel.class.php';
require_once '../class/agefodd_calendrier.class.php';
require_once '../class/html.formagefodd.class.php';
require_once '../lib/agefodd.lib.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("admin");
$langs->load('agefodd@agefodd');

if (! $user->rights->agefodd->admin && ! $user->admin)
    accessforbidden();

$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

llxHeader('', $langs->trans('AgefoddSetupDesc'));

if ($action == 'sessionlevel_create') {
    $agf = new Agefodd_session_admlevel($db);

    $parent_level = GETPOST('parent_level', 'int');

    if (! empty($parent_level)) {
        $agf->fk_parent_level = $parent_level;

        $agf_static = new Agefodd_session_admlevel($db);
        $result_stat = $agf_static->fetch($agf->fk_parent_level);

        if ($result_stat > 0) {
            if (! empty($agf_static->id)) {
                $agf->level_rank = $agf_static->level_rank + 1;
                $agf->indice = ebi_get_adm_get_next_indice_action($agf_static->id);
            } else { // no parent : This case may not occur but we never know
                $agf->indice = (ebi_get_adm_level_number() + 1) . '00';
                $agf->level_rank = 0;
            }
        } else {
            setEventMessage($agf_static->error, 'errors');
        }
    } else {
        // no parent
        $agf->fk_parent_level = 0;
        $agf->indice = (ebi_get_adm_level_number() + 1) . '00';
        $agf->level_rank = 0;
    }

    $agf->intitule = GETPOST('intitule', 'alpha');
    $agf->delais_alerte = GETPOST('delai', 'int');
    $agf->delais_alerte_end = GETPOST('delai_end', 'int');

    // prevent mysql error
    if(empty($agf->delais_alerte)){ $agf->delais_alerte = 0 ; }
    if(empty($agf->delais_alerte_end)){ $agf->delais_alerte_end= 0 ; }

    if ($agf->level_rank > 3) {
        setEventMessage($langs->trans("AgfAdminNoMoreThan3Level"), 'errors');
    } else {
        $result = $agf->create($user);

        if ($result != 1) {
            setEventMessage($agf->error, 'errors');
        }
    }
}

if ($action == 'sessionlevel_update') {
    $agf = new Agefodd_session_admlevel($db);

    $id = GETPOST('id', 'int');
    $parent_level = GETPOST('fk_parent_level', 'int');

    $result = $agf->fetch($id);

    if ($result > 0) {


		$agf->intitule = GETPOST('intitule', 'alpha');
		$agf->delais_alerte = GETPOST('delai', 'int');
		$agf->delais_alerte_end = GETPOST('delai_end', 'int');

		// prevent mysql error
		if(empty($agf->delais_alerte)){ $agf->delais_alerte = 0 ; }
		if(empty($agf->delais_alerte_end)){ $agf->delais_alerte_end= 0 ; }

		if (! empty($parent_level)) {
			if ($parent_level != $agf->fk_parent_level) {
				$agf->fk_parent_level = $parent_level;

				$agf_static = new Agefodd_session_admlevel($db);
				$result_stat = $agf_static->fetch($agf->fk_parent_level);

				if ($result_stat > 0) {
					if (! empty($agf_static->id)) {
						$agf->level_rank = $agf_static->level_rank + 1;
						$agf->indice = ebi_get_adm_get_next_indice_action($agf_static->id);

					} else { // no parent : This case may not occur but we never know
						$agf->indice = (ebi_get_adm_level_number() + 1) . '00';
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
				setEventMessage($agf->error, 'errors');
			}
		}

    } else {
        setEventMessage('This action do not exists', 'errors');
    }
}

if ($action == 'sesslevel_remove' && $confirm == 'yes'){
    $agf = new Agefodd_session_admlevel($db);

    $id = GETPOST('id', 'int');

    $result = $agf->fetch($id);

    if ($result > 0) {
        $result = $agf->delete($user);
        if ($result != 1) {
            setEventMessage($agf->error, 'errors');
        }
    }
}

/*
 *  Admin Form
 *
 */
$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother=new FormOther($db);

dol_htmloutput_mesg($mesg);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans("AgefoddSetupDesc"), $linkback, 'setup');

// Configuration header
$head = agefodd_admin_prepare_head();
dol_fiche_head($head, 'administrativetasks', $langs->trans("Module103000Name"), 0, "agefodd@agefodd");

dol_fiche_end(0);
// Admin Training level administation

$admlevel = new Agefodd_session_admlevel($db);
$result0 = $admlevel->fetch_all();

$morehtmlright = '';
print load_fiche_titre($langs->trans("AgfAdminSessionLevel"), $morehtmlright);

$sesslevel_remove = GETPOST('sesslevel_remove', 'none');
if ($action == 'sessionlevel_update' && !empty($sesslevel_remove) && empty($confirm)){
	$deleteConfirmUrl = $_SERVER ['PHP_SELF'].'?sesslevel_remove=1&id='. GETPOST('id', 'int');
	print $form->formconfirm($deleteConfirmUrl, $langs->trans('ConfirmDelete'), '', 'sesslevel_remove', '', 0, 1);
}

$TNested = $admlevel->fetch_all_children_nested(0);

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
print '<td>' . $langs->trans("Add") . ' <input type="text" name="intitule" value="" size="30" placeholder="' . $langs->trans("AgfIntitule") . '"/></td>';
print '<td>' . $formAgefodd->select_action_session_adm('', 'parent_level', 0, $trainingid) . '</td>';
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
				url: "'.dol_buildpath('agefodd/scripts/interface.php?action=setAgefoddAdminAdmlevelHierarchy', 1).'",
				method: "POST",
				data: {
					\'items\' : $(\'#sortableLists\').sortableListsToHierarchy()
				},
				dataType: "json",

				// La fonction à apeller si la requête aboutie
				success: function (data) {
					// Loading data
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
		},
		opener: {
			active: true,
			as: \'html\',  // if as is not set plugin uses background image
			close: \'<i class="fa fa-minus c3"></i>\',  // or \'fa-minus c3\',  // or \'./imgs/Remove2.png\',
			open: \'<i class="fa fa-plus"></i>\',  // or \'fa-plus\',  // or\'./imgs/Add2.png\',
			openerCss: {
				\'display\': \'inline-block\',
				\'float\': \'left\',
				\'margin-left\': \'-35px\',
				\'margin-right\': \'5px\',
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
	dialogBox.dialog({
	autoOpen: false,
	resizable: true,
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
 * @param $admlevel Agefodd_session_admlevel
 */
function _displayFormField($admlevel)
{
	global $langs;

	$outForm= '<form name="SessionLevel_update" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
	$outForm.= '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">' . "\n";
	$outForm.= '<input type="hidden" name="id" value="' . $admlevel->id . '">' . "\n";
	$outForm.= '<input type="hidden" name="fk_parent_level" value="' . $admlevel->fk_parent_level . '">' . "\n";
	$outForm.= '<input type="hidden" name="action" value="sessionlevel_update">' . "\n";

	// changed by JS
	$outForm.= '<p>';
	//$outForm.= '<label>' . $langs->trans("AgfIntitule") . '</label><br/>';
	$outForm.= '<input type="text" name="intitule" style="width:100%;max-width:900px" placeholder="' . dol_escape_htmltag($langs->trans("AgfIntitule")) . '" value="' . dol_escape_htmltag($admlevel->intitule) . '" size="30"/>';
	$outForm.= '</p>';

	$outForm.= '<p>';
	$outForm.= '<label>' . $langs->trans("AgfDelaiSessionLevel") . '</label><br/>';
	$outForm.= '<i class="fa fa-hourglass-start"></i> ';
	$outForm.= '<input type="number" step="1" name="delai" value="' . $admlevel->alerte . '"/>';
	$outForm.= ' '.$langs->trans('days');
	$outForm.= '</p>';

	$outForm.= '<p>';
	$outForm.= '<label>' . $langs->trans("AgfDelaiSessionLevelEnd") . '</label><br/>';
	$outForm.= '<i class="fa fa-hourglass-start"></i> ';
	$outForm.= '<input type="number" step="1" name="delai_end" value="' . $admlevel->alerte_end . '"/>';
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
			if($open) {
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

			$deleteUrl = $_SERVER ['PHP_SELF'].'?sesslevel_remove=1&amp;id='. $object->id.'&amp;action=sessionlevel_update&amp;sesslevel_remove=1';

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
