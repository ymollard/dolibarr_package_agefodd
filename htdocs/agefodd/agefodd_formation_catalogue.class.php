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
 *	\version	$Id: agefodd_formation_catalogue.class.php 51 2010-03-28 17:06:42Z ebullier $
 */

require_once("../commonobject.class.php");

/**
 *	\class		Agefodd
 *	\brief		Module Agefodd class
 */
class Agefodd
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
    function Agefodd($DB) 
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
		$this->intitule = trim($this->intitule);
		//$this->public = addslashes(trim($this->public));
		//$this->methode = addslashes(trim($this->methode));
		//$this->methode = addslashes(trim($this->prerequis));
		//$this->programme = addslashes(trim($this->programme));


		// Check parameters
		// Put here code to add control on parameters value
		
		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_formation_catalogue(";
		$sql.= "datec, ref_interne, intitule, duree, public, methode, prerequis, programme, fk_user";
		$sql.= ") VALUES (";
		$sql.= '"'.$this->datec.'", ';
		$sql.= '"'.$this->ref_interne.'", ';
		$sql.= '"'.ebi_mysql_escape_string($this->intitule).'", ';
		$sql.= '"'.$this->duree.'", ';
		$sql.= '"'.ebi_mysql_escape_string($this->public).'",';
		$sql.= '"'.ebi_mysql_escape_string($this->methode).'",';
		$sql.= '"'.ebi_mysql_escape_string($this->prerequis).'",';
		$sql.= '"'.ebi_mysql_escape_string($this->programme).'",';
		$sql.= '"'.$user.'"';
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
	$sql.= " c.rowid, c.ref_interne, c.intitule, c.duree,";
	$sql.= " c.public, c.methode, c.prerequis, c.programme, archive";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c";
        $sql.= " WHERE c.rowid = ".$id;

	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
                $this->id = $obj->rowid;
                $this->ref_interne = $obj->ref_interne;
                $this->intitule = stripslashes($obj->intitule);
                $this->duree = $obj->duree;
                $this->public = stripslashes($obj->public);
                $this->methode = stripslashes($obj->methode);
                $this->prerequis = stripslashes($obj->prerequis);
                $this->programme = stripslashes($obj->programme);
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
     *    \brief      Load info object in memory from database
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function info($id)
    {
    	global $langs;
    	
        $sql = "SELECT";
	$sql.= " c.rowid, c.datec, c.tms, c.fk_user";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c";
        $sql.= " WHERE c.rowid = ".$id;

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
                $this->fk_user = $obj->fk_user;
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
	//$this->intitule = trim($this->intitule);
        //$this->public = addslashes(trim($this->public));
        //$this->methode = addslashes(trim($this->methode));
        //$this->programme = addslashes(trim($this->programme));

        

	// Check parameters
	// Put here code to add control on parameters values


        // Update request
        if (!isset($this->archive)) $this->archive = 0; 
        $sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c SET";
	$sql.= " c.ref_interne='".$this->ref_interne."',";
	$sql.= " c.intitule='".ebi_mysql_escape_string($this->intitule)."',";
	$sql.= " c.duree='".$this->duree."',";
        $sql.= " c.public='".ebi_mysql_escape_string($this->public)."',";
        $sql.= " c.methode='".ebi_mysql_escape_string($this->methode)."',";
        $sql.= " c.prerequis='".ebi_mysql_escape_string($this->prerequis)."',";
        $sql.= " c.programme='".ebi_mysql_escape_string($this->programme)."',";
        $sql.= " c.fk_user='".$user."',";
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
	*      \brief      Supprime l'operation
	*      \param      id          Id operation à supprimer
	*      \return     int         <0 si ko, >0 si ok
	*/
	function remove($id)
	{
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_formation_catalogue";
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
	 *	\brief		Load an object from its id and create a new one in database
	 *	\param		fromid		Id of object to clone
	 * 	\return		int		New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;
		
		$error=0;
		
		$object=new Agefodd($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...
				
		// Create clone
		$result=$object->create($user);

		// Other options
		if ($result < 0) 
		{
			$this->error=$object->error;
			$error++;
		}
		
		if (! $error)
		{
			
			
			
		}
		
		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

	
	/**
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		

		
	}


    /**
     *      \brief      Create in database
     *      \param      user        	User that create
     *      \param      notrigger	0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, Id of created object if OK
     */
    function create_objpeda($user=0)
    {
    	global $conf, $langs;
		$error=0;
    	
		// Clean parameters
		$this->intitule = addslashes(trim($this->intitule));



		// Check parameters
		// Put here code to add control on parameters value
		
		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_formation_objectifs_peda(";
		$sql.= "fk_formation_catalogue, intitule, priorite, fk_user";
		$sql.= ") VALUES (";
                $sql.= '"'.$this->fk_formation_catalogue.'", ';
                $sql.= '"'.$this->intitule.'", ';
                $sql.= '"'.$this->priorite.'", ';
                $sql.= '"'.$user.'"';
		$sql.= ")";

		$this->db->begin();
		
	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
	   	$resql=$this->db->query($sql);
	   	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		if (! $error)
		{
		    $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."agefodd_formation_objectifs_peda");
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
     *    \return	int	<0 if KO, >0 if OK
     */
    function fetch_objpeda($id_formation)
    {
    	global $langs;
    	
        $sql = "SELECT";
	$sql.= " o.intitule, o.priorite";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_formation_objectifs_peda";
	$sql.= " as o";
        $sql.= " WHERE o.rowid = ".$id_formation;
        

	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
                $this->id = $obj->rowid;
                $this->intitule = stripslashes($obj->intitule);
                $this->priorite = $obj->priorite;
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
	*    \brief	Recupere les objectifs pedagogiques d'une formation
	*    \param	id	int	id formation
	*    \return	int	<0 if KO, >0 if OK
	*/
	function fetch_objpeda_per_formation($id_formation)
	{
		global $langs;
		
		$sql = "SELECT";
		$sql.= " o.rowid, o.intitule, o.priorite, o.fk_formation_catalogue, o.tms, o.fk_user";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_formation_objectifs_peda AS o";
		$sql.= " WHERE o.fk_formation_catalogue = ".$id_formation;
		$sql.= " ORDER BY o.priorite ASC";

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->line = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			while( $i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				
				$this->line[$i]->id = $obj->rowid;
				$this->line[$i]->intitule = stripslashes($obj->intitule);
				$this->line[$i]->priorite = $obj->priorite;
				
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
     *      \brief      Update database
     *      \param      user        	User that modify
     *      \param      notrigger	0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update_objpeda($user=0)
    {
	global $conf, $langs;
	$error=0;
	
	// Clean parameters
	$this->intitule = addslashes(trim($this->intitule));
        

	// Check parameters
	// Put here code to add control on parameters values


        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_formation_objectifs_peda as o SET";
	$sql.= " o.fk_formation_catalogue='".$this->fk_formation_catalogue."',";
	$sql.= " o.intitule='".$this->intitule."',";
	//$sql.= " o.fk_user='".$this->fk_user."',";
	$sql.= " o.fk_user='".$user."',";
	$sql.= " o.priorite='".$this->priorite."'";
	$sql.= " WHERE o.rowid = ".$this->id;

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
	function remove_objpeda($id)
	{
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_formation_objectifs_peda";
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
