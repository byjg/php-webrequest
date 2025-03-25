---
sidebar_position: 2
---

# HTTP Client

The `HttpClient` class is the core component for sending HTTP requests. It implements the [PSR-18 HTTP Client Interface](https://www.php-fig.org/psr/psr-18/), providing a standardized way to send PSR-7 requests and receive PSR-7 responses.

## Features

- PSR-18 compliant HTTP client
- Built on top of PHP's cURL extension
- Highly customizable with cURL options
- Support for proxies
- SSL verification control
- Redirect following control

## Basic Usage

```php
<?php
// Create a request
$uri = \ByJG\Util\Uri::getInstanceFromString('http://www.example.com/page');
$request = \ByJG\WebRequest\Psr7\Request::getInstance($uri);

// Get an instance of the HTTP client
$client = \ByJG\WebRequest\HttpClient::getInstance();

// Send the request
$response = $client->sendRequest($request);

// Process the response
$statusCode = $response->getStatusCode();
$body = $response->getBody()->getContents();
```

## Customization Options

### Disabling Redirect Following

By default, the client will follow HTTP redirects (status codes 301 and 302). You can disable this behavior:

```php
<?php
$client = \ByJG\WebRequest\HttpClient::getInstance()
    ->withNoFollowRedirect();
```

### Disabling SSL Verification

For development purposes or when working with self-signed certificates, you can disable SSL verification:

```php
<?php
$client = \ByJG\WebRequest\HttpClient::getInstance()
    ->withNoSSLVerification();
```

### Using a Proxy

You can route requests through a proxy:

```php
<?php
$proxyUri = \ByJG\Util\Uri::getInstanceFromString('http://proxy.example.com:8080');
$client = \ByJG\WebRequest\HttpClient::getInstance()
    ->withProxy($proxyUri);
```

### Custom cURL Options

You can set any arbitrary cURL option:

```php
<?php
$client = \ByJG\WebRequest\HttpClient::getInstance()
    ->withCurlOption(CURLOPT_TIMEOUT, 60)         // 60 second timeout
    ->withCurlOption(CURLOPT_CONNECTTIMEOUT, 30); // 30 second connection timeout
```

You can also remove previously set cURL options:

```php
<?php
$client = $client->withoutCurlOption(CURLOPT_TIMEOUT);
```

## Method Chaining

All customization methods return the client instance, allowing for method chaining:

```php
<?php
$client = \ByJG\WebRequest\HttpClient::getInstance()
    ->withNoFollowRedirect()
    ->withNoSSLVerification()
    ->withCurlOption(CURLOPT_TIMEOUT, 60);
``` 