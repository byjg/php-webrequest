<?php

namespace ByJG\WebRequest\Factory;

use ByJG\Util\Uri;
use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequestFactory implements ServerRequestFactoryInterface
{
    /**
     * @inheritDoc
     * @throws MessageException
     * @throws RequestException
     */
    #[\Override]
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        }
        return (new ServerRequest($uri, $serverParams))->withMethod($method);
    }

    public static function instance(): ServerRequestFactoryInterface
    {
        return new ServerRequestFactory();
    }
}