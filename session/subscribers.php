<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
* Copyright (C) 2012		Florian Henry	<florian.henry@open-concept.pro>
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
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

/**
 * 	\file		/agefodd/session/subscribers.php
* 	\brief		Page présentant la liste des documents administratif disponibles dans Agefodd
*/

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/contact/class/contact.class.php');
dol_include_once('/agefodd/class/html.formagefodd.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$action=GETPOST('action','alpha');
$id=GETPOST('id','int');
$confirm=GETPOST('confirm','alpha');
$stag_update_x=GETPOST('stag_update_x','alpha');
$stag_add_x=GETPOST('stag_add_x','alpha');

$mesg = '';
if ($action=='edit' && $user->rights->agefodd->creer) {

	if($stag_update_x  > 0) {
		$agf = new Agsession($db);

		$agf->id = GETPOST('stagerowid','int');
		$agf->sessid = GETPOST('sessid','int');
		$agf->stagiaire = GETPOST('stagiaire','int');
		$agf->type = GETPOST('stagiaire_type','int');

		if ($agf->update_stag_in_session($user) > 0)
		{
			$redirect=true;
			if ($agf->fetch($agf->sessid)) {

				// TODO : si session inter => ajout des infos OPCA dans la table
				if ($result && $agf->type_session == 1) {

					/*
					 *  Test si les infos existent déjà
					* -> si OUI alors on update
					* -> si NON on crée l'entrée dans la table
					*/
					$agf->id_opca_trainee = $agf->getOpcaForTraineeInSession(GETPOST('fk_soc_trainee','int'),GETPOST('sessid','int'));

					$agf->fk_soc_trainee 		= GETPOST('fk_soc_trainee','int');
					$agf->fk_session_agefodd 	= GETPOST('sessid','int');
					$agf->is_date_ask_OPCA 		= GETPOST('isdateaskOPCA','int');
					$agf->date_ask_OPCA 		= dol_mktime(0,0,0,GETPOST('ask_OPCAmonth','int'),GETPOST('ask_OPCAday','int'),GETPOST('ask_OPCAyear','int'));
					$agf->is_OPCA 				= GETPOST('isOPCA','int');
					$agf->fk_soc_OPCA 			= GETPOST('fksocOPCA','int');
					$agf->fk_socpeople_OPCA 	= GETPOST('fksocpeopleOPCA','int');
					$agf->num_OPCA_soc 			= GETPOST('numOPCAsoc','alpha');
					$agf->num_OPCA_file 		= GETPOST('numOPCAFile','alpha');

					if ($agf->id_opca_trainee > 0)
					{
						if ($agf->updateInfosOpca($user)) {
							$mesg = '<div class="confirm">'.$langs->trans('Saved').'</div>';
						}
						else {
							$mesg = '<div class="error">'.$agf->error.'</div>';
							$redirect=false;
						}
					}
					else {
						if ($agf->saveInfosOpca($user)) {
							$mesg = '<div class="confirm">'.$langs->trans('Saved').'</div>';
						}
						else {
							$mesg = '<div class="error">'.$agf->error.'</div>';
							$redirect=false;
						}
					}
				}
			}
			else
			{
				$mesg = '<div class="error">'.$agf->error.'</div>';
			}
			if ($redirect)
			{
				Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&msg=1&id=".$id);
				exit;
			}
		}
		else
		{
			dol_syslog("agefodd:session:subscribers error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	}

	if($stag_add_x > 0) {

		$agf = new Agsession($db);

		$agf->sessid = GETPOST('sessid','int');
		$agf->stagiaire = GETPOST('stagiaire','int');
		$agf->stagiaire_type = GETPOST('stagiaire_type','int');
		$result = $agf->create_stag_in_session($user);

		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd:session:subscribers error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	}
}


/*
 * Actions delete stagiaire
*/

if ($action == 'confirm_delete_stag' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	$stagerowid=GETPOST('stagerowid','int');

	$agf = new Agsession($db);
	$result = $agf->remove_stagiaire($stagerowid);

	if ($result > 0)
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
		exit;

	}
	else
	{
		dol_syslog("agefodd:session:subscribers error=".$agf->error, LOG_ERR);
		$mesg = '<div class="error">'.$agf->error.'</div>';
	}
}

/*
 * Action update info OPCA
*/
if ($action == 'update_subrogation' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"])
	{
		$error=0;

		$agf = new Agsession($db);

		$res = $agf->fetch($id);
		if ($res > 0)
		{
			$isOPCA=GETPOST('isOPCA','int');
			if (!empty($isOPCA)) {
				$agf->is_OPCA=$isOPCA;
			}
			else {$agf->is_OPCA=0;
			}

			$fksocpeopleOPCA=GETPOST('fksocpeopleOPCA','int');
			$agf->fk_socpeople_OPCA=$fksocpeopleOPCA;
			$fksocOPCA=GETPOST('fksocOPCA','int');
			if (!empty($fksocOPCA)) {
				$agf->fk_soc_OPCA=$fksocOPCA;
			}

			$agf->num_OPCA_soc=GETPOST('numOPCAsoc','alpha');
			$agf->num_OPCA_file=GETPOST('numOPCAFile','alpha');

			$agf->date_ask_OPCA = dol_mktime(0,0,0,GETPOST('ask_OPCAmonth','int'),GETPOST('ask_OPCAday','int'),GETPOST('ask_OPCAyear','int'));
			if ($agf->date_ask_OPCA=='') {
				$isdateaskOPCA=0;
			} else {$isdateressite=GETPOST('isdateaskOPCA','int');
			}
			$agf->is_date_ask_OPCA=$isdateressite;

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
					$action='edit_subrogation';
				}
			}
		}
	}
}


/*
 * View
*/
$arrayofcss = array('/agefodd/css/agefodd.css');
llxHeader($head, '','','','','','',$arrayofcss,'');

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);

