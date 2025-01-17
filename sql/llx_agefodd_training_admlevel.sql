-- ============================================================================
-- Copyright (C) 2012-2013	Florian Henry	<florian.henry@open-concept.pro>
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
-- Structure de la table llx_agefodd_training_admlevel
--
CREATE TABLE IF NOT EXISTS llx_agefodd_training_admlevel (
  rowid integer NOT NULL auto_increment PRIMARY KEY,
  fk_agefodd_training_admlevel integer NOT NULL default '0',
  fk_training integer  NOT NULL,
  level_rank integer NOT NULL default 0,
  fk_parent_level integer default 0,
  indice integer NOT NULL,
  intitule varchar(150) NOT NULL,
  delais_alerte integer NOT NULL,
  delais_alerte_end integer,
  fk_user_author integer NOT NULL,
  datec datetime NOT NULL,
  fk_user_mod integer NOT NULL,
  tms timestamp NOT NULL,
  trigger_name varchar(150) NULL
) ENGINE=InnoDB;
