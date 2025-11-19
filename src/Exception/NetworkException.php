<?php

namespace ByJG\WebRequest\Exception;

use Exception;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;

class NetworkException extends Exception implements RequestExceptionInterface
{
    protected RequestInterface $request;
    public function __construct($request, $message = "", $code = 0, $previous = null)
    {
        $this->request = $request;
        parent::__construct($message, $code, $previous);
    }

    #[\Override]
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
