<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
* Copyright (C) 2012		Florian Henry	<florian.henry@open-concept.pro>
* Copyright (C) 2012		JF FERRY	<jfefe@aternatik.fr>
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
 *  \file       	/agefodd/session/card.php
*  \brief      	Page fiche session de formation
*  \version		$Id$
*/

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory
if (! $res) die("Include of main fails");

dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
dol_include_once('/agefodd/class/agefodd_session_admlevel.class.php');
dol_include_once('/agefodd/class/html.formagefodd.class.php');
dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
dol_include_once('/agefodd/class/agefodd_calendrier.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
dol_include_once('/contact/class/contact.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/core/lib/date.lib.php');

// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$mesg = '';

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('id','int');
$arch=GETPOST('arch','int');

/*
 * Actions delete session
*/

if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	$agf = new Agsession($db);
	$result = $agf->remove($id);

	if ($result > 0)
	{
		Header ( "Location: list.php");
		exit;
	}
	else
	{
		dol_syslog("agefodd:session:card error=".$error, LOG_ERR);
		$mesg = '<div class="error">'.$langs->trans("AgfDeleteErr").':'.$agf->error.'</div>';
	}
}


/*
 * Actions delete period
*/

if ($action == 'confirm_delete_period' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	$modperiod=GETPOST('modperiod','int');

	$agf = new Agefodd_sesscalendar($db);
	$result = $agf->remove($modperiod);

	if ($result > 0)
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
		exit;
	}
	else
	{
		dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
		$mesg = '<div class="error">'.$agf->error.'</div>';
	}
}

/*
 * Actions archive/active
*/

if ($action == 'arch_confirm_delete' && $user->rights->agefodd->creer)
{
	if ($confirm == "yes")
	{
		$agf = new Agsession($db);

		$result = $agf->fetch($id);
		$agf->archive = $_GET["arch"];
		$result = $agf->updateArchive($user);

		if ($result > 0)
		{
			// Si la mise a jour s'est bien passée, on effectue le nettoyage des templates pdf
			foreach (glob($conf->agefodd->dir_output."/*_".$id."_*.pdf") as $filename) {
				//echo "$filename effacé <br>";
				if(is_file($filename)) unlink("$filename");
			}

			Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}

	}
	else
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
}


/*
 * Action update (fiche session)
*/
if ($action == 'update' && $user->rights->agefodd->creer && ! $_POST["stag_update_x"] && ! $_POST["period_update_x"])
{
	if (! $_POST["cancel"])
	{
		$error=0;

		$agf = new Agsession($db);

		$fk_session_place = GETPOST('place','int');
		if (($fk_session_place==-1) || (empty($fk_session_place)))
		{
			$error++;
			$mesg = '<div class="error">'.$langs->trans('AgfPlaceMandatory').'</div>';
		}

		$result = $agf->fetch($id);

		$agf->fk_formation_catalogue = GETPOST('formation','int');

		$agf->dated = dol_mktime(0,0,0,GETPOST('dadmonth','int'),GETPOST('dadday','int'),GETPOST('dadyear','int'));
		$agf->datef = dol_mktime(0,0,0,GETPOST('dafmonth','int'),GETPOST('dafday','int'),GETPOST('dafyear','int'));
		$agf->fk_session_place = $fk_session_place;
		$agf->type_session = GETPOST('type_session','int');
		$agf->commercialid = GETPOST('commercial','int');
		$agf->contactid = GETPOST('contact','int');
		if ($conf->global->AGF_CONTACT_DOL_SESSION)	{
			$agf->sourcecontactid = $agf->contactid;
		}		
		$agf->notes = GETPOST('notes','alpha');

		$agf->cost_trainer = GETPOST('costtrainer','alpha');
		$agf->cost_site = GETPOST('costsite','alpha');
		$agf->sell_price = GETPOST('sellprice','alpha');

		$agf->date_res_site = dol_mktime(0,0,0,GETPOST('res_sitemonth','int'),GETPOST('res_siteday','int'),GETPOST('res_siteyear','int'));
		$agf->date_res_trainer = dol_mktime(0,0,0,GETPOST('res_trainmonth','int'),GETPOST('res_trainday','int'),GETPOST('res_trainyear','int'));

		if ($agf->date_res_site=='') {
			$isdateressite=0;
		} else {$isdateressite=GETPOST('isdateressite','alpha');
		}
		if ($agf->date_res_trainer=='')	{
			$isdaterestrainer=0;
		} else {$isdaterestrainer=GETPOST('isdaterestrainer','alpha');
		}

		if ($isdateressite==1 && $agf->date_res_site!='') {
			$agf->is_date_res_site = 1;
		}
		else {	$agf->is_date_res_site = 0;	$agf->date_res_site='';
		}

		if ($isdaterestrainer==1 && $agf->date_res_trainer!='') {
			$agf->is_date_res_trainer = 1;
		}
		else {	$agf->is_date_res_trainer = 0; $agf->date_res_trainer='';
		}

		$fk_soc				= GETPOST('fk_soc','int');
		$color				= GETPOST('color','alpha');
		$nb_place			= GETPOST('nb_place','int');
		$nb_stagiaire		= GETPOST('nb_stagiaire','int');
		$force_nb_stagiaire	= GETPOST('force_nb_stagiaire','int');

		if ($force_nb_stagiaire==1 && $agf->force_nb_stagiaire!='') {
			$agf->force_nb_stagiaire = 1;
		}
		else {
			$agf->force_nb_stagiaire = 0;
		}

		$cost_trip = GETPOST('costtrip','alpha');

		if(!empty($fk_soc)) 			$agf->fk_soc =  $fk_soc;
		if(!empty($color))				$agf->color =  $color;
		if(!empty($nb_place)) 			$agf->nb_place = $nb_place;
		if(!empty($nb_stagiaire))		$agf->nb_stagiaire = $nb_stagiaire;
		if(!empty($force_nb_stagiaire))	$agf->force_nb_stagiaire = $force_nb_stagiaire;
		if(!empty($cost_trip)) 			$agf->cost_trip = $cost_trip;

		if ($error==0)
		{
			$result = $agf->update($user);
			if ($result > 0)
			{
				if ($_POST['saveandclose']!='') {
					Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
				}
				else
				{
					Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
				}
				exit;
			}
			else
			{
				dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
				$mesg = '<div class="error">'.$agf->error.'</div>';
			}
		}
		else
		{
			if ($_POST['saveandclose']!='') {
				$action='';
			}
			else
			{
				$action='edit';
			}
		}
	}
	else
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
}


