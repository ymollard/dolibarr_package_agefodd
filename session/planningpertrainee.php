<?php

$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
    $res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
    die("Include of main fails");

require_once ('../class/agsession.class.php');
require_once ('../class/html.formagefodd.class.php');
require_once ('../class/agefodd_calendrier.class.php');
require_once ('../class/agefodd_session_stagiaire_heures.class.php');
require_once ('../class/agefodd_session_formateur.class.php');
require_once ('../class/agefodd_session_stagiaire_planification.class.php');
require_once ('../lib/agefodd.lib.php');

// Security check
if (! $user->rights->agefodd->lire) {
    accessforbidden();
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array(
    'agefoddsessionplanningpertrainee'
));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$sessid = GETPOST('sessid', 'int');
$codeCalendar = GETPOST('code_c_session_calendrier_type', 'alpha');
$hoursp = GETPOST('heurep', 'alpha');
$traineeid = GETPOST('traineeid', 'int');
$trainerid = GETPOST('trainerid', 'int');
if ($trainerid==-1) {$trainerid=0;}
$hours_add = GETPOST('addHours', 'alpha');
$idPlanningHourstoremove = GETPOST('idPlanningHoursToRemove', 'alpha');

$agf = new Agsession($db);
if(!empty($id)) $result = $agf->fetch($id);

/*
 * Action
 */

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $agf, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0)
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


if($action == 'edit'){

    $error_message = '';
    $error = 0;

    $planningTrainee = new AgefoddSessionStagiairePlanification($db);

    if(!empty($hours_add))
    {
        $totalScheduledHoursTrainee = $planningTrainee->getTotalScheduledHoursbyTrainee($sessid, $traineeid);
		if ($totalScheduledHoursTrainee<0) {
			$error ++;
			$error_message = $planningTrainee->error;
		} else if (($totalScheduledHoursTrainee+$hoursp) > $agf->duree_session) {
            $error ++;
            $error_message = $langs->trans('AgfErrorHoursPTraineeHoursSess');
        }

        if(!$error)
        {
            $res = $planningTrainee->verifyAlreadyExist($sessid, $traineeid, $codeCalendar, $trainerid);

            if ($res > 0)       //mise à jour de la ligne
            {
                $res = $planningTrainee->fetch($res);
                if ($res > 0)
                {
                    $planningTrainee->heurep += $hoursp;
                    if($planningTrainee->heurep < 0) $planningTrainee->heurep = 0;

                    $res = $planningTrainee->update($user);

                    if($res <= 0){
                        $error ++;
                        $error_message= $langs->trans("AgfErrorUpdate").$planningTrainee->error;
                    }
                } else {
                    $error ++;
                    $error_message= $langs->trans("AgfErrorFetchPlanification");
                }
            }
            else        //créaton de la ligne
            {
                $planningTrainee->fk_session_stagiaire = $traineeid;
                $planningTrainee->fk_session_formateur = $trainerid;
                $planningTrainee->fk_session = $sessid;

                $sql = "SELECT";
                $sql .= " rowid ";
                $sql .= " FROM ".MAIN_DB_PREFIX."c_agefodd_session_calendrier_type";
                $sql .= " WHERE code = '".$codeCalendar."'";
                $resql = $db->query($sql);

                if ($resql)
                {
                    $obj = $db->fetch_object($resql);
                    $planningTrainee->fk_calendrier_type = $obj->rowid;
                } else {
                    $error ++;
                    $error_message = $langs->trans('Error');
                }

                $planningTrainee->heurep = (float) $hoursp;
                if($planningTrainee->heurep < 0) $planningTrainee->heurep = 0;

                $res = $planningTrainee->create($user);

                if($res <= 0){
                    $error ++;
                    $error_message = $langs->trans("AgfErrorCreateTraineePlannification").$planningTrainee->error;
                }
            }
        }
    }
    elseif (!empty($idPlanningHourstoremove))
    {
        $res = $planningTrainee->fetch($idPlanningHourstoremove);

        if($res > 0)
        {
            $res = $planningTrainee->delete($user);

            if ($res <= 0)
            {
                $error++;
                $error_message = $langs->trans("AgfErrorDeleteTraineePlanification").$planningTrainee->error;
            }
        } else {
            $error++;
            $error_message = $langs->trans("AgfErrorFetchPlanification").$planningTrainee->error;
        }
    }

    if (!$error) {
        Header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id . "");
    } else {
        setEventMessage($error_message, 'errors');
    }
}

/*
 * View
 */

llxHeader('', $langs->trans("AgfSessionDetail"), '', '', '', '', array(
    '/agefodd/includes/lib.js'
), array());

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

if ($id)
{
    if ($result > 0)
    {
        if (!(empty($agf->id)))
        {
            $head = session_prepare_head($agf);

            dol_fiche_head($head, 'planningpertrainee', $langs->trans("AgfSessionDetail"), 0, 'calendarday');

            dol_agefodd_banner_tab($agf, 'id');

            dol_fiche_end();
        }
    } else {
        setEventMessage($agf->error, 'errors');
    }
} else {
    print $langs->trans('AgfNoSession');
}

