ALTER TABLE llx_agefodd_session ADD COLUMN fk_product integer NULL AFTER nb_subscribe_min;
ALTER TABLE llx_agefodd_session ADD COLUMN status integer DEFAULT NULL AFTER archive;

UPDATE llx_agefodd_session SET status=4 WHERE archive=1; 
UPDATE llx_agefodd_session SET status=2 WHERE status IS NULL; 