/*
 * Action update
* - changement ou ajout periode dans fiche session
* - changement ou ajout formateur dans fiche session
*/
if ($action == 'edit' && $user->rights->agefodd->creer)
{
	if($_POST["period_update_x"])
	{

		$modperiod=GETPOST('modperiod','int');
		$date_session = dol_mktime(0,0,0,GETPOST('datemonth','int'),GETPOST('dateday','int'),GETPOST('dateyear','int'));

		$heure_tmp_arr = array();

		$heured_tmp = GETPOST('dated','alpha');
		if (!empty($heured_tmp)){
			$heure_tmp_arr = explode(':',$heured_tmp);
			$heured = dol_mktime($heure_tmp_arr[0],$heure_tmp_arr[1],0,GETPOST('datemonth','int'),GETPOST('dateday','int'),GETPOST('dateyear','int'));
		}

		$heuref_tmp = GETPOST('datef','alpha');
		if (!empty($heuref_tmp)){
			$heure_tmp_arr = explode(':',$heuref_tmp);
			$heuref = dol_mktime($heure_tmp_arr[0],$heure_tmp_arr[1],0,GETPOST('datemonth','int'),GETPOST('dateday','int'),GETPOST('dateyear','int'));
		}

		$agf = new Agefodd_sesscalendar($db);
		$result = $agf->fetch($modperiod);

		if(!empty($modperiod)) 			$agf->id = $modperiod;
		if(!empty($date_session)) 		$agf->date_session = $date_session;
		if(!empty($heured)) 			$agf->heured = $heured;
		if(!empty($heuref)) 			$agf->heuref =  $heuref;


		$result = $agf->update($user);

		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	}

	if($_POST["period_add_x"])
	{
		$agf = new Agefodd_sesscalendar($db);

		$agf->sessid = GETPOST('sessid','int');
		$agf->date_session = dol_mktime(0,0,0,GETPOST('datemonth','int'),GETPOST('dateday','int'),GETPOST('dateyear','int'));

		//From template
		$idtemplate=GETPOST('fromtemplate','int');
		if (($idtemplate!=-1) && (!empty($idtemplate))) {
			$tmpl_calendar = new Agefoddcalendrier($db);
			$result=$tmpl_calendar->fetch($idtemplate);
			$tmpldate = dol_mktime(0,0,0,GETPOST('datetmplmonth','int'),GETPOST('datetmplday','int'),GETPOST('datetmplyear','int'));
			if ($tmpl_calendar->day_session!=1) {
				$tmpldate = dol_time_plus_duree($tmpldate, (($tmpl_calendar->day_session)-1), 'd');
			}
			
			$agf->date_session = $tmpldate;
			
			$heure_tmp_arr = explode(':',$tmpl_calendar->heured);
			$agf->heured = dol_mktime($heure_tmp_arr[0],$heure_tmp_arr[1],0,dol_print_date($agf->date_session, "%m"),dol_print_date($agf->date_session, "%d"),dol_print_date($agf->date_session, "%Y"));
			
			$heure_tmp_arr = explode(':',$tmpl_calendar->heuref);
			$agf->heuref = dol_mktime($heure_tmp_arr[0],$heure_tmp_arr[1],0,dol_print_date($agf->date_session, "%m"),dol_print_date($agf->date_session, "%d"),dol_print_date($agf->date_session, "%Y"));
		
		}else {
			//From calendar selection
			$heure_tmp_arr = array();
	
			$heured_tmp = GETPOST('dated','alpha');
			if (!empty($heured_tmp)){
				$heure_tmp_arr = explode(':',$heured_tmp);
				$agf->heured = dol_mktime($heure_tmp_arr[0],$heure_tmp_arr[1],0,GETPOST('datemonth','int'),GETPOST('dateday','int'),GETPOST('dateyear','int'));
			}
	
			$heuref_tmp = GETPOST('datef','alpha');
			if (!empty($heuref_tmp)){
				$heure_tmp_arr = explode(':',$heuref_tmp);
				$agf->heuref = dol_mktime($heure_tmp_arr[0],$heure_tmp_arr[1],0,GETPOST('datemonth','int'),GETPOST('dateday','int'),GETPOST('dateyear','int'));
			}
		}

		$result = $agf->create($user);

		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	}

	if($_POST["form_update_x"])
	{
		$agf = new Agefodd_session_formateur($db);

		$agf->opsid = GETPOST('opsid','int');
		$agf->formid = GETPOST('formid','int');

		$result = $agf->update($user);

		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	}

	if($_POST["form_add_x"])
	{
		$agf = new Agefodd_session_formateur($db);

		$agf->sessid = GETPOST('sessid','int');
		$agf->formid = GETPOST('formid','int');
		$result = $agf->create($user);

		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	}

}

/*
 * Action create (nouvelle session de formation)
*/

