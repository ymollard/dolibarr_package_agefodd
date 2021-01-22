<?php
/*
 * Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file agefodd/session/list_fin.php
 * \ingroup agefodd
 * \brief list of session per order or invoice
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_formation_catalogue.class.php');
require_once ('../class/agefodd_session_element.class.php');
require_once ('../class/agefodd_place.class.php');
require_once ('../lib/agefodd.lib.php');
require_once ('../class/html.formagefodd.class.php');
require_once (DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php');
require_once (DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php');
require_once (DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php');
require_once (DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php');
require_once (DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');

$action = GETPOST('action', 'none');
$confirm = GETPOST('confirm', 'none');
$idelement = GETPOST('idelement', 'none');
$idsess = GETPOST('idsess', 'none');

// Search criteria
$search_orderid = GETPOST('search_orderid', 'int');
$search_invoiceid = GETPOST('search_invoiceid', 'int');
$search_fourninvoiceid = GETPOST('search_fourninvoiceid', 'int');
$search_fourninvoiceref = GETPOST('search_fourninvoiceref', 'none');
$search_fournorderid = GETPOST('search_fournorderid', 'int');
$search_fournorderref = GETPOST('search_fournorderref', 'none');
$search_orderref = GETPOST('search_orderref', 'alpha');
$search_invoiceref = GETPOST('search_invoiceref', 'alpha');
$search_propalref = GETPOST('search_propalref', 'alpha');
$search_propalid = GETPOST('search_propalid', 'alpha');


$link_element = GETPOST("link_element", 'none');
if (! empty($link_element)) {
	$action = 'link_element';
}

$langs->load('bills');
$langs->load('orders');

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x", 'none') || GETPOST("button_removefilter", 'none')) {
	$search_orderid = '';
	$search_invoiceid = '';
	$search_fourninvoiceid = '';
	$search_fourninvoiceref = '';
	$search_fournorderid = '';
	$search_fournorderref = '';
	$search_orderref = '';
	$search_invoiceref = '';
	$search_propalref = '';
	$search_propalid = '';
}

if (empty($sortorder)) {
	$sortorder = "DESC";
}
if (empty($sortfield)) {
	$sortfield = "s.rowid";
}

if (empty($page) || $page == - 1) {
	$page = 0;
}

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

if (! empty($search_orderid)) {
	$order = new Commande($db);
	$order->fetch($search_orderid);
	$order->fetch_thirdparty();
	$search_orderref = $order->ref;
	$object_socid = $order->socid;
	$urlcomplete = '&search_orderid=' . $search_orderid;
}

if (! empty($search_invoiceid)) {
	$invoice = new Facture($db);
	$invoice->fetch($search_invoiceid);
	$invoice->fetch_thirdparty();
	$search_invoiceref = $invoice->ref;
	$object_socid = $invoice->socid;
	$urlcomplete = '&search_invoiceid=' . $search_invoiceid;
}

if (! empty($search_fourninvoiceid)) {
	$fourninvoice = new FactureFournisseur($db);
	$fourninvoice->fetch($search_fourninvoiceid);
	$fourninvoice->fetch_thirdparty();
	$search_fourninvoiceref = $fourninvoice->ref;
	$object_socid = $fourninvoice->socid;
	$urlcomplete = '&search_fourninvoiceid=' . $search_fourninvoiceid;
}

if (! empty($search_fournorderid)) {
	$fournorder = new CommandeFournisseur($db);
	$fournorder->fetch($search_fournorderid);
	$fournorder->fetch_thirdparty();
	$search_fournorderref = $fournorder->ref;
	$object_socid = $fournorder->socid;
	$urlcomplete = '&search_fournorderid=' . $search_fournorderid;
}

if (! empty($search_orderref)) {
	$order = new Commande($db);
	$order->fetch('', $search_orderref);
	$order->fetch_thirdparty();
	$search_orderid = $order->id;
	$object_socid = $order->socid;
	$urlcomplete = '&search_orderid=' . $search_orderid;
}

if (! empty($search_invoiceref)) {
	$invoice = new Facture($db);
	$invoice->fetch('', $search_invoiceref);
	$invoice->fetch_thirdparty();
	$search_invoiceid = $invoice->id;
	$object_socid = $invoice->socid;
	$urlcomplete = '&search_invoiceid=' . $search_invoiceid;
}

if (! empty($search_fourninvoiceref) && empty($search_fourninvoiceid)) {
	$fourninvoice = new FactureFournisseur($db);
	$fourninvoice->fetch('', $search_fourninvoiceref);
	$fourninvoice->fetch_thirdparty();
	$search_fourninvoiceid = $fourninvoice->id;
	$object_socid = $fourninvoice->socid;
	$urlcomplete = '&search_fourninvoiceid=' . $search_fourninvoiceid;
}

if (! empty($search_fournorderref)) {
	$fournorder = new CommandeFournisseur($db);
	$fournorder->fetch('', $search_fournorderref);
	$fournorder->fetch_thirdparty();
	$search_fournorderid = $fournorder->id;
	$object_socid = $fournorder->socid;
	$urlcomplete = '&search_fournorderid=' . $search_fournorderid;
}

if (! empty($search_propalref)) {
	$propal = new Propal($db);
	$propal->fetch('', $search_propalref);
	$propal->fetch_thirdparty();
	$search_propalid = $propal->id;
	$object_socid = $propal->socid;
	$urlcomplete = '&search_propalid=' . $search_propalid;
}

if (! empty($search_propalid)) {
	$propal = new Propal($db);
	$propal->fetch($search_propalid, '');
	$propal->fetch_thirdparty();
	$search_propalref = $propal->ref;
	$object_socid = $propal->socid;
	$urlcomplete = '&search_propalid=' . $search_propalid;
}

if ($action == 'unlink_confirm' && $confirm == 'yes' && ($user->rights->agefodd->modifier || $user->rights->fournisseur->facture->creer)) {
	$agf_fin = new Agefodd_session_element($db);
	$result = $agf_fin->fetch($idelement);
	if ($result < 0) {
		setEventMessage($agf_fin->error, 'errors');
	}

	// If exists we update
	if ($agf_fin->id) {
		$result2 = $agf_fin->delete($user);
	}
	if ($result2 > 0) {

		// Update training cost
		$result = $agf_fin->fetch_by_session_by_thirdparty($idsess, 0);
		if ($result < 0) {
			setEventMessage($agf_fin->error, 'errors');
		}

		$TSessions = $agf_fin->get_linked_sessions($agf_fin->fk_element, $agf_fin->element_type);

		foreach ( $TSessions as $k => $dummy ) {
			if ($k !== 'total') {
				$agf_fin->fk_session_agefodd = $k;
				$agf_fin->updateSellingPrice($user);
			}
		}

		Header('Location: ' . $_SERVER['PHP_SELF'] . "?" . substr($urlcomplete, 1));
		exit();
	} else {
		setEventMessage($agf->error, 'errors');
	}
}

$title = $langs->trans("AgfMenuSessByInvoiceOrder");
llxHeader('', $title);

if (! empty($search_orderid) || ! empty($search_orderref)) {
	require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';
	$head = commande_prepare_head($order);
	dol_fiche_head($head, 'tabAgefodd', $langs->trans("Order"), 0, 'order');
	$element_type = 'order';
	$element_id = $search_orderid;

	$morehtmlref = '<div class="refidno">';
	// Ref customer
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $order->ref_client, $order, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $order->ref_client, $order, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>' . $langs->trans('ThirdParty') . ' : ' .  (!empty($order->thirdparty)?$order->thirdparty->getNomUrl(1):'');
	$morehtmlref .= '</div>';
	if (function_exists('dol_banner_tab')) {
		dol_banner_tab($order, 'search_orderref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	}
}

if (! empty($search_invoiceid) || ! empty($search_invoiceref)) {
	require_once DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';
	$head = facture_prepare_head($invoice);
	dol_fiche_head($head, 'tabAgefodd', $langs->trans('InvoiceCustomer'), 0, 'bill');
	$element_type = 'invoice';
	$element_id = $search_invoiceid;

	$morehtmlref = '<div class="refidno">';
	// Ref customer
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $invoice->ref_client, $invoice, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $invoice->ref_client, $invoice, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>' . $langs->trans('ThirdParty') . ' : ' .  (!empty($invoice->thirdparty)?$invoice->thirdparty->getNomUrl(1):'');
	$morehtmlref .= '</div>';
	if (function_exists('dol_banner_tab')) {
		dol_banner_tab($invoice, 'search_invoiceref', $linkback, 1, 'facnumber', 'ref', $morehtmlref, '', 0, '', '');
	}
}

if (! empty($search_fourninvoiceid) || ! empty($search_fourninvoiceref)) {
	require_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
	$head = facturefourn_prepare_head($fourninvoice);
	dol_fiche_head($head, 'tabAgefodd', $langs->trans('SupplierInvoice'), 0, 'bill');

	$morehtmlref = '<div class="refidno">';
	// Ref customer
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $fourninvoice->ref_client, $fourninvoice, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $fourninvoice->ref_client, $fourninvoice, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>' . $langs->trans('ThirdParty') . ' : ' . (!empty($fourninvoice->thirdparty)?$fourninvoice->thirdparty->getNomUrl(1):'');
	$morehtmlref .= '</div>';
	if (function_exists('dol_banner_tab')) {
		dol_banner_tab($fourninvoice, 'search_fourninvoiceref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	}
}

if (! empty($search_fournorderid) || ! empty($search_fournordereref)) {
	require_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
	$head = ordersupplier_prepare_head($fournorder);
	dol_fiche_head($head, 'tabAgefodd', $langs->trans('SupplierOrder'), 0, 'bill');

	$element_type = 'order_supplier';
	$element_id = $search_fournorderid;

	$morehtmlref = '<div class="refidno">';
	// Ref customer
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $fournorder->ref_client, $fournorder, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $fournorder->ref_client, $fournorder, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>' . $langs->trans('ThirdParty') . ' : ' . (!empty($fournorder->thirdparty)?$fournorder->thirdparty->getNomUrl(1):'');
	$morehtmlref .= '</div>';
	if (function_exists('dol_banner_tab')) {
		dol_banner_tab($fournorder, 'search_fournorderref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	}
}

if (! empty($search_propalref) || ! empty($search_propalid)) {
	require_once DOL_DOCUMENT_ROOT . '/core/lib/propal.lib.php';
	$head = propal_prepare_head($propal);
	dol_fiche_head($head, 'tabAgefodd', $langs->trans('Proposal'), 0, 'propal');
	$element_type = 'propal';
	$element_id = $search_propalid;

	$morehtmlref = '<div class="refidno">';
	// Ref customer
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $propal->ref_client, $propal, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $propal->ref_client, $propal, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>' . $langs->trans('ThirdParty') . ' : ' . $propal->thirdparty->getNomUrl(1);
	$morehtmlref .= '</div>';
	if (function_exists('dol_banner_tab')) {
		dol_banner_tab($propal, 'search_propalref', $linkback, 1, 'ref', 'ref', $morehtmlref);
	}
}

if ($action == 'link_element' && ($user->rights->agefodd->modifier || $user->rights->fournisseur->facture->creer)) {
	$agf_fin = new Agefodd_session_element($db);
	$sessionid = GETPOST('session_id', 'int');
	if (! empty($sessionid)) {
		$agf_fin->fk_session_agefodd = $sessionid;
		$agf_fin->fk_soc = $object_socid;
		$agf_fin->element_type = $element_type;
		$agf_fin->fk_element = $element_id;
		$result = $agf_fin->create($user);
		if ($result < 0) {
			setEventMessage($agf_fin->error, 'errors');
		}
	}
}

if (GETPOST('link_site', 'none') && ($user->rights->agefodd->modifier || $user->rights->fournisseur->facture->creer)) {
	$id = GETPOST('session_id_site', 'int');
	if ($id > 0) {
		$sess = new Agsession($db);
		$sess->fetch($id);
		$placeid = $sess->fk_session_place;
		$type = 'invoice_supplier_room';

		// Create link with the session/customer
		$session_invoice = new Agefodd_session_element($db);
		$session_invoice->fk_soc = $object_socid;
		$session_invoice->fk_session_agefodd = $id;
		if (! empty($fournorder)) {
			$session_invoice->fk_element = $fournorder->id;
			$session_invoice->element_type = 'order_supplier_room';
		} else {
			$session_invoice->fk_element = $fourninvoice->id;
			$session_invoice->element_type = 'invoice_supplier_room';
		}
		$result = $session_invoice->create($user);
		if ($result < 0) {
			setEventMessage($session_invoice->error, 'errors');
		}
		if (! empty($fourninvoice)) {
			// Update training cost
			$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0, array(
					'\'invoice_supplier_room\'',
					'\'invoice_supplierline_room\''
			));
			if ($result < 0) {
				setEventMessage($session_invoice->error, 'errors');
			}
			if (count($session_invoice->lines) > 0) {
				$suplier_invoice = new FactureFournisseur($db);
				$totalht = 0;
				foreach ( $session_invoice->lines as $line ) {
					if ($line->element_type == 'invoice_supplier_room') {
						$suplier_invoice->fetch($line->fk_element);

						$total_ht += $suplier_invoice->total_ht;
					} else {
						$suplier_invoiceline->fetch($line->fk_element);

						$total_ht += $suplier_invoiceline->total_ht;
					}
				}
			}
			$sess->cost_site = $total_ht;
			$result = $sess->update($user, 1);
			if ($result < 0) {
				setEventMessage($agf->error, 'errors');
			}
		}
	}
}

if (GETPOST('link_formateur', 'none') && ($user->rights->agefodd->modifier || $user->rights->fournisseur->facture->creer)) {

	$id = GETPOST('session_id_form', 'none');
	if ($id > 0) {
		$sess = new Agsession($db);
		$sess->fetch($id);
		$opsid = GETPOST('opsid', 'int');

		// Create link with the session/customer
		$session_invoice = new Agefodd_session_element($db);
		$session_invoice->fk_soc = $object_socid;
		$session_invoice->fk_session_agefodd = $id;

		if (! empty($fournorder)) {
			$session_invoice->fk_element = $fournorder->id;
			$session_invoice->element_type = 'order_supplier_trainer';
		} else {
			$session_invoice->fk_element = $fourninvoice->id;
			$session_invoice->element_type = 'invoice_supplier_trainer';
		}
		$session_invoice->fk_sub_element = $opsid;
		$result = $session_invoice->create($user);
		if ($result < 0) {
			setEventMessage($session_invoice->error, 'errors');
		}
		if (! empty($fourninvoice)) {
			// Update training cost
			$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0, array(
					'\'invoice_supplier_trainer\'',
					'\'invoice_supplierline_trainer\''
			));
			if ($result < 0) {
				setEventMessage($session_invoice->error, 'errors');
			}
			if (count($session_invoice->lines) > 0) {
				$suplier_invoice = new FactureFournisseur($db);
				$suplier_invoiceline = new SupplierInvoiceLine($db);
				$total_ht = 0;
				foreach ( $session_invoice->lines as $line ) {
					if ($line->element_type == 'invoice_supplier_trainer') {
						$suplier_invoice->fetch($line->fk_element);

						$total_ht += $suplier_invoice->total_ht;
					} else {
						$suplier_invoiceline->fetch($line->fk_element);

						$total_ht += $suplier_invoiceline->total_ht;
					}
				}
			}
			$sess->cost_trainer = $total_ht;
			$result = $sess->update($user, 1);
			if ($result < 0) {
				setEventMessage($agf->error, 'errors');
			}
		}
	}
}

if (GETPOST('link_mission', 'none') && ($user->rights->agefodd->modifier || $user->rights->fournisseur->facture->creer)) {

	$id = GETPOST('session_id_missions', 'none');
	if ($id > 0) {
		$sess = new Agsession($db);
		$sess->fetch($id);
		$opsid = GETPOST('opsid', 'int');

		// Create link with the session/customer
		$session_invoice = new Agefodd_session_element($db);
		$session_invoice->fk_soc = $object_socid;
		$session_invoice->fk_session_agefodd = $id;
		$session_invoice->fk_element = $fourninvoice->id;
		if (! empty($fournorder)) {
			$session_invoice->fk_element = $fournorder->id;
			$session_invoice->element_type = 'order_supplier_missions';
		} else {
			$session_invoice->fk_element = $fourninvoice->id;
			$session_invoice->element_type = 'invoice_supplier_missions';
		}
		$result = $session_invoice->create($user);
		if ($result < 0) {
			setEventMessage($session_invoice->error, 'errors');
		}
		if (! empty($fourninvoice)) {
			// Update training cost
			$result = $session_invoice->fetch_by_session_by_thirdparty($id, 0, array(
					'\'invoice_supplier_missions\'',
					'\'invoice_supplierline_missions\''
			));
			if ($result < 0) {
				setEventMessage($session_invoice->error, 'errors');
			}
			if (count($session_invoice->lines) > 0) {
				$suplier_invoice = new FactureFournisseur($db);
				$total_ht = 0;
				foreach ( $session_invoice->lines as $line ) {
					if ($line->element_type == 'invoice_supplier_missions') {
						$suplier_invoice->fetch($line->fk_element);

						$total_ht += $suplier_invoice->total_ht;
					} else {
						$suplier_invoiceline->fetch($line->fk_element);

						$total_ht += $suplier_invoiceline->total_ht;
					}
				}
			}
			$sess->cost_trainer = $total_ht;
			$result = $sess->update($user, 1);
			if ($result < 0) {
				setEventMessage($agf->error, 'errors');
			}
		}
	}
}

if ($action == 'unlink' && ($user->rights->agefodd->modifier || $user->rights->fournisseur->facture->creer)) {

	$agf_liste = new Agefodd_session_element($db);
	$result = $agf_liste->fetch($idelement);
	$ref = $agf_liste->facfournnumber;

	print $form->formconfirm($_SERVER['PHP_SELF'] . '?socid=' . $object_socid . '&idsess=' . $idsess . '&idelement=' . $idelement . $urlcomplete, $langs->trans("AgfConfirmUnlink"), '', "unlink_confirm", '', '', 1);
}

$agf = new Agsession($db);
$resql = $agf->fetch_all_by_order_invoice_propal($sortorder, $sortfield, $limit, $offset, $search_orderid, $search_invoiceid, $search_propalid, $search_fourninvoiceid, $search_fournorderid);

if ($resql < 0) {
	setEventMessage($agf->error, 'errors');
}

$session_array_id = array();

if ($resql != - 1) {
	$num = $resql;

	$menu = $langs->trans("AgfMenuSessAct");

	dol_fiche_end();

	print_barre_liste($menu, $page, $_SERVER['PHP_SELF'], '&search_propalid=' . $search_propalid . '&search_orderid=' . $search_orderid . '&search_invoiceid=' . $search_invoiceid . '&search_fourninvoiceid=' . $search_fourninvoiceid . '&search_fournorderid=' . $search_fournorderid, $sortfield,
			$sortorder, '', $num);

	$i = 0;
	print '<form method="get" action="' . $_SERVER ['PHP_SELF'] .'" name="search_form">' . "\n";
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	$arg_url = '&page=' . $page . '&search_propalid=' . $search_propalid . '&search_orderid=' . $search_orderid . '&search_invoiceid=' . $search_invoiceid . '&search_fourninvoiceid=' . $search_fourninvoiceid;
	$arg_url .= '&search_fournorderid=' . $search_fournorderid;
	print_liste_field_titre($langs->trans("Id"), $_SERVER['PHP_SELF'], "s.rowid", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Ref"), $_SERVER['PHP_SELF'], "s.ref", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfSessionCommercial"), $_SERVER['PHP_SELF'], "sale.lastname", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfIntitule"), $_SERVER['PHP_SELF'], "c.intitule", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfRefInterne"), $_SERVER['PHP_SELF'], "c.ref", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfDateDebut"), $_SERVER['PHP_SELF'], "s.dated", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfDateFin"), $_SERVER['PHP_SELF'], "s.datef", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfLieu"), $_SERVER['PHP_SELF'], "p.ref_interne", "", $arg_url, '', $sortfield, $sortorder);
	if (! (empty($search_orderref))) {
		print_liste_field_titre($langs->trans("AgfBonCommande"), $_SERVER['PHP_SELF'], "order_dol.ref", '', $arg_url, '', $sortfield, $sortorder);
	}
	if (! (empty($search_invoiceref))) {
		print_liste_field_titre($langs->trans("AgfFacture"), $_SERVER['PHP_SELF'], "invoice.facnumber", '', $arg_url, '', $sortfield, $sortorder);
	}
	if (! (empty($search_fourninvoiceref))) {
		print_liste_field_titre($langs->trans("AgfFacture"), $_SERVER['PHP_SELF'], "invoice.facnumber", '', $arg_url, '', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Type"), $_SERVER['PHP_SELF'], "ord_inv.element_type", '', $arg_url, '', $sortfield, $sortorder);
	}
	if (! (empty($search_fournorderref))) {
		print_liste_field_titre($langs->trans("Order"), $_SERVER['PHP_SELF'], "fournorder.ref", '', $arg_url, '', $sortfield, $sortorder);
		print_liste_field_titre($langs->trans("Type"), $_SERVER['PHP_SELF'], "ord_inv.element_type", '', $arg_url, '', $sortfield, $sortorder);
	}
	if (! (empty($search_propalref))) {
		print_liste_field_titre($langs->trans("Proposal"), $_SERVER['PHP_SELF'], "propal_dol.ref", '', $arg_url, '', $sortfield, $sortorder);
	}
	print '<td>&nbsp;</td>';
	print "</tr>\n";

	// Search bar
	$url_form = $_SERVER["PHP_SELF"];
	$addcriteria = false;
	if (! empty($sortorder)) {
		$url_form .= '?sortorder=' . $sortorder;
		$addcriteria = true;
	}
	if (! empty($sortfield)) {
		if ($addcriteria) {
			$url_form .= '&sortfield=' . $sortfield;
		} else {
			$url_form .= '?sortfield=' . $sortfield;
		}
		$addcriteria = true;
	}
	if (! empty($page)) {
		if ($addcriteria) {
			$url_form .= '&page=' . $page;
		} else {
			$url_form .= '?page=' . $page;
		}
		$addcriteria = true;
	}

	print '<tr class="liste_titre">';

	print '<td>&nbsp;</td>';

	print '<td>&nbsp;</td>';

	print '<td>&nbsp;</td>';

	print '<td>&nbsp;</td>';

	print '<td>&nbsp;</td>';

	print '<td>&nbsp;</td>';

	print '<td>&nbsp;</td>';

	print '<td>&nbsp;</td>';
	if (! (empty($search_orderref))) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_orderref" value="' . $search_orderref . '" size="20">';
		print '</td>';
	}
	if (! (empty($search_invoiceref))) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_invoiceref" value="' . $search_invoiceref . '" size="20">';
		print '</td>';
	}
	if (! (empty($search_fourninvoiceref))) {
		print '<td class="liste_titre">';
		print '<input type="hidden" name="search_fourninvoiceid" value="' . $search_fourninvoiceid . '">';
		print '<input type="text" class="flat" name="search_fourninvoiceref" value="' . $search_fourninvoiceref . '" size="20">';
		print '</td>';
		print '<td></td>';
	}
	if (! (empty($search_fournorderref))) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_fournorderref" value="' . $search_fournorderref . '" size="20">';
		print '</td>';
		print '<td></td>';
	}
	if (! (empty($search_propalref))) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_propalref" value="' . $search_propalref . '" size="20">';
		print '</td>';
	}
	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	print '</td>';

	print "</tr>\n";

	$var = true;
	foreach ( $agf->lines as $line ) {
		$session_array_id[$line->rowid] = $line->rowid;
		// Affichage tableau des sessions
		$var = ! $var;
		print "<tr $bc[$var]>";
		// Calcul de la couleur du lien en fonction de la couleur définie sur la session
		// http://www.w3.org/TR/AERT#color-contrast
		// SI ((Red value X 299) + (Green value X 587) + (Blue value X 114)) / 1000 < 125 ALORS AFFICHER DU BLANC (#FFF)
		$couleur_rgb = agf_hex2rgb($line->color);
		$color_a = '';
		if ($line->color && ((($couleur_rgb[0] * 299) + ($couleur_rgb[1] * 587) + ($couleur_rgb[2] * 114)) / 1000) < 125)
			$color_a = ' style="color: #FFFFFF;"';

		if (empty($search_fourninvoiceref)) {
			print '<td  style="background: #' . $line->color . '"><a' . $color_a . ' href="document.php?id=' . $line->rowid . '&socid=' . $object_socid . '&mainmenu=agefodd">' . img_object($langs->trans("AgfShowDetails"), "service") . ' ' . $line->rowid . '</a></td>';
		} else {
			print '<td  style="background: #' . $line->color . '"><a' . $color_a . ' href="cost.php?id=' . $line->rowid . '&socid=' . $object_socid . '&mainmenu=agefodd">' . img_object($langs->trans("AgfShowDetails"), "service") . ' ' . $line->rowid . '</a></td>';
		}
		if (empty($search_fourninvoiceref)) {
			print '<td  style="background: #' . $line->color . '"><a' . $color_a . ' href="document.php?id=' . $line->rowid . '&socid=' . $object_socid . '&mainmenu=agefodd">' . img_object($langs->trans("AgfShowDetails"), "service") . ' ' . $line->refsession . '</a></td>';
		} else {
			print '<td  style="background: #' . $line->color . '"><a' . $color_a . ' href="cost.php?id=' . $line->rowid . '&socid=' . $object_socid . '&mainmenu=agefodd">' . img_object($langs->trans("AgfShowDetails"), "service") . ' ' . $line->refsession . '</a></td>';
		}
		if (! empty($line->fk_user_com))
		{
			$comm = new User($db);
			$comm->fetch($line->fk_user_com);
			if (! empty($comm->id))
			{
				print '<td>'.$comm->getNomUrl().'</td>';
			}
			else
			{
				print '<td>&nbsp;</td>';
			}
		}
		else
		{
			print '<td>&nbsp;</td>';
		}
		print '<td>' . stripslashes(dol_trunc($line->intitule, 60)) . '</td>';
		print '<td>' . $line->ref . '</td>';
		print '<td>' . dol_print_date($line->dated, 'daytext') . '</td>';
		print '<td>' . dol_print_date($line->datef, 'daytext') . '</td>';
		print '<td>' . stripslashes($line->ref_interne) . '</td>';
		if (! (empty($search_orderref))) {
			print '<td>' . $line->orderref . '</td>';
		}
		if (! (empty($search_invoiceref))) {
			print '<td>' . $line->invoiceref . '</td>';
		}
		if (! (empty($search_fourninvoiceref))) {
			print '<td>' . $line->fourninvoiceref . '</td><td>'. $langs->trans($line->element_type) . '</td>';
		}
		if (! (empty($search_fournorderref))) {

			print '<td>' . $line->fournorderref . '</td><td>' . $langs->trans($line->element_type) . '</td>';
		}
		if (! (empty($search_propalref))) {
			print '<td>' . $line->propalref . '</td>';
		}

		if (! (empty($search_orderref))) {
			$idfin = $search_orderid;
			$type = 'bc';
		}
		if (! (empty($search_invoiceref))) {
			$idfin = $search_invoiceid;
			$type = 'fac';
		}
		if (! (empty($search_fourninvoiceref))) {
			$idfin = $search_fourninvoiceid;
			$type = "fourn";
		}
		if (! (empty($search_fournorderref))) {
			$idfin = $search_fournorderid;
			$type = "fourn";
		}
		if (! (empty($search_propalref))) {
			$idfin = $search_propalid;
			$type = 'prop';
		}

		$agf_fin = new Agefodd_session_element($db);
		if ($type !== 'fourn') {
			$agf_fin->fetch_element_by_id($idfin, $type, $line->rowid);
			$idelement = $agf_fin->lines[0]->id;
		} else {
			if (! empty($search_fourninvoiceref)) {
				$TFournTypes = array(
						'invoice_supplier_trainer',
						'invoice_supplier_room',
						'invoice_supplier_missions',
				);
				foreach ( $TFournTypes as $type ) {
					$agf_fin->fetch_element_by_id($idfin, $type, $line->rowid);
					if (! empty($agf_fin->lines))
						break;
				}
				$idelement = $agf_fin->lines[0]->id;
				if (property_exists($line, 'agelemetnid') && ! empty($line->agelemetnid)) {
					$id_element = $line->agelemetnid;
				}
			} else {
				$type = 'order_supplier';
				$agf_fin->fetch_element_by_id($idfin, $type, $line->rowid);
				$idelement = $agf_fin->lines[0]->id;
			}
		}
		if (! empty($line->id_element))
			$id_element = $line->id_element;

		if (! empty($line->agelemetnid))
			$id_element = $line->agelemetnid;

		print '<td align="right">';
		if ($user->rights->agefodd->modifier || $user->rights->fournisseur->facture->creer) {
			$legende = (empty($search_fourninvoiceref)) ? $langs->trans("AgfFactureUnselectFac") : $langs->trans("AgfFactureUnselectSuplierInvoice");
			print '<a href="' . $_SERVER['PHP_SELF'] . '?action=unlink&idelement=' . $id_element . '&idsess=' . $line->rowid . '&socid=' . $object_socid . $urlcomplete . '" alt="' . $legende . '" title="' . $legende . '">';
			print '<img src="' . dol_buildpath('/agefodd/img/unlink.png', 1) . '" border="0" align="absmiddle" hspace="2px" ></a>';
		}
		print '</td>';
		print "</tr>\n";

		$i ++;
	}

	print "</table>";
} else {
	setEventMessage($agf->error, 'errors');
}
if (! empty($search_fournorderid)) {
	$excludeSessions=array();
	$sql = "SELECT s.rowid, c.intitule, c.ref_interne as trainingrefinterne, p.ref_interne, s.dated, s.ref as sessionref
            FROM " . MAIN_DB_PREFIX . "agefodd_session as s
            INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c ON c.rowid = s.fk_formation_catalogue
            INNER JOIN " . MAIN_DB_PREFIX . "agefodd_place as p ON p.rowid = s.fk_session_place
			INNER JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf ON sf.fk_session = s.rowid
			INNER JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f ON f.rowid = sf.fk_agefodd_formateur
			INNER JOIN " . MAIN_DB_PREFIX . "socpeople as socpf ON f.fk_socpeople = socpf.rowid AND socpf.fk_soc=" . $object_socid . "
            WHERE s.entity IN (0,". getEntity('agefodd') .") AND s.status NOT IN (4,3)";
	if (is_array($excludeSessions) && count($excludeSessions) > 0) {
		$sql .= " AND s.rowid NOT IN (" . implode(",", $excludeSessions) . ")";
	}
	$sql .= " GROUP BY s.rowid, s.dated, s.status, p.ref_interne, c.intitule, c.ref_interne
            ORDER BY s.dated ASC";

	$resql = $db->query($sql);
	if ($resql) {
		while ( $obj = $db->fetch_object($resql) ) {
			! empty($obj->trainingrefinterne) ? $training_ref_interne = ' - (' . $obj->trainingrefinterne . ')' : $training_ref_interne = '';
			$sessions [$obj->rowid] = $obj->rowid.'('.$obj->sessionref.')'.' '. $obj->ref_interne.$training_ref_interne. ' - ' . $obj->intitule . ' - ' . dol_print_date($db->jdate($obj->dated), 'daytext');
		}
	}

	if (! empty($conf->global->AGF_ASSOCIATE_PROPAL_WITH_NON_RELATED_SESSIONS)) {
		$sessions=array();
		$excludeSessions = array();
		foreach ( $agf->lines as $line )
			$excludeSessions[] = ( int ) $line->rowid;
		$excludeSessions = array_merge($excludeSessions, array_keys($sessions));

		$sql = "SELECT s.rowid, c.intitule, c.ref_interne as trainingrefinterne, p.ref_interne, s.dated, s.ref as sessionref
            FROM " . MAIN_DB_PREFIX . "agefodd_session as s
            LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c ON c.rowid = s.fk_formation_catalogue
            LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p ON p.rowid = s.fk_session_place
            WHERE s.entity IN (0,". getEntity('agefodd') .")";
		if (is_array($excludeSessions) && count($excludeSessions) > 0) {
			$sql .= " AND s.rowid NOT IN (" . implode(",", $excludeSessions) . ")";
		}
		$sql .= " GROUP BY s.rowid, s.dated, s.status, p.ref_interne, c.intitule, c.ref_interne
            ORDER BY s.dated ASC";

		$resql = $db->query($sql);
		if ($resql) {
			while ( $obj = $db->fetch_object($resql) ) {
				! empty($obj->trainingrefinterne) ? $training_ref_interne = ' - (' . $obj->trainingrefinterne . ')' : $training_ref_interne = '';
				$sessions [$obj->rowid] = $obj->rowid.'('.$obj->sessionref.')'.' '. $obj->ref_interne.$training_ref_interne. ' - ' . $obj->intitule . ' - ' . dol_print_date($db->jdate($obj->dated), 'daytext');
			}
		}
	}

	$sql2 = "SELECT sess.rowid as sessid, sess.dated, c.intitule, c.ref_interne as trainingrefinterne, p.rowid as pid, p.ref_interne, sess.ref as sessionref
        FROM " . MAIN_DB_PREFIX . "agefodd_session as sess
        LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c ON c.rowid = sess.fk_formation_catalogue
        LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p ON p.rowid = sess.fk_session_place
        LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON p.fk_societe = s.rowid
        LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socp ON p.fk_socpeople = socp.rowid
        WHERE p.entity IN (0," . getEntity('agefodd') . ")
        AND p.fk_societe = " . $object_socid;

	if (is_array($session_array_id) && count($session_array_id) > 0) {
		$sql2 .= " AND sess.rowid NOT IN (" . implode(",", $session_array_id) . ")";
	}

    $sql2 .= " ORDER BY sess.dated  ASC";

	$resql2 = $db->query($sql2);
	if ($resql2) {
		while ( $obj = $db->fetch_object($resql2) ) {
			! empty($obj->trainingrefinterne) ? $training_ref_interne = ' - (' . $obj->trainingrefinterne . ')' : $training_ref_interne = '';
			$sessionsSite[$obj->sessid] = $obj->sessid . ' ' . $obj->ref_interne . $training_ref_interne . ' - ' . $obj->intitule . ' - ' . dol_print_date($db->jdate($obj->dated), 'daytext');
		}
	}

	if ($user->rights->agefodd->modifier || $user->rights->fournisseur->facture->creer) {
		print '<table class="noborder" width="100%">';
		print '<tr>';
		print '<th>Type</th>';
		print '<th>Session</th>';
		print '<th>' . $langs->trans('Link') . '</th>';
		print '</tr>';
		print '<tr>';
		print '<td align="center">' . $langs->trans('AgfFormateur') . '</td>';
		print '<td align="center">';
		print '<input type="hidden" id="opsid" name="opsid">';
		print '<select id="ids" style="display:none">';
		foreach ( $sessions as $k => $v )
			print '<option value=' . $k . '>' . $v . '</option>';
		print '</select>';
		print $form->selectarray('session_id_form', $sessions, '', 1, 0, 0, '', 0, 0, 0, '', '', 1);
		print '</td>';
		print '<td align="center">';
		print '<input type="submit" value="' . $langs->trans('AgfSelectAgefoddSessionToLink') . '" class="butAction" name="link_formateur"/>';
		print '</td>';
		print '</tr>';
		print '<tr>';
		print '<td align="center">' . $langs->trans('AgfTripAndMissions') . '</td>';
		print '<td align="center">';
		print $form->selectarray('session_id_missions', $sessions, '', 1, 0, 0, '', 0, 0, 0, '', '', 1);
		print '</td>';
		print '<td align="center">';
		print '<input type="submit" value="' . $langs->trans('AgfSelectAgefoddSessionToLink') . '" class="butAction" name="link_mission"/>';
		print '</td>';
		print '</tr>';
		print '<tr>';
		print '<td align="center">' . $langs->trans('AgfLieu') . '</td>';
		print '<td align="center">';
		print $form->selectarray('session_id_site', $sessionsSite, '', 1, 0, 0, '', 0, 0, 0, '', '', 1);
		print '</td>';
		print '<td align="center">';
		print '<input type="submit" value="' . $langs->trans('AgfSelectAgefoddSessionToLink') . '" class="butAction" name="link_site"/>';
		print '</td>';
		print '</tr>';
		print "</table>";
	}

	?>

<script type="text/javascript">
		$(document).ready(function(){
			$('#session_id_form').change(function(){
				sessid = $(this).val();
				$('#opsid').val($('#ids').find('[value='+sessid+']').html());
				console.log($('#ids').find('[value='+sessid+']').html());
			});
		});
	</script>

<?php
} elseif (empty($search_fourninvoiceref)) {
	$filter = array();
	$soc = new Societe($db);
	$result = $soc->fetch($object_socid);
	if ($result < 0) {
		setEventMessage($soc->error, 'errors');
	}
	// $filter['so.nom']=$soc->name;
	if (count($session_array_id) > 0) {
		$filter['!s.rowid'] = implode(',', $session_array_id);
	}
	$select_array = array(
			'thirdparty' => $langs->trans('ThirdParty'),
			'trainee' => $langs->trans('AgfParticipant'),
			'requester' => $langs->trans('AgfTypeRequester'),
			'trainee_requester' => $langs->trans('AgfTypeTraineeRequester'),
			'opca' => $langs->trans('AgfMailTypeContactOPCA')
	);

	$sessions = array();
	foreach ( $select_array as $key => $val ) {
		$filter['type_affect'] = $key;
		$agf_session = new Agsession($db);
		$result = $agf_session->fetch_all_by_soc($object_socid, "ASC", "s.dated", 0, 0, $filter);
		if ($result < 0) {
			setEventMessage($soc->error, 'errors');
		}

		foreach ( $agf_session->lines as $line_session ) {
			! empty($line_session->training_ref_interne) ? $training_ref_interne = ' - (' . $line_session->training_ref_interne . ')' : $training_ref_interne = '';
			$sessions[$line_session->rowid] = $line_session->rowid . '(' . $line_session->sessionref . ')' . ' ' . $line_session->ref_interne . $training_ref_interne . ' - ' . $line_session->intitule . ' - ' . dol_print_date($line_session->dated, 'daytext');
		}
	}

	if (! empty($conf->global->AGF_ASSOCIATE_PROPAL_WITH_NON_RELATED_SESSIONS)) {
		$excludeSessions = array();
		foreach ( $agf->lines as $line )
			$excludeSessions[] = ( int ) $line->rowid;
		$excludeSessions = array_merge($excludeSessions, array_keys($sessions));

		$sql = "SELECT s.rowid, c.intitule, c.ref_interne as trainingrefinterne, p.ref_interne, s.dated, s.ref as sessionref
            FROM " . MAIN_DB_PREFIX . "agefodd_session as s
            LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c ON c.rowid = s.fk_formation_catalogue
            LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p ON p.rowid = s.fk_session_place
            WHERE s.entity IN (0,". getEntity('agefodd') .") ";

		if (is_array($excludeSessions) && count($excludeSessions) > 0) {
			$sql .= " AND s.rowid NOT IN (" . implode(",", $excludeSessions) . ")";
		}
		$sql .= " GROUP BY s.rowid, s.dated, s.status, p.ref_interne, c.intitule, c.ref_interne
            ORDER BY s.dated ASC";

		$resql = $db->query($sql);
		if ($resql) {
			while ( $obj = $db->fetch_object($resql) ) {
				! empty($obj->trainingrefinterne) ? $training_ref_interne = ' - (' . $obj->trainingrefinterne . ')' : $training_ref_interne = '';
				$sessions[$obj->rowid] = $obj->rowid . '(' . $obj->sessionref . ')' . ' ' . $obj->ref_interne . $training_ref_interne . ' - ' . $obj->intitule . ' - ' . dol_print_date($db->jdate($obj->dated), 'daytext');
			}
		}
	}

	if ($user->rights->agefodd->modifier || $user->rights->fournisseur->facture->creer) {
		print '<table class="noborder" width="100%">';
		print '<tr>';
		print '<td align="right">';
		print $form->selectarray('session_id', $sessions, GETPOST('session_id', 'none'), 1, 0, 0, '', 0, 0, 0, '', '', 1);
		print '</td>';
		print '<td align="left">';
		print '<input type="submit" value="' . $langs->trans('AgfSelectAgefoddSessionToLink') . '" class="butAction" name="link_element"/>';
		print '</td>';
		print '</tr>';
		print "</table>";
	}
} else {
	$sessids = array();
	$sessionsForm = array();
	$sessionsSite = array();
	$sessionsMissions = array();

	// session dans lequel un formateur est un contact du tiers $session_array_id
	$sql = "SELECT s.rowid as sessid, sf.rowid as opsid, c.intitule, c.ref_interne as trainingrefinterne, p.ref_interne, s.dated, sp.lastname as name_socp, sp.firstname as firstname_socp, s.ref as sessionref
        FROM " . MAIN_DB_PREFIX . "agefodd_session as s
        LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c ON c.rowid = s.fk_formation_catalogue
        LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p ON p.rowid = s.fk_session_place
        LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_formateur as sf on s.rowid = sf.fk_session
        LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur as f ON sf.fk_agefodd_formateur = f.rowid
        LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as sp ON f.fk_socpeople = sp.rowid
        LEFT JOIN " . MAIN_DB_PREFIX . "societe as soc ON soc.rowid = sp.fk_soc
        LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON f.fk_user = u.rowid
        LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formateur_type as st ON st.rowid = sf.fk_agefodd_formateur_type
        WHERE soc.rowid = ".$object_socid." AND s.entity IN (0,". getEntity('agefodd') .") AND s.status NOT IN (4,1)";
	if (is_array($session_array_id) && count($session_array_id) > 0) {
		$sql .= " AND s.rowid NOT IN (" . implode(",", $session_array_id) . ") ";
	}
    $sql .= " ORDER BY s.dated ASC";
	$resql = $db->query($sql);
	if ($resql) {
		while ( $obj = $db->fetch_object($resql) ) {
			! empty($obj->trainingrefinterne) ? $training_ref_interne = ' - (' . $obj->trainingrefinterne . ')' : $training_ref_interne = '';
			$sessionsForm[$obj->sessid] = $obj->sessid . '(' . $obj->sessionref . ')' . ' ' . $obj->ref_interne . $training_ref_interne . ' - ' . $obj->intitule . ' - ' . dol_print_date($db->jdate($obj->dated), 'daytext');
			$sessids[$obj->sessid] = $obj->opsid;
		}
	}

	// session dont le lieu appartient au tiers
	$sql2 = "SELECT sess.rowid as sessid, sess.dated, c.intitule, c.ref_interne as trainingrefinterne, p.rowid as pid, p.ref_interne, sess.ref as sessionref
        FROM " . MAIN_DB_PREFIX . "agefodd_session as sess
        LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c ON c.rowid = sess.fk_formation_catalogue
        LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p ON p.rowid = sess.fk_session_place
        LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON p.fk_societe = s.rowid
        LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socp ON p.fk_socpeople = socp.rowid
        WHERE p.entity IN (0," . getEntity('agefodd') . ")
        AND p.fk_societe = " . $object_socid;
	if (is_array($session_array_id) && count($session_array_id) > 0) {
		$sql2 .= " AND sess.rowid NOT IN (" . implode(",", $session_array_id) . ")";
	}
    $sql2 .= " ORDER BY sess.dated ASC";
	$resql2 = $db->query($sql2);
	if ($resql2) {
		while ( $obj = $db->fetch_object($resql2) ) {
			! empty($obj->trainingrefinterne) ? $training_ref_interne = ' - (' . $obj->trainingrefinterne . ')' : $training_ref_interne = '';
			$sessionsSite[$obj->sessid] = $obj->sessid . '(' . $obj->sessionref . ')' . ' ' . $obj->ref_interne . $training_ref_interne . ' - ' . $obj->intitule . ' - ' . dol_print_date($db->jdate($obj->dated), 'daytext');
		}
	}

	$excludeSessions = array();
	$sessions = array();
	foreach ( $agf->lines as $line )
		$excludeSessions[] = ( int ) $line->rowid;

	$sql = "SELECT s.rowid, c.intitule, c.ref_interne as trainingrefinterne, p.ref_interne, s.dated, s.ref as sessionref
            FROM " . MAIN_DB_PREFIX . "agefodd_session as s
            LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_formation_catalogue as c ON c.rowid = s.fk_formation_catalogue
            LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_place as p ON p.rowid = s.fk_session_place
            WHERE s.entity IN (0," . getEntity('agefodd') . ") AND s.status IN (1,2,5)";
	if (is_array($excludeSessions) && count($excludeSessions) > 0) {
		$sql .= " AND s.rowid NOT IN (" . implode(",", $excludeSessions) . ")";
	}
	$sql .= " GROUP BY s.rowid, s.dated, s.status, p.ref_interne, c.intitule, c.ref_interne
            ORDER BY s.dated ASC";

	$resql = $db->query($sql);
	if ($resql) {
		while ( $obj = $db->fetch_object($resql) ) {
			! empty($obj->trainingrefinterne) ? $training_ref_interne = ' - (' . $obj->trainingrefinterne . ')' : $training_ref_interne = '';
			$sessionsMissions[$obj->rowid] = $obj->rowid . '(' . $obj->sessionref . ')' . ' ' . $obj->ref_interne . $training_ref_interne . ' - ' . $obj->intitule . ' - ' . dol_print_date($db->jdate($obj->dated), 'daytext');
		}
	}

	if ($user->rights->agefodd->modifier || $user->rights->fournisseur->facture->creer) {
		print '<table class="noborder" width="100%">';
		print '<tr>';
		print '<th>Type</th>';
		print '<th>Session</th>';
		print '<th>' . $langs->trans('Link') . '</th>';
		print '</tr>';
		print '<tr>';
		print '<td align="center">' . $langs->trans('AgfFormateur') . '</td>';
		print '<td align="center">';
		print '<input type="hidden" id="opsid" name="opsid">';
		print '<select id="ids" style="display:none">';
		foreach ( $sessids as $k => $v )
			print '<option value=' . $k . '>' . $v . '</option>';
		print '</select>';
		print $form->selectarray('session_id_form', $sessionsForm, '', 1, 0, 0, '', 0, 0, 0, '', '', 1);
		print '</td>';
		print '<td align="center">';
		print '<input type="submit" value="' . $langs->trans('AgfSelectAgefoddSessionToLink') . '" class="butAction" name="link_formateur"/>';
		print '</td>';
		print '</tr>';
		print '<tr>';
		print '<td align="center">' . $langs->trans('AgfTripAndMissions') . '</td>';
		print '<td align="center">';
		print $form->selectarray('session_id_missions', $sessionsMissions, '', 1, 0, 0, '', 0, 0, 0, '', '', 1);
		print '</td>';
		print '<td align="center">';
		print '<input type="submit" value="' . $langs->trans('AgfSelectAgefoddSessionToLink') . '" class="butAction" name="link_mission"/>';
		print '</td>';
		print '</tr>';
		print '<tr>';
		print '<td align="center">' . $langs->trans('AgfLieu') . '</td>';
		print '<td align="center">';
		print $form->selectarray('session_id_site', $sessionsSite, '', 1, 0, 0, '', 0, 0, 0, '', '', 1);
		print '</td>';
		print '<td align="center">';
		print '<input type="submit" value="' . $langs->trans('AgfSelectAgefoddSessionToLink') . '" class="butAction" name="link_site"/>';
		print '</td>';
		print '</tr>';
		print "</table>";
	}

	?>

<script type="text/javascript">
		$(document).ready(function(){
			$('#session_id_form').change(function(){
				sessid = $(this).val();
				$('#opsid').val($('#ids').find('[value='+sessid+']').html());
				console.log($('#ids').find('[value='+sessid+']').html());
			});
		});
	</script>

<?php
}
print '</form>';

if ($user->rights->agefodd->creer && ! empty($search_propalid)) {
	print '<div class="tabsAction">';
	print '<a class="butAction" href="' . dol_buildpath('/agefodd/session/card.php', 1) . '?mainmenu=agefodd&action=create&fk_propal=' . $search_propalid . '&fk_soc=' . $object_socid . '">' . $langs->trans('AgfMenuSessNew') . '</a>';
	print '</div>';
}

if ($user->rights->agefodd->creer && ! empty($search_orderid)) {
	print '<div class="tabsAction">';
	print '<a class="butAction" href="' . dol_buildpath('/agefodd/session/card.php', 1) . '?mainmenu=agefodd&action=create&fk_order=' . $search_orderid . '&fk_soc=' . $object_socid . '">' . $langs->trans('AgfMenuSessNew') . '</a>';
	print '</div>';
}

llxFooter();
$db->close();
