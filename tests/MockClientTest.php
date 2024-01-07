<?php

namespace Test;

use ByJG\Util\Exception\MessageException;
use ByJG\Util\Exception\RequestException;
use ByJG\Util\Helper\RequestFormUrlEncoded;
use ByJG\Util\Helper\RequestJson;
use ByJG\Util\Helper\RequestMultiPart;
use ByJG\Util\MockClient;
use ByJG\Util\MultiPartItem;
use ByJG\Util\Psr7\MemoryStream;
use ByJG\Util\Psr7\Request;
use ByJG\Util\Psr7\Response;
use ByJG\Util\Uri;
use PHPUnit\Framework\TestCase;

class MockClientTest extends TestCase
{

    protected string $BASE_URL_TEST;

    protected string $SERVER_TEST;
    protected string $REDIRECT_TEST;
    protected string $SOAP_TEST;

    /**
     * @var MockClient
     */
    protected MockClient $object;
    
    protected array $curlOptions;
    
    public function setUp(): void
    {
        $host = empty(getenv('HTTP_TEST_HOST')) ?  "localhost" : getenv('HTTP_TEST_HOST');
        $port = empty(getenv('HTTP_TEST_PORT')) ?  "8080" : getenv('HTTP_TEST_PORT');

        $this->BASE_URL_TEST = "$host:$port";

        $this->SERVER_TEST = "http://$host:$port/rest.php";
        $this->REDIRECT_TEST = "http://$host:$port/redirect.php";
        $this->SOAP_TEST = "http://$host:$port/soap.php";
        
        $this->object = new MockClient();
        
        $curl_version = curl_version();
        $this->curlOptions = [
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => "WebRequest/2.0.4 curl/" . $curl_version["version"] . " PHP/" . phpversion(),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => 1,
            CURLOPT_HTTPHEADER => [
                "Host: $this->BASE_URL_TEST"
            ],
        ];
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testGetLastStatus()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST));
        $response = $this->object->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals("1.1", $response->getProtocolVersion());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testWithCredentials()
    {
        $uri = Uri::getInstanceFromString($this->SERVER_TEST)
            ->withUserInfo("user", "pass");

        $request = Request::getInstance($uri);

        $response = $this->object->sendRequest($request);

        $curlOptions = $this->curlOptions + [
            CURLOPT_HTTPAUTH => 1,
            CURLOPT_USERPWD => "user:pass",
        ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testReferer()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST))
            ->withHeader("referer", "http://example.com/abc");

        $response = $this->object->sendRequest($request);

        $curlOptions = $this->curlOptions + [
            CURLOPT_REFERER => 'http://example.com/abc',
        ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testCustomHeader()
    {
        unset($this->curlOptions[CURLOPT_HTTPHEADER]);

        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST))
            ->withHeader("X-Custom-Header", "Defined");

        $response = $this->object->sendRequest($request);

        $curlOptions = $this->curlOptions + [
            CURLOPT_HTTPHEADER => ["Host: $this->BASE_URL_TEST", 'X-Custom-Header: Defined'],
        ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testisFollowingLocation()
    {
        unset($this->curlOptions[CURLOPT_FOLLOWLOCATION]);

        $request = Request::getInstance(Uri::getInstanceFromString($this->REDIRECT_TEST));
        $this->object = MockClient::getInstance()
            ->withNoFollowRedirect();
        $response = $this->object->sendRequest($request);
        $curlOptions = $this->curlOptions + [
            CURLOPT_FOLLOWLOCATION => false,
        ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());

        $this->object = MockClient::getInstance();
        $response = $this->object->sendRequest($request);
        $this->assertEquals(200, $response->getStatusCode());
        $curlOptions = $this->curlOptions + [
            CURLOPT_FOLLOWLOCATION => true,
        ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testisVerifySSL()
    {
        unset($this->curlOptions[CURLOPT_SSL_VERIFYHOST]);
        unset($this->curlOptions[CURLOPT_SSL_VERIFYPEER]);

        $request = Request::getInstance(Uri::getInstanceFromString($this->REDIRECT_TEST));
        $this->object = MockClient::getInstance()
            ->withNoSSLVerification();
        $response = $this->object->sendRequest($request);
        $curlOptions = $this->curlOptions + [
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());

        $this->object = MockClient::getInstance();
        $response = $this->object->sendRequest($request);
        $curlOptions = $this->curlOptions + [
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => 1,
        ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testGet1()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST))
            ->withMethod("GET");
        $response = $this->object->sendRequest($request);

        $curlOptions = $this->curlOptions;
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testPost1()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST))
            ->withMethod("POST");
        

        $response = $this->object->sendRequest($request);

        $curlOptions = $this->curlOptions + [
            CURLOPT_POST => true,
        ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testPost2()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), [
            'param1' => 'value1',
            'param2' => 'value2'
        ]);
        
        $response = $this->object->sendRequest($request);

        unset($this->curlOptions[CURLOPT_HTTPHEADER]);
        $curlOptions = $this->curlOptions + [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ["Host: $this->BASE_URL_TEST", 'Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => "param1=value1&param2=value2"
        ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testPost4()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), 'just_string=value1&just_string2=value2');

        $response = $this->object->sendRequest($request);

        unset($this->curlOptions[CURLOPT_HTTPHEADER]);
        $curlOptions = $this->curlOptions + [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ["Host: $this->BASE_URL_TEST", 'Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => "just_string=value1&just_string2=value2"
        ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testPost5()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("extra=ok"), [
            'param' => 'value'
        ]);

        $response = $this->object->sendRequest($request);

        unset($this->curlOptions[CURLOPT_HTTPHEADER]);
        $curlOptions = $this->curlOptions + [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ["Host: $this->BASE_URL_TEST", 'Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => "param=value"
        ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testPostPayload()
    {
        $request = RequestJson::build(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("extra=ok"),
            "POST",
            '{teste: "ok"}'
        );

        $response = $this->object->sendRequest($request);

        unset($this->curlOptions[CURLOPT_HTTPHEADER]);
        $curlOptions = $this->curlOptions + [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ["Host: $this->BASE_URL_TEST", 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => '{teste: "ok"}'
        ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }


    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testPut1()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST))
            ->withMethod("PUT");


        $response = $this->object->sendRequest($request);

        $curlOptions = $this->curlOptions + [
                CURLOPT_CUSTOMREQUEST => "PUT",
            ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testPut2()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), [
            'param1' => 'value1',
            'param2' => 'value2'
        ])->withMethod("PUT");

        $response = $this->object->sendRequest($request);

        unset($this->curlOptions[CURLOPT_HTTPHEADER]);
        $curlOptions = $this->curlOptions + [
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_HTTPHEADER => ["Host: $this->BASE_URL_TEST", 'Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_POSTFIELDS => "param1=value1&param2=value2"
            ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testPut4()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), 'just_string=value1&just_string2=value2')
            ->withMethod("PUT");

        $response = $this->object->sendRequest($request);

        unset($this->curlOptions[CURLOPT_HTTPHEADER]);
        $curlOptions = $this->curlOptions + [
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_HTTPHEADER => ["Host: $this->BASE_URL_TEST", 'Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_POSTFIELDS => "just_string=value1&just_string2=value2"
            ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testPut5()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("extra=ok"), [
            'param' => 'value'
        ])->withMethod("PUT");

        $response = $this->object->sendRequest($request);

        unset($this->curlOptions[CURLOPT_HTTPHEADER]);
        $curlOptions = $this->curlOptions + [
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_HTTPHEADER => ["Host: $this->BASE_URL_TEST", 'Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_POSTFIELDS => "param=value"
            ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testPutPayload()
    {
        $request = RequestJson::build(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("extra=ok"),
            "PUT",
            '{teste: "ok"}'
        )->withMethod("PUT");

        $response = $this->object->sendRequest($request);

        unset($this->curlOptions[CURLOPT_HTTPHEADER]);
        $curlOptions = $this->curlOptions + [
                CURLOPT_CUSTOMREQUEST => "PUT",
                CURLOPT_HTTPHEADER => ["Host: $this->BASE_URL_TEST", 'Content-Type: application/json'],
                CURLOPT_POSTFIELDS => '{teste: "ok"}'
            ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }


    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testDelete1()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST))
            ->withMethod("DELETE");


        $response = $this->object->sendRequest($request);

        $curlOptions = $this->curlOptions + [
                CURLOPT_CUSTOMREQUEST => "DELETE",
            ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testDelete2()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), [
            'param1' => 'value1',
            'param2' => 'value2'
        ])->withMethod("DELETE");

        $response = $this->object->sendRequest($request);

        unset($this->curlOptions[CURLOPT_HTTPHEADER]);
        $curlOptions = $this->curlOptions + [
                CURLOPT_CUSTOMREQUEST => "DELETE",
                CURLOPT_HTTPHEADER => ["Host: $this->BASE_URL_TEST", 'Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_POSTFIELDS => "param1=value1&param2=value2"
            ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testDelete4()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST), 'just_string=value1&just_string2=value2')
            ->withMethod("DELETE");

        $response = $this->object->sendRequest($request);

        unset($this->curlOptions[CURLOPT_HTTPHEADER]);
        $curlOptions = $this->curlOptions + [
                CURLOPT_CUSTOMREQUEST => "DELETE",
                CURLOPT_HTTPHEADER => ["Host: $this->BASE_URL_TEST", 'Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_POSTFIELDS => "just_string=value1&just_string2=value2"
            ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testDelete5()
    {
        $request = RequestFormUrlEncoded::build(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("extra=ok"), [
            'param' => 'value'
        ])->withMethod("DELETE");

        $response = $this->object->sendRequest($request);

        unset($this->curlOptions[CURLOPT_HTTPHEADER]);
        $curlOptions = $this->curlOptions + [
                CURLOPT_CUSTOMREQUEST => "DELETE",
                CURLOPT_HTTPHEADER => ["Host: $this->BASE_URL_TEST", 'Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_POSTFIELDS => "param=value"
            ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testDeletePayload()
    {
        $request = RequestJson::build(Uri::getInstanceFromString($this->SERVER_TEST)->withQuery("extra=ok"),
            "DELETE",
            '{teste: "ok"}'
        )->withMethod("DELETE");

        $response = $this->object->sendRequest($request);

        unset($this->curlOptions[CURLOPT_HTTPHEADER]);
        $curlOptions = $this->curlOptions + [
                CURLOPT_CUSTOMREQUEST => "DELETE",
                CURLOPT_HTTPHEADER => ["Host: $this->BASE_URL_TEST", 'Content-Type: application/json'],
                CURLOPT_POSTFIELDS => '{teste: "ok"}'
            ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }


    /**
     * @throws MessageException
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
            $uploadFile,
            "12345"
        );
        

        $response = $this->object->sendRequest($request);

        unset($this->curlOptions[CURLOPT_HTTPHEADER]);
        $curlOptions = $this->curlOptions + [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ["Host: $this->BASE_URL_TEST", 'Content-Type: multipart/form-data; boundary=12345'],
                CURLOPT_POSTFIELDS => "--12345\n".
                    "Content-Disposition: form-data; name=\"field1\";\n".
                    "\n".
                    "value1\n".
                    "--12345\n".
                    "Content-Disposition: form-data; name=\"field2\"; filename=\"filename.json\";\n".
                    "Content-Type: application/json; charset=UTF-8\n".
                    "\n".
                    "{\"key\": \"value2\"}\n".
                    "--12345\n".
                    "Content-Disposition: form-data; name=\"field3\";\n".
                    "\n".
                    "value3\n".
                    "--12345--"
            ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testWithCurlOption()
    {
        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST));

        $this->object->withCurlOption(CURLOPT_NOBODY, 1);

        $response = $this->object->sendRequest($request);

        $curlOptions = $this->curlOptions + [
                CURLOPT_NOBODY => 1,
            ];
        $this->assertEquals($curlOptions, $this->object->getCurlConfiguration());
    }

    /**
     * @throws MessageException
     * @throws RequestException
     */
    public function testMockResponse()
    {
        $expectedResponse = Response::getInstance(404)
            ->withBody(new MemoryStream("<h1>Not Found</h1>"));

        $request = Request::getInstance(Uri::getInstanceFromString($this->SERVER_TEST));

        $this->object = new MockClient($expectedResponse);

        $response = $this->object->sendRequest($request);

        $this->assertSame($expectedResponse, $response);
    }

}
