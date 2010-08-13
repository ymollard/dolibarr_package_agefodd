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
 */


/**
	\file		$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/s_liste.php $
	\brief		Page présentant la liste des sites sur lesquels sont effectuées les formations
	\version	$Id: s_liste.php 54 2010-03-30 18:58:28Z ebullier $
*/
require_once("../../../../main.inc.php");

/**
 *	\brief   	Crée un document PDF
 *	\param   	db  			objet base de donnee
 *	\param   	modele  		modele à utiliser
 *	\param		outputlangs		objet lang a utiliser pour traduction
 *	\return  	int        		<0 if KO, >0 if OK
 */
function agf_pdf_create($db, $id, $message, $typeModele, $outputlangs, $file, $socid, $courrier='')
{
	global $conf,$langs;
	$langs->load("@agefodd");

	// Charge le modele
	$nomModele = DOL_DOCUMENT_ROOT.'/agefodd/inc/models/pdf/pdf_'.$typeModele.'_modele.php';
	
	if (file_exists($nomModele))
	{
		//$classname = "pdf_".$modele;
		require_once($nomModele);

		$obj = new agf_pdf_document($db);
		$obj->message = $message;

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($id, $outputlangs, $file, $socid, $courrier) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"pdf_create Error: ".$obj->error);
			return -1;
		}

	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file));
		return -1;
	}
}


# llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
