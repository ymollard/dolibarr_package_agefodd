<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012       Florian Henry   <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

/**     \defgroup   	agefodd     Module AGeFoDD (Assistant de GEstion de la FOrmation Dans Dolibarr)
 *      \brief      	agefodd module descriptor.
 */

/**
 *      \file       	/core/modules/modAgefodd.class.php
 *      \ingroup    	agefodd
 *      \brief      	Description and activation file for module agefodd
 *		\version		$Id$
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 * 		\class      modAgefodd
 *      \brief      Description and activation class for module agefodd
 */
class modAgefodd extends DolibarrModules
{
	/**
	 *   \brief      Constructor. Define names, constants, directories, boxes, permissions
	 *   \param      DB      Database handler
	 */
	function __construct($DB)
	{
		$this->db = $DB;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 103000;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'agefodd';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		//It is used to group modules in module setup page
		$this->family = "hr";
		// Module label, used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Trainning Management Assistant Module";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '2.0.14';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/images directory, use this->picto=DOL_URL_ROOT.'/module/images/file.png'
		$this->picto='agefodd@agefodd';

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array("/agefodd/documents");
		$r=0;

		// Relative path to module style sheet if exists. Example: '/mymodule/mycss.css'.
		$this->style_sheet = '/agefodd/css/agefodd.css';

		// Config pages. Put here list of php page names stored in admin directory used to setup module.
		$this->config_page_url = array("agefodd.php@agefodd");
		
		//define triggers
		$this->module_parts = array('triggers' => 1);

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(4,3);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,2);	// Minimum version of Dolibarr required by module
		$this->langfiles = array('agefodd@agefodd');

		// Constants
		$this->const = array();			// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')
		$r=0;

		$r++;
		$this->const[$r][0] = "AGF_USE_STAGIAIRE_TYPE";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '0';
		$this->const[$r][3] = 'Use trainee type';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r++;
		$this->const[$r][0] = "AGF_DEFAULT_STAGIAIRE_TYPE";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '2';
		$this->const[$r][3] = 'Type of  trainee funding';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r++;
		$this->const[$r][0] = "AGF_UNIVERSAL_MASK";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Mask of training number ref';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r++;
		$this->const[$r][0] = "AGF_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = 'mod_agefodd_simple';
		$this->const[$r][3] = 'Use simple mask for training ref';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r++;
		$this->const[$r][0] = "AGF_ORGANISME_PREF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Prefecture d\'enregistrement';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r++;
		$this->const[$r][0] = "AGF_ORGANISME_NUM";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Numerot d\'enregistrement a la prefecture';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r++;
		$this->const[$r][0] = "AGF_ORGANISME_REPRESENTANT";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Representant de la societé de formation';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r++;
		$this->const[$r][0] = "AGF_TRAINING_USE_SEARCH_TO_SELECT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Search Training with combobox';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r++;
		$this->const[$r][0] = "AGF_TRAINER_USE_SEARCH_TO_SELECT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Search Trainer with combobox';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r++;
		$this->const[$r][0] = "AGF_TRAINEE_USE_SEARCH_TO_SELECT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Search Trainee with combobox';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r++;
		$this->const[$r][0] = "AGF_SITE_USE_SEARCH_TO_SELECT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Search site with combobox';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r++;
		$this->const[$r][0] = "AGF_STAGTYPE_USE_SEARCH_TO_SELECT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Search stagiaire type with combobox';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r++;
		$this->const[$r][0] = "AGF_CONTACT_USE_SEARCH_TO_SELECT";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Search contact with combobox';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;

		$r++;
		$this->const[$r][0] = "AGF_CONTACT_DOL_SESSION";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Use dolibarr or agefodd contact for session';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		
		$r++;
		$this->const[$r][0] = "AGF_LAST_VERION_INSTALL";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = $this->version;
		$this->const[$r][3] = 'Last version installed to know change table to execute';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;
		
		$r++;
		$this->const[$r][0] = "AGF_DOL_AGENDA";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = '';
		$this->const[$r][3] = 'Create Event in Dolibarr Agenda';
		$this->const[$r][4] = 0;
		$this->const[$r][5] = 0;


		// Array to add new pages in new tabs
		//$this->tabs = array('entity:Title:@mymodule:/mymodule/mynewtab.php?id=__ID__');
		// where entity can be
		// 'thirdparty'       to add a tab in third party view
		// 'intervention'     to add a tab in intervention view
		// 'supplier_order'   to add a tab in supplier order view
		// 'supplier_invoice' to add a tab in supplier invoice view
		// 'invoice'          to add a tab in customer invoice view
		// 'order'            to add a tab in customer order view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'member'           to add a tab in fundation member view
		// 'contract'         to add a tab in contract view


		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
		// Example:
		//$this->boxes[$r][1] = "myboxa.php";
		//$r++;
		//$this->boxes[$r][1] = "myboxb.php";
		//$r++;


