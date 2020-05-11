<?php
/*
 * Copyright (C) 2012-2014 Florian Henry <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file		/agefodd/class/report_general.php
 * \ingroup agefodd
 * \brief File of class to generate report for agefodd
 */
require_once ('agefodd_export_excel.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

/**
 * Class to build report ca ventilated
 */
class ReportCAVentilated extends AgefoddExportExcel {
	public $productRefMap = array();
	public $Tfacture = array();

	public $status_array = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	public function __construct($db, $outputlangs) {

		$outputlangs->load('agefodd@agefodd');
		$outputlangs->load('bills');
		$outputlangs->load("exports");
		$outputlangs->load("main");
		$outputlangs->load("commercial");
		$outputlangs->load("companies");
		$outputlangs->load("products");

		$sheet_array = array (
				0 => array (
						'name' => 'send',
						'title' => $outputlangs->transnoentities('AgfMenuReportCAVentilated')
				)
		);

		// Contruct header (column name)
		$array_column_header = array ();

		$array_column_header[0][1] = array (
			'type' => 'text',
			'title' => 'Numéro de facture'
		);

		$array_column_header[0][2] = array (
			'type' => 'date',
			'title'=> $outputlangs->transnoentities('DateInvoice')
		);

		$array_column_header[0][3] = array (
			'type' => 'text',
			'title'=> 'ID Session'
		);

		$array_column_header[0][4] = array (
			'type' => 'text',
			'title'=> $outputlangs->transnoentities('AgfLieu')
		);

		$array_column_header[0][5] = array (
			'type' => 'text',
			'title'=> $outputlangs->transnoentities('Customer')
		);

		$array_column_header[0][6] = array (
			'type' => 'text',
			'title'=> $outputlangs->transnoentities('AgfTypeRequester')
		);

		$array_column_header[0][7] = array (
			'type' => 'text',
			'title'=> $outputlangs->transnoentities('AgfSessionInvoicedThirdparty')
		);

		$array_column_header[0][8] = array (
			'type' => 'text',
			'title'=> $outputlangs->transnoentities('ParentCompany')
		);

		$array_column_header[0][9] = array (
			'type' => 'text',
			'title'=> $outputlangs->transnoentities('SalesRepresentatives')
		);

		// Je laisse ça pour quand le client voudra filter par status des factures
		$this->status_array=array(1=>$outputlangs->trans('BillShortStatusDraft'),2=>$outputlangs->trans('BillShortStatusPaid'), 3=>$outputlangs->trans('BillShortStatusNotPaid'));
		$this->status_array_noentities=array(1=>$outputlangs->transnoentities('BillShortStatusDraft'),2=>$outputlangs->transnoentities('BillShortStatusPaid'), 3=>$outputlangs->transnoentities('BillShortStatusNotPaid'));

		return parent::__construct($db, $array_column_header, $outputlangs, $sheet_array);
	}

	/**
	 * Output filter line into file
	 *
	 * @return int if KO, >0 if OK
	 */
	public function write_filter($filter) {
		dol_syslog(get_class($this) . "::write_filter ");
		// Create a format for the column headings
		try {

			// Manage filter
			if (count($filter) > 0) {
				foreach ( $this->sheet_array as $keysheet => $sheet ) {

					$this->workbook->setActiveSheetIndex($keysheet);
					$this->row[$keysheet] ++;
					foreach ( $filter as $key => $value ) {
						if ($key == 'f.datef') {
							if (isset($value['start'])) {
								$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('AgfExportFrom'));
								$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], date('d-m-Y', $value['start']));
								$this->row[$keysheet] ++;
							}
							if (isset($value['end'])) {
								$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('AgfExportTo'));
								$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], date('d-m-Y', $value['end']));
								$this->row[$keysheet] ++;
							}
						} elseif ($key == 'so.nom') {
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('Company'));
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], $value);
							$this->row[$keysheet] ++;
						} elseif ($key == 'so.parent|sorequester.parent') {
							$socParent='';
							foreach ($value as $parentSocid)
							{
								$socparent = new Societe($this->db);
								$result = $socparent->fetch($parentSocid);
								if ($result < 0) {
									$this->error = $socparent->error;
									return $result;
								}
								$socParent .= $socparent->name.',';
							}
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('ParentCompany'));
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], $socParent);
							$this->row[$keysheet] ++;
						} elseif ($key == 'socrequester.nom') {
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('AgfTypeRequester'));
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], $value);
							$this->row[$keysheet] ++;
						} elseif ($key == 'sale.fk_user') {
							require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
							$salesMan='';
							foreach ($value as $sale_id)
							{
								$user_salesman = new User($this->db);
								$result = $user_salesman->fetch($sale_id);
								if ($result < 0) {
									$this->error = $user_salesman->error;
									return $result;
								}
								$salesMan .= $user_salesman->getFullName($this->outputlangs).",";
							}
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('SalesRepresentatives'));
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], $salesMan);
							$this->row[$keysheet] ++;
						}
					}
				}
			}
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			return - 1;
		}

		return 1;
	}

	/**
	 * Give complinat file name regarding filter
	 *
	 * @param $filter array an array filter
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function getSubTitlFileName($filter) {
		$str_sub_name = '';
		if (count($filter) > 0) {
			foreach ( $filter as $key => $value ) {
				if ($key == 'f.datef') {
					if (isset($value['start'])) $str_sub_name.=$this->outputlangs->transnoentities('AgfExportFrom').date('d-m-Y', $value['start']);
					if (isset($value['end'])) $str_sub_name.=$this->outputlangs->transnoentities('AgfExportTo').date('d-m-Y', $value['end']);
				}
				if ($key == 'so.nom') {
					$str_sub_name .= $this->outputlangs->transnoentities('Company') . $value;
				} elseif ($key == 'so.parent|sorequester.parent') {
					require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
					$str_sub_name .= $this->outputlangs->transnoentities('ParentCompany');
					foreach ($value as $parentSocid)
					{
						$socparent = new Societe($this->db);
						$result = $socparent->fetch($parentSocid);
						if ($result < 0) {
							$this->error = $socparent->error;
							return $result;
						}
						$str_sub_name .= $socparent->name.'-';
					}
				} elseif ($key == 'socrequester.nom') {
					$str_sub_name .= $this->outputlangs->transnoentities('AgfTypeRequester') . $value;
				} elseif ($key == 'sale.fk_user') {
					require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
					$str_sub_name .= $this->outputlangs->transnoentities('SalesRepresentatives');
					foreach ($value as $sale_id)
					{
						$user_salesman = new User($this->db);
						$result = $user_salesman->fetch($sale_id);
						if ($result < 0) {
							$this->error = $user_salesman->error;
							return $result;
						}
						$str_sub_name .= $user_salesman->getFullName($this->outputlangs)."-";
					}
				}
			}
		}
		$str_sub_name = str_replace(' ', '', $str_sub_name);
		$str_sub_name = str_replace('.', '', $str_sub_name);
		$str_sub_name = dol_sanitizeFileName($str_sub_name);

		return $str_sub_name;
	}

	/**
	 * Wrtire Excel File
	 *
	 * @param $filter array filter array
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function write_file($filter) {
		global $user;

		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		require_once 'agefodd_convention.class.php';
		require_once 'agsession.class.php';
		require_once 'agefodd_session_stagiaire.class.php';
		require_once 'agefodd_stagiaire.class.php';
		require_once 'agefodd_session_calendrier.class.php';

		$this->outputlangs->load('agefodd@agefodd');
		$this->outputlangs->load('bills');
		$this->outputlangs->load("exports");
		$this->outputlangs->load("main");
		$this->outputlangs->load("commercial");
		$this->outputlangs->load("companies");

		$this->title = $this->outputlangs->transnoentities('AgfMenuReportCA');
		$this->subject = $this->outputlangs->transnoentities('AgfMenuReportCA');
		$this->description = $this->outputlangs->transnoentities('AgfMenuReportCA');
		$this->keywords = $this->outputlangs->transnoentities('AgfMenuReportCA');

		$result = $this->open_file($this->file);
		if ($result < 0) {
			return $result;
		}

		// Récupérer les ref produits présentes dans le rapport pour ajouter des colonnes
		$result = $this->fetch_columns($filter); // pour récupérer tous les produits à présenter en colonne + 1 colonne pour les lignes libres
		if ($result < 0) return $result;

		$result = $this->fetch_ca($filter);
		if ($result < 0) return $result;

		if ($result > 0) {

			$this->setArrayColumnHeader($this->array_column_header);

			$result = $this->write_header();
			if ($result < 0) {
				return $result;
			}

			// Ouput Lines
			$line_to_output = $line_total = array ();
			$line_total[1] = "Total HT";
			for ($i = 2; $i < 9; $i++) $line_total[$i] = "";
			$headercol = count($this->array_column_header[0]);
			for ($i = 9; $i < $headercol; $i++) $line_total[$i] = 0; // to avoid non-numeric warning

			foreach ($this->Tfacture as $fac_id => $Tsession)
			{
				// reinit de la ligne à écrire
				foreach ($this->array_column_header[0] as $k => $colsetup) $line_to_output[$k] = "";

				$facture = new Facture($this->db);
				$ret = $facture->fetch($fac_id);

				if ($ret > 0)
				{
					$facture->fetch_thirdparty();

					foreach ($Tsession as $sessid)
					{
						$session = new Agsession($this->db);
						$session->fetch($sessid);
						$session->fetch_thirdparty();

						// ref facture
						$line_to_output[1] = $facture->ref;

						// date facture
						$line_to_output[2] = date("d/m/Y",$facture->date);

						// ID Session
						$line_to_output[3] = $sessid;

						// Lieu
						$line_to_output[4] = $session->placecode;

						// Client
						$socclient = clone($facture->thirdparty);
						$session->fetch_societe_per_session($sessid);
						if (!empty($session->lines))
						{
							foreach ($session->lines as $s_line)
							{
								if ($s_line->socid == $facture->thirdparty->id)
								{
									// dans le cas d'un financement OPCA, ou financement de l'employeur, le client est le tiers du participant
									if ($s_line->typeline == 'trainee_OPCA' || $s_line->typeline == 'trainee_soc')
									{
										$sta_sql = "SELECT soc.rowid, soc.code_client, soc.nom";
										$sta_sql.= " FROM ".MAIN_DB_PREFIX."agefodd_stagiaire as sta";
										$sta_sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = sta.fk_soc";
										$sta_sql.= " WHERE sta.rowid = ".$s_line->trainee_array[0]['id'];

										$sta_resql = $this->db->query($sta_sql);
										if ($sta_resql && $this->db->num_rows($sta_resql))
										{
											$obj = $this->db->fetch_object($sta_resql);
											$socclient->fetch($obj->rowid);
										}
									}
									else if ($s_line->typeline == 'OPCA') // financement de toute la session par l'OPCA
									{
										// client de la session s'il existe
										if (!empty($session->thirdparty->id)) $socclient = clone($session->thirdparty);
									}

									break;
								}
							}
						}
						$line_to_output[5] = $socclient->name.($socclient->code_client ? ' - '. $socclient->code_client : '');

						// Demandeur
						$line_to_output[6] = "";
						$socrequester = new Societe($this->db);
						if ($s_line->typeline == 'trainee_OPCA')
						{
							// coalesce (participant, session)
							$sta_sql = "SELECT sta.fk_soc_requester";
							$sta_sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session stagiaire as sta";
							$sta_sql.= " WHERE sta.fk_stagiaire = ".$s_line->trainee_array[0]['id'];

							$sta_resql = $this->db->query($sta_sql);
							if ($sta_resql && $this->db->num_rows($sta_resql))
							{
								$obj = $this->db->fetch_object($sta_resql);
								$socrequester->fetch($obj->fk_soc_requester);
							}
							else
							{
								$socrequester->fetch($session->fk_soc_requester);
							}
						}
						else if ($s_line->typeline == 'OPCA')
						{
							// celui de la session
							$socrequester->fetch($session->fk_soc_requester);
						}

						if ($socrequester->id) $line_to_output[6] = $socrequester->name.($socrequester->code_client ? ' - '. $socrequester->code_client : '');

						// Payeur
						$line_to_output[7] = $facture->thirdparty->name.($facture->thirdparty->code_client ? ' - ' . $facture->thirdparty->code_client: '');

						// Maison mère
						$line_to_output[8] = $this->getParentName($socclient->parent);

						// Commerciaux
						$line_to_output[9] = "";
						$commArray = $socclient->getSalesRepresentatives($user);
						if (!empty($commArray))
						{
							$tab = array();
							foreach ($commArray as $commData) $tab[] = $commData['firstname'].' '.$commData['lastname'];

							$line_to_output[9] .= implode(', ', $tab);
						}

						//Total de ligne
						$line_to_output[$headercol]=0;
						// remplissage des colonnes produits
						foreach ($facture->lines as $line)
						{
							$productKey = $this->productRefMap[$line->fk_product];
							if (!array_key_exists($productKey,$line_to_output)) {
								$line_to_output[$productKey]=0;
							} elseif(empty($line_to_output[$productKey])) {
								$line_to_output[$productKey]=0;
							}
							$line_to_output[$productKey] += floatval($line->total_ht);
							$line_to_output[$headercol] += floatval($line->total_ht);
						}

						foreach ($this->array_column_header[0] as $k => $dummy)
						{
							if ($k > 8) $line_total[$k] += floatval($line_to_output[$k]);
						}

						$result = $this->write_line($line_to_output, 0);
						if ($result < 0) {
							return $result;
						}

					}
				}

			}

		}

		$result = $this->write_line_total($line_total);
		if ($result < 0) {
			return $result;
		}

		$result = $this->write_filter($filter);
		if ($result < 0) {
			return $result;
		}

		$this->close_file(0,0,0);
		return 1;
	}

	public function fetch_columns($filter = array())
	{
		$this->productRefMap = array();

		$sql = "SELECT DISTINCT p.ref, p.rowid";
		$sql.= $this->getSQL($filter, true);
		$sql.= " ORDER BY p.ref ASC";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				$index = count($this->array_column_header[0])+1;
				while ($obj = $this->db->fetch_object($resql))
				{
					$this->productRefMap[$obj->rowid] = $index;
					$this->array_column_header[0][$index] = array(
						'type' 	=> 'number',
						'title' => $obj->ref
					);
					$index++;
				}
				// pour les lignes libres
				$this->productRefMap[null] = $index;
				$this->array_column_header[0][$index] = array(
					'type' 	=> 'number',
					'title' => "Lignes libres"
				);
				// pour les totaux de ligne

				$this->array_column_header[0][$index+1] = array(
					'type' 	=> 'number',
					'title' => "Total"
				);

				return 1;
			}
		}
		else
		{
			$this->error = "SQL error on fetch_comlumns";
			return -1;
		}
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_ca($filter = array()) {
		global $langs, $conf;

		$sql = "SELECT";
		$sql.= (float) DOL_VERSION < 10 ? " f.facnumber" : " f.ref as facnumber";
		$sql.= ", f.rowid, f.datef";
		$sql.= ", sess.rowid as sess_id";
		$sql.= ", sess.fk_soc as sess_client_id";
		$sql.= ", sess.fk_soc_requester as requester";
		$sql.= ", f.fk_soc as buyer";
		$sql.= ", so.nom as buyerName";
		$sql.= $this->getSQL($filter);
		$sql.= " ORDER BY ".((float) DOL_VERSION < 10 ? " f.facnumber ASC" : " f.ref ASC").", sess.rowid ASC";

		// Récupérer les data...
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);

			if (empty($num))
			{
				$this->error = $this->outputlangs->transnoentities("NoDataToRetrieve");
				return -1;
			}

			while ($obj = $this->db->fetch_object($resql))
			{
				if (!isset($this->Tfacture[$obj->rowid])) $this->Tfacture[$obj->rowid] = array();
				if (!in_array($obj->sess_id, $this->Tfacture[$obj->rowid])) $this->Tfacture[$obj->rowid][] = $obj->sess_id;
			}
		}
		else
		{
			$this->error = "SQL error : Get data";
			return -1;
		}

		return 1;
	}

	function getSQL($filter = array(), $columns = false)
	{
		global $langs, $conf;


		$sql = " FROM ".MAIN_DB_PREFIX."facture AS f";
		if ($columns)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facturedet AS fd ON fd.fk_facture = f.rowid";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product AS p ON p.rowid = fd.fk_product";
		}
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."agefodd_session_element AS ase ON ase.fk_element = f.rowid AND ase.element_type='invoice'";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."agefodd_session AS sess ON sess.rowid = ase.fk_session_agefodd";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."societe as so ON so.rowid = f.fk_soc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as sessclient ON sessclient.rowid = sess.fk_soc";
		if (
			array_key_exists('sale.fk_user', $filter)
			||	array_key_exists('socrequester.nom', $filter)
			||	array_key_exists('so.parent|sorequester.parent', $filter)
		) {
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as socrequester ON socrequester.rowid = sess.fk_soc_requester";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire ass ON ass.fk_session_agefodd = sess.rowid";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire trainee ON trainee.rowid = ass.fk_stagiaire";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as traineesocrequester ON traineesocrequester.rowid = ass.fk_soc_requester";
		}
		if (array_key_exists('sale.fk_user', $filter)) {
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sale ON sale.fk_soc = COALESCE(trainee.fk_soc, sess.fk_soc)";
		}

		$sql .= " WHERE f.fk_statut IN (1,2)";

		$sql .= " AND sess.rowid IS NOT NULL"; // On prend uniquement les factures liées à des sessions de formation

		if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
			$sql .= " AND f.type IN (0,1,2)";
		else
			$sql .= " AND f.type IN (0,1,2,3)";

		// Manage filter
		if (count($filter) > 0) {
			foreach ( $filter as $key => $value ) {
				if ($key == 'f.datef') {
					if (isset($value['start'])) $sql.=" AND ".$key." >= '".$this->db->idate($value['start'])."'";
					if (isset($value['end'])) $sql.=" AND ".$key." <= '".$this->db->idate($value['end'])."'";
				} elseif ($key == 'so.parent|sorequester.parent') {
					$ValArray = array();
					foreach ($value as $v) $ValArray[] = $this->db->escape($v);
					$sql .= ' AND (so.parent IN (\'' . implode("','", $ValArray) . '\') OR socrequester.parent IN (\'' . implode("','", $ValArray) . '\')';
					$sql .= ' OR so.rowid IN (\'' . implode("','", $ValArray) . '\') OR socrequester.rowid IN (\'' . implode("','", $ValArray) . '\'))';
				} elseif ($key == 'socrequester.nom') {
					$sql .= ' AND (socrequester.nom LIKE "%'.$this->db->escape($value).'%" OR traineesocrequester.nom LIKE "%'.$this->db->escape($value).'%")';
					// TODO manage search_sale
				} elseif ($key == 'sale.fk_user') {

					$ValArray = array();
					foreach ($value as $v) $ValArray[] = $this->db->escape($v);
					$sql .= ' AND sale.fk_user IN (\'' . implode("','", $ValArray) . '\')';

				} else {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}

			}
		}

		return $sql;
	}

	public function getParentName($socid = "")
	{
		$ret = "";

		if (!empty($socid))
		{
			$soc = new Societe($this->db);
			$res = $soc->fetch($socid);
			if ($res > 0)
			{
				$ret = $soc->name.($soc->code_client ? ' - '.$soc->code_client : '');
			}
		}

		return $ret;
	}
}

