<?php


namespace ByJG\Util\Psr7;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

class Message implements MessageInterface
{
    protected $protocolVersion = "1.1";
    protected $headers = [];

    /**
     * @var StreamInterface
     */
    protected $body = null;

    /**
     * @inheritDoc
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * @inheritDoc
     * @throws MessageException
     */
    public function withProtocolVersion($version)
    {
        if ($version != "1.0" && $version != "1.1") {
            throw new MessageException("Invalid Protocol Version");
        }
        $this->protocolVersion = $version;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function hasHeader($name)
    {
        return (isset($this->headers[$this->normalize($name)]));
    }

    /**
     * @inheritDoc
     */
    public function getHeader($name)
    {
        if ($this->hasHeader($name)) {
            return $this->headers[$this->normalize($name)];
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine($name)
    {
        return implode(",", $this->getHeader($name));
    }

    /**
     * @inheritDoc
     * @throws MessageException
     */
    public function withHeader($name, $value)
    {
        $this->setHeader($name, $value, true);
        return $this;
    }

    /**
     * @inheritDoc
     * @throws MessageException
     */
    public function withAddedHeader($name, $value)
    {
        $this->setHeader($name, $value, false);
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @param $overwrite
     * @throws MessageException
     */
    protected function setHeader($name, $value, $overwrite)
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
    public function withoutHeader($name)
    {
        if ($this->hasHeader($name)) {
            unset($this->headers[$this->normalize($name)]);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        if (!is_null($this->body)) {
            $this->body->rewind();
        }
        return $this->body;
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body)
    {
        $this->body = $body;
        return $this;
    }

    protected function normalize($header)
    {
        return str_replace(" ", "-", ucwords(str_replace("-", " ", strtolower($header))));
    }
}