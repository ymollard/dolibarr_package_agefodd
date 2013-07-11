<?php
/* Copyright (C) 2012-2013	Florian Henry		<florian.henry@open-concept.pro>
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
 *      \file       agefodd/class/agefodd_session_stagiaire.class.php
 *      \ingroup    agefodd
 *      \brief      Manage trainee in session
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");


/**
 *	Manage certificate
*/
class Agefodd_session_stagiaire  extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='agfsessionsta';			//!< Id that identify managed objects
	var $table_element='agefodd_session_stagiaire';	//!< Name of table without prefix where object is stored

	var $id;
	var $entity;
	
	var $fk_session_agefodd;
	var $fk_stagiaire;
	var $fk_agefodd_stagiaire_type;

	var $fk_user_author='';
	var $fk_user_mod='';
	var $datec='';
	var $tms='';



	var $lines=array();
	var $lines_state=array();


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
	 *  Load object  in memory from database
	 *
	 *  @param	int		$id    Id of session
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		$sql = "SELECT";
		$sql.= " fk_session_agefodd, fk_stagiaire, fk_agefodd_stagiaire_type, fk_user_author,fk_user_mod, datec";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_stagiaire";
		$sql.= " WHERE rowid= ".$id;
		
		dol_syslog(get_class($this)."::fetch_stagiaire_per_session sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			
			$this->fk_session_agefodd=$obj->fk_session_agefodd;
			$this->fk_stagiaire=$obj->fk_stagiaire;
			$this->fk_agefodd_stagiaire_type=$obj->fk_agefodd_stagiaire_type;
			$this->fk_user_author=$obj->fk_user_author;
			$this->fk_user_mod=$obj->fk_user_mod;
			
			$this->db->free($resql);
			
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_stagiaire_per_session ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *  Load object (all trainee for one session) in memory from database
	 *
	 *  @param	int		$id    Id of session
	 *  @return int          	<0 if KO, >0 if OK
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
		$sql.= " st.rowid as typeid, st.intitule as type, sa.mail as stamail, sope.email as socpemail,";
		$sql.= " sa.date_birth,";
		$sql.= " sa.place_birth,";
		$sql.= " sa.fk_socpeople,";
		$sql.= " sope.birthday";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."agefodd_session_stagiaire as ss";
		$sql.= " ON s.rowid = ss.fk_session_agefodd";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_stagiaire as sa";
		$sql.= " ON sa.rowid = ss.fk_stagiaire";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_civilite as civ";
		$sql.= " ON civ.code = sa.civilite";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as so";
		$sql.= " ON so.rowid = sa.fk_soc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sope";
		$sql.= " ON sope.rowid = sa.fk_socpeople";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_stagiaire_type as st";
		$sql.= " ON st.rowid = ss.fk_agefodd_stagiaire_type";
		$sql.= " WHERE s.rowid = ".$id;
		if (!empty($socid)) $sql.= " AND so.rowid = ".$socid;
		$sql.= " ORDER BY sa.nom";
	
		dol_syslog(get_class($this)."::fetch_stagiaire_per_session sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->lines = array();
			$num = $this->db->num_rows($resql);
	
			$i = 0;
			while( $i < $num)
			{
				$obj = $this->db->fetch_object($resql);
	
				$line = new AgfTraineeSessionLine();
	
				$line->stagerowid = $obj->rowid;
				$line->sessid = $obj->sessid;
				$line->id = $obj->fk_stagiaire;
				$line->nom = $obj->nom;
				$line->prenom = $obj->prenom;
				$line->civilite = $obj->civilite;
				$line->civilitel = $obj->civilitel;
				$line->socname = $obj->socname;
				$line->socid = $obj->socid;
				$line->typeid = $obj->typeid;
				$line->place_birth = $obj->place_birth;
				if (empty($obj->date_birth)) {
					$line->date_birth = $this->db->jdate($obj->birthday);
				}else {
					$line->date_birth = $this->db->jdate($obj->date_birth);
				}
	
				$line->type = $obj->type;
				$line->fk_socpeople=$obj->fk_socpeople;
				if (empty($obj->stamail)) {
					$line->email = $obj->socpemail;
				} else {
					$line->email = $obj->mail;
				}
				$line->fk_agefodd_stagiaire_type=$obj->fk_agefodd_stagiaire_type;
	
				$this->lines[$i]=$line;
	
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
	 *  Create object (trainee in session) into database
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
		$this->fk_session_agefodd = $this->db->escape(trim($this->fk_session_agefodd));
		$this->fk_stagiaire = $this->db->escape(trim($this->fk_stagiaire));
		$this->fk_agefodd_stagiaire_type = $this->db->escape(trim($this->fk_agefodd_stagiaire_type));
	
		// Check parameters
		// Put here code to add control on parameters value
		if (!$conf->global->AGF_USE_STAGIAIRE_TYPE)
		{
			$this->fk_agefodd_stagiaire_type=$conf->global->AGF_DEFAULT_STAGIAIRE_TYPE;
		}
	
		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_session_stagiaire (";
		$sql.= "fk_session_agefodd, fk_stagiaire, fk_agefodd_stagiaire_type, fk_user_author,fk_user_mod, datec";
		$sql.= ") VALUES (";
		$sql.= $this->fk_session_agefodd.', ';
		$sql.= $this->fk_stagiaire.', ';
		$sql.= $this->fk_agefodd_stagiaire_type.', ';
		$sql.= $user->id.",";
		$sql.= $user->id.",";
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
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."agefodd_session_stagiaire");
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
				
			$session = new Agsession($this->db);
			$session->fetch($this->fk_session_agefodd);
			if (empty($session->force_nb_stagiaire)) {
				$this->fetch_stagiaire_per_session($this->fk_session_agefodd);
				$session->nb_stagiaire=count($this->lines);
				$session->update($user);
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
	 *  Delete object (trainne in session) in database
	 *
	 *	@param  int		$id        trainee to delete
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	 int					 <0 if KO, >0 if OK
	 */
	function delete($user,$notrigger=0)
	{
		$this->fetch($this->id);
		
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_session_stagiaire";
		$sql .= " WHERE rowid = ".$this->id;
	
		dol_syslog(get_class($this)."::delete sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query ($sql);
	
		if ($resql)
		{
			$session = new Agsession($this->db);
			$session->fetch($this->fk_session_agefodd);
			if (empty($session->force_nb_stagiaire)) {
				$this->fetch_stagiaire_per_session($this->fk_session_agefodd);
				$session->nb_stagiaire=count($this->lines);
				$session->update($user);
			}
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}
	
	/**
	 *  Update object (trainee in session) into database
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
		$this->fk_session_agefodd = $this->db->escape(trim($this->fk_session_agefodd));
		$this->fk_stagiaire = $this->db->escape(trim($this->fk_stagiaire));
		$this->fk_agefodd_stagiaire_type = $this->db->escape(trim($this->fk_agefodd_stagiaire_type));
	
		// Check parameters
		// Put here code to add control on parameters values
		// Check parameters
		// Put here code to add control on parameters value
		if (!$conf->global->AGF_USE_STAGIAIRE_TYPE)
		{
			$this->stagiaire_type=$conf->global->AGF_DEFAULT_STAGIAIRE_TYPE;
		}
	
		// Update request
		if (!isset($this->archive)) $this->archive = 0;
		$sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_session_stagiaire SET";
		$sql.= " fk_session_agefodd=".(isset($this->fk_session_agefodd)?$this->fk_session_agefodd:"null").",";
		$sql.= " fk_stagiaire=".(isset($this->fk_stagiaire)?$this->fk_stagiaire:"null").",";
		$sql.= " fk_user_mod=".$user->id.",";
		$sql.= " fk_agefodd_stagiaire_type=".(isset($this->fk_agefodd_stagiaire_type)?$this->fk_agefodd_stagiaire_type:"0");
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

}

/**
 *	Session Trainee Link Class
 */
class AgfTraineeSessionLine
{
	var $stagerowid;
	var $sessid;
	var $id;
	var $nom;
	var $prenom;
	var $civilite;
	var $civilitel;
	var $socname;
	var $socid;
	var $typeid;
	var $type;
	var $email;
	var $fk_socpeople;
	var $date_birth;
	var $place_birth;
	var $fk_agefodd_stagiaire_type;

	function __construct()
	{
		return 1;
	}
}