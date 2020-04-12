<?php

namespace ByJG\Util\Helper;

use ByJG\Util\MultiPartItem;
use ByJG\Util\Psr7\MessageException;
use ByJG\Util\Psr7\Request;
use MintWare\Streams\MemoryStream;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class RequestMultiPart extends Request
{
    /**
     * @param UriInterface $uri
     * @param string $method
     * @param MultiPartItem[] $multiPartItem
     * @return Request|MessageInterface|RequestInterface
     * @throws MessageException
     */
    public static function build(UriInterface $uri, $method, $multiPartItem)
    {
        $request = Request::getInstance($uri)
            ->withMethod($method)
            ->withHeader("content-type", "application/x-www-form-urlencoded");

        self::buildMultiPart($multiPartItem, $request);

        return $request;
    }

    /**
     * @param MultiPartItem[] $multiPartItem
     * @param RequestInterface $request
     */
    protected static function buildMultiPart($multiPartItem, $request)
    {
        $stream = new MemoryStream();

        $boundary = 'boundary-' . md5(time());

        foreach ($multiPartItem as $item) {
            $stream->write("--$boundary\nContent-Disposition: form-data; name=\"{$item->getField()}\";");
            $fileName = $item->getFileName();
            if (!empty($fileName)) {
                $stream->write(" filename=\"{$item->getFileName()}\";");
            }
            $contentType = $item->getContentType();
            if (!empty($contentType)) {
                $stream->write("\nContent-Type: {$item->getContentType()}");
            }
            $stream->write("\n\n{$item->getContent()}\n");
        }
        $stream->write("--$boundary--");

        $request
            ->withBody($stream)
            ->withHeader("Content-Type", "multipart/form-data; boundary=$boundary");
    }
}
