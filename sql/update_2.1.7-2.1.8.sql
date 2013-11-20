ALTER TABLE llx_agefodd_session_stagiaire ADD COLUMN dt_acknowledgement timestamp NULL;

INSERT INTO llx_agefodd_session_element (fk_session_agefodd, fk_soc ,element_type,fk_element,fk_user_author,datec,fk_user_mod,tms) SELECT llx_agefodd_facture.fk_session, llx_agefodd_facture.fk_societe,'propal',llx_agefodd_facture.fk_propal,llx_agefodd_facture.fk_user_author,llx_agefodd_facture.datec,llx_agefodd_facture.fk_user_mod,llx_agefodd_facture.tms FROM llx_agefodd_facture INNER JOIN llx_propal ON llx_agefodd_facture.fk_propal=llx_propal.rowid;

INSERT INTO llx_agefodd_session_element (fk_session_agefodd, fk_soc ,element_type,fk_element,fk_user_author,datec,fk_user_mod,tms) SELECT llx_agefodd_facture.fk_session, llx_agefodd_facture.fk_societe,'order',llx_agefodd_facture.fk_commande,llx_agefodd_facture.fk_user_author,llx_agefodd_facture.datec,llx_agefodd_facture.fk_user_mod,llx_agefodd_facture.tms FROM llx_agefodd_facture INNER JOIN llx_commande ON llx_agefodd_facture.fk_commande=llx_commande.rowid;

INSERT INTO llx_agefodd_session_element (fk_session_agefodd, fk_soc ,element_type,fk_element,fk_user_author,datec,fk_user_mod,tms) SELECT llx_agefodd_facture.fk_session, llx_agefodd_facture.fk_societe,'invoice',llx_agefodd_facture.fk_facture,llx_agefodd_facture.fk_user_author,llx_agefodd_facture.datec,llx_agefodd_facture.fk_user_mod,llx_agefodd_facture.tms FROM llx_agefodd_facture INNER JOIN llx_facture ON llx_agefodd_facture.fk_facture=llx_facture.rowid;

--DROP TABLE llx_agefodd_facture;

