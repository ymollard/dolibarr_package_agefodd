<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
* Copyright (C) 2012 	   Florian Henry        <florian.henry@open-concept.pro>
* Copyright (C) 2012		JF FERRY	<jfefe@aternatik.fr>
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
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 *  \file       agefodd/core/triggers/interface_90_agefodd.class.php
 *  \ingroup    core
 *  \brief      Fichier qui permet de lancer un trigger avec la mise a jours d'une action
*/


/**
 *  Class of triggers Agefodd
*/
class InterfaceAgefodd
{
	var $db;

	/**
	 *   Constructor
	 *
	 *   @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i','',get_class($this));
		$this->family = "agefodd";
		$this->description = "When action (agenda event)link to session is changed to session calendar is changed to";
		$this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
		$this->picto = 'technic';
	}


	/**
	 *   Return name of trigger file
	 *
	 *   @return     string      Name of trigger file
	 */
	function getName()
	{
		return $this->name;
	}

	/**
	 *   Return description of trigger file
	 *
	 *   @return     string      Description of trigger file
	 */
	function getDesc()
	{
		return $this->description;
	}

	/**
	 *   Return version of trigger file
	 *
	 *   @return     string      Version of trigger file
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("Development");
		elseif ($this->version == 'experimental') return $langs->trans("Experimental");
		elseif ($this->version == 'dolibarr') return DOL_VERSION;
		elseif ($this->version) return $this->version;
		else return $langs->trans("Unknown");
	}

	/**
	 *      Function called when a Dolibarrr business event is done.
	 *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
	 *
	 *      @param	string		$action		Event action code
	 *      @param  Object		$object     Object
	 *      @param  User		$user       Object user
	 *      @param  Translate	$langs      Object langs
	 *      @param  conf		$conf       Object conf
	 *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
    	dol_include_once('/comm/action/class/actioncomm.class.php');
    	dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action

        // Users
        if ($action == 'ACTION_MODIFY') {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".$user->id.". id=".$object->id);

            if ($object->type_code=='AC_AGF_SESS') {

	            $action = new ActionComm($this->db);
	            $result = $action->fetch($object->id);

	            if ($result != -1) {

	            	if ($object->id == $action->id) {

	            		$agf_cal = new Agefodd_sesscalendar($this->db);
	            		$result = $agf_cal->fetch_by_action($action->id);
	            		if ($result != -1) {

	            			$dt_array =  getdate($action->datep);
	            			$agf_cal->date_session = dol_mktime(0,0,0,$dt_array['mon'],$dt_array['mday'],$dt_array['year']);
							$agf_cal->heured = $action->datep;
							$agf_cal->heuref = $action->datef;

							$result = $agf_cal->update($user,1);

							if ($result == -1) {
								dol_syslog(get_class($this)."::run_trigger ".$agf_cal->error, LOG_ERR);
								return -1;
							}
						}
					}
				}
			}
		}
		// Envoi fiche pédago par mail
		elseif ($action == 'FICHEPEDAGO_SENTBYMAIL') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".$user->id.". id=".$object->id);


			if ($object->actiontypecode=='AC_AGF_PEDAG') {

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$langs->load("agefodd@agefodd");
				$langs->load("agenda");

				if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("AgfFichePedaSentByEMail",$object->ref);
				if (empty($object->actionmsg))
				{
					$object->actionmsg=$langs->transnoentities("AgfFichePedaSentByEMail",$object->ref);
					$object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
				}

				$ok=1;
			}
		}
		// Envoi fiche présence par mail
		elseif ($action == 'FICHEPRESENCE_SENTBYMAIL') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".$user->id.". id=".$object->id);


			if ($object->actiontypecode == 'AC_AGF_PRES') {

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$langs->load("agefodd@agefodd");
				$langs->load("agenda");

				if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("AgfFichePresenceSentByEMail",$object->ref);
				if (empty($object->actionmsg))
				{
					$object->actionmsg=$langs->transnoentities("AgfFichePresenceSentByEMail",$object->ref);
					$object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
				}

				$ok=1;
			}
		}
		// Envoi convention par mail
		elseif ($action == 'CONVENTION_SENTBYMAIL') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".$user->id.". id=".$object->id);


			if ($object->actiontypecode == 'AC_AGF_CONVE') {

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$langs->load("agefodd@agefodd");
				$langs->load("agenda");

				if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("AgfConventionSentByEMail",$object->ref);
				if (empty($object->actionmsg))
				{
					$object->actionmsg=$langs->transnoentities("AgfConventionSentByEMail",$object->ref);
					$object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
				}

				$ok=1;
			}
		}
		// Envoi attestation par mail
		elseif ($action == 'ATTESTATION_SENTBYMAIL') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".$user->id.". id=".$object->id);


			if ($object->actiontypecode == 'AC_AGF_ATTES') {

				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$langs->load("agefodd@agefodd");
				$langs->load("agenda");

				if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("AgfConventionSentByEMail",$object->ref);
				if (empty($object->actionmsg))
				{
					$object->actionmsg=$langs->transnoentities("AgfConventionSentByEMail",$object->ref);
					$object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
				}

				$ok=1;
			}
		}


		// Add entry in event table
		if ($ok)
		{
			$now=dol_now();

			require_once(DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php');
			require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
			$contactforaction=new Contact($this->db);
			$societeforaction=new Societe($this->db);
			if ($object->sendtoid > 0) $contactforaction->fetch($object->sendtoid);
			if ($object->socid > 0)    $societeforaction->fetch($object->socid);

			// Insertion action
			require_once(DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php');


			$actioncomm = new ActionComm($this->db);
			$actioncomm->type_code   = $object->actiontypecode;
			$actioncomm->label       = $object->actionmsg2;
			$actioncomm->note        = $object->actionmsg;
			$actioncomm->datep       = $now;
			$actioncomm->datef       = $now;
			$actioncomm->durationp   = 0;
			$actioncomm->punctual    = 1;
			$actioncomm->percentage  = -1;   // Not applicable
			$actioncomm->contact     = $contactforaction;
			$actioncomm->societe     = $societeforaction;
			$actioncomm->author      = $user;   // User saving action
			//$actioncomm->usertodo  = $user;	// User affected to action
			$actioncomm->userdone    = $user;	// User doing action
			$actioncomm->fk_element  = $object->id;
			$actioncomm->elementtype = $object->element;
			$ret=$actioncomm->add($user);       // User qui saisit l'action
			if ($ret > 0)
			{
				return 1;
			}
			else
			{
				$error ="Failed to insert : ".$actioncomm->error." ";
				$this->error=$error;

				dol_syslog("interface_modAgefodd_Agefodd.class.php: ".$this->error, LOG_ERR);
				return -1;
			}
		}
		return 0;
	}

}
?>