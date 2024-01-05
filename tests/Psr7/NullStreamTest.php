<?php

namespace Test\Psr7;

use ByJG\Util\Psr7\NullStream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class NullStreamTest extends TestCase
{
    protected ?StreamInterface $stream;

    public function getResource(string $data): StreamInterface
    {
        return new NullStream();
    }

    public function tearDownResource()
    {
        $this->stream = null;
    }

    public function setUp(): void
    {
        $this->stream = $this->getResource('');
    }

    public function tearDown(): void
    {
        $this->tearDownResource();
        $this->stream = null;
    }

    public function testToString()
    {
        $this->assertEquals('', (string)$this->stream);
    }

    public function TestToStringEmpty()
    {
        $this->stream = $this->getResource(null);
        $this->assertEquals("", (string)$this->stream);
    }

    public function testGetSize()
    {
        $this->assertSame(0, $this->stream->getSize());
    }

    public function testTell()
    {
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(10);
        $this->assertSame(0, $this->stream->tell());
    }

    public function testEof()
    {
        $this->assertTrue($this->stream->eof());
    }

    public function testIsSeekable()
    {
        $this->assertTrue($this->stream->isSeekable());
    }

    public function testSeek()
    {
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(10);
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(10, SEEK_CUR);
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(0, SEEK_END);
        $this->assertSame(0, $this->stream->tell());
    }

    public function testRewind()
    {
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(0, SEEK_END);
        $this->assertSame(0, $this->stream->tell());
        $this->stream->rewind();
        $this->assertSame(0, $this->stream->tell());
    }

    public function testIsWritable()
    {
        $this->assertTrue($this->stream->isWritable());
    }

    public function testOverwrite()
    {
        $this->stream->rewind();
        $result = $this->stream->write('test');

        $this->assertEquals("", (string)$this->stream);
        $this->assertSame(0, $result);
    }

    public function testAppend()
    {
        $this->stream->seek(0, SEEK_END);
        $result = $this->stream->write("test");
        $this->assertEquals('', (string)$this->stream);
        $this->assertSame(0, $result);
    }

    public function testIsReadble()
    {
        $this->assertTrue($this->stream->isReadable());
    }

    public function testRead1()
    {
        $result = $this->stream->read(6);
        $this->assertEquals("", $result);
        $result = $this->stream->read(6);
        $this->assertEquals("", $result);
    }

    public function testGetContents1()
    {
        $result = $this->stream->read(6);
        $this->assertEquals("", $result);
        $result = $this->stream->getContents();
        $this->assertEquals("", $result);
    }

    public function testGetContents2()
    {
        $this->stream->rewind();
        $result = $this->stream->getContents();
        $this->assertEquals("", $result);
    }
}
