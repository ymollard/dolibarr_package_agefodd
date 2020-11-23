<?php
/*
 * Copyright (C) 2007-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2014		Florian Henry 		<florian.henry@open-concept.pro>
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
 * \file agefodd/class/agefodd_index.class.php
 * \ingroup agefodd
 * \brief Index page public function
 */
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
class Agefodd_index_line
{
	public $sessid;
	public $rowid;
	public $intitule;
	public $dated;
	public $datef;
	public $idforma;
	public $id;
	public $ref;
	public $ref_interne;
	public $num;
	public $duree;
}
class Agefodd_CertifExpireSoc_line
{
	public $customer_name;
	public $customer_id;
	public $fromintitule;
	public $fromref;
}

/**
 * Index pages
 */
class Agefodd_index
{
	/** @var DoliDB $db */
	protected $db;
	public $error;
	public $errors = array();
	public $element = 'agefodd';
	public $table_element = 'agefodd';
	public $id;
	public $lines = array();

	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	public function __construct($db) {
		$this->db = $db;
		return 1;
	}

	/**
	 * Load object in memory from database
	 *
	 * @return int if KO, $num of student if OK
	 */
	public function fetch_student_nb() {
		$sql = "SELECT ";
		$sql .= " sum(se.nb_stagiaire) as nb_sta ";
		$sql .= " FROM  " . MAIN_DB_PREFIX . "agefodd_session as se";
		$sql .= " WHERE se.status IN (4,5)";
		$sql .= " AND se.entity IN (" . getEntity('agefodd') . ")";

		dol_syslog(get_class($this) . "::fetch_student_nb", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			if (! empty($num)) {
				$obj = $this->db->fetch_object($resql);
				$num = $obj->nb_sta;
				if (empty($num)) {
					$num = 0;
				}
			} else {
				$num = 0;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_student_nb " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @return int if KO, $num of student if OK
	 */
	public function fetch_session_nb() {
		$sql = "SELECT count(*) as num";
		$sql .= " FROM  " . MAIN_DB_PREFIX . "agefodd_session";
		$sql .= " WHERE status = 5";
		$sql .= " AND entity IN (" . getEntity('agefodd') . ")";

		dol_syslog(get_class($this) . "::fetch_session_nb ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->num = $obj->num;
			} else
				$this->num = 0;

			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_session_nb " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @return int if KO, $num of student if OK
	 */
	public function fetch_formation_nb() {
		$sql = "SELECT count(*) as num";
		$sql .= " FROM  " . MAIN_DB_PREFIX . "agefodd_formation_catalogue";
		$sql .= " WHERE archive = 0";
		$sql .= " AND entity IN (" . getEntity('agefodd') . ")";

		dol_syslog(get_class($this) . "::fetch_formation_nb ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->num = $obj->num;
			}
			if ($obj->num == '')
				$this->num = 0;
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_formation_nb " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @return int if KO, $num of student if OK
	 */
	public function fetch_heures_sessions_nb() {
		$sql = "SELECT  sum(f.duree) AS total";
		$sql .= " FROM  " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue AS f";
		$sql .= " ON s.fk_formation_catalogue = f.rowid";
		$sql .= " WHERE s.status IN (4,5)";
		$sql .= " AND s.entity IN (" . getEntity('agefodd' /*agsession*/
		) . ")";
		// $sql.= " GROUP BY f.duree";

		dol_syslog(get_class($this) . "::fetch_heures_sessions_nb ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->total = $obj->total;
			}
			if ($obj->total == '')
				$this->total = 0;
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_heures_sessions_nb " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @return int if KO, $num of student if OK
	 */
	public function fetch_heures_stagiaires_nb() {
		$error = 0;

		$this->fetch_heures_sessions_nb();
		$nbhour = $this->total;

		$sql = "SELECT  sum(s.nb_stagiaire) AS total";
		$sql .= " FROM  " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue AS f";
		$sql .= " ON s.fk_formation_catalogue = f.rowid";
		$sql .= " WHERE s.status IN (4,5)";
		$sql .= " AND s.entity IN (" . getEntity('agefodd' /*agsession*/
		) . ")";
		// $sql.= " GROUP BY f.duree";

		dol_syslog(get_class($this) . "::fetch_heures_stagiaires_nb ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->total = $obj->total * (empty($nbhour) ? 1 : $nbhour);
			}
			if ($obj->total == '') {
				$this->total = 0;
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_heures_stagiaires_nb " . $this->error, LOG_ERR);
			$error ++;
		}

		if (empty($error)) {
			$this->db->free($resql);
			return 1;
		} else {
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $number number of sessions to display
	 * @return int if KO, $num of student if OK
	 */
	public function fetch_last_formations($number = 5) {
		$this->lines = array();

		$sql = "SELECT c.intitule, s.dated, s.datef, s.fk_formation_catalogue, s.rowid as id, s.ref as ref";
		$sql .= " FROM  " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " ON c.rowid = s.fk_formation_catalogue";
		$sql .= " WHERE s.status IN (4,5)";
		$sql .= " AND s.entity IN (" . getEntity('agefodd' /*agsession*/
		) . ")";
		$sql .= " ORDER BY s.dated DESC LIMIT " . $number;

		dol_syslog(get_class($this) . "::fetch_last_formations", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new Agefodd_index_line();

				$line->intitule = $obj->intitule;
				$line->dated = $this->db->jdate($obj->dated);
				$line->datef = $this->db->jdate($obj->datef);
				$line->idforma = $obj->fk_formation_catalogue;
				$line->id = $obj->id;
				$line->ref = $obj->ref;

				$this->lines[$i] = $line;

				$i ++;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_last_formations " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $number number of sessions to display
	 * @return int if KO, $num of student if OK
	 */
	public function fetch_top_formations($number = 5) {
		$this->lines = array();

		$sql = "SELECT c.intitule, count(s.rowid) as num, c.duree, c.ref, c.ref_interne, ";
		$sql .= " s.fk_formation_catalogue";
		$sql .= " FROM  " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " ON c.rowid = s.fk_formation_catalogue";
		$sql .= " WHERE s.status IN (5,4)";
		$sql .= " AND s.entity IN (" . getEntity('agefodd') . ")";
		$sql .= " GROUP BY c.intitule, c.duree, c.ref, c.ref_interne, s.fk_formation_catalogue";
		$sql .= " ORDER BY num DESC LIMIT " . $number;

		dol_syslog(get_class($this) . "::fetch_top_formations", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new Agefodd_index_line();
				$line->intitule = $obj->intitule;
				$line->ref = $obj->ref;
				$line->ref_interne = $obj->ref_interne;
				$line->num = $obj->num;
				$line->duree = $obj->duree;
				$line->idforma = $obj->fk_formation_catalogue;

				$this->lines[$i] = $line;

				$i ++;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_top_formations " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $fk_status session_status_type
	 * @return int if KO, $num of student if OK
	 */
	public function fetch_session($fk_status = 0) {
		$sql = "SELECT count(*) as total";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session";
		$sql .= " WHERE";
		if (empty($fk_status))
			$sql .= " status <> 4";
		else
			$sql .= " status=$fk_status";

		$sql .= " AND entity IN (" . getEntity('agefodd' /*agsession*/
		) . ")";

		dol_syslog(get_class($this) . "::fetch_session", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->total = $obj->total;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_session " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @return int if KO, $num of student if OK
	 */
	public function fetch_tache_late() {
		$this->lines = array();

		$sql = "SELECT DISTINCT asa.rowid, asa.fk_agefodd_session";
		$sql .= " FROM  " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as asa";
		$sql .= " LEFT JOIN  " . MAIN_DB_PREFIX . "agefodd_session as asession ON (asession.rowid = asa.fk_agefodd_session)";
		$sql .= ' WHERE 1=1 ';
		$sql .= " AND asa.datea <= NOW() ";
		$sql .= " AND asa.fk_parent_level <> 0 AND asa.archive <> 1";
		$sql .= " AND asession.entity IN (" . getEntity('agefodd') . ")";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array();
			if ($this->db->num_rows($resql)) {
				$num = $this->db->num_rows($resql);
				$i = 0;

				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);

					$line = new Agefodd_index_line();

					$line->rowid = $obj->rowid;
					$line->sessid = $obj->fk_agefodd_session;

					$this->lines[$line->rowid] = $line;

					$i ++;
				}
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $nbjourst Nb days before now
	 * @param int $nbjourend Nb days after now
	 * @return int if KO, $num of student if OK
	 */
	public function fetch_tache_in_between($nbjourst = 0, $nbjourend = 0) {
		$this->lines = array();

		$intervaldayst = $nbjourst . ' DAY';
		if ($this->db->type == 'pgsql') {
			$intervaldayst = "'" . $nbjourst . " DAYS'";
		}

		$intervaldayend = $nbjourend . ' DAY';
		if ($this->db->type == 'pgsql') {
			$intervaldayend = "'" . $nbjourend . " DAYS'";
		}

		$sql = "SELECT DISTINCT asa.rowid,asa.fk_agefodd_session, asa.intitule";
		$sql .= " FROM  " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as asa";
		$sql .= " LEFT JOIN  " . MAIN_DB_PREFIX . "agefodd_session as asession ON (asession.rowid = asa.fk_agefodd_session)";
		$sql .= ' WHERE 1=1 ';
		if (!empty($nbjourst) && !empty($nbjourend)) {
			$sql .= " AND (NOW() BETWEEN (asa.datea - INTERVAL " . $intervaldayend . ") AND (asa.datea - INTERVAL " . $intervaldayst . "))";
		} elseif (!empty($nbjourst) && empty($nbjourend)) {
			$sql .= " AND (asa.datea > (NOW() +  INTERVAL " . $intervaldayst . "))";
		} elseif (empty($nbjourst) && ! empty($nbjourend)) {
			$sql .= " AND (NOW() BETWEEN (asa.datea - INTERVAL " . $intervaldayend . ") AND asa.datea)";
		}
		$sql .= " AND asa.rowid NOT IN (select fk_parent_level FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu) AND asa.archive <> 1";
		$sql .= " AND asession.entity IN (" . getEntity('agefodd') . ")";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array();
			if ($this->db->num_rows($resql)) {
				$num = $this->db->num_rows($resql);
				$i = 0;

				while ( $i < $num ) {
					$obj = $this->db->fetch_object($resql);

					$line = new Agefodd_index_line();

					$line->rowid = $obj->rowid;
					$line->sessid = $obj->fk_agefodd_session;
					$line->intitule = $obj->intitule;

					$this->lines[$line->rowid] = $line;

					$i ++;
				}
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = __METHOD__.' '.$sql." Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @return int if KO, $num of student if OK
	 */
	public function fetch_tache_en_cours() {
		$sql = "SELECT count(*) as total";
		$sql .= " FROM  " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as asa";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as ags ON asa.fk_agefodd_session = ags.rowid";
		$sql .= " WHERE asa.rowid NOT IN (select fk_parent_level FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu) AND asa.archive <> 1";
		$sql .= " AND ags.entity IN (" . getEntity('agefodd') . ")";

		dol_syslog(get_class($this) . "::fetch_tache_en_cours", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->total = $obj->total;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_tache_en_cours " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit offset limit
	 * @param int $offset offset limit
	 * @param int $delais_sup high limit
	 * @param int $delais_inf low limit
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_session_per_dateLimit($sortorder, $sortfield, $limit, $offset, $delais_sup, $delais_inf = 0) {
		$intervalday_sup = $delais_sup . ' DAY';
		$intervalday_inf = $delais_inf . ' DAY';

		if ($this->db->type == 'pgsql') {
			$intervalday_sup = "'" . $delais_sup . " DAYS'";
			$intervalday_inf = "'" . $delais_inf . " DAYS'";
		}

		$sql = "SELECT";
		$sql .= " s.rowid, s.fk_agefodd_session_admlevel, s.fk_agefodd_session, s.intitule,";
		$sql .= " s.delais_alerte, s.indice, s.dated, s.datef, s.datea, s.notes,";
		$sql .= " sess.dated as sessdated, sess.datef as sessdatef,";
		$sql .= " f.intitule as titre";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session as sess";
		$sql .= " ON s.fk_agefodd_session = sess.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as f";
		$sql .= " ON sess.fk_formation_catalogue = f.rowid";
		$sql .= " WHERE s.archive = 0 AND (NOW() < s.datef)";
		$sql .= " AND sess.status IN (1,2)";
		if (! empty($delais_sup) && ! empty($delais_inf)) {
			if ($delais_sup != 1)
				$delais_sup_sql = 's.datea - INTERVAL ' . $intervalday_sup;
			else
				$delais_sup_sql = 's.datea';

			if ($delais_inf != 1)
				$delais_inf_sql = 's.datea - INTERVAL ' . $intervalday_inf;
			else
				$delais_inf_sql = 's.datea';

			$sql .= " AND  ( ";
			$sql .= ' NOW() BETWEEN (' . $delais_sup_sql . ') AND (' . $delais_inf_sql . ')';
			$sql .= " )";
		}

		$sql .= " ORDER BY " . $sortfield . " " . $sortorder . " " . $this->db->plimit($limit + 1, $offset);

		dol_syslog(get_class($this) . "::fetch_session_per_dateLimit", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new Agefodd_index_line();

				$line->rowid = $obj->rowid;
				$line->sessid = $obj->fk_agefodd_session;

				$this->lines[$i] = $line;

				$i ++;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @return int if KO, $num of student if OK
	 */
	public function fetch_session_to_archive() {
		// Il faut que toutes les tâches administratives soit crées (top_level);
		$sql = "SELECT MAX(sa.datea), sa.fk_agefodd_session";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_adminsitu as sa";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " ON s.rowid = sa.fk_agefodd_session";
		$sql .= " WHERE sa.archive = 1";
		$sql .= " AND sa.level_rank=0";
		$sql .= " AND s.status <> 4";
		$sql .= " AND s.entity IN (" . getEntity('agefodd') . ")";
		$sql .= " GROUP BY sa.fk_agefodd_session";

		dol_syslog(get_class($this) . "::fetch_session_to_archive", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				$obj = $this->db->fetch_object($resql);
				$this->sessid = $obj->fk_agefodd_session;
			}

			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_session_to_archive " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $month_expiration Months before expiration
	 * @return int if KO, $num of student if OK
	 */
	public function fetch_certif_expire($month_expiration) {

		$sql = "SELECT ";
		$sql .= " DISTINCT ";
		$sql .= "c.intitule as fromintitule,";
		$sql .= "c.ref as fromref,";
		$sql .= "soc.nom as customer_name,";
		$sql .= "soc.rowid as customer_id";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire_certif as certif";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as s ON certif.fk_session_agefodd=s.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c ON c.rowid = s.fk_formation_catalogue";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid = certif.fk_stagiaire";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as stasess ON sta.rowid = stasess.fk_stagiaire AND stasess.fk_session_agefodd=s.rowid AND certif.fk_session_stagiaire=stasess.rowid";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "societe as soc ON soc.rowid = sta.fk_soc";

		$sql .= " WHERE s.entity IN (" . getEntity('agefodd' /*agsession*/
		) . ")";
		$sql .= " AND sta.rowid NOT IN (SELECT stasessinner.fk_stagiaire FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as stasessinner INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as sinner ON sinner.rowid=stasessinner.fk_session_agefodd ";
		$sql .= " AND stasessinner.status_in_session IN (2,3) WHERE sinner.dated<certif.certif_dt_end AND sinner.fk_formation_catalogue=s.fk_formation_catalogue AND sinner.entity IN (" . getEntity('agefodd' /*agsession*/
		) . "))";
		$sql .= ' GROUP BY c.intitule,c.ref,soc.rowid ';
		if ($this->db->type == 'pgsql') {
			$sql .= " HAVING MAX(certif.certif_dt_end) < ( NOW() + INTERVAL '" . $month_expiration . " MONTHS') ";
		} else {
			$sql .= " HAVING MAX(certif.certif_dt_end) < ( NOW() + INTERVAL " . $month_expiration . " MONTH) ";
		}

		dol_syslog(get_class($this) . "::fetch_certif_expire", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new Agefodd_CertifExpireSoc_line();

				$line->fromintitule = $obj->fromintitule;
				$line->fromref = $obj->fromref;
				$line->customer_name = $obj->customer_name;
				$line->customer_id = $obj->customer_id;

				$this->lines[$i] = $line;

				$i ++;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_certif_expire " . $this->error, LOG_ERR);
			return - 1;
		}
	}
}
