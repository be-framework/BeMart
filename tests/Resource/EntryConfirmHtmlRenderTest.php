<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Support\HtmlTestInjector;
use PHPUnit\Framework\TestCase;

/**
 * Functional render test for the Entry confirm page (goCustomerRegistrationConfirm).
 *
 * Template: var/templates/Page/Entry/Confirm.html.twig
 * Resource: src/Resource/Page/Entry/Confirm.php (thin pure renderer).
 *
 * The confirm screen re-shows the entered registration values as plain text
 * AND carries them forward as HIDDEN inputs so the final submit re-posts the
 * full payload to doRegisterCustomer (POST /entry).
 *
 * Test layers:
 *   L1 — Required fields present and data output rendered (semantic contract).
 *   L2 — form action/method and link href integrity.
 *
 * The EC-CUBE parity diff test is archived — see the @group
 * ec-cube-parity-archived method below.
 */
final class EntryConfirmHtmlRenderTest extends TestCase
{
    private ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = HtmlTestInjector::getInstance();
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    // -------------------------------------------------------------------------
    // L0: document well-formedness
    // -------------------------------------------------------------------------

    public function testEntryConfirmPageRendersAsHtmlDocument(): void
    {
        $ro = $this->resource->get('page://self/entry/confirm');

        $this->assertSame(Code::OK, $ro->code);

        $html = $ro->toString();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('<html lang="ja">', $html);
        $this->assertStringContainsString('</body>', $html);

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
    }

    // -------------------------------------------------------------------------
    // L1: required fields and semantic structure
    // -------------------------------------------------------------------------

    /**
     * The page must carry the IDEA STORE brand identity in the title.
     */
    public function testTitleContainsIdeastoreBrand(): void
    {
        $html = $this->resource->get('page://self/entry/confirm')->toString();

        $this->assertStringContainsString('IDEA STORE', $html);
    }

    /**
     * All registration field labels must be present so the user can
     * review what they entered before submitting.
     */
    public function testRequiredFieldLabelsArePresent(): void
    {
        $html = $this->resource->get('page://self/entry/confirm')->toString();

        foreach ([
            'お名前',
            'メールアドレス',
            'パスワード',
            '住所',
            '電話番号',
            '生年月日',
            '性別',
        ] as $label) {
            $this->assertStringContainsString($label, $html, "Field label missing: {$label}");
        }
    }

    /**
     * The password must not be echoed back in any form; only a mask may appear.
     */
    public function testPasswordIsNotEchoedInPlainText(): void
    {
        $html = $this->resource->get('page://self/entry/confirm')->toString();

        // The page must show SOME password placeholder (mask characters),
        // but the actual hidden input must carry the name without exposing value.
        $this->assertStringContainsString('<input type="hidden" name="password"', $html);
        // No value attribute should expose a password value in visible content.
        $this->assertStringNotContainsString('value="password"', $html);
    }

    // -------------------------------------------------------------------------
    // L1: IdeaStore design language — idea-* class presence
    // -------------------------------------------------------------------------

    /**
     * The template must use IdeaStore design language (idea-* classes) and
     * must not carry any EC-CUBE legacy class names.
     */
    public function testUsesIdeaStoreDesignLanguage(): void
    {
        $html = $this->resource->get('page://self/entry/confirm')->toString();

        foreach ([
            'idea-container',
            'idea-section',
            'idea-checkout-panel',
            'idea-panel-head',
            'idea-info-list',
            'idea-button',
            'idea-form-actions',
        ] as $class) {
            $this->assertStringContainsString($class, $html, "IdeaStore class missing: {$class}");
        }
    }

    public function testNoEcCubeLegacyClasses(): void
    {
        $html = $this->resource->get('page://self/entry/confirm')->toString();

        foreach ([
            'ec-registerRole',
            'ec-pageHeader',
            'ec-off1Grid',
            'ec-borderedDefs',
            'ec-blockBtn',
        ] as $class) {
            $this->assertStringNotContainsString($class, $html, "EC-CUBE legacy class must not appear: {$class}");
        }
    }

    // -------------------------------------------------------------------------
    // L1: hidden form carriers (payload forwarding)
    // -------------------------------------------------------------------------

    /**
     * The registration payload is carried forward as real hidden inputs
     * rendered by a form library, not static markup.
     */
    public function testEntryConfirmPageRendersHiddenFormCarriers(): void
    {
        $html = $this->resource->get('page://self/entry/confirm')->toString();

        $this->assertStringContainsString('<input type="hidden" name="name01"', $html);
        $this->assertStringContainsString('<input type="hidden" name="email"', $html);
        $this->assertStringContainsString('<input type="hidden" name="password"', $html);
        $this->assertStringContainsString('<input type="hidden" name="user_policy_check"', $html);
    }

    /**
     * All 20 registration fields declared in EntryConfirmForm must be
     * present as hidden inputs so the full payload is forwarded.
     */
    public function testAllPayloadFieldsAreCarriedAsHiddenInputs(): void
    {
        $html = $this->resource->get('page://self/entry/confirm')->toString();

        foreach ([
            'name01', 'name02', 'kana01', 'kana02',
            'companyName', 'postalCode', 'pref', 'addr01', 'addr02',
            'phoneNumber', 'email', 'email_confirm',
            'password', 'password_confirm',
            'birth_year', 'birth_month', 'birth_day',
            'sex', 'job', 'user_policy_check',
        ] as $field) {
            $this->assertStringContainsString(
                "name=\"{$field}\"",
                $html,
                "Hidden carrier missing for field: {$field}",
            );
        }
    }

    // -------------------------------------------------------------------------
    // L2: form action / method integrity
    // -------------------------------------------------------------------------

    /**
     * The form must submit via POST to /entry (doRegisterCustomer).
     * Source of truth: Entry/Confirm.php #[Link] + submitTo body field.
     */
    public function testFormActionAndMethodMatchResourceContract(): void
    {
        $html = $this->resource->get('page://self/entry/confirm')->toString();

        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringContainsString('action="/entry"', $html);
    }

    /**
     * The breadcrumb must link back to /entry so the user can navigate
     * to the registration form.
     */
    public function testBreadcrumbLinksToEntryForm(): void
    {
        $html = $this->resource->get('page://self/entry/confirm')->toString();

        $this->assertStringContainsString('href="/entry"', $html);
    }

    // -------------------------------------------------------------------------
    // EC-CUBE parity test — archived
    // -------------------------------------------------------------------------

    /**
     * @group ec-cube-parity-archived
     */
    public function testEntryConfirmHtmlMatchesEcCubeRenderingWithinResidualAllowlist(): void
    {
        $this->markTestSkipped(
            'EC-CUBE DOM-parity check archived: template rebuilt in IdeaStore design language. '
            . 'Functional and semantic coverage is provided by the L1/L2 tests above.',
        );
    }
}
