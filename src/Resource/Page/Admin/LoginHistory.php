<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\LoginHistoryListFetched;
use MyVendor\BeMart\Be\Input\GetLoginHistoryListInput;

use function assert;

/**
 * EC-CUBE goLoginHistoryList — 管理画面ログイン履歴 (Wave 8).
 *
 * Safe read. No CSRF (read-only). Admin-only — the Be Final raises
 * {@see UnauthorizedAdminAccessException} when the admin session is
 * empty (mapped to 403).
 *
 * ALPS doc: 成功/失敗・IP アドレス・User-Agent を記録. Wave 8
 * surfaces timestamp / loginId / success / clientIp; the User-Agent
 * field is Phase 2 (the fake storage seeds a sample of the four
 * surfaced fields only).
 *
 * Failure mapping:
 *   - SemanticVariableException             → 400 (limit format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 */
class LoginHistory extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * @psalm-taint-source input $limit
     */
    #[Link(rel: 'goMemberList', href: 'page://self/admin/member-list')]
    public function onGet(int $limit = 50): static
    {
        try {
            $final = ($this->becoming)(new GetLoginHistoryListInput(limit: $limit));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof LoginHistoryListFetched);

        $this->code = Code::OK;
        $this->body = [
            'entries' => $final->entries,
            'count' => $final->count,
        ];

        return $this;
    }
}
