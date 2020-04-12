<?php

/**
 * Class to abstract Soap and REST calls
 * @author jg
 *
 */

namespace ByJG\Util;

use ByJG\Util\Psr7\Response;
use InvalidArgumentException;
use MintWare\Streams\MemoryStream;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use SoapClient;
use SoapParam;

class HttpClient
{

    protected $url;
    protected $defaultCurlOptions = [];
    protected $curlOptions = [];
    protected $lastFetchedUrl = "";

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param RequestInterface $request
     * @return HttpClient
     */
    public static function getInstance()
    {
        return new HttpClient();
    }



    /**
     * Set the CURLOPT_FOLLOWLOCATION
     *
     * @param bool $value
     * @return HttpClient
     */
    public function withNoFollowRedirect()
    {
        $this->withCurlOption(CURLOPT_FOLLOWLOCATION, false);
        return $this;
    }

    public function withNoSSLVerification()
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
    public function withProxy(UriInterface $uri)
    {
        if ($this->request->getUri()->getUserInfo() != "") {
            $this->withCurlOption(CURLOPT_PROXYUSERPWD, $uri->getUserInfo());
            $this->request->getUri()->withUserInfo("");
        }
        $this->withCurlOption(CURLOPT_PROXY, $uri);
        return $this;
    }

    public function withCurlOption($key, $value)
    {
        if (!is_int($key)) {
            throw new InvalidArgumentException('It is not a CURL_OPT argument');
        }

        if (!is_null($value)) {
            $this->defaultCurlOptions[$key] = $value;
        } else {
            unset($this->defaultCurlOptions[$key]);
        }
    }




    /**
     * @return Response
     * @throws CurlException
     */
    public function sendRequest(RequestInterface $request)
    {
        $this->request = $request;
        $this->curlOptions = [];
        $this->clearRequestMethod();
        $this->defaultCurlOptions();

        $this->setCredentials();
        $this->setHeaders();
        $this->setMethod();
        $this->setBody();

        $curlHandle = $this->curlInit();

        return $this->curlGetResponse($curlHandle);
    }



    /**
     * Request the method using the CURLOPT defined previously;
     *
     * @return resource
     */
    protected function curlInit()
    {
        $curlHandle = curl_init();

        curl_setopt($curlHandle, CURLOPT_URL, $this->request->getUri());

        // Set Curl Options
        foreach ($this->curlOptions as $key => $value) {
            curl_setopt($curlHandle, $key, $value);
        }

        // Set last fetched URL
        $this->lastFetchedUrl = null;

        return $curlHandle;
    }

    protected function setMethod()
    {
        switch ($this->request->getMethod()) {
            case "POST":
                $this->setCurl(CURLOPT_POST, true);
                break;
            case "GET":
                break;
            default:
                $this->setCurl(CURLOPT_CUSTOMREQUEST, $this->request->getMethod());
        }
    }

    protected function setBody()
    {
        $stream = $this->request->getBody();
        if (!is_null($stream)) {
            $this->setCurl(CURLOPT_POSTFIELDS, $stream->getContents());
        }
    }

    /**
     * Defines Basic credentials for access the service.
     */
    protected function setCredentials()
    {
        if ($this->request->getUri()->getUserInfo() != "") {
            $this->setCurl(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            $this->setCurl(CURLOPT_USERPWD, $this->request->getUri()->getUserInfo());
            $this->request->getUri()->withUserInfo("");
        }
    }

    protected function setHeaders()
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
    protected function setCurl($key, $value)
    {
        if (!is_int($key)) {
            throw new InvalidArgumentException('It is not a CURL_OPT argument');
        }

        if (!is_null($value)) {
            $this->curlOptions[$key] = $value;
        } else {
            unset($this->curlOptions[$key]);
        }
    }

    /**
     *
     */
    protected function clearRequestMethod()
    {
        $this->setCurl(CURLOPT_POST, null);
        $this->setCurl(CURLOPT_PUT, null);
        $this->setCurl(CURLOPT_CUSTOMREQUEST, null);
    }

    /**
     * Set the default curl options.
     * You can override this method to setup your own default options.
     * You can pass the options to the constructor also;
     */
    protected function defaultCurlOptions()
    {
        $this->setCurl(CURLOPT_CONNECTTIMEOUT, 30);
        $this->setCurl(CURLOPT_TIMEOUT, 30);
        $this->setCurl(CURLOPT_HEADER, true);
        $this->setCurl(CURLOPT_RETURNTRANSFER, true);
        $this->setCurl(CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        $this->setCurl(CURLOPT_FOLLOWLOCATION, true);
        $this->setCurl(CURLOPT_SSL_VERIFYHOST, 2);
        $this->setCurl(CURLOPT_SSL_VERIFYPEER, 1);

        foreach ($this->defaultCurlOptions as $key => $value) {
            $this->setCurl($key, $value);
        }
    }

    /**
     * @param resource $curlHandle
     * @return Response
     * @throws \ByJG\Util\CurlException
     */
    protected function curlGetResponse($curlHandle)
    {
        $result = curl_exec($curlHandle);
        $error = curl_error($curlHandle);
        if ($result === false) {
            curl_close($curlHandle);
            throw new CurlException("CURL - " . $error);
        }

        $headerSize = curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE);
        $status = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $lastFetchedUrl = curl_getinfo($curlHandle, CURLINFO_EFFECTIVE_URL);
        curl_close($curlHandle);

        $response = Response::getInstance($status)
            ->withBody(new MemoryStream(substr($result, $headerSize)));

        $this->parseHeader($response, substr($result, 0, $headerSize));

        return $response;
    }


    protected function parseHeader(MessageInterface $response, $rawHeaders)
    {
        $key = '';

        foreach (preg_split("/\r?\n/", $rawHeaders) as $headerLine) {
            $headerLine = explode(':', $headerLine, 2);

            if (isset($headerLine[1])) {
                $response->withHeader($headerLine[0], preg_replace("/^\s+/", "", $headerLine[1]));
            }
        }
    }










}
