<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\MypageFetched;
use MyVendor\BeMart\Be\Input\GetMypageInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE goMypage — 会員マイページのダッシュボード.
 *
 * Safe read. No CSRF (read-only). AUTHN required — Be Final raises
 * UnauthenticatedException when the session has no customerId, which
 * we map to 401. Aggregates basic profile + recent orders +
 * favorite count into a flat dashboard projection.
 *
 * Failure mapping:
 *   - SemanticVariableException → 400 (parameter format invalid)
 *   - UnauthenticatedException  → 401 (no / stale session)
 *
 * Coexists with `Resource\Page\Mypage\` namespace (Change, Favorite,
 * …) — PHP allows a file and a sibling directory of the same name to
 * share a namespace prefix.
 */
class Mypage extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /** ALPS `goMypage` に対応する GET 操作。 */
    #[Alps('goMypage')]
    #[JsonSchema(schema: 'get-mypage.json', params: 'get-mypage.param.json')]
    #[Link(rel: 'goMypageHistory', href: 'page://self/mypage/history')]
    #[Link(rel: 'goMypageChange', href: 'page://self/mypage/change')]
    #[Link(rel: 'goCustomerAddressList', href: 'page://self/mypage/address-list')]
    #[Link(rel: 'goFavoriteList', href: 'page://self/mypage/favorite-list')]
    #[Link(rel: 'goMypageWithdraw', href: 'page://self/mypage/withdraw')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    #[Link(rel: 'doAddFavorite', href: 'page://self/mypage/favorite', method: 'post')]
    #[Link(rel: 'doRemoveFavorite', href: 'page://self/mypage/favorite', method: 'delete')]
    public function onGet(int $orderLimit = 5): static
    {
        $final = ($this->becoming)(new GetMypageInput(orderLimit: $orderLimit));

        assert($final instanceof MypageFetched);

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
            'name01' => $final->name01,
            'name02' => $final->name02,
            'recentOrders' => $final->recentOrders,
            'recentOrderCount' => $final->recentOrderCount,
            'favoriteCount' => $final->favoriteCount,
        ];

        return $this;
    }
}
