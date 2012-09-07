<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012       Florian Henry   <florian.henry@open-concept.pro>
 * Copyright (C) 2012       JF FERRY        <jfefe@aternatik.fr>

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
 * 	\file		/agefodd/session/send_docs.php
 * 	\brief		Page permettant d'envoyer les documents relatifs à la session de formation
 */

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/class/agsession.class.php');
dol_include_once('/agefodd/class/agefodd_sessadm.class.php');
dol_include_once('/agefodd/class/agefodd_facture.class.php');
dol_include_once('/agefodd/class/agefodd_convention.class.php');
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/class/html.formagefodd.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/agefodd/class/html.formagefodd.class.php');
dol_include_once('/agefodd/class/html.formagefoddsenddocs.class.php');
dol_include_once('/commande/class/commande.class.php');
dol_include_once('/contact/class/contact.class.php');
dol_include_once('/agefodd/lib/agefodd_document.lib.php');
dol_include_once('/core/class/html.formmail.class.php');
dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
include(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$action=GETPOST('action','alpha');
$pre_action=GETPOST('pre_action','alpha');
$id=GETPOST('id','int');
$socid=GETPOST('socid','int');

$mesg = '';
$mesgs=array();

if (GETPOST('mesg','int',1) && isset($_SESSION['message'])) $mesg=$_SESSION['message'];

$form = new Form($db);
$formmail = new FormAgefoddsenddocs($db);
$formAgefodd = new FormAgefodd($db);

/*
 * Envoi document unique
*/
if ($action == 'send' && ! $_POST['addfile'] && ! $_POST['removedfile'] && ! $_POST['cancel'])
{
	$langs->load('mails');

	$action=$pre_action;

	$object = new Agsession($db);
	$result=$object->fetch($id);

	if ($result > 0)
	{
		$result=$object->fetch_thirdparty();

		$sendto = array();
		if ($_POST['sendto'])
		{
			// Le destinataire a ete fourni via le champ libre
			$sendto = array(GETPOST('sendto','alpha'));
			$sendtoid = 0;
		}
		elseif (is_array($_POST['receiver']))
		{
			$receiver = $_POST['receiver'];
			foreach($_POST['receiver'] as $socpeople_id) {
				// Initialisation donnees
				$contactstatic = new Contact($db);
				$contactstatic->fetch($socpeople_id);
				if ($contactstatic->email !='')
				{
					$sendto[$socpeople_id] =  trim($contactstatic->firstname." ".$contactstatic->name)." &lt;".$contactstatic->email."&gt;";;
				}
			}
		}
		if (is_array($sendto) && count($sendto) > 0) {
			$langs->load("commercial");

			$from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
			$replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
			$message = $_POST['message'];
			$sendtocc = $_POST['sendtocc'];
			$deliveryreceipt = $_POST['deliveryreceipt'];

			// Envoi du mail + trigger pour chaque contact
			$i = 0;
			foreach($sendto as $send_contact_id => $send_email) {

				$models = GETPOST('models','alpha');

				// Initialisation donnees
				$contactstatic = new Contact($db);
				$contactstatic->fetch($send_contact_id);

				if ($models == 'fiche_pedago')
				{
					if (dol_strlen($_POST['subject'])) $subject = $_POST['subject'];
					else $subject = $langs->transnoentities('AgfFichePedagogique').' '.$object->ref;
					$actiontypecode='AC_AGF_PEDAG';
					$actionmsg = $langs->trans('MailSentBy').' '.$from.' '.$langs->trans('To').' '.$send_email.".\n";
					if ($message)
					{
						$actionmsg.=$langs->trans('MailTopic').": ".$subject."\n";
						$actionmsg.=$langs->trans('TextUsedInTheMessageBody').":\n";
						$actionmsg.=$message;

					}
					$actionmsg2=$langs->trans('Action'.FICHEPEDAGO_SENTBYMAIL);
				}
				elseif ($models == 'fiche_presence')
				{
					if (dol_strlen($_POST['subject'])) $subject = $_POST['subject'];
					else $subject = $langs->trans('AgfFichePresence').' '.$object->ref;
					$actiontypecode='AC_AGF_PRES';
					$actionmsg = $langs->trans('MailSentBy').' '.$from.' '.$langs->trans('To').' '.$send_email.".\n";
					if ($message)
					{
						$actionmsg.=$langs->trans('MailTopic').": ".$subject."\n";
						$actionmsg.=$langs->trans('TextUsedInTheMessageBody').":\n";
						$actionmsg.=$message;
					}
					$actionmsg2=$langs->trans('Action'.FICHEPRESENCE_SENTBYMAIL);
				}
				elseif ($models == 'convention')
				{
					if (dol_strlen($_POST['subject'])) $subject = $_POST['subject'];
					else $subject = $langs->trans('AgfConvention').' '.$object->ref;
					$actiontypecode='AC_AGF_CONV';
					$actionmsg = $langs->trans('MailSentBy').' '.$from.' '.$langs->trans('To').' '.$send_email.".\n";
					if ($message)
					{
						$actionmsg.=$langs->trans('MailTopic').": ".$subject."\n";
						$actionmsg.=$langs->trans('TextUsedInTheMessageBody').":\n";
						$actionmsg.=$message;
					}
					$actionmsg2=$langs->trans('Action'.CONVENTION_SENTBYMAIL);
				}
				elseif ($models == 'attestation')
				{
					if (dol_strlen($_POST['subject'])) $subject = $_POST['subject'];
					else $subject = $langs->trans('AgfAttestation').' '.$object->ref;
					$actiontypecode='AC_AGF_ATTES';
					$actionmsg = $langs->trans('MailSentBy').' '.$from.' '.$langs->trans('To').' '.$send_email.".\n";
					if ($message)
					{
						$actionmsg.=$langs->trans('MailTopic').": ".$subject."\n";
						$actionmsg.=$langs->trans('TextUsedInTheMessageBody').":\n";
						$actionmsg.=$message;
					}
					$actionmsg2=$langs->trans('Action'.ATTESTATION_SENTBYMAIL);
				}


				// Create form object
				include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
				$formmail = new FormMail($db);

				$attachedfiles=$formmail->get_attached_files();
				$filepath = $attachedfiles['paths'];
				$filename = $attachedfiles['names'];
				$mimetype = $attachedfiles['mimes'];

				// Envoi de la fiche
				require_once(DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php');
				$mailfile = new CMailFile($subject,$send_email,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt);
				if ($mailfile->error)
				{
					$mesgs[]=$mailfile->error;
				}
				else
				{
					$result=$mailfile->sendfile();
					if ($result)
					{
						$mesgs[]=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($send_email,2));	// Must not contain "

						$error=0;
						$socid_action = ($contactstatic->socid > 0 ? $contactstatic->socid : ($socid > 0 ? $socid : $object->fk_soc));
						$object->socid 			= $socid_action;
						$object->sendtoid		= $send_contact_id;
						$object->actiontypecode	= $actiontypecode;
						$object->actionmsg		= $actionmsg;
						$object->actionmsg2		= $actionmsg2;
						$object->fk_element		= $object->id;
						$object->elementtype	= $object->element;

						/* Appel des triggers */
						include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
						$interface=new Interfaces($db);
						$models = GETPOST('models','alpha');
						if ($models == 'fiche_pedago')
						{
							$result=$interface->run_triggers('FICHEPEDAGO_SENTBYMAIL',$object,$user,$langs,$conf);
						}
						elseif ($models == 'fiche_presence')
						{
							$result=$interface->run_triggers('FICHEPRESENCE_SENTBYMAIL',$object,$user,$langs,$conf);
						}
						elseif ($models == 'convention')
						{
							$result=$interface->run_triggers('CONVENTION_SENTBYMAIL',$object,$user,$langs,$conf);
						}
						elseif ($models == 'attestation')
						{
							$result=$interface->run_triggers('ATTESTATION_SENTBYMAIL',$object,$user,$langs,$conf);
						}
						if ($result < 0) {
							$error++; $object->errors=$interface->errors;
						}
						// Fin appel triggers

						if ($error)
						{
							dol_print_error($db);
						}
						else
						{
							$i++;
							$action = '';
						}
					}
					else
					{
						$langs->load("other");
						if ($mailfile->error)
						{
							$mesgs[]=$langs->trans('ErrorFailedToSendMail',$from,$send_email);
							dol_syslog($langs->trans('ErrorFailedToSendMail',$from,$send_email).' : '.$mailfile->error);
						}
						else
						{
							$mesgs[]='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
						}
					}
				}
			}
		}
		else
		{
			$langs->load("other");
			$mesgs[]='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').'</div>';
			dol_syslog('Recipient email is empty');
			$action = $pre_action;
		}
	}


}

