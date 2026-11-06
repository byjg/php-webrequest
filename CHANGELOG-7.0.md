# Changelog - Version 7.0

> **Status: in development.** This document tracks changes landing on the `7.0` branch.
> Nothing here is released yet, and the contents may still change.

## Overview

Version 7.0 narrows the return types of the PSR-7 wither methods from the interface types
(`MessageInterface`, `RequestInterface`) to `static`. Fluent chains now keep the concrete type of
the object they started from, so calling a subclass method after a base-class wither no longer
requires an intermediate variable or a cast.

This is a source-compatible change for every caller. It is a breaking change only for code that
*extends* `Message` or `Request` and overrides one of these methods — see
[Breaking Changes](#breaking-changes).

## New Features

### Async requests with promises: sendAsync() and HttpPromise

- `HttpClient::sendAsync(RequestInterface $request): HttpPromise` starts the request and returns at once. The
  requests run in parallel with curl_multi, in the current process, while `HttpPromise::await()` is called on any
  promise (there is no event loop).
- `HttpPromise` follows the JavaScript Promise semantics: `then(?$onFulfilled, ?$onRejected)`, `catch()`,
  `finally()` (no argument, keeps the value or reason), a returned `HttpPromise` is followed (chained requests),
  `await()` returns the value or throws the reason, `getState()` returns an `HttpPromiseState`, and the static
  `all()` (keeps the order and keys), `race()`, `resolve()`, `reject()` and `try()` (calls a closure:
  resolved with its return value or rejected with what it throws, as `Promise.try()`).
- `HttpPromise::map(iterable $items, Closure $callback, int $concurrency)` limits the requests in progress: it calls
  the callback for each item with at most `$concurrency` promises pending, starting the next item when one settles,
  and resolves with the values in the order of the items.
- The promise is fulfilled with the `ResponseInterface` (an HTTP error status is a response, as in
  `sendRequest()`) or rejected with a `NetworkException`.
- `MockClient::sendAsync()` returns a promise settled with the mocked response.
- See `docs/http-client-parallel.md`. `HttpClientParallel` is unchanged.

### ServerRequest::fromGlobals(), MultiPartItem::parseBody()

- `ServerRequest::fromGlobals(StreamInterface|string|null $body = null)` builds the current request from
  `$_SERVER` (method, URI, headers, server params), `$_COOKIE`, `$_FILES` and, for a POST of a form, `$_POST` as
  the parsed body. The body is a seekable copy of `php://input`, or the one given (a stream as it is, a string
  in a `MemoryStream`), for a worker in the CLI or a test.
- `getParsedBody()`, when no parsed body was given, parses the body by its `Content-Type`: JSON,
  urlencoded, multipart, and now XML, YAML and CSV (through byjg/serializer). `withParsedBody()` writes the
  body for JSON, urlencoded, XML, YAML and CSV.
- `ServerRequest::parseBody()` parses the body as `getParsedBody()` does, but throws `MessageException` saying
  why there is no parsed body (a `Content-Type` such as `text/plain`, an invalid body, a JSON or YAML scalar).
- `MultiPartItem::parseBody(string $body, string $contentType)` reads a multipart body back into its fields
  (as PHP fills `$_POST`) and its files (as `MultiPartItem`, an `UploadedFileInterface`), for a body PHP did
  not parse.

### `Message::withHeaders()`

`withHeaders(array $headers)` sets several headers at once: each as `withHeader()` sets it (validated, it replaces
the header of that name in any case), with one copy of the message instead of one for each `withHeader()` call.
It takes what `getHeaders()` returns, so a request is copied with
`$new->withHeaders($request->getHeaders())`. Not part of PSR-7.

## Improvements

### `ServerRequest::getQueryParams()` does not copy the request

It cloned the whole request on each call, for nothing.

### `MockClient` keeps the request it is sent, not a copy

`createCurlHandle()` cloned the request, as `HttpClient` does not: a request is immutable, so the copy protected
nothing (and `clone` is shallow, so it shared the body anyway). `getRequestedObject()` now returns the request that was
sent.

### PSR-7 withers preserve the concrete type

`Message` and `Request` declared their withers as returning the PSR interface, which discarded the
concrete type at the first call in a chain. `Response::withStatus()` and every `ServerRequest`
method already returned `static`, so the behaviour was inconsistent across the same object graph.

Before, a chain starting on a `ServerRequest` degraded to `Request`, then to `Message`, and the
`ServerRequest`-specific methods became unreachable to static analysis:

```php
// 6.x — static analysis error: Method Message::withQueryParams does not exist
$request = (new ServerRequest($uri))
    ->withUri($uri)               // inferred as Request
    ->withAddedHeader('X', 'y')   // inferred as Message
    ->withQueryParams(['a' => 1]);
```

The workaround was to order the calls so the most-derived method came first, or to reassign through
intermediate variables with `@var` annotations. Neither is necessary now:

```php
// 7.0 — the ServerRequest type survives the whole chain
$request = (new ServerRequest($uri))
    ->withUri($uri)
    ->withAddedHeader('X', 'y')
    ->withoutHeader('X')
    ->withProtocolVersion('1.1')
    ->withRequestTarget('/api/users')
    ->withQueryParams(['a' => 1])
    ->withAttribute('route', 'users.store');
```

The eight affected methods:

| Class     | Methods                                                                             |
|-----------|-------------------------------------------------------------------------------------|
| `Message` | `withProtocolVersion`, `withHeader`, `withAddedHeader`, `withoutHeader`, `withBody`  |
| `Request` | `withRequestTarget`, `withMethod`, `withUri`                                        |

Each also carries a `@return $this` docblock. The native `static` return type serves PHP and IDEs;
the docblock is what static analysers resolve against the call-site class rather than the declaring
class, and both are needed for the type to survive a chain.

All methods already returned `clone $this`, so runtime behaviour is unchanged.

## Bug Fixes

- **`StreamBase::close()` never closed the resource** (the check was inverted); it now closes it.
- **`HttpClient` dropped the headers after `Referer` or `User-Agent`**: the loop stopped (`break`) when it
  met one of them, and their names were compared case-sensitively. Every header is now sent.

- **`UploadedFile::parseFilesGlobal()` read every upload into memory**, and `moveTo()` wrote a copy and left
  the temporary file behind. The uploaded file is now a stream over its temporary file, read only when used,
  and `moveTo()` moves a file on disk (`move_uploaded_file()` for a file PHP received, `rename()` otherwise).
  After `moveTo()`, `getStream()` and a second `moveTo()` throw a `RuntimeException`, as PSR-7 requires.

- **Credentials with special characters**: the request and proxy credentials were passed to curl as the
  percent-encoded PSR-7 userinfo (`CURLOPT_USERPWD`/`CURLOPT_PROXYUSERPWD`), so a password with `@`, `:` or
  `%` was sent encoded and authentication failed. The user and password are now decoded and sent
  separately (`CURLOPT_USERNAME`/`CURLOPT_PASSWORD`, `CURLOPT_PROXYUSERNAME`/`CURLOPT_PROXYPASSWORD`). Code
  reading `getCurlConfiguration()` sees these options instead of `CURLOPT_USERPWD`.
- **`HttpClientParallel::getErrorList()` mixed strings and exceptions**: a response that could not be
  parsed was stored as a `"ClassName: message"` string, while an exception thrown by a handler was stored
  as the object. Every entry is now the `Throwable`; the `CurlException` thrown by `execute()` still lists
  each error as `"ClassName: message"`.
- **`HttpClientParallel` handled a request twice**: a request whose connection failed called `onError`
  twice, and a response that could not be parsed was recorded twice in `getErrorList()`, because the request
  was not removed when the error happened and was processed again after the loop. Also, only one finished
  request was read per `curl_multi_exec()` call, so requests finishing together were handled only after all
  requests ended. Now every finished request is read as it finishes and removed whatever happens, so each
  request calls `onSuccess` or `onError` exactly once and each error is recorded once.

## Breaking Changes

| Before (6.x) | After (7.0) | Description |
|--------------|-------------|-------------|
| `Message::withProtocolVersion(): MessageInterface` | `: static` | Return type narrowed |
| `Message::withHeader(): MessageInterface` | `: static` | Return type narrowed |
| `Message::withAddedHeader(): MessageInterface` | `: static` | Return type narrowed |
| `Message::withoutHeader(): MessageInterface` | `: static` | Return type narrowed |
| `Message::withBody(): MessageInterface` | `: static` | Return type narrowed |
| `Request::withRequestTarget(): RequestInterface` | `: static` | Return type narrowed |
| `Request::withMethod(): RequestInterface` | `: static` | Return type narrowed |
| `Request::withUri(): RequestInterface` | `: static` | Return type narrowed |
| `HttpClientParallel::getErrorList()` entries: `Throwable` or `string` | always `Throwable` | A response that could not be parsed is stored as the exception, not as a `"ClassName: message"` string |
| `HttpClientParallel`: `onError` called twice for a failed connection, a parse error recorded twice | called / recorded once | Code that compensated for the duplicates (e.g. counted `onError` calls or the `CurlException` "Raised N error(s)" count) sees the real numbers |

### PSR-7 conformance

The package now runs the PSR-7 and PSR-17 conformance suites with its tests (alone: `vendor/bin/phpunit --testsuite Conformance`),
and the behavior below changed to follow them:

- **`ServerRequest` holds only what it is given.** `new ServerRequest()` and `ServerRequestFactory` no longer
  read `$_SERVER`, `$_COOKIE`, `$_FILES` or `$_POST`: build the current request with the new
  `ServerRequest::fromGlobals()`. A multipart body is parsed from the body, not taken from `$_POST`.
- **The parsed body is `null`, an array or an object.** `getParsedBody()` returns `null`, not the body as a
  string, for a `Content-Type` it does not parse (`text/plain`...); read it with `getBody()`, or call
  `parseBody()` for the reason. `withParsedBody()` accepts only `null`, an array or an object
  (`MessageException` otherwise; a string was written as the body).
- **`getHeaders()` keeps the header name as it was first given** (`content-type`, not `Content-Type`).
  `getHeader()`, `hasHeader()` and `getHeaderLine()` still ignore the case. Code that read
  `getHeaders()['Content-Type']` must use `getHeaderLine('Content-Type')`.
- **Header names and values are validated**: a name that is not an RFC 7230 token, an empty array, a
  non-string value or a value with a line break throws `MessageException`. Values are trimmed.
- **The method is case-sensitive and any token**: `withMethod('post')` keeps `post`, and `PROPFIND` or
  `PURGE` are accepted. An invalid method throws `MessageException` instead of `RequestException`.
  `HttpClient` sends the method as given, and servers such as nginx answer `400 Bad Request` to `post`:
  pass the `HttpMethod` enum (`HttpMethod::Post`) or the uppercase name.
- **`MessageException` extends `InvalidArgumentException`** (still an `Exception`).
- **`withUri()` with a URI without a host keeps the `Host` header**, and an empty host is no longer written.
- **`StreamFactory::createStreamFromResource()` uses the resource**, at its current position (it is not
  copied, and closing the stream closes it), and `createStreamFromFile()` opens the file with the mode given
  (`r` by default, so the stream is read only; an invalid mode throws `RuntimeException`). Both return the new
  `ResourceStream`.
- **Streams**: `read()` and `getContents()` on a stream that is not readable throw `RuntimeException`;
  `isReadable()` and `isWritable()` follow the mode as `fopen()` does (`"rw"` opens a file read only);
  `isSeekable()`, `isReadable()` and `isWritable()` return `false` for a detached stream instead of throwing.
- **`MultiPartItem::build()` ends its lines with CRLF**, as RFC 7578 requires, instead of LF.

### Who is affected

**Callers of the withers are not affected.** Narrowing a return type is covariant: anything that accepted the old
type accepts the new one. Code that stores the result in a `MessageInterface` or
`RequestInterface` variable, parameter or property keeps working unchanged.

**Subclasses that override these methods are affected.** PHP does not allow a child to widen a
return type, so an override still declaring the interface type is now a fatal error:

```php
class MyRequest extends Request
{
    // Fatal error in 7.0: declaration must be compatible with Request::withMethod(): static
    public function withMethod(string|HttpMethod $method): RequestInterface { /* ... */ }
}
```

The fix is to match the parent:

```php
    public function withMethod(string|HttpMethod $method): static { /* ... */ }
```

Only the eight methods listed above are affected. `RequestJson`, `RequestFormUrlEncoded`,
`RequestMultiPart` and `ServerRequest` ship with this package and needed no change, since none of
them override a wither.

**Code reading `HttpClientParallel::getErrorList()`** and treating an entry as a string must read the
exception instead, e.g. `get_class($error) . ": " . $error->getMessage()`.

### Enum cases renamed to PascalCase

Enum cases follow the ByJG standard and are written in PascalCase. The backed values did not
change, so `->value`, `from()`, `tryFrom()` and stored data are not affected; only code that
names a case (or reads `->name`) has to be updated.

| Enum | Before (6.x) | After (7.0) |
|---|---|---|
| `ContentDisposition` | `formData`, `inline`, `attachement` | `FormData`, `Inline`, `Attachment` |
| `HttpMethod` | `GET`, `HEAD`, `POST`, `PUT`, `DELETE`, `CONNECT`, `OPTIONS`, `TRACE`, `PATCH` | `Get`, `Head`, `Post`, `Put`, `Delete`, `Connect`, `Options`, `Trace`, `Patch` |
| `HttpStatus` | every case, in `UPPER_CASE`: `OK`, `LENGTH_REQUIRED`, `NETWORK_AUTHENTICATION_REQUIRED`, … | the same name in PascalCase: `Ok`, `LengthRequired`, `NetworkAuthenticationRequired`, … |

Not a plain change of casing: `ContentDisposition::attachement` is now `ContentDisposition::Attachment`.

## Upgrading from 6.x

Rename every reference to a case of `ContentDisposition`, `HttpMethod` or `HttpStatus`
(see [Enum cases renamed to PascalCase](#enum-cases-renamed-to-pascalcase)). Code that only uses
`->value`, `from()` or `tryFrom()`, or passes plain strings such as `'POST'`, needs no change.

If you extend `Message` or `Request` and override any of the eight methods, change the declared
return type of your override to `static`. A static analyser or a single run of your test suite will
surface every occurrence, as the failure is a fatal error at class-load time rather than a silent
behaviour change.

## Requirements

- PHP 8.3, 8.4, 8.5 and 8.6 are now supported: `"php": ">=8.3 <8.7"`.
  The previous `<8.6` upper bound excluded PHP 8.6, since `<8.6` is exclusive.

### ByJG dependencies

- `byjg/uri` is now `^7.0`.
- `byjg/serializer` `^7.0` is a new dependency (with `symfony/yaml`), to parse and write XML, YAML and CSV
  bodies.

While 7.0 is unreleased these resolve to `7.0.x-dev` from each component's
`7.0` branch, via `minimum-stability: dev` with `prefer-stable: true`.

## Toolchain

- PHPUnit updated to `^12.5`.
- Psalm is installed as `psalm/phar` instead of `vimeo/psalm`.

  `vimeo/psalm` lists the PHP versions it supports and no published release includes
  8.6, so as a dev dependency it made `composer install` fail on the 8.6 build job
  before any test ran. `psalm/phar` requires only `php ^8.2` and bundles its own
  dependencies, so it installs on every PHP version in the matrix and cannot conflict
  with the project's. Psalm itself still refuses to *run* on 8.6, which is why the
  Psalm job uses 8.5. `composer psalm` runs it.

- PHPUnit 13 is deliberately **not** used. It requires PHP `>=8.4.1`, breaking the
  8.3 floor.

## Continuous Integration

- The build matrix now includes PHP 8.6.
- The Psalm job runs on PHP 8.5.

## Housekeeping

- `phpunit.xml.dist` renamed to `phpunit.xml`.
- Removed the obsolete `.travis.yml`.
- Removed `example.php` and `example_parallel.php`; the examples are in the README and `docs/`.
