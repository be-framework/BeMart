SELECT o.order_no,
       o.customer_id,
       o.message,
       p.payment_method,
       o.subtotal,
       o.delivery_fee_total,
       o.charge,
       o.discount,
       o.tax,
       o.total,
       o.payment_total,
       o.add_point,
       o.use_point,
       o.order_status_id,
       o.order_date,
       o.payment_date,
       (SELECT COALESCE(CONCAT('[', GROUP_CONCAT(JSON_OBJECT(
           'name01', s.name01,
           'name02', s.name02,
           'kana01', s.kana01,
           'kana02', s.kana02,
           'postalCode', s.postal_code,
           'prefName', pref.name,
           'addr01', s.addr01,
           'addr02', s.addr02,
           'phoneNumber', s.phone_number,
           'deliveryName', s.delivery_name,
           'deliveryDate', s.delivery_date,
           'deliveryTime', s.delivery_time,
           'items', (SELECT COALESCE(CONCAT('[', GROUP_CONCAT(JSON_OBJECT(
               'productCode', oi.product_code,
               'productName', oi.product_name,
               'quantity', oi.quantity,
               'unitPrice', oi.price
           ) ORDER BY oi.id ASC SEPARATOR ','), ']'), JSON_ARRAY())
              FROM dtb_order_item oi
              WHERE oi.order_id = o.id
                AND (oi.shipping_id = s.id OR oi.shipping_id IS NULL))
       ) ORDER BY s.sort_no IS NULL, s.sort_no ASC, s.id ASC SEPARATOR ','), ']'), JSON_ARRAY())
        FROM dtb_shipping s
        LEFT JOIN mtb_pref pref ON pref.id = s.pref_id
        WHERE s.order_id = o.id) AS shippings_json,
       (SELECT COALESCE(CONCAT('[', GROUP_CONCAT(JSON_OBJECT(
           'sendDate', mh.send_date,
           'mailSubject', mh.mail_subject,
           'mailBody', mh.mail_body
       ) ORDER BY mh.send_date ASC, mh.id ASC SEPARATOR ','), ']'), JSON_ARRAY())
        FROM dtb_mail_history mh
        WHERE mh.order_id = o.id) AS mail_histories_json
FROM dtb_order o
LEFT JOIN dtb_payment p ON p.id = o.payment_id
WHERE o.order_no = :orderNo
  AND o.order_status_id <> 8
LIMIT 1
