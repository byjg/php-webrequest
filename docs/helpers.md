---
sidebar_position: 5
title: Helper Classes
description: Helper classes for JSON, Form URL Encoded, and MultiPart requests
---

# Helper Classes

The WebRequest package provides several helper classes to simplify creating common request types. These helper classes make it easy to create requests for specific content types and use cases.

## Request JSON

The `RequestJson` helper allows you to easily create JSON requests.

```php
<?php
// Create a URI
$uri = \ByJG\Util\Uri::getInstanceFromString('http://api.example.com/users');

// Create a JSON request with RequestJson helper
$request = \ByJG\WebRequest\Helper\RequestJson::build(
    $uri,                    // The URI
    'POST',                  // HTTP method
    '{"name": "John Doe"}'   // JSON string (can also be an array)
);

// Send the request
$response = \ByJG\WebRequest\HttpClient::getInstance()->sendRequest($request);
```

You can also pass an associative array instead of a JSON string:

```php
<?php
$request = \ByJG\WebRequest\Helper\RequestJson::build(
    $uri,
    'POST',
    [
        'name' => 'John Doe',
        'email' => 'john@example.com'
    ]
);
```

## Request Form URL Encoded

The `RequestFormUrlEncoded` helper allows you to create requests with `application/x-www-form-urlencoded` content type, which is commonly used for form submissions.

```php
<?php
// Create a URI
$uri = \ByJG\Util\Uri::getInstanceFromString('http://example.com/login');

// Create a form URL encoded request
$request = \ByJG\WebRequest\Helper\RequestFormUrlEncoded::build(
    $uri,
    [
        'username' => 'johndoe',
        'password' => 'secret123'
    ]
);

// Send the request
$response = \ByJG\WebRequest\HttpClient::getInstance()->sendRequest($request);
```

The method uses HTTP POST by default.

## Request Multi Part

The `RequestMultiPart` helper creates requests with `multipart/form-data` content type, which is used for file uploads and forms with binary data.

```php
<?php
// Create a URI
$uri = \ByJG\Util\Uri::getInstanceFromString('http://example.com/upload');

// Create MultiPartItem objects for each form field
$items = [];

// Add a simple text field
$items[] = new \ByJG\WebRequest\MultiPartItem('field1', 'value1');

// Add a JSON value with a specific filename and content type
$items[] = new \ByJG\WebRequest\MultiPartItem(
    'document',
    '{"key": "value"}',
    'data.json',
    'application/json; charset=UTF-8'
);

// Add a file from disk
$items[] = new \ByJG\WebRequest\MultiPartItem(
    'avatar',
    file_get_contents('/path/to/image.jpg'),
    'profile.jpg',
    'image/jpeg'
);

// Create the multipart request
$request = \ByJG\WebRequest\Helper\RequestMultiPart::build(
    $uri,
    'POST',
    $items
);

// Send the request
$response = \ByJG\WebRequest\HttpClient::getInstance()->sendRequest($request);
```

## MultiPartItem Class

The `MultiPartItem` class represents a single item in a multipart request:

```php
<?php
/**
 * Create a MultiPartItem
 * 
 * @param string $name          The form field name
 * @param string $value         The field value or file contents
 * @param string|null $filename The filename (for file uploads)
 * @param string|null $mimeType The content type of the file
 */
$item = new \ByJG\WebRequest\MultiPartItem(
    'fieldName',
    'fieldValue',
    'filename.txt',  // Optional, for file uploads
    'text/plain'     // Optional, defaults to 'application/octet-stream'
);
```

You can also set a custom Content-Disposition:

```php
<?php
$item = new \ByJG\WebRequest\MultiPartItem('field', 'value');
$item->setContentDisposition(\ByJG\WebRequest\ContentDisposition::FORM_DATA);
```

## HTTP Status and Methods

The package also provides constants for HTTP status codes and methods:

```php
<?php
// Use HTTP method constants
$method = \ByJG\WebRequest\HttpMethod::POST;

// Check for specific status code
if ($response->getStatusCode() === \ByJG\WebRequest\HttpStatus::NOT_FOUND) {
    echo "Resource not found!";
}
```

### Available HTTP Methods

The `HttpMethod` enum provides constants for all standard HTTP methods:

- `GET`, `POST`, `PUT`, `PATCH`, `DELETE`
- `HEAD`, `OPTIONS`, `CONNECT`, `TRACE`

### Common HTTP Status Codes

The `HttpStatus` class provides constants for all standard HTTP status codes, including:

- **2xx Success**: `OK` (200), `CREATED` (201), `NO_CONTENT` (204)
- **3xx Redirection**: `MOVED_PERMANENTLY` (301), `FOUND` (302)
- **4xx Client Errors**: `BAD_REQUEST` (400), `UNAUTHORIZED` (401), `FORBIDDEN` (403), `NOT_FOUND` (404)
- **5xx Server Errors**: `INTERNAL_SERVER_ERROR` (500), `BAD_GATEWAY` (502), `SERVICE_UNAVAILABLE` (503) 