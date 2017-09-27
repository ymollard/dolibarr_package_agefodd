<?php
/* Copyright (C) 2007-2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file        htdocs/modulebuilder/template/class/agefodd_session_stagiaire_heures.class.php
 * \brief       This file is a CRUD class file for Agefoddsessionstagiaireheures (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Agefoddsessionstagiaireheures
 */
class Agefoddsessionstagiaireheures extends CommonObject
{
    public $error; // !< To return error code (or message)
    public $errors = array (); // !< To return several error codes (or messages)
    public $element = 'agefodd_session_stagiaire_heures';
    public $table_element = 'agefodd_session_stagiaire_heures';
    public $id;
    public $fk_stagiaire;
    public $nom_stagiaire;
    public $datec = '';
    public $fk_user_author;
    public $tms = '';
    //public $lines = array();
    public $fk_calendrier;
    public $fk_session;
    public $heures;
    
	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param User $user that creates
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0) {
	    global $conf, $langs;
	    $error = 0;
	    
	    // Clean parameters
	    
	    if (isset($this->fk_stagiaire))
	        $this->fk_stagiaire = trim($this->fk_stagiaire);
	    if (isset($this->$fk_calendrier))
	        $this->$fk_calendrier = trim($this->$fk_calendrier);
	    if (isset($this->$fk_session))
            $this->$fk_session = trim($this->$fk_session);
        if (isset($this->$heures))
            $this->$heures = trim($this->$heures);
        if (isset($this->fk_user_author))
            $this->fk_user_author = trim($this->fk_user_author);
                   
        // Insert request
            $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element ."(";
        $sql .= "entity,";
        $sql .= "fk_stagiaire,";
        $sql .= "fk_session,";
        $sql .= "fk_calendrier,";
        $sql .= "heures,";
        $sql .= "fk_user_author,";
        $sql .= "datec";
        $sql .= ") VALUES (";
        
        $sql .= " '" . $conf->entity . "',";
        $sql .= " " . (! isset($this->fk_stagiaire) ? 'NULL' : "'" . $this->fk_stagiaire . "'") . ",";
        $sql .= " " . (! isset($this->fk_session) ? 'NULL' : "'" . $this->fk_session . "'") . ",";
        $sql .= " " . (! isset($this->fk_calendrier) ? 'NULL' : "'" . $this->fk_calendrier . "'") . ",";
        $sql .= " " . (! isset($this->heures) || dol_strlen($this->heures) == 0 ? 'NULL' : "'" . $this->db->escape($this->heures) . "'") . ",";
        $sql .= " " . (! isset($this->fk_user_author) ? $user->id : "'" . $this->fk_user_author . "'") . ",";
        $sql .= " '" . (! isset($this->datec) || dol_strlen($this->datec) == 0 ? $this->db->idate(dol_now()) : $this->db->idate($this->datec)) . "'";
        
        $sql .= ")";
        $this->db->begin();
        
        dol_syslog(get_class($this) . "::create", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error ++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }
        
        if (! $error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_session_formateur_calendrier");
            
            if (! $notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.
                
                // // Call triggers
                // include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                // $interface=new Interfaces($this->db);
                // $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
                // if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // // End call triggers
            }
        }
        
        // Commit or rollback
        if ($error) {
            foreach ( $this->errors as $errmsg ) {
                dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return - 1 * $error;
        } else {
            $this->db->commit();
            return $this->id;
        }
	}
	
	/**
	 * Load object in memory from the database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id) {
	    global $langs;
	    $sql = "SELECT";
	    $sql .= " t.rowid,";
	    $sql .= " t.fk_stagiaire,";
	    $sql .= " t.fk_session,";
	    $sql .= " t.fk_calendrier,";
	    $sql .= " t.heures,";
	    $sql .= " t.fk_user_author,";
	    $sql .= " t.datec,";
	    $sql .= " t.tms";
	    $sql .= " CONCAT(a.nom,' ', a.prenom) as nom_stagiaire";
	    $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
	    $sql .= " LEFT JOIN " .MAIN_DB_PREFIX . "agefodd_stagiaire as a ON a.rowid = t.fk_stagiaire";
	    $sql .= " WHERE t.rowid = " . $id;
	    
	    dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
	    $resql = $this->db->query($sql);
	    if ($resql) {
	        if ($this->db->num_rows($resql)) {
	            $obj = $this->db->fetch_object($resql);
	            
	            $this->id = $obj->rowid;
	            $this->fk_stagiaire = $obj->fk_stagiaire;
	            $this->nom_stagiaire = $obj->nom_stagiaire;
	            $this->fk_session = $obj->fk_session;
	            $this->fk_calendrier = $obj->fk_calendrier;
	            $this->heures= $obj->heures;	            
	            $this->fk_user_author = $obj->fk_user_author;
	            $this->datec = $this->db->jdate($obj->datec);
	            $this->tms = $this->db->jdate($obj->tms);
	        }
	        $this->db->free($resql);
	        
	        return 1;
	    } else {
	        $this->error = "Error " . $this->db->lasterror();
	        dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
	        return - 1;
	    }
	}
	
	/**
	 * 
	 * @param int $id session
	 * @param int $trainee
	 * @param int $calendar
	 */
	public function fetch_by_session($id, $trainee, $calendar)
	{
	    $sql = "SELECT t.rowid,";
	    $sql .= " t.fk_stagiaire,";
	    $sql .= " t.fk_session,";
	    $sql .= " t.fk_calendrier,";
	    $sql .= " t.heures,";
	    $sql .= " t.fk_user_author,";
	    $sql .= " t.datec,";
	    $sql .= " t.tms,";
	    $sql .= " CONCAT(a.nom,' ', a.prenom) as nom_stagiaire";
	    $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as t";
	    $sql .= " LEFT JOIN " .MAIN_DB_PREFIX . "agefodd_stagiaire as a ON a.rowid = t.fk_stagiaire";
	    $sql .= " WHERE t.fk_session = " . $id;
	    $sql .= " AND t.fk_stagiaire = " . $trainee;
	    $sql .= " AND t.fk_calendrier = " . $calendar;
	    
	    dol_syslog(get_class($this) . "::fetch_by_session", LOG_DEBUG);
	    $resql = $this->db->query($sql);
	    
	    if ($resql) {
	        if ($this->db->num_rows($resql)) {
	            $obj = $this->db->fetch_object($resql);
	            
	            $this->id = $obj->rowid;
	            $this->fk_stagiaire = $obj->fk_stagiaire;
	            $this->nom_stagiaire = $obj->nom_stagiaire;
	            $this->fk_session = $obj->fk_session;
	            $this->fk_calendrier = $obj->fk_calendrier;
	            $this->heures= $obj->heures;
	            $this->fk_user_author = $obj->fk_user_author;
	            $this->datec = $this->db->jdate($obj->datec);
	            $this->tms = $this->db->jdate($obj->tms);
	        }
	        $this->db->free($resql);
	        
	        return 1;
	    } else {
	        $this->error = "Error " . $this->db->lasterror();
	        dol_syslog(get_class($this) . "::fetch_by_session " . $this->error, LOG_ERR);
	        return - 1;
	    }
	}
	
	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}

}

/**
 * Class Agefoddsessionstagiaireheuresline
 */
class Agefoddsessionstagiaireheuresline
{
    public $id;
    public $fk_calendier;
    public $fk_session;
    public $heures;
}
