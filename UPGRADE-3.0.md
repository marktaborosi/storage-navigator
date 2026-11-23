# Upgrading from 2.x to 3.0

Version 3 is a rewrite. The ideas stay the same (a storage, a look, optional filters), but every class has a new name and the navigator is now a PSR-15 request handler. Most applications need to change only the few lines that build the navigator.

## Requirements

- PHP 8.3 or newer (was 8.2).
- The storage libraries are no longer installed automatically. Require the ones you use:

| You used | Now require |
| --- | --- |
| `PHPNativeAdapter`, `FlysystemAdapter` with a local adapter | nothing (included) |
| `S3StorageAdapter`, `AwsCloudStorageAdapter`, `GoogleCloudStorageAdapter` | `league/flysystem-aws-s3-v3` |
| `FtpAdapter` | `league/flysystem-ftp` |
| `SftpAdapter` | `league/flysystem-sftp-v3` |
| `ZipArchiveAdapter` | `league/flysystem-ziparchive` |
| `UnifiedArchiveAdapter` | `wapmorgan/unified-archive` |

## Building the navigator

Before:

```php
$navigator = new StorageNavigator(
    adapter: new PHPNativeAdapter(),
    renderer: new HtmlRenderer('basic-dark', new HtmlRendererConfig(['date_format' => 'Y-m-d H:i'])),
    rootPath: __DIR__ . '/storage/',
    filterBuilder: (new FileStructureFilterBuilder())->extensionNotEquals('log'),
);
$navigator->display();
```

After:

```php
$navigator = new StorageNavigator(
    Storage::local(__DIR__ . '/storage'),
    new Options(
        theme: 'modern-dark',
        dateFormat: 'Y-m-d H:i',
        filter: Filter::not(Filter::extension('log')),
    ),
);
$navigator->handleGlobals();   // or: $response = $navigator->handle($psrRequest);
```

The root path is now part of the storage: `Storage::local($directory)`, `Storage::flysystem($filesystem, 'sub/folder')`, `Storage::s3($client, $bucket, 'prefix')`.

## Adapters

| 2.x | 3.0 |
| --- | --- |
| `new PHPNativeAdapter()` + `rootPath` | `Storage::local($rootPath)` |
| `new FlysystemAdapter($filesystem)` | `Storage::flysystem($filesystem)` |
| `new S3StorageAdapter($bucket, $client)` | `Storage::s3($client, $bucket)` |
| `AwsCloudStorageAdapter`, `GoogleCloudStorageAdapter` | `Storage::s3()` (GCS through its S3-compatible endpoint) |
| `FtpAdapter` + `FtpConnection` + `initialize()` | `Storage::ftp(['host' => …, 'username' => …, 'password' => …, 'ssl' => true])` |
| `new SftpAdapter($host, $user, $password, $port, $root)` | `Storage::sftp(host: …, username: …, hostFingerprint: …, password: …, port: …, root: …)` |
| `new ZipArchiveAdapter($file)` | `Storage::zip($file)` |
| `new UnifiedArchiveAdapter($file)` | `Storage::archive($file)` |
| `NullAdapter` | `Storage::memory()` |
| your own `StorageNavigatorAdapterInterface` | implement `Storage\StorageInterface` (`stat`, `list`, `listRecursive`, `readStream`) |

An adapter no longer sends HTTP headers itself: `readStream()` returns a stream and the navigator builds the response.

## Renderers and themes

| 2.x | 3.0 |
| --- | --- |
| `new HtmlRenderer($theme, $config, $disableNavigation, $disableFileDownload)` | `Options(theme: …, dateFormat: …, features: new Features(navigation: …, download: …))` |
| `HtmlRendererConfig(['date_format' => …])` | `Options(dateFormat: …)` |
| `ConsoleRenderer` | `Rendering\TextRenderer::render($navigator->listing($path))` |
| `NullRenderer`, `HttpNavigationHandler`, `NullNavigationHandler` | not needed: requests go through `handle()` |
| your own `StorageNavigatorRendererInterface` | implement `Rendering\RendererInterface`, or override templates with `Options::$templatePaths` |

Theme names:

| 2.x | 3.0 |
| --- | --- |
| `basic-light`, `basic-mac`, `bootstrap-mason-light` | `modern-light` (or `modern` to follow the system) |
| `basic-dark` | `modern-dark` |
| `basic-norton`, `console-norton-commander` | `norton-commander` |
| `console-midnight-commander` | `midnight-commander` |
| `console-hacker` | `hacker` |
| `basic-terminal` | `terminal` |

The old names keep working in 3.x and trigger an `E_USER_DEPRECATED` notice. A theme can no longer be a path to an arbitrary CSS file; use `Theme::custom()` with your stylesheet's URL.

## Filters

`FileStructureFilterBuilder` and `FileStructureFilterer` are replaced by composable filter objects:

| 2.x | 3.0 |
| --- | --- |
| `->isFile()` | `Filter::files()` |
| `->isDirectory()` | `Filter::directories()` |
| `->nameEquals(['a', 'b'])` | `Filter::name('a', 'b')` |
| `->nameNotEquals('a')` | `Filter::not(Filter::name('a'))` |
| `->nameContains('x')` | `Filter::name('*x*')` |
| `->nameNotContains('x')` | `Filter::not(Filter::name('*x*'))` |
| `->extensionEquals(['pdf'])` | `Filter::extension('pdf')` |
| `->extensionNotEquals('log')` | `Filter::not(Filter::extension('log'))`* |
| several calls on one builder | `Filter::all(…)` |

\* `Filter::extension()` lets folders through, so `Filter::not(Filter::extension('log'))` would hide every folder. To hide only `.log` files, use `Filter::not(Filter::name('*.log'))`.

Behaviour changes:

- Filters are now enforced: a filtered-out file can no longer be downloaded by guessing its URL.
- Extension filters no longer hide folders.
- Dotfiles are hidden by default; set `Options(showHidden: true)` to show them.

## HTTP

- Navigation uses GET links (`?path=docs`) instead of hidden POST forms, so pages can be bookmarked and the Back button works.
- Downloads: `?action=download&path=…`.
- `display()` is gone: call `handleGlobals()` in plain PHP, or `handle($request)` with a PSR-7 request.

## New in 3.0

Preview, folder downloads as ZIP, upload, new folder, rename and delete (all optional), keyboard navigation, search, grid view, dark mode, a Hungarian translation, and the `authorize` callback for per-user permissions. See the [README](README.md).
