<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\FavoriteAdded;
use MyVendor\BeMart\Be\Final\FavoriteRemoved;
use MyVendor\BeMart\Be\Input\AddFavoriteInput;
use MyVendor\BeMart\Be\Input\RemoveFavoriteInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doAddFavorite — お気に入りに追加 (Pilot 13).
 *
 * AUTHZ via Session (customerId never in body). Idempotent re-add
 * returns 200 (alreadyExisted=true) rather than 201, so the UI can
 * distinguish first-add from re-add.
 */
class Favorite extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * ALPS `doAddFavorite` に対応する POST 操作。
     * @psalm-taint-source input $productCode
     */
    #[Alps('doAddFavorite')]
    #[JsonSchema(schema: 'post-mypage-favorite.json', params: 'post-mypage-favorite.param.json')]
    #[Link(rel: 'doRemoveFavorite', href: 'page://self/mypage/favorite', method: 'delete')]
    #[Link(rel: 'goProduct', href: 'page://self/product')]
    #[CsrfProtected]
    public function onPost(string $productCode): static
    {
        $final = ($this->becoming)(new AddFavoriteInput(productCode: $productCode));

        assert($final instanceof FavoriteAdded);

        $this->code = $final->alreadyExisted ? Code::OK : Code::CREATED;
        $this->body = [
            'customerId' => $final->customerId,
            'productCode' => $final->productCode,
            'productName' => $final->productName,
            'unitPrice' => $final->unitPrice,
            'alreadyExisted' => $final->alreadyExisted,
        ];

        return $this;
    }

    /**
     * EC-CUBE doRemoveFavorite — お気に入りから削除 (idempotent inverse
     * of Pilot 13). DELETE is idempotent (ALPS type=idempotent):
     * re-removing an already-absent item returns 200 with
     * alreadyAbsent=true rather than 404. The flag lets the UI
     * distinguish first-remove from re-remove without leaking the
     * underlying state.
     *
     * Unlike onPost, we do NOT validate that productCode resolves to
     * a real product — DELETE removes a stored row, not a product.
     *
     * @psalm-taint-source input $productCode
     */
    #[Alps('doRemoveFavorite')]
    #[JsonSchema(schema: 'delete-mypage-favorite.json', params: 'delete-mypage-favorite.param.json')]
    #[Link(rel: 'goMypageWithdraw', href: 'page://self/mypage/withdraw')]
    #[Link(rel: 'goMypage', href: 'page://self/mypage')]
    #[CsrfProtected]
    public function onDelete(string $productCode): static
    {
        $final = ($this->becoming)(new RemoveFavoriteInput(productCode: $productCode));

        assert($final instanceof FavoriteRemoved);

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $final->customerId,
            'productCode' => $final->productCode,
            'alreadyAbsent' => $final->alreadyAbsent,
        ];

        return $this;
    }
}
