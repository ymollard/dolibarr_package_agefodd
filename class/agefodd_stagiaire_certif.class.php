<?php
/* Copyright (C) 2012	Florian Henry		<florian.henry@open-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       agefodd/class/agefodd_stagiaire_certif.class.php
 *      \brief      Manage certificate
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");


/**
 *	Manage certificate
 */
class Agefodd_stagiaire_certif  extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='agfstacertif';			//!< Id that identify managed objects
	var $table_element='agefodd_stagiaire_certif';	//!< Name of table without prefix where object is stored

    var $id;
    
	var $entity;
	
	var $fk_user_author='';
	var $fk_user_mod='';
	var $datec='';
	var $tms='';

	var $fk_stagiaire;
	var $fk_session_agefodd;
	var $fk_session_stagiaire;
	var $certif_code;
	var $certif_label;
	var $certif_dt_start='';
	var $certif_dt_end='';


    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
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
		if (isset($this->fk_stagiaire)) $this->fk_stagiaire=trim($this->fk_stagiaire);
		if (isset($this->fk_session_agefodd)) $this->fk_session_agefodd=trim($this->fk_session_agefodd);
		if (isset($this->fk_session_stagiaire)) $this->fk_session_stagiaire=trim($this->fk_session_stagiaire);
		if (isset($this->certif_code)) $this->certif_code=trim($this->certif_code);
		if (isset($this->certif_label)) $this->certif_label=trim($this->certif_label);

        

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_stagiaire_certif(";
		
		$sql.= "entity,";
		$sql.= "fk_user_author,";
		$sql.= "fk_user_mod,";
		$sql.= "datec,";
		$sql.= "fk_stagiaire,";
		$sql.= "fk_session_agefodd,";
		$sql.= "fk_session_stagiaire,";
		$sql.= "certif_code,";
		$sql.= "certif_label,";
		$sql.= "certif_dt_start,";
		$sql.= "certif_dt_end";

		
        $sql.= ") VALUES (";
        
		$sql.= " ".$conf->entity.",";
		$sql.= " '".$user->id."',";
		$sql.= " '".$user->id."',";
		$sql.= "'".$this->db->idate(dol_now())."',";
		$sql.= " ".(! isset($this->fk_stagiaire)?'NULL':"'".$this->fk_stagiaire."'").",";
		$sql.= " ".(! isset($this->fk_session_agefodd)?'NULL':"'".$this->fk_session_agefodd."'").",";
		$sql.= " ".(! isset($this->fk_session_stagiaire)?'NULL':"'".$this->fk_session_stagiaire."'").",";
		$sql.= " ".(! isset($this->certif_code)?'NULL':"'".$this->db->escape($this->certif_code)."'").",";
		$sql.= " ".(! isset($this->certif_label)?'NULL':"'".$this->db->escape($this->certif_label)."'").",";
		$sql.= " ".(! isset($this->certif_dt_start) || dol_strlen($this->certif_dt_start)==0?'NULL':$this->db->idate($this->certif_dt_start)).",";
		$sql.= " ".(! isset($this->certif_dt_end) || dol_strlen($this->certif_dt_end)==0?'NULL':$this->db->idate($this->certif_dt_end))."";

        
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."agefodd_stagiaire_certif");

			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.

	            //// Call triggers
	            //include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
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
     *  @param	int		$id    Id object
     *  @param	int		$id_trainee    Id object
     *  @param	int		$id_session    Id object
     *  @param	int		$id_sess_trainee    Id object
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch($id=0,$id_trainee=0,$id_session=0,$id_sess_trainee=0)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		
		$sql.= " t.entity,";
		$sql.= " t.fk_user_author,";
		$sql.= " t.fk_user_mod,";
		$sql.= " t.datec,";
		$sql.= " t.tms,";
		$sql.= " t.fk_stagiaire,";
		$sql.= " t.fk_session_agefodd,";
		$sql.= " t.fk_session_stagiaire,";
		$sql.= " t.certif_code,";
		$sql.= " t.certif_label,";
		$sql.= " t.certif_dt_start,";
		$sql.= " t.certif_dt_end";

		
        $sql.= " FROM ".MAIN_DB_PREFIX."agefodd_stagiaire_certif as t";
        if (!empty($id)) {
        	$sql.= " WHERE t.rowid = ".$id;
        }else {
        	$sqlwhere = array();

        	if (!empty($id_trainee)) {
        		$sqlwhere[]= "  t.fk_stagiaire = ".$id_trainee;
        	}
       		if (!empty($id_session)) {
        		$sqlwhere[]= " t.fk_session_agefodd = ".$id_session;
       		}
       		if (!empty($id_sess_trainee)) {
        		$sqlwhere[]= " t.fk_session_stagiaire = ".$id_sess_trainee;
       		}

       		if (count($sqlwhere>0)) {
       			$sql.= " WHERE ".implode(' AND ',$sqlwhere);
       		}
        }
        

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
                
				$this->entity = $obj->entity;
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_stagiaire = $obj->fk_stagiaire;
				$this->fk_session_agefodd = $obj->fk_session_agefodd;
				$this->fk_session_stagiaire = $obj->fk_session_stagiaire;
				$this->certif_code = $obj->certif_code;
				$this->certif_label = $obj->certif_label;
				$this->certif_dt_start = $this->db->jdate($obj->certif_dt_start);
				$this->certif_dt_end = $this->db->jdate($obj->certif_dt_end);

                
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
     *  @param	string $sortorder    Sort Order
     *  @param	string $sortfield    Sort field
     *  @param	int $limit    	offset limit
     *  @param	int $offset    	offset limit
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch_all($sortorder, $sortfield, $limit, $offset)
    {
    	global $langs;
    
    	$sql = "SELECT";
    	$sql.= " t.rowid,";
		
		$sql.= " t.entity,";
		$sql.= " t.fk_user_author,";
		$sql.= " t.fk_user_mod,";
		$sql.= " t.datec,";
		$sql.= " t.tms,";
		$sql.= " t.fk_stagiaire,";
		$sql.= " t.fk_session_agefodd,";
		$sql.= " t.fk_session_stagiaire,";
		$sql.= " t.certif_code,";
		$sql.= " t.certif_label,";
		$sql.= " t.certif_dt_start,";
		$sql.= " t.certif_dt_end";
    	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_stagiaire_certif as t";
    	
    	$sql.= " WHERE t.entity IN (".getEntity('agsession').")";
    
    	$sql.= " ORDER BY ".$sortfield." ".$sortorder." ".$this->db->plimit( $limit + 1 ,$offset);
    
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
    			
    			$line = new AgfStagiaireCertifLine();
    
    			$line->id    = $obj->rowid;
                
				$line->entity = $obj->entity;
				$line->fk_user_author = $obj->fk_user_author;
				$line->fk_user_mod = $obj->fk_user_mod;
				$line->datec = $this->db->jdate($obj->datec);
				$line->tms = $this->db->jdate($obj->tms);
				$line->fk_stagiaire = $obj->fk_stagiaire;
				$line->fk_session_agefodd = $obj->fk_session_agefodd;
				$line->fk_session_stagiaire = $obj->fk_session_stagiaire;
				$line->certif_code = $obj->certif_code;
				$line->certif_label = $obj->certif_label;
				$line->certif_dt_start = $this->db->jdate($obj->certif_dt_start);
				$line->certif_dt_end = $this->db->jdate($obj->certif_dt_end);
				
				$this->line[$i]=$line;
    
    			$i++;
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
     *  Update object into database
     *
     *  @param	User	$user        User that modify
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
		if (isset($this->fk_stagiaire)) $this->fk_stagiaire=trim($this->fk_stagiaire);
		if (isset($this->fk_session_agefodd)) $this->fk_session_agefodd=trim($this->fk_session_agefodd);
		if (isset($this->fk_session_stagiaire)) $this->fk_session_stagiaire=trim($this->fk_session_stagiaire);
		if (isset($this->certif_code)) $this->certif_code=trim($this->certif_code);
		if (isset($this->certif_label)) $this->certif_label=trim($this->certif_label);

		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_stagiaire_certif SET";
        
		$sql.= " entity=".$conf->entity.",";
		$sql.= " fk_user_mod=".$user->id.",";
		$sql.= " fk_stagiaire=".(isset($this->fk_stagiaire)?$this->fk_stagiaire:"null").",";
		$sql.= " fk_session_agefodd=".(isset($this->fk_session_agefodd)?$this->fk_session_agefodd:"null").",";
		$sql.= " fk_session_stagiaire=".(isset($this->fk_session_stagiaire)?$this->fk_session_stagiaire:"null").",";
		$sql.= " certif_code=".(isset($this->certif_code)?"'".$this->db->escape($this->certif_code)."'":"null").",";
		$sql.= " certif_label=".(isset($this->certif_label)?"'".$this->db->escape($this->certif_label)."'":"null").",";
		$sql.= " certif_dt_start=".(dol_strlen($this->certif_dt_start)!=0 ? "'".$this->db->idate($this->certif_dt_start)."'" : 'null').",";
		$sql.= " certif_dt_end=".(dol_strlen($this->certif_dt_end)!=0 ? "'".$this->db->idate($this->certif_dt_end)."'" : 'null')."";

        $sql.= " WHERE rowid=".$this->id;

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
	            //include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
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
     *	@param  User	$user        User that delete
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$this->db->begin();

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
		        // want this action call a trigger.

		        //// Call triggers
		        //include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		        //$interface=new Interfaces($this->db);
		        //$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
		        //if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        //// End call triggers
			}
		}

		if (! $error)
		{
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_stagiaire_certif";
    		$sql.= " WHERE rowid=".$this->id;

    		dol_syslog(get_class($this)."::delete sql=".$sql);
    		$resql = $this->db->query($sql);
        	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
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
	 *  Give information on the object
	 *
	 *  @param	int		$id    Id object
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function info($id)
	{
		global $langs;
	
		$sql = "SELECT";
		$sql.= " p.rowid, p.datec, p.tms, p.fk_user_mod, p.fk_user_author";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_stagiaire_certif as p";
		$sql.= " WHERE p.rowid = ".$id;
	
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
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		
		$this->entity='';
		$this->fk_user_author='';
		$this->fk_user_mod='';
		$this->datec='';
		$this->tms='';
		$this->fk_stagiaire='';
		$this->fk_session_agefodd='';
		$this->fk_session_stagiaire='';
		$this->certif_code='';
		$this->certif_label='';
		$this->certif_dt_start='';
		$this->certif_dt_end='';

		
	}

}

/**
 *	Session line Class
 */
class AgfStagiaireCertifLine
{
	 var $id;
    
	var $entity;

	var $fk_stagiaire;
	var $fk_session_agefodd;
	var $fk_session_stagiaire;
	var $certif_code;
	var $certif_label;
	var $certif_dt_start='';
	var $certif_dt_end='';
	
	
	function __construct()
	{
		return 1;
	}
}