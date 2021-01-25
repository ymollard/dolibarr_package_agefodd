<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once '../class/agsession.class.php';
require_once '../class/html.formagefodd.class.php';
require_once '../lib/agefodd.lib.php';
require_once '../class/agefodd_session_element.class.php';

$id = GETPOST('id', 'none');
$with_calendar = GETPOST('with_calendar','alpha');
if (empty($with_calendar)) {
	$with_calendar = 'nocalendar';
}
llxHeader('', $langs->trans("Events/Agenda"));

if (! empty($id)) {
	$agf = new Agsession($db);
	$agf->fetch($id);

	$result = $agf->fetch_societe_per_session($id);

	// Display consult
	$head = session_prepare_head($agf);

	dol_fiche_head($head, 'agenda', $langs->trans("AgfSessionDetail"), 0, 'generic');

	dol_agefodd_banner_tab($agf, 'id');
	print '<div class="underbanner clearboth"></div>';

	// List of actions on element
	include_once (DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php');
	$formactions = new FormAgefodd($db);
	$somethingshown = $formactions->showactions($agf, 'agefodd_agsession', $socid, $with_calendar);
}

llxFooter();
