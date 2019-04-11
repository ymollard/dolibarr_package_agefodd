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
            iu.sent, 
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
			'; //AND iu.fk_statut IN (1,2)





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

function agfGetLinkAnswersUser($fk_user,$ref)
{

    global $id, $i_rep, $formfile;

    if(empty($formfile)){
        $formfile = new FormFile($db);
    }

    $i_rep++;

    $filename=dol_sanitizeFileName($ref);
    $filedir=DOL_DATA_ROOT.'/questionnaire/' . dol_sanitizeFileName($ref);


    return '<span style="white-space: nowrap;" ><a class="ajax-pop-in"  href="'.dol_buildpath('/questionnaire/answer/card.php',1).'?id='.$fk_user.'">'.$ref.'</a>'.$formfile->getDocumentsLink('questionnaire', $filename, $filedir).'</span>';
}

function agfGetLinkAnswersStatut($status)
{

    global $db, $id, $questionnaire_status_forced_key;

    if ($status == 1)
        $questionnaire_status_forced_key = 'answerValidate';
    else
        $questionnaire_status_forced_key = '';

    // Juste pour utiliser la fonction LibStatus
    $q = new Questionnaire($db);
    $q->fetch($id);

    return $q->LibStatut($status, 6);
}