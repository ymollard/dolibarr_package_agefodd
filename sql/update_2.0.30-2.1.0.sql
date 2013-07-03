ALTER TABLE llx_agefodd_stagiaire ADD COLUMN import_key	varchar(14);
ALTER TABLE llx_agefodd_stagiaire ADD COLUMN date_birth datetime default NULL AFTER mail;
ALTER TABLE llx_agefodd_stagiaire ADD COLUMN place_birth  varchar(100) default NULL AFTER date_birth;
