<?php

namespace ByJG\Util\Helper;

use ByJG\Util\MultiPartItem;
use ByJG\Util\Psr7\MessageException;
use ByJG\Util\Psr7\Request;
use ByJG\Util\Psr7\MemoryStream;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class RequestMultiPart extends Request
{
    /**
     * @param UriInterface $uri
     * @param string $method
     * @param MultiPartItem[] $multiPartItem
     * @param string $boundary
     * @return Request|MessageInterface|RequestInterface
     * @throws MessageException
     */
    public static function build(UriInterface $uri, $method, $multiPartItem, $boundary = null)
    {
        $request = Request::getInstance($uri)
            ->withMethod($method);

        $request = self::buildMultiPart($multiPartItem, $request, $boundary);

        return $request;
    }

    /**
     * @param MultiPartItem[] $multiPartItems
     * @param RequestInterface $request
     * @param string $boundary
     * @return RequestInterface
     */
    public static function buildMultiPart($multiPartItems, $request, $boundary = null)
    {
        $stream = new MemoryStream();

        $boundary = (is_null($boundary) ? md5(time()) : $boundary);

        $contentType = "multipart/form-data";

        foreach ($multiPartItems as $item) {
            $item->build($stream, $boundary);
            if ($item->getContentDisposition() != "form-data") {
                $contentType = "multipart/related";
            }
        }

        $stream->write("--$boundary--");

        return $request
                ->withBody($stream)
                ->withHeader("Content-Type", "$contentType; boundary=$boundary");
    }
}
