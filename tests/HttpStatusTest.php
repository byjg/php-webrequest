<?php

namespace Test;

use ByJG\WebRequest\HttpStatus;
use PHPUnit\Framework\TestCase;

class HttpStatusTest extends TestCase
{

    public function testDescription()
    {
        $this->assertEquals("OK", HttpStatus::OK->description());
        $this->assertEquals("Not found", HttpStatus::NOT_FOUND->description());
        $this->assertEquals("Internal server error", HttpStatus::INTERNAL_SERVER_ERROR->description());
        $this->assertEquals("Bad request", HttpStatus::BAD_REQUEST->description());
        $this->assertEquals("Unauthorized", HttpStatus::UNAUTHORIZED->description());
        $this->assertEquals("Payment required", HttpStatus::PAYMENT_REQUIRED->description());
        $this->assertEquals("Forbidden", HttpStatus::FORBIDDEN->description());
        $this->assertEquals("Method not allowed", HttpStatus::METHOD_NOT_ALLOWED->description());
        $this->assertEquals("Not acceptable", HttpStatus::NOT_ACCEPTABLE->description());
        $this->assertEquals("Proxy authentication required", HttpStatus::PROXY_AUTHENTICATION_REQUIRED->description());
        $this->assertEquals("Request timeout", HttpStatus::REQUEST_TIMEOUT->description());
        $this->assertEquals("Conflict", HttpStatus::CONFLICT->description());
        $this->assertEquals("Gone", HttpStatus::GONE->description());
        $this->assertEquals("Length required", HttpStatus::LENGTH_REQUIRED->description());
        $this->assertEquals("Precondition failed", HttpStatus::PRECONDITION_FAILED->description());
        $this->assertEquals("Payload too large", HttpStatus::PAYLOAD_TOO_LARGE->description());
        $this->assertEquals("Uri too long", HttpStatus::URI_TOO_LONG->description());
        $this->assertEquals("Unsupported media type", HttpStatus::UNSUPPORTED_MEDIA_TYPE->description());
        $this->assertEquals("Range not satisfiable", HttpStatus::RANGE_NOT_SATISFIABLE->description());
        $this->assertEquals("Expectation failed", HttpStatus::EXPECTATION_FAILED->description());
        $this->assertEquals("I'm a teapot", HttpStatus::IM_A_TEAPOT->description());
        $this->assertEquals("I'm used", HttpStatus::IM_USED->description());
        $this->assertEquals("Misdirected request", HttpStatus::MISDIRECTED_REQUEST->description());
        $this->assertEquals("Unprocessable entity", HttpStatus::UNPROCESSABLE_ENTITY->description());
        $this->assertEquals("Locked", HttpStatus::LOCKED->description());
        $this->assertEquals("Failed dependency", HttpStatus::FAILED_DEPENDENCY->description());
        $this->assertEquals("Too early", HttpStatus::TOO_EARLY->description());
    }
}
