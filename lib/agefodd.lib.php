<?php
/* Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
 * Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
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
 *  \file       	$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/lib/agefodd.lib.php $
 *  \brief      	Page fiche d'une operation sur CCA
 *  \version		$Id$
 */

$langs->load('agefodd@agefodd');

function training_prepare_head($object)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();
	
	$head[$h][0] = dol_buildpath('/agefodd/training/card.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$hselected = $h;
	$h++;
	
	$head[$h][0] = dol_buildpath('/agefodd/training/info.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$hselected = $h;
	$h++;
	
	complete_head_from_modules($conf,$langs,$object,$head,$h,'agefodd_training');

	return $head;
}


function session_prepare_head($object,$showconv=0)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();
	
	$head[$h][0] = dol_buildpath('/agefodd/session/card.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;
	
	/*$head[$h][0] = DOL_URL_ROOT.'/agefodd/s_fpresence.php?id='.$object->id;
	$head[$h][1] = $langs->trans("AgfFichePresence");
	$head[$h][2] = 'presence';
	$h++;*/ //TODO fiche de presence

	$head[$h][0] = dol_buildpath('/agefodd/session/administrative.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("AgfAdmSuivi");
	$head[$h][2] = 'administrative';
	$h++;

	$head[$h][0] = dol_buildpath('/agefodd/session/document.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("AgfLinkedDocuments");
	$head[$h][2] = 'document';
	$h++;
	
	if ($showconv)
	{
		$head[$h][0] = dol_buildpath('/agefodd/session/convention.php',1).'?sessid='.$object->id;
		$head[$h][1] = $langs->trans("AgfConvention");
		$head[$h][2] = 'convention';
		$h++;
	}
	
	$head[$h][0] = dol_buildpath('/agefodd/session/info.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'agefodd_session');

	return $head;
}

function trainee_prepare_head($object)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();
	
	$head[$h][0] = dol_buildpath('/agefodd/trainee/card.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;
	
	$head[$h][0] = dol_buildpath('/agefodd/trainee/info.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'agefodd_trainee');

	return $head;
}

function trainer_prepare_head($object)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();
	
	$head[$h][0] = dol_buildpath('/agefodd/trainer/card.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;
	
	$head[$h][0] = dol_buildpath('/agefodd/trainer/info.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'agefodd_trainer');

	return $head;
}

function contact_prepare_head($object)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();
	
	$head[$h][0] = dol_buildpath('/agefodd/contact/card.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;
	
	$head[$h][0] = dol_buildpath('/agefodd/contact/info.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'agefodd_contact');

	return $head;
}

function site_prepare_head($object)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();
	
	$head[$h][0] = dol_buildpath('/agefodd/site/card.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;
	
	$head[$h][0] = dol_buildpath('/agefodd/site/info.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'agefodd_site');

	return $head;
}

/**
 *    \brief	Formate une chaine à une longeur donnée en incluant les points finaux
 *    \param	str	la chaine
 *		length	la longueur souhaitée
 *    \return	str	la chaine formatée
 */
function ebi_print_text($str,$length=30)
{
    if (strlen($str) > $length)
    {
	$newLength = ($length - 3);
	$formatStr = substr($str, 0, $newLength).'...';
    }
    else
    {
	$formatStr = $str;
    }
    return $formatStr;

}


/**
 *    \brief	affiche un champs select contenant la liste des civilites.
 *    \param	str	valeur à preselectionner (code ou libelle)
 *		str	nom du champs select
 *    \return	str	la chaine formatée
 */
function ebi_select_civilite($selectid, $name='civilite')
{
	global $db;
	
	$sql = "SELECT c.rowid, c.code, c.civilite";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_civilite as c";
	$sql.= " WHERE active = 1";
	$sql.= " ORDER BY c.civilite";

        $result = $db->query($sql);
	if ($result)
	{
	    $var=True;
	    $num = $db->num_rows($result);
	    $i = 0;
	    $options = '<option value=""></option>'."\n";

	    while ($i < $num)
	    {
		$obj = $db->fetch_object($result);
		if ($obj->rowid == $selectid) $selected = ' selected="true"';
		else $selected = '';
		$options .= '<option value="'.$obj->rowid.'"'.$selected.'>'.$obj->civilite.'</option>'."\n";
		$i++;
	    }
	    $db->free($result);
		
		return '<select class="flat" name="'.$name.'">'."\n".$options."\n".'</select>'."\n";
	}
	else
        {
		$error="Error ".$db->lasterror();
		return -1;
        }
}

/**
 *    \brief	affiche un champs select contenant les pays repertories. 
 *		Permet la préselection par code ou libelle.
 *    \param	str	valeur à preselectionner (code ou libelle)
 *		str	nom du champs select
 *		str	type de la valeure insérée dans le champs (peut être rowid, code, libelle)
 *    \return	str	la chaine formatée
 */
function ebi_select_pays($select_value, $name='pays', $type='libelle')
{
	global $db;
	
	(strlen($select_value) > 2) ? $champs = 'libelle' : $champs = code;
	
	$sql = "SELECT p.rowid, p.code, p.libelle";
	$sql.= " FROM ".MAIN_DB_PREFIX."c_pays as p";
	$sql.= " WHERE p.active = 1";
	$sql.= " ORDER BY p.".$champs;
	
	$result = $db->query($sql);
	if ($result)
	{
		$var = True;
		$num = $db->num_rows($result);
		$i = 0;
		$options = '<option value=""></option>'."\n";
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			if ($obj->$champs == $select_value) $selected = ' selected="true"';
			else $selected = '';
			$options .= '<option value="'.$obj->$type.'"'.$selected.'>'.$obj->libelle.'</option>'."\n";
			$i++;
		}
		$db->free($result);
		return '<select class="flat" name="pays">'."\n".$options."\n".'</select>';
	}
	else
	{
		$error="Error ".$db->lasterror();
		print $error;
		//return -1;
	}
}


/**
 *    \brief	affiche un champs select contenant la liste des formations disponibles. 
 *    \param	str	valeur à preselectionner
 *		str	nom du champs select
 *		str	trie effectué sur le code (code) ou sur le libelle (intitule).
 *    \return	str	la chaine formatée
 */
function ebi_select_formation($selectid, $name='formation', $return='intitule')
{
	global $db;
	
	if ($return == 'code') $order = 'c.ref';
	else $order = 'c.intitule';
		
	$sql = "SELECT c.rowid, c.intitule, c.ref";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c";
	$sql.= " WHERE archive LIKE 0";
	$sql.= " ORDER BY ".$order;

	$result = $db->query($sql);
	if ($result)
	{
	    $var=True;
	    $num = $db->num_rows($result);
	    $i = 0;
	    $options = '<option value=""></option>'."\n";
	
	    while ($i < $num)
	    {
		$obj = $db->fetch_object($result);
		if ($obj->rowid == $selectid) $selected = ' selected="true"';
		else $selected = '';
		$options .= '<option value="'.$obj->rowid.'"'.$selected.'>';
		if ($return == 'code') $options .= $obj->ref.'</option>'."\n";
		else $options .= stripslashes($obj->intitule).'</option>'."\n";
		$i++;
	    }
		$db->free($result);
		return '<select class="flat" name="'.$name.'">'."\n".$options."\n".'</select>'."\n";
	}
	else
	{
		$error="Error ".$db->lasterror();
		return -1;
	}
}

/**
 *    \brief	affiche un champs select contenant la liste des action d'admin des session disponibles.
 *    \param	str	valeur à preselectionner
 *		str	nom du champs select
 *		str	trie effectué sur le code (code) ou sur le libelle (intitule).
 *    \return	str	la chaine formatée
 */
function ebi_select_action_session_adm($selectid='', $html_name='action_level', $excludeid='')
{
	global $db;

 	$sql = "SELECT";
	$sql.= " t.rowid,";
	$sql.= " t.level_rank,";
	$sql.= " t.intitule";
    $sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_admlevel as t";
    if ($excludeid!='') { $sql.= ' WHERE t.rowid<>"'.$excludeid.'"'; }
    $sql.= " ORDER BY t.indice";

	$result = $db->query($sql);
	if ($result)
	{
		$var=True;
		$num = $db->num_rows($result);
		$i = 0;
		$options = '<option value=""></option>'."\n";

		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			if ($obj->rowid == $selectid) $selected = ' selected="true"';
			else $selected = '';
			$strRank=str_repeat('-',$obj->level_rank);
			$options .= '<option value="'.$obj->rowid.'"'.$selected.'>';
			$options .= $strRank.' '.stripslashes($obj->intitule).'</option>'."\n";
			$i++;
		}
		$db->free($result);
		return '<select class="flat" style="width:300px" name="'.$html_name.'">'."\n".$options."\n".'</select>'."\n";
	}
	else
	{
		$error="Error ".$db->lasterror();
		return -1;
	}
}


/**
*  affiche un champs select contenant la liste des action des session disponibles par session.
*
*  @param	$session_id  int	    L'id de la session
*  @param	$selectid  int	    	Id de la session selectionner
*  @param	$html_name  string	    Name of HTML control
*  @return string          			The HTML control
*/
function ebi_select_action_session($session_id=0, $selectid='', $html_name='action_level')
{
	global $db;

	$sql = "SELECT";
	$sql.= " t.rowid,";
	$sql.= " t.level_rank,";
	$sql.= " t.intitule";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as t";
	$sql.= ' WHERE t.fk_agefodd_session="'.$session_id.'"';
	$sql.= " ORDER BY t.indice";

	$result = $db->query($sql);
	if ($result)
	{
		$var=True;
		$num = $db->num_rows($result);
		$i = 0;
		$options = '<option value=""></option>'."\n";

		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			if ($obj->rowid == $selectid) $selected = ' selected="true"';
			else $selected = '';
			$strRank=str_repeat('-',$obj->level_rank);
			$options .= '<option value="'.$obj->rowid.'"'.$selected.'>';
			$options .= $strRank.' '.stripslashes($obj->intitule).'</option>'."\n";
			$i++;
		}
		$db->free($result);
		return '<select class="flat" style="width:300px" name="'.$html_name.'">'."\n".$options."\n".'</select>'."\n";
	}
	else
	{
		$error="Error ".$db->lasterror();
		return -1;
	}
}


/**
 *    \brief	affiche un champs select contenant la liste des sites de formation déjà référéencés.
 *    \param	str	valeur à preselectionner
 *		str	nom du champs select
 *    \return	str	la chaine formatée
 */
function ebi_select_site_forma($selectid, $name='place')
{
	global $db;
	
	$sql = "SELECT p.rowid, p.ref_interne";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_place as p";
	$sql.= " ORDER BY p.ref_interne";

	$result = $db->query($sql);
	if ($result)
	{
		$var=True;
		$num = $db->num_rows($result);
		$i = 0;
		$options = '<option value=""></option>'."\n";;
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			if ($obj->rowid == $selectid) $selected = ' selected="true"';
			else $selected = '';
			$options .= '<option value="'.$obj->rowid.'"'.$selected.'>'.$obj->ref_interne.'</option>'."\n";
			$i++;
	    	}
	    	$db->free($result);
		return '<select class="flat" name="'.$name.'">'."\n".$options."\n".'</select>'."\n";
	}
	else
	{
		$error="Error ".$db->lasterror();
		return -1;
	}
}


/**
 *    \brief	affiche un champs select contenant la liste des stagiaires déjà référéencés.
 *    \param	str	valeur à preselectionner
 *		str	nom du champs select
 *    \return	str	la chaine formatée
 */
function ebi_select_stagiaire($selectid='', $name='stagiaire')
{
	global $db;
	
	$sql = "SELECT";
	$sql.= " s.rowid, CONCAT(s.nom,' ',s.prenom) as fullname,";
	$sql.= " so.nom as socname, so.rowid as socid";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_stagiaire as s";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as so";
	$sql.= " ON so.rowid = s.fk_soc";
	$sql.= " ORDER BY fullname";

	$result = $db->query($sql);
	if ($result)
	{
		$var=True;
		$num = $db->num_rows($result);
		$i = 0;
		$options = '<option value=""></option>'."\n";
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			if ($obj->rowid == $selectid) $selected = ' selected="true"';
			else $selected = '';
			$output_format = $obj->fullname;
			if ($obj->socname) $output_format .= ' ('.$obj->socname.')';
			$options .= '<option value="'.$obj->rowid.'"'.$selected.'>'.$output_format.'</option>'."\n";
			$i++;
	    	}
	    	$db->free($result);
		return '<select class="flat" name="'.$name.'">'."\n".$options."\n".'</select>'."\n";
	}
	else
	{
		$error="Error ".$db->lasterror();
		print $error;
		//return -1;
	}
}


/**
 *    \brief	affiche un champs select contenant la liste des sociétés (tiers).
 *    \param	str	valeur à preselectionner
 *		str	nom du champs select
 *    \return	str	la chaine formatée
 */
function ebi_select_societe($selectid, $name='societe')
{
	global $db;
	
	$sql = "SELECT so.rowid, so.nom";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as so";
	$sql.= " WHERE so.fournisseur = 0";
	$sql.= " ORDER BY so.nom";
	
	$result = $db->query($sql);
	if ($result)
	{
		$var=True;
		$num = $db->num_rows($result);
		$i = 0;
		$options = '<option value=""></option>'."\n";
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			if ($obj->rowid == $selectid) $selected = ' selected="true"';
			else $selected = '';
			$options .= '<option value="'.$obj->rowid.'"'.$selected.'>'.$obj->nom.'</option>'."\n";
			$i++;
		}
		$db->free($result);
		return '<select class="flat" name="'.$name.'">'."\n".$options."\n".'</select>';
	}
	else
	{
		$error="Error ".$db->lasterror();
		print $error;
		//return -1;
	}
}


/**
 *    \brief	affiche un champs select contenant la liste des formateurs déjà référéencés.
 *    \param	str	valeur à preselectionner
 *		str	nom du champs select
 *    \return	str	la chaine formatée
 */
function ebi_select_formateur($selectid='', $name='formateur')
{
	global $db;
	
	$sql = "SELECT";
	$sql.= " s.rowid, s.fk_socpeople,";
	$sql.= " s.rowid, CONCAT(sp.name,' ',sp.firstname) as fullname";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_formateur as s";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp";
	$sql.= " ON sp.rowid = s.fk_socpeople";
	$sql.= " ORDER BY fullname";

	$result = $db->query($sql);
	if ($result)
	{
		$var=True;
		$num = $db->num_rows($result);
		$i = 0;
		$options = '<option value=""></option>'."\n";
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			if ($obj->rowid == $selectid) $selected = ' selected="true"';
			else $selected = '';
			$options .= '<option value="'.$obj->rowid.'"'.$selected.'>'.$obj->fullname.'</option>'."\n";
			$i++;
	    	}
	    	$db->free($result);
		return '<select class="flat" name="'.$name.'">'."\n".$options."\n".'</select>'."\n";
	}
	else
	{
		$error="Error ".$db->lasterror();
		print $error;
		//return -1;
	}
}


/**
 *    \brief	affiche un champs select contenant les contacts (tous ou groupés par société). 
 *    \param	str	nom du champs select
 *		int	éventuellement, id de la société à laquelle les contacts doivent être rattachés
 *    \return	str	la chaine formatée
 */
function ebi_select_contacts($name='place', $socid=0)
{
	global $db;
	
	$sql = "SELECT p.rowid, p.name, p.firstname, p.fk_soc";
	$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as p";
	if (!empty($socid)) $sql.= " WHERE p.fk_soc = ".$socid;
	$sql.= " ORDER BY p.name, p.firstname";

	$result = $db->query($sql);
	if ($result)
	{
		$var=True;
		$num = $db->num_rows($result);
		$i = 0;
		$options = '<option value=""></option>'."\n";;
		while ($i < $num)
		{
			$obj = $db->fetch_object($result);
			$options .= '<option value="'.$obj->rowid.'">'.strtoupper($obj->name).' '.$obj->firstname.'</option>'."\n";
			$i++;
	    	}
	    	$db->free($result);
		return '<select class="flat" name="'.$name.'">'."\n".$options."\n".'</select>'."\n";
	}
	else
	{
		$error="Error ".$db->lasterror();
		//print $error;
		return -1;
	}
}

/**
 *    \brief	affiche un champs select contenant la liste des 1/4 d"heures de 7:00 à 20h00. 
 *    \param	str	nom du champs select
 *    \return	str	la liste html formatée
 */
function ebi_select_time($name='period',$preselect='')
{

	$time = 7;
	$heuref = 21;
	$min = 0;
	$options = '<option value=""></option>'."\n";;
	while ($time < $heuref)
	{
		if ( $min == 60) 
		{
			$min = 0;
			$time ++;
		}
		$ftime = sprintf("%02d", $time).':'.sprintf("%02d", $min); 
		if ($preselect == $ftime.':00') $selected = ' selected="true"';
		else $selected = '';
		$options .= '<option value="'.$ftime.'"'.$selected.'>'.$ftime.'</option>'."\n";
		$min += 15;
	}
	return '<select class="flat" name="'.$name.'">'."\n".$options."\n".'</select>'."\n";
}


/**
 *    \brief	affiche un champs select contenant une liste incrémenter de "incr" à partir de "deb" jusqu'à "fin". 
 *    \param	str	nom du champs select
 *    \param	str	valeur de l'incrément
 *    \param	str	valeur de début
 *    \param	str	valeur de fin
 *    \return	str	la liste html formatée
 */
function ebi_select_number($name='nombre',$preselect='', $incr=1, $deb=1, $fin=10)
{

	$number = $deb;
	$options = '<option value=""></option>'."\n";
	while ($number <= $fin)
	{
		if ($preselect == $number) $selected = ' selected="true"';
		else $selected = '';
		$options .= '<option value="'.$number.'"'.$selected.'>'.$number.'</option>'."\n";
		$number++;
	}
	return '<select class="flat" name="'.$name.'">'."\n".$options."\n".'</select>'."\n";
}


/**
 *    \brief	affiche un champs select contenant la liste des financements possible pour un stagiaire
 *		(nécessaire pour la Declaration annuelle de Formation Professionnelle). 
 *    \param	int	id du champs préselectionné
 *    \param	str	nom du champs select
 *    \return	str	la liste html formatée
 */
function ebi_select_type_stagiaire($selectid, $name='stagiaire_type')
{
	global $db;
	
	$sql = "SELECT t.rowid, t.intitule";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_stagiaire_type as t";
	//$sql.= " ORDER BY t.intitule";
	$sql.= " ORDER BY t.order";

        $result = $db->query($sql);
	if ($result)
	{
	    $var = True;
	    $num = $db->num_rows($result);
	    $i = 0;
	    $options = '<option value=""></option>'."\n";

	    while ($i < $num)
	    {
		$obj = $db->fetch_object($result);
		if ($obj->rowid == $selectid) $selected = ' selected="true"';
		else $selected = '';
		$options .= '<option value="'.$obj->rowid.'"'.$selected.'>'.stripslashes($obj->intitule).'</option>'."\n";
		$i++;
	    }
	    $db->free($result);
		
		return '<select class="flat" name="'.$name.'">'."\n".$options."\n".'</select>'."\n";
	}
	else
        {
		$error="Error ".$db->lasterror();
		return -1;
        }
}


/**
 *    \brief	formate une jauge permettant d'afficher le niveau l'état du traitement des tâches administratives
 *    \param	int	valeur de l'état actuel
 *    \param	int	valeur de l'état quand toutes les tâches sont remplies
 *    \param	str	légende précédent la jauge
 *    \return	str	la jauge formatée au format html
 */
function ebi_level_graph($actual_level, $total_level, $title)
{
	$str = '<table style="border:0px; margin:0px; padding:0px">'."\n";
	$str.= '<tr style="border:0px;"><td style="border:0px; margin:0px; padding:0px">'.$title.' : </td>'."\n";
	for ( $i=0; $i< $total_level; $i++ )
	{
		if ($i < $actual_level) $color = 'green'; 
		else $color = '#d5baa8';
		$str .= '<td style="border:0px; margin:0px; padding:0px" width="10px" bgcolor="'.$color.'">&nbsp;</td>'."\n";
	}
	$str.= '</tr>'."\n";
	$str.= '</table>'."\n";

	return $str;
}


/**
 *    \brief	Calcule le nombre de regroupement par premier niveau des tâches adminsitratives
 *    \return	str	nbre de niveaux
 */
function ebi_get_adm_level_number()
{
	global $db;
	
	$sql = "SELECT l.rowid, l.level_rank";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_admlevel as l";
	$sql.= " WHERE l.level_rank = 0";

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
	    $db->free($result);
		return $num;
	}
	else
	{
		$error="Error ".$db->lasterror();
		return -1;
	}
}

