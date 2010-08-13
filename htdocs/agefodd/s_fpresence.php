<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne		<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009	Regis Houssin		<regis@dolibarr.fr>
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
 */

/**
	\file		$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/index.php $
	\brief		Page temporaire pour non implementation d'une focntion.
	\Version	$Id: index.php 51 2010-03-28 17:06:42Z ebullier $
*/

require("../main.inc.php");

$langs->load("@agefodd");


// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

llxHeader();

print_barre_liste($langs->trans("AgfBilanGlobal"), $page, "index.php","&socid=$socid",$sortfield,$sortorder,'',$num);

print '<div class="error">&nbsp;'.$langs->trans("AgfNotImplemented").'</div>';

llxFooter('$Date: 2010-03-28 19:06:42 +0200 (dim. 28 mars 2010) $ - $Revision: 51 $');
?>
