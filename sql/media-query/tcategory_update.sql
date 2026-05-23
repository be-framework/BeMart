UPDATE dtb_category SET category_name = :categoryName, parent_category_id = :parentId, sort_no = :sortNo, hierarchy = :hierarchy, update_date = NOW() WHERE id = :id
