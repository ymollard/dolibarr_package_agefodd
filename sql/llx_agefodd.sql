


--
-- Structure de la table llx_agefodd_session
--

CREATE TABLE IF NOT EXISTS llx_agefodd_session (
  rowid int(11) NOT NULL auto_increment,
  fk_formation_catalogue int(11) NOT NULL,
  fk_session_place int(11) NOT NULL,
  fk_agefodd_formateur int(11) default NULL,
  dated datetime default NULL,
  datef datetime default NULL,
  notes text NOT NULL,
  fk_user_author int(11) NOT NULL,
  datec datetime NOT NULL,
  fk_user_mod int(11) NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  archive enum('0','1') NOT NULL default '0',
  PRIMARY KEY  (rowid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


--
-- Structure de la table llx_agefodd_session_adminsitu
--

CREATE TABLE IF NOT EXISTS llx_agefodd_session_adminsitu (
  rowid int(11) NOT NULL auto_increment,
  fk_agefodd_session_admlevel int(11) NOT NULL,
  fk_agefodd_session int(11) NOT NULL,
  intitule varchar(100) default NULL,
  delais_alerte int(11) NOT NULL,
  indice int(11) NOT NULL,
  top_level enum('Y','N') NOT NULL default 'N',
  dated datetime default NULL,
  datef datetime NOT NULL,
  datea datetime NOT NULL,
  notes text NOT NULL,
  fk_user_mod int(11) NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP,
  archive enum('0','1') NOT NULL default '0',
  PRIMARY KEY  (rowid),
  KEY fk_agefodd_session (fk_agefodd_session)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;


--
-- Structure de la table llx_agefodd_session_admlevel
--

CREATE TABLE IF NOT EXISTS llx_agefodd_session_admlevel (
  rowid int(11) NOT NULL auto_increment,
  indice int(11) NOT NULL,
  top_level enum('Y','N') NOT NULL default 'N',
  intitule varchar(150) NOT NULL,
  delais_alerte int(11) NOT NULL,
  fk_user_author int(11) NOT NULL,
  datec datetime NOT NULL,
  fk_user_mod int(11) NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (rowid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Contenu de la table llx_agefodd_session_admlevel
--

INSERT INTO llx_agefodd_session_admlevel (rowid, indice, top_level, intitule, delais_alerte, fk_user_author, datec, fk_user_mod, tms) VALUES
(1, 100, 'Y', 'Préparation de l''action', -40, 2, '2010-04-02 16:30:17', 0, '2010-04-02 16:31:13'),
(2, 200, 'Y', 'Transmission de la convention de formation', -30, 2, '2010-04-02 16:31:09', 0, '2010-04-02 16:31:13'),
(3, 400, 'Y', 'Vérifications et mise en place des moyens', -10, 2, '2010-04-02 16:32:16', 0, '2010-04-02 16:32:55'),
(4, 500, 'Y', 'Execution de la prestation', 0, 2, '2010-04-02 16:32:52', 0, '2010-04-02 16:32:55'),
(5, 600, 'Y', 'Cloture administrative', 8, 0, '0000-00-00 00:00:00', 0, '2010-04-02 16:34:27'),
(6, 300, 'Y', 'Envoi des convocations', -15, 2, '2010-04-02 16:35:29', 0, '2010-04-02 16:35:32'),
(7, 601, 'N', 'impression des attestations', 8, 2, '2010-04-02 16:37:44', 0, '2010-04-02 16:38:48'),
(8, 602, 'N', 'creation de la facture et verification', 8, 2, '2010-04-02 16:38:36', 0, '2010-04-02 16:38:48'),
(9, 603, 'N', 'création du courrier d''accompagnement', 8, 2, '2010-04-02 16:40:04', 0, '2010-04-02 16:40:51'),
(10, 604, 'N', 'impression de la liasse administrative', 8, 2, '2010-04-02 16:40:48', 0, '2010-04-02 16:40:51'),
(11, 605, 'N', 'envoi de la liasse administrative', 8, 2, '2010-04-02 16:41:28', 0, '2010-04-02 16:41:30'),
(12, 101, 'N', 'inscription des stagiaires', -31, 2, '2010-04-02 21:20:32', 0, '2010-04-02 21:20:35'),
(13, 210, 'N', 'Impression convention et vérification', -31, 2, '2010-04-05 09:21:40', 0, '2010-04-05 09:21:45'),
(14, 211, 'N', 'Envoi convention (VP ou numérique avec AC)', -30, 2, '2010-04-05 09:22:54', 0, '2010-04-05 09:23:00'),
(15, 301, 'N', 'Preparation du dossier<br>(convoc., rég. intérieur, programme, fiche péda, conseils pratiques)', -15, 0, '0000-00-00 00:00:00', 0, '2010-04-05 11:22:07'),
(16, 302, 'N', 'Envoi du dossier à chaque stagiaire (inter) ou au respo. formation (intra)', -15, 0, '0000-00-00 00:00:00', 0, '2010-04-05 11:25:25'),
(17, 401, 'N', 'Verification du retour de la convention signée', -10, 2, '2010-04-13 19:17:08', 0, '2010-04-13 19:17:31') ;

-- --------------------------------------------------------

--
-- Structure de la table llx_agefodd_session_calendrier
--

CREATE TABLE IF NOT EXISTS llx_agefodd_session_calendrier (
  rowid int(11) NOT NULL auto_increment,
  fk_agefodd_session int(11) NOT NULL,
  date date NOT NULL,
  heured time NOT NULL,
  heuref time NOT NULL,
  fk_user_author int(11) NOT NULL,
  datec datetime NOT NULL,
  fk_user_mod int(11) NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (rowid),
  KEY idx_fk_agefodd_session (fk_agefodd_session)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Contenu de la table llx_agefodd_session_calendrier
--


-- --------------------------------------------------------

--
-- Structure de la table llx_agefodd_session_formateur
--

CREATE TABLE IF NOT EXISTS llx_agefodd_session_formateur (
  rowid int(11) NOT NULL auto_increment,
  fk_session int(11) NOT NULL,
  fk_agefodd_formateur int(11) NOT NULL,
  fk_user_author int(11) NOT NULL,
  datec datetime NOT NULL,
  fk_user_mod int(11) NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (rowid),
  KEY fk_session (fk_session),
  KEY idx_fk_agefodd_formateur (fk_agefodd_formateur)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

--
-- Contenu de la table llx_agefodd_session_formateur
--


-- --------------------------------------------------------

--
-- Structure de la table llx_agefodd_session_place
--

CREATE TABLE IF NOT EXISTS llx_agefodd_session_place (
  rowid int(11) NOT NULL auto_increment,
  code varchar(80) NOT NULL,
  adresse varchar(255) NOT NULL,
  cp varchar(10) NOT NULL,
  ville varchar(50) NOT NULL,
  pays varchar(30) NOT NULL,
  tel varchar(20) default NULL,
  fk_societe int(11) NOT NULL,
  fk_agefodd_reg_interieur int(11) NOT NULL,
  notes text NOT NULL,
  archive enum('0','1') NOT NULL default '0',
  fk_user_author int(11) NOT NULL,
  datec datetime NOT NULL,
  fk_user_mod int(11) NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (rowid),
  KEY archive (archive)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

--
-- Contenu de la table llx_agefodd_session_place
--

-- --------------------------------------------------------

--
-- Structure de la table llx_agefodd_session_stagiaire
--

CREATE TABLE IF NOT EXISTS llx_agefodd_session_stagiaire (
  rowid int(11) NOT NULL auto_increment,
  fk_session int(11) NOT NULL,
  fk_stagiaire int(11) NOT NULL,
  fk_agefodd_stagiaire_type int(11) NOT NULL,
  fk_user_author int(11) NOT NULL,
  datec datetime NOT NULL,
  fk_user_mod int(11) NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (rowid),
  KEY fk_session (fk_session),
  KEY fk_agefodd_stagiaire_type (fk_agefodd_stagiaire_type)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

--
-- Contenu de la table llx_agefodd_session_stagiaire
--


-- --------------------------------------------------------

--
-- Structure de la table llx_agefodd_stagiaire
--

CREATE TABLE IF NOT EXISTS llx_agefodd_stagiaire (
  rowid int(11) NOT NULL auto_increment,
  nom varchar(50) NOT NULL,
  prenom varchar(50) NOT NULL,
  fk_c_civilite int(11) NOT NULL,
  fk_user_author int(11) default NULL,
  fk_user_mod int(11) NOT NULL,
  datec datetime NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  fk_soc int(11) NOT NULL,
  fonction varchar(60) default NULL,
  tel1 varchar(30) default NULL,
  tel2 varchar(30) default NULL,
  mail varchar(100) default NULL,
  note text,
  PRIMARY KEY  (rowid),
  KEY nom (nom),
  KEY fk_soc (fk_soc)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

--
-- Contenu de la table llx_agefodd_stagiaire
--

-- --------------------------------------------------------

--
-- Structure de la table llx_agefodd_stagiaire_type
--

CREATE TABLE IF NOT EXISTS llx_agefodd_stagiaire_type (
  rowid int(11) NOT NULL auto_increment,
  intitule varchar(80) NOT NULL,
  order tinyint(4) NOT NULL,
  datec datetime NOT NULL,
  tms timestamp NOT NULL default CURRENT_TIMESTAMP,
  fk_user_author int(11) NOT NULL,
  fk_user_mod int(11) NOT NULL,
  PRIMARY KEY  (rowid)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

--
-- Contenu de la table llx_agefodd_stagiaire_type
--

INSERT INTO llx_agefodd_stagiaire_type (rowid, intitule, order, datec, tms, fk_user_author, fk_user_mod) VALUES
(2, 'financement par l''employeur (contrat pro.)', 1, '0000-00-00 00:00:00', '2010-06-30 18:47:43', 0, 0),
(3, 'financement par l''employeur (autre)', 2, '0000-00-00 00:00:00', '2010-06-30 18:47:56', 0, 0),
(4, 'demandeur d''emploi avec financement public', 3, '0000-00-00 00:00:00', '2010-06-30 18:48:05', 0, 0),
(5, 'autre', 4, '0000-00-00 00:00:00', '2010-06-30 18:48:11', 0, 0);

--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table llx_agefodd_convention
--
ALTER TABLE llx_agefodd_convention
  ADD CONSTRAINT llx_agefodd_convention_ibfk_1 FOREIGN KEY (fk_agefodd_session) REFERENCES llx_agefodd_session (rowid) ON DELETE CASCADE;

--
-- Contraintes pour la table llx_agefodd_facture
--
ALTER TABLE llx_agefodd_facture
  ADD CONSTRAINT llx_agefodd_facture_ibfk_1 FOREIGN KEY (fk_session) REFERENCES llx_agefodd_session (rowid) ON DELETE CASCADE;

--
-- Contraintes pour la table llx_agefodd_formation_objectifs_peda
--
ALTER TABLE llx_agefodd_formation_objectifs_peda
  ADD CONSTRAINT llx_agefodd_formation_objectifs_peda_ibfk_1 FOREIGN KEY (fk_formation_catalogue) REFERENCES llx_agefodd_formation_catalogue (rowid) ON DELETE CASCADE;

--
-- Contraintes pour la table llx_agefodd_session_adminsitu
--
ALTER TABLE llx_agefodd_session_adminsitu
  ADD CONSTRAINT llx_agefodd_session_adminsitu_ibfk_1 FOREIGN KEY (fk_agefodd_session) REFERENCES llx_agefodd_session (rowid) ON DELETE CASCADE;

--
-- Contraintes pour la table llx_agefodd_session_calendrier
--
ALTER TABLE llx_agefodd_session_calendrier
  ADD CONSTRAINT llx_agefodd_session_calendrier_ibfk_1 FOREIGN KEY (fk_agefodd_session) REFERENCES llx_agefodd_session (rowid) ON DELETE CASCADE;

--
-- Contraintes pour la table llx_agefodd_session_stagiaire
--
ALTER TABLE llx_agefodd_session_stagiaire
  ADD CONSTRAINT llx_agefodd_session_stagiaire_ibfk_1 FOREIGN KEY (fk_session) REFERENCES llx_agefodd_session (rowid) ON DELETE CASCADE;
