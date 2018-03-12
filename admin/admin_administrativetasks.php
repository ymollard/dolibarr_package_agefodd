<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2017	Florian Henry	<florian.henry@open-concept.pro>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file /agefodd/admin/admin_administrativetasks.php
 * \ingroup agefodd
 * \brief agefood module setup page
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res) $res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res) die("Include of main fails");

require_once '../class/agefodd_formation_catalogue.class.php';
require_once '../class/agefodd_session_admlevel.class.php';
require_once '../class/agefodd_calendrier.class.php';
require_once '../class/html.formagefodd.class.php';
require_once '../lib/agefodd.lib.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("admin");
$langs->load('agefodd@agefodd');

if (! $user->rights->agefodd->admin && ! $user->admin)
    accessforbidden();
    
$action = GETPOST('action', 'alpha');

if ($action == 'sessionlevel_create') {
    $agf = new Agefodd_session_admlevel($db);
    
    $parent_level = GETPOST('parent_level', 'int');
    
    if (! empty($parent_level)) {
        $agf->fk_parent_level = $parent_level;
        
        $agf_static = new Agefodd_session_admlevel($db);
        $result_stat = $agf_static->fetch($agf->fk_parent_level);
        
        if ($result_stat > 0) {
            if (! empty($agf_static->id)) {
                $agf->level_rank = $agf_static->level_rank + 1;
                $agf->indice = ebi_get_adm_get_next_indice_action($agf_static->id);
            } else { // no parent : This case may not occur but we never know
                $agf->indice = (ebi_get_adm_level_number() + 1) . '00';
                $agf->level_rank = 0;
            }
        } else {
            setEventMessage($agf_static->error, 'errors');
        }
    } else {
        // no parent
        $agf->fk_parent_level = 0;
        $agf->indice = (ebi_get_adm_level_number() + 1) . '00';
        $agf->level_rank = 0;
    }
    
    $agf->intitule = GETPOST('intitule', 'alpha');
    $agf->delais_alerte = GETPOST('delai', 'int');
    $agf->delais_alerte_end = GETPOST('delai_end', 'int');
    
    // prevent mysql error
    if(empty($agf->delais_alerte)){ $agf->delais_alerte = 0 ; }
    if(empty($agf->delais_alerte_end)){ $agf->delais_alerte_end= 0 ; }
    
    if ($agf->level_rank > 3) {
        setEventMessage($langs->trans("AgfAdminNoMoreThan3Level"), 'errors');
    } else {
        $result = $agf->create($user);
        
        if ($result1 != 1) {
            setEventMessage($agf->error, 'errors');
        }
    }
}

if ($action == 'sessionlevel_update') {
    $agf = new Agefodd_session_admlevel($db);
    
    $id = GETPOST('id', 'int');
    $parent_level = GETPOST('parent_level', 'int');
    
    $result = $agf->fetch($id);
    
    if ($result > 0) {
        
        // Up level of action
        if (GETPOST('sesslevel_up_x')) {
            $result2 = $agf->shift_indice($user, 'less');
            if ($result1 != 1) {
                setEventMessage($agf->error, 'errors');
            }
        }
        
        // Down level of action
        if (GETPOST('sesslevel_down_x')) {
            $result1 = $agf->shift_indice($user, 'more');
            if ($result1 != 1) {
                setEventMessage($agf->error, 'errors');
            }
        }
        
        // Update action
        if (GETPOST('sesslevel_update_x')) {
            $agf->intitule = GETPOST('intitule', 'alpha');
            $agf->delais_alerte = GETPOST('delai', 'int');
            $agf->delais_alerte_end = GETPOST('delai_end', 'int');
            
            // prevent mysql error
            if(empty($agf->delais_alerte)){ $agf->delais_alerte = 0 ; }
            if(empty($agf->delais_alerte_end)){ $agf->delais_alerte_end= 0 ; }
            
            if (! empty($parent_level)) {
                if ($parent_level != $agf->fk_parent_level) {
                    $agf->fk_parent_level = $parent_level;
                    
                    $agf_static = new Agefodd_session_admlevel($db);
                    $result_stat = $agf_static->fetch($agf->fk_parent_level);
                    
                    if ($result_stat > 0) {
                        if (! empty($agf_static->id)) {
                            $agf->level_rank = $agf_static->level_rank + 1;
                            $agf->indice = ebi_get_adm_get_next_indice_action($agf_static->id);
                            
                        } else { // no parent : This case may not occur but we never know
                            $agf->indice = (ebi_get_adm_level_number() + 1) . '00';
                            $agf->level_rank = 0;
                        }
                    } else {
                        setEventMessage($agf_static->error, 'errors');
                    }
                }
            } else {
                // no parent
                $agf->fk_parent_level = 0;
                $agf->level_rank = 0;
            }
            
            if ($agf->level_rank > 3) {
                setEventMessage($langs->trans("AgfAdminNoMoreThan3Level"), 'errors');
            } else {
                $result1 = $agf->update($user);
                if ($result1 != 1) {
                    setEventMessage($agf->error, 'errors');
                }
            }
        }
        
        // Delete action
        if (GETPOST('sesslevel_remove_x')) {
            
            $result = $agf->delete($user);
            if ($result != 1) {
                setEventMessage($agf->error, 'errors');
            }
        }
    } else {
        setEventMessage('This action do not exists', 'errors');
    }
}

