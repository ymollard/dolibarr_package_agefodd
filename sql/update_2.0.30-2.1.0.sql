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
