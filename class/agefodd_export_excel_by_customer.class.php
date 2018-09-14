<?php
/* Copyright (C) 2014 Florian Henry <florian.henry@open-concept.pro>
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
 * \file /agefodd/class/agefodd_export_excel.class.php
 * \ingroup agefodd
 * \brief File of class to generate report for agefodd
 * \author Florian Henry
 */
require_once '../includes/phpexcel/Classes/PHPExcel.php';
require_once '../includes/phpexcel/Classes/PHPExcel/Style/Alignment.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

/**
 * Class to build export files with Excel format
 */
class AgefoddExportExcelByCustomer {
	var $id;
	var $label;
	var $extension;
	var $version;
	var $label_lib;
	var $version_lib;
	var $workbook; // Handle fichier
	var $worksheet; // Handle onglet
	var $row;
	var $col;
	var $file; // To save filename
	var $title;
	var $subject;
	var $description;
	var $keywords;
	private $error;
	public $outputlangs;
	protected $array_column_header = array ();
	public $rowheader;
	
	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 * @param DoliDB $array_column_header array header array
	 */
	public function __construct($db, $array_column_header, $outputlangs) {
		global $conf, $langs;
		$this->db = $db;
		
		$this->id = 'excel2007'; // Same value then xxx in file name export_xxx.modules.php
		$this->label = 'Excel 2007'; // Label of driver
		$this->desc = $langs->trans('Excel2007FormatDesc');
		$this->extension = 'xlsx'; // Extension for generated file by this driver
		$this->picto = 'mime/xls'; // Picto
		$this->version = '1.30'; // Driver version
		                         
		// If driver use an external library, put its name here
		$this->label_lib = 'PhpExcel';
		$this->version_lib = '1.8.0';
		
		$this->row = 0;
		
		$this->array_column_header = $array_column_header;
		$this->outputlangs = $outputlangs;
	}
	
	/**
	 * getDriverId
	 *
	 * @return int
	 */
	function getDriverId() {
		return $this->id;
	}
	
	/**
	 * getDriverLabel
	 *
	 * @return string driver label
	 */
	function getDriverLabel() {
		return $this->label;
	}
	
	/**
	 * getDriverDesc
	 *
	 * @return string
	 */
	function getDriverDesc() {
		return $this->desc;
	}
	
	/**
	 * getDriverExtension
	 *
	 * @return string
	 */
	function getDriverExtension() {
		return $this->extension;
	}
	
	/**
	 * getDriverVersion
	 *
	 * @return string
	 */
	function getDriverVersion() {
		return $this->version;
	}
	
	/**
	 * getLibLabel
	 *
	 * @return string
	 */
	function getLibLabel() {
		return $this->label_lib;
	}
	
	/**
	 * getLibVersion
	 *
	 * @return string
	 */
	function getLibVersion() {
		return $this->version_lib;
	}
	
	/**
	 * Open output file
	 *
	 * @param string $file to generate
	 * @return int if KO, >=0 if OK
	 */
	public function open_file($file) {
		global $user, $conf, $langs;
		
		dol_syslog(get_class($this) . "::open_file file=" . $file);
		$this->file = $file;
		
		$this->outputlangs->load("exports");
		
		// To use PCLZip
		if (! class_exists('ZipArchive')) {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorPHPNeedModule', 'zip');
			return - 1;
		}
		
		try {
			
			$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_discISAM;
			$cacheSettings = array (
					'dir' => $conf->agefodd->dir_output . '/report/' 
			);
			PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
			
			$this->workbook = new PHPExcel();
			$this->workbook->getProperties()->setCreator($user->getFullName($this->outputlangs) . ' - Dolibarr ' . DOL_VERSION);
			$this->workbook->getProperties()->setLastModifiedBy($user->getFullName($this->outputlangs) . ' - Dolibarr ' . DOL_VERSION);
			// $this->workbook->getProperties()->setLastModifiedBy('Dolibarr '.DOL_VERSION);
			
			$this->workbook->getProperties()->setTitle($this->title);
			$this->workbook->getProperties()->setSubject($this->subject);
			$this->workbook->getProperties()->setDescription($this->description);
			
			$this->workbook->getProperties()->setKeywords($this->keywords);
			
			$this->workbook->setActiveSheetIndex(0);
			$this->workbook->getActiveSheet()->setTitle(dol_trunc($this->title, 31, 'right', 'UTF-8', 1));
			//$this->workbook->getActiveSheet()->getDefaultRowDimension()->setRowHeight(16);
			$this->workbook->getActiveSheet()->getDefaultRowDimension()->setRowHeight(-1);
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			return - 1;
		}
		
		return 1;
	}
	
