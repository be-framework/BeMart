<?php

declare(strict_types=1);

namespace MyVendor\BeMart;

use function getenv;
use function putenv;
use function str_starts_with;

/** Resolves the BeMart APP_CONTEXT escape hatch before BEAR.Package composition is restored. */
final class BootstrapContextResolver
{
    /**
     * @param non-empty-string $defaultContext
     *
     * @return non-empty-string
     */
    public function resolve(string $defaultContext): string
    {
        $context = getenv('APP_CONTEXT');
        if ($context === false || $context === '') {
            return $defaultContext;
        }

        /** @var non-empty-string $context */
        return $this->normalize($context, $defaultContext);
    }

    /** @param non-empty-string $context */
    public function publish(string $context, bool $loggable): void
    {
        putenv('APP_CONTEXT=' . $context);
        $_SERVER['APP_CONTEXT'] = $context;
        if ($loggable) {
            $_SERVER['BEMART_BOOTSTRAP_LOGGABLE'] = '1';
        }
    }

    /**
     * @param non-empty-string $context
     * @param non-empty-string $defaultContext
     *
     * @return non-empty-string
     */
    private function normalize(string $context, string $defaultContext): string
    {
        $normalized = match ($context) {
            'app' => 'hal-api-app',
            'fake' => 'fake-hal-api-app',
            'dev' => 'dev-fake-hal-api-app',
            'test' => 'test-hal-api-app',
            'html' => 'html-hal-app',
            'html-test' => 'html-test-hal-api-app',
            'prod' => 'prod-hal-api-app',
            'html-prod' => 'html-prod-hal-api-app',
            default => $context,
        };

        if ($normalized === $context || ! str_starts_with($defaultContext, 'cli-')) {
            return $normalized;
        }

        /** @var non-empty-string */
        return 'cli-' . $normalized;
    }
}
