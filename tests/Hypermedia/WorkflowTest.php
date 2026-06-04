<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Hypermedia;

use Aura\Router\Map;
use Aura\Router\RouterContainer;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use DOMDocument;
use DOMElement;
use DOMXPath;
use MyVendor\BeMart\Auth\HtmlCartSession;
use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Tests\Support\Hypermedia\AbstractWorkflowTest;

use function array_key_exists;
use function assert;
use function getenv;
use function is_array;
use function is_int;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function putenv;
use function session_cache_limiter;
use function session_destroy;
use function session_id;
use function session_save_path;
use function session_start;
use function session_status;
use function session_write_close;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function sys_get_temp_dir;
use function trim;
use function uniqid;

use const PHP_SESSION_ACTIVE;

class WorkflowTest extends AbstractWorkflowTest
{
    private string|false|null $appContextBefore = null;
    private bool $startedSession = false;

    protected function setUp(): void
    {
        $this->appContextBefore = getenv('APP_CONTEXT');
        putenv('APP_CONTEXT=html-test-hal-api-app');
        $this->startActiveSession();

        parent::setUp();
    }

    protected function newResource(): ResourceInterface
    {
        $resource = Injector::getInstance('html-test-hal-api-app')->getInstance(ResourceInterface::class);
        assert($resource instanceof ResourceInterface);

        return new RoutedResource($resource, self::routerContainer());
    }

    protected function tearDown(): void
    {
        if ($this->startedSession && session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION[HtmlCartSession::CART_SESSION_PREFIX_KEY]);
            $_SESSION = [];
            session_destroy();
            session_write_close();
            session_id('');
        }

        if ($this->appContextBefore === null) {
            return;
        }

        if ($this->appContextBefore === false) {
            putenv('APP_CONTEXT');

            return;
        }

