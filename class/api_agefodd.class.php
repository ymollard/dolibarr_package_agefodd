<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
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

 use Luracast\Restler\RestException;

 require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
 require_once (DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php');

 dol_include_once('/agefodd/class/agsession.class.php');
 dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
 dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');
 dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
 dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
 dol_include_once('/agefodd/class/agefodd_session_formateur_calendrier.class.php');
 dol_include_once('/agefodd/class/agefodd_session_element.class.php');
 dol_include_once('/agefodd/class/agefodd_convention.class.php');
 dol_include_once('/agefodd/class/agefodd_cursus.class.php');
 dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
 dol_include_once('/agefodd/class/agefodd_stagiaire_cursus.class.php');
 dol_include_once('/agefodd/class/agefodd_stagiaire_certif.class.php');
 dol_include_once('/agefodd/class/agefodd_formateur.class.php');
 dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
 dol_include_once('/agefodd/class/agefodd_formation_catalogue_modules.class.php');
 dol_include_once('/agefodd/class/agefodd_formation_cursus.class.php');
 dol_include_once('/agefodd/class/agefodd_training_admlevel.class.php');
 dol_include_once('/agefodd/class/agefodd_place.class.php');
 dol_include_once('/agefodd/class/agefodd_reginterieur.class.php');
 dol_include_once('/agefodd/class/agefodd_opca.class.php');

 dol_include_once('agefodd/lib/agefodd.lib.php');

/**
 * API class for Agefodd
 *
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Agefodd extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
//         exemple

//         'objecttypes' => array(
//             'mandatoryFields' => array('id', 'entity')
//             ,'fieldTypes' => array(
//                 'id' => 'int'
//                 ,'entity' => 'int'
//                 ,'label' => 'string'
//                 ,'price' => 'float'
//             )
//          )

        // validate sessions
        'session' => array(
            'mandatoryFields' => array('fk_formation_catalogue', 'fk_session_place')
            ,'fieldTypes' => array()
        )

        // validate trainee
        ,'trainee' => array(
            'mandatoryFields' => array('nom', 'prenom', 'civilite', 'socid')
            ,'fieldTypes' => array()
        )

        // validate traineeinsession
        ,'traineeinsession' => array(
            'mandatoryFields' => array('fk_session_agefodd', 'fk_stagiaire')
            ,'fieldTypes' => array(
                'fk_session_agefodd' => 'int'
                ,'fk_stagiaire' => 'int'
            )
        )

        // validate trainerinsession
        ,'trainerinsession' => array(
            'mandatoryFields' => array('fk_session', 'fk_agefodd_formateur')
            ,'fieldTypes' => array(
                'fk_session' => 'int'
                ,'fk_agefodd_formateur' => 'int'
            )
        )

        // validate trainer
        ,'trainer' => array(
            'mandatoryFields' => array('id')
            ,'fieldTypes' => array(
                'id' => 'int'
            )
        )

        // validate training
        ,'training' => array(
            'mandatoryFields' => array('intitule', 'duree')
            ,'fieldTypes' => array(
                'intitule' => 'string'
                ,'duree' => 'float'
            )
        )

        ,'objpeda' => array(
            'mandatoryFields' => array('fk_formation_catalogue', 'intitule')
            ,'fieldTypes' => array(
                'intitule' => 'string'
                ,'fk_formation_catalogue' => 'int'
            )
        )

        ,'place' => array(
            'mandatoryFields' => array('ref_interne', 'fk_societe')
            ,'fieldTypes' => array(
                'ref_interne' => 'string'
                ,'fk_societe' => 'int'
            )
        )
    );


    /**
     * @var Agsession $session {@type Session}
     */
    public $session;

    /**
     * Constructor
     *
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
		$this->session = new Agsession($this->db);                                            // agefodd session
		$this->sessioncalendar = new Agefodd_sesscalendar($this->db);                         // agefodd sessioncalendar
		$this->cursus = new Agefodd_cursus($this->db);                                        // agefodd cursus
		$this->trainee = new Agefodd_stagiaire($this->db);                                    // agefodd trainee
		$this->traineeinsession = new Agefodd_session_stagiaire($this->db);                   // traineeinsession
		$this->traineehours = new Agefoddsessionstagiaireheures($this->db);                   // hours spent by trainees in the sessions
		$this->trainer = new Agefodd_teacher($this->db);                                      // agefodd teacher
		$this->trainerinsession = new Agefodd_session_formateur($this->db);                   // trainerinsession
		$this->trainerinsessioncalendar = new Agefoddsessionformateurcalendrier($this->db);   // calendar of a trainer in a session
		$this->training = new Formation($this->db);                                           // agefodd training
		$this->trainingmodules = new Agefoddformationcataloguemodules($this->db);             // agefodd trainingmodule
		$this->place = new Agefodd_place($this->db);                                          // agefodd place
		$this->opca = new Agefodd_opca($this->db);                                            // agefodd OPCA
		$this->sessionlinks = new Agefodd_session_element($this->db);                         // elements linked to a session
		$this->certif = new Agefodd_stagiaire_certif($this->db);                              // certificates
		$this->convention = new Agefodd_convention($this->db);                                // agefodd convention
    }


    /***************************************************************** Session Part *****************************************************************/

    /**
     * List Sessions
     *
     * Get a list of Agefodd Sessions
     *
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @return array                Array of session objects
     *
     * @url     GET /sessions/
     * @throws RestException
     */
    function sessionIndex($sortfield = "s.rowid", $sortorder = 'DESC', $limit = 100, $page = 0) {
        global $db, $conf;

        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $obj_ret = array();

        $offset = 0;
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

        }

        $result = $this->session->fetch_all($sortorder, $sortfield, $limit, $offset);

        if ($result > 0)
        {
            foreach ($this->session->lines as $line){
                $obj_ret[] = $this->_cleanObjectDatas($line);
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve session list '.$sql);
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No session found');
        }
        return $obj_ret;
    }

    /**
     * Filtered List of Sessions
     *
     * Get a list of Agefodd Sessions
     *
     * @param string	$sortfield	        Sort field
     * @param string	$sortorder	        Sort order
     * @param int		$limit		        Limit for list
     * @param int		$page		        Page number
     * @param array		$filter		        array of filters ($k => $v)
     * @param int       $user               id of the sale User
     * @param array     $array_options_keys array of filters on extrafields
     *
     * @return array                Array of session objects
     *
     * @url     POST /sessions/filter
     * @throws RestException
     */
    function sessionFilteredIndex($sortfield = "s.rowid", $sortorder = 'DESC', $limit = 100, $page = 0, $filter=array(), $user = 0, $array_options_keys=array()) {
        global $db, $conf;

        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $obj_ret = array();

        $offset = 0;
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

        }

        if (!empty($user)) {
            $u = new User($this->db);
            $result = $u->fetch($user);
            if($result <= 0) throw new RestException(404, "User $user not found");
        } else $u = 0;

        $result = $this->session->fetch_all($sortorder, $sortfield, $limit, $offset, $filter, $u, $array_options_keys=array());

        if ($result > 0)
        {
            foreach ($this->session->lines as $line){
                $obj_ret[] = $this->_cleanObjectDatas($line);
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve session list '.$sql);
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No session found');
        }
        return $obj_ret;
    }

    /**
     * Filtered List of Sessions with admin tasks
     *
     * Get a list of Agefodd Sessions
     *
     * @param string	$sortfield	        Sort field
     * @param string	$sortorder	        Sort order
     * @param int		$limit		        Limit for list
     * @param int		$page		        Page number
     * @param int       $user               id of the sale User
     * @param array		$filter		        array of filters ($k => $v)
     *
     *
     * @return array                Array of session objects
     *
     * @url     POST /sessions/withtasks
     * @throws RestException
     */
    function sessionFilteredIndexWithTasks($sortfield = "s.rowid", $sortorder = 'DESC', $limit = 100, $page = 0, $user = 0, $filter= array()) {
        global $db, $conf;

        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $obj_ret = array();

        $offset = 0;
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

        }

        if (!empty($user)) {
            $u = new User($this->db);
            $result = $u->fetch($user);
            if($result <= 0) throw new RestException(404, "User $user not found");
        } else $u = 0;

        $result = $this->session->fetch_all_with_task_state($sortorder, $sortfield, $limit, $offset, $filter, $u);

        if ($result > 0)
        {
            foreach ($this->session->lines as $line){
                $line->TasksLate = $line->task0; unset($line->task0);
                $line->TasksHot = $line->task1; unset($line->task1);
                $line->TasksTodo = $line->task2; unset($line->task2);
                $line->TasksInProgress = $line->task3; unset($line->task3);
                $obj_ret[] = $this->_cleanObjectDatas($line);
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve session list | '.$this->session->error);
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No session found');
        }
        return $obj_ret;
    }

    /**
     * Filtered List of Sessions inter-societies
     *
     * Get a list of Agefodd Sessions
     *
     * @param string	$sortfield	        Sort field
     * @param string	$sortorder	        Sort order
     * @param int		$limit		        Limit for list
     * @param int		$page		        Page number
     * @param int       $user               id of the sale User
     * @param array		$filter		        array of filters ($k => $v)
     *
     * @return array                Array of session objects
     *
     * @url     POST /sessions/inter
     * @throws RestException
     */
    function sessionInterFilteredIndex($sortfield = "s.rowid", $sortorder = 'DESC', $limit = 100, $page = 0, $user = 0, $filter= array()) {
        global $db, $conf;

        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $obj_ret = array();

        $offset = 0;
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

        }

        if (!empty($user)) {
            $u = new User($this->db);
            $result = $u->fetch($user);
            if($result <= 0) throw new RestException(404, "User $user not found");
        } else $u = 0;

        $result = $this->session->fetch_all_inter($sortorder, $sortfield, $limit, $offset, $filter, $u);

        if ($result > 0)
        {
            foreach ($this->session->lines as $line){
                $obj_ret[] = $this->_cleanObjectDatas($line);
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve session list | '.$this->session->error);
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No session found');
        }
        return $obj_ret;
    }

    /**
     * Get all sessions linked to a document
     *
     * Return an array of session linked to the document provided
     *
     * @param string    $documentType (order, propal, invoice, supplier_invoice, supplier_order)
     * @param int       $documentId
     * @param string    $sortorder order
     * @param string    $sortfield field
     * @param int       $limit
     * @param int       $offset
     *
     * @return array    Array of session objects
     *
     * @url    GET /sessions/linked
     * @throws RestException
     */
    function sessionLinkedbyDoc($documentType, $documentId, $sortorder = 'DESC', $sortfield = 's.rowid', $limit = 100, $offset = 0)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $orderid = $invoiceid = $propalid = $fourninvoiceid = $fournorderid = '';
        $Ttypes = array('order', 'propal', 'invoice', 'supplier_invoice', 'supplier_order');

        if (! in_array($documentType, $Ttypes)) throw new RestException(500, "Type '$documentType' not supported");
        if (empty($documentId) || $documentId < 0) throw new RestException(500, "Invalid id : $documentId");

        switch ($documentType)
        {
            case 'order' :
                $orderid = $documentId;
                break;

            case 'propal' :
                $propalid = $documentId;
                break;

            case 'invoice' :
                $invoiceid = $documentId;
                break;

            case 'supplier_invoice' :
                $fourninvoiceid = $documentId;
                break;

            case 'supplier_order' :
                $fournorderid = $documentId;
                break;
        }

        $this->session = new Agsession($this->db);
        $obj_ret = array();

        $result = $this->session->fetch_all_by_order_invoice_propal($sortorder, $sortfield, $limit, $offset, $orderid, $invoiceid, $propalid, $fourninvoiceid, $fournorderid);
        if (empty($result)) throw new RestException(404, "No session found");
        elseif ($result < 0) throw new RestException(503, 'Error when retrieve session list | '.$this->session->error);

        foreach ($this->session->lines as $line){
            $obj_ret[] = $this->_cleanObjectDatas($line);
        }

        return $obj_ret;
    }

    /**
     * Get properties of a session object
     *
     * Return an array with session informations
     *
     * @param 	int 	$id ID of session
     * @return 	array|mixed data without useless information
	 *
	 * @url	GET /sessions/{id}
     * @throws 	RestException
     */
    function getSession($id)
    {
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);

        $result = $this->session->fetch($id);
        if( $result < 0 || empty($this->session->id)) {
            throw new RestException(404, 'session not found');
        }

        return $this->_cleanObjectDatas($this->session);
    }

    /**
     * Get Thirdparties of a session
     *
     * Return an array with thirdparties
     *
     * @param   int     $id
     * @return  array   data without useless information
     *
     * @url GET /sessions/{id}/thirdparties/
     * @throw RestException
     *
     */
    function getSessionThirdparties($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);

        $result = $this->session->fetch($id);
        if( $result < 0 || empty($this->session->id)) {
            throw new RestException(404, 'session not found');
        }

        $result = $this->session->fetch_societe_per_session($id);
        if( $result <= 0) throw new RestException(404, 'No thirdparty found');

        $obj_ret = array();

        foreach ($this->session->lines as $line){
            $obj_ret[] = $this->_cleanObjectDatas($line);
        }

        return $obj_ret;
    }

    /**
     * Get informations for a session object
     *
     * Return an array with informations for the session
     *
     * @param 	int 	$id ID of session
     * @return 	array|mixed data without useless information
     *
     * @url	GET /sessions/{id}/infos/
     * @throws 	RestException
     */
    function getSessionInfos($id)
    {
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);

        $result = $this->session->fetch($id);
        if($result>0) $this->session->info($id);
        if( $result < 0 || empty($this->session->id)) {
            throw new RestException(404, 'session not found');
        }

        return $this->_cleanObjectDatas($this->session);
    }

    /**
     * Get sessions in the same place for the same dates
     *
     * Return an array with informations for the session
     *
     * @param 	int 	$id ID of session
     * @return 	array|mixed data without useless information
     *
     * @url	GET /sessions/{id}/sameplacedate/
     * @throws 	RestException
     */
    function getSessionsSamePlaceDate($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);

        $result = $this->session->fetch($id);
        if( $result < 0 || empty($this->session->id)) {
            throw new RestException(404, 'session not found');
        }

        $result = $this->session->fetchOtherSessionSameplacedate();
        if($result < 0) throw new RestException(500, "Error while retrieving sessions", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(!count($this->lines_place)) throw new RestException(404, "No session found for this place at the same date");

        $obj_ret = array();
        $sessAdded = array();
        foreach ($this->lines_place as $sessid)
        {
            if(!in_array($sessid, $sessAdded))
            {
                $sessAdded[] = $sessid;
                $agf = new Agsession($this->db);
                $agf->fetch($sessid);
                $obj_ret[] = $this->_cleanObjectDatas($agf);
            }
        }

        return $obj_ret;
    }

    /**
     * Create session object
     *
     * @url     POST /sessions/
     *
     *
     * @param string    $mode           create, clone or createadm (create admin tasks)
     * @param array     $request_data   Request data
     *
     * @return 	array|mixed data without useless information
     */
    function postSession($mode = 'create', $request_data)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Creation not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($mode == "createadm"){ // creation des taches administratives de la session pass��e en param

		    if (in_array('id', array_keys($request_data['request_data']))){
		        $this->session->fetch((int)$request_data['request_data']['id']);
		        $result = $this->session->createAdmLevelForSession(DolibarrApiAccess::$user);
		        return empty($result) ? $this->getSession($this->session->id) : $result .' '. $this->session->error;
		    } else throw new RestException(404, 'session not found');

		} elseif ($mode == "clone"){ // clone de la session pass��e en param

		    if (in_array('id', array_keys($request_data['request_data']))){
		        return $this->_cloneSession((int)$request_data['request_data']['id']);
		    } else throw new RestException(404, 'session not found');

		} else { //creation d'une session
		    // Check mandatory fields
		    $result = $this->_validate($request_data['request_data'], 'session');

		    foreach($request_data['request_data'] as $field => $value) {
                $this->session->$field = $value;
            }

            if ($this->session->create(DolibarrApiAccess::$user) < 0) {
                throw new RestException(500, 'Error when creating session', array_merge(array($this->session->error), $this->session->errors));
            }
            return $this->getSession($this->session->id);
		}

    }

    /**
     * Clone a session object
     *
     * @param int $id ID of the session to clone
     * @return int  ID of the clone
     */
    function _cloneSession($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Creation not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->session->fetch($id);
        if( $result < 0 || empty($this->session->id) ) {
            throw new RestException(404, 'session not found');
        }

        $cloneid = $this->session->createFromClone($id);
        if ($cloneid < 0) {
            throw new RestException(500, 'Error when cloning session', array_merge(array($this->session->error), $this->session->errors));
        }
        return $this->getSession($cloneid);
    }

    /**
     * Create a proposal for a thirdparty of the session
     *
     * Return array
     *
     * @param int $sessid   ID of the session
     * @param int $socid    ID of the customer
     *
     * @return array|mixed
     * @throw RestException
     * require_once 'agefodd_session_calendrier.class.php';
     * @url POST sessions/createproposal/
     */
    function sessionCreateProposal($sessid, $socid)
   {
       global $conf;

       if(empty($conf->propal->enabled)) throw new RestException(503, "Module propal must be enabled");
       if(! DolibarrApiAccess::$user->rights->propal->creer) {
           throw new RestException(401, 'Propal creation not allowed for login '.DolibarrApiAccess::$user->login);
       }

       if (empty($sessid) || $sessid < 0) throw new RestException(503, "Invalid sessid");
       if (empty($socid) || $socid < 0) throw new RestException(503, "Invalid socid");

       $this->session = new Agsession($this->db);
       $result = $this->session->fetch($sessid);
       if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'session not found');
       if( empty($this->session->fk_product)) throw new RestException(503, "No product linked to the session.");

       $result = $this->session->fetch_societe_per_session($sessid);
       if( $result <= 0 ) throw new RestException(404, 'No thirdparty found');

       $TCustomers = array();
       foreach ($this->session->lines as $line)
       {
           $TCustomers[] = $line->socid;
       }

       if(count($TCustomers) == 0) throw  new RestException(404, "No thirdparty for this session");
       if(!in_array($socid, $TCustomers)) throw new RestException(404, "$socid is not a thirdparty of this session");

       $result = $this->session->createProposal(DolibarrApiAccess::$user, $socid);
       if($result < 0) throw new RestException(500, 'Error when creating the proposal', array_merge(array($this->session->error, $this->db->lastqueryerror), $this->session->errors));

       return array(
           'success' => array(
               'code' => 200,
               'message' => "Proposal ID $result created for the socid"
           )
       );
    }

    /**
     * Create an order for a thirdparty of the session
     *
     * Return array
     *
     * @param int $sessid           ID of the session
     * @param int $socid            ID of the customer
     * @param int $frompropalid     ID of an existing and signed proposal for the thirdparty
     *
     * @return array|mixed
     * @throw RestException
     *
     * @url POST sessions/createorder/
     */
    function sessionCreateOrder($sessid, $socid, $frompropalid = 0)
    {
        global $conf, $user;
        $user = DolibarrApiAccess::$user;

        if(empty($conf->commande->enabled)) throw new RestException(503, "Module commande must be enabled");
        if(! DolibarrApiAccess::$user->rights->commande->creer) {
            throw new RestException(401, 'Order creation not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (empty($sessid) || $sessid < 0) throw new RestException(503, "Invalid sessid");
        if (empty($socid) || $socid < 0) throw new RestException(503, "Invalid socid");

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'session not found');
        if( empty($this->session->fk_product)) throw new RestException(503, "No product linked to the session.");

        $result = $this->session->fetch_societe_per_session($sessid);
        if( $result <= 0 ) throw new RestException(404, 'No thirdparty found');

        $TCustomers = array();
        foreach ($this->session->lines as $line)
        {
            /*if ($line->typeline == "customer")*/ $TCustomers[] = $line->socid;
        }

        if(count($TCustomers) == 0) throw  new RestException(404, "No thirdparty for this session");
        if(!in_array($socid, $TCustomers)) throw new RestException(404, "$socid is not a thirdparty of this session");

        $result = $this->session->createOrder(DolibarrApiAccess::$user, $socid, $frompropalid);
        if($result < 0) throw new RestException(500, 'Error when creating the order', array_merge(array($this->session->error, $this->db->lastqueryerror), $this->session->errors));

        return array(
            'success' => array(
                'code' => 200,
                'message' => "Proposal ID $result created for the socid"
            )
        );
    }

    /**
     * Create an invoice for a thirdparty of the session
     *
     * Return array
     *
     * @param int       $sessid           ID of the session
     * @param int       $socid            ID of the customer
     * @param int       $frompropalid     ID of an existing and signed proposal for the thirdparty
     * @param number    $amount           Amount to affect to session product
     *
     * @return array|mixed
     * @throw RestException
     *
     * @url POST sessions/createinvoice/
     */
    function sessionCreateInvoice($sessid, $socid, $frompropalid = 0, $amount = 0)
    {
        global $conf, $user;
        $user = DolibarrApiAccess::$user;

        if(empty($conf->facture->enabled)) throw new RestException(503, "Module invoice must be enabled");
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(401, 'Invoice creation not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (empty($sessid) || $sessid < 0) throw new RestException(503, "Invalid sessid");
        if (empty($socid) || $socid < 0) throw new RestException(503, "Invalid socid");

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'session not found');
        if( empty($this->session->fk_product)) throw new RestException(503, "No product linked to the session.");

        $result = $this->session->fetch_societe_per_session($sessid);
        if( $result <= 0 ) throw new RestException(404, 'No thirdparty found');

        $TCustomers = array();
        foreach ($this->session->lines as $line)
        {
            /*if ($line->typeline == "customer")*/ $TCustomers[] = $line->socid;
        }

        if(count($TCustomers) == 0) throw  new RestException(404, "No thirdparty for this session");
        if(!in_array($socid, $TCustomers)) throw new RestException(404, "$socid is not a thirdparty of this session");

        $result = $this->session->createInvoice($user, $socid, $frompropalid, $amount);
        if($result < 0) throw new RestException(500, 'Error when creating the order', array_merge(array($this->session->error, $this->db->lastqueryerror), $this->session->errors));

        return array(
            'success' => array(
                'code' => 200,
                'message' => "Invoice ID $result created for the socid"
            )
        );
    }

    /**
     * Update session
     *
     * @url     PUT /sessions/{id}
     *
     * @param int   $id             Id of session to update
     * @param array $request_data   Datas
     * @return 	array|mixed data without useless information
     */
    function putSession($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);

        $result = $this->session->fetch($id);
        if( $result < 0 || empty($this->session->id) ) {
            throw new RestException(404, 'session not found');
        }

        foreach($request_data as $field => $value) {
            if ($field !== "array_options") {
                if($field !== 'id' && $field !== 'rowid') $this->session->$field = $value;
            } else {
                foreach ($value as $option => $val) $this->session->array_options[$option] = $val;
            }
        }

        if($this->session->update(DolibarrApiAccess::$user) < 0) throw new RestException(500, "Error while updating session", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getSession($id);
    }


    /**
     * Archive a session
     *
     * @url     PUT /sessions/archive
     *
     * @param 	int 	$id ID of place
     * @return 	array|mixed data without useless information
     *
     */
    function archiveSession($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->session->fetch($id);
        if( $result < 0 || empty($this->session->id) ) {
            throw new RestException(404, 'session not found');
        }

        if((int)$this->session->status !== 4) $this->session->status = 4;
        else $this->session->status = 1;

        if($this->session->update(DolibarrApiAccess::$user))
            return $this->getSession($id);

            return false;
    }

    /**
     * Set archive flag to 1 to session according to selected year
     *
     * @url     PUT /sessions/archiveYear/{year}
     *
     * @param int $year year
     *
     * @return array
     */
    function archiveYearSession($year)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->session->updateArchiveByYear($year, DolibarrApiAccess::$user);
        if ($result < 0) throw new RestException(500, 'Error when cloning session', array_merge(array($this->session->error), $this->session->errors));
        return array(
            'success' => array(
                'code' => 200,
                'message' => "sessions archived for year $year"
            )
        );
    }

    /**
     * Set Sale user for a session
     *
     * @url     PUT /sessions/sale/
     *
     * @param int    $id        ID of the session
     * @param int    $userId    ID of the sale (delete link to a sale if empty)
     *
     * @return 	array|mixed data without useless information
     * @throw RestException
     */
    function setSessionCommercial($id, $userId = 0)
    {

        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->session->fetch($id);
        if( $result < 0 || empty($this->session->id) ) {
            throw new RestException(404, 'session not found');
        }

        if (!empty($userId))
        {
            $u = new User($this->db);
            $result = $u->fetch($userId);
            if(empty($result)) throw new RestException(404, "User $userId not found.");
            elseif ($result<0) throw new RestException(500, "Error while retrieving user $userId");
        }

        $result = $this->session->setCommercialSession($userId, DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error while setting sale");

        return $this->getSession($this->session->id);
    }

    /**
     * Delete session
     *
     * @url	DELETE /sessions/{id}
     *
     * @param int $id   Session ID
     * @return array
     */
    function deleteSession($id)
    {
        //if( ! DolibarrApi::_checkAccessToResource('agefodd_session',$this->session->id)) {
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->session->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'session not found');
        }

        if($this->session->remove($id) < 0)
        {
            throw new RestException(500, 'error while deleting session '.$id);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'session deleted'
            )
        );
    }

    /**
     * Return the list of documents attached to a session
     *
     * @param	int		$id				ID of element
     * @param	string	$sortfield		Sort criteria ('','fullname','relativename','name','date','size')
     * @param	string	$sortorder		Sort order ('asc' or 'desc')
     * @return	array					Array of documents with path
     *
     * @url GET /sessions/attachment
     */
    function getSessionAttachments($id, $sortfield='name', $sortorder='asc')
    {
        return $this->_getDocumentsListByElement("agefodd", $id, '', 'session', $sortfield, $sortorder);
    }

    /**
     * Attach a file to a session.
     *
     * Test sample 1: { "filename": "mynewfile.txt", "id": "1462", "filecontent": "content text", "fileencoding": "", "overwriteifexists": "0" }.
     * Test sample 2: { "filename": "mynewfile.txt", "id": "1462", "filecontent": "Y29udGVudCB0ZXh0Cg==", "fileencoding": "base64", "overwriteifexists": "0" }.
     *
     * @param   string  $filename           Name of file to create ('mynewfile.txt')
     * @param   string  $id                 ID of element
     * @param   string  $filecontent        File content (string with file content. An empty file will be created if this parameter is not provided)
     * @param   string  $fileencoding       File encoding (''=no encoding, 'base64'=Base 64) {@example '' or 'base64'}
     * @param   int 	$overwriteifexists  Overwrite file if exists (0 by default)
     *
     * @throws 200
     * @throws 400
     * @throws 401
     * @throws 404
     * @throws 500
     *
     * @url POST /sessions/attach
     */
    function attachToSession($filename, $id, $filecontent='', $fileencoding='', $overwriteifexists=0)
    {
        return $this->_upload_file($filename, "session", $id, $filecontent, $fileencoding, $overwriteifexists);
    }


    /***************************************************************** SessionCalendar Part ******************************************************************/

    /**
     * Get calendar of the session
     *
     * @param int   $sessid     ID of the session
     *
     * @url GET /sessions/calendar/{sessid}
     */
    function sessionGetCalendar($sessid)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);

        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) {
            throw new RestException(404, 'session not found');
        }

        $this->sessioncalendar = new Agefodd_sesscalendar($this->db);
        $result = $this->sessioncalendar->fetch_all($sessid);
        if($result < 0) throw new RestException(500, "Can't retrieve the session's calendar", array($this->session->error, $this->db->lastqueryerror));

        if(!count($this->sessioncalendar->lines)) throw new RestException(404, "No calendar for this session yet");

        $obj_ret = array();
        foreach ($this->sessioncalendar->lines as $line)
        {
            $obj = new stdClass();
            $obj->id = $line->id;
            $obj->date_session = date("Y-m-d", $line->date_session);
            $obj->heured = date("Y-m-d H:i:s", $line->heured);
            $obj->heuref = date("Y-m-d H:i:s", $line->heuref);

            $obj_ret[] = $obj;
        }

        return $obj_ret;
    }

    /**
     * Create calendar for the session
     *
     * @param int       $sessid     ID of the session
     * @param string    $mode       Creation method (datetodate, addperiod)
     * @param array     $request    Data needed for creation
     *
     * @url POST /sessions/calendar/
     */
    function sessionCreateCalendar($sessid, $mode = "datetodate", $request = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Creation not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);

        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) {
            throw new RestException(404, 'session not found');
        }

        switch ($mode)
        {
            case "datetodate" :
                return $this->_sessioncalendarDateToDate($sessid, $request);
                break;

            case "addperiod" :
                return $this->_sessioncalendarAddPeriod($sessid, $request);
                break;

            Default :
                throw new RestException(503, "Invalid mode");
        }
    }

    /**
     * create calendar for a session from date start to date end
     *
     * @param int       $sessid     ID of the session
     * @param array     $request    Data needed for creation
     *
     */
    function _sessioncalendarDateToDate($sessid, $request)
    {
        dol_include_once('/core/lib/date.lib.php');

        if(!isset($request['weekday']) || empty($request['weekday']) || !is_array($request['weekday'])) throw new RestException(503, "weekday must be provided for this mode. It must be an array with day of the week's numbers from 0 (Sunday) to 6 (Saturday).");
        if(!isset($request['datestart']) || empty($request['datestart'])) throw new RestException(503, "datestart must be provided for this mode. It must be a string date with format yyyy-mm-dd");
        if(!isset($request['dateend']) || empty($request['dateend'])) throw new RestException(503, "dateend must be provided for this mode. It must be a string date with format yyyy-mm-dd");
        if(!isset($request['hours1']) || empty($request['hours1']) || !is_array($request['hours1'])) throw new RestException(503, 'hours1 must be provided for this mode. It must be an array($starthour, $endhour). Hours must be in 24h format like 08:30 or 15:15. You can define hours2 to have 2 range of hours per day');

        if(count($request['hours1']) !== 2) throw new RestException(503, "You must provide a start hour and an end hour for hours1");
        foreach ($request['hours1'] as $hour) {
            if(!preg_match('/^[0-9]{2}:[0-9]{2}$/', $hour)) throw new RestException(503, "Bad hour $hour provided");
        }

        if(isset($request['hours2'])) {
            if(empty($request['hours2']) || !is_array($request['hours2'])){
                throw new RestException(503, 'hours2 must be provided for this mode. It must be an array($starthour, $endhour). Hours must be in 24h format like 08:30 or 15:15.');
            }

            if(count($request['hours2']) !== 2) throw new RestException(503, "You must provide a start hour and an end hour for hours2");
            foreach ($request['hours2'] as $hour) {
                if(!preg_match('/^[0-9]{2}:[0-9]{2}$/', $hour)) throw new RestException(503, "Bad hour $hour provided");
            }
        }

        $datestart = dol_mktime(0, 0, 0, substr($request['datestart'], 5, 2), substr($request['datestart'], 8, 2), substr($request['datestart'], 0, 4));
        $dateend = dol_mktime(0, 0, 0, substr($request['dateend'], 5, 2), substr($request['dateend'], 8, 2), substr($request['dateend'], 0, 4));
        if($datestart > $dateend) throw new RestException(503, "dateend must be after datestart");

        $treatmentdate = $datestart;
        while ( $treatmentdate <= $dateend ) {
            $weekday_num = dol_print_date($treatmentdate, '%w');
            if (in_array($weekday_num, $request['weekday'])) {
                $this->sessioncalendar = new Agefodd_sesscalendar($this->db);
                $this->sessioncalendar->sessid = $sessid;
                $this->sessioncalendar->date_session = $treatmentdate;

                $heure_tmp_arr = explode(':', $request['hours1'][0]);
                $this->sessioncalendar->heured = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, dol_print_date($treatmentdate, "%m"), dol_print_date($treatmentdate, "%d"), dol_print_date($treatmentdate, "%Y"));
                $heure_tmp_arr = explode(':', $request['hours1'][1]);
                $this->sessioncalendar->heuref = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, dol_print_date($treatmentdate, "%m"), dol_print_date($treatmentdate, "%d"), dol_print_date($treatmentdate, "%Y"));

                $result = $this->sessioncalendar->create(DolibarrApiAccess::$user);
                if ($result < 0) throw new RestException(500, "Creation error", array($this->session->error, $this->db->lastqueryerror));

                if (isset($request['hours2'])) {
                    $this->sessioncalendar = new Agefodd_sesscalendar($this->db);
                    $this->sessioncalendar->sessid = $sessid;
                    $this->sessioncalendar->date_session = $treatmentdate;

                    $heure_tmp_arr = explode(':', $request['hours2'][0]);
                    $this->sessioncalendar->heured = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, dol_print_date($treatmentdate, "%m"), dol_print_date($treatmentdate, "%d"), dol_print_date($treatmentdate, "%Y"));
                    $heure_tmp_arr = explode(':', $request['hours2'][1]);
                    $this->sessioncalendar->heuref = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, dol_print_date($treatmentdate, "%m"), dol_print_date($treatmentdate, "%d"), dol_print_date($treatmentdate, "%Y"));

                    $result = $this->sessioncalendar->create(DolibarrApiAccess::$user);
                    if ($result < 0) throw new RestException(500, "Creation error", array($this->session->error, $this->db->lastqueryerror));
                }
            }
            $treatmentdate = dol_time_plus_duree($treatmentdate, '1', 'd');
        }

        return $this->sessionGetCalendar($sessid);
    }

    /**
     * add a period to the session calendar
     *
     * @param int       $sessid     ID of the session
     * @param array     $request    Data needed for creation
     */
    function _sessioncalendarAddPeriod($sessid, $request)
    {
        if(!isset($request['date']) || empty($request['date'])) throw new RestException(503, "date must be provided for this mode. It must be a string date with the format yyyy-mm-dd");
        if(!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $request['date'])) throw new RestException(503, "Bad date format");

        if(!isset($request['start_hour']) || !preg_match('/^[0-9]{2}:[0-9]{2}$/', $request['start_hour'])) throw new RestException(503, "start_hour must be provided for this mode. With 24h format for ex.: 14:30 or 09:00");
        if(!isset($request['end_hour']) || !preg_match('/^[0-9]{2}:[0-9]{2}$/', $request['start_hour'])) throw new RestException(503, "end_hour must be provided for this mode. With 24h format for ex.: 14:30 or 09:00");

        $this->sessioncalendar = new Agefodd_sesscalendar($this->db);

        $this->sessioncalendar->sessid = $this->session->id;
        $this->sessioncalendar->date_session = dol_mktime(0, 0, 0, substr($request['date'], 5, 2), substr($request['date'], 8, 2), substr($request['date'], 0, 4));

        // From calendar selection
        $heure_tmp_arr = array();

        $heured_tmp = $request['start_hour'];
        if (! empty($heured_tmp)) {
            $heure_tmp_arr = explode(':', $heured_tmp);
            $this->sessioncalendar->heured = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, substr($request['date'], 5, 2), substr($request['date'], 8, 2), substr($request['date'], 0, 4));
        }

        $heuref_tmp = $request['end_hour'];
        if (! empty($heuref_tmp)) {
            $heure_tmp_arr = explode(':', $heuref_tmp);
            $this->sessioncalendar->heuref = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, substr($request['date'], 5, 2), substr($request['date'], 8, 2), substr($request['date'], 0, 4));
        }

        $result = $this->sessioncalendar->create(DolibarrApiAccess::$user);
        if ($result < 0) throw new RestException(500, "Creation error", array($this->session->error, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Period added to the session calendar'
            )
        );
    }

    /**
     * Update a session calendar period
     *
     * @param int $id ID of the period
     *
     * @url PUT /sessions/calendar/updateperiod/
     */
    function sessionCalendarPutPeriod($id, $date_session = '', $starthour = '', $endhour = '')
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->sessioncalendar = new Agefodd_sesscalendar($this->db);
        $result = $this->sessioncalendar->fetch($id);
        if($result<0) throw new RestException(404, "Period not found");

        if(!empty($date_session))
        {
            if(!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_session)) throw new RestException(503, "Bad date format. It must be in format yyyy-mm-dd");
            $this->sessioncalendar->date_session = dol_mktime(0, 0, 0, substr($date_session, 5, 2), substr($date_session, 8, 2), substr($date_session, 0, 4));
        }

        if(!empty($starthour))
        {
            if(!preg_match('/^[0-9]{2}:[0-9]{2}$/', $starthour)) throw new RestException(503, "starthour must be provided for this mode. With 24h format for ex.: 14:30 or 09:00");
            $heure_tmp_arr = explode(':', $starthour);
            $tmpH = $heure_tmp_arr[0];
            $tmpM = $heure_tmp_arr[1];
        }
        else
        {
            $tmpH = date("H",$this->sessioncalendar->heured);
            $tmpM = date("i",$this->sessioncalendar->heured);
        }
        $this->sessioncalendar->heured = dol_mktime($tmpH, $tmpM, 0, date("m", $this->sessioncalendar->date_session), date("d", $this->sessioncalendar->date_session), date("Y", $this->sessioncalendar->date_session));

        if(!empty($endhour))
        {
            if(!preg_match('/^[0-9]{2}:[0-9]{2}$/', $endhour)) throw new RestException(503, "endhour must be provided for this mode. With 24h format for ex.: 14:30 or 09:00");
            $heure_tmp_arr = explode(':', $endhour);
            $tmpH = $heure_tmp_arr[0];
            $tmpM = $heure_tmp_arr[1];
        }
        else
        {
            $tmpH = date("H",$this->sessioncalendar->heuref);
            $tmpM = date("i",$this->sessioncalendar->heuref);
        }
        $this->sessioncalendar->heuref = dol_mktime($tmpH, $tmpM, 0, date("m", $this->sessioncalendar->date_session), date("d", $this->sessioncalendar->date_session), date("Y", $this->sessioncalendar->date_session));

        $result = $this->sessioncalendar->update(DolibarrApiAccess::$user, 1);
        if($result < 0) throw new RestException(500, "Modification error", array($this->session->error, $this->db->lastqueryerror));

        dol_include_once('/comm/action/class/actioncomm.class.php');
        $action = new ActionComm($this->db);
        $result = $action->fetch($this->sessioncalendar->fk_actioncomm);
        $result = $action->fetch_userassigned();

        if($result > 0)
        {
            $action->datep = $this->sessioncalendar->heured;
            $action->datef = $this->sessioncalendar->heuref;

            $result = $action->update(DolibarrApiAccess::$user, 1);
            if($result < 0) throw new RestException(500, "Event not updated");
        }

        return $this->sessionGetCalendar($this->sessioncalendar->sessid);

    }

    /**
     * Delete a session calendar period
     *
     * @param int $id ID of the session calendar period
     *
     * @url DELETE /sessions/calendar/delperiod/{id}
     */
    function sessionCalendarDelPeriod($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->sessioncalendar = new Agefodd_sesscalendar($this->db);
        $result = $this->sessioncalendar->fetch($id);
        if($result<0) throw new RestException(404, "Period not found");

        $result = $this->sessioncalendar->remove($id);
        if($result<0) throw new RestException(500, "Error in delete period", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->sessioncalendar;
        // nettoyage des heures r��elles
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire_heures";
        $sql.= " WHERE fk_calendrier = " . $id;

        $this->db->query($sql);

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Period delete from the session calendar'
            )
        );
    }

    /**
     * Delete all calendar period for a session
     *
     * @param int $sessid ID of the session
     *
     * @url DELETE /sessions/calendar/delall/{sessid}
     */
    function sessionCalendarDelAll($sessid){
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);

        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) {
            throw new RestException(404, 'session not found');
        }

        $this->sessioncalendar = new Agefodd_sesscalendar($this->db);
        $result = $this->sessioncalendar->fetch_all($sessid);
        if($result < 0) throw new RestException(500, "Can't retrieve the session's calendar", array($this->session->error, $this->db->lastqueryerror));

        if(!count($this->sessioncalendar->lines)) throw new RestException(404, "No calendar for this session yet");

        foreach ($this->sessioncalendar->lines as $line)
        {
            $result = $this->sessioncalendar->remove($line->id);
            if($result<0) throw new RestException(500, "Error in delete period", array($this->db->lasterror, $this->db->lastqueryerror));

            // nettoyage des heures r��elles
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire_heures";
            $sql.= " WHERE fk_calendrier = " . $line->id;

            $this->db->query($sql);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'All period deleted for the session'
            )
        );
    }



    /***************************************************************** Traineeinsession Part *****************************************************************/

    /**
     * Add a trainee to a session
     *
     * @param int        $sessId            ID of the session
     * @param int        $traineeId         ID of the trainee to add
     * @param int        $type_fin          ID of the type of funding
     * @param int        $traineeStatus     ID of the status of the trainee in the Session
     * @param int        $fk_soc_link       ID of the thirdparty on documents
     * @param int        $fk_soc_requester  ID of the thirdparty which requested the training
     * @param int        $fk_socpeople_sign ID of the contact who signed the training contract
     * @param number     $hour_foad         Number of FOAD hours (for france)
     *
     * @throws RestException
     * @return number
     *
     * @url	POST /sessions/addtrainee
     */
    function sessionAddTrainee($sessId, $traineeId, $type_fin = 0, $traineeStatus = 0, $fk_soc_link = 0, $fk_soc_requester = 0, $fk_socpeople_sign = 0, $hour_foad = 0)
    {
        global $conf;

        $this->traineeinsession = new Agefodd_session_stagiaire($this->db);

        // check parameters
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->_validate(array('fk_session_agefodd' => $sessId, 'fk_stagiaire' => $traineeId), 'traineeinsession');

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessId);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');

        $this->trainee = new Agefodd_stagiaire($this->db);
        $this->trainee->fetch($traineeId);
        if( $result < 0 || empty($this->trainee->id)) throw new RestException(404, 'Trainee not found');

        $this->traineeinsession->fetch_stagiaire_per_session($sessId);
        if (count($this->traineeinsession->lines)) {
            foreach ($this->traineeinsession->lines as $line) {
                if ($line->id == $traineeId) throw new RestException(500, 'Trainee already signed up');
            }
        }

        if(empty($type_fin) && !empty($conf->global->AGF_USE_STAGIAIRE_TYPE)) $type_fin = $conf->global->AGF_DEFAULT_STAGIAIRE_TYPE;

        if(!empty($fk_soc_link)){
            $c = new Societe($this->db);
            $result = $c->fetch($fk_soc_link);
            if($result <= 0) throw new RestException(404, "Thirdparty $fk_soc_link not found");
        }

        if(!empty($fk_soc_requester)){
            $c = new Societe($this->db);
            $result = $c->fetch($fk_soc_requester);
            if($result <= 0) throw new RestException(404, "Thirdparty $fk_soc_requester not found");
        }

        if(!empty($fk_socpeople_sign)){
            $c = new Contact($this->db);
            $result = $c->fetch($fk_socpeople_sign);
            if($result <= 0) throw new RestException(404, "Contact $fk_socpeople_sign not found");
        }

        $this->traineeinsession->fk_session_agefodd = $sessId;
        $this->traineeinsession->fk_stagiaire = $traineeId;
        $this->traineeinsession->fk_agefodd_stagiaire_type = $type_fin;
        $this->traineeinsession->status_in_session = $traineeStatus;

        $this->traineeinsession->fk_soc_link = $fk_soc_link;                   // tiers pour les documents
        $this->traineeinsession->fk_soc_requester = $fk_soc_requester;         // tiers demandeur
        $this->traineeinsession->fk_socpeople_sign = $fk_socpeople_sign;       // signataire de la convention
        $this->traineeinsession->hour_foad = $hour_foad;                       // heure en parcours FOAD

        $result = $this->traineeinsession->create(DolibarrApiAccess::$user);

        if ($result > 0) {
            return array(
                'success' => array(
                    'code' => 200,
                    'message' => 'trainee added to the session'
                )
            );
        } else {
            throw new RestException(500, 'Error while adding the trainee', array($this->db->lastqueryerror));
        }
    }

    /**
     * Get trainees of the session
     *
     * @param int $id       ID of a Session
     * @param int $socid    ID of a thirdparty (return only trainee of this thirdparty if provided)
     *
     * @return array|mixed without useless informations
     *
     * @url GET /sessions/gettrainees/{sessid}
     */
    function sessionGetAllTrainees($sessid, $socid = 0)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->traineeinsession = new Agefodd_session_stagiaire($this->db);

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');

        if(!empty($socid)){
            $result = $this->session->fetch_societe_per_session($sessid);
            if( $result <= 0 ) throw new RestException(404, 'No thirdparty found');

            $TCustomers = array();
            foreach ($this->session->lines as $line)
            {
                $TCustomers[] = $line->socid;
            }

            if(count($TCustomers) == 0) throw  new RestException(404, "No thirdparty for this session");
            if(!in_array($socid, $TCustomers)) throw new RestException(404, "$socid is not a thirdparty of this session");
        }

        $result = $this->traineeinsession->fetch_stagiaire_per_session($sessid, $socid);
        if(empty($result)) throw new RestException(404, "No trainee found");
        elseif($result < 0) throw new RestException(500, "Error while retrieving trainees", array($this->db->lastqueryerror));

        $obj_ret = array();
        foreach ($this->traineeinsession->lines as $line) $obj_ret[] = $this->_cleanObjectDatas($line);

        return $obj_ret;
    }

    /**
     * Get trainees of the session by OPCA
     *
     * @param int $id           ID of a Session
     * @param int $socid        ID of a OPCA (return only trainee Founded by this thirdparty if provided)
     * @param int $traineeid    ID of the trainee (same trainee ID used to add the trainee in session)
     *
     * @return array|mixed without useless informations
     *
     * @url GET /sessions/gettraineesopca/{sessid}
     */
    function sessionGetAllTraineesOPCA($sessid, $socid = 0, $traineeid = 0)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->traineeinsession = new Agefodd_session_stagiaire($this->db);

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');

        $traineeinsessionid = 0;
        if(!empty($traineeid)){
            $result = $this->traineeinsession->fetch_by_trainee($sessid, $traineeid);
            if($result < 0) throw new RestException(500, 'Error while retrieving the trainee in session', array($this->db->lasterror, $this->db->lastqueryerror));
            elseif (empty($result)) throw new RestException(404, 'Trainee not found in this session');

            $traineeinsession = $this->traineeinsession->id;
            $this->traineeinsession = new Agefodd_session_stagiaire($this->db);
        }

        $result = $this->traineeinsession->fetch_stagiaire_per_session_per_OPCA($sessid, $socid, $traineeinsessionid);
        if(empty($result)) throw new RestException(404, "No trainee found");
        elseif($result < 0) throw new RestException(500, "Error while retrieving trainees", array($this->db->lastqueryerror));

        $obj_ret = array();

        foreach ($this->traineeinsession->lines as $line)
        {
            $obj_ret[] = $this->_cleanObjectDatas($line);
        }

        return $obj_ret;

    }

    /**
     * Get a trainee in a session
     *
     * @param int $sessid       ID of the session
     * @param int $traineeid    ID of the trainee (same trainee ID used to add the trainee in session)
     *
     * @url GET /sessions/trainee/
     */
    function sessionGetTrainee($sessid, $traineeid)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->traineeinsession = new Agefodd_session_stagiaire($this->db);

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');

        $result = $this->traineeinsession->fetch_by_trainee($sessid, $traineeid);
        if($result < 0) throw new RestException(500, 'Error while retrieving the trainee in session', array($this->db->lasterror, $this->db->lastqueryerror));
        elseif (empty($result)) throw new RestException(404, 'Trainee not found in this session');
        $obj_ret = new stdClass();

        $obj_ret->fk_session_agefodd = (isset($this->traineeinsession->fk_session_agefodd) ? $this->traineeinsession->fk_session_agefodd : null);
        $obj_ret->fk_stagiaire = (isset($this->traineeinsession->fk_stagiaire) ? $this->traineeinsession->fk_stagiaire : null);
        $obj_ret->status_in_session = (isset($this->traineeinsession->status_in_session) ? $this->traineeinsession->status_in_session : 0);
        $obj_ret->fk_agefodd_stagiaire_type = (isset($this->traineeinsession->fk_agefodd_stagiaire_type) ? $this->traineeinsession->fk_agefodd_stagiaire_type : 0);
        $obj_ret->fk_soc_link = (isset($this->traineeinsession->fk_soc_link) ? $this->traineeinsession->fk_soc_link : null);
        $obj_ret->fk_soc_requester = (isset($this->traineeinsession->fk_soc_requester) ? $this->traineeinsession->fk_soc_requester : null);
        $obj_ret->fk_socpeople_sign = (isset($this->traineeinsession->fk_socpeople_sign) ? $this->traineeinsession->fk_socpeople_sign : null);
        $obj_ret->hour_foad = (isset($this->traineeinsession->hour_foad) ? price2num($this->traineeinsession->hour_foad): null);


        return $obj_ret;
    }

    /**
     * Update a trainee of a session
     *
     * @param int       $sessid       ID of the session
     * @param int       $traineeid    ID of the trainee (same trainee ID used to add the trainee in session)
     * @param array     $request      Array fields to update
     *
     * @url PUT /sessions/trainee/
     */
    function sessionPutTrainee($sessid, $traineeid, $request = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->traineeinsession = new Agefodd_session_stagiaire($this->db);

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');

        $result = $this->traineeinsession->fetch_by_trainee($sessid, $traineeid);
        if($result < 0) throw new RestException(500, 'Error while retrieving the trainee in session', array($this->db->lasterror, $this->db->lastqueryerror));
        elseif (empty($result)) throw new RestException(404, 'Trainee not found in this session');

        foreach ($request as $field => $value){
            if($field !== 'fk_stagiaire' && $field !== 'fk_session_agefodd' ) $this->traineeinsession->$field = $value;
        }

        $result = $this->traineeinsession->update(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error while updating trainee in session", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->sessionGetTrainee($sessid, $traineeid);
    }

    /**
     * Mass Update Status of the trainees in session
     *
     * @param int       $sessid     ID of the session
     * @param int       $status     ID of the status in session
     * @param int       $socid      ID of a thirdparty (if used only status of the trainees of this thirdparty are changed)
     *
     * @return array of trainees in session
     *
     * @url PUT /sessions/traineesstatus/
     */
    function sessionMassUpdatetraineeStatus($sessid, $status, $socid = 0){
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');
        if(!is_numeric($status) || $status < 0) throw new RestException(500, 'Invalid status : '.$status);

        if(!empty($socid)){
            $result = $this->session->fetch_societe_per_session($sessid);
            if( $result <= 0 ) throw new RestException(404, 'No thirdparty found');

            $TCustomers = array();
            foreach ($this->session->lines as $line)
            {
                $TCustomers[] = $line->socid;
            }

            if(count($TCustomers) == 0) throw  new RestException(404, "No thirdparty for this session");
            if(!in_array($socid, $TCustomers)) throw new RestException(404, "$socid is not a thirdparty of this session");
        }

        $this->traineeinsession = new Agefodd_session_stagiaire($this->db);
        $this->traineeinsession->fk_session_agefodd = $sessid;

        $result = $this->traineeinsession->update_status_by_soc(DolibarrApiAccess::$user, 0, $socid, $status);
        if ($result < 0) throw new RestException(500, 'Error during modification', array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->sessionGetAllTrainees($sessid);
    }

    /**
     * Remove a trainee from a session
     *
     * @param int $sessid       ID of the session
     * @param int $traineeid    ID of the trainee (same trainee ID used to add the trainee in session)
     *
     * @url DELETE /sessions/trainee/
     */
    function sessionDeleteTrainee($sessid, $traineeid)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');

        $this->traineeinsession = new Agefodd_session_stagiaire($this->db);
        $result = $this->traineeinsession->fetch_by_trainee($sessid, $traineeid);
        if($result < 0) throw new RestException(500, 'Error while retrieving the trainee in session', array($this->db->lasterror, $this->db->lastqueryerror));
        elseif (empty($result)) throw new RestException(404, 'Trainee not found in this session');

        $result = $this->traineeinsession->delete(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, 'Error while deleting the trainee in session', array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Trainee removed from the session'
            )
        );
    }

    /***************************************************************** Trainerinsession Part *****************************************************************/

    /**
     * Add a trainer to a session
     *
     * @param int        $sessId            ID of the session
     * @param int        $trainerId         ID of the trainer to add
     * @param int        $trainerId         ID of the trainer Type
     * @param int        $trainerStatus     Status of the trainer in session
     *
     * @throws RestException
     * @return number
     *
     * @url	POST /sessions/addtrainer
     */
    function sessionAddTrainer($sessId, $trainerId, $trainerType = 0, $trainerStatus = 0){
        global $conf;

        if (empty($trainerType)) $trainerType = $conf->global->AGF_DEFAULT_FORMATEUR_TYPE;

        $this->trainerinsession = new Agefodd_session_formateur($this->db);

        // check parameters
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Creaton not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessId);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');

        $this->trainer = new Agefodd_teacher($this->db);
        $result = $this->trainer->fetch($trainerId);
        if( $result < 0 || empty($this->trainer->id) ) throw new RestException(404, 'Trainer not found');

        $this->_validate(array('fk_session' => $sessId, 'fk_agefodd_formateur' => $trainerId), 'trainerinsession');

        $this->trainerinsession->sessid = $sessId;
        $this->trainerinsession->formid = $trainerId;
        $this->trainerinsession->trainer_status = $trainerStatus;
        $this->trainerinsession->trainer_type = $trainerType;
        $result = $this->trainerinsession->create(DolibarrApiAccess::$user);

        if($result < 0) throw new RestException(500, "Error while adding trainer $trainerId to the session $sessId", array($this->db->lasterror, $this->db->lastqueryerror));

        return $result;
    }

    /**
     * Get trainers of the session
     *
     * @param int $id       ID of a Session
     *
     * @return array|mixed without useless informations
     *
     * @url GET /sessions/gettrainers/{sessid}
     */
    function sessionGetAllTrainers($sessid){
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->trainerinsession = new Agefodd_session_formateur($this->db);

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session '.$sessid.' not found');

        $result = $this->trainerinsession->fetch_formateur_per_session($sessid);
        if (empty($result)) throw new RestException(404, "No trainer for this session");
        elseif ($result < 0) throw new RestException(500, "Error while retrieving the trainers");

        $obj_ret = array();
        foreach ($this->trainerinsession->lines as $line) $obj_ret[] = $this->_cleanObjectDatas($line);

        return $obj_ret;

    }

    /**
     * Get a trainer in a session
     *
     * @param int $sessid       ID of the session
     * @param int $trainerid    ID of the trainer (same trainee ID used to add the trainer in session)
     *
     * @url GET /sessions/trainer/
     */
    function sessionGetTrainer($sessid, $trainerid)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->trainerinsession = new Agefodd_session_formateur($this->db);

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');

        $result = $this->trainerinsession->fetch_formateur_per_session($sessid);
        if (empty($result)) throw new RestException(404, "No trainer for this session");
        elseif ($result < 0) throw new RestException(500, "Error while retrieving the trainers");

        foreach ($this->trainerinsession->lines as $line) {
            if($line->formid == $trainerid) {
                $opsid = $line->opsid;
                break;
            }
        }

        $result = $this->trainerinsession->fetch($opsid);
        if($result < 0) throw new RestException(500, "Error while retrieving trainer");

        unset($this->trainerinsession->lines);
        unset($this->trainerinsession->labelstatut);
        unset($this->trainerinsession->labelstatut_short);
        unset($this->trainerinsession->errors);
        unset($this->trainerinsession->error);
        unset($this->trainerinsession->element);
        unset($this->trainerinsession->table_element);

        return $this->trainerinsession;

    }

    /**
     * Update a trainer of a session
     *
     * @param int       $sessid       ID of the session
     * @param int       $trainerid    ID of the trainer (same trainer ID used to add the trainer in session)
     * @param array     $request      Array fields to update (trainer_status, trainer_type)
     *
     * @url PUT /sessions/trainer/
     */
    function sessionPutTrainer($sessid, $trainerid, $request = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->trainerinsession = new Agefodd_session_formateur($this->db);

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');

        $result = $this->trainerinsession->fetch_formateur_per_session($sessid);
        if (empty($result)) throw new RestException(404, "No trainer for this session");
        elseif ($result < 0) throw new RestException(500, "Error while retrieving the trainers");

        $opsid = '';
        foreach ($this->trainerinsession->lines as $line) {
            if($line->formid == $trainerid) {
                $opsid = $line->opsid;
                break;
            }
        }
        if (empty($opsid)) throw new RestException(404, "Trainer not found in this session");
        $this->trainerinsession = new Agefodd_session_formateur($this->db);

        $result = $this->trainerinsession->fetch($opsid);
        if($result < 0) throw new RestException(500, "Error while retrieving trainer");
        $this->trainerinsession->opsid = $opsid;

        foreach ($request as $field => $value){
            if(in_array($field, array('trainer_status', 'trainer_type'))) $this->trainerinsession->$field = $value;
        }

        $result = $this->trainerinsession->update(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error while updating trainer in session", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->sessionGetTrainer($sessid, $trainerid);
    }

    /**
     * Remove a trainer from a session
     *
     * @param int $sessid       ID of the session
     * @param int $trainerid    ID of the trainer (same trainee ID used to add the trainer in session)
     *
     * @url DELETE /sessions/trainer/
     */
    function sessionDeleteTrainer($sessid, $trainerid)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');

        $result = $this->trainerinsession->fetch_formateur_per_session($sessid);
        if (empty($result)) throw new RestException(404, "No trainer for this session");
        elseif ($result < 0) throw new RestException(500, "Error while retrieving the trainers");

        $opsid = '';
        foreach ($this->trainerinsession->lines as $line) {
            if($line->formid == $trainerid) {
                $opsid = $line->opsid;
                break;
            }
        }
        if (empty($opsid)) throw new RestException(404, "Trainer not found in this session");
        $this->trainerinsession = new Agefodd_session_formateur($this->db);

        $result = $this->trainerinsession->fetch($opsid);
        if($result < 0) throw new RestException(500, "Error while retrieving trainer");

        $result = $this->trainerinsession->remove($opsid);
        if($result < 0) throw new RestException(500, 'Error while deleting the trainer in session', array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Trainer removed from the session'
            )
        );
    }

    /**
     * Get the calendar of a trainer in the session
     *
     * @param int       $sessid       ID of the session
     * @param int       $trainerid    ID of the trainer (same trainer ID used to add the trainer in session)
     *
     * @url GET /sessions/trainer/calendar
     */
    function sessionGetTrainerCalendar($sessid, $trainerid)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');

        $result = $this->trainerinsession->fetch_formateur_per_session($sessid);
        if (empty($result)) throw new RestException(404, "No trainer for this session");
        elseif ($result < 0) throw new RestException(500, "Error while retrieving the trainers");

        $opsid = '';
        foreach ($this->trainerinsession->lines as $line) {
            if($line->formid == $trainerid) {
                $opsid = $line->opsid;
                break;
            }
        }
        if (empty($opsid)) throw new RestException(404, "Trainer not found in this session");

        $this->trainerinsessioncalendar = new Agefoddsessionformateurcalendrier($this->db);
        $result = $this->trainerinsessioncalendar->fetch_all($opsid);
        if($result < 0) throw new RestException(500, "Error retrieving calendar", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->trainerinsessioncalendar->lines)) throw new RestException(404, "No calendar found for this trainer in the session");

        $obj_ret = array();
        foreach ($this->trainerinsessioncalendar->lines as $line)
        {
            $obj = new stdClass();
            $obj->id = $line->id;
            $obj->sessid = $sessid;
            $obj->date_session = date("Y-m-d", $line->date_session);
            $obj->heured = date("Y-m-d H:i:s", $line->heured);
            $obj->heuref = date("Y-m-d H:i:s", $line->heuref);
            $obj->status_in_session = $line->trainer_status_in_session;

            $obj_ret[] = $obj;
        }

        return $obj_ret;
    }

    /**
     * create the calendar of a trainer in the session
     *
     * @param int       $sessid       ID of the session
     * @param int       $trainerid    ID of the trainer (same trainer ID used to add the trainer in session)
     * @param string    $mode         Creation method ('addperiod' or 'fromsessioncalendar')
     *
     * @url POST /sessions/trainers/calendar
     */
    function sessionSetTrainerCalendar($sessid, $trainerid, $mode = "addperiod", $request = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');

        $result = $this->trainerinsession->fetch_formateur_per_session($sessid);
        if (empty($result)) throw new RestException(404, "No trainer for this session");
        elseif ($result < 0) throw new RestException(500, "Error while retrieving the trainers");

        $opsid = '';
        foreach ($this->trainerinsession->lines as $line) {
            if($line->formid == $trainerid) {
                $opsid = $line->opsid;
                break;
            }
        }
        if (empty($opsid)) throw new RestException(404, "Trainer not found in this session");

        switch($mode){
            case 'addperiod' :
                return $this->_trainerAddSessionCalendarPeriod($sessid, $trainerid, $opsid, $request);
                break;

            case 'fromsessioncalendar' :
                return $this->_trainerCopySessionCalendar($sessid, $trainerid, $opsid);
                break;

            Default :
                throw new RestException(503, "Invalid mode");
        }
    }

    function _trainerAddSessionCalendarPeriod($sessid, $trainerid, $opsid, $request)
    {
        global $langs, $conf;

        if(!isset($request['date']) || empty($request['date'])) throw new RestException(503, "date must be provided for this mode. It must be a string date with the format yyyy-mm-dd");
        if(!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $request['date'])) throw new RestException(503, "Bad date format");

        if(!isset($request['start_hour']) || !preg_match('/^[0-9]{2}:[0-9]{2}$/', $request['start_hour'])) throw new RestException(503, "start_hour must be provided for this mode. With 24h format for ex.: 14:30 or 09:00");
        if(!isset($request['end_hour']) || !preg_match('/^[0-9]{2}:[0-9]{2}$/', $request['start_hour'])) throw new RestException(503, "end_hour must be provided for this mode. With 24h format for ex.: 14:30 or 09:00");

        $error = 0;
        $error_message = array();
        $warning_message = array();

        $this->trainerinsessioncalendar = new Agefoddsessionformateurcalendrier($this->db);

        $this->trainerinsessioncalendar->sessid = $sessid;
        $this->trainerinsessioncalendar->fk_agefodd_session_formateur = $opsid;
        $this->trainerinsessioncalendar->date_session = dol_mktime(0, 0, 0, substr($request['date'], 5, 2), substr($request['date'], 8, 2), substr($request['date'], 0, 4));

        // From calendar selection
        $heure_tmp_arr = array();

        $heured_tmp = $request['start_hour'];
        if (! empty($heured_tmp)) {
            $heure_tmp_arr = explode(':', $heured_tmp);
            $this->trainerinsessioncalendar->heured = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, substr($request['date'], 5, 2), substr($request['date'], 8, 2), substr($request['date'], 0, 4));
        }

        $heuref_tmp = $request['end_hour'];
        if (! empty($heuref_tmp)) {
            $heure_tmp_arr = explode(':', $heuref_tmp);
            $this->trainerinsessioncalendar->heuref = dol_mktime($heure_tmp_arr[0], $heure_tmp_arr[1], 0, substr($request['date'], 5, 2), substr($request['date'], 8, 2), substr($request['date'], 0, 4));
        }

        // Test if trainer is already book for another training
        $result = $this->trainerinsessioncalendar->fetch_all_by_trainer($trainerid);
        if ($result < 0) throw new RestException(500, "Error retrieving trainer calendar", array($this->db->lasterror, $this->db->lastqueryerror));

        foreach ( $this->trainerinsessioncalendar->lines as $line ) {
            if (! empty($line->trainer_status_in_session) && $line->trainer_status_in_session != 6) {
                if (($this->trainerinsessioncalendar->heured <= $line->heured && $this->trainerinsessioncalendar->heuref >= $line->heuref) || ($this->trainerinsessioncalendar->heured >= $line->heured && $this->trainerinsessioncalendar->heuref <= $line->heuref) || ($this->trainerinsessioncalendar->heured <= $line->heured && $this->trainerinsessioncalendar->heuref <= $line->heuref && $this->trainerinsessioncalendar->heuref > $line->heured) || ($this->trainerinsessioncalendar->heured >= $line->heured && $this->trainerinsessioncalendar->heuref >= $line->heuref && $this->trainerinsessioncalendar->heured < $line->heuref)) {
                    if (! empty($conf->global->AGF_ONLY_WARNING_ON_TRAINER_AVAILABILITY)) {
                        $warning_message[] = $langs->trans('AgfTrainerlAreadybookAtThisTime') . '(<a href=' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?id=' . $line->fk_session . ' target="_blanck">' . $line->fk_session . '</a>)<br>';
                    } else {
                        $error ++;
                        $error_message[] = $langs->trans('AgfTrainerlAreadybookAtThisTime') . '(<a href=' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?id=' . $line->fk_session . ' target="_blanck">' . $line->fk_session . '</a>)<br>';
                    }
                }
            }
        }
        if (! $error) {

            $result = $this->trainerinsessioncalendar->create(DolibarrApiAccess::$user);
            if ($result < 0) {
                $error ++;
                $error_message[] = $this->trainerinsessioncalendar->error;
            }
        }

        if(!$error) {
            return array(
                'success' => array(
                    'code' => 200,
                    'message' => 'Trainer\'s calendar period added',
                    'warnings' => $warning_message
                )
            );
        } else {
            return array(
                'fail' => array(
                    'code' => 500,
                    'message' => 'Trainer\'s calendar period not added',
                    'errors' => $error_message,
                    'warnings' => $warning_message
                )
            );
        }

    }

    function _trainerCopySessionCalendar($sessid, $trainerid, $opsid)
    {
        global $langs, $conf;

        $error_message = array();
        $warning_message = array();

        $this->sessioncalendar = new Agefodd_sesscalendar($this->db);
        $result = $this->sessioncalendar->fetch_all($sessid);
        if($result < 0) throw new RestException(500, "Error retrieving session calendar", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->sessioncalendar->lines)) throw new RestException(404, "No calendar defined for this session");

        // Delete all time already inputed
        $this->trainerinsessioncalendar = new Agefoddsessionformateurcalendrier($this->db);
        $this->trainerinsessioncalendar->fetch_all($opsid);
        if (is_array($this->trainerinsessioncalendar->lines) && count($this->trainerinsessioncalendar->lines) > 0) {
            foreach ( $this->trainerinsessioncalendar->lines as $line ) {
                $delteobject = new Agefoddsessionformateurcalendrier($this->db);
                $delteobject->remove($line->id);
            }
        }

        // Create as many as session caldendar
        foreach ( $this->sessioncalendar->lines as $line ) {
            $error = 0;

            $this->trainerinsessioncalendar = new Agefoddsessionformateurcalendrier($this->db);

            $this->trainerinsessioncalendar->sessid = $sessid;
            $this->trainerinsessioncalendar->fk_agefodd_session_formateur = $opsid;

            $this->trainerinsessioncalendar->date_session = $line->date_session;

            $this->trainerinsessioncalendar->heured = $line->heured;
            $this->trainerinsessioncalendar->heuref = $line->heuref;

            // Test if trainer is already book for another training
            $result = $this->trainerinsessioncalendar->fetch_all_by_trainer($trainerid);
            if ($result < 0) throw new RestException(500, "Error retrieving trainer calendar", array($this->db->lasterror, $this->db->lastqueryerror));

            foreach ( $this->trainerinsessioncalendar->lines as $line ) {
                if (! empty($line->trainer_status_in_session) && $line->trainer_status_in_session != 6) {

                    if (($this->trainerinsessioncalendar->heured <= $line->heured && $this->trainerinsessioncalendar->heuref >= $line->heuref) || ($this->trainerinsessioncalendar->heured >= $line->heured && $this->trainerinsessioncalendar->heuref <= $line->heuref) || ($this->trainerinsessioncalendar->heured <= $line->heured && $this->trainerinsessioncalendar->heuref <= $line->heuref && $this->trainerinsessioncalendar->heuref > $line->heured) || ($this->trainerinsessioncalendar->heured >= $line->heured && $this->trainerinsessioncalendar->heuref >= $line->heuref && $this->trainerinsessioncalendar->heured < $line->heuref)) {
                        if (! empty($conf->global->AGF_ONLY_WARNING_ON_TRAINER_AVAILABILITY)) {
                            $warning_message[] = $langs->trans('AgfTrainerlAreadybookAtThisTime') . '(<a href=' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?id=' . $line->fk_session . ' target="_blanck">' . $line->fk_session . '</a>)<br>';
                        } else {
                            $error ++;
                            $error_message[] = $langs->trans('AgfTrainerlAreadybookAtThisTime') . '(<a href=' . dol_buildpath('/agefodd/session/trainer.php', 1) . '?id=' . $line->fk_session . ' target="_blanck">' . $line->fk_session . '</a>)<br>';
                        }
                    }
                }
            }

            if (! $error) {
                $result = $this->trainerinsessioncalendar->create(DolibarrApiAccess::$user);
                if ($result < 0) {
                    $error_message[] = $this->trainerinsessioncalendar->error;
                }
            }
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Trainer\'s calendar copied from the session',
                'errors' => $error_message,
                'warnings' => $warning_message
            )
        );
    }

    /**
     * Update a trainer's calendar period
     *
     * @param int $id ID of the period
     *
     * @url PUT /sessions/trainer/calendar/
     */
    function sessionPutTrainerCalendarPeriod($id, $date_session = '', $starthour = '', $endhour = '')
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->trainerinsessioncalendar = new Agefoddsessionformateurcalendrier($this->db);
        $result = $this->trainerinsessioncalendar->fetch($id);
        if($result<0 || empty($this->trainerinsessioncalendar->id)) throw new RestException(404, "Period not found");

        if(!empty($date_session))
        {
            if(!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_session)) throw new RestException(503, "Bad date format. It must be in format yyyy-mm-dd");
            $this->trainerinsessioncalendar->date_session = dol_mktime(0, 0, 0, substr($date_session, 5, 2), substr($date_session, 8, 2), substr($date_session, 0, 4));
        }

        if(!empty($starthour))
        {
            if(!preg_match('/^[0-9]{2}:[0-9]{2}$/', $starthour)) throw new RestException(503, "starthour must be provided for this mode. With 24h format for ex.: 14:30 or 09:00");
            $heure_tmp_arr = explode(':', $starthour);
            $tmpH = $heure_tmp_arr[0];
            $tmpM = $heure_tmp_arr[1];
        }
        else
        {
            $tmpH = date("H",$this->trainerinsessioncalendar->heured);
            $tmpM = date("i",$this->trainerinsessioncalendar->heured);
        }
        $this->trainerinsessioncalendar->heured = dol_mktime($tmpH, $tmpM, 0, date("m", $this->trainerinsessioncalendar->date_session), date("d", $this->trainerinsessioncalendar->date_session), date("Y", $this->trainerinsessioncalendar->date_session));

        if(!empty($endhour))
        {
            if(!preg_match('/^[0-9]{2}:[0-9]{2}$/', $endhour)) throw new RestException(503, "endhour must be provided for this mode. With 24h format for ex.: 14:30 or 09:00");
            $heure_tmp_arr = explode(':', $endhour);
            $tmpH = $heure_tmp_arr[0];
            $tmpM = $heure_tmp_arr[1];
        }
        else
        {
            $tmpH = date("H",$this->trainerinsessioncalendar->heuref);
            $tmpM = date("i",$this->trainerinsessioncalendar->heuref);
        }
        $this->trainerinsessioncalendar->heuref = dol_mktime($tmpH, $tmpM, 0, date("m", $this->trainerinsessioncalendar->date_session), date("d", $this->trainerinsessioncalendar->date_session), date("Y", $this->trainerinsessioncalendar->date_session));

        $this->trainerinsession->fetch($this->trainerinsessioncalendar->fk_agefodd_session_formateur);

        // the trainerinsessioncalendar->sessid doesn't exist but is needed to update linked ActionComm
        $this->trainerinsessioncalendar->sessid =  $this->trainerinsession->sessid;

        $result = $this->trainerinsessioncalendar->update(DolibarrApiAccess::$user, 1);
        if($result < 0) throw new RestException(500, "Modification error", array($this->trainerinsessioncalendar->error, $this->db->lastqueryerror));

        return $this->sessionGetTrainerCalendar($this->trainerinsession->sessid, $this->trainerinsession->formid);
    }

    /**
     * Delete a Calendar period for a trainer in a session
     *
     * @param int   $id     ID of the trainer's calendar period
     *
     * @url DELETE /sessions/trainer/calendar
     */
    function sessionDelTrainerCalendarPeriod($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->trainerinsessioncalendar = new Agefoddsessionformateurcalendrier($this->db);
        $result = $this->trainerinsessioncalendar->remove($id);
        if($result < 0) throw new RestException(500, "Error Deleting trainer calendar period", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Trainer\'s calendar period deleted'
            )
        );
    }

    /***************************************************************** Convention Part *****************************************************************/
    /**
     * Get a session convention.
     *
     * You can get it by $sessid/$socid or the $id of the conventionif you know it
     *
     * @param int $sessid ID of the session
     * @param int $socid  ID of the thirdparty involved
     * @param int $id     ID of the convention (if not 0, sessid and socid don't matter)
     *
     * @url GET /sessions/convention
     */
    function sessionGetConvention($sessid, $socid, $id = 0) // fetch
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->convention = new Agefodd_convention($this->db);
        $result = $this->convention->fetch($sessid, $socid, $id);
        if($result < 0) throw new RestException(500, "Error retrieving convention", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->convention->id)) throw new RestException(404, "Convention not found");

        if(!empty($this->convention->line_trainee)){
            // remplacer les id des traineeinsession qui est un attribut que nous garderont privé par celui du trainee de base
            $trainees = $this->convention->line_trainee;
            $this->convention->line_trainee = array();
            foreach ($trainees as $traineeid)
            {
                $this->traineeinsession = new Agefodd_session_stagiaire($this->db);
                $result = $this->traineeinsession->fetch($traineeid);
                if($result > 0) $this->convention->line_trainee[] = $this->traineeinsession->fk_stagiaire;
            }
        }

        unset($this->convention->error);
        unset($this->convention->errors);
        unset($this->convention->element);
        unset($this->convention->table_element);

        return $this->convention;
    }

    /**
     * Get infos on a convention
     *
     * @param int $id ID of the convention
     *
     * @url GET /sessions/convention/info
     */
    function sessionGetConventionInfos($id) // info
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->convention = new Agefodd_convention($this->db);
        $result = $this->convention->fetch(0, 0, $id);
        $result = $this->convention->info($id);
        if($result < 0) throw new RestException(500, "Error retrieving convention", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->convention->id)) throw new RestException(404, "Convention not found");

        if(!empty($this->convention->line_trainee)){
            // remplacer les id des traineeinsession qui est un attribut que nous garderont privé par celui du trainee de base
            $trainees = $this->convention->line_trainee;
            $this->convention->line_trainee = array();
            foreach ($trainees as $traineeid)
            {
                $this->traineeinsession = new Agefodd_session_stagiaire($this->db);
                $result = $this->traineeinsession->fetch($traineeid);
                if($result > 0) $this->convention->line_trainee[] = $this->traineeinsession->fk_stagiaire;
            }
        }

        unset($this->convention->error);
        unset($this->convention->errors);
        unset($this->convention->element);
        unset($this->convention->table_element);

        return $this->convention;
    }

    /**
     * Get all convention for a session.
     * Can be filtered by socid
     *
     * @param int $sessid ID of the session
     * @param int $socid  ID of the thirdparty (to get only the convention of the thirdparty)
     *
     * @url GET /sessions/convention/all
     */
    function sessionGetAllConvention($sessid, $socid = 0) // fetch_all
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->convention = new Agefodd_convention($this->db);
        $result = $this->convention->fetch_all($sessid, $socid);
        if($result < 0) throw new RestException(500, "Error retrieving convention", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->convention->lines)) throw new RestException(404, "No convention found");

        $obj_ret = array();

        foreach ($this->convention->lines as $line)
        {
            if(!empty($line->line_trainee)){
                // remplacer les id des traineeinsession qui est un attribut que nous garderont privé par celui du trainee de base
                $trainees = $line->line_trainee;
                $line->line_trainee = array();
                foreach ($trainees as $traineeid)
                {
                    $this->traineeinsession = new Agefodd_session_stagiaire($this->db);
                    $result = $this->traineeinsession->fetch($traineeid);
                    if($result > 0) $line->line_trainee[] = $this->traineeinsession->fk_stagiaire;
                }
            }

            unset($line->error);
            unset($line->errors);
            unset($line->element);
            unset($line->table_element);

            $obj_ret[] = $line;
        }

        return $obj_ret;
    }

    /**
     * Create a Convention.
     *
     * All text optionnal fields can be left blank and will be replaced by a default text
     *
     * @param int       $sessid               ID of the session
     * @param int       $socid                ID of the thirdparty
     * @param int       $source_element_id    ID of the proposal, order or invoice linked to the session for this thirdparty
     * @param string    $source_type          Type of the source element ("propal", "order" or "invoice")
     * @param array     $trainees             Array of trainee id
     * @param string    $intro1               First text of the convention (informations on your organisation)
     * @param string    $intro2               Second text of the convention (informations on the thirdparty)
     * @param array     $articles             Array of strings from "art1" to "art9"
     * @param string    $sig                  The thirdparty signature
     * @param string    $notes                Some comments on the convention
     *
     * @url POST /sessions/convention
     */
    function sessionPostConvention($sessid, $socid, $source_element_id = 0, $source_type = '', $trainees = array(), $intro1 = '', $intro2 = '', $articles = array(), $sig = '', $notes = '')
    {
        global $conf, $langs;

        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Creation not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');

        $result = $this->session->fetch_societe_per_session($sessid);
        if( $result <= 0 ) throw new RestException(404, 'No thirdparty found');

        $TCustomers = array();
        foreach ($this->session->lines as $line)
        {
            /*if ($line->typeline == "customer")*/ $TCustomers[] = $line->socid;

        }

        if(count($TCustomers) == 0) throw  new RestException(404, "No thirdparty for this session");
        if(!in_array($socid, $TCustomers)) throw new RestException(404, "$socid is not a thirdparty of this session");

        if(empty($conf->global->AGF_ALLOW_CONV_WITHOUT_FINNACIAL_DOC) && (empty($source_element_id) || empty($source_type) || !in_array($source_type, array("propal", "order", "invoice")))) throw new RestException(500, "the fields source_element_id and source_type are required.");

        if (! empty($conf->propal->enabled))	{
            require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
        }
        if (! empty($conf->facture->enabled))	{
            require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
        }
        if (! empty($conf->commande->enabled))	{
            require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
        }

        // Get proposal/order/invoice informations
        $agf_comid = new Agefodd_session_element($this->db);
        $result = $agf_comid->fetch_by_session_by_thirdparty($sessid, $socid);

        $order_array = array ();
        $propal_array = array ();
        $invoice_array = array ();
        foreach ( $agf_comid->lines as $line ) {
            if ($line->element_type == 'order' && ! empty($line->comref)) {
                $order = new Commande($this->db);
                $order->fetch($line->fk_element);
                if (($order->statut != - 1) && ($order->statut != 0)) {
                    $order_array [$line->fk_element] = $line->comref;
                }
            }
            if ($line->element_type == 'propal' && ! empty($line->propalref)) {
                $propal = new Propal($this->db);
                $propal->fetch($line->fk_element);
                if (($propal->statut != 3) && ($propal->statut != 0)) {
                    $propal_array [$line->fk_element] = $line->propalref;
                }
            }
            if ($line->element_type == 'invoice' && ! empty($line->facnumber)) {
                $invoice = new Facture($this->db);
                $invoice->fetch($line->fk_element);
                if ($invoice->statut != 0) {
                    $invoice_array [$line->fk_element] = $line->facnumber;
                }
            }
        }

        if ((count($propal_array) == 0) && (count($order_array) == 0) && (count($invoice_array) == 0) && empty($conf->global->AGF_ALLOW_CONV_WITHOUT_FINNACIAL_DOC)) {
            throw new RestException(500, $langs->trans("AgfFacturePropalHelp"));
        }

        // on prérempli la convention avec les infos génériques (issues des traductions)
        $this->_generateNewConv($sessid, $socid);

        $arraytotest = array_merge($trainees, $this->convention->line_trainee);
        $this->convention->line_trainee = array();

        if(!empty($source_element_id)) {
            if(!empty($source_type)) {
                $this->convention->element = $source_element_id;
                $this->convention->element_type = $source_type;
            }
            else
            {
                throw new RestException(500, "you have provided a source_element_id but the field source_type is missing");
            }
        }

        if(!empty($arraytotest)){
            foreach ($arraytotest as $traineeid){
                $result = $this->traineeinsession->fetch_by_trainee($sessid, $traineeid);
                if($result > 0) $this->convention->line_trainee[] = $this->traineeinsession->id;
            }
        }

        if(!empty($articles))
        {
            for($i = 1; $i < 10; $i++)
            {
                $key = "art".$i;
                if(!empty($articles[$key])) $this->convention->{$key} = $articles[$key];
            }
        }

        $result = $this->convention->create(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error creating convention", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->sessionGetConvention(0, 0, $this->convention->id);
    }

    function _generateNewConv($sessid, $socid)
    {
        global $conf, $langs, $mysoc;

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);

        $this->convention = new Agefodd_convention($this->db);
        $this->convention->sessid = $sessid;
        $this->convention->socid = $socid;
        $this->convention->model_doc = "pdf_convention";

        // intro1
        $statut = getFormeJuridiqueLabel($mysoc->forme_juridique_code);
        $this->convention->intro1 = $langs->transnoentitiesnoconv('AgfConvIntro1_1') . ' ' . $mysoc->name . ', ' . $statut;
        if (! empty($mysoc->capital)) {
            $this->convention->intro1 .= ' ' . $langs->transnoentitiesnoconv('AgfConvIntro1_2');
            $this->convention->intro1 .= ' ' . $mysoc->capital . ' ' . $langs->transnoentitiesnoconv("Currency" . $conf->currency);
        }

        $addr = preg_replace( "/\r|\n/", " ", $mysoc->address . ', ' . $mysoc->zip . ' ' . $mysoc->town );
        $this->convention->intro1 .= $langs->transnoentitiesnoconv('AgfConvIntro1_3') . ' ' . $addr;
        if (! empty($mysoc->idprof1)) {
            $this->convention->intro1 .= $langs->transnoentitiesnoconv('AgfConvIntro1_4') . ' ' . $mysoc->idprof1;
        }
        if (empty($conf->global->AGF_ORGANISME_NUM)) {
            $this->convention->intro1 .= ' ' . $langs->transnoentitiesnoconv('AgfConvIntro1_5') . ' ' . $conf->global->AGF_ORGANISME_PREF;
        } else {
            $this->convention->intro1 .= ' ' . $langs->transnoentitiesnoconv('AgfConvIntro1_6');
            $this->convention->intro1 .= ' ' . $conf->global->AGF_ORGANISME_PREF . ' ' . $langs->transnoentitiesnoconv('AgfConvIntro1_7') . ' ' . $conf->global->AGF_ORGANISME_NUM. ' '. $langs->transnoentitiesnoconv('AgfConvOrg1');
        }
        if (! empty($conf->global->AGF_ORGANISME_REPRESENTANT)) {
            $this->convention->intro1 .= $langs->transnoentitiesnoconv('AgfConvIntro1_8') . ' ' . $conf->global->AGF_ORGANISME_REPRESENTANT . $langs->transnoentitiesnoconv('AgfConvIntro1_9');
        }

        // intro2
        // Get trhidparty info
        $agf_soc = new Societe($this->db);
        $result = $agf_soc->fetch($socid);


        // intro2
        $addr = preg_replace( "/\r|\n/", " ", $agf_soc->address. ", " . $agf_soc->zip . " " . $agf_soc->town );
        $this->convention->intro2 = $langs->transnoentitiesnoconv('AgfConvIntro2_1') . ' ' . $agf_soc->name ;
        if (!empty($addr)) {
            $this->convention->intro2 .= ", ". $langs->transnoentitiesnoconv('AgfConvIntro2_2') . ' ' . $addr ;
        }
        if (!empty($agf_soc->idprof2)) {
            $this->convention->intro2 .= ", ". $langs->transnoentitiesnoconv('AgfConvIntro2_3') . ' ' . $agf_soc->idprof2;
        }

        $signataire='';
        $contactname=trim($this->session->contactname);
        if (!empty($contactname)) {
            $this->convention->intro2 .= ', ' . $langs->transnoentitiesnoconv('AgfConvIntro2_4') . ' ';
            $this->convention->intro2 .= ucfirst(strtolower($this->session->contactcivilite)) . ' ' . $this->session->contactname;
            $this->convention->intro2 .= ' ' . $langs->transnoentitiesnoconv('AgfConvIntro2_5');
        } else {

            //Trainee link to the company convention
            $stagiaires = new Agefodd_session_stagiaire($this->db);
            $result=$stagiaires->fetch_stagiaire_per_session($sessid,$socid,1);
            if ($result>0) {
                if (is_array($stagiaires->lines) && count($stagiaires->lines)>0) {

                    foreach ($stagiaires->lines as $line) {
                        if (!empty($line->fk_socpeople_sign)) {
                            $socpsign=new Contact($this->db);
                            $socpsign->fetch($line->fk_socpeople_sign);
                            $signataire=$socpsign->getFullName($langs).' ';
                        }
                    }
                    if (!empty($signataire)) {
                        $this->convention->intro2 .= ', ' . $langs->transnoentitiesnoconv('AgfConvIntro2_4') . ' '.$signataire.' '. $langs->transnoentitiesnoconv('AgfConvIntro2_5');
                    }


                }
            }
        }

        // article 1
        $this->convention->art1 = $langs->transnoentitiesnoconv('AgfConvArt1_1') . "\n";
        $this->convention->art1 .= $langs->transnoentitiesnoconv('AgfConvArt1_2') . ' ' . $this->session->formintitule . ' ' . $langs->transnoentitiesnoconv('AgfConvArt1_3') . " \n". "\n";

        $obj_peda = new Formation($this->db);
        $resql = $obj_peda->fetch_objpeda_per_formation($this->session->formid);
        if (count( $obj_peda->lines)>0) {
            $this->convention->art1 .= $langs->transnoentitiesnoconv('AgfConvArt1_4') . "\n";
            foreach ( $obj_peda->lines as $line ) {
                $this->convention->art1 .= "-	" . $line->intitule . "\n";
            }
            $this->convention->art1 .= "\n";
        }
        $this->convention->art1 .= $langs->transnoentitiesnoconv('AgfConvArt1_6') . "\n". "\n";
        $this->convention->art1 .= $langs->transnoentitiesnoconv('AgfConvArt1_7');

        if ($this->session->dated == $this->session->datef)
            $this->convention->art1 .= $langs->transnoentitiesnoconv('AgfConvArt1_8') . ' ' . dol_print_date($this->session->datef);
        else
            $this->convention->art1 .= $langs->transnoentitiesnoconv('AgfConvArt1_9') . ' ' . dol_print_date($this->session->dated) . ' ' . $langs->transnoentitiesnoconv('AgfConvArt1_10') . ' ' . dol_print_date($this->session->datef);

        $this->convention->art1 .= "\n";

        // Durée de formation
        $this->convention->art1 .= $langs->transnoentitiesnoconv('AgfConvArt1_11') . ' ' . $this->session->duree_session . ' ' . $langs->transnoentitiesnoconv('AgfConvArt1_12') . ' ' . "\n";

        $calendrier = new Agefodd_sesscalendar($this->db);
        $resql = $calendrier->fetch_all($sessid);
        $blocNumber = count($calendrier->lines);
        $old_date = 0;
        $duree = 0;
        for($i = 0; $i < $blocNumber; $i ++) {
            if ($calendrier->lines [$i]->date_session != $old_date) {
                if ($i > 0)
                    $this->convention->art1 .= "), ";
                $this->convention->art1 .= dol_print_date($calendrier->lines [$i]->date_session, 'daytext') . ' (';
            } else
                $this->convention->art1 .= '/';
            $this->convention->art1 .= dol_print_date($calendrier->lines [$i]->heured, 'hour');
            $this->convention->art1 .= ' - ';
            $this->convention->art1 .= dol_print_date($calendrier->lines [$i]->heuref, 'hour');
            if ($i == $blocNumber - 1)
                $this->convention->art1 .= ').' . "\n";

            $old_date = $calendrier->lines [$i]->date_session;
        }

        // Formateur
        $formateurs = new Agefodd_session_formateur($this->db);
        $nbform = $formateurs->fetch_formateur_per_session($this->session->id);
        foreach($formateurs->lines as $trainer) {
            $TTrainer[] = $trainer->firstname . ' ' . $trainer->lastname;
        }
        if ($nbform>0) {
            $this->convention->art1 .= "\n". $langs->transnoentitiesnoconv('AgfTrainingTrainer') . ' : ' . implode(', ', $TTrainer) . "\n";
        }

        $this->convention->art1 .= "\n". $langs->transnoentitiesnoconv('AgfConvArt1_13') . "\n". "\n";


        $this->convention->art1 .= $langs->transnoentitiesnoconv('AgfConvArt1_14') . ' Nb_participants ';
        $this->convention->art1 .= $langs->transnoentitiesnoconv('AgfConvArt1_17') . "\n". "\n";

        // Adresse lieu de formation
        $agf_place = new Agefodd_place($this->db);
        $resql3 = $agf_place->fetch($agf->placeid);
        $addr = preg_replace( "/\r|\n/", " ", $agf_place->adresse . ", " . $agf_place->cp . " " . $agf_place->ville );
        $this->convention->art1 .= $langs->transnoentitiesnoconv('AgfConvArt1_18') . $agf_place->ref_interne . $langs->transnoentitiesnoconv('AgfConvArt1_19') . ' ' . $addr . '.';


        // article 2
        $this->convention->art2 = $langs->transnoentitiesnoconv('AgfConvArt2_1');

        // article 3
        $this->convention->art3 = $langs->transnoentitiesnoconv('AgfConvArt3_1');

        // article 4
        if (empty($conf->global->FACTURE_TVAOPTION)) {
            $this->convention->art4 = $langs->transnoentitiesnoconv('AgfConvArt4_1');
        } else {
            $this->convention->art4 = $langs->transnoentitiesnoconv('AgfConvArt4_3');
        }
        $this->convention->art4 .= "\n" . $langs->transnoentitiesnoconv('AgfConvArt4_2');

        // article 5
        $listOPCA = '';

        if(! empty($this->session->type_session)) { // Session inter-entreprises : OPCA gérés par participant
            dol_include_once('/agefodd/class/agefodd_opca.class.php');
            $stagiaires = new Agefodd_session_stagiaire($this->db);
            $resulttrainee = $stagiaires->fetch_stagiaire_per_session($this->session->id);
            if ($resulttrainee > 0) {
                foreach($stagiaires->lines as $line) {
                    $opca = new Agefodd_opca($this->db);
                    $opca->getOpcaForTraineeInSession($line->socid, $this->session->id, $line->stagerowid);

                    if(! empty($opca->soc_OPCA_name)) { // Au moins un participant avec un OPCA
                        $listOPCA = ' ('.$langs->transnoentitiesnoconv('AgfMailTypeContactOPCA').' : List_OPCA)';
                        break;
                    }
                }
            }

        } elseif(! empty($this->session->fk_soc_OPCA)) {
            $listOPCA = ' ('.$langs->transnoentitiesnoconv('AgfMailTypeContactOPCA').' : List_OPCA)';
        }

        $this->convention->art5 = $langs->transnoentitiesnoconv('AgfConvArt5_1', $listOPCA);

        // article 6
        $this->convention->art6 = $langs->transnoentitiesnoconv('AgfConvArt6_1') . "\n". "\n";
        $this->convention->art6 .= $langs->transnoentitiesnoconv('AgfConvArt6_2') . "\n". "\n";
        $this->convention->art6 .= $langs->transnoentitiesnoconv('AgfConvArt6_3') . "\n". "\n";
        $this->convention->art6 .= $langs->transnoentitiesnoconv('AgfConvArt6_4') . "\n". "\n";

        // article 7
        $this->convention->art7 = $langs->transnoentitiesnoconv('AgfConvArt7_1'). ' ';
        $this->convention->art7 .= $langs->transnoentitiesnoconv('AgfConvArt7_2') . ' ' . $mysoc->town . ".";

        // article 9
        $this->convention->art9 = $langs->transnoentitiesnoconv('AgfConvArt9_1'). "\n";
        $this->convention->art9 .= $langs->transnoentitiesnoconv('AgfConvArt9_2');

        // Signature du client
        $this->convention->sig = $agf_soc->name . "\n";
        $this->convention->sig .= $langs->transnoentitiesnoconv('AgfConvArtSigCli') . ' ';
        //$sig .= ucfirst(strtolower($agf_contact->civilite)) . ' ' . $agf_contact->firstname . ' ' . $agf_contact->lastname . " (*)";
        $contactname=trim($agf->contactname);
        if (!empty($contactname)) {
            $this->convention->sig .= $agf->contactname;
        } elseif (!empty($signataire)) {
            $this->convention->sig .= $signataire;
        }
        $this->convention->sig .= " (*)";

        // sélection des stagiaires liés à l'entreprise
        $stagiaires = new Agefodd_session_stagiaire($this->db);
        //Trainee link to the company convention
        $stagiaires->fetch_stagiaire_per_session($sessid,$socid,1);
        $options_trainee_array_selected_id = array();
        foreach ( $stagiaires->lines as $traine_line ) {
            $options_trainee_array_selected_id [] = $traine_line->id;
        }

        $this->convention->line_trainee = $options_trainee_array_selected_id;
        $this->convention->socname = $agf_soc->name;

    }

    /**
     * Update a convention.
     *
     * All fields left blank will not be changed
     *
     * @param int $id ID of the convention
     * @param int       $source_element_id    ID of the proposal, order or invoice linked to the session for this thirdparty
     * @param string    $source_type          Type of the source element ("propal", "order" or "invoice")
     * @param array     $trainees             Array of trainee id
     * @param string    $intro1               First text of the convention (informations on your organisation)
     * @param string    $intro2               Second text of the convention (informations on the thirdparty)
     * @param array     $articles             Array of strings from "art1" to "art9"
     * @param string    $sig                  The thirdparty signature
     * @param string    $notes                Some comments on the convention
     *
     * @url PUT /sessions/convention
     */
    function sessionPutConvention($id, $source_element_id = 0, $source_type = '', $trainees = array(), $intro1 = '', $intro2 = '', $articles = array(), $sig = '', $notes = '')
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->convention = new Agefodd_convention($this->db);
        $result = $this->convention->fetch(0, 0, $id);
        if($result < 0) throw new RestException(500, "Error retrieving convention", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->convention->id)) throw new RestException(404, "Convention not found");

        if (! empty($intro1))
            $this->convention->intro1 = $intro1;
        if (! empty($intro2))
            $this->convention->intro2 = $intro2;

        if(!empty($articles))
        {
            for($i = 1; $i < 10; $i++)
            {
                $key = "art".$i;
                if(!empty($articles[$key])) $this->convention->{$key} = $articles[$key];
            }
        }

        if (! empty($sig))
            $this->convention->sig = $sig;
        if (! empty($source_element_id))
            $this->convention->fk_element = $source_element_id;
        if (! empty($source_type))
            $this->convention->element_type = $source_type;

        $this->convention->notes = $notes;

        //$this->convention->line_trainee = $trainees;
        if(!empty($trainees) && $trainees[0] !== ""){
            $this->convention->line_trainee = array();
            foreach ($trainees as $traineeid){
                $result = $this->traineeinsession->fetch_by_trainee($sessid, $traineeid);
                if($result > 0) $this->convention->line_trainee[] = $this->traineeinsession->id;
            }
        }

        $result = $this->convention->update(DolibarrApiAccess::$user);
        if ($result < 0) throw new RestException(500, "Error updating the convention", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->sessionGetConvention(0, 0, $id);
    }

    /**
     * Delete a convention
     *
     * @param int $id ID of the convention
     *
     * @url DELETE /sessions/convention
     */
    function sessionDeleteConvention($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->convention = new Agefodd_convention($this->db);
        $result = $this->convention->fetch($sessid, $socid, $id);
        if($result < 0) throw new RestException(500, "Error retrieving convention", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->convention->id)) throw new RestException(404, "Convention not found");

        $result = $this->convention->remove($id);
        if($result < 0) throw new RestException(500, "Can't delete the convention", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => "convention has been deleted"
            )
        );
    }

    /***************************************************************** Documents Part *****************************************************************/

    /**
     * Generate an agefodd document of a session.
     *
     *
     * @param int       $sessid     ID of the session
     * @param string    $model      Name of the PDF model to generate
     * @param int       $socid      ID of the thirdparty involved (or trainer for model "mission_trainer" or trainee for models "convocation_trainee", "attestation_trainee" and "attestationendtraining_trainee")
     * @param string    $cour       Name of the letter model if model is courrier (it can be "accueil", "cloture" or "convention")
     * @param int       $langid     ID of a language if needed
     *
     * @url POST /sessions/generatedocument
     */
    function sessionGenerateDocument($sessid, $model, $socid = 0, $cour='', $langid = 0)
    {
        global $conf, $langs;

        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'PDF generation not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if( $result < 0 || empty($this->session->id) ) throw new RestException(404, 'Session not found');

        if($model == "mission_trainer"){
            $this->trainerinsession = new Agefodd_session_formateur($this->db);
            $this->trainerinsession->fetch_formateur_per_session($sessid);
            $TFormateurs = array();
            if(!empty($this->trainerinsession->lines)){
                foreach ($this->trainerinsession->lines as $line)
                {
                    $TFormateurs[$line->formid] = $line->opsid;
                }
            }

            $socid = $TFormateurs[$socid];
        }


        $TUnCommonModels = array(
            "attestation",
            "attestationendtraining",
            "attestationpresencecollective",
            "attestationpresencetraining",
            "convocation",
            "certificateA4",
            "certificatecard",
            "courrier"
        );

        $TTraineeModels = array(
            "convocation_trainee",
            "attestation_trainee",
            "attestationendtraining_trainee"
        );

        if((in_array($model, $TUnCommonModels) || $model == "convention") && empty($socid)) throw new RestException(500, "the field socid is required for this model");
        if((in_array($model, $TTraineeModels)) && empty($socid)) throw new RestException(500, "the field socid must be the id of a trainee for the model '$model'");

        if($model == 'courrier' && empty($cour)) throw new RestException(500, "the field cour is required for model 'courrier'");

        if(!empty($socid) && !in_array($model, $TTraineeModels) && $model !== "mission_trainer")
        {
            $result = $this->session->fetch_societe_per_session($sessid);
            if( $result <= 0 ) throw new RestException(404, 'No thirdparty found');

            $TCustomers = array();
            foreach ($this->session->lines as $line)
            {
                /*if ($line->typeline == "customer")*/ $TCustomers[] = $line->socid;
            }

            if(count($TCustomers) == 0) throw  new RestException(404, "No thirdparty for this session");
            if(!in_array($socid, $TCustomers)) throw new RestException(404, "$socid is not a thirdparty of this session");

        }

        if(in_array($model, $TTraineeModels))
        {
            $this->traineeinsession = new Agefodd_session_stagiaire($this->db);
            $result = $this->traineeinsession->fetch_by_trainee($sessid, $socid);
            if($result < 0) throw new RestException(500, "Error retrieving trainee in session", array($this->db->lasterror, $this->db->lastqueryerror));
            elseif(empty($result)) throw new RestException(404, "trainee not in the session");
        }

        $idform = $this->session->fk_formation_catalogue;

        $outputlangs = $langs;
        if (! empty($langid)) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($langid);
        }

        $id_tmp = $sessid;

        if ($model == "courrier") $file = $model . '-' . $cour . '_' . $sessid . '_' . $socid . '.pdf';
        elseif(in_array($model, $TUnCommonModels)) $file = $model . '_' . $sessid . '_' . $socid . '.pdf';
        elseif(in_array($model, $TTraineeModels)) $file = $model . '_' . $this->traineeinsession->id . '.pdf';
        elseif ($model == 'convention') {

            $convention = new Agefodd_convention($this->db);
            $result = $convention->fetch($sessid, $socid);
            if($result < 0) {
                throw new RestException(500, "No convention found");
            }
            else
            {
                $id_tmp = $convention->id;dol_include_once('/agefodd/class/agefodd_convention.class.php');
                $file = 'convention' . '_' . $sessid . '_' . $socid . '_' . $convention->id . '.pdf';
            }

        }
        elseif (strpos($model, 'fiche_pedago') !== false) {
            $file = $model . '_' . $idform . '.pdf';
            $id_tmp = $idform;
            $cour = $sessid;
        } elseif (strpos($model, 'mission_trainer') !== false || strpos($model, 'contrat_trainer') !== false) {
            $sessiontrainerid = $socid;
            $file = $model . '_' . $sessiontrainerid . '.pdf';
            $socid = $sessiontrainerid;
            $id_tmp = $sessid;
        } else {
            $file = $model . '_' . $sessid . '.pdf';
        }

        // this configuration variable is designed like
        // standard_model_name:new_model_name&standard_model_name:new_model_name&....
        if (! empty($conf->global->AGF_PDF_MODEL_OVERRIDE) && ($model != 'convention')) {
            $modelarray = explode('&', $conf->global->AGF_PDF_MODEL_OVERRIDE);
            if (is_array($modelarray) && count($modelarray) > 0) {
                foreach ( $modelarray as $modeloveride ) {
                    $modeloverridearray = explode(':', $modeloveride);
                    if (is_array($modeloverridearray) && count($modeloverridearray) > 0) {
                        if ($modeloverridearray[0] == $model) {
                            $model = $modeloverridearray[1];
                        }
                    }
                }
            }
        }
        // TODO à implémenter
