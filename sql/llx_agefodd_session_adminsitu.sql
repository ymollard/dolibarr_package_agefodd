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
-- Structure de la table llx_agefodd_session_adminsitu
--
CREATE TABLE IF NOT EXISTS llx_agefodd_session_adminsitu (
  rowid integer NOT NULL auto_increment PRIMARY KEY,
  fk_agefodd_session_admlevel integer NOT NULL default '0',
  fk_agefodd_session integer NOT NULL,
  intitule varchar(500) default NULL,
  delais_alerte integer NOT NULL,
  delais_alerte_end integer NOT NULL,
  indice integer NOT NULL,
  level_rank integer NOT NULL default '0',
  fk_parent_level integer default '0',
  dated datetime default NULL,
  datef datetime NOT NULL,
  datea datetime NOT NULL,
  notes text NOT NULL,
  fk_user_author integer NOT NULL,
  datec datetime NOT NULL,
  fk_user_mod integer NOT NULL,
  tms timestamp NOT NULL,
  archive smallint NOT NULL DEFAULT 0,
  trigger_name varchar(150) NULL
) ENGINE=InnoDB;
