<?php

namespace Test\Psr7;

use ByJG\Util\Psr7\TempFileStream;
use Psr\Http\Message\StreamInterface;

class TempFileStreamTest extends StreamBaseTest
{
    public function getResource(string $data): StreamInterface
    {
        return new TempFileStream($data);
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
