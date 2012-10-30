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

/**
 *  \file       	/agefodd/trainee/card.php
 *  \brief      	Page fiche stagiaire
 *  \version		$Id$
*/

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/class/agefodd_stagiaire.class.php');
dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/html.formagefodd.class.php');
dol_include_once('/core/class/html.formcompany.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/contact/class/contact.class.php');

// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$mesg = '';

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('id','int');
$arch=GETPOST('arch','int');
$url_back=GETPOST('url_back','alpha');

/*
 * Actions delete
*/
if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_stagiaire($db);
	$result = $agf->remove($id);

	if ($result > 0)
	{
		Header ( "Location: list.php");
		exit;
	}
	else
	{
		if (strpos($agf->error,'llx_agefodd_session_stagiaire_ibfk_2')) {
			$agf->error=$langs->trans("AgfErrorTraineeInSession");
		}
		else {
			dol_syslog("agefodd::card error=".$agf->error, LOG_ERR);
		}
		$mesg = '<div class="error">'.$agf->error.'</div>';
	}
}



/*
 * Action update (fiche rens stagiaire)
*/
if ($action == 'update' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd_stagiaire($db);

		$result = $agf->fetch($id);

		$agf->nom = GETPOST('nom','alpha');
		$agf->prenom = GETPOST('prenom','alpha');
		$agf->civilite = GETPOST('civilite_id','alpha');
		$agf->socid = GETPOST('societe','int');
		$agf->fonction =GETPOST('fonction','alpha');
		$agf->tel1 = GETPOST('tel1','alpha');
		$agf->tel2 = GETPOST('tel2','alpha');
		$agf->mail = GETPOST('mail','alpha');
		$agf->note = GETPOST('note','alpha');
		$agf->fk_socpeople = GETPOST('fk_socpeople','int');
		$result = $agf->update($user);

		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd::card error=".$agf->error, LOG_ERR);
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
 * Action create (fiche formation)
*/

if ($action == 'create_confirm' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"])
	{
		$error=0;
		$agf = new Agefodd_stagiaire($db);

		if( !GETPOST('nom','alpha') && ! GETPOST('civilite_id','alpha')  && ! GETPOST('prenom','alpha')) {
			$mesg = '<div class="error">'.$langs->trans('AgfNameRequiredForParticipant').'</div>';
			$error++;
		}
		if(!$error) {
			
			$create_thirdparty = GETPOST('create_thirdparty','int');
			$create_contact = GETPOST('create_contact','int');

			$name = GETPOST('nom','alpha');
			$firstname = GETPOST('prenom','alpha');
			$civilite_id = GETPOST('civilite_id','alpha');
			$socid = GETPOST('societe','int');
			$fonction = GETPOST('fonction','alpha');
			$tel1 = GETPOST('tel1','alpha');
			$tel2 = GETPOST('tel2','alpha');
			$mail = GETPOST('mail','alpha');
			$note = GETPOST('note','alpha');
			
			$societe_name = GETPOST('societe_name');
			$address = GETPOST('adresse','alpha');
			$zip = GETPOST('zipcode','alpha');
			$town = GETPOST('town','alpha');
			
			$stagiaire_type = GETPOST('stagiaire_type','int');
			$session_id = GETPOST('session_id','int');
			
			$agf->nom = $name;
			$agf->prenom = $firstname;
			$agf->civilite = $civilite_id;
			$agf->socid = $socid;
			$agf->fonction = $fonction;
			$agf->tel1 = $tel1;
			$agf->tel2 = $tel2;
			$agf->mail = $mail;
			$agf->note = $note;
			
			
			// Création tiers demandé
			if($create_thirdparty > 0) {
				
				$socstatic = new Societe($db);
				
				$socstatic->name = $societe_name;
				$socstatic->tel = $tel1;
				$socstatic->email = $mail;
				$socstatic->address=$address;
				$socstatic->zip=$zip;
				$socstatic->town=$town;
				$socstatic->client=1;
				
				$result = $socstatic->create($user);
				
				if (! $result >= 0)
				{
					$error=$socstatic->error; $errors=$socstatic->errors;
				}
								
				$agf->socid=$socstatic->id;

			}
			
			// Création du contact si demandé
			if($create_contact > 0) {
					
				$contact=new Contact($db);
					
				$contact->civilite_id		= $civilite_id;
				$contact->name				= $name;
				$contact->firstname			= $firstname;
				$contact->address			= $address;
				$contact->zip				= $zip;
				$contact->town				= $$town;
				$contact->state_id      	= $state_id;
				$contact->country_id		= $objectcountry_id;
				$contact->socid				= ($socstatic->id > 0?$socstatic->id:$socid);	// fk_soc
				$contact->status			= 1;
				$contact->email				= $mail;
				$contact->phone_pro			= $tel1;
				$contact->mobile			= $tel2;
				$contact->priv				= 0;
					
				$result=$contact->create($user);
				if (! $result >= 0)
				{
					$error=$contact->error; $errors=$contact->errors;
				}
				$agf->fk_socpeople=$contact->id;
			}
			
			$result = $agf->create($user);
			
			if ($result > 0)
			{
				
				// Inscrire dans la session
				if($session_id > 0) 
				{
					$sessionstat = new Agsession($db);
					$sessionstat->sessid = GETPOST('session_id','int');
					$sessionstat->stagiaire = $agf->id;
					$sessionstat->stagiaire_type = GETPOST('stagiaire_type','int');
					$result = $sessionstat->create_stag_in_session($user);
				}
		
				if ($result > 0)
				{
					$mesg = '<div class="success">'.$langs->trans('SuccessCreateStagInSession').'</div>';
					$url_back = dol_buildpath('/agefodd/session/subscribers.php',1).'?id='.$session_id;
				}
				else
				{
					dol_syslog("agefodd:trainee:card error=".$agf->error, LOG_ERR);
					$mesg = '<div class="error">'.$agf->error.'</div>';
				}
				if(strlen($url_back) > 0) {
					Header ( "Location: ".$url_back);
				}
				else {
					Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$agf->id);
				}
				exit;
					
			}
			else
			{
				dol_syslog("agefodd::card error=".$agf->error, LOG_ERR);
				$mesg = '<div class="error">'.$agf->error.'</div>';

			}
		}
		$action='create';

	}
	else
	{
		Header ( "Location: list.php");
		exit;
	}
}

