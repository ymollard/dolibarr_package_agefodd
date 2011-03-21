-- ============================================================================
-- Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
-- Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
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
-- $Id: llx_agefodd_contact.sql 39 2011-03-14 09:47:59Z hregis $
-- ============================================================================

create table llx_agefodd_contact
(
	rowid				integer			AUTO_INCREMENT	PRIMARY KEY,
	fk_socpeople		integer			NOT NULL,
	fk_user_author		integer			NOT NULL,
	datec				datetime,
	fk_user_mod			integer			NOT NULL,
	tms 				timestamp
	
)ENGINE=innodb;