if ($action == 'add_confirm' && $user->rights->agefodd->creer)
{
	$error=0;
	if (! $_POST["cancel"])
	{
		$agf = new Agsession($db);

		$fk_session_place = GETPOST('place','int');
		if (($fk_session_place==-1) || (empty($fk_session_place)))
		{
			$error++;
			$mesg = '<div class="error">'.$langs->trans('AgfPlaceMandatory').'</div>';
		}

		$agf->fk_formation_catalogue = GETPOST('formation','int');
		$agf->fk_session_place = $fk_session_place;
		$agf->nb_place = GETPOST('nb_place','int');
		$agf->type_session = GETPOST('type_session','int');
		$agf->nb_place = GETPOST('nb_place','int');
		$agf->type_session = GETPOST('type_session','int');

		$agf->fk_soc = GETPOST('fk_soc','int');
		$agf->dated = dol_mktime(0,0,0,GETPOST('dadmonth','int'),GETPOST('dadday','int'),GETPOST('dadyear','int'));
		$agf->datef = dol_mktime(0,0,0,GETPOST('dafmonth','int'),GETPOST('dafday','int'),GETPOST('dafyear','int'));
		$agf->notes = GETPOST('notes','alpha');
		$agf->commercialid = GETPOST('commercial','int');
		$agf->contactid = GETPOST('contact','int');

		if ($error==0)
		{
			$result = $agf->create($user);

			if ($result > 0)
			{
				// Si la création de la session s'est bien passée,
				// on crée automatiquement toutes les tâches administratives associées...
				$result = $agf->createAdmLevelForSession($user);
				if ($result>0) {
					dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
					$mesg .= $agf->error;
					$error++;
				}
			}
			else
			{
				dol_syslog("agefodd:session:card error=".$agf->error, LOG_ERR);
				$mesg .= $agf->error;
				$error++;
			}
		}
		if ($error==0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$agf->id);
			exit;
		}

		else
		{
			$mesg='<div class="error">'.$mesg.'</div>';
			$action='create';
		}
	}
	else
	{
		Header ( "Location: list.php");
		exit;
	}
}

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes')
{
	if (1==0 &&  ! GETPOST('clone_content') /*&& ! GETPOST('clone_receivers')*/ )
	{
		$mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
	}
	else
	{
		$agf = new Agsession($db);
		if ($agf->fetch($id) > 0)
		{
			$result=$agf->createFromClone($id, $hookmanager);
			if ($result > 0)
			{
				if(GETPOST('clone_calendar') )
				{
					// Reprendre les infos du calendrier
					$calendrierstat = new Agefodd_sesscalendar($db);
					$calendrier = new Agefodd_sesscalendar($db);
					$calendrier->fetch_all($id);
					$blocNumber = count($calendrier->lines);
					if ($blocNumber > 0)
					{
						$old_date = 0;
						$duree = 0;
						for ($i = 0; $i < $blocNumber; $i++)
						{
							$calendrierstat->sessid = $result;
							$calendrierstat->date_session = $calendrier->lines[$i]->date_session;
							$calendrierstat->heured = $calendrier->lines[$i]->heured;
							$calendrierstat->heuref = $calendrier->lines[$i]->heuref;

							$result1 = $calendrierstat->create($user);
						}
					}
				}
				header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
				exit;
			}
			else
			{
				$mesg=$agf->error;
				$action='';
			}
		}
	}
}



/*
 * View
*/

llxHeader('',$langs->trans("AgfSessionDetail"),'','','','',array('/agefodd/includes/jquery/plugins/colorpicker/js/colorpicker.js','/agefodd/includes/lib.js'), array('/agefodd/includes/jquery/plugins/colorpicker/css/colorpicker.css','/agefodd/includes/lib.js'));
$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

dol_htmloutput_mesg($mesg);

