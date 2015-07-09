ALTER TABLE llx_agefodd_convention ADD COLUMN only_product_session integer DEFAULT 0 AFTER sig;
ALTER TABLE llx_agefodd_formation_catalogue ADD COLUMN color varchar(32) NULL AFTER certif_duration;