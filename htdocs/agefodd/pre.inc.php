<?php
/* Copyright (C) 2001-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2010	Erick Bullier		<eb.dev@ebiconsulting.fr>
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

$langs->load("@agefodd");

define('AGF_ORGANISME_NAME', 'XXXXXXX');
define('AGF_ORGANISME_NUM', '00000000');
define('AGF_ORGANISME_PREF', 'Hérault');
define('AGF_ORGANISME_LOGO', DOL_DOCUMENT_ROOT.'/agefodd/images/logo.jpg');
define('AGF_ORGANISME_SIEGE', 'Montpellier');
define('AGF_ORGANISME_BASELINE', $conf->global->MAIN_INFO_SOCIETE_WEB);
define('AGF_ORGANISME_RESPONSABLE', 'Yoko Tsuno');
define('AGF_ORGANISME_REPRESENTANT', 'Paul Pitron');

function llxHeader($head = "") {
    global $user, $conf, $langs;
    
    top_menu($head);
    
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
	// Bilan DRTEPF
	if ($user->rights->agefodd->lire)  $menu->add(DOL_URL_ROOT."/agefodd/not_implemented.php?mainmenu=&leftmenu=agefodd", $langs->trans("AgfMenuSAdmBilanDRTEPF"),1);

	// Tests
	//$menu->add(DOL_URL_ROOT."/agefodd/pdf_document.php?mainmenu=&leftmenu=agefodd", "TEST");

    }
    left_menu($menu->liste);
}

?>