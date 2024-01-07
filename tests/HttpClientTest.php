<?php

namespace Test;

use ByJG\Util\Exception\MessageException;
use ByJG\Util\Exception\NetworkException;
use ByJG\Util\Exception\RequestException;
use ByJG\Util\Helper\RequestFormUrlEncoded;
use ByJG\Util\Helper\RequestJson;
use ByJG\Util\Helper\RequestMultiPart;
use ByJG\Util\HttpClient;
use ByJG\Util\MultiPartItem;
use ByJG\Util\ParseBody;
use ByJG\Util\Psr7\MemoryStream;
use ByJG\Util\Psr7\Request;
use ByJG\Util\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

class HttpClientTest extends TestCase
{

    protected string $BASE_URL_TEST;

    protected string $SERVER_TEST;
    protected string $REDIRECT_TEST;
    protected string $SOAP_TEST;

    /**
     * @var ?HttpClient
     */

    protected ?HttpClient $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = HttpClient::getInstance();

        $host = empty(getenv('HTTP_TEST_HOST')) ?  "localhost" : getenv('HTTP_TEST_HOST');
        $port = empty(getenv('HTTP_TEST_PORT')) ?  "8080" : getenv('HTTP_TEST_PORT');

        $this->BASE_URL_TEST = "$host:$port";

