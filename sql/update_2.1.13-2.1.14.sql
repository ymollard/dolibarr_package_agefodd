ALTER TABLE llx_agefodd_convention ADD COLUMN only_product_session integer DEFAULT 0 AFTER sig;
ALTER TABLE llx_agefodd_formation_catalogue ADD COLUMN color varchar(32) NULL AFTER certif_duration;
ALTER TABLE llx_agefodd_session ADD COLUMN cost_buy_charges double(24,8) DEFAULT 0 AFTER sell_price;
ALTER TABLE llx_agefodd_session ADD COLUMN cost_sell_charges double(24,8) DEFAULT 0 AFTER cost_buy_charges;
ALTER TABLE llx_agefodd_session ADD COLUMN invoice_amount double(24,8) DEFAULT 0 AFTER sell_price;