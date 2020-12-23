<?php
/* Copyright (C) 2012-2014		Florian Henry			<florian.henry@open-concept.pro>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file agefodd/class/convention.class.php
 * \ingroup agefodd
 * \brief Manage convention object
 */
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");

/**
 * Convention class
 */
class Agefodd_convention {
	protected $db;
	public $error;
	public $errors = array ();
	public $element = 'agefodd_convention';
	public $table_element = 'agefodd_convention';
	public $id;
	public $sessid;
	public $socid;
	public $element_type;
	public $fk_element;
	public $model_doc;
	public $socname;
	public $intro1;
	public $intro2;
	public $art1;
	public $art2;
	public $art3;
	public $art4;
	public $art5;
	public $art6;
	public $art7;
	public $art8;
	public $art9;
	public $sig;
	public $only_product_session;
	public $doc_lang;
	public $notes;
	public $contatcdoc;
	public $lines = array ();
	public $line_trainee = array ();

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
	 * @param User $user that create
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (isset($this->intro1))
			$this->intro1 = $this->db->escape(trim($this->intro1));
		if (isset($this->intro2))
			$this->intro2 = $this->db->escape(trim($this->intro2));
		if (isset($this->art1))
			$this->art1 = $this->db->escape(trim($this->art1));
		if (isset($this->art2))
			$this->art2 = $this->db->escape(trim($this->art2));
		if (isset($this->art3))
			$this->art3 = $this->db->escape(trim($this->art3));
		if (isset($this->art4))
			$this->art4 = $this->db->escape(trim($this->art4));
		if (isset($this->art5))
			$this->art5 = $this->db->escape(trim($this->art5));
		if (isset($this->art6))
			$this->art6 = $this->db->escape(trim($this->art6));
		if (isset($this->art7))
			$this->art7 = $this->db->escape(trim($this->art7));
		if (isset($this->art8))
			$this->art8 = $this->db->escape(trim($this->art8));
		if (isset($this->art9))
			$this->art9 = $this->db->escape(trim($this->art9));
		if (isset($this->sig))
			$this->sig = $this->db->escape(trim($this->sig));
		if (isset($this->notes))
			$this->notes = $this->db->escape(trim($this->notes));
		if (empty($this->fk_element))
			$this->fk_element = 0;
		if (!empty($this->element_type))
			$this->element_type = $this->db->escape(trim($this->element_type));
		if (!empty($this->model_doc))
			$this->model_doc = $this->db->escape(trim($this->model_doc));
		if (!empty($this->doc_lang))
			$this->doc_lang = $this->db->escape(trim($this->doc_lang));
		if (!empty($this->only_product_session))
			$this->only_product_session = $this->db->escape(trim($this->only_product_session));

