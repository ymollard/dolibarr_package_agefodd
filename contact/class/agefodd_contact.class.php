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
 *	\class		Agefodd
 *	\brief		Module Agefodd class
 */
class Agefodd_contact extends CommonObject
{
	var $db;
	var $error;
	var $errors=array();
	var $element='agefodd';
	var $table_element='agefodd_contact';
    var $id;

	/**
	*	\brief		Constructor
	*	\param		DB	Database handler
	*/
	function Agefodd_contact($DB) 
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
	
	
		// Check parameters
		// Put here code to add control on parameters value
		
		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_contact(";
		$sql.= "fk_socpeople, fk_user_author, datec";
		$sql.= ") VALUES (";
		$sql.= '"'.$this->spid.'", ';
		$sql.= '"'.$user.'",';
		$sql.= $this->db->idate(dol_now());
		$sql.= ")";
	
		$this->db->begin();
		
		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		if (! $error)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."agefodd_contact");
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
	*    \param	int     societe rowid
	*    \return    int     <0 if KO, 1 if OK
	*/
	function fetch($id, $type='socid')
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " c.rowid, c.fk_socpeople,";
		$sql.= " s.rowid as spid , s.name, s.firstname, s.civilite, s.address, s.cp, s.ville, c.archive";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_contact as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as s ON c.fk_socpeople = s.rowid";
		($type == 'socid') ? $sql.= " WHERE s.fk_soc = ".$id : $sql.= " WHERE c.rowid = ".$id;
		
		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->ref = $obj->rowid; // Use for next prev ref
				$this->spid = $obj->spid;
				$this->name = $obj->name;
				$this->firstname = $obj->firstname;
				$this->civilite = $obj->civilite;
				$this->address = $obj->address;
				$this->cp = $obj->cp;
				$this->ville = $obj->ville;
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
	*    \brief	Load object in memory from database
	*    \param	$sortorder	Load object in memory from database
	*		$sortfield
	*		$limit
	*		$offset
	*		$arch 	int (0 for only active record, 1 for only archive record)
	*    \return    int     <0 if KO, $num of teacher if OK
	*/
	function fetch_all($sortorder, $sortfield, $limit='', $offset, $arch=0)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " c.rowid, c.fk_socpeople,";
		$sql.= " s.rowid as spid , s.name, s.firstname, s.civilite, s.phone, s.email, s.phone_mobile,";
		$sql.= " soc.nom as socname, soc.rowid as socid, c.archive";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_contact as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as s ON c.fk_socpeople = s.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = s.fk_soc";
		if ($arch == 0 || $arch == 1) $sql.= " WHERE c.archive LIKE ".$arch;
		$sql.= " ORDER BY ".$sortfield." ".$sortorder." ";
		if (!empty($limit)) { $sql.=$this->db->plimit( $limit + 1 ,$offset);}
		
		dol_syslog(get_class($this)."::fetch_all sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->line = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num)
			{
				while( $i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$this->line[$i]->id = $obj->rowid;
					$this->line[$i]->ref = $obj->rowid; // Use for next prev ref
					$this->line[$i]->spid = $obj->spid;
					$this->line[$i]->socid = $obj->socid;
					$this->line[$i]->socname = $obj->socname;
					$this->line[$i]->name = $obj->name;
					$this->line[$i]->firstname = $obj->firstname;
					$this->line[$i]->civilite = $obj->civilite;
					$this->line[$i]->phone = $obj->phone;
					$this->line[$i]->email = $obj->email;
					$this->line[$i]->phone_mobile = $obj->phone_mobile;
					$this->line[$i]->fk_socpeople = $obj->fk_socpeople;
					$this->line[$i]->archive = $obj->archive;
					$i++;
				}
			}
			$this->db->free($resql);
			return $num;
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
		$sql.= " c.rowid, c.datec, c.tms, c.fk_user_mod, c.fk_user_author";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_contact as c";
		$sql.= " WHERE c.rowid = ".$id;
		
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
			$this->user_modification = $obj->fk_user_mod;
			$this->user_creation = $obj->fk_user_author;
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
	
	
	// Check parameters
	// Put here code to add control on parameters values
	
	// Update request
	if (!isset($this->archive)) $this->archive = 0; 
	$sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_contact as c SET";
	$sql.= " c.fk_user_mod='".$user."',";
	$sql.= " c.archive='".$this->archive."'";
	$sql.= " WHERE c.rowid = ".$this->id;
	
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
	*      \brief      Supprime le contact
	*      \param      id	int	Id operation à supprimer
	*      \return     int         <0 si ko, >0 si ok
	*/
	function remove($id)
	{
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_contact";
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
