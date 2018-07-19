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

		$out.= '<script type="text/javascript" >
					$(document).ready(function(){
						$("#session-list").DataTable({
							"language": {
								"url": "'.$context->getRootUrl().'vendor/data-tables/french.json"
							},
							responsive: true,
							order: [[ 2, "desc" ]],
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

function getPageViewSessionCardExternalAccess(&$agsession, &$trainer)
{
	global $db,$langs;
	
	$context = Context::getInstance();
	
	$agf_calendrier_formateur = new Agefoddsessionformateurcalendrier($db);
	$agf_calendrier_formateur->fetch_all_by_trainer($trainer->id);
	
	$out = '';
	$out.= '<section id="section-session-card"><div class="container">';
	
	
	$out.= '
		<ul class="nav nav-tabs mb-3" id="section-session-card-calendrier-formateur-tab" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" id="calendrier-info-tab" data-toggle="tab" href="#nav-calendrier-info" role="tab" aria-controls="calendrier-info" aria-selected="true">Créneaux</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="calendrier-summary-tab" data-toggle="tab" href="#nav-calendrier-summary" role="tab" aria-controls="calendrier-summary" aria-selected="false">Récapitulatif</a>
			</li>
		</ul>
	';
	
	$out.= '
		<div class="tab-content" id="section-session-card-calendrier-formateur-tab-tabContent">
			<div class="tab-pane fade show active" id="nav-calendrier-info" role="tabpanel" aria-labelledby="nav-calendrier-info-tab">'.getPageViewSessionCardExternalAccess_crenaux($agsession, $trainer, $agf_calendrier_formateur).'</div>
			<div class="tab-pane fade" id="nav-calendrier-summary" role="tabpanel" aria-labelledby="nav-calendrier-summary-tab">'.getPageViewSessionCardExternalAccess_summary($agsession, $trainer, $agf_calendrier_formateur).'</div>
		</div>
	';
	
	
	
	$out.= '</div></section>';

	
	
	return $out;
}

function getPageViewSessionCardExternalAccess_crenaux(&$agsession, &$trainer, &$agf_calendrier_formateur)
{
	global $langs;
	
	$context = Context::getInstance();
	
	$out = '';
	$out.= '<table id="session-list" class="table table-striped w-100" >';

	$out.= '<thead>';

	$out.= '<tr>';
	$out.= ' <th class="text-center" ></th>';
	$out.= ' <th class="" >'.$langs->trans('AgfDateSession').'</th>';
	$out.= ' <th class="" >'.$langs->trans('AgfPeriodTimeB').'</th>';
	$out.= ' <th class="" >'.$langs->trans('AgfPeriodTimeE').'</th>';
	$out.= ' <th class="text-center" >'.$langs->trans('AgfDuree').'</th>';
	$out.= ' <th class="text-center" >'.$langs->trans('Status').'</th>';
	$out.= ' <th class="text-center" ></th>';
	$out.= '</tr>';

	$out.= '<tbody>';
	foreach ($agf_calendrier_formateur->lines as &$item)
	{
		$out.= '<tr>';
		// TODO replace $item->id by $item->ref when merge master
		$out.= ' <td class="text-center">'.$item->id.'</td>';
		$date_session = dol_print_date($item->date_session);
		$out.= ' <td data-search="'.$date_session.'" data-calendrierf="'.$item->date_session.'"  >'.$date_session.'</td>';
		
		$out.= ' <td data-calendrierf="'.$item->heured.'" >'.dol_print_date($item->heured, '%H:%M').'</td>';
		$out.= ' <td data-calendrierf="'.$item->heuref.'" >'.dol_print_date($item->heuref, '%H:%M').'</td>';
		$duree = ($item->heuref - $item->heured) / 60 / 60;
		$out.= ' <td class="text-center" data-calendrierf="'.$duree.'"  >'.$duree.'</td>';
		$statut = Agefoddsessionformateurcalendrier::getStaticLibStatut($item->status, 0);
		$out.= ' <td class="text-center" data-calendrierf="'.$statut.'" >'.$statut.'</td>';

		$edit = '<a href="'.$context->getRootUrl('agefodd_session_card_time_slot', '&sessid='.$agsession->id.'&slotid='.$item->id).'"><i class="fa fa-edit"></a></i>';
		$delete = '<i class="fa fa-trash" data-id="'.$item->id.'" data-toggle="modal" data-target="#session-card-delete-time-slot" onclick="$(\'#session-card-delete-time-slot\').find(\'[name=fk_agefodd_session_formateur_calendrier]\').val(this.dataset.id)"></i>';
		$out.= ' <td class="text-center" >'.$edit.' '.$delete.'</td>';

		$out.= '</tr>';
		
//		var_dump($item);break;
	}
	
	$out.= '</tbody>';
	$out.= '</table>';
	
	$body = $langs->trans('Agf_EA_DeleteClandrierFormateurBody');
	$body.= '<input type="hidden" name="sessid" value="'.$agsession->id.'" />';
	$body.= '<input type="hidden" name="fk_agefodd_session_formateur_calendrier" value="" />';
	$out.= getModalConfirm('session-card-delete-time-slot', $langs->trans('Agf_EA_DeleteClandrierFormateurTitle'), $body, $context->getRootUrl('agefodd_session_card', '&sessid='.$agsession->id), 'deleteCalendrierFormateur');
	
	$out.= '<script type="text/javascript" >
				$(document).ready(function(){
					$("#session-list").DataTable({
						"language": {
							"url": "'.$context->getRootUrl().'vendor/data-tables/french.json"
						},
						responsive: true,
						order: [[ 1, "desc" ]],
						columnDefs: [{
							orderable: false,
							"aTargets": [-1,0]
						}, {
							"bSearchable": false,
							"aTargets": [-1, 0]
						}]
					});
				});
		   </script>';
	
	return $out;
}

function getPageViewSessionCardExternalAccess_summary(&$agsession, &$trainer, &$agf_calendrier_formateur)
{
	$out = '';
	
	
	
	return $out;
}





function getPageViewSessionCardCalendrierFormateurExternalAccess($agsession, $trainer, $agf_calendrier_formateur)
{
	global $db,$langs;
	
	$context = Context::getInstance();
	
	$out = '';
	$out.= '<section id="section-session-card-calendrier-formateur" class="py-5"><div class="container">';
//	var_dump($agf_calendrier_formateur);
	// TODO formulaire de saisie d'heures de présence, confirmation du créneau
	
	if (!empty($agf_calendrier_formateur->id)) $action = 'update';
	else $action = 'add';
	
	$out.= '
		<form action="'.$_SERVER['PHP_SELF'].'" method="POST" class="clearfix">
			<input type="hidden" name="action" value="'.$action.'" />
			<input type="hidden" name="sessid" value="'.$agsession->id.'" />
			<input type="hidden" name="trainerid" value="'.$trainer->id.'" />
			<input type="hidden" name="slotid" value="'.$agf_calendrier_formateur->id.'" />
			<input type="hidden" name="controller" value="'.$context->controller.'" />
				
			<h4>Créneau</h4>
			<div class="form-group">
				<label for="heured">Date</label>
				<input type="date" class="form-control" id="date_session" name="date_session" value="'.date('Y-m-d', $agf_calendrier_formateur->date_session).'">
			</div>
			<div class="form-group">
				<label for="heured">Heure début:</label>
				<input type="time" class="form-control" id="heured" name="heured" value="'.date('H:i', $agf_calendrier_formateur->heured).'">
				<label for="heuref">Heure fin:</label>
				<input type="time" class="form-control" id="heuref" name="heuref" value="'.date('H:i', $agf_calendrier_formateur->heuref).'">
			</div>
			<div class="form-group form-check">
				<label class="form-check-label">
					<input class="form-check-input" type="checkbox"> Remember me
				</label>
			</div>';
	
	$stagiaires = new Agefodd_session_stagiaire($db);
	$stagiaires->fetch_stagiaire_per_session($agsession->id);
	if (!empty($stagiaires->lines))
	{
		$TCalendrier = _getCalendrierFromCalendrierFormateur($agf_calendrier_formateur);
		$agfssh = new Agefoddsessionstagiaireheures($db);
		$result = 0;
		
		$out.= '<h4>Déclarer des heures de présence par participant</h4>';
		foreach ($stagiaires->lines as &$stagiaire)
		{
			if ($stagiaire->id <= 0)	continue;
			
			$secondes = 0;
			if (!empty($TCalendrier)) 
			{
				$result = $agfssh->fetch_by_session($agsession->id, $stagiaire->id, $TCalendrier[0]->id);
				$secondes = $agfssh->heures * 60 * 60;
			}
			
			$out.= '
				<div class="form-group">
					<label for="stagiaire_'.$stagiaire->id.'">'.strtoupper($stagiaire->nom) . ' ' . ucfirst($stagiaire->prenom).'</label>
					<input type="time" class="form-control" id="stagiaire_'.$stagiaire->id.'" name="hours['.$stagiaire->id.']" value="'.(!empty($secondes) ? convertSecondToTime($secondes) : '').'" />
				</div>';
		}
	}
	
	$out.= '<input type="submit" class="btn btn-primary pull-right" value="Submit" />
		</form>';
	
	$out.= '</div></section>';
	
	return $out;
}
