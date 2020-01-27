<?php

/*
 * script to normalize agefodd rights
 */

/**
 * @param $modAgefodd modAgefodd
 * @return array
 */
function getRightsToUpdate($modAgefodd)
{
	global $db;

	$TRights = array();
	$sql = "SELECT DISTINCT r.id, r.libelle, r.module, r.entity, r.perms, r.subperms, r.type, r.bydefault FROM ".MAIN_DB_PREFIX."rights_def r";
	$sql.= " WHERE r.module = '".$modAgefodd->rights_class."'";
	$sql.= " AND r.id > 103000 AND r.id <= 103025";

	$resql = $db->query($sql);
	if ($resql && $db->num_rows($resql))
	{
		while($obj = $db->fetch_object($resql)) $TRights[$obj->id][$obj->entity] = $obj;
	}

	return $TRights;
}

function fixAgefoddRights($TRights = array(), $mod_number = 0)
{
	global $db, $conf;

	if (empty($TRights)) return 0;
	if (empty($mod_number)) return -1;

	$fail = false;
	$sqlur = $sqlug = $sqlrd = "";
	$db->begin();
	foreach ($TRights as $r_id => $data)
	{
		foreach ($data as $entity => $right)
		{

			// création des droits numérotés comme il faut
			if ($entity != $conf->entity)
			{
				$sqlrd = "INSERT INTO ".MAIN_DB_PREFIX."rights_def (`id`, `libelle`, `module`, `entity`, `perms`, `subperms`, `type`, `bydefault`) VALUES ("
					.$mod_number.(intval(substr($r_id, -2)) -1).","
					."'".$right->libelle."',"
					."'".$right->module."',"
					.$right->entity.","
					."'".$right->perms."',"
					."'".$right->subperms."',"
					."'".$right->type."',"
					.$right->bydefault." );";

				$res = $db->query($sqlrd);
				if (!$res) {
					$fail = true;
					break 2;
				}
			}

			//print '<p>'.$sqlrd.'<p>';

			// UPDATE des droits user
			// 1. check no Duplicate entry for key 'uk_user_rights'
			$sqlCountUr = "SELECT COUNT(*) as countNumb FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_id=".$mod_number.(intval(substr($r_id, -2)) -1)." AND entity = ".$entity.";";
			$res = $db->query($sqlCountUr);
			//print '<p>'.$sqlCountUr.'<p>';
			if ($res) {
				$count = $db->fetch_object($res);
				if(empty($count->countNumb))
				{
					// 2. UPDATE des droits user
					$sqlur = "UPDATE ".MAIN_DB_PREFIX."user_rights SET fk_id=".$mod_number.(intval(substr($r_id, -2)) -1)." WHERE fk_id = ".$r_id." AND entity = ".$entity.";";
					//print '<p>'.$sqlur.'<p>';
					$res = $db->query($sqlur);
					if (!$res) {
						$fail = true;
						break 2;
					}
				}
			}
			else{
				setEventMessage('Error : update user right count query', 'errors');
				$fail = true;
				break 2;
			}


			// UPDATE des droits group
			// 1. check no Duplicate entry for key 'uk_group_rights'
			$sqlCountUg = "SELECT COUNT(*) as countNumb FROM ".MAIN_DB_PREFIX."usergroup_rights WHERE fk_id=".$mod_number.(intval(substr($r_id, -2)) -1)." AND entity = ".$entity.";";
			$res = $db->query($sqlCountUg);
			//print '<p>'.$sqlCountUg.'<p>';

			if ($res) {
				$count = $db->fetch_object($res);
				if(empty($count->countNumb))
				{
					// 2. UPDATE des droits groupes
					$sqlug = "UPDATE ".MAIN_DB_PREFIX."usergroup_rights SET fk_id=".$mod_number.(intval(substr($r_id, -2)) -1)." WHERE fk_id = ".$r_id." AND entity = ".$entity.";";
					//print '<p>'.$sqlug.'<p>';
					$res = $db->query($sqlug);
					if (!$res) {
						$fail = true;
						break 2;
					}
				}
			}
			else{
				setEventMessage('Error : update group right count query', 'errors');
				$fail = true;
				break 2;
			}



		}
		$TRights_id[] = $r_id;

	}

	if(!empty($TRights_id) and is_array($TRights_id))
	{
		// suppression des droits erronés
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE id IN (".implode(', ', $TRights_id).");";
		//print '<p>'.$sql.'<p><hr/>';
		$resql = $db->query($sql);
		if (!$resql) $fail = true;
	}


	if ($fail)
	{
		$db->rollback();
		return -2;
	}
	else
	{
		$db->commit();
		return 1;
	}

}
