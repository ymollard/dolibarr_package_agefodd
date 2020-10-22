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


require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_session_formateur.class.php');
require_once ('../class/agefodd_session_stagiaire.class.php');
require_once ('../class/agefodd_stagiaire.class.php');
require_once ('../class/agefodd_session_element.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../lib/agefodd.lib.php');
dol_include_once('/questionnaire/class/invitation.class.php');
dol_include_once('/questionnaire/class/questionnaire.class.php');
dol_include_once('/questionnaire/class/question.class.php');
dol_include_once('/questionnaire/lib/questionnaire.lib.php');
require_once ('../lib/agf_questionnaire.lib.php');




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

$date_limite_year = GETPOST('date_limiteyear', 'none');
$date_limite_month = GETPOST('date_limitemonth', 'none');
$date_limite_day = GETPOST('date_limiteday', 'none');
$massaction = GETPOST('massaction', 'alpha');
$confirmmassaction = GETPOST('confirmmassaction', 'alpha');
$toselect = GETPOST('toselect', 'array');


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
$hookmanager->initHooks(array('questionnaireinvitationcard', 'globalcard', 'agefoddquestionnaireinvitationcard'));

$parameters = array('id' => $id, 'ref' => $ref, 'mode' => $mode);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some
if ($reshook < 0)
    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (GETPOST('cancel', 'alpha'))
{
    $action = 'list';
    $massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend')
{
    $massaction = '';
}

$arrayofselected = is_array($toselect) ? $toselect : array();

if (!empty($massaction) && $massaction == 'send' && !empty($arrayofselected))
{
    $langs->load('mails');
    $invuser = new InvitationUser($db);


    $objQuestionnaire = new Questionnaire($db);
    if($objQuestionnaire->fetch($idQuestionnaire) > 0) {

        foreach ($arrayofselected as $inv_selected) {


            $invuser->load($inv_selected);

            $subject = $langs->transnoentitiesnoconv('MailSubjQuest', $objQuestionnaire->title);

            $content = prepareMailContent($invuser, $idQuestionnaire);
            include_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';

            $mailfile = new CMailFile($subject, $invuser->email, $conf->email_from, $content);
            if (!$mailfile->sendfile()) {
                setEventMessages($langs->transnoentities($langs->trans("ErrorFailedToSendMail", $conf->email_from, $invuser->email) . '. ' . $mailfile->error), null, 'errors');
            } else {
                $invuser->sent = 1;
                $invuser->date_envoi = dol_now();
                $invuser->update($user);
                setEventMessages($langs->trans("MailSuccessfulySent", $conf->email_from, $invuser->email), null, 'mesgs');
            }
        }
    }
}elseif($massaction == 'delete' && !empty($arrayofselected)){

    $objQuestionnaire = new Questionnaire($db);
    if($objQuestionnaire->fetch($idQuestionnaire) > 0) {

        foreach ($arrayofselected as $inv_selected) {
            $invitation = new InvitationUser($db);
            $invitation->load($inv_selected);
            $invitation->delete($user);
            $objQuestionnaire->deleteAllAnswersUser($inv_selected);
        }
    }
}


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
            setEventMessage($langs->trans('agfQuestionnaireLinkRemoveError'), 'errors');
        }

        header('Location: '.dol_buildpath('/agefodd/session/questionnaire.php', 1).'?id='.$agf->id);
        exit;
    }

}
elseif ($action == "addInvitations" && !empty($idQuestionnaire) )
{
    if(!empty($user->rights->agefodd->questionnaire->send))
    {
        $objQuestionnaire = new Questionnaire($db);
        if($objQuestionnaire->fetch($idQuestionnaire) > 0){
            $toselect = GETPOST('toselect', 'array');
            // Enregistrement des données dans les tables invitation et invitation_user
            $date_limite_reponse = strtotime($date_limite_year.'-'.$date_limite_month.'-'.$date_limite_day);
            $addRes = addInvitationsTrainnee($objQuestionnaire, $toselect, $date_limite_reponse, $user, $addLog);

            if($addRes == 1){
                setEventMessage($langs->trans('agfInvitationsAdded'));
            }
            elseif(empty($addRes)){
                setEventMessage($langs->trans('agfInvitationNotingToDo'), 'warnings');
            }
            elseif($addRes < 0 && empty($addLog)){
                setEventMessage($langs->trans('agfInvitationErrors'), 'errors');
            }
            elseif(($addRes == 2 || $addRes < 0) && !empty($addLog)){
                foreach ($addLog as $id => $log)
                {
                    if($log['status'] < 0){
                        setEventMessage($log['msg'], 'errors');
                    }
                    elseif($log['status'] && !empty($log['msg'])){
                        setEventMessage($log['msg']);
                    }
                }
            }
        }
        else{
            setEventMessage($langs->trans('agfQuestionnaireNotFound'), 'errors');
        }

        $action = 'prepareAddInvitations';
    }
    else{
        setEventMessage($langs->trans('agfNotEnoughRight'), 'warnings');
    }
}



