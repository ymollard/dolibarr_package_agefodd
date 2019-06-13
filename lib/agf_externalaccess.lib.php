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
	global $langs, $user;

	$context = Context::getInstance();
	$html = '	<!-- getMenuAgefoddExternalAccess -->
				<section id="agefodd">
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

	if($user->rights->agefodd->external_trainer_agenda){
		$link = $context->getRootUrl('agefodd_trainer_agenda');
		$html.= getService($langs->trans('AgfMenuAgendaFormateur'),'fa-calendar',$link);
	}



	$html.= '</div>
			</div>
		  </section>';

	return $html;
}

/**
 * Affiche la liste des sessions du formateur courant agefodd
 *
 * route => agefodd_session_list
 *
 * @return string
 */
function getPageViewSessionListExternalAccess()
{
	global $langs,$db,$user, $conf;

	$context = Context::getInstance();

	if (!validateFormateur($context)) return '';

	$formateur = new Agefodd_teacher($db);
	$agsession = new Agsession($db);

	$formateur->fetchByUser($user);
	if (!empty($formateur->id))
	{
		$agsession->fetch_session_per_trainer($formateur->id);
	}

	$out = '<!-- getPageViewSessionListExternalAccess -->';
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

			$filters['excludeCanceled'] = true;
			$agsession->fetchTrainers($item->rowid);
			$trainerinsessionid = 0;
			if (!empty($agsession->TTrainer))
			{
			    foreach ($agsession->TTrainer as $trainer)
			    {
			        if ($trainer->id == $formateur->id) $trainerinsessionid = $trainer->agefodd_session_formateur->id;
			    }
			}

			if (!empty($trainerinsessionid)) $filters['formateur'] = $trainerinsessionid;
			$duree_declared = Agsession::getStaticSumDureePresence($item->rowid, null, $filters);
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
 * route => agefodd_session_card
 *
 * @param Agsession $agsession
 * @param Teacher $trainer
 * @return string
 */
function getPageViewSessionCardExternalAccess(&$agsession, &$trainer)
{
	global $db,$langs, $user, $hookmanager;

	$context = Context::getInstance();
	if (!validateFormateur($context)) return '';

	$tab = GETPOST('tab');
	$agf_calendrier_formateur = new Agefoddsessionformateurcalendrier($db);
	$agf_calendrier_formateur->fetchAllBy(array('trainer.rowid'=>$trainer->id, 'sf.fk_session'=>$agsession->id), '');

	$out = '<!-- getPageViewSessionCardExternalAccess -->';
	$out.= '<section id="section-session-card" class="py-5"><div class="container">';

	$url_add = '';
	if (!empty($user->rights->agefodd->external_trainer_write)) $url_add = $context->getRootUrl('agefodd_session_card_time_slot', '&sessid='.$agsession->id.'&slotid=0');

	$out.= getEaNavbar($context->getRootUrl('agefodd_session_list', '&save_lastsearch_values=1'), $url_add);

	$out.= '
		<blockquote class="blockquote">
			<p>'.$agsession->ref.'</p>
			<p>'.$agsession->trainer_ext_information.'</p>
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

    $out.= '</div>';

    $parameters=array(
        'agsession' =>& $agsession,
        'trainer' =>& $trainer
    );
    $reshook=$hookmanager->executeHooks('agf_getPageViewSessionCardExternalAccess', $parameters, $agf_calendrier_formateur);

    if (!empty($reshook)){
        // override full output
        $out = $hookmanager->resPrint;
    }
    else{
        $out.= $hookmanager->resPrint;
        $out.= '</section>';
    }

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
	global $langs, $user, $hookmanager;

	$context = Context::getInstance();

	if (!validateFormateur($context)) return '';

	$out = '<!-- getPageViewSessionCardExternalAccess_creneaux -->';
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

		if ((empty($agf_calendrier) || empty($agf_calendrier->billed)) && $user->rights->agefodd->external_trainer_write)
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



    $parameters=array(
        'agsession' =>& $agsession,
        'trainer' =>& $trainer
    );
    $reshook=$hookmanager->executeHooks('agf_getPageViewSessionCardExternalAccess_creneaux', $parameters, $agf_calendrier_formateur);

    if (!empty($reshook)){
        // override full output
        $out = $hookmanager->resPrint;
    }
    else{
        $out.= $hookmanager->resPrint;
    }

	return $out;
}

function getPageViewSessionCardExternalAccess_summary(&$agsession, &$trainer, &$agf_calendrier_formateur)
{
	global $langs,$db;

	$context = Context::getInstance();
	if (!validateFormateur($context)) return '';

	$agefodd_sesscalendar = new Agefodd_sesscalendar($db);
	$agefodd_sesscalendar->fetch_all($agsession->id);
	$stagiaires = new Agefodd_session_stagiaire($db);
	$stagiaires->fetch_stagiaire_per_session($agsession->id);

	$nbstag = count($stagiaires->lines);

	$date_deb = dol_print_date($agsession->dated, 'daytext');
	$date_fin = dol_print_date($agsession->datef, 'daytext');
	$duree_scheduled = 0;
	$duree_scheduled_total = 0;
	$duree_presence_max_comptabilise = 0;
	$duree_presence_comptabilise = 0;
	$duree_presence_comptabilise_cancel = 0;
	$duree_presence_draft = 0;

	// somme des heures programmées pour le formateur excluant les horaires annulés et les heures de type "plateforme"
	foreach ($agf_calendrier_formateur->lines as &$line)
	{
	    $TCal = _getCalendrierFromCalendrierFormateur($line, true, true);
	    if ((int)$line->status == Agefoddsessionformateurcalendrier::STATUS_CANCELED || $TCal[0]->calendrier_type == 'AGF_TYPE_PLATF') continue;
		$duree_scheduled += ($line->heuref - $line->heured) / 60 / 60;
	}

	foreach ($agefodd_sesscalendar->lines as &$agf_calendrier)
	{
		/** @var Agefodd_sesscalendar $agf_calendrier */
	    $tmparr = $agf_calendrier->getSumDureePresence();
	    $duree_declared = $tmparr[0];
	    $duree_max = $tmparr[1];

	    if ((int)$agf_calendrier->status !== Agefodd_sesscalendar::STATUS_CANCELED && $agf_calendrier->calendrier_type !== 'AGF_TYPE_PLATF') $duree_scheduled_total += ($agf_calendrier->heuref - $agf_calendrier->heured)/3600;

		if ($agf_calendrier->status == Agefodd_sesscalendar::STATUS_CONFIRMED)
		{
			$duree_presence_comptabilise += $duree_declared;
			$duree_presence_max_comptabilise += $duree_max;
		}
		else if ($agf_calendrier->status == Agefodd_sesscalendar::STATUS_CANCELED)
		{
			$duree_presence_comptabilise_cancel += ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
			$duree_presence_max_comptabilise += $duree_max;
		}
		else $duree_presence_draft += $duree_declared;
	}

	$total_duree_comptabilise = $duree_presence_comptabilise+$duree_presence_comptabilise_cancel;
	if ($total_duree_comptabilise > 0) $tx_assi = $duree_presence_comptabilise * 100 / $duree_presence_max_comptabilise;
	else $tx_assi = 0;

	$out = '';

	$out.= '<!-- getPageViewSessionCardExternalAccess_summary -->
		<div class="container px-0">
			<h5>'.$langs->trans('AgfSessionSummary', $date_deb, $date_fin).'</h5>
			<div class="panel panel-default">
				<div class="panel-body">
					<div class="row clearfix">
						<div class="col-md-6">
							<div class="form-group">
								<label class="col-md-7 px-0" for="AgfSessionSummaryTotalHours">'.$langs->trans('AgfSessionSummaryTotalHours').'</label>
								<span class="col-md-5 px-0" id="AgfSessionSummaryTotalHours">'.$langs->trans('AgfHours', price($agsession->duree_session * $nbstag, 0, '', 1, -1, 2)).'</span>
							</div>
                            <div class="form-group">
								<label class="col-md-7 px-0"  for="AgfSessionSummaryTotalScheduled">'.$langs->trans('AgfSessionSummaryTotalScheduled').'</label>
								<span class="col-md-5 px-0" id="AgfSessionSummaryTotalScheduled">'.$langs->trans('AgfHours', price($duree_scheduled_total, 0, '', 1, -1, 2)).'</span>
							</div>
							<div class="form-group">
								<label class="col-md-7 px-0"  for="AgfSessionSummaryTotalScheduled">'.$langs->trans('AgfSessionSummaryTotalScheduledTrainer').'</label>
								<span class="col-md-5 px-0" id="AgfSessionSummaryTotalScheduled">'.$langs->trans('AgfHours', price($duree_scheduled, 0, '', 1, -1, 2)).'</span>
							</div>
							<div class="form-group">
								<label class="col-md-7 px-0"  for="AgfSessionSummaryTotalLeft">'.$langs->trans('AgfSessionSummaryTotalLeft').'</label>
								<span class="col-md-5 px-0" id="AgfSessionSummaryTotalLeft">'.$langs->trans('AgfHours', price($duree_scheduled_total - ($agsession->duree_session * $nbstag), 0, '', 1, -1, 2)).'</span>
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
    global $langs, $db, $conf, $user;
    $context = Context::getInstance();
    if (!validateFormateur($context)) return '';

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
    $out.= '<!-- getPageViewSessionCardExternalAccess_files -->
		<div class="container px-0">
			<div class="panel panel-default">
				<div class="panel-body">
					<div class="row clearfix">
						<div class="col-md-6">
                            <h5>Liste des fichiers générés pour cette session</h5>
                            <br>';
    if (empty($user->rights->agefodd->external_trainer_download)){
        $out.='<div class="alert alert-secondary" role="alert">
				Vous n\'avez pas le droit nécessaire au téléchargement de fichiers
			</div>';
    }
    if (count($files))
    {
        foreach ($files as $file => $type)
        {
            $out.= "<p>";
            $legende = $langs->trans("AgfDocOpen");
            $dowloadUrl = $context->getRootUrl().'script/interface.php?action=downloadSessionFile&file='.$file;
            $downloadLink = '<a class="btn btn-xs btn-primary" href="'.$dowloadUrl.'&amp;forcedownload=1" target="_blank" ><i class="fa fa-download"></i> '.$langs->trans('Download').'</a>';
            if (!empty($user->rights->agefodd->external_trainer_download)) $out.= $downloadLink;
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

	if (empty($user->rights->agefodd->external_trainer_upload)){
	    $out.='<div class="alert alert-secondary" role="alert">
				Vous n\'avez pas le droit nécessaire au dépot de fichiers
			</div>';
	}
	// Show upload form (document)
	ob_start();
	$formfile->form_attach_new_file(
	    $_SERVER["PHP_SELF"].'?controller=agefodd_session_card&action=uploadfile&sessid='.$agsession->id.'&tab=session-files-tab',
	    'none',
	    0,
	    0,
	    $user->rights->agefodd->external_trainer_upload,
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

function getPageViewSessionCardCalendrierFormateurAddFullCalendarEventExternalAccess($action='')
{
	global $db,$langs, $hookmanager, $user;

	$context = Context::getInstance();
	$trainer = new Agefodd_teacher($db);

	if ($trainer->fetchByUser($user) > 0) {

		$out = '<!-- getPageViewSessionCardCalendrierFormateurAddFullCalendarEventExternalAccess -->';
		$out .= '<section id="section-session-card-calendrier-formateur" class="py-5">';
		$out .= '<div class="container">';
		$out .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST" class="clearfix">';
		$out .= '<input type="hidden" name="iframe" value="' . $context->iframe . '" />';
		$out .= '<input type="hidden" name="controller" value="' . $context->controller . '" />';

		$startDate = DateTime::createFromFormat("Y-m-d\TH:i:s P", GETPOST('start'));
		$fullDay = false;
		if (!$startDate) {
			$startDate = DateTime::createFromFormat("Y-m-d", GETPOST('start'));
			$fullDay = true;
		}


		if (!empty($startDate)) {
			$out .= '<input type="hidden" name="date" 	value="' . $startDate->format('Y-m-d') . '" />';
			$out .= '<input type="hidden" name="heured" 	value="' . $startDate->format('H:i') . '" />';
		}


		$endDate = DateTime::createFromFormat("Y-m-d\TH:i:s P", GETPOST('end'));


		if (!empty($endDate)) {
			$out .= '<input type="hidden" name="heuref" 	value="' . $endDate->format('H:i') . '" />';
		} elseif ($startDate) {
			$startDate->add(new DateInterval('PT1H'));
			$out .= '<input type="hidden" name="heuref" 	value="' . $startDate->format('H:i') . '" />';
		}

		$agsession = new Agsession($db);
		$agsession->fetch_session_per_trainer($trainer->id);
		$optionSessions = '';
		if (!empty($agsession->lines)) {
			foreach ($agsession->lines as $line) {
				$optionSessions .= '<option value="' . $line->rowid . '">' . $line->sessionref . ' : ' . $line->intitule . '</option>';
			}
		}

		$out .= '<div class="form-group">';
		$out .= '<label for="sessid">' . $langs->trans('AgfSelectSession') . '</label>';
		$out .= '<select class="form-control" name="sessid">' . $optionSessions . '</select>';
		$out .= '</div>';


		$out .= '<button type="submit" class="btn btn-primary pull-right" >' . $langs->trans('Next') . '</button>';

		$out .= '</form>';


		// Others options
		$out .= '<div class="or">' . $langs->trans('OrSeparator') . '</div>';

		$out .= '<div class="row">';

		$enableAddNotAvailableRange = false;
		$link = '';
		if ((!empty($startDate) && $fullDay && empty($endDate)) || (!empty($startDate) && !empty($endDate))) {
			$enableAddNotAvailableRange = true;

			$linkParams = '&action=add&type=AC_AGF_NOT_AVAILABLE_RANGE';
			if (!empty($startDate)) {
				$linkParams .= '&heured=' . urlencode($startDate->format('Y-m-d\TH:i'));
				if ($fullDay) {
					$linkParams .= '&date=' . urlencode($startDate->format('Y-m-d\TH:i'));
				}
			}

			if (!empty($endDate)) {
				$linkParams .= '&heuref=' . urlencode($endDate->format('Y-m-d\TH:i'));
			}

			$link = $context->getRootUrl('agefodd_event_other', $linkParams);

		}

		$out .= getService($langs->trans('Agf_TrainerNotAvailableRange'), 'fa-calendar-times-o', $link, $langs->trans('Agf_TrainerNotAvailableRangeDesc'), !$enableAddNotAvailableRange);

		$parameters=array(
			'startDate' => $startDate,
			'endDate' => $endDate,
			'action' => $action,
			'fullDay' => $fullDay
		);
		$reshook=$hookmanager->executeHooks('getPageViewSessionCardCalendrierFormateurAddFullCalendarEventExternalAccess', $parameters, $agf_calendrier_formateur);

		if (!empty($reshook)){
			// override full output
			$out = $hookmanager->resPrint;
		}
		else{
			$out.= $hookmanager->resPrint;
		}


		$out .= '</div>';

		$out .= '</div></section>';

		return $out;
	}
}

function getPageViewSessionCardCalendrierFormateurExternalAccess($agsession, $trainer, $agf_calendrier_formateur, $agf_calendrier, $action='')
{
	global $db,$langs, $hookmanager, $user;

	dol_include_once('/agefodd/class/html.formagefodd.class.php');
	$formAgefodd = new FormAgefodd($db);

	$context = Context::getInstance();
	if (!validateFormateur($context)) return '';

	$billed = $agf_calendrier->billed;

	if ($billed) $action = 'view';

	if ($action != 'view')
	{
		if (!empty($agf_calendrier_formateur->id)) $action = 'update';
		else $action = 'add';
	}

	$out = '<!-- getPageViewSessionCardCalendrierFormateurExternalAccess -->';
	$out.= '<section id="section-session-card-calendrier-formateur" class="py-5"><div class="container">';

	$backUrl = $context->getRootUrl('agefodd_session_card', '&sessid='.$agsession->id.'&save_lastsearch_values=1');
	if($context->iframe){
		$backUrl=false;
	}
	$editUrl = '';
	if (empty($agf_calendrier->billed) && $user->rights->agefodd->external_trainer_write && $action != 'update' && $action != 'add'){
		$editUrl = $context->getRootUrl('agefodd_session_card_time_slot', '&sessid='.$agsession->id.'&slotid='.$agf_calendrier_formateur->id);
	}
	$out.= getEaNavbar($backUrl, '', $editUrl);

	if ($action != 'view')
	{
		$out.= '
			<form action="'.$_SERVER['PHP_SELF'].'" method="POST" class="clearfix">
				<input type="hidden" name="action" value="'.$action.'" />
				<input type="hidden" name="sessid" value="'.$agsession->id.'" />
				<input type="hidden" name="trainerid" value="'.$trainer->id.'" />
				<input type="hidden" name="slotid" value="'.$agf_calendrier_formateur->id.'" />
				<input type="hidden" name="iframe" value="'.$context->iframe.'" />
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

	$TStatus = Agefoddsessionformateurcalendrier::getListStatus();
	$statusOptions = '';
	foreach ($TStatus as $statusKey => $label)
	{
		$statusOptions.= '<option '.($agf_calendrier_formateur->status == $statusKey ? 'selected' : '').' value="'.$statusKey.'">'.$label.'</option>';
	}


	$date_session = (($action == 'update' || $action == 'view') ? date('Y-m-d', $agf_calendrier_formateur->date_session) : date('Y-m-d'));
	if(isset($_POST['date'])){
		$date_session = GETPOST('date');
	}

	$heured = (($action == 'update' || $action == 'view') ? date('H:i', $agf_calendrier_formateur->heured) : '09:00' );
	if(isset($_POST['heured'])){
		$heured = GETPOST('heured');
	}

	$heuref = (($action == 'update' || $action == 'view') ? date('H:i', $agf_calendrier_formateur->heuref) : '12:00' );
	if(isset($_POST['heuref'])){
		$heuref = GETPOST('heuref');
	}


	$out.='
	<div class="form-row">
		<div class="col">
			<div class="form-group">
				<label for="heured">Date</label>
				<input '.($action == 'view' ? 'readonly' : '').' type="date" class="form-control" id="date_session" required name="date_session" value="'.$date_session.'">
			</div>
		</div>
		<div class="col">
			<div class="form-group">
				<label for="heured">Heure début:</label>
				<input '.($action == 'view' ? 'readonly' : '').' type="time" class="form-control" step="900" id="heured" required name="heured" value="'.$heured.'">
			</div>
		</div>	
		<div class="col">
			<div class="form-group">
				<label for="heuref">Heure fin:</label>
				<input '.($action == 'view' ? 'readonly' : '').' type="time" class="form-control" step="900" id="heuref" required name="heuref" value="'.$heuref.'">
			</div>
		</div>	
	</div>
	
	<div class="form-row">
		<div class="col">
		
			<div class="form-group">
				<label for="status">Status</label>
				<select '.($action == 'view' ? 'disabled' : '').' class="form-control" id="status" name="status" >
					'.$statusOptions.'
				</select>
			</div>
		</div>	
		<div class="col">
			<div class="form-group">
				<label for="status">Type</label>
				'.$formAgefodd->select_calendrier_type($calendrier_type, 'code_c_session_calendrier_type', true, ($action == 'view' ? 'disabled' : ''), 'form-control').'
			</div>
		</div>	
	</div>

			<script>
			$( document ).ready(function() {
				if($("#status").val() == \''.Agefoddsessionformateurcalendrier::STATUS_CONFIRMED.'\')
				{
					$("#code_c_session_calendrier_type").prop(\'required\',true);
				} else {
					$("#code_c_session_calendrier_type").prop(\'required\',false);
				}
				$("#status").change(function() {

				   	var formStatus = $(this).val();

					if(formStatus == \''.Agefoddsessionformateurcalendrier::STATUS_CONFIRMED.'\')
					{
						$("#code_c_session_calendrier_type").prop(\'required\',true);
					} else {
						$("#code_c_session_calendrier_type").prop(\'required\',false);
					}

				});
			});

			</script>
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


    $parameters=array(
        'agsession' =>& $agsession,
        'trainer' =>& $trainer,
        'agf_calendrier' => $agf_calendrier,
        'action' => $action,
        'agf_calendrier_formateur' => $agf_calendrier_formateur
    );
    $reshook=$hookmanager->executeHooks('agf_getPageViewSessionCardCalendrierFormateurExternalAccess', $parameters, $agf_calendrier_formateur);

    if (!empty($reshook)){
        // override full output
        $out = $hookmanager->resPrint;
    }
    else{
        $out.= $hookmanager->resPrint;
    }


    $buttons= '';
	if ($action != 'view')
	{
        $buttons.= '<input type="submit" class="btn btn-primary pull-right" value="'.$langs->trans('Save').'" />';
	}

	$buttons.= '<button type="button" class="btn btn-danger" data-id="21" data-toggle="modal" data-target="#session-card-delete-time-slot" ><i class="fa fa-trash"></i>  Supprimer </button>';


    $parameters=array(
        'agsession' =>& $agsession,
        'trainer' =>& $trainer,
        'agf_calendrier' => $agf_calendrier,
        'action' => $action,
        'agf_calendrier_formateur' => $agf_calendrier_formateur
    );
    $reshook=$hookmanager->executeHooks('ExternalAccess_addMoreActionsButtons', $parameters, $agf_calendrier_formateur);

    if (!empty($reshook)){
        // override full output
        $buttons = $hookmanager->resPrint;
    }
    else{
        $buttons.= $hookmanager->resPrint;
    }

    $out.= $buttons;

    if ($action != 'view')
    {
        $out.= '</form>';
    }

    $out.= '</div>';



    $parameters=array(
        'agsession' =>& $agsession,
        'trainer' =>& $trainer,
        'agf_calendrier' => $agf_calendrier,
        'action' => $action,
        'agf_calendrier_formateur' => $agf_calendrier_formateur
    );
    $reshook=$hookmanager->executeHooks('agf_getPageViewSessionCardCalendrierFormateurExternalAccess_afterForm', $parameters, $agf_calendrier_formateur);

    if (!empty($reshook)){
        // override full output
        $out = $hookmanager->resPrint;
    }
    else{
        $out.= $hookmanager->resPrint;
    }





    $out.= '</section>';

    // Delete creneau modal
	$body = $langs->trans('Agf_EA_DeleteClandrierFormateurBody');
	$body.= '<input type="hidden" name="sessid" value="'.$agsession->id.'" />';
	$body.= '<input type="hidden" name="fk_agefodd_session_formateur_calendrier" value="'.$agf_calendrier_formateur->id.'" />';


	$out .= getEaModalConfirm('session-card-delete-time-slot', $langs->trans('Agf_EA_DeleteClandrierFormateurTitle'), $body, $context->getRootUrl('agefodd_session_card'), 'deleteCalendrierFormateur');


	return $out;
}

function validateFormateur($context)
{
    global $conf, $user, $langs;

    $errors = array();

    // si l'accés formateur n'est pas activé ont rejette
    if (empty($conf->global->AGF_EA_TRAINER_ENABLED))
    {
        $errors[] = $context->setError($langs->trans('AgfErrorAccessTrainerNotActivated'));
    }

    // si l'utilisateur n'a pas le droit de lecture externe
    if(empty($user->rights->agefodd->external_trainer_read))
    {
        $errors[] = $context->setError($langs->trans('AgfErrorRightsNotValide'));
    }

    if (count($errors))
    {
        $context->setError($errors);
        return false;
    }
    else return true;
}

function getPageViewAgendaOtherExternalAccess()
{

	global $db, $conf, $user, $langs;

	include_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

	$context = Context::getInstance();

	$action = 'view';
	if($context->action == 'add' || $context->action == 'edit'){
		$action = 'edit';
	}

	$event = new ActionComm($db);

	$id = GETPOST('id', 'int');
	if(!empty($id)){
		$event->fetch(intval($id));
	}

	$html = '';

	$note = GETPOST('note', 'nohtml');
	if(empty($note)){
		$note = $event->note;
	}

	// Get start date
	$heured = GETPOST('heured');
	$startDate 	= new DateTime();
	if(empty($heured) && !empty($event->id)){
		$startDate 	= DateTime::setTimestamp ( $event->datep );
	}
	elseif(!empty($heured)){
		$startDate 	= parseFullCalendarDateTime($heured);
	}

	if(!empty($startDate)){
		$heured = $startDate->format('Y-m-d\TH:i');
	}

	// Get end date
	$heuref = GETPOST('heuref');
	$endDate = new DateTime();
	if(empty($heuref) && !empty($event->id)){
		$endDate = DateTime::setTimestamp ( $event->datef );
	}
	elseif(!empty($heuref)){
		$endDate = parseFullCalendarDateTime($heuref);
	}

	if(!empty($endDate)){
		$heuref = $endDate->format('Y-m-d\TH:i');
	}

	$TAvailableType = getEnventOtherTAvailableType();


	if ($action == 'edit'){
		$html.= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" class="clearfix">';
		$html.= '<input type="hidden" name="iframe" value="'.$context->iframe.'" />';
		$html.= '<input type="hidden" name="controller" value="'.$context->controller.'" />';
	}

	$type = GETPOST('type');
	if(!in_array($type, $TAvailableType)){
		$typeTitle = $langs->trans('AgfAgendaOtherTypeNotValid') ;
	}
	else{
		$typeTitle = $langs->trans('AgfAgendaOtherType_'.$type) ;
		$html.='<input type="hidden" name="type" value="'.$type.'" />';
	}

	$html.='<h4>'.$typeTitle.'</h4>';

	if(!empty($id)){
		$html.='<input type="hidden" name="id" value="'.$id.'" />';
	}

	$html.='
	<div class="form-row">
		<div class="col">
			<div class="form-group">
				<label for="heured">'.$langs->trans('StartDateTime').'</label>
				<input '.($action == 'view' ? 'readonly' : '').' type="datetime-local" class="form-control" id="heured" required name="heured" value="'.$heured.'">
			</div>
		</div>
		<div class="col">
			<div class="form-group">
				<label for="heuref">'.$langs->trans('EndDateTime').'</label>
				<input '.($action == 'view' ? 'readonly' : '').' type="datetime-local" class="form-control" id="heuref" required name="heuref" value="'.$heuref.'">
			</div>
		</div>
	</div>
	
	
	<div class="form-group">
		<label for="actionnote">'.$langs->trans('Notes').'</label>
		<textarea '.($action == 'view' ? 'readonly' : '').' type="datetime-local" class="form-control" id="actionnote" name="note" >'.dol_htmlentities($event->note).'</textarea>
	</div>
	';

	if($action == 'edit'){
		$html.='<p><button class="btn btn-primary pull-right" type="submit" name="action" value="save" >'.$langs->trans('Save').'</button></p>';
		$html.= '</form>';
	}

	return '<section ><div class="container">'.$html.'</div></section >';
}

function getEnventOtherTAvailableType()
{
	// car à un moment il va bien en avoir d'autres ...
	return array(
		'AC_AGF_NOT_AVAILABLE_RANGE'
	);
}

function getPageViewAgendaFormateurExternalAccess(){

	global $conf, $user, $langs;

	$context = Context::getInstance();

	$html = '<div class="container"><div class="container-fluid">';
	$html.= '<div id="agf-agenda-formation" ></div>';
	$html.= '</div></div>';

	$html.= '<link href="'.$context->getRootUrl(). 'vendor/fullcalendar/packages/core/main.css" rel="stylesheet">';
	$html.= '<link href="'.$context->getRootUrl(). 'vendor/fullcalendar/packages/daygrid/main.css" rel="stylesheet">';
	$html.= '<link href="'.$context->getRootUrl(). 'vendor/fullcalendar/packages/timegrid/main.css" rel="stylesheet"" />';
	$html.= '<link href="'.$context->getRootUrl(). 'vendor/fullcalendar/packages/list/main.css" rel="stylesheet"" />';

	$html.= '<script src="'.$context->getRootUrl(). 'vendor/fullcalendar/packages/core/main.js"></script>';
	$html.= '<script src="'.$context->getRootUrl(). 'vendor/fullcalendar/packages/interaction/main.js"></script>';
	$html.= '<script src="'.$context->getRootUrl(). 'vendor/fullcalendar/packages/daygrid/main.js"></script>';
	$html.= '<script src="'.$context->getRootUrl(). 'vendor/fullcalendar/packages/timegrid/main.js"></script>';
	$html.= '<script src="'.$context->getRootUrl(). 'vendor/fullcalendar/packages/list/main.js"></script>';
	$html.= '<script src="'.$context->getRootUrl(). 'vendor/fullcalendar/packages/rrule/main.js"></script>';
	$html.= '<script src="'.$context->getRootUrl(). 'vendor/fullcalendar/packages/core/locales-all.js"></script>';



	$html.= '<script type="text/javascript">

	fullcalendarscheduler_interface = "'.$context->getRootUrl().'script/interface.php?action=getSessionAgenda";
	fullcalendarscheduler_initialLangCode = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_LOCALE_LANG) ? $conf->global->FULLCALENDARSCHEDULER_LOCALE_LANG : 'fr').'";
	fullcalendarscheduler_snapDuration = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_SNAP_DURATION) ? $conf->global->FULLCALENDARSCHEDULER_SNAP_DURATION : '00:15:00').'";
	fullcalendarscheduler_aspectRatio = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_ASPECT_RATIO) ? $conf->global->FULLCALENDARSCHEDULER_ASPECT_RATIO : '1.6').'";
	fullcalendarscheduler_minTime = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_MIN_TIME) ? $conf->global->FULLCALENDARSCHEDULER_MIN_TIME : '00:00').'";
	fullcalendarscheduler_maxTime = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_MAX_TIME) ? $conf->global->FULLCALENDARSCHEDULER_MAX_TIME : '23:00').'";


	fullcalendar_scheduler_resources_allowed = [];

	fullcalendar_scheduler_businessHours_week_start = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_START : '08:00').'";
	fullcalendar_scheduler_businessHours_week_end = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEK_END : '18:00').'";

	fullcalendar_scheduler_businessHours_weekend_start = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_START : '10:00').'";
	fullcalendar_scheduler_businessHours_weekend_end = "'.(!empty($conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END) ? $conf->global->FULLCALENDARSCHEDULER_BUSINESSHOURS_WEEKEND_END : '16:00').'";
    


	document.addEventListener(\'DOMContentLoaded\', function() {
    var calendarEl = document.getElementById(\'agf-agenda-formation\');

    var calendar = new FullCalendar.Calendar(calendarEl, {
		plugins: [ \'interaction\', \'dayGrid\', \'timeGrid\', \'list\', \'rrule\' ],
		defaultDate: \''.date('Y-m-d').'\',
		defaultView: \'timeGridWeek\',
		snapDuration: fullcalendarscheduler_snapDuration,
		weekNumbers: true,
		weekNumbersWithinDays: true,
		weekNumberCalculation: \'ISO\',
		header: {
			left: \'prev,next today\',
			center: \'title\',
			right: \'dayGridMonth,timeGridWeek,timeGridDay,listMonth\'
		},
		editable: false, // next step add rights and allow edition
      	selectable: true,
		locale: fullcalendarscheduler_initialLangCode,
		eventLimit: true, // allow "more" link when too many events
		eventRender: function(info) {

		    $(info.el).popover({
		    		title: info.event.title ,
		    		content: info.event.extendedProps.session_formateur_calendrier.msg,
		    		html: true,
		    		trigger: "hover"
		    });
		    
		},
		events: 
		{
			url: fullcalendarscheduler_interface,
			/*extraParams: {
				custom_param1: \'something\',
				custom_param2: \'somethingelse\'
			},*/
			failure: function() {
			//document.getElementById(\'script-warning\').style.display = \'block\'
			}
		},
		loading: function(bool) {
		//document.getElementById(\'loading\').style.display = bool ? \'block\' : \'none\';
		},
		eventClick: function(info) {
		    
    		info.jsEvent.preventDefault(); // don\'t let the browser navigate
    		//console.log ( info.event.extendedProps.session_formateur_calendrier );
    		//console.log ( info.event );
    		
			if (info.event.url.length > 0){
			    //console.log(info.event);
			    // Open url in new window
			    //window.open(info.event.url, "_blank");
			    
			    $("#calendarModalLabel").html(info.event.title);
			    $("#calendarModalIframe").attr("src",info.event.url + "&iframe=1");

    			$("#calendarModal").modal();
			    $("#calendarModalIframe").on("load", function() {
			        var calendarIframeHeight = 0;
					calendarIframeHeight = $(this).contents().find("#section-session-card-calendrier-formateur").height();
					
					if( $( window ).height() < (calendarIframeHeight + 200) ){
						$("#calendarModalIframe").height($( window ).height() - 200);
					}
					else if(calendarIframeHeight > 0){
						$("#calendarModalIframe").height(calendarIframeHeight);
					}
					else{
						$("#calendarModalIframe").height(400);
					}
				});

			    
			    
			    // Deactivate original link
			    return false;
			}
		},
		
		dateClick: function(info) {
			//newEventModal(info.startStr);
		},
		
		select: function(info) {
			newEventModal(info.startStr, info.endStr);
		}
    });

    // refresh event on modal close
    $("#calendarModal").on("hide.bs.modal", function (e) {
		calendar.refetchEvents();
	});
	

    
    
    calendar.render();
    
    
 	function newEventModal(start, end = 0){
 	     console.log(start);
 	    $("#calendarModalLabel").html("'.$langs->trans('Agf_fullcalendarscheduler_title_dialog_create_event').'");
 	    $("#calendarModalIframe").attr("src","'.$context->getRootUrl('agefodd_session_card_time_slot', '&iframe=1').'" + "&start=" + encodeURIComponent(start) + "&end=" + encodeURIComponent(end) );
    	$("#calendarModal").modal();
    	$("#calendarModalIframe").on("load", function() { $("#calendarModalIframe").height($( window ).height() - 200); });

 	    
 	}
		    
    
  });
		
	
	window.closeModal = function(){
    	$("#calendarModal").modal(\'hide\');
	};
</script>';


	$iframeModal = '
	<!-- Modal -->
	<div class="modal fade" id="calendarModal" tabindex="-1" role="dialog" aria-labelledby="calendarModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg" >
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="modal-title" id="calendarModalLabel">=</h4>
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				</div>

				<div class="modal-body">
				<iframe id="calendarModalIframe" src="" width="100%" height="300" frameborder="0" allowtransparency="true"></iframe>  
				</div>
			</div>
		</div>
	</div>
	<!-- /.modal -->
	';


	return '<section >'.$html.'</section >'.$iframeModal;
}



function  getAgefoddJsonAgendaFormateur($fk_formateur = 0, $start = 0, $end = 0){

	global $db, $hookmanager, $langs, $user, $globalSessionCache;

	$langs->load("agefodd@agefodd");

	dol_include_once('/agefodd/class/agsession.class.php');
	dol_include_once('/agefodd/class/agefodd_formateur.class.php');
	dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
	dol_include_once('/agefodd/class/agefodd_session_formateur_calendrier.class.php');
	dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');

	$context = Context::getInstance();

	$TRes = array();
	//if (empty($fk_formateur)) return json_encode($TRes);

	$sql = 'SELECT c.rowid, c.heured, c.heuref, s.ref ref_session, sf.fk_session, fc.intitule, fc.ref as ref_formation ';

	$sql.= ' FROM '.MAIN_DB_PREFIX.'agefodd_session_formateur_calendrier c ';

	$sql.= ' JOIN '.MAIN_DB_PREFIX.'agefodd_session_formateur sf ON (sf.rowid = c.fk_agefodd_session_formateur) ';
	$sql.= ' JOIN '.MAIN_DB_PREFIX.'agefodd_session s ON (s.rowid = sf.fk_session) ';
	$sql.= ' JOIN '.MAIN_DB_PREFIX.'agefodd_formation_catalogue fc ON (fc.rowid = s.fk_formation_catalogue) ';



	$sql.= ' WHERE 1 = 1 ';

	if(!empty($start)){
		$sql.= ' AND c.heured <= \''.date('Y-m-d H:i:s', $end).'\'';
	}

	if(!empty($start)){
		$sql.= ' AND c.heuref >= \''.date('Y-m-d H:i:s', $start).'\'';
	}

	if(!empty($fk_formateur)){
		$sql.= ' AND sf.fk_agefodd_formateur = '.intval($fk_formateur);
	}


	$resql = $db->query($sql);

	if ($resql)
	{
		while ($obj = $db->fetch_object($resql))
		{
			$event = new stdClass();

			//$event->groupId: 999,
			$event->title	= $obj->intitule . ' - ' . $langs->trans('AgfSessionDetail') . ' ' . $obj->ref_session;

			$event->toolTip = 'test';


			$agf_calendrier_formateur = new Agefoddsessionformateurcalendrier($db);
			$agf_calendrier_formateur->fetch($obj->rowid);

			// get agf session cache
			if(empty($globalSessionCache[$obj->fk_session]) || !is_object($globalSessionCache[$obj->fk_session])){
				$agf_session = new Agsession($db);
				$agf_session->fetch($obj->fk_session);
			}else{
				$agf_session = $globalSessionCache[$obj->fk_session];
			}

			$actionUrl = '&action=view';
			if (empty($agf_calendrier_formateur->billed) && $user->rights->agefodd->external_trainer_write) {
				$actionUrl = '';
			}

          	$event->url		= $context->getRootUrl('agefodd_session_card_time_slot', '&sessid='.$obj->fk_session.'&slotid='.$obj->rowid.$actionUrl);
          	$event->start	= date('c', $db->jdate($obj->heured));
			$event->end		= date('c', $db->jdate($obj->heuref));

			//...
			$event->session_formateur_calendrier = new stdClass();
			$event->session_formateur_calendrier->id = $obj->rowid;
			$event->session_formateur_calendrier->msg = '';


			$duree_declared = Agsession::getStaticSumDureePresence($obj->fk_session);

			$T = array();
			$T[] = '<small style="font-weight: bold;" >'.$langs->trans('AgfInfoSession').' :</small>';
			$T[] = $langs->trans('AgfDuree').' : '.$agf_session->duree_session;
			$T[] = $langs->trans('AgfDureeDeclared').' : '.$duree_declared;
			$T[] = $langs->trans('AgfDureeSolde').' : '. ($agf_session->duree_session - $duree_declared);

			$event->session_formateur_calendrier->msg.= implode('<br/>',$T);


			$parameters= array(
				'sqlObj' => $obj,
				'agf_calendrier_formateur' => $agf_calendrier_formateur,
			);

			$reshook=$hookmanager->executeHooks('externalaccess_getAgefoddJsonAgendaFormateur',$parameters,$event);    // Note that $action and $object may have been modified by hook

			if ($reshook>0)
			{
				$event = $hookmanager->resArray;
			}


			$TRes[] = $event;
		}
	}
	else
	{
		dol_print_error($db);
	}

	return json_encode($TRes);
}



/** Parses a string into a DateTime object, optionally forced into the given timezone.
 * @param $string
 * @param null $timezone
 * @return DateTime
 * @throws Exception
 */
function parseFullCalendarDateTime($string, $timezone=null) {
	$date = new DateTime(
		$string,
		$timezone ? $timezone : new DateTimeZone('UTC')
		// Used only when the string is ambiguous.
		// Ignored if string has a timezone offset in it.
	);
	if ($timezone) {
		// If our timezone was ignored above, force it.
		$date->setTimezone($timezone);
	}
	return $date;
}
