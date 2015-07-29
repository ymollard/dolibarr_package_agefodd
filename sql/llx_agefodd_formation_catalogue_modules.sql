-- ========================================================================
-- Copyright (C) 2015 Florian HENRY	<florian.henry@open-concept.pro>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ========================================================================

CREATE TABLE IF NOT EXISTS llx_agefodd_formation_catalogue_modules (
  rowid integer AUTO_INCREMENT PRIMARY KEY,
  entity integer NOT NULL DEFAULT '1',
  fk_formation_catalogue integer NOT NULL,
  sort_order integer NOT NULL DEFAULT '1',
  title varchar(200) NOT NULL,
  content_text text,
  duration double,
  obj_peda text,
  status integer NOT NULL DEFAULT '1',
  import_key varchar(100) DEFAULT NULL,
  fk_user_author integer NOT NULL,
  datec datetime NOT NULL,
  fk_user_mod integer NOT NULL,
  tms timestamp
) ENGINE=InnoDB;