/*
* View
*/

llxHeader('', $langs->trans("AgfSessionQuestionaire"), '', '', 0, 0, '', array('agefodd/css/questionnaire.css.php'));

$head = session_prepare_head($agf);

if ($agf->type_session == 1) {
    $styledisplay = ' style="display:none" ';
}

dol_fiche_head($head, 'survey', $langs->trans("AgfSessionDetail"), 0, 'calendarday');

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
    <div class="agf_col-2 agf_left_nav">
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
                        // if no qestionnaire loaded we force first
                        if(empty($objQuestionnaire)){
                            $objQuestionnaire = new Questionnaire($db);
                            $idQuestionnaire=$linkedQuestionnnaire->id;
                            if($objQuestionnaire->fetch($linkedQuestionnnaire->id) < 1){
                                $objQuestionnaire = false;
                                $idQuestionnaire=0;
                            }
                        }

                        print '<li class="'.($idQuestionnaire==$linkedQuestionnnaire->id?'active ':'').'" >';
                        print '<a href="'.dol_buildpath('/agefodd/session/questionnaire.php', 1).'?id='.$agf->id.'&amp;idQuestionnaire='.$linkedQuestionnnaire->id.'" >'.$linkedQuestionnnaire->ref. ' - ' .$linkedQuestionnnaire->title.'</a>';
                        print '</li>';
                    }
                }
                ?>
            </ul>
        </div>
    </div>
    <div class="agf_col-10">
    <?php if(empty($objQuestionnaire)) {
        if(empty($linkedList))
        {
            print '<div class="info" >'.$langs->trans('AgfToStartLinkYourFirstQuestionnaire').'</div>';
        }
    }
    else{ ?>

        <div class="agf_row agf_title_head" >

            <h3 class="pull-left"><small ><?php print $objQuestionnaire->ref; ?></small> - <?php print $objQuestionnaire->title; ?></h3>

            <?php if(!empty($user->rights->questionnaire->read)){ ?>
            <a class="agf_btn agf_btn-app pull-right classfortooltip" href="<?php print dol_buildpath('/questionnaire/card.php', 1).'?id='.$agf->id.'&amp;questionnaire='.$linkedQuestionnnaire->id.'&amp;action=send'; ?>" title="<?php print $langs->trans('agfQuestCardShortHelp'); ?>" >
                <i class="fa fa-file"></i>
                <?php print $langs->trans('Card'); ?>
            </a>
            <?php } ?>

            <?php if(!empty($user->rights->agefodd->questionnaire->send)){ ?>
                <?php if( $action == 'prepareAddInvitations'){ ?>
                    <a class="agf_btn agf_btn-app pull-right classfortooltip" href="<?php print dol_buildpath('/agefodd/session/questionnaire.php', 1).'?id='.$agf->id.'&amp;idQuestionnaire='.$linkedQuestionnnaire->id; ?>" title="<?php print $langs->trans('agfQuestAnswerShortHelp'); ?>" >
                        <i class="fa fa-reply-all"></i>
                        <?php print $langs->trans('agfQuestAnswerShort'); ?>
                    </a>
                <?php }else{ ?>
                    <a class="agf_btn agf_btn-app pull-right classfortooltip" href="<?php print dol_buildpath('/agefodd/session/questionnaire.php', 1).'?id='.$agf->id.'&amp;idQuestionnaire='.$linkedQuestionnnaire->id.'&amp;action=prepareAddInvitations'; ?>" title="<?php print $langs->trans('agfQuestSendShortHelp'); ?>" >
                        <i class="fa fa-plus-circle"></i>
                        <?php print $langs->trans('agfQuestSendShort'); ?>
                    </a>
                <?php } ?>
            <?php } ?>

            <?php if(!empty($user->rights->agefodd->questionnaire->link)){ ?>
            <a class="agf_btn agf_btn-app pull-right classfortooltip" href="<?php print dol_buildpath('/agefodd/session/questionnaire.php', 1).'?id='.$agf->id.'&amp;questionnaire='.$linkedQuestionnnaire->id.'&amp;action=unlink'; ?>" title="<?php print $langs->trans('agfQuestUnlinkShortHelp'); ?>" >
                <i class="fa fa-unlink"></i>
                <?php print $langs->trans('agfQuestUnlinkShort'); ?>
            </a>
            <?php } ?>

        </div>

        <div class="agf_list_wrap" >
            <?php

            if ($massaction == "addInvitations" && !empty($confirmmassaction) && !empty($idQuestionnaire) )
            {
                if(!empty($user->rights->agefodd->questionnaire->send))
                {
                    $form = new Form($db);
                    $url = $_SERVER['PHP_SELF'].'?id='.$agf->id.'&idQuestionnaire='.$objQuestionnaire->id;

                    $toselect = GETPOST('toselect', 'array');
                    $formToSelectFields = '';
                    if(!empty($toselect))
                    {
                        foreach ($toselect as $sessioncontactId)
                        {
                            $formToSelectFields .= '<input type="hidden" name="toselect[]" value="'.$sessioncontactId.'" />';
                        }
                    }

                    $formSelectDate =  $form->select_date(dol_now()+ (60*60*24*3 ), 'date_limite', 0, 0, 0, '', 1, 0, 1);

                    print '<div id="addInvitations-dialog" style="display: none" >';
                    print '<form action="'.$url.'" method="POST" >';
                    print '<input type="hidden" name="action" value="addInvitations" />';
                    print $formSelectDate;
                    print $formToSelectFields;
                    print '</form></div>';
                    print '<script>
                      $( function() {
                       $( "#addInvitations-dialog" ).dialog({
                            autoOpen: true,
                            modal: true,
                            title: "'.$langs->transnoentities('questionnaire_date_limite_reponse').'",
                            buttons:{
                                    "createInvite" : {
                                        text: "'.$langs->trans('Confirm').'",
                                        click: function() {
                                            $( "#addInvitations-dialog form" ).submit();
                                        }
                                    },
                                    "cancel" : {
                                        text: "'.$langs->trans('Cancel').'",
                                        click: function() {
                                            $( this ).dialog( "close" );
                                        }
                                    }
                                },
                        });
                      } );
                      </script>';
                }
                else{
                    setEventMessage($langs->trans('agfNotEnoughRight'), 'warnings');
                }
            }

            if($action=='prepareAddInvitations'){
                _printRenderQuestionnaireParticipantsList($objQuestionnaire, $agf);
            }
            else{
                _printRenderQuestionnaireGuestsList($objQuestionnaire, $agf);
            }


            ?>
        </div>

    <?php } ?>
    </div>
