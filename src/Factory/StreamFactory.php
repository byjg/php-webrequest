<?php

namespace ByJG\WebRequest\Factory;

use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\ResourceStream;
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
        if ($filename === '') {
            throw new RuntimeException("The file name is empty");
        }

        $resource = @fopen($filename, $mode);
        if ($resource === false) {
            throw new RuntimeException(error_get_last()['message'] ?? "Failed to open file: $filename");
        }

        return new ResourceStream($resource);
    }

    /**
     * The stream uses the resource as it is, at its current position, and closing the stream closes it.
     *
     * @inheritDoc
     */
    #[\Override]
    public function createStreamFromResource($resource): StreamInterface
    {
        if (!is_resource($resource)) {
            throw new RuntimeException("Cannot open resource");
        }

        return new ResourceStream($resource);
    }

    public static function instance(string $class = MemoryStream::class): StreamFactoryInterface
    {
        return new StreamFactory($class);
    }
}