DELETE FROM llx_user_rights WHERE fk_user=1 AND fk_id IN (SELECT rd.id FROM llx_rights_def as rd WHERE rd.libelle='trainermode');