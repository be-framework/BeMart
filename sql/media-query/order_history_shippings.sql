SELECT s.id, s.name01, s.name02, s.kana01, s.kana02,
       s.postal_code, s.addr01, s.addr02, s.phone_number,
       s.delivery_name, s.delivery_date, s.delivery_time,
       pref.name AS pref_name
FROM dtb_shipping s
LEFT JOIN mtb_pref pref ON pref.id = s.pref_id
WHERE s.order_id = :orderId
ORDER BY s.sort_no IS NULL, s.sort_no ASC, s.id ASC
