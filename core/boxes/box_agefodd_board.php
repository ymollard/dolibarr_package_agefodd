<?php
/* References letters
 * Copyright (C) 2014  HENRY Florian  florian.henry@open-concept.pro
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file		core/boxes/mybox.php
 * \ingroup	referenceletters
 * \brief		This file is a sample box definition file
 * Put some comments here
 */
include_once DOL_DOCUMENT_ROOT . "/core/boxes/modules_boxes.php";

/**
 * Class to manage the box
 */
class box_agefodd_board extends ModeleBoxes {
	var $boxcode = "agefodd_board";
	var $boximg = "agefodd@agefodd";
	var $boxlabel;
	var $depends = array (
			"agefodd"
	);
	var $db;
	var $param;
	var $info_box_head = array ();
	var $info_box_contents = array ();

	/**
	 * Constructor
	 */
	function __construct() {
		global $langs,$user;
		$langs->load("boxes");

		$this->boxlabel = $langs->transnoentitiesnoconv("AgefoddShort").'-'.$langs->transnoentitiesnoconv("AgfIndexBoard");

		$this->hidden=! ($user->rights->agefodd->lire);
	}

	/**
	 * Load data into info_box_contents array to show array later.
	 *
	 * @param int $max of records to load
	 * @return void
	 */
	function loadBox() {
		global $conf, $user, $langs, $db;

		$this->max = $max;

		dol_include_once('/agefodd/class/agefodd_index.class.php');

		$text = $langs->trans("AgfIndexBoard");
		$this->info_box_head = array (
				'text' => $text,
		);
		$key = 0;
		$agf = new Agefodd_index($db);

		$result = $agf->fetch_session(2);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		} else {

			$this->info_box_contents[$key][0] = array(
					'td' => 'align="left"',
					//'logo' => img_object($langs->trans("AgfRunningSession"), "generic",'',false,1),
					'text' => img_object($langs->trans("AgfRunningSession"), "generic").$langs->trans("AgfRunningSession")
			);
			$this->info_box_contents[$key][1] = array(
					'td' => 'align="left"',
					'text' => $agf->total,
					'url' => (dol_buildpath('/agefodd/session/list.php', 1) . '?status=2&mainmenu=agefodd')
			);
			$key ++;
		}

		$result = $agf->fetch_tache_late();
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		} else {
			$nbre = count($agf->lines);
			$this->info_box_contents[$key][0] = array(
					'td' => 'align="left"',
					'text' => img_object($langs->trans("AgfAlertDay"), "generic").$langs->trans("AgfAdmSuivi").' : '.$langs->trans("AgfAlertDay")
			);
			$this->info_box_contents[$key][1] = array(
					'td' => 'align="left" bgcolor="red"',
					'text' => $nbre,
					'url' => (dol_buildpath('/agefodd/session/list_ope.php', 1) . '?search_alert=alert0&mainmenu=agefodd')
			);
			$key ++;
		}

		$result = $agf->fetch_tache_in_between(0,3);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		} else {
			$nbre = count($agf->lines);
			$this->info_box_contents[$key][0] = array(
					'td' => 'align="left"',
					'text' => img_object($langs->trans("AgfYDaysBeforeAlert"), "generic").$langs->trans("AgfAdmSuivi").' : '.$langs->trans("AgfYDaysBeforeAlert")
			);
			$this->info_box_contents[$key][1] = array(
					'td' => 'align="left" bgcolor="orange"',
					'text' => $nbre,
					'url' => (dol_buildpath('/agefodd/session/list_ope.php', 1) . '?search_alert=alert1&mainmenu=agefodd')
			);
			$key ++;
		}

		$result = $agf->fetch_tache_in_between(3,8);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		} else {
			$nbre = count($agf->lines);
			$this->info_box_contents[$key][0] = array(
					'td' => 'align="left"',
					'text' => img_object($langs->trans("AgfXDaysBeforeAlert"), "generic").$langs->trans("AgfAdmSuivi").' : '.$langs->trans("AgfXDaysBeforeAlert")
			);
			$this->info_box_contents[$key][1] = array(
					'td' => 'align="left" bgcolor="ffe27d"',
					'text' => $nbre,
					'url' => (dol_buildpath('/agefodd/session/list_ope.php', 1) . '?search_alert=alert2&mainmenu=agefodd')
			);
			$key ++;
		}

		$result = $agf->fetch_tache_in_between(8,0);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		} else {
			$nbre = count($agf->lines);
			$this->info_box_contents[$key][0] = array(
					'td' => 'align="left"',
					'text' => img_object($langs->trans("AgfZDaysBeforeAlert"), "generic").$langs->trans("AgfAdmSuivi").' : '.$langs->trans("AgfZDaysBeforeAlert")
			);
			$this->info_box_contents[$key][1] = array(
					'td' => 'align="left" bgcolor="#d5baa8"',
					'text' => $nbre,
					'url' => (dol_buildpath('/agefodd/session/list_ope.php', 1) . '?search_alert=morethanzdays&mainmenu=agefodd')
			);
			$key ++;
		}


		$result = $agf->fetch_tache_en_cours();
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		} else {
			$this->info_box_contents[$key][0] = array(
					'td' => 'align="left"',
					'text' => img_object($langs->trans("AgfAlertLevel3Short"), "generic").$langs->trans("AgfAdmSuivi").' : '.$langs->trans("AgfAlertLevel3Short")
			);
			$this->info_box_contents[$key][1] = array(
					'td' => 'align="left"',
					'text' => $agf->total,
					'url' => (dol_buildpath('/agefodd/session/list_ope.php', 1) . '?search_alert=alert3&mainmenu=agefodd')
			);
			$key ++;
		}
	}

	/**
	 * Method to show box
	 *
	 * @param array $head with properties of box title
	 * @param array $contents with properties of box lines
	 * @param integer $nooutput nooutput
	 * @return void
	 */
	function showBox($head = null, $contents = null, $nooutput = 0) {
		parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
