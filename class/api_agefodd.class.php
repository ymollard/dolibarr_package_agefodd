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

 require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
 require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
 
 dol_include_once('/agefodd/class/agsession.class.php');

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
		$this->session = new Agsession($this->db);
		
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
     * List Sessions
     * 
     * Get a list of Agefodd Sessions
     *
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param int		$limit		Limit for list
     * @param int		$page		Page number
     * @return array                Array of category objects
     * 
     * @url     GET /sessions/
	 * @throws RestException
     */
    function sessionindex($sortfield = "s.rowid", $sortorder = 'ASC', $limit = 100, $page = 0) {
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
                $obj_ret[] = parent::_cleanObjectDatas($line);
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

		if ($mode == "clone"){
		    if (in_array('id', $request_data['request_data'])){
		        return $this->_cloneSession((int)$v);
		    } else throw new RestException(404, 'session not found');

		} else {
		    // Check mandatory fields
            $result = $this->_validate($request_data, 'session');
            
            foreach($request_data as $field => $value) {
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
        
        if( !$this->session->remove($id))
        {
            throw new RestException(500);
        }
        
        return array(
            'success' => array(
                'code' => 200,
                'message' => 'session deleted'
            )
        );
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
        }
        
        $object = array();
        foreach ($Tfields as $field) {
            if (!isset($data[$field]) || empty($data[$field]) || $data[$field] <= 0)
                throw new RestException(400, "$field field missing");
            $object[$field] = $data[$field];
        }
        return $object;
    }
}
