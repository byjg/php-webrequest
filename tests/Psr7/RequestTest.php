<?php

use ByJG\Util\Psr7\Request;
use ByJG\Util\Uri;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function uriProvider()
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
     * @param \ByJG\Util\Psr7\Request $request
     * @param string[] $expected
     */
    public function testGetRequestTarget($request, $expected)
    {
        $this->assertEquals($request->getRequestTarget(), $expected[1]);
    }

    /**
     * @dataProvider uriProvider
     * @param \ByJG\Util\Psr7\Request $request
     * @param string[] $expected
     */
    public function testWithRequestTarget($request, $expected)
    {
        $path = "/another" . rand(1000, 9000);
        $query = "query=" . rand(1000, 9000);
        $request = $request->withRequestTarget($path . "?" . $query);
        $this->assertEquals($path . "?" . $query, $request->getRequestTarget());
        $this->assertEquals($path, $request->getUri()->getPath());
        $this->assertEquals($query, $request->getUri()->getQuery());
    }

    public function testGetMethod()
    {
        $request = new Request(new Uri());
        $this->assertEquals("GET", $request->getMethod());
    }

    public function testWithMethod()
    {
        $request = new Request(new Uri());
        $methods = [ "GET", "HEAD", "POST", "PUT", "DELETE", "CONNECT", "OPTIONS", "TRACE", "PATCH" ];
        foreach ($methods as $method) {
            $expectedRequest = $request->withMethod(strtolower($method));
            $this->assertEquals($method, $expectedRequest->getMethod());
        }
    }

    public function testWithUri()
    {
        $uri = new Uri("http://somehost.com");
        $request  = new Request($uri);
        $this->assertEquals(['somehost.com'], $request->getHeader("host"));
        $this->assertEquals($uri, $request->getUri());

        $uri = new Uri("https://anotherhost.uk:9090/test");
        $request = $request->withUri($uri, false);
        $this->assertEquals(["anotherhost.uk:9090"], $request->getHeader("host"));
        $this->assertEquals($uri, $request->getUri());

        $uri = new Uri("https://shouldnot.me:1234/path");
        $request = $request->withUri($uri, true);
        $this->assertEquals(["anotherhost.uk:9090"], $request->getHeader("host"));
        $this->assertEquals($uri, $request->getUri());
    }

}