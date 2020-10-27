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

require_once '../class/agsession.class.php';
require_once '../class/agefodd_sessadm.class.php';
require_once '../class/agefodd_session_stagiaire.class.php';
require_once '../class/agefodd_stagiaire.class.php';
require_once '../class/agefodd_session_element.class.php';
require_once '../class/agefodd_convention.class.php';
require_once '../core/modules/agefodd/modules_agefodd.php';
require_once '../class/html.formagefodd.class.php';
require_once '../lib/agefodd.lib.php';
require_once '../lib/agefodd_document.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . "/core/class/html.formmail.class.php";

$langs->load('propal');
$langs->load('bills');
$langs->load('orders');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$hookmanager->initHooks(array(
	'agefoddsessiondocumenttrainee'
));

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$session_trainee_id = GETPOST('sessiontraineeid', 'int');
$confirm = GETPOST('confirm', 'alpha');
$id_external_model = GETPOST('id_external_model', 'none');
$addfile = GETPOST('addfile', 'none');
$removedfile = GETPOST('removedfile', 'none');
$pre_action = GETPOST('pre_action', 'alpha');

if (GETPOST('modelselected', 'none')) {
	$action = GETPOST('pre_action', 'none');
}


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
		$newlang = $object->thirdparty->default_lang;
	if (! empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	}

	$file = $model . '_' . $session_trainee_id . '.pdf';

	//this configuration variable is designed like
	//standard_model_name:new_model_name&standard_model_name:new_model_name&....
	if (!empty($conf->global->AGF_PDF_MODEL_OVERRIDE) && ($model != 'convention')) {
		$modelarray=explode('&', $conf->global->AGF_PDF_MODEL_OVERRIDE);
		if (is_array($modelarray) && count($modelarray)>0){
			foreach($modelarray as $modeloveride) {
				$modeloverridearray=explode(':',$modeloveride);
				if (is_array($modeloverridearray) && count($modeloverridearray)>0){
					if ($modeloverridearray[0]==$model) {
						$model=$modeloverridearray[1];
					}
				}
			}

		}
	}

	if (!empty($id_external_model) || strpos($model, 'rfltr_agefodd') !== false) {
		$path_external_model = '/referenceletters/core/modules/referenceletters/pdf/pdf_rfltr_agefodd.modules.php';
		if(strpos($model, 'rfltr_agefodd') !== false) $id_external_model= (int)strtr($model, array('rfltr_agefodd_'=>''));
	}

    $result = agf_pdf_create($db, $id, '', $model, $outputlangs, $file, $session_trainee_id, $cour, $path_external_model, $id_external_model);
}

/*
 * Add file in email form
 */
