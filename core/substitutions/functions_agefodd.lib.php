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
	$outputlangs->trans('agefood@agefodd');
	$substitutionarray=array_merge($substitutionarray, array(
			'__FORMINTITULE__' => $outputlangs->trans('AgfFormIntitule'),
	));
	return $substitutionarray;
}