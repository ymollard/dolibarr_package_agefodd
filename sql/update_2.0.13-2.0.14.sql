ALTER TABLE llx_agefodd_session ADD COLUMN fk_soc int NULL AFTER rowid;
ALTER TABLE llx_agefodd_session ADD COLUMN color varchar(32) NULL AFTER notes;
ALTER TABLE llx_agefodd_session ADD COLUMN nb_place int NULL AFTER fk_session_place;
ALTER TABLE llx_agefodd_session ADD COLUMN nb_stagiaire int NULL AFTER nb_place;
ALTER TABLE llx_agefodd_session ADD COLUMN force_nb_stagiaire int NULL AFTER nb_stagiaire;

ALTER TABLE llx_agefodd_session ADD COLUMN cost_trip double(24,8) NULL AFTER cost_site;

ALTER TABLE table ADD INDEX fk_soc (fk_soc)

INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES ('', 'AC_AGF_CONVEN', 'system', 'Send Convention by mail', 'agefodd', 1, NULL, 10);
INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES ('', 'AC_AGF_CONVOC', 'system', 'Send Convocation by mail', 'agefodd', 1, NULL, 10);
INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES ('', 'AC_AGF_PEDAGO', 'system', 'Send Fiche pédagogique by mail', 'agefodd', 1, NULL, 10);
INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES ('', 'AC_AGF_ATTES', 'system', 'Send attestation by mail', 'agefodd', 1, NULL, 10);
