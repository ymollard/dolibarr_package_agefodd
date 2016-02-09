<?php
/*
 * Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2012 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin <regis@dolibarr.fr>
 * Copyright (C) 2012 JF FERRY <jfefe@aternatik.fr>
 * Copyright (C) 2012-2016 Florian Henry <florian.henry@open-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file agefodd/class/sessionstats.class.php
 * \ingroup agefodd
 * \brief Fichier de la classe de gestion des stats des Formation
 */
require_once DOL_DOCUMENT_ROOT . "/core/class/stats.class.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/date.lib.php";

dol_include_once("/agefodd/class/agsession.class.php");

/**
 * \class SessionStats
 * \brief Classe permettant la gestion des stats des Sessions de formations
 */
class SessionStats extends Stats {
	public $socid;
	public $userid;
	public $table_element;
	public $from;
	public $field;
	public $where;
	
	/**
	 * Constructor
	 *
	 * @param DoliDB $db
	 * @param int $socid party
	 * @param string $mode
	 * @param int $userid user for filter
	 * @return SessionStats
	 */
	public function SessionStats($db, $socid = 0, $mode = '', $userid = 0, $training_id = 0) {
		global $conf;
		
		$this->db = $db;
		$this->socid = $socid;
		$this->userid = $userid;
		$this->training_id = $training_id;
		
		$object = new Agsession($this->db);
		$this->from = MAIN_DB_PREFIX . $object->table_element;
		$this->field = 'sell_price';
		
		// $this->where = " fk_statut > 0";
		$this->where .= " entity = " . $conf->entity;
		// if ($mode == 'customer') $this->where.=" AND (fk_statut <> 3 OR close_code <> 'replaced')"; // Exclude replaced invoices as they are
		// duplicated (we count closed invoices for other reasons)
		if ($this->socid) {
			$this->where .= " AND main.fk_soc = " . $this->socid;
		}
		if ($this->userid > 0)
			$this->where .= ' AND com.fk_user_com = ' . $this->userid;
		
		if ($this->training_id > 0)
			$this->where .= ' AND main.fk_formation_catalogue=' . $this->training_id;
	}
	
	/**
	 * Renvoie le nombre de facture par annee
	 *
	 * @return array of values
	 */
	public function getNbByYear() {
		$sql = "SELECT YEAR(main.datef) as dm, COUNT(DISTINCT main.rowid)";
		$sql .= " FROM " . $this->from . " as main";
		
		if (strpos($this->where, 'com.fk_user_com') !== false) {
			$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "agefodd_session_commercial as com ON main.rowid=com.fk_session_agefodd";
		}
		$sql .= " WHERE " . $this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');
		
		return $this->_getNbByYear($sql);
	}
	
	/**
	 * Renvoie le nombre de facture par mois pour une annee donnee
	 *
	 * @param int $year scan
	 * @return array of values
	 */
	public function getNbByMonth($year) {
		$sql = "SELECT MONTH(main.datef) as dm, COUNT(DISTINCT main.rowid)";
		$sql .= " FROM " . $this->from . " as main";
		if (strpos($this->where, 'com.fk_user_com') !== false) {
			$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "agefodd_session_commercial as com ON main.rowid=com.fk_session_agefodd";
		}
		$sql .= " WHERE datef BETWEEN '" . $this->db->idate(dol_get_first_day($year)) . "' AND '" . $this->db->idate(dol_get_last_day($year)) . "'";
		$sql .= " AND " . $this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');
		
		$res = $this->_getNbByMonth($year, $sql);
		// var_dump($res);print '<br>';
		return $res;
	}
	
	/**
	 * Renvoie le montant de facture par mois pour une annee donnee
	 *
	 * @param int $year scan
	 * @return array of values
	 */
	public function getAmountByMonth($year) {
		$sql = "SELECT date_format(main.datef,'%m') as dm, SUM(" . $this->field . ")";
		$sql .= " FROM " . $this->from . " as main";
		if (strpos($this->where, 'com.fk_user_com') !== false) {
			$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "agefodd_session_commercial as com ON main.rowid=com.fk_session_agefodd";
		}
		$sql .= " WHERE date_format(main.datef,'%Y') = '" . $year . "'";
		$sql .= " AND " . $this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');
		
		$res = $this->_getAmountByMonth($year, $sql);
		// var_dump($res);print '<br>';
		return $res;
	}
	
	/**
	 * Return average amount
	 *
	 * @param int $year scan
	 * @return array of values
	 */
	public function getAverageByMonth($year) {
		$sql = "SELECT date_format(main.datef,'%m') as dm, AVG(" . $this->field . ")";
		$sql .= " FROM " . $this->from . " as main";
		if (strpos($this->where, 'com.fk_user_com') !== false) {
			$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "agefodd_session_commercial as com ON main.rowid=com.fk_session_agefodd";
		}
		$sql .= " WHERE datef BETWEEN '" . $this->db->idate(dol_get_first_day($year)) . "' AND '" . $this->db->idate(dol_get_last_day($year)) . "'";
		$sql .= " AND " . $this->where;
		$sql .= " GROUP BY dm";
		$sql .= $this->db->order('dm', 'DESC');
		
		return $this->_getAverageByMonth($year, $sql);
	}
	
	/**
	 * Return nb, total and average
	 *
	 * @return array of values
	 */
	public function getAllByYear() {
		$sql = "SELECT date_format(main.datef,'%Y') as year, COUNT(DISTINCT main.rowid) as nb, SUM(" . $this->field . ") as total, AVG(" . $this->field . ") as avg";
		$sql .= " FROM " . $this->from . " as main";
		if (strpos($this->where, 'com.fk_user_com') !== false) {
			$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "agefodd_session_commercial as com ON main.rowid=com.fk_session_agefodd";
		}
		$sql .= " WHERE " . $this->where;
		$sql .= " GROUP BY year";
		$sql .= $this->db->order('year', 'DESC');
		
		return $this->_getAllByYear($sql);
	}
}
