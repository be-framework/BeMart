<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Help;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

/**
 * EC-CUBE goHelpAgreement — ご利用規約 (Wave 3H pure renderer).
 *
 * Pure static page: no Be Framework, no domain logic, no Reasons.
 * Anonymous-accessible. Maps to `page://self/help/agreement`.
 *
 * Admin-editable. Wave 3H exposes the shape only.
 */
class Agreement extends ResourceObject
{
    /**
     * @todo Wave-future: surface agreement content from admin-editable store.
     */
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goHelpAgreement',
            'fields' => [],
            'submitTo' => null,
            'staticContent' => [
                'page' => 'agreement',
                'title' => 'ご利用規約',
                'sections' => [],
            ],
            'links' => [
                'goTop' => 'page://self/',
            ],
        ];

        return $this;
    }
}
