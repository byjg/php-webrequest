<?php

namespace Test\Psr7;

use ByJG\Util\Uri;
use ByJG\WebRequest\Factory\ServerRequestFactory;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\ServerRequest;
use ByJG\WebRequest\Psr7\UploadedFile;
use PHPUnit\Framework\TestCase;

class ServerRequestTest extends TestCase
{
    public function testSuperGlobal()
    {
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_COOKIE = ['test' => 'value'];

        try {
            $request = new ServerRequest();
            $this->assertEquals(['test' => 'value'], $request->getCookieParams());
            $this->assertEquals(['REQUEST_METHOD' => 'GET'], $request->getServerParams());
        } finally {
            unset($_SERVER);
            unset($_COOKIE);
        }
    }

    public function testGetServerParams()
    {
        $serverParams = ['REQUEST_METHOD' => 'GET'];
        $request = new ServerRequest(null, $serverParams);
        $this->assertEquals($serverParams, $request->getServerParams());
    }

    public function testGetCookieParams()
    {
        $cookieParams = ['test' => 'value'];
        $request = new ServerRequest(null, [], $cookieParams);
        $this->assertEquals($cookieParams, $request->getCookieParams());
    }

    public function testWithCookieParams()
    {
        $request = new ServerRequest();
        $newCookies = ['new' => 'cookie'];
        $newRequest = $request->withCookieParams($newCookies);
        $this->assertEquals($newCookies, $newRequest->getCookieParams());
    }

    public function testGetQueryParams()
    {
        $uri = Uri::getInstanceFromString('http://example.com?param1=value1');
        $request = new ServerRequest($uri);
        $this->assertEquals(['param1' => 'value1'], $request->getQueryParams());

        $request = new ServerRequest($uri, serverParams: ['QUERY_STRING' => 'param2=value2']);
        $this->assertEquals(['param1' => 'value1', 'param2' => 'value2'], $request->getQueryParams());
    }

    public function testWithQueryParams()
    {
        $request = new ServerRequest();
        $newQuery = ['param2' => 'value2'];
        $newRequest = $request->withQueryParams($newQuery);
        $this->assertEquals($newQuery, $newRequest->getQueryParams());
    }

    public function testWithUploadedFiles()
    {
        $request = new ServerRequest();
        $uploadedFiles = ['file1' => new UploadedFile('')];
        $newRequest = $request->withUploadedFiles($uploadedFiles);
        $this->assertEquals($uploadedFiles, $newRequest->getUploadedFiles());
    }

    public function testGetParsedBody()
    {
        $request = (new ServerRequest(null))
            ->withHeader('content-Type', 'application/x-www-form-urlencoded')
            ->withBody(new MemoryStream('key=value'));
        $this->assertEquals(['key' => 'value'], $request->getParsedBody());

        $request = (new ServerRequest(null))
            ->withHeader('content-Type', 'application/json')
            ->withBody(new MemoryStream('{"key2": "value2"}'));
        $this->assertEquals(['key2' => 'value2'], $request->getParsedBody());

        $request = (new ServerRequest(null))
            ->withHeader('content-Type', 'text/plain')
            ->withBody(new MemoryStream('plain text'));
        $this->assertEquals('plain text', $request->getParsedBody());
    }

    public function testWithParsedBody()
    {
        $request = new ServerRequest();
        $this->assertEquals('', $request->getBody()->getContents());
        $this->assertEquals('', $request->getParsedBody());

        $request = (new ServerRequest())
            ->withHeader('content-Type', 'application/x-www-form-urlencoded')
            ->withParsedBody(['key' => 'value']);
        $this->assertEquals('key=value', $request->getBody()->getContents());
        $this->assertEquals(['key' => 'value'], $request->getParsedBody());

        $request = (new ServerRequest())
            ->withHeader('content-Type', 'application/json')
            ->withParsedBody(['key2' => 'value2']);
        $this->assertEquals('{"key2":"value2"}', $request->getBody()->getContents());
        $this->assertEquals(['key2' => 'value2'], $request->getParsedBody());
    }

