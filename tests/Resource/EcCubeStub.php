<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use ReturnTypeWillChange;
use Traversable;

use function array_key_exists;
use function count;

/**
 * Generic property bag used by {@see CartHtmlRenderTest} to feed EC-CUBE's
 * real Cart/index.twig + default_frame.twig without booting Doctrine.
 *
 * EC-CUBE's templates access entity data as `Cart.cart_key`,
 * `Cart.CartItems`, `CartItem.ProductClass.Product.name`, etc. Twig
 * resolves `a.b` against an object by trying the property, then getB()/
 * isB()/hasB(), then array access. This stub exposes everything as public
 * dynamic state via ArrayAccess + magic __get so any of those resolution
 * paths land on the supplied value. It is test scaffolding only — it
 * carries no behaviour, just the cart shape EC-CUBE's template reads.
 *
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
final class EcCubeStub implements ArrayAccess, IteratorAggregate, Countable
{
    /** @var list<mixed>|null Explicit `{% for %}` iteration items. */
    private array|null $iterationItems;

    /**
     * @param array<string, mixed> $data
     * @param list<mixed>|null     $iterationItems When EC-CUBE iterates
     *        the object itself (`{% for x in pagination %}`,
     *        `{% for x in search_form %}`) the iteration set differs from
     *        the property bag — e.g. a `pagination` with `totalItemCount`
     *        readable but ZERO product rows. Pass the iteration items
     *        explicitly; `null` falls back to iterating `$data` (the
     *        Cart-pilot behaviour).
     */
    public function __construct(
        private array $data = [],
        array|null $iterationItems = null,
    ) {
        $this->iterationItems = $iterationItems;
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function __call(string $name, array $args): mixed
    {
        // `app.request.get('_route')`-style accessor: a generic getter
        // called with a key argument resolves that key.
        if ($name === 'get' && isset($args[0])) {
            return $this->data[(string) $args[0]] ?? null;
        }

        return $this->data[$name] ?? null;
    }

    #[ReturnTypeWillChange]
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists((string) $offset, $this->data);
    }

    #[ReturnTypeWillChange]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[(string) $offset] ?? null;
    }

    #[ReturnTypeWillChange]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[(string) $offset] = $value;
    }

    #[ReturnTypeWillChange]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[(string) $offset]);
    }

    public function getIterator(): Traversable
    {
        yield from $this->iterationItems ?? $this->data;
    }

    public function count(): int
    {
        return count($this->iterationItems ?? $this->data);
    }

    /**
     * The EC-CUBE 4.3 messages.ja.yaml values the storefront template
     * ports substitute for `{{ '...'|trans }}`. Only the keys the ported
     * pages (Cart / Top / ProductList / Login / Entry / Contact) and
     * default_frame.twig actually use are listed — the values are copied
     * verbatim from src/Eccube/Resource/locale/messages.ja.yaml.
     *
     * @return array<string, string>
     */
    public static function jaMessages(): array
    {
        return [
            // --- Cart/index.twig + default_frame.twig -------------------
            'front.cart.title' => 'ショッピングカート',
            'front.cart.nav__cart_items' => 'カートの商品',
            'front.cart.nav__customer_info' => 'お客様情報',
            'front.cart.nav__order' => 'ご注文手続き',
            'front.cart.nav__confirm' => 'ご注文内容確認',
            'front.cart.nav__complete' => '完了',
            'front.cart.total_price' => '商品の合計金額は「<strong>%price%</strong>」です。',
            'front.cart.divide_cart' => '同時購入できない商品のカートを分けました。お手数ですが別々で注文してください。',
            'front.cart.delete' => '削除',
            'front.cart.delete__confirm' => 'カートから商品を削除してよろしいですか？',
            'front.cart.product' => '商品内容',
            'front.cart.checkout' => 'レジに進む',
            'front.cart.continue' => 'お買い物を続ける',
            'front.cart.no_items' => '現在カート内に商品はございません。',
            'common.quantity' => '数量',
            'common.quantity__with_separator' => '数量：',
            'common.subtotal' => '小計',
            'common.subtotal__with_separator' => '小計：',
            'common.total__with_separator' => '合計：',
            'common.pagetop' => 'ページトップへ',
            // --- common.* (Login / Entry / Contact / ProductList) -------
            'common.login' => 'ログイン',
            'common.remember_me' => '次回から自動的にログインする',
            'common.signup' => '新規会員登録',
            'common.forgot_login' => 'ログイン情報をお忘れですか？',
            'common.name' => 'お名前',
            'common.kana' => 'お名前(カナ)',
            'common.first_name' => '名',
            'common.last_name' => '姓',
            'common.first_name_kana' => 'メイ',
            'common.last_name_kana' => 'セイ',
            'common.company_name' => '会社名',
            'common.address' => '住所',
            'common.postal_symbol' => '〒',
            'common.search_postal_code' => '郵便番号検索',
            'common.address_sample_01' => '市区町村名(例：大阪市北区)',
            'common.address_sample_02' => '番地・ビル名(例：西梅田1丁目6-8)',
            'common.phone_number' => '電話番号',
            'common.mail_address' => 'メールアドレス',
            'common.mail_address_sample' => '例：ec-cube@example.com',
            'common.repeated_confirm' => '確認のためもう一度入力してください',
            'common.password' => 'パスワード',
            'common.password_sample' => '半角英数記号%min%〜%max%文字',
            'common.gender' => '性別',
            'common.birth_day' => '生年月日',
            'common.job' => '職業',
            'common.go_to_confirm' => '確認ページへ',
            'common.go_to_cart' => 'カートへ進む',
            'common.next' => '次へ',
            'common.registration' => '登録する',
            // --- front.forgot.* (Forgot/index|reset|complete.twig) ------
            'front.forgot.title' => 'パスワードの再発行',
            'front.forgot.message1' => 'ご登録時のメールアドレスを入力して「次へ」ボタンをクリックしてください。',
            'front.forgot.message2' => '※パスワード再発行メールを送信します。メールの内容をご確認の上、新しいパスワードを登録してください。',
            'front.forgot.reset_title' => 'パスワード再発行(再設定)',
            'front.forgot.complete_title' => 'パスワードの再発行(メール送信)',
            'front.forgot.complete_message__title' => 'パスワード再発行メールの送信が完了しました。',
            'front.forgot.complete_message__body' => "ご登録メールアドレスにパスワードを再発行するためのメールを送信いたしました。\nメールの内容をご確認いただきますよう、お願いいたします。\n※メールが届かない場合はメールアドレスをご確認の上、再度お試しください。\n",
            // --- front.entry.* (Entry/index|confirm|complete|activate) --
            'front.entry.title' => '新規会員登録',
            'front.entry.agree' => '同意する',
            'front.entry.disagree' => '同意しない',
            'front.entry.agree_with_terms' => '<a class="ec-link" href="%url%" target="_blank">利用規約</a>に同意してお進みください',
            'front.entry.confirm_title' => '新規会員登録(確認)',
            'front.entry.confirm_message' => "下記の内容で登録してもよろしいでしょうか？\nよろしければ、一番下の「会員登録をする」ボタンをクリックしてください。\n",
            'front.entry.do_register' => '会員登録をする',
            'front.entry.complete_title' => '新規会員登録(完了)',
            'front.entry.complete_title__temporary' => '新規会員登録(仮登録完了)',
            'front.entry.complete_message__title' => '会員登録ありがとうございます',
            'front.entry.complete_message__temporary' => "現在、仮会員の状態です。ただいま、ご入力いただいたメールアドレス宛に、ご本人様確認用のメールをお送りいたしました。\nメール本文内のURLをクリックすると、本会員登録が完了となります。\n",
            'front.entry.complete_message__activated' => "会員登録が完了しました。\nメールアドレスとパスワードでログイン後、ショッピングをお楽しみください。\n",
            // --- front.contact.* (Contact/index|confirm|complete) -------
            'front.contact.title' => 'お問い合わせ',
            'front.contact.inquiry_notice' => "内容によっては回答をさしあげるのにお時間をいただくこともございます。\nまた、休業日は翌営業日以降の対応となりますのでご了承ください。\n",
            'front.contact.inquiry_contents' => 'お問い合わせ内容',
            'front.contact.order_notice' => 'ご注文に関するお問い合わせには、必ず「ご注文番号」をご記入くださいますようお願いいたします。',
            'front.contact.complete_title' => 'お問い合わせ(完了)',
            'front.contact.complete_message__title' => 'お問い合わせ内容の送信が完了いたしました',
            'front.contact.complete_message__body' => '万一、ご確認メールが届かない場合は、トラブルの可能性もありますので大変お手数ではございますがもう一度お問い合わせいただくか、お電話にてお問い合わせくださいませ。',
            // --- front.product.* (Product/list.twig) --------------------
            'front.product.all_category' => '全て',
            'front.product.search__category_not_found' => 'ご指定のカテゴリは存在しません',
            'front.product.search__product_not_found' => 'お探しの商品は見つかりませんでした',
            'front.product.search_result__keyword' => '「%name%」の検索結果',
            'front.product.search_result__detail' => '<span class="ec-font-bold">%count%件</span><span>の商品が見つかりました</span>',
            'front.product.add_cart_complete' => 'カートに追加しました。',
            'front.product.add_cart_error' => 'カートへの追加に失敗しました。',
            'front.product.add_cart' => 'カートに入れる',
            'front.product.out_of_stock' => 'ただいま品切れ中です。',
            'front.product.continue' => 'お買い物を続ける',
            'front.product.invalid_quantity' => '1以上で入力してください。',
            'front.product.code' => '商品コード',
            'front.product.product_class_unselected' => '項目が選択されていません',
            'front.product.normal_price' => '通常価格',
            'front.product.related_category' => '関連カテゴリ',
            'front.product.add_favorite' => 'お気に入りに追加',
            'front.product.delete_favorite' => 'お気に入りから削除',
            'front.product.delete_favorite_confirm' => 'お気に入りから削除してもよろしいですか？',
            // --- front.mypage.* (Mypage/*.twig + navi.twig) -------------
            'front.mypage.title' => 'マイページ',
            'front.mypage.welcome' => 'ようこそ%last_name% %first_name%さん',
            'front.mypage.welcome__point' => '現在の所持ポイントは %point%pt です。',
            'front.mypage.nav__history' => 'ご注文履歴',
            'front.mypage.nav__history_detail' => 'ご注文履歴詳細',
            'front.mypage.nav__favorite' => 'お気に入り一覧',
            'front.mypage.nav__customer' => '会員情報編集',
            'front.mypage.nav__customer_address' => 'お届け先一覧',
            'front.mypage.nav__withdrow' => '退会手続き',
            'front.mypage.history_count' => '%count%件の履歴があります',
            'front.mypage.history_not_found' => 'ご注文履歴はありません。',
            'front.mypage.message_not_found' => '記載なし',
            'front.mypage.view_detail' => '詳細を見る',
            'front.mypage.order_no' => 'ご注文番号',
            'front.mypage.order_status' => 'ご注文状況',
            'front.mypage.order_date' => 'ご注文日時',
            'front.mypage.use_point' => 'ご利用ポイント',
            'front.mypage.add_point' => '加算ポイント',
            'front.mypage.delivery_info' => '配送情報',
            'front.mypage.delivery' => 'お届け先',
            'front.mypage.delivery_provider' => '配送方法',
            'front.mypage.delivery_date' => 'お届け日',
            'front.mypage.delivery_time' => 'お届け時間',
            'front.mypage.current_price' => '【現在価格】',
            'front.mypage.reorder' => '再注文する',
            'front.mypage.reorder_message' => '※金額が変更されている商品があるため、再注文時はご注意ください。',
            'front.mypage.payment_info' => 'お支払い情報',
            'front.mypage.payment' => 'お支払い方法',
            'front.mypage.message' => 'お問い合わせ',
            'front.mypage.mail_not_found' => 'メール履歴はありません。',
            'front.mypage.mail_list' => 'メール配信履歴一覧',
            'front.mypage.favorite_count' => '%count%件のお気に入りがあります',
            'front.mypage.favorite_not_found' => 'お気に入りは登録されていません。',
            'front.mypage.customer_address_count' => '%count%件のお届け先があります',
            'front.mypage.customer_address_not_found' => 'お届け先は登録されていません。',
            'front.mypage.add_customer_address' => '新規お届け先を追加する',
            'front.mypage.withdraw_message__title' => '退会手続きの前にご確認ください',
            'front.mypage.withdraw_message__body' => '退会手続きが完了した時点で、現在保存されている購入履歴やお届け先等の情報は、すべて削除されますのでご注意ください。',
            'front.mypage.withdraw_confirm' => '退会手続きへ',
            // --- Help/* (about / agreement / privacy / guide / tradelaw) -
            'front.about.title' => '当サイトについて',
            'front.about.business_hour' => '店舗営業時間',
            'front.about.good_traded' => '取り扱い商品',
            'front.about.message' => 'メッセージ',
            'front.agreement.title' => '利用規約',
            'front.tradelaw.title' => '特定商取引法に基づく表記',
            'front.privacy.title' => 'プライバシーポリシー',
            'front.guide.title' => 'ご利用ガイド',
            'common.shop_name' => '店名',
            // --- common.* (Mypage form / order-summary keys) ------------
            'common.back' => '戻る',
            'common.send' => '送信する',
            'common.close' => '閉じる',
            'common.change' => '変更',
            'common.tax_include' => '税込',
            'common.total' => '合計',
            'common.charge' => '手数料',
            'common.delivery_fee' => '送料',
            'common.discount' => '値引き',
            'common.tax_rate_target' => '税率 %rate% %対象',
            'common.tax_amount' => '内消費税',
            'common.customer_address_count_is_over' => 'お届け先登録の上限の%count%件に達しています。お届け先を入力したい場合は、削除か変更を行ってください。',
            'common.select__unspecified' => '指定なし',
            // --- Shopping/* (index / confirm / complete / login /
            //     nonmember / shipping / shipping_edit / shipping_multiple
            //     / shopping_error) -------------------------------------
            'common.select__pref' => '都道府県を選択',
            'common.required' => '必須',
            'common.ok' => 'OK',
            'common.cancel' => 'キャンセル',
            'common.go_to_top' => 'トップページへ',
            'common.payment_total' => 'お支払い合計',
            'common.name.prefix' => '',
            'common.name.suffix' => ' 様',
            'common.next' => '次へ',
            'common.registration' => '登録する',
            'front.cart.nav__cart_items' => 'カートの商品',
            'front.cart.nav__customer_info' => 'お客様情報',
            'front.cart.nav__order' => 'ご注文手続き',
            'front.cart.nav__confirm' => 'ご注文内容確認',
            'front.cart.nav__complete' => '完了',
            'front.shopping.title' => 'ご注文手続き',
            'front.shopping.order_no' => 'ご注文番号',
            'front.shopping.customer_info' => 'お客様情報',
            'front.shopping.delivery_info' => '配送情報',
            'front.shopping.delivery_to' => 'お届け先',
            'front.shopping.delivery_provider' => '配送方法',
            'front.shopping.delivery_date' => 'お届け日',
            'front.shopping.delivery_time' => 'お届け時間',
            'front.shopping.to_multiple' => 'お届け先を追加する',
            'front.shopping.payment_info' => 'お支払方法',
            'front.shopping.message_info' => 'お問い合わせ',
            'front.shopping.message_placeholder' => 'お問い合わせ事項がございましたら、こちらにご入力ください。(3000文字まで)',
            'front.shopping.go_to_confirm' => '確認する',
            'front.shopping.back_to_cart' => 'カートに戻る',
            'front.shopping.back_to_order' => 'ご注文手続きに戻る',
            'front.shopping.shipping_title' => 'お届け先の指定',
            'front.shopping.shipping_add_new_shipping' => '新規お届け先を追加する',
            'front.shopping.shipping_add_new_shipping__short' => 'お届け先追加',
            'front.shopping.shipping_unselected' => 'お届け先を指定してください',
            'front.shopping.shipping_send_selected_shipping' => '選択したお届け先に送る',
            'front.shopping.shipping_multiple_title' => 'お届け先の複数指定',
            'front.shopping.shipping_multiple_message' => '各商品のお届け先を選択してください。',
            'front.shopping.confirm_title' => 'ご注文内容のご確認',
            'front.shopping.checkout' => '注文する',
            'front.shopping.complete_title' => 'ご注文完了',
            'front.shopping.complete_message__title' => 'ご注文ありがとうございました',
            'front.shopping.complete_message__body' => "ただいま、ご注文の確認メールをお送りさせていただきました。\n万一、ご確認メールが届かない場合は、トラブルの可能性もありますので大変お手数ではございますがお問い合わせくださいますようお願いいたします。\n",
            'front.shopping.continue' => '購入を続ける',
            'front.shopping.guest_purchase_message' => '会員登録をせずに購入手続きをされたい方は、下記よりお進みください。',
            'front.shopping.guest_purchase' => 'ゲスト購入',
            'front.shopping.nonmember' => 'お客様情報の入力',
            'front.shopping.shipping_edit_title_nomember' => '商品購入/お届け先の変更',
            'front.shopping.shipping_edit_header_customer' => 'お届け先の追加',
            'front.shopping.shipping_edit_header_nonmember' => 'お届け先の変更',
            'front.shopping.error' => '購入エラー',
        ];
    }
}
