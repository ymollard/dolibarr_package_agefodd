/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  quentin
 * Created: 10 juil. 2018
 */

ALTER TABLE llx_agefodd_session ADD COLUMN fk_soc_employer integer DEFAULT NULL;

ALTER TABLE llx_agefodd_session DROP is_date_res_site;
ALTER TABLE llx_agefodd_session DROP is_date_res_confirm_site;
ALTER TABLE llx_agefodd_session DROP is_date_res_trainer;
