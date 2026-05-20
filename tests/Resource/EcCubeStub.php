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
            // --- front.entry.* (Entry/index.twig) -----------------------
            'front.entry.title' => '新規会員登録',
            'front.entry.agree' => '同意する',
            'front.entry.disagree' => '同意しない',
            'front.entry.agree_with_terms' => '<a class="ec-link" href="%url%" target="_blank">利用規約</a>に同意してお進みください',
            // --- front.contact.* (Contact/index.twig) -------------------
            'front.contact.title' => 'お問い合わせ',
            'front.contact.inquiry_notice' => "内容によっては回答をさしあげるのにお時間をいただくこともございます。\nまた、休業日は翌営業日以降の対応となりますのでご了承ください。\n",
            'front.contact.inquiry_contents' => 'お問い合わせ内容',
            'front.contact.order_notice' => 'ご注文に関するお問い合わせには、必ず「ご注文番号」をご記入くださいますようお願いいたします。',
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
        ];
    }
}
