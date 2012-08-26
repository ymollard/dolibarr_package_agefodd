ALTER TABLE llx_agefodd_session ADD COLUMN fk_soc int NULL AFTER rowid;
ALTER TABLE llx_agefodd_session ADD COLUMN color varchar(32) NULL AFTER notes;
ALTER TABLE llx_agefodd_session ADD COLUMN nb_place int NULL AFTER fk_session_place;
ALTER TABLE llx_agefodd_session ADD COLUMN nb_stagiaire int NULL AFTER nb_place;
ALTER TABLE llx_agefodd_session ADD COLUMN force_nb_stagiaire int NULL AFTER nb_stagiaire;

ALTER TABLE llx_agefodd_session ADD COLUMN cost_trip double(24,8) NULL AFTER cost_site;

ALTER TABLE table ADD INDEX fk_soc (fk_soc)

INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES ('', 'AC_AGF_CONVEN', 'agefodd', 'Send Convention by mail', 'agefodd', 1, NULL, 10);
INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES ('', 'AC_AGF_CONVOC', 'agefodd', 'Send Convocation by mail', 'agefodd', 1, NULL, 20);
INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES ('', 'AC_AGF_PEDAGO', 'agefodd', 'Send Fiche pédagogique by mail', 'agefodd', 1, NULL, 30);
INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES ('', 'AC_AGF_PRES', 'agefodd', 'Send Fiche présence by mail', 'agefodd', 1, NULL, 40);
INSERT INTO `llx_c_actioncomm` (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES ('', 'AC_AGF_ATTES', 'agefodd', 'Send attestation by mail', 'agefodd', 1, NULL, 50);
