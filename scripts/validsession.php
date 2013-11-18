<?php
/* Copyright (C) 2013 Florian Henry  <florian.henry@open-concept.pro>
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
*/

/**
 * \file /agefodd/scripts/createtaskadmin.php
 * \brief Generate script
 */
if (! defined ( 'NOTOKENRENEWAL' ))
	define ( 'NOTOKENRENEWAL', '1' ); // Disables token renewal
if (! defined ( 'NOREQUIREMENU' ))
	define ( 'NOREQUIREMENU', '1' );
if (! defined ( 'NOREQUIREHTML' ))
	define ( 'NOREQUIREHTML', '1' );
if (! defined ( 'NOREQUIREAJAX' ))
	define ( 'NOREQUIREAJAX', '1' );
if (! defined ( 'NOLOGIN' ))
	define ( 'NOLOGIN', '1' );

$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die ( "Include of main fails" );

dol_include_once ( '/agefodd/class/agsession.class.php' );
dol_include_once ( '/user/class/user.class.php' );
dol_include_once ( '/agefodd/class/agefodd_facture.class.php' );
dol_include_once ( '/comm/propal/class/propal.class.php' );
dol_include_once ( '/societe/class/societe.class.php' );
dol_include_once ( '/agefodd/core/modules/agefodd/modules_agefodd.php' );
dol_include_once ( '/agefodd/class/agefodd_session_calendrier.class.php' );
dol_include_once ( '/agefodd/class/agefodd_formation_catalogue.class.php' );
dol_include_once ( '/agefodd/class/agefodd_convention.class.php' );
dol_include_once ( '/agefodd/class/agefodd_contact.class.php' );
dol_include_once ( '/agefodd/class/agefodd_place.class.php' );
dol_include_once ( '/agefodd/class/agefodd_session_stagiaire.class.php');

$userlogin = GETPOST ( 'login' );
$idpropal = GETPOST ( 'idpropal' );

$user = new User ( $db );
$result = $user->fetch ( '', $userlogin );

