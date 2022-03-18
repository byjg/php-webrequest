<?php

use ByJG\Util\MultiPartItem;
use ByJG\Util\WebRequest;
use PHPUnit\Framework\TestCase;

class WebRequestTest extends TestCase
{

    protected $BASE_URL_TEST;

    protected $SERVER_TEST;
    protected $REDIRECT_TEST;
    protected $SOAP_TEST;

    /**
     * @var WebRequest
     */

    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $host = empty(getenv('HTTP_TEST_HOST')) ?  "localhost" : getenv('HTTP_TEST_HOST');
        $port = empty(getenv('HTTP_TEST_PORT')) ?  "8080" : getenv('HTTP_TEST_PORT');

        $this->BASE_URL_TEST = "$host:$port";

        $this->SERVER_TEST = "http://$host:$port/rest.php";
        $this->REDIRECT_TEST = "http://$host:$port/redirect.php";
        $this->SOAP_TEST = "http://$host:$port/soap.php";
        
        $this->object = new WebRequest($this->SERVER_TEST);
    }


    public function testSetCredentials()
    {
        $this->object->setCredentials('user', 'pass');

        $this->assertEquals(CURLAUTH_BASIC, $this->object->getCurlOption(CURLOPT_HTTPAUTH));
        $this->assertEquals('user:pass', $this->object->getCurlOption(CURLOPT_USERPWD));
    }

    public function testReferer()
    {
        $this->object->setReferer('http://example.com');
        $this->assertEquals('http://example.com', $this->object->getReferer());
    }

    public function testGetLastStatus()
    {
        $this->object->get();
        $this->assertEquals(200, $this->object->getLastStatus());
    }

    public function testGetResponseHeader()
    {
        $this->object->get();

        $result = $this->object->getResponseHeader();
        $this->assertEquals('HTTP/1.1 200 OK', $result[0]);
    }

    public function testisFollowingLocation()
    {
        $this->object->setFollowLocation(true);
        $this->assertTrue($this->object->isFollowingLocation());

        $this->object->setFollowLocation(false);
        $this->assertFalse($this->object->isFollowingLocation());
    }

    public function testSetCurlOption()
    {
        $this->object->setCurlOption(CURLOPT_NOBODY, true);
        $this->assertTrue($this->object->getCurlOption(CURLOPT_NOBODY));
    }

    public function testGet1()
    {
        $response = $this->object->get();
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $response = $this->object->get(['param1' => 'value1', 'param2' => 'value2']);
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $response = $this->object->get('just_string');
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $response = $this->object->get('just_string=value1&just_string2=value2');
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
        $expected = [
            'content-type' => null,
            'method' => 'GET',
            'query_string' => ['just_string' => 'value1', 'just_string2' => 'value2'],
            'post_string' => [],
            'payload' => ''
        ];
        $this->assertEquals($expected, $result);
    }

    public function testGet5()
    {
        $this->object = new WebRequest($this->SERVER_TEST . '?extraparam=ok');
        $response = $this->object->get(['param1' => 'value1']);
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
        $expected = [
            'content-type' => null,
            'method' => 'GET',
            'query_string' => ['extraparam' => 'ok', 'param1' => 'value1'],
            'post_string' => [],
            'payload' => ''
        ];
        $this->assertEquals($expected, $result);
    }

    public function testPost1()
    {
        $response = $this->object->post();
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $response = $this->object->post(['param1' => 'value1', 'param2' => 'value2']);
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $response = $this->object->post('just_string');
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $response = $this->object->post('just_string=value1&just_string2=value2');
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $this->object = new WebRequest($this->SERVER_TEST . '?extra=ok');
        $response = $this->object->post(['param' => 'value']);
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $this->object = new WebRequest($this->SERVER_TEST . '?extra=ok');
        $response = $this->object->postPayload('{teste: "ok"}', 'application/json');
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $response = $this->object->put();
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $response = $this->object->put(['param1' => 'value1', 'param2' => 'value2']);
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $response = $this->object->put('just_string');
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $response = $this->object->put('just_string=value1&just_string2=value2');
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $this->object = new WebRequest($this->SERVER_TEST . '?extra=ok');
        $response = $this->object->put(['param' => 'value']);
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $this->object = new WebRequest($this->SERVER_TEST . '?extra=ok');
        $response = $this->object->putPayload('{teste: "ok"}', 'application/json');
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $response = $this->object->delete();
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $response = $this->object->delete(['param1' => 'value1', 'param2' => 'value2']);
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $response = $this->object->delete('just_string');
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $response = $this->object->delete('just_string=value1&just_string2=value2');
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $this->object = new WebRequest($this->SERVER_TEST . '?extra=ok');
        $response = $this->object->delete(['param' => 'value']);
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $this->object = new WebRequest($this->SERVER_TEST . '?extra=ok');
        $response = $this->object->deletePayload('{teste: "ok"}', 'application/json');
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);
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
        $this->object = new WebRequest($this->SERVER_TEST);

        $uploadFile = [];
        $uploadFile[] = new MultiPartItem('field1', 'value1');
        $uploadFile[] = new MultiPartItem(
            'field2',
            '{"key": "value2"}',
            'filename.json',
            'application/json; charset=UTF-8'
        );
        $uploadFile[] = new MultiPartItem('field3', 'value3');

        $response = $this->object->postMultiPartForm($uploadFile);
        $this->assertEquals(200, $this->object->getLastStatus());
        $result = json_decode($response, true);

        $this->assertStringContainsString('multipart/form-data; boundary=boundary-', $result['content-type']);
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

    public function testCurlException()
    {
        $this->expectException(\ByJG\Util\Exception\CurlException::class);
        $this->object = new WebRequest('http://laloiuyakkkall.iiiuqq/');

        $this->object->get();
    }

    public function testSoap()
    {
        $this->object = new WebRequest($this->SOAP_TEST);
        $resutl = $this->object->soapCall('test', ['param1' => 'teste', 'param2' => 1]);
        $this->assertEquals("teste - 1", $resutl);
        $resutl = $this->object->soapCall('test', ['param1' => 'another call', 'param2' => 2018]);
        $this->assertEquals("another call - 2018", $resutl);
    }

    public function testSoapFail()
    {
        $this->expectException(\SoapFault::class);
        $this->object = new WebRequest($this->SERVER_TEST);
        $this->object->soapCall('test', ['param1' => 'teste', 'param2' => 1]);
    }
}
