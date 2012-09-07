<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012       Florian Henry   <florian.henry@open-concept.pro>
 * Copyright (C) 2012		JF FERRY	<jfefe@aternatik.fr>
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
 *	\file		/agefodd/session/agsession.class.php
 *	\ingroup	agefodd
 *	\brief		CRUD class file (Create/Read/Update/Delete) for agefodd module
 *	\version	$Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 *	\class		Agefodd
 *	\brief		Module Agefodd class
 */
class Agsession extends CommonObject
{
	var $db;
	var $error;
	var $errors=array();
	var $element='agefodd_agsession';
	var $table_element='agefodd_session';
    var $id;
    var $fk_soc;
    var $client;
    var $socid;
    var $fk_formation_catalogue;
    var $fk_session_place;
    var $nb_place;
    var $nb_stagiaire;
    var $force_nb_stagiaire;
    var $type_session;	// type formation entreprise : 0 intra  / 1 inter
    var $dated='';
    var $datef='';
    var $notes;
    var $color;
    var $cost_trainer;
    var $cost_site;
    var $cost_trip;
    var $sell_price;
    var $date_res_site='';
    var $is_date_res_site;
    var $date_res_trainer='';
    var $is_date_res_trainer;
    var $date_ask_OPCA='';
    var $is_date_ask_OPCA;
    var $is_OPCA;
    var $fk_soc_OPCA;
    var $soc_OPCA_name;
    var $fk_socpeople_OPCA;
    var $contact_name_OPCA;
    var $num_OPCA_soc;
    var $num_OPCA_file;
    var $fk_user_author;
    var $datec='';
    var $fk_user_mod;
    var $tms='';
    var $archive;
    var $line;
    var $commercialid;
    var $commercialname;
    var $contactid;
    var $contactname;
    var $sourcecontactid;
    var $fk_actioncomm;

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

    	if (isset($this->fk_formation_catalogue)) $this->fk_formation_catalogue=trim($this->fk_formation_catalogue);
    	if (isset($this->fk_session_place)) $this->fk_session_place=trim($this->fk_session_place);
    	if (isset($this->fk_soc)) $this->fk_soc=trim($this->fk_soc);
    	if (isset($this->nb_place)) $this->nb_place=trim($this->nb_place);
    	if (isset($this->notes)) $this->notes=trim($this->notes);

    	// Check parameters
    	// Put here code to add control on parameters values

