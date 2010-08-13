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
 *  \file       	$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/f_fiche.php $
 *  \brief      	Page fiche d'une operation sur CCA
 *  \version		$Id: f_fiche.php 54 2010-03-30 18:58:28Z ebullier $
 */
require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/agefodd/class/agefodd_formation_catalogue.class.php");
require_once(DOL_DOCUMENT_ROOT."/agefodd/lib/agefodd.lib.php");

$langs->load("@agefodd");

// Security check
if (!$user->rights->agefodd->lire) accessforbidden();


$mesg = '';

$db->begin();


/*
 * Actions delete
 */
if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes" && $user->rights->agefodd->creer)
{
	$agf = new Agefodd($db);
	$result = $agf->remove($_GET["id"]);
	
	if ($result > 0)
	{
		$db->commit();
		Header ( "Location: f_liste.php");
		exit;
	}
	else
	{
		$db->rollback();
		dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
	}

}

//if ($_POST["action"] == 'arch_confirm_delete' && $_POST["confirm"] == "yes" && $user->rights->agefodd->creer)
if ($_POST["action"] == 'arch_confirm_delete' && $user->rights->agefodd->creer)
{
    if ($_POST["confirm"] == "yes")
    {
	$agf = new Agefodd($db);

	$result = $agf->fetch($_GET["id"]);

	$agf->archive = $_GET["archive"];
	$result = $agf->update($user->id);

	if ($result > 0)
	{
	    $db->commit();
	    Header ( "Location: f_fiche.php?id=".$_GET["id"]);
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
	Header ( "Location: f_fiche.php?id=".$_GET["id"]);
	exit;
	}
}


/*
 * Action update (fiche de formation)
 */
if ($_POST["action"] == 'update' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd($db);

		$result = $agf->fetch($_POST["id"]);

		$agf->intitule = $_POST["intitule"];
		$agf->ref_interne = $_POST["ref_interne"];
		$agf->duree = $_POST["duree"];
		$agf->public = $_POST["public"];
		$agf->methode = $_POST["methode"];
		$agf->prerequis = $_POST["prerequis"];
		$agf->programme = $_POST["programme"];
		$result = $agf->update($user->id);

		if ($result)
		{
			$db->commit();
			Header ( "Location: f_fiche.php?id=".$_POST["id"]);
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
		Header ( "Location: f_fiche.php?id=".$_POST["id"]);
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
		$agf = new Agefodd($db);

		$agf->datec = $db->idate(mktime());
		$agf->intitule = $_POST["intitule"];
		$agf->ref_interne = $_POST["ref_interne"];
		$agf->duree = $_POST["duree"];
		$agf->public = $_POST["public"];
		$agf->methode = $_POST["methode"];
		$agf->prerequis = $_POST["prerequis"];
		$agf->programme = $_POST["programme"];
		$result = $agf->create($user->id);

		if ($result > 0)
		{
			$db->commit();
			Header ( "Location: f_fiche.php?id=".$result);
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
		Header ( "Location: f_fiche.php?id=".$_POST["id"]);
		exit;
	}
}


/*
 * Action create (objectif pedagogique)
 */

if ($_POST["action"] == "obj_update" && $user->rights->agefodd->creer)
{
	$agf = new Agefodd($db);
	
	// MAJ d'un objectif pedagogique
	if ($_POST["obj_update_x"])
	{
		$result = $agf->fetch_objpeda($_POST["id"]);

		$agf->intitule = $_POST["intitule"];
		$agf->priorite = $_POST["priorite"];
		$agf->fk_formation_catalogue = $_POST["idforma"];
		$agf->id = $_POST["id"];
		
		$result = $agf->update_objpeda($user->id);
	}
	
	// Suppression d'un objectif pedagogique
	if ($_POST["obj_remove_x"]) $result = $agf->remove_objpeda($_POST["id"]);
	
	// Creation d'un nouvel objectif pedagogique
	if ($_POST["obj_add_x"]) 
	{
	    $agf->intitule = $_POST["intitule"];
	    $agf->priorite = $_POST["priorite"];
	    $agf->fk_formation_catalogue = $_POST["idforma"];
	    
	    $result = $agf->create_objpeda($user->id);
	}

	if ($result > 0)
	{
		$db->commit();
		Header ( "Location: f_fiche.php?action=edit&id=".$_POST["idforma"]);
		exit;
	}
	else
	{
		$db->rollback();
		dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
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
	
	$head[$h][0] = DOL_URL_ROOT."/agefodd/f_fiche.php?id=$agf->id";
	$head[$h][1] = $langs->trans("Card");
	$hselected = $h;
	$h++;

	dol_fiche_head($head, $hselected, $langs->trans("AgfCatalogDetail"), 0, 'label');

	print "<form name='create' action=\"f_fiche.php\" method=\"post\">\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="create">';

	print '<table class="border" width="100%">';

	print '<tr><td width="20%">'.$langs->trans("AgfIntitule").'</td><td>';
	print '<input name="intitule" class="flat" size="50" value=""></td></tr>';

	print '<tr><td width="20%">'.$langs->trans("AgfRefInterne").'</td><td>';
	print '<input name="ref_interne" class="flat" size="50" value=""></td></tr>';

	print '<tr><td width="20%">'.$langs->trans("AgfDuree").'</td><td>';
	print '<input name="duree" class="flat" size="50" value=""></td></tr>';

	print '<tr>';
	print '<td valign="top">'.$langs->trans("AgfPublic").'</td><td>';
	print '<textarea name="public" rows="2" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfMethode").'</td><td>';
	print '<textarea name="methode" rows="2" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfPrerequis").'</td><td>';
	print '<textarea name="prerequis" rows="2" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfProgramme").'</td><td colspan=3>';
	print '<textarea name="programme" rows="6" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';

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
	// Affichage de la fiche "formation"
	if ($id)
	{
		if ($_GET["arch"]) $arch = $_GET["arch"];
		else $arch = 0;
		
		$agf = new Agefodd($db);
		$result = $agf->fetch($id,$arch);

		if ($result)
		{

			if ($mesg) print $mesg."<br>";
			
			// recuperation des objectifs pedagogique de la formation
			$sql = "SELECT";
			$sql.= " o.rowid, o.intitule, o.priorite, o.fk_formation_catalogue, o.tms, o.fk_user";
			$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_formation_objectifs_peda AS o";
			$sql.= " WHERE o.fk_formation_catalogue = ".$agf->id;
			
			$resql = $db->query($sql);
			if ($resql) $num = $db->num_rows($resql);
			
			// Affichage en mode "édition"
			if ($_GET["action"] == 'edit')
			{
				$h=0;
				
				$head[$h][0] = DOL_URL_ROOT."/agefodd/f_fiche.php?id=$agf->id";
				$head[$h][1] = $langs->trans("Card");
				$hselected = $h;
				$h++;

				$head[$h][0] = DOL_URL_ROOT."/agefodd/f_info.php?id=$agf->id";
				$head[$h][1] = $langs->trans("Info");
				$h++;

				dol_fiche_head($head, $hselected, $langs->trans("AgfCatalogDetail"), 0, 'label');

				print "<form name='update' action=\"f_fiche.php\" method=\"post\">\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="update">';
				print '<input type="hidden" name="id" value="'.$id.'">';

				print '<table class="border" width="100%">';

				print "<tr>";
				print '<td width="20%">'.$langs->trans("Ref").'</td><td>';
				print $agf->id;
				print '</td></tr>';

				print '<tr><td width="20%">'.$langs->trans("AgfIntitule").'</td><td>';
				print '<input name="intitule" class="flat" size="50" value="'.stripslashes($agf->intitule).'"></td></tr>';

				print '<tr><td width="20%">'.$langs->trans("AgfRefInterne").'</td><td>';
				print '<input name="ref_interne" class="flat" size="50" value="'.$agf->ref_interne.'"></td></tr>';

				print '<tr><td width="20%">'.$langs->trans("AgfDuree").'</td><td>';
				print '<input name="duree" class="flat" size="50" value="'.$agf->duree.'"></td></tr>';

				print '<tr>';
				print '<td valign="top">'.$langs->trans("AgfPublic").'</td><td>';
				print '<textarea name="public" rows="2" cols="0" class="flat" style="width:360px;">'.stripslashes($agf->public).'</textarea></td></tr>';
				print '</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfMethode").'</td><td>';
				print '<textarea name="methode" rows="2" cols="0" class="flat" style="width:360px;">'.stripslashes($agf->methode).'</textarea></td></tr>';
				print '</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfPrerequis").'</td><td>';
				print '<textarea name="prerequis" rows="2" cols="0" class="flat" style="width:360px;">'.stripslashes($agf->prerequis).'</textarea></td></tr>';
				print '</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfProgramme").'</td><td colspan=3>';
				print '<textarea name="programme" rows="6" cols="0" class="flat" style="width:360px;">'.stripslashes($agf->programme).'</textarea></td></tr>';

				print '</table>';
				print '</div>';
				
				print '<table style=noborder align="right">';
				print '<tr><td align="center" colspan=2>';
				print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'"> &nbsp; ';
				print '<input type="submit" name="cancel" class="butActionDelete" value="'.$langs->trans("Cancel").'">';
				print '</td></tr>';
				
				print '</table>';
				print '</form>';
				
				print_barre_liste($langs->trans("AgfObjPeda"), "", "","","","",'',0);

				
				// Affichage des objectifs pedagogiques
				print '<div class="tabBar">';
				print '<table class="border" width="100%">';
				if ($_GET["objc"]) $num = $num + $_GET["objc"]; 
				print '<tr>';
				if ($num > 0) 
				{
				    print '<td width="40">'.$langs->trans("AgfObjPoids").'</td>';
				    print '<td>'.$langs->trans("AgfObjDesc").'</td>';
				    print '<td><a href="'.$_SERVER['PHP_SELF'].'?action=edit&amp;id='.$agf->id.'&amp;objc='.($_GET["objc"] - 1).'">';
				    print img_edit_remove($langs->trans("AgfNewObjRemove")) . "</a>";
				}
				else
				{
				    print '<td width="400">'.$langs->trans("AgfNoObj").'</td>';
				    print '<td>';
				}
				print '&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?action=edit&amp;id='.$agf->id.'&amp;objc='.($_GET["objc"] + 1).'">';
				if ($user->rights->agefodd->creer)	print img_edit_add($langs->trans("AgfNewObjAdd")) ."</a></td>";
				print '</tr>';
				
				$i = 0;
				while ($i < $num)
				{
				    $objp = $db->fetch_object($resql);
				    print '<form name="obj_update_'.$i.'" action="f_fiche.php" method="post">'."\n";
				    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
				    print '<input type="hidden" name="action" value="obj_update">'."\n";
				    
				    if ( $objp->rowid )
				    {
					print '<input type="hidden" name="id" value="'.$objp->rowid.'">'."\n";
					print '<input type="hidden" name="idforma" value="'.$id.'">'."\n";
					print '<input type="hidden" name="priorite" value="'.$objp->priorite.'">'."\n";
					print '<tr><td align="center">'."\n";
					print $objp->priorite;
					
				    }
				    else
				    {
					print '<input type="hidden" name="idforma" value="'.$id.'">'."\n";
					$priorite = ($i + 1);
					print '<input type="hidden" name="priorite" value="'.$priorite.'">'."\n";
					print '<tr><td align="center">'."\n";
					print $priorite;
				    
				    }
				    print '<td width="400"><input name="intitule" class="flat" size="50" value="'.stripslashes($objp->intitule).'"></td>'."\n";
				    print "<td>";
				    
				    if ( $objp->rowid )
				    {
				    
				    	if ($user->rights->agefodd->modifier)
					//if ($user->rights->agefodd->creer)
					{
					    //print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" name="obj_update" alt="'.$langs->trans("AgfModSave").'">';
					    print '<input type="image" src="'.DOL_URL_ROOT.'/agefodd/img/save.png" border="0" name="obj_update" alt="'.$langs->trans("AgfModSave").'">';
					}
					print '&nbsp;';
					if ($user->rights->agefodd->creer)
					{
					    print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" border="0" name="obj_remove" alt="'.$langs->trans("AgfModSave").'">';
					}
				    }
				    else
				    {
					if ($user->rights->agefodd->creer)
					{
					    //print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" name="obj_add" alt="'.$langs->trans("AgfNew").'">';
					    print '<input type="image" src="'.DOL_URL_ROOT.'/agefodd/img/save.png" border="0" name="obj_add" alt="'.$langs->trans("AgfNew").'">';
					}
				    
				    }

				    print '</td></tr>'."\n";
				    print '</form>'."\n";
				    $i++;
				}
				print '</table>'."\n";
				print '</div>'."\n";
			}
			else
			{
				// Affichage en mode "consultation"
				$h=0;
				
				$head[$h][0] = DOL_URL_ROOT."/agefodd/f_fiche.php?id=$agf->id";
				$head[$h][1] = $langs->trans("Card");
				$hselected = $h;
				$h++;

				$head[$h][0] = DOL_URL_ROOT."/agefodd/f_info.php?id=$agf->id";
				$head[$h][1] = $langs->trans("Info");
				$h++;

				dol_fiche_head($head, $hselected, $langs->trans("AgfCatalogDetail"), 0, 'label');

				/*
				 * Confirmation de la suppression
				 */
				if ($_GET["action"] == 'delete')
				{
					$ret=$html->form_confirm("f_fiche.php?id=".$id,$langs->trans("AgfDeleteOps"),$langs->trans("AgfConfirmDeleteOps"),"confirm_delete");
					if ($ret == 'html') print '<br>';
				}

				if (isset($_GET["archive"]))
				{
					$ret=$html->form_confirm("f_fiche.php?archive=".$_GET["archive"]."&id=".$id,$langs->trans("AgfFormationArchiveChange"),$langs->trans("AgfConfirmArchiveChange"),"arch_confirm_delete");
					if ($ret == 'html') print '<br>';
				}

				print '<table class="border" width="100%">';

				print "<tr>";
				print '<td width="20%">'.$langs->trans("Ref").'</td><td colspan=2>';
				print $agf->id;
				print '</td></tr>';

				print '<tr><td width="20%">'.$langs->trans("AgfIntitule").'</td colspan=2>';
				print '<td colspan=2>'.stripslashes($agf->intitule).'</td></tr>';

				print '<tr><td>'.$langs->trans("AgfRefInterne").'</td><td colspan=2>';
				print $agf->ref_interne.'</td></tr>';

				print '<tr><td>'.$langs->trans("AgfDuree").'</td><td colspan=2>';
				print $agf->duree.'</td></tr>';
				
				print '<tr><td valign="top">'.$langs->trans("AgfPublic").'</td><td colspan=2>';
				print stripslashes(nl2br($agf->public)).'</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfMethode").'</td><td colspan=2>';
				print stripslashes(nl2br($agf->methode)).'</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfPrerequis").'</td><td colspan=2>';
				if (!empty($agf->prerequis)) $prerequis = nl2br($agf->prerequis);
				else $prerequis = $langs->trans("AgfUndefinedPrerequis");
				print stripslashes($prerequis).'</td></tr>';

				print '<script type="text/javascript">'."\n";
				print 'function DivStatus( div_){'."\n";
				print '	var Obj = document.getElementById( div_);'."\n";
				print '	if( Obj.style.display=="none"){'."\n";
				print '		Obj.style.display ="block";'."\n";
				print '	}'."\n";
				print '	else{'."\n";
				print '		Obj.style.display="none";'."\n";
				print '	}'."\n";
				print '}'."\n";
				print '</script>'."\n";
				                  
				print '<tr class="liste_titre"><td valign="top">'.$langs->trans("AgfProgramme").'</td>';
				print '<td align="left" colspan=2>';
				print '<a href="javascript:DivStatus(\'prog\');" title="afficher detail" style="font-size:14px;">+</a></td></tr>';
				if (!empty($agf->programme)) $programme = nl2br($agf->programme);
				else $programme = $langs->trans("AgfUndefinedProg");
				print '<tr><td></td><td><div id="prog" style="display:none;">'.stripslashes($programme).'</div></td></tr>';

				print '</table>';
				print '&nbsp';
				print '<table class="border" width="100%">';
				print '<tr class="liste_titre"><td colspan=3>'.$langs->trans("AgfObjPeda").'</td></tr>';
				
				$i = 0;
				while ($i < $num)
				{
				    $objp = $db->fetch_object($resql);
				
				    print '<tr>';
				    print '<td width="40" align="center">'.$objp->priorite.'</td>';
				    print '<td>'.stripslashes($objp->intitule).'</td>';
				    print "</tr>\n";
				    $i++;
				}
				
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

if ($_GET["action"] != 'create' && $_GET["action"] != 'edit')
{
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butAction" href="f_fiche.php?action=edit&id='.$id.'">'.$langs->trans('Modify').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Modify').'</a>';
	}
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butActionDelete" href="f_fiche.php?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
	}

	if ($agf->archive == 0) 
	{
	    $button_action = $langs->trans('AgfArchiver');
	    if ($user->rights->agefodd->creer)
	    {
		print '<a class="butActionDelete" href="f_fiche.php?archive=1&id='.$id.'">';
		print $button_action.'</a>';
	    }
	    else
	    {
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$button_action.'</a>';
	    
	    }
	}
	else
	{
	    $button_action = $langs->trans('AgfActiver');
	    if ($user->rights->agefodd->creer)
	    {
		    print '<a class="butActionDelete" href="f_fiche.php?archive=0&id='.$id.'">';
		    print $button_action.'</a>';
	    }
	    else
	    {
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$button_action.'</a>';
	    }
	}
}

print '</div>';

$db->close();

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
