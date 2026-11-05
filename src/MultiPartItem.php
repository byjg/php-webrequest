<?php

namespace ByJG\WebRequest;

use ByJG\WebRequest\Psr7\MemoryStream;
use ByJG\WebRequest\Psr7\UploadedFile;
use Psr\Http\Message\StreamInterface;

class MultiPartItem extends UploadedFile
{
    protected bool $base64 = false;

    protected ContentDisposition $contentDisposition = ContentDisposition::FormData;

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
    
    /**
     * Write the part to a multipart body. Lines end with CRLF (RFC 7578, RFC 2046).
     */
    public function build(StreamInterface $stream, $boundary): void
    {
        $stream->write("--$boundary\r\nContent-Disposition: {$this->getContentDisposition()->value}; name=\"{$this->getField()}\";");
        $fileName = $this->getClientFilename();
        if (!empty($fileName)) {
            $stream->write(" filename=\"{$this->getClientFilename()}\";");
        }
        $contentType = $this->getClientMediaType();
        if (!empty($contentType)) {
            $stream->write("\r\nContent-Type: {$this->getClientMediaType()}");
        }
        if ($this->isBase64()) {
            $stream->write("\r\nContent-Transfer-Encoding: base64");
        }
        $stream->write("\r\n\r\n{$this->getContent()}\r\n");
    }

    /**
     * The fields and the files of a multipart body, the reverse of build(): for a body PHP did not parse
     * (a request built in a test, a PSR-7 request without a parsed body). The fields are read as PHP reads
     * them into $_POST ("tags[]" is a list); the files are by their field name, as MultiPartItem objects.
     * Lines may end with CRLF or LF.
     *
     * @param string $body
     * @param string $contentType the Content-Type header, with the boundary
     * @return array{array<array-key, mixed>, array<string, MultiPartItem>}
     */
    public static function parseBody(string $body, string $contentType): array
    {
        if (preg_match('/boundary="?([^";]+)"?/i', $contentType, $match) !== 1) {
            return [[], []];
        }

        $query = [];
        $files = [];
        $parts = preg_split('/(?:\r?\n)?--' . preg_quote(trim($match[1]), '/') . '(?:--)?[ \t]*(?:\r?\n|$)/', $body) ?: [];
        foreach ($parts as $part) {
            $sections = preg_split('/\r?\n\r?\n/', $part, 2);
            if ($sections === false || count($sections) < 2) {
                continue;
            }
            [$head, $content] = $sections;
            if (preg_match('/\bname="([^"]*)"/i', $head, $name) !== 1) {
                continue;
            }
            if (preg_match('/^Content-Transfer-Encoding:\s*base64\s*$/im', $head) === 1) {
                $content = (string)base64_decode($content);
            }
            if (preg_match('/\bfilename="([^"]*)"/i', $head, $filename) === 1) {
                $type = preg_match('/^Content-Type:\s*([^\r\n]+)/im', $head, $typeMatch) === 1 ? trim($typeMatch[1]) : '';
                $files[$name[1]] = new MultiPartItem($name[1], $content, $filename[1], $type);
                continue;
            }
            $query[] = rawurlencode($name[1]) . '=' . rawurlencode($content);
        }

        parse_str(implode('&', $query), $fields);
        return [$fields, $files];
    }
}
