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
 *  \file       	/agefodd/site/card.php $
 *  \brief      	Page fiche site de formation
*  \version		$Id$
*/

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/class/agefodd_place.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/core/class/html.formcompany.class.php');

// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$mesg = '';

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('id','int');
$arch=GETPOST('arch','int');

$url_return=GETPOST('url_return','alpha');

/*
 * Actions delete
*/
if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_place($db);
	$agf->id=$id;
	$result = $agf->remove($user);

	if ($result > 0)
	{
		Header ( "Location: list.php");
		exit;
	}
	else
	{
		dol_syslog("agefodd::site::card error=".$agf->error, LOG_ERR);
		$mesg='<div class="error">'.$langs->trans("AgfDeleteErr").':'.$agf->error.'</div>';
	}
}


/*
 * Actions archive/active
*/
if ($action == 'arch_confirm_delete' && $user->rights->agefodd->creer)
{
	if ($confirm == "yes")
	{
		$agf = new Agefodd_place($db);

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
			dol_syslog("agefodd::site::card error=".$agf->error, LOG_ERR);
			$mesg='<div class="error">'.$agf->error.'</div>';
		}

	}
	else
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
}

/*
 * Action update (fiche site de formation)
*/
if ($action == 'update' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"] && ! $_POST["importadress"])
	{
		$agf = new Agefodd_place($db);

		$result = $agf->fetch($id);

		$agf->ref_interne = GETPOST('ref_interne','alpha');
		$agf->adresse = GETPOST('adresse','alpha');
		$agf->cp = GETPOST('zipcode','alpha');
		$agf->ville = GETPOST('town','alpha');
		$agf->pays_id = GETPOST('country_id','int');
		$agf->tel = GETPOST('phone','alpha');
		$agf->fk_societe = GETPOST('societe','int');
		$agf->notes = GETPOST('notes','alpha');
		$agf->acces_site = GETPOST('acces_site','alpha');
		$agf->note1 = GETPOST('note1','alpha');
		$result = $agf->update($user);

		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd::site::card error=".$agf->error, LOG_ERR);
			$mesg='<div class="error">'.$agf->error.'</div>';
		}

	}
	elseif (! $_POST["cancel"] && $_POST["importadress"])	{

		$agf = new Agefodd_place($db);

		$result = $agf->fetch($id);
		$result = $agf->import_customer_adress($user);

		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$id);
			exit;
		}
		else
		{
			dol_syslog("agefodd::site::card error=".$agf->error, LOG_ERR);
			$mesg='<div class="error">'.$agf->error.'</div>';
		}

	}else {
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
}


/*
 * Action create (fiche site de formation)
*/

