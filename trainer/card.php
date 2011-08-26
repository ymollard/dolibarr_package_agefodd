<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
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

$res=@include("../../../main.inc.php");									// For "custom" directory
if (! $res) $res=@include("../../main.inc.php");						// For root directory
if (! $res) @include("../../../../../../dolibarr/htdocs/main.inc.php");	// Used on dev env only

require_once("./class/agefodd_formateur.class.php");
require_once("../lib/agefodd.lib.php");


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();


$mesg = '';

$db->begin();

/*
 * Actions delete
 */
if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes" && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_teacher($db);
	$result = $agf->remove($_GET["id"]);
	
	if ($result > 0)
	{
		$db->commit();
		Header ( "Location: list.php");
		exit;
	}
	else
	{
		$db->rollback();
		dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
	}
}

/*
 * Actions archive/active
 */
if ($_POST["action"] == 'arch_confirm_delete' && $user->rights->agefodd->creer)
{
	if ($_POST["confirm"] == "yes")
	{
		$agf = new Agefodd_teacher($db);
	
		$result = $agf->fetch($_GET["id"]);
	
		$agf->archive = $_GET["arch"];
		$result = $agf->update($user->id);
	
		if ($result > 0)
		{
		$db->commit();
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$_GET["id"]);
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
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$_GET["id"]);
		exit;
	}
}


/*
 * Action create (fiche formateur: attention, le contact DLB doit déjà exister)
 */

if ($_POST["action"] == 'create' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd_teacher($db);

		$agf->datec = $db->idate(mktime());
		$agf->spid = $_POST["spid"];
		$result = $agf->create($user->id);

		if ($result > 0)
		{
			$db->commit();
			Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$result);
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
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$_POST["id"]);
		exit;
	}
}



/*
 * View
 */

llxHeader();

$html = new Form($db);

$id = $_GET['id'];


/*
 * Action create
 */
if ($_GET["action"] == 'create' && $user->rights->agefodd->creer)
{
	$h=0;
	
	$head[$h][0] = $_SERVER['PHP_SELF']."?id=$agf->id";
	$head[$h][1] = $langs->trans("Card");
	$hselected = $h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("AgfTeacher"), 0, 'user');

	print '<form name="create" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
	print '<input type="hidden" name="action" value="create">'."\n";

	print '<div class="warning">La fiche formateur ne peut être créée qu\'à partir d\'un contact déjà existant dans Dolibarr.';
	print '<br>Si ce contact n\'existe pas, vous devez le créer à partir de la fiche de création d\'un <a href="'.DOL_URL_ROOT.'/contact/fiche.php?action=create">nouveau contact</a>. Sinon, selectionnez le contact dans la liste déroulante ci dessous.</div>';

	print '<table class="border" width="100%">'."\n";

	print '<tr><td>'.$langs->trans("AgfContact").'</td>';
	print '<td>'.ebi_select_contacts("spid").'</td></tr>';
	
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
			if ($_GET["action"] == 'delete')
			{
				$ret=$html->form_confirm("s_teacher_fiche.php?id=".$id,$langs->trans("AgfDeleteTeacher"),$langs->trans("AgfConfirmDeleteTeacher"),"confirm_delete");
				if ($ret == 'html') print '<br>';
			}
			
			/*
			* Confirmation de l'archivage/activation suppression
			*/
			if (isset($_GET["arch"]))
			{
				$ret=$html->form_confirm($_SERVER['PHP_SELF']."?arch=".$_GET["arch"]."&id=".$id,$langs->trans("AgfFormationArchiveChange"),$langs->trans("AgfConfirmArchiveChange"),"arch_confirm_delete");
				if ($ret == 'html') print '<br>';
			}

			print '<table class="border" width="100%">';

			print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
			print '<td>'.$agf->id.'</td></tr>';

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

if ($_GET["action"] != 'create' && $_GET["action"] != 'edit' && $_GET["action"] != 'nfcontact')
{
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$agf->spid.'">'.$langs->trans('AgfModifierFicheContact').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('AgfModifierFicheContact').'</a>';
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
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&arch='.$arch.'&id='.$id.'">'.$button.'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$button.'</a>';
	}

}

print '</div>';

$db->close();

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
