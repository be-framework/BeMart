#!/usr/bin/env php
<?php

declare(strict_types=1);

use MyVendor\BeMart\Injector;
use MyVendor\BeMart\Module\ToolUseAgentModule;
use MyVendor\BeMart\ToolUse\BeMartAgentBuilder;

require dirname(__DIR__) . '/vendor/autoload.php';

$prompt = trim(implode(' ', array_slice($_SERVER['argv'], 1)));
if ($prompt === '') {
    $prompt = 'sample-001 の商品を教えてください';
}

$injector = Injector::getOverrideInstance('cli-fake-hal-app', new ToolUseAgentModule());
$builder = $injector->getInstance(BeMartAgentBuilder::class);
assert($builder instanceof BeMartAgentBuilder);

$response = $builder->createCoordinator()->run($prompt, $builder->readOnlyOptions());
echo $response->getText() . PHP_EOL;

exit($response->completed ? 0 : 1);
