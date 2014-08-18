<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2014   Florian Henry   <florian.henry@open-concept.pro>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file agefodd/session/cost.php
 * \ingroup agefodd
 * \brief Cost session management
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_session_formateur.class.php');
require_once ('../class/agefodd_session_formateur_calendrier.class.php');
require_once ('../class/html.formagefodd.class.php');
require_once ('../lib/agefodd.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once (DOL_DOCUMENT_ROOT . '/product/class/product.class.php');
require_once ('../class/agefodd_session_element.class.php');
require_once ('../class/agefodd_place.class.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$opsid = GETPOST('opsid', 'int');
$socid = GETPOST('socid', 'int');
$product_fourn = GETPOST('product_fourn', 'int');
$product_fourn_ref = GETPOST('search_product_fourn', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$islink = GETPOST('link_x', 'int');

$type = GETPOST('type', 'alpha');
$idelement = GETPOST('idelement', 'int');

$agf = new Agsession($db);
$result = $agf->fetch($id);
if ($result < 0) {
	setEventMessage($agf->error, 'errors');
}

/*
 * Action
 */
if ($action == 'invoice_addline') {
	
	$error = 0;
	
	$suplier_invoice = new FactureFournisseur($db);
	$suplier_invoice->fetch($idelement);
	
	// Find product description
	$prod = new Product($db);
	$result = $prod->fetch($product_fourn);
	if ($result < 0) {
		setEventMessage($prod->error, 'errors');
	}
	
	if (empty($prod->id)) {
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Product")), 'errors');
		$error ++;
	}
	
	if (! $error) {
		$result = $suplier_invoice->addline($prod->ref . ' ' . $prod->description, GETPOST('price'), GETPOST('tva_tx'), 0, 0, GETPOST('qty'), $product_fourn, 0, '', '', 0, '', 'HT', $prod->type);
		if ($result < 0) {
			setEventMessage($suplier_invoice->error, 'errors');
		}
		
		$total_ht = 0;
		$session_invoice = new Agefodd_session_element($db);
		// Update trainer cost
		$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0, 'invoice_supplier_trainer');
		if ($result < 0) {
			setEventMessage($session_invoice->error, 'errors');
		}
		if (count($session_invoice->lines) > 0) {
			foreach ( $session_invoice->lines as $line ) {
				$suplier_invoice->fetch($line->fk_element);
				
				$total_ht += $suplier_invoice->total_ht;
			}
		}
		$agf->cost_trainer = $total_ht;
		$result = $agf->update($user, 1);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		}
		
		// Update trip cost
		$total_ht = 0;
		$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0, 'invoice_supplier_missions');
		if ($result < 0) {
			setEventMessage($session_invoice->error, 'errors');
		}
		if (count($session_invoice->lines) > 0) {
			foreach ( $session_invoice->lines as $line ) {
				$suplier_invoice->fetch($line->fk_element);
				
				$total_ht += $suplier_invoice->total_ht;
			}
		}
		$agf->cost_trip = $total_ht;
		$result = $agf->update($user, 1);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		}
		
		// Update training cost
		$total_ht = 0;
		$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0, 'invoice_supplier_room');
		if ($result < 0) {
			setEventMessage($session_invoice->error, 'errors');
		}
		if (count($session_invoice->lines) > 0) {
			foreach ( $session_invoice->lines as $line ) {
				$suplier_invoice->fetch($line->fk_element);
				
				$totalht += $suplier_invoice->total_ht;
			}
		}
		$agf->cost_site = $totalht;
		$result = $agf->update($user, 1);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		}
	}
	
	header('Location:' . $_SERVER ['SELF'] . '?id=' . $id);
}

