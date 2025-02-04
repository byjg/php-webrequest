<?php

namespace ByJG\WebRequest\Psr7;

use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\HttpMethod;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Request extends Message implements RequestInterface
{
    protected string $method = "GET";

    /**
     * @var UriInterface
     */
    protected UriInterface $uri;

    /**
     * Request constructor.
     * @param UriInterface $uri
     * @throws MessageException
     */
    public function __construct(UriInterface $uri)
    {
        $this->setUri($uri);
    }

    /**
     * @param UriInterface $uri
     * @return Request
     * @throws MessageException
     */
    public static function getInstance(UriInterface $uri): Request
    {
        return new Request($uri);
    }

    /**
     * @inheritDoc
     */
    public function getRequestTarget(): string
    {
        $target = $this->uri->getQuery();
        if (!empty($target)) {
            $target = "?$target";
        }
        return $this->uri->getPath() . $target;
    }

    /**
     * @inheritDoc
     * @throws MessageException
     */
    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        $clone = clone $this;
        $parts = explode("?", $requestTarget);
        $uri = $clone->uri->withPath($parts[0]);
        if (isset($parts[1])) {
            unset($parts[0]);
            $uri = $uri->withQuery(implode("?", $parts));
        }
        $clone->setUri($uri);
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @inheritDoc
     * @return $this
     * @throws RequestException
     */
    public function withMethod(string|HttpMethod $method): RequestInterface
    {
        if ($method instanceof HttpMethod) {
            $method = $method->value;
        }

        $method = strtoupper($method);

        if (is_null(HttpMethod::tryFrom($method))) {
            throw new RequestException($this, "Invalid Method " . $method);
        }

        $clone = clone $this;
        $clone->method = $method;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @inheritDoc
     * @throws MessageException
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        $clone = clone $this;
        $clone->setUri($uri, $preserveHost);
        return $clone;
    }

    /**
     * @throws MessageException
     */
    protected function setUri(UriInterface $uri, $preserveHost = false): void
    {
        $this->uri = $uri;

        if (!$preserveHost || (!$this->hasHeader("host") && $this->uri->getHost() !== "")) {
            $host = $this->uri->getPort();
            if (!empty($host)) {
                $host = ":$host";
            }
            $this->setHeader("host", $this->uri->getHost() . $host, true);
        }
    }
}
