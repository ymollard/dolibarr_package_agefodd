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
-- Structure de la table llx_agefodd_session
--

CREATE TABLE IF NOT EXISTS llx_agefodd_session (
  rowid int(11) NOT NULL auto_increment,
  fk_formation_catalogue int(11) NOT NULL,
  fk_session_place int(11) NOT NULL,
  fk_agefodd_formateur int(11) default NULL,
  dated datetime default NULL,
  datef datetime default NULL,
  notes text NOT NULL,
  fk_user_author int(11) NOT NULL,
  datec datetime NOT NULL,
  fk_user_mod int(11) NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  archive enum('0','1') NOT NULL default '0',
  PRIMARY KEY  (rowid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