if ($action == 'invoice_supplier_trainer_confirm') {
	
	$error = 0;
	
	$suplier_invoice = new FactureFournisseur($db);
	$suplier_invoice->socid = $socid;
	$suplier_invoice->ref_supplier = $agf->formintitule . ' ' . dol_print_date(dol_now(), 'standard');
	
	// Calculate time past in session
	$trainer_calendar = new Agefoddsessionformateurcalendrier($db);
	$result = $trainer_calendar->fetch_all($opsid);
	if ($result < 0) {
		setEventMessage($trainer_calendar->error, 'errors');
	}
	$totaltime = 0;
	foreach ( $trainer_calendar->lines as $line_trainer_calendar ) {
		$totaltime += $line_trainer_calendar->heuref - $line_trainer_calendar->heured;
	}
	
	$suplier_invoice->libelle = $agf->formintitule . ' ' . dol_print_date($agf->dated, 'daytextshort') . ' ' . dol_print_date($agf->datef, 'daytextshort') . '-' . dol_print_date($totaltime, 'hourduration', 'tz');
	$suplier_invoice->date = dol_now();
	$suplier_invoice->date_echeance = dol_now();
	
	$suplier_invoice->lines [0] = ( object ) array ();
	
	// Find product description
	$prod = new Product($db);
	$result = $prod->fetch($product_fourn,$product_fourn_ref);
	if ($result < 0) {
		setEventMessage($prod->error, 'errors');
	}
	
	if (empty($prod->id)) {
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Product")), 'errors');
		$error ++;
	}
	
	if (! $error) {
		$suplier_invoice->lines [0]->description = $prod->ref . ' ' . $prod->description;
		$suplier_invoice->lines [0]->pu_ht = GETPOST('pricetrainer');
		$suplier_invoice->lines [0]->tva_tx = GETPOST('tva_tx');
		$suplier_invoice->lines [0]->qty = GETPOST('qtytrainer');
		$suplier_invoice->lines [0]->fk_product = $prod->id;
		$suplier_invoice->lines [0]->product_type = $prod->type;
		
		$result = $suplier_invoice->create($user);
		if ($result < 0) {
			setEventMessage($suplier_invoice->error, 'errors');
		} else {
			
			// Create link with the session/customer
			$session_invoice = new Agefodd_session_element($db);
			$session_invoice->fk_soc = $socid;
			$session_invoice->fk_session_agefodd = $id;
			$session_invoice->fk_element = $result;
			$session_invoice->element_type = 'invoice_supplier_trainer';
			$result = $session_invoice->create($user);
			if ($result < 0) {
				setEventMessage($session_invoice->error, 'errors');
			}
			
			// Update training cost
			$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0, 'invoice_supplier_trainer');
			if ($result < 0) {
				setEventMessage($session_invoice->error, 'errors');
			}
			if (count($session_invoice->lines) > 0) {
				$total_ht = 0;
				foreach ( $session_invoice->lines as $line ) {
					$suplier_invoice->fetch($line->fk_element);
					
					$total_ht += $suplier_invoice->total_ht;
				}
			}
			$agf->cost_trainer = $total_ht;
			$result = $agf->update($user, 1);
			if ($result < 0) {
				setEventMessage($agf->error, 'errors');
			}
			
			header('Location:' . $_SERVER ['SELF'] . '?id=' . $id);
		}
	}
} // Creation with soc and product
elseif ($action == 'invoice_supplier_missions_confirm' && empty($islink)) {
	
	$error = 0;
	
	$suplier_invoice = new FactureFournisseur($db);
	$suplier_invoice->socid = $socid;
	$suplier_invoice->ref_supplier = $agf->formintitule . ' ' . dol_print_date(dol_now(), 'standard');
	
	$suplier_invoice->libelle = $agf->formintitule . ' ' . dol_print_date($agf->dated, 'daytextshort') . ' ' . dol_print_date($agf->datef, 'daytextshort');
	$suplier_invoice->date = dol_now();
	$suplier_invoice->date_echeance = dol_now();
	
	$suplier_invoice->lines [0] = ( object ) array ();
	
	// Find product description
	$prod = new Product($db);
	$result = $prod->fetch($product_fourn,$product_fourn_ref);
	if ($result < 0) {
		setEventMessage($prod->error, 'errors');
	}
	
	if (empty($prod->id)) {
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Product")), 'errors');
		$error ++;
	}
	
	if (! $error) {
		$suplier_invoice->lines [0]->description = $prod->ref . ' ' . $prod->description;
		$suplier_invoice->lines [0]->pu_ht = GETPOST('pricemission');
		$suplier_invoice->lines [0]->tva_tx = GETPOST('tva_tx');
		$suplier_invoice->lines [0]->qty = GETPOST('qtymission');
		$suplier_invoice->lines [0]->fk_product = $prod->id;
		$suplier_invoice->lines [0]->product_type = $prod->type;
		
		$result = $suplier_invoice->create($user);
		if ($result < 0) {
			setEventMessage($suplier_invoice->error, 'errors');
		} else {
			
			// Create link with the session/customer
			$session_invoice = new Agefodd_session_element($db);
			$session_invoice->fk_soc = $socid;
			$session_invoice->fk_session_agefodd = $id;
			$session_invoice->fk_element = $result;
			$session_invoice->element_type = 'invoice_supplier_missions';
			$result = $session_invoice->create($user);
			if ($result < 0) {
				setEventMessage($session_invoice->error, 'errors');
			}
			
			// Update training cost
			$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0, 'invoice_supplier_missions');
			if ($result < 0) {
				setEventMessage($session_invoice->error, 'errors');
			}
			if (count($session_invoice->lines) > 0) {
				$total_ht = 0;
				foreach ( $session_invoice->lines as $line ) {
					$suplier_invoice->fetch($line->fk_element);
					
					$total_ht += $suplier_invoice->total_ht;
				}
			}
			$agf->cost_trip = $total_ht;
			$result = $agf->update($user, 1);
			if ($result < 0) {
				setEventMessage($agf->error, 'errors');
			}
			
			if (! empty($conf->global->AGF_AUTO_ACT_ADMIN_UPD)) {
				dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
				$admintask = new Agefodd_sessadm($db);
				$result = $admintask->updateByTriggerName($user, $id, 'AGF_MOV_ORGANISE');
				if ($result < 0) {
					setEventMessage($admintask->error, 'errors');
				}
			}
			
			header('Location:' . $_SERVER ['SELF'] . '?id=' . $id);
		}
	}
} elseif ($action == 'invoice_supplier_place_confirm') {
	
	$error = 0;
	
	$suplier_invoice = new FactureFournisseur($db);
	$suplier_invoice->socid = $socid;
	$suplier_invoice->ref_supplier = $agf->formintitule . ' ' . dol_print_date(dol_now(), 'standard');
	
	$suplier_invoice->libelle = $agf->formintitule . ' ' . dol_print_date($agf->dated, 'daytextshort') . ' ' . dol_print_date($agf->datef, 'daytextshort');
	$suplier_invoice->date = dol_now();
	$suplier_invoice->date_echeance = dol_now();
	
	$suplier_invoice->lines [0] = ( object ) array ();
	
	// Find product description
	$prod = new Product($db);
	$result = $prod->fetch($product_fourn,$product_fourn_ref);
	if ($result < 0) {
		setEventMessage($prod->error, 'errors');
	}
	
	if (empty($prod->id)) {
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Product")), 'errors');
		$error ++;
	}
	
	if (! $error) {
		$suplier_invoice->lines [0]->description = $prod->ref . ' ' . $prod->description;
		$suplier_invoice->lines [0]->pu_ht = GETPOST('priceroom');
		$suplier_invoice->lines [0]->tva_tx = GETPOST('tva_tx');
		$suplier_invoice->lines [0]->qty = GETPOST('qtyroom');
		$suplier_invoice->lines [0]->fk_product = $prod->id;
		$suplier_invoice->lines [0]->product_type = $prod->type;
		
		$result = $suplier_invoice->create($user);
		if ($result < 0) {
			setEventMessage($suplier_invoice->error, 'errors');
		} else {
			
			// Create link with the session/customer
			$session_invoice = new Agefodd_session_element($db);
			$session_invoice->fk_soc = $socid;
			$session_invoice->fk_session_agefodd = $id;
			$session_invoice->fk_element = $result;
			$session_invoice->element_type = 'invoice_supplier_room';
			$result = $session_invoice->create($user);
			if ($result < 0) {
				setEventMessage($session_invoice->error, 'errors');
			}
			
			// Update training cost
			$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0, 'invoice_supplier_room');
			if ($result < 0) {
				setEventMessage($session_invoice->error, 'errors');
			}
			if (count($session_invoice->lines) > 0) {
				$totalht = 0;
				foreach ( $session_invoice->lines as $line ) {
					$suplier_invoice->fetch($line->fk_element);
					
					$totalht += $suplier_invoice->total_ht;
				}
			}
			$agf->cost_site = $totalht;
			$result = $agf->update($user, 1);
			if ($result < 0) {
				setEventMessage($agf->error, 'errors');
			}
			
			header('Location:' . $_SERVER ['SELF'] . '?id=' . $id);
		}
	}
} elseif ($action == 'unlink_confirm' && $confirm == 'yes' && $user->rights->agefodd->creer) {
	$agf_fin = new Agefodd_session_element($db);
	$result = $agf_fin->fetch($idelement);
	if ($result < 0) {
		setEventMessage($agf_fin->error, 'errors');
	}
	
	$deleteobject = GETPOST('deleteobject', 'int');
	if (! empty($deleteobject)) {
		$obj_link = new FactureFournisseur($db);
		$obj_link->fetch($agf_fin->fk_element);
		$resultdel = $obj_link->delete($agf_fin->fk_element);
		
		if ($resultdel < O) {
			setEventMessage($obj_link->error, 'errors');
		}
	}
	
	// If exists we update
	if ($agf_fin->id) {
		$result2 = $agf_fin->delete($user);
	}
	if ($result2 > 0) {
		
		// Update training cost
		$result = $agf_fin->fetch_by_session_by_thirdparty($id, 0);
		if ($result < 0) {
			setEventMessage($agf_fin->error, 'errors');
		}
		
		if (count($agf_fin->lines) > 0) {
			$suplier_invoice = new FactureFournisseur($db);
			$totalhttrainer = 0;
			$totalhtroom = 0;
			$totalhtmission = 0;
			foreach ( $agf_fin->lines as $line ) {
				$suplier_invoice->fetch($line->fk_element);
				if ($line->element_type == 'invoice_supplier_trainer') {
					$totalhttrainer += $suplier_invoice->total_ht;
				}
				if ($line->element_type == 'invoice_supplier_room') {
					$totalhtroom += $suplier_invoice->total_ht;
				}
				if ($line->element_type == 'invoice_supplier_missions') {
					$totalhtmission += $suplier_invoice->total_ht;
				}
			}
		}
		$agf->cost_trainer = $totalhttrainer;
		$agf->cost_site = $totalhtroom;
		$agf->cost_trip = $totalhtmission;
		$result = $agf->update($user, 1);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		}
		
		Header('Location: ' . $_SERVER ['PHP_SELF'] . '?id=' . $id);
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
} elseif ($action == 'link_confirm' && $confirm == 'yes' && $user->rights->agefodd->creer) {
	
	$invoiceselected = GETPOST('invoiceselected', 'int');
	if (! empty($invoiceselected)) {
		$session_invoice = new Agefodd_session_element($db);
		$session_invoice->fk_soc = $socid;
		$session_invoice->fk_session_agefodd = $id;
		$session_invoice->fk_element = $invoiceselected;
		$session_invoice->element_type = $type;
		
		$result = $session_invoice->create($user);
		
		if ($result < O) {
			setEventMessage($obj_link->error, 'errors');
		} else {
			// Update training cost
			$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0);
			if ($result < 0) {
				setEventMessage($session_invoice->error, 'errors');
			}
			
			if (count($session_invoice->lines) > 0) {
				$suplier_invoice = new FactureFournisseur($db);
				$totalhttrainer = 0;
				$totalhtroom = 0;
				$totalhtmission = 0;
				foreach ( $session_invoice->lines as $line ) {
					$suplier_invoice->fetch($line->fk_element);
					if ($line->element_type == 'invoice_supplier_trainer') {
						$totalhttrainer += $suplier_invoice->total_ht;
					}
					if ($line->element_type == 'invoice_supplier_room') {
						$totalhtroom += $suplier_invoice->total_ht;
					}
					if ($line->element_type == 'invoice_supplier_missions') {
						$totalhtmission += $suplier_invoice->total_ht;
					}
				}
			}
			$agf->cost_trainer = $totalhttrainer;
			$agf->cost_site = $totalhtroom;
			$agf->cost_trip = $totalhtmission;
			$result = $agf->update($user, 1);
			if ($result < 0) {
				setEventMessage($agf->error, 'errors');
			}
		}
	}
}

