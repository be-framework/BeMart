<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Help;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Resource\Annotation\JsonSchema;

/**
 * EC-CUBE goHelpAbout — 当サイトについて (Wave 3H pure renderer).
 *
 * Pure static page: no Be Framework, no domain logic, no Reasons.
 * Anonymous-accessible. Maps to `page://self/help/about`.
 *
 * The ALPS `#HelpAbout` resource carries shopMessage + goodTraded
 * content edited from the admin BaseInfo screen. Wave 3H exposes the
 * shape only; the actual content lookup is left as TODO until a
 * BaseInfo aggregation lands.
 */
class About extends ResourceObject
{
    /**
     * ALPS `goodTraded` に対応する GET 操作。
     * @todo Wave-future: surface shopMessage + goodTraded from BaseInfo.
     */
    #[Alps('goodTraded')]
    #[JsonSchema(schema: 'get-help-about.json')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goHelpAbout',
            'fields' => [],
            'submitTo' => null,
            'staticContent' => [
                'page' => 'about',
                'title' => '当サイトについて',
                'sections' => [],
            ],
            'links' => [
                'goTop' => 'page://self/',
            ],
        ];

        return $this;
    }
}
