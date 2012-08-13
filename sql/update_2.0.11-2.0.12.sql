ALTER TABLE llx_agefodd_reg_interieur ADD COLUMN reg_int text NULL AFTER file;
ALTER TABLE llx_agefodd_reg_interieur ADD COLUMN fk_user_author int(11) NOT NULL AFTER notes;
ALTER TABLE llx_agefodd_reg_interieur ADD COLUMN datec datetime NOT NULL AFTER fk_user_author;
ALTER TABLE llx_agefodd_reg_interieur ADD COLUMN fk_user_mod int(11) NOT NULL AFTER datec;
ALTER TABLE llx_agefodd_reg_interieur ADD COLUMN tms timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP AFTER fk_user_mod;
ALTER TABLE llx_agefodd_reg_interieur DROP COLUMN file;

ALTER TABLE llx_agefodd_place ADD COLUMN fk_reg_interieur int NULL AFTER archive;
ALTER TABLE llx_agefodd_place ADD CONSTRAINT llx_agefodd_place_ibfk_2 FOREIGN KEY (fk_reg_interieur) REFERENCES llx_agefodd_reg_interieur (rowid);
