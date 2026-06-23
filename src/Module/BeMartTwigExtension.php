<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use MyVendor\BeMart\Auth\CartSessionPrefixInterface;
use MyVendor\BeMart\Auth\EccubeSharedCsrfTokenAdapter;
use MyVendor\BeMart\Auth\EccubeSharedSessionAdapter;
use MyVendor\BeMart\Be\Reason\Query\CartQueryInterface;
use MyVendor\BeMart\Support\Resource\AdminFlash;
use NumberFormatter;
use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function bin2hex;
use function is_string;
use function random_bytes;

/**
 * Twig helpers the EC-CUBE template port relies on.
 *
 * The BeMart storefront templates are PORTS of EC-CUBE 4.3's default-theme
 * Twig files (see var/templates/README.md). EC-CUBE's templates call a
 * handful of Twig functions/filters provided by Symfony / EC-CUBE's own
 * `EccubeExtension`. To keep the ported markup byte-faithful while keeping
 * the rebinding minimal, this extension supplies the small subset the Cart
 * port uses:
 *
 *  - `price`  filter   — EC-CUBE's `EccubeExtension::getPriceFilter`. Same
 *                        impl: NumberFormatter CURRENCY for JPY, yielding
 *                        e.g. `￥1,200`.
 *  - `asset`  function — EC-CUBE/Symfony asset(). BeMart has no asset-hash
 *                        pipeline, so this resolves a path to a static URL.
 *                        The optional second argument is EC-CUBE's asset
 *                        PACKAGE (`assets.packages` in framework.yaml): the
 *                        `default` theme, the `admin` theme and the webpack
 *                        `bundle` output are physically distinct asset roots
 *                        — EC-CUBE serves them under different base paths so
 *                        same-named files (`assets/js/function.js` exists in
 *                        BOTH themes, byte-different) never collide. This
 *                        method is a faithful port of that package map: the
 *                        deployed `public/` tree mirrors EC-CUBE's served
 *                        URLs (`/assets`, `/template/admin/assets`,
 *                        `/bundle`), so every package resolves to a real,
 *                        byte-identical EC-CUBE file.
 *  - CSRF helpers      — EC-CUBE-compatible CSRF token helpers. Links and
 *                        forms use canonical BEAR Resource URLs directly.
 *
 * Every value produced here is deterministic, so the rendered HTML is
 * diffable against EC-CUBE's output (residual-diff verification).
 */
final class BeMartTwigExtension extends AbstractExtension
{
    /**
     * Storefront category tree for the header search select.
     *
     * Single source of truth for the storefront category vocabulary, mirroring
     * the `category_id => name` map that {@see \MyVendor\BeMart\Resource\Page\Products}
     * filters on (its `categoryName()`) and that the ported
     * `Block/category_nav_pc.twig` lists. EC-CUBE renders this select from the
     * Category repository (`form.category_id` choices); BeMart's fake corpus
     * carries these six fixture categories, so the frame exposes the same six
     * here until a DB-backed category resource lands. Keyed in EC-CUBE display
     * order (新入荷 first) to match `category_nav_pc`.
     *
     * @var array<int, string>
     */
    public const CATEGORIES = [
        2 => '新入荷',
        1 => 'ジェラート',
        3 => '彩のデザート',
        4 => 'CUBE',
        5 => 'アイスサンド',
        6 => 'フルーツ',
    ];

    /**
     * Cart deps are OPTIONAL so render-test / CLI contexts can keep using
     * `new BeMartTwigExtension()`; the html context injects the real
     * session-prefix resolver + cart query via {@see HtmlTwigEnvironmentProvider}
     * so the frame's cart badge reflects the live session cart.
     */
    public function __construct(
        private readonly CartSessionPrefixInterface|null $cartSessionPrefix = null,
        private readonly CartQueryInterface|null $cartQuery = null,
    ) {
    }

