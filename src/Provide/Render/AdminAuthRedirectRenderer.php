<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Provide\Render;

use BEAR\Resource\Code;
use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceObject;
use Override;
use Ray\Aop\WeavedInterface;
use Ray\Di\Di\Named;
use ReflectionClass;

use function str_starts_with;

/**
 * HTML-context renderer decorator for the admin firewall.
 *
 * Page\Admin resources answer Code::FORBIDDEN for an anonymous or expired
 * admin session. Rendering that body through the resource template painted
 * the full page skeleton with empty data (a dashboard of zeroed KPIs) and
 * hid the "login required" message. Browser users now get a 303 redirect
 * to the admin login page instead, matching EC-CUBE behaviour.
 *
 * The resource-level 403 contract is untouched — the mutation happens at
 * render (transfer) time, and API/HAL contexts bind their own JSON
 * renderer, so they keep receiving 403 + message.
 */
final class AdminAuthRedirectRenderer implements RenderInterface
{
    private const ADMIN_PAGE_NS = 'MyVendor\\BeMart\\Resource\\Page\\Admin\\';
    private const LOGIN_PATH = '/admin/login';

    public function __construct(
        #[Named('twig')]
        private readonly RenderInterface $inner,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-taint-escape html
     */
    #[Override]
    public function render(ResourceObject $ro)
    {
        if ($ro->code === Code::FORBIDDEN && str_starts_with($this->resourceClass($ro), self::ADMIN_PAGE_NS)) {
            $ro->code = Code::SEE_OTHER;
            $ro->headers['Location'] = self::LOGIN_PATH;
        }

        return $this->inner->render($ro);
    }

    /** @return class-string */
    private function resourceClass(ResourceObject $ro): string
    {
        $class = new ReflectionClass($ro);
        if ($ro instanceof WeavedInterface) {
            $parent = $class->getParentClass();
            if ($parent !== false) {
                return $parent->name;
            }
        }

        return $class->name;
    }
}
