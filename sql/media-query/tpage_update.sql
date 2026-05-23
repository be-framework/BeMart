UPDATE dtb_page SET page_name = :pageName, url = :url, file_name = :fileName, edit_type = :editType, update_date = NOW() WHERE id = :id