		// Permissions
		$this->rights = array();
		$r=0;

		$this->rights[$r][0] = 103001;
		$this->rights[$r][1] = 'Lecture';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'lire';
		$r++;

		$this->rights[$r][0] = 103002;
		$this->rights[$r][1] = 'Modification';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'modifier';
		$r++;

		$this->rights[$r][0] = 103003;
		$this->rights[$r][1] = 'Ajout';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';
		$r++;


		$this->rights[$r][0] = 103004;
		$this->rights[$r][1] = 'Suppression';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';
		$r++;

		// Main menu entries
		$this->menus = array();
		$r=0;

		$this->menu[$r]=array(	'fk_menu'=>0,
								'type'=>'top',
								'titre'=>'Gestion Formation',
								'mainmenu'=>'agefodd',
								'leftmenu'=>'0',
								'url'=>'/agefodd/index.php',
								'langs'=>'agefodd@agefodd',
								'position'=>100,
								'enabled'=>'1',
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0
		);
		$r++;
		// 1
		$this->menu[$r]=array(	'fk_menu'=>'r=0',
								'type'=>'left',
								'titre'=>'AgfMenuCat',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/training/list.php',
								'langs'=>'agefodd@agefodd',
								'position'=>101,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=1',
								'type'=>'left',
								'titre'=>'AgfMenuCatListActivees',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/training/list.php',
								'langs'=>'agefodd@agefodd',
								'position'=>102,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=1',
								'type'=>'left',
								'titre'=>'AgfMenuCatListArchivees',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/training/list.php?arch=1',
								'langs'=>'agefodd@agefodd',
								'position'=>103,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=1',
								'type'=>'left',
								'titre'=>'AgfMenuCatNew',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/training/card.php?action=create',
								'langs'=>'agefodd@agefodd',
								'position'=>104,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->creer',
								'target'=>'',
								'user'=>0);
		$r++;
		// 2
		$this->menu[$r]=array(	'fk_menu'=>'r=0',
								'type'=>'left',
								'titre'=>'AgfMenuSess',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/session/list.php',
								'langs'=>'agefodd@agefodd',
								'position'=>201,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=5',
								'type'=>'left',
								'titre'=>'AgfMenuSessActList',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/session/list.php',
								'langs'=>'agefodd@agefodd',
								'position'=>202,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=5',
								'type'=>'left',
								'titre'=>'AgfMenuSessArchList',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/session/list.php?arch=1',
								'langs'=>'agefodd@agefodd',
								'position'=>203,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=5',
								'type'=>'left',
								'titre'=>'AgfMenuSessNew',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/session/card.php?action=create',
								'langs'=>'agefodd@agefodd',
								'position'=>204,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->creer',
								'target'=>'',
								'user'=>0);
		$r++;
		// 3
		$this->menu[$r]=array(	'fk_menu'=>'r=0',
								'type'=>'left',
								'titre'=>'AgfMenuActStagiaire',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/trainee/list.php',
								'langs'=>'agefodd@agefodd',
								'position'=>301,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=9',
								'type'=>'left',
								'titre'=>'AgfMenuActStagiaireList',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/trainee/list.php',
								'langs'=>'agefodd@agefodd',
								'position'=>302,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=9',
								'type'=>'left',
								'titre'=>'AgfMenuActStagiaireNew',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/trainee/card.php?action=create',
								'langs'=>'agefodd@agefodd',
								'position'=>303,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->creer',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=9',
								'type'=>'left',
								'titre'=>'AgfMenuActStagiaireNewFromContact',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/trainee/card.php?action=nfcontact',
								'langs'=>'agefodd@agefodd',
								'position'=>304,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->creer',
								'target'=>'',
								'user'=>0);
		$r++;
		// 4
		$this->menu[$r]=array(	'fk_menu'=>'r=0',
								'type'=>'left',
								'titre'=>'AgfMenuLogistique',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/trainee/list.php',
								'langs'=>'agefodd@agefodd',
								'position'=>401,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=13',
								'type'=>'left',
								'titre'=>'AgfMenuSite',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/site/list.php',
								'langs'=>'agefodd@agefodd',
								'position'=>402,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=13',
								'type'=>'left',
								'titre'=>'AgfMenuFormateur',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/trainer/list.php',
								'langs'=>'agefodd@agefodd',
								'position'=>403,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=13',
								'type'=>'left',
								'titre'=>'AgfMenuContact',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/contact/list.php',
								'langs'=>'agefodd@agefodd',
								'position'=>404,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0);
		$r++;
		// 5
		//TODO : Create BPF
		/*$this->menu[$r]=array(	'fk_menu'=>'r=0',
								'type'=>'left',
								'titre'=>'AgfMenuSAdm',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/not_implemented.php',
								'langs'=>'agefodd@agefodd',
								'position'=>501,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0);
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'r=17',
								'type'=>'left',
								'titre'=>'AgfMenuSAdmBilanDRTEFP',
								'mainmenu'=>'agefodd',
								'url'=>'/agefodd/not_implemented.php',
								'langs'=>'agefodd@agefodd',
								'position'=>502,
								'enabled'=>1,
								'perms'=>'$user->rights->agefodd->lire',
								'target'=>'',
								'user'=>0);*/

	}

	/**
	 *	Function called when module is enabled.
	 *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *	It also creates data directories.
	 *
	 *  @return		int	1 if OK, 0 if KO
	 */
	function init()
	{
		$sql = array();

		$result=$this->load_tables();

		return $this->_init($sql);
	}

	/**
	 *	Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted.
	 *
	 *  @return		int	1 if OK, 0 if KO
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);
	}


	/**
	 *	Create tables, keys and data required by module
	 * 	Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * 	and create data commands must be stored in directory /mymodule/sql/
	 *	This function is called by this->init.
	 *
	 * 	@return		int	<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables_agefodd('/agefodd/sql/');
	}
	
	/**
	 *  Create tables and keys required by module.
	 *  Do not use version of Dolibarr because execute script only if version requiered it
	 *  Files module.sql and module.key.sql with create table and create keys
	 *  commands must be stored in directory reldir='/module/sql/'
	 *  This function is called by this->init
	 *
	 *  @param	string	$reldir		Relative directory where to scan files
	 *  @return	int     			<=0 if KO, >0 if OK
	 */
	function _load_tables_agefodd($reldir)
	{
		global $db,$conf;
	
		$error=0;
	
		include_once(DOL_DOCUMENT_ROOT ."/core/lib/admin.lib.php");
	
		$ok = 1;
		foreach($conf->file->dol_document_root as $dirroot)
		{
			if ($ok)
			{
				$dir = $dirroot.$reldir;
				$ok = 0;
	
				// Run llx_mytable.sql files
				$handle=@opendir($dir);         // Dir may not exists
				if (is_resource($handle))
				{
					while (($file = readdir($handle))!==false)
					{
						if (preg_match('/\.sql$/i',$file) && ! preg_match('/\.key\.sql$/i',$file) && substr($file,0,4) == 'llx_' && substr($file,0,4) != 'data')
						{
							$result=run_sql($dir.$file,1,'',1);
							if ($result <= 0) $error++;
						}
					}
					closedir($handle);
				}
	
				// Run llx_mytable.key.sql files (Must be done after llx_mytable.sql)
				$handle=@opendir($dir);         // Dir may not exist
				if (is_resource($handle))
				{
					while (($file = readdir($handle))!==false)
					{
						if (preg_match('/\.key\.sql$/i',$file) && substr($file,0,4) == 'llx_' && substr($file,0,4) != 'data')
						{
							$result=run_sql($dir.$file,1,'',1);
							if ($result <= 0) $error++;
						}
					}
					closedir($handle);
				}

				// Run update_xxx.sql files
				$handle=@opendir($dir);         // Dir may not exist
				if (is_resource($handle))
				{
					while (($file = readdir($handle))!==false)
					{	
						$dorun =true;
						if (preg_match('/\.sql$/i',$file) && ! preg_match('/\.key\.sql$/i',$file) && substr($file,0,6) == 'update')
						{
							//Special test to know what kind of update script to run
							if ($file=='update_1.0.0-2.0.sql')	{
								$sql="SHOW COLUMNS FROM llx_agefodd_session_calendrier FROM dolibarrDev where Field = 'heured' and Type='datetime'";
								$resql=$this->db->query($sql);
								if ($resql) {
									if ($this->db->num_rows($resql)==0) {
										$dorun=false;
									}
								}
								else
								{
									$this->error="Error ".$this->db->lasterror();
									dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
									$error ++;
								}
							} 
							if ($dorun) {
							$result=run_sql($dir.$file,1,'',1);
							if ($result <= 0) $error++;
							}
						}
					}
					closedir($handle);
				}

				if ($error == 0)
				{
					$ok = 1;
				}
			}
		}

		return $ok;
	}
}

?>
