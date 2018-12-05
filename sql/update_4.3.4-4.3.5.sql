ALTER TABLE llx_agefodd_session_calendrier ADD COLUMN calendrier_type varchar(15) AFTER fk_actioncomm;
ALTER TABLE llx_agefodd_session_calendrier ADD COLUMN status integer DEFAULT 0 AFTER calendrier_type;
ALTER TABLE llx_agefodd_place ADD COLUMN nb_place integer NULL AFTER archive;
ALTER TABLE llx_agefodd_session_formateur_calendrier ADD COLUMN status integer DEFAULT 0 AFTER fk_actioncomm;
ALTER TABLE llx_agefodd_formation_catalogue ADD COLUMN nb_place integer NULL AFTER duree;
ALTER TABLE llx_agefodd_session_calendrier ADD COLUMN billed INT NOT NULL DEFAULT 0 AFTER status;
