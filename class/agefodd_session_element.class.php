<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file dev/skeletons/agefoddsessionelement.class.php
 * \ingroup mymodule othermodule1 othermodule2
 * \brief This file is an example for a CRUD class file (Create/Read/Update/Delete)
 * Initialy built by build_class_from_table on 2013-10-31 01:49
 */

// Put here all includes required by your class file
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
// require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
// require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

/**
 * Put here description of your class
 */
class Agefodd_session_element extends CommonObject {
	var $db; // !< To store db handler
	var $error; // !< To return error code (or message)
	var $errors = array (); // !< To return several error codes (or messages)
	var $element = 'agefodd_session_element'; // !< Id that identify managed objects
	var $table_element = 'agefodd_session_element'; // !< Name of table without prefix where object is stored
	var $id;
	var $fk_session_agefodd;
	var $fk_soc;
	var $element_type;
	var $fk_element;
	var $fk_user_author;
	var $datec = '';
	var $fk_user_mod;
	var $tms = '';
	var $propalref = '';
	var $comref = '';
	var $facnumber = '';
	var $facfournnumber = '';
	var $propal_sign_amount;
	var $propal_amount;
	var $order_amount;
	var $invoice_ongoing_amount;
	var $invoice_payed_amount;
	
	var $lines = array ();

	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	function __construct($db) {

		$this->db = $db;
		return 1;
	}