/*
 * Remove file in email form
*/
if (! empty($_POST['removedfile']))
{
	require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir_tmp = $vardir.'/temp';

	// TODO Delete only files that was uploaded from email form
	$mesg=dol_remove_file_process($_POST['removedfile'],0);

	$action = $pre_action;

}

/*
 * Add file in email form
*/
if ($_POST['addfile'])
{
	require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

	// Set tmp user directory TODO Use a dedicated directory for temp mails files
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir_tmp = $vardir.'/temp';

	$mesg=dol_add_file_process($upload_dir_tmp,0,0);

	$action = $pre_action;
}


$extrajs = array('/agefodd/inc/multiselect/js/ui.multiselect.js');
$extracss = array('/agefodd/inc/multiselect/css/ui.multiselect.css');

llxHeader('',$langs->trans("AgfSendCommonDocs"),'','','','',$extrajs,$extracss);


print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	jQuery.extend($.ui.multiselect.locale, {
		addAll:\''. $langs->transnoentities("AddAll").'\',
		removeAll:\''. $langs->transnoentities("RemoveAll").'\',
		itemsCount:\''. $langs->transnoentities("ItemsCount").'\'
	});
	jQuery(function(){
		jQuery("#receiver").addClass("multiselect").attr("multiple","multiple").attr("name","receiver[]");
		jQuery(".multiselect").multiselect({sortable: false, searchable: false});
	});
});
</script>';



