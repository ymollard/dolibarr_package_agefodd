/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  quentin
 * Created: 23 juil. 2018
 */

ALTER TABLE llx_agefodd_session ADD COLUMN cost_trainer_planned double(24,8) DEFAULT 0;         
ALTER TABLE llx_agefodd_session ADD COLUMN cost_site_planned double(24,8) DEFAULT 0;         
ALTER TABLE llx_agefodd_session ADD COLUMN cost_trip_planned double(24,8) NULL;         
ALTER TABLE llx_agefodd_session ADD COLUMN sell_price_planned double(24,8) DEFAULT 0;         


UPDATE llx_agefodd_session SET cost_trainer_planned = cost_trainer, cost_site_planned=cost_site, cost_trip_planned=cost_trip,  sell_price_planned=sell_price