/**
 *    \brief	Calcule le nombre de regroupement par premier niveau des tâches par session
 *    \param	$sessionid int	id de la session
 *    \return	str	nbre de niveaux
 */
function ebi_get_level_number($session)
{
	global $db;

	$sql = "SELECT l.rowid, l.level_rank";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as l";
	$sql.= " WHERE l.level_rank = 0 AND l.fk_agefodd_session=".$session;

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$db->free($result);
		return $num;
	}
	else
	{
		$error="Error ".$db->lasterror();
		return -1;
	}
}


/**
 *    \brief	Calcule le nombre de regroupement par premier niveau terminés pour une session donnée
 *    \param	int	rowid de la session
 *    \return	str	nbre de niveaux
 */
function ebi_get_adm_lastFinishLevel($sessid)
{
	global $db;
	
	$sql = "SELECT COUNT(*) as level";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as s";
	$sql.= ' WHERE s.level_rank = 0 AND s.datef < '.$db->idate(dol_now()).' ';
	$sql.= " AND fk_agefodd_session = ".$sessid;

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
	    	$obj = $db->fetch_object($result);

		$db->free($result);
		return $obj->level;
	}
	else
	{
		$error="Error ".$db->lasterror();
		//print $error;
		return -1;
	}
}

/**
 *    \brief	Calcule le nombre de d'action filles
 *    \param	int	rowid du niveaux
 *    \return	str	nbre d d'action
 */
