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
require_once ('agefodd_export_excel_by_customer.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');
dol_include_once('/societe/class/societe.class.php');
dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire.class.php');
dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');

/**
 * Class to build report by customer
 */
class ReportCalendarByCustomer extends AgefoddExportExcelByCustomer {
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

		$array_column_header = array (
				0 => array (
						'type' => 'text',
						'title' => $outputlangs->transnoentities('SessionRef')
				),
				1 => array (
						'type' => 'text',
						'title' => $outputlangs->transnoentities('AgfMenuActStagiaire')
				),
				2 => array (
						'type' => 'text',
						'title' => $outputlangs->transnoentities('Type')
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
		
		$result = $this->write_header();
		if ($result < 0) {
			return $result;
		}


		// Start find data
		$result = $this->fetch_all_session($filter);
		if ($result < 0) {
			return $result;
		}

		$array_sub_total = array ();
		$array_total = array ();

		$session = new Agsession($this->db);

		$total_line = count($this->lines);
		if (count($this->lines) > 0) {
			foreach ( $this->lines as $line ) {
			    
			    if ($refsession != $line->refsession)
			    {
			        $result = $this->write_line_total($array_sub_total);
			        if ($result < 0) {
			            return $result;
			        }
			        
// 			        $array_total[9] += $array_sub_total[9];
// 			        $array_total[11] += $array_sub_total[11];
// 			        $array_total[18] += $array_sub_total[18];
// 			        $array_total[19] += $array_sub_total[19];
// 			        $array_total[20] += $array_sub_total[20];
// 			        $array_total[21] += $array_sub_total[21];
			        
			        $array_sub_total = array ();
			    }
			    
			    // Must have same struct than $array_column_header
			    $line_to_output = array ();
			    
			    // Use to break on session reference
			    $refsession = $line->refsession;

			    // ref session
			    $line_to_output[0] = $line->refsession;
			    $array_sub_total[0] = $line->refsession;

			    // nom du stagiaire
			    $line_to_output[1] = $line->stagiaire;
			    
                // modalité pédagogique
			    $line_to_output[2] = $line->modalite;
			    
			    $i = 3;
			    // colonnes des mois
			    foreach ($line->months as $m => $type)
			    {
			        
			        $line_to_output[$i] = $type['presence'];
			        $i++;
			        
			        $line_to_output[$i] = $type['missing'];
			        $i++;
			        
			        $line_to_output[$i] = $type['canceled'];
			        $i++;
			        
			    }
			    
			    // total
			    $line_to_output[$i] = $line->total_heures;
			    $array_sub_total[$i]+= $line->total_heures;
			    $i++;

			    $line_to_output[$i] = 0;
			    $array_sub_total[$i]+= $line->total_heures;
			    $i++;
			    
				$this->write_line($line_to_output);
			}
			$result = $this->write_line_total($array_sub_total);
			if ($result < 0) {
				return $result;
			}

// 			$array_total[9] += $array_sub_total[9];
// 			$array_total[11] += $array_sub_total[11];
// 			$array_total[18] += $array_sub_total[18];
// 			$array_total[19] += $array_sub_total[19];
// 			$array_total[20] += $array_sub_total[20];
// 			$array_total[21] += $array_sub_total[21];
		}
		
// 		$result = $this->write_line_total($array_total, '3d85c6');
// 		if ($result < 0) {
// 			return $result;
// 		}

		$this->row++;
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

		// exit;
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

		if ($filter['so.rowid']) $sql .= ' INNER JOIN llx_agefodd_stagiaire AS sta ON ss.fk_stagiaire = sta.rowid AND sta.fk_soc = '.$this->db->escape($filter['so.rowid']);
		
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

		dol_syslog(get_class($this) . "::fetch_all_session sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array ();

			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {
			    
			    $lastref = '';
			    
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
			            
			            $refline = $obj->ref.'-'.$trainee->stagerowid.'-'.$mode;
			            
			            if (!in_array($refline, $this->lines)){
			                $this->lines[$refline] = new ReportCalendarByCustomerLine();
			                $this->lines[$refline]->id = $refline;
			                $this->lines[$refline]->refsession = $obj->ref;
			                $this->lines[$refline]->stagiaire = $trainee->nom . ' ' . $trainee->prenom;
			                $this->lines[$refline]->modalite = $mode;
			                
			                foreach ($Tmonths as $m)
			                {
			                    $this->lines[$refline]->months[$m]['canceled'] = 0;
			                    $this->lines[$refline]->months[$m]['missing'] = 0;
			                    $this->lines[$refline]->months[$m]['presence'] = 0;
			                }
			                
			                $this->lines[$refline]->total_heures = 0;
			            }
			            
			            $mois = dol_print_date(strtotime($obj->date_session), '__b__ %Y', 'tzserver', $this->outputlangs);
			            
			            if ($calendar->status == Agefodd_sesscalendar::STATUS_CANCELED)
			            {
			                $hourslabel = "canceled";
			                $this->lines[$refline]->months[$mois]['canceled'] += $calendar->duration;
			            }
			            elseif ($calendar->status == Agefodd_sesscalendar::STATUS_MISSING)
			            {
			                $hourslabel = "missing";
			                
			                $this->lines[$refline]->months[$mois]['missing'] += $calendar->duration;
			                $this->lines[$refline]->total_heures += $calendar->duration;
			            }
			            else
			            {
			                $hourslabel = "presence";
			                if (empty($conf->global->AGF_USE_REAL_HOURS)) // si on utilise pas les heures réelles
			                {
			                    
			                    $this->lines[$refline]->months[$mois]['presence'] += $calendar->duration;
			                    $this->lines[$refline]->total_heures += $calendar->duration;
			                }
			                else
			                {
			                    // sinon on récupère les heures saisies
			                    $heuresStagiaires = new Agefoddsessionstagiaireheures($this->db);
			                    $heuresStagiaires->fetch_by_session($obj->rowid, $trainee->id, $calendar->id);
			                    
			                    $this->lines[$refline]->months[$mois]['presence'] += floatval($heuresStagiaires->heures);
			                    $this->lines[$refline]->total_heures += floatval($heuresStagiaires->heures);
			                }
			            }
			            
			        }
			        
			        $i++;
			    }
// 			    echo '<pre>'; print_r($this->lines); exit;
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all_session " . $this->error, LOG_ERR);
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
	    
	    if ($filter['so.rowid']) $sql .= ' INNER JOIN llx_agefodd_stagiaire AS sta ON ss.fk_stagiaire = sta.rowid AND sta.fk_soc = '.$this->db->escape($filter['so.rowid']);
	    
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
	            
	            $nbhead = count($this->array_column_header);
	            foreach ($Tmonths as $month)
	            {
	                $this->array_column_header[$nbhead + 1]['type'] = 'text';
	                $this->array_column_header[$nbhead + 1]['title'] = '';
	                $nbhead++;
	                
	                $this->array_column_header[$nbhead + 1]['type'] = 'text';
	                $this->array_column_header[$nbhead + 1]['title'] = $month;
	                $nbhead++;
	                
	                $this->array_column_header[$nbhead + 1]['type'] = 'text';
	                $this->array_column_header[$nbhead + 1]['title'] = '';
	                $nbhead++;
	            }
	            
	            $this->array_column_header[$nbhead + 1]['type'] = 'text';
	            $this->array_column_header[$nbhead + 1]['title'] = $this->outputlangs->transnoentities('AgfSessionSummaryTotalHours');
	            $nbhead++;
	            
	            $this->array_column_header[$nbhead + 1]['type'] = 'text';
	            $this->array_column_header[$nbhead + 1]['title'] = $this->outputlangs->transnoentities('AgfSessionSummaryTotalLeft');
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
