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
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if (!file_exists($filename)) {
            throw new RuntimeException("File not found: $filename");
        }

        return $this->createStreamFromResource(fopen($filename, $mode));
    }

    /**
     * @inheritDoc
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        if (!is_resource($resource)) {
            throw new RuntimeException("Cannot open resource");
        }

        $stream = $this->createStream(file_get_contents($resource));
        fclose($resource);
        return $stream;
    }

    public static function instance(string $class = MemoryStream::class): StreamFactoryInterface
    {
        return new StreamFactory($class);
    }
}