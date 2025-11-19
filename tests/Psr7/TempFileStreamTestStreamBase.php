<?php

namespace Test\Psr7;

use ByJG\WebRequest\Factory\StreamFactory;
use ByJG\WebRequest\Psr7\TempFileStream;
use Psr\Http\Message\StreamInterface;

class TempFileStreamTestStreamBase extends TestStreamBase
{
    #[\Override]
    public function getResource(?string $data): StreamInterface
    {
        return new TempFileStream($data);
    }

    /**
     * @return void
     */
    #[\Override]
    public function tearDownResource()
    {
        $this->stream->close();
        $this->stream = null;
    }

    /**
     * @return true
     */
    #[\Override]
    public function isWriteable()
    {
        return true;
    }

    /**
     * @return true
     */
    #[\Override]
    public function canOverwrite()
    {
        return true;
    }

    public function testCreateStream()
    {
        $stream = StreamFactory::instance(TempFileStream::class)->createStream("test");

        $this->assertEquals("test", (string)$stream);
    }
}
