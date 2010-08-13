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
class Agefodd_sesscalendar
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
	function Agefodd_sesscalendar($DB) 
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
		$this->fk_formation_catalogue = trim($this->nom);
		$this->fk_session_place = trim($this->prenom);
		$this->fk_agefodd_formateur = addslashes(trim($this->fonction));
		$this->dated = addslashes(trim($this->dated));
		$this->datef = addslashes(trim($this->datef));
		$this->notes = addslashes(trim($this->notes));
		$this->fk_user_author = addslashes(trim($this->mail));
		
		// Check parameters
		// Put here code to add control on parameters value
		
		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_session_calendrier(";
		$sql.= "fk_agefodd_session, date, heured, heuref, fk_user_author, datec";
		$sql.= ") VALUES (";
		$sql.= '"'.$this->sessid.'", ';
		$sql.= '"'.$this->date.'", ';
		$sql.= '"'.$this->heured.'", ';
		$sql.= '"'.$this->heuref.'", ';
		$sql.= '"'.$user.'", ';
		$sql.= '"'.$this->datec.'"';
		$sql.= ")";
	
		$this->db->begin();
		
		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		if (! $error)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."agefodd_formation_catalogue");
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
	*    \brief	Load object in memory from database
	*    \param	id	id object
	*			arch	archive (0=no, 1=yes, 2=all)
	*    \return     int         <0 if KO, >0 if OK
	*/
	function fetch($id)
	{
		global $langs;
					
		$sql = "SELECT";
		$sql.= " s.rowid, s.date, s.heured, s.heuref";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_calendrier as s";
		$sql.= " WHERE s.fk_agefodd_session = ".$id;
		$sql.= " ORDER BY s.date ASC, s.heured ASC";
		$sql.= " WHERE s.rowid = ".$id;
		
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->date = $obj->date;
				$this->heured = $obj->heured;
				$this->heuref = $obj->heuref;
				$this->sessid = $obj->sessid;
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
	*    \brief	Récupére le calendrier d'une session (les blocs horaires)
	*    \param	id	id session
	*			arch	archive (0=no, 1=yes, 2=all)
	*    \return     int     <0 if KO, >0 if OK
	*/
	function fetch_all($id)
	{
		global $langs;
					
		$sql = "SELECT";
		$sql.= " s.rowid, s.date, s.heured, s.heuref";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_calendrier as s";
		$sql.= " WHERE s.fk_agefodd_session = ".$id;
		$sql.= " ORDER BY s.date ASC, s.heured ASC";

		
		dol_syslog(get_class($this)."::fetch_all sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->line = array();
			$num = $this->db->num_rows($resql);
			$i = 0;
        	    	for ($i=0; $i < $num; $i++)
			{
				$obj = $this->db->fetch_object($resql);
				$this->line[$i]->id = $obj->rowid;
				$this->line[$i]->date = $obj->date;
				$this->line[$i]->heured = $obj->heured;
				$this->line[$i]->heuref = $obj->heuref;
				$this->line[$i]->sessid = $obj->sessid;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_all ".$this->error, LOG_ERR);
			return -1;
		}
	}



	/**
	*    \brief      Load info object in memory from database
	*    \param      id          id object
	*    \return     int         <0 if KO, >0 if OK
	*/
	function info($id)
	{
		global $langs;
		
		$sql = "SELECT";
		$sql.= " s.rowid, s.datec, s.tms, s.fk_user_author, s.fk_user_mod";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_calendrier as s";
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
	*      \brief      Update database
	*      \param      user        	User that modify
	*      \param      notrigger	0=launch triggers after, 1=disable triggers
	*      \return     int         	<0 if KO, >0 if OK
	*/
	function update($user=0, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;
		
		// Clean parameters
		$this->fk_session_place = ebi_mysql_escape_string(trim($this->fk_session_place));
		$this->notes = ebi_mysql_escape_string(trim($this->notes));
		
		// Check parameters
		// Put here code to add control on parameters values
		
		
		// Update request
		if (!isset($this->archive)) $this->archive = 0; 
		$sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_session_calendrier as s SET";
		$sql.= " s.date='".$this->date."',";
		$sql.= " s.heured='".$this->heured."',";
		$sql.= " s.heuref='".$this->heuref."',";
		$sql.= " s.fk_user_mod='".$user."'";
		$sql.= " WHERE s.rowid = ".$this->id;
		
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
	*      \brief      Supprime l'operation
	*      \param      id          Id operation à supprimer
	*      \return     int         <0 si ko, >0 si ok
	*/
	function remove($id)
	{
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_session_calendrier";
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


}

# $Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $
?>
