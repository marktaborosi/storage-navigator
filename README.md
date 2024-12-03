
# Storage Navigator

A simple, lightweight, and universal storage/file browser and navigator library with rendering options.

This library allows accessing various storage systems via adapters and rendering their contents using different renderers. It supports navigating and displaying storage contents across various platforms, including FTP, SFTP, local filesystems, and more.

## Supported Adapters

- **League\Flysystem**: Provides integration with the Flysystem library for a unified filesystem interface.
- **FTP**: Access FTP servers and navigate their directories.
- **SFTP**: Secure FTP connections to remote servers.
- **Native (PHP built-in functions)**: Uses PHP's built-in `scandir` and other native file system functions.
- **Warpmorgan\UnifiedArchive**: For working with archive files like TAR, GZ, etc.
- **ZIP**: Access and interact with ZIP archives.
- **Null**: A placeholder adapter for scenarios where no actual storage is needed.

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

Each theme provides a different look and feel for the file browser. Below are some screenshots of the available themes:

![basic-dark](screenshots/basic-dark.png)
![basic-light](screenshots/basic-light.png)
![basic-mac](screenshots/basic-mac.png)
![basic-norton](screenshots/basic-norton.png)
![basic-terminal](screenshots/basic-terminal.png)
![console-hacker](screenshots/console-hacker.png)
![console-midnight-commander](screenshots/console-midnight-commander.png)
![console-norton-commander](screenshots/console-norton-commander.png)

## Installation

You can install **Storage Navigator** via Composer:

```bash
composer require marktaborosi/storage-navigator
```

## Usage

Below are examples of how to use **Storage Navigator** with different adapters and renderers.

### Native Adapter Example

```php
$adapter = new PHPNativeAdapter();

// Create config
$config = new FileBrowserConfig([
    'date_format' => "Y-m-d H:i:s",
    'ignore_filenames' => [],
    'ignore_extensions' => []
]);

// Create renderer
$renderer = new HtmlRenderer(
    theme: "console-norton-commander",
    disableNavigation: false,
    disableFileDownload: false
);

$browser = new StorageBrowser(
    adapter: $adapter,
    renderer: $renderer,
    config: $config,
    rootPath: __DIR__ . "/storage/"
);

$browser->display();
```

### Flysystem Adapter with Local Adapter Example

```php
$localAdapter = new LocalFilesystemAdapter(__DIR__."/storage/");
$filesystem = new Filesystem($localAdapter);
$adapter = new FilesystemAdapter($filesystem);

$renderer = new HtmlRenderer(theme: 'console-midnight-commander');
$config = new FileBrowserConfig([
    'date_format' => "M d Y H:i",
    'ignore_filenames' => ["rars"],
    'ignore_extensions' => ['zip', 'php', 'html']
]);

$browser = new StorageBrowser(
    adapter: $adapter,
    renderer: $renderer,
    config: $config,
    rootPath: ""
);

$browser->display();
```

### FTP Adapter Example

Refer to the examples directory for FTP integration examples.

## Configuration

You can configure various options for the file browser, such as:

- **`date_format`**: The format for displaying file modification dates.
- **`ignore_filenames`**: List of filenames to ignore.
- **`ignore_extensions`**: List of file extensions to ignore.

## Contributing

If you'd like to contribute to the project, feel free to fork the repository and submit a pull request. Please ensure that your code follows the project's coding standards and that you include tests for any new features.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
