<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
* Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
* Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
* Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
* Copyright (C) 2014      Florian Henry		  	<florian.henry@open-concept.pro>
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 *       \file       /sendinblue/sendinblue/contact_activites.php
 *       \ingroup    sendinblue
 *       \brief      Card of a contact sendinblue activites
 */

$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

// Test
if(empty($conf->sendinblue->enabled)) die('Plug in not installed');



require_once ('../class/agefodd_stagiaire.class.php');
require_once ('../class/agsession.class.php');
require_once ('../class/html.formagefodd.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php');
require_once ('../lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../class/agefodd_session_stagiaire.class.php');


// LOAD
require_once dol_buildpath('sendinblue/class/dolsendinblue.class.php');
require_once dol_buildpath('sendinblue/class/html.formsendinblue.class.php');

$langs->load("companies");
$langs->load("users");
$langs->load("other");
$langs->load("commercial");
$langs->load("sendinblue@sendinblue");

$mesg=''; $error=0; $errors=array();

$action		= (GETPOST('action','alpha') ? GETPOST('action','alpha') : 'view');
$confirm	= GETPOST('confirm','alpha');
$backtopage = GETPOST('backtopage','alpha');
$id			= GETPOST('id','int');
$socid		= GETPOST('socid','int');
$listid		= GETPOST('listid','alpha');
if ($user->societe_id) $socid=$user->societe_id;

$object = new Agefodd_stagiaire($db);
$sendinblue= new DolSendinBlue($db);



// Si edition contact deja existant
$res=$object->fetch($id);
if ($res < 0) { dol_print_error($db,$object->error); exit; }
$res=$object->fetch_optionals($object->id,$extralabels);



// Security check
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe', '', '', 'rowid'); // If we create a contact with no company (shared contacts), no check on write permission

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('sendinbluetraineetab'));

/*
 *	Actions
*/

$parameters=array('id'=>$id);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
$error=$hookmanager->error; $errors=array_merge($errors, (array) $hookmanager->errors);

if (empty($reshook))
{

}

if ($action=='unsubscribe') {
	$result = $sendinblue->deleteEmailFromList($listid,array($object->mail));
	if ($result<0) {
		setEventMessage($sendinblue->error,'errors');
	}
}

if ($action=='subscribe') {

    $array_email = array();

    if(empty($object->fk_socpeople)) $array_email[] = $object->mail.'&trainee&'.$object->id;
    else $array_email[] = $object->mail.'&contact&'.$object->fk_socpeople;

    $result = $sendinblue->addEmailToList($listid,$array_email);

	if ($result<0) {
		setEventMessage($sendinblue->error,'errors');
	}
}

/*
 *	View
*/


$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("SendinBlueActivites"),$help_url);

$form = new Form($db);
$formcompany = new FormCompany($db);

$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';



// Show tabs

$head = trainee_prepare_head($object);

$title = $langs->trans("AgfStagiaireDetail");
dol_fiche_head($head, 'seninblue', $title, 0, 'contact');

dol_agefodd_banner_tab($object, 'id');
print '<div class="underbanner clearboth"></div>';

/*
 * SendinBlue list subscription
 */

//find is email is in the list
if($conf->global->SENDINBLUE_API_KEY){


    if(empty($object->mail)){
        print '<div class="info" >'.$langs->trans('AgfNeedEmail').'</div>';
    }
    else{

        $result = $sendinblue->getListForEmail($object->mail);
        if ($result<0) {
            setEventMessage($sendinblue->error,'errors');
        }
        $list_subcribed_id=array();
        if (is_array($sendinblue->listlist_lines) && count($sendinblue->listlist_lines)>0) {
            foreach ($sendinblue->listlist_lines as $listsubcribed) {
                $list_subcribed_id[]=$listsubcribed;
            }
        }


        $result=$sendinblue->getListDestinaries();
        if ($result<0) {
            setEventMessage($sendinblue->error,'errors');
        }

        if (is_array($sendinblue->listdest_lines) && count($sendinblue->listdest_lines)>0) {
            print load_fiche_titre($langs->trans("SendinBlueDestList"),'','');

            print '<link rel="stylesheet" href="'.dol_buildpath('seninblue/script/style.css',1).'" />';
            print '<table class="border" width="100%">';
            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans('SendinBlueListName').'</td>';
            print '<td>'.$langs->trans('SendinBlueContactIsList').'</td>';
            print '<td>'.$langs->trans('SendinBlueSubscribersState').'</td>';
            print '</tr>';

            foreach($sendinblue->listdest_lines['data'] as $line) {
                //Si le contact n'a pas d'email

                if($object->mail == null){
                    $var=!$var;
                    print "<tr " . $bc[$var] . ">";
                    print '<td width="20%"><a target="_blanck" href="https://my.sendinblue.com/users/list/id/'.$line['id'].'">'.$line['name'].'</a></td>';
                    print '<td>';

                    print '<link rel="stylesheet" href="../script/style.css" />';
                    print "<div style='position:relative;' >";
                    print "<div class='sendinblue_grise'></div>";
                    print img_picto($langs->trans("Enabled"),'switch_off');
                    print "</div>";


                    print "</td>\n";
                    print '<td>';


                    print $langs->trans("NoEmail");

                    print "</td>\n";

                    print '</tr>';
                }


                //Récupération du statut
                else if($object->mail != null){
                    $var=!$var;
                    //var_dump($line);exit;
                    $emails = array(array('email' => $object->mail));
                    if(!in_array($line['id'],$list_subcribed_id)){
                        //$result = $sendinblue->sendinblue->get('lists/'.$line['id'].'/members/'.$sendinblue->sendinblue->subscriberHash($object->mail));
                        $statut = 'cleaned';
                    }else {
                        $statut = 'subscribed';
                    }

                    print "<tr " . $bc[$var] . ">";
                    print '<td width="20%"><a target="_blanck" href="https://my.sendinblue.com/users/list/id/'.$line['id'].'">'.$line['name'].'</a></td>';
                    print '<td>';
                    if ($statut == 'subscribed') {
                        print '<a href="'.$_SERVER['PHP_SELF'].'?action=unsubscribe&id='.$object->id.'&listid='.$line['id'].'">';
                        print img_picto($langs->trans("Disabled"),'switch_on');
                        print '</a>';
                    }  /*else if($sendinblue->isUnsubscribed($line['id'], $object->mail) || $object->status == 0){
					var_dump(array($line['id'], $object->mail, $object->status));
					print "<div style='position:relative;' >";
					print "<div class='sendinblue_grise'></div>";
					print img_picto($langs->trans("Enabled"),'switch_off');
					$statut='unsubscribed';
					print "</div>";
				}*/ else {
                        print '<a href="'.$_SERVER['PHP_SELF'].'?action=subscribe&id='.$object->id.'&listid='.$line['id'].'">';
                        print img_picto($langs->trans("Enabled"),'switch_off');
                        print '</a>';
                    }
                    print "</td>\n";
                    print '<td>';

                    if($statut != null){
                        print $langs->trans("SendinBlueStatus".$statut);
                    }
                    print "</td>\n";
                    print '</tr>';

                }
                print "</td>\n";

                print '</tr>';
            }
            print '</table>';
        }

        /*
         * SendinBlue Campagin Actvites
         */

        $result=$sendinblue->getEmailcontactActivites($object->mail);

        if ($result<0) {
            setEventMessage($sendinblue->error,'errors');
        }
        $sendinbluestatic= new DolSendinBlue($db);
    }
}else{
	setEventMessage($langs->trans('InvalidAPIKey'),'errors');
}

dol_fiche_end();

llxFooter();
$db->close();
