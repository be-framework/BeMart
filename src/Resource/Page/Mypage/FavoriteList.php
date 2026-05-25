<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\FavoriteListFetched;
use MyVendor\BeMart\Be\Input\GetFavoriteListInput;

use function assert;

/**
 * EC-CUBE goFavoriteList — お気に入り一覧 (read pair for Pilot 13's
 * doAddFavorite + doRemoveFavorite).
 *
 * Safe read. No CSRF (read-only). AUTHN is enforced in the Be layer:
 * the customer can only see their own favorites — the customerId
 * comes from SessionInterface, never the request body (Pilot 5 F-2
 * lesson).
 *
 * Failure mapping:
 *   - SemanticVariableException  → 400 (defensive — the Input is 0-arg)
 *   - UnauthenticatedException   → 401 (no session)
 */
class FavoriteList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    #[Link(rel: 'doAddFavorite', href: 'page://self/mypage/favorite', method: 'post')]
    #[Link(rel: 'doRemoveFavorite', href: 'page://self/mypage/favorite', method: 'delete')]
    #[Link(rel: 'goMypage', href: 'page://self/mypage')]
    public function onGet(): static
    {
        try {
            $final = ($this->becoming)(new GetFavoriteListInput());
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthenticatedException) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'この操作を行うにはログインが必要です。'];

            return $this;
        }

        assert($final instanceof FavoriteListFetched);

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $final->customerId,
            'favorites' => $final->favorites,
            'favoriteCount' => $final->favoriteCount,
        ];

        return $this;
    }
}