if ($action == 'nfcontact_confirm' && $user->rights->agefodd->creer)
{
	// traitement de l'import d'un contact
	$contact = new Contact($db);
	$result = $contact->fetch($_POST["contact"]);

	if ($result > 0)
	{
		$agf = new Agefodd_stagiaire($db);

		$agf->nom = $contact->name;
		$agf->prenom = $contact->firstname;
		$agf->civilite = $contact->civilite_id;
		$agf->socid = $contact->socid;
		$agf->fonction = $contact->poste;
		$agf->tel1 = $contact->phone_pro;
		$agf->tel2 = $contact->phone_mobile;
		$agf->mail = $contact->email;
		$agf->note = $contact->note;
		$agf->fk_socpeople = $contact->id;

		$result2 = $agf->create($user);

		if ($result2 > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$agf->id);
			exit;
		}
		else
		{
			dol_syslog("agefodd::card error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
			$action='nfcontact';
		}

	}
}


/*
 * View
*/
$title = ($action == 'nfcontact' || $action == 'create' ? $langs->trans("AgfMenuActStagiaireNew") : $langs->trans("AgfStagiaireDetail") );
llxHeader('',$title);

$form = new Form($db);
$formcompany = new FormCompany($db);
$formAgefodd = new FormAgefodd($db);

dol_htmloutput_mesg($mesg);

