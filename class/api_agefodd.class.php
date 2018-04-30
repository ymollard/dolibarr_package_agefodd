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
 
 dol_include_once('/agefodd/class/agsession.class.php');
 dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
 dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
 dol_include_once('/agefodd/class/agefodd_formateur.class.php');
 dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
 dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');

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
		$this->session = new Agsession($this->db);                            // agefodd session
		$this->trainee = new Agefodd_stagiaire($this->db);                    // agefodd trainee
		$this->traineeinsession = new Agefodd_session_stagiaire($this->db);   // traineeinsession
		$this->trainer = new Agefodd_teacher($this->db);                      // agefodd teacher
		$this->trainerinsession = new Agefodd_session_formateur($this->db);   // trainerinsession
		$this->training = new Formation($this->db);                           // agefodd training
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
		
		if ($mode == "createadm"){ // creation des taches administratives de la session passée en param
		    
		    if (in_array('id', array_keys($request_data['request_data']))){
		        $this->session->fetch((int)$request_data['request_data']['id']);
		        $result = $this->session->createAdmLevelForSession(DolibarrApiAccess::$user);
		        return empty($result) ? $this->getSession($this->session->id) : $result .' '. $this->session->error;
		    } else throw new RestException(404, 'session not found');
		    
		} elseif ($mode == "clone"){ // clone de la session passée en param
		    
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
     * 
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
     * @url     PUT /sessions/{id}/archive
     *
     * @param int   $id Id of session to archive
     *
     * @return 	array|mixed data without useless information
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
        
        $this->session->status = 4;
        if($this->session->updateArchive(DolibarrApiAccess::$user))
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
     * @param int $trainerid    ID of the trainer (same trainee ID used to add the trainee in session)
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
     * @url     PUT /trainers/{id}/archive
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
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
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
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
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
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
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
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
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
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
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
     *
     * @param string    $mode           create, clone, createobjpeda(create goals) or createadm (create admin tasks)
     * @param array     $request_data   Request data
     *
     * @return 	array|mixed data without useless information
     */
    function postTraining($mode = 'create', $request_data)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
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
        }
//         if ($mode == "createadm"){ // creation des taches administratives de la session passée en param
            
//             if (in_array('id', array_keys($request_data['request_data']))){
//                 $this->session->fetch((int)$request_data['request_data']['id']);
//                 $result = $this->session->createAdmLevelForSession(DolibarrApiAccess::$user);
//                 return empty($result) ? $this->getSession($this->session->id) : $result .' '. $this->session->error;
//             } else throw new RestException(404, 'session not found');
            
//         } elseif ($mode == "clone"){ // clone de la session passée en param
            
//             if (in_array('id', array_keys($request_data['request_data']))){
//                 return $this->_cloneSession((int)$request_data['request_data']['id']);
//             } else throw new RestException(404, 'session not found');
            
//         } else { //creation d'une session
//             // Check mandatory fields
//             $result = $this->_validate($request_data['request_data'], 'session');
            
//             foreach($request_data['request_data'] as $field => $value) {
//                 $this->session->$field = $value;
//             }
            
//             if ($this->session->create(DolibarrApiAccess::$user) < 0) {
//                 throw new RestException(500, 'Error when creating session', array_merge(array($this->session->error), $this->session->errors));
//             }
//             return $this->getSession($this->session->id);
//         }
        
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
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
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
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
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
    
    /***************************************************************** Place Part *****************************************************************/
    
    /***************************************************************** Contact Part *****************************************************************/
    
    /***************************************************************** Cursus Part *****************************************************************/
    
    /***************************************************************** Calendar Part *****************************************************************/
    
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
    
    /***************************************************************** Common Part *****************************************************************/
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
