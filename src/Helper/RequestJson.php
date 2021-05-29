<?php

namespace ByJG\Util\Helper;

use ByJG\Util\Psr7\MessageException;
use ByJG\Util\Psr7\Request;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class RequestJson extends Request
{
    /**
     * @param UriInterface $uri
     * @param $method
     * @param $json
     * @return Request|MessageInterface|RequestInterface
     * @throws MessageException
     */
    public static function build(UriInterface $uri, $method, $json)
    {
        if (is_array($json)) {
            $json = json_encode($json);
        }

        return Request::getInstance($uri)
            ->withMethod($method)
            ->withBody(\GuzzleHttp\Psr7\Utils::streamFor($json))
            ->withHeader("content-type", "application/json");
    }
}