$planningTrainee = new AgefoddSessionStagiairePlanification($db);
$result=$planningTrainee->loadDictModalite();
if ($result<0) {
	setEventMessage($planningTrainee->error,'errors');
}

//Tableau pour chaque participant de la session
$session_trainee = new Agefodd_session_stagiaire($db);
$session_trainer = new Agefodd_session_formateur($db);
$res = $session_trainee->fetch_stagiaire_per_session($id);

if($res > 0)
{
	foreach ($session_trainee->lines as $trainee)
    {
	    $heureRTotal=0;
        $idTrainee_session = $trainee->stagerowid;
        $idtrainee = $trainee->id;

        //Tableau de toutes les heures plannifiées du participant

        $TLinesTraineePlanning = $planningTrainee->getSchedulesPerCalendarType($id, $idTrainee_session);

        //Nombre d'heures planifiées
        $totalScheduledHoursTrainee = $planningTrainee->getTotalScheduledHoursbyTrainee($id, $idTrainee_session);
	    if ($totalScheduledHoursTrainee==-1) {
		    setEventMessage($planningTrainee->error, 'errors');
	    }
        if(empty($totalScheduledHoursTrainee)) $totalScheduledHoursTrainee = 0;

        //heures réalisées par type de créneau
	    $TtrainerName=array();
        $trainee_hr = new Agefoddsessionstagiaireheures($db);
        $THoursR = $trainee_hr->fetch_heures_stagiaire_per_type($id, $idtrainee);

	    if (!is_array($THoursR) && $THoursR<0) {
			setEventMessage($trainee_hr->error, 'errors');
	    } elseif (is_array($THoursR) && count($THoursR)>0) {
	    	//heures totales réalisées par le stagiaire
		    $detailHours='';
		    foreach($THoursR as $typ=>$trainer_hrs) {
				foreach($trainer_hrs as $trainerid=>$hrs) {
					$detailHours.=$planningTrainee->TTypeTimeByCode[$typ]['label'].':'.$hrs;
					if (!empty($trainerid)) {
						if (!array_key_exists($trainerid, $TtrainerName)) {
							$result = $session_trainer->fetch($trainerid);
							if ($result < 0) {
								setEventMessage($session_trainer->error, 'errors');
							} elseif($result==1) {
								$TtrainerName[$trainerid] = $session_trainer->firstname . ' ' . $session_trainer->lastname;
							}
						}
						$detailHours.=' (' . $TtrainerName[$trainerid].')';
					}
					$detailHours.='<br/>';
					$heureRTotal+=$hrs;
				}
		    }
	    } else {
		    $heureRTotal=0;
	    }

        //heures totales restantes : durée de la session - heures réalisées totales
        $heureRestTotal = $agf->duree_session - $heureRTotal;

        print '<div class="" id="formPlannifTrainee">';
        print '<form name="obj_update" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
        print '<input type="hidden" name="action" value="edit">'."\n";
        print '<input type="hidden" name="sessid" value="'.$agf->id.'">'."\n";
        print '<input type="hidden" name="traineeid" value="'.$idTrainee_session.'">'."\n";
	    $modByTrainer=array();
        foreach ($TLinesTraineePlanning as $line) {
			if (empty($line->fk_agefodd_session_formateur)) {
				$modByTrainer[$line->fk_calendrier_type]['without'] = 1;
			}
	        if (!empty($line->fk_agefodd_session_formateur)) {
		        $modByTrainer[$line->fk_calendrier_type]['with'] = 1;
	        }
		}
	    $detailAlert='';
        foreach($modByTrainer as $typ=>$alert) {
			if (array_key_exists('without',$alert) && array_key_exists('with',$alert)) {
				$detailAlert.=$langs->trans('AgfPlanTraineeStrangeData',$planningTrainee->TTypeTimeById[$typ]['label']).'<br/>';
			}
        }
        if (!empty($detailAlert)) {
	        $pictoAlert = img_warning().$detailAlert;
        } else {
	        $pictoAlert='';
        }
	    print load_fiche_titre($langs->trans('AgfTraineePlanification', $trainee->civilite, $trainee->nom, $trainee->prenom, $agf->duree_session), '', '', 0, 0, '', '');
	    print $pictoAlert;
        print '<table class="noborder period" width="100%" id="planningTrainee">';

        //Titres
        print '<tr class="liste_titre">';
        print '<th width="15%" class="liste_titre" style="font-weight: bold;">'.$langs->trans('AgfCalendarType').'</th>';
        print '<th width="35%" class="liste_titre_hoursp_'.$idTrainee_session.'" style="font-weight: bold;">'.$langs->trans('AgfHoursP').' ('.$totalScheduledHoursTrainee.')</th>';
	    if (!empty($detailHours)) {
		    $picto = $form->textwithpicto('', $detailHours);
	    } else {
	    	$picto='';
	    }
        print '<th class="liste_titre_hoursr_'.$idTrainee_session.'" style="font-weight: bold;">'.$langs->trans('AgfHoursR').' ('.$heureRTotal.$picto.')</th>';
        print '<th class="liste_titre_hoursrest_'.$idTrainee_session.'" style="font-weight: bold;">'.$langs->trans('AgfHoursRest').' ('.$heureRestTotal.')</th>';
        print '<th class="linecoldelete center">&nbsp;</th>';
        print '</tr>';

        //Lignes par type de modalité
        foreach($TLinesTraineePlanning as $line)
        {
			//Match Trainer
	        if (!empty($line->fk_agefodd_session_formateur)) {
		        if (!array_key_exists($line->fk_agefodd_session_formateur, $TtrainerName)) {
			        $result = $session_trainer->fetch($line->fk_agefodd_session_formateur);
			        if ($result < 0) {
				        setEventMessage($session_trainer->error, 'errors');
			        } elseif($result==1) {
				        $TtrainerName[$line->fk_agefodd_session_formateur] = $session_trainer->firstname . ' ' . $session_trainer->lastname;
			        }
		        }
		        //Calcul heures restantes
		        $heureRest = $line->heurep - $THoursR[$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['code']][$line->fk_agefodd_session_formateur];
		        if ($heureRest<0) {
			        $heureRest ='<div style="color:red;font-weight: bold">'.$heureRest.' (' . $TtrainerName[$line->fk_agefodd_session_formateur].')</div>';
		        } else {
			        $heureRest .=' (' . $TtrainerName[$line->fk_agefodd_session_formateur].')';
		        }
		        //Calcul heures réalisé
		        if (!empty($THoursR[$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['code']][$line->fk_agefodd_session_formateur])) {
			        $heureDone = $THoursR[$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['code']][$line->fk_agefodd_session_formateur] . ' ('. $TtrainerName[$line->fk_agefodd_session_formateur].')';
		        } else {
			        $heureDone ='';
		        }
	        } else {
		        $heureDoneByModTotal=0;
		        $TrainerDoneByMod=array();
		        if(is_array($THoursR[$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['code']])) {
			        foreach ($THoursR[$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['code']] as $trainerid => $hrsDone) {
				        $heureDoneByModTotal += $hrsDone;
				        $TrainerDoneByMod[] = $TtrainerName[$trainerid];
			        }


			        if (!empty($heureDoneByModTotal)) {
				        $heureDone = $heureDoneByModTotal . ' (' . implode(',', $TrainerDoneByMod) . ')';
				        $heureRest = $line->heurep - $heureDoneByModTotal;
			        } else {
				        $heureRest = $line->heurep - $THoursR[$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['code']][0];
				        $heureDone = $THoursR[$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['code']][0];
			        }
		        }
	        }

            print '<tr>';

            //Type créneau
            print '<td>'.$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['label'].'</td>';
            //Heure planifé
            print '<td>'.$line->heurep.(array_key_exists($line->fk_agefodd_session_formateur, $TtrainerName)?' (' . $TtrainerName[$line->fk_agefodd_session_formateur].')':'').'</td>';
            //Heure réalisées
            print '<td>'.$heureDone.'</td>';
            //Heures restantes
            print '<td>'.$heureRest.'</td>';

            print '<td class = "linecoldelete center"><a href='.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'&idPlanningHoursToRemove='.$line->rowid.'>'. img_picto($langs->trans("Delete"), 'delete') . '</a></td>';

            print '</tr>';

        }

        print '<tr class="pair nodrag nodrop nohoverpair liste_titre_create" >';
        print '<td></td>';
        print '<td class="fieldrequired">'.$langs->trans('AgfCalendarType').' '.$formAgefodd->select_calendrier_type('', 'code_c_session_calendrier_type').'</td>';
        print '<td class="fieldrequired">'.$langs->trans('AgfAddScheduledHours').' <input  name="heurep">&nbsp;</input></td>';
	    if (! empty($conf->global->AGF_DOL_TRAINER_AGENDA)) {
		    print '<td>' . $langs->trans('AgfTrainer') . ' ' . $formAgefodd->selectSessionTrainer($id, '', 'trainerid') . '</td>';
	    } else {
	    	print '<td></td>';
	    }
        print '<td class="linecoldelete center">&nbsp;</td>';
        print '</tr>';
        print '</table>';

        print '<div class="tabsAction">';
        print '<div class="inline-block divButAction">';
        print '<input type="submit" class="butAction" name="addHours" value="'.$langs->trans('AgfNewHoursP').'">';
        print '</div>';
        print '</div>';

        print '</form>';

    }
}

llxFooter();
$db->close();
