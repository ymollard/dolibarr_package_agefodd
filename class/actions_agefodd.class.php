<?php
/* Copyright (C) 2013	Florian HENRY 		<florian.henry@open-concept.pro>
 *
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * \file /agefodd/class/actions_agefodd.class.php
 * \ingroup agefodd
 * \brief File of class to manage Session and trainee
 */

/**
 * \class ActionsAnnonce
 * \brief Class to manage Annonce
 */
class ActionsAgefodd {
	var $db;
	var $dao;
	var $error;
	var $errors = array ();
	var $resprints = '';
	
	/**
	 * Constructor
	 *
	 * @param DoliDB $db
	 */
	function __construct($db) {
		$this->db = $db;
		$this->error = 0;
		$this->errors = array ();
	}
	
	/**
	 * printSearchForm Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object	&$object			Object to use hooks on
	 * @param string	&$action			Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	function printSearchForm($parameters, &$object, &$action, $hookmanager) {
		global $langs;
		$langs->load('agefodd@agefodd');
		
		$out = printSearchForm(dol_buildpath('/agefodd/session/list.php', 1), dol_buildpath('/agefodd/session/list.php', 1), img_object('', 'agefodd@agefodd') . ' ' . $langs->trans("AgfSessionId"), 'agefodd', 'search_id');
		$out .= printSearchForm(dol_buildpath('/agefodd/trainee/list.php', 1), dol_buildpath('/agefodd/trainee/list.php', 1), img_object('', 'contact') . ' ' . $langs->trans("AgfMenuActStagiaire"), 'agefodd', 'search_namefirstname');
		
		$this->resprints = $out;
	}
	
	/**
	 * formObjectOptions Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object	&$object			Object to use hooks on
	 * @param string	&$action			Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	function formObjectOptions($parameters, &$object, &$action, $hookmanager) {
		global $langs, $conf, $user;
		
		// dol_syslog(get_class($this).':: formObjectOptions',LOG_DEBUG);
		
		return 0;
	}
	
	/**
	 * DoAction Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object	&$object			Object to use hooks on
	 * @param string	&$action			Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	function doActions($parameters, &$object, &$action, $hookmanager) {
		// global $langs,$conf,$user;
		return 0;
	}
}