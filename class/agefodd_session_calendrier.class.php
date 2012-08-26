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
 *	\file		$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/agsession.class.php $
 *	\ingroup	agefodd
 *	\brief		CRUD class file (Create/Read/Update/Delete) for agefodd module
 *	\version	$Id$
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
	function __construct($DB) 
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
		
		if ($conf->global->AGF_DOL_AGENDA) {
			$result = $this->createAction($user);
			if ($result <= 0){
				$error++; $this->errors[]="Error ".$this->db->lasterror();
			}
			else {
				$this->fk_actioncomm=$result;
			}
		}
		
		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_session_calendrier(";
		$sql.= "fk_agefodd_session, date_session, heured, heuref,fk_actioncomm, fk_user_author, datec";
		$sql.= ") VALUES (";
		$sql.= '"'.$this->sessid.'", ';
		$sql.= $this->db->idate($this->date_session).', ';
		$sql.= $this->db->idate($this->heured).', ';
		$sql.= $this->db->idate($this->heuref).', ';
    	$sql.= " ".(! isset($this->fk_actioncomm)?'NULL':"'".$this->db->escape($this->fk_actioncomm)."'").",";
		$sql.= '"'.$user->id.'", ';
		$sql.= $this->db->idate(dol_now());
		$sql.= ")";
	
		$this->db->begin();
		
		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		if (! $error)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."agefodd_session_calendrier");
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
	 *  @param	int		$actionid    Id object
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		global $langs;
					
		$sql = "SELECT";
		$sql.= " s.rowid, s.date_session, s.heured, s.heuref, s.fk_actioncomm, s.fk_agefodd_session ";
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
				$this->date_session = $this->db->jdate($obj->date_session);
				$this->heured = $this->db->jdate($obj->heured);
				$this->heuref = $this->db->jdate($obj->heuref);
				$this->sessid = $obj->fk_agefodd_session;
				$this->fk_actioncomm = $obj->fk_actioncomm;
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
	 *  @param	int		$actionid    Id object
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function fetch_by_action($actionid)
	{
		global $langs;
			
		$sql = "SELECT";
		$sql.= " s.rowid, s.date_session, s.heured, s.heuref, s.fk_actioncomm, s.fk_agefodd_session ";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_calendrier as s";
		$sql.= " WHERE s.fk_actioncomm = ".$actionid;
	
		dol_syslog(get_class($this)."::fetch_by_action sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->date_session = $this->db->jdate($obj->date_session);
				$this->heured = $this->db->jdate($obj->heured);
				$this->heuref = $this->db->jdate($obj->heuref);
				$this->sessid = $obj->fk_agefodd_session;
				$this->fk_actioncomm = $obj->fk_actioncomm;
			}
			$this->db->free($resql);
	
			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_by_action ".$this->error, LOG_ERR);
			return -1;
		}
	}
	

	/**
	*    \brief	Récupére le calendrier d'une session (les blocs horaires)
	*    \param	id	id session
	*    \return     int     <0 if KO, >0 if OK
	*/
	function fetch_all($id)
	{
		global $langs;
					
		$sql = "SELECT";
		$sql.= " s.rowid, s.date_session, s.heured, s.heuref";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_calendrier as s";
		$sql.= " WHERE s.fk_agefodd_session = ".$id;
		$sql.= " ORDER BY s.date_session ASC, s.heured ASC";

		
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
				$this->line[$i]->date_session = $this->db->jdate($obj->date_session);
				$this->line[$i]->heured = $this->db->jdate($obj->heured);
				$this->line[$i]->heuref = $this->db->jdate($obj->heuref);
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
	function update($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;
		
		// Clean parameters
		
		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		if (!isset($this->archive)) $this->archive = 0; 
		$sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_session_calendrier SET";
		$sql.= " date_session=".$this->db->idate($this->date_session).",";
		$sql.= " heured=".$this->db->idate($this->heured).",";
		$sql.= " heuref=".$this->db->idate($this->heuref).",";
		$sql.= " fk_user_mod='".$user->id."'";
		$sql.= " WHERE rowid = ".$this->id;
		
		
		
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
			
				//Update Action is needed
				if (!empty($this->fk_actioncomm) && $conf->global->AGF_DOL_AGENDA) {
					$result = $this->updateAction($user);
					if ($result==-1) {
						$error++; $this->errors[]="Error ".$this->db->lasterror();
					}
				}
			
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
		$result = $this->fetch($id);
		if (!empty($this->fk_actioncomm)) {
			dol_include_once('/comm/action/class/actioncomm.class.php');
			
			$action = new ActionComm($this->db);
			$action->id=$this->fk_actioncomm;
			$action->delete();
		}
		
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
     
     /**
      *  Create Action in Dolibarr Agenda
      *
      *  @param	int			fk_session_place    Location of session
      *  @param	User	$user        User that modify
      */
     function createAction($user){

     	global $conf, $langs;

     	$error = 0;

     	dol_include_once('/comm/action/class/actioncomm.class.php');
     	dol_include_once('/agefodd/class/agsession.class.php');

     	$action = new ActionComm($this->db);
     	$session = new Agsession($this->db);
     	
     	$result = $session->fetch($this->sessid);
     	if ($result < 0) {
     		$error ++;
     	}

     	$action->label =  $session->formintitule.'('.$session->formref.')';
     	$action->location =  $session->placecode;
     	$action->datep = $this->heured;
     	$action->datef = $this->heuref;
     	$action->author      = $user;   // User saving action
     	$action->userdone    = $user;	// User doing action
     	$action->fk_element  = $session->id;
     	$action->elementtype = $session->element;
     	$action->type_code = 'AC_AGF_SESS';

     	if ($error == 0) {
     		$result = $action->add($user);
     		 
     		if ($result < 0) {
     			$error ++;
     			dol_syslog(get_class($this)."::createAction ".$action->error, LOG_ERR);
     			return -1;
     		}
     		else {
     			return $result;
     		}
     	}
     	else {
     		dol_syslog(get_class($this)."::createAction ".$action->error, LOG_ERR);
     		return -1;
     	}
     }

     /**
      *  update Action in Dolibarr Agenda
      *
      *  @param	User	$user        User that modify
      */
     function updateAction($user){

     	global $conf, $langs;

     	$error = 0;

     	dol_include_once('/comm/action/class/actioncomm.class.php');
     	dol_include_once('/agefodd/class/agsession.class.php');

     	$action = new ActionComm($this->db);
     	$session = new Agsession($this->db);
     	
     	$result = $session->fetch($this->sessid);
     	if ($result < 0) {
     		$error ++;
     	}
     	
     	$result = $action->fetch($this->fk_actioncomm);

     	if ($error == 0) {
     		 
     		if ($action->id==$this->fk_actioncomm){

		     	$action->label =  $session->formintitule.'('.$session->formref.')';
		     	$action->location =  $session->placecode;
		     	$action->datep = $this->heured;
		     	$action->datef = $this->heuref;
		     	$action->type_code = 'AC_AGF_SESS';
     			
     			$result = $action->update($user);
     		}
     		else {
     			$result = $this->createAction($user);
     		}

     		if ($result < 0) {
     			$error ++;

     			dol_syslog(get_class($this)."::updateAction ".$action->error, LOG_ERR);
     			return -1;
     		}
     		else {
     			return 1;
     		}
     	}
     	else {
     		dol_syslog(get_class($this)."::updateAction ".$action->error, LOG_ERR);
     		return -1;
     	}
     }

}

# $Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $
?>
