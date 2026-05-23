UPDATE dtb_product SET name = :name, product_status_id = :productStatus, description_detail = :description, search_word = :searchWord, note = :note, update_date = NOW() WHERE id = :id
