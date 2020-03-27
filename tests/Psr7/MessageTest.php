<?php

use ByJG\Util\Psr7\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    private $message;

    public function setUp()
    {
        $this->message = new Message();
    }

    public function tearDown()
    {
        $this->message = null;
    }

    public function headerDataProvider()
    {
        return [
            [
                ["mime-type", "text/plain"],
                ["Mime-Type" => ["text/plain"]]
            ],
            [
                ["MIME-TYPE", "text/plain"],
                ["Mime-Type" => ["text/plain"]]
            ],
            [
                ["accept-encoding", ["gzip", "deflate", "br"]],
                ["Accept-Encoding" => ["gzip", "deflate", "br"]]
            ]
        ];
    }

    /**
     * @dataProvider headerDataProvider
     * @param $header
     * @param $expected
     * @throws \ByJG\Util\Psr7\MessageException
     */
    public function testWithHeader($header, $expected)
    {
        $this->message->withHeader($header[0], $header[1]);
        $this->assertEquals($expected, $this->message->getHeaders());
    }

    /**
     * @dataProvider headerDataProvider
     * @param $header
     * @param $expected
     * @throws \ByJG\Util\Psr7\MessageException
     */
    public function testHasHeader($header, $expected)
    {
        $this->message->withHeader($header[0], $header[1]);
        $this->assertTrue($this->message->hasHeader($header[0]));
        $this->assertFalse($this->message->hasHeader($header[0] . "no"));
    }

    /**
     * @dataProvider headerDataProvider
     * @param $header
     * @param $expected
     * @throws \ByJG\Util\Psr7\MessageException
     */
    public function testGetHeader($header, $expected)
    {
        $this->message->withHeader($header[0], $header[1]);
        $expected = array_values($expected);
        $this->assertEquals($expected[0], $this->message->getHeader($header[0]));
    }

    public function testGetProtocolVersion()
    {
        $this->assertEquals("1.1", $this->message->getProtocolVersion());
    }

    /**
     * @dataProvider headerDataProvider
     * @param $header
     * @param $expected
     * @throws \ByJG\Util\Psr7\MessageException
     */
    public function testGetHeaderLine($header, $expected)
    {
        $this->message->withHeader($header[0], $header[1]);
        $expected = array_values($expected);
        $this->assertEquals(implode(",", $expected[0]), $this->message->getHeaderLine($header[0]));
    }

    /**
     * @dataProvider headerDataProvider
     * @param $header
     * @param $expected
     * @throws \ByJG\Util\Psr7\MessageException
     */
    public function testWithoutHeader($header, $expected)
    {
        $this->message
            ->withHeader("accept-language", "en-US,en")
            ->withHeader("cache-control", "max-age=0")
            ->withHeader($header[0], $header[1]);

        $this->message->withoutHeader("accept-language");

        $expected = array_merge(["Cache-Control" => ["max-age=0"]], $expected);
        $this->assertEquals($expected, $this->message->getHeaders());
    }

    /**
     * @dataProvider headerDataProvider
     * @param $header
     * @param $expected
     * @throws \ByJG\Util\Psr7\MessageException
     */
    public function testWithAddedHeader($header, $expected)
    {
        $this->message->withHeader($header[0], $header[1]);
        $this->message->withAddedHeader($header[0], "added");
        $this->message->withAddedHeader($header[0], ["more added", "extra"]);

        $expected = array_values($expected);
        $expected = array_merge($expected[0], ["added", "more added", "extra"]);
        $this->assertEquals($expected, $this->message->getHeader($header[0]));
    }

    public function testWithBody()
    {

    }

    public function testGetBody()
    {

    }

    public function testWithProtocolVersion()
    {
        $this->message->withProtocolVersion("1.0");
        $this->assertEquals("1.0", $this->message->getProtocolVersion());

        $this->message->withProtocolVersion("1.1");
        $this->assertEquals("1.1", $this->message->getProtocolVersion());
    }

    /**
     * @expectedException \ByJG\Util\Psr7\MessageException
     * @expectedExceptionMessage Invalid Protocol Version
     * @throws \ByJG\Util\Psr7\MessageException
     */
    public function testWithProtocolVersionInvalid()
    {
        $this->message->withProtocolVersion("3.0");
    }
}
