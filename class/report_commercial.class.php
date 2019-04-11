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
 * Class to build commercial report
 */
class ReportCommercial extends AgefoddExportExcel
{
	private $TData = array();
	private $TCache = array(
		'salesrep' => array()
	);
	private $value_ca_total_hthf = array ();

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
	public function __construct($db, $outputlangs)
	{
		$outputlangs->load('agefodd@agefodd');
		$outputlangs->load('bills');
		$outputlangs->load("exports");
		$outputlangs->load("main");
		$outputlangs->load("commercial");
		$outputlangs->load("companies");
		$outputlangs->load("products");
		$outputlangs->load('admin');

		$sheet_array = array (
				0 => array (
						'name' => 'send',
						'title' => $outputlangs->transnoentities('AgfMenuReportCommercial')
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
	public function write_filter($filter)
	{
		dol_syslog(get_class($this) . "::write_filter ");
		// Create a format for the column headings
		try
		{
			// Manage filter
			if (count($filter) > 0)
			{
				foreach ( $this->sheet_array as $keysheet => $sheet )
				{
					$this->workbook->setActiveSheetIndex($keysheet);

					foreach ( $filter as $key => $value )
					{
						$filterName = '';
						$filterValue = '';

						switch($key) {
							case 'startyear':
								$filterName = $this->outputlangs->transnoentities('AgfReportCommercialBaseYear');
								$filterValue = $value;
								break;

							case 'nbyears':
								$filterName = $this->outputlangs->transnoentities('AgfReportCommercialNbYears');
								$filterValue = $value;
								break;

							case 'accounting_date':
								$filterName = $this->outputlangs->transnoentities('AgfReportCommercialInvoiceAccountingDate');
								$filterValue = $this->outputlangs->transnoentities($this->T_ACCOUNTING_DATE_CHOICES[$value]);
								break;

							case 's.type_session':
								$filterName = $this->outputlangs->transnoentities('AgfReportCommercialSessionType');

								if ($value == 0) {
									$filterValue = $this->outputlangs->transnoentities('AgfFormTypeSessionIntra');
								} elseif ($value == 1) {
									$filterValue = $this->outputlangs->transnoentities('AgfFormTypeSessionInter');
								}

								break;

							case 'sale.fk_user':
								$filterName = $this->outputlangs->transnoentities('SalesRepresentative');
								require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
								$user_salesman = new User($this->db);
								$result = $user_salesman->fetch($value);
								if ($result < 0) {
									$this->error = $user_salesman->error;
									return $result;
								}
								$filterValue = $user_salesman->getFullName($this->outputlangs);
								break;

							case 'soc.rowid':
								$filterName = $this->outputlangs->transnoentities('Companies');
								$filterValue = $this->outputlangs->transnoentities('AgfReportCommercialFilterCompaniesSeeAbove');
								break;

							case 's.client':
								$filterName = $this->outputlangs->transnoentities('ProspectCustomer');
								switch ($value) {
									case 1: // Client
										$filterValue = 'C';
										break;

									case 2: // Prospect
										$filterValue = 'P';
										break;

									case 3: // Client/Prospect
										$filterValue = 'CP';
										break;
								}

								break;

							case 's.active':
								$filterName = $this->outputlangs->transnoentities('AgfReportCommercialOnlyActive');
								break;

							case 's.created_during_selected_period':
								$filterName = $this->outputlangs->transnoentities('AgfReportCommercialOnlyCreatedOnSelectedPeriod');
								break;

							case 'detail':
								$filterName = $this->outputlangs->transnoentities('AgfReportCommercialDetail');
						}

						if(! empty($filterName))
						{
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $this->row[$keysheet], $filterName);
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row[$keysheet], $filterValue);
							$this->row[$keysheet]++;
						}
					}
				}
			}
		}
		catch ( Exception $e )
		{
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
	public function getSubTitlFileName($filter)
	{
		$str_sub_name = '';

		if (count($filter) > 0)
		{
			foreach ( $filter as $key => $value )
			{
				switch($key)
				{
					case 'startyear':
						$str_sub_name .= '-' . $this->outputlangs->transnoentities('Year') . $value;
						break;

					case 'nbyears':
						$str_sub_name .= '-' . $value . $this->outputlangs->transnoentities('Years');
						break;

					case 'accounting_date':
						$str_sub_name .= '-' . str_replace(' ', '', ucwords($this->outputlangs->transnoentities($this->T_ACCOUNTING_DATE_CHOICES[$value])));
						break;

					case 's.type_session':
						if ($value == 0) {
							$type_session = $this->outputlangs->transnoentities('AgfFormTypeSessionIntra');
						} elseif ($value == 1) {
							$type_session = $this->outputlangs->transnoentities('AgfFormTypeSessionInter');
						}
						$str_sub_name .= '-' . $this->outputlangs->transnoentities('Type') . $type_session;
						break;

					case 'sale.fk_user':
						require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
						$user_salesman = new User($this->db);
						$result = $user_salesman->fetch($value);
						if ($result < 0) {
							$this->error = $user_salesman->error;
							return $result;
						}
						$str_sub_name .= '-' . $this->outputlangs->transnoentities('SalesRepresentative') . $user_salesman->getFullName($this->outputlangs);
						break;

					case 'soc.rowid':
						$str_sub_name .= '-' . $this->outputlangs->transnoentities('Companies') . implode('-', $value);
						break;

					case 's.active':
						$str_sub_name .= '-' . $this->outputlangs->transnoentities('Active');
						break;

					case 's.created_during_selected_period':
						$str_sub_name .= '-' . str_replace(' ', '', ucwords($this->outputlangs->transnoentities('AgfReportCommercialFilterCreatedDuringPeriod')));
						break;

					case 's.client':
						switch($value)
						{
							case 1: // Client
								$str_sub_name .= '-C';
								break;

							case 2: // Prospect
								$str_sub_name .= '-P';
								break;

							case 3: // Client/Prospect
								$str_sub_name .= '-CP';
								break;
						}

						break;

					case 'detail':
						$str_sub_name .= '-' . $this->outputlangs->transnoentities('AgfReportCommercialFilterDetailed');
				}
			}
		}

		$str_sub_name = str_replace(' ', '_', $str_sub_name);
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
	public function write_file($filter)
	{
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

		$this->title = $this->outputlangs->transnoentities('AgfMenuReportCommercial');
		$this->subject = $this->outputlangs->transnoentities('AgfMenuReportCommercial');
		$this->description = $this->outputlangs->transnoentities('AgfMenuReportCommercial');
		$this->keywords = $this->outputlangs->transnoentities('AgfMenuReportCommercial');


		$this->year_to_report_array = array($filter['startyear']);

		for($i = 1; $i < $filter['nbyears']; $i++)
		{
			$this->year_to_report_array[] = $filter['startyear'] - $i;
		}

		// General
		$result = $this->fetch_data($filter);
		if ($result < 0) {
			return $result;
		}

		$result = $this->open_file($this->file);
		if ($result < 0) {
			return $result;
		}
		/*$result = $this->write_title();
		if ($result < 0) {
			return $result;
		}*/

		// Contruct header (column name) with year array fill in fetch_ca method
		$array_column_header = array ();
		if (count($this->year_to_report_array) > 0) {

			$array_column_header[0][] = array (
				'type' => 'text',
				'title' => 'Société - Code - Client/Prospect - Maison-mère' // TODO translate
			);

			$array_column_header[0][] = array (
				'type' => 'text',
				'title' => 'CP'
			);

			foreach ( $this->year_to_report_array as $year_todo ) {
				$array_column_header[0][] = array (
					'type' => 'number',
					'title' => $year_todo
				);
			}

			$array_column_header[0][] = array (
				'type' => 'number',
				'title' => 'Total'
			);
		}

		if (count($this->year_to_report_array) > 0)
		{
			$this->setArrayColumnHeader($array_column_header);

			$result = $this->write_header();
			if ($result < 0) {
				return $result;
			}
			$array_total_hthf = array ();

			// Ouput Lines
			$line_to_output = array ();

			$TTotal = array_fill(0, count($this->year_to_report_array) + 1, 0);

			foreach($this->TData as $TDataLine)
			{
				$fill = '';

				if($TDataLine['isParentCompany'])
				{
					$fill = '9fc5e8';
				}

				if($TDataLine['isStandaloneCompany'])
				{
					$fill = 'd9d9d9';
				}

				$total = 0;

				foreach($TDataLine['row'] as $index => $value)
				{
					if($index < 2)
					{
						continue;
					}

					$total += $value;

					$TTotal[$index - 2] += $value;
				}

				$TDataLine['row'][2 + count($this->year_to_report_array)] = $total;


				$TTotal[count($this->year_to_report_array)] += $total;

				$result = $this->write_line($TDataLine['row'], 0, $fill);
				if ($result < 0)
				{
					return $result;
				}
			}

			// Totaux

			$TTotalRow = array_merge(array('TOTAUX', ''), $TTotal);

			$result = $this->write_line($TTotalRow);
			if ($result < 0)
			{
				return $result;
			}
		}

		$this->row[0]++;
		$result = $this->write_filter($filter);
		if ($result < 0) {
			return $result;
		}

		$this->close_file();
		return count($this->year_to_report_array);
	}


	public function fetch_data($filter)
	{
		$TCompanies = $this->fetch_companies($filter);
		if($TCompanies < 0)
		{
			return -1;
		}

		foreach($TCompanies as $company)
		{
			$result = $this->fetch_company_data($company, $filter);
			if($result < 0)
			{
				return -1;
			}
		}
	}


	public function fetch_companies($filter, $parentID = 0)
	{
		// 103 : OPCA
		// LEFT JOIN pour s'éviter les cas où la société parente est renseignée... mais a été supprimée
		$sql = 'SELECT s.rowid, s.nom, s.client, parent.rowid as parent, s.code_client
				FROM '  . MAIN_DB_PREFIX . 'societe s
				LEFT JOIN ' . MAIN_DB_PREFIX . 'societe parent ON (parent.rowid = s.parent)
				LEFT JOIN ' . MAIN_DB_PREFIX . 'societe_commerciaux sc ON (sc.fk_soc = s.rowid)
				WHERE s.entity = ' . getEntity('societe') . '
				AND COALESCE(parent.rowid, 0) = ' . $parentID . '
				AND s.fk_typent != 103';

		if(empty($parentID) && ! empty($filter['soc.rowid']))
		{
			if(! is_array($filter['soc.rowid']))
			{
				$filter['soc.rowid'] = array($filter['soc.rowid']);
			}

			$sql.= '
				AND s.rowid IN (' . implode(', ', $filter['soc.rowid']) . ')';
		}

		// TODO $parentID == 0 ?
		if(! empty($filter['sale.fk_user']))
		{
			$sql.= '
				AND sc.fk_user = ' . $filter['sale.fk_user'];
		}

		if(! empty($filter['s.active']))
		{
			$sql.= '
				AND s.status = 1';
		}

		if(! empty($filter['s.created_during_selected_period']))
		{
			$sql.= '
				AND s.datec <= "' . $filter['startyear'] . '"
				AND s.datec > "' . ($filter['startyear'] - $filter['nbyears']) . '"';
		}

		if(! empty($filter['s.client']))
		{
			$sql.= '
				AND s.client = ' . $filter['s.client'];
		}

		$sql.= '
				GROUP BY s.rowid
				ORDER BY s.nom ASC';


		$resql = $this->db->query($sql);

		if(! $resql)
		{
			$this->error = $this->db->lasterror();
			return -1;
		}

		$TCompanies = array();

		$num = $this->db->num_rows($resql);

		for($i = 0; $i < $num ; $i++)
		{
			$companystatic = new Societe($this->db);
			$objp = $this->db->fetch_object($resql);
			$companystatic->id = $objp->rowid;
			foreach(get_object_vars($objp) as $key => $value)
			{
				$companystatic->{ $key } = $value;
			}

			$TCompanies[$companystatic->rowid] = $companystatic;
		}

		return $TCompanies;
	}


	public function fetch_company_data(Societe $societe, $filter, &$TDataRowParent = array())
	{
		global $user;

		$TDataRow = array();

		// Company name

		$companyName = $societe->nom;
		if(! empty($societe->code_client))
		{
			$companyName .= ' - ' . $societe->code_client;
		}

		switch($societe->client)
		{
			case 1: // Client
				$companyName .= ' - C';
				break;

			case 2: // Prospect
				$companyName .= ' - P';
				break;

			case 3: // Client/Prospect
				$companyName .= ' - CP';
				break;
		}


		// SalesRep initials

		$salesrepInitials = '';
		$TSalesRep = $societe->getSalesRepresentatives($user);

		$commercial_id = $TSalesRep[0]['id'];

		if($commercial_id > 0)
		{
			if(empty($this->TCache['salesrep'][$commercial_id]))
			{
				$commercial = new User($this->db);
				$commercial->fetch($commercial_id);

				$commercialName = $commercial->getFullName($this->outputlangs, 0, 1);

				$TNameComponents = preg_split('/[\s+|\-]/', $commercialName);

				foreach ($TNameComponents as $name)
				{
					$salesrepInitials .= strtoupper(substr($name, 0, 1));
				}

				$commercial->_initials = $salesrepInitials;

				$this->TCache['salesrep'][$commercial_id] = $commercial;
			}
			else
			{
				$commercial = $this->TCache['salesrep'][$commercial_id];
			}

			$salesrepInitials = $commercial->_initials;
		}


		$TChildren = $this->fetch_companies($filter, $societe->rowid);

		if($TChildren < 0)
		{
			return -1;
		}


		if(empty($societe->parent) && ! empty($TChildren))
		{
			$companyName .= ' - M';
		}

		$TDataRow[] = $companyName;
		$TDataRow[] = $salesrepInitials;

		$result = $this->fetch_company_ca_data($TDataRow, $societe->rowid, $filter);
		if($result < 0)
		{
			return -1;
		}

		if(empty($societe->parent))
		{
			$TDataRowParent = $TDataRow;
		}

		if(! empty($filter['detail']))
		{
			$this->TData[] = array(
				'isParentCompany' => empty($societe->parent) && ! empty($TChildren)
				, 'isStandaloneCompany' => empty($societe->parent) && empty($TChildren)
				, 'row' => $TDataRow
			);
		}


		foreach ($TChildren as $child)
		{
			$return = $this->fetch_company_data($child, $filter, $TDataRowParent);
			if($return < 0)
			{
				return -1;
			}
		}

		if(empty($filter['detail']))
		{
			if(empty($societe->parent))
			{
				$this->TData[] = array(
					'isParentCompany' => empty($societe->parent) && ! empty($TChildren)
					, 'isStandaloneCompany' => empty($societe->parent) && empty($TChildren)
					, 'row' => $TDataRowParent
				);
			}
			else
			{
				foreach($TDataRow as $index => $cell)
				{
					if($index < 2)
					{
						continue;
					}

					$TDataRowParent[$index] += $cell;
				}
			}
		}

		return 1;
	}


	public function fetch_company_ca_data(&$TDataRow, $companyID, $filter)
	{
		global $conf;

		$maxYear = $this->year_to_report_array[0];

		$TTypesTodo = array();

		if(! isset($filter['s.type_session']) || $filter['s.type_session'] == '0')
		{
			$TTypesTodo[] = 'intra';
		}

		if(! isset($filter['s.type_session']) || $filter['s.type_session'] == '1')
		{
			$TTypesTodo[] = 'inter';
			$TTypesTodo[] = 'interopca';
		}

		foreach($TTypesTodo as $type)
		{
			$sql = $this->get_ca_data_sql_query($type, $companyID, $filter);

			$resql = $this->db->query($sql);

			if(! $resql)
			{
				$this->error = $this->db->lasterror();
				return -1;
			}

			$num = $this->db->num_rows($resql);

			for($i = 0; $i < $num; $i++)
			{
				$obj = $this->db->fetch_object($resql);

				$TDataRow[2 + $maxYear - $obj->year] += $obj->total;
			}
		}

		return 1;
	}


	public function get_ca_data_sql_query($type, $companyID, $filter)
	{
		global $conf;

		switch($filter['accounting_date'])
		{
			case 'session_start':
				$dateField = 's.dated';
				break;

			case 'session_end':
				$dateField = 's.datef';
				break;

			default:
				$dateField = 'f.datef';
		}


		$multiplier = 1;

		if($type == 'interopca')
		{
			$multiplier = '(
				SELECT COUNT(IF(opca.fk_soc_trainee = ' . $companyID .', 1, NULL)) / COUNT(*)
				FROM ' . MAIN_DB_PREFIX . 'facture f2
				INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_element se ON (se.fk_element = f2.rowid AND se.element_type = "invoice")
				INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire ass ON (ass.fk_session_agefodd = se.fk_session_agefodd)
				INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire ags ON (ags.rowid = ass.fk_stagiaire)
				INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_opca opca ON (opca.fk_session_agefodd = se.fk_session_agefodd AND opca.fk_session_trainee = ass.rowid AND opca.fk_soc_trainee = ags.fk_soc)
				WHERE se.fk_session_agefodd = s.rowid
				AND opca.fk_soc_OPCA = f2.fk_soc
				AND f.fk_soc = f2.fk_soc
			)';
		}

		$sql = 'SELECT YEAR(' . $dateField . ') AS year, MONTH(' . $dateField . ') AS month, COALESCE( ' . $multiplier . ' * SUM(fd.total_ht), 0) as total
				FROM ' . MAIN_DB_PREFIX . 'facture f
				INNER JOIN ' . MAIN_DB_PREFIX . 'facturedet fd ON (fd.fk_facture = f.rowid)
				INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_element se ON (se.fk_element = f.rowid AND se.element_type = "invoice")
				INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session s ON (s.rowid = se.fk_session_agefodd)
				WHERE f.fk_statut > 0
				AND f.fk_statut < 3';

		if(! empty($conf->global->AGF_CAT_PRODUCT_CHARGES))
		{
			$sql .= '
				AND COALESCE(fd.fk_product, 0) NOT IN (
					SELECT fk_product
					FROM ' . MAIN_DB_PREFIX . 'categorie_product
					WHERE fk_categorie IN (' . $conf->global->AGF_CAT_PRODUCT_CHARGES . ')
				)';
		}

		if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS))
			$sql .= '
				AND f.type IN (0,1,2)';
		else
			$sql .= '
				AND f.type IN (0,1,2,3)';

		switch ($type)
		{
			case 'intra':
				$sql.= '
				AND s.type_session = 0
				AND COALESCE(IF(s.fk_soc = 0, NULL, s.fk_soc), s.fk_soc_requester) = ' . $companyID;

				break;

			case 'inter':
				$sql.= '
				AND f.fk_soc = ' . $companyID . '
				AND s.rowid IN (
					SELECT s2.rowid
					FROM ' . MAIN_DB_PREFIX . 'agefodd_session s2
					INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire ass ON (ass.fk_session_agefodd = s2.rowid)
					INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire ags ON (ags.rowid = ass.fk_stagiaire)
					WHERE ags.fk_soc = f.fk_soc                   
					AND s2.type_session = 1
				)';

				break;

			case 'interopca':
				$sql.= '
				AND s.rowid IN (
					SELECT s2.rowid
					FROM ' . MAIN_DB_PREFIX . 'agefodd_session s2
					INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire ass ON (ass.fk_session_agefodd = s2.rowid)
					INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire ags ON (ags.rowid = ass.fk_stagiaire)
					INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_opca opca ON (opca.fk_session_trainee = ass.rowid AND opca.fk_soc_trainee = ags.fk_soc)
					WHERE opca.fk_soc_OPCA = f.fk_soc
					AND opca.fk_soc_OPCA != opca.fk_soc_trainee
					AND s2.type_session = 1
					AND opca.fk_soc_trainee = ' . $companyID . '
				)';

				break;
		}

		$sql.= '
				AND YEAR(' . $dateField . ') IN (' . implode(', ', $this->year_to_report_array) . ')

				GROUP BY YEAR(' . $dateField . '), MONTH(' . $dateField . ')
				ORDER BY YEAR(' . $dateField . ') DESC, MONTH(' . $dateField . ') DESC';

		return $sql;
	}
}

