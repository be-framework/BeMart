<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Layout;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminLayoutListFetched;
use MyVendor\BeMart\Be\Input\GetAdminLayoutListInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE goLayoutList — list endpoint (Wave 9 CMS).
 *
 * Layouts have no create / delete affordances per ALPS — only list and
 * update.
 */
class LayoutList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /** ALPS `goLayoutList` に対応する GET 操作。 */
    #[Alps('goLayoutList')]
    #[JsonSchema(schema: 'get-admin-layout-layout-list.json')]
    #[Link(rel: 'doUpdateLayout', href: 'page://self/admin/layout/layout', method: 'put')]
    public function onGet(): static
    {
        $final = ($this->becoming)(new GetAdminLayoutListInput());

        assert($final instanceof AdminLayoutListFetched);

        $this->code = Code::OK;
        $this->body = [
            'count' => $final->count,
            'layouts' => $final->layouts,
        ];

        return $this;
    }
}
