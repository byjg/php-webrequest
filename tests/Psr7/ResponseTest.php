<?php

namespace Test\Psr7;

use ByJG\WebRequest\Factory\ResponseFactory;
use ByJG\WebRequest\HttpStatus;
use ByJG\WebRequest\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{

    public function testGetStatusCode(): void
    {
        $response = new Response();
        $this->assertEquals('200', $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());

        $response = $response->withStatus(HttpStatus::NOT_FOUND);
        $this->assertEquals('404', $response->getStatusCode());
        $this->assertEquals("Not Found", $response->getReasonPhrase());

        $response = $response->withStatus(HttpStatus::NOT_FOUND, "Metodo nao permitido");
        $this->assertEquals('404', $response->getStatusCode());
        $this->assertEquals("Metodo nao permitido", $response->getReasonPhrase());
    }

    public function testCreateResponse()
    {
        $response = ResponseFactory::instance()->createResponse(404);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals("Not Found", $response->getReasonPhrase());
    }
}
