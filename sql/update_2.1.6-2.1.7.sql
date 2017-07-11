ALTER TABLE llx_agefodd_session ADD COLUMN ref_ext varchar(50) DEFAULT NULL AFTER import_key;
ALTER TABLE llx_agefodd_session_stagiaire ADD INDEX idx_session_stagiaire_status (status_in_session);
ALTER TABLE llx_agefodd_session_stagiaire DROP FOREIGN KEY llx_agefodd_session_stagiaire_ibfk_3;
ALTER TABLE llx_agefodd_session MODIFY intitule_custo varchar(100) DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_agefodd_session ALTER COLUMN intitule_custo SET DEFAULT NULL;
ALTER TABLE llx_agefodd_formation_catalogue MODIFY COLUMN ref_interne varchar(100) DEFAULT NULL;
-- VPGSQL8.2 ALTER TABLE llx_agefodd_formation_catalogue ALTER COLUMN ref_interne SET DEFAULT NULL;
ALTER TABLE llx_agefodd_formation_catalogue MODIFY COLUMN intitule varchar(100) NOT NULL DEFAULT '';
-- VPGSQL8.2 ALTER TABLE llx_agefodd_formation_catalogue ALTER COLUMN intitule SET NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_agefodd_formation_catalogue ALTER COLUMN intitule SET DEFAULT '';


