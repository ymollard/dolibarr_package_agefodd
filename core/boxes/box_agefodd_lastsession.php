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
class box_agefodd_lastsession extends ModeleBoxes {
	var $boxcode = "agefodd_lastsession";
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

		$this->boxlabel = $langs->transnoentitiesnoconv("AgefoddShort").'-'.$langs->transnoentitiesnoconv("AgfIndex5sess");

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
		include_once DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php";

		$text = $langs->trans("AgfIndex5sess");
		$this->info_box_head = array (
				'text' => $text,
		);
		$key = 0;
		$agf = new Agefodd_index($db);

		$result = $agf->fetch_last_formations(5);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		} else {
			$num = count($agf->lines);
			foreach($agf->lines as $key=>$line) {

				$this->info_box_contents[$key][0] = array(
						'td' => 'align="left"',
						'url' => (dol_buildpath('/agefodd/session/card.php', 1) . '?id='.$line->id.'&mainmenu=agefodd'),
						'text' => $line->id.' # '.$line->ref
				);
				$this->info_box_contents[$key][1] = array(
						'td' => 'align="left"',
						'url' => (dol_buildpath('/agefodd/session/card.php', 1) . '?id='.$line->id.'&mainmenu=agefodd'),
						'text' => dol_trunc($line->intitule, 50)
				);
				if (!empty($line->datef)) {
					$ilya = (num_between_day($line->datef, dol_now(), 0));
					$this->info_box_contents[$key][2] = array(
							'td' => 'align="left"',
							'text' => $langs->trans("AgfThereIsDay", $ilya)
					);
				}
			}
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
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
}
