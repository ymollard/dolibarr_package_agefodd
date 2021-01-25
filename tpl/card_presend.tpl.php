<?php
/* Copyright (C)    2017-2018 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/*
 * Code to ouput content when action is presend
 *
 * $trackid must be defined
 * $modelmail
 * $defaulttopic
 * $diroutput
 * $arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}



if ($action == 'presend')
{
	$langs->load("mails");

	$titreform='SendMail';

	//$agf->fetch_projet();

	if (! in_array($agf->element, array('societe', 'user', 'member')))
	{
		// TODO get also the main_lastdoc field of $agf. If not found, try to guess with following code

		$ref = dol_sanitizeFileName($agf->ref);
		include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
		// Special case
		if ($agf->element == 'invoice_supplier')
		{
			$fileparams = dol_most_recent_file($diroutput . '/' . get_exdir($agf->id,2,0,0,$agf,$agf->element).$ref, preg_quote($ref,'/').'([^\-])+');
		}
		else
		{
			$fileparams = dol_most_recent_file($diroutput . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
		}

		$file = $fileparams['fullname'];
	}

	// Define output language
	$outputlangs = $langs;
	$newlang = '';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
	{
		$newlang = $_REQUEST['lang_id'];
	}
	if ($conf->global->MAIN_MULTILANGS && empty($newlang))
	{
		$newlang = $agf->thirdparty->default_lang;
	}

	if (!empty($newlang))
	{
		$outputlangs = new Translate('', $conf);
		$outputlangs->setDefaultLang($newlang);
		// Load traductions files requiredby by page
		$outputlangs->loadLangs(array('commercial','bills','orders','contracts','members','propal','products','supplier_proposal','interventions'));
	}

	$topicmail='';
	if (empty($agf->ref_client)) {
		$topicmail = $outputlangs->trans($defaulttopic, '__REF__');
	} else if (! empty($agf->ref_client)) {
		$topicmail = $outputlangs->trans($defaulttopic, '__REF__ (__REFCLIENT__)');
	}

	// Build document if it not exists
	$forcebuilddoc=true;
	if (in_array($agf->element, array('societe', 'user', 'member'))) $forcebuilddoc=false;
	if ($agf->element == 'invoice_supplier' && empty($conf->global->INVOICE_SUPPLIER_ADDON_PDF)) $forcebuilddoc=false;
	if ($forcebuilddoc)    // If there is no default value for supplier invoice, we do not generate file, even if modelpdf was set by a manual generation
	{
		if ((! $file || ! is_readable($file)) && method_exists($agf, 'generateDocument'))
		{
			$result = $agf->generateDocument(GETPOST('model', 'none') ? GETPOST('model', 'none') : $agf->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result < 0) {
				dol_print_error($db, $agf->error, $agf->errors);
				exit();
			}
			if ($agf->element == 'invoice_supplier')
			{
				$fileparams = dol_most_recent_file($diroutput . '/' . get_exdir($agf->id,2,0,0,$agf,$agf->element).$ref, preg_quote($ref,'/').'([^\-])+');
			}
			else
			{
				$fileparams = dol_most_recent_file($diroutput . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
			}

			$file = $fileparams['fullname'];
		}
	}

	print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
	print '<div class="clearboth"></div>';
	print '<br>';
	print load_fiche_titre($langs->trans($titreform));

	dol_fiche_head('');

	// Create form for email
	include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);

	$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
	$formmail->fromtype = (GETPOST('fromtype', 'none')?GETPOST('fromtype', 'none'):(!empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE)?$conf->global->MAIN_MAIL_DEFAULT_FROMTYPE:'user'));

	if ($formmail->fromtype === 'user')
	{
		$formmail->fromid = $user->id;
	}
	$formmail->trackid=$trackid;
	if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
	{
		include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$formmail->frommail=dolAddEmailTrackId($formmail->frommail, $trackid);
	}
	$formmail->withfrom = 1;

	// Fill list of recipient with email inside <>.
	$liste = array();
	if ($agf->element == 'expensereport')
	{
		$fuser = new User($db);
		$fuser->fetch($agf->fk_user_author);
		$liste['thirdparty'] = $fuser->getFullName($langs)." <".$fuser->email.">";
	}
	elseif ($agf->element == 'societe')
	{
		foreach ($agf->thirdparty_and_contact_email_array(1) as $key => $value) {
			$liste[$key] = $value;
		}
	}
	elseif ($agf->element == 'user' || $agf->element == 'member')
	{
		$liste['thirdparty'] = $agf->getFullName($langs)." <".$agf->email.">";
	}
	else
	{
		if (is_object($agf->thirdparty))
		{
			foreach ($agf->thirdparty->thirdparty_and_contact_email_array(1) as $key => $value) {
				$liste[$key] = $value;
			}
		}
	}
	if (!empty($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT)) {
		$listeuser=array();
		$fuserdest = new User($db);

		$result= $fuserdest->fetchAll('ASC', 't.lastname', 0, 0, array('customsql'=>'t.statut=1 AND t.employee=1 AND t.email IS NOT NULL AND t.email<>\'\''));
		if ($result>0 && is_array($fuserdest->users) && count($fuserdest->users)>0) {
			foreach($fuserdest->users as $uuserdest) {
				$listeuser[$uuserdest->id] = $uuserdest->user_get_property($uuserdest->id,'email');
			}
		} elseif ($result<0) {
			setEventMessages(null, $fuserdest->errors,'errors');
		}
		if (count($listeuser)>0) {
			$formmail->withtouser = $listeuser;
			$formmail->withtoccuser = $listeuser;
		}
	}

	$formmail->withto = GETPOST('sendto', 'none') ? GETPOST('sendto', 'none') : $liste;
	$formmail->withtocc = $liste;
	$formmail->withtoccc = $conf->global->MAIN_EMAIL_USECCC;
	$formmail->withtopic = $topicmail;
	$formmail->withfile = 2;
	$formmail->withbody = 1;
	$formmail->withdeliveryreceipt = 1;
	$formmail->withcancel = 1;

	//$arrayoffamiliestoexclude=array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...);
	if (! isset($arrayoffamiliestoexclude)) $arrayoffamiliestoexclude=null;

	// Make substitution in email content
	$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, $arrayoffamiliestoexclude, $agf);
	$substitutionarray['__CHECK_READ__'] = (is_object($agf) && is_object($agf->thirdparty)) ? '<img src="' . DOL_MAIN_URL_ROOT . '/public/emailing/mailing-read.php?tag=' . $agf->thirdparty->tag . '&securitykey=' . urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY) . '" width="1" height="1" style="width:1px;height:1px" border="0"/>' : '';
	$substitutionarray['__PERSONALIZED__'] = '';	// deprecated
	$substitutionarray['__CONTACTCIVNAME__'] = '';
	$parameters = array(
		'mode' => 'formemail'
	);
	complete_substitutions_array($substitutionarray, $outputlangs, $agf, $parameters);

	// Find the good contact adress
	$custcontact = '';
	$contactarr = array();
	/*$contactarr = $agf->liste_contact(- 1, 'external');

	if (is_array($contactarr) && count($contactarr) > 0) {
		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		$contactstatic = new Contact($db);

		foreach ($contactarr as $contact) {
			$contactstatic->fetch($contact['id']);
			$substitutionarray['__CONTACT_NAME_'.$contact['code'].'__'] = $contactstatic->getFullName($langs, 1);
		}
	}*/

	// Tableau des substitutions
	$formmail->substit = $substitutionarray;

	// Tableau des parametres complementaires
	$formmail->param['action'] = 'send';
	$formmail->param['models'] = $modelmail;
	$formmail->param['models_id']=GETPOST('modelmailselected','int');
	$formmail->param['id'] = $agf->id;
	$formmail->param['returnurl'] = $_SERVER["PHP_SELF"] . '?id=' . $agf->id;
	$formmail->param['fileinit'] = array($file);

	// Show form
	print $formmail->get_form();

	dol_fiche_end();
}

