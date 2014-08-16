ALTER TABLE llx_agefodd_stagiaire_certif ADD COLUMN mark varchar(20) DEFAULT NULL AFTER certif_dt_warning;
ALTER TABLE llx_agefodd_opca ADD COLUMN fk_session_trainee integer DEFAULT NULL AFTER rowid;
ALTER TABLE llx_agefodd_session_formateur ADD COLUMN fk_agefodd_formateur_type integer AFTER fk_agefodd_formateur;
UPDATE llx_agefodd_formateur_type SET active=1;
UPDATE llx_agefodd_stagiaire_type SET active=1;

