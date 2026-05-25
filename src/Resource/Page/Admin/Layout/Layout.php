<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Layout;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\LayoutNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\LayoutUpdated;
use MyVendor\BeMart\Be\Input\UpdateLayoutInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doUpdateLayout — single-row endpoint (Wave 9 CMS). Only PUT
 * is exposed; layouts can be neither created nor deleted via the admin
 * UI (system-managed).
 */
class Layout extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $layoutId
     * @psalm-taint-source input $layoutName
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goLayoutList', href: 'page://self/admin/layout/layout-list')]
    public function onPut(
        string $layoutId,
        string|null $layoutName = null,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new UpdateLayoutInput(
                layoutId: $layoutId,
                layoutName: $layoutName,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (LayoutNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定されたレイアウトは見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof LayoutUpdated);

        $this->code = Code::OK;
        $this->body = [
            'layoutId' => $final->layoutId,
            'layoutName' => $final->layoutName,
            'deviceType' => $final->deviceType,
        ];

        return $this;
    }
}
