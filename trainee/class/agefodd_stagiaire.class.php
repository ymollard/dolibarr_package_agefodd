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
 *	\file		$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/agefodd_stagiaire.class.php $
 *	\ingroup	agefodd
 *	\brief		CRUD class file (Create/Read/Update/Delete) for agefodd module
 *	\version	$Id$
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");

/**
 *	\class		Agefodd
 *	\brief		Module Agefodd class
 */
class Agefodd_stagiaire
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
    function Agefodd_stagiaire($DB) 
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
		$this->nom = trim($this->nom);
    		$this->prenom = trim($this->prenom);
    		$this->fonction = addslashes(trim($this->fonction));
    		$this->tel1 = addslashes(trim($this->tel1));
    		$this->tel2 = addslashes(trim($this->tel2));
		$this->mail = addslashes(trim($this->mail));
    		$this->note = addslashes(trim($this->note));

		// Check parameters
		// Put here code to add control on parameters value
		$this->nom = strtoupper($this->nom);
    		$this->prenom = ucfirst(strtolower($this->prenom));

		
		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_stagiaire(";
		$sql.= "nom, prenom, fk_c_civilite, fk_user_author, datec, ";
		$sql.= "fk_soc, fonction, tel1, tel2, mail, note";
		$sql.= ") VALUES (";
		$sql.= '"'.$this->nom.'", ';
		$sql.= '"'.$this->prenom.'", ';
		$sql.= '"'.$this->civilite.'", ';
		$sql.= '"'.$user.'", ';
		$sql.= '"'.$this->datec.'", ';
		$sql.= '"'.$this->socid.'", ';
		$sql.= '"'.ebi_mysql_escape_string($this->fonction).'", ';
		$sql.= '"'.$this->tel1.'", ';
		$sql.= '"'.$this->tel2.'", ';
		$sql.= '"'.$this->mail.'", ';
		$sql.= '"'.ebi_mysql_escape_string($this->note).'"';
		$sql.= ")";
		
		$this->db->begin();
		
	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
	   	$resql=$this->db->query($sql);
	   	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		if (! $error)
		{
		    $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."agefodd_stagiaire");
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
	$sql.= " so.rowid as socid, so.nom as socname,";
	$sql.= " civ.code as civilite,";
	$sql.= " s.rowid, s.nom, s.prenom, s.fk_c_civilite, s.fk_soc, s.fonction,";
	$sql.= " s.tel1, s.tel2, s.mail, s.note";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_stagiaire as s";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as so";
	$sql.= " ON s.fk_soc = so.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_civilite as civ";
	$sql.= " ON s.fk_c_civilite = civ.rowid";
        $sql.= " WHERE s.rowid = ".$id;

	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
                $this->id = $obj->rowid;
                $this->nom = $obj->nom;
                $this->prenom = $obj->prenom;
                $this->civilite_code = $obj->civilite;
                $this->civilite_id = $obj->fk_c_civilite;
                $this->socid = $obj->socid;
                $this->socname = $obj->socname;
                $this->fonction = $obj->fonction;
                $this->tel1 = $obj->tel1;
                $this->tel2 = $obj->tel2;
                $this->mail = $obj->mail;
                $this->note = $obj->note;
                
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
     *    \param	sortorder	how is orderer the result
     *			sortfield	on wich field is based the filter
     *			limit		max number row display per page
     *			offseth		from wich row start the display
     *    \return	int		<0 if KO, number of row if OK
     */
    function fetch_liste_globale($sortorder, $sortfield, $limit, $offset)
    {
    	global $langs;
                            
	$sql = "SELECT";
	$sql.= " so.rowid as socid, so.nom as socname,";
	$sql.= " civ.code as civilite,";
	$sql.= " s.rowid, s.nom, s.prenom, s.fk_c_civilite, s.fk_soc, s.fonction,";
	$sql.= " s.tel1, s.tel2, s.mail, s.note";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_stagiaire as s";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as so";
	$sql.= " ON s.fk_soc = so.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_civilite as civ";
	$sql.= " ON s.fk_c_civilite = civ.rowid";
	$sql.= " ORDER BY ".$sortfield." ".$sortorder." ".$this->db->plimit( $limit + 1 ,$offset);

	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $numrows = $this->db->num_rows($resql);
            if ($numrows)
            {
                $obj = $this->db->fetch_object($resql);
                $this->id = $obj->rowid;
                $this->nom = $obj->nom;
                $this->prenom = $obj->prenom;
                $this->civilite = $obj->civilite;
                $this->socid = $obj->socid;
                $this->socname = $obj->socname;
                $this->tel1 = $obj->tel1;
                $this->mail = $obj->mail;
	    }
            $this->db->free($resql);

            return $numrows;
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
	$sql.= " s.rowid, s.datec, s.tms, s.fk_user_author, s.fk_user_mod";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_stagiaire as s";
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
	$this->nom = trim($this->nom);
        $this->prenom = trim($this->prenom);
        $this->fonction = addslashes(trim($this->fonction));
        $this->tel1 = addslashes(trim($this->tel1));
        $this->tel2 = addslashes(trim($this->tel2));
	$this->mail = addslashes(trim($this->mail));
        $this->note = addslashes(trim($this->note));

        

	// Check parameters
	// Put here code to add control on parameters values


        // Update request
        if (!isset($this->archive)) $this->archive = 0; 
        $sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_stagiaire as s SET";
	$sql.= " s.nom='".$this->nom."',";
	$sql.= " s.prenom='".$this->prenom."',";
	$sql.= " s.fk_c_civilite='".$this->civilite."',";
        $sql.= " s.fk_user_mod='".$user."',";
        $sql.= " s.fk_soc='".$this->socid."',";
        $sql.= " s.fonction='".$this->fonction."',";
        $sql.= " s.tel1='".$this->tel1."',";
        $sql.= " s.tel2='".$this->tel2."',";
        $sql.= " s.mail='".$this->mail."',";
        $sql.= " s.note='".ebi_mysql_escape_string($this->note)."'";
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
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_stagiaire";
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
	function createFromContact($fromid)
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

}

# $Date: 2010-03-28 19:06:42 +0200 (dim. 28 mars 2010) $ - $Revision: 51 $
?>
