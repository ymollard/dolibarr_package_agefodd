<?php
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
    $res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
    die("Include of main fails");
    
dol_include_once('/core/lib/functions.lib.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');

$put = GETPOST('put');
$idTraining = GETPOST('idTraining');

switch ($put){
    case 'printform':
        printForm($idTraining);
        break;
       
    Default:
        break;
}

function printForm($idTraining){
    global $db, $user,$langs;

    $agf_peda = new Agefodd($db);
    $result_peda = $agf_peda->fetch_objpeda_per_formation($idTraining);
    
    $form = '<form name="obj_peda" id="obj_peda" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
    $form.= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
    $form.= '<input type="hidden" name="action" value="obj_update">' . "\n";
    $form.= '<input type="hidden" name="idforma" value="' . $id . '">' . "\n";
    $form.= '<table class="border" width="100%">';
    $form.= '<tr>';
    if (count($agf_peda->lines) > 0) {
        $form.= '<td align="center" width="50">' . $langs->trans("AgfObjPoids") . '</td>';
        $form.= '<td>' . $langs->trans("AgfObjDesc") . '</td>';
    } elseif (empty($objc)) {
        $form.= '<td width="10%" colspan="2">' . $langs->trans("AgfNoObj") . '</td>';
    }
    $form.= '<td>';
    if (empty($objc) && $user->rights->agefodd->agefodd_formation_catalogue->creer) {
        $form.= '&nbsp;<a id="addOne" href="#">';
        $form.= img_edit_add($langs->trans("AgfNewObjAdd")) . "</a>";
    }
    $form.= '</td>';
    $form.= '</tr>';
    
    foreach ( $agf_peda->lines as $line ) {
        $form.= '<tr><td align="center" width="40">' . "\n";
        $form.= '<input name="priorite_' . $line->id . '" class="flat" size="4" value="' . $line->priorite . '"></td>';
        
        $form.= '<td width="400"><input name="intitule_' . $line->id . '" class="flat" size="50" value="' . stripslashes($line->intitule) . '"></td>' . "\n";
        
        $form.= "<td>";
        
        if ($user->rights->agefodd->agefodd_formation_catalogue->creer) {
            $form.= '<a href="#" class="obj_remove_x">' . img_picto($langs->trans("Delete"), 'delete') . '</a>';
        }
        
        $form.= '</tr>' . "\n";
        $priorite = $line->priorite;
    }
    if ($user->rights->agefodd->agefodd_formation_catalogue->creer) {
        $form.= '<tr><td colspan="3">';
        $form.= '<input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" name="obj_update" alt="' . $langs->trans("AgfModSave") . '">';
        $form.= '</td></tr>';
    }
    
    // New Objectif peda line
    if (! empty($objc)) {
        $form.= '<table class="border" width="100%">';
        $priorite ++;
        $form.= '<tr><td align="center" width="40">' . "\n";
        $form.= '<input name="priorite_new" id="priorite_new" class="flat" size="4" value="' . $priorite . '"></td>';
        $form.= '<td width="400"><input name="intitule_new" class="flat" size="50"></td>' . "\n";
        $form.= "<td>";
        if ($user->rights->agefodd->agefodd_formation_catalogue->creer) {
            $form.= '<input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" name="obj_add" alt="' . $langs->trans("AgfNewObjAdd") . '">';
        }
        $form.= '</td></tr>' . "\n";
    }
    $form.= '</table>' . "\n";
    $form.= '</form>' . "\n";
    
    $form.= '<script>
                $(".obj_remove_x").each(function(){
                    $(this).click(function(e) {
                        e.preventDefault();
                        $(this).parent().parent().remove();
                    });
                });
            </script>';

    print json_encode(array('idtraining'=>$idTraining, 'form' => $form));
}

