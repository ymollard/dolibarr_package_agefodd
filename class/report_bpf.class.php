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
	private $trainer_data = array ();
	private $trainee_data = array ();
	private $financial_data = array ();
	private $financial_data_outcome = array ();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	public function __construct($db, $outputlangs) {
		$outputlangs->load('agefodd@agefodd');
		$outputlangs->load("main");

		$sheet_array = array (
				0 => array (
						'name' => 'bpf',
						'title' => $outputlangs->transnoentities('AgfMenuReportBPF')
				)
		);

		$array_column_header = array ();

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
				if ($key == 'search_year') {
					$str_sub_name .= $this->outputlangs->transnoentities('Year');
					$str_sub_name .= $value;
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

		// Fetch Trainer
		$result = $this->fetch_trainer($filter);
		if ($result < 0) {
			return $result;
		}

		// Contruct header (column name)
		$array_column_header = array ();
		$array_column_header[0][0] = array (
				'type' => 'text',
				'title' => $this->outputlangs->transnoentities('AgfFormateur')
		);

		$array_column_header[0][1] = array (
				'type' => 'int',
				'title' => $this->outputlangs->transnoentities('AgfFormateurNb')
		);
		$array_column_header[0][2] = array (
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
		$line_to_output = array ();
		$array_total_output = array ();
		foreach ( $this->trainer_data as $label_type => $trainer_data ) {
			$line_to_output[0] = $label_type;
			$line_to_output[1] = $trainer_data['nb'];
			$line_to_output[2] = $trainer_data['time'];

			$array_total_output[0] = 'Total';
			$array_total_output[1] += $trainee_data['nb'];
			$array_total_output[2] += $trainee_data['time'];

			$result = $this->write_line($line_to_output, 0);
			if ($result < 0) {
				return $result;
			}
		}

		$result = $this->write_line_total($array_total_output, '3d85c6');
		if ($result < 0) {
			return $result;
		}

		// Fetch Trainee
		/*$result = $this->fetch_trainee($filter);
		if ($result < 0) {
			return $result;
		}

		// Contruct header (column name)
		$array_column_header = array ();
		$array_column_header[0][0] = array (
				'type' => 'text',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFTypeParticipants')
		);

		$array_column_header[0][1] = array (
				'type' => 'int',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFNbPart')
		);
		$array_column_header[0][2] = array (
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
		$line_to_output = array ();
		$array_total_output = array ();
		foreach ( $this->trainee_data as $label_type => $trainee_data ) {
			$line_to_output[0] = $label_type;
			$line_to_output[1] = $trainee_data['nb'];
			$line_to_output[2] = $trainee_data['time'];
			$array_total_output[0] = 'Total';
			$array_total_output[1] += $trainee_data['nb'];
			$array_total_output[2] += $trainee_data['time'];

			$result = $this->write_line($line_to_output, 0);
			if ($result < 0) {
				return $result;
			}
		}
		$result = $this->write_line_total($array_total_output, '3d85c6');
		if ($result < 0) {
			return $result;
		}

		// Fetch Financial data
		$result = $this->fetch_financial($filter);
		if ($result < 0) {
			return $result;
		}

		// Contruct header (column name)
		$array_column_header = array ();
		$array_column_header[0][0] = array (
				'type' => 'text',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFOrigProd')
		);

		$array_column_header[0][1] = array (
				'type' => 'number',
				'title' => $this->outputlangs->transnoentities('Amount')
		);

		$this->setArrayColumnHeader($array_column_header);

		$result = $this->write_header();
		if ($result < 0) {
			return $result;
		}

		// Ouput Lines
		$line_to_output = array ();
		$array_total_output = array ();
		foreach ( $this->financial_data as $label_type => $financial_data ) {
			$line_to_output[0] = $label_type;
			$line_to_output[1] = $financial_data;

			$array_total_output[0] = $this->outputlangs->transnoentities('Total');
			$array_total_output[1] += $financial_data;

			$result = $this->write_line($line_to_output, 0);
			if ($result < 0) {
				return $result;
			}
		}
		$result = $this->write_line_total($array_total_output, '3d85c6');
		if ($result < 0) {
			return $result;
		}

		// Fetch Financial data outcome
		$result = $this->fetch_financial_outcome($filter);
		if ($result < 0) {
			return $result;
		}

		// Contruct header (column name)
		$array_column_header = array ();

		$array_column_header[0][0] = array (
				'type' => 'text',
				'title' => $this->outputlangs->transnoentities('Code')
		);

		$array_column_header[0][1] = array (
				'type' => 'text',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFRubrique')
		);

		$array_column_header[0][2] = array (
				'type' => 'text',
				'title' => $this->outputlangs->transnoentities('AgfReportBPFCharge')
		);

		$array_column_header[0][3] = array (
				'type' => 'number',
				'title' => $this->outputlangs->transnoentities('Amount')
		);

		$this->setArrayColumnHeader($array_column_header);

		$result = $this->write_header();
		if ($result < 0) {
			return $result;
		}

		// Ouput Lines
		$line_to_output = array ();
		$array_total_output = array ();
		foreach ( $this->financial_data_outcome as $label_type => $financial_data ) {
			$line_to_output[0] = $financial_data['code'];
			$line_to_output[1] = $financial_data['rubrique'];
			$line_to_output[2] = $financial_data['label'];
			$line_to_output[3] = $financial_data['amount'];
			$array_total_output[0] = 'Total';
			$array_total_output[1] = '';
			$array_total_output[2] = '';
			$array_total_output[3] += $financial_data['amount'];

			$result = $this->write_line($line_to_output, 0);
			if ($result < 0) {
				return $result;
			}
		}
		$result = $this->write_line_total($array_total_output, '3d85c6');
		if ($result < 0) {
			return $result;
		}*/

		/*$this->row[0] ++;
		$result = $this->write_filter($filter);
		if ($result < 0) {
			return $result;
		}*/

		$this->close_file(0, 0, 0);
		return count($this->trainer_data);
		// return 1;
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_trainer($filter = array()) {
		global $langs, $conf;

		$minyear = dol_print_date(dol_now(), '%Y');
		// find minimum invoice year with dolibarr
		$sql = "SELECT MIN(YEAR(sess.dated)) as minyear";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess";

		dol_syslog(get_class($this) . "::".__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$minyear = $obj->minyear;
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::".__METHOD__. $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);

		if (array_key_exists('startyear', $filter) && $filter['startyear'] < $minyear) {
			$this->error = $langs->trans('AgfReportMinimumYear', $minyear);
			return - 1;
		}

		// find max year to report
		if (array_key_exists('startyear', $filter)) {
			$max_year = $filter['startyear'];
		} else {
			$max_year = dol_print_date(dol_now(), '%Y');
		}

		if ($max_year < $minyear) {
			$this->error = $langs->trans('AgfReportMaxYear', $max_year);
			return - 1;
		}

		// For Nb Trainer
		$sql = "select count(DISTINCT form.rowid) as cnt, SUM(TIME_TO_SEC(TIMEDIFF(formtime.heuref, formtime.heured)))/(24*60*60) as timeinsession,fromtype.intitule";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur AS sessform ON sessform.fk_session=sess.rowid AND sessform.trainer_status IN (3,4)";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formateur_type as fromtype ON fromtype.rowid=sessform.fk_agefodd_formateur_type";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as form ON form.rowid=sessform.fk_agefodd_formateur";
		$sql .= " INNER JOIN llx_agefodd_session_formateur_calendrier as formtime ON formtime.fk_agefodd_session_formateur=sessform.rowid";
		$sql .= " WHERE YEAR(sess.dated)=" . $filter['search_year'];
		$sql .= " GROUP BY fromtype.intitule";

		dol_syslog(get_class($this) . "::fetch_trainee", LOG_DEBUG);
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
			dol_syslog(get_class($this) . "::fetch_trainee " . $this->error, LOG_ERR);
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

		// For Nb Trainee
		$sql = "select count(DISTINCT sta.rowid) as cnt ,SUM(TIME_TO_SEC(TIMEDIFF(statime.heuref, statime.heured)))/(24*60*60) as timeinsession, statype.intitule ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as sess ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire AS sesssta ON sesssta.fk_session_agefodd=sess.rowid AND sesssta.status_in_session IN (3,4) ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire_type as statype ON statype.rowid=sesssta.fk_agefodd_stagiaire_type ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_stagiaire as sta ON sta.rowid=sesssta.fk_stagiaire ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as statime ON statime.fk_agefodd_session=sess.rowid ";
		$sql .= " WHERE YEAR(sess.dated)=" . $filter['search_year'];
		$sql .= " GROUP BY statype.intitule ";

		dol_syslog(get_class($this) . "::fetch_trainer nb", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->trainee_data[$obj->intitule]['nb'] = $obj->cnt;
					$this->trainee_data[$obj->intitule]['time'] = $obj->timeinsession;
				}
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_ca TTC" . $this->error, LOG_ERR);
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
	function fetch_financial($filter = array()) {
		global $langs, $conf;

		if (! empty($conf->global->AGF_CAT_BPF_PRODPEDA) && ! empty($conf->global->AGF_CAT_BPF_ENTFRENCH)) {
			// 1.a :: Entreprises pour la formation de leurs salariés
			$sql = "SELECT SUM(facdet.total_ht) as amount ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f  ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as facdet ON facdet.fk_facture=f.rowid  ";
			// Customer is Entreprise Francaise
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = f.fk_soc AND so.rowid IN ";
			$sql .= " (SELECT fk_soc FROM " . MAIN_DB_PREFIX . "categorie_societe WHERE fk_categorie IN (" . $conf->global->AGF_CAT_BPF_ENTFRENCH . "))  ";
			// Product in categorie Pédagogique and children
			$sql .= " WHERE facdet.fk_product IN (SELECT catprod.fk_product FROM " . MAIN_DB_PREFIX . "categorie_product as catprod WHERE catprod.fk_categorie IN (" . $conf->global->AGF_CAT_BPF_PRODPEDA . ")  ";
			// Invoice concern only session match with year criteria
			$sql .= " AND f.rowid IN (SELECT sesselement.fk_element FROM llx_agefodd_session_element as sesselement INNER JOIN llx_agefodd_session as sess ";
			$sql .= " ON sess.rowid=sesselement.fk_session_agefodd AND sesselement.element_type='invoice' AND YEAR(sess.dated)=" . $filter['search_year'] . ")";
			$sql .= " AND f.fk_statut in (1,2)) ";

			dol_syslog(get_class($this) . "::fetch_financial 1.a", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					while ( $obj = $this->db->fetch_object($resql) ) {
						$this->financial_data['1.a:Entreprises pour la formation de leurs salariés'] = $obj->amount;
					}
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::fetch_financial 1.a" . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);
		}

		if (! empty($conf->global->AGF_CAT_BPF_PRODPEDA) && ! empty($conf->global->AGF_CAT_BPF_OPCA)) {
			// 2.a :: OPCA au titre du plan de formation
			$sql = "SELECT SUM(facdet.total_ht) as amount ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f  ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as facdet ON facdet.fk_facture=f.rowid  ";
			// Customer is OPCA type
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = f.fk_soc AND so.rowid IN ";
			$sql .= " (SELECT fk_soc FROM " . MAIN_DB_PREFIX . "categorie_societe WHERE fk_categorie IN (" . $conf->global->AGF_CAT_BPF_OPCA . "))  ";
			// Product in categorie Pédagogique and children
			$sql .= " WHERE facdet.fk_product IN (SELECT catprod.fk_product FROM " . MAIN_DB_PREFIX . "categorie_product as catprod WHERE catprod.fk_categorie IN (" . $conf->global->AGF_CAT_BPF_PRODPEDA . "))  ";
			// Invoice concern only session match with year criteria
			$sql .= " AND f.rowid IN (SELECT sesselement.fk_element FROM llx_agefodd_session_element as sesselement INNER JOIN llx_agefodd_session as sess ";
			$sql .= " ON sess.rowid=sesselement.fk_session_agefodd AND sesselement.element_type='invoice' AND YEAR(sess.dated)=" . $filter['search_year'] . ")";
			$sql .= " AND f.fk_statut in (1,2) ";

			dol_syslog(get_class($this) . "::fetch_financial 2.a", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					while ( $obj = $this->db->fetch_object($resql) ) {
						$this->financial_data['2.a:OPCA au titre du plan de formation'] = $obj->amount;
					}
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::fetch_financial 2.a" . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);
		}

		if (! empty($conf->global->AGF_CAT_BPF_PRODPEDA) && ! empty($conf->global->AGF_CAT_BPF_ADMINISTRATION)) {
			// 3.a :: Pouvoirs publics Pour leurs agents
			$sql = "SELECT SUM(facdet.total_ht) as amount ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f  ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as facdet ON facdet.fk_facture=f.rowid  ";
			// Customer is Administration type
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = f.fk_soc AND so.rowid IN  ";
			$sql .= " (SELECT fk_soc FROM " . MAIN_DB_PREFIX . "categorie_societe WHERE fk_categorie IN (" . $conf->global->AGF_CAT_BPF_ADMINISTRATION . "))  ";
			// Product in categorie Pédagogique and children
			$sql .= " WHERE facdet.fk_product IN (SELECT catprod.fk_product FROM " . MAIN_DB_PREFIX . "categorie_product as catprod WHERE catprod.fk_categorie IN (" . $conf->global->AGF_CAT_BPF_PRODPEDA . "))  ";
			// Invoice concern only session match with year criteria
			$sql .= " AND f.rowid IN (SELECT sesselement.fk_element FROM llx_agefodd_session_element as sesselement INNER JOIN llx_agefodd_session as sess ";
			$sql .= " ON sess.rowid=sesselement.fk_session_agefodd AND sesselement.element_type='invoice' AND YEAR(sess.dated)=" . $filter['search_year'] . ")";
			$sql .= " AND f.fk_statut in (1,2) ";

			dol_syslog(get_class($this) . "::fetch_financial 3.a", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					while ( $obj = $this->db->fetch_object($resql) ) {
						$this->financial_data['3.a:Pouvoirs publics Pour leurs agents'] = $obj->amount;
					}
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::fetch_financial 3.a" . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);
		}

		if (! empty($conf->global->AGF_CAT_BPF_PRODPEDA) && ! empty($conf->global->AGF_CAT_BPF_PARTICULIER)) {
			// 4 :: Particuliers
			$sql = "SELECT SUM(facdet.total_ht) as amount ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f  ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as facdet ON facdet.fk_facture=f.rowid  ";
			// Customer is Particulier type
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = f.fk_soc AND so.rowid IN  ";
			$sql .= " (SELECT fk_soc FROM " . MAIN_DB_PREFIX . "categorie_societe WHERE fk_categorie IN (" . $conf->global->AGF_CAT_BPF_PARTICULIER . "))  ";
			// Product in categorie Pédagogique and children
			$sql .= " WHERE facdet.fk_product IN (SELECT catprod.fk_product FROM " . MAIN_DB_PREFIX . "categorie_product as catprod WHERE catprod.fk_categorie IN (" . $conf->global->AGF_CAT_BPF_PRODPEDA . "))  ";
			// Invoice concern only session match with year criteria
			$sql .= " AND f.rowid IN (SELECT sesselement.fk_element FROM llx_agefodd_session_element as sesselement INNER JOIN llx_agefodd_session as sess ";
			$sql .= " ON sess.rowid=sesselement.fk_session_agefodd AND sesselement.element_type='invoice' AND YEAR(sess.dated)=" . $filter['search_year'] . ")";
			$sql .= " AND f.fk_statut in (1,2) ";

			dol_syslog(get_class($this) . "::fetch_financial 4", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					while ( $obj = $this->db->fetch_object($resql) ) {
						$this->financial_data['4:Particuliers'] = $obj->amount;
					}
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::fetch_financial 4" . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);
		}

		if (! empty($conf->global->AGF_CAT_BPF_PRODPEDA) && ! empty($conf->global->AGF_CAT_BPF_PRESTA)) {
			// 5 :: Prestataires de formation
			$sql = "SELECT SUM(facdet.total_ht) as amount ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f  ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as facdet ON facdet.fk_facture=f.rowid  ";
			// Customer is Prestataires de formation
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = f.fk_soc AND so.rowid IN  ";
			$sql .= " (SELECT fk_soc FROM " . MAIN_DB_PREFIX . "categorie_societe WHERE fk_categorie IN (" . $conf->global->AGF_CAT_BPF_PRESTA . "))  ";
			// Product in categorie Pédagogique and children
			$sql .= " WHERE facdet.fk_product IN (SELECT catprod.fk_product FROM " . MAIN_DB_PREFIX . "categorie_product as catprod WHERE catprod.fk_categorie IN (" . $conf->global->AGF_CAT_BPF_PRODPEDA . "))  ";
			// Invoice concern only session match with year criteria
			$sql .= " AND f.rowid IN (SELECT sesselement.fk_element FROM llx_agefodd_session_element as sesselement INNER JOIN llx_agefodd_session as sess ";
			$sql .= " ON sess.rowid=sesselement.fk_session_agefodd AND sesselement.element_type='invoice' AND YEAR(sess.dated)=" . $filter['search_year'] . ")";
			$sql .= " AND f.fk_statut in (1,2) ";

			dol_syslog(get_class($this) . "::fetch_financial 5", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					while ( $obj = $this->db->fetch_object($resql) ) {
						$this->financial_data['5:Prestataires de formation'] = $obj->amount;
					}
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::fetch_financial 5" . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);
		}

		if (! empty($conf->global->AGF_CAT_BPF_PRODPEDA) && ! empty($conf->global->AGF_CAT_BPF_FOREIGNCOMP)) {
			// 6.a :: Autres produits Formations HTVA (facturées à des entreprises étrangères)
			$sql = "SELECT SUM(facdet.total_ht) as amount ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f  ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as facdet ON facdet.fk_facture=f.rowid  ";
			// Customer is Entreprise étrangére
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = f.fk_soc AND so.rowid IN  ";
			$sql .= " (SELECT fk_soc FROM " . MAIN_DB_PREFIX . "categorie_societe WHERE fk_categorie IN (" . $conf->global->AGF_CAT_BPF_FOREIGNCOMP . "))  ";
			// Product in categorie Pédagogique and children
			$sql .= " WHERE facdet.fk_product IN (SELECT catprod.fk_product FROM " . MAIN_DB_PREFIX . "categorie_product as catprod WHERE catprod.fk_categorie IN (" . $conf->global->AGF_CAT_BPF_PRODPEDA . "))  ";
			// Invoice concern only session match with year criteria
			$sql .= " AND f.rowid IN (SELECT sesselement.fk_element FROM llx_agefodd_session_element as sesselement INNER JOIN llx_agefodd_session as sess ";
			$sql .= " ON sess.rowid=sesselement.fk_session_agefodd AND sesselement.element_type='invoice' AND YEAR(sess.dated)=" . $filter['search_year'] . ")";
			$sql .= " AND f.fk_statut in (1,2)";

			dol_syslog(get_class($this) . "::fetch_financial 6.a", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					while ( $obj = $this->db->fetch_object($resql) ) {
						$this->financial_data['6.a:Autres produitsFormations HTVA (facturées à des entreprises étrangères)'] = $obj->amount;
					}
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::fetch_financial 6.a" . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);
		}

		if (! empty($conf->global->AGF_CAT_BPF_TOOLPEDA)) {
			// 6.b :: Outils pédagogiques
			$sql = "SELECT SUM(facdet.total_ht) as amount ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f  ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as facdet ON facdet.fk_facture=f.rowid  ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = f.fk_soc";
			// Product in categorie Pédagogique and children
			$sql .= " WHERE facdet.fk_product IN (SELECT catprod.fk_product FROM " . MAIN_DB_PREFIX . "categorie_product as catprod WHERE catprod.fk_categorie IN (" . $conf->global->AGF_CAT_BPF_TOOLPEDA . "))  ";
			// Invoice concern only session match with year criteria
			$sql .= " AND f.rowid IN (SELECT sesselement.fk_element FROM llx_agefodd_session_element as sesselement INNER JOIN llx_agefodd_session as sess ";
			$sql .= " ON sess.rowid=sesselement.fk_session_agefodd AND sesselement.element_type='invoice' AND YEAR(sess.dated)=" . $filter['search_year'] . ")";
			$sql .= " AND f.fk_statut in (1,2) ";

			dol_syslog(get_class($this) . "::fetch_financial 6.b", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					while ( $obj = $this->db->fetch_object($resql) ) {
						$this->financial_data['6.b:Outils pédagogiques'] = $obj->amount;
					}
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::fetch_financial 6.b" . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);
		}

		if (! empty($conf->global->AGF_CAT_PRODUCT_CHARGES)) {
			// 6.f :: Autres produits liés à la formation
			$sql = "SELECT SUM(facdet.total_ht) as amount ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f  ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as facdet ON facdet.fk_facture=f.rowid  ";
			$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = f.fk_soc";
			// Product in categorie Frais
			$sql .= " WHERE facdet.fk_product IN (SELECT catprod.fk_product FROM " . MAIN_DB_PREFIX . "categorie_product as catprod WHERE catprod.fk_categorie IN (" . $conf->global->AGF_CAT_PRODUCT_CHARGES . "))  ";
			// Invoice concern only session match with year criteria
			$sql .= " AND f.rowid IN (SELECT sesselement.fk_element FROM llx_agefodd_session_element as sesselement INNER JOIN llx_agefodd_session as sess ";
			$sql .= " ON sess.rowid=sesselement.fk_session_agefodd AND sesselement.element_type='invoice' AND YEAR(sess.dated)=" . $filter['search_year'] . ")";
			$sql .= " AND f.fk_statut in (1,2) ";

			dol_syslog(get_class($this) . "::fetch_financial 6.b", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					while ( $obj = $this->db->fetch_object($resql) ) {
						$this->financial_data['6.f:Autres produits liés à la formation'] = $obj->amount;
					}
				}
			} else {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(get_class($this) . "::fetch_financial 6.b" . $this->error, LOG_ERR);
				return - 1;
			}
			$this->db->free($resql);
		}
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_financial_outcome($filter = array()) {
		global $langs, $conf;

		// 60 B :: Achats (fournitures)
		$sql = "SELECT SUM(facdet.total_ht) as amount ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f  ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn_det as facdet ON facdet.fk_facture_fourn=f.rowid  ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = f.fk_soc";
		$sql .= " WHERE facdet.fk_product IN (SELECT catprod.fk_product FROM " . MAIN_DB_PREFIX . "categorie_product as catprod WHERE catprod.fk_categorie IN (" . $conf->global->AGF_CAT_PRODUCT_CHARGES . "))  ";
		// Invoice not concern by session
		$sql .= " AND f.rowid  NOT IN (SELECT sesselement.fk_element FROM llx_agefodd_session_element as sesselement INNER JOIN llx_agefodd_session as sess ";
		$sql .= " ON sess.rowid=sesselement.fk_session_agefodd AND sesselement.element_type IN ('invoice_supplier_trainer','invoice_supplier_missions','invoice_supplier_room') ";
		$sql .= " AND YEAR(sess.dated)=" . $filter['search_year'] . ")";
		$sql .= " AND YEAR(f.datef)=" . $filter['search_year'];

		dol_syslog(get_class($this) . "::fetch_financial_outcome 60 B ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->financial_data_outcome[] = array (
							'code' => '60',
							'rubrique' => 'B',
							'label' => 'Achats (fournitures)',
							'amount' => $obj->amount
					);
				}
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_financial_outcome 60 B " . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);

		// 6226 :: Honoraires de formation
		$sql = "SELECT SUM(facdet.total_ht) as amount ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f  ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn_det as facdet ON facdet.fk_facture_fourn=f.rowid  ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = f.fk_soc";
		// Honoraire
		$sql .= " WHERE facdet.fk_product IN (SELECT catprod.fk_product FROM " . MAIN_DB_PREFIX . "categorie_product as catprod WHERE catprod.fk_categorie IN (" . $conf->global->AGF_CAT_BPF_FEEPRESTA . "))  ";
		// Invoice concern only session
		$sql .= " AND f.rowid IN (SELECT sesselement.fk_element FROM llx_agefodd_session_element as sesselement INNER JOIN llx_agefodd_session as sess ";
		$sql .= " ON sess.rowid=sesselement.fk_session_agefodd AND sesselement.element_type='invoice_supplier_trainer' AND YEAR(sess.dated)=" . $filter['search_year'] . ")";
		$sql .= " AND YEAR(f.datef)=" . $filter['search_year'];

		dol_syslog(get_class($this) . "::fetch_financial_outcome 604 ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->financial_data_outcome[] = array (
							'code' => '6226',
							'rubrique' => '',
							'label' => 'Honoraires de formation',
							'amount' => $obj->amount
					);
				}
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_financial_outcome 604 " . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);

		// 6132 :: Locations liées à la formation
		$sql = "SELECT SUM(facdet.total_ht) as amount ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f  ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn_det as facdet ON facdet.fk_facture_fourn=f.rowid  ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = f.fk_soc";
		// Invoice concern only session
		$sql .= " WHERE f.rowid IN (SELECT sesselement.fk_element FROM llx_agefodd_session_element as sesselement INNER JOIN llx_agefodd_session as sess ";
		$sql .= " ON sess.rowid=sesselement.fk_session_agefodd AND sesselement.element_type='invoice_supplier_room' AND YEAR(sess.dated)=" . $filter['search_year'] . ")";
		$sql .= " AND YEAR(f.datef)=" . $filter['search_year'];

		dol_syslog(get_class($this) . "::fetch_financial_outcome Locations liées à la formation ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->financial_data_outcome[] = array (
							'code' => '6132',
							'rubrique' => '',
							'label' => 'Locations liées à la formation',
							'amount' => $obj->amount
					);
				}
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_financial_outcome Locations liées à la formation " . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);

		// 62 C :: Autres services extérieurs (Honoraires, commissions, déplacements, réceptions, cadeaux, frais bancaires, postes et telecommunications)
		$sql = "SELECT SUM(facdet.total_ht) as amount ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn as f  ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn_det as facdet ON facdet.fk_facture_fourn=f.rowid  ";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid = f.fk_soc";
		// Invoice concern only session
		$sql .= " WHERE f.rowid IN (SELECT sesselement.fk_element FROM llx_agefodd_session_element as sesselement INNER JOIN llx_agefodd_session as sess ";
		$sql .= " ON sess.rowid=sesselement.fk_session_agefodd AND sesselement.element_type='invoice_supplier_missions' AND YEAR(sess.dated)=" . $filter['search_year'] . ")";
		$sql .= " AND YEAR(f.datef)=" . $filter['search_year'];

		dol_syslog(get_class($this) . "::fetch_financial_outcome Locations liées à la formation ", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {
					$this->financial_data_outcome[] = array (
							'code' => '62',
							'rubrique' => 'C',
							'label' => 'Autres services extérieurs(déplacements)',
							'amount' => $obj->amount
					);
				}
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_financial_outcome Locations liées à la formation " . $this->error, LOG_ERR);
			return - 1;
		}
		$this->db->free($resql);

		/*

		 Charges
		 60	A	Achats (fournitures)
		 604		Achats de prestations de formation
		 61	B	Services extérieurs (Sous-traitance, crédit-bail, location, assurances)
		 613		Locations
		 6132		Locations liées à la formation
		 6135		Locations de matériel pédagogique lié à la formation
		 62	C	Autres services extérieurs (Honoraires, commissions, déplacements, réceptions, cadeaux, frais bancaires, postes et telecommunications)
		 621		Personnel extérieur à l'entreprise
		 622		Honoraires
		 6226		Honoraires de formation
		 623		Pub, RP
		 63	D	Impôts, taxes
		 64	E	Charges de personnel (salaires, charges, commissions, avantages en nature, CP,  CO, TR, abondement)
		 641		Rémunérations du personnel
		 6411		Salaires des formateurs
		 6411		Autres salaires
		 644		Rémunération du traail de l'exploitant
		 65	F	Autres charges de gestion courante
		 66	G	Charges financières
		 67	H	Charges exceptionnelles
		 68	I	Dotations aux amortissements
		 69	J	Impôts sur les bénéfices et assimilés
		 Total des charges*/
	}
}

