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
    public $fk_calendrier_type;
    public $heurep;

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }

    public function create($user, $notrigger = 0) {
        global $conf, $langs;
        $error = 0;

        // Clean parameters

        if (isset($this->fk_session))
            $this->fk_session = trim($this->fk_session);
        if (isset($this->fk_session_stagiaire))
            $this->fk_session_stagiaire = trim($this->fk_session_stagiaire);
        if (isset($this->fk_calendrier_type))
            $this->fk_calendrier_type = trim($this->fk_calendrier_type);
        if (isset($this->heurep))
            $this->heurep = (float)$this->heurep;

        // Insert request
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element ."(";
        $sql .= "fk_agefodd_session,";
        $sql .= "fk_agefodd_session_stagiaire,";
        $sql .= "fk_calendrier_type,";
        $sql .= "heurep";
        $sql .= ") VALUES (";

        $sql .= " " . (! isset($this->fk_session) ? 'NULL' : "'" . $this->fk_session . "'") . ",";
        $sql .= " " . (! isset($this->fk_session_stagiaire) ? 'NULL' : "'" . $this->fk_session_stagiaire . "'") . ",";
        $sql .= " " . (! isset($this->fk_calendrier_type) ? 'NULL' : "'" . $this->fk_calendrier_type . "'") . ",";
        $sql .= " " . (! isset($this->heurep) ? 'NULL' : "'" . $this->heurep . "'");
        $sql .= ")";

        $this->db->begin();

        dol_syslog(get_class($this) . "::create", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) {

            $error ++;
            $this->errors[] = "Error " . $this->db->lasterror();

        }

        if (! $error) {

            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

            if (! $notrigger) {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.

                // // Call triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('AGEFODDSESSIONSTAGIAIREPLANIFICATION_CREATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
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

            foreach ( $this->errors as $errmsg ) {

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
     * Load object in memory from the database
     *
     * @param int $id object
     * @return int <0 if KO, >0 if OK
     */
    public function fetch($id) {

        global $langs;

        $sql = "SELECT";
        $sql .= " t.rowid,";
        $sql .= " fk_agefodd_session,";
        $sql .= " fk_agefodd_session_stagiaire,";
        $sql .= " fk_calendrier_type,";
        $sql .= " heurep";
        $sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element;
        $sql .= " WHERE t.rowid = " . $id;

        dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->fk_agefodd_session = $obj->fk_agefodd_session;
                $this->fk_agefodd_session_stagiaire = $obj->fk_agefodd_session_stagiaire;
                $this->fk_calendrier_type = $obj->fk_calendrier_type;
                $this->heurep = $obj->heurep;
            }

            $this->db->free($resql);

            return 1;

        }
        else {

            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
            return - 1;
        }
    }



}