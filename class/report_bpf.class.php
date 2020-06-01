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
 * \file /agefodd/class/report_bpf.php
 * \ingroup agefodd
 * \brief File of class to generate report for agefodd
 */
require_once ('agefodd_export_excel.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');

/**
 * Class to build report by customer
 */
class ReportBPF extends AgefoddExportExcel
{
	private $trainer_data = array();
	private $trainee_data = array();
	private $trainee_data_f2 = array();
	private $financial_data = array();
	private $financial_data_c = array();
	private $financial_data_outcome = array();
	public $warnings = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	public function __construct($db, $outputlangs) {
		$outputlangs->load('agefodd@agefodd');
		$outputlangs->load("main");

		$sheet_array = array(
				0 => array(
						'name' => 'bpf',
						'title' => $outputlangs->transnoentities('AgfMenuReportBPF')
				)
		);

		$array_column_header = array();

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
						if ($key == 'search_year') {
							$str_cirteria = $this->outputlangs->transnoentities('Year') . ' ';
							$str_criteria_value = $value;
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $this->row[$keysheet], $str_cirteria);
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $str_criteria_value);
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
				if ($key == 'search_date_start') {
					$str_sub_name .= $this->outputlangs->transnoentities('From');
					$str_sub_name .= dol_print_date($value);
				}
				if ($key == 'search_date_end') {
					$str_sub_name .= $this->outputlangs->transnoentities('to');
					$str_sub_name .= dol_print_date($value);
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
		$this->outputlangs->load('agefodd@agefodd');

		$this->title = $this->outputlangs->transnoentities('AgfMenuReportBPF');
		$this->subject = $this->outputlangs->transnoentities('AgfMenuReportBPF');
		$this->description = $this->outputlangs->transnoentities('AgfMenuReportBPF');
		$this->keywords = $this->outputlangs->transnoentities('AgfMenuReportBPF');

		$result = $this->open_file($this->file);
		if ($result < 0) {
			return $result;
		}

		// Fetch Financial data Bock C
		$result = $this->fetch_financial_c($filter);
		if ($result < 0) {
			return $result;
		}

		// Contruct header (column name)
		$array_column_header = array();
		$array_column_header[0][1] = array(
				'type' => 'text',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFOrigProd')
		);

		$array_column_header[0][2] = array(
				'type' => 'number',
				'title' => $this->outputlangs->transnoentities('Amount')
		);

		$this->setArrayColumnHeader($array_column_header);

		$result = $this->write_header();
		if ($result < 0) {
			return $result;
		}



		// Ouput Lines
		$line_to_output = array();
		$array_total_output = array();
		if (is_array($this->financial_data) && count($this->financial_data) > 0) {
			foreach ( $this->financial_data as $label_type => $financial_data ) {
				$line_to_output[1] = $label_type;
				$line_to_output[2] = $financial_data;

				$array_total_output[1] = $this->outputlangs->transnoentities('Total');
				$array_total_output[2] += $financial_data;

				$result = $this->write_line($line_to_output, 0);
				if ($result < 0) {
					return $result;
				}
			}
			$result = $this->write_line_total($array_total_output, '3d85c6');
			if ($result < 0) {
				return $result;
			}

			// Fetch Financial data Bock d
			$result = $this->fetch_financial_d($filter);
			if ($result < 0) {
				return $result;
			}
		}

		// Contruct header (column name)
		$array_column_header = array();
		$array_column_header[0][1] = array(
				'type' => 'text',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFChargeProd')
		);

		$array_column_header[0][2] = array(
				'type' => 'number',
				'title' => $this->outputlangs->transnoentities('Amount')
		);

		$this->setArrayColumnHeader($array_column_header);

		$result = $this->write_header();
		if ($result < 0) {
			return $result;
		}

		// Ouput Lines
		$line_to_output = array();
		$array_total_output = array();
		if (is_array($this->financial_data_d) && count($this->financial_data_d) > 0) {
			foreach ( $this->financial_data_d as $label_type => $financial_data ) {
				$line_to_output[1] = $label_type;
				$line_to_output[2] = $financial_data;

				$array_total_output[1] = $this->outputlangs->transnoentities('Total');
				$array_total_output[2] += $financial_data;

				$result = $this->write_line($line_to_output, 0);
				if ($result < 0) {
					return $result;
				}
			}
			$result = $this->write_line_total($array_total_output, '3d85c6');
			if ($result < 0) {
				return $result;
			}
		}

		// Fetch Trainer Block E
		$result = $this->fetch_trainer($filter);
		if ($result < 0) {
			return $result;
		}

		// Contruct header (column name)
		$array_column_header = array();
		$array_column_header[0][1] = array(
				'type' => 'text',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFChaperE')
		);

		$array_column_header[0][2] = array(
				'type' => 'int',
				'title' => $this->outputlangs->transnoentities('AgfFormateurNb')
		);
		$array_column_header[0][3] = array(
				'type' => 'hours',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFNbHour')
		);
		// 'autosize' => 0

		$this->setArrayColumnHeader($array_column_header);

		$result = $this->write_header();
		if ($result < 0) {
			return $result;
		}

		// Ouput Lines
		$line_to_output = array();
		$array_total_output = array();
		$array_total_output[2] = 0;
		$array_total_output[3] = 0;
		if (is_array($this->trainer_data) && count($this->trainer_data) > 0) {
			foreach ( $this->trainer_data as $label_type => $trainer_data ) {
				$line_to_output[1] = $label_type;
				$line_to_output[2] = $trainer_data['nb'];
				$line_to_output[3] = $trainer_data['time'];

				$array_total_output[1] = 'Total';
				$array_total_output[2] += $trainer_data['nb'];
				$array_total_output[3] += $trainer_data['time'];

				$result = $this->write_line($line_to_output, 0);
				if ($result < 0) {
					return $result;
				}
			}

			$result = $this->write_line_total($array_total_output, '3d85c6');
			if ($result < 0) {
				return $result;
			}
		}

		// Fetch Trainee Block F -1
		$result = $this->fetch_trainee($filter);
		if ($result < 0) {
			return $result;
		}

		// Contruct header (column name)
		$array_column_header = array();
		$array_column_header[0][1] = array(
				'type' => 'text',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFChaperF1')
		);

		$array_column_header[0][2] = array(
				'type' => 'int',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFNbPart')
		);
		$array_column_header[0][3] = array(
				'type' => 'hours',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFNbHeureSta')
		);
		// 'autosize' => 0

		$this->setArrayColumnHeader($array_column_header);

		$result = $this->write_header();
		if ($result < 0) {
			return $result;
		}

		// Ouput Lines
		$line_to_output = array();
		$array_total_output = array();
		if (is_array($this->trainee_data) && count($this->trainee_data) > 0) {
			foreach ( $this->trainee_data as $label_type => $trainee_data ) {
				$line_to_output[1] = $label_type;
				$line_to_output[2] = $trainee_data['nb'];
				$line_to_output[3] = $trainee_data['time'];
				$array_total_output[1] = 'Total';
				$array_total_output[2] += $trainee_data['nb'];
				$array_total_output[3] += $trainee_data['time'];

				$result = $this->write_line($line_to_output, 0);
				if ($result < 0) {
					return $result;
				}
			}
			$result = $this->write_line_total($array_total_output, '3d85c6');
			if ($result < 0) {
				return $result;
			}
		}

		// Fetch Trainee Block F -2
		$result = $this->fetch_trainee_f2($filter);
		if ($result < 0) {
			return $result;
		}

		// Contruct header (column name)
		$array_column_header = array();
		$array_column_header[0][1] = array(
				'type' => 'text',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFChaperF2')
		);

		$array_column_header[0][2] = array(
				'type' => 'int',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFNbPart')
		);
		$array_column_header[0][3] = array(
				'type' => 'hours',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFNbHeureSta')
		);
		// 'autosize' => 0

		$this->setArrayColumnHeader($array_column_header);

		$result = $this->write_header();
		if ($result < 0) {
			return $result;
		}

		// Ouput Lines
		$line_to_output = array();
		$array_total_output = array();
		if (is_array($this->trainee_data_f2) && count($this->trainee_data_f2) > 0) {
			foreach ( $this->trainee_data_f2 as $label_type => $trainee_data ) {
				$line_to_output[1] = $label_type;
				$line_to_output[2] = $trainee_data['nb'];
				$line_to_output[3] = $trainee_data['time'];
				$array_total_output[1] = 'Total';
				$array_total_output[2] += $trainee_data['nb'];
				$array_total_output[3] += $trainee_data['time'];

				$result = $this->write_line($line_to_output, 0);
				if ($result < 0) {
					return $result;
				}
			}
			$result = $this->write_line_total($array_total_output, '3d85c6');
			if ($result < 0) {
				return $result;
			}
		}

		// Fetch Trainee Block F -3
		$result = $this->fetch_trainee_f3($filter);
		if ($result < 0) {
			return $result;
		}

		// Contruct header (column name)
		$array_column_header = array();
		$array_column_header[0][1] = array(
				'type' => 'text',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFChaperF3')
		);

		$array_column_header[0][2] = array(
				'type' => 'int',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFNbPart')
		);
		$array_column_header[0][3] = array(
				'type' => 'hours',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFNbHeureSta')
		);
		// 'autosize' => 0

		$this->setArrayColumnHeader($array_column_header);

		$result = $this->write_header();
		if ($result < 0) {
			return $result;
		}

		// Ouput Lines
		$line_to_output = array();
		$array_total_output = array();
		if (is_array($this->trainee_data_f3) && count($this->trainee_data_f3) > 0) {
			foreach ( $this->trainee_data_f3 as $label_type => $trainee_data ) {
				$line_to_output[1] = $label_type;
				$line_to_output[2] = $trainee_data['nb'];
				$line_to_output[3] = $trainee_data['time'];
				$array_total_output[1] = 'Total';
				$array_total_output[2] += $trainee_data['nb'];
				$array_total_output[3] += $trainee_data['time'];

				$result = $this->write_line($line_to_output, 0);
				if ($result < 0) {
					return $result;
				}
			}
			$result = $this->write_line_total($array_total_output, '3d85c6');
			if ($result < 0) {
				return $result;
			}
		}

		// Fetch Trainee Block F -4
		$result = $this->fetch_trainee_f4($filter);
		if ($result < 0) {
			return $result;
		}

		// Contruct header (column name)
		$array_column_header = array();
		$array_column_header[0][1] = array(
				'type' => 'text',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFChaperF4')
		);

		$array_column_header[0][2] = array(
				'type' => 'int',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFNbPart')
		);
		$array_column_header[0][3] = array(
				'type' => 'hours',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFNbHeureSta')
		);
		// 'autosize' => 0

		$this->setArrayColumnHeader($array_column_header);

		$result = $this->write_header();
		if ($result < 0) {
			return $result;
		}

		// Ouput Lines
		$line_to_output = array();
		$array_total_output = array();
		if (is_array($this->trainee_data_f4) && count($this->trainee_data_f4) > 0) {
			foreach ( $this->trainee_data_f4 as $label_type => $trainee_data ) {
				$line_to_output[1] = $label_type;
				$line_to_output[2] = $trainee_data['nb'];
				$line_to_output[3] = $trainee_data['time'];
				$array_total_output[1] = 'Total';
				$array_total_output[2] += $trainee_data['nb'];
				$array_total_output[3] += $trainee_data['time'];

				$result = $this->write_line($line_to_output, 0);
				if ($result < 0) {
					return $result;
				}
			}
			$result = $this->write_line_total($array_total_output, '3d85c6');
			if ($result < 0) {
				return $result;
			}
		}

		// Fetch Trainee Block G
		$result = $this->fetch_trainee_g($filter);
		if ($result < 0) {
			return $result;
		}

		// Contruct header (column name)
		$array_column_header = array();
		$array_column_header[0][1] = array(
				'type' => 'text',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFChaperG')
		);

		$array_column_header[0][2] = array(
				'type' => 'int',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFNbPart')
		);
		$array_column_header[0][3] = array(
				'type' => 'hours',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFNbHeureSta')
		);
		// 'autosize' => 0

		$this->setArrayColumnHeader($array_column_header);

		$result = $this->write_header();
		if ($result < 0) {
			return $result;
		}

		// Ouput Lines
		$line_to_output = array();
		$array_total_output = array();
		if (is_array($this->trainee_data_g) && count($this->trainee_data_g) > 0) {
			foreach ( $this->trainee_data_g as $label_type => $trainee_data ) {
				$line_to_output[1] = $label_type;
				$line_to_output[2] = $trainee_data['nb'];
				$line_to_output[3] = $trainee_data['time'];
				$array_total_output[1] = 'Total';
				$array_total_output[2] += $trainee_data['nb'];
				$array_total_output[3] += $trainee_data['time'];

				$result = $this->write_line($line_to_output, 0);
				if ($result < 0) {
					return $result;
				}
			}
			$result = $this->write_line_total($array_total_output, '3d85c6');
			if ($result < 0) {
				return $result;
			}
		}

		$this->close_file(0, 0, 0);
		return count($this->trainer_data) + count($this->trainee_data) + count($this->financial_data);
		// return 1;
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_trainee_f2($filter = array()) {
		global $langs, $conf;

		$key = 'Formés par votre organisme pour son propre compte';
		$sql = "select count(DISTINCT sesssta.rowid) as cnt , ";
		if ($this->db->type == 'pgsql') {
			$sql .= "SUM(TIME_TO_SEC(TIMEDIFF('second',statime.heuref, statime.heured)))/(24*60*60) as timeinsession";
		} else {
			$sql .= "SUM(TIME_TO_SEC(TIMEDIFF(statime.heuref, statime.heured)))/(24*60*60) as timeinsession";
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS sesssta ON sesssta.fk_session_agefodd=sess.rowid AND sesssta.status_in_session IN (3,4) ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sesssta.fk_stagiaire ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.fk_agefodd_session=sess.rowid ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
		$sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
		$sql .= " AND sess.status IN (5,6)";
		$sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
		$sql .= " AND COALESCE(sess.fk_soc_employer, 0) = 0";

		dol_syslog(get_class($this) . "::" . __METHOD__. "-".$key, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->trainee_data_f2[$key]['nb'] = $obj->cnt;
					$this->trainee_data_f2[$key]['time'] = $obj->timeinsession;
				}
			}
			if (!empty($conf->global->AGF_USE_REAL_HOURS)){
			    $sql = "select count(DISTINCT assh.fk_stagiaire) as cnt , ";
			    $sql .= "SUM(assh.heures)/24 as timeinsession";
			    $sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire_heures as assh";
			    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as sess ON sess.rowid = assh.fk_session";
                $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.rowid=assh.fk_calendrier ";
	            $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
                $sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
			    $sql .= " AND sess.status IN (5,6)";
                $sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
                $sql .= " AND COALESCE(sess.fk_soc_employer, 0) = 0";

			    dol_syslog(get_class($this) . " AGF_USE_REAL_HOURS::" . __METHOD__. "-".$key, LOG_DEBUG);
			    $resql2 = $this->db->query($sql);
			    if ($resql2) {
			    	$num=$this->db->num_rows($resql);
			    	if (empty($num)) {
			            $this->trainee_data_f2[$key]['nb'] = 0;
			            $this->trainee_data_f2[$key]['time'] = 0;
			        }
			        if ($this->db->num_rows($resql2)){
			            while($obj = $this->db->fetch_object($resql2)){
    			            $this->trainee_data_f2[$key]['nb'] += $obj->cnt;
    			            $this->trainee_data_f2[$key]['time'] += $obj->timeinsession;
    			        }
			        }

			    }
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);

		// Add time from FOAD
		$sql = "select count(DISTINCT sesssta.rowid) as cnt, SUM(sesssta.hour_foad)/24 as timeinsession ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS sesssta ON sesssta.fk_session_agefodd=sess.rowid AND sesssta.status_in_session IN (3,4) ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sesssta.fk_stagiaire ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.fk_agefodd_session=sess.rowid ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
		$sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
		$sql .= " AND sess.status IN (5,6)";
		$sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
		$sql .= " AND COALESCE(sess.fk_soc_employer, 0) = 0";
		$sql .= " AND COALESCE(sesssta.hour_foad, 0) <> 0";

		dol_syslog(get_class($this) . " - FOAD ::" . __METHOD__. "-".$key, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					if (array_key_exists($key, $this->trainee_data_f2) && !empty($obj->timeinsession)) {
						$this->trainee_data_f2[$key]['time'] += $obj->timeinsession;
					} /*else {
						$this->trainee_data_f2[$key]['nb'] = $obj->cnt;
						$this->trainee_data_f2[$key]['time'] = $obj->timeinsession;
					}*/
				}
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);

		$key = 'Formés par votre organisme pour le compte d’un autre organisme';
		$sql = "select count(DISTINCT sesssta.rowid) as cnt , ";
		if ($this->db->type == 'pgsql') {
			$sql .= "SUM(TIME_TO_SEC(TIMEDIFF('second',statime.heuref, statime.heured)))/(24*60*60) as timeinsession";
		} else {
			$sql .= "SUM(TIME_TO_SEC(TIMEDIFF(statime.heuref, statime.heured)))/(24*60*60) as timeinsession";
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS sesssta ON sesssta.fk_session_agefodd=sess.rowid AND sesssta.status_in_session IN (3,4) ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sesssta.fk_stagiaire ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.fk_agefodd_session=sess.rowid ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
		$sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
		$sql .= " AND sess.status IN (5,6)";
		$sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
		$sql .= " AND COALESCE(sess.fk_soc_employer, 0) > 0";

        dol_syslog(get_class($this) . "::" . __METHOD__. "-".$key, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->trainee_data_f2[$key]['nb'] = $obj->cnt;
					$this->trainee_data_f2[$key]['time'] = $obj->timeinsession;
				}
			}
            if (!empty($conf->global->AGF_USE_REAL_HOURS)){
                $sql = "select count(DISTINCT assh.fk_stagiaire) as cnt , ";
                $sql .= "SUM(assh.heures)/24 as timeinsession";
                $sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire_heures as assh";
                $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as sess ON sess.rowid = assh.fk_session";
                $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.rowid=assh.fk_calendrier ";
	            $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
                $sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
                $sql .= " AND sess.status IN (5,6)";
                $sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
                $sql .= " AND COALESCE(sess.fk_soc_employer, 0) > 0";

                dol_syslog(get_class($this) . " AGF_USE_REAL_HOURS::" . __METHOD__. "-".$key, LOG_DEBUG);
                $resql2 = $this->db->query($sql);
                if ($resql2) {
                	$num=$this->db->num_rows($resql);
                	if (empty($num)) {
                        $this->trainee_data_f2[$key]['nb'] = 0;
                        $this->trainee_data_f2[$key]['time'] = 0;
                    }
                    if ($this->db->num_rows($resql2)){
                        while($obj = $this->db->fetch_object($resql2)){
                            $this->trainee_data_f2[$key]['nb'] += $obj->cnt;
                            $this->trainee_data_f2[$key]['time'] += $obj->timeinsession;
                        }
                    }

                }
            }
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);

