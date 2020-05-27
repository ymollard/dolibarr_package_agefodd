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
class ReportTime extends AgefoddExportExcel {

	public $TType_report = array(
		'time_sta' => 'AgfReportTimeSta',
		'time_sta_detail' => 'AgfReportTimeStaDetail',
		'time_trainer' => 'AgfReportTimeTrainer'
	);
	public $type_report;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	public function __construct($db, $outputlangs) {
		$outputlangs->load('agefodd@agefodd');
		$outputlangs->load("exports");
		$outputlangs->load("main");

		$sheet_array = array (
				0 => array (
						'name' => 'reporttime',
						'title' => $outputlangs->transnoentities('AgfMenuReportTime')
				)
		);

		foreach($this->TType_report as $key=>$data) {
			$this->TType_report[$key]=$outputlangs->transnoentities($data);
		}

		$array_column_header['time_sta'] = array(
			1  => array(
				'type'  => 'int',
				'title' => $outputlangs->transnoentities('Year')
			),
			2  => array(
				'type'  => 'int',
				'title' => $outputlangs->transnoentities('Month')
			),
			3  => array(
				'type'  => 'number',
				'title' => $outputlangs->transnoentities('AgfReportTimeDoneReal')
			),
			4 => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfTrainingCateg')
			),
			5 => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfTrainingCategBPF')
			),
		);

		$array_column_header['time_sta_detail'] = array(
			1  => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('SessionRef')
			),
			2 => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfFormIntituleCust')
			),
			3 => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfTrainingRef')
			),
			4  => array(
				'type'  => 'date',
				'title' => $outputlangs->transnoentities('AgfDateDebut')
			),
			5  => array(
				'type'  => 'date',
				'title' => $outputlangs->transnoentities('AgfDateFin')
			),
			6  => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfParticipant')
			),
			7  => array(
				'type'  => 'int',
				'title' => $outputlangs->transnoentities('AgfDuree')
			),
			8  => array(
				'type'  => 'int',
				'title' => $outputlangs->transnoentities('AgfReportTimeDoneReal')
			),
		);

		$array_column_header['time_trainer'] = array(
			1  => array(
				'type'  => 'int',
				'title' => $outputlangs->transnoentities('Year')
			),
			2  => array(
				'type'  => 'int',
				'title' => $outputlangs->transnoentities('Month')
			),
			3  => array(
				'type'  => 'int',
				'title' => $outputlangs->transnoentities('AgfReportTimeDoneReal')
			),
			4  => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfFormateur')
			),
			5 => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfTrainingCateg')
			),
			6 => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfTrainingCategBPF')
			),
		);

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

					$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[0], $this->outputlangs->transnoentities("Type"));
					$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[0], $this->outputlangs->transnoentities($this->TType_report[$this->type_report]));
					$this->row[0]++;

					foreach ( $filter as $key => $value ) {
						if ($key=='secal.date_session') {
							$str_cirteria = $this->outputlangs->transnoentities('AgfReportTimeCalTime') . ' ';
							if (array_key_exists('start', $value)) {
								$str_criteria_value = $this->outputlangs->transnoentities("AgfDateDebut"). ':' . dol_print_date($value['start'],'daytext', 'tzserver', $this->outputlangs);
								$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[0], $str_cirteria);
								$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[0], $str_criteria_value);
								$this->row[0]++;
							}
							if (array_key_exists('end', $value)) {
								$str_criteria_value = $this->outputlangs->transnoentities("AgfDateFin") . ':' . dol_print_date($value['end'],'daytext', 'tzserver', $this->outputlangs);
								$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[0], $str_cirteria);
								$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[0], $str_criteria_value);
								$this->row[0]++;
							}
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
							$session_status =array();
							$sql = "SELECT t.rowid, t.code ,t.intitule ";
							$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_status_type as t";
							$sql .= ' WHERE t.rowid IN (' . implode(',', $value) . ')';

							dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
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
				if ($key == 'secal.date_session') {
					$str_sub_name .= $this->outputlangs->transnoentities('AgfSessionDetail');
					if (array_key_exists('start', $value)) {
						$str_sub_name .= $this->outputlangs->transnoentities("Start") . dol_print_date($value['start'], 'dayrfc', 'tzserver', $this->outputlangs);
					}
					if (array_key_exists('end', $value)) {
						$str_sub_name .= $this->outputlangs->transnoentities("End") . dol_print_date($value['end'], 'dayrfc', 'tzserver', $this->outputlangs);
					}
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
				}
			}
		}

		$str_sub_name = str_replace(' ', '', $str_sub_name);
		$str_sub_name = str_replace('.', '', $str_sub_name);
		$str_type_report = str_replace('time_', '', $this->type_report);
		$str_sub_name = dol_sanitizeFileName($str_type_report.'_'.$str_sub_name);
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
		require_once 'agsession.class.php';
		require_once 'agefodd_session_stagiaire.class.php';
		require_once 'agefodd_stagiaire.class.php';
		require_once 'agefodd_session_calendrier.class.php';

		$this->outputlangs->load('agefodd@agefodd');
		$this->outputlangs->load("exports");
		$this->outputlangs->load("main");

		$this->title = $this->outputlangs->transnoentities('AgfMenuReportTime');
		$this->subject = $this->outputlangs->transnoentities('AgfMenuReportTime');
		$this->description = $this->outputlangs->transnoentities('AgfMenuReportTime');
		$this->keywords = $this->outputlangs->transnoentities('AgfMenuReportTime');

		$result = $this->open_file($this->file);
		if ($result < 0) {
			return $result;
		}

		if (empty($this->type_report)) {
			$this->type_report=key($this->TType_report);
		}
		$this->setArrayColumnHeader(array(0=>$this->array_column_header[$this->type_report]));

		$result = $this->write_header();
		if ($result < 0) {
			return $result;
		}

		// General
		$count = $this->fetch_data_and_write($filter);
		if ($count < 0) {
			return $count;
		}

		$this->row[0]++;
		$result = $this->write_filter($filter);
		if ($result < 0) {
			return $result;
		}

		$this->close_file();
		return $count;
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_data_and_write($filter = array()) {
		global $langs, $conf;

		dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
		dol_include_once('/agefodd/class/agefodd_session_formateur_calendrier.class.php');
		dol_include_once('/agefodd/class/agefodd_formateur.class.php');
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

		$sql = 'SELECT ';
		if ($this->type_report=='time_sta') {
			$sql .=' YEAR(secal.date_session) as yeardt,
			MONTH(secal.date_session) as monthdt,
			SUM(sth.heures) as sumhour,
			cat.intitule as catlabel,
			catbpf.intitule as catbpflabel';
		}
		if ($this->type_report=='time_sta_detail') {
			$sql .=' s.ref as sessref,
			s.intitule_custo,
			f.ref as formref,
			s.dated,
			s.datef,
			sth.fk_stagiaire as sthfk_stagiaire,
			s.duree_session,
			SUM(sth.heures) as sumhour';
		}
		if ($this->type_report=='time_trainer') {
			$sql .=' YEAR(secal.date_session) as yeardt,
			MONTH(secal.date_session) as monthdt,';
			if ($this->db->type == 'pgsql') {
				$sql .='SUM(TIME_TO_SEC(TIMEDIFF(\'second\',trainercal.heuref, trainercal.heured))/(3600)) as sumhr,' ;
			} else {
				$sql .='SUM(TIME_TO_SEC(TIMEDIFF(trainercal.heuref, trainercal.heured))/(3600)) as sumhr,';
			}
			$sql .='trainer.rowid as trainerowid,
			cat.intitule as catlabel,
			catbpf.intitule as catbpflabel';
		}
		$sql .='
		FROM '.MAIN_DB_PREFIX.'agefodd_session as s
		INNER JOIN '.MAIN_DB_PREFIX.'agefodd_session_stagiaire as sesssta ON sesssta.fk_session_agefodd=s.rowid
		INNER JOIN '.MAIN_DB_PREFIX.'agefodd_session_calendrier as secal ON secal.fk_agefodd_session=s.rowid AND secal.date_session';

		if (strpos($this->type_report,'time_sta')!==false) {
			//for time trainneee
			$sql .=' INNER JOIN '.MAIN_DB_PREFIX.'agefodd_session_stagiaire_heures as sth ON sth.fk_stagiaire=sesssta.fk_stagiaire AND sth.fk_calendrier=secal.rowid';
		} elseif (strpos($this->type_report,'time_trainer')!==false) {
			//For time trainer
			$sql .=' INNER JOIN '.MAIN_DB_PREFIX.'agefodd_session_formateur as sestrainer on sestrainer.fk_session = s.rowid
					INNER JOIN '.MAIN_DB_PREFIX.'agefodd_formateur as trainer ON trainer.rowid=sestrainer.fk_agefodd_formateur
					INNER JOIN '.MAIN_DB_PREFIX.'agefodd_session_formateur_calendrier as trainercal on trainercal.fk_agefodd_session_formateur = sestrainer.rowid
					  AND trainercal.status in ('.Agefoddsessionformateurcalendrier::STATUS_CONFIRMED.','.Agefoddsessionformateurcalendrier::STATUS_FINISH.')';
		}

		$sql .=' INNER JOIN '.MAIN_DB_PREFIX.'agefodd_formation_catalogue f on f.rowid=s.fk_formation_catalogue
		LEFT JOIN '.MAIN_DB_PREFIX.'agefodd_formation_catalogue_type as cat ON cat.rowid=f.fk_c_category
		LEFT JOIN '.MAIN_DB_PREFIX.'agefodd_formation_catalogue_type_bpf as catbpf ON catbpf.rowid=f.fk_c_category_bpf';

		if (count($filter)>0) {
			$filtersql=array();
			foreach($filter as $key=>$value) {
				if ($key == 's.type_session') {
					$filtersql[]= $key . ' = ' . $this->db->escape($value);
				} elseif ($key == 's.status') {
					if (is_array($value) && count($value) > 0) {
						$filtersql[]= $key . ' IN (' . implode(',', $value) . ")";
					}
				} elseif ($key == 'secal.date_session') {
					if (array_key_exists('start', $value))
					{
						$filtersql[]= $key . '>=\'' . $this->db->idate($value['start']) . "'";
					}
					if (array_key_exists('end', $value))
					{
						$filtersql[]= $key . '<=\'' . $this->db->idate($value['end']) . "'";
					}
				}
			}
			if (count($filtersql)>0) {
				$sql .= ' WHERE '.implode(' AND ',$filtersql);
			}
		}
		if ($this->type_report=='time_sta') {
			$sql .= ' GROUP BY YEAR(secal.date_session),MONTH(secal.date_session),cat.intitule,catbpf.intitule
			ORDER BY YEAR(secal.date_session),MONTH(secal.date_session),cat.intitule,catbpf.intitule';
		}
		if ($this->type_report=='time_sta_detail') {
			$sql .= ' GROUP BY s.ref,
			s.intitule_custo,
			f.ref,
			s.dated,
			s.datef,
			sth.fk_stagiaire,
			s.duree_session
			ORDER BY  s.ref,
			s.intitule_custo,
			f.ref,
			s.dated,
			s.datef,
			sth.fk_stagiaire,
			s.duree_session';
		}
		if ($this->type_report=='time_trainer') {
			$sql .=' GROUP BY YEAR(secal.date_session) ,
			MONTH(secal.date_session) ,
			trainer.rowid,
			cat.intitule,
			catbpf.intitule
			ORDER BY YEAR(secal.date_session) ,
			MONTH(secal.date_session) ,
			trainer.rowid,
			cat.intitule,
			catbpf.intitule ';
		}

		dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				while ($obj = $this->db->fetch_object($resql)) {
					$i=1;
					$line_to_output=array();
					foreach($obj as $key=>$val) {

						if ($this->type_report=='time_sta_detail' && $key=='sthfk_stagiaire') {
							$agfsta= new Agefodd_stagiaire($this->db);
							$result=$agfsta->fetch($val);
							if ($result<0) {
								$this->error = $agfsta->error;
								return $result;
							} else {
								$contact_static = new Contact($this->db);
								$contact_static->civility_id = $agfsta->civilite;
								$contact_static->civility_code = $agfsta->civilite;
								$line_to_output[$i] = $contact_static->getCivilityLabel(). ' '. $agfsta->nom.' '.$agfsta->prenom;
							}
						} elseif($this->type_report=='time_trainer' && $key=='trainerowid') {
							$agftrainer= new Agefodd_teacher($this->db);
							$result=$agftrainer->fetch($val);
							if ($result<0) {
								$this->error = $agftrainer->error;
								return $result;
							} else {
								$contact_static = new Contact($this->db);
								$contact_static->civility_id = $agftrainer->civilite;
								$contact_static->civility_code = $agftrainer->civilite;
								$line_to_output[$i] = $contact_static->getCivilityLabel() . ' ' . $agftrainer->name . ' ' . $agftrainer->firstname;
							}
						} else {
							$line_to_output[$i] = $val;
						}
						$i++;
					}
					$result=$this->write_line($line_to_output, 0);
					if ($result < 0) {
						return $result;
					}
				}
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::".__METHOD__.' ERROR ' . $this->error, LOG_ERR);
			return - 1;
		}
	}
}