//         if($conf->referenceletters->enabled) {
//             if (!empty($id_external_model) || strpos($model, 'rfltr_agefodd') !== false) {
//                 $path_external_model = '/referenceletters/core/modules/referenceletters/pdf/pdf_rfltr_agefodd.modules.php';
//                 if(strpos($model, 'rfltr_agefodd') !== false) $id_external_model= (int)strtr($model, array('rfltr_agefodd_'=>''));

//                 dol_include_once('/referenceletters/class/referenceletters.class.php');
//                 $rfltr = new ReferenceLetters($this->db);

//                 $result = $rfltr->fetch($id_external_model);
//                 if($result < 0) throw new RestException(500, "Can't retrieve external model");

//                 $model = strtr($rfltr->element_type, array('rfltr_agefodd_'=>''));

//             }
//         }


        if (strpos($model, 'fiche_pedago') !== false){
            $agf = new Agsession($this->db);
            $agf->fetch($id);
            $agfTraining = new Formation($this->db);
            $agfTraining->fetch($agf->fk_formation_catalogue);
            $PDALink = $agfTraining->generatePDAByLink();
        }
        if(empty($PDALink)) {
            dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
            $result = agf_pdf_create($this->db, $id_tmp, '', $model, $outputlangs, $file, $socid, $cour, $path_external_model, $id_external_model, $convention);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => "Model $model generated"
            )
        );
    }

    /**
     * Get a list of agefodd documents
     *
     * @param int       $sessid         ID of the session
     * @param int       $socid          ID of the thirdparty (if you want to filter uncommon docs)
     * @param int       $trainerid      ID of the trainer (if you want to filter trainers docs)
     * @param int       $withcommon     Get Common docs of the session
     * @param int       $withuncommon   Get thirdparty docs of the session
     * @param int       $withtrainer    Get trainers docs of the session
     *
     * @url GET /sessions/documents
     */
    function documentsSessionList($sessid, $socid = 0, $trainerid = 0, $withcommon = 1, $withuncommon = 1, $withtrainer = 1)
    {
	    global $conf;

        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if(empty($filearray)) {
            $upload_dir = $conf->agefodd->dir_output;
            $filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$');
        }

        return $this->_documentsSessionList($sessid, $socid, $trainerid, $withcommon, $withuncommon, $withtrainer, $filearray);
    }

    function _documentsSessionList($sessid, $socid = 0, $trainerid = 0, $withcommon = 1, $withuncommon = 1, $withtrainer = 1, $filearray = array())
    {
        global $conf;

        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);

        $result = $this->session->fetch($sessid);
	    $files=array();
        if( $result < 0 || empty($this->session->id))  {
        	throw new RestException(404, 'session not found');
        } else {
	        $files = $this->session->documentsSessionList($sessid);
        }

        return $files;
    }

    /**
     * Get a list of available agefodd document models
     *
     * @url GET /documents/models
     */
    function documentsGetModels()
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $dir = dol_buildpath('/agefodd/core/modules/agefodd/pdf/');

        $filearray=dol_dir_list($dir,"files",0,'','(pdf_courrier_.*|pdf_demo.*|\.meta|_preview.*\.png)$');

        $models = array();
        if (!empty($filearray)){
            foreach ($filearray as $file) {
                $models[] = strtr($file['name'], array('pdf_' => '', '.modules.php' => ''));
            }
        }

        return $models;
    }

    /**
     * Download an agefodd file
     *
     * @param string $filename Name of the file to download
     * @param int    $entity   ID of the entity where to get the file
     *
     * @url GET /documents/download
     */
    function documentsDownload($filename, $entity = 0)
    {
        global $conf, $langs;

        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (empty($filename)) {
            throw new RestException(400, 'bad value for parameter filename');
        }

        //--- Finds and returns the document
	if (empty($entity))
        	$entity=$conf->entity;
	else if (!isset($conf->agefodd->multidir_output[$entity])) throw new RestException(500, "the entity provided has no folder defined");
        else $conf->agefodd->dir_output = $conf->agefodd->multidir_output[$entity];

        $check_access = dol_check_secure_access_document('agefodd', $filename, $entity, DolibarrApiAccess::$user, '', 'read');
        $accessallowed              = $check_access['accessallowed'];
        $sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
        $original_file              = $check_access['original_file'];

        if (preg_match('/\.\./',$original_file) || preg_match('/[<>|]/',$original_file))
        {
            throw new RestException(401);
        }
        if (!$accessallowed) {
            throw new RestException(401);
        }

        $file = basename($original_file);
        $original_file_osencoded=dol_osencode($original_file);	// New file name encoded in OS encoding charset

        if (! file_exists($original_file_osencoded))
        {
            throw new RestException(404, 'File not found');
        }

        $file_content=file_get_contents($original_file_osencoded);
        return array('filename'=>$file, 'content'=>base64_encode($file_content), 'encoding'=>'MIME base64 (base64_encode php function, http://php.net/manual/en/function.base64-encode.php)' );
    }

    /**
     * Delete an agefodd document
     *
     * @param string $filename      Name of the document
     *
     * @url DELETE /documents/
     */
    function documentDelete($filename)
    {
        global $conf;

        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (empty($filename)) {
            throw new RestException(400, 'bad value for parameter filename');
        }

        $file = $conf->agefodd->dir_output . '/' . $filename;

        if (is_file($file))
            unlink($file);
        else {
            $error = $file . ' : ' . $langs->trans("AgfDocDelError");
            throw new RestException(500, $error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Document successfully deleted'
            )
        );
    }

     /**
      * Get the generated documents of a trainee
      *
      * @param int $id              ID of the trainee
      * @param int $sessid          ID of a Session (to filter on just one session)
      * @param int $withcommon      1 to get the common files of the session or 0 to get only the trainee documents
      * @param int $withThirdpartyDocs 1 to get the documents of the trainee's thirdparty for the session
      * @param int $entity	    ID of the entity where to search for the files
      *
      * @url GET /trainees/documents
      */
     function documentsTraineeList($id, $sessid = 0, $withcommon = 1, $withThirdpartyDocs = 1, $entity = 1)
     {
         global $conf;

         $this->trainee = new Agefodd_stagiaire($this->db);
         $result = $this->trainee->fetch($id);
         if($result < 0) throw new RestException(500, "Error retrieving trainee", array($this->db->lasterror, $this->db->lastqueryerror));
         elseif(empty($result)) throw new RestException(404, "Trainee not found");

	 if (!isset($conf->agefodd->multidir_output[$entity])) throw new RestException(500, "the entity provided has no folder defined");

         $upload_dir = $conf->agefodd->multidir_output[$entity];
         $filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$');
         $files = array();

         // si sessid n'est pas vide,
         if(!empty($sessid)){
             // on vérifie si la session existe
             $this->session = new Agsession($this->db);
             $result = $this->session->fetch($sessid);
             if($result < 0 || empty($this->session->id)) throw new RestException(404, "Session $sessid not found");

            // on récupère uniquement les documents + les documents du trainee de la session
            $this->traineeinsession = new Agefodd_session_stagiaire($this->db);
            $result = $this->traineeinsession->fetch_by_trainee($sessid, $id);
            if($result < 0) throw new RestException(500, "Error retrieving trainee in session", array($this->db->lasterror, $this->db->lastqueryerror));
            elseif(empty($result)) throw new RestException(404, "Trainee not found in this session");

	    if ($withThirdpartyDocs){
            	$socid = (!empty($this->traineeinsession->fk_soc_link)) ? $this->traineeinsession->fk_soc_link : $this->trainee->socid;

           	$files[$sessid] = $this->_documentsSessionList($sessid, $socid, 0, $withcommon, 1, 0, $filearray);
            }
            if(!empty($filearray)) {
                foreach ($filearray as $f)
                {
                    $mod = substr($f['name'], 0, strrpos($f['name'], '_'));
                    if(in_array($mod, array("convocation_trainee", "attestation_trainee", "attestationendtraining_trainee")) && preg_match("/^".$mod."_([0-9]+).pdf$/", $f['name'], $i) && $i[1] == $this->traineeinsession->id) $files[$sessid][] = $f['name'];
                    if(preg_match("/^attestation_cursus_([0-9]+)_([0-9]+).pdf$/", $f['name'], $i ) && $i[2] == $id) $files[$sessid][] = $f['name'];
                }
            }
         }
         else // sinon
         {
             // on récupère la liste des sessions dans lequel il se trouve
             $result = $this->session->fetch_session_per_trainee($id);
             if($result > 0)
             {
                 // pour chaque session trouvée on récupère la liste des fichiers concernant le trainee
                 foreach ($this->session->lines as $sess)
                 {
                     $this->traineeinsession = new Agefodd_session_stagiaire($this->db);
                     $result = $this->traineeinsession->fetch_by_trainee($sess->rowid, $id);
                     if($result < 0) throw new RestException(500, "Error retrieving trainee in session", array($this->db->lasterror, $this->db->lastqueryerror));

                     $socid = (!empty($this->traineeinsession->fk_soc_link)) ? $this->traineeinsession->fk_soc_link : $this->trainee->socid;

                     $sessionfiles = $this->_documentsSessionList($sess->rowid, $socid, 0, $withcommon, 1, 0, $filearray);
                     if(!empty($sessionfiles)) $files[$sess->rowid] = $sessionfiles;

                     if(!empty($filearray)) {
                         foreach ($filearray as $f)
                         {
                             $mod = substr($f['name'], 0, strrpos($f['name'], '_'));
                             if(in_array($mod, array("convocation_trainee", "attestation_trainee", "attestationendtraining_trainee")) && preg_match("/^".$mod."_([0-9]+).pdf$/", $f['name'], $i) && $i[1] == $this->traineeinsession->id) $files[$sess->rowid][] = $f['name'];
                             if(preg_match("/^attestation_cursus_([0-9]+)_([0-9]+).pdf$/", $f['name'], $i ) && $i[2] == $id) $files['cursus_'.$i[1]] = $f['name'];
                         }
                     }
                 }
             }

         }

         if(empty($files)) $files[] = "No document generated";
         return $files;
     }

     /**
      * Get the generated agefodd documents of a trainer
      *
      * @param int $id              ID of the trainer
      * @param int $sessid          ID of a Session (to filter on just one session)
      * @param int $withcommon      1 to get the common files of the session or 0 to get only the trainer documents
      *
      * @url GET /trainers/documents
      */
     function documentsTrainerList($id, $sessid = 0, $withcommon = 0)
     {
         global $conf;

         if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
             throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
         }

         $upload_dir = $conf->agefodd->dir_output;
         $filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$');
         $files = array();

         $this->trainer = new Agefodd_teacher($this->db);
         $result = $this->trainer->fetch($id);
         if($result < 0) throw new RestException(500, "Error retrieving trainer", array($this->db->lasterror, $this->db->lastqueryerror));
         elseif(empty($result)) throw new RestException(404, "Trainer not found");

         if(!empty($sessid))// si sessid n'est pas vide
         {
             // on vérifie si la session existe
             $this->session = new Agsession($this->db);
             $result = $this->session->fetch($sessid);
             if($result < 0 || empty($this->session->id)) throw new RestException(404, "Session $sessid not found");

             //on récupère les docs de la session concernant le trainer
             $files[$sessid] = $this->_documentsSessionList($sessid, 0, $id, $withcommon, 0, 1, $filearray);
         }
         else // sinon
         {
             // on récupère la liste des sessions du trainer
             $result = $this->session->fetch_session_per_trainer($id);
             if($result < 0) throw new RestException(500, "Error retrieving trainer sessions", array($this->db->lasterror, $this->db->lastqueryerror));
             elseif (empty($result)) throw new RestException(404, "No session found for this trainer");

             // et pour chacune, on récupère les docs
             foreach ($this->session->lines as $sess)
             {
                 $sessionfiles = $this->_documentsSessionList($sess->rowid, 0, $id, $withcommon, 0, 1, $filearray);
                 if(!empty($sessionfiles)) $files[$sess->rowid] = $sessionfiles;

             }
         }

         if(empty($files)) $files[] = "No document generated";
         return $files;
     }

     /**
      * Get all generated agefodd documents for a thirdparty
      *
      * @param int  $id         ID of the thirdparty
      * @param int  $sessid     ID of a Session (to filter on just one session)
      * @param int $withcommon  1 to get the common files of the session or 0 to get only the thirdparty documents
      *
      * @url GET /thirdparties/documents
      */
     function documentsThirdpartyList($id, $sessid = 0, $withcommon = 0)
     {
         global $conf;

         if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
             throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
         }

         $upload_dir = $conf->agefodd->dir_output;
         $filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$');
         $files = array();

         if(!empty($sessid))
         {
             // on vérifie si la session existe
             $this->session = new Agsession($this->db);
             $result = $this->session->fetch($sessid);
             if($result < 0 || empty($this->session->id)) throw new RestException(404, "Session $sessid not found");

             // on récupère les doc de la session demandée
             $files[$sessid] = $this->_documentsSessionList($sessid, $id, 0, $withcommon, 1, 0, $filearray);

         }
         else
         {
             // récupère les sessions ou le tiers a une relation tiers ou trainee ou opca
             $this->session = new Agsession($this->db);
             $TSession = array();
             $filter['type_affect'] = 'thirdparty';
             $this->session->fetch_all_by_soc($id, 'ASC', 's.rowid', 0, 0, $filter);
             if(count($this->session->lines))
             {
                 foreach ($this->session->lines as $line)
                 {
                     $TSession[$line->rowid] = $line->rowid;
                 }
                 $this->session->lines = array();
             }

             $filter['type_affect'] = 'trainee';
             $this->session->fetch_all_by_soc($id, 'ASC', 's.rowid', 0, 0, $filter);
             if(count($this->session->lines))
             {
                 foreach ($this->session->lines as $line)
                 {
                     $TSession[$line->rowid] = $line->rowid;
                 }
                 $this->session->lines = array();
             }

             $filter['type_affect'] = 'opca';
             $this->session->fetch_all_by_soc($id, 'ASC', 's.rowid', 0, 0, $filter);
             if(count($this->session->lines))
             {
                 foreach ($this->session->lines as $line)
                 {
                     $TSession[$line->rowid] = $line->rowid;
                 }
                 $this->session->lines = array();
             }

             if(!empty($TSession))
             {
                 foreach ($TSession as $sessid)
                 {
                     $sessionfiles = $this->_documentsSessionList($sessid, $id, 0, $withcommon, 1, 0, $filearray);
                     if(!empty($sessionfiles)) $files[$sessid] = $sessionfiles;
                 }
             }

         }

         if(empty($files)) $files[] = "No document generated";
         return $files;
     }

    /***************************************************************** Trainee Part *****************************************************************/

    /**
     * List trainees
     *
     * Get a list of Agefodd trainees
     *
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @return array                Array of trainees objects
     *
     * @url     GET /trainees/
     * @throws RestException
     */
    function traineeIndex($sortfield = "s.rowid", $sortorder = 'DESC', $limit = 100, $page = 0) {
        global $db, $conf;

        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $obj_ret = array();

        $offset = 0;
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

        }

        $result = $this->trainee->fetch_all($sortorder, $sortfield, $limit, $offset);

        if ($result > 0)
        {
            foreach ($this->trainee->lines as $line){
                $obj_ret[] = $this->_cleanObjectDatas($line);
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve trainee list');
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No trainee found');
        }
        return $obj_ret;
    }

    /**
     * Filtered List trainees
     *
     * Get a list of Agefodd trainees
     *
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @param array     $filter     Array of filters ($k => $v)
     * @param array     $array_options_keys array of filters on extrafields
     *
     * @return array                Array of trainees objects
     *
     * @url     POST /trainees/filter
     * @throws RestException
     */
    function traineeFilteredIndex($sortfield = "s.rowid", $sortorder = 'DESC', $limit = 100, $page = 0, $filter = array(), $array_options_keys = array()) {
        global $db, $conf;

        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $obj_ret = array();

        $offset = 0;
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

        }

        $result = $this->trainee->fetch_all($sortorder, $sortfield, $limit, $offset, $filter, $array_options_keys);

        if ($result > 0)
        {
            foreach ($this->trainee->lines as $line){
                $obj_ret[] = $this->_cleanObjectDatas($line);
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve trainee list '.$sql);
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No trainee found');
        }
        return $obj_ret;
    }

    /**
     * Get properties of a trainee object
     *
     * Return an array with trainee informations
     *
     * @param 	int 	$id ID of trainee
     * @return 	array|mixed data without useless information
     *
     * @url	GET /trainees/{id}
     * @throws 	RestException
     */
    function getTrainee($id)
    {
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->trainee = new Agefodd_stagiaire($this->db);

        $result = $this->trainee->fetch($id);
        if( $result < 0 || empty($this->trainee->id)) {
            throw new RestException(404, 'trainee not found');
        }

        return $this->_cleanObjectDatas($this->trainee);
    }

    /**
     * Get sessions for a trainee object
     *
     * Return an array with session informations for the trainee
     *
     * @param 	int 	$id ID of trainee
     * @return 	array|mixed data without useless information
     *
     * @url	GET /trainees/{id}/sessions/
     * @throws 	RestException
     */
    function getTraineeSessions($id)
    {
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->session->fetch_session_per_trainee($id);
        if( $result < 0) {
            throw new RestException(404, 'trainee not found');
        } elseif (count($this->session->lines) == 0) {
            throw new RestException(404, 'no session for this trainee');
        }

        foreach ($this->session->lines as $line){
            $obj_ret[] = $this->_cleanObjectDatas($line);
        }

        return $obj_ret;
    }

    /**
     * Get informations for a trainee object
     *
     * Return an array with informations for the trainee
     *
     * @param 	int 	$id ID of trainee
     * @return 	array|mixed data without useless information
     *
     * @url	GET /trainees/{id}/infos/
     * @throws 	RestException
     */
    function getTraineeInfos($id)
    {
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->trainee->fetch($id);
        if($result>0) $this->trainee->info($id);
        if( $result < 0 || empty($this->trainee->id)) {
            throw new RestException(404, 'trainee not found');
        }

        return $this->_cleanObjectDatas($this->trainee);
    }

    /**
     * Create trainee object
     *
     * @url     POST /trainees/
     *
     * @param array     $request_data   Request data
     *
     * @return int      ID of trainee
     */
    function postTrainee($request_data)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Create not allowed for login '.DolibarrApiAccess::$user->login);
        }

        // Check mandatory fields
        $result = $this->_validate($request_data, 'trainee');

        foreach($request_data as $field => $value) {
            $this->trainee->$field = $value;
        }

        if ($this->trainee->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, 'Error when creating trainee', array_merge(array($this->trainee->error), $this->trainee->errors));
        }

        return $this->getTrainee($this->trainee->id);

    }

    /**
     * Update trainee
     *
     * @url     PUT /trainees/{id}
     *
     * @param int   $id             Id of trainee to update
     * @param array $request_data   Datas
     * @return int
     */
    function putTrainee($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->trainee->fetch($id);
        if( $result < 0 || empty($this->trainee->id) ) {
            throw new RestException(404, 'trainee not found');
        }

        foreach($request_data as $field => $value) {
            if ($field !== "array_options") {
                if($field !== 'id' && $field !== 'rowid') $this->trainee->$field = $value;
            } else {
                foreach ($value as $option => $val) $this->trainee->array_options[$option] = $val;
            }
        }

        $result = $this->trainee->update(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error while updating trainee", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getTrainee($id);
    }

    /**
     * Delete trainee
     *
     * @url	DELETE /trainees/{id}
     *
     * @param int $id   trainee ID
     * @return array
     */
    function deleteTrainee($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->trainee->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'trainee not found');
        }

        if($this->trainee->remove($id) < 0)
        {
            throw new RestException(500, 'Error while deleting trainee '.$id);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'trainee deleted'
            )
        );
    }

     /**
     * Return the list of documents attached to a trainee
     *
     * @param	int		$id				ID of trainee
     * @param	string	$sortfield		Sort criteria ('','fullname','relativename','name','date','size')
     * @param	string	$sortorder		Sort order ('asc' or 'desc')
     * @return	array					Array of documents with path
     *
     * @url GET /trainees/attachment
     */
    function getTraineeAttachment($id, $sortfield='name', $sortorder='asc')
    {
        return $this->_getDocumentsListByElement("agefodd", $id, '', 'trainee', $sortfield, $sortorder);
    }

    /**
     * Attach a file to a trainee.
     *
     * Test sample 1: { "filename": "mynewfile.txt", "id": "3061", "filecontent": "content text", "fileencoding": "", "overwriteifexists": "0" }.
     * Test sample 2: { "filename": "mynewfile.txt", "id": "3061", "filecontent": "Y29udGVudCB0ZXh0Cg==", "fileencoding": "base64", "overwriteifexists": "0" }.
     *
     * @param   string  $filename           Name of file to create ('test.txt')
     * @param   string  $id                 ID of element
     * @param   string  $filecontent        File content (string with file content. An empty file will be created if this parameter is not provided)
     * @param   string  $fileencoding       File encoding (''=no encoding, 'base64'=Base 64) {@example '' or 'base64'}
     * @param   int 	$overwriteifexists  Overwrite file if exists (0 by default)
     *
     * @throws 200
     * @throws 400
     * @throws 401
     * @throws 404
     * @throws 500
     *
     * @url POST /trainees/attach
     */
    function attachToTrainee($filename, $id, $filecontent='', $fileencoding='', $overwriteifexists=0)
    {
        return $this->_upload_file($filename, "trainee", $id, $filecontent, $fileencoding, $overwriteifexists);
    }

    /***************************************************************** Trainer Part *****************************************************************/

    /**
     * List trainers
     *
     * Get a list of Agefodd trainer
     *
     * @param string   $sortorder Sort Order
	 * @param string   $sortfield Sort field
	 * @param int      $limit offset limit
	 * @param int      $offset offset limit
	 * @param int      $arch archive
	 *
     * @return array                Array of trainers objects
     *
     * @url     GET /trainers/
     * @throws RestException
     */
    function trainerIndex($sortfield = "s.rowid", $sortorder = 'DESC', $limit = 100, $offset = 0, $arch = 0) {
        global $db, $conf;

        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $obj_ret = array();

        $result = $this->trainer->fetch_all($sortorder, $sortfield, $limit, $offset, $arch = 0);

        if ($result > 0)
        {
            foreach ($this->trainer->lines as $line){
                $obj_ret[] = $this->_cleanObjectDatas($line);
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve trainee list');
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No trainee found');
        }
        return $obj_ret;
    }

    /**
     * Filtered List trainers
     *
     * Get a list of Agefodd trainers
     *
     * @param string   $sortorder Sort Order
	 * @param string   $sortfield Sort field
	 * @param int      $limit offset limit
	 * @param int      $offset offset limit
	 * @param int      $arch archive
	 * @param array    $filter array of filter
     *
     * @return array                Array of trainers objects
     *
     * @url     POST /trainers/filter
     * @throws  RestException
     */
    function trainerFilteredIndex($sortfield = "s.rowid", $sortorder = 'DESC', $limit = 100, $offset = 0, $arch = 0, $filter = array()) {
        global $db, $conf;

        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $obj_ret = array();

        $result = $this->trainer->fetch_all($sortorder, $sortfield, $limit, $offset, $arch = 0, $filter);

        if ($result > 0)
        {
            foreach ($this->trainer->lines as $line){
                $obj_ret[] = $this->_cleanObjectDatas($line);
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve trainee list '.$sql);
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No trainee found');
        }
        return $obj_ret;
    }

    /**
     * Get properties of a trainer object
     *
     * Return an array with trainer informations
     *
     * @param 	int 	$id ID of trainer
     * @return 	array|mixed data without useless information
     *
     * @url	GET /trainers/{id}
     * @throws 	RestException
     */
    function getTrainer($id)
    {
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->trainer = new Agefodd_teacher($this->db);

        $result = $this->trainer->fetch($id);
        if( $result < 0 || empty($this->trainer->id)) {
            throw new RestException(404, 'trainer not found');
        }

        return $this->_cleanObjectDatas($this->trainer);
    }

    /**
     * Get sessions for a trainer object
     *
     * Return an array with session informations for the trainer
     *
     * @param 	int 	$id ID of trainer
     * @param   string  $sortorder order
	 * @param   string  $sortfield field
	 * @param   int     $limit page
	 * @param   int     $offset
	 * @param   array   $filter
     *
     * @return 	array|mixed data without useless information
     *
     * @url	POST /trainers/{id}/sessions/
     * @throws 	RestException
     */
    function getTrainerSessions($id, $sortorder = 'DESC', $sortfield = 's.dated', $limit = 0, $offset = 0, $filter = array())
    {
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->session->fetch_session_per_trainer($id, $sortorder, $sortfield, $limit, $offset, $filter);
        if( $result < 0) {
            throw new RestException(404, 'trainer not found');
        } elseif (count($this->session->lines) == 0) {
            throw new RestException(404, 'no session for this trainer');
        }

        foreach ($this->session->lines as $line){
            $obj_ret[] = $this->_cleanObjectDatas($line);
        }

        return $obj_ret;
    }

    /**
     * Get informations for a trainer object
     *
     * Return an array with informations for the trainer
     *
     * @param 	int 	$id ID of trainer
     * @return 	array|mixed data without useless information
     *
     * @url	GET /trainers/{id}/infos/
     * @throws 	RestException
     */
    function getTrainerInfos($id)
    {
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->trainer->fetch($id);
        if($result>0) $this->trainer->info($id);
        if( $result < 0 || empty($this->trainer->id)) {
            throw new RestException(404, 'trainer not found');
        }

        return $this->_cleanObjectDatas($this->trainer);
    }

    /**
     * Get categories of trainers
     *
     * return array of categories of trainers
     *
     * @return array data without useless information
     *
     * @url GET /trainers/categories
     * @throws RestException
     */
    function getTrainerCategories()
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->trainer->fetchAllCategories();
        if( empty($result)) throw new RestException(404, 'no categories found');
        elseif ($result < 0) throw new RestException(500, 'Error while retrieving categories');

        return $this->trainer->dict_categories;
    }

    /**
     * Set categories of a trainers
     *
     * return informations trainers
     * @param int   $id         ID of a trainer
     * @param array $categories Array of categories id to apply
     *
     * @return array data without useless information
     *
     * @url POST /trainers/categories
     * @throws RestException
     */
    function setTrainerCategories($id, $categories = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $TCats = $categories;
        $categories = array();

        $result = $this->trainer->fetch($id);
        if( $result < 0 || empty($this->trainer->id)) {
            throw new RestException(404, 'trainer not found');
        }

        if(!empty($TCats)){
            $result = $this->trainer->fetchAllCategories();
            if( empty($result)) throw new RestException(404, 'no categories found');
            elseif ($result < 0) throw new RestException(500, 'Error while retrieving categories');

            // create array to validate the array provided
            $TCatIds = array();
            foreach ($this->trainer->dict_categories as $cat){
                $TCatIds[] = $cat->dictid;
            }

            foreach ($TCats as $c){
                if(in_array($c, $TCatIds)) $categories[] = $c;
            }
        }

        $result = $this->trainer->setTrainerCat($categories, DolibarrApiAccess::$user);
        if ($result < 0) throw new RestException(500, 'Error while setting categories : '. $this->trainer->error);

        $this->trainer->dict_categories = array();
        return $this->getTrainer($this->trainer->id);

        //return $this->trainer->dict_categories;
    }

    /**
     * Get the calendar of a trainer
     *
     * @param int       $trainerid    ID of the trainer (same trainer ID used to add the trainer in session)
     *
     * @url GET /trainers/calendar
     */
    function trainerGetCalendar($trainerid)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->trainer = new Agefodd_teacher($this->db);
        $result = $this->trainer->fetch($trainerid);
        if($result < 0) throw new RestException(500, "Error retrieving trainer", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->trainer->id)) throw new RestException(404, "trainer not found");

        $this->trainerinsessioncalendar = new Agefoddsessionformateurcalendrier($this->db);
        $result = $this->trainerinsessioncalendar->fetch_all_by_trainer($trainerid);
        if($result < 0) throw new RestException(500, "Error retrieving calendar", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->trainerinsessioncalendar->lines)) throw new RestException(404, "No calendar found for this trainer in the session");

        $obj_ret = array();
        foreach ($this->trainerinsessioncalendar->lines as $line)
        {
            $obj = new stdClass();
            $obj->id = $line->id;
            $obj->date_session = date("Y-m-d", $line->date_session);
            $obj->heured = date("Y-m-d H:i:s", $line->heured);
            $obj->heuref = date("Y-m-d H:i:s", $line->heuref);
            $obj->fk_session = $line->fk_session;
            $obj->status_in_session = $line->trainer_status_in_session;

            $obj_ret[] = $obj;
        }

        return $obj_ret;
    }

    /**
     * Create trainer object
     *
     * @url     POST /trainers/
     *
     * @param int       $id     id of the source (contact or user)
     * @param string    $mode   fromContact or fromUser
     *
     *
     * @return int      ID of trainee
     */
    function postTrainer($id = 0, $mode = 'fromContact')
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Creation not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if ($id <= 0) throw new RestException(404, 'no valid id provided');
        switch ($mode) {
            case 'fromContact' :
                dol_include_once('/contact/class/contact.class.php');
                $c = new Contact($this->db);
                $result = $c->fetch($id);
                if($result <= 0) throw new RestException(404, "Contact $id not found");
                else
                {
                    $this->trainer->spid = $id;
                    $this->trainer->type_trainer = $this->trainer->type_trainer_def[1];
                }
                break;

            case 'fromUser' :
                dol_include_once('/user/class/user.class.php');
                $u = new User($this->db);
                $result = $u->fetch($id);
                if($result <= 0) throw new RestException(404, "User $id not found");
                else
                {
                    $this->trainer->fk_user = $id;
                    $this->trainer->type_trainer = $this->trainer->type_trainer_def[0];
                }
                break;

            Default :
                throw new RestException(500, "invalid mode $mode. It must be 'fromContact' or 'fromUser'");
        }

        // Check mandatory fields
        //$result = $this->_validate($request_data, 'trainer');

        if ($this->trainer->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, 'Error when creating trainer', array_merge(array($this->trainee->error), $this->trainee->errors));
        }

        return $this->getTrainer($this->trainer->id);
    }

    /**
     * Archive/Activate a trainer
     *
     * @url     PUT /trainers/archive
     *
     * @param int   $id             Id of trainer to update
     *
     * @return int
     */
    function archiveTrainer($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->trainer->fetch($id);
        if( $result < 0 || empty($this->trainer->id) ) {
            throw new RestException(404, 'trainer not found');
        }

        $this->trainer->archive = (empty($this->trainer->archive)) ? 1 : 0;
        if($this->trainer->update(DolibarrApiAccess::$user))
            return $this->getTrainer($id);

            return false;
    }

    /**
     * Delete trainer
     *
     * @url	DELETE /trainers/{id}
     *
     * @param int $id   trainer ID
     * @return array
     */
    function deleteTrainer($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->trainer->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'trainer not found');
        }

        if($this->trainer->remove($id) < 0)
        {
            throw new RestException(500, 'Error while deleting trainer '.$id);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'trainer deleted'
            )
        );
    }

    /**
     * Return the list of documents attached to a trainer
     *
     * @param	int		$id				ID of trainer (not the trainerinsession id)
     * @param	string	$sortfield		Sort criteria ('','fullname','relativename','name','date','size')
     * @param	string	$sortorder		Sort order ('asc' or 'desc')
     * @return	array					Array of documents with path
     *
     * @url GET /trainer/attachment
     */
    function getTrainerAttachments($id, $sortfield='name', $sortorder='asc')
    {
        return $this->_getDocumentsListByElement("agefodd", $id, '', 'trainer', $sortfield, $sortorder);
    }

    /**
     * Attach a file to a trainer.
     *
     * Test sample 1: { "filename": "mynewfile.txt", "id": "1", "filecontent": "content text", "fileencoding": "", "overwriteifexists": "0" }.
     * Test sample 2: { "filename": "mynewfile.txt", "id": "1", "filecontent": "Y29udGVudCB0ZXh0Cg==", "fileencoding": "base64", "overwriteifexists": "0" }.
     *
     * @param   string  $filename           Name of file to create ('test.txt')
     * @param   string  $id                 ID of element
     * @param   string  $filecontent        File content (string with file content. An empty file will be created if this parameter is not provided)
     * @param   string  $fileencoding       File encoding (''=no encoding, 'base64'=Base 64) {@example '' or 'base64'}
     * @param   int 	$overwriteifexists  Overwrite file if exists (0 by default)
     *
     * @throws 200
     * @throws 400
     * @throws 401
     * @throws 404
     * @throws 500
     *
     * @url POST /trainer/attach
     */
    function attachToTrainer($filename, $id, $filecontent='', $fileencoding='', $overwriteifexists=0)
    {
        return $this->_upload_file($filename, "trainer", $id, $filecontent, $fileencoding, $overwriteifexists);
    }

    /***************************************************************** Formation Part *****************************************************************/

    /**
     * List Training
     *
     * Get a list of Agefodd Training
     *
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @return array                Array of formation objects
     *
     * @url     GET /trainings/
     * @throws RestException
     */
    function trainingIndex($sortfield = "c.rowid", $sortorder = 'DESC', $limit = 100, $page = 0) {
        global $db, $conf;

        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->trainig = new Formation($this->db);
        $obj_ret = array();

        $offset = 0;
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

        }

        $result = $this->training->fetch_all($sortorder, $sortfield, $limit, $offset);

        if ($result > 0)
        {
            foreach ($this->training->lines as $line){
                $obj_ret[] = $this->_cleanObjectDatas($line);
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve training list ', array_merge(array($this->training->error, $this->db->lastqueryerror), $this->training->errors));
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No training found');
        }
        return $obj_ret;
    }

    /**
     * Filtered List of Trainings
     *
     * Get a list of Agefodd Training
     *
     * @param string	$sortfield	        Sort field
     * @param string	$sortorder	        Sort order
     * @param int		$limit		        Limit for list
     * @param int		$page		        Page number
     * @param int       $arch               archived training (0 or 1)
     * @param array		$filter		        array of filters ($k => $v)
     * @param array     $array_options_keys array of filters on extrafields
     *
     * @return array                Array of training objects
     *
     * @url     POST /trainings/filter
     * @throws RestException
     */
    function trainingFilteredIndex($sortfield = "c.rowid", $sortorder = 'DESC', $limit = 100, $page = 0, $arch = 0, $filter = array(), $array_options_keys=array()) {
        global $db, $conf;

        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);
        $obj_ret = array();

        $offset = 0;
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

        }

        $result = $this->training->fetch_all($sortorder, $sortfield, $limit, $offset, $arch, $filter, $array_options_keys);

        if ($result > 0)
        {
            foreach ($this->training->lines as $line){
                $obj_ret[] = $this->_cleanObjectDatas($line);
            }
        }
        elseif(empty($result)) {
            throw new RestException(404, "No training found");
        }
        else {
            throw new RestException(503, 'Error when retrieve training list ', array_merge(array($this->training->error, $this->db->lastqueryerror), $this->training->errors));
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No training found');
        }
        return $obj_ret;
    }

    /**
     * Get properties of a training object
     *
     * Return an array with training informations
     *
     * @param 	int 	$id ID of training
     * @return 	array|mixed data without useless information
     *
     * @url	GET /trainings/{id}
     * @throws 	RestException
     */
    function getTraining($id)
    {
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);

        $result = $this->training->fetch($id);
        if( $result < 0 || empty($this->training->id)) {
            throw new RestException(404, 'training not found');
        }

        return $this->_cleanObjectDatas($this->training);
    }

    /**
     * Get informations for a training object
     *
     * Return an array with informations for the training
     *
     * @param 	int 	$id ID of training
     * @return 	array|mixed data without useless information
     *
     * @url	GET /trainings/{id}/infos/
     * @throws 	RestException
     */
    function getTrainingInfos($id)
    {
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->training->fetch($id);
        if($result>0) $this->training->info($id);
        if( $result < 0 || empty($this->training->id)) {
            throw new RestException(404, 'training not found');
        }

        return $this->_cleanObjectDatas($this->training);
    }

    /**
     * Get all sessions for the training
     *
     * Return an array of sessions
     *
     * @param 	int 	$id ID of training
     * @return 	array|mixed data without useless information
     *
     * @url POST /trainings/{id}/sessions/
     * throws RestException
     *
     */
    function getTrainingSessions($id, $sortorder = 'DESC', $sortfield = 's.dated', $limit = 0, $offset = 0, $filter = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);
        $result = $this->training->fetch($id);
        if( $result < 0 || empty($this->training->id)) {
            throw new RestException(404, 'training not found');
        }

        $filter['s.fk_formation_catalogue'] = $this->training->id;

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch_all($sortorder, $sortfield, $limit, $offset, $filter);

        if(empty($result)) throw new RestException(404, "No session found for this training");
        elseif($result < 0) throw new RestException(500, "Error while retrieving sessions", array($this->db->lasterror,$this->db->lastqueryerror));

        $obj_ret = array();
        foreach ($this->session->lines as $line) $obj_ret[] = $this->_cleanObjectDatas($line);

        return $obj_ret;
    }

    /**
     * Create Training object
     *
     * @url     POST /trainings/
     *
     * @param string    $mode           create, clone or createadm (create admin tasks)
     * @param array     $request_data   Request data
     *
     * @return 	array|mixed data without useless information
     */
    function postTraining($mode = 'create', $request_data)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->creer) {
            throw new RestException(401, 'Creation not allowed for login '.DolibarrApiAccess::$user->login);
        }

        switch ($mode)
        {
            case 'create' :
                return $this->_createTraining($request_data['request_data']);
                break;

            case 'clone' :
                if(!in_array('id', array_keys($request_data['request_data']))) throw new RestException(404, "No source id provided");
                return $this->_cloneTraining($request_data['request_data']['id']);
                break;

            case 'createadm' :
                if(!in_array('id', array_keys($request_data['request_data']))) throw new RestException(404, "No training id provided");
                return $this->_createTrainingAdm($request_data['request_data']['id']);
                break;

            Default :
                throw new RestException(404, "Invalid mode provided");
        }

    }

    /**
     * Create Training object
     *
     * @param array $request_data
     *
     * @return array|mixed data without useless information
     */
    function _createTraining($request_data)
    {
        // Check mandatory fields
        $result = $this->_validate($request_data, 'training');

        $this->training = new Formation($this->db);

        foreach($request_data as $field => $value) {
            $this->training->$field = $value;
        }

        if(!isset($request_data['ref']) || empty($request_data['ref'])){
            $obj = empty($conf->global->AGF_ADDON) ? 'mod_agefodd_simple' : $conf->global->AGF_ADDON;

            $path_rel = dol_buildpath('/agefodd/core/modules/agefodd/' . $obj . '.php');
            if (is_readable($path_rel)) {
                dol_include_once('/agefodd/core/modules/agefodd/' . $obj . '.php');
                $modAgefodd = new $obj();
                $defaultref = $modAgefodd->getNextValue($soc, $agf);
            }
            $this->training->ref_obj = $defaultref;
        }

        $result = $this->training->create(DolibarrApiAccess::$user);

        if ($result < 0) {
            throw new RestException(500, 'Error when creating training', array_merge(array($this->db->lasterror, $this->db->lastqueryerror), $this->session->errors));
        }
        return $this->getTraining($result);
    }

    /**
     * Clone Training object
     *
     * @param int $id ID of the training to clone
     *
     * @return 	array|mixed data without useless information
     */
    function _cloneTraining($id)
    {
        if(empty($id) || !is_numeric($id)) throw new RestException(503, "Invalid id provided");

        $this->training = new Formation($this->db);
        $result = $this->training->fetch($id);
        if($result < 0) throw new RestException(500, "Error while retrieving training $id", array($this->db->lasterror, $this->db->lastqueryerror));
        if(empty($this->training->id)) throw new RestException(404, 'training not found');

        $result = $this->training->createFromClone($id);
        if($result<0) throw new RestException(500, 'Training not created', array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getTraining($result);
    }

    /**
     * Create Admin task
     *
     * @param int $id ID of the training
     *
     * @return 	array|mixed data without useless information
     */
    function _createTrainingAdm($id)
    {
        if(empty($id) || !is_numeric($id)) throw new RestException(503, "Invalid id provided");

        $this->training = new Formation($this->db);
        $result = $this->training->fetch($id);
        if($result < 0) throw new RestException(500, "Error while retrieving training $id", array($this->db->lasterror, $this->db->lastqueryerror));
        if(empty($this->training->id)) throw new RestException(404, 'training not found');

        $agf_adminlevel = new Agefodd_training_admlevel($this->db);
        $agf_adminlevel->fk_training = $id;
        $result = $agf_adminlevel->delete_training_task($user);
        if($result < 0) throw new RestException(500, "Error delete adm for training $id", array($this->db->lasterror, $this->db->lastqueryerror));

        $result = $this->training->createAdmLevelForTraining(DolibarrApiAccess::$user);
        if(!empty($result)) throw new RestException(503, "Error in Adm creation", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => "Admin tasks created for training $id"
            )
        );
    }

    /**
     * Update Training
     *
     * @url     PUT /trainings/{id}
     *
     * @param int   $id             Id of training to update
     * @param array $request_data   Datas
     * @return 	array|mixed data without useless information
     */
    function putTraining($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);
        $result = $this->training->fetch($id);
        if($result < 0) throw new RestException(500, "Error while retrieving training $id", array($this->db->lasterror, $this->db->lastqueryerror));
        if(empty($this->training->id)) throw new RestException(404, 'training not found');

        foreach (Agefodd::$FIELDS['training']['mandatoryFields'] as $field){
            if (!isset($request_data[$field]) || empty($request_data[$field]) || $request_data[$field] == -1) $request_data[$field] = $this->training->$field;
        }

        foreach($request_data as $field => $value) {
            if ($field !== "array_options") {
                if($field !== 'id' && $field !== 'rowid') $this->training->$field = $value;
            } else {
                foreach ($value as $option => $val) $this->training->array_options[$option] = $val;
            }
        }

        if($this->training->update(DolibarrApiAccess::$user) < 0) throw new RestException(500, "Error while updating session", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getTraining($id);
    }

    /**
     * Delete training
     *
     * @url	DELETE /trainings/{id}
     *
     * @param int $id   training ID
     * @return array
     */
    function deleteTraining($id)
    {
        //if( ! DolibarrApi::_checkAccessToResource('agefodd_session',$this->session->id)) {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->training->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'training not found');
        }

        if($this->training->remove($id) < 0)
        {
            throw new RestException(500, 'error while deleting training '.$id);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'training deleted'
            )
        );
    }

    /**
     * Get all trainers for a training
     *
     * @url GET /trainings/gettrainers/{id}
     *
     * @param int $id       ID of a Training
     *
     * @return array|mixed without useless informations
     * @throws RestException
     */
    function trainingGetTrainers($id){
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);
        $result = $this->training->fetch($id);
        if($result < 0) throw new RestException(500, "Error while retrieving training $id", array($this->db->lasterror, $this->db->lastqueryerror));
        if(empty($this->training->id)) throw new RestException(404, 'training not found');

        $result = $this->training->fetchTrainer();
        if(empty($result)) throw new RestException(404, "No trainer found for training $id");
        elseif($result < 0) throw new RestException(500, "Error while retrieving trainers", array($this->db->lasterror, $this->db->lastqueryerror));

        $obj_ret = array();
        foreach ($this->training->trainers as $trainer) $obj_ret[] = $this->_cleanObjectDatas($trainer);

        return $obj_ret;
    }


    /**
     * Set trainers list for the training
     *
     * return trainers list for the training
     *
     * @param int       $id         ID of a Training
     * @param array     $trainers   Array of id of trainers for this training
     *
     * @return array data without useless information
     *
     * @url POST /trainings/settrainers/
     * @throws RestException
     */
    function trainingSetTrainers($id, $trainers = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);
        $result = $this->training->fetch($id);
        if($result < 0) throw new RestException(500, "Error while retrieving training $id", array($this->db->lasterror, $this->db->lastqueryerror));
        if(empty($this->training->id)) throw new RestException(404, 'training not found');

        $Ttrainers = $trainers;
        $trainers = array();

        // avoid to add non-existing trainers
        foreach ($Ttrainers as $t){
            $this->trainer = new Agefodd_teacher($this->db);
            $res = $this->trainer->fetch($t);
            if($res > 0) $trainers[] = $t;
        }

        $result = $this->training->setTrainingTrainer($trainers, DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error during update", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->trainingGetTrainers($id);
    }

    /**
     * Add a trainer for the training
     *
     * return trainers list for the training
     *
     * @param int       $id         ID of a Training
     * @param int       $trainerid  ID of the trainer to add
     *
     * @return array data without useless information
     *
     * @url POST /trainings/addtrainer/
     * @throws RestException
     */
    function trainingAddTrainer($id, $trainerid)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);
        $result = $this->training->fetch($id);
        if($result < 0) throw new RestException(500, "Error while retrieving training $id", array($this->db->lasterror, $this->db->lastqueryerror));
        if(empty($this->training->id)) throw new RestException(404, 'training not found');

        $this->trainer = new Agefodd_teacher($this->db);
        $res = $this->trainer->fetch($trainerid);
        if(empty($res)) throw new RestException(404, "This trainer does not exist");
        elseif($res < 0) throw new RestException(404, "Invalid trainer id provided");

        $this->training->fetchTrainer();
        $Ttrainers = array();
        foreach ($this->training->trainers as $trainer)
        {
            if($trainer->id == $trainerid) throw new RestException(503, "Trainer already in the list");
            $Ttrainers[] = $trainer->id;
        }
        $Ttrainers[] = $trainerid;

        $result = $this->training->setTrainingTrainer($Ttrainers, DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error during update", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->trainingGetTrainers($id);
    }

    /**
     * Remove a trainer for the training
     *
     * return trainers list for the training
     *
     * @param int       $id         ID of a Training
     * @param int       $trainerid  ID of the trainer to remove
     *
     * @return array data without useless information
     *
     * @url DELETE /trainings/removetrainer/
     * @throws RestException
     */
    function trainingRemoveTrainer($id, $trainerid)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);
        $result = $this->training->fetch($id);
        if($result < 0) throw new RestException(500, "Error while retrieving training $id", array($this->db->lasterror, $this->db->lastqueryerror));
        if(empty($this->training->id)) throw new RestException(404, 'training not found');

        $this->training->fetchTrainer();
        $Ttrainers = array();
        $found = false;
        foreach ($this->training->trainers as $trainer)
        {
            if((int)$trainer->id !== $trainerid) $Ttrainers[] = $trainer->id;
            else $found = true;
        }
        if(!$found) throw new RestException(404, "Trainer $trainerid is not in the list for this training");


        $result = $this->training->setTrainingTrainer($Ttrainers, DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error during update", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => "trainer $trainerid removed from training $id"
            )
        );
    }

    /**
     * Generate pdf program of a training by link
     *
     * @param int   $id     ID of the training
     *
     * @return array data without useless information
     *
     * @url POST /trainings/generatepdf
     * @throws RestException
     */
    function trainingGeneratePDF($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);
        $result = $this->training->fetch($id);
        if($result < 0) throw new RestException(500, "Error while retrieving training $id", array($this->db->lasterror, $this->db->lastqueryerror));
        if(empty($this->training->id)) throw new RestException(404, 'training not found');

        $result = $this->training->generatePDAByLink();
        if(empty($result)) throw new RestException(404, "Link PRG not found");

        return array(
            'success' => array(
                'code' => 200,
                'message' => "document generated"
            )
        );
    }

    /**
     * Get the list of goals for the training
     *
     * @param int   $id     ID of the training
     *
     * @return array data without useless information
     *
     * @url GET /trainings/goals/
     * @throws RestException
     */
    function trainingGetAllGoals($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);
        $result = $this->training->fetch($id);
        if($result < 0) throw new RestException(500, "Error while retrieving training $id", array($this->db->lasterror, $this->db->lastqueryerror));
        if(empty($this->training->id)) throw new RestException(404, 'training not found');

        $result = $this->training->fetch_objpeda_per_formation($id);
        if(empty($result)) throw new RestException(404, "no goals found for this training");
        elseif($result < 0) throw new RestException(503, "Error while retrieving goals", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->training->lines;
    }

    /**
     * Add a goal to the training
     *
     * @param int       $id         ID of the training
     * @param array     $request    data to create the goal
     *
     * @return array data without useless information
     *
     * @url POST /trainings/addgoal/
     * @throws RestException
     */
    function trainingAddGoal($id, $request = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);
        $result = $this->training->fetch($id);
        if($result < 0) throw new RestException(500, "Error while retrieving training $id", array($this->db->lasterror, $this->db->lastqueryerror));
        if(empty($this->training->id)) throw new RestException(404, 'training not found');

        $result = $this->training->fetch_objpeda_per_formation($id);
        $request['priorite'] = 0;
        if($result < 0) throw new RestException(503, "Error while retrieving existing goals", array($this->db->lasterror, $this->db->lastqueryerror));
        else {
            foreach ($this->training->lines as $goal){
                if($goal->priorite > $request['priorite']) $request['priorite'] = $goal->priorite;
            }
            $request['priorite']++;
        }

        $request['fk_formation_catalogue'] = $id;
        $result = $this->_validate($request, 'objpeda');

        foreach($request as $field => $value) {
            $this->training->$field = $value;
        }

        $result = $this->training->create_objpeda(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(503, "Error in goal creation", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->trainingGetallGoals($id);
    }

    /**
     * Update a training goal
     *
     * @param int       $id         ID of the training
     * @param array     $request    data to update the goal
     *
     * @return array data without useless information
     *
     * @url PUT /trainings/goal/{id}
     * @throws RestException
     */
    function trainingPutGoal($id, $request = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);
        $result = $this->training->fetch_objpeda($id);
        if(empty($result)) throw new RestException(404, "Goal not found");
        elseif($result < 0) throw new RestException(503, "Error fetch", array($this->db->lasterror, $this->db->lastqueryerror));

        $TChamps = array('intitule', 'fk_formation_catalogue', 'priorite');

        foreach ($TChamps as $champ)
        {
            if(!isset($request[$champ]) || empty($request[$champ]))
            {
                $request[$champ] = $this->training->$champ;
            }
        }

        foreach($request as $field => $value) {
            if($field !== 'id' && $field !== 'rowid') $this->training->$field = $value;
        }

        $result = $this->training->update_objpeda(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(503, "Error in update", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->trainingGetGoal($id);
    }

    /**
     * Get one training goals
     *
     * @param int $id ID of the goal to retrieve
     *
     * @url GET /trainings/getgoal/
     *
     * @return array data without useless information
     * @throws RestException
     */
    function trainingGetGoal($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);

        $result = $this->training->fetch_objpeda($id);
        if(empty($result)) throw new RestException(404, "Goal not found");
        elseif($result < 0) throw new RestException(503, "Error fetch", array($this->db->lasterror, $this->db->lastqueryerror));

        $obj = new stdClass();
        $obj->id = $this->training->id;
        $obj->fk_formation_catalogue = $this->training->fk_formation_catalogue;
        $obj->intitule = $this->training->intitule;
        $obj->priorite = $this->training->priorite;

        return $obj;
    }

    /**
     * Remove a goal
     *
     * @param int       $id     ID of the goal to delete
     *
     * @return array data without useless information
     *
     * @url DELETE /trainings/removegoal/
     * @throws RestException
     */
    function trainingRemoveGoal($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);

        $result = $this->training->fetch_objpeda($id);
        if(empty($result)) throw new RestException(404, "Goal not found");
        elseif($result < 0) throw new RestException(503, "Error fetch", array($this->db->lasterror, $this->db->lastqueryerror));

        $result = $this->training->remove_objpeda($id);
        if($result < 0) throw new RestException(503, "Error delete", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'training goal deleted'
            )
        );
    }

    /**
     * Get Admin tasks for a training
     *
     * @param int $id ID of the training
     *
     * @return array data without useless information
     *
     * @url GET /trainings/admintasks/
     * @throws RestException
     */
    function trainingGetAllAdm($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->training = new Formation($this->db);
        $result = $this->training->fetch($id);
        if($result < 0) throw new RestException(500, "Error while retrieving training $id", array($this->db->lasterror, $this->db->lastqueryerror));
        if(empty($this->training->id)) throw new RestException(404, 'training not found');

        $admlevel = new Agefodd_training_admlevel($this->db);
        $result = $admlevel->fetch_all($id);
        if(empty($result)) throw new RestException(404, "No admin tasks found");
        elseif($result < 0) throw new RestException(500, "Error while retrieving admin tasks", array($this->db->lasterror, $this->db->lastqueryerror));

        return $admlevel->lines;
    }

    /**
     *  Get an admin task
     *
     *  @param int $id  the admin task rowid
     *
     *  @return array data without useless information
     *
     * @url GET /trainings/getadmintask/{id}
     * @throws RestException
     */
    function trainingGetAdm($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $agf = new Agefodd_training_admlevel($this->db);

        $result = $agf->fetch($id);
        if(empty($agf->id)) throw new RestException(404, "Admin task not found");
        elseif ($result < 0) throw new RestException(500, "Error while retrieving admin task", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->_cleanObjectDatas($agf);
    }

    /**
     * Update an admin task
     *
     * @param int       $id                 the admin task rowid
     * @param string    $intitule           data to update the task
     * @param int       $delais_alerte      number of days to alert before event
     * @param int       $delais_alerte_end  number of days to alert after event
     * @param int       $parent_level       ID of parent task
     *
     * @return array data without useless information
     *
     * @url PUT /trainings/admintasks/{id}
     * @throws RestException
     */
    function trainingPutAdm($id, $intitule, $delais_alerte = 0, $delais_alerte_end = 0, $parent_level = 0)
    {
        global $langs;

        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $agf = new Agefodd_training_admlevel($this->db);

        $result = $agf->fetch($id);
        if(empty($agf->id)) throw new RestException(404, "Admin task not found");
        elseif ($result < 0) throw new RestException(500, "Error while retrieving admin task", array($this->db->lasterror, $this->db->lastqueryerror));

        $agf->intitule = $intitule;
        if($delais_alerte !== $agf->delais_alerte) $agf->delais_alerte = $delais_alerte;
        if($delais_alerte_end !== $agf->delais_alerte_end) $agf->delais_alerte_end = $delais_alerte_end;
        if (! empty($parent_level)) {
            $admlevel = new Agefodd_training_admlevel($this->db);
            $result = $admlevel->fetch_all($id);
            if(empty($result)) throw new RestException(404, "No admin tasks found");
            elseif($result < 0) throw new RestException(500, "Error while retrieving admin tasks", array($this->db->lasterror, $this->db->lastqueryerror));

            $TParentAdms = array();
            foreach ($admlevel->lines as $trainingAdm)
            {
                $TParentAdms[] = $trainingAdm->rowid;
            }

            if (!in_array($parent_level, $TParentAdms)) throw new RestException(503, "The parent_level is not an Admin task from this training");

            if ($parent_level != $agf->fk_parent_level) {
                $agf->fk_parent_level = $parent_level;

                $agf_static = new Agefodd_training_admlevel($this->db);
                $result_stat = $agf_static->fetch($agf->fk_parent_level);

                if ($result_stat > 0) {
                    if (! empty($agf_static->id)) {
                        $agf->level_rank = $agf_static->level_rank + 1;
                        $agf->indice = ebi_get_adm_training_get_next_indice_action($agf_static->id);
                    } else { // no parent : This case may not occur but we never know
                        $agf->indice = (ebi_get_adm_training_level_number() + 1) . '00';
                        $agf->level_rank = 0;
                    }
                } else {
                    throw new RestException(500, $agf_static->error);
                }
            }
        } else {
            // no parent
            $agf->fk_parent_level = 0;
            $agf->level_rank = 0;
        }

        if ($agf->level_rank > 3) {
            throw new RestException(500, $langs->trans("AgfAdminNoMoreThan3Level"));
        } else {
            $result1 = $agf->update(DolibarrApiAccess::$user);
            if ($result1 != 1) {
                throw new RestException(500, $agf_static->error, array($this->db->lasterror, $this->db->lastqueryerror));
            }
        }

        return $this->trainingGetAdm($id);
    }

    /**
     *  Get an admin task
     *
     *  @param int $id  the admin task rowid
     *
     *  @return array data without useless information
     *
     * @url DELETE /trainings/removeadmintask/{id}
     * @throws RestException
     */
    function trainingRemoveAdm($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $agf = new Agefodd_training_admlevel($this->db);

        $result = $agf->fetch($id);
        if(empty($agf->id)) throw new RestException(404, "Admin task not found");
        elseif ($result < 0) throw new RestException(500, "Error while retrieving admin task", array($this->db->lasterror, $this->db->lastqueryerror));

        $result = $agf->delete(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(503, "Error delete", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Admin task deleted'
            )
        );
    }

    /**
     * Return the list of documents attached to a training
     *
     * @param	int		$id				ID of trainer (not the trainerinsession id)
     * @param	string	$sortfield		Sort criteria ('','fullname','relativename','name','date','size')
     * @param	string	$sortorder		Sort order ('asc' or 'desc')
     * @return	array					Array of documents with path
     *
     * @url GET /trainings/attachment
     */
    function getTrainingAttachments($id, $sortfield='name', $sortorder='asc')
    {
        return $this->_getDocumentsListByElement("agefodd", $id, '', 'training', $sortfield, $sortorder);
    }

    /**
     * Attach a file to a training.
     *
     * Test sample 1: { "filename": "mynewfile.txt", "id": "4848", "filecontent": "content text", "fileencoding": "", "overwriteifexists": "0" }.
     * Test sample 2: { "filename": "mynewfile.txt", "id": "4848", "filecontent": "Y29udGVudCB0ZXh0Cg==", "fileencoding": "base64", "overwriteifexists": "0" }.
     *
     * @param   string  $filename           Name of file to create ('test.txt')
     * @param   string  $id                 ID of element
     * @param   string  $filecontent        File content (string with file content. An empty file will be created if this parameter is not provided)
     * @param   string  $fileencoding       File encoding (''=no encoding, 'base64'=Base 64) {@example '' or 'base64'}
     * @param   int 	$overwriteifexists  Overwrite file if exists (0 by default)
     *
     * @throws 200
     * @throws 400
     * @throws 401
     * @throws 404
     * @throws 500
     *
     * @url POST /trainings/attach
     */
    function attachToTraining($filename, $id, $filecontent='', $fileencoding='', $overwriteifexists=0)
    {
        return $this->_upload_file($filename, "training", $id, $filecontent, $fileencoding, $overwriteifexists);
    }

    /***************************************************************** Formation Module Part *****************************************************************/

    /**
     * Get a list of training modules
     *
     * @param string    $sortorder
     * @param string    $sortfield
     * @param int       $limit
     * @param int       $offset
     * @param array     $filter
     *
     * @url POST /trainingmodules/filter/
     */
    function trainingModuleIndex($sortorder = 'DESC', $sortfield = 't.rowid', $limit = 100, $offset = 0, $filter = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->trainingmodules = new Agefoddformationcataloguemodules($this->db);
        $result = $this->trainingmodules->fetchAll($sortorder, $sortfield, $limit, $offset, $filter);
        if($result < 0) throw new RestException(500, "Error retrieving modules", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($result)) throw new RestException(404, "No training module found");

        $obj_ret = array();
        foreach ($this->trainingmodules->lines as $line) {
            $obj = new stdClass();

            $obj->id = $line->id;
            $obj->fk_formation_catalogue = $line->fk_formation_catalogue;
            $obj->sort_order = $line->sort_order;
            $obj->title = $line->title;
            $obj->content_text = $line->content_text;
            $obj->obj_peda = $line->obj_peda;
            $obj->duration = $line->duration;

            $obj_ret[] = $obj;
        }

        return $obj_ret;
    }

    /**
     * Get a training module
     * @param int $id ID of the module
     *
     * @url GET /trainingmodules/{id}
     */
    function getTrainingModule($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if(empty($id)) throw new RestException(503, "No id provided");

        $this->trainingmodules = new Agefoddformationcataloguemodules($this->db);
        $result = $this->trainingmodules->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving modules", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($result)) throw new RestException(404, "No training module found");

        $obj_ret = new stdClass();

        $obj_ret->id = $this->trainingmodules->id;
        $obj_ret->fk_formation_catalogue = $this->trainingmodules->fk_formation_catalogue;
        $obj_ret->sort_order = $this->trainingmodules->sort_order;
        $obj_ret->title = $this->trainingmodules->title;
        $obj_ret->content_text = $this->trainingmodules->content_text;
        $obj_ret->obj_peda = $this->trainingmodules->obj_peda;
        $obj_ret->duration = $this->trainingmodules->duration;

        return $obj_ret;

    }

    /**
     * Create a training module
     *
     * @param int       $trainingId     ID of the training
     * @param string    $title          Title of the module
     * @param float     $duration       duration of this training part
     * @param string    $content_text   text infos on the module
     * @param string    $obj_peda       training goals of this part
     * @param string    $sort_order     Order of the training part in the training("max" to add module at the end or a number)
     *
     * @url POST /trainingmodules/
     */
    function postTrainingModule($trainingId, $title, $duration = 0, $content_text = '', $obj_peda='', $sort_order = "max")
    {
        global $conf;

        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->creer) {
            throw new RestException(401, 'Create not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if(empty($trainingId)) throw new RestException(503, "No trainingId provided");
        else {
            $this->training = new Formation($this->db);
            $result = $this->training->fetch($trainingId);
            if($result < 0) throw new RestException(503, "Error retrieving training", array($this->db->lasterror, $this->db->lastqueryerror));
            elseif(empty($this->training->id)) throw new RestException(404, "Training $trainingId not found");
        }

        if(empty($title)) throw new RestException(503, "No title provided");

        $this->trainingmodules = new Agefoddformationcataloguemodules($this->db);

        $this->trainingmodules->entity=$conf->entity;
        $this->trainingmodules->fk_formation_catalogue = $trainingId;

        if($sort_order == "max")
        {
            $this->trainingmodules->sort_order = $this->trainingmodules->findMaxSortOrder();
        } elseif(!intval($sort_order)){
            throw new RestException(500, "sort_order must be 'max' or a number");
        } else $this->trainingmodules->sort_order = $sort_order;

        $this->trainingmodules->title = $title;
        $this->trainingmodules->content_text = $content_text;
        $this->trainingmodules->duration = $duration;
        $this->trainingmodules->obj_peda = $obj_peda;
        $this->trainingmodules->status=1;

        $result = $this->trainingmodules->create(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error creating the module", array($this->db->lasterror, $this->db->lastquery));

        return $this->getTrainingModule($this->trainingmodules->id);
    }

    /**
     * Clone a training module
     *
     * @param int $id ID of the module
     *
     * @throws RestException
     * @return stdClass
     *
     * @url POST /trainingmodules/clone/
     */
    function cloneTrainingModule($id)
    {
        global $user;

        $user = DolibarrApiAccess::$user;

        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->creer) {
            throw new RestException(401, 'Create not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->trainingmodules = new Agefoddformationcataloguemodules($this->db);
        $result = $this->trainingmodules->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving modules", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($result)) throw new RestException(404, "No training module found");

        $result = $this->trainingmodules->createFromClone($id);
        if($result < 0) throw new RestException(500, "Error during module clone", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getTrainingModule($result);
    }

    /**
     * Update a training module
     *
     * @param int       $id             ID of the training module
     * @param string    $title          title of the module
     * @param string    $content_text   text infos on the module
     * @param float     $duration       duration of this training part
     * @param string    $obj_peda       training goals of this part
     * @param int       $sort_order     Order of the training part in the training
     *
     * @url PUT /trainingmodules/
     */
    function putTrainingModule($id, $title = '', $content_text = '', $duration = 0, $obj_peda = '', $sort_order = 0)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if(empty($id)) throw new RestException(503, "No id provided");

        $this->trainingmodules = new Agefoddformationcataloguemodules($this->db);
        $result = $this->trainingmodules->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving modules", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($result)) throw new RestException(404, "No training module found");

        if(!empty($title)) $this->trainingmodules->title = $title;
        if(!empty($content_text)) $this->trainingmodules->content_text = $content_text;
        if(!empty($duration) || $this->trainingmodules->duration !== $duration) $this->trainingmodules->duration = $duration;
        if(!empty($obj_peda)) $this->trainingmodules->obj_peda = $obj_peda;
        if(!empty($sort_order)) $this->trainingmodules->sort_order = $sort_order;

        $result = $this->trainingmodules->update(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error updating training module $id", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getTrainingModule($id);
    }

    /**
     * Delete a training module
     *
     * @param int $id ID of the training module to delete
     *
     * @throws RestException
     * @url DELETE /trainingmodules/
     */
    function deleteTrainingModule($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if(empty($id)) throw new RestException(503, "No id provided");

        $this->trainingmodules = new Agefoddformationcataloguemodules($this->db);
        $result = $this->trainingmodules->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving modules", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($result)) throw new RestException(404, "No training module found");

        $result = $this->trainingmodules->delete(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error deleting training module $id", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'training module '.$id.' deleted'
            )
        );
    }



    /***************************************************************** Place Part *****************************************************************/

    /**
     * List Places
     *
     * Get a list of Agefodd Places
     *
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$offset		Page number
     *
     * @url     GET /places/
     * @throws RestException
     */
    function placeIndex($sortfield = "p.rowid", $sortorder = 'DESC', $limit = 100, $offset=0)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_place->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->place = new Agefodd_place($this->db);
        $result = $this->place->fetch_all($sortorder, $sortfield, $limit, $offset);
        if(empty($result)) throw new RestException(404, "No place found");
        elseif($result < 0) throw new RestException(500, "Error while retrieving places", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->place->lines;
    }

    /**
     * Filtered List of Places
     *
     * Get a list of Agefodd Places
     *
     * @param string	$sortfield	        Sort field
     * @param string	$sortorder	        Sort order
     * @param int		$limit		        Limit for list
     * @param int		$page		        Page number
     * @param array		$filter		        array of filters ($k => $v)
     *
     * @return array                Array of place objects
     *
     * @url     POST /places/filter
     * @throws RestException
     */
    function placeFilteredIndex($sortfield = "p.rowid", $sortorder = 'DESC', $limit = 100, $offset=0, $filter = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_place->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->place = new Agefodd_place($this->db);
        $result = $this->place->fetch_all($sortorder, $sortfield, $limit, $offset, $filter);
        if(empty($result)) throw new RestException(404, "No place found");
        elseif($result < 0) throw new RestException(500, "Error while retrieving places", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->place->lines;
    }


    /**
     * Get properties of a place object
     *
     * @param int $id ID of the place
     *
     * @url    GET /places/{id}
     * @throws RestException
     */
    function getPlace($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_place->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->place = new Agefodd_place($this->db);
        $result = $this->place->fetch($id);
        if(empty($this->place->id)) throw new RestException(404, "Place not found");
        elseif($result < 0) throw new RestException(500, "Error while retrieving place", array($this->db->lasterror, $this->db->lastqueryerror));

        if(!empty($this->place->fk_reg_interieur))
        {
            $agf = new Agefodd_reg_interieur($this->db);
            $result = $agf->fetch($this->place->fk_reg_interieur);
            if($result > 0) {
                $obj = new stdClass();
                $obj->reg_int = $agf->reg_int;
                $obj->notes = $agf->notes;
                $this->place->rules = $obj;
            }
        }

        return $this->_cleanObjectDatas($this->place);
    }

    /**
     * Get informations for a place object
     *
     * Return an array with informations for the place
     *
     * @param 	int 	$id ID of place
     * @return 	array|mixed data without useless information
     *
     * @url	GET /places/{id}/infos/
     * @throws 	RestException
     */
    function getPlaceInfos($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_place->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->place = new Agsession($this->db);

        $result = $this->place->fetch($id);
        if($result>0) $this->place->info($id);
        if( $result < 0 || empty($this->place->id)) {
            throw new RestException(404, 'place not found');
        }

        return $this->_cleanObjectDatas($this->place);
    }

    /**
     * Get all sessions for the place
     *
     * Return an array of sessions
     *
     * @param 	int 	$id ID of place
     * @return 	array|mixed data without useless information
     *
     * @url POST /places/{id}/sessions/
     * throws RestException
     *
     */
    function getPlaceSessions($id, $sortorder = 'DESC', $sortfield = 's.dated', $limit = 0, $offset = 0, $filter = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_formation_catalogue->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->place = new Formation($this->db);
        $result = $this->place->fetch($id);
        if( $result < 0 || empty($this->place->id)) {
            throw new RestException(404, 'place not found');
        }

        $filter['s.fk_session_place'] = $this->place->id;

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch_all($sortorder, $sortfield, $limit, $offset, $filter);

        if(empty($result)) throw new RestException(404, "No session found for this training");
        elseif($result < 0) throw new RestException(500, "Error while retrieving sessions", array($this->db->lasterror,$this->db->lastqueryerror));

        $obj_ret = array();
        foreach ($this->session->lines as $line) $obj_ret[] = $this->_cleanObjectDatas($line);

        return $obj_ret;
    }

    /**
     * Create a place
     *
     * Return an array with informations for the place created
     *
     * @param string        $ref_interne    Reference of the place
     * @param int           $owner          ID of the thirdparty owner
     * @param array         $request        more data on the place
     *
     * @url POST /places/
     * @throws 	RestException
     */
    function postPlace($ref_interne, $owner, $request = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_place->creer) {
            throw new RestException(401, 'Creation not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->place = new Agefodd_place($this->db);
        $this->place->ref_interne = $ref_interne;

        $company = new Societe($this->db);
        $result = $company->fetch($owner);
        if(empty($result)) throw new RestException(404, "Thirdparty $owner not found");
        elseif($result < 0) throw  new RestException(500, "Error", array($this->db->lasterror, $this->db->lastqueryerror));

        $this->place->fk_societe = $owner;
        //$result = $this->_validate($request, 'place');

        foreach($request as $field => $value) {
            $this->place->$field = $value;
        }

        $result = $this->place->create(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error in creation", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getPlace($this->place->id);
    }

    /**
     * Update place address from the owner thirdparty adresse
     *
     * @param 	int 	$id ID of place
     * @return 	array|mixed data without useless information
     *
     * @url	PUT /places/importaddress/
     * @throws 	RestException
     */
    function placeImportAddress ($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_place->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->place = new Agefodd_place($this->db);

        $result = $this->place->fetch($id);
        if(empty($this->place->id)) throw new RestException(404, "Place not found");
        elseif($result < 0) throw new RestException(500, "Error while retrieving place", array($this->db->lasterror, $this->db->lastqueryerror));

        if(empty($this->place->fk_societe)) throw new RestException(503, "No owner for this place. A place MUST have a owner");

        $result = $this->place->import_customer_adress(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error in creation", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getPlace($id);
    }

    /**
     * Update a place
     *
     * Return an array with informations for the place created
     *
     * @param int           $id             ID of the place to update
     * @param array         $request        more data on the place
     *
     * @url PUT /places/
     *
     */
    function putPlace($id, $request = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_place->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->place = new Agefodd_place($this->db);

        $result = $this->place->fetch($id);
        if(empty($this->place->id)) throw new RestException(404, "Place not found");
        elseif($result < 0) throw new RestException(500, "Error while retrieving place", array($this->db->lasterror, $this->db->lastqueryerror));

        foreach ($request as $field => $value)
        {
            if($field == 'fk_societe' && !empty($value) && $value !== '-1') $this->place->$field = $value;
            elseif($field !== 'rowid') $this->place->$field = $value;
        }

        $result = $this->place->update(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error in update", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getPlace($id);
    }

    /**
     * Archive/Activate a place
     *
     * @url     PUT /places/archive
     *
     * @param int   $id     Id of place to update
     *
     */
    function archivePlace($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_place->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->place->fetch($id);
        if( $result < 0 || empty($this->place->id) ) {
            throw new RestException(404, 'place not found');
        }

        $this->place->archive = (empty($this->place->archive)) ? 1 : 0;
        $result = $this->place->update(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error in update", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getPlace($id);
    }

    /**
     * Update place Rules and notes
     *
     * Return an array with informations for the place created
     *
     * @param int       $id     ID of the place
     * @param string    $rules  Text that specifies rules for the place
     * @param string    $notes  Text that specifies notes for the place
     *
     * @url PUT /places/rules/
     *
     */
    function placePutRules($id, $rules = '', $notes = '')
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_place->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->place = new Agefodd_place($this->db);

        $result = $this->place->fetch($id);
        if( $result < 0 || empty($this->place->id) ) {
            throw new RestException(404, 'place not found');
        }

        $agf = new Agefodd_reg_interieur($this->db);

        if (empty($this->place->fk_reg_interieur)) // Create rules
        {
            if(!empty($rules)) $agf->reg_int = $rules;
            if(!empty($notes)) $agf->notes = $notes;
            $result = $agf->create(DolibarrApiAccess::$user);

            if ($result > 0) {
                $this->place->fk_reg_interieur = $result;
                $result = $this->place->update(DolibarrApiAccess::$user);
                if($result < 0) throw new RestException(500, "Error in update", array($this->db->lasterror, $this->db->lastqueryerror));

            } else throw new RestException(500, "Error in rules creation", array($this->db->lasterror, $this->db->lastqueryerror));
        } else { // Update rules
            $result = $agf->fetch($this->place->fk_reg_interieur);
            if ($result > 0) {
                if(!empty($rules)) $agf->reg_int = $rules;
                if(!empty($notes)) $agf->notes = $notes;
                $result = $agf->update(DolibarrApiAccess::$user);

                if($result < 0) throw new RestException(500, "Error in update", array($this->db->lasterror, $this->db->lastqueryerror));

            } else {
                throw new RestException(500, "Can't retrieve rules $this->place->fk_reg_interieur", array($this->db->lasterror, $this->db->lastqueryerror));
            }
        }

        return $this->getPlace($id);
    }

    /**
     * Update place Rules
     *
     * @param int       $id     ID of the place
     *
     * @url DELETE /places/rules/{id}
     */
    function placeDeleteRules($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_place->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->place = new Agefodd_place($this->db);

        $result = $this->place->fetch($id);
        if( $result < 0 || empty($this->place->id) ) {
            throw new RestException(404, 'place not found');
        }

        if(empty($this->place->fk_reg_interieur)) throw new RestException(404, "No rule to delete");

        $agf = new Agefodd_reg_interieur($this->db);
        $agf->id = $this->place->fk_reg_interieur;
        $result = $agf->delete(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error while deleting rules", array($this->db->lasterror, $this->db->lastqueryerror));

        $result = $this->place->remove_reg_int(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error while updating place", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'place rules deleted'
            )
        );
    }

    /**
     * Delete a place
     *
     * @param int $id   ID of the place to delete
     *
     * @url DELETE /places/{id}
     * @throws 	RestException
     */
    function deletePlace($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->agefodd_place->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->place = new Agefodd_place($this->db);

        $result = $this->place->fetch($id);
        if(empty($this->place->id)) throw new RestException(404, "Place not found");
        elseif($result < 0) throw new RestException(500, "Error while retrieving place", array($this->db->lasterror, $this->db->lastqueryerror));

        $result = $this->place->remove(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(503, "Error delete", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'place deleted'
            )
        );
    }

    /**
     * Return the list of documents attached to a place
     *
     * @param	int		$id				ID of trainer (not the trainerinsession id)
     * @param	string	$sortfield		Sort criteria ('','fullname','relativename','name','date','size')
     * @param	string	$sortorder		Sort order ('asc' or 'desc')
     * @return	array					Array of documents with path
     *
     * @url GET /places/attachment
     */
    function getPlaceAttachments($id, $sortfield='name', $sortorder='asc')
    {
        return $this->_getDocumentsListByElement("agefodd", $id, '', 'place', $sortfield, $sortorder);
    }

    /**
     * Attach a file to a place.
     *
     * Test sample 1: { "filename": "mynewfile.txt", "id": "70", "filecontent": "content text", "fileencoding": "", "overwriteifexists": "0" }.
     * Test sample 2: { "filename": "mynewfile.txt", "id": "70", "filecontent": "Y29udGVudCB0ZXh0Cg==", "fileencoding": "base64", "overwriteifexists": "0" }.
     *
     * @param   string  $filename           Name of file to create ('test.txt')
     * @param   string  $id                 ID of element
     * @param   string  $filecontent        File content (string with file content. An empty file will be created if this parameter is not provided)
     * @param   string  $fileencoding       File encoding (''=no encoding, 'base64'=Base 64) {@example '' or 'base64'}
     * @param   int 	$overwriteifexists  Overwrite file if exists (0 by default)
     *
     * @throws 200
     * @throws 400
     * @throws 401
     * @throws 404
     * @throws 500
     *
     * @url POST /places/attach
     */
    function attachToPlace($filename, $id, $filecontent='', $fileencoding='', $overwriteifexists=0)
    {
        return $this->_upload_file($filename, "place", $id, $filecontent, $fileencoding, $overwriteifexists);
    }

    /***************************************************************** OPCA Part *****************************************************************/

    /**
     * Get an OPCA
     *
     * @param int $id ID of the OPCA
     *
     * @url GET /opca/
     */
    function getOpca($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->opca = new Agefodd_opca($this->db);
        $result = $this->opca->fetch($id);
        if($result < 0) throw new RestException(500, "Error getting the OPCA", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->opca->id)) throw new RestException(404, "OPCA not found");

        return $this->_cleanObjectDatas($this->opca);
    }

    /**
     * Get all OPCA of a session
     *
     * @param int $sessid
     *
     * @url GET /opca/bysession
     */
    function getSessionOpca($sessid)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if($result < 0) throw new RestException(500, "Error retrieving session", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->session->id)) throw new RestException(404, "Session not found");

        $this->opca = new Agefodd_opca($this->db);
        $result = $this->opca->getOpcaSession($sessid);
        if($result<0) throw new RestException(500, "Error retrieving OPCA", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->opca->lines)) throw new RestException(404, "No OPCA found for this session");

        return $this->opca->lines;

    }

    /**
     * Get all OPCA in a session for a thirdparty ( and for a trainee)
     *
     * @param int $fk_soc_trainee   ID of the thirdparty
     * @param int $sessid           ID of the session
     * @param int $traineeid        ID of a trainee
     *
     * @url GET /opca/traineesession/
     */
    function getTraineeSessionOpca($fk_soc_trainee, $sessid, $traineeid = 0)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if($result < 0) throw new RestException(500, "Error retrieving session", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->session->id)) throw new RestException(404, "Session not found");

        $fk_trainee_session = 0;
        if(!empty($traineeid))
        {
            $this->traineeinsession = new Agefodd_session_stagiaire($this->db);
            $result = $this->traineeinsession->fetch_by_trainee($sessid, $traineeid);
            if($result < 0) throw new RestException(500, 'Error while retrieving the trainee in session', array($this->db->lasterror, $this->db->lastqueryerror));
            elseif (empty($result)) throw new RestException(404, 'Trainee not found in this session');

            $fk_trainee_session = $this->traineeinsession->id;
        }

        $this->opca = new Agefodd_opca($this->db);
        $result = $this->opca->getOpcaForTraineeInSession($fk_soc_trainee, $sessid);
        if($result < 0) throw new RestException(500, "Error getting the OPCA", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->opca->opca_rowid)) throw new RestException(404, "OPCA not found");

        return $this->getOpca($this->opca->opca_rowid);

    }

    /**
     * Create an Opca
     *
     * @param int       $sessid                 ID of the session
     * @param int       $traineeId              ID of the trainee
     * @param int       $traineeSoc             ID of the society of the trainee
     * @param string    $date_ask_OPCA          date of the demand if asked (Must be with the format yyyy-mm-dd)
     * @param int       $is_OPCA                1 if there is an founder thirdparty
     * @param int       $fk_soc_OPCA            ID of the thirdparty founder
     * @param int       $fk_socpeople_OPCA      ID of the founder contact
     * @param string    $num_OPCA_soc           Reference of the thirdparty in OPCA
     * @param string    num_OPCA_file           Reference of the OPCA file
     *
     * @throws RestException
     *
     * @url POST /opca/
     */
    function postOpca($sessid, $traineeId, $traineeSoc, $date_ask_OPCA='', $is_OPCA = 0, $fk_soc_OPCA = 0, $fk_socpeople_OPCA = 0, $num_OPCA_soc = '', $num_OPCA_file = '')
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Creation not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if($result < 0) throw new RestException(500, "Error retrieving session", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->session->id)) throw new RestException(404, "Session not found");

        $this->trainee = new Agefodd_stagiaire($this->db);
        $result = $this->trainee->fetch($traineeId);
        if($result < 0) throw new RestException(500, "Error retrieving trainee", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->trainee->id)) throw new RestException(404, "trainee not found");

        $this->traineeinsession = new Agefodd_session_stagiaire($this->db);
        $result = $this->traineeinsession->fetch_by_trainee($sessid, $traineeId);
        if($result < 0) throw new RestException(500, 'Error while retrieving the trainee in session', array($this->db->lasterror, $this->db->lastqueryerror));
        elseif (empty($result)) throw new RestException(404, 'Trainee not found in this session');

        $this->opca = new Agefodd_opca($this->db);
        $this->opca->fk_session_trainee = $this->traineeinsession->id;
        $this->opca->fk_soc_trainee = $traineeSoc;
        $this->opca->fk_session_agefodd = $sessid;

        if(!empty($date_ask_OPCA)) {
            if(!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_ask_OPCA)) throw new RestException(503, "Bad date format. date_ask_OPCA must be in format yyyy-mm-dd");
            $this->opca->date_ask_OPCA = dol_mktime(0, 0, 0, substr($date_ask_OPCA, 5, 2), substr($date_ask_OPCA, 8, 2), substr($date_ask_OPCA, 0, 4));
        }
        $this->opca->is_OPCA = (empty($is_OPCA)) ? 0 : 1;
        $this->opca->fk_soc_OPCA = (empty($fk_soc_OPCA)) ? 0 : $fk_soc_OPCA;
        $this->opca->fk_socpeople_OPCA = (empty($fk_socpeople_OPCA)) ? 0 : $fk_socpeople_OPCA;
        $this->opca->num_OPCA_soc = (empty($num_OPCA_soc)) ? '' : $num_OPCA_soc;
        $this->opca->num_OPCA_file = (empty($num_OPCA_file)) ? '' : $num_OPCA_file;

        $result = $this->opca->create(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error creating opca", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getOpca($result);
    }

    /**
     * Clone an opca
     *
     * @param int $id ID of the OPCA to clone
     *
     * @url POST /opca/clone/
     */
    function cloneOpca($id)
    {
        global $user;

        $user = DolibarrApiAccess::$user;

        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Creation not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->opca = new Agefodd_opca($this->db);
        $result = $this->opca->fetch($id);
        if($result < 0) throw new RestException(500, "Error getting the OPCA", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->opca->id)) throw new RestException(404, "OPCA not found");

        $result = $this->opca->createFromClone($id);
        if($result < 0) throw new RestException(500, "Error during clone", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getOpca($result);
    }

    /**
     * Update an OPCA
     *
     * @param int       $id                     ID of the OPCA to update
     * @param string    $date_ask_OPCA          date of the demand if asked (Must be with the format yyyy-mm-dd)
     * @param int       $is_OPCA                1 if there is a founder thirdparty (-1 = unchanged)
     * @param int       $fk_soc_OPCA            ID of the thirdparty founder (-1 = unchanged)
     * @param int       $fk_socpeople_OPCA      ID of the founder contact (-1 = unchanged)
     * @param string    $num_OPCA_soc           Reference of the thirdparty in OPCA
     * @param string    $num_OPCA_file           Reference of the OPCA file
     *
     * @throws RestException
     *
     * @url PUT /opca/
     */
    function putOpca($id,  $date_ask_OPCA='', $is_OPCA = -1, $fk_soc_OPCA = -1, $fk_socpeople_OPCA = -1, $num_OPCA_soc = '', $num_OPCA_file = '')
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->opca = new Agefodd_opca($this->db);
        $result = $this->opca->fetch($id);
        if($result < 0) throw new RestException(500, "Error getting the OPCA", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->opca->id)) throw new RestException(404, "OPCA not found");


        if(!empty($date_ask_OPCA)) {
            if(!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_ask_OPCA)) throw new RestException(503, "Bad date format. date_ask_OPCA must be in format yyyy-mm-dd");
            $this->opca->date_ask_OPCA = dol_mktime(0, 0, 0, substr($date_ask_OPCA, 5, 2), substr($date_ask_OPCA, 8, 2), substr($date_ask_OPCA, 0, 4));
        }
        if($is_OPCA > -1) $this->opca->is_OPCA = (empty($is_OPCA)) ? 0 : 1;
        if($fk_soc_OPCA > -1) $this->opca->fk_soc_OPCA = $fk_soc_OPCA;
        if($fk_socpeople_OPCA > -1) $this->opca->fk_socpeople_OPCA = $fk_socpeople_OPCA;
        if(!empty($num_OPCA_soc)) $this->opca->num_OPCA_soc = $num_OPCA_soc;
        if(!empty($num_OPCA_file)) $this->opca->num_OPCA_file = $num_OPCA_file;

        $result = $this->opca->update(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error updating opca", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getOpca($id);
    }

    /**
     * Delete an OPCA
     *
     * @param int  $id  ID of the OPCA to delete
     *
     * @throws RestException
     *
     * @url DELETE /opca/
     */
    function deleteOpca($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->opca = new Agefodd_opca($this->db);
        $result = $this->opca->fetch($id);
        if($result < 0) throw new RestException(500, "Error getting the OPCA", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->opca->id)) throw new RestException(404, "OPCA not found");

        $result = $this->opca->delete(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(503, "Error delete", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'OPCA deleted'
            )
        );

    }

    /***************************************************************** Session Links Part *****************************************************************/

    /**
     * Get a SessionLink
     *
     * @param int   $id     ID of the SessionLink
     *
     * @throws RestException
     * @url GET /sessions/links/{id}
     */
    function getLinks($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->sessionlinks = new Agefodd_session_element($this->db);
        $result = $this->sessionlinks->fetch($id);
        if($result<0) throw new RestException(500, "Error while getting the link", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->sessionlinks->id)) throw new RestException(404, "Link not found");

        return $this->_cleanObjectDatas($this->sessionlinks);
    }

    /**
     * Get all linked documents of a type for the thirdparty
     *
     * @param int       $socid  ID of the thirdparty
     * @param string    $type   Type of document ("bc" for orders, "fac" for invoices or "prop" for proposals)
     *
     * @url GET /sessions/links/bysoc
     */
    function getLinksSoc($socid, $type = 'bc')
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if(empty($type) || !in_array($type, array('bc','prop', 'fac'))) throw new RestException(500, "Bad type $type provided");

        $this->sessionlinks = new Agefodd_session_element($this->db);
        $result = $this->sessionlinks->fetch_element_per_soc($socid, $type);
        if($result<0) throw new RestException(500, "Error while getting the links", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->sessionlinks->lines)) throw new RestException(404, "No link found with this parameters");

        $obj_ret = array();

        foreach ($this->sessionlinks->lines as $line)
        {
            $obj_ret[] = $this->_cleanObjectDatas($line);
        }

        return $obj_ret;
    }

    /**
     * Get a link from the document id
     *
     * @param int    $id        ID of the document
     * @param string $type      Type of document ("bc" for orders, "fac" for invoices or "prop" for proposals)
     * @param int    $sessid    filter on a session id
     *
     * @url GET /sessions/links/bydoc
     */
    function getLinksById($id, $type = 'bc', $sessid = 0)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if(empty($type) || !in_array($type, array('bc','prop', 'fac'))) throw new RestException(500, "Bad type $type provided");

        $this->sessionlinks = new Agefodd_session_element($this->db);
        $result = $this->sessionlinks->fetch_element_by_id($id, $type, $sessid);
        if($result<0) throw new RestException(500, "Error while getting the links", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->sessionlinks->lines)) throw new RestException(404, "No link found with this parameters");

        $obj_ret = array();

        foreach ($this->sessionlinks->lines as $line)
        {
            $obj_ret[] = $this->_cleanObjectDatas($line);
        }

        return $obj_ret;
    }

    /**
     * Get all link for a session
     *
     * @param int   $sessid     ID of the session
     * @param int   $idsoc      ID of the thirdparty
     * @param array $type       Array of types (you can use "propal", "order", "invoice")
     *
     * @throws RestException
     * @return array[]
     *
     * @url POST /sessions/links/bythirdparty
     */
    function getLinksByThirdparty($sessid, $idsoc = 0, $type = array())
    {
        // fetch_by_session_by_thirdparty
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->sessionlinks = new Agefodd_session_element($this->db);
        $result = $this->sessionlinks->fetch_by_session_by_thirdparty($sessid, $idsoc, $type);
        if($result<0) throw new RestException(500, "Error while getting the links", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->sessionlinks->lines)) throw new RestException(404, "No link found with this parameters");

        $obj_ret = array();

        foreach ($this->sessionlinks->lines as $line)
        {
            $obj_ret[] = $this->_cleanObjectDatas($line);
        }

        return $obj_ret;
    }

    /**
     * Get the list of IDs of linkable supplier_invoice for a thirdparty
     *
     * @param int   $idsoc      ID of the supplier thridparty
     *
     * @throws RestException
     * @url GET /sessions/links/supplierinvoices
     */
    function getLinksSupplierInvoices($idsoc)
    {
        // fetch_invoice_supplier_by_thridparty
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->sessionlinks = new Agefodd_session_element($this->db);
        $result = $this->sessionlinks->fetch_invoice_supplier_by_thridparty($idsoc);
        if($result<0) throw new RestException(500, "Error while getting the links", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->sessionlinks->lines)) throw new RestException(404, "No link found with this parameters");

        return $this->sessionlinks->lines;
    }

    /**
     * Get costs and amounts on a session
     *
     * @param int       $sessid         ID of the session
     * @param int       $trainerid      Filter on a trainer id
     *
     * @throws RestException
     * @return stdClass
     *
     * @url GET /sessions/costs/
     */
    function getSessionAmounts($sessid, $trainerid = 0)
    {
        // fetch_by_session
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if($result < 0) throw new RestException(500, "Error getting the session", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->session->id)) throw new RestException(404, "Session not found");

        $opsid = '';
        if(!empty($trainerid))
        {
            $this->trainerinsession = new Agefodd_session_formateur($this->db);
            $result = $this->trainerinsession->fetch_formateur_per_session($sessid);
            if (empty($result)) throw new RestException(404, "No trainer for this session");
            elseif ($result < 0) throw new RestException(500, "Error while retrieving the trainers");


            foreach ($this->trainerinsession->lines as $line) {
                if($line->formid == $trainerid) {
                    $opsid = $line->opsid;
                    break;
                }
            }
            if (empty($opsid)) throw new RestException(404, "Trainer not found in this session");
        }

        $this->sessionlinks = new Agefodd_session_element($this->db);
        $result = $this->sessionlinks->fetch_by_session($sessid, $opsid);
        if($result<0) throw new RestException(500, "Error while getting amounts", array($this->db->lasterror, $this->db->lastqueryerror));

        $obj_ret = new stdClass();

        $obj_ret->propal_sign_amount = $this->sessionlinks->propal_sign_amount;
        $obj_ret->propal_amount = $this->sessionlinks->propal_amount;
        $obj_ret->order_amount = $this->sessionlinks->order_amount;
        $obj_ret->invoice_ongoing_amount = $this->sessionlinks->invoice_ongoing_amount;
        $obj_ret->nb_invoice_validated = $this->sessionlinks->nb_invoice_validated;
        $obj_ret->nb_invoice_unpaid = $this->sessionlinks->nb_invoice_unpaid;
        $obj_ret->invoice_payed_amount = $this->sessionlinks->invoice_payed_amount;
        $obj_ret->trainer_cost_amount = $this->sessionlinks->trainer_cost_amount;
        $obj_ret->trip_cost_amount = $this->sessionlinks->trip_cost_amount;
        $obj_ret->room_cost_amount = $this->sessionlinks->room_cost_amount;
        $obj_ret->invoicetrainerdraft = $this->sessionlinks->invoicetrainerdraft;

        return $obj_ret;
    }

    /**
     * Get the number of trainee for sessions linked with the supplier invoice given
     *
     * @param int $id ID of the supplier invoice
     *
     * @url GET /sessions/nbtraineebyinvoice
     */
    function getLinkedSessions($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->sessionlinks = new Agefodd_session_element($this->db);
        $result = $this->sessionlinks->get_linked_sessions($id, '%invoice_supplier%');
        if(!$result) throw new RestException(500, "Error", array($this->db->lasterror, $this->db->lastqueryerror));

        return $result;
    }

    /**
     * Get all element linked to a session
     *
     * @param int   $sessid     ID of the session
     *
     * @throws RestException
     *
     * @url GET /sessions/links/
     *
     */
    function getAllSessionLinks($sessid)
    {
        //fetch_element_by_session
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->sessionlinks = new Agefodd_session_element($this->db);
        $result = $this->sessionlinks->fetch_element_by_session($sessid);
        if($result<0) throw new RestException(500, "Error", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif (empty($this->sessionlinks->lines)) throw new RestException(404, "No link found");

        return $this->sessionlinks->lines;

    }

    /**
     * Update a session selling price
     *
     * @param int   $sessid     Id of the session
     *
     * @url PUT /sessions/updatesellingprice/
     */
    function sessionUpdatePrices($sessid)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if($result < 0) throw new RestException(500, "Error getting the session", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->session->id)) throw new RestException(404, "Session not found");

        $this->sessionlinks = new Agefodd_session_element($this->db);
        $this->sessionlinks->fk_session_agefodd = $sessid;
        $result = $this->sessionlinks->updateSellingPrice(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error updating prices", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'selling price for session '. $sessid .' updated'
            )
        );
    }

    /**
     * Create a link to a session
     *
     * @param int       $sessid         ID of the session
     * @param int       $fk_soc         ID of the thirdparty involved
     * @param string    $element_type   Type of link (can be "propal", "invoice", "order", "invoice_supplier_room", "invoice_supplierline_room", "invoice_supplier_trainer", "invoice_supplierline_trainer", "invoice_supplier_missions" or "order_supplier")
     * @param int       $fk_element     ID of the element to link
     * @param int       $trainerid      ID of a trainer (only used if element_type is "invoice_supplier_trainer" or "invoice_supplier_trainer")
     *
     * @url POST /sessions/links/
     */
    function createLink($sessid, $fk_soc, $element_type, $fk_element, $trainerid = 0)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if($result < 0) throw new RestException(500, "Error getting the session", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->session->id)) throw new RestException(404, "Session not found");

        if(empty($fk_soc)) throw new RestException(500, "fk_soc needed");

        $result = $this->session->fetch_societe_per_session($sessid);
        if( $result <= 0 ) throw new RestException(404, 'No thirdparty found');

        $TCustomers = array();
        foreach ($this->session->lines as $line)
        {
            $TCustomers[] = $line->socid;
        }

        if(count($TCustomers) == 0) throw  new RestException(404, "No thirdparty for this session");
        if(!in_array($fk_soc, $TCustomers)) throw new RestException(404, "$socid is not a thirdparty of this session");

        $TAllowedTypes = array("propal", "invoice", "order", "invoice_supplier_room", "invoice_supplierline_room", "invoice_supplier_trainer", "invoice_supplierline_trainer", "invoice_supplier_missions", "order_supplier");
        if(!in_array($element_type, $TAllowedTypes)) throw new RestException(500, "Bad element_type $element_type provided");

        if(in_array($element_type, array("invoice_supplier_trainer", "invoice_supplier_trainer")))
        {
            if(empty($trainerid)) throw new RestException(500, "trainerid needed for the element_type $element_type");
            else {
                $opsid = '';
                if(!empty($trainerid))
                {
                    $this->trainerinsession = new Agefodd_session_formateur($this->db);
                    $result = $this->trainerinsession->fetch_formateur_per_session($sessid);
                    if (empty($result)) throw new RestException(404, "No trainer for this session");
                    elseif ($result < 0) throw new RestException(500, "Error while retrieving the trainers");


                    foreach ($this->trainerinsession->lines as $line) {
                        if($line->formid == $trainerid) {
                            $opsid = $line->opsid;
                            break;
                        }
                    }
                    if (empty($opsid)) throw new RestException(404, "Trainer not found in this session");
                }
            }
        }

        if(empty($fk_element)) throw new RestException(500, "fk_element needed");

        $this->sessionlinks = new Agefodd_session_element($this->db);
        $this->sessionlinks->fk_session_agefodd = $sessid;
        $this->sessionlinks->fk_soc = $fk_soc;
        $this->sessionlinks->element_type = $element_type;
        $this->sessionlinks->fk_element = $fk_element;
        if(!empty($opsid)) $this->sessionlinks->fk_sub_element = $opsid;

        $result = $this->sessionlinks->create(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error creating link", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getLinks($result);
    }

    /**
     * Update a link
     *
     * @param int       $id             ID of the link
     * @param int       $sessid         ID of the session (-1 = do not change anything)
     * @param int       $fk_soc         ID of the thirdparty involved (-1 = do not change anything)
     * @param string    $element_type   Type of link (can be "propal", "invoice", "order", "invoice_supplier_room", "invoice_supplierline_room", "invoice_supplier_trainer", "invoice_supplierline_trainer", "invoice_supplier_missions" or "order_supplier") ('' = do not change anything)
     * @param int       $fk_element     ID of the element to link (-1 = do not change anything)
     * @param int       $trainerid      ID of a trainer (only used if element_type is "invoice_supplier_trainer" or "invoice_supplier_trainer") (-1 = do not change anything)
     *
     * @url PUT /sessions/links/
     */
    function putLink($id, $sessid = -1, $fk_soc = -1, $element_type = '', $fk_element = -1, $trainerid = -1)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->sessionlinks = new Agefodd_session_element($this->db);
        $result = $this->sessionlinks->fetch($id);
        if($result<0) throw new RestException(500, "Error while getting the link", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->sessionlinks->id)) throw new RestException(404, "Link not found");

        if($sessid > -1) $this->sessionlinks->fk_session_agefodd = $sessid;
        if($fk_soc > -1) $this->sessionlinks->fk_soc = $fk_soc;
        if(!empty($element_type))
        {
            $TAllowedTypes = array("propal", "invoice", "order", "invoice_supplier_room", "invoice_supplierline_room", "invoice_supplier_trainer", "invoice_supplierline_trainer", "invoice_supplier_missions", "order_supplier");
            if(!in_array($element_type, $TAllowedTypes)) throw new RestException(500, "Bad element_type $element_type provided");

            $this->sessionlinks->element_type = $element_type;

            if(in_array($element_type, array("invoice_supplier_trainer", "invoice_supplier_trainer")))
            {
                if(empty($trainerid)) throw new RestException(500, "trainerid needed for the element_type $element_type");
                else {
                    $opsid = '';
                    if(!empty($trainerid))
                    {
                        $this->trainerinsession = new Agefodd_session_formateur($this->db);
                        $result = $this->trainerinsession->fetch_formateur_per_session($sessid);
                        if (empty($result)) throw new RestException(404, "No trainer for this session");
                        elseif ($result < 0) throw new RestException(500, "Error while retrieving the trainers");

                        foreach ($this->trainerinsession->lines as $line) {
                            if($line->formid == $trainerid) {
                                $opsid = $line->opsid;
                                break;
                            }
                        }
                        if (empty($opsid)) throw new RestException(404, "Trainer not found in this session");
                        else $this->sessionlinks->fk_sub_element = $opsid;
                    }
                }

            } else unset($this->sessionlinks->fk_sub_element);
        }
        if($fk_element > -1) $this->sessionlinks->fk_element = $fk_element;

        $result = $this->sessionlinks->update(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error creating link", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getLinks($id);
    }

    /**
     * Delete a link
     *
     * @param int   $id     ID of the link to delete
     *
     * @url DELETE /sessions/links/
     */
    function deleteLink($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->sessionlinks = new Agefodd_session_element($this->db);
        $result = $this->sessionlinks->fetch($id);
        if($result<0) throw new RestException(500, "Error while getting the link", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->sessionlinks->id)) throw new RestException(404, "Link not found");

        $result = $this->sessionlinks->delete(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(503, "Error delete", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Link deleted'
            )
        );
    }

    /***************************************************************** Trainee Hours Part *****************************************************************/

    /**
     * Get one TraineeHour by his id
     *
     * @param int $id ID of the traineehour
     *
     * @url GET /sessions/traineehours/byid
     */
    function getTraineeHour($id) // fetch
    {
        global $conf, $langs;

        if(empty($conf->global->AGF_USE_REAL_HOURS)) throw new RestException(503, 'the configuration \''.$langs->transnoentitiesnoconv('AgfUseRealHours').'\' must be activated to use this feature.');
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->traineehours = new Agefoddsessionstagiaireheures($this->db);
        $result = $this->traineehours->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving the hours", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->traineehours->id)) throw new RestException(404, "Trainee Hour $id not found");

        return $this->_cleanObjectDatas($this->traineehours);
    }

    /**
     * Get one TraineeHour by the id of the session, trainee Id and id of the session calendar period
     *
     * @param int $sessid               ID of the session
     * @param int $traineeid            ID of the trainee
     * @param int $sessionCalendarId    iD of the sessioncalendar period
     *
     * @url GET /sessions/traineehours
     */
    function getTraineeHourBySession($sessid, $traineeid, $sessionCalendarId) // fetch_by_session
    {
        global $conf, $langs;

        if(empty($conf->global->AGF_USE_REAL_HOURS)) throw new RestException(503, 'the configuration \''.$langs->transnoentitiesnoconv('AgfUseRealHours').'\' must be activated to use this feature.');
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->traineehours = new Agefoddsessionstagiaireheures($this->db);
        $result = $this->traineehours->fetch_by_session($sessid, $traineeid, $sessionCalendarId);
        if($result < 0) throw new RestException(500, "Error retrieving the hours", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif($result = 0) throw new RestException(404, "Trainee Hour not found");

        return $this->_cleanObjectDatas($this->traineehours);
    }

    /**
     * Get all TraineeHour of a trainee in a session
     *
     * @param int $sessid               ID of the session
     * @param int $traineeid            ID of the trainee
     *
     * @url GET /sessions/traineehours/all
     */
    function getAllTraineeHourBySession($sessid, $traineeid) // fetch_all_by_session
    {
        global $conf, $langs;

        if(empty($conf->global->AGF_USE_REAL_HOURS)) throw new RestException(503, 'the configuration \''.$langs->transnoentitiesnoconv('AgfUseRealHours').'\' must be activated to use this feature.');
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->traineehours = new Agefoddsessionstagiaireheures($this->db);
        $result = $this->traineehours->fetch_all_by_session($sessid, $traineeid);
        if($result < 0) throw new RestException(500, "Error retrieving the hours", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif($result = 0) throw new RestException(404, "No trainee Hour found");

        $obj_ret = array();

        foreach ($this->traineehours->lines as $line) $obj_ret[] = $this->_cleanObjectDatas($line);

        return $obj_ret;
    }

    /**
     * Get total hours spent by the trainee on the session
     *
     * @param int   $sessid     ID of the session
     * @param int   $traineeid  ID of the trainee
     *
     * @throws RestException
     *
     * @url GET /sessions/traineehours/forsession
     */
    function getTraineeSessionHours($sessid, $traineeid) // heures_stagiaire
    {
        global $conf, $langs;

        if(empty($conf->global->AGF_USE_REAL_HOURS)) throw new RestException(503, 'the configuration \''.$langs->transnoentitiesnoconv('AgfUseRealHours').'\' must be activated to use this feature.');
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->traineehours = new Agefoddsessionstagiaireheures($this->db);
        return $this->traineehours->heures_stagiaire($sessid, $traineeid);
    }

    /**
     * Get total hours spent by the trainee
     *
     * @param int $traineeid ID of the trainee
     *
     * @throws RestException
     * @url GET /sessions/traineehours/total
     */
    function getTotalTraineeHours($traineeid) // heures_stagiaire_totales
    {
        global $conf, $langs;

        if(empty($conf->global->AGF_USE_REAL_HOURS)) throw new RestException(503, 'the configuration \''.$langs->transnoentitiesnoconv('AgfUseRealHours').'\' must be activated to use this feature.');
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->traineehours = new Agefoddsessionstagiaireheures($this->db);
        return $this->traineehours->heures_stagiaire_totales($traineeid);
    }

    /**
     * Create a trainee hour
     *
     * @param int   $sessid
     * @param int   $traineeid
     * @param int   $calendarId
     * @param number $hours
     *
     * @throws RestException
     *
     * @url POST /sessions/traineehours/
     */
    function createTraineeHour($sessid, $traineeid, $calendarId, $hours = 0) // create
    {
        global $conf, $langs;

        if(empty($conf->global->AGF_USE_REAL_HOURS)) throw new RestException(503, 'the configuration \''.$langs->transnoentitiesnoconv('AgfUseRealHours').'\' must be activated to use this feature.');
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Create not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if($result < 0) throw new RestException(500, "Error getting the session", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->session->id)) throw new RestException(404, "Session not found");

        $this->traineeinsession = new Agefodd_session_stagiaire($this->db);
        $result = $this->traineeinsession->fetch_by_trainee($sessid, $traineeid);
        if($result < 0) throw new RestException(500, 'Error while retrieving the trainee in session', array($this->db->lasterror, $this->db->lastqueryerror));
        elseif (empty($result)) throw new RestException(404, 'Trainee not found in this session');

        $this->sessioncalendar = new Agefodd_sesscalendar($this->db);
        $result = $this->sessioncalendar->fetch($calendarId);
        if($result < 0) throw new RestException(500, "Error retrieving the session calendar", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->sessioncalendar)) throw new RestException(404, "Session calendar not found");

        $this->traineehours = new Agefoddsessionstagiaireheures($this->db);
        $this->traineehours->fk_stagiaire = $traineeid;
        $this->traineehours->fk_calendrier = $calendarId;
        $this->traineehours->fk_session = $sessid;
        $this->traineehours->heures = ( float ) $hours;

        $result = $this->traineehours->create(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error creating the trainee hour", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getAllTraineeHourBySession($sessid, $traineeid);
    }

    /**
     * Update a trainee hour
     *
     * @param int       $id         ID of a trainee hour
     * @param number    $hours      Number of hours
     *
     * @throws RestException
     *
     * @url PUT /sessions/traineehours/
     */
    function putTraineeHour($id, $hours = -1) // update
    {
        global $conf, $langs;

        if(empty($conf->global->AGF_USE_REAL_HOURS)) throw new RestException(503, 'the configuration \''.$langs->transnoentitiesnoconv('AgfUseRealHours').'\' must be activated to use this feature.');
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Update not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->traineehours = new Agefoddsessionstagiaireheures($this->db);
        $result = $this->traineehours->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving the hours", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->traineehours->id)) throw new RestException(404, "Trainee Hour $id not found");

        if($hours>-1) $this->traineehours->heures = ( float ) $hours;

        $result = $this->traineehours->update(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error updating the trainee hour", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getTraineeHour($id);
    }

    /**
     * Delete a trainee hour
     *
     * @param int       $id         ID of a trainee hour
     *
     * @throws RestException
     *
     * @url DELETE /sessions/traineehours/
     */
    function deleteTraineeHour($id) // delete
    {
        global $conf, $langs;

        if(empty($conf->global->AGF_USE_REAL_HOURS)) throw new RestException(503, 'the configuration \''.$langs->transnoentitiesnoconv('AgfUseRealHours').'\' must be activated to use this feature.');
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->traineehours = new Agefoddsessionstagiaireheures($this->db);
        $result = $this->traineehours->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving the hours", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->traineehours->id)) throw new RestException(404, "Trainee Hour $id not found");

        $result = $this->traineehours->delete(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error deleting the hours", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Trainee Hour deleted'
            )
        );
    }

    /***************************************************************** Cursus Part *****************************************************************/

    /**
     * Get a list of cursus
     *
     * @param string    $sortorder
     * @param string    $sortfield
     * @param int       $limit          limit the number of records returned
     * @param int       $offset
     * @param int       $arch           1 = search in archived cursus or 0 for not archived
     *
     * @url GET /cursus/all
     */
    function cursusIndex($sortorder = 'DESC', $sortfield = 't.rowid', $limit = 100, $offset = 0, $arch = 0) // fetch_all($sortorder, $sortfield, $limit, $offset, $arch = 0)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch_all($sortorder, $sortfield, $limit, $offset, $arch);
        if($result < 0) throw new RestException(500, "Error retrieving cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "No cursus found");

        $obj_ret = array();

        foreach ($this->cursus->lines as $line) $obj_ret[] = $this->_cleanObjectDatas($line);

        return $obj_ret;
    }
    /**
     * Get a cursus
     *
     * @param int   $id
     *
     * @throws RestException
     * @url GET /cursus/
     */
    function getCursus($id) // fetch($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Cursus not found");

        return $this->_cleanObjectDatas($this->cursus);
    }

    /**
     * Get a cursus object with more infos
     *
     * @param int   $id
     *
     * @throws RestException
     * @url GET /cursus/infos
     */
    function getCursusInfos($id) // info($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Cursus not found");

        $result = $this->cursus->info($id);
        if($result < 0) throw new RestException(500, "Error retrieving cursus infos", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->_cleanObjectDatas($this->cursus);
    }

    /**
     * Create a cursus
     *
     * @param string    $ref_interne    reference of the cursus
     * @param string    $intitule       name of the cursus
     * @param string    $note_private   Private note on the cursus
     * @param string    $note_public    Public note on the cursus
     * @param array     $extra          array of the cursus extrafields ('code'=>'value')
     *
     * @throws RestException
     * @url POST /cursus/
     */
    function createCursus($ref_interne, $intitule, $note_private = '', $note_public = '', $extra = array()) // create($user)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Creation not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $ref_interne = trim($ref_interne);
        if(empty($ref_interne)) throw new RestException(500, "Invalid ref_interne");
        $this->cursus->ref_interne = $ref_interne;

        $intitule = trim($intitule);
        if(empty($intitule)) throw new RestException(500, "Invalid intitule");
        $this->cursus->intitule = $intitule;

        $this->cursus->note_private = $note_private;
        $this->cursus->note_public = $note_public;

        if(!empty($extra))
        {
            require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
            $extrafields = new ExtraFields($this->db);
            $extralabels = $extrafields->fetch_name_optionals_label($this->cursus->table_element, true);
            if (count($extralabels) > 0) {
                foreach ($extralabels as $key => $v)
                {
                    if(array_key_exists($key, $extra))
                    {
                        $this->cursus->array_options['options_'.$key] = $extra[$key];
                    }
                }
            }
        }

        $result = $this->cursus->create(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error creating cursus", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getCursus($result);
    }

    /**
     * Clone a cursus
     *
     * @param int   $id ID of the cursus to clone
     *
     * @throws RestException
     * @url POST /cursus/clone
     */
    function cloneCursus($id) // createFromClone($fromid)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Creation not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Cursus not found");

        $result = $this->cursus->createFromClone($id);
        if($result < 0) throw new RestException(500, "Error cloning cursus", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getCursus($result);
    }

    /**
     * Archive/Active a cursus.
     *
     * @param int $id   ID of the cursus
     *
     * @throws RestException
     * @url PUT /cursus/archive
     */
    function archiveCursus($id) // on cursus card
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Cursus not found");

        if(empty($this->cursus->archive)) $this->cursus->archive = 1;
        else $this->cursus->archive = 0;

        $result = $this->cursus->update(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error updating cursus", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getCursus($id);

    }

    /**
     * Update a cursus
     *
     * @param int       $id             ID of the cursus
     * @param string    $ref_interne    reference of the cursus (leave empty to apply no change)
     * @param string    $intitule       name of the cursus (leave empty to apply no change)
     * @param string    $note_private   Private note on the cursus
     * @param string    $note_public    Public note on the cursus
     * @param array     $extra          array of the cursus extrafields ('code'=>'value')
     *
     * @throws RestException
     * @url PUT /cursus/
     */
    function putCursus($id, $ref_interne= '', $intitule = '', $note_private = '', $note_public = '', $extra = array()) // update($user)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Cursus not found");

        if(!empty($ref_interne)) {
            $ref_interne = trim($ref_interne);
            if(empty($ref_interne)) throw new RestException(500, "Invalid ref_interne");
            $this->cursus->ref_interne = $ref_interne;
        }

        if(!empty($intitule)) {
            $intitule = trim($intitule);
            if(empty($intitule)) throw new RestException(500, "Invalid intitule");
            $this->cursus->intitule = $intitule;
        }

        $this->cursus->note_private = $note_private;
        $this->cursus->note_public = $note_public;

        if(!empty($extra))
        {
            require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
            $extrafields = new ExtraFields($this->db);
            $extralabels = $extrafields->fetch_name_optionals_label($this->cursus->table_element, true);
            if (count($extralabels) > 0) {
                foreach ($extralabels as $key => $v)
                {
                    if(array_key_exists($key, $extra))
                    {
                        $this->cursus->array_options['options_'.$key] = $extra[$key];
                    }
                }
            }
        }

        $result = $this->cursus->update(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error updating cursus", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getCursus($id);
    }

    /**
     * Delete a cursus
     *
     * @param int   $id
     *
     * @throws RestException
     * @url DELETE /cursus/
     */
    function deleteCursus($id) // delete($user)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401, 'Delete not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Cursus not found");

        $result = $this->cursus->delete(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error removing cursus", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Cursus deleted'
            )
        );
    }

    /**
     * Get the list of the trainings in a cursus
     *
     * @param int   $id     ID of the cursus
     *
     * @throws RestException
     * @url GET /cursus/trainings
     */
    function cursusGetTrainings($id, $sortorder = "ASC", $sortfield = "f.ref", $limit = 0, $offset = 0)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Cursus not found");

        $agf = new Agefodd_formation_cursus($this->db);
        $agf->fk_cursus = $this->cursus->id;
        $result = $agf->fetch_formation_per_cursus($sortorder, $sortfield, $limit, $offset);
        if($result < 0) throw new RestException(500, "Error retrieving trainings", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "No training found");

        $obj_ret = array();

        foreach ($agf->lines as $line)
        {
            $obj = new stdClass();
            $obj->trainingid = $line->fk_formation_catalogue;
            $obj->ref = $line->ref;
            $obj->ref_interne = $line->ref_interne;
            $obj->intitule = $line->intitule;
            $obj->archive = $line->archive;

            $obj_ret[] = $obj;
        }

        return $obj_ret;
    }

    /**
     * Add a training to a cursus
     *
     * @param int   $id             ID of the cursus
     * @param int   $trainingId     ID of the training to add
     *
     * @throws RestException
     * @url POST /cursus/addtraining
     */
    function cursusAddTraining($id, $trainingId)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Cursus not found");

        $this->training = new Formation($this->db);
        $result = $this->training->fetch($trainingId);
        if($result < 0) throw new RestException(500, "Error retrieving training", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "training not found");

        $agf = new Agefodd_formation_cursus($this->db);
        $agf->fk_cursus = $this->cursus->id;
        $result = $agf->fetch_formation_per_cursus();
        if($result < 0) throw new RestException(500, "Error retrieving trainings", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result !== 0) {
            foreach ($agf->lines as $line)
            {
                if($line->fk_formation_catalogue == $trainingId) throw new RestException(500, "Formation $trainingId already in the cursus $id");
            }
        }
        $agf->fk_formation_catalogue = $trainingId;

        $result = $agf->create(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error adding the training to cursus", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->cursusGetTrainings($id);

    }

    /**
     * Remove a training from a cursus
     *
     * @param int   $id             ID of the cursus
     * @param int   $trainingId     ID of the training to add
     *
     * @throws RestException
     *
     * @url DELETE /cursus/deltraining
     */
    function cursusRemoveTraining($id, $trainingId)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving the cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Cursus not found");

        $this->training = new Formation($this->db);
        $result = $this->training->fetch($trainingId);
        if($result < 0) throw new RestException(500, "Error retrieving the training", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "training not found");

        $agf = new Agefodd_formation_cursus($this->db);
        $agf->fk_cursus = $this->cursus->id;
        $result = $agf->fetch_formation_per_cursus();
        if($result < 0) throw new RestException(500, "Error retrieving trainings", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "No training found");

        $delId = 0;
        foreach ($agf->lines as $line)
        {
            if($line->fk_formation_catalogue == $trainingId) $delId = $line->id;
        }

        if(empty($delId)) throw new RestException(404, "The training $trainingId is not in the cursus $id");

        $agf->id = $delId;
        $result = $agf->delete(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error removing the training from the cursus", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Training removed from the cursus'
            )
        );
    }

    /**
     * Get the list of trainee for cursus
     *
     * @param int       $id         ID of the cursus
     * @param string    $sortorder
     * @param string    $sortfield
     * @param number    $limit
     * @param number    $offset
     * @param array     $filter
     *
     * @throws RestException
     * @url POST /cursus/trainees
     */
    function cursusGetTrainees($id, $sortorder = 'ASC', $sortfield = 't.rowid', $limit = 0, $offset = 0, $filter = array())
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Cursus not found");

        $agf = new Agefodd_stagiaire_cursus($this->db);
        $agf->fk_cursus = $id;
        $result = $agf->fetch_stagiaire_per_cursus($sortorder, $sortfield, $limit, $offset, $filter);
        if($result < 0) throw new RestException(500, "Error retrieving trainees", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "No trainee found");

        $obj_ret = array();

        foreach ($agf->lines as $line)
        {
            $obj = new stdClass();
            $obj->traineeid = $line->starowid;
            $obj->civilite = $line->civilite;
            $obj->nom = $line->nom;
            $obj->prenom = $line->prenom;
            $obj->socid = $line->socid;
            $obj->socname = $line->socname;
            $obj->nbsessdone = $line->nbsessdone;
            $obj->nbsesstodo = $line->nbsesstodo;

            $obj_ret[] = $obj;
        }

        return $obj_ret;

    }

    /**
     * Add a trainee to the cursus
     *
     * @param int   $id         ID of the cursus
     * @param int   $traineeId  ID of the trainee to add
     *
     * @throws RestException
     * @url POST /cursus/addtrainee
     */
    function cursusAddTrainee($id, $traineeId)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving the cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Cursus not found");

        $this->trainee = new Agefodd_stagiaire($this->db);
        $result = $this->trainee->fetch($traineeId);
        if($result < 0) throw new RestException(500, "Error retrieving the trainee", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif($result == 0) throw new RestException(404, "Trainee not found");

        $agf = new Agefodd_stagiaire_cursus($this->db);
        $agf->fk_cursus = $id;
        $result = $agf->fetch_stagiaire_per_cursus('ASC', 't.rowid', 0, 0);
        if($result < 0) throw new RestException(500, "Error retrieving trainees", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result !== 0) {
            foreach ($agf->lines as $line)
            {
                if($line->starowid == $traineeId) throw new RestException(500, "Trainee $traineeId is already in the cursus $id");
            }
        }

        $agf->fk_stagiaire = $traineeId;

        $result = $agf->create(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error adding the trainee to cursus", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->cursusGetTrainees($id);
    }

    /**
     * Remove a trainee for a cursus
     *
     * @param int       $id         ID of the cursus
     * @param int       $traineeId  ID of the trainee to remove
     *
     * @throws RestException
     * @url DELETE /cursus/deltrainee
     */
    function cursusRemoveTrainee($id, $traineeId)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving the cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Cursus not found");

        $this->trainee = new Agefodd_stagiaire($this->db);
        $result = $this->trainee->fetch($traineeId);
        if($result < 0) throw new RestException(500, "Error retrieving the trainee", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif($result == 0) throw new RestException(404, "Trainee not found");

        $agf = new Agefodd_stagiaire_cursus($this->db);
        $agf->fk_cursus = $id;
        $result = $agf->fetch_stagiaire_per_cursus('ASC', 't.rowid', 0, 0);
        if($result < 0) throw new RestException(500, "Error retrieving trainees", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "No trainee found");

        $traineeincursus = 0;
        foreach ($agf->lines as $line)
        {
            if($line->starowid == $traineeId) $traineeincursus = $line->id;
        }

        if(empty($traineeincursus)) throw new RestException(404, "Trainee $traineeId is not in the cursus $id");

        $agf->id = $traineeincursus;
        $result = $agf->delete(DolibarrApiAccess::$user);
        if($result<0) throw new RestException(500, "Error removing the trainee from cursus", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'trainee removed'
            )
        );
    }

    /**
     * Generate a certificate for the cursus
     *
     * @param int $id           ID of the cursus
     * @param int $traineeId    ID of the trainee
     *
     * @throws RestException
     * @url POST /cursus/certificate
     */
    function cursusGenerateAttestation($id, $traineeId)
    {
        global $conf, $langs;

        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if(empty($id)) throw new RestException(500, "the field id is required.");
        if(empty($traineeId)) throw new RestException(500, "the field traineeId is required.");

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if ($result < 0) throw new RestException(500, "Error retrieving cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif (empty($result)) throw new RestException(404, "Cursus not found");

        $outputlangs = $langs;
        if ($conf->global->MAIN_MULTILANGS)
            $newlang = $object->thirdparty->default_lang;
        if (! empty($newlang)) {
            $outputlangs = new Translate("", $conf);
            $outputlangs->setDefaultLang($newlang);
        }
        $model = 'attestation_cursus';
        $file = $model . '_' . $id . '_' . $traineeId . '.pdf';

        // this configuration variable is designed like
        // standard_model_name:new_model_name&standard_model_name:new_model_name&....
        if (! empty($conf->global->AGF_PDF_MODEL_OVERRIDE)) {
            $modelarray = explode('&', $conf->global->AGF_PDF_MODEL_OVERRIDE);
            if (is_array($modelarray) && count($modelarray) > 0) {
                foreach ( $modelarray as $modeloveride ) {
                    $modeloverridearray = explode(':', $modeloveride);
                    if (is_array($modeloverridearray) && count($modeloverridearray) > 0) {
                        if ($modeloverridearray[0] == $model) {
                            $model = $modeloverridearray[1];
                        }
                    }
                }
            }
        }


        $this->cursus->fk_stagiaire = $traineeId;
        dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
        $result = agf_pdf_create($this->db, $this->cursus, '', $model, $outputlangs, $file, 0);
        if ($result < 0) throw new RestException(500, "Can't create the file");

        return array(
            'success' => array(
                'code' => 200,
                'message' => "Attestation created with filename $file"
            )
        );

    }

    /***************************************************************** Cursus Trainee Part *****************************************************************/

    /**
     * Get a list of cursus for a trainee
     *
     * @param int       $traineeId      ID of the trainee
     * @param string    $sortorder
     * @param string    $sortfield
     * @param number    $limit
     * @param number    $offset
     *
     * @throws RestException
     * @url GET /trainees/cursus
     */
    function traineeListCursus($traineeId, $sortorder = 'ASC', $sortfield = 'c.rowid', $limit = 0, $offset = 0) // fetch_cursus_per_trainee
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->trainee = new Agefodd_stagiaire($this->db);
        $result = $this->trainee->fetch($traineeId);
        if($result < 0) throw new RestException(500, "Error retrieving the trainee", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif($result == 0) throw new RestException(404, "Trainee not found");

        $agf = new Agefodd_stagiaire_cursus($this->db);
        $agf->fk_stagiaire = $traineeId;
        $result = $agf->fetch_cursus_per_trainee($sortorder, $sortfield, $limit, $offset);
        if($result < 0) throw new RestException(500, "Error retrieving cursus", array($this->db->lasterror, $this->db->lastqueryerror));

        return $agf->lines;

    }

    /**
     * Get planed sessions for a trainee in a cursus
     *
     * @param int       $id         ID of the cursus
     * @param int       $traineeId  ID of the trainee
     * @param string    $sortorder
     * @param string    $sortfield
     * @param int       $limit
     * @param int       $offset
     *
     * @throws RestException
     *
     * @url GET /trainees/cursus/sessions
     */
    function traineeGetCursusSession($id, $traineeId, $sortorder='ASC', $sortfield = 's.rowid', $limit = 0, $offset = 0) // fetch_session_cursus_per_trainee
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Cursus not found");

        $this->trainee = new Agefodd_stagiaire($this->db);
        $result = $this->trainee->fetch($traineeId);
        if($result < 0) throw new RestException(500, "Error retrieving the trainee", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Trainee not found");

        $agf = new Agefodd_stagiaire_cursus($this->db);
        $agf->fk_cursus = $id;
        $result = $agf->fetch_stagiaire_per_cursus('ASC', 't.rowid', 0, 0);
        if($result < 0) throw new RestException(500, "Error retrieving trainees", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "No trainee found");

        $traineeincursus = 0;
        foreach ($agf->lines as $line)
        {
            if($line->starowid == $traineeId) $traineeincursus = $line->id;
        }

        if(empty($traineeincursus)) throw new RestException(404, "Trainee $traineeId is not in the cursus $id");

        $agf = new Agefodd_stagiaire_cursus($this->db);
        $agf->fk_stagiaire = $traineeId;
        $agf->fk_cursus = $id;

        $result = $agf->fetch_session_cursus_per_trainee($sortorder, $sortfield, $limit, $offset);
        if($result < 0) throw new RestException(500, "Error retrieving sessions", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif (empty($result)) throw new RestException(404, "No session found for this parameters");

        return $agf->lines;
    }

    /**
     * Get the list of sessions to plan for the trainee in the cursus.
     * It represents the trainings that the trainee has to validate to be able to validate the cursus.
     *
     * Return a list of trainings
     *
     * @param int   $id         ID of the cursus
     * @param int   $traineeId  ID of the trainee
     *
     * @throws RestException
     * @url GET /trainees/cursus/sessionstoplan
     *
     */
    function traineeGetSessionToPlan($id, $traineeId) // fetch_training_session_to_plan
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->cursus = new Agefodd_cursus($this->db);
        $result = $this->cursus->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving cursus", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Cursus not found");

        $this->trainee = new Agefodd_stagiaire($this->db);
        $result = $this->trainee->fetch($traineeId);
        if($result < 0) throw new RestException(500, "Error retrieving the trainee", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "Trainee not found");

        $agf = new Agefodd_stagiaire_cursus($this->db);
        $agf->fk_cursus = $id;
        $result = $agf->fetch_stagiaire_per_cursus('ASC', 't.rowid', 0, 0);
        if($result < 0) throw new RestException(500, "Error retrieving trainees", array($this->db->lasterror, $this->db->lastqueryerror));
        if($result == 0) throw new RestException(404, "No trainee found");

        $traineeincursus = 0;
        foreach ($agf->lines as $line)
        {
            if($line->starowid == $traineeId) $traineeincursus = $line->id;
        }

        if(empty($traineeincursus)) throw new RestException(404, "Trainee $traineeId is not in the cursus $id");

        $agf = new Agefodd_stagiaire_cursus($this->db);
        $agf->fk_stagiaire = $traineeId;
        $agf->fk_cursus = $id;

        $result = $agf->fetch_training_session_to_plan();
        if($result < 0) throw new RestException(500, "Error retrieving sessions to plan");
        elseif(empty($result)) throw new RestException(404, "No session to plan");

        return $agf->lines;
    }

    /**
     * Add a cursus to the trainee
     *
     * @param int   $id         ID of the cursus to add
     * @param int   $traineeId  ID of the trainee
     *
     * @throws RestException
     * @url POST /trainees/addcursus
     */
    function traineeAddCursus($id, $traineeId) // create
    {
        return $this->cursusAddTrainee($id, $traineeId);
    }

    /**
     * Remove a cursus of a trainee
     *
     * @param int   $id         ID of the cursus to add
     * @param int   $traineeId  ID of the trainee
     *
     * @throws RestException
     * @url DELETE /trainees/delcursus
     */
    function traineeDelCursus($id, $traineeId) // delete
    {
        return $this->cursusRemoveTrainee($id, $traineeId);
    }

    /***************************************************************** Certification Part *****************************************************************/
    // $conf->global->AGF_MANAGE_CERTIF must be activated for this part

    /**
     * Get a list of certificates
     *
     * @param string    $sortorder
     * @param string    $sortfield
     * @param int       $limit
     * @param int       $offset
     * @param array     $filter
     *
     * @throws RestException
     * @url POST /certificates/filter
     */
    function certifIndex($sortorder = "DESC", $sortfield = "t.rowid", $limit = 0, $offset = 0, $filter = array()) // fetch_all
    {
        global $conf, $langs;

        if (empty($conf->global->AGF_MANAGE_CERTIF)) throw new RestException(500, "The option '" . $langs->trans("AgfManageCertification") . "' must be activated for this module part");

        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->certif = new Agefodd_stagiaire_certif($this->db);
        $result = $this->certif->fetch_all($sortorder, $sortfield, $limit, $offset, $filter);
        if($result < 0) throw new RestException(500, "Error retrieving certificates", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($result)) throw new RestException(404, "no certificates found with these parameters");

        $obj_ret = array();

        foreach ($this->certif->lines as $line) {
            $line->fetch_certif_state($line->id);
            $obj_ret[] = $this->_cleanObjectDatas($line);
        }

        return $obj_ret;
    }

    /**
     * Get a certificate by his ID or by the trainee and session id.
     *
     * Use $id if you know it or the couple $id_trainee/$id_session if not
     *
     * @param number $id            ID of the certificate
     * @param number $id_trainee    ID of the trainee
     * @param number $id_session    ID of the session
     *
     * @throws RestException
     * @url GET /certificates/
     */
    function getCertif($id = 0, $id_trainee = 0, $id_session = 0) // fetch
    {
        global $conf, $langs;

        if (empty($conf->global->AGF_MANAGE_CERTIF)) throw new RestException(500, "The option '" . $langs->trans("AgfManageCertification") . "' must be activated for this module part");

        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if(empty($id))
        {
            if(!empty($id_trainee) && empty($id_session)) throw new RestException(500, "field id_session missing");
            elseif(empty($id_trainee) && !empty($id_session)) throw new RestException(500, "field id_trainee missing");
            elseif(empty($id_trainee) && empty($id_session)) throw new RestException(500, "Can't get certificate without parameters");
        }

        $this->certif = new Agefodd_stagiaire_certif($this->db);
        $result = $this->certif->fetch($id, $id_trainee, $id_session);
        if($result < 0) throw new RestException(500, "Error retrieving certificates", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->certif->id)) throw new RestException(404, "no certificates found with these parameters");

        return $this->_cleanObjectDatas($this->certif);
    }

    /**
     * Get all certificates of the trainees of a thirdparty
     *
     * @param int       $socid      ID of the thirdparty of the trainees
     * @param string    $sortorder
     * @param string    $sortfield
     * @param int       $limit
     * @param int       $offset
     * @param array     $filter
     *
     * @throws RestException
     * @url POST /certificates/bythirdparty
     */
    function getCertifByThirdparty($socid, $sortorder = 'DESC', $sortfield = 'certif.certif_dt_start', $limit = 0, $offset = 0, $filter = array()) // fetch_certif_customer
    {
        global $conf, $langs;

        if (empty($conf->global->AGF_MANAGE_CERTIF)) throw new RestException(500, "The option '" . $langs->trans("AgfManageCertification") . "' must be activated for this module part");

        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if(empty($socid)) throw new RestException(500, "field socid missing");

        $this->certif = new Agefodd_stagiaire_certif($this->db);
        $result = $this->certif->fetch_certif_customer($socid, $sortorder, $sortfield, $limit, $offset, $filter);
        if($result < 0) throw new RestException(500, "Error retrieving certificates", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($result)) throw new RestException(404, "no certificates found with these parameters");

        $obj_ret = array();

        foreach ($this->certif->lines as $line) $obj_ret[] = $this->_cleanObjectDatas($line);

        return $obj_ret;
    }

    /**
     * Get the states of a certificate
     *
     * @param int $id ID of the certificate
     *
     * @throws RestException
     * @url GET /certificates/states
     */
    function getCertifStates($id) // fetch_certif_state
    {
        global $conf, $langs;

        if (empty($conf->global->AGF_MANAGE_CERTIF)) throw new RestException(500, "The option '" . $langs->trans("AgfManageCertification") . "' must be activated for this module part");

        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->certif = new Agefodd_stagiaire_certif($this->db);
        $result = $this->certif->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving certificates", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->certif->id)) throw new RestException(404, "Certificate $id not found");

        $result = $this->certif->fetch_certif_state($id);
        if($result < 0) throw new RestException(500, "Error retrieving states", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(count($this->certif->lines_state) == 0) throw new RestException(404, "No state found for certificate $id");

        return $this->certif->lines_state;
    }

    /**
     * Get an array of certificate types
     *
     * @throws RestException
     * @return array of types
     *
     * @throws RestException
     * @url GET /certificates/statetypes
     */
    function getCertifTypes() // get_certif_type
    {
        global $conf, $langs;

        if (empty($conf->global->AGF_MANAGE_CERTIF)) throw new RestException(500, "The option '" . $langs->trans("AgfManageCertification") . "' must be activated for this module part");

        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->certif = new Agefodd_stagiaire_certif($this->db);
        $result = $this->certif->get_certif_type();
        if($result < 0) throw new RestException(500, "Error retrieving certificate types", array($this->db->lasterror, $this->db->lastqueryerror));

        return $result;
    }

    /**
     * Get infos on certificate creation
     *
     * @param int $id ID of the certificate
     *
     * @url GET /certificates/infos
     */
    function getCertifInfos($id) // info
    {
        global $conf, $langs;

        if (empty($conf->global->AGF_MANAGE_CERTIF)) throw new RestException(500, "The option '" . $langs->trans("AgfManageCertification") . "' must be activated for this module part");

        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->certif = new Agefodd_stagiaire_certif($this->db);
        $result = $this->certif->fetch($id);
        $result = $this->certif->info($id);
        if($result < 0) throw new RestException(500, "Error retrieving certificates", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->certif->id)) throw new RestException(404, "no certificates found with these parameters");

        return $this->_cleanObjectDatas($this->certif);
    }

    /**
     * Get all certificates of a trainee
     *
     * @param int  $traineeId ID of the trainee
     *
     * @throws RestException
     * @url GET /trainees/certificates
     */
    function traineeGetCertificates($traineeId) // fetch_all_by_trainee
    {
        global $conf, $langs;

        if (empty($conf->global->AGF_MANAGE_CERTIF)) throw new RestException(500, "The option '" . $langs->trans("AgfManageCertification") . "' must be activated for this module part");

        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->trainee = new Agefodd_stagiaire($this->db);
        $result = $this->trainee->fetch($traineeId);
        if($result < 0) throw new RestException(500, "Error retrieving trainee", array($this->db->lasterror, $this->db->lastqueryerror));

        $this->certif = new Agefodd_stagiaire_certif($this->db);
        $result = $this->certif->fetch_all_by_trainee($traineeId);
        if($result < 0) throw new RestException(500, "Error retrieving certificates", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($result)) throw new RestException(404, "No certificate found for this trainee");

        return $this->certif->lines;
    }

    /**
     * Create a certificate
     *
     * @param int       $traineeId      ID of the trainee
     * @param int       $sessid         ID of the session
     * @param string    $date_start     start of the certificate validity (session start date if left blank)
     * @param string    $date_end       end of the certificate validity ( = $date_start if left blank)
     * @param string    $date_warning   date to alert the trainee of short validity (6 month before the end if blank)
     * @param string    $label          optionnal label
     * @param string    $note           optionnal note on the certificate
     *
     * @throws RestException
     * @url POST /certificates/
     */
    function postCertif($traineeId, $sessid, $date_start = '', $date_end = '', $date_warning = '', $label = '', $note = '') // create
    {
        global $conf, $langs;

        if (empty($conf->global->AGF_MANAGE_CERTIF)) throw new RestException(500, "The option '" . $langs->trans("AgfManageCertification") . "' must be activated for this module part");

        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->certif = new Agefodd_stagiaire_certif($this->db);

        $this->trainee = new Agefodd_stagiaire($this->db);
        $result = $this->trainee->fetch($traineeId);
        if($result < 0) throw new RestException(500, "Error retrieving the trainee", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif($result == 0) throw new RestException(404, "Trainee not found");

        $this->certif->fk_stagiaire = $this->trainee->id;

        $this->session = new Agsession($this->db);
        $result = $this->session->fetch($sessid);
        if($result < 0) throw new RestException(500, "Error retrieving the session", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->session->id)) throw new RestException(404, "session not found");

        $this->traineeinsession = new Agefodd_session_stagiaire($this->db);
        $result = $this->traineeinsession->fetch_by_trainee($sessid, $traineeId);
        if($result < 0) throw new RestException(500, "Error retrieving trainee in session", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($result)) throw new RestException(404, "the trainee $traineeId is not in the session $sessid");

        $agf_certif = new Agefodd_stagiaire_certif($this->db);
        $res = $agf_certif->fetch(0, $this->trainee->id, $this->session->id, $this->traineeinsession->id);
        if($res<0) throw new RestException(500, "Error retrieving certif");
        elseif (!empty($agf_certif->id)) throw new RestException(503, "Error certificate already exists in the session $sessid for the trainee $traineeId. His Id is $agf_certif->id");

        $this->certif->fk_stagiaire = $this->trainee->id;
        $this->certif->fk_session_agefodd = $this->session->id;
        $this->certif->fk_session_stagiaire = $this->traineeinsession->id;

        $obj = empty($conf->global->AGF_CERTIF_ADDON) ? 'mod_agefoddcertif_simple' : $conf->global->AGF_CERTIF_ADDON;
        $path_rel = dol_buildpath('/agefodd/core/modules/agefodd/certificate/' . $conf->global->AGF_CERTIF_ADDON . '.php');
        if (! empty($conf->global->AGF_CERTIF_ADDON) && is_readable($path_rel)) {
            $agf_training = new Formation($this->db);
            $agf_training->fetch($this->session->fk_formation_catalogue);
            dol_include_once('/agefodd/core/modules/agefodd/certificate/' . $conf->global->AGF_CERTIF_ADDON . '.php');
            $modAgefodd = new $obj();
            $certif_code = $modAgefodd->getNextValue($agf_training, $this->session);
        }

        $this->certif->certif_code = $certif_code;

        if(empty($label)) $this->certif->certif_label = $certif_code;
        else $this->certif->certif_label = $this->db->escape($label);

        if(!empty($date_start) && !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_start)) throw new RestException(503, "Bad date format for date_start. It must be a string date with format yyyy-mm-dd");
        elseif(empty($date_start)) $date_start = date("Y-m-d",$this->session->dated);

        $this->certif->certif_dt_start = strtotime($date_start);

        if(!empty($date_end) && !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_end)) throw new RestException(503, "Bad date format for date_end. It must be a string date with format yyyy-mm-dd");
        elseif (!empty($date_end) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_end)) $this->certif->certif_dt_end = strtotime($date_end);
        elseif (! empty($agf_training->certif_duration)) {
            require_once (DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php');
            $duration_array = explode(':', $agf_training->certif_duration);
            $year = $duration_array [0];
            $month = $duration_array [1];
            $day = $duration_array [2];
            $this->certif->certif_dt_end = dol_time_plus_duree($this->certif->certif_dt_start, $year, 'y');
            $this->certif->certif_dt_end = dol_time_plus_duree($this->certif->certif_dt_end, $month, 'm');
            $this->certif->certif_dt_end = dol_time_plus_duree($this->certif->certif_dt_end, $day, 'd');
        } else {
            $this->certif->certif_dt_end = $this->certif->certif_dt_start;
        }

        if(!empty($date_warning) && !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_warning)) throw new RestException(503, "Bad date format for date_warning. It must be a string date with format yyyy-mm-dd");
        elseif(!empty($date_warning)) $this->certif->certif_dt_warning = strtotime($date_warning);
        else $this->certif->certif_dt_warning = dol_time_plus_duree($this->certif->certif_dt_end, -6, 'm');

        if(!empty($note)) $this->certif->mark = $this->db->escape($note);

        $resultcertif = $this->certif->create(DolibarrApiAccess::$user);
        if($resultcertif < 0) throw new RestException(500, "Error in certificate creation", array($this->db->lasterror, $this->db->lastqueryerror));

        // certif states initialisation
        $certif_type_array = $this->certif->get_certif_type();

        if (is_array($certif_type_array) && count($certif_type_array) > 0) {
            $error = 0;
            $errors = array();
            foreach ( $certif_type_array as $certif_type_id => $certif_type_label ) {
                // Case state didn't exists yet
                $result = $this->certif->set_certif_state(DolibarrApiAccess::$user, $resultcertif, $certif_type_id, 0);
                if ($result < 0) {
                    $error++;
                    $errors[] = $this->certif->error;
                }
            }
            if(!empty($error)) throw new RestException(500, "Error creating states", $errors);
        }

        return $this->getCertif($resultcertif);
    }

    /**
     * Update a certificate
     *
     * @param int       $id             ID of the certificate to update
     * @param string    $date_start     start of the certificate validity (must be a string date with format yyyy-mm-dd. No change if left blank)
     * @param string    $date_end       end of the certificate validity (must be a string date with format yyyy-mm-dd. No change if left blank)
     * @param string    $date_warning   date to alert the trainee of short validity (must be a string date with format yyyy-mm-dd. No change if left blank)
     * @param string    $label          optionnal label of the certificate
     * @param string    $note           some notes on the certificate
     * @param array     $states         array of certificate states by type (array(fk_certif_type => 0 or 1))
     *
     * @throws RestException
     * @url PUT /certificates/
     */
    function putCertif($id, $date_start = '', $date_end = '', $date_warning = '', $label = '', $note = '', $states = array()) // update
    {
        global $conf, $langs;

        if (empty($conf->global->AGF_MANAGE_CERTIF)) throw new RestException(500, "The option '" . $langs->trans("AgfManageCertification") . "' must be activated for this module part");

        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if(empty($id)) throw new RestException(500, "the id of the certificate is required");
        $this->certif = new Agefodd_stagiaire_certif($this->db);
        $result = $this->certif->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving certificates", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->certif->id)) throw new RestException(404, "Certificate $id not found");

        if(!empty($date_start) && !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_start)) throw new RestException(503, "Bad date format for date_start. It must be a string date with format yyyy-mm-dd");
        elseif(!empty($date_start) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_start)) $this->certif->certif_dt_start = strtotime($date_start);

        if(!empty($date_end) && !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_end)) throw new RestException(503, "Bad date format for date_end. It must be a string date with format yyyy-mm-dd");
        elseif (!empty($date_end) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_end)) $this->certif->certif_dt_end = strtotime($date_end);

        if(!empty($date_warning) && !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_warning)) throw new RestException(503, "Bad date format for date_warning. It must be a string date with format yyyy-mm-dd");
        elseif(!empty($date_warning) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $date_warning)) $this->certif->certif_dt_warning = strtotime($date_warning);

        if(!empty($label)) $this->certif->certif_label = $this->db->escape($label);
        if(!empty($note)) $this->certif->mark = $this->db->escape($note);

        $resultcertif = $this->certif->update(DolibarrApiAccess::$user);
        if($resultcertif < 0) throw new RestException(500, "Error in certificate modification", array($this->db->lasterror, $this->db->lastqueryerror));

        // manage states
        if(!empty($states)){
            $certif_type_array = $this->certif->get_certif_type();
            if (is_array($certif_type_array) && count($certif_type_array) > 0) {
                $error = 0;
                $errors = array();
                foreach ( $certif_type_array as $certif_type_id => $certif_type_label ) {
                    if(array_key_exists($certif_type_id, $states)) {
                        $state = !empty($states[$certif_type_id]) ? 1 : 0;
                        $result = $this->certif->set_certif_state(DolibarrApiAccess::$user, $id, $certif_type_id, $state);
                        if($result < 0){
                            $error++;
                            $errors[] = "Error during modification of the state $certif_type_id";
                        }
                    }
                }

                if(!empty($error)) throw new RestException(500, "Error during modification of states", $errors);
            }
        }


        return $this->getCertif($id);
    }

    /**
     * Set states of a certificate
     *
     * @param int   $certif_id          ID of the certificate
     * @param int   $certif_type_id     ID of the type of state (fk_certif_type)
     * @param int   $certif_state       0 if state is "not validated" or 1 if "validated"
     *
     * @throws RestException
     * @url POST /certificates/states
     *
     */
    function setCertifStates($certif_id, $certif_type_id, $certif_state) // set_certif_state
    {
        global $conf, $langs;

        if (empty($conf->global->AGF_MANAGE_CERTIF)) throw new RestException(500, "The option '" . $langs->trans("AgfManageCertification") . "' must be activated for this module part");

        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if(empty($certif_id)) throw new RestException(500, "field certif_id is required");
        if(empty($certif_type_id)) throw new RestException(500, "field certif_type_id is required");
        if(empty($certif_state)) $certif_state = 0;
        else $certif_state = 1;

        $this->certif = new Agefodd_stagiaire_certif($this->db);
        $result = $this->certif->fetch($certif_id);
        if($result < 0) throw new RestException(500, "Error retrieving certificate", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->certif->id)) throw new RestException(404, "no certificate found");

        $result = $this->certif->set_certif_state(DolibarrApiAccess::$user, $certif_id, $certif_type_id, $certif_state);
        if($result < 0) throw new RestException(500, "Error during modification", array($this->db->lasterror, $this->db->lastqueryerror));

        return $this->getCertifStates($certif_id);
    }

    /**
     * Delete a certificate
     *
     * @param int   $id     ID of the certificate to delete
     *
     * @throws RestException
     * @url DELETE /certificates/
     */
    function deleteCertif($id)
    {
        global $conf, $langs;

        if (empty($conf->global->AGF_MANAGE_CERTIF)) throw new RestException(500, "The option '" . $langs->trans("AgfManageCertification") . "' must be activated for this module part");

        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Modification not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->certif = new Agefodd_stagiaire_certif($this->db);
        $result = $this->certif->fetch($id);
        if($result < 0) throw new RestException(500, "Error retrieving certificate", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($this->certif->id)) throw new RestException(404, "no certificate found");

        $result = $this->certif->delete(DolibarrApiAccess::$user);
        if($result < 0) throw new RestException(500, "Error : certificate not deleted", array($this->db->lasterror, $this->db->lastqueryerror));

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Certificate deleted'
            )
        );

    }



    /***************************************************************** Thirdparty Part *****************************************************************/

    /**
     * Get sessions for a thirdparty object
     *
     * Return an array with thirdparty informations for the trainee
     *
     * @param int $id socid filter
	 * @param string $sortorder order
	 * @param string $sortfield field
	 * @param int $limit page
	 * @param int $offset
	 * @param array $filter output
	 *
     * @return 	array data without useless information
     *
     * @url	POST /thirdparties/{id}/sessions/
     * @throws 	RestException
     */
    function getThirdpartiesSessions($id, $sortorder = "DESC", $sortfield = "s.rowid", $limit = 100, $offset = 0, $filter = '')
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401);
        }

        if(empty($id)) throw new RestException(404, 'no thirdparty id provided');
        if (empty($filter['type_affect'])) $filter['type_affect'] = 'thirdparty';

        $result = $this->session->fetch_all_by_soc($id, $sortorder, $sortfield, $limit, $offset, $filter);

        if( $result < 0) {
            throw new RestException(503, 'Error when retrieve session list');
        } elseif (count($this->session->lines) == 0) {
            throw new RestException(404, 'no session for this thirdparty');
        }

        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach ($this->session->lines as $line){
            $obj_ret[] = parent::_cleanObjectDatas($line);
        }

        return $obj_ret;
    }

    /**
     * Get the last signed convention
     *
     * @param int $socid ID of a thirdparty
     *
     * @throws RestException
     * @url GET /thirdparties/{id}/lastconvention
     */
    function getLastConv($id) // Agefodd_convention::fetch_last_conv_per_socity
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $agf = new Agefodd_convention($this->db);
        $result = $agf->fetch_last_conv_per_socity($id);
        if($result < 0) throw new RestException(500, "Can't retrieve last convention", array($this->db->lasterror, $this->db->lastqueryerror));
        elseif(empty($agf->sessid)) throw new RestException(404, "No convention found");

        return $this->sessionGetConvention($agf->sessid, $id);

    }

    /***************************************************************** Common Part *****************************************************************/

    /**
     * Return the list of documents of a dedicated element (from its ID or Ref)
     *
     * @param   string 	$modulepart		Name of module or area concerned ('thirdparty', 'member', 'proposal', 'order', 'invoice', 'shipment', 'project',  ...)
     * @param	int		$id				ID of element
     * @param	string	$ref			Ref of element
     * @param   string  $object_type    Type of object if modulepart is "agefodd" (can be "session", "trainee", "trainer", "training" or "place")
     * @param	string	$sortfield		Sort criteria ('','fullname','relativename','name','date','size')
     * @param	string	$sortorder		Sort order ('asc' or 'desc')
     * @return	array					Array of documents with path
     *
     * @throws 200
     * @throws 400
     * @throws 401
     * @throws 404
     * @throws 500
     *
     */
    private function _getDocumentsListByElement($modulepart, $id=0, $ref='', $object_type = '', $sortfield='name', $sortorder='asc')
    {
        global $conf;

        if (empty($modulepart)) {
            throw new RestException(400, 'bad value for parameter modulepart');
        }

        if ($modulepart == "agefodd" && empty($object_type)) {
            throw new RestException(400, "No object_type provided for modulepart agefodd");
        }

        if ($modulepart == "agefodd" && !in_array($object_type, array("session", "trainer", "trainee", "training", "place"))) {
            throw new RestException(400, 'Invalid object_type');
        }

        if (empty($id) && empty($ref)) {
            throw new RestException(400, 'bad value for parameter id or ref');
        }

        $id = (empty($id)?0:$id);

        if ($modulepart == 'societe' || $modulepart == 'thirdparty')
        {
            require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

            if (!DolibarrApiAccess::$user->rights->societe->lire) {
                throw new RestException(401);
            }

            $object = new Societe($this->db);
            $result=$object->fetch($id, $ref);
            if ( ! $result ) {
                throw new RestException(404, 'Thirdparty not found');
            }

            $upload_dir = $conf->societe->multidir_output[$object->entity] . "/" . $object->id;
        }
        else if ($modulepart == 'adherent' || $modulepart == 'member')
        {
            require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

            if (!DolibarrApiAccess::$user->rights->adherent->lire) {
                throw new RestException(401);
            }

            $object = new Adherent($this->db);
            $result=$object->fetch($id, $ref);
            if ( ! $result ) {
                throw new RestException(404, 'Member not found');
            }

            $upload_dir = $conf->adherent->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'member');
        }
        else if ($modulepart == 'propal' || $modulepart == 'proposal')
        {
            require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';

            if (!DolibarrApiAccess::$user->rights->propal->lire) {
                throw new RestException(401);
            }

            $object = new Propal($this->db);
            $result=$object->fetch($id, $ref);
            if ( ! $result ) {
                throw new RestException(404, 'Proposal not found');
            }

            $upload_dir = $conf->propal->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'propal');
        }
        else if ($modulepart == 'commande' || $modulepart == 'order')
        {
            require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

            if (!DolibarrApiAccess::$user->rights->commande->lire) {
                throw new RestException(401);
            }

            $object = new Commande($this->db);
            $result=$object->fetch($id, $ref);
            if ( ! $result ) {
                throw new RestException(404, 'Order not found');
            }

            $upload_dir = $conf->commande->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'commande');
        }
        else if ($modulepart == 'shipment' || $modulepart == 'expedition')
        {
            require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

            if (!DolibarrApiAccess::$user->rights->expedition->lire) {
                throw new RestException(401);
            }

            $object = new Expedition($this->db);
            $result=$object->fetch($id, $ref);
            if ( ! $result ) {
                throw new RestException(404, 'Shipment not found');
            }

            $upload_dir = $conf->expedition->dir_output . "/sending/" . get_exdir(0, 0, 0, 1, $object, 'shipment');
        }
        else if ($modulepart == 'facture' || $modulepart == 'invoice')
        {
            require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

            if (!DolibarrApiAccess::$user->rights->facture->lire) {
                throw new RestException(401);
            }

            $object = new Facture($this->db);
            $result=$object->fetch($id, $ref);
            if ( ! $result ) {
                throw new RestException(404, 'Invoice not found');
            }

            $upload_dir = $conf->facture->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'invoice');
        }
        else if ($modulepart == 'agefodd')
        {
            if (!DolibarrApiAccess::$user->rights->agefodd->lire) {
                throw new RestException(401);
            }

            switch ($object_type)
            {
                case "session" :
                    $upload_dir = $conf->agefodd->dir_output . "/" .$id;
                    break;

                case "trainee" :
                    $upload_dir = $conf->agefodd->dir_output . "/trainee/" .$id;
                    break;

                case "trainer" :
                    $upload_dir = $conf->agefodd->dir_output . "/trainer/" .$id;
                    break;

                case "training" :
                    $upload_dir = $conf->agefodd->dir_output . "/training/" .$id;
                    break;

                case "place" :
                    $upload_dir = $conf->agefodd->dir_output . "/place/" .$id;
                    break;
            }

        }
        else
        {
            throw new RestException(500, 'Modulepart '.$modulepart.' not implemented yet.');
        }

        $filearray=dol_dir_list($upload_dir,"files",0,'','',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
        if (empty($filearray)) {
            throw new RestException(404, 'Search for '.$object_type.' with Id '.$id.' does not return any document.');
        }

        foreach ($filearray as &$file)
        {
            $file['pathtodownload'] = substr($file['fullname'], strrpos($file['fullname'], 'agefodd/') + 8);
        }

        return $filearray;
    }

    /**
     * Upload a file.
     *
     * Test sample 1: { "filename": "mynewfile.txt", "modulepart": "facture", "ref": "FA1701-001", "subdir": "", "filecontent": "content text", "fileencoding": "", "overwriteifexists": "0" }.
     * Test sample 2: { "filename": "mynewfile.txt", "modulepart": "medias", "ref": "", "subdir": "image/mywebsite", "filecontent": "Y29udGVudCB0ZXh0Cg==", "fileencoding": "base64", "overwriteifexists": "0" }.
     *
     * @param   string  $filename           Name of file to create ('FA1705-0123.txt')
     * @param   string  $object_type        Type of object (can be "session", "trainee" or "trainer")
     * @param   string  $id                 ID of element
     * @param   string  $filecontent        File content (string with file content. An empty file will be created if this parameter is not provided)
     * @param   string  $fileencoding       File encoding (''=no encoding, 'base64'=Base 64) {@example '' or 'base64'}
     * @param   int 	$overwriteifexists  Overwrite file if exists (1 by default)
     *
     * @throws 200
     * @throws 400
     * @throws 401
     * @throws 404
     * @throws 500
     *
     */
    private function _upload_file($filename, $object_type, $id = 0, $filecontent='', $fileencoding='', $overwriteifexists=0)
    {
        global $db, $conf;

        /*var_dump($modulepart);
         var_dump($filename);
         var_dump($filecontent);
         exit;*/

        if(empty($filename))
        {
            throw new RestException(400, 'filename not provided.');
        }

        if(empty($object_type))
        {
            throw new RestException(400, 'object_type not provided.');
        }

        if (!in_array($object_type, array("session", "trainer", "trainee", "training", "place"))) {
            throw new RestException(400, 'Invalid object_type');
        }

        if(empty($id))
        {
            throw new RestException(400, 'id not provided.');
        }

        if (!DolibarrApiAccess::$user->rights->ecm->upload) {
            throw new RestException(401);
        }

        $newfilecontent = '';
        if (empty($fileencoding)) $newfilecontent = $filecontent;
        if ($fileencoding == 'base64') $newfilecontent = base64_decode($filecontent);

        $original_file = dol_sanitizeFileName($filename);

        // Define $uploadir
        $object = null;
        $entity = DolibarrApiAccess::$user->entity;

        switch ($object_type)
        {
            case "session" :
                $upload_dir = $conf->agefodd->dir_output . "/" .$id;
                break;

            case "trainee" :
                $upload_dir = $conf->agefodd->dir_output . "/trainee/" .$id;
                break;

            case "trainer" :
                $upload_dir = $conf->agefodd->dir_output . "/trainer/" .$id;
                break;

            case "training" :
                $upload_dir = $conf->agefodd->dir_output . "/training/" .$id;
                break;

            case "place" :
                $upload_dir = $conf->agefodd->dir_output . "/place/" .$id;
                break;
        }

        if (empty($upload_dir) || $upload_dir == '/')
        {
            throw new RestException(500, 'This value of modulepart does not support yet usage of ref. Check modulepart parameter or try to use subdir parameter instead of ref.');
        }

        // $original_file here is still value of filename without any dir.

        $upload_dir = dol_sanitizePathName($upload_dir);

        $destfile = $upload_dir . '/' . $original_file;
        $destfiletmp = DOL_DATA_ROOT.'/agefodd/temp/' . $original_file;
        dol_delete_file($destfiletmp);
        //var_dump($original_file);exit;

        if (!dol_is_dir(dirname($destfile))) {
            dol_mkdir(dirname($destfile));
            //throw new RestException(401, 'Directory not exists : '.dirname($destfile));
        }

        if (! $overwriteifexists && dol_is_file($destfile))
        {
            throw new RestException(500, "File with name '".$original_file."' already exists.");
        }

        $fhandle = @fopen($destfiletmp, 'w');
        if ($fhandle)
        {
            $nbofbyteswrote = fwrite($fhandle, $newfilecontent);
            fclose($fhandle);
            @chmod($destfiletmp, octdec($conf->global->MAIN_UMASK));
        }
        else
        {
            throw new RestException(500, "Failed to open file '".$destfiletmp."' for write");
        }

        $result = dol_move($destfiletmp, $destfile, 0, $overwriteifexists, 1);
        if (! $result)
        {
            throw new RestException(500, "Failed to move file into '".$destfile."'");
        }

        return "The file " . dol_basename($destfile) . " has been successfully attached to " . $object_type . " " . $id;
    }

    /**
     * Clean sensible object datas
     *
     * @param   Categorie  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
    function _cleanObjectDatas($object) {

        $object = parent::_cleanObjectDatas($object);

        // Remove fields not relevent to categories
        unset($object->country);
        unset($object->country_id);
        unset($object->country_code);
        unset($object->total_ht);
        unset($object->total_ht);
        unset($object->total_localtax1);
        unset($object->total_localtax2);
        unset($object->total_ttc);
        unset($object->total_tva);
        unset($object->lines);
        unset($object->fk_incoterms);
        unset($object->libelle_incoterms);
        unset($object->location_incoterms);
        unset($object->civility_id);
        //unset($object->name);
        //unset($object->lastname);
        //unset($object->firstname);
        unset($object->shipping_method_id);
        unset($object->fk_delivery_address);
        unset($object->cond_reglement);
        unset($object->cond_reglement_id);
        unset($object->mode_reglement_id);
        unset($object->barcode_type_coder);
        unset($object->barcode_type_label);
        unset($object->barcode_type_code);
        unset($object->barcode_type);
        unset($object->canvas);
        unset($object->cats);
        unset($object->motherof);
        unset($object->context);
        unset($object->socid);
        unset($object->thirdparty);
        unset($object->contact);
        unset($object->contact_id);
        unset($object->user);
        unset($object->fk_account);
        unset($object->fk_project);
        unset($object->note);
        unset($object->statut);
        unset($object->labelstatut);
        unset($object->labelstatut_short);

        return $object;
    }

    /**
     * Validate fields before create or update object
     *
     * @param array|null    $data           Data to validate
     * @param string        $objecttype     type of agefodd object
     * @return array
     *
     * @throws RestException
     */
    function _validate($data, $objecttype)
    {
        global $conf;

        if(empty($objecttype)) throw new RestException(503, "Can't guess what type of object to validate");
        if(!isset(Agefodd::$FIELDS[$objecttype])) throw new RestException(503, "Unknown object type to validate");

        $object = array();
        foreach (Agefodd::$FIELDS[$objecttype]['mandatoryFields'] as $field) {
            if (!isset($data[$field]) || empty($data[$field]) || $data[$field] == -1)
                throw new RestException(400, "$field field missing");
            $object[$field] = $data[$field];
        }
        return $object;
    }
}
