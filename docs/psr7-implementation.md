---
sidebar_position: 1
title: PSR-7 and PSR-17 Implementation
description: Complete PSR-7 HTTP Message and PSR-17 HTTP Factories implementation
---

# PSR-7 and PSR-17 Implementation

:::info PSR Standards Compliance
This package provides **complete implementations** of:
- ✅ **[PSR-7: HTTP Message Interface](https://www.php-fig.org/psr/psr-7/)** - Standard interfaces for HTTP messages
- ✅ **[PSR-17: HTTP Factories](https://www.php-fig.org/psr/psr-17/)** - Standard factories for creating PSR-7 objects
:::

WebRequest implements the [PSR-7 HTTP Message Interface](https://www.php-fig.org/psr/psr-7/) specification, which provides interfaces for HTTP messages (requests and responses) and their components.

## Components

### Message Classes

| Class | Description | Implements |
|-------|-------------|------------|
| **Message** | Base implementation for requests and responses | PSR-7 MessageInterface |
| **Request** | HTTP request implementation | PSR-7 RequestInterface |
| **Response** | HTTP response implementation | PSR-7 ResponseInterface |
| **ServerRequest** | Server-side HTTP request | PSR-7 ServerRequestInterface |

Headers keep the name in the case it was first given (`getHeaders()` returns `content-type` if it was set
as `content-type`), and are still read in any case. A header name must be an RFC 7230 token and a value cannot
have line breaks, otherwise `MessageException`, an `InvalidArgumentException`, is thrown. The method is
case-sensitive and can be any token (`PROPFIND`, `PURGE`...), not only the ones in `HttpMethod`. A URI without
a host keeps the `Host` header the request had.

`ServerRequest` holds only what it is given, as PSR-7 requires: `new ServerRequest()` and
`ServerRequestFactory` do not read PHP's globals.

#### Parsed body

`getParsedBody()` returns what `withParsedBody()` set. When nothing was set, it parses the request's own body
by its `Content-Type`:

| Content-Type                                                          | Parsed with                                    | Result                                     |
|-----------------------------------------------------------------------|------------------------------------------------|--------------------------------------------|
| `application/json`, `*+json`                                          | `json_decode()`                                | associative array                          |
| `application/x-www-form-urlencoded`                                   | `parse_str()`                                  | the fields                                 |
| `multipart/form-data`                                                 | `MultiPartItem::parseBody()`                   | the fields, as PHP fills `$_POST`          |
| `application/xml`, `text/xml`, `*+xml`                                | `Serialize::fromXml()` (byjg/serializer)       | array, without the root element            |
| `application/yaml`, `application/x-yaml`, `text/yaml`, `text/x-yaml`  | `Serialize::fromYaml()`                        | array                                      |
| `text/csv`, `application/csv`                                         | `Serialize::fromCsv()`                         | list of rows, keyed by the header row      |

PSR-7 allows only `null`, an array or an object as the parsed body, so any other `Content-Type` (`text/plain`,
`application/octet-stream`...), an invalid body or a JSON or YAML scalar (`"text"`, `abc`) gives `null`; read it
with `getBody()`. `parseBody()` does the same parsing but throws `MessageException`, an
`InvalidArgumentException`, saying why there is no parsed body. For a YAML scalar, the serializer's `TypeError`
is its previous exception:

```php
<?php
$request->getParsedBody();  // null for text/plain
$request->parseBody();      // MessageException: The Content-Type 'text/plain' has no parsed body: ...
```

`withParsedBody()` accepts `null`, an array or an object (`MessageException` otherwise). For JSON,
urlencoded, XML, YAML and CSV it also writes the body from the data, so both describe the same request; for
any other `Content-Type` (multipart...) the body stays as it is.

#### fromGlobals()

`ServerRequest::fromGlobals()` builds the current request from the globals: the method, URI, headers and
server params from `$_SERVER`, the cookies from `$_COOKIE`, the uploaded files from `$_FILES`, and, for a
`POST` of a form (`application/x-www-form-urlencoded` or `multipart/form-data`), `$_POST` as the parsed body.
The body is a copy of `php://input`, or the one you give, as a stream or a string (a worker in the CLI, where
`php://input` is empty, or a test):

```php
<?php
$request = \ByJG\WebRequest\Psr7\ServerRequest::fromGlobals();
$request = \ByJG\WebRequest\Psr7\ServerRequest::fromGlobals('{"name":"John"}');
```

`UploadedFile::parseFilesGlobal()` turns `$_FILES` into `UploadedFile` objects whose stream reads the
temporary file when used. `moveTo()` moves a file on disk instead of copying it, and can be called once.

### Stream Implementations

| Class | Description | Use Case |
|-------|-------------|----------|
| **StreamBase** | Base stream implementation | Base class for all streams |
| **MemoryStream** | In-memory stream | String data, JSON payloads |
| **FileStream** | File-based stream | Reading from files |
| **TempFileStream** | Temporary file stream | Large data that shouldn't stay in memory |
| **ResourceStream** | Stream over a resource opened elsewhere | `fopen()` results, `StreamFactory::createStreamFromResource()` |
| **NullStream** | Discards all data | Testing, ignoring response bodies |
| **UploadedFile** | Uploaded file handler | Processing file uploads |

## Usage Examples

### Creating a Request

```php
<?php
$uri = \ByJG\Util\Uri::getInstance('http://www.example.com/page');
$request = \ByJG\WebRequest\Psr7\Request::getInstance($uri);
```

### Creating a Response

```php
<?php
$response = new \ByJG\WebRequest\Psr7\Response(200);
$response = $response->withBody(new \ByJG\WebRequest\Psr7\MemoryStream('{"key":"value"}'));
```

### Working with Streams

```php
<?php
// Create a memory stream
$stream = new \ByJG\WebRequest\Psr7\MemoryStream('Hello world');
echo $stream->getContents(); // "Hello world"

// Create a file stream
$stream = new \ByJG\WebRequest\Psr7\FileStream('/path/to/file.txt');
```

Every stream extending `StreamBase` also implements `ByJG\WebRequest\Helper\ExtendedStreamInterface`:

```php
<?php
$stream = new \ByJG\WebRequest\Psr7\MemoryStream('Hello ');

// Append the contents of another stream at the end
$stream->appendStream(new \ByJG\WebRequest\Psr7\MemoryStream('world'));

// Attach a PHP stream filter, applied on read ("r", default) or on write ("w")
$stream->addFilter('string.toupper', 'r');
```

## PSR-17 Factory Implementation

WebRequest also implements [PSR-17 HTTP Factories](https://www.php-fig.org/psr/psr-17/) for creating PSR-7 objects:

- **RequestFactory**: Creates Request objects
- **ResponseFactory**: Creates Response objects
- **ServerRequestFactory**: Creates ServerRequest objects
- **StreamFactory**: Creates Stream objects. `createStreamFromResource()` uses the resource itself, at its current
  position (it is not copied, and closing the stream closes it), and `createStreamFromFile()` opens the file with
  the mode given (`r` by default, so the stream is read only)
- **UploadedFileFactory**: Creates UploadedFile objects
- **UriFactory**: Creates Uri objects. It is `ByJG\Util\UriFactory`, from [byjg/uri](https://github.com/byjg/php-uri),
  which WebRequest already requires

:::tip
PSR-17 factories provide a standardized way to create PSR-7 objects, making your code more interoperable with other PSR-compliant libraries.
:::

Usage example:

```php
<?php
$factory = new \ByJG\WebRequest\Factory\RequestFactory();
$request = $factory->createRequest('GET', 'http://example.com');
``` 