        $this->SERVER_TEST = "http://$host:$port/rest.php";
        $this->REDIRECT_TEST = "http://$host:$port/redirect.php";
        $this->SOAP_TEST = "http://$host:$port/soap.php";
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        $this->object = null;
    }

    /**
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     * @throws ClientExceptionInterface
     */
    public function testGetLastStatus()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST));
        $response = $this->object->sendRequest($request);
        $body = ParseBody::parse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("1.1", $response->getProtocolVersion());
        $this->assertFalse(isset($body["authinfo"]));
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testWithCredentials()
    {
        $uri = Uri::getInstanceFromString($this->SERVER_TEST)
            ->withUserInfo("user", "pass");

        $request = Request::getInstance($uri);

        $response = $this->object->sendRequest($request);
        $body = ParseBody::parse($response);

        $this->assertEquals("user:pass", $body["authinfo"]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testReferer()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST))
            ->withHeader("referer", "http://example.com/abc");

        $response = $this->object->sendRequest($request);
        $body = ParseBody::parse($response);
        $this->assertEquals('http://example.com/abc', $body["referer"]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testCustomHeader()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST))
            ->withHeader("X-Custom-Header", "Defined");

        $response = $this->object->sendRequest($request);
        $body = ParseBody::parse($response);
        $this->assertEquals('Defined', $body["custom_header"]);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testisFollowingLocation()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->REDIRECT_TEST));
        $this->object = HttpClient::getInstance()
            ->withNoFollowRedirect();
        $response = $this->object->sendRequest($request);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals("rest.php", $response->getHeaderLine("Location"));

        $this->object = HttpClient::getInstance();
        $response = $this->object->sendRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testGet()
    {
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage("Cannot set body with method GET");
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST))
            ->withBody(new MemoryStream("A"));

        $this->object->sendRequest($request);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testGet1()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST))
            ->withMethod("GET");
        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => null,
            'method' => 'GET',
            'query_string' => [],
            'post_string' => [],
            'payload' => ''
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testGet2()
    {
        $uri = Uri::getInstanceFromString($this->SERVER_TEST)
            ->withQuery(http_build_query(['param1' => 'value1', 'param2' => 'value2']));

        $request = Request::getInstance($uri);

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => null,
            'method' => 'GET',
            'query_string' => ['param1' => 'value1', 'param2' => 'value2'],
            'post_string' => [],
            'payload' => ''
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testGet3()
    {
        $uri = Uri::getInstanceFromString($this->SERVER_TEST)
            ->withQuery("just string");

        $request = Request::getInstance($uri);

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => null,
            'method' => 'GET',
            'query_string' => ['just_string' => ''],
            'post_string' => [],
            'payload' => ''
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testGet4()
    {
        $uri = Uri::getInstanceFromString($this->SERVER_TEST)
            ->withQuery('just_string=value1&just_string2=value2');

        $request = Request::getInstance($uri);

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => null,
            'method' => 'GET',
            'query_string' => ['just_string' => 'value1', 'just_string2' => 'value2'],
            'post_string' => [],
            'payload' => ''
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testPost1()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST))
            ->withMethod("POST");
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'POST',
            'query_string' => [],
            'post_string' => [],
            'payload' => ''
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testPost2()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), [
            'param1' => 'value1',
            'param2' => 'value2'
        ]);
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'POST',
            'query_string' => [],
            'post_string' => ['param1' => 'value1', 'param2' => 'value2'],
            'payload' => 'param1=value1&param2=value2'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testPost3()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), 'just_string');
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'POST',
            'query_string' => [],
            'post_string' => [
                'just_string' => ''
            ],
            'payload' => 'just_string'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testPost4()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), 'just_string=value1&just_string2=value2');
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'POST',
            'query_string' => [],
            'post_string' => ['just_string' => 'value1', 'just_string2' => 'value2'],
            'payload' => 'just_string=value1&just_string2=value2'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testPost5()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("extra=ok"), [
            'param' => 'value'
        ]);
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'POST',
            'query_string' => ['extra' => 'ok'],
            'post_string' => ['param' => 'value'],
            'payload' => 'param=value'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testPostPayload()
    {
        $request = RequestJson::build(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("extra=ok"),
            "POST",
            '{teste: "ok"}'
        );
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/json',
            'method' => 'POST',
            'query_string' => ['extra' => 'ok'],
            'post_string' => [],
            'payload' => '{teste: "ok"}'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testPut1()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST))
            ->withHeader("content-type",  'application/x-www-form-urlencoded')
            ->withMethod("PUT");
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'PUT',
            'query_string' => [],
            'post_string' => [],
            'payload' => ''
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testPut2()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), [
            'param1' => 'value1',
            'param2' => 'value2'
        ])->withMethod("PUT");
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'PUT',
            'query_string' => [],
            'post_string' => [],
            'payload' => 'param1=value1&param2=value2'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testPut3()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), 'just_string')
            ->withMethod("PUT");
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'PUT',
            'query_string' => [],
            'post_string' => [],
            'payload' => 'just_string'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testPut4()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), 'just_string=value1&just_string2=value2')
            ->withMethod("PUT");
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'PUT',
            'query_string' => [],
            'post_string' => [],
            'payload' => 'just_string=value1&just_string2=value2'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testPut5()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("extra=ok"), [
            'param' => 'value'
        ])->withMethod("PUT");
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'PUT',
            'query_string' => ['extra' => 'ok'],
            'post_string' => [],
            'payload' => 'param=value'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testPutPayload()
    {
        $request = RequestJson::build(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("extra=ok"),
            "PUT",
            '{teste: "ok"}'
        );
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/json',
            'method' => 'PUT',
            'query_string' => ['extra' => 'ok'],
            'post_string' => [],
            'payload' => '{teste: "ok"}'
        ];
        $this->assertEquals($expected, $result);
    }


    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testDelete1()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST))
            ->withHeader("content-type",  'application/x-www-form-urlencoded')
            ->withMethod("DELETE");
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'DELETE',
            'query_string' => [],
            'post_string' => [],
            'payload' => ''
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testDelete2()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), [
            'param1' => 'value1',
            'param2' => 'value2'
        ])->withMethod("DELETE");
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'DELETE',
            'query_string' => [],
            'post_string' => [],
            'payload' => 'param1=value1&param2=value2'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testDelete3()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), 'just_string')
            ->withMethod("DELETE");
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'DELETE',
            'query_string' => [],
            'post_string' => [],
            'payload' => 'just_string'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testDelete4()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), 'just_string=value1&just_string2=value2')
            ->withMethod("DELETE");
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'DELETE',
            'query_string' => [],
            'post_string' => [],
            'payload' => 'just_string=value1&just_string2=value2'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testDelete5()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("extra=ok"), [
            'param' => 'value'
        ])->withMethod("DELETE");
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/x-www-form-urlencoded',
            'method' => 'DELETE',
            'query_string' => ['extra' => 'ok'],
            'post_string' => [],
            'payload' => 'param=value'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testDeletePayload()
    {
        $request = RequestJson::build(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("extra=ok"),
            "DELETE",
            '{teste: "ok"}'
        );
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = [
            'content-type' => 'application/json',
            'method' => 'DELETE',
            'query_string' => ['extra' => 'ok'],
            'post_string' => [],
            'payload' => '{teste: "ok"}'
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testPostMultiPartForm()
    {
        $uploadFile = [];
        $uploadFile[] = new MultiPartItem('field1', 'value1');
        $uploadFile[] = new MultiPartItem(
            'field2',
            '{"key": "value2"}',
            'filename.json',
            'application/json; charset=UTF-8'
        );
        $uploadFile[] = new MultiPartItem('field3', 'value3');

        $request = RequestMultiPart::build(Uri::getInstanceFromString($this->SERVER_TEST),
            "POST",
            $uploadFile
        );
        

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);

        $this->assertStringContainsString('multipart/form-data; boundary=', $result['content-type']);
        $this->assertEquals('POST', $result['method']);
        $this->assertEquals([], $result['query_string']);
        $this->assertEquals(['field1' => 'value1', 'field3' => 'value3'], $result['post_string']);
        $this->assertEquals('', $result['payload']);
        $this->assertEquals(['field2' => [
            'name' => 'filename.json',
            'type' => 'application/json',
            'error' => 0,
            'size' => 17,
            'content' => "{\"key\": \"value2\"}"
        ] + (PHP_VERSION_ID >= 80100 ? ["full_path" => "filename.json"] :[])], $result['files']);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testWithCurlOption()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST));

        $this->object->withCurlOption(CURLOPT_NOBODY, 1);

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $expected = null;
        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testHead1()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST))
            ->withHeader( "Connection", "Keep-Alive")
            ->withMethod("HEAD");

        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);
        $this->assertEquals(null, $result);

        $this->assertNotEmpty($response->getHeaders());
    }

    /**
     * @throws ClientExceptionInterface
     * @throws MessageException
     * @throws NetworkException
     * @throws RequestException
     */
    public function testInvalid()
    {
        $this->expectException(NetworkException::class);
        $this->expectExceptionMessage("CURL - Could not resolve host: abc.def");

        $request = Request::getInstance(Uri::getInstanceFromString("http://abc.def"));
        $this->object->sendRequest($request);
    }

}