</div>


<?php

print '</div>';
llxFooter();
$db->close();




function _printRenderQuestionnaireParticipantsList(Questionnaire $object, Agsession $session)
{
    global $db, $langs, $hookmanager;

    $url = $_SERVER['PHP_SELF'].'?id='.$session->id.'&idQuestionnaire='.$object->id.'&amp;action=prepareAddInvitations';

    $formcore = new TFormCore($url, 'form_list_questionnaire_stagiaire', 'POST');

    $r = new Listview($db, 'questionnaire-session_trainee-list');



    $sql = "SELECT";
    $sql .= " s.fk_stagiaire rowid, s.fk_stagiaire,  s.status_in_session, iu.rowid invitation_id, stag.nom, stag.prenom, stag.civilite, stag.fk_soc, stag.mail";
    $sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire s";
    $sql .= " JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire stag ON (stag.rowid = s.fk_stagiaire ) ";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "quest_invitation_user iu ON (iu.fk_element = s.rowid AND iu.type_element = 'agefodd_stagiaire' ) ";
    $sql .= " WHERE s.fk_session_agefodd = " . $session->id .' AND iu.rowid IS NULL ';

    $trainneeCardUrl = dol_buildpath('agefodd/trainee/card.php',1).'?id=@fk_stagiaire@';
    $trainneeCardLink = '<a href="'.$trainneeCardUrl.'" >@val@</a>';

    $param = array(
            'view_type' => 'list'
    ,'limit'=>array('nbLine' => 500)
    ,'subQuery' => array()
    ,'link' => array(
            'rowid' =>  $trainneeCardLink
            ,'prenom' =>  $trainneeCardLink
            ,'nom' =>  $trainneeCardLink
            ,'mail' =>  $trainneeCardLink
        )
    ,'type' => array()
    ,'search' => array()
    ,'translate' => array()
    ,'list' => array(
            'title' => $langs->trans('AgfSessionParticipantsListTitle'),
            'massactions'=>array(
                    'addInvitations'  => $langs->trans('AgfQuestionnaireInvite')
            )
        )
    ,'hide'=> array('rowid')
    ,'title'=>array(
            'rowid' => $langs->trans('Id')
         ,'prenom' => $langs->trans('Firstname')
        ,'nom' => $langs->trans('Name')
        ,'mail' => $langs->trans('Email')
            ,'invitation_id'  => $langs->trans('Invited')
            ,'selectedfields' => ''
        )
    ,'eval'=>array(
        'invitation_id'  => '_is_invited("@val@")'
        )
    );

    if(!empty($url)) {
        $param['list']['param_url'] = 'id='.$session->id.'&idQuestionnaire='.$object->id;
    }

    echo $r->render($sql, $param);
}