/*
 *  Admin Form
 *
 */

llxHeader('', $langs->trans('AgefoddSetupDesc'));

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formother=new FormOther($db);

dol_htmloutput_mesg($mesg);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans("AgefoddSetupDesc"), $linkback, 'setup');

// Configuration header
$head = agefodd_admin_prepare_head();
dol_fiche_head($head, 'administrativetasks', $langs->trans("Module103000Name"), 0, "agefodd@agefodd");

// Admin Training level administation

$admlevel = new Agefodd_session_admlevel($db);
$result0 = $admlevel->fetch_all();

print_titre($langs->trans("AgfAdminSessionLevel"));

if ($result0 > 0) {
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td width="10px"></td>';
    print '<td>' . $langs->trans("AgfIntitule") . '</td>';
    print '<td>' . $langs->trans("AgfParentLevel") . '</td>';
    print '<td>' . $langs->trans("AgfDelaiSessionLevel") . '</td>';
    print '<td>' . $langs->trans("AgfDelaiSessionLevelEnd") . '</td>';
    print '<td></td>';
    print "</tr>\n";
    
    $var = true;
    foreach ( $admlevel->lines as $line ) {
        $var = ! $var;
        $toplevel = '';
        print '<form name="SessionLevel_update_' . $line->rowid . '" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
        print '<input type="hidden" name="id" value="' . $line->rowid . '">' . "\n";
        print '<input type="hidden" name="action" value="sessionlevel_update">' . "\n";
        print '<tr ' . $bc[$var] . '>';
        
        print '<td>';
        if ($line->indice != ebi_get_adm_indice_per_rank($line->level_rank, $line->fk_parent_level, 'MIN')) {
            print '<input type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/1uparrow.png" border="0" name="sesslevel_up" alt="' . $langs->trans("Save") . '">';
        }
        if ($line->indice != ebi_get_adm_indice_per_rank($line->level_rank, $line->fk_parent_level, 'MAX')) {
            print '<input type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/1downarrow.png" border="0" name="sesslevel_down" alt="' . $langs->trans("Save") . '">';
        }
        print '</td>';
        
        print '<td>' . str_repeat('&nbsp;&nbsp;&nbsp;', $line->level_rank) . '<input type="text" name="intitule" value="' . $line->intitule . '" size="30"/></td>';
        print '<td>' . $formAgefodd->select_action_session_adm($line->fk_parent_level, 'parent_level', $line->rowid) . '</td>';
        print '<td><input type="text" name="delai" value="' . $line->alerte . '" size="2"/></td>';
        print '<td><input type="text" name="delai_end" value="' . $line->alerte_end . '" size="2"/></td>';
        print '<td><input type="image" src="' . dol_buildpath('/agefodd/img/save.png', 1) . '" border="0" name="sesslevel_update" alt="' . $langs->trans("Save") . '">';
        print '<input type="image" src="' . img_picto($langs->trans("Delete"), 'delete','',false,1).'" border="0" name="sesslevel_remove" alt="' . $langs->trans("Delete") . '"></td>';
        print '</tr>';
        print '</form>';
    }
}
print '<form name="SessionLevel_create" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";
print '<input type="hidden" name="action" value="sessionlevel_create">' . "\n";
print '<tr>';
print '<td></td>';
print '<td><input type="text" name="intitule" value="" size="30"/></td>';
print '<td>' . $formAgefodd->select_action_session_adm('', 'parent_level') . '</td>';
print '<td><input type="text" name="delai" value=""/></td>';
print '<td><input type="text" name="delai_end" value=""/></td>';
print '<td><input type="image" src="' . img_picto($langs->trans("Save"), 'edit_add','',false,1).'" border="0" name="sesslevel_update" alt="' . $langs->trans("Save") . '"></td>';
print '</tr>';
print '</form>';
print '</table><br>';

llxFooter();
$db->close();