<?php
/* Copyright (C) 2012       Florian Henry   <florian.henry@open-concept.pro>
 * Copyright (C) 2012       JF FERRY        <jfefe@aternatik.fr>

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
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 *      \file       agefodd/core/class/html.formagefodd.class.php
*      \brief      Fichier de la classe des fonctions predefinie de composants html agefodd
*/


/**
 *      Class to manage building of HTML components
*/
class FormAgefodd extends Form
{
	var $db;
	var $error;

	var $type_session_def;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $langs;
		$this->db = $db;
		$this->type_session_def = array(0=> $langs->trans('AgfFormTypeSessionIntra'), 1 => $langs->trans('AgfFormTypeSessionInter') );
		return 1;
	}

	/**
	 * Affiche un champs select contenant la liste des formations disponibles.
	 *
	 * @param   int 	$selectid		Valeur à preselectionner
	 * @param   string	$htmlname		Name of select field
	 * @param   string	$sort			Name of Value to show/edit (not used in this function)
	 * @param	 int	$showempty		Add an empty field
	 * @param	 int	$forcecombo		Force to use combo box
     * @param	 array	$event			Event options
	 * @return	string					HTML select field
	 */
	function select_formation($selectid, $htmlname='formation', $sort='intitule', $showempty=0, $forcecombo=0, $event=array())
	{
		global $conf,$user,$langs;

		$out='';

		if ($sort == 'code') $order = 'c.ref';
		else $order = 'c.intitule';

		$sql = "SELECT c.rowid, c.intitule, c.ref";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_formation_catalogue as c";
		$sql.= " WHERE archive LIKE 0";
		$sql.= " ORDER BY ".$order;

		dol_syslog(get_class($this)."::select_formation sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($conf->use_javascript_ajax && $conf->global->AGF_TRAINING_USE_SEARCH_TO_SELECT && ! $forcecombo)
			{
				$out.= ajax_combobox($htmlname, $event);
			}

			$out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
			if ($showempty) $out.= '<option value="-1"></option>';
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$label=$obj->intitule;

					if ($selectid > 0 && $selectid == $obj->rowid)
					{
						$out.= '<option value="'.$obj->rowid.'" selected="selected">'.$label.'</option>';
					}
					else
					{
						$out.= '<option value="'.$obj->rowid.'">'.$label.'</option>';
					}
					$i++;
				}
			}
			$out.= '</select>';
		}
		else
		{
			dol_print_error($this->db);
		}
		$this->db->free($resql);
		return $out;
	}

	/**
	 * Affiche un champs select contenant la liste des action de session disponibles.
	 *
	 * @param   int 	$selectid		Valeur à preselectionner
	 * @param   string	$htmlname		Name of select field
	 * @param   string	$excludeid		Si il est necessaire d'exclure une valeur de sortie
	 * @return	 string					HTML select field
	 */
	function select_action_session_adm($selectid='', $htmlname='action_level', $excludeid='')
	{
		global $conf,$langs;

		$sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.level_rank,";
		$sql.= " t.intitule";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_admlevel as t";
		if ($excludeid!='') {
			$sql.= ' WHERE t.rowid<>"'.$excludeid.'"';
		}
		$sql.= " ORDER BY t.indice";

		dol_syslog(get_class($this)."::select_action_session_adm sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$var=True;
			$num = $this->db->num_rows($result);
			$i = 0;
			$options = '<option value=""></option>'."\n";

			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				if ($obj->rowid == $selectid) $selected = ' selected="true"';
				else $selected = '';
				$strRank=str_repeat('-',$obj->level_rank);
				$options .= '<option value="'.$obj->rowid.'"'.$selected.'>';
				$options .= $strRank.' '.stripslashes($obj->intitule).'</option>'."\n";
				$i++;
			}
			$this->db->free($result);
			return '<select class="flat" style="width:300px" name="'.$htmlname.'">'."\n".$options."\n".'</select>'."\n";
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::select_action_session_adm ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  affiche un champs select contenant la liste des action des session disponibles par session.
	 *
	 *  @param	int $session_id     L'id de la session
	 *  @param	int $selectid  		Id de la session selectionner
	 *  @param	string $htmlname    Name of HTML control
	 *  @return string         		The HTML control
	 */
	function select_action_session($session_id=0, $selectid='', $htmlname='action_level')
	{
		global $conf,$langs;

		$sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.level_rank,";
		$sql.= " t.intitule";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_session_adminsitu as t";
		$sql.= ' WHERE t.fk_agefodd_session="'.$session_id.'"';
		$sql.= " ORDER BY t.indice";

		dol_syslog(get_class($this)."::select_action_session sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$var=True;
			$num = $this->db->num_rows($result);
			$i = 0;
			$options = '<option value=""></option>'."\n";

			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				if ($obj->rowid == $selectid) $selected = ' selected="true"';
				else $selected = '';
				$strRank=str_repeat('-',$obj->level_rank);
				$options .= '<option value="'.$obj->rowid.'"'.$selected.'>';
				$options .= $strRank.' '.stripslashes($obj->intitule).'</option>'."\n";
				$i++;
			}
			$this->db->free($result);
			return '<select class="flat" style="width:300px" name="'.$htmlname.'">'."\n".$options."\n".'</select>'."\n";
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::select_action_session ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  affiche un champs select contenant la liste des sites de formation déjà référéencés.
	 *
	 *  @param	int 	$selectid  		Id de la session selectionner
	 *  @param	string 	$htmlname 	    Name of HTML control
	 *  @param	int		$showempty		Add an empty field
	 *  @param	int		$forcecombo		Force to use combo box
     *  @param	array	$event			Event options
	 *  @return string         			The HTML control
	 */
	function select_site_forma($selectid, $htmlname='place', $showempty=0, $forcecombo=0, $event=array())
	{
		global $conf,$langs;

		$sql = "SELECT p.rowid, p.ref_interne";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_place as p";
		$sql.= " WHERE archive LIKE 0";
		$sql.= " ORDER BY p.ref_interne";

		dol_syslog(get_class($this)."::select_site_forma sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($conf->use_javascript_ajax && $conf->global->AGF_SITE_USE_SEARCH_TO_SELECT && ! $forcecombo)
			{
				$out.= ajax_combobox($htmlname, $event);
			}

			$out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
			if ($showempty) $out.= '<option value="-1"></option>';
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$label=$obj->ref_interne;

					if ($selectid > 0 && $selectid == $obj->rowid)
					{
						$out.= '<option value="'.$obj->rowid.'" selected="selected">'.$label.'</option>';
					}
					else
					{
						$out.= '<option value="'.$obj->rowid.'">'.$label.'</option>';
					}
					$i++;
				}
			}
			$out.= '</select>';
			$this->db->free($result);
			return $out;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::select_site_forma ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  affiche un champs select contenant la liste des stagiaires déjà référéencés.
	 *
	 *  @param	int 	$selectid  		Id de la session selectionner
	 *  @param	string  $htmlname    	Name of HTML control
	 *  @param	string  $filter     	SQL part for filter
	 *  @param	int		$showempty		Add an empty field
	 *  @param	int		$forcecombo		Force to use combo box
     *  @param	array	$event			Event options
	 *  @return string         		The HTML control
	 */
	function select_stagiaire($selectid='', $htmlname='stagiaire', $filter='', $showempty=0, $forcecombo=0, $event=array())
	{
		global $conf,$langs;

		$sql = "SELECT";
		$sql.= " s.rowid, CONCAT(s.nom,' ',s.prenom) as fullname,";
		$sql.= " so.nom as socname, so.rowid as socid";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_stagiaire as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as so";
		$sql.= " ON so.rowid = s.fk_soc";
		if (!empty($filter)) {
			$sql .= ' WHERE '.$filter;
		}
		$sql.= " ORDER BY fullname";

		dol_syslog(get_class($this)."::select_stagiaire sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($conf->use_javascript_ajax && $conf->global->AGF_TRAINEE_USE_SEARCH_TO_SELECT && ! $forcecombo)
			{
				$out.= ajax_combobox($htmlname, $event);
			}

			$out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
			if ($showempty) $out.= '<option value="-1"></option>';
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$label = $obj->fullname;
					if ($obj->socname) $label .= ' ('.$obj->socname.')';

					if ($selectid > 0 && $selectid == $obj->rowid)
					{
						$out.= '<option value="'.$obj->rowid.'" selected="selected">'.$label.'</option>';
					}
					else
					{
						$out.= '<option value="'.$obj->rowid.'">'.$label.'</option>';
					}
					$i++;
				}
			}
			$out.= '</select>';
			$this->db->free($result);
			return $out;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::select_stagiaire ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  affiche un champs select contenant la liste des contact déjà référéencés.
	 *
	 *  @param	int 	$selectid  		Id de la session selectionner
	 *  @param	string  $htmlname    	Name of HTML control
	 *  @param	string  $filter     	SQL part for filter
	 *  @param	int		$showempty		Add an empty field
	 *  @param	int		$forcecombo		Force to use combo box
	 *  @param	array	$event			Event options
	 *  @return string         		The HTML control
	 */
	function select_agefodd_contact($selectid='', $htmlname='contact', $filter='', $showempty=0, $forcecombo=0, $event=array())
	{
		global $conf,$langs;

			$sql = "SELECT";
		$sql.= " c.rowid, ";
		$sql.= " s.name, s.firstname, s.civilite, ";
		$sql.= " soc.nom as socname";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_contact as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as s ON c.fk_socpeople = s.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON soc.rowid = s.fk_soc";
		$sql.= " WHERE c.archive LIKE 0";
		if (!empty($filter)) {
			$sql .= ' AND '.$filter;
		}
		$sql.= " ORDER BY socname";

		dol_syslog(get_class($this)."::select_agefodd_contact sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($conf->use_javascript_ajax && $conf->global->AGF_CONTACT_USE_SEARCH_TO_SELECT && ! $forcecombo)
			{
				$out.= ajax_combobox($htmlname, $event);
			}

			$out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
			if ($showempty) $out.= '<option value="-1"></option>';
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$label = $obj->firstname.' '.$obj->name;
					if ($obj->socname) $label .= ' ('.$obj->socname.')';

					if ($selectid > 0 && $selectid == $obj->rowid)
					{
						$out.= '<option value="'.$obj->rowid.'" selected="selected">'.$label.'</option>';
					}
					else
					{
						$out.= '<option value="'.$obj->rowid.'">'.$label.'</option>';
					}
					$i++;
				}
			}
			$out.= '</select>';
			$this->db->free($result);
			return $out;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::select_agefodd_contact ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *  affiche un champs select contenant la liste des formateurs déjà référéencés.
	 *
	 *  @param	int     $selectid  		Id de la session selectionner
	 *  @param	string  $htmlname    Name of HTML control
	 *  @param	string  $filter      SQL part for filter
	 *  @param	int		$showempty		Add an empty field
	 *  @param	int		$forcecombo		Force to use combo box
     *  @param	array	$event			Event options
	 *  @return string         		The HTML control
	 */
	function select_formateur($selectid='', $htmlname='formateur', $filter='', $showempty=0, $forcecombo=0, $event=array())
	{
		global $conf,$langs;

		$sql = "SELECT";
		$sql.= " s.rowid, s.fk_socpeople,";
		$sql.= " s.rowid, CONCAT(sp.name,' ',sp.firstname) as fullname";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_formateur as s";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as sp";
		$sql.= " ON sp.rowid = s.fk_socpeople";
		$sql.= " WHERE s.archive LIKE 0";
		if (!empty($filter)) {
			$sql .= ' AND '.$filter;
		}
		$sql.= " ORDER BY fullname";

		dol_syslog(get_class($this)."::select_formateur sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {

			if ($conf->use_javascript_ajax && $conf->global->AGF_TRAINER_USE_SEARCH_TO_SELECT && ! $forcecombo)
			{
				$out.= ajax_combobox($htmlname, $event);
			}

			$out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
			if ($showempty) $out.= '<option value="-1"></option>';
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$label = $obj->fullname;

					if ($selectid > 0 && $selectid == $obj->rowid)
					{
						$out.= '<option value="'.$obj->rowid.'" selected="selected">'.$label.'</option>';
					}
					else
					{
						$out.= '<option value="'.$obj->rowid.'">'.$label.'</option>';
					}
					$i++;
				}
			}
			$out.= '</select>';
			$this->db->free($result);
			return $out;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::select_formateur ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  affiche un champs select contenant la liste des financements possible pour un stagiaire
	 *
	 *  @param	int     $selectid  		Id de la session selectionner
	 *  @param	string  $htmlname    Name of HTML control
	 *  @param	string  $filter      SQL part for filter
	 *  @param	int		$showempty		Add an empty field
	 *  @param	int		$forcecombo		Force to use combo box
	 *  @param	array	$event			Event options
	 *  @return string         		The HTML control
	 */
	function select_type_stagiaire($selectid, $htmlname='stagiaire_type', $filter='', $showempty=0, $forcecombo=0, $event=array())
	{
		global $conf,$langs;

		$sql = "SELECT t.rowid, t.intitule";
		$sql.= " FROM ".MAIN_DB_PREFIX."agefodd_stagiaire_type as t";
		if (!empty($filter)) {
			$sql .= ' WHERE '.$filter;
		}
		$sql.= " ORDER BY t.sort";

		dol_syslog(get_class($this)."::select_type_stagiaire sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{

			if ($conf->use_javascript_ajax && $conf->global->AGF_STAGTYPE_USE_SEARCH_TO_SELECT && ! $forcecombo)
			{
				$out.= ajax_combobox($htmlname, $event);
			}

			$out.= '<select id="'.$htmlname.'" class="flat" name="'.$htmlname.'">';
			if ($showempty) $out.= '<option value="-1"></option>';
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$label = stripslashes($obj->intitule);

					if ($selectid > 0 && $selectid == $obj->rowid)
					{
						$out.= '<option value="'.$obj->rowid.'" selected="selected">'.$label.'</option>';
					}
					else
					{
						$out.= '<option value="'.$obj->rowid.'">'.$label.'</option>';
					}
					$i++;
				}
			}
			$out.= '</select>';
			$this->db->free($result);
			return $out;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::select_type_stagiaire ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *  Formate une jauge permettant d'afficher le niveau l'état du traitement des tâches administratives
	 *
	 *  @param	int $actual_level  		valeur de l'état actuel
	 *  @param	int $total_level    valeur de l'état quand toutes les tâches sont remplies
	 *  @param	string $title      légende précédent la jauge
	 *  @return string         		The HTML control
	 */
	function level_graph($actual_level, $total_level, $title)
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
	 *  Affiche un champs select contenant la liste des 1/4 d"heures de 7:00 à 21h00.
	 *
	 *  @param	string $selectval  	valeur a selectionner par defaut
	 *  @param	string $htmlname    nom du control HTML
	 *  @return string         		The HTML control
	 */
	function select_time($selectval='', $htmlname='period')
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
			if ($selectval == $ftime) $selected = ' selected="true"';
			else $selected = '';
			$options .= '<option value="'.$ftime.'"'.$selected.'>'.$ftime.'</option>'."\n";
			$min += 15;
		}
		return '<select class="flat" name="'.$htmlname.'">'."\n".$options."\n".'</select>'."\n";
	}

	/**
	 * Affiche une liste de sélection des types de formation
	 *
	 *  @param	string	$htmlname	nom du control HTML
	 *  @param	int		$selectval	valeur a selectionner par defaut
	 *  @return string				The HTML control
	 */
	function select_type_session($htmlname,$selectval)
	{
		return $this->selectarray($htmlname,$this->type_session_def,$selectval,0);
	}

	/**
	 *  Show list of actions for element
	 *
	 *  @param	Object	$object			Object
	 *  @param  string	$typeelement	'agefodd_agsession'
	 *	@param	int		$socid			socid of user
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	function showactions($object,$typeelement='agefodd_agsession',$socid=0)
	{
		global $langs,$conf,$user;
		global $bc;

		require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");

		$actioncomm = new ActionComm($this->db);
		$actioncomm->getActions($socid, $object->id, $typeelement);

		$num = count($actioncomm->actions);
		if ($num)
		{
			if ($typeelement == 'agefodd_agsession')   $title=$langs->trans('AgfActionsOnTraining');
			//elseif ($typeelement == 'fichinter') $title=$langs->trans('ActionsOnFicheInter');
			else $title=$langs->trans("Actions");

			print_titre($title);

			$total = 0;	$var=true;
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre"><th class="liste_titre">'.$langs->trans('Ref').'</th><th class="liste_titre">'.$langs->trans('Date').'</th><th class="liste_titre">'.$langs->trans('Action').'</th><th class="liste_titre">'.$langs->trans('ThirdParty').'</th><th class="liste_titre">'.$langs->trans('By').'</th></tr>';
			print "\n";

			foreach($actioncomm->actions as $action)
			{
				$var=!$var;
				print '<tr '.$bc[$var].'>';
				print '<td>'.$action->getNomUrl(1).'</td>';
				print '<td>'.dol_print_date($action->datep,'dayhour').'</td>';
				print '<td title="'.dol_escape_htmltag($action->label).'">'.dol_trunc($action->label,50).'</td>';
				$userstatic = new User($this->db);
				$userstatic->id = $action->author->id;
				$userstatic->firstname = $action->author->firstname;
				$userstatic->lastname = $action->author->lastname;
				print '<td>'.$userstatic->getElementUrl($action->socid, 'societe',1).'</td>';
				print '<td>'.$userstatic->getNomUrl(1).'</td>';
				print '</tr>';
			}
			print '</table>';
		}

		return $num;
	}

}