/*
 * View
*/

llxHeader('', $langs->trans("AgfCostManagement"));

$head = session_prepare_head($agf);

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

if ($conf->global->AGF_NEW_BROWSER_WINDOWS_ON_LINK) {
	$target = ' target="_blanck" ';
} else {
	$target = '';
}

dol_fiche_head($head, 'cost', $langs->trans("AgfCostManagement"), 0, 'bill');

/*
 * Confirm unlink
*/
if ($action == 'unlink') {
	
	$agf_liste = new Agefodd_session_element($db);
	$result = $agf_liste->fetch($idelement);
	$ref = $agf_liste->facfournnumber;
	
	if (! empty($agf_liste->id)) {
		$form_question = array ();
		$form_question [] = array (
				'label' => $langs->trans("AgfDeleteObjectAlso", $ref),
				'type' => 'radio',
				'values' => array (
						'0' => $langs->trans('No'),
						'1' => $langs->trans('Yes') 
				),
				'name' => 'deleteobject' 
		);
	}
	$ret = $form->form_confirm($_SERVER ['PHP_SELF'] . '?socid=' . $socid . '&id=' . $id . '&idelement=' . $idelement, $langs->trans("AgfConfirmUnlink"), '', "unlink_confirm", $form_question, '', 1);
	if ($ret == 'html')
		print '<br>';
}

