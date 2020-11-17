<?php
/* 
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
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
 * \file    dev/skeletons/agefoddformationcataloguemodules.class.php
 * \ingroup mymodule othermodule1 othermodule2
 * \brief   This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *          Put some comments here
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class Agefoddformationcataloguemodules
 *
 * Put here description of your class
 */
class Agefoddformationcataloguemodules extends CommonObject
{
	/**
	 * @var string Error code (or message)
	 * @deprecated
	 * @see Agefoddformationcataloguemodules::errors
	 */
	public $error;
	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'agefoddformationcataloguemodules';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'agefodd_formation_catalogue_modules';

	/**
	 * @var AgefoddformationcataloguemodulesLine[] Lines
	 */
	public $lines = array();

	/**
	 * @var int ID
	 */
	public $id;
	
	public $entity;
	public $fk_formation_catalogue;
	public $sort_order;
	public $title;
	public $content_text;
	public $duration;
	public $obj_peda;
	public $status;
	public $import_key;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $tms = '';

	

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
		return 1;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		// Clean parameters
		
		if (isset($this->entity)) {
			 $this->entity = trim($this->entity);
		}
		if (isset($this->fk_formation_catalogue)) {
			 $this->fk_formation_catalogue = trim($this->fk_formation_catalogue);
		}
		if (isset($this->sort_order)) {
			 $this->sort_order = trim($this->sort_order);
		}
		if (isset($this->title)) {
			 $this->title = trim($this->title);
		}
		if (isset($this->content_text)) {
			 $this->content_text = trim($this->content_text);
		}
		if (isset($this->duration)) {
			 $this->duration = trim($this->duration);
		}
		if (isset($this->obj_peda)) {
			 $this->obj_peda = trim($this->obj_peda);
		}
		if (isset($this->status)) {
			 $this->status = trim($this->status);
		}
		if (isset($this->import_key)) {
			 $this->import_key = trim($this->import_key);
		}
		if (isset($this->fk_user_author)) {
			 $this->fk_user_author = trim($this->fk_user_author);
		}
		if (isset($this->fk_user_mod)) {
			 $this->fk_user_mod = trim($this->fk_user_mod);
		}

		

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element . '(';
		
		$sql.= 'entity,';
		$sql.= 'fk_formation_catalogue,';
		$sql.= 'sort_order,';
		$sql.= 'title,';
		$sql.= 'content_text,';
		$sql.= 'duration,';
		$sql.= 'obj_peda,';
		$sql.= 'status,';
		$sql.= 'import_key,';
		$sql.= 'fk_user_author,';
		$sql.= 'datec,';
		$sql.= 'fk_user_mod';

		
		$sql .= ') VALUES (';
		
