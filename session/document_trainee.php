<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012-2014   Florian Henry   <florian.henry@open-concept.pro>
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
 * \file agefodd/session/document.php
 * \ingroup agefodd
 * \brief list of document
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../class/agsession.class.php');
require_once ('../class/agefodd_sessadm.class.php');
require_once ('../class/agefodd_session_stagiaire.class.php');
require_once ('../class/agefodd_stagiaire.class.php');
require_once ('../class/agefodd_session_element.class.php');
require_once ('../class/agefodd_convention.class.php');
require_once ('../core/modules/agefodd/modules_agefodd.php');
require_once ('../class/html.formagefodd.class.php');
require_once ('../lib/agefodd.lib.php');
require_once ('../lib/agefodd_document.lib.php');
require_once (DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php');
require_once (DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php');
require_once ('../class/html.formagefoddsenddocs.class.php');

$langs->load('propal');
$langs->load('bills');
$langs->load('orders');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$session_trainee_id = GETPOST('sessiontraineeid', 'int');
$confirm = GETPOST('confirm', 'alpha');

/*
 * Action create and refresh pdf document
*/
if (($action == 'create' || $action == 'refresh') && $user->rights->agefodd->creer) {
	$cour = GETPOST('cour', 'alpha');
	$model = GETPOST('model', 'alpha');
	$idform = GETPOST('idform', 'alpha');
	
	// Define output language
	$outputlangs = $langs;
	$newlang = GETPOST('lang_id', 'alpha');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))
		$newlang = $object->client->default_lang;
	if (! empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	}
	
	$file = $model . '_' . $session_trainee_id . '.pdf';
	
	$result = agf_pdf_create($db, $id, '', $model, $outputlangs, $file, $session_trainee_id, $cour);
}

