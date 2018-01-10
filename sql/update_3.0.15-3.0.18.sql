ALTER TABLE llx_agefodd_training_admlevel ADD COLUMN delais_alerte_end integer AFTER delais_alerte;
ALTER TABLE llx_agefodd_session_admlevel ADD COLUMN delais_alerte_end integer AFTER delais_alerte;
ALTER TABLE llx_agefodd_session_adminsitu MODIFY intitule varchar(150) default NULL;