<?php


namespace ByJG\WebRequest\Psr7;

use ByJG\WebRequest\Exception\MessageException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

class Message implements MessageInterface
{
    protected string $protocolVersion = "1.1";
    /**
     * @var array<string, string[]> the values by the header name, in the case it was first given
     */
    protected array $headers = [];

    /**
     * @var array<string, string> the name in $headers by the lowercase name
     */
    protected array $headerNames = [];

    /**
     * @var ?StreamInterface
     */
    protected ?StreamInterface $body = null;

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @inheritDoc
     * @return $this
     * @throws MessageException
     */
    #[\Override]
    public function withProtocolVersion($version): static
    {
        if ($version != "1.0" && $version != "1.1") {
            throw new MessageException("Invalid Protocol Version");
        }
        $clone = clone $this;
        $clone->protocolVersion = $version;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getHeader(string $name): array
    {
        if ($this->hasHeader($name)) {
            return $this->headers[$this->headerNames[strtolower($name)]];
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getHeaderLine($name): string
    {
        return implode(",", $this->getHeader($name));
    }

    /**
     * @inheritDoc
     * @return $this
     * @throws MessageException
     */
    #[\Override]
    public function withHeader(string $name, $value): static
    {
        $clone = clone $this;
        $clone->setHeader($name, $value, true);
        return $clone;
    }

    /**
     * An instance with these headers, each set as withHeader() sets it (it replaces the header of that name, in any
     * case): one copy of the message for all of them, instead of one for each withHeader() call. Not part of PSR-7.
     *
     * @param array<string, string|string[]> $headers The values of each header name, as getHeaders() returns them
     * @return static
     * @throws MessageException when a name or a value is invalid
     */
    public function withHeaders(array $headers): static
    {
        $clone = clone $this;
        foreach ($headers as $name => $value) {
            $clone->setHeader((string)$name, $value, true);
        }
        return $clone;
    }

    /**
     * @inheritDoc
     * @return $this
     * @throws MessageException
     */
    #[\Override]
    public function withAddedHeader(string $name, $value): static
    {
        $clone = clone $this;
        $clone->setHeader($name, $value, false);
        return $clone;
    }

    /**
     * @param mixed $name
     * @param mixed $value
     * @param bool $overwrite
     * @throws MessageException
     */
    protected function setHeader(mixed $name, mixed $value, bool $overwrite): void
    {
        // RFC 7230: the name is a token, and a value has no line breaks or control characters
        if (!is_string($name) || preg_match("/^[!#$%&'*+.^_`|~0-9A-Za-z-]+$/", $name) !== 1) {
            throw new MessageException("Invalid header name");
        }

        $values = is_array($value) ? array_values($value) : [$value];
        if (empty($values)) {
            throw new MessageException("The header $name has no value");
        }
        foreach ($values as $key => $item) {
            if ((!is_string($item) && !is_int($item) && !is_float($item))
                || preg_match("/^[\x20\x09\x21-\x7E\x80-\xFF]*$/", (string)$item) !== 1
            ) {
                throw new MessageException("Invalid value for the header $name");
            }
            $values[$key] = trim((string)$item, " \t");
        }

        $lowercase = strtolower($name);
        if (isset($this->headerNames[$lowercase])) {
            if (!$overwrite) {
                // Adding keeps the name as it was first given
                $name = $this->headerNames[$lowercase];
                $values = array_merge($this->headers[$name], $values);
            }
            unset($this->headers[$this->headerNames[$lowercase]]);
        }

        $this->headerNames[$lowercase] = $name;
        $this->headers[$name] = $values;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    #[\Override]
    public function withoutHeader(string $name): static
    {
        $clone = clone $this;
        $lowercase = strtolower($name);
        if (isset($clone->headerNames[$lowercase])) {
            unset($clone->headers[$clone->headerNames[$lowercase]], $clone->headerNames[$lowercase]);
        }
        return $clone;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getBody(): StreamInterface
    {
        if (is_null($this->body)) {
            $this->body = new NullStream();
        }
        $this->body->rewind();
        return $this->body;
    }

    /**
     * @inheritDoc
     * @return $this
     */
    #[\Override]
    public function withBody(StreamInterface $body): static
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }
}
