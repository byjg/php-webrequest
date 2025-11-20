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
$uri = \ByJG\Util\Uri::getInstanceFromString('http://www.example.com/page');
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

## PSR-17 Factory Implementation

WebRequest also implements [PSR-17 HTTP Factories](https://www.php-fig.org/psr/psr-17/) for creating PSR-7 objects:

- **RequestFactory**: Creates Request objects
- **ResponseFactory**: Creates Response objects
- **ServerRequestFactory**: Creates ServerRequest objects
- **StreamFactory**: Creates Stream objects
- **UploadedFileFactory**: Creates UploadedFile objects

:::tip
PSR-17 factories provide a standardized way to create PSR-7 objects, making your code more interoperable with other PSR-compliant libraries.
:::

Usage example:

```php
<?php
$factory = new \ByJG\WebRequest\Factory\RequestFactory();
$request = $factory->createRequest('GET', 'http://example.com');
``` 