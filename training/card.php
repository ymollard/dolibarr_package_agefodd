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
 *  \file       	/agefodd/training/card.php
 *  \brief      	Page fiche d'une operation sur CCA
*  \version		$Id$
*/

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory
if (! $res) die("Include of main fails");

dol_include_once('/agefodd/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');

// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$mesg = '';

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('id','int');
$arch=GETPOST('arch','int');

/*
 * Actions delete
*/
if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	$agf = new Agefodd($db);
	$result = $agf->remove($id);

	if ($result > 0)
	{
		Header ( "Location: list.php");
		exit;
	}
	else
	{
		dol_syslog("Agefodd:training:card error=".$agf->error, LOG_ERR);
		$mesg = '<div class="error">'.$agf->error.'</div>';
	}

}

if ($action == 'arch_confirm_delete' && $confirm == "yes" && $user->rights->agefodd->creer)
{
	$agf = new Agefodd($db);

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
		dol_syslog("Agefodd:training:card error=".$agf->error, LOG_ERR);
		$mesg = '<div class="error">'.$agf->error.'</div>';
	}
}

/*
 * Action update (fiche de formation)
*/
if ($action == 'update' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd($db);

		$result = $agf->fetch($id);

		$agf->intitule = GETPOST('intitule','alpha');
		$agf->ref_obj = GETPOST('ref','alpha');
		$agf->ref_interne = GETPOST('ref_interne','alpha');
		$agf->duree = GETPOST('duree','int');
		$agf->public = GETPOST('public','alpha');
		$agf->methode = GETPOST('methode','alpha');
		$agf->note1 = GETPOST('note1','alpha');
		$agf->note2 = GETPOST('note2','alpha');
		$agf->prerequis = GETPOST('prerequis','alpha');
		$agf->but = GETPOST('but','alpha');
		$agf->programme = GETPOST('programme');
		$result = $agf->update($user);

		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
		else
		{
			dol_syslog("Agefodd:training:card error=".$agf->error, LOG_ERR);
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
		$agf = new Agefodd($db);
		
		$agf->intitule = GETPOST('intitule','alpha');
		$agf->ref_obj = GETPOST('ref','alpha');
		$agf->ref_interne = GETPOST('ref_interne','alpha');
		$agf->duree = GETPOST('duree','int');
		$agf->public = GETPOST('public','alpha');
		$agf->methode = GETPOST('methode','alpha');
		$agf->note1 = GETPOST('note1','alpha');
		$agf->note2 = GETPOST('note2','alpha');
		$agf->prerequis = GETPOST('prerequis','alpha');
		$agf->but = GETPOST('but','alpha');
		$agf->programme = GETPOST('programme');
		$result = $agf->create($user);

		if ($result > 0)
		{
			Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$result);
			exit;
		}
		else
		{
			dol_syslog("Agefodd:training:card error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}

	}
	else
	{
		Header ("Location: list.php");
		exit;
	}
}


/*
 * Action create (objectif pedagogique)
*/

if ($action == "obj_update" && $user->rights->agefodd->creer)
{
	$agf = new Agefodd($db);

	$idforma = GETPOST('idforma','int');

	// MAJ d'un objectif pedagogique
	if (GETPOST('obj_update_x'))
	{
		$result = $agf->fetch_objpeda($id);

		$agf->intitule = GETPOST('intitule','alpha');
		$agf->priorite = GETPOST('priorite','alpha');
		$agf->fk_formation_catalogue = $idforma;
		$agf->id = $id;

		$result = $agf->update_objpeda($user);
	}

	// Suppression d'un objectif pedagogique
	if (GETPOST("obj_remove_x"))
	{
		$result = $agf->remove_objpeda($id);

		if ($result < 0)
		{
			dol_syslog("Agefodd:training:card error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	}

	// Creation d'un nouvel objectif pedagogique
	if (GETPOST("obj_add_x"))
	{
		$agf->intitule = GETPOST('intitule','alpha');
		$agf->priorite = GETPOST('priorite','alpha');
		$agf->fk_formation_catalogue = $idforma;

		$result = $agf->create_objpeda($user);
	}

	if ($result > 0)
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?action=edit&id=".$idforma);
		exit;
	}
	else
	{
		dol_syslog("Agefodd:training:card error=".$agf->error, LOG_ERR);
		$mesg = '<div class="error">'.$agf->error.'</div>';
	}
}

/*
 * Action generate fiche pédagogique
*/
if ($action == 'fichepeda' && $user->rights->agefodd->creer)
{
	// Define output language
	$outputlangs = $langs;
	$newlang=GETPOST('lang_id','alpha');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	$model='fiche_pedago';
	$file=$model.'_'.$id.'.pdf';

	$result = agf_pdf_create($db, $id, '', $model, $outputlangs, $file, 0);

	if ($result > 0)
	{
		Header ( "Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
	else
	{
		dol_syslog("Agefodd:training:card error=".$agf->error, LOG_ERR);
		$mesg = '<div class="error">'.$agf->error.'</div>';
	}
}




/*
 * View
*/
$title = ($action == 'create' ? $langs->trans("AgfMenuCatNew") : $langs->trans("AgfCatalogDetail"));
llxHeader('',$title);

$form = new Form($db);

dol_htmloutput_mesg($mesg);

/*
 * Action create
*/
if ($action == 'create' && $user->rights->agefodd->creer)
{
	print_fiche_titre($langs->trans("AgfMenuCatNew"));

	print '<form name="create" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="create_confirm">';

	print '<table class="border" width="100%">';

	print '<tr><td width="20%"><span class="fieldrequired">'.$langs->trans("AgfIntitule").'</span></td><td>';
	print '<input name="intitule" class="flat" size="50" value=""></td></tr>';

	$agf = new Agefodd($db);

	$defaultref='';
	$obj = empty($conf->global->AGF_ADDON)?'mod_agefodd_simple':$conf->global->AGF_ADDON;
	$path_rel=dol_buildpath('/agefodd/core/modules/agefodd/'.$conf->global->AGF_ADDON.'.php');
	if (! empty($conf->global->AGF_ADDON) && is_readable($path_rel))
	{
		dol_include_once('/agefodd/core/modules/agefodd/'.$conf->global->AGF_ADDON.'.php');
		$modAgefodd = new $obj;
		$defaultref = $modAgefodd->getNextValue($soc,$agf);
	}

	if (is_numeric($defaultref) && $defaultref <= 0) $defaultref='';
	$defaultref = GETPOST('ref','alpha')?GETPOST('ref'):$defaultref;

	print '<tr><td width="20%"><span class="fieldrequired">'.$langs->trans("Ref").'</span></td><td>';
	print '<input name="ref" class="flat" size="50" value="'.$defaultref.'"></td></tr>';

	print '<tr><td width="20%"><span>'.$langs->trans("AgfRefInterne").'</span></td><td>';
	print '<input name="ref_interne" class="flat" size="50" value="'.GETPOST('ref_interne','alpha').'"></td></tr>';

	print '<tr><td width="20%" class="fieldrequired">'.$langs->trans("AgfDuree").'</td><td>';
	print '<input name="duree" class="flat" size="50" value="'.GETPOST('duree','int').'"></td></tr>';

	print '<tr>';
	print '<td valign="top">'.$langs->trans("AgfPublic").'</td><td>';
	print '<textarea name="public" rows="2" cols="0" class="flat" style="width:360px;">'.GETPOST('public','alpha').'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfMethode").'</td><td>';
	print '<textarea name="methode" rows="2" cols="0" class="flat" style="width:360px;">'.GETPOST('methode','alpha').'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfDocNeeded").'</td><td>';
	print '<textarea name="note1" rows="2" cols="0" class="flat" style="width:360px;">'.GETPOST('note1','alpha').'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfEquiNeeded").'</td><td>';
	print '<textarea name="note2" rows="2" cols="0" class="flat" style="width:360px;">'.GETPOST('note2','alpha').'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfPrerequis").'</td><td>';
	print '<textarea name="prerequis" rows="2" cols="0" class="flat" style="width:360px;">'.GETPOST('prerequis','alpha').'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfBut").'</td><td>';
	print '<textarea name="but" rows="2" cols="0" class="flat" style="width:360px;">'.GETPOST('but','alpha').'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfProgramme").'</td><td colspan=3>';
	print '<textarea name="programme" rows="6" cols="0" class="flat" style="width:360px;">'.GETPOST('programme','alpha').'</textarea></td></tr>';

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
	if (!empty($id))
	{
		if (empty($arch)) $arch = 0;

		$agf = new Agefodd($db);
		$result = $agf->fetch($id);

		$head = training_prepare_head($agf);

		dol_fiche_head($head, 'card', $langs->trans("AgfCatalogDetail"), 0, 'label');

		if ($result)
		{

			$agf_peda=new Agefodd($db);
			$result_peda = $agf_peda->fetch_objpeda_per_formation($id);

			// Affichage en mode "édition"
			if ($action == 'edit')
			{
				print '<form name="update" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="update">';
				print '<input type="hidden" name="id" value="'.$id.'">';

				print '<table class="border" width="100%">';

				print "<tr>";
				print '<td width="20%">'.$langs->trans("Id").'</td><td>';
				print $agf->id;
				print '</td></tr>';

				print '<tr><td width="20%" class="fieldrequired">'.$langs->trans("AgfIntitule").'</td><td>';
				print '<input name="intitule" class="flat" size="50" value="'.stripslashes($agf->intitule).'"></td></tr>';

				print '<tr><td width="20%" class="fieldrequired">'.$langs->trans("Ref").'</td><td>';
				print '<input name="ref" class="flat" size="50" value="'.$agf->ref_obj.'"></td></tr>';

				print '<tr><td width="20%">'.$langs->trans("AgfRefInterne").'</td><td>';
				print '<input name="ref_interne" class="flat" size="50" value="'.$agf->ref_interne.'"></td></tr>';

				print '<tr><td width="20%" class="fieldrequired">'.$langs->trans("AgfDuree").'</td><td>';
				print '<input name="duree" class="flat" size="50" value="'.$agf->duree.'"></td></tr>';

				print '<tr>';
				print '<td valign="top">'.$langs->trans("AgfPublic").'</td><td>';
				print '<textarea name="public" rows="2" cols="0" class="flat" style="width:360px;">'.stripslashes($agf->public).'</textarea></td></tr>';
				print '</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfMethode").'</td><td>';
				print '<textarea name="methode" rows="2" cols="0" class="flat" style="width:360px;">'.stripslashes($agf->methode).'</textarea></td></tr>';
				print '</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfDocNeeded").'</td><td>';
				print '<textarea name="note1" rows="2" cols="0" class="flat" style="width:360px;">'.stripslashes($agf->note1).'</textarea></td></tr>';
				print '</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfEquiNeeded").'</td><td>';
				print '<textarea name="note2" rows="2" cols="0" class="flat" style="width:360px;">'.stripslashes($agf->note2).'</textarea></td></tr>';
				print '</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfPrerequis").'</td><td>';
				print '<textarea name="prerequis" rows="2" cols="0" class="flat" style="width:360px;">'.stripslashes($agf->prerequis).'</textarea></td></tr>';
				print '</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfBut").'</td><td>';
				print '<textarea name="but" rows="2" cols="0" class="flat" style="width:360px;">'.stripslashes($agf->but).'</textarea></td></tr>';
				print '</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfProgramme").'</td><td colspan=3>';
				print '<textarea name="programme" rows="6" cols="7" class="flat" style="width:560px;">'.stripslashes($agf->programme).'</textarea></td></tr>';

				print '</table>';
				print '</div>';

				print '<table style=noborder align="right">';
				print '<tr><td align="center" colspan=2>';
				print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'"> &nbsp; ';
				print '<input type="submit" name="cancel" class="butActionDelete" value="'.$langs->trans("Cancel").'">';
				print '</td></tr>';

				print '</table>';
				print '</form>';

				// Affichage des objectifs pedagogiques
				print_barre_liste($langs->trans("AgfObjPeda"), "", "","","","",'',0);

				print '<div class="tabBar">';
				print '<table class="border" width="100%">';
				print '<tr>';
				if ($result_peda > 0)
				{
					print '<td width="40">'.$langs->trans("AgfObjPoids").'</td>';
					print '<td>'.$langs->trans("AgfObjDesc").'</td>';
					print '<td>';
				}
				elseif (empty($_GET["objc"]))
				{
					print '<td width="400">'.$langs->trans("AgfNoObj").'</td>';
					print '<td>';
				}
				print '&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?action=edit&amp;id='.$agf->id.'&amp;objc=1">';
				if ($user->rights->agefodd->creer)	print img_edit_add($langs->trans("AgfNewObjAdd")) ."</a></td>";
				print '</tr>';

				$i = 0;
				foreach ($agf_peda->line as $line)
				{
					print '<form name="obj_update_'.$line->id.'" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
					print '<input type="hidden" name="action" value="obj_update">'."\n";

					print '<input type="hidden" name="id" value="'.$line->id.'">'."\n";
					print '<input type="hidden" name="idforma" value="'.$id.'">'."\n";
					print '<input type="hidden" name="priorite" value="'.$line->priorite.'">'."\n";
					print '<tr><td align="center">'."\n";
					print $line->priorite;


					print '<td width="400"><input name="intitule" class="flat" size="50" value="'.stripslashes($line->intitule).'"></td>'."\n";
					print "<td>";

					if ( $line->id )
					{
						if ($user->rights->agefodd->modifier)
						{
							print '<input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" name="obj_update" alt="'.$langs->trans("AgfModSave").'">';
						}
						print '&nbsp;';
						if ($user->rights->agefodd->creer)
						{
							print '<input type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" border="0" name="obj_remove" alt="'.$langs->trans("AgfModSave").'">';
						}
					}

					print '</td></tr>'."\n";
					print '</form>'."\n";
					$i++;
				}

				//New Objectif peda line
				if ($_GET["objc"])
				{
					print '<form name="obj_add" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
					print '<input type="hidden" name="action" value="obj_update">'."\n";
					print '<input type="hidden" name="idforma" value="'.$id.'">'."\n";
					$priorite = ($i + 1);
					print '<input type="hidden" name="priorite" value="'.$priorite.'">'."\n";
					print '<tr><td align="center">'."\n";
					print $priorite;
					print '<td width="400"><input name="intitule" class="flat" size="50" value=""></td>'."\n";
					print "<td>";
					if ($user->rights->agefodd->creer)
					{
						print '<input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" name="obj_add" alt="'.$langs->trans("AgfNewObjAdd").'">';
					}
					print '</td></tr>'."\n";
					print '</form>'."\n";
				}

				print '</table>'."\n";
				print '</div>'."\n";
			}
			else
			{
				/*
				 * Affichage
				*/

				//Confirmation de la suppression
				if ($action == 'delete')
				{
					$ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$id,$langs->trans("AgfDeleteOps"),$langs->trans("AgfConfirmDeleteOps"),"confirm_delete",'','',1);
					if ($ret == 'html') print '<br>';
				}

				//Confirmation Archivage!
				if ($action == 'archive' || $action == 'active')
				{
					if ($action == 'archive') $value=1;
					if ($action == 'active') $value=0;

					$ret=$form->form_confirm($_SERVER['PHP_SELF']."?arch=".$value."&id=".$id,$langs->trans("AgfFormationArchiveChange"),$langs->trans("AgfConfirmArchiveChange"),"arch_confirm_delete",'','',1);
					if ($ret == 'html') print '<br>';
				}

				print '<table class="border" width="100%">';

				print "<tr>";
				print '<td width="20%">'.$langs->trans("Id").'</td><td colspan=2>';
				print $form->showrefnav($agf,'id','',1,'rowid','id');
				print '</td></tr>';

				print '<tr><td width="20%">'.$langs->trans("AgfIntitule").'</td>';
				print '<td colspan=2>'.stripslashes($agf->intitule).'</td></tr>';

				print '<tr><td>'.$langs->trans("Ref").'</td><td colspan=2>';
				print $agf->ref_obj.'</td></tr>';

				print '<tr><td>'.$langs->trans("AgfRefInterne").'</td><td colspan=2>';
				print $agf->ref_interne.'</td></tr>';

				print '<tr><td>'.$langs->trans("AgfDuree").'</td><td colspan=2>';
				print $agf->duree.'</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfPublic").'</td><td colspan=2>';
				print stripslashes(nl2br($agf->public)).'</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfMethode").'</td><td colspan=2>';
				print stripslashes(nl2br($agf->methode)).'</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfDocNeeded").'</td><td colspan=2>';
				print stripslashes(nl2br($agf->note1)).'</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfEquiNeeded").'</td><td colspan=2>';
				print stripslashes(nl2br($agf->note2)).'</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfPrerequis").'</td><td colspan=2>';
				if (!empty($agf->prerequis)) $prerequis = nl2br($agf->prerequis);
				else $prerequis = $langs->trans("AgfUndefinedPrerequis");
				print stripslashes($prerequis).'</td></tr>';

				print '<tr><td valign="top">'.$langs->trans("AgfBut").'</td><td colspan=2>';
				if (!empty($agf->but)) $but = nl2br($agf->but);
				else $but = $langs->trans("AgfUndefinedBut");
				print stripslashes($but).'</td></tr>';

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
				foreach ($agf_peda->line as $line)
				{

					print '<tr>';
					print '<td width="40" align="center">'.$line->priorite.'</td>';
					print '<td>'.stripslashes($line->intitule).'</td>';
					print "</tr>\n";
					$i++;
				}

				print "</table>";


				if (is_file($conf->agefodd->dir_output.'/fiche_pedago_'.$id.'.pdf'))
				{
					print '&nbsp';
					print '<table class="border" width="100%">';
					print '<tr class="liste_titre"><td colspan=3>'.$langs->trans("AgfLinkedDocuments").'</td></tr>';
					// afficher
					$legende = $langs->trans("AgfDocOpen");
					print '<tr><td width="200" align="center">Fiche pedagogique : </td><td> ';
					print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=agefodd&file=fiche_pedago_'.$id.'.pdf" alt="'.$legende.'" title="'.$legende.'">';
					print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/pdf2.png" border="0" align="absmiddle" hspace="2px" ></a>';
					print '</td></tr></table>';
				}

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

	if ($agf->archive == 0)
	{
		$button_action = $langs->trans('AgfArchiver');
		if ($user->rights->agefodd->creer)
		{
			print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=archive&id='.$id.'">';
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
			print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=active&id='.$id.'">';
			print $button_action.'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$button_action.'</a>';
		}
	}

	if ($user->rights->agefodd->creer)
	{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=fichepeda&id='.$id.'">'.$langs->trans('AgfPrintFichePedago').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('AgfPrintFichePedago').'</a>';
	}


}

print '</div>';

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