if ($action == 'create_confirm' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd_place($db);

		$agf->ref_interne = GETPOST('ref_interne','alpha');
		//$agf->adresse = GETPOST('adresse','alpha');
		//$agf->cp = GETPOST('zipcode','alpha');
		//$agf->ville = GETPOST('town','alpha');
		//$agf->pays = GETPOST('country_id','int');
		//$agf->tel = GETPOST('phone','alpha');
		$agf->fk_societe = GETPOST('societe','int');
		$agf->notes = GETPOST('notes','alpha');
		$agf->acces_site = GETPOST('acces_site','alpha');
		$agf->note1 = GETPOST('note1','alpha');
		$result = $agf->create($user);

		if ($result > 0)
		{
			if($url_return)
				Header ( "Location: ".$url_return);
			else
				Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$result);
			exit;
		}
		else
		{
			dol_syslog("agefodd::site::card error=".$agf->error, LOG_ERR);
			$mesg='<div class="error">'.$agf->error.'</div>';
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
	$formcompany = new FormCompany($db);
	print_fiche_titre($langs->trans("AgfCreatePlace"));

	print '<form name="create" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
	print '<input type="hidden" name="action" value="create_confirm">'."\n";

	print '<input type="hidden" name="url_return" value="'.$url_return.'">'."\n";

	print '<table class="border" width="100%">'."\n";

	print '<tr><td width="20%"><span class="fieldrequired">'.$langs->trans("AgfSessPlaceCode").'</span></td>';
	print '<td><input name="ref_interne" class="flat" size="50" value=""></td></tr>';

	print '<tr><td><span class="fieldrequired">'.$langs->trans("Societe").'</span></td>';
	print '<td>'.$form->select_company('','societe','((s.client IN (1,2)) OR (s.fournisseur=1))',1,1,0).'</td></tr>';
	/*
	 print '<tr><td>'.$langs->trans("Address").'</td>';
	print '<td><input name="adresse" class="flat" size="50" value=""></td></tr>';

	print '<tr><td>'.$langs->trans('CP').'</td><td>';
	print $formcompany->select_ziptown('','zipcode',array('town','selectcountry_id'),6).'</tr>';
	print '<tr></td><td>'.$langs->trans('Ville').'</td><td>';
	print $formcompany->select_ziptown('','town',array('zipcode','selectcountry_id')).'</tr>';

	print '<tr><td>'.$langs->trans("Pays").'</td>';
	print '<td>'.$form->select_country('','country_id').'</td></tr>';


	print '<tr><td>'.$langs->trans("Phone").'</td>';
	print '<td><input name="phone" class="flat" size="50" value=""></td></tr>';*/

	print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td>';
	print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfAccesSite").'</td>';
	print '<td><textarea name="acces_site" rows="3" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfPlaceNote1").'</td>';
	print '<td><textarea name="note1" rows="3" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';
	print '</table>';
	print '</div>';


	print '<table style=noborder align="right">';
	print '<tr><td align="center" colspan=2>';
	print '<input type="submit" name="importadress" class="butAction" value="'.$langs->trans("Save").'"> &nbsp; ';
	print '<input type="submit" name="cancel" class="butActionDelete" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';
	print '</table>';
	print '</form>';

}
else
{
	// Affichage de la fiche "site de formation"
	if ($id)
	{
		$agf = new Agefodd_place($db);
		$result = $agf->fetch($id);

		if ($result)
		{
			$head = site_prepare_head($agf);

			dol_fiche_head($head, 'card', $langs->trans("AgfSessPlace"), 0, 'address');

			// Affichage en mode "édition"
			if ($action == 'edit')
			{
				$formcompany = new FormCompany($db);

				print '<form name="update" action="'.$_SERVER['PHP_SELF'].'" method="post">'."\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
				print '<input type="hidden" name="action" value="update">'."\n";
				print '<input type="hidden" name="id" value="'.$id.'">'."\n";

				print '<table class="border" width="100%">'."\n";
				print '<tr><td width="20%">'.$langs->trans("Id").'</td>';
				print '<td>'.$agf->id.'</td></tr>';

				print '<tr><td>'.$langs->trans("AgfSessPlaceCode").'</td>';
				print '<td><input name="ref_interne" class="flat" size="50" value="'.$agf->ref_interne.'"></td></tr>';

				print '<tr><td>'.$langs->trans("Societe").'</td>';
				print '<td>'.$form->select_company($agf->socid,'societe','((s.client IN (1,2)) OR (s.fournisseur=1))',0,1).'</td></tr>';

				print '<tr><td>'.$langs->trans("Address").'</td>';
				print '<td><input name="adresse" class="flat" size="50" value="'.$agf->adresse.'"></td></tr>';


				print '<tr><td>'.$langs->trans('CP').'</td><td>';
				print $formcompany->select_ziptown($agf->cp,'zipcode',array('town','selectcountry_id'),6).'</tr>';
				print '<tr></td><td>'.$langs->trans('Ville').'</td><td>';
				print $formcompany->select_ziptown($agf->ville,'town',array('zipcode','selectcountry_id')).'</tr>';

				print '<tr><td>'.$langs->trans("Pays").'</td>';
				print '<td>'.$form->select_country($agf->pays,'country_id').'</td></tr>';

				print '<tr><td>'.$langs->trans("Phone").'</td>';
				print '<td><input name="phone" class="flat" size="50" value="'.$agf->tel.'"></td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td>';
				print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;">'.$agf->notes.'</textarea></td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfAccesSite").'</td>';
				print '<td><textarea name="acces_site" rows="3" cols="0" class="flat" style="width:360px;">'.$agf->acces_site.'</textarea></td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfPlaceNote1").'</td>';
				print '<td><textarea name="note1" rows="3" cols="0" class="flat" style="width:360px;">'.$agf->note1.'</textarea></td></tr>';

				print '</table>';
				print '</div>';
				print '<table style=noborder align="right">';
				print '<tr><td align="center" colspan=2>';
				print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'"> &nbsp; ';
				print '<input type="submit" name="importadress" class="butAction" value="'.$langs->trans("AgfImportCustomerAdress").'"> &nbsp; ';
				print '<input type="submit" name="cancel" class="butActionDelete" value="'.$langs->trans("Cancel").'">';
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
					$ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$id,$langs->trans("AgfDeletePlace"),$langs->trans("AgfConfirmDeletePlace"),"confirm_delete",'','',1);
					if ($ret == 'html') print '<br>';
				}
				/*
				 * Confirmation de l'archivage/activation suppression
				*/
				if ($action=='archive' || $action=='active')
				{
					if ($action == 'archive') $value=1;
					if ($action == 'active') $value=0;

					$ret=$form->form_confirm($_SERVER['PHP_SELF']."?arch=".$value."&id=".$id,$langs->trans("AgfFormationArchiveChange"),$langs->trans("AgfConfirmArchiveChange"),"arch_confirm_delete",'','',1);
					if ($ret == 'html') print '<br>';
				}

				print '<table class="border" width="100%">';

				print '<tr><td width="20%">'.$langs->trans("Id").'</td>';
				print '<td>'.$form->showrefnav($agf,'id	','',1,'rowid','id').'</td></tr>';

				print '<tr><td>'.$langs->trans("AgfSessPlaceCode").'</td>';
				print '<td>'.$agf->ref_interne.'</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("Company").'</td><td>';
				if ($agf->socid)
				{
					print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$agf->socid.'">';
					print img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($agf->socname,20).'</a>';
				}
				else
				{
					print '&nbsp;';
				}
				print '</tr>';

				print '<tr><td rowspan=3 valign="top">'.$langs->trans("Address").'</td>';
				print '<td>'.$agf->adresse.'</td></tr>';

				print '<tr>';
				print '<td>'.$agf->cp.' - '.$agf->ville.'</td></tr>';

				print '<tr>';
				print '<td>'.$agf->pays.'</td></tr>';

				print '</td></tr>';

				print '<tr><td>'.$langs->trans("Phone").'</td>';
				print '<td>'.dol_print_phone($agf->tel).'</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfNotes").'</td>';
				print '<td>'.nl2br($agf->notes).'</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfAccesSite").'</td>';
				print '<td>'.nl2br($agf->acces_site).'</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfPlaceNote1").'</td>';
				print '<td>'.nl2br($agf->note1).'</td></tr>';

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

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
