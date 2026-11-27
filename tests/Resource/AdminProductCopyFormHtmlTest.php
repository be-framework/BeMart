<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use DOMDocument;
use DOMElement;
use DOMXPath;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeAdminSession;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

use function assert;

/** Submits the rendered doCopyProduct form of each admin screen the way a browser would. */
final class AdminProductCopyFormHtmlTest extends TestCase
{
    private const string PRODUCT_CODE = 'admin-active-001';

    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $session = new FakeAdminSession('ad000000000000000000000000000001');
        $injector = HtmlTestInjector::getOverrideInstance(new class ($session) extends AbstractModule {
            public function __construct(private readonly FakeAdminSession $session)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->bind(AdminSession::class)->toInstance($this->session);
            }
        });
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    /** @return array<string, array{string}> */
    public static function screens(): array
    {
        return [
            'product list' => ['page://self/admin/product-list'],
            'product page' => ['page://self/admin/product?productCode=' . self::PRODUCT_CODE],
            'product edit' => ['page://self/admin/product/edit?productCode=' . self::PRODUCT_CODE],
        ];
    }

    #[DataProvider('screens')]
    public function testCopyFormReachesTheCopy(string $uri): void
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($this->resource->get($uri)->toString());
        $xpath = new DOMXPath($dom);
        // DOMDocument keeps a nested form that a browser drops, so it is checked here
        $this->assertSame(0, $xpath->query('//form//form')?->length);

        $form = $xpath->query('//form[@rel="doCopyProduct"][.//input[@name="productCode"][@value="' . self::PRODUCT_CODE . '"]]')?->item(0);
        $this->assertInstanceOf(DOMElement::class, $form);
        $this->assertSame('/admin/product-copy', $form->getAttribute('action'));
        $this->assertSame('post', $form->getAttribute('method'));
        $fields = $xpath->query('.//input', $form) ?: [];
        $id = $form->getAttribute('id');
        $attached = $id === '' ? [] : ($xpath->query('//input[@form="' . $id . '"]') ?: []);
        $params = [];
        foreach ([$fields, $attached] as $inputs) {
            foreach ($inputs as $input) {
                assert($input instanceof DOMElement);
                $params[$input->getAttribute('name')] = $input->getAttribute('value');
            }
        }

        $this->assertSame(self::PRODUCT_CODE . '.copy', $params['newProductCode'] ?? null);
        $this->assertSame(Code::CREATED, $this->resource->post('page://self/admin/product-copy', $params)->code);
    }
}
