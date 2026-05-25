UPDATE dtb_news SET visible = :visible, update_date = NOW() WHERE :newsId REGEXP '^[0-9]+$' AND id = CAST(:newsId AS UNSIGNED)
