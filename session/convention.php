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
 *  \file       	/agefodd/convention_fiche.php
 *  \brief      	Page fiche convention de formation
 *  \version		$Id$
 */

$res=@include("../main.inc.php");					// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/lib/agefodd.lib.php');
dol_include_once('/agefodd/session/class/agefodd_session.class.php');
dol_include_once('/agefodd/session/class/agefodd_session_calendrier.class.php');
dol_include_once('/agefodd/training/class/agefodd_formation_catalogue.class.php');
dol_include_once('/agefodd/class/agefodd_facture.class.php');
dol_include_once('/agefodd/session/class/agefodd_convention.class.php');
dol_include_once('/agefodd/contact/class/agefodd_contact.class.php');
dol_include_once('/agefodd/site/class/agefodd_place.class.php');

// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$mesg = '';


/*
 * Actions delete
 */
if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes" && $user->rights->agefodd->creer)
{
	//$_GET["id"] = $convid-sessid
	$GET_array = explode('-',$_GET["id"]);

	$agf = new Agefodd_convention($db);
	$result = $agf->remove($GET_array[0]);
	
	if ($result > 0)
	{
		$db->commit();
		Header ( "Location: s_doc_fiche.php?id=".$GET_array[1]);
		exit;
	}
	else
	{
		$db->rollback();
		dol_syslog("CommonObject::agefodd error=".$error, LOG_ERR);
	}
}


/*
 * Actions archive/active (convention de formation)
 */
