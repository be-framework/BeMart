<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use MyVendor\BeMart\Annotation\CsrfProtected;
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
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminTradeLawForm;
use Ray\WebFormModule\FormFactory;

use function assert;
use function count;
use function explode;
use function trim;

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
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * Wave 9ι: goTradeLawList — admin views the current TradeLaw body.
     */
    #[Link(rel: 'doUpdateTradeLaw', href: 'page://self/admin/trade-law', method: 'post')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $final = ($this->becoming)(new GetTradeLawInput());

        assert($final instanceof TradeLawFetched);

        $rows = $this->tradeLawRows($final->tradeLawBody);
        $form = $this->formFactory->newInstance(AdminTradeLawForm::class);
        assert($form instanceof AdminTradeLawForm);
        $form->fillRows($rows);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'tradeLawRows' => $rows,
            'tradeLawBody' => $final->tradeLawBody,
        ];

        return $this;
    }

    /**
     * @return list<array{id: int, name: string, description: string, displayOrderScreen: bool, nameKey: string, descriptionKey: string, displayOrderScreenKey: string}>
     */
    private function tradeLawRows(string $body): array
    {
        $rows = [];
        $index = 1;
        foreach (explode("\n", $body) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $name = '項目' . $index;
            $description = $line;
            $parts = explode(': ', $line, 2);
            if (count($parts) === 2) {
                [$name, $description] = $parts;
            }

            $keys = AdminTradeLawForm::fieldKeys($index);
            $rows[] = [
                'id' => $index,
                'name' => $name,
                'description' => $description,
                'displayOrderScreen' => true,
                'nameKey' => $keys['nameKey'],
                'descriptionKey' => $keys['descriptionKey'],
                'displayOrderScreenKey' => $keys['displayOrderScreenKey'],
            ];
            $index++;
        }

        return $rows;
    }

    /**
     * @psalm-taint-source input $tradeLawBody
     */
    #[Link(rel: 'goTop', href: 'page://self/admin')]
    #[Link(rel: 'goContentCss', href: 'page://self/admin/content/css')]
    #[CsrfProtected]
    public function onPost(string $tradeLawBody): static
    {
        $final = ($this->becoming)(new UpdateTradeLawInput(tradeLawBody: $tradeLawBody));

        assert($final instanceof TradeLawUpdated);

        $this->code = Code::OK;
        $this->body = [
            'tradeLawBody' => $final->tradeLawBody,
            'changed' => $final->changed,
        ];

        return $this;
    }
}
