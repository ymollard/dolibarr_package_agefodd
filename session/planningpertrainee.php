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
$sessid = GETPOST('sessid', 'alpha');
$codeCalendar = GETPOST('code_c_session_calendrier_type', 'alpha');
$hoursp = GETPOST('heurep', 'alpha');
$traineeid = GETPOST('traineeid', 'alpha');
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

        if(($totalScheduledHoursTrainee+$hoursp) > $agf->duree_session) {
            $error ++;
            $error_message = $langs->trans('AgfErrorHoursPTraineeHoursSess');
        }

        if(!$error)
        {
            $res = $planningTrainee->verifyAlreadyExist($sessid, $traineeid, $codeCalendar);

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
                        $error_message= $langs->trans("AgfErrorUpdate");
                    }
                } else {
                    $error ++;
                    $error_message= $langs->trans("AgfErrorFetchPlanification");
                }
            }
            else        //créaton de la ligne
            {
                $planningTrainee->fk_session_stagiaire = $traineeid;
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

                $planningTrainee->heurep = $hoursp;
                if($planningTrainee->heurep < 0) $planningTrainee->heurep = 0;

                $res = $planningTrainee->create($user);

                if($res <= 0){
                    $error ++;
                    $error_message = $langs->trans("AgfErrorCreateTraineePlannification");
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
                $error_message = $langs->trans("AgfErrorDeleteTraineePlanification");
            }
        } else {
            $error++;
            $error_message = $langs->trans("AgfErrorFetchPlanification");
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

//Tableau pour chaque participant de la session
$session_trainee = new Agefodd_session_stagiaire($db);
$res = $session_trainee->fetch_stagiaire_per_session($id);

if($res > 0)
{
    foreach ($session_trainee->lines as $trainee)
    {
        $idTrainee_session = $trainee->stagerowid;
        $idtrainee = $trainee->id;

        //Tableau de toutes les heures plannifiées du participant
        $planningTrainee = new AgefoddSessionStagiairePlanification($db);
        $TLinesTraineePlanning = $planningTrainee->getSchedulesPerCalendarType($id, $idTrainee_session);

        //Nombre d'heures planifiées
        $totalScheduledHoursTrainee = $planningTrainee->getTotalScheduledHoursbyTrainee($id, $idTrainee_session);
        if(empty($totalScheduledHoursTrainee)) $totalScheduledHoursTrainee = 0;

        //heures réalisées par type de créneau
        $trainee_hr = new Agefoddsessionstagiaireheures($db);
        $THoursR = $trainee_hr->fetch_heures_stagiaire_per_type($id, $idtrainee);

        //heures totales réalisées par le stagiaire
        $heureRTotal = array_sum($THoursR);
        if(empty($heureRTotal)) $heureRTotal = 0;

        //heures totales restantes : durée de la session - heures réalisées totales
        $heureRestTotal = $agf->duree_session - $heureRTotal;

        print '<div class="" id="formPlannifTrainee">';
        print '<form name="obj_update" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
        print '<input type="hidden" name="action" value="edit">'."\n";
        print '<input type="hidden" name="sessid" value="'.$agf->id.'">'."\n";
        print '<input type="hidden" name="traineeid" value="'.$idTrainee_session.'">'."\n";
        print load_fiche_titre($langs->trans('AgfTraineePlanification', $trainee->civilite, $trainee->nom, $trainee->prenom, $agf->duree_session), '', '', 0, 0, '', $massactionbutton);

        print '<table class="noborder period" width="100%" id="planningTrainee">';

        //Titres
        print '<tr class="liste_titre">';
        print '<th width="15%" class="liste_titre" style="font-weight: bold;">'.$langs->trans('AgfCalendarType').'</th>';
        print '<th width="35%" class="liste_titre_hoursp_'.$idTrainee_session.'" style="font-weight: bold;">'.$langs->trans('AgfHoursP').' ('.$totalScheduledHoursTrainee.')</th>';
        print '<th class="liste_titre_hoursr_'.$idTrainee_session.'" style="font-weight: bold;">'.$langs->trans('AgfHoursR').' ('.$heureRTotal.')</th>';
        print '<th class="liste_titre_hoursrest_'.$idTrainee_session.'" style="font-weight: bold;">'.$langs->trans('AgfHoursRest').' ('.$heureRestTotal.')</th>';
        print '<th class="linecoldelete center">&nbsp;</th>';
        print '</tr>';

        //Lignes par type de modalité
        foreach($TLinesTraineePlanning as $line)
        {
            //Modalité
            $sql = "SELECT";
            $sql .= " label, code ";
            $sql .= " FROM ".MAIN_DB_PREFIX."c_agefodd_session_calendrier_type";
            $sql .= " WHERE rowid = '".$line->fk_calendrier_type . "'";
            $resql = $db->query($sql);

            if($resql)
            {
                $obj = $db->fetch_object($resql);
                $codeCalendrierType = $obj->code;
                $codeCalendrierLabel = $obj->label;
            }

            //Calcul heures restantes
            $heureRest = $line->heurep - $THoursR[$codeCalendrierType];

            print '<tr>';

            //Type créneau
            print '<td>'.$codeCalendrierLabel.'</td>';
            //Heure saisie prévue
            print '<td>'.$line->heurep.'</td>';
            //Heure réalisées
            print '<td>'.$THoursR[$codeCalendrierType].'</td>';
            //Heures restantes
            print '<td>'.$heureRest.'</td>';

            print '<td class = "linecoldelete center"><a href='.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'&idPlanningHoursToRemove='.$line->rowid.'>'. img_picto($langs->trans("Delete"), 'delete') . '</a></td>';

            print '</tr>';

        }

        print '<tr class="pair nodrag nodrop nohoverpair liste_titre_create" >';
        print '<td></td>';
        print '<td>'.$langs->trans('AgfCalendarType').' '.$formAgefodd->select_calendrier_type('', 'code_c_session_calendrier_type').'</td>';
        print '<td>'.$langs->trans('AgfAddScheduledHours').' <input  name="heurep">&nbsp;</input></td>';
        print '<td>&nbsp;</td>';
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
