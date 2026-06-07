-- BeMart product read-side indexes for Koriym.SqlQuality High/Very High fixes.
-- Applied after the canonical EC-CUBE 4.3 schema dump by sql/setup-db.sh.

CREATE INDEX idx_bemart_pc_code_default
    ON dtb_product_class(product_code, class_category_id1, class_category_id2, id, product_id);

CREATE INDEX idx_bemart_pc_default_order
    ON dtb_product_class(class_category_id1, class_category_id2, id, product_id);

CREATE INDEX idx_bemart_pi_product_sort
    ON dtb_product_image(product_id, sort_no, id);
