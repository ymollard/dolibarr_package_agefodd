<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2014   Florian Henry   <florian.henry@open-concept.pro>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file agefodd/session/document.php
 * \ingroup agefodd
 * \brief list of document
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res){ $res = @include ("../../../main.inc.php"); }// For "custom" directory
if (! $res){ die("Include of main fails"); }



require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_session_formateur.class.php');
require_once ('../class/agefodd_session_stagiaire.class.php');
require_once ('../class/agefodd_session_element.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../lib/agefodd.lib.php');
dol_include_once('questionnaire/class/questionnaire.class.php');
require_once ('../lib/agf_questionnaire.lib.php');

dol_include_once('/questionnaire/class/invitation.class.php');

$ret = $langs->loadLangs(array("questionnaire@questionnaire", "agfquestionnaire@agefodd"));
dol_include_once('/user/class/usergroup.class.php');
dol_include_once('/societe/class/societe.class.php');
dol_include_once('/contact/class/contact.class.php');



// Security check
if (! $user->rights->agefodd->lire) {
    accessforbidden();
}


$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$idQuestionnaire = GETPOST('idQuestionnaire', 'int');
$confirm = GETPOST('confirm', 'alpha');

if(empty($id)){
    print $langs->trans('SessionIdUnknow');
    exit;
}

$agf = false;
if ($id) {
    $agf = new Agsession($db);
    $result = $agf->fetch($id);

    if ($result <= 0 || empty($agf->id)) {
        print $langs->trans('SessionIdUnknow');
        exit;
    }
}


/*
* Actions
*/
if($action == 'linkquestionnaire')
{
    $questionnaire = GETPOST('questionnaire' , 'int');

    if(!empty($questionnaire)){
        if($agf->add_object_linked('questionnaire', $questionnaire)>0){
            setEventMessage($langs->trans('agfQuestionnaireLinkAdded'));
        }
        else{
            setEventMessage($langs->trans('agfQuestionnaireLinkAddError'));
        }

        header('Location: '.dol_buildpath('/agefodd/session/questionnaire.php', 1).'?id='.$agf->id);
        exit;
    }

}
elseif($action == 'unlink')
{
    $questionnaire = GETPOST('questionnaire' , 'int');

    if(!empty($questionnaire)){

        if($agf->deleteObjectLinked($questionnaire, 'questionnaire') >0){
            setEventMessage($langs->trans('agfQuestionnaireLinkRemoved'));
        }
        else{
            setEventMessage($langs->trans('agfQuestionnaireLinkRemoveError'));
        }

        header('Location: '.dol_buildpath('/agefodd/session/questionnaire.php', 1).'?id='.$agf->id);
        exit;
    }

}


/*
* View
*/

llxHeader('', $langs->trans("AgfSessionQuestionaire"), '', '', 0, 0, '', array('agefodd/css/questionnaire.css'));

$head = session_prepare_head($agf);

if ($agf->type_session == 1) {
    $styledisplay = ' style="display:none" ';
}

dol_fiche_head($head, 'survey', $langs->trans("AgfSessionsurvey"), 0, 'survey');

$agf_fact = new Agefodd_session_element($db);
$agf_fact->fetch_by_session($agf->id);

$cost_trainer_engaged = $agf_fact->trainer_engaged_amount;
$cost_site_engaged = $agf_fact->room_engaged_amount;
$cost_trip_engaged = $agf_fact->trip_engaged_amount;

$engaged_revenue = $agf_fact->propal_sign_amount;
$paied_revenue = $agf_fact->invoice_payed_amount + $agf_fact->invoice_ongoing_amount;
$other_amount = '(' . $langs->trans('AgfProposalAmountSigned') . ' ' . $agf_fact->propal_sign_amount . ' ' . $langs->trans('Currency' . $conf->currency);
if (! empty($conf->commande->enabled)) {
    $other_amount .= '/' . $langs->trans('AgfOrderAmount') . ' ' . $agf_fact->order_amount . ' ' . $langs->trans('Currency' . $conf->currency);
}
$other_amount .= '/' . $langs->trans('AgfInvoiceAmountWaiting') . ' ' . $agf_fact->invoice_ongoing_amount . ' ' . $langs->trans('Currency' . $conf->currency);
$other_amount .= '/' . $langs->trans('AgfInvoiceAmountPayed') . ' ' . $agf_fact->invoice_payed_amount . ' ' . $langs->trans('Currency' . $conf->currency) . ')';

