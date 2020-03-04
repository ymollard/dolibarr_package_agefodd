-- ============================================================================
-- Copyright (C) 2012-2016 Florian Henry  <florian.henry@open-concept.pro>
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
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ============================================================================


CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_contact FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_convention FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_formateur FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_session_element FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_formation_catalogue FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_formation_objectifs_peda FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_opca FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_place FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_reg_interieur FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_session_adminsitu FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_session_admlevel FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_session_calendrier FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_session_commercial FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_session_contact FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_session_formateur FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_session_stagiaire FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_session FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_stagiaire_type FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_stagiaire FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_stagiaire_certif FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_certif_state FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_certificate_type FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_training_admlevel FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_convention_stagiaire FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_cursus FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_cursus_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_formation_catalogue_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();
CREATE TRIGGER update_customer_modtime BEFORE UPDATE ON llx_agefodd_session_extrafields FOR EACH ROW EXECUTE PROCEDURE update_modified_column_tms();

CREATE OR REPLACE FUNCTION TIMEDIFF (units VARCHAR(30), start_t TIMESTAMP, end_t TIMESTAMP) RETURNS INT AS $$ DECLARE diff_interval INTERVAL; diff INT = 0; years_diff INT = 0; BEGIN IF units IN ('yy', 'yyyy', 'year', 'mm', 'm', 'month') THEN years_diff = DATE_PART('year', end_t) - DATE_PART('year', start_t); IF units IN ('yy', 'yyyy', 'year') THEN RETURN years_diff; ELSE  RETURN years_diff * 12 + (DATE_PART('month', end_t) - DATE_PART('month', start_t)); END IF; END IF; diff_interval = end_t - start_t; diff = diff + DATE_PART('day', diff_interval); IF units IN ('wk', 'ww', 'week') THEN diff = diff/7; RETURN diff; END IF; IF units IN ('dd', 'd', 'day') THEN RETURN diff; END IF; diff = diff * 24 + DATE_PART('hour', diff_interval); IF units IN ('hh', 'hour') THEN RETURN diff; END IF; diff = diff * 60 + DATE_PART('minute', diff_interval); IF units IN ('mi', 'n', 'minute') THEN RETURN diff; END IF; diff = diff * 60 + DATE_PART('second', diff_interval); RETURN diff; END; $$ LANGUAGE plpgsql;
DROP FUNCTION TIME_TO_SEC(t int);
CREATE OR REPLACE FUNCTION TIME_TO_SEC(t int) RETURNS INTEGER AS $$ DECLARE hs INTEGER; ms INTEGER; s INTEGER; BEGIN SELECT (EXTRACT( HOUR FROM  to_timestamp(t)::time without time zone) * 60*60) INTO hs; SELECT (EXTRACT (MINUTES FROM to_timestamp(t)::time without time zone) * 60) INTO ms; SELECT (EXTRACT (SECONDS from to_timestamp(t)::time without time zone)) INTO s; SELECT (hs + ms + s) INTO s; RETURN s; END; $$ LANGUAGE plpgsql;
DROP FUNCTION time_to_hour(integer);
CREATE OR REPLACE FUNCTION TIME_TO_HOUR(t int) RETURNS INTEGER AS $$ DECLARE h INTEGER; BEGIN SELECT (EXTRACT( HOUR FROM  to_timestamp(t)::time without time zone)) INTO h; RETURN h; END; $$ LANGUAGE plpgsql;
