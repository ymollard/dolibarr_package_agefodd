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


function convertHundredthHoursToReadable($hours)
{
    $hours = doubleval($hours);

    $minutes = floor($hours * 60 % 60);
    $hours = floor($hours);
    if(empty($hours)){
        return (!empty($minutes)?$minutes.'min':'');
    }
    else{
        return floor($hours).'H'.(!empty($minutes)?$minutes:'');
    }
}


/**
 * Ajout les icône dans l'écran "services"
 *
 * @return string
 */
function getMenuAgefoddExternalAccess()
{
	global $langs, $user, $hookmanager;

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

    if($user->rights->agefodd->external_trainer_read) {
        $link = $context->getRootUrl('agefodd_session_list');
        $html .= getService($langs->trans('AgfMenuSess'), 'fa-hourglass', $link);
        // TODO faire les getService() pour avoir accés à d'autres objets d'agefodd (pour plus tard)
    }

	if($user->rights->agefodd->external_trainer_agenda){
		$link = $context->getRootUrl('agefodd_trainer_agenda');
		$html.= getService($langs->trans('AgfMenuAgendaFormateur'),'fa-calendar',$link);
	}

    if($user->rights->agefodd->external_trainee_read){
        $link = $context->getRootUrl('agefodd_trainee_session_list');
        $html.= getService($langs->trans('AgfMenuSessTrainee'),'fa-graduation-cap',$link);
    }

	if (is_object($hookmanager))
	{
		$params = array ();
		$reshook = $hookmanager->executeHooks('addAgefoddExternalAccessServices', $params);

		if (!empty($reshook)){
			// override full output
			$html = $hookmanager->resPrint;
		}
		else{
			$html.= $hookmanager->resPrint;
		}
	}

	$html.= '</div>
			</div>
		  </section>';

	return $html;
}


/**
 * Affiche la liste des sessions du stagiaire courant agefodd
 *
 * route => agefodd_trainee_session_list
 *
 * @return string
 */
