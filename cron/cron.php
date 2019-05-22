<?php
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');


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

	//Si la date est dans le passé et que le statut est confirmé
	public function autoStatusAgefoddCalendar($fk_newStatus = Agefodd_sesscalendar::STATUS_FINISH, $days = 60)
	{
		global $db, $user;

		$errors = 0;
		$updated = 0;
		$message = '';

		dol_include_once('agefodd/class/agefodd_session_calendrier.class.php');
		// GET SESSION AT DAY-1
		$sql = "SELECT rowid, fk_agefodd_session ";
		$sql.= " FROM " . MAIN_DB_PREFIX . "agefodd_session_calendrier sc ";
		$sql.= " WHERE sc.heuref >=  CURDATE() - INTERVAL ".$days." DAY AND sc.heuref < CURDATE() - INTERVAL ".($days+1)." DAY ";
		$sql.= " AND   sc.status =  ".Agefodd_sesscalendar::STATUS_CONFIRMED;

		$resql = $this->db->query($sql);
		if (!empty($resql) && $this->db->num_rows($resql) > 0) {
			while ($obj = $this->db->fetch_object($resql)) {

				$sessionCal = new Agefodd_sesscalendar($db);
				if($sessionCal->fetch($obj->rowid)>0)
				{
					$sessionCal->status = $fk_newStatus;
					if($sessionCal->update($user) > 0)
					{
						$updated++;
					}
					else
					{
						$errors++;
					}
				}
			}
		}

		$this->output = 'errors: '.$errors.' | Updated: '.$updated;

		return $errors;
	}

	public function sendAgendaToTrainee($fk_mailModel = 0, $days = 1)
	{
        global $conf, $langs, $user;
        require_once (DOL_DOCUMENT_ROOT .'/core/class/CMailFile.class.php');
        $message = '';

        $days = intval($days);

        if(empty($fk_mailModel) && !empty($conf->global->AGF_SENDAGENDATOTRAINEE_DEFAULT_MAILMODEL)) {
            $fk_mailModel = $conf->global->AGF_SENDAGENDATOTRAINEE_DEFAULT_MAILMODEL;
        }

        $mailTpl = agf_getMailTemplate($fk_mailModel);
        if($mailTpl < 1){
            return $langs->trans('TemplateNotExist');
        }


        /* # Status
         *  0 prévi
         *  1 Confirmée
         */
        // GET SESSION AT DAY-1
        $sql = "SELECT rowid, fk_agefodd_session, heured, heuref, date_session  ";
        $sql.= " FROM " . MAIN_DB_PREFIX . "agefodd_session_calendrier sc ";
        $sql.= " WHERE sc.heured >=  CURDATE() + INTERVAL ".$days." DAY AND sc.heured < CURDATE() + INTERVAL ".($days+1)." DAY ";
        $sql.= " AND   sc.status = 1 ";

        $resql = $this->db->query($sql);



        $sended = 0;
        $errors = 0;
        $disabledMContact = 0;


        if (!empty($resql) && $this->db->num_rows($resql) > 0)
        {
            while ($obj = $this->db->fetch_object($resql))
            {
                $agsession = new Agsession($this->db);
                if($agsession->fetch($obj->fk_agefodd_session))
                {
                    $agsession->fetch_optionals();

                    // GET TRAINEES
                    $sql = "SELECT rowid ";
                    $sql.= " FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire ss ";
                    $sql.= " WHERE  ss.fk_session_agefodd = ".$agsession->id . ' AND status_in_session IN (2) ' ;

                    $resqlStag = $this->db->query($sql);

                    if (!empty($resqlStag) && $this->db->num_rows($resqlStag) > 0) {
                        while ($objStag = $this->db->fetch_object($resqlStag)){
                            $agsessionTrainee = new Agefodd_session_stagiaire($this->db);
                            if($agsessionTrainee->fetch($objStag->rowid) > 0)
                            {
                                $agsessionTrainee->fetch_optionals();

                                $stagiaire = new Agefodd_stagiaire($this->db);
                                if($stagiaire->fetch($agsessionTrainee->fk_stagiaire) > 0)
                                {
                                    if(!empty($stagiaire->disable_auto_mail)){
                                        $disabledMContact ++;
                                        continue;
                                    }
                                    else{
                                        // PREPARE EMAIL

                                        $from = $user->email;

                                        //$arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
                                        if (! isset($arrayoffamiliestoexclude)) $arrayoffamiliestoexclude=null;

                                        // Make substitution in email content
                                        $substitutionarray = getCommonSubstitutionArray($langs, 0, $arrayoffamiliestoexclude, $agsession);

                                        complete_substitutions_array($substitutionarray, $langs, $agsession);



                                        $thisSubstitutionarray = $substitutionarray;

                                        $thisSubstitutionarray['__agfsendall_nom__'] = $stagiaire->nom;
                                        $thisSubstitutionarray['__agfsendall_prenom__'] = $stagiaire->prenom;
                                        $thisSubstitutionarray['__agfsendall_civilite__'] = $stagiaire->civilite;
                                        $thisSubstitutionarray['__agfsendall_socname__'] = $stagiaire->socname;
                                        $thisSubstitutionarray['__agfsendall_email__'] = $stagiaire->email;

                                        // Tableau des substitutions
                                        if (! empty($agsession->intitule_custo)) {
                                            $thisSubstitutionarray['__FORMINTITULE__'] = $agsession->intitule_custo;
                                        } else {
                                            $thisSubstitutionarray['__FORMINTITULE__'] = $agsession->formintitule;
                                        }

                                        $date_conv = $agsession->libSessionDate('daytext');
                                        $thisSubstitutionarray['__FORMDATESESSION__'] = $date_conv;


                                        $sendTopic =make_substitutions($mailTpl->topic, $thisSubstitutionarray);
                                        $sendContent =make_substitutions($mailTpl->content, $thisSubstitutionarray);


                                        $to = $stagiaire->email;
                                        if(!empty($conf->global->AGF_CRON_FORCE_EMAIL_TO) && agf_isEmail($conf->global->AGF_CRON_FORCE_EMAIL_TO) ){
                                            $to = $conf->global->AGF_CRON_FORCE_EMAIL_TO;
                                        }

                                        $cMailFile = new CMailFile($sendTopic, $to, $from, $sendContent, array(), array(), array(), "", "",  0, 1, $from);

                                        if($cMailFile->sendfile()){
                                            $sended++;
                                        }
                                        else{
                                            $errors++;
                                        }

                                    }
                                }
                                else{
                                    $message.='error fetch stagiaire';
                                }
                            }
                            else{
                                $message.='error fetch agsessionTrainee';
                            }
                        }
                    }
                    else{
                        // nothing to send
                        if (empty($resql)) dol_print_error($this->db);
                        $message.='No email to send ';
                    }

                }
                else{
                    $message.='error fetch agefodd';
                }
            }

            $message.= ' | ' . $langs->trans('Sended').' : '.$sended;
            $message.= ' | ' . $langs->trans('agfcron_sendEmailError').' : '.$errors;
            $message.= ' | disabled contact : '.$disabledMContact;
        }
        else{
            if (empty($resql)) dol_print_error($this->db);
            $message.=  $langs->trans('AgfNoSessionToSend');
        }

		$this->output = $message;

        return $errors;
	}

}
