<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Help;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Resource\Annotation\JsonSchema;

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
     * ALPS `goHelpPrivacy` に対応する GET 操作。
     * @todo Wave-future: surface privacy policy content from admin store.
     */
    #[Alps('goHelpPrivacy')]
    #[JsonSchema(schema: 'get-help-privacy.json')]
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
