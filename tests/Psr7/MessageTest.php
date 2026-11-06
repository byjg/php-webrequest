<?php

namespace Tests\Psr7;

use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Message;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    private $message;

    #[\Override]
    public function setUp(): void
    {
        $this->message = new Message();
    }

    #[\Override]
    public function tearDown(): void
    {
        $this->message = null;
    }

    /**
     * @return (string|string[])[][][]
     */
    public static function headerDataProvider(): array
    {
        return [
            [
                ["mime-type", "text/plain"],
                ["mime-type" => ["text/plain"]]
            ],
            [
                ["MIME-TYPE", "text/plain"],
                ["MIME-TYPE" => ["text/plain"]]
            ],
            [
                ["accept-encoding", ["gzip", "deflate", "br"]],
                ["accept-encoding" => ["gzip", "deflate", "br"]]
            ]
        ];
    }

    #[DataProvider('headerDataProvider')]
    public function testWithHeader($header, $expected): void
    {
        $message = $this->message->withHeader($header[0], $header[1]);
        $this->assertEquals($expected, $message->getHeaders());
    }

    #[DataProvider('headerDataProvider')]
    public function testHasHeader($header, $expected): void
    {
        $message = $this->message->withHeader($header[0], $header[1]);
        $this->assertTrue($message->hasHeader($header[0]));
        $this->assertFalse($message->hasHeader($header[0] . "no"));
    }

    #[DataProvider('headerDataProvider')]
    public function testGetHeader($header, $expected): void
    {
        $message = $this->message->withHeader($header[0], $header[1]);
        $expected = array_values($expected);
        $this->assertEquals($expected[0], $message->getHeader($header[0]));
    }

    public function testGetProtocolVersion(): void
    {
        $this->assertEquals("1.1", $this->message->getProtocolVersion());
    }

    #[DataProvider('headerDataProvider')]
    public function testGetHeaderLine($header, $expected): void
    {
        $message = $this->message->withHeader($header[0], $header[1]);
        $expected = array_values($expected);
        $this->assertEquals(implode(",", $expected[0]), $message->getHeaderLine($header[0]));
    }

    #[DataProvider('headerDataProvider')]
    public function testWithoutHeader($header, $expected): void
    {
        $message = $this->message
            ->withHeader("accept-language", "en-US,en")
            ->withHeader("cache-control", "max-age=0")
            ->withHeader($header[0], $header[1]);

        $message = $message->withoutHeader("accept-language");

        $expected = array_merge(["cache-control" => ["max-age=0"]], $expected);
        $this->assertEquals($expected, $message->getHeaders());
    }

    #[DataProvider('headerDataProvider')]
    public function testWithAddedHeader($header, $expected): void
    {
        $message = $this->message->withHeader($header[0], $header[1])
            ->withAddedHeader($header[0], "added")
            ->withAddedHeader($header[0], ["more added", "extra"]);

        $expected = array_values($expected);
        $expected = array_merge($expected[0], ["added", "more added", "extra"]);
        $this->assertEquals($expected, $message->getHeader($header[0]));
    }

    public function testWithBody(): void
    {
        $stream = new MemoryStream("<html>ok</html>");
        $message = $this->message->withBody($stream);
        $this->assertEquals("<html>ok</html>", $message->getBody());
    }

    /**
     * @throws MessageException
     */
    public function testWithProtocolVersion(): void
    {
        $message = $this->message->withProtocolVersion("1.0");
        $this->assertEquals("1.0", $message->getProtocolVersion());

        $message = $message->withProtocolVersion("1.1");
        $this->assertEquals("1.1", $message->getProtocolVersion());
    }

    /**
     * @throws MessageException
     */
    public function testWithProtocolVersionInvalid(): void
    {
        $this->expectException(MessageException::class);
        $this->expectExceptionMessage("Invalid Protocol Version");
        $this->message->withProtocolVersion("3.0");
    }

    public function testWithAddedHeaderKeepsTheFirstName(): void
    {
        $message = $this->message
            ->withAddedHeader("content-type", "text/html")
            ->withAddedHeader("Content-Type", "text/plain");
        $this->assertEquals(["content-type" => ["text/html", "text/plain"]], $message->getHeaders());

        // withHeader() replaces the values and the name
        $message = $message->withHeader("CONTENT-TYPE", "application/json");
        $this->assertEquals(["CONTENT-TYPE" => ["application/json"]], $message->getHeaders());
    }

    public function testHeaderValueIsTrimmedAndListKeysDropped(): void
    {
        $message = $this->message->withHeader("x-foo", ["a" => " bar ", "b" => 10]);
        $this->assertSame(["bar", "10"], $message->getHeader("X-Foo"));
    }

    /**
     * @return array<array{mixed, mixed}>
     */
    public static function invalidHeaderProvider(): array
    {
        return [
            "empty name" => ["", "value"],
            "name with a space" => ["x foo", "value"],
            "empty array" => ["x-foo", []],
            "boolean" => ["x-foo", false],
            "object" => ["x-foo", new \stdClass()],
            "header injection" => ["x-foo", "value\r\nSet-Cookie: x=y"],
        ];
    }

    #[DataProvider('invalidHeaderProvider')]
    public function testInvalidHeader(string $name, mixed $value): void
    {
        $this->expectException(MessageException::class);
        $this->message->withHeader($name, $value);
    }

    public function testWithHeadersIsWithHeaderForEachOne(): void
    {
        $base = $this->message->withHeader('Accept', 'text/html')->withHeader('X-Keep', 'kept');
        $headers = [
            'ACCEPT' => 'application/json',
            'Authorization' => ['Bearer abc'],
            'Accept-Encoding' => ['gzip', ' deflate '],
            'X-Number' => 10,
        ];

        $chained = $base;
        foreach ($headers as $name => $value) {
            $chained = $chained->withHeader($name, $value);
        }
        $bulk = $base->withHeaders($headers);

        $this->assertSame($chained->getHeaders(), $bulk->getHeaders());
        $this->assertSame(
            ['X-Keep' => ['kept'], 'ACCEPT' => ['application/json'], 'Authorization' => ['Bearer abc'],
                'Accept-Encoding' => ['gzip', 'deflate'], 'X-Number' => ['10']],
            $bulk->getHeaders()
        );
        $this->assertSame('application/json', $bulk->getHeaderLine('accept'));

        // Immutable: the message it came from is not changed
        $this->assertSame(['Accept' => ['text/html'], 'X-Keep' => ['kept']], $base->getHeaders());
        $this->assertNotSame($base, $bulk);
    }

    public function testWithHeadersOfNoHeaders(): void
    {
        $base = $this->message->withHeader('Accept', 'text/html');
        $this->assertSame($base->getHeaders(), $base->withHeaders([])->getHeaders());
    }

    #[DataProvider('invalidHeaderProvider')]
    public function testWithHeadersInvalidHeader(string $name, mixed $value): void
    {
        $this->expectException(MessageException::class);
        $this->message->withHeaders(['X-Valid' => 'ok', $name => $value]);
    }
}
