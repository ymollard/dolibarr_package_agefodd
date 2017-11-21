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
require_once (DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');

// Search criteria
$search_orderid = GETPOST('search_orderid', 'int');
$search_invoiceid = GETPOST('search_invoiceid', 'int');
$search_fourninvoiceid = GETPOST('search_fourninvoiceid', 'int');
$search_orderref = GETPOST('search_orderref', 'alpha');
$search_invoiceref = GETPOST('search_invoiceref', 'alpha');
$search_propalref = GETPOST('search_propalref', 'alpha');
$search_propalid = GETPOST('search_propalid', 'alpha');

$link_element = GETPOST("link_element");
if (! empty($link_element)) {
	$action = 'link_element';
}

$langs->load('bills');

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) {
	$search_orderid = '';
	$search_invoiceid = '';
	$search_fourninvoiceid = '';
	$search_orderref = '';
	$search_invoiceref = '';
	$search_propalref = '';
	$search_propalid = '';
}

if (empty($sortorder))
	$sortorder = "DESC";
if (empty($sortfield))
	$sortfield = "s.rowid";

if ($page == - 1) {
	$page = 0;
}

$limit = $conf->liste_limit;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

$title = $langs->trans("AgfMenuSessByInvoiceOrder");
llxHeader('', $title);

if (! empty($search_orderid)) {
	$order = new Commande($db);
	$order->fetch($search_orderid);
	$search_orderref = $order->ref;
	$object_socid = $order->socid;
}

if (! empty($search_invoiceid)) {
	$invoice = new Facture($db);
	$invoice->fetch($search_invoiceid);
	$search_invoiceref = $invoice->ref;
	$object_socid = $invoice->socid;
}

if (! empty($search_fourninvoiceid)) {
	$fourninvoice = new FactureFournisseur($db);
	$fourninvoice->fetch($search_fourninvoiceid);
	$search_fourninvoiceref = $fourninvoice->ref;
	$object_socid = $fourninvoice->socid;
}

if (! empty($search_orderref)) {
	$order = new Commande($db);
	$order->fetch('', $search_orderref);
	$search_orderid = $order->id;
	$object_socid = $order->socid;
}

if (! empty($search_invoiceref)) {
	$invoice = new Facture($db);
	$invoice->fetch('', $search_invoiceref);
	$search_invoiceid = $invoice->id;
	$object_socid = $invoice->socid;
}

if (! empty($search_fourninvoiceref)) {
	$fourninvoice = new FactureFournisseur($db);
	$fourninvoice->fetch('', $search_fourninvoiceref);
	$search_fourninvoiceid = $fourninvoice->id;
	$object_socid = $fourninvoice->socid;
}

if (! empty($search_propalref)) {
	$propal = new Propal($db);
	$propal->fetch('', $search_propalref);
	$search_propalid = $propal->id;
	$object_socid = $propal->socid;
}

if (! empty($search_propalid)) {
	$propal = new Propal($db);
	$propal->fetch($search_propalid, '');
	$search_propalref = $propal->ref;
	$object_socid = $propal->socid;
}

if (! empty($search_orderid) || ! empty($search_orderref)) {
	require_once DOL_DOCUMENT_ROOT . '/core/lib/order.lib.php';
	$head = commande_prepare_head($order);
	dol_fiche_head($head, 'tabAgefodd', $langs->trans("AgfMenuSessByInvoiceOrder"), 0, 'order');
	$element_type = 'order';
	$element_id = $search_orderid;
}

if (! empty($search_invoiceid) || ! empty($search_invoiceref)) {
	require_once DOL_DOCUMENT_ROOT . '/core/lib/invoice.lib.php';
	$head = facture_prepare_head($invoice);
	dol_fiche_head($head, 'tabAgefodd', $langs->trans('AgfMenuSessByInvoiceOrder'), 0, 'bill');
	$element_type = 'invoice';
	$element_id = $search_invoiceid;
}

if (! empty($search_fourninvoiceid) || ! empty($search_fourninvoiceref)) {
	require_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
	$head = facturefourn_prepare_head($fourninvoice);
	dol_fiche_head($head, 'tabAgefodd', $langs->trans('AgfMenuSessByInvoiceOrder'), 0, 'bill');
}

if (! empty($search_propalref) || ! empty($search_propalid)) {
	require_once DOL_DOCUMENT_ROOT . '/core/lib/propal.lib.php';
	$head = propal_prepare_head($propal);
	dol_fiche_head($head, 'tabAgefodd', $langs->trans('AgfMenuSessByInvoiceOrder'), 0, 'propal');
	$element_type = 'propal';
	$element_id = $search_propalid;
}

