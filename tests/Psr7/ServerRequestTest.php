<?php

namespace Tests\Psr7;

use ByJG\Util\Uri;
use ByJG\WebRequest\Exception\MessageException;
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
            $request = ServerRequest::fromGlobals();
            $this->assertEquals(['test' => 'value'], $request->getCookieParams());
            $this->assertEquals(['REQUEST_METHOD' => 'GET'], $request->getServerParams());
        } finally {
            $_SERVER = [];
            $_COOKIE = [];
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
        $uri = Uri::getInstance('http://example.com?param1=value1');
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

        // PSR-7 allows only null, an array or an object: text/plain has no parsed body
        $request = (new ServerRequest(null))
            ->withHeader('content-Type', 'text/plain')
            ->withBody(new MemoryStream('plain text'));
        $this->assertNull($request->getParsedBody());
        $this->expectException(MessageException::class);
        $this->expectExceptionMessage("The Content-Type 'text/plain' has no parsed body");
        $request->parseBody();
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

    /**
     * For a multipart/form-data request, getParsedBody() returns the fields of the body, as PHP reads them
     * into $_POST ("tags[]" is a list)
     */
    public function testGetParsedBodyMultipartReturnsPost()
    {
        $request = (new ServerRequest(null))
            ->withMethod('POST')
            ->withHeader('Content-Type', 'multipart/form-data; boundary=12345')
            ->withBody(new MemoryStream(
                "--12345\r\nContent-Disposition: form-data; name=\"name\"\r\n\r\nJohn\r\n"
                . "--12345\r\nContent-Disposition: form-data; name=\"tags[]\"\r\n\r\na\r\n"
                . "--12345\r\nContent-Disposition: form-data; name=\"tags[]\"\r\n\r\nb\r\n"
                . "--12345--\r\n"
            ));
        $this->assertEquals(['name' => 'John', 'tags' => ['a', 'b']], $request->getParsedBody());
    }

    /**
     * withParsedBody() for a content type it cannot encode (none, multipart...) keeps the body as is
     */
    private function requestWithBody(string $contentType, string $body): ServerRequest
    {
        return (new ServerRequest())
            ->withHeader('Content-Type', $contentType)
            ->withBody(new MemoryStream($body));
    }

    public function testGetParsedBodyXml()
    {
        $request = $this->requestWithBody(
            'application/xml; charset=utf-8',
            '<root><name>John</name><tags><item>a</item><item>b</item></tags></root>'
        );
        $this->assertEquals(['name' => 'John', 'tags' => ['a', 'b']], $request->getParsedBody());

        // text/xml and a +xml suffix are XML too
        $this->assertEquals(['name' => 'John'], $this->requestWithBody('text/xml', '<root><name>John</name></root>')->getParsedBody());
        $this->assertEquals(['title' => 'Error'], $this->requestWithBody('application/problem+xml', '<problem><title>Error</title></problem>')->getParsedBody());
    }

    public function testGetParsedBodyYaml()
    {
        $request = $this->requestWithBody('application/yaml', "name: John\ntags:\n  - a\n  - b\n");
        $this->assertEquals(['name' => 'John', 'tags' => ['a', 'b']], $request->getParsedBody());

        $this->assertEquals(['name' => 'John'], $this->requestWithBody('text/x-yaml', 'name: John')->getParsedBody());
    }

    public function testGetParsedBodyCsv()
    {
        $request = $this->requestWithBody('text/csv', "name,age\r\nJohn,30\r\nJane,25\r\n");
        $this->assertEquals([['name' => 'John', 'age' => '30'], ['name' => 'Jane', 'age' => '25']], $request->getParsedBody());
    }

    public function testGetParsedBodyJsonSuffix()
    {
        $request = $this->requestWithBody('application/vnd.api+json', '{"data": {"id": "1"}}');
        $this->assertEquals(['data' => ['id' => '1']], $request->getParsedBody());
    }

    /**
     * Parsing reads the body from the start and leaves it to be read again
     */
    public function testGetParsedBodyKeepsTheBody()
    {
        $request = $this->requestWithBody('application/json', '{"key": "value"}');
        $request->getBody()->getContents();

        $this->assertEquals(['key' => 'value'], $request->getParsedBody());
        $this->assertEquals('{"key": "value"}', (string)$request->getBody());
    }

    /**
     * A parsed body given to withParsedBody(), null too, is not replaced by parsing the body
     */
    public function testWithParsedBodyNullIsKept()
    {
        $request = $this->requestWithBody('multipart/form-data; boundary=12345', "--12345\r\nContent-Disposition: form-data; name=\"name\"\r\n\r\nJohn\r\n--12345--\r\n");
        $this->assertEquals(['name' => 'John'], $request->getParsedBody());
        $this->assertNull($request->withParsedBody(null)->getParsedBody());
    }

    public function testWithParsedBodyWritesXml()
    {
        $request = (new ServerRequest())
            ->withHeader('Content-Type', 'application/xml')
            ->withParsedBody(['name' => 'John', 'tags' => ['a', 'b']]);
        $this->assertEquals(
            "<?xml version=\"1.0\"?>\n<root><name>John</name><tags><item>a</item><item>b</item></tags></root>\n",
            $request->getBody()->getContents()
        );
        $this->assertEquals(['name' => 'John', 'tags' => ['a', 'b']], $request->getParsedBody());
    }

    public function testWithParsedBodyWritesYaml()
    {
        $request = (new ServerRequest())
            ->withHeader('Content-Type', 'application/yaml')
            ->withParsedBody(['name' => 'John', 'tags' => ['a', 'b']]);
        $this->assertEquals("name: John\ntags:\n  - a\n  - b\n", $request->getBody()->getContents());
    }

    public function testWithParsedBodyWritesCsv()
    {
        $request = (new ServerRequest())
            ->withHeader('Content-Type', 'text/csv')
            ->withParsedBody([['name' => 'John', 'age' => '30'], ['name' => 'Jane', 'age' => '25']]);
        $this->assertEquals("name,age\nJohn,30\nJane,25\n", $request->getBody()->getContents());
    }

    public static function unparsedBodyProvider(): array
    {
        return [
            'octet-stream' => ['application/octet-stream', 'raw bytes', "The Content-Type 'application/octet-stream' has no parsed body"],
            'no Content-Type' => ['', 'something', "The Content-Type '' has no parsed body"],
            'invalid JSON' => ['application/json', '{"key": ', 'The body is not valid json'],
            'JSON scalar' => ['application/json', '"text"', 'The json body is not an array or an object'],
            'YAML scalar' => ['application/yaml', 'abc', 'The yaml body is not an array or an object'],
            'invalid XML' => ['application/xml', '<root><name>', 'The body is not valid xml'],
            'invalid YAML' => ['application/yaml', "key: [a, b", 'The body is not valid yaml'],
        ];
    }

    /**
     * parseBody() says why there is no parsed body; getParsedBody() returns null (PSR-7)
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('unparsedBodyProvider')]
    public function testParseBodyExplainsWhyThereIsNoParsedBody(string $contentType, string $body, string $message)
    {
        $request = $this->requestWithBody($contentType, $body);
        $this->assertNull($request->getParsedBody());

        $this->expectException(MessageException::class);
        $this->expectExceptionMessage($message);
        $request->parseBody();
    }

    /**
     * The body is the one received: $_POST as the parsed body does not rewrite a urlencoded body
     */
    public function testFromGlobalsKeepsTheReceivedFormBody()
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/form',
            'HTTP_HOST' => 'localhost',
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ];
        $_POST = ['a' => '1', 'tags' => ['x', 'y']];
        try {
            $request = ServerRequest::fromGlobals('a=1&tags[]=x&tags[]=y');
            $this->assertEquals('a=1&tags[]=x&tags[]=y', (string)$request->getBody());
            $this->assertEquals(['a' => '1', 'tags' => ['x', 'y']], $request->getParsedBody());
        } finally {
            $_SERVER = [];
            $_POST = [];
        }
    }

    /**
     * A YAML scalar from the client is an invalid argument, with the serializer's TypeError as the previous
     */
    public function testParseBodyYamlScalarIsAnInvalidArgument()
    {
        try {
            $this->requestWithBody('application/yaml', 'abc')->parseBody();
            $this->fail('A YAML scalar has no parsed body');
        } catch (\InvalidArgumentException $exception) {
            $this->assertInstanceOf(MessageException::class, $exception);
            $this->assertInstanceOf(\TypeError::class, $exception->getPrevious());
        }
    }

    /**
     * Under php-fpm php://input is empty for multipart: the fields come from $_POST
     */
    public function testFromGlobalsMultipartFieldsFromPost()
    {
        $_SERVER = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/form',
            'HTTP_HOST' => 'localhost',
            'CONTENT_TYPE' => 'multipart/form-data; boundary=12345',
        ];
        $_POST = ['name' => 'John', 'tags' => ['a', 'b']];
        try {
            $request = ServerRequest::fromGlobals('');
            $this->assertEquals(['name' => 'John', 'tags' => ['a', 'b']], $request->getParsedBody());
        } finally {
            $_SERVER = [];
            $_POST = [];
        }
    }

    public function testWithParsedBodyKeepsTheBodyForOtherContentTypes()
    {
        $request = (new ServerRequest())->withParsedBody(['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $request->getParsedBody());
        $this->assertEquals('', $request->getBody()->getContents());

        $request = (new ServerRequest())
            ->withHeader('Content-Type', 'multipart/form-data; boundary=12345')
            ->withBody(new MemoryStream('raw multipart'))
            ->withParsedBody(['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $request->getParsedBody());
        $this->assertEquals('raw multipart', $request->getBody()->getContents());
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
        $uri = Uri::getInstance('http://example.com?param1=value1');
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

            $request = ServerRequest::fromGlobals($body)
                ->withUri(new Uri('http://example.com?param1=value1'))
                ->withAttribute('attr', 'valueattr');

            $this->assertEquals('GET', $request->getMethod());
            $this->assertEquals(['REQUEST_METHOD' => 'GET', 'HOST' => 'localhost', 'QUERY_STRING' => 'param2=value2'], $request->getServerParams());
            $this->assertEquals(['test' => 'value'], $request->getCookieParams());
            $this->assertEquals(['param1' => 'value1', 'param2' => 'value2'], $request->getQueryParams());
            $this->assertEquals(12, $request->getUploadedFiles()['file1']->getSize());
            $this->assertEquals(['attr' => 'valueattr'], $request->getAttributes());
            $this->assertEquals('key=value', $request->getBody()->getContents());
        } finally {
            $_SERVER = [];
            $_COOKIE = [];
            $_GET = [];
            $_FILES = [];
            $_REQUEST = [];
            $_POST = [];

            if (file_exists('/tmp/xyz')) {
                unlink('/tmp/xyz');
            }
        }
    }

    public function testDoesNotReadTheGlobals()
    {
        $_SERVER = ['REQUEST_METHOD' => 'GET'];
        $_COOKIE = ['test' => 'value'];
        $_FILES = ['file1' => ['name' => 'file1', 'type' => '', 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0]];
        $_POST = ['key' => 'value'];

        try {
            $request = new ServerRequest();
            $this->assertEquals([], $request->getServerParams());
            $this->assertEquals([], $request->getCookieParams());
            $this->assertEquals([], $request->getUploadedFiles());
            $this->assertNull($request->getParsedBody());
        } finally {
            $_SERVER = [];
            $_COOKIE = [];
            $_FILES = [];
            $_POST = [];
        }
    }

    public function testFromGlobals()
    {
        $tmpName = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($tmpName, 'file content');
        try {
            $_SERVER = [
                'REQUEST_METHOD' => 'POST',
                'HTTPS' => 'on',
                'HTTP_HOST' => 'example.com:8443',
                'REQUEST_URI' => '/form?param1=value1',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_X_CUSTOM_HEADER' => 'Defined',
                'REMOTE_ADDR' => '10.0.0.5',
            ];
            $_COOKIE = ['test' => 'value'];
            $_FILES = ['file1' => ['name' => 'file1.txt', 'type' => 'text/plain', 'tmp_name' => $tmpName, 'error' => UPLOAD_ERR_OK, 'size' => 12]];
            $_POST = ['key' => 'value'];

            $request = ServerRequest::fromGlobals('key=value');

            $this->assertEquals('POST', $request->getMethod());
            $this->assertEquals('https://example.com:8443/form?param1=value1', (string)$request->getUri());
            $this->assertEquals(['example.com:8443'], $request->getHeader('Host'));
            $this->assertEquals(['application/x-www-form-urlencoded'], $request->getHeader('Content-Type'));
            $this->assertEquals(['Defined'], $request->getHeader('X-Custom-Header'));
            $this->assertEquals($_SERVER, $request->getServerParams());
            $this->assertEquals(['test' => 'value'], $request->getCookieParams());
            $this->assertEquals(['param1' => 'value1'], $request->getQueryParams());
            $this->assertEquals('file content', (string)$request->getUploadedFiles()['file1']->getStream());
            $this->assertEquals(['key' => 'value'], $request->getParsedBody());
            $this->assertEquals('key=value', (string)$request->getBody());
        } finally {
            $_SERVER = [];
            $_COOKIE = [];
            $_FILES = [];
            $_POST = [];
            unlink($tmpName);
        }
    }

    public function testFromGlobalsUsesTheStreamGiven()
    {
        $_SERVER = ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/'];
        try {
            $body = new MemoryStream('{"a":1}');
            $request = ServerRequest::fromGlobals($body);
            $this->assertSame($body, $request->getBody());
            $this->assertEquals('http://localhost/', (string)$request->getUri());
        } finally {
            $_SERVER = [];
        }
    }

    public function testFromGlobalsReadsPhpInput()
    {
        // php://input is empty in the CLI; the body is still a readable, seekable stream
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'];
        try {
            $body = ServerRequest::fromGlobals()->getBody();
            $this->assertTrue($body->isSeekable());
            $this->assertSame('', (string)$body);
        } finally {
            $_SERVER = [];
        }
    }

    /**
     * PHP fills $_POST only for a POST of a form, so only then is it the parsed body (PSR-7)
     */
    public function testFromGlobalsParsedBodyIsPostOnlyForAFormPost()
    {
        $_POST = ['key' => 'value'];
        try {
            $_SERVER = ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json', 'REQUEST_URI' => '/'];
            $this->assertNull(ServerRequest::fromGlobals('')->getParsedBody());

            // Not a POST: the fields come from the body, not from $_POST
            $_SERVER = ['REQUEST_METHOD' => 'PUT', 'CONTENT_TYPE' => 'application/x-www-form-urlencoded', 'REQUEST_URI' => '/'];
            $this->assertEquals(['other' => '1'], ServerRequest::fromGlobals('other=1')->getParsedBody());

            $_SERVER = ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'multipart/form-data; boundary=12345', 'REQUEST_URI' => '/'];
            $this->assertEquals(['key' => 'value'], ServerRequest::fromGlobals('')->getParsedBody());
        } finally {
            $_SERVER = [];
            $_POST = [];
        }
    }
}
