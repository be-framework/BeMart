UPDATE dtb_news SET title = :title, description = :description, url = :url, publish_date = :publishDate, link_method = :linkMethod, update_date = NOW() WHERE id = :id