if ($_POST["action"] == 'arch_confirm_delete' && $user->rights->agefodd->creer)
{
	if ($_POST["confirm"] == "yes")
	{
		$agf = new Agefodd_convention($db);
	
		$result = $agf->fetch($_GET["convid"]);
	
		$agf->archive = $_GET["arch"];
		$result = $agf->update($user->id);
	
		if ($result > 0)
		{
			$db->commit();
			Header ( "Location: convention_fiche.php?id=".$_GET["convid"]);
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
		Header ( "Location: s_doc_fiche.php?id=".$_GET["sessid"]);
		exit;
	}
}




/*
 * Action update (convention de formation)
 */
if ($_POST["action"] == 'update' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd_convention($db);

		$result = $agf->fetch(0, 0 , $_POST["convid"]);

		//$agf = new Agefodd_convention($db);

		if (!empty($_POST["intro1"])) $agf->intro1 = $_POST["intro1"];
		if (!empty($_POST["intro2"])) $agf->intro2 = $_POST["intro2"];
		if (!empty($_POST["art1"])) $agf->art1 = $_POST["art1"];
		if (!empty($_POST["art2"])) $agf->art2 = $_POST["art2"];
		if (!empty($_POST["art3"])) $agf->art3 = $_POST["art3"];
		if (!empty($_POST["art4"])) $agf->art4 = $_POST["art4"];
		if (!empty($_POST["art5"])) $agf->art4 = $_POST["art5"];
		if (!empty($_POST["art6"])) $agf->art6 = $_POST["art6"];
		if (!empty($_POST["art7"])) $agf->art7 = $_POST["art7"];
		if (!empty($_POST["art8"])) $agf->art8 = $_POST["art8"];
		if (!empty($_POST["sig"])) $agf->sig = $_POST["sig"];
		$agf->notes = $_POST["notes"];
		if (!empty($_POST["socid"])) $agf->socid = $_POST["socid"];
		if (!empty($_POST["sessid"])) $agf->sessid = $_POST["sessid"];
		$agf->rowid = $_POST["convid"];

		$result = $agf->update($user->id);

		if ($result > 0)
		{
			$db->commit();
			Header ( "Location: convention_fiche.php?convid=".$_POST["convid"]);
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
		Header ( "Location: convention_fiche.php?convid=".$_POST["convid"]);
		exit;
	}
}


/*
 * Action create (convention de formation)
 */

if ($_POST["action"] == 'create' && $user->rights->agefodd->creer)
{
	if (! $_POST["cancel"])
	{
		$agf = new Agefodd_convention($db);

		$agf->intro1 = $_POST["intro1"];
		$agf->intro2 = $_POST["intro2"];
		$agf->art1 = $_POST["art1"];
		$agf->art2 = $_POST["art2"];
		$agf->art3 = $_POST["art3"];
		$agf->art4 = $_POST["art4"];
		$agf->art5 = $_POST["art5"];
		$agf->art6 = $_POST["art6"];
		$agf->art7 = $_POST["art7"];
		$agf->art8 = $_POST["art8"];
		$agf->sig = $_POST["sig"];
		$agf->notes = $_POST["notes"];
		$agf->socid = $_POST["socid"];
		$agf->sessid = $_POST["sessid"];
		$agf->datec = $db->idate(mktime());
		
		$result = $agf->create($user->id);

		if ($result > 0)
		{
			$db->commit();
			Header ( "Location: convention_fiche.php?action=edit&convid=".$result);
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
		Header ( "Location: s_doc_fiche.php?id=".$_POST["sessid"]);
		exit;
	}
}



/*
 * View
 */

llxHeader();

$form = new Form($db);
dol_htmloutput_mesg($mesg);

$id = $_GET['id'];

// on récupére l'id  de la session dans la table convention
$agf_last = new Agefodd_convention($db);
$result = $agf_last->fetch_last_conv_per_socity($_GET["socid"]);
$convid = $_GET['convid'];



/*
 * Affichage de la fiche convention en mode création
 */
if ($_GET["action"] == 'create' && $user->rights->agefodd->creer)
{
	$sessid = $_GET['id'];

	

	$agf = new Agefodd_session($db);
	$resql = $agf->fetch($id);

	// On cherche si une convention de formation a déjà été faite pour ce client.
	// Si c'est le cas, on récupére les valeurs de cette convention pour en faire les valeurs par defaut.
	// Sinon on prends les valeurs par défault du script...
	$agf_last = new Agefodd_convention($db);
	$result = $agf_last->fetch_last_conv_per_socity($_GET["socid"]);
	if ($result > 0) 
	{
		$agf_conv = new Agefodd_convention($db);
		$result = $agf_conv->fetch($agf_last->sessid, $_GET["socid"]);
		if( $agf_last->sessid) $last_conv = 'ok';
	}

	// if agefodd contact exist
	$agf_contact = new Agefodd_contact($db);
	$resql2 = $agf_contact->fetch($_GET["socid"]);

	
	//intro1
	if ($agf_conv->intro1) $intro1 = $agf_conv->intro1;
	else
	{
		// On recupere les infos societe
		$agf_soc = new Societe($db);
		$result = $agf_soc->fetch($socid);

		$statut = getFormeJuridiqueLabel($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
		$intro1 = "La société ".$conf->global->MAIN_INFO_SOCIETE_NOM .', '.$statut." au capital de ";
		$intro1.= $conf->global->MAIN_INFO_CAPITAL." euros, dont le siège social est à ".$conf->global->MAIN_INFO_SOCIETE_VILLE;
		$intro1.= " (".$conf->global->MAIN_INFO_SOCIETE_CP."), immatriculée au Registre du Commerce et des Sociétés sous la référence ";
		$intro1.= $conf->global->MAIN_INFO_RCS;
		$intro1.= " et enregistré comme organisme de formation auprès de la préfecture de l'";
		$intro1.= AGF_ORGANISME_PREF." sous le numéro ".AGF_ORGANISME_NUM;
		$intro1.= ", représentée par Monsieur ".AGF_ORGANISME_REPRESENTANT.", dûment habilité à ce faire en sa qualité de gérant,";
	}
	
	//intro2
	if ($agf_conv->intro2) $intro2 = $agf_conv->intro2;
	else
	{
		// On recupere les infos societe
		$agf_soc = new Societe($db);
		$result = $agf_soc->fetch($_GET["socid"]);

		// intro2
		$intro2 = "La société ".$agf_soc->nom.", situé au ".$agf_soc->adresse." ".$agf_soc->cp." ".$agf_soc->ville.",";
		$intro2.= " d'identifiant SIRET". $agf_soc->siret;
		$intro2.= " et représenté par ";
		$intro2.= ucfirst(strtolower($agf_contact->civilite)).' '.$agf_contact->firstname.' '.$agf_contact->name;
		$intro2.= " dûment habilité à ce faire,";
	}

	//article 1
	//if ($agf_conv->art1) $art1 = $agf_conv->art1;
	if (false) $art1 = $agf_conv->art1;
	else
	{
		// Mise en page (Cf. fonction "liste_a_puce()" du fichier pdf_convention_modele.php)
		// Si la ligne commence par:
		// '!# ' aucune puce ne sera générée, la ligne commence sur la magre gauche
		// '# ', une puce de premier niveau est mis en place
		// '## ', une puce de second niveau est mis en place
		// '### ', une puce de troisième niveau est mis en place
		$art1 = "!# L'organisme accomplit l'action de formation suivante :"."\n";
		$art1.= '# Intitulé du stage : « '.$agf->formintitule.' »'."\n";
		$art1.= '# Objectifs :'."\n";
		$obj_peda = new Agefodd($db);
		$resql = $obj_peda->fetch_objpeda_per_formation($agf->formid);
		for ( $i = 0; $i < count($obj_peda->line); $i++)
		{
			$art1.= "## ".$obj_peda->line[$i]->intitule."\n";
		}
		$art1.= '# Programme et méthode : cf. annexe1 (fiche pédagogique).'."\n";
		$art1.= '# Type d\'action de formation : acquisition des connaissances.'."\n";
		$art1.= '# Date';
		if ($agf->dated == $agf->datef) $art1.= ": le ".dol_print_date($agf->datef);
		else $art1.= "s: du ".dol_print_date($agf->dated).' au '.dol_print_date($agf->datef);
		$art1.= "\n";

		// Durée de formation
		$art1.= '# Durée : '.$agf->duree.' heures, réparties de la façon suivante :'."\n";

		$calendrier = new Agefodd_sesscalendar($db);
		$resql = $calendrier->fetch_all($agf->id);
		$blocNumber = count($calendrier->line);
		$old_date = 0;
		$duree = 0;
		for ($i = 0; $i < $blocNumber; $i++)
		{
			if ($calendrier->line[$i]->date != $old_date)
			{
				if ($i > 0 ) $art1.= "), ";
				$arrayJour = explode('-', $calendrier->line[$i]->date);
				$mktime = mktime(0, 0, 0, $arrayJour[1], $arrayJour[2], $arrayJour[0]);
				setlocale(LC_TIME, 'fr_FR', 'fr_FR.utf8', 'fr');
				$jour = strftime("%A", $mktime);
				$art1.= $jour.' '.dol_print_date($calendrier->line[$i]->date).' (';
			}
			else $art1.= '/';
			$heured = ebi_time_array($calendrier->line[$i]->heured);
			$art1.= $heured['h'].':'.$heured['m'] ;
			$heuref = ebi_time_array($calendrier->line[$i]->heuref);
			$art1.= ' - '.$heuref['h'].':'.$heuref['m'];
			if ($i == $blocNumber - 1) $art1.=').'."\n";
			
			$old_date = $calendrier->line[$i]->date;
		}
	
		$stagiaires = new Agefodd_session($db);
		$stagiaires->fetch_stagiaire_per_session($id,$socid );
		$nbstag = count($stagiaires->line);
		$art1.= '# Effectif du stage : '.$nbstag.' personne';
		if ($nbstag > 1) $art1.= 's';
		$art1.= ".\n";
		// Adresse lieu de formation
		$agf_place = new Agefodd_splace($db);
		$resql3 = $agf_place->fetch($agf->placeid);
		$adresse = $agf_place->adresse.", ".$agf_place->cp." ".$agf_place->ville;
		$art1.= "# Lieu : salle de formation (".$agf_place->code.') située '.$adresse.'.';

	}

	// texte 2
	if ($agf_conv->art2) $art2 = $agf_conv->art2;
	else
	{
		$art2 = "Cf. annexe 1 (fiche pédagogique).";
	}

	// texte3
	if ($agf_conv->art3) $art3 = $agf_conv->art3;
	else
	{
		$art3 = "L'organisme formera le";
		($nbstag > 1) ? $art3.='s stagiaires ' : $art3.=' stagiaire ';
		for ($i= 0; $i < $nbstag; $i++)
		{
			$art3.= $stagiaires->line[$i]->nom.' '.$stagiaires->line[$i]->prenom;
			if ($i == $nbstag - 1) $art3.= '.';
			else
			{
				if ($i == $nbstag - 2) $art3.= ' et ';
				else  $art3.= ', ';
			}
		}
	}

	// texte 4
	if ($agf_conv->art4) $art4 = $agf_conv->art4;
	else
	{
		$art4 = "L'organisme déclare être assujetti à la TVA au sens de l'article 261-4-4°-a du CGI et des articles L.900-2 et R.950-4 du code du travail. \nEn contrepartie de cette action de formation, le client devra s'acquitter des sommes suivantes :";
	}

	// texte 5
	if ($agf_conv->art5) $art5 = $agf_conv->art5;
	else
	{
		$art5 = "La facture correspondant à la somme indiquée ci-dessus sera adressée, service fait, par l'organisme au client qui en règlera le montant sur le compte de l'organisme.";
	}

	//article 6
	if ($agf_conv->art6) $art6 = $agf_conv->art6;
	else
	{
		$art6 = "En application de l'article L 6354-1 du code du travail, il est convenu entre les signataires de la présente convention, que faute de réalisation totale ou partielle de la prestation de formation, l'organisme de formation remboursera au cocontractant les sommes qu'il aura indûment perçues de ce fait. C'est-à-dire les sommes qui ne correspondront pas à la réalisation de la prestation de formation.\n
La non réalisation totale de l'action due à la carence du prestataire ou au renoncement à la prestation par l'acheteur ne donnera pas lieu à une facturation au titre de la formation professionnelle continue.\n
La réalisation partielle de la prestation de formation, imputable ou non à l'organisme de formation ou à son client, ne donnera lieu qu'à facturation, au titre de la formation professionnelle continue, des sommes correspondants à la réalisation effective de la prestation.\n
En cas de dédit par le client à moins de 5 jours francs, avant le début de l'action mentionnée à l'Article 1, ou d'abandon en cours de formation par un ou plusieurs stagiaires, l'organisme retiendra sur le coût total, les sommes qu'il aura réellement dépensées ou engagées pour la réalisation de la dite action, conformément aux dispositions de l'Article L 920-9 du Code du Travail.";
	}

	//article 7
	if ($agf_conv->art7) $art7 = $agf_conv->art7;
	else
	{
		$art7 = "En cas de litige entre les deux parties, celles-ci s'engagent à rechercher préalablement une solution amiable.
En cas d'échec d'une solution négociée, les parties conviennent expressément d'attribuer compétence exclusive aux tribunaux de Montpellier.";
	}

	// Signature du client
	if ($agf_conv->sig) $sig = $agf_conv->sig;
	else
	{
		$sig = $agf_soc->nom."\n";
		$sig.= "représenté par ";
		$sig.= ucfirst(strtolower($agf_contact->civilite)).' '.$agf_contact->firstname.' '.$agf_contact->name." (*)";
	}

	print '<div class="warning">';
	($last_conv == 'ok') ? print $langs->trans("AgfConvLastWarning") : print $langs->trans("AgfConvDefaultWarning");
	print '</div>'."\n";
	print '<form name="create" action="convention_fiche.php" method="post">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
	print '<input type="hidden" name="action" value="create">'."\n";
	print '<input type="hidden" name="sessid" value="'.$id.'">'."\n";
	print '<input type="hidden" name="socid" value="'.$_GET["socid"].'">'."\n";

	print '<table class="border" width="100%">'."\n";

	print '<tr><td valign="top" width="200px">'.$langs->trans("AgfConventionIntro1").'</td>';
	print '<td><textarea name="intro1" rows="3" cols="0" class="flat" style="width:360px;">'.$intro1.'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfConventionIntro2").'</td>';
	print '<td><textarea name="intro2" rows="3" cols="0" class="flat" style="width:360px;">'.$intro2.'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfConventionArt1").'</td>';
	print '<td><textarea name="art1" rows="3" cols="0" class="flat" style="width:360px;">'.$art1.'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfConventionArt2").'</td>';
	print '<td><textarea name="art2" rows="3" cols="0" class="flat" style="width:360px;">'.$art2.'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfConventionArt3").'</td>';
	print '<td><textarea name="art3" rows="3" cols="0" class="flat" style="width:360px;">'.$art3.'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfConventionArt4").'</td>';
	print '<td><textarea name="art4" rows="3" cols="0" class="flat" style="width:360px;">'.$art4.'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfConventionArt5").'</td>';
	print '<td><textarea name="art5" rows="3" cols="0" class="flat" style="width:360px;">'.$art5.'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfConventionArt6").'</td>';
	print '<td><textarea name="art6" rows="3" cols="0" class="flat" style="width:360px;">'.$art6.'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfConventionArt7").'</td>';
	print '<td><textarea name="art7" rows="3" cols="0" class="flat" style="width:360px;">'.$art7.'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfConventionSig").'</td>';
	print '<td><textarea name="sig" rows="3" cols="0" class="flat" style="width:360px;">'.$sig.'</textarea></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AgfNote").'<br /><span style=" font-size:smaller; font-style:italic;">'.$langs->trans("AgfConvNotesExplic").'</span></td>';
	print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;"></textarea></td></tr>';
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
	// Affichage de la fiche convention
	$agf = new Agefodd_convention($db);
	$result = $agf->fetch(0, 0, $_GET["convid"]);


	if ($result)
	{

		$head = session_prepare_head($agf);
		
		dol_fiche_head($head, $hselected, $langs->trans("AgfConvention"), 0, 'bill');
		
		
		// Affichage en mode "édition"
		if ($_GET["action"] == 'edit')
		{

			print '<form name="update" action="convention_fiche.php?convid='.$_GET["convid"].'" method="post">'."\n";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
			print '<input type="hidden" name="action" value="update">'."\n";
			print '<input type="hidden" name="convid" value="'.$_GET["convid"].'">'."\n";
			print '<input type="hidden" name="sessid" value="'.$agf->sessid.'">'."\n";

			print '<table class="border" width="100%">'."\n";

			print '<tr><td valign="top" width="200px">'.$langs->trans("AgfConventionIntro1").'</td>';
			print '<td><textarea name="intro1" rows="3" cols="0" class="flat" style="width:360px;">'.$agf->intro1.'</textarea></td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionIntro2").'</td>';
			print '<td><textarea name="intro2" rows="3" cols="0" class="flat" style="width:360px;">'.$agf->intro2.'</textarea></td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionArt1").'</td>';
			print '<td><textarea name="art1" rows="3" cols="0" class="flat" style="width:360px;">'.$agf->art1.'</textarea></td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionArt2").'</td>';
			print '<td><textarea name="art2" rows="3" cols="0" class="flat" style="width:360px;">'.$agf->art2.'</textarea></td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionArt3").'</td>';
			print '<td><textarea name="art3" rows="3" cols="0" class="flat" style="width:360px;">'.$agf->art3.'</textarea></td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionArt4").'</td>';
			print '<td><textarea name="art4" rows="3" cols="0" class="flat" style="width:360px;">'.$agf->art4.'</textarea></td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionArt5").'</td>';
			print '<td><textarea name="art5" rows="3" cols="0" class="flat" style="width:360px;">'.$agf->art5.'</textarea></td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionArt6").'</td>';
			print '<td><textarea name="art6" rows="3" cols="0" class="flat" style="width:360px;">'.$agf->art6.'</textarea></td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionArt7").'</td>';
			print '<td><textarea name="art7" rows="3" cols="0" class="flat" style="width:360px;">'.$agf->art7.'</textarea></td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionSig").'</td>';
			print '<td><textarea name="sig" rows="3" cols="0" class="flat" style="width:360px;">'.$agf->sig.'</textarea></td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfNote").'<br /><span style=" font-size:smaller; font-style:italic;">'.$langs->trans("AgfConvNotesExplic").'</span></td>';
			print '<td><textarea name="notes" rows="3" cols="0" class="flat" style="width:360px;">'.$agf->notes.'</textarea></td></tr>';

			print '</table>';
			print '</div>';
			print '<table style=noborder align="right">';
			print '<tr><td align="center" colspan=2>';
			print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'"> &nbsp; ';
			print '<input type="submit" name="cancel" class="butActionDelete" value="'.$langs->trans("Cancel").'">';
			print '</td></tr>';
			print '</table>';
			print '</form>';
				
			print '</div>'."\n";
		}
		else
		{

			/*
			* Confirmation de la suppression
			*/
			if ($_GET["action"] == 'delete')
			{
				$ret=$form->form_confirm("convention_fiche.php?id=".$convid.'-'.$agf->sessid,$langs->trans("AgfDeleteConvention"),$langs->trans("AgfConfirmDeleteConvention"),"confirm_delete");
				if ($ret == 'html') print '<br>';
			}
			/*
			* Confirmation de l'archivage/activation suppression
			*/
			if (isset($_GET["arch"]))
			{
				$ret=$form->form_confirm("convention_fiche.php?arch=".$_GET["arch"]."&convid=".$convid,$langs->trans("AgfFormationArchiveChange"),$langs->trans("AgfConfirmArchiveChange"),"arch_confirm_delete");
				if ($ret == 'html') print '<br>';
			}

			print '<table class="border" width="100%">'."\n";

			print '<tr><td valign="top" width="200px">'.$langs->trans("AgfConventionIntro1").'</td>';
			print '<td>'.nl2br($agf->intro1).'</td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionIntro2").'</td>';
			print '<td>'.nl2br($agf->intro2).'</td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionArt1").'</td>';
			print '<td>'.ebi_liste_a_puce($agf->art1, true).'</td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionArt2").'</td>';
			print '<td>'.nl2br($agf->art2).'</td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionArt3").'</td>';
			print '<td>'.nl2br($agf->art3).'</td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionArt4").'</td>';
			print '<td>'.nl2br($agf->art4).'</td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionArt5").'</td>';
			print '<td>'.nl2br($agf->art5).'</td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionArt6").'</td>';
			print '<td>'.nl2br($agf->art6).'</td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionArt7").'</td>';
			print '<td>'.nl2br($agf->art7).'</td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfConventionSig").'</td>';
			print '<td>'.nl2br($agf->sig).'</td></tr>';
		
			print '<tr><td valign="top">'.$langs->trans("AgfNote").'<br /><span style=" font-size:smaller; font-style:italic;">'.$langs->trans("AgfConvNotesExplic").'</span></td>';
			print '<td valign="top">'.nl2br($agf->notes).'</td></tr>';

			print '</table>';
			print '</div>';

		}

	}
}


/*
 * Barre d'actions
 *
 */

print '<div class="tabsAction">';

if ($_GET["action"] != 'create' && $_GET["action"] != 'edit' && $_GET["action"] != 'nfcontact')
{
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butAction" href="convention_fiche.php?action=edit&convid='.$convid.'">'.$langs->trans('Modify').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Modify').'</a>';
	}
	if ($user->rights->agefodd->creer)
	{
		print '<a class="butActionDelete" href="convention_fiche.php?action=delete&convid='.$convid.'">'.$langs->trans('Delete').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
	}
}

print '</div>';

$db->close();

llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
