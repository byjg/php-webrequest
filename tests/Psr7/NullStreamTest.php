<?php

namespace Test\Psr7;

use ByJG\WebRequest\Psr7\NullStream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class NullStreamTest extends TestCase
{
    protected ?StreamInterface $stream;

    public function getResource(?string $data): StreamInterface
    {
        return new NullStream();
    }

    public function tearDownResource(): void
    {
        $this->stream = null;
    }

    #[\Override]
    public function setUp(): void
    {
        $this->stream = $this->getResource('');
    }

    #[\Override]
    public function tearDown(): void
    {
        $this->tearDownResource();
        $this->stream = null;
    }

    public function testToString(): void
    {
        $this->assertEquals('', (string)$this->stream);
    }

    public function TestToStringEmpty(): void
    {
        $this->stream = $this->getResource(null);
        $this->assertEquals("", (string)$this->stream);
    }

    public function testGetSize(): void
    {
        $this->assertSame(0, $this->stream->getSize());
    }

    public function testTell(): void
    {
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(10);
        $this->assertSame(0, $this->stream->tell());
    }

    public function testEof(): void
    {
        $this->assertTrue($this->stream->eof());
    }

    public function testIsSeekable(): void
    {
        $this->assertTrue($this->stream->isSeekable());
    }

    public function testSeek(): void
    {
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(10);
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(10, SEEK_CUR);
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(0, SEEK_END);
        $this->assertSame(0, $this->stream->tell());
    }

    public function testRewind(): void
    {
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(0, SEEK_END);
        $this->assertSame(0, $this->stream->tell());
        $this->stream->rewind();
        $this->assertSame(0, $this->stream->tell());
    }

    public function testIsWritable(): void
    {
        $this->assertTrue($this->stream->isWritable());
    }

    public function testOverwrite(): void
    {
        $this->stream->rewind();
        $result = $this->stream->write('test');

        $this->assertEquals("", (string)$this->stream);
        $this->assertSame(0, $result);
    }

    public function testAppend(): void
    {
        $this->stream->seek(0, SEEK_END);
        $result = $this->stream->write("test");
        $this->assertEquals('', (string)$this->stream);
        $this->assertSame(0, $result);
    }

    public function testIsReadble(): void
    {
        $this->assertTrue($this->stream->isReadable());
    }

    public function testRead1(): void
    {
        $result = $this->stream->read(6);
        $this->assertEquals("", $result);
        $result = $this->stream->read(6);
        $this->assertEquals("", $result);
    }

    public function testGetContents1(): void
    {
        $result = $this->stream->read(6);
        $this->assertEquals("", $result);
        $result = $this->stream->getContents();
        $this->assertEquals("", $result);
    }

    public function testGetContents2(): void
    {
        $this->stream->rewind();
        $result = $this->stream->getContents();
        $this->assertEquals("", $result);
    }
}
