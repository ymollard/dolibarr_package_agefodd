ALTER TABLE llx_agefodd_session_formateur ADD COLUMN trainer_status integer DEFAULT NULL AFTER fk_agefodd_formateur;
ALTER TABLE llx_agefodd_session_formateur_calendrier ADD COLUMN trainer_cost  real DEFAULT NULL AFTER heuref;
ALTER TABLE llx_agefodd_session_formateur_calendrier ADD COLUMN trainer_status integer DEFAULT NULL AFTER trainer_cost;