if ($action == 'nfcontact' && !isset($_GET["ph"])&& $user->rights->agefodd->creer)
{
	print_fiche_titre($langs->trans("AgfMenuActStagiaireNew"));

	print '<form name="update" action="'.$_SERVER['PHP_SELF'].'" method="post">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
	print '<input type="hidden" name="action" value="nfcontact_confirm">'."\n";
	print '<input type="hidden" name="id" value="'.$id.'">'."\n";
	if($url_back)
		print '<input type="hidden" name="url_back" value="'.$url_back.'">'."\n";
	print '<table class="border" width="100%">';

	print '<tr><td width="20%">'. $langs->trans("AgfContactImportAsStagiaire").'</td>';
	print '<td>';

	$agf_static = new Agefodd_stagiaire($db);
	$agf_static->fetch_all('DESC','s.rowid','',0);
	$exclude_array = array();
	if (is_array($agf_static->line) && count($agf_static) > 0)
	{
		foreach($agf_static->line as $line)
		{
			$exclude_array[]=$line->fk_socpeople;
		}
	}
	$form->select_contacts(0,'','contact',1,$exclude_array);
	print '</td></tr>';

	print '</table>';

	print '<table style=noborder align="right">';
	print '<tr><td align="center" colspan=2>';
	print '<input type="submit" class="butAction" value="'.$langs->trans("AgfImport").'">';
	print '</td></tr>';
	print '</table>';
	print '</div>';
}


