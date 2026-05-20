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
    /** @param array<string, mixed> $data */
    public function __construct(private array $data = [])
    {
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
        yield from $this->data;
    }

    public function count(): int
    {
        return count($this->data);
    }

    /**
     * The EC-CUBE 4.3 messages.ja.yaml values the Cart port substitutes
     * for `{{ '...'|trans }}`. Only the keys Cart/index.twig +
     * default_frame.twig actually use are listed.
     *
     * @return array<string, string>
     */
    public static function jaMessages(): array
    {
        return [
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
        ];
    }
}
