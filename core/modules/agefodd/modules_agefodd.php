<?php
/*
 * Copyright (C) 2010 Regis Houssin <regis@dolibarr.fr>
 * Copyright (C) 2012-2016 Florian Henry <florian.henry@open-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 * \file agefodd/modules/agefodd/modules_agefodd.php
 * \ingroup project
 * \brief File that contain parent class for projects models
 * and parent class for projects numbering models
 */
require_once DOL_DOCUMENT_ROOT . "/core/class/commondocgenerator.class.php";

/**
 * \class ModelePDFCommandes
 * \brief Classe mere des modeles de commandes
 */
abstract class ModelePDFAgefodd extends CommonDocGenerator
{
	public $error = '';
	public $name='';
	public $description='';
	public $type='';
	public $page_largeur;
	public $page_hauteur;
	public $format=array();
	public $marge_gauche = 0;
	public $marge_droite = 0;
	public $marge_haute = 0;
	public $marge_basse = 0;
	public $unit = 'mm';
	public $oriantation = '';
	public $espaceH_dispo = 0;
	public $milieu = 0;
	public $espaceV_dispo = 0;
	public $default_font_size=12;
	public $header_vertical_margin=0;
	public $formation_widthcol1 = 0;
	public $formation_widthcol2 = 0;
	public $formation_widthcol3 = 0;
	public $formation_widthcol4 = 0;
	public $trainer_widthcol1 = 0;
	public $trainer_widthcol2 = 0;
	public $trainer_widthtimeslot = 0;
	public $trainee_widthcol1 = 0;
	public $trainee_widthcol2 = 0;
	public $trainee_widthtimeslot = 0;
	public $nbtimeslots = 0;


	/**
	 * Return list of active generation modules
	 *
	 * @param DoliDB $db handler
	 * @param string $maxfilenamelength length of value to show
	 * @return array of templates
	 */
	static function liste_modeles($db, $maxfilenamelength = 0) {
		global $conf;

		$type = 'agefodd';
		$liste = array ();

		$liste [] = 'agefodd';

		return $liste;
	}

	/**
	 *
	 * @param string $txt
	 */
	public function getRealHeightLine($type = '') {
		global $conf;

		// Determine if jump pages is needed
		$this->pdf->startTransaction();

		$this->pdf->setPrintHeader(false);
		$this->pdf->setPrintFooter(false);
		$this->pdf->AddPage();

		// store starting values
		$start_y = $this->pdf->GetY();
		// print '$start_y='.$start_y.'<br>';

		$start_page = $this->pdf->getPage();

		$height = 0;

		// print content
		if ($type == 'head') {
			$this->_pagehead($this->pdf->ref_object, 1, $this->outputlangs);
		} elseif ($type == 'foot') {
			$height = $this->_pagefoot($this->pdf->ref_object, $this->outputlangs);
		}

		if (empty($height)) {
			// get the new Y
			$end_y = $this->pdf->GetY();
			$end_page = $this->pdf->getPage() - 1;
			// calculate height
			// print '$end_y='.$end_y.'<br>';
			// print '$end_page='.$end_page.'<br>';

			if (($end_page == $start_page || $end_page == 0) && $end_y > $start_y) {
				$height = $end_y - $start_y;
				// print 'aa$height='.$height.'<br>';
			} else {
				for($page = $start_page; $page <= $end_page; $page ++) {
					$this->pdf->setPage($page);
					// print '$page='.$page.'<br>';
					if ($page == $start_page) {
						// first page
						$height = $this->page_hauteur - $start_y - $this->marge_basse;
						// print '$height=$this->page_hauteur - $start_y - $this->marge_basse='.$this->page_hauteur .'-'. $start_y .'-'. $this->marge_basse.'='.$height.'<br>';
					} elseif ($page == $end_page) {
						// last page
						// print '$height='.$height.'<br>';
						$height += $end_y - $this->marge_haute;
						// print '$height += $end_y - $this->marge_haute='.$end_y.'-'. $this->marge_haute.'='.$height.'<br>';
					} else {
						// print '$height='.$height.'<br>';
						$height += $this->page_hauteur - $this->marge_haute - $this->marge_basse;
						// print '$height += $this->page_hauteur - $this->marge_haute - $this->marge_basse='.$this->page_hauteur .'-'. $this->marge_haute .'-'. $this->marge_basse.'='.$height.'<br>';
					}
				}
			}
		}
		$this->pdf->setPrintHeader(true);
		$this->pdf->setPrintFooter(true);

		// restore previous object
		$this->pdf = $this->pdf->rollbackTransaction();
		// print '$heightfinnal='.$height.'<br>';

		// exit;
		return $height;
	}
}