	/**
	 * Create object into database
	 *
	 * @param User $user that creates
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	function create($user, $notrigger = 0) {

		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		
		if (isset ( $this->fk_session_agefodd ))
			$this->fk_session_agefodd = trim ( $this->fk_session_agefodd );
		if (isset ( $this->fk_soc ))
			$this->fk_soc = trim ( $this->fk_soc );
		if (isset ( $this->element_type ))
			$this->element_type = trim ( $this->element_type );
		if (isset ( $this->fk_element ))
			$this->fk_element = trim ( $this->fk_element );
		if (isset ( $this->fk_user_author ))
			$this->fk_user_author = trim ( $this->fk_user_author );
		if (isset ( $this->fk_user_mod ))
			$this->fk_user_mod = trim ( $this->fk_user_mod );
			
			// Check parameters
			// Put here code to add control on parameters values
			
		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_session_element(";
		
		$sql .= "fk_session_agefodd,";
		$sql .= "fk_soc,";
		$sql .= "element_type,";
		$sql .= "fk_element,";
		$sql .= "fk_user_author,";
		$sql .= "datec,";
		$sql .= "fk_user_mod";
		
		$sql .= ") VALUES (";
		
		$sql .= " " . (! isset ( $this->fk_session_agefodd ) ? 'NULL' : "'" . $this->fk_session_agefodd . "'") . ",";
		$sql .= " " . (! isset ( $this->fk_soc ) ? 'NULL' : "'" . $this->fk_soc . "'") . ",";
		$sql .= " " . (! isset ( $this->element_type ) ? 'NULL' : "'" . $this->db->escape ( $this->element_type ) . "'") . ",";
		$sql .= " " . (! isset ( $this->fk_element ) ? 'NULL' : "'" . $this->fk_element . "'") . ",";
		$sql .= " " . $user->id . ",";
		$sql .= " '" . $this->db->idate ( dol_now () ) . "',";
		$sql .= " " . $user->id;
		
		$sql .= ")";
		
		$this->db->begin ();
		
		dol_syslog ( get_class ( $this ) . "::create sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror ();
		}
		
		if (! $error) {
			$this->id = $this->db->last_insert_id ( MAIN_DB_PREFIX . "agefodd_session_element" );
			
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
				
				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);fetch_by_session sql=SELECT rowid, fk_element, element_type, fk_soc FROM
			// llx_agefodd_session_element WHERE fk_session_agefodd=419
				// $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog ( get_class ( $this ) . "::create " . $errmsg, LOG_ERR );
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback ();
			return - 1 * $error;
		} else {
			$this->db->commit ();
			$this->updateSellingPrice ( $user );
			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch($id) {

		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";
		
		$sql .= " t.fk_session_agefodd,";
		$sql .= " t.fk_soc,";
		$sql .= " t.element_type,";
		$sql .= " t.fk_element,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms,";
		
		$sql .= " propal.ref as propalref,";
		$sql .= " commande.ref as comref,";
		$sql .= " facture.facnumber,";
		$sql .= " facture_fourn.ref as facfournnumber";
		
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element as t";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "propal as propal ON propal.rowid=t.fk_element AND t.element_type='propal'";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "commande as commande ON commande.rowid=t.fk_element AND t.element_type='order'";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "facture as facture ON facture.rowid=t.fk_element AND t.element_type='invoice'";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "facture_fourn as facture_fourn ON facture_fourn.rowid=t.fk_element AND t.element_type LIKE 'invoice_supplier_%'";
		$sql .= " WHERE t.rowid = " . $id;
		
		dol_syslog ( get_class ( $this ) . "::fetch sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if ($resql) {
			if ($this->db->num_rows ( $resql )) {
				$obj = $this->db->fetch_object ( $resql );
				
				$this->id = $obj->rowid;
				
				$this->fk_session_agefodd = $obj->fk_session_agefodd;
				$this->fk_soc = $obj->fk_soc;
				$this->element_type = $obj->element_type;
				$this->fk_element = $obj->fk_element;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate ( $obj->datec );
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate ( $obj->tms );
				$this->propalref = $obj->propalref;
				$this->comref = $obj->comref;
				$this->facnumber = $obj->facnumber;
				$this->facfournnumber = $obj->facfournnumber;
			}
			$this->db->free ( $resql );
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::fetch " . $this->error, LOG_ERR );
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $socid Session
	 * @param string $type is default
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_element_per_soc($socid, $type = 'bc') {

		global $langs;
		
		$sql = "SELECT";
		if ($type == 'bc') {
			$sql .= " c.rowid, c.fk_soc, c.ref";
			$sql .= " FROM " . MAIN_DB_PREFIX . "commande as c";
		}
		if ($type == 'fac') {
			$sql .= " f.rowid, f.fk_soc, f.facnumber";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
		}
		if ($type == 'prop') {
			$sql .= " f.rowid, f.fk_soc, f.ref";
			$sql .= " FROM " . MAIN_DB_PREFIX . "propal as f";
		}
		$sql .= " WHERE fk_soc = " . $socid;
		
		dol_syslog ( get_class ( $this ) . "::fetch_element_per_soc sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows ( $resql );
			$i = 0;
			for($i = 0; $i < $num; $i ++) {
				$line = new AgefoddElementLine ();
				
				$obj = $this->db->fetch_object ( $resql );
				$line->id = $obj->rowid;
				$line->socid = $obj->fk_soc;
				if ($type == 'bc' || $type == 'prop') {
					$line->ref = $obj->ref;
				} else {
					$line->ref = $obj->facnumber;
				}
				
				$this->lines [$i] = $line;
			}
			$this->db->free ( $resql );
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::fetch_element_per_soc " . $this->error, LOG_ERR );
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $id ob object
	 * @param string $type is default
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_element_by_id($id, $type = 'bc') {

		global $langs;
		
		if (! empty ( $id )) {
			$sql = "SELECT";
			$sql .= " rowid, fk_session_agefodd, fk_soc ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element";
			$sql .= " WHERE fk_element = " . $id;
			if ($type == 'bc') {
				$sql .= " AND element_type='order'";
			}
			if ($type == 'fac') {
				$sql .= " AND element_type='invoice'";
			}
			if ($type == 'prop') {
				$sql .= " AND element_type='propal'";
			}
			if ($type == 'invoice_supplier_trainer') {
				$sql .= " AND element_type='invoice_supplier_trainer'";
			}
			
			dol_syslog ( get_class ( $this ) . "::fetch_element_by_id sql=" . $sql, LOG_DEBUG );
			$resql = $this->db->query ( $sql );
			if ($resql) {
				$this->lines = array ();
				$num = $this->db->num_rows ( $resql );
				$i = 0;
				for($i = 0; $i < $num; $i ++) {
					$line = new AgefoddElementLine ();
					
					$obj = $this->db->fetch_object ( $resql );
					$line->id = $obj->rowid;
					$line->socid = $obj->fk_soc;
					$line->fk_session_agefodd = $obj->fk_session_agefodd;
					
					$this->lines [$i] = $line;
				}
				$this->db->free ( $resql );
				return 1;
			} else {
				$this->error = "Error " . $this->db->lasterror ();
				dol_syslog ( get_class ( $this ) . "::fetch_element_by_id " . $this->error, LOG_ERR );
				return - 1;
			}
		} else {
			return 1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $idsession session id
	 * @param int $idsoc
	 * @param string type order,invoice,propal,invoice_supplier
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_by_session_by_thirdparty($idsession, $idsoc = 0, $type = '') {

		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";
		
		$sql .= " t.fk_session_agefodd,";
		$sql .= " t.fk_soc,";
		$sql .= " t.element_type,";
		$sql .= " t.fk_element,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms,";
		
		$sql .= " propal.ref as propalref,";
		$sql .= " commande.ref as comref,";
		$sql .= " facture.facnumber";
		
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element as t";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "propal as propal ON propal.rowid=t.fk_element AND t.element_type='propal'";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "commande as commande ON commande.rowid=t.fk_element AND t.element_type='order'";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "facture as facture ON facture.rowid=t.fk_element AND t.element_type='invoice'";
		$sql .= " WHERE t.fk_session_agefodd = " . $idsession;
		if (! empty ( $idsoc )) {
			$sql .= " AND t.fk_soc = " . $idsoc;
		}
		if (! empty ( $type )) {
			$sql .= ' AND t.element_type=\'' . $type . '\'';
		}
		
		dol_syslog ( get_class ( $this ) . "::fetch_by_session_by_thirdparty sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if ($resql) {
			$num = $this->db->num_rows ( $resql );
			
			$this->lines = array ();
			
			while ( $obj = $this->db->fetch_object ( $resql ) ) {
				
				$line = new AgefoddSessionElementLine ();
				
				$line->id = $obj->rowid;
				
				$line->fk_session_agefodd = $obj->fk_session_agefodd;
				$line->fk_soc = $obj->fk_soc;
				$line->element_type = $obj->element_type;
				$line->fk_element = $obj->fk_element;
				$line->fk_user_author = $obj->fk_user_author;
				$line->datec = $this->db->jdate ( $obj->datec );
				$line->fk_user_mod = $obj->fk_user_mod;
				$line->tms = $this->db->jdate ( $obj->tms );
				$line->propalref = $obj->propalref;
				$line->comref = $obj->comref;
				$line->facnumber = $obj->facnumber;
				
				$this->lines [] = $line;
			}
			$this->db->free ( $resql );
			
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::fetch_by_session_by_thirdparty " . $this->error, LOG_ERR );
			return - 1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $idsession session id
	 * @param int $idsoc
	 * @param string type order,invoice,propal,invoice_supplier
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_invoice_supplier_by_thridparty($idsoc) {

		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid";
		$sql .= " ,t.ref";
		$sql .= " ,t.ref_supplier";
		
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as t";
		$sql .= " WHERE t.fk_soc = " . $idsoc;
		
		dol_syslog ( get_class ( $this ) . "::fetch_invoice_supplier_by_thridparty sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if ($resql) {
			$num = $this->db->num_rows ( $resql );
			
			$this->lines = array ();
			
			while ( $obj = $this->db->fetch_object ( $resql ) ) {
				
				$line = new AgefoddElementInvoiceLine ();
				
				$line->id = $obj->rowid;
				
				$line->ref = $obj->ref;
				$line->ref_supplier = $obj->ref_supplier;
				
				$this->lines [] = $line;
			}
			$this->db->free ( $resql );
			
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::fetch_invoice_supplier_by_thridparty " . $this->error, LOG_ERR );
			return - 1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param User $user that modifies
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function update($user = 0, $notrigger = 0) {

		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		
		if (isset ( $this->fk_session_agefodd ))
			$this->fk_session_agefodd = trim ( $this->fk_session_agefodd );
		if (isset ( $this->fk_soc ))
			$this->fk_soc = trim ( $this->fk_soc );
		if (isset ( $this->element_type ))
			$this->element_type = trim ( $this->element_type );
		if (isset ( $this->fk_element ))
			$this->fk_element = trim ( $this->fk_element );
		if (isset ( $this->fk_user_author ))
			$this->fk_user_author = trim ( $this->fk_user_author );
		if (isset ( $this->fk_user_mod ))
			$this->fk_user_mod = trim ( $this->fk_user_mod );
			
			// Check parameters
			// Put here code to add a control on parameters values
			
		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_element SET";
		
		$sql .= " fk_session_agefodd=" . (isset ( $this->fk_session_agefodd ) ? $this->fk_session_agefodd : "null") . ",";
		$sql .= " fk_soc=" . (isset ( $this->fk_soc ) ? $this->fk_soc : "null") . ",";
		$sql .= " element_type=" . (isset ( $this->element_type ) ? "'" . $this->db->escape ( $this->element_type ) . "'" : "null") . ",";
		$sql .= " fk_element=" . (isset ( $this->fk_element ) ? $this->fk_element : "null") . ",";
		$sql .= " fk_user_mod=" . $user->id;
		
		$sql .= " WHERE rowid=" . $this->id;
		
		$this->db->begin ();
		
		dol_syslog ( get_class ( $this ) . "::update sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if (! $resql) {
			$error ++;
			$this->errors [] = "Error " . $this->db->lasterror ();
		}
		
		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
				
				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog ( get_class ( $this ) . "::update " . $errmsg, LOG_ERR );
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback ();
			return - 1 * $error;
		} else {
			$this->db->commit ();
			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user that deletes
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger = 0) {

		global $conf, $langs;
		$error = 0;
		
		$this->db->begin ();
		
		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
				
				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}
		
		if (! $error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_session_element";
			$sql .= " WHERE rowid=" . $this->id;
			
			dol_syslog ( get_class ( $this ) . "::delete sql=" . $sql );
			$resql = $this->db->query ( $sql );
			if (! $resql) {
				$error ++;
				$this->errors [] = "Error " . $this->db->lasterror ();
			}
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog ( get_class ( $this ) . "::delete " . $errmsg, LOG_ERR );
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback ();
			return - 1 * $error;
		} else {
			$this->db->commit ();
			$this->updateSellingPrice ( $user );
			return 1;
		}
	}

	/**
	 * Set invoice ref where propal or order is already linked
	 *
	 * @param int $id to find
	 * @param int $type or propal
	 * @param int $invoiceid to link
	 * @return int <0 if KO, >0 if OK
	 */
	function add_invoice($user, $id, $type, $invoiceid) {

		global $langs;
		
		$sql = "SELECT";
		$sql .= " f.rowid, f.fk_element, f.element_type, f.fk_soc, f.fk_session_agefodd ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element as f";
		if ($type == 'propal') {
			$sql .= " WHERE f.fk_element = " . $id . " AND f.element_type='propal'";
		}
		if ($type == 'commande') {
			$sql .= " WHERE f.fk_element = " . $id . " AND f.element_type='order'";
		}
		
		dol_syslog ( get_class ( $this ) . "::add_invoice sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		
		if ($resql) {
			if ($this->db->num_rows ( $resql )) {
				$obj = $this->db->fetch_object ( $resql );
				$this->fk_session_agefodd = $obj->fk_session_agefodd;
				$this->fk_soc = $obj->fk_soc;
				$this->fk_element = $invoiceid;
				$this->element_type = 'invoice';
				
				$result = $this->create ( $user );
				if ($result < 0) {
					return - 1;
				}
			}
			$this->db->free ( $resql );
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::add_invoice " . $this->error, LOG_ERR );
			return - 1;
		}
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param int $fromid of object to clone
	 * @return int id of clone
	 */
	function createFromClone($fromid) {

		global $user, $langs;
		
		$error = 0;
		
		$object = new Agefoddsessionelement ( $this->db );
		
		$this->db->begin ();
		
		// Load source object
		$object->fetch ( $fromid );
		$object->id = 0;
		$object->statut = 0;
		
		// Clear fields
		// ...
		
		// Create clone
		$result = $object->create ( $user );
		
		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$error ++;
		}
		
		if (! $error) {
		}
		
		// End
		if (! $error) {
			$this->db->commit ();
			return $object->id;
		} else {
			$this->db->rollback ();
			return - 1;
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	function initAsSpecimen() {

		$this->id = 0;
		
		$this->fk_session_agefodd = '';
		$this->fk_soc = '';
		$this->element_type = '';
		$this->fk_element = '';
		$this->fk_user_author = '';
		$this->datec = '';
		$this->fk_user_mod = '';
		$this->tms = '';
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $id ob object
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_by_session($id) {

		require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
		require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
		
		global $langs;
		
		$this->propal_sign_amount = 0;
		$this->propal_amount = 0;
		$this->order_amount = 0;
		$this->invoice_ongoing_amount = 0;
		$this->invoice_payed_amount = 0;
		
		$sql = "SELECT";
		$sql .= " rowid, fk_element, element_type, fk_soc ";
		
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element";
		$sql .= " WHERE fk_session_agefodd=" . $id;
		
		dol_syslog ( get_class ( $this ) . "::fetch_by_session sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows ( $resql );
			$i = 0;
			for($i = 0; $i < $num; $i ++) {
				
				$obj = $this->db->fetch_object ( $resql );
				
				if ($obj->element_type == 'order') {
					$order = new Commande ( $this->db );
					$order->fetch ( $obj->fk_element );
					$this->order_amount += $order->total_ht;
				}
				
				if ($obj->element_type == 'propal') {
					$proposal = new Propal ( $this->db );
					$proposal->fetch ( $obj->fk_element );
					if ($proposal->statut == 2) {
						$this->propal_sign_amount += $proposal->total_ht;
						dol_syslog ( get_class ( $this ) . "::fetch_by_session status=2 proposal->total_ht=" . $proposal->total_ht, LOG_DEBUG );
					} elseif ($proposal->statut != 4 ) {
						$this->propal_amount=+$proposal->total_ht;
					} 
					$this->propal_amount +=$proposal->total_ht;
				}
				
				if ($obj->element_type == 'invoice') {
					$facture = new Facture ( $this->db );
					$facture->fetch ( $obj->fk_element );
					if ($facture->statut == 2) {
						$this->invoice_payed_amount += $facture->total_ht;
						dol_syslog ( get_class ( $this ) . "::fetch_by_session status=2 facture->total_ht=" . $facture->total_ht, LOG_DEBUG );
					}
					if ($facture->statut == 1) {
						$this->invoice_ongoing_amount += $facture->total_ht;
						dol_syslog ( get_class ( $this ) . "::fetch_by_session status=1 facture->total_ht=" . $facture->total_ht, LOG_DEBUG );
					}
				}
			}
			$this->db->free ( $resql );
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::fetch_by_session " . $this->error, LOG_ERR );
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $id ob object
	 * @return int <0 if KO, >0 if OK
	 */
	function get_charges_amount($id,$catid=0) {

		global $langs;
	
		$total_charges=0;
		
		$sql = "SELECT";
		$sql .= " rowid, fk_element, element_type, fk_soc ";
	
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element";
		$sql .= " WHERE fk_session_agefodd=" . $id;
	
		dol_syslog ( get_class ( $this ) . "::fetch_by_session sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if ($resql) {
			$num = $this->db->num_rows ( $resql );
			$i = 0;
			for($i = 0; $i < $num; $i ++) {
	
				$obj = $this->db->fetch_object ( $resql );
	
				if ($obj->element_type == 'order') {
					/*$order = new Commande ( $this->db );
					$order->fetch ( $obj->fk_element );
					$this->order_amount += $order->total_ht;*/
				}
	
				if ($obj->element_type == 'propal') {
					$sqlcharges = "SELECT SUM(ldet.total_ht) as totalcharges FROM ".MAIN_DB_PREFIX."propaldet as ldet WHERE ldet.fk_propal=".$obj->fk_element;
					$sqlcharges .=	" AND ldet.fk_product IN (SELECT fk_product FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_categorie IN (".$catid."))";
					dol_syslog ( get_class ( $this ) . "::get_charges_amount sql=" . $sqlcharges, LOG_DEBUG );
					$resqlcharges = $this->db->query ( $sqlcharges ); 
					if ($resqlcharges){
						$objcharges = $this->db->fetch_object ( $sqlcharges );
						$total_charges=$objcharges->totalcharges;
						$this->db->free ( $resqlcharges );
					}
					else {
						$this->error = "Error " . $this->db->lasterror ();
						dol_syslog ( get_class ( $this ) . "::get_charges_amount " . $this->error, LOG_ERR );
						return - 1;
					}
				}
	
				if ($obj->element_type == 'invoice') {
					/*$facture = new Facture ( $this->db );
					$facture->fetch ( $obj->fk_element );
					if ($facture->statut == 2) {
						$this->invoice_payed_amount += $facture->total_ht;
						dol_syslog ( get_class ( $this ) . "::fetch_by_session status=2 facture->total_ht=" . $facture->total_ht, LOG_DEBUG );
					}
					if ($facture->statut == 1) {
						$this->invoice_ongoing_amount += $facture->total_ht;
						dol_syslog ( get_class ( $this ) . "::fetch_by_session status=1 facture->total_ht=" . $facture->total_ht, LOG_DEBUG );
					}*/
				}
			}
			$this->db->free ( $resql );
			return $total_charges;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::fetch_by_session " . $this->error, LOG_ERR );
			return - 1;
		}
	}

	/**
	 * update selling price
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function updateSellingPrice($user) {
		// Update session selling price
		$sell_price = 0;
		
		$result = $this->fetch_by_session ( $this->fk_session_agefodd );
		
		$sell_price = $this->invoice_payed_amount;
		dol_syslog ( get_class ( $this ) . "::updateSellingPrice invoice sell_price=" . $sell_price, LOG_DEBUG );
		
		if (empty ( $sell_price ))
			$sell_price = $this->order_amount;
		dol_syslog ( get_class ( $this ) . "::updateSellingPrice order sell_price=" . $sell_price, LOG_DEBUG );
		
		if (empty ( $sell_price ))
			$sell_price = $this->propal_sign_amount - $this->invoice_payed_amount;
		dol_syslog ( get_class ( $this ) . "::updateSellingPrice propal sell_price=" . $sell_price, LOG_DEBUG );
		
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session SET sell_price=\'' . price2num ( $sell_price ) . '\' WHERE rowid=' . $this->fk_session_agefodd;
		
		dol_syslog ( get_class ( $this ) . "::updateSellingPrice sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if (! $resql) {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::updateSellingPrice " . $this->error, LOG_ERR );
			return - 1;
		} else {
			return 1;
		}
	}
	
	/**
	 * Load object in memory from database
	 *
	 * @param int $id ob object
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_element_by_session($id) {
	
		require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
		require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
	
		global $langs;
	
	
		$sql = "SELECT";
		$sql .= " rowid, fk_element, element_type, fk_soc ";
	
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element";
		$sql .= " WHERE fk_session_agefodd=" . $id;
	
		dol_syslog ( get_class ( $this ) . "::fetch_element_by_session sql=" . $sql, LOG_DEBUG );
		$resql = $this->db->query ( $sql );
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows ( $resql );
			$i = 0;
			for($i = 0; $i < $num; $i ++) {
	
				$obj = $this->db->fetch_object ( $resql );
	
				$line = new AgefoddSessionElementLine();
				$line->id=$obj->rowid;
				$line->fk_session_agefodd=$id;
				$line->fk_soc=$obj->fk_soc;
				$line->element_type = $obj->element_type;
				$line->fk_element=$obj->fk_element;
				
				
				if ($obj->element_type == 'order') {
					$order = new Commande ( $this->db );
					$order->fetch ( $obj->fk_element );
					if (!empty($order->id)) {
						$line->urllink=$order->getNomUrl(1);
					}
				}
	
				if ($obj->element_type == 'propal') {
					$proposal = new Propal ( $this->db );
					$proposal->fetch ( $obj->fk_element );
					if (!empty($proposal->id)) {
						$line->urllink=$proposal->getNomUrl(1);
					}
				}
	
				if ($obj->element_type == 'invoice') {
					$facture = new Facture ( $this->db );
					$facture->fetch ( $obj->fk_element );
					if (!empty($facture->id)) {
						$line->urllink=$facture->getNomUrl(1);
					}
				}
				
				$this->lines[]=$line;
				
			}
			$this->db->free ( $resql );
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror ();
			dol_syslog ( get_class ( $this ) . "::fetch_element_by_session " . $this->error, LOG_ERR );
			return - 1;
		}
	}
}

class AgefoddSessionElementLine {
	var $id;
	var $fk_session_agefodd;
	var $fk_soc;
	var $element_type;
	var $fk_element;
	var $fk_user_author;
	var $datec = '';
	var $fk_user_mod;
	var $tms = '';
	var $propalref = '';
	var $comref = '';
	var $facnumber = '';
	var $urllink;
}

class AgefoddElementLine {
	var $id;
	var $socid;
	var $fk_session_agefodd;
	var $ref;
}

class AgefoddElementInvoiceLine {
	var $id;
	var $ref;
	var $ref_supplier;
}
?>
