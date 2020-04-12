<?php

use ByJG\Util\Helper\RequestFormUrlEncoded;
use ByJG\Util\Helper\RequestJson;
use ByJG\Util\Helper\RequestMultiPart;
use ByJG\Util\MultiPartItem;
use ByJG\Util\HttpClient;
use ByJG\Util\ParseBody;
use ByJG\Util\Psr7\Request;
use ByJG\Util\Uri;
use MintWare\Streams\MemoryStream;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{

    const SERVER_TEST = 'http://localhost:8080/rest.php';
    const REDIRECT_TEST = 'http://localhost:8080/redirect.php';
    const SOAP_TEST = 'http://localhost:8080/soap.php';

    /**
     * @var HttpClient
     */

    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $request = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST));
        $this->object = HttpClient::getInstance($request);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        $this->object = null;
    }

    public function testGetLastStatus()
    {
        $response = $this->object->send();
        $body = ParseBody::parse($response);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("1.1", $response->getProtocolVersion());
        $this->assertFalse(isset($body["authinfo"]));
    }

    public function testWithCredentials()
    {
        $request = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST));
        $request
            ->getUri()
            ->withUserInfo("user", "pass");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();
        $body = ParseBody::parse($response);

        $this->assertEquals("user:pass", $body["authinfo"]);
    }

    public function testReferer()
    {
        $request = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST))
            ->withHeader("referer", "http://example.com/abc");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();
        $body = ParseBody::parse($response);
        $this->assertEquals('http://example.com/abc', $body["referer"]);
    }

    public function testCustomHeader()
    {
        $request = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST));
        $this->object = HttpClient::getInstance($request);

        $request = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST))
            ->withHeader("X-Custom-Header", "Defined");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();
        $body = ParseBody::parse($response);
        $this->assertEquals('Defined', $body["custom_header"]);
    }

    public function testisFollowingLocation()
    {
        $request = Request::getInstance(Uri::getInstanceFromString(self::REDIRECT_TEST));
        $this->object = HttpClient::getInstance($request)
            ->withNoFollowRedirect();
        $response = $this->object->send();
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals("rest.php", $response->getHeaderLine("Location"));

        $this->object = HttpClient::getInstance($request);
        $response = $this->object->send();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGet1()
    {
        $response = $this->object->send();

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

    public function testGet2()
    {
        $request = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST));
        $request
            ->getUri()
            ->withQuery(http_build_query(['param1' => 'value1', 'param2' => 'value2']));
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testGet3()
    {
        $request = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST));
        $request
            ->getUri()
            ->withQuery("just string");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testGet4()
    {
        $request = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST));
        $request
            ->getUri()
            ->withQuery('just_string=value1&just_string2=value2');
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testPost1()
    {
        $request = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST))
            ->withMethod("POST");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testPost2()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString(self::SERVER_TEST), [
            'param1' => 'value1',
            'param2' => 'value2'
        ]);
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testPost3()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString(self::SERVER_TEST), 'just_string');
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testPost4()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString(self::SERVER_TEST), 'just_string=value1&just_string2=value2');
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testPost5()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString(self::SERVER_TEST)->withQuery("extra=ok"), [
            'param' => 'value'
        ]);
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testPostPayload()
    {
        $request = RequestJson::build(Uri::getInstanceFromString(self::SERVER_TEST)->withQuery("extra=ok"),
            "POST",
            '{teste: "ok"}'
        );
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testPut1()
    {
        $request = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST))
            ->withHeader("content-type",  'application/x-www-form-urlencoded')
            ->withMethod("PUT");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testPut2()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString(self::SERVER_TEST), [
            'param1' => 'value1',
            'param2' => 'value2'
        ])->withMethod("PUT");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testPut3()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString(self::SERVER_TEST), 'just_string')
            ->withMethod("PUT");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testPut4()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString(self::SERVER_TEST), 'just_string=value1&just_string2=value2')
            ->withMethod("PUT");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testPut5()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString(self::SERVER_TEST)->withQuery("extra=ok"), [
            'param' => 'value'
        ])->withMethod("PUT");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testPutPayload()
    {
        $request = RequestJson::build(Uri::getInstanceFromString(self::SERVER_TEST)->withQuery("extra=ok"),
            "PUT",
            '{teste: "ok"}'
        );
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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


    public function testDelete1()
    {
        $request = Request::getInstance(Uri::getInstanceFromString(self::SERVER_TEST))
            ->withHeader("content-type",  'application/x-www-form-urlencoded')
            ->withMethod("DELETE");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testDelete2()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString(self::SERVER_TEST), [
            'param1' => 'value1',
            'param2' => 'value2'
        ])->withMethod("DELETE");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testDelete3()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString(self::SERVER_TEST), 'just_string')
            ->withMethod("DELETE");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testDelete4()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString(self::SERVER_TEST), 'just_string=value1&just_string2=value2')
            ->withMethod("DELETE");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testDelete5()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString(self::SERVER_TEST)->withQuery("extra=ok"), [
            'param' => 'value'
        ])->withMethod("DELETE");
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

    public function testDeletePayload()
    {
        $request = RequestJson::build(Uri::getInstanceFromString(self::SERVER_TEST)->withQuery("extra=ok"),
            "DELETE",
            '{teste: "ok"}'
        );
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

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

        $request = RequestMultiPart::build(Uri::getInstanceFromString(self::SERVER_TEST),
            "POST",
            $uploadFile
        );
        $this->object = HttpClient::getInstance($request);

        $response = $this->object->send();

        $this->assertEquals(200, $response->getStatusCode());
        $result = ParseBody::parse($response);

        unset($result['files']['field2']['tmp_name']);

        $this->assertContains('multipart/form-data; boundary=boundary-', $result['content-type']);
        $this->assertEquals('POST', $result['method']);
        $this->assertEquals([], $result['query_string']);
        $this->assertEquals(['field1' => 'value1', 'field3' => 'value3'], $result['post_string']);
        $this->assertEquals('', $result['payload']);
        $this->assertEquals(['field2' => [
            'name' => 'filename.json',
            'type' => 'application/json',
            'error' => 0,
            'size' => 17
        ]], $result['files']);
    }
}

/*


public function testSetCurlOption()
{
    $this->object->setCurlOption(CURLOPT_NOBODY, true);
    $this->assertTrue($this->object->getCurlOption(CURLOPT_NOBODY));
}









public function testCurlException()
{
    $this->object = new WebRequest('http://laloiuyakkkall.iiiuqq/');

    $this->object->get();
}

public function testSoap()
{
    $this->object = new WebRequest(self::SOAP_TEST);
    $resutl = $this->object->soapCall('test', ['param1' => 'teste', 'param2' => 1]);
    $this->assertEquals("teste - 1", $resutl);
    $resutl = $this->object->soapCall('test', ['param1' => 'another call', 'param2' => 2018]);
    $this->assertEquals("another call - 2018", $resutl);
}

public function testSoapFail()
{
    $this->object = new WebRequest(self::SERVER_TEST);
    $this->object->soapCall('test', ['param1' => 'teste', 'param2' => 1]);
}
}
*/