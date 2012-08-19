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
  fk_soc int NULL,
  fk_formation_catalogue int(11) NOT NULL,
  fk_session_place int(11) NOT NULL,
  nb_place int NULL,
  nb_stagiaire int NULL,
  force_nb_stagiaire int NULL,
  dated datetime default NULL,
  datef datetime default NULL,
  notes text NOT NULL,
  color varchar(32) NULL,
  cost_trainer double(24,8) DEFAULT 0,         
  cost_site double(24,8) DEFAULT 0,
  cost_trip double(24,8) NULL,    
  sell_price double(24,8) DEFAULT 0, 
  is_date_res_site tinyint NOT NULL DEFAULT 0,
  date_res_site datetime DEFAULT NULL,
  is_date_res_trainer tinyint NOT NULL DEFAULT 0,
  date_res_trainer datetime DEFAULT NULL,
  date_ask_OPCA datetime DEFAULT NULL,
  is_date_ask_OPCA tinyint NOT NULL DEFAULT 0,
  is_OPCA tinyint NOT NULL DEFAULT 0,
  fk_soc_OPCA int(11) DEFAULT NULL,
  fk_socpeople_OPCA int(11) DEFAULT NULL,
  num_OPCA_soc varchar(100) DEFAULT NULL,
  num_OPCA_file varchar(100) DEFAULT NULL,
  fk_user_author int(11) NOT NULL,
  datec datetime NOT NULL,
  fk_user_mod int(11) NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  archive tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY  (rowid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
