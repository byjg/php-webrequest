<?php

namespace ByJG\WebRequest\Factory;

use ByJG\WebRequest\Psr7\MemoryStream;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class StreamFactory implements StreamFactoryInterface
{
    public function __construct(
        protected string $className = MemoryStream::class
    )
    {
        if (!is_subclass_of($this->className, StreamInterface::class)) {
            throw new RuntimeException("Invalid class");
        }
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function createStream(string $content = ''): StreamInterface
    {
        /** @var StreamInterface $stream */
        $stream = new ($this->className)();
        if (!empty($content)) {
            $stream->write($content);
            $stream->rewind();
        }
        return $stream;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: $filename");
        }

        $resource = fopen($filename, $mode);
        if ($resource === false) {
            throw new RuntimeException("Failed to open file: $filename");
        }

        return $this->createStreamFromResource($resource);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function createStreamFromResource($resource): StreamInterface
    {
        if (!is_resource($resource)) {
            throw new RuntimeException("Cannot open resource");
        }

        $content = '';
        while ($buffer = fread($resource, 8192)) {
            $content .= $buffer;
        }
        $stream = $this->createStream($content);
        fclose($resource);
        return $stream;
    }

    public static function instance(string $class = MemoryStream::class): StreamFactoryInterface
    {
        return new StreamFactory($class);
    }
}