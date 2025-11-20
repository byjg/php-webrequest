---
sidebar_position: 6
title: Comparison with Guzzle
description: How WebRequest compares to Guzzle HTTP client
---

# Comparison with Guzzle

Both WebRequest and [Guzzle](https://docs.guzzlephp.org/) are PSR-compliant HTTP clients for PHP. This guide helps you understand the differences and choose the right tool for your needs.

## Quick Comparison

| Feature                     | WebRequest                         | Guzzle                  |
|-----------------------------|------------------------------------|-------------------------|
| **PSR-7 (HTTP Messages)**   | ✅ Complete implementation          | ✅ Uses PSR-7 interfaces |
| **PSR-17 (HTTP Factories)** | ✅ Built-in factories               | ✅ Compatible            |
| **PSR-18 (HTTP Client)**    | ✅ Native implementation            | ✅ Native implementation |
| **Async Requests**          | ❌ Not supported                    | ✅ Full async support    |
| **Middleware System**       | ❌ Not included                     | ✅ Extensive middleware  |
| **Parallel Requests**       | ✅ Via HttpClientParallel           | ✅ Via Pool              |
| **Mock/Testing**            | ✅ Dedicated MockClient             | ✅ Mock handler          |
| **Dependencies**            | Minimal (byjg/uri, PSR interfaces) | More dependencies       |
| **Learning Curve**          | Simple and straightforward         | Steeper (more features) |
| **Size/Footprint**          | Lightweight                        | Larger                  |

## When to Choose WebRequest

### ✅ Best For:

1. **Simple HTTP Requests**
   - You need straightforward GET/POST/PUT/DELETE requests
   - You don't need async operations
   - Your use case is REST API consumption

2. **Lightweight Projects**
   - You want minimal dependencies
   - Package size matters
   - You prefer simplicity over extensive features

3. **Learning PSR Standards**
   - You want to understand PSR-7/17/18 implementations
   - Clean, readable codebase
   - Direct PSR-7 implementation (not just interface usage)

4. **Testing-Friendly Code**
   - Dedicated `MockClient` with easy setup
   - Simple request/response verification
   - No complex mock configuration needed

5. **Parallel Requests (Simple Cases)**
   - You need concurrent requests without async complexity
   - Handler-based approach is sufficient
   - You don't need promise-based flow control

### Example: Simple API Call

```php
<?php
use ByJG\WebRequest\HttpClient;
use ByJG\WebRequest\Psr7\Request;
use ByJG\Util\Uri;

// Simple and straightforward
$request = Request::getInstance(
    Uri::getInstanceFromString('https://api.example.com/users')
);

$response = HttpClient::getInstance()->sendRequest($request);
$data = json_decode($response->getBody()->getContents(), true);
```

## When to Choose Guzzle

### ✅ Best For:

1. **Async/Promise-Based Operations**
   - You need non-blocking HTTP requests
   - Promise-based flow control is required
   - High-concurrency scenarios

2. **Complex Request/Response Processing**
   - Middleware chains for authentication, logging, retry logic
   - Custom handlers for specific protocols
   - Advanced error handling and retry strategies

3. **Large-Scale Applications**
   - Enterprise-level projects
   - Complex service integrations
   - Need for extensive customization

4. **Streaming Operations**
   - Large file uploads/downloads with progress tracking
   - Stream processing with custom handlers
   - Advanced streaming scenarios

5. **Established Ecosystem**
   - Many third-party integrations
   - Extensive community support
   - Well-documented edge cases

## Feature-by-Feature Comparison

### HTTP Client Capabilities

#### Basic Requests
Both libraries support all HTTP methods and are PSR-18 compliant.

**WebRequest:**
```php
$client = HttpClient::getInstance()
    ->withNoSSLVerification()
    ->withCurlOption(CURLOPT_TIMEOUT, 30);
```

**Guzzle:**
```php
$client = new GuzzleHttp\Client([
    'verify' => false,
    'timeout' => 30
]);
```

### Parallel/Concurrent Requests

#### WebRequest: HttpClientParallel
- Handler-based approach
- Synchronous execution of multiple requests
- Simple callback system

```php
$parallel = new HttpClientParallel($httpClient, $onSuccess, $onError);
$parallel->addRequest($request1)
    ->addRequest($request2)
    ->execute();
```

#### Guzzle: Pool & Promises
- True async with promises
- More control over concurrency limits
- Non-blocking execution

```php
$promises = [
    'request1' => $client->getAsync('http://example.com/1'),
    'request2' => $client->getAsync('http://example.com/2'),
];
$results = Promise\Utils::settle($promises)->wait();
```

### Testing and Mocking

#### WebRequest: MockClient
- Extends HttpClient
- Direct mock response injection
- Easy CURL option verification

```php
$mockResponse = new Response(200);
$mockClient = new MockClient($mockResponse);

// Test your code
$result = $myService->callApi($mockClient);

// Verify
$sentRequest = $mockClient->getRequestedObject();
$curlConfig = $mockClient->getCurlConfiguration();
```

#### Guzzle: Mock Handler
- Handler stack system
- Queue of responses
- History middleware for verification

```php
$mock = new MockHandler([
    new Response(200, [], 'Body'),
    new Response(404)
]);
$handler = HandlerStack::create($mock);
$client = new Client(['handler' => $handler]);
```

### Helper Classes

#### WebRequest: Purpose-Built Helpers
- `RequestJson::build()` - JSON requests
- `RequestFormUrlEncoded::build()` - Form requests
- `RequestMultiPart::build()` - File uploads

```php
$request = RequestJson::build($uri, 'POST', [
    'name' => 'John',
    'email' => 'john@example.com'
]);
```

#### Guzzle: Options-Based
- Configuration through options array
- More flexible but requires more code

```php
$response = $client->post('http://example.com', [
    'json' => [
        'name' => 'John',
        'email' => 'john@example.com'
    ]
]);
```

## PSR Standards Implementation

### WebRequest
- **PSR-7**: Full implementation of all interfaces (Request, Response, Stream, etc.)
- **PSR-17**: Complete factory implementations
- **PSR-18**: Native HTTP client implementation

WebRequest provides the actual classes that implement PSR interfaces, making it useful for understanding and working directly with the standards.

### Guzzle
- **PSR-7**: Uses PSR-7 interfaces via `guzzlehttp/psr7`
- **PSR-17**: Compatible through adapter packages
- **PSR-18**: Native implementation

Guzzle consumes PSR interfaces and focuses on advanced features on top of the standards.

## Performance Considerations

### WebRequest
- Lower memory footprint
- Fewer dependencies to load
- Direct cURL wrapper
- Best for: Sequential requests, simple API calls

### Guzzle
- Higher memory usage
- More dependencies
- Abstracted transport layer
- Best for: Async operations, high concurrency, complex workflows

## Migration Between Libraries

Both libraries are PSR-18 compliant, making migration relatively straightforward:

```php
// Using PSR-18, this code works with both:
/** @var Psr\Http\Client\ClientInterface $client */
$client = /* WebRequest HttpClient or Guzzle Client */;

$request = /* PSR-7 Request */;
$response = $client->sendRequest($request);
```

:::tip Interoperability
Thanks to PSR standards, you can write code that works with either library by depending on PSR interfaces rather than concrete implementations.
:::

## Decision Matrix

Choose **WebRequest** if you answer YES to most:
- [ ] Do you primarily make simple REST API calls?
- [ ] Is lightweight/minimal dependencies important?
- [ ] Do you prefer simplicity over advanced features?
- [ ] Are you building a small to medium-sized project?
- [ ] Do you want clear PSR-7 implementation examples?

Choose **Guzzle** if you answer YES to most:
- [ ] Do you need async/promise-based requests?
- [ ] Will you use middleware extensively?
- [ ] Is your project large-scale or enterprise?
- [ ] Do you need advanced streaming capabilities?
- [ ] Do you require complex retry/error handling logic?

## Conclusion

:::info The Bottom Line
- **WebRequest** = Simple, lightweight, PSR-compliant HTTP client for straightforward use cases
- **Guzzle** = Feature-rich, enterprise-grade HTTP client for complex scenarios

Both are excellent choices, and the "right" one depends on your specific needs. You can't go wrong with either for basic HTTP operations!
:::

## Additional Resources

- [Guzzle Documentation](https://docs.guzzlephp.org/)
- [PSR-7: HTTP Message Interface](https://www.php-fig.org/psr/psr-7/)
- [PSR-17: HTTP Factories](https://www.php-fig.org/psr/psr-17/)
- [PSR-18: HTTP Client](https://www.php-fig.org/psr/psr-18/)
