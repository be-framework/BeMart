<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Help;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

/**
 * EC-CUBE goHelpGuide — ご利用ガイド (Wave 3H pure renderer).
 *
 * Pure static page: no Be Framework, no domain logic, no Reasons.
 * Anonymous-accessible. Maps to `page://self/help/guide`.
 *
 * EC-CUBE default content is editable from the admin Help screen.
 * Wave 3H exposes the shape only.
 */
class Guide extends ResourceObject
{
    /**
     * @todo Wave-future: surface guide content from admin-editable store.
     */
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goHelpGuide',
            'fields' => [],
            'submitTo' => null,
            'staticContent' => [
                'page' => 'guide',
                'title' => 'ご利用ガイド',
                'sections' => [],
            ],
            'links' => [
                'goTop' => 'page://self/',
            ],
        ];

        return $this;
    }
}
