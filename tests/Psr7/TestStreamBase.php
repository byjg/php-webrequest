<?php

namespace Test\Psr7;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

abstract class TestStreamBase extends TestCase
{
    const TEXT1 = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam auctor augue justo, id condimentum tortor molestie et. Quisque at egestas dui. Vestibulum id lectus et mi interdum lobortis non sit.";

    abstract public function getResource(?string $data): StreamInterface;

    abstract public function tearDownResource();

    abstract public function isWriteable();

    abstract public function canOverwrite();

    protected ?StreamInterface $stream;

    #[\Override]
    public function setUp(): void
    {
        $this->stream = $this->getResource(self::TEXT1);
    }

    #[\Override]
    public function tearDown(): void
    {
        $this->tearDownResource();
        $this->stream = null;
    }

    public function testToString(): void
    {
        $this->assertEquals(self::TEXT1, (string)$this->stream);
    }

    public function TestToStringEmpty(): void
    {
        $this->stream = $this->getResource(null);
        $this->assertEquals("", (string)$this->stream);
    }

    public function testGetSize(): void
    {
        $this->assertSame(strlen(self::TEXT1), $this->stream->getSize());
    }

    public function testTell(): void
    {
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(10);
        $this->assertSame(10, $this->stream->tell());
    }

    public function testEof(): void
    {
        $this->assertFalse($this->stream->eof());
        $this->stream->seek(0, SEEK_END);
        $this->stream->read(1);        // It is necessary read after the end of file to get eof true.
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
        $this->assertSame(10, $this->stream->tell());
        $this->stream->seek(10, SEEK_CUR);
        $this->assertSame(20, $this->stream->tell());
        $this->stream->seek(-10, SEEK_END);
        $this->assertSame(strlen(self::TEXT1)-10, $this->stream->tell());
    }

    public function testRewind(): void
    {
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(0, SEEK_END);
        $this->assertSame(strlen(self::TEXT1), $this->stream->tell());
        $this->stream->rewind();
        $this->assertSame(0, $this->stream->tell());
    }

    public function testIsWritable(): void
    {
        if ($this->isWriteable()) {
            $this->assertTrue($this->stream->isWritable());
        } else {
            $this->assertFalse($this->stream->isWritable());
        }

    }

    public function testOverwrite(): void
    {
        if ($this->isWriteable()) {
            $this->stream->rewind();
            $result = $this->stream->write("test");
            if ($this->canOverwrite()) {
                $this->assertEquals("test" . substr(self::TEXT1, 4), (string)$this->stream);
                $this->assertSame(4, $result);
            } else {
                $this->assertNotEquals("test" . substr(self::TEXT1, 4), (string)$this->stream);
            }
        }
    }

    public function testAppend(): void
    {
        if ($this->isWriteable()) {
            $this->stream->seek(0, SEEK_END);
            $result = $this->stream->write("test");
            $this->assertEquals(self::TEXT1 . "test", (string)$this->stream);
            $this->assertSame(4, $result);
        }
    }

    public function testIsReadble(): void
    {
        $this->assertTrue($this->stream->isReadable());
    }

    public function testRead1(): void
    {
        $result = $this->stream->read(6);
        $this->assertEquals("Lorem ", $result);
        $result = $this->stream->read(6);
        $this->assertEquals("ipsum ", $result);
    }

    public function testGetContents1(): void
    {
        $result = $this->stream->read(6);
        $this->assertEquals("Lorem ", $result);
        $result = $this->stream->getContents();
        $this->assertEquals(substr(self::TEXT1, 6), $result);
    }

    public function testGetContents2(): void
    {
        $this->stream->rewind();
        $result = $this->stream->getContents();
        $this->assertEquals(self::TEXT1, $result);
    }
}
