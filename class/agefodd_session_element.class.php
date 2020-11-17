<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012-2016 Florian Henry <florian.henry@open-concept.pro>
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
	public $error; // !< To return error code (or message)
	public $errors = array (); // !< To return several error codes (or messages)
	public $element = 'agefodd_session_element'; // !< Id that identify managed objects
	public $table_element = 'agefodd_session_element'; // !< Name of table without prefix where object is stored
	public $id;
	public $fk_session_agefodd;
	public $fk_soc;
	public $element_type;
	public $fk_element;
	public $fk_sub_element;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $tms = '';
	public $propalref = '';
	public $comref = '';
	public $facnumber = '';
	public $facfournnumber = '';
	public $propal_sign_amount;
	public $propal_amount;
	public $order_amount;
	public $invoice_ongoing_amount;
	public $invoice_payed_amount;
	public $trainer_cost_amount;
	public $trip_cost_amount;
	public $room_cost_amount;
	public $nb_invoice_validated;
	public $invoicetrainerdraft;
	public $lines = array ();

	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	public function __construct($db) {
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
	public function create($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->fk_session_agefodd))
			$this->fk_session_agefodd = trim($this->fk_session_agefodd);
		if (isset($this->fk_soc))
			$this->fk_soc = trim($this->fk_soc);
		if (isset($this->element_type))
			$this->element_type = trim($this->element_type);
		if (isset($this->fk_sub_element))
			$this->fk_sub_element = trim($this->fk_sub_element);
		if (isset($this->fk_element))
			$this->fk_element = trim($this->fk_element);
		if (isset($this->fk_user_author))
			$this->fk_user_author = trim($this->fk_user_author);
		if (isset($this->fk_user_mod))
			$this->fk_user_mod = trim($this->fk_user_mod);

			// Check parameters
			// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_session_element(";

		$sql .= "fk_session_agefodd,";
		$sql .= "fk_soc,";
		$sql .= "element_type,";
		$sql .= "fk_element,";
		$sql .= "fk_sub_element,";
		$sql .= "fk_user_author,";
		$sql .= "datec,";
		$sql .= "fk_user_mod";

		$sql .= ") VALUES (";

		$sql .= " " . (! isset($this->fk_session_agefodd) ? 'NULL' : "'" . $this->fk_session_agefodd . "'") . ",";
		$sql .= " " . (! isset($this->fk_soc) ? 'NULL' : "'" . $this->fk_soc . "'") . ",";
		$sql .= " " . (! isset($this->element_type) ? 'NULL' : "'" . $this->db->escape($this->element_type) . "'") . ",";
		$sql .= " " . (! isset($this->fk_element) ? 'NULL' : $this->fk_element) . ",";
		$sql .= " " . (empty($this->fk_sub_element) ? 'NULL' : $this->fk_sub_element) . ",";
		$sql .= " " . $user->id . ",";
		$sql .= " '" . $this->db->idate(dol_now()) . "',";
		$sql .= " " . $user->id;

		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_session_element");

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
				dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			$this->updateSellingPrice($user);
			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id) {
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.fk_session_agefodd,";
		$sql .= " t.fk_soc,";
		$sql .= " t.element_type,";
		$sql .= " t.fk_element,";
		$sql .= " t.fk_sub_element,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms,";

		$sql .= " propal.ref as propalref,";
		$sql .= " commande.ref as comref,";
		if(floatval(DOL_VERSION) > 9){
            $sql .= " facture.ref as facnumber ,";
        }
		else{
		    $sql .= " facture.facnumber,";
        }
		$sql .= " facture_fourn.ref as facfournnumber";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element as t";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "propal as propal ON propal.rowid=t.fk_element AND t.element_type='propal'";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "commande as commande ON commande.rowid=t.fk_element AND t.element_type='order'";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "facture as facture ON facture.rowid=t.fk_element AND t.element_type='invoice'";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "facture_fourn as facture_fourn ON facture_fourn.rowid=t.fk_element AND t.element_type LIKE 'invoice_supplier_%'";
		$sql .= " WHERE t.rowid = " . $id;

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->fk_session_agefodd = $obj->fk_session_agefodd;
				$this->fk_soc = $obj->fk_soc;
				$this->element_type = $obj->element_type;
				$this->fk_element = $obj->fk_element;
				$this->fk_sub_element = $obj->fk_sub_element;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);
				$this->propalref = $obj->propalref;
				$this->comref = $obj->comref;
				$this->facnumber = $obj->facnumber;
				$this->facfournnumber = $obj->facfournnumber;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
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
	public function fetch_element_per_soc($socid, $type = 'bc') {
		$sql = "SELECT";
		if ($type == 'bc') {
			$sql .= " c.rowid, c.fk_soc, c.ref, c.date_creation as datec, c.total_ttc as amount, soc.nom as socname";
			$sql .= " FROM " . MAIN_DB_PREFIX . "commande as c";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as soc ON soc.rowid=c.fk_soc";

			require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
		}
		if ($type == 'fac') {
			$sql .= " f.rowid, f.fk_soc,  f.datec, f.total_ttc as amount, soc.nom as socname";

            if(floatval(DOL_VERSION) > 9){
                $sql .= " ,f.ref as ref";
            }
            else{
                $sql .= " ,f.facnumber as ref";
            }

			$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as soc ON soc.rowid=f.fk_soc";

			require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
		}
		if ($type == 'prop') {
			$sql .= " f.rowid, f.fk_soc, f.ref, f.datec, f.total as amount, soc.nom as socname";
			$sql .= " FROM " . MAIN_DB_PREFIX . "propal as f";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as soc ON soc.rowid=f.fk_soc";

			require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
		}
		$sql .= " WHERE (fk_soc = " . $socid . " OR fk_soc IN (SELECT parent FROM " . MAIN_DB_PREFIX . "societe WHERE rowid=" . $socid . "))";

		dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);

			for($i = 0; $i < $num; $i ++) {
				$line = new AgefoddElementLine();

				$obj = $this->db->fetch_object($resql);
				$line->id = $obj->rowid;
				$line->socid = $obj->fk_soc;
				$line->socname = $obj->socname;
				$line->ref = $obj->ref;
				$line->date = $this->db->jdate($obj->datec);
				$line->amount = $obj->amount;

				if ($type == 'fac') {
					$facture = new Facture($this->db);
					$facture->fetch($obj->rowid);
					$line->status = $facture->getLibStatut(1);
				}

				if ($type == 'bc') {
					$order = new Commande($this->db);
					$order->fetch($obj->rowid);
					$line->status = $order->getLibStatut(1);
				}

				if ($type == 'prop') {
					$proposal = new Propal($this->db);
					$proposal->fetch($obj->rowid);
					$line->status = $proposal->getLibStatut(1);
				}

				$this->lines[$i] = $line;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::".__METHOD__.' '. $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $id ob object
	 * @param string $type is default
	 * @param int $id_session id session
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_element_by_id($id, $type = 'bc', $id_session=0) {
		if (! empty($id)) {
			$sql = "SELECT";
			$sql .= " rowid, fk_session_agefodd, fk_soc, element_type ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element";
			$sql .= " WHERE fk_element = " . $id;
			if ($type == 'bc' || $type == 'order') {
				$sql .= " AND element_type='order'";
			} elseif ($type == 'fac' || $type == 'invoice') {
				$sql .= " AND element_type='invoice'";
			} elseif ($type == 'prop' || $type == 'propal') {
				$sql .= " AND element_type='propal'";
			} elseif ($type == 'invoice_supplier_trainer') {
				$sql .= " AND element_type='invoice_supplier_trainer'";
			} elseif ($type == 'invoice_supplier_room') {
				$sql .= " AND element_type='invoice_supplier_room'";
			} elseif ($type == 'invoice_supplier_missions') {
				$sql .= " AND element_type='invoice_supplier_missions'";
			} elseif ($type == 'invoice_supplier') {
				$sql .= " AND element_type LIKE 'invoice_supplier%' AND element_type NOT LIKE 'invoice_supplierline%'";
			}elseif ($type == 'invoice_supplierline') {
				$sql .= " AND element_type LIKE 'invoice_supplierline%'";
			} elseif ($type == 'order_supplier_trainer') {
				$sql .= " AND element_type='order_supplier_trainer'";
			} elseif ($type == 'invoice_supplier_room') {
				$sql .= " AND element_type='order_supplier_room'";
			} elseif ($type == 'order_supplier_missions') {
				$sql .= " AND element_type='order_supplier_missions'";
			} elseif ($type == 'order_supplier') {
				$sql .= " AND element_type LIKE 'order_supplier%' AND element_type NOT LIKE 'order_supplierline%'";
			}elseif ($type == 'order_supplierline') {
				$sql .= " AND element_type LIKE 'order_supplierline%'";
			}
			if (!empty($id_session)) {
				$sql .= " AND fk_session_agefodd = " . $id_session;
			}

			dol_syslog(get_class($this) . "::fetch_element_by_id", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->lines = array ();
				$num = $this->db->num_rows($resql);
				while ($obj = $this->db->fetch_object($resql)) {
					$line = new AgefoddElementLine();

					$line->id = $obj->rowid;
					$line->socid = $obj->fk_soc;
					$line->fk_session_agefodd = $obj->fk_session_agefodd;
					$line->element_type = $obj->element_type;

					$this->lines[] = $line;
				}
				$this->db->free($resql);
				return $num;
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::fetch_element_by_id " . $this->error, LOG_ERR);
				return - 1;
			}
		} else {
			return 1;
		}
	}

	/**
	 * Return 1 if all invoice validated
	 *
	 * @param int $session_id ob object
	 * @return int <0 if KO, >0 if OK
	 */
	public function check_all_invoice_validate($session_id) {
		if (! empty($id)) {
			$sql = "SELECT";
			$sql .= " fac.rowid, fac.fk_statut";
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element as sesselem";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture as fac ON sesselem.fk_element=fac.rowid ";
			$sql .= " WHERE sesselem.fk_session_agefodd = " . $session_id;
			$sql .= " AND sesselem.element_type='invoice'";

			dol_syslog(get_class($this) . "::check_all_invoice_validate", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->lines = array ();
				$num = $this->db->num_rows($resql);

				for($i = 0; $i < $num; $i ++) {

					$obj = $this->db->fetch_object($resql);
					if ($obj->fk_statut != 0) {
						$return = 1;
					} else {
						$return = 0;
						break;
					}

					$this->lines[$i] = $obj;
				}
				$this->db->free($resql);

				if (empty($num))
					$return = 0;

				return $return;
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::check_all_invoice_validate " . $this->error, LOG_ERR);
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
	public function fetch_by_session_by_thirdparty($idsession, $idsoc = 0, $type = '') {
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.fk_session_agefodd,";
		$sql .= " t.fk_soc,";
		$sql .= " t.element_type,";
		$sql .= " t.fk_element,";
		$sql .= " t.fk_sub_element,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms,";

		$sql .= " propal.ref as propalref,";
		$sql .= " commande.ref as comref,";

        if(floatval(DOL_VERSION) > 9){
            $sql .= " facture.ref as facnumber ";
        }
        else{
            $sql .= " facture.facnumber ";
        }

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element as t";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "propal as propal ON propal.rowid=t.fk_element AND t.element_type='propal'";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "commande as commande ON commande.rowid=t.fk_element AND t.element_type='order'";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "facture as facture ON facture.rowid=t.fk_element AND t.element_type='invoice'";
		$sql .= " WHERE t.fk_session_agefodd = " . $idsession;
		if (! empty($idsoc)) {
			$sql .= " AND t.fk_soc = " . $idsoc;
		}
		if (! empty($type)) {
			if(is_array($type)){
				$sql .= ' AND t.element_type IN (' . implode(',',$type) . ')';
			}else {
				$sql .= ' AND t.element_type=\'' . $type . '\'';
			}
		}

		dol_syslog(get_class($this) . "::fetch_by_session_by_thirdparty", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			$this->lines = array ();

			while ( $obj = $this->db->fetch_object($resql) ) {

				$line = new AgefoddSessionElementLine();

				$line->id = $obj->rowid;

				$line->fk_session_agefodd = $obj->fk_session_agefodd;
				$line->fk_soc = $obj->fk_soc;
				$line->element_type = $obj->element_type;
				$line->fk_element = $obj->fk_element;
				$line->fk_sub_element = $obj->fk_sub_element;
				$line->fk_user_author = $obj->fk_user_author;
				$line->datec = $this->db->jdate($obj->datec);
				$line->fk_user_mod = $obj->fk_user_mod;
				$line->tms = $this->db->jdate($obj->tms);
				$line->propalref = $obj->propalref;
				$line->comref = $obj->comref;
				$line->facnumber = $obj->facnumber;

				$this->lines[] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_by_session_by_thirdparty " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $idsoc
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_invoice_supplier_by_thridparty($idsoc) {
		$sql = "SELECT";
		$sql .= " t.rowid";
		$sql .= " ,t.ref";
		$sql .= " ,t.ref_supplier";

		$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as t";
		$sql .= " WHERE t.fk_soc = " . $idsoc;
		$sql .= ' ORDER BY t.ref';

		dol_syslog(get_class($this) . "::fetch_invoice_supplier_by_thridparty", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			$this->lines = array ();

			while ( $obj = $this->db->fetch_object($resql) ) {

				$line = new AgefoddElementInvoiceLine();

				$line->id = $obj->rowid;

				$line->ref = $obj->ref;
				$line->ref_supplier = $obj->ref_supplier;

				$this->lines[] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_invoice_supplier_by_thridparty " . $this->error, LOG_ERR);
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
	public function update($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->fk_session_agefodd))
			$this->fk_session_agefodd = trim($this->fk_session_agefodd);
		if (isset($this->fk_soc))
			$this->fk_soc = trim($this->fk_soc);
		if (isset($this->element_type))
			$this->element_type = trim($this->element_type);
		if (isset($this->fk_element))
			$this->fk_element = trim($this->fk_element);
		if (isset($this->fk_sub_element))
			$this->fk_sub_element = trim($this->fk_sub_element);

			// Check parameters
			// Put here code to add a control on parameters values

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_session_element SET";

		$sql .= " fk_session_agefodd=" . (isset($this->fk_session_agefodd) ? $this->fk_session_agefodd : "null") . ",";
		$sql .= " fk_soc=" . (isset($this->fk_soc) ? $this->fk_soc : "null") . ",";
		$sql .= " element_type=" . (isset($this->element_type) ? "'" . $this->db->escape($this->element_type) . "'" : "null") . ",";
		$sql .= " fk_element=" . (isset($this->fk_element) ? $this->fk_element : "null") . ",";
		$sql .= " fk_sub_element=" . (! empty($this->fk_sub_element) ? $this->fk_sub_element : "null") . ",";
		$sql .= " fk_user_mod=" . $user->id;

		$sql .= " WHERE rowid=" . $this->id;

		$this->db->begin();

		dol_syslog(get_class($this) . "::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
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
				dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
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
	 * @param User $user that deletes
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		$this->db->begin();

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

			dol_syslog(get_class($this) . "::delete");
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			$this->updateSellingPrice($user);
			return 1;
		}
	}

	/**
	 * Set invoice ref where propal or order is already linked
	 *
	 * @param $user User user oprate
	 * @param $id int id of source lement
	 * @param $type string type of element
	 * @param $invoiceid int invoice to link
	 * @return int int <0 if KO, >0 if OK
	 * @throws Exception
	 */
	public function add_invoice($user, $id, $type, $invoiceid) {

		$sql = "SELECT";
		$sql .= " f.rowid, f.fk_element, f.element_type, f.fk_soc, f.fk_session_agefodd ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element as f";
		if ($type == 'propal') {
			$sql .= " WHERE f.fk_element = " . $id . " AND f.element_type='propal'";
		}
		if ($type == 'commande') {
			$sql .= " WHERE f.fk_element = " . $id . " AND f.element_type='order'";
		}
		if ($type == 'facture') {
			$sql .= " WHERE f.fk_element = " . $id . " AND f.element_type='invoice'";
		}

		dol_syslog(get_class($this) . "::add_invoice", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->fk_session_agefodd = $obj->fk_session_agefodd;
				$this->fk_soc = $obj->fk_soc;
				$this->fk_element = $invoiceid;
				$this->element_type = 'invoice';

				$result = $this->create($user);
				if ($result < 0) {
					return - 1;
				} else {
				    return $result;
                }
			}
			$this->db->free($resql);

			return 0;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::add_invoice " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param int $fromid id of object to clone
	 * @return int id of clone
	 */
	public function createFromClone($fromid) {
		global $user;

		$error = 0;

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id = 0;
		$object->statut = 0;

		// Clear fields
		// ...

		// Create clone
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$error ++;
		}

		if (! $error) {
		}

		// End
		if (! $error) {
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
	public function initAsSpecimen() {
		$this->id = 0;

		$this->fk_session_agefodd = '';
		$this->fk_soc = '';
		$this->element_type = '';
		$this->fk_element = '';
		$this->fk_sub_element = '';
		$this->fk_user_author = '';
		$this->datec = '';
		$this->fk_user_mod = '';
		$this->tms = '';
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $id ob object
	 * @param int $sub_elment ob object
	 * @param string $action
	 * @param int $lineid
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_by_session($id, $sub_elment = 0,$action = null, $lineid = null) {
		require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
		require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
		require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

		//$action == 'confirm_deleteline'){ var_dump($obj); exit;}

		$this->propal_sign_amount = 0;
		$this->propal_amount = 0;
		$this->order_amount = 0;
		$this->invoice_ongoing_amount = 0;
		$this->nb_invoice_validated = 0;
		$this->nb_invoice_unpaid = 0;
		$this->invoice_payed_amount = 0;
		$this->trainer_cost_amount = 0;
		$this->trip_cost_amount = 0;
		$this->room_cost_amount = 0;
		$this->trainer_engaged_amount = 0;
		$this->trip_engaged_amount = 0;
		$this->room_engaged_amount = 0;
		$this->invoicetrainerdraft = false;

		$sql = "SELECT";
		$sql .= " rowid, fk_element, element_type, fk_soc ";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element";
		$sql .= " WHERE fk_session_agefodd=" . $id;

		if (! empty($sub_elment)) {
			$sql .= ' AND fk_sub_element=' . $sub_elment;
		}
		dol_syslog(get_class($this) . "::fetch_by_session", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				while ( $obj = $this->db->fetch_object($resql) ) {

					if ($obj->element_type == 'order') {
						$order = new Commande($this->db);
						$order->fetch($obj->fk_element);
						$this->order_amount += $order->total_ht;
					}

					if ($obj->element_type == 'propal') {
						$proposal = new Propal($this->db);
						$proposal->fetch($obj->fk_element);
						if ($proposal->statut == 2 || $proposal->statut == 4) {
							$this->propal_sign_amount += $proposal->total_ht;
							dol_syslog(get_class($this) . "::fetch_by_session status=2 proposal->total_ht=" . $proposal->total_ht, LOG_DEBUG);
						} elseif ($proposal->statut != 4) {
							$this->propal_amount = + $proposal->total_ht;
						}
					}

					if ($obj->element_type == 'invoice') {
						$facture = new Facture($this->db);
						$facture->fetch($obj->fk_element);
						if ($facture->statut == 2) {
							$this->invoice_payed_amount += $facture->total_ht;
							dol_syslog(get_class($this) . "::fetch_by_session status=2 facture->total_ht=" . $facture->total_ht, LOG_DEBUG);
							$this->nb_invoice_validated ++;
						}
						if ($facture->statut == 1) {
							$this->invoice_ongoing_amount += $facture->total_ht;
							dol_syslog(get_class($this) . "::fetch_by_session status=1 facture->total_ht=" . $facture->total_ht, LOG_DEBUG);
							$this->nb_invoice_unpaid ++;
						}
					}

					if ($obj->element_type == 'invoice_supplier_trainer' || $obj->element_type == 'invoice_supplierline_trainer') {
							if($obj->element_type == 'invoice_supplier_trainer')$facturefourn = new FactureFournisseur($this->db);
							else $facturefourn = new SupplierInvoiceLine($this->db);

							$facturefourn->fetch($obj->fk_element);
							$sessions = $this->get_linked_sessions($obj->fk_element, $obj->element_type);
							if (is_array($facturefourn->lines) && count($facturefourn->lines) > 0) { // facture fournisseur
								foreach ( $facturefourn->lines as $line ) {
								    if(!($action == 'confirm_deleteline' && $lineid == $line->id)) $this->trainer_cost_amount += ($sessions !== false && !empty($sessions['total'])) ? ($line->total_ht / $sessions['total']) * $sessions[$id] : $line->total_ht;
								}
							} else { // ligne de facture fournisseur
							    $this->trainer_cost_amount += ($sessions !== false && !empty($sessions['total'])) ? ($facturefourn->total_ht / $sessions['total']) * $sessions[$id] : $facturefourn->total_ht;
							}
							$this->invoicetrainerdraft = $this->invoicetrainerdraft || ($facturefourn->statut == 0);

							dol_syslog(get_class($this) . "::fetch_by_session invoice_supplier_trainer facturefourn->total_ht=" . $facturefourn->total_ht, LOG_DEBUG);
						}

						if ($obj->element_type == 'invoice_supplier_missions'|| $obj->element_type == 'invoice_supplierline_missions') {
							if($obj->element_type == 'invoice_supplier_missions')$facturefourn = new FactureFournisseur($this->db);
							else $facturefourn = new SupplierInvoiceLine($this->db);
							$facturefourn->fetch($obj->fk_element);

							$sessions = $this->get_linked_sessions($obj->fk_element, $obj->element_type);
							if (is_array($facturefourn->lines) && count($facturefourn->lines) > 0) {

							    foreach ( $facturefourn->lines as $line ) {
							        if(!($action == 'confirm_deleteline' && $lineid == $line->id)) $this->trip_cost_amount += ($sessions !== false && !empty($sessions['total'])) ? ($line->total_ht / $sessions['total']) * $sessions[$id] : $line->total_ht;
							    }
							} else {
							    $this->trip_cost_amount += ($sessions !== false && !empty($sessions['total'])) ? ($facturefourn->total_ht / $sessions['total']) * $sessions[$id] : $facturefourn->total_ht;
							}
							dol_syslog(get_class($this) . "::fetch_by_session invoice_supplier_missions facturefourn->total_ht=" . $facturefourn->total_ht, LOG_DEBUG);
						}

						if ($obj->element_type == 'invoice_supplier_room'|| $obj->element_type == 'invoice_supplierline_room') {
							if($obj->element_type == 'invoice_supplier_room')$facturefourn = new FactureFournisseur($this->db);
							else $facturefourn = new SupplierInvoiceLine($this->db);
							$facturefourn->fetch($obj->fk_element);
							$sessions = $this->get_linked_sessions($obj->fk_element, $obj->element_type);

							if (is_array($facturefourn->lines) && count($facturefourn->lines) > 0) {
							    foreach ( $facturefourn->lines as $line ) {
							        if(!($action == 'confirm_deleteline' && $lineid == $line->id)) $this->room_cost_amount += ($sessions !== false && !empty($sessions['total'])) ? ($line->total_ht / $sessions['total']) * $sessions[$id] : $line->total_ht;
							    }
							} else {
							    $this->room_cost_amount += ($sessions !== false && !empty($sessions['total'])) ? ($facturefourn->total_ht / $sessions['total']) * $sessions[$id] : $facturefourn->total_ht;
							}
							dol_syslog(get_class($this) . "::fetch_by_session  invoice_supplier_room facturefourn->total_ht=" . $facturefourn->total_ht, LOG_DEBUG);
						}
					if ($obj->element_type == 'order_supplier_trainer' || $obj->element_type == 'order_supplierline_trainer')
					{
						if ($obj->element_type == 'order_supplier_trainer')
							$commandefourn = new CommandeFournisseur($this->db);
						else
							$commandefourn = new CommandeFournisseurLigne($this->db);

						$res = $commandefourn->fetch($obj->fk_element);
						$sessions = $this->get_linked_sessions($obj->fk_element, $obj->element_type);

						if (is_array($commandefourn->lines) && count($commandefourn->lines) > 0)
						{ // facture fournisseur
							if($commandefourn->statut==0)continue;
							foreach ($commandefourn->lines as $line)
							{
								if (!($action == 'confirm_deleteline' && $lineid == $line->id))
									$this->trainer_engaged_amount += ($sessions !== false && !empty($sessions['total'])) ? ($line->total_ht / $sessions['total']) * $sessions[$id] : $line->total_ht;
							}
						} else
						{ // ligne de facture fournisseur
							$this->trainer_engaged_amount += ($sessions !== false && !empty($sessions['total'])) ? ($commandefourn->total_ht / $sessions['total']) * $sessions[$id] : $commandefourn->total_ht;
						}
						$this->ordertrainerdraft = $this->ordertrainerdraft || ($commandefourn->statut == 0);

						dol_syslog(get_class($this)."::fetch_by_session order_supplier_trainer facturefourn->total_ht=".$commandefourn->total_ht, LOG_DEBUG);
					}

					if ($obj->element_type == 'order_supplier_missions' || $obj->element_type == 'order_supplierline_missions')
					{
						if ($obj->element_type == 'order_supplier_missions')
							$commandefourn = new CommandeFournisseur($this->db);
						else
							$commandefourn = new CommandeFournisseurLigne($this->db);
						$commandefourn->fetch($obj->fk_element);

						$sessions = $this->get_linked_sessions($obj->fk_element, $obj->element_type);
						if (is_array($commandefourn->lines) && count($commandefourn->lines) > 0)
						{
							if($commandefourn->statut==0)continue;

							foreach ($commandefourn->lines as $line)
							{
								if (!($action == 'confirm_deleteline' && $lineid == $line->id))
									$this->trip_engaged_amount += ($sessions !== false && !empty($sessions['total'])) ? ($line->total_ht / $sessions['total']) * $sessions[$id] : $line->total_ht;
							}
						} else
						{
							$this->trip_engaged_amount += ($sessions !== false && !empty($sessions['total'])) ? ($commandefourn->total_ht / $sessions['total']) * $sessions[$id] : $commandefourn->total_ht;
						}
						dol_syslog(get_class($this)."::fetch_by_session order_supplier_missions facturefourn->total_ht=".$commandefourn->total_ht, LOG_DEBUG);
					}

					if ($obj->element_type == 'order_supplier_room' || $obj->element_type == 'order_supplierline_room')
					{
						if ($obj->element_type == 'order_supplier_room')
							$commandefourn = new CommandeFournisseur($this->db);
						else
							$commandefourn = new CommandeFournisseurLigne($this->db);
						$commandefourn->fetch($obj->fk_element);
						$sessions = $this->get_linked_sessions($obj->fk_element, $obj->element_type);

						if (is_array($commandefourn->lines) && count($commandefourn->lines) > 0)
						{
							if($commandefourn->statut==0)continue;
							foreach ($commandefourn->lines as $line)
							{
								if (!($action == 'confirm_deleteline' && $lineid == $line->id))
									$this->room_engaged_amount += ($sessions !== false && !empty($sessions['total'])) ? ($line->total_ht / $sessions['total']) * $sessions[$id] : $line->total_ht;
							}
						} else
						{
							$this->room_engaged_amount += ($sessions !== false && !empty($sessions['total'])) ? ($commandefourn->total_ht / $sessions['total']) * $sessions[$id] : $commandefourn->total_ht;
						}
						dol_syslog(get_class($this)."::fetch_by_session  order_supplier_room facturefourn->total_ht=".$commandefourn->total_ht, LOG_DEBUG);
					}
				}

				$this->db->free($resql);
			}
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_by_session " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Get all sessions linked with the supplier invoice given
	 * @param int $id id of the supplier invoice
	 * @param string $element_type element type
	 * @return array|false
	 */
	public function get_linked_sessions($id, $element_type = '%invoice_supplier%'){
	    $sql = "SELECT rowid, fk_session_agefodd FROM " . MAIN_DB_PREFIX . "agefodd_session_element WHERE element_type LIKE '".$this->db->escape($element_type)."' AND fk_element =".$id;

	    $res = $this->db->query($sql);
	    if ($res){
	        dol_include_once('/agefodd/class/agsession.class.php');
	        $tab = array();
	        $tab['total'] = 0;
	       while($obj = $this->db->fetch_object($res)){
	           $sess = new Agsession($this->db);
	           $sess->fetch($obj->fk_session_agefodd);
	           $tab[$obj->fk_session_agefodd] = (int)$sess->nb_stagiaire;
	           $tab['total'] += $sess->nb_stagiaire;
	       }
	       return $tab;
	    }
	    return false;
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $id ob object
	 * @param int $catid ob object
	 * @param string $type_element element type
	 * @param int $sub_element id of sub element
	 * @return int <0 if KO, >0 if OK
	 */
	public function get_charges_amount($id, $catid = 0, $type_element = '', $sub_element = 0) {
		global $conf;

		$total_charges = 0;

		if (empty($catid)) {
			$catid = $conf->global->AGF_CAT_PRODUCT_CHARGES;
		}

		if (! empty($catid)) {
			$sql = "SELECT";
			$sql .= " rowid, fk_element, element_type, fk_soc ";

			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element";
			$sql .= " WHERE fk_session_agefodd=" . $id;
			if (! empty($sub_element)) {
				$sql .= ' AND fk_sub_element=' . $sub_element;
			}

			dol_syslog(get_class($this) . "::get_charges_amount", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				if ($num > 0) {
					while ( $obj = $this->db->fetch_object($resql) ) {

						if ($obj->element_type == 'order') {
							/*$order = new Commande ( $this->db );
							 $order->fetch ( $obj->fk_element );
							 $this->order_amount += $order->total_ht;*/
						}

						if ($obj->element_type == 'propal' && $type_element == 'propal') {
							$sqlcharges = "SELECT SUM(ldet.total_ht) as totalcharges FROM " . MAIN_DB_PREFIX . "propaldet as ldet WHERE ldet.fk_propal=" . $obj->fk_element;
							$sqlcharges .= " AND ldet.fk_product IN (SELECT fk_product FROM " . MAIN_DB_PREFIX . "categorie_product WHERE fk_categorie IN (" . $catid . "))";
							dol_syslog(get_class($this) . "::get_charges_amount sqlcharges", LOG_DEBUG);
							$resqlcharges = $this->db->query($sqlcharges);
							if ($resqlcharges) {
								$objcharges = $this->db->fetch_object($resqlcharges);
								$total_charges += $objcharges->totalcharges;
								$this->db->free($resqlcharges);
							} else {
								$this->error = "Error " . $this->db->lasterror();
								dol_syslog(get_class($this) . "::get_charges_amount " . $this->error, LOG_ERR);
								return - 1;
							}
						}

						if ($obj->element_type == 'invoice' && $type_element == 'invoice') {
							$sqlcharges = "SELECT SUM(ldet.total_ht) as totalcharges FROM " . MAIN_DB_PREFIX . "facturedet as ldet WHERE ldet.fk_facture=" . $obj->fk_element;
							$sqlcharges .= " AND ldet.fk_product IN (SELECT fk_product FROM " . MAIN_DB_PREFIX . "categorie_product WHERE fk_categorie IN (" . $catid . "))";
							dol_syslog(get_class($this) . "::get_charges_amount sqlcharges", LOG_DEBUG);
							$resqlcharges = $this->db->query($sqlcharges);
							if ($resqlcharges) {
								$objcharges = $this->db->fetch_object($resqlcharges);
								$total_charges += $objcharges->totalcharges;
								$this->db->free($resqlcharges);
							} else {
								$this->error = "Error " . $this->db->lasterror();
								dol_syslog(get_class($this) . "::get_charges_amount " . $this->error, LOG_ERR);
								return - 1;
							}
						}

						if ($obj->element_type == 'invoice_supplier_trainer' && $type_element == 'invoice_supplier_trainer') {
							$sqlcharges = "SELECT SUM(ldet.total_ht) as totalcharges FROM " . MAIN_DB_PREFIX . "facture_fourn_det as ldet WHERE ldet.fk_facture_fourn=" . $obj->fk_element;
							$sqlcharges .= " AND ldet.fk_product IN (SELECT fk_product FROM " . MAIN_DB_PREFIX . "categorie_product WHERE fk_categorie IN (" . $catid . "))";
							dol_syslog(get_class($this) . "::get_charges_amount sqlcharges", LOG_DEBUG);
							$resqlcharges = $this->db->query($sqlcharges);
							if ($resqlcharges) {
								$objcharges = $this->db->fetch_object($resqlcharges);
								$total_charges += $objcharges->totalcharges;
								$this->db->free($resqlcharges);
							} else {
								$this->error = "Error " . $this->db->lasterror();
								dol_syslog(get_class($this) . "::get_charges_amount " . $this->error, LOG_ERR);
								return - 1;
							}
						}
					}
				}
				$this->db->free($resql);
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::fetch_by_session " . $this->error, LOG_ERR);
				return - 1;
			}
		}

		if (empty($total_charges)) {
			$total_charges = 0;
		}

		return $total_charges;
	}

	/**
	 * update selling price
	 *
	 * @param User $user
	 * @param string $action
	 * @param int $lineid
	 * @return int <0 if KO, >0 if OK
	 */
	public function updateSellingPrice($user,$action = null, $lineid = null) {
		global $conf;

		// Update session selling price
		$sell_price = 0;

		$result = $this->fetch_by_session($this->fk_session_agefodd, 0, $action , $lineid );

		if ($result < 0) {
			return -1;
		}
		
		// Par defaut si montant facturé non payé ou facturé payé => prix de vent c'est facturé non payé + facturé payé
		// Sinon montant total propal
		$invoiced_amount = 0;
		if ((! empty($this->invoice_payed_amount) || (! empty($this->invoice_ongoing_amount)))) {
			$sell_price = $this->invoice_payed_amount + $this->invoice_ongoing_amount;
			$invoiced_amount = $sell_price;
			dol_syslog(get_class($this) . "::updateSellingPrice invoice sell_price=" . $sell_price, LOG_DEBUG);
		}

		// Save charge cost and buy
		$total_sell_charges = $this->get_charges_amount($this->fk_session_agefodd, 0, 'invoice');
		if (empty($total_sell_charges)) {
			$total_sell_charges = $this->get_charges_amount($this->fk_session_agefodd, 0, 'propal');
		}
		$total_buy_charges = $this->get_charges_amount($this->fk_session_agefodd, 0, 'invoice_supplier_trainer');

		if (empty($sell_price)) {
			$sell_price = $this->propal_sign_amount;
		}
		dol_syslog(get_class($this) . "::updateSellingPrice propal sell_price=" . $sell_price, LOG_DEBUG);

		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'agefodd_session SET sell_price=\'' . price2num($sell_price) . '\' ';
		$sql .= ' ,invoice_amount=\'' . price2num($invoiced_amount) . '\' ';
		if (!empty($conf->global->AGF_ADVANCE_COST_MANAGEMENT)) {
			//if (!empty($this->room_cost_amount)) {
				$sql .= ' ,cost_site=\'' . price2num($this->room_cost_amount) . '\' ';
			//}
			//if (!empty($this->trainer_cost_amount)) {
				$sql .= ' ,cost_trainer=\'' . price2num($this->trainer_cost_amount) . '\' ';
			//}
			//if (!empty($this->trip_cost_amount)) {
				$sql .= ' ,cost_trip=\'' . price2num($this->trip_cost_amount) . '\' ';
			//}
			$sql .= ' ,cost_buy_charges=\'' . price2num($total_buy_charges) . '\' ';
		}
		$sql .= ' ,cost_sell_charges=\'' . price2num($total_sell_charges) . '\' ';
		$sql .= ' ,fk_user_mod=' . $user->id . ' ';
		$sql .= 'WHERE rowid=' . $this->fk_session_agefodd;

		dol_syslog(get_class($this) . "::updateSellingPrice", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::updateSellingPrice " . $this->error, LOG_ERR);
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
	public function fetch_element_by_session($id) {
		require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
		require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

		$sql = "SELECT";
		$sql .= " rowid, fk_element, element_type, fk_soc ";

		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_element";
		$sql .= " WHERE fk_session_agefodd=" . $id;

		dol_syslog(get_class($this) . "::fetch_element_by_session", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			$num = $this->db->num_rows($resql);

			for($i = 0; $i < $num; $i ++) {

				$obj = $this->db->fetch_object($resql);

				$line = new AgefoddSessionElementLine();
				$line->id = $obj->rowid;
				$line->fk_session_agefodd = $id;
				$line->fk_soc = $obj->fk_soc;
				$line->element_type = $obj->element_type;
				$line->fk_element = $obj->fk_element;

				if ($obj->element_type == 'order') {
					$order = new Commande($this->db);
					$order->fetch($obj->fk_element);
					if (! empty($order->id)) {
						$line->urllink = $order->getNomUrl(1);
					}
				}

				if ($obj->element_type == 'propal') {
					$proposal = new Propal($this->db);
					$proposal->fetch($obj->fk_element);
					if (! empty($proposal->id)) {
						$line->urllink = $proposal->getNomUrl(1);
					}
				}

				if ($obj->element_type == 'invoice') {
					$facture = new Facture($this->db);
					$facture->fetch($obj->fk_element);
					if (! empty($facture->id)) {
						$line->urllink = $facture->getNomUrl(1);
					}
				}

				$this->lines[] = $line;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_element_by_session " . $this->error, LOG_ERR);
			return - 1;
		}
	}
}
class AgefoddSessionElementLine {
	public $id;
	public $fk_session_agefodd;
	public $fk_soc;
	public $element_type;
	public $fk_element;
	public $fk_sub_element;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $tms = '';
	public $propalref = '';
	public $comref = '';
	public $facnumber = '';
	public $urllink;
}
class AgefoddElementLine {
	public $id;
	public $socid;
	public $socname;
	public $fk_session_agefodd;
	public $ref;
	public $date;
	public $amount;
	public $status;
	public $element_type;
}
class AgefoddElementInvoiceLine {
	public $id;
	public $ref;
	public $ref_supplier;
}
