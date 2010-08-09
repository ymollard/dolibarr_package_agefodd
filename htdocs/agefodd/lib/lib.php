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
 *  \file       	$HeadURL: https://192.168.22.4/dolidev/trunk/agefodd/lib/lib.php $
 *  \brief      	Page fiche d'une operation sur CCA
 *  \version		$Id: lib.php 54 2010-03-30 18:58:28Z ebullier $
 */

$langs->load("@agefodd");


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
	
	if ($return == 'code') $order = 'c.ref_interne';
	else $order = 'c.intitule';
		
	$sql = "SELECT c.rowid, c.intitule, c.ref_interne";
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
		if ($return == 'code') $options .= $obj->ref_interne.'</option>'."\n";
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
 *    \brief	affiche un champs select contenant la liste des sites de formation déjà référéencés.
 *    \param	str	valeur à preselectionner
 *		str	nom du champs select
 *    \return	str	la chaine formatée
 */
function ebi_select_site_forma($selectid, $name='place')
{
	global $db;
	
	$sql = "SELECT p.rowid, p.code";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_place as p";
	$sql.= " ORDER BY p.code";

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
			$options .= '<option value="'.$obj->rowid.'"'.$selected.'>'.$obj->code.'</option>'."\n";
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
	if (!empty($socid)) $sql.= " WHERE = p.fk_soc = ".$socid;
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
 *    \brief	formate une chaine de type string avant sont utilisation dans une requête SQL.
 *    \param	str	la chaine
 *		str	En fonction du système installé, on devra utiliser
 *			- la nouvelle syntaxe "mysql_real_escape_string" ($real="new")
 *			- l'ancienne syntaxe "mysql_escape_string" ($real="old")
 *    \return	str	la chaine formatée
 */
function ebi_mysql_escape_string($string, $real='old')
{
	$string = addslashes($string);
	if ($real == 'new')
	{
		
		return mysql_real_escape_string($string);
	}
	else
	{
		return mysql_escape_string($string);
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
 *    \brief	Calcule le nombre de regroupement par premier niveau des  tâches adminsitratives
 *    \return	str	nbre de niveaux
 */
function ebi_get_adm_level_number()
{
	global $db;
	

	$sql = "SELECT l.rowid, l.top_level";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_admlevel as l";
	$sql.= " WHERE l.top_level = 'Y'";

	

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
		//print $error;
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
	
	/*
	$sql = "SELECT COUNT(s.indice) as level";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as s";
	$sql.= " WHERE s.top_level = 'Y' AND s.datef != '0000-00-00 00:00:00'";
	$sql.= " AND fk_agefodd_session = ".$sessid;
	$sql.= " GROUP BY s.indice ORDER BY s.indice DESC LIMIT 1";
	*/
	$sql = "SELECT COUNT(*) as level";
	$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as s";
	$sql.= " WHERE s.top_level = 'Y' AND s.datef != '0000-00-00 00:00:00'";
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
 *    \brief	Prend une duree au format hh:mm:ss et retourne un tableau
 *    \param	str	la chaine
  *   \return	str	la chaine formatée
 */
function ebi_time_array($time)
{
	global $conf;

	$arraytime = explode(':' ,$time);
	$newtime['h'] = $arraytime[0];
	$newtime['m'] = $arraytime[1];

	return $newtime;
}


/**
 *    \brief	Transforme une date du format Datetime mysql au format timestamp
 *    \param	str	la chaine
  *   \return	str	la chaine formatée
 */
function mysql2timestamp($datetime)
{
	$val = explode(" ",$datetime);
	$date = explode("-",$val[0]);
	$time = explode(":",$val[1]);
	
	return mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
}


/**
 *    \brief	Formatage d'une liste à puce
 *    \param	str	txt la chaine
 *   \param	bool	html sortie au format html (true) ou texte (false)
 *   \return	str	liste formatée
 */
function ebi_liste_a_puce($text, $html=false)
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
		if ($html)
		{
			if (preg_match('/^\!# /', $row))
			{
				if ($level == 1) $str.= '</ul>'."\n";
				if ($level == 2) $str.= '<ul>'."\n".'</ul>'."\n";
				if ($level == 3) $str.= '</ul>'."\n".'</ul>'."\n".'</ul>'."\n";
				$str.= ereg_replace('!# ', '', $row.'<br />')."\n";
			}
			elseif (preg_match('/^# /', $row))
			{
				if ($level == 0) $str.= '<ul>';
				if ($level == 2) $str.= '</ul>'."\n";
				if ($level == 3) $str.= '</ul>'."\n".'</ul>'."\n";
				$str.= '<li>'.ereg_replace('# ', '', $row).'</li>'."\n";
				$level = 1;
			}
			elseif (preg_match('/^## /', $row))
			{
				if ($level == 1) $str.= '<ul>';
				if ($level == 3) $str.= '</ul>'."\n";
				$str.= '<li>'.ereg_replace('## ', '', $row).'</li>'."\n";
				$level = 2;
			}
			elseif (preg_match('/^### /', $row)) 
			{
				if ($level == 2) $str.= '<ul>';
				$str.= '<li>'.ereg_replace('### ', '', $row).'</li>'."\n";
				$level = 3;
			}
			else $str.= '   '.$row.'<br />'."\n";

		}
		else
		{
			if (preg_match('/^\!# /', $row)) $str.= ereg_replace('!# ', '', $row)."\n";
			elseif (preg_match('/^# /', $row)) $str.= chr(149).' '.ereg_replace('#', '', $row)."\n";
			elseif (preg_match('/^## /', $row)) $str.= '   '.'-'.ereg_replace('##', '', $row)."\n";
			elseif (preg_match('/^### /', $row)) $str.= '   '.'  '.chr(155).' '.ereg_replace('###', '', $row)."\n";
			else $str.= '   '.$row."\n";
		}
	}
	return $str;
}

#llxFooter('$Date: 2010-03-30 20:58:28 +0200 (mar. 30 mars 2010) $ - $Revision: 54 $');
?>
