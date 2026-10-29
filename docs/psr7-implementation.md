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

`ServerRequest` falls back to PHP's globals for what the request does not set: the server params
(`$_SERVER`), the cookies (`$_COOKIE`) and the uploaded files (`$_FILES`). `getParsedBody()` decodes a JSON
or `application/x-www-form-urlencoded` body; for `multipart/form-data` it returns `$_POST`, as PSR-7
requires, since PHP already parsed that body. `withParsedBody()` encodes the data into the body for those
same JSON and form content types; for any other, the body stays as it is.

`UploadedFile::parseFilesGlobal()` turns `$_FILES` into `UploadedFile` objects whose stream reads the
temporary file when used. `moveTo()` moves a file on disk instead of copying it, and can be called once.

### Stream Implementations

| Class | Description | Use Case |
|-------|-------------|----------|
| **StreamBase** | Base stream implementation | Base class for all streams |
| **MemoryStream** | In-memory stream | String data, JSON payloads |
| **FileStream** | File-based stream | Reading from files |
| **TempFileStream** | Temporary file stream | Large data that shouldn't stay in memory |
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
- **StreamFactory**: Creates Stream objects
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