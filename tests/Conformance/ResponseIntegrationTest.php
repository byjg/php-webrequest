<?php

namespace Tests\Conformance;

use ByJG\WebRequest\Factory\ResponseFactory;
use Http\Psr7Test\ResponseIntegrationTest as Psr7ResponseIntegrationTest;
use Psr\Http\Message\ResponseInterface;

class ResponseIntegrationTest extends Psr7ResponseIntegrationTest
{
    #[\Override]
    public function createSubject(): ResponseInterface
    {
        return (new ResponseFactory())->createResponse();
    }
}
