ALTER TABLE llx_agefodd_session_stagiaire ADD COLUMN fk_soc integer NOT NULL;

ALTER TABLE llx_agefodd_session_stagiaire ADD INDEX fk_session_soc_sta (fk_soc);


UPDATE llx_agefodd_session_stagiaire ass INNER JOIN llx_agefodd_stagiaire sta ON ( ass.fk_stagiaire = sta.rowid) SET ass.fk_soc = sta.fk_soc;
INSERT INTO llx_agefodd_stagiaire_soc_history (fk_user_creat, datec, fk_stagiaire, fk_soc, date_start) SELECT fk_user_author, NOW(), rowid, fk_soc, NOW() FROM llx_agefodd_stagiaire;