function ebi_get_adm_indice_action_child($id)
{
	global $db;

	$sql = "SELECT MAX(s.indice) as nb_action";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_admlevel as s";
	$sql.= " WHERE fk_parent_level=".$id;

	dol_syslog("agefodd:lib:ebi_get_adm_indice_action_child sql=".$sql, LOG_DEBUG);
	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$obj = $db->fetch_object($result);

		$db->free($result);
		return $obj->nb_action;
	}
	else
	{
		$error="Error ".$db->lasterror();
		return -1;
	}
}

/**
 *    \brief	Calcule l'indice min ou max d'un niveau
 *    \param	int	lvl_rank Rang des actions a tester
 *    \param	int	parent_level niveau parent
 *    \param	str	type MIN ou MAX
 *    \return	str	l'indice min ou max
 */
function ebi_get_adm_indice_per_rank($lvl_rank,$parent_level='',$type='MIN')
{
	global $db;

	$sql = "SELECT ";
	if ($type=='MIN')
	{
		$sql.= ' MIN(s.indice) ';
	}
	else
	{
		$sql.= ' MAX(s.indice) ';
	}
	$sql.= " as indice";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_admlevel as s";
	$sql.= " WHERE s.level_rank=".$lvl_rank;
	if ($parent_level!='')
	{
		$sql.= " AND s.fk_parent_level=".$parent_level;
	}

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$obj = $db->fetch_object($result);

		$db->free($result);
		return $obj->indice;
	}
	else
	{
		$error="Error ".$db->lasterror();
		return -1;
	}
}

