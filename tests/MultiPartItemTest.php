<?php


use ByJG\Util\MultiPartItem;
use PHPUnit\Framework\TestCase;

class MultiPartItemTest extends TestCase
{

    public function testBuild1()
    {
        $stream = new \ByJG\Util\Psr7\MemoryStream();

        $multiPartItem = new MultiPartItem("fieldname", "Lorem ipsum dolor sit amet");

        $expected = [
            "--1234567890",
            "Content-Disposition: form-data; name=\"fieldname\";",
            "",
            "Lorem ipsum dolor sit amet",
            ""
        ];

        $multiPartItem->build($stream, "1234567890");
        $stream->rewind();

        $this->assertEquals(implode("\n", $expected), $stream->getContents());
    }

    public function testBuild2()
    {
        $stream = new \ByJG\Util\Psr7\MemoryStream();

        $multiPartItem = new MultiPartItem("fieldname", "Lorem ipsum dolor sit amet", "text.txt", "text/html");

        $expected = [
            "--1234567890",
            "Content-Disposition: form-data; name=\"fieldname\"; filename=\"text.txt\";",
            "Content-Type: text/html",
            "",
            "Lorem ipsum dolor sit amet",
            ""
        ];

        $multiPartItem->build($stream, "1234567890");
        $stream->rewind();

        $this->assertEquals(implode("\n", $expected), $stream->getContents());
    }

    public function testBuild3()
    {
        $stream = new \ByJG\Util\Psr7\MemoryStream();

        $multiPartItem = new MultiPartItem("fieldname");
        $multiPartItem
            ->withContent("Lorem ipsum dolor sit amet")
            ->withContentType("text/html")
            ->withFilename("text.txt");

        $expected = [
            "--1234567890",
            "Content-Disposition: form-data; name=\"fieldname\"; filename=\"text.txt\";",
            "Content-Type: text/html",
            "",
            "Lorem ipsum dolor sit amet",
            ""
        ];

        $multiPartItem->build($stream, "1234567890");
        $stream->rewind();

        $this->assertEquals(implode("\n", $expected), $stream->getContents());
    }

    public function testBuild4()
    {
        $stream = new \ByJG\Util\Psr7\MemoryStream();

        $multiPartItem = new MultiPartItem("fieldname");
        $multiPartItem
            ->withContent("Lorem ipsum dolor sit amet")
            ->withContentType("text/html")
            ->withFilename("text.txt")
            ->withContentDisposition("form-data")
            ->withEncodedBase64();

        $expected = [
            "--1234567890",
            "Content-Disposition: form-data; name=\"fieldname\"; filename=\"text.txt\";",
            "Content-Type: text/html",
            "Content-Transfer-Encoding: base64",
            "",
            "TG9yZW0gaXBzdW0gZG9sb3Igc2l0IGFtZXQ=",
            ""
        ];

        $multiPartItem->build($stream, "1234567890");
        $stream->rewind();

        $this->assertEquals(implode("\n", $expected), $stream->getContents());
    }

    public function testBuild5()
    {
        $stream = new \ByJG\Util\Psr7\MemoryStream();

        $multiPartItem = new MultiPartItem("fieldname");
        $multiPartItem
            ->loadFile(__DIR__ . "/file.txt")
            ->withContentType("text/html")
            ->withEncodedBase64();

        $expected = [
            "--1234567890",
            "Content-Disposition: form-data; name=\"fieldname\"; filename=\"file.txt\";",
            "Content-Type: text/html",
            "Content-Transfer-Encoding: base64",
            "",
            "TG9yZW0gaXBzdW0gZG9sb3Igc2l0IGFtZXQ=",
            ""
        ];

        $multiPartItem->build($stream, "1234567890");
        $stream->rewind();

        $this->assertEquals(implode("\n", $expected), $stream->getContents());
    }
}
