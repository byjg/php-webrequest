<?php

namespace ByJG\WebRequest\Exception;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;

class CurlException extends Exception implements ClientExceptionInterface
{

}
