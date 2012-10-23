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
 *  \file       	/agefodd/trainer/card.php
 *  \brief      	Page fiche site de formation
*  \version		$Id$
*/

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/class/agefodd_formateur.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');


$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('id','int');
$arch=GETPOST('arch','int');

// Security check
if (!$user->rights->agefodd->lire) accessforbidden();


$mesg = '';


/*
 * Actions delete
*/
if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_teacher($db);
	$result = $agf->remove($id);

	if ($result > 0)
	{
		Header ( "Location: list.php");
		exit;
	}
	else
	{
		dol_syslog("/agefodd/trainer/card.php::agefodd error=".$agf->error, LOG_ERR);
		$mesg='<div class="error">'.$langs->trans("AgfDeleteFormErr").':'.$agf->error.'</div>';
	}
}

/*
 * Actions archive/active
*/
if ($action == 'arch_confirm_delete' && $user->rights->agefodd->creer && $confirm == "yes")
{
	$agf = new Agefodd_teacher($db);

	$result = $agf->fetch($id);

	$agf->archive = $arch;
	$result = $agf->update($user);

	if ($result > 0)
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
	else
	{
		dol_syslog("/agefodd/trainer/card.php::agefodd error=".$agf->error, LOG_ERR);
		$mesg='<div class="error">'.$langs->trans("AgfDeleteFormErr").':'.$agf->error.'</div>';
	}
}


/*
 * Action create (fiche formateur: attention, le contact DLB doit déjà exister)
*/

if ($action == 'create_confirm_contact' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd_teacher($db);

		$agf->spid = $_POST["spid"];
		$agf->type_trainer = $agf->type_trainer_def[1];
		$result = $agf->create($user);

		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$result);
			exit;
		}
		else
		{
			dol_syslog("/agefodd/trainer/card.php::agefodd error=".$agf->error, LOG_ERR);
			$mesg='<div class="error">'.$langs->trans("AgfDeleteFormErr").':'.$agf->error.'</div>';
		}
	}
	else
	{
		Header ( "Location: list.php");
		exit;
	}
}


/*
 * Action create (fiche formateur: attention, le contact DLB doit déjà exister)
*/

if ($action == 'create_confirm_user' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd_teacher($db);

		$agf->fk_user = $_POST["fk_user"];
		$agf->type_trainer = $agf->type_trainer_def[0];
		$result = $agf->create($user);

		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$result);
			exit;
		}
		else
		{
			dol_syslog("/agefodd/trainer/card.php::agefodd error=".$agf->error, LOG_ERR);
			$mesg='<div class="error">'.$langs->trans("AgfDeleteFormErr").':'.$agf->error.'</div>';
		}
	}
	else
	{
		Header ( "Location: list.php");
		exit;
	}
}


/*
 * View
*/

llxHeader();

$form = new Form($db);

