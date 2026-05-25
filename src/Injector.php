<?php

declare(strict_types=1);

namespace MyVendor\BeMart;

use BEAR\AppMeta\Meta;
use MyVendor\BeMart\Module\AppModule;
use MyVendor\BeMart\Module\HtmlModule;
use MyVendor\BeMart\Module\ProdModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector as RayInjector;
use Ray\Di\InjectorInterface;

use function dirname;
use function sprintf;

final class Injector
{
    private function __construct()
    {
    }

    /** @param non-empty-string $context */
    public static function getInstance(string $context): InjectorInterface
    {
        $appDir = dirname(__DIR__);
        $meta = new Meta(__NAMESPACE__, $context, $appDir);

        return new RayInjector(self::module($context, $meta), $meta->tmpDir);
    }

    /** @param non-empty-string $context */
    private static function module(string $context, Meta $meta): AbstractModule
    {
        if ($context === 'app' || $context === 'test') {
            return new AppModule($meta);
        }

        if ($context === 'html') {
            return new HtmlModule($meta);
        }

        if ($context === 'prod') {
            return new ProdModule($meta);
        }

        throw new AppContextModuleNotFoundException(sprintf('Unknown app context: %s', $context));
    }
}