dol_agefodd_banner_tab($agf, 'id');

print '</div>';


$form = new Form($db);
$objQuestionnaire = new Questionnaire($db);
if($objQuestionnaire->fetch($idQuestionnaire) < 1){
        $objQuestionnaire = false;
}


?>

<div class="agf_row" >
    <div class="agf_col-3 agf_left_nav">
        <h4><?php print $langs->trans('LinkedQuestionnaire') ?></h4>

        <?php if(!empty($user->rights->agefodd->questionnaire->link)){ ?>
        <form name="answerQuestionnaire" method="POST" action="<?php print $_SERVER['PHP_SELF'].'?id='.$id; ?>">
        <?php
            print getQuestionnaireSessionListForm($agf->id, 'notlinked', 'questionnaire');
        ?>
            <button class="button" type="submit" name="action" value="linkquestionnaire" ><?php print $langs->trans('Add') ?></button>
        </form>
        <?php } ?>

        <div class="agf_list_nav" >

            <ul class="agf_nav agf_nav-pills agf_nav-stacked">
                <?php
                $linkedList = getQuestionnaireSessionList($agf->id, 'linked');

                if(!empty($linkedList) && is_array($linkedList)){
                    foreach ($linkedList as $linkedQuestionnnaire)
                    {
                        print '<li class="'.($idQuestionnaire==$linkedQuestionnnaire->id?'active ':'').'" >';
                        print '<a href="'.dol_buildpath('/agefodd/session/questionnaire.php', 1).'?id='.$agf->id.'&amp;idQuestionnaire='.$linkedQuestionnnaire->id.'" >'.$linkedQuestionnnaire->ref. ' - ' .$linkedQuestionnnaire->title.'</a>';
                        print '</li>';
                    }
                }
                ?>
            </ul>
        </div>
    </div>
    <div class="agf_col-9">
    <?php if(empty($objQuestionnaire)) {

    }
    else{ ?>

        <div class="agf_row agf_title_head" >

            <h3 class="pull-left"><small ><?php print $objQuestionnaire->ref; ?></small> - <?php print $objQuestionnaire->title; ?></h3>

            <?php if(!empty($user->rights->agefodd->questionnaire->send)){ ?>
            <a class="agf_btn agf_btn-app pull-right classfortooltip" href="<?php print dol_buildpath('/agefodd/session/questionnaire.php', 1).'?id='.$agf->id.'&amp;questionnaire='.$linkedQuestionnnaire->id.'&amp;action=send'; ?>" title="<?php print $langs->trans('agfQuestSendShortHelp'); ?>" >
                <i class="fa fa-send"></i>
                <?php print $langs->trans('agfQuestSendShort'); ?>
            </a>
            <?php } ?>

            <?php if(!empty($user->rights->agefodd->questionnaire->link)){ ?>
            <a class="agf_btn agf_btn-app pull-right classfortooltip" href="<?php print dol_buildpath('/agefodd/session/questionnaire.php', 1).'?id='.$agf->id.'&amp;questionnaire='.$linkedQuestionnnaire->id.'&amp;action=unlink'; ?>" title="<?php print $langs->trans('agfQuestUnlinkShortHelp'); ?>" >
                <i class="fa fa-unlink"></i>
                <?php print $langs->trans('agfQuestUnlinkShort'); ?>
            </a>
            <?php } ?>

        </div>

        <div class="agf_list_wrap" >
            <?php printRenderQuestionnaireGuestsList($objQuestionnaire, $agf); ?>
        </div>

    <?php } ?>
    </div>
</div>


<?php

print '</div>';
llxFooter();
$db->close();