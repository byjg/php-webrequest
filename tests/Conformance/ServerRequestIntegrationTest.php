<?php

namespace Tests\Conformance;

use ByJG\WebRequest\Factory\ServerRequestFactory;
use Http\Psr7Test\ServerRequestIntegrationTest as Psr7ServerRequestIntegrationTest;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequestIntegrationTest extends Psr7ServerRequestIntegrationTest
{
    #[\Override]
    public function createSubject(): ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest('GET', '/', $_SERVER);
    }
}
