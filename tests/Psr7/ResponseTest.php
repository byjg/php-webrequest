<?php

use ByJG\Util\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{

    public function testGetStatusCode()
    {
        $response = new Response();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("OK", $response->getReasonPhrase());

        $response = $response->withStatus(404);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals("Not Found", $response->getReasonPhrase());

        $response = $response->withStatus(401, "Metodo nao permitido");
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals("Metodo nao permitido", $response->getReasonPhrase());
    }
}
