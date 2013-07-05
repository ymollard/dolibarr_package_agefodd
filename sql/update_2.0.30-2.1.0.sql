--new admnlevel training table
CREATE TABLE IF NOT EXISTS llx_agefodd_training_admlevel (
  rowid integer NOT NULL auto_increment PRIMARY KEY,
  level_rank integer NOT NULL default 0,
  fk_training integer,
  fk_parent_level integer default 0,
  indice integer NOT NULL,
  intitule varchar(150) NOT NULL,
  delais_alerte integer NOT NULL,
  fk_user_author integer NOT NULL,
  datec datetime NOT NULL,
  fk_user_mod integer NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP
) ENGINE=InnoDB;

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
  tms timestamp NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS llx_agefodd_certif_state (
  rowid integer NOT NULL auto_increment PRIMARY KEY,
  fk_user_author integer default NULL,
  fk_user_mod integer NOT NULL,
  datec datetime NOT NULL,
  tms timestamp NOT NULL,
  fk_certif integer NOT NULL,
  fk_certif_type integer NOT NULL,
  certif_state integer default NULL,
  import_key		varchar(14)
) ENGINE=InnoDB;

ALTER TABLE llx_agefodd_stagiaire_type MODIFY datec datetime;

ALTER TABLE llx_agefodd_formation_catalogue ADD COLUMN note_private text AFTER fk_user_mod;
ALTER TABLE llx_agefodd_formation_catalogue ADD COLUMN note_public  text AFTER note_private;
