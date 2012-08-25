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
 *	\version	$Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 *	\class		Agefodd_sessadm
 *	\brief		Module Agefodd Session administration class
 */
class Agefodd_sessadm extends CommonObject
{
	var $db;
	var $error;
	var $errors=array();
	var $element='agefodd';
	var $table_element='agefodd_session_adminsitu';
    var $id;

    var $level_rank;
    var $fk_parent_level;
    var $indice;
    var $intitule;
    var $delais_alerte;
    var $fk_user_author;
    var $datec='';
    var $fk_user_mod;
    var $tms='';
    var $line;
    
    
    /**
     *	\brief		Constructor
     *	\param		DB	Database handler
     */
    function __construct($DB) 
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
		$this->intitule = $this->db->escape(trim($this->intitule));
		$this->indice = trim($this->indice);
		$this->notes = $this->db->escape(trim($this->notes));
		$this->fk_user_author = trim($this->fk_user_author);
				
		// Check parameters
		// Put here code to add control on parameters value
		
		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_session_adminsitu (";
		$sql.= "fk_agefodd_session_admlevel, fk_agefodd_session, intitule, delais_alerte, ";
		$sql.= "indice, level_rank, fk_parent_level, dated, datef, datea, notes,archive,fk_user_author, datec";
		$sql.= ") VALUES (";
		$sql.= '"'.$this->fk_agefodd_session_admlevel.'", ';
		$sql.= '"'.$this->fk_agefodd_session.'", ';
		$sql.= '"'.$this->intitule.'", ';
		$sql.= '"'.$this->delais_alerte.'", ';
		$sql.= '"'.$this->indice.'", ';
		$sql.= '"'.$this->level_rank.'", ';
		$sql.= '"'.$this->fk_parent_level.'", ';
		$sql.= $this->db->idate($this->dated).', ';
		$sql.= $this->db->idate($this->datef).', ';
		$sql.= $this->db->idate($this->datea).', ';
		$sql.= '"'.$this->notes.'",';
		$sql.= $this->archive.',';
		$sql.= '"'.$user->id.'", ';
		$sql.= $this->db->idate(dol_now());
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
	 *  Load object in memory from database
	 *
	 *  @param	int		$user        User id that modify
	 *  @param	int		$notrigger   0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 <0 if KO, >0 if OK
	 */
	function update($user, $notrigger=0)
	{
		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		//$this->delais_alerte = trim($this->delais_alerte);
		$this->dated = trim($this->dated);
		$this->datef = trim($this->datef);
		$this->datea = trim($this->datea);
		$this->notes = $this->db->escape(trim($this->notes));
	
		// Check parameters
		// Put here code to add control on parameters values
	
	
		// Update request
		if (!isset($this->archive)) $this->archive = 0; 
		$sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_session_adminsitu as s SET";
		$sql.= " s.delais_alerte='".$this->delais_alerte."',";
		$sql.= " s.dated=".$this->db->idate($this->dated).",";
		$sql.= " s.datef=".$this->db->idate($this->datef).",";
		$sql.= " s.datea=".$this->db->idate($this->datea).",";
		$sql.= " s.fk_user_mod='".$user->id."',";
		$sql.= " s.notes='".$this->notes."',";
		$sql.= " s.archive='".$this->archive."',";
		$sql.= " s.level_rank='".$this->level_rank."',";
		$sql.= " s.fk_parent_level='".$this->fk_parent_level."',";
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
	 *  Load object in memory from database
	 *
	 *  @param	int		$id        Admin action (in table agefodd_session_adminsitu)
	 *  @return int     		   	 <0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		global $langs;

        $sql = "SELECT";
        $sql.= " s.rowid, s.fk_agefodd_session_admlevel, s.fk_agefodd_session, s.intitule,";
        $sql.= " s.level_rank, s.fk_parent_level, s.indice, s.dated, s.datea, s.datef, s.notes, s.delais_alerte, s.archive";
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
				$this->level_rank = $obj->level_rank;
				$this->fk_parent_level = $obj->fk_parent_level;
				$this->delais_alerte = $obj->delais_alerte;
				$this->dated = $this->db->jdate($obj->dated);
				$this->datef = $this->db->jdate($obj->datef);
				$this->datea = $this->db->jdate($obj->datea);
				$this->notes = $obj->notes;
				$this->archive = $obj->archive;
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
     *  Load action into memory per session
     *
     *  @param	int		$sess_id        Session Id
     *  @return int     		   	 <0 if KO, >0 if OK
     */
	function fetch_all($sess_id)
	{
		global $langs;

        $sql = "SELECT";
        $sql.= " s.rowid, s.fk_agefodd_session_admlevel, s.fk_agefodd_session, s.intitule,";
        $sql.= " s.level_rank, s.fk_parent_level, s.indice, s.dated, s.datea, s.datef, s.notes, s.delais_alerte, s.archive";
        $sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as s";
        $sql.= " WHERE s.fk_agefodd_session = ".$sess_id;
        $sql.= " ORDER BY s.indice";
        
		dol_syslog(get_class($this)."::fetch_all sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->line = array();
			$num = $this->db->num_rows($resql);
			$i = 0;
			
			while( $i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$this->line[$i]->id = $obj->rowid;
				$this->line[$i]->level = $obj->fk_agefodd_session_admlevel;
				$this->line[$i]->sessid = $obj->fk_agefodd_session;
				$this->line[$i]->intitule = $obj->intitule;
				$this->line[$i]->indice = $obj->indice;
				$this->line[$i]->level_rank = $obj->level_rank;
				$this->line[$i]->fk_parent_level = $obj->fk_parent_level;
				$this->line[$i]->delais_alerte = $obj->delais_alerte;
				$this->line[$i]->dated = $this->db->jdate($obj->dated);
				$this->line[$i]->datef = $this->db->jdate($obj->datef);
				$this->line[$i]->datea = $this->db->jdate($obj->datea);
				$this->line[$i]->notes = $obj->notes;
				$this->line[$i]->archive = $obj->archive;
				
				$i++;
			}
			$this->db->free($resql);
			return $num;
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
			$this->date_creation = $this->db->jdate($obj->datec);
			$this->date_modification = $this->db->jdate($obj->tms);
			$this->user_creation = $obj->fk_user_author;
			$this->user_modification = $obj->fk_user_mod;
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
				$this->dated = $this->db->jdate($obj->dated);
				$this->datef = $this->db->jdate($obj->datef);
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
	 *  After a creation set the good parent id for action session
	 *
	 *  @param	$user	int	       		 User id that modify
	 *  @param $session_id	int			 the session to update
	 *  @param  $notrigger int			 0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 	 <0 if KO, >0 if OK
	 */
	function setParentActionId($user, $session_id)
	{
		global $conf, $langs;
		$error = 0;
	
		// Update request
				
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'agefodd_session_adminsitu as ori,'.MAIN_DB_PREFIX.'agefodd_session_adminsitu as upd ';
		$sql.= ' SET upd.fk_parent_level=ori.rowid ';
		$sql.= ' WHERE upd.fk_parent_level=ori.fk_agefodd_session_admlevel AND upd.level_rank<>0 AND upd.fk_agefodd_session=ori.fk_agefodd_session';
		$sql.= ' AND upd.fk_agefodd_session='.$session_id;
	
		//print $sql;
		//exit;
		$this->db->begin();
	
		dol_syslog(get_class($this)."::setParentActionId sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		
		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::setParentActionId ".$errmsg, LOG_ERR);
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

}
# $Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $
?>
