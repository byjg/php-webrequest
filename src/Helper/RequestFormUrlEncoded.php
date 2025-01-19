<?php

namespace ByJG\WebRequest\Helper;

use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\HttpMethod;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class RequestFormUrlEncoded extends Request
{
    /**
     * @param UriInterface $uri
     * @param array|string $params
     * @return RequestInterface
     * @throws MessageException
     * @throws RequestException
     */
    public static function build(UriInterface $uri, array|string $params): RequestInterface
    {
        if (is_array($params)) {
            $params = http_build_query($params);
        }
        return Request::getInstance($uri)
            ->withMethod(HttpMethod::POST->value)
            ->withBody(new MemoryStream($params))
            ->withHeader("content-type", "application/x-www-form-urlencoded");
    }
}
