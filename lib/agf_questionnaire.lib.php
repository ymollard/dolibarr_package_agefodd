<?php

/**
 * @param $db
 * @param $sessionId
 * @param string $filter 'linked' 'notlinked' 'both'
 */
function getQuestionnaireSessionList($sessionId, $filter='both'){
    global $conf, $db;

    $sql = 'SELECT q.rowid id, q.ref, q.title ';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'quest_questionnaire q ';

    if($filter==='linked'){
        $sql.= ' JOIN '.MAIN_DB_PREFIX.'element_element ee ';
        $sql.= ' ON ( ee.sourcetype = \'questionnaire\'  AND ee.targettype =  \'agefodd_agsession\' AND ee.fk_target = \''.intval($sessionId).'\'  AND `fk_source` = q.rowid )  ';
    }

    $sql.= ' WHERE q.entity = '.intval ($conf->entity);

    if($filter==='notlinked'){
        $sql.= ' AND q.rowid NOT IN ( SELECT el.fk_source FROM '.MAIN_DB_PREFIX.'element_element el WHERE el.targettype = \'agefodd_agsession\' AND el.fk_target = \''.intval($sessionId).'\' AND el.sourcetype = \'questionnaire\' )  ';
    }

    $arrayQuestionnaireList = array();

    $result = $db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        if (!empty($num)) {
            while ($obj = $db->fetch_object($result)) {
                $arrayQuestionnaireList[$obj->id] = $obj;
            }
        }
    }
    else{
        dol_print_error($db);
    }



    return $arrayQuestionnaireList;


}

/**
 * @param $db
 * @param $sessionId
 * @param string $filter 'linked' 'notlinked' 'both'
 */
function getQuestionnaireSessionListForm($sessionId, $filter='both', $htmlname='questionnaire', $htmlid = ''){
    global $conf, $db;

    $arrayQuestionnaireList = getQuestionnaireSessionList($sessionId, $filter);

    $arrayFormList = array();

    if(!empty($arrayQuestionnaireList) and is_array($arrayQuestionnaireList))
    {
        foreach ($arrayQuestionnaireList as $key => $obj){
            $arrayFormList[$obj->id] = $obj->ref. ' - ' .$obj->title;
        }
    }

    $form = new Form($db);
    return $form->selectarray($htmlname, $arrayFormList, $htmlid, 1, 0, 0, '', 0, 0, 0, '', '', 1);
}



function getQuestionnaireGuestsList($object, $return = 'array')
{
    global $db;

    $sql = 'SELECT DISTINCT 
            iu.fk_element as id_element,
            iu.ref, 
            iu.rowid as fk_invitation_user, 
            COALESCE(NULLIF(iu.type_element,""), "External") as type_element, 
            iu.fk_element,
            iu.fk_questionnaire,  
            iu.email, 
            iu.fk_statut as status,
            iu.date_limite_reponse,
            iu.date_validation

			FROM '.MAIN_DB_PREFIX.'quest_invitation_user iu  
			
			WHERE iu.fk_questionnaire = '.$object->id.'
			AND (fk_element > 0 OR email != "")
			';





    if($return == 'sql'){
        return $sql;
    }

    $resql = $db->query($sql);
    $TData = array();
    if (!empty($resql) && $db->num_rows($resql) > 0) {
        while ($res = $db->fetch_object($resql)) {
            $TData[] = $res;
        }
    }

    return $TData;
}

