<?php
/* Copyright (C) 2007-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2012       Florian Henry       <florian.henry@open-concept.pro>
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

require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");

/**
 *	\class		Agefodd
 *	\brief		Module Agefodd class
 */
class Agefodd extends CommonObject
{
	var $db;
	var $error;
	var $errors=array();
	var $element='agefodd';
	var $table_element='agefodd_formation_catalogue';
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
		$this->intitule = $this->db->escape($this->intitule);
		$this->public = $this->db->escape($this->public);
		$this->methode = $this->db->escape($this->methode);
		$this->prerequis = $this->db->escape($this->prerequis);
		$this->but = $this->db->escape($this->but);
		$this->note1 = $this->db->escape($this->note1);
		$this->note2 = $this->db->escape($this->note2);


		// Check parameters
		// Put here code to add control on parameters value

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_formation_catalogue(";
		$sql.= "datec, ref, intitule, duree, public, methode, prerequis, but, programme, note1, note2, fk_user_author";
		$sql.= ") VALUES (";
	  	$sql.= $this->db->idate(dol_now()).', ';
		$sql.= '"'.$this->ref_interne.'", ';
		$sql.= '"'.$this->intitule.'", ';
		$sql.= '"'.$this->duree.'", ';
		$sql.= '"'.$this->public.'",';
		$sql.= '"'.$this->methode.'",';
		$sql.= '"'.$this->prerequis.'",';
		$sql.= '"'.$this->but.'",';
		$sql.= '"'.$this->programme.'",';
		$sql.= '"'.$this->note1.'",';
		$sql.= '"'.$this->note2.'",';
		$sql.= '"'.$user->id.'"';
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
	$sql.= " c.rowid, c.ref, c.intitule, c.duree,";
	$sql.= " c.public, c.methode, c.prerequis, but, c.programme, c.archive, c.note1, c.note2 ";
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
			$this->ref = $obj->rowid; //use for next prev ref
			$this->ref_interne = $obj->ref;
			$this->intitule = stripslashes($obj->intitule);
			$this->duree = $obj->duree;
			$this->public = stripslashes($obj->public);
			$this->methode = stripslashes($obj->methode);
			$this->prerequis = stripslashes($obj->prerequis);
			$this->but = stripslashes($obj->but);
			$this->programme = stripslashes($obj->programme);
			$this->note1 = stripslashes($obj->note1);
			$this->note2 = stripslashes($obj->note2);
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
		$sql.= " c.rowid, c.datec, c.tms, c.fk_user_author, c.fk_user_mod ";
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
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
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
	*      \param      notrigger	    0=launch triggers after, 1=disable triggers
	*      \return     int         	<0 if KO, >0 if OK
	*/
	function update($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		// Clean parameters
		$this->intitule = $this->db->escape($this->intitule);
		$this->public = $this->db->escape($this->public);
		$this->methode = $this->db->escape($this->methode);
		$this->prerequis = $this->db->escape($this->prerequis);
		$this->but = $this->db->escape($this->but);
		$this->programme = $this->db->escape($this->programme);
		$this->note1 = $this->db->escape($this->note1);
		$this->note2 = $this->db->escape($this->note2);

		// Check parameters
		// Put here code to add control on parameters values
		// Update request
		if (!isset($this->archive)) $this->archive = 0;
		$sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c SET";
		$sql.= " c.ref='".$this->ref_interne."',";
		$sql.= " c.intitule='".$this->intitule."',";
		$sql.= " c.duree='".$this->duree."',";
		$sql.= " c.public='".$this->public."',";
		$sql.= " c.methode='".$this->methode."',";
		$sql.= " c.prerequis='".$this->prerequis."',";
		$sql.= " c.but='".$this->but."',";
		$sql.= " c.programme='".$this->programme."',";
		$sql.= " c.note1='".$this->note1."',";
		$sql.= " c.note2='".$this->note2."',";
		$sql.= " c.fk_user_mod='".$user->id."',";
		$sql.= " c.archive=".$this->archive;
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
	*      \brief      Create in database
	*      \param      user        	User that create
	*      \param      notrigger	0=launch triggers after, 1=disable triggers
	*      \return     int         	<0 if KO, Id of created object if OK
	*/
	function create_objpeda($user,$notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		// Clean parameters
		$this->intitule = $this->db->escape($this->intitule);

		// Check parameters
		// Put here code to add control on parameters value

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_formation_objectifs_peda(";
		$sql.= "fk_formation_catalogue, intitule, priorite, fk_user_author";
		$sql.= ") VALUES (";
        $sql.= '"'.$this->fk_formation_catalogue.'", ';
        $sql.= '"'.$this->intitule.'", ';
        $sql.= '"'.$this->priorite.'", ';
        $sql.= '"'.$user->id.'"';
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
		$sql.= " o.rowid, o.intitule, o.priorite, o.fk_formation_catalogue, o.tms, o.fk_user_author";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_formation_objectifs_peda AS o";
		$sql.= " WHERE o.fk_formation_catalogue = ".$id_formation;
		$sql.= " ORDER BY o.priorite ASC";

		dol_syslog(get_class($this)."::fetch_objpeda_per_formation sql=".$sql, LOG_DEBUG);
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
			return $num;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_objpeda_per_formation ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	*      \brief      Update database
	*      \param      user        	User that modify
	*      \param      notrigger	0=launch triggers after, 1=disable triggers
	*      \return     int         	<0 if KO, >0 if OK
	*/
	function update_objpeda($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		// Clean parameters
		$this->intitule = $this->db->escape($this->intitule);

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_formation_objectifs_peda as o SET";
		$sql.= " o.fk_formation_catalogue='".$this->fk_formation_catalogue."',";
		$sql.= " o.intitule='".$this->intitule."',";
		$sql.= " o.fk_user_mod='".$user->id."',";
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

    /**
     *	Initialise object with example values
     *	Id must be 0 if object instance is a specimen
     *
     *	@return	void
     */
    function initAsSpecimen()
    {
    	$this->id=0;
    	$this->ref = '';
    	$this->intitule = '';
    	$this->duree = '';
    	$this->public = '';
    	$this->methode = '';
    	$this->prerequis = '';
    	$this->programme = '';
    	$this->archive = '';
    }


    /**
     *      Return description of training
     *
     *		@return	string					HTML translated description
     */
    function getToolTip()
    {
    	global $conf;

    	$langs->load("admin");
    	$langs->load("agefodd@agefodd");

    	$s='';
    	if (type=='trainning')
    	{
    		$s.='<b>'.$langs->trans("AgfTraining").'</b>:<u>'.$this->intitule.':</u><br>';
    		$s.='<br>';
    		$s.=$langs->trans("AgfDuree").' : '.$this->duree.' H <br>';
    		$s.=$langs->trans("AgfPublic").' : '.$this->public.'<br>';
    		$s.=$langs->trans("AgfMethode").' : '.$this->methode.'<br>';

    		$s.='<br>';
    	}
    	return $s;
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
    function fetch_all($sortorder, $sortfield, $limit, $offset, $arch=0)
    {
    	global $langs;

    	$sql = "SELECT c.rowid, c.intitule, c.ref, c.datec, c.duree,";
		$sql.= " (SELECT MAX(sess1.datef) FROM ".MAIN_DB_PREFIX."agefodd_session as sess1 WHERE sess1.fk_formation_catalogue=c.rowid AND sess1.archive=1) as lastsession,";
		$sql.= " (SELECT count(rowid) FROM ".MAIN_DB_PREFIX."agefodd_session as sess WHERE sess.fk_formation_catalogue=c.rowid AND sess.archive=1) as nbsession,";
		$sql.= " a.dated";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session as a";
		$sql.= " ON c.rowid = a.fk_formation_catalogue";
		$sql.= " WHERE c.archive LIKE ".$arch;
		$sql.= " GROUP BY c.ref";
		$sql.= " ORDER BY $sortfield $sortorder " . $this->db->plimit( $limit + 1 ,$offset);

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
    				$this->line[$i]->intitule = $obj->intitule;
    				$this->line[$i]->ref = $obj->ref;
    				$this->line[$i]->datec = $this->db->jdate($obj->datec);
    				$this->line[$i]->duree = $obj->duree;
    				$this->line[$i]->lastsession = $obj->lastsession;
    				$this->line[$i]->nbsession = $obj->nbsession;

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





# $Date: 2010-03-28 19:06:42 +0200 (dim. 28 mars 2010) $ - $Revision: 51 $
?>
