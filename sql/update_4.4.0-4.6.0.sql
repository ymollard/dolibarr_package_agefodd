ALTER TABLE llx_agefodd_session_stagiaire_heures ADD COLUMN mail_sended integer DEFAULT 0 AFTER fk_calendrier;
ALTER TABLE llx_agefodd_session_stagiaire_heures ADD COLUMN planned_absence integer DEFAULT 0 AFTER mail_sended;
