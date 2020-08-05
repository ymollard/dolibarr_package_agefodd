<?php
/*
 * Copyright (C) 2017 Florian Henry <florian.henry@open-concept.pro>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file agefodd/core/substitution/functions_agefodd.lib.php
 * \ingroup agefodd
 * \brief Some display function
 */


function agefodd_completesubstitutionarray(&$substitutionarray,$outputlangs,$object,$parameters) {
	global $conf, $db;

	$outputlangs->load('agefood@agefodd');
	$substitutionarray=array_merge($substitutionarray, array(
			'__FORMINTITULE__' => $outputlangs->trans('AgfFormIntitule').' '.$outputlangs->trans('OnlyOnTrainingMail'),
			'__FORMDATESESSION__' => $outputlangs->trans('AgfPDFFichePres7bis').' '.$outputlangs->trans('OnlyOnTrainingMail'),
			'__AGENDATOKEN__' => $conf->global->MAIN_AGENDA_XCAL_EXPORTKEY,
			'__TRAINER_1_EXTRAFIELD_XXXX__' => $outputlangs->trans('OnlyOnSessionMail')
	));

	// Add ICS link replacement to mails
	$downloadIcsLink = dol_buildpath('public/agenda/agendaexport.php', 2).'?format=ical&type=event';

	if(!empty($object) && $object->element == 'agefodd_formateur')
	{
		$substitutionarray['__AGENDAICS__'] = $downloadIcsLink.'&amp;agftrainerid='.$object->id;
		$substitutionarray['__AGENDAICS__'].= '&exportkey='.md5($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY.'agftrainerid'.$object->id);
	}
	elseif(!empty($object) && get_class ($object) == "Agefodd_stagiaire")
	{
		$substitutionarray['__AGENDAICS__'] = $downloadIcsLink.'&amp;agftraineeid='.$object->id;
		$substitutionarray['__AGENDAICS__'].= '&exportkey='.md5($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY.'agftraineeid'.$object->id);
	}
    elseif(!empty($object) && $object->element == 'contact' && $parameters['needforkey'] == 'SUBSTITUTION_AGFSESSIONLIST')
    {
        $nbCollections = 0;

        // count session list
        $sql  = "SELECT";
        $sql .= " s.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "agefodd_stagiaire as trainee";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session_stagiaire as ss ON ss.fk_stagiaire = trainee.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "agefodd_session as s ON s.rowid = ss.fk_session_agefodd";
        $sql .= " WHERE s.entity IN (" . getEntity('agefodd') . ")";
        $sql .= " AND trainee.fk_socpeople = " . $object->id;

        $resql = $db->query($sql);
        if ($resql) {
            $nbCollections = $db->num_rows($resql);
        } else {
            dol_print_error($db);
        }

        $substitutionarray['AGFSESSIONLIST'] = $outputlangs->trans('AgfMenuSess') . ($nbCollections > 0 ? ' <span class="badge">' . ($nbCollections) . '</span>' : '');
    }

	// show sendCreneauEmailAlertToTrainees for  __AGENDAICS__ external access substitution



	return $substitutionarray;
}
