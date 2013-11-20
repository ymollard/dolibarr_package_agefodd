<?php
/** Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
* Copyright (C) 2012-2013       Florian Henry   <florian.henry@open-concept.pro>
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
 *	\file       agefodd/session/document.php
 *	\ingroup    agefodd
 *	\brief      list of document
*/

error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory
if (! $res) die("Include of main fails");

require_once('../class/agsession.class.php');
require_once('../class/agefodd_sessadm.class.php');
require_once('../class/agefodd_session_element.class.php');
require_once('../class/agefodd_convention.class.php');
require_once('../core/modules/agefodd/modules_agefodd.php');
require_once('../class/html.formagefodd.class.php');
require_once('../lib/agefodd.lib.php');
require_once('../lib/agefodd_document.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');


$langs->load('propal');

// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$action=GETPOST('action','alpha');
$id=GETPOST('id','int');
$socid=GETPOST('socid','int');
$confirm=GETPOST('confirm','alpha');

$type_link = GETPOST('type','alpha');
$idelement= GETPOST('idelement','int');

// Link invoice or order to session/customer
if($action == 'link_confirm' && $user->rights->agefodd->creer)
{
	$agf = new Agefodd_session_element($db);
	$agf->fk_element=GETPOST('select','int');
	$agf->fk_session_agefodd=$id;
	$agf->fk_soc=$socid;

	if ($type_link == 'bc') $agf->element_type='order';
	if ($type_link == 'fac') $agf->element_type='invoice';
	if ($type_link == 'prop') $agf->element_type='propal';
	

	$result = $agf->create($user);


	if ($result<0)
	{
		setEventMessage($agf->error,'errors');
	}
}

// Unlink propal/order/invoice with the session
if($action == 'unlink_confirm' && $confirm=='yes' && $user->rights->agefodd->creer)
{	
	$agf = new Agefodd_session_element($db);
	$result = $agf->fetch($idelement);
		
	$deleteobject=GETPOST('deleteobject','int');
	if (!empty($deleteobject)) {
		if ($type_link == 'bc') {
			$obj_link=new Commande($db);
			$obj_link->id=$agf->fk_element;
			$resultdel=$obj_link->delete($user);
		}
		if ($type_link == 'fac') {
			$obj_link=new Facture($db);
			$obj_link->id=$agf->fk_element;
			$resultdel=$obj_link->delete();
			
		}
		if ($type_link == 'prop') {
			$obj_link=new Propal($db);
			$obj_link->id=$agf->fk_element;
			$resultdel=$obj_link->delete($user);
		}
		
		if ($resultdel<O) {
			setEventMessage($obj_link->error,'errors');
		}
	}
	
	
	// If exists we update
	if ($agf->id)
	{
		$result2 = $agf->delete($user);
	}
	if ($result2 > 0)
	{
		Header( 'Location: '.$_SERVER['PHP_SELF'].'?id='.$id);
		exit;
	}
	else
	{
		setEventMessage($agf->error,'errors');
	}

}

/*
 * Action create and refresh pdf document
*/
if (($action == 'create' || $action == 'refresh' ) && $user->rights->agefodd->creer)
{
	$cour=GETPOST('cour','alpha');
	$model=GETPOST('model','alpha');
	$idform=GETPOST('idform','alpha');
	

	$idtypeelement=GETPOST('idtypelement','alpha');
	if (!empty($idtypeelement)) {
		$idtypeelement_array=explode(':',$idtypeelement);
	}

	// Define output language
	$outputlangs = $langs;
	$newlang=GETPOST('lang_id','alpha');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	$id_tmp= $id;
	if (!empty($cour)) $file = $model.'-'.$cour.'_'.$id.'_'.$socid.'.pdf';
	elseif(!empty($socid)) $file = $model.'_'.$id.'_'.$socid.'.pdf';
	elseif ($model=='fiche_pedago') {
		$file=$model.'_'.$idform.'.pdf';
		$id_tmp=$idform;
	}
	else $file = $model.'_'.$id.'.pdf';
	$result = agf_pdf_create($db, $id_tmp, '', $model, $outputlangs, $file, $socid, $cour);
}

//Confirm create order
if (($action == 'createorder_confirm') && $confirm=='yes' && $user->rights->agefodd->creer)
{
	$frompropalid=GETPOST('propalid','int');
	$agf = new Agsession($db);
	$result=$agf->fetch($id);
	if ($result < 0){
		setEventMessage($agf->error,'errors');
	}else {
		$result=$agf->createOrder($user,$socid,$frompropalid);
		if ($result < 0){
			setEventMessage($agf->error,'errors');
		}
	}
}

