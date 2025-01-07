<?php

namespace Test\Psr7;

use ByJG\WebRequest\HttpStatus;
use ByJG\WebRequest\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{

    public function testGetStatusCode(): void
    {
        $response = new Response();
        $this->assertEquals(HttpStatus::OK->value, $response->getStatusCode());
        $this->assertEquals(HttpStatus::OK->name, $response->getReasonPhrase());

        $response = $response->withStatus(HttpStatus::NOT_FOUND);
        $this->assertEquals(HttpStatus::NOT_FOUND->value, $response->getStatusCode());
        $this->assertEquals("Not Found", $response->getReasonPhrase());

        $response = $response->withStatus(HttpStatus::NOT_FOUND, "Metodo nao permitido");
        $this->assertEquals(HttpStatus::NOT_FOUND->value, $response->getStatusCode());
        $this->assertEquals("Metodo nao permitido", $response->getReasonPhrase());
    }
}
