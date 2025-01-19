<?php

namespace ByJG\WebRequest;

use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\UploadedFile;
use Psr\Http\Message\StreamInterface;

class MultiPartItem extends UploadedFile
{
    protected bool $base64 = false;

    protected ContentDisposition $contentDisposition = ContentDisposition::formData;

    /**
     * MultiPartItem constructor.
     *
     * @param string $field
     * @param string $content
     * @param string $filename
     * @param string $contentType
     */
    public function __construct(protected string $field, string $content = "", string $filename = "", string $contentType = "")
    {
        parent::__construct($content, strlen($content), clientFilename: $filename, clientMediaType: $contentType);
    }

    /**
     * @param string $filename
     * @param string $contentType
     * @return MultiPartItem
     * @throws FileNotFoundException
     */
    public function loadFile(string $filename, string $contentType = ""): MultiPartItem
    {
        if (!file_exists($filename)) {
            throw new FileNotFoundException("File '$filename' does not found!");
        }

        $this->stream = new MemoryStream(file_get_contents($filename));
        $this->size = $this->stream->getSize();
        $this->clientFilename = basename($filename);
        $this->clientMediaType = $contentType;

        return $this;
    }

    public function withEncodedBase64(): MultiPartItem
    {
        $this->base64 = true;
        return $this;
    }

    /**
     * @param ContentDisposition $type
     * @return $this
     */
    public function withContentDisposition(ContentDisposition $type): MultiPartItem
    {
        $this->contentDisposition = $type;
        return $this;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getContent(): string
    {
        $this->stream->rewind();
        if ($this->isBase64()) {
            return base64_encode($this->stream->getContents());
        }
        return $this->stream->getContents();
    }

    public function withField($field): MultiPartItem
    {
        $this->field = $field;
        return $this;
    }

    public function withContent($content): MultiPartItem
    {
        $this->stream = new MemoryStream($content);
        return $this;
    }

    public function withFilename($filename): MultiPartItem
    {
        $this->clientFilename = $filename;
        return $this;
    }

    public function withContentType($contentType): MultiPartItem
    {
        $this->clientMediaType = $contentType;
        return $this;
    }

    /**
     * @return bool
     */
    public function isBase64(): bool
    {
        return $this->base64;
    }

    /**
     * @return ContentDisposition
     */
    public function getContentDisposition(): ContentDisposition
    {
        return $this->contentDisposition;
    }
    
    public function build(StreamInterface $stream, $boundary): void
    {
        $stream->write("--$boundary\nContent-Disposition: {$this->getContentDisposition()->value}; name=\"{$this->getField()}\";");
        $fileName = $this->getClientFilename();
        if (!empty($fileName)) {
            $stream->write(" filename=\"{$this->getClientFilename()}\";");
        }
        $contentType = $this->getClientMediaType();
        if (!empty($contentType)) {
            $stream->write("\nContent-Type: {$this->getClientMediaType()}");
        }
        if ($this->isBase64()) {
            $stream->write("\nContent-Transfer-Encoding: base64");
        }
        $stream->write("\n\n{$this->getContent()}\n");
    }
}