/*
 * Action create
*/
if ($action == 'create' && $user->rights->agefodd->creer)
{
	print_fiche_titre($langs->trans("AgfMenuActStagiaireNew"));

	print '<form name="create" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="create_confirm">';
	if($url_back)
		print '<input type="hidden" name="url_back" value="'.$url_back.'">'."\n";

	print '<table class="border" width="100%">';

	print '<tr class="liste_titre"><td colspan="4"><strong>'.$langs->trans("ThirdParty").'</strong></td>';
	
	print '<tr><td valign="top">'.$langs->trans("Company").'</td><td colspan="3">';
	print $form->select_company('','societe','(s.client IN (1,2))',1,1);
	print '</td></tr>';
	
	
	print '<tr><td colspan="">'.$langs->trans('CreateANewThirPartyFromTraineeForm');
	print img_picto($langs->trans("CreateANewThirPartyFromTraineeFormInfo"),'help');
	print '</td>';
	print '<td colspan="3">';
	print '<input type="radio" id="create_thirdparty_confirm" name="create_thirdparty" value="1" '.(GETPOST('create_thirdparty','int')?'checked="checked"':'').'/> <label for="create_thirdparty_confirm">'.$langs->trans('Yes').'</label>';
	print '<input type="radio" id="create_thirdparty_cancel" name="create_thirdparty" '.(!GETPOST('create_thirdparty','int')?'checked="checked"':'').' value="-1"/> <label for="create_thirdparty_cancel">'.$langs->trans('no').'</label>';
	print '</td>';
	print '	</tr>';
	
	print '<tr><td>'.$langs->trans("ThirdPartyName").'</td>';
	print '<td colspan="3"><input name="societe_name" class="flat" size="50" value=""></td></tr>';
	
	// Address
	print '<tr><td valign="top">'.$langs->trans('Address').'</td><td colspan="3"><textarea name="adresse" cols="40" rows="3" wrap="soft">';
	print $object->address;
	print '</textarea></td></tr>';
	
	// Zip / Town
	print '<tr><td>'.$langs->trans('Zip').'</td><td>';
	print $formcompany->select_ziptown($object->zip,'zipcode',array('town','selectcountry_id','departement_id'),6);
	print '</td><td>'.$langs->trans('Town').'</td><td>';
	print $formcompany->select_ziptown($object->town,'town',array('zipcode','selectcountry_id','departement_id'));
	print '</td></tr>';
	
	
	// Infos participant
	print '<tr class="liste_titre"><td colspan="4"><strong>'.$langs->trans("Participant").'</strong></td>';
	
	print '<tr><td><span class="fieldrequired">'.$langs->trans("AgfCivilite").'</span></td>';
	print '<td colspan="3">'.$formcompany->select_civility().'</td>';
	print '</tr>';
	
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Lastname").'</span></td>';
	print '<td colspan="3"><input name="nom" class="flat" size="50" value=""></td></tr>';
	
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Firstname").'</span></td>';
	print '<td colspan="3"><input name="prenom" class="flat" size="50" value=""></td></tr>';
	
	print '<tr><td colspan="">'.$langs->trans('CreateANewContactFromTraineeForm');
	print img_picto($langs->trans("CreateANewContactFromTraineeFormInfo"),'help');
	print '</td>';
	print '<td colspan="3">';
	print '<input type="radio" id="create_contact_confirm" name="create_contact" value="1" '.(GETPOST('create_thirdparty','int')?'checked="checked"':'').'/> <label for="create_contact_confirm">'.$langs->trans('Yes').'</label>';
	print '<input type="radio" id="create_contact_cancel" name="create_contact" '.(!GETPOST('create_contact','int')?'checked="checked"':'').' value="-1"/> <label for="create_contact_cancel">'.$langs->trans('no').'</label>';
	print '</td>';
	print '	</tr>';

	print '<tr><td>'.$langs->trans("AgfFonction").'</td>';
	print '<td colspan="3"><input name="fonction" class="flat" size="50" value=""></td></tr>';

	print '<tr><td>'.$langs->trans("Phone").'</td>';
	print '<td colspan="3"><input name="tel1" class="flat" size="50" value=""></td></tr>';

	print '<tr><td>'.$langs->trans("Mobile").'</td>';
	print '<td colspan="3"><input name="tel2" class="flat" size="50" value=""></td></tr>';

	print '<tr><td>'.$langs->trans("Mail").'</td>';
	print '<td colspan="3"><input name="mail" class="flat" size="50" value=""></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td>';
	print '<td colspan="3"><textarea name="note" rows="3" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';
	
	print '<tr class="liste_titre"><td colspan="4"><strong>'.$langs->trans("AgfSessionToRegister").'</strong></td>';
	
	// Session
	if (empty($sortorder)) $sortorder="ASC";
	if (empty($sortfield)) $sortfield="s.dated";
	if (empty($arch)) $arch = 0;
		
	if ($page == -1) { $page = 0 ; }
		
	$limit = $conf->global->AGF_NUM_LIST;
	$agf = new Agsession($db);
		
	$resql = $agf->fetch_all($sortorder, $sortfield, $limit, $offset, $arch, $filter);
	$sessions = array();
	foreach ($agf->line as $line)
	{
		$sessions[$line->rowid] = $line->ref_interne.' - '.$line->intitule.' - '.dol_print_date($line->dated,'daytext');
	}
		
	print '<tr class="agelfoddline">';
	print '<td>'.$langs->trans('SelectAgefoddSession').'</td>';
	print '<td colspan="3">';
	print $form->selectarray('session_id',$sessions,GETPOST('session_id'),1);
	print '</td>';
	print '</tr>';
		
	if (!empty($conf->global->AGF_USE_STAGIAIRE_TYPE))
	{
		// Public stagiaire
		print '<tr class="agelfoddline"><td>'.$langs->trans("AgfPublic").'</td><td colspan="3">';
		print $formAgefodd->select_type_stagiaire(GETPOST('stagiaire_type','int'),'stagiaire_type','',1);
		print '</td></tr>';
	}
	

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
	// Affichage de la fiche "stagiaire"
	if ($id)
	{
		$agf = new Agefodd_stagiaire($db);
		$result = $agf->fetch($id);

		if ($result)
		{
			$head = trainee_prepare_head($agf);

			dol_fiche_head($head, 'card', $langs->trans("AgfStagiaireDetail"), 0, 'user');

			// Affichage en mode "édition"
			if ($action == 'edit')
			{
				print '<form name="update" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="update">';

				print '<input type="hidden" name="id" value="'.$id.'">';

				print '<table class="border" width="100%">';
				print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
				print '<td>'.$agf->id.'</td></tr>';

				//if contact trainee from contact then display contact inforamtion
				if (empty($agf->fk_socpeople))
				{
					print '<tr><td>'.$langs->trans("Lastname").'</td>';
					print '<td><input name="nom" class="flat" size="50" value="'.strtoupper($agf->nom).'"></td></tr>';

					print '<tr><td>'.$langs->trans("Firstname").'</td>';
					print '<td><input name="prenom" class="flat" size="50" value="'.ucfirst($agf->prenom).'"></td></tr>';

					print '<tr><td>'.$langs->trans("AgfCivilite").'</td>';

					print '<td>'.$formcompany->select_civility($agf->civilite).'</td>';
					print '</tr>';

					print '<tr><td valign="top">'.$langs->trans("Company").'</td><td>';

					print $form->select_company($agf->socid,'societe','(s.client IN (1,2))',1,1);

					print '</td></tr>';

					print '<tr><td>'.$langs->trans("AgfFonction").'</td>';
					print '<td><input name="fonction" class="flat" size="50" value="'.$agf->fonction.'"></td></tr>';

					print '<tr><td>'.$langs->trans("Phone").'</td>';
					print '<td><input name="tel1" class="flat" size="50" value="'.$agf->tel1.'"></td></tr>';

					print '<tr><td>'.$langs->trans("Mobile").'</td>';
					print '<td><input name="tel2" class="flat" size="50" value="'.$agf->tel2.'"></td></tr>';

					print '<tr><td>'.$langs->trans("Mail").'</td>';
					print '<td><input name="mail" class="flat" size="50" value="'.$agf->mail.'"></td></tr>';
				}
				else
				{
					print '<input type="hidden" name="fk_socpeople" value="'.$agf->fk_socpeople.'">';
					print '<tr><td>'.$langs->trans("Lastname").'</td>';
					print '<td><a href="'.dol_buildpath('/contact/fiche.php',1).'?id='.$agf->fk_socpeople.'">'.strtoupper($agf->nom).'</a></td></tr>';
					print '<input type="hidden" name="nom" value="'.$agf->nom.'">';

					print '<tr><td>'.$langs->trans("Firstname").'</td>';
					print '<td>'.ucfirst($agf->prenom).'</td></tr>';
					print '<input type="hidden" name="prenom" value="'.$agf->prenom.'">';

					print '<tr><td>'.$langs->trans("AgfCivilite").'</td>';

					$contact_static= new Contact($db);
					$contact_static->civilite_id = $agf->civilite;

					print '<td>'.$contact_static->getCivilityLabel().'</td></tr>';
					print '<input type="hidden" name="civilite_id" value="'.$agf->civilite.'">';

					print '<tr><td valign="top">'.$langs->trans("Company").'</td><td>';
					if ($agf->socid)
					{
						print '<a href="'.dol_buildpath('/comm/fiche.php',1).'?socid='.$agf->socid.'">';
						print '<input type="hidden" name="societe" value="'.$agf->socid.'">';
						print img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($agf->socname,20).'</a>';
					}
					else
					{
						print '&nbsp;';
						print '<input type="hidden" name="societe" value="">';
					}
					print '</td></tr>';

					print '<tr><td>'.$langs->trans("AgfFonction").'</td>';
					print '<td>'.$agf->fonction.'</td></tr>';
					print '<input type="hidden" name="fonction" value="'.$agf->fonction.'">';

					print '<tr><td>'.$langs->trans("Phone").'</td>';
					print '<td>'.dol_print_phone($agf->tel1).'</td></tr>';
					print '<input type="hidden" name="tel1" value="'.$agf->tel1.'">';

					print '<tr><td>'.$langs->trans("Mobile").'</td>';
					print '<td>'.dol_print_phone($agf->tel2).'</td></tr>';
					print '<input type="hidden" name="tel2" value="'.$agf->tel1.'">';

					print '<tr><td>'.$langs->trans("Mail").'</td>';
					print '<td>'.dol_print_email($agf->mail, $agf->id, $agf->socid,'AC_EMAIL',25).'</td></tr>';
					print '<input type="hidden" name="mail" value="'.$agf->mail.'">';

				}

				print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td>';
				if (!empty($agf->note)) $notes = nl2br($agf->note);
				else $notes =  $langs->trans("AgfUndefinedNote");
				print '<td><textarea name="note" rows="3" cols="0" class="flat" style="width:360px;">'.stripslashes($agf->note).'</textarea></td></tr>';


				print '</table>';
				print '</div>';
				print '<table style=noborder align="right">';
				print '<tr><td align="center" colspan=2>';
				print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'"> &nbsp; ';
				print '<input type="submit" name="cancel" class="butActionDelete" value="'.$langs->trans("Cancel").'">';
				if (!empty($agf->fk_socpeople))
				{
					print '<a class="butAction" href="'.dol_buildpath('/contact/fiche.php',1).'?id='.$agf->fk_socpeople.'">'.$langs->trans('AgfModifierFicheContact').'</a>';
				}
				print '</td></tr>';
				print '</table>';
				print '</form>';

				print '</div>'."\n";
			}
			else
			{
				// Affichage en mode "consultation"
				/*
				* Confirmation de la suppression
				*/
				if ($action == 'delete')
				{
					$ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$id,$langs->trans("AgfDeleteOps"),$langs->trans("AgfConfirmDeleteTrainee"),"confirm_delete",'','',1);
					if ($ret == 'html') print '<br>';
				}

				print '<table class="border" width="100%">';

				print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
				print '<td>'.$form->showrefnav($agf,'id	','',1,'rowid','id').'</td></tr>';

				if (!empty($agf->fk_socpeople))
				{
					print '<tr><td>'.$langs->trans("Lastname").'</td>';
					print '<td><a href="'.dol_buildpath('/contact/fiche.php',1).'?id='.$agf->fk_socpeople.'">'.strtoupper($agf->nom).'</a></td></tr>';
				}
				else
				{
					print '<tr><td>'.$langs->trans("Lastname").'</td>';
					print '<td>'.strtoupper($agf->nom).'</td></tr>';
				}

				print '<tr><td>'.$langs->trans("Firstname").'</td>';
				print '<td>'.ucfirst($agf->prenom).'</td></tr>';

				print '<tr><td>'.$langs->trans("AgfCivilite").'</td>';

				$contact_static= new Contact($db);
				$contact_static->civilite_id = $agf->civilite;

				print '<td>'.$contact_static->getCivilityLabel().'</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("Company").'</td><td>';
				if ($agf->socid)
				{
					print '<a href="'.dol_buildpath('/comm/fiche.php',1).'?socid='.$agf->socid.'">';
					print img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($agf->socname,20).'</a>';
				}
				else
				{
					print '&nbsp;';
				}
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("AgfFonction").'</td>';
				print '<td>'.$agf->fonction.'</td></tr>';

				print '<tr><td>'.$langs->trans("Phone").'</td>';
				print '<td>'.dol_print_phone($agf->tel1).'</td></tr>';

				print '<tr><td>'.$langs->trans("Mobile").'</td>';
				print '<td>'.dol_print_phone($agf->tel2).'</td></tr>';

				print '<tr><td>'.$langs->trans("Mail").'</td>';
				print '<td>'.dol_print_email($agf->mail, $agf->id, $agf->socid,'AC_EMAIL',25).'</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td>';
				if (!empty($agf->note)) $notes = nl2br($agf->note);
				else $notes =  $langs->trans("AgfUndefinedNote");
				print '<td>'.stripslashes($notes).'</td></tr>';

				print "</table>";

				print '</div>';
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

if ($action != 'create' && $action != 'edit' && $action != 'nfcontact')
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
		print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
	}
}

print '</div>';

$db->close();

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
