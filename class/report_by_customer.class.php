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
 * \file        /agefodd/class/report_by_customer.php
 * \ingroup agefodd
 * \brief File of class to generate report for agefodd
 */
require_once('agefodd_export_excel_by_customer.class.php');
require_once(DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');

/**
 * Class to build report by customer
 */
class ReportByCustomer extends AgefoddExportExcelByCustomer
{
	private $lines;

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

		$array_column_header = array(
			1  => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfRptSocRequester')
			),
			2  => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('Contact')
			),
			3  => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('Company')
			),
			4  => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfRptPole')
			),
			5  => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfRptTypeSess')
			),
			6  => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfNumDossier')
			),
			7  => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfLieu')
			),
			8  => array(
				'type'  => 'date',
				'title' => $outputlangs->transnoentities('AgfDateDebut')
			),
			9  => array(
				'type'  => 'date',
				'title' => $outputlangs->transnoentities('AgfDateFin')
			),
			10  => array(
				'type'  => 'hour',
				'title' => $outputlangs->transnoentities('AgfRptNbHour')
			),
			11 => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfMenuActStagiaire') . '-' . $outputlangs->transnoentities('Name'),
				//'header' => $outputlangs->transnoentities('AgfMenuActStagiaire')
			),
			12 => array(
				'type'  => 'int',
				'title' => $outputlangs->transnoentities('AgfMenuActStagiaire') . '-' . $outputlangs->transnoentities('Nb'),
				//'header' => $outputlangs->transnoentities('AgfMenuActStagiaire')
			),
			13 => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfRptIntervenant')
			),
			14 => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfRptIntituleSession')
			),
			15 => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('Product')
			),
			16 => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfRptEntityToInvoice')
			),
			17 => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfRptNumOrder')
			),
			18 => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfRptNumInvoice')
			),
			19 => array(
				'type'  => 'amount',
				'title' => $outputlangs->transnoentities('AgfRptProductHT')
			),
			20 => array(
				'type'  => 'amount',
				'title' => $outputlangs->transnoentities('AgfRptFraisHT')
			),
			21 => array(
				'type'  => 'amount',
				'title' => $outputlangs->transnoentities('AgfRptTotalHT')
			),
			22 => array(
				'type'  => 'amount',
				'title' => $outputlangs->transnoentities('AgfRptTotalTTC')
			),
			23 => array(
				'type'  => 'text',
				'title' => $outputlangs->transnoentities('AgfStatusSession')
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
	public function getSubTitlFileName($filter)
	{
		$str_sub_name = '';
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 'so.nom') {
					$str_sub_name .= $this->outputlangs->transnoentities('Company') . $value;
				} elseif ($key == 'so.parent|sorequester.parent') {
					require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
					$socparent = new Societe($this->db);
					$result = $socparent->fetch($value);
					if ($result < 0) {
						$this->error = $socparent->error;
						return $result;
					}
					$str_sub_name .= $this->outputlangs->transnoentities('ParentCompany') . $socparent->name;
				} elseif ($key == 'socrequester.nom') {
					$str_sub_name .= $this->outputlangs->transnoentities('AgfTypeRequester') . $value;
				} elseif ($key == 'sesscal.date_session') {
					$str_sub_name .= $this->outputlangs->transnoentities('AgfSessionDetail');
					if (array_key_exists('start', $value)) {
						$str_sub_name .= $this->outputlangs->transnoentities("Start") . dol_print_date($value['start'], 'dayrfc', 'tzserver', $this->outputlangs);
					}
					if (array_key_exists('end', $value)) {
						$str_sub_name .= $this->outputlangs->transnoentities("End") . dol_print_date($value['end'], 'dayrfc', 'tzserver', $this->outputlangs);
					}
				} elseif ($key == 'f.datef') {
					$str_sub_name .= $this->outputlangs->transnoentities('InvoiceCustomer');
					if (array_key_exists('start', $value)) {
						$str_sub_name .= $this->outputlangs->transnoentities("Start") . dol_print_date($value['start'], 'dayrfc', 'tzserver', $this->outputlangs);
					}
					if (array_key_exists('end', $value)) {
						$str_sub_name .= $this->outputlangs->transnoentities("End") . dol_print_date($value['end'], 'dayrfc', 'tzserver', $this->outputlangs);
					}
				} elseif ($key == 'sale.fk_user_com') {
					require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
					$user_salesman = new User($this->db);
					$result = $user_salesman->fetch($value);
					if ($result < 0) {
						$this->error = $user_salesman->error;
						return $result;
					}
					$str_sub_name .= $this->outputlangs->transnoentities('SalesRepresentatives') . $user_salesman->getFullName($this->outputlangs);
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
								while ($obj = $this->db->fetch_object($result)) {
									$session_status .= $obj->code;
								}
							}
						} else {
							$this->error = "Error " . $this->db->lasterror();
							dol_syslog(get_class($this) . "::getSubTitlFileName " . $this->error, LOG_ERR);
							return -1;
						}
					}
					$str_sub_name .= $this->outputlangs->transnoentities('AgfStatusSession') . $session_status;
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
	public function write_file($filter)
	{

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
		/*$result = $this->write_title();
		if ($result < 0) {
			return $result;
		}

		$result = $this->write_filter($filter);
		if ($result < 0) {
			return $result;
		}*/

		$result = $this->write_header();
		if ($result < 0) {
			return $result;
		}


		// Start find data
		$result = $this->fetch_all_session($filter);
		if ($result < 0) {
			return $result;
		}

		/**
		 * @var array $array_sub_total
		 *
		 * [10] = total durée session ( += $line->duree_session )
		 * [12] = nombre de participant ( count($traineelist) )
		 * [19] = total HT des lignes de facture ( += $invoice_lines->total_ht )
		 * [20] = somme des charges ( += $result => $result = $session_elem_fin_frais->get_charges_amount(...) )
		 * [21] = total HT des factures ( += $facture->total_ht )
		 * [22] = total TTC des factures ( += $facture->total_ttc )
		 */
		$array_sub_total = array();

		/** @var array $array_total
		 *
		 * [10] = total durée session
		 * [12] = total nombre de participant
		 * [19] = total HT des lignes de facture
		 * [20] = somme des charges
		 * [21] = total HT des factures
		 * [22] = total TTC des factures
		 */
		$array_total = array();

		$session = new Agsession($this->db);

		$total_line = count($this->lines);

		$requestername = false;
		if (count($this->lines) > 0) {
			$requestername = ''; // init var
			foreach ($this->lines as $line) {

				if ($requestername != $line->socrequestername) {
					$result = $this->write_line_total($array_sub_total);
					if ($result < 0) {
						return $result;
					}

					$array_total[10] += $array_sub_total[10];
					$array_total[12] += $array_sub_total[12];
					$array_total[19] += $array_sub_total[19];
					$array_total[20] += $array_sub_total[20];
					$array_total[21] += $array_sub_total[21];
					$array_total[22] += $array_sub_total[22];

					$array_sub_total = array();
				}

				// Must have same struct than $array_column_header
				$line_to_output = array();

				// Use to break on requester soc name
				$requestername = $line->socrequestername;

				// Soc reuester
				$line_to_output[1] = $line->socrequestername;

				// Contact requester
				if (!empty($line->fk_socpeople_requester)) {
					$contact = new Contact($this->db);
					$result = $contact->fetch($line->fk_socpeople_requester);
					if ($result < 0) {
						$this->error = $contact->error;
						return $result;
					}
					$line_to_output[2] = $contact->getFullName($this->outputlangs);
				} else {
					$line_to_output[2] = '';
				}

				// Thirdparty is manage in trainee

				// Pole
				$line_to_output[4] = $line->raissocial2;

				// TypeSession
				if ($line->type_session == 0) {
					$type_session = $this->outputlangs->transnoentities('AgfFormTypeSessionIntra');
				} elseif ($line->type_session == 1) {
					$type_session = $this->outputlangs->transnoentities('AgfFormTypeSessionInter');
				} else {
					$type_session = '';
				}
				$line_to_output[5] = $type_session;

				// Num dossier and trainee and nb trainee
				$numdossier = array();

				$conv = new Agefodd_convention($this->db);
				$result = $conv->fetch_all($line->id, 0, array(4, 3));
				if ($result < 0) {
					$this->error = $conv->error;
					return $result;
				}

				$OPCA_array = array();
				$OPCA_array_socid = array();
				// We test $line->socid because if customer is not specified is mostly certain taht is is a interentreprise session.
				// In this case we report all trainnees
				if (is_array($conv->lines) && count($conv->lines) > 1 && !empty($line->socid)) {
					// If we have more than one convention per customer
					foreach ($conv->lines as $convline) {
						$check_soc = false;
						$output_trainee = false;
						// If filter by soc is done we output only trainee and conv related to this soc
						if (array_key_exists('so.nom', $filter)
							|| array_key_exists('so.parent|sorequester.parent', $filter)
							|| array_key_exists('socrequester.nom', $filter)
							|| array_key_exists('sale.fk_user_com', $filter)) {
							$check_soc = true;
						}
						if ($check_soc) {
							$output_trainee_soc_nom = false;
							$output_trainee_socparent = false;
							$output_trainee_socrequester_nom = false;
							$output_trainee_salesman = false;

							if (array_key_exists('so.nom', $filter)) {
								if (strpos($line->socname, $filter['so.nom']) !== false) {
									$output_trainee_soc_nom = true;
								}
							}
							if (array_key_exists('socrequester.nom', $filter)) {
								if (strpos($line->socrequestername, $filter['socrequester.nom']) !== false) {
									$output_trainee_socrequester_nom = true;
								}
							}

							if (array_key_exists('so.parent|sorequester.parent', $filter)) {
								$socstatic = new Societe($this->db);
								$result = $socstatic->fetch($line->socid);
								if ($result < 0) {
									$this->error = '$socstatic ERROR=' . $socstatic->error;
									return $result;
								}

								if ($socstatic->parent == $filter['so.parent|sorequester.parent'] || $socstatic->id == $filter['so.parent|sorequester.parent']) {
									$output_trainee_socparent = true;
								}
							}

							if (array_key_exists('sale.fk_user_com', $filter)) {
								$sql = 'SELECT fk_user FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux WHERE fk_soc=' . $line->socid . ' AND fk_user=' . $filter['sale.fk_user_com'];
								dol_syslog(get_class($this) . "::find salesman for thirdparty sql=" . $sql, LOG_DEBUG);
								$result = $this->db->query($sql);
								if ($result) {
									if ($this->db->num_rows($result)) {
										$output_trainee_salesman = true;
									} else {
										$output_trainee_salesman = false;
									}
								} else {
									$this->error = "Error " . $this->db->lasterror();
									dol_syslog(get_class($this) . "::write_file " . $this->error, LOG_ERR);
									return -1;
								}
							}

							$output_trainee = $output_trainee_soc_nom || $output_trainee_socrequester_nom || $output_trainee_socparent || $output_trainee_salesman;
						} else {
							$output_trainee = true;
						}

						if ($output_trainee) {
							$numdossier[$convline->id] = $line->id . '_' . $line->socid . '_' . $convline->id;
							// Trainee link to the convention
							if (is_array($convline->line_trainee) && count($convline->line_trainee) > 0) {
								$stagiaires_session_conv = new Agefodd_session_stagiaire($this->db);
								foreach ($convline->line_trainee as $trainee_session_id) {
									$traineelist = array();
									$result = $stagiaires_session_conv->fetch($trainee_session_id);
									if ($result < 0) {
										$this->error = $stagiaires_session_conv->error;
										return $result;
									}
									$stagiaire_conv = new Agefodd_stagiaire($this->db);
									$result = $stagiaire_conv->fetch($stagiaires_session_conv->fk_stagiaire);
									if ($result < 0) {
										$this->error = $stagiaire_conv->error;
										return $result;
									}
									$traineelist[$trainee_session_id] = $stagiaire_conv->nom . ' ' . $stagiaire_conv->prenom;

									$sessionOPCA = new Agefodd_opca($this->db);
									$result = $sessionOPCA->getOpcaForTraineeInSession($stagiaires_session_conv->fk_soc, $line->id);
									if ($result < 0) {
										$this->error = $sessionOPCA->error;
										return $result;
									}
									$OPCA_array[$sessionOPCA->fk_soc_OPCA] = $stagiaires_session_conv->fk_soc;
									$OPCA_array_socid[$stagiaires_session_conv->fk_soc] = $sessionOPCA->fk_soc_OPCA;

									// If comapny is empty we are probably in inter-entre or false inter
									// In this case we add into company column the trainee company
									if (empty($line->socname)) {
										$line_to_output[3][$trainee_session_id] = $stagiaire_conv->socname;
									} else {
										$line_to_output[3] = $line->socname;
									}
								}
							}
						}
						$line_to_output[11][$convline->id] = $traineelist;
						$line_to_output[12][$convline->id] = is_array($traineelist)?count($traineelist):0;
						$array_sub_total[12] += is_array($traineelist)?count($traineelist):0;
					}
				} else {
					if (is_array($conv->lines) && count($conv->lines) > 1 && empty($line->socid)) {
						// To be sure that society of convention is really link to the session

						$result = $session->fetch_societe_per_session($line->id);
						if ($result < 0) {
							$this->error = $session->error;
							return $result;
						}

						foreach ($conv->lines as $convline) {
							$traineelist = array();
							$thirdparty_link_to_session = array();
							// Be sure that convention is related to soc (we don't want convention related to socrequester)
							// mostly the case when session is updated before invoiced session that comes from reseau nomad...
							if (is_array($session->lines) && count($session->lines)) {
								foreach ($session->lines as $sessionline) {
									$thirdparty_link_to_session[$sessionline->socid] = $sessionline->socid;
								}
							}

							if (array_key_exists($convline->socid, $thirdparty_link_to_session)) {
								$check_soc = false;
								$output_trainee = false;
								// If filter by soc is done we output only trainee and conv related to this soc
								if (array_key_exists('so.nom', $filter)
									|| array_key_exists('so.parent|sorequester.parent', $filter)
									|| array_key_exists('socrequester.nom', $filter)
									|| array_key_exists('sale.fk_user_com', $filter)) {
									$check_soc = true;
								}
								if ($check_soc) {

									$output_trainee_soc_nom = false;
									$output_trainee_socparent = false;
									$output_trainee_socrequester_nom = false;
									$output_trainee_salesman = false;

									$socconvstatic = new Societe($this->db);
									$result = $socconvstatic->fetch($convline->socid);
									if ($result < 0) {
										$this->error = '$socconvstatic ERROR:' . $socconvstatic->error;
										return $result;
									}

									if (array_key_exists('so.nom', $filter)) {
										if (strpos(dol_strtoupper($socconvstatic->name), dol_strtoupper($filter['so.nom'])) !== false) {
											$output_trainee_soc_nom = true;
										}
									}
									if (array_key_exists('socrequester.nom', $filter)) {
										if (strpos(dol_strtoupper($line->socrequestername), dol_strtoupper($filter['socrequester.nom'])) !== false) {
											$output_trainee_socrequester_nom = true;
										}
									}
									if (array_key_exists('so.parent|sorequester.parent', $filter)) {
										if ($socconvstatic->parent == $filter['so.parent|sorequester.parent'] || $socconvstatic->id == $filter['so.parent|sorequester.parent']) {
											$output_trainee_socparent = true;
										}
									}

									if (array_key_exists('sale.fk_user_com', $filter)) {
										$sql = 'SELECT fk_user FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux WHERE fk_soc=' . $convline->socid . ' AND fk_user=' . $filter['sale.fk_user_com'];
										dol_syslog(get_class($this) . "::find salesman for thirdparty sql=" . $sql, LOG_DEBUG);
										$result = $this->db->query($sql);
										if ($result) {
											if ($this->db->num_rows($result)) {
												$output_trainee_salesman = true;
											} else {
												$output_trainee_salesman = false;
											}
										} else {
											$this->error = "Error " . $this->db->lasterror();
											dol_syslog(get_class($this) . "::write_file " . $this->error, LOG_ERR);
											return -1;
										}
									}

									$output_trainee = $output_trainee_soc_nom || $output_trainee_socrequester_nom || $output_trainee_socparent || $output_trainee_salesman;
								} else {
									$output_trainee = true;
								}

								//var_dump($output_trainee);
								if ($output_trainee) {
									$numdossier[$convline->id] = $line->id . '_' . $convline->socid;
									// var_dump($numdossier);
									// All trainnee is linked to this convention.
									$stagiaires = new Agefodd_session_stagiaire($this->db);
									$result = $stagiaires->fetch_stagiaire_per_session($line->id, $convline->socid);
									if ($result < 0) {
										$this->error = $stagiaires->error;
										return $result;
									}
									foreach ( $stagiaires->lines as $traine_line ) {
										if ($traine_line->status_in_session == 3 || $traine_line->status_in_session == 4) {
											$traineelist[$traine_line->stagerowid] = $traine_line->nom . ' ' . $traine_line->prenom;

											// If comapny is empty we are probably in inter-entre or false inter
											// In this case we add into company column the trainee company
											if (empty($line->socname)) {
												$line_to_output[3][$traine_line->stagerowid] = $traine_line->socname;
											} else {
												$line_to_output[3] = $line->socname;
											}

											$sessionOPCA = new Agefodd_opca($this->db);
											$result = $sessionOPCA->getOpcaForTraineeInSession($traine_line->socid, $line->id);
											if ($result < 0) {
												$this->error = $sessionOPCA->error;
												return $result;
											}
											$OPCA_array[$sessionOPCA->fk_soc_OPCA] = $traine_line->socid;
											$OPCA_array_socid[$traine_line->socid] = $sessionOPCA->fk_soc_OPCA;
										}
									}
								}
							}

							$line_to_output[11][$convline->id] = $traineelist;
							$line_to_output[12][$convline->id] = count($traineelist);
							$array_sub_total[12] += count($traineelist);
						}
					} else {
						$traineelist = array ();
						$numdossier[0] = $line->id . '_' . $line->socid;
						// All trainnee is linked to this convention.
						$stagiaires = new Agefodd_session_stagiaire($this->db);
						$result = $stagiaires->fetch_stagiaire_per_session($line->id, $line->socid);
						if ($result < 0) {
							$this->error = $stagiaires->error;
							return $result;
						}
						foreach ($stagiaires->lines as $traine_line) {

							if ($traine_line->status_in_session == 3 || $traine_line->status_in_session == 4) {
								$output_trainee = false;
								// If filter by soc is done we output only trainee and conv related to this soc
								if (array_key_exists('so.nom', $filter)
										|| array_key_exists('so.parent|sorequester.parent', $filter)
										|| array_key_exists('socrequester.nom', $filter)
										|| array_key_exists('sale.fk_user_com', $filter)) {
									$check_soc = true;
								}
								if ($check_soc) {

									$output_trainee_soc_nom = false;
									$output_trainee_socparent = false;
									$output_trainee_socrequester_nom = false;
									$output_trainee_salesman= false;

									$socstatic = new Societe($this->db);
									$result = $socstatic->fetch($traine_line->socid);
									if ($result < 0) {
										$this->error = '$socstatic $traine_line ERROR='.$socstatic->error;
										return $result;
									}


										if (array_key_exists('so.nom', $filter)) {
											if (strpos(dol_strtoupper($socstatic->name), dol_strtoupper($filter['so.nom'])) !== false) {
												$output_trainee_soc_nom = true;
											}
										}
										if (array_key_exists('socrequester.nom', $filter)) {

										if (!empty($traine_line->fk_soc_requester) && $traine_line->fk_soc_requester!=-1) {
											$socstaticrequester = new Societe($this->db);
											$result = $socstaticrequester->fetch($traine_line->fk_soc_requester);
											if ($result < 0) {
												$this->error = 'socstaticrequester='.$socstaticrequester->error;
												return $result;
											}

												if (strpos(dol_strtoupper($socstaticrequester->name), dol_strtoupper($filter['socrequester.nom'])) !== false) {
													$output_trainee_socrequester_nom = true;
												}
											}

										if (strpos(dol_strtoupper($socstatic->name), dol_strtoupper($filter['socrequester.nom'])) !== false) {
											$output_trainee_socrequester_nom = true;
										}
									}
									if (array_key_exists('so.parent|sorequester.parent', $filter)) {
										if ($socstatic->parent==$filter['so.parent|sorequester.parent'] || $socstatic->id==$filter['so.parent|sorequester.parent']) {
											$output_trainee_socparent = true;
										}
									}

									if (array_key_exists('sale.fk_user_com', $filter)) {
										$traineesoc=array();
										if (!empty($traine_line->socid)) {
											$traineesoc[]=$traine_line->socid;
										}
										if (!empty($traine_line->fk_soc_requester)) {
											$traineesoc[]=$traine_line->fk_soc_requester;
										}
										if (count($traineesoc)>0){
											$sql = 'SELECT fk_user FROM '.MAIN_DB_PREFIX.'societe_commerciaux WHERE fk_soc IN ('.implode(',',$traineesoc).')';
											$sql .='  AND fk_user='.$filter['sale.fk_user_com'];
											dol_syslog(get_class($this) . "::find salesman for thirdparty output_trainee_salesman sql=" . $sql, LOG_DEBUG);
											$result = $this->db->query($sql);
											if ($result) {
												if ($this->db->num_rows($result)) {
													$output_trainee_salesman = true;
												} else {
													$output_trainee_salesman = false;
												}
											} else {
												$this->error = "Error " . $this->db->lasterror();
												dol_syslog(get_class($this) . "::write_file " . $this->error, LOG_ERR);
												return - 1;
											}
										}else {
											$output_trainee_salesman=true;
										}
									}

									$output_trainee = $output_trainee_soc_nom || $output_trainee_socrequester_nom || $output_trainee_socparent || $output_trainee_salesman;
								} else {
									$output_trainee = true;
								}

								if ($output_trainee) {
									$traineelist[$traine_line->stagerowid] = $traine_line->nom . ' ' . $traine_line->prenom;
									if (empty($line->socid)) {
										$numdossier[0] = $line->id . '_' . $traine_line->socid;
									}
									// If comapny is empty we are probably in inter-entre or false inter
									// In this case we add into company column the trainee company
									if (empty($line->socname)) {
										$line_to_output[3][$traine_line->stagerowid] = $traine_line->socname;
									} else {
										$line_to_output[3] = $line->socname;
									}

									$sessionOPCA = new Agefodd_opca($this->db);
									$result = $sessionOPCA->getOpcaForTraineeInSession($traine_line->socid, $line->id);
									if ($result < 0) {
										$this->error = $sessionOPCA->error;
										return $result;
									}
									$OPCA_array[$sessionOPCA->fk_soc_OPCA] = $traine_line->socid;
									$OPCA_array_socid[$traine_line->socid] = $sessionOPCA->fk_soc_OPCA;
								}
							}
						}

						$line_to_output[11][0] = $traineelist;
						$line_to_output[12][0] = count($traineelist);
						$array_sub_total[12] += count($traineelist);
					}
				}

				// var_dump($numdossier);
				$line_to_output[6] = $numdossier;

				// Place
				$line_to_output[7] = $line->lieucode;

				// dtDeb
				$line_to_output[8] = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(dol_mktime(12, 0, 0, dol_print_date($line->dated, '%m'), dol_print_date($line->dated, '%d'), dol_print_date($line->dated, '%Y')));

				// dtFin
				$line_to_output[9] = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(dol_mktime(12, 0, 0, dol_print_date($line->datef, '%m'), dol_print_date($line->datef, '%d'), dol_print_date($line->datef, '%Y')));

				// Nb hours
				$line_to_output[10] = $line->duree_session;
				$array_sub_total[10] += $line->duree_session;


				// Trainer
				$trainerlist = array();
				$session_trainer = new Agefodd_session_formateur($this->db);
				$result = $session_trainer->fetch_formateur_per_session($line->id);
				if ($result < 0) {
					$this->error = $session_trainer->error;
					return $result;
				}
				if (is_array($session_trainer->lines) && count($session_trainer->lines) > 0) {
					foreach ($session_trainer->lines as $trainer_lines) {
						$trainerlist[$trainer_lines->opsid] = $trainer_lines->firstname . ' ' . $trainer_lines->lastname;
					}
				}
				$line_to_output[13] = $trainerlist;

				// Session title
				if (empty($line->intitule_custo)) {
					$line_to_output[14] = $line->intitule;
				} else {
					$line_to_output[14] = $line->intitule_custo;
				}

				// Product and order and Invoice/propal and price
				$productlist = array();
				$destservlist = array();
				$refcustlist = array();
				$invoicelist = array();
				$productHTlist = array();
				$totalHTlist = array();
				$totalTTClist = array();
				$totalFraiHTlist = array();
				$invoice_found = array();
				$invoice_rejected = array();

				$result = $session->fetch($line->id);
				if ($result < 0) {
					$this->error = $session->error;
					return $result;
				}
				if (!empty($session->fk_soc_OPCA)) {
					$OPCA_array[$session->fk_soc_OPCA] = $line->socid;
					$OPCA_array_socid[$line->socid] = $session->fk_soc_OPCA;
				}

				$session_elem_fin = new Agefodd_session_element($this->db);
				$result = $session_elem_fin->fetch_element_by_session($line->id);
				if ($result < 0) {
					$this->error = $session_elem_fin->error;
					return $result;
				}
				if (is_array($session_elem_fin->lines) && count($session_elem_fin->lines) > 0) {
					foreach ($session_elem_fin->lines as $elem_line) {

						// We manage invoice first
						//var_dump
						if ($elem_line->element_type == 'invoice') {
							$facture = new Facture($this->db);
							$result = $facture->fetch($elem_line->fk_element);
							if ($result <= 0) {
								$this->error = $facture->error;
								setEventMessage('Erreur fetch facture rowid : ' . $elem_line->fk_element . ', ligne à supprimer dans la table llx_agefodd_session_element', 'errors');
								return $result;
							}

							$socinvoicestatic = new Societe($this->db);
							$result = $socinvoicestatic->fetch($facture->socid);
							if ($result < 0) {
								$this->error = $socinvoicestatic->error;
								return $result;
							}

							$check_soc = false;
							$output_invoice = false;
							// If filter by soc is done we output only trainee and conv related to this soc
							if (array_key_exists('so.nom', $filter)
								|| array_key_exists('so.parent|sorequester.parent', $filter)
								|| array_key_exists('socrequester.nom', $filter)
								|| array_key_exists('sale.fk_user_com', $filter)) {
								$check_soc = true;
							}
							if ($check_soc) {

								$output_invoice_soc_nom = false;
								$output_invoice_socparent = false;
								$output_invoice_socrequester_nom = false;
								$output_invoice_is_OPCA = false;
								$output_invoice_saleman = false;

								if (array_key_exists('so.nom', $filter)) {
									/*print '$filter[so.nom]='.$filter['so.nom'].'<br>';
									print '$socinvoicestatic->name='.$socinvoicestatic->name.'<br>';
									print '$facture->socid='.$facture->socid.'<br><BR>';
									var_dump(strpos(dol_strtoupper($socinvoicestatic->name), dol_strtoupper($filter['so.nom'])));*/
									if (strpos(dol_strtoupper($socinvoicestatic->name), dol_strtoupper($filter['so.nom'])) !== false) {
										$output_invoice_soc_nom = true;
									}
								}
								if (array_key_exists('socrequester.nom', $filter)) {
									if (strpos(dol_strtoupper($socinvoicestatic->name), dol_strtoupper($filter['socrequester.nom'])) !== false) {
										$output_invoice_socrequester_nom = true;
									}
								}
								if (array_key_exists('so.parent|sorequester.parent', $filter)) {

									if ($socinvoicestatic->parent == $filter['so.parent|sorequester.parent'] || $socinvoicestatic->id == $filter['so.parent|sorequester.parent']) {
										$output_invoice_socparent = true;
									}
								}

								if (array_key_exists($facture->socid, $OPCA_array)) {
									$output_invoice_is_OPCA = true;
								}

								if (array_key_exists('sale.fk_user_com', $filter)) {
									$sql = 'SELECT fk_user FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux WHERE fk_soc IN (' . $facture->socid . ')';
									$sql .= '  AND fk_user=' . $filter['sale.fk_user_com'];
									dol_syslog(get_class($this) . "::find salesman for thirdparty sql=" . $sql, LOG_DEBUG);
									$result = $this->db->query($sql);
									if ($result) {
										if ($this->db->num_rows($result)) {
											$output_invoice_saleman = true;
										} else {
											$output_invoice_saleman = false;
										}
									} else {
										$this->error = "Error " . $this->db->lasterror();
										dol_syslog(get_class($this) . "::write_file " . $this->error, LOG_ERR);
										return -1;
									}
								}

								$output_invoice = $output_invoice_soc_nom || $output_invoice_socrequester_nom || $output_invoice_socparent || $output_invoice_is_OPCA || $output_invoice_saleman;
							} else {
								$output_invoice = true;
							}

							//Check invoice date
							if (array_key_exists('f.datef', $filter)) {
								$output_invoice_date = false;
							} else {
								$output_invoice_date = true;
							}

							if (array_key_exists('f.datef', $filter)) {
								if ($facture->date >= $filter['f.datef']['start'] && $facture->date <= $filter['f.datef']['end']) {
									$output_invoice_date = true;
								}
							}

							$output_invoice = $output_invoice && $output_invoice_date;

							// $output_invoice=true;

							if (!empty($facture->id) && !$output_invoice) {
								$invoice_rejected[$facture->id] = $facture->socid;
							}

							if (!empty($facture->id) && $output_invoice) {
								$invoice_found[$facture->id] = $facture->socid;
								if (is_array($facture->lines) && count($facture->lines) > 0 && $facture->statut !== '0') {
									foreach ($facture->lines as $invoice_lines) {

										// Check if procut is in not in category of CHARGES
										$is_not_frais = true;
										if (!empty($invoice_lines->fk_product) && !empty($conf->global->AGF_CAT_PRODUCT_CHARGES)) {
											$sql = " SELECT prod.rowid FROM " . MAIN_DB_PREFIX . "product as prod";
											$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "categorie_product as catprod ON prod.rowid=catprod.fk_product AND catprod.fk_categorie IN (" . $conf->global->AGF_CAT_PRODUCT_CHARGES . ")";
											$sql .= " WHERE  prod.rowid=" . $invoice_lines->fk_product;
											dol_syslog(get_class($this) . "::write_file sql=" . $sql, LOG_DEBUG);
											$result = $this->db->query($sql);
											if ($result) {
												if ($this->db->num_rows($result)) {
													$is_not_frais = false;
												}
											} else {
												$this->error = '#' . __LINE__ . " Error " . $this->db->lasterror();
												dol_syslog(get_class($this) . "::write_file " . $this->error, LOG_ERR);
												return -1;
											}
										}

										if ($is_not_frais) {
											if (empty($invoice_lines->product_label)) {
												$productlist[$facture->id][$invoice_lines->rowid] = $invoice_lines->description;
											} else {
												// $productlist[$facture->id][$invoice_lines->rowid] = $invoice_lines->product_ref . '-' . $invoice_lines->product_label;
												$productlist[$facture->id][$invoice_lines->rowid] = $invoice_lines->product_label;
											}

											$productHTlist[$facture->id][$invoice_lines->rowid] = $invoice_lines->total_ht;
											$array_sub_total[19] += $invoice_lines->total_ht;
										}
									}

									$destservlist[$facture->id] = $socinvoicestatic->name;
									$refcustlist[$facture->id] = $facture->ref_client;
									$invoicelist[$facture->id] = $facture->ref;
									$totalHTlist[$facture->id] = $facture->total_ht;
									$totalTTClist[$facture->id] = $facture->total_ttc;

									$array_sub_total[21] += $facture->total_ht;
									$array_sub_total[22] += $facture->total_ttc;
								}
							}
						}
					}

					// If there is no invoice them look for proposal
					foreach ($session_elem_fin->lines as $elem_line) {
						if ($elem_line->element_type == 'propal') {
							$propal = new Propal($this->db);
							$result = $propal->fetch($elem_line->fk_element);
							if ($result < 0) {
								$this->error = $propal->error;
								return $result;
							}

							$socpropalstatic = new Societe($this->db);
							$result = $socpropalstatic->fetch($propal->socid);
							if ($result < 0) {
								$this->error = $socpropalstatic->error;
								return $result;
							}

							$check_soc = false;
							$output_propal = false;
							// If filter by soc is done we output only trainee and conv related to this soc
							if (array_key_exists('so.nom', $filter)
								|| array_key_exists('so.parent|sorequester.parent', $filter)
								|| array_key_exists('socrequester.nom', $filter)
								|| array_key_exists('sale.fk_user_com', $filter)) {
								$check_soc = true;
							}
							if ($check_soc) {

								$output_propal_soc_nom = false;
								$output_propal_socparent = false;
								$output_propal_socrequester_nom = false;
								$output_propal_is_OPCA = false;
								$output_propal_saleman = false;

								if (array_key_exists('so.nom', $filter)) {
									/*print '$filter[so.nom]='.$filter['so.nom'].'<br>';
									print '$socinvoicestatic->name='.$socpropalstatic->name.'<br>';
									print '$propal->socid='.$propal->socid.'<br><BR>';
									var_dump(strpos(dol_strtoupper($socpropalstatic->name), dol_strtoupper($filter['so.nom'])));*/
									if (strpos(dol_strtoupper($socpropalstatic->name), dol_strtoupper($filter['so.nom'])) !== false) {
										$output_propal_soc_nom = true;
									}
								}
								if (array_key_exists('socrequester.nom', $filter)) {
									if (strpos(dol_strtoupper($socpropalstatic->name), dol_strtoupper($filter['socrequester.nom'])) !== false) {
										$output_propal_socrequester_nom = true;
									}
								}
								if (array_key_exists('so.parent|sorequester.parent', $filter)) {
									if ($socpropalstatic->parent == $filter['so.parent|sorequester.parent'] || $socpropalstatic->id == $filter['so.parent|sorequester.parent']) {
										$output_propal_socparent = true;
									}
								}

								if (array_key_exists($propal->socid, $OPCA_array)) {
									$output_propal_is_OPCA = true;
								}

								if (array_key_exists('sale.fk_user_com', $filter)) {
									$sql = 'SELECT fk_user FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux WHERE fk_soc IN (' . $propal->socid . ')';
									$sql .= '  AND fk_user=' . $filter['sale.fk_user_com'];
									dol_syslog(get_class($this) . "::find salesman for thirdparty sql=" . $sql, LOG_DEBUG);
									$result = $this->db->query($sql);
									if ($result) {
										if ($this->db->num_rows($result)) {
											$output_propal_saleman = true;
										} else {
											$output_propal_saleman = false;
										}
									} else {
										$this->error = "Error " . $this->db->lasterror();
										dol_syslog(get_class($this) . "::write_file " . $this->error, LOG_ERR);
										return -1;
									}
								}

								$output_propal = $output_propal_soc_nom || $output_propal_socrequester_nom || $output_propal_socparent || $output_propal_is_OPCA || $output_propal_saleman;
							} else {
								$output_propal = true;
							}

							//If propal is not signed the reject it
							if ($propal->statut == 3) {
								$output_propal = false;
							}

							//Check if proposal was no link to invoice already outputed
							$output_propal_no_invoice_link_outputed = true;
							//print '<BR><BR><BR>$propal->ref='.$propal->ref;
							if (!empty($propal->id)) {
								$result = $propal->fetchObjectLinked($propal->id, $propal->element, '', 'facture');
								if ($result < 0) {
									$this->error = $propal->error;
									return $result;
								}
								if (is_array($propal->linkedObjects['facture']) && count($propal->linkedObjects['facture']) > 0) {
									foreach ($propal->linkedObjects['facture'] as $linked_invoice) {
										if (array_key_exists($linked_invoice->id, $invoice_found)) {
											$output_propal_no_invoice_link_outputed = false;
										}
									}
								}
							}
							//print '<BR>$output_propal_no_invoiceoutputed='.$output_propal_no_invoice_link_outputed;

							//Check if proposal was no link to invoice already outputed by an OPCA funding
							if (!empty($propal->socid) && $output_propal_no_invoice_link_outputed) {

								foreach ($OPCA_array_socid as $trainee_socid => $opca_id) {
									//print '<BR>';
									//print '$opca_id='.$opca_id;
									//print '$trainee_socid='.$trainee_socid;

									if ($trainee_socid == $propal->socid) {

										foreach ($invoice_found as $invoiceid => $invoicesocid) {
											//print '<BR>';
											//print '$invoiceid='.$invoiceid;
											//print '$invoicesocid='.$invoicesocid;

											if ($invoicesocid == $opca_id) {
												$output_propal_no_invoice_link_outputed = false;
											}
										}
									}
								}
							}

							//print '<BR>$output_propal='.$output_propal;
							//print '<BR>$output_propal_no_invoiceoutputed='.$output_propal_no_invoice_link_outputed;


							//Check if proposal was no link to invoice already outputed by an OPCA funding but if invoice was rejected no output propal as well
							if (!empty($propal->socid) && $output_propal_no_invoice_link_outputed) {

								foreach ($OPCA_array_socid as $trainee_socid => $opca_id) {
									//print '<BR>';
									//print '$opca_id='.$opca_id;
									//print '$trainee_socid='.$trainee_socid;

									if ($trainee_socid == $propal->socid) {

										foreach ($invoice_rejected as $invoiceid => $invoicesocid) {
											//print '<BR>';
											//print '$invoiceid='.$invoiceid;
											//print '$invoicesocid='.$invoicesocid;

											if ($invoicesocid == $opca_id) {
												$output_propal_no_invoice_link_outputed = false;
											}
										}
									}
								}
							}

							$output_propal = $output_propal && $output_propal_no_invoice_link_outputed;

							if (!empty($propal->id) && $output_propal) {
								if (is_array($propal->lines) && count($propal->lines) > 0) {
									foreach ($propal->lines as $propal_lines) {

										// Check if procut is in not in category of CHARGES
										$is_not_frais = true;
										if (!empty($propal_lines->fk_product)) {
											$sql = " SELECT prod.rowid FROM " . MAIN_DB_PREFIX . "product as prod";
											$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "categorie_product as catprod ON prod.rowid=catprod.fk_product ";
											if (!empty($conf->global->AGF_CAT_PRODUCT_CHARGES) && !ctype_space($conf->global->AGF_CAT_PRODUCT_CHARGES)) {
												$sql .= " AND catprod.fk_categorie IN (" . $conf->global->AGF_CAT_PRODUCT_CHARGES . ") ";
											}
											$sql .= " WHERE prod.rowid=" . $propal_lines->fk_product;
											dol_syslog(get_class($this) . "::write_file sql=" . $sql, LOG_DEBUG);
											$result = $this->db->query($sql);
											if ($result) {
												if ($this->db->num_rows($result)) {
													$is_not_frais = false;
												}
											} else {
												$this->error = '#' . __LINE__ . " Error " . $this->db->lasterror();
												dol_syslog(get_class($this) . "::write_file " . $this->error, LOG_ERR);
												return -1;
											}
										}

										if ($is_not_frais) {
											if (empty($propal_lines->product_label)) {
												$productlist[$propal->id][$propal_lines->rowid] = $propal_lines->description;
											} else {
												// $productlist[$facture->id][$invoice_lines->rowid] = $invoice_lines->product_ref . '-' . $invoice_lines->product_label;
												$productlist[$propal->id][$propal_lines->rowid] = $propal_lines->product_label;
											}

											$productHTlist[$propal->id][$propal_lines->rowid] = $propal_lines->total_ht;
											$array_sub_total[18] += $propal_lines->total_ht;
										}
									}

									$destservlist[$propal->id] = $socpropalstatic->name;
									$refcustlist[$propal->id] = $propal->ref_client;
									//$invoicelist[$propal->id] = $propal->ref;
									$invoicelist[$propal->id] = '';
									$totalHTlist[$propal->id] = $propal->total_ht;
									$totalTTClist[$propal->id] = $propal->total_ttc;

									$array_sub_total[21] += $propal->total_ht;
									$array_sub_total[22] += $propal->total_ttc;
								}
							}
						}
					}
				}
				$line_to_output[15] = $productlist;
				$line_to_output[16] = $destservlist;
				$line_to_output[17] = $refcustlist;
				$line_to_output[18] = $invoicelist;
				$line_to_output[19] = $productHTlist;
				$line_to_output[21] = $totalHTlist;
				$line_to_output[22] = $totalTTClist;

				// Total Frais HT
				//if ($invoice_found) {
				$session_elem_fin_frais = new Agefodd_session_element($this->db);
				$result = $session_elem_fin_frais->get_charges_amount($line->id, $conf->global->AGF_CAT_PRODUCT_CHARGES, 'invoice');
				if ($result < 0) {
					$this->error = $session_elem_fin_frais->error;
					return $result;
				}
				/*} else {
					$session_elem_fin_frais = new Agefodd_session_element($this->db);
					$result = $session_elem_fin_frais->get_charges_amount($line->id, $conf->global->AGF_CAT_PRODUCT_CHARGES, 'propal');
					if ($result < 0) {
						$this->error = $session_elem_fin_frais->error;
						return $result;
					}
				}*/
				$line_to_output[20] = $result;
				$array_sub_total[20] += $result;

				// Session status
				$line_to_output[23] = $line->session_status;

				// Output line into Excel File
				$this->write_line($line_to_output);
			}
			$result = $this->write_line_total($array_sub_total);
			if ($result < 0) {
				return $result;
			}

			$array_total[10] += $array_sub_total[10];
			$array_total[12] += $array_sub_total[12];
			$array_total[19] += $array_sub_total[19];
			$array_total[20] += $array_sub_total[20];
			$array_total[21] += $array_sub_total[21];
			$array_total[22] += $array_sub_total[22];
		}

		if (empty($this->avoidNotLinkedInvoices)) {
			// Start data for invoice witout training
			$this->lines = array();
			if (is_array($filter) && (array_key_exists('so.nom', $filter)
					|| array_key_exists('so.parent|sorequester.parent', $filter)
					|| array_key_exists('sale.fk_user_com', $filter)
					|| array_key_exists('f.datef', $filter))
				&& (!array_key_exists('sesscal.date_session', $filter))) {

				$result = $this->fetch_invoice_without($filter);
				if ($result < 0) {
					return $result;
				}
			}

			if (count($this->lines) > 0) {
				$total_line += count($this->lines);
				foreach ($this->lines as $line) {
					// Must have same struct than $array_column_header
					$line_to_output = array();

					$array_sub_total = array();

					$facture = new Facture($this->db);
					$result = $facture->fetch($line->id);
					if ($result < 0) {
						$this->error = $facture->error;
						return $result;
					}

					// Soc reuester
					$line_to_output[1] = $line->socrequestername;

					// contact
					$line_to_output[2] = '';

					// Societe
					$line_to_output[3] = $line->socname;

					// Pole
					$line_to_output[4] = $line->raissocial2;

					// type session
					$line_to_output[5] = '';

					// Num Dossier
					$line_to_output[6] = array();

					// Lieu
					$line_to_output[7] = '';

					// dt deb
					$line_to_output[8] = '';

					// dt fin
					$line_to_output[9] = '';

					// nb heure
					$line_to_output[10] = '';

					// Participant
					$line_to_output[11] = array();
					// Nb
					$line_to_output[12] = array(
						0 => ''
					);

					// Intervenant
					$line_to_output[13] = array();

					// Session
					$line_to_output[14] = '';

					// Product and order and Invoice/propal and price
					$productlist = array();
					$destservlist = array();
					$refcustlist = array();
					$invoicelist = array();
					$productHTlist = array();
					$totalHTlist = array();
					$totalTTClist = array();
					$totalFraiHTlist = array();

					if (is_array($facture->lines) && count($facture->lines) > 0) {
						foreach ($facture->lines as $invoice_lines) {

							// Check if procut is in not in category of CHARGES
							$is_not_frais = true;
							if (!empty($invoice_lines->fk_product)) {
								$sql = " SELECT prod.rowid FROM " . MAIN_DB_PREFIX . "product as prod";
								$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "categorie_product as catprod ON prod.rowid=catprod.fk_product AND catprod.fk_categorie IN (3,61)";
								$sql .= " WHERE  prod.rowid=" . $invoice_lines->fk_product;
								dol_syslog(get_class($this) . "::write_file sql=" . $sql, LOG_DEBUG);
								$result = $this->db->query($sql);
								if ($result) {
									if ($this->db->num_rows($result)) {
										$is_not_frais = false;
									}
								} else {
									$this->error = '#' . __LINE__ . " Error " . $this->db->lasterror();
									dol_syslog(get_class($this) . "::write_file " . $this->error, LOG_ERR);
									return -1;
								}
							}

							if ($is_not_frais) {
								if (empty($invoice_lines->product_label)) {
									$productlist[$facture->id][$invoice_lines->rowid] = $invoice_lines->description;
								} else {
									// $productlist[$facture->id][$invoice_lines->rowid] = $invoice_lines->product_ref . '-' . $invoice_lines->product_label;
									$productlist[$facture->id][$invoice_lines->rowid] = $invoice_lines->product_label;
								}

								$productHTlist[$facture->id][$invoice_lines->rowid] = $invoice_lines->total_ht;
								$array_sub_total[18] += $invoice_lines->total_ht;
							}
						}

						$destservlist[$facture->id] = '';
						$refcustlist[$facture->id] = $facture->ref_client;
						$invoicelist[$facture->id] = $facture->ref;
						$totalHTlist[$facture->id] = $facture->total_ht;
						$totalTTClist[$facture->id] = $facture->total_ttc;

						$array_sub_total[21] += $facture->total_ht;
						$array_sub_total[22] += $facture->total_ttc;

					}

					$line_to_output[15] = $productlist;
					$line_to_output[16] = $destservlist;
					$line_to_output[17] = $refcustlist;
					$line_to_output[18] = $invoicelist;
					$line_to_output[19] = $productHTlist;
					$line_to_output[21] = $totalHTlist;
					$line_to_output[22] = $totalTTClist;

					// Output line into Excel File
					$this->write_line($line_to_output);

					// $array_total[9] += $array_sub_total[9];
					// $array_total[11] += $array_sub_total[11];
					$array_total[19] += $array_sub_total[19];
					$array_total[20] += $array_sub_total[20];
					$array_total[21] += $array_sub_total[21];
					$array_total[22] += $array_sub_total[22];
				}
			}
		}

		$result = $this->write_line_total($array_total, '3d85c6');
		if ($result < 0) {
			return $result;
		}

		$this->row++;
		$result = $this->write_filter($filter);
		if ($result < 0) {
			return $result;
		}

		// exit;
		if ($total_line > 0) {
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
	function fetch_all_session($filter = array())
	{
		global $langs;

		$sql = "SELECT s.rowid";
		$sql .= " ,socrequester.nom as socrequestername";
		$sql .= " ,socrequester.rowid as socrequesterid";
		$sql .= " ,s.fk_socpeople_requester";
		$sql .= " ,so.rowid as socid";
		$sql .= " ,so.nom as socname";
		$extrafields = new ExtraFields($this->db);
		$extrafields->fetch_name_optionals_label('thirdparty');
		if (is_array($extrafields->attributes['societe']) && array_key_exists('ts_nameextra', $extrafields->attributes['societe']['type'])) {
			$sql .= " ,soextra.ts_nameextra as raissocial2";
		} else {
			$sql .= " ,so.name_alias as raissocial2";
		}
		$sql .= " ,s.type_session";
		$sql .= " ,p.ref_interne as lieucode";
		$sql .= " ,s.intitule_custo";
		$sql .= " ,s.duree_session";
		$sql .= " ,s.dated";
		$sql .= " ,s.datef";
		$sql .= " ,c.intitule";
		$sql .= " ,dictstatus.intitule as statuslib";
		$sql .= " , dictstatus.code as statuscode";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session as s";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c";
		$sql .= " ON c.rowid = s.fk_formation_catalogue";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p";
		$sql .= " ON p.rowid = s.fk_session_place";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_calendrier as sesscal";
		$sql .= " ON s.rowid = sesscal.fk_agefodd_session";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_status_type as dictstatus";
		$sql .= " ON s.status = dictstatus.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_element as sesselement";
		$sql .= " ON s.rowid = sesselement.fk_session_agefodd AND sesselement.element_type='invoice'";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture as f";
		$sql .= " ON f.rowid = sesselement.fk_element";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = s.fk_soc";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_extrafields as soextra";
		$sql .= " ON so.rowid = soextra.fk_object";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as socrequester";
		$sql .= " ON socrequester.rowid = s.fk_soc_requester";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socprequester";
		$sql .= " ON socprequester.rowid = s.fk_socpeople_requester";

		if (is_array($filter)) {
			foreach ($filter as $key => $value) {
				if (strpos($key, 'extra.') !== false) {
					$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_extrafields as extra";
					$sql .= " ON s.rowid = extra.fk_object";
					break;
				}
			}

			if (key_exists('sale.fk_user_com', $filter)) {
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sale";
				$sql .= " ON so.rowid = sale.fk_soc";
			}
		}

		$sql .= " WHERE s.entity IN (" . getEntity('agsession') . ")";

		// Manage filter
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if (($key == 's.type_session') || ($key == 'extra.ts_logistique')) {
					$sql .= ' AND ' . $key . ' = ' . $value;
				} elseif ($key == 'sale.fk_user_com') {
					$sql .= ' AND (sale.fk_user = ' . $value;
					$sql .= ' OR (s.rowid IN (SELECT DISTINCT innersess.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session as innersess';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire as inserss ON innersess.rowid = inserss.fk_session_agefodd';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire as insersta ON insersta.rowid = inserss.fk_stagiaire';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe as insersoc ON insersoc.rowid = inserss.fk_soc';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe_commerciaux as saleinnersess ON insersoc.rowid = saleinnersess.fk_soc';
					$sql .= ' WHERE saleinnersess.fk_user=' . $this->db->escape($value) . '))';
					$sql .= ')';
				} elseif ($key == 'so.parent|sorequester.parent') {
					$sql .= ' AND (';
					$sql .= '	(so.parent=' . $this->db->escape($value) . ' OR socrequester.parent=' . $this->db->escape($value);
					$sql .= ' OR so.rowid=' . $this->db->escape($value) . ' OR socrequester.rowid=' . $this->db->escape($value) . ')';
					// Parent company of trainnee into inter session
					$sql .= ' OR (  s.rowid IN (SELECT DISTINCT innersess.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session as innersess';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire as inserss ON innersess.rowid = inserss.fk_session_agefodd';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire as insersta ON insersta.rowid = inserss.fk_stagiaire';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe as insersoc ON insersoc.rowid = inserss.fk_soc';
					$sql .= ' WHERE insersoc.parent=' . $this->db->escape($value) . '))';
					// Parent company of trainnee soc requester
					$sql .= ' OR (  s.rowid IN (SELECT DISTINCT innersess.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session as innersess';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire as inserss ON innersess.rowid = inserss.fk_session_agefodd';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire as insersta ON insersta.rowid = inserss.fk_stagiaire';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe as insersoc ON insersoc.rowid = innersess.fk_soc_requester';
					$sql .= ' WHERE insersoc.parent=' . $this->db->escape($value) . '))';
					$sql .= ')';
				} elseif ($key == 'f.datef') {
					if (array_key_exists('start', $value)) {
						$sql .= ' AND ' . $key . '>=\'' . $this->db->idate($value['start']) . "'";
					}
					if (array_key_exists('end', $value)) {
						$sql .= ' AND ' . $key . '<=\'' . $this->db->idate($value['end']) . "'";
					}
				} elseif ($key == 'sesscal.date_session') {
					if (array_key_exists('start', $value)) {
						$sql .= ' AND ' . $key . '>=\'' . $this->db->idate($value['start']) . "'";
					}
					if (array_key_exists('end', $value)) {
						$sql .= ' AND ' . $key . '<=\'' . $this->db->idate($value['end']) . "'";
					}
				} elseif ($key == 'so.nom') {
					// Search for all thirdparty concern by the session
					$sql .= ' AND ((' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\') OR (s.rowid IN (SELECT innersess.rowid FROM ' . MAIN_DB_PREFIX . 'agefodd_session as innersess ';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire as inserss ON innersess.rowid = inserss.fk_session_agefodd';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire as insersta ON insersta.rowid = inserss.fk_stagiaire ';
					$sql .= ' INNER JOIN ' . MAIN_DB_PREFIX . 'societe as insersoc ON insersoc.rowid = inserss.fk_soc ';
					$sql .= ' WHERE insersoc.nom LIKE \'%' . $this->db->escape($value) . '%\' )))';
				} elseif ($key == 's.status') {
					if (is_array($value) && count($value) > 0) {
						$sql .= ' AND ' . $key . ' IN (' . implode(',', $value) . ")";
					}
				} else {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		//$sql .= ' AND s.rowid=10010';
		$sql .= ' GROUP BY s.rowid';
		$sql .= ' ORDER BY socrequester.nom,s.type_session,s.dated';
		//$sql .= ' ORDER BY socrequester.nom,s.rowid,sesscal.dated';

		dol_syslog(get_class($this) . "::fetch_all_session sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array();

			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);

					$line = new ReportByCustomerLine();

					$line->id = $obj->rowid;
					$line->socrequestername = $obj->socrequestername;
					$line->socrequesterid = $obj->socrequesterid;
					$line->fk_socpeople_requester = $obj->fk_socpeople_requester;
					$line->socname = $obj->socname;
					$line->socid = $obj->socid;
					$line->raissocial2 = $obj->raissocial2;
					$line->type_session = $obj->type_session;
					$line->lieucode = $obj->lieucode;
					$line->intitule_custo = $obj->intitule_custo;
					$line->intitule = $obj->intitule;
					$line->duree_session = $obj->duree_session;
					$line->dated = $this->db->jdate($obj->dated);
					$line->datef = $this->db->jdate($obj->datef);
					if ($obj->statuslib == $this->outputlangs->transnoentities('AgfStatusSession_' . $obj->statuscode)) {
						$label = stripslashes($obj->statuslib);
					} else {
						$label = $this->outputlangs->transnoentities('AgfStatusSession_' . $obj->statuscode);
					}
					$line->session_status = $label;

					$this->lines[$i] = $line;
					$i++;
				}
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all_session " . $this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_invoice_without($filter = array())
	{
		global $langs;

		$sql = "SELECT f.rowid";
		$sql .= " ,'' as socrequestername";
		$sql .= " ,'' as socrequesterid";
		$sql .= " ,'' as fk_socpeople_requester";
		$sql .= " ,so.rowid as socid";
		$sql .= " ,so.nom as socname";
		$extrafields = new ExtraFields($this->db);
		$extrafields->fetch_name_optionals_label('thirdparty');
		if (is_array($extrafields->attributes['societe']) && array_key_exists('ts_nameextra', $extrafields->attributes['societe']['type'])) {
			$sql .= " ,soextra.ts_nameextra as raissocial2";
		} else {
			$sql .= " ,so.name_alias as raissocial2";
		}
		$sql .= " ,'' as type_session";
		$sql .= " ,'' as lieucode";
		$sql .= " ,'' as intitule_custo";
		$sql .= " ,'' as duree_session";
		$sql .= " ,'' as dated";
		$sql .= " ,'' as datef";
		$sql .= " ,'' as intitule";
		$sql .= " ,'' as statuslib";
		$sql .= " , '' as statuscode";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "societe as so";
		$sql .= " ON so.rowid = f.fk_soc";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_extrafields as soextra";
		$sql .= " ON so.rowid = soextra.fk_object";

		if (array_key_exists('sale.fk_user_com', $filter)) {
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as salesman";
			$sql .= " ON so.rowid = salesman.fk_soc";
		}

		$sql .= " WHERE so.entity IN (" . getEntity('agsession') . ")";
		$sql .= " AND f.rowid NOT IN (SELECT DISTINCT fk_element FROM " . MAIN_DB_PREFIX . "agefodd_session_element as sesselement WHERE sesselement.element_type='invoice')";

		// Manage filter
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 'so.parent|sorequester.parent') {
					$sql .= ' AND (so.parent=' . $this->db->escape($value) . ' OR so.rowid=' . $this->db->escape($value) . ')';
				} elseif ($key == 'f.datef') {
					if (array_key_exists('start', $value)) {
						$sql .= ' AND ' . $key . '>=\'' . $this->db->idate($value['start']) . "'";
					}
					if (array_key_exists('end', $value)) {
						$sql .= ' AND ' . $key . '<=\'' . $this->db->idate($value['end']) . "'";
					}
				} elseif ($key == 'so.nom') {
					// Search for all thirdparty concern by the session
					$sql .= ' AND (' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\')';
				} elseif ($key == 'sale.fk_user_com') {
					// Search for all thirdparty concern by the session
					$sql .= ' AND (salesman.fk_user=' . $value . ')';
				}
			}
		}
		// $sql .= ' AND s.rowid=10030';
		$sql .= ' GROUP BY f.rowid';

		dol_syslog(get_class($this) . "::fetch_invoice_without sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array();

			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ($obj = $this->db->fetch_object($resql)) {

					$line = new ReportByCustomerLine();

					$line->id = $obj->rowid;
					$line->socrequestername = $obj->socrequestername;
					$line->socrequesterid = $obj->socrequesterid;
					$line->fk_socpeople_requester = $obj->fk_socpeople_requester;
					$line->socname = $obj->socname;
					$line->socid = $obj->socid;
					$line->raissocial2 = $obj->raissocial2;
					$line->type_session = $obj->type_session;
					$line->lieucode = $obj->lieucode;
					$line->intitule_custo = $obj->intitule_custo;
					$line->intitule = $obj->intitule;
					$line->duree_session = $obj->duree_session;
					$line->dated = $this->db->jdate($obj->dated);
					$line->datef = $this->db->jdate($obj->datef);
					if ($obj->statuslib == $this->outputlangs->transnoentities('AgfStatusSession_' . $obj->statuscode)) {
						$label = stripslashes($obj->statuslib);
					} else {
						$label = $this->outputlangs->transnoentities('AgfStatusSession_' . $obj->statuscode);
					}
					$line->session_status = $label;

					$this->lines[$i] = $line;
					$i++;
				}
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_invoice_without " . $this->error, LOG_ERR);
			return -1;
		}
	}
}

class ReportByCustomerLine
{
	public $id;
	public $socrequestername;
	public $socrequesterid;
	public $fk_socpeople_requester;
	public $socname;
	public $socid;
	public $raissocial2;
	public $type_session;
	public $lieucode;
	public $intitule_custo;
	public $duree_session;
	public $dated;
	public $datef;
	public $session_status;
}