dol_htmloutput_mesg($mesg);

if (!empty($id))
{
	$agf = new Agsession($db);
	$result = $agf->fetch($id);

	$head = session_prepare_head($agf);

	dol_fiche_head($head, 'subscribers', $langs->trans("AgfSessionDetail"), 0, 'group');

	if ($action == 'edit')
	{

		/*
		 * Confirmation de la suppression
		*/
		if ($_POST["stag_remove_x"])
		{
			// Param url = id de la ligne stagiaire dans session - id session
			$ret=$form->form_confirm($_SERVER['PHP_SELF']."?stagerowid=".$_POST["stagerowid"].'&id='.$id,$langs->trans("AgfDeleteStag"),$langs->trans("AgfConfirmDeleteStag"),"confirm_delete_stag",'','',1);
			if ($ret == 'html') print '<br>';
		}

		print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
		print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
		print '</div>';

		// Print session card
		$agf->printSessionInfo();

		print '&nbsp';

		/*
		 * Gestion de la subrogation (affiché uniquement si sessions de type inter-entreprise
		 	*/
		if(!$agf->type_session > 0)
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

		print '<div class="tabBar">';
		print '<table class="border" width="100%">';

		/*
		 *  Bloc d'affichage et de modification des infos sur les stagiaires
		*
		*/
		$stagiaires = new Agsession($db);
		$stagiaires->fetch_stagiaire_per_session($agf->id);
		$nbstag = count($stagiaires->line);
		if ($nbstag > 0)
		{
			$fk_soc_used=array();
			for ($i=0; $i < $nbstag; $i++)
			{
				$show_subrogation='';
				// Check if it's first consult of fk_soc
				if(! in_array($stagiaires->line[$i]->socid,$fk_soc_used)) {
					$fk_soc_used[$i] = $stagiaires->line[$i]->socid;
					$show_subrogation=true;
				}
				if ($stagiaires->line[$i]->id == $_POST["modstagid"] && $_POST["stag_remove_x"]) print '<tr bgcolor="#d5baa8">';
				else print '<tr>';
				print '<form name="obj_update_'.$i.'" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
				print '<input type="hidden" name="sessid" value="'.$stagiaires->line[$i]->sessid.'">'."\n";
				print '<input type="hidden" name="stagerowid" value="'.$stagiaires->line[$i]->stagerowid.'">'."\n";
				print '<input type="hidden" name="modstagid" value="'.$stagiaires->line[$i]->id.'">'."\n";
				print '<input type="hidden" name="fk_soc_trainee" value="'.$stagiaires->line[$i]->socid.'">'."\n";
				print '<td width="3%" align="center">'.($i+1).'</td>';

				if ($stagiaires->line[$i]->id == $_POST["modstagid"] && ! $_POST["stag_remove_x"])
				{
					print '<td colspan="2" >';
					print'<label for="'.$htmlname.'" style="width:45%; display: inline-block;margin-left:5px;">'.$langs->trans('AgfSelectStagiaire').'</label>';

					print $formAgefodd->select_stagiaire($stagiaires->line[$i]->id, 'stagiaire', '(s.rowid NOT IN (SELECT fk_stagiaire FROM '.MAIN_DB_PREFIX.'agefodd_session_stagiaire WHERE fk_session_agefodd='.$id.')) OR (s.rowid='.$stagiaires->line[$i]->id.')');

					/*
					 * Gestion OPCA pour le stagiaire si session inter-entreprise
					* Affiché seulement si c'est le premier tiers de la liste
					*
					*/
					if ($agf->type_session == 1 && !$_POST['cancel'] && $show_subrogation)
					{
						$agf->getOpcaForTraineeInSession($stagiaires->line[$i]->socid,$agf->id);
						print '<table class="noborder noshadow" width="100%" id="form_subrogation">';
						print '<tr class="noborder"><td  class="noborder" width="45%">'.$langs->trans("AgfSubrocation").'</td>';
						if ($agf->is_OPCA==1) {
							$chckisOPCA='checked="checked"';
						}
						print '<td><input type="checkbox" class="flat" name="isOPCA" value="1" '.$chckisOPCA.'" /></td></tr>';

						print '<tr><td>'.$langs->trans("AgfOPCAName").'</td>';
						print '	<td>';
						$events=array();
						$events[]=array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php',1), 'htmlname' => 'fksocpeopleOPCA', 'params' => array('add-customer-contact' => 'disabled'));
						print $form->select_company($agf->fk_soc_OPCA,'fksocOPCA','(s.client IN (1,2))',1,1,0,$events);
						print '</td></tr>';

						print '<tr><td>'.$langs->trans("AgfOPCAContact").'</td>';
						print '	<td>';
						if (!empty($agf->fk_soc_OPCA)) {
							$form->select_contacts($agf->fk_soc_OPCA,$agf->fk_socpeople_OPCA,'fksocpeopleOPCA',1);
						}
						else
						{
							print '<select class="flat" id="fksocpeopleOPCA" name="fksocpeopleOPCA">';
							print '<option value="0">'.$langs->trans("AgfDefSocNeed").'</option>';
							print '</select>';
						}
						print '</td></tr>';

						print '<tr><td width="20%">'.$langs->trans("AgfOPCANumClient").'</td>';
						print '<td><input size="30" type="text" class="flat" name="numOPCAsoc" value="'.$agf->num_OPCA_soc.'" /></td></tr>';

						print '<tr><td width="20%">'.$langs->trans("AgfOPCADateDemande").'</td>';
						if ($agf->is_date_ask_OPCA==1) {
							$chckisDtOPCA='checked="checked"';
						}
						print '<td><table class="nobordernopadding"><tr><td>';
						print '<input type="checkbox" class="flat" name="isdateaskOPCA" value="1" '.$chckisDtOPCA.' /></td>';
						print '<td>';
						print $form->select_date($agf->date_ask_OPCA, 'ask_OPCA','','',1,'update',1,1);
						print '</td><td>';
						print $form->textwithpicto('', $langs->trans("AgfDateCheckbox"));
						print '</td></tr></table>';
						print '</td></tr>';

						print '<tr><td width="20%">'.$langs->trans("AgfOPCANumFile").'</td>';
						print '<td><input size="30" type="text" class="flat" name="numOPCAFile" value="'.$agf->num_OPCA_file.'" /></td></tr>';

						print '</table>';
					}
					if (! $show_subrogation)
						print '<div class="info">'.$langs->trans('AgfInfoEditSubrogation').'</div>';

					if (!empty($conf->global->AGF_USE_STAGIAIRE_TYPE))
					{
						print '</td><td valign="top">'.$formAgefodd->select_type_stagiaire($stagiaires->line[$i]->typeid,'stagiaire_type','',1);
					}
					if ($user->rights->agefodd->modifier)
					{
						print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="stag_update" alt="'.$langs->trans("AgfModSave").'" ">';
					}
					print '</td>';
				}
				else
				{
					print '<td width="40%">';
					// info stagiaire
					if (strtolower($stagiaires->line[$i]->nom) == "undefined")
					{
						print $langs->trans("AgfUndefinedStagiaire");
					}
					else
					{
						$trainee_info = '<a href="'.dol_buildpath('/agefodd/trainee/card.php',1).'?id='.$stagiaires->line[$i]->id.'">';
						$trainee_info .= img_object($langs->trans("ShowContact"),"contact").' ';
						$trainee_info .= strtoupper($stagiaires->line[$i]->nom).' '.ucfirst($stagiaires->line[$i]->prenom).'</a>';
						$contact_static= new Contact($db);
						$contact_static->civilite_id = $stagiaires->line[$i]->civilite;
						$trainee_info .= ' ('.$contact_static->getCivilityLabel().')';

						if ($agf->type_session == 1)
						{
							print '<table class="nobordernopadding" width="100%"><tr class="noborder"><td colspan="2">';
							print $trainee_info;
							print '</td></tr>';
								
							$agf->getOpcaForTraineeInSession($stagiaires->line[$i]->socid,$agf->id);
							print '<tr class="noborder"><td  class="noborder" width="45%">'.$langs->trans("AgfSubrocation").'</td>';
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
					print '<td width="30%" style="border-left: 0px;">';
					// Affichage de l'organisme auquel est rattaché le stagiaire
					if ($stagiaires->line[$i]->socid)
					{
						print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$stagiaires->line[$i]->socid.'">';
						print img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($stagiaires->line[$i]->socname,20).'</a>';
					}
					else
					{
						print '&nbsp;';
					}
					if (!empty($conf->global->AGF_USE_STAGIAIRE_TYPE))
					{
						print '</td><td width="20%" style="border-left: 0px;">'.stripslashes($stagiaires->line[$i]->type);
					}
					print '</td><td>';


					if ($user->rights->agefodd->modifier)
					{
						print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" name="stag_edit" alt="'.$langs->trans("AgfModSave").'">';
					}
					print '&nbsp;';
					if ($user->rights->agefodd->creer)
					{
						print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" border="0" name="stag_remove" alt="'.$langs->trans("AgfModSave").'">';
					}
					print '</td>'."\n";
				}
				print '</form>'."\n";
				print '</tr>'."\n";
			}
		}

		// Champs nouveau stagiaire
		if (isset($_POST["newstag"]))
		{
			print '<tr>';
			print '<form name="obj_update_'.($i + 1).'" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
			print '<input type="hidden" name="sessid" value="'.$agf->id.'">'."\n";
			print '<input type="hidden" name="stagerowid" value="'.$stagiaires->line[$i]->stagerowid.'">'."\n";
			print '<td width="20px" align="center">'.($i+1).'</td>';
			print '<td colspan="2" width="500px">';
			print $formAgefodd->select_stagiaire('','stagiaire', 's.rowid NOT IN (SELECT fk_stagiaire FROM '.MAIN_DB_PREFIX.'agefodd_session_stagiaire WHERE fk_session_agefodd='.$id.')',1);

			if (!empty($conf->global->AGF_USE_STAGIAIRE_TYPE))
			{
				print $formAgefodd->select_type_stagiaire($conf->global->AGF_DEFAULT_STAGIAIRE_TYPE,'stagiaire_type');
			}
			if ($user->rights->agefodd->modifier)
			{
				print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="stag_add" alt="'.$langs->trans("AgfModSave").'" ">';
			}
			print '</td>';
			print '</form>';
			print '</tr>'."\n";
		}

		print '</table>';
		if (!isset($_POST["newstag"]))
		{
			print '</div>';
			//print '&nbsp';
			print '<table style="border:0;" width="100%">';
			print '<tr><td align="right">';
			print '<form name="newstag" action="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'"  method="POST">'."\n";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
			print '<input type="hidden" name="action" value="edit">'."\n";
			print '<input type="hidden" name="newstag" value="1">'."\n";
			print '<input type="submit" class="butAction" value="'.$langs->trans("AgfStagiaireAdd").'">';
			print '<a class="butAction" href="../trainee/card.php?action=create&url_back='.urlencode($_SERVER['PHP_SELF'].'?action=edit&id='.$id).'" title="'.$langs->trans('AgfNewParticipantLinkInfo').'">'.$langs->trans('AgfNewParticipant').'</a>';
			if ($user->rights->agefodd->creer && !$agf->type_session > 0)	{
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit_subrogation&id='.$id.'">'.$langs->trans('AgfModifySubrogation').'</a>';
			}
			else {
				if($agf->type_session) $title = ' / '.$langs->trans('AgfAvailableForIntraOnly');
				print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).$title.'">'.$langs->trans('AgfModifySubrogation').'</a>';
			}
			print '</td></tr>';
			print '</form>';
		}

		print '</table>';
		print '</div>';
	}
	else {
		// Affichage en mode "consultation"

		print '<div width=100% align="center" style="margin: 0 0 3px 0;">';
		print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
		print '</div>';

		// Print session card
		$agf->printSessionInfo();

		print '&nbsp';

		/*
		 * Gestion de la subrogation (formulaire d'édition) uniquement pour une session de type intra
		*/
		if(!$agf->type_session > 0)
		{
			if ($action == "edit_subrogation" && $agf->type_session==0)
			{
				print '</div>';

				print_barre_liste($langs->trans("AgfGestSubrocation"),"", "","","","",'',0);
				print '<div class="tabBar">';

				print '<form name="add" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="update_subrogation">';
				print '<input type="hidden" name="id" value="'.$agf->id.'">';
				print '<table class="border" width="100%">';
				print '<tr><td width="20%">'.$langs->trans("AgfSubrocation").'</td>';
				if ($agf->is_OPCA==1) {
					$chckisOPCA='checked="checked"';
				}
				print '<td><input type="checkbox" class="flat" name="isOPCA" value="1" '.$chckisOPCA.'" /></td></tr>';

				print '<tr><td width="20%">'.$langs->trans("AgfOPCAName").'</td>';
				print '	<td>';
				$events=array();
				$events[]=array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php',1), 'htmlname' => 'fksocpeopleOPCA', 'params' => array('add-customer-contact' => 'disabled'));
				print $form->select_company($agf->fk_soc_OPCA,'fksocOPCA','(s.client IN (1,2))',1,1,0,$events);
				print '</td></tr>';

				print '<tr><td width="20%">'.$langs->trans("AgfOPCAContact").'</td>';
				print '	<td>';
				if (!empty($agf->fk_soc_OPCA)) {
					$form->select_contacts($agf->fk_soc_OPCA,$agf->fk_socpeople_OPCA,'fksocpeopleOPCA',1);
				}
				else
				{
					print '<select class="flat" id="fksocpeopleOPCA" name="fksocpeopleOPCA">';
					print '<option value="0">'.$langs->trans("AgfDefSocNeed").'</option>';
					print '</select>';
				}
				print '</td></tr>';

				print '<tr><td width="20%">'.$langs->trans("AgfOPCANumClient").'</td>';
				print '<td><input size="30" type="text" class="flat" name="numOPCAsoc" value="'.$agf->num_OPCA_soc.'" /></td></tr>';

				print '<tr><td width="20%">'.$langs->trans("AgfOPCADateDemande").'</td>';
				if ($agf->is_date_ask_OPCA==1) {
					$chckisDtOPCA='checked="checked"';
				}
				print '<td><table class="nobordernopadding"><tr><td>';
				print '<input type="checkbox" class="flat" name="isdateaskOPCA" value="1" '.$chckisDtOPCA.' /></td>';
				print '<td>';
				print $form->select_date($agf->date_ask_OPCA, 'ask_OPCA','','',1,'update',1,1);
				print '</td><td>';
				print $form->textwithpicto('', $langs->trans("AgfDateCheckbox"));
				print '</td></tr></table>';
				print '</td></tr>';

				print '<tr><td width="20%">'.$langs->trans("AgfOPCANumFile").'</td>';
				print '<td><input size="30" type="text" class="flat" name="numOPCAFile" value="'.$agf->num_OPCA_file.'" /></td></tr>';


				print '<tr><td align="center" colspan=2>';
				print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'"> &nbsp; ';
				print '<input type="submit" name="cancel" class="butActionDelete" value="'.$langs->trans("Cancel").'">';
				print '</td></tr>';

				print '</table></div>';
			}
			else
			{
				/*
				 * Gestion de la subrogation (affichage infos)
				*/
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
		}

		/*
		 * Gestion des stagiaires
		*/

		print '&nbsp';
		print '<table class="border" width="100%">';

		$stagiaires = new Agsession($db);
		$stagiaires->fetch_stagiaire_per_session($agf->id);
		$nbstag = count($stagiaires->line);
		print '<tr><td  width="20%" valign="top" ';
		if ($nbstag < 1)
		{
			print '>'.$langs->trans("AgfParticipants").'</td>';
			print '<td style="text-decoration: blink;">'.$langs->trans("AgfNobody").'</td></tr>';
		}
		else
		{
			print ' rowspan='.($nbstag).'>'.$langs->trans("AgfParticipants");
			if ($nbstag > 1) print ' ('.$nbstag.')';
			print '</td>';

			for ($i=0; $i < $nbstag; $i++)
			{
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
						print '<table class="nobordernopadding" width="100%"><tr class="noborder"><td colspan="2">';
						print $trainee_info;
						print '</td></tr>';

						$agf->getOpcaForTraineeInSession($stagiaires->line[$i]->socid,$agf->id);
						print '<tr class="noborder"><td  class="noborder" width="45%">'.$langs->trans("AgfSubrocation").'</td>';
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
				if ($stagiaires->line[$i]->type) {
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

/*
 * Barre d'actions
*
*/

print '<div class="tabsAction">';

if ($action != 'create' && $action != 'edit' && $action != "edit_subrogation" && (!empty($agf->id)))
{
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$id.'">'.$langs->trans('AgfModifyTrainee').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('AgfModifyTrainee').'</a>';
	}

	if ($user->rights->agefodd->creer && !$agf->type_session > 0)
	{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit_subrogation&id='.$id.'">'.$langs->trans('AgfModifySubrogation').'</a>';
	}
	else
	{
		if($agf->type_session) $title = ' / '.$langs->trans('AgfAvailableForIntraOnly');
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).$title.'">'.$langs->trans('AgfModifySubrogation').'</a>';
	}

}

print '</div>';

llxFooter('');
?>
