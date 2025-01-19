<?php

namespace ByJG\WebRequest\Psr7;

use ByJG\Util\Uri;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequest extends Request implements ServerRequestInterface
{
    protected ?array $files = null;
    protected mixed $parsedBody = null;
    protected array $attributes = [];

    public function __construct(
        ?Uri $uri = null,
        protected array $serverParams = [],
        protected array $cookieParams = [],
    )
    {
        parent::__construct($uri ?? new Uri());

        if (empty($this->serverParams)) {
            $this->serverParams = $_SERVER;
        }

        if (empty($this->cookieParams)) {
            $this->cookieParams = $_COOKIE;
        }
    }

    /**
     * @inheritDoc
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * @inheritDoc
     */
    public function withCookieParams(array $cookies): static
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getQueryParams(): array
    {
        $clone = clone $this;
        $queryUri = $clone->uri->getQuery();
        parse_str($queryUri, $output);

        $queryServer = $clone->serverParams['QUERY_STRING'] ?? '';
        parse_str($queryServer, $outputServer);

        return array_merge($output, $outputServer);
    }

    /**
     * @inheritDoc
     */
    public function withQueryParams(array $query): static
    {
        $clone = clone $this;
        $clone->serverParams['QUERY_STRING'] = http_build_query($query);
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getUploadedFiles(): array
    {
        if (is_null($this->files)) {
            $this->files = UploadedFile::parseFilesGlobal();
        }
        return $this->files;
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $clone = clone $this;
        $clone->files = $uploadedFiles;
        return $clone;
    }

    /**
     * @inheritDoc
     * @psalm-suppress InvalidReturnStatement
     */
    public function getParsedBody()
    {
        if ($this->parsedBody !== null) {
            return $this->parsedBody;
        }

        $contentType = $this->getHeader('Content-Type')[0] ?? '';
        $bodyContents = $this->getBody()->getContents();

        if (stripos($contentType, 'application/json') !== false) {
            $this->parsedBody = json_decode($bodyContents, true);
        } elseif (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($bodyContents, $this->parsedBody);
        } else {
            $this->parsedBody = $bodyContents;
        }

        return $this->parsedBody;
    }

    /**
     * @inheritDoc
     */
    public function withParsedBody($data): static
    {
        $contentType = $this->getHeader('Content-Type')[0] ?? '';

        if (stripos($contentType, 'application/json') !== false) {
            $clone = $this->withBody(new MemoryStream(json_encode($data)));
        } elseif (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
            $clone = $this->withBody(new MemoryStream(http_build_query($data)));
        } else {
            $clone = $this->withBody(new MemoryStream($data));
        }
        $clone->parsedBody = $data;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function getAttribute(string $name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function withAttribute(string $name, $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute(string $name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }
}