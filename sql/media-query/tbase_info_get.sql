SELECT shop_name, shop_kana, shop_name_eng, company_name, postal_code, pref_id, addr01, addr02, phone_number, business_hour, email01, message
FROM dtb_base_info
WHERE id = 1
UNION ALL
SELECT 'EC-CUBE SHOP', 'イーシーキューブショップ', 'EC-CUBE SHOP', '株式会社EC-CUBE', '5300001', 27, '大阪市北区', '梅田1-1-1', '0612345678', '10:00-19:00', 'shop@example.com', 'ようこそ、EC-CUBE SHOP へ。'
WHERE NOT EXISTS (SELECT 1 FROM dtb_base_info WHERE id = 1)
LIMIT 1
