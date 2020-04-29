<?php

/**
 * \file agefodd/class/agsession.class.php
 * \ingroup agefodd
 * \brief Manage Session object
 */
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");


class AgefoddSessionStagiairePlanification extends CommonObject
{

    public $error;
    public $errors = array ();
    public $element = 'agefodd_session_stagiaire_planification';
    public $table_element = 'agefodd_session_stagiaire_planification';
    public $id;
    public $fk_session;
    public $fk_session_stagiaire;
    public $fk_session_formateur;
    public $fk_calendrier_type;
    public $heurep;

    public $TTypeTimeById =array();
	public $TTypeTimeByCode=array();

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
     * @param User $user that create
     * @param int $notrigger triggers after, 1=disable triggers
     * @return int <0 if KO, Id of created object if OK
     * @throws Exception
     */
    public function create($user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        // Clean parameters

        if (isset($this->fk_session))
            $this->fk_session = trim($this->fk_session);
        if (isset($this->fk_session_stagiaire))
            $this->fk_session_stagiaire = trim($this->fk_session_stagiaire);
	    if (isset($this->fk_session_formateur))
		    $this->fk_session_formateur = trim($this->fk_session_formateur);
        if (isset($this->fk_calendrier_type))
            $this->fk_calendrier_type = trim($this->fk_calendrier_type);
        if (isset($this->heurep))
            $this->heurep = (float) $this->heurep;


        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element ."(";
        $sql .= "fk_agefodd_session,";
        $sql .= "fk_agefodd_session_stagiaire,";
        $sql .= "fk_agefodd_session_formateur,";
        $sql .= "fk_calendrier_type,";
        $sql .= "heurep";
        $sql .= ") VALUES (";

        $sql .= " " . (! isset($this->fk_session) ? 'NULL' : $this->fk_session ) . ",";
        $sql .= " " . (! isset($this->fk_session_stagiaire) ? 'NULL' : $this->fk_session_stagiaire) . ",";
        $sql .= " " . (! isset($this->fk_session_formateur) ? 'NULL' : $this->fk_session_formateur) . ",";
        $sql .= " " . (! isset($this->fk_calendrier_type) ? 'NULL' : $this->fk_calendrier_type) . ",";
        $sql .= " " . (! isset($this->heurep) ? 'NULL' : $this->heurep);
        $sql .= ")";

        $this->db->begin();

        dol_syslog(get_class($this) . "::create", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $error ++;
            $this->errors[] = "Error " . $this->db->lasterror();

        }

        if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

            if (! $notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.

                // // Call triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('AGEFODDSESSIONSTAGIAIREPLANIFICATION_CREATE', $this, $user, $langs, $conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // // End call triggers
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg)
            {
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
     * Delete object (trainne in session) in database
     *
     * @param User $user user object
     * @param int $notrigger triggers after, 1=disable triggers
     * @return int <0 if KO, >0 if OK
     */
    public function delete($user, $notrigger = 0)
    {
        $error = 0;
        $this->db->begin();

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " WHERE rowid = " . $this->id;

        dol_syslog(get_class($this) . "::delete", LOG_DEBUG);

        $resql = $this->db->query($sql);

        if ($resql) {
            // ...
        } else {
            $error ++;
            $this->errors[] = "Error " . $this->db->lasterror();

        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);

            }

            $this->db->rollback();

            return - 1 * $error;

        } else {
            $this->db->commit();

            return 1;

        }
    }

    /**
     * Update object into database
     *
     * @param User $user that modifies
     * @param int $notrigger triggers after, 1=disable triggers
     * @return int <0 if KO, >0 if OK
     */
    public function update(User $user, $notrigger = 0)
    {
        global $conf, $langs;
        $error = 0;

        // Clean parameters

        if (isset($this->fk_session))
            $this->fk_session = trim($this->fk_session);
        if (isset($this->fk_session_stagiaire))
            $this->fk_session_stagiaire = trim($this->fk_session_stagiaire);
        if (isset($this->fk_session_formateur))
            $this->fk_session_formateur = trim($this->fk_session_formateur);
        if (isset($this->fk_calendrier_type))
            $this->fk_calendrier_type = trim($this->fk_calendrier_type);
        if (isset($this->heurep))
            $this->heurep = (float) $this->heurep;

        // Update request
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element ." SET";

        $sql .= " fk_agefodd_session=" . (isset($this->fk_session) ? $this->fk_session : "null") . ",";
        $sql .= " fk_agefodd_session_stagiaire=" . (isset($this->fk_session_stagiaire) ? $this->fk_session_stagiaire : "null") . ",";
        $sql .= " fk_agefodd_session_formateur=" . (isset($this->fk_session_formateur) ? $this->fk_session_formateur : "null") . ",";
        $sql .= " fk_calendrier_type=" . (isset($this->fk_calendrier_type) ? $this->fk_calendrier_type : "null") . ",";
        $sql .= " heurep=" . (isset($this->heurep) ? $this->heurep : "null");
        $sql .= " WHERE rowid=" . $this->id;

        $this->db->begin();

        dol_syslog(get_class($this) . "::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) {
            $error ++;
            $this->errors[] = "Error " . $this->db->lasterror();
        }

        if (! $error) {
            if (! $notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.

                // // Call triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('AGEFODDSESSIONSTAGIAIREHEURES_UPDATE', $this, $user, $langs, $conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // // End call triggers
            }
        }

        // Commit or rollback
        if ($error) {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return - 1 * $error;
        } else {
            $this->db->commit();
            return 1;
        }
    }

