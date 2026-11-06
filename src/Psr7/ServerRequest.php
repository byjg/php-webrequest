<?php

namespace ByJG\WebRequest\Psr7;

use ByJG\Serializer\Exception\InvalidArgumentException as SerializerException;
use ByJG\Serializer\Serialize;
use ByJG\Util\Uri;
use ByJG\WebRequest\Exception\MessageException;
use ByJG\WebRequest\Exception\RequestException;
use ByJG\WebRequest\MultiPartItem;
use JsonException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Throwable;
use TypeError;

/**
 * A server request holds only what it is given (PSR-7). fromGlobals() builds the current request from
 * PHP's globals. Without a parsed body given, getParsedBody() parses the request's own body by its
 * Content-Type (see parseBody()).
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    protected array $files = [];
    protected null|array|object $parsedBody = null;
    /** withParsedBody() was called: the parsed body is what it was given, even null */
    protected bool $hasParsedBody = false;
    protected array $attributes = [];

    public function __construct(
        ?Uri $uri = null,
        protected array $serverParams = [],
        protected array $cookieParams = [],
    )
    {
        parent::__construct($uri ?? new Uri());
    }

    /**
     * The current request: the method, URI and headers from $_SERVER, the cookies from $_COOKIE and the
     * uploaded files from $_FILES. For a POST of a form (urlencoded or multipart), the parsed body is $_POST.
     *
     * @param StreamInterface|string|null $body the body, when it does not come from php://input (a worker
     *        in the CLI, a test...). A stream is used as it is, and a string is put in a MemoryStream.
     * @return ServerRequest
     * @throws RequestException
     */
    public static function fromGlobals(StreamInterface|string|null $body = null): ServerRequest
    {
        $server = $_SERVER;
        $scheme = !empty($server['HTTPS']) && strtolower($server['HTTPS']) !== 'off' ? 'https' : 'http';
        $host = $server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? 'localhost';
        $uri = new Uri("$scheme://$host" . ($server['REQUEST_URI'] ?? '/'));

        $request = (new ServerRequest($uri, $server, $_COOKIE))
            ->withMethod($server['REQUEST_METHOD'] ?? 'GET')
            ->withUploadedFiles(UploadedFile::parseFilesGlobal($_FILES));

        foreach ($server as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (str_starts_with($key, 'HTTP_')) {
                $name = substr($key, 5);
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $name = $key;
            } else {
                continue;
            }
            $request = $request->withHeader(str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name)))), $value);
        }

        if (is_string($body)) {
            $body = new MemoryStream($body);
        } elseif ($body === null) {
            // A seekable copy in php://temp, which goes to disk past 2 MB
            $copy = fopen('php://temp', 'w+');
            $input = fopen('php://input', 'r');
            if ($copy === false || $input === false) {
                throw new MessageException("Cannot read php://input");
            }
            stream_copy_to_stream($input, $copy);
            fclose($input);
            rewind($copy);
            $body = new ResourceStream($copy);
        }
        // PHP parsed a form POST into $_POST; for multipart php://input is empty, so the body cannot be parsed
        if ($request->getMethod() === 'POST'
            && in_array(self::bodyFormat($request->getHeaderLine('Content-Type')), ['form', 'multipart'], true)
        ) {
            $request = $request->withParsedBody($_POST);
        }

        // After withParsedBody(), which writes a urlencoded body: the body is the one received
        return $request->withBody($body);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function withCookieParams(array $cookies): static
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getQueryParams(): array
    {
        parse_str($this->uri->getQuery(), $output);
        parse_str($this->serverParams['QUERY_STRING'] ?? '', $outputServer);

        return array_merge($output, $outputServer);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function withQueryParams(array $query): static
    {
        $clone = clone $this;
        $clone->serverParams['QUERY_STRING'] = http_build_query($query);
        return $clone;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getUploadedFiles(): array
    {
        return $this->files;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $clone = clone $this;
        $clone->files = $uploadedFiles;
        return $clone;
    }

    /**
     * The parsed body given to withParsedBody() (fromGlobals() gives $_POST for a form). When none was
     * given, the body parsed by its Content-Type (see parseBody()), or null when the body is empty, its
     * Content-Type has no array or object form (text/plain...) or it is not valid.
     *
     * @inheritDoc
     */
    #[\Override]
    public function getParsedBody(): null|array|object
    {
        if ($this->hasParsedBody) {
            return $this->parsedBody;
        }
        try {
            return $this->parseBody();
        } catch (MessageException) {
            return null;
        }
    }

    /**
     * The body parsed by its Content-Type:
     *
     * - JSON (application/json, *+json): json_decode(), as an associative array
     * - application/x-www-form-urlencoded: parse_str()
     * - multipart/form-data: the fields, as PHP reads them into $_POST (see MultiPartItem::parseBody())
     * - XML (application/xml, text/xml, *+xml): Serialize::fromXml(), without the root element
     * - YAML (application/yaml, application/x-yaml, text/yaml, text/x-yaml): Serialize::fromYaml()
     * - CSV (text/csv, application/csv): Serialize::fromCsv(), a list of rows keyed by the header row
     *
     * PSR-7 allows only null, an array or an object as the parsed body: a body without that form (text/plain,
     * application/octet-stream...) is read with getBody().
     *
     * @return array|object
     * @throws MessageException when the Content-Type has no array or object form, or the body is not valid
     */
    public function parseBody(): array|object
    {
        $contentType = $this->getHeaderLine('Content-Type');
        $format = self::bodyFormat($contentType);
        $body = (string)$this->getBody();

        try {
            $parsed = match ($format) {
                'json' => json_decode($body, true, flags: JSON_THROW_ON_ERROR),
                'form' => self::parseStr($body),
                'multipart' => MultiPartItem::parseBody($body, $contentType)[0],
                'xml' => Serialize::fromXml($body)->toArray(),
                'yaml' => self::parseYaml($body),
                'csv' => Serialize::fromCsv($body)->toArray(),
                default => throw new MessageException(
                    "The Content-Type '$contentType' has no parsed body: PSR-7 allows only null, an array or an "
                    . "object. Read the body with getBody()"
                ),
            };
        } catch (JsonException|SerializerException|ParseException $exception) {
            throw new MessageException("The body is not valid $format: " . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($parsed) && !is_object($parsed)) {
            throw self::notStructured((string)$format);
        }
        return $parsed;
    }

    /**
     * The serializer refuses a YAML scalar with a TypeError (nothing to serialize). Here the scalar came from
     * the client's body, so it is an invalid argument for a parsed body, as a JSON scalar is.
     */
    private static function parseYaml(string $body): array
    {
        try {
            $serialize = Serialize::fromYaml($body);
        } catch (TypeError $exception) {
            throw self::notStructured('yaml', $exception);
        }
        return $serialize->toArray();
    }

    private static function notStructured(string $format, ?Throwable $previous = null): MessageException
    {
        return new MessageException("The $format body is not an array or an object", 0, $previous);
    }

    private static function parseStr(string $body): array
    {
        parse_str($body, $fields);
        return $fields;
    }

    /**
     * The format of a body with this Content-Type: json, form, multipart, xml, yaml, csv, or null
     */
    private static function bodyFormat(string $contentType): ?string
    {
        $mediaType = strtolower(trim(explode(';', $contentType)[0]));
        return match (true) {
            $mediaType === 'application/json' || str_ends_with($mediaType, '+json') => 'json',
            $mediaType === 'application/x-www-form-urlencoded' => 'form',
            $mediaType === 'multipart/form-data' => 'multipart',
            in_array($mediaType, ['application/xml', 'text/xml'], true) || str_ends_with($mediaType, '+xml') => 'xml',
            in_array($mediaType, ['application/yaml', 'application/x-yaml', 'text/yaml', 'text/x-yaml'], true) => 'yaml',
            in_array($mediaType, ['text/csv', 'application/csv'], true) => 'csv',
            default => null,
        };
    }

    /**
     * For JSON, urlencoded, XML, YAML and CSV the body is written from the data, so both describe the same
     * request; for any other Content-Type (multipart...) the body stays as it is.
     *
     * @inheritDoc
     * @throws MessageException when the data is not null, an array or an object
     */
    #[\Override]
    public function withParsedBody($data): static
    {
        if (!is_null($data) && !is_array($data) && !is_object($data)) {
            throw new MessageException("The parsed body must be null, an array or an object");
        }

        $format = self::bodyFormat($this->getHeaderLine('Content-Type'));
        $content = match (true) {
            $format === 'json' => json_encode($data),
            $format === 'form' => http_build_query($data ?? []),
            $data === null => null,
            $format === 'xml' => Serialize::from($data)->toXml(),
            $format === 'yaml' => Serialize::from($data)->toYaml(),
            $format === 'csv' => Serialize::from($data)->toCsv(),
            default => null,
        };
        if ($content === false) {
            throw new MessageException("The parsed body cannot be written as $format");
        }

        $clone = is_string($content) ? $this->withBody(new MemoryStream($content)) : clone $this;
        $clone->parsedBody = $data;
        $clone->hasParsedBody = true;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function getAttribute(string $name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function withAttribute(string $name, $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function withoutAttribute(string $name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }
}