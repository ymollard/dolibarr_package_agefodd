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

/**
 * Ajout les icône dans l'écran "services"
 * 
 * @return string
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

/**
 * Affiche la liste des sessions du formateur courant agefodd
 * 
 * @return string
 */
function getPageViewSessionListExternalAccess()
{
	global $langs,$db,$user, $conf;

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
		$out.= ' <th class="" >'.$langs->trans('AgfParticipant').'</th>';
		$out.= ' <th class="" >'.$langs->trans('DateStart').'</th>';
		$out.= ' <th class="" >'.$langs->trans('DateEnd').'</th>';
		$out.= ' <th class="text-center" >'.$langs->trans('AgfDuree').'</th>';
		$out.= ' <th class="text-center" >'.$langs->trans('AgfDureeDeclared').'</th>';
		$out.= ' <th class="text-center" >'.$langs->trans('AgfDureeSolde').'</th>';
		$out.= ' <th class="text-center" >'.$langs->trans('Status').'</th>';
		$out.= ' <th class="text-center" ></th>';
		$out.= '</tr>';

		$out.= '</thead>';

		$out.= '<tbody>';
		
		/** @var AgfSessionLine $item */
		foreach ($agsession->lines as &$item)
		{
			$stagiaires = new Agefodd_session_stagiaire($db);
			$stagiaires->fetch_stagiaire_per_session($item->rowid);

			$stagiaires_str = '';
			if (!empty($stagiaires->lines))
			{
				foreach ($stagiaires->lines as &$stagiaire)
				{
					if (empty($stagiaire->nom) && empty($stagiaire->prenom)) continue;
					$stagiaires_str.= implode(' ', array( $stagiaire->civilite, strtoupper($stagiaire->nom), ucfirst($stagiaire->prenom) ))."<br />";
				}

			}
			
			$out.= '<tr>';
			$out.= ' <td data-order="'.$item->sessionref.'" data-search="'.$item->sessionref.'"  ><a href="'.$context->getRootUrl('agefodd_session_card', '&sessid='.$item->rowid).'">'.$item->sessionref.'</a></td>';
			$out.= ' <td data-order="'.$item->intitule.'" data-search="'.$item->intitule.'"  >'.$item->intitule.'</td>';
			$out.= ' <td data-search="'.$stagiaires_str.'"  >'.$stagiaires_str.'</td>';
			$out.= ' <td data-order="'.$item->dated.'" data-search="'.dol_print_date($item->dated, '%d/%m/%Y').'" >'.dol_print_date($item->dated, '%d/%m/%Y').'</td>';
			$out.= ' <td data-order="'.$item->datef.'" data-search="'.dol_print_date($item->datef, '%d/%m/%Y').'" >'.dol_print_date($item->datef, '%d/%m/%Y').'</td>';
			$out.= ' <td class="text-center" data-order="'.$item->duree_session.'" data-session="'.$item->duree_session.'"  >'.$item->duree_session.'</td>';
			$duree_declared = Agsession::getStaticSumDureePresence($item->rowid);
			if (!empty($duree_declared) && !empty($conf->global->AGF_EA_ECLATE_HEURES_PAR_TYPE))
			{
			    $duree_exploded = Agsession::getStaticSumExplodeDureePresence($item->rowid);
			    
			    if (count($duree_exploded))
			    {
			        $plus = ' <i class="fa fa-plus hours-detail"></i>';
			        
			        
			        $popcontent = '';
			        foreach ($duree_exploded as $label => $hours)
			        {
			            $popcontent.= dol_escape_htmltag('<br>'.$label.' : '.$hours, 1);
			        }
			        $plus = ' <span data-toggle="popover" title="Détail des heures" data-content="'.$popcontent.'"><i class="fa fa-plus hours-detail"></i></span>';
			        $plus.= '<span style="display:none;">';
			        
			        $plus.='</span>';
			    }
			}
			else
			{
			    $plus = '';
			}
			$out.= ' <td class="text-center" data-order="'.$duree_declared.'">'.$duree_declared.$plus.'</td>';
			$solde = $item->duree_session - $duree_declared;
			$out.= ' <td class="text-center" data-order="'.$solde.'">'.$solde.'</td>';
			$statut = Agsession::getStaticLibStatut($item->status, 0);
			$out.= ' <td class="text-center" data-search="'.$statut.'" data-order="'.$statut.'" >'.$statut.'</td>';

			$out.= ' <td class="text-right" >&nbsp;</td>';

			$out.= '</tr>';
		}
		$out.= '</tbody>';

		$out.= '</table>';

		$out.= '<script type="text/javascript" >
					$(document).ready(function(){
						$("#session-list").DataTable({
							stateSave: '.(GETPOST('save_lastsearch_values') ? 'true' : 'false').',
							"language": {
								"url": "'.$context->getRootUrl().'vendor/data-tables/french.json"
							},
							responsive: true,
							order: [[ 3, "desc" ]],
							columnDefs: [{
								orderable: false,
								"aTargets": [-1, 2]
							}, {
								"bSearchable": false,
								"aTargets": [-1]
							}]
						});
                        /* on réaffecte event js on mouseenter pour hacker datatable */
                        $(document).on("mouseenter", \'[data-toggle="popover"]\', function(e){$(this).popover({html : true});});
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

/**
 * Génère les écrans liés à une session
 * 
 * @param Agsession $agsession
 * @param Teacher $trainer
 * @return string
 */
function getPageViewSessionCardExternalAccess(&$agsession, &$trainer)
{
	global $db,$langs;
	
	$context = Context::getInstance();
	$tab = GETPOST('tab');
	$agf_calendrier_formateur = new Agefoddsessionformateurcalendrier($db);
	$agf_calendrier_formateur->fetchAllBy(array('trainer.rowid'=>$trainer->id, 'sf.fk_session'=>$agsession->id), '');
	
	$out = '';
	$out.= '<section id="section-session-card" class="py-5"><div class="container">';
	
	$out.= getEaNavbar($context->getRootUrl('agefodd_session_list', '&save_lastsearch_values=1'), $context->getRootUrl('agefodd_session_card_time_slot', '&sessid='.$agsession->id.'&slotid=0'));
	
	$out.= '
		<blockquote class="blockquote">
			<p>'.$agsession->ref.'</p>
		</blockquote>
		<ul class="nav nav-tabs mb-3" id="section-session-card-calendrier-formateur-tab" role="tablist">
			<li class="nav-item">
				<a class="nav-link'.((empty($tab) || $tab == 'calendrier-info-tab') ? ' active' : '').'" id="calendrier-info-tab" data-toggle="tab" href="#nav-calendrier-info" role="tab" aria-controls="calendrier-info" aria-selected="'.((empty($tab) || $tab == 'calendrier-info-tab') ? 'true' : 'false').'">Créneaux</a>
			</li>
			<li class="nav-item">
				<a class="nav-link'.(($tab == 'calendrier-summary-tab') ? ' active' : '').'" id="calendrier-summary-tab" data-toggle="tab" href="#nav-calendrier-summary" role="tab" aria-controls="calendrier-summary" aria-selected="'.(($tab == 'calendrier-summary-tab') ? 'true' : 'false').'">Récapitulatif</a>
			</li>
            <li class="nav-item">
				<a class="nav-link'.(($tab == 'session-files-tab') ? ' active' : '').'" id="session-files-tab" data-toggle="tab" href="#nav-session-files" role="tab" aria-controls="session-files" aria-selected="'.(($tab == 'session-files-tab') ? 'true' : 'false').'">Fichiers joints</a>
			</li>
		</ul>
	';
	
	$out.= '
		<div class="tab-content" id="section-session-card-calendrier-formateur-tab-tabContent">
			<div class="tab-pane fade'.((empty($tab) || $tab == 'calendrier-info-tab') ? ' show active' : '').'" id="nav-calendrier-info" role="tabpanel" aria-labelledby="nav-calendrier-info-tab">'.getPageViewSessionCardExternalAccess_creneaux($agsession, $trainer, $agf_calendrier_formateur).'</div>
			<div class="tab-pane fade'.(($tab == 'calendrier-summary-tab') ? ' show active' : '').'" id="nav-calendrier-summary" role="tabpanel" aria-labelledby="nav-calendrier-summary-tab">'.getPageViewSessionCardExternalAccess_summary($agsession, $trainer, $agf_calendrier_formateur).'</div>
            <div class="tab-pane fade'.(($tab == 'session-files-tab') ? ' show active' : '').'" id="nav-session-files" role="tabpanel" aria-labelledby="nav-session-files-tab">'.getPageViewSessionCardExternalAccess_files($agsession, $trainer).'</div>
		</div>
	';
	
	
	
	$out.= '</div></section>';

	
	
	return $out;
}

/**
 * Affiche les créneaux du calendrier formateur
 * 
 * @param Agsession $agsession
 * @param Teacher $trainer
 * @param Teacher_calendar $agf_calendrier_formateur
 * @return string
 */
function getPageViewSessionCardExternalAccess_creneaux(&$agsession, &$trainer, &$agf_calendrier_formateur)
{
	global $langs;
	
	$context = Context::getInstance();
	
	$out = '';
	$out.= '<table id="session-list" class="table table-striped w-100" >';

	$out.= '<thead>';

	$out.= '<tr>';
	$out.= ' <th class="text-center" >'.$langs->trans('ID').'</th>';
	$out.= ' <th class="" >'.$langs->trans('AgfDateSession').'</th>';
	$out.= ' <th class="" >'.$langs->trans('AgfPeriodTimeB').'</th>';
	$out.= ' <th class="" >'.$langs->trans('AgfPeriodTimeE').'</th>';
	$out.= ' <th class="text-center" >'.$langs->trans('AgfDuree').'</th>';
	$out.= ' <th class="text-center" >'.$langs->trans('Status').'</th>';
	$out.= ' <th class="text-center" >'.$langs->trans('Type').'</th>';
	$out.= ' <th class="text-center" ></th>';
	$out.= '</tr>';

	$out.= '<tbody>';
	foreach ($agf_calendrier_formateur->lines as &$item)
	{
		$TCalendrier = _getCalendrierFromCalendrierFormateur($item, true, true);
		if (is_string($TCalendrier)) 
		{
			$context->setError($langs->trans('Agf_EA_error_sql'));
			$TCalendrier = array();
		}
		if (!empty($TCalendrier)) $agf_calendrier = $TCalendrier[0];
		else $agf_calendrier = null;
		
		$url = $context->getRootUrl('agefodd_session_card_time_slot', '&sessid='.$agsession->id.'&slotid='.$item->id);
		
		$out.= '<tr>';
		// TODO replace $item->id by $item->ref when merge master
		$out.= ' <td class="text-center"><a href="'.$url.'&action=view">'.$item->id.'</a></td>';
		$date_session = dol_print_date($item->date_session, '%d/%m/%Y');
		$out.= ' <td data-order="'.$item->date_session.'" data-search="'.$date_session.'" >'.$date_session.'</td>';
		
		$heured = dol_print_date($item->heured, '%H:%M');
		$out.= ' <td data-order="'.$heured.'" data-search="'.$heured.'" >'.$heured.'</td>';
		$heuref = dol_print_date($item->heuref, '%H:%M');
		$out.= ' <td data-order="'.$heuref.'" data-search="'.$heuref.'" >'.$heuref.'</td>';
		$duree = ($item->heuref - $item->heured) / 60 / 60;
		$out.= ' <td class="text-center" data-order="'.$duree.'" data-search="'.$duree.'" >'.$duree.'</td>';
		if ($item->status == Agefoddsessionformateurcalendrier::STATUS_DRAFT) $statut = $langs->trans('AgfStatusCalendar_previsionnel');
		else $statut = Agefoddsessionformateurcalendrier::getStaticLibStatut($item->status, 0);
		$out.= ' <td class="text-center" data-order="'.$statut.'" data-search="'.$statut.'" >'.$statut.'</td>';
		
		$calendrier_type_label = !empty($agf_calendrier) ? $agf_calendrier->calendrier_type_label : '';
		$out.= ' <td class="text-center" data-order="'.$calendrier_type_label.'" data-search="'.$calendrier_type_label.'" >'.$calendrier_type_label.'</td>';

		//$edit = '<a href="'.$url.'"><i class="fa fa-edit"></a></i>';
		$delete = '<i class="fa fa-trash" ></i>  Supprimer';
		$out.= ' <td class="text-center" >';
		
		$out.= '<div class="btn-group" role="group" aria-label="Button group with nested dropdown">
		<a  class="btn btn-xs btn-secondary" href="'.$url.'"><i class="fa fa-edit"></i></a>
		
		<div class="btn-group" role="group">
		<button id="btnGroupDrop1" type="button" class="btn btn-xs btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
		<div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
        <a  class="dropdown-item" href="'.$url.'"><i class="fa fa-edit"> Editer</i></a>';
		
		if (empty($agf_calendrier) || empty($agf_calendrier->billed))
		  $out.= '<button type="button" class="dropdown-item" data-id="'.$item->id.'" data-toggle="modal" data-target="#session-card-delete-time-slot" onclick="$(\'#session-card-delete-time-slot\').find(\'[name=fk_agefodd_session_formateur_calendrier]\').val(this.dataset.id)" >'.$delete.' </button>';
		
		$out.= '
		</div>
		</div>
		</div>
		</td>';

		$out.= '</tr>';
		
		
//		var_dump($item);break;
	}
	
	$out.= '</tbody>';
	$out.= '</table>';
	
	$body = $langs->trans('Agf_EA_DeleteClandrierFormateurBody');
	$body.= '<input type="hidden" name="sessid" value="'.$agsession->id.'" />';
	$body.= '<input type="hidden" name="fk_agefodd_session_formateur_calendrier" value="" />';
	$out.= getEaModalConfirm('session-card-delete-time-slot', $langs->trans('Agf_EA_DeleteClandrierFormateurTitle'), $body, $context->getRootUrl('agefodd_session_card', '&sessid='.$agsession->id), 'deleteCalendrierFormateur');
	
	$out.= '<script type="text/javascript" >
				$(document).ready(function(){
					$("#session-list").DataTable({
						stateSave: '.(GETPOST('save_lastsearch_values') ? 'true' : 'false').',
						"language": {
							"url": "'.$context->getRootUrl().'vendor/data-tables/french.json"
						},
						responsive: true,
						order: [[ 1, "desc" ]],
						columnDefs: [{
							orderable: false,
							"aTargets": [-1]
						}, {
							"bSearchable": false,
							"aTargets": [-1]
						}]
					});
				});
		   </script>';
	
	return $out;
}

function getPageViewSessionCardExternalAccess_summary(&$agsession, &$trainer, &$agf_calendrier_formateur)
{
	global $langs,$db;
	
	$agefodd_sesscalendar = new Agefodd_sesscalendar($db);
	$agefodd_sesscalendar->fetch_all($agsession->id);
	$stagiaires = new Agefodd_session_stagiaire($db);
	$stagiaires->fetch_stagiaire_per_session($agsession->id);
	
	$date_deb = dol_print_date($agsession->dated, 'daytext');
	$date_fin = dol_print_date($agsession->datef, 'daytext');
	$duree_scheduled = 0;
	$duree_presence_max_comptabilise = 0;
	$duree_presence_comptabilise = 0;
	$duree_presence_comptabilise_cancel = 0;
	$duree_presence_draft = 0;

	foreach ($agf_calendrier_formateur->lines as &$line)
	{
		$duree_scheduled += ($line->heuref - $line->heured) / 60 / 60;
	}
	
	foreach ($agefodd_sesscalendar->lines as &$agf_calendrier)
	{
		/** @var Agefodd_sesscalendar $agf_calendrier */
		list($duree_declared, $duree_max) = $agf_calendrier->getSumDureePresence();
		if ($agf_calendrier->status == Agefodd_sesscalendar::STATUS_CONFIRMED)
		{
			$duree_presence_comptabilise += $duree_declared;
			$duree_presence_max_comptabilise += $duree_max;
		}
		else if ($agf_calendrier->status == Agefodd_sesscalendar::STATUS_CANCELED)
		{
			$duree_presence_comptabilise_cancel += $duree_declared;
			$duree_presence_max_comptabilise += $duree_max;
		}
		else $duree_presence_draft += $duree_declared;
	}

	$total_duree_comptabilise = $duree_presence_comptabilise+$duree_presence_comptabilise_cancel;
	if ($total_duree_comptabilise > 0) $tx_assi = $duree_presence_comptabilise * 100 / $duree_presence_max_comptabilise;
	else $tx_assi = 0;
	
	$out = '';

	$out.= '
		<div class="container px-0">
			<h5>'.$langs->trans('AgfSessionSummary', $date_deb, $date_fin).'</h5>
			<div class="panel panel-default">
				<div class="panel-body">
					<div class="row clearfix">
						<div class="col-md-6">
							<div class="form-group">
								<label class="col-md-7 px-0" for="AgfSessionSummaryTotalHours">'.$langs->trans('AgfSessionSummaryTotalHours').'</label>
								<span class="col-md-5 px-0" id="AgfSessionSummaryTotalHours">'.$langs->trans('AgfHours', price($agsession->duree_session, 0, '', 1, -1, 2)).'</span>
							</div>
							<div class="form-group">
								<label class="col-md-7 px-0"  for="AgfSessionSummaryTotalScheduled">'.$langs->trans('AgfSessionSummaryTotalScheduled').'</label>
								<span class="col-md-5 px-0" id="AgfSessionSummaryTotalScheduled">'.$langs->trans('AgfHours', price($duree_scheduled, 0, '', 1, -1, 2)).'</span>
							</div>
							<div class="form-group">
								<label class="col-md-7 px-0"  for="AgfSessionSummaryTotalLeft">'.$langs->trans('AgfSessionSummaryTotalLeft').'</label>
								<span class="col-md-5 px-0" id="AgfSessionSummaryTotalLeft">'.$langs->trans('AgfHours', price($agsession->duree_session-$duree_scheduled, 0, '', 1, -1, 2)).'</span>
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label class="col-md-7 px-0"  for="AgfSessionSummaryTotalPresence">'.$langs->trans('AgfSessionSummaryTotalPresence').'</label>
								<span class="col-md-5 px-0" id="AgfSessionSummaryTotalPresence">'.$langs->trans('AgfHours', price($duree_presence_draft+$duree_presence_comptabilise+$duree_presence_comptabilise_cancel, 0, '', 1, -1, 2)).'</span>
							</div>
							<div class="form-group">
								<label class="col-md-7 px-0"  for="AgfSessionSummaryTotalHoursComptabilise">'.$langs->trans('AgfSessionSummaryTotalHoursComptabilise').'</label>
								<span class="col-md-5 px-0" id="AgfSessionSummaryTotalHoursComptabilise">'.$langs->trans('AgfHours', price($duree_presence_comptabilise, 0, '', 1, -1, 2)).'</span>
							</div>
							<div class="form-group">
								<label class="col-md-7 px-0"  for="AgfSessionSummaryTotalHoursComptabiliseCancel">'.$langs->trans('AgfSessionSummaryTotalHoursComptabiliseCancel').'</label>
								<span class="col-md-5 px-0" id="AgfSessionSummaryTotalHoursComptabiliseCancel">'.$langs->trans('AgfHours', price($duree_presence_comptabilise_cancel, 0, '', 1, -1, 2)).'</span>
							</div>
							<div class="form-group">
								<label class="col-md-7 px-0"  for="AgfSessionSummaryTotalHoursMaxComptabilise">'.$langs->trans('AgfSessionSummaryTotalHoursMaxComptabilise').'</label>
								<span class="col-md-5 px-0" id="AgfSessionSummaryTotalHoursMaxComptabilise">'.$langs->trans('AgfHours', price($duree_presence_max_comptabilise, 0, '', 1, -1, 2)).'</span>
							</div>
							<div class="form-group">
								<label class="col-md-7 px-0"  for="AgfSessionSummaryTauxAssiduite">'.$langs->trans('AgfSessionSummaryTauxAssiduite').'</label>
								<span class="col-md-5 px-0" id="AgfSessionSummaryTauxAssiduite">'.number_format($tx_assi, 2).' %</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<h5>'.$langs->trans('AgfStagiaireList').'</h5>
			<div class="panel panel-default">
				<div class="panel-body py-0">';
	
	$out.= '<ul class="list-group list-group-flush my-0">';
	foreach ($stagiaires->lines as &$stagiaire)
	{
		if ($stagiaire->id <= 0) continue;	
		$out.= '<li class="list-group-item"><i class="fa fa-'.(in_array($stagiaire->civilite, array('MME', 'MLE')) ? 'female' : 'male').'"></i><span class="ml-2">'.strtoupper($stagiaire->nom) . ' ' . ucfirst($stagiaire->prenom).'</span></li>';
	}
	$out.= '</ul>';
	
	$out.= '
				</div>
			</div>
		</div>

	';
	
	return $out;
}

function getPageViewSessionCardExternalAccess_files($agsession, $trainer)
{
    global $langs, $db, $conf;
    $context = Context::getInstance();
    
    $upload_dir = $conf->agefodd->dir_output;
    $filearray=dol_dir_list($upload_dir,"files",0,'','',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

    $files = array();
    if (!empty($filearray)){
        $TCommonModels = array(
//             "conseils",
            "fiche_presence",
            "fiche_presence_direct",
            "fiche_presence_empty",
            "fiche_presence_landscape",
            "fiche_presence_trainee",
            "fiche_presence_trainee_direct",
//             "fiche_evaluation",
//             "fiche_remise_eval",
            "chevalet",
//             "attestationendtraining_empty"
        );
        
        $TTrad = array(
            "fiche_presence" => "AgfFichePresence",
            "fiche_presence_direct" => "AgfFichePresenceDirect",
            "fiche_presence_empty" => "AgfFichePresenceEmpty",
            "fiche_presence_landscape" => "AgfFichePresenceTraineeLandscape",
            "fiche_presence_trainee" => "AgfFichePresenceTrainee",
            "fiche_presence_trainee_direct" => "AgfFichePresenceTraineeDirect",
            "chevalet" => "AgfChevalet",
            "mission_trainer" => "AgfTrainerMissionLetter",
            "contrat_trainer" => "AgfContratTrainer"
        );
        
        foreach ($filearray as $file) {
            $mod = substr($file['name'], 0, strrpos($file['name'], '_'));
            if(in_array($mod, $TCommonModels) && preg_match("/^".$mod."_([0-9]+).pdf$/", $file['name'], $i) && $i[1] == $agsession->id) $files[$file['name']] = $langs->transnoentitiesnoconv($TTrad[$mod]);

            if((preg_match("/^mission_trainer_([0-9]+).pdf$/", $file['name'], $i) && $i[1] == $trainer->agefodd_session_formateur->id)
                || (preg_match("/^contrat_trainer_([0-9]+).pdf$/", $file['name'], $i) && $i[1] == $trainer->agefodd_session_formateur->id)
                )
            {
                $files[$file['name']] = $langs->trans($TTrad[$mod]);
            }
        }
    }
    
    $out = '';
    $out.= '
		<div class="container px-0">
			<div class="panel panel-default">
				<div class="panel-body">
					<div class="row clearfix">
						<div class="col-md-6">
                            <h5>Liste des fichiers générés pour cette session</h5>
                            <br>';
    if (count($files))
    {
        foreach ($files as $file => $type)
        {
            $out.= "<p>";
            $legende = $langs->trans("AgfDocOpen");
            $dowloadUrl = $context->getRootUrl().'script/interface.php?action=downloadSessionFile&file='.$file;
            $downloadLink = '<a class="btn btn-xs btn-primary" href="'.$dowloadUrl.'&amp;forcedownload=1" target="_blank" ><i class="fa fa-download"></i> '.$langs->trans('Download').'</a>';
            $out.= $downloadLink;
//             $out.= '<a href="' . DOL_URL_ROOT . '/document.php?modulepart=agefodd&file=' . $file . '" alt="' . $legende . '" title="' . $legende . '">';
//             $out.=img_picto($legende, 'pdf2').'</a>&nbsp;&nbsp;';
            $out.= '&nbsp;&nbsp;'.$type;
            $out.= "</p>";
            
        }
    }
    else
    {
        $out.= "<p>";
        $out.= "Aucun fichier disponible pour le moment";
        $out.= "</p>";
    }
	$out.= '
						</div>
								    
						<div class="col-md-6">
                            <h5>Déposer un fichier pour cette session</h5><br>';
	dol_include_once('/core/class/html.formfile.class.php');
	$formfile = new FormFile($db);
	
	// Show upload form (document and links)
	ob_start();
	$formfile->form_attach_new_file(
	    $_SERVER["PHP_SELF"].'?controller=agefodd_session_card&action=uploadfile&sessid='.$agsession->id.'&tab=session-files-tab',
	    'none',
	    0,
	    0,
	    1,
	    $conf->browser->layout == 'phone' ? 40 : 60,
	    $agsession,
	    '',
	    1,
	    '',
	    0
	    );
	$out.= ob_get_clean();
	$out.= '				<h5>Liste des fichiers déposés pour cette session</h5><br>';
	$upload_dir = $conf->agefodd->dir_output . "/" .$agsession->id;
	$filearray=dol_dir_list($upload_dir,"files",0,'','',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);

	if(count($filearray))
	{
	    foreach ($filearray as $file)
	    {
	        $out.="<p>";
	        $out.= $file['name'];
	        $out.= "</p>";
	    }
	}
	else
	{
	    $out.= "<p>";
	    $out.= "Aucun fichier déposé pour le moment";
	    $out.= "</p>";
	}
	
	$out.=			    '</div>
					</div>
				</div>
			</div>';
    
    return $out;
}


function getPageViewSessionCardCalendrierFormateurExternalAccess($agsession, $trainer, $agf_calendrier_formateur, $agf_calendrier, $action='')
{
	global $db,$langs;
	
	dol_include_once('/agefodd/class/html.formagefodd.class.php');
	$formAgefodd = new FormAgefodd($db);
	
	$context = Context::getInstance();
	
	$billed = $agf_calendrier->billed;
	
	if ($billed) $action = 'view';
	
	if ($action != 'view')
	{
		if (!empty($agf_calendrier_formateur->id)) $action = 'update';
		else $action = 'add';
	}
	
	$out = '';
	$out.= '<section id="section-session-card-calendrier-formateur" class="py-5"><div class="container">';
	$out.= getEaNavbar($context->getRootUrl('agefodd_session_card', '&sessid='.$agsession->id.'&save_lastsearch_values=1'));
	
	if ($action != 'view') 
	{
		$out.= '
			<form action="'.$_SERVER['PHP_SELF'].'" method="POST" class="clearfix">
				<input type="hidden" name="action" value="'.$action.'" />
				<input type="hidden" name="sessid" value="'.$agsession->id.'" />
				<input type="hidden" name="trainerid" value="'.$trainer->id.'" />
				<input type="hidden" name="slotid" value="'.$agf_calendrier_formateur->id.'" />
				<input type="hidden" name="controller" value="'.$context->controller.'" />';
	}
	
	$calendrier_type = !empty($agf_calendrier->calendrier_type) ? $agf_calendrier->calendrier_type : '';
	$out.= '
			<h4>'.$langs->trans('AgfExternalAccessSessionCardCreneau').'</h4>';
	
	if ($billed)
	{
	    $out.= '
			<div class="alert alert-secondary" role="alert">
				'.$langs->trans('AgfExternalAccessSessionCardBilled').'
			</div>';
	}
	
	$out.='
			<div class="form-group">
				<label for="heured">Date</label>
				<input '.($action == 'view' ? 'readonly' : '').' type="date" class="form-control" id="date_session" required name="date_session" value="'.(($action == 'update' || $action == 'view') ? date('Y-m-d', $agf_calendrier_formateur->date_session) : date('Y-m-d')).'">
			</div>
			<div class="form-group">
				<label for="heured">Heure début:</label>
				<input '.($action == 'view' ? 'readonly' : '').' type="time" class="form-control" step="900" id="heured" required name="heured" value="'.(($action == 'update' || $action == 'view') ? date('H:i', $agf_calendrier_formateur->heured) : '09:00' ).'">
				<label for="heuref">Heure fin:</label>
				<input '.($action == 'view' ? 'readonly' : '').' type="time" class="form-control" step="900" id="heuref" required name="heuref" value="'.(($action == 'update' || $action == 'view') ? date('H:i', $agf_calendrier_formateur->heuref) : '12:00' ).'">
			</div>
			<div class="form-group">
				<label for="status">Status</label>
				<select '.($action == 'view' ? 'disabled' : '').' class="form-control" id="status" name="status">
					<option '.($agf_calendrier_formateur->status == Agefoddsessionformateurcalendrier::STATUS_DRAFT ? 'selected' : '').' value="'.Agefoddsessionformateurcalendrier::STATUS_DRAFT.'">'.$langs->trans('AgfStatusCalendar_previsionnel').'</option>
					<option '.($agf_calendrier_formateur->status == Agefoddsessionformateurcalendrier::STATUS_CONFIRMED ? 'selected' : '').' value="'.Agefoddsessionformateurcalendrier::STATUS_CONFIRMED.'">'.Agefoddsessionformateurcalendrier::getStaticLibStatut(Agefoddsessionformateurcalendrier::STATUS_CONFIRMED, 0).'</option>
                    <option '.($agf_calendrier_formateur->status == Agefoddsessionformateurcalendrier::STATUS_MISSING ? 'selected' : '').' value="'.Agefoddsessionformateurcalendrier::STATUS_MISSING.'">'.Agefoddsessionformateurcalendrier::getStaticLibStatut(Agefoddsessionformateurcalendrier::STATUS_MISSING, 0).'</option>
					<option '.($agf_calendrier_formateur->status == Agefoddsessionformateurcalendrier::STATUS_CANCELED ? 'selected' : '').' value="'.Agefoddsessionformateurcalendrier::STATUS_CANCELED.'">'.Agefoddsessionformateurcalendrier::getStaticLibStatut(Agefoddsessionformateurcalendrier::STATUS_CANCELED, 0).'</option>
				</select>
			</div>
			<div class="form-group">
				<label for="status">Type</label>
				'.$formAgefodd->select_calendrier_type($calendrier_type, 'code_c_session_calendrier_type', true, ($action == 'view' ? 'disabled' : ''), 'form-control').'
			</div>
			';
	
	$stagiaires = new Agefodd_session_stagiaire($db);
	$stagiaires->fetch_stagiaire_per_session($agsession->id);
	if (!empty($stagiaires->lines))
	{
		$TCalendrier = _getCalendrierFromCalendrierFormateur($agf_calendrier_formateur, true, true);
		if (is_string($TCalendrier)) 
		{
			$context->setError($langs->trans('Agf_EA_error_sql'));
			$TCalendrier = array();
		}
		$agfssh = new Agefoddsessionstagiaireheures($db);
		$result = 0;



		$out.= '<h4>'.$langs->trans('AgfExternalAccessSessionCardDeclareHours').'</h4>';

		$out.= '
			<div class="alert alert-secondary" role="alert">
				'.$langs->trans('AgfExternalAccessSessionCardDeclareHoursInfo').'
			</div>';

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
					<input '.($action == 'view' || $agf_calendrier_formateur->date_session > dol_now() ? 'readonly' : '').' type="time" step="900" class="form-control" id="stagiaire_'.$stagiaire->id.'" name="hours['.$stagiaire->id.']" value="'.(!empty($secondes) ? convertSecondToTime($secondes) : '00:00').'" />
				</div>';
		}
	}
	
	if ($action != 'view') 
	{
		$out.= '<input type="submit" class="btn btn-primary pull-right" value="'.$langs->trans('Save').'" />
			</form>';
	}
	
	
	$out.= '</div></section>';
	
	return $out;
}
