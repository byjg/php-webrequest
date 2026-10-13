<?php

namespace Tests;

use ByJG\WebRequest\HttpStatus;
use PHPUnit\Framework\TestCase;

class HttpStatusTest extends TestCase
{

    public function testDescription()
    {
        $this->assertEquals("OK", HttpStatus::Ok->description());
        $this->assertEquals("Not found", HttpStatus::NotFound->description());
        $this->assertEquals("Internal server error", HttpStatus::InternalServerError->description());
        $this->assertEquals("Bad request", HttpStatus::BadRequest->description());
        $this->assertEquals("Unauthorized", HttpStatus::Unauthorized->description());
        $this->assertEquals("Payment required", HttpStatus::PaymentRequired->description());
        $this->assertEquals("Forbidden", HttpStatus::Forbidden->description());
        $this->assertEquals("Method not allowed", HttpStatus::MethodNotAllowed->description());
        $this->assertEquals("Not acceptable", HttpStatus::NotAcceptable->description());
        $this->assertEquals("Proxy authentication required", HttpStatus::ProxyAuthenticationRequired->description());
        $this->assertEquals("Request timeout", HttpStatus::RequestTimeout->description());
        $this->assertEquals("Conflict", HttpStatus::Conflict->description());
        $this->assertEquals("Gone", HttpStatus::Gone->description());
        $this->assertEquals("Length required", HttpStatus::LengthRequired->description());
        $this->assertEquals("Precondition failed", HttpStatus::PreconditionFailed->description());
        $this->assertEquals("Payload too large", HttpStatus::PayloadTooLarge->description());
        $this->assertEquals("Uri too long", HttpStatus::UriTooLong->description());
        $this->assertEquals("Unsupported media type", HttpStatus::UnsupportedMediaType->description());
        $this->assertEquals("Range not satisfiable", HttpStatus::RangeNotSatisfiable->description());
        $this->assertEquals("Expectation failed", HttpStatus::ExpectationFailed->description());
        $this->assertEquals("I'm a teapot", HttpStatus::ImATeapot->description());
        $this->assertEquals("I'm used", HttpStatus::ImUsed->description());
        $this->assertEquals("Misdirected request", HttpStatus::MisdirectedRequest->description());
        $this->assertEquals("Unprocessable entity", HttpStatus::UnprocessableEntity->description());
        $this->assertEquals("Locked", HttpStatus::Locked->description());
        $this->assertEquals("Failed dependency", HttpStatus::FailedDependency->description());
        $this->assertEquals("Too early", HttpStatus::TooEarly->description());
    }
}
