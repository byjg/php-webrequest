<?php

namespace ByJG\WebRequest\Exception;

use InvalidArgumentException;

/**
 * An invalid argument to a message (header, method, protocol version...). PSR-7 asks for an
 * InvalidArgumentException there, and this is still an Exception for the code that catches that.
 */
class MessageException extends InvalidArgumentException
{

}
