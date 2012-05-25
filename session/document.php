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
 * 	\file		/agefodd/session/document.php
 * 	\brief		Page présentant la liste des documents administratif disponibles dans Agefodd
 */

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/session/class/agefodd_session.class.php');
dol_include_once('/agefodd/session/class/agefodd_sessadm.class.php');
dol_include_once('/agefodd/class/agefodd_facture.class.php');
dol_include_once('/agefodd/session/class/agefodd_convention.class.php');
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$action=GETPOST('action','alpha');
$id=GETPOST('id','int');
$socid=GETPOST('socid','int');


$mesg = '';

// lie une facture ou un bon de commande à la session
if($action == 'link_confirm' && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_facture($db);
	$result = $agf->fetch($id, $socid);
	
	// si existe déjà, on met à jour
	if ($agf->id)
	{
		if ($_POST["type"] == 'bc') $agf->comid=$_POST["select"];
		if ($_POST["type"] == 'fac') $agf->facid=$_POST["select"];
		$result2 = $agf->update($user->id);
	}
	// si nouveau, on créé
	else
	{
		if ($_POST["type"] == 'bc')
		{
			$agf->comid=$_POST["select"];
			$agf->facid="";
		}
		$agf->sessid = $id;
		$agf->socid = $socid;
		$agf->datec  =$db->idate(dol_now());;
		$result2 = $agf->create($user->id);
	}

	if ($result2)
	{
		Header( 'Location: '.$_SERVER['PHP_SELF'].'?id='.$id);
		exit;
	}
	else
	{
		dol_syslog("CommonObject::agefodd error=".$agf->error, LOG_ERR);
		$mesg = "Document linked error" . $agf->error;
	}
}

// Casse le lien entre une facture ou un bon de commande et la session
if($action == 'unlink' && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_facture($db);
	$result = $agf->fetch($id, $socid);
	
	// si existe déjà, on met à jour
	if ($agf->id)
	{
		if ($_GET["type"] == 'bc') $agf->comid="";
		if ($_GET["type"] == 'fac') $agf->facid="";
		$result2 = $agf->update($user->id);
	}
	if ($result2)
	{
		Header( 'Location: '.$_SERVER['PHP_SELF'].'?id='.$id);
		exit;
	}
	else
	{
		dol_syslog("CommonObject::agefodd error=".$agf->error, LOG_ERR);
		$mesg = "Document unlink error".$agf->error;

	}

}


/*
 * View
 */

llxHeader();

$form = new Form($db);

dol_htmloutput_mesg($mesg);

/*
 * Action create and refresh pdf document
 */
if (($action == 'create' || $action == 'refresh' ) && $user->rights->agefodd->creer)
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
	
	if (!empty($_GET["cour"])) $file = $_GET["model"].'-'.$_GET["cour"].'_'.$_GET["id"].'_'.$_GET["socid"].'.pdf';
	else $file = $_GET["model"].'_'.$_GET["id"].'_'.$_GET["socid"].'.pdf';
	$result = agf_pdf_create($db, $id, '', $_GET["model"], $outputlangs, $file, $_GET["socid"], $_GET["cour"]);
}

/*
 * Action delete pdf document
 */
if ($action == 'del' && $user->rights->agefodd->creer)
{
	if (!empty($_GET["cour"])) 
	    $file = $conf->agefodd->dir_output.'/'.$_GET["model"].'-'.$_GET["cour"].'_'.$_GET["id"].'_'.$_GET["socid"].'.pdf';
	else 
	    $file = $conf->agefodd->dir_output.'/'.$_GET["model"].'_'.$_GET["id"].'_'.$_GET["socid"].'.pdf';
	if (is_file($file)) unlink($file);
	else
	{
		$error = $file.' : '.$langs->trans("AgfDocDelError");
		dol_syslog("Agefodd::document::del error=".$error, LOG_ERR);
	}
}


