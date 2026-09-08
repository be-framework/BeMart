<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Fake\Html;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

final class LinkedResourceObject extends ResourceObject
{
    #[Link(rel: 'goNext', href: 'page://self/next')]
    public function onGet(): self
    {
        return $this;
    }
}