		// Add time from FOAD
		$sql = "select count(DISTINCT sesssta.rowid) as cnt, SUM(sesssta.hour_foad)/24 as timeinsession ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS sesssta ON sesssta.fk_session_agefodd=sess.rowid AND sesssta.status_in_session IN (3,4) ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sesssta.fk_stagiaire ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.fk_agefodd_session=sess.rowid ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
		$sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
		$sql .= " AND sess.status IN (5,6)";
		$sql .= " AND COALESCE(sesssta.hour_foad, 0) <> 0";
        $sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
        $sql .= " AND COALESCE(sess.fk_soc_employer, 0) > 0";

		dol_syslog(get_class($this) . " - FOAD ::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					if (array_key_exists($key, $this->trainee_data_f2) && !empty($obj->timeinsession)) {
						$this->trainee_data_f2[$key]['time'] += $obj->timeinsession;
					} /*else {
						$this->trainee_data_f2[$key]['nb'] = $obj->cnt;
						$this->trainee_data_f2[$key]['time'] = $obj->timeinsession;
					}*/
				}
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_trainee_f3($filter = array()) {
		global $langs, $conf;

		$sql = "select count(DISTINCT sesssta.rowid) as cnt, catform.intitule, ";
		if ($this->db->type == 'pgsql') {
			$sql .= "SUM(TIME_TO_SEC(TIMEDIFF('second',statime.heuref, statime.heured)))/(24*60*60) as timeinsession";
		} else {
			$sql .= "SUM(TIME_TO_SEC(TIMEDIFF(statime.heuref, statime.heured)))/(24*60*60) as timeinsession";
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS sesssta ON sesssta.fk_session_agefodd=sess.rowid AND sesssta.status_in_session IN (3,4) ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sesssta.fk_stagiaire ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.fk_agefodd_session=sess.rowid ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue ";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_type_bpf as catform ON catform.rowid=formation.fk_c_category_bpf ";
		$sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
		$sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
		$sql .= " AND sess.status IN (5,6)";
		$sql .= " GROUP BY catform.intitule";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->trainee_data_f3[$obj->intitule]['nb'] = $obj->cnt;
					$this->trainee_data_f3[$obj->intitule]['time'] = $obj->timeinsession;
				}
			}
			if (!empty($conf->global->AGF_USE_REAL_HOURS)){
			    $sql = "select count(DISTINCT assh.fk_stagiaire) as cnt , catform.intitule,";
			    $sql .= "SUM(assh.heures)/24 as timeinsession";
			    $sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire_heures as assh";
			    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as sess ON sess.rowid = assh.fk_session";
			    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.rowid=assh.fk_calendrier ";
			    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue ";
			    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_type_bpf as catform ON catform.rowid=formation.fk_c_category_bpf ";
			    $sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
			    $sql .= " AND sess.status IN (5,6)";
		        $sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
			    $sql .= " GROUP BY catform.intitule";

			    dol_syslog(get_class($this) . " AGF_USE_REAL_HOURS::" . __METHOD__, LOG_DEBUG);
			    $resql2 = $this->db->query($sql);
			    if ($resql2) {
			    	$num=$this->db->num_rows($resql);
			    	if (empty($num)) {
			            $this->trainee_data_f3[$obj->intitule]['nb'] = 0;
			            $this->trainee_data_f3[$obj->intitule]['time'] = 0;
			        }
			        if ($this->db->num_rows($resql2)){
			            while($obj = $this->db->fetch_object($resql2)){
			                $this->trainee_data_f3[$obj->intitule]['nb'] += $obj->cnt;
			                $this->trainee_data_f3[$obj->intitule]['time'] += $obj->timeinsession;
			            }
			        }

			    }
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);

		// Add time from FOAD

		$sql = "select SUM(sesssta.hour_foad)/24 as timeinsession, catform.intitule ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS sesssta ON sesssta.fk_session_agefodd=sess.rowid AND sesssta.status_in_session IN (3,4) ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sesssta.fk_stagiaire ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.fk_agefodd_session=sess.rowid ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_type_bpf as catform ON catform.rowid=formation.fk_c_category_bpf ";
		$sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
		$sql .= " AND sess.status IN (5,6)";
		$sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
		$sql .= " AND COALESCE(sesssta.hour_foad, 0) <> 0";
		$sql .= " GROUP BY catform.intitule";

		dol_syslog(get_class($this) . " - FOAD ::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					if (array_key_exists($obj->intitule, $this->trainee_data_f3)  && !empty($obj->timeinsession)) {
						$this->trainee_data_f3[$obj->intitule]['time'] += $obj->timeinsession;
					} /*else {
						$this->trainee_data_f3[$obj->intitule]['nb'] = $obj->cnt;
						$this->trainee_data_f3[$obj->intitule]['time'] = $obj->timeinsession;
					}*/
				}
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_trainee_f4($filter = array()) {
		global $langs, $conf;

		$sql = "select count(DISTINCT sesssta.rowid) as cnt, CONCAT(catform.code , '-', catform.intitule) as intitule, ";
		if ($this->db->type == 'pgsql') {
			$sql .= "SUM(TIME_TO_SEC(TIMEDIFF('second',statime.heuref, statime.heured)))/(24*60*60) as timeinsession";
		} else {
			$sql .= "SUM(TIME_TO_SEC(TIMEDIFF(statime.heuref, statime.heured)))/(24*60*60) as timeinsession";
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS sesssta ON sesssta.fk_session_agefodd=sess.rowid AND sesssta.status_in_session IN (3,4) ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sesssta.fk_stagiaire ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.fk_agefodd_session=sess.rowid ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_type as catform ON catform.rowid=formation.fk_c_category ";
		$sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
		$sql .= " AND sess.status IN (5,6)";
		$sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
		$sql .= " GROUP BY CONCAT(catform.code , '-', catform.intitule)";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->trainee_data_f4[$obj->intitule]['nb'] = $obj->cnt;
					$this->trainee_data_f4[$obj->intitule]['time'] = $obj->timeinsession;
				}
			}
			if (!empty($conf->global->AGF_USE_REAL_HOURS)){
			    $sql = "select count(DISTINCT assh.fk_stagiaire) as cnt , CONCAT(catform.code , '-', catform.intitule) as intitule,";
			    $sql .= "SUM(assh.heures)/24 as timeinsession";
			    $sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire_heures as assh";
			    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as sess ON sess.rowid = assh.fk_session";
			    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.rowid=assh.fk_calendrier ";
			    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue ";
			    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_type as catform ON catform.rowid=formation.fk_c_category ";
			    $sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
			    $sql .= " AND sess.status IN (5,6)";
		        $sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
			    $sql .= " GROUP BY CONCAT(catform.code , '-', catform.intitule)";

			    dol_syslog(get_class($this) . " AGF_USE_REAL_HOURS::" . __METHOD__, LOG_DEBUG);
			    $resql2 = $this->db->query($sql);
			    if ($resql2) {
			    	$num=$this->db->num_rows($resql);
			    	if (empty($num)) {
			            $this->trainee_data_f4[$obj->intitule]['nb'] = 0;
			            $this->trainee_data_f4[$obj->intitule]['time'] = 0;
			        }
			        if ($this->db->num_rows($resql2)){
			            while($obj = $this->db->fetch_object($resql2)){
			                $this->trainee_data_f4[$obj->intitule]['nb'] += $obj->cnt;
			                $this->trainee_data_f4[$obj->intitule]['time'] += $obj->timeinsession;
			            }
			        }

			    }
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);

		// Add time from FOAD
		$sql = "select count(DISTINCT sesssta.rowid) as cnt ,SUM(sesssta.hour_foad)/24 as timeinsession,CONCAT(catform.code , '-', catform.intitule) as intitule ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS sesssta ON sesssta.fk_session_agefodd=sess.rowid AND sesssta.status_in_session IN (3,4) ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sesssta.fk_stagiaire ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.fk_agefodd_session=sess.rowid ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue_type as catform ON catform.rowid=formation.fk_c_category ";
		$sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
		$sql .= " AND sess.status IN (5,6)";
		$sql .= " AND COALESCE(sesssta.hour_foad, 0) <> 0";
		$sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
		$sql .= " GROUP BY CONCAT(catform.code , '-', catform.intitule)";

		dol_syslog(get_class($this) . " - FOAD ::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					if (array_key_exists($obj->intitule, $this->trainee_data_f4) && !empty($obj->timeinsession) ) {
						$this->trainee_data_f4[$obj->intitule]['time'] += $obj->timeinsession;
					} /*else {
						$this->trainee_data_f4[$obj->intitule]['nb'] = $obj->cnt;
						$this->trainee_data_f4[$obj->intitule]['time'] = $obj->timeinsession;
					}*/
				}
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_trainee_g($filter = array()) {
		global $langs, $conf;

		$key = 'Formations confiées par votre organisme à un autre organisme de formation';
		$sql = "SELECT count(DISTINCT sesssta.rowid) as cnt, ";
		if ($this->db->type == 'pgsql') {
			$sql .= " SUM(TIME_TO_SEC(TIMEDIFF('second',statime.heuref, statime.heured)))/(24*60*60) as timeinsession ";
		} else {
			$sql .= " SUM(TIME_TO_SEC(TIMEDIFF(statime.heuref, statime.heured)))/(24*60*60) as timeinsession ";
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS sesssta ON sesssta.fk_session_agefodd=sess.rowid AND sesssta.status_in_session IN (3,4) ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sesssta.fk_stagiaire ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.fk_agefodd_session=sess.rowid ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
		$sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
		$sql .= " AND sess.status IN (5,6)";
		$sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) > 0";

		dol_syslog(get_class($this) . "::" . __METHOD__. ' '.$key, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->trainee_data_g[$key]['nb'] = $obj->cnt;
					$this->trainee_data_g[$key]['time'] = $obj->timeinsession;
				}
			}
			if (!empty($conf->global->AGF_USE_REAL_HOURS)){
			    $sql = "SELECT count(DISTINCT assh.fk_stagiaire) as cnt , ";
			    $sql .= "SUM(assh.heures)/24 as timeinsession";
			    $sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire_heures as assh";
			    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as sess ON sess.rowid = assh.fk_session";
			    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.rowid=assh.fk_calendrier ";
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
			    $sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
			    $sql .= " AND sess.status IN (5,6)";
		        $sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) > 0";

			    dol_syslog(get_class($this) . " AGF_USE_REAL_HOURS::" . __METHOD__, LOG_DEBUG);
			    $resql2 = $this->db->query($sql);
			    if ($resql2) {
			    	$num=$this->db->num_rows($resql);
			    	if (empty($num)) {
			            $this->trainee_data_g[$key]['nb'] = 0;
			            $this->trainee_data_g[$key]['time'] = 0;
			        }
			        if ($this->db->num_rows($resql2)){
			            while($obj = $this->db->fetch_object($resql2)){
			                $this->trainee_data_g[$key]['nb'] += $obj->cnt;
			                $this->trainee_data_g[$key]['time'] += $obj->timeinsession;
			            }
			        }

			    }
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);

		// Add time from FOAD
		$sql = "SELECT count(DISTINCT sesssta.rowid) as cnt ,SUM(sesssta.hour_foad)/24 as timeinsession ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS sesssta ON sesssta.fk_session_agefodd=sess.rowid AND sesssta.status_in_session IN (3,4) ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sesssta.fk_stagiaire ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.fk_agefodd_session=sess.rowid ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
		$sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
		$sql .= " AND sess.status IN (5,6)";
		$sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) > 0";
		$sql .= " AND COALESCE(sesssta.hour_foad, 0) <> 0";

		dol_syslog(get_class($this) . " - FOAD ::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					if (array_key_exists($key, $this->trainee_data_g)  && !empty($obj->timeinsession)) {
						$this->trainee_data_g[$key]['time'] += $obj->timeinsession;
					}/* else {
						$this->trainee_data_g[$key]['nb'] = $obj->cnt;
						$this->trainee_data_g[$key]['time'] = $obj->timeinsession;
					}*/
				}
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_trainer($filter = array()) {
		global $langs, $conf;

		// For Nb Trainer
		$sql = "select count(DISTINCT form.rowid) as cnt, fromtype.intitule, ";
		if ($this->db->type == 'pgsql') {
			$sql .= "SUM(TIME_TO_SEC(TIMEDIFF('second',formtime.heuref, formtime.heured)))/(24*60*60) as timeinsession";
		} else {
			$sql .= "SUM(TIME_TO_SEC(TIMEDIFF(formtime.heuref, formtime.heured)))/(24*60*60) as timeinsession";
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur AS sessform ON sessform.fk_session=sess.rowid AND sessform.trainer_status IN (3,4)";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formateur_type as fromtype ON fromtype.rowid=sessform.fk_agefodd_formateur_type";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as form ON form.rowid=sessform.fk_agefodd_formateur";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur_calendrier as formtime ON formtime.fk_agefodd_session_formateur=sessform.rowid";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
		$sql .= " WHERE formtime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND formtime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
		$sql .= " AND sess.status IN (5,6)";
		$sql .= " AND sess.rowid IN (SELECT DISTINCT fk_session_agefodd FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire)";
		$sql .= " AND formtime.status <> '-1'"; // ne pas compter les heures des créneaux annulés
		$sql .= " GROUP BY fromtype.intitule";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->trainer_data[$obj->intitule]['nb'] = $obj->cnt;
					$this->trainer_data[$obj->intitule]['time'] = $obj->timeinsession;
				}
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_trainee($filter = array()) {
		global $langs, $conf;
		$array_data = array(
				array(
						'label' => 'a-Salariés d’employeurs privés hors apprentis',
						'idtype' => '1,2,7,5,4'
				),
				array(
						'label' => 'b-Apprentis',
						'idtype' => '18'
				),
				array(
						'label' => 'c-Personnes en recherche d’emploi formées par votre organisme de formation',
						'idtype' => '17'
				),
				array(
						'label' => 'd-Particuliers à leurs propres frais formés par votre organisme de formation',
						'idtype' => '15'
				),
				array(
						'label' => 'e-Autres stagiaires',
						'idtype' => '6,8,9,10,11,12,13,14,16,0,20'
				)
		);

		foreach ( $array_data as $key => $data ) {

			$sql = "select count(DISTINCT sesssta.rowid) as cnt , ";
			if ($this->db->type == 'pgsql') {
				$sql .= "SUM(TIME_TO_SEC(TIMEDIFF('second',statime.heuref, statime.heured)))/(24*60*60) as timeinsession";
			} else {
				$sql .= "SUM(TIME_TO_SEC(TIMEDIFF(statime.heuref, statime.heured)))/(24*60*60) as timeinsession";
			}
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS sesssta ON sesssta.fk_session_agefodd=sess.rowid AND sesssta.status_in_session IN (3,4) ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sesssta.fk_stagiaire ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.fk_agefodd_session=sess.rowid ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
			$sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
			$sql .= " AND sess.status IN (5,6)";
			$sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
			if (! empty($data['idtype'])) {
				$sql .= " AND COALESCE(sesssta.fk_agefodd_stagiaire_type, 0) IN (" . $data['idtype'] . ") ";
			}

			$total_cnt = 0;
			$total_timeinsession = 0;

			dol_syslog(get_class($this) . "::" . __METHOD__ . ' ' . $data['label'], LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					if ($obj = $this->db->fetch_object($resql)) {
						$this->trainee_data[$data['label']]['nb'] = $obj->cnt;
						$this->trainee_data[$data['label']]['time'] = $obj->timeinsession;
						$total_cnt += $obj->cnt;
						$total_timeinsession += $obj->timeinsession;
					}
				}
				if (!empty($conf->global->AGF_USE_REAL_HOURS)){
				    $sql = "select count(DISTINCT assh.fk_stagiaire) as cnt , ";
				    $sql .= "SUM(assh.heures)/24 as timeinsession";
				    $sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire_heures as assh";
				    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as sess ON sess.rowid = assh.fk_session";
				    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS sesssta ON sesssta.fk_stagiaire = assh.fk_stagiaire AND sesssta.fk_session_agefodd=sess.rowid AND sesssta.status_in_session IN (3,4) ";
                    $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.rowid=assh.fk_calendrier ";
	                $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
                    $sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
				    $sql .= " AND sess.status IN (5,6)";
                    $sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
                    if (! empty($data['idtype'])) {
                        $sql .= " AND COALESCE(sesssta.fk_agefodd_stagiaire_type, 0) IN (" . $data['idtype'] . ") ";
                    }

				    dol_syslog(get_class($this) . "AGF_USE_REAL_HOURS::" . __METHOD__ . ' ' . $data['label'], LOG_DEBUG);
				    $resql2 = $this->db->query($sql);
				    if ($resql2) {
				    	$num=$this->db->num_rows($resql);
				    	if (empty($num)) {
				            $this->trainee_data[$data['label']]['nb'] = 0;
				            $this->trainee_data[$data['label']]['time'] = 0;
				        }
				        if ($this->db->num_rows($resql2)){
				            while($obj = $this->db->fetch_object($resql2)){
				                $this->trainee_data[$data['label']]['nb'] += $obj->cnt;
				                $this->trainee_data[$data['label']]['time'] += $obj->timeinsession;
				                $total_cnt += $obj->cnt;
				                $total_timeinsession += $obj->timeinsession;
				            }
				        }

				    }
				}

			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::" . __METHOD__ . " " . $data['label'] . " " . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);



			// Add time from FOAD
			$sql = "select SUM(sessstaout.hour_foad)/24 as timeinsession ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS sessstaout";
			$sql .= " WHERE sessstaout.fk_session_agefodd IN (SELECT sess.rowid FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as sesssta ON sesssta.fk_session_agefodd=sess.rowid AND sesssta.status_in_session IN (3,4) ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sesssta.fk_stagiaire ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.fk_agefodd_session=sess.rowid ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
			$sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
			$sql .= " AND sess.status IN (5,6)";
            if (! empty($data['idtype'])) {
                $sql .= " AND COALESCE(sesssta.fk_agefodd_stagiaire_type, 0) IN (" . $data['idtype'] . ") ";
            }
            $sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0)";
			$sql .= " AND COALESCE(sessstaout.hour_foad, 0) <> 0";

			dol_syslog(get_class($this) . " - FOAD ::" . __METHOD__ . ' ' . $data['label'], LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					while ( $obj = $this->db->fetch_object($resql) ) {
						if (array_key_exists($data['label'], $this->trainee_data) && !empty($obj->timeinsession)) {
							$this->trainee_data[$data['label']]['time'] += $obj->timeinsession;
							$total_timeinsession += $obj->timeinsession;
						}
                    }
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::" . __METHOD__ . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);

			if ($data['idtype'] == '1,2,7,5,4') {
				//Ajout des heures forcer
				if ($this->db->type == 'pgsql') {
					$sql = "SELECT SUM(TIME_TO_SEC(TIMEDIFF('second', statime.heuref, statime.heured))) / (24 * 60 * 60) AS timeinsession ";
				} else {
					$sql = "SELECT SUM(TIME_TO_SEC(TIMEDIFF(statime.heuref, statime.heured))) / (24 * 60 * 60) AS timeinsession ";
				}
				$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session AS sess ";
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier AS statime ON statime.fk_agefodd_session = sess.rowid ";
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
				$sql .= " WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
				$sql .= " AND sess.status IN (5,6) ";
                $sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
				$sql .= " AND sess.force_nb_stagiaire=1 ";

				dol_syslog(get_class($this) . "::" . __METHOD__ . ' ' . $data['label'], LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					if ($this->db->num_rows($resql)) {
						if ($obj = $this->db->fetch_object($resql)) {
							$this->trainee_data[$data['label']]['time'] += $obj->timeinsession;
							$total_timeinsession += $obj->timeinsession;
						}
					}
				} else {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::" . __METHOD__ . " " . $data['label'] . " " . $this->error, LOG_ERR);
					return - 1;
				}
				$this->db->free($resql);

				$sql = "SELECT SUM(sess.nb_stagiaire) AS cnt ";
				$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session AS sess ";
				$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as formation ON formation.rowid=sess.fk_formation_catalogue AND formation.fk_c_category IS NOT NULL AND fk_c_category_bpf IS NOT NULL ";
				$sql .= " WHERE sess.rowid IN (SELECT fk_agefodd_session FROM " . MAIN_DB_PREFIX . "agefodd_session_calendrier AS statime ";
				$sql .= " 		        WHERE statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "'";
				$sql .= " 		        AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "') ";
				$sql .= " AND sess.status IN (5,6) ";
                $sql .= " AND COALESCE(sess.fk_socpeople_presta, 0) = 0";
				$sql .= " AND sess.force_nb_stagiaire = 1";

				dol_syslog(get_class($this) . "::" . __METHOD__ . ' ' . $data['label'], LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					if ($this->db->num_rows($resql)) {
						if ($obj = $this->db->fetch_object($resql)) {
							$this->trainee_data[$data['label']]['nb'] += $obj->cnt;
							$total_cnt += $obj->cnt;
						}
					}
				} else {
					$this->error = "Error " . $this->db->lasterror();
					dol_syslog(get_class($this) . "::" . __METHOD__ . " " . $data['label'] . " " . $this->error, LOG_ERR);
					return - 1;
				}
				$this->db->free($resql);
			}

			if (empty($data['idtype'])) {
				$this->trainee_data[$data['label']]['nb'] = (!empty($obj->cnt)) ? ($obj->cnt - $total_cnt) : 0;
				$this->trainee_data[$data['label']]['time'] = (!empty($obj->timeinsession)) ? ($obj->timeinsession - $total_timeinsession) : 0;
			}
		}
	}

/**
 * Load all objects in memory from database
 *
 * @param array $filter output
 * @return int <0 if KO, >0 if OK
 */
function fetch_financial_c($filter = array()) {
	global $langs, $conf;

	$array_fin = array(
		array(
			'idtypesta'     => 2,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-1 Produits provenant des entreprises pour la formation de leurs salariés',
			'confcust'      => '',
			'employer'      => 0,
			'checkOPCA'     => 0,
			'checkPV'       => 0,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => 18,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-a OPCA pour des formations dispensées des contrats d’apprentissage',
			'confcust'      => 'AGF_CAT_BPF_OPCA',
			'confcustlabel' => 'AgfReportBPFCategOPCA',
			'employer'      => 0,
			'checkOPCA'     => 1,
			'checkPV'       => 0,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => 1,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-b OPCA pour des formations dispensées des contrats de professionnalisation',
			'confcust'      => 'AGF_CAT_BPF_OPCA',
			'confcustlabel' => 'AgfReportBPFCategOPCA',
			'employer'      => 0,
			'checkOPCA'     => 1,
			'checkPV'       => 0,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => 19,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-c OPCA pour des formations dispensées de la promotion ou de la reconversion par alternance',
			'confcust'      => 'AGF_CAT_BPF_OPCA',
			'confcustlabel' => 'AgfReportBPFCategOPCA',
			'employer'      => 0,
			'checkOPCA'     => 1,
			'checkPV'       => 0,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => 7,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-d OPCA pour des formations dispensées des congés individuels de formation et des projets de transition professionnelle',
			'confcust'      => 'AGF_CAT_BPF_OPCA',
			'confcustlabel' => 'AgfReportBPFCategOPCA',
			'employer'      => 0,
			'checkOPCA'     => 1,
			'checkPV'       => 0,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => 5,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-e OPCA pour des formations dispensées du compte personnel de formation',
			'confcust'      => 'AGF_CAT_BPF_OPCA',
			'confcustlabel' => 'AgfReportBPFCategOPCA',
			'employer'      => 0,
			'checkOPCA'     => 1,
			'checkPV'       => 0,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => '17,3',
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-f OPCA pour des formations dispensées pour des dispositifs spécifiques pour les personnes en recherche d\'emploi',
			'confcust'      => 'AGF_CAT_BPF_OPCA',
			'confcustlabel' => 'AgfReportBPFCategOPCA',
			'employer'      => 0,
			'checkOPCA'     => 1,
			'checkPV'       => 0,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => 8,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-g des fonds d assurance formation de non-salariés',
			'confcust'      => 'AGF_CAT_BPF_FAF',
			'confcustlabel' => 'AgfReportBPFCategFAF',
			'employer'      => 0,
			'checkOPCA'     => 1,
			'checkPV'       => 0,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => '20,4',
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-h OPCA pour des formations dispensées pour du plan de développement des compétences ou d’autres dispositifs',
			'confcust'      => 'AGF_CAT_BPF_OPCA',
			'confcustlabel' => 'AgfReportBPFCategOPCA',
			'employer'      => 0,
			'checkOPCA'     => 1,
			'checkPV'       => 0,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => 9,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-3 Pouvoirs publics pour la formation de leurs agents (Etat, collectivités territoriales, établissements publics à caractère administratif)',
			'confcust'      => 'AGF_CAT_BPF_ADMINISTRATION',
			'confcustlabel' => 'AgfReportBPFCategAdmnistration',
			'employer'      => 0,
			'checkOPCA'     => 0,
			'checkPV'       => 0,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => 10,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-4 Pouvoirs publics spécifiques Instances européennes',
			'confcust'      => '',
			'confcustlabel' => 'AgfReportBPFCategAdmnistration',
			'employer'      => 0,
			'checkOPCA'     => 0,
			'checkPV'       => 1,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => 11,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-5 Pouvoirs publics spécifiques Etat',
			'confcust'      => '',
			'confcustlabel' => 'AgfReportBPFCategAdmnistration',
			'employer'      => 0,
			'checkOPCA'     => 0,
			'checkPV'       => 1,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => 12,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-6 Pouvoirs publics spécifiques Conseils régionaux',
			'confcust'      => '',
			'confcustlabel' => 'AgfReportBPFCategAdmnistration',
			'employer'      => 0,
			'checkOPCA'     => 0,
			'checkPV'       => 1,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => 13,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-7 Pouvoirs publics spécifiques Pôle emploi',
			'confcust'      => '',
			'confcustlabel' => 'AgfReportBPFCategAdmnistration',
			'employer'      => 0,
			'checkOPCA'     => 0,
			'checkPV'       => 1,
			'datefac'       => 1,
			'checkaltfin'   => 1
		),
		array(
			'idtypesta'     => 14,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-8 Pouvoirs publics spécifiques Autres ressources publiques',
			'confcust'      => '',
			'confcustlabel' => 'AgfReportBPFCategAdmnistration',
			'employer'      => 0,
			'checkOPCA'     => 0,
			'checkPV'       => 1,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => 15,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-9 Contrats conclus avec des personnes à titre individuel et à leurs frais',
			'confcust'      => 'AGF_CAT_BPF_PARTICULIER',
			'confcustlabel' => 'AgfReportBPFCategParticulier',
			'employer'      => 0,
			'checkOPCA'     => 0,
			'checkPV'       => 0,
			'datefac'       => 1,
		),
		array(
			'idtypesta'     => 16,
			'confprod'      => 'AGF_CAT_BPF_PRODPEDA',
			'confprodlabel' => 'AgfReportBPFCategProdPeda',
			'label'         => 'C-10 Contrats conclus avec d’autres organismes de formation y compris CFA',
			'confcust'      => '',
			'employer'      => 1,
			'checkOPCA'     => 0,
			'checkPV'       => 0,
			'datefac'       => 1,
		)
	);

	$sqldebugall=array();
	foreach ( $array_fin as $key => $data ) {
		$result = $this->_getAmountFin($data, $filter,$sqldebugall);
		if ($result < 0) {
			return - 1;
		}
	}


	// C - 11
	$result = $this->_getAmountFinC11($filter,$sqldebugall);
	if ($result < 0) {
		return - 1;
	}

	// C - 13
	$result = $this->_getAmountFinC13($filter,$sqldebugall);
	if ($result < 0) {
		return - 1;
	}

	dol_syslog(get_class($this) . "::" . __METHOD__ . ' DEBUG ALL C1 to 13 '."\n".implode(' UNION ',$sqldebugall), LOG_DEBUG);

	$invoiceField = floatval(DOL_VERSION) > 9 ? 'factdddd.ref as facnumber' : 'factdddd.facnumber';

	if(floatval(DOL_VERSION) > 9) {
		$filedref = " factdddd.ref as facnumber";
	}
	else{
		$filedref = " factdddd.facnumber";
	}
	$sqldebugall_findinvoice="SELECT ".$filedref.",factdddd.total FROM " . MAIN_DB_PREFIX . "facture as factdddd WHERE
 					(factdddd.datef BETWEEN '" . $this->db->idate($filter['search_date_start']) . "' AND '" . $this->db->idate($filter['search_date_end'])."')
						AND factdddd.rowid NOT IN ( SELECT factinsssss.rowid FROM (".implode(' UNION ',$sqldebugall).") as factinsssss)";

	dol_syslog(get_class($this) . "::" . __METHOD__ . ' DEBUG find invoice not in C1 to 13 '."\n".$sqldebugall_findinvoice, LOG_DEBUG);
}

/**
 * Load all objects in memory from database
 *
 * @param array $filter output
 * @return int <0 if KO, >0 if OK
 */
function fetch_financial_d($filter = array()) {
	global $langs, $conf;

	if (empty($conf->global->AGF_CAT_PRODUCT_CHARGES) && ! in_array($langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities("AgfCategOverheadCost")), $this->warnings)) {
		$this->warnings[] = $langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities("AgfCategOverheadCost"));
		dol_syslog(get_class($this) . ":: " . end($this->warnings), LOG_WARNING);
		// return - 1;
	}

	if (empty($conf->global->AGF_CAT_BPF_PRESTA) && ! in_array($langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities("AgfReportBPFCategPresta")), $this->warnings)) {
		$this->warnings[] = $langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities("AgfReportBPFCategPresta"));
		dol_syslog(get_class($this) . ":: " . end($this->warnings), LOG_WARNING);
		// return - 1;
	}

	if (empty($conf->global->AGF_CAT_BPF_FEEPRESTA) && ! in_array($langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities("AgfReportBPFCategFeePresta")), $this->warnings)) {
		$this->warnings[] = $langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities("AgfReportBPFCategFeePresta"));
		dol_syslog(get_class($this) . ":: " . end($this->warnings), LOG_WARNING);
		// return - 1;
	}

	if (! empty($conf->global->AGF_CAT_PRODUCT_CHARGES)) {
		// Total des charges de l’organisme liées à l’activité de formation
		$sql = "SELECT SUM(facdet.total_ht) as amount ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f  ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn_det as facdet ON facdet.fk_facture_fourn=f.rowid  ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = f.fk_soc";
		$sql .= " WHERE facdet.fk_product IN (SELECT catprod.fk_product FROM " . MAIN_DB_PREFIX . "categorie_product as catprod WHERE catprod.fk_categorie IN (" . $conf->global->AGF_CAT_PRODUCT_CHARGES . "))  ";
		$sql .= " AND f.rowid IN (SELECT sesselement.fk_element FROM " . MAIN_DB_PREFIX . "agefodd_session_element as sesselement INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as sess ";
		$sql .= " ON sess.rowid=sesselement.fk_session_agefodd AND sesselement.element_type IN ('invoice_supplier_trainer','invoice_supplier_missions','invoice_supplier_room') AND sess.status IN (5,6) ";
		$sql .= " AND sess.dated BETWEEN '" . $this->db->idate($filter['search_date_start']) . "' AND '" . $this->db->idate($filter['search_date_end']) . "')";
		$sql .= " AND f.datef BETWEEN '" . $this->db->idate($filter['search_date_start']) . "' AND '" . $this->db->idate($filter['search_date_end']) . "'";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->financial_data_d['Total des charges de l’organisme liées à l’activité de formation'] = $obj->amount;
				}
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . " Total des charges de l’organisme liées à l’activité de formation " . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);
	}

	if (! empty($conf->global->AGF_CAT_BPF_FEEPRESTA) && ! empty($conf->global->AGF_CAT_BPF_PRESTA)) {
		// dont Achats de prestation de formation et honoraires de formation
		$sql = "SELECT SUM(facdet.total_ht) as amount ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f  ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn_det as facdet ON facdet.fk_facture_fourn=f.rowid  ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = f.fk_soc";
		$sql .= " WHERE facdet.fk_product IN (SELECT catprod.fk_product FROM " . MAIN_DB_PREFIX . "categorie_product as catprod WHERE catprod.fk_categorie IN (" . $conf->global->AGF_CAT_BPF_FEEPRESTA . "))  ";
		$sql .= " AND f.rowid IN (SELECT sesselement.fk_element FROM " . MAIN_DB_PREFIX . "agefodd_session_element as sesselement INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session as sess ";
		$sql .= " ON sess.rowid=sesselement.fk_session_agefodd AND sesselement.element_type IN ('invoice_supplier_trainer','invoice_supplier_missions','invoice_supplier_room') AND sess.status IN (5,6) ";
		$sql .= " AND sess.dated BETWEEN '" . $this->db->idate($filter['search_date_start']) . "' AND '" . $this->db->idate($filter['search_date_end']) . "')";
		$sql .= " AND f.datef BETWEEN '" . $this->db->idate($filter['search_date_start']) . "' AND '" . $this->db->idate($filter['search_date_end']) . "'";
		$sql .= " AND f.fk_soc IN (SELECT catfourn.fk_soc FROM " . MAIN_DB_PREFIX . "categorie_fournisseur as catfourn WHERE fk_categorie IN (" . $conf->global->AGF_CAT_BPF_PRESTA . "))";

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->financial_data_d['dont Achats de prestation de formation et honoraires de formation'] = $obj->amount;
				}
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::" . __METHOD__ . " Total des charges de l’organisme liées à l’activité de formation " . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);
	}
}

	/**
	 *
	 * @return number
	 */
	public function createDefaultCategAffectConst() {
		global $conf;

		$sql = ' INSERT INTO ' . MAIN_DB_PREFIX . 'categorie (entity,fk_parent,label,type,description,fk_soc,visible,import_key) VALUES ('.$conf->entity.',0,\'BPF\',2,\'\',NULL,1,\'agefodd\')';

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		} else {
			$parent = $this->db->last_insert_id(MAIN_DB_PREFIX . "categorie");
		}

		if (! empty($parent)) {
			$sql = ' INSERT INTO ' . MAIN_DB_PREFIX . 'categorie (entity,fk_parent,label,type,description,fk_soc,visible,import_key) VALUES ('.$conf->entity.',' . $parent . ',\'BPF - OPCA\',2,\'\',NULL,1,\'agefodd\')';
			dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			} else {
				$idcateg = $this->db->last_insert_id(MAIN_DB_PREFIX . "categorie");
				$selected_categ = array();
				if (! empty($conf->global->AGF_CAT_BPF_OPCA)) {
					$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_OPCA);
				}
				if (! in_array($idcateg, $selected_categ)) {
					$selected_categ[] = $idcateg;
				}

				$res = dolibarr_set_const($this->db, 'AGF_CAT_BPF_OPCA', implode(',', $selected_categ), 'chaine', 0, '', $conf->entity);

				if (! $res > 0) {
					$error ++;
				}
			}

			$sql = ' INSERT INTO ' . MAIN_DB_PREFIX . 'categorie (entity,fk_parent,label,type,description,fk_soc,visible,import_key) VALUES ('.$conf->entity.',' . $parent . ',\'BPF - Admnistration\',2,\'\',NULL,1,\'agefodd\')';
			dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			} else {
				$idcateg = $this->db->last_insert_id(MAIN_DB_PREFIX . "categorie");
				$selected_categ = array();
				if (! empty($conf->global->AGF_CAT_BPF_ADMINISTRATION)) {
					$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_ADMINISTRATION);
				}
				if (! in_array($idcateg, $selected_categ)) {
					$selected_categ[] = $idcateg;
				}

				$res = dolibarr_set_const($this->db, 'AGF_CAT_BPF_ADMINISTRATION', implode(',', $selected_categ), 'chaine', 0, '', $conf->entity);

				if (! $res > 0) {
					$error ++;
				}
			}

			$sql = ' INSERT INTO ' . MAIN_DB_PREFIX . 'categorie (entity,fk_parent,label,type,description,fk_soc,visible,import_key) VALUES ('.$conf->entity.',' . $parent . ',\'BPF - FAF\',2,\'\',NULL,1,\'agefodd\')';
			dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			} else {
				$idcateg = $this->db->last_insert_id(MAIN_DB_PREFIX . "categorie");
				$selected_categ = array();
				if (! empty($conf->global->AGF_CAT_BPF_FAF)) {
					$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_FAF);
				}
				if (! in_array($idcateg, $selected_categ)) {
					$selected_categ[] = $idcateg;
				}

				$res = dolibarr_set_const($this->db, 'AGF_CAT_BPF_FAF', implode(',', $selected_categ), 'chaine', 0, '', $conf->entity);

				if (! $res > 0) {
					$error ++;
				}
			}

			$sql = ' INSERT INTO ' . MAIN_DB_PREFIX . 'categorie (entity,fk_parent,label,type,description,fk_soc,visible,import_key) VALUES ('.$conf->entity.',' . $parent . ',\'BPF - Particulier\',2,\'\',NULL,1,\'agefodd\')';
			dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			} else {
				$idcateg = $this->db->last_insert_id(MAIN_DB_PREFIX . "categorie");
				$selected_categ = array();
				if (! empty($conf->global->AGF_CAT_BPF_PARTICULIER)) {
					$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_PARTICULIER);
				}
				if (! in_array($idcateg, $selected_categ)) {
					$selected_categ[] = $idcateg;
				}

				$res = dolibarr_set_const($this->db, 'AGF_CAT_BPF_PARTICULIER', implode(',', $selected_categ), 'chaine', 0, '', $conf->entity);

				if (! $res > 0) {
					$error ++;
				}
			}

			$sql = ' INSERT INTO ' . MAIN_DB_PREFIX . 'categorie (entity,fk_parent,label,type,description,fk_soc,visible,import_key) VALUES ('.$conf->entity.',' . $parent . ',\'BPF - Entreprise etrangere\',2,\'\',NULL,1,\'agefodd\')';
			dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			} else {
				$idcateg = $this->db->last_insert_id(MAIN_DB_PREFIX . "categorie");
				$selected_categ = array();
				if (! empty($conf->global->AGF_CAT_BPF_FOREIGNCOMP)) {
					$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_FOREIGNCOMP);
				}
				if (! in_array($idcateg, $selected_categ)) {
					$selected_categ[] = $idcateg;
				}

				$res = dolibarr_set_const($this->db, 'AGF_CAT_BPF_FOREIGNCOMP', implode(',', $selected_categ), 'chaine', 0, '', $conf->entity);

				if (! $res > 0) {
					$error ++;
				}
			}
		}

		$sql = ' INSERT INTO ' . MAIN_DB_PREFIX . 'categorie (entity,fk_parent,label,type,description,fk_soc,visible,import_key) VALUES ('.$conf->entity.',0,\'BPF\',1,\'\',NULL,1,\'agefodd\')';

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		} else {
			$parent = $this->db->last_insert_id(MAIN_DB_PREFIX . "categorie");
		}

		if (! empty($parent)) {
			$sql = ' INSERT INTO ' . MAIN_DB_PREFIX . 'categorie (entity,fk_parent,label,type,description,fk_soc,visible,import_key) VALUES ('.$conf->entity.',' . $parent . ',\'BPF - Prestataire\',1,\'\',NULL,1,\'agefodd\')';
			dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			} else {
				$idcateg = $this->db->last_insert_id(MAIN_DB_PREFIX . "categorie");
				$selected_categ = array();
				if (! empty($conf->global->AGF_CAT_BPF_PRESTA)) {
					$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_PRESTA);
				}
				if (! in_array($idcateg, $selected_categ)) {
					$selected_categ[] = $idcateg;
				}

				$res = dolibarr_set_const($this->db, 'AGF_CAT_BPF_PRESTA', implode(',', $selected_categ), 'chaine', 0, '', $conf->entity);

				if (! $res > 0) {
					$error ++;
				}
			}
		}

		$sql = ' INSERT INTO ' . MAIN_DB_PREFIX . 'categorie (entity,fk_parent,label,type,description,fk_soc,visible,import_key) VALUES ('.$conf->entity.',0,\'BPF\',0,\'\',NULL,1,\'agefodd\')';

		dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		} else {
			$parent = $this->db->last_insert_id(MAIN_DB_PREFIX . "categorie");
		}

		if (! empty($parent)) {
			$sql = ' INSERT INTO ' . MAIN_DB_PREFIX . 'categorie (entity,fk_parent,label,type,description,fk_soc,visible,import_key) VALUES ('.$conf->entity.',' . $parent . ',\'BPF - Produit Formation\',0,\'\',NULL,1,\'agefodd\')';
			dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			} else {
				$idcateg = $this->db->last_insert_id(MAIN_DB_PREFIX . "categorie");
				$selected_categ = array();
				if (! empty($conf->global->AGF_CAT_BPF_PRODPEDA)) {
					$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_PRODPEDA);
				}
				if (! in_array($idcateg, $selected_categ)) {
					$selected_categ[] = $idcateg;
				}

				$res = dolibarr_set_const($this->db, 'AGF_CAT_BPF_PRODPEDA', implode(',', $selected_categ), 'chaine', 0, '', $conf->entity);

				if (! $res > 0) {
					$error ++;
				}
			}

			$sql = ' INSERT INTO ' . MAIN_DB_PREFIX . 'categorie (entity,fk_parent,label,type,description,fk_soc,visible,import_key) VALUES ('.$conf->entity.',' . $parent . ',\'BPF - Outils pédagogiques\',0,\'\',NULL,1,\'agefodd\')';
			dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			} else {
				$idcateg = $this->db->last_insert_id(MAIN_DB_PREFIX . "categorie");
				$selected_categ = array();
				if (! empty($conf->global->AGF_CAT_BPF_TOOLPEDA)) {
					$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_TOOLPEDA);
				}
				if (! in_array($idcateg, $selected_categ)) {
					$selected_categ[] = $idcateg;
				}

				$res = dolibarr_set_const($this->db, 'AGF_CAT_BPF_TOOLPEDA', implode(',', $selected_categ), 'chaine', 0, '', $conf->entity);

				if (! $res > 0) {
					$error ++;
				}
			}

			$sql = ' INSERT INTO ' . MAIN_DB_PREFIX . 'categorie (entity,fk_parent,label,type,description,fk_soc,visible,import_key) VALUES ('.$conf->entity.',' . $parent . ',\'BPF - Frais Autre \',0,\'\',NULL,1,\'agefodd\')';
			dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			} else {
				$idcateg = $this->db->last_insert_id(MAIN_DB_PREFIX . "categorie");
				$selected_categ = array();
				if (! empty($conf->global->AGF_CAT_PRODUCT_CHARGES)) {
					$selected_categ = explode(',', $conf->global->AGF_CAT_PRODUCT_CHARGES);
				}
				if (! in_array($idcateg, $selected_categ)) {
					$selected_categ[] = $idcateg;
				}

				$res = dolibarr_set_const($this->db, 'AGF_CAT_PRODUCT_CHARGES', implode(',', $selected_categ), 'chaine', 0, '', $conf->entity);

				if (! $res > 0) {
					$error ++;
				}
			}

			$sql = ' INSERT INTO ' . MAIN_DB_PREFIX . 'categorie (entity,fk_parent,label,type,description,fk_soc,visible,import_key) VALUES ('.$conf->entity.',' . $parent . ',\'BPF - Frais/honoraire prestataires\',0,\'\',NULL,1,\'agefodd\')';
			dol_syslog(get_class($this) . "::" . __METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			} else {
				$idcateg = $this->db->last_insert_id(MAIN_DB_PREFIX . "categorie");
				$selected_categ = array();
				if (! empty($conf->global->AGF_CAT_BPF_FEEPRESTA)) {
					$selected_categ = explode(',', $conf->global->AGF_CAT_BPF_FEEPRESTA);
				}
				if (! in_array($idcateg, $selected_categ)) {
					$selected_categ[] = $idcateg;
				}

				$res = dolibarr_set_const($this->db, 'AGF_CAT_BPF_FEEPRESTA', implode(',', $selected_categ), 'chaine', 0, '', $conf->entity);

				if (! $res > 0) {
					$error ++;
				}
			}
		}

		if (! empty($error)) {
			return - 1;
		}
	}

	/**
	 *
	 * @param array $data
	 * @param array $filter
	 * @return number
	 */
	private function _getAmountFinC13($filter, &$sqldebugarray=array()) {
		global $conf, $langs;

		if (empty($conf->global->AGF_CAT_PRODUCT_CHARGES) && !in_array($langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities("AgfCategOverheadCost")),$this->warnings)) {
			$this->warnings[] = $langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities("AgfCategOverheadCost"));
			dol_syslog(get_class($this) . ":: " . end($this->warnings), LOG_WARNING);
			// return - 1;
		}

		if (empty($conf->global->AGF_CAT_BPF_FOREIGNCOMP) && !in_array($langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities("AgfReportBPFCategForeignComp")),$this->warnings)) {
			$this->warnings[] = $langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities("AgfReportBPFCategForeignComp"));
			dol_syslog(get_class($this) . ":: " . end($this->warnings), LOG_WARNING);
			// return - 1;
		}

		if (empty($conf->global->AGF_CAT_BPF_PRODPEDA) && !in_array($langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities("AgfReportBPFCategProdPeda")),$this->warnings)) {
			$this->warnings[] = $langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities("AgfReportBPFCategProdPeda"));
			dol_syslog(get_class($this) . ":: " . end($this->warnings), LOG_WARNING);
			// return - 1;
		}

		$this->financial_data['C-13 Autres produits au titre de la formation professionnelle continue'] = 0;

		if (! empty($conf->global->AGF_CAT_PRODUCT_CHARGES)) {


			$sqldebug = ' SELECT DISTINCT f.rowid ';
			$sql = " SELECT SUM(fd.total_ht) as amount ";
			$sqlrest = "
				FROM
				    " . MAIN_DB_PREFIX . "facturedet AS fd
				        INNER JOIN
				    " . MAIN_DB_PREFIX . "facture AS f ON f.rowid = fd.fk_facture
				WHERE
				    f.fk_statut IN (1 , 2)
					AND f.datef BETWEEN '" . $this->db->idate($filter['search_date_start']) . "' AND '" . $this->db->idate($filter['search_date_end']) . "'
			 			AND fd.fk_product IN (SELECT
				            cp.fk_product
				        FROM
				            " . MAIN_DB_PREFIX . "categorie_product AS cp
				        WHERE
				            cp.fk_categorie IN (" . $conf->global->AGF_CAT_PRODUCT_CHARGES . "))
					AND f.rowid IN (SELECT DISTINCT
				            factin.rowid
				        FROM
				            " . MAIN_DB_PREFIX . "agefodd_session_element AS se
				                INNER JOIN
				            " . MAIN_DB_PREFIX . "agefodd_session AS sess ON sess.rowid = se.fk_session_agefodd
				                AND se.element_type = 'invoice'
				                AND sess.dated BETWEEN '" . $this->db->idate($filter['search_date_start']) . "' AND '" . $this->db->idate($filter['search_date_end']) . "'
								AND sess.status IN (5,6)
								INNER JOIN
							" . MAIN_DB_PREFIX . "facture AS factin ON factin.rowid=se.fk_element)";

			$sql = $sql.$sqlrest;

			$sqldebugarray[]='('.$sqldebug.$sqlrest.')';

			dol_syslog(get_class($this) . "::" . __METHOD__ . ' C-13', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					if ($obj = $this->db->fetch_object($resql)) {
						$this->financial_data['C-13 Autres produits au titre de la formation professionnelle continue'] += $obj->amount;
					}
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::" . __METHOD__ . ' C-13 Autres produits au titre de la formation professionnelle continue' . " " . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);
		}

		if (! empty($conf->global->AGF_CAT_BPF_PRODPEDA) && ! empty($conf->global->AGF_CAT_BPF_FOREIGNCOMP)) {

			$sqldebug = ' SELECT DISTINCT f.rowid ';
			$sql = " SELECT SUM(fd.total_ht) as amount ";
			$sqlrest = "
			FROM
			    " . MAIN_DB_PREFIX . "facturedet AS fd
			        INNER JOIN
			    " . MAIN_DB_PREFIX . "facture AS f ON f.rowid = fd.fk_facture
			WHERE
			    f.fk_statut IN (1 , 2)
				AND f.datef BETWEEN '" . $this->db->idate($filter['search_date_start']) . "' AND '" . $this->db->idate($filter['search_date_end']) . "'
		 			AND fd.fk_product IN (SELECT
			            cp.fk_product
			        FROM
			            " . MAIN_DB_PREFIX . "categorie_product AS cp
			        WHERE
			            cp.fk_categorie IN (" . $conf->global->AGF_CAT_BPF_PRODPEDA . "))
				AND f.fk_soc IN (SELECT
			            cs.fk_soc
			        FROM
			            " . MAIN_DB_PREFIX . "categorie_societe AS cs
			        WHERE
			            cs.fk_categorie IN (" . $conf->global->AGF_CAT_BPF_FOREIGNCOMP . "))
				AND f.rowid IN (SELECT DISTINCT
			            factin.rowid
			        FROM
			            " . MAIN_DB_PREFIX . "agefodd_session_element AS se
			                INNER JOIN
			            " . MAIN_DB_PREFIX . "agefodd_session AS sess ON sess.rowid = se.fk_session_agefodd
			                AND se.element_type = 'invoice'
			                AND sess.dated BETWEEN '" . $this->db->idate($filter['search_date_start']) . "' AND '" . $this->db->idate($filter['search_date_end']) . "'
							AND sess.status IN (5,6)
							INNER JOIN
						" . MAIN_DB_PREFIX . "facture AS factin ON factin.rowid=se.fk_element
							INNER JOIN
						" . MAIN_DB_PREFIX . "agefodd_place as pl ON pl.rowid=sess.fk_session_place
							AND pl.fk_pays<>1)";

			$sql = $sql.$sqlrest;

			$sqldebugarray[]='('.$sqldebug.$sqlrest.')';
			dol_syslog(get_class($this) . "::" . __METHOD__ . 'C-13', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					if ($obj = $this->db->fetch_object($resql)) {
						$this->financial_data['C-13 Autres produits au titre de la formation professionnelle continue'] += $obj->amount;
					}
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::" . __METHOD__ . ' C-13 Autres produits au titre de la formation professionnelle continue' . " " . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);
		}

		if (empty($this->financial_data['C-13 Autres produits au titre de la formation professionnelle continue'])) {
			unset($this->financial_data['C-13 Autres produits au titre de la formation professionnelle continue']);
		}

		return 1;
	}

	/**
	 *
	 * @param array $filter
	 * @return number
	 */
	private function _getAmountFinC11($filter, &$sqldebugarray=array()) {
		global $conf, $langs;

		if (empty($conf->global->AGF_CAT_BPF_TOOLPEDA)) {
			$this->warnings[] = $langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities("AgfReportBPFCategToolPeda"));
			dol_syslog(get_class($this) . ":: " . end($this->warnings), LOG_WARNING);
			// return - 1;
		}

		if (! empty($conf->global->AGF_CAT_BPF_TOOLPEDA)) {

			$sqldebug = ' SELECT DISTINCT f.rowid ';
			$sql = " SELECT SUM(fd.total_ht) as amount ";
			$sqlrest = "
			FROM
			    " . MAIN_DB_PREFIX . "facturedet AS fd
			        INNER JOIN
			    " . MAIN_DB_PREFIX . "facture AS f ON f.rowid = fd.fk_facture
			WHERE
			    f.fk_statut IN (1 , 2)
				AND f.datef BETWEEN '" . $this->db->idate($filter['search_date_start']) . "' AND '" . $this->db->idate($filter['search_date_end']) . "'
		 			AND fd.fk_product IN (SELECT
			            cp.fk_product
			        FROM
			            " . MAIN_DB_PREFIX . "categorie_product AS cp
			        WHERE
			            cp.fk_categorie IN (" . $conf->global->AGF_CAT_BPF_TOOLPEDA . "))";

			$sql = $sql.$sqlrest;

			$sqldebugarray[]='('.$sqldebug.$sqlrest.')';
			dol_syslog(get_class($this) . "::" . __METHOD__ . 'C-12', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					if ($obj = $this->db->fetch_object($resql)) {
						$this->financial_data['C-12 Produits résultant de la vente d’outils pédagogiques'] = $obj->amount;
					}
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::" . __METHOD__ . ' C-12 Produits résultant de la vente d’outils pédagogiques' . " " . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);
		}

		return 1;
	}

	/**
	 *
	 * @param array $data
	 * @param array $filter
	 * @return number$
	 */
	private function _getAmountFin($data = array(), $filter, &$sqldebugarray=array()) {
		global $conf, $langs;

		if (! empty($data['confprod']) && empty($conf->global->{$data['confprod']}) && !in_array($langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities($data['confprodlabel'])),$this->warnings)) {
			$this->warnings[] = $langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities($data['confprodlabel']));
			dol_syslog(get_class($this) . ":: " . end($this->warnings), LOG_ERR);
			// return - 1;
		}

		if (! empty($data['confcust']) && empty($conf->global->{$data['confcust']}) && !in_array($langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities($data['confcustlabel'])),$this->warnings)) {
			$this->warnings[] = $langs->transnoentities('AgfErroVarNotSetBPF', $langs->transnoentities($data['confcustlabel']));
			dol_syslog(get_class($this) . ":: " . end($this->warnings), LOG_ERR);
			// return - 1;
		}

		if (! empty($data['confprod']) && !empty($conf->global->{$data['confprod']}) || (! empty($data['confcust']) && !empty($conf->global->{$data['confcust']}))) {

			$sqldebug = ' SELECT DISTINCT f.rowid ';
			$sql = " SELECT SUM(fd.total_ht) as amount ";
			$sqlrest =  " FROM
			    " . MAIN_DB_PREFIX . "facturedet AS fd
			        INNER JOIN
			    " . MAIN_DB_PREFIX . "facture AS f ON f.rowid = fd.fk_facture ";
			if (!empty($data['employer'])) {
				$sqlrest .= " AND (f.datef BETWEEN '" . $this->db->idate($filter['search_date_start']) . "' AND '" . $this->db->idate($filter['search_date_end'])."')";
			}
			if (!empty($data['datefac'])) {
				$sqlrest .= " AND (f.datef BETWEEN '" . $this->db->idate($filter['search_date_start']) . "' AND '" . $this->db->idate($filter['search_date_end'])."')";
			}
			$sqlrest .= " WHERE
			    f.fk_statut IN (1 , 2) ";
			if (! empty($data['confprod']) && !empty($conf->global->{$data['confprod']})) {
				$sqlrest .= " AND fd.fk_product IN (SELECT
			            cp.fk_product
			        FROM
			            " . MAIN_DB_PREFIX . "categorie_product AS cp
			        WHERE
			            cp.fk_categorie IN (" . $conf->global->{$data['confprod']} . "))";
			}

			if (! empty($data['confcust']) && !empty($conf->global->{$data['confcust']})) {
				$sqlrest .= " AND f.fk_soc IN (SELECT
			            cs.fk_soc
			        FROM
			            " . MAIN_DB_PREFIX . "categorie_societe AS cs
			        WHERE
			            cs.fk_categorie IN (" . $conf->global->{$data['confcust']} . "))";
			}

			$sqlrest .= " AND ( (f.rowid IN (SELECT DISTINCT
			            factin.rowid
			        FROM
			            " . MAIN_DB_PREFIX . "agefodd_session_element AS se
			                INNER JOIN
			            " . MAIN_DB_PREFIX . "agefodd_session AS sess ON sess.rowid = se.fk_session_agefodd
			                AND se.element_type = 'invoice'
							AND sess.status IN (5,6)
							INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.fk_agefodd_session=sess.rowid
							AND statime.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statime.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";

			if (! empty($data['employer'])) {
				$sqlrest .= " AND sess.fk_soc_employer IS NOT NULL ";
			}
			$sqlrest .= " INNER JOIN
			            " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS ss ON ss.fk_session_agefodd = sess.rowid
			                AND ss.fk_agefodd_stagiaire_type IN (" . $data['idtypesta'] . ")";
			if (empty($data['checkOPCA']) && empty($data['employer'])) {
				$sqlrest .= " INNER JOIN
			            " . MAIN_DB_PREFIX . "agefodd_stagiaire AS sta ON sta.rowid = ss.fk_stagiaire";
				$sqlrest .= " INNER JOIN
			            " . MAIN_DB_PREFIX . "facture AS factin ON ";
			      if (empty($data['checkPV'])) {
			      	$sqlrest .= " factin.fk_soc = sta.fk_soc AND ";
			      }
			      if (array_key_exists('checkaltfin',$data) && !empty($data['checkaltfin'])) {
					$sqlrest .= " factin.fk_soc = ss.fk_soc_link AND ";
			      }
			      $sqlrest .= " factin.rowid=se.fk_element))";
			} elseif (!empty($data['checkOPCA'])) {
				$sqlrest .= " INNER JOIN
			            " . MAIN_DB_PREFIX . "agefodd_opca AS opca ON opca.fk_session_trainee = ss.rowid AND opca.fk_session_agefodd=sess.rowid
			                INNER JOIN
			            " . MAIN_DB_PREFIX . "facture AS factin ON factin.fk_soc = opca.fk_soc_OPCA AND factin.rowid=se.fk_element))";
			} elseif (!empty($data['employer'])) {
				$sqlrest .= " INNER JOIN
			            " . MAIN_DB_PREFIX . "facture AS factin ON factin.fk_soc = sess.fk_soc_employer AND factin.rowid=se.fk_element))";
			}
			if (! empty($data['checkOPCA'])) {
				$sqlrest .= " OR (f.rowid IN (SELECT DISTINCT
			            factinopca.rowid
			        FROM
			            " . MAIN_DB_PREFIX . "agefodd_session_element AS seopca
			                INNER JOIN
			            " . MAIN_DB_PREFIX . "agefodd_session AS sessopca ON sessopca.rowid = seopca.fk_session_agefodd
			                AND seopca.element_type = 'invoice'
							AND sessopca.status IN (5,6)
							INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statimeopca ON statimeopca.fk_agefodd_session=sessopca.rowid
							AND statimeopca.heured >= '" . $this->db->idate($filter['search_date_start']) . "' AND statimeopca.heuref <= '" . $this->db->idate($filter['search_date_end']) . "'";
				if (! empty($data['employer'])) {
					$sqlrest .= "  AND sessopca.fk_soc_employer IS NOT NULL ";
				}
				$sqlrest .= " 	INNER JOIN
			            " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS ssopca ON ssopca.fk_session_agefodd = sessopca.rowid
			                AND ssopca.fk_agefodd_stagiaire_type IN (" . $data['idtypesta'] . ")
 							INNER JOIN
			            " . MAIN_DB_PREFIX . "facture AS factinopca ON factinopca.fk_soc = sessopca.fk_soc_OPCA AND factinopca.rowid=seopca.fk_element))";
			}
			$sqlrest .= ")";

			$sql = $sql.$sqlrest;

			$sqldebugarray[]='('.$sqldebug.$sqlrest.')';
			dol_syslog(get_class($this) . "::" . __METHOD__ . ' ' . $data['label'], LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					if ($obj = $this->db->fetch_object($resql)) {
						$this->financial_data[$data['label']] = $obj->amount;
					}
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::" . __METHOD__ . ' ' . $data['label'] . " " . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);
		}

		return 1;
	}
}

