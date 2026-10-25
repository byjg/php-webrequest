---
sidebar_key: webrequest
tags: [php, http]
---

# Web Request

A PSR-18 HTTP client on cURL, with its own PSR-7 messages, parallel requests and a mock client for tests, in one small package.

[![Sponsor](https://img.shields.io/badge/Sponsor-%23ea4aaa?logo=githubsponsors&logoColor=white&labelColor=0d1117)](https://github.com/sponsors/byjg)
[![Build Status](https://github.com/byjg/php-webrequest/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/php-webrequest/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](https://opensource.byjg.com)
[![Install MCP Server](https://img.shields.io/badge/Install-MCP_Server-8A2BE2?logo=modelcontextprotocol&logoColor=white)](https://opensource.byjg.com/docs/ai/mcpserver-byjg-docs/)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/php-webrequest/)
[![GitHub license](https://img.shields.io/github/license/byjg/php-webrequest.svg)](https://opensource.byjg.com/license/)
[![GitHub release](https://img.shields.io/github/release/byjg/php-webrequest.svg)](https://github.com/byjg/php-webrequest/releases/)

## Why

Calling an API should not need a large dependency tree, and the code that does it should be
easy to test. Most clients give you one of the two.

WebRequest implements the standards (PSR-7 messages, PSR-17 factories, a PSR-18 client), so
the code that uses it depends on the interfaces, not on this package:

```php
$responses = HttpPromise::all(
    users:  $httpClient->sendAsync(Request::getInstance(Uri::getInstance('https://api.example.com/users'))),
    orders: $httpClient->sendAsync(Request::getInstance(Uri::getInstance('https://api.example.com/orders'))),
)->await();
```

- **Parallel requests without an event loop.** `sendAsync()` returns a promise, and every
  pending request runs together on cURL multi when you `await()`.
- **Test without the network.** `MockClient` returns the response you give it and keeps the
  request it received.
- **Helpers for the usual bodies:** JSON, form URL-encoded and multipart.

## Overview

PHP WebRequest is a **fully PSR-compliant** HTTP client package that provides:

### PSR Implementations
- ✅ **[PSR-7](https://www.php-fig.org/psr/psr-7/)** - HTTP Message Interface (Request, Response, Stream)
- ✅ **[PSR-17](https://www.php-fig.org/psr/psr-17/)** - HTTP Factories (Request, Response, Stream, etc.)
- ✅ **[PSR-18](https://www.php-fig.org/psr/psr-18/)** - HTTP Client Interface

### Additional Features
- Helper classes for common request types (JSON, Form URL Encoded, MultiPart)
- Support for parallel HTTP requests, with promises (`sendAsync()`) or callbacks
- Mock client for testing

## Documentation

- [HTTP Messages and Factories (PSR-7/PSR-17)](docs/psr7-implementation.md) - Create and manipulate HTTP requests, responses, and streams
- [Sending HTTP Requests (PSR-18)](docs/http-client.md) - Send HTTP requests with customizable options (proxy, SSL, timeouts)
- [Parallel Requests](docs/http-client-parallel.md) - Execute multiple HTTP requests concurrently, with promises (`sendAsync()`) or callbacks
- [Testing with Mock Client](docs/mock-client.md) - Test HTTP code without real network calls
- [Request Helpers](docs/helpers.md) - Quickly build JSON, Form, and MultiPart requests
- [Comparison with Guzzle](docs/comparison-with-guzzle.md) - How WebRequest compares to Guzzle HTTP client

## Basic Example

```php
<?php
// Create a request
$uri = \ByJG\Util\Uri::getInstance('http://www.example.com/page');
$request = \ByJG\WebRequest\Psr7\Request::getInstance($uri);

// Send the request
$response = \ByJG\WebRequest\HttpClient::getInstance()->sendRequest($request);

// Process the response
$statusCode = $response->getStatusCode();
$body = $response->getBody()->getContents();
```

## Installation

```bash
composer require "byjg/webrequest"
```

## Running Tests

```bash
# Start the test server
docker-compose up -d

# Run the tests
vendor/bin/phpunit

# Stop the server
docker-compose down
```

----
[Open source ByJG](https://opensource.byjg.com)
