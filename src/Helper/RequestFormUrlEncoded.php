<?php

namespace ByJG\Util\Helper;

use ByJG\Util\Exception\MessageException;
use ByJG\Util\Exception\RequestException;
use ByJG\Util\Psr7\MemoryStream;
use ByJG\Util\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class RequestFormUrlEncoded extends Request
{
    /**
     * @param UriInterface $uri
     * @param $params
     * @return RequestInterface
     * @throws MessageException
     * @throws RequestException
     */
    public static function build(UriInterface $uri, $params): RequestInterface
    {
        if (is_array($params)) {
            $params = http_build_query($params);
        }
        return Request::getInstance($uri)
            ->withMethod("POST")
            ->withBody(new MemoryStream($params))
            ->withHeader("content-type", "application/x-www-form-urlencoded");
    }
}
