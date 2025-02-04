<?php

namespace ByJG\WebRequest\Helper;

use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\HttpMethod;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Request;
use Psr\Http\Message\UriInterface;

class RequestJson extends Request
{
    /**
     * @param UriInterface $uri
     * @param HttpMethod|string $method
     * @param array|string $json
     * @return Request
     * @throws MessageException
     * @throws RequestException
     */
    public static function build(UriInterface $uri, HttpMethod|string $method, array|string $json): Request
    {
        if (is_array($json)) {
            $json = json_encode($json);
        }

        return Request::getInstance($uri)
            ->withMethod($method)
            ->withBody(new MemoryStream($json))
            ->withHeader("content-type", "application/json");
    }
}
