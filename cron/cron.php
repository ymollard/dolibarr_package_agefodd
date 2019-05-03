<?php
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');


class cron_agefodd
{

	private $db;

	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		global $db, $const;
		$this->db = $db;
		$this->const = $const;

	}


	public function sendAgendaToTrainee()
	{

        /* # Status
         *  1 Envisagée
         *  2 Confirmée
         *  6 En cours
         *  5 Réalisée
         *  3 Non réalisée
         *  4 Archivée
         */
        // GET SESSION AT DAY-1
        $sql = "SELECT rowid ";
        $sql.= " FROM " . MAIN_DB_PREFIX . "agefodd_session s ";
        $sql.= " WHERE s.dated >=  CURDATE() + INTERVAL 1 DAY AND s.dated <  CURDATE() + INTERVAL 2 DAY ";
        $sql.= " AND   s.status = 2 ";

        $resql = $this->db->query($sql);

        if (!empty($resql) && $this->db->num_rows($resql) > 0) {
            while ($obj = $this->db->fetch_object($resql)){
                $agsession = new Agsession($this->db);
                if($agsession->fetch($obj->rowid))
                {
                    $agsession->fetch_optionals();


                    // GET TRAINEES
                    // var_dump($agsession);
                    // $agsession->intitule_custo
                    // $agsession->formintitule
                    // $agsession->ref

                    $sql = "SELECT rowid ";
                    $sql.= " FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire ss ";
                    $sql.= " WHERE  ss.fk_session_agefodd = ".$agsession->id;

                    $resqlStag = $this->db->query($sql);

                    if (!empty($resqlStag) && $this->db->num_rows($resqlStag) > 0) {
                        while ($objStag = $this->db->fetch_object($resqlStag)){
                            $agsessionTrainee = new Agefodd_session_stagiaire($this->db);
                            if($agsessionTrainee->fetch($objStag->rowid))
                            {
                                $agsessionTrainee->fetch_optionals();
                                var_dump($agsessionTrainee->array_options, $agsessionTrainee);
                                if(!empty($agsessionTrainee->array_options['bcls_disablemailsession'])){
                                    continue;
                                }
                                else{


                                }
                            }
                        }
                    }
                    else{
                        // nothing to send
                        if (empty($resql)) dol_print_error($this->db);
                    }

                }
            }
        }
        else{
            if (empty($resql)) dol_print_error($this->db);
            return 'nothing to do';
        }


	}

}