/**
 * Classe mere des modeles de numerotation des references de Agefodd
 */
abstract class ModeleNumRefAgefodd {
	var $error = '';

	/**
	 * Return if a module can be used or not
	 *
	 * @return boolean true if module can be used
	 */
	function isEnabled() {
		return true;
	}

	/**
	 * Renvoi la description par defaut du modele de numerotation
	 *
	 * @return string Texte descripif
	 */
	function info() {
		global $langs;
		$langs->load("agefodd@agefodd");
		return $langs->trans("AgfNoDescription");
	}

	/**
	 * Renvoi un exemple de numerotation
	 *
	 * @return string Example
	 */
	function getExample() {
		global $langs;
		$langs->load("agefodd@agefodd");
		return $langs->trans("AgfNoExample");
	}

	/**
	 * Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 * de conflits qui empechera cette numerotation de fonctionner.
	 *
	 * @return boolean false si conflit, true si ok
	 */
	function canBeActivated() {
		return true;
	}

	/**
	 * Renvoi prochaine valeur attribuee
	 *
	 * @param Societe $objsoc party
	 * @param Project $project
	 * @return string
	 */
	function getNextValue($objsoc, $project) {
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 * Renvoi version du module numerotation
	 *
	 * @return string Valeur
	 */
	function getVersion() {
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development')
			return $langs->trans("VersionDevelopment");
			if ($this->version == 'experimental')
				return $langs->trans("VersionExperimental");
				if ($this->version == 'dolibarr')
					return DOL_VERSION;
					return $langs->trans("NotAvailable");
	}
}

/**
 * \brief Crée un document PDF
 * \param db objet base de donnee
 * \param id can be object or rowid
 * \param modele modele à utiliser
 * \param		outputlangs		objet lang a utiliser pour traduction
 * \return int <0 if KO, >0 if OK
 */
function agf_pdf_create($db, $id, $message, $typeModele, $outputlangs, $file, $socid, $courrier = '', $path_external_model='', $id_external_model='', $obj_agefodd_convention='')
{
	global $conf, $langs;
	$langs->load('agefodd@agefodd');
	$langs->load('bills');

	// Charge le modele
	if(empty($path_external_model))
	{
		if (file_exists(dol_buildpath('/agefodd/core/modules/agefodd/pdf/pdf_' . $typeModele . '.modules.php'))) $nomModele = dol_buildpath('/agefodd/core/modules/agefodd/pdf/pdf_' . $typeModele . '.modules.php');
		else $nomModele = dol_buildpath('/agefodd/core/modules/agefodd/pdf/override/pdf_' . $typeModele . '.modules.php');
	}
	else $nomModele = dol_buildpath($path_external_model);

	if (file_exists($nomModele)) {
		require_once ($nomModele);

		$classname = "pdf_" . $typeModele;
		if(!empty($id_external_model)) $classname = 'pdf_rfltr_agefodd';

		$obj = new $classname($db);
		$obj->message = $message;

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output = $outputlangs->charset_output;

		if(empty($path_external_model)) $res_writefile = $obj->write_file($id, $outputlangs, $file, $socid, $courrier);
		elseif(!empty($id_external_model) && is_callable(array($obj,'write_file_custom_agefodd'))) {
			$res_writefile = $obj->write_file_custom_agefodd($id, $id_external_model, $outputlangs, $file, $obj_agefodd_convention, $socid);
		} else  {
			$res_writefile = $obj->write_file($id, $id_external_model, $outputlangs, $file, $obj_agefodd_convention, $socid);
		}

		if ($res_writefile > 0) {
			$outputlangs->charset_output = $sav_charset_output;
			return 1;
		} else {
			$outputlangs->charset_output = $sav_charset_output;
			setEventMessages($langs->trans('AgfPDFGenerationError'), null, 'errors');
			setEventMessages($obj->error, $obj->errors, 'errors');
			return -1;
		}
	} else {
		dol_print_error('', $langs->trans("Error") . " " . $langs->trans("ErrorFileDoesNotExists", $nomModele));
		return - 1;
	}
}
