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
    #[\Override]
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function withCookieParams(array $cookies): static
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
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
    #[\Override]
    public function withQueryParams(array $query): static
    {
        $clone = clone $this;
        $clone->serverParams['QUERY_STRING'] = http_build_query($query);
        return $clone;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
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
    #[\Override]
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
    #[\Override]
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
    #[\Override]
    public function withParsedBody($data): static
    {
        $contentType = $this->getHeader('Content-Type')[0] ?? '';

        if (stripos($contentType, 'application/json') !== false) {
            $clone = $this->withBody(new MemoryStream(json_encode($data)));
        } elseif (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
            $clone = $this->withBody(new MemoryStream(http_build_query($data ?? [])));
        } else {
            $clone = $this->withBody(new MemoryStream($data));
        }
        $clone->parsedBody = $data;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getAttribute(string $name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function withAttribute(string $name, $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function withoutAttribute(string $name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }
}