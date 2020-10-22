<?php
/*
 * Copyright (C) 2012-2014 Florian Henry <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 * \file		/agefodd/report/report_bpf_help.php
 * \brief		report part
 * (Agefodd).
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once ('../lib/agefodd.lib.php');

// Security check
if (! $user->rights->agefodd->lire)
	accessforbidden();

$lang_id = GETPOST('lang_id', 'none');

$langs->load('agefodd@agefodd');

llxHeader('', $langs->trans('AgfMenuReportBPFHelp'), '', '', '', '', $extrajs, $extracss);


print load_fiche_titre($langs->trans("AgfMenuReportBPFHelp"));

print "<br>\n";

print $langs->trans("AgfBPFIntro");
print ' <a href="'.dol_buildpath('/agefodd/report/docBPF/cerfa_10443-16.pdf',1).'">CERFA 10443 * 16</a>';
print ' <a href="'.dol_buildpath('/agefodd/report/docBPF/notice_cerfa_10443-16.pdf',1).'">(Notice 10443 * 16)</a>';
print "<br>\n";
print "<br>\n";


print load_fiche_titre($langs->trans("Configuration"), '', 'object_bill')."<br>\n";
print '<hr>';
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFHelpconfig");
print "<br>\n";
print '1 - <a href="'.dol_buildpath('/agefodd/admin/admin_catbpf.php',1).'">'.$langs->trans('AgefoddSetupDesc').'</a>';
print "<br>\n";
print '2 - <a href="'.dol_buildpath('/agefodd/admin/admin_catcost.php',1).'">'.$langs->trans('AgefoddSetupDesc').'</a>';
print "<br>\n";
print '3 - <a href="'.dol_buildpath('/admin/dict.php',1).'">'.$langs->trans('AgfTraineeType').'</a>';
print "<br>\n";
print '4 - <a href="'.dol_buildpath('/admin/dict.php',1).'">'.$langs->trans('AgfTrainerTypeDict').'</a>';
print "<br>\n";
print '4 - <a href="'.dol_buildpath('/admin/dict.php',1).'">'.$langs->trans('AgfTrainingCategTbl').'</a>';
print "<br>\n";
print '4 - <a href="'.dol_buildpath('/admin/dict.php',1).'">'.$langs->trans('AgfTrainingCategTblBPF').'</a>';
print "<br>\n";
print '6 - Tous les participants sont renseignés sur les sessions (sinon les sessions sans participant ne seront pas comptabilisées)';
print "<br>\n";
print '7 - Assurez vous que le calendrier des sessions soit correctement renseigné dans chaque session';
print "<br>\n";
print '8 - Assurez vous que le calendrier des formateurs soit correctement renseigné dans chaque session';
print "<br>\n";
print '9 - Assurez vous que les tiers (client/fournisseur) soient bien dans les bonnes catégories';
print "<br>\n";
print '10 - Assurez vous que les produits/services soient bien dans les bonnes catégories';


print "<br>\n";
print "<br>\n";


print load_fiche_titre($langs->trans("AgfReportBPFOrigProd"), '', 'object_bill')."<br>\n";
print '<hr>';
print "<br>\n";

print img_picto('', 'puce').' '.$langs->trans("AgfBPFExplanationChaperC");
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFC1",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFCa",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategOPCA').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFCb",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategOPCA').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFCc",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategOPCA').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFCd",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategOPCA').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFCe",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategOPCA').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFCf",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategOPCA').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFCg",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategOPCA').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFCh",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategOPCA').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFC3",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategFAF').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFC4",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategAdmnistration').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFC5",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategAdmnistration').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFC6",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategAdmnistration').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFC7",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategAdmnistration').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFC8",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategAdmnistration').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFC9",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategAdmnistration').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFC10",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategParticulier').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFC11",'<strong>'.$langs->transnoentities('AgfReportBPFCategProdPeda').'</strong>','<strong>'.$langs->transnoentities('AgfTypeEmployee').'</strong>');
print "<br>\n";
print "<br>\n";


print load_fiche_titre($langs->trans("AgfReportBPFChargeProd"), '', 'object_bill')."<br>\n";
print '<hr>';
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFExplanationChaperD");
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFD1",'<strong>'.$langs->transnoentities('AgfCategOverheadCost').'</strong>');
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFD2",'<strong>'.$langs->transnoentities('AgfReportBPFCategFeePresta').'</strong>','<strong>'.$langs->transnoentities('AgfReportBPFCategPresta').'</strong>');
print "<br>\n";
print "<br>\n";

print load_fiche_titre($langs->trans("AgfReportBPFChaperE"), '', 'object_action')."<br>\n";
print '<hr>';
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFExplanationChaperE",$langs->transnoentities('AgfTrainerTypeDict'), $langs->transnoentities('TraineeSessionStatusPresent'), $langs->transnoentities('TraineeSessionStatusPartPresent'));
print "<br>\n";
print "<br>\n";

print load_fiche_titre($langs->trans("AgfReportBPFChaperF1"), '', 'object_action')."<br>\n";
print '<hr>';
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFExplanationChaperF1", $langs->transnoentities('AgfTraineeType'), $langs->transnoentities('TraineeSessionStatusPresent'), $langs->transnoentities('TraineeSessionStatusPartPresent'));
if (!empty($conf->global->AGF_USE_REAL_HOURS)) {
	print $langs->trans("AgfBPFExplanationChaperFRealTime");
}
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFF1a");
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFF1b");
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFF1c");
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFF1d");
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFF1e");
print "<br>\n";
print "<br>\n";


print load_fiche_titre($langs->trans("AgfReportBPFChaperF2"), '', 'object_action')."<br>\n";
print '<hr>';
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFExplanationChaperF2", $langs->transnoentities('TraineeSessionStatusPresent'), $langs->transnoentities('TraineeSessionStatusPartPresent'));
if (!empty($conf->global->AGF_USE_REAL_HOURS)) {
	print $langs->trans("AgfBPFExplanationChaperFRealTime");
}
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFF2a", $langs->transnoentities('TraineeSessionStatusPresent'), $langs->transnoentities('TraineeSessionStatusPartPresent'));
print "<br>\n";
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFF2b", $langs->transnoentities('TraineeSessionStatusPresent'), $langs->transnoentities('TraineeSessionStatusPartPresent'));
print "<br>\n";
print "<br>\n";


print load_fiche_titre($langs->trans("AgfReportBPFChaperF3"), '', 'object_action')."<br>\n";
print '<hr>';
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFExplanationChaperF3", $langs->transnoentities('TraineeSessionStatusPresent'), $langs->transnoentities('TraineeSessionStatusPartPresent'),$langs->transnoentities('AgfTrainingCategTblBPF'));
if (!empty($conf->global->AGF_USE_REAL_HOURS)) {
	print $langs->trans("AgfBPFExplanationChaperFRealTime");
}
print "<br>\n";
print "<br>\n";

print load_fiche_titre($langs->trans("AgfReportBPFChaperF4"), '', 'object_action')."<br>\n";
print '<hr>';
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFExplanationChaperF4", $langs->transnoentities('TraineeSessionStatusPresent'), $langs->transnoentities('TraineeSessionStatusPartPresent'),$langs->transnoentities('AgfTrainingCategTbl'));
print "<br>\n";
print "<br>\n";

print load_fiche_titre($langs->trans("AgfReportBPFChaperG"), '', 'object_action')."<br>\n";
print '<hr>';
print "<br>\n";
print img_picto('', 'puce').' '.$langs->trans("AgfBPFExplanationChaperG", $langs->transnoentities('TraineeSessionStatusPresent'), $langs->transnoentities('TraineeSessionStatusPartPresent'));
print "<br>\n";
print "<br>\n";

llxFooter();
$db->close();

