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
	
	$head[$h][0] = dol_buildpath('/agefodd/session/subscribers.php',1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("AgfParticipant");
	$head[$h][2] = 'subscribers';
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

    complete_head_from_modules($conf,$langs,$object,$head,$h,'agefodd_site');

	return $head;
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