/*
 * Confirm select invoice supplier link
*/
if ($action == 'link' || ($action == 'invoice_supplier_missions_confirm' && ! empty($islink))) {
	
	if (! empty($islink)) {
		$socid = GETPOST('socidlink', 'int');
	}
	
	$agf_liste = new Agefodd_session_element($db);
	$result = $agf_liste->fetch_invoice_supplier_by_thridparty($socid);
	if ($result < 0) {
		setEventMessage($agf_liste->error, 'errors');
	}
	
	$invoice_array = array ();
	foreach ( $agf_liste->lines as $line ) {
		$invoice_array [$line->id] = $line->ref . ' ' . $line->ref_supplier;
	}
	
	$form_question = array ();
	$form_question [] = array (
			'label' => $langs->trans("AgfFactureSelectInvoice"),
			'type' => 'select',
			'values' => $invoice_array,
			'name' => 'invoiceselected' 
	);
	
	$ret = $form->form_confirm($_SERVER ['PHP_SELF'] . '?socid=' . $socid . '&id=' . $id . '&type=' . $type, $langs->trans("AgfFactureSelectInvoice"), '', "link_confirm", $form_question, '', 1);
	if ($ret == 'html')
		print '<br>';
}

print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
print '</div>';

// Print session card
$agf->printSessionInfo();

/*
 * Trainer cost management
 */
print_fiche_titre($langs->trans('AgfFormateur'));

$agf_formateurs = new Agefodd_session_formateur($db);
$agf_fin = new Agefodd_session_element($db);

$nbform = $agf_formateurs->fetch_formateur_per_session($agf->id);
if ($result < 0) {
	setEventMessage($agf_formateurs->error, 'errors');
}

