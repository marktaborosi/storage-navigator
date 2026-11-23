# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [3.0.0] - unreleased

A rewrite. See [UPGRADE-3.0.md](UPGRADE-3.0.md) for migrating from 2.x.

### Added
- PSR-15 request handler (`StorageNavigator::handle()`) and `handleGlobals()` for plain PHP.
- Storages on top of Flysystem 3: `Storage::local()`, `s3()`, `ftp()`, `sftp()`, `zip()`, `flysystem()`, `memory()`, plus `archive()` for RAR, 7z and TAR.
- Preview of images, video, audio, PDF and text with syntax highlighting; HTTP Range support for media.
- Folder downloads as streamed ZIP files (`Features::$zipDownload`).
- Optional upload, new folder, rename and delete, protected by a signed CSRF token.
- `authorize` callback for per-user permissions on every operation.
- Composable filters (`Filter::name()`, `path()`, `extension()`, `all()`, `any()`, `not()` …).
- New modern theme with light and dark mode, list and grid views, live search, sorting and keyboard navigation.
- Retro console themes rebuilt on the same features: Norton Commander, Midnight Commander, hacker, terminal.
- English and Hungarian translations.
- JSON listing (`Accept: application/json`) and `TextRenderer` for command-line use.

### Changed
- Requires PHP 8.3.
- Storage packages (AWS SDK, phpseclib, unified-archive, ext-ftp, ext-zip) are optional instead of required.
- Navigation uses bookmarkable GET links instead of POST forms.
- Assets are served with cache headers instead of being inlined into every page; no external fonts or CDNs.

### Removed
- The 2.x adapters, renderers, navigation handlers, `FileStructureFilterBuilder` and the Gulp/SCSS build.

### Security
- Paths outside the root can no longer be listed or downloaded (CWE-22).
- File names can no longer inject script into the page (stored XSS, CWE-79).
- `disableFileDownload`, `disableNavigation` and filters are now enforced on the server.
- Themes can no longer read arbitrary files.
- Dotfiles are hidden and inaccessible by default.
- Strict Content Security Policy on every page; previews are sandboxed.

## [2.2.0] - 2025-04-01
- AWS S3 and Google Cloud Storage adapters.

## [2.0.0] - 2024-12-06
- Themes, filters and the console renderer.

## [1.0.0] - 2024-12-03
- First release.

[Unreleased]: https://github.com/marktaborosi/storage-navigator/compare/v3.0.0...HEAD
[3.0.0]: https://github.com/marktaborosi/storage-navigator/compare/v2.2.0...v3.0.0
[2.2.0]: https://github.com/marktaborosi/storage-navigator/compare/v2.0.0...v2.2.0
[2.0.0]: https://github.com/marktaborosi/storage-navigator/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/marktaborosi/storage-navigator/releases/tag/v1.0.0
