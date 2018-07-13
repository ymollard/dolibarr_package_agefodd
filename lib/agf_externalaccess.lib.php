<?php
/*
 * Copyright (C) 2018		Pierre-Henry Favre	<phf@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

function getMenuAgefoddExternalAccess()
{
	global $langs;

	$context = Context::getInstance();
	$html = '<section id="agefodd">
				<div class="container">
				  <div class="row">
					<div class="col-lg-12 text-center">
					  <h2 class="section-heading" id="agefodd-title">'.$langs->trans('AgfTraining').'</h2>
					  <hr class="my-4">
					</div>
				  </div>
				</div> 
				<div class="container">
				  <div class="row">';

	$link = $context->getRootUrl('agefodd_session_list');
	$html.= getService($langs->trans('AgfMenuSess'),'fa-hourglass',$link);
	// TODO faire les getService() pour avoir accés à d'autres objets d'agefodd (pour plus tard)

	$html.= '</div>
			</div>
		  </section>';

	return $html;
}

function getPageViewSessionListExternalAccess()
{
	global $langs,$db,$user;

	$context = Context::getInstance();

	$formateur = new Agefodd_teacher($db);
	$agsession = new Agsession($db);

	$formateur->fetchByUser($user);
	if (!empty($formateur->id))
	{
		$agsession->fetch_session_per_trainer($formateur->id);
	}

	$out = '';
	$out.= '<section id="section-session-list"><div class="container">';

	if(!empty($agsession->lines))
	{
		$out.= '<table id="session-list" class="table table-striped w-100" >';

		$out.= '<thead>';

		$out.= '<tr>';
		$out.= ' <th class="" >'.$langs->trans('Ref').'</th>';
		$out.= ' <th class="" >'.$langs->trans('AgfFormIntitule').'</th>';
		$out.= ' <th class="" >'.$langs->trans('DateStart').'</th>';
		$out.= ' <th class="" >'.$langs->trans('DateEnd').'</th>';
		$out.= ' <th class="text-center" >'.$langs->trans('AgfDuree').'</th>';
		$out.= ' <th class="text-center" >'.$langs->trans('Status').'</th>';
		$out.= ' <th class="text-center" ></th>';
		$out.= '</tr>';

		$out.= '</thead>';

		$out.= '<tbody>';
		foreach ($agsession->lines as &$item)
		{
			$out.= '<tr>';
			// TODO replace $item->id by $item->ref when merge master
			$out.= ' <td data-search="'.$item->rowid.'" data-session="'.$item->rowid.'"  ><a href="'.$context->getRootUrl('agefodd_session_card', '&sessid='.$item->rowid).'">'.$item->rowid.'</a></td>';
			$out.= ' <td data-search="'.$item->intitule.'" data-session="'.$item->intitule.'"  >'.$item->intitule.'</td>';
			$out.= ' <td data-search="'.dol_print_date($item->dated).'" data-session="'.$item->dated.'" >'.dol_print_date($item->dated).'</td>';
			$out.= ' <td data-search="'.dol_print_date($item->datef).'" data-session="'.$item->datef.'" >'.dol_print_date($item->datef).'</td>';
			$out.= ' <td class="text-center" data-search="'.$item->duree_session.'" data-session="'.$item->duree_session.'"  >'.$item->duree_session.'</td>';
			$statut = Agsession::getStaticLibStatut($item->status, 0);
			$out.= ' <td class="text-center" data-search="'.$statut.'" data-session="'.$statut.'" >'.$statut.'</td>';

			$out.= ' <td class="text-right" >&nbsp;</td>';

			$out.= '</tr>';
		}
		$out.= '</tbody>';

		$out.= '</table>';

		$out.='	<script type="text/javascript" >
					$(document).ready(function(){
						$("#session-list").DataTable({
							"language": {
								"url": "'.$context->getRootUrl().'vendor/data-tables/french.json"
							},
							responsive: true,
							columnDefs: [{
								orderable: false,
								"aTargets": [-1]
							}, {
								"bSearchable": false,
								"aTargets": [-1, -2]
							}]
						});
					});
			   </script>';
	}
	else {
		$out.= '<div class="info clearboth text-center" >';
		$out.=  $langs->trans('EACCESS_Nothing');
		$out.= '</div>';
	}

	$out.= '</div></section>';

	return $out;
}

function getPageViewSessionCardExternalAccess()
{
	return 'TOTO';
}