print '<form method="POST" action="' . $_SERVER ['PHP_SELF'] . '" name="costmanamgent">';
print '<table class="border" width="100%">';
foreach ( $agf_formateurs->lines as $line ) {
	print '<tr>';
	
	print '<td width="20%" valign="top">';
	// Trainers info
	print '<a href="' . DOL_URL_ROOT . '/contact/fiche.php?id=' . $line->socpeopleid . '" ' . $target . '>';
	print img_object($langs->trans("ShowContact"), "contact") . ' ';
	print strtoupper($line->lastname) . ' ' . ucfirst($line->firstname) . '</a>';
	print '&nbsp;';
	print $line->getLibStatut(2);
	print '&nbsp;';
	
	// Calculate time past in session
	$trainer_calendar = new Agefoddsessionformateurcalendrier($db);
	$result = $trainer_calendar->fetch_all($line->opsid);
	if ($result < 0) {
		setEventMessage($trainer_calendar->error, 'errors');
	}
	$totaltime = 0;
	foreach ( $trainer_calendar->lines as $line_trainer_calendar ) {
		$totaltime += $line_trainer_calendar->heuref - $line_trainer_calendar->heured;
	}
	
	print '(' . dol_print_date($totaltime, 'hourduration', 'tz') . ')';
	
	print '</td>';
	
	// If contact is a contact of a supllier
	if (! empty($line->socpeopleid)) {
		
		$contact_static = new Contact($db);
		$contact_static->fetch($line->socpeopleid);
		$contact_static->fetch_thirdparty();
		
		// If contact is a contact of a supllier
		if ($contact_static->thirdparty->fournisseur == 1) {
			
			// Get all document lines
			$agf_fin->fetch_by_session_by_thirdparty($id, $contact_static->thirdparty->id, 'invoice_supplier_trainer');
			
			if (count($agf_fin->lines) > 0) {
				
				print '<td>';
				
				foreach ( $agf_fin->lines as $line_fin ) {
					
					if ($action == 'addline' && $idelement == $line_fin->id) {
						
						$suplier_invoice = new FactureFournisseur($db);
						$suplier_invoice->fetch($line_fin->fk_element);
						
						print '<input type="hidden" name="action" value="invoice_addline">';
						print '<input type="hidden" name="id" value="' . $id . '">';
						print '<input type="hidden" name="idelement" value="' . $line_fin->fk_element . '">';
						print '<table class="nobordernopadding"><tr>';
						
						print '<td nowrap="nowrap">';
						// print $langs->trans('AgfSelectFournProduct');
						print $suplier_invoice->getLibStatut(2) . ' ' . $suplier_invoice->getNomUrl(1, '', 0, $conf->global->AGF_NEW_BROWSER_WINDOWS_ON_LINK) . ' (' . price($suplier_invoice->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
						print $formAgefodd->select_produits_fournisseurs_agefodd($contact_static->thirdparty->id, $product_fourn, 'product_fourn');
						print '</td>';
						
						print '<td align="left" style="padding-left:10px">';
						print $langs->trans('PriceUHT') . '<input type="text" class="flat" size="4" name="price" value="' . GETPOST('pricetrainer') . '">' . $langs->getCurrencySymbol($conf->currency);
						print '</td>';
						
						print '<td  align="left" style="padding-left:10px">';
						print $form->load_tva('tva_tx', (GETPOST('tva_tx') ? GETPOST('tva_tx') : - 1));
						print '</td>';
						
						print '<td  align="left" style="padding-left:10px">';
						print $langs->trans('Qty') . '<input type="text" class="flat" size="2" name="qty" value="' . GETPOST('qtytrainer') . '">';
						print '</td>';
						
						print '<td align="left" style="padding-left:10px">';
						print '<input type="submit" class="butAction" name="invoice_supplier_trainer_add" value="' . $langs->trans("AgfFactureAddLineSuplierInvoice") . '">';
						print '</td></tr></table>';
					} else {
						$suplier_invoice = new FactureFournisseur($db);
						$suplier_invoice->fetch($line_fin->fk_element);
						print '<table class="nobordernopadding">';
						print '<tr>';
						// Supplier Invoice inforamtion
						print '<td nowrap="nowrap">';
						print $suplier_invoice->getLibStatut(2) . ' ' . $suplier_invoice->getNomUrl(1, '', 0, $conf->global->AGF_NEW_BROWSER_WINDOWS_ON_LINK) . ' (' . price($suplier_invoice->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
						print '</td>';
						print '<td>';
						// Ad invoice line
						$legende = $langs->trans("AgfFactureAddLineSuplierInvoice");
						print '<a href="' . $_SERVER ['PHP_SELF'] . '?action=addline&idelement=' . $line_fin->id . '&id=' . $id . '&socid=' . $contact_static->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
						print img_picto($legende, 'edit_add', 'align="absmiddle"') . '</a>';
						// Unlink order
						$legende = $langs->trans("AgfFactureUnselectSuplierInvoice");
						print '<a href="' . $_SERVER ['PHP_SELF'] . '?action=unlink&idelement=' . $line_fin->id . '&id=' . $id . '&socid=' . $contact_static->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
						print '<img src="' . dol_buildpath('/agefodd/img/unlink.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
						
						print '</td>';
						print '</tr>';
						print '</table>';
					}
				}
				print '</td>';
			}
			
			print '<td>';
			
			// Create new supplier invoice
			if ($action == 'createinvoice_supplier' && $opsid == $line->opsid) {
				
				print '<input type="hidden" name="action" value="invoice_supplier_trainer_confirm">';
				print '<input type="hidden" name="opsid" value="' . $line->opsid . '">';
				print '<input type="hidden" name="id" value="' . $id . '">';
				print '<input type="hidden" name="socid" value="' . $contact_static->thirdparty->id . '">';
				print '<table class="nobordernopadding"><tr>';
				
				print '<td nowrap="nowrap">';
				// print $langs->trans('AgfSelectFournProduct');
				print $formAgefodd->select_produits_fournisseurs_agefodd($contact_static->thirdparty->id, $product_fourn, 'product_fourn');
				print '</td>';
				
				print '<td align="left" style="padding-left:10px">';
				print $langs->trans('PriceUHT') . '<input type="text" class="flat" size="4" name="pricetrainer" value="' . GETPOST('pricetrainer') . '">' . $langs->getCurrencySymbol($conf->currency);
				print '</td>';
				
				print '<td  align="left" style="padding-left:10px">';
				print $form->load_tva('tva_tx', (GETPOST('tva_tx') ? GETPOST('tva_tx') : - 1));
				print '</td>';
				
				print '<td  align="left" style="padding-left:10px">';
				print $langs->trans('Qty') . '<input type="text" class="flat" size="2" name="qtytrainer" value="' . GETPOST('qtytrainer') . '">';
				print '</td>';
				
				print '<td align="left" style="padding-left:10px">';
				print '<input type="submit" class="butAction" name="invoice_supplier_trainer_add" value="' . $langs->trans("AgfCreateSupplierInvoice") . '">';
				print '</td></tr></table>';
			} elseif ($user->rights->agefodd->modifier) {
				$legende = $langs->trans("AgfCreateSupplierInvoice");
				print '<a href="' . $_SERVER ['PHP_SELF'] . '?action=createinvoice_supplier&opsid=' . $line->opsid . '&id=' . $id . '" alt="' . $legende . '" title="' . $legende . '">';
				print '<img src="' . dol_buildpath('/agefodd/img/new.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
				
				$legende = $langs->trans("AgfFactureSelectInvoice");
				print '<a href="' . dol_buildpath('/agefodd/session/cost.php', 1) . '?action=link&type=invoice_supplier_trainer&id=' . $id . '&opsid=' . $line->opsid . '&socid=' . $contact_static->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
				print '<img src="' . dol_buildpath('/agefodd/img/link.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
			}
			
			print '</td>';
		} else {
			print '<td>' . $langs->trans('AgfTrainerNotAContactOfSupplier') . '</td>';
		}
	} else {
		print '<td>' . $langs->trans('AgfTrainerNotAContactOfSupplier') . '</td>';
	}
}

print '</table>';

print '<br>';

/*
 * Trip And Missions cost management
*/
print_fiche_titre($langs->trans('AgfTripAndMissions'));

$agf_fin = new Agefodd_session_element($db);
// Get all document lines
$result = $agf_fin->fetch_by_session_by_thirdparty($id, 0, 'invoice_supplier_missions');
if ($result < 0) {
	setEventMessage($agf_fin->error, 'errors');
}

print '<table class="border" width="100%">';
foreach ( $agf_fin->lines as $line_fin ) {
	print '<tr>';
	
	print '<td width="20%" valign="top">';
	// Societe Info
	$soc_missions = new Societe($db);
	$result = $soc_missions->fetch($line_fin->fk_soc);
	if ($result < 0) {
		setEventMessage($soc_missions->error, 'errors');
	}
	
	print $soc_missions->getNomUrl(1);
	print '&nbsp;';
	print $soc_missions->getLibStatut(2);
	print '&nbsp;';
	
	print '</td>';
	
	// If contact is a contact of a supllier
	if ($soc_missions->fournisseur == 1) {
		
		if (count($agf_fin->lines) > 0) {
			print '<td>';
			if ($action == 'addline' && $idelement == $line_fin->id) {
				
				$suplier_invoice = new FactureFournisseur($db);
				$suplier_invoice->fetch($line_fin->fk_element);
				
				print '<input type="hidden" name="action" value="invoice_addline">';
				print '<input type="hidden" name="id" value="' . $id . '">';
				print '<input type="hidden" name="idelement" value="' . $line_fin->fk_element . '">';
				print '<table class="nobordernopadding"><tr>';
				
				print '<td nowrap="nowrap">';
				// print $langs->trans('AgfSelectFournProduct');
				print $suplier_invoice->getLibStatut(2) . ' ' . $suplier_invoice->getNomUrl(1, '', 0, $conf->global->AGF_NEW_BROWSER_WINDOWS_ON_LINK) . ' (' . price($suplier_invoice->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
				print $formAgefodd->select_produits_fournisseurs_agefodd($contact_static->thirdparty->id, $product_fourn, 'product_fourn');
				print '</td>';
				
				print '<td align="left" style="padding-left:10px">';
				print $langs->trans('PriceUHT') . '<input type="text" class="flat" size="4" name="price" value="' . GETPOST('pricetrainer') . '">' . $langs->getCurrencySymbol($conf->currency);
				print '</td>';
				
				print '<td  align="left" style="padding-left:10px">';
				print $form->load_tva('tva_tx', (GETPOST('tva_tx') ? GETPOST('tva_tx') : - 1));
				print '</td>';
				
				print '<td  align="left" style="padding-left:10px">';
				print $langs->trans('Qty') . '<input type="text" class="flat" size="2" name="qty" value="' . GETPOST('qtytrainer') . '">';
				print '</td>';
				
				print '<td align="left" style="padding-left:10px">';
				print '<input type="submit" class="butAction" name="invoice_supplier_trainer_add" value="' . $langs->trans("AgfFactureAddLineSuplierInvoice") . '">';
				print '</td></tr></table>';
			} else {
				$suplier_invoice = new FactureFournisseur($db);
				$suplier_invoice->fetch($line_fin->fk_element);
				print '<table class="nobordernopadding">';
				print '<tr>';
				// Supplier Invoice inforamtion
				print '<td nowrap="nowrap">';
				print $suplier_invoice->getLibStatut(2) . ' ' . $suplier_invoice->getNomUrl(1, '', 0, $conf->global->AGF_NEW_BROWSER_WINDOWS_ON_LINK) . ' (' . price($suplier_invoice->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
				print '</td>';
				print '<td>';
				$legende = $langs->trans("AgfFactureAddLineSuplierInvoice");
				print '<a href="' . $_SERVER ['PHP_SELF'] . '?action=addline&idelement=' . $line_fin->id . '&id=' . $id . '&socid=' . $contact_static->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
				print img_picto($legende, 'edit_add', 'align="absmiddle"') . '</a>';
				// Unlink order
				$legende = $langs->trans("AgfFactureUnselectSuplierInvoice");
				print '<a href="' . $_SERVER ['PHP_SELF'] . '?action=unlink&idelement=' . $line_fin->id . '&id=' . $id . '&socid=' . $contact_static->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
				print '<img src="' . dol_buildpath('/agefodd/img/unlink.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
				print '</td>';
				print '</tr>';
				print '</table>';
			}
		}
		print '</td>';
		
		print '<td>';
		
		print '</td>';
	} else {
		print '<td>' . $langs->trans('AgfTrainerNotAContactOfSupplier') . '</td>';
	}
	print '</tr>';
}

if ($user->rights->agefodd->modifier && $action == 'new_invoice_supplier_missions') {
	// New lines direct creation
	print '<tr>';
	
	// Create new supplier invoice
	print '<td width="20%" valign="top">';
	print $form->select_company($socid, 'socid', 's.fournisseur=1', 1);
	print '</td>';
	
	print '<td>';
	
	print '<input type="hidden" name="action" value="invoice_supplier_missions_confirm">';
	print '<input type="hidden" name="type" value="invoice_supplier_missions">';
	print '<input type="hidden" name="id" value="' . $id . '">';
	print '<table class="nobordernopadding"><tr>';
	
	print '<td nowrap="nowrap">';
	// print $langs->trans('AgfSelectFournProduct');
	print $formAgefodd->select_produits_fournisseurs_agefodd($socid, $product_fourn, 'product_fourn');
	print '</td>';
	
	print '<td align="left" style="padding-left:10px">';
	print $langs->trans('PriceUHT') . '<input type="text" class="flat" size="4" name="pricemission" value="' . GETPOST('pricemission') . '">' . $langs->getCurrencySymbol($conf->currency);
	print '</td>';
	
	print '<td  align="left" style="padding-left:10px">';
	print $form->load_tva('tva_tx', (GETPOST('tva_tx') ? GETPOST('tva_tx') : - 1));
	print '</td>';
	
	print '<td  align="left" style="padding-left:10px">';
	print $langs->trans('Qty') . '<input type="text" class="flat" size="2" name="qtymission" value="' . GETPOST('qtymission') . '">';
	print '</td>';
	
	print '<td align="left" style="padding-left:10px">';
	print '<input type="submit" class="butAction" name="invoice_supplier_missions_add" value="' . $langs->trans("AgfCreateSupplierInvoice") . '">';
	print '</td></tr></table>';
	
	print '</td>';
	print '</tr>';
	
	// New lines link creation
	print '<tr>';
	print '<td width="20%" valign="top">';
	// print $langs->trans('AgfSelectFournProduct');
	print $form->select_company($socid, 'socidlink', 's.fournisseur=1', 1);
	print '</td>';
	
	print '<td>';
	$legende = $langs->trans("AgfFactureSelectInvoice");
	print '<input type="image" src="' . dol_buildpath('/agefodd/img/link.png', 1) . '" border="0" align="absmiddle" name="link" alt="' . $langs->trans("$legende") . '" ">';
	print '</td>';
	print '</tr>';
} else if ($user->rights->agefodd->modifier) {
	print '<tr>';
	print '<td>';
	print '</td>';
	print '<td>';
	$legende = $langs->trans("AgfCreateSupplierInvoice");
	print '<a href="' . $_SERVER ['PHP_SELF'] . '?action=new_invoice_supplier_missions&id=' . $id . '" alt="' . $legende . '" title="' . $legende . '">';
	print '<img src="' . dol_buildpath('/agefodd/img/new.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
	print '</td>';
	print '</tr>';
}

print '</table>';

print '<br>';

/*
 * Location cost management
 */

print_fiche_titre($langs->trans('AgfLieu'));
$place = new Agefodd_place($db);
$result = $place->fetch($agf->fk_session_place);
if ($result < 0) {
	setEventMessage($place->error, 'errors');
}
$result = $place->fetch_thirdparty();
if ($result < 0) {
	setEventMessage($place->error, 'errors');
}

if (! empty($place->id)) {
	
	print '<table class="border" width="100%">';
	
	print '<tr>';
	
	print '<td width="20%" valign="top">';
	print '<a href="' . dol_buildpath('/agefodd/site/card.php', 1) . '?id=' . $place->id . '">' . $place->ref_interne . '</a>';
	print '&nbsp;';
	print $place->thirdparty->getNomUrl(1);
	print '</td>';
	
	// If contact is a contact of a supllier
	if ($place->thirdparty->fournisseur == 1) {
		
		// Get all document lines
		$agf_fin->fetch_by_session_by_thirdparty($id, $place->thirdparty->id, 'invoice_supplier_room');
		
		if (count($agf_fin->lines) > 0) {
			
			print '<td>';
			
			foreach ( $agf_fin->lines as $line_fin ) {
				if ($action == 'addline' && $idelement == $line_fin->id) {
					
					$suplier_invoice = new FactureFournisseur($db);
					$suplier_invoice->fetch($line_fin->fk_element);
					
					print '<input type="hidden" name="action" value="invoice_addline">';
					print '<input type="hidden" name="id" value="' . $id . '">';
					print '<input type="hidden" name="idelement" value="' . $line_fin->fk_element . '">';
					print '<table class="nobordernopadding"><tr>';
					
					print '<td nowrap="nowrap">';
					// print $langs->trans('AgfSelectFournProduct');
					print $suplier_invoice->getLibStatut(2) . ' ' . $suplier_invoice->getNomUrl(1, '', 0, $conf->global->AGF_NEW_BROWSER_WINDOWS_ON_LINK) . ' (' . price($suplier_invoice->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
					print $formAgefodd->select_produits_fournisseurs_agefodd($contact_static->thirdparty->id, $product_fourn, 'product_fourn');
					print '</td>';
					
					print '<td align="left" style="padding-left:10px">';
					print $langs->trans('PriceUHT') . '<input type="text" class="flat" size="4" name="price" value="' . GETPOST('pricetrainer') . '">' . $langs->getCurrencySymbol($conf->currency);
					print '</td>';
					
					print '<td  align="left" style="padding-left:10px">';
					print $form->load_tva('tva_tx', (GETPOST('tva_tx') ? GETPOST('tva_tx') : - 1));
					print '</td>';
					
					print '<td  align="left" style="padding-left:10px">';
					print $langs->trans('Qty') . '<input type="text" class="flat" size="2" name="qty" value="' . GETPOST('qtytrainer') . '">';
					print '</td>';
					
					print '<td align="left" style="padding-left:10px">';
					print '<input type="submit" class="butAction" name="invoice_supplier_trainer_add" value="' . $langs->trans("AgfFactureAddLineSuplierInvoice") . '">';
					print '</td></tr></table>';
				} else {
					$suplier_invoice = new FactureFournisseur($db);
					$suplier_invoice->fetch($line_fin->fk_element);
					print '<table class="nobordernopadding">';
					print '<tr>';
					// Supplier Invoice inforamtion
					print '<td nowrap="nowrap">';
					print $suplier_invoice->getLibStatut(2) . ' ' . $suplier_invoice->getNomUrl(1, '', 0, $conf->global->AGF_NEW_BROWSER_WINDOWS_ON_LINK) . ' (' . price($suplier_invoice->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
					print '</td>';
					print '<td>';
					$legende = $langs->trans("AgfFactureAddLineSuplierInvoice");
					print '<a href="' . $_SERVER ['PHP_SELF'] . '?action=addline&idelement=' . $line_fin->id . '&id=' . $id . '&socid=' . $contact_static->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
					print img_picto($legende, 'edit_add', 'align="absmiddle"') . '</a>';
					// Unlink order
					$legende = $langs->trans("AgfFactureUnselectSuplierInvoice");
					print '<a href="' . $_SERVER ['PHP_SELF'] . '?action=unlink&idelement=' . $line_fin->id . '&id=' . $id . '&socid=' . $place->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
					print '<img src="' . dol_buildpath('/agefodd/img/unlink.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
					print '</td>';
					print '</tr>';
					print '</table>';
				}
			}
			print '</td>';
		}
		
		print '<td>';
		
		// Create new supplier invoice
		if ($action == 'createinvoice_supplier_place') {
			
			print '<input type="hidden" name="action" value="invoice_supplier_place_confirm">';
			print '<input type="hidden" name="placeid" value="' . $place->id . '">';
			print '<input type="hidden" name="id" value="' . $id . '">';
			print '<input type="hidden" name="socid" value="' . $place->thirdparty->id . '">';
			print '<table class="nobordernopadding"><tr>';
			
			print '<td nowrap="nowrap">';
			// print $langs->trans('AgfSelectFournProduct');
			print $formAgefodd->select_produits_fournisseurs_agefodd($place->thirdparty->id, $product_fourn, 'product_fourn');
			print '</td>';
			
			print '<td align="left" style="padding-left:10px">';
			print $langs->trans('PriceUHT') . '<input type="text" class="flat" size="4" name="priceroom" value="' . GETPOST('priceroom') . '">' . $langs->getCurrencySymbol($conf->currency);
			print '</td>';
			
			print '<td  align="left" style="padding-left:10px">';
			print $form->load_tva('tva_tx', (GETPOST('tva_tx') ? GETPOST('tva_tx') : - 1));
			print '</td>';
			
			print '<td  align="left" style="padding-left:10px">';
			print $langs->trans('Qty') . '<input type="text" class="flat" size="2" name="qtyroom" value="' . GETPOST('qtyroom') . '">';
			print '</td>';
			
			print '<td align="left" style="padding-left:10px">';
			print '<input type="submit" class="butAction" name="invoice_supplier_place_add" value="' . $langs->trans("AgfCreateSupplierInvoice") . '">';
			print '</td></tr></table>';
		} elseif ($user->rights->agefodd->modifier) {
			$legende = $langs->trans("AgfCreateSupplierInvoice");
			print '<a href="' . $_SERVER ['PHP_SELF'] . '?action=createinvoice_supplier_place&placeid=' . $place->id . '&id=' . $id . '" alt="' . $legende . '" title="' . $legende . '">';
			print '<img src="' . dol_buildpath('/agefodd/img/new.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
			
			$legende = $langs->trans("AgfFactureSelectInvoice");
			print '<a href="' . dol_buildpath('/agefodd/session/cost.php', 1) . '?action=link&type=invoice_supplier_room&id=' . $id . '&placeid=' . $place->id . '&socid=' . $place->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
			print '<img src="' . dol_buildpath('/agefodd/img/link.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
		}
		
		print '</td>';
	} else {
		print '<td>' . $langs->trans('AgfPlaceSocNotASupplier') . '</td>';
	}
	
	print '</tr>';
	
	print '</table>';
}

print '</form>';

llxFooter();
$db->close();