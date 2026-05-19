<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\TradeLawFetched;
use MyVendor\BeMart\Be\Final\TradeLawUpdated;
use MyVendor\BeMart\Be\Input\GetTradeLawInput;
use MyVendor\BeMart\Be\Input\UpdateTradeLawInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doUpdateTradeLaw + goTradeLawList — 特定商取引法 (Wave 8 + Wave 9).
 *
 *   - GET  → goTradeLawList (safe read, admin AUTHZ, Wave 9ι)
 *   - POST → doUpdateTradeLaw (idempotent, admin AUTHZ + CSRF, Wave 8ε)
 *
 * Wave 8 first iteration treats the page as a single body blob; Phase 2
 * will split into per-item rows.
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403 (POST only)
 *   - SemanticVariableException             → 400 (body length)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 */
class TradeLaw extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * Wave 9ι: goTradeLawList — admin views the current TradeLaw body.
     */
    #[Link(rel: 'doUpdateTradeLaw', href: 'page://self/admin/trade-law', method: 'post')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetTradeLawInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof TradeLawFetched);

        $this->code = Code::OK;
        $this->body = [
            'tradeLawBody' => $final->tradeLawBody,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $tradeLawBody
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goTop', href: 'page://self/admin')]
    public function onPost(string $tradeLawBody, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new UpdateTradeLawInput(tradeLawBody: $tradeLawBody));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof TradeLawUpdated);

        $this->code = Code::OK;
        $this->body = [
            'tradeLawBody' => $final->tradeLawBody,
            'changed' => $final->changed,
        ];

        return $this;
    }
}
