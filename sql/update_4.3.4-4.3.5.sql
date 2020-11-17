ALTER TABLE llx_agefodd_session_calendrier ADD COLUMN calendrier_type varchar(15);
ALTER TABLE llx_agefodd_session_calendrier ADD COLUMN status integer DEFAULT 0;
ALTER TABLE llx_agefodd_place ADD COLUMN nb_place integer NULL;
ALTER TABLE llx_agefodd_session_formateur_calendrier ADD COLUMN status integer DEFAULT 0;
ALTER TABLE llx_agefodd_formation_catalogue ADD COLUMN nb_place integer NULL;
ALTER TABLE llx_agefodd_session_calendrier ADD COLUMN billed integer NOT NULL DEFAULT 0;