dol_htmloutput_mesg($mesg);
/*
 * Action create
*/
if ($action == 'create' && $user->rights->agefodd->creer)
{
	print_fiche_titre($langs->trans("AgfFormateurAdd"));

	print '<form name="create_contact" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
	print '<input type="hidden" name="action" value="create_confirm_contact">'."\n";

	print '<div class="warning">La fiche formateur peut être créée à partir d\'un contact déjà existant dans Dolibarr.';
	print '<br>Si ce contact n\'existe pas, vous devez le créer à partir de la fiche de création d\'un <a href="'.DOL_URL_ROOT.'/contact/fiche.php?action=create">nouveau contact</a>. Sinon, selectionnez le contact dans la liste déroulante ci dessous.</div>';

	print '<table class="border" width="100%">'."\n";

	print '<tr><td>'.$langs->trans("AgfContact").'</td>';
	print '<td>';

	$agf_static = new Agefodd_teacher($db);
	$agf_static->fetch_all('ASC','s.name, s.firstname','',0);
	$exclude_array = array();
	foreach($agf_static->line as $line)
	{
		$exclude_array[]=$line->fk_socpeople;
	}
	$form->select_contacts(0,'','spid',1,$exclude_array);
	print '</td></tr>';

	print '</table>';


	print '<table style=noborder align="right">';
	print '<tr><td align="center" colspan=2>';
	print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'"> &nbsp; ';
	print '<input type="submit" name="cancel" class="butActionDelete" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';
	print '</table>';
	print '</form>';

	print '<br>';
	print '<br>';
	print '<br>';

	print '<form name="create_user" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
	print '<input type="hidden" name="action" value="create_confirm_user">'."\n";

	print '<div class="warning">La fiche formateur peut être créée à partir d\'un utilisateur de Dolibarr.';
	print '<br>Si ce contact n\'existe pas, vous devez le créer à partir de la fiche de création d\'un <a href="'.DOL_URL_ROOT.'user/fiche.php?action=create">nouvelle utilisateur</a>. Sinon, selectionnez l\'utilisateur dans la liste déroulante ci dessous.</div>';

	print '<table class="border" width="100%">'."\n";

	print '<tr><td>'.$langs->trans("AgfUser").'</td>';
	print '<td>';

	$agf_static = new Agefodd_teacher($db);
	$agf_static->fetch_all('ASC','s.name, s.firstname','',0);
	$exclude_array = array();
	foreach($agf_static->line as $line)
	{
		$exclude_array[]=$line->fk_user;
	}
	$form->select_users('','fk_user',1,$exclude_array);
	print '</td></tr>';

	print '</table>';



	print '<table style=noborder align="right">';
	print '<tr><td align="center" colspan=2>';
	print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'"> &nbsp; ';
	print '<input type="submit" name="cancel" class="butActionDelete" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';
	print '</table>';
	print '</form>';

	print '</div>';
}
else
{
	// Affichage de la fiche "formateur"
	if ($id)
	{
		$agf = new Agefodd_teacher($db);
		$result = $agf->fetch($id);

		if ($result)
		{
			if ($mesg) print $mesg."<br>";
				
			// Affichage en mode "consultation"
				
			$head = trainer_prepare_head($agf);

			dol_fiche_head($head, 'card', $langs->trans("AgfTeacher"), 0, 'user');

			/*
				* Confirmation de la suppression
			*/
			if ($action == 'delete')
			{
				$ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$id,$langs->trans("AgfDeleteTeacher"),$langs->trans("AgfConfirmDeleteTeacher"),"confirm_delete",'','',1);
				if ($ret == 'html') print '<br>';
			}
				
			/*
			 * Confirmation de l'archivage/activation suppression
			*/
			if ($action == 'archive' || $action == 'active')
			{
				if ($action == 'archive') $value=1;
				if ($action == 'active') $value=0;

				$ret=$form->form_confirm($_SERVER['PHP_SELF']."?arch=".$value."&id=".$id,$langs->trans("AgfFormationArchiveChange"),$langs->trans("AgfConfirmArchiveChange"),"arch_confirm_delete",'','',1);
				if ($ret == 'html') print '<br>';
			}

			print '<table class="border" width="100%">';

			print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
			print '<td>'.$form->showrefnav($agf,'id','',1,'rowid','id').'</td></tr>';
				
			print '<tr><td>'.$langs->trans("Name").'</td>';
			print '<td>'.ucfirst(strtolower($agf->civilite)).' '.strtoupper($agf->name).' '.ucfirst(strtolower($agf->firstname)).'</td></tr>';


			print "</table>";

			print '</div>';
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
	if ($agf->type_trainer==$agf->type_trainer_def[1]) {
		if ($user->rights->societe->contact->creer)
		{
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$agf->spid.'">'.$langs->trans('AgfModifierFicheContact').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('AgfModifierFicheContact').'</a>';
		}
	}
	elseif ($agf->type_trainer==$agf->type_trainer_def[0]) {
		if ($user->rights->user->user->creer)
		{
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$agf->fk_user.'">'.$langs->trans('AgfModifierFicheUser').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('AgfModifierFicheUser').'</a>';
		}
	}

	if ($user->rights->agefodd->creer)
	{
		print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
	}

	if ($user->rights->agefodd->modifier)
	{
		if ($agf->archive == 0)
		{
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=archive&id='.$id.'">'.$langs->trans('AgfArchiver').'</a>';
		}
		else
		{
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=active&id='.$id.'">'.$langs->trans('AgfActiver').'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('AgfArchiver').'/'.$langs->trans('AgfActiver').'</a>';
	}

}

print '</div>';

$db->close();

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
