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
 *	\file		$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/agefodd_formation_catalogue.class.php $
 *	\ingroup	agefodd
 *	\brief		CRUD class file (Create/Read/Update/Delete) for agefodd module
 *	\version	$Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 *	\class		Agefodd
 *	\brief		Module Agefodd class
 */
class Agefodd_splace
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
	function Agefodd_splace($DB) 
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
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_session_place(";
		$sql.= "code, adresse, cp, ville, pays, tel, fk_societe, notes, datec, fk_user_author";
		$sql.= ") VALUES (";
		$sql.= '"'.$this->code.'", ';
		$sql.= '"'.$this->adresse.'", ';
		$sql.= '"'.$this->cp.'", ';
		$sql.= '"'.$this->ville.'", ';
		$sql.= '"'.ebi_mysql_escape_string($this->pays).'",';
		$sql.= '"'.ebi_mysql_escape_string($this->tel).'",';
		$sql.= '"'.$this->fk_societe.'",';
		$sql.= '"'.ebi_mysql_escape_string($this->notes).'",';
		$sql.= '"'.$user.'",';
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
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;
    	
        $sql = "SELECT";
	$sql.= " p.rowid, p.code, p.adresse, p.cp, p.ville, p.pays, p.tel, p.fk_societe, p.notes, p.archive,";
	$sql.= " s.rowid as socid, s.nom as socname";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_place as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_societe = s.rowid";
        $sql.= " WHERE p.rowid = ".$id;
	//$sql.= " AND p.archive LIKE 0";
	
	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
		if ($this->db->num_rows($resql))
		{
			$obj = $this->db->fetch_object($resql);
			$this->id = $obj->rowid;
			$this->code =  stripslashes($obj->code);
			$this->adresse = stripslashes($obj->adresse);
			$this->cp = $obj->cp;
			$this->ville = stripslashes($obj->ville);
			$this->pays = stripslashes($obj->pays);
			$this->tel = stripslashes($obj->tel);
			$this->fk_societe = $obj->fk_societe;
			$this->notes = stripslashes($obj->notes);
			$this->socid = $obj->socid;
			$this->socname = stripslashes($obj->socname);
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
     *    \param	id	id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch_all($sortorder, $sortfield, $limit, $offset, $arch=0)
    {
    	global $langs;
    	
        $sql = "SELECT";
	$sql.= " p.rowid, p.code, p.adresse, p.cp, p.ville, p.pays, p.tel, p.fk_societe, p.notes, p.archive,";
	$sql.= " s.rowid as socid, s.nom as socname";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_place as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_societe = s.rowid";
	if ($arch == 0 || $arch == 1) $sql.= " WHERE p.archive LIKE ".$arch;
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

			$this->line[$i]->id = $obj->rowid;
			$this->line[$i]->code =  stripslashes($obj->code);
			$this->line[$i]->adresse = stripslashes($obj->adresse);
			$this->line[$i]->cp = $obj->cp;
			$this->line[$i]->ville = stripslashes($obj->ville);
			$this->line[$i]->pays = stripslashes($obj->pays);
			$this->line[$i]->tel = stripslashes($obj->tel);
			$this->line[$i]->fk_societe = $obj->fk_societe;
			$this->line[$i]->notes = stripslashes($obj->notes);
			$this->line[$i]->socid = $obj->socid;
			$this->line[$i]->socname = stripslashes($obj->socname);
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
     *    \brief      Load info object in memory from database
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function info($id)
    {
    	global $langs;
    	
        $sql = "SELECT";
	$sql.= " p.rowid, p.datec, p.tms, p.fk_user_mod, p.fk_user_author";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_place as p";
        $sql.= " WHERE p.rowid = ".$id;

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
                $this->fk_user_mod = $obj->fk_user_mod;
                $this->fk_user_author = $obj->fk_user_author;
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
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
	global $conf, $langs;
	$error=0;
	
	// Clean parameters
	$this->intitule = trim($this->intitule);
        $this->public = addslashes(trim($this->public));
        $this->methode = addslashes(trim($this->methode));
        $this->programme = addslashes(trim($this->programme));

        

	// Check parameters
	// Put here code to add control on parameters values


        // Update request
        if (!isset($this->archive)) $this->archive = 0; 
        $sql = 'UPDATE '.MAIN_DB_PREFIX.'agefodd_session_place as p SET';
	$sql.= ' p.code="'.$this->code.'", ';
	$sql.= ' p.adresse="'.$this->adresse.'", ';
	$sql.= ' p.cp="'.$this->cp.'", ';
	$sql.= ' p.ville="'.$this->ville.'", ';
	$sql.= ' p.pays="'.ebi_mysql_escape_string($this->pays).'",';
	$sql.= ' p.tel="'.ebi_mysql_escape_string($this->tel).'",';
	$sql.= ' p.fk_societe="'.$this->fk_societe.'",';
	$sql.= ' p.notes="'.ebi_mysql_escape_string($this->notes).'",';
	$sql.= ' p.fk_user_mod="'.$user.'",';
	$sql.= ' p.archive="'.$this->archive.'"';
        $sql.= " WHERE p.rowid LIKE ".$this->id;

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
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_session_place";
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





# $Date: 2010-03-28 19:06:42 +0200 (dim. 28 mars 2010) $ - $Revision: 51 $
?>
