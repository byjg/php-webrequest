<?php


namespace ByJG\Util\Psr7;

use ByJG\Util\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface
{
    protected $method = "GET";
    protected $validMethods = [ "GET", "HEAD", "POST", "PUT", "DELETE", "CONNECT", "OPTIONS", "TRACE", "PATCH" ];

    /**
     * @var UriInterface
     */
    protected $uri;

    /**
     * Request constructor.
     * @param UriInterface $uri
     * @throws MessageException
     */
    public function __construct(UriInterface $uri)
    {
        $this->withUri($uri);
    }

    /**
     * @param UriInterface $uri
     * @return Request
     * @throws MessageException
     */
    public static function getInstance(UriInterface $uri)
    {
        return new Request($uri);
    }

    /**
     * @inheritDoc
     */
    public function getRequestTarget()
    {
        $target = $this->uri->getQuery();
        if (!empty($target)) {
            $target = "?$target";
        }
        return $this->uri->getPath() . $target;
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget($requestTarget)
    {
        $parts = explode("?", $requestTarget);
        $this->uri->withPath($parts[0]);
        if (isset($parts[1])) {
            unset($parts[0]);
            $this->uri->withQuery(implode("?", $parts));
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @inheritDoc
     * @throws MessageException
     */
    public function withMethod($method)
    {
        $method = strtoupper($method);

        if (!in_array($method, $this->validMethods)) {
            throw new MessageException("Invalid Method " . $method);
        }
 
        $this->method = $method;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @inheritDoc
     * @throws MessageException
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $this->uri = $uri;

        if (!$preserveHost || (!$this->hasHeader("host") && $this->uri->getHost() !== "")) {
            $host = $this->uri->getPort();
            if (!empty($host)) {
                $host = ":$host";
            }
            $this->withHeader("host", $this->uri->getHost() . $host);
        }
        return $this;
    }
}