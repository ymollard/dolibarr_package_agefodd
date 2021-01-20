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
 * \file		/agefodd/class/report_by_customer.php
 * \ingroup agefodd
 * \brief File of class to generate report for agefodd
 */
require_once ('agefodd_export_excel.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
dol_include_once('/societe/class/societe.class.php');
dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');

/**
 * Class to build report by customer
 */
class ReportCalendarByCustomer extends AgefoddExportExcel {
	private $lines;

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

		$sheet_array[0] = array (
		    0 => array (
		        'name' => 'send',
		        'title' => $outputlangs->transnoentities('AgfMenuReportCalendarByCustomer')
		    )
		);

		$array_column_header[0] = array (
				1 => array (
						'type' => 'text',
						'header' => $outputlangs->transnoentities('SessionRef')
				),
				2 => array (
						'type' => 'text',
						'header' => $outputlangs->transnoentities('AgfMenuActStagiaire')
				),
				3 => array (
						'type' => 'text',
						'header' => $outputlangs->transnoentities('Type')
				)

		);

		return parent::__construct($db, $array_column_header, $outputlangs);
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
				if ($key == 'so.rowid') {
				    $soc = new Societe($this->db);
				    $soc->fetch($value);

					$str_sub_name .= $this->outputlangs->transnoentities('Company') . $soc->name;
				} elseif ($key == 'sesscal.date_session') {
					$str_sub_name .= $this->outputlangs->transnoentities('AgfSessionDetail');
					if (array_key_exists('start', $value)) {
						$str_sub_name .= $this->outputlangs->transnoentities("Start") . dol_print_date($value['start'], 'dayrfc', 'tzserver', $this->outputlangs);
					}
					if (array_key_exists('end', $value)) {
						$str_sub_name .= $this->outputlangs->transnoentities("End") . dol_print_date($value['end'], 'dayrfc', 'tzserver', $this->outputlangs);
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

		global $conf;

		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
		require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
		require_once 'agefodd_convention.class.php';
		require_once 'agsession.class.php';
		require_once 'agefodd_session_stagiaire.class.php';
		require_once 'agefodd_stagiaire.class.php';
		require_once 'agefodd_session_calendrier.class.php';
		require_once 'agefodd_session_formateur.class.php';
		require_once 'agefodd_session_element.class.php';
		require_once 'agefodd_opca.class.php';

		$this->outputlangs->load('agefodd@agefodd');
		$this->outputlangs->load('bills');
		$this->outputlangs->load("exports");
		$this->outputlangs->load("main");
		$this->outputlangs->load("commercial");
		$this->outputlangs->load("companies");

		$this->title = $this->outputlangs->transnoentities('AgfMenuReportByCustomer');
		$this->subject = $this->outputlangs->transnoentities('AgfMenuReportByCustomer');
		$this->description = $this->outputlangs->transnoentities('AgfMenuReportByCustomer');
		$this->keywords = $this->outputlangs->transnoentities('AgfMenuReportByCustomer');

		$result = $this->open_file($this->file);
		if ($result < 0) {
			return $result;
		}

		// modifier le header en fonction du nombre de mois de la tranche
		$this->complete_header($filter);

		$this->row[0]++;
		$result = $this->write_header();
		if ($result < 0) {
			return $result;
		}

		$this->apply_header_style();

		// Start find data
		$result = $this->fetch_all_session($filter);
		if ($result < 0) {
			return $result;
		}

		$array_sub_total = array ();

		$total_line = count($this->lines);
		if (count($this->lines) > 0) {
			foreach ( $this->lines as $line ) {
			    if (!empty($refsession) && $refsession != $line->refsession)
			    {
			        $result = $this->write_line_total($array_sub_trainee);
			        $result = $this->write_line_total($array_sub_total, '3d85c6');
			        if ($result < 0) {
			            return $result;
			        }

			        $array_sub_total = array ();
			    }

			    // Must have same struct than $array_column_header
			    $line_to_output = array ();

			    // Manage display
			    $displayref = false;
			    $displaytraineename = false;

			    if ($refsession != $line->refsession) $displayref = true;
			    if ($lasttrainee != $line->stagiaire)
			    {
			        if (!empty($lasttrainee)) $result = $this->write_line_total($array_sub_trainee);
			        $displaytraineename = true;
			        $lasttrainee = $line->stagiaire;

			        $total_reste = $line->total_reste;

			        $array_sub_trainee = array();
			    }

			    // Use to break on session reference
			    $refsession = $line->refsession;

			    // ref session
			    if ($displayref) $line_to_output[1] = $line->refsession;
			    else $line_to_output[1] = '';
			    $array_sub_total[1] = $line->refsession;

			    // nom du stagiaire
			    if ($displaytraineename){
			        $line_to_output[2] = $line->stagiaire;
			        $array_sub_trainee[2] = $line->stagiaire;
			    }
			    else $line_to_output[2] = '';

                // modalité pédagogique
			    $line_to_output[3] = $line->modalite;

			    $i = 4;
			    // colonnes des mois
			    foreach ($line->months as $m => $type)
			    {

			        $line_to_output[$i] = $type['presence'];
			        $array_sub_trainee[$i] += $type['presence'];
			        $array_sub_total[$i] += $type['presence'];
			        $i++;

			        $line_to_output[$i] = $type['missing'];
			        $array_sub_trainee[$i] += $type['missing'];
			        $array_sub_total[$i] += $type['missing'];
			        $i++;

			        $line_to_output[$i] = $type['canceled'];
			        $array_sub_trainee[$i] += $type['canceled'];
			        $array_sub_total[$i] += $type['canceled'];
			        $i++;

			    }

			    // total
			    $line_to_output[$i] = $line->total_heures;
			    $array_sub_trainee[$i] += $line->total_heures;
			    $array_sub_total[$i]+= $line->total_heures;
			    $i++;

			    // total restant
			    $total_reste -= $line->total_heures;
			    $line_to_output[$i] = $total_reste;
			    $array_sub_trainee[$i] = $total_reste;
			    $i++;

				$this->write_line($line_to_output, 0);

			}

			$result = $this->write_line_total($array_sub_trainee);
			$result = $this->write_line_total($array_sub_total, '3d85c6');
			if ($result < 0) {
				return $result;
			}

		}

		$this->row[0]++;
		if (array_key_exists('so.rowid', $filter))
		{
		    $soc = new Societe($this->db);
		    $soc->fetch($filter['so.rowid']);

		    $filter['so.nom'] = $soc->name;
		}
		$result = $this->write_filter($filter);
		if ($result < 0) {
			return $result;
		}

// 		exit;
		if ($total_line>0) {
			$this->close_file();
		}

		return $total_line;
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_all_session($filter = array()) {
	    global $langs, $conf, $Tmonths;

		$sql = 'SELECT DISTINCT s.rowid, s.ref, s.duree_session, sesscal.rowid as cal_id, sesscal.calendrier_type, sesscal.date_session';
		$sql.= ' FROM llx_agefodd_session AS s';
		$sql.= ' LEFT JOIN llx_agefodd_session_calendrier AS sesscal ON sesscal.fk_agefodd_session = s.rowid';
		$sql.= ' INNER JOIN llx_agefodd_session_stagiaire AS ss ON s.rowid = ss.fk_session_agefodd';

		if ($filter['so.rowid']) $sql .= ' INNER JOIN llx_agefodd_stagiaire AS sta ON ss.fk_stagiaire = sta.rowid AND ss.fk_soc = '.$this->db->escape($filter['so.rowid']);

		$sql .= ' WHERE s.entity IN (' . getEntity('agefodd') . ')';

		// Manage filter
		if (count($filter) > 0) {
		    foreach ( $filter as $key => $value )
		    {
		        if ($key == 'sesscal.date_session')
		        {
		            if (array_key_exists('start', $value))
		            {
		                $sql .= ' AND ' . $key . '>=\'' . $this->db->idate($value['start']) . "'";
		            }
		            if (array_key_exists('end', $value))
		            {
		                $sql .= ' AND ' . $key . '<=\'' . $this->db->idate($value['end']) . "'";
		            }
		        }
		    }
		}

		$sql.= ' ORDER BY s.ref, sesscal.date_session, sesscal.calendrier_type DESC';

		dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array ();

			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {

			    $TSessions = array();

			    while ( $i < $num)
			    {
			        $obj = $this->db->fetch_object($resql);

			        $calendar = new Agefodd_sesscalendar($this->db);
			        $calendar->fetch($obj->cal_id);
			        $calendar->duration = floatval(($calendar->heuref - $calendar->heured) / 3600);

			        $stagiaire = new Agefodd_session_stagiaire($this->db);
			        $stagiaire->fetch_stagiaire_per_session($obj->rowid, $filter['so.rowid']);
			        //var_dump($stagiaire); exit;

			        // pour chaque créneaux du calendrier de session
			        // pour chaque modalité pédagogique, on calcule le nb d'heures de présence/absence/annulation de chaque stagiaire
			        foreach ($stagiaire->lines as $trainee)
			        {
			            $mode = $calendar->calendrier_type_label;
			            if(empty($mode)) $mode = 'Non définie';

			            if (!array_key_exists($obj->ref, $TSessions)) $TSessions[$obj->ref] = array();

		                $TSessions[$obj->ref][$trainee->stagerowid]['name'] = $trainee->nom . ' ' . $trainee->prenom;
		                $TSessions[$obj->ref][$trainee->stagerowid]['duree_session'] = $obj->duree_session;
		                if (empty($TSessions[$obj->ref][$trainee->stagerowid]['modalite'][$mode]))
		                {
		                    $TSessions[$obj->ref][$trainee->stagerowid]['modalite'][$mode] = array();
		                    foreach ($Tmonths as $m)
		                    {
		                        $TSessions[$obj->ref][$trainee->stagerowid]['modalite'][$mode]['months'][$m]['canceled'] = 0;
		                        $TSessions[$obj->ref][$trainee->stagerowid]['modalite'][$mode]['months'][$m]['missing'] = 0;
		                        $TSessions[$obj->ref][$trainee->stagerowid]['modalite'][$mode]['months'][$m]['presence'] = 0;
		                    }
		                    $TSessions[$obj->ref][$trainee->stagerowid]['modalite'][$mode]['total_heures'] = 0;
		                }

			            $mois = dol_print_date(strtotime($obj->date_session), '__b__ %Y', 'tzserver', $this->outputlangs);

			            if ($calendar->status == Agefodd_sesscalendar::STATUS_CANCELED)
			            {
			                $hourslabel = "canceled";
			                $TSessions[$obj->ref][$trainee->stagerowid]['modalite'][$mode]['months'][$mois]['canceled'] += $calendar->duration;
			            }
			            elseif ($calendar->status == Agefodd_sesscalendar::STATUS_MISSING)
			            {
			                $hourslabel = "missing";

			                $TSessions[$obj->ref][$trainee->stagerowid]['modalite'][$mode]['months'][$mois]['missing'] += $calendar->duration;
			                $TSessions[$obj->ref][$trainee->stagerowid]['modalite'][$mode]['total_heures'] += $calendar->duration;
			            }
			            else
			            {
			                $hourslabel = "presence";
			                if (empty($conf->global->AGF_USE_REAL_HOURS)) // si on utilise pas les heures réelles
			                {
			                    $TSessions[$obj->ref][$trainee->stagerowid]['modalite'][$mode]['months'][$mois]['presence'] += $calendar->duration;
			                    $TSessions[$obj->ref][$trainee->stagerowid]['modalite'][$mode]['total_heures'] += $calendar->duration;
			                }
			                else
			                {
			                    // sinon on récupère les heures saisies
			                    $heuresStagiaires = new Agefoddsessionstagiaireheures($this->db);
			                    $heuresStagiaires->fetch_by_session($obj->rowid, $trainee->id, $calendar->id);

			                    $TSessions[$obj->ref][$trainee->stagerowid]['modalite'][$mode]['months'][$mois]['presence'] += floatval($heuresStagiaires->heures);
			                    $TSessions[$obj->ref][$trainee->stagerowid]['modalite'][$mode]['total_heures'] += floatval($heuresStagiaires->heures);
			                }
			            }

			        }

			        $i++;
			    }

			    // Construire le tableau $this->lines
			    $this->lines = array();
			    $i = 0;
			    // parcours du tableau $TSessions
			    foreach ($TSessions as $refsession => $TStagiaires)
			    {
			        foreach ($TStagiaires as $stag_id => $stag)
			        {
			            foreach ($stag['modalite'] as $modname => $data)
			            {
			                $line = new ReportCalendarByCustomerLine();

			                $line->id = $i;
			                $line->refsession = $refsession;
			                $line->stagiaire = $stag['name'];
			                $line->modalite = $modname;
			                $line->months = $data['months'];
			                $line->total_heures = $data['total_heures'];
			                $line->total_reste = $stag['duree_session'];

			                $this->lines[] = $line;
			                $i++;
			            }
			        }
			    }

// 			    echo '<pre>'; var_dump($this->array_column_header); echo '<br />'; print_r($this->lines); exit;
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::".__METHOD__.' ERROR ' . $this->error, LOG_ERR);
			return - 1;
		}
	}

	function complete_header($filter)
	{
	    global $Tmonths;

	    $Tmonths = array();

	    $sql = 'SELECT DISTINCT s.rowid, s.ref, sesscal.rowid as cal_id, sesscal.calendrier_type, sesscal.date_session';
	    $sql.= ' FROM llx_agefodd_session AS s';
	    $sql.= ' LEFT JOIN llx_agefodd_session_calendrier AS sesscal ON sesscal.fk_agefodd_session = s.rowid';
	    $sql.= ' INNER JOIN llx_agefodd_session_stagiaire AS ss ON s.rowid = ss.fk_session_agefodd';

	    if ($filter['so.rowid']) $sql .= ' INNER JOIN llx_agefodd_stagiaire AS sta ON ss.fk_stagiaire = sta.rowid AND ss.fk_soc = '.$this->db->escape($filter['so.rowid']);

	    $sql .= ' WHERE s.entity IN (' . getEntity('agefodd') . ')';

	    // Manage filter
	    if (count($filter) > 0) {
	        foreach ( $filter as $key => $value )
	        {
	            if ($key == 'sesscal.date_session')
	            {
	                if (array_key_exists('start', $value))
	                {
	                    $sql .= ' AND ' . $key . '>=\'' . $this->db->idate($value['start']) . "'";
	                }
	                if (array_key_exists('end', $value))
	                {
	                    $sql .= ' AND ' . $key . '<=\'' . $this->db->idate($value['end']) . "'";
	                }
	            }
	        }
	    }

	    $sql.= ' ORDER BY s.ref, sesscal.date_session, sesscal.calendrier_type DESC';

	    dol_syslog(get_class($this) . "::complete_header sql=" . $sql, LOG_DEBUG);
	    $resql = $this->db->query($sql);

	    if ($resql)
	    {
	        $num = $this->db->num_rows($resql);
	        $i = 0;

	        if ($num) {

	            while ( $i < $num ) {
	                $obj = $this->db->fetch_object($resql);

	                $tmpdate = dol_print_date(strtotime($obj->date_session), '__b__ %Y', 'tzserver', $this->outputlangs);
	                if (!in_array($tmpdate, $Tmonths))
	                    $Tmonths[] = dol_print_date(strtotime($obj->date_session), '__b__ %Y', 'tzserver', $this->outputlangs);

	                $i++;
	            }

	            $nbhead = count($this->array_column_header[0]);
	            foreach ($Tmonths as $month)
	            {
	                $this->array_column_header[0][$nbhead]['type'] = 'text';
	                $this->array_column_header[0][$nbhead]['header'] = $month;
	                $this->array_column_header[0][$nbhead]['title'] = 'heures de présence';
	                $nbhead++;

	                $this->array_column_header[0][$nbhead]['type'] = 'text';
	                $this->array_column_header[0][$nbhead]['header'] = $month;
	                $this->array_column_header[0][$nbhead]['title'] = 'heures d\'absence';
	                $nbhead++;

	                $this->array_column_header[0][$nbhead]['type'] = 'text';
	                $this->array_column_header[0][$nbhead]['header'] = $month;
	                $this->array_column_header[0][$nbhead]['title'] = 'heures annulées';
	                $nbhead++;
	            }

	            $this->array_column_header[0][$nbhead]['type'] = 'text';
	            $this->array_column_header[0][$nbhead]['header'] = $this->outputlangs->transnoentities('AgfSessionSummaryTotalHours');
	            $this->array_column_header[0][$nbhead]['title'] = '';
	            $nbhead++;

	            $this->array_column_header[0][$nbhead]['type'] = 'text';
	            $this->array_column_header[0][$nbhead]['header'] = $this->outputlangs->transnoentities('AgfSessionSummaryTotalLeft');
	            $this->array_column_header[0][$nbhead]['title'] = '';
	            $nbhead++;

	        }
	        $this->db->free($resql);
	        return $num;
	    } else {
	        $this->error = "Error " . $this->db->lasterror();
	        dol_syslog(get_class($this) . "::complete_header " . $this->error, LOG_ERR);
	        return - 1;
	    }

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
	            foreach ( $filter as $key => $value ) {
	                if ($key=='sesscal.date_session') {
	                    $str_cirteria = $this->outputlangs->transnoentities('AgfSessionDetail') . ' ';
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
	                } elseif ($key == 'so.nom') {
	                    $this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[0], $this->outputlangs->transnoentities('Company'));
	                    $this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $this->row[0], $value);
	                    $this->row[0]++;
	                }
	            }
	        }
	    } catch ( Exception $e ) {
	        $this->error = $e->getMessage();
	        return - 1;
	    }

	    return 1;
	}

	public function apply_header_style()
	{
		$styleArray = array (
			'borders' => array (
				'allBorders' => array (
					'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
					'color' => array (
						'argb' => \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK
					)
				)
			),
			'fill' => array (
				'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
				'startColor' => array (
					'rgb' => 'cfe2f3'
				),
				'endColor' => array (
					'rgb' => 'cfe2f3'
				)
			),
			'font' => array (
				'color' => array (
					'argb' => \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK
				),
				'bold' => true
			),
			'alignment' => array (
				'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
			)
		);

	    $min_value_key = min(array_keys($this->array_column_header[0]));
	    $max_value_key = max(array_keys($this->array_column_header[0]));
	    $range_header = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($min_value_key) . ($this->row[0]-2) . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($max_value_key) . ($this->row[0]-2);
	    $this->workbook->getActiveSheet()->getStyle($range_header)->applyFromArray($styleArray);
	}
}

class ReportCalendarByCustomerLine {
	public $id;
	public $refsession;
	public $stagiaire;
	public $modalite;
	public $months;
	public $total_heures;
 	public $total_reste;

}
