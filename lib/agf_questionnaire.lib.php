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