<?php

namespace ByJG\WebRequest\Psr7;

use ByJG\WebRequest\Helper\ExtendedStreamInterface;
use Psr\Http\Message\StreamInterface;

class NullStream implements ExtendedStreamInterface, StreamInterface
{

    #[\Override]
    function appendStream(StreamInterface $stream): void
    {
        // TODO: Implement appendStream() method.
    }

    #[\Override]
    function addFilter($filter, string $mode = "r"): void
    {
        // TODO: Implement addFilter() method.
    }

    public function __toString(): string
    {
        return "";
    }

    #[\Override]
    public function close(): void
    {
        // TODO: Implement close() method.
    }

    #[\Override]
    public function detach()
    {
        // TODO: Implement detach() method.
    }

    #[\Override]
    public function getSize(): ?int
    {
        return 0;
    }

    #[\Override]
    public function tell(): int
    {
        return 0;
    }

    #[\Override]
    public function eof(): bool
    {
        return true;
    }

    #[\Override]
    public function isSeekable(): bool
    {
        return true;
    }

    #[\Override]
    public function seek($offset, $whence = SEEK_SET): void
    {
        // Nothing to do
    }

    #[\Override]
    public function rewind(): void
    {
        // Nothing to do
    }

    #[\Override]
    public function isWritable(): bool
    {
        return true;
    }

    #[\Override]
    public function write($string): int
    {
        return 0;
    }

    #[\Override]
    public function isReadable(): bool
    {
        return true;
    }

    #[\Override]
    public function read($length): string
    {
        return "";
    }

    #[\Override]
    public function getContents(): string
    {
        return "";
    }

    #[\Override]
    public function getMetadata($key = null)
    {
        // TODO: Implement getMetadata() method.
    }
}
