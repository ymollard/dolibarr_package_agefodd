DELETE FROM llx_c_actioncomm WHERE code='AC_AGF';

INSERT INTO llx_c_actioncomm (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES (1030001, 'AC_AGF_SESS', 'agefodd', 'Link to Training', 'agefodd', 1, NULL, 10);
INSERT INTO llx_c_actioncomm (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES (1030002, 'AC_AGF_CONVEN', 'agefodd', 'Send Convention by mail', 'agefodd', 1, NULL, 20);
INSERT INTO llx_c_actioncomm (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES (1030003, 'AC_AGF_CONVOC', 'agefodd', 'Send Convocation by mail', 'agefodd', 1, NULL, 30);
INSERT INTO llx_c_actioncomm (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES (1030004, 'AC_AGF_PEDAGO', 'agefodd', 'Send Fiche pédagogique by mail', 'agefodd', 1, NULL, 40);
INSERT INTO llx_c_actioncomm (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES (1030005, 'AC_AGF_PRES', 'agefodd', 'Send Fiche présence by mail', 'agefodd', 1, NULL, 50);
INSERT INTO llx_c_actioncomm (`id`, `code`, `type`, `libelle`, `module`, `active`, `todo`, `position`) VALUES (1030006, 'AC_AGF_ATTES', 'agefodd', 'Send attestation by mail', 'agefodd', 1, NULL, 60);

ALTER TABLE llx_agefodd_stagiaire_type ADD COLUMN active int NULL AFTER sort;

ALTER TABLE  llx_agefodd_formation_catalogue ADD COLUMN but tinytext NULL AFTER prerequis;

ALTER TABLE llx_actioncomm MODIFY elementtype VARCHAR(32);

UPDATE llx_const SET value='2.0.13' WHERE name='AGF_LAST_VERION_INSTALL';