	/**
	 * Output title line into file
	 *
	 * @return int if KO, >0 if OK
	 */
	public function write_title() {
		dol_syslog(get_class($this) . "::write_title this->title=" . $this->title);
		// Create a format for the column headings
		try {
			$this->workbook->getActiveSheet()->setCellValue('C2', $this->title);
			$this->workbook->getActiveSheet()->mergeCells('C2:E2');
			
			$styleArray = array (
					'borders' => array (
							'outline' => array (
									'style' => PHPExcel_Style_Border::BORDER_THICK,
									'color' => array (
											'argb' => PHPExcel_Style_Color::COLOR_BLACK 
									) 
							) 
					),
					'fill' => array (
							'type' => PHPExcel_Style_Fill::FILL_PATTERN_DARKGRAY 
					),
					'font' => array (
							'color' => array (
									'argb' => PHPExcel_Style_Color::COLOR_WHITE 
							),
							'bold' => true 
					),
					'alignment' => array (
							'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER 
					) 
			);
			$this->workbook->getActiveSheet()->getStyle('C2:E2')->applyFromArray($styleArray);
			
			$this->row = 6;
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			return - 1;
		}
		
		return 1;
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
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $this->row, $str_cirteria);
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row, $str_criteria_value);
							$this->row ++;
						}
						if (array_key_exists('end', $value)) {
							$str_criteria_value = $this->outputlangs->transnoentities("AgfDateFin") . ':' . dol_print_date($value['end'],'daytext', 'tzserver', $this->outputlangs);
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $this->row, $str_cirteria);
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row, $str_criteria_value);
							$this->row ++;
						}
					} elseif ($key=='f.datef') {
						$str_cirteria = $this->outputlangs->transnoentities('InvoiceCustomer') . ' ';
						if (array_key_exists('start', $value)) {
							$str_criteria_value = $this->outputlangs->transnoentities("AgfDateDebut"). ':' . dol_print_date($value['start'],'daytext', 'tzserver', $this->outputlangs);
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $this->row, $str_cirteria);
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row, $str_criteria_value);
							$this->row ++;
						}
						if (array_key_exists('end', $value)) {
							$str_criteria_value = $this->outputlangs->transnoentities("AgfDateFin") . ':' . dol_print_date($value['end'],'daytext', 'tzserver', $this->outputlangs);
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $this->row, $str_cirteria);
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row, $str_criteria_value);
							$this->row ++;
						}
					} elseif ($key == 'so.nom') {
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $this->row, $this->outputlangs->transnoentities('Company'));
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row, $value);
						$this->row ++;
					} elseif ($key == 'so.parent|sorequester.parent') {
						require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
						$socparent = new Societe($this->db);
						$result = $socparent->fetch($value);
						if ($result < 0) {
							$this->error = $socparent->error;
							return $result;
						}
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $this->row, $this->outputlangs->transnoentities('ParentCompany'));
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row, $socparent->name);
						$this->row ++;
					} elseif ($key == 'socrequester.nom') {
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $this->row, $this->outputlangs->transnoentities('AgfTypeRequester'));
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row, $value);
						$this->row ++;
					} elseif ($key == 'sale.fk_user_com') {
						require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
						$user_salesman = new User($this->db);
						$result = $user_salesman->fetch($value);
						if ($result < 0) {
							$this->error = $user_salesman->error;
							return $result;
						}
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $this->row, $this->outputlangs->transnoentities('SalesRepresentatives'));
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row, $user_salesman->getFullName($this->outputlangs));
						$this->row ++;
					} elseif ($key == 's.type_session') {
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $this->row, $this->outputlangs->transnoentities('Type'));
						if ($value == 0) {
							$type_session = $this->outputlangs->transnoentities('AgfFormTypeSessionIntra');
						} elseif ($value == 1) {
							$type_session = $this->outputlangs->transnoentities('AgfFormTypeSessionInter');
						}
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row, $type_session);
						$this->row ++;
					}elseif ($key == 's.status') {
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(0, $this->row, $this->outputlangs->transnoentities('AgfStatusSession'));
						$session_status=array();
						$sql = "SELECT t.rowid, t.code ,t.intitule ";
						$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_status_type as t";
						$sql .= ' WHERE t.rowid IN ('.implode(',',$value).')';
						
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
						}else {
							$this->error = "Error " . $this->db->lasterror();
							dol_syslog(get_class($this) . "::write_filter " . $this->error, LOG_ERR);
							return - 1;
						}
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(1, $this->row, implode(',',$session_status));
						$this->row ++;
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
	 * Write Header of spreadheet
	 *
	 * @return int if KO, >0 if OK
	 */
	public function write_header() {
		dol_syslog(get_class($this) . "::write_header ");
		
		// Title header merge subarea is outputted (case of merge cell on line upper the header to explain kind of data)
		$upper_hearder_output = '';
		
		$this->row ++;
		
		// Style for heeader tittle SpreadSheet
		$styleArray = array (
				'borders' => array (
						'allborders' => array (
								'style' => PHPExcel_Style_Border::BORDER_THIN,
								'color' => array (
										'argb' => PHPExcel_Style_Color::COLOR_BLACK 
								) 
						) 
				),
				'fill' => array (
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array (
								'rgb' => 'cfe2f3' 
						) 
				),
				'font' => array (
						'color' => array (
								'argb' => PHPExcel_Style_Color::COLOR_BLACK 
						),
						'bold' => true 
				),
				'alignment' => array (
						'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
						//'wrap' => true
				) 
		);
		
		try {
			foreach ( $this->array_column_header as $col => $value ) {
				
				$this->workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $this->row, $value['title']);
				// If header is set then write header
				if (array_key_exists('header', $value)) {
					if ($upper_hearder_output != $value['header']) {
						$colstartheader = $col;
						//$this->workbook->getActiveSheet()->getStyle('A1:'.PHPExcel_Cell::stringFromColumnIndex($max_value_key).$this->row)->getAlignment()->setWrapText(true);
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $this->row - 1, $value['header']);
						$this->workbook->getActiveSheet()->getRowDimension()->setRowHeight(-1);
						
						$upper_hearder_output = $value['header'];
					}
				} else {
					if ($colstartheader > 0) {
						$range_upper_header = PHPExcel_Cell::stringFromColumnIndex($colstartheader) . ($this->row - 1) . ':' . PHPExcel_Cell::stringFromColumnIndex($col - 1) . ($this->row - 1);
						
						$this->workbook->getActiveSheet()->mergeCells($range_upper_header);
						$this->workbook->getActiveSheet()->getStyle($range_upper_header)->applyFromArray($styleArray);
						
						$upper_hearder_output = '';
						$colstartheader = - 1;
					}
				}
			}
			
			$min_value_key = min(array_keys($this->array_column_header));
			$max_value_key = max(array_keys($this->array_column_header));
			$range_header = PHPExcel_Cell::stringFromColumnIndex($min_value_key) . ($this->row) . ':' . PHPExcel_Cell::stringFromColumnIndex($max_value_key) . ($this->row);
			$this->rowheader=$this->row;
			$this->workbook->getActiveSheet()->getStyle($range_header)->applyFromArray($styleArray);
			
			$this->row ++;
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			return - 1;
		}
		
		return 1;
	}
	
	/**
	 * Write total line
	 *
	 * @return int if KO, >0 if OK
	 */
	public function write_line_total($array_subtotal = array(), $style_color = 'cfe2f3') {
		$styleArray = array (
				'borders' => array (
						'allborders' => array (
								'style' => PHPExcel_Style_Border::BORDER_THIN,
								'color' => array (
										'argb' => PHPExcel_Style_Color::COLOR_BLACK 
								) 
						) 
				),
				'fill' => array (
						'type' => PHPExcel_Style_Fill::FILL_SOLID,
						'color' => array (
								'rgb' => $style_color 
						) 
				),
				'font' => array (
						'color' => array (
								'argb' => PHPExcel_Style_Color::COLOR_BLACK 
						),
						'bold' => true 
				) 
		);
		try {
			if (count($array_subtotal) > 0) {
				foreach ( $array_subtotal as $col => $value ) {
					$this->workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $this->row, $value);
					if ($col!=9 && $col!=11) {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
					}
				}
			}
			$min_value_key = min(array_keys($this->array_column_header));
			$max_value_key = max(array_keys($this->array_column_header));
			$range_header = PHPExcel_Cell::stringFromColumnIndex($min_value_key) . ($this->row) . ':' . PHPExcel_Cell::stringFromColumnIndex($max_value_key) . ($this->row);
			$this->workbook->getActiveSheet()->getStyle($range_header)->applyFromArray($styleArray);
			
			$this->row ++;
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			return - 1;
		}
		
		return 1;
	}
	
	/**
	 * Write line
	 *
	 * @return int if KO, >0 if OK
	 */
	public function write_line($array_line = array()) {
		$styleArray = array (
				'borders' => array (
						'top' => array (
								'style' => PHPExcel_Style_Border::BORDER_THIN,
								'color' => array (
										'argb' => PHPExcel_Style_Color::COLOR_BLACK 
								) 
						) 
				) 
		);
		try {
			foreach ( $array_line as $col => $value ) {
				if ($col == 5) {
					// Case num dossier & participants
					$next_block_row_sta = $this->row;
					$next_block_row_conv = $this->row;
					
					if (count($value) >= 1) {
						//More than one convention per session/customer
						foreach ( $value as $convid => $numdossier ) {
							// Num dossier
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $next_block_row_conv, $numdossier);
							// trainee
							if (is_array($array_line[10][$convid]) && count($array_line[10][$convid]) > 0) {
								
								$next_block_row_sta = $next_block_row_conv;
								
								foreach ( $array_line[10][$convid] as $stagerowid=>$trainee ) {
									$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(10, $next_block_row_sta, $trainee);
									

									if (is_array($array_line[2])) {
										$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $next_block_row_sta, $array_line[2][$stagerowid]);
										/*var_dump($stagerowid);
										var_dump($array_line[2]);*/
									}
									
									
									$next_block_row_sta ++;
								}
							}
							
							// Nb Trainee
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(11, $next_block_row_conv, $array_line[11][$convid]);
							
							
							$next_block_row_conv = $next_block_row_sta;
						}
					} else {
						//Only one convention per session/customer
						// Num dossier
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $next_block_row_conv, $value[0]);
						// trainee
						if (is_array($array_line[10][0]) && count($array_line[10][0]) > 0) {
							foreach ( $array_line[10][0] as $stagerowid=>$trainee ) {
								$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(10, $next_block_row_sta, $trainee);
								
								if (is_array($array_line[2])) {
									$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(2, $next_block_row_sta, $array_line[2][0][$stagerowid]);
								}
								
								$next_block_row_sta ++;
							}
						}
						// Nb Trainee
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(11, $next_block_row_conv, $array_line[11][0]);
						$next_block_row_conv = $next_block_row_sta;
					}
					
					
					
					
				} elseif ($col == 12) {
					// Trainer
					$next_block_row_trainer = $this->row;
					
					if (is_array($value) && count($value) > 0) {
						foreach ( $value as $trainer ) {
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $next_block_row_trainer, $trainer);
							// $this->workbook->getActiveSheet()->setCellValueByColumnAndRow($col,$this->row,PHPExcel_Shared_Date::PHPToExcel($date_hoursession));
							// $this->workbook->getActiveSheet()->getStyleByColumnAndRow($col,$this->row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DATETIME);
							$next_block_row_trainer ++;
						}
					}
				} elseif ($col == 17) {
					// Invoice or propal
					$next_block_row_invoice = $this->row;
					
					if (is_array($value) && count($value) > 0) {
						foreach ( $value as $invoiceid => $refcust ) {
							// Invoice ref cust
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(16, $next_block_row_invoice, $array_line[16][$invoiceid]);
							
							// Invoice destinaries service name
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(15, $next_block_row_invoice, $array_line[15][$invoiceid]);
							
							// Invoice Ref
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(17, $next_block_row_invoice, $array_line[17][$invoiceid]);
							
							// Invoice Total HT
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(20, $next_block_row_invoice, $array_line[20][$invoiceid]);
							$this->workbook->getActiveSheet()->getStyleByColumnAndRow(20, $next_block_row_invoice)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
							
							// Invoice Total TTC
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(21, $next_block_row_invoice, $array_line[21][$invoiceid]);
							$this->workbook->getActiveSheet()->getStyleByColumnAndRow(21, $next_block_row_invoice)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
							
							// Product
							$next_block_row_product = $next_block_row_invoice;
							
							if (is_array($array_line[14][$invoiceid]) && count($array_line[14][$invoiceid]) > 0) {
								foreach ( $array_line[14][$invoiceid] as $invoicelineid => $product ) {
									// Product
									$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(14, $next_block_row_product, $product);
									
									// Product HT
									$this->workbook->getActiveSheet()->setCellValueByColumnAndRow(18, $next_block_row_product, $array_line[18][$invoiceid][$invoicelineid]);
									$this->workbook->getActiveSheet()->getStyleByColumnAndRow(18, $next_block_row_product)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
									
									$next_block_row_product ++;
									$next_block_row_invoice ++;
								}
							}
							else{
							    $next_block_row_invoice ++;
							}
						}
					}
				} elseif ($col == 19) {
					$this->workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $this->row, $value);
					$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
				}elseif ($col == 2) {
					//If company is an array the company was outputed in trainee block (5)
					if ( ! is_array($value)) {
						$this->workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $this->row, $value);
					}
				} elseif ($col != 10 && $col != 11 && $col != 14 && $col != 15 && $col != 16 && $col != 17 && $col != 18 && $col != 20 && $col != 21) {
					$this->workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $this->row, $value);
					if ($this->array_column_header[$col]['type']=='date') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
					}
				}
			}
			
			$next_row_array = array (
					$next_block_row_hour,
					$next_block_row_sta,
					$next_block_row_conv,
					$next_block_row_trainer,
					$next_block_row_product,
					$next_block_row_invoice 
			);
			$this->row = max($next_row_array);
			
			$min_value_key = min(array_keys($this->array_column_header));
			$max_value_key = max(array_keys($this->array_column_header));
			$range_session = PHPExcel_Cell::stringFromColumnIndex($min_value_key) . ($this->row) . ':' . PHPExcel_Cell::stringFromColumnIndex($max_value_key) . ($this->row);
			$this->workbook->getActiveSheet()->getStyle($range_session)->applyFromArray($styleArray);
		} catch ( Exception $e ) {
			unset($this->workbook);
			$this->error = $e->getMessage();
			return - 1;
		}
		return 1;
	}
	
	/**
	 * Close Excel file
	 *
	 * @return int if KO, >0 if OK
	 */
	public function close_file() {
		try {
			PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);
			$max_value_key = max(array_keys($this->array_column_header));
			
			
			$this->workbook->getActiveSheet()->getStyle('A1:'.PHPExcel_Cell::stringFromColumnIndex($max_value_key).$this->row)->getAlignment()->setWrapText(true);
			
			foreach ( range('A', PHPExcel_Cell::stringFromColumnIndex($max_value_key)) as $columnID ) {
				if ($columnID!='A' && $columnID!='C' && $columnID!='K' && $columnID!='M' && $columnID!='P' && $columnID!='P' && $columnID!='B' && $columnID!='G' && $columnID!='N') {
					$this->workbook->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
				} else {
					$this->workbook->getActiveSheet()->getColumnDimension($columnID)->setWidth(20);
				}
			}
			for ($i=$this->rowheader; $i <= $this->row; $i++) {
				$this->workbook->getActiveSheet()->getRowDimension($i)->setRowHeight(25);
			}
			
			$this->workbook->getActiveSheet()->freezePaneByColumnAndRow(PHPExcel_Cell::stringFromColumnIndex($max_value_key),$this->rowheader+1);
			
			$this->workbook->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
			$this->workbook->getActiveSheet()->getPageSetup()->setPrintArea('A1:'.PHPExcel_Cell::stringFromColumnIndex($max_value_key).$this->row);
			
			$this->workbook->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
			
			$this->workbook->getActiveSheet()->getPageMargins()->setRight(0.20);
			$this->workbook->getActiveSheet()->getPageMargins()->setLeft(0.20);
			
			$objWriter = PHPExcel_IOFactory::createWriter($this->workbook, 'Excel2007');
			$objWriter->save($this->file);
			unset($this->workbook);
		} catch ( Exception $e ) {
			unset($this->workbook);
			$this->error = $e->getMessage();
			return - 1;
		}
		/*As CSV
		$objWriterCSV = PHPExcel_IOFactory::createWriter($objPHPExcelFromCSV, 'CSV');
		$objWriterCSV->setExcelCompatibility(true);
		$objWriterCSV->save(str_replace('.php', '_excel.csv', __FILE__));
		*/
	}
	
	/**
	 * Clean a cell to respect rules of Excel file cells
	 *
	 * @param string $newvalue clean
	 * @return string cleaned
	 */
	function excel_clean($newvalue) {
		// Rule Dolibarr: No HTML
		$newvalue = dol_string_nohtmltag($newvalue);
		
		return $newvalue;
	}
}