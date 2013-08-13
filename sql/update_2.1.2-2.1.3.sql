ALTER TABLE llx_agefodd_session ADD COLUMN nb_subscribe_min integer NULL AFTER force_nb_stagiaire;
UPDATE llx_agefodd_session SET nb_subscribe_min=nb_subscribe_min;
ALTER TABLE llx_agefodd_session DROP COLUMN nb_subscribe_min;