// Selection du bon de commande ou de la facture à lier
if (($action == 'link' ) && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_session($db);
	$agf->fetch($id);
	
	$head = session_prepare_head($agf);
	
	dol_fiche_head($head, 'document', $langs->trans("AgfSessionDetail"), 0, 'user');
	
	print '<div width=100% align="center" style="margin: 0 0 3px 0;">'."\n";
	print ebi_level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_adm_level_number(), $langs->trans("AgfAdmLevel"));
	print '</div>'."\n";

	print '<table class="border" width="100%">'."\n";
		
	print '<tr class="liste_titre">'."\n";
	print '<td colspan=3>';
	print  '<a href="#">'.$langs->trans("AgfCommonDocs").'</a></td>'."\n";
	print '</tr>'."\n";
	
	print '<tr class="liste">'."\n";
	
	// creation de la liste de choix
	$agf_liste = new Agefodd_facture($db);
	$result = $agf_liste->fetch_fac_per_soc($_GET["socid"], $_GET["type"]);
	$num = count($agf_liste->line);
	if ($num > 0)
	{
		print '<form name="fact_link" action="document.php?action=link_confirm&id='.$id.'"  method="post">'."\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
		print '<input type="hidden" name="socid" value="'.$_GET["socid"].'">'."\n";		
		print '<input type="hidden" name="type" value="'.$_GET["type"].'">'."\n";		
		
		$var=True;
		$options = '<option value=""></option>'."\n";;
		for ($i = 0; $i < $num; $i++)
		{
			$options .= '<option value="'.$agf_liste->line[$i]->id.'">'.$agf_liste->line[$i]->ref.'</option>'."\n";
		}
		$select = '<select class="flat" name="select">'."\n".$options."\n".'</select>'."\n";
		
		print '<td width="250px">';
		($_GET["type"] == 'bc') ? print $langs->trans("AgfFactureBcSelectList") : print $langs->trans("AgfFactureFacSelectList");
		print '</td>'."\n";
		print '<td>'.$select.'</td>'."\n";
		if ($user->rights->agefodd->modifier)
		{
			print '</td><td><input type="image" src="'.dol_buildpath('/agefodd/img/save.png',1).'" border="0" align="absmiddle" name="bt_save" alt="'.$langs->trans("AgfModSave").'"></td>'."\n";
		}
		print '</form>';
	}
	else
	{
		print '<td colspan=3>';
		($_GET["type"] == 'bc') ? print $langs->trans("AgfFactureBcNoResult") : print $langs->trans("AgfFactureFacNoResult");
		print '</td>';
	}
	print '</tr>'."\n";

	print '</div>'."\n";
	exit;
}


