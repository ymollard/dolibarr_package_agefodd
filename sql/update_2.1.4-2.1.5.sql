ALTER TABLE llx_agefodd_session ADD COLUMN fk_product integer NULL AFTER nb_subscribe_min;
ALTER TABLE llx_agefodd_session ADD COLUMN status varchar(30) DEFAULT NULL AFTER archive;

