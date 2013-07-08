<?php
/* Copyright (C) 2007-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
* Copyright (C) 2013	Florian Henry		<florian.henry@open-concept.pro>
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
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

/**
 *  \file       agefodd/class/agefodd_facture.class.php
 *  \ingroup    agefodd
*  \brief      Manage Invoice object
*/

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 *	Invoice Class
*/
class Agefodd_facture
{
	var $db;
	var $error;
	var $errors=array();
	var $element='agefodd';
	var $table_element='agefodd';
	
	var $id;
	
	var $sessid;
	var $socid;
	var $facid;
	var $facnumber;
	var $comid;
	var $comref;

	var $lines=array();

	/**
	 *  Constructor
	 *
	 *  @param	DoliDb		$db      Database handler
	 */
	function __construct($DB)
	{
		$this->db = $DB;
		return 1;
	}


	/**
	 *  Create object into database
	 *
	 *  @param	User	$user        User that create
	 *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
	 *  @return int      		   	 <0 if KO, Id of created object if OK
	 */
	function create($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;
			
		// Clean parameters

		// Check parameters
		// Put here code to add control on parameters value

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_facture(";
		$sql.= "fk_commande, fk_facture, fk_session, fk_societe, fk_user_author,fk_user_mod, datec";
		$sql.= ") VALUES (";
		$sql.= " ".(empty($this->comid)?'NULL':$this->comid).", ";
		$sql.= " ".(empty($this->facid)?'NULL':$this->facid).", ";
		$sql.= " ".$this->sessid.", ";
		$sql.= " ".$this->socid.", ";
		$sql.= " ".$user->id.', ';
		$sql.= " ".$user->id.', ';
		$sql.= "'".$this->db->idate(dol_now())."'";
		$sql.= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (! $resql) {
			$error++; $this->errors[]="Error ".$this->db->lasterror();
		}
		if (! $error)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."agefodd_facture");
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
	 *  @param	int		$sessid    Id Session
	 *  @param	int		$socid    Id soc
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function fetch($sessid, $socid)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " f.rowid, f.fk_session, f.fk_societe, f.fk_facture,";
		$sql.= " fa.rowid as facid, fa.facnumber,";
		$sql.= " co.rowid as comid, co.ref as comref";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_facture as f";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fa";
		$sql.= " ON fa.rowid=f.fk_facture";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as co";
		$sql.= " ON co.rowid=f.fk_commande";
		$sql.= " WHERE f.fk_session = ".$sessid;
		$sql.= " AND f.fk_societe = ".$socid;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);

		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->sessid = $obj->fk_session;
				$this->socid = $obj->fk_societe;
				$this->facid = $obj->facid;
				$this->facnumber = $obj->facnumber;
				$this->comid = $obj->comid;
				$this->comref = $obj->comref;
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
	 *  Load object in memory from database
	 *
	 *  @param	int		$socid    Id Session
	 *  @param	string		$type    bc is default
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function fetch_fac_per_soc($socid, $type='bc')
	{
		global $langs;

		$sql = "SELECT";
		if ($type == 'bc')
		{
			$sql.= " c.rowid, c.fk_soc, c.ref";
			$sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
		}
		if ($type == 'fac')
		{
			$sql.= " f.rowid, f.fk_soc, f.facnumber";
			$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
		}
		$sql.= " WHERE fk_soc = ".$socid;

		dol_syslog(get_class($this)."::fetch_fac_per_soc sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->lines = array();
			$num = $this->db->num_rows($resql);
			$i = 0;
			for ($i=0; $i < $num; $i++)
			{
				$line = new Agefodd_facture_line();

				$obj = $this->db->fetch_object($resql);
				$line->id = $obj->rowid;
				$line->socid = $obj->fk_soc;
				($type == 'bc') ? $line->ref = $obj->ref : $line->ref = $obj->facnumber;

				$this->lines[$i]=$line;
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
	 *  Give information on the object
	 *
	 *  @param	int		$id    Id object
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function info($id)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " f.rowid, f.datec, f.tms, f.fk_user_author, f.fk_user_mod";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_facture as f";
		$sql.= " WHERE f.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->date_creation = $obj->datec;
				$this->tms = $obj->tms;
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
	 *  Update object into database
	 *
	 *  @param	User	$user        User that modify
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 <0 if KO, >0 if OK
	 */
	function update($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		// Clean parameters


		// Check parameters
		// Put here code to add control on parameters values


		// Update request
		if (!isset($this->archive)) $this->archive = 0;
		$sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_facture SET";
		$sql.= " fk_commande=".(empty($this->comid)?'NULL':$this->comid).",";
		$sql.= " fk_facture=".(empty($this->facid)?'NULL':$this->facid).",";
		$sql.= " fk_societe=".$this->socid.",";
		$sql.= " fk_user_mod=".$user->id;
		$sql.= " WHERE rowid = ".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error++; $this->errors[]="Error ".$this->db->lasterror();
		}
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
	 *  Delete object in database
	 *
	 *	@param  int 	$id		object to delete
	 *  @return	 int		<0 if KO, >0 if OK
	 */
	function remove($id)
	{
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_facture";
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

Class Agefodd_facture_line {

	var $id;
	var $socid;
	var $ref;

	function __construct()
	{
		return 1;
	}
}