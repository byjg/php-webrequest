<?php

namespace ByJG\WebRequest\Psr7;


use ByJG\WebRequest\Helper\ExtendedStreamInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

abstract class StreamBase implements StreamInterface, ExtendedStreamInterface
{

    protected mixed $resource;

    public function __construct()
    {
        if ($this->isDetached() || $this->resource === false) {
            throw new RuntimeException("Resource is invalid");
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }
            return $this->getContents();
        } catch (\Throwable) {
            // PSR-7: __toString() must not throw
            return "";
        }
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function close(): void
    {
        $resource = $this->detach();
        if (is_resource($resource)) {
            fclose($resource);
        }
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function detach()
    {
        if ($this->isDetached()) {
            return null;
        }
        $resource = $this->resource;
        unset($this->resource);
        return $resource;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getSize(): ?int
    {
        if (!$this->isDetached()) {
            $stat = fstat($this->resource);
            return $stat !== false ? $stat['size'] : null;
        }
        return null;
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    #[\Override]
    public function tell(): int
    {
        if ($this->isDetached()) {
            throw new RuntimeException("Stream is detached");
        }
        $result = ftell($this->resource);

        if ($result === false) {
            throw new RuntimeException("Cannot return stream position");
        }

        return $result;
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    #[\Override]
    public function eof(): bool
    {
        if ($this->isDetached()) {
            throw new RuntimeException("Stream is detached");
        }
        return feof($this->resource);
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    #[\Override]
    public function isSeekable(): bool
    {
        if ($this->isDetached()) {
            return false;
        }
        return $this->getMetadata('seekable') === true;
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    #[\Override]
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException("Stream is not seekable");
        }
        if (fseek($this->resource, $offset, $whence) === -1) {
            throw new RuntimeException("Cannot seek to position $offset");
        }
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    #[\Override]
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    #[\Override]
    public function isWritable(): bool
    {
        $mode = $this->mode();
        // The first letter is what fopen() honors: "rw" opens a file read only
        return $mode !== '' && (in_array($mode[0], ['w', 'a', 'x', 'c'], true) || str_contains($mode, '+'));
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    #[\Override]
    public function write(string $string): int
    {
        if (!$this->isWritable()) {
            throw new RuntimeException("Stream is not writable");
        }

        $result = fwrite($this->resource, $string);

        if ($result === false) {
            throw new RuntimeException("Could not write to stream");
        }

        fflush($this->resource);

        return $result;
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    #[\Override]
    public function isReadable(): bool
    {
        $mode = $this->mode();
        return $mode !== '' && ($mode[0] === 'r' || str_contains($mode, '+'));
    }

    /**
     * The mode the resource was opened with, or an empty string when the stream is detached
     */
    protected function mode(): string
    {
        if ($this->isDetached()) {
            return '';
        }
        $mode = $this->getMetadata('mode');
        return is_string($mode) ? $mode : '';
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function read(int $length): string
    {
        if (!$this->isReadable()) {
            throw new RuntimeException("Stream is not readable");
        }
        if ($length < 0) {
            throw new RuntimeException("Length must not be negative");
        }
        if (feof($this->resource) || $length === 0) {
            return "";
        }
        $result = fread($this->resource, $length);
        if ($result === false) {
            throw new RuntimeException("Failed to read from stream");
        }
        return $result;
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    #[\Override]
    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new RuntimeException("Stream is not readable");
        }
        $contents = stream_get_contents($this->resource);
        if ($contents === false) {
            throw new RuntimeException("Failed to read from stream");
        }
        return $contents;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getMetadata($key = null)
    {
        if ($this->isDetached()) {
            throw new RuntimeException("Stream is detached");
        }

        $metaData = stream_get_meta_data($this->resource);
        if (is_null($key)) {
            return $metaData;
        } else if (isset($metaData[$key])) {
            return $metaData[$key];
        }

        return null;
    }

    protected function isDetached(): bool
    {
        return (!isset($this->resource) || !is_resource($this->resource));
    }

    /**
     * @param StreamInterface $stream
     */
    #[\Override]
    public function appendStream(StreamInterface $stream): void
    {
        $this->seek(0, SEEK_END);
        $stream->rewind();
        $this->write($stream->getContents());
    }

    /**
     * @param string $filter
     * @param string $mode (r)ead or (w)rite
     */
    #[\Override]
    public function addFilter(string $filter, string $mode = "r"): void
    {
        stream_filter_append($this->resource, $filter, $mode == "r" ? STREAM_FILTER_READ : STREAM_FILTER_WRITE);
    }
}
