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

/**
 * Class to build report by customer
 */
class ReportCA extends AgefoddExportExcel {
	private $value_ca_total_ht = array ();
	private $persent_ca_total_ht = array ();
	private $value_ca_total_ttc = array ();
	private $persent_ca_total_ttc = array ();
	private $value_ca_total_hthf = array ();
	private $persent_ca_total_hthf = array ();
	private $year_to_report_array = array ();

	public $status_array = array();
	public $T_ACCOUNTING_DATE_CHOICES = array(
		'invoice' => 'DateInvoice'
		, 'session_start' => 'AgfDateSessionStart'
		, 'session_end' => 'AgfDateSessionEnd'
	);

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
						'title' => $outputlangs->transnoentities('AgfMenuReportCA')
				)
		);

		$array_column_header = array ();

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

					foreach ( $filter as $key => $value ) {
						if ($key == 'startyear') {
							$str_cirteria = $this->outputlangs->transnoentities('Year') . ' ';
							$str_criteria_value = $value;
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $str_cirteria);
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], $str_criteria_value);
							$this->row[$keysheet] ++;
						} elseif ($key == 'so.nom') {
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('Company'));
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], $value);
							$this->row[$keysheet] ++;
						} elseif ($key == 'so.parent|sorequester.parent') {
							require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
							$socparent = new Societe($this->db);
							$result = $socparent->fetch($value);
							if ($result < 0) {
								$this->error = $socparent->error;
								return $result;
							}
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('ParentCompany'));
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], $socparent->name);
							$this->row[$keysheet] ++;
						} elseif ($key == 'socrequester.nom') {
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('AgfTypeRequester'));
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], $value);
							$this->row[$keysheet] ++;
						} elseif ($key == 'sale.fk_user_com') {
							require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
							$user_salesman = new User($this->db);
							$result = $user_salesman->fetch($value);
							if ($result < 0) {
								$this->error = $user_salesman->error;
								return $result;
							}
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('SalesRepresentatives'));
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], $user_salesman->getFullName($this->outputlangs));
							$this->row[$keysheet] ++;
						} elseif ($key == 's.type_session') {
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('Type'));
							if ($value == 0) {
								$type_session = $this->outputlangs->transnoentities('AgfFormTypeSessionIntra');
							} elseif ($value == 1) {
								$type_session = $this->outputlangs->transnoentities('AgfFormTypeSessionInter');
							}
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], $type_session);
							$this->row[$keysheet] ++;
						} elseif ($key == 's.status') {
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('AgfStatusSession'));
							$session_status = '';
							$sql = "SELECT t.rowid, t.code ,t.intitule ";
							$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_status_type as t";
							$sql .= ' WHERE t.rowid IN (' . implode(',', $value) . ')';

							dol_syslog(get_class($this) . "::write_filter sql=" . $sql, LOG_DEBUG);
							$result = $this->db->query($sql);
							if ($result) {

								$num = $this->db->num_rows($result);
								if ($num) {
									while ( $obj = $this->db->fetch_object($result) ) {
										if ($obj->intitule == $this->outputlangs->trans('AgfStatusSession_' . $obj->code)) {
											$session_status[] = stripslashes($obj->intitule);
										} else {
											$session_status[] = $this->outputlangs->transnoentities('AgfStatusSession_' . $obj->code);
										}
									}
								}
							} else {
								$this->error = "Error " . $this->db->lasterror();
								dol_syslog(get_class($this) . "::write_filter " . $this->error, LOG_ERR);
								return - 1;
							}
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], implode(',', $session_status));
							$this->row[$keysheet] ++;
						} elseif ($key == 'invstatus') {
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('Status'));
							if (is_array($value) && count($value)>0) {
								foreach($value as $key=>$invstatus) {
									$invoice_status[]=$this->status_array_noentities[$invstatus];
								}
							}
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], implode(',', $invoice_status));
							$this->row[$keysheet] ++;
						} elseif ($key == 'group_by_session') {
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('AgfReportCASessionDetail'));
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], $this->outputlangs->transnoentities('Yes'));
							$this->row[$keysheet] ++;
						} elseif ($key == 'accounting_date') {
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $this->outputlangs->transnoentities('AgfReportCAInvoiceAccountingDate'));
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[$keysheet], $this->outputlangs->transnoentities($this->T_ACCOUNTING_DATE_CHOICES[$value]));
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
				if ($key == 'accounting_date') {
					$str_sub_name .= str_replace(' ', '', ucwords($this->outputlangs->transnoentities($this->T_ACCOUNTING_DATE_CHOICES[$value])));
				}
				if ($key == 'startyear') {
					$str_sub_name .= $this->outputlangs->transnoentities('Year');
					$str_sub_name .= $value;
				}
				if ($key == 'so.nom') {
					$str_sub_name .= $this->outputlangs->transnoentities('Company') . $value;
				} elseif ($key == 'so.parent|sorequester.parent') {
					require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
					$socparent = new Societe($this->db);
					$result = $socparent->fetch($value);
					if ($result < 0) {
						$this->error = $socparent->error;
						return $result;
					}
					$str_sub_name .= $this->outputlangs->transnoentities('ParentCompany') . $socparent->name;
				} elseif ($key == 'socrequester.nom') {
					$str_sub_name .= $this->outputlangs->transnoentities('AgfTypeRequester') . $value;
				} elseif ($key == 'sale.fk_user') {
					require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
					$user_salesman = new User($this->db);
					$result = $user_salesman->fetch($value);
					if ($result < 0) {
						$this->error = $user_salesman->error;
						return $result;
					}
					$str_sub_name .= $this->outputlangs->transnoentities('SalesRepresentatives') . $user_salesman->getFullName($this->outputlangs);
				} elseif ($key == 's.type_session') {

					if ($value == 0) {
						$type_session = $this->outputlangs->transnoentities('AgfFormTypeSessionIntra');
					} elseif ($value == 1) {
						$type_session = $this->outputlangs->transnoentities('AgfFormTypeSessionInter');
					}
					$str_sub_name .= $this->outputlangs->transnoentities('Type') . $type_session;
				} elseif ($key == 's.status') {
					$session_status = '';
					if (is_array($value) && count($value) > 0) {
						$sql = "SELECT t.rowid, t.code ,t.intitule ";
						$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_status_type as t";
						$sql .= ' WHERE t.rowid IN (' . implode(',', $value) . ')';

						dol_syslog(get_class($this) . "::getSubTitlFileName sql=" . $sql, LOG_DEBUG);
						$result = $this->db->query($sql);
						if ($result) {

							$num = $this->db->num_rows($result);
							if ($num) {
								while ( $obj = $this->db->fetch_object($result) ) {
									$session_status .= $obj->code;
								}
							}
						} else {
							$this->error = "Error " . $this->db->lasterror();
							dol_syslog(get_class($this) . "::getSubTitlFileName " . $this->error, LOG_ERR);
							return - 1;
						}
					}
					$str_sub_name .= $this->outputlangs->transnoentities('AgfStatusSession') . $session_status;
				} elseif ($key == 'invstatus') {
					if (is_array($value) && count($value)>0) {
						foreach($value as $key=>$invstatus) {
							$invoice_status[]=$this->status_array_noentities[$invstatus];
						}
					}
					$str_sub_name .= $this->outputlangs->transnoentities('Status').implode('-', $invoice_status);
				} elseif ($key == 'group_by_session') {
					$str_sub_name .= 'BySession';
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
		/*$result = $this->write_title();
		if ($result < 0) {
			return $result;
		}*/

		// General
		$result = $this->fetch_ca($filter);
		if ($result < 0) {
			return $result;
		}

		// Contruct header (column name) with year array fill in fetch_ca method
		$array_column_header = array ();
		if (count($this->year_to_report_array) > 0) {

			$array_column_header[0][1] = array (
					'type' => 'text',
					'title' => ''
			);

			$i = 2;
			foreach ( $this->year_to_report_array as $year_todo ) {
				$array_column_header[0][$i] = array (
						'type' => 'number',
						'title' => $year_todo .' HT/HF'
				);
				$array_column_header[0][$i+1] = array (
						'type' => 'number',
						'title' => $year_todo. ' HT'
				);
				$array_column_header[0][$i + 2] = array (
						'type' => 'percent',
						'title' => $this->outputlangs->transnoentities('Ecart')
				);
				$i = $i + 3;
			}
		}

		if (count($this->year_to_report_array) > 0) {

			$this->setArrayColumnHeader($array_column_header);

			$result = $this->write_header();
			if ($result < 0) {
				return $result;
			}
			$array_total_hthf = array ();
			$array_total_hthf_trim1 = array ();
			$array_total_hthf_trim2 = array ();
			$array_total_hthf_trim3 = array ();
			$array_total_hthf_trim4 = array ();
			$array_total_ht_trim1 = array ();
			$array_total_ht_trim2 = array ();
			$array_total_ht_trim3 = array ();
			$array_total_ht_trim4 = array ();
			$array_total_ht = array ();
			$array_total_ttc = array ();
			// Ouput Lines
			$line_to_output = array ();


			for($month_todo = 1; $month_todo <= 12; $month_todo ++) {

				if (strlen($month_todo) == 1) {
					$line_to_output[1] = $this->outputlangs->transnoentities('Month0' . $month_todo);
				} else {
					$line_to_output[1] = $this->outputlangs->transnoentities('Month' . $month_todo);
				}
				$i = 2;

				$sessions = array();

				foreach ( $this->year_to_report_array as $year_todo ) {

					$line_to_output[$i] = $this->value_ca_total_hthf[$year_todo][$month_todo]['total'];
					$line_to_output[$i + 1] = $this->value_ca_total_ht[$year_todo][$month_todo]['total'];
					$line_to_output[$i + 2] = $this->persent_ca_total_ht[$year_todo][$month_todo];
					$i = $i + 3;

					$array_total_hthf[$year_todo] += $this->value_ca_total_hthf[$year_todo][$month_todo]['total'];
					$array_total_ht[$year_todo] += $this->value_ca_total_ht[$year_todo][$month_todo]['total'];
					$array_total_ttc[$year_todo] += $this->value_ca_total_ttc[$year_todo][$month_todo]['total'];

					$sessions = array_merge($sessions, array_keys($this->value_ca_total_ht[$year_todo][$month_todo]['detail']));

					if ($month_todo == 1 || $month_todo == 2 || $month_todo == 3) {
						$array_total_hthf_trim1[$year_todo] += $this->value_ca_total_hthf[$year_todo][$month_todo]['total'];
						$array_total_ht_trim1[$year_todo] += $this->value_ca_total_ht[$year_todo][$month_todo]['total'];
					}
					if ($month_todo == 4 || $month_todo == 5 || $month_todo == 6) {
						$array_total_hthf_trim2[$year_todo] += $this->value_ca_total_hthf[$year_todo][$month_todo]['total'];
						$array_total_ht_trim2[$year_todo] += $this->value_ca_total_ht[$year_todo][$month_todo]['total'];
					}
					if ($month_todo == 7 || $month_todo == 8 || $month_todo == 9) {
						$array_total_hthf_trim3[$year_todo] += $this->value_ca_total_hthf[$year_todo][$month_todo]['total'];
						$array_total_ht_trim3[$year_todo] += $this->value_ca_total_ht[$year_todo][$month_todo]['total'];
					}
					if ($month_todo == 10 || $month_todo == 11 || $month_todo == 12) {
						$array_total_hthf_trim4[$year_todo] += $this->value_ca_total_hthf[$year_todo][$month_todo]['total'];
						$array_total_ht_trim4[$year_todo] += $this->value_ca_total_ht[$year_todo][$month_todo]['total'];
					}
				}
				$result = $this->write_line($line_to_output, 0);
				if ($result < 0) {
					return $result;
				}

				sort($sessions);

				foreach($sessions as $sessionId) {

					$line_to_output = array();
					$line_to_output[1] = $sessionId;

					$i = 2;

					$toPrint = false;

					foreach ( $this->year_to_report_array as $year_todo ) {
						$totalHTHF = $this->value_ca_total_hthf[$year_todo][$month_todo]['detail'][$sessionId];
						$totalHT = $this->value_ca_total_ht[$year_todo][$month_todo]['detail'][$sessionId];
						if(! $toPrint && $totalHT > 0) $toPrint = true;

						$line_to_output[$i] = $totalHTHF;
						$line_to_output[$i+1] = $totalHT;
						$line_to_output[$i+2] = '';

						$i += 3;
					}

					if($toPrint) {
						$result = $this->write_line($line_to_output, 0);
						if ($result < 0) {
							return $result;
						}
					}
				}
			}

			//Jump line
			$this->row[0]++;

			// Write total HTHF
			$line_to_output[1] = $this->outputlangs->transnoentities('Total HT/HF');

			$i = 2;
			foreach ( $this->year_to_report_array as $year_todo ) {

				$line_to_output[$i] = $array_total_hthf[$year_todo];
				$line_to_output[$i+1] = 0;
				$line_to_output[$i+2] = 'N/A';

				$i = $i + 3;
			}
			$result = $this->write_line($line_to_output, 0);
			if ($result < 0) {
				return $result;
			}

			// Write total HT
			$line_to_output[1] = $this->outputlangs->transnoentities('Total HT');

			$i = 2;
			foreach ( $this->year_to_report_array as $year_todo ) {

				$line_to_output[$i] = 0;
				$line_to_output[$i+1] = $array_total_ht[$year_todo];
				if (array_key_exists($year_todo - 1, $array_total_ht)) {
					if ($array_total_ht[$year_todo - 1] != 0) {
						$line_to_output[$i + 2] = (($array_total_ht[$year_todo] - $array_total_ht[$year_todo - 1]) / $array_total_ht[$year_todo - 1]);
					} else {
						$line_to_output[$i + 2] = 'N/A';
					}
				} else {
					$line_to_output[$i + 2] = 'N/A';
				}

				$i = $i + 3;
			}
			$result = $this->write_line($line_to_output, 0);
			if ($result < 0) {
				return $result;
			}

			// Write total TTC
			$line_to_output[1] = $this->outputlangs->transnoentities('Total TTC');

			$i = 2;
			foreach ( $this->year_to_report_array as $year_todo ) {

				$line_to_output[$i] = 0;
				$line_to_output[$i+1] = $array_total_ttc[$year_todo];
				if (array_key_exists($year_todo - 1, $array_total_ttc)) {
					if ($array_total_ttc[$year_todo - 1] != 0) {
						$line_to_output[$i + 2] = (($array_total_ttc[$year_todo] - $array_total_ttc[$year_todo - 1]) / $array_total_ttc[$year_todo - 1]);
					} else {
						$line_to_output[$i + 2] = 'N/A';
					}
				} else {
					$line_to_output[$i + 2] = 'N/A';
				}

				$i = $i + 3;
			}
			$result = $this->write_line($line_to_output, 0);
			if ($result < 0) {
				return $result;
			}

			//Jump line
			$this->row[0]++;

			// Write total by trimesters
			for($trim = 1; $trim <= 4; $trim ++) {
				$line_to_output[1] = $this->outputlangs->transnoentities('Trimestre ' . $trim);

				$i = 2;
				foreach ( $this->year_to_report_array as $year_todo ) {

					$line_to_output[$i] = ${'array_total_hthf_trim' . $trim}[$year_todo];
					$line_to_output[$i+1] = ${'array_total_ht_trim' . $trim}[$year_todo];

					if (array_key_exists($year_todo - 1, ${'array_total_ht_trim' . $trim})) {
						if (${'array_total_ht_trim' . $trim}[$year_todo - 1] != 0) {
							$line_to_output[$i + 2] = ((${'array_total_ht_trim' . $trim}[$year_todo] - ${'array_total_ht_trim' . $trim}[$year_todo - 1]) / ${'array_total_ht_trim' . $trim}[$year_todo - 1]);
						} else {
							$line_to_output[$i + 2] = 'N/A';
						}
					} else {
						$line_to_output[$i + 2] = 'N/A';
					}

					$i = $i + 3;
				}

				$result = $this->write_line($line_to_output, 0);
				if ($result < 0) {
					return $result;
				}
			}
		}

		$this->row[0]++;
		$result = $this->write_filter($filter);
		if ($result < 0) {
			return $result;
		}

		$this->close_file();
		return count($this->year_to_report_array);
		// return 1;
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_ca($filter = array()) {
		global $langs, $conf;

		$minyear = dol_print_date(dol_now(), '%Y');
		// find minimum invoice year with dolibarr
		$sql = "SELECT MIN(YEAR(f.datef)) as minyear";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as facdet ON facdet.fk_facture=f.rowid ";
		//No more filter on product dolibarr
		//$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "product as prod ON prod.rowid=facdet.fk_product ";
		$sql .= " WHERE f.paye=1 ";

		dol_syslog(get_class($this) . "::fetch_ca sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$minyear = $obj->minyear;
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_ca " . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);

		if (array_key_exists('startyear', $filter) && $filter['startyear'] < $minyear) {
			$this->error = $langs->trans('AgfReportCAMinimumYear', $minyear);
			return - 1;
		}

		// find max year to report
		if (array_key_exists('startyear', $filter)) {
			$max_year = $filter['startyear'];
		} else {
			$max_year = dol_print_date(dol_now(), '%Y');
		}

		if ($max_year < $minyear) {
			$this->error = $langs->trans('AgfReportCAMaxYear', $max_year);
			return - 1;
		}

		$this->year_to_report_array = array ();
		for($i = intval($max_year); $i >= $minyear; $i --) {
			$this->year_to_report_array[$i] = $i;
		}

		$this->value_ca_total_ht = array ();
		$this->value_ca_total_ttc = array ();
		$this->value_ca_total_hthf = array ();

		switch($filter['accounting_date']) {
			case 'session_start':
				$accounting_date = 's.dated';
				break;

			case 'session_end':
				$accounting_date = 's.datef';
				break;

			case 'invoice':
			default:
				$accounting_date = 'f.datef';
		}

		unset($filter['accounting_date']);

		// Amount
		foreach ( $this->year_to_report_array as $year_todo ) {
			for($month_todo = 1; $month_todo <= 12; $month_todo ++) {

				// For Total HT/TTC
				$sql = "SELECT (facdet.total_ttc) as amount_ttc";
				$sql.= ", (facdet.total_ht) as amount_ht";
				if(array_key_exists('group_by_session', $filter)) $sql.= ", s.rowid";
				$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as facdet ON facdet.fk_facture=f.rowid ";
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so";
				$sql .= " ON so.rowid = f.fk_soc";
				if (
						array_key_exists('sale.fk_user', $filter)
					||	array_key_exists('group_by_session', $filter)
					||	array_key_exists('s.type_session', $filter)
					||	array_key_exists('socrequester.nom', $filter)
					||	array_key_exists('so.parent|sorequester.parent', $filter)
					||	preg_match('/^s\./', $accounting_date)
				) {
					$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_element as sesselement";
					$sql .= " ON sesselement.element_type='invoice' AND f.rowid = sesselement.fk_element";
					$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as s";
					$sql .= " ON s.rowid = sesselement.fk_session_agefodd ";
					$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as socrequester";
					$sql .= " ON socrequester.rowid = s.fk_soc_requester";
				}
				if (array_key_exists('sale.fk_user', $filter)) {
					$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire ass";
					$sql .= " ON ass.fk_session_agefodd = s.rowid";
					$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire trainee";
					$sql .= " ON trainee.rowid = ass.fk_stagiaire";
					$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sale";
					$sql .= " ON sale.fk_soc = COALESCE(trainee.fk_soc, s.fk_soc)";
				}

				$sql .= " WHERE YEAR(".$accounting_date.")=" . $year_todo;
				$sql .= " AND MONTH(".$accounting_date.")=" . $month_todo;
				// Statut = validate | closed
				$sql .= " AND f.fk_statut IN (1,2)";
				if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
					$sql .= " AND f.type IN (0,1,2)";
				else
					$sql .= " AND f.type IN (0,1,2,3)";

				// Manage filter
				if (count($filter) > 0) {
					foreach ( $filter as $key => $value ) {
						if ($key == 's.type_session') {
							$sql .= ' AND ' . $key . ' = ' . $this->db->escape($value);
						} elseif ($key == 'sale.fk_user') {
							$sql .= ' AND (' . $value . ' IN (';
							$sql .= '	SELECT sale.fk_user';
							$sql .= '	FROM ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire ass';
							$sql .= '	INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire trainee ON trainee.rowid = ass.fk_stagiaire';
							$sql .= '	INNER JOIN ' . MAIN_DB_PREFIX . 'societe_commerciaux as sale ON sale.fk_soc = trainee.fk_soc';
							$sql .= '	LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_opca opca ON ass.fk_session_agefodd = opca.fk_session_agefodd AND opca.fk_session_trainee = ass.rowid';
							$sql .= '	INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_element ase ON ase.fk_session_agefodd = ass.fk_session_agefodd AND ase.element_type = "invoice"';
							$sql .= '	INNER JOIN ' . MAIN_DB_PREFIX . 'facture f2 ON ase.fk_element = f2.rowid';
							$sql .= '	WHERE ass.fk_session_agefodd = s.rowid';
							$sql .= '	AND f2.rowid = f.rowid';
							//$sql .= '	AND f2.fk_soc = COALESCE(IF(s.type_session = 1, IF(opca.fk_soc_OPCA <= 0, NULL, opca.fk_soc_OPCA), s.fk_soc_OPCA), trainee.fk_soc)';
							$sql .= '	AND f2.fk_soc = COALESCE(CASE WHEN s.type_session = 1 THEN CASE WHEN opca.fk_soc_OPCA <= 0 THEN NULL ELSE opca.fk_soc_OPCA END ELSE s.fk_soc_OPCA END, trainee.fk_soc)'; // TODO : remove this comment if all is ok after few tests with CASE style
							$sql .= '	AND sale.fk_soc = COALESCE(trainee.fk_soc, s.fk_soc)';
							$sql .= ')';
							$sql .= ' OR ';
							$sql .= ' (' . $value . ' IN (SELECT salecom.fk_user FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux as salecom ';
							$sql .= ' WHERE salecom.fk_soc=s.fk_soc AND s.rowid NOT IN (SELECT asscom.fk_session_agefodd FROM ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire asscom))';
							$sql .= ' )';
							$sql .= ')';
						} elseif ($key == 'so.parent|sorequester.parent') {
							$sql .= ' AND (so.parent=' . $this->db->escape($value) . ' OR socrequester.parent=' . $this->db->escape($value);
							$sql .= ' OR so.rowid=' . $this->db->escape($value) . ' OR socrequester.rowid=' . $this->db->escape($value) . ')';
						} /*elseif ($key == 'so.nom') {
							// Search for all thirdparty concern by the session
							$sql .= ' AND ((' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\') OR (s.rowid IN (SELECT innersess.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session as innersess ';
							$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire as inserss ON innersess.rowid = inserss.fk_session_agefodd';
							$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire as insersta ON insersta.rowid = inserss.fk_stagiaire ';
							$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe as insersoc ON insersoc.rowid = insersta.fk_soc ';
							$sql .= ' WHERE insersoc.nom LIKE \'%' . $this->db->escape($value) . '%\' )))';
						}*/
						elseif ($key == 'invstatus') {
							$invstatus=array_flip($value);
							$sql_invstatus=array();

							if (array_key_exists('2',$invstatus)) {
								$sql_invstatus[]= " f.paye=1 ";
							}
							if (array_key_exists('3',$invstatus)) {
								$sql_invstatus[]= " f.paye=0 ";
							}
							if (array_key_exists('1',$invstatus)) {
								$sql_invstatus[]= " fk_statut=0 ";
							}
							$sql .= ' AND ('.implode(' OR ', $sql_invstatus).')';
						} elseif ($key != 'startyear' && $key != 'group_by_session') {
							$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
						}

					}
				}

				if(array_key_exists('group_by_session', $filter)) $sql.= " GROUP BY s.rowid,facdet.rowid ORDER BY s.rowid ASC";
				else $sql.=" GROUP BY facdet.rowid";
				dol_syslog(get_class($this) . "::fetch_ca HT TTC sql=" . $sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$this->value_ca_total_ht[$year_todo][$month_todo]['total'] = 0;
					$this->value_ca_total_ttc[$year_todo][$month_todo]['total'] = 0;
					$this->value_ca_total_ht[$year_todo][$month_todo]['detail'] = array();
					$this->value_ca_total_ttc[$year_todo][$month_todo]['detail'] = array();

					if ($this->db->num_rows($resql)) {

						while ( $obj = $this->db->fetch_object($resql) ) {
							if (! empty($obj->amount_ht)) {
								$this->value_ca_total_ht[$year_todo][$month_todo]['total'] += $obj->amount_ht;
							}

							if (! empty($obj->amount_ttc)) {
								$this->value_ca_total_ttc[$year_todo][$month_todo]['total'] += $obj->amount_ttc;
							}

							if($filter['group_by_session']) {
								if (! empty($obj->amount_ht)) {
									$this->value_ca_total_ht[$year_todo][$month_todo]['detail'][$obj->rowid] = $obj->amount_ht;
								}

								if (! empty($obj->amount_ttc)) {
									$this->value_ca_total_ttc[$year_todo][$month_todo]['detail'][$obj->rowid] = $obj->amount_ttc;
								}
							}
						}
					}
				} else {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::fetch_ca TTC" . $this->error, LOG_ERR);
					return - 1;
				}
				$this->db->free($resql);

				// For TotalHF/HT
				$sql = "SELECT (facdet.total_ttc) as amount_ttc";
				$sql.= ", (facdet.total_ht) as amount_ht";
				if(array_key_exists('group_by_session', $filter)) $sql.= ", s.rowid";
				$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as facdet ON facdet.fk_facture=f.rowid ";
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "product as prod ON prod.rowid=facdet.fk_product ";
				//$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "categorie_product as catprod ON catprod.fk_categorie IN (" . $conf->global->AGF_CAT_PRODUCT_CHARGES . ") AND catprod.fk_product=prod.rowid ";
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so";
				$sql .= " ON so.rowid = f.fk_soc";
				if (
						array_key_exists('sale.fk_user', $filter)
					||	array_key_exists('group_by_session', $filter)
					||	array_key_exists('s.type_session', $filter)
					||	array_key_exists('socrequester.nom', $filter)
					||	array_key_exists('so.parent|sorequester.parent', $filter)
					||	preg_match('/^s\./', $accounting_date)
				) {
					$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_element as sesselement";
					$sql .= " ON sesselement.element_type='invoice' AND f.rowid = sesselement.fk_element";
					$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as s";
					$sql .= " ON s.rowid = sesselement.fk_session_agefodd ";
					$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as socrequester";
					$sql .= " ON socrequester.rowid = s.fk_soc_requester";
				}

				$sql .= " WHERE YEAR(".$accounting_date.")=" . $year_todo;
				$sql .= " AND MONTH(".$accounting_date.")=" . $month_todo;
				$sql .= " AND f.fk_statut in (1,2)";
				$sql .= " AND facdet.fk_product IN (SELECT fk_product FROM " . MAIN_DB_PREFIX . "categorie_product WHERE fk_categorie IN (" . $conf->global->AGF_CAT_PRODUCT_CHARGES . "))";
				if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
					$sql .= " AND f.type IN (0,1,2)";
				else
					$sql .= " AND f.type IN (0,1,2,3)";

				// Manage filter
				if (count($filter) > 0) {
					foreach ( $filter as $key => $value ) {
						if ($key == 's.type_session') {
							$sql .= ' AND ' . $key . ' = ' . $this->db->escape($value);
						} elseif ($key == 'sale.fk_user') {
							$sql .= ' AND (' . $value . ' IN (';
							$sql .= '	SELECT sale.fk_user';
							$sql .= '	FROM ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire ass';
							$sql .= '	INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire trainee ON trainee.rowid = ass.fk_stagiaire';
							$sql .= '	INNER JOIN ' . MAIN_DB_PREFIX . 'societe_commerciaux as sale ON sale.fk_soc = trainee.fk_soc';
							$sql .= '	LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_opca opca ON ass.fk_session_agefodd = opca.fk_session_agefodd AND opca.fk_session_trainee = ass.rowid';
							$sql .= '	INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_element ase ON ase.fk_session_agefodd = ass.fk_session_agefodd AND ase.element_type = "invoice"';
							$sql .= '	INNER JOIN ' . MAIN_DB_PREFIX . 'facture f2 ON ase.fk_element = f2.rowid';
							$sql .= '	WHERE ass.fk_session_agefodd = s.rowid';
							$sql .= '	AND f2.rowid = f.rowid';
							//$sql .= '	AND f2.fk_soc = COALESCE(IF(s.type_session = 1, IF(opca.fk_soc_OPCA <= 0, NULL, opca.fk_soc_OPCA), s.fk_soc_OPCA), trainee.fk_soc)'; // TODO : remove this comment if all is ok after few tests with CASE style
							$sql .= '	AND f2.fk_soc = COALESCE(CASE WHEN s.type_session = 1 THEN CASE WHEN opca.fk_soc_OPCA <= 0 THEN NULL ELSE opca.fk_soc_OPCA END ELSE s.fk_soc_OPCA END, trainee.fk_soc)';
							$sql .= '	AND sale.fk_soc = COALESCE(trainee.fk_soc, s.fk_soc)';
							$sql .= ')';
							$sql .= ' OR ';
							$sql .= ' (' . $value . ' IN (SELECT  salecom.fk_user FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux as salecom ';
							$sql .= ' WHERE salecom.fk_soc=s.fk_soc AND s.rowid NOT IN (SELECT asscom.fk_session_agefodd FROM ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire asscom))';
							$sql .= ' )';
							$sql .= ')';
						} elseif ($key == 'so.parent|sorequester.parent') {
							$sql .= ' AND (so.parent=' . $this->db->escape($value) . ' OR socrequester.parent=' . $this->db->escape($value);
							$sql .= ' OR so.rowid=' . $this->db->escape($value) . ' OR socrequester.rowid=' . $this->db->escape($value) . ')';
						} /*elseif ($key == 'so.nom') {
						// Search for all thirdparty concern by the session
						$sql .= ' AND ((' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\') OR (s.rowid IN (SELECT innersess.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session as innersess ';
								$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire as inserss ON innersess.rowid = inserss.fk_session_agefodd';
								$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire as insersta ON insersta.rowid = inserss.fk_stagiaire ';
								$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe as insersoc ON insersoc.rowid = insersta.fk_soc ';
								$sql .= ' WHERE insersoc.nom LIKE \'%' . $this->db->escape($value) . '%\' )))';
						}*/ elseif ($key == 'invstatus') {
							$invstatus=array_flip($value);
							$sql_invstatus=array();

							if (array_key_exists('2',$invstatus)) {
								$sql_invstatus[]= " f.paye=1 ";
							}
							if (array_key_exists('3',$invstatus)) {
								$sql_invstatus[]= " f.paye=0 ";
							}
							if (array_key_exists('1',$invstatus)) {
								$sql_invstatus[]= " fk_statut=0 ";
							}
							$sql .= ' AND ('.implode(' OR ', $sql_invstatus).')';
						} elseif ($key != 'startyear' && $key != 'group_by_session') {
							$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
						}
					}
				}

				if(array_key_exists('group_by_session', $filter)) $sql.= " GROUP BY s.rowid,facdet.rowid ORDER BY s.rowid ASC";
				else $sql.=" GROUP BY facdet.rowid";

				dol_syslog(get_class($this) . "::fetch_ca HFHT sql=" . $sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$this->value_ca_total_hthf[$year_todo][$month_todo] = $this->value_ca_total_ht[$year_todo][$month_todo];
					if ($this->db->num_rows($resql)) {
						while ( $obj = $this->db->fetch_object($resql) ) {
							if (! empty($obj->amount_ht)) {
								$this->value_ca_total_hthf[$year_todo][$month_todo]['total'] -= $obj->amount_ht;

								if($filter['group_by_session']) {
									$this->value_ca_total_hthf[$year_todo][$month_todo]['detail'][$obj->rowid] -= $obj->amount_ht;
								}
							}
						}
					}
				} else {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::fetch_ca " . $this->error, LOG_ERR);
					return - 1;
				}
				$this->db->free($resql);
			}
		}

		// Percent

		$this->persent_ca_total_ht = array ();
		$this->persent_ca_total_ttc = array ();
		$this->persent_ca_total_hthf = array ();

		foreach ( $this->year_to_report_array as $year_todo ) {
			for($month_todo = 1; $month_todo <= 12; $month_todo ++) {
				if (array_key_exists($year_todo - 1, $this->value_ca_total_hthf) && $this->value_ca_total_hthf[$year_todo - 1][$month_todo]['total'] != 0) {
					$this->persent_ca_total_hthf[$year_todo][$month_todo] = (($this->value_ca_total_hthf[$year_todo][$month_todo]['total'] - $this->value_ca_total_hthf[$year_todo - 1][$month_todo]['total']) / $this->value_ca_total_hthf[$year_todo - 1][$month_todo]['total']);
					// In excel file it is formated as percentage, no need to multibly by 100
				} else {
					$this->persent_ca_total_hthf[$year_todo][$month_todo] = 'N/A';
				}

				if (array_key_exists($year_todo - 1, $this->value_ca_total_ht) && $this->value_ca_total_ht[$year_todo - 1][$month_todo]['total'] != 0) {
					$this->persent_ca_total_ht[$year_todo][$month_todo] = (($this->value_ca_total_ht[$year_todo][$month_todo]['total'] - $this->value_ca_total_ht[$year_todo - 1][$month_todo]['total']) / $this->value_ca_total_ht[$year_todo - 1][$month_todo]['total']);
					// In excel file it is formated as percentage, no need to multibly by 100
				} else {
					$this->persent_ca_total_ht[$year_todo][$month_todo] = 'N/A';
				}
			}
			/*if (array_key_exists($year_todo - 1, $this->value_ca_total_ht)) {
				$total_year = 0;
				$total_past_year = 0;
				for($month_todo = 1; $month_todo <= 12; $month_todo ++) {
					$total_year += $this->value_ca_total_ht[$year_todo][$month_todo];
				}
				for($month_todo = 1; $month_todo <= 12; $month_todo ++) {
					$total_past_year += $this->value_ca_total_ht[$year_todo - 1][$month_todo];
				}
				if ($total_year != 0) {
					$this->persent_ca_total_ht[$year_todo] = ((($total_year - $total_past_year) * 100) / $total_year);

					// In excel file it is formated as percentage so ...
					$this->persent_ca_total_ht[$year_todo] = $this->persent_ca_total_ht[$year_todo] / 100;
				} else {
					$this->persent_ca_total_ht[$year_todo] = 'N/A';
				}
			} else {
				$this->persent_ca_total_ht[$year_todo] = 'N/A';
			}*/
		}
	}
}

