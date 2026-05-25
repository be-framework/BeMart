<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Category;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\CategoryCsvExported;
use MyVendor\BeMart\Be\Final\CategoryCsvImported;
use MyVendor\BeMart\Be\Input\ExportCategoryInput;
use MyVendor\BeMart\Be\Input\ImportCategoryCsvInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE goExportCategory + doImportCategoryCsv — CSV endpoint
 * (Wave 7).
 *
 *   - GET  → goExportCategory   (RFC 4180 dump — admin AUTHZ)
 *   - POST → doImportCategoryCsv (**Phase 2 stub** — accepts the body
 *                                 but does not persist; ALPS/AUTHZ
 *                                 contract is exercised, full parser
 *                                 deferred)
 *
 * Both methods enforce the admin firewall. The stubbed import path
 * returns `accepted=false` with an explanatory notice so callers
 * cannot mistake the stub for a real import.
 */
class Csv extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    #[Link(rel: 'goCategoryList', href: 'page://self/admin/category/category-list')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new ExportCategoryInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof CategoryCsvExported);

        $this->code = Code::OK;
        $this->headers['Content-Type'] = 'text/csv; charset=UTF-8';
        $this->body = [
            'csv' => $final->csv,
            'rowCount' => $final->rowCount,
        ];

        return $this;
    }

    /**
     * @psalm-taint-source input $csv
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goCategoryList', href: 'page://self/admin/category/category-list')]
    public function onPost(string $csv, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new ImportCategoryCsvInput(csv: $csv));
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof CategoryCsvImported);

        $this->code = Code::ACCEPTED;
        $this->body = [
            'accepted' => $final->accepted,
            'lineCount' => $final->lineCount,
            'message' => $final->message,
        ];

        return $this;
    }
}
