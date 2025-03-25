---
sidebar_position: 4
---

# Mock Client

The `MockClient` class extends `HttpClient` to provide testing capabilities. It allows you to simulate HTTP requests and responses without making actual network calls, which is essential for unit testing.

## Features

- Extends the standard HttpClient
- Allows setting predefined responses
- Captures CURL options for verification
- Provides access to requested objects
- Useful for unit testing HTTP-dependent code

## Basic Usage

```php
<?php
// Create a mock response
$mockResponse = (new \ByJG\WebRequest\Psr7\Response(200))
    ->withBody(new \ByJG\WebRequest\Psr7\MemoryStream('{"success": true}'));

// Create a mock client with the predefined response
$mockClient = new \ByJG\WebRequest\MockClient($mockResponse);

// Create a request
$request = \ByJG\WebRequest\Psr7\Request::getInstance(
    \ByJG\Util\Uri::getInstanceFromString('http://example.com/api')
);

// Use the mock client to send the request
// This will not actually send the request over the network
$response = $mockClient->sendRequest($request);

// The response will be the mock response
assert($response === $mockResponse);
```

## Default Mock Response

If you don't provide a mock response when creating the MockClient, it will use a default response:

```php
<?php
// Create a mock client with default response
$mockClient = new \ByJG\WebRequest\MockClient();

// Default response is a 200 OK with a JSON body: {"key":"value"}
$response = $mockClient->sendRequest($request);
$body = $response->getBody()->getContents(); // {"key":"value"}
```

## Verifying CURL Options

One of the most powerful features of MockClient is the ability to inspect the CURL options that would have been used:

```php
<?php
$mockClient = new \ByJG\WebRequest\MockClient();

// Configure the client
$mockClient->withNoSSLVerification()
    ->withCurlOption(CURLOPT_TIMEOUT, 60);

// Send a request
$mockClient->sendRequest($request);

// Get the CURL configuration for verification
$curlConfig = $mockClient->getCurlConfiguration();

// Verify specific options
assert($curlConfig[CURLOPT_SSL_VERIFYHOST] === 0);
assert($curlConfig[CURLOPT_SSL_VERIFYPEER] === 0);
assert($curlConfig[CURLOPT_TIMEOUT] === 60);
```

## Inspecting Requests

You can also access the request object that was sent:

```php
<?php
$mockClient = new \ByJG\WebRequest\MockClient();
$mockClient->sendRequest($request);

// Get the request object that was sent
$sentRequest = $mockClient->getRequestedObject();

// Verify request properties
assert($sentRequest->getMethod() === 'GET');
assert((string)$sentRequest->getUri() === 'http://example.com/api');
```

## Getting the Expected Response

You can retrieve the expected response:

```php
<?php
$mockResponse = new \ByJG\WebRequest\Psr7\Response(201);
$mockClient = new \ByJG\WebRequest\MockClient($mockResponse);

// Get the expected response
$expectedResponse = $mockClient->getExpectedResponse();
assert($expectedResponse === $mockResponse);
```

## Integration with PHPUnit

The MockClient is particularly useful when combined with PHPUnit:

```php
<?php
use PHPUnit\Framework\TestCase;

class ApiServiceTest extends TestCase
{
    public function testApiCall()
    {
        // Create a mock response
        $mockResponse = (new \ByJG\WebRequest\Psr7\Response(200))
            ->withBody(new \ByJG\WebRequest\Psr7\MemoryStream('{"success": true}'));
        
        // Create a mock client
        $mockClient = new \ByJG\WebRequest\MockClient($mockResponse);
        
        // Inject the mock client into your service
        $apiService = new ApiService($mockClient);
        
        // Execute the method that makes HTTP requests
        $result = $apiService->callApi();
        
        // Assert that the result is what you expect
        $this->assertTrue($result);
        
        // Verify that the request was made correctly
        $request = $mockClient->getRequestedObject();
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
    }
}
``` 