<?php

namespace Test\Psr7;

use ByJG\WebRequest\Psr7\MemoryStream;
use Psr\Http\Message\StreamInterface;

class MemoryStreamTest extends StreamBaseTest
{
    public function getResource(?string $data): StreamInterface
    {
        return new MemoryStream($data);
    }

    /**
     * @return void
     */
    public function tearDownResource()
    {
        $this->stream->close();
        $this->stream = null;
    }

    /**
     * @return true
     */
    public function isWriteable()
    {
        return true;
    }

    /**
     * @return true
     */
    public function canOverwrite()
    {
        return true;
    }
}
