<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
 * Copyright (C) 2012       Florian Henry  	<florian.henry@open-concept.pro>
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
 * 	\file       /agefodd/admin/agefodd.php
 *	\ingroup    agefodd
 *	\brief      agefood module setup page
 *	\version    $Id$
 */

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/training/class/agefodd_formation_catalogue.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$langs->load("admin");
$langs->load('agefodd@agefodd');

if (!$user->admin) accessforbidden();

$value = GETPOST('value','alpha');
$action = GETPOST('action','alpha');
$label = GETPOST('label','alpha');

if ($action == 'updateMask')
{
	$maskconsttraining=GETPOST('maskconstproject','alpha');
	$masktraining=GETPOST('maskproject','alpha');

	if ($maskconsttraining)  $res = dolibarr_set_const($db,$maskconsttraining,$masktraining,'chaine',0,'',$conf->entity);

	if (! $res > 0) $error++;

	if (! $error)
	{
		$mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
	}
	else
	{
		$mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
	}
}
/*
if ($_POST["action"] == 'setvalue' && $user->admin)
{
	$result = dolibarr_set_const($db, "AGF_PRELEV_TRIGGER",$_POST["AGF_PRELEV_TRIGGER"],'chaine',0,'',$conf->entity);
  	if ($result >= 0)
  	{
  		$mesg='<div class="ok">'.$langs->trans("SetupSaved").'</div>';
  	}
  	else
  	{
		dol_print_error($db);
    }
}
*/


/*
 *
 *
 */

llxHeader();

$form=new Form($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("AgefoddSetupDesc"),$linkback,'setup');

print $langs->trans("AgefoddSetupParamChoice")."<br>\n";

//$mesg = "La page de paramètrage du module Agefodd n'a pas encore été développée.";
//$mesg.= "<br />Pour le configurer, il faut modifier à la main les variables globales du programme en éditant le fichier 'agefodd/pre.inc.php'";

if ($mesg) print '<br>'.$mesg;


// Project numbering module
print_titre($langs->trans("AgfAdminTrainingNumber"));



print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="100">'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td align="center" width="60">'.$langs->trans("Activated").'</td>';
print '<td align="center" width="80">'.$langs->trans("Infos").'</td>';
print "</tr>\n";

clearstatcache();

$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);

foreach ($dirmodels as $reldir)
{
	$dir = dol_buildpath("/agefodd/core/modules/agefodd/");
	
	if (is_dir($dir))
	{
		$handle = opendir($dir);
		if (is_resource($handle))
		{
			$var=true;
			
			while (($file = readdir($handle))!==false)
			{
				if (preg_match('/^(mod_.*)\.php$/i',$file,$reg))
				{
					$file = $reg[1];
					$classname = substr($file,4);

					require_once($dir.$file.".php");

					$module = new $file;
					
					// Show modules according to features level
					if ($module->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

					if ($module->isEnabled())
					{	
						$var=!$var;
						print '<tr '.$bc[$var].'><td>'.$module->nom."</td><td>\n";
						print $module->info();
						print '</td>';

						// Show example of numbering module
						print '<td nowrap="nowrap">';
						$tmp=$module->getExample();
						if (preg_match('/^Error/',$tmp)) {
							$langs->load("errors"); print '<div class="error">'.$langs->trans($tmp).'</div>';
						}
						elseif ($tmp=='NotConfigured') print $langs->trans($tmp);
						else print $tmp;
						print '</td>'."\n";

						print '<td align="center">';
						if ($conf->global->AGF_ADDON == 'mod_'.$classname)
						{
							print img_picto($langs->trans("Activated"),'switch_on');
						}
						else
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=setmod&amp;value=mod_'.$classname.'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
						}
						print '</td>';

						$agf=new Agefodd($db);
						$agf->initAsSpecimen();

						// Info
						$htmltooltip='';
						$htmltooltip.=''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
						$nextval=$module->getNextValue($mysoc,$agf);
						if ("$nextval" != $langs->trans("AgfNotAvailable"))	// Keep " on nextval
						{
							$htmltooltip.=''.$langs->trans("NextValue").': ';
							if ($nextval)
							{
								$htmltooltip.=$nextval.'<br>';
							}
							else
							{
								$htmltooltip.=$langs->trans($module->error).'<br>';
							}
						}

						print '<td align="center">';
						print $form->textwithpicto('',$htmltooltip,1,0);
						print '</td>';

						print '</tr>';
					}
				}
			}
			closedir($handle);
		}
	}
}

print '</table><br>';

/*
print '<br>';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

$var=true;

// Entête
print '<table summary="bookmarklist" class="notopnoleftnoright" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td>';

print $langs->trans("Traitement automatique sur prélévement").'</td>';
$selected = '';
print '<td><select class="flat" name="AGF_PRELEV_TRIGGER">\n';
print '<option value="">&nbsp;</option>\n';
if  ($conf->global->AGF_PRELEV_TRIGGER == "no") $selected = 'selected="true"';
print '<option value="no" '.$selected.'>'.$langs->trans("CcaSetupNo").'</option>\n';
if  ($conf->global->AGF_PRELEV_TRIGGER == "bank_only") $selected='selected="true"';
print '<option value="bank_only" '.$selected.'>'.$langs->trans("CcaSetupBankOnly").'</option>\n';
if  ($conf->global->AGF_PRELEV_TRIGGER == "ff_only") $selected='selected="true"';
print '<option value="ff_only" '.$selected.'>'.$langs->trans("CcaSetupFactureFournisseurOnly").'</option>\n';
print '</select>
print '</td></tr>';

print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table></form>';
*/

$db->close();

llxFooter('$Date: 2010-03-21 21:28:31 +0100 (dim. 21 mars 2010) $ - $Revision: 46 $');
?>
