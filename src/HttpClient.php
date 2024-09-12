<?php

namespace ByJG\Util;

use ByJG\Util\Exception\NetworkException;
use ByJG\Util\Exception\RequestException;
use CurlHandle;
use ByJG\Util\Psr7\NullStream;
use InvalidArgumentException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class HttpClient implements ClientInterface
{
    use ParseCurlTrait;

    protected array $defaultCurlOptions = [];
    protected array $curlOptions = [];

    protected RequestInterface $request;

    /**
     * @return HttpClient
     */
    public static function getInstance(): HttpClient
    {
        return new HttpClient();
    }


    /**
     * Set the CURLOPT_FOLLOWLOCATION
     *
     * @return HttpClient
     */
    public function withNoFollowRedirect(): HttpClient
    {
        $this->withCurlOption(CURLOPT_FOLLOWLOCATION, false);
        return $this;
    }

    public function withNoSSLVerification(): HttpClient
    {
        $this->withCurlOption(CURLOPT_SSL_VERIFYHOST, 0);
        $this->withCurlOption(CURLOPT_SSL_VERIFYPEER, 0);
        return $this;
    }

    /**
     * Setting the Proxy
     *
     * The full representation of the proxy is scheme://url:port,
     * but the only required is the URL;
     *
     * Some examples:
     *    my.proxy.com
     *    my.proxy.com:1080
     *    https://my.proxy.com:1080
     *    socks4://my.proxysocks.com
     *    socks5://my.proxysocks.com
     *
     * @param UriInterface $uri
     * @return HttpClient
     */
    public function withProxy(UriInterface $uri): HttpClient
    {
        if ($uri->getUserInfo() != "") {
            $this->withCurlOption(CURLOPT_PROXYUSERPWD, $uri->getUserInfo());
        }
        $this->withCurlOption(CURLOPT_PROXY, $uri);
        return $this;
    }

    public function withCurlOption(int $key, $value): HttpClient
    {
        if (!is_null($value)) {
            $this->defaultCurlOptions[$key] = $value;
        } else {
            unset($this->defaultCurlOptions[$key]);
        }

        return $this;
    }

    public function withoutCurlOption(int $key): HttpClient
    {
        return $this->withCurlOption($key, null);
    }


    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws RequestException
     * @throws NetworkException
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $curlHandle = $this->createCurlHandle($request);

        $result = curl_exec($curlHandle);
        $error = curl_error($curlHandle);
        if ($result === false) {
            curl_close($curlHandle);
            throw new NetworkException($this->request, "CURL - " . $error);
        }

        return $this->parseCurl($result, $curlHandle);
    }

    /**
     * @param RequestInterface $request
     * @return CurlHandle
     * @throws RequestException
     */
    public function createCurlHandle(RequestInterface $request): CurlHandle
    {
        $this->request = $request;
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

        curl_setopt($curlHandle, CURLOPT_URL, $this->request->getUri());

        // Set Curl Options
        foreach ($this->curlOptions as $key => $value) {
            curl_setopt($curlHandle, $key, $value);
        }

        return $curlHandle;
    }

    protected function setMethod(): void
    {
        switch ($this->request->getMethod()) {
            case "POST":
                $this->setCurl(CURLOPT_POST, true);
                break;
            case "HEAD":
                $this->setCurl(CURLOPT_NOBODY, true);
                $this->setCurl(CURLOPT_WRITEFUNCTION, null);
                $this->setCurl(CURLOPT_READFUNCTION, null);
                $this->setCurl(CURLOPT_FILE, null);
                $this->setCurl(CURLOPT_INFILE, null);
                break;
            case "GET":
                break;
            default:
                $this->setCurl(CURLOPT_CUSTOMREQUEST, $this->request->getMethod());
        }
    }

    /**
     * @throws RequestException
     */
    protected function setBody(): void
    {
        $stream = $this->request->getBody();
        if (!$stream instanceof NullStream) {
            if (!$this->getCurl(CURLOPT_POST) && !$this->getCurl(CURLOPT_CUSTOMREQUEST)) {
                throw new RequestException($this->request,"Cannot set body with method GET");
            }
        }
    }

    /**
     * Defines Basic credentials for access the service.
     */
    protected function setCredentials(): void
    {
        if ($this->request->getUri()->getUserInfo() != "") {
            $this->setCurl(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            $this->setCurl(CURLOPT_USERPWD, $this->request->getUri()->getUserInfo());
        }
    }

    protected function setHeaders(): void
    {
        $headers = $this->request->getHeaders();
        $resultHeaders = [];
        foreach ($headers as $key => $value) {
            if ($key === "Referer") {
                $this->setCurl(CURLOPT_REFERER, $this->request->getHeaderLine($key));
                break;
            }
            if ($key === "User-Agent") {
                $this->setCurl(CURLOPT_USERAGENT, $this->request->getHeaderLine($key));
                break;
            }

            $resultHeaders[] = $key . ": " . $this->request->getHeaderLine($key);
        }

        if (count($resultHeaders) > 0) {
            $this->setCurl(CURLOPT_HTTPHEADER, $resultHeaders);
        }
    }

    /**
     * Set a custom CURL option
     *
     * @param int $key
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    protected function setCurl(int $key, mixed $value): void
    {
        if (!is_null($value)) {
            $this->curlOptions[$key] = $value;
        } else {
            unset($this->curlOptions[$key]);
        }
    }

    protected function getCurl($key): ?string
    {
        if (isset($this->curlOptions[$key])) {
            return $this->curlOptions[$key];
        }

        return null;
    }

    /**
     *
     */
    protected function clearRequestMethod(): void
    {
        $this->setCurl(CURLOPT_POST, null);
        $this->setCurl(CURLOPT_PUT, null);
        $this->setCurl(CURLOPT_CUSTOMREQUEST, null);
    }

    /**
     * Set the default curl options.
     * You can override this method to set up your own default options.
     * You can pass the options to the constructor also;
     */
    protected function defaultCurlOptions(): void
    {
        $curl_version = curl_version();
        $this->setCurl(CURLOPT_CONNECTTIMEOUT, 30);
        $this->setCurl(CURLOPT_TIMEOUT, 30);
        $this->setCurl(CURLOPT_HEADER, true);
        $this->setCurl(CURLOPT_RETURNTRANSFER, true);
        $this->setCurl(CURLOPT_USERAGENT, "WebRequest/2.0.4 curl/" . $curl_version["version"] . " PHP/" . phpversion());
        $this->setCurl(CURLOPT_FOLLOWLOCATION, true);
        $this->setCurl(CURLOPT_SSL_VERIFYHOST, 2);
        $this->setCurl(CURLOPT_SSL_VERIFYPEER, 1);

        foreach ($this->defaultCurlOptions as $key => $value) {
            $this->setCurl($key, $value);
        }
    }
}