/*
 * Action create
*/
if ($action == 'create' && $user->rights->agefodd->creer)
{
	
	$fk_soc_crea = GETPOST('fk_soc','int');
	
	print_fiche_titre($langs->trans("AgfMenuSessNew"));

	print '<form name="add" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add_confirm">';

	print '<table class="border" width="100%">';


	print '<tr><td><span class="fieldrequired">'.$langs->trans("AgfLieu").'</span></td>';
	print '<td><table class="nobordernopadding"><tr><td>';
	print $formAgefodd->select_site_forma("",'place',1);
	print '</td>';
	print '<td> <a href="'.dol_buildpath('/agefodd/site/card.php',1).'?action=create&url_return='.urlencode($_SERVER['PHP_SELF'].'?action=create').'" title="'.$langs->trans('AgfCreateNewSite').'">'.$langs->trans('AgfCreateNewSite').'</a>';
	print '</td><td>'.$form->textwithpicto('',$langs->trans("AgfCreateNewSiteHelp"),1,'help').'</td></tr></table>';
	print '</td></tr>';

	print '<tr><td><span class="fieldrequired">'.$langs->trans("AgfFormIntitule").'</span></td>';
	print '<td>'.$formAgefodd->select_formation("", 'formation','intitule',1).'</a></td></tr>';

	print '<tr><td>'.$langs->trans("AgfFormTypeSession").'</td>';
	print '<td>'.$formAgefodd->select_type_session('type_session',0).'</a></td></tr>';

	print '<tr><td><span class="fieldrequired">'.$langs->trans("AgfSessionCommercial").'</span></td>';
	print '<td>';
	$form->select_users('','commercial',1, array(1));
	print '</td></tr>';

	print '<tr><td><span class="fieldrequired">'.$langs->trans("AgfDateDebut").'</span></td><td>';
	$form->select_date("", 'dad','','','','add');
	print '</td></tr>';

	print '<tr><td><span class="fieldrequired">'.$langs->trans("AgfDateFin").'</span></td><td>';
	$form->select_date("", 'daf','','','','add');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Customer").'</td>';
	print '<td>';
	if ($conf->global->AGF_CONTACT_DOL_SESSION)	{
		$events=array();
		$events[]=array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php',1), 'htmlname' => 'contact', 'params' => array('add-customer-contact' => 'disabled'));
		print $form->select_company($fk_soc_crea,'fk_soc','',1,1,0,$events);
	} else {
		print $form->select_company($fk_soc_crea,'fk_soc','',1,1);
	}
	print '</td></tr>';

	if ($conf->global->AGF_CONTACT_DOL_SESSION)	{
		print '<tr><td>'.$langs->trans("AgfSessionContact").'</td>';
		print '<td><table class="nobordernopadding"><tr><td>';
		if (!empty($fk_soc_crea)) {
			$form->select_contacts($fk_soc_crea,'','contact',1,'','',1);
		} else {
			$form->select_contacts(0,'','contact',1,'','',1);
		}
		print '</td>';
		print '<td>'.$form->textwithpicto('',$langs->trans("AgfAgefoddDolContactHelp"),1,'help').'</td></tr></table>';
		print '</td></tr>';
	}
	else {
		print '<tr><td>'.$langs->trans("AgfSessionContact").'</td>';
		print '<td><table class="nobordernopadding"><tr><td>';
		print $formAgefodd->select_agefodd_contact('', 'contact','',1);
		print '</td>';
		print '<td>'.$form->textwithpicto('',$langs->trans("AgfAgefoddContactHelp"),1,'help').'</td></tr></table>';
		print '</td></tr>';
	}

	print '<tr><td>'.$langs->trans("AgfNumberPlaceAvailable").'</td>';
	print '<td>';
	print '<input type="text" class="flat" name="nb_place" size="4" value="'.GETPOST('nb_place','int').'"/>';
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td>';
	print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';

	print '</table>';
	print '</div>';

	print '<table style=noborder align="right">';
	print '<tr><td align="center" colspan=2>';
	print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'"> &nbsp; ';
	print '<input type="submit" name="cancel" class="butActionDelete" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';

	print '</table>';
	print '</form>';

}
else
{
	// Affichage de la fiche "session"
	if ($id)
	{
		$agf = new Agsession($db);
		$result = $agf->fetch($id);

		if ($result)
		{
			if (!(empty($agf->id)))
			{
				$head = session_prepare_head($agf);

				dol_fiche_head($head, 'card', $langs->trans("AgfSessionDetail"), 0, 'calendarday');

				// Affichage en mode "édition"
				if ($action == 'edit')
				{		
					
					$newperiod=GETPOST('newperiod','int');
					
					print '<form name="update" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="action" value="update">';
					print '<input type="hidden" name="id" value="'.$id.'">';
					print '<input type="hidden" name="action" value="update">';

					print '<table class="border" width="100%">';
					print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
					print '<td>'.$agf->id.'</td></tr>';

					print '<tr><td>'.$langs->trans("AgfFormIntitule").'</td>';
					print '<td>'.$formAgefodd->select_formation($agf->formid, 'formation');
					print '</td></tr>';

					print '<tr><td>'.$langs->trans("AgfFormTypeSession").'</td>';
					print '<td>'.$formAgefodd->select_type_session('type_session',$agf->type_session).'</a></td></tr>';

					print '<tr><td>'.$langs->trans("AgfFormCodeInterne").'</td>';
					print '<td>'.$agf->formref.'</td></tr>';

					print '<tr><td>'.$langs->trans("Color").'</td>';
					print '<td><input id="colorpicker" type="text" size="8" name="color" value="'.$agf->color.'" /></td></tr>';

					print '<script type="text/javascript" language="javascript">
						$(document).ready(function() {
						$("#colorpicker").css("backgroundColor", \'#'.$agf->color.'\');
							$("#colorpicker").ColorPicker({
							color: \'#'.$agf->color.'\',
								onShow: function (colpkr) {
									$(colpkr).fadeIn(500);
									return false;
								},
								onHide: function (colpkr) {
									$(colpkr).fadeOut(500);
									return false;
								},
								onChange: function (hsb, hex, rgb) {
									$("#colorpicker").css("backgroundColor", \'#\' + hex);
									$("#colorpicker").val(hex);
								},
								onSubmit: function (hsb, hex, rgb) {
									$("#colorpicker").val(hex);
								}
							});
						})
								.bind(\'keyup\', function(){
								$(this).ColorPickerSetColor(this.value);
						});
							</script>';
					print '<tr><td>'.$langs->trans("AgfSessionCommercial").'</td>';
					print '<td>';
					$form->select_users($agf->commercialid, 'commercial',1, array(1));
					print '</td></tr>';

					print '<tr><td>'.$langs->trans("AgfDuree").'</td>';
					print '<td>'.$agf->duree.'</td></tr>';

					print '<tr><td>'.$langs->trans("AgfDateDebut").'</td><td>';
					$form->select_date($agf->dated, 'dad','','','','update');
					print '</td></tr>';

					print '<tr><td>'.$langs->trans("AgfDateFin").'</td><td>';
					$form->select_date($agf->datef, 'daf','','','','update');
					print '</td></tr>';

					print '<tr><td>'.$langs->trans("Customer").'</td>';
					print '<td>';
					if ($conf->global->AGF_CONTACT_DOL_SESSION)	{
						$events=array();
						$events[]=array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php',1), 'htmlname' => 'contact', 'params' => array('add-customer-contact' => 'disabled'));
						print $form->select_company($agf->fk_soc,'fk_soc','',1,1,0,$events);
					} else {
						print $form->select_company($agf->fk_soc,'fk_soc','',1,1);
					}

					if ($conf->global->AGF_CONTACT_DOL_SESSION)	{
						print '<tr><td>'.$langs->trans("AgfSessionContact").'</td>';
						print '<td><table class="nobordernopadding"><tr><td>';
						if (!empty($agf->fk_soc)) {
							$form->select_contacts($agf->fk_soc,$agf->sourcecontactid,'contact',1,'','',1);
						} else {
							$form->select_contacts(0,$agf->sourcecontactid,'contact',1,'','',1);
						}
						print '</td>';
						print '<td>'.$form->textwithpicto('',$langs->trans("AgfAgefoddDolContactHelp"),1,'help').'</td></tr></table>';
						print '</td></tr>';
					}
					else {
						print '<tr><td>'.$langs->trans("AgfSessionContact").'</td>';
						print '<td><table class="nobordernopadding"><tr><td>';
						print $formAgefodd->select_agefodd_contact($agf->contactid, 'contact','',1);
						print '</td><td>'.$form->textwithpicto('',$langs->trans("AgfAgefoddContactHelp"),1,'help').'</td></tr></table>';
						print '</td></tr>';
					}

					print '<tr><td>'.$langs->trans("AgfLieu").'</td>';
					print '<td>';
					print $formAgefodd->select_site_forma($agf->placeid,'place');
					print '</td></tr>';

					print '<tr><td width="20%">'.$langs->trans("AgfNumberPlaceAvailable").'</td>';
					print '<td><input size="4" type="text" class="flat" name="nb_place" value="'.$agf->nb_place.'" />'.'</td></tr>';

					if ($agf->force_nb_stagiaire==0 || empty($agf->force_nb_stagiaire)) {
						$disabled = 'disabled="disabled"';
						$checked = '';
					}
					else {
						$disabled = '';
						$checked = 'checked="checked"';
					}
					// Si non forcé on doit pouvoir saisir une valeur
					print '<tr><td width="20%">'.$langs->trans("AgfNbreParticipants").'</td>';
					print '<td><input size="4" type="text" class="flat" id="nb_stagiaire" name="nb_stagiaire" '.$disabled.' value="'.($agf->nb_stagiaire>0?$agf->nb_stagiaire:'0').'" />'.'</td></tr>';

					print '<tr><td width="20%">'.$langs->trans("AgfForceNbreParticipants").'</td>';
					print '<td>';
					print '<input size="4" type="checkbox" '.$checked.' name="force_nb_stagiaire" value="1" onclick="fnForceUpdate(this);" />'.'</td></tr>';

					print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td>';
					if (!empty($agf->note)) $notes = nl2br($agf->note);
					else $notes =  $langs->trans("AgfUndefinedNote");
					print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;">'.stripslashes($agf->notes).'</textarea></td></tr>';

					print '<tr><td>'.$langs->trans("AgfDateResTrainer").'</td><td><table class="nobordernopadding"><tr><td>';
					if ($agf->is_date_res_site==1) {
						$chkrestrainer='checked="checked"';
					}
					print '<input type="checkbox" name="isdaterestrainer" value="1" '.$chkrestrainer.'/></td><td>';
					$form->select_date($agf->date_res_trainer, 'res_train','','',1,'update',1,1);
					print '</td><td>';
					print $form->textwithpicto('', $langs->trans("AgfDateCheckbox"));
					print '</td></tr></table>';
					print '</td></tr>';

					print '<tr><td>'.$langs->trans("AgfDateResSite").'</td><td><table class="nobordernopadding"><tr><td>';
					if ($agf->is_date_res_site==1) {
						$chkressite='checked="checked"';
					}
					print '<input type="checkbox" name="isdateressite" value="1" '.$chkressite.' /></td><td>';
					$form->select_date($agf->date_res_site, 'res_site','','',1,'update',1,1);
					print '</td><td>';
					print $form->textwithpicto('', $langs->trans("AgfDateCheckbox"));
					print '</td></tr></table>';
					print '</td></tr>';

					print '</table>';
					print '</div>';

					/*
					 * Gestion des cout
					*/
					print_barre_liste($langs->trans("AgfCost"),"", "","","","",'',0);
					print '<div class="tabBar">';
					print '<table class="border" width="100%">';
					print '<tr><td width="20%">'.$langs->trans("AgfCoutFormateur").'</td>';
					print '<td><input size="6" type="text" class="flat" name="costtrainer" value="'.price($agf->cost_trainer).'" />'.' '.$langs->trans('Currency'.$conf->currency).'</td></tr>';

					print '<tr><td width="20%">'.$langs->trans("AgfCoutSalle").'</td>';
					print '<td><input size="6" type="text" class="flat" name="costsite" value="'.price($agf->cost_site).'" />'.' '.$langs->trans('Currency'.$conf->currency).'</td></tr>';
					print '<tr><td width="20%">'.$langs->trans("AgfCoutDeplacement").'</td>';
					print '<td><input size="6" type="text" class="flat" name="costtrip" value="'.price($agf->cost_trip).'" />'.' '.$langs->trans('Currency'.$conf->currency).'</td></tr>';

					print '<tr><td width="20%">'.$langs->trans("AgfCoutFormation").'</td>';
					print '<td><input size="6" type="text" class="flat" name="sellprice" value="'.price($agf->sell_price).'" />'.' '.$langs->trans('Currency'.$conf->currency).'</td></tr>';
					print '</table></div>';

					print '<table style=noborder align="right">';
					print '<tr><td align="center" colspan=2>';
					print '<input type="submit" class="butAction" name="save" value="'.$langs->trans("Save").'"> &nbsp; ';
					print '<input type="submit" class="butAction" name="saveandclose" value="'.$langs->trans("SaveAndClose").'"> &nbsp; ';
					print '<input type="submit" name="cancel" class="butActionDelete" value="'.$langs->trans("Cancel").'">';
					print '</td></tr>';
					print '</table>';

					print '</form>';

					/*
					 * Gestion Calendrier
					*/
					print_barre_liste($langs->trans("AgfCalendrier"),"", "","","","",'',0);

					/*
					 * Confirmation de la suppression
					*/
					if ($_POST["period_remove_x"])
					{
						// Param url = id de la periode à supprimer - id session
						$ret=$form->form_confirm($_SERVER['PHP_SELF'].'?modperiod='.$_POST["modperiod"].'&id='.$id,$langs->trans("AgfDeletePeriod"),$langs->trans("AgfConfirmDeletePeriod"),"confirm_delete_period",'','',1);
						if ($ret == 'html') print '<br>';
					}
					print '<div class="tabBar">';
					print '<table class="border" width="100%">';

					$calendrier = new Agefodd_sesscalendar($db);
					$calendrier->fetch_all($agf->id);
					$blocNumber = count($calendrier->lines);
					if ($blocNumber < 1 && !(empty($newperiod)))
					{
						print '<tr>';
						print '<td  colpsan=1 style="color:red; text-decoration: blink;">'.$langs->trans("AgfNoCalendar").'</td></tr>';
					}
					else
					{
						$old_date = 0;
						$duree = 0;
						for ($i = 0; $i < $blocNumber; $i++)
						{
							print '<tr>'."\n";;
							print '<form name="obj_update_'.$i.'" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
							print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
							print '<input type="hidden" name="action" value="edit">'."\n";
							print '<input type="hidden" name="sessid" value="'.$calendrier->lines[$i]->sessid.'">'."\n";
							print '<input type="hidden" name="modperiod" value="'.$calendrier->lines[$i]->id.'">'."\n";

							if ($calendrier->lines[$i]->id == $_POST["modperiod"] && ! $_POST["period_remove_x"])
							{
								print '<td  width="20%">'.$langs->trans("AgfPeriodDate").' ';
								$form->select_date($calendrier->lines[$i]->date_session, 'date','','','','obj_update_'.$i);
								print '</td>';
								print '<td width="150px" nowrap>'.$langs->trans("AgfPeriodTimeB").' ';
								print $formAgefodd->select_time(dol_print_date($calendrier->lines[$i]->heured,'hour'),'dated');
								print ' - '.$langs->trans("AgfPeriodTimeE").' ';
								print $formAgefodd->select_time(dol_print_date($calendrier->lines[$i]->heuref,'hour'),'datef');
								print '</td>';

								if ($user->rights->agefodd->modifier)
								{
									print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="period_update" alt="'.$langs->trans("AgfModSave").'" ">';
								}
							}
							else
							{
								print '<td width="20%">'.dol_print_date($calendrier->lines[$i]->date_session,'daytext').'</td>';
								print '<td  width="150px">'.dol_print_date($calendrier->lines[$i]->heured,'hour').' - '.dol_print_date($calendrier->lines[$i]->heuref,'hour');
								if ($user->rights->agefodd->modifier)
								{
									print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" name="period_edit" alt="'.$langs->trans("AgfModSave").'">';
								}
								print '&nbsp;';
								if ($user->rights->agefodd->creer)
								{
									print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" border="0" name="period_remove" alt="'.$langs->trans("AgfModSave").'">';
								}
							}
							print '</td>' ;

							// On calcule la duree totale du calendrier
							$duree += ($calendrier->lines[$i]->heuref - $calendrier->lines[$i]->heured);

							print '</form>'."\n";
							print '</tr>'."\n";
						}
						if (($agf->duree * 3600) != $duree)
						{
							print '<tr><td colspan=5 align="center"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/recent.png" border="0" align="absmiddle" hspace="6px" >';
							if (($agf->duree * 3600) < $duree) print $langs->trans("AgfCalendarSup");
							if (($agf->duree * 3600) > $duree) print $langs->trans("AgfCalendarInf");
							$rsec = sprintf("%02d",$duree % 60);
							$min = floor($duree/60) ;
							$rmin = sprintf("%02d", $min %60) ;
							$hour = floor($min/60);
							print ' ('.$langs->trans("AgfCalendarDureeProgrammee").': '.$hour.':'.$rmin.', ';
							print $langs->trans("AgfCalendarDureeThéorique").' : '.($agf->duree).':00).</td></tr>';
						}
					}

					// Champs nouvelle periode
					
					if (!empty($newperiod))
					{
						print "</table></div>";
						//print '&nbsp';
						print '<table style="border:0;" width="100%">';
						print '<tr><td align="right">';
						print '<form name="newperiod" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
						print '<input type="hidden" name="action" value="edit">'."\n";
						print '<input type="hidden" name="newperiod" value="1">'."\n";
						print '<input type="submit" class="butAction" value="'.$langs->trans("AgfPeriodAdd").'">';
						print '</td></tr>';
						print '</form>';
					}
					else
					{
						print '<form name="obj_update_'.($i + 1).'" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
						print '<input type="hidden" name="action" value="edit">'."\n";
						print '<input type="hidden" name="sessid" value="'.$agf->id.'">'."\n";
						print '<input type="hidden" name="periodid" value="'.$stagiaires->line[$i]->stagerowid.'">'."\n";
						print '<input type="hidden" id="datetmplday"   name="datetmplday"   value="'.dol_print_date($agf->dated, "%d").'">'."\n";
						print '<input type="hidden" id="datetmplmonth" name="datetmplmonth" value="'.dol_print_date($agf->dated, "%m").'">'."\n";
						print '<input type="hidden" id="datetmplyear"  name="datetmplyear"  value="'.dol_print_date($agf->dated, "%Y").'">'."\n";
						
						//Add new line from template
						$tmpl_calendar = new Agefoddcalendrier($db);
						$result=$tmpl_calendar->fetch_all();
						if ($result) {
							print '<tr>';
							print '<td colspan="3">';
							print $langs->trans('AgfCalendarFromTemplate').':';
							print '<select id="fromtemplate" name="fromtemplate">';
							print '<option value="-1"></option>';
							foreach($tmpl_calendar->lines as $line) {
								if ($line->day_session!=1) {
								$tmpldate = dol_time_plus_duree($agf->dated, (($line->day_session)-1), 'd');
								} else {
									$tmpldate= $agf->dated;
								}
								print '<option value="'.$line->id.'">'.dol_print_date($tmpldate,'daytext').' '.$line->heured.' - '.$line->heuref.'</option>';
							}
							print '</select>';
							print '</td>';
							if ($user->rights->agefodd->modifier)
							{
								print '<td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="period_add" alt="'.$langs->trans("AgfModSave").'" "></td>';
							}
							print '</tr>'."\n";
						}
						print '<tr>';
	
						print '<td  width="300px">'.$langs->trans("AgfPeriodDate").' ';
						$form->select_date($agf->dated, 'date','','','','newperiod');
						print '</td>';
						print '<td width="400px">'.$langs->trans("AgfPeriodTimeB").' ';
						print $formAgefodd->select_time('08:00','dated');
						print '</td>';
						print '<td width="400px">'.$langs->trans("AgfPeriodTimeE").' ';
						print $formAgefodd->select_time('18:00','datef');
						print '</td>';
						if ($user->rights->agefodd->modifier)
						{
							print '<td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="period_add" alt="'.$langs->trans("AgfModSave").'" "></td>';
						}
						
						print '</tr>'."\n";
						print '</form>';
					}

					print '</table>';
					print '</div>';


				}
				else
				{
					// Affichage en mode "consultation"
					/*
					* Confirmation de la suppression
					*/
					if ($action == 'delete')
					{
						$ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$id,$langs->trans("AgfDeleteOps"),$langs->trans("AgfConfirmDeleteOps"),"confirm_delete",'','',1);
						if ($ret == 'html') print '<br>';
					}

					/*
					 * Confirmation de l'archivage/activation suppression
					*/
					if (isset($_GET["arch"]))
					{
						$ret=$form->form_confirm($_SERVER['PHP_SELF']."?arch=".$_GET["arch"]."&id=".$id,$langs->trans("AgfFormationArchiveChange"),$langs->trans("AgfConfirmArchiveChange"),"arch_confirm_delete",'','',1);
						if ($ret == 'html') print '<br>';
					}

					// Confirm delete
					if ($action == 'clone')
					{
						$formquestion=array(
						'text' => $langs->trans("ConfirmClone"),
						array('type' => 'checkbox', 'name' => 'clone_calendar','label' => $langs->trans("AgfCloneSessionCalendar"),   'value' => 1)
						);
						$ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$id,$langs->trans("CloneSession"),$langs->trans("ConfirmCloneSession"),"confirm_clone",$formquestion,'',1);
						//$ret=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('CloneSession'), $langs->trans('ConfirmCloneSession',$agf->ref), 'confirm_clone','','',1);
						if ($ret == 'html') print '<br>';
					}

					print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
					print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
					print '</div>';

					// Print session card
					$agf->printSessionInfo();

					print '&nbsp';

					/*
					 * Gestion de la subrogation (affiché si la session est de type inter-entreprise)
					*/
					if(!$agf->type_session > 0 && !empty($conf->global->AGF_MANAGE_OPCA))
					{
						print '&nbsp';
						print '<table class="border" width="100%">';
						print '<tr><td>'.$langs->trans("AgfSubrocation").'</td>';
						if ($agf->is_OPCA==1) {
							$isOPCA=' checked="checked" ';
						}else {$isOPCA='';
						}
						print '<td><input type="checkbox" class="flat" readonly="readonly" '.$isOPCA.'/></td></tr>';

						print '<tr><td width="20%">'.$langs->trans("AgfOPCAName").'</td>';
						print '	<td>';
						print '<a href="'.dol_buildpath('/societe/soc.php',1).'?socid='.$agf->fk_soc_OPCA.'">'.$agf->soc_OPCA_name.'</a>';
						print '</td></tr>';

						print '<tr><td width="20%">'.$langs->trans("AgfOPCAAdress").'</td>';
						print '	<td>';
						print dol_print_address($agf->OPCA_adress,'gmap','thirdparty',0);
						print '</td></tr>';

						print '<tr><td width="20%">'.$langs->trans("AgfOPCAContact").'</td>';
						print '	<td>';
						print '<a href="'.dol_buildpath('/contact/fiche.php',1).'?id='.$agf->fk_socpeople_OPCA.'">'.$agf->contact_name_OPCA.'</a>';
						print '</td></tr>';

						print '<tr><td width="20%">'.$langs->trans("AgfOPCANumClient").'</td>';
						print '<td>';
						print $agf->num_OPCA_soc;
						print '</td></tr>';

						print '<tr><td width="20%">'.$langs->trans("AgfOPCADateDemande").'</td>';
						if ($agf->is_date_ask_OPCA==1) {
							$chckisDtOPCA='checked="checked"';
						}
						print '<td><input type="checkbox" class="flat" readonly="readonly" name="isdateaskOPCA" value="1" '.$chckisDtOPCA.' />';
						print dol_print_date($agf->date_ask_OPCA,'daytext');
						print '</td></tr>';

						print '<tr><td width="20%">'.$langs->trans("AgfOPCANumFile").'</td>';
						print '<td>';
						print $agf->num_OPCA_file;
						print '</td></tr>';

						print '</table>';
					}

					/*
					 * Gestion des cout
					*/
					$spend_cost = 0;
					$cashed_cost = 0;
					
					print '&nbsp';
					print '<table class="border" width="100%">';
					print '<tr><td width="20%">'.$langs->trans("AgfCoutFormateur").'</td>';
					print '<td>'.price($agf->cost_trainer).' '.$langs->trans('Currency'.$conf->currency).'</td></tr>';
					$spend_cost+=$agf->cost_trainer;

					print '<tr><td width="20%">'.$langs->trans("AgfCoutSalle").'</td>';
					print '<td>'.price($agf->cost_site).' '.$langs->trans('Currency'.$conf->currency).'</td></tr>';
					$spend_cost+=$agf->cost_site;

					print '<tr><td width="20%">'.$langs->trans("AgfCoutDeplacement").'</td>';
					print '<td>'.price($agf->cost_trip).' '.$langs->trans('Currency'.$conf->currency).'</td></tr>';
					$spend_cost+=$agf->cost_trip;

					print '<tr><td width="20%"><strong>'.$langs->trans("AgfCoutTotal").'</strong></td>';
					print '<td><strong>'.price($spend_cost).' '.$langs->trans('Currency'.$conf->currency).'</strong></td></tr>';

					print '<tr><td width="20%">'.$langs->trans("AgfCoutFormation").'</td>';
					print '<td>'.price($agf->sell_price).' '.$langs->trans('Currency'.$conf->currency).'</td></tr>';
					$cashed_cost+=$agf->sell_price;
					
					print '<tr><td width="20%"><strong>'.$langs->trans("AgfCoutRevient").'</strong></td>';
					print '<td><strong>'.price($cashed_cost-$spend_cost).' '.$langs->trans('Currency'.$conf->currency).'</strong></td></tr>';

					print '</table>';


					/*
					 * Gestion des formateurs
					*/
					print '&nbsp';
					print '<table class="border" width="100%">';

					$formateurs = new Agefodd_session_formateur($db);
					$nbform = $formateurs->fetch_formateur_per_session($agf->id);
					print '<tr><td width="20%" valign="top">';
					print $langs->trans("AgfFormateur");
					if ($nbform > 0) print ' ('.$nbform.')';
					print '</td>';
					if ($nbform < 1)
					{
						print '<td style="text-decoration: blink;">'.$langs->trans("AgfNobody").'</td></tr>';
					}
					else
					{
						print '<td>';
						for ($i=0; $i < $nbform; $i++)
						{
							// Infos formateurs
							print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$formateurs->line[$i]->socpeopleid.'">';
							print img_object($langs->trans("ShowContact"),"contact").' ';
							print strtoupper($formateurs->line[$i]->name).' '.ucfirst($formateurs->line[$i]->firstname).'</a>';
							if ($i < ($nbform - 1)) print ',&nbsp;&nbsp;';

						}
						print '</td>';
						print "</tr>\n";
					}
					print "</table>";

					/*
					 * Gestion du calendrier
					*/

					print '&nbsp';
					print '<table class="border" width="100%">';
					print '<tr>';

					$calendrier = new Agefodd_sesscalendar($db);
					$calendrier->fetch_all($agf->id);
					$blocNumber = count($calendrier->lines);
					if ($blocNumber < 1)
					{
						print '<td  width="20%" valign="top" >'.$langs->trans("AgfCalendrier").'</td>';
						print '<td style="color:red; text-decoration: blink;">'.$langs->trans("AgfNoCalendar").'</td></tr>';
					}
					else
					{
						print '<td  width="20%" valign="top" style="border-bottom:0px;">'.$langs->trans("AgfCalendrier").'</td>';
						$old_date = 0;
						$duree = 0;
						for ($i = 0; $i < $blocNumber; $i++)
						{
							if ($calendrier->lines[$i]->date_session != $old_date)
							{
								if ($i > 0 )print '</tr><tr><td width="150px" style="border:0px;">&nbsp;</td>';
								print '<td width="150px">';
								print dol_print_date($calendrier->lines[$i]->date_session,'daytext').'</td><td>';
							}
							else print ', ';
							print dol_print_date($calendrier->lines[$i]->heured,'hour').' - '.dol_print_date($calendrier->lines[$i]->heuref,'hour');
							if ($i == $blocNumber -1 ) print '</td></tr>';

							$old_date = $calendrier->lines[$i]->date_session;

							// On calcule la duree totale du calendrier
							// pour mémoire: mktime(heures, minutes, secondes, mois, jour, année);
							$duree += ($calendrier->lines[$i]->heuref - $calendrier->lines[$i]->heured);
						}
						if (($agf->duree * 3600) != $duree)
						{
							print '<tr><td>&nbsp;</td><td colspan=2><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/recent.png" border="0" align="absmiddle" hspace="6px" >';
							if (($agf->duree * 3600) < $duree) print $langs->trans("AgfCalendarSup");
							if (($agf->duree * 3600) > $duree) print $langs->trans("AgfCalendarInf");
							$rsec = sprintf("%02d",$duree % 60);
							$min = floor($duree/60) ;
							$rmin = sprintf("%02d", $min %60) ;
							$hour = floor($min/60);
							print ' ('.$langs->trans("AgfCalendarDureeProgrammee").': '.$hour.':'.$rmin.', ';
							print $langs->trans("AgfCalendarDureeThéorique").' : '.($agf->duree).':00).</td></tr>';
						}
					}
					print '</tr>';
					print "</table>";

					/*
					 * Gestion des stagiaires
					*/

					print '&nbsp';
					print '<table class="border" width="100%">';

					$stagiaires = new Agsession($db);
					$stagiaires->fetch_stagiaire_per_session($agf->id);
					$nbstag = count($stagiaires->line);
					print '<tr><td  width="20%" valign="top" ';
					if ($nbstag < 1) {
						print '>'.$langs->trans("AgfParticipants").'</td>';
						print '<td style="text-decoration: blink;">'.$langs->trans("AgfNobody").'</td></tr>';
					}
					else
					{
						print ' rowspan='.($nbstag).'>'.$langs->trans("AgfParticipants");
						if ($nbstag > 1) print ' ('.$nbstag.')';
						print '</td>';

						for ($i=0; $i < $nbstag; $i++)	{
							print '<td witdth="20px" align="center">'.($i+1).'</td>';
							print '<td width="400px"style="border-right: 0px;">';
							// Infos stagiaires
							if (strtolower($stagiaires->line[$i]->nom) == "undefined")	{
								print $langs->trans("AgfUndefinedStagiaire");
							}
							else {
								$trainee_info = '<a href="'.dol_buildpath('/agefodd/trainee/card.php',1).'?id='.$stagiaires->line[$i]->id.'">';
								$trainee_info .= img_object($langs->trans("ShowContact"),"contact").' ';
								$trainee_info .= strtoupper($stagiaires->line[$i]->nom).' '.ucfirst($stagiaires->line[$i]->prenom).'</a>';
								$contact_static= new Contact($db);
								$contact_static->civilite_id = $stagiaires->line[$i]->civilite;
								$trainee_info .= ' ('.$contact_static->getCivilityLabel().')';

								if ($agf->type_session == 1)
								{
									print '<table class="nobordernopadding" width="100%"><tr><td colspan="2">';
									print $trainee_info;
									print '</td></tr>';

									$agf->getOpcaForTraineeInSession($stagiaires->line[$i]->socid,$agf->id);
									print '<tr><td width="45%">'.$langs->trans("AgfSubrocation").'</td>';
									if ($agf->is_OPCA==1) {
										$chckisOPCA='checked="checked"';
									}
									print '<td><input type="checkbox" class="flat" name="isOPCA" value="1" '.$chckisOPCA.'" readonly="readonly"/></td></tr>';

									print '<tr><td>'.$langs->trans("AgfOPCAName").'</td>';
									print '	<td>';
									print '<a href="'.dol_buildpath('/societe/soc.php',1).'?socid='.$agf->fk_soc_OPCA.'">'.$agf->soc_OPCA_name.'</a>';
									print '</td></tr>';

									print '<tr><td>'.$langs->trans("AgfOPCAContact").'</td>';
									print '	<td>';
									print '<a href="'.dol_buildpath('/contact/fiche.php',1).'?id='.$agf->fk_socpeople_OPCA.'">'.$agf->contact_name_OPCA.'</a>';
									print '</td></tr>';

									print '<tr><td width="20%">'.$langs->trans("AgfOPCANumClient").'</td>';
									print '<td>'.$agf->num_OPCA_soc.'</td></tr>';

									print '<tr><td width="20%">'.$langs->trans("AgfOPCADateDemande").'</td>';
									if ($agf->is_date_ask_OPCA==1) {
										$chckisDtOPCA='checked="checked"';
									}
									print '<td><table class="nobordernopadding"><tr><td>';
									print '<input type="checkbox" class="flat" name="isdateaskOPCA" readonly="readonly" value="1" '.$chckisDtOPCA.' /></td>';
									print '<td>';
									print dol_print_date($agf->date_ask_OPCA,'daytext');
									print '</td><td>';
									print '</td></tr></table>';
									print '</td></tr>';

									print '<tr><td width="20%">'.$langs->trans("AgfOPCANumFile").'</td>';
									print '<td>'.$agf->num_OPCA_file.'</td></tr>';

									print '</table>';
								}
								else {
									print $trainee_info;
								}
							}
							print '</td>';
							print '<td style="border-left: 0px; border-right: 0px;">';
							// Infos organisme de rattachement
							if ($stagiaires->line[$i]->socid) {
								print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$stagiaires->line[$i]->socid.'">';
								print img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($stagiaires->line[$i]->socname,20).'</a>';
							}
							else {
								print '&nbsp;';
							}
							print '</td>';
							print '<td style="border-left: 0px;">';
							// Infos mode de financement
							if ($stagiaires->line[$i]->type && (!empty($conf->global->AGF_USE_STAGIAIRE_TYPE))) {
								print '<div class=adminaction>';
								print $langs->trans("AgfStagiaireModeFinancement");
								print '-<span>'.stripslashes($stagiaires->line[$i]->type).'</span></div>';
							}
							else {
								print '&nbsp;';
							}
							print '</td>';
							print "</tr>\n";
						}
					}
					print "</table>";
					print '</div>';
				}
			}
			else
			{
				print $langs->trans('AgfNoSession');
			}
		}
		else
		{
			dol_print_error($db);
		}
	}
}


