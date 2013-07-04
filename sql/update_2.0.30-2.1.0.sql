ALTER TABLE llx_agefodd_stagiaire ADD COLUMN import_key	varchar(14);
ALTER TABLE llx_agefodd_stagiaire_certif ADD COLUMN import_key	varchar(14);
ALTER TABLE llx_agefodd_session_stagiaire ADD COLUMN import_key	varchar(14);
ALTER TABLE llx_agefodd_stagiaire ADD COLUMN import_key	varchar(14);
ALTER TABLE llx_agefodd_stagiaire ADD COLUMN date_birth datetime default NULL AFTER mail;
ALTER TABLE llx_agefodd_stagiaire ADD COLUMN place_birth  varchar(100) default NULL AFTER date_birth;

CREATE TABLE IF NOT EXISTS llx_agefodd_certificate_type (
  rowid integer NOT NULL auto_increment PRIMARY KEY,
  intitule varchar(80) NOT NULL,
  sort smallint NOT NULL,
  active integer NULL,
  datec datetime NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP,
  fk_user_author integer NOT NULL,
  fk_user_mod integer NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS llx_agefodd_certif_state (
  rowid integer NOT NULL auto_increment PRIMARY KEY,
  fk_user_author integer default NULL,
  fk_user_mod integer NOT NULL,
  datec datetime NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP,
  fk_certif integer NOT NULL,
  fk_certif_type integer NOT NULL,
  certif_state integer default NULL,
  import_key		varchar(14)
) ENGINE=InnoDB;
