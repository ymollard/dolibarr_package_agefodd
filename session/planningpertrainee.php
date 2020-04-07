<?php

$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
    $res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
    die("Include of main fails");

require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_sessadm.class.php');
require_once ('../class/agefodd_session_admlevel.class.php');
require_once ('../class/html.formagefodd.class.php');
require_once ('../class/agefodd_session_calendrier.class.php');
require_once ('../class/agefodd_calendrier.class.php');
require_once ('../class/agefodd_session_formateur.class.php');
require_once ('../class/agefodd_session_stagiaire.class.php');
require_once ('../class/agefodd_session_stagiaire_heures.class.php');
require_once ('../class/agefodd_session_stagiaire_planification.class.php');
require_once ('../class/agefodd_session_element.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
require_once ('../class/agefodd_formation_catalogue.class.php');
require_once ('../class/agefodd_opca.class.php');

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
$hourp = GETPOST('heurep', 'alpha');
$traineeid = GETPOST('traineeid', 'alpha');
$hours_add = GETPOST('addHours', 'alpha');
$idhourtoremove = GETPOST('hourremove', 'alpha');

$agf = new Agsession($db);
if(!empty($id)) $result = $agf->fetch($id);
$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($agf->table_element);

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

    $agfSessTraineesP = new AgefoddSessionStagiairePlanification($db);

    if(!empty($hours_add))
    {
        $totalHoursTrainee = $agfSessTraineesP->getTotalHoursbySessAndTrainee($sessid, $traineeid);

        if(($totalHoursTrainee+$hourp) > $agf->duree_session) {
            $error ++;
            $error_message = $langs->trans('AgfErrorHoursPTraineeHoursSess');
        }

        if(!$error)
        {
            $res = $agfSessTraineesP->verifyAlreadyExist($sessid, $traineeid, $codeCalendar);

            if ($res > 0)
            {
                $res = $agfSessTraineesP->fetch($res);

                if ($res > 0)
                {
                    $agfSessTraineesP->heurep += $hourp;

                    $res = $agfSessTraineesP->update($user);

                    if($res <= 0){
                        $error ++;
                        $error_message= $langs->trans("AgfErrorUpdate");
                    }
                } else {
                    $error ++;
                    $error_message= $langs->trans("AgfErrorFetchPlanification");
                }
            }
            else
            {
                $agfSessTraineesP->fk_session_stagiaire = $traineeid;
                $agfSessTraineesP->fk_session = $sessid;

                $sql = "SELECT";
                $sql .= " rowid ";
                $sql .= " FROM ".MAIN_DB_PREFIX."c_agefodd_session_calendrier_type";
                $sql .= " WHERE code = '".$codeCalendar."'";
                $resql = $db->query($sql);

                if ($resql)
                {
                    $obj = $db->fetch_object($resql);
                    $agfSessTraineesP->fk_calendrier_type = $obj->rowid;
                } else {
                    $error ++;
                    $error_message = $langs->trans('Error');
                }

                $agfSessTraineesP->heurep = $hourp;

                $res = $agfSessTraineesP->create($user);

                if($res <= 0){
                    $error ++;
                    $error_message = $langs->trans("AgfErrorCreateTraineePlannification");
                }
            }
        }
    }
    elseif (!empty($idhourtoremove))
    {
        $res = $agfSessTraineesP->fetch($idhourtoremove);

        if($res > 0)
        {
            $res = $agfSessTraineesP->delete($user);

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

    foreach ($session_trainee->lines as $line)
    {

        //Tableau de toutes les heures plannifiées du participant
        $agfSessTraineesP = new AgefoddSessionStagiairePlanification($db);
        $TLinesTraineePlanification = $agfSessTraineesP->TotalHoursPerCalendarType($id, $line->id);

        //heures réalisées par type de créneau
        $trainee_hr = new Agefoddsessionstagiaireheures($db);
        $THoursR = $trainee_hr->fetch_heures_stagiaire_per_type($id, $line->id);

        //heures totales réalisées par le stagiaire
        $heureRTotal = array_sum($THoursR);

        //heures totales restantes : durée de la session - heures réalisées totales
        $heureRestTotal = $agf->duree_session - $heureRTotal;

        print '<div class="" id="formPlannifTrainee">';
        print '<form name="obj_update" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
        print '<input type="hidden" name="action" value="edit">'."\n";
        print '<input type="hidden" name="sessid" value="'.$agf->id.'">'."\n";
        print '<input type="hidden" name="traineeid" value="'.$line->id.'">'."\n";
        print load_fiche_titre($langs->trans('AgfTraineePlanification'), '', '', 0, 0, '', $massactionbutton);
        print '<table class="noborder period" width="100%" id="period">';

        //Titres
        print '<tr class="liste_titre">';
        print '<th width="15%" class="liste_titre">'.$langs->trans('').'</th>';
        print '<th width="35%" class="liste_titre">'.$langs->trans('AgfHoursP').'</th>';
        print '<th class="text-center" >'.$langs->trans('AgfHoursR').'</th>';
        print '<th class="liste_titre">'.$langs->trans('AgfHoursRest').'</th>';
        print '<th class="linecoldelete center">&nbsp;</th>';
        print '</tr>';

        //Totaux
        print '<tr>';
        print '<td style="text-decoration:underline;">Total</td>';
        print '<td style="text-decoration:underline;">'.$agf->duree_session.'</td>';
        print '<td style="text-decoration:underline;">'.$heureRTotal.'</td>';
        print '<td style="text-decoration:underline;">'.$heureRestTotal.'</td>';
        print '<td class="linecoldelete center">&nbsp;</td>';
        print '</tr>';

        //Lignes par type de modalité
        foreach($TLinesTraineePlanification as $lineP)
        {
            print '<tr>';

            //Modalité
            $sql = "SELECT";
            $sql .= " label, code ";
            $sql .= " FROM ".MAIN_DB_PREFIX."c_agefodd_session_calendrier_type";
            $sql .= " WHERE rowid = '".$lineP->fk_calendrier_type . "'";
            $resql = $db->query($sql);

            if($resql)
            {
                $obj = $db->fetch_object($resql);
                $codeCalendrierType = $obj->code;

                print '<td>'.$obj->label.'</td>';
            }

            //Heure saisie prévue
            print '<td>'.$lineP->heurep.'</td>';

            //Heure réalisées
            print '<td>'.$THoursR[$codeCalendrierType].'</td>';

            //Heure restante
            $heureRest = $lineP->heurep - $THoursR[$codeCalendrierType];
            print '<td>'.$heureRest.'</td>';

            print '<td class = "linecoldelete center"><a href='.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'&hourremove='.$lineP->rowid.'>'. img_picto($langs->trans("Delete"), 'delete') . '</a></td>';

            print '</tr>';

        }

        print '<tr class="pair nodrag nodrop nohoverpair liste_titre_create" >';
        print '<td></td>';
        print '<td>Modalité : '.$formAgefodd->select_calendrier_type('', 'code_c_session_calendrier_type').'</td>';
        print '<td>Heures plannifiées ( "-" pour retirer des heures) : <input  name="heurep">&nbsp;</input></td>';
        print '<td>&nbsp;</td>';
        print '<td class="linecoldelete center">&nbsp;</td>';
        print '</tr>';
        print '</table>';

        print '<div class="tabsAction">';
        print '<div class="inline-block divButAction">';
        print '<input type="submit" class="butAction" name="addHours" value="'.$langs->trans('AgfNewHoursR').'">';
        print '</div>';
        print '</div>';

        print '</form>';

    }

}

llxFooter();
$db->close();
