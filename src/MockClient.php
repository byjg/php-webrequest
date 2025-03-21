<?php

namespace ByJG\WebRequest;

use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\Response;
use CurlHandle;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class MockClient extends HttpClient
{
    /**
     * @var Response
     */
    protected ResponseInterface $expectedResponse;


    /**
     * MockClient constructor.
     * @param ResponseInterface|null $expectedResponse
     */
    public function __construct(?ResponseInterface $expectedResponse = null)
    {
        if (is_null($expectedResponse)) {
            $expectedResponse = (new Response(200))
                                    ->withBody(new MemoryStream('{"key":"value"}'));
        }
        $this->expectedResponse = $expectedResponse;
    }

    /**
     * @return MockClient
     */
    public static function getInstance(): MockClient
    {
        return new MockClient(new Response(200));
    }

    /**
     * @param RequestInterface $request
     * @return Response
     * @throws RequestException
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $curlHandle = $this->createCurlHandle($request);
        curl_close($curlHandle);

        return $this->parseCurl("", $curlHandle);
    }

    public function parseCurl(string $body, $curlHandle, $close = true): Response
    {
        return $this->expectedResponse;
    }

    /**
     * @param RequestInterface $request
     * @throws RequestException
     * @return CurlHandle
     */
    public function createCurlHandle(RequestInterface $request): CurlHandle
    {
        $this->request = clone $request;
        $this->curlOptions = [];
        $this->clearRequestMethod();
        $this->defaultCurlOptions();

        $this->setCredentials();
        $this->setHeaders();
        $this->setMethod();
        $this->setBody();

        return $this->curlInit();
    }



    /**
     * Request the method using the CURLOPT defined previously;
     *
     * @return CurlHandle
     */
    protected function curlInit(): CurlHandle
    {
        $curlHandle = curl_init();
        if ($curlHandle === false) {
            throw new \RuntimeException("Failed to initialize cURL");
        }
        return $curlHandle;
    }

    /**
     * @return array
     */
    public function getCurlConfiguration(): array
    {
        return $this->curlOptions;
    }

    /**
     * @return RequestInterface
     */
    public function getRequestedObject(): RequestInterface
    {
        return $this->request;
    }

    /**
     * @return Response
     */
    public function getExpectedResponse(): ResponseInterface
    {
        return $this->expectedResponse;
    }
}
