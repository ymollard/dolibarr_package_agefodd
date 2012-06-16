-- ============================================================================
-- Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
-- Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
-- Copyright (C) 2012		Florian Henry	<florian.henry@open-concept.pro>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- ============================================================================

--
-- Structure de la table llx_agefodd_stagiaire_type
--

CREATE TABLE IF NOT EXISTS llx_agefodd_stagiaire_type (
  rowid int(11) NOT NULL auto_increment,
  intitule varchar(80) NOT NULL,
  sort tinyint(4) NOT NULL,
  datec datetime NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  fk_user_author int(11) NOT NULL,
  fk_user_mod int(11) NOT NULL,
  PRIMARY KEY  (rowid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

--
-- Contenu de la table llx_agefodd_stagiaire_type
--

INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, datec, tms, fk_user_author, fk_user_mod) VALUES
(1, 'financement par l''employeur (contrat pro.)', 1, '0000-00-00 00:00:00', '2010-06-30 18:47:43', 0, 0),
(2, 'financement par l''employeur (autre)', 2, '0000-00-00 00:00:00', '2010-06-30 18:47:56', 0, 0),
(3, 'demandeur d''emploi avec financement public', 3, '0000-00-00 00:00:00', '2010-06-30 18:48:05', 0, 0),
(4, 'autre', 4, '0000-00-00 00:00:00', '2010-06-30 18:48:11', 0, 0);

