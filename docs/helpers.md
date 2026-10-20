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

The request always uses the HTTP POST method.

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
 * @param string $field       The form field name
 * @param string $content     The field value or file contents
 * @param string $filename    The filename (for file uploads)
 * @param string $contentType The content type of the item
 */
$item = new \ByJG\WebRequest\MultiPartItem(
    'fieldName',
    'fieldValue',
    'filename.txt',  // Optional, for file uploads
    'text/plain'     // Optional; when empty, no Content-Type header is written for the item
);
```

To read the contents from a file, use `loadFile()`; the filename is taken from the path:

```php
<?php
$item = (new \ByJG\WebRequest\MultiPartItem('avatar'))
    ->loadFile('/path/to/image.jpg', 'image/jpeg');
```

You can also set a custom Content-Disposition:

```php
<?php
$item = new \ByJG\WebRequest\MultiPartItem('field', 'value');
$item->withContentDisposition(\ByJG\WebRequest\ContentDisposition::Inline);
```

The default is `ContentDisposition::FormData`. If any item uses another disposition (`Inline` or
`Attachment`), `RequestMultiPart` sends the request as `multipart/related` instead of
`multipart/form-data`.

To send an item base64 encoded (adds `Content-Transfer-Encoding: base64`), call `withEncodedBase64()`.

To add the multipart body to a request you already built, use `buildMultiPart()`:

```php
<?php
$request = \ByJG\WebRequest\Helper\RequestMultiPart::buildMultiPart($items, $request);
```

## Parsing the Response Body

`ParseBody::parse()` returns the body of a message decoded as an associative array when its
`Content-Type` is `application/json`, and as a string otherwise:

```php
<?php
$data = \ByJG\WebRequest\ParseBody::parse($response);
```

## HTTP Status and Methods

The package also provides constants for HTTP status codes and methods:

```php
<?php
// Use HTTP method constants
$method = \ByJG\WebRequest\HttpMethod::Post;

// Check for specific status code
if ($response->getStatusCode() === \ByJG\WebRequest\HttpStatus::NotFound->value) {
    echo "Resource not found!";
}
```

### Available HTTP Methods

The `HttpMethod` enum provides constants for all standard HTTP methods:

- `Get`, `Post`, `Put`, `Patch`, `Delete`
- `Head`, `Options`, `Connect`, `Trace`

### Common HTTP Status Codes

The `HttpStatus` enum has a case for every standard HTTP status code, including:

- **2xx Success**: `Ok` (200), `Created` (201), `NoContent` (204)
- **3xx Redirection**: `MovedPermanently` (301), `Found` (302)
- **4xx Client Errors**: `BadRequest` (400), `Unauthorized` (401), `Forbidden` (403), `NotFound` (404)
- **5xx Server Errors**: `InternalServerError` (500), `BadGateway` (502), `ServiceUnavailable` (503) 