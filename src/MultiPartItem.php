<?php

namespace ByJG\Util;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;

class MultiPartItem
{
    protected $field;

    protected $content;

    protected $filename;

    protected $contentType;

    protected $base64 = false;

    protected $contentDisposition = "form-data";

    /**
     * MultiPartItem constructor.
     *
     * @param $field
     * @param $content
     * @param $filename
     * @param $contentType
     */
    public function __construct($field, $content = "", $filename = "", $contentType = "")
    {
        $this->field = $field;
        $this->content = $content;
        $this->filename = $filename;
        $this->contentType = $contentType;
    }

    /**
     * @param $filename
     * @param string $contentType
     * @return MultiPartItem
     * @throws FileNotFoundException
     */
    public function loadFile($filename, $contentType = "")
    {
        if (!file_exists($filename)) {
            throw new FileNotFoundException("File '$filename' does not found!");
        }

        $this->content = file_get_contents($filename);
        $this->filename = basename($filename);
        $this->contentType = $contentType;

        return $this;
    }

    public function withEncodedBase64()
    {
        $this->base64 = true;
        return $this;
    }

    /**
     * @param $type
     * @return $this
     */
    public function withContentDisposition($type)
    {
        $validTypes = ["form-data", "inline", "attachment"];
        if (!in_array($type, $validTypes)) {
            throw new InvalidArgumentException("Only '" . implode("', '", $validTypes) . "' are accepted.");
        }
        $this->contentDisposition = $type;
        return $this;
    }

    public function getField()
    {
        return $this->field;
    }

    public function getContent()
    {
        if ($this->isBase64()) {
            return base64_encode($this->content);
        }
        return $this->content;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function withField($field)
    {
        $this->field = $field;
        return $this;
    }

    public function withContent($content)
    {
        $this->content = $content;
        return $this;
    }

    public function withFilename($filename)
    {
        $this->filename = $filename;
        return $this;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function withContentType($contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }

    /**
     * @return bool
     */
    public function isBase64()
    {
        return $this->base64;
    }

    /**
     * @return string
     */
    public function getContentDisposition()
    {
        return $this->contentDisposition;
    }
    
    public function build(StreamInterface $stream, $boundary)
    {
        $stream->write("--$boundary\nContent-Disposition: {$this->getContentDisposition()}; name=\"{$this->getField()}\";");
        $fileName = $this->getFileName();
        if (!empty($fileName)) {
            $stream->write(" filename=\"{$this->getFileName()}\";");
        }
        $contentType = $this->getContentType();
        if (!empty($contentType)) {
            $stream->write("\nContent-Type: {$this->getContentType()}");
        }
        if ($this->isBase64()) {
            $stream->write("\nContent-Transfer-Encoding: base64");
        }
        $stream->write("\n\n{$this->getContent()}\n");
    }
}
