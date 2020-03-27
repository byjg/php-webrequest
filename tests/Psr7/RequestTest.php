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
        $target = "/another" . rand(1000, 9000) . "?query=" . rand(1000, 9000);
        $request->withRequestTarget($target);
        $this->assertEquals($target, $request->getRequestTarget());
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
            $request->withMethod(strtolower($method));
            $this->assertEquals($method, $request->getMethod());
        }
    }

    public function testGetUri()
    {

    }

    public function testWithUri()
    {
        $request  = new Request(new Uri("http://somehost.com"));
        $this->assertEquals("somehost.com:80", $request->getHeader("host"));

        $uri = new Uri("https://anotherhost.uk:9090/test");
        $request->withUri($uri, false);
        $this->assertEquals("anotherhost.uk:9090", $request->getHeader("host"));

        $uri = new Uri("https://shouldnot.me:1234/path");
        $request->withUri($uri, true);
        $this->assertEquals("anotherhost.uk:9090", $request->getHeader("host"));
    }

}