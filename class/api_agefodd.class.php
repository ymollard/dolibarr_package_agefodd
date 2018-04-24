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
 dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
 dol_include_once('/agefodd/class/agefodd_formateur.class.php');

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
    static $SESSIONFIELDS = array(
        'fk_formation_catalogue',
        'fk_session_place'
    );
    
    static $TRAINEEFIELDS = array(
        'nom'
        ,'prenom'
        ,'civilite'
        ,'socid'
        //,'fk_user_mod'
        //,'datec'
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
		$this->session = new Agsession($this->db);            // agefodd session
		$this->trainee = new Agefodd_stagiaire($this->db);    // agefodd trainee
		$this->trainer = new Agefodd_teacher($this->db);      // agefodd teacher
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
    function sessionIndex($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 100, $page = 0) {
        global $db, $conf;
        
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
    function sessionFilteredIndex($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $filter=array(), $user = 0, $array_options_keys=array()) {
        global $db, $conf;
        
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
            $u->fetch($user);
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
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401);
        }
        
        $result = $this->session->fetch($id);
        if( $result < 0 || empty($this->session->id)) {
            throw new RestException(404, 'session not found');
        }
        
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        
        return $this->_cleanObjectDatas($this->session);
    }

    /**
     * Create session object
     * 
     * @url     POST /sessions/
     * 
     * 
     * @param string    $mode           create or clone 
     * @param array     $request_data   Request data
     * 
     * @return int      ID of session
     */
    function postSession($mode = 'create', $request_data)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
     * Update session
     * 
     * @url     PUT /sessions/{id}
     * 
     * @param int   $id             Id of session to update
     * @param array $request_data   Datas   
     * @return int 
     */
    function putSession($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->creer) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        
        $result = $this->session->fetch($id);
        if( $result < 0 || empty($this->session->id) ) {
            throw new RestException(404, 'session not found');
        }
        
        foreach($request_data as $field => $value) {
            if ($field !== "array_options") {
                $this->session->$field = $value;
            } else {
                foreach ($value as $option => $val) $this->session->array_options[$option] = $val;
            }
        }
        
        if($this->session->update(DolibarrApiAccess::$user, 1))
            return $this->getSession($id);
            
            return false;
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
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401);
        }
        $result = $this->session->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'session not found');
        }
        
        //if( ! DolibarrApi::_checkAccessToResource('agefodd_session',$this->session->id)) {
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
    function traineeIndex($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 100, $page = 0) {
        global $db, $conf;
        
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
     * 
     * @return array                Array of trainees objects
     *
     * @url     POST /trainees/filter
     * @throws RestException
     */
    function traineeFilteredIndex($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $filter = array()) {
        global $db, $conf;
        
        $obj_ret = array();
        
        $offset = 0;
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;
            
        }
        
        $result = $this->trainee->fetch_all($sortorder, $sortfield, $limit, $offset, $filter);
        
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
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401);
        }
        
        $result = $this->trainee->fetch($id);
        if( $result < 0 || empty($this->trainee->id)) {
            throw new RestException(404, 'trainee not found');
        }
        
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401);
        }
        
        $result = $this->session->fetch_session_per_trainee($id);
        if( $result < 0) {
            throw new RestException(404, 'trainee not found');
        } elseif (count($this->session->lines) == 0) {
            throw new RestException(404, 'no session for this trainee');
        }
        
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401);
        }
        
        $result = $this->trainee->fetch($id);
        if($result>0) $this->trainee->info($id);
        if( $result < 0 || empty($this->trainee->id)) {
            throw new RestException(404, 'trainee not found');
        }
        
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        
        $result = $this->trainee->fetch($id);
        if( $result < 0 || empty($this->trainee->id) ) {
            throw new RestException(404, 'trainee not found');
        }
        
        foreach($request_data as $field => $value) {
            if ($field !== "array_options") {
                $this->trainee->$field = $value;
            } else {
                foreach ($value as $option => $val) $this->trainee->array_options[$option] = $val;
            }
        }
        
        if($this->trainee->update(DolibarrApiAccess::$user, 1))
            return $this->getTrainee($id);
            
            return false;
    }
    
    /**
     * Delete trainee
     *
     * @url	DELETE /trainee/{id}
     *
     * @param int $id   trainee ID
     * @return array
     */
    function deleteTrainee($id)
    {
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401);
        }
        $result = $this->trainee->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'trainee not found');
        }
        
        //if( ! DolibarrApi::_checkAccessToResource('agefodd_session',$this->session->id)) {
        if(! DolibarrApiAccess::$user->rights->agefodd->supprimer) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
     * @url     GET /trainer/
     * @throws RestException
     */
    function trainerIndex($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 100, $offset = 0, $arch = 0) {
        global $db, $conf;
        
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
    function trainerFilteredIndex($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 100, $offset = 0, $arch = 0, $filter = array()) {
        global $db, $conf;
        
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
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401);
        }
        
        $result = $this->trainer->fetch($id);
        if( $result < 0 || empty($this->trainer->id)) {
            throw new RestException(404, 'trainer not found');
        }
        
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        
        return $this->_cleanObjectDatas($this->trainer);
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
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401);
        }
        
        $result = $this->trainer->fetch($id);
        if($result>0) $this->trainer->info($id);
        if( $result < 0 || empty($this->trainer->id)) {
            throw new RestException(404, 'trainer not found');
        }
        
        //if( ! DolibarrApi::_checkAccessToResource('agefodd',$this->session->id, 'agefodd_session')) {
        if(! DolibarrApiAccess::$user->rights->agefodd->lire) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        
        return $this->_cleanObjectDatas($this->trainer);
    }
    
    /***************************************************************** Formation Part *****************************************************************/
    
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
     * @return 	array|mixed data without useless information
     *
     * @url	POST /thirdparties/{id}/sessions/
     * @throws 	RestException
     */
    function getThirdpartiesSessions($id, $sortorder = "ASC", $sortfield = "s.rowid", $limit = 100, $offset = 0, $filter = '')
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
        unset($object->name);
        unset($object->lastname);
        unset($object->firstname);
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
        
        switch ($objecttype){
            case 'session' :
                $Tfields = Agefodd::$SESSIONFIELDS;
                break;
                
            case 'trainee' :
                $Tfields = Agefodd::$TRAINEEFIELDS;
                break;
        }
        
        $object = array();
        foreach ($Tfields as $field) {
            if (!isset($data[$field]) || empty($data[$field]) || $data[$field] == -1)
                throw new RestException(400, "$field field missing");
            $object[$field] = $data[$field];
        }
        return $object;
    }
}
