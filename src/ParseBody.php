<?php


namespace ByJG\Util;


use MintWare\Streams\MemoryStream;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

class ParseBody
{
    /**
     * @param MessageInterface $response
     * @return mixed
     */
    public static function parse(MessageInterface $response)
    {
        if (strpos("application/json", $response->getHeaderLine("content-type")) !== 0) {
            return json_decode($response->getBody(), true);
        }

        return (string)$response->getBody();
    }
}