//Confirm create propal
if (($action == 'createproposal') && $user->rights->agefodd->creer)
{
	$agf = new Agsession($db);
	$result=$agf->fetch($id);
	if ($result < 0){
		setEventMessage($agf->error,'errors');
	}else {
		$result=$agf->createProposal($user,$socid);
		if ($result < 0){
			setEventMessage($agf->error,'errors');
		}
	}
}

/*
 * Action delete pdf document
*/
if ($action == 'del' && $user->rights->agefodd->creer)
{
	$cour=GETPOST('cour','alpha');
	$model=GETPOST('model','alpha');
	$idform=GETPOST('idform','alpha');

	if (!empty($cour))
		$file = $conf->agefodd->dir_output.'/'.$model.'-'.$cour.'_'.$id.'_'.$socid.'.pdf';
	elseif (!empty($socid))
	$file = $conf->agefodd->dir_output.'/'.$model.'_'.$id.'_'.$socid.'.pdf';
	elseif ($model=='fiche_pedago') {
		$file = $conf->agefodd->dir_output.'/'.$model.'_'.$idform.'.pdf';
	}
	else
		$file = $conf->agefodd->dir_output.'/'.$model.'_'.$id.'.pdf';

	if (is_file($file)) unlink($file);
	else
	{
		$error = $file.' : '.$langs->trans("AgfDocDelError");
		setEventMessage($error,'errors');
	}
}


/*
 * View
*/

llxHeader('',$langs->trans("AgfSessionDetail"));

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);


// Selection du bon de commande ou de la facture à lier
if (($action == 'link' ) && $user->rights->agefodd->creer)
{
	$agf = new Agsession($db);
	$agf->fetch($id);

	$head = session_prepare_head($agf);

	dol_fiche_head($head, 'document', $langs->trans("AgfSessionDetail"), 0, 'user');

	print '<div width=100% align="center" style="margin: 0 0 3px 0;">'."\n";
	print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
	print '</div>'."\n";

	// Print session card
	$agf->printSessionInfo();

	print '&nbsp';

	print '<table class="border" width="100%">'."\n";

	print '<tr class="liste_titre">'."\n";
	print '<td colspan=3>';
	print  '<a href="#">'.$langs->trans("AgfCommonDocs").'</a></td>'."\n";
	print '</tr>'."\n";

	print '<tr class="liste">'."\n";

	// creation de la liste de choix
	$agf_liste = new Agefodd_session_element($db);
	$result = $agf_liste->fetch_element_per_soc($socid, $type_link);
	$num = count($agf_liste->lines);
	if ($num > 0)
	{
		print '<form name="fact_link" action="document.php?action=link_confirm&id='.$id.'"  method="post">'."\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
		print '<input type="hidden" name="socid" value="'.$socid.'">'."\n";
		print '<input type="hidden" name="type" value="'.$type_link.'">'."\n";

		$var=True;
		$options = '<option value=""></option>'."\n";;
		for ($i = 0; $i < $num; $i++)
		{
			$options .= '<option value="'.$agf_liste->lines[$i]->id.'">'.$agf_liste->lines[$i]->ref.'</option>'."\n";
		}
		$select = '<select class="flat" name="select">'."\n".$options."\n".'</select>'."\n";

		print '<td width="250px">';
		if ($type_link == 'bc')  print $langs->trans("AgfFactureBcSelectList");
		if ($type_link == 'fac')  print $langs->trans("AgfFactureFacSelectList");
		if ($type_link == 'prop')  print $langs->trans("AgfFacturePropSelectList");
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
		($type_link == 'bc') ? print $langs->trans("AgfFactureBcNoResult") : print $langs->trans("AgfFactureFacNoResult");
		print '</td>';
	}
	print '</tr>'."\n";

	print '</div>'."\n";
	exit;
}

