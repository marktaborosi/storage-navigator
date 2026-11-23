# Contributing

Thanks for helping. Bug reports, fixes, translations and themes are all welcome.

## Before you start

- For a bug, open an issue with the steps to reproduce it, your PHP version and your storage.
- For a larger change, open an issue first so we can agree on the approach.
- Security problems go through [SECURITY.md](SECURITY.md), not public issues.

## Development setup

```bash
git clone https://github.com/marktaborosi/storage-navigator.git
cd storage-navigator
composer install
php -S localhost:8000 -t examples/demo
```

There is no build step: CSS and JavaScript in `resources/assets` are used as they are.

## Checks

Every pull request must pass:

```bash
composer test      # PHPUnit
composer analyse   # PHPStan level 8
node --check resources/assets/navigator.js
```

Add tests for what you change. Anything touching paths, permissions, uploads or output escaping
needs a test in `tests/Integration/SecurityTest.php` or `WriteOperationsTest.php`.

## Guidelines

- `declare(strict_types=1)` everywhere, final classes and readonly properties by default.
- Never trust a path from the request: always go through `Path::fromString()` and `ActionContext::authorize()`.
- The browser script must not build HTML from strings; set `textContent` and attributes.
- New UI strings go into every file in `resources/translations` (a test checks that the keys match).
- Keep the page working without JavaScript for browsing and downloads.

## Adding a translation

Copy `resources/translations/en.php` to `<locale>.php`, translate the values and open a pull request.

## Commit messages and changelog

Write commit messages in the imperative ("Add Polish translation"). Add a line under
"Unreleased" in [CHANGELOG.md](CHANGELOG.md) for anything users will notice.
