<?php

namespace Tests;

use ByJG\WebRequest\ContentDisposition;
use ByJG\WebRequest\FileNotFoundException;
use ByJG\WebRequest\MultiPartItem;
use ByJG\WebRequest\Psr7\MemoryStream;
use PHPUnit\Framework\TestCase;
use ByJG\Util\Uri;
use ByJG\WebRequest\Helper\RequestMultiPart;

class MultiPartItemTest extends TestCase
{

    public function testBuild1(): void
    {
        $stream = new MemoryStream();

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

        $this->assertEquals(implode("\r\n", $expected), $stream->getContents());
    }

    public function testBuild2(): void
    {
        $stream = new MemoryStream();

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

        $this->assertEquals(implode("\r\n", $expected), $stream->getContents());
    }

    public function testBuild3(): void
    {
        $stream = new MemoryStream();

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

        $this->assertEquals(implode("\r\n", $expected), $stream->getContents());
    }

    public function testBuild4(): void
    {
        $stream = new MemoryStream();

        $multiPartItem = new MultiPartItem("fieldname");
        $multiPartItem
            ->withContent("Lorem ipsum dolor sit amet")
            ->withContentType("text/html")
            ->withFilename("text.txt")
            ->withContentDisposition(ContentDisposition::FormData)
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

        $this->assertEquals(implode("\r\n", $expected), $stream->getContents());
    }

    /**
     * @throws FileNotFoundException
     */
    public function testBuild5(): void
    {
        $stream = new MemoryStream();

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

        $this->assertEquals(implode("\r\n", $expected), $stream->getContents());
    }

    public function testParseBodyReadsWhatRequestMultiPartBuilds(): void
    {
        // Binary content with line breaks, a NUL byte and a line that looks like a delimiter
        $png = "\x89PNG\r\n\x1a\n\0\r\n--not-the-boundary\r\nend";
        $request = RequestMultiPart::build(new Uri('http://localhost/upload'), 'POST', [
            new MultiPartItem('note', 'somenote'),
            new MultiPartItem('tags[]', 'a'),
            new MultiPartItem('tags[]', 'b'),
            new MultiPartItem('upfile', $png, 'file.txt', 'text/plain'),
            (new MultiPartItem('encoded', 'base64 content', 'b.txt', 'text/plain'))->withEncodedBase64(),
        ]);

        [$fields, $files] = MultiPartItem::parseBody((string)$request->getBody(), $request->getHeaderLine('Content-Type'));

        $this->assertSame(['note' => 'somenote', 'tags' => ['a', 'b']], $fields);
        $this->assertSame(['upfile', 'encoded'], array_keys($files));
        $this->assertSame($png, (string)$files['upfile']->getStream());
        $this->assertSame(strlen($png), $files['upfile']->getSize());
        $this->assertSame('file.txt', $files['upfile']->getClientFilename());
        $this->assertSame('text/plain', $files['upfile']->getClientMediaType());
        $this->assertSame('base64 content', (string)$files['encoded']->getStream());
    }

    public function testParseBodyWithLfLineEndingsAndQuotedBoundary(): void
    {
        $body = "--xyz\nContent-Disposition: form-data; name=\"name\"\n\nJohn\n--xyz--\n";

        [$fields, $files] = MultiPartItem::parseBody($body, 'multipart/form-data; boundary="xyz"');

        $this->assertSame(['name' => 'John'], $fields);
        $this->assertSame([], $files);
    }

    public function testParseBodyWithoutBoundary(): void
    {
        $this->assertSame([[], []], MultiPartItem::parseBody('anything', 'multipart/form-data'));
    }
}
