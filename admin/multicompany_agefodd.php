<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$res = @include ("../../main.inc.php"); // For root directory
if (!$res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (!$res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once '../class/html.formagefodd.class.php';
require_once '../lib/agefodd.lib.php';

$langs->load("admin");
$langs->load('agefodd@agefodd');
$action = GETPOST("action", 'none');
if ($action == 'save_multicompany_shared_conf')
{
	$multicompanypriceshare = GETPOST('multicompany-agefodd', 'array');
	$dao = new DaoMulticompany($db);
	$dao->getEntities();

	foreach ($dao->entities as $entity)
	{
		$entity->options['sharings']['agefodd'] = array();
		$entity->update($entity->id, $user);
	}

	if (!empty($multicompanypriceshare))
	{

		foreach ($multicompanypriceshare as $entityId => $shared)
		{

			//'MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED
			if (is_array($shared))
			{
				$shared = array_map('intval', $shared);


				if ($dao->fetch($entityId) > 0)
				{
					$dao->options['sharings']['agefodd'] = $shared;
					if ($dao->update($entityId, $user) < 1)
					{
						setEventMessage('Error');
					}
				}
			}
		}
	}
}


$extrajs = $extracss = array();
if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
{
	$extrajs = array(
		'/multicompany/inc/multiselect/js/ui.multiselect.js',
	);
	$extracss = array(
		'/multicompany/inc/multiselect/css/ui.multiselect.css',
	);
}

llxHeader('', $langs->trans('AgefoddSetupMulticompany'), '', '', '', '', $extrajs, $extracss);



$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("AgefoddSetupMulticompany"), $linkback, 'setup');

// Configuration header
$head = agefodd_admin_prepare_head();
dol_fiche_head($head, 'multicompany', $langs->trans("Module103000Name"), 0, "agefodd@agefodd");



if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
{

	print '<br><br>';

	//var_dump($mc);
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="save_multicompany_shared_conf">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Multicompany").'</td>'."\n";
	print '<td align="center" ></td>';
	print '</tr>';

	$element = 'agefodd';
	$moduleSharingEnabled = 'MULTICOMPANY_'.strtoupper($element).'_SHARING_ENABLED';



	print '<tr class="oddeven" >';
	print '<td align="left" >';
	print $langs->trans("ActivateSharing");
	print '</td>';
	print '<td align="center" >';
	print ajax_constantonoff($moduleSharingEnabled, array(), 0);
	print '</td>';
	print '</tr>';


	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("MulticompanyConfiguration").'</td>'."\n";
	print '<td align="center" >'.$langs->trans("ShareWith").'</td>';
	print '</tr>';

	$m = new ActionsMulticompany($db);

	$dao = new DaoMulticompany($db);
	$dao->getEntities();

	if (is_array($dao->entities))
	{

		foreach ($dao->entities as $entitie)
		{

			if (intval($conf->entity) === 1 || intval($conf->entity) === intval($entitie->id))
			{

				print '<tr class="oddeven" >';
				print '<td align="left" >';
				print $entitie->name.' <em>('.$entitie->label.')</em> ';
				//
				print '</td>';
				print '<td align="center" >';
				print _multiselect_entities('multicompany-agefodd['.$entitie->id.']', $entitie, '', $element);
				print '</td>';
				print '</tr>';
			}
		}


		print '<tr>';
		print '<td colspan="2" style="text-align:right;" >';
		print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
		print '</td>';
		print '</tr>';
	}
	print '</table>';

	print '</form>';

	$langs->loadLangs(array('languages', 'multicompany@multicompany'));

	print '<script type="text/javascript">';
	print '$(document).ready(function () {';

	print '     $.extend($.ui.multiselect.locale, {';
	print '         addAll:\''.$langs->transnoentities("AddAll").'\',';
	print '         removeAll:\''.$langs->transnoentities("RemoveAll").'\',';
	print '         itemsCount:\''.$langs->transnoentities("ItemsCount").'\'';
	print '    });';


	print '    $(function(){';
	print '        $(".multiselect").multiselect({sortable: false, searchable: false});';
	print '    });';
	print '});';
	print '</script>';
}


llxFooter();

/**
 * 	Return multiselect list of entities.
 *
 * 	@param	string	$htmlname	Name of select
 * 	@param	DaoMulticompany	$current	Current entity to manage
 * 	@param	string	$option		Option
 * 	@return	string
 */
function _multiselect_entities($htmlname, $current, $option = '', $sharingElement = '')
{
	global $conf, $langs, $db;

	$dao = new DaoMulticompany($db);
	$dao->getEntities();

	$sharingElement = !empty($sharingElement) ? $sharingElement : $htmlname;

	$return = '<select id="'.$htmlname.'" class="multiselect" multiple="multiple" name="'.$htmlname.'[]" '.$option.'>';
	if (is_array($dao->entities))
	{
		foreach ($dao->entities as $entity)
		{
			if (is_object($current) && $current->id != $entity->id && $entity->active == 1)
			{

				$return .= '<option value="'.$entity->id.'" ';
				if (is_array($current->options['sharings'][$sharingElement]) && in_array($entity->id, $current->options['sharings'][$sharingElement]))
				{
					$return .= 'selected="selected"';
				}
				$return .= '>';
				$return .= $entity->label;
				if (empty($entity->visible))
				{
					$return .= ' ('.$langs->trans('Hidden').')';
				}
				$return .= '</option>';
			}
		}
	}
	$return .= '</select>';

	return $return;
}
