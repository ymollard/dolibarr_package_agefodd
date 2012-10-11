ALTER TABLE llx_agefodd_formateur MODIFY fk_socpeople int(11);
ALTER TABLE llx_agefodd_formateur ADD COLUMN fk_user int(11) AFTER fk_socpeople;
ALTER TABLE llx_agefodd_formateur ADD COLUMN type_trainer varchar(20) AFTER fk_user;
ALTER TABLE llx_agefodd_formateur DROP FOREIGN KEY llx_agefodd_formateur_ibfk_1;
ALTER TABLE llx_agefodd_formateur ADD INDEX idx_agefodd_formateur_fk_user (fk_user);
UPDATE llx_agefodd_formateur SET type_trainer='socpeople' WHERE fk_socpeople IS NOT NULL;