$propal = new Propal ( $db );
$propal->fetch ( idpropal );
if (! empty ( $propal->id )) {
	
	$result = $propal->cloture ( $user, 2 );
	if ($result > 0) {
		print - 1;
	} else {
		
		$langs->trans('agefodd@agefodd');
		
		$socid=$propal->socid;
		
		$agf_fin = new Agefodd_facture ( $db );
		$agf_fin->fetch_fac_by_id ( $idpropal, 'prop' );
		
		$agf = new Agsession ( $db );
		$agf->fetch ( $agf_fin->lines [0]->fk_session );
		
		$agf_last = new Agefodd_convention ( $db );
		$result = $agf_last->fetch_last_conv_per_socity ( $socid );
		if ($result > 0) {
			$agf_conv = new Agefodd_convention ( $db );
			$result = $agf_conv->fetch ( $agf_last->sessid, $socid );
			if ($agf_last->sessid)
				$last_conv = 'ok';
		}
		
		// intro1
		$statut = getFormeJuridiqueLabel ( $mysoc->forme_juridique_code );
		$intro1 = $langs->trans ( 'AgfConvIntro1_1' ) . ' ' . $mysoc->name . ', ' . $statut . ' ' . $langs->trans ( 'AgfConvIntro1_2' ) . ' ';
		if (! empty ( $mysoc->capital )) {
			$capital_text = ' ' . $mysoc->capital . ' ' . $langs->trans ( "Currency" . $conf->currency );
		} else {
			$capital_text = '';
		}
		$intro1 .= $capital_text . ' ' . $langs->trans ( 'AgfConvIntro1_3' ) . ' ' . $mysoc->town;
		$intro1 .= ' (' . $mysoc->zip . ') ';
		if (! empty ( $mysoc->idprof4 )) {
			$intro1 .= $langs->trans ( 'AgfConvIntro1_4' ) . ' ' . $mysoc->idprof4;
		}
		if (empty ( $conf->global->AGF_ORGANISME_NUM )) {
			$intro1 .= ' ' . $langs->trans ( 'AgfConvIntro1_5' ) . ' ' . $conf->global->AGF_ORGANISME_PREF;
		} else {
			$intro1 .= $langs->trans ( 'AgfConvIntro1_6' );
			$intro1 .= $conf->global->AGF_ORGANISME_PREF . ' ' . $langs->trans ( 'AgfConvIntro1_7' ) . ' ' . $conf->global->AGF_ORGANISME_NUM;
		}
		if (! empty ( $conf->global->AGF_ORGANISME_REPRESENTANT )) {
			$intro1 .= $langs->trans ( 'AgfConvIntro1_8' ) . ' ' . $conf->global->AGF_ORGANISME_REPRESENTANT . $langs->trans ( 'AgfConvIntro1_9' );
		}
		
		// intro2
		// Get trhidparty info
		$agf_soc = new Societe ( $db );
		$result = $agf_soc->fetch ( $socid );
		
		// if agefodd contact exist
		$agf_contact = new Agefodd_contact ( $db );
		$resql2 = $agf_contact->fetch ( $socid, 'socid' );
		
		// intro2
		$intro2 = $langs->trans ( 'AgfConvIntro2_1' ) . ' ' . $agf_soc->name . $langs->trans ( 'AgfConvIntro2_2' ) . ' ' . $agf_soc->address . " " . $agf_soc->zip . " " . $agf_soc->town . ",";
		$intro2 .= ' ' . $langs->trans ( 'AgfConvIntro2_3' ) . ' ' . $agf_soc->idprof2 . ", ";
		$intro2 .= ' ' . $langs->trans ( 'AgfConvIntro2_4' ) . ' ';
		$intro2 .= ucfirst ( strtolower ( $agf_contact->civilite ) ) . ' ' . $agf_contact->firstname . ' ' . $agf_contact->lastname;
		$intro2 .= ' ' . $langs->trans ( 'AgfConvIntro2_5' );
		
		// article 1
		// Mise en page (Cf. fonction "liste_a_puce()" du fichier pdf_convention_modele.php)
		// Si la ligne commence par:
		// '!# ' aucune puce ne sera générée, la ligne commence sur la magre gauche
		// '# ', une puce de premier niveau est mis en place
		// '## ', une puce de second niveau est mis en place
		// '### ', une puce de troisième niveau est mis en place
		$art1 = $langs->trans ( 'AgfConvArt1_1' ) . "\n";
		$art1 .= $langs->trans ( 'AgfConvArt1_2' ) . ' ' . $agf->formintitule . ' ' . $langs->trans ( 'AgfConvArt1_3' ) . " \n";
		$art1 .= $langs->trans ( 'AgfConvArt1_4' ) . "\n";
		
		$obj_peda = new Agefodd ( $db );
		$resql = $obj_peda->fetch_objpeda_per_formation ( $agf->formid );
		foreach ( $obj_peda->lines as $line ) {
			$art1 .= "##	" . $line->intitule . "\n";
		}
		$art1 .= $langs->trans ( 'AgfConvArt1_5' ) . "\n";
		$art1 .= $langs->trans ( 'AgfConvArt1_6' ) . "\n";
		$art1 .= $langs->trans ( 'AgfConvArt1_7' );
		
		if ($agf->dated == $agf->datef)
			$art1 .= $langs->trans ( 'AgfConvArt1_8' ) . ' ' . dol_print_date ( $agf->datef );
		else
			$art1 .= $langs->trans ( 'AgfConvArt1_9' ) . ' ' . dol_print_date ( $agf->dated ) . ' ' . $langs->trans ( 'AgfConvArt1_10' ) . ' ' . dol_print_date ( $agf->datef );
		
		$art1 .= "\n";
		
		// Durée de formation
		$art1 .= $langs->trans ( 'AgfConvArt1_11' ) . ' ' . $agf->duree . ' ' . $langs->trans ( 'AgfConvArt1_12' ) . ' ' . "\n";
		
		$calendrier = new Agefodd_sesscalendar ( $db );
		$resql = $calendrier->fetch_all ( $sessid );
		$blocNumber = count ( $calendrier->lines );
		$old_date = 0;
		$duree = 0;
		for($i = 0; $i < $blocNumber; $i ++) {
			if ($calendrier->lines [$i]->date_session != $old_date) {
				if ($i > 0)
					$art1 .= "), ";
				$art1 .= dol_print_date ( $calendrier->lines [$i]->date_session, 'daytext' ) . ' (';
			} else
				$art1 .= '/';
			$art1 .= dol_print_date ( $calendrier->lines [$i]->heured, 'hour' );
			$art1 .= ' - ';
			$art1 .= dol_print_date ( $calendrier->lines [$i]->heuref, 'hour' );
			if ($i == $blocNumber - 1)
				$art1 .= ').' . "\n";
			
			$old_date = $calendrier->lines [$i]->date_session;
		}
		
		$art1 .= $langs->trans ( 'AgfConvArt1_13' ) . "\n";
		
		$stagiaires = new Agefodd_session_stagiaire ( $db );
		$nbstag = $stagiaires->fetch_stagiaire_per_session ( $sessid, $socid );
		$art1 .= $langs->trans ( 'AgfConvArt1_14' ) . ' ' . $nbstag . ' ' . $langs->trans ( 'AgfConvArt1_15' );
		if ($nbstag > 1)
			$art1 .= $langs->trans ( 'AgfConvArt1_16' );
		$art1 .= $langs->trans ( 'AgfConvArt1_17' ) . "\n";
		// Adresse lieu de formation
		$agf_place = new Agefodd_place ( $db );
		$resql3 = $agf_place->fetch ( $agf->placeid );
		$adresse = $agf_place->adresse . ", " . $agf_place->cp . " " . $agf_place->ville;
		$art1 .= $langs->trans ( 'AgfConvArt1_18' ) . $agf_place->ref_interne . $langs->trans ( 'AgfConvArt1_19' ) . ' ' . $adresse . '.';
		
		// texte 2
		if ($agf_conv->art2)
			$art2 = $agf_conv->art2;
		else {
			$art2 = $langs->trans ( 'AgfConvArt2_1' );
		}
		
		// texte3
		$art3 = $langs->trans ( 'AgfConvArt3_1' );
		($nbstag > 1) ? $art3 .= $langs->trans ( 'AgfConvArt3_2' ) . ' ' : $art3 .= ' ' . $langs->trans ( 'AgfConvArt3_3' ) . ' ';
		
		for($i = 0; $i < $nbstag; $i ++) {
			$art3 .= $stagiaires->lines [$i]->nom . ' ' . $stagiaires->lines [$i]->prenom;
			if (! empty ( $stagiaires->lines [$i]->poste )) {
				$art3 .= ' (' . $stagiaires->lines [$i]->poste . ')';
			}
			if ($i == $nbstag - 1)
				$art3 .= '.';
			else {
				if ($i == $nbstag - 2)
					$art3 .= ' ' . $langs->trans ( 'AgfConvArt3_4' ) . ' ';
				else
					$art3 .= ', ';
			}
		}
		
		// texte 4
		if ($conf->global->FACTURE_TVAOPTION == "franchise") {
			$art4 = $langs->trans ( 'AgfConvArt4_1' );
		} else {
			$art4 = $langs->trans ( 'AgfConvArt4_3' );
		}
		$art4 .= "\n" . $langs->trans ( 'AgfConvArt4_2' );
		
		// texte 5
		if ($agf_conv->art5)
			$art5 = $agf_conv->art5;
		else {
			$art5 = $langs->trans ( 'AgfConvArt5_1' );
		}
		
		// article 6
		if ($agf_conv->art6) {
			$art6 = $agf_conv->art6;
		} else {
			$art6 = $langs->trans ( 'AgfConvArt6_1' ) . "\n";
			$art6 .= $langs->trans ( 'AgfConvArt6_2' ) . "\n";
			$art6 .= $langs->trans ( 'AgfConvArt6_3' ) . "\n";
			$art6 .= $langs->trans ( 'AgfConvArt6_4' ) . "\n";
		}
		
		// article 7
		if ($agf_conv->art7)
			$art7 = $agf_conv->art7;
		else {
			$art7 = $langs->trans ( 'AgfConvArt7_1' );
			$art7 .= $langs->trans ( 'AgfConvArt7_2' ) . ' ' . $mysoc->town . ".";
		}
		
		// Signature du client
		if ($agf_conv->sig)
			$sig = $agf_conv->sig;
		else {
			$sig = $agf_soc->nom . "\n";
			$sig .= $langs->trans ( 'AgfConvArtSig' ) . ' ';
			$sig .= ucfirst ( strtolower ( $agf_contact->civilite ) ) . ' ' . $agf_contact->firstname . ' ' . $agf_contact->lastname . " (*)";
		}
		
		$agf = new Agefodd_convention ( $db );
		
		if (! empty ( $intro1 ))
			$agf->intro1 = $intro1;
		if (! empty ( $intro2 ))
			$agf->intro2 = $intro2;
		if (! empty ( $art1 ))
			$agf->art1 = $art1;
		if (! empty ( $art2 ))
			$agf->art2 = $art2;
		if (! empty ( $art3 ))
			$agf->art3 = $art3;
		if (! empty ( $art4 ))
			$agf->art4 = $art4;
		if (! empty ( $art5 ))
			$agf->art5 = $art5;
		if (! empty ( $art6 ))
			$agf->art6 = $art6;
		if (! empty ( $art7 ))
			$agf->art7 = $art7;
		if (! empty ( $art8 ))
			$agf->art8 = $art8;
		if (! empty ( $sig ))
			$agf->sig = $sig;
		if (! empty ( $notes ))
			$agf->notes = $notes;
		$agf->socid = $socid;
		$agf->sessid = $sessid;
		
		$result = $agf->create ( $user );
		if ($result<0) {
			print -1;
			print '<BR>'.$agf->error;
			exit;
		}
		
		$model='convention';
		$file = $model.'_'.$agf_fin->lines [0]->fk_session.'_'.$socid.'.pdf';
		
		$result = agf_pdf_create ( $db, $agf_fin->lines [0]->fk_session, '', $model, $outputlangs, $file, $socid);
		if ($result<0) {
			print -1;
			print '<BR>$result='.$result;
			exit;
		}
		
		print 1;
	}
}

			