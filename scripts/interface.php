<?php

$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");


dol_include_once('/core/lib/functions.lib.php');



global $db;

$langs->load('clia2a@clia2a');



/*
 * Action
 */
$data = $_POST;
$data['result'] = 0; // by default if no action result is false
$data['errorMsg'] = ''; // default message for errors



	// do action from GETPOST ...
	if(GETPOST('action'))
	{
		$action = GETPOST('action');


		if($action == 'get_duration_and_product')
		{
			$fk_training= GETPOST('fk_training');
			if(!empty($fk_training)){
				$sql="SELECT cat.nb_place,cat.duree,cat.fk_product,p.ref FROM ".MAIN_DB_PREFIX."agefodd_formation_catalogue cat";
				$sql.=" LEFT JOIN ".MAIN_DB_PREFIX."product p on (p.rowid = cat.fk_product)";
				$sql.=" WHERE cat.rowid=".$fk_training;
				$resql = $db->query($sql);
				if(!empty($resql)){
					$obj = $db->fetch_object($resql);
					$data['duree']=$obj->duree;
					$data['nb_place']=$obj->nb_place;
					$data['fk_product']=$obj->fk_product;
					$data['ref_product']=$obj->ref;
					$data['result'] = 1;
				}
			}

		}

	}

echo json_encode($data);