if ($action == 'link_element') {
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

$agf = new Agsession($db);
$resql = $agf->fetch_all_by_order_invoice_propal($sortorder, $sortfield, $limit, $offset, $search_orderid, $search_invoiceid, $search_propalid, $search_fourninvoiceid);

$session_array_id = array ();

if ($resql != - 1) {
	$num = $resql;
	
	$menu = $langs->trans("AgfMenuSessAct");
	
	print_barre_liste($menu, $page, $_SERVEUR['PHP_SELF'], '&search_propalid=' . $search_propalid . '&search_orderid=' . $search_orderid . '&search_invoiceid=' . $search_invoiceid . '&search_fourninvoiceid=' . $search_fourninvoiceid, $sortfield, $sortorder, '', $num);
	
	$i = 0;
	print '<form method="get" action="' . $url_form . '" name="search_form">' . "\n";
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	$arg_url = '&page=' . $page . '&search_propalid=' . $search_propalid . '&search_orderid=' . $search_orderid . '&search_invoiceid=' . $search_invoiceid . '&search_fourninvoiceid=' . $search_fourninvoiceid;
	print_liste_field_titre($langs->trans("Id"), $_SERVEUR['PHP_SELF'], "s.rowid", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfIntitule"), $_SERVEUR['PHP_SELF'], "c.intitule", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfRefInterne"), $_SERVEUR['PHP_SELF'], "c.ref", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfDateDebut"), $_SERVEUR['PHP_SELF'], "s.dated", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfDateFin"), $_SERVEUR['PHP_SELF'], "s.datef", "", $arg_url, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AgfLieu"), $_SERVEUR['PHP_SELF'], "p.ref_interne", "", $arg_url, '', $sortfield, $sortorder);
	if (! (empty($search_orderref))) {
		print_liste_field_titre($langs->trans("AgfBonCommande"), $_SERVEUR['PHP_SELF'], "order_dol.ref", '', $arg_url, '', $sortfield, $sortorder);
	}
	if (! (empty($search_invoiceref))) {
		print_liste_field_titre($langs->trans("AgfFacture"), $_SERVEUR['PHP_SELF'], "invoice.facnumber", '', $arg_url, '', $sortfield, $sortorder);
	}
	if (! (empty($search_fourninvoiceref))) {
		print_liste_field_titre($langs->trans("AgfFacture"), $_SERVEUR['PHP_SELF'], "invoice.facnumber", '', $arg_url, '', $sortfield, $sortorder);
	}
	if (! (empty($search_propalref))) {
		print_liste_field_titre($langs->trans("Proposal"), $_SERVEUR['PHP_SELF'], "propal_dol.ref", '', $arg_url, '', $sortfield, $sortorder);
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
		print '<input type="text" class="flat" name="search_fourninvoiceref" value="' . $search_fourninvoiceref . '" size="20">';
		print '</td>';
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
		
		print '<td  style="background: #' . $line->color . '"><a' . $color_a . ' href="document.php?id=' . $line->rowid . '&socid=' . $object_socid . '&mainmenu=agefodd">' . img_object($langs->trans("AgfShowDetails"), "service") . ' ' . $line->rowid . '</a></td>';
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
			print '<td>' . $line->fourninvoiceref . '</td>';
		}
		if (! (empty($search_propalref))) {
			print '<td>' . $line->propalref . '</td>';
		}
		print '<td></td>';
		print "</tr>\n";
		
		$i ++;
	}
	
	print "</table>";
} else {
	setEventMessage($agf->error, 'errors');
}

// Do not display add form for suppier invoice
// TODO : set this option for supplier invoice
if (empty($search_fourninvoiceref)) {
	$filter=array();
	$soc = new Societe($db);
	$result=$soc->fetch($object_socid);
	if ($result<0) {
		setEventMessage($soc->error, 'errors');
	}
	//$filter['so.nom']=$soc->name;
	if (count($session_array_id)>0) {
		$filter['!s.rowid']=implode(',',$session_array_id);
	}
	$select_array = array (
				'thirdparty' => $langs->trans('ThirdParty'),
				'trainee' => $langs->trans('AgfParticipant'),
				'requester' => $langs->trans('AgfTypeRequester'),
				'trainee_requester' => $langs->trans('AgfTypeTraineeRequester'),
				'opca' => $langs->trans('AgfMailTypeContactOPCA'),
		);
	
	$sessions = array ();
	foreach($select_array as $key=>$val) {
		$filter['type_affect']=$key;
		$agf_session = new Agsession($db);
		$result=$agf_session->fetch_all_by_soc($object_socid,"ASC", "s.dated", 0, 0, $filter);
		if ($result<0) {
			setEventMessage($soc->error, 'errors');
		}
		
		foreach ( $agf_session->lines as $line_session ) {
			!empty($line_session->training_ref_interne)?$training_ref_interne= ' - (' .$line_session->training_ref_interne.')': $training_ref_interne='';
			$sessions [$line_session->rowid] = $line_session->rowid.' '. $line_session->ref_interne.$training_ref_interne. ' - ' . $line_session->intitule . ' - ' . dol_print_date($line_session	->dated, 'daytext');
		}
	}
	print '<table class="noborder" width="100%">';
	print '<tr>';
	print '<td align="right">';
	print $form->selectarray('session_id', $sessions, GETPOST('session_id'), 1);
	print '</td>';
	print '<td align="left">';
	print '<input type="submit" value="' . $langs->trans('AgfSelectAgefoddSessionToLink') . '" name="link_element"/>';
	print '</td>';
	print '</tr>';
	print "</table>";
}
print '</form>';

llxFooter();
$db->close();