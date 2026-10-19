---
sidebar_position: 3
title: HTTP Client Parallel
description: Execute multiple HTTP requests concurrently for better performance
---

# HTTP Client Parallel

There are two ways to run requests in parallel, both based on cURL multi-handle (one process, no threads):
promises with `HttpClient::sendAsync()`, or callbacks with `HttpClientParallel`.

## Promises: sendAsync()

`HttpClient::sendAsync(RequestInterface $request)` starts the request and returns at once an `HttpPromise`, which is
fulfilled with the `ResponseInterface` or rejected with a `NetworkException` (connection error, timeout, ...). An HTTP
error status (4xx, 5xx) is a response, as with `sendRequest()`.

```php
<?php
use ByJG\WebRequest\HttpClient;
use ByJG\WebRequest\HttpPromise;
use ByJG\WebRequest\Psr7\Request;
use ByJG\Util\Uri;

$httpClient = HttpClient::getInstance();

$responses = HttpPromise::all(
    users: $httpClient->sendAsync(Request::getInstance(Uri::getInstanceFromString('https://api.example.com/users'))),
    orders: $httpClient->sendAsync(Request::getInstance(Uri::getInstanceFromString('https://api.example.com/orders'))),
)->await();

echo $responses['users']->getStatusCode();
```

There is no event loop: the requests are transferred while `await()` is called on any promise, and all the pending
requests progress together. The callbacks run then, in the current process.

| Method                                                         | Description                                                                                                                                                       |
|----------------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `then(?Closure $onFulfilled, ?Closure $onRejected)`            | A new promise settled with the handler's return value; a handler that throws rejects it. A returned `HttpPromise` is followed, so a request can chain another one |
| `catch(Closure $onRejected)`                                   | `then(null, $onRejected)`: the value it returns recovers the rejection                                                                                            |
| `finally(Closure $onFinally)`                                  | The callback gets no argument; the promise keeps the original value or reason unless it throws                                                                    |
| `await()`                                                      | Transfers the requests until the promise settles; returns the value or throws the reason                                                                          |
| `getState()`                                                   | `HttpPromiseState::Pending`, `Fulfilled` or `Rejected`                                                                                                            |
| `HttpPromise::all(HttpPromise ...$promises)`                   | Fulfilled with the values in the order (and keys) of the promises, or rejected with the first rejection                                                           |
| `HttpPromise::race(HttpPromise ...$promises)`                  | Settled as the first promise that settles                                                                                                                         |
| `HttpPromise::resolve($value)`, `HttpPromise::reject($reason)` | An already settled promise                                                                                                                                        |
| `HttpPromise::try(Closure $closure, mixed ...$args)`     | Calls the closure: resolved with its return value (a returned promise is followed) or rejected with what it throws, as `Promise.try()` in JavaScript |
| `HttpPromise::map(iterable $items, Closure $callback, int $concurrency)` | Calls `$callback($item, $key)` with at most `$concurrency` promises pending: the next item starts when one settles. Fulfilled with the values in the order (and keys) of the items, or rejected with the first rejection (no other item starts) |

```php
<?php
$name = $httpClient->sendAsync($userRequest)
    ->then(fn($response) => json_decode((string)$response->getBody(), true))
    ->then(fn($user) => $httpClient->sendAsync($profileRequestFor($user['id'])))   // a chained request
    ->then(fn($response) => json_decode((string)$response->getBody(), true)['name'])
    ->catch(fn(\ByJG\WebRequest\Exception\NetworkException $ex) => 'unknown')
    ->await();
```

`MockClient::sendAsync()` returns a promise already settled with the mocked response.

### Limiting the requests in progress

Every `sendAsync()` starts its request at once, so `HttpPromise::all()` over a long list opens all the connections
together. `HttpPromise::map()` starts the requests as the previous ones finish, with at most `$concurrency` in progress
(e.g. to respect the rate limit of an API):

```php
<?php
$users = HttpPromise::map(
    $userIds,
    fn(int $id) => $httpClient->sendAsync(Request::getInstance(Uri::getInstanceFromString("https://api.example.com/users/$id")))
        ->then(fn($response) => json_decode((string)$response->getBody(), true)),
    10   // at most 10 requests in progress
)->await();   // in the order of $userIds
```

`$items` is read as the requests start, so it can be a generator.

:::note
A request whose promise is never awaited (directly, or through a chained or `all()` promise) only progresses while
another promise is awaited. Awaiting a pending promise with no request in progress throws a `LogicException`.
:::

## Callbacks: HttpClientParallel

The `HttpClientParallel` class allows you to execute multiple HTTP requests concurrently, improving performance when making multiple independent API calls.

## Features

- Run multiple HTTP requests in parallel
- Define success and error handlers for each request
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

$onError = function (string $error, $id) {
    // Handle the error: $error is the cURL error message
    echo "Request $id failed: $error\n";
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

$customErrorHandler = function (string $error, $id) {
    // Special error handling for this request
    echo "Special error handler for request $id: $error\n";
};

// Add request with custom handlers
$parallel->addRequest(
    $request4, 
    $customSuccessHandler,
    $customErrorHandler
);
```

## Getting Error Information

The handlers receive the response (or the cURL error message) and the request id, which is the
order in which the request was added, starting at 0. Each request calls exactly one handler, once,
as soon as it finishes: `onSuccess` when a response was received, `onError` when the connection failed.

If a handler throws, or a response cannot be parsed, the error is collected and `execute()`
throws a `CurlException` after all requests have finished; its message lists each error, once, as
`"ClassName: message"`. `getErrorList()` returns the collected errors as `Throwable` objects: the
exception thrown by a handler, or the exception raised while parsing the response.

```php
<?php
use ByJG\WebRequest\Exception\CurlException;

try {
    $parallel->execute();
} catch (CurlException $e) {
    foreach ($parallel->getErrorList() as $error) {
        echo get_class($error) . ": " . $error->getMessage() . "\n";
    }
}
```

## Performance Considerations

:::info Best Practices
- The parallel client is most effective when making multiple independent requests
- Each request should be able to run independently without dependencies on other requests
- There is some overhead in setting up the parallel execution mechanism, so for small numbers of requests (1-2), it may not be significantly faster than sequential requests
::: 