if ($action == 'send' && ! $_POST ['addfile'] && ! $_POST ['removedfile'] && ! $_POST ['cancel']) {
	$langs->load('mails');
	
	$send_to = GETPOST('sendto', 'alpha');
	$receiver = GETPOST('receiver');
	
	$action = $pre_action;
	
	$object = new Agsession($db);
	$result = $object->fetch($id);
	
	if ($result > 0) {
		$result = $object->fetch_thirdparty();
		
		$sendto = array ();
		if (! empty($send_to)) {
			// Le destinataire a ete fourni via le champ libre
			$sendto = array (
					$send_to 
			);
			$sendtoid = 0;
		} elseif (is_array($receiver)) {
			$receiver = $receiver;
			foreach ( $receiver as $socpeople_id ) {
				// Initialisation donnees
				$contactstatic = new Contact($db);
				$contactstatic->fetch($socpeople_id);
				if ($contactstatic->email != '') {
					$sendto [$socpeople_id] = trim($contactstatic->firstname . " " . $contactstatic->lastname) . " <" . $contactstatic->email . ">";
				}
			}
		}
		if (is_array($sendto) && count($sendto) > 0) {
			$langs->load("commercial");
			
			$from = GETPOST('fromname') . ' <' . GETPOST('frommail') . '>';
			$replyto = GETPOST('replytoname') . ' <' . GETPOST('replytomail') . '>';
			$message = GETPOST('message');
			$sendtocc = GETPOST('sendtocc');
			$deliveryreceipt = GETPOST('deliveryreceipt');
			
			// Envoi du mail + trigger pour chaque contact
			$i = 0;
			foreach ( $sendto as $send_contact_id => $send_email ) {
				
				$models = GETPOST('models', 'alpha');
				
				$subject = GETPOST('subject');
				// Initialisation donnees
				$contactstatic = new Contact($db);
				$contactstatic->fetch($send_contact_id);
				
				if ($models == 'attestation_trainee') {
					if (empty($subject))
						$langs->transnoentities('AgfAttestation') . ' ' . $object->formintitule;
					$actiontypecode = 'AC_AGF_ATTES';
					$actionmsg = $langs->trans('MailSentBy') . ' ' . $from . ' ' . $langs->trans('To') . ' ' . $send_email . ".\n";
					if ($message) {
						$actionmsg .= $langs->trans('MailTopic') . ": " . $subject . "\n";
						$actionmsg .= $langs->trans('TextUsedInTheMessageBody') . ":\n";
						$actionmsg .= $message;
					}
					$actionmsg2 = $langs->trans('ActionATTESTATION_SENTBYMAIL');
				} elseif ($models == 'convocation_trainee') {
					if (empty($subject))
						$langs->transnoentities('AgfPDFConvocation') . ' ' . $object->formintitule;
					$actiontypecode = 'AC_AGF_CONVO';
					$actionmsg = $langs->trans('MailSentBy') . ' ' . $from . ' ' . $langs->trans('To') . ' ' . $send_email . ".\n";
					if ($message) {
						$actionmsg .= $langs->trans('MailTopic') . ": " . $subject . "\n";
						$actionmsg .= $langs->trans('TextUsedInTheMessageBody') . ":\n";
						$actionmsg .= $message;
					}
					$actionmsg2 = $langs->trans('ActionCONVOCATION_SENTBYMAIL');
				}
				// Create form object
				include_once (DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php');
				$formmail = new FormMail($db);
				
				$attachedfiles = $formmail->get_attached_files();
				$filepath = $attachedfiles ['paths'];
				$filename = $attachedfiles ['names'];
				$mimetype = $attachedfiles ['mimes'];
				
				// Envoi de la fiche
				require_once (DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');
				$mailfile = new CMailFile($subject, $send_email, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt, - 1);
				if ($mailfile->error) {
					setEventMessage($mailfile->error, 'errors');
				} else {
					$result = $mailfile->sendfile();
					if ($result) {
						setEventMessage($langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($send_email, 2)), 'mesgs');
						
						$error = 0;
						$socid_action = ($contactstatic->socid > 0 ? $contactstatic->socid : ($socid > 0 ? $socid : $object->fk_soc));
						$object->socid = $socid_action;
						$object->sendtoid = $send_contact_id;
						$object->actiontypecode = $actiontypecode;
						$object->actionmsg = $actionmsg;
						$object->actionmsg2 = $actionmsg2;
						$object->fk_element = $object->id;
						$object->elementtype = $object->element;
						
						/* Appel des triggers */
						include_once (DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
						$interface = new Interfaces($db);
						$models = GETPOST('models', 'alpha');
						if ($models == 'convocation_trainee') {
							$result = $interface->run_triggers('CONVOCATION_SENTBYMAIL', $object, $user, $langs, $conf);
						} elseif ($models == 'attestation_trainee') {
							$result = $interface->run_triggers('ATTESTATION_SENTBYMAIL', $object, $user, $langs, $conf);
						}
						if ($result < 0) {
							$error ++;
							$object->errors = $interface->errors;
						}
						// Fin appel triggers
						
						if ($error) {
							setEventMessage($object->errors, 'errors');
						} else {
							$i ++;
							$action = '';
						}
					} else {
						$langs->load("other");
						if ($mailfile->error) {
							setEventMessage($langs->trans('ErrorFailedToSendMail', $from, $send_email), 'errors');
							dol_syslog($langs->trans('ErrorFailedToSendMail', $from, $send_email) . ' : ' . $mailfile->error);
						} else {
							setEventMessage('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS', 'errors');
						}
					}
				}
			}
		} else {
			$langs->load("other");
			setEventMessage($langs->trans('ErrorMailRecipientIsEmpty'), 'errors');
			dol_syslog('Recipient email is empty', LOG_ERR);
			$action = $pre_action;
		}
	}
}

if ($action == 'sendmassmail' && $user->rights->agefodd->creer) {
	$langs->load('mails');
	
	$models = GETPOST('typemodel');
	
	$from = $user->getFullName($langs) . ' <' . $user->email . '>';
	
	$object = new Agsession($db);
	$result = $object->fetch($id);
	
	$agf_trainee = new Agefodd_session_stagiaire($db);
	$result = $agf_trainee->fetch_stagiaire_per_session($id);
	if ($result < 0) {
		setEventMessage($agf_trainee->error, 'errors');
	}
	
	foreach ( $agf_trainee->lines as $line ) {
		
		$agf_trainee = new Agefodd_stagiaire($db);
		$agf_trainee->fetch($line->id);
		
		$send_email = $agf_trainee->mail;
		
		$contact_trainee = new Contact($db);
		$contact_trainee->fetch($agf_trainee->fk_socpeople);
		
		$sendmail_check = true;
		
		if ($models == 'attestation_trainee') {
			
			// Do not send attestation if status is not present
			if ($line->status_in_session != 3 && $line->status_in_session != 4) {
				$sendmail_check = false;
			}
			$subject = $langs->transnoentities('AgfSendAttestation', $object->formintitule);
			$message = str_replace('\n', "\n", $langs->transnoentities('AgfSendAttestationBody', $object->formintitule));
			
			$actiontypecode = 'AC_AGF_ATTES';
			$actionmsg = $langs->trans('MailSentBy') . ' ' . $from . ' ' . $langs->trans('To') . ' ' . $send_email . ".\n";
			if ($message) {
				$actionmsg .= $langs->trans('MailTopic') . ": " . $subject . "\n";
				$actionmsg .= $langs->trans('TextUsedInTheMessageBody') . ":\n";
				$actionmsg .= $message;
			}
			$actionmsg2 = $langs->trans('ActionATTESTATION_SENTBYMAIL');
			
			$file = $conf->agefodd->dir_output . '/' . 'attestation_trainee_' . $line->stagerowid . '.pdf';
			
			if (! file_exists($file))
				$sendmail_check = false;
			
			$filepath = array (
					$file 
			);
			$filename = array (
					basename($file) 
			);
			$mimetype = array (
					dol_mimetype($file) 
			);
		} elseif ($models == 'convocation_trainee') {
			
			$subject = $langs->transnoentities('AgfSendConvocation', $object->formintitule);
			$message = str_replace('\n', "\n", $langs->transnoentities('AgfSendConvocationBody', $object->formintitule));
			
			$actiontypecode = 'AC_AGF_CONVO';
			$actionmsg = $langs->trans('MailSentBy') . ' ' . $from . ' ' . $langs->trans('To') . ' ' . $send_email . ".\n";
			if ($message) {
				$actionmsg .= $langs->trans('MailTopic') . ": " . $subject . "\n";
				$actionmsg .= $langs->trans('TextUsedInTheMessageBody') . ":\n";
				$actionmsg .= $message;
			}
			$actionmsg2 = $langs->trans('ActionCONVOCATION_SENTBYMAIL');
			
			$file = $conf->agefodd->dir_output . '/' . 'convocation_trainee_' . $line->stagerowid . '.pdf';
			
			if (! file_exists($file))
				$sendmail_check = false;
			
			$filepath = array (
					$file 
			);
			$filename = array (
					basename($file) 
			);
			$mimetype = array (
					dol_mimetype($file) 
			);
		}
		
		if ($sendmail_check == true) {
			// Create form object
			include_once (DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php');
			$formmail = new FormMail($db);
			
			// Envoi de la fiche
			require_once (DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');
			$mailfile = new CMailFile($subject, $send_email, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', 1, - 1);
			if ($mailfile->error) {
				setEventMessage($mailfile->error, 'errors');
			} else {
				$result = $mailfile->sendfile();
				if ($result) {
					setEventMessage($langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($send_email, 2)), 'mesgs');
					
					$error = 0;
					$socid_action = $contact_trainee->socid;
					$object->socid = $socid_action;
					$object->sendtoid = $agf_trainee->fk_socpeople;
					$object->actiontypecode = $actiontypecode;
					$object->actionmsg = $actionmsg;
					$object->actionmsg2 = $actionmsg2;
					$object->fk_element = $object->id;
					$object->elementtype = $object->element;
					
					/* Appel des triggers */
					include_once (DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
					$interface = new Interfaces($db);
					
					if ($models == 'convocation_trainee') {
						$result = $interface->run_triggers('CONVOCATION_SENTBYMAIL', $object, $user, $langs, $conf);
					} elseif ($models == 'attestation_trainee') {
						$result = $interface->run_triggers('ATTESTATION_SENTBYMAIL', $object, $user, $langs, $conf);
					}
					if ($result < 0) {
						$error ++;
						$object->errors = $interface->errors;
					}
					// Fin appel triggers
					
					if ($error) {
						setEventMessage($object->errors, 'errors');
					} else {
						$i ++;
						$action = '';
					}
				} else {
					$langs->load("other");
					if ($mailfile->error) {
						setEventMessage($langs->trans('ErrorFailedToSendMail', $from, $send_email), 'errors');
						dol_syslog($langs->trans('ErrorFailedToSendMail', $from, $send_email) . ' : ' . $mailfile->error);
					} else {
						setEventMessage('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS', 'errors');
					}
				}
			}
		}
	}
}

if ($action == 'generateall' && $user->rights->agefodd->creer) {
	// Define output language
	
	$typemodel = GETPOST('typemodel');
	$outputlangs = $langs;
	$newlang = GETPOST('lang_id', 'alpha');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))
		$newlang = $object->client->default_lang;
	if (! empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	}
	
	$agf_trainee = new Agefodd_session_stagiaire($db);
	$result = $agf_trainee->fetch_stagiaire_per_session($id);
	if ($result < 0) {
		setEventMessage($agf_trainee->error, 'errors');
	}
	
	foreach ( $agf_trainee->lines as $line ) {
		
		if (($typemodel == 'attestation_trainee' && ($line->status_in_session == 3 || $line->status_in_session == 4) || ($typemodel == 'convocation_trainee'))) {
			$file = $typemodel . '_' . $line->stagerowid . '.pdf';
			
			$result = agf_pdf_create($db, $id, '', $typemodel, $outputlangs, $file, $line->stagerowid, $cour);
		} elseif ($typemodel == 'attestation_trainee') {
			setEventMessage($langs->trans('AgfOnlyPresentTraineeGetAttestation', $line->nom . ' ' . $line->prenom), 'warnings');
		}
	}
}

/*
 * Action delete pdf document
*/
if ($action == 'del' && $user->rights->agefodd->creer) {
	$model = GETPOST('model', 'alpha');
	
	$file = $conf->agefodd->dir_output . '/' . $model . '_' . $session_trainee_id . '.pdf';
	
	if (is_file($file))
		unlink($file);
	else {
		$error = $file . ' : ' . $langs->trans("AgfDocDelError");
		setEventMessage($error, 'errors');
	}
}

/*
 * View
*/

$extrajs = array (
		'/agefodd/includes/multiselect/js/ui.multiselect.js' 
);
$extracss = array (
		'/agefodd/includes/multiselect/css/ui.multiselect.css',
		'/agefodd/css/agefodd.css' 
);

llxHeader('', $langs->trans("AgfSessionDetail"), '', '', '', '', $extrajs, $extracss);

print '<script type="text/javascript" language="javascript">
	jQuery(document).ready(function() {
	jQuery.extend($.ui.multiselect.locale, {
	addAll:\'' . $langs->transnoentities("AddAll") . '\',
		removeAll:\'' . $langs->transnoentities("RemoveAll") . '\',
			itemsCount:\'' . $langs->transnoentities("ItemsCount") . '\'
});
				jQuery(function(){
				jQuery("#receiver").addClass("multiselect").attr("multiple","multiple").attr("name","receiver[]");
				jQuery(".multiselect").multiselect({sortable: false, searchable: false});
});
});
				</script>';

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formmail = new FormAgefoddsenddocs($db);

if (! empty($id)) {
	$agf = new Agsession($db);
	$agf->fetch($id);
	
	$result = $agf->fetch_societe_per_session($id);
	
	if ($result) {
		$idform = $agf->formid;
		
		// Display View mode
		$head = session_prepare_head($agf);
		
		dol_fiche_head($head, 'document_trainee', $langs->trans("AgfSessionDetail"), 0, 'generic');
		
		// Put user on the right action block after reload
		if (! empty($session_trainee_id)) {
			print '<script type="text/javascript">
					jQuery(document).ready(function () {
						jQuery(function() {
							var documentBody = (($.browser.chrome)||($.browser.safari)) ? document.body : document.documentElement;
		    				 $(documentBody).animate({scrollTop: $("#sessiontraineeid' . $session_trainee_id . '").offset().top}, 500,\'easeInOutCubic\');
						});
					});
					</script> ';
		}
		
		print '<div width=100% align="center" style="margin: 0 0 3px 0;">' . "\n";
		print $formAgefodd->level_graph(ebi_get_adm_lastFinishLevel($id), ebi_get_level_number($id), $langs->trans("AgfAdmLevel"));
		print '</div>' . "\n";
		
		// Print session card
		$agf->printSessionInfo();
		
		print '&nbsp';
		
		/*
		 * Formulaire d'envoi des documents
		*/
		if ($action == 'presend_attestation_trainee' || $action == 'presend_convocation_trainee') {
			
			if ($action == 'presend_attestation_trainee') {
				$filename = 'attestation_trainee_' . $session_trainee_id . '.pdf';
			} elseif ($action == 'presend_convocation_trainee') {
				$filename = 'convocation_trainee_' . $session_trainee_id . '.pdf';
			}
			
			if ($filename) {
				$file = $conf->agefodd->dir_output . '/' . $filename;
			}
			
			// Init list of files
			if (GETPOST("mode") == 'init') {
				$formmail->clear_attached_files();
				if ($action == 'presend_convocation_trainee') {
					$formmail->add_attached_files($file, basename($file), dol_mimetype($file));
				} elseif ($action == 'presend_attestation_trainee') {
					$formmail->add_attached_files($file, basename($file), dol_mimetype($file));
				}
			}
			
			$formmail->fromtype = 'user';
			$formmail->fromid = $user->id;
			$formmail->fromname = $user->getFullName($langs);
			$formmail->frommail = $user->email;
			$formmail->withfrom = 1;
			// $formmail->withto=(!GETPOST('sendto','alpha'))?1:explode(',',GETPOST('sendto','alpha'));
			// $formmail->withtosocid=($agf->fk_soc > 0?$agf->fk_soc:$socid);
			$formmail->withtocc = 1;
			$formmail->withtoccsocid = 0;
			$formmail->withtoccc = $conf->global->MAIN_EMAIL_USECCC;
			$formmail->withtocccsocid = 0;
			$formmail->withfile = 1;
			
			$formmail->withdeliveryreceipt = 1;
			$formmail->withdeliveryreceiptreadonly = 0;
			$formmail->withcancel = 1;
			
			/*--------------------------------------------------------------
			 *
			* Définition des destinataires selon type de document demandé
			*
			*-------------------------------------------------------------*/
			if ($action == 'presend_attestation_trainee') {
				$formmail->withtopic = $langs->trans('AgfSendAttestation', '__FORMINTITULE__');
				$formmail->withbody = $langs->trans('AgfSendAttestationBody', '__FORMINTITULE__');
				$formmail->param ['models'] = 'attestation_trainee';
				$formmail->param ['pre_action'] = 'presend_attestation_trainee';
			} elseif ($action == "presend_convocation_trainee") {
				$formmail->withtopic = $langs->trans('AgfSendConvocation', '__FORMINTITULE__');
				$formmail->withbody = $langs->trans('AgfSendConvocationBody', '__FORMINTITULE__');
				$formmail->param ['models'] = 'convocation_trainee';
				$formmail->param ['pre_action'] = 'presend_convocation_trainee';
			}
			
			$withto = array ();
			
			// Trainee List
			$agf_trainnees = new Agefodd_session_stagiaire($db);
			$agf_trainnees->fetch($session_trainee_id);
			
			$agf_trainee = new Agefodd_stagiaire($db);
			$agf_trainee->fetch($agf_trainnees->fk_stagiaire);
			
			$withto [$agf_trainee->fk_socpeople] = $agf_trainee->nom . ' ' . $agf_trainee->prenom . ' - ' . $agf_trainee->mail;
			
			$contact_trainee = new Contact($db);
			$contact_trainee->fetch($agf_trainee->fk_socpeople);
			
			if (! empty($withto)) {
				$formmail->withto = $withto;
			}
			
			$formmail->withdeliveryreceipt = 1;
			
			$formmail->withbody .= "\n\n--\n__SIGNATURE__\n";
			
			// Tableau des substitutions
			$formmail->substit ['__FORMINTITULE__'] = $agf->formintitule;
			$formmail->substit ['__SIGNATURE__'] = $user->signature;
			$formmail->substit ['__PERSONALIZED__'] = '';
			
			// Tableau des parametres complementaires
			$formmail->param ['action'] = 'send';
			$formmail->param ['id'] = $agf->id;
			$formmail->param ['returnurl'] = $_SERVER ["PHP_SELF"] . '?id=' . $agf->id;
			
			if ($action == 'presend_convocation_trainee') {
				print_fiche_titre($langs->trans('AgfSendDocuments'), '', dol_buildpath('/agefodd/img/mail_generic.png', 1), 1);
			} elseif ($action == 'presend_attestation_trainee') {
				print_fiche_titre($langs->trans('AgfSendDocuments'), '', dol_buildpath('/agefodd/img/mail_generic.png', 1), 1);
			}
			$formmail->show_form();
			
			if (! empty($mesg)) {
				setEventMessage($mesg, $style_mesg);
			}
		} else {
			
			print '<div class="tabsAction">';
			print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=generateall&typemodel=convocation_trainee">' . $langs->trans('AgfGenerateAllConvocation') . '</a>';
			print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=generateall&typemodel=attestation_trainee">' . $langs->trans('AgfGenerateAllAttestation') . '</a>';
			print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=sendmassmail&typemodel=convocation_trainee">' . $langs->trans('AgfSendMailAllConvocation') . '</a>';
			print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=sendmassmail&typemodel=attestation_trainee">' . $langs->trans('AgfSendMailAllAttestation') . '</a>';
			print '</div>';
			
			$agf_trainee = new Agefodd_session_stagiaire($db);
			$result = $agf_trainee->fetch_stagiaire_per_session($id);
			if ($result < 0) {
				setEventMessage($agf_trainee->error, 'errors');
			}
			
			$linecount = count($agf_trainee->lines);
			
			for($i = 0; $i < $linecount; $i ++) {
				if (! empty($agf_trainee->lines [$i]->stagerowid)) {
					print '<table class="border" width="100%">' . "\n";
					
					print '<tr class="liste_titre">' . "\n";
					print '<td colspan=3>';
					print '<a href="' . dol_buildpath('/agefodd/trainee/card.php', 1) . '?id=' . $agf_trainee->lines [$i]->id . '" name="sessiontraineeid' . $agf_trainee->lines [$i]->stagerowid . '" id="sessiontraineeid' . $agf_trainee->lines [$i]->stagerowid . '">' . $agf_trainee->lines [$i]->nom . ' ' . $agf_trainee->lines [$i]->prenom . '</a></td>' . "\n";
					print '</tr>' . "\n";
					
					// Before training session
					print '<tr><td colspan=3 style="background-color:#d5baa8;">' . $langs->trans("AgfBeforeTraining") . '</td></tr>' . "\n";
					document_line($langs->trans("AgfPDFConvocation"), 2, 'convocation_trainee', $agf_trainee->lines [$i]->stagerowid);
					
					// After training session
					print '<tr><td colspan=3 style="background-color:#d5baa8;">' . $langs->trans("AgfAfterTraining") . '</td></tr>' . "\n";
					document_line($langs->trans("AgfSendAttestation"), 2, "attestation_trainee", $agf_trainee->lines [$i]->stagerowid);
					
					print '</table>';
					if ($i < $linecount)
						print '&nbsp;' . "\n";
				}
			}
			print '</div>' . "\n";
		}
	}
}

llxFooter();
$db->close();