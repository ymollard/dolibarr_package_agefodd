ALTER TABLE llx_agefodd_session_stagiaire ADD COLUMN dt_acknowledgement timestamp NULL;

ALTER TABLE llx_agefodd_session_element ADD COLUMN fk_soc integer NOT NULL DEFAULT 0 AFTER fk_session_agefodd;

INSERT INTO llx_agefodd_session_element (fk_session_agefodd, fk_soc ,element_type,fk_element,fk_user_author,datec,fk_user_mod,tms) SELECT llx_agefodd_facture.fk_session, llx_agefodd_facture.fk_societe,'propal',llx_agefodd_facture.fk_propal,llx_agefodd_facture.fk_user_author,llx_agefodd_facture.datec,llx_agefodd_facture.fk_user_mod,llx_agefodd_facture.tms FROM llx_agefodd_facture INNER JOIN llx_propal ON llx_agefodd_facture.fk_propal=llx_propal.rowid;

INSERT INTO llx_agefodd_session_element (fk_session_agefodd, fk_soc ,element_type,fk_element,fk_user_author,datec,fk_user_mod,tms) SELECT llx_agefodd_facture.fk_session, llx_agefodd_facture.fk_societe,'order',llx_agefodd_facture.fk_commande,llx_agefodd_facture.fk_user_author,llx_agefodd_facture.datec,llx_agefodd_facture.fk_user_mod,llx_agefodd_facture.tms FROM llx_agefodd_facture INNER JOIN llx_commande ON llx_agefodd_facture.fk_commande=llx_commande.rowid;

INSERT INTO llx_agefodd_session_element (fk_session_agefodd, fk_soc ,element_type,fk_element,fk_user_author,datec,fk_user_mod,tms) SELECT llx_agefodd_facture.fk_session, llx_agefodd_facture.fk_societe,'invoice',llx_agefodd_facture.fk_facture,llx_agefodd_facture.fk_user_author,llx_agefodd_facture.datec,llx_agefodd_facture.fk_user_mod,llx_agefodd_facture.tms FROM llx_agefodd_facture INNER JOIN llx_facture ON llx_agefodd_facture.fk_facture=llx_facture.rowid;

--DROP TABLE llx_agefodd_facture;

ALTER TABLE llx_agefodd_convention ADD COLUMN element_type varchar(50) DEFAULT NULL AFTER fk_societe;
ALTER TABLE llx_agefodd_convention ADD COLUMN fk_element integer DEFAULT NULL AFTER element_type;
ALTER TABLE llx_agefodd_convention ADD COLUMN model_doc	varchar(200) DEFAULT NULL AFTER fk_element;
UPDATE llx_agefodd_convention SET model_doc='pdf_convention' WHERE model_doc IS NULL; 

ALTER TABLE llx_agefodd_session_formateur ADD COLUMN trainer_status integer DEFAULT NULL AFTER fk_agefodd_formateur;
ALTER TABLE llx_agefodd_session_formateur_calendrier ADD COLUMN trainer_status integer DEFAULT NULL AFTER trainer_cost;

TRUNCATE TABLE llx_agefodd_session_status_type;
INSERT INTO llx_agefodd_session_status_type (rowid,code, intitule, sort, active, tms) VALUES (1,'ENV', 'Envisagée', 1, 1, '2013-01-01 00:00:00' );
INSERT INTO llx_agefodd_session_status_type (rowid,code, intitule, sort, active, tms) VALUES (2,'CONF', 'Confirmée', 1, 1, '2013-01-01 00:00:00' );
INSERT INTO llx_agefodd_session_status_type (rowid,code, intitule, sort, active, tms) VALUES (3,'NOT', 'Non réalisée', 1, 1, '2013-01-01 00:00:00' );
INSERT INTO llx_agefodd_session_status_type (rowid,code, intitule, sort, active, tms) VALUES (4,'ARCH', 'Archivé', 1, 1, '2013-01-01 00:00:00' );

UPDATE llx_agefodd_session SET status=1 WHERE status=2;
UPDATE llx_agefodd_session SET status=2 WHERE status=3;
UPDATE llx_agefodd_session SET status=2 WHERE status=4;
UPDATE llx_agefodd_session SET status=4 WHERE archive=1;

ALTER TABLE llx_agefodd_session DROP COLUMN archive;

ALTER TABLE llx_agefodd_session ADD INDEX idx_agefodd_session_status (status);

ALTER TABLE llx_agefodd_session MODIFY COLUMN duree_session real NOT NULL DEFAULT 0;
-- VPGSQL8.2 ALTER TABLE llx_agefodd_session ALTER COLUMN intitule SET NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_agefodd_session ALTER COLUMN intitule SET DEFAULT 0;

ALTER TABLE llx_agefodd_formation_catalogue MODIFY COLUMN duree real NOT NULL DEFAULT 0;
-- VPGSQL8.2 ALTER TABLE llx_agefodd_session ALTER COLUMN duree SET NOT NULL;
-- VPGSQL8.2 ALTER TABLE llx_agefodd_session ALTER COLUMN duree SET DEFAULT 0;

ALTER TABLE llx_agefodd_session ADD COLUMN is_date_res_confirm_site smallint NOT NULL DEFAULT 0 AFTER date_res_site;
ALTER TABLE llx_agefodd_session ADD COLUMN date_res_confirm_site datetime DEFAULT NULL AFTER is_date_res_confirm_site;

ALTER TABLE llx_agefodd_session ADD COLUMN fk_soc_requester int NULL AFTER fk_soc;
ALTER TABLE llx_agefodd_session ADD COLUMN fk_socpeople_requester int NULL AFTER fk_soc_requester;
ALTER TABLE llx_agefodd_session ADD INDEX fk_soc_requester_session (fk_soc_requester);

ALTER TABLE llx_agefodd_session_stagiaire ADD COLUMN fk_soc_link integer NULL AFTER fk_agefodd_stagiaire_type;
ALTER TABLE llx_agefodd_session_stagiaire ADD COLUMN fk_soc_requester integer NULL AFTER fk_soc_link;

ALTER TABLE llx_agefodd_training_admlevel ADD COLUMN trigger_name varchar(150) NULL;
ALTER TABLE llx_agefodd_session_admlevel ADD COLUMN trigger_name varchar(150) NULL;
ALTER TABLE llx_agefodd_session_adminsitu ADD COLUMN trigger_name varchar(150) NULL;
ALTER TABLE llx_agefodd_training_admlevel ADD INDEX idx_agefodd_training_admlevel_fk_parent_level (fk_parent_level);
ALTER TABLE llx_agefodd_session_admlevel ADD INDEX idx_agefodd_session_admlevel_fk_parent_level (fk_parent_level);
ALTER TABLE llx_agefodd_session_adminsitu ADD INDEX idx_agefodd_session_adminsitu_fk_parent_level (fk_parent_level);

 

