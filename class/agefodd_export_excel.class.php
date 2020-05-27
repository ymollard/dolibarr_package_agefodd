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
if (file_exists(DOL_DOCUMENT_ROOT.'/includes/phpoffice/autoloader.php')) {
	require_once DOL_DOCUMENT_ROOT . '/includes/phpoffice/autoloader.php';
} else {
	dol_include_once('/agefodd/includes/phpoffice/autoloader.php');
}
if (file_exists(DOL_DOCUMENT_ROOT.'/includes/Psr/autoloader.php')) {
	require_once DOL_DOCUMENT_ROOT.'/includes/Psr/autoloader.php';
} else {
	dol_include_once('/agefodd/includes/Psr/autoloader.php');
}
if (file_exists(DOL_DOCUMENT_ROOT.'/includes/phpoffice/PhpSpreadsheet/Spreadsheet.php')) {
	require_once DOL_DOCUMENT_ROOT.'/includes/phpoffice/PhpSpreadsheet/Spreadsheet.php';
} else {
	dol_include_once('/includes/phpoffice/PhpSpreadsheet/Spreadsheet.php');
}

require_once DOL_DOCUMENT_ROOT. '/core/lib/date.lib.php';

/**
 * Class to build export files with Excel format
 */
class AgefoddExportExcel {
	public $id;
	public $label;
	public $extension;
	public $version;
	public $label_lib;
	public $version_lib;
	public $workbook; // Handle fichier
	public $worksheet; // Handle onglet
	public $row=array();
	public $col;
	public $file; // To save filename
	public $title;
	public $subject;
	public $description;
	public $keywords;
	public $error;
	public $outputlangs;
	protected $array_column_header = array ();
	protected $sheet_array = array ();
	public $rowheader=array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 * @param DoliDB $array_column_header array header array
	 * @param Translate $outputlangs langs
	 * @param array $sheet_array array of sheet
	 */
	public function __construct($db, $array_column_header, $outputlangs, $sheet_array=array()) {
		global $langs;

		$this->db = $db;

		$this->id = 'excel2007'; // Same value then xxx in file name export_xxx.modules.php
		$this->label = 'Excel 2007'; // Label of driver
		$this->desc = $langs->trans('Excel2007FormatDesc');
		$this->extension = 'xlsx'; // Extension for generated file by this driver
		$this->picto = 'mime/xls'; // Picto
		$this->version = '1.30'; // Driver version

		// If driver use an external library, put its name here
		$this->label_lib = 'PhpSpreadSheet';
		$this->version_lib = '1.6.0';

		$this->array_column_header = $array_column_header;

		if (count($sheet_array)>0) {
			$this->sheet_array = $sheet_array;
		} else {
			$this->sheet_array = array(0=>array('name'=>'worksheet','title'=>('WorkSheet')));
		}
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
	 * set column hearder
	 *
	 * @return string
	 */
	public function setArrayColumnHeader($array_column_header) {
		$this->array_column_header=$array_column_header;
	}


	/**
	 * set column hearder
	 *
	 * @return string
	 */
	public function getArrayColumnHeader() {
		return $this->array_column_header;
	}

	/**
	 * Open output file
	 *
	 * @param string $file to generate
	 * @return int if KO, >=0 if OK
	 */
	public function open_file($file) {
		global $user, $langs;

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

			$this->workbook = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
			$this->workbook->getProperties()->setCreator($user->getFullName($this->outputlangs) . ' - Dolibarr ' . DOL_VERSION);
			$this->workbook->getProperties()->setLastModifiedBy($user->getFullName($this->outputlangs) . ' - Dolibarr ' . DOL_VERSION);

			$this->workbook->getProperties()->setTitle($this->title);
			$this->workbook->getProperties()->setSubject($this->subject);
			$this->workbook->getProperties()->setDescription($this->description);

			$this->workbook->getProperties()->setKeywords($this->keywords);
			foreach($this->sheet_array as $keysheet=>$sheet) {
				$myWorkSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($this->workbook, $sheet['title']);
				$this->workbook->addSheet($myWorkSheet, $keysheet);

				$this->workbook->setActiveSheetIndex($keysheet);
				$this->workbook->getActiveSheet()->setTitle(dol_trunc($sheet['title'], 31, 'right', 'UTF-8', 1));
				$this->workbook->getActiveSheet()->getDefaultRowDimension()->setRowHeight(16);

				$this->row[$keysheet] = 0;
			}
			//Remove the last default one
			$this->workbook->removeSheetByIndex(count($this->sheet_array));
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
		$styleArray = array (
				'borders' => array (
						'outline' => array (
								'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
								'color' => array (
										'argb' => \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK
								)
						)
				),
				'fill' => array (
						'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_PATTERN_DARKGRAY
				),
				'font' => array (
						'color' => array (
								'argb' => \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE
						),
						'bold' => true
				),
				'alignment' => array (
						'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
				)
		);
		// Create a format for the column headings
		try {
			foreach($this->sheet_array as $keysheet=>$sheet) {
				$this->workbook->setActiveSheetIndex($keysheet);
				$this->workbook->getActiveSheet()->setCellValue('C2', $sheet['title']);
				$this->workbook->getActiveSheet()->mergeCells('C2:G2');


				$this->workbook->getActiveSheet()->getStyle('C2:G2')->applyFromArray($styleArray);
				$this->row[$keysheet] = 6;
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

		// Style for heeader tittle SpreadSheet
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

		try {
			foreach($this->sheet_array as $keysheet=>$sheet) {

				$this->workbook->setActiveSheetIndex($keysheet);
				$this->row[$keysheet] ++;

				foreach ( $this->array_column_header[$keysheet] as $col => $value ) {
					$this->workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $this->row[$keysheet], $value['title']);
					// If header is set then write header
					if (array_key_exists('header', $value)) {
						if ($upper_hearder_output != $value['header']) {
							$colstartheader = $col;
							$this->workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $this->row[$keysheet] - 1, $value['header']);
							$upper_hearder_output = $value['header'];
						}
					} else {
						if ($colstartheader > 0) {
							$range_upper_header = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colstartheader) . ($this->row[$keysheet] - 1) . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col - 1) . ($this->row[$keysheet] - 1);

							$this->workbook->getActiveSheet()->mergeCells($range_upper_header);
							$this->workbook->getActiveSheet()->getStyle($range_upper_header)->applyFromArray($styleArray);

							$upper_hearder_output = '';
							$colstartheader = - 1;
						}
					}
				}

				$min_value_key = min(array_keys($this->array_column_header[$keysheet]));
				$max_value_key = max(array_keys($this->array_column_header[$keysheet]));
				$range_header = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($min_value_key) . ($this->row[$keysheet]) . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($max_value_key) . ($this->row[$keysheet]);
				$this->rowheader[$keysheet]=$this->row[$keysheet];
				$this->workbook->getActiveSheet()->getStyle($range_header)->applyFromArray($styleArray);

				$this->row[$keysheet] ++;
			}
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
	public function write_line_total($array_subtotal = array(), $style_color = 'cfe2f3', $sheetkey=0) {
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
							'rgb' => $style_color
						),
						'endColor' => array (
							'rgb' => $style_color
						)
				),
				'font' => array (
						'color' => array (
								'argb' => \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK
						),
						'bold' => true
				),
		);
		try {
			$this->workbook->setActiveSheetIndex($sheetkey);
			if (count($array_subtotal) > 0) {
				foreach ( $array_subtotal as $col => $value ) {
					$this->workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $this->row[$sheetkey], $value);
					if ($this->array_column_header[$sheetkey][$col]['type']=='number') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row[$sheetkey])->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
					}
					if ($this->array_column_header[$sheetkey][$col]['type']=='int') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row[$sheetkey])->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
					}
					if ($this->array_column_header[$sheetkey][$col]['type']=='percent') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row[$sheetkey])->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_00);
					}
					if ($this->array_column_header[$sheetkey][$col]['type']=='amount') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row[$sheetkey])->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
					}
					if ($this->array_column_header[$sheetkey][$col]['type']=='hours') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row[$sheetkey])->getNumberFormat()->setFormatCode('[h]:mm');
					}
					if ($this->array_column_header[$sheetkey][$col]['type']=='number1') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row[$sheetkey])->getNumberFormat()->setFormatCode("0.0");
					}
					if ($this->array_column_header[$sheetkey][$col]['type']=='date') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row[$sheetkey])->getNumberFormat()->setFormatCode('dd/mm/yyyy');
					}
				}
			}
			$min_value_key = min(array_keys($this->array_column_header[$sheetkey]));
			$max_value_key = max(array_keys($this->array_column_header[$sheetkey]));
			$range_header = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($min_value_key) . ($this->row[$sheetkey]) . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($max_value_key) . ($this->row[$sheetkey]);
			$this->workbook->getActiveSheet()->getStyle($range_header)->applyFromArray($styleArray);

			$this->row[$sheetkey] ++;
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
	public function write_line($array_line = array(), $sheetkey = 0, $fill = '') {
		$styleArray = array (
				'borders' => array (
						'top' => array (
								'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
								'color' => array (
										'argb' => \PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK
								)
						)
				)
		);
		if(! empty($fill))
		{
			$styleArray['fill'] = array(
				'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
				'startColor' => array (
					'rgb' => $fill
				),
				'endColor' => array (
					'rgb' => $fill
				)
			);
		}
		try {
			$this->workbook->setActiveSheetIndex($sheetkey);
			foreach ( $array_line as $col => $value ) {
					$this->workbook->getActiveSheet()->setCellValueByColumnAndRow($col, $this->row[$sheetkey], $value);
					if ($this->array_column_header[$sheetkey][$col]['type']=='number') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row[$sheetkey])->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
					}
					if ($this->array_column_header[$sheetkey][$col]['type']=='int') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row[$sheetkey])->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
					}
					if ($this->array_column_header[$sheetkey][$col]['type']=='percent') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row[$sheetkey])->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_PERCENTAGE_00);
					}
					if ($this->array_column_header[$sheetkey][$col]['type']=='amount') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row[$sheetkey])->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_00);
					}
					if ($this->array_column_header[$sheetkey][$col]['type']=='hours') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row[$sheetkey])->getNumberFormat()->setFormatCode('[h]:mm');
					}
					if ($this->array_column_header[$sheetkey][$col]['type']=='number1') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row[$sheetkey])->getNumberFormat()->setFormatCode("0.0");
					}
					if ($this->array_column_header[$sheetkey][$col]['type']=='date') {
						$this->workbook->getActiveSheet()->getStyleByColumnAndRow($col, $this->row[$sheetkey])->getNumberFormat()->setFormatCode('dd/mm/yyyy');
					}
			}

			$min_value_key = min(array_keys($this->array_column_header[$sheetkey]));
			$max_value_key = max(array_keys($this->array_column_header[$sheetkey]));
			$range_session = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($min_value_key) . ($this->row[$sheetkey]) . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($max_value_key) . ($this->row[$sheetkey]);
			$this->workbook->getActiveSheet()->getStyle($range_session)->applyFromArray($styleArray);

			$this->row[$sheetkey] ++;

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
	public function close_file($row_header_height=0,$line_header_height=0,$freezepan=1) {
		dol_syslog(get_class($this) . "::close_file ");
		try {
			foreach($this->sheet_array as $keysheet=>$sheet) {
				$this->workbook->setActiveSheetIndex($keysheet);
				$max_value_key = max(array_keys($this->array_column_header[$keysheet]));
				$column_array=array();
				$column_array_manually_sized=array();
				for($i=1; $i <= $max_value_key; $i++) {
					//If key is not define auto size column
					if (!array_key_exists('autosize', $this->array_column_header[$keysheet][$i])) {
						$column_array[]=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
					} elseif (!empty($this->array_column_header[$keysheet][$i]['noautosize'])) {
						//If exists but not empty then add it else do not auto adjust
						$column_array[]=\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
					}

					if (array_key_exists('width', $this->array_column_header[$keysheet][$i])
							&& !empty($this->array_column_header[$keysheet][$i]['width'])) {
								$column_array_manually_sized[\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i)]=$this->array_column_header[$keysheet][$i]['width'];
					}
				}

				foreach ( $column_array as $columnID ) {
					$this->workbook->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
				}

				foreach ( $column_array_manually_sized as $columnID=>$size ) {
					$this->workbook->getActiveSheet()->getColumnDimension($columnID)->setWidth($size);
				}

				if (!empty($line_header_height)) {
					for ($i=$this->rowheader[$keysheet]; $i <= $this->row[$keysheet]; $i++) {
						$this->workbook->getActiveSheet()->getRowDimension($i)->setRowHeight($line_header_height);
					}
				}
				if (!empty($row_header_height)) {
					$this->workbook->getActiveSheet()->getRowDimension($this->rowheader[$keysheet])->setRowHeight($row_header_height);
				}

				if (!empty($freezepan)) {
					$this->workbook->getActiveSheet()->freezePaneByColumnAndRow($max_value_key, $this->rowheader[$keysheet]+1);
				}

				$this->workbook->getActiveSheet()->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
				$this->workbook->getActiveSheet()->getPageSetup()->setPrintArea('A1:'.\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($max_value_key).$this->row[$keysheet]);


				$this->workbook->getActiveSheet()->getPageMargins()->setRight(0.20);
				$this->workbook->getActiveSheet()->getPageMargins()->setLeft(0.20);
			}

			$this->workbook->setActiveSheetIndex(0);
			$objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($this->workbook, 'Xlsx');
			#$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->workbook);
			$objWriter->save($this->file);
			unset($this->workbook);
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			unset($this->workbook);
			return - 1;
		}
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
