<?php
/* Copyright (C) 2007-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
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
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

/**
 *	\file		$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/agsession.class.php $
 *	\ingroup	agefodd
 *	\brief		CRUD class file (Create/Read/Update/Delete) for agefodd module
*	\version	$Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 *	\class		Agefodd
 *	\brief		Module Agefodd class
*/
class Agefodd_index
{
	var $db;
	var $error;
	var $errors=array();
	var $element='agefodd';
	var $table_element='agefodd';
	var $id;

	/**
	 *	\brief		Constructor
	 *	\param		DB	Database handler
	 */
	function Agefodd_index($DB)
	{
		$this->db = $DB;
		return 1;
	}



	/**
	 *    \brief	Load object in memory from database
	 *    \return    int     <0 if KO, $num of student if OK
	 */
	function fetch_student_nb()
	{
		global $langs;

		$sql = "SELECT DISTINCT";
		$sql.= " s.fk_stagiaire,";
		$sql.= " se.archive";
		$sql.= " FROM  ".MAIN_DB_PREFIX."agefodd_session_stagiaire as s";
		$sql.= " LEFT JOIN  ".MAIN_DB_PREFIX."agefodd_session as se";
		$sql.= " ON se.rowid = s.fk_session_agefodd";
		$sql.= " WHERE se.archive LIKE 1";

		dol_syslog(get_class($this)."::fetch_student_nb sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num == '') $num = 0;
			$this->db->free($resql);
			return $num;

			//return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_student_nb ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *    \brief	Load object in memory from database
	 *    \return    int     <0 if KO, 1 if OK
	 */
	function fetch_session_nb()
	{
		global $langs;

		$sql = "SELECT count(*) as num";
		$sql.= " FROM  ".MAIN_DB_PREFIX."agefodd_session";
		$sql.= " WHERE archive LIKE 1";
		$sql.= " AND entity IN (".getEntity('agsession').")";


		dol_syslog(get_class($this)."::fetch_session_nb sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->num = $obj->num;
			}
			else $this->num = 0;

			$this->db->free($resql);
			return 1;

		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_session_nb ".$this->error, LOG_ERR);
			return -1;
		}
	}



	/**
	 *    \brief	Load object in memory from database
	 *    \return    int     <0 if KO, 1 if OK
	 */
	function fetch_formation_nb()
	{
		global $langs;

		$sql = "SELECT count(*) as num";
		$sql.= " FROM  ".MAIN_DB_PREFIX."agefodd_formation_catalogue";
		$sql.= " WHERE archive LIKE 0";
		$sql.= " AND entity IN (".getEntity('agsession').")";

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->num = $obj->num;
			}
			if ($obj->num == '') $this->num = 0;
			$this->db->free($resql);
			return 1;

		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *    \brief	Load object in memory from database
	 *    \return    int     <0 if KO, 1 if OK
	 */
	function fetch_heures_sessions_nb()
	{
		global $langs;

		$sql = "SELECT  sum(f.duree) AS total";
		$sql.= " FROM  ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formation_catalogue AS f";
		$sql.= " ON s.fk_formation_catalogue = f.rowid";
		$sql.= " WHERE s.archive LIKE 1";
		$sql.= " AND s.entity IN (".getEntity('agsession').")";
		//$sql.= " GROUP BY f.duree";

		dol_syslog(get_class($this)."::fetch_heures_sessions_nb sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->total = $obj->total;
			}
			if ($obj->total == '') $this->total = 0;
			$this->db->free($resql);
			return 1;

		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_heures_sessions_nb ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *    \brief	Load object in memory from database
	 *    \return    int     <0 if KO, 1 if OK
	 */
	function fetch_heures_stagiaires_nb()
	{
		global $langs;

		$sql = "SELECT  sum(f.duree) AS total";
		$sql.= " FROM  ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."agefodd_session_stagiaire AS ss";
		$sql.= " ON ss.fk_session_agefodd = s.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formation_catalogue AS f";
		$sql.= " ON s.fk_formation_catalogue = f.rowid";
		$sql.= " WHERE s.archive LIKE 1";
		$sql.= " AND s.entity IN (".getEntity('agsession').")";
		//$sql.= " GROUP BY f.duree";

		dol_syslog(get_class($this)."::fetch_heures_stagiaires_nb sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->total = $obj->total;
			}
			if ($obj->total == '') $this->total = 0;
			$this->db->free($resql);
			return 1;

		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_heures_stagiaires_nb ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *    \brief	Load object in memory from database
	 *    \return    int     <0 if KO, 1 if OK
	 */
	function fetch_last_formations($number=5)
	{
		global $langs;

		$sql = "SELECT c.intitule, s.dated, s.datef, s.fk_formation_catalogue, s.rowid as id";
		$sql.= " FROM  ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c";
		$sql.= " ON c.rowid = s.fk_formation_catalogue";
		$sql.= " WHERE s.archive LIKE 1";
		$sql.= " AND entity IN (".getEntity('agsession').")";
		$sql.= " ORDER BY s.dated DESC LIMIT ".$number;

		dol_syslog(get_class($this)."::fetch_last_formations sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->line = array();
			$num = $this->db->num_rows($resql);
			$i = 0;
			while( $i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$this->line[$i]->intitule = $obj->intitule;
				$this->line[$i]->dated = $this->db->idate($obj->dated);
				$this->line[$i]->datef = $this->db->idate($obj->datef);
				$this->line[$i]->idforma = $obj->fk_formation_catalogue;
				$this->line[$i]->id = $obj->id;
				$i++;
			}
			$this->db->free($resql);
			return 1;

		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_last_formations ".$this->error, LOG_ERR);
			return -1;
		}
	}



	/**
	 *    \brief	Load object in memory from database
	 *    \return    int     <0 if KO, 1 if OK
	 */
	function fetch_top_formations($number=5)
	{
		global $langs;

		$sql = "SELECT c.intitule, count(*) as num, c.duree, ";
		$sql.= " s.fk_formation_catalogue";
		$sql.= " FROM  ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c";
		$sql.= " ON c.rowid = s.fk_formation_catalogue";
		$sql.= " WHERE s.archive LIKE 1";
		$sql.= " AND s.entity IN (".getEntity('agsession').")";
		$sql.= " GROUP BY c.intitule";
		$sql.= " ORDER BY num DESC LIMIT ".$number;

		dol_syslog(get_class($this)."::fetch_top_formations sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->line = array();
			$num = $this->db->num_rows($resql);
			$i = 0;
			while( $i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$this->line[$i]->intitule = $obj->intitule;
				$this->line[$i]->num = $obj->num;
				$this->line[$i]->duree = $obj->duree;
				$this->line[$i]->idforma = $obj->fk_formation_catalogue;
				$i++;
			}
			$this->db->free($resql);
			return 1;

		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_top_formations ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *    \brief	Load object in memory from database
	 *    \return    int     <0 if KO, 1 if OK
	 */
	function fetch_session($archive=0)
	{
		global $langs;

		$sql = "SELECT count(*) as total";
		$sql.= " FROM  ".MAIN_DB_PREFIX."agefodd_session";
		$sql.= " WHERE archive LIKE ".$archive;
		$sql.= " AND entity IN (".getEntity('agsession').")";

		dol_syslog(get_class($this)."::fetch_session sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->total = $obj->total;
			}
			$this->db->free($resql);
			return 1;

		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_session ".$this->error, LOG_ERR);
			return -1;
		}
	}



	/**
	 *    \brief	Load object in memory from database
	 *    \param     int     day of overshoot
	 *    \return    int     <0 if KO, 1 if OK
	 */
	function fetch_tache_en_retard($jour=0)
	{
		global $langs;

		$sql = "SELECT rowid,fk_agefodd_session";
		$sql.= " FROM  ".MAIN_DB_PREFIX."agefodd_session_adminsitu";
		$sql.= " WHERE (datea - INTERVAL ".$jour." DAY) <= NOW() AND archive LIKE 0 AND (NOW() < datef)";
		$sql.= " AND entity IN (".getEntity('agsession').")";

		dol_syslog(get_class($this)."::fetch_tache_en_retard sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$this->line = array();
				$num = $this->db->num_rows($resql);
				$i = 0;

				while( $i < $num)
				{
					$obj = $this->db->fetch_object($resql);

					$this->line[$i]->rowid = $obj->rowid;
					$this->line[$i]->sessid = $obj->fk_agefodd_session;

					$i++;
				}
			}
			$this->db->free($resql);
			return 1;

		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_tache_en_retard ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *    \brief	Load object in memory from database
	 *    \return    int     <0 if KO, 1 if OK
	 */
	function fetch_tache_en_cours()
	{
		global $langs;

		$sql = "SELECT count(*) as total";
		$sql.= " FROM  ".MAIN_DB_PREFIX."agefodd_session_adminsitu";
		$sql.= " WHERE archive LIKE 0";
		$sql.= " AND entity IN (".getEntity('agsession').")";

		dol_syslog(get_class($this)."::fetch_tache_en_cours sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->total = $obj->total;
			}
			$this->db->free($resql);
			return 1;

		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_tache_en_cours ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *    \brief	Load object in memory from database
	 *    \param	id	id admin action (in table agefodd_session_adminsitu)
	 *    \return    int     <0 if KO, >0 if OK
	 */
	function fetch_session_per_dateLimit($sortorder, $sortfield, $limit, $offset, $delais_sup, $delais_inf=0)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " s.rowid, s.fk_agefodd_session_admlevel, s.fk_agefodd_session, s.intitule,";
		$sql.= " s.delais_alerte, s.indice, s.dated, s.datef, s.datea, s.notes,";
		$sql.= " sess.dated as sessdated, sess.datef as sessdatef,";
		$sql.= " f.intitule as titre";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session as sess";
		$sql.= " ON s.fk_agefodd_session = sess.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formation_catalogue as f";
		$sql.= " ON sess.fk_formation_catalogue = f.rowid";
		$sql.= " WHERE s.archive LIKE 0 AND (NOW() < s.datef)";
		if ( !empty($delais_sup) && !empty($delais_inf) )
		{
			if ($delais_sup!=1) $delais_sup_sql= 's.datea - INTERVAL '.$delais_sup.' DAY';
			else $delais_sup_sql='s.datea';

			if ($delais_inf!=1) $delais_inf_sql= 's.datea - INTERVAL '.$delais_inf.' DAY';
			else $delais_inf_sql='s.datea';

			$sql.= " AND  ( ";
			$sql.= ' NOW() BETWEEN ('.$delais_sup_sql.') AND ('.$delais_inf_sql.')';
			$sql.= " )";
		}
		$sql.= " AND s.entity IN (".getEntity('agsession').")";

		$sql.= " ORDER BY ".$sortfield." ".$sortorder." ".$this->db->plimit( $limit + 1 ,$offset);

		dol_syslog(get_class($this)."::fetch_session_per_dateLimit sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->line = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			while( $i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$this->line[$i]->rowid = $obj->rowid;
				$this->line[$i]->sessid = $obj->fk_agefodd_session;

				$i++;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *    \brief	Load object in memory from database
	 *    \return    int     <0 if KO, 1 if OK
	 */
	function fetch_session_to_archive()
	{
		global $langs;

		// Il faut que toutes les tâches administratives soit crées (top_level);
		$sql = "SELECT MAX(sa.datea), sa.rowid, sa.fk_agefodd_session";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as sa";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " ON s.rowid = sa.fk_agefodd_session";
		$sql.= " WHERE sa.archive LIKE 1";
		$sql.= " AND sa.level_rank=0";
		$sql.= " AND s.archive LIKE 0";
		$sql.= " AND s.entity IN (".getEntity('agsession').")";
		$sql.= " GROUP BY sa.fk_agefodd_session";

		dol_syslog(get_class($this)."::fetch_session_to_archive sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				$obj = $this->db->fetch_object($resql);
				$this->sessid = $obj->fk_agefodd_session;
			}

			$this->db->free($resql);

			return $num;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_session_to_archive ".$this->error, LOG_ERR);
			return -1;
		}
	}


}
# $Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $
?>