/**
 *    \brief	Formatage d'un menu aide en html (icone + curseur)
 *    \param	str	l'aide à afficher quand la souris survole l'icône
 *    \param	str	légende à afficher pour l'image
 *    \return	str	chaine formatée en html
 */
function ebi_help($desc, $legend="")
{
	global $conf;

	$mess = '<span onmouseover="showtip(\''.$desc.'\')" onmouseout="hidetip()" width="14"><img style="cursor: help;" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/info.png" alt="'.$legend.'" title="'.$legend.'"border="0" align="absmiddle"></span>';

	return $mess;
}

/**
 *    \brief	Formatage d'une liste à puce
 *    \param	str	txt la chaine
 *   \param	bool	html sortie au format html (true) ou texte (false)
 *   \return	str	liste formatée
 */
function ebi_liste_a_puce($text, $form=false)
{
	// 1er niveau: remplacement de '# ' en debut de ligne par une puce de niv 1 (petit rond noir)
	// 2éme niveau: remplacement de '## ' en début de ligne par une puce de niv 2 (tiret)
	// 3éme niveau: remplacement de '### ' en début de ligne par une puce de niv 3 (>)
	// Pour annuler le formatage (début de ligne sur la mage gauche : '!#'
	$str = "";
	$line = explode("\n", $text);
	$level = 0;
	foreach ($line as $row)
	{
		if ($form)
		{
			if (preg_match('/^\!# /', $row))
			{
				if ($level == 1) $str.= '</ul>'."\n";
				if ($level == 2) $str.= '<ul>'."\n".'</ul>'."\n";
				if ($level == 3) $str.= '</ul>'."\n".'</ul>'."\n".'</ul>'."\n";
				$str.= preg_replace('/^\!# /', '', $row.'<br />')."\n";
			}
			elseif (preg_match('/^# /', $row))
			{
				if ($level == 0) $str.= '<ul>';
				if ($level == 2) $str.= '</ul>'."\n";
				if ($level == 3) $str.= '</ul>'."\n".'</ul>'."\n";
				$str.= '<li>'.preg_replace('/^# /', '', $row).'</li>'."\n";
				$level = 1;
			}
			elseif (preg_match('/^## /', $row))
			{
				if ($level == 1) $str.= '<ul>';
				if ($level == 3) $str.= '</ul>'."\n";
				$str.= '<li>'.preg_replace('/^## /', '', $row).'</li>'."\n";
				$level = 2;
			}
			elseif (preg_match('/^### /', $row)) 
			{
				if ($level == 2) $str.= '<ul>';
				$str.= '<li>'.preg_replace('/^### /', '', $row).'</li>'."\n";
				$level = 3;
			}
			else $str.= '   '.$row.'<br />'."\n";

		}
		else
		{
			if (preg_match('/^\!# /', $row)) $str.= preg_replace('/^\!# /', '', $row)."\n";
			elseif (preg_match('/^# /', $row)) $str.= chr(149).' '.preg_replace('/^#/', '', $row)."\n";
			elseif (preg_match('/^## /', $row)) $str.= '   '.'-'.preg_replace('/^##/', '', $row)."\n";
			elseif (preg_match('/^### /', $row)) $str.= '   '.'  '.chr(155).' '.preg_replace('/^###/', '', $row)."\n";
			else $str.= '   '.$row."\n";
		}
	}
	return $str;
}



