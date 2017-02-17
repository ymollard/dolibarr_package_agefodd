<?php
/* Copyright (C) 2005-2009 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005	   Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2015	   Francis Appels		<francis.appels@yahoo.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file		htdocs/core/modules/barcode/doc/tcpdfbarcode.modules.php
 *		\ingroup	barcode
 *		\brief		File of class to manage barcode numbering with tcpdf library
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/barcode/modules_barcode.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/barcode/doc/tcpdfbarcode.modules.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/barcode.lib.php';	   // This is to include def like $genbarcode_loc and $font_loc

/**
 *	Class to generate barcode images using tcpdf barcode generator
 */
class modTcpdfbarcode_agefood extends modTcpdfbarcode
{
	/**
	 *	Save an image file on disk (with no output)
	 *
	 *	@param	   string	    $code		      Value to encode
	 *	@param	   string	    $encoding	      Mode of encoding
	 *	@param	   string	    $readable	      Code can be read
	 *	@param	   integer		$scale			  Scale (not used with this engine)
	 *  @param     integer      $nooutputiferror  No output if error (not used with this engine)
	 *	@return	   int			                  <0 if KO, >0 if OK
	 */
	function writeBarCode($code,$encoding,$readable='Y',$scale=1,$nooutputiferror=0,$trainingid=0)
	{
		global $conf,$_GET;

		dol_mkdir($conf->agefodd->dir_output . '/images/');
		$file=$conf->agefodd->dir_output . '/images/'.'/barcode_'.$trainingid.'_'.$encoding.'.png';

		$tcpdfEncoding = $this->getTcpdfEncodingType($encoding);
		if (empty($tcpdfEncoding)) return -1;

		$color = array(0,0,0);

		$_GET["code"]=$code;
		$_GET["type"]=$encoding;
		$_GET["height"]=$height;
		$_GET["readable"]=$readable;

		if ($code) {
			// Load the tcpdf barcode class
			if ($this->is2d) {
				$height = 1;
				$width = 1;
				require_once TCPDF_PATH.'tcpdf_barcodes_2d.php';
				$barcodeobj = new TCPDF2DBarcode($code, $tcpdfEncoding);
			} else {
				$height = 50;
				$width = 1;
				require_once TCPDF_PATH.'tcpdf_barcodes_1d.php';
				$barcodeobj = new TCPDFBarcode($code, $tcpdfEncoding);
			}

			dol_syslog("writeBarCode::TCPDF.getBarcodePngData");
			if ($imageData = $barcodeobj->getBarcodePngData($width, $height, $color)) {
				if (function_exists('imagecreate')) {
					$imageData = imagecreatefromstring($imageData);
				}
				if (imagepng($imageData, $file)) {
					return 1;
				} else {
					return -3;
				}
			} else {
				return -4;
			}
		} else {
			return -2;
		}
	}
}
