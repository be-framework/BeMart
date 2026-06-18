SELECT
    (
        SELECT
            COUNT(*)
        FROM
            dtb_product
    ) AS products,
    (
        SELECT
            COUNT(*)
        FROM
            dtb_customer
        WHERE
            customer_status_id = 2
    ) AS customers,
    (
        SELECT
            COUNT(*)
        FROM
            dtb_product_class
        WHERE
            stock_unlimited = 0
            AND stock <= 0
    ) AS nonStock