		$sql .= ' '.(! isset($this->entity)?'NULL':$this->entity).',';
		$sql .= ' '.(! isset($this->fk_formation_catalogue)?'NULL':$this->fk_formation_catalogue).',';
		$sql .= ' '.(! isset($this->sort_order)?'NULL':$this->sort_order).',';
		$sql .= ' '.(! isset($this->title)?'NULL':"'".$this->db->escape($this->title)."'").',';
		$sql .= ' '.(! isset($this->content_text)?'NULL':"'".$this->db->escape($this->content_text)."'").',';
		$sql .= ' '.(! isset($this->duration)?'NULL':"'".$this->db->escape($this->duration)."'").',';
		$sql .= ' '.(! isset($this->obj_peda)?'NULL':"'".$this->db->escape($this->obj_peda)."'").',';
		$sql .= ' '.(! isset($this->status)?'NULL':$this->status).',';
		$sql .= ' '.(! isset($this->import_key)?'NULL':"'".$this->db->escape($this->import_key)."'").',';
		$sql .= ' '.$user->id.',';
		$sql .= ' '."'".$this->db->idate(dol_now())."'".',';
		$sql .= ' '.$user->id;

		
		$sql .= ')';

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);

			if (!$notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action to call a trigger.

				//// Call triggers
				//$result=$this->call_trigger('MYOBJECT_CREATE',$user);
				//if ($result < 0) $error++;
				//// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id  Id object
	 * @param string $ref Ref
	 *
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		
		$sql .= " t.entity,";
		$sql .= " t.fk_formation_catalogue,";
		$sql .= " t.sort_order,";
		$sql .= " t.title,";
		$sql .= " t.content_text,";
		$sql .= " t.duration,";
		$sql .= " t.obj_peda,";
		$sql .= " t.status,";
		$sql .= " t.import_key,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";

		
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if (null !== $ref) {
			$sql .= ' WHERE t.ref = ' . '\'' . $this->db->escape($ref) . '\'';
		} else {
			$sql .= ' WHERE t.rowid = ' . $id;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				
				$this->entity = $obj->entity;
				$this->fk_formation_catalogue = $obj->fk_formation_catalogue;
				$this->sort_order = $obj->sort_order;
				$this->title = $obj->title;
				$this->content_text = $obj->content_text;
				$this->duration = $obj->duration;
				$this->obj_peda = $obj->obj_peda;
				$this->status = $obj->status;
				$this->import_key = $obj->import_key;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);

				
			}
			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int    $limit     offset limit
	 * @param int    $offset    offset limit
	 * @param array  $filter    filter array
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchAll($sortorder, $sortfield, $limit, $offset, array $filter = array())
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		
		$sql .= " t.entity,";
		$sql .= " t.fk_formation_catalogue,";
		$sql .= " t.sort_order,";
		$sql .= " t.title,";
		$sql .= " t.content_text,";
		$sql .= " t.duration,";
		$sql .= " t.obj_peda,";
		$sql .= " t.status,";
		$sql .= " t.import_key,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";

		
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element.' as t';

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key=='t.fk_formation_catalogue') {
					$sqlwhere [] = $key . ' =' . $this->db->escape($value);
				} else {
					$sqlwhere [] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
				
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' WHERE ' . implode(' AND ', $sqlwhere);
		}
		$sql .= ' ORDER BY ' . $sortfield . ' ' . $sortorder . ' ';
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$this->lines = array();

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new AgefoddformationcataloguemodulesLine();

				$line->id = $obj->rowid;
				
				$line->entity = $obj->entity;
				$line->fk_formation_catalogue = $obj->fk_formation_catalogue;
				$line->sort_order = $obj->sort_order;
				$line->title = $obj->title;
				$line->content_text = $obj->content_text;
				$line->duration = $obj->duration;
				$line->obj_peda = $obj->obj_peda;
				$line->status = $obj->status;
				$line->import_key = $obj->import_key;
				$line->fk_user_author = $obj->fk_user_author;
				$line->datec = $this->db->jdate($obj->datec);
				$line->fk_user_mod = $obj->fk_user_mod;
				$line->tms = $this->db->jdate($obj->tms);

				

				$this->lines[] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		// Clean parameters
		
		if (isset($this->entity)) {
			 $this->entity = trim($this->entity);
		}
		if (isset($this->fk_formation_catalogue)) {
			 $this->fk_formation_catalogue = trim($this->fk_formation_catalogue);
		}
		if (isset($this->sort_order)) {
			 $this->sort_order = trim($this->sort_order);
		}
		if (isset($this->title)) {
			 $this->title = trim($this->title);
		}
		if (isset($this->content_text)) {
			 $this->content_text = trim($this->content_text);
		}
		if (isset($this->duration)) {
			 $this->duration = trim($this->duration);
		}
		if (isset($this->obj_peda)) {
			 $this->obj_peda = trim($this->obj_peda);
		}
		if (isset($this->status)) {
			 $this->status = trim($this->status);
		}
		if (isset($this->import_key)) {
			 $this->import_key = trim($this->import_key);
		}
		if (isset($this->fk_user_author)) {
			 $this->fk_user_author = trim($this->fk_user_author);
		}
		if (isset($this->fk_user_mod)) {
			 $this->fk_user_mod = trim($this->fk_user_mod);
		}

		

		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';
		
		$sql .= ' entity = '.(isset($this->entity)?$this->entity:"null").',';
		$sql .= ' fk_formation_catalogue = '.(isset($this->fk_formation_catalogue)?$this->fk_formation_catalogue:"null").',';
		$sql .= ' sort_order = '.(isset($this->sort_order)?$this->sort_order:"null").',';
		$sql .= ' title = '.(isset($this->title)?"'".$this->db->escape($this->title)."'":"null").',';
		$sql .= ' content_text = '.(isset($this->content_text)?"'".$this->db->escape($this->content_text)."'":"null").',';
		$sql .= ' duration = '.(isset($this->duration)?"'".$this->db->escape($this->duration)."'":"null").',';
		$sql .= ' obj_peda = '.(isset($this->obj_peda)?"'".$this->db->escape($this->obj_peda)."'":"null").',';
		$sql .= ' status = '.(isset($this->status)?$this->status:"null").',';
		$sql .= ' import_key = '.(isset($this->import_key)?"'".$this->db->escape($this->import_key)."'":"null").',';
		$sql .= ' fk_user_author = '.(isset($this->fk_user_author)?$this->fk_user_author:"null").',';
		$sql .= ' datec = '.(! isset($this->datec) || dol_strlen($this->datec) != 0 ? "'".$this->db->idate($this->datec)."'" : 'null').',';
		$sql .= ' fk_user_mod = '.$user->id.',';
		$sql .= ' tms = '.(dol_strlen($this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : "'".$this->db->idate(dol_now())."'");

        
		$sql .= ' WHERE rowid=' . $this->id;

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}

		if (!$error && !$notrigger) {
			// Uncomment this and change MYOBJECT to your own tag if you
			// want this action calls a trigger.

			//// Call triggers
			//$result=$this->call_trigger('MYOBJECT_MODIFY',$user);
			//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
			//// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user      User that deletes
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		if (!$error) {
			if (!$notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//$result=$this->call_trigger('MYOBJECT_DELETE',$user);
				//if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
				//// End call triggers
			}
		}

		if (!$error) {
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $this->table_element;
			$sql .= ' WHERE rowid=' . $this->id;

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error ++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param int $fromid Id of object to clone
	 *
	 * @return int New id of clone
	 */
	public function createFromClone($fromid)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		global $user;
		$error = 0;
		$object = new Agefoddformationcataloguemodules($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		// Reset object
		$object->id = 0;

		// Clear fields
		// ...

		// Create clone
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$error ++;
			$this->errors = $object->errors;
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
		}

		// End
		if (!$error) {
			$this->db->commit();

			return $object->id;
		} else {
			$this->db->rollback();

			return - 1;
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;
		
		$this->entity = '';
		$this->fk_formation_catalogue = '';
		$this->sort_order = '';
		$this->title = '';
		$this->content_text = '';
		$this->duration = '';
		$this->obj_peda = '';
		$this->status = '';
		$this->import_key = '';
		$this->fk_user_author = '';
		$this->datec = '';
		$this->fk_user_mod = '';
		$this->tms = '';

		
	}
	
	/**
	 * Retrun max +1 sort roder for a letters model
	 * 
	 * @return int	max + 1
	 */
	public function findMaxSortOrder() {
		$sql = "SELECT";
		$sql.= " MAX(t.sort_order) as maxsortorder";
		$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql.= " WHERE t.fk_formation_catalogue = ".$this->fk_formation_catalogue;
		
		dol_syslog(get_class($this)."::findMaxSortOrder sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		$max=0;
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
		
				$max = $obj->maxsortorder;
		
		
			}
			$this->db->free($resql);
			
			return $max+1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::findMaxSortOrder ".$this->error, LOG_ERR);
			return -1;
		}
	}

}

/**
 * Class AgefoddformationcataloguemodulesLine
 */
class AgefoddformationcataloguemodulesLine
{
	/**
	 * @var int ID
	 */
	public $id;
	
	public $entity;
	public $fk_formation_catalogue;
	public $sort_order;
	public $title;
	public $content_text;
	public $duration;
	public $obj_peda;
	public $status;
	public $import_key;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $tms = '';

}