if (! empty($addfile)) {
	require_once (DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");

	// Set tmp user directory TODO Use a dedicated directory for temp mails files
	$vardir = $conf->user->dir_output . "/" . $user->id;
	$upload_dir_tmp = $vardir . '/temp';

	$mesg = dol_add_file_process($upload_dir_tmp, 0, 0, 'addedfile', '', null, $formmail->trackid);

	$action = $pre_action;
}

/*
 * Remove file in email form
 */
if (! empty($removedfile)) {
	require_once (DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");

	// Set tmp user directory
	$vardir = $conf->user->dir_output . "/" . $user->id;
	$upload_dir_tmp = $vardir . '/temp';

	// TODO Delete only files that was uploaded from email form
	$mesg = dol_remove_file_process($removedfile, 0, 1, $formmail->trackid);

	$action = $pre_action;
}

if ($action == 'send' && ! $_POST ['addfile'] && ! $_POST ['removedfile'] && ! $_POST ['cancel']) {
	$langs->load('mails');

	$send_to = GETPOST('sendto', 'alpha');
	$receiver = GETPOST('receiver', 'none');

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
		} elseif (is_array($receiver) && count($receiver)>0) {
			foreach ( $receiver as $id_receiver ) {
				// Initialisation donnees

				if (preg_match ( "/_third/", $id_receiver )) {
					$id_receiver= preg_replace('/_third/', '', $id_receiver);
					$societe = new Societe($db);
					$societe->fetch($id_receiver);
					$sendto[$id_receiver.'_third'] = $societe->name . " <" . $societe->email . ">";
				} elseif (preg_match ( "/_socp/", $id_receiver )) {
					$id_receiver= preg_replace('/_socp/', '', $id_receiver);
					if (!empty($id_receiver)) {
						$contactstatic = new Contact($db);
						$contactstatic->fetch($id_receiver);
						if ($contactstatic->email != '') {
							$sendto[$id_receiver.'_socp'] = trim($contactstatic->firstname . " " . $contactstatic->lastname) . " <" . $contactstatic->email . ">";
						}
					}
				}
			}
		}
		if (is_array($sendto) && count($sendto) > 0) {
			$langs->load("commercial");

			$from = GETPOST('fromname', 'none') . ' <' . GETPOST('frommail', 'none') . '>';
			$replyto = GETPOST('replytoname', 'none') . ' <' . GETPOST('replytomail', 'none') . '>';
			$message = GETPOST('message', 'none');
			$sendtocc = GETPOST('sendtocc', 'none');
			$deliveryreceipt = GETPOST('deliveryreceipt', 'none');

			// Envoi du mail + trigger pour chaque contact
			$i = 0;
//            var_dump($sendto);exit;
			foreach ( $sendto as $send_id => $send_email ) {

				$models = GETPOST('models', 'alpha');

				$subject = GETPOST('subject', 'none');

				//Usefull for trigger actioncomm
				if (preg_match ( "/_third/", $send_id )) {
					$send_id= preg_replace('/_third/', '', $send_id);
					$societe = new Societe($db);
					$societe->fetch($send_id);
					$object->socid = $send_id;
					$object->sendtoid=0;
				} elseif (preg_match ( "/_socp/", $send_id )) {
					$send_id= preg_replace('/_socp/', '', $send_id);
					$contactstatic = new Contact($db);
					$contactstatic->fetch($send_id);
					$contactstatic->fetch_thirdparty();
					$object->socid = $contactstatic->thirdparty->id;
					$object->sendtoid=$send_id;
				}

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
                } elseif ($models == 'fiche_presence_trainee_trainee') {
                    if (empty($subject))
                        $langs->transnoentities('AgfPDFFichePresence') . ' ' . $object->formintitule;
                    $actiontypecode = 'AC_AGF_PRES';
                    $actionmsg = $langs->trans('MailSentBy') . ' ' . $from . ' ' . $langs->trans('To') . ' ' . $send_email . ".\n";
                    if ($message) {
                        $actionmsg .= $langs->trans('MailTopic') . ": " . $subject . "\n";
                        $actionmsg .= $langs->trans('TextUsedInTheMessageBody') . ":\n";
                        $actionmsg .= $message;
                    }
                    $actionmsg2 = $langs->trans('ActionFICHEPRESENCE_SENTBYMAIL');
                } elseif ($models == 'attestationendtraining_trainee') {
					if (empty($subject))
						$langs->transnoentities('AgfAttestationEndTraining') . ' ' . $object->formintitule;
					$actiontypecode = 'AC_AGF_ATTES';
					$actionmsg = $langs->trans('MailSentBy') . ' ' . $from . ' ' . $langs->trans('To') . ' ' . $send_email . ".\n";
					if ($message) {
						$actionmsg .= $langs->trans('MailTopic') . ": " . $subject . "\n";
						$actionmsg .= $langs->trans('TextUsedInTheMessageBody') . ":\n";
						$actionmsg .= $message;
					}
					$actionmsg2 = $langs->trans('ActionATTESTATION_SENTBYMAIL');
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
						} elseif ($models == 'attestation_trainee' || $models == 'attestationendtraining_trainee') {
							$result = $interface->run_triggers('ATTESTATION_SENTBYMAIL', $object, $user, $langs, $conf);
						} elseif ($models == 'fiche_presence_trainee_trainee') {
							$result = $interface->run_triggers('FICHEPRESENCE_SENTBYMAIL', $object, $user, $langs, $conf);
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
							setEventMessage($langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), $send_email), 'errors');
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

	$models = GETPOST('typemodel', 'none');

	$from = $user->getFullName($langs) . ' <' . $user->email . '>';

	$object = new Agsession($db);
	$result = $object->fetch($id);

	$agf_trainee = new Agefodd_session_stagiaire($db);
	$result = $agf_trainee->fetch_stagiaire_per_session($id);
	if ($result < 0) {
		setEventMessage($agf_trainee->error, 'errors');
	}
	// tableau des fichiers envoyés aux stagiaires
    $TSentFile = array();

	foreach ( $agf_trainee->lines as $line ) {

		$agf_trainee = new Agefodd_stagiaire($db);
		$agf_trainee->fetch($line->id);

		$contact_trainee = new Contact($db);
		$contact_trainee->fetch($agf_trainee->fk_socpeople);

		$companyid=$contact_trainee->socid;
		if (!empty($agf_trainee->fk_socpeople)) {
			$contactid = $agf_trainee->fk_socpeople;
		} else {
			$contactid=0;
		}

		//Perapre data for trigeer action comm
		$object->socid = $companyid;
		$object->sendtoid = $contactid;

		$send_email = $agf_trainee->mail;

		$sendmail_check = true;

		if ($models == 'attestation_trainee' || $models == 'attestationendtraining_trainee') {

			// Do not send attestation if status is not present
			if ($line->status_in_session != 3 && $line->status_in_session != 4) {
				$sendmail_check = false;
				setEventMessage($langs->trans('AgfOnlyPresentTraineeGetAttestation', $line->nom . ' ' . $line->prenom), 'errors');
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

			if ($models == 'attestation_trainee') {
				$file = $conf->agefodd->dir_output . '/' . 'attestation_trainee_' . $line->stagerowid . '.pdf';
			} elseif ($models == 'attestationendtraining_trainee') {
				$file = $conf->agefodd->dir_output . '/' . 'attestationendtraining_trainee_' . $line->stagerowid . '.pdf';
			}

			if (! file_exists($file))
				$sendmail_check = false;

            $TSentFile[$agf_trainee->id] = $file;

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

            $TSentFile[$agf_trainee->id] = $file;

			$filepath = array (
					$file
			);
			$filename = array (
					basename($file)
			);
			$mimetype = array (
					dol_mimetype($file)
			);
        } elseif ($models == 'fiche_presence_trainee_trainee') {
            $subject = $langs->transnoentities('AgfSendFeuillePresence', $object->formintitule);
            $message = str_replace('\n', "\n", $langs->transnoentities('AgfSendFeuillePresenceBody', $object->formintitule));

            $actiontypecode = 'AC_AGF_PRES';
            $actionmsg = $langs->trans('MailSentBy') . ' ' . $from . ' ' . $langs->trans('To') . ' ' . $send_email . ".\n";
            if ($message) {
                $actionmsg .= $langs->trans('MailTopic') . ": " . $subject . "\n";
                $actionmsg .= $langs->trans('TextUsedInTheMessageBody') . ":\n";
                $actionmsg .= $message;
            }
            $actionmsg2 = $langs->trans('ActionFICHEPRESENCE_SENTBYMAIL');

            $file = $conf->agefodd->dir_output . '/' . 'fiche_presence_trainee_trainee_' . $line->stagerowid . '.pdf';

            if (! file_exists($file))
                $sendmail_check = false;

            $TSentFile[$agf_trainee->id] = $file;

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

		$parameters = array(
			'contact_trainee' =>& $contact_trainee,
			'subject' =>& $subject,
			'send_email' =>& $send_email,
			'from' =>& $from,
			'message' =>& $message,
			'filepath' =>& $filepath,
			'mimetype' =>& $mimetype,
			'filename' =>& $filename,
			'sendtocc' =>& $sendtocc,
			'sendmail_check' =>& $sendmail_check
		);

		$reshook = $hookmanager->executeHooks('sendMassmail', $parameters, $agf_trainee, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		if (empty($reshook)) {

			if ($sendmail_check == true) {
				// Create form object
				include_once(DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php');
				$formmail = new FormMail($db);

				if (!empty($conf->global->FCKEDITOR_ENABLE_MAIL)) {
					$message = str_replace("\n", "<BR>", $message);
				}

				$message .= $user->signature;

				// Envoi de la fiche
				require_once(DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');
				$mailfile = new CMailFile($subject, $send_email, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', 1, -1);
				if ($mailfile->error) {
					setEventMessage($mailfile->error, 'errors');
				} else {
					$result = $mailfile->sendfile();
					if ($result) {
						setEventMessage($langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($send_email, 2)), 'mesgs');

						$error = 0;

						$object->actiontypecode = $actiontypecode;
						$object->actionmsg = $actionmsg;
						$object->actionmsg2 = $actionmsg2;
						$object->fk_element = $object->id;
						$object->elementtype = $object->element;

						/* Appel des triggers */
						include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
						$interface = new Interfaces($db);

						if ($models == 'convocation_trainee') {
							$result = $interface->run_triggers('CONVOCATION_SENTBYMAIL', $object, $user, $langs, $conf);
						} elseif ($models == 'attestation_trainee' || $models == 'attestationendtraining_trainee') {
							$result = $interface->run_triggers('ATTESTATION_SENTBYMAIL', $object, $user, $langs, $conf);
						} elseif ($models == 'fiche_presence_trainee_trainee') {
						    $result = $interface->run_triggers('FICHEPRESENCE_SENTBYMAIL', $object, $user, $langs, $conf);
                        }
						if ($result < 0) {
							$error++;
							$object->errors = $interface->errors;
						}
						// Fin appel triggers

						if ($error) {
							setEventMessage($object->errors, 'errors');
						} else {
							$i++;
							$action = '';
						}
					} else {
						$langs->load("other");
						if ($mailfile->error) {
							setEventMessage($langs->transnoentities('ErrorFailedToSendMail', dol_escape_htmltag($from), $send_email) . ":<br/>" . $mailfile->error, 'errors');
							dol_syslog($langs->trans('ErrorFailedToSendMail', $from, $send_email) . ' : ' . $mailfile->error);
						} else {
							setEventMessage('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS', 'errors');
						}
					}
				}
			}
		}
	}

	$agf_session = new Agsession($db);
	$agf_session->fetch($id);

	$parameters = array(
	    'TSentFile' => $TSentFile,
        'from' => $from,
        'mimetype' => $mimetype,
        'sendmail_check' => &$sendmail_check
    );
	$reshook = $hookmanager->executeHooks('afterSendMassMail', $parameters, $agf_session, $action); // Note that $action and $object may have been modified by some hooks
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if ($action == 'confirm_generateall' && $user->rights->agefodd->creer && $confirm=='yes') {
	// Define output language

	$typemodel = GETPOST('typemodel', 'none');
	$outputlangs = $langs;
	$newlang = GETPOST('lang_id', 'alpha');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))
		$newlang = $object->thirdparty->default_lang;
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

		if ((($typemodel == 'attestation_trainee'  || $typemodel == 'attestationendtraining_trainee') && ($line->status_in_session == 3 || $line->status_in_session == 4))
				|| ($typemodel == 'convocation_trainee') || ($typemodel == 'fiche_presence_trainee_trainee')) {
			$file = $typemodel . '_' . $line->stagerowid . '.pdf';

			$typemodel_override = $typemodel;
			// this configuration variable is designed like
			// standard_model_name:new_model_name&standard_model_name:new_model_name&....
			if (! empty($conf->global->AGF_PDF_MODEL_OVERRIDE) && ($typemodel != 'convention')) {
				$modelarray = explode('&', $conf->global->AGF_PDF_MODEL_OVERRIDE);
				if (is_array($modelarray) && count($modelarray) > 0) {
					foreach ( $modelarray as $modeloveride ) {
						$modeloverridearray = explode(':', $modeloveride);
						if (is_array($modeloverridearray) && count($modeloverridearray) > 0) {
							if ($modeloverridearray[0] == $typemodel) {
								$typemodel_override = $modeloverridearray[1];
							}
						}
					}
				}
			}

			$id_external_model=GETPOST('id_external_model_confirm', 'none');
			if (!empty($id_external_model) || strpos($typemodel, 'rfltr_agefodd') !== false) {
				$path_external_model = '/referenceletters/core/modules/referenceletters/pdf/pdf_rfltr_agefodd.modules.php';
				if(strpos($typemodel, 'rfltr_agefodd') !== false) $id_external_model= (int)strtr($typemodel, array('rfltr_agefodd_'=>''));
			}

			$result = agf_pdf_create($db, $id, '', $typemodel_override, $outputlangs, $file, $line->stagerowid, $cour, $path_external_model, $id_external_model);
		} elseif ($typemodel == 'attestation_trainee' || $typemodel == 'attestationendtraining_trainee') {
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


llxHeader('', $langs->trans("AgfSessionDetail"));

if(!empty($conf->referenceletters->enabled)) {
	dol_include_once('/referenceletters/class/referenceletters_tools.class.php');
	if (class_exists('RfltrTools') && method_exists('RfltrTools','print_js_external_models')) {
		RfltrTools::print_js_external_models('document_by_trainee');
	}
}

$form = new Form($db);
$formAgefodd = new FormAgefodd($db);
$formmail = new FormMail($db);

if (! empty($id)) {
	$agf = new Agsession($db);
	$agf->fetch($id);

	// Display View mode
	$head = session_prepare_head($agf);

	dol_fiche_head($head, 'document_trainee', $langs->trans("AgfSessionDetail"), 0, 'generic');

	dol_agefodd_banner_tab($agf, 'id');
	print '<div class="underbanner clearboth"></div>';

	$result = $agf->fetch_societe_per_session($id);

	if ($result>0) {
		$idform = $agf->formid;

		// Put user on the right action block after reload
		if (! empty($session_trainee_id)) {
			print '<script type="text/javascript">
					jQuery(document).ready(function () {
						jQuery(function() {
							if(typeof $("#sessiontraineeid' . $session_trainee_id . '").val() != "undefined") {
				    				$(\'html, body\').animate({scrollTop: $("#sessiontraineeid' . $session_trainee_id . '").offset().top}, 500,\'easeInOutCubic\');
							}
						});
					});
					</script> ';
		}

		/*
		 * Formulaire d'envoi des documents
		*/
		if ($action == 'presend_attestation_trainee' || $action == 'presend_convocation_trainee' || $action == 'presend_attestationendtraining_trainee' || $action == 'presend_fichepres_trainee_trainee') {

			if ($action == 'presend_attestation_trainee') {
				$filename = 'attestation_trainee_' . $session_trainee_id . '.pdf';
			} elseif ($action == 'presend_convocation_trainee') {
				$filename = 'convocation_trainee_' . $session_trainee_id . '.pdf';
			} elseif ($action == 'presend_attestationendtraining_trainee') {
				$filename = 'attestationendtraining_trainee_' . $session_trainee_id . '.pdf';
			} elseif ($action == 'presend_fichepres_trainee_trainee') {
			    $filename = 'fiche_presence_trainee_trainee_' . $session_trainee_id . '.pdf';
            }

			if ($filename) {
				$file = $conf->agefodd->dir_output . '/' . $filename;
			}

			// Init list of files
			if (GETPOST("mode", 'none') == 'init') {
				$formmail->clear_attached_files();
				if ($action == 'presend_convocation_trainee') {
					$formmail->add_attached_files($file, basename($file), dol_mimetype($file));
					if((float) DOL_VERSION >= 7.0) $formmail->param['fileinit'][] = $file;
                    else $formmail->param['fileinit'] = $file;
				} elseif ($action == 'presend_attestation_trainee') {
					$formmail->add_attached_files($file, basename($file), dol_mimetype($file));
					if((float) DOL_VERSION >= 7.0) $formmail->param['fileinit'][] = $file;
                    else $formmail->param['fileinit'] = $file;
				} elseif ($action == 'presend_attestationendtraining_trainee') {
					$formmail->add_attached_files($file, basename($file), dol_mimetype($file));
					if((float) DOL_VERSION >= 7.0) $formmail->param['fileinit'][] = $file;
                    else $formmail->param['fileinit'] = $file;
				} elseif ($action == 'presend_fichepres_trainee_trainee') {
                    $formmail->add_attached_files($file, basename($file), dol_mimetype($file));
                    if((float) DOL_VERSION >= 7.0) $formmail->param['fileinit'][] = $file;
                    else $formmail->param['fileinit'] = $file;
                }
			}
			else
			{
				$newfilearray = $formmail->get_attached_files();
				if (!empty($newfilearray['paths']))
				{
					foreach ($newfilearray['paths'] as $key => $path)
					{
						$formmail->add_attached_files($path, basename($path), dol_mimetype($path));
					}
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
			$formmail->withfile = 2;

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
			} elseif ($action == "presend_attestationendtraining_trainee") {
				$formmail->withtopic = $langs->trans('AgfAttestationEndTraining', '__FORMINTITULE__');
				$formmail->withbody = $langs->trans('AgfSendAttestationBody', '__FORMINTITULE__');
				$formmail->param ['models'] = 'attestationendtraining_trainee';
				$formmail->param ['pre_action'] = 'presend_attestationendtraining_trainee';
			} elseif ($action == 'presend_fichepres_trainee_trainee') {
                $formmail->withtopic = $langs->trans('AgfFichePresence', '__FORMINTITULE__');
                $formmail->withbody = $langs->trans('AgfSendFichePresenceBody', '__FORMINTITULE__');
                $formmail->param ['models'] = 'fiche_presence_trainee_trainee';
                $formmail->param ['pre_action'] = 'presend_fichepres_trainee_trainee';
            }

			if (! empty($conf->global->FCKEDITOR_ENABLE_MAIL)) {
				$formmail->withbody = str_replace('\n', '<BR>', $formmail->withbody);
			}

			$withto = array ();
			$withtoname = array ();
			$withtocompanyname=array();


			// Trainee List
			$agf_trainnees = new Agefodd_session_stagiaire($db);
			$agf_trainnees->fetch($session_trainee_id);

			$agf_trainee = new Agefodd_stagiaire($db);
			$agf_trainee->fetch($agf_trainnees->fk_stagiaire);

			$contact_trainee = new Contact($db);
			$contact_trainee->fetch($agf_trainee->fk_socpeople);

			// Send to company
			$thirdpartyid = 0;
			if (! empty($agf_trainee->socid)) {
				$agf_trainee->fetch_thirdparty();
				$thirdpartyid = $agf_trainee->thirdparty->id;
				$send_email = $agf_trainee->thirdparty->email;
				$companyname = $agf_trainee->thirdparty->name;
			} else {
				$contact_trainee->fetch_thirdparty();
				if (! empty($contact_trainee->thirdparty->id)) {
					$thirdpartyid = $contact_trainee->thirdparty->id;
					$send_email = $contact_trainee->thirdparty->email;
					$companyname = $contact_trainee->thirdparty->name;
				}
			}
			if (! empty($thirdpartyid)) {
				$withto[$thirdpartyid . '_third'] = $companyname . ' - ' . $send_email;
				$withtocompanyname[$thirdpartyid] = $companyname;
			}
			if (!empty($agf_trainee->fk_socpeople)) {
				$withto[$agf_trainee->fk_socpeople . '_socp'] = $agf_trainee->nom . ' ' . $agf_trainee->prenom . ' - ' . $agf_trainee->mail;
				$withtoname[] = $agf_trainee->nom . ' ' . $agf_trainee->prenom;
			} else {

				if(empty($conf->global->AGF_FILL_SENDTO_WITH_TRAINEE_MAIL_IF_NOT_SOCPEOPLE)) {
					setEventMessage($langs->trans('AgfTraineeIsNotAContact',$agf_trainee->nom . ' ' . $agf_trainee->prenom . ' - ' . $agf_trainee->mail ),'warnings');
				} else {
					?>
					<script type="text/javascript">
						$(document).ready(function() {
							$('#sendto').val('<?php echo $agf_trainee->mail; ?>');
						});
					</script>
					<?php
				}

			}
			if (! empty($withto)) {
				$formmail->withto = $withto;
			}

			$formmail->withdeliveryreceipt = 1;

			$formmail->withbody .= "\n\n\n__SIGNATURE__\n";

			// Tableau des substitutions
			if (! empty($agf->intitule_custo)) {
				$formmail->substit ['__FORMINTITULE__'] = $agf->intitule_custo;
			} else {
				$formmail->substit ['__FORMINTITULE__'] = $agf->formintitule;
			}

                        $date_conv = $agf->libSessionDate('daytext');
                        $formmail->substit['__FORMDATESESSION__'] = $date_conv;

			if (is_array($withtocompanyname) && count($withtocompanyname)>0) {
				if (! empty($conf->global->FCKEDITOR_ENABLE_MAIL)) {
					$formmail->substit['__THIRDPARTY_NAME__'] = implode('<BR>',$withtocompanyname);
				} else {
					$formmail->substit['__THIRDPARTY_NAME__'] = implode(', ',$withtocompanyname);
				}
			}

			if (is_array($withtoname) && count($withtoname)>0) {
				if (! empty($conf->global->FCKEDITOR_ENABLE_MAIL)) {
					$formmail->substit['__CONTACTCIVNAME__'] = implode('<BR>',$withtoname);
				} else {
					$formmail->substit['__CONTACTCIVNAME__'] = implode(', ',$withtoname);
				}

			}

			$formmail->substit ['__SIGNATURE__'] = $formmail->substit['__USER_SIGNATURE__'] = $user->signature;
			$formmail->substit ['__PERSONALIZED__'] = '';

			// Tableau des parametres complementaires
			$formmail->param ['action'] = 'send';
			$formmail->param ['sessiontraineeid'] = $session_trainee_id;
			$formmail->param ['id'] = $agf->id;
			$formmail->param ['models_id'] = GETPOST('modelmailselected', 'none');
            $formmail->param ['pre_action'] = $action;
			$formmail->param ['returnurl'] = $_SERVER ["PHP_SELF"] . '?id=' . $agf->id;

			if ($action == 'presend_convocation_trainee') {
				print_fiche_titre($langs->trans('AgfSendDocuments'), '', dol_buildpath('/agefodd/img/mail_generic.png', 1), 1);
			} elseif ($action == 'presend_attestation_trainee' || $action == 'presend_attestationendtraining_trainee') {
				print_fiche_titre($langs->trans('AgfSendDocuments'), '', dol_buildpath('/agefodd/img/mail_generic.png', 1), 1);
			}

			if (GETPOST('mode', 'none') != 'init') $formmail->param['fileinit'] = $formmail->get_attached_files()['paths'];
			$formmail->show_form();

			if (! empty($mesg)) {
				setEventMessage($mesg, $style_mesg);
			}
		} else {

			$agf_trainee = new Agefodd_session_stagiaire($db);
			$result = $agf_trainee->fetch_stagiaire_per_session($id);
			if ($result < 0) {
				setEventMessage($agf_trainee->error, 'errors');
			}

			if ($action == 'generateall' && $conf->referenceletters->enabled) {

				if (class_exists('RfltrTools') && method_exists('RfltrTools','getAgefoddModelList')) {
					$TModels = RfltrTools::getAgefoddModelList();
					if (array_key_exists('rfltr_agefodd_'.GETPOST('typemodel', 'none'), $TModels)) {
						$model_array=$TModels['rfltr_agefodd_'.GETPOST('typemodel', 'none')];
					}
				}
				$model_array[0]=$langs->trans('AgfDocModelStandard');
				$formquestion = array(
						array('type' => 'select','name' => 'id_external_model_confirm','label' => $langs->trans("AgfModels"),'values' => $model_array),
						array('type' => 'hidden','name' => 'typemodel','value' => GETPOST('typemodel', 'none')),
				);
				print $form->formconfirm($_SERVER['PHP_SELF'] . "?id=" . $id, $langs->trans("AgfSelectDocEditModel"), '', "confirm_generateall", $formquestion, '', 1, 210, 600);
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
					document_line($langs->trans("AgfPDFConvocation"), 'convocation_trainee', $agf_trainee->lines [$i]->stagerowid);
					document_line($langs->trans("AgfPDFFichePresence"), 'fiche_presence_trainee_trainee', $agf_trainee->lines[$i]->stagerowid);

					// After training session
					print '<tr><td colspan=3 style="background-color:#d5baa8;">' . $langs->trans("AgfAfterTraining") . '</td></tr>' . "\n";
					document_line($langs->trans("AgfSendAttestation"), "attestation_trainee", $agf_trainee->lines [$i]->stagerowid);
					document_line($langs->trans("AgfAttestationEndTraining"), "attestationendtraining_trainee", $agf_trainee->lines [$i]->stagerowid);

					print '</table>';
					if ($i < $linecount)
						print '&nbsp;' . "\n";
				}
			}

			print '</div>' . "\n";

		}
	} elseif ($result==0) {
	    print '<div style="text-align:center"><br>'.$langs->trans('AgfThirdparyMandatory').'</div>';
	} else {
	    setEventMessages($agf->error, null, 'errors');
	}

	if (!empty($linecount)){

		//find if docedit model exits
		$docedit_convtrainee_exists = $docedit_attestrainee_exists = $docedit_attesendtraining_trainee_exists = $docedit_fichepres_trainee_exists = false;
		if ($conf->referenceletters->enabled) {
			if (class_exists('RfltrTools') && method_exists('RfltrTools','getAgefoddModelList')) {
				$TModels = RfltrTools::getAgefoddModelList();
                if (array_key_exists('rfltr_agefodd_convocation_trainee', $TModels)) {
                    $docedit_convtrainee_exists=true;
                }
                if (array_key_exists('rfltr_agefodd_fichepres_trainee', $TModels)) {
                    $docedit_fichepres_trainee_exists=true;
                }
				if (array_key_exists('rfltr_agefodd_attestation_trainee', $TModels)) {
					$docedit_attestrainee_exists=true;
				}
				if (array_key_exists('rfltr_agefodd_attestationendtraining_trainee', $TModels)) {
					$docedit_attesendtraining_trainee_exists=true;
				}
			}
		}

	    print '<div class="tabsAction">';
	    print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action='.((!$docedit_convtrainee_exists)?'confirm_generateall&confirm=yes':'generateall').'&typemodel=convocation_trainee">' . $langs->trans('AgfGenerateAllConvocation') . '</a>';
	    print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action='.((!$docedit_fichepres_trainee_exists)?'confirm_generateall&confirm=yes':'generateall').'&typemodel=fiche_presence_trainee_trainee">' . $langs->trans('AgfGenerateAllFichePresence') . '</a>';
	    print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action='.((!$docedit_attestrainee_exists)?'confirm_generateall&confirm=yes':'generateall').'&typemodel=attestation_trainee">' . $langs->trans('AgfGenerateAllAttestation') . '</a>';
	    print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action='.((!$docedit_attesendtraining_trainee_exists)?'confirm_generateall&confirm=yes':'generateall').'&typemodel=attestationendtraining_trainee">' . $langs->trans('AgfGenerateAllAttestationEndTraining') . '</a>';
	    print '<BR>';
	    print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=sendmassmail&typemodel=convocation_trainee">' . $langs->trans('AgfSendMailAllConvocation') . '</a>';
	    print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=sendmassmail&typemodel=fiche_presence_trainee_trainee">' . $langs->trans('AgfSendMailAllFichePresence') . '</a>';
	    print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=sendmassmail&typemodel=attestation_trainee">' . $langs->trans('AgfSendMailAllAttestation') . '</a>';
	    print '<a class="butAction" href="' . $_SERVER ['PHP_SELF'] . '?id=' . $id . '&action=sendmassmail&typemodel=attestationendtraining_trainee">' . $langs->trans('AgfSendMailAllAttestationEndTraining') . '</a>';
	    print '</div>';
	} else {
	    print '<div style="text-align:center"><br>'.$langs->trans('AgfNobody').'</div>';
	    print '<div class="tabsAction">';
	    if (($user->rights->agefodd->creer || $user->rights->agefodd->modifier) && $agf->status != 4) {
	        print '<a class="butAction" href="' . dol_buildpath('/agefodd/session/subscribers.php', 1) . '?action=edit&id=' . $id . '">' . $langs->trans('AgfModifyTrainee') . '</a>';
	    } else {
	        print '<a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('AgfModifyTrainee') . '</a>';
	    }
	    print '</div>';
	}
}

llxFooter();
$db->close();