        putenv('APP_CONTEXT=' . $this->appContextBefore);
    }

    private static function routerContainer(): RouterContainer
    {
        $container = new RouterContainer();
        /** @var callable(Map): null $routes */
        $routes = require __DIR__ . '/../../config/aura-routes.php';
        $container->setMapBuilder($routes);

        return $container;
    }

    public function testCustomerCanFollowStorefrontPurchaseSpineToCart(): void
    {
        $home = $this->resource->get('/');
        $this->assertSame(Code::OK, $home->code);
        $homeHtml = $home->toString();
        $this->assertRenderedAnchor($homeHtml, '/products/list');
        $this->assertRenderedAnchor($homeHtml, '/cart');

        $products = $this->resource->get('/products/list');
        $this->assertSame(Code::OK, $products->code);
        $productsHtml = $products->toString();
        $detailPath = $this->firstHrefStartingWith($productsHtml, '/products/detail/');
        $this->assertRenderedAnchor($productsHtml, $detailPath);

        $detail = $this->resource->get($detailPath);
        $this->assertSame(Code::OK, $detail->code);
        $detailHtml = $detail->toString();
        $productName = $this->firstText(
            $detailHtml,
            '//h2[contains(concat(" ", normalize-space(@class), " "), " ec-headingTitle ")]',
        );

        $form = $this->assertRenderedForm($detailHtml, 'post', '/products/add_cart/');
        $fields = $this->formFields($form);
        $this->assertArrayHasKey('_token', $fields);
        $this->assertArrayHasKey('product_id', $fields);
        $this->assertArrayHasKey('quantity', $fields);
        $this->assertSame(sprintf('/products/add_cart/%s', $fields['product_id']), $form->getAttribute('action'));

        $added = $this->resource->post($form->getAttribute('action'), $this->canonicalizeFormFields($fields));
        $this->assertContains($added->code, [Code::CREATED, Code::SEE_OTHER]);
        $this->assertSame('/cart', $this->header($added, 'Location'));

        $cart = $this->resource->get('/cart');
        $this->assertSame(Code::OK, $cart->code);
        $this->assertCartShowsProduct($cart, $productName, $fields['product_id']);

        $cartAgain = $this->resource->get('/cart');
        $this->assertSame(Code::OK, $cartAgain->code);
        $this->assertCartShowsProduct($cartAgain, $productName, $fields['product_id']);
    }

    private function startActiveSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            $this->startedSession = true;

            return;
        }

        session_cache_limiter('');
        session_save_path(sys_get_temp_dir());
        session_id('bemart-workflow-' . uniqid());
        session_start(['use_cookies' => false, 'use_strict_mode' => false]);
        $this->startedSession = true;
    }

    private function assertRenderedAnchor(string $html, string $href): void
    {
        $xpath = new DOMXPath($this->document($html));
        $nodes = $xpath->query('//a[@href="' . $href . '"]');
        $this->assertNotFalse($nodes);
        $this->assertGreaterThan(0, $nodes->count(), "Missing followable anchor for {$href}");
    }

    private function assertRenderedForm(string $html, string $method, string $actionPrefix): DOMElement
    {
        $xpath = new DOMXPath($this->document($html));
        $forms = $xpath->query('//form');
        $this->assertNotFalse($forms);

        foreach ($forms as $form) {
            if (! $form instanceof DOMElement) {
                continue;
            }

            if (strtolower($form->getAttribute('method')) !== $method) {
                continue;
            }

            if (! str_starts_with($form->getAttribute('action'), $actionPrefix)) {
                continue;
            }

            return $form;
        }

        $this->fail("Missing followable {$method} form for {$actionPrefix}");
    }

    /** @return array<string, string> */
    private function formFields(DOMElement $form): array
    {
        $fields = [];
        foreach ($form->getElementsByTagName('input') as $input) {
            $name = $input->getAttribute('name');
            if ($name === '') {
                continue;
            }

            $fields[$name] = $input->getAttribute('value');
        }

        return $fields;
    }

    /**
     * @param array<string, string> $fields
     *
     * @return array<string, int|string>
     */
    private function canonicalizeFormFields(array $fields): array
    {
        $body = $fields;
        foreach (['_token' => 'csrfToken', 'product_id' => 'productCode'] as $wire => $canonical) {
            if (! array_key_exists($wire, $body) || array_key_exists($canonical, $body)) {
                continue;
            }

            $body[$canonical] = $body[$wire];
            unset($body[$wire]);
        }

        if (array_key_exists('quantity', $body)) {
            $body['quantity'] = (int) $body['quantity'];
        }

        return $body;
    }

    private function firstHrefStartingWith(string $html, string $prefix): string
    {
        $xpath = new DOMXPath($this->document($html));
        $nodes = $xpath->query('//a[starts-with(@href, "' . $prefix . '")]');
        $this->assertNotFalse($nodes);
        $this->assertGreaterThan(0, $nodes->count(), "Missing followable anchor under {$prefix}");

        $node = $nodes->item(0);
        $this->assertInstanceOf(DOMElement::class, $node);

        return $node->getAttribute('href');
    }

    private function firstText(string $html, string $query): string
    {
        $xpath = new DOMXPath($this->document($html));
        $nodes = $xpath->query($query);
        $this->assertNotFalse($nodes);
        $this->assertGreaterThan(0, $nodes->count(), "Missing text node for {$query}");

        $node = $nodes->item(0);
        $this->assertNotNull($node);

        return trim($node->textContent);
    }

    private function assertCartShowsProduct(
        ResourceObject $cart,
        string $productName,
        string $productCode,
    ): void {
        $cartHtml = $cart->toString();
        $this->assertStringContainsString('<div class="ec-cartRole">', $cartHtml);
        $this->assertStringContainsString($productName, $cartHtml);

        if (! is_array($cart->body) || ! array_key_exists('cartCount', $cart->body)) {
            return;
        }

        $this->assertGreaterThan(0, $cart->body['cartCount']);
        foreach ($cart->body['carts'] as $cartRow) {
            if (! is_array($cartRow)) {
                continue;
            }

            foreach ($cartRow['items'] as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (($item['productCode'] ?? null) !== $productCode) {
                    continue;
                }

                $actualQuantity = $item['quantity'] ?? null;
                $this->assertTrue(is_int($actualQuantity));
                $this->assertGreaterThan(0, $actualQuantity);

                return;
            }
        }

        $this->fail("Cart body does not contain product {$productCode}");
    }

    private function document(string $html): DOMDocument
    {
        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }
}
