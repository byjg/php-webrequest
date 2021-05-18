<?php

use ByJG\Util\Psr7\StreamBase;
use PHPUnit\Framework\TestCase;


class ExtendedStreamInterfaceTest extends TestCase
{
    public function testAppend()
    {
        $stream1 = new \ByJG\Util\Psr7\MemoryStream("text1");
        $stream2 = new \ByJG\Util\Psr7\MemoryStream("text2");

        $stream1->appendStream($stream2);

        $stream1->rewind();
        $stream2->rewind();
        $this->assertEquals("text1text2", $stream1);
        $this->assertEquals("text2", $stream2);
    }

    public function testFilterRead()
    {
        $stream = new \ByJG\Util\Psr7\MemoryStream("ZW5jb2RlZCB0ZXh0");
        $stream->addFilter("convert.base64-decode", "r");

        $this->assertEquals("encoded text", $stream);
    }

    public function testFilterWrite()
    {
        $stream = new \ByJG\Util\Psr7\MemoryStream();
        $stream->addFilter("convert.base64-encode", "w");
        $stream->write("encoded text");

        $this->assertEquals("ZW5jb2RlZCB0ZXh0", $stream);
    }

}
