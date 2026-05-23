SELECT COALESCE((SELECT description FROM dtb_tradelaw WHERE id = 1 LIMIT 1), '販売業者: 株式会社EC-CUBE
所在地: 大阪市北区梅田1-1-1
連絡先: 06-1234-5678') AS description
