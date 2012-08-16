ALTER TABLE llx_agefodd_session ADD COLUMN fk_soc int NULL AFTER rowid;
ALTER TABLE llx_agefodd_session ADD COLUMN color varchar(32) NULL AFTER notes;
ALTER TABLE llx_agefodd_session ADD COLUMN nb_place int NULL AFTER fk_session_place;
ALTER TABLE llx_agefodd_session ADD COLUMN nb_stagiaire int NULL AFTER nb_place;
ALTER TABLE llx_agefodd_session ADD COLUMN force_nb_stagiaire int NULL AFTER nb_stagiaire;

ALTER TABLE llx_agefodd_session ADD COLUMN cost_trip double(24,8) NULL AFTER cost_site;

ALTER TABLE table ADD INDEX fk_soc (fk_soc)
