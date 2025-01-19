<?php

namespace ByJG\WebRequest\Factory;

use ByJG\Util\Uri;
use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\HttpMethod;
use ByJG\WebRequest\Psr7\Request;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

class RequestFactory implements RequestFactoryInterface
{
    /**
     * @inheritDoc
     * @param HttpMethod|string $method
     * @param Uri|string $uri
     * @throws RequestException
     * @throws MessageException
     */
    public function createRequest(HttpMethod|string $method, $uri): RequestInterface
    {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }
        if ($method instanceof HttpMethod) {
            $method = $method->value;
        }
        return (new Request($uri))->withMethod($method);
    }

    public static function instance(): static
    {
        return new RequestFactory();
    }
}