<?php

namespace ByJG\Util\Psr7;

use ByJG\Util\Helper\ExtendedStreamInterface;
use Psr\Http\Message\StreamInterface;

class NullStream implements ExtendedStreamInterface, StreamInterface
{

    function appendStream($stream)
    {
        // TODO: Implement appendStream() method.
    }

    function addFilter($filter)
    {
        // TODO: Implement addFilter() method.
    }

    public function __toString()
    {
        return "";
    }

    public function close()
    {
        // TODO: Implement close() method.
    }

    public function detach()
    {
        // TODO: Implement detach() method.
    }

    public function getSize()
    {
        return 0;
    }

    public function tell()
    {
        return 0;
    }

    public function eof()
    {
        return true;
    }

    public function isSeekable()
    {
        return true;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        return 0;
    }

    public function rewind()
    {
        return 0;
    }

    public function isWritable()
    {
        return true;
    }

    public function write($string)
    {
        // TODO: Implement write() method.
    }

    public function isReadable()
    {
        return true;
    }

    public function read($length)
    {
        return "";
    }

    public function getContents()
    {
        return "";
    }

    public function getMetadata($key = null)
    {
        // TODO: Implement getMetadata() method.
    }
}