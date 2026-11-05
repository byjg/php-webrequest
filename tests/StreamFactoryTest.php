<?php

namespace Tests;

use ByJG\WebRequest\Factory\StreamFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class StreamFactoryTest extends TestCase
{
    public function testStreamFromResourceUsesTheResource(): void
    {
        $resource = fopen('php://memory', 'w+');
        fwrite($resource, 'abcdef');
        fseek($resource, 3);

        $stream = (new StreamFactory())->createStreamFromResource($resource);

        // Same position, and what the stream writes is in the resource
        $this->assertSame(3, $stream->tell());
        $stream->seek(0, SEEK_END);
        $stream->write('ghi');
        rewind($resource);
        $this->assertSame('abcdefghi', stream_get_contents($resource));

        $stream->close();
        $this->assertFalse(is_resource($resource));
    }

    public function testDetachReturnsTheResource(): void
    {
        $resource = fopen('php://memory', 'w+');
        $stream = (new StreamFactory())->createStreamFromResource($resource);

        $this->assertSame($resource, $stream->detach());
        $this->assertTrue(is_resource($resource));
        $this->assertFalse($stream->isReadable());
        $this->assertSame('', (string)$stream);
    }

    public function testStreamFromFileUsesTheMode(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'stream');
        try {
            file_put_contents($filename, 'abc');

            $stream = (new StreamFactory())->createStreamFromFile($filename);
            $this->assertSame('abc', (string)$stream);
            $this->assertFalse($stream->isWritable());

            $stream = (new StreamFactory())->createStreamFromFile($filename, 'a');
            $this->assertTrue($stream->isWritable());
            $this->assertFalse($stream->isReadable());
            $this->expectException(RuntimeException::class);
            $stream->read(1);
        } finally {
            unlink($filename);
        }
    }

    public function testStreamFromFileWithInvalidMode(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("is not a valid mode");
        (new StreamFactory())->createStreamFromFile(__FILE__, 'z');
    }
}
