<?php

declare(strict_types=1);

namespace Marktaborosi\StorageNavigator\Storage;

use Aws\S3\S3ClientInterface;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Marktaborosi\StorageNavigator\Exception\ConfigurationException;
use Marktaborosi\StorageNavigator\Exception\MissingDependencyException;
use Marktaborosi\StorageNavigator\Exception\NotFoundException;
use Marktaborosi\StorageNavigator\Path\Path;

/**
 * Shortcuts for the common storages. Each one needs only the matching Flysystem
 * adapter package; a missing package raises a MissingDependencyException that says
 * what to install.
 */
final class Storage
{
    private function __construct()
    {
    }

    /**
     * A directory on the local disk.
     *
     * Symbolic links are hidden from listings, and every access is checked with
     * realpath() so that a link cannot lead outside the directory.
     */
    public static function local(string $directory): FlysystemStorage
    {
        $root = realpath($directory);
        if ($root === false || !is_dir($root)) {
            throw new ConfigurationException(sprintf('The directory "%s" does not exist.', $directory));
        }

        $adapter = new LocalFilesystemAdapter($root, linkHandling: LocalFilesystemAdapter::SKIP_LINKS);
        $prefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $guard = static function (Path $path) use ($root, $prefix): void {
            // Check the deepest part of the path that exists: a write target may not exist yet.
            $candidate = $path;
            $real = realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate->toString()));
            while ($real === false && !$candidate->isRoot()) {
                $candidate = $candidate->parent();
                $real = realpath($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate->toString()));
            }
            if ($real === false || ($real !== $root && !str_starts_with($real, $prefix))) {
                throw new NotFoundException('Not found.');
            }
        };

        return new FlysystemStorage(new Filesystem($adapter), guard: $guard);
    }

    /**
     * Any Flysystem filesystem, optionally narrowed to a sub-directory.
     */
    public static function flysystem(FilesystemOperator $filesystem, string $root = ''): FlysystemStorage
    {
        return new FlysystemStorage($filesystem, $root);
    }

    /**
     * An empty in-memory filesystem, handy for demos and tests.
     */
    public static function memory(): FlysystemStorage
    {
        self::requireClass(InMemoryFilesystemAdapter::class, 'league/flysystem-memory', 'Storage::memory()');

        return new FlysystemStorage(new Filesystem(new InMemoryFilesystemAdapter()));
    }

    /**
     * A ZIP file, readable and writable. Missing files are created on the first write.
     */
    public static function zip(string $file): FlysystemStorage
    {
        self::requireClass(ZipArchiveAdapter::class, 'league/flysystem-ziparchive', 'Storage::zip()');

        return new FlysystemStorage(new Filesystem(new ZipArchiveAdapter(new FilesystemZipArchiveProvider($file))));
    }

    /**
     * Any archive format wapmorgan/unified-archive can read (RAR, 7z, TAR, ZIP ...), read-only.
     */
    public static function archive(string $file): ArchiveStorage
    {
        return new ArchiveStorage($file);
    }

    /**
     * An Amazon S3 bucket, or any S3-compatible service (MinIO, Cloudflare R2, Wasabi,
     * Google Cloud Storage interoperability mode), optionally narrowed to a key prefix.
     */
    public static function s3(S3ClientInterface $client, string $bucket, string $prefix = ''): FlysystemStorage
    {
        self::requireClass(AwsS3V3Adapter::class, 'league/flysystem-aws-s3-v3', 'Storage::s3()');

        return new FlysystemStorage(new Filesystem(new AwsS3V3Adapter($client, $bucket, $prefix)));
    }

    /**
     * An FTP server. Pass 'ssl' => true for FTPS whenever the server supports it:
     * plain FTP sends the password unencrypted.
     *
     * @param array<string, mixed> $options FtpConnectionOptions::fromArray() options: host, username, password, root, port, ssl, timeout, passive ...
     */
    public static function ftp(array $options): FlysystemStorage
    {
        self::requireClass(FtpAdapter::class, 'league/flysystem-ftp', 'Storage::ftp()');
        $options += ['root' => '/'];

        return new FlysystemStorage(new Filesystem(new FtpAdapter(FtpConnectionOptions::fromArray($options))));
    }

    /**
     * An SFTP server.
     *
     * $hostFingerprint pins the server key (for example "SHA256:..." as printed by
     * ssh-keyscan | ssh-keygen -lf -). Pass null only on trusted networks: without it a
     * man-in-the-middle can capture the credentials.
     */
    public static function sftp(
        string $host,
        string $username,
        ?string $hostFingerprint,
        ?string $password = null,
        ?string $privateKey = null,
        ?string $passphrase = null,
        int $port = 22,
        string $root = '/',
        int $timeout = 10,
    ): FlysystemStorage {
        self::requireClass(SftpAdapter::class, 'league/flysystem-sftp-v3', 'Storage::sftp()');

        $provider = new SftpConnectionProvider(
            host: $host,
            username: $username,
            password: $password,
            privateKey: $privateKey,
            passphrase: $passphrase,
            port: $port,
            timeout: $timeout,
            hostFingerprint: $hostFingerprint,
        );

        return new FlysystemStorage(new Filesystem(new SftpAdapter($provider, $root)));
    }

    private static function requireClass(string $class, string $package, string $feature): void
    {
        if (!class_exists($class)) {
            throw MissingDependencyException::forPackage($package, $feature);
        }
    }
}