/**
 *    \brief	Calcule le next number d'indice pour une action
 *    \param	int	rowid du niveaux
 *    \return	str	nbre d d'action
 */
function ebi_get_adm_get_next_indice_action($id)
{
	global $db;

	$sql = "SELECT MAX(s.indice) as nb_action";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_admlevel as s";
	$sql.= " WHERE fk_parent_level=".$id;

	dol_syslog("agefodd:lib:ebi_get_adm_get_next_indice_action sql=".$sql, LOG_DEBUG);
	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$obj = $db->fetch_object($result);
		$db->free($result);
		if (!empty($obj->nb_action)) 
		{
			return intval(intval($obj->nb_action) + 1);
		}
		else
		{
			$sql = "SELECT MAX(s.indice) as nb_action";
			$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_admlevel as s";
			$sql.= " WHERE fk_parent_level=(SELECT fk_parent_level FROM ".MAIN_DB_PREFIX."agefodd_session_admlevel WHERE rowid=".$id.")";
			
			dol_syslog("agefodd:lib:ebi_get_adm_get_next_indice_action sql=".$sql, LOG_DEBUG);
			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$obj = $db->fetch_object($result);
			
				$db->free($result);
				return intval(intval($obj->nb_action) + 1);
			}
			else
			{
			
				$error="Error ".$db->lasterror();
				return -1;
			}
		}
	}
	else
	{
		
		$error="Error ".$db->lasterror();
		return -1;
	}
}

