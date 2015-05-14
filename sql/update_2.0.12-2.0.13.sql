ALTER TABLE llx_agefodd_stagiaire_type ADD COLUMN active integer AFTER sort;

ALTER TABLE llx_agefodd_formation_catalogue ADD COLUMN but text AFTER prerequis;

ALTER TABLE llx_actioncomm MODIFY elementtype VARCHAR(32);
