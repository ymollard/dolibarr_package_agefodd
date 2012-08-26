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
dol_include_once('/commande/class/commande.class.php');
dol_include_once('/agefodd/lib/agefodd_document.lib.php');
dol_include_once('/core/class/html.formmail.class.php');


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$action=GETPOST('action','alpha');
$id=GETPOST('id','int');
$socid=GETPOST('socid','int');


$mesg = '';

if (GETPOST('mesg','int',1) && isset($_SESSION['message'])) $mesg=$_SESSION['message'];

$form = new Form($db);
$formmail = new FormMail($db);
$formAgefodd = new FormAgefodd($db);

/*
 * Envoi fiche pédagogique par mail
*/
if ($action == 'send_pedago' && ! $_POST['addfile'] && ! $_POST['removedfile'] && ! $_POST['cancel'])
{
	$langs->load('mails');

	$object = new Agsession($db);


	$result=$object->fetch($id);

	if ($result > 0)
	{
		$result=$object->fetch_thirdparty();

		if ($_POST['sendto'])
		{
			// Le destinataire a ete fourni via le champ libre
			$sendto = $_POST['sendto'];
			$sendtoid = 0;
		}
		elseif ($_POST['receiver'] != '-1')
		{
			// Recipient was provided from combo list
			if ($_POST['receiver'] == 'thirdparty')	// Id of third party
			{
				$sendto = $object->client->email;
				$sendtoid = 0;
			}
			else	// Id du contact
			{
				$sendto = $object->client->contact_get_property($_POST['receiver'],'email');
				$sendtoid = $_POST['receiver'];
			}
		}

		if (dol_strlen($sendto))
		{
			$langs->load("commercial");

			$from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
			$replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
			$message = $_POST['message'];
			$sendtocc = $_POST['sendtocc'];
			$deliveryreceipt = $_POST['deliveryreceipt'];

			if ($action == 'send_pedago')
			{
				if (dol_strlen($_POST['subject'])) $subject = $_POST['subject'];
				else $subject = $langs->transnoentities('Propal').' '.$object->ref;
				$actiontypecode='AC_AGF_PEDAG';
				$actionmsg = $langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
				if ($message)
				{
					$actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
					$actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
					$actionmsg.=$message;
				}
				$actionmsg2=$langs->transnoentities('Action'.FICHEPEDAGO_SENTBYMAIL);
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
			$mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt);
			if ($mailfile->error)
			{
				$mesg='<div class="error">'.$mailfile->error.'</div>';
			}
			else
			{
				$result=$mailfile->sendfile();
				if ($result)
				{
					$mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));	// Must not contain "

					$error=0;

					// Initialisation donnees
					$object->socid 			= $object->fk_soc;
					$object->sendtoid		= $sendtoid;
					$object->actiontypecode	= $actiontypecode;
					$object->actionmsg		= $actionmsg;
					$object->actionmsg2		= $actionmsg2;
					$object->fk_element		= $object->id;
					$object->elementtype	= $object->element;

					/* Appel des triggers */
					include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
					$interface=new Interfaces($db);
					$result=$interface->run_triggers('FICHEPEDAGO_SENTBYMAIL',$object,$user,$langs,$conf);
					if ($result < 0) {
						$error++; $this->errors=$interface->errors;
					}
					// Fin appel triggers

					if ($error)
					{
						dol_print_error($db);
					}
					else
					{
						// Redirect here
						// This avoid sending mail twice if going out and then back to page
						$_SESSION['message'] = $mesg;
						Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&mesg=1');
						exit;
					}
				}
				else
				{
					$langs->load("other");
					$mesg='<div class="error">';
					if ($mailfile->error)
					{
						$mesg.=$langs->trans('ErrorFailedToSendMail',$from,$sendto);
						$mesg.='<br>'.$mailfile->error;
					}
					else
					{
						$mesg.='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
					}
					$mesg.='</div>';
				}
			}
		}
		else
		{
			$langs->load("other");
			$mesg='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').' !</div>';
			dol_syslog('Recipient email is empty');
		}
	}
	$action = 'presend_pedago';
}

llxHeader();

$form = new Form($db);
$formmail = new FormMail($db);
$formAgefodd = new FormAgefodd($db);

