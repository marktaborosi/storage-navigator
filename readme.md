
# Storage Navigator

[![Author](https://img.shields.io/badge/author-@marktaborosi-blue.svg)](https://github.com/marktaborosi)
[![Latest Version](https://img.shields.io/github/release/marktaborosi/storage-navigator.svg?style=flat-square)](https://github.com/marktaborosi/storage-navigator/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](https://github.com/thephpleague/flysystem/blob/master/LICENSE)
[![Quality Assurance](https://github.com/thephpleague/flysystem/workflows/Quality%20Assurance/badge.svg?branch=2.x)](https://github.com/thephpleague/flysystem/actions?query=workflow%3A%22Quality+Assurance%22)
![php 7.2+](https://img.shields.io/badge/php-min%208.2-red.svg)



A simple, lightweight, and universal storage/file browser and navigator library with rendering options.

This library allows accessing various storage systems via adapters and rendering their contents using different renderers. It supports navigating and displaying storage contents across various platforms, including FTP, SFTP, local filesystems, and more.

## Supported Adapters

- **Flysystem**: Provides integration with the *League\Flysystem* library for a unified filesystem interface. The following Flysystem adapters are currently available:
    - **Local**: Accesses and interacts with local file systems.
    - **Dropbox**: Provides access to Dropbox storage.
    - **Amazon S3 (AWS)**: Enables integration with Amazon S3 for cloud storage. Has AsyncAws too.
    - **Azure Blob**: Provides access to Microsoft Azure Blob Storage, allowing interaction with objects stored in Azure's cloud-based storage service.
    - **FTP**: Allows FTP connections for navigating and managing files on FTP servers.
    - **SFTP**: Secure FTP connections to remote servers via SSH. Has version 2 and 3 of phpseclib.
    - **MongoDB GridFS**: Allows interaction with MongoDB's GridFS, a specification for storing large files in MongoDB, enabling streaming of large files.
    - **Rackspace**: Connects with Rackspace cloud storage.
    - **Google Drive**: Provides access to Google Drive for cloud file management.
    - **GitLab**: Accesses files stored on GitLab, a web-based Git repository manager, for managing repository data and artifacts.
    - **WebDAV**: Enables file interactions with WebDAV-compatible servers.
    - **InMemory**: This adapter keeps the filesystem completely in memory. This is useful when you need a filesystem, but don’t want it persisted.


## Renderers

- **HTML Renderer**: A fully-featured renderer designed for browsers, with support for various themes and file navigation options.
- **Console Renderer**: A simple CLI-based renderer for listing files, without navigation support.
- **Null Renderer**: A placeholder renderer, useful for debugging or specific use cases.

## HTML Renderer Themes

The HTML renderer supports multiple skins. Here are some of the available themes:

- **basic-dark**
- **basic-light**
- **basic-mac**
- **basic-norton**
- **basic-terminal**
- **console-hacker**
- **console-midnight-commander**
- **console-norton-commander**

Each theme provides a different look and feel for the file browser. 
Check themes at the end of documentation.

## Installation

You can install **Storage Navigator** via Composer:

```bash
composer require marktaborosi/storage-navigator
````

## Usage

Below are examples of how to use **Storage Navigator** with different adapters and renderers.

### Native Adapter Example

```php
$adapter = new PHPNativeAdapter();

// Create config
$config = new FileBrowserConfig([
    'date_format' => "Y-m-d H:i:s", // Format for displaying file modification dates (same format as PHP's `date()` function)
    'ignore_filenames' => [], // An array of specific filenames to exclude from the view.
    'ignore_extensions' => [] // A list of file extensions to exclude from the view.
]);

// Create renderer
$renderer = new HtmlRenderer(
    theme: "console-norton-commander", // Theme to use for rendering
    disableNavigation: false, // Set to true to disable navigation, only allowing browsing in the current folder
    disableFileDownload: false // Set to true to disable file downloads upon click
);

$browser = new StorageBrowser(
    adapter: $adapter,
    renderer: $renderer,
    config: $config,
    rootPath: __DIR__ . "/storage/" // Starting directory of the filesystem
);

$browser->display();
```


## Flysystem Adapter with Local Adapter Example

For detailed information on Flysystem, its adapters, and usage, please refer to the [Flysystem Documentation](https://flysystem.thephpleague.com/docs/).

```php
$localAdapter = new LocalFilesystemAdapter(__DIR__."/storage/");
$filesystem = new Filesystem($localAdapter);
$adapter = new FlysystemAdapter($filesystem);

$renderer = new HtmlRenderer(theme: 'console-midnight-commander');
$config = new FileBrowserConfig([
    'date_format' => "M d Y H:i", // Format for displaying file modification dates (same format as PHP's `date()` function)
    'ignore_filenames' => ["file.log"], // Exclude file.log from list
    'ignore_extensions' => ['zip', 'php', 'html'] // Exclude files with these extensions
]);

$browser = new StorageBrowser(
    adapter: $adapter,
    renderer: $renderer,
    config: $config,
    rootPath: "" // Starting directory of the filesystem
);

$browser->display();
```

### FTP Adapter Example

```php
// Initialize FTP adapter
$adapter = new FtpAdapter(new FtpConnection());

$adapter->initialize(
    'test.rebex.net',   // FTP server address
    'demo',             // FTP username
    'password',         // FTP password
    21,                 // FTP port (default 21)
    "/"                 // Initial directory
);

// Create renderer
$renderer = new HtmlRenderer(theme: 'console-norton-commander');

// Create config
$config = new FileBrowserConfig([
    'ignore_filenames' => [],
    'ignore_extensions' => [],
]);

$browser = new StorageBrowser(
    adapter: $adapter,
    renderer: $renderer,
    config: $config,
    rootPath: ""
);

$browser->display();
```

### SFTP Adapter Example

```php
// Initialize SFTP adapter
$adapter = new SftpAdapter(
    'test.rebex.net',   // SFTP server address
    'demo',             // SFTP username
    'password',         // SFTP password
    22,                 // SFTP port (default 22)
    ""                  // Initial directory
);

// Create renderer
$renderer = new HtmlRenderer(theme: 'console-norton-commander');

// Create config
$config = new FileBrowserConfig([
    'ignore_filenames' => [],
    'ignore_extensions' => [],
]);

$browser = new StorageBrowser(
    adapter: $adapter,
    renderer: $renderer,
    config: $config,
    rootPath: ""
);

$browser->display();
```

### Unified Archive Adapter Example

```php
// Initialize Unified Archive adapter
$adapter = new UnifiedArchiveAdapter(__DIR__ . "/storage/zips/1mb-fake-sample.zip");

// Create renderer
$renderer = new HtmlRenderer(theme: 'console-norton-commander');

// Create config
$config = new FileBrowserConfig();

$browser = new StorageBrowser(
    adapter: $adapter,
    renderer: $renderer,
    config: $config,
    rootPath: ""
);

// Render Browser
$browser->display();
```

### ZIP Archive Adapter Example

```php
// Initialize ZIP Archive adapter
$adapter = new ZipArchiveAdapter(__DIR__ . "/storage/zips/1mb-fake-sample.zip");

// Create renderer
$renderer = new HtmlRenderer(theme: 'console-norton-commander');

// Create config
$config = new FileBrowserConfig([
]);

$browser = new StorageBrowser(
    adapter: $adapter,
    renderer: $renderer,
    config: $config,
    rootPath: ""
);

// Render Browser
$browser->display();
```

## Configuration

You can configure various options for the file browser, such as:

- **date_format**: The format for displaying file modification dates. This follows the same format as PHP's date() function. For example, Y-m-d H:i:s would display dates as 2024-01-01 15:30:00.
  
- **ignore_filenames**: An array of specific filenames that should be ignored. This can be useful for excluding temporary or system files from the view.
  
- **ignore_extensions**: A list of file extensions to exclude. For instance, you might exclude zip files or certain types of configuration files by adding them to this array.

## HTTP Renderer Themes

Below are some screenshots of the available themes:

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
    <div style="text-align: center;">
        <img src="examples/screenshots/basic-dark.png" alt="basic-dark" style="width: 100%;"/>
        <p><strong>basic-dark</strong></p>
    </div>
    <div style="text-align: center;">
        <img src="examples/screenshots/basic-light.png" alt="basic-light" style="width: 100%;"/>
        <p><strong>basic-light</strong></p>
    </div>
    <div style="text-align: center;">
        <img src="examples/screenshots/basic-mac.png" alt="basic-mac" style="width: 100%;"/>
        <p><strong>basic-mac</strong></p>
    </div>
    <div style="text-align: center;">
        <img src="examples/screenshots/basic-norton.png" alt="basic-norton" style="width: 100%;"/>
        <p><strong>basic-norton</strong></p>
    </div>
    <div style="text-align: center;">
        <img src="examples/screenshots/basic-terminal.png" alt="basic-terminal" style="width: 100%;"/>
        <p><strong>basic-terminal</strong></p>
    </div>
    <div style="text-align: center;">
        <img src="examples/screenshots/console-hacker.png" alt="console-hacker" style="width: 100%;"/>
        <p><strong>console-hacker</strong></p>
    </div>
    <div style="text-align: center;">
        <img src="examples/screenshots/console-midnight-commander.png" alt="console-midnight-commander" style="width: 100%;"/>
        <p><strong>console-midnight-commander</strong></p>
    </div>
    <div style="text-align: center;">
        <img src="examples/screenshots/console-norton-commander.png" alt="console-norton-commander" style="width: 100%;"/>
        <p><strong>console-norton-commander</strong></p>
    </div>
</div>

## Contributing

If you'd like to contribute to the project, feel free to fork the repository and submit a pull request.
