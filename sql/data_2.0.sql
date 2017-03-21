INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(1, 'Financement par l''employeur (contrat pro.)', 2, 1, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(2, 'Financement par l''employeur', 1, 1, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(3, 'Dispositifs spécifiques pour les personnes en recherche d''emploi', 4, 1, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(4, 'Autre dispositifs (plan de formation, périodes de professionnalisation,...)', 5, 1, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(5, 'Compte personnel de formation (CPF)', 3, 1, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(6, 'Période PRO', 99, 0, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(7, 'Congés individuel de formation (CIF)', 2, 1, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(8, 'Fond d''assurance formation de non-salariés', 6, 1, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(9, 'Pouvoirs publics pour la formation de leurs agents', 7, 1, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(10, 'Pouvoirs publics pour la formation de publics spécifiques : Instances européenne', 8, 1, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(11, 'Pouvoirs publics pour la formation de publics spécifiques : Etat', 9, 1, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(12, ' Pouvoirs publics pour la formation de publics spécifiques : Conseils régionaux', 10, 1, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(13, 'Pouvoirs publics pour la formation de publics spécifiques : Pôle emploi', 11, 1, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(14, 'Pouvoirs publics pour la formation de publics spécifiques : Autres ressources publique', 12, 1, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(15, 'Contrats conclus avec des personnes à titre individuel et à leurs frais', 13, 1, now());
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(16, 'Contrats conclus avec d’autres organismes de formation', 14, 1, now());

INSERT INTO llx_agefodd_formateur_type (rowid,intitule, sort, active, tms) VALUES
(1,'Formateur interne', 0, 1,now()),
(2,'Formateur externe - Indépendant', 1, 1,now()),
(3,'Formateur externe - Salarié', 2, 1,now());

INSERT INTO llx_agefodd_session_admlevel(rowid, level_rank, fk_parent_level, indice, intitule, delais_alerte, fk_user_author, datec, fk_user_mod, tms) VALUES
(1, 0, 0, 100, 'Préparation de l''action', -40, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(2, 1, 1, 101, 'Inscription des stagiaires', -31, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(3, 0, 0, 200, 'Transmission de la convention de formation', -30, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(4, 1, 3, 201, 'Impression convention et vérification', -31, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(5, 1, 3, 202, 'Envoi convention (VP ou numérique avec AC)', -30, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(6, 0, 0, 300, 'Envoi des convocations', -15, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(7, 1, 6, 301, 'Préparation du dossier<br>(convoc., rég. intérieur, programme, fiche péda, conseils pratiques)', -15, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(8, 1, 6, 302, 'Envoi du dossier à chaque stagiaire (inter) ou au respo. formation (intra)', -15, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(9, 0, 0, 400, 'Vérifications et mise en place des moyens', -10, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(10, 1, 9, 401, 'Vérification du retour de la convention signée', -10, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(11, 0, 0, 500, 'Execution de la prestation', 0, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(12, 0, 0, 600, 'Cloture administrative', 8, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(13, 1, 12, 601, 'Impression des attestations', 8, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(14, 1, 12, 602, 'Creation de la facture et verification', 8, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(15, 1, 12, 603, 'Création du courrier d''accompagnement', 8, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(16, 1, 12, 604, 'Impression de la liasse administrative', 8, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(17, 1, 12, 605, 'Envoi de la liasse administrative', 8, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00');

DELETE FROM llx_c_actioncomm WHERE code LIKE 'AC_AGF%';

INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, position) VALUES (1030001, 'AC_AGF_SESS', 'agefodd', 'Link to Training', 'agefodd', 1, NULL, 10);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, position) VALUES (1030002, 'AC_AGF_CONVE', 'agefodd', 'Send Convention by mail', 'agefodd', 1, NULL, 20);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, position) VALUES (1030003, 'AC_AGF_CONVO', 'agefodd', 'Send Convocation by mail', 'agefodd', 1, NULL, 30);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, position) VALUES (1030004, 'AC_AGF_PEDAG', 'agefodd', 'Send Fiche pédagogique by mail', 'agefodd', 1, NULL, 40);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, position) VALUES (1030005, 'AC_AGF_PRES', 'agefodd', 'Send Fiche présence by mail', 'agefodd', 1, NULL, 50);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, position) VALUES (1030006, 'AC_AGF_ATTES', 'agefodd', 'Send attestation by mail', 'agefodd', 1, NULL, 60);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, position) VALUES (1030007, 'AC_AGF_CLOT', 'agefodd', 'Send dossier cloture by mail', 'agefodd', 1, NULL, 70);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, position) VALUES (1030008, 'AC_AGF_CONSE', 'agefodd', 'Send Advise document by mail', 'agefodd', 1, NULL, 80);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, position) VALUES (1030009, 'AC_AGF_ACCUE', 'agefodd', 'Send welcome document by mail', 'agefodd', 1, NULL, 90);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, position) VALUES (1030010, 'AC_AGF_SESST', 'agefodd', 'Link to Training for trainer', 'agefodd', 1, NULL, 15);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, position) VALUES (1030011, 'AC_AGF_MISTR', 'agefodd', 'Send mission trainer', 'agefodd', 1, NULL, 100);
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, position) VALUES (1030012, 'AC_AGF_DOCTR', 'agefodd', 'Send doc trainer', 'agefodd', 1, NULL, 100);

INSERT INTO llx_agefodd_calendrier (rowid, entity, day_session, heured, heuref, fk_user_author, datec, fk_user_mod, tms) VALUES (1, 1, 1, '09:00', '12:00', 1, '2013-10-13 19:23:12', 1, '2013-10-13 17:23:12');
INSERT INTO llx_agefodd_calendrier (rowid, entity, day_session, heured, heuref, fk_user_author, datec, fk_user_mod, tms) VALUES(2, 1, 1, '14:00', '18:00', 1, '2013-10-13 19:23:25', 1, '2013-10-13 17:23:25');
INSERT INTO llx_agefodd_calendrier (rowid, entity, day_session, heured, heuref, fk_user_author, datec, fk_user_mod, tms) VALUES (3, 1, 2, '09:00', '12:00', 1, '2013-10-13 19:23:12', 1, '2013-10-13 17:23:12');
INSERT INTO llx_agefodd_calendrier (rowid, entity, day_session, heured, heuref, fk_user_author, datec, fk_user_mod, tms) VALUES(4, 1, 2, '14:00', '18:00', 1, '2013-10-13 19:23:25', 1, '2013-10-13 17:23:25');
INSERT INTO llx_agefodd_calendrier (rowid, entity, day_session, heured, heuref, fk_user_author, datec, fk_user_mod, tms) VALUES (5, 1, 3, '09:00', '12:00', 1, '2013-10-13 19:23:12', 1, '2013-10-13 17:23:12');
INSERT INTO llx_agefodd_calendrier (rowid, entity, day_session, heured, heuref, fk_user_author, datec, fk_user_mod, tms) VALUES(6, 1, 3, '14:00', '18:00', 1, '2013-10-13 19:23:25', 1, '2013-10-13 17:23:25');
INSERT INTO llx_agefodd_calendrier (rowid, entity, day_session, heured, heuref, fk_user_author, datec, fk_user_mod, tms) VALUES (7, 1, 4, '09:00', '12:00', 1, '2013-10-13 19:23:12', 1, '2013-10-13 17:23:12');
INSERT INTO llx_agefodd_calendrier (rowid, entity, day_session, heured, heuref, fk_user_author, datec, fk_user_mod, tms) VALUES(8, 1, 4, '14:00', '18:00', 1, '2013-10-13 19:23:25', 1, '2013-10-13 17:23:25');

INSERT INTO llx_agefodd_session_status_type (rowid,code, intitule, sort, active, tms) VALUES
(1,'ENV', 'Envisagée', 1, 1, '2013-01-01 00:00:00' );
INSERT INTO llx_agefodd_session_status_type (rowid,code, intitule, sort, active, tms) VALUES
(2,'CONF', 'Confirmée client', 1, 1, '2013-01-01 00:00:00' );
INSERT INTO llx_agefodd_session_status_type (rowid,code, intitule, sort, active, tms) VALUES
(3,'NOT', 'Non réalisée', 1, 1, '2013-01-01 00:00:00' );
INSERT INTO llx_agefodd_session_status_type (rowid,code, intitule, sort, active, tms) VALUES
(4,'ARCH', 'Archivé', 1, 1, '2013-01-01 00:00:00' );
INSERT INTO llx_agefodd_session_status_type (rowid,code, intitule, sort, active, tms) VALUES
(5,'DONE', 'Réalisé', 1, 1, '2013-01-01 00:00:00' );
