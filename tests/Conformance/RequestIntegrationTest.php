<?php

namespace Tests\Conformance;

use ByJG\WebRequest\Factory\RequestFactory;
use Http\Psr7Test\RequestIntegrationTest as Psr7RequestIntegrationTest;
use Psr\Http\Message\RequestInterface;

/**
 * php-http's PSR-7 conformance tests, the suite other PSR-7 implementations run.
 * Run it with the Conformance test suite (see the README).
 */
class RequestIntegrationTest extends Psr7RequestIntegrationTest
{
    #[\Override]
    public function createSubject(): RequestInterface
    {
        return (new RequestFactory())->createRequest('GET', '/');
    }
}