function _is_invited($id = 0){
    global $langs;

    return (!empty($id)?$langs->trans('Yes'):$langs->trans('No'));
}


function _printRenderQuestionnaireGuestsList(Questionnaire $object, Agsession $session)
{
    global $db, $langs, $hookmanager;

    $url = $_SERVER['PHP_SELF'].'?id='.$session->id.'&idQuestionnaire='.$object->id;

    $formcore = new TFormCore($url, 'form_list_questionnaire', 'POST');

    $r = new Listview($db, 'questionnaire-guests-list');

    $sql = getQuestionnaireGuestsList($object, 'sql');


    $linkToAnswer = '<a class="ajax-pop-in" href="'.dol_buildpath('questionnaire/answer/card.php',1).'?id=@fk_invitation_user@" >@val@</a>';

    $TStatus = InvitationUser::$TStatus;
    //unset($TStatus[InvitationUser::STATUS_DRAFT]);

    $listViewConfig = array(
        'view_type' => 'list' // default = [list], [raw], [chart]
    ,'limit'=>array('nbLine' => 500)
    ,'subQuery' => array()
    ,'link' => array()
    ,'type' => array(
            'date_limite_reponse' => 'date' // [datetime], [hour], [money], [number], [integer]
        ,'date_validation' => 'date'
        )
    ,'search' => array(
            'date_limite_reponse' => array('search_type' => 'calendars', 'allow_is_null' => true)
        ,'date_validation' => array('search_type' => 'calendars', 'allow_is_null' => true)
        ,'status' => array('search_type' => $TStatus , 'to_translate' => true) // selec
        ,'sent' => array('search_type' => InvitationUser::$TSentStatus , 'to_translate' => true) // select html, la clé = le status de l'objet, 'to_translate' à true si nécessaire
        ,'email' => array('search_type' => true, 'table' => array('iu', 'iu'), 'field' => array('email'))
        ,'ref' => array('search_type' => true, 'table' => array('iu', 'iu'), 'field' => array('ref'))
        )
    ,'translate' => array()

    ,'list' => array(
            'title' => $langs->trans('QuestionnaireAnswerList')
        //,'image' => 'title_generic.png'
        ,'picto_precedent' => '<'
        ,'picto_suivant' => '>'
        ,'noheader' => 1
        ,'messageNothing' => $langs->trans('Nothing')
        ,'picto_search' => img_picto('','search.png', '', 0)
        ,'massactions'=>array(
                'send' => $langs->trans("SendByMail"),
                'delete'=>$langs->trans("Delete"),
            )
        )
    ,'hide'=> array('rowid')
    ,'title'=>array(
            'ref' => $langs->trans('Ref')
        ,'date_limite_reponse' => $langs->trans('questionnaire_date_limite_reponse')
        ,'date_validation' => $langs->trans('ValidationDate')
        , 'sent' => $langs->trans('Sent')
        , 'status' => $langs->trans('Status')

        , 'email' => $langs->trans('Email')
        , 'fk_element' => $langs->trans('Element')
        , 'fk_usergroup' => $langs->trans('Group')
        , 'link_invit' => $langs->trans('LinkInvit')
        ,'selectedfields' => ''
        )
    ,'eval'=>array(
            'sent' => 'InvitationUser::LibStatut(intval("@sent@"), 6)'
        ,'status' => 'agfGetLinkAnswersStatut("@status@")'
        , 'fk_element' => 'agfGetNomUrl("@fk_element@","Externe","@type_element@")'
        , 'fk_usergroup' => 'agfGetNomUrlGrp("@fk_usergroup@")'
        , 'link_invit' => 'agfGetLinkUrl("@type_element@","@fk_element@","@fk_questionnaire@","@id_user@","@token@")'
        , 'ref' => 'agfGetLinkAnswersUser("@fk_invitation_user@","@ref@")'
        )
    );

    if(!empty($url)) {
        $listViewConfig['list']['param_url'] = 'id='.$session->id.'&idQuestionnaire='.$object->id;
    }

    // Change view from hooks
    $parameters=array(  'listViewConfig' => $listViewConfig);

    $reshook=$hookmanager->executeHooks('listViewConfig',$parameters,$r);    // Note that $action and $object may have been modified by hook
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
    if ($reshook>0)
    {
        $listViewConfig = $hookmanager->resArray;
    }


    echo $r->render($sql, $listViewConfig);

    print '<div id="jquery-questionnaire-dialog-box" ></div>';


    $parameters=array('sql'=>$sql);
    $reshook=$hookmanager->executeHooks('printFieldListFooter', $parameters, $object);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    $formcore->end_form();

    print '<script type="text/javascript">
    function copyLink(e){
        /* Get the text field */
       var copyText = e.closest("tr").getElementsByClassName("copyToClipboard");
        /* Select the text field */
        copyText[0].select();
        /* Copy the text inside the text field */
        document.execCommand("copy");
    }


    $( document ).ready(function() {

        var popinId = "jquery-questionnaire-dialog-box";

	    var windowWidth = $(window).width()*0.7; //retrieve current window width
	    var windowHeight = $(window).height()*0.7; //retrieve current window height


        $(".ajax-pop-in").click(function (e) {

            e.preventDefault();

            $dialog = $( "#" + popinId );
            var dialogUrl = $(this).attr("href");

            $dialog.dialog({
                autoOpen: false,
                modal: true,
                height: windowHeight,
                width: windowWidth,
                title: "",
                buttons:{

                        \'openquestionnaire\' : {
                            text: "'.$langs->trans('Card').'",
                            "class": "cancelButtonClass",
                            click: function() {
                              	window.location = dialogUrl;
                            }
                        }
                    },
            });

            $dialog.load($(this).attr("href") + " #allQuestions").dialog("open");
        });

	});




    </script>';


}