    	// Insert request
    	$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_session(";

    	$sql.= "fk_soc,";
    	$sql.= "fk_formation_catalogue,";
    	$sql.= "fk_session_place,";
    	$sql.= "nb_place,";
    	$sql.= "type_session,";
    	$sql.= "dated,";
    	$sql.= "datef,";
    	$sql.= "notes,";
    	$sql.= "fk_user_author,";
    	$sql.= "datec,";
    	$sql.= "fk_user_mod";
    	$sql.= ") VALUES (";
    	$sql.= " ".(! isset($this->fk_soc)?'NULL':"'".$this->fk_soc."'").",";
    	$sql.= " ".(! isset($this->fk_formation_catalogue)?'NULL':"'".$this->fk_formation_catalogue."'").",";
    	$sql.= " ".(! isset($this->fk_session_place)?'NULL':"'".$this->fk_session_place."'").",";
    	$sql.= " ".(! isset($this->nb_place)?'NULL':"'".$this->nb_place."'").",";
    	$sql.= " ".(! isset($this->type_session)?'0':"'".$this->type_session."'").",";
    	$sql.= " ".(! isset($this->dated) || dol_strlen($this->dated)==0?'NULL':$this->db->idate($this->dated)).",";
    	$sql.= " ".(! isset($this->datef) || dol_strlen($this->datef)==0?'NULL':$this->db->idate($this->datef)).",";
    	$sql.= " ".(! isset($this->notes)?'NULL':"'".$this->db->escape($this->notes)."'").",";
    	$sql.= " ".$this->db->escape($user->id).",";
    	$sql.= " ".$this->db->idate(dol_now()).",";
    	$sql.= " ".$this->db->escape($user->id);

    	$sql.= ")";

    	$this->db->begin();

    	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
    	$resql=$this->db->query($sql);
    	if (! $resql) {
    		$error++; $this->errors[]="Error ".$this->db->lasterror();
    	}

    	if (! $error)
    	{
    		$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."agefodd_session");
    		//Create or update line in session commercial table and get line number
    		if (!empty($this->commercialid))
    		{
    			$result = $this->setCommercialSession($this->commercialid,$user);
    			if ($result <= 0){
    				$error++; $this->errors[]="Error ".$this->db->lasterror();
    			}
    		}

    		//Create or update line in session contact table and get line number
    		if ($conf->global->AGF_CONTACT_DOL_SESSION)	{
    			$contactid = $this->sourcecontactid;
    		}
    		else {
    			$contactid = $this->contactid;
    		}
    		if ($contactid)
    		{
    			$result = $this->setContactSession($contactid,$user);
    			if ($result <= 0){
    				$error++; $this->errors[]="Error ".$this->db->lasterror();
    			}
    		}

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
     *  Create object (trainee in session) into database
     *
     *  @param	User	$user        User that create
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
    function create_stag_in_session($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
		$this->sessid = $this->db->escape(trim($this->sessid));

		// Check parameters
		// Put here code to add control on parameters value
		if (!$conf->global->AGF_USE_STAGIAIRE_TYPE)
		{
			$this->stagiaire_type=$conf->global->AGF_DEFAULT_STAGIAIRE_TYPE;
		}

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_session_stagiaire (";
		$sql.= "fk_session_agefodd, fk_stagiaire, fk_agefodd_stagiaire_type, fk_user_author, datec";
		$sql.= ") VALUES (";
		$sql.= '"'.$this->sessid.'", ';
		$sql.= '"'.$this->stagiaire.'", ';
		$sql.= '"'.$this->stagiaire_type.'", ';
		$sql.= '"'.$user->id.'", ';
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
     *  Load object in memory from database
     *
     *  @param	int		$id    Id object
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;

    	$sql = "SELECT";
    	$sql.= " t.rowid,";
    	$sql.= " t.fk_soc,";
    	$sql.= " t.fk_formation_catalogue,";
    	$sql.= " c.intitule as formintitule,";
    	$sql.= " c.rowid as formid,";
    	$sql.= " c.ref as formref,";
    	$sql.= " c.duree,";
    	$sql.= " t.fk_session_place,";
    	$sql.= " t.nb_place,";
    	$sql.= " t.nb_stagiaire,";
    	$sql.= " t.force_nb_stagiaire,";
    	$sql.= " t.type_session,";
    	$sql.= " t.dated,";
    	$sql.= " t.datef,";
    	$sql.= " t.notes,";
    	$sql.= " t.color,";
    	$sql.= " t.cost_trainer,";
    	$sql.= " t.cost_site,";
    	$sql.= " t.cost_trip,";
    	$sql.= " t.sell_price,";
    	$sql.= " t.date_res_site,";
    	$sql.= " t.is_date_res_site,";
    	$sql.= " t.date_res_trainer,";
    	$sql.= " t.is_date_res_trainer,";
    	$sql.= " t.date_ask_OPCA,";
    	$sql.= " t.is_date_ask_OPCA,";
    	$sql.= " t.is_OPCA,";
    	$sql.= " t.fk_soc_OPCA,";
    	$sql.= " t.fk_socpeople_OPCA,";
    	$sql.= " concactOPCA.name as concactOPCAname, concactOPCA.firstname as concactOPCAfirstname,";
    	$sql.= " t.num_OPCA_soc,";
    	$sql.= " t.num_OPCA_file,";
    	$sql.= " t.fk_user_author,";
    	$sql.= " t.datec,";
    	$sql.= " t.fk_user_mod,";
    	$sql.= " t.tms,";
    	$sql.= " t.archive,";
    	$sql.= " p.rowid as placeid, p.ref_interne as placecode,";
    	$sql.= " us.name as commercialname, us.firstname as commercialfirstname, ";
    	$sql.= " com.fk_user_com as commercialid, ";
    	$sql.= " socp.name as contactname, socp.firstname as contactfirstname, ";
    	$sql.= " agecont.fk_socpeople as sourcecontactid, ";
    	$sql.= " agecont.rowid as contactid, ";
    	$sql.= " socOPCA.address as OPCA_adress, socOPCA.cp as OPCA_cp, socOPCA.ville as OPCA_ville, ";
    	$sql.= " socOPCA.nom as soc_OPCA_name ";

    	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session as t";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c";
    	$sql.= " ON c.rowid = t.fk_formation_catalogue";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_place as p";
    	$sql.= " ON p.rowid = t.fk_session_place";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_stagiaire as ss";
    	$sql.= " ON ss.fk_session_agefodd = c.rowid";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_commercial as com";
    	$sql.= " ON com.fk_session_agefodd = t.rowid";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as us";
    	$sql.= " ON com.fk_user_com = us.rowid";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_contact as scont";
    	$sql.= " ON scont.fk_session_agefodd = t.rowid";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_contact as agecont";
    	$sql.= " ON agecont.rowid = scont.fk_agefodd_contact";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as socp ";
		$sql.= " ON agecont.fk_socpeople = socp.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as socOPCA ";
		$sql.= " ON t.fk_soc_OPCA = socOPCA.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as concactOPCA ";
		$sql.= " ON t.fk_socpeople_OPCA = concactOPCA.rowid";
    	$sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		if ($this->db->num_rows($resql))
    		{
    			$obj = $this->db->fetch_object($resql);

    			$this->id    = $obj->rowid;
    			$this->ref    = $obj->rowid; // Use for next prev ref
    			$this->fk_soc    = $obj->fk_soc; // don't work with fetch_thirdparty()
    			$this->socid    = $obj->fk_soc; // work with fetch_thirdparty()
    			$this->fk_formation_catalogue = $obj->fk_formation_catalogue;
    			$this->formintitule = $obj->formintitule;
    			$this->formid = $obj->formid;
    			$this->formref = $obj->formref;
    			$this->duree = $obj->duree;
    			$this->fk_session_place = $obj->fk_session_place;
    			$this->nb_place = $obj->nb_place;
    			$this->nb_stagiaire = $obj->nb_stagiaire;
    			$this->force_nb_stagiaire = $obj->force_nb_stagiaire;
    			$this->type_session = $obj->type_session;
    			$this->placeid = $obj->placeid;
    			$this->placecode = $obj->placecode;
    			$this->dated = $this->db->jdate($obj->dated);
    			$this->datef = $this->db->jdate($obj->datef);
    			$this->notes = $obj->notes;
    			$this->color = $obj->color;
    			$this->fk_commercial = $obj->fk_commercial;
    			$this->fk_contact_client = $obj->fk_contact_client;
    			$this->cost_trainer = $obj->cost_trainer;
    			$this->cost_site = $obj->cost_site;
    			$this->cost_trip = $obj->cost_trip;
    			$this->sell_price = $obj->sell_price;
    			$this->date_res_site = $this->db->jdate($obj->date_res_site);
    			$this->is_date_res_site = $obj->is_date_res_site;
    			$this->date_res_trainer = $this->db->jdate($obj->date_res_trainer);
    			$this->is_date_res_trainer = $obj->is_date_res_trainer;
    			$this->date_ask_OPCA = $this->db->jdate($obj->date_ask_OPCA);
    			$this->is_date_ask_OPCA = $obj->is_date_ask_OPCA;
    			$this->is_OPCA = $obj->is_OPCA;
    			$this->fk_soc_OPCA = $obj->fk_soc_OPCA;
    			$this->soc_OPCA_name = $obj->soc_OPCA_name;
    			$this->OPCA_adress = $obj->OPCA_adress."\n". $obj->OPCA_cp.' - '. $obj->OPCA_ville;
    			$this->fk_socpeople_OPCA = $obj->fk_socpeople_OPCA;
    			$this->contact_name_OPCA = $obj->concactOPCAname.' '.$obj->concactOPCAfirstname;
    			$this->num_OPCA_soc = $obj->num_OPCA_soc;
    			$this->num_OPCA_file = $obj->num_OPCA_file;
    			$this->fk_user_author = $obj->fk_user_author;
    			$this->datec = $this->db->jdate($obj->datec);
    			$this->fk_user_mod = $obj->fk_user_mod;
    			$this->tms = $this->db->jdate($obj->tms);
    			$this->archive = $obj->archive;
    			$this->commercialname = $obj->commercialname.' '.$obj->commercialfirstname;
    			$this->commercialid=$obj->commercialid;
    			$this->contactname = $obj->contactname.' '.$obj->contactfirstname;
    			$this->sourcecontactid=$obj->sourcecontactid;
    			$this->contactid=$obj->contactid;
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
     *  Load object (all trainee for one session) in memory from database
     *
     *  @param	int		$id    Id object
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
		$sql.= " st.rowid as typeid, st.intitule as type";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."agefodd_session_stagiaire as ss";
		$sql.= " ON s.rowid = ss.fk_session_agefodd";
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
     *  Load object (company per session) in memory from database
     *
     *  @param	int		$id    Id object
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch_societe_per_session($id)
    {
    	global $langs;

		$sql = "SELECT";
		$sql.= " DISTINCT so.rowid as socid,";
		$sql.= " s.rowid, so.nom as socname ";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_stagiaire as ss";
		$sql.= " ON s.rowid = ss.fk_session_agefodd";
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
     *  Load object (information) in memory from database
     *
     *  @param	int		$id    Id object
     *  @return int          	<0 if KO, >0 if OK
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
     *  Update only archive session into database
     *
     *  @param	User	$user        User that modify
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function updateArchive($user, $notrigger=0)
    {
    	global $conf, $langs;
    	$error=0;

    	// Update request
    	$sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_session SET";
    	$sql.= " fk_user_mod=".$this->db->escape($user->id).",";
    	$sql.= " archive=".(isset($this->archive)?$this->archive:"0")."";
    	$sql.= " WHERE rowid=".$this->id;

    	$this->db->begin();

    	dol_syslog(get_class($this)."::updateArchive sql=".$sql, LOG_DEBUG);
    	$resql = $this->db->query($sql);
    	if (! $resql) {
    		dol_syslog(get_class($this)."::updateArchive sql=".$sql, LOG_ERR);
    		$error++; $this->errors[]="Error ".$this->db->lasterror();
    		$this->db->rollback();
    		return -1*$error;
    	}else {
    		$this->db->commit();
    		return 1;
    	}


    }


    /**
     *  Update object into database
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
		if (isset($this->fk_soc)) $this->fk_soc=trim($this->fk_soc);
		if (isset($this->fk_formation_catalogue)) $this->fk_formation_catalogue=trim($this->fk_formation_catalogue);
		if (isset($this->fk_session_place)) $this->fk_session_place=trim($this->fk_session_place);
		if (isset($this->nb_place)) $this->nb_place=trim($this->nb_place);
		if (isset($this->nb_stagiaire)) $this->nb_stagiaire=trim($this->nb_stagiaire);
		if (isset($this->force_nb_stagiaire)) $this->force_nb_stagiaire=trim($this->force_nb_stagiaire);
		if (isset($this->type_session)) $this->type_session=trim($this->type_session);
		if (isset($this->notes)) $this->notes=trim($this->notes);
		if (isset($this->color)) $this->color=trim($this->color);
		if (isset($this->cost_trainer)) $this->cost_trainer=price2num(trim($this->cost_trainer));
		if (isset($this->cost_site)) $this->cost_site=price2num(trim($this->cost_site));
		if (isset($this->cost_trip)) $this->cost_trip=price2num(trim($this->cost_trip));
		if (isset($this->sell_price)) $this->sell_price=price2num(trim($this->sell_price));
		if (isset($this->is_OPCA)) $this->is_OPCA=trim($this->is_OPCA);
		if (isset($this->is_date_res_site)) $this->is_date_res_site=trim($this->is_date_res_site);
		if (isset($this->is_date_res_trainer)) $this->is_date_res_trainer=trim($this->is_date_res_trainer);
		if (isset($this->fk_soc_OPCA)) $this->fk_soc_OPCA=trim($this->fk_soc_OPCA);
		if (isset($this->fk_socpeople_OPCA)) $this->fk_socpeople_OPCA=trim($this->fk_socpeople_OPCA);
		if (isset($this->num_OPCA_soc)) $this->num_OPCA_soc=trim($this->num_OPCA_soc);
		if (isset($this->num_OPCA_file)) $this->num_OPCA_file=trim($this->num_OPCA_file);
		if (isset($this->archive)) $this->archive=trim($this->archive);


		//Create or update line in session commercial table and get line number
		$result = $this->setCommercialSession($this->commercialid,$user);
		if ($result <= 0) {
			$error++; $this->errors[]="Error ".$this->db->lasterror();
		}

		//Create or update line in session contact table and get line number
		$result = $this->setContactSession($this->contactid,$user);
		if ($result <= 0){
			$error++; $this->errors[]="Error ".$this->db->lasterror();
		}

		if ($error==0)
		{
			// Check parameters
			// Put here code to add control on parameters values

	        // Update request
	        $sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_session SET";

	        $sql.= " fk_soc=".(isset($this->fk_soc)?$this->fk_soc:"null").",";
	        $sql.= " fk_formation_catalogue=".(isset($this->fk_formation_catalogue)?$this->fk_formation_catalogue:"null").",";
			$sql.= " fk_session_place=".(isset($this->fk_session_place)?$this->fk_session_place:"null").",";
			$sql.= " nb_place=".(isset($this->nb_place)?$this->nb_place:"null").",";
			$sql.= " nb_stagiaire=".(isset($this->nb_stagiaire)?$this->nb_stagiaire:"null").",";
			$sql.= " force_nb_stagiaire=".(isset($this->force_nb_stagiaire)?$this->force_nb_stagiaire:"0").",";
			$sql.= " type_session=".(isset($this->type_session)?$this->type_session:"null").",";
			$sql.= " dated=".(dol_strlen($this->dated)!=0 ? "'".$this->db->idate($this->dated)."'" : 'null').",";
			$sql.= " datef=".(dol_strlen($this->datef)!=0 ? "'".$this->db->idate($this->datef)."'" : 'null').",";
			$sql.= " notes=".(isset($this->notes)?"'".$this->db->escape($this->notes)."'":"null").",";
			$sql.= " color=".(isset($this->color)?"'".$this->db->escape($this->color)."'":"null").",";
			$sql.= " cost_trainer=".(isset($this->cost_trainer)?$this->cost_trainer:"null").",";
			$sql.= " cost_site=".(isset($this->cost_site)?$this->cost_site:"null").",";
			$sql.= " cost_trip=".(isset($this->cost_trip)?$this->cost_trip:"null").",";
			$sql.= " sell_price=".(isset($this->sell_price)?$this->sell_price:"null").",";
			$sql.= " date_res_site=".(dol_strlen($this->date_res_site)!=0 ? "'".$this->db->idate($this->date_res_site)."'" : 'null').",";
			$sql.= " date_res_trainer=".(dol_strlen($this->date_res_trainer)!=0 ? "'".$this->db->idate($this->date_res_trainer)."'" : 'null').",";
			$sql.= " date_ask_OPCA=".(dol_strlen($this->date_ask_OPCA)!=0 ? "'".$this->db->idate($this->date_ask_OPCA)."'" : 'null').",";
			$sql.= " is_OPCA=".(isset($this->is_OPCA)?$this->is_OPCA:"0").",";
			$sql.= " is_date_res_site=".(isset($this->is_date_res_site)?$this->is_date_res_site:"0").",";
			$sql.= " is_date_res_trainer=".(isset($this->is_date_res_trainer)?$this->is_date_res_trainer:"0").",";
			$sql.= " is_date_ask_OPCA=".(isset($this->is_date_ask_OPCA)?$this->is_date_ask_OPCA:"0").",";
			$sql.= " fk_soc_OPCA=".(isset($this->fk_soc_OPCA) && $this->fk_soc_OPCA!=-1?$this->fk_soc_OPCA:"null").",";
			$sql.= " fk_socpeople_OPCA=".(isset($this->fk_socpeople_OPCA) && $this->fk_socpeople_OPCA!=0?$this->fk_socpeople_OPCA:"null").",";
			$sql.= " num_OPCA_soc=".(isset($this->num_OPCA_soc)?"'".$this->db->escape($this->num_OPCA_soc)."'":"null").",";
			$sql.= " num_OPCA_file=".(isset($this->num_OPCA_file)?"'".$this->db->escape($this->num_OPCA_file)."'":"null").",";
			$sql.= " fk_user_mod=".$this->db->escape($user->id).",";
			$sql.= " archive=".(isset($this->archive)?$this->archive:"0")."";


	        $sql.= " WHERE rowid=".$this->id;

			$this->db->begin();

			dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
	        $resql = $this->db->query($sql);
	    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}
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
     *  Update object (commercial in session) into database
     *
     *  @param	int	    $userid      User commercial to link to session
     *  @param	User	$user        User that modify
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function setCommercialSession($userid,$user)
    {
    	global $conf, $langs;
    	$error=0;
    	$to_create=false;
    	$to_update=false;
    	$to_delete=false;


    	if (empty($userid) || $userid==-1)
    	{
    		$to_delete=true;
    	}
    	else {

	    	$sql = "SELECT com.rowid,com.fk_user_com as commercialid FROM ".MAIN_DB_PREFIX."agefodd_session_commercial as com ";
	    	$sql .= " WHERE com.fk_session_agefodd=".$this->db->escape($this->id);

	    	dol_syslog(get_class($this)."::setCommercialSession sql=".$sql, LOG_DEBUG);
	    	$resql=$this->db->query($sql);
	    	if ($resql) {
	    		if ($this->db->num_rows($resql))
	    		{
	    			$obj = $this->db->fetch_object($resql);
	    			//metre a jour
	    			if ($obj->commercialid!=$userid)	{
	    				$to_update=true;
	    				$fk_commercial = $obj->rowid;
	    			}
	    			else {
	    				$this->commercialid = $obj->commercialid;
	    				$fk_commercial = $obj->rowid;
	    			}
	    		}
	    		else {
	    			//a crée
	    			$to_create=true;
	    		}

	    		$this->db->free($resql);
	    	}
	    	else {
	    		dol_syslog(get_class($this)."::setCommercialSession ".$this->db->lasterror(), LOG_ERR);
	    		return -1;
	    	}
    	}

    	if ($to_update) {

	   		// Update request
	   		$sql = 'UPDATE '.MAIN_DB_PREFIX.'agefodd_session_commercial SET ';
	   		$sql.= ' fk_user_com='.$this->db->escape($userid).',';
	   		$sql.= ' fk_user_mod='.$this->db->escape($user->id);
	   		$sql.= ' WHERE rowid='.$this->db->escape($fk_commercial);

	   		$this->db->begin();

	   		dol_syslog(get_class($this)."::setCommercialSession update sql=".$sql, LOG_DEBUG);
	   		$resql=$this->db->query($sql);
	   		if (! $resql) {
	   			$error++; $this->errors[]="Error ".$this->db->lasterror();
	   		}
   		}

   		if ($to_create) {

	   		// INSERT request
	   		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'agefodd_session_commercial(fk_session_agefodd, fk_user_com, fk_user_author, datec)';
			$sql.= ' VALUES ( ';
			$sql.= $this->db->escape($this->id).',';
			$sql.= $this->db->escape($userid).',';
			$sql.= $this->db->escape($user->id).',';
			$sql.= $this->db->idate(dol_now()).')';

	    	$this->db->begin();

	    	dol_syslog(get_class($this)."::setCommercialSession insert sql=".$sql, LOG_DEBUG);
	    	$resql=$this->db->query($sql);
	    	if (! $resql) {
	    		$error++; $this->errors[]="Error ".$this->db->lasterror();
	    	}
   		}

   		if ($to_delete) {

   			// DELETE request
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_session_commercial";
			$sql .= " WHERE fk_session_agefodd = ".$this->id;

   			$this->db->begin();

   			dol_syslog(get_class($this)."::setCommercialSession delete sql=".$sql, LOG_DEBUG);
   			$resql=$this->db->query($sql);
   			if (! $resql) {
   				$error++; $this->errors[]="Error ".$this->db->lasterror();
   			}
   		}

	    // Commit or rollback
	    if ($error)
	    {
	    	foreach($this->errors as $errmsg)
	    		{
	    			dol_syslog(get_class($this)."::setCommercialSession ".$errmsg, LOG_ERR);
	    			$this->error.=($this->error?', '.$errmsg:$errmsg);
	    		}
	    	$this->db->rollback();
	    	return -1*$error;
	    }
	    elseif ($to_create || $to_update || $to_delete)
	    {
	    	$this->db->commit();
	    	return 1;
	    }
	    else {
	    	return 1;
	    }

    }

    /**
     *  Update object (contact in session) into database
     *
     *  @param	int	    $contactid      User contact to link to session
     *  @param	User	$user       	User that modify
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function setContactSession($contactid,$user)
    {
    	global $conf, $langs;
    	$error=0;
    	$to_create=false;
    	$to_update=false;
    	$to_delete=false;

    	if (empty($contactid) || $contactid==-1)
    	{
    		$to_delete=true;
    	}
    	else {

    		//Contact id can be dolibarr contactid (from llx_socpoeple) or contact of Agefodd (llx_agefodd_contact) according settings
    		if ($conf->global->AGF_CONTACT_DOL_SESSION)
    		{
    			//Test if this dolibarr contact is already a Agefodd contact
    			$sql = "SELECT agecont.rowid FROM ".MAIN_DB_PREFIX."agefodd_contact as agecont ";
    			$sql .= " WHERE agecont.fk_socpeople=".$contactid;

    			dol_syslog(get_class($this)."::setContactSession sql=".$sql, LOG_DEBUG);
    			$resql=$this->db->query($sql);
    			if ($resql) {
    				if ($this->db->num_rows($resql) > 0) {
    					// if exists the contact id to set is the rowid of agefood contact
    					$obj = $this->db->fetch_object($resql);
    					$contactid = $obj->rowid;
    				}
    				else {
    					// We need to create the agefodd contact
    					dol_include_once('/agefodd/class/agefodd_contact.class.php');
    					$contactAgefodd = new Agefodd_contact($this->db);
    					$contactAgefodd->spid = $contactid;
						$result = $contactAgefodd->create($user);
						if ($result > 0)
						{
							$contactid = $result;
						}
						else
						{
							dol_syslog(get_class($this)."::setContactSession Error agefodd_contact".$contactAgefodd->error, LOG_ERR);
							$this->db->free($resql);
    						return -1;
						}
    				}
    			}
    			else {
    				dol_syslog(get_class($this)."::setContactSession Error AGF_CONTACT_DOL_SESSION:".$this->db->lasterror(), LOG_ERR);
    				return -1;
    			}
    		}

    		$sql = "SELECT agecont.rowid,agecont.fk_agefodd_contact as contactid FROM ".MAIN_DB_PREFIX."agefodd_session_contact as agecont ";
    		$sql .= " WHERE agecont.fk_session_agefodd=".$this->db->escape($this->id);

    		dol_syslog(get_class($this)."::setContactSession sql=".$sql, LOG_DEBUG);
    		$resql=$this->db->query($sql);
    		if ($resql) {
    			if ($this->db->num_rows($resql))
    			{
    				$obj = $this->db->fetch_object($resql);
    				//metre a jour
    				if ($obj->contactid!=$contactid)	{
    					$to_update=true;
    					$fk_contact = $obj->rowid;
    				}
    				else {
    					$this->contactid = $obj->contactid;
    					$fk_contact = $obj->rowid;
    				}
    			}
    			else {
    				//a crée
    				$to_create=true;
    			}

    			$this->db->free($resql);
    		}
    		else {
    			dol_syslog(get_class($this)."::setContactSession Error:".$this->db->lasterror(), LOG_ERR);
    			return -1;
    		}
    	}

    	dol_syslog(get_class($this)."::setContactSession to_update:".$to_update.", to_create:".$to_create.", to_delete:".$to_delete, LOG_DEBUG);

    	if ($to_update) {

    		// Update request
    		$sql = 'UPDATE '.MAIN_DB_PREFIX.'agefodd_session_contact SET ';
    		$sql.= ' fk_agefodd_contact='.$this->db->escape($contactid).',';
    		$sql.= ' fk_user_mod='.$this->db->escape($user->id);
    		$sql.= ' WHERE rowid='.$this->db->escape($fk_contact);

    		$this->db->begin();

    		dol_syslog(get_class($this)."::setContactSession update sql=".$sql, LOG_DEBUG);
    		$resql=$this->db->query($sql);
    		if (! $resql) {
    			$error++; $this->errors[]="Error ".$this->db->lasterror();
    		}
    	}

    	if ($to_create) {

    		// INSERT request
    		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'agefodd_session_contact(fk_session_agefodd, fk_agefodd_contact, fk_user_author, datec)';
    		$sql.= ' VALUES ( ';
    		$sql.= $this->db->escape($this->id).',';
    		$sql.= $this->db->escape($contactid).',';
    		$sql.= $this->db->escape($user->id).',';
    		$sql.= $this->db->idate(dol_now()).')';

    		$this->db->begin();

    		dol_syslog(get_class($this)."::setContactSession insert sql=".$sql, LOG_DEBUG);
    		$resql=$this->db->query($sql);
    		if (! $resql) {
    			$error++; $this->errors[]="Error ".$this->db->lasterror();
    		}
    	}

    	if ($to_delete) {

    		// DELETE request
    		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_session_contact";
    		$sql .= " WHERE fk_session_agefodd = ".$this->id;

    		$this->db->begin();

    		dol_syslog(get_class($this)."::setContactSession delete sql=".$sql, LOG_DEBUG);
    		$resql=$this->db->query($sql);
    		if (! $resql) {
    			$error++; $this->errors[]="Error ".$this->db->lasterror();
    		}
    	}

    	// Commit or rollback
    	if ($error)
    	{
    		foreach($this->errors as $errmsg)
    		{
    			dol_syslog(get_class($this)."::setContactSession ".$errmsg, LOG_ERR);
    			$this->error.=($this->error?', '.$errmsg:$errmsg);
    		}
    		$this->db->rollback();
    		return -1*$error;
    	}
    	elseif ($to_create || $to_update || $to_delete)
    	{
    		$this->db->commit();
    		return 1;
    	}
    	else
    	{
    		return 1;
    	}
    }



    /**
     *  Update object (trainee in session) into database
     *
     *  @param	User	$user        User that modify
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function update_stag_in_session($user, $notrigger=0)
    {
		global $conf, $langs;
		$error=0;

		// Clean parameters
		$this->sessid = addslashes(trim($this->sessid));
		$this->stagiaire = addslashes(trim($this->stagiaire));
		$this->type = addslashes(trim($this->type));

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
        $sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_session_stagiaire as s SET";
		$sql.= " s.fk_session_agefodd='".$this->sessid."',";
		$sql.= " s.fk_stagiaire='".$this->stagiaire."',";
        $sql.= " s.fk_user_mod='".$user->id."',";
        $sql.= " s.fk_agefodd_stagiaire_type='".$this->stagiaire_type."',";
        $sql.= " s.fk_agefodd_stagiaire_type='".$this->type."'";
        $sql.= " WHERE s.rowid = ".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update_stag_in_session sql=".$sql, LOG_DEBUG);
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
     *  Update OPCA info for a trainee in a session (used if session type is 'inter')
     *
     *  @param	User	$user        User that modify
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function updateInfosOpcaForTrainee() {
    	global $conf, $langs;
    	$error=0;

    	// Clean parameters
    	$this->date_ask_OPCA = addslashes(trim($this->date_ask_OPCA));
    	$this->is_date_ask_OPCA = addslashes(trim($this->is_date_ask_OPCA));
    	$this->is_OPCA = addslashes(trim($this->is_OPCA));
    	$this->fk_soc_OPCA = addslashes(trim($this->fk_soc_OPCA));
    	$this->soc_OPCA_name = addslashes(trim($this->soc_OPCA_name));
    	$this->OPCA_adress = addslashes(trim($this->OPCA_adress));
    	$this->fk_socpeople_OPCA = addslashes(trim($this->fk_socpeople_OPCA));
    	$this->contact_name_OPCA = addslashes(trim($this->contact_name_OPCA));
    	$this->num_OPCA_soc = addslashes(trim($this->num_OPCA_soc));
    	$this->num_OPCA_file = addslashes(trim($this->num_OPCA_file));

    	// Update request
    	$sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_opca as s SET";
    	$sql.= " s.fk_session_agefodd='".$this->sessid."',";
    	$sql.= " s.fk_stagiaire='".$this->stagiaire."',";
    	$sql.= " s.fk_user_mod='".$user->id."',";
    	$sql.= " s.fk_agefodd_stagiaire_type='".$this->stagiaire_type."',";
    	$sql.= " s.fk_agefodd_stagiaire_type='".$this->type."'";
    	$sql.= " WHERE s.rowid = ".$this->id;

    	$this->db->begin();

    	dol_syslog(get_class($this)."::updateInfosOpcaForTrainee sql=".$sql, LOG_DEBUG);
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
    			dol_syslog(get_class($this)."::updateInfosOpcaForTrainee ".$errmsg, LOG_ERR);
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
     *	@param  int		$id        Session to delete
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	 int					 <0 if KO, >0 if OK
	 */
	function remove($id, $notrigger=0)
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

		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."agefodd_session";
		$sql .= " WHERE rowid = ".$id;

		dol_syslog(get_class($this)."::remove sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query ($sql);

		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
		    $this->error=$this->db->lasterror();
		    return -1;
		}
        }


 	/**
	 *  Delete object (trainne in session) in database
	 *
     *	@param  int		$id        Session to delete
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	 int					 <0 if KO, >0 if OK
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
			dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');

			$agf_training = new Agefodd($db);
			$agf_training->fetch($this->formid);
			$s=$agf_training->getToolTip();
		}
		return $s;
	}

    /**
     *  Load all objects in memory from database
     *
     *  @param	string		$sortorder    sort order
     *  @param	string		$sortfield    sort field
     *  @param	int			$limit		  limit page
     *  @param	int			$offset    	  page
     *  @param	int			$arch    	  display archive or not
     *  @param	array		$filter    	  filter output
     *  @return int          	<0 if KO, >0 if OK
     */
	function fetch_all($sortorder, $sortfield, $limit, $offset, $arch, $filter='')
	{
		global $langs;

		$sql = "SELECT s.rowid, s.fk_soc, s.fk_session_place, s.type_session, s.dated, s.datef, s.is_date_res_site, s.is_date_res_trainer, s.date_res_trainer, s.color, s.force_nb_stagiaire, s.nb_stagiaire,s.notes,";
		$sql.= " c.intitule, c.ref,";
		$sql.= " p.ref_interne,";
		$sql.= " (SELECT count(*) FROM ".MAIN_DB_PREFIX."agefodd_session_stagiaire WHERE fk_session_agefodd=s.rowid) as num";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c";
		$sql.= " ON c.rowid = s.fk_formation_catalogue";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_place as p";
		$sql.= " ON p.rowid = s.fk_session_place";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_stagiaire as ss";
		$sql.= " ON s.rowid = ss.fk_session_agefodd";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."agefodd_session_adminsitu as sa";
		$sql.= " ON s.rowid = sa.fk_agefodd_session";

		if ($arch == 2)
		{
			$sql.= " WHERE s.archive LIKE 0";
			$sql.= " AND sa.indice=";
			$sql.= "(";
			$sql.= " SELECT MAX(indice) FROM llx_agefodd_session_adminsitu WHERE level_rank=0";
			$sql.= ")";
			$sql.= " AND sa.archive LIKE 1";
		}
		else $sql.= " WHERE s.archive LIKE ".$arch;

		//Manage filter
		if (!empty($filter)){
			foreach($filter as $key => $value) {
				if (strpos($key,'date')) {
					$sql.= ' AND '.$key.' = \''.$this->db->idate($value).'\'';
				}
				elseif ($key=='s.fk_session_place')
				{
					$sql.= ' AND '.$key.' = '.$value;
				}
				else {
					$sql.= ' AND '.$key.' LIKE \'%'.$value.'%\'';
				}
			}
		}
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
					$this->line[$i]->socid = $obj->socid;
					$this->line[$i]->socid = $obj->fk_soc;
					$this->line[$i]->type_session = $obj->type_session;
					$this->line[$i]->socid = $obj->fk_soc;
					$this->line[$i]->is_date_res_site = $obj->is_date_res_site;
					$this->line[$i]->is_date_res_trainer = $obj->is_date_res_trainer;
					$this->line[$i]->date_res_trainer = $this->db->jdate($obj->date_res_trainer);
					$this->line[$i]->fk_session_place = $obj->fk_session_place;
					$this->line[$i]->dated = $this->db->jdate($obj->dated);
					$this->line[$i]->datef =$this->db->jdate($obj->datef);
					$this->line[$i]->intitule = $obj->intitule;
					$this->line[$i]->ref = $obj->ref;
					$this->line[$i]->ref_interne = $obj->ref_interne;
					$this->line[$i]->num = $obj->num;
					$this->line[$i]->color = $obj->color;
					$this->line[$i]->nb_stagiaire = $obj->nb_stagiaire;
					$this->line[$i]->force_nb_stagiaire = $obj->force_nb_stagiaire;
					$this->line[$i]->notes = $obj->notes;

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


	/**
	 * Print table of session information
	 */
	function printSessionInfo()
	{
		global $form, $langs;
		print '<table class="border" width="100%">';

		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td>'.$form->showrefnav($this,'id','',1,'rowid','id').'</td></tr>';

		print '<tr><td>'.$langs->trans("AgfFormIntitule").'</td>';
		print '<td><a href="'.dol_buildpath('/agefodd/training/card.php',1).'?id='.$this->fk_formation_catalogue.'">'.$this->formintitule.'</a></td></tr>';

		print '<tr><td>'.$langs->trans("AgfFormCodeInterne").'</td>';
		print '<td>'.$this->formref.'</td></tr>';

		// Type de la session
		print '<tr><td>'.$langs->trans("AgfFormTypeSession").'</td>';
		print '<td>'.( $this->type_session?$langs->trans('AgfFormTypeSessionInter'):$langs->trans('AgfFormTypeSessionIntra') ).'</td></tr>';

		print '<tr><td>'.$langs->trans("AgfSessionCommercial").'</td>';
		print '<td><a href="'.dol_buildpath('/user/fiche.php',1).'?id='.$this->commercialid.'">'.$this->commercialname.'</a></td></tr>';

		print '<tr><td>'.$langs->trans("AgfDuree").'</td>';
		print '<td>'.$this->duree.' heure(s)</td></tr>';

		print '<tr><td>'.$langs->trans("AgfDateDebut").'</td>';
		print '<td>'.dol_print_date($this->dated,'daytext').'</td></tr>';

		print '<tr><td>'.$langs->trans("AgfDateFin").'</td>';
		print '<td>'.dol_print_date($this->datef,'daytext').'</td></tr>';

		print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
		print '	<td>';
		print $this->getElementUrl($this->fk_soc, 'societe',1);
		print '</td></tr>';


		print '<tr><td>'.$langs->trans("AgfSessionContact").'</td>';
		print '<td><a href="'.dol_buildpath('/agefodd/contact/card.php',1).'?id='.$this->sourcecontactid.'">'.$this->contactname.'</a></td></tr>';

		print '<tr><td>'.$langs->trans("AgfLieu").'</td>';
		print '<td><a href="'.dol_buildpath('/agefodd/site/card.php',1).'?id='.$this->placeid.'">'.$this->placecode.'</a></td></tr>';

		print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td>';
		if (!empty($this->notes)) $notes = nl2br($this->notes);
		else $notes =  $langs->trans("AgfUndefinedNote");
		print '<td>'.stripslashes($notes).'</td></tr>';

		print '<tr><td>'.$langs->trans("AgfDateResTrainer").'</td>';
		if ($this->is_date_res_trainer) {
			print '<td>'.dol_print_date($this->date_res_trainer,'daytext').'</td></tr>';
		}
		else {
			print '<td>'.$langs->trans("AgfNoDefined").'</td></tr>';
		}


		print '<tr><td>'.$langs->trans("AgfDateResSite").'</td>';
		if ($this->is_date_res_site) {
			print '<td>'.dol_print_date($this->date_res_site,'daytext').'</td></tr>';
		}
		else {
			print '<td>'.$langs->trans("AgfNoDefined").'</td></tr>';
		}

		print '</table>';
	}

	function loadArrayTypeSession()
	{
		return $this->type_session_def;
	}

	/**
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param		int		$withpicto		Add picto into link
	 *	@param		string	$option			Where point the link
	 *	@param		int		$maxlength		Maxlength of ref
	 *	@return		string					String with URL
	 */
	function getNomUrl($withpicto=0,$option='',$maxlength=0)
	{
		global $langs;

		$result='';

		if (!$option)
		{
			$lien = '<a href="'.dol_buildpath('/agefodd/session/card.php',1).'?id='.$this->id.'">';
			$lienfin='</a>';
		}
		$newref=$this->formintitule;
		if ($maxlength) $newref=dol_trunc($newref,$maxlength,'middle');

		if ($withpicto) {
			$result.=($lien.img_object($langs->trans("ShowSession").' '.$this->ref,'agefodd@agefodd').$lienfin.' ');
		}
		$result.=$lien.$newref.$lienfin;
		return $result;
	}

	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id_trainee		Id of trainee in session
	 *  @param	int		$id_session		Id of session
	 *  @return int          	<0 if KO, >0 if OK (rowid)
	 */
	function getOpcaForTraineeInSession($fk_soc_trainee,$id_session)
	{
		global $langs;
		$sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.fk_soc_trainee,";
		$sql.= " t.fk_session_agefodd,";
		$sql.= " t.date_ask_OPCA,";
		$sql.= " t.is_date_ask_OPCA,";
		$sql.= " t.is_OPCA,";
		$sql.= " t.fk_soc_OPCA,";
		$sql.= " t.fk_socpeople_OPCA,";
		$sql.= " t.num_OPCA_soc,";
		$sql.= " t.num_OPCA_file,";
		$sql.= " t.fk_user_author,";
		$sql.= " t.datec,";
		$sql.= " t.fk_user_mod,";
		$sql.= " t.tms";

		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_opca as t";

		$sql.= " WHERE t.fk_soc_trainee = ".$fk_soc_trainee;
		$sql.= " AND t.fk_session_agefodd = ".$id_session;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->opca_rowid					= $obj->rowid;
				$this->fk_soc_trainee 		= $obj->fk_soc_trainee;
				$this->fk_session_agefodd 	= $obj->fk_session_agefodd;
				$this->date_ask_OPCA 		= $this->db->jdate($obj->date_ask_OPCA);
				$this->is_date_ask_OPCA 	= $obj->is_date_ask_OPCA;
				$this->is_OPCA 				= $obj->is_OPCA;
				$this->fk_soc_OPCA 			= $obj->fk_soc_OPCA;
				$this->fk_socpeople_OPCA 	= $obj->fk_socpeople_OPCA;
				$this->num_OPCA_soc 		= $obj->num_OPCA_soc;
				$this->num_OPCA_file 		= $obj->num_OPCA_file;
				$this->fk_user_author 		= $obj->fk_user_author;
				$this->datec 				= $this->db->jdate($obj->datec);
				$this->fk_user_mod 			= $obj->fk_user_mod;
				$this->tms 					= $this->db->jdate($obj->tms);

				$this->soc_OPCA_name = $this->getValueFrom('societe', $this->fk_soc_OPCA, 'nom');

			}
			$this->db->free($resql);

			return $this->opca_rowid;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  Create line into database about OPCA infos
	 *
	 *  @param	User	$user        User that create
	 *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
	 *  @return int      		   	 <0 if KO, Id of created object if OK
	 */
	function saveInfosOpca($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		// Clean parameters
		if (isset($this->fk_soc_trainee)) $this->fk_soc_trainee=trim($this->fk_soc_trainee);
		if (isset($this->fk_session_agefodd)) $this->fk_session_agefodd=trim($this->fk_session_agefodd);
		if (isset($this->date_ask_OPCA)) $this->date_ask_OPCA=trim($this->date_ask_OPCA);
		if (isset($this->is_date_ask_OPCA)) $this->is_date_ask_OPCA=trim($this->is_date_ask_OPCA);
		if (isset($this->is_OPCA)) $this->is_OPCA=trim($this->is_OPCA);
		if (isset($this->fk_soc_OPCA)) $this->fk_soc_OPCA=trim($this->fk_soc_OPCA);
		if (isset($this->fk_socpeople_OPCA)) $this->fk_socpeople_OPCA=trim($this->fk_socpeople_OPCA);
		if (isset($this->num_OPCA_soc)) $this->num_OPCA_soc=trim($this->num_OPCA_soc);
		if (isset($this->num_OPCA_file)) $this->num_OPCA_file=trim($this->num_OPCA_file);
		if (isset($this->fk_user_author)) $this->fk_user_author=trim($this->fk_user_author);
		if (isset($this->fk_user_mod)) $this->fk_user_mod=trim($this->fk_user_mod);

		// Check parameters
		// Put here code to add control on parameters values
		if (! $this->fk_user_author) $this->fk_user_author = $user->id;
		if (! $this->datec) $this->datec = $this->db->idate(dol_now());

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."agefodd_opca(";

		$sql.= "fk_soc_trainee,";
		$sql.= "fk_session_agefodd,";
		$sql.= "date_ask_OPCA,";
		$sql.= "is_date_ask_OPCA,";
		$sql.= "is_OPCA,";
		$sql.= "fk_soc_OPCA,";
		$sql.= "fk_socpeople_OPCA,";
		$sql.= "num_OPCA_soc,";
		$sql.= "num_OPCA_file,";
		$sql.= "fk_user_author,";
		$sql.= "datec,";
		$sql.= "fk_user_mod";


		$sql.= ") VALUES (";

		$sql.= " ".(! isset($this->fk_soc_trainee)?'NULL':"'".$this->fk_soc_trainee."'").",";
		$sql.= " ".(! isset($this->fk_session_agefodd)?'NULL':"'".$this->fk_session_agefodd."'").",";
		$sql.= " ".(! isset($this->date_ask_OPCA) || dol_strlen($this->date_ask_OPCA)==0?'NULL':$this->db->idate($this->date_ask_OPCA)).",";
		$sql.= " ".(! isset($this->is_date_ask_OPCA)?'NULL':"'".$this->is_date_ask_OPCA."'").",";
		$sql.= " ".(! isset($this->is_OPCA)?'NULL':"'".$this->is_OPCA."'").",";
		$sql.= " ".(! isset($this->fk_soc_OPCA)?'NULL':"'".$this->fk_soc_OPCA."'").",";
		$sql.= " ".(! isset($this->fk_socpeople_OPCA)?'NULL':"'".$this->fk_socpeople_OPCA."'").",";
		$sql.= " ".(! isset($this->num_OPCA_soc)?'NULL':"'".$this->db->escape($this->num_OPCA_soc)."'").",";
		$sql.= " ".(! isset($this->num_OPCA_file)?'NULL':"'".$this->db->escape($this->num_OPCA_file)."'").",";
		$sql.= " ".(! isset($this->fk_user_author)?'NULL':"'".$this->fk_user_author."'").",";
		$sql.= " ".(! isset($this->datec) || dol_strlen($this->datec)==0?'NULL':$this->db->idate($this->datec)).",";
		$sql.= " ".(! isset($this->fk_user_mod)?'NULL':"'".$this->fk_user_mod."'")."";

		$sql.= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (! $resql) {
			$error++; $this->errors[]="Error ".$this->db->lasterror();
		}

		if (! $error)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."agefodd_opca");

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
	 *  Update OPCA info into database for the thirparty of trainee in agefodd session
	 *
	 *  @param	User	$user        User that modify
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 <0 if KO, >0 if OK
	 */
	function updateInfosOpca($user=0, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->fk_soc_trainee)) $this->fk_soc_trainee=trim($this->fk_soc_trainee);
		if (isset($this->fk_session_agefodd)) $this->fk_session_agefodd=trim($this->fk_session_agefodd);
		if (isset($this->is_date_ask_OPCA)) $this->is_date_ask_OPCA=trim($this->is_date_ask_OPCA);
		if (isset($this->is_OPCA)) $this->is_OPCA=trim($this->is_OPCA);
		if (isset($this->fk_soc_OPCA)) $this->fk_soc_OPCA=trim($this->fk_soc_OPCA);
		if (isset($this->fk_socpeople_OPCA)) $this->fk_socpeople_OPCA=trim($this->fk_socpeople_OPCA);
		if (isset($this->num_OPCA_soc)) $this->num_OPCA_soc=trim($this->num_OPCA_soc);
		if (isset($this->num_OPCA_file)) $this->num_OPCA_file=trim($this->num_OPCA_file);
		if (isset($this->fk_user_author)) $this->fk_user_author=trim($this->fk_user_author);
		if (isset($this->fk_user_mod)) $this->fk_user_mod=trim($this->fk_user_mod);



		// Check parameters
		// Put here code to add control on parameters values
		if (! $this->fk_user_mod) $this->fk_user_mod = $user->id;

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."agefodd_opca SET";

		$sql.= " fk_soc_trainee=".(isset($this->fk_soc_trainee)?$this->fk_soc_trainee:"null").",";
		$sql.= " fk_session_agefodd=".(isset($this->fk_session_agefodd)?$this->fk_session_agefodd:"null").",";
		$sql.= " date_ask_OPCA=".(dol_strlen($this->date_ask_OPCA)!=0 ? "'".$this->db->idate($this->date_ask_OPCA)."'" : 'null').",";
		$sql.= " is_date_ask_OPCA=".(isset($this->is_date_ask_OPCA)?"'".$this->db->escape($this->is_date_ask_OPCA)."'" :"null").",";
		$sql.= " is_OPCA=".(isset($this->is_OPCA)?"'".$this->db->escape($this->is_OPCA)."'":"null").",";
		$sql.= " fk_soc_OPCA=".(isset($this->fk_soc_OPCA)?"'".$this->db->escape($this->fk_soc_OPCA)."'":"null").",";
		$sql.= " fk_socpeople_OPCA=".(isset($this->fk_socpeople_OPCA)?"'".$this->db->escape($this->fk_socpeople_OPCA)."'":"null").",";
		$sql.= " num_OPCA_soc=".(isset($this->num_OPCA_soc)?"'".$this->db->escape($this->num_OPCA_soc)."'":"null").",";
		$sql.= " num_OPCA_file=".(isset($this->num_OPCA_file)?"'".$this->db->escape($this->num_OPCA_file)."'":"null").",";
		$sql.= " fk_user_author=".(isset($this->fk_user_author)?"'".$this->db->escape($this->fk_user_author)."'":"null").",";
		$sql.= " datec=".(dol_strlen($this->datec)!=0 ? "'".$this->db->idate($this->datec)."'" : 'null').",";
		$sql.= " fk_user_mod=".(isset($this->fk_user_mod)?"'".$this->db->escape($this->fk_user_mod)."'" :"null")."";

		$sql.= " WHERE rowid=".$this->id_opca_trainee;

		$this->db->begin();

		dol_syslog(get_class($this)."::updateInfosOpca sql=".$sql, LOG_DEBUG);
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
				dol_syslog(get_class($this)."::updateInfosOpca ".$errmsg, LOG_ERR);
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

?>
