<?php


namespace ByJG\Util;


use Psr\Http\Message\MessageInterface;

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