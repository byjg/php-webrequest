<?php

namespace ByJG\Util\Helper;

use ByJG\Util\Psr7\Request;
use MintWare\Streams\MemoryStream;
use Psr\Http\Message\UriInterface;

class RequestFormUrlEncoded extends Request
{
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
