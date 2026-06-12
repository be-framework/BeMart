-- BeMart customer read-side indexes for Koriym.SqlQuality High/Very High fixes.
-- Applied after the canonical EC-CUBE schema dump by sql/setup-db.sh.

CREATE INDEX idx_bemart_customer_reset_key
    ON dtb_customer(reset_key, id, reset_expire);
