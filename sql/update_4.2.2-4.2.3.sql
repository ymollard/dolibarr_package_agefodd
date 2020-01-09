ALTER TABLE llx_agefodd_session_adminsitu ADD COLUMN delais_alerte_end integer NOT NULL DEFAULT 0;

UPDATE llx_agefodd_session_admlevel SET delais_alerte_end=0 WHERE delais_alerte_end IS NULL;

UPDATE llx_agefodd_training_admlevel SET delais_alerte_end=0 WHERE delais_alerte_end IS NULL;

-- VMYSQL4.1 ALTER TABLE llx_agefodd_training_admlevel MODIFY COLUMN delais_alerte_end integer NOT NULL DEFAULT 0;
-- VPGSQL8.2 ALTER TABLE llx_agefodd_training_admlevel ALTER COLUMN delais_alerte_end SET NOT NULL DEFAULT 0;

-- VMYSQL4.1 ALTER TABLE llx_agefodd_session_admlevel MODIFY COLUMN delais_alerte_end integer NOT NULL DEFAULT 0;
-- VPGSQL8.2 ALTER TABLE llx_agefodd_session_admlevel ALTER COLUMN delais_alerte_end SET NOT NULL DEFAULT 0;

UPDATE llx_agefodd_training_admlevel AS dest, llx_agefodd_session_admlevel AS src SET dest.delais_alerte_end = src.delais_alerte_end WHERE dest.fk_agefodd_training_admlevel = src.rowid;

UPDATE llx_agefodd_session_adminsitu AS dest, llx_agefodd_training_admlevel AS src SET dest.delais_alerte_end = src.delais_alerte_end WHERE dest.fk_agefodd_session_admlevel = src.rowid;

