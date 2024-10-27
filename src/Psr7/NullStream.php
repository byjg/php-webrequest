<?php

namespace ByJG\WebRequest\Psr7;

use ByJG\WebRequest\Helper\ExtendedStreamInterface;
use Psr\Http\Message\StreamInterface;

class NullStream implements ExtendedStreamInterface, StreamInterface
{

    function appendStream(StreamInterface $stream): void
    {
        // TODO: Implement appendStream() method.
    }

    function addFilter($filter, string $mode = "r"): void
    {
        // TODO: Implement addFilter() method.
    }

    public function __toString(): string
    {
        return "";
    }

    public function close(): void
    {
        // TODO: Implement close() method.
    }

    public function detach()
    {
        // TODO: Implement detach() method.
    }

    public function getSize(): ?int
    {
        return 0;
    }

    public function tell(): int
    {
        return 0;
    }

    public function eof(): bool
    {
        return true;
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        // Nothing to do
    }

    public function rewind(): void
    {
        // Nothing to do
    }

    public function isWritable(): bool
    {
        return true;
    }

    public function write($string): int
    {
        return 0;
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        return "";
    }

    public function getContents(): string
    {
        return "";
    }

    public function getMetadata($key = null)
    {
        // TODO: Implement getMetadata() method.
    }
}
