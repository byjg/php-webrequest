<?php

namespace Test\Psr7;

use ByJG\WebRequest\Psr7\MemoryStream;
use PHPUnit\Framework\TestCase;


class ExtendedStreamInterfaceTest extends TestCase
{
    public function testAppend(): void
    {
        $stream1 = new MemoryStream("text1");
        $stream2 = new MemoryStream("text2");

        $stream1->appendStream($stream2);

        $stream1->rewind();
        $stream2->rewind();
        $this->assertEquals("text1text2", $stream1);
        $this->assertEquals("text2", $stream2);
    }

    public function testFilterRead(): void
    {
        $stream = new MemoryStream("ZW5jb2RlZCB0ZXh0");
        $stream->addFilter("convert.base64-decode");

        $this->assertEquals("encoded text", $stream);
    }

    public function testFilterWrite(): void
    {
        $stream = new MemoryStream();
        $stream->addFilter("convert.base64-encode", "w");
        $stream->write("encoded text");

        $this->assertEquals("ZW5jb2RlZCB0ZXh0", $stream);
    }

}
