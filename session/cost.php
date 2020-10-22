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
require_once (DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php');
require_once (DOL_DOCUMENT_ROOT . '/product/class/product.class.php');
require_once (DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php');
require_once ('../class/agefodd_session_element.class.php');
require_once ('../class/agefodd_place.class.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$opsid = GETPOST('opsid', 'int');
$socid = GETPOST('socid', 'int');
$product_fourn = GETPOST('product_fourn', 'alpha');
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

$socsell = new Societe($db);
if (!empty($socid)) {
	$result = $socsell->fetch($socid);
	if ($result < 0) {
		setEventMessage($socsell->error, 'errors');
	}
}


//If price is not defined Dolibarr combox return idprod_XXX
//else return the fourn price id....
if (strpos($product_fourn,'idprod_')!==false) {
	$product_fourn=str_replace('idprod_', '', $product_fourn);
} else {
	require_once (DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php');

	$prodfourn=new ProductFournisseur($db);
	$result =$prodfourn->fetch_product_fournisseur_price($product_fourn);
	if ($result < 0) {
		setEventMessage($socsell->error, 'errors');
	} else {
		$product_fourn = $prodfourn->fk_product;
	}
}
if (!empty($product_fourn)) {
	// Find product
	$prod = new Product($db);
	$result = $prod->fetch($product_fourn, $product_fourn_ref);
	if ($result < 0) {
		setEventMessage($prod->error, 'errors');
	}
}

/*
 * Action
 */
if ($action == 'invoice_addline') {

	$error = 0;

	$suplier_invoice = new FactureFournisseur($db);
	$suplier_invoice->fetch($idelement);
	$suplier_invoiceline = new SupplierInvoiceLine($db);

	if (empty($prod->id)) {
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Product")), 'errors');
		$error ++;
	}

	if (! $error) {
		$result = $suplier_invoice->addline($prod->ref . ' ' . $prod->description, GETPOST('price', 'none'), GETPOST('tva_tx', 'none'), 0, 0, GETPOST('qty', 'none'), $product_fourn, 0, '', '', 0, '', 'HT', $prod->type);
		if ($result < 0) {
			setEventMessage($suplier_invoice->error, 'errors');
		}

		$total_ht = 0;
		$session_invoice = new Agefodd_session_element($db);
		// Update trainer cost
		$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0, array('\'invoice_supplier_trainer\'','\'invoice_supplierline_trainer\''));

		if ($result < 0) {
			setEventMessage($session_invoice->error, 'errors');
		}
		if (count($session_invoice->lines) > 0) {
			foreach ( $session_invoice->lines as $line ) {

				if($line->element_type  == 'invoice_supplier_trainer'){
					$suplier_invoice->fetch($line->fk_element);

					$total_ht += $suplier_invoice->total_ht;
				}else{
					$suplier_invoiceline->fetch($line->fk_element);

					$total_ht += $suplier_invoiceline->total_ht;
				}

			}
		}
		$agf->cost_trainer = $total_ht;
		$result = $agf->update($user, 1);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		}

		// Update trip cost
		$total_ht = 0;
		$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0,array('\'invoice_supplier_missions\'','\'invoice_supplierline_missions\''));

		if ($result < 0) {
			setEventMessage($session_invoice->error, 'errors');
		}
		if (count($session_invoice->lines) > 0) {
			foreach ( $session_invoice->lines as $line ) {

				if($line->element_type  == 'invoice_supplier_missions'){
					$suplier_invoice->fetch($line->fk_element);

					$total_ht += $suplier_invoice->total_ht;
				}else{
					$suplier_invoiceline->fetch($line->fk_element);

					$total_ht += $suplier_invoiceline->total_ht;
				}
			}
		}
		$agf->cost_trip = $total_ht;
		$result = $agf->update($user, 1);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		}

		// Update training cost
		$total_ht = 0;
		$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0, array('\'invoice_supplier_room\'','\'invoice_supplierline_room\''));
		if ($result < 0) {
			setEventMessage($session_invoice->error, 'errors');
		}
		if (count($session_invoice->lines) > 0) {
			foreach ( $session_invoice->lines as $line ) {

				if($line->element_type  == 'invoice_supplier_room'){
					$suplier_invoice->fetch($line->fk_element);

					$total_ht += $suplier_invoice->total_ht;
				}else{
					$suplier_invoiceline->fetch($line->fk_element);

					$total_ht += $suplier_invoiceline->total_ht;
				}
			}
		}

		$agf->cost_site = $total_ht;
		$result = $agf->update($user, 1);
		if ($result < 0) {
			setEventMessage($agf->error, 'errors');
		}
	}

	header('Location:' . $_SERVER['SELF'] . '?id=' . $id);
}

