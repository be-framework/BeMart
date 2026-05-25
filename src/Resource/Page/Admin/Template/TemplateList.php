<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Template;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminTemplateListFetched;
use MyVendor\BeMart\Be\Input\GetAdminTemplateListInput;

use function assert;

/**
 * EC-CUBE goTemplateList — list-only endpoint (Wave 9). ALPS exposes
 * no other affordances; template upload / activation is Phase 2.
 */
class TemplateList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    #[Link(rel: 'goTemplateAdd', href: 'page://self/admin/template/template-add')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetAdminTemplateListInput());
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof AdminTemplateListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'templates' => $final->templates,
            'links' => [
                'goTemplateAdd' => 'page://self/admin/template/template-add',
            ],
        ];

        return $this;
    }
}
