<?php

namespace Test\Psr7;

use ByJG\WebRequest\Factory\StreamFactory;
use ByJG\WebRequest\Psr7\MemoryStream;
use Psr\Http\Message\StreamInterface;

class MemoryStreamTestStreamBase extends TestStreamBase
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

    public function testCreateStream()
    {
        $stream = StreamFactory::instance(MemoryStream::class)->createStream("test");
        $this->assertEquals("test", (string)$stream);

        try {
            file_put_contents('/tmp/test.txt', 'test');
            $stream = StreamFactory::instance(MemoryStream::class)->createStreamFromFile('/tmp/test.txt');
            $this->assertEquals("test", (string)$stream);
        } finally {
            if (file_exists('/tmp/test.txt')) {
                unlink('/tmp/test.txt');
            }
        }
    }
}
