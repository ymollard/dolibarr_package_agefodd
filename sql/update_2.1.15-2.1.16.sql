ALTER TABLE llx_agefodd_session_element ADD COLUMN fk_sub_element integer AFTER fk_element;
ALTER TABLE llx_agefodd_stagiaire MODIFY fonction varchar(80);