if (!empty($id))
{
	$agf = new Agsession($db);
	$agf->fetch($id);

	$result = $agf->fetch_societe_per_session($id);

	if ($result)
	{
		$idform = $agf->formid;

		// Display View mode
		$head = session_prepare_head($agf);

		dol_fiche_head($head, 'document', $langs->trans("AgfSessionDetail"), 0, 'generic');
		
		//Put user on the right action block after reload
		if (!empty($socid) && $action!='unlink' && $action!='createorder') {
			print '<script type="text/javascript">
					jQuery(document).ready(function () {
						jQuery(function() {
							var documentBody = (($.browser.chrome)||($.browser.safari)) ? document.body : document.documentElement;
		    				 $(documentBody).animate({scrollTop: $("#socid'.$socid.'").offset().top}, 500,\'easeInOutCubic\');
						});
					});
					</script> ';
		}

		/*
		 * Confirm delete
		*/
		if ($action == 'delete')
		{
			$ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$id,$langs->trans("AgfDeleteOps"),$langs->trans("AgfConfirmDeleteOps"),"confirm_delete",'','',1);
			if ($ret == 'html') print '<br>';
		}
		
		/*
		 * Confirm create order
		*/
		if ($action == 'createorder')
		{

			$agf_liste = new Agefodd_session_element($db);
			$result = $agf_liste->fetch_by_session_by_thirdparty($id, $socid);
			$propal_array=array('0'=>$langs->trans('AgfFromScratch'));
			
			foreach($agf_liste->lines as $line) {
				if ($line->element_type=='propal') {
					$propal_array[$line->fk_element]=$langs->trans('AgfFromObject').' '.$line->propalref;
				}
			}
			
				$form_question=array();
				$form_question[]=array('label'=> $langs->trans("AgfCreateOrderFromPropal"),'type'=> 'radio',
				'values'=>$propal_array,'name'=>'propalid');
			
			$ret=$form->form_confirm($_SERVER['PHP_SELF']."?socid=".$socid."&id=".$id,$langs->trans("AgfCreateOrderFromSession"),'',"createorder_confirm",$form_question,'',1);
			if ($ret == 'html') print '<br>';
		}
		
		
		/*
		 * Confirm create order
		*/
		if ($action == 'refreshask' || $action == 'createask')
		{
		
			$agf_liste = new Agefodd_session_element($db);
			$result = $agf_liste->fetch_by_session_by_thirdparty($id, $socid);
			$propal_array=array();
				
			foreach($agf_liste->lines as $line) {
				$propal_array[$line->fk_element.':'.$line->element_type]=$langs->trans('AgfFromObject').' '.$line->propalref;
			}
				
			$form_question=array();
			$form_question[]=array('label'=> $langs->trans("AgfGénérateConvFrom"),'type'=> 'radio',
			'values'=>$propal_array,'name'=>'idtypelement');
				
			$ret=$form->form_confirm($_SERVER['PHP_SELF']."?socid=".$socid."&id=".$id,$langs->trans("AgfCreateOrderFromSession"),'',"createorder_confirm",$form_question,'',1);
			if ($ret == 'html') print '<br>';
		}
		
		/*
		 * Confirm unlink
		*/
		if ($action == 'unlink')
		{
			
			$agf_liste = new Agefodd_session_element($db);
			$result = $agf_liste->fetch($idelement);
			if ($type_link == 'bc') {
				$ref=$agf_liste->comref;
			}
			if ($type_link == 'fac') {
				$ref=$agf_liste->facnumber;
			}
			if ($type_link == 'prop') {
				$ref=$agf_liste->propalref;
			}
			if (!empty($agf->id))
			{
				$form_question=array();
				$form_question[]=array('label'=> $langs->trans("AgfDeleteObjectAlso",$ref),
				'type'=> 'radio','values'=>array('0'=>$langs->trans('No'),'1'=>$langs->trans('Yes')),
				'name'=>'deleteobject');
			}
			$ret=$form->form_confirm($_SERVER['PHP_SELF'].'?type='.$type_link.'&socid='.$socid.'&id='.$id.'&idelement='.$idelement,$langs->trans("AgfConfirmUnlink"),
				'',"unlink_confirm",$form_question,'',1);
			if ($ret == 'html') print '<br>';
		}

		print '<div width=100% align="center" style="margin: 0 0 3px 0;">'."\n";
		print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
		print '</div>'."\n";

		// Print session card
		$agf->printSessionInfo();

		print '&nbsp';

		print '<table class="border" width="100%">'."\n";

		print '<tr class="liste_titre">'."\n";
		print '<td colspan=3>';
		print $langs->trans("AgfCommonDocs").'</td>'."\n";
		print '</tr>'."\n";


		print '<tr><td colspan=3 style="background-color:#d5baa8;">'.$langs->trans("AgfBeforeTraining").'</td></tr>'."\n";
		document_line($langs->trans("AgfFichePedagogique"), 2, 'fiche_pedago');
		document_line($langs->trans("AgfConseilsPratique"), 2, 'conseils');

		// During training
		print '<tr><td colspan=3 style="background-color:#d5baa8;">'.$langs->trans("AgfDuringTraining").'</td></tr>'."\n";
		document_line($langs->trans("AgfFichePresence"), 2, "fiche_presence");
		document_line($langs->trans("AgfFichePresenceEmpty"), 2, "fiche_presence_empty");
		document_line($langs->trans("AgfFichePresenceTrainee"), 2, "fiche_presence_trainee");
		document_line($langs->trans("AgfFichePresenceTraineeLandscape"), 2, "fiche_presence_landscape");
		document_line($langs->trans("AgfFicheEval"), 2, "fiche_evaluation");

		print '</table>'."\n";
		print '&nbsp;'."\n";

		$linecount = count($agf->lines);

		for ($i=0; $i < $linecount ; $i++)
		{
			if (!empty($agf->lines[$i]->socid))
			{
				print '<table class="border" width="100%">'."\n";

				print '<tr class="liste_titre">'."\n";
				print '<td colspan=3>';
				print  '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$agf->lines[$i]->socid.'" name="socid'.$agf->lines[$i]->socid.'" id="socid'.$agf->lines[$i]->socid.'">'.$agf->lines[$i]->code_client.' - '.$agf->lines[$i]->socname.'</a></td>'."\n";
				print '</tr>'."\n";

				// Before training session
				print '<tr><td colspan=3 style="background-color:#d5baa8;">'.$langs->trans("AgfBeforeTraining").'</td></tr>'."\n";
				if (!empty($conf->global->MAIN_MODULE_PROPALE)) {
					document_line($langs->trans("Proposal"), 2, "prop", $agf->lines[$i]->socid);
				}
				if (!empty($conf->global->MAIN_MODULE_COMMANDE)) {
					document_line($langs->trans("AgfBonCommande"), 2, "bc", $agf->lines[$i]->socid);
				}
				document_line($langs->trans("AgfConvention"), 2, "convention", $agf->lines[$i]->socid);
				document_line($langs->trans("AgfPDFConvocation"), 2, 'convocation', $agf->lines[$i]->socid);
				document_line($langs->trans("AgfCourrierConv"), 2, "courrier", $agf->lines[$i]->socid,'convention');
				document_line($langs->trans("AgfCourrierAcceuil"), 2, "courrier", $agf->lines[$i]->socid, 'accueil');

				// After training session
				print '<tr><td colspan=3 style="background-color:#d5baa8;">'.$langs->trans("AgfAfterTraining").'</td></tr>'."\n";
				document_line($langs->trans("AgfSendAttestation"), 2, "attestation", $agf->lines[$i]->socid);

				$text_fac = $langs->trans("AgfFacture");
				if($agf->lines[$i]->type_session && (!empty($conf->global->AGF_MANAGE_OPCA))) { // session inter
					$agfstat = new Agsession($db);
					// load les infos OPCA pour la session
					$agfstat->getOpcaForTraineeInSession($agf->lines[$i]->socid,$agf->lines[$i]->sessid);
					// invocie to OPCA if funding thridparty
					$soc_to_select = ($agfstat->is_OPCA?$agfstat->fk_soc_OPCA:$agf->lines[$i]->socid);

					// If funding is fill
					if ($soc_to_select > 0 && $agfstat->is_OPCA)
					{
						$text_fac.=' '.$langs->trans("AgfOPCASub1");
					}
					elseif(!$agfstat->is_OPCA) // No funding
					{
						$text_fac.=' '.$langs->trans("AgfOPCASub2");
					}
					else // No funding trhirdparty filled
					{
						$text_fac.= ' <span class="error">'.$langs->trans("AgfOPCASubErr").' <a href="'.dol_buildpath("/agefodd/session/subscribers.php",1).'?action=edit&id='.$id.'">'.$langs->trans('AgfModifySubscribersAndSubrogation').'</a></span>';
					}
				}
				document_line($text_fac, 2, "fac",$agf->lines[$i]->socid);

				document_line($langs->trans("AgfCourrierCloture"), 2, "courrier", $agf->lines[$i]->socid, 'cloture');
				if (!empty($conf->global->AGF_MANAGE_CERTIF)) {
					document_line($langs->trans("AgfPDFCertificateA4"), 2, "certificateA4", $agf->lines[$i]->socid);
					document_line($langs->trans("AgfPDFCertificateCard"), 2, "certificatecard", $agf->lines[$i]->socid);
				}
				print '</table>';
				if ($i < $linecount) print '&nbsp;'."\n";
			}
		}
		print '</div>'."\n";
	}

}

llxFooter();
$db->close();