function getPageViewTraineeSessionListExternalAccess()
{
    global $langs,$db,$user, $conf;

    $context = Context::getInstance();

    if (!validateTrainee($context, true)) return '';

    $trainee = new Agefodd_stagiaire($db);
    if($trainee->fetch_by_contact($user->contactid) <= 0)
    {
        // normalement ne devrait jamais arriver vu que ce test est effectué par validateTrainee()
        $context->setError($langs->trans('ErrorFechingTrainee'));
    }


    $agsession = new Agsession($db);

    if (!empty($trainee->id)){
        $agsession->fetch_session_per_trainee($trainee->id);
    }

    $out = '<!-- getPageViewTraineeSessionListExternalAccess -->';
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
        $out.= ' <th class="text-center" >'.$langs->trans('Status').'</th>';
        $out.= ' <th class="text-center" >'.$langs->trans('AgfDuree').'</th>';
        $out.= ' <th class="text-center" >'.$langs->trans('AgfConsumedTime').'</th>';
        $out.= ' <th class="text-center" >'.$langs->trans('AgfDureeSolde').'</th>';
       // $out.= ' <th class="text-center" ></th>';
        $out.= '</tr>';

        $out.= '</thead>';

        $out.= '<tbody>';

        /** @var AgfSessionLine $item */
        foreach ($agsession->lines as &$item)
        {

            $out.= '<tr>';
            $out.= ' <td data-order="'.$item->sessionref.'" data-search="'.$item->sessionref.'"  ><a href="'.$context->getRootUrl('agefodd_trainee_session_card', '&sessid='.$item->rowid).'">'.$item->sessionref.'</a></td>';
            $out.= ' <td data-order="'.$item->intitule.'" data-search="'.$item->intitule.'"  >'.$item->intitule.'</td>';
            $out.= ' <td data-order="'.$item->dated.'" data-search="'.dol_print_date($item->dated, '%d/%m/%Y').'" >'.dol_print_date($item->dated, '%d/%m/%Y').'</td>';
            $out.= ' <td data-order="'.$item->datef.'" data-search="'.dol_print_date($item->datef, '%d/%m/%Y').'" >'.dol_print_date($item->datef, '%d/%m/%Y').'</td>';


            $statut = Agsession::getStaticLibStatut($item->status, 0);
            $out.= ' <td class="text-center" data-search="'.$statut.'" data-order="'.$statut.'" >'.$statut.'</td>';

            $out.= ' <td class="text-center" data-order="'.$item->duree_session.'" data-session="'.$item->duree_session.'"  >'.convertHundredthHoursToReadable($item->duree_session).'</td>';

            $filters['excludeCanceled'] = true;
            $sumDureePresence = Agsession::getStaticSumDureePresence($item->rowid, $trainee->id, $filters);
            $out.= ' <td class="text-center" data-order="'.$sumDureePresence.'">'.convertHundredthHoursToReadable($sumDureePresence).'</td>';
            $solde = $item->duree_session - $sumDureePresence;
            $out.= ' <td class="text-center" data-order="'.$solde.'">'.convertHundredthHoursToReadable($solde).'</td>';

           // $out.= ' <td class="text-right" >&nbsp;</td>';

            $out.= '</tr>';
        }
        $out.= '</tbody>';

        $out.= '</table>';

        $out.= '<script type="text/javascript" >
					$(document).ready(function(){
						$("#session-list").DataTable({
							"pageLength" : '.(empty($conf->global->AGF_EA_NUMBER_OF_ELEMENTS_IN_LISTS) ? 10 : $conf->global->AGF_EA_NUMBER_OF_ELEMENTS_IN_LISTS).',
							stateSave: '.(GETPOST('save_lastsearch_values', 'none') ? 'true' : 'false').',
							"language": {
								"url": "'.$context->getRootUrl().'vendor/data-tables/french.json"
							},
							responsive: true,
							order: [[ 3, "desc" ]]
							/*columnDefs: [{
								orderable: false,
								"aTargets": [2]
							}, {
								"bSearchable": false,
								"aTargets": [-1]
							}]*/
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
	$status = GETPOST('filterStatusCode', 'none');
	if (!empty($formateur->id))
	{
		$sortorder = '';
		$sortfield = '';
		$limit = 0;
		$offset = 0;
		$filter = array();



		if(empty($status)){
			$status = 'DEFAULT';
		}

		$sql = "SELECT rowid ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_session_status_type";
		$sql .= " WHERE " . MAIN_DB_PREFIX . "agefodd_session_status_type.code = 'ARCH' ";
		$sql .= " LIMIT 1 ";

		$resql = $db->query($sql);
		if($resql)
		{
			$obj = $db->fetch_object($resql);
			if($status == 'ARCH'){
				$filter['s.status'] = $obj->rowid;
			}
			elseif($status == 'DEFAULT'){
				$filter['!s.status'] = $obj->rowid;
			}
		}

		$agsession->fetch_session_per_trainer($formateur->id, $sortorder, $sortfield, $limit, $offset, $filter);

	}

	$out = '<!-- getPageViewSessionListExternalAccess -->';
	$out.= '<section id="section-session-list"><div class="container">';


	$out.= '
		<ul class="nav nav-tabs mb-3" role="tablist">
			<li class="nav-item">
				<a class="nav-link '.($status=='DEFAULT'?'active':'').'"  href="'.$context->getRootUrl('agefodd_session_list').'" >'.$langs->trans('Sessions').'</a>
			</li>
			<li class="nav-item">
				<a class="nav-link '.($status=='ARCH'?'active':'').'"  href="'.$context->getRootUrl('agefodd_session_list', '&filterStatusCode=ARCH').'" >'.$langs->trans('ArchivedSessions').'</a>
			</li>
        </ul>
	';

	if(!empty($agsession->lines))
	{
		$out.= '<table id="session-list" class="table table-striped w-100" >';

		$out.= '<thead>';

		$out.= '<tr>';
		$out.= ' <th class="" >'.$langs->trans('Ref').'</th>';
		$out.= ' <th class="" >'.$langs->trans('AgfFormIntitule').'</th>';
		$out.= ' <th class="" >'.$langs->trans('DateStart').'</th>';
		$out.= ' <th class="" >'.$langs->trans('DateEnd').'</th>';
		$out.= ' <th class="" >'.$langs->trans('AgfParticipant').'</th>';
		$out.= ' <th class="text-center" >'.$langs->trans('AgfDureeOffPlatform').'</th>';
		$out.= ' <th class="text-center" >'.$langs->trans('AgfSessionSummaryTimeDone').'</th>';
		$out.= ' <th class="text-center" >'.$langs->trans('AgfSessionSummaryTotalLeft').'</th>';
		$out.= ' <th class="text-center" >'.$langs->trans('AgfDureeDeclared').'</th>';
		//$out.= ' <th class="text-center" >'.$langs->trans('AgfDureeSoldeTrainee').'</th>';
		$out.= ' <th class="text-center" >'.$langs->trans('Status').'</th>';
		$out.= ' <th class="text-center" >'.$langs->trans('AgfDuree').'</th>';
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
			$plus_sta='';
			$Tsearch_sta=array();
			$Tpopcontent_sta=array();
			if (!empty($stagiaires->lines))
			{
				foreach ($stagiaires->lines as &$stagiaire)
				{
					//Populate cell value and search value with only one trainee
					if (empty($stagiaire->nom) && empty($stagiaire->prenom)) continue;
					$stagiaires_str = implode(' ', array( $stagiaire->civilite, strtoupper($stagiaire->nom), ucfirst($stagiaire->prenom), ' ('.$stagiaire->socname.') '));
					$search_sta= ' '. strtoupper($stagiaire->nom) . ' ' . ucfirst($stagiaire->prenom) . ' ('.$stagiaire->socname.') ';
					if (!empty($stagiaire->tel1)) {
						$search_sta.= ' - '.$stagiaire->tel1;
					}
					if (!empty($stagiaire->tel2)) {
						$search_sta.= ' - '.$stagiaire->tel2;
					}
					if (!empty($stagiaire->email)) {
						$search_sta .= ' - ' . $stagiaire->email;
					}
					$Tsearch_sta[]=$search_sta;
					break;
				}
				if (count($stagiaires->lines)>1) {
					$Tsearch_sta=array();
					foreach ($stagiaires->lines as &$stagiaire)
					{
						//Populate cell value and "plus" data and search value with all trainees
						if (empty($stagiaire->nom) && empty($stagiaire->prenom)) continue;
						$search_sta=  ' '. strtoupper($stagiaire->nom) . ' ' . ucfirst($stagiaire->prenom) . ' ('.$stagiaire->socname.')';
						if (!empty($stagiaire->tel1)) {
							$search_sta.= ' - '.$stagiaire->tel1;
						}
						if (!empty($stagiaire->tel2)) {
							$search_sta.= ' - '.$stagiaire->tel2;
						}
						if (!empty($stagiaire->email)) {
							$search_sta .= ' - ' . $stagiaire->email;
						}
						$Tsearch_sta[]=$search_sta;
						$Tpopcontent_sta[]=' '.$stagiaire->civilite. ' '.$search_sta;
					}
					$plus_sta = ' <span data-toggle="popover" title="'.$langs->trans('AgfParticipants').'" data-content="'.implode(' <br /> ',$Tpopcontent_sta).'"><i class="fa fa-plus hours-detail"></i></span>';
				}

			}

			$out.= '<tr>';
			$out.= ' <td data-order="'.$item->sessionref.'" data-search="'.$item->sessionref.'"  ><a href="'.$context->getRootUrl('agefodd_session_card', '&sessid='.$item->rowid).'">'.$item->sessionref.'</a></td>';
			$out.= ' <td data-order="'.$item->intitule.'" data-search="'.$item->intitule.'"  >'.$item->intitule.'</td>';
			$out.= ' <td data-order="'.$item->dated.'" data-search="'.dol_print_date($item->dated, '%d/%m/%Y').'" >'.dol_print_date($item->dated, '%d/%m/%Y').'</td>';
			$out.= ' <td data-order="'.$item->datef.'" data-search="'.dol_print_date($item->datef, '%d/%m/%Y').'" >'.dol_print_date($item->datef, '%d/%m/%Y').'</td>';
			$out.= ' <td data-search="'.implode(" ",$Tsearch_sta).'"  >'.$stagiaires_str.$plus_sta.'</td>';

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

			$filters['!calendrier_type'] = 'AGF_TYPE_PLATF';
			$duree_offPlatform = Agsession::getStaticSumTimeSlot($item->rowid, null, $filters);
			$out.= ' <td class="text-center" data-order="'.$duree_offPlatform.'">'.$duree_offPlatform.'</td>';


			$duree_declared = Agsession::getStaticSumDureePresence($item->rowid, null, $filters);
			if (!empty($duree_declared) && !empty($conf->global->AGF_EA_ECLATE_HEURES_PAR_TYPE))
			{
			    $duree_exploded = Agsession::getStaticSumExplodeDureePresence($item->rowid);

			    if (count($duree_exploded))
			    {


			        $popcontent = '';
			        foreach ($duree_exploded as $label => $hours)
			        {
			            $popcontent.= dol_escape_htmltag($label.' : '.$hours.'<br>', 1);
			        }
			        $plus = ' <span data-toggle="popover" title="'.$langs->trans('AgfDetailHeure').'" data-content="'.$popcontent.'"><i class="fa fa-plus hours-detail"></i></span>';
			    }
			}
			else
			{
			    $plus = '';
			}
			$agefodd_sesscalendar = new Agefodd_sesscalendar($db);
			$agefodd_sesscalendar->fetch_all($item->rowid);
			$duree_timeDone=0;
			foreach ($agefodd_sesscalendar->lines as $agf_calendrier)
			{
				if ($agf_calendrier->status == Agefodd_sesscalendar::STATUS_FINISH) {
					$duree_timeDone += ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
				}
			}
			$soldeTotal = ((int) $item->duree_session)-$duree_timeDone;
			$out.= ' <td class="text-center" data-order="'.$duree_timeDone.'">'.$duree_timeDone.'</td>';
			$out.= ' <td class="text-center" data-order="'.$soldeTotal.'">'.$soldeTotal.'</td>';
			$out.= ' <td class="text-center" data-order="'.$duree_declared.'">'.$duree_declared.$plus.'</td>';
			//$solde = $duree_offPlatform - $duree_declared;
			//$out.= ' <td class="text-center" data-order="'.$solde.'">'.$solde.'</td>';
			$statut = Agsession::getStaticLibStatut($item->status, 0);
			$out.= ' <td class="text-center" data-search="'.$statut.'" data-order="'.$statut.'" >'.$statut.'</td>';

			$out.= ' <td class="text-center" data-order="'.$item->duree_session.'" data-session="'.$item->duree_session.'"  >'.$item->duree_session.'</td>';

			$out.= ' <td class="text-right" >&nbsp;</td>';

			$out.= '</tr>';
		}
		$out.= '</tbody>';

		$out.= '</table>';

		$out.= '<script type="text/javascript" >
					$(document).ready(function(){
						$("#session-list").DataTable({
							"pageLength" : '.(empty($conf->global->AGF_EA_NUMBER_OF_ELEMENTS_IN_LISTS) ? 10 : $conf->global->AGF_EA_NUMBER_OF_ELEMENTS_IN_LISTS).',
							stateSave: '.(GETPOST('save_lastsearch_values', 'none') ? 'true' : 'false').',
							"language": {
								"url": "'.$context->getRootUrl().'vendor/data-tables/french.json"
							},
							responsive: true,
							order: [[ '.(!isset($conf->global->AGF_EA_SORT_FIELDS_IN_SESSION_LISTS) ? 3 : $conf->global->AGF_EA_SORT_FIELDS_IN_SESSION_LISTS).', '.(empty($conf->global->AGF_EA_SORT_ORDER_IN_SESSION_LISTS) ? "\"desc\"" : "\"".$conf->global->AGF_EA_SORT_ORDER_IN_SESSION_LISTS."\"").' ]],
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
 * @param Agefodd_teacher $trainer
 * @return string
 */
function getPageViewSessionCardExternalAccess(&$agsession, &$trainer)
{
	global $db, $langs, $user, $hookmanager;

	$context = Context::getInstance();
	if (!validateFormateur($context)) return '';

	$langs->load('agfexternalaccess@agefodd');

	$tab = GETPOST('tab', 'none');
	$agf_calendrier_formateur = new Agefoddsessionformateurcalendrier($db);
	$agf_calendrier_formateur->fetchAllBy(array('trainer.rowid'=>$trainer->id, 'sf.fk_session'=>$agsession->id), '');

	//Calcul du total d'heures restantes sur la session
	$duree_timeDone = 0;
	$duree_timeRest = 0;
    $agefodd_sesscalendar = new Agefodd_sesscalendar ($db);
    $agefodd_sesscalendar->fetch_all($agsession->id);
    foreach ($agefodd_sesscalendar->lines as $agf_calendrier)
    {
	    if ($agf_calendrier->status == Agefodd_sesscalendar::STATUS_FINISH) {
            $duree_timeDone += ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
        }
    }
    $duree_timeRest = $agsession->duree_session - $duree_timeDone;

	$out = '<!-- getPageViewSessionCardExternalAccessTrainer -->';
	$out.= '<section id="section-session-card" class="py-5"><div class="container">';

	$url_add = '';
	if (!empty($user->rights->agefodd->external_trainer_write) && ($duree_timeRest > 0)) $url_add = $context->getRootUrl('agefodd_session_card_time_slot', '&sessid='.$agsession->id.'&slotid=0');

	$out.= getEaNavbar($context->getRootUrl('agefodd_session_list', '&save_lastsearch_values=1'), $url_add);

	$out.= '
		<blockquote class="blockquote">
			<p>'.$agsession->ref.'</p>
			<p>'.$agsession->trainer_ext_information.'</p>
		</blockquote>
	';

	$tabTitle= '
		<ul class="nav nav-tabs mb-3" id="section-session-card-calendrier-formateur-tab" role="tablist">
			<li class="nav-item">
				<a class="nav-link'.((empty($tab) || $tab == 'calendrier-info-tab') ? ' active' : '').'" id="calendrier-info-tab" data-toggle="tab" href="#nav-calendrier-info" role="tab" aria-controls="calendrier-info" aria-selected="'.((empty($tab) || $tab == 'calendrier-info-tab') ? 'true' : 'false').'">'.$langs->trans('AgfCrenaux').'</a>
			</li>
			<li class="nav-item">
				<a class="nav-link'.(($tab == 'calendrier-summary-tab') ? ' active' : '').'" id="calendrier-summary-tab" data-toggle="tab" href="#nav-calendrier-summary" role="tab" aria-controls="calendrier-summary" aria-selected="'.(($tab == 'calendrier-summary-tab') ? 'true' : 'false').'">'.$langs->trans('AgfRecap').'</a>
			</li>
            <li class="nav-item">
				<a class="nav-link'.(($tab == 'session-files-tab') ? ' active' : '').'" id="session-files-tab" data-toggle="tab" href="#nav-session-files" role="tab" aria-controls="session-files" aria-selected="'.(($tab == 'session-files-tab') ? 'true' : 'false').'">'.$langs->trans('Documents').'</a>
			</li>
	';


	$parameters=array(
		'agsession' =>& $agsession,
		'trainer' =>& $trainer,
		'tab' =>& $tab
	);
	$reshook=$hookmanager->executeHooks('agf_getPageViewSessionCardExternalAccess_tab', $parameters, $agf_calendrier_formateur);

	if (!empty($reshook)){
		// override full output
		$tabTitle = $hookmanager->resPrint;
	}
	else{
		$tabTitle.= $hookmanager->resPrint;
		$tabTitle.= '</ul>';
	}

	$out.= $tabTitle;

	$tabContent= '
		<div class="tab-content" id="section-session-card-calendrier-formateur-tab-tabContent">
			<div class="tab-pane fade'.((empty($tab) || $tab == 'calendrier-info-tab') ? ' show active' : '').'" id="nav-calendrier-info" role="tabpanel" aria-labelledby="nav-calendrier-info-tab">'.getPageViewSessionCardExternalAccess_creneaux($agsession, $trainer, $agf_calendrier_formateur).'</div>
			<div class="tab-pane fade'.(($tab == 'calendrier-summary-tab') ? ' show active' : '').'" id="nav-calendrier-summary" role="tabpanel" aria-labelledby="nav-calendrier-summary-tab">'.getPageViewSessionCardExternalAccess_summary($agsession, $trainer, $agf_calendrier_formateur).'</div>
            <div class="tab-pane fade'.(($tab == 'session-files-tab') ? ' show active' : '').'" id="nav-session-files" role="tabpanel" aria-labelledby="nav-session-files-tab">'.getPageViewSessionCardExternalAccess_files($agsession, $trainer).'</div>

	';


	$parameters=array(
		'agsession' =>& $agsession,
		'trainer' =>& $trainer,
		'tab' =>& $tab
	);
	$reshook=$hookmanager->executeHooks('agf_getPageViewSessionCardExternalAccess_tab_content', $parameters, $agf_calendrier_formateur);

	if (!empty($reshook)){
		// override full output
		$tabContent = $hookmanager->resPrint;
	}
	else{
		$tabContent.= $hookmanager->resPrint.'</div>';
	}

	$out.= $tabContent;



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
 * Génère les écrans liés à une session
 *
 * route => agefodd_session_card
 *
 * @return string
 */
function getPageViewTraineeSessionCardExternalAccess()
{
    global $db, $langs, $user, $hookmanager;

    $context = Context::getInstance();
    if (!validateTrainee($context)) return '';

    $agsession = new Agsession($db);
    if ($agsession->fetch(GETPOST('sessid', 'none')) > 0) // Vérification que la session existe
    {
        $trainee = new Agefodd_stagiaire($db);
        if($trainee->fetch_by_contact($user->contactid) <= 0){
            // normalement ne devrait jamais arriver vu que ce test est effectué par validateTrainee()
            $context->setError($langs->trans('ErrorFechingTrainee'));
        }
        else{
            $context->setControllerFound();

            // LOAD CALENDARS
            dol_include_once('/agefodd/class/agefodd_session_calendrier.class.php');
            $calendrier = new Agefodd_sesscalendar($db);
            $calendrier->fetch_all($agsession->id);

            $tab = GETPOST('tab', 'none');

            $out = '<!-- getPageViewSessionCardExternalAccessTrainee -->';
            $out.= '<section id="section-session-card" class="py-5"><div class="container">';

            $out.= getEaNavbar($context->getRootUrl('agefodd_trainee_session_list', '&save_lastsearch_values=1'));

            $out.= '<h4>'.$agsession->ref.' : '.$agsession->formintitule.'</h4>';

            $sumDureePresence = $agsession->getSumDureePresence($trainee->id);
            $out.= '<p>';
            $out.= $langs->trans('AgfSessionTime').' : '.convertHundredthHoursToReadable($agsession->duree_session);
            $out.= ' &nbsp;&nbsp; '.$langs->trans('AgfConsumedTime').' : '.convertHundredthHoursToReadable($sumDureePresence);
            $out.= ' &nbsp;&nbsp; '.$langs->trans('AgfSessionBalanceOfHours').' : '.convertHundredthHoursToReadable($agsession->duree_session - $sumDureePresence);
            $out.= '</p>';

            $tabTitle= '
    <ul class="nav nav-tabs mb-3" id="section-session-card-calendrier-formateur-tab" role="tablist">
        <li class="nav-item">
            <a class="nav-link'.((empty($tab) || $tab == 'calendrier-info-tab') ? ' active' : '').'" id="calendrier-info-tab" data-toggle="tab" href="#nav-calendrier-info" role="tab" aria-controls="calendrier-info" aria-selected="'.((empty($tab) || $tab == 'calendrier-info-tab') ? 'true' : 'false').'"><i class="fa fa-calendar" aria-hidden="true"></i> '.$langs->trans('AgfTraineeCreaneaux').'</a>
        </li>
        <li class="nav-item">
            <a class="nav-link'.($tab == 'download-tab' ? ' active' : '').'" id="download-tab" data-toggle="tab" href="#nav-download" role="tab" aria-controls="download" aria-selected="'.($tab == 'download-tab' ? 'true' : 'false').'"><i class="fa fa-download" aria-hidden="true"></i> '.$langs->trans('AgfTraineeSessionDownload').'</a>
        </li>
';

            $parameters=array(
                'agsession' =>& $agsession,
                'trainee' =>& $trainee,
                'tab' =>& $tab
            );
            $reshook=$hookmanager->executeHooks('agf_getPageViewTraineeSessionCardExternalAccess_tab', $parameters, $calendrier);

            if (!empty($reshook)){
                // override full output
                $tabTitle = $hookmanager->resPrint;
            }
            else{
                $tabTitle.= $hookmanager->resPrint;
                $tabTitle.= '</ul>';
            }

            $out.= $tabTitle;

            $tabContent= '
    <div class="tab-content" id="section-session-card-calendrier-formateur-tab-tabContent">
        <div class="tab-pane fade'.((empty($tab) || $tab == 'calendrier-info-tab') ? ' show active' : '').'" id="nav-calendrier-info" role="tabpanel" aria-labelledby="nav-calendrier-info-tab">'.getPageViewTraineeSessionCardExternalAccess_creneaux($agsession, $trainee, $calendrier).'</div>
        <div class="tab-pane fade'.($tab == 'download-tab' ? ' show active' : '').'" id="nav-download" role="tabpanel" aria-labelledby="nav-download-tab">'.getPageViewTraineeSessionCardExternalAccess_downloads($agsession, $trainee).'</div>
';

            $parameters=array(
                'agsession' =>& $agsession,
                'trainee' =>& $trainee,
                'tab' =>& $tab
            );
            $reshook=$hookmanager->executeHooks('agf_getPageViewTraineeSessionCardExternalAccess_tab_content', $parameters, $agf_calendrier_formateur);

            if (!empty($reshook)){
                // override full output
                $tabContent = $hookmanager->resPrint;
            }
            else{
                $tabContent.= $hookmanager->resPrint.'</div>';
            }

            $out.= $tabContent;

            $out.= '</div>';

            $parameters=array(
                'agsession' =>& $agsession,
                'trainee' =>& $trainee
            );
            $reshook=$hookmanager->executeHooks('agf_getPageViewTraineeSessionCardExternalAccess', $parameters, $calendrier);

            if (!empty($reshook)){
                // override full output
                $out = $hookmanager->resPrint;
            }
            else{
                $out.= $hookmanager->resPrint;
                $out.= '</section>';
            }
        }
    }

    return $out;
}

/**
 * Affiche les téléchargement possible pour le stagiaire
 *
 * @param Agsession $agsession
 * @param Agefodd_stagiaire $trainee
 * @return string
 */
function getPageViewTraineeSessionCardExternalAccess_downloads($agsession, $trainee)
{
    global $langs, $conf, $db, $user;

	$langs->load('agfexternalaccess@agefodd');

    $context = Context::getInstance();

    $out = '<!-- getPageViewTraineeSessionCardExternalAccess_downloads -->'."\n";

	$out.= '<div class="row">';
	$out.= '<div class="col-md-6">';
	$out.= '<h5>'.$langs->trans('AgfDownloadFilesTrainee').'</h5>';

    $downloadUrl = $context->getRootUrl().'script/interface.php?action=downloadAgefoddTrainneeDoc&session='.$agsession->id.'&model=';
    $attestationendtraining_trainee = getAgefoddTraineeDocumentPath($agsession, $trainee, 'attestationendtraining_trainee');
    $downloadLink = '';
    if(!empty($attestationendtraining_trainee) && file_exists($attestationendtraining_trainee)){
        $downloadLink = $downloadUrl.'attestationendtraining_trainee';
    }
    $out.= getAgefoddDownloadTpl($langs->trans('AgfAttestationEndTraining'), $langs->trans('AgfDownloadDescAttestationEndTraining'), $downloadLink);


    $attestation_trainee = getAgefoddTraineeDocumentPath($agsession, $trainee, 'attestation_trainee');
    $downloadLink = '';
    if(!empty($attestation_trainee) && file_exists($attestation_trainee)){
        $downloadLink = $downloadUrl.'attestation_trainee';
    }
    $out.= getAgefoddDownloadTpl($langs->trans('AgfAttestationTraining'), $langs->trans('AgfDownloadDescAttestationTraining'), $downloadLink);

	if ($user->rights->agefodd->external_access_link_attatchement) {
		require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
		$link = new Link($db);
		$links = array();
		$link->fetchAll($links, $agsession->element, $agsession->id, '', '');
		if (is_array($links) && count($links)>0) {
			$out .= '				<br><br><h5>' . $langs->trans('AgfLinksExternal') . '</h5>';
			foreach ($links as $link) {
				$out .= '<a data-ajax="false" href="' . $link->url . '" target="_blank"><i class="fa fa-link"></i> ';
				$out .= dol_escape_htmltag($link->label).'</a><br/>';
			}
		}
	}

	$out.= '</div>';
	$out.= '<div class="col-md-6">';

	$upload_dir = $conf->agefodd->dir_output . "/" .$agsession->id;
	$filearray=dol_dir_list($upload_dir, "files", 0, '', '', '', SORT_ASC, 1);

	if(count($filearray)>0)
	{
		$out_file= '<h5>'.$langs->trans('AgfDownloadDocumentsTrainee').'</h5>';
		$fileAvailable=false;

		require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';

		foreach ($filearray as $file)
		{
			$filename=$file['name'];
			$ecmfile = new ECMFiles($db);
			$result=$ecmfile->fetch(0, '', dol_osencode("agefodd/" .$agsession->id . "/" .$filename));
			if ($result > 0) {
				if (!empty($ecmfile->share)) {
					$dowloadUrl = $context->getRootUrl().'script/interface.php?action=downloadSessionAttachement&hashp='.$ecmfile->share;
					$out_file.=getAgefoddDownloadTpl($filename, $langs->trans('Download'), $dowloadUrl, pathinfo($file['fullname'], PATHINFO_EXTENSION));
					$fileAvailable=true;
				}
			}
		}
		if ($fileAvailable) {
			$out.=$out_file;
		}
	}

	$out.= '</div>';
	$out.= '</div>';

    return $out;
}

function getAgefoddDownloadTpl($title, $desc = '', $downloadLink = '', $fileType = 'pdf', $imageUrl = false)
{
    global  $langs;
    $context = Context::getInstance();

    if(empty($imageUrl)){
    	if (file_exists($context->tplDir.'img/mime/'.$fileType.'.png')) {
		    $imageUrl = $context->getRootUrl() . '/img/mime/' . $fileType . '.png';
	    } else {
		    $imageUrl = $context->getRootUrl() . '/img/mime/file.png';
	    }
    }

    $out = '<!-- Left-aligned -->';
    $out.= '<div class="media">';
    $out.= '<div class="media-left">';
    $out.= '<img src="'.$imageUrl.'" class="media-object" style="margin: 5px 5px 5px 0;">';
    $out.= '</div>';

    if(!empty($downloadLink)){
        $out.= '<a class="media-body" href="'.$downloadLink.'" target="_blank">';
    }
    else{
        $out.= '<div class="media-body" href="'.$downloadLink.'" target="_blank">';
    }


    $out.= '<strong class="media-heading">'.$title.'</strong>';
    $out.= '<p>';
    if(empty($downloadLink)){
        $out.= ' <small>('.$langs->trans('DocumentFileNotAvailable').')</small>';
    }
    elseif(empty($desc)){
        $out.= ' <small><i class="fa fa-download" aria-hidden="true"></i> '.$langs->trans('Download').'</small>';
    }
    else{
        $out.= ' <small>'.$desc.'</small>';
    }

    $out.= '</p>';

    if(!empty($downloadLink)){
        $out.= '</a>';
    }
    else{
        $out.= '</div>';
    }

    $out.= '</div>';

    return $out;
}

/**
 * @param Agefodd_stagiaire $trainee
 * @param Agsession $agsession
 * @param $model string
 * @return bool|int|string
 */
function getAgefoddTraineeDocumentPath($agsession, $trainee, $model)
{
    global $conf, $db;
    require_once __DIR__ . '/../class/agefodd_session_stagiaire.class.php';

    if(empty($trainee->id)){
        return false;
    }
    if(empty($agsession->id)){
        return false;
    }

    // TODO : Apparement je télécharge pas le bon fichier, pas cool
    $session_stagiaire = new Agefodd_session_stagiaire($db);
    $resFetchSessStag = $session_stagiaire->fetch_by_trainee($agsession->id, $trainee->id);

    $file = false;
    if ($model == 'attestation_trainee' && $session_stagiaire->id > 0) {
        $file = $conf->agefodd->dir_output . '/' . 'attestation_trainee_' . $session_stagiaire->id . '.pdf';
    } elseif ($model == 'attestationendtraining_trainee' && $session_stagiaire->id > 0) {
        $file = $conf->agefodd->dir_output . '/' . 'attestationendtraining_trainee_' . $session_stagiaire->id . '.pdf';
    }

    return $file;
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
	global $langs, $user, $hookmanager, $conf;

	$context = Context::getInstance();

	if (!validateFormateur($context)) return '';

	$out = '<!-- getPageViewSessionCardExternalAccess_creneaux -->';
	$out.= '<table id="session-list" class="table table-striped w-100" >';

	$out.= '<thead>';

	$out.= '<tr>';
	// $out.= ' <th class="text-center" >'.$langs->trans('ID').'</th>';
	$out.= ' <th class="" >'.$langs->trans('AgfDateSession').'</th>';
	$out.= ' <th class="" >'.$langs->trans('AgfPeriodTimeB').'</th>';
	$out.= ' <th class="" >'.$langs->trans('AgfPeriodTimeE').'</th>';
	$out.= ' <th class="text-center" >'.$langs->trans('AgfDuree').'</th>';
	$out.= ' <th class="text-center" >'.$langs->trans('Status').'</th>';
	$out.= ' <th class="text-center" >'.$langs->trans('Type').'</th>';


    // Fields from hook
    $parameters=array(
        'agsession' =>& $agsession,
        'trainer' =>& $trainer
    );
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters, $agf_calendrier_formateur);    // Note that $action and $object may have been modified by hook
    $out.= $hookmanager->resPrint;


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
		// $out.= ' <td class="text-center"><a href="'.$url.'&action=view">'.$item->id.'</a></td>';
		$date_session = dol_print_date($item->date_session, '%d/%m/%Y');
		$out.= ' <td data-order="'.$item->date_session.'" data-search="'.$date_session.'" ><a href="'.$url.'&action=view">'.$date_session.'</a></td>';

		$heured = dol_print_date($item->heured, '%H:%M');
		$out.= ' <td data-order="'.$item->heured.'" data-search="'.$heured.'" ><a href="'.$url.'&action=view">'.$heured.'</a></td>';
		$heuref = dol_print_date($item->heuref, '%H:%M');
		$out.= ' <td data-order="'.$item->heuref.'" data-search="'.$heuref.'" ><a href="'.$url.'&action=view">'.$heuref.'</a></td>';
		$duree = ($item->heuref - $item->heured) / 60 / 60;
		$out.= ' <td class="text-center" data-order="'.$duree.'" data-search="'.$duree.'" ><a href="'.$url.'&action=view">'.$duree.'</a></td>';
		if ($item->status == Agefoddsessionformateurcalendrier::STATUS_DRAFT) $statut = $langs->trans('AgfStatusCalendar_previsionnel');
		else $statut = Agefoddsessionformateurcalendrier::getStaticLibStatut($item->status, 0);
		$out.= ' <td class="text-center" data-order="'.$statut.'" data-search="'.$statut.'" ><a href="'.$url.'&action=view">'.$statut.'</a></td>';

		$calendrier_type_label = !empty($agf_calendrier) ? $agf_calendrier->calendrier_type_label : '';
		$out.= ' <td class="text-center" data-order="'.$calendrier_type_label.'" data-search="'.$calendrier_type_label.'" ><a href="'.$url.'&action=view">'.$calendrier_type_label.'</a></td>';



        // Fields from hook
        $parameters=array(
            'agsession' =>& $agsession,
            'trainer' =>& $trainer,
            'agf_calendrier_formateur' =>& $agf_calendrier_formateur
        );
        $reshook=$hookmanager->executeHooks('printFieldListValue',$parameters, $item);    // Note that $action and $object may have been modified by hook
        $out.= $hookmanager->resPrint;


		//$edit = '<a href="'.$url.'"><i class="fa fa-edit"></a></i>';
		$delete = '<i class="fa fa-trash" ></i>  Supprimer';
		$out.= ' <td class="text-center" >';

		$out.= '<div class="btn-group" role="group" aria-label="Button group with nested dropdown">
		<a  class="btn btn-xs btn-secondary" href="'.$url.'"><i class="fa fa-edit"></i></a>

		<div class="btn-group" role="group">
		<button id="btnGroupDrop1" type="button" class="btn btn-xs btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
		<div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
        <a  class="dropdown-item" href="'.$url.'"><i class="fa fa-edit"> Editer</i></a>';

		if ((empty($agf_calendrier) || empty($agf_calendrier->billed)) && !empty($user->rights->agefodd->external_trainer_time_slot_delete))
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
						"pageLength" : '.(empty($conf->global->AGF_EA_NUMBER_OF_ELEMENTS_IN_LISTS) ? 10 : $conf->global->AGF_EA_NUMBER_OF_ELEMENTS_IN_LISTS).',
						stateSave: '.(GETPOST('save_lastsearch_values', 'none') ? 'true' : 'false').',
						"language": {
							"url": "'.$context->getRootUrl().'vendor/data-tables/french.json"
						},
						responsive: true,
						order: [[ 1, '.(empty($conf->global->AGF_EA_SORT_ORDER_IN_CRENEAUX_LISTS) ? "\"desc\"" : "\"".$conf->global->AGF_EA_SORT_ORDER_IN_CRENEAUX_LISTS."\"").' ]],
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



/**
 * Affiche les créneaux du calendrier stagiaire
 *
 * @param Agsession $agsession
 * @param Agefodd_stagiaire $trainee
 * @param Agefoddcalendrier $agf_calendrier
 * @return string
 */
function getPageViewTraineeSessionCardExternalAccess_creneaux(&$agsession, &$trainee, &$agf_calendrier)
{
    global $langs, $hookmanager, $db, $conf;

    $context = Context::getInstance();

    $slotid = GETPOST('slotid', 'int');

    if (!validateTrainee($context)) return '';

    $out = '<!-- getPageViewTraineeSessionCardExternalAccess_creneaux -->';
    $out.= '<table id="session-list" class="table table-striped w-100" >';

    $out.= '<thead>';

    $out.= '<tr>';
    $out.= ' <th class="" >'.$langs->trans('AgfDateSession').'</th>';
    $out.= ' <th class="" >'.$langs->trans('AgfPeriodTimeB').'</th>';
    $out.= ' <th class="" >'.$langs->trans('AgfPeriodTimeE').'</th>';
    $out.= ' <th class="text-center" >'.$langs->trans('AgfDuree').'</th>';
    $out.= ' <th class="text-center" >'.$langs->trans('Status').'</th>';
    $out.= ' <th class="text-center" >'.$langs->trans('YourPresence').'</th>';


    // Fields from hook
    $parameters=array(
        'agsession' =>& $agsession,
        'trainee' =>& $trainee
    );
    $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters, $agf_calendrier);    // Note that $action and $object may have been modified by hook
    $out.= $hookmanager->resPrint;


    $out.= '</tr>';

    $out.= '<tbody>';
    foreach ($agf_calendrier->lines as &$item)
    {

        $rowclass = '';
        if(intval($item->id) == intval($slotid)){
            $rowclass = 'table-info';
        }

        $out.= '<tr id="slotid'.$item->id.'" class="'.$rowclass.'" >';

        $date_session = dol_print_date($item->date_session, '%d/%m/%Y');
        $out.= ' <td data-order="'.$item->date_session.'" data-search="'.$date_session.'" >'.$date_session.'</td>';

        $heured = dol_print_date($item->heured, '%H:%M');
        $out.= ' <td data-order="'.$item->heured.'" data-search="'.$heured.'" >'.$heured.'</td>';
        $heuref = dol_print_date($item->heuref, '%H:%M');
        $out.= ' <td data-order="'.$item->heuref.'" data-search="'.$heuref.'" >'.$heuref.'</td>';
        $duree = ($item->heuref - $item->heured) / 60 / 60;
        $out.= ' <td class="text-center" data-order="'.$duree.'" data-search="'.$duree.'" >'.convertHundredthHoursToReadable($duree).'</td>';
        $statut = Agefoddsessionformateurcalendrier::getStaticLibStatut($item->status, 0);
        $out.= ' <td class="text-center" data-order="'.$statut.'" data-search="'.$statut.'" >'.$statut.'</td>';

        // get hours
        $stagiaireheures = new Agefoddsessionstagiaireheures($db);
        $stagiaireheures->fetchAllBy($item->id, 'fk_calendrier');
        $heures = 0;
        $plannedAbsence = 0;
        if(!empty($stagiaireheures->lines)){
            foreach ($stagiaireheures->lines as $line){
                if($line->fk_stagiaire == $trainee->id){
                    $heures = doubleval($line->heures);
                    $plannedAbsence = intval($line->planned_absence);
                }
            }
        }

        $out.= ' <td class="text-center"  >';


        $heuresLabel = '';
        if(!empty($heures)){
            $heuresLabel =  convertHundredthHoursToReadable($heures);
        }


        if (($item->status == Agefoddsessionformateurcalendrier::STATUS_CONFIRMED
            || $item->status == Agefoddsessionformateurcalendrier::STATUS_FINISH
            || ( $item->status == Agefoddsessionformateurcalendrier::STATUS_DRAFT && $item->heuref > time())
            )
        )
        {
            $out.= '<div class="btn-group">';

            if(!empty($plannedAbsence)){
                $class= 'btn btn-info btn-xs';
                $out.= '<button type="button" disabled class="btn btn-info btn-xs" ><i class="fa fa-calendar-times-o" aria-hidden="true"></i> '.$langs->trans('AgfTraineePlannedAbsence').'</span>';
            }
            elseif(empty($heures) && empty($plannedAbsence) && $item->heuref < time()){
                $class= 'btn btn-danger btn-xs';
                $out.= '<button type="button" disabled class="btn btn-danger btn-xs" ><i class="fa fa-user-times" aria-hidden="true"></i> '.$langs->trans('AgfTraineeMissing').'</span>';
            }
            elseif($heures < $duree && $item->heuref < time()){
                $class= 'btn btn-warning btn-xs';
                $out.= '<button type="button" disabled class="btn btn-warning btn-xs" ><i class="fa fa-user-times"></i> '.$langs->trans('AgfTraineePartialyPresent').' : '.$heuresLabel.'</span>';
            }
            elseif($item->heuref < time()){
                $class= 'btn btn-success btn-xs';
                $out.= '<button type="button" disabled class="btn btn-success btn-xs" ><i class="fa fa-check"></i> '.$langs->trans('AgfTraineePresent').'</span>';
            }
            else{
                $out.= '<button type="button" disabled class="btn btn-primary btn-xs" ><i class="fa fa-calendar-check-o"></i> '.$langs->trans('AgfTraineePlanedPresent').'</span>';
                $class= 'btn btn-primary btn-xs';
            }

            if(traineeCanChangeAbsenceStatus($item->heured))
            {
                $out.= '<button type="button" class="'.$class.' dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="caret"></span></button>';
                $out.= '<ul class="dropdown-menu">';
                $out.= '<li>';
                $url = $context->getRootUrl('agefodd_trainee_session_card').'&sessid='.$agsession->id.'&slotid='.$item->id.'&save_lastsearch_values=1';
                if(!empty($plannedAbsence)){
                    $out.= '<a href="'.$url.'&action=setplannedAbsence&plannedAbsence=present#slotid'.$item->id.'" >'.$langs->trans('AgfSetTrainneePresent').'</a>';
                }
                else{
                    $out.= '<a href="'.$url.'&action=setplannedAbsence&plannedAbsence=missing#slotid'.$item->id.'" >'.$langs->trans('AgfSetTrainneeMissing').'</a>';
                }
                $out.= '</li>';
            }
            $out.='</ul>';
            $out.= '</div>';

        }



        $out.='</td>';

        // Fields from hook
        $parameters=array(
            'agsession' =>& $agsession,
            'trainee' =>& $trainee,
            'agf_calendrier' =>& $agf_calendrier
        );
        $reshook=$hookmanager->executeHooks('printFieldListValue',$parameters, $item);    // Note that $action and $object may have been modified by hook
        $out.= $hookmanager->resPrint;




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
						"pageLength" : '.(empty($conf->global->AGF_EA_NUMBER_OF_ELEMENTS_IN_LISTS) ? 10 : $conf->global->AGF_EA_NUMBER_OF_ELEMENTS_IN_LISTS).',
						stateSave: '.(GETPOST('save_lastsearch_values', 'none') ? 'true' : 'false').',
						"language": {
							"url": "'.$context->getRootUrl().'vendor/data-tables/french.json"
						},
						responsive: true,
						pageLength: 100,
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
        'trainee' =>& $trainee
    );
    $reshook=$hookmanager->executeHooks('agf_getPageViewTraineeSessionCardExternalAccess_creneaux', $parameters, $agf_calendrier);

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
	$duree_timeDone = 0;
	$duree_timeMissing = 0;
	$duree_timeConfirm = 0;

	// somme des heures programmées pour le formateur excluant les horaires annulés et les heures de type "plateforme"
	foreach ($agf_calendrier_formateur->lines as &$line)
	{
	    $TCal = _getCalendrierFromCalendrierFormateur($line, true, true);
	    if ((int) $line->status == Agefoddsessionformateurcalendrier::STATUS_CANCELED || $TCal[0]->calendrier_type == 'AGF_TYPE_PLATF') continue;
		$duree_scheduled += ($line->heuref - $line->heured) / 60 / 60;
	}

	foreach ($agefodd_sesscalendar->lines as &$agf_calendrier)
	{
		/** @var Agefodd_sesscalendar $agf_calendrier */
	    $tmparr = $agf_calendrier->getSumDureePresence();
	    $duree_declared = $tmparr[0];
	    $duree_max = $tmparr[1];

	    if ($agf_calendrier->calendrier_type !== 'AGF_TYPE_PLATF')  {
	    	$duree_scheduled_total += ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
	    }

		if ($agf_calendrier->status == Agefodd_sesscalendar::STATUS_CONFIRMED)
		{
			$duree_presence_comptabilise += ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
			$duree_presence_max_comptabilise += ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
			$duree_timeConfirm += ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
		}
		else if ($agf_calendrier->status == Agefodd_sesscalendar::STATUS_CANCELED)
		{
			$duree_presence_comptabilise_cancel += ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
		}
		else if($agf_calendrier->status == Agefodd_sesscalendar::STATUS_MISSING) {
			$duree_timeMissing += ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
		}
		else if ($agf_calendrier->status == Agefodd_sesscalendar::STATUS_FINISH) {
			$duree_timeDone += ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
			$duree_presence_max_comptabilise += ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
		}
		else $duree_presence_draft += $duree_declared;
	}

	if ($agsession->duree_session > 0) $tx_assi = 100 - ($duree_timeMissing * 100 / $agsession->duree_session);
	else $tx_assi = 0;

	$out = '';

	$out.= '<!-- getPageViewSessionCardExternalAccess_summary -->
		<div class="container px-0">
			<h5>'.$langs->trans('AgfSessionSummary', $date_deb, $date_fin).'</h5>
			<div class="panel panel-default">
				<div class="panel-body">
					<div class="row clearfix">
							<div class="col-md-4 text-center">
								<span>'.$langs->trans('AgfDuree').'</span><br/>
								<strong id="AgfSessionSummaryTotalHours">'.$langs->trans('AgfHours', price($agsession->duree_session, 0, '', 1, -1, 2)).'</strong>
							</div>
							<div class="col-md-4 text-center">
								<span>'.$langs->trans('AgfSessionSummaryTimeDone').'</span><br/>
								<strong id="AgfSessionSummaryTotalHours">'.$langs->trans('AgfHours', price($duree_timeDone, 0, '', 1, -1, 2)).'</strong>
							</div>
							<div class="col-md-4 text-center">
								<span>'.$langs->trans('AgfSessionSummaryTotalLeft').'</span><br/>
								<strong id="AgfSessionSummaryTotalHours">'.$langs->trans('AgfHours', price($agsession->duree_session - $duree_timeDone, 0, '', 1, -1, 2)).'</strong>
							</div>
					</div>
					<div class="accordion col-md text-center" id="accordionDetail">
						<a class="btn btn-primary" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
				          <i class="btn-primary fa fa-plus-circle"></i><span class="btn-primary d-none d-sm-inline">&nbsp;'.$langs->trans('AgfDetailHeure').'</span>
				        </a>
						<div class="row clearfix collapse hidde" id="collapseOne" data-parent="#accordionDetail">
							<div class="col-md">
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
									<label class="col-md-7 px-0"  for="AgfSessionSummaryTotalLeft">'.$langs->trans('AgfSessionSummaryTotalLeftTotal').'</label>
									<span class="col-md-5 px-0" id="AgfSessionSummaryTotalLeft">'.$langs->trans('AgfHours', price($duree_scheduled_total - ($agsession->duree_session * $nbstag), 0, '', 1, -1, 2)).'</span>
								</div>
							</div>

							<div class="col-md">
								<div class="form-group">
									<label class="col-md-7 px-0"  for="AgfSessionSummaryTotalPresence">'.$langs->trans('AgfSessionSummaryTotalPresence').'</label>
									<span class="col-md-5 px-0" id="AgfSessionSummaryTotalPresence">'.$langs->trans('AgfHours', price($duree_timeConfirm, 0, '', 1, -1, 2)).'</span>
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
			</div>

			<h5>'.$langs->trans('AgfStagiaireList').'</h5>
			<div class="panel panel-default">
				<div class="panel-body py-0">';

	$out.= '<ul class="list-group list-group-flush my-0">';
	foreach ($stagiaires->lines as &$stagiaire)
	{
		if ($stagiaire->id <= 0) continue;
		$out.= '<li class="list-group-item"><i class="fa fa-'.(in_array($stagiaire->civilite, array('MME', 'MLE')) ? 'female' : 'male').'"></i><span class="ml-2">';
		$out.= strtoupper($stagiaire->nom) . ' ' . ucfirst($stagiaire->prenom) . ' ('.$stagiaire->socname.')';
		if (!empty($stagiaire->tel1)) {
			$out.= ' - '.$stagiaire->tel1;
		}
		if (!empty($stagiaire->tel2)) {
			$out.= ' - '.$stagiaire->tel2;
		}
		if (!empty($stagiaire->email)) {
			$out .= ' - ' . $stagiaire->email;
		}

		//planning par participant
        $out .= '<br><br>';
        $out.= getPlanningViewSessionTrainee($agsession, $agsession->id, $stagiaire);

		$out.= '</span></li>';

	}
	$out.= '</ul>';


	$out.= '
				</div>
			</div>
		</div>

	';

	$out.= '<script type="text/javascript" >
				$(document).ready(function(){
					$(\'[data-toggle="popover"]\').popover({html:true});
				})
			</script>';

	return $out;
}

/**
 * @param Agsession $agsession Sesison obejct
 * @param Agefodd_session_formateur $trainer Trainer object
 * @return string
 */
function getPageViewSessionCardExternalAccess_files($agsession, $trainer)
{
    global $langs, $db, $hookmanager, $conf, $user;

	$langs->load('agfexternalaccess@agefodd');

    $context = Context::getInstance();
    if (!validateFormateur($context)) return '';

    $upload_dir = $conf->agefodd->dir_output;
    $filearray=dol_dir_list($upload_dir, "files", 0, '', '', '', SORT_ASC, 1);

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

    $parameters = array('files' => $files);

    $reshook=$hookmanager->executeHooks('agf_getPageViewSessionCardAddAttachments', $parameters, $agsession);
    if (!empty($reshook)) {
        $files = $hookmanager->resArray;
    } else {
        $files += $hookmanager->resArray;
    }


    $out = '';
    $out.= '<!-- getPageViewSessionCardExternalAccess_files -->
		<div class="container px-0">
			<div class="panel panel-default">
				<div class="panel-body">
					<div class="row clearfix">
						<div class="col-md-6">
                            <h5>'.$langs->trans('AgfDownloadFilesTrainer').'</h5>
                            <br>';
    if (empty($user->rights->agefodd->external_trainer_download)){
        $out.='<div class="alert alert-secondary" role="alert">'.$langs->trans('AgfDownloadRightPb').'</div>';
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
        $out.= $langs->trans('AgfNoFilesDownload');
        $out.= "</p>";
    }

	if ($user->rights->agefodd->external_access_link_attatchement) {
		require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
		$link = new Link($db);
		$links = array();
		$link->fetchAll($links, $agsession->element, $agsession->id, '', '');
		if (is_array($links) && count($links)>0) {
			$out .= '				<br /><br /><h5>' . $langs->trans('AgfLinksExternal') . '</h5>';
			foreach ($links as $link) {
				$out .= '<a data-ajax="false" href="' . $link->url . '" target="_blank"><i class="fa fa-link"></i>';
				$out .= dol_escape_htmltag($link->label).'</a><br />';
			}
		}
	}

	$out.= '
						</div>

						<div class="col-md-6">
                            <h5>'.$langs->trans('AgfUploadFileTrainer').'</h5><br />';
	dol_include_once('/core/class/html.formfile.class.php');
	$formfile = new FormFile($db);

	if (empty($user->rights->agefodd->external_trainer_upload)){
	    $out.='<div class="alert alert-secondary" role="alert">'.$langs->trans('AgfDownloadRightPb').'</div>';
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
	if(floatval(DOL_VERSION) > 9){
		$out.= $langs->trans('AgfAllowLaterDownload').': <input type="checkbox" name="createsharelink" id="createsharelink">';
		$out.= "\n".'<script type="text/javascript">
			$(document).ready(function() {
				$(\'#createsharelink\').change(function() {
					let hiddenInput =  $(\'#createsharelink_hid\');

					if (hiddenInput.length == 0) {
						$(\'#formuserfile\').prepend(\'<input type="hidden" name="createsharelink_hid" id="createsharelink_hid" value="" />\');
						hiddenInput =  $(\'#createsharelink_hid\');
					}
					hiddenInput.val(this.checked | 0);
				});
			});
		</script>';
	}
	$out.= '				<br><br><h5>'.$langs->trans('AgfDownloadDocumentsTrainer').'</h5>';
	$upload_dir = $conf->agefodd->dir_output . "/" .$agsession->id;
	$filearray=dol_dir_list($upload_dir, "files", 0, '', '', '', SORT_ASC, 1);

	if(count($filearray))
	{
		require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';

	    foreach ($filearray as $file)
	    {
		    $filename=$file['name'];

		    $ecmfile = new ECMFiles($db);
		    $result=$ecmfile->fetch(0, '', dol_osencode("agefodd/" .$agsession->id . "/" .$filename));
		    if ($result > 0) {
			    if (!empty($ecmfile->share)) {
				    $dowloadUrl = $context->getRootUrl().'script/interface.php?action=downloadSessionAttachement&hashp='.$ecmfile->share;
				    $filename=getAgefoddDownloadTpl($filename, $langs->trans('Download'), $dowloadUrl, pathinfo($file['fullname'], PATHINFO_EXTENSION));
			    }
		    }
		    $out.= "<p>";
		    $out.= $filename;
		    $out.= "</p>";
	    }
	}
	else
	{
	    $out.= "<p>";
	    $out.= $langs->trans('AgfNoFilesDownload');
	    $out.= "</p>";
	}

	$out.=			    '</div>
					</div>
				</div>
			</div>
		</div>';

    return $out;
}

function getPageViewSessionCardCalendrierFormateurAddFullCalendarEventExternalAccess($action='')
{
	global $db,$langs, $hookmanager, $user, $conf;

	$context = Context::getInstance();
	$trainer = new Agefodd_teacher($db);

	if ($trainer->fetchByUser($user) > 0) {

		$out = '<!-- getPageViewSessionCardCalendrierFormateurAddFullCalendarEventExternalAccess -->';
		$out .= '<section id="section-session-card-calendrier-formateur" class="py-5">';
		$out .= '<div class="container">';
		$out .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="POST" class="clearfix">';
		$out .= '<input type="hidden" name="iframe" value="' . $context->iframe . '" />';
		$out .= '<input type="hidden" name="controller" value="' . $context->controller . '" />';

		$startDate = DateTime::createFromFormat("Y-m-d\TH:i:s P", GETPOST('start', 'none'));
		$fullDay = false;
		if (!$startDate) {
			$startDate = DateTime::createFromFormat("Y-m-d", GETPOST('start', 'none'));
			$fullDay = true;
		}


		if (!empty($startDate)) {
			$out .= '<input type="hidden" name="date" 	value="' . $startDate->format('Y-m-d') . '" />';
			$out .= '<input type="hidden" name="heured" 	value="' . $startDate->format('H:i') . '" />';
		}


		$endDate = DateTime::createFromFormat("Y-m-d\TH:i:s P", GETPOST('end', 'none'));


		if (!empty($endDate)) {
			$out .= '<input type="hidden" name="heuref" 	value="' . $endDate->format('H:i') . '" />';
		} elseif ($startDate) {
			$startDate->add(new DateInterval('PT1H'));
			$out .= '<input type="hidden" name="heuref" 	value="' . $startDate->format('H:i') . '" />';
		}

		$agsession = new Agsession($db);
		$agsession->fetch_session_per_trainer($trainer->id);
		$optionSessions = '';
		$countNbSessionAvailable = 0;
		if (!empty($agsession->lines)) {
			foreach ($agsession->lines as $line) {
			    //Calcul du total d'heures restantes sur la session
                $duree_timeDone = 0;
                $duree_timeRest = 0;
                $agefodd_sesscalendar = new Agefodd_sesscalendar ($db);
                $agefodd_sesscalendar->fetch_all($line->rowid);
                foreach ($agefodd_sesscalendar->lines as $agf_calendrier)
                {
                    if ($agf_calendrier->status == Agefodd_sesscalendar::STATUS_FINISH) {
                        $duree_timeDone += ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
                    }
                }
                $duree_timeRest = $line->duree_session - $duree_timeDone;

                if($duree_timeRest > 0)
                {
                    if (($line->datef >= $endDate->getTimestamp() && $line->dated <= $startDate->getTimestamp())
                        || !empty($conf->global->AGF_CAN_ADD_SESSION_CRENEAU_OUT_SESSION_DATE)
                    )
                    {
                        $countNbSessionAvailable++;
                        $optionLabel = $line->sessionref.' : '.$line->intitule;
                        if (!empty($conf->global->AGF_EA_ADD_TRAINEE_NAME_IN_SESSION_LIST))
                        {
                            $optionLabel .= '';
                            $sessionStagiaire = new Agefodd_session_stagiaire($db);
                            $sessionStagiaire->fetch_stagiaire_per_session($line->rowid);
                            if (!empty($sessionStagiaire->lines))
                            {
                                $i = 0;
                                $optionLabel .= ' (';
                                foreach ($sessionStagiaire->lines as $stagiare)
                                {
                                    $optionLabel .= ($i > 0 ? ', ' : '').$stagiare->getFullName($langs);
                                    $i++;
                                }
                                $optionLabel .= ')';
                            }

                        }
                        $optionSessions .= '<option value="'.$line->rowid.'">'.$optionLabel.'</option>';
                    }
                }
			}
		}

		if(empty($countNbSessionAvailable)){
		    $out.= '<div class="alert alert-info" >'.$langs->trans('AgfNoAvailableSessionInDateRange').'</div>' ;
        }
        else{

            $out .= '<div class="form-group">';
            $out .= '<label for="sessid">' . $langs->trans('AgfSelectSession') . '</label>';
            $out .= '<select class="form-control selectsearchable" data-live-search="true" name="sessid">' . $optionSessions . '</select>';
            $out .= '</div>';


		    $out .= '<button type="submit" class="btn btn-primary pull-right" >' . $langs->trans('Next') . '</button>';
        }
		$out .= '</form>';

		// Others options
		$out .= '<div class="or">' . $langs->trans('OrSeparator') . '</div>';

		$out .= '<div class="row">';

		$enableAddNotAvailableRange = false;
		$link = '';
		if ((!empty($startDate) && $fullDay && empty($endDate)) || (!empty($startDate) && !empty($endDate))) {
			$enableAddNotAvailableRange = true;

			$linkParams = '&action=add&type=AC_AGF_NOTAV';
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

/**
 * @param $agsession Agsession
 * @param $trainer Agefodd_teacher
 * @param $agf_calendrier_formateur Agefoddsessionformateurcalendrier
 * @param $agf_calendrier
 * @param string $action
 * @return string
 * @throws Exception
 */
function getPageViewSessionCardCalendrierFormateurExternalAccess($agsession, $trainer, $agf_calendrier_formateur, $agf_calendrier, $action='')
{
	global $db,$langs, $hookmanager, $user, $conf;

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

    //Calcul du total d'heures restantes sur la session
    $duree_timeDone = 0;    // temps réalisé sur la session

    $duree_timePlanned = 0; // (tk11191) temps planifié sur la session: il ne doit pas dépasser la durée de la session
    $agefodd_sesscalendar = new Agefodd_sesscalendar ($db);
    $agefodd_sesscalendar->fetch_all($agsession->id);
    foreach ($agefodd_sesscalendar->lines as $agf_calendrier)
    {
        if ($agf_calendrier->status == Agefodd_sesscalendar::STATUS_FINISH) {
            $duree_timeDone += ($agf_calendrier->heuref - $agf_calendrier->heured) / 60 / 60;
        }
        if ($agf_calendrier->status != Agefodd_sesscalendar::STATUS_CANCELED) {
        	$duree_timePlanned += ($agf_calendrier->heuref - $agf_calendrier->heured) / 3600;
		}
    }
    $duree_timeRest = $agsession->duree_session - $duree_timeDone;

	$out = '<!-- getPageViewSessionCardCalendrierFormateurExternalAccess -->';

	// CLOSE IFRAME
	$fromaction = GETPOST('fromaction', 'none');
	if ($context->iframe && $fromaction === 'add' && $action === 'view'){
		$out.= '<script >window.parent.closeModal();</script>';
	} elseif ($context->iframe && $fromaction === 'update' && $action === 'view' && $conf->global->AGF_EA_CLOSE_MODAL_AFTER_UPDATE_SESSION_SLOT) {
		$out.= '<script >window.parent.closeModal();</script>';
	}

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

    if(!empty($agf_calendrier_formateur->heured) && !empty($agf_calendrier_formateur->heuref))
    {
        $isTrainerFree = Agefoddsessionformateurcalendrier::isTrainerFree($trainer->id, $agf_calendrier_formateur->heured, $agf_calendrier_formateur->heuref, $agf_calendrier_formateur->id, 'default', array());
        if(!$isTrainerFree->isFree)
        {
            if($isTrainerFree->errors > 0){
                $out.= '<div class="alert alert-danger" >'.$langs->trans('TrainerNotFree').'</div>';
            } elseif ($isTrainerFree->warnings > 0){
                $out.= '<div class="alert alert-warning" >'.$langs->trans('TrainerCouldBeNotFree').'</div>';
            }
        }
    }

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
    //Type du créneau par défaut si plusieurs participants : défini dans la configuration du module
    $stagiaires = new Agefodd_session_stagiaire($db);
    $nb_trainees = $stagiaires->fetch_stagiaire_per_session($agsession->id);
    if ($nb_trainees > 1){
        if(!empty($conf->global->AGF_SESSION_CARD_TIMESLOT_DEFAULT_TYPE)) $calendrier_type = $conf->global->AGF_SESSION_CARD_TIMESLOT_DEFAULT_TYPE;
    } elseif ($nb_trainees == 1) {
        if(!empty($conf->global->AGF_SESSION_CARD_TIMESLOT_DEFAULT_TYPE_ONE)) $calendrier_type = $conf->global->AGF_SESSION_CARD_TIMESLOT_DEFAULT_TYPE_ONE;
    }

    $out.= '
		<blockquote class="blockquote">
			<p>'.$agsession->ref.'</p>
			<p>'.$agsession->trainer_ext_information.'</p>
		</blockquote>
	';
	$out.= '
			<h4>'.$langs->trans('AgfExternalAccessSessionCardCreneau').'</h4>';

	if ($billed)
	{
	    $out.= '
			<div class="alert alert-secondary" role="alert">
				'.$langs->trans('AgfExternalAccessSessionCardBilled').'
			</div>';
	}



	$date_session = (($action == 'update' || $action == 'view') ? date('Y-m-d', $agf_calendrier_formateur->date_session) : date('Y-m-d'));
	if(isset($_POST['date'])){
		$date_session = GETPOST('date', 'none');
	}

	$heured = (($action == 'update' || $action == 'view') ? date('H:i', $agf_calendrier_formateur->heured) : '09:00' );
	if(isset($_POST['heured'])){
		$heured = GETPOST('heured', 'none');
	}

	$heuref = (($action == 'update' || $action == 'view') ? date('H:i', $agf_calendrier_formateur->heuref) : '12:00' );
	if(isset($_POST['heuref'])){
		$heuref = GETPOST('heuref', 'none');
	}

	$TStatus = Agefoddsessionformateurcalendrier::getListStatus();
	$statusOptions = '';
	foreach ($TStatus as $statusKey => $label)
	{

		$missingTimeToTest = time();
		if(!empty($conf->global->AGF_NUMBER_OF_HOURS_BEFORE_LOCKING_ABSENCE_REQUESTS)){
			$missingTimeToTest = time() - intval($conf->global->AGF_NUMBER_OF_HOURS_BEFORE_LOCKING_ABSENCE_REQUESTS) * 3600;
		}

		$inputDisabled = '';
		if( ($agf_calendrier_formateur->heuref > time() && $statusKey == Agefoddsessionformateurcalendrier::STATUS_FINISH)
			|| (($agf_calendrier_formateur->heuref < $missingTimeToTest && $statusKey == Agefoddsessionformateurcalendrier::STATUS_MISSING)
					&& empty($conf->global->AGF_NOT_LIMIT_STATUS_TRAINER_PORTAIL_TRAINER))
		){
			$inputDisabled = 'disabled';
		}

		$statusOptions.= '<option '.($agf_calendrier_formateur->status == $statusKey ? 'selected' : '').' value="'.$statusKey.'" '.$inputDisabled.'>'.$label.'</option>';
	}

	$out.='
	<div class="form-row">
		<div class="col">
			<div class="form-group">
				<label for="heured">Date</label>
				<input '.($action == 'view' ? 'readonly' : '').' min="'.date('Y-m-d', $agsession->dated).'" max="'.date('Y-m-d', $agsession->datef).'" type="date" class="form-control" id="date_session" required name="date_session" value="'.$date_session.'">
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
				<label for="status">Status <i class="fa fa-question-circle" data-toggle="tooltip" title="'.htmlentities($langs->trans('AgfExternalAccessSessionCardDeclareHoursInfo')).'" data-html="true" aria-hidden="true"></i></label>
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
	<div class="form-row">
        <label for="note_private">Note</label>
        <div class="input-group">
                    <!-- <input '.($action == 'view' ? 'readonly' : '').' title="Note Private" type="text" class="form-control" id="note_private" name="note_private" value="'.$agf_calendrier_formateur->note_private.'" /> -->
			<textarea '.($action == 'view' ? 'readonly' : '').' title="Note Private" type="text" class="form-control" id="note_private" name="note_private">'.$agf_calendrier_formateur->note_private.'</textarea>
			<script src="'.$context->getRootUrl().'vendor/ckeditor/ckeditor.js"></script>
        </div>
    </div>

			<script>
			$( document ).ready(function() {
				CKEDITOR.replace("note_private", {width: \'100%\',});
				if($("#status").val() == "'.Agefoddsessionformateurcalendrier::STATUS_CONFIRMED.'")
				{
					$("#code_c_session_calendrier_type").prop(\'required\',true);
				} else {
					$("#code_c_session_calendrier_type").prop(\'required\',false);
				}


				$(".setTraineePresent").click(function() {

                    // auto update Hours
                    var start = document.getElementById("heured").value;
                    var end = document.getElementById("heuref").value;
                    var duration = agfTimeDiff(start, end);

                    $($(this).data("target")).val(duration);
                    $($(this).data("target")).css("outline", "none");
				});

				$(".setTraineeAbsent").click(function() {
                    // auto update Hours
                    $($(this).data("target")).val("00:00");
                    $($(this).data("target")).css("outline", "none");
				});


				var heured = document.getElementById("heured");
				var heuref = document.getElementById("heuref");

				var checkPlannedTimeValidation = function(inputHeure) {
					var dureePlanif = ' . $duree_timePlanned . ';
					var dureeSession = ' . ($agsession->duree_session) . ';
					// dureeCreneau en heures plutôt qu’en millisecondes
					var dureeCreneau = agfTimeDiff(heured.value, heuref.value, false) / 3600000;
//					console.log(dureePlanif, dureeCreneau, (dureePlanif + dureeCreneau), dureeSession);
					if (dureeCreneau < 0) {
						inputHeure.setCustomValidity("'.$langs->transnoentities('HourInvalid').'");
					} else if (dureePlanif + dureeCreneau > dureeSession) {
					    inputHeure.setCustomValidity("'.$langs->transnoentities('HourInvalidNoTime').'");
					} else {
						heured.setCustomValidity("");
						heuref.setCustomValidity("");
					}
				};

				heured.addEventListener("change", function (event) {
					checkPlannedTimeValidation(heured);
				});

				heuref.addEventListener("change", function (event) {
					checkPlannedTimeValidation(heuref);
				});

				$("#status").focus(function() {
					//Store old value
					$(this).data("lastValue",$(this).val());
				});

				$("#status").change(function() {

				   	var formStatus = $(this).val();

					if(formStatus == \''.Agefoddsessionformateurcalendrier::STATUS_CONFIRMED.'\')
					{
						$("#code_c_session_calendrier_type").prop(\'required\',true);
					} else {
						$("#code_c_session_calendrier_type").prop(\'required\',false);
					}

					// get Hours
                    var start = document.getElementById("heured").value;
                    var end = document.getElementById("heuref").value;
                    var duration = agfTimeDiff(start, end);

					if(agfTimeDiff(start, end, false) < 0){
						window.alert("'.$langs->transnoentities('HourInvalid').'");
						$(this).val($(this).data("lastValue")); // restore last value
						return;
					}


					if(formStatus == \''.Agefoddsessionformateurcalendrier::STATUS_FINISH.'\')
					{
                        $(".traineeHourSpended").each(function( index ) {
                            if($( this ).data("plannedabsence") == 0 && !$( this ).prop("readonly"))
                            {
                                 if($(this).val() == "00:00") // != duration
                                 {
                                     $(this).val(duration);
                                     $(this).css("outline", "4px solid rgba(66, 170, 245, .5)");
                                 }
                            }
                        });
					}

					if(formStatus == \''.Agefoddsessionformateurcalendrier::STATUS_MISSING.'\')
					{
                        $(".traineeHourSpended").each(function( index ) {
                            if($( this ).data("plannedabsence") == 0 && !$( this ).prop("readonly"))
                            {
                                 $(this).val(duration);
                                 $(this).css("outline", "4px solid rgba(66, 170, 245, .5)");
                            }
                        });
					}

					// Si le statut passe à annulé, les heures participants doivent passer à 0 car la session n\'a pas eu lieu
					if(formStatus == \''.Agefoddsessionformateurcalendrier::STATUS_CANCELED.'\')
					{
					    $(".traineeHourSpended").each(function( index ) {
                             $(this).val("00:00");
                             $(this).css("outline", "4px solid rgba(66, 170, 245, .5)");
                        });
					}

				});
			});

			function agfTimeDiff(start, end, outputFormated = true) {
                start = start.split(":");
                end = end.split(":");
                var startDate = new Date(0, 0, 0, start[0], start[1], 0);
                var endDate = new Date(0, 0, 0, end[0], end[1], 0);
                var diff = endDate.getTime() - startDate.getTime();
                // console.log(diff);
                if(outputFormated){
					var hours = Math.floor(diff / 1000 / 60 / 60);
					diff -= hours * 1000 * 60 * 60;
					var minutes = Math.floor(diff / 1000 / 60);

                	return ((hours < 9 && hours >= 0) ? "0" : "") + hours + ":" + (minutes < 9 ? "0" : "") + minutes;
                }
                else{
                	return diff;
                }
            }
			</script>
			';


	$stagiairesEmailErrors = array();
	$stagiairesEmailOk = 0;

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

		$out.= '<h4>'.$langs->trans('AgfExternalAccessSessionCardDeclareHours').'</h4>';



		foreach ($stagiaires->lines as &$stagiaire)
		{
			$agfssh = new Agefoddsessionstagiaireheures($db);
			if ($stagiaire->id <= 0)	continue;

			if(!filter_var($stagiaire->email, FILTER_VALIDATE_EMAIL)){
				$stagiairesEmailErrors[] = $stagiaire->nom;
			}
			else{
				$stagiairesEmailOk++;
			}

			$secondes = 0;
			$planned_absence = 0;
			if (!empty($TCalendrier))
			{
				$result = $agfssh->fetch_by_session($agsession->id, $stagiaire->id, $TCalendrier[0]->id);
				if (!$result) {
					$secondes = 0;
				} else {
					$secondes = $agfssh->heures * 60 * 60;

					if(!empty($agfssh->planned_absence))
					{
						$planned_absence = $agfssh->planned_absence;
					}
				}
			}

			$inputValue = (!empty($secondes) ? convertSecondToTime($secondes) : '00:00');
            $inputDisabled = $planned_absence?' readonly ':'';

            $inputReadonly = 0;
            $inputReadonly = $action == 'view' || $agf_calendrier_formateur->date_session > dol_now() ? 1 : $inputReadonly;
            $inputReadonly = $planned_absence ? 1 : $inputReadonly;


            $inputMore = '';
            $inputClass = '';
            $inputTitle = '';

            if($planned_absence){
                $inputMore.= ' data-toggle="tooltip" data-placement="bottom" ';
                $inputTitle = $langs->trans('AgfTraineePlannedAbsence');
                $inputClass.= ' is-valid';
            }

			$out.= '
			<label for="stagiaire_'.$stagiaire->id.'">'.strtoupper($stagiaire->nom) . ' ' . ucfirst($stagiaire->prenom).'</label>
				<div class="input-group">';

            $out.= '<input '.$inputMore.' title="'.$inputTitle.'" data-plannedabsence="'.$planned_absence.'" '.($inputReadonly?' readonly ':'').' type="text" pattern="[0-9]*(:)[0-5][0-9]" placeholder="00:00" class="form-control traineeHourSpended '.$inputClass.'" id="stagiaire_'.$stagiaire->id.'" name="hours['.$stagiaire->id.']" value="'.$inputValue.'" />';

            if(!$inputReadonly)
            {
                $out .= '<div class="input-group-append">
                        <button data-target="#stagiaire_' . $stagiaire->id . '" class="setTraineePresent btn btn-outline-success" type="button"><i class="fa fa-check-circle-o" aria-hidden="true"></i></button>
                        <button data-target="#stagiaire_' . $stagiaire->id . '" class="setTraineeAbsent btn btn-outline-danger" type="button"><i class="fa fa-ban" aria-hidden="true"></i></button>
                    </div>';
            }
            $out.= '</div>';

		}
	}

	$out.= '<div style="height:30px;" ></div>';


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


	if ($action != 'view') {

		$emailErrors = '';
		if(!empty($stagiairesEmailErrors)){
			$emailErrors = $langs->trans('AgfEmailInvalid')." :\n";
			$emailErrors.= implode("\n", $stagiairesEmailErrors);

			$emailErrors= ' <i class="fa fa-exclamation-triangle" class="tooltip" title="'.dol_htmlentities($emailErrors).'"></i>';
		}

		$checked = '';
		$readonly = '';
		if(!empty($stagiairesEmailOk))
		{
			if(empty($conf->global->AGF_DONT_SEND_EMAIL_TO_TRAINEE_BY_DEFAULT)){
				$checked = ' checked ';
			}

			if(isset($_POST['SendEmailAlertToTrainees']) || isset($_GET['SendEmailAlertToTrainees'])){
				$is_checked = GETPOST('SendEmailAlertToTrainees', 'int');
				if(empty($is_checked)){
					$checked = ' ';
				}else{
					$checked = ' checked ';
				}
			}
		}
		else{
			$readonly = ' disabled readonly ';
		}

		$out.= '<div class="form-check text-right">
    					<input type="checkbox" name="SendEmailAlertToTrainees" class="form-check-input" id="SendEmailAlertToTrainees" value="1" '.$checked.$readonly.' >
    					'.$emailErrors.' <label class="form-check-label" for="SendEmailAlertToTrainees">'.$langs->trans('AGFSendEmailAlertToTrainees').'</label>
  					</div>';
	}



		$buttons= '';
	if ($action != 'view')
	{
		$buttonsValue = $langs->trans('Add');
		if(!empty($agf_calendrier_formateur->id)) {
			$buttonsValue = $langs->trans('Update');
		}

		$buttons.= '<input type="submit" onclick="this.disabled=true; this.form.submit();" class="btn btn-primary pull-right" value="'.$buttonsValue.'" />';

	}

	if(empty($user->rights->agefodd->external_trainer_time_slot_delete)){
		$buttons .= '';
	}
	elseif($billed && !empty($agf_calendrier_formateur->id)) {
        $buttons .= '<button data-toggle="tooltip" data-placement="bottom"  title="'.$langs->trans('AgfCantDeleteBilledElement').'" type="button"  class="btn btn-grey" ><i class="fa fa-trash"></i>  Supprimer </button>';
    }
	elseif(!empty($agf_calendrier_formateur->id)) {
		$buttons .= '<button type="button" class="btn btn-danger" data-id="21" data-toggle="modal" data-target="#session-card-delete-time-slot" ><i class="fa fa-trash"></i>  Supprimer </button>';
	}

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


/**
 * @param $context
 * @param bool $strict check if current user is a trainee too
 * @return bool
 */
function validateTrainee($context, $strict = true)
{
    global $conf, $user, $langs;

    $errors = array();

    // si l'accés stagiaire n'est pas activé ont rejette
    if (empty($conf->global->AGF_EA_TRAINEE_ENABLED))
    {
        $errors[] = $context->setError($langs->trans('AgfErrorAccessTraineeNotActivated'));
    }

    // si l'utilisateur n'a pas le droit de lecture externe
    if(empty($user->rights->agefodd->external_trainee_read))
    {
        $errors[] = $context->setError($langs->trans('AgfErrorRightsNotValide'));
    }

    if($strict && !agf_UserIsTrainee($user)){
        $errors[] = $context->setError($langs->trans('AgfErrorCurrentUserIsntTrainee'));
    }

    if (count($errors))
    {
        $context->setError($errors);
        return false;
    }
    else return true;
}

/**
 * @param $user User
 * @return bool
 */
function agf_UserIsTrainee($user){
    global $db;

    require_once __DIR__ . '/../class/agefodd_stagiaire.class.php';

    $isTrainee = false;

    if(!empty($user->contactid)){
        $trainee = new Agefodd_stagiaire($db);
        if($trainee->fetch_by_contact($user->contactid) > 0)
        {
            $isTrainee = true;
        }
    }

    return $isTrainee;
}

function getPageViewAgendaOtherExternalAccess()
{

	global $db, $conf, $user, $langs, $hookmanager;

	include_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

	$context = Context::getInstance();

    if (!validateFormateur($context)) return '';

    if($context->action == 'eventdeleted'){
        $html = $langs->trans('agfEventDeleted');
        // CLOSE IFRAME
        if($context->iframe){
            $html .= '<script >window.parent.closeModal();</script>';
        }
        return '<section ><div class="container">'.$html.'</div></section >';
    }


	$action = 'view';
	if($context->action == 'add' || $context->action == 'edit' || $context->action == 'saved'){
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
	$heured = GETPOST('heured', 'none');
	$heuredDate = GETPOST('heured-date', 'none');
	$heuredTime = GETPOST('heured-time', 'none');
	if(empty($heured) && !empty($heuredDate) && !empty($heuredTime)){
		$heured = $heuredDate.'T'.$heuredTime; // it's a fix for firefox and datetime-local
	}

	$startDate 	= new DateTime();
	if(empty($heured) && !empty($event->id)){
		$startDate->setTimestamp ( $event->datep );
	}
	elseif(!empty($heured)){
		$startDate 	= parseFullCalendarDateTime($heured);
	}

	if(!empty($startDate)){
		$heured = $startDate->format('Y-m-d\TH:i');
		$heuredDate = $startDate->format('Y-m-d');
		$heuredTime = $startDate->format('H:i');
	}

	// Get end date
	$heuref = GETPOST('heuref', 'none'); // envoyer par le calendrier
	$heurefDate = GETPOST('heuref-date', 'none');
	$heurefTime = GETPOST('heuref-time', 'none');
	if(empty($heuref) && !empty($heurefDate) && !empty($heurefTime)){
		$heured = $heurefDate.'T'.$heurefTime; // it's a fix for firefox and datetime-local
	}

	$endDate = new DateTime();
	if(empty($heuref) && !empty($event->id)){
		$endDate->setTimestamp ( $event->datef );
	}
	elseif(!empty($heuref)){
		$endDate = parseFullCalendarDateTime($heuref);
	}

	if(!empty($endDate)){
		$heuref = $endDate->format('Y-m-d\TH:i');
		$heurefDate = $endDate->format('Y-m-d');
		$heurefTime = $endDate->format('H:i');
	}

	$TAvailableType = getEnventOtherTAvailableType();


	if ($action == 'edit'){
		$html.= '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" class="clearfix">';
		$html.= '<input type="hidden" name="iframe" value="'.$context->iframe.'" />';
		$html.= '<input type="hidden" name="controller" value="'.$context->controller.'" />';
	}

	$type = GETPOST('type', 'none');
	if(!empty($id)){
		$type =$event->type_code; // on update, code could not be change
	}
	if(!in_array($type, $TAvailableType)){
		$typeTitle = $langs->trans('AgfAgendaOtherTypeNotValid') ;
	}
	else{
		$typeTitle = $langs->trans('AgfAgendaOtherType_'.$type) ;
		$html.='<input type="hidden" name="type" value="'.$type.'" />';
	}

	$html.='<h4 class="mb-3">'.$typeTitle.'</h4>';

	if(!empty($id)){
		$html.='<input type="hidden" name="id" value="'.$id.'" />';
	}

	$html.='
	<div class="form-row">
		<div class="col">
			<div class="form-group">
				<label for="heured">'.$langs->trans('StartDateTime').'</label>
				<div class="row">
					<div class="col">
					  <input '.($action == 'view' ? 'readonly' : '').' type="date" class="form-control" id="heured-date" required name="heured-date" value="'.$heuredDate.'">
					</div>
					<div class="col">
					  <input '.($action == 'view' ? 'readonly' : '').' type="time" class="form-control" id="heured-time" required name="heured-time" value="'.$heuredTime.'">
					</div>
			  	</div>

			</div>
		</div>
		<div class="col">
			<div class="form-group">
				<label for="heuref">'.$langs->trans('EndDateTime').'</label>
				<div class="row">
					<div class="col">
					  <input '.($action == 'view' ? 'readonly' : '').' type="date" class="form-control" id="heuref-date" required name="heuref-date" value="'.$heurefDate.'">
					</div>
					<div class="col">
					  <input '.($action == 'view' ? 'readonly' : '').' type="time" class="form-control" id="heuref-time" required name="heuref-time" value="'.$heurefTime.'">
					</div>
			  	</div>
			</div>
		</div>
	</div>


	<div class="form-group">
		<label for="actionnote">'.$langs->trans('Notes').'</label>
		<textarea '.($action == 'view' ? 'readonly' : '').' type="datetime-local" class="form-control" id="actionnote" name="note" >'.dol_htmlentities($event->note).'</textarea>
	</div>';

    $parameters = array(
         'heured'  => $heured
        , 'heuredDat' => $heuredDate
        , 'heuredTime' => $heuredTime
        , 'heuref' => $heuref
        , 'heurefDate' => $heurefDate
        , 'heurefTime' => $heurefTime
    );


    $hookmanager->executeHooks('formObjectOptions', $parameters, $event, $action);
    if (!empty($hookmanager->resPrint)) $html.= $hookmanager->resPrint;



    $html.='<p>';
    $html.='<button class="btn btn-danger pull-left" type="button" data-toggle="modal" data-target="#deleteeventotherconfirm"  ><i class="fa fa-trash" ></i> '.$langs->trans('Delete').'</button>';
	if($action == 'edit'){
        $html.='<button class="btn btn-primary pull-right" type="submit" name="action" value="save" > '.$langs->trans('Save').'</button>';
	}
    $html.='</p>';

    if($action == 'edit'){
        $html.= '</form>';
    }



    $title = $langs->trans('AreYouSureToDelete');
    $body  = $langs->trans('AgfYouAreUnderDeleteCalendarEvent');
    $deleteUrl = $_SERVER['PHP_SELF'] . '?iframe='.$context->iframe.'&controller='.$context->controller."&id=".$id;
    $html.= getEaModalConfirm('deleteeventotherconfirm', $title, $body, $deleteUrl, 'delete');


	return '<section ><div class="container">'.$html.'</div></section >';
}

function getEnventOtherTAvailableType()
{
	// car à un moment il va bien en avoir d'autres ...
	return array(
		'AC_AGF_NOTAV'
	);
}

function getPageViewAgendaFormateurExternalAccess(){

	global $conf, $user, $langs;

	$context = Context::getInstance();

    if (!validateFormateur($context)) return '';

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

			$(info.el).popover(\'destroy\');

		    $(info.el).popover({
		    		title: info.event.title ,
		    		content: info.event.extendedProps.msg,
		    		html: true,
		    		trigger: "hover"
		    });

		},
		eventSources: [
			{
				url: fullcalendarscheduler_interface,
				extraParams: {
					agendaType: \'session\'
				},
				failure: function() {
				//document.getElementById(\'script-warning\').style.display = \'block\'
				}
			},
			{
				url: fullcalendarscheduler_interface,
				extraParams: {
					agendaType: \'notAvailableRange\'
				},
				failure: function() {
				//document.getElementById(\'script-warning\').style.display = \'block\'
				}
			}
		],
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
	<div class="modal fade" id="calendarModal" tabindex="-1" role="dialog" aria-labelledby="calendarModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false" >
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
    $langs->load("agfexternalaccess@agefodd");

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

            $obj->heured = $db->jdate($obj->heured);
            $obj->heuref = $db->jdate($obj->heuref);


			$agf_calendrier_formateur = new Agefoddsessionformateurcalendrier($db);
			$agf_calendrier_formateur->fetch($obj->rowid);

            $isTrainerFree = Agefoddsessionformateurcalendrier::isTrainerFree($fk_formateur, $obj->heured, $obj->heuref, $obj->rowid, 'default', array());


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
          	$event->start	= date('c', $obj->heured);
			$event->end		= date('c', $obj->heuref);

			//...
			$event->session_formateur_calendrier = new stdClass();
			$event->session_formateur_calendrier->id = $obj->rowid;


			$event->msg = '';


			$duree_declared = Agsession::getStaticSumDureePresence($obj->fk_session);


			//$TsessionStatusLiving = array('ENV', 'CONF', 'ONGOING');
			//$TsessionStatusIdle = array('NOT', 'ARCH', 'DONE');

			$TsessionCalendrierStatusLiving = array(
				Agefoddsessionformateurcalendrier::STATUS_CONFIRMED,
			);
			$TsessionCalendrierStatusIdle = array(
				Agefoddsessionformateurcalendrier::STATUS_DRAFT,
				Agefoddsessionformateurcalendrier::STATUS_MISSING,
				Agefoddsessionformateurcalendrier::STATUS_FINISH,
				Agefoddsessionformateurcalendrier::STATUS_CANCELED,
			);

			$event->color = '#3788d8';
			if(in_array($agf_calendrier_formateur->status, $TsessionCalendrierStatusIdle)){
				$event->color = '#547ea9';
			}



			if($db->jdate($obj->heuref) < time()){
				$event->color = AGF_colorLighten($event->color, 10);
			}

			$T = array();
			$T['calendrierStatus'] 	= '<span class="badge badge-primary" style="background: '.$event->color.' " >'.$agf_calendrier_formateur->getLibStatut().'</span>';

            if(!$isTrainerFree->isFree)
            {
                if($isTrainerFree->errors > 0){
                    $event->color = '#c20a22';
                    $T['calendrierIsTrainerFree'] = '<div class="alert alert-danger" >'.$langs->trans('TrainerNotFree').'</div>';
                } elseif ($isTrainerFree->warnings > 0){
                    $event->borderColor = '#ffa20d';
                    $T['calendrierIsTrainerFree'] = '<div class="alert alert-warning" >'.$langs->trans('TrainerCouldBeNotFree').'</div>';
                }

                $T['calendrierIsTrainerFree'].= ' '.$isTrainerFree->errorMsg;
            }

			$T['sessionTitle'] 		= '<small style="font-weight: bold;" >'.$langs->trans('AgfInfoSession').' :</small>';
			$T['sessionStatus'] 	= $langs->trans('AgfStatus').' : '.$agf_session->getLibStatut(0);
			$T['sessionDuration'] 	= $langs->trans('AgfDuree').' : '.$agf_session->duree_session;
			$T['sessionDurationDeclared'] = $langs->trans('AgfDureeDeclared').' : '.$duree_declared;
			$T['sessionDurationSold'] = $langs->trans('AgfDureeSolde').' : '. ($agf_session->duree_session - $duree_declared);

			$event->msg.= implode('<br/>',$T);

			$parameters= array(
				'sqlObj' => $obj,
				'agf_calendrier_formateur' => $agf_calendrier_formateur,
				'T' => $T
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


function  getAgefoddJsonAgendaFormateurNotAvailable($fk_formateur = 0, $start = 0, $end = 0){

	global $db, $hookmanager, $langs, $user, $globalSessionCache;

	$langs->load("agefodd@agefodd");

	dol_include_once('/agefodd/class/agsession.class.php');
	dol_include_once('/agefodd/class/agefodd_formateur.class.php');

	$context = Context::getInstance();

	$TRes = array();
	//if (empty($fk_formateur)) return json_encode($TRes);

	$sql = 'SELECT a.id, a.datep, a.datep2, a.fk_action, a.code, a.label, a.fk_element, a.elementtype, a.fulldayevent  ';

	$sql.= ' FROM '.MAIN_DB_PREFIX.'actioncomm a ';


	$sql.= " WHERE a.code = 'AC_AGF_NOTAV' ";

	if(!empty($start)){
		$sql.= ' AND a.datep <= \''.date('Y-m-d H:i:s', $end).'\'';
	}

	if(!empty($start)){
		$sql.= ' AND a.datep2 >= \''.date('Y-m-d H:i:s', $start).'\'';
	}

	if(!empty($fk_formateur)){
		$sql.= ' AND a.fk_element = '.intval($fk_formateur);
		$sql.= " AND a.elementtype = 'agefodd_formateur' ";
	}


	$resql = $db->query($sql);

	if ($resql)
	{
		while ($obj = $db->fetch_object($resql))
		{
			$event = new stdClass();

			//$event->groupId: 999,
			$event->title	= $obj->label;



			$actionUrl = '&action=view';
			if ( $user->rights->agefodd->external_trainer_write) {
				$actionUrl = '&action=edit';
			}//agefodd_event_other

			$event->url		= $context->getRootUrl('agefodd_event_other', '&id='.$obj->id.$actionUrl);
			$event->start	= date('c', $db->jdate($obj->datep));
			$event->end		= date('c', $db->jdate($obj->datep2));
			$event->agendaType == 'notAvailableRange';
			//$event->rendering = 'background';

			//...
			$event->session_formateur_calendrier = new stdClass();
			$event->session_formateur_calendrier->id = 0;
			$event->msg = '';


			$event->color = '#828282';
			if($db->jdate($obj->datep2) < time()){
				$event->color = AGF_colorLighten($event->color, 10);
			}



			$T = array();
			//$T[] = '<small style="font-weight: bold;" >'.$langs->trans('AgfInfoSession').' :</small>';

			$event->msg.= implode('<br/>',$T);


			$parameters= array(
				'sqlObj' => $obj,
				'T' => $T
			);

			$reshook=$hookmanager->executeHooks('externalaccess_getAgefoddJsonAgendaFormateurNotAvailable',$parameters,$event);    // Note that $action and $object may have been modified by hook

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

	$date = new DateTime($string);
	if ($timezone) {
		// If our timezone was ignored above, force it.
		$date->setTimezone($timezone);
	}
	return $date;
}


/**
 * @param string $hex color in hex
 * @param integer $steps Steps should be between -255 and 255. Negative = darker, positive = lighter
 * @return string
 */
function AGF_colorAdjustBrightness($hex, $steps)
{
	// Steps should be between -255 and 255. Negative = darker, positive = lighter
	$steps = max(-255, min(255, $steps));
	// Normalize into a six character long hex string
	$hex = str_replace('#', '', $hex);
	if (strlen($hex) == 3) {
		$hex = str_repeat(substr($hex, 0, 1), 2).str_repeat(substr($hex, 1, 1), 2).str_repeat(substr($hex, 2, 1), 2);
	}
	// Split into three parts: R, G and B
	$color_parts = str_split($hex, 2);
	$return = '#';
	foreach ($color_parts as $color) {
		$color   = hexdec($color); // Convert to decimal
		$color   = max(0, min(255, $color + $steps)); // Adjust color
		$return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
	}
	return $return;
}

/**
 * @param string $hex color in hex
 * @param integer $percent 0 to 100
 * @return string
 */
function AGF_colorDarker($hex, $percent)
{
	$steps = intval(255 * $percent / 100) * -1;
	return AGF_colorAdjustBrightness($hex, $steps);
}

/**
 * @param string $hex color in hex
 * @param integer $percent 0 to 100
 * @return string
 */
function AGF_colorLighten($hex, $percent)
{
	$steps = intval(255 * $percent / 100);
	return AGF_colorAdjustBrightness($hex, $steps);
}


function downloadAgefoddTrainneeDoc(){

    global $langs, $db, $conf, $user;

    dol_include_once('/agefodd/class/agsession.class.php');

    $filename=false;
    $context = Context::getInstance();
    $forceDownload = GETPOST('forcedownload','int');
    $model = GETPOST('model', 'none');
    $sessionId = GETPOST('session', 'int');


    if($conf->global->AGF_EA_TRAINEE_ENABLED
        && !empty($user->rights->agefodd->external_trainee_read)
        && validateTrainee($context, true)
        && !empty($sessionId)
    )
    {

        $trainee = new Agefodd_stagiaire($db);
        if($trainee->fetch_by_contact($user->contactid) > 0)
        {
            $modelsAvailables = array(
                'attestationendtraining_trainee',
                'attestation_trainee'
            );

            $agsession = new Agsession($db);
            if($agsession->fetch($sessionId) > 0)
            {
                if(!empty($model) && in_array($model, $modelsAvailables)){
                    $documentPath = getAgefoddTraineeDocumentPath($agsession, $trainee, $model);

                    if(!empty($documentPath)){
                        downloadFile($documentPath, $forceDownload);
                    }
                    else{
                        print $langs->trans('FileNotExists');
                    }
                }
            }
        }
    }
}

/**
 * Send email alert to trainer when a trainee change absence status
 * @param $user User
 * @param $agsession Agsession
 * @param $trainee Agefodd_stagiaire
 * @param $sessionStagiaire Agefodd_session_stagiaire
 * @param $calendrier Agefodd_sesscalendar
 * @param $sessionstagiaireheures Agefoddsessionstagiaireheures
 * @param $errorsMsg string
 */
function traineeSendMailAlertForAbsence($user, $agsession, $trainee, $sessionStagiaire, $calendrier, $sessionstagiaireheures, &$errorsMsg = array())
{
    global $conf, $langs, $user, $db, $hookmanager;

    $nbMailSend = 0;
    $error = 0;

    // Check conf of module
    if(empty($conf->global->AGF_SEND_CREATE_CRENEAU_TO_TRAINEE_MAILMODEL) || empty($conf->global->AGF_SEND_SAVE_CRENEAU_TO_TRAINEE_MAILMODEL)) {
        $errorsMsg[]= $langs->trans('TemplateMailNotExist');
        return -1;
    }

    $fk_mailModel= $conf->global->AGF_SEND_TRAINEE_ABSENCE_ALERT_MAILMODEL;

    require_once (DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php');


    if ($trainee->id <= 0){
        $errorsMsg[] = $langs->trans('AgfWarningStagiaireNoId');
        return -1;
    }

    $sessionstagiaireheures = new Agefoddsessionstagiaireheures($db);
    $result = $sessionstagiaireheures->fetch_by_session($agsession->id, $trainee->id, $calendrier->id);
    if ($result < 0){
        $errorsMsg[] = $langs->trans('AgfErrorFetchingAgefoddsessionstagiaireheures');
        $error++;
    }else {

        $mailTpl = agf_getMailTemplate($fk_mailModel);
        if($mailTpl < 1){
            $errorsMsg[] = $langs->trans('AgfEMailTemplateNotExist');
            return -2;
        }

        if($agsession->fetchTrainers() < 1)
        {
            $errorsMsg[] = $langs->trans('AgfFetchTrainersError');
            return -3;
        }

        if(empty($agsession->TTrainer)){
            $errorsMsg[] = $langs->trans('AgfNoTrainerFoundCallYourContact');
            return 0;
        }


        // PREPARE EMAIL
        $from = getExternalAccessSendEmailFrom($user->email);
        $replyto = $user->email;
        $errors_to = $conf->global->MAIN_MAIL_ERRORS_TO;

        if (! isset($arrayoffamiliestoexclude)) $arrayoffamiliestoexclude=null;

        // Make substitution in email content
        $substitutionarray = getCommonSubstitutionArray($langs, 0, $arrayoffamiliestoexclude, $agsession);

        complete_substitutions_array($substitutionarray, $langs, $agsession);

        $thisSubstitutionarray = $substitutionarray;

        $thisSubstitutionarray['__agfsendall_nom__'] = $trainee->nom;
        $thisSubstitutionarray['__agfsendall_prenom__'] = $trainee->prenom;
        $thisSubstitutionarray['__agfsendall_civilite__'] = $trainee->civilite;
        $thisSubstitutionarray['__agfsendall_socname__'] = $trainee->socname;
        $thisSubstitutionarray['__agfsendall_email__'] = $trainee->email;



        $thisSubstitutionarray['__agfcreneau_heured__'] = date('H:i', $calendrier->heured);
        $thisSubstitutionarray['__agfcreneau_heuref__'] = date('H:i', $calendrier->heuref);
        $thisSubstitutionarray['__agfcreneau_datesession__'] = dol_print_date($calendrier->date_session);
        $thisSubstitutionarray['__agfcreneau_status__'] = $calendrier->getLibStatut();

        if(empty($sessionstagiaireheures->planned_absence)){
            $thisSubstitutionarray['__agfcreneau_planned_absence__'] = $langs->trans('AgfTraineeMailPlanedPresentStatus');
        }
        else{
            $thisSubstitutionarray['__agfcreneau_planned_absence__'] = $langs->trans('AgfTraineeMailPlanedMissingStatus');
        }

        $sessionstagiaireheures->planned_absence = 0;

        // Tableau des substitutions
        if (! empty($agsession->intitule_custo)) {
            $thisSubstitutionarray['__FORMINTITULE__'] = $agsession->intitule_custo;
        } else {
            $thisSubstitutionarray['__FORMINTITULE__'] = $agsession->formintitule;
        }

        $date_conv = $agsession->libSessionDate('daytext');
        $thisSubstitutionarray['__FORMDATESESSION__'] = $date_conv;

        $sendTopic =make_substitutions($mailTpl->topic, $thisSubstitutionarray);
        $sendContent =make_substitutions($mailTpl->content, $thisSubstitutionarray);

        $addr_cc = '';
        $addr_bcc = '';

        $TTo = array();

        // Add trainer emails
        foreach ($agsession->TTrainer as $trainer){
            $TTo[] = $trainer->email;
        }

        foreach ($TTo as $key => $to){
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                unset($TTo[$key]);
                // is not a valid email address
                $toMsg = empty($to)?$langs->trans('AgfMailEmpty'):$to;
                $errorsMsg[] = $langs->trans('AgfInvalidAddressEmail', $toMsg);
                $error++;
            }
        }

        $parameters=array(
            'TTo'               =>& $TTo,
            'user'              => $user,
            'agsession'         => $agsession,
            'sessionStagiaire'  => $sessionStagiaire,
            'calendrier'        => $calendrier,
            'sessionstagiaireheures' => $sessionstagiaireheures,
            'errorsMsg'         =>& $errorsMsg,
            'sendTopic'         =>& $sendTopic,
            'from'              =>& $from,
            'sendContent'       =>& $sendContent,
            'addr_cc'           =>& $addr_cc,
            'addr_bcc'          =>& $addr_bcc,
            'replyto'           =>& $replyto,
            'errors_to'         =>& $errors_to
        );
        $reshook=$hookmanager->executeHooks('agf_traineeSendMailAlertForAbsence', $parameters, $trainee);

        if (empty($reshook)) {
            // override full output

            // hidden conf
            if (!empty($conf->global->AGF_CRENEAU_FORCE_EMAIL_TO) && filter_var($conf->global->AGF_CRENEAU_FORCE_EMAIL_TO, FILTER_VALIDATE_EMAIL)) {
                $TTo = array($conf->global->AGF_CRENEAU_FORCE_EMAIL_TO);
            }

            if (!empty($TTo)) {

                $to = implode(',', $TTo);

                $cMailFile = new CMailFile($sendTopic, $to, $from, $sendContent, array(), array(), array(), $addr_cc, $addr_bcc,  0, 1, $errors_to, '', '', '', getExternalAccessSendEmailContext(), $replyto);

                if ($cMailFile->sendfile()) {
                    $nbMailSend++;
                } else {
                    $errorsMsg[] = $cMailFile->error . ' : ' . $to;
                    $error++;
                }
            } else {
                $errorsMsg[] = $langs->trans('AgfNoEmailToSend');
                $error++;
            }
        }

    }


    return $nbMailSend;
}

function traineeCanChangeAbsenceStatus($heured)
{
    global $conf;

    if(!empty($conf->global->AGF_NUMBER_OF_HOURS_BEFORE_LOCKING_ABSENCE_REQUESTS)){
        return (intval($heured) - intval($conf->global->AGF_NUMBER_OF_HOURS_BEFORE_LOCKING_ABSENCE_REQUESTS) * 3600) > time();
    }
    else{
        return false;
    }
}


function getExternalAccessSendEmailContext(){
    global $conf;
    $sendcontext='emailing';
    if(!empty($conf->global->AGF_SEND_EMAIL_CONTEXT_STANDARD))
    {
        $sendcontext='standard';
    }

    return $sendcontext;
}

function getExternalAccessSendEmailFrom($default){
    global $conf;
    $mail=$default;
    if(!empty($conf->global->AGF_EA_SEND_EMAIL_FROM))
    {
        $mail=$conf->global->AGF_EA_SEND_EMAIL_FROM;
    }
    elseif(!empty($conf->global->MAIN_MAIL_EMAIL_FROM)){
        $mail=$conf->global->MAIN_MAIL_EMAIL_FROM;
    }

    return $mail;
}

/**
 * Get table of a planning's trainee
 * @param $session User
 * @param $idsession Agsession
 * @param $trainee Agefodd_stagiaire
 * @return string
 */
function getPlanningViewSessionTrainee($session, $idsess, $trainee){

    global $db, $langs, $user;

	$context = Context::getInstance();

    dol_include_once('/agefodd/class/agefodd_session_stagiaire_heures.class.php');
	dol_include_once('/agefodd/class/agefodd_session_stagiaire_planification.class.php');
	dol_include_once('/agefodd/class/agefodd_session_formateur.class.php');
	dol_include_once('/agefodd/class/agefodd_formateur.class.php');

	$out='';
	$session_trainer = new Agefodd_session_formateur($db);
	$formateur = new Agefodd_teacher($db);
	$formateur->fetchByUser($user);
	$TtrainerName = array();
	$fk_agefodd_session_formateur=0;
	//Find fk_agefodd_session_formateur for the current trainer
	if (!empty($formateur->id)) {
		$result = $session_trainer->fetch_formateur_per_session($idsess);
		if ($result < 0) {
			$context->setEventMessage($session_trainer->error, 'errors');
		} else {
			if (is_array($session_trainer->lines) && count($session_trainer->lines) > 0) {
				foreach ($session_trainer->lines as $line) {
					if ($line->formid == $formateur->id) {
						$fk_agefodd_session_formateur = $line->opsid;
						$TtrainerName[$fk_agefodd_session_formateur] = $line->firstname . ' ' . $line->lastname;
						break;
					}
				}
			}
		}

		$idTrainee_session = $trainee->stagerowid;
		$idtrainee = $trainee->id;

		//Tableau de toutes les heures plannifiées du participant
		$planningTrainee = new AgefoddSessionStagiairePlanification($db);
		$result = $planningTrainee->loadDictModalite();
		if ($result < 0) {
			$context->setEventMessage($planningTrainee->error, 'errors');
		}
		$TLinesTraineePlanning = $planningTrainee->getSchedulesPerCalendarType($idsess, $idTrainee_session);

		//if(empty($user->rights->agefodd->external_trainer_seeotrainerplantime))

		//Nombre d'heures planifiées
		$totalScheduledHoursTrainee = $planningTrainee->getTotalScheduledHoursbyTrainee($idsess, $idTrainee_session);
		if (empty($totalScheduledHoursTrainee))
			$totalScheduledHoursTrainee = 0;

		//heures réalisées par type de créneau

		$heureRTotal = 0;
		$trainee_hr = new Agefoddsessionstagiaireheures($db);
		$THoursR = $trainee_hr->fetch_heures_stagiaire_per_type($idsess, $idtrainee);
		if (!is_array($THoursR) && $THoursR < 0) {
			$context->setEventMessages($trainee_hr->error, 'errors');
		} elseif (is_array($THoursR) && count($THoursR) > 0) {
			$detailHours = '';
			foreach ($THoursR as $typ => $trainer_hrs) {
				foreach ($trainer_hrs as $trainerid => $hrs) {
					$detailHours .= $planningTrainee->TTypeTimeByCode[$typ]['label'] . ':' . $hrs;
					if (!empty($trainerid)) {
						if (!array_key_exists($trainerid, $TtrainerName)) {
							$result = $session_trainer->fetch($trainerid);
							if ($result < 0) {
								setEventMessage($session_trainer->error, 'errors');
							} elseif ($result == 1) {
								if (!empty($user->rights->agefodd->external_trainer_seeotrainerplantime)) {
									$TtrainerName[$trainerid] = $session_trainer->firstname . ' ' . $session_trainer->lastname;
								} else {
									$TtrainerName[$trainerid] = $langs->trans('AgfOtherTrainer');
								}
							}
						}
						$detailHours .= ' (' . $TtrainerName[$trainerid] . ')';
					}
					$detailHours .= '<br/>';
					$heureRTotal += $hrs;
				}
			}
		} else {
			$heureRTotal = 0;
		}

		if (!empty($detailHours)) {
			$detailHours = ' <span data-toggle="popover" title="' . $langs->trans('AgfDetailHeure') . '" data-content="' . $detailHours . '"><i class="fa fa-plus hours-detail"></i></span>';
		}

		//heures totales restantes : durée de la session - heures réalisées totales
		$heureRestTotal = $session->duree_session - $heureRTotal;

		$out = '<table class="table table-striped w-100" id="planningTrainee">';

		//Titres
		$out .= '<tr class="text-center">';
		$out .= '<th width="15%" class="text-center">' . $langs->trans('AgfCalendarType') . '</th>';
		$out .= '<th width="35%" class="text-center">' . $langs->trans('AgfHoursP') . ' (' . $totalScheduledHoursTrainee . ')</th>';
		$out .= '<th class="text-center">' . $langs->trans('AgfHoursR') . ' (' . $heureRTotal . $detailHours . ')</th>';
		$out .= '<th class="text-center">' . $langs->trans('AgfHoursRest') . ' (' . $heureRestTotal . ')</th>';
		$out .= '</tr>';

		//Lignes par type de modalité
		foreach ($TLinesTraineePlanning as $line) {


			//Match Trainer
			if (!empty($line->fk_agefodd_session_formateur)) {
				if (!array_key_exists($line->fk_agefodd_session_formateur, $TtrainerName)) {
					$result = $session_trainer->fetch($line->fk_agefodd_session_formateur);
					if ($result < 0) {
						setEventMessage($session_trainer->error, 'errors');
					} elseif($result==1) {
						$TtrainerName[$line->fk_agefodd_session_formateur] = $session_trainer->firstname . ' ' . $session_trainer->lastname;
					}
				}
				//Calcul heures restantes
				$heureRest = $line->heurep - $THoursR[$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['code']][$line->fk_agefodd_session_formateur];
				if ($heureRest<0) {
					$heureRest ='<div style="color:red;font-weight: bold">'.$heureRest.' (' . $TtrainerName[$line->fk_agefodd_session_formateur].')</div>';
				} else {
					$heureRest .=' (' . $TtrainerName[$line->fk_agefodd_session_formateur].')';
				}
				//Calcul heures réalisé
				if (!empty($THoursR[$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['code']][$line->fk_agefodd_session_formateur])) {
					$heureDone = $THoursR[$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['code']][$line->fk_agefodd_session_formateur] . ' ('. $TtrainerName[$line->fk_agefodd_session_formateur].')';
				} else {
					$heureDone ='';
				}
			} else {
				$heureDoneByModTotal=0;
				$TrainerDoneByMod=array();
				foreach($THoursR[$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['code']] as $trainerid=>$hrsDone) {
					$heureDoneByModTotal += $hrsDone;
					$TrainerDoneByMod[]= $TtrainerName[$trainerid];
				}

				if (!empty($heureDoneByModTotal)) {
					$heureDone = $heureDoneByModTotal.' ('.implode(',', $TrainerDoneByMod).')';
					$heureRest = $line->heurep - $heureDoneByModTotal;
				} else {
					$heureRest = $line->heurep - $THoursR[$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['code']][0];
					$heureDone = $THoursR[$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['code']][0];
				}
			}

			$out .= '<tr>';

			//Type créneau
			$out .= '<td>'.$planningTrainee->TTypeTimeById[$line->fk_calendrier_type]['label'].'</td>';
			//Heure planifé
			$out .= '<td class="text-center">'.$line->heurep.(array_key_exists($line->fk_agefodd_session_formateur, $TtrainerName)?' (' . $TtrainerName[$line->fk_agefodd_session_formateur].')':'').'</td>';
			//Heure réalisées
			$out .= '<td class="text-center">'.$heureDone.'</td>';
			//Heures restantes
			$out.= '<td class="text-center">'.$heureRest.'</td>';

		}

		$out .= '</table>';
	}
    return $out;
}
