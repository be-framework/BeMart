DELETE FROM
  dtb_cart
WHERE
  cart_key LIKE CONCAT(
    REPLACE(
      REPLACE(
        REPLACE(:sessionPrefix, '\\', '\\\\'),
        '%',
        '\\%'
      ),
      '_',
      '\\_'
    ),
    '\\_%'
  ) ESCAPE '\\'
