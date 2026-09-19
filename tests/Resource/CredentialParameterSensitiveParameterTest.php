<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Resource;

use BEAR\AppMeta\Meta as AppMeta;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use SensitiveParameter;

use function implode;
use function preg_match;
use function sprintf;
use function str_starts_with;

/**
 * Boundary contract: every Resource-layer on* parameter whose name looks like a
 * credential must be marked #[SensitiveParameter], so the resource method's own
 * stack frame redacts it when an exception propagates through that frame (e.g. a
 * re-thrown SemanticVariableException, or a throw during $this->becoming(...)).
 * The attribute protects only that one frame, not the whole trace or any copy of
 * the value held elsewhere; CsrfForbiddenInterceptor never throws (403 body +
 * return), so it is not part of this rationale.
 *
 * Discovery-based, not an allowlist: a manual audit previously missed
 * Entry::onPost's $password/$password_confirm, and a hardcoded map would miss the
 * next one the same way. Every resource class BEAR\AppMeta can see is reflected,
 * so a new credential-bearing method fails here until it is annotated.
 */
final class CredentialParameterSensitiveParameterTest extends TestCase
{
    private const CREDENTIAL_NAME = '/password|secret|token|resetKey|authKey/i';

    public function testEveryCredentialShapedResourceParameterIsMarkedSensitive(): void
    {
        $checked = 0;
        $violations = [];
        foreach ((new AppMeta('MyVendor\\BeMart', 'test'))->getGenerator('*') as $resource) {
            $class = new ReflectionClass($resource->class);
            foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (! str_starts_with($method->getName(), 'on')) {
                    continue;
                }

                foreach ($method->getParameters() as $parameter) {
                    if (preg_match(self::CREDENTIAL_NAME, $parameter->getName()) !== 1) {
                        continue;
                    }

                    $checked++;
                    if ($parameter->getAttributes(SensitiveParameter::class) !== []) {
                        continue;
                    }

                    $violations[] = sprintf('%s::%s($%s)', $resource->class, $method->getName(), $parameter->getName());
                }
            }
        }

        $this->assertGreaterThan(0, $checked, 'discovery found no credential-shaped resource parameters; the generator or pattern is broken');
        $this->assertSame(
            [],
            $violations,
            "credential-shaped resource parameters without #[SensitiveParameter]:\n  " . implode("\n  ", $violations),
        );
    }
}