			// Check parameters
			// Put here code to add control on parameters value

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_convention(";
		$sql .= "fk_agefodd_session, fk_societe, intro1, intro2, art1, art2, art3,";
		$sql .= " art4, art5, art6, art7, art8, art9, sig, notes, fk_user_author, fk_user_mod, datec";
		$sql .= ",element_type";
		$sql .= ",fk_element";
		$sql .= ",model_doc";
		$sql .= ",doc_lang";
		$sql .= ",only_product_session";
		$sql .= ") VALUES (";
		$sql .= "'" . $this->sessid . "', ";
		$sql .= "'" . $this->socid . "', ";
		$sql .= "'" . $this->intro1 . "', ";
		$sql .= "'" . $this->intro2 . "', ";
		$sql .= "'" . $this->art1 . "', ";
		$sql .= "'" . $this->art2 . "', ";
		$sql .= "'" . $this->art3 . "', ";
		$sql .= "'" . $this->art4 . "', ";
		$sql .= "'" . $this->art5 . "', ";
		$sql .= "'" . $this->art6 . "', ";
		$sql .= "'" . $this->art7 . "', ";
		$sql .= "'" . $this->art8 . "', ";
		$sql .= "'" . $this->art9 . "', ";
		$sql .= "'" . $this->sig . "', ";
		$sql .= "'" . $this->notes . "', ";
		$sql .= $user->id . ', ';
		$sql .= $user->id . ', ';
		$sql .= "'" . $this->db->idate(dol_now()) . "'";
		$sql .= "," . (empty($this->element_type) ? 'NULL' : "'" . $this->element_type . "'");
		$sql .= "," . $this->fk_element;
		$sql .= "," . (empty($this->model_doc) ? 'NULL' : "'" . $this->model_doc . "'");
		$sql .= "," . (empty($this->doc_lang) ? 'NULL' : "'" . $this->doc_lang . "'");
		$sql .= "," . (empty($this->only_product_session) ? '0' : $this->only_product_session);
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}
		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "agefodd_convention");
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.

				// // Call triggers
				// include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}
		if (! $error && count($this->line_trainee) > 0) {
			foreach ( $this->line_trainee as $line ) {
				$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_convention_stagiaire(";
				$sql .= "fk_agefodd_convention,";
				$sql .= "fk_agefodd_session_stagiaire,";
				$sql .= "fk_user_author,";
				$sql .= "fk_user_mod,";
				$sql .= "datec";
				$sql .= ") VALUES (";
				$sql .= $this->id . ", ";
				$sql .= $line . ", ";
				$sql .= $user->id . ', ';
				$sql .= $user->id . ', ';
				$sql .= "'" . $this->db->idate(dol_now()) . "'";
				$sql .= ")";

				dol_syslog(get_class($this) . "::create ", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql) {
					$error ++;
					$this->errors[] = "Error " . $this->db->lasterror();
				}
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
			return $this->id;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $sessid id of agefodd_session
	 * @param int $socid id of societe
	 * @param int $id id of agefodd_convention
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($sessid, $socid, $id = 0) {
		global $langs;

		$sql = "SELECT";
		$sql .= " c.rowid, c.fk_agefodd_session, c.fk_societe, c.intro1, c.intro2,";
		$sql .= " c.art1, c.art2, c.art3, c.art4, c.art5, c.art6, c.art7, c.art8, c.art9, c.sig, notes, s.nom as socname";
		$sql .= ",element_type";
		$sql .= ",fk_element";
		$sql .= ",model_doc";
		$sql .= ",doc_lang";
		$sql .= ",only_product_session";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_convention as c";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid=c.fk_societe";
		if ($id > 0)
			$sql .= " WHERE c.rowid = " . $id;
		else {
			$sql .= " WHERE c.fk_agefodd_session = " . $sessid;
			$sql .= " AND c.fk_societe = " . $socid;
		}

		dol_syslog(get_class($this) . "::fetch ", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->sessid = $obj->fk_agefodd_session;
				$this->socid = $obj->fk_societe;
				$this->socname = $obj->socname;
				$this->intro1 = $obj->intro1;
				$this->intro2 = $obj->intro2;
				$this->art1 = $obj->art1;
				$this->art2 = $obj->art2;
				$this->art3 = $obj->art3;
				$this->art4 = $obj->art4;
				$this->art5 = $obj->art5;
				$this->art6 = $obj->art6;
				$this->art7 = $obj->art7;
				$this->art8 = $obj->art8;
				$this->art9 = $obj->art9;
				$this->sig = $obj->sig;
				$this->notes = $obj->notes;
				$this->element_type = $obj->element_type;
				$this->fk_element = $obj->fk_element;
				$this->model_doc = $obj->model_doc;
				$this->doc_lang = $obj->doc_lang;
				$this->only_product_session = $obj->only_product_session;
			}
			$this->db->free($resql);

			if (! empty($this->id)) {
				$this->line_trainee = array ();

				$sql = "SELECT";
				$sql .= " convtrainee.fk_agefodd_session_stagiaire";
				$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_convention_stagiaire as convtrainee ";
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as s ON s.rowid=convtrainee.fk_agefodd_session_stagiaire";
				$sql .= " WHERE convtrainee.fk_agefodd_convention = " . $this->id;

				dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
				$resql = $this->db->query($sql);

				if ($resql) {
					if ($this->db->num_rows($resql)) {
						while ( $obj = $this->db->fetch_object($resql) ) {
							$this->line_trainee[] = $obj->fk_agefodd_session_stagiaire;
						}
					}
				}
				$this->db->free($resql);
			}

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
	 * @param int   $sessid              session id
	 * @param int   $socid               socid
	 * @param array $filterTraineeStatus Filter on status of trainee
	 * @return int <0 if KO, >0 if OK
	 * @throws Exception
	 */
	public function fetch_all($sessid, $socid = 0, $filterTraineeStatus=array()) {

		global $langs;

		$sql = "SELECT";
		$sql .= " c.rowid, c.fk_agefodd_session, c.fk_societe, c.intro1, c.intro2,";
		$sql .= " c.art1, c.art2, c.art3, c.art4, c.art5, c.art6, c.art7, c.art8, c.art9, c.sig, notes, s.nom as socname";
		$sql .= ",element_type";
		$sql .= ",fk_element";
		$sql .= ",model_doc";
		$sql .= ",doc_lang";
		$sql .= ",only_product_session";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_convention as c";
		$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid=c.fk_societe";
		$sql .= " WHERE c.fk_agefodd_session = " . $sessid;
		if (! empty($socid)) {
			$sql .= " AND c.fk_societe = " . $socid;
		}

		dol_syslog(get_class($this) . "::fetch_all", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			if ($this->db->num_rows($resql)) {

				$this->lines = array ();

				while ( $obj = $this->db->fetch_object($resql) ) {
					$line = new Agefodd_convention($this->db);

					$line->id = $obj->rowid;
					$line->sessid = $obj->fk_agefodd_session;
					$line->socid = $obj->fk_societe;
					$line->socname = $obj->socname;
					$line->intro1 = $obj->intro1;
					$line->intro2 = $obj->intro2;
					$line->art1 = $obj->art1;
					$line->art2 = $obj->art2;
					$line->art3 = $obj->art3;
					$line->art4 = $obj->art4;
					$line->art5 = $obj->art5;
					$line->art6 = $obj->art6;
					$line->art7 = $obj->art7;
					$line->art8 = $obj->art8;
					$line->art9 = $obj->art9;
					$line->sig = $obj->sig;
					$line->notes = $obj->notes;
					$line->element_type = $obj->element_type;
					$line->fk_element = $obj->fk_element;
					$line->model_doc = $obj->model_doc;
					$line->doc_lang = $obj->doc_lang;
					$line->only_product_session = $obj->only_product_session;

					$line->line_trainee = array ();

					$sql_trainee = "SELECT";
					$sql_trainee .= " convtrainee.fk_agefodd_session_stagiaire";
					$sql_trainee .= " FROM " . MAIN_DB_PREFIX . "agefodd_convention_stagiaire as convtrainee ";
					$sql_trainee .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as s ON s.rowid=convtrainee.fk_agefodd_session_stagiaire";
					$sql_trainee .= " WHERE convtrainee.fk_agefodd_convention = " . $line->id;
					if (count($filterTraineeStatus)>0) {
						$sql_trainee .= " AND s.status_in_session IN (".implode(",", $filterTraineeStatus).")";
					}
					dol_syslog(get_class($this) . "::fetch_all ", LOG_DEBUG);
					$resqltrainee = $this->db->query($sql_trainee);
					if ($resqltrainee) {
						if ($this->db->num_rows($resqltrainee)) {

							while ( $objtrainee = $this->db->fetch_object($resqltrainee) ) {
								$line->line_trainee[] = $objtrainee->fk_agefodd_session_stagiaire;
							}
						}
					} else {
						$this->error = "Error " . $this->db->lasterror();
						dol_syslog(get_class($this) . "::fetch_all " . $this->error, LOG_ERR);
						return - 1;
					}
					$this->db->free($resqltrainee);

					$this->lines[] = $line;
				}
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from database
	 *
	 * @param int $socid id
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_last_conv_per_socity($socid) {
		global $langs;

		$sql = "SELECT";
		$sql .= " c.rowid, MAX(c.fk_agefodd_session) as sessid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_convention as c";
		$sql .= " WHERE c.fk_societe = " . $socid;
		$sql .= " GROUP BY c.rowid";

		dol_syslog(get_class($this) . "::fetch_last_conv_per_socity ", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->sessid = $obj->sessid;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_last_conv_per_socity " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	public static function nl2br($string)
    {
        // is HTML
        if ($string !== strip_tags($string))
        {
            // alors ceci provient d'un WYSIWYG, les sauts de ligne dans un WYSIWYG applique un "<br />" ainsi qu'un "\n"
            return str_replace("\n", '', $string);
        }
        else
        {
            return nl2br($string);
        }
    }

	/**
	 * Load order lines object in memory from database
	 *
	 * @param int $comid id
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_order_lines($comid) {
		require_once (DOL_DOCUMENT_ROOT . "/product/class/product.class.php");

		global $langs, $conf;

		$sql = "SELECT";
		$sql .= " c.rowid, c.fk_product, c.description, c.tva_tx, c.remise_percent,";
		$sql .= " c.fk_remise_except, c.subprice, c.qty, c.total_ht, c.total_tva, c.total_ttc";
		$sql .= " FROM " . MAIN_DB_PREFIX . "commandedet as c";
		$sql .= " WHERE c.fk_commande = " . $comid;
		if (! empty($this->only_product_session)) {
			$sql .= " AND c.fk_product IN (SELECT fk_product FROM " . MAIN_DB_PREFIX . "agefodd_session WHERE rowid=" . $this->sessid . ")";
		}

		dol_syslog(get_class($this) . "::fetch_commande_lines ", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new AgfConventionLine();

				$line->rowid = $obj->rowid;
				$line->fk_product = $obj->fk_product;
				if (! empty($line->fk_product)) {
					$prod_static = new Product($this->db);
					$result = $prod_static->fetch($line->fk_product);
					if ($result < 0) {
						dol_syslog(get_class($this) . "::fetch_order_lines " . $prod_static->error, LOG_ERR);
					}

					if (strpos($obj->description, $prod_static->description) !== false || $conf->global->AGEFODD_CONVENTION_DOUBLE_DESC_DESACTIVATE) {
						$line->description = $prod_static->ref . ' ' . $prod_static->label . '<BR>' . self::nl2br($obj->description);
					} else {
						$line->description = $prod_static->ref . ' ' . self::nl2br($prod_static->description) . '<BR>' . $prod_static->label . '<BR>' . self::nl2br($obj->description);
					}
				} else {
					$line->description = $obj->description;
				}
				$line->tva_tx = $obj->tva_tx;
				$line->remise_percent = $obj->remise_percent;
				$line->fk_remise_except = $obj->fk_remise_except;
				$line->price = $obj->subprice;
				$line->qty = $obj->qty;
				$line->total_ht = $obj->total_ht;
				$line->total_tva = $obj->total_tva;
				$line->total_ttc = $obj->total_ttc;

				$this->lines[$i] = $line;

				$i ++;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_commande_lines " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load Invoice lines object in memory from database
	 *
	 * @param int $factid id
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_invoice_lines($factid) {
		require_once (DOL_DOCUMENT_ROOT . "/product/class/product.class.php");

		global $langs, $conf;

		$sql = "SELECT";
		$sql .= " c.rowid, c.fk_product, c.description, c.tva_tx, c.remise_percent,";
		$sql .= " c.fk_remise_except, c.subprice, c.qty, c.total_ht, c.total_tva, c.total_ttc";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as c";
		$sql .= " WHERE c.fk_facture = " . $factid;
		if (! empty($this->only_product_session)) {
			$sql .= " AND c.fk_product IN (SELECT fk_product FROM " . MAIN_DB_PREFIX . "agefodd_session WHERE rowid=" . $this->sessid . ")";
		}

		dol_syslog(get_class($this) . "::fetch_invoice_lines ", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new AgfConventionLine();

				$line->rowid = $obj->rowid;
				$line->fk_product = $obj->fk_product;
				if (! empty($line->fk_product)) {
					$prod_static = new Product($this->db);
					$result = $prod_static->fetch($line->fk_product);
					if ($result < 0) {
						dol_syslog(get_class($this) . "::fetch_invoice_lines " . $prod_static->error, LOG_ERR);
					}

					if (strpos($obj->description, $prod_static->description) !== false || $conf->global->AGEFODD_CONVENTION_DOUBLE_DESC_DESACTIVATE) {
						$line->description = $prod_static->ref . ' ' . $prod_static->label . '<BR>' . self::nl2br($obj->description);
					} else {
						$line->description = $prod_static->ref . ' ' . self::nl2br($prod_static->description) . '<BR>' . $prod_static->label . '<BR>' . self::nl2br($obj->description);
					}
				} else {
					$line->description = $obj->description;
				}
				$line->tva_tx = $obj->tva_tx;
				$line->remise_percent = $obj->remise_percent;
				$line->fk_remise_except = $obj->fk_remise_except;
				$line->price = $obj->subprice;
				$line->qty = $obj->qty;
				$line->total_ht = $obj->total_ht;
				$line->total_tva = $obj->total_tva;
				$line->total_ttc = $obj->total_ttc;

				$this->lines[$i] = $line;

				$i ++;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_invoice_lines " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load Proposal lines object in memory from database
	 *
	 * @param int $propalid id
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_propal_lines($propalid) {
		require_once (DOL_DOCUMENT_ROOT . "/product/class/product.class.php");

		global $langs;

		$sql = "SELECT";
		$sql .= " c.rowid, c.fk_product, c.description,c.label, c.tva_tx, c.remise_percent,";
		$sql .= " c.fk_remise_except, c.subprice, c.qty, c.total_ht, c.total_tva, c.total_ttc";
		$sql .= " FROM " . MAIN_DB_PREFIX . "propaldet as c";
		$sql .= " WHERE c.fk_propal = " . $propalid;
		if (! empty($this->only_product_session)) {
			$sql .= " AND c.fk_product IN (SELECT fk_product FROM " . MAIN_DB_PREFIX . "agefodd_session WHERE rowid=" . $this->sessid . ")";
		}

		dol_syslog(get_class($this) . "::fetch_propal_lines ", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ( $i < $num ) {
				$obj = $this->db->fetch_object($resql);

				$line = new AgfConventionLine();

				$line->rowid = $obj->rowid;
				$line->fk_product = $obj->fk_product;
				if (! empty($line->fk_product)) {
					$prod_static = new Product($this->db);
					$result = $prod_static->fetch($line->fk_product);
					if ($result < 0) {
						dol_syslog(get_class($this) . "::fetch_propal_lines " . $prod_static->error, LOG_ERR);
					}
					// $line->description = $prod_static->description . '<BR>' . $prod_static->label . '<BR>' . nl2br ( $obj->description );
					if (! empty($obj->label) && $obj->label != $prod_static->label) {
						$line->description = $obj->label . '<BR>' . self::nl2br($obj->description);
					} elseif (strpos($obj->description, $prod_static->label) !== false) {
						$line->description = self::nl2br($obj->description);
					} else {
						$line->description = $prod_static->label . '<BR>' . self::nl2br($obj->description);
					}
				} else {
					$line->description = $obj->description;
				}
				$line->tva_tx = $obj->tva_tx;
				$line->remise_percent = $obj->remise_percent;
				$line->fk_remise_except = $obj->fk_remise_except;
				$line->price = $obj->subprice;
				$line->qty = $obj->qty;
				$line->total_ht = $obj->total_ht;
				$line->total_tva = $obj->total_tva;
				$line->total_ttc = $obj->total_ttc;

				$this->lines[$i] = $line;

				$i ++;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_propal_lines " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Give information on the object
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function info($id) {
		global $langs;

		$sql = "SELECT";
		$sql .= " c.rowid, c.datec, c.tms, c.fk_user_author, c.fk_user_mod";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_convention as c";
		$sql .= " WHERE c.rowid = " . $id;

		dol_syslog(get_class($this) . "::info ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->datec = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
				$this->fk_userc = $obj->fk_user_author;
				$this->fk_userm = $obj->fk_user_mod;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::info " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param User $user that modify
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (isset($this->intro1))
			$this->intro1 = $this->db->escape(trim($this->intro1));
		if (isset($this->intro2))
			$this->intro2 = $this->db->escape(trim($this->intro2));
		if (isset($this->art1))
			$this->art1 = $this->db->escape(trim($this->art1));
		if (isset($this->art2))
			$this->art2 = $this->db->escape(trim($this->art2));
		if (isset($this->art3))
			$this->art3 = $this->db->escape(trim($this->art3));
		if (isset($this->art4))
			$this->art4 = $this->db->escape(trim($this->art4));
		if (isset($this->art5))
			$this->art5 = $this->db->escape(trim($this->art5));
		if (isset($this->art6))
			$this->art6 = $this->db->escape(trim($this->art6));
		if (isset($this->art7))
			$this->art7 = $this->db->escape(trim($this->art7));
		if (isset($this->art8))
			$this->art8 = $this->db->escape(trim($this->art8));
		if (isset($this->art9))
			$this->art9 = $this->db->escape(trim($this->art9));
		if (isset($this->sig))
			$this->sig = $this->db->escape(trim($this->sig));
		if (isset($this->notes))
			$this->notes = $this->db->escape(trim($this->notes));
		if (empty($this->fk_element))
			$this->fk_element = 0;
		if (!empty($this->element_type))
			$this->element_type = $this->db->escape(trim($this->element_type));
		if (isset($this->model_doc))
			$this->model_doc = $this->db->escape(trim($this->model_doc));
		if (!empty($this->doc_lang))
			$this->doc_lang = $this->db->escape(trim($this->doc_lang));
		if (!empty($this->only_product_session))
			$this->only_product_session = $this->db->escape(trim($this->only_product_session));

			// Update request
		if (! isset($this->archive))
			$this->archive = 0;
		$sql = "UPDATE " . MAIN_DB_PREFIX . "agefodd_convention SET";
		$sql .= " intro1='" . $this->intro1 . "',";
		$sql .= " intro2='" . $this->intro2 . "',";
		$sql .= " art1='" . $this->art1 . "',";
		$sql .= " art2='" . $this->art2 . "',";
		$sql .= " art3='" . $this->art3 . "',";
		$sql .= " art4='" . $this->art4 . "',";
		$sql .= " art5='" . $this->art5 . "',";
		$sql .= " art6='" . $this->art6 . "',";
		$sql .= " art7='" . $this->art7 . "',";
		$sql .= " art8='" . $this->art8 . "',";
		$sql .= " art9='" . $this->art9 . "',";
		$sql .= " sig='" . $this->sig . "',";
		$sql .= " notes='" . $this->notes . "',";
		$sql .= " fk_element=" . $this->fk_element . ",";
		$sql .= " element_type=" . (!empty($this->element_type) ? "'" . $this->element_type . "'" : "null") . ", ";
		$sql .= " fk_societe=" . $this->socid . ",";
		$sql .= " fk_agefodd_session=" . $this->sessid . ",";
		$sql .= " fk_user_mod=" . $user->id . ", ";
		$sql .= " doc_lang=" . (!empty($this->doc_lang) ? "'" . $this->doc_lang . "'" : "null") . ", ";
		$sql .= " model_doc=" . (isset($this->model_doc) ? "'" . $this->model_doc . "'" : "null") . ", ";
		$sql .= " only_product_session=" . (!empty($this->only_product_session) ? $this->only_product_session  : "0");
		$sql .= " WHERE rowid = " . $this->id;

		$this->db->begin();

		dol_syslog(get_class($this) . "::update ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}
		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action call a trigger.

				// // Call triggers
				// include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}

		if (! $error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_convention_stagiaire";
			$sql .= " WHERE fk_agefodd_convention = " . $this->id;

			dol_syslog(get_class($this) . "::update", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}

			if (! $error && count($this->line_trainee) > 0) {
				foreach ( $this->line_trainee as $line ) {
					$sql = "INSERT INTO " . MAIN_DB_PREFIX . "agefodd_convention_stagiaire(";
					$sql .= "fk_agefodd_convention,";
					$sql .= "fk_agefodd_session_stagiaire,";
					$sql .= "fk_user_author,";
					$sql .= "fk_user_mod,";
					$sql .= "datec";
					$sql .= ") VALUES (";
					$sql .= $this->id . ", ";
					$sql .= $line . ", ";
					$sql .= $user->id . ', ';
					$sql .= $user->id . ', ';
					$sql .= "'" . $this->db->idate(dol_now()) . "'";
					$sql .= ")";

					dol_syslog(get_class($this) . "::update", LOG_DEBUG);
					$resql = $this->db->query($sql);
					if (! $resql) {
						$error ++;
						$this->errors[] = "Error " . $this->db->lasterror();
					}
				}
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
	 * @param int $id id of agefodd_convention to remove
	 * @return int <0 if KO, >0 if OK
	 */
	public function remove($id) {
		global $conf, $langs;

		$error = 0;

		$this->fetch(0, 0, $id);
		$file = '';
		// For backwoard compatibilty check convention file name with id of convention
		if (is_file($conf->agefodd->dir_output . '/convention_' . $this->sessid . '_' . $this->socid . '.pdf')) {
			$file = $conf->agefodd->dir_output . '/convention_' . $this->sessid . '_' . $this->socid . '.pdf';
		} elseif (is_file($conf->agefodd->dir_output . '/convention_' . $this->sessid . '_' . $this->socid . '_' . $this->id . '.pdf')) {
			$file = $conf->agefodd->dir_output . '/convention_' . $this->sessid . '_' . $this->socid . '_' . $this->id . '.pdf';
		}

		$this->db->begin();

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_convention_stagiaire";
		$sql .= " WHERE fk_agefodd_convention = " . $id;

		dol_syslog(get_class($this) . "::remove", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "agefodd_convention";
		$sql .= " WHERE rowid = " . $id;

		dol_syslog(get_class($this) . "::remove", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error && ! empty($file)) {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
			$result = dol_delete_file($file);
			if (! $result) {
				$error ++;
				$this->errors[] = $file . ' : ' . $langs->trans("AgfDocDelError");
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::remove " . $errmsg, LOG_ERR);
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
	 * Load contact info from object in memory from database
	 *
	 * @param int $contactsource id of contact to fetch
	 * @param string $contacttype type of contact to fetch
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch_contact($contactsource, $contacttype) {
		require_once (DOL_DOCUMENT_ROOT . "/contact/class/contact.class.php");

		global $langs;

		$sql = "SELECT";
		$sql .= " socp.rowid as contactid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "element_contact as elmentcontact";
		$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'socpeople as socp ON socp.rowid=elmentcontact.fk_socpeople';
		$sql .= ' WHERE  elmentcontact.fk_c_type_contact IN (SELECT rowid FROM ' . MAIN_DB_PREFIX . 'c_type_contact WHERE element ="' . $this->element_type . '" AND source="' . $contactsource . '"';
		$sql .= ' AND code="' . $contacttype . '")';
		$sql .= " AND elmentcontact.element_id = " . $this->fk_element;

		dol_syslog(get_class($this) . "::fetch_contact", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 0) {

				$obj = $this->db->fetch_object($resql);

				$contact = new Contact($this->db);
				$result = $contact->fetch($obj->contactid);
				if ($result < 0) {
					$this->error = $contact->error;
					return - 1;
				}

				if (! empty($contact->id)) {
					$this->contatcdoc = $contact;
				}

				return $num;
			}

			return 0;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_contact " . $this->error, LOG_ERR);
			return - 1;
		}
	}
}
class AgfConventionLine {
	public $rowid;
	public $fk_product;
	public $description;
	public $tva_tx;
	public $remise_percent;
	public $fk_remise_except;
	public $price;
	public $qty;
	public $total_ht;
	public $total_tva;
	public $total_ttc;
	public function __construct() {
		return 1;
	}
}
