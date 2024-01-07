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
        if (!str_starts_with("application/json", $response->getHeaderLine("content-type"))) {
            return json_decode($response->getBody(), true);
        }

        return (string)$response->getBody();
    }
}