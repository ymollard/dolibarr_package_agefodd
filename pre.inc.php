<?php
 /* Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
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
 *
 */

/**   
      \file   	    	$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/pre.inc.php $
      \ingroup      	agefodd
      \brief  	    	Fichier gestionnaire du menu agefodd
      \Version		$ID$
*/

require("../main.inc.php");

$langs->load('agefodd@agefodd');

// TODO déplacer la configuration dans le fichier admin du module et supprimer ce fichier

/**** Debut des variables globales **********************************************/

// Nom de l'organisme de formation
define('AGF_ORGANISME_NAME', 'AGEFODD');
// Numero d'enregistrement de l'organisme de formation
define('AGF_ORGANISME_NUM', '9134 06XXX 34');
// Préfecture ayant enregistré l'organisme de formation
define('AGF_ORGANISME_PREF', 'Hérault');
// Logo a utiliser sur les documents (par défaut jpg de 200x250)
define('AGF_ORGANISME_LOGO', DOL_DOCUMENT_ROOT.'/agefodd/img/logo.jpg');
// Siége de l'organisme de formation
define('AGF_ORGANISME_SIEGE', 'Montpellier');
// Baseline de l'oganisme (par défaut url de la société)
define('AGF_ORGANISME_BASELINE', $conf->global->MAIN_INFO_SOCIETE_WEB);
// Nom du responsable formation de l'organisme
define('AGF_ORGANISME_RESPONSABLE', 'Marc DACIER');
// Nom du représentant juridique de la structure (gerant, président etc.)
define('AGF_ORGANISME_REPRESENTANT', 'James Tiberius Kirk');
// Utilisation du type de financment des stagiaire's (par defaut OK)
define('USE_STAGIAIRE_TYPE', 'OK');	// OK or NOK
// Si on utilise le type de financement celui qui sera défini par defaut
// Ici, c'est le 3 : financement par l'employeur (autre)
// On peut consulter les types de financments dans la table llx_agefodd_stagiaire_type
define('DEFAULT_STAGIAIRE_TYPE', 3);	// Default type to use when adding a student to a session

/**** Fin des variables globales *************************************************/


function llxHeader($head = "") {
	
	global $user, $conf, $langs;
	
	top_menu($head);
/*	
	$menu = new Menu();
	
	if ($conf->agefodd->enabled)
	{
		// Catalogue de formation
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/f_liste.php?mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuCat"));
		// Liste des formations actives
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/f_liste.php?mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuCatListActivees"),1);
		// Liste des formations archivees
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/f_liste.php?arch=1&mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuCatListArchivees"),1);
		// Nouvelle action de formation
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/f_fiche.php?mainmenu=&leftmenu=agefodd&action=create", $langs->trans("AgfMenuCatNew"),1);

		// Action de formation (Session)
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/s_liste.php?mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuSess"));
		// Liste des sessions en cours
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/s_liste.php?mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuSessActList"),1);
		// Liste des sessions archivees
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/s_liste.php?arch=1&mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuSessArchList"),1);
		// Nouvelle session
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/s_fiche.php?mainmenu=&leftmenu=agefodd&action=create", $langs->trans("AgfMenuSessNew"),1);
	
		// Gestion stagiaires
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/u_liste.php?mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuActStagiaire"));
		// Liste des stagiaires
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/u_liste.php?mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuActStagiaireList"),1);
		// Creer une nouvelle fiche
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/u_fiche.php?mainmenu=&leftmenu=agefodd&action=create", $langs->trans("AgfMenuActStagiaireNew"),1);
		// Importer une fiche depuis contact
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/u_fiche.php?mainmenu=&leftmenu=agefodd&action=nfcontact", $langs->trans("AgfMenuActStagiaireNewFromContact"),1);

		// Logistique
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/u_liste.php?mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuLogistique"));
		// Gestion des sites
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/s_place_liste.php?mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuSite"),1);
		// Gestion des formateurs
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/s_teacher_liste.php?mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuFormateur"),1);
		// Gestion des formateurs
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/c_liste.php?mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuContact"),1);


		// Suivi administratif
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/not_implemented.php?mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuSAdm"));
		// Bilan DIRECCTE
		if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/not_implemented.php?mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuSAdmBilanDRTEFP"),1);

		// Tests
		//$menu->add(DOL_URL_ROOT."/agefodd/includes/models/pdf/pdf_document.php?mainmenu=&leftmenu=agefodd", "TEST");

	}
	left_menu($menu->liste);
	*/
}

?>