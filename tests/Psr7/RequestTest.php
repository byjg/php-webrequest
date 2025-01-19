<?php

namespace Test\Psr7;

use ByJG\Util\Uri;
use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\Factory\RequestFactory;
use ByJG\WebRequest\HttpMethod;
use ByJG\WebRequest\Psr7\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    /**
     * @throws MessageException
     *
     * @return (Request|string[])[][]
     */
    public function uriProvider(): array
    {
        return [
            [
                new Request(new Uri("https://byjg.com")),
                [
                    "byjg.com:80", ""
                ]
            ],
            [
                new Request(new Uri("https://byjg.com.br:8080")),
                [
                    "byjg.com.br:8080", ""
                ]
            ],
            [
                new Request(new Uri("https://byjg.com/full/path")),
                [
                    "byjg.com:80", "/full/path"
                ]
            ],
            [
                new Request(new Uri("https://byjg.com/full/path?query=90&a=1")),
                [
                    "byjg.com:80", "/full/path?query=90&a=1"
                ]
            ],
            [
                new Request(new Uri("https://byjg.com/full/path#test")),
                [
                    "byjg.com:80", "/full/path"
                ]
            ],
        ];
    }

    /**
     * @dataProvider uriProvider
     *
     * @param Request $request
     * @param string[] $expected
     */
    public function testGetRequestTarget($request, $expected): void
    {
        $this->assertEquals($request->getRequestTarget(), $expected[1]);
    }

    /**
     * @dataProvider uriProvider
     *
     * @param Request $request
     * @param string[] $expected
     *
     * @throws MessageException
     */
    public function testWithRequestTarget($request, $expected): void
    {
        $path = "/another" . rand(1000, 9000);
        $query = "query=" . rand(1000, 9000);
        $request = $request->withRequestTarget($path . "?" . $query);
        $this->assertEquals($path . "?" . $query, $request->getRequestTarget());
        $this->assertEquals($path, $request->getUri()->getPath());
        $this->assertEquals($query, $request->getUri()->getQuery());
    }

    /**
     * @throws MessageException
     */
    public function testGetMethod(): void
    {
        $request = new Request(new Uri());
        $this->assertEquals("GET", $request->getMethod());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testWithMethod(): void
    {
        $request = new Request(new Uri());
        $methods = HttpMethod::cases();
        foreach ($methods as $method) {
            $expectedRequest = $request->withMethod($method);
            $this->assertEquals($method->value, $expectedRequest->getMethod());
        }

        foreach ($methods as $method) {
            $request = RequestFactory::instance()->createRequest($method, new Uri('http://localhost'));

            $this->assertEquals($method->value, $request->getMethod());
            $this->assertEquals(new Uri('http://localhost'),$request->getUri());
        }


    }

    /**
     * @throws MessageException
     */
    public function testWithUri(): void
    {
        $uri = new Uri("http://somehost.com");
        $request  = new Request($uri);
        $this->assertEquals(['somehost.com'], $request->getHeader("host"));
        $this->assertEquals($uri, $request->getUri());

        $uri = new Uri("https://anotherhost.uk:9090/test");
        $request = $request->withUri($uri);
        $this->assertEquals(["anotherhost.uk:9090"], $request->getHeader("host"));
        $this->assertEquals($uri, $request->getUri());

        $uri = new Uri("https://shouldnot.me:1234/path");
        $request = $request->withUri($uri, true);
        $this->assertEquals(["anotherhost.uk:9090"], $request->getHeader("host"));
        $this->assertEquals($uri, $request->getUri());
    }

}