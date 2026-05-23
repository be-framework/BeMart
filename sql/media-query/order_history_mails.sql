SELECT mh.send_date, mh.mail_subject, mh.mail_body
FROM dtb_mail_history mh
WHERE mh.order_id = :orderId
ORDER BY mh.send_date ASC, mh.id ASC
