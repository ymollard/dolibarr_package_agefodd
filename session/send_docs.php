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

dol_include_once('/agefodd/session/class/agefodd_session.class.php');
dol_include_once('/agefodd/session/class/agefodd_sessadm.class.php');
dol_include_once('/agefodd/class/agefodd_facture.class.php');
dol_include_once('/agefodd/session/class/agefodd_convention.class.php');
dol_include_once('/agefodd/core/modules/agefodd/modules_agefodd.php');
dol_include_once('/agefodd/core/class/html.formagefodd.class.php');
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



llxHeader();

$form = new Form($db);
$formmail = new FormMail($db);
$formAgefodd = new FormAgefodd($db);

dol_htmloutput_mesg($mesg);

if (!empty($id))
{
	$agf = new Agefodd_session($db);
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

		print '<table class="border" width="100%">'."\n";

		print '<tr class="liste_titre">'."\n";
		print '<td colspan=3>';
		print $langs->trans("AgfSendCommonDocs").'</td>'."\n";
		print '</tr>'."\n";


		print '<tr><td colspan=3 style="background-color:#d5baa8;">'.$langs->trans("AgfBeforeTraining").'</td></tr>'."\n";

		/*
		 * Envoi fiche pédagogique
		 */
		print '<tr style="height:14px">'."\n";
		print '<td colspan="2" style="border-right:0px;">';
		$file = 'fiche_pedago_'.$agf->fk_formation_catalogue.'.pdf';
print $file;

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
	$formmail->withtopic=$langs->trans('SendInterventionRef','__FICHINTERREF__');
	$formmail->withfile=2;
	$formmail->withbody=1;
	$formmail->withdeliveryreceipt=1;
	$formmail->withcancel=1;

	// Tableau des substitutions
	$formmail->substit['__FICHINTERREF__']=$object->ref;
	$formmail->substit['__SIGNATURE__']=$user->signature;
	$formmail->substit['__PERSONALIZED__']='';
	// Tableau des parametres complementaires
	$formmail->param['action']='send';
	$formmail->param['models']='fichinter_send';
	$formmail->param['fichinter_id']=$object->id;
	$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;

	// Init list of files
	if (GETPOST("mode")=='init')
	{
		$formmail->clear_attached_files();
		$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
	}
	$formmail->show_form();

		//document_send_line("Convocation", 2, 'convocation');
		document_line("Réglement intérieur", 2, 'reglement');
		document_line("Fiche pédagogique", 2, 'fiche_pedago');
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

llxFooter('');
?>
