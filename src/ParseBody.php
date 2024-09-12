<?php


namespace ByJG\Util;


use Psr\Http\Message\MessageInterface;

class ParseBody
{
    /**
     * @param MessageInterface $response
     * @return mixed
     */
    public static function parse(MessageInterface $response): mixed
    {
        if (str_contains(trim($response->getHeaderLine("content-type")),  "application/json")) {
            return json_decode($response->getBody()->getContents(), true);
        }

        return $response->getBody()->getContents();
    }
}