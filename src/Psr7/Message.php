<?php


namespace ByJG\WebRequest\Psr7;

use ByJG\WebRequest\Exception\MessageException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

class Message implements MessageInterface
{
    protected string $protocolVersion = "1.1";
    protected array $headers = [];

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
     * @throws MessageException
     */
    #[\Override]
    public function withProtocolVersion($version): MessageInterface
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
        return (isset($this->headers[$this->normalize($name)]));
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getHeader(string $name): array
    {
        if ($this->hasHeader($name)) {
            return $this->headers[$this->normalize($name)];
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
    public function withHeader(string $name, $value): MessageInterface
    {
        $clone = clone $this;
        $clone->setHeader($name, $value, true);
        return $clone;
    }

    /**
     * @inheritDoc
     * @throws MessageException
     */
    #[\Override]
    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $clone = clone $this;
        $clone->setHeader($name, $value, false);
        return $clone;
    }

    /**
     * @param $name
     * @param $value
     * @param $overwrite
     * @throws MessageException
     */
    protected function setHeader($name, $value, $overwrite): void
    {
        if (!is_string($value) && !is_array($value)) {
            throw new MessageException("Invalid Header Value");
        }

        $value = (array)$value;

        if (!$overwrite) {
            $value = array_merge($this->getHeader($name), $value);
        }

        $this->headers[$this->normalize($name)] = $value;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function withoutHeader(string $name): MessageInterface
    {
        $clone = clone $this;
        if ($clone->hasHeader($name)) {
            unset($clone->headers[$this->normalize($name)]);
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
    public function withBody(StreamInterface $body): MessageInterface
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    protected function normalize($header): string
    {
        return str_replace(" ", "-", ucwords(str_replace("-", " ", strtolower($header))));
    }
}