if (!empty($id))
{
	$agf = new Agsession($db);
	$agf->fetch($id);

	$result = $agf->fetch_societe_per_session($id);

	if ($result)
	{
		$idform = $agf->formid;

		// Affichage en mode "consultation"
		$head = session_prepare_head($agf);

		dol_fiche_head($head, 'send_docs', $langs->trans("AgfSessionDetail"), 0, 'generic');


		/*
		* Confirmation de la suppression
		*/
		if ($action == 'delete')
		{
			$ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$id,$langs->trans("AgfDeleteOps"),$langs->trans("AgfConfirmDeleteOps"),"confirm_delete");
			if ($ret == 'html') print '<br>';
		}

		print '<div width=100% align="center" style="margin: 0 0 3px 0;">'."\n";
		print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
		print '</div>'."\n";


		$agf->printSessionInfo();
		print '</div>'."\n";

		/*
		 * Formulaire d'envoi des documents
		*/
		if ($action == 'presend_pedago' || $action == 'presend_presence' || $action == 'presend_convention' || $action == 'presend_attestation') {

			if ($action == 'presend_presence') {
				$filename = 'fiche_presence_'.$agf->id.'.pdf';
			}
			elseif ($action == 'presend_pedago') {
				$filename = 'fiche_pedago_'.$agf->fk_formation_catalogue.'.pdf';
			}
			elseif ($action == 'presend_convention') {
				$filename = 'convention_'.$agf->id.'_'.$socid.'.pdf';
			}
			elseif ($action == 'presend_attestation') {
				$filename = 'attestation_'.$agf->id.'_'.$socid.'.pdf';
			}

			$file = $conf->agefodd->dir_output . '/' .$filename;

			// Init list of files
			if (GETPOST("mode")=='init')
			{
				$formmail->clear_attached_files();
				if ($action == 'presend_convention') {
					$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
					// Ajout fiche péda
					$filename = 'fiche_pedago_'.$agf->fk_formation_catalogue.'.pdf';
					$file = $conf->agefodd->dir_output . '/' .$filename;
					if (file_exists($file))
						$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
				}
				elseif ($action == 'presend_presence') {
					$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
					// Ajout fiche péda
					$filename = 'fiche_evaluation_'.$agf->id.'.pdf';
					$file = $conf->agefodd->dir_output . '/' .$filename;
					if (file_exists($file))
						$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
				}
				else {
					$formmail->add_attached_files($conf->agefodd->dir_output,basename($file),dol_mimetype($file));
				}
			}

			$formmail->fromtype = 'user';
			$formmail->fromid   = $user->id;
			$formmail->fromname = $user->getFullName($langs);
			$formmail->frommail = $user->email;
			$formmail->withfrom=1;
			//$formmail->withto=(!GETPOST('sendto','alpha'))?1:explode(',',GETPOST('sendto','alpha'));
			//$formmail->withtosocid=($agf->fk_soc > 0?$agf->fk_soc:$socid);
			$formmail->withtocc=0;
			$formmail->withtoccsocid=0;
			$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
			$formmail->withtocccsocid=0;
			$formmail->withfile=1;

			$formmail->withdeliveryreceipt=1;
			$formmail->withdeliveryreceiptreadonly=1;
			$formmail->withcancel=1;


			if ($action == 'presend_presence') {
				$formmail->withtopic=$langs->trans('AdfSendFeuillePresence','__FORMINTITULE__');
				$formmail->withbody=$langs->trans('AdfSendFeuillePresenceBody','__FORMINTITULE__');
				$formmail->param['models']='fiche_presence';
				$formmail->param['pre_action']='presend_presence';

				// Feuille de présence peut être aux formateurs
				$agftrainersess = new Agefodd_session_formateur($db);
				$num = $agftrainersess->fetch_formateur_per_session($id);
				$withto= array();
				if($num > 0) {
					foreach ($agftrainersess->line as $formateur) {
						if($formateur->email != '')
							$withto[$formateur->socpeopleid] = $formateur->name.' '.$formateur->firstname .' (formateur)';
					}
				}

				// feuille de présence peut être envoyé à l'opca
				if ($agf->type_session &&  $socid) {
					$result_opca = $agf->getOpcaForTraineeInSession($socid,$id);
					if (! $result_opca) {
						$mesg = '<div class="warning">'.$langs->trans('AgfSendWarningNoMailOpca').'</div>';
						$style_mesg='warning';
					}
					else {
						$withto[$agf->fk_socpeople_OPCA] 	= $agf->soc_OPCA_name.' (OPCA)';
					}
				}
				else {
					$withto[$agf->fk_socpeople_OPCA] 	= $agf->soc_OPCA_name.' (OPCA)';
				}

				// Contact client
				if($agf->contactid > 0)
					$withto[$agf->contactid]		= $agf->contactname.' (Client)';

				$formmail->withto=$withto;
				$formmail->withtofree=0;
				$formmail->withfile=2;
			}
			elseif ($action == 'presend_pedago') {
				$formmail->withtopic=$langs->trans('AdfSendFichePedagogique','__FORMINTITULE__');
				$formmail->withbody=$langs->trans('AdfSendFichePedagogiqueBody','__FORMINTITULE__');
				$formmail->param['models']='fiche_pedago';
				$formmail->param['pre_action']='presend_pedago';
			}
			elseif ($action == 'presend_convention') {

				$formmail->withtopic=$langs->trans('AdfSendConvention','__FORMINTITULE__');
				$formmail->withbody=$langs->trans('AdfSendConventionBody','__FORMINTITULE__');
				$formmail->param['models']='convention';
				$formmail->param['pre_action']='presend_convention';

				// Convention peut être envoyé à l'opca ou au client
				// TODO:  gérer intra / inter)
				$withto[$agf->fk_socpeople_OPCA] 	= $agf->soc_OPCA_name.' (OPCA)';

				// Contact client
				$withto[$agf->contactid] 			= $agf->contactname.' (Client)';

				$formmail->withto=$withto;
				$formmail->withtofree=1;
				$formmail->withfile=2;
			}
			if ($action == 'presend_attestation') {

				$formmail->withtopic=$langs->trans('AdfSendAttestation','__FORMINTITULE__');
				$formmail->withbody=$langs->trans('AdfSendAttestationBody','__FORMINTITULE__');
				$formmail->param['models']='attestation';
				$formmail->param['pre_action']='presend_attestation';

				// Attestation peut être envoyé à l'opca ou au client
				if ($agf->type_session &&  $socid) {
					$result_opca = $agf->getOpcaForTraineeInSession($socid,$id);
					if (! $result_opca) {
						$mesg = '<div class="warning">'.$langs->trans('AgfSendWarningNoMailOpca').'</div>';
						$style_mesg='warning';
					}
					else {
						$withto[$agf->fk_socpeople_OPCA] 	= $agf->soc_OPCA_name.' (OPCA)';
					}
				}
				else {
					$withto[$agf->fk_socpeople_OPCA] 	= $agf->soc_OPCA_name.' (OPCA)';
				}

				// Contact client
				if($agf->contactid > 0)
					$withto[$agf->contactid]		= $agf->contactname.' (Client)';

				$formmail->withto=$withto;
				$formmail->withtofree=1;

			}

			$formmail->withbody.="\n\n--\n__SIGNATURE__\n";

			// Tableau des substitutions
			$formmail->substit['__FORMINTITULE__']=$agf->formintitule;
			$formmail->substit['__SIGNATURE__']=$user->signature;
			$formmail->substit['__PERSONALIZED__']='';


			//Tableau des parametres complementaires
			$formmail->param['action']='send';
			$formmail->param['id']=$agf->id;
			$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$agf->id;


			dol_htmloutput_mesg($mesg,$mesgs,$style_mesg);


			if ($action == 'presend_pedago') {
				print_fiche_titre('Envoi fiche pédagogique','','menus/mail.png');
			}
			elseif ($action == 'presend_presence') {
				print_fiche_titre('Envoi feuille de présence','','menus/mail.png');
			}
			elseif ($action == 'presend_convention') {
				print_fiche_titre('Envoi convention de formation','','menus/mail.png');
			}
			elseif ($action == 'presend_attestation') {
				print_fiche_titre('Envoi attestation de formation','','menus/mail.png');
			}
			$formmail->show_form();

		}

		/*
		 * Envoi fiche pédagogique
		*/
		if ($action == 'presend_presence') {
			$filename = 'fiche_presence_'.$agf->id.'.pdf';
		}

		if(!$action || GETPOST('cancel')) {

			dol_htmloutput_mesg($mesg,$mesgs);

			print '<table class="border" width="100%">'."\n";

			print '<tr class="liste_titre">'."\n";
			print '<td colspan=3>';
			print $langs->trans("AgfSendCommonDocs").'</td>'."\n";
			print '</tr>'."\n";

			// Avant la  formation
			print '<tr><td colspan=3 style="background-color:#d5baa8;">'.$langs->trans("AgfCommonDocs").'</td></tr>'."\n";

			include_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');

			//document_send_line("Convocation", 2, 'convocation');
			//document_line("Réglement intérieur", 2, 'reglement');

			document_send_line("Envoi fiche pédagogique", 2, 'fiche_pedago','');
			document_send_line("Fiche de présence", 2, "fiche_presence");

			// Pendant la formation
			//print '<tr><td colspan=3 style="background-color:#d5baa8;">'.$langs->trans("AgfDuringTraining").'</td></tr>'."\n";


			//document_line("Fiche d'évaluation", 2, "fiche_evaluation");

			print '</table>'."\n";
			print '&nbsp;'."\n";

			$linecount = count($agf->line);

			for ($i=0; $i < $linecount ; $i++)
			{
				if (!empty($agf->line[$i]->socid))
				{
					$ext = '_'.$id.'_'.$agf->line[$i]->socid.'.pdf';

					${'flag_bc_'.$agf->line[$i]->socid} = 0;

					print '<table class="border" width="100%">'."\n";

					print '<tr class="liste_titre">'."\n";
					print '<td colspan=3>';
					print  '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$agf->line[$i]->socid.'">'.$agf->line[$i]->socname.'</a></td>'."\n";
					print '</tr>'."\n";

					// Avant la formation
					//print '<tr><td colspan=3 style="background-color:#d5baa8;">Avant la formation</td></tr>'."\n";
					//document_send_line("bon de commande", 2, "bc", $agf->line[$i]->socid);
					document_send_line("Convention de formation", 2, "convention", $agf->line[$i]->socid);
					//document_line("Courrier accompagnant l'envoi des conventions de formation", 2, "courrier", $agf->line[$i]->socid,'convention');
					//document_line("Courrier accompagnant l'envoi du dossier d'accueil", 2, "courrier", $agf->line[$i]->socid, 'accueil');

					// Après la formation
					//print '<tr><td colspan=3 style="background-color:#d5baa8;">Après la formation</td></tr>'."\n";
					document_send_line("Attestations de formation", 2, "attestation", $agf->line[$i]->socid);
					//document_send_line("Facture", 2, "fac", $agf->line[$i]->socid);
					//document_line("Courrier accompagnant l'envoi du dossier de clôture", 2, "courrier", $agf->line[$i]->socid, 'cloture');
					//document_line("for test only", 2, "courrier", $agf->line[$i]->socid, "test");
					print '</table>';
					if ($i < $linecount) print '&nbsp;'."\n";
				}
			}
			print '</div>'."\n";
		}

		print '<div class="tabsAction">';
		if ($action !='view_actioncomm') {
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=view_actioncomm&id='.$id.'">'.$langs->trans('AgfViewActioncomm').'</a>';
		}

		print '</div>';

		if ($action =='view_actioncomm') {
			// List of actions on element
			 include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php');
			$formactions=new FormAgefodd($db);
			$somethingshown=$formactions->showactions($agf,'agefodd_agsession',$socid);

		}
	}

}

llxFooter('');
?>
