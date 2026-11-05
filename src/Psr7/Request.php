<?php

namespace ByJG\WebRequest\Psr7;

use ByJG\WebRequest\Exception\MessageException;
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
    #[\Override]
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
     * @return $this
     * @throws MessageException
     */
    #[\Override]
    public function withRequestTarget(string $requestTarget): static
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
    #[\Override]
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * The method is case-sensitive and can be any token, not only the ones in HttpMethod.
     *
     * @inheritDoc
     * @return $this
     * @throws MessageException
     */
    #[\Override]
    public function withMethod(string|HttpMethod $method): static
    {
        if ($method instanceof HttpMethod) {
            $method = $method->value;
        }

        if (preg_match("/^[!#$%&'*+.^_`|~0-9A-Za-z-]+$/", $method) !== 1) {
            throw new MessageException("Invalid Method " . $method);
        }

        $clone = clone $this;
        $clone->method = $method;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @inheritDoc
     * @return $this
     * @throws MessageException
     */
    #[\Override]
    public function withUri(UriInterface $uri, bool $preserveHost = false): static
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

        // A URI without a host keeps the Host header, and so does $preserveHost when there is one
        $host = $this->uri->getHost();
        if ($host === "" || ($preserveHost && $this->getHeaderLine("Host") !== "")) {
            return;
        }

        $port = $this->uri->getPort();
        if (!empty($port)) {
            $host .= ":$port";
        }
        $this->setHeader("Host", $host, true);
    }
}