if ($id)
{
	$agf = new Agefodd_session($db);
	$agf->fetch($id);
	
	$result = $agf->fetch_societe_per_session($id);

	if ($result)
	{		
		
		function show_conv($file, $socid,$nom_courrier)
		{
			global $langs, $conf, $db, $id, $form, ${'flag_bc_'.$socid};

			$model = $file;
			$file = $file.'_'.$id.'_'.$socid.'.pdf';
			
			$agf = new Agefodd_convention($db);
			$result = $agf->fetch($id, $socid);

			// Si la convention a déjà été complété (création d'un entrée dans la table)
			if ($agf->id)
			{
				if (is_file($conf->agefodd->dir_output.'/'.$file))
				{
					// afficher
					$legende = $langs->trans("AgfDocOpen");
					#$mess = '<a href="'.$conf->agefodd->dir_output.'/'.$file.'" alt="'.$legende.'" title="'.$legende.'">';
					$mess = '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=agefodd&file='.$file.'" alt="'.$legende.'" title="'.$legende.'">';
					$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/pdf2.png" border="0" align="absmiddle" hspace="2px" ></a>';
	
					// Regenerer
					$legende = $langs->trans("AgfDocRefresh");
					$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&socid='.$socid.'&action=refresh&model='.$model.'&cour='.$nom_courrier.'" alt="'.$legende.'" title="'.$legende.'">';
					$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/refresh.png" border="0" align="absmiddle" hspace="2px" ></a>';
					
					// Supprimer
					$legende = $langs->trans("AgfDocDel");
					$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&socid='.$socid.'&action=del&model='.$model.'&cour='.$nom_courrier.'" alt="'.$legende.'" title="'.$legende.'">';
					$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/editdelete.png" border="0" align="absmiddle" hspace="2px" ></a>';
				}
				else
				{
					// Création de la convention au format PDF
					$legende = $langs->trans("AgfDocCreate");
					$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=create&socid='.$socid.'&model='.$model.'&cour='.$nom_courrier.'" alt="'.$legende.'" title="'.$legende.'">';
					$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
				}
				
				// editer la convention pour modification
				$legende = $langs->trans("AgfDocEdit");
				$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=edit&convid='.$agf->id.'" alt="'.$legende.'" title="'.$legende.'">';
				$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" align="absmiddle" hspace="2px" ></a>';


			}
			else
			{ 
				// Si la convention n'a pas encore été renseignée, il faut le faire maintenant
				$legende = $langs->trans("AgfDocEdit");
				$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=create&socid='.$socid.'&model='.$model.'&cour='.$nom_courrier.'" alt="'.$legende.'" title="'.$legende.'">';
				$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';

			}
			
			if (empty(${'flag_bc_'.$socid})) $mess = $form->textwithpicto('',$langs->trans("AgfFactureFacNoBonHelp"),1,'help');//ebi_help($langs->trans("AgfFactureFacNoBonHelp"));
			
			return $mess;
		}

		function show_doc($file, $socid, $nom_courrier)
		{
			global $langs, $conf, $id, ${'flag_bc_'.$socid}, $form;
			
			$model = $file;
			if(!empty($nom_courrier)) $file = $file.'-'.$nom_courrier.'_'.$id.'_'.$socid.'.pdf';
			else $file = $file.'_'.$id.'_'.$socid.'.pdf';
			
			if (is_file($conf->agefodd->dir_output.'/'.$file))
			{
				// afficher
				$legende = $langs->trans("AgfDocOpen");
				//$mess = '<a href="'.$conf->agefodd->dir_output.'/'.$file.'" alt="'.$legende.'" title="'.$legende.'">';
				$mess = '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=agefodd&file='.$file.'" alt="'.$legende.'" title="'.$legende.'">';
				$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/pdf2.png" border="0" align="absmiddle" hspace="2px" ></a>';

				// Regenerer
				$legende = $langs->trans("AgfDocRefresh");
				$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&socid='.$socid.'&action=refresh&model='.$model.'&cour='.$nom_courrier.'" alt="'.$legende.'" title="'.$legende.'">';
				$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/refresh.png" border="0" align="absmiddle" hspace="2px" ></a>';
				
				// Supprimer
				$legende = $langs->trans("AgfDocDel");
				$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&socid='.$socid.'&action=del&model='.$model.'&cour='.$nom_courrier.'" alt="'.$legende.'" title="'.$legende.'">';
				$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/editdelete.png" border="0" align="absmiddle" hspace="2px" ></a>';

			}
			else
			{
				// Génereration des documents
				if (file_exists(dol_buildpath('/agefodd/core/modules/agefodd/pdf/pdf_'.$model.'.modules.php')))
				{ 
					$legende = $langs->trans("AgfDocCreate");
					$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&action=create&socid='.$socid.'&model='.$model.'&cour='.$nom_courrier.'" alt="'.$legende.'" title="'.$legende.'">';
					$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';
				}
				else
				{
					//$mess = "<font size='-2'>no template</font>";
					$mess = $form->textwithpicto('',$langs->trans("AgfDocNoTemplate"),1,'warning');//img_warning($langs->trans(""));.
				}
			}
			return $mess;
		}
		
		function show_fac($file, $socid, $mdle)
		{
			global $langs, $conf, $db, $id, $form, ${'flag_bc_'.$socid};

			$agf = new Agefodd_facture($db);
			$result = $agf->fetch($id, $socid);
			
			// Gestion des bons de commande (ou brouillon de facture)
			if ($mdle == 'bc')
			{
				if ($agf->comid)
				{
					// Consulter la fiche Dolibarr du BC
					$legende = $langs->trans("AgfFactureSeeBon").' '.$agf->comref;
					$mess.= '<a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$agf->comid.'" alt="'.$legende.'" title="'.$legende.'">';
					$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" align="absmiddle" hspace="2px" ></a>';
					//$mess = '<a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$agf->comid.'" alt="'.$legende.'" title="'.$legende.'">'.$agf->comref.'</a>';
					${'flag_bc_'.$socid} = $agf->comid;

					// Délier le bon de commande
					$legende = $langs->trans("AgfFactureUnselectBon");
					$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?action=unlink&id='.$id.'&type=bc&socid='.$socid.'" alt="'.$legende.'" title="'.$legende.'">';
					//$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/undo.png" border="0" align="absmiddle" hspace="2px" ></a>';
					$mess.= '<img src="'.dol_buildpath('/agefodd/img/unlink.png',1).'" border="0" align="absmiddle" hspace="2px" ></a>';
				}
				else
				{
					$mess = '';

					// Generer le bon de commande
					$legende = $langs->trans("AgfFactureGenererBon");
					$mess = '<a href="'.DOL_URL_ROOT.'/commande/fiche.php?action=create&socid='.$socid.'" alt="'.$legende.'" title="'.$legende.'">';
					$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';

					// Lier un bon de commande existant
					$legende = $langs->trans("AgfFactureSelectBon");
					//$mess.= '<a href="#" alt="'.$legende.'" title="'.$legende.'">';$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/redo.png" border="0" align="absmiddle" hspace="2px" ></a>';
					$mess.= '<a href="'.dol_buildpath('/agefodd/session/document.php',1).'?action=link&id='.$id.'&type=bc&socid='.$socid.'" alt="'.$legende.'" title="'.$legende.'">';$mess.= '<img src="'.dol_buildpath('/agefodd/img/link.png',1).'" border="0" align="absmiddle" hspace="2px" ></a>';
						
					$mess.= "&nbsp;".$form->textwithpicto('',$langs->trans("AgfFactureBonBeforeSelectHelp"),1,'help');//ebi_help($langs->trans("AgfFactureBonBeforeSelectHelp"));
				}
			} 
 			// gestion des factures
			elseif ($mdle == 'fac')
			{
				if ($agf->facid)
				{
					$legende = $langs->trans("AgfFactureSeeFac").' '.$agf->facnumber;
					//$mess = '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$agf->facid.'" alt="'.$legende.'" title="'.$legende.'">'.$agf->facnumber.'</a>';
					$mess = '<a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$agf->facid.'" alt="'.$legende.'" title="'.$legende.'">';
					$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/edit.png" border="0" align="absmiddle" hspace="2px" ></a>';

					// Délier la facture
					$legende = $langs->trans("AgfFactureUnselectFac");
					$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?action=unlink&id='.$id.'&type=fac&socid='.$socid.'" alt="'.$legende.'" title="'.$legende.'">';
					$mess.= '<img src="'.dol_buildpath('/agefodd/img/unlink.png',1).'" border="0" align="absmiddle" hspace="2px" ></a>';

				}
				else
				{
					if (!empty(${'flag_bc_'.$socid}))
					{
						$mess = '';
	
						// Créer la facture
						$legende = $langs->trans("AgfFactureAddFac");
						$mess.= '<a href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&commandeid='.$agf->comid.'&socid='.$socid.'"  alt="'.$legende.'" title="'.$legende.'">';
						$mess.= '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filenew.png" border="0" align="absmiddle" hspace="2px" ></a>';

						// lier une facture existante
						$legende = $langs->trans("AgfFactureSelectFac");
						$mess.= '<a href="'.$_SERVER['PHP_SELF'].'?action=link&id='.$id.'&type=fac&socid='.$socid.'" alt="'.$legende.'" alt="'.$legende.'" title="'.$legende.'">';$mess.= '<img src="'.dol_buildpath('/agefodd/img/link.png',1).'" border="0" align="absmiddle" hspace="2px" ></a>';
					}
					else
					{
						$mess = $form->textwithpicto('',$langs->trans("AgfFactureFacNoBonHelp"),1,'help');//ebi_help($langs->trans("AgfFactureFacNoBonHelp"));
					}
				}
			}
			else
			{
				$mess = 'error';
			}

			return $mess;
		}
		
		function document_line($intitule, $level=2, $mdle, $socid, $nom_courrier='')
		{
			print '<tr style="height:14px">'."\n";
			if ($level == 2) 
			{
				print '<td style="border:0px; width:10px">&nbsp;</td>'."\n";
				print '<td style="border-right:0px;">';
			}
			else print '<td colspan="2" style="border-right:0px;">';
			print $intitule.'</td>'."\n";
			if ( $mdle == 'bc' || $mdle == 'fac')
			{
				print '<td style="border-left:0px;" align="right">'.show_fac($mdle, $socid, $mdle).'</td></tr>'."\n";
			}
			elseif ( $mdle == 'convention')
			{
				print '<td style="border-left:0px; width:200px" align="right">'.show_conv($mdle, $socid,$nom_courrier).'</td></tr>'."\n";
			}
			else
			{
				print '<td style="border-left:0px; width:200px"  align="right">'.show_doc($mdle, $socid, $nom_courrier).'</td></tr>'."\n";
			}
		}
		

		// Affichage en mode "consultation"
		$head = session_prepare_head($agf);
		
		dol_fiche_head($head, 'document', $langs->trans("AgfSessionDetail"), 0, 'user');

		
		/*
		* Confirmation de la suppression
		*/
		if ($action == 'delete')
		{
			$ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$id,$langs->trans("AgfDeleteOps"),$langs->trans("AgfConfirmDeleteOps"),"confirm_delete");
			if ($ret == 'html') print '<br>';
		}

		print '<div width=100% align="center" style="margin: 0 0 3px 0;">'."\n";
		print ebi_level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_adm_level_number(), $langs->trans("AgfAdmLevel"));
		print '</div>'."\n";

		print '<table class="border" width="100%">'."\n";
			
		print '<tr class="liste_titre">'."\n";
		print '<td colspan=3>';
		print $langs->trans("AgfCommonDocs").'</td>'."\n";
		print '</tr>'."\n";

		
		print '<tr><td colspan=3 style="background-color:#d5baa8;">'.$langs->trans("AgfBeforeTraining").'</td></tr>'."\n";
		document_line("Convocation", 2, 'convocation', $agf->line[$i]->socid);
		document_line("Réglement intérieur", 2, 'reglement', $agf->line[$i]->socid);
		document_line("Programme", 2, 'programme', $agf->line[$i]->socid);
		document_line("Fiche pédagogique", 2, 'fiche_pedago', $agf->line[$i]->socid);
		document_line("Conseils pratiques", 2, 'conseils', $agf->line[$i]->socid);

		// Pendant la formation
		print '<tr><td colspan=3 style="background-color:#d5baa8;">'.$langs->trans("AgfDuringTraining").'</td></tr>'."\n";
		document_line("Fiche de présence", 2, "fiche-presence", $agf->line[$i]->socid);
		document_line("Fiche d'évaluation", 2, "fiche-evaluation", $agf->line[$i]->socid);

		print '</table>'."\n";
		print '&nbsp;'."\n";

		$linecount = count($agf->line);

		for ($i=0; $i < $linecount ; $i++)
		{
			$ext = '_'.$id.'_'.$agf->line[$i]->socid.'.pdf';
			
			${'flag_bc_'.$agf->line[$i]->socid} = 0;

			print '<table class="border" width="100%">'."\n";
			
			print '<tr class="liste_titre">'."\n";
			print '<td colspan=3>';
			print  '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$agf->line[$i]->socid.'">'.$agf->line[$i]->socname.'</a></td>'."\n";
			print '</tr>'."\n";

			// Avant la formation
			print '<tr><td colspan=3 style="background-color:#d5baa8;">Avant la formation</td></tr>'."\n";
			document_line("bon de commande", 2, "bc", $agf->line[$i]->socid);
			document_line("Convention de formation", 2, "convention", $agf->line[$i]->socid);
			document_line("Courrier accompagnant l'envoi des conventions de formation", 2, "courrier", $agf->line[$i]->socid,'convention');
			document_line("Courrier accompagnant l'envoi du dossier d'accueil", 2, "courrier", $agf->line[$i]->socid, 'accueil');

// 			// Pendant la formation
 			print '<tr><td colspan=3 style="background-color:#d5baa8;">Pendant la formation</td></tr>'."\n";
 			document_line("Fiche de présence", 2, "fiche-presence", $agf->line[$i]->socid);
 			document_line("Fiche d'évaluation", 2, "fiche-evaluation", $agf->line[$i]->socid);

			// Après la formation
			print '<tr><td colspan=3 style="background-color:#d5baa8;">Après la formation</td></tr>'."\n";
			document_line("Attestations de formation", 2, "attestation", $agf->line[$i]->socid);
			document_line("Facture", 2, "fac", $agf->line[$i]->socid);
			document_line("Courrier accompagnant l'envoi du dossier de clôture", 2, "courrier", $agf->line[$i]->socid, 'cloture');
			//document_line("for test only", 2, "courrier", $agf->line[$i]->socid, "test");
			print '</table>';
			if ($i < $linecount) print '&nbsp;'."\n";
		}	
		print '</div>'."\n";
	}

}

$db->close();

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
