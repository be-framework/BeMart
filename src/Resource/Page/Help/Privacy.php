<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Help;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

/**
 * EC-CUBE goHelpPrivacy — プライバシーポリシー (Wave 3H pure renderer).
 *
 * Pure static page: no Be Framework, no domain logic, no Reasons.
 * Anonymous-accessible. Maps to `page://self/help/privacy`.
 *
 * Admin-editable. Wave 3H exposes the shape only.
 */
class Privacy extends ResourceObject
{
    /**
     * @todo Wave-future: surface privacy policy content from admin store.
     */
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goHelpPrivacy',
            'fields' => [],
            'submitTo' => null,
            'staticContent' => [
                'page' => 'privacy',
                'title' => 'プライバシーポリシー',
                'sections' => [],
            ],
            'links' => [
                'goTop' => 'page://self/',
            ],
        ];

        return $this;
    }
}
