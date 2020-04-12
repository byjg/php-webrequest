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
    protected $requestUrl;
    protected $soapClass = null;
    protected $requestHeader = array();
    protected $responseHeader = null;
    protected $cookies = array();
    protected $lastStatus = "";
    protected $curlOptions = [];
    protected $lastFetchedUrl = "";

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
        $this->curlOptions = [];
        $this->defaultCurlOptions();
    }

    /**
     * @param RequestInterface $request
     * @return HttpClient
     */
    public static function getInstance(RequestInterface $request)
    {
        return new HttpClient($request);
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
     * Set a custom CURL option
     *
     * @param int $key
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    public function withCurlOption($key, $value)
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

    /**
     * @return Response
     * @throws CurlException
     */
    public function send()
    {
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
                $this->withCurlOption(CURLOPT_POST, true);
                break;
            case "GET":
                break;
            default:
                $this->withCurlOption(CURLOPT_CUSTOMREQUEST, $this->request->getMethod());
        }
    }

    protected function setBody()
    {
        $stream = $this->request->getBody();
        if (!is_null($stream)) {
            $this->withCurlOption(CURLOPT_POSTFIELDS, $stream->getContents());
        }
    }

    /**
     * Defines Basic credentials for access the service.
     */
    protected function setCredentials()
    {
        if ($this->request->getUri()->getUserInfo() != "") {
            $this->withCurlOption(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            $this->withCurlOption(CURLOPT_USERPWD, $this->request->getUri()->getUserInfo());
            $this->request->getUri()->withUserInfo("");
        }
    }

    protected function setHeaders()
    {
        $headers = $this->request->getHeaders();
        $resultHeaders = [];
        foreach ($headers as $key => $value) {
            if ($key === "Referer") {
                $this->withCurlOption(CURLOPT_REFERER, $this->request->getHeaderLine($key));
                break;
            }
            if ($key === "User-Agent") {
                $this->withCurlOption(CURLOPT_USERAGENT, $this->request->getHeaderLine($key));
                break;
            }

            $resultHeaders[] = $key . ": " . $this->request->getHeaderLine($key);
        }

        if (count($resultHeaders) > 0) {
            $this->withCurlOption(CURLOPT_HTTPHEADER, $resultHeaders);
        }
    }

    /**
     * Set the default curl options.
     * You can override this method to setup your own default options.
     * You can pass the options to the constructor also;
     */
    protected function defaultCurlOptions()
    {
        $this->withCurlOption(CURLOPT_CONNECTTIMEOUT, 30);
        $this->withCurlOption(CURLOPT_TIMEOUT, 30);
        $this->withCurlOption(CURLOPT_HEADER, true);
        $this->withCurlOption(CURLOPT_RETURNTRANSFER, true);
        $this->withCurlOption(CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        $this->withCurlOption(CURLOPT_FOLLOWLOCATION, true);
        $this->withCurlOption(CURLOPT_SSL_VERIFYHOST, 2);
        $this->withCurlOption(CURLOPT_SSL_VERIFYPEER, 1);
    }











    /**
     * Get the current CURLOPT_REFERER
     *
     * @return string
     */
    public function getReferer()
    {
        return $this->getCurlOption(CURLOPT_REFERER);
    }

    /**
     * Set the CURLOPT_REFERER
     *
     * @param string $value
     */
    public function setReferer($value)
    {
        $this->withCurlOption(CURLOPT_REFERER, $value);
    }

    /**
     * Get the status of last request (get, put, delete, post)
     *
     * @return integer
     */
    public function getLastStatus()
    {
        return $this->lastStatus;
    }

    /**
     * Get an array with the curl response header
     *
     * @return array
     */
    public function getResponseHeader()
    {
        return $this->responseHeader;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getLastFetchedUrl()
    {
        return $this->lastFetchedUrl;
    }


    /**
     *
     * @return SoapClient
     */
    protected function getSoapClient()
    {
        if (is_null($this->soapClass)) {
            $this->soapClass = new SoapClient(
                null,
                [
                    "location" => $this->url,
                    "uri" => "urn:xmethods-delayed-quotes",
                    "style" => SOAP_RPC,
                    "use" => SOAP_ENCODED,
                    "trace" => true,
                    "exceptions" => true
                ]
            );

            if ($this->getCurlOption(CURLOPT_HTTPAUTH) == CURLAUTH_BASIC) {
                $curlPwd = explode(":", $this->getCurlOption(CURLOPT_USERPWD));
                $username = $curlPwd[0];
                $password = $curlPwd[1];
                $this->soapClass->setCredentials($username, $password);
            }
        }

        return $this->soapClass;
    }

    /**
     * Call a Soap client.
     *
     * For example:
     *
     * $webreq = new WebRequest("http://www.byjg.com.br/webservice.php/ws/cep");
     * $result = $webreq->soapCall("obterCep", new array("cep", "11111233"));
     *
     * @param string $method
     * @param array $params
     * @param array $soapOptions
     * @return string
     */
    public function soapCall($method, $params = null, $soapOptions = null)
    {
        $soapParams = null;
        
        if (is_array($params)) {
            $soapParams = array();
            foreach ($params as $key => $value) {
                $soapParams[] = new SoapParam($value, $key);
            }
        }

        if (!is_array($soapOptions) || (is_null($soapOptions))) {
            $soapOptions = array(
                "uri" => "urn:xmethods-delayed-quotes",
                "soapaction" => "urn:xmethods-delayed-quotes#getQuote"
            );
        }

        // Chamando método do webservice
        $result = $this->getSoapClient()->__soapCall(
            $method,
            $soapParams,
            $soapOptions
        );

        return $result;
    }


    /**
     * Get the current Curl option
     *
     * @param int $key
     * @return mixed
     */
    protected function getCurlOption($key)
    {
        return (isset($this->curlOptions[$key]) ? $this->curlOptions[$key] : null);
    }


    /**
     * @param array|string|null $fields
     * @return string|array|null
     */
    protected function getMultiFormData($fields)
    {
        if (is_array($fields)) {
            return http_build_query($fields);
        }
        
        return $fields;
    }

    /**
     * @param array|string $fields
     */
    protected function setPostString($fields)
    {
        $replaceHeader = true;
        foreach ($this->requestHeader as $header) {
            if (stripos($header, 'content-type') !== false) {
                $replaceHeader = false;
            }
        }

        if ($replaceHeader) {
            $this->addRequestHeader("Content-Type", 'application/x-www-form-urlencoded');
        }

        $this->withCurlOption(CURLOPT_POSTFIELDS, $this->getMultiFormData($fields));
    }

    /**
     * @param array|string|null $fields
     */
    protected function setQueryString($fields)
    {
        $queryString = $this->getMultiFormData($fields);

        if (!empty($queryString)) {
            $this->requestUrl = $this->url . (strpos($this->url, "?") === false ? "?" : "&") . $queryString;
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

    /**
     *
     */
    protected function clearRequestMethod()
    {
        $this->withCurlOption(CURLOPT_POST, null);
        $this->withCurlOption(CURLOPT_PUT, null);
        $this->withCurlOption(CURLOPT_CUSTOMREQUEST, null);
    }

    /**
     * @param array|string|null $params
     * @param resource|null $curlHandle
     * @return null|resource
     */
    public function prepareGet($params = null, $curlHandle = null)
    {
        $this->clearRequestMethod();
        $this->setQueryString($params);
        if (empty($curlHandle)) {
            $curlHandle = $this->curlInit();
        }
        return $curlHandle;
    }

    /**
     * Make a REST Get method call
     *
     * @param array|null $params
     * @return string
     * @throws \ByJG\Util\CurlException
     */
    public function get($params = null)
    {
        $curlHandle = $this->prepareGet($params);
        return $this->curlGetResponse($curlHandle);
    }

    /**
     * @param array|string|null $params
     * @param resource|null $curlHandle
     * @param int $curlOption
     * @param mixed $curlValue
     * @return resource
     */
    protected function prepare($params, $curlHandle, $curlOption, $curlValue)
    {
        $this->clearRequestMethod();
        $this->withCurlOption($curlOption, $curlValue);
        $this->setPostString($params);
        if (empty($curlHandle)) {
            $curlHandle = $this->curlInit();
        }
        return $curlHandle;
    }

    /**
     * @param string|array|null $params
     * @param resource|null $curlHandle
     * @return resource
     */
    public function preparePost($params = '', $curlHandle = null)
    {
        return $this->prepare(is_null($params) ? '' : $params, $curlHandle, CURLOPT_POST, true);
    }

    /**
     * Make a REST POST method call with parameters
     *
     * @param array|string $params
     * @return string
     * @throws \ByJG\Util\CurlException
     */
    public function post($params = '')
    {
        $handle = $this->preparePost($params);
        return $this->curlGetResponse($handle);
    }

    /**
     * @param MultiPartItem[] $params
     * @param resource|null $curlHandle
     * @return null|resource
     */
    public function preparePostMultiFormData($params = [], $curlHandle = null)
    {
        $this->clearRequestMethod();
        $this->withCurlOption(CURLOPT_POST, true);

        $boundary = 'boundary-' . md5(time());
        $body = '';
        foreach ($params as $item) {
            $body .= "--$boundary\nContent-Disposition: form-data; name=\"{$item->getField()}\";";
            $fileName = $item->getFileName();
            if (!empty($fileName)) {
                $body .= " filename=\"{$item->getFileName()}\";";
            }
            $contentType = $item->getContentType();
            if (!empty($contentType)) {
                $body .= "\nContent-Type: {$item->getContentType()}";
            }
            $body .= "\n\n{$item->getContent()}\n";
        }
        $body .= "--$boundary--";

        $this->addRequestHeader("Content-Type", "multipart/form-data; boundary=$boundary");

        $this->setPostString($body);
        if (empty($curlHandle)) {
            $curlHandle = $this->curlInit();
        }
        return $curlHandle;
    }

    /**
     * Make a REST POST method call with parameters
     *
     * @param MultiPartItem[] $params
     * @return string
     * @throws \ByJG\Util\CurlException
     */
    public function postMultiPartForm($params = [])
    {
        $handle = $this->preparePostMultiFormData($params);
        return $this->curlGetResponse($handle);
    }

    /**
     * Make a REST POST method call sending a payload
     *
     * @param string $data
     * @param string $contentType
     * @return string
     * @throws \ByJG\Util\CurlException
     */
    public function postPayload($data, $contentType = "text/plain")
    {
        $this->addRequestHeader("Content-Type", $contentType);
        return $this->post($data);
    }

    /**
     * @param array|string|null $params
     * @param resource|null $curlHandle
     * @return resource
     */
    public function preparePut($params = null, $curlHandle = null)
    {
        return $this->prepare($params, $curlHandle, CURLOPT_CUSTOMREQUEST, 'PUT');
    }

    /**
     * Make a REST PUT method call with parameters
     *
     * @param array|string $params
     * @return string
     * @throws \ByJG\Util\CurlException
     */
    public function put($params = null)
    {
        $handle = $this->preparePut($params);
        return $this->curlGetResponse($handle);
    }

    /**
     * Make a REST PUT method call sending a payload
     *
     * @param string $data
     * @param string $contentType
     * @return string
     * @throws \ByJG\Util\CurlException
     */
    public function putPayload($data, $contentType = "text/plain")
    {
        $this->addRequestHeader("Content-Type", $contentType);
        return $this->put($data);
    }

    /**
     * @param array|string|null $params
     * @param resource|null $curlHandle
     * @return resource
     */
    public function prepareDelete($params = null, $curlHandle = null)
    {
        return $this->prepare($params, $curlHandle, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }


    /**
     * Make a REST DELETE method call with parameters
     *
     * @param array|string $params
     * @return string
     * @throws \ByJG\Util\CurlException
     */
    public function delete($params = null)
    {
        $handle = $this->prepareDelete($params);
        return $this->curlGetResponse($handle);
    }

    /**
     * Make a REST DELETE method call sending a payload
     *
     * @param string $data
     * @param string $contentType
     * @return string
     * @throws \ByJG\Util\CurlException
     */
    public function deletePayload($data = null, $contentType = "text/plain")
    {
        $this->addRequestHeader("Content-Type", $contentType);
        return $this->delete($data);
    }

    /**
     * Makes a URL Redirection based on the current client navigation (Browser)
     *
     * @param array $params
     * @param bool $atClientSide If true send a javascript for redirection
     */
    public function redirect($params = null, $atClientSide = false)
    {
        $this->setQueryString($params);

        ob_clean();
        header('Location: ' . $this->requestUrl);
        if ($atClientSide) {
            echo "<script language='javascript'>window.top.location = '" . $this->requestUrl . "'; </script>";
        }
    }
}
