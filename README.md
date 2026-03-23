# phpstan-yield

[![Latest Version](https://img.shields.io/packagist/v/mtorromeo/phpstan-yield)](https://packagist.org/packages/mtorromeo/phpstan-yield)
[![Open Issues](https://img.shields.io/github/issues/mtorromeo/phpstan-yield)](https://github.com/mtorromeo/phpstan-yield/issues)
[![Coverage](https://img.shields.io/codecov/c/github/mtorromeo/phpstan-yield)](https://codecov.io/gh/mtorromeo/phpstan-yield)
[![CI](https://img.shields.io/github/actions/workflow/status/mtorromeo/phpstan-yield/ci.yml?label=CI)](https://github.com/mtorromeo/phpstan-yield/actions/workflows/ci.yml)

A [PHPStan](https://phpstan.org/) extension that enables type inference for `yield` expressions using `@yield` annotations on classes.

## Purpose

PHP generators use `yield` to suspend execution and return values to the caller. However, PHPStan has no built-in way to know what type a `yield` expression resolves to on the call side — i.e., the value sent back into the generator via `Generator::send()` or resolved by an async framework.

This extension solves that by reading `@phpstan-yield`, `@psalm-yield`, or `@yield` annotations from the docblock of the class being yielded. When PHPStan encounters `yield $value`, it looks up the type annotation on the class of `$value` (and its entire class hierarchy) to infer what the `yield` expression returns.

This is particularly useful for coroutine-based async frameworks where yielding a promise-like object causes the scheduler to suspend the coroutine and eventually resume it with the resolved value.

### Example

```php

use React\Promise\PromiseInterface;

/**
 * @return PromiseInterface<User>
 */
function fetchUser(int $id): PromiseInterface
{
    // ...
}

$user = yield fetchUser($id);
// PHPStan now knows $user is of type User
echo $user->name;
```

Without this extension, PHPStan would infer `$user` as `mixed`. With it, the type is correctly resolved from the `@yield T` annotation in `PromiseInterface` combined with the generic argument `User`.

## Requirements

- PHP 8.1+
- PHPStan ^2.1

## Installation

To use this extension require it in composer:

```bash
composer require --dev mtorromeo/phpstan-yield
```

The extension registers itself automatically if you have installed phpstan/extension-installer. Otherwise, include the extension manually in your project's PHPStan configuration:

```neon
includes:
    - vendor/mtorromeo/phpstan-yield/extension.neon
```

## Annotations

The extension recognizes three equivalent annotation tags on class docblocks:

| Tag | Description |
|-----|-------------|
| `@phpstan-yield <type>` | PHPStan-specific (recommended) |
| `@psalm-yield <type>` | Psalm-compatible alias |
| `@yield <type>` | Generic alias |

The type can be any valid PHPStan type expression, including:

- Concrete types: `@phpstan-yield string`, `@phpstan-yield User`
- Template types: `@phpstan-yield T` (resolved against generic arguments)
- Complex types: `@phpstan-yield array<string, int>`

## How It Works

When PHPStan analyses a `yield $value` expression, the extension:

1. Determines the type of `$value`
2. Traverses the full class hierarchy of that type (parent classes and all interfaces) using breadth-first search
3. Looks for a `@phpstan-yield`, `@psalm-yield`, or `@yield` tag in the docblock of each class/interface
4. Resolves the annotated type in the context of the declaring class (handling namespace imports correctly)
5. If the annotation uses a template type (e.g. `@phpstan-yield T`), binds it against the actual generic arguments of `$value`

The search stops at the first class or interface in the hierarchy that provides a yield annotation. Annotations on parent classes and interfaces are inherited, so you only need to annotate the base class or interface.

## Development

```bash
# Run tests
composer test

# Run static analysis
composer phpstan

# Format code
composer format
```

## License

MIT — see [LICENSE](LICENSE) for details.
