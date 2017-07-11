ALTER TABLE llx_agefodd_formation_catalogue ADD COLUMN import_key varchar(36) DEFAULT NULL AFTER tms;
-- VPGSQL8.2 ALTER TABLE llx_agefodd_formation_catalogue ALTER COLUMN import_key SET DEFAULT NULL;
ALTER TABLE llx_agefodd_stagiaire MODIFY COLUMN fonction varchar(80);
ALTER TABLE llx_agefodd_place ADD COLUMN fk_socpeople integer AFTER fk_societe;
ALTER TABLE llx_agefodd_place ADD COLUMN timeschedule text AFTER fk_socpeople;
ALTER TABLE llx_agefodd_place ADD COLUMN control_occupation integer AFTER timeschedule;
ALTER TABLE llx_agefodd_session_stagiaire ADD COLUMN fk_socpeople_sign integer AFTER fk_soc_link;
ALTER TABLE llx_agefodd_session_element MODIFY COLUMN fk_sub_element integer DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_agefodd_session_element ALTER COLUMN fk_sub_element SET DEFAULT NULL;