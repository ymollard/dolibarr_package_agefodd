ALTER TABLE llx_agefodd_stagiaire_type ADD COLUMN active int NULL AFTER sort;

ALTER TABLE llx_agefodd_formation_catalogue ADD COLUMN but tinytext NULL AFTER prerequis;

ALTER TABLE llx_actioncomm MODIFY elementtype VARCHAR(32);
