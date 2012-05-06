-- ============================================================================
-- Copyright (C) 2009-2010	Erick Bullier	<eb.dev@ebiconsulting.fr>
-- Copyright (C) 2010-2011	Regis Houssin	<regis@dolibarr.fr>
-- Copyright (C) 2012		Florian Henry	<florian.henry@open-concept.pro>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- ============================================================================
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

