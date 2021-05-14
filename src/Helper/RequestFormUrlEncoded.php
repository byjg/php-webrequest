<?php

namespace ByJG\Util\Helper;

use ByJG\Util\Psr7\MessageException;
use ByJG\Util\Psr7\Request;
use ByJG\Util\Psr7\MemoryStream;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class RequestFormUrlEncoded extends Request
{
    /**
     * @param UriInterface $uri
     * @param $params
     * @return Request|MessageInterface|RequestInterface
     * @throws MessageException
     */
    public static function build(UriInterface $uri, $params)
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
