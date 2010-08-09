<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file       htdocs/agefodd/download.php
 *  \brief      Wrapper to download data files
 *  \version    $Id: document.php,v 1.93.2.1 2009/12/15 10:51:11 eldy Exp $
 *  \remarks    Call of this wrapper is mad with URL:
 * 		download.php?modulepart=agefodd&file=pathrelatifdufichier
 */

// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
$action = isset($_GET["action"])?$_GET["action"]:'';
$original_file = isset($_GET["file"])?$_GET["file"]:'';

$modulepart = isset($_GET["modulepart"])?$_GET["modulepart"]:'';
$urlsource = isset($_GET["urlsource"])?$_GET["urlsource"]:'';

// Define if we need master or master+main
$needmasteronly=false;
if ($modulepart == 'bittorrent') $needmasteronly=true;

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

// Load master or main
if ($needmasteronly)
{
	// For some download we don't need login
	require("../master.inc.php");
}
else
{
	if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU','1');
	if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML','1');
	if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX','1');

	// Pour autre que companylogo, on charge environnement + info issus de logon comme le user
	require("../main.inc.php");
	// master.inc.php is included in main.inc.php
}
require_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');

// Modif EBI pour gestion absence de valeur transmie pour file
if (empty($original_file))
{
    //$original_file='null.pdf';
    dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$original_file));
    exit;
}

// C'est un wrapper, donc header vierge
function llxHeader() { }


// Define mime type
$type = 'application/octet-stream';
if (! empty($_GET["type"])) $type=$_GET["type"];
else $type=dol_mimetype($original_file);
//print 'X'.$type.'-'.$original_file;exit;

// Define attachment (attachment=true to force choice popup 'open'/'save as')
$attachment = true;
// Text files
if (preg_match('/\.txt$/i',$original_file))  	{ $attachment = false; }
if (preg_match('/\.csv$/i',$original_file))  	{ $attachment = true; }
if (preg_match('/\.tsv$/i',$original_file))  	{ $attachment = true; }
// Documents MS office
if (preg_match('/\.doc(x)?$/i',$original_file)) { $attachment = true; }
if (preg_match('/\.dot(x)?$/i',$original_file)) { $attachment = true; }
if (preg_match('/\.mdb$/i',$original_file))     { $attachment = true; }
if (preg_match('/\.ppt(x)?$/i',$original_file)) { $attachment = true; }
if (preg_match('/\.xls(x)?$/i',$original_file)) { $attachment = true; }
// Documents Open office
if (preg_match('/\.odp$/i',$original_file))     { $attachment = true; }
if (preg_match('/\.ods$/i',$original_file))     { $attachment = true; }
if (preg_match('/\.odt$/i',$original_file))     { $attachment = true; }
// Misc
if (preg_match('/\.(html|htm)$/i',$original_file)) 	{ $attachment = false; }
if (preg_match('/\.pdf$/i',$original_file))  	{ $attachment = true; }
if (preg_match('/\.sql$/i',$original_file))     { $attachment = true; }
// Images
if (preg_match('/\.jpg$/i',$original_file)) 	{ $attachment = true; }
if (preg_match('/\.jpeg$/i',$original_file)) 	{ $attachment = true; }
if (preg_match('/\.png$/i',$original_file)) 	{ $attachment = true; }
if (preg_match('/\.gif$/i',$original_file)) 	{ $attachment = true; }
if (preg_match('/\.bmp$/i',$original_file)) 	{ $attachment = true; }
if (preg_match('/\.tiff$/i',$original_file)) 	{ $attachment = true; }
// Calendar
if (preg_match('/\.vcs$/i',$original_file))  	{ $attachment = true; }
if (preg_match('/\.ics$/i',$original_file))  	{ $attachment = true; }
if ($_REQUEST["attachment"])            { $attachment = true; }
if (! empty($conf->global->MAIN_DISABLE_FORCE_SAVEAS)) $attachment=false;
//print "XX".$attachment;exit;

// Suppression de la chaine de caractere ../ dans $original_file
$original_file = str_replace("../","/", $original_file);

// find the subdirectory name as the reference
$refname=basename(dirname($original_file)."/");

$accessallowed=0;
$sqlprotectagainstexternals='';
if ($modulepart)
{
	// On fait une verification des droits et on definit le repertoire concerne

	// Wrapping pour Agefodd
	if ($modulepart == 'agefodd')
	{
		$user->getrights('agefodd');
		if ($user->rights->agefodd->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		//$original_file=$conf->facture->dir_output.'/'.$original_file;
		$original_file=DOL_DOCUMENT_ROOT.'/agefodd/documents/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."facture WHERE ref='$refname'";
	}
}

// Basic protection (against external users only)
if ($user->societe_id > 0)
{
	if ($sqlprotectagainstexternals)
	{
		$resql = $db->query($sqlprotectagainstexternals);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			$num=$db->num_rows($resql);
			if ($num>0 && $user->societe_id != $obj->fk_soc)
			$accessallowed=0;
		}
	}
}

// Security:
// Limite acces si droits non corrects
if (! $accessallowed)
{
	accessforbidden();
}

// Security:
// On interdit les remontees de repertoire ainsi que les pipe dans
// les noms de fichiers.
if (preg_match('/\.\./',$original_file) || preg_match('/[<>|]/',$original_file))
{
	dol_syslog("Refused to deliver file ".$original_file);
	// Do no show plain path in shown error message
	dol_print_error(0,$langs->trans("ErrorFileNameInvalid",$_GET["file"]));
	exit;
}


if ($action == 'remove_file')	// Remove a file
{
	clearstatcache();

	dol_syslog("document.php remove $original_file $urlsource", LOG_DEBUG);

	// This test should be useless. We keep it to find bug more easily
	$original_file_osencoded=dol_osencode($original_file);	// New file name encoded in OS encoding charset
	if (! file_exists($original_file_osencoded))
	{
		dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$_GET["file"]));
		exit;
	}

	dol_delete_file($original_file);

	dol_syslog("document.php back to ".urldecode($urlsource), LOG_DEBUG);

	header("Location: ".urldecode($urlsource));

	return;
}
else						// Open and return file
{
	clearstatcache();

	$filename = basename($original_file);
	// Output file on browser
	dol_syslog("document.php download $original_file $filename content-type=$type");
	$original_file_osencoded=dol_osencode($original_file);	// New file name encoded in OS encoding charset

	// This test if file exists should be useless. We keep it to find bug more easily
	if (! file_exists($original_file_osencoded))
	{
		dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$original_file));
		exit;
	}

	// Les drois sont ok et fichier trouve, on l'envoie

	if ($encoding)   header('Content-Encoding: '.$encoding);
	if ($type)       header('Content-Type: '.$type);
	if ($attachment) header('Content-Disposition: attachment; filename="'.$filename.'"');
	else header('Content-Disposition: inline; filename="'.$filename.'"');

	// Ajout directives pour resoudre bug IE
	header('Cache-Control: Public, must-revalidate');
	header('Pragma: public');

	readfile($original_file_osencoded);
}
?>
