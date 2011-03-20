<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009	Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
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
 *  \file       	$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/u_fiche.php $
 *  \brief      	Page fiche stagiaire
 *  \version		$Id$
 */
require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/agefodd/class/agefodd_stagiaire.class.php");
require_once(DOL_DOCUMENT_ROOT."/agefodd/lib/agefodd.lib.php");


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();


$mesg = '';

$db->begin();

/*
 * Actions delete
 */
if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes" && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_stagiaire($db);
	$result = $agf->remove($_GET["id"]);
	
	if ($result > 0)
	{
		$db->commit();
		Header ( "Location: u_liste.php");
		exit;
	}
	else
	{
		$db->rollback();
		dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
	}
}



/*
 * Action update (fiche rens stagiaire)
 */
if ($_POST["action"] == 'update' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd_stagiaire($db);

		$result = $agf->fetch($_POST["id"]);

		$agf->nom = $_POST["nom"];
		$agf->prenom = $_POST["prenom"];
		$agf->civilite = $_POST["civilite"];
		$agf->socid = $_POST["societe"];
		$agf->fonction = $_POST["fonction"];
		$agf->tel1 = $_POST["tel1"];
		$agf->tel2 = $_POST["tel2"];
		$agf->mail = $_POST["mail"];
		$agf->note = $_POST["note"];
		$result = $agf->update($user->id);

		if ($result > 0)
		{
			$db->commit();
			Header ( "Location: u_fiche.php?id=".$_POST["id"]);
			exit;
		}
		else
		{
			$db->rollback();
			dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
		}

	}
	else
	{
		Header ( "Location: u_fiche.php?id=".$_POST["id"]);
		exit;
	}
}


/*
 * Action create (fiche formation)
 */

if ($_POST["action"] == 'create' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd_stagiaire($db);

		$agf->nom = $_POST["nom"];
		$agf->prenom = $_POST["prenom"];
		$agf->civilite = $_POST["civilite"];
		$agf->socid = $_POST["societe"];
		$agf->fonction = $_POST["fonction"];
		$agf->datec = $db->idate(mktime());
		$agf->tel1 = $_POST["tel1"];
		$agf->tel2 = $_POST["tel2"];
		$agf->mail = $_POST["mail"];
		$agf->note = $_POST["note"];
		$result = $agf->create($user->id);

		if ($result > 0)
		{
			$db->commit();
			Header ( "Location: u_fiche.php?action=edit&id=".$result);
			exit;
		}
		else
		{
			$db->rollback();
			dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
		}

	}
	else
	{
		Header ( "Location: u_fiche.php?id=".$_POST["id"]);
		exit;
	}
}

if ($_GET["action"] == 'nfcontact' && $_GET["ph"] == 2 && $user->rights->agefodd->creer)
{
	// traitement de l'import d'un contact

	include_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");

	$contact = new Contact($db);
	$result = $contact->fetch($_POST["contact"]);
	
	if ($result > 0)
	{
		$agf = new Agefodd_stagiaire($db);

		$agf->nom = $contact->name;
		$agf->prenom = $contact->firstname;
		$agf->civilite = $contact->civilite;
		$agf->socid = $contact->socid;
		$agf->fonction = $contact->poste;
		$agf->datec = $db->idate(mktime());
		$agf->tel1 = $contact->phone_pro;
		$agf->tel2 = $contact->phone_mobile;
		$agf->mail = $contact->email;
		$agf->note = $contact->note;

		$result2 = $agf->create($user->id);
		
		if ($result2 > 0)
		{
			$db->commit();
			Header ( "Location: u_fiche.php?id=".$agf->id."&action=edit");
			exit;
		}
	}
}


/*
 * View
 */

llxHeader();

$html = new Form($db);

$id = $_GET['id'];


if ($_GET["action"] == 'nfcontact' && !isset($_GET["ph"])&& $user->rights->agefodd->creer)
{
	// Affichage du formulaire d'import de contact
	$h=0;
	
	$head[$h][0] = DOL_URL_ROOT."/agefodd/u_fiche.php";
	$head[$h][1] = $langs->trans("Card");
	$hselected = $h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("AgfStagiaireDetail"), 0, 'user');

	//print '<div class="error">&nbsp;Cette fonction n\'est pas encore implémentée.</div>';

	print '<form name="update" action="u_fiche.php?action=nfcontact&ph=2&id=" method="post">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
	print '<input type="hidden" name="action" value="update">'."\n";
	print '<input type="hidden" name="id" value="'.$id.'">'."\n";
	print $langs->trans("AgfContactImportAsStagiaire").'&nbsp;';
	print ebi_select_contacts("contact").'<br />';
	print '<input type="submit" class="butAction" value="'.$langs->trans("AgfImport").'"> &nbsp; ';
	print '</div>';
}


