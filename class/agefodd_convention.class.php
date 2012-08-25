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

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 *	\class		Agefodd_convention
 *	\brief		Module Agefodd class
 */
class Agefodd_convention
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
		$this->intro1 = $this->db->escape($this->intro1);
		$this->intro2 = $this->db->escape($this->intro2);
		$this->art1 = $this->db->escape($this->art1);
		$this->art2 = $this->db->escape($this->art2);
		$this->art3 = $this->db->escape($this->art3);
		$this->art4 = $this->db->escape($this->art4);
		$this->art5 = $this->db->escape($this->art5);
		$this->art6 = $this->db->escape($this->art6);
		$this->art7 = $this->db->escape($this->art7);
		$this->art8 = $this->db->escape($this->art8);
		$this->sig = $this->db->escape($this->sig);
		$this->notes = $this->db->escape($this->notes);		
		
		// Check parameters
		// Put here code to add control on parameters value
		
		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_convention(";
		$sql.= "fk_agefodd_session, fk_societe, intro1, intro2, art1, art2, art3,";
		$sql.= " art4, art5, art6, art7, art8, sig, notes, fk_user_author, datec";
		$sql.= ") VALUES (";
		$sql.= '"'.$this->sessid.'", ';
		$sql.= '"'.$this->socid.'", ';
		$sql.= '"'.$this->intro1.'", ';
		$sql.= '"'.$this->intro2.'", ';
		$sql.= '"'.$this->art1.'", ';
		$sql.= '"'.$this->art2.'", ';
		$sql.= '"'.$this->art3.'", ';
		$sql.= '"'.$this->art4.'", ';
		$sql.= '"'.$this->art5.'", ';
		$sql.= '"'.$this->art6.'", ';
		$sql.= '"'.$this->art7.'", ';
		$sql.= '"'.$this->art8.'", ';
		$sql.= '"'.$this->sig.'", ';
		$sql.= '"'.$this->notes.'", ';
		$sql.= '"'.$user->id.'", ';
		$sql.= '"'.$this->datec.'"';
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
	*    \brief	Recupére les infos concernant une convention
	*    \param	sessid	int	id session
	*		socid	int	id societe
	*		type	str	état facturation (commande (bc) ou facture (fac))
	*    \return     	int     <0 if KO, >0 if OK
	*/
	function fetch($sessid, $socid, $id=0)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " c.rowid, c.fk_agefodd_session, c.fk_societe, c.intro1, c.intro2,";
		$sql.= " c.art1, c.art2, c.art3, c.art4, c.art5, c.art6, c.art7, c.art8, c.sig, notes, s.nom as socname";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_convention as c";
		$sql.= " LEFT OUTER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid=c.fk_societe";
		if ( $id > 0) $sql.= " WHERE c.rowid = ".$id;
		else 
		{
			$sql.= " WHERE c.fk_agefodd_session = ".$sessid;
			$sql.= " AND c.fk_societe = ".$socid;
		}

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);

		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->sessid = $obj->fk_agefodd_session;
				$this->socid = $obj->fk_societe;
				$this->socname = $obj->socname;
				$this->intro1 = $obj->intro1;
				$this->intro2 = $obj->intro2;
				$this->art1 = $obj->art1;
				$this->art2 = $obj->art2;
				$this->art3 = $obj->art3;
				$this->art4 = $obj->art4;
				$this->art5 = $obj->art5;
				$this->art6 = $obj->art6;
				$this->art7 = $obj->art7;
				$this->art8 = $obj->art8;
				$this->sig = $obj->sig;
				$this->notes = $obj->notes;
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
	*    \brief	Recupére les infos concernant la dernière convention relaisé pour une société donnée
	*    \param	sessid	int	id session
	*		socid	int	id societe
	*		type	str	état facturation (commande (bc) ou facture (fac))
	*    \return     	int     <0 if KO, >0 if OK
	*/
	function fetch_last_conv_per_socity($socid)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " c.rowid, MAX(c.fk_agefodd_session) as sessid";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_convention as c";
		$sql.= " WHERE c.fk_societe = ".$socid;

		dol_syslog(get_class($this)."::fetch_last_conv_per_socity sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);

		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->sessid = $obj->sessid;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_last_conv_per_socity ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	*    \brief	Recupére les infos concernant la commande associée à la convention
	*    \param	commid	int	id commande
	*    \return     	int     <0 if KO, >0 if OK
	*/
	function fetch_commande_lines($comid)
	{
		global $langs;

		$sql = "SELECT";
		$sql.= " c.rowid, c.fk_commande, c.fk_product, c.description, c.tva_tx, c.remise_percent,";
		$sql.= " c.fk_remise_except, c.price, c.qty, c.total_ht, c.total_tva, c.total_ttc";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as c";
		$sql.= " WHERE c.fk_commande = ".$comid;

		dol_syslog(get_class($this)."::fetch_commande_lines sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);

		if ($resql)
		{
			$this->line = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			while( $i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$this->line[$i]->rowid = $obj->rowid;
				$this->line[$i]->fk_product = $obj->fk_product;
				$this->line[$i]->description = $obj->description;
				$this->line[$i]->tva_tx = $obj->tva_tx;
				$this->line[$i]->remise_percent = $obj->remise_percent;
				$this->line[$i]->fk_remise_except = $obj->fk_remise_except;
				$this->line[$i]->price = $obj->price;
				$this->line[$i]->qty = $obj->qty;
				$this->line[$i]->total_ht = $obj->total_ht;
				$this->line[$i]->total_tva = $obj->total_tva;
				$this->line[$i]->total_ttc = $obj->total_ttc;
				$i++;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_commande_lines ".$this->error, LOG_ERR);
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
		$sql.= " f.rowid, f.datec, f.tms, f.fk_user_author, f.fk_user_mod";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_convention as c";
		$sql.= " WHERE f.rowid = ".$id;
		
		dol_syslog(get_class($this)."::info sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
			$obj = $this->db->fetch_object($resql);
			$this->id = $obj->rowid;
			$this->datec = $this->db->jdate($obj->datec);
			$this->date_modification = $this->db->jdate($obj->tms);
			$this->fk_userc = $obj->fk_user_author;
			$this->fk_userm = $obj->fk_user_mod;
			}
			$this->db->free($resql);
		
			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::info ".$this->error, LOG_ERR);
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
		$this->intro1 = $this->db->escape($this->intro1);
		$this->intro2 = $this->db->escape($this->intro2);
		$this->art1 = $this->db->escape($this->art1);
		$this->art2 = $this->db->escape($this->art2);
		$this->art3 = $this->db->escape($this->art3);
		$this->art4 = $this->db->escape($this->art4);
		$this->art5 = $this->db->escape($this->art5);
		$this->art6 = $this->db->escape($this->art6);
		$this->art7 = $this->db->escape($this->art7);
		$this->art8 = $this->db->escape($this->art8);
		$this->sig = $this->db->escape($this->sig);
		$this->notes = $this->db->escape($this->notes);
		
		
		// Check parameters
		// Put here code to add control on parameters values
		
		
		// Update request
		if (!isset($this->archive)) $this->archive = 0; 
		$sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_convention as c SET";
		$sql.= " c.intro1='".$this->intro1."',";
		$sql.= " c.intro2='".$this->intro2."',";
		$sql.= " c.art1='".$this->art1."',";
		$sql.= " c.art2='".$this->art2."',";
		$sql.= " c.art3='".$this->art3."',";
		$sql.= " c.art4='".$this->art4."',";
		$sql.= " c.art5='".$this->art5."',";
		$sql.= " c.art6='".$this->art6."',";
		$sql.= " c.art7='".$this->art7."',";
		$sql.= " c.art8='".$this->art8."',";
		$sql.= " c.sig='".$this->sig."',";
		$sql.= " c.notes='".$this->notes."',";
		$sql.= " c.fk_societe='".$this->socid."',";
		$sql.= " c.fk_agefodd_session='".$this->sessid."',";
		$sql.= " c.fk_user_mod='".$user->id."'";
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
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_convention";
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
