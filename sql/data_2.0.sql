INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(1, 'Financement par l''employeur (contrat pro.)', 2, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(2, 'Financement par l''employeur', 1, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(3, 'Dispositifs spécifiques pour les personnes en recherche d emploi financement publique', 4, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(4, 'Autre dispositifs (plan de formation, périodes de professionnalisation,...)', 5, 0, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(5, 'Compte personnel de formation (CPF)', 3, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(6, 'Période PRO', 99, 0, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(7, 'Congés individuel de formation (CIF)', 2, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(8, 'Fond d''assurance formation de non-salariés', 6, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(9, 'Pouvoirs publics pour la formation de leurs agents', 7, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(10, 'Pouvoirs publics pour la formation de publics spécifiques : Instances européenne', 8, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(11, 'Pouvoirs publics pour la formation de publics spécifiques : Etat', 9, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(12, 'Pouvoirs publics pour la formation de publics spécifiques : Conseils régionaux', 10, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(13, 'Pouvoirs publics pour la formation de publics spécifiques : Pôle emploi', 11, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(14, 'Pouvoirs publics pour la formation de publics spécifiques : Autres ressources publique', 12, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(15, 'Contrats conclus avec des personnes à titre individuel et à leurs frais', 13, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(16, 'Contrats conclus avec d’autres organismes de formation', 14, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(17, 'Dispositifs spécifiques pour les personnes en recherche d''emploi financement OPCO', 4, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(18, 'Contrats d''apprentissage', 2, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(19, 'Alternance', 3, 1, '2017-03-23 12:00:00');
INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, sort, active, tms) VALUES
(20, 'Plan de développement des compétences ou autres dispositifs', 5, 1, '2017-03-23 12:00:00');
-- From new 2020 BPF
UPDATE llx_agefodd_stagiaire_type SET active=0 WHERE rowid=4 AND intitule LIKE 'Autre dispositifs (plan de formation%';
UPDATE llx_agefodd_session_stagiaire SET fk_agefodd_stagiaire_type=20 WHERE fk_agefodd_stagiaire_type=4;


INSERT INTO llx_agefodd_formateur_type (rowid,intitule, sort, active, tms) VALUES
(1,'Formateur interne', 0, 1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formateur_type (rowid,intitule, sort, active, tms) VALUES
(2,'Formateur externe - Indépendant', 1, 1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formateur_type (rowid,intitule, sort, active, tms) VALUES
(3,'Formateur externe - Salarié', 2, 1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formateur_type (rowid,intitule, sort, active, tms) VALUES
(4,'Formateur externe - Sous traintance', 2, 1,'2017-03-23 12:00:00');

INSERT INTO llx_agefodd_session_admlevel(rowid, level_rank, fk_parent_level, indice, intitule, delais_alerte, delais_alerte_end, fk_user_author, datec, fk_user_mod, tms) VALUES
(1, 0, 0, 100, 'Préparation de l''action', -40, 0, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(2, 1, 1, 101, 'Inscription des stagiaires', -31, 0, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(3, 0, 0, 200, 'Transmission de la convention de formation', -30, 0, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(4, 1, 3, 201, 'Impression convention et vérification', -31, 0, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(5, 1, 3, 202, 'Envoi convention (VP ou numérique avec AC)', -30, 0, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(6, 0, 0, 300, 'Envoi des convocations', -15, 0, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(7, 1, 6, 301, 'Préparation du dossier<br>(convoc., rég. intérieur, programme, fiche péda, conseils pratiques)', -15, 0, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(8, 1, 6, 302, 'Envoi du dossier à chaque stagiaire (inter) ou au respo. formation (intra)', -15, 0, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(9, 0, 0, 400, 'Vérifications et mise en place des moyens', -10, 0, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(10, 1, 9, 401, 'Vérification du retour de la convention signée', -10, 0, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(11, 0, 0, 500, 'Execution de la prestation', 0, 0, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(12, 0, 0, 600, 'Cloture administrative', 0, 8, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(13, 1, 12, 601, 'Impression des attestations', 0, 8, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(14, 1, 12, 602, 'Creation de la facture et verification', 0, 8, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(15, 1, 12, 603, 'Création du courrier d''accompagnement', 0, 8, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(16, 1, 12, 604, 'Impression de la liasse administrative', 0, 8, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00'),
(17, 1, 12, 605, 'Envoi de la liasse administrative', 0, 8, 1, '2012-01-01 00:00:00', 0, '2012-01-01 00:00:00');

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
INSERT INTO llx_c_actioncomm (id, code, type, libelle, module, active, todo, position) VALUES (1030013, 'AC_AGF_ATTEP', 'agefodd', 'Send attestation présence by mail', 'agefodd', 1, NULL, 60);

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
(2,'CONF', 'Confirmée client', 2, 1, '2013-01-01 00:00:00' );
INSERT INTO llx_agefodd_session_status_type (rowid,code, intitule, sort, active, tms) VALUES
(3,'NOT', 'Non réalisée', 6, 1, '2013-01-01 00:00:00' );
INSERT INTO llx_agefodd_session_status_type (rowid,code, intitule, sort, active, tms) VALUES
(4,'ARCH', 'Archivée', 7, 1, '2013-01-01 00:00:00' );
INSERT INTO llx_agefodd_session_status_type (rowid,code, intitule, sort, active, tms) VALUES
(5,'DONE', 'Réalisée', 5, 1, '2013-01-01 00:00:00' );
INSERT INTO llx_agefodd_session_status_type (rowid,code, intitule, sort, active, tms) VALUES
(6,'ONGOING', 'En cours', 4, 1, '2013-01-01 00:00:00' );


INSERT INTO llx_agefodd_formation_catalogue_type_bpf (rowid,code,intitule,sort,active,tms) VALUES
(1, 'F3a1', 'Formations visant un diplôme ou un titre à finalité professionnelle (hors certificat de qualification professionnelle) inscrit au Répertoire national des certifications professionnelles (RNCP)', 0, 1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type_bpf (rowid,code,intitule,sort,active,tms) VALUES
(2, 'F3a2', 'dont de niveau I et II (licence, maîtrise, master, DEA, DESS, diplôme d’ingénieur)', 1, 1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type_bpf (rowid,code,intitule,sort,active,tms) VALUES
(3, 'F3a3', 'dont de niveau III (BTS, DUT, écoles de formation sanitaire et sociale ...)', 2, 1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type_bpf (rowid,code,intitule,sort,active,tms) VALUES
(4, 'F3a4', 'dont de niveau IV (BAC professionnel, BT, BP, BM...)', 3, 1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type_bpf (rowid,code,intitule,sort,active,tms) VALUES
(5, 'F3a5', 'dont de niveau V (BEP, CAP ou CFPA 1 er degré...)', 4, 1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type_bpf (rowid,code,intitule,sort,active,tms) VALUES
(6, 'F3b', 'Formations visant un certificat de qualification professionnelle (CQP)', 5, 1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type_bpf (rowid,code,intitule,sort,active,tms) VALUES
(7, 'F3c', 'Formations visant une certification et/ou une habilitation inscrite à l’inventaire de la CNCP', 6, 1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type_bpf (rowid,code,intitule,sort,active,tms) VALUES
(8, 'F3d', 'Autres formations professionnelles continues', 7, 1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type_bpf (rowid,code,intitule,sort,active,tms) VALUES
(9, 'F3e', 'Bilans de compétence', 8, 1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type_bpf (rowid,code,intitule,sort,active,tms) VALUES
(10, 'F3f', 'Actions d’accompagnement à la validation des acquis de l’expérience', 9, 1,'2017-03-23 12:00:00');

INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (1,'100','Formations générales',1,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (2,'110','Spécialités pluriscientifiques',2,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (3,'111','Physique-chimie',3,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (4,'112','Chimie-biologie, biochimie',4,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (5,'113','Sciences naturelles (biologie-géologie)',5,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (6,'114','Mathématiques',6,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (7,'115','Physique',7,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (8,'116','Chimie',8,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (9,'117','Sciences de la terre',9,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (10,'118','Sciences de la vie',10,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (11,'120','Spécialités pluridisciplinaires, sciences humaines et droit',11,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (12,'121','Géographie',12,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (13,'122','Economie',13,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (14,'123','Sciences sociales (y compris démographie, anthropologie)',14,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (15,'124','Psychologie',15,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (16,'125','Linguistique',16,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (17,'126','Histoire',17,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (18,'127','Philosophie, éthique et théologie',18,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (19,'128','Droit, sciences politiques',19,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (20,'130','Spécialités littéraires et artistiques plurivalentes',20,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (21,'131','Français, littérature et civilisation française',21,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (22,'132','Arts plastiques',22,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (23,'133','Musique, arts du spectacle',23,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (24,'134','Autres disciplines artistiques et spécialités artistiques plurivalentes',24,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (25,'135','Langues et civilisations anciennes',25,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (26,'136','Langues vivantes, civilisations étrangères et régionales',26,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (27,'200','Technologies industrielles fondamentales (génie industriel, procédés de Transformation, spécialités à dominante fonctionnelle)',27,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (28,'201','Technologies de commandes des transformations industriels (automatismes et robotique industriels, informatique industrielle)',28,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (29,'210','Spécialités plurivalentes de l''agronomie et de l''agriculture',29,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (30,'211','Productions végétales, cultures spécialisées (horticulture, viticulture,arboriculture fruitière…)',30,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (31,'212','Productions animales, élevage spécialisé, aquaculture, soins aux animaux,y compris vétérinaire',31,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (32,'213','Forêts, espaces naturels, faune sauvage, pêche',32,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (33,'214','Aménagement paysager (parcs, jardins, espaces verts ...)',33,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (34,'220','Spécialités pluritechnologiques des transformations',34,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (35,'221','Agro-alimentaire, alimentation, cuisine',35,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (36,'222','Transformations chimiques et apparentées (y compris industrie pharmaceutique)',36,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (37,'223','Métallurgie (y compris sidérurgie, fonderie, non ferreux...)',37,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (38,'224','Matériaux de construction, verre, céramique',38,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (39,'225','Plasturgie, matériaux composites',39,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (40,'226','Papier, carton',40,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (41,'227','Energie, génie climatique (y compris énergie nucléaire, thermique, hydraulique ;',41,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (42,'230','Spécialités génie civil, , pluritechnologiques, construction, bois',42,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (43,'231','Mines et carrières, génie civil, topographie',43,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (44,'232','Bâtiment : construction et couverture',44,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (45,'233','Bâtiment : finitions',45,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (46,'234','Travail du bois et de l''ameublement',46,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (47,'240','Spécialités pluritechnologiques matériaux souples',47,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (48,'241','Textile',48,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (49,'242','Habillement (y compris mode, couture)',49,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (50,'243','Cuirs et peaux',50,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (51,'250','Spécialités pluritechnologiques mécanique-électricité (y compris maintenance mécano-électrique)',51,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (52,'251','Mécanique générale et de précision, usinage',52,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (53,'252','Moteurs et mécanique auto',53,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (54,'253','Mécanique aéronautique et spatiale',54,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (55,'254','Structures métalliques (y compris soudure, carrosserie, coque bateau, cellule, avion',55,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (56,'255','Electricité, électronique (non compris automatismes, productique)',56,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (57,'300','Spécialités plurivalentes des services',57,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (58,'310','Spécialités plurivalentes des échanges et de la gestion (y compris administration générale des entreprises et des collectivités)',58,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (59,'311','Transports, manutention, magasinage',59,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (60,'312','Commerce, vente',60,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (61,'313','Finances, banque, assurances',61,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (62,'314','Comptabilité, gestion',62,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (63,'315','Ressources humaines, gestion du personnel, gestion de l''emploi',63,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (64,'320','Spécialités plurivalentes de la communication',64,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (65,'321','Journalisme, communication (y compris communication graphique et publicité)',65,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (66,'322','Techniques de l''imprimerie et de l''édition',66,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (67,'323','Techniques de l''image et du son, métiers connexes du spectacle',67,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (68,'324','Secrétariat, bureautique',68,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (69,'325','Documentation, bibliothèques, administration des données',69,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (70,'326','Informatique, traitement de l''information, réseaux de transmission des données',70,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (71,'330','Spécialités plurivalentes sanitaires et sociales',71,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (72,'331','Santé',72,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (73,'332','Travail social',73,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (74,'333','Enseignement, formation',74,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (75,'334','Accueil, hôtellerie, tourisme',75,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (76,'335','Animation culturelle, sportive et de loisirs',76,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (77,'336','Coiffure, esthétique et autres spécialités des services aux personnes',77,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (78,'341','Aménagement du territoire, développement, urbanisme',78,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (79,'342','Protection et développement du patrimoine',79,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (80,'343','Nettoyage, assainissement, protection de l''environnement',80,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (81,'344','Sécurité des biens et des personnes, police, surveillance (y compris hygiène et sécurité)',81,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (82,'345','Application des droits et statut des personnes',82,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (83,'346','Spécialités militaires',83,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (84,'410','Spécialités concernant plusieurs capacités',84,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (85,'411','Pratiques sportives (y compris : arts martiaux)',85,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (86,'412','Développement des capacités mentales et apprentissages de base',86,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (87,'413','Développement des capacités comportementales et relationnelles',87,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (88,'414','Développement des capacités individuelles d''organisation',88,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (89,'415','Développement des capacités d''orientation, d''insertion ou de réinsertion sociales',89,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (90,'421','Jeux et activités spécifiques de loisirs',90,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (91,'422','Economie et activités domestiques',91,1,'2017-03-23 12:00:00');
INSERT INTO llx_agefodd_formation_catalogue_type(rowid,code,intitule,sort,active,tms) VALUES (92,'423','Vie familiale, vie sociale et autres formations au développement personne',92,1,'2017-03-23 12:00:00');

