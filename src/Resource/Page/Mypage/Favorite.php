<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\ProductNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\FavoriteAdded;
use MyVendor\BeMart\Be\Input\AddFavoriteInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

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
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $productCode
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goProduct', href: 'page://self/product')]
    public function onPost(string $productCode, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new AddFavoriteInput(productCode: $productCode));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthenticatedException) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'この操作を行うにはログインが必要です。'];

            return $this;
        } catch (ProductNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Product not found.', 'productCode' => $productCode];

            return $this;
        }

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
}
