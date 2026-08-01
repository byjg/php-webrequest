# WebRequest 7.0 Release Notes

## Overview

Version 7.0 narrows the return types of the PSR-7 wither methods from the interface types
(`MessageInterface`, `RequestInterface`) to `static`. Fluent chains now keep the concrete type of
the object they started from, so calling a subclass method after a base-class wither no longer
requires an intermediate variable or a cast.

This is a source-compatible change for every caller. It is a breaking change only for code that
*extends* `Message` or `Request` and overrides one of these methods — see
[Breaking Changes](#breaking-changes).

## Improvements

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

### Who is affected

**Callers are not affected.** Narrowing a return type is covariant: anything that accepted the old
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

## Upgrading from 6.x

For most projects the upgrade is a version bump — no code changes are required.

If you extend `Message` or `Request` and override any of the eight methods, change the declared
return type of your override to `static`. A static analyser or a single run of your test suite will
surface every occurrence, as the failure is a fatal error at class-load time rather than a silent
behaviour change.
