<?php
/* Copyright (C) 2012-2013  Florian Henry   <florian.henry@open-concept.pro>
 * Copyright (C) 2012       JF FERRY        <jfefe@aternatik.fr>
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 * \file agefodd/class/html.formagefodd.class.php
 * \brief Fichier de la classe des fonctions predefinie de composants html agefodd
 */

/**
 * Class to manage building of HTML components
 */
class FormAgefodd extends Form
{
	public $error;
	public $type_session_def;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	public function __construct($db) {
		global $langs;
		$this->db = $db;
		$this->type_session_def = array(
				0 => $langs->trans('AgfFormTypeSessionIntra'),
				1 => $langs->trans('AgfFormTypeSessionInter')
		);
		return 1;
	}

	/**
	 * Affiche un champs select contenant la liste des formations disponibles.
	 *
	 * @param int $selectid à preselectionner
	 * @param string $htmlname select field
	 * @param string $sort Value to show/edit (not used in this function )
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string select field
	 */
	public function select_formation($selectid, $htmlname = 'formation', $sort = 'intitule', $showempty = 0, $forcecombo = 0, $event = array(), $filters = array()) {
		global $conf, $user, $langs;
		if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->AGF_TRAINING_USE_SEARCH_TO_SELECT)) {
			$placeholder = '';

			if ($selectid) {

				$formationtmpselect = new Formation($this->db);
				$formationtmpselect->fetch($selectid);
				$selected_input_value = $formationtmpselect->intitule;

				unset($formationtmpselect);
			}
			// mode=1 means customers products
			$filter=json_encode($filters);
			$urloption = 'htmlname=' . $htmlname . '&outjson=1&filter=' . $filter;
			print ajax_autocompleter($selectid, $htmlname, dol_buildpath('/agefodd/ajax/formation.php', 1), $urloption, $conf->global->AGF_TRAINING_USE_SEARCH_TO_SELECT, 0, '');

			return img_picto($langs->trans("Search"), 'search', 'style="vertical-align: middle;"').' <input type="text" class="minwidth100" name="search_' . $htmlname . '" id="search_' . $htmlname . '" value="' . $selected_input_value . '" placeholder="'.$langs->trans("Label").'" />';
		} else {
			return $this->select_formation_liste($selectid, $htmlname, $sort, $showempty, $forcecombo, $event, $filters);
		}
	}

	/**
	 * Affiche un champs select contenant la liste des formations disponibles.
	 *
	 * @param int $selectid à preselectionner
	 * @param string $htmlname select field
	 * @param string $sort Value to show/edit (not used in this function )
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string select field
	 */
	public function select_formation_liste($selectid, $htmlname = 'formation', $sort = 'intitule', $showempty = 0, $forcecombo = 0, $event = array(), $filters = array(), $mode = 0) {
		global $conf, $user, $langs;
		$arrayout = array();
		$out = '';

		if ($sort == 'code')
			$order = 'c.ref';
		else
			$order = 'c.intitule';

		$sql = "SELECT c.rowid, c.intitule, c.ref, c.ref_interne";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " WHERE archive = 0";
		$sql .= " AND entity IN (" . getEntity('agefodd') . ")";
		if (count($filters) > 0) {
			foreach ( $filters as $filter )
				$sql .= $filter;
		}
		$sql .= " ORDER BY " . $order;

		dol_syslog(get_class($this) . "::select_formation", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($conf->use_javascript_ajax && ! $forcecombo) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, $event);
			}

			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty) {
				$out .= '<option value="-1"></option>';
			}
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);
					$label = dol_html_entity_decode(stripslashes($obj->intitule), ENT_QUOTES);
					if (! empty($obj->ref_interne)) {
						$label .= ' (' . $obj->ref_interne . ')';
					}
					$arrayout[] = array(
							'key' => $obj->rowid,
							'label' => $label
					);
					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
		} else {
			dol_print_error($this->db);
		}
		$this->db->free($resql);
		if (empty($mode))
			return $out;
		else
			return $arrayout;
	}

	/**
	 * Affiche un champs select contenant la liste des cursus disponibles.
	 *
	 * @param int $selectid à preselectionner
	 * @param string $htmlname select field
	 * @param string $sort Value to show/edit (not used in this function )
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string select field
	 */
	public function select_cursus($selectid, $htmlname = 'cursus', $sort = 'c.ref_interne', $showempty = 0, $forcecombo = 0, $event = array(), $filters = array()) {
		global $conf, $user, $langs;

		$out = '';

		$sql = "SELECT c.rowid, c.intitule, c.ref_interne";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_cursus as c";
		$sql .= " WHERE archive = 0";
		$sql .= " AND entity IN (" . getEntity('agefodd') . ")";
		if (count($filters) > 0) {
			foreach ( $filters as $filter )
				$sql .= $filter;
		}
		$sql .= " ORDER BY " . $sort;

		dol_syslog(get_class($this) . "::select_cursus", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($conf->use_javascript_ajax && $conf->global->AGF_CURSUS_USE_SEARCH_TO_SELECT && ! $forcecombo) {
				$out .= ajax_combobox($htmlname, $event);
			}

			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty) {
				$out .= '<option value=""></option>';
			}
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);
					$label = $obj->intitule;

					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
		} else {
			dol_print_error($this->db);
		}
		$this->db->free($resql);
		return $out;
	}

	/**
	 * Affiche un champs select contenant la liste des action de session disponibles.
	 *
	 * @param int $selectid à preselectionner
	 * @param string $htmlname select field
	 * @param string $excludeid est necessaire d'exclure une valeur de sortie
	 * @return string select field
	 */
	public function select_action_session_adm($selectid = '', $htmlname = 'action_level', $excludeid = '')
	{
		global $conf, $langs;

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.level_rank,";
		$sql .= " t.intitule";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_admlevel as t";
		if ($excludeid != '') {
			$sql .= ' WHERE t.rowid<>\'' . $excludeid . '\'';
		}
		$sql .= " ORDER BY t.indice";

		dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$var = True;
			$num = $this->db->num_rows($result);
			$i = 0;
			$options = '<option value=""></option>' . "\n";

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($result);
				if ($obj->rowid == $selectid)
					$selected = ' selected="true"';
				else
					$selected = '';
				$strRank = str_repeat('-', $obj->level_rank);
				$options .= '<option value="' . $obj->rowid . '"' . $selected . '>';
				$options .= $strRank . ' ' . stripslashes($obj->intitule) . '</option>' . "\n";
				$i ++;
			}
			$this->db->free($result);
			return '<select class="flat" style="width:300px" name="' . $htmlname . '">' . "\n" . $options . "\n" . '</select>' . "\n";
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::".__METHOD__. ' Error' . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Display select list with training action administrative task
	 *
	 * @param int $selectid à preselectionner
	 * @param string $htmlname select field
	 * @param string $excludeid est necessaire d'exclure une valeur de sortie
	 * @param string $fk_training
	 * @return string select field
	 */
	public function select_action_training_adm($selectid = '', $htmlname = 'action_level', $excludeid = '', $fk_training = 0)
	{
		global $conf, $langs;

		$sqlwhere = array();

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.level_rank,";
		$sql .= " t.intitule";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_training_admlevel as t";
		if ($excludeid != '') {
			$sqlwhere[] = ' t.rowid<>\'' . $excludeid . '\'';
		}
		if (! empty($fk_training)) {
			$sqlwhere[] = ' fk_training=' . $fk_training;
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' WHERE ' . implode(' AND ', $sqlwhere);
		}
		$sql .= " ORDER BY t.indice";

		dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$var = true;
			$num = $this->db->num_rows($result);
			$i = 0;
			$options = '<option value=""></option>' . "\n";

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($result);
				if ($obj->rowid == $selectid)
					$selected = ' selected="true"';
				else
					$selected = '';
				$strRank = str_repeat('-', $obj->level_rank);
				$options .= '<option value="' . $obj->rowid . '"' . $selected . '>';
				$options .= $strRank . ' ' . stripslashes($obj->intitule) . '</option>' . "\n";
				$i ++;
			}
			$this->db->free($result);
			return '<select class="flat" style="width:300px" name="' . $htmlname . '">' . "\n" . $options . "\n" . '</select>' . "\n";
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::".__METHOD__. ' ' . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * affiche un champs select contenant la liste des action des session disponibles par session.
	 *
	 * @param int $session_id L'id de la session
	 * @param int $selectid Id de la session selectionner
	 * @param string $htmlname Name of HTML control
	 * @return string The HTML control
	 */
	public function select_action_session($session_id = 0, $selectid = '', $htmlname = 'action_level') {
		global $conf, $langs;

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.level_rank,";
		$sql .= " t.intitule";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as t";
		$sql .= ' WHERE t.fk_agefodd_session=\'' . $session_id . '\'';
		$sql .= " ORDER BY t.indice";

		dol_syslog(get_class($this) . "::select_action_session", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$var = True;
			$num = $this->db->num_rows($result);
			$i = 0;
			$options = '<option value=""></option>' . "\n";

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($result);
				if ($obj->rowid == $selectid)
					$selected = ' selected="true"';
				else
					$selected = '';
				$strRank = str_repeat('-', $obj->level_rank);
				$options .= '<option value="' . $obj->rowid . '"' . $selected . '>';
				$options .= $strRank . ' ' . stripslashes($obj->intitule) . '</option>' . "\n";
				$i ++;
			}
			$this->db->free($result);
			return '<select class="flat" style="width:300px" name="' . $htmlname . '">' . "\n" . $options . "\n" . '</select>' . "\n";
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::select_action_session " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * affiche un champs select contenant la liste des sites de formation déjà référéencés.
	 *
	 * @param int $selectid Id de la session selectionner
	 * @param string $htmlname Name of HTML control
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string The HTML control
	 */
	public function select_site_forma($selectid, $htmlname = 'place', $showempty = 0, $forcecombo = 0, $event = array(), $class = '') {
		global $conf, $langs;
		if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->AGF_SITE_USE_SEARCH_TO_SELECT)) {
			$placeholder = '';

			if ($selectid) {
				dol_include_once('/agefodd/class/agefodd_place.class.php');
				$placetmpselect = new Agefodd_place($this->db);
				$placetmpselect->fetch($selectid);
				$selected_input_value = $placetmpselect->ref_interne;
				unset($placetmpselect);
			}
			// mode=1 means customers products
			$urloption = 'htmlname=' . $htmlname . '&outjson=1&filter=';

			print ajax_autocompleter($selectid, $htmlname, '../ajax/lieu.php', $urloption, $conf->global->AGF_SITE_USE_SEARCH_TO_SELECT, 0, '');

			print img_picto($langs->trans("Search"), 'search', 'style="vertical-align: middle;"').' <input type="text" class="minwidth100" name="search_' . $htmlname . '" id="search_' . $htmlname . '" value="' . $selected_input_value . '" placeholder="'.$langs->trans("Label").'" />';
		} else {
			return $this->select_site_forma_liste($selectid, $htmlname, $showempty, $forcecombo, $event, $class);
		}
	}

	/**
	 * affiche un champs select contenant la liste des sites de formation déjà référéencés.
	 *
	 * @param int $selectid Id de la session selectionner
	 * @param string $htmlname Name of HTML control
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string The HTML control
	 */
	public function select_site_forma_liste($selectid, $htmlname = 'place', $showempty = 0, $forcecombo = 0, $event = array(), $class = '', $mode = 0, $filter = '') {
		global $conf, $langs;
		$arrayout = array();

		$sql = "SELECT p.rowid, p.ref_interne";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_place as p";
		$sql .= " WHERE archive = 0";
		if (! empty($filter))
			$sql .= ' AND ' . $filter;

		$sql .= " AND p.entity IN (" . getEntity('agefodd') . ")";
		$sql .= " ORDER BY p.ref_interne";

		dol_syslog(get_class($this) . "::select_site_forma", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($conf->use_javascript_ajax && ! $forcecombo) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, $event);
			}

			$out .= '<select id="' . $htmlname . '" class="flat ' . $class . '" name="' . $htmlname . '">';
			if ($showempty) {
				$out .= '<option value="-1"></option>';
			}
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($result);
					$label = $obj->ref_interne;
					$arrayout[] = array(
							'key' => $obj->rowid,
							'label' => $label
					);
					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free($result);
			if (empty($mode))
				return $out;
			else
				return $arrayout;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::select_site_forma " . $this->error, LOG_ERR);

			if (empty($mode))
				return - 1;
			else
				return $arrayout;
		}
	}

	/**
	 * affiche un champs select contenant la liste des stagiaires déjà référéencés.
	 *
	 * @param int $selectid Id de la session selectionner
	 * @param string $htmlname Name of HTML control
	 * @param string $filter SQL part for filter
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string The HTML control
	 */
	public function select_stagiaire($selectid = '', $htmlname = 'stagiaire', $filter = '', $showempty = 0, $forcecombo = 0, $event = array()) {
		global $conf, $langs;
		if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->AGF_TRAINEE_USE_SEARCH_TO_SELECT)) {
			$placeholder = '';

			if ($selectid) {
				dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
				$traineetmpselect = new Agefodd_stagiaire($this->db);
				$traineetmpselect->fetch($selectid);
				$selected_input_value = $traineetmpselect->nom . ' ' . $traineetmpselect->prenom;
				unset($traineetmpselect);
			}
			$filter = str_replace('FROM', 'TOREPLACE', $filter);
			$urloption = 'htmlname=' . $htmlname . '&outjson=1&filter=' . $filter;

			print ajax_autocompleter($selectid, $htmlname, '../ajax/stagiaire.php', $urloption, $conf->global->AGF_TRAINEE_USE_SEARCH_TO_SELECT, 0, '');

			print '<input type="text" class="minwidth100" name="search_' . $htmlname . '" id="search_' . $htmlname . '" value="' . $selected_input_value . '" />';
		} else {
			return $this->select_stagiaire_list($selectid, $htmlname, $filter, $showempty, $forcecombo, $event);
		}
	}
	public function select_stagiaire_list($selectid = '', $htmlname = 'stagiaire', $filter = '', $showempty = 0, $forcecombo = 0, $event = array(), $mode = 0) {
		$arrayout = array();
		$sql = "SELECT";
		$sql .= " s.rowid, CONCAT(s.nom,' ',s.prenom) as fullname,";
		$sql .= " so.nom as socname, so.rowid as socid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";

		$sql .= " ON so.rowid = s.fk_soc";

		if (! empty($filter)) {
			$sql .= ' WHERE ' . $filter;
			$sql .= " AND s.entity IN (" . getEntity('agefodd') . ")";
		} else {
			$sql .= " WHERE s.entity IN (" . getEntity('agefodd') . ")";
		}
		$sql .= " ORDER BY fullname";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($conf->use_javascript_ajax && ! $forcecombo) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, $event, 1);
			}

			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty) {
				$out .= '<option value="-1"></option>';
			}
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($result);

					$label = $obj->fullname;
					if ($obj->socname)
						$label .= ' (' . $obj->socname . ')';
					$arrayout[] = array(
							'key' => $obj->rowid,
							'label' => $label
					);
					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free($result);
			if (empty($mode))
				return $out;
			else
				return $arrayout;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::select_stagiaire " . $this->error, LOG_ERR);
			if (empty($mode))
				return - 1;
			else
				return array();
		}
	}

	/**
	 * affiche un champs select contenant la liste des contact déjà référéencés.
	 *
	 * @param int $selectid Id de la session selectionner
	 * @param string $htmlname Name of HTML control
	 * @param string $filter SQL part for filter
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string The HTML control
	 */
	public function select_agefodd_contact($selectid = '', $htmlname = 'contact', $filter = '', $showempty = 0, $forcecombo = 0, $event = array()) {
		global $conf, $langs;

		$sql = "SELECT";
		$sql .= " c.rowid, ";
		$sql .= " s.lastname, s.firstname, s.civility, ";
		$sql .= " soc.nom as socname";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_contact as c";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as s ON c.fk_socpeople = s.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as soc ON soc.rowid = s.fk_soc";
		$sql .= " WHERE c.archive = 0";
		if (! empty($filter)) {
			$sql .= ' AND ' . $filter;
		}
		$sql .= " ORDER BY socname";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($conf->use_javascript_ajax && $conf->global->AGF_CONTACT_USE_SEARCH_TO_SELECT && ! $forcecombo) {
				$out .= ajax_combobox($htmlname, $event);
			}

			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty) {
				$out .= '<option value="-1"></option>';
			}
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($result);
					$label = $obj->firstname . ' ' . $obj->name;
					if ($obj->socname)
						$label .= ' (' . $obj->socname . ')';

					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free($result);
			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::select_agefodd_contact " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Return list of all contacts (for a third party or all)
	 *
	 * @param int $socid ot third party or 0 for all
	 * @param string $selected contact pre-selectionne
	 * @param string $htmlname of HTML field ('none' for a not editable field)
	 * @param int $showempty empty value, 1=add an empty value
	 * @param string $exclude of contacts id to exclude
	 * @param string $limitto that are not id in this array list
	 * @param string $showpublic function into label
	 * @param string $moreclass class to class style
	 * @param string $showsoc company into label
	 * @param int $forcecombo use combo box
	 * @param array $event Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid',
	 *        'params'=>array('add-customer-contact'=>'disabled')))
	 * @param bool $options_only only (for ajax treatment)
	 * @param bool $supplier only
	 * @return int if KO, Nb of contact in list if OK
	 */
	function select_contacts_custom($socid, $selected = '', $htmlname = 'contactid', $showempty = 0, $exclude = '', $limitto = '', $showfunction = 0, $moreclass = '', $showsoc = 0, $forcecombo = 0, $event = array(), $options_only = false, $supplier = 0) {
		print $this->selectcontactscustom($socid, $selected, $htmlname, $showempty, $exclude, $limitto, $showfunction, $moreclass, $options_only, $showsoc, $forcecombo, $event, $supplier);
		return $this->num;
	}

	/**
	 * Return list of all contacts (for a third party or all)
	 *
	 * @param int $socid ot third party or 0 for all
	 * @param string $selected contact pre-selectionne
	 * @param string $htmlname of HTML field ('none' for a not editable field)
	 * @param int $showempty empty value, 1=add an empty value, 2=add line 'Internal' (used by user edit)
	 * @param string $exclude of contacts id to exclude
	 * @param string $limitto contact ti display in max
	 * @param string $showpublic function into label
	 * @param string $moreclass class to class style
	 * @param bool $options_only only (for ajax treatment)
	 * @param string $showsoc company into label
	 * @param int $forcecombo use combo box
	 * @param array $event Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid',
	 *        'params'=>array('add-customer-contact'=>'disabled')))
	 * @param bool $supplier only
	 * @return int if KO, Nb of contact in list if OK
	 */
	public function selectcontactscustom($socid, $selected = '', $htmlname = 'contactid', $showempty = 0, $exclude = '', $limitto = 0, $showfunction = 0, $moreclass = '', $options_only = false, $showsoc = 0, $forcecombo = 0, $event = array(), $supplier = 0) {
		global $conf, $langs, $user;

		$langs->load('companies');

		$out = '';

		// On recherche les societes
		$sql = "SELECT DISTINCT sp.rowid, sp.lastname, sp.statut, sp.firstname, sp.poste";
		if ($showsoc > 0) {
			$sql .= " , s.nom as company";
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . "socpeople as sp";

		if (empty($supplier)) {
			$sql .= " LEFT OUTER JOIN  " . MAIN_DB_PREFIX . "societe as s ON s.rowid=sp.fk_soc ";
		} else {
			$sql .= " INNER JOIN  " . MAIN_DB_PREFIX . "societe as s ON s.rowid=sp.fk_soc and s.fournisseur=1";
		}

		// Limit contact visibility to contact of thirdparty saleman
		if (empty($user->rights->societe->client->voir)) {
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
		}

		$sql .= " WHERE sp.entity IN (" . getEntity('societe', 1) . ")";
		if ($socid > 0) {
			$sql .= " AND (sp.fk_soc IN (SELECT rowid FROM  " . MAIN_DB_PREFIX . "societe WHERE parent IN (SELECT parent FROM " . MAIN_DB_PREFIX . "societe WHERE rowid=" . $socid . '))';
			$sql .= " OR (sp.fk_soc=" . $socid . ")";
			$sql .= " OR (sp.fk_soc IN (SELECT parent FROM " . MAIN_DB_PREFIX . "societe WHERE rowid=" . $socid . "))";
			$sql .= " OR (sp.fk_soc IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "societe WHERE parent=" . $socid . ")))";
		}

		if (! empty($conf->global->CONTACT_HIDE_INACTIVE_IN_COMBOBOX))
			$sql .= " AND sp.statut<>0 ";

		$sql .= " ORDER BY sp.lastname ASC";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			if ($conf->use_javascript_ajax && ! $forcecombo && ! $options_only) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$comboenhancement = ajax_combobox($htmlname, $events, $conf->global->CONTACT_USE_SEARCH_TO_SELECT);
				$out .= $comboenhancement;
				$nodatarole = ($comboenhancement ? ' data-role="none"' : '');
			}

			if ($htmlname != 'none' || $options_only)
				$out .= '<select class="flat' . ($moreclass ? ' ' . $moreclass : '') . '" id="' . $htmlname . '" name="' . $htmlname . '"' . $nodatarole . '>';
			if ($showempty == 1)
				$out .= '<option value="0"' . ($selected == '0' ? ' selected' : '') . '></option>';
			if ($showempty == 2)
				$out .= '<option value="0"' . ($selected == '0' ? ' selected' : '') . '>' . $langs->trans("Internal") . '</option>';

			if ($num) {
				include_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
				$contactstatic = new Contact($this->db);

				while ( $obj = $this->db->fetch_object($resql) ) {

					$contactstatic->id = $obj->rowid;
					$contactstatic->lastname = $obj->lastname;
					$contactstatic->firstname = $obj->firstname;

					if ($htmlname != 'none') {
						$disabled = 0;
						if (is_array($exclude) && count($exclude) && in_array($obj->rowid, $exclude))
							$disabled = 1;
						if (is_array($limitto) && count($limitto) && ! in_array($obj->rowid, $limitto))
							$disabled = 1;
						if ($selected && $selected == $obj->rowid) {
							$out .= '<option value="' . $obj->rowid . '"';
							if ($disabled)
								$out .= ' disabled';
							$out .= ' selected>';
							$out .= $contactstatic->getFullName($langs);
							if ($showfunction && $obj->poste)
								$out .= ' (' . $obj->poste . ')';
							if (($showsoc > 0) && $obj->company)
								$out .= ' - (' . $obj->company . ')';
							$out .= '</option>';
						} else {
							$out .= '<option value="' . $obj->rowid . '"';
							if ($disabled)
								$out .= ' disabled';
							$out .= '>';
							$out .= $contactstatic->getFullName($langs);
							if ($showfunction && $obj->poste)
								$out .= ' (' . $obj->poste . ')';
							if (($showsoc > 0) && $obj->company)
								$out .= ' - (' . $obj->company . ')';
							$out .= '</option>';
						}
					} else {
						if ($selected == $obj->rowid) {
							$out .= $contactstatic->getFullName($langs);
							if ($showfunction && $obj->poste)
								$out .= ' (' . $obj->poste . ')';
							if (($showsoc > 0) && $obj->company)
								$out .= ' - (' . $obj->company . ')';
						}
					}
				}
			} else {
				$out .= '<option value="-1"' . ($showempty == 2 ? '' : ' selected') . ' disabled>' . $langs->trans($socid ? "NoContactDefinedForThirdParty" : "NoContactDefined") . '</option>';
			}
			if ($htmlname != 'none' || $options_only) {
				$out .= '</select>';
			}

			$this->num = $num;
			return $out;
		} else {
			dol_print_error($this->db);
			return - 1;
		}
	}

	/**
	 * affiche un champs select contenant la liste des formateurs déjà référéencés.
	 *
	 * @param int $selectid Id de la session selectionner
	 * @param string $htmlname Name of HTML control
	 * @param string $filter SQL part for filter
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string The HTML control
	 */
	public function select_formateur($selectid = '', $htmlname = 'formateur', $filter = '', $showempty = 0, $forcecombo = 0, $event = array()) {
		global $conf, $langs;
		if (! empty($conf->use_javascript_ajax) && ! empty($conf->global->AGF_TRAINER_USE_SEARCH_TO_SELECT)) {
			$placeholder = '';

			if ($selectid) {
				dol_include_once('/agefodd/class/agefodd_formateur.class.php');
				$traineetmpselect = new Agefodd_teacher($this->db);
				$traineetmpselect->fetch($selectid);
				$selected_input_value = $traineetmpselect->name . ' ' . $traineetmpselect->firstname;
				unset($traineetmpselect);
			}
			// mode=1 means customers products
			$filter = str_replace('FROM', 'TOREPLACE', $filter);
			$urloption = 'htmlname=' . $htmlname . '&outjson=1&filter=' . $filter;

			$return = ajax_autocompleter($selectid, $htmlname, dol_buildpath('/agefodd/ajax/formateur.php', 1), $urloption, $conf->global->AGF_TRAINER_USE_SEARCH_TO_SELECT, 0, '');

			$return.= '<input type="text" class="minwidth100" name="search_' . $htmlname . '" id="search_' . $htmlname . '" value="' . $selected_input_value . '" />';
			return $return;
		} else {
			return $this->select_formateur_liste($selectid, $htmlname, $filter, $showempty, $forcecombo, $event);
		}
	}

	/**
	 * affiche un champs select contenant la liste des formateurs déjà référéencés.
	 *
	 * @param int $selectid Id de la session selectionner
	 * @param string $htmlname Name of HTML control
	 * @param string $filter SQL part for filter
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string The HTML control
	 */
	public function select_formateur_liste($selectid = '', $htmlname = 'formateur', $filter = '', $showempty = 0, $forcecombo = 0, $event = array(), $mode = 0) {
		global $conf, $langs;

		$arrayout = array();

		$sql = "SELECT";
		$sql .= " s.rowid, s.fk_socpeople, s.fk_user,";
		$sql .= " s.rowid, CONCAT(sp.lastname,' ',sp.firstname) as fullname_contact,";
		$sql .= " CONCAT(u.lastname,' ',u.firstname) as fullname_user";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formateur as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sp";
		$sql .= " ON sp.rowid = s.fk_socpeople";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u";
		$sql .= " ON u.rowid = s.fk_user";
		$sql .= " WHERE s.archive = 0";
		$sql .= " AND s.entity IN (" . getEntity('agefodd') . ")";
		if (! empty($filter)) {
			$sql .= ' AND ' . $filter;
		}
		$sql .= " ORDER BY sp.lastname,u.lastname";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {

			if ($conf->use_javascript_ajax && ! $forcecombo) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($htmlname, $event);
			}

			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty) {
				$out .= '<option value="-1"></option>';
			}
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($result);
					if (! empty($obj->fk_socpeople)) {
						$label = $obj->fullname_contact;
					}
					if (! empty($obj->fk_user)) {
						$label = $obj->fullname_user;
					}
					$arrayout[] = array(
							'key' => $obj->rowid,
							'label' => $label
					);
					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free($result);
			if (empty($mode))
				return $out;
			else
				return $arrayout;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::select_formateur " . $this->error, LOG_ERR);
			if (empty($mode))
				return - 1;
			else
				return array();
		}
	}

	/**
	 * affiche un champs select contenant la liste des financements possible pour un stagiaire
	 *
	 * @param int $selectid Id de la session selectionner
	 * @param string $htmlname Name of HTML control
	 * @param string $filter SQL part for filter
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string The HTML control
	 */
	public function select_type_stagiaire($selectid, $htmlname = 'stagiaire_type', $filter = '', $showempty = 0, $forcecombo = 0, $event = array()) {
		global $conf, $langs;

		$sql = "SELECT t.rowid, t.intitule";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire_type as t";
		if (! empty($filter)) {
			$sql .= ' WHERE ' . $filter;
		}
		$sql .= " ORDER BY t.sort";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {

			if ($conf->use_javascript_ajax && $conf->global->AGF_STAGTYPE_USE_SEARCH_TO_SELECT && ! $forcecombo) {
				$out .= ajax_combobox($htmlname, $event);
			}

			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty) {
				$out .= '<option value="-1"></option>';
			}
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($result);
					$label = stripslashes($obj->intitule);

					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free($result);
			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::select_type_stagiaire " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * affiche un champs select contenant la liste des type de formateur
	 *
	 * @param int $selectid Id de la session selectionner
	 * @param string $htmlname Name of HTML control
	 * @param string $filter SQL part for filter
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string The HTML control
	 */
	public function select_type_formateur($selectid, $htmlname = 'trainertype', $filter = '', $showempty = 0, $forcecombo = 0, $event = array()) {
		global $conf, $langs;

		$sql = "SELECT t.rowid, t.intitule";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formateur_type as t";
		if (! empty($filter)) {
			$sql .= ' WHERE ' . $filter;
		}
		$sql .= " ORDER BY t.sort";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {

			if ($conf->use_javascript_ajax && $conf->global->AGF_STAGTYPE_USE_SEARCH_TO_SELECT && ! $forcecombo) {
				$out .= ajax_combobox($htmlname, $event);
			}

			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty) {
				$out .= '<option value="-1"></option>';
			}
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($result);
					$label = stripslashes($obj->intitule);

					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free($result);
			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . " " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Display select of session status from dictionnary
	 *
	 * @param int $selectid Id
	 * @param string $htmlname Name of HTML control
	 * @param string $filter SQL part for filter
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @param string $returntype typereturn
	 * @return string The HTML control
	 */
	public function select_session_status($selectid, $htmlname = 'session_status', $filter = '', $showempty = 0, $forcecombo = 0, $event = array(), $returntype = '', $multiselect = false) {
		global $conf, $langs;

		$TSelectid = array();
		if (is_array($selectid)) {
			$TSelectid = $selectid;
		} else {
			$TSelectid[] = $selectid;
		}

		$sql = "SELECT t.rowid, t.code ,t.intitule ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_status_type as t";
		if (! empty($filter)) {
			$sql .= ' WHERE ' . $filter;
		}
		$sql .= " ORDER BY t.sort";

		$out = '';

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {

			if (empty($returntype)) {
				$out .= '<select id="' . $htmlname . '" class="flat minwidth100" name="' . $htmlname;
				if ($multiselect) {
					$out .= '[]" ';
					$out .= 'multiple="multiple"';
				}
				$out .= '">';
			} else {
				$out .= 'select';
			}

			if ($showempty) {
				if (empty($returntype)) {
					$out .= '<option value=""></option>';
				} else {
				}
			}
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($result);
					if ($obj->intitule == $langs->trans('AgfStatusSession_' . $obj->code)) {
						$label = stripslashes($obj->intitule);
					} else {
						$label = $langs->trans('AgfStatusSession_' . $obj->code);
					}

					if (! empty($TSelectid) && in_array($obj->rowid, $TSelectid)) {
						if (empty($returntype)) {
							$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
						}
					} else {
						if (empty($returntype)) {
							$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
						}
					}
					$i ++;
				}
			}
			if (empty($returntype)) {
				$out .= '</select>';
			}
			$this->db->free($result);

			if (! empty($conf->global->MAIN_USE_JQUERY_MULTISELECT)) {
				$out .= '<script type="text/javascript">';
				$out .= '$(document).ready(function () {';
				$out .= '	$("select[multiple]").select2();';
				$out .= '});';
				$out .= '</script>';
			}

			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . ' ' . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Display select of session status from dictionnary
	 *
	 * @param int $selectid Id
	 * @param string $htmlname Name of HTML control
	 * @return string The HTML control
	 */
	public function select_type_affect($selectid, $htmlname = 'search_type_affect') {
		global $conf, $langs;

		$select_array = array(
				'thirdparty' => $langs->trans('ThirdParty'),
				'trainee' => $langs->trans('AgfParticipant'),
				'requester' => $langs->trans('AgfTypeRequester'),
				'trainee_requester' => $langs->trans('AgfTypeTraineeRequester'),
				'employer' => $langs->trans('AgfTypeEmployee'),
				'presta' => $langs->trans('AgfTypePresta')
		);

		if ($conf->global->AGF_ADVANCE_COST_MANAGEMENT) {
			$select_array['trainer'] = $langs->trans('AgfFormateur');
		}

		if ($conf->global->AGF_MANAGE_OPCA) {
			$select_array['opca'] = $langs->trans('AgfMailTypeContactOPCA');
		}

		return $this->selectarray($htmlname, $select_array, $selectid, 0);
	}

	/**
	 * Display list of training category
	 *
	 * @param int $selectid Id de la session selectionner
	 * @param string $htmlname Name of HTML control
	 * @param string $filter SQL part for filter
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string The HTML control
	 */
	public function select_training_categ($selectid, $htmlname = 'stagiaire_type', $filter = '', $showempty = 1) {
		global $conf, $langs;

		$sql = "SELECT t.rowid, t.code, t.intitule";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_type as t";
		if (! empty($filter)) {
			$sql .= ' WHERE ' . $filter;
		}
		$sql .= " ORDER BY t.sort";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {

			if ($conf->use_javascript_ajax) {
				$out .= ajax_combobox($htmlname, array(), 0, 0, '200px');
			}

			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty) {
				$out .= '<option value="-1"></option>';
			}
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($result);
					$label = stripslashes($obj->code . ' - ' . $obj->intitule);

					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free($result);
			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Display list of training category
	 *
	 * @param int $selectid Id de la session selectionner
	 * @param string $htmlname Name of HTML control
	 * @param string $filter SQL part for filter
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string The HTML control
	 */
	public function select_training_categ_bpf($selectid, $htmlname = 'stagiaire_type', $filter = '', $showempty = 1) {
		global $conf, $langs;

		$sql = "SELECT t.rowid, t.code, t.intitule";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_type_bpf as t";
		if (! empty($filter)) {
			$sql .= ' WHERE ' . $filter;
		}
		$sql .= " ORDER BY t.sort";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {

			if ($conf->use_javascript_ajax) {
				$out .= ajax_combobox($htmlname, array(), 0, 0, '200px');
			}

			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty) {
				$out .= '<option value="-1"></option>';
			}
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object($result);
					$label = stripslashes($obj->code . ' - ' . $obj->intitule);

					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free($result);
			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Formate une jauge permettant d'afficher le niveau l'état du traitement des tâches administratives
	 *
	 * @param int $actual_level valeur de l'état actuel
	 * @param int $total_level valeur de l'état quand toutes les tâches sont remplies
	 * @param string $title légende précédent la jauge
	 * @return string The HTML control
	 */
	public function level_graph($actual_level, $total_level, $title) {
		$str = '<table style="border:0px; margin:0px; padding:0px">' . "\n";
		$str .= '<tr style="border:0px;"><td style="border:0px; margin:0px; padding:0px">' . $title . ' : </td>' . "\n";
		for($i = 0; $i < $total_level; $i ++) {
			if ($i < $actual_level)
				$color = 'green';
			else
				$color = '#d5baa8';
			$str .= '<td style="border:0px; margin:0px; padding:0px" width="10px" bgcolor="' . $color . '">&nbsp;</td>' . "\n";
		}
		$str .= '</tr>' . "\n";
		$str .= '</table>' . "\n";

		return $str;
	}

	/**
	 * Affiche un champs select contenant la liste des 1/4 d"heures de 7:00 à 21h00.
	 *
	 * @param string $selectval valeur a selectionner par defaut
	 * @param string $htmlname nom du control HTML
	 * @return string The HTML control
	 */
	public function select_time($selectval = '', $htmlname = 'period', $enabled = 1, $with_empty = true, $more_class = '') {
		global $conf;
		$time = (isset($conf->global->AGF_MIN_HOUR_SCHEDULE) && $conf->global->AGF_MIN_HOUR_SCHEDULE >= 0) ? $conf->global->AGF_MIN_HOUR_SCHEDULE : 5;
		$heuref = 23;
		$min = 0;
		if ($with_empty) {
			$options = '<option value=""></option>' . "\n";
		}
		while ( $time < $heuref ) {
			if ($min == 60) {
				$min = 0;
				$time ++;
			}

			$ftime = sprintf("%02d", $time) . ':' . sprintf("%02d", $min);

			if ($selectval == $ftime)
				$selected = ' selected="selected"';
			else
				$selected = '';
			$options .= '<option value="' . $ftime . '"' . $selected . '>' . $ftime . '</option>' . "\n";
			$min += 15;
		}
		if (empty($enabled)) {
			$disabled = ' disabled="disabeld" ';
		} else {
			$disabled = '';
		}

		return '<select class="flat ' . $more_class . '" ' . $disabled . ' name="' . $htmlname . '">' . "\n" . $options . "\n" . '</select>' . "\n";
	}

	/**
	 * Affiche un champs select contenant la liste des 1/4 d"heures de 7:00 à 21h00.
	 *
	 * @param string $selectval valeur a selectionner par defaut
	 * @param string $htmlname nom du control HTML
	 * @return string The HTML control
	 */
	public function select_duration_agf($selectval = '', $htmlname = 'duration') {
		global $langs;

		$duration_array = array();

		if (! empty($selectval)) {
			$duration_array = explode(':', $selectval);
			$year = $duration_array[0];
			$month = $duration_array[1];
			$day = $duration_array[2];
		} else {
			$year = $month = $day = 0;
		}

		$out = '<input name="' . $htmlname . '_year" class="flat" size="4" value="' . $year . '">' . $langs->trans('Year');
		$out .= '<select class="flat" name="' . $htmlname . '_month">';
		for($i = 0; $i <= 12; $i ++) {
			if ($i == $month) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$out .= '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
		}
		$out .= '</select>' . $langs->trans('Month') . "\n";

		$out .= '<select class="flat" name="' . $htmlname . '_day">';
		for($i = 0; $i <= 31; $i ++) {
			if ($i == $day) {
				$selected = ' selected="selected"';
			} else {
				$selected = '';
			}
			$out .= '<option value="' . $i . '"' . $selected . '>' . $i . '</option>';
		}
		$out .= '</select>' . $langs->trans('Day') . "\n";

		return $out;
	}

	/**
	 * Affiche une liste de sélection des types de formation
	 *
	 * @param string $htmlname control HTML
	 * @param int $selectval selectionner par defaut
	 * @param int $showempty Show Empty
	 * @return string HTML control
	 */
	public function select_type_session($htmlname, $selectval, $showempty = 0) {
		return $this->selectarray($htmlname, $this->type_session_def, $selectval, $showempty);
	}

	/**
	 * Show list of actions for element
	 *
	 * @param Object $object
	 * @param string $typeelement
	 * @param int $socid user
	 * @param int $with_calendar with_calendar
	 * @return int if KO, >=0 if OK
	 */
	public function showactions($object, $typeelement = 'agefodd_agsession', $socid = 0, $with_calendar='withcalendar') {
		global $langs, $conf, $user;
		global $bc;

		require_once DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php";
		require_once DOL_DOCUMENT_ROOT . "/societe/class/societe.class.php";
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($this->db);
		$form = new Form($this->db);

		$actioncode = GETPOST('actioncode', 'array');

		$action_arr = ActionComm::getActions($this->db, $socid, $object->id, $typeelement);

		if ($with_calendar=='nocalendar') {
			foreach ( $action_arr as $key=>$action ) {
				if ($action->type_code=='AC_AGF_SESS' || $action->type_code=='AC_AGF_SESST') {
					unset($action_arr[$key]);
				}
			}
			$action_arr=array_values($action_arr);
		}

		// On récupère les événements liés aux factures elles mêmes liées à la session de formation
		$agf = new Agefodd_session_element($this->db);
		$result = $agf->fetch_by_session_by_thirdparty($object->id, $socid, 'invoice');
		if (! empty($agf->lines)) {
			foreach ( $agf->lines as $line ) {
				if ($line->element_type == 'invoice' && ! empty($line->facnumber)) {
					$action_arr_fac = ActionComm::getActions($this->db, $socid, $line->fk_element, 'invoice');
					if (! empty($action_arr_fac))
						$action_arr = array_merge($action_arr, $action_arr_fac);
				}
			}
		}

		// On récupère les événements liés aux propales elles mêmes liées à la session de formation
		$result = $agf->fetch_by_session_by_thirdparty($object->id, $socid, 'propal');
		if (! empty($agf->lines)) {
			foreach ( $agf->lines as $line ) {
				if ($line->element_type == 'propal' && ! empty($line->propalref)) {
					$action_arr_prop = ActionComm::getActions($this->db, $socid, $line->fk_element, 'propal');
					if (! empty($action_arr_prop))
						$action_arr = array_merge($action_arr, $action_arr_prop);
				}
			}
		}

		if (is_array($actioncode) && count($actioncode) > 0) {
			foreach ( $action_arr as $key => $action ) {

				if (! in_array($action->type_code, $actioncode)) {
					unset($action_arr[$key]);
				}
			}
			$action_arr = array_values($action_arr);
		}

		$num = count($action_arr);

		if ($typeelement == 'agefodd_agsession') {
			$title = $langs->trans('AgfActionsOnTraining');
		} else {
			$title = $langs->trans("Actions");
		}

		print '<div class="div-table-responsive">';
		print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="searchFormList" id="searchFormList">' . "\n";
		print '<input type="hidden" name="action" value="view_actioncomm"/>';
		print '<input type="hidden" name="id" value="' . $object->id . '"/>';
		print '<input type="hidden" name="id" value="'.$object->id.'"/>';
		print '<input type="hidden" name="with_calendar" value="'.$with_calendar.'"/>';

		print_barre_liste($title, 0, $_SERVER['PHP_SELF'], '', '', '', "", $num, $num, 'title_generic.png', 0, '', '', -1,1,1);
		if ($with_calendar=='withcalendar') {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?id='.$object->id.'&with_calendar=nocalendar">'.$langs->trans('AgfHideCalendarEventsType').'</a>';
		} else {
			print '<a href="' . $_SERVER['PHP_SELF'] . '?id='.$object->id.'&with_calendar=withcalendar">'.$langs->trans('AgfDisplayCalendarEventsType').'</a>';
		}

		$total = 0;
		$var = true;
		print '<table class="noborder tagtable liste listwithfilterbefore" width="100%">';
		print '<tr class="liste_titre_filter">';
		print '<td class="liste_titre"></td>';
		print '<td class="liste_titre"></td>';
		print '<td class="liste_titre">';
		print $formactions->select_type_actions($actioncode, "actioncode", array(), (empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : - 1), 0, 1);
		print '</td>';
		print '<td class="liste_titre"></td>';
		print '<td class="liste_titre"></td>';
		// Search lens
		print '<td class="liste_titre" align="right">';
		if (method_exists($form, 'showFilterButtons')) {
			$searchpicto = $form->showFilterButtons();

			print $searchpicto;
		} else {
			print '<input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
			print '&nbsp; ';
			print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
		}
		print '</td>';
		print '</tr>';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Ref"), $_SERVER['PHP_SELF'], "", '', $option, '', '', '');
		print_liste_field_titre($langs->trans("Date"), $_SERVER['PHP_SELF'], "", '', $option, '', '', '');
		print_liste_field_titre($langs->trans("Type"), $_SERVER['PHP_SELF'], "", '', $option, '', '', '');
		print_liste_field_titre($langs->trans("Action"), $_SERVER['PHP_SELF'], "", '', $option, '', '', '');
		print_liste_field_titre($langs->trans("ThirdParty"), $_SERVER['PHP_SELF'], "", '', $option, '', '', '');
		print_liste_field_titre($langs->trans("By"), $_SERVER['PHP_SELF'], "", '', $option, '', '', '');
		print '</tr>';
		print "\n";

		foreach ( $action_arr as $action ) {
			print '<tr class="oddeven">';
			print '<td>' . $action->getNomUrl(1) . '</td>';
			print '<td>' . dol_print_date($action->datep, 'dayhour') . '</td>';
			print '<td>' . $action->type . '</td>';
			print '<td title="' . dol_escape_htmltag($action->label) . '">' . dol_trunc($action->label, 50) . '</td>';
			$userstatic = new User($this->db);
			$userstatic->id = $action->author->id;
			$userstatic->firstname = $action->author->firstname;
			$userstatic->lastname = $action->author->lastname;

			$socurl = '';
			$socstatic = new Societe($this->db);
			if (! empty($action->socid)) {
				$socstatic->fetch($action->socid);
				$socurl = $socstatic->getNomUrl(1);
			}

			print '<td>' . $socurl . '</td>';
			print '<td>' . $userstatic->getNomUrl(1) . '</td>';
			print '</tr>';
		}
		print '</table>';
		print '</form>';
		print '</div>';

		return $num;
	}

	/**
	 * Display select Trainee status in session
	 *
     * @param   string          $selectval          valeur a selectionner par defaut
     * @param   string          $htmlname           nom du control HTML
     * @param   Agsession       $session            Object de la session en court
     * @param	int|string		$show_empty			0 no empty value allowed, 1 or string to add an empty value into list (key is -1 and value is '' or '&nbsp;' if 1, key is -1 and value is text if string), <0 to add an empty value with key that is this value.
     * @return  string          The HTML control
	 */
	public function select_stagiaire_session_status($htmlname, $selectval, $session = null, $show_empty = 0)
	{
		global $conf,$langs;

		require_once 'agefodd_session_stagiaire.class.php';
		$sess_sta = new Agefodd_session_stagiaire($this->db);

		if (! empty($conf->global->AGF_USE_REAL_HOURS) && isset($session)) {
			require_once 'agefodd_session_calendrier.class.php';
			$cal = new Agefodd_sesscalendar($this->db);
			$res = $cal->fetch_all($session->id);
			if ($res < 0) {
				setEventMessage($cal->error, 'errors');
			} else {
				$optionStatus=array();
				if (is_array($cal->lines) && count($cal->lines)>0) {
					$dateToTest = $cal->lines[0]->heured;
				} else {
					$dateToTest = $session->dated;
				}
				foreach ($sess_sta->labelstatut as $statuskey => $statuslabel) {
					if($dateToTest >= dol_now() && in_array($statuskey, $sess_sta->statusAvalaibleForFuture)) {
						$optionStatus[$statuskey]=$statuslabel;
					} elseif ($dateToTest <= dol_now() && in_array($statuskey, $sess_sta->statusAvalaibleForPast)) {
						$optionStatus[$statuskey]=$statuslabel;
					}
				}
				return $this->selectarray($htmlname, $optionStatus, $selectval, $show_empty).img_warning($langs->trans('AgfWarnStatusLimited'));
			}
		} else {
			return $this->selectarray($htmlname, $sess_sta->labelstatut, $selectval, $show_empty);
		}
	}

	/**
	 * Display select Trainer status in session
	 *
	 * @param string $selectval valeur a selectionner par defaut
	 * @param string $htmlname nom du control HTML
	 * @return string The HTML control
	 */
	public function select_trainer_session_status($htmlname, $selectval, $filter = array(), $showempty = 0) {
		require_once 'agefodd_session_formateur.class.php';
		$sess_trainer = new Agefodd_session_formateur($this->db);

		$array_status = array();
		if (count($filter) > 0) {
			$array_status = array_intersect_key($sess_trainer->labelstatut, $filter);
		} else {
			$array_status = $sess_trainer->labelstatut;
		}

		return $this->selectarray($htmlname, $array_status, $selectval, $showempty);
	}

	/**
	 * Show filter form in agenda view
	 *
	 * @param Object $form
	 * @param int $year
	 * @param int $month
	 * @param int $day
	 * @param string $filter_commercial Salesman
	 * @param string $filter_customer Customer
	 * @param string $filter_contact Contact
	 * @param int $filter_trainer Trainer
	 * @param int $canedit edit filter
	 * @param bool $set_select_thirdparty_multiple transforme le select thirdparty en multiselect
	 * @param string $filter_customer Customer
	 * @return void
	 */
	public function agenda_filter($form, $year, $month, $day, $filter_commercial, $filter_customer, $filter_contact, $filter_trainer, $canedit = 1, $filterdatestart = '', $filterdatesend = '', $onlysession = 0, $filter_type_session = '', $display_only_trainer_filter = 0, $filter_location = '', $action = '', $filter_session_status = '', $filter_trainee = 0, $filter_control_occupation = false, $set_select_thirdparty_multiple = false, $filter_customer_place = array()) {
		global $conf, $langs;

		print '<form name="listactionsfilter" class="listactionsfilter" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="status" value="' . $status . '">';
		print '<input type="hidden" name="year" value="' . $year . '">';
		print '<input type="hidden" name="month" value="' . $month . '">';
		print '<input type="hidden" name="day" value="' . $day . '">';
		print '<input type="hidden" name="action" value="' . $action . '">';
		print '<input type="hidden" name="onlysession" value="' . $onlysession . '">';
		print '<input type="hidden" name="displayonlytrainerfilter" value="' . $display_only_trainer_filter . '">';
		print '<table class="nobordernopadding" width="100%">';

		print '<tr><td class="nowrap" width="10%">';

		if (! empty($conf->browser->phone))
			print '<div class="fichehalfleft">';
		else
			print '<table class="nobordernopadding" width="100%"><tr><td class="nowrap">';

		print '<table class="nobordernopadding">';

		if ($canedit) {

			if (empty($display_only_trainer_filter)) {
				print '<tr>';
				print '<td class="nowrap">';
				print $langs->trans("AgfSessionCommercial") . ' &nbsp; ';
				print '</td><td class="nowrap maxwidthonsmartphone">';
				if (empty($filter_commercial)) {
					$filter_commercial = 'a';
				}
				$form->select_users($filter_commercial, 'commercial', 1, array(
						1
				));
				print '</td>';
				print '</tr>';

				print '<tr>';
				print '<td class="nowrap">';
				print $langs->trans("or") . ' ' . $langs->trans("Customer");
				print ' &nbsp;</td><td class="nowrap maxwidthonsmartphone">';

				$moreparam = '';
				if ($set_select_thirdparty_multiple && ( float ) DOL_VERSION <= 8.0) {
					$moreparam = ' name="fk_soc[]" multiple >' . ";//"; // OMG j'ose vraiment le faire ? .. oui je l'ai fait :'( [@see => https://github.com/Dolibarr/dolibarr/pull/9028]
				}

				if ($conf->global->AGF_CONTACT_DOL_SESSION) {
					$events = array();
					$events[] = array(
							'method' => 'getContacts',
							'url' => dol_buildpath('/core/ajax/contacts.php', 1),
							'htmlname' => 'contact',
							'params' => array(
									'add-customer-contact' => 'disabled'
							)
					);

					$html_select_thirdparty_list = $form->select_thirdparty_list($filter_customer, 'fk_soc', '', 'SelectThirdParty', 1, 0, $events, '', 0, 0, 'minwidth100', $moreparam, $set_select_thirdparty_multiple);
				} else {
					$html_select_thirdparty_list = $form->select_thirdparty_list($filter_customer, 'fk_soc', '', 'SelectThirdParty', 1, 0, array(), '', 0, 0, 'minwidth100', $moreparam, $set_select_thirdparty_multiple);
				}

				// MOUAHAHAHAH, comme je peux pas hack le 1er param de la méthode select_thirdparty_list() pour réécrire le test, je fist le contenu html qui est retourné par cette même méthode (je suis diabolique, oui je le sais...)
				if ($set_select_thirdparty_multiple && ( float ) DOL_VERSION <= 8.0) {
					foreach ( $filter_customer as $fk_soc ) {
						$html_select_thirdparty_list = preg_replace('/<option value="' . $fk_soc . '">/', '<option value="' . $fk_soc . '" selected>', $html_select_thirdparty_list);
					}
				}

				print $html_select_thirdparty_list;

				print '</td></tr>';
				print '<tr>';
				print '<td class="nowrap maxwidthonsmartphone">';
				print $langs->trans("or") . ' ' . $langs->trans("AgfSessionContact");
				print ' &nbsp;</td><td class="nowrap maxwidthonsmartphone">';
				if ($conf->global->AGF_CONTACT_DOL_SESSION) {
					if (! empty($filter_customer) && ! is_array($filter_customer)) {
						$form->select_contacts($filter_customer, $filter_contact, 'contact', 1, '', '', 1, '', 1);
					} else {
						$form->select_contacts(0, $filter_contact, 'contact', 1, '', '', 1, '', 1);
					}
				} else {
					print $this->select_agefodd_contact($filter_contact, 'contact', '', 1);
				}
				print '</td></tr>';
			}

			print '<tr>';
			print '<td class="nowrap">';
			print $langs->trans("or") . ' ' . $langs->trans("AgfLieu") . ' ' . $langs->trans("Customer");
			print ' &nbsp;</td><td class="nowrap maxwidthonsmartphone">';

			$moreparam = '';
			if ($set_select_thirdparty_multiple && ( float ) DOL_VERSION <= 8.0) {
				$moreparam = ' name="fk_soc_place[]" multiple >' . ";//"; // OMG j'ose vraiment le faire ? .. oui je l'ai fait :'( [@see => https://github.com/Dolibarr/dolibarr/pull/9028]
			}
			$html_select_thirdparty_place_list = $form->select_thirdparty_list($filter_customer_place, 'fk_soc_place', '', 'SelectThirdParty', 1, 0, array(), '', 0, 0, 'minwidth100', $moreparam, $set_select_thirdparty_multiple);

			if ($set_select_thirdparty_multiple && ( float ) DOL_VERSION <= 8.0) {
				foreach ( $filter_customer_place as $fk_soc ) {
					$html_select_thirdparty_place_list = preg_replace('/<option value="' . $fk_soc . '">/', '<option value="' . $fk_soc . '" selected>', $html_select_thirdparty_place_list);
				}
			}

			print $html_select_thirdparty_place_list;
			print '</td></tr>';

			print '<tr>';
			print '<td class="nowrap">';
			print $langs->trans("or") . ' ' . $langs->trans("AgfFormateur");
			print ' &nbsp;</td><td class="nowrap maxwidthonsmartphone">';
			print $this->select_formateur($filter_trainer, "trainerid", '', 1);
			print '</td></tr>';

			print '<tr>';
			print '<td class="nowrap">';
			print $langs->trans("or") . ' ' . $langs->trans("AgfParticipant");
			print ' &nbsp;</td><td class="nowrap maxwidthonsmartphone">';
			print $this->select_stagiaire($filter_trainee, "traineeid", '', 1);
			print '</td></tr>';

			print '<tr>';
			print '<td class="nowrap">';
			print $langs->trans("or") . ' ' . $langs->trans("AgfLieu");
			print ' &nbsp;</td><td class="nowrap maxwidthonsmartphone">';
			print $this->select_site_forma($filter_location, 'location', 1);
			print '</td></tr>';

			// Filter by periode only on list view
			if (strpos($_SERVER["PHP_SELF"], 'listactions.php') !== false) {

				print '<tr>';
				print '<td class="nowrap">' . $langs->trans("DateStart") . '</td>';
				print '<td>';
				$form->select_date($filterdatestart, 'dt_start_filter', 0, 0, 1);
				print '</td>';
				print "</tr>\n";

				print '<td class="nowrap">' . $langs->trans("DateEnd") . '</td>';
				print '<td>';
				$form->select_date($filterdatesend, 'dt_end_filter', 0, 0, 1);
				print '</td>';
				print "</tr>\n";
			}

			if (strpos($_SERVER["PHP_SELF"], 'pertrainer.php') !== false || strpos($_SERVER["PHP_SELF"], 'perlocation.php') !== false) {
				print '<tr>';
				print '<td class="nowrap">' . $langs->trans("DateStart") . '</td>';
				print '<td>';
				$form->select_date($filterdatestart, 'dt_start_filter', 0, 0, 1);
				print '</td>';
				print "</tr>\n";
			}

			print '<tr>';
			print '<td class="nowrap maxwidthonsmartphone">';
			print $langs->trans("AgfOnlySession");
			print ' &nbsp;</td><td class="nowrap maxwidthonsmartphone">';
			if (! empty($onlysession)) {
				$checkedyes = ' checked="checked" ';
				$checkedno = ' ';
			} else {
				$checkedyes = '  ';
				$checkedno = ' checked="checked" ';
			}
			print '<input type="radio" name="onlysession" ' . $checkedyes . ' value="1">' . $langs->trans('Yes');
			print '<input type="radio" name="onlysession" ' . $checkedno . ' value="0">' . $langs->trans('No');
			print '</td></tr>';

			print '<tr>';
			print '<td class="nowrap">';
			print $langs->trans("AgfFormTypeSession");
			print ' &nbsp;</td><td class="nowrap maxwidthonsmartphone">';
			print $this->select_type_session('type_session', $filter_type_session, 1);
			print '</td></tr>';

			print '<tr>';
			print '<td class="nowrap">';
			print $langs->trans("AgfStatusSession");
			print ' &nbsp;</td><td class="nowrap maxwidthonsmartphone">';

			// Find all posible status
			$sql_status = "SELECT t.rowid, t.code ,t.intitule ";
			$sql_status .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_status_type as t";
			$sql_status .= ' WHERE active=1';
			$sql_status .= " ORDER BY t.sort";
			dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
			$data_status = array();
			$result_status = $this->db->query($sql_status);
			if ($result_status) {
				while ( $obj_status = $this->db->fetch_object($result_status) ) {
					$data_status[$obj_status->rowid] = $obj_status->intitule;
				}
			}
			print $form->multiselectarray('search_session_status', $data_status, $filter_session_status, '', 0, '', 0, '100%');
			print '</td></tr>';

			if ($filter_control_occupation !== false) {
				print '<tr>';
				print '<td class="nowrap">' . $langs->trans("AgfOccupationControlSearch") . '</td>';
				print '<td>' . $form->selectarray('control_occupation', array(
						- 1 => $langs->trans('All'),
						0 => $langs->trans('ControlOccupationUncheck'),
						1 => $langs->trans('ControlOccupationCheck')
				), $filter_control_occupation) . '</td>';
				print '</tr>';
			}
		}

		print '</table>';

		if (! empty($conf->browser->phone))
			print '</div>';
		else
			print '</td>';

		if (! empty($conf->browser->phone))
			print '<div class="fichehalfright">';
		else
			print '<td align="center" valign="middle" class="nowrap">';
		if ($canedit) {
			print '<table><tr><td align="center">';
			print '<div class="formleftzone">';
			print '<input type="submit" class="button" style="min-width:120px" name="refresh" value="' . $langs->trans("Refresh") . '">';
			print '</div>';
			print '</td></tr>';
			print '</table>';
		}
		if (! empty($conf->browser->phone))
			print '</div>';
		else
			print '</td></tr></table>';

		// print '</div>'; // Close fichecenter
		print '<div style="clear:both"></div>';

		print '</form>';
	}

	/**
	 * Return select of available convention document model
	 *
	 * @param string $model item
	 * @return string HTML output
	 */
	public function select_conv_model($model = '', $htmlname = 'model_doc') {
		global $conf;

		$outselect = '<select class="flat" id="' . $htmlname . '" name="' . $htmlname . '">';

		$TDir[] = dol_buildpath("/agefodd/core/modules/agefodd/pdf/");
		$TDir[] = dol_buildpath('/agefodd/core/modules/agefodd/pdf/override/');

		foreach ( $TDir as $dir ) {
			if (is_dir($dir)) {
				$handle = opendir($dir);
				if (is_resource($handle)) {
					$var = true;

					// TODO PH faire la lecture du sous dossier "override"
					while ( ($file = readdir($handle)) !== false ) {
						if ($file === 'override')
							continue;
						if (preg_match('/^(pdf_convention.*)\.modules.php$/i', $file, $reg)) {
							$file = $reg[1];

							require_once ($dir . $file . ".modules.php");

							$module = new $file($this->db);

							$selected = '';
							if ($file == $model) {
								$selected = 'selected="selected"';
							}
							$outselect .= '<option value="' . $file . '" ' . $selected . '>' . $module->description . '</option>';
						}
					}
				}
			}
		}

		if (! empty($conf->referenceletters->enabled))
			$this->addReferenceLettersModelsToSelect($outselect, $model);

		$outselect .= '</select>';

		return $outselect;
	}

	/**
	 *
	 * @param string $outselect
	 * @param string $model
	 */
	function addReferenceLettersModelsToSelect(&$outselect, $model = '') {
		dol_include_once('/referenceletters/class/referenceletters_tools.class.php');
		if (class_exists('RfltrTools') && method_exists('RfltrTools', 'getAgefoddModelListDefault')) {
			$TModelAgefodd = RfltrTools::getAgefoddModelListDefault();
			if (is_array($TModelAgefodd) && count($TModelAgefodd) > 0) {
				foreach ( $TModelAgefodd as $line ) {
					if ($line->element_type == 'rfltr_agefodd_convention') {

						$selected = '';
						$defaultaffect = false;
						if (($model === 'rfltr_agefodd_' . $line->rowid || ! empty($line->default_doc)) && ! $defaultaffect) {
							$selected = 'selected="selected"';
							$defaultaffect = true;
						}
						$outselect .= '<option value="rfltr_agefodd_' . $line->rowid . '" ' . $selected . '>' . $line->title . '</option>';
					}
				}
			}
		}
	}

	/**
	 * Return multiselect list of entities.
	 *
	 * @param string $htmlname select
	 * @param array $options_array to manage
	 * @param array $selected_array to manage
	 * @param int $showempty show empty
	 * @return void
	 */
	public function agfmultiselectarray($htmlname, $options_array = array(), $selected_array = array(), $showempty = 0) {
		global $conf, $langs;

		if (! method_exists($this, 'multiselectarray')) {
			$return = '<script type="text/javascript" language="javascript">
						$(document).ready(function () {
							$.extend($.ui.multiselect.locale, {
								addAll:\'' . $langs->transnoentities("AddAll") . '\',
								removeAll:\'' . $langs->transnoentities("RemoveAll") . '\',
								itemsCount:\'' . $langs->transnoentities("ItemsCount") . '\'
							});

							$(function (){
								$("#' . $htmlname . '").addClass("' . $htmlname . '").attr("multiple","multiple").attr("name","' . $htmlname . '[]");
								$(".multiselect").multiselect({sortable: false, searchable: false});
							});
						});
					</script>';

			$return .= '<select id="' . $htmlname . '" class="multiselect" multiple="multiple" name="' . $htmlname . '[]" style="display: none;">';
			if ($showempty) {
				$return .= '<option value="">&nbsp;</option>';
			}

			// Find if keys is in selected array value
			if (is_array($selected_array) && count($selected_array) > 0) {
				$intersect_array = array_intersect_key($options_array, array_flip($selected_array));
			} else {
				$intersect_array = array();
			}

			if (count($options_array) > 0) {
				foreach ( $options_array as $keyoption => $valoption ) {
					// If key is in intersect table then it have to e selected
					if (count($intersect_array) > 0) {
						if (array_key_exists($keyoption, $intersect_array)) {
							$selected = ' selected="selected" ';
						} else {
							$selected = '';
						}
					}

					$return .= '<option ' . $selected . ' value="' . $keyoption . '">' . $valoption . '</option>';
				}
			}

			$return .= '</select>';
		} else {

			$return = $this->multiselectarray($htmlname, $options_array, $selected_array, '', 0, '', 0, '40%');
		}

		return $return;
	}
	public function selectMassSessionsAction()
	{
		global $langs;

		$sql = "SELECT rowid, code FROM " . MAIN_DB_PREFIX . "agefodd_session_status_type WHERE active = 1";

		$res = $this->db->query($sql);
		$TStatut = array();

		if ($res) {
			while ( $obj = $this->db->fetch_object($res) ) {
				$TStatut['set_statut' . $obj->rowid] = $langs->trans('AgfChangeStatutTo') . ' ' . $langs->trans('AgfStatusSession_' . $obj->code);
			}
		}

		return $this->selectMassAction('', $TStatut);
	}

	/**
	 * Permet de retourner un select html du dictionnaire llx_c_session_calendrier_type
	 *
	 * @global type $conf
	 * @param string $selected
	 * @param string $htmlname
	 * @param bool $emptyvalue
	 * @return string
	 */
	public function select_calendrier_type($selected = '', $htmlname = 'code_c_session_calendrier_type', $emptyvalue = true, $moreattr = '', $more_class = '')
	{
		global $conf;

		$out = '<select class="flat select_calendrier_type ' . $more_class . '" name="' . $htmlname . '" id="' . $htmlname . '" ' . $moreattr . '>';
		if ($emptyvalue)
			$out .= '<option value=""></options>';

		// TODO optimisation possible en stockant dans un attribut les codes lors d'un premier passage
		$sql = 'SELECT code, label FROM ' . MAIN_DB_PREFIX . 'c_agefodd_session_calendrier_type WHERE active = 1 AND entity IN (0,' . $conf->entity.')';
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$out .= '<option value="' . $obj->code . '" ' . ($selected == $obj->code ? 'selected' : '') . '>' . $obj->label . '</options>';
			}
		} else {
			dol_print_error($this->db);
		}

		$out .= '</select>';

		return $out;
	}

	/**
	 * Permet de retourner un select html des formateur d'une session
	 *
	 * @param int $sessionId Session Id
	 * @param int $selectId selected id
	 * @param string $htmlname html input name
	 * @param int $showempty show empty value
	 * @return string
	 */
	public function selectSessionTrainer($sessionId = 0, $selectId = 0, $htmlname = 'trainerid', $showempty = 1)
	{
		if (!empty($sessionId)) {
			require_once 'agefodd_session_formateur.class.php';

			$arrayTrainer=array();
			$sess_trainer = new Agefodd_session_formateur($this->db);
			$result = $sess_trainer->fetch_formateur_per_session($sessionId);
			if ($result < 0) {
				setEventMessage($sess_trainer->error, 'errors');
			} else {
				if (is_array($sess_trainer->lines) && count($sess_trainer->lines)>0) {
					foreach($sess_trainer->lines as $line) {
						$arrayTrainer[$line->opsid]=$line->firstname.' '.$line->lastname;
					}
				}
			}
			return $this->selectarray($htmlname, $arrayTrainer, $selectId, $showempty);
		} else {
			return'';
		}
	}

	/**
	 *
	 * @global type $conf
	 * @param string $selected
	 * @param string $htmlname
	 * @param bool $emptyvalue
	 * @return string
	 */
	public function select_calendrier_status($selected = '', $htmlname = 'calendar_status') {
		global $conf, $langs;
		dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
		$TStatus = Agefodd_sesscalendar::getListStatus();
		return $this->selectarray($htmlname, $TStatus, $selected);
	}

	/**
	 * Display multiselect of session status from dictionnary
	 *
	 * @param string $selected value
	 * @param string $htmlname of combo list (example: 'search_sale')
	 * @param string $filter filter
	 * @return string combo list code
	 */
	function multiselect_session_status($htmlname = 'search_session_status', $selected_array = array(), $filter = array()) {
		global $conf, $langs;

		$options_array = array();

		$sql = "SELECT t.rowid, t.code ,t.intitule ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_status_type as t";
		if (! empty($filter)) {
			$sql .= ' WHERE ' . $filter;
		}
		$sql .= " ORDER BY t.sort";

		dol_syslog(get_class($this) . "::multiselect_session_status sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {

			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num) {
				while ( $obj = $this->db->fetch_object($result) ) {
					if ($obj->intitule == $langs->trans('AgfStatusSession_' . $obj->code)) {
						$label = stripslashes($obj->intitule);
					} else {
						$label = $langs->trans('AgfStatusSession_' . $obj->code);
					}

					$options_array[$obj->rowid] = $label;

					$i ++;
				}
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}

		return $this->multiselectarray($htmlname, $options_array, $selected_array);
	}
}
