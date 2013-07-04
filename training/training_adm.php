<?php
/* 
* Copyright (C) 2012       Florian Henry  	<florian.henry@open-concept.pro>
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 3 of the License, or
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
 * 	\file       /agefodd/training/training_adm.php
 *	\ingroup    agefodd
 *	\brief      agefood agefodd admin training task by trainig
*/

$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/agefodd/class/agefodd_session_admlevel.class.php');
dol_include_once('/agefodd/class/html.formagefodd.class.php');
dol_include_once('/agefodd/lib/agefodd.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$langs->load("admin");
$langs->load('agefodd@agefodd');

// Security check
if (!$user->rights->agefodd->lire) accessforbidden();

$action = GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$id=GETPOST('id','int');


if ($action == 'sessionlevel_create')
{
	$agf = new Agefoddtrainingadmlevel($db);

	$parent_level = GETPOST('parent_level','int');

	if (!empty($parent_level))
	{
		$agf->fk_parent_level = $parent_level;

		$agf_static = new Agefoddtrainingadmlevel($db);
		$result_stat = $agf_static->fetch($agf->fk_parent_level);

		if ($result_stat > 0)
		{
			if (!empty($agf_static->id))
			{
				$agf->level_rank = $agf_static->level_rank + 1;
				$agf->indice = ebi_get_adm_get_next_indice_action($agf_static->id);
			}
			else
			{	//no parent : This case may not occur but we never know
				$agf->indice = (ebi_get_adm_level_number() + 1) . '00';
				$agf->level_rank = 0;
			}
		}
		else
		{
			dol_syslog("Agefodd::agefodd error=".$result_stat->error, LOG_ERR);
			$mesg = '<div class="error">'.$result_stat->error.'</div>';
		}
	}
	else
	{
		//no parent
		$agf->fk_parent_level = 0;
		$agf->indice = (ebi_get_adm_level_number() + 1) . '00';
		$agf->level_rank = 0;
	}

	$agf->intitule = GETPOST('intitule','alpha');
	$agf->delais_alerte = GETPOST('delai','int');

	if ($agf->level_rank>3)
	{
		$mesg = '<div class="error">'.$langs->trans("AgfAdminNoMoreThan3Level").'</div>';
	}
	else
	{
		$result = $agf->create($user);

		if ($result1!=1)
		{
			dol_syslog("Agefodd::agefodd error=".$agf->error, LOG_ERR);
			$mesg = '<div class="error">'.$agf->error.'</div>';
		}
	}


}

if ($action == 'sessionlevel_update')
{
	$agf = new Agefoddtrainingadmlevel($db);

	$id = GETPOST('id','int');
	$parent_level = GETPOST('parent_level','int');

	$result = $agf->fetch($id);

	if ($result > 0)
	{

		//Up level of action
		if (GETPOST('sesslevel_up_x'))
		{
			$result2 = $agf->shift_indice($user,'less');
			if ($result1!=1)
			{
				dol_syslog("Agefodd::agefodd error=".$agf->error, LOG_ERR);
				$mesg = '<div class="error">'.$agf->error.'</div>';
			}
		}

		//Down level of action
		if (GETPOST('sesslevel_down_x'))
		{
			$result1 = $agf->shift_indice($user,'more');
			if ($result1!=1)
			{
				dol_syslog("Agefodd::agefodd error=".$agf->error, LOG_ERR);
				$mesg = '<div class="error">'.$agf->error.'</div>';
			}
		}

		//Update action
		if (GETPOST('sesslevel_update_x'))
		{
			$agf->intitule = GETPOST('intitule','alpha');
			$agf->delais_alerte = GETPOST('delai','int');

			if (!empty($parent_level))
			{
				if ($parent_level!=$agf->fk_parent_level)
				{
					$agf->fk_parent_level = $parent_level;

					$agf_static = new Agefodd_session_admlevel($db);
					$result_stat = $agf_static->fetch($agf->fk_parent_level);

					if ($result_stat > 0)
					{
						if (!empty($agf_static->id))
						{
							$agf->level_rank = $agf_static->level_rank + 1;
							$agf->indice = ebi_get_adm_get_next_indice_action($agf_static->id);
						}
						else
						{	//no parent : This case may not occur but we never know
							$agf->indice = (ebi_get_adm_level_number() + 1) . '00';
							$agf->level_rank = 0;
						}
					}
					else
					{
						dol_syslog("Agefodd::agefodd error=".$result_stat->error, LOG_ERR);
						$mesg = '<div class="error">'.$result_stat->error.'</div>';
					}
				}
			}
			else
			{
				//no parent
				$agf->fk_parent_level = 0;
				$agf->indice = (ebi_get_adm_level_number() + 1) . '00';
				$agf->level_rank = 0;
			}

			if ($agf->level_rank>3)
			{
				$mesg = '<div class="error">'.$langs->trans("AgfAdminNoMoreThan3Level").'</div>';
			}
			else
			{
				$result1 = $agf->update($user);
				if ($result1!=1)
				{
					dol_syslog("Agefodd::agefodd error=".$agf->error, LOG_ERR);
					$mesg = '<div class="error">'.$agf->error.'</div>';
				}
			}
		}

		//Delete action
		if (GETPOST('sesslevel_remove_x'))
		{

			$result = $agf->delete($user);
			if ($result!=1)
			{
				dol_syslog("Agefodd::agefodd error=".$agf->error, LOG_ERR);
				$mesg = '<div class="error">'.$agf->error.'</div>';
			}
		}
	}
	else
	{
		$mesg = '<div class="error">This action do not exists</div>';
	}
}