/**
 *    \brief	Calcule le next number d'indice pour une action
 *    \param	int	rowid du niveaux
 *    \param	int	sessionid Id de la sessino
 *    \return	str	nbre d d'action
 */
function ebi_get_next_indice_action($id,$sessionid)
{
	global $db;

	$sql = "SELECT MAX(s.indice) as nb_action";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as s";
	$sql.= " WHERE fk_parent_level=".$id;
	$sql.= " AND fk_agefodd_session=".$sessionid;

	dol_syslog("ebi_get_get_next_indice_action sql=".$sql, LOG_DEBUG);
	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);
		$obj = $db->fetch_object($result);
		$db->free($result);
		if (!empty($obj->nb_action))
		{
			return intval(intval($obj->nb_action) + 1);
		}
		else
		{
			$sql = "SELECT MAX(s.indice) as nb_action";
			$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as s";
			$sql.= " WHERE fk_parent_level=(SELECT fk_parent_level FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu WHERE rowid=".$id." AND fk_agefodd_session=".$sessionid.")";
			$sql.= " AND fk_agefodd_session=".$sessionid;
				
			dol_syslog("ebi_get_get_next_indice_action sql=".$sql, LOG_DEBUG);
			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$obj = $db->fetch_object($result);
					
				$db->free($result);
				return intval(intval($obj->nb_action) + 1);
			}
			else
			{
					
				$error="Error ".$db->lasterror();
				return -1;
			}
		}
	}
	else
	{

		$error="Error ".$db->lasterror();
		return -1;
	}
}

#llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