if ($action == 'invoice_supplier_trainer_confirm') {

	$error = 0;

	$suplier_invoice = new FactureFournisseur($db);
	$suplier_invoiceline = new SupplierInvoiceLine($db);
	$suplier_invoice->socid = $socid;
	$suplier_invoice->ref_supplier = $agf->id . '-' . dol_print_date(dol_now(), 'standard');

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

	$suplier_invoice->lines[0] = ( object ) array ();

	if (empty($prod->id)) {
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Product")), 'errors');
		$error ++;
	}

	if (! $error) {
		$suplier_invoice->lines[0]->description = $prod->ref . ' ' . $prod->description;
		$suplier_invoice->lines[0]->pu_ht = GETPOST('pricetrainer', 'none');
		$suplier_invoice->lines[0]->tva_tx = GETPOST('tva_tx', 'none');
		$suplier_invoice->lines[0]->qty = GETPOST('qtytrainer', 'none');
		$suplier_invoice->lines[0]->fk_product = $prod->id;
		$suplier_invoice->lines[0]->product_type = $prod->type;

		$result = $suplier_invoice->create($user);
		if ($result < 0)
		{
			setEventMessage($suplier_invoice->error, 'errors');
		}
		else
		{

			// Create link with the session/customer
			$session_invoice = new Agefodd_session_element($db);
			$session_invoice->fk_soc = $socid;
			$session_invoice->fk_session_agefodd = $id;
			$session_invoice->fk_element = $result;
			$session_invoice->element_type = 'invoice_supplier_trainer';
			$session_invoice->fk_sub_element = $opsid;
			$result = $session_invoice->create($user);
			if ($result < 0)
			{
				setEventMessage($session_invoice->error, 'errors');
			}

			// Update training cost
			$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0, array('\'invoice_supplier_trainer\'', '\'invoice_supplierline_trainer\''));
			if ($result < 0)
			{
				setEventMessage($session_invoice->error, 'errors');
			}
			if (count($session_invoice->lines) > 0)
			{
				$total_ht = 0;
				foreach ($session_invoice->lines as $line)
				{
					if ($line->element_type == 'invoice_supplier_trainer')
					{
						$suplier_invoice->fetch($line->fk_element);

						$total_ht += $suplier_invoice->total_ht;
					}
					else
					{
						$suplier_invoiceline->fetch($line->fk_element);

						$total_ht += $suplier_invoiceline->total_ht;
					}
				}
			}
			$agf->cost_trainer = $total_ht;
			$result = $agf->update($user, 1);
			if ($result < 0)
			{
				setEventMessage($agf->error, 'errors');
			}

			header('Location:'.$_SERVER['SELF'].'?id='.$id);
		}
	}
} // Creation with soc and product
elseif ($action == 'invoice_supplier_missions_confirm' && empty($islink)) {

	$error = 0;

	$suplier_invoice = new FactureFournisseur($db);
	$suplier_invoiceline = new SupplierInvoiceLine($db);
	$suplier_invoice->socid = $socid;
	$suplier_invoice->ref_supplier = $agf->id . '-' . dol_print_date(dol_now(), 'standard');

	$suplier_invoice->libelle = $agf->formintitule . ' ' . dol_print_date($agf->dated, 'daytextshort') . ' ' . dol_print_date($agf->datef, 'daytextshort');
	$suplier_invoice->date = dol_now();
	$suplier_invoice->date_echeance = dol_now();

	$suplier_invoice->lines[0] = ( object ) array ();

	if (empty($prod->id)) {
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Product")), 'errors');
		$error ++;
	}

	if (! $error) {
		$suplier_invoice->lines[0]->description = $prod->ref . ' ' . $prod->description;
		$suplier_invoice->lines[0]->pu_ht = GETPOST('pricemission', 'none');
		$suplier_invoice->lines[0]->tva_tx = GETPOST('tva_tx', 'none');
		$suplier_invoice->lines[0]->qty = GETPOST('qtymission', 'none');
		$suplier_invoice->lines[0]->fk_product = $prod->id;
		$suplier_invoice->lines[0]->product_type = $prod->type;

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
			$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0, array('\'invoice_supplier_missions\'','\'invoice_supplierline_missions\''));
			if ($result < 0) {
				setEventMessage($session_invoice->error, 'errors');
			}
			if (count($session_invoice->lines) > 0) {
				$total_ht = 0;
				foreach ( $session_invoice->lines as $line ) {
					if ($line->element_type == 'invoice_supplier_missions')
					{
						$suplier_invoice->fetch($line->fk_element);

						$total_ht += $suplier_invoice->total_ht;
					}
					else
					{
						$suplier_invoiceline->fetch($line->fk_element);

						$total_ht += $suplier_invoiceline->total_ht;
					}
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

			header('Location:' . $_SERVER['SELF'] . '?id=' . $id);
		}
	}
} elseif ($action == 'invoice_supplier_place_confirm') {

	$error = 0;

	$suplier_invoice = new FactureFournisseur($db);
	$suplier_invoiceline = new SupplierInvoiceLine($db);
	$suplier_invoice->socid = $socid;
	$suplier_invoice->ref_supplier = $agf->id . '-' . dol_print_date(dol_now(), 'standard');

	$suplier_invoice->libelle = $agf->formintitule . ' ' . dol_print_date($agf->dated, 'daytextshort') . ' ' . dol_print_date($agf->datef, 'daytextshort');
	$suplier_invoice->date = dol_now();
	$suplier_invoice->date_echeance = dol_now();

	$suplier_invoice->lines[0] = ( object ) array ();

	if (empty($prod->id)) {
		setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Product")), 'errors');
		$error ++;
	}

	if (! $error) {
		$suplier_invoice->lines[0]->description = $prod->ref . ' ' . $prod->description;
		$suplier_invoice->lines[0]->pu_ht = GETPOST('priceroom', 'none');
		$suplier_invoice->lines[0]->tva_tx = GETPOST('tva_tx', 'none');
		$suplier_invoice->lines[0]->qty = GETPOST('qtyroom', 'none');
		$suplier_invoice->lines[0]->fk_product = $prod->id;
		$suplier_invoice->lines[0]->product_type = $prod->type;

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
			$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0, array('\'invoice_supplier_room\'','\'invoice_supplierline_room\''));
			if ($result < 0) {
				setEventMessage($session_invoice->error, 'errors');
			}
			if (count($session_invoice->lines) > 0) {
				$totalht = 0;
				foreach ( $session_invoice->lines as $line ) {
					if ($line->element_type == 'invoice_supplier_room')
					{
						$suplier_invoice->fetch($line->fk_element);

						$total_ht += $suplier_invoice->total_ht;
					}
					else
					{
						$suplier_invoiceline->fetch($line->fk_element);

						$total_ht += $suplier_invoiceline->total_ht;
					}
				}
			}
			$agf->cost_site = $total_ht;
			$result = $agf->update($user, 1);
			if ($result < 0) {
				setEventMessage($agf->error, 'errors');
			}

			header('Location:' . $_SERVER['SELF'] . '?id=' . $id);
		}
	}
}
elseif ($action == 'unlink_confirm' && $confirm == 'yes' && $user->rights->agefodd->creer)
{
	$agf_fin = new Agefodd_session_element($db);
	$result = $agf_fin->fetch($idelement);
	if ($result < 0)
	{
		setEventMessage($agf_fin->error, 'errors');
	}

	$deleteobject = GETPOST('deleteobject', 'int');
	if (!empty($deleteobject) && $user->rights->fournisseur->facture->supprimer)
	{
		$isLine = strstr($agf_fin->element_type, 'line');

		if (!empty($isLine))
		{
			$obj_linkline = new SupplierInvoiceLine($db);
			$obj_linkline->fetch($agf_fin->fk_element);
			$obj_link = new FactureFournisseur($db);
			$obj_link->fetch($obj_linkline->fk_facture_fourn);
			$resultdel = $obj_link->deleteline($obj_linkline->id);
		}
		else
		{
			$obj_link = new FactureFournisseur($db);
			$obj_link->fetch($agf_fin->fk_element);
			$resultdel = $obj_link->delete($user,$obj_link->id);
		}

		if ($resultdel < O)
		{
			setEventMessage($obj_link->error, 'errors');
		}
	}

	// If exists we update
	if ($agf_fin->id)
	{
		$result2 = $agf_fin->delete($user);
	}
	if ($result2 > 0)
	{

		// Update training cost
		$result = $agf_fin->fetch_by_session_by_thirdparty($id, 0);
		if ($result < 0)
		{
			setEventMessage($agf_fin->error, 'errors');
		}

		$TSessions = $agf_fin->get_linked_sessions($agf_fin->fk_element, $agf_fin->element_type);

		foreach ($TSessions as $k => $dummy){
		    if($k !== 'total') {
		        $agf_fin->fk_session_agefodd = $k;
		        $agf_fin->updateSellingPrice($user);
		    }

		}

		Header('Location: '.$_SERVER['PHP_SELF'].'?id='.$id);
		exit();
	}
	else
	{
		setEventMessage($agf->error, 'errors');
	}
}
elseif ($action == 'link_confirm' && $confirm == 'yes' && $user->rights->agefodd->creer)
{

	$invoicelineselected = GETPOST('invoicelineselected', 'int');
	$isSelected = false;
	if (!empty($invoicelineselected) && $invoicelineselected != -1)
	{
		$session_invoice = new Agefodd_session_element($db);
		$session_invoice->fk_soc = $socid;
		$session_invoice->fk_session_agefodd = $id;
		$session_invoice->fk_element = $invoicelineselected;

		$session_invoice->element_type = str_replace('_supplier_', '_supplierline_', $type);

		if ($type == 'invoice_supplier_trainer')
		{
			$session_invoice->fk_sub_element = $opsid;
		}
		$isSelected = true;
	}
	else
	{

		$invoiceselected = GETPOST('invoiceselected', 'int');
		if (!empty($invoiceselected))
		{
			$session_invoice = new Agefodd_session_element($db);
			$session_invoice->fk_soc = $socid;
			$session_invoice->fk_session_agefodd = $id;
			$session_invoice->fk_element = $invoiceselected;
			$session_invoice->element_type = $type;
			if ($type == 'invoice_supplier_trainer')
			{
				$session_invoice->fk_sub_element = $opsid;
			}
			$isSelected = true;
		}
	}
	if ($isSelected)
	{
		$result = $session_invoice->create($user);

		if ($result < 0)
		{
			setEventMessage($session_invoice->error, 'errors');
		}
		else
		{
			// Update training cost
			$result = $session_invoice->fetch_by_session($id);
			if ($result < 0)
			{
				setEventMessage($session_invoice->error, 'errors');
			}
			$TSessions = $session_invoice->get_linked_sessions($session_invoice->fk_element, $session_invoice->element_type);

			foreach ($TSessions as $k => $dummy){
			    if($k !== 'total') {
			        $session_invoice->fk_session_agefodd = $k;
			        $session_invoice->updateSellingPrice($user);
			    }

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

dol_fiche_head($head, 'cost', $langs->trans("AgfSessionDetail"), 0, 'bill');

dol_agefodd_banner_tab($agf, 'id');
print '<div class="underbanner clearboth"></div>';

/*
 * Confirm unlink
 */
if ($action == 'unlink') {

	$agf_liste = new Agefodd_session_element($db);
	$result = $agf_liste->fetch($idelement);
	$ref = $agf_liste->facfournnumber;

	if (! empty($agf_liste->id)) {
		$form_question = array ();
		$form_question[] = array (
				'label' => $langs->trans("AgfDeleteObjectAlso", $ref),
				'type' => 'radio',
				'values' => array (
						'0' => $langs->trans('No'),
						'1' => $langs->trans('Yes')
				),
				'name' => 'deleteobject'
		);
	}
	print $form->formconfirm($_SERVER['PHP_SELF'] . '?socid=' . $socid . '&id=' . $id . '&idelement=' . $idelement, $langs->trans("AgfConfirmUnlink"), '', "unlink_confirm", $form_question, '', 1);
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
	$lines_invoice_array = array();
	foreach ( $agf_liste->lines as $line ) {
		$invoice_array[$line->id] = $line->ref . ' ' . $line->ref_supplier;
		$facfourn = new FactureFournisseur($db);
		$facfourn->fetch($line->id);
		$facfourn->fetch_lines();
		foreach($facfourn->lines as $facline){
			 if(!empty($facline->label))$label =  $facline->label;
			 else $label =  $facline->description;
			$lines_invoice_array[$facline->id] = $line->ref . ' ' . $line->ref_supplier.' => '.$label.' x'.$facline->qty.' -- '.price($facline->subprice).'€';
		}
	}

	if ($conf->companycontacts->enabled && $type == 'invoice_supplier_trainer') {

		// Find extra thirdarties link to trainer (case of trainer that are invoiced themselves or from other company)
		$agf_formateurs = new Agefodd_session_formateur($db);
		$result=$agf_formateurs->fetch($opsid);
		if ($result < 0) {
			setEventMessage($compcontact->error, 'errors');
		} else {
			if (!empty($agf_formateurs->socpeopleid)) {
				dol_include_once('/companycontacts/class/companycontacts.class.php');
				$compcontact=new Companycontacts($db);
				$result=$compcontact->fetchAll('t.fk_soc_source','',0,0,array('t.fk_contact'=>$agf_formateurs->socpeopleid));
				if ($result < 0) {
					setEventMessage($compcontact->error, 'errors');
				} else {
					if (is_array($compcontact->lines) && count($compcontact->lines)>0) {
						foreach($compcontact->lines as $trainersocline) {
							$contact_static = new Contact($db);
							$contact_static->fetch($line->socpeopleid);

							$agf_liste = new Agefodd_session_element($db);
							$result = $agf_liste->fetch_invoice_supplier_by_thridparty($trainersocline->fk_soc_source);
							if ($result < 0) {
								setEventMessage($agf_liste->error, 'errors');
							} else {
								foreach ( $agf_liste->lines as $line ) {
									$invoice_array[$line->id] = $line->ref . ' ' . $line->ref_supplier;
									$facfourn = new FactureFournisseur($db);
									$facfourn->fetch($line->id);
									$facfourn->fetch_lines();
									foreach($facfourn->lines as $facline){
										if (!array_key_exists($facline->id, $lines_invoice_array)) {
											if(!empty($facline->label))$label =  $facline->label;
											else $label =  $facline->description;
											$lines_invoice_array[$facline->id] = $line->ref . ' ' . $line->ref_supplier.' => '.$label.' x'.$facline->qty.' -- '.price($facline->subprice).'€';
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}

	$form_question = array ();
	$form_question[] = array (
			'label' => $langs->trans("AgfFactureSelectInvoice"),
			'type' => 'select',
			'values' => $invoice_array,
			'name' => 'invoiceselected'
	);
	$form_question[] = array (
			'label' => $langs->trans("ORAgfFactureSelectInvoiceLine"),
			'type' => 'select',
			'values' => $lines_invoice_array,
			'name' => 'invoicelineselected'
	);

	if ($type == 'invoice_supplier_trainer') {
		$opsid_param = '&opsid=' . $opsid;
	} else {
		$opsid_param = '';
	}

	print $form->formconfirm($_SERVER['PHP_SELF'] . '?socid=' . $socid . '&id=' . $id . '&type=' . $type . $opsid_param, $langs->trans("AgfFactureSelectInvoice"), '', "link_confirm", $form_question, '', 1,300,700);
	if ($ret == 'html')
		print '<br>';
}

/*
 * Trainer cost management
 */
print_fiche_titre($langs->trans('AgfFormateur'));

$agf_formateurs = new Agefodd_session_formateur($db);
$agf_fin = new Agefodd_session_element($db);
$nbform = $agf_formateurs->fetch_formateur_per_session($agf->id);
if ($nbform < 0) {
	setEventMessage($agf_formateurs->error, 'errors');
}

print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '" name="costmanamgent">';
print '<table class="border" width="100%">';
foreach ( $agf_formateurs->lines as $line ) {
	print '<tr>';
	print '<td width="20%" valign="top">';
	// Trainers info
	if (! empty($line->socpeopleid)) {
		print '<a href="' . DOL_URL_ROOT . '/contact/card.php?id=' . $line->socpeopleid . '" ' . $target . '>';
		print img_object($langs->trans("ShowContact"), "contact") . ' ';
		print strtoupper($line->lastname) . ' ' . ucfirst($line->firstname) . '</a>';
	} elseif (! empty($line->userid)) {
		print '<a href="' . DOL_URL_ROOT . '/user/card.php?id=' . $line->userid . '" ' . $target . '>';
		print img_object($langs->trans("ShowContact"), "contact") . ' ';
		print strtoupper($line->lastname) . ' ' . ucfirst($line->firstname) . '</a>';
	}
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
			$agf_fin->fetch_by_session_by_thirdparty($id, $contact_static->thirdparty->id, array('\'invoice_supplier_trainer\'', '\'invoice_supplierline_trainer\''));

			//Manage trainer with multicompany
			$soc_trainer_array=array();
			if ($conf->companycontacts->enabled) {

				$sql_innercontact = "SELECT c.fk_soc_source ";
				$sql_innercontact .= " FROM " . MAIN_DB_PREFIX . "company_contacts as c";
				$sql_innercontact .= " WHERE c.fk_contact=" . $contact_static->id;

				$resql_innercontact = $db->query($sql_innercontact);
				if ($resql_innercontact) {
					while ( $obj_innercontact = $db->fetch_object($resql_innercontact) ) {
						$soc_trainer_array[$obj_innercontact->fk_soc_source] = $obj_innercontact->fk_soc_source;
					}
				} else {
					setEventMessage($db->lasterror(),'errors');
				}
			}
			$soc_trainer_array[$contact_static->thirdparty->id] = $contact_static->thirdparty->id;

			$invoice_trainer_array=array();
			foreach($soc_trainer_array as $soc_trainer) {
				// Get all document lines
				$agf_fin->fetch_by_session_by_thirdparty($id, $soc_trainer, array('\'invoice_supplier_trainer\'', '\'invoice_supplierline_trainer\'','\'order_supplier_trainer\''));
				$invoice_trainer_array=array_merge($invoice_trainer_array,$agf_fin->lines);
			}
			if (count($invoice_trainer_array) > 0) {
				print '<td>';

				foreach ( $invoice_trainer_array as $line_fin ) {

					if ($action == 'addline' && $idelement == $line_fin->id) {

						$suplier_invoice = new FactureFournisseur($db);
						$suplier_invoice->fetch($line_fin->fk_element);

						print '<input type="hidden" name="action" value="invoice_addline">';
						print '<input type="hidden" name="id" value="' . $id . '">';
						print '<input type="hidden" name="idelement" value="' . $line_fin->fk_element . '">';
						print '<table class="nobordernopadding"><tr>';

						print '<td nowrap="nowrap">';
						print $suplier_invoice->getLibStatut(2) . ' ' . $suplier_invoice->getNomUrl(1, '', 0) . ' (' . price($suplier_invoice->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
						print $form->select_produits_fournisseurs($contact_static->thirdparty->id, $product_fourn, 'product_fourn','','',array(),0,1);
						print '</td>';

						print '<td align="left" style="padding-left:10px">';
						print $langs->trans('PriceUHT') . '<input type="text" class="flat" size="4" name="price" value="' . GETPOST('pricetrainer', 'none') . '">' . $langs->getCurrencySymbol($conf->currency);
						print '</td>';

						print '<td  align="left" style="padding-left:10px">';

						print $form->load_tva('tva_tx', (GETPOST('tva_tx', 'none') ? GETPOST('tva_tx', 'none') : - 1),$contact_static->thirdparty,$mysoc);
						print '</td>';

						print '<td  align="left" style="padding-left:10px">';
						print $langs->trans('Qty') . '<input type="text" class="flat" size="2" name="qty" value="' . GETPOST('qtytrainer', 'none') . '">';
						print '</td>';

						print '<td align="left" style="padding-left:10px">';
						print '<input type="submit" class="butAction" name="invoice_supplier_trainer_add" value="' . $langs->trans("AgfFactureAddLineSuplierInvoice") . '">';
						print '</td></tr></table>';
					}else if($line_fin->element_type == 'order_supplier_trainer'){
						$supplier_order = new CommandeFournisseur($db);
						$supplier_order->fetch($line_fin->fk_element);
						print '<table class="nobordernopadding">';
						print '<tr>';
						// Supplier Invoice inforamtion
						print '<td nowrap="nowrap">';
						print $supplier_order->getLibStatut(4) . ' ' . $supplier_order->getNomUrl(1, '', 0) . ' (' . price($supplier_order->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
						print '</td>';
						print '<td>';

						print '</tr>';
						print '</table>';
					} else {

						if($line_fin->element_type == "invoice_supplier_trainer"){
							$suplier_invoice = new FactureFournisseur($db);
							$suplier_invoice->fetch($line_fin->fk_element);
							$agf->fetch_all_by_order_invoice_propal('', '','','','','','',$suplier_invoice->id,'');
                            $count = count($agf->lines);
							print '<table class="nobordernopadding">';
							print '<tr>';
							// Supplier Invoice inforamtion
							print '<td nowrap="nowrap">';
							print $suplier_invoice->getLibStatut(2) . ' ' . $suplier_invoice->getNomUrl(1, '', 0, $conf->global->AGF_NEW_BROWSER_WINDOWS_ON_LINK) . ' (' . price($suplier_invoice->total_ht). $langs->getCurrencySymbol($conf->currency);
							if ($count > 1){
                                print ' soit '.price($suplier_invoice->total_ht/count($agf->lines)) . $langs->getCurrencySymbol($conf->currency) .' '. $langs->trans("AgfForSession"). ' ' . $agf->ref;
                            }
                            print ')';
							print '</td>';
							print '<td>';
							// Ad invoice line
							$legende = $langs->trans("AgfFactureAddLineSuplierInvoice");
							print '<a href="' . $_SERVER['PHP_SELF'] . '?action=addline&idelement=' . $line_fin->id . '&id=' . $id . '&socid=' . $contact_static->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
							print img_picto($legende, 'edit_add', 'align="absmiddle"') . '</a>';
							// Unlink order
							$legende = $langs->trans("AgfFactureUnselectSuplierInvoice");
							print '<a href="' . $_SERVER['PHP_SELF'] . '?action=unlink&idelement=' . $line_fin->id . '&id=' . $id . '&socid=' . $contact_static->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
							print '<img src="' . dol_buildpath('/agefodd/img/unlink.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';

							print '</td>';
							print '</tr>';
							print '</table>';
						}else {

							$supplier_invoiceline = new SupplierInvoiceLine($db);
							$supplier_invoiceline->fetch($line_fin->fk_element);
							$suplier_invoice = new FactureFournisseur($db);
							$suplier_invoice->fetch($supplier_invoiceline->fk_facture_fourn);
							if (!empty($supplier_invoiceline->label))$label = $supplier_invoiceline->label;
							else $label = $supplier_invoiceline->product_desc;
							print '<table class="nobordernopadding">';
							print '<tr>';
							// Supplier Invoice inforamtion
							print '<td nowrap="nowrap">';
							print $suplier_invoice->getLibStatut(2) . ' ' . $suplier_invoice->getNomUrl(1, '', 0, $conf->global->AGF_NEW_BROWSER_WINDOWS_ON_LINK) . ' - '.$label.' (' . price($supplier_invoiceline->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
							print '</td>';
							print '<td>';

							// Unlink order
							$legende = $langs->trans("AgfFactureUnselectSuplierInvoice");
							print '<a href="' . $_SERVER['PHP_SELF'] . '?action=unlink&idelement=' . $line_fin->id . '&id=' . $id . '&socid=' . $contact_static->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
							print '<img src="' . dol_buildpath('/agefodd/img/unlink.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';

							print '</td>';
							print '</tr>';
							print '</table>';
						}

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

				if ($conf->companycontacts->enabled) {

					$sql_innercontact = "SELECT c.fk_soc_source ";
					$sql_innercontact .= " FROM " . MAIN_DB_PREFIX . "company_contacts as c";
					$sql_innercontact .= " WHERE c.fk_contact=" . $contact_static->id;

					$resql_innercontact = $db->query($sql_innercontact);
					if ($resql_innercontact) {
						while ( $obj_innercontact = $db->fetch_object($resql_innercontact) ) {
							$soc_trainer_array[$obj_innercontact->fk_soc_source] = $obj_innercontact->fk_soc_source;
						}
					} else {
						setEventMessage($db->lasterror(),'errors');
					}
				}
				$soc_trainer_array[$contact_static->thirdparty->id] = $contact_static->thirdparty->id;

				if (count($soc_trainer_array)==1) {
					print '<input type="hidden" name="socid" value="' . $contact_static->thirdparty->id . '">';
				}

				print '<table class="nobordernopadding"><tr>';

				if ($conf->companycontacts->enabled && count($soc_trainer_array)>1) {
					print '<td nowrap="nowrap">';
					// print $langs->trans('AgfSelectFournProduct');
					print $form->select_company($contact_static->thirdparty->id, 'socid', 's.rowid IN ('.implode(',',$soc_trainer_array).')', 0, 1, 1);
					print '</td>';
				}

				print '<td nowrap="nowrap">';
				// print $langs->trans('AgfSelectFournProduct');
				print $form->select_produits_fournisseurs($contact_static->thirdparty->id, $product_fourn, 'product_fourn','','',array(),0,1);
				print '</td>';

				print '<td align="left" style="padding-left:10px">';
				print $langs->trans('PriceUHT') . '<input type="text" class="flat" size="4" name="pricetrainer" value="' . GETPOST('pricetrainer', 'none') . '">' . $langs->getCurrencySymbol($conf->currency);
				print '</td>';
				print '<td  align="left" style="padding-left:10px">';
				print $form->load_tva('tva_tx', (GETPOST('tva_tx', 'none') ? GETPOST('tva_tx', 'none') : - 1),$contact_static->thirdparty,$mysoc);
				print '</td>';

				print '<td  align="left" style="padding-left:10px">';
				print $langs->trans('Qty') . '<input type="text" class="flat" size="2" name="qtytrainer" value="' . GETPOST('qtytrainer', 'none') . '">';
				print '</td>';

				print '<td align="left" style="padding-left:10px">';
				print '<input type="submit" class="butAction" name="invoice_supplier_trainer_add" value="' . $langs->trans("AgfCreateSupplierInvoice") . '">';
				print '</td></tr></table>';
			} elseif ($user->rights->agefodd->modifier) {
				$legende = $langs->trans("AgfCreateSupplierInvoice");
				print '<a href="' . $_SERVER['PHP_SELF'] . '?action=createinvoice_supplier&opsid=' . $line->opsid . '&id=' . $id . '" alt="' . $legende . '" title="' . $legende . '">';
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
$result = $agf_fin->fetch_by_session_by_thirdparty($id, 0, array('\'invoice_supplier_missions\'','\'invoice_supplierline_missions\'','\'order_supplier_missions\''));

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
				$suplier_invoice->fetch_thirdparty();
				print '<input type="hidden" name="action" value="invoice_addline">';
				print '<input type="hidden" name="id" value="' . $id . '">';
				print '<input type="hidden" name="idelement" value="' . $line_fin->fk_element . '">';
				print '<table class="nobordernopadding"><tr>';

				print '<td nowrap="nowrap">';
				// print $langs->trans('AgfSelectFournProduct');
				print $suplier_invoice->getLibStatut(2) . ' ' . $suplier_invoice->getNomUrl(1, '', 0) . ' (' . price($suplier_invoice->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
				print $form->select_produits_fournisseurs($contact_static->thirdparty->id, $product_fourn, 'product_fourn','','',array(),0,1);
				print '</td>';

				print '<td align="left" style="padding-left:10px">';
				print $langs->trans('PriceUHT') . '<input type="text" class="flat" size="4" name="price" value="' . GETPOST('pricetrainer', 'none') . '">' . $langs->getCurrencySymbol($conf->currency);
				print '</td>';


				print '<td  align="left" style="padding-left:10px">';
				print $form->load_tva('tva_tx', (GETPOST('tva_tx', 'none') ? GETPOST('tva_tx', 'none') : - 1),$suplier_invoice->thirdparty,$mysoc);
				print '</td>';

				print '<td  align="left" style="padding-left:10px">';
				print $langs->trans('Qty') . '<input type="text" class="flat" size="2" name="qty" value="' . GETPOST('qtytrainer', 'none') . '">';
				print '</td>';

				print '<td align="left" style="padding-left:10px">';
				print '<input type="submit" class="butAction" name="invoice_supplier_trainer_add" value="' . $langs->trans("AgfFactureAddLineSuplierInvoice") . '">';
				print '</td></tr></table>';
			} else {
				if($line_fin->element_type == 'invoice_supplier_missions'){
					$suplier_invoice = new FactureFournisseur($db);
					$suplier_invoice->fetch($line_fin->fk_element);
					print '<table class="nobordernopadding">';
					print '<tr>';
					// Supplier Invoice inforamtion
					print '<td nowrap="nowrap">';
					print $suplier_invoice->getLibStatut(2) . ' ' . $suplier_invoice->getNomUrl(1, '', 0) . ' (' . price($suplier_invoice->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
					print '</td>';
					print '<td>';
					$legende = $langs->trans("AgfFactureAddLineSuplierInvoice");
					print '<a href="' . $_SERVER['PHP_SELF'] . '?action=addline&idelement=' . $line_fin->id . '&id=' . $id . '&socid=' . $contact_static->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
					print img_picto($legende, 'edit_add', 'align="absmiddle"') . '</a>';
					// Unlink order
					$legende = $langs->trans("AgfFactureUnselectSuplierInvoice");
					print '<a href="' . $_SERVER['PHP_SELF'] . '?action=unlink&idelement=' . $line_fin->id . '&id=' . $id . '&socid=' . $contact_static->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
					print '<img src="' . dol_buildpath('/agefodd/img/unlink.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
					print '</td>';
					print '</tr>';
					print '</table>';
				}else if($line_fin->element_type == 'order_supplier_missions'){
					$supplier_order = new CommandeFournisseur($db);
					$supplier_order->fetch($line_fin->fk_element);
					print '<table class="nobordernopadding">';
					print '<tr>';
					// Supplier Invoice inforamtion
					print '<td nowrap="nowrap">';
					print $supplier_order->getLibStatut(4) . ' ' . $supplier_order->getNomUrl(1, '', 0) . ' (' . price($supplier_order->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
					print '</td>';
					print '<td>';

					print '</tr>';
					print '</table>';
				}
					else {
						$supplier_invoiceline = new SupplierInvoiceLine($db);
						$supplier_invoiceline->fetch($line_fin->fk_element);
						$suplier_invoice = new FactureFournisseur($db);
						$suplier_invoice->fetch($supplier_invoiceline->fk_facture_fourn);
						if (!empty($supplier_invoiceline->label))
							$label = $supplier_invoiceline->label;
						else
							$label = $supplier_invoiceline->description;

						print '<table class="nobordernopadding">';
						print '<tr>';
						// Supplier Invoice inforamtion
						print '<td nowrap="nowrap">';
						print $suplier_invoice->getLibStatut(2).' '.$suplier_invoice->getNomUrl(1, '', 0).' - '.$label.' ('.price($supplier_invoiceline->total_ht).$langs->getCurrencySymbol($conf->currency).')';
						print '</td>';
						print '<td>';

						// Unlink order
						$legende = $langs->trans("AgfFactureUnselectSuplierInvoice");
						print '<a href="'.$_SERVER['PHP_SELF'].'?action=unlink&idelement='.$line_fin->id.'&id='.$id.'&socid='.$contact_static->thirdparty->id.'" alt="'.$legende.'" title="'.$legende.'">';
						print '<img src="'.dol_buildpath('/agefodd/img/unlink.png', 1).'" border="0" align="absmiddle" hspace="2px" ></a>';

						print '</td>';
						print '</tr>';
						print '</table>';
					}
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
	print $form->select_thirdparty_list($socid, 'socid', 's.fournisseur=1', 'SelectThirdParty');
	print '</td>';

	print '<td>';

	print '<input type="hidden" name="action" value="invoice_supplier_missions_confirm">';
	print '<input type="hidden" name="type" value="invoice_supplier_missions">';
	print '<input type="hidden" name="id" value="' . $id . '">';
	print '<table class="nobordernopadding"><tr>';

	print '<td nowrap="nowrap">';
	// print $langs->trans('AgfSelectFournProduct');
	print $form->select_produits_fournisseurs($socid, $product_fourn, 'product_fourn','','',array(),0,1);
	print '</td>';

	print '<td align="left" style="padding-left:10px">';
	print $langs->trans('PriceUHT') . '<input type="text" class="flat" size="4" name="pricemission" value="' . GETPOST('pricemission', 'none') . '">' . $langs->getCurrencySymbol($conf->currency);
	print '</td>';

	print '<td  align="left" style="padding-left:10px">';
	print $form->load_tva('tva_tx', (GETPOST('tva_tx', 'none') ? GETPOST('tva_tx', 'none') : - 1),$mysoc,$mysoc);
	print '</td>';

	print '<td  align="left" style="padding-left:10px">';
	print $langs->trans('Qty') . '<input type="text" class="flat" size="2" name="qtymission" value="' . GETPOST('qtymission', 'none') . '">';
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
	print $form->select_thirdparty_list($socid, 'socidlink', 's.fournisseur=1', 'SelectThirdParty');
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
	print '<a href="' . $_SERVER['PHP_SELF'] . '?action=new_invoice_supplier_missions&id=' . $id . '" alt="' . $legende . '" title="' . $legende . '">';
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
	if(!empty($place->thirdparty)) print $place->thirdparty->getNomUrl(1);
	print '</td>';

	// If contact is a contact of a supllier
	if ($place->thirdparty->fournisseur == 1) {

		// Get all document lines
		$agf_fin->fetch_by_session_by_thirdparty($id, $place->thirdparty->id,array( '\'invoice_supplier_room\'','\'invoice_supplierline_room\'','\'order_supplier_room\''));

		if (count($agf_fin->lines) > 0) {

			print '<td>';
			foreach ( $agf_fin->lines as $line_fin ) {
				if ($action == 'addline' && $idelement == $line_fin->id) {

					$suplier_invoice = new FactureFournisseur($db);
					$suplier_invoice->fetch($line_fin->fk_element);
					$suplier_invoice->fetch_thirdparty();
					print '<input type="hidden" name="action" value="invoice_addline">';
					print '<input type="hidden" name="id" value="' . $id . '">';
					print '<input type="hidden" name="idelement" value="' . $line_fin->fk_element . '">';
					print '<table class="nobordernopadding"><tr>';

					print '<td nowrap="nowrap">';
					// print $langs->trans('AgfSelectFournProduct');
					print $suplier_invoice->getLibStatut(2) . ' ' . $suplier_invoice->getNomUrl(1, '', 0) . ' (' . price($suplier_invoice->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
					print $form->select_produits_fournisseurs($contact_static->thirdparty->id, $product_fourn, 'product_fourn','','',array(),0,1);
					print '</td>';
					print '<td align="left" style="padding-left:10px">';
					print $langs->trans('PriceUHT') . '<input type="text" class="flat" size="4" name="price" value="' . GETPOST('pricetrainer', 'none') . '">' . $langs->getCurrencySymbol($conf->currency);
					print '</td>';

					print '<td  align="left" style="padding-left:10px">';
					print $form->load_tva('tva_tx', (GETPOST('tva_tx', 'none') ? GETPOST('tva_tx', 'none') : - 1),$suplier_invoice->thirdparty,$mysoc);
					print '</td>';

					print '<td  align="left" style="padding-left:10px">';
					print $langs->trans('Qty') . '<input type="text" class="flat" size="2" name="qty" value="' . GETPOST('qtytrainer', 'none') . '">';
					print '</td>';

					print '<td align="left" style="padding-left:10px">';
					print '<input type="submit" class="butAction" name="invoice_supplier_trainer_add" value="' . $langs->trans("AgfFactureAddLineSuplierInvoice") . '">';
					print '</td></tr></table>';
				} else {
					if($line_fin->element_type == 'invoice_supplier_room'){
						$suplier_invoice = new FactureFournisseur($db);
						$suplier_invoice->fetch($line_fin->fk_element);
						print '<table class="nobordernopadding">';
						print '<tr>';
						// Supplier Invoice inforamtion
						print '<td nowrap="nowrap">';
						print $suplier_invoice->getLibStatut(2) . ' ' . $suplier_invoice->getNomUrl(1, '', 0) . ' (' . price($suplier_invoice->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
						print '</td>';
						print '<td>';
						$legende = $langs->trans("AgfFactureAddLineSuplierInvoice");
						print '<a href="' . $_SERVER['PHP_SELF'] . '?action=addline&idelement=' . $line_fin->id . '&id=' . $id . '&socid=' . $contact_static->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
						print img_picto($legende, 'edit_add', 'align="absmiddle"') . '</a>';
						// Unlink order
						$legende = $langs->trans("AgfFactureUnselectSuplierInvoice");
						print '<a href="' . $_SERVER['PHP_SELF'] . '?action=unlink&idelement=' . $line_fin->id . '&id=' . $id . '&socid=' . $place->thirdparty->id . '" alt="' . $legende . '" title="' . $legende . '">';
						print '<img src="' . dol_buildpath('/agefodd/img/unlink.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
						print '</td>';
						print '</tr>';
						print '</table>';
					}
					else if($line_fin->element_type == 'order_supplier_room'){
						$supplier_order = new CommandeFournisseur($db);
						$supplier_order->fetch($line_fin->fk_element);
						print '<table class="nobordernopadding">';
						print '<tr>';
						// Supplier Invoice inforamtion
						print '<td nowrap="nowrap">';
						print $supplier_order->getLibStatut(4) . ' ' . $supplier_order->getNomUrl(1, '', 0) . ' (' . price($supplier_order->total_ht) . $langs->getCurrencySymbol($conf->currency) . ')';
						print '</td>';
						print '<td>';

						print '</tr>';
						print '</table>';
					}
					else
					{
						$supplier_invoiceline = new SupplierInvoiceLine($db);
						$supplier_invoiceline->fetch($line_fin->fk_element);
						$suplier_invoice = new FactureFournisseur($db);
						$suplier_invoice->fetch($supplier_invoiceline->fk_facture_fourn);
						if (!empty($supplier_invoiceline->label))
							$label = $supplier_invoiceline->label;
						else
							$label = $supplier_invoiceline->description;

						print '<table class="nobordernopadding">';
						print '<tr>';
						// Supplier Invoice inforamtion
						print '<td nowrap="nowrap">';
						print $suplier_invoice->getLibStatut(2).' '.$suplier_invoice->getNomUrl(1, '', 0).' - '.$label.' ('.price($supplier_invoiceline->total_ht).$langs->getCurrencySymbol($conf->currency).')';
						print '</td>';
						print '<td>';

						// Unlink order
						$legende = $langs->trans("AgfFactureUnselectSuplierInvoice");
						print '<a href="'.$_SERVER['PHP_SELF'].'?action=unlink&idelement='.$line_fin->id.'&id='.$id.'&socid='.$contact_static->thirdparty->id.'" alt="'.$legende.'" title="'.$legende.'">';
						print '<img src="'.dol_buildpath('/agefodd/img/unlink.png', 1).'" border="0" align="absmiddle" hspace="2px" ></a>';

						print '</td>';
						print '</tr>';
						print '</table>';
					}
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
			print $form->select_produits_fournisseurs($place->thirdparty->id, $product_fourn, 'product_fourn','','',array(),0,1);
			print '</td>';

			print '<td align="left" style="padding-left:10px">';
			print $langs->trans('PriceUHT') . '<input type="text" class="flat" size="4" name="priceroom" value="' . GETPOST('priceroom', 'none') . '">' . $langs->getCurrencySymbol($conf->currency);
			print '</td>';

			print '<td  align="left" style="padding-left:10px">';
			print $form->load_tva('tva_tx', (GETPOST('tva_tx', 'none') ? GETPOST('tva_tx', 'none') : - 1),$place->thirdparty,$mysoc);
			print '</td>';

			print '<td  align="left" style="padding-left:10px">';
			print $langs->trans('Qty') . '<input type="text" class="flat" size="2" name="qtyroom" value="' . GETPOST('qtyroom', 'none') . '">';
			print '</td>';

			print '<td align="left" style="padding-left:10px">';
			print '<input type="submit" class="butAction" name="invoice_supplier_place_add" value="' . $langs->trans("AgfCreateSupplierInvoice") . '">';
			print '</td></tr></table>';
		} elseif ($user->rights->agefodd->modifier) {
			$legende = $langs->trans("AgfCreateSupplierInvoice");
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=createinvoice_supplier_place&placeid=' . $place->id . '&id=' . $id . '" alt="' . $legende . '" title="' . $legende . '">';
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


/*
 * Supplier Orders list
 */
$agf_supplierorder = new Agefodd_session_element($db);
// Get all document lines
$result = $agf_supplierorder->fetch_by_session_by_thirdparty($id, 0, array('\'order_supplier\''));

if ($result > 0) {
    print '<br>';
    print_fiche_titre($langs->trans('AgfOrders'));

    print '<table class="border" width="100%">';

    foreach ($agf_supplierorder->lines as $line){
        if(!empty($line->fk_soc)){
            $soc = new Societe($db);
            $soc->fetch($line->fk_soc);
        }
        if (!empty($line->fk_element)){
            $order = new CommandeFournisseur($db);
            $order->fetch($line->fk_element);
        }
        print '<tr>';

        if(!empty($line->fk_soc)) print '<td width="20%">'.$soc->getNomUrl(1).'</td>';
        else print '<td width="20%"></td>';

        // Unlink order
        $legende = $langs->trans("AgfOrdersUnselect");
        $delink = '<a href="'.$_SERVER['PHP_SELF'].'?action=unlink&idelement='.$line->id.'&id='.$line->fk_session_agefodd.'&socid='.$line->fk_soc.'" alt="'.$legende.'" title="'.$legende.'">';
        $delink .= '<img src="'.dol_buildpath('/agefodd/img/unlink.png', 1).'" border="0" align="absmiddle" hspace="2px" ></a>';

        if(!empty($line->fk_element)) print '<td>'.$order->getLibStatut(4).' '.$order->getNomUrl(1).' ('.price($order->total_ht).'€) '.$delink.'</td>';
        else print '<td></td>';
        print '</tr>';

    }
    print '</table>';

}

llxFooter();
$db->close();
