<?php

namespace ByJG\Util\Helper;

use ByJG\Util\Exception\MessageException;
use ByJG\Util\Exception\RequestException;
use ByJG\Util\Psr7\MemoryStream;
use ByJG\Util\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class RequestJson extends Request
{
    /**
     * @param UriInterface $uri
     * @param $method
     * @param $json
     * @return RequestInterface
     * @throws MessageException
     * @throws RequestException
     */
    public static function build(UriInterface $uri, $method, $json): RequestInterface
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