dol_htmloutput_mesg($mesg);
dol_htmloutput_errors('',$errors);

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
		 * Envoi fiche pédagogique
		*/
		if ($action == 'presend_pedago') {

			$filename = 'fiche_pedago_'.$agf->fk_formation_catalogue.'.pdf';
			$file = $conf->agefodd->dir_output . '/' .$filename;

			$formmail->fromtype = 'user';
			$formmail->fromid   = $user->id;
			$formmail->fromname = $user->getFullName($langs);
			$formmail->frommail = $user->email;
			$formmail->withfrom=1;
			$formmail->withto=(!GETPOST('sendto','alpha'))?1:GETPOST('sendto','alpha');
			$formmail->withtosocid=$agf->fk_soc;
			$formmail->withtocc=1;
			$formmail->withtoccsocid=0;
			$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
			$formmail->withtocccsocid=0;
			$formmail->withtopic=$langs->trans('AdfSendFichePedagogique','__FORMINTITULE__');
			$formmail->withfile=1;
			$formmail->withbody=$langs->trans('AdfSendFichePedagogiqueBody','__FORMINTITULE__');
			$formmail->withbody.="\n\n--\n__SIGNATURE__\n";
			$formmail->withdeliveryreceipt=1;
			$formmail->withdeliveryreceiptreadonly=1;
			$formmail->withcancel=1;

			// Tableau des substitutions
			$formmail->substit['__FORMINTITULE__']=$agf->formintitule;
			$formmail->substit['__SIGNATURE__']=$user->signature;
			$formmail->substit['__PERSONALIZED__']='';
			// Tableau des parametres complementaires
			$formmail->param['id']=$agf->id;
			$formmail->param['action']='send_pedago';
			$formmail->param['models']='fiche_pedago';
			//$formmail->param['fichinter_id']=$object->id;
			$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$agf->id;

			// Init list of files
			if (GETPOST("mode")=='init')
			{
				$formmail->clear_attached_files();
				$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
			}
			print_fiche_titre('Envoi fiche pédagogique','','menus/mail.png');
			$formmail->show_form();

		}

		if(!$action || GETPOST('cancel')) {


			print '<table class="border" width="100%">'."\n";

			print '<tr class="liste_titre">'."\n";
			print '<td colspan=3>';
			print $langs->trans("AgfSendCommonDocs").'</td>'."\n";
			print '</tr>'."\n";


			print '<tr><td colspan=3 style="background-color:#d5baa8;">'.$langs->trans("AgfBeforeTraining").'</td></tr>'."\n";

			include_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');

			//document_send_line("Convocation", 2, 'convocation');
			document_line("Réglement intérieur", 2, 'reglement');







			if ($action != "presend_pedago") {

				//print '<a href="'.$_SERVER['PHP_SELF'].'&id='.$id.'&action=send_pedago&mode=init">'.$langs->trans('Send').'</a>';
				document_send_line("Envoi fiche pédagogique", 2, 'fiche_pedago','');
			}
			document_line("Conseils pratiques", 2, 'conseils');

			// Pendant la formation
			print '<tr><td colspan=3 style="background-color:#d5baa8;">'.$langs->trans("AgfDuringTraining").'</td></tr>'."\n";
			document_line("Fiche de présence", 2, "fiche_presence");
			document_line("Fiche d'évaluation", 2, "fiche_evaluation");

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
					print '<tr><td colspan=3 style="background-color:#d5baa8;">Avant la formation</td></tr>'."\n";
					document_line("bon de commande", 2, "bc", $agf->line[$i]->socid);
					document_line("Convention de formation", 2, "convention", $agf->line[$i]->socid);
					document_line("Courrier accompagnant l'envoi des conventions de formation", 2, "courrier", $agf->line[$i]->socid,'convention');
					document_line("Courrier accompagnant l'envoi du dossier d'accueil", 2, "courrier", $agf->line[$i]->socid, 'accueil');

					// Après la formation
					print '<tr><td colspan=3 style="background-color:#d5baa8;">Après la formation</td></tr>'."\n";
					document_line("Attestations de formation", 2, "attestation", $agf->line[$i]->socid);
					document_line("Facture", 2, "fac", $agf->line[$i]->socid);
					document_line("Courrier accompagnant l'envoi du dossier de clôture", 2, "courrier", $agf->line[$i]->socid, 'cloture');
					//document_line("for test only", 2, "courrier", $agf->line[$i]->socid, "test");
					print '</table>';
					if ($i < $linecount) print '&nbsp;'."\n";
				}
			}
			print '</div>'."\n";
		}
	}

}

llxFooter('');
?>
