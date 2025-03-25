---
sidebar_position: 1
---

# PSR-7 Implementation

WebRequest implements the [PSR-7 HTTP Message Interface](https://www.php-fig.org/psr/psr-7/) specification, which provides interfaces for HTTP messages (requests and responses) and their components.

## Components

### Message Classes

- **Message**: Base implementation for requests and responses
- **Request**: Implementation of the PSR-7 RequestInterface
- **Response**: Implementation of the PSR-7 ResponseInterface
- **ServerRequest**: Implementation of the PSR-7 ServerRequestInterface

### Stream Implementations

- **StreamBase**: Base implementation of the PSR-7 StreamInterface
- **MemoryStream**: Stream implementation for in-memory data
- **FileStream**: Stream implementation for file data
- **TempFileStream**: Stream implementation for temporary files
- **NullStream**: Stream implementation that discards all data
- **UploadedFile**: Implementation of the PSR-7 UploadedFileInterface

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

Usage example:

```php
<?php
$factory = new \ByJG\WebRequest\Factory\RequestFactory();
$request = $factory->createRequest('GET', 'http://example.com');
``` 