function printRenderQuestionnaireGuestsList(Questionnaire $object, Agsession $session)
{
    global $db, $langs, $hookmanager;

    $url = $_SERVER['PHP_SELF'].'?id='.$session->id.'&idQuestionnaire='.$object->id;

    $formcore = new TFormCore($url, 'form_list_questionnaire', 'POST');

    $r = new Listview($db, 'questionnaire-guests-list');

    $sql = getQuestionnaireGuestsList($object, 'sql');


    $linkToAnswer = '<a class="ajax-pop-in" href="'.dol_buildpath('questionnaire/answer/card.php',1).'?id=@fk_invitation_user@" >@val@</a>';

    $param = array(
        'view_type' => 'list' // default = [list], [raw], [chart]
    ,'limit'=>array(
        'nbLine' => 500
    )
    ,'subQuery' => array()
    ,'link' => array(
            'date_limite_reponse' => $linkToAnswer
        ,'date_validation' => $linkToAnswer
        , 'status' => $linkToAnswer
        , 'email' => $linkToAnswer
        )
    ,'type' => array(
        'date_limite_reponse' => 'date' // [datetime], [hour], [money], [number], [integer]
        ,'date_validation' => 'date'
    )
    ,'search' => array(
        'date_limite_reponse' => array('search_type' => 'calendars', 'allow_is_null' => true)
        ,'date_validation' => array('search_type' => 'calendars', 'allow_is_null' => true)
        ,'status' => array('search_type' => InvitationUser::$TStatus , 'to_translate' => true) // select html, la clé = le status de l'objet, 'to_translate' à true si nécessaire
        ,'email' => array('search_type' => true, 'table' => array('iu', 'iu'), 'field' => array('email'))
    )
    ,'translate' => array()

    ,'list' => array(
        'title' => $langs->trans('QuestionnaireAnswerList')
        ,'image' => 'title_generic.png'
        ,'picto_precedent' => '<'
        ,'picto_suivant' => '>'
        ,'noheader' => 1
        ,'messageNothing' => $langs->trans('Nothing')
        ,'picto_search' => img_picto('','search.png', '', 0)
    )
    ,'title'=>array(

        'date_limite_reponse' => $langs->trans('questionnaire_date_limite_reponse')
    ,'date_validation' => $langs->trans('ValidationDate')
    , 'status' => $langs->trans('Status')
    , 'email' => $langs->trans('Email')
    , 'fk_element' => $langs->trans('Element')
    , 'fk_usergroup' => $langs->trans('Group')
    , 'link_invit' => $langs->trans('LinkInvit')
    )
    ,'eval'=>array(
        'status' => 'InvitationUser::LibStatut(intval("@status@"), 6)'
    , 'fk_element' => 'agfGetNomUrl("@fk_element@","Externe","@type_element@")'
    , 'fk_usergroup' => 'agfGetNomUrlGrp("@fk_usergroup@")'
    , 'link_invit' => 'agfGetLinkUrl("@type_element@","@fk_element@","@fk_questionnaire@","@id_user@","@token@")'
    )
    );

    if(!empty($url)) {
        $param['list']['param_url'] = 'id='.$session->id.'&idQuestionnaire='.$object->id;
    }

    echo $r->render($sql, $param);

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
            
            $dialog.dialog({
                autoOpen: false,
                modal: true,
                height: windowHeight,
                width: windowWidth,
                title: "",
            });
            
            $dialog.load($(this).attr("href") + " #allQuestions").dialog("open");
        });
        
	});
            
            
            
            
    </script>';


}


function agfGetLinkUrl($type_element, $fk_element,$fk_questionnaire,$fk_invit,$token){

    global $conf, $langs;

    if ($type_element == 'user' && $fk_element > 0) {
        $url = dol_buildpath('/questionnaire/card.php?id=' . $fk_questionnaire . '&action=answer&fk_invitation=' . $fk_invit . '&token=' . $token, 2);
    }else if(!empty($conf->global->QUESTIONNAIRE_CUSTOM_DOMAIN)) {
        $url = $conf->global->QUESTIONNAIRE_CUSTOM_DOMAIN . 'toAnswer.php?id=' . $fk_questionnaire . '&action=answer&fk_invitation=' . $fk_invit . '&token=' . $token;
    }

    return  ' <input style="opacity:0;width:1px;" type="text"  value="'.$url.'" class="copyToClipboard"><button type="button" class="button classfortooltip" title="'.$langs->trans('CopyLink').'" onclick="copyLink(this);" ><i class="fa fa-copy"></i></button>';
}


function agfGetUsers()
{

    global $db;

    $sql = 'SELECT rowid, lastname, firstname
			FROM '.MAIN_DB_PREFIX.'user
			WHERE statut = 1';

    $resql = $db->query($sql);
    $TRes = array();
    if (!empty($resql) && $db->num_rows($resql) > 0)
    {
        while ($res = $db->fetch_object($resql))
            $TRes[$res->rowid] = $res->lastname.' '.$res->firstname;
    }

    return $TRes;
}

function agfGetUserGroups()
{

    global $db;

    $sql = 'SELECT rowid, nom
			FROM '.MAIN_DB_PREFIX.'usergroup';

    $resql = $db->query($sql);
    $TRes = array();
    if (!empty($resql) && $db->num_rows($resql) > 0)
    {
        while ($res = $db->fetch_object($resql))
            $TRes[$res->rowid] = $res->nom;
    }

    return $TRes;
}

function agfGetNomUrl($fk_element, $email,$type_element)
{

    global $db;
    $type_element= ucfirst($type_element);
    if($type_element == 'Thirdparty')$type_element='Societe';
    if(class_exists($type_element))$u = new $type_element($db);

    if (!empty($fk_element) && method_exists($u, 'getNomUrl')){
        $u->fetch($fk_element);
        $res = $u->getNomUrl(1);
    }else
        $res = $email;
    return $res;
}

function agfGetNomUrlGrp($fk_usergroup)
{

    global $db;

    $u = new UserGroup($db);
    $u->fetch($fk_usergroup);
    if (!empty($fk_usergroup))
        if(method_exists($u, 'getNomUrl')) $res = $u->getNomUrl();
        else $res = $u->nom;
    else
        $res = 'Non';
    return $res;
}