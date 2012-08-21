ALTER TABLE llx_agefodd_reg_interieur ADD COLUMN reg_int text NULL AFTER file;
ALTER TABLE llx_agefodd_reg_interieur ADD COLUMN fk_user_author int(11) NOT NULL AFTER notes;
ALTER TABLE llx_agefodd_reg_interieur ADD COLUMN datec datetime NOT NULL AFTER fk_user_author;
ALTER TABLE llx_agefodd_reg_interieur ADD COLUMN fk_user_mod int(11) NOT NULL AFTER datec;
ALTER TABLE llx_agefodd_reg_interieur ADD COLUMN tms timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP AFTER fk_user_mod;
ALTER TABLE llx_agefodd_reg_interieur DROP COLUMN file;

ALTER TABLE llx_agefodd_place ADD COLUMN fk_reg_interieur int NULL AFTER archive;
ALTER TABLE llx_agefodd_reg_interieur ADD CONSTRAINT llx_agefodd_reg_interieur_ibfk_1 FOREIGN KEY (rowid) REFERENCES llx_agefodd_place ( fk_reg_interieur)  ON DELETE CASCADE;

ALTER TABLE llx_agefodd_session ADD COLUMN type_session int NULL AFTER fk_session_place;

ALTER TABLE llx_agefodd_session_calendrier ADD COLUMN fk_actioncomm int NOT NULL DEFAULT 0 AFTER heuref;
ALTER TABLE llx_agefodd_session_calendrier ADD CONSTRAINT llx_agefodd_session_calendrier_ibfk_2 FOREIGN KEY (fk_actioncomm) REFERENCES llx_actioncomm (id)  ON DELETE CASCADE;

ALTER TABLE llx_agefodd_session ADD COLUMN fk_soc int NULL AFTER rowid;
ALTER TABLE llx_agefodd_session ADD COLUMN color varchar(32) NULL AFTER notes;
ALTER TABLE llx_agefodd_session ADD COLUMN nb_place int NULL AFTER fk_session_place;
ALTER TABLE llx_agefodd_session ADD COLUMN nb_stagiaire int NULL AFTER nb_place;
ALTER TABLE llx_agefodd_session ADD COLUMN force_nb_stagiaire int NULL AFTER nb_stagiaire;
ALTER TABLE llx_agefodd_session ADD COLUMN cost_trip double(24,8) NULL AFTER cost_site;

ALTER TABLE llx_agefodd_session ADD INDEX fk_soc (fk_soc);

INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES ('', 'AC_AGF', 'system', 'Link to Training', 'agefodd', 1, NULL, 10);

ALTER TABLE llx_agefodd_stagiaire_type ADD COLUMN active int NULL AFTER sort;

