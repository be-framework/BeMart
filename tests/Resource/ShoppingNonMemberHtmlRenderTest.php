<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;

use function str_contains;

/**
 * Phase 3 — HTML render test for the Shopping non-member page
 * (goShoppingNonMember).
 *
 * Validates two semantic levels:
 *   L1 — required fields and data output present in the rendered document
 *   L2 — form action / method, link href / rel semantics
 *
 * EC-CUBE parity diffing has been retired — see
 * {@see testShoppingNonMemberHtmlMatchesEcCubeRenderingWithinResidualAllowlist}
 * below.
 */
final class ShoppingNonMemberHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // -------------------------------------------------------------------------
    // L1 — Required fields and data output
    // -------------------------------------------------------------------------

    /** The page renders as a complete HTML document with the correct title. */
    public function testRendersCompleteHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/shopping/non-member');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('お客様情報の入力', $html);
        $this->assertStringContainsString('</body>', $html);
    }

    /** All required guest-info fields are present in the rendered form. */
    public function testAllRequiredFieldsAreRendered(): void
    {
        $html = $this->resource->get('page://self/shopping/non-member')->toString();

        foreach ([
            'name="name01"',
            'name="name02"',
            'name="kana01"',
            'name="kana02"',
            'name="email"',
            'name="email_confirm"',
            'name="phoneNumber"',
            'name="postalCode"',
            'name="pref"',
            'name="addr01"',
            'name="addr02"',
            'name="csrfToken"',
        ] as $field) {
            $this->assertStringContainsString($field, $html, "missing field: {$field}");
        }
    }

    /** Field labels are present for all required inputs. */
    public function testFieldLabelsArePresent(): void
    {
        $html = $this->resource->get('page://self/shopping/non-member')->toString();

        foreach ([
            '姓', '名',
            'セイ', 'メイ',
            'メールアドレス',
            '電話番号',
            '郵便番号',
            '都道府県',
            '市区町村',
            '番地・建物名',
        ] as $label) {
            $this->assertStringContainsString($label, $html, "missing label: {$label}");
        }
    }

    /** A rejected POST re-renders the form with inline field errors. */
    public function testRejectedPostShowsInlineErrors(): void
    {
        $ro = $this->resource->post('page://self/shopping/non-member', [
            'name01' => '',
            'name02' => '',
            'kana01' => '',
            'kana02' => '',
            'companyName' => '',
            'email' => '',
            'email_confirm' => '',
            'phoneNumber' => '',
            'postalCode' => '',
            'pref' => '',
            'addr01' => '',
            'addr02' => '',
            'csrfToken' => FakeCsrfToken::TOKEN,
        ]);

        $this->assertSame(Code::BAD_REQUEST, $ro->code);

        $html = $ro->toString();
        $this->assertStringContainsString('お客様情報の入力', $html);
        $this->assertStringContainsString('入力してください。', $html);
        $this->assertStringContainsString('name="email_confirm"', $html);
        $this->assertStringNotContainsString('Invalid parameter type', $html);
    }

    // -------------------------------------------------------------------------
    // L2 — Form action / method and navigation link semantics
    // -------------------------------------------------------------------------

    /** The guest-info form posts to the correct resource endpoint. */
    public function testFormActionAndMethodAreCorrect(): void
    {
        $html = $this->resource->get('page://self/shopping/non-member')->toString();

        $this->assertStringContainsString('action="/shopping/non-member"', $html);
        $this->assertStringContainsString('method="post"', $html);
    }

    /** A submit button is present to advance the checkout flow. */
    public function testSubmitButtonIsPresent(): void
    {
        $html = $this->resource->get('page://self/shopping/non-member')->toString();

        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('次へ', $html);
    }

    /** Navigation back to the cart is available. */
    public function testCartReturnLinkIsPresent(): void
    {
        $html = $this->resource->get('page://self/shopping/non-member')->toString();

        $this->assertTrue(
            str_contains($html, 'href="/cart"'),
            'cart return link missing',
        );
    }

    /** Navigation back to the purchase entry screen is available. */
    public function testLoginReturnLinkIsPresent(): void
    {
        $html = $this->resource->get('page://self/shopping/non-member')->toString();

        $this->assertTrue(
            str_contains($html, 'href="/shopping/login"'),
            'shopping/login return link missing',
        );
    }

    // -------------------------------------------------------------------------
    // EC-CUBE parity diff — retired
    // -------------------------------------------------------------------------

    /**
     * EC-CUBE line-by-line diff test — archived.
     *
     * This test compared BeMart's rendered HTML against an EC-CUBE 4.3
     * reference render. The NonMember page has been rebuilt in the IdeaStore
     * design language, so the DOM structure intentionally differs from
     * EC-CUBE's default theme. The diff test is no longer meaningful.
     *
     * @group ec-cube-parity-archived
     */
    public function testShoppingNonMemberHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE parity diff retired: NonMember page rebuilt in IdeaStore '
            . 'design language. Functional coverage is provided by L1/L2 tests above.',
        );
    }
}
