<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012       Florian Henry   <florian.henry@open-concept.pro>
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
 *	\file		/agefodd/session/agefodd_session.class.php
 *	\ingroup	agefodd
 *	\brief		CRUD class file (Create/Read/Update/Delete) for agefodd module
 *	\version	$Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 *	\class		Agefodd
 *	\brief		Module Agefodd class
 */
class Agefodd_session extends CommonObject
{
	var $db;
	var $error;
	var $errors=array();
	var $element='agefodd';
	var $table_element='agefodd_session';
    var $id;

    /**
     *	\brief		Constructor
     *	\param		DB	Database handler
     */
    function Agefodd_session($DB) 
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
    	$this->fk_agefodd_formateur = $this->db->escape(trim($this->fonction));
    	$this->dated = trim($this->dated);
    	$this->datef = trim($this->datef);
		$this->notes = trim($this->notes);
		$this->notes = $this->db->escape($this->notes);
    		
		// Check parameters
		// Put here code to add control on parameters value
		
		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_session(";
		$sql.= "fk_formation_catalogue, fk_session_place, fk_agefodd_formateur, dated, datef, ";
		$sql.= "notes, fk_user_author, datec";
		$sql.= ") VALUES (";
		$sql.= '"'.$this->formid.'", ';
		$sql.= '"'.$this->place.'", ';
		$sql.= '"'.$this->formateur.'", ';
		$sql.= ($this->dated != '' ? $this->db->idate($this->dated) : 'null').', ';
		$sql.= ($this->datef != '' ? $this->db->idate($this->datef) : 'null').', ';
		$sql.= '"'.$this->notes.'",';
		$sql.= '"'.$user.'", ';
		$sql.= $this->db->idate(dol_now());
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
     *      \brief      Create in database (stagiaire in session)
     *      \param      user        	User that create
     *      \param      notrigger	0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, Id of created object if OK
     */
     function create_stag_in_session($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;
    	
		// Clean parameters
		$this->sessid = $this->db->escape(trim($this->sessid));
		
		// Check parameters
		// Put here code to add control on parameters value
		
		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_session_stagiaire (";
		$sql.= "fk_session, fk_stagiaire, fk_agefodd_stagiaire_type, fk_user_author, datec";
		$sql.= ") VALUES (";
		$sql.= '"'.$this->sessid.'", ';
		$sql.= '"'.$this->stagiaire.'", ';
		$sql.= '"'.$this->stagiaire_type.'", ';
		$sql.= '"'.$user.'", ';
		$sql.= $this->db->idate(dol_now());
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
		$sql.= " s.rowid, s.fk_formation_catalogue, s.fk_session_place, s.fk_agefodd_formateur, s.dated, s.datef, s.notes, s.archive,";
		$sql.= " c.intitule, c.ref, c.duree,";
		$sql.= " p.rowid as placeid, p.ref_interne,";
		$sql.= " CONCAT(sp.name,' ',sp.firstname) as teachername";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c";
		$sql.= " ON c.rowid = s.fk_formation_catalogue";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_place as p";
		$sql.= " ON p.rowid = s.fk_session_place";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_stagiaire as ss";
		$sql.= " ON ss.fk_session = c.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formateur as f";
		$sql.= " ON f.rowid = s.fk_agefodd_formateur";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp";
		$sql.= " ON sp.rowid = f.fk_socpeople";
        $sql.= " WHERE s.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
                $this->id = $obj->rowid;
                $this->ref = $obj->rowid; //use for prev next ref
                $this->formid = stripslashes($obj->fk_formation_catalogue);
                $this->place = stripslashes($obj->fk_session_place);
				$this->teacherid = stripslashes($obj->fk_agefodd_formateur);
                $this->formref = stripslashes($obj->ref);
                $this->formintitule = stripslashes($obj->intitule);
				$this->duree = $obj->duree;
                $this->placeid = stripslashes($obj->placeid);
				$this->teachername = stripslashes($obj->teachername);
				$this->placecode = stripslashes($obj->ref_interne);
                $this->dated = $this->db->jdate($obj->dated);
                $this->datef =$this->db->jdate($obj->datef);
                $this->notes = stripslashes($obj->notes);
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
     *    \param	id	id session
     *    \return	int	<0 if KO, num of row if OK
     */
    function fetch_stagiaire_per_session($id, $socid=NULL)
    {
    	global $langs;
                            
		$sql = "SELECT";
		$sql.= " s.rowid as sessid,";
		$sql.= " ss.rowid, ss.fk_stagiaire, ss.fk_agefodd_stagiaire_type,";
		$sql.= " sa.nom, sa.prenom,";
		$sql.= " civ.code as civilite, civ.civilite as civilitel,";
		$sql.= " so.nom as socname, so.rowid as socid,";
		$sql.= " st.rowid as typeid, st.intitule as type";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."agefodd_session_stagiaire as ss";
		$sql.= " ON s.rowid = ss.fk_session";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_stagiaire as sa";
		$sql.= " ON sa.rowid = ss.fk_stagiaire";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_civilite as civ";
		$sql.= " ON civ.code = sa.civilite";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as so";
		$sql.= " ON so.rowid = sa.fk_soc";
	 	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_stagiaire_type as st";
		$sql.= " ON st.rowid = ss.fk_agefodd_stagiaire_type";
        $sql.= " WHERE s.rowid = ".$id;
		if (!empty($socid)) $sql.= " AND so.rowid = ".$socid;
		$sql.= " ORDER BY sa.nom";

		dol_syslog(get_class($this)."::fetch_stagiaire_per_session sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->line = array();
            $num = $this->db->num_rows($resql);

			$i = 0;
           	while( $i < $num)
		//for ($i=0; $i < $num; $i++)
			{
	           	$obj = $this->db->fetch_object($resql);
				$this->line[$i]->stagerowid = $obj->rowid;
	           	$this->line[$i]->sessid = $obj->sessid;
				$this->line[$i]->id = $obj->fk_stagiaire;
               	$this->line[$i]->nom = $obj->nom;
               	$this->line[$i]->prenom = $obj->prenom;
               	$this->line[$i]->civilite = $obj->civilite;
               	$this->line[$i]->civilitel = $obj->civilitel;
               	$this->line[$i]->socname = $obj->socname;
				$this->line[$i]->socid = $obj->socid;
				$this->line[$i]->typeid = $obj->typeid;
				$this->line[$i]->type = $obj->type;
				$i++;
			}
			$this->db->free($resql);
			return $num;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch_stagiaire_per_session ".$this->error, LOG_ERR);
            return -1;
        }
    }


     /**
     *    \brief	Load object in memory from database
     *    \param	id	id session
     *    \return	int	<0 if KO, num of row if OK
     */
    function fetch_societe_per_session($id)
    {
    	global $langs;
                            
		$sql = "SELECT";
		$sql.= " DISTINCT so.rowid as socid,";
		$sql.= " s.rowid, so.nom as socname ";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_stagiaire as ss";
		$sql.= " ON s.rowid = ss.fk_session";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_stagiaire as sa";
		$sql.= " ON sa.rowid = ss.fk_stagiaire";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as so";
		$sql.= " ON so.rowid = sa.fk_soc";
        $sql.= " WHERE s.rowid = ".$id;
		$sql.= " ORDER BY so.nom";

		dol_syslog(get_class($this)."::fetch_societe_per_session sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->line = array();
            $num = $this->db->num_rows($resql);

			if ($num)
			{
				$i = 0;
				while( $i < $num)
				{
	                $obj = $this->db->fetch_object($resql);
	                $this->line[$i]->sessid = $obj->sessid;
					$this->line[$i]->socname = $obj->socname;
	                $this->line[$i]->socid = $obj->socid;
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
     *    \brief      Load info object in memory from database
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
		$this->fk_session_place = $this->db->escape(trim($this->fk_session_place));
        $this->notes = $this->db->escape(trim($this->notes));

		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        if (!isset($this->archive)) $this->archive = 0; 
        $sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_session as s SET";
		$sql.= " s.fk_session_place='".$this->fk_session_place."',";
		$sql.= " s.fk_agefodd_formateur='".$this->formateur."',";
        $sql.= " s.dated=".$this->db->idate($this->dated).",";
        $sql.= " s.datef=".$this->db->idate($this->datef).",";
        $sql.= " s.notes='".$this->notes."',";
        $sql.= " s.fk_user_mod='".$user."',";
        $sql.= " s.archive='".$this->archive."'";
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
     *      \brief      Update database
     *      \param      stagiaire        Stagiaire who is added to a formation
     *      \param      notrigger	0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update_stag_in_session($user=0, $notrigger=0)
    {
		global $conf, $langs;
		$error=0;
		
		// Clean parameters
		$this->sessid = addslashes(trim($this->sessid));
		$this->stagiaire = addslashes(trim($this->stagiaire));
		$this->type = addslashes(trim($this->type));
	        
		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        if (!isset($this->archive)) $this->archive = 0; 
        $sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_session_stagiaire as s SET";
		$sql.= " s.fk_session='".$this->sessid."',";
		$sql.= " s.fk_stagiaire='".$this->stagiaire."',";
        $sql.= " s.fk_user_mod='".$user."',";
        $sql.= " s.fk_agefodd_stagiaire_type='".$this->type."'";
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
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_session";
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
	*      \brief      Supprime le stagiaire d'une session (desinscrit)
	*      \param      id          Id stagiaire à supprimer
	*      \return     int         <0 si ko, >0 si ok
	*/
	function remove_stagiaire($id)
	{
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_session_stagiaire";
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
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id=0;
	}
	
	/**
	 *      Return description of session
	 *
	 *		@param	int			$type		trainning
	 *		@return	string					HTML translated description
	 */
	function getToolTip($type)
	{
		global $conf;
		
		$langs->load("admin");
		
		$s='';
		if (type=='training')
		{
			dol_include_once('/agefodd/training/class/agefodd_formation_catalogue.class.php');
			
			$agf_training = new Agefodd($db);
			$agf_training->fetch($this->formid);
			$s=$agf_training->getToolTip();
		}
		return $s;
	}
	
	function fetch_all($sortorder, $sortfield, $limit, $offset, $arch)
	{
		global $langs;
	
		$sql = "SELECT s.rowid, s.fk_session_place, s.dated, s.datef,";
		$sql.= " c.intitule, c.ref,";
		$sql.= " p.ref_interne,";
		$sql.= " (SELECT count(*) FROM ".MAIN_DB_PREFIX."agefodd_session_stagiaire WHERE fk_session=s.rowid) as num";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c";
		$sql.= " ON c.rowid = s.fk_formation_catalogue";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_place as p";
		$sql.= " ON p.rowid = s.fk_session_place";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_stagiaire as ss";
		$sql.= " ON s.rowid = ss.fk_session";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_adminsitu as sa";
		$sql.= " ON s.rowid = sa.fk_agefodd_session";
		
		if ($arch == 2)
		{
			$sql.= " WHERE s.archive LIKE 0";
			$sql.= " AND sa.indice=";
			$sql.= "(";
			$sql.= " SELECT MAX(indice) FROM llx_agefodd_session_adminsitu WHERE level_rank=0";
			$sql.= ")";
			$sql.= " AND sa.archive LIKE 1 AND sa.datef > '0000-00-00 00:00:00'";
		}
		else $sql.= " WHERE s.archive LIKE ".$arch;
		$sql.= " GROUP BY (s.rowid)";
		$sql.= " ORDER BY $sortfield $sortorder " . $this->db->plimit( $limit + 1 ,$offset);
		
		$resql = $this->db->query($sql);
		
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
					$this->line[$i]->rowid = $obj->rowid;
					$this->line[$i]->fk_session_place = $obj->fk_session_place;
					$this->line[$i]->dated = $this->db->jdate($obj->dated);
					$this->line[$i]->datef =$this->db->jdate($obj->datef);
					$this->line[$i]->intitule = $obj->intitule;
					$this->line[$i]->ref = $obj->ref;
					$this->line[$i]->ref_interne = $obj->ref_interne;
					$this->line[$i]->num = $obj->num;
		
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
	
}

# $Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $
?>
