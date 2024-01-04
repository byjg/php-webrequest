<?php

namespace Test\Psr7;

use ByJG\Util\Psr7\MemoryStream;
use Psr\Http\Message\StreamInterface;

class MemoryStreamTest extends StreamBaseTest
{
    public function getResource(string $data): StreamInterface
    {
        return new MemoryStream($data);
    }

    public function tearDownResource()
    {
        $this->stream->close();
        $this->stream = null;
    }

    public function isWriteable()
    {
        return true;
    }

    public function canOverwrite()
    {
        return true;
    }
}