    /** @return list<TwigFilter> */
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('price', [$this, 'price']),
        ];
    }

    /** @return list<TwigFunction> */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset', [$this, 'asset']),
            new TwigFunction('csrf_token', [$this, 'csrfToken']),
            new TwigFunction('csrf_token_for_anchor', [$this, 'csrfTokenForAnchor']),
            new TwigFunction('is_logged_in', [$this, 'isLoggedIn']),
            new TwigFunction('cart_summary', [$this, 'cartSummary']),
            new TwigFunction('storefront_categories', [$this, 'categories']),
            new TwigFunction('admin_flashes', [$this, 'adminFlashes']),
        ];
    }

    /**
     * Consume the admin success-flash queue for the admin frame banner.
     *
     * Port of EC-CUBE's `@admin/alert.twig` reading
     * `app.flashes('eccube.admin.success')`. The admin write resources push
     * 「保存しました」 via {@see AdminFlash::add()} on the POST-redirect-GET;
     * the admin frame ({@see admin-base.html.twig}) renders each returned
     * message as an `alert alert-success` banner. Consume-once: the banner
     * shows exactly on the redirected page and is gone on the next reload.
     *
     * @return list<string>
     */
    public function adminFlashes(): array
    {
        return AdminFlash::consume();
    }

    /**
     * Live cart totals for the frame's header cart navi (Block/cart.html.twig).
     *
     * The ported EC-CUBE frame includes the cart block on every storefront
     * page; without this the badge/price were hardcoded to 0, so a cart with
     * items still showed "0" in the header. Resolves the current session's
     * cart key prefix and sums quantity + price across its carts. Returns zero
     * totals when cart deps are absent (render-test/CLI) or no session prefix
     * is available, so the markup stays renderable everywhere.
     *
     * @return array{quantity: int, price: int}
     */
    public function cartSummary(): array
    {
        if ($this->cartSessionPrefix === null || $this->cartQuery === null) {
            return ['quantity' => 0, 'price' => 0];
        }

        $prefix = $this->cartSessionPrefix->prefix();
        if ($prefix === null || $prefix === '') {
            return ['quantity' => 0, 'price' => 0];
        }

        $quantity = 0;
        $price = 0;
        foreach ($this->cartQuery->listBySessionPrefix($prefix) as $cart) {
            $price += $cart->totalPrice;
            foreach ($cart->items as $item) {
                $quantity += $item->quantity;
            }
        }

        return ['quantity' => $quantity, 'price' => $price];
    }

    /**
     * Storefront category options for the header search select.
     *
     * Port of EC-CUBE's `form.category_id` choices that
     * `Block/search_product.twig` renders. Without this the header select
     * carried only the empty 「全ての商品」 option, so a user could not filter
     * by category from the header even though {@see \MyVendor\BeMart\Resource\Page\Products}
     * implements `category_id=1..6` filtering. Returns the six fixture
     * categories ({@see self::CATEGORIES}) as `{id, name}` rows in EC-CUBE
     * display order. Deterministic and DB-free, so the markup stays renderable
     * in render-test / CLI contexts identically to the live html context.
     *
     * @return list<array{id: int, name: string}>
     */
    public function categories(): array
    {
        $rows = [];
        foreach (self::CATEGORIES as $id => $name) {
            $rows[] = ['id' => $id, 'name' => $name];
        }

        return $rows;
    }

    /**
     * Mirror of EC-CUBE EccubeExtension::getPriceFilter for the JPY store.
     */
    public function price(int|float|null $number): string
    {
        $formatter = new NumberFormatter('ja_JP', NumberFormatter::CURRENCY);

        return (string) $formatter->formatCurrency((float) ($number ?? 0), 'JPY');
    }

    /**
     * Port of EC-CUBE's asset() — resolves $path under its asset PACKAGE.
     *
     * EC-CUBE's `assets.packages` (framework.yaml) gives each package a
     * `base_path`; the deployed `public/` tree mirrors those served URLs:
     *
     *  - default (no package) — the `default` storefront theme  -> `/`
     *  - `admin`              — the admin theme                 -> `/template/admin/`
     *  - `bundle`             — the webpack output               -> `/bundle/`
     *  - `save_image`         — uploaded product imagery         -> `/`
     *
     * `save_image` paths in the BeMart port are written as ordinary
     * `assets/img/...` literals (the no-image placeholder is deployed under
     * `public/assets/img/common/`), so it resolves like the default package.
     */
    public function asset(string $path, string $package = ''): string
    {
        $prefix = match ($package) {
            'admin' => '/template/admin/',
            'bundle' => '/bundle/',
            default => '/',
        };

        return $prefix . $path;
    }

    /**
     * Minimal EC-CUBE-compatible CSRF widget for ported Twig templates.
     *
     * The html front controller starts PHP's session before rendering, so the
     * generated token is stored under the same flat key that the production
     * CSRF adapter validates on POST. In CLI/render-test contexts with no
     * active session, returning a fresh non-empty token is enough to keep
     * templates renderable.
     */
    public function csrfToken(string $tokenId = ''): string
    {
        /** @var mixed $stored */
        $stored = $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] ?? null;
        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        $token = bin2hex(random_bytes(32));
        if (isset($_SESSION)) {
            $_SESSION[EccubeSharedCsrfTokenAdapter::SESSION_KEY] = $token;
        }

        return $token;
    }

    /** EC-CUBE's anchor-token helper; BeMart reuses the same request token. */
    public function csrfTokenForAnchor(string $tokenId = ''): string
    {
        return $this->csrfToken($tokenId);
    }

    /**
     * Whether a customer is authenticated in the current session.
     *
     * Mirrors EC-CUBE's `is_granted('ROLE_USER')` for the ported storefront
     * header: reads the flat customer-id session key the html-context session
     * writer and {@see EccubeSharedSessionAdapter} share. Lets Block/login
     * render the logged-in affordances (マイページ / ログアウト) instead of
     * always showing the anonymous ログイン link.
     *
     * @SuppressWarnings("PHPMD.Superglobals") Session boundary, like csrfToken().
     */
    public function isLoggedIn(): bool
    {
        $customerId = $_SESSION[EccubeSharedSessionAdapter::CUSTOMER_ID_KEY] ?? null;

        return is_string($customerId) && $customerId !== '';
    }
}
