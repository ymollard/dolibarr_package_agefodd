ALTER TABLE llx_agefodd_stagiaire_type MODIFY COLUMN intitule VARCHAR(255) NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_agefodd_stagiaire_type ALTER COLUMN intitule SET NOT NULL;

UPDATE llx_agefodd_stagiaire_type SET intitule='Pouvoirs publics pour la formation de publics spécifiques : Autres ressources publique' WHERE rowid=14;

ALTER TABLE llx_agefodd_formateur_type DROP COLUMN fk_user_mod;
ALTER TABLE llx_agefodd_formateur_type DROP COLUMN fk_user_author;
ALTER TABLE llx_agefodd_formateur_type DROP COLUMN datec;

ALTER TABLE llx_agefodd_stagiaire_type DROP COLUMN fk_user_mod;
ALTER TABLE llx_agefodd_stagiaire_type DROP COLUMN fk_user_author;
ALTER TABLE llx_agefodd_stagiaire_type DROP COLUMN datec;

ALTER TABLE llx_agefodd_formateur_category_dict DROP COLUMN fk_user_mod;
ALTER TABLE llx_agefodd_formateur_category_dict DROP COLUMN fk_user_author;
ALTER TABLE llx_agefodd_formateur_category_dict DROP COLUMN datec;

ALTER TABLE llx_agefodd_formation_catalogue_type MODIFY COLUMN intitule VARCHAR(1000) NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_agefodd_formation_catalogue_type ALTER COLUMN intitule SET NOT NULL;

ALTER TABLE llx_agefodd_formation_catalogue ADD COLUMN fk_c_category_bpf integer NULL AFTER fk_c_category;