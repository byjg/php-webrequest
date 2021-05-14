<?php


namespace ByJG\Util\Psr7;


use Psr\Http\Message\StreamInterface;
use \RuntimeException;

abstract class StreamBase implements StreamInterface
{

    protected $resource;

    public function __construct()
    {
        if ($this->isDetached() || $this->resource === false) {
            throw new RuntimeException("Resource is invalid");
        }
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        if ($this->isDetached()) {
            return "";
        }

        if ($this->getSize() === 0) {
            return "";
        }

        if ($this->isSeekable()) {
            $this->rewind();
        }

        return $this->getContents();
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        if (!$this->isDetached()) {
            return;
        }
        fclose($this->resource);
        unset($this->resource);
    }

    /**
     * @inheritDoc
     */
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
    public function getSize()
    {
        if (!$this->isDetached()) {
            return (int)fstat($this->resource)['size'];
        }
        return null;
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function tell()
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
    public function eof()
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
    public function isSeekable()
    {
        if ($this->isDetached()) {
            throw new RuntimeException("Stream is detached");
        }
        return $this->getMetadata('seekable');
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException("Stream is not seekable");
        }
        fseek($this->resource, $offset, $whence);
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function rewind()
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException("Stream is not seekable");
        }
        rewind($this->resource);
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function isWritable()
    {
        if ($this->isDetached()) {
            throw new RuntimeException("Stream is detached");
        }
        return stristr($this->getMetadata('mode'), 'w') !== false;
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function write($string)
    {
        if (!$this->isWritable()) {
            throw new RuntimeException("Stream is not writable");
        }
        return fwrite($this->resource, $string);
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function isReadable()
    {
        if ($this->isDetached()) {
            throw new RuntimeException("Stream is detached");
        }
        $mode = $this->getMetadata('mode');
        return stristr($mode, 'w+') !== false
            || stristr($mode, 'r') !== false;
    }

    /**
     * @inheritDoc
     */
    public function read($length)
    {
        if ($this->isDetached()) {
            throw new RuntimeException("Stream is detached");
        }
        return fread($this->resource, $length);
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function getContents()
    {
        return $this->read($this->getSize() - $this->tell());
    }

    /**
     * @inheritDoc
     */
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

    protected function isDetached()
    {
        return (!isset($this->resource) || !is_resource($this->resource));
    }
}