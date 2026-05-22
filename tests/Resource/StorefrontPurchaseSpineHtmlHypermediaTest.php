<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use DOMDocument;
use DOMElement;
use DOMXPath;
use MyVendor\BeMart\Module\HtmlModule;
use MyVendor\BeMart\Router\RouteTable;
use MyVendor\BeMart\Router\Router;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function array_key_exists;
use function dirname;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function sprintf;
use function strtolower;

/**
 * Rendered HTML hypermedia contract for the critical purchase spine.
 *
 * This is intentionally browser-free: it renders the html context, parses
 * the resulting HTML, and follows the same route table the front
 * controller uses. Removing any critical `<a href>` or add-cart `<form>`
 * turns this story red.
 */
final class StorefrontPurchaseSpineHtmlHypermediaTest extends TestCase
{
    private ResourceInterface $resource;
    private Router $router;

    protected function setUp(): void
    {
        $meta = new Meta('MyVendor\\BeMart', 'html');
        $injector = new Injector(
            new HtmlModule($meta),
            dirname(__DIR__, 2) . '/var/tmp/html',
        );

        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->router = new Router(RouteTable::default());
    }

    public function testCustomerCanFollowRenderedHomeToCartPurchaseSpine(): void
    {
        $home = $this->resource->get('page://self/');
        $this->assertSame(Code::OK, $home->code);
        $homeHtml = $home->toString();

        $this->assertRenderedAnchor($homeHtml, '/products/list');
        $this->assertRenderedAnchor($homeHtml, '/cart');

        $products = $this->getPath('/products/list');
        $this->assertSame(Code::OK, $products->code);
        $this->assertNotEmpty($products->body['products']);

        /** @var array{id: string, name: string, price02: int} $product */
        $product = $products->body['products'][0];
        $detailPath = sprintf('/products/detail/%s', $product['id']);
        $productsHtml = $products->toString();
        $this->assertRenderedAnchor($productsHtml, $detailPath);

        $detail = $this->getPath($detailPath);
        $this->assertSame(Code::OK, $detail->code);
        $detailHtml = $detail->toString();

        $addCartPath = sprintf('/products/add_cart/%s', $product['id']);
        $form = $this->assertRenderedForm($detailHtml, 'post', $addCartPath);
        $fields = $this->formFields($form);

        $this->assertArrayHasKey('_token', $fields);
        $this->assertArrayHasKey('product_id', $fields);
        $this->assertArrayHasKey('quantity', $fields);
        $this->assertSame($product['id'], $fields['product_id']);

        $added = $this->postPath($addCartPath, $this->canonicalizeFormFields($fields));
        $this->assertSame(Code::CREATED, $added->code);
        $this->assertSame('/cart', $added->headers['Location']);

        $cart = $this->getPath($added->headers['Location']);
        $this->assertSame(Code::OK, $cart->code);
        $this->assertGreaterThan(0, $cart->body['cartCount']);

        $cartHtml = $cart->toString();
        $this->assertStringContainsString('<div class="ec-cartRole">', $cartHtml);
        $this->assertStringContainsString($product['name'], $cartHtml);
    }

    private function getPath(string $path): \BEAR\Resource\ResourceObject
    {
        $matched = $this->router->match('GET', $path);

        return $this->resource->get($matched->resource, $matched->params);
    }

    /** @param array<string, string> $body */
    private function postPath(string $path, array $body): \BEAR\Resource\ResourceObject
    {
        $matched = $this->router->match('POST', $path);

        return $this->resource->post($matched->resource, $matched->params + $body);
    }

    private function assertRenderedAnchor(string $html, string $href): void
    {
        $xpath = new DOMXPath($this->document($html));
        $nodes = $xpath->query('//a[@href="' . $href . '"]');
        $this->assertNotFalse($nodes);

        $this->assertGreaterThan(
            0,
            $nodes->count(),
            "Missing followable anchor for {$href}",
        );
    }

    private function assertRenderedForm(string $html, string $method, string $action): DOMElement
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

            if ($form->getAttribute('action') !== $action) {
                continue;
            }

            return $form;
        }

        $this->fail("Missing followable {$method} form for {$action}");
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
     * Mirrors public/index.php's EC-CUBE wire-field aliases for this form.
     *
     * @param array<string, string> $fields
     * @return array<string, string>
     */
    private function canonicalizeFormFields(array $fields): array
    {
        $body = $fields;
        foreach (['_token' => 'csrfToken', 'product_id' => 'productCode'] as $wire => $canonical) {
            if (array_key_exists($wire, $body) && ! array_key_exists($canonical, $body)) {
                $body[$canonical] = $body[$wire];
                unset($body[$wire]);
            }
        }

        return $body;
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
