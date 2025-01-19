<?php

namespace ByJG\WebRequest\Factory;

use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\Psr7\Request;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

class RequestFactory implements RequestFactoryInterface
{
    /**
     * @inheritDoc
     * @throws RequestException
     * @throws MessageException
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return (new Request($uri))->withMethod($method);
    }

    public static function instance(): RequestFactoryInterface
    {
        return new RequestFactory();
    }
}