    public function testWithAttribute()
    {
        $request = new ServerRequest();
        $newRequest = $request->withAttribute('key', 'value');
        $this->assertEquals('value', $newRequest->getAttribute('key'));
    }

    public function testWithoutAttribute()
    {
        $request = new ServerRequest();
        $newRequest = $request->withAttribute('key', 'value');
        $this->assertEquals('value', $newRequest->getAttribute('key'));
        $newRequest = $newRequest->withoutAttribute('key');
        $this->assertNull($newRequest->getAttribute('key'));
    }

    public function testAllConfigurations()
    {
        $uri = Uri::getInstanceFromString('http://example.com?param1=value1');
        $serverParams = ['HOST' => 'localhost'];
        $cookieParams = ['test' => 'value'];
        $query = ['param2' => 'value2'];
        $uploadedFiles = ['file1' => new UploadedFile('')];
        $attributes = ['key' => 'value'];
        $body = new MemoryStream('key=value');
        $request = ServerRequestFactory::instance()
            ->createServerRequest('GET', $uri, $serverParams)
            ->withCookieParams($cookieParams)
            ->withQueryParams($query)
            ->withUploadedFiles($uploadedFiles)
            ->withAttribute('key', 'value')
            ->withBody($body);

        $this->assertEquals($uri, $request->getUri());
        $this->assertEquals(['HOST' => 'localhost', 'QUERY_STRING' => 'param2=value2'], $request->getServerParams());
        $this->assertEquals($cookieParams, $request->getCookieParams());
        $this->assertEquals(['param1' => 'value1', 'param2' => 'value2'], $request->getQueryParams());
        $this->assertEquals($uploadedFiles, $request->getUploadedFiles());
        $this->assertEquals($attributes, $request->getAttributes());
        $this->assertEquals($body->getContents(), $request->getBody()->getContents());
    }

    public function testAllConfigurationsFromGlobal()
    {
        try {
            $_SERVER = ['REQUEST_METHOD' => 'GET', 'HOST' => 'localhost'];
            $_COOKIE = ['test' => 'value'];
            $_GET = ['param1' => 'value1'];
            $_FILES = ['file1' => ['name' => 'file1', 'type' => '', 'tmp_name' => '/tmp/xyz', 'error' => 0, 'size' => 0]];
            $_REQUEST = ['key' => 'value'];
            $_POST = ['key' => 'value'];
            $_SERVER['QUERY_STRING'] = 'param2=value2';
            $body = new MemoryStream('key=value');
            file_put_contents('/tmp/xyz', 'file content');

            $request = ServerRequestFactory::instance()
                ->createServerRequest('GET', new Uri('http://example.com?param1=value1'))
                ->withAttribute('attr', 'valueattr')
                ->withBody($body);

            $this->assertEquals('GET', $request->getMethod());
            $this->assertEquals(['REQUEST_METHOD' => 'GET', 'HOST' => 'localhost', 'QUERY_STRING' => 'param2=value2'], $request->getServerParams());
            $this->assertEquals(['test' => 'value'], $request->getCookieParams());
            $this->assertEquals(['param1' => 'value1', 'param2' => 'value2'], $request->getQueryParams());
            $this->assertEquals(12, $request->getUploadedFiles()['file1']->getSize());
            $this->assertEquals(['attr' => 'valueattr'], $request->getAttributes());
            $this->assertEquals('key=value', $request->getBody()->getContents());
        } finally {
            unset($_SERVER);
            unset($_COOKIE);
            unset($_GET);
            unset($_FILES);
            unset($_REQUEST);
            unset($_POST);

            if (file_exists('/tmp/xyz')) {
                unlink('/tmp/xyz');
            }
        }
    }
}