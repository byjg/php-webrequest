# WebRequest 6.0 Release Notes

## Overview

Version 6.0 represents a major release with significant improvements to PHP version support, PSR compliance, error handling, and documentation. This release drops support for PHP versions below 8.3 and adds support for PHP 8.4 and 8.5.

## New Features

### PHP 8.4 and 8.5 Support
- Added full compatibility with PHP 8.4 and PHP 8.5
- Updated test suite to include PHP 8.4 testing in GitHub Actions
- Docker configuration now uses PHP 8.4-based webserver image

### Enhanced Type Safety
- Added `#[\Override]` attributes to all overridden methods for improved type safety
- Refactored type hints and return types for improved PSR compatibility
- Better null safety checks throughout the codebase

### Improved Error Handling
- Enhanced error handling in cURL operations
- Added validation for cURL initialization failures
- Improved error handling in stream operations (fstat, fwrite, fread)
- Better handling of stream metadata with proper type checking

### Development Environment Improvements
- Added Gitpod configuration for cloud-based development
- Added VSCode launch settings for better debugging experience
- Updated Psalm integration with GitHub CodeQL for automated security analysis

### Documentation Improvements
- Complete documentation restructure with dedicated topic pages:
  - [PSR-7 and PSR-17 Implementation](docs/psr7-implementation.md)
  - [HTTP Client Usage](docs/http-client.md)
  - [Parallel Requests](docs/http-client-parallel.md)
  - [Mock Client for Testing](docs/mock-client.md)
  - [Request Helpers](docs/helpers.md)
  - [Comparison with Guzzle](docs/comparison-with-guzzle.md)
- Streamlined README with better organization and quick links

### Updated Dependencies
- Updated to `byjg/uri` ^6.0
- Updated PHPUnit to ^10.5|^11.5
- Updated Psalm to ^5.9|^6.13
- Updated PSR package versions for better compatibility

## Bug Fixes

- Fixed deprecation warnings in PHP 8.3+
- Fixed cURL handle not being closed on network exceptions
- Fixed potential null pointer issues in stream operations
- Fixed mode checking in `isWritable()` to properly handle non-string modes
- Fixed `getSize()` to handle failed `fstat()` calls gracefully
- Improved `phpversion()` handling with fallback for unknown versions

## Breaking Changes

| Before (5.x) | After (6.0) | Description |
|--------------|-------------|-------------|
| PHP >=8.1 <8.4 | PHP >=8.3 <8.6 | Minimum PHP version increased from 8.1 to 8.3; added support for PHP 8.4 and 8.5 |
| `byjg/uri` ^5.0 | `byjg/uri` ^6.0 | Updated to major version 6 of the URI package |
| PHPUnit ^9.6 | PHPUnit ^10.5\|^11.5 | Updated to PHPUnit 10 and 11 for better PHP 8.3+ support |
| Psalm ^5.9 | Psalm ^5.9\|^6.13 | Extended Psalm version support to include version 6 |
| `StreamBase::getSize()` returns `int` | Returns `int\|null` | Now returns `null` when fstat fails instead of potentially causing errors |
| `isWritable()` mode check | Strict string type check | Mode metadata must be string; null mode now returns false |

## Migration Guide: Upgrading from 5.x to 6.0

### Step 1: Update PHP Version

Ensure your environment is running PHP 8.3 or higher:

```bash
php -v  # Should show 8.3.x, 8.4.x, or 8.5.x
```

If you're running PHP 8.1 or 8.2, you must upgrade your PHP installation before proceeding.

### Step 2: Update Dependencies

Update your `composer.json` requirements:

```bash
composer require byjg/webrequest:^6.0
composer update
```

This will automatically update `byjg/uri` to version 6.0 and other dependencies.

### Step 3: Update Development Dependencies

If you're developing with this library, update your development tools:

```bash
composer require --dev phpunit/phpunit:^10.5
# or
composer require --dev phpunit/phpunit:^11.5
```

### Step 4: Review Stream Handling Code

If you have code that depends on `StreamBase::getSize()` always returning an integer:

**Before:**
```php
$size = $stream->getSize();
$buffer = fread($handle, $size); // Could fail if size is null
```

**After:**
```php
$size = $stream->getSize();
if ($size !== null) {
    $buffer = fread($handle, $size);
} else {
    // Handle case where size is unknown
    $buffer = $stream->getContents();
}
```

### Step 5: Test Your Application

Run your test suite to ensure compatibility:

```bash
vendor/bin/phpunit
```

If you use Psalm for static analysis:

```bash
vendor/bin/psalm
```

### Step 6: Review Custom Stream Implementations

If you've created custom stream classes extending `StreamBase`, add `#[\Override]` attributes to overridden methods for better type safety:

```php
class MyCustomStream extends StreamBase
{
    #[\Override]
    public function read(int $length): string
    {
        // Your implementation
    }
}
```

### Common Migration Issues

#### Issue: "Your PHP version (8.2.x) does not satisfy requirements"

**Solution:** Upgrade PHP to 8.3 or higher.

#### Issue: Composer conflicts with byjg/uri version

**Solution:** Explicitly require the new version:
```bash
composer require byjg/uri:^6.0
```

#### Issue: PHPUnit tests fail with deprecation notices

**Solution:** Update to PHPUnit 10.5+ or 11.5+ which properly supports PHP 8.3+.

### Getting Help

If you encounter issues during migration:

1. Check the [documentation](docs/) for detailed usage examples
2. Review the [comparison with Guzzle](docs/comparison-with-guzzle.md) if migrating from Guzzle
3. Open an issue on [GitHub](https://github.com/byjg/php-webrequest/issues)

## Acknowledgments

Special thanks to:
- [@UlrichEckhardt](https://github.com/UlrichEckhardt) for contributions to Psalm integration and GitHub Actions improvements

---

Released: 2025
