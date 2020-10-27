<?php
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
    $res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
    die("Include of main fails");

dol_include_once('/core/lib/functions.lib.php');
dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');

$put = GETPOST('put', 'none');
$idTraining = GETPOST('idTraining', 'none');

switch ($put){
    case 'printform':
        printForm($idTraining);
        break;

    Default:
        break;
}

function printForm($idTraining){
    global $db, $user,$langs;

    $agf_peda = new Formation($db);
    $result_peda = $agf_peda->fetch_objpeda_per_formation($idTraining);

    $form = '<form name="obj_peda" id="obj_peda" action="' . dol_buildpath('/agefodd/training/card.php',1) . "?id=" . $idTraining . '" method="POST">' . "\n";
    $form.= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
    $form.= '<input type="hidden" name="action" value="ajax_obj_update">' . "\n";
    $form.= '<input type="hidden" name="idforma" value="' . $idTraining . '">' . "\n";
    $form.= '<span style="display:none" id="imgdel">'.img_picto($langs->trans("Delete"), 'delete').'</span>';
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

    $i = 0;
    foreach ( $agf_peda->lines as $line ) {
        $form.= '<tr><td align="center" width="40">' . "\n";
        $form.= '<input name="pedago[' . $i . '][priorite]" class="flat" size="4" value="' . $line->priorite . '">';
        $form.= '<input type="hidden" name="pedago[' . $i . '][id]" value="' . $line->id . '"></td>';

        $form.= '<td ><input name="pedago[' . $i . '][intitule]" class="flat" size="50" value="' . stripslashes($line->intitule) . '"></td>' . "\n";

        $form.= '<td><a href="#" class="obj_remove_x">' . img_picto($langs->trans("Delete"), 'delete') . '</a></td>';

        $form.= '</tr>' . "\n";
        $priorite = $line->priorite;
        $i++;
    }
    if ($user->rights->agefodd->agefodd_formation_catalogue->creer) {
        $form.= '<tr id="savepedago"><td colspan="3" style="text-align:right">';
        $form.= '<input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" name="obj_update" alt="' . $langs->trans("AgfModSave") . '">';
        $form.= '</td></tr>';
    }
    /*
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
    }*/
    $form.= '</table>' . "\n";
    $form.= '</form>' . "\n";

    $form.= '<script>
                $(".obj_remove_x").each(function(){
                    $(this).click(function(e) {
                        e.preventDefault();
                        $(this).parent().parent().remove();
                    });
                });

                $("#addOne").click(function(e){
                    var lastPriority = parseInt($(".obj_remove_x").last().parent().parent().find("input").first().val()) + 1;
                    var objLength = $(".obj_remove_x").length;
                    if (isNaN(lastPriority)){ lastPriority = 1; }
                    e.preventDefault();
                    $("<tr><td align=\"center\" width=\"40\"><input name=\"pedago["+objLength+"][priorite]\" class=\"flat\" size=\"4\" value="+lastPriority+" ><input type=\"hidden\" name=\"pedago["+objLength+"][id]\"></td><td><input name=\"pedago["+objLength+"][intitule]\" class=\"flat\" size=\"50\"></td><td><a href=\"#\" class=\"obj_remove_x\">"+$("#imgdel").html()+"</a></td></tr>").insertBefore("#savepedago");
                    $(".obj_remove_x").last().click(function(e) {
                            e.preventDefault();
                            $(this).parent().parent().remove();
                    });

                });

            </script>';

    print json_encode(array('idtraining'=>$idTraining, 'form' => $form));
}