/*
 * Action create
 */
if ($_GET["action"] == 'create' && $user->rights->agefodd->creer)
{
	$h=0;
	
	$head[$h][0] = DOL_URL_ROOT."/agefodd/u_fiche.php?id=$agf->id";
	$head[$h][1] = $langs->trans("Card");
	$hselected = $h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("AgfStagiaireDetail"), 0, 'user');

	print "<form name='create' action=\"u_fiche.php\" method=\"post\">\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="create">';

	print '<table class="border" width="100%">';

	print '<tr><td>'.$langs->trans("Lastname").'</td>';
	print '<td><input name="nom" class="flat" size="50" value=""></td></tr>';

	print '<tr><td>'.$langs->trans("Firstname").'</td>';
	print '<td><input name="prenom" class="flat" size="50" value=""></td></tr>';

	print '<tr><td>'.$langs->trans("AgfCivilite").'</td>';

	// Chargement de la liste des civilités dans $options
	$sql = "SELECT c.rowid, c.code, c.civilite";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_civilite as c";
	$sql.= " WHERE active = 1";
	$sql.= " ORDER BY c.civilite";
	
	$result2 = $db->query($sql);
	if ($result2)
	{
	    $var=True;
	    $num = $db->num_rows($result2);
	    
	    $i = 0;
	    $options = '';
	    
	    while ($i < $num)
	    {
		$obj = $db->fetch_object($result2);
		$options .= '<option value="'.$obj->rowid.'">'.$obj->civilite.'</option>'."\n";
		$i++;
	    }
	    $db->free($result2);
	}
	print '<td><select class="flat" name="civilite">'."\n".$options."\n".'</select></td>';
	print '</tr>';
	
	print '<tr><td valign="top">'.$langs->trans("Company").'</td><td>';
	
	// Chargement de la liste des sociétés dans $options
	$sql = "SELECT so.rowid, so.nom";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as so";
	$sql.= " WHERE so.fournisseur = 0";
	$sql.= " ORDER BY so.nom";
	
	$result3 = $db->query($sql);
	if ($result3)
	{
	    $var=True;
	    $num = $db->num_rows($result3);
	    $i = 0;
	    $options = '<option value=""></option>'."\n";
	    while ($i < $num)
	    {
		$obj = $db->fetch_object($result3);
		$options .= '<option value="'.$obj->rowid.'">'.$obj->nom.'</option>'."\n";
		$i++;
	    }
	    $db->free($result3);
	}
	
	print '<select class="flat" name="societe">'."\n".$options."\n".'</select>';
	
	print '</td></tr>';
	
	print '<tr><td>'.$langs->trans("AgfFonction").'</td>';
	print '<td><input name="fonction" class="flat" size="50" value=""></td></tr>';
	
	print '<tr><td>'.$langs->trans("Phone").'</td>';
	print '<td><input name="tel1" class="flat" size="50" value=""></td></tr>';
	
	print '<tr><td>'.$langs->trans("Mobile").'</td>';
	print '<td><input name="tel2" class="flat" size="50" value=""></td></tr>';
	
	print '<tr><td>'.$langs->trans("Mail").'</td>';
	print '<td><input name="mail" class="flat" size="50" value=""></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfNote").'</td>';
	print '<td><textarea name="note" rows="3" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';

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

			if ($mesg) print $mesg."<br>";
			
			
			// Affichage en mode "édition"
			if ($_GET["action"] == 'edit')
			{
				$h=0;
				
				$head[$h][0] = DOL_URL_ROOT."/agefodd/u_fiche.php?id=$agf->id";
				$head[$h][1] = $langs->trans("Card");
				$hselected = $h;
				$h++;

				$head[$h][0] = DOL_URL_ROOT."/agefodd/u_info.php?id=$agf->id";
				$head[$h][1] = $langs->trans("Info");
				$h++;

				dol_fiche_head($head, $hselected, $langs->trans("AgfStagiaireDetail"), 0, 'user');

				print "<form name='update' action=\"u_fiche.php\" method=\"post\">\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="update">';
				print '<input type="hidden" name="id" value="'.$id.'">';

				print '<table class="border" width="100%">';
				print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
				print '<td>'.$agf->id.'</td></tr>';

				print '<tr><td>'.$langs->trans("Lastname").'</td>';
				print '<td><input name="nom" class="flat" size="50" value="'.strtoupper($agf->nom).'"></td></tr>';

				print '<tr><td>'.$langs->trans("Firstname").'</td>';
				print '<td><input name="prenom" class="flat" size="50" value="'.ucfirst($agf->prenom).'"></td></tr>';

				print '<tr><td>'.$langs->trans("AgfCivilite").'</td>';
				
				print '<td>'.ebi_select_civilite($agf->civilite_id).'</td>';
				print '</tr>';
				
				print '<tr><td valign="top">'.$langs->trans("Company").'</td><td>';
				
				// Chargement de la liste des sociétés dans $options
				$sql = "SELECT so.rowid, so.nom";
				$sql.= " FROM ".MAIN_DB_PREFIX."societe as so";
				$sql.= " WHERE so.fournisseur = 0";
				$sql.= " ORDER BY so.nom";
				
				$result3 = $db->query($sql);
				if ($result3)
				{
				    $var=True;
				    $num = $db->num_rows($result3);
				    $i = 0;
				    $options = '<option value=""></option>'."\n";
				    while ($i < $num)
				    {
					$obj = $db->fetch_object($result3);
					if ($obj->rowid == $agf->socid) $selected = ' selected="true"';
					else $selected = '';
					$options .= '<option value="'.$obj->rowid.'"'.$selected.'>'.$obj->nom.'</option>'."\n";
					$i++;
				    }
				    $db->free($result);
				    print '<select class="flat" name="societe">'."\n".$options."\n".'</select>';
				}
				print '</td></tr>';
				
				print '<tr><td>'.$langs->trans("AgfFonction").'</td>';
				print '<td><input name="fonction" class="flat" size="50" value="'.$agf->fonction.'"></td></tr>';
				
				print '<tr><td>'.$langs->trans("Phone").'</td>';
				print '<td><input name="tel1" class="flat" size="50" value="'.$agf->tel1.'"></td></tr>';
				
				print '<tr><td>'.$langs->trans("Mobile").'</td>';
				print '<td><input name="tel2" class="flat" size="50" value="'.$agf->tel2.'"></td></tr>';
				
				print '<tr><td>'.$langs->trans("Mail").'</td>';
				print '<td><input name="mail" class="flat" size="50" value="'.$agf->mail.'"></td></tr>';

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
				print '</td></tr>';
				print '</table>';
				print '</form>';
					
				print '</div>'."\n";
			}
			else
			{
				// Affichage en mode "consultation"
				$h=0;
				
				$head[$h][0] = DOL_URL_ROOT."/agefodd/u_fiche.php?id=$agf->id";
				$head[$h][1] = $langs->trans("Card");
				$hselected = $h;
				$h++;

				$head[$h][0] = DOL_URL_ROOT."/agefodd/u_info.php?id=$agf->id";
				$head[$h][1] = $langs->trans("Info");
				$h++;

				dol_fiche_head($head, $hselected, $langs->trans("AgfStagiaireDetail"), 0, 'user');

				/*
				 * Confirmation de la suppression
				 */
				if ($_GET["action"] == 'delete')
				{
					$ret=$html->form_confirm("u_fiche.php?id=".$id,$langs->trans("AgfDeleteOps"),$langs->trans("AgfConfirmDeleteOps"),"confirm_delete");
					if ($ret == 'html') print '<br>';
				}

				print '<table class="border" width="100%">';

				print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
				print '<td>'.$agf->id.'</td></tr>';

				print '<tr><td>'.$langs->trans("Lastname").'</td>';
				print '<td>'.strtoupper($agf->nom).'</td></tr>';

				print '<tr><td>'.$langs->trans("Firstname").'</td>';
				print '<td>'.ucfirst($agf->prenom).'</td></tr>';

				print '<tr><td>'.$langs->trans("AgfCivilite").'</td>';
				print '<td>'.$agf->civilite_code.'</td></tr>';
				
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
				print '</td></tr>';
				
				print '<tr><td>'.$langs->trans("Phone").'</td>';
				print '<td>'.dol_print_phone($agf->tel1).'</td></tr>';
				
				print '<tr><td>'.$langs->trans("AgfFonction").'</td>';
				print '<td>'.$agf->fonction.'</td></tr>';
				
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

if ($_GET["action"] != 'create' && $_GET["action"] != 'edit' && $_GET["action"] != 'nfcontact')
{
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butAction" href="u_fiche.php?action=edit&id='.$id.'">'.$langs->trans('Modify').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Modify').'</a>';
	}
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butActionDelete" href="u_fiche.php?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
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