    /**
     * Load object in memory from the database
     *
     * @param int $id object
     * @return int <0 if KO, >0 if OK
     */
    public function fetch($id)
    {

        $sql = "SELECT";
        $sql .= " rowid,";
        $sql .= " fk_agefodd_session,";
        $sql .= " fk_agefodd_session_stagiaire,";
        $sql .= " fk_agefodd_session_formateur,";
        $sql .= " fk_calendrier_type,";
        $sql .= " heurep";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " WHERE rowid = " . $id;

        dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->fk_session = $obj->fk_agefodd_session;
                $this->fk_session_stagiaire = $obj->fk_agefodd_session_stagiaire;
                $this->fk_session_formateur = $obj->fk_agefodd_session_formateur;
                $this->fk_calendrier_type = $obj->fk_calendrier_type;
                $this->heurep = $obj->heurep;
            }

            $this->db->free($resql);

            return 1;

        }
        else {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::".__METHOD__ . $this->error, LOG_ERR);
            return - 1;
        }
    }

    /**
     * Return schedules of a trainee in a session per calendar type
     *
     * @param int $idsess session id
     * @param int $idtrainee trainee id
     * @param int $idTrainer Trainer id
     * @return int <0 if KO, >0 if OK
     */
    public function getSchedulesPerCalendarType($idsess, $idtrainee, $idTrainer = 0)
    {

        $sql = "SELECT rowid, fk_calendrier_type, heurep, fk_agefodd_session_formateur ";
        $sql.= "FROM " . MAIN_DB_PREFIX . $this->table_element. " ";
        $sql.= "WHERE fk_agefodd_session = " . $idsess . " AND fk_agefodd_session_stagiaire = ".$idtrainee;
	    if (!empty($idTrainer)) {
		    $sql .= " AND fk_agefodd_session_formateur = " . $idTrainer;
	    }
	    $sql .= $this->db->order('fk_calendrier_type,fk_agefodd_session_formateur');

        $resql = $this->db->query($sql);

        if ($resql) {
            $TRes = array();

            while ($obj = $this->db->fetch_object($resql))
            {
                $TRes[] = $obj;
            }

            return $TRes;

        } else {
        	$this->error=$this->db->lasterror;
	        dol_syslog(get_class($this) . ":: ".__METHOD__ . $this->error, LOG_ERR);
            return -1;
        }

        return 0;
    }

    /**
     * Return Total of scheduled hours for a trainee in a session
     *
     * @param int $idsess id session
     * @param int $idtrainee trainee id
     * @param int $idTrainer Trainer id
     * @return int <0 if KO, >0 if OK
     */
    public function getTotalScheduledHoursbyTrainee($idsess, $idtrainee, $idTrainer = 0)
    {
        $sql = "SELECT SUM(heurep) as totalheurep ";
        $sql.= "FROM " . MAIN_DB_PREFIX . $this->table_element . " ";
        $sql.= "WHERE fk_agefodd_session = " . $idsess . " AND fk_agefodd_session_stagiaire = ".$idtrainee;
	    if (!empty($idTrainer)) {
		    $sql .= " AND fk_agefodd_session_formateur = " . $idTrainer;
	    }

        $resql = $this->db->query($sql);

        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return $obj->totalheurep;
        } else {
        	$this->error=$this->db->lasterror;
	        dol_syslog(get_class($this) . ":: ".__METHOD__ . $this->error, LOG_ERR);
            return -1;
        }

        return 0;
    }

    /**
     * Verify if object already exist
     *
     * @param int $idsess id session
     * @param int $idtrainee trainee id
     * @param int $code_calendar Calendar code
     * @param int $idTrainer Trainer id
     * @return int <0 if KO, >0 if OK
     */
    public function verifyAlreadyExist($idsess, $idtrainee, $code_calendar, $idTrainer)
    {
        $sql = "SELECT p.rowid ";
        $sql.= "FROM " . MAIN_DB_PREFIX . $this->table_element. " as p ";
        $sql.= " INNER JOIN " . MAIN_DB_PREFIX . "c_agefodd_session_calendrier_type as c ON c.rowid = p.fk_calendrier_type ";
        $sql.= "WHERE p.fk_agefodd_session = " . $idsess . " AND p.fk_agefodd_session_stagiaire = ".$idtrainee." AND c.code = '".$code_calendar . "'";
        if (!empty($idTrainer)) {
	        $sql .= " AND p.fk_agefodd_session_formateur = " . $idTrainer;
        }

        $resql = $this->db->query($sql);

        if($resql)
        {
            if ($this->db->num_rows($resql) > 0)
            {
                $obj = $this->db->fetch_object($resql);

                return $obj->rowid;

            }
        } else {
        	$this->error = $this->db->lasterror;
	        dol_syslog(get_class($this) . ":: ".__METHOD__ . $this->error, LOG_ERR);
            return -1;
        }
        return 0;
    }

	/**
	 * Load dict data, populate TTypeTimeById and TTypeTimeByCode
	 * @return int <0 if KO, >0 if OK
	 */
    public function loadDictModalite() {

	    $sql = "SELECT";
	    $sql .= " rowid, label, code ";
	    $sql .= " FROM ".MAIN_DB_PREFIX."c_agefodd_session_calendrier_type";
	    $resql = $this->db->query($sql);
	    if (!$resql) {
		    $this->error = $this->db->lasterror;
		    return -1;
	    } else {
		    while($obj = $this->db->fetch_object($resql)) {
			    $this->TTypeTimeById[$obj->rowid]=array('code'=>$obj->code,'label'=>$obj->label);
			    $this->TTypeTimeByCode[$obj->code]=array('rowid'=>$obj->rowid,'label'=>$obj->label);
		    }
	    }
	    return count($this->TTypeTimeByCode);
    }
}
