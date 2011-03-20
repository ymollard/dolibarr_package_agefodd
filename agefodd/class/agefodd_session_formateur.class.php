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
 *	\file		$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/agefodd_session_formateur.php $
 *	\ingroup	agefodd
 *	\brief		CRUD class file (Create/Read/Update/Delete) for agefodd module
 *	\version	$Id: agefodd_stagiaire.class.php 51 2010-03-28 17:06:42Z ebullier $
 */

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 *	\class		Agefodd
 *	\brief		Module Agefodd class
 */
class Agefodd_session_formateur
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
    function Agefodd_session_formateur($DB) 
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
		$this->sessid = trim($this->sessid);
		$this->formid = trim($this->formid);

		// Check parameters

		
		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_session_formateur(";
		$sql.= "fk_session,fk_agefodd_formateur, fk_user_author, datec";
		$sql.= ") VALUES (";
		$sql.= '"'.$this->sessid.'", ';
		$sql.= '"'.$this->formid.'", ';
		$sql.= '"'.$user.'", ';
		$sql.= '"'.$this->datec.'" ';
		$sql.= ")";
		
		$this->db->begin();
		
	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
	   	$resql=$this->db->query($sql);
	   	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		if (! $error)
		{
		    $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."agefodd_session_formateur");
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
	$sql.= " sf.rowid, sf.fk_session, sf.fk_agefodd_formateur,";
	$sql.= " f.fk_socpeople,";
	$sql.= " sp.name, sp.firstname";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_formateur as sf";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formateur as f";
	$sql.= " ON sf.fk_agefodd_formateur = f.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp";
	$sql.= " ON f.fk_socpeople = sp.rowid";
	$sql.= " WHERE s.rowid = ".$id;

	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
                $this->id = $obj->rowid;
                $this->sessid = $obj->fk_session;
		$this->formid = $obj->fk_agefodd_formateur;
		$this->name = $obj->name;
		$this->firstname = $obj->firstname;
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
     *    \param	id		Session ID
     *    \return	int		<0 if KO, number of row if OK
     */
    function fetch_formateur_per_session($id)
    {
    	global $langs;
                            
	$sql = "SELECT";
	$sql.= " sf.rowid, sf.fk_session, sf.fk_agefodd_formateur,";
	$sql.= " f.rowid as formid, f.fk_socpeople,";
	$sql.= " sp.name, sp.firstname";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_formateur as sf";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formateur as f";
	$sql.= " ON sf.fk_agefodd_formateur = f.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp";
	$sql.= " ON f.fk_socpeople = sp.rowid";
	$sql.= " WHERE sf.fk_session = ".$id;
	$sql.= " ORDER BY name ASC";

	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
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
				$this->line[$i]->opsid = $obj->rowid;
				$this->line[$i]->name = $obj->name;
				$this->line[$i]->firstname = $obj->firstname;
				$this->line[$i]->socpeopleid = $obj->fk_socpeople;
				$this->line[$i]->formid = $obj->formid;
				$this->line[$i]->sessid = $obj->fk_session;
				$i++;
			}
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
	$this->opsid = trim($this->opsid);
	$this->formid = trim($this->formid);
	
	// Check parameters
	// Put here code to add control on parameters values


        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_session_formateur as sf SET";
	$sql.= " sf.fk_agefodd_formateur='".$this->formid."',";
	$sql.= " sf.fk_user_mod='".$user."'";
        $sql.= " WHERE sf.rowid = ".$this->opsid;

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
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_session_formateur";
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
