<?php

namespace ByJG\Util\Exception;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;

class CurlException extends Exception implements ClientExceptionInterface
{

}
