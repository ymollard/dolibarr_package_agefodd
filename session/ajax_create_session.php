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
$data['msg'] = $langs->trans('JobDo'); // default message for errors



	// do action from GETPOST ...
	if(GETPOST('action'))
	{
		$action = GETPOST('action');
		
		
		if($action == 'change_intitule')
		{
			$intitule= GETPOST('intitule');
			if(!empty($intitule)){
				$sql="SELECT duree,fk_product FROM ".MAIN_DB_PREFIX."agefodd_formation_catalogue WHERE intitule='".$intitule."'";
				$resql = $db->query($sql);
				if(!empty($resql)){
					$obj = $db->fetch_object($resql);
					$data['duree']=$obj->duree;
					$data['fk_product']=$obj->fk_product;
					$data['result'] = 1; 
				}
			}
			
		}
		
	}

echo json_encode($data);

