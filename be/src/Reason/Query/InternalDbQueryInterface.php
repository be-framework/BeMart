<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Result\AffectedRows;

/**
 * Internal Ray.MediaQuery proxy for SQL files that still need orchestration.
 *
 * Do not implement this interface manually; MediaQueryRuntimeModule registers it
 * with Queries::fromClasses().
 */
interface InternalDbQueryInterface
{
    #[DbQuery('address_delete')]
    public function address_delete(mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('address_exists')]
    public function address_exists(mixed $id): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('address_get')]
    public function address_get(mixed $id): array|null;

    #[DbQuery('address_insert')]
    public function address_insert(mixed $id, mixed $customerId, mixed $name01, mixed $name02, mixed $kana01, mixed $kana02, mixed $companyName, mixed $phoneNumber, mixed $postalCode, mixed $prefId, mixed $addr01, mixed $addr02): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('address_list_by_customer')]
    public function address_list_by_customer(mixed $customerId): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('address_next_id')]
    public function address_next_id(): array|null;

    #[DbQuery('address_update')]
    public function address_update(mixed $customerId, mixed $name01, mixed $name02, mixed $kana01, mixed $kana02, mixed $companyName, mixed $phoneNumber, mixed $postalCode, mixed $prefId, mixed $addr01, mixed $addr02, mixed $id): void;

    #[DbQuery('admin_create')]
    public function admin_create(mixed $id, mixed $work, mixed $authority, mixed $name, mixed $loginId, mixed $password): void;

    #[DbQuery('admin_delete')]
    public function admin_delete(mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('admin_find_by_id')]
    public function admin_find_by_id(mixed $adminId): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('admin_find_by_login')]
    public function admin_find_by_login(mixed $loginId): array|null;

    /** @return list<array<string, mixed>> */
    #[DbQuery('admin_list')]
    public function admin_list(mixed $limit, mixed $offset): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('admin_next_id')]
    public function admin_next_id(): array|null;

    /** @return list<array<string, mixed>> */
    #[DbQuery('admin_search')]
    public function admin_search(mixed $nameKeyword): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('admin_search_all')]
    public function admin_search_all(): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('admin_search_name')]
    public function admin_search_name(mixed $pattern): array;

    #[DbQuery('admin_update')]
    public function admin_update(mixed $loginId, mixed $password, mixed $name, mixed $authority, mixed $work, mixed $id): void;

    #[DbQuery('admin_update_authority')]
    public function admin_update_authority(mixed $authority, mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('block_next_id')]
    public function block_next_id(): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('cart_by_key')]
    public function cart_by_key(mixed $cartKey): array|null;

    /** @return list<array<string, mixed>> */
    #[DbQuery('cart_by_session_prefix')]
    public function cart_by_session_prefix(mixed $sessionPrefix): array;

    #[DbQuery('cart_clear_pre_order')]
    public function cart_clear_pre_order(mixed $preOrderId): void;

    #[DbQuery('cart_clear_session_prefix')]
    public function cart_clear_session_prefix(mixed $pattern): void;

    #[DbQuery('cart_delete_by_key')]
    public function cart_delete_by_key(mixed $cartKey): void;

    #[DbQuery('cart_insert')]
    public function cart_insert(mixed $cartKey, mixed $preOrderId, mixed $totalPrice, mixed $deliveryFeeTotal): void;

    #[DbQuery('cart_item_insert')]
    public function cart_item_insert(mixed $productClassId, mixed $cartId, mixed $price, mixed $quantity): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('cart_items')]
    public function cart_items(mixed $cartId): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('cart_last_id')]
    public function cart_last_id(): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('cart_resolve_product_class')]
    public function cart_resolve_product_class(mixed $productCode): array|null;

    /** @return list<array<string, mixed>> */
    #[DbQuery('cart_sale_type')]
    public function cart_sale_type(mixed $id): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('category_next_id')]
    public function category_next_id(): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('classCategory_next_id')]
    public function classCategory_next_id(): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('className_next_id')]
    public function className_next_id(): array|null;

    #[DbQuery('csv_column_delete_by_type')]
    public function csv_column_delete_by_type(mixed $csvType): void;

    #[DbQuery('csv_column_insert')]
    public function csv_column_insert(mixed $csvType, mixed $entityName, mixed $fieldName, mixed $dispName, mixed $sortNo, mixed $enabled, mixed $discriminator): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('csv_column_list_by_type')]
    public function csv_column_list_by_type(mixed $csvType): array;

    #[DbQuery('csv_column_release_savepoint')]
    public function csv_column_release_savepoint(): void;

    #[DbQuery('csv_column_rollback_savepoint')]
    public function csv_column_rollback_savepoint(): void;

    #[DbQuery('csv_column_savepoint')]
    public function csv_column_savepoint(): void;

    #[DbQuery('customer_activate')]
    public function customer_activate(mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('customer_email_exists')]
    public function customer_email_exists(mixed $email): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('customer_find_by_email')]
    public function customer_find_by_email(mixed $email): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('customer_find_by_id')]
    public function customer_find_by_id(mixed $customerId): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('customer_find_by_secret_key')]
    public function customer_find_by_secret_key(mixed $secretKey): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('customer_next_id')]
    public function customer_next_id(): array|null;

    #[DbQuery('customer_register')]
    public function customer_register(mixed $id, mixed $customerStatus, mixed $sex, mixed $job, mixed $pref, mixed $name01, mixed $name02, mixed $kana01, mixed $kana02, mixed $companyName, mixed $postalCode, mixed $addr01, mixed $addr02, mixed $email, mixed $phoneNumber, mixed $birth, mixed $password, mixed $secretKey, mixed $point): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('customer_search')]
    public function customer_search(mixed $nameKeyword, mixed $emailKeyword, mixed $limit): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('customer_search_all')]
    public function customer_search_all(mixed $limit): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('customer_search_email')]
    public function customer_search_email(mixed $emailKeyword, mixed $limit): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('customer_search_name')]
    public function customer_search_name(mixed $nameA, mixed $nameB, mixed $nameC, mixed $limit): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('customer_search_name_email')]
    public function customer_search_name_email(mixed $nameA, mixed $nameB, mixed $nameC, mixed $emailKeyword, mixed $limit): array;

    #[DbQuery('customer_update')]
    public function customer_update(mixed $email, mixed $password, mixed $name01, mixed $name02, mixed $kana01, mixed $kana02, mixed $companyName, mixed $phoneNumber, mixed $postalCode, mixed $pref, mixed $addr01, mixed $addr02, mixed $birth, mixed $sex, mixed $job, mixed $customerStatus, mixed $secretKey, mixed $point, mixed $id): void;

    #[DbQuery('customer_update_password')]
    public function customer_update_password(mixed $password, mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('delivery_next_id')]
    public function delivery_next_id(): array|null;

    #[DbQuery('favorite_add')]
    public function favorite_add(mixed $customerId, mixed $productId): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('favorite_has')]
    public function favorite_has(mixed $customerId, mixed $productCode): array|null;

    /** @return list<array<string, mixed>> */
    #[DbQuery('favorite_list')]
    public function favorite_list(mixed $customerId): array;

    #[DbQuery('favorite_remove')]
    public function favorite_remove(mixed $customerId, mixed $productId): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('favorite_resolve_product')]
    public function favorite_resolve_product(mixed $productCode): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('news_next_id')]
    public function news_next_id(): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('order_by_order_no')]
    public function order_by_order_no(mixed $orderNo): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('order_by_pre_order_id')]
    public function order_by_pre_order_id(mixed $preOrderId): array|null;

    /** @return list<array<string, mixed>> */
    #[DbQuery('order_history_by_order_no')]
    public function order_history_by_order_no(mixed $orderNo): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('order_history_header')]
    public function order_history_header(mixed $orderNo, mixed $processing): array|null;

    /** @return list<array<string, mixed>> */
    #[DbQuery('order_history_items')]
    public function order_history_items(mixed $orderId, mixed $shippingId): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('order_history_mails')]
    public function order_history_mails(mixed $orderId): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('order_history_shippings')]
    public function order_history_shippings(mixed $orderId): array;

    #[DbQuery('order_insert')]
    public function order_insert(mixed $customerId, mixed $paymentId, mixed $preOrderId, mixed $orderNo, mixed $name01, mixed $name02, mixed $subtotal, mixed $discount, mixed $deliveryFeeTotal, mixed $charge, mixed $tax, mixed $total, mixed $paymentTotal, mixed $addPoint, mixed $usePoint, mixed $orderStatus, mixed $orderDate, mixed $paymentDate, mixed $discriminator): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('order_items_by_order_no')]
    public function order_items_by_order_no(mixed $orderNo): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('order_list_all')]
    public function order_list_all(mixed $limit, mixed $offset): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('order_list_by_customer')]
    public function order_list_by_customer(mixed $customerId, mixed $limit, mixed $offset): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('order_pre_order_exists')]
    public function order_pre_order_exists(mixed $preOrderId): array|null;

    #[DbQuery('order_promote_pre_order')]
    public function order_promote_pre_order(mixed $orderNo, mixed $customerId, mixed $paymentId, mixed $subtotal, mixed $deliveryFeeTotal, mixed $charge, mixed $discount, mixed $tax, mixed $total, mixed $paymentTotal, mixed $addPoint, mixed $usePoint, mixed $orderStatus, mixed $orderDate, mixed $paymentDate, mixed $preOrderId): void;

    #[DbQuery('order_update')]
    public function order_update(mixed $customerId, mixed $paymentId, mixed $subtotal, mixed $deliveryFeeTotal, mixed $charge, mixed $discount, mixed $tax, mixed $total, mixed $paymentTotal, mixed $addPoint, mixed $usePoint, mixed $orderStatus, mixed $orderDate, mixed $paymentDate, mixed $orderNo): void;

    #[DbQuery('order_update_status')]
    public function order_update_status(mixed $status, mixed $orderNo): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('page_next_id')]
    public function page_next_id(): array|null;

    #[DbQuery('password_reset_delete')]
    public function password_reset_delete(mixed $resetKey): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('password_reset_get')]
    public function password_reset_get(mixed $resetKey): array|null;

    #[DbQuery('password_reset_put')]
    public function password_reset_put(mixed $resetKey, mixed $resetExpire, mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('paymentMethodAdmin_next_id')]
    public function paymentMethodAdmin_next_id(): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('plugin_find_by_code')]
    public function plugin_find_by_code(mixed $code): array|null;

    #[DbQuery('plugin_insert')]
    public function plugin_insert(mixed $name, mixed $code, mixed $version, mixed $source, mixed $discriminator): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('plugin_list_all')]
    public function plugin_list_all(): array;

    #[DbQuery('plugin_mark_installed')]
    public function plugin_mark_installed(mixed $code): void;

    #[DbQuery('plugin_set_enabled')]
    public function plugin_set_enabled(mixed $enabled, mixed $code): void;

    #[DbQuery('plugin_uninstall')]
    public function plugin_uninstall(mixed $code): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('product_categories')]
    public function product_categories(mixed $productId): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('product_class_get')]
    public function product_class_get(mixed $productCode): array|null;

    /** @return list<array<string, mixed>> */
    #[DbQuery('product_class_names')]
    public function product_class_names(mixed $productId): array;

    #[DbQuery('product_create')]
    public function product_create(mixed $productStatus, mixed $name, mixed $note, mixed $description, mixed $searchWord, mixed $productCode, mixed $price02, mixed $stock, mixed $stockUnlimited): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('product_export')]
    public function product_export(): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('product_find_id')]
    public function product_find_id(mixed $productCode): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('product_get')]
    public function product_get(mixed $productCode): array|null;

    /** @return list<array<string, mixed>> */
    #[DbQuery('product_image')]
    public function product_image(mixed $productId): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('product_list')]
    public function product_list(mixed $limit, mixed $offset): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('product_search')]
    public function product_search(mixed $nameKeyword, mixed $limit): array;

    #[DbQuery('product_soft_delete')]
    public function product_soft_delete(mixed $setStatus, mixed $id, mixed $whereStatus): void;

    #[DbQuery('product_status_update')]
    public function product_status_update(mixed $setStatus, mixed $id, mixed $whereStatus): AffectedRows;

    /** @return list<array<string, mixed>> */
    #[DbQuery('product_tags')]
    public function product_tags(mixed $productId): array;

    #[DbQuery('product_update_class')]
    public function product_update_class(mixed $price02, mixed $stock, mixed $id): void;

    #[DbQuery('product_update_header')]
    public function product_update_header(mixed $name, mixed $productStatus, mixed $description, mixed $searchWord, mixed $note, mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('shipping_first_id_by_order_id')]
    public function shipping_first_id_by_order_id(mixed $orderId): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('shipping_get_by_order_id')]
    public function shipping_get_by_order_id(mixed $orderId): array|null;

    #[DbQuery('shipping_insert')]
    public function shipping_insert(mixed $orderId, mixed $prefId, mixed $name01, mixed $name02, mixed $postalCode, mixed $addr01, mixed $addr02, mixed $phoneNumber, mixed $discriminator): void;

    #[DbQuery('shipping_insert_tracking')]
    public function shipping_insert_tracking(mixed $orderId, mixed $name01, mixed $name02, mixed $trackingNumber, mixed $discriminator): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('shipping_list_all')]
    public function shipping_list_all(): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('shipping_order_id_by_order_no')]
    public function shipping_order_id_by_order_no(mixed $orderNo): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('shipping_tracking_by_order_id')]
    public function shipping_tracking_by_order_id(mixed $orderId): array|null;

    #[DbQuery('shipping_update')]
    public function shipping_update(mixed $name01, mixed $name02, mixed $postalCode, mixed $prefId, mixed $addr01, mixed $addr02, mixed $phoneNumber, mixed $id): void;

    #[DbQuery('shipping_update_tracking')]
    public function shipping_update_tracking(mixed $trackingNumber, mixed $id): void;

    #[DbQuery('tag_delete')]
    public function tag_delete(mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('tag_exists')]
    public function tag_exists(mixed $id): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('tag_get')]
    public function tag_get(mixed $id): array|null;

    #[DbQuery('tag_insert')]
    public function tag_insert(mixed $id, mixed $name, mixed $sortNo): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('tag_list')]
    public function tag_list(): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('tag_next_id')]
    public function tag_next_id(): array|null;

    #[DbQuery('tag_reorder')]
    public function tag_reorder(mixed $sortNo, mixed $id): void;

    #[DbQuery('tag_update')]
    public function tag_update(mixed $name, mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('taxRule_next_id')]
    public function taxRule_next_id(): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('tbase_info_exists')]
    public function tbase_info_exists(): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('tbase_info_get')]
    public function tbase_info_get(): array|null;

    #[DbQuery('tbase_info_insert')]
    public function tbase_info_insert(mixed $shopName, mixed $shopKana, mixed $shopNameEng, mixed $companyName, mixed $postalCode, mixed $pref, mixed $addr01, mixed $addr02, mixed $phoneNumber, mixed $businessHour, mixed $shopEmail01, mixed $shopMessage): void;

    #[DbQuery('tbase_info_update')]
    public function tbase_info_update(mixed $shopName, mixed $shopKana, mixed $shopNameEng, mixed $companyName, mixed $postalCode, mixed $pref, mixed $addr01, mixed $addr02, mixed $phoneNumber, mixed $businessHour, mixed $shopEmail01, mixed $shopMessage): void;

    #[DbQuery('tblock_delete')]
    public function tblock_delete(mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('tblock_exists')]
    public function tblock_exists(mixed $id): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('tblock_get')]
    public function tblock_get(mixed $id): array|null;

    #[DbQuery('tblock_insert')]
    public function tblock_insert(mixed $id, mixed $blockName, mixed $fileName, mixed $deletable): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('tblock_list')]
    public function tblock_list(): array;

    #[DbQuery('tblock_position_delete')]
    public function tblock_position_delete(mixed $id): void;

    #[DbQuery('tblock_update')]
    public function tblock_update(mixed $blockName, mixed $fileName, mixed $deletable, mixed $id): void;

    #[DbQuery('tcategory_delete')]
    public function tcategory_delete(mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('tcategory_exists')]
    public function tcategory_exists(mixed $id): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('tcategory_get')]
    public function tcategory_get(mixed $id): array|null;

    #[DbQuery('tcategory_insert')]
    public function tcategory_insert(mixed $id, mixed $parentId, mixed $categoryName, mixed $hierarchy, mixed $sortNo): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('tcategory_list')]
    public function tcategory_list(): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('tcategory_parent_hierarchy')]
    public function tcategory_parent_hierarchy(mixed $id): array|null;

    #[DbQuery('tcategory_product_delete')]
    public function tcategory_product_delete(mixed $id): void;

    #[DbQuery('tcategory_update')]
    public function tcategory_update(mixed $categoryName, mixed $parentId, mixed $sortNo, mixed $hierarchy, mixed $id): void;

    #[DbQuery('tclass_category_delete')]
    public function tclass_category_delete(mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('tclass_category_exists')]
    public function tclass_category_exists(mixed $id): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('tclass_category_get')]
    public function tclass_category_get(mixed $id): array|null;

    #[DbQuery('tclass_category_insert')]
    public function tclass_category_insert(mixed $id, mixed $classNameId, mixed $name, mixed $sortNo): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('tclass_category_list')]
    public function tclass_category_list(): array;

    /** @return list<array<string, mixed>> */
    #[DbQuery('tclass_category_list_by_class_name')]
    public function tclass_category_list_by_class_name(mixed $classNameId): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('tclass_category_next_sort')]
    public function tclass_category_next_sort(mixed $classNameId): array|null;

    #[DbQuery('tclass_category_reorder')]
    public function tclass_category_reorder(mixed $sortNo, mixed $id): void;

    #[DbQuery('tclass_category_update')]
    public function tclass_category_update(mixed $classNameId, mixed $name, mixed $id): void;

    #[DbQuery('tclass_category_visible')]
    public function tclass_category_visible(mixed $visible, mixed $id): void;

    #[DbQuery('tclass_name_children_delete')]
    public function tclass_name_children_delete(mixed $id): void;

    #[DbQuery('tclass_name_delete')]
    public function tclass_name_delete(mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('tclass_name_exists')]
    public function tclass_name_exists(mixed $id): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('tclass_name_get')]
    public function tclass_name_get(mixed $id): array|null;

    #[DbQuery('tclass_name_insert')]
    public function tclass_name_insert(mixed $id, mixed $name, mixed $sortNo): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('tclass_name_list')]
    public function tclass_name_list(): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('tclass_name_next_sort')]
    public function tclass_name_next_sort(): array|null;

    #[DbQuery('tclass_name_reorder')]
    public function tclass_name_reorder(mixed $sortNo, mixed $id): void;

    #[DbQuery('tclass_name_update')]
    public function tclass_name_update(mixed $name, mixed $id): void;

    #[DbQuery('tdelivery_delete')]
    public function tdelivery_delete(mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('tdelivery_exists')]
    public function tdelivery_exists(mixed $id): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('tdelivery_get')]
    public function tdelivery_get(mixed $id): array|null;

    #[DbQuery('tdelivery_insert')]
    public function tdelivery_insert(mixed $id, mixed $name, mixed $visible): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('tdelivery_list')]
    public function tdelivery_list(): array;

    #[DbQuery('tdelivery_reorder')]
    public function tdelivery_reorder(mixed $sortNo, mixed $id): void;

    #[DbQuery('tdelivery_update')]
    public function tdelivery_update(mixed $name, mixed $visible, mixed $id): void;

    #[DbQuery('tdelivery_visible')]
    public function tdelivery_visible(mixed $visible, mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('tlayout_exists')]
    public function tlayout_exists(mixed $id): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('tlayout_get')]
    public function tlayout_get(mixed $id): array|null;

    #[DbQuery('tlayout_insert')]
    public function tlayout_insert(mixed $id, mixed $deviceType, mixed $layoutName): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('tlayout_list')]
    public function tlayout_list(): array;

    #[DbQuery('tlayout_update')]
    public function tlayout_update(mixed $layoutName, mixed $deviceType, mixed $id): void;

    #[DbQuery('tlogin_history_insert')]
    public function tlogin_history_insert(mixed $statusId, mixed $loginId, mixed $clientIp, mixed $created): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('tlogin_history_list')]
    public function tlogin_history_list(mixed $limit): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('tmail_template_exists')]
    public function tmail_template_exists(mixed $id): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('tmail_template_get')]
    public function tmail_template_get(mixed $id): array|null;

    /** @return list<array<string, mixed>> */
    #[DbQuery('tmail_template_list')]
    public function tmail_template_list(): array;

    #[DbQuery('tmail_template_update')]
    public function tmail_template_update(mixed $subject, mixed $id): void;

    #[DbQuery('tnews_delete')]
    public function tnews_delete(mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('tnews_exists')]
    public function tnews_exists(mixed $id): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('tnews_get')]
    public function tnews_get(mixed $id): array|null;

    #[DbQuery('tnews_insert')]
    public function tnews_insert(mixed $id, mixed $title, mixed $description, mixed $url, mixed $linkMethod, mixed $publishDate): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('tnews_list')]
    public function tnews_list(): array;

    #[DbQuery('tnews_update')]
    public function tnews_update(mixed $title, mixed $description, mixed $url, mixed $publishDate, mixed $linkMethod, mixed $id): void;

    #[DbQuery('tnews_visible')]
    public function tnews_visible(mixed $visible, mixed $id): void;

    #[DbQuery('tpage_delete')]
    public function tpage_delete(mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('tpage_exists')]
    public function tpage_exists(mixed $id): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('tpage_get')]
    public function tpage_get(mixed $id): array|null;

    #[DbQuery('tpage_insert')]
    public function tpage_insert(mixed $id, mixed $pageName, mixed $url, mixed $fileName, mixed $editType): void;

    #[DbQuery('tpage_layout_delete')]
    public function tpage_layout_delete(mixed $id): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('tpage_list')]
    public function tpage_list(): array;

    #[DbQuery('tpage_update')]
    public function tpage_update(mixed $pageName, mixed $url, mixed $fileName, mixed $editType, mixed $id): void;

    #[DbQuery('tpayment_delete')]
    public function tpayment_delete(mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('tpayment_exists')]
    public function tpayment_exists(mixed $id): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('tpayment_get')]
    public function tpayment_get(mixed $id): array|null;

    #[DbQuery('tpayment_insert')]
    public function tpayment_insert(mixed $id, mixed $paymentMethod, mixed $charge, mixed $ruleMax, mixed $ruleMin, mixed $visible): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('tpayment_list')]
    public function tpayment_list(): array;

    #[DbQuery('tpayment_option_delete')]
    public function tpayment_option_delete(mixed $id): void;

    #[DbQuery('tpayment_reorder')]
    public function tpayment_reorder(mixed $sortNo, mixed $id): void;

    #[DbQuery('tpayment_update')]
    public function tpayment_update(mixed $paymentMethod, mixed $charge, mixed $ruleMin, mixed $ruleMax, mixed $visible, mixed $id): void;

    #[DbQuery('tpayment_visible')]
    public function tpayment_visible(mixed $visible, mixed $id): void;

    #[DbQuery('ttax_rule_delete')]
    public function ttax_rule_delete(mixed $id): void;

    /** @return array<string, mixed>|null */
    #[DbQuery('ttax_rule_exists')]
    public function ttax_rule_exists(mixed $id): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('ttax_rule_get')]
    public function ttax_rule_get(mixed $id): array|null;

    #[DbQuery('ttax_rule_insert')]
    public function ttax_rule_insert(mixed $id, mixed $taxRate, mixed $applyDate): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('ttax_rule_list')]
    public function ttax_rule_list(): array;

    #[DbQuery('ttax_rule_update')]
    public function ttax_rule_update(mixed $taxRate, mixed $applyDate, mixed $id): void;

    /** @return list<array<string, mixed>> */
    #[DbQuery('ttemplate_list')]
    public function ttemplate_list(): array;

    /** @return array<string, mixed>|null */
    #[DbQuery('ttrade_law_exists')]
    public function ttrade_law_exists(): array|null;

    /** @return array<string, mixed>|null */
    #[DbQuery('ttrade_law_get')]
    public function ttrade_law_get(): array|null;

    #[DbQuery('ttrade_law_insert')]
    public function ttrade_law_insert(mixed $description): void;

    #[DbQuery('ttrade_law_update')]
    public function ttrade_law_update(mixed $description): void;

}
