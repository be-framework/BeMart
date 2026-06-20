<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Http;

use BEAR\Resource\ResourceInterface;
use MyVendor\BeMart\Tests\Hypermedia\FlowCustomerRegistrationTest as Workflow;

final class FlowCustomerRegistrationTest extends Workflow
{
    protected function newResource(): ResourceInterface
    {
        return new HttpResource(
            '127.0.0.1:8094',
            __DIR__ . '/prod-json-index.php',
            __DIR__ . '/log/' . self::FLOW_ID . '.log',
        );
    }

    /**
     * doActivateCustomer is exercised only in the in-process Hypermedia
     * projection.
     *
     * The Hypermedia test recreates the mail-auth precondition (read the
     * server-generated `secret_key`, demote the customer to provisional
     * status=1) through the in-process injector's PDO connection, which is
     * inside the rolled-back WorkflowDbSession transaction. Over real HTTP
     * the activation POST runs in a separate server process with its own DB
     * connection that cannot see those uncommitted in-process changes, so the
     * precondition is invisible and the affordance cannot be driven across the
     * process boundary. Skipped here; the green e2e lives in
     * tests/Hypermedia/FlowCustomerRegistrationTest::testActivatesCustomer.
     */
    public function testActivatesCustomer(): void
    {
        $this->markTestSkipped(
            'doActivateCustomer needs an in-process DB precondition (secret_key + status demotion) '
            . 'invisible to the separate HTTP server process; verified in the Hypermedia projection.',
        );
    }
}
