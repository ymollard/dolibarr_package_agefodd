<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013-2016 Florian Henry <florian.henry@open-concept.pro>
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
 * \file htdocs/webservices/server_agsession.php
 * \brief File that is entry point to call Dolibarr WebServices
 * \version $Id: server_agsession.php,v 1.7 2010/12/19 11:49:37 eldy Exp $
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'] . '/htdocs');

$res = @include ("../../master.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../master.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");
require_once (NUSOAP_PATH . '/nusoap.php'); // Include SOAP
require_once (DOL_DOCUMENT_ROOT . "/core/lib/ws.lib.php");
require_once (DOL_DOCUMENT_ROOT . "/agsession/class/agsession.class.php");

dol_syslog("Call Agsession webservices interfaces");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES)) {
	$langs->load("admin");
	dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
	print $langs->trans("WarningModuleNotActive", 'WebServices') . '.<br><br>';
	print $langs->trans("ToActivateModule");
	exit();
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding = 'UTF-8';
$server->decode_utf8 = false;
$ns = 'http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrAgsession', $ns);
$server->wsdl->schemaTargetNamespace = $ns;

// Define WSDL Authentication object
$server->wsdl->addComplexType('authentication', 'complexType', 'struct', 'all', '', array(
		'dolibarrkey' => array(
				'name' => 'dolibarrkey',
				'type' => 'xsd:string'
		),
		'sourceapplication' => array(
				'name' => 'sourceapplication',
				'type' => 'xsd:string'
		),
		'login' => array(
				'name' => 'login',
				'type' => 'xsd:string'
		),
		'password' => array(
				'name' => 'password',
				'type' => 'xsd:string'
		),
		'entity' => array(
				'name' => 'entity',
				'type' => 'xsd:string'
		)
));

// Define WSDL Return object
$server->wsdl->addComplexType('result', 'complexType', 'struct', 'all', '', array(
		'result_code' => array(
				'name' => 'result_code',
				'type' => 'xsd:string'
		),
		'result_label' => array(
				'name' => 'result_label',
				'type' => 'xsd:string'
		)
));

// Define other specific objects
$server->wsdl->addComplexType('agsession', 'complexType', 'struct', 'all', '', array(

		'id' => array(
				'name' => 'id',
				'type' => 'xsd:string'
		),
		'fk_soc' => array(
				'name' => 'fk_soc',
				'type' => 'xsd:string'
		),
		'client' => array(
				'name' => 'client',
				'type' => 'xsd:string'
		),
		'socid' => array(
				'name' => 'socid',
				'type' => 'xsd:string'
		),
		'fk_formation_catalogue' => array(
				'name' => 'fk_formation_catalogue',
				'type' => 'xsd:string'
		),
		'fk_session_place' => array(
				'name' => 'fk_session_place',
				'type' => 'xsd:string'
		),
		'nb_place' => array(
				'name' => 'nb_place',
				'type' => 'xsd:string'
		),
		'nb_stagiaire' => array(
				'name' => 'nb_stagiaire',
				'type' => 'xsd:string'
		),
		'force_nb_stagiaire' => array(
				'name' => 'force_nb_stagiaire',
				'type' => 'xsd:string'
		),
		'type_session' => array(
				'name' => 'type_session',
				'type' => 'xsd:string'
		),
		'dated' => array(
				'name' => 'dated',
				'type' => 'xsd:string'
		),
		'datef' => array(
				'name' => 'datef',
				'type' => 'xsd:string'
		),
		'notes' => array(
				'name' => 'notes',
				'type' => 'xsd:string'
		),
		'color' => array(
				'name' => 'color',
				'type' => 'xsd:string'
		),
		'cost_trainer' => array(
				'name' => 'cost_trainer',
				'type' => 'xsd:string'
		),
		'cost_site' => array(
				'name' => 'cost_site',
				'type' => 'xsd:string'
		),
		'cost_trip' => array(
				'name' => 'cost_trip',
				'type' => 'xsd:string'
		),
		'sell_price' => array(
				'name' => 'sell_price',
				'type' => 'xsd:string'
		),
		'invoice_amount' => array(
				'name' => 'invoice_amount',
				'type' => 'xsd:string'
		),
		'cost_buy_charges' => array(
				'name' => 'cost_buy_charges',
				'type' => 'xsd:string'
		),
		'cost_sell_charges' => array(
				'name' => 'cost_sell_charges',
				'type' => 'xsd:string'
		),
		'date_res_site' => array(
				'name' => 'date_res_site',
				'type' => 'xsd:string'
		),
		
		'date_res_confirm_site' => array(
				'name' => 'date_res_confirm_site',
				'type' => 'xsd:string'
		),
		
		'date_res_trainer' => array(
				'name' => 'date_res_trainer',
				'type' => 'xsd:string'
		),
		
		'date_ask_OPCA' => array(
				'name' => 'date_ask_OPCA',
				'type' => 'xsd:string'
		),
	
		'is_OPCA' => array(
				'name' => 'is_OPCA',
				'type' => 'xsd:string'
		),
		'fk_soc_OPCA' => array(
				'name' => 'fk_soc_OPCA',
				'type' => 'xsd:string'
		),
		'soc_OPCA_name' => array(
				'name' => 'soc_OPCA_name',
				'type' => 'xsd:string'
		),
		'fk_socpeople_OPCA' => array(
				'name' => 'fk_socpeople_OPCA',
				'type' => 'xsd:string'
		),
		'contact_name_OPCA' => array(
				'name' => 'contact_name_OPCA',
				'type' => 'xsd:string'
		),
		'OPCA_contact_adress' => array(
				'name' => 'OPCA_contact_adress',
				'type' => 'xsd:string'
		),
		'OPCA_adress' => array(
				'name' => 'OPCA_adress',
				'type' => 'xsd:string'
		),
		'num_OPCA_soc' => array(
				'name' => 'num_OPCA_soc',
				'type' => 'xsd:string'
		),
		'num_OPCA_file' => array(
				'name' => 'num_OPCA_file',
				'type' => 'xsd:string'
		),
		'fk_user_author' => array(
				'name' => 'fk_user_author',
				'type' => 'xsd:string'
		),
		'datec' => array(
				'name' => 'datec',
				'type' => 'xsd:string'
		),
		'fk_user_mod' => array(
				'name' => 'fk_user_mod',
				'type' => 'xsd:string'
		),
		'tms' => array(
				'name' => 'tms',
				'type' => 'xsd:string'
		),
		'lines' => array(
				'name' => 'lines',
				'type' => 'xsd:string'
		),
		'commercialid' => array(
				'name' => 'commercialid',
				'type' => 'xsd:string'
		),
		'commercialname' => array(
				'name' => 'commercialname',
				'type' => 'xsd:string'
		),
		'contactid' => array(
				'name' => 'contactid',
				'type' => 'xsd:string'
		),
		'contactname' => array(
				'name' => 'contactname',
				'type' => 'xsd:string'
		),
		'sourcecontactid' => array(
				'name' => 'sourcecontactid',
				'type' => 'xsd:string'
		),
		'fk_actioncomm' => array(
				'name' => 'fk_actioncomm',
				'type' => 'xsd:string'
		),
		'fk_product' => array(
				'name' => 'fk_product',
				'type' => 'xsd:string'
		),
		'formintitule' => array(
				'name' => 'formintitule',
				'type' => 'xsd:string'
		),
		'formid' => array(
				'name' => 'formid',
				'type' => 'xsd:string'
		),
		'formref' => array(
				'name' => 'formref',
				'type' => 'xsd:string'
		),
		'duree' => array(
				'name' => 'duree',
				'type' => 'xsd:string'
		),
		'nb_subscribe_min' => array(
				'name' => 'nb_subscribe_min',
				'type' => 'xsd:string'
		),
		'status' => array(
				'name' => 'status',
				'type' => 'xsd:string'
		),
		'statuscode' => array(
				'name' => 'statuscode',
				'type' => 'xsd:string'
		),
		'statuslib' => array(
				'name' => 'statuslib',
				'type' => 'xsd:string'
		),
		'contactcivilite' => array(
				'name' => 'contactcivilite',
				'type' => 'xsd:string'
		),
		'duree_session' => array(
				'name' => 'duree_session',
				'type' => 'xsd:string'
		),
		'intitule_custo' => array(
				'name' => 'intitule_custo',
				'type' => 'xsd:string'
		),
		'placecode' => array(
				'name' => 'placecode',
				'type' => 'xsd:string'
		),
		'placeid' => array(
				'name' => 'placeid',
				'type' => 'xsd:string'
		),
		'commercialemail' => array(
				'name' => 'commercialemail',
				'type' => 'xsd:string'
		),
		'commercialphone' => array(
				'name' => 'commercialphone',
				'type' => 'xsd:string'
		),
		'fk_soc_requester' => array(
				'name' => 'fk_soc_requester',
				'type' => 'xsd:string'
		),
		'fk_socpeople_requester' => array(
				'name' => 'fk_socpeople_requester',
				'type' => 'xsd:string'
		),
		'socname' => array(
				'name' => 'socname',
				'type' => 'xsd:string'
		),
		'fk_session_trainee' => array(
				'name' => 'fk_session_trainee',
				'type' => 'xsd:string'
		),
		'avgpricedesc' => array(
				'name' => 'avgpricedesc',
				'type' => 'xsd:string'
		),
		'fk_soc_presta' => array(
				'name' => 'fk_soc_presta',
				'type' => 'xsd:string'
		),
		'fk_socpeople_presta' => array(
				'name' => 'fk_socpeople_presta',
				'type' => 'xsd:string'
		),
		'import_key' => array(
				'name' => 'import_key',
				'type' => 'xsd:string'
		),
		'array_options' => array(
				'name' => 'array_options',
				'type' => 'xsd:string'
		),
		'linkedObjectsIds' => array(
				'name' => 'linkedObjectsIds',
				'type' => 'xsd:string'
		),
		'linkedObjects' => array(
				'name' => 'linkedObjects',
				'type' => 'xsd:string'
		),
		'context' => array(
				'name' => 'context',
				'type' => 'xsd:string'
		),
		'canvas' => array(
				'name' => 'canvas',
				'type' => 'xsd:string'
		),
		'project' => array(
				'name' => 'project',
				'type' => 'xsd:string'
		),
		'fk_project' => array(
				'name' => 'fk_project',
				'type' => 'xsd:string'
		),
		'projet' => array(
				'name' => 'projet',
				'type' => 'xsd:string'
		),
		'contact' => array(
				'name' => 'contact',
				'type' => 'xsd:string'
		),
		'contact_id' => array(
				'name' => 'contact_id',
				'type' => 'xsd:string'
		),
		'thirdparty' => array(
				'name' => 'thirdparty',
				'type' => 'xsd:string'
		),
		'user' => array(
				'name' => 'user',
				'type' => 'xsd:string'
		),
		'origin' => array(
				'name' => 'origin',
				'type' => 'xsd:string'
		),
		'origin_id' => array(
				'name' => 'origin_id',
				'type' => 'xsd:string'
		),
		'ref' => array(
				'name' => 'ref',
				'type' => 'xsd:string'
		),
		'ref_previous' => array(
				'name' => 'ref_previous',
				'type' => 'xsd:string'
		),
		'ref_next' => array(
				'name' => 'ref_next',
				'type' => 'xsd:string'
		),
		'ref_ext' => array(
				'name' => 'ref_ext',
				'type' => 'xsd:string'
		),
		'statut' => array(
				'name' => 'statut',
				'type' => 'xsd:string'
		),
		'country' => array(
				'name' => 'country',
				'type' => 'xsd:string'
		),
		'country_id' => array(
				'name' => 'country_id',
				'type' => 'xsd:string'
		),
		'country_code' => array(
				'name' => 'country_code',
				'type' => 'xsd:string'
		),
		'barcode_type' => array(
				'name' => 'barcode_type',
				'type' => 'xsd:string'
		),
		'barcode_type_code' => array(
				'name' => 'barcode_type_code',
				'type' => 'xsd:string'
		),
		'barcode_type_label' => array(
				'name' => 'barcode_type_label',
				'type' => 'xsd:string'
		),
		'barcode_type_coder' => array(
				'name' => 'barcode_type_coder',
				'type' => 'xsd:string'
		),
		'mode_reglement_id' => array(
				'name' => 'mode_reglement_id',
				'type' => 'xsd:string'
		),
		'cond_reglement_id' => array(
				'name' => 'cond_reglement_id',
				'type' => 'xsd:string'
		),
		'cond_reglement' => array(
				'name' => 'cond_reglement',
				'type' => 'xsd:string'
		),
		'fk_delivery_address' => array(
				'name' => 'fk_delivery_address',
				'type' => 'xsd:string'
		),
		'shipping_method_id' => array(
				'name' => 'shipping_method_id',
				'type' => 'xsd:string'
		),
		'modelpdf' => array(
				'name' => 'modelpdf',
				'type' => 'xsd:string'
		),
		'fk_account' => array(
				'name' => 'fk_account',
				'type' => 'xsd:string'
		),
		'note_public' => array(
				'name' => 'note_public',
				'type' => 'xsd:string'
		),
		'note_private' => array(
				'name' => 'note_private',
				'type' => 'xsd:string'
		),
		'note' => array(
				'name' => 'note',
				'type' => 'xsd:string'
		),
		'total_ht' => array(
				'name' => 'total_ht',
				'type' => 'xsd:string'
		),
		'total_tva' => array(
				'name' => 'total_tva',
				'type' => 'xsd:string'
		),
		'total_localtax1' => array(
				'name' => 'total_localtax1',
				'type' => 'xsd:string'
		),
		'total_localtax2' => array(
				'name' => 'total_localtax2',
				'type' => 'xsd:string'
		),
		'total_ttc' => array(
				'name' => 'total_ttc',
				'type' => 'xsd:string'
		),
		'fk_incoterms' => array(
				'name' => 'fk_incoterms',
				'type' => 'xsd:string'
		),
		'libelle_incoterms' => array(
				'name' => 'libelle_incoterms',
				'type' => 'xsd:string'
		),
		'location_incoterms' => array(
				'name' => 'location_incoterms',
				'type' => 'xsd:string'
		),
		'name' => array(
				'name' => 'name',
				'type' => 'xsd:string'
		),
		'lastname' => array(
				'name' => 'lastname',
				'type' => 'xsd:string'
		),
		'firstname' => array(
				'name' => 'firstname',
				'type' => 'xsd:string'
		),
		'civility_id' => array(
				'name' => 'civility_id',
				'type' => 'xsd:string'
		)
)
// ...
);

// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc = 'rpc'; // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse = 'encoded'; // encoded/literal/literal wrapped
                     // Better choice is document/literal wrapped but literal wrapped not supported by nusoap.

// Register WSDL
$server->register('getAgsession',
		// Entry values
		array(
				'authentication' => 'tns:authentication',
				'id' => 'xsd:string',
				'ref' => 'xsd:string',
				'ref_ext' => 'xsd:string'
		),
		// Exit values
		array(
				'result' => 'tns:result',
				'agsession' => 'tns:agsession'
		), $ns, $ns . '#getAgsession', $styledoc, $styleuse, 'WS to get agsession');

// Register WSDL
$server->register('createAgsession',
		// Entry values
		array(
				'authentication' => 'tns:authentication',
				'agsession' => 'tns:agsession'
		),
		// Exit values
		array(
				'result' => 'tns:result',
				'id' => 'xsd:string'
		), $ns, $ns . '#createAgsession', $styledoc, $styleuse, 'WS to create a agsession');

/**
 * Get Agsession
 *
 * @param array $authentication Array of authentication information
 * @param int $id Id of object
 * @param string $ref Ref of object
 * @param string $ref_ext Ref external of object
 * @return mixed
 */
function getAgsession($authentication, $id, $ref = '', $ref_ext = '') {
	global $db, $conf, $langs;

	dol_syslog("Function: getAgsession login=" . $authentication['login'] . " id=" . $id . " ref=" . $ref . " ref_ext=" . $ref_ext);

	if ($authentication['entity'])
		$conf->entity = $authentication['entity'];

		// Init and check authentication
	$objectresp = array();
	$errorcode = '';
	$errorlabel = '';
	$error = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);
	// Check parameters
	if (! $error && (($id && $ref) || ($id && $ref_ext) || ($ref && $ref_ext))) {
		$error ++;
		$errorcode = 'BAD_PARAMETERS';
		$errorlabel = "Parameter id, ref and ref_ext can't be both provided. You must choose one or other but not both.";
	}

	if (! $error) {
		$fuser->getrights();

		if ($fuser->rights->agsession->read) {
			$agsession = new Agsession($db);
			$result = $agsession->fetch($id, $ref, $ref_ext);
			if ($result > 0) {
				// Create
				$objectresp = array(
						'result' => array(
								'result_code' => 'OK',
								'result_label' => ''
						),
						'agsession' => array(

								'id' => $agsession->id,
								'fk_soc' => $agsession->fk_soc,
								'client' => $agsession->client,
								'socid' => $agsession->socid,
								'fk_formation_catalogue' => $agsession->fk_formation_catalogue,
								'fk_session_place' => $agsession->fk_session_place,
								'nb_place' => $agsession->nb_place,
								'nb_stagiaire' => $agsession->nb_stagiaire,
								'force_nb_stagiaire' => $agsession->force_nb_stagiaire,
								'type_session' => $agsession->type_session,
								'dated' => $agsession->dated,
								'datef' => $agsession->datef,
								'notes' => $agsession->notes,
								'color' => $agsession->color,
								'cost_trainer' => $agsession->cost_trainer,
								'cost_site' => $agsession->cost_site,
								'cost_trip' => $agsession->cost_trip,
								'sell_price' => $agsession->sell_price,
								'invoice_amount' => $agsession->invoice_amount,
								'cost_buy_charges' => $agsession->cost_buy_charges,
								'cost_sell_charges' => $agsession->cost_sell_charges,
								'date_res_site' => $agsession->date_res_site,
								'date_res_confirm_site' => $agsession->date_res_confirm_site,
								'date_res_trainer' => $agsession->date_res_trainer,
								'date_ask_OPCA' => $agsession->date_ask_OPCA,
								'is_OPCA' => $agsession->is_OPCA,
								'fk_soc_OPCA' => $agsession->fk_soc_OPCA,
								'soc_OPCA_name' => $agsession->soc_OPCA_name,
								'fk_socpeople_OPCA' => $agsession->fk_socpeople_OPCA,
								'contact_name_OPCA' => $agsession->contact_name_OPCA,
								'OPCA_contact_adress' => $agsession->OPCA_contact_adress,
								'OPCA_adress' => $agsession->OPCA_adress,
								'num_OPCA_soc' => $agsession->num_OPCA_soc,
								'num_OPCA_file' => $agsession->num_OPCA_file,
								'fk_user_author' => $agsession->fk_user_author,
								'datec' => $agsession->datec,
								'fk_user_mod' => $agsession->fk_user_mod,
								'tms' => $agsession->tms,
								'lines' => $agsession->lines,
								'commercialid' => $agsession->commercialid,
								'commercialname' => $agsession->commercialname,
								'contactid' => $agsession->contactid,
								'contactname' => $agsession->contactname,
								'sourcecontactid' => $agsession->sourcecontactid,
								'fk_actioncomm' => $agsession->fk_actioncomm,
								'fk_product' => $agsession->fk_product,
								'formintitule' => $agsession->formintitule,
								'formid' => $agsession->formid,
								'formref' => $agsession->formref,
								'duree' => $agsession->duree,
								'nb_subscribe_min' => $agsession->nb_subscribe_min,
								'status' => $agsession->status,
								'statuscode' => $agsession->statuscode,
								'statuslib' => $agsession->statuslib,
								'contactcivilite' => $agsession->contactcivilite,
								'duree_session' => $agsession->duree_session,
								'intitule_custo' => $agsession->intitule_custo,
								'placecode' => $agsession->placecode,
								'placeid' => $agsession->placeid,
								'commercialemail' => $agsession->commercialemail,
								'commercialphone' => $agsession->commercialphone,
								'fk_soc_requester' => $agsession->fk_soc_requester,
								'fk_socpeople_requester' => $agsession->fk_socpeople_requester,
								'socname' => $agsession->socname,
								'fk_session_trainee' => $agsession->fk_session_trainee,
								'avgpricedesc' => $agsession->avgpricedesc,
								'fk_soc_presta' => $agsession->fk_soc_presta,
								'fk_socpeople_presta' => $agsession->fk_socpeople_presta,
								'import_key' => $agsession->import_key,
								'array_options' => $agsession->array_options,
								'linkedObjectsIds' => $agsession->linkedObjectsIds,
								'linkedObjects' => $agsession->linkedObjects,
								'context' => $agsession->context,
								'canvas' => $agsession->canvas,
								'project' => $agsession->project,
								'fk_project' => $agsession->fk_project,
								'projet' => $agsession->projet,
								'contact' => $agsession->contact,
								'contact_id' => $agsession->contact_id,
								'thirdparty' => $agsession->thirdparty,
								'user' => $agsession->user,
								'origin' => $agsession->origin,
								'origin_id' => $agsession->origin_id,
								'ref' => $agsession->ref,
								'ref_previous' => $agsession->ref_previous,
								'ref_next' => $agsession->ref_next,
								'ref_ext' => $agsession->ref_ext,
								'statut' => $agsession->statut,
								'country' => $agsession->country,
								'country_id' => $agsession->country_id,
								'country_code' => $agsession->country_code,
								'barcode_type' => $agsession->barcode_type,
								'barcode_type_code' => $agsession->barcode_type_code,
								'barcode_type_label' => $agsession->barcode_type_label,
								'barcode_type_coder' => $agsession->barcode_type_coder,
								'mode_reglement_id' => $agsession->mode_reglement_id,
								'cond_reglement_id' => $agsession->cond_reglement_id,
								'cond_reglement' => $agsession->cond_reglement,
								'fk_delivery_address' => $agsession->fk_delivery_address,
								'shipping_method_id' => $agsession->shipping_method_id,
								'modelpdf' => $agsession->modelpdf,
								'fk_account' => $agsession->fk_account,
								'note_public' => $agsession->note_public,
								'note_private' => $agsession->note_private,
								'note' => $agsession->note,
								'total_ht' => $agsession->total_ht,
								'total_tva' => $agsession->total_tva,
								'total_localtax1' => $agsession->total_localtax1,
								'total_localtax2' => $agsession->total_localtax2,
								'total_ttc' => $agsession->total_ttc,
								'fk_incoterms' => $agsession->fk_incoterms,
								'libelle_incoterms' => $agsession->libelle_incoterms,
								'location_incoterms' => $agsession->location_incoterms,
								'name' => $agsession->name,
								'lastname' => $agsession->lastname,
								'firstname' => $agsession->firstname,
								'civility_id' => $agsession->civility_id
						)
						// ...

				);
			} else {
				$error ++;
				$errorcode = 'NOT_FOUND';
				$errorlabel = 'Object not found for id=' . $id . ' nor ref=' . $ref . ' nor ref_ext=' . $ref_ext;
			}
		} else {
			$error ++;
			$errorcode = 'PERMISSION_DENIED';
			$errorlabel = 'User does not have permission for this request';
		}
	}

	if ($error) {
		$objectresp = array(
				'result' => array(
						'result_code' => $errorcode,
						'result_label' => $errorlabel
				)
		);
	}

	return $objectresp;
}

/**
 * Create Agsession
 *
 * @param array $authentication Array of authentication information
 * @param Agsession $agsession $agsession
 * @return array Array result
 */
function createAgsession($authentication, $agsession) {
	global $db, $conf, $langs;

	$now = dol_now();

	dol_syslog("Function: createAgsession login=" . $authentication['login']);

	if ($authentication['entity'])
		$conf->entity = $authentication['entity'];

		// Init and check authentication
	$objectresp = array();
	$errorcode = '';
	$errorlabel = '';
	$error = 0;
	$fuser = check_authentication($authentication, $error, $errorcode, $errorlabel);
	// Check parameters

	if (! $error) {
		$newobject = new Agsession($db);

		$newobject->id = $agsession->id;
		$newobject->fk_soc = $agsession->fk_soc;
		$newobject->client = $agsession->client;
		$newobject->socid = $agsession->socid;
		$newobject->fk_formation_catalogue = $agsession->fk_formation_catalogue;
		$newobject->fk_session_place = $agsession->fk_session_place;
		$newobject->nb_place = $agsession->nb_place;
		$newobject->nb_stagiaire = $agsession->nb_stagiaire;
		$newobject->force_nb_stagiaire = $agsession->force_nb_stagiaire;
		$newobject->type_session = $agsession->type_session;
		$newobject->dated = $agsession->dated;
		$newobject->datef = $agsession->datef;
		$newobject->notes = $agsession->notes;
		$newobject->color = $agsession->color;
		$newobject->cost_trainer = $agsession->cost_trainer;
		$newobject->cost_site = $agsession->cost_site;
		$newobject->cost_trip = $agsession->cost_trip;
		$newobject->sell_price = $agsession->sell_price;
		$newobject->invoice_amount = $agsession->invoice_amount;
		$newobject->cost_buy_charges = $agsession->cost_buy_charges;
		$newobject->cost_sell_charges = $agsession->cost_sell_charges;
		$newobject->date_res_site = $agsession->date_res_site;
		$newobject->date_res_confirm_site = $agsession->date_res_confirm_site;
		$newobject->date_res_trainer = $agsession->date_res_trainer;
		$newobject->date_ask_OPCA = $agsession->date_ask_OPCA;
		$newobject->is_OPCA = $agsession->is_OPCA;
		$newobject->fk_soc_OPCA = $agsession->fk_soc_OPCA;
		$newobject->soc_OPCA_name = $agsession->soc_OPCA_name;
		$newobject->fk_socpeople_OPCA = $agsession->fk_socpeople_OPCA;
		$newobject->contact_name_OPCA = $agsession->contact_name_OPCA;
		$newobject->OPCA_contact_adress = $agsession->OPCA_contact_adress;
		$newobject->OPCA_adress = $agsession->OPCA_adress;
		$newobject->num_OPCA_soc = $agsession->num_OPCA_soc;
		$newobject->num_OPCA_file = $agsession->num_OPCA_file;
		$newobject->fk_user_author = $agsession->fk_user_author;
		$newobject->datec = $agsession->datec;
		$newobject->fk_user_mod = $agsession->fk_user_mod;
		$newobject->tms = $agsession->tms;
		$newobject->lines = $agsession->lines;
		$newobject->commercialid = $agsession->commercialid;
		$newobject->commercialname = $agsession->commercialname;
		$newobject->contactid = $agsession->contactid;
		$newobject->contactname = $agsession->contactname;
		$newobject->sourcecontactid = $agsession->sourcecontactid;
		$newobject->fk_actioncomm = $agsession->fk_actioncomm;
		$newobject->fk_product = $agsession->fk_product;
		$newobject->formintitule = $agsession->formintitule;
		$newobject->formid = $agsession->formid;
		$newobject->formref = $agsession->formref;
		$newobject->duree = $agsession->duree;
		$newobject->nb_subscribe_min = $agsession->nb_subscribe_min;
		$newobject->status = $agsession->status;
		$newobject->statuscode = $agsession->statuscode;
		$newobject->statuslib = $agsession->statuslib;
		$newobject->contactcivilite = $agsession->contactcivilite;
		$newobject->duree_session = $agsession->duree_session;
		$newobject->intitule_custo = $agsession->intitule_custo;
		$newobject->placecode = $agsession->placecode;
		$newobject->placeid = $agsession->placeid;
		$newobject->commercialemail = $agsession->commercialemail;
		$newobject->commercialphone = $agsession->commercialphone;
		$newobject->fk_soc_requester = $agsession->fk_soc_requester;
		$newobject->fk_socpeople_requester = $agsession->fk_socpeople_requester;
		$newobject->socname = $agsession->socname;
		$newobject->fk_session_trainee = $agsession->fk_session_trainee;
		$newobject->avgpricedesc = $agsession->avgpricedesc;
		$newobject->fk_soc_presta = $agsession->fk_soc_presta;
		$newobject->fk_socpeople_presta = $agsession->fk_socpeople_presta;
		$newobject->import_key = $agsession->import_key;
		$newobject->array_options = $agsession->array_options;
		$newobject->linkedObjectsIds = $agsession->linkedObjectsIds;
		$newobject->linkedObjects = $agsession->linkedObjects;
		$newobject->context = $agsession->context;
		$newobject->canvas = $agsession->canvas;
		$newobject->project = $agsession->project;
		$newobject->fk_project = $agsession->fk_project;
		$newobject->projet = $agsession->projet;
		$newobject->contact = $agsession->contact;
		$newobject->contact_id = $agsession->contact_id;
		$newobject->thirdparty = $agsession->thirdparty;
		$newobject->user = $agsession->user;
		$newobject->origin = $agsession->origin;
		$newobject->origin_id = $agsession->origin_id;
		$newobject->ref = $agsession->ref;
		$newobject->ref_previous = $agsession->ref_previous;
		$newobject->ref_next = $agsession->ref_next;
		$newobject->ref_ext = $agsession->ref_ext;
		$newobject->statut = $agsession->statut;
		$newobject->country = $agsession->country;
		$newobject->country_id = $agsession->country_id;
		$newobject->country_code = $agsession->country_code;
		$newobject->barcode_type = $agsession->barcode_type;
		$newobject->barcode_type_code = $agsession->barcode_type_code;
		$newobject->barcode_type_label = $agsession->barcode_type_label;
		$newobject->barcode_type_coder = $agsession->barcode_type_coder;
		$newobject->mode_reglement_id = $agsession->mode_reglement_id;
		$newobject->cond_reglement_id = $agsession->cond_reglement_id;
		$newobject->cond_reglement = $agsession->cond_reglement;
		$newobject->fk_delivery_address = $agsession->fk_delivery_address;
		$newobject->shipping_method_id = $agsession->shipping_method_id;
		$newobject->modelpdf = $agsession->modelpdf;
		$newobject->fk_account = $agsession->fk_account;
		$newobject->note_public = $agsession->note_public;
		$newobject->note_private = $agsession->note_private;
		$newobject->note = $agsession->note;
		$newobject->total_ht = $agsession->total_ht;
		$newobject->total_tva = $agsession->total_tva;
		$newobject->total_localtax1 = $agsession->total_localtax1;
		$newobject->total_localtax2 = $agsession->total_localtax2;
		$newobject->total_ttc = $agsession->total_ttc;
		$newobject->fk_incoterms = $agsession->fk_incoterms;
		$newobject->libelle_incoterms = $agsession->libelle_incoterms;
		$newobject->location_incoterms = $agsession->location_incoterms;
		$newobject->name = $agsession->name;
		$newobject->lastname = $agsession->lastname;
		$newobject->firstname = $agsession->firstname;
		$newobject->civility_id = $agsession->civility_id;

		// ...

		$db->begin();

		$result = $newobject->create($fuser);
		if ($result <= 0) {
			$error ++;
		}

		if (! $error) {
			$db->commit();
			$objectresp = array(
					'result' => array(
							'result_code' => 'OK',
							'result_label' => ''
					),
					'id' => $newobject->id,
					'ref' => $newobject->ref
			);
		} else {
			$db->rollback();
			$error ++;
			$errorcode = 'KO';
			$errorlabel = $newobject->error;
		}
	}

	if ($error) {
		$objectresp = array(
				'result' => array(
						'result_code' => $errorcode,
						'result_label' => $errorlabel
				)
		);
	}

	return $objectresp;
}

// Return the results.
$server->service(file_get_contents("php://input"));
