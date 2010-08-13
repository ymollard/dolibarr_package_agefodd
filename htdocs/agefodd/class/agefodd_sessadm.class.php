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
 *	\file		$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/agefodd_session.class.php $
 *	\ingroup	agefodd
 *	\brief		CRUD class file (Create/Read/Update/Delete) for agefodd module
 *	\version	$Id: agefodd_session.class.php 54 2010-03-30 18:58:28Z ebullier $
 */

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 *	\class		Agefodd
 *	\brief		Module Agefodd class
 */
class Agefodd_sessadm
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
    function Agefodd_sessadm($DB) 
    {
        $this->db = $DB;
        return 1;
    }


	/**
	*      \brief      Create in database
	*      \param      user        	User that create
	*      \param      notrigger	0=launch triggers after, 1=disable triggers
	*      \return     int         	<0 if KO, Id of created object if OK
	*/
	function create($user, $notrigger=0)
	{
    		global $conf, $langs;
		$error=0;
    	
		// Clean parameters
		$this->fk_agefodd_session_admlevel = trim($this->fk_agefodd_session_admlevel);
		$this->fk_agefodd_session = trim($this->fk_agefodd_session);
		$this->intitule = ebi_mysql_escape_string(trim($this->intitule));
		$this->indice = trim($this->indice);
		$this->notes = ebi_mysql_escape_string(trim($this->notes));
		$this->fk_user_mod = ebi_mysql_escape_string($this->fk_user_mod);
				
		// Check parameters
		// Put here code to add control on parameters value
		
		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_session_adminsitu (";
		$sql.= "fk_agefodd_session_admlevel, fk_agefodd_session, intitule, delais_alerte, ";
		$sql.= "indice,top_level, dated, datea, notes,fk_user_mod";
		$sql.= ") VALUES (";
		$sql.= '"'.$this->fk_agefodd_session_admlevel.'", ';
		$sql.= '"'.$this->fk_agefodd_session.'", ';
		$sql.= '"'.$this->intitule.'", ';
		$sql.= '"'.$this->delais_alerte.'", ';
		$sql.= '"'.$this->indice.'", ';
		$sql.= '"'.$this->top_level.'", ';
		$sql.= '"'.$this->dated.'", ';
		$sql.= '"'.$this->datea.'", ';
		$sql.= '"'.$this->notes.'",';
		$sql.= '"'.$user.'"';
		$sql.= ")";

		$this->db->begin();
		
	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
	   	$resql=$this->db->query($sql);
	   	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		if (! $error)
		{
		    $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."agefodd_session_adminsitu");
		    if (! $notrigger)
		    {
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.
	            
	            //// Call triggers
	            //include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
		    }
		}

		// Commit or rollback
    		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
			    dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
			    $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return $this->id;
		}
	}
	
	
	/**
	*      \brief      Update database
	*      \param      user        	User that modify
	*      \param      notrigger	0=launch triggers after, 1=disable triggers
	*      \return     int         	<0 if KO, >0 if OK
	*/
	function update($user=0, $notrigger=0)
	{
		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		//$this->delais_alerte = trim($this->delais_alerte);
		$this->dated = trim($this->dated);
		$this->datef = trim($this->datef);
		$this->datea = trim($this->datea);
		$this->fk_user_mod = trim($this->fk_user_mod);
		$this->note = ebi_mysql_escape_string(trim($this->note));
	
		
	
		// Check parameters
		// Put here code to add control on parameters values
	
	
		// Update request
		if (!isset($this->archive)) $this->archive = 0; 
		$sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_session_adminsitu as s SET";
		//$sql.= " s.delais_alerte='".$this->delais	."',";
		$sql.= " s.dated='".$this->dated."',";
		$sql.= " s.datef='".$this->datef."',";
		$sql.= " s.datea='".$this->datea."',";
		$sql.= " s.fk_user_mod='".$user."',";
		$sql.= " s.notes='".$this->notes."',";
		$sql.= " s.archive='".$this->archive."'";
		$sql.= " WHERE s.rowid = ".$this->id;
	
		//print $sql;
		//exit;
		$this->db->begin();
	
		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.
				
	            //// Call triggers
	            //include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
	    	}
		}
		
		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}	
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}		
	}



	/**
     	*    \brief	Load object in memory from database
     	*    \param	id	id admin action (in table agefodd_session_adminsitu)
	*    \return    int     <0 if KO, >0 if OK
	*/
	function fetch($id)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " s.rowid, s.fk_agefodd_session_admlevel, s.fk_agefodd_session, s.intitule,";
		$sql.= " s.top_level, s.indice, s.dated, s.datef, s.notes";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as s";
        	$sql.= " WHERE s.rowid = '".$id."'";
	
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->level = $obj->fk_agefodd_session_admlevel;
				$this->sessid = $obj->fk_agefodd_session;
				$this->intitule = $obj->intitule;
				$this->indice = $obj->indice;
				$this->top_level = $obj->top_level;
				$this->dated = $obj->dated;
				$this->datef = $obj->datef;
				$this->notes = $obj->notes;
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
	*    \brief      Load info object in memory from database (onglet suivi)
	*    \param      id          id object
	*    \return     int         <0 if KO, >0 if OK
	*/
	function info($id)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " s.rowid, s.datec, s.tms, s.fk_user_author, s.fk_user_mod";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " WHERE s.rowid = ".$id;
	
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
			$obj = $this->db->fetch_object($resql);
			$this->id = $obj->rowid;
			$this->datec = $obj->datec;
			$this->tms = $obj->tms;
			$this->fk_userc = $obj->fk_user_author;
			$this->fk_userm = $obj->fk_user_mod;
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
	*      \brief      Supprime l'operation
	*      \param      id          Id operation à supprimer
	*      \return     int         <0 si ko, >0 si ok
	*/
	function remove($id)
	{
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu";
		$sql .= " WHERE rowid = ".$id;
		
		dol_syslog(get_class($this)."::remove sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query ($sql);
		
		if ($resql)
		{
			return 1;
		}
		else
		{
		    $this->error=$this->db->lasterror();
		    return -1;
		}
        }


	/**
	*      \brief      Charge dans le tableau $line les différents niveaux administratifs
	*      \return     int         <0 si ko, count of elements if ok
	*/
	function get_admlevel_table()
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " s.rowid, s.top_level, s.indice, s.intitule, s.delais_alerte";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_admlevel as s";
		$sql.= " ORDER BY s.indice";
		
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
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
				$this->line[$i]->intitule = $obj->intitule;
				$this->line[$i]->alerte = $obj->delais_alerte;
				$this->line[$i]->indice = $obj->indice;
				$this->line[$i]->top_level = $obj->top_level;
				
				$i++;
			}
			$this->db->free($resql);
			return $num;
			//return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
        	}
	}

	/**
	*    \brief      Load info object in memory from database (onglet suivi)
	*    \param      id          id object
	*    \return     int         <0 if KO, >0 if OK
	*/
	function get_session_dated($sessid)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " s.dated, s.datef";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " WHERE s.rowid = ".$sessid;
	
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->dated = $obj->dated;
				$this->datef = $obj->datef;
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
     	*    \param	id	id admin action (in table agefodd_session_adminsitu)
	*    \return    int     <0 if KO, >0 if OK
	*/
	function fetch_admin_action_rens_from_id($id)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " s.rowid, s.fk_agefodd_session_admlevel, s.fk_agefodd_session, s.intitule,";
		$sql.= " s.top_level, s.indice, s.datea, s.dated, s.datef, s.notes, s.delais_alerte";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as s";
        	$sql.= " WHERE s.rowid = '".$id."'";
	
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->level = $obj->fk_agefodd_session_admlevel;
				$this->sessid = $obj->fk_agefodd_session;
				$this->intitule = $obj->intitule;
				$this->indice = $obj->indice;
				$this->top_level = $obj->top_level;
				$this->dated = $obj->dated;
				$this->datef = $obj->datef;
				$this->datea = $obj->datea;
				$this->notes = $obj->notes;
				$this->delais_alerte = $obj->delais_alerte;
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
	*    \param	id	id admin action (in table agefodd_session_adminsitu)
	*    \return    int     <0 if KO, >0 if OK
	*/
	function fetch_admin_action_rens($fk_agefodd_session_admlevel, $fk_agefodd_session)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " s.rowid, s.fk_agefodd_session_admlevel, s.fk_agefodd_session, s.intitule,";
		$sql.= " s.delais_alerte, s.top_level, s.indice, s.dated, s.datef, s.datea, s.notes,";
		$sql.= " sess.dated as sessdated, sess.datef as sessdatef";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session as sess";
		$sql.= " ON s.fk_agefodd_session = sess.rowid";
		$sql.= " WHERE s.fk_agefodd_session = '".$fk_agefodd_session."'";
		$sql.= " AND s.fk_agefodd_session_admlevel = '".$fk_agefodd_session_admlevel."'";
	
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->level = $obj->fk_agefodd_session_admlevel;
				$this->sessid = $obj->fk_agefodd_session;
				$this->intitule = $obj->intitule;
				$this->alerte = $obj->delais_alerte;
				$this->indice = $obj->indice;
				$this->top_level = $obj->top_level;
				$this->dated = $obj->dated;
				$this->datef = $obj->datef;
				$this->datea = $obj->datea;
				$this->notes = $obj->notes;
				$this->sessdated = $obj->sessdated;
				$this->sessdatef = $obj->sessdatef;
				
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
	*    \param	id	id admin action (in table agefodd_session_adminsitu)
	*    \return    int     <0 if KO, >0 if OK
	*/
	function fetch_adminlevel_infos($admlevel)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " a.rowid, a.indice, a.top_level, a.intitule, a.delais_alerte";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_admlevel as a";
		//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session as s";
		//$sql.= " ON s.fk_agefodd_session = a.rowid";
		$sql.= " WHERE a.rowid = '".$admlevel."'";
	
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->intitule = $obj->intitule;
				$this->alerte = $obj->delais_alerte;
				$this->indice = $obj->indice;
				$this->top_level = $obj->top_level;	
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
	*    \param	id	id admin action (in table agefodd_session_adminsitu)
	*    \return    int     <0 if KO, >0 if OK
	*/
	function fetch_session_per_dateLimit($sortorder, $sortfield, $limit, $offset, $delais_sup, $delais_inf=0)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " s.rowid, s.fk_agefodd_session_admlevel, s.fk_agefodd_session, s.intitule,";
		$sql.= " s.delais_alerte, s.top_level, s.indice, s.dated, s.datef, s.datea, s.notes,";
		$sql.= " sess.dated as sessdated, sess.datef as sessdatef,";
		$sql.= " f.intitule as titre";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session as sess";
		$sql.= " ON s.fk_agefodd_session = sess.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formation_catalogue as f";
		$sql.= " ON sess.fk_formation_catalogue = f.rowid";
		//$sql.= " WHERE (datea - INTERVAL ".$delais." DAY) < NOW()";
		$sql.= " WHERE s.archive LIKE 0";
		if ( !empty($delais_sup) && !empty($delais_inf) )
		{
			$sql.= " AND  ( ";
			$sql.= " NOW() BETWEEN ( s.datea - INTERVAL ".$delais_sup." DAY) AND (s.datea - INTERVAL ".$delais_inf."   DAY)";
			$sql.= " )";
		}
		$sql.= " ORDER BY ".$sortfield." ".$sortorder." ".$this->db->plimit( $limit + 1 ,$offset);
	
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
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
				$this->line[$i]->intitule = stripslashes($obj->intitule);
				$this->line[$i]->datea = $obj->datea;
				$this->line[$i]->titre = stripslashes($obj->titre);
				$this->line[$i]->sessdated = $obj->sessdated;
				$this->line[$i]->sessdatef = $obj->sessdatef;
			
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
	*    \param	id	id admin action (in table agefodd_session_adminsitu)
	*    \return    int     <0 if KO, >0 if OK
	*/
	function fetch_session_to_archive($sortorder, $sortfield, $limit, $offset)
	{
		global $langs;

		/*$sql = "SELECT";
		$sql.= " s.rowid, s.fk_agefodd_session_admlevel, s.fk_agefodd_session, s.intitule,";
		$sql.= " s.dated, s.datef, s.datea,";
		$sql.= " sess.dated as sessdated, sess.datef as sessdatef,";
		$sql.= " f.intitule as titre";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session as sess";
		$sql.= " ON s.fk_agefodd_session = sess.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formation_catalogue as f";
		$sql.= " ON sess.fk_formation_catalogue = f.rowid";
		$sql.= " WHERE s.archive LIKE 0";
		$sql.= " AND s.dated > '0000-00-00 00:00:00'";
		$sql.= " AND s.datef > '0000-00-00 00:00:00'";
		$sql.= " GROUP BY sess.rowid ";
		*/
		$sql = "SELECT";
		$sql.= " s.rowid, s.fk_agefodd_session, s.datef,";
		$sql.= " sess.dated as sessdated, sess.datef as sessdatef,";
		$sql.= " f.intitule as titre";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu AS s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session as sess";
		$sql.= " ON s.fk_agefodd_session = sess.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formation_catalogue as f";
		$sql.= " ON sess.fk_formation_catalogue = f.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_admlevel as a";
		$sql.= " ON a.rowid = s.fk_agefodd_session_admlevel";
		$sql.= " WHERE s.archive LIKE 0";
		$sql.= " AND a.top_level LIKE 'Y'";
		$sql.= " AND s.indice = MAX(a.indice)";
		$sql.= " AND s.datef > '0000-00-00 00:00:00'";
		$sql.= " GROUP BY s.fk_agefodd_session";
		$sql.= " ORDER BY ".$sortfield." ".$sortorder." ".$this->db->plimit( $limit + 1 ,$offset);
	
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
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
				$this->line[$i]->intitule = stripslashes($obj->intitule);
				$this->line[$i]->datea = $obj->datef;
				$this->line[$i]->titre = stripslashes($obj->titre);
			
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
	


}
# $Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $
?>