/*
 * Barre d'actions
*
*/

print '<div class="tabsAction">';

if ($action != 'create' && $action != 'edit' && (!empty($agf->id)))
{
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'">'.$langs->trans('Modify').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Modify').'</a>';
	}
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butAction" href="subscribers.php?action=edit&id='.$id.'">'.$langs->trans('AgfModifySubscribersAndSubrogation').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('AgfModifySubscribersAndSubrogation').'</a>';
	}

	if ($user->rights->agefodd->creer)
	{
		print '<a class="butAction" href="trainer.php?action=edit&id='.$id.'">'.$langs->trans('AgfModifyTrainer').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('AgfModifyTrainer').'</a>';
	}

	if ($user->rights->agefodd->creer)
	{
		print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
	}
	if ($agf->archive == 0)
	{
		$button = $langs->trans('AgfArchiver');
		$arch = 1;
	}
	else
	{
		$button = $langs->trans('AgfActiver');
		$arch = 0;
	}
	if ($user->rights->agefodd->modifier)
	{
		print '<a class="butAction" href="'.dol_buildpath('/agefodd/session/send_docs.php',1).'?action=view_actioncomm&id='.$id.'">'.$langs->trans('AgfViewActioncomm').'</a>';
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=clone&id='.$id.'">'.$langs->trans('ToClone').'</a>';
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?arch='.$arch.'&id='.$id.'">'.$button.'</a>';

	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$button.'</a>';
	}
}

print '</div>';

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>

