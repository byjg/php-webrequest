<?php

namespace ByJG\WebRequest\Factory;

use ByJG\WebRequest\Psr7\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * @inheritDoc
     */
    #[\Override]
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return (new Response($code))->withStatus($code, $reasonPhrase);
    }

    public static function instance(): ResponseFactoryInterface
    {
        return new ResponseFactory();
    }
}