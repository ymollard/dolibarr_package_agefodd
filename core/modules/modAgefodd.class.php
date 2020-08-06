<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2012	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2013-2014   Florian Henry   <florian.henry@open-concept.pro>
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
 * \defgroup agefodd Module AGeFoDD (Assistant de GEstion de la FOrmation Dans Dolibarr)
 * \brief agefodd module descriptor.
 * \file /core/modules/modAgefodd.class.php
 * \ingroup agefodd
 * \brief Description and activation file for module agefodd
 */
include_once (DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php");

/**
 * \class modAgefodd
 * \brief Description and activation class for module agefodd
 */
class modAgefodd extends DolibarrModules
{
	var $error;
	/**
	 * Constructor.
	 *
	 * @param DoliDB Database handler
	 */
	function __construct($db) {
		global $conf, $langs;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 103000;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'agefodd';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "hr";
		// Module label, used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Trainning Management Assistant Module";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '4.12.2';

		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/images directory, use this->picto=DOL_URL_ROOT.'/module/images/file.png'
		$this->picto = 'agefodd@agefodd';

		$this->editor_name = 'ATM-Consulting';
		$this->editor_url = 'https://www.atm-consulting.fr';

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array(
				"/agefodd",
				"/agefodd/training",
				"/agefodd/trainer",
				"/agefodd/place",
				"/agefodd/trainee",
				"/agefodd/report",
				"/agefodd/report/bpf",
				"/agefodd/report/ca",
		        "/agefodd/report/bycust/",
		        "/agefodd/report/calendarbycust/",
		        "/agefodd/report/commercial",
		        "/agefodd/report/time",
				"/agefodd/background"
		);
		$r = 0;

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
			0 => array('label' => 'CronTaskSendAgendaToTraineeLabel', 'jobtype' => 'method', 'class' => 'agefodd/cron/cron.php', 'objectname' => 'cron_agefodd', 'method' => 'sendAgendaToTrainee', 'parameters' => '', 'comment' => 'Send email to trainees', 'frequency' => 1, 'unitfrequency' => 86400, 'status' => 0, 'test' => true),
			//1 => array('label' => 'DATAPOLICY Mailing', 'jobtype' => 'method', 'class' => '/datapolicy/class/datapolicyCron.class.php', 'objectname' => 'RgpdCron', 'method' => 'sendMailing', 'parameters' => '', 'comment' => 'Comment', 'frequency' => 1, 'unitfrequency' => 86400, 'status' => 0, 'test' => true)
		);
		// Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true),
		//                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>true)
		// );

		// Relative path to module style sheet if exists. Example: '/mymodule/mycss.css'.
		$this->style_sheet = '/agefodd/css/agefodd.css';

		// Config pages. Put here list of php page names stored in admin directory used to setup module.
		$this->config_page_url = array(
				"admin_agefodd.php@agefodd"
		);

		// define triggers
		$this->module_parts = array(
				'triggers' => 1,
				'hooks' => array(
						'searchform',
						'pdfgeneration',
						'propalcard',
						'ordercard',
						'invoicecard',
						'ordersuppliercard',
						'invoicesuppliercard',
						'admin',
						'emailtemplates',
				        'externalaccesspage',
				        'externalaccessinterface',
						'upgrade',
						'agendaexport',
						'contactcard',
						'agenda',
                        'fileupload',
                        'main',
						'attachmentsform'
				),
				'substitutions' => '/agefodd/core/substitutions/',
				'models' => 1,
		        'css' => array('/agefodd/css/agefodd.css'),
		);

		// Dependencies
		$this->depends = array(
				'modSociete',
				'modPropale',
				// 'modComptabilite', Ne plus utiliser , je le laisse en comment car il revient de temps en temps
				'modFacture',
				'modBanque',
				'modFournisseur',
				'modService',
				'modAgenda',
				'modCategorie',
				'modFckeditor'
		);
		$this->requiredby = array();
		$this->phpmin = array(
				7,
				0
		);
		$this->need_dolibarr_version = array(
				9,
				0
		);
		$this->langfiles = array(
				'agefodd@agefodd'
		);

		// Constants
		$this->const = array();
		$r = 0;

		$r ++;
		$this->const[$r][0] = "AGF_USE_STAGIAIRE_TYPE";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Use trainee type';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_DEFAULT_STAGIAIRE_TYPE";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '2';
		$this->const[$r][3] = 'Type of  trainee funding';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_UNIVERSAL_MASK";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Mask of training number ref';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = 'mod_agefodd_simple';
		$this->const[$r][3] = 'Use simple mask for training ref';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_ORGANISME_PREF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Prefecture d\'enregistrement';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_ORGANISME_NUM";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Numerot d\'enregistrement a la prefecture';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_ORGANISME_REPRESENTANT";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Representant de la societé de formation';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_TRAINING_USE_SEARCH_TO_SELECT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Search Training with combobox';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_TRAINER_USE_SEARCH_TO_SELECT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Search Trainer with combobox';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_TRAINEE_USE_SEARCH_TO_SELECT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Search Trainee with combobox';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_SITE_USE_SEARCH_TO_SELECT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Search site with combobox';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_STAGTYPE_USE_SEARCH_TO_SELECT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Search stagiaire type with combobox';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_CONTACT_USE_SEARCH_TO_SELECT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Search contact with combobox';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_CONTACT_DOL_SESSION";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Use dolibarr or agefodd contact for session';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_LAST_VERION_INSTALL";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = $this->version;
		$this->const[$r][3] = 'Last version installed to know change table to execute';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 'allentities';
		$this->const[$r][6] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_DOL_AGENDA";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Create Event in Dolibarr Agenda';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_USE_FAC_WITHOUT_ORDER";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Can link invocie without order to session';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_LINK_OPCA_ADRR_TO_CONTACT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Display OPCA adress from OPCA contact rather than OPCA';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_TEXT_COLOR";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '000000';
		$this->const[$r][3] = 'Text color of PDF in hexadecimal';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_HEADER_COLOR_BG";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = 'FFFFFF';
		$this->const[$r][3] = 'Text color of PDF in hexadecimal';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_HEADER_COLOR_TEXT";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '000000';
		$this->const[$r][3] = 'Text color of PDF in hexadecimal';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_COLOR_LINE";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '1A60C9';
		$this->const[$r][3] = 'Text color of PDF in hexadecimal';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_HEAD_COLOR";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '1A60C9';
		$this->const[$r][3] = 'Text color header in hexadecimal';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_FOOT_COLOR";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = 'BEBEBE';
		$this->const[$r][3] = 'Text color of PDF footer, in hexadccimal';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_MANAGE_CERTIF";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Manage certification';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_DEFAULT_CREATE_CERTIF";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'When Add a trainee defaut create certificate';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_FCKEDITOR_ENABLE_TRAINING";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Use WISIWYG on training information';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_MANAGE_OPCA";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Manage Opca';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_CERTIF_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = 'mod_agefoddcertif_simple';
		$this->const[$r][3] = 'Use simple mask for certif ref';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_CERTIF_UNIVERSAL_MASK";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Mask of certificate code';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_SESSION_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = 'mod_agefoddsession_simple';
		$this->const[$r][3] = 'Use simple mask for session ref';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_SESSION_UNIVERSAL_MASK";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Mask of session code';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_SESSION_TRAINEE_STATUS_AUTO";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Manage subcription status by propal/order status';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_DOL_TRAINER_AGENDA";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Manage time by session for trainer';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_DEFAULT_SESSION_STATUS";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Defaut status session';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_ADD_TRAINEE_NAME_INTO_DOCPROPODR";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Add trainnee name when create order/proposal';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_ADD_AVGPRICE_DOCPROPODR";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Add average price on create order/proposal';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_MANAGE_CURSUS";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Manage cursus';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_CURSUS_USE_SEARCH_TO_SELECT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Display combobox for cursus select';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_ADVANCE_COST_MANAGEMENT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Advanced session cost management';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_NOT_DISPLAY_WARNING_TIME_SESSION";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Do not display warning betwenn training and session time';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_NO_MANUAL_CREATION_DOC";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Do not display manual propal/order/invoice creation';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_NEW_BROWSER_WINDOWS_ON_LINK";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'open new browser window/tab on link click';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_CONTACT_NOT_MANDATORY_ON_SESSION";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Contact is not mandatory on session';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_USE_FORMATEUR_TYPE";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Use trainer type';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_DEFAULT_FORMATEUR_TYPE";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Type of  trainer funding';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_CAT_PRODUCT_CHARGES";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Cat product charges';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_FILTER_TRAINER_TRAINING";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Filter trainer list';
		$this->const[$r][4] = 1;
		$this->const[$r][5] = 0;
		$r ++;

		$this->const[$r][0] = "AGF_1DAYSHIFT";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '09:00';
		$this->const[$r][3] = '';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r ++;

		$this->const[$r][0] = "AGF_2DAYSHIFT";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '12:00';
		$this->const[$r][3] = '';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r ++;

		$this->const[$r][0] = "AGF_USESEONDPERIOD";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = '';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r ++;

		$this->const[$r][0] = "AGF_3DAYSHIFT";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '14:00';
		$this->const[$r][3] = '';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r ++;

		$this->const[$r][0] = "AGF_4DAYSHIFT";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '18:00';
		$this->const[$r][3] = '';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r ++;

		$this->const[$r][0] = "AGF_REF_PROPAL_AUTO";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = '';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		$r ++;

		$this->const[$r][0] = "THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Can create contact in same time as third party';
		$this->const[$r][4] = 1;
		$this->const[$r][5] = 0;
		$r ++;

		foreach ( array(
				1,
				2,
				3,
				4,
				5,
				6,
				0
		) as $daynum ) {

			$this->const[$r][0] = 'AGF_WEEKADAY' . $daynum;
			$this->const[$r][1] = "yesno";
			if ($daynum == 6 || $daynum == 0) {
				$this->const[$r][2] = '0';
			} else {
				$this->const[$r][2] = '1';
			}
			$this->const[$r][3] = '';
			$this->const[$r][4] = 0;
			$this->const[$r][5] = 0;
			$r ++;
		}

		$r ++;
		$this->const[$r][0] = "MAIN_MODULES_FOR_EXTERNAL";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = 'user,facture,categorie,commande,fournisseur,contact,propal,projet,contrat,societe,ficheinter,expedition,agenda,adherent,agefodd';
		$this->const[$r][3] = 'External modules availability';
		$this->const[$r][4] = 1;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_ALLOW_ADMIN_COMMERCIAL";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Admin not allowed by default';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "PRODUIT_DESC_IN_FORM";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = '';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_MANAGE_BPF";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Manage BPF';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_ADD_PROGRAM_TO_CONV";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Add program to convention';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_ADD_PROGRAM_TO_CONVMAIL";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Add program to convention mail';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_ADD_SIGN_TO_CONVOC";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '1';
		$this->const[$r][3] = 'Add signature to convocation';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_NB_HOUR_IN_DAYS";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '7';
		$this->const[$r][3] = 'Nb Hour in days';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_DEFAULT_SESSION_TYPE";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'default type';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r ++;
		$this->const[$r][0] = "AGF_HELP_LINK";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = 'http://wiki.atm-consulting.fr/index.php/Agefodd_V2/Documentation_utilisateur';
		$this->const[$r][3] = 'help wikipage';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		// Setup $conf environement Dolibarr variable
		if (! isset($conf->agefodd->enabled)) {
			$conf->agefodd = ( object ) array();
			$conf->agefodd->enabled = 0;
		}

		// Dictionnaries
		$this->dictionnaries = array(
				'langs' => 'agefodd@agefodd',
				'tabname' => array(
						MAIN_DB_PREFIX . "agefodd_stagiaire_type",
						MAIN_DB_PREFIX . "agefodd_formateur_type",
						MAIN_DB_PREFIX . "agefodd_certificate_type",
						MAIN_DB_PREFIX . "agefodd_formation_catalogue_type",
						MAIN_DB_PREFIX . "agefodd_formateur_category_dict",
						MAIN_DB_PREFIX . "agefodd_formation_catalogue_type_bpf",
						MAIN_DB_PREFIX . "c_agefodd_session_calendrier_type"
				),
				'tablib' => array(
						"AgfTraineeType",
						"AgfTrainerTypeDict",
						"AgfCertificateType",
						"AgfTrainingCategTbl",
						"AgfTrainerCategoryDict",
						"AgfTrainingCategTblBPF",
						"AgfTypeTime"
				),
				'tabsql' => array(
						'SELECT f.rowid as rowid, f.intitule, f.sort, f.active FROM ' . MAIN_DB_PREFIX . 'agefodd_stagiaire_type as f',
						'SELECT f.rowid as rowid, f.intitule, f.sort, f.active FROM ' . MAIN_DB_PREFIX . 'agefodd_formateur_type as f',
						'SELECT f.rowid as rowid, f.intitule, f.sort, f.active FROM ' . MAIN_DB_PREFIX . 'agefodd_certificate_type as f',
						'SELECT f.rowid as rowid, f.code, f.intitule, f.sort, f.active FROM ' . MAIN_DB_PREFIX . 'agefodd_formation_catalogue_type as f',
						'SELECT f.rowid as rowid, f.code, f.label, f.description, f.active FROM ' . MAIN_DB_PREFIX . 'agefodd_formateur_category_dict as f',
						'SELECT f.rowid as rowid, f.code, f.intitule, f.sort, f.active FROM ' . MAIN_DB_PREFIX . 'agefodd_formation_catalogue_type_bpf as f',
						'SELECT f.rowid as rowid, f.code, f.label, f.active FROM ' . MAIN_DB_PREFIX . 'c_agefodd_session_calendrier_type as f'
				),
				'tabsqlsort' => array(
						'sort ASC',
						'sort ASC',
						'sort ASC',
						'sort ASC',
						'code ASC',
						'sort ASC',
						'code ASC'
				),
				'tabfield' => array(
						"intitule,sort",
						"intitule,sort",
						"intitule,sort",
						"code,intitule,sort",
						"code,label,description",
						"code,intitule,sort",
						"code,label"
				),
				'tabfieldvalue' => array(
						"intitule,sort",
						"intitule,sort",
						"intitule,sort",
						"code,intitule,sort",
						"code,label,description",
						"code,intitule,sort",
						"code,label"
				),
				'tabfieldinsert' => array(
						"intitule,sort",
						"intitule,sort",
						"intitule,sort",
						"code,intitule,sort",
						"code,label,description",
						"code,intitule,sort",
						"code,label"
				),
				'tabrowid' => array(
						"rowid",
						"rowid",
						"rowid",
						"rowid",
						"rowid",
						"rowid",
						"rowid"
				),
				'tabcond' => array(
						'$conf->agefodd->enabled',
						'$conf->agefodd->enabled',
						'$conf->agefodd->enabled',
						'$conf->agefodd->enabled',
						'$conf->agefodd->enabled',
						'$conf->agefodd->enabled',
						'$conf->agefodd->enabled'
				)
		);

		// Import list of trainee
		$r = 0;
		$r ++;
		$this->import_code[$r] = $this->rights_class . '_' . $r;
		$this->import_label[$r] = 'ImportDataset_trainee';
		$this->import_icon[$r] = 'contact';
		$this->import_entities_array[$r] = array(
				's.fk_soc' => 'company',
				's.nom' => 'AgfNbreParticipants',
				's.prenom' => 'AgfNbreParticipants',
				's.civilite' => 'AgfNbreParticipants',
				's.tel1' => 'AgfNbreParticipants',
				's.tel2' => 'AgfNbreParticipants',
				's.mail' => 'AgfNbreParticipants',
				's.fonction' => 'AgfNbreParticipants',
				's.date_birth' => 'AgfNbreParticipants',
				's.place_birth' => 'AgfNbreParticipants',
				's.datec' => 'AgfNbreParticipants'
		);
		// Add extra fields
		$sql="SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'agefodd_stagiaire' AND entity IN (0,".$conf->entity.")";
		$resql=$this->db->query($sql);
		if ($resql)    // This can fail when class is used on old database (during migration for example)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$fieldname='extra.'.$obj->name;
				$fieldlabel=ucfirst($obj->label);
				$this->import_entities_array[$r][$fieldname]='AgfNbreParticipants';
			}
		}
		$this->import_tables_array[$r] = array(
				's' => MAIN_DB_PREFIX . 'agefodd_stagiaire'
		    ,'extra'=>MAIN_DB_PREFIX.'agefodd_stagiaire_extrafields'
		);
		$this->import_fields_array[$r] = array(
				's.fk_soc' => 'ThirdPartyName*',
				's.nom' => 'AgfFamilyName',
				's.prenom' => 'AgfFirstName',
				's.civilite' => 'AgfTitle',
				's.tel1' => 'AgfTelephone1',
				's.tel2' => 'AgfTelephone2',
				's.fonction' => 'AgfFonction',
				's.mail' => 'AgfPDFFicheEvalEmailTrainee',
				's.date_birth' => 'DateToBirth',
				's.place_birth' => 'AgfPlaceBirth',
				's.datec' => 'AgfDateC'
		);
		// Add extra fields
		$sql="SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'agefodd_stagiaire' AND entity IN (0,".$conf->entity.")";
		$resql=$this->db->query($sql);
		if ($resql)    // This can fail when class is used on old database (during migration for example)
		{
		    while ($obj=$this->db->fetch_object($resql))
		    {
		        $fieldname='extra.'.$obj->name;
		        $fieldlabel=ucfirst($obj->label);
		        $this->import_fields_array[$r][$fieldname]=$fieldlabel.($obj->fieldrequired?'*':'');
		    }
		}
		$this->import_fieldshidden_array[$r] = array(
				's.fk_user_author' => 'user->id',
				's.fk_user_mod' => 'user->id',
		       'extra.fk_object'=>'lastrowid-'.MAIN_DB_PREFIX.'agefodd_stagiaire'
		);
		$this->import_convertvalue_array[$r] = array(
				's.fk_soc' => array(
						'rule' => 'fetchidfromref',
						'classfile' => '/societe/class/societe.class.php',
						'class' => 'Societe',
						'method' => 'fetch',
						'element' => 'ThirdParty'
				)
		);
		$this->import_regex_array[$r] = array(
				's.date_birth' => '^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
				's.datec' => '^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$'
		);
		$this->import_examplevalues_array[$r] = array(
				's.fk_soc' => 'MyBigCompany',
				's.nom' => 'Huppelepup',
				's.prenom' => 'Jantje',
				's.civilite' => 'MR',
				's.tel1' => '1234567890',
				's.tel2' => '0987654321',
				's.fonction' => 'Boss',
				's.mail' => 'Jantje@tks.nl',
				's.date_birth' => '2013-11-12',
				's.place_birth' => 'Almelo',
				's.datec' => '1998-11-06'
		);

		// Import certificate
		$r ++;
		$this->import_code[$r] = $this->rights_class . '_' . $r;
		$this->import_label[$r] = 'ImportDataset_agefoddcertificate';
		$this->import_icon[$r] = 'contact';
		$this->import_entities_array[$r] = array(
				's.fk_session_agefodd' => 'AgefoddMenuAction',
				's.fk_stagiaire' => 'AgfNbreParticipants',
				's.fk_agefodd_stagiaire_type' => 'AgfNbreParticipants',
				's.datec' => 'AgfNbreParticipants',
				'certif.fk_stagiaire' => 'AgfNbreParticipants',
				'certif.fk_session_agefodd' => 'AgefoddMenuAction',
				'certif.certif_code' => 'AgfCertificate',
				'certif.certif_label' => 'AgfCertificate',
				'certif.certif_dt_start' => 'AgfCertificate',
				'certif.certif_dt_end' => 'AgfCertificate',
				'certif.datec' => 'AgfCertificate'
		);
		$this->import_tables_array[$r] = array(
				's' => MAIN_DB_PREFIX . 'agefodd_session_stagiaire',
				'certif' => MAIN_DB_PREFIX . 'agefodd_stagiaire_certif'
		);
		$this->import_fields_array[$r] = array(
				's.fk_session_agefodd' => 'Id*',
				's.fk_stagiaire' => 'Id*',
				's.fk_agefodd_stagiaire_type' => "AgfTraineeType",
				's.datec' => 'DateCreation',
				'certif.fk_stagiaire' => 'Id*',
				'certif.fk_session_agefodd' => 'Id*',
				'certif.certif_code' => 'AgfCertifCode',
				'certif.certif_label' => 'AgfCertifLabel',
				'certif.certif_dt_start' => 'AgfCertifDateSt',
				'certif.certif_dt_end' => 'AgfCertifDateEnd',
				'certif.datec' => "DateCreation"
		);

		$this->import_fieldshidden_array[$r] = array(
				's.fk_user_author' => 'user->id',
				's.fk_user_mod' => 'user->id',
				'certif.fk_user_author' => 'user->id',
				'certif.fk_user_mod' => 'user->id',
				'certif.fk_session_stagiaire' => 'lastrowid-' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire'
		);
		$this->import_convertvalue_array[$r] = array();
		$this->import_regex_array[$r] = array(
				'certif.datec' => '^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
				's.datec' => '^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
				'certif.certif_dt_start' => '^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
				'certif.certif_dt_end' => '^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$'
		);
		$this->import_examplevalues_array[$r] = array(
				's.fk_session_agefodd' => '999999',
				's.fk_stagiaire' => '1',
				's.fk_agefodd_stagiaire_type' => $conf->global->AGF_DEFAULT_STAGIAIRE_TYPE,
				's.datec' => '2013-11-12',
				'çertif.fk_stagiaire' => '1',
				'certif.fk_session_agefodd' => '999999',
				'certif.certif_code' => 'CertifCode',
				'certif.certif_label' => 'CertifLabel',
				'certif.certif_dt_start' => '2013-11-12',
				'certif.certif_dt_end' => '2013-11-12',
				'certif.datec' => "2013-11-12"
		);

		// Import Session Trainee
		$r ++;
		$this->import_code[$r] = $this->rights_class . '_' . $r;
		$this->import_label[$r] = 'ImportDataset_agefoddsessionparticipant';
		$this->import_icon[$r] = 'contact';
		$this->import_entities_array[$r] = array(
				's.fk_session_agefodd' => 'AgefoddMenuAction',
				's.fk_stagiaire' => 'AgfNbreParticipants',
				's.fk_agefodd_stagiaire_type' => 'AgfNbreParticipants',
				's.datec' => 'AgfNbreParticipants'
		);
		$this->import_tables_array[$r] = array(
				's' => MAIN_DB_PREFIX . 'agefodd_session_stagiaire'
		);
		$this->import_fields_array[$r] = array(
				's.fk_session_agefodd' => 'Id*',
				's.fk_stagiaire' => 'Id*',
				's.fk_agefodd_stagiaire_type' => "AgfTraineeType",
				's.datec' => 'DateCreation'
		);

		$this->import_fieldshidden_array[$r] = array(
				's.fk_user_author' => 'user->id',
				's.fk_user_mod' => 'user->id'
		);
		$this->import_convertvalue_array[$r] = array();
		$this->import_regex_array[$r] = array(
				's.datec' => '^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$'
		);
		$this->import_examplevalues_array[$r] = array(
				's.fk_session_agefodd' => '999999',
				's.fk_stagiaire' => '1',
				's.fk_agefodd_stagiaire_type' => $conf->global->AGF_DEFAULT_STAGIAIRE_TYPE,
				's.datec' => '2013-11-12'
		);

		// Import training program
		$r ++;
		$this->import_code[$r] = $this->rights_class . '_' . $r;
		$this->import_label[$r] = 'ImportDataset_agefoddtraingingprogram';
		$this->import_icon[$r] = 'agefodd@agefodd';
		$this->import_entities_array[$r] = array(
				's.ref' => 'AgfCatalogDetail',
				's.ref_interne' => 'AgfCatalogDetail',
				's.intitule' => "AgfCatalogDetail",
				's.duree' => "AgfCatalogDetail",
				's.public' => "AgfCatalogDetail",
				's.methode' => "AgfCatalogDetail",
				's.but' => "AgfCatalogDetail",
				's.programme' => "AgfCatalogDetail",
				's.pedago_usage' => "AgfCatalogDetail",
				's.note2' => "AgfCatalogDetail",
				's.sanction' => "AgfCatalogDetail",
				's.prerequis' => "AgfCatalogDetail",
				's.fk_product' => "AgfCatalogDetail",
				's.archive' => "AgfCatalogDetail",
				's.note_private' => "AgfCatalogDetail",
				's.note_public' => "AgfCatalogDetail",
				's.nb_subscribe_min' => "AgfCatalogDetail",
				's.fk_c_category' => "AgfCatalogDetail",
				's.fk_c_category_bpf' => "AgfCatalogDetail",
				's.certif_duration' => "AgfCatalogDetail",
				's.color' => "AgfCatalogDetail",
				's.datec' => "AgfCatalogDetail"
		);
		$this->import_tables_array[$r] = array(
				's' => MAIN_DB_PREFIX . 'agefodd_formation_catalogue'
		);
		$this->import_fields_array[$r] = array(
				's.ref' => 'Ref*',
				's.ref_interne' => 'AgfRefInterne',
				's.intitule' => "AgfIntitule",
				's.duree' => "AgfDuree",
				's.public' => "AgfPublic",
				's.methode' => "AgfMethode",
				's.but' => "AgfBut",
				's.programme' => "AgfProgramme",
				's.pedago_usage' => "AgfPedagoUsage",
				's.note2' => "AgfEquiNeeded",
				's.sanction' => "AgfSanction",
				's.prerequis' => "AgfPrerequis",
				's.fk_product' => "AgfProductServiceLinked",
				's.archive' => "AgfArchiver",
				's.note_private' => "NotePrivate",
				's.note_public' => "NotePublic",
				's.nb_subscribe_min' => "AgfNbMintarget",
				's.fk_c_category' => "AgfTrainingCateg",
				's.fk_c_category_bpf' => "AgfTrainingCategBPF",
				's.certif_duration' => "AgfCertificateDuration",
				's.color' => "Color",
				's.datec' => "DateCreation"
		);
		// Add extra fields
		$sql = "SELECT name, label, fieldrequired FROM " . MAIN_DB_PREFIX . "extrafields WHERE elementtype = 'agefodd_formation_catalogue' AND entity = " . $conf->entity;
		$resql = $this->db->query($sql);
		if ($resql) // This can fail when class is used on old database (during migration for example)
		{
			while ( $obj = $this->db->fetch_object($resql) ) {
				$fieldname = 'extra.' . $obj->name;
				$fieldlabel = ucfirst($obj->label);
				$this->import_fields_array[$r][$fieldname] = $fieldlabel . ($obj->fieldrequired ? '*' : '');
			}
		}
		$this->import_fieldshidden_array[$r] = array(
				's.fk_user_author' => 'user->id',
				's.fk_user_mod' => 'user->id'
		);
		$this->import_convertvalue_array[$r] = array(
				's.fk_product' => array(
						'rule' => 'fetchidfromref',
						'classfile' => '/product/class/product.class.php',
						'class' => 'Product',
						'method' => 'fetch',
						'element' => 'product'
				),
				's.fk_c_category' => array(
						'rule' => 'fetchidfromref',
						'classfile' => '/agefodd/class/agefodd_formation_catalogue_type.class.php',
						'class' => 'Agefoddformationcataloguetype',
						'method' => 'fetch',
						'dict' => 'AgfTrainingCateg'
				),
				's.fk_c_category_bpf' => array(
						'rule' => 'fetchidfromref',
						'classfile' => '/agefodd/class/agefodd_formation_catalogue_type_bpf.class.php',
						'class' => 'Agefoddformationcataloguetypebpf',
						'method' => 'fetch',
						'dict' => 'AgfTrainingCategBPF'
				)

		);
		$this->import_regex_array[$r] = array(
				's.datec' => '^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
		);
		$this->import_examplevalues_array[$r] = array(
				's.ref' => 'FOR1601_00001',
				's.ref_interne' => 'FO16012000',
				's.intitule' => "Formation",
				's.duree' => "1",
				's.public' => "Tous public",
				's.methode' => "Participative",
				's.but' => "But",
				's.programme' => "programe",
				's.pedago_usage' => "Methode pédagogique",
				's.note2' => "Equipement necessaires",
				's.sanction' => "Diplome",
				's.prerequis' => "Savoir lire et écrire",
				's.fk_product' => "PRD01",
				's.archive' => "0",
				's.note_private' => "",
				's.note_public' => "",
				's.nb_subscribe_min' => "5",
				's.fk_c_category' => "ref:100",
				's.fk_c_category_bpf' => "ref:F3d",
				's.certif_duration' => "1",
				's.color' => "DDDDDD",
				's.datec' => "2016-12-31"
		);
		$this->import_updatekeys_array[$r]=array('s.ref'=>'Ref');

		// Trainee export
		$r = 0;
		$r ++;
		$this->export_code[$r] = $this->rights_class . '_' . $r;
		$this->export_label[$r] = 'ExportDataset_trainee';
		$this->export_icon[$r] = 'contact';
		$this->export_permission[$r] = array(
				array(
						"agefodd",
						"export"
				)
		);
		$this->export_fields_array[$r] = array(
				's.rowid' => 'Id',
				'c.nom' => 'ThirdPartyName',
				's.nom' => 'AgfFamilyName',
				's.prenom' => 'AgfFirstName',
				's.civilite' => 'AgfTitle',
				's.tel1' => 'AgfTelephone1',
				's.tel2' => 'AgfTelephone2',
				's.mail' => 'AgfPDFFicheEvalEmailTrainee',
				's.date_birth' => 'DateToBirth',
				's.place_birth' => 'AgfPlaceBirth',
				's.datec' => 'AgfDateC'
		);
		$this->export_TypeFields_array[$r] = array(
				'c.nom' => "Text",
				's.nom' => "Text",
				's.prenom' => "Text",
				's.civilite' => "Text"
		);
		$this->export_entities_array[$r] = array(
				'c.nom' => "company",
				's.rowid' => "AgfNbreParticipants",
				's.nom' => "AgfNbreParticipants",
				's.prenom' => "AgfNbreParticipants",
				's.civilite' => "AgfNbreParticipants",
				's.tel1' => "AgfNbreParticipants",
				's.tel2' => "AgfNbreParticipants",
				's.mail' => "AgfNbreParticipants",
				's.date_birth' => "AgfNbreParticipants",
				's.place_birth' => "AgfNbreParticipants",
				's.datec' => "AgfNbreParticipants"
		);

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r] = ' FROM ' . MAIN_DB_PREFIX . 'agefodd_stagiaire as s';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as c ON s.fk_soc = c.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire_extrafields as extratrainee ON extratrainee.fk_object = s.rowid';
		$this->export_sql_end[$r] .= ' WHERE c.entity IN (' . getEntity("societe", 1) . ')';

		$keyforselect = 'agefodd_stagiaire';
		$keyforelement = 'AgfMailTypeContactTrainee';
		$keyforaliasextra = 'extratrainee';
		include DOL_DOCUMENT_ROOT . '/core/extrafieldsinexport.inc.php';

		// certificate export
		$r ++;
		$this->export_code[$r] = $this->rights_class . '_' . $r;
		$this->export_label[$r] = 'ExportDataset_certificate';
		$this->export_icon[$r] = 'contact';
		$this->export_permission[$r] = array(
				array(
						"agefodd",
						"export"
				)
		);
		$this->export_fields_array[$r] = array(
				's.nom' => 'AgfFamilyName',
				's.prenom' => 'AgfFirstName',
				's.civilite' => 'AgfTitle',
				's.date_birth' => 'DateToBirth',
				's.place_birth' => 'AgfPlaceBirth',
				'certif.fk_stagiaire' => 'Id',
				'certif.fk_session_agefodd' => 'Id',
				'certif.certif_code' => 'AgfCertifCode',
				'certif.certif_label' => 'AgfCertifLabel',
				'certif.certif_dt_start' => 'AgfCertifDateSt',
				'certif.certif_dt_end' => 'AgfCertifDateEnd',
				's.datec' => 'AgfDateC'
		);
		$this->export_TypeFields_array[$r] = array(
				'c.nom' => "Text",
				's.nom' => "Text",
				's.prenom' => "Text",
				's.civilite' => "Text"
		);
		$this->export_entities_array[$r] = array(
				'c.nom' => "company",
				's.nom' => 'AgfNbreParticipants',
				's.prenom' => 'AgfNbreParticipants',
				's.civilite' => 'AgfNbreParticipants',
				's.date_birth' => 'AgfNbreParticipants',
				's.place_birth' => 'AgfNbreParticipants',
				'certif.fk_stagiaire' => 'AgfNbreParticipants',
				'certif.fk_session_agefodd' => 'AgefoddMenuAction',
				'certif.certif_code' => 'AgfCertificate',
				'certif.certif_label' => 'AgfCertificate',
				'certif.certif_dt_start' => 'AgfCertificate',
				'certif.certif_dt_end' => 'AgfCertificate',
				's.datec' => 'AgfNbreParticipants'
		);

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r] = ' FROM ' . MAIN_DB_PREFIX . 'agefodd_stagiaire as s';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire_certif as certif ON certif.fk_stagiaire = s.rowid';
		$this->export_sql_end[$r] .= ' WHERE 1=1 ';

		// Session export
		$r ++;
		$this->export_code[$r] = $this->rights_class . '_' . $r;
		$this->export_label[$r] = 'ExportDataset_session';
		$this->export_icon[$r] = 'bill';
		$this->export_permission[$r] = array(
				array(
						"agefodd",
						"export"
				)
		);

		$this->export_fields_array[$r] = array(
				's.rowid' => 'Id',
				's.ref' => 'Ref.',
				'CASE WHEN s.type_session=0 THEN \'Intra\' ELSE \'Inter\' END as type_session' => 'AgfFormTypeSession',
				's.dated' => 'AgfDateDebut',
				's.datef' => 'AgfDateFin',
				'cal.heured' => 'AgfPeriodTimeB',
				'cal.heuref' => 'AgfPeriodTimeE',
				's.nb_stagiaire' => 'AgfNbreParticipants',
				's.notes' => 'AgfNote',
				's.cost_trainer' => 'AgfCoutFormateur',
				's.cost_site' => 'AgfCoutSalle',
				's.cost_trip' => 'AgfCoutDeplacement',
				's.sell_price' => 'AgfCoutFormation',
				'statusdict.code as sessionstatus' => 'AgfStatusSession',
				's.is_opca as sessionisopca' => 'AgfSubrocation',
				'socsessopca.nom as sessionsocopca' => 'AgfOPCAName',
				'contactsessopca.civility as contactsessopcaciv' => 'AgfOPCASessContactCiv',
				'contactsessopca.lastname as contactsessopcafirstname' => 'AgfOPCASessContactFirstName',
				'contactsessopca.firstname as contactsessopcalastname' => 'AgfOPCASessContactLastName',
				'contactsession.firstname as contactsessionfirstname' => 'AgfSessionContactFirstName',
				'contactsession.lastname as contactsessionlastname' => 'AgfSessionContactLastName',
				'contactsession.email as contactsessionemail' => 'AgfSessionContactEmail',
				'contactsession.phone as contactsessionphone' => 'AgfSessionContactPhone',
				'socpresta.nom as prestanom' => 'AgfTypePresta',
				'presta.civility as prestasessciv' => 'AgfTypePrestaCiv',
				'presta.lastname as prestasesslastname' => 'AgfTypePrestaFirstName',
				'presta.firstname as prestasessfirstname' => 'AgfTypePrestaLastName',
				'c.intitule' => 'AgfFormIntitule',
				'c.ref' => 'Ref',
				'c.ref_interne' => 'AgfFormCodeInterne',
				'c.duree' => 'AgfDuree',
				'dictcat.code as catcode' => 'AgfTrainingCategCode',
				'dictcat.intitule as catlib' => 'AgfTrainingCategLabel',
				'product.ref' => 'ProductRef',
				'product.label' => 'ProductLabel',
				'product.price' => 'SellingPriceTTC',
				'product.accountancy_code_buy' => 'ProductAccountancySellCode',
				'p.ref_interne' => 'AgfLieu',
				'p.adresse' => 'Address',
				'p.cp' => 'Zip',
				'p.ville' => 'Town',
				'p_pays.label as country' => 'Country',
				'CASE WHEN f.type_trainer=\'user\' THEN fu.civility ELSE fp.civility END as trainerciv' => 'AgfTrainerCiv',
				'CASE WHEN f.type_trainer=\'user\' THEN fu.lastname ELSE fp.lastname END as trainerlastname' => 'AgfTrainerLastname',
				'CASE WHEN f.type_trainer=\'user\' THEN fu.firstname ELSE fp.firstname END as trainerfirstname' => 'AgfTrainerCivFirstname',
				'trainerdicttype.intitule as trainertype' => 'AgfTrainerType',
				'so.nom as cust_name' => 'Customer',
				'sta.civilite as traineeciv' => 'AgfCivilite',
				'sta.nom as traineelastname' => 'AgfStaLastname',
				'sta.prenom as traineefirstname' => 'AgfStaFirstname',
				'sta.mail as traineemail' => 'AgfStaMail',
				'sta.date_birth' => "DateToBirth",
				'sta.tel1' => "Phone",
				'sta.tel2' => "Mobile",
				'sta.place_birth' => "AgfPlaceBirth",
				'ssdicttype.intitule as statype' => 'AgfStagiaireModeFinancement',
				'sosta.nom as traineecustomer' => 'Customer',
				's.is_opca as staisopca' => 'AgfSubrocation',
				'socstaopca.nom as stasocopca' => 'AgfOPCAName',
				'contactstaopca.civility as contactstaopcaciv' => 'AgfOPCAStaContactCiv',
				'contactstaopca.lastname as contactstaopcalastname' => 'AgfOPCAStaContactLastName',
				'contactstaopca.firstname as contactstaopcafirstname' => 'AgfOPCAStaContactFirstName'
		);

		$this->export_TypeFields_array[$r] = array(
				'c.rowid' => "Text",
				'c.intitule' => 'Text',
				'c.ref' => 'Text',
				'c.ref_interne' => 'Text',
				's.dated' => 'Date',
				's.datef' => 'Date',
				'sosta.nom' => 'Text',
				's.ref'=>'Text',
				's.rowid'=>'Text',
				'sta.date_birth' => "Date",
				'sta.tel1' => "Text",
				'sta.tel2' => "Text",
		);
		$this->export_entities_array[$r] = array(
				's.rowid' => "AgfSessionDetail",
				's.ref' => "AgfSessionDetail",
				'CASE WHEN s.type_session=0 THEN \'Intra\' ELSE \'Inter\' END as type_session' => 'AgfSessionDetail',
				's.dated' => 'AgfSessionDetail',
				's.datef' => 'AgfSessionDetail',
				'cal.heured' => 'AgfSessionDetail',
				'cal.heuref' => 'AgfSessionDetail',
				's.nb_stagiaire' => 'AgfSessionDetail',
				's.notes' => 'AgfSessionDetail',
				's.cost_trainer' => 'AgfSessionDetail',
				's.cost_site' => 'AgfSessionDetail',
				's.cost_trip' => 'AgfSessionDetail',
				's.sell_price' => 'AgfSessionDetail',
				'statusdict.code as sessionstatus' => 'AgfSessionDetail',
				's.is_opca as sessionisopca' => 'AgfSessionDetail',
				'socsessopca.nom as sessionsocopca' => 'AgfSessionDetail',
				'contactsessopca.civility as contactsessopcaciv' => 'AgfSessionDetail',
				'contactsessopca.lastname as contactsessopcafirstname' => 'AgfSessionDetail',
				'contactsessopca.firstname as contactsessopcalastname' => 'AgfSessionDetail',
				'contactsession.firstname as contactsessionfirstname' => 'AgfSessionDetail',
				'contactsession.lastname as contactsessionlastname' => 'AgfSessionDetail',
				'contactsession.email as contactsessionemail' => 'AgfSessionDetail',
				'contactsession.phone as contactsessionphone' => 'AgfSessionDetail',
				'socpresta.nom as prestanom' => 'AgfSessionDetail',
				'presta.civility as prestasessciv' => 'AgfSessionDetail',
				'presta.lastname as prestasesslastname' => 'AgfSessionDetail',
				'presta.firstname as prestasessfirstname' => 'AgfSessionDetail',
				'c.intitule' => 'AgfCatalogDetail',
				'c.ref' => 'AgfCatalogDetail',
				'c.ref_interne' => 'AgfCatalogDetail',
				'c.duree' => 'AgfCatalogDetail',
				'dictcat.code as catcode' => 'AgfCatalogDetail',
				'dictcat.intitule as catlib' => 'AgfCatalogDetail',
				'product.ref' => 'Product',
				'product.label' => 'Product',
				'product.price' => 'Product',
				'product.accountancy_code_buy' => 'Product',
				'p.ref_interne' => 'AgfSessPlace',
				'p.adresse' => 'AgfSessPlace',
				'p.cp' => 'AgfSessPlace',
				'p.ville' => 'AgfSessPlace',
				'p_pays.label as country' => 'AgfSessPlace',
				'CASE WHEN f.type_trainer=\'user\' THEN fu.civility ELSE fp.civility END as trainerciv' => 'AgfTeacher',
				'CASE WHEN f.type_trainer=\'user\' THEN fu.lastname ELSE fp.lastname END as trainerlastname' => 'AgfTeacher',
				'CASE WHEN f.type_trainer=\'user\' THEN fu.firstname ELSE fp.firstname END as trainerfirstname' => 'AgfTeacher',
				'trainerdicttype.intitule as trainertype' => 'AgfTeacher',
				'so.nom as cust_name' => 'AgfSessionDetail',
				'sta.civilite as traineeciv' => 'AgfNbreParticipants',
				'sta.nom as traineelastname' => 'AgfNbreParticipants',
				'sta.prenom as traineefirstname' => 'AgfNbreParticipants',
				'sta.mail as traineemail' => 'AgfNbreParticipants',
				'sta.date_birth' => "AgfNbreParticipants",
				'sta.place_birth' => "AgfNbreParticipants",
				'sta.tel1' => "AgfNbreParticipants",
				'sta.tel2' => "AgfNbreParticipants",
				'ssdicttype.intitule as statype' => 'AgfNbreParticipants',
				'sosta.nom as traineecustomer' => 'AgfNbreParticipants',
				's.is_opca as staisopca' => 'AgfNbreParticipants',
				'socstaopca.nom as stasocopca' => 'AgfNbreParticipants',
				'contactstaopca.civility as contactstaopcaciv' => 'AgfNbreParticipants',
				'contactstaopca.lastname as contactstaopcalastname' => 'AgfNbreParticipants',
				'contactstaopca.firstname as contactstaopcafirstname' => 'AgfNbreParticipants'
		);

		$keyforselect = 'agefodd_stagiaire';
		$keyforelement = 'AgfMailTypeContactTrainee';
		$keyforaliasextra = 'extratrainee';
		include DOL_DOCUMENT_ROOT . '/core/extrafieldsinexport.inc.php';

		$keyforselect = 'agefodd_formation_catalogue';
		$keyforelement = 'AgfCatalogDetail';
		$keyforaliasextra = 'extracatalogue';
		include DOL_DOCUMENT_ROOT . '/core/extrafieldsinexport.inc.php';

		$keyforselect = 'agefodd_session';
		$keyforelement = 'AgfSessionDetail';
		$keyforaliasextra = 'extrasession';
		include DOL_DOCUMENT_ROOT . '/core/extrafieldsinexport.inc.php';

		$keyforselect = 'agefodd_stagiaire';
		$keyforelement = 'AgfNbreParticipants';
		$keyforaliasextra = 'extratrainee';
		include DOL_DOCUMENT_ROOT . '/core/extrafieldsinexport.inc.php';


		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r] = ' FROM ' . MAIN_DB_PREFIX . 'agefodd_session as s';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_calendrier as cal ON s.rowid = cal.fk_agefodd_session';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_formation_catalogue as c ON c.rowid = s.fk_formation_catalogue';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_formation_catalogue_extrafields as extracatalogue ON c.rowid = extracatalogue.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_place as p ON p.rowid = s.fk_session_place';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_stagiaire as ss ON s.rowid = ss.fk_session_agefodd';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire as sta ON sta.rowid = ss.fk_stagiaire';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire_type as ssdicttype ON ssdicttype.rowid = ss.fk_agefodd_stagiaire_type';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as so ON so.rowid = s.fk_soc';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_formateur as sf ON sf.fk_session = s.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_formateur_type as trainerdicttype ON trainerdicttype.rowid = sf.fk_agefodd_formateur_type';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_formateur as f ON f.rowid = sf.fk_agefodd_formateur';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as fu ON fu.rowid = f.fk_user';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as fp ON fp.rowid = f.fk_socpeople';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_formation_catalogue_type as dictcat ON dictcat.rowid = c.fk_c_category';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_country as p_pays ON p_pays.rowid = p.fk_pays';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product as product ON product.rowid = c.fk_product';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as socsessopca ON socsessopca.rowid = s.fk_soc_opca';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as contactsessopca ON contactsessopca.rowid = s.fk_socpeople_opca';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_opca as staopca ON staopca.fk_session_agefodd=s.rowid AND (staopca.fk_soc_trainee=sta.fk_soc OR staopca.fk_session_trainee=ss.rowid)';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as socstaopca ON socstaopca.rowid = staopca.fk_soc_opca';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as contactstaopca ON contactstaopca.rowid = staopca.fk_socpeople_opca';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_status_type as statusdict ON statusdict.rowid = s.status';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_contact as sesscontact ON sesscontact.fk_session_agefodd = s.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_contact as agfcontact ON agfcontact.rowid = sesscontact.fk_agefodd_contact';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as contactsession ON contactsession.rowid = agfcontact.fk_socpeople';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_stagiaire_extrafields as extratrainee ON extratrainee.fk_object = sta.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_session_extrafields as extrasession ON extrasession.fk_object = s.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as sosta ON sosta.rowid = sta.fk_soc';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople as presta ON s.fk_socpeople_presta = presta.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as socpresta ON socpresta.rowid = presta.fk_soc';
		$this->export_sql_end[$r] .= ' WHERE 1=1 ';

		// training export
		$r ++;
		$this->export_code[$r] = $this->rights_class . '_' . $r;
		$this->export_label[$r] = 'ExportDataset_TrainingProgram';
		$this->export_icon[$r] = 'bill';
		$this->export_permission[$r] = array(
				array(
						"agefodd",
						"export"
				)
		);
		$this->export_fields_array[$r] = array(
				's.rowid' => 'Id',
				's.ref' => 'Ref',
				's.ref_interne' => 'AgfRefInterne',
				's.intitule' => "AgfIntitule",
				's.duree' => "AgfDuree",
				's.public' => "AgfPublic",
				's.methode' => "AgfMethode",
				's.but' => "AgfBut",
				's.programme' => "AgfProgramme",
				's.pedago_usage' => "AgfPedagoUsage",
				's.sanction' => "AgfSanction",
				's.prerequis' => "AgfPrerequis",
				's.fk_product' => "AgfProductServiceLinked",
				's.archive' => "AgfArchiver",
				's.note_private' => "NotePrivate",
				's.note_public' => "NotePublic",
				's.nb_subscribe_min' => "AgfNbMintarget",
				's.fk_c_category' => "AgfTrainingCateg",
				's.certif_duration' => "AgfCertificateDuration",
				's.color' => "Color"
		);

		$this->export_TypeFields_array[$r] = array(
				's.rowid' => "Text",
				's.ref' => "Text",
				's.ref_interne' => 'Text',
				's.intitule' => "Text",
				's.duree' => "Text",
				's.public' => "Text",
				's.methode' => "Text",
				's.but' => "Text",
				's.programme' => "Text",
				's.pedago_usage' => "Text",
				's.sanction' => "Text",
				's.prerequis' => "Text",
				's.fk_product' => "Text",
				's.archive' => "Text",
				's.note_private' => "Text",
				's.note_public' => "Text",
				's.nb_subscribe_min' => "Text",
				's.fk_c_category' => "Text",
				's.certif_duration' => "Text",
				's.color' => "Text"
		);
		$this->export_entities_array[$r] = array(
				's.rowid' => "AgfCatalogDetail",
				's.ref' => "AgfCatalogDetail",
				's.ref_interne' => 'AgfCatalogDetail',
				's.intitule' => "AgfCatalogDetail",
				's.duree' => "AgfCatalogDetail",
				's.public' => "AgfCatalogDetail",
				's.methode' => "AgfCatalogDetail",
				's.but' => "AgfCatalogDetail",
				's.programme' => "AgfCatalogDetail",
				's.pedago_usage' => "AgfCatalogDetail",
				's.sanction' => "AgfCatalogDetail",
				's.prerequis' => "AgfCatalogDetail",
				's.fk_product' => "AgfCatalogDetail",
				's.archive' => "AgfCatalogDetail",
				's.note_private' => "AgfCatalogDetail",
				's.note_public' => "AgfCatalogDetail",
				's.nb_subscribe_min' => "AgfCatalogDetail",
				's.fk_c_category' => "AgfCatalogDetail",
				's.certif_duration' => "AgfCatalogDetail",
				's.color' => "AgfCatalogDetail"
		);

		$keyforselect = 'agefodd_formation_catalogue';
		$keyforelement = 'AgfCatalogDetail';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT . '/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r] = ' FROM ' . MAIN_DB_PREFIX . 'agefodd_formation_catalogue as s';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'agefodd_formation_catalogue_extrafields as extra ON extra.fk_object = s.rowid';
		$this->export_sql_end[$r] .= ' WHERE 1=1 ';

		// Array to add new pages in new tabs
		// $this->tabs = array('entity:Title:@mymodule:/mymodule/mynewtab.php?id=__ID__');
		// where entity can be
		// 'thirdparty' to add a tab in third party view
		// 'intervention' to add a tab in intervention view
		// 'supplier_order' to add a tab in supplier order view
		// 'supplier_invoice' to add a tab in supplier invoice view
		// 'invoice' to add a tab in customer invoice view
		// 'order' to add a tab in customer order view
		// 'product' to add a tab in product view
		// 'propal' to add a tab in propal view
		// 'member' to add a tab in fundation member view
		// 'contract' to add a tab in contract view
		// Array to add new pages in new tabs
		// Array to add new pages in new tabs
		$this->tabs = array(
				'order:+tabAgefodd:AgfMenuSess:agefodd@agefodd:$user->rights->agefodd->lire:/agefodd/session/list_fin.php?search_orderid=__ID__',
				'invoice:+tabAgefodd:AgfMenuSess:agefodd@agefodd:$user->rights->agefodd->lire:/agefodd/session/list_fin.php?search_invoiceid=__ID__',
				'propal:+tabAgefodd:AgfMenuSess:agefodd@agefodd:$user->rights->agefodd->lire:/agefodd/session/list_fin.php?search_propalid=__ID__',
				'thirdparty:+tabAgefodd:AgfMenuSess:agefodd@agefodd:$user->rights->agefodd->lire:/agefodd/session/list_soc.php?socid=__ID__',
				'supplier_invoice:+tabAgefodd:AgfMenuSess:agefodd@agefodd:$user->rights->agefodd->lire:/agefodd/session/list_fin.php?search_fourninvoiceid=__ID__',
		        'supplier_order:+tabAgefodd:AgfMenuSess:agefodd@agefodd:$user->rights->agefodd->lire:/agefodd/session/list_fin.php?search_fournorderid=__ID__',
		        'contact:+tabAgefodd:Module103000Name:agefodd@agefodd:$user->rights->agefodd->lire:/agefodd/contact/contact_card.php?id=__ID__',
                'contact:+tabAgefoddSessionList:SUBSTITUTION_AGFSESSIONLIST:agefodd@agefodd:$user->rights->agefodd->lire:/agefodd/contact/session_list.php?id=__ID__',
		);

		// Boxes
		$this->boxes = array();
		$r = 0;

		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
		// Example:
		$this->boxes[$r][1] = "box_agefodd_board.php@agefodd";
		$r++;
		$this->boxes[$r][1] = "box_agefodd_lastsession.php@agefodd";
		$r++;
		$this->boxes[$r][1] = "box_agefodd_preferedtraining.php@agefodd";
		$r++;
		$this->boxes[$r][1] = "box_agefodd_stats.php@agefodd";


		// Permissions
		$this->rights = array();
		$r = 0;

		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_ShowSessions';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'lire';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_EditSessions';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'modifier';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_CreateSessions';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_DeleteSessions';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_ShowStats';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'viewstats';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_Export';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_Agenda';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'agenda';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_Agendatrainer';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'agendatrainer';
		$r ++;

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_ShowCatalogTrainings';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'agefodd_formation_catalogue';
		$this->rights[$r][5] = 'lire';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_CreateEditCatalogTrainings';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'agefodd_formation_catalogue';
		$this->rights[$r][5] = 'creer';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_DeleteCatalogTrainings';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'agefodd_formation_catalogue';
		$this->rights[$r][5] = 'supprimer';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_ShowPlaces';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'agefodd_place';
		$this->rights[$r][5] = 'lire';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_CreateEditPlaces';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'agefodd_place';
		$this->rights[$r][5] = 'creer';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_DeletePlaces';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'agefodd_place';
		$this->rights[$r][5] = 'supprimer';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_SeeAllSession';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'session';
		$this->rights[$r][5] = 'all';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_SeeSessionMargin';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'session';
		$this->rights[$r][5] = 'margin';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_SeeReports';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'report';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_SeeBPFReports';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'report';
		$this->rights[$r][5] = 'bpf';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_AdminAgefodd';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'admin';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_SeeLocationAgenda';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'agendalocation';
		$this->rights[$r][5] = 'all';

		$r ++;
		$this->rights[$r][0] = $this->numero . $r;
		$this->rights[$r][1] = 'AGFR_Trainermode';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'session';
		$this->rights[$r][5] = 'trainer';

		$r ++;
        if (!empty($conf->externalaccess->enabled)) {
            $this->rights[$r][0] = $this->numero . $r;
            $this->rights[$r][1] = 'AgfEATrainerRead';
            $this->rights[$r][2] = 'r';
            $this->rights[$r][3] = 0;
            $this->rights[$r][4] = 'external_trainer_read';
        }

        $r ++;
        if (!empty($conf->externalaccess->enabled)) {
		    $this->rights[$r][0] = $this->numero . $r;
		    $this->rights[$r][1] = 'AgfEATrainerWrite';
		    $this->rights[$r][2] = 'w';
		    $this->rights[$r][3] = 0;
		    $this->rights[$r][4] = 'external_trainer_write';
        }

        $r ++;
        if (!empty($conf->externalaccess->enabled)) {
		    $this->rights[$r][0] = $this->numero . $r;
		    $this->rights[$r][1] = 'AgfEATrainerDownload';
		    $this->rights[$r][2] = 'r';
		    $this->rights[$r][3] = 0;
		    $this->rights[$r][4] = 'external_trainer_download';
        }

        $r ++;
        if (!empty($conf->externalaccess->enabled)) {
		    $this->rights[$r][0] = $this->numero . $r;
		    $this->rights[$r][1] = 'AgfEATrainerUpload';
		    $this->rights[$r][2] = 'w';
		    $this->rights[$r][3] = 0;
		    $this->rights[$r][4] = 'external_trainer_upload';
		}

        $r ++;
        if (!empty($conf->questionnaire->enabled)) {
            $this->rights[$r][0] = $this->numero . $r;    // Permission id (must not be already used)
            $this->rights[$r][1] = 'AgfQuestionnaireLinkRight';    // Permission label
            $this->rights[$r][3] = 0;                    // Permission by default for new user (0/1)
            $this->rights[$r][4] = 'questionnaire';                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
            $this->rights[$r][5] = 'link';                // In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        }

        $r ++;
        if (!empty($conf->questionnaire->enabled)) {
            $this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
            $this->rights[$r][1] = 'AgfQuestionnaireSendRight';	// Permission label
            $this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
            $this->rights[$r][4] = 'questionnaire';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
            $this->rights[$r][5] = 'send';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
        }


        // MORE External access rights for agefodd

        $r ++;
        if (!empty($conf->externalaccess->enabled)) {
            $this->rights[$r][0] = $this->numero . $r;
            $this->rights[$r][1] = 'AgfEATrainerAgenda';
            $this->rights[$r][3] = 0;
            $this->rights[$r][4] = 'external_trainer_agenda';
        }

        $r ++;
        if (!empty($conf->externalaccess->enabled)) {
            $this->rights[$r][0] = $this->numero . $r;
            $this->rights[$r][1] = 'AgfEATrainee';
            $this->rights[$r][3] = 0;
            $this->rights[$r][4] = 'external_trainee_read';
        }

        $r ++;
        if (!empty($conf->externalaccess->enabled)) {
            $this->rights[$r][0] = $this->numero . $r;
            $this->rights[$r][1] = 'AgfEATrainerTimeslotDelete';
            $this->rights[$r][3] = 0;
            $this->rights[$r][4] = 'external_trainer_time_slot_delete';
        }

        $r ++;
        if (!empty($conf->externalaccess->enabled)) {
            $this->rights[$r][0] = $this->numero . $r;
            $this->rights[$r][1] = 'AgfEATrainerAndTraineeAccessSessionLink';
            $this->rights[$r][3] = 0;
            $this->rights[$r][4] = 'external_access_link_attatchement';
        }

		$r ++;
		if (!empty($conf->externalaccess->enabled)) {
			$this->rights[$r][0] = $this->numero . $r;
			$this->rights[$r][1] = 'AgfEATrainerSeeOtherTrainerIdentityPlanedTime';
			$this->rights[$r][3] = 0;
			$this->rights[$r][4] = 'external_trainer_seeotrainerplantime';
		}

		// Main menu entries
		$this->menus = array();
		$r = 0;
		$this->menu[$r] = array(
				'fk_menu' => 0,
				'type' => 'top',
				'titre' => 'Module103000Name',
				'mainmenu' => 'agefodd',
				'leftmenu' => '0',
				'url' => '/agefodd/index.php',
				'langs' => 'agefodd@agefodd',
				'position' => 100,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 2
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd',
				'type' => 'left',
				'titre' => 'AgfMenuCat',
				'leftmenu' => 'AgfMenuCat',
				'url' => '/agefodd/training/list.php',
				'langs' => 'agefodd@agefodd',
				'position' => 100 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->agefodd_formation_catalogue->lire',
				'perms' => '$user->rights->agefodd->agefodd_formation_catalogue->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuCat',
				'type' => 'left',
				'titre' => 'AgfMenuCatNew',
				'url' => '/agefodd/training/card.php?action=create',
				'langs' => 'agefodd@agefodd',
				'position' => 100 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->agefodd_formation_catalogue->creer',
				'perms' => '$user->rights->agefodd->agefodd_formation_catalogue->creer',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuCat',
				'type' => 'left',
				'titre' => 'List',
				'leftmenu' => 'AgfMenuCatList',
				'mainmenu' => 'agefodd',
				'url' => '/agefodd/training/list.php?leftmenu=AgfMenuCatList',
				'langs' => 'agefodd@agefodd',
				'position' => 100 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->agefodd_formation_catalogue->lire',
				'perms' => '$user->rights->agefodd->agefodd_formation_catalogue->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuCatList',
				'type' => 'left',
				'titre' => 'AgfMenuCatListActivees',
				'mainmenu' => 'agefodd',
				'url' => '/agefodd/training/list.php',
				'langs' => 'agefodd@agefodd',
				'position' => 100 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->agefodd_formation_catalogue->lire',
				'perms' => '$user->rights->agefodd->agefodd_formation_catalogue->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuCatList',
				'type' => 'left',
				'titre' => 'AgfMenuCatListArchivees',
				'mainmenu' => 'agefodd',
				'url' => '/agefodd/training/list.php?arch=1',
				'langs' => 'agefodd@agefodd',
				'position' => 100 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire && empty($user->rights->agefodd->session->trainer)',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd',
				'type' => 'left',
				'titre' => 'AgfMenuSess',
				'leftmenu' => 'AgfMenuSess',
				'url' => '/agefodd/session/list.php?leftmenu=AgfMenuSessList',
				'langs' => 'agefodd@agefodd',
				'position' => 200 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSess',
				'type' => 'left',
				'titre' => 'AgfMenuSessNew',
				'url' => '/agefodd/session/card.php?action=create',
				'langs' => 'agefodd@agefodd',
				'position' => 200 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->creer',
				'perms' => '$user->rights->agefodd->creer',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSess',
				'type' => 'left',
				'leftmenu' => 'AgfMenuSessList',
				'titre' => 'List',
				'url' => '/agefodd/session/list.php?leftmenu=AgfMenuSessList',
				'langs' => 'agefodd@agefodd',
				'position' => 200 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSessList',
				'type' => 'left',
				'titre' => 'AgfMenuSessDraftList',
				'url' => '/agefodd/session/list.php?search_session_status=1&leftmenu=AgfMenuSessList',
				'langs' => 'agefodd@agefodd',
				'position' => 200 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSessList',
				'type' => 'left',
				'titre' => 'AgfMenuSessConfList',
				'url' => '/agefodd/session/list.php?search_session_status=2&leftmenu=AgfMenuSessList',
				'langs' => 'agefodd@agefodd',
				'position' => 200 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSessList',
				'type' => 'left',
				'titre' => 'AgfMenuSessNotDoneList',
				'url' => '/agefodd/session/list.php?search_session_status=3',
				'langs' => 'agefodd@agefodd',
				'position' => 200 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSessList',
				'type' => 'left',
				'titre' => 'AgfMenuSessDoneList',
				'url' => '/agefodd/session/list.php?search_session_status=5&leftmenu=AgfMenuSessList',
				'langs' => 'agefodd@agefodd',
				'position' => 200 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSessList',
				'type' => 'left',
				'titre' => 'AgfMenuSessOnGoingList',
				'url' => '/agefodd/session/list.php?search_session_status=6&leftmenu=AgfMenuSessList',
				'langs' => 'agefodd@agefodd',
				'position' => 200 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire && empty($user->rights->agefodd->session->trainer)',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSessList',
				'type' => 'left',
				'titre' => 'AgfMenuSessArchList',
				'url' => '/agefodd/session/list.php?search_session_status=4&leftmenu=AgfMenuSessList',
				'langs' => 'agefodd@agefodd',
				'position' => 200 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSessList',
				'type' => 'left',
				'titre' => 'AgfMenuSessListOpe',
				'url' => '/agefodd/session/list_ope.php?leftmenu=AgfMenuSessList',
				'langs' => 'agefodd@agefodd',
				'position' => 200 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire && empty($user->rights->agefodd->session->trainer)',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSessList',
				'type' => 'left',
				'titre' => 'AgfMenuSessListOpeInter',
				'url' => '/agefodd/session/list_ope_inter.php?leftmenu=AgfMenuSessList',
				'langs' => 'agefodd@agefodd',
				'position' => 200 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire && !empty($conf->global->AGEFODD_OPE_INTER_ENABLED)',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSess',
				'type' => 'left',
				'leftmenu' => 'AgfMenuSessTools',
				'titre' => 'AgfTools',
				'url' => '/agefodd/session/archive_year.php?leftmenu=AgfMenuSessTools',
				'langs' => 'agefodd@agefodd',
				'position' => 200 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->creer && empty($user->rights->agefodd->session->trainer)',
				'perms' => '$user->rights->agefodd->creer',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSessTools',
				'type' => 'left',
				'titre' => 'AgfMenuSessArchiveByYear',
				'url' => '/agefodd/session/archive_year.php',
				'langs' => 'agefodd@agefodd',
				'position' => 200 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->creer && empty($user->rights->agefodd->session->trainer)',
				'perms' => '$user->rights->agefodd->creer',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSess',
				'type' => 'left',
				'titre' => 'AgfMenuSessStats',
				'url' => '/agefodd/session/stats/index.php',
				'langs' => 'agefodd@agefodd',
				'position' => 200 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->viewstats && empty($user->rights->agefodd->session->trainer)',
				'perms' => '$user->rights->agefodd->viewstats',
				'target' => '',
				'user' => 0
		);

		/*	$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSess',
		 'type'=>'left',
		 'titre'=>'AgfMenuSessListOpeInter',
		 'url'=>'/agefodd/session/list_ope_inter.php',
		 'langs'=>'agefodd@agefodd',
		 'position'=>209,
		 'enabled'=>'$user->rights->agefodd->lire',
		 'perms'=>'$user->rights->agefodd->lire',
		 'target'=>'',
		 'user'=>0);
		 $r++;*/

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd',
				'type' => 'left',
				'titre' => 'AgfMenuActStagiaire',
				'leftmenu' => 'AgfMenuActStagiaire',
				'url' => '/agefodd/trainee/list.php',
				'langs' => 'agefodd@agefodd',
				'position' => 300 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuActStagiaire',
				'type' => 'left',
				'titre' => 'AgfMenuActStagiaireNew',
				'url' => '/agefodd/trainee/card.php?action=create',
				'langs' => 'agefodd@agefodd',
				'position' => 300 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->creer',
				'perms' => '$user->rights->agefodd->creer',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuActStagiaire',
				'type' => 'left',
				'titre' => 'AgfMenuActStagiaireNewFromContact',
				'url' => '/agefodd/trainee/card.php?action=create&importfrom=contact',
				'langs' => 'agefodd@agefodd',
				'position' => 300 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->creer',
				'perms' => '$user->rights->agefodd->creer',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuActStagiaire',
				'type' => 'left',
				'leftmenu' => 'AgfMenuActStagiaireList',
				'titre' => 'List',
				'url' => '/agefodd/trainee/list.php',
				'langs' => 'agefodd@agefodd',
				'position' => 300 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuActStagiaireList',
				'type' => 'left',
				'titre' => 'AgfMenuActStagiaireList',
				'url' => '/agefodd/trainee/list.php',
				'langs' => 'agefodd@agefodd',
				'position' => 300 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuActStagiaireList',
				'type' => 'left',
				'titre' => 'AgfCertificate',
				'url' => '/agefodd/certificate/list.php',
				'langs' => 'agefodd@agefodd',
				'position' => 300 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->creer && !empty($conf->global->AGF_MANAGE_CERTIF)',
				'perms' => '$user->rights->agefodd->creer',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd',
				'type' => 'left',
				'titre' => 'AgfMenuSite',
				'leftmenu' => 'AgfMenuSite',
				'url' => '/agefodd/site/list.php',
				'langs' => 'agefodd@agefodd',
				'position' => 400 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->agefodd_place->lire && empty($user->rights->agefodd->session->trainer)',
				'perms' => '$user->rights->agefodd->agefodd_place->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSite',
				'type' => 'left',
				'titre' => 'AgfMenuSiteCreate',
				'url' => '/agefodd/site/card.php?action=create',
				'langs' => 'agefodd@agefodd',
				'position' => 400 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->agefodd_place->creer && empty($user->rights->agefodd->session->trainer)',
				'perms' => '$user->rights->agefodd->agefodd_place->creer',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuSite',
				'type' => 'left',
				'titre' => 'List',
				'url' => '/agefodd/site/list.php',
				'langs' => 'agefodd@agefodd',
				'position' => 400 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->agefodd_place->lire && empty($user->rights->agefodd->session->trainer)',
				'perms' => '$user->rights->agefodd->agefodd_place->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd',
				'type' => 'left',
				'titre' => 'AgfMenuFormateur',
				'leftmenu' => 'AgfMenuFormateur',
				'url' => '/agefodd/trainer/list.php',
				'langs' => 'agefodd@agefodd',
				'position' => 500 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire && empty($user->rights->agefodd->session->trainer)',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuFormateur',
				'type' => 'left',
				'titre' => 'AgfMenuFormateurCreate',
				'url' => '/agefodd/trainer/card.php?action=create',
				'langs' => 'agefodd@agefodd',
				'position' => 500 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire && empty($user->rights->agefodd->session->trainer)',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuFormateur',
				'type' => 'left',
				'titre' => 'List',
				'url' => '/agefodd/trainer/list.php',
				'langs' => 'agefodd@agefodd',
				'position' => 500 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire && empty($user->rights->agefodd->session->trainer)',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd',
				'type' => 'left',
				'titre' => 'AgfMenuContact',
				'leftmenu' => 'AgfMenuContact',
				'url' => '/agefodd/contact/list.php',
				'langs' => 'agefodd@agefodd',
				'position' => 600 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire && empty($conf->global->AGF_CONTACT_DOL_SESSION)',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuContact',
				'type' => 'left',
				'titre' => 'AgfMenuContactCreate',
				'url' => '/agefodd/contact/card.php?action=create',
				'langs' => 'agefodd@agefodd',
				'position' => 600 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire && empty($conf->global->AGF_CONTACT_DOL_SESSION)',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuContact',
				'type' => 'left',
				'titre' => 'List',
				'url' => '/agefodd/contact/list.php',
				'langs' => 'agefodd@agefodd',
				'position' => 600 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->lire && empty($conf->global->AGF_CONTACT_DOL_SESSION)',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd',
				'type' => 'left',
				'titre' => 'AgfMenuAgenda',
				'leftmenu' => 'AgfMenuAgenda',
				'url' => '/agefodd/agenda/index.php',
				'langs' => 'agefodd@agefodd',
				'position' => 700 + $r,
				'enabled' => '$conf->agefodd->enabled && ($user->rights->agefodd->lire || $user->rights->agefodd->agendatrainer)',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 2
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuAgenda',
				'type' => 'left',
				'titre' => 'AgfMenuAgenda',
				'url' => '/agefodd/agenda/index.php',
				'langs' => 'agefodd@agefodd',
				'position' => 700 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->agenda',
				'perms' => '$user->rights->agefodd->agenda',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuAgenda',
				'type' => 'left',
				'titre' => 'AgfMenuAgendaTrainerOnly',
				'url' => '/agefodd/agenda/pertrainer.php',
				'langs' => 'agefodd@agefodd',
				'position' => 700 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->agenda && ! $user->rights->agefodd->session->trainer',
				'perms' => '$user->rights->agefodd->agenda',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuAgenda',
				'type' => 'left',
				'titre' => 'AgfMenuAgendaTrainer',
				'url' => '/agefodd/agenda/pertrainer.php?type=trainerext',
				'langs' => 'agefodd@agefodd',
				'position' => 700 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->agendatrainer',
				'perms' => '$user->rights->agefodd->agendatrainer',
				'target' => '',
				'user' => 2
		);

		$r ++;
		$this->menu [$r] = array (
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuAgenda',
				'type' => 'left',
				'titre' => 'AgfMenuAgendaPerLocation',
				'url' => '/agefodd/agenda/perlocation.php',
				'langs' => 'agefodd@agefodd',
				'position' => 700 + $r,
				'enabled' => '$user->rights->agefodd->agendalocation',
				'perms' => '$user->rights->agefodd->agendalocation',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd',
				'type' => 'left',
				'titre' => 'AgfMenuCursus',
				'leftmenu' => 'AgfMenuCursus',
				'url' => '/agefodd/cursus/list.php',
				'langs' => 'agefodd@agefodd',
				'position' => 800 + $r,
				'enabled' => '$conf->agefodd->enabled && $conf->global->AGF_MANAGE_CURSUS',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuCursus',
				'type' => 'left',
				'titre' => 'AgfMenuCursusNew',
				'url' => '/agefodd/cursus/card.php?action=create',
				'langs' => 'agefodd@agefodd',
				'position' => 800 + $r,
				'enabled' => '$conf->agefodd->enabled && $conf->global->AGF_MANAGE_CURSUS',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuCursus',
				'type' => 'left',
				'titre' => 'AgfMenuCursusList',
				'url' => '/agefodd/cursus/list.php',
				'langs' => 'agefodd@agefodd',
				'position' => 800 + $r,
				'enabled' => '$conf->agefodd->enabled && $conf->global->AGF_MANAGE_CURSUS',
				'perms' => '$user->rights->agefodd->lire',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd',
				'type' => 'left',
				'titre' => 'AgfMenuReport',
				'leftmenu' => 'AgfMenuReport',
				'url' => '/agefodd/index.php',
				'langs' => 'agefodd@agefodd',
				'position' => 900 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->report',
				'perms' => '$user->rights->agefodd->report',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuReport',
				'type' => 'left',
				'titre' => 'AgfMenuReportBPF',
				'url' => '/agefodd/report/report_bpf.php',
				'langs' => 'agefodd@agefodd',
				'position' => 900 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->report->bpf && $conf->global->AGF_MANAGE_BPF',
				'perms' => '$user->rights->agefodd->report->bpf',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu [$r] = array (
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuReport',
				'type' => 'left',
				'titre' => 'AgfMenuReportByCustomer',
				'url' => '/agefodd/report/report_by_customer.php',
				'langs' => 'agefodd@agefodd',
				'position' => 900 + $r,
				'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->report',
				'perms' => '$user->rights->agefodd->report',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu [$r] = array (
		    'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuReport',
		    'type' => 'left',
		    'titre' => 'AgfMenuReportCalendarByCustomer',
		    'url' => '/agefodd/report/report_calendar_by_customer.php',
		    'langs' => 'agefodd@agefodd',
		    'position' => 900 + $r,
		    'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->report',
		    'perms' => '$user->rights->agefodd->report',
		    'target' => '',
		    'user' => 0
		);

		$r ++;
		$this->menu [$r] = array (
		    'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuReport',
		    'type' => 'left',
		    'titre' => 'AgfMenuReportCA',
		    'url' => '/agefodd/report/report_ca.php',
		    'langs' => 'agefodd@agefodd',
		    'position' => 900 + $r,
		    'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->report',
		    'perms' => '$user->rights->agefodd->report',
		    'target' => '',
		    'user' => 0
		);

		$r ++;
		$this->menu [$r] = array (
			'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuReport',
			'type' => 'left',
			'titre' => 'AgfMenuReportCommercial',
			'url' => '/agefodd/report/report_commercial.php',
			'langs' => 'agefodd@agefodd',
			'position' => 900 + $r,
			'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->report',
			'perms' => '$user->rights->agefodd->report',
			'target' => '',
			'user' => 0
		);

		$r ++;
		$this->menu [$r] = array (
			'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuReport',
			'type' => 'left',
			'titre' => 'AgfMenuReportCAVentilated',
			'url' => '/agefodd/report/report_ca_ventilated.php',
			'langs' => 'agefodd@agefodd',
			'position' => 900 + $r,
			'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->report',
			'perms' => '$user->rights->agefodd->report',
			'target' => '',
			'user' => 0
		);

		$r ++;
		$this->menu [$r] = array (
			'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuReport',
			'type' => 'left',
			'titre' => 'AgfMenuReportTime',
			'url' => '/agefodd/report/report_time.php',
			'langs' => 'agefodd@agefodd',
			'position' => 900 + $r,
			'enabled' => '$conf->agefodd->enabled && $user->rights->agefodd->report && $conf->global->AGF_USE_REAL_HOURS',
			'perms' => '$user->rights->agefodd->report',
			'target' => '',
			'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd',
				'type' => 'left',
				'titre' => 'AgfMenuDemoAdmin',
				'leftmenu' => 'AgfMenuDemoAdmin',
				'url' => '/agefodd/admin/admin_agefodd.php',
				'langs' => 'agefodd@agefodd',
				'position' => 1000 + $r,
				'enabled' => '$conf->agefodd->enabled',
				'perms' => '$user->rights->agefodd->admin',
				'target' => '',
				'user' => 0
		);

		$r ++;
		$this->menu[$r] = array(
				'fk_menu' => 'fk_mainmenu=agefodd,fk_leftmenu=AgfMenuDemoAdmin',
				'type' => 'left',
				'titre' => 'AgfMenuDemoAdminDetail',
				'url' => '/agefodd/admin/admin_agefodd.php',
				'langs' => 'agefodd@agefodd',
				'position' => 1000 + $r,
				'enabled' => '$conf->agefodd->enabled',
				'perms' => '$user->rights->agefodd->admin',
				'target' => '',
				'user' => 0
		);


		dol_include_once('/agefodd/scripts/update_rights.php');
		$TRights = getRightsToUpdate($this);
		$retfixrights = 0;
		if (!empty($TRights))
		{
			$this->warnings_activation = array('always'=>$langs->trans('AgfInitWarningNeedBackupBefore'));
		}

	}

	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 * It also creates data directories.
	 *
	 * @return int if OK, 0 if KO
	 */
	function init($options = '') {
		global $conf, $db, $langs;

		$sql = array();

		$result_table = $this->load_tables();

		if ($this->db->type == 'pgsql') {
			dol_syslog(get_class($this) . "::init this->db->type=" . $this->db->type, LOG_DEBUG);
			$res = @include_once (DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");
			dol_syslog(get_class($this) . "::init res=" . $res, LOG_DEBUG);
			foreach ( $conf->file->dol_document_root as $dirroot ) {
				$dir = $dirroot . '/agefodd/lib/sql/';

				$handle = @opendir($dir);
				// Dir may not exists
				if (is_resource($handle)) {
					$result_pgsql = run_sql($dir . 'agefodd_function.sql', 1, '', 1);
				}
			}
		} else {
			$result_pgsql = 1;
		}

		$return_init = $this->_init($sql);
		$result = $result_table && $result_pgsql && $return_init;

		//Remove trainer mod permission for user admin
		foreach ( $conf->file->dol_document_root as $dirroot ) {
			$dir = $dirroot . '/agefodd/sql/';

			$handle = @opendir($dir);
			// Dir may not exists
			if (is_resource($handle)) {
				$result_cleanright = run_sql($dir . 'clean_admin_right.sql', 1, '', 1);
			}
		}
		$result = $result && $result_cleanright;

		// Create new agenda event type
		include_once DOL_DOCUMENT_ROOT . '/comm/action/class/cactioncomm.class.php';
		$cactioncomm=new CActionComm($this->db);
		$resultAc=$cactioncomm->fetch('AC_AGF_NOTAV');

		if ($resultAc <= 0)
		{
			// Add new event type
			$cactioncomm=new CActionComm($this->db);
			$cactioncomm->code = 'AC_AGF_NOTAV';
			$cactioncomm->label = 'Indisponibilité formateur'; //'AgfAgendaOtherType_AC_AGF_NOTAV';
			$cactioncomm->color = '#ec9497';
			$cactioncomm->active = 1;


			$sql = "SELECT MAX(id) id FROM ".MAIN_DB_PREFIX."c_actioncomm ";
			$resql = $this->db->query($sql);
			$obj = $this->db->fetch_object($resql);


			// Incredible, CActionComm haven't any save methode ...
			$sql = "INSERT INTO  ".MAIN_DB_PREFIX."c_actioncomm  (id, code, type, libelle, module, active, todo, position, color)";
			$sql.= " VALUES (".( intval($obj->id) + 1 ).", '".$cactioncomm->code."', 'agefodd', '".$cactioncomm->label."', 'agefodd', '".$cactioncomm->active."', NULL, 60, '".$cactioncomm->color."');";

			if(!$this->db->query($sql)){
				setEventMessage('Error adding new action com type : '.$this->db->error(), 'errors');
				$result ++;
			}
		}

		if (! $result) {
			setEventMessage('Problem during Migration, please contact your administrator', 'errors');
		}
		return $result;
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted.
	 *
	 * @return int if OK, 0 if KO
	 */
	function remove($options = '') {
		global $langs, $db, $conf;

		$sql = array();

		dol_include_once('/agefodd/scripts/update_rights.php');
		$TRights = getRightsToUpdate($this);
		$retfixrights = 1; // default ok for return part

		if (!empty($TRights) && is_array($TRights))
		{
			$retfixrights = fixAgefoddRights($TRights, $this->numero);
			if($retfixrights == -2)
			{
				setEventMessage($langs->trans('AGFInitRightsErrors', 'errors'));
				// suppression des droits erronés
				$res = $db->query("DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE id > 1030000 AND   r.module = '".$this->rights_class."'  AND entity = ".$conf->entity);
			}
			elseif($retfixrights === 1){
				setEventMessage($langs->trans('AGFInitRightsSuccess'));
			}
		}



		return $this->_remove($sql) && ($retfixrights >= 0);
	}

	/**
	 * Create tables, keys and data required by module
	 * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * and create data commands must be stored in directory /mymodule/sql/
	 * This function is called by this->init.
	 *
	 * @return int if KO, >0 if OK
	 */
	function load_tables() {
		return $this->_load_tables_agefodd('/agefodd/sql/');
	}

	/**
	 * Create tables and keys required by module.
	 * Do not use version of Dolibarr because execute script only if version requiered it
	 * Files module.sql and module.key.sql with create table and create keys
	 * commands must be stored in directory reldir='/module/sql/'
	 * This function is called by this->init
	 *
	 * @param string $reldir where to scan files
	 * @return int <=0 if KO, >0 if OK
	 */
	function _load_tables_agefodd($reldir) {
		global $db, $conf;

		$error = 0;

		include_once (DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php");

		$last_version_install = $this->get_last_version_install($error);

		$sql_execution_order = array(
		    'table',  // first create tables
            'update', // we cannot update tables that do not exist yet
            'key',    // we cannot add indexes / foreign keys on tables that do not exist yet
            'data'    // we cannot add data into columns that do not exist yet
        );

		$sql_file_paths = array(
		    'table'  => array(),
            'update' => array(),
            'key'    => array(),
            'data'   => array()
        );

		$sql_regexp = array(
            // example match: "llx_agefodd_session.sql"
		    'table' => '/^llx_.+(?<!\.key)\.sql$/i',    // must start with llx_ and end with .sql NOT preceded by .key

            // example match: "update_3.0.0-3.0.1.sql"
            'update' => '/^update.+(?<!\.key)\.sql$/i', // must start with update and end with .sql NOT preceded by .key

            // example match: "llx_agefodd_session.key.sql"
            'key' => '/^llx_.+\.key\.sql$/i',           // must start with llx_ and end with .key.sql

            // example match: "data_2.0.sql"
            'data' => '/^data.+\.sql$/i',               // must start with data and end with .sql
        );


		// store the paths of sql files by role (table, update, key or data)
		foreach ( $conf->file->dol_document_root as $dirroot ) {
            $dir = $dirroot . $reldir;
            $handle = @opendir($dir);
            // Dir may not exist
            if (is_resource($handle)) {
                while (($file = readdir($handle)) !== false) {
                    foreach ($sql_execution_order as $sql_file_role) {
                        if (preg_match($sql_regexp[$sql_file_role], $file)) {
                            $sql_file_paths[$sql_file_role][] = $dir . $file;
                            break;
                        }
                    }
                }
                closedir($handle);
            }
        }

		// Special Case: 'update' (file naming pattern = update_x.x.x-y.y.y.sql) files need
        // to be sorted by version first.
        $sql_file_paths['update'] = $this->get_update_sql_files_sorted_by_version(
            $sql_file_paths['update'],
            $last_version_install
        );

		// run the sql files in the right order
		$update_refsession_done = false;
		foreach ($sql_execution_order as $sql_file_role) {
		    foreach ($sql_file_paths[$sql_file_role] as $sql_file_path) {
                switch($sql_file_role) {
                    // 'update' files are handled differently, they don't have the same structure
                    case 'update':
                        $sql_file_data = $sql_file_path;
                        $sql_file_path = $sql_file_data['file'];
                        dol_syslog(
                            get_class($this) .
                            "::_load_tables_agefodd run file from sorted array :" .
                            $sql_file_data['file'],
                            LOG_DEBUG
                        );
                        $result = run_sql($sql_file_path, 1, '', 1);
                        if (
                            !$update_refsession_done &&
                            version_compare($last_version_install, '3.2', '<=') &&
                            version_compare($sql_file_data['to'],  '3.3', '>=')
                        ) {
                            $this->update_refsession();
                            $update_refsession_done = true;
                        }
                        break;
                    default:
                        $result = run_sql($sql_file_path, 1, '', 1);
                        break;
                }
                if ($result <= 0) {
                    $error++;
                    break;
                }
            }
        }

		$return_code = ($error == 0);

		// FIXME (atm-florianm): shouldn’t we set the return code after the following DELETE to include possible errors?
        // DELETE AGF_LAST_VERION_INSTALL to update with the new one
        $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'const WHERE name=\'AGF_LAST_VERION_INSTALL\'';
        dol_syslog(get_class($this) . "::_load_tables_agefodd ", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) {
            $this->error = "Error " . $this->db->lasterror();
            dol_syslog(get_class($this) . "::_load_tables_agefodd " . $this->error, LOG_ERR);
            $error ++;
        }

        return $return_code;
	}

	function update_refsession()
	{
		global $db, $user;
		dol_include_once('/user/class/user.class.php');
		dol_include_once('/agefodd/class/agsession.class.php');
		dol_include_once('/agefodd/core/modules/agefodd/session/mod_agefoddsession_simple.php');

		$db->begin();
		$sql = "SELECT rowid,datec FROM ".MAIN_DB_PREFIX."agefodd_session WHERE ref = '' ORDER BY rowid";

		$resql = $db->query($sql);
		while ($obj = $db->fetch_object($resql))
		{
			$modSession = new mod_agefoddsession_simple();
			$ref = $modSession->getNextValue('', '', $obj->datec);

			if(! empty($ref)) {
				$update_sql = 'UPDATE '.MAIN_DB_PREFIX.'agefodd_session';
				$update_sql.= " SET ref='".$ref."'";
				$update_sql.= ' WHERE rowid='.$obj->rowid;

				$resUpdate = $db->query($update_sql);
				if(! $resUpdate) {
					dol_print_error($db);
					exit;
				}
			}
		}

		$db->commit();
	}

    /**
     * @param $error
     * @throws Exception
     *
     * @return string  Last installed version number from the database or -1 in case of db error
     *                 or multiple database entries for the version number.
     */
    private function get_last_version_install(&$error)
    {
        // ⚠ keep the typo in the constant name (AGF_LAST_VERION_INSTALL), otherwise the script will fail.
        $sql = 'SELECT value FROM ' . MAIN_DB_PREFIX . 'const WHERE name=\'AGF_LAST_VERION_INSTALL\'';
        $resql = $this->db->query($sql);

        if ($resql) {
            if ($this->db->num_rows($resql) == 1) {
                $obj = $this->db->fetch_object($resql);
                dol_syslog(get_class($this) . "::_load_tables_agefodd last_version_install:" . $last_version_install, LOG_DEBUG);
                return $obj->value;
            } else {
                // todo
                dol_syslog(get_class($this) . "::_load_tables_agefodd SQL does not return exactly 1 row: " . $sql, LOG_ERR);
                return -1;
            }
        } else {
            $this->error = 'Error ' . $this->db->lasterror();
            dol_syslog(get_class($this) . '::_load_tables_agefodd ' . $this->error, LOG_ERR);
            $error++;
            return -1;
        }
    }

    /**
     * @param $list_of_sql_update_files  array  list of 'update_x.x.x-y.y.y.sql' files
     * @param $last_version_install      string version number of the last installed version
     * @throws Exception
     *
     * @return array  list of update_x.x.x-y.y.y.sql files sorted in the order of
     *                versions up to current version
     */
    private function get_update_sql_files_sorted_by_version($list_of_sql_update_files, $last_version_install) {
        $sorted_update_sql_files = array();

        $regexp_extract_version_num = '/^.*\/update_([^-]+)-(.+)\.sql$/i';

        $sql_update_files_by_version = array();
        foreach($list_of_sql_update_files as $sql_update_file) {
            dol_syslog(
                get_class($this) . "::_load_tables_agefodd analyse file:" . $sql_update_file,
                LOG_DEBUG
            );
            $match = array();
            if (!preg_match($regexp_extract_version_num, $sql_update_file, $match)) {
                setEventMessages(
                    'SQL file name ' . $sql_update_file . 'does not match pattern: "update_x.x.x-y.y.y.sql"' .
                    ' and will be skipped; this might cause errors later.',
                    array(),
                    'error'
                );
                continue;
            }
            $from_version = $match[1];
            $to_version   = $match[2];

            if (version_compare($to_version, $last_version_install, '>')) {
                // only include updates to versions that are newer than last_version_install
                $sql_update_files_by_version[$from_version] = array(
                    'from'     => $from_version,
                    'to'       => $to_version,
                    'file' => $sql_update_file
                );
            }
        }
        uksort(
            $sql_update_files_by_version,
            'version_compare'
        );
        return $sql_update_files_by_version;
    }

	function change_order_supplier_type()
	{
		global $db, $user;
		dol_include_once('/user/class/user.class.php');
		dol_include_once('/agefodd/class/agefodd_session_element.class.php');
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."agefodd_session_element WHERE element_type = 'order_supplier' ORDER BY rowid";
		$resql = $db->query($sql);
		if(!empty($resql))
		{
			while ($obj = $db->fetch_object($resql))
			{
				$ags = new Agefodd_session_element($db);
				$ags->fetch($obj->rowid);
				$ags->element_type='order_supplier_trainer';
				$ags->update($user);
			}
		}
	}

}
