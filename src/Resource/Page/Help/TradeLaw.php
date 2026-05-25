<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Help;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

/**
 * EC-CUBE goHelpTradeLaw — 特定商取引法に基づく表記 (Wave 3H pure renderer).
 *
 * Pure static page: no Be Framework, no domain logic, no Reasons.
 * Anonymous-accessible. Maps to `page://self/help/trade-law`.
 *
 * The ALPS `#HelpTradeLaw` resource carries tradeLawName / tradeLawDescription
 * pairs (事業者情報、返品ポリシー、送料 etc.). Wave 3H exposes the shape only;
 * the content lookup against the admin-editable TradeLaw store is left as
 * TODO until a dedicated aggregation lands.
 */
class TradeLaw extends ResourceObject
{
    /**
     * @todo Wave-future: surface tradeLawName / tradeLawDescription
     *     entries from the admin-editable TradeLaw store.
     */
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goHelpTradeLaw',
            'fields' => [],
            'submitTo' => null,
            'staticContent' => [
                'page' => 'trade-law',
                'title' => '特定商取引法に基づく表記',
                'sections' => [],
            ],
            'links' => [
                'goTop' => 'page://self/',
            ],
        ];

        return $this;
    }
}
