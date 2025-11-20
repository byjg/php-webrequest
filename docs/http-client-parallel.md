---
sidebar_position: 3
title: HTTP Client Parallel
description: Execute multiple HTTP requests concurrently for better performance
---

# HTTP Client Parallel

The `HttpClientParallel` class allows you to execute multiple HTTP requests concurrently, improving performance when making multiple independent API calls.

## Features

- Run multiple HTTP requests in parallel
- Define success and error handlers for each request
- Batch processing for high-volume requests
- Based on cURL multi-handle functionality

## Basic Usage

```php
<?php
// Get an instance of the HTTP client
$httpClient = \ByJG\WebRequest\HttpClient::getInstance();

// Define default success and error handlers
$onSuccess = function ($response, $id) {
    // Process the successful response
    $statusCode = $response->getStatusCode();
    $body = $response->getBody()->getContents();
    echo "Request $id completed with status $statusCode\n";
};

$onError = function ($error, $id) {
    // Handle the error
    echo "Request $id failed: " . $error->getMessage() . "\n";
};

// Create the HttpClientParallel instance
$parallel = new \ByJG\WebRequest\HttpClientParallel(
    $httpClient,
    $onSuccess,
    $onError
);

// Create requests
$request1 = \ByJG\WebRequest\Psr7\Request::getInstance(
    \ByJG\Util\Uri::getInstanceFromString('http://api1.example.com')
);
$request2 = \ByJG\WebRequest\Psr7\Request::getInstance(
    \ByJG\Util\Uri::getInstanceFromString('http://api2.example.com')
);
$request3 = \ByJG\WebRequest\Psr7\Request::getInstance(
    \ByJG\Util\Uri::getInstanceFromString('http://api3.example.com')
);

// Add requests to the parallel client
$parallel
    ->addRequest($request1) // Will use default handlers
    ->addRequest($request2) // Will use default handlers
    ->addRequest($request3); // Will use default handlers

// Execute all requests in parallel
$parallel->execute();
```

## Custom Handlers for Specific Requests

You can override the default handlers for specific requests:

```php
<?php
// Custom handler for a specific request
$customSuccessHandler = function ($response, $id) {
    // Special handling for this request
    echo "Special handler for request $id\n";
};

$customErrorHandler = function ($error, $id) {
    // Special error handling for this request
    echo "Special error handler for request $id: " . $error->getMessage() . "\n";
};

// Add request with custom handlers
$parallel->addRequest(
    $request4, 
    $customSuccessHandler,
    $customErrorHandler
);
```

## Getting Error Information

After execution, you can retrieve a list of errors that occurred:

```php
<?php
$parallel->execute();

// Get errors that occurred during execution
$errors = $parallel->getErrorList();

foreach ($errors as $requestId => $error) {
    echo "Error in request $requestId: " . $error->getMessage() . "\n";
}
```

## Performance Considerations

:::info Best Practices
- The parallel client is most effective when making multiple independent requests
- Each request should be able to run independently without dependencies on other requests
- There is some overhead in setting up the parallel execution mechanism, so for small numbers of requests (1-2), it may not be significantly faster than sequential requests
::: 