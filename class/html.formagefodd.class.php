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
class FormAgefodd extends Form {
	var $db;
	var $error;
	var $type_session_def;
	
	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	function __construct($db) {
		global $langs;
		$this->db = $db;
		$this->type_session_def = array (
		0 => $langs->trans ( 'AgfFormTypeSessionIntra' ),
		1 => $langs->trans ( 'AgfFormTypeSessionInter' ) 
		);
		return 1;
	}
	
	/**
	 * Affiche un champs select contenant la liste des formations disponibles.
	 *
	 * @param int $selectid à preselectionner
	 * @param string $htmlname select field
	 * @param string $sort Value to show/edit (not used in this function)
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string select field
	 */
	function select_formation($selectid, $htmlname = 'formation', $sort = 'intitule', $showempty = 0, $forcecombo = 0, $event = array(), $filters = array()) {
		global $conf, $user, $langs;
		
		$out = '';
		
		if ($sort == 'code')
			$order = 'c.ref';
		else
			$order = 'c.intitule';
		
		$sql = "SELECT c.rowid, c.intitule, c.ref";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " WHERE archive = 0";
		$sql .= " AND entity IN (" . getEntity ( 'agsession' ) . ")";
		if (count($filters)>0) {
			foreach($filters as $filter)
				$sql .= $filter;
		}
		$sql .= " ORDER BY " . $order;
		
		dol_syslog ( get_class ( $this ) . "::select_formation sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if ($resql) {
			if ($conf->use_javascript_ajax && $conf->global->AGF_TRAINING_USE_SEARCH_TO_SELECT && ! $forcecombo) {
				$out .= ajax_combobox ( $htmlname, $event );
			}
			
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty)
				$out .= '<option value="-1"></option>';
			$num = $this->db->num_rows ( $resql );
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object ( $resql );
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
			dol_print_error ( $this->db );
		}
		$this->db->free ( $resql );
		return $out;
	}
	
	/**
	 * Affiche un champs select contenant la liste des cursus disponibles.
	 *
	 * @param int $selectid à preselectionner
	 * @param string $htmlname select field
	 * @param string $sort Value to show/edit (not used in this function)
	 * @param int $showempty empty field
	 * @param int $forcecombo use combo box
	 * @param array $event
	 * @return string select field
	 */
	function select_cursus($selectid, $htmlname = 'cursus', $sort = 'c.ref_interne', $showempty = 0, $forcecombo = 0, $event = array(), $filters = array()) {
		global $conf, $user, $langs;
	
		$out = '';

	
		$sql = "SELECT c.rowid, c.intitule, c.ref_interne";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_cursus as c";
		$sql .= " WHERE archive = 0";
		$sql .= " AND entity IN (" . getEntity ( 'agsession' ) . ")";
		if (count($filters)>0) {
			foreach($filters as $filter)
				$sql .= $filter;
		}
		$sql .= " ORDER BY " . $sort;
	
		dol_syslog ( get_class ( $this ) . "::select_cursus sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if ($resql) {
			if ($conf->use_javascript_ajax && $conf->global->AGF_CURSUS_USE_SEARCH_TO_SELECT && ! $forcecombo) {
				$out .= ajax_combobox ( $htmlname, $event );
			}
				
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty)
				$out .= '<option value=""></option>';
			$num = $this->db->num_rows ( $resql );
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object ( $resql );
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
			dol_print_error ( $this->db );
		}
		$this->db->free ( $resql );
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
	function select_action_session_adm($selectid = '', $htmlname = 'action_level', $excludeid = '') {
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
		
		dol_syslog ( get_class ( $this ) . "::select_action_session_adm sql=" . $sql, LOG_DEBUG );
		$result = $this->db->query ( $sql );
		if ($result) {
			$var = True;
			$num = $this->db->num_rows ( $result );
			$i = 0;
			$options = '<option value=""></option>' . "\n";
			
			while ( $i < $num ) {
				$obj = $this->db->fetch_object ( $result );
				if ($obj->rowid == $selectid)
					$selected = ' selected="true"';
				else
					$selected = '';
				$strRank = str_repeat ( '-', $obj->level_rank );
				$options .= '<option value="' . $obj->rowid . '"' . $selected . '>';
				$options .= $strRank . ' ' . stripslashes ( $obj->intitule ) . '</option>' . "\n";
				$i ++;
			}
			$this->db->free ( $result );
			return '<select class="flat" style="width:300px" name="' . $htmlname . '">' . "\n" . $options . "\n" . '</select>' . "\n";
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::select_action_session_adm " . $this->error, LOG_ERR );
			return - 1;
		}
	}
	
	/**
	 * Display select list with training action administrative task
	 *
	 * @param int $selectid à preselectionner
	 * @param string $htmlname select field
	 * @param string $excludeid est necessaire d'exclure une valeur de sortie
	 * @return string select field
	 */
	function select_action_training_adm($selectid = '', $htmlname = 'action_level', $excludeid = '') {
		global $conf, $langs;
		
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.level_rank,";
		$sql .= " t.intitule";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_training_admlevel as t";
		if ($excludeid != '') {
			$sql .= ' WHERE t.rowid<>\'' . $excludeid . '\'';
		}
		$sql .= " ORDER BY t.indice";
		
		dol_syslog ( get_class ( $this ) . "::select_action_training_adm sql=" . $sql, LOG_DEBUG );
		$result = $this->db->query ( $sql );
		if ($result) {
			$var = True;
			$num = $this->db->num_rows ( $result );
			$i = 0;
			$options = '<option value=""></option>' . "\n";
			
			while ( $i < $num ) {
				$obj = $this->db->fetch_object ( $result );
				if ($obj->rowid == $selectid)
					$selected = ' selected="true"';
				else
					$selected = '';
				$strRank = str_repeat ( '-', $obj->level_rank );
				$options .= '<option value="' . $obj->rowid . '"' . $selected . '>';
				$options .= $strRank . ' ' . stripslashes ( $obj->intitule ) . '</option>' . "\n";
				$i ++;
			}
			$this->db->free ( $result );
			return '<select class="flat" style="width:300px" name="' . $htmlname . '">' . "\n" . $options . "\n" . '</select>' . "\n";
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::select_action_training_adm " . $this->error, LOG_ERR );
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
	function select_action_session($session_id = 0, $selectid = '', $htmlname = 'action_level') {
		global $conf, $langs;
		
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.level_rank,";
		$sql .= " t.intitule";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as t";
		$sql .= ' WHERE t.fk_agefodd_session=\'' . $session_id . '\'';
		$sql .= " ORDER BY t.indice";
		
		dol_syslog ( get_class ( $this ) . "::select_action_session sql=" . $sql, LOG_DEBUG );
		$result = $this->db->query ( $sql );
		if ($result) {
			$var = True;
			$num = $this->db->num_rows ( $result );
			$i = 0;
			$options = '<option value=""></option>' . "\n";
			
			while ( $i < $num ) {
				$obj = $this->db->fetch_object ( $result );
				if ($obj->rowid == $selectid)
					$selected = ' selected="true"';
				else
					$selected = '';
				$strRank = str_repeat ( '-', $obj->level_rank );
				$options .= '<option value="' . $obj->rowid . '"' . $selected . '>';
				$options .= $strRank . ' ' . stripslashes ( $obj->intitule ) . '</option>' . "\n";
				$i ++;
			}
			$this->db->free ( $result );
			return '<select class="flat" style="width:300px" name="' . $htmlname . '">' . "\n" . $options . "\n" . '</select>' . "\n";
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::select_action_session " . $this->error, LOG_ERR );
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
	function select_site_forma($selectid, $htmlname = 'place', $showempty = 0, $forcecombo = 0, $event = array()) {
		global $conf, $langs;
		
		$sql = "SELECT p.rowid, p.ref_interne";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_place as p";
		$sql .= " WHERE archive = 0";
		$sql .= " AND p.entity IN (" . getEntity ( 'agsession' ) . ")";
		$sql .= " ORDER BY p.ref_interne";
		
		dol_syslog ( get_class ( $this ) . "::select_site_forma sql=" . $sql, LOG_DEBUG );
		$result = $this->db->query ( $sql );
		if ($result) {
			if ($conf->use_javascript_ajax && $conf->global->AGF_SITE_USE_SEARCH_TO_SELECT && ! $forcecombo) {
				$out .= ajax_combobox ( $htmlname, $event );
			}
			
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty)
				$out .= '<option value="-1"></option>';
			$num = $this->db->num_rows ( $result );
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object ( $result );
					$label = $obj->ref_interne;
					
					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free ( $result );
			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::select_site_forma " . $this->error, LOG_ERR );
			return - 1;
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
	function select_stagiaire($selectid = '', $htmlname = 'stagiaire', $filter = '', $showempty = 0, $forcecombo = 0, $event = array()) {
		global $conf, $langs;
		
		$sql = "SELECT";
		$sql .= " s.rowid, CONCAT(s.nom,' ',s.prenom) as fullname,";
		$sql .= " so.nom as socname, so.rowid as socid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = s.fk_soc";
		if (! empty ( $filter )) {
			$sql .= ' WHERE ' . $filter;
			$sql .= " AND s.entity IN (" . getEntity ( 'agsession' ) . ")";
		} else {
			$sql .= " WHERE s.entity IN (" . getEntity ( 'agsession' ) . ")";
		}
		$sql .= " ORDER BY fullname";
		
		dol_syslog ( get_class ( $this ) . "::select_stagiaire sql=" . $sql, LOG_DEBUG );
		$result = $this->db->query ( $sql );
		if ($result) {
			if ($conf->use_javascript_ajax && $conf->global->AGF_TRAINEE_USE_SEARCH_TO_SELECT && ! $forcecombo) {
				$out .= ajax_combobox ( $htmlname, $event, 3 );
			}
			
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty)
				$out .= '<option value="-1"></option>';
			$num = $this->db->num_rows ( $result );
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object ( $result );
					$label = $obj->fullname;
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
			$this->db->free ( $result );
			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::select_stagiaire " . $this->error, LOG_ERR );
			return - 1;
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
	function select_agefodd_contact($selectid = '', $htmlname = 'contact', $filter = '', $showempty = 0, $forcecombo = 0, $event = array()) {
		global $conf, $langs;
		
		$sql = "SELECT";
		$sql .= " c.rowid, ";
		$sql .= " s.lastname, s.firstname, s.civilite, ";
		$sql .= " soc.nom as socname";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_contact as c";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as s ON c.fk_socpeople = s.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as soc ON soc.rowid = s.fk_soc";
		$sql .= " WHERE c.archive = 0";
		if (! empty ( $filter )) {
			$sql .= ' AND ' . $filter;
		}
		$sql .= " ORDER BY socname";
		
		dol_syslog ( get_class ( $this ) . "::select_agefodd_contact sql=" . $sql, LOG_DEBUG );
		$result = $this->db->query ( $sql );
		if ($result) {
			if ($conf->use_javascript_ajax && $conf->global->AGF_CONTACT_USE_SEARCH_TO_SELECT && ! $forcecombo) {
				$out .= ajax_combobox ( $htmlname, $event );
			}
			
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty)
				$out .= '<option value="-1"></option>';
			$num = $this->db->num_rows ( $result );
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object ( $result );
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
			$this->db->free ( $result );
			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::select_agefodd_contact " . $this->error, LOG_ERR );
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
	function select_formateur($selectid = '', $htmlname = 'formateur', $filter = '', $showempty = 0, $forcecombo = 0, $event = array()) {
		global $conf, $langs;
		
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
		if (! empty ( $filter )) {
			$sql .= ' AND ' . $filter;
		}
		$sql .= " ORDER BY sp.lastname,u.lastname";
		
		dol_syslog ( get_class ( $this ) . "::select_formateur sql=" . $sql, LOG_DEBUG );
		$result = $this->db->query ( $sql );
		if ($result) {
			
			if ($conf->use_javascript_ajax && $conf->global->AGF_TRAINER_USE_SEARCH_TO_SELECT && ! $forcecombo) {
				$out .= ajax_combobox ( $htmlname, $event );
			}
			
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty)
				$out .= '<option value=""></option>';
			$num = $this->db->num_rows ( $result );
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object ( $result );
					if (! empty ( $obj->fk_socpeople )) {
						$label = $obj->fullname_contact;
					}
					if (! empty ( $obj->fk_user )) {
						$label = $obj->fullname_user;
					}
					
					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free ( $result );
			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::select_formateur " . $this->error, LOG_ERR );
			return - 1;
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
	function select_type_stagiaire($selectid, $htmlname = 'stagiaire_type', $filter = '', $showempty = 0, $forcecombo = 0, $event = array()) {
		global $conf, $langs;
		
		$sql = "SELECT t.rowid, t.intitule";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire_type as t";
		if (! empty ( $filter )) {
			$sql .= ' WHERE ' . $filter;
		}
		$sql .= " ORDER BY t.sort";
		
		dol_syslog ( get_class ( $this ) . "::select_type_stagiaire sql=" . $sql, LOG_DEBUG );
		$result = $this->db->query ( $sql );
		if ($result) {
			
			if ($conf->use_javascript_ajax && $conf->global->AGF_STAGTYPE_USE_SEARCH_TO_SELECT && ! $forcecombo) {
				$out .= ajax_combobox ( $htmlname, $event );
			}
			
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty)
				$out .= '<option value="-1"></option>';
			$num = $this->db->num_rows ( $result );
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object ( $result );
					$label = stripslashes ( $obj->intitule );
					
					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free ( $result );
			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::select_type_stagiaire " . $this->error, LOG_ERR );
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
	 * @return string The HTML control
	 */
	function select_session_status($selectid, $htmlname = 'session_status', $filter = '', $showempty = 0, $forcecombo = 0, $event = array()) {
		global $conf, $langs;
	
		$sql = "SELECT t.rowid, t.code ,t.intitule ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_status_type as t";
		if (! empty ( $filter )) {
			$sql .= ' WHERE ' . $filter;
		}
		$sql .= " ORDER BY t.sort";
	
		dol_syslog ( get_class ( $this ) . "::select_session_status sql=" . $sql, LOG_DEBUG );
		$result = $this->db->query ( $sql );
		if ($result) {
				
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty)
				$out .= '<option value=""></option>';
			$num = $this->db->num_rows ( $result );
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object ( $result );
					if ($obj->intitule==$langs->trans('AgfStatusSession_'.$obj->code)) {
						$label=stripslashes ( $obj->intitule );
					}else {
						$label=$langs->trans('AgfStatusSession_'.$obj->code);
					}
						
					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free ( $result );
			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::select_session_status " . $this->error, LOG_ERR );
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
	function select_type_affect($selectid, $htmlname = 'search_type_affect' ) {
		global $conf, $langs;
	
		$select_array=array(
		'thirdparty'=>$langs->trans('ThirdParty'),
		'trainee'=>$langs->trans('AgfParticipant')
		);
		
		if ($conf->global->AGF_MANAGE_OPCA) {
			$select_array['opca']=$langs->trans('AgfMailTypeContactOPCA');
		}
		
		return $this->selectarray ( $htmlname, $select_array, $selectid, 0 );
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
	function select_training_categ($selectid, $htmlname = 'stagiaire_type', $filter = '', $showempty = 1) {
		global $conf, $langs;
	
		$sql = "SELECT t.rowid, t.code, t.intitule";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_type as t";
		if (! empty ( $filter )) {
			$sql .= ' WHERE ' . $filter;
		}
		$sql .= " ORDER BY t.sort";
	
		dol_syslog ( get_class ( $this ) . "::select_training_categ sql=" . $sql, LOG_DEBUG );
		$result = $this->db->query ( $sql );
		if ($result) {
				
			$out .= '<select id="' . $htmlname . '" class="flat" name="' . $htmlname . '">';
			if ($showempty)
				$out .= '<option value="-1"></option>';
			$num = $this->db->num_rows ( $result );
			$i = 0;
			if ($num) {
				while ( $i < $num ) {
					$obj = $this->db->fetch_object ( $result );
					$label = stripslashes ( $obj->code.' - '.$obj->intitule );
						
					if ($selectid > 0 && $selectid == $obj->rowid) {
						$out .= '<option value="' . $obj->rowid . '" selected="selected">' . $label . '</option>';
					} else {
						$out .= '<option value="' . $obj->rowid . '">' . $label . '</option>';
					}
					$i ++;
				}
			}
			$out .= '</select>';
			$this->db->free ( $result );
			return $out;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::select_training_categ " . $this->error, LOG_ERR );
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
	function level_graph($actual_level, $total_level, $title) {
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
	function select_time($selectval = '', $htmlname = 'period') {
		$time = 7;
		$heuref = 23;
		$min = 0;
		$options = '<option value=""></option>' . "\n";
		while ( $time < $heuref ) {
			if ($min == 60) {
				$min = 0;
				$time ++;
			}
			$ftime = sprintf ( "%02d", $time ) . ':' . sprintf ( "%02d", $min );
			if ($selectval == $ftime)
				$selected = ' selected="true"';
			else
				$selected = '';
			$options .= '<option value="' . $ftime . '"' . $selected . '>' . $ftime . '</option>' . "\n";
			$min += 15;
		}
		return '<select class="flat" name="' . $htmlname . '">' . "\n" . $options . "\n" . '</select>' . "\n";
	}
	
	/**
	 * Affiche une liste de sélection des types de formation
	 *
	 * @param string $htmlname control HTML
	 * @param int $selectval selectionner par defaut
	 * @param int $showempty Show Empty
	 * @return string HTML control
	 */
	function select_type_session($htmlname, $selectval, $showempty=0) {
		return $this->selectarray ( $htmlname, $this->type_session_def, $selectval, $showempty );
	}
	
	/**
	 * Show list of actions for element
	 *
	 * @param Object $object
	 * @param string $typeelement
	 * @param int $socid user
	 * @return int if KO, >=0 if OK
	 */
	function showactions($object, $typeelement = 'agefodd_agsession', $socid = 0) {
		global $langs, $conf, $user;
		global $bc;
		
		require_once (DOL_DOCUMENT_ROOT . "/comm/action/class/actioncomm.class.php");
		
		$action_arr = ActionComm::getActions ( $this->db, $socid, $object->id, $typeelement );
		$num = count ( $action_arr );
		if ($num) {
			if ($typeelement == 'agefodd_agsession')
				$title = $langs->trans ( 'AgfActionsOnTraining' );
				// elseif ($typeelement == 'fichinter') $title=$langs->trans('ActionsOnFicheInter');
			else
				$title = $langs->trans ( "Actions" );
			
			print_titre ( $title );
			
			$total = 0;
			$var = true;
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre"><th class="liste_titre">' . $langs->trans ( 'Ref' ) . '</th><th class="liste_titre">' . $langs->trans ( 'Date' ) . '</th><th class="liste_titre">' . $langs->trans ( 'Action' ) . '</th><th class="liste_titre">' . $langs->trans ( 'ThirdParty' ) . '</th><th class="liste_titre">' . $langs->trans ( 'By' ) . '</th></tr>';
			print "\n";
			
			foreach ( $action_arr as $action ) {
				$var = ! $var;
				print '<tr ' . $bc [$var] . '>';
				print '<td>' . $action->getNomUrl ( 1 ) . '</td>';
				print '<td>' . dol_print_date ( $action->datep, 'dayhour' ) . '</td>';
				print '<td title="' . dol_escape_htmltag ( $action->label ) . '">' . dol_trunc ( $action->label, 50 ) . '</td>';
				$userstatic = new User ( $this->db );
				$userstatic->id = $action->author->id;
				$userstatic->firstname = $action->author->firstname;
				$userstatic->lastname = $action->author->lastname;
				print '<td>' . $userstatic->getElementUrl ( $action->socid, 'societe', 1 ) . '</td>';
				print '<td>' . $userstatic->getNomUrl ( 1 ) . '</td>';
				print '</tr>';
			}
			print '</table>';
		}
		
		return $num;
	}
	
	/**
	 * Display select Trainee status in session
	 *
	 * @param string $selectval valeur a selectionner par defaut
	 * @param string $htmlname nom du control HTML
	 * @return string The HTML control
	 */
	function select_stagiaire_session_status($htmlname, $selectval) {
		require_once 'agefodd_session_stagiaire.class.php';
		$sess_sta = new Agefodd_session_stagiaire ( $this->db );
		
		return $this->selectarray ( $htmlname, $sess_sta->labelstatut, $selectval, 0 );
	}
	
	/**
	 * Output a HTML code to select a color
	 *
	 * @param string $set_color
	 * @param string $prefix HTML field
	 * @return string HTML result
	 */
	function select_color($set_color = '', $htmlname = 'f_color') {
		$out = '<input id="' . $htmlname . '" type="text" size="8" name="' . $htmlname . '" value="' . $set_color . '" />';
		
		$out .= '<script type="text/javascript" language="javascript">
			$(document).ready(function() {
			$("#' . $htmlname . '").css("backgroundColor", \'#' . $set_color . '\');
				$("#' . $htmlname . '").ColorPicker({
					color: \'#' . $set_color . '\',
						onShow: function (colpkr) {
						$(colpkr).fadeIn(500);
						return false;
	},
						onHide: function (colpkr) {
						$(colpkr).fadeOut(500);
						return false;
	},
						onChange: function (hsb, hex, rgb) {
						$("#' . $htmlname . '").css("backgroundColor", \'#\' + hex);
							$("#' . $htmlname . '").val(hex);
	},
								onSubmit: function (hsb, hex, rgb) {
								$("#' . $htmlname . '").val(hex);
	}
	});
	})
									.bind(\'keyup\', function(){
									$(this).ColorPickerSetColor(this.value);
	});
									</script>';
		
		return $out;
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
	 * @return void
	 */
	function agenda_filter($form, $year, $month, $day, $filter_commercial, $filter_customer, $filter_contact, $filter_trainer, $canedit = 1) {
		global $conf, $langs;
		
		print '<form name="listactionsfilter" class="listactionsfilter" action="' . $_SERVER ["PHP_SELF"] . '" method="POST">';
		print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
		print '<input type="hidden" name="status" value="' . $status . '">';
		print '<input type="hidden" name="year" value="' . $year . '">';
		print '<input type="hidden" name="month" value="' . $month . '">';
		print '<input type="hidden" name="day" value="' . $day . '">';
		print '<table class="nobordernopadding" width="100%">';
		
		print '<tr><td class="nowrap">';
		
		if (! empty ( $canedit )) {
			print '<table class="nobordernopadding">';
			
			print '<tr>';
			print '<td class="nowrap">';
			print $langs->trans ( "AgfSessionCommercial" );
			print ' &nbsp;</td><td class="nowrap">';
			$form->select_users ( $filter_commercial, 'commercial', 1, array (
			1 
			) );
			print '</td>';
			print '</tr>';
			
			print '<tr>';
			print '<td class="nowrap">';
			print $langs->trans ( "or" ) . ' ' . $langs->trans ( "Customer" );
			print ' &nbsp;</td><td class="nowrap">';
			if ($conf->global->AGF_CONTACT_DOL_SESSION) {
				$events = array ();
				$events [] = array (
				'method' => 'getContacts',
				'url' => dol_buildpath ( '/core/ajax/contacts.php', 1 ),
				'htmlname' => 'contact',
				'params' => array (
				'add-customer-contact' => 'disabled' 
				) 
				);
				print $form->select_company ( $filter_customer, 'fk_soc', '', 1, 1, 0, $events );
			} else {
				print $form->select_company ( $filter_customer, 'fk_soc', '', 1, 1 );
			}
			print '</td></tr>';
			print '<tr>';
			print '<td class="nowrap">';
			print $langs->trans ( "or" ) . ' ' . $langs->trans ( "AgfSessionContact" );
			print ' &nbsp;</td><td class="nowrap">';
			if ($conf->global->AGF_CONTACT_DOL_SESSION) {
				if (! empty ( $filter_customer )) {
					$form->select_contacts ( $filter_customer, $filter_contact, 'contact', 1, '', '', 1, '', 1 );
				} else {
					$form->select_contacts ( 0, $filter_contact, 'contact', 1, '', '', 1, '', 1 );
				}
			} else {
				print $this->select_agefodd_contact ( $filter_contact, 'contact', '', 1 );
			}
			print '</td></tr>';
			
			print '<tr>';
			print '<td class="nowrap">';
			print $langs->trans ( "or" ) . ' ' . $langs->trans ( "AgfFormateur" );
			print ' &nbsp;</td><td class="nowrap">';
			print $this->select_formateur ( $filter_trainer, "trainerid", '', 1 );
			print '</td></tr>';
			
			print '</table>';
		}
		print '</td>';
		
		// Buttons
		print '<td align="center" valign="middle" class="nowrap">';
		print img_picto ( $langs->trans ( "ViewCal" ), 'object_calendar', 'class="hideonsmartphone"' ) . ' <input type="submit" class="button" style="min-width:120px" name="viewcal" value="' . $langs->trans ( "ViewCal" ) . '">';
		print '<br>';
		print img_picto ( $langs->trans ( "ViewWeek" ), 'object_calendarweek', 'class="hideonsmartphone"' ) . ' <input type="submit" class="button" style="min-width:120px" name="viewweek" value="' . $langs->trans ( "ViewWeek" ) . '">';
		print '<br>';
		print img_picto ( $langs->trans ( "ViewDay" ), 'object_calendarday', 'class="hideonsmartphone"' ) . ' <input type="submit" class="button" style="min-width:120px" name="viewday" value="' . $langs->trans ( "ViewDay" ) . '">';
		print '<br>';
		print img_picto ( $langs->trans ( "ViewList" ), 'object_list', 'class="hideonsmartphone"' ) . ' <input type="submit" class="button" style="min-width:120px" name="viewlist" value="' . $langs->trans ( "ViewList" ) . '">';
		print '</td>';
		
		print '</tr>';
		
		print '</table>';
		print '</form>';
	}
}