<?php

namespace ByJG\Util\Helper;

use ByJG\Util\Psr7\Request;
use MintWare\Streams\MemoryStream;
use Psr\Http\Message\UriInterface;

class RequestJson extends Request
{
    public static function build(UriInterface $uri, $method, $json)
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
