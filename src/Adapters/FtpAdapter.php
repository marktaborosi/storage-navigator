<?php

namespace Marktaborosi\StorageNavigator\Adapters;

use Exception;
use Marktaborosi\StorageNavigator\Adapters\FTP\FtpConnection;
use Marktaborosi\StorageNavigator\Builders\FileStructureBuilder;
use Marktaborosi\StorageNavigator\Entities\DirectoryAttribute;
use Marktaborosi\StorageNavigator\Entities\FileAttribute;
use Marktaborosi\StorageNavigator\Entities\FileStructure;
use Marktaborosi\StorageNavigator\Interfaces\StorageNavigatorAdapterInterface;
use Marktaborosi\StorageNavigator\Traits\PathHelperTrait;

/**
 * Class FtpAdapter
 *
 * Provides functionality to interact with an FTP server as a storage backend.
 * Implements the StorageNavigatorAdapterInterface to support file structure retrieval,
 * file existence checks, and file downloads over FTP.
 *
 * @package Marktaborosi\StorageNavigator\Adapters
 * @pattern Adapter
 */
class FtpAdapter implements StorageNavigatorAdapterInterface
{
    use PathHelperTrait;

    /**
     * @var FtpConnection FTP connection instance for server communication.
     */
    private FtpConnection $ftp;

    /**
     * @var string The root directory on the FTP server.
     */
    private string $rootDir;

    /**
     * Constructor.
     *
     * @param FtpConnection $ftpConnection An established FTP connection.
     * @param string $rootDir The root directory for file operations (default: '/').
     */
    public function __construct(FtpConnection $ftpConnection, string $rootDir = '/')
    {
        $this->ftp = $ftpConnection;
        $this->rootDir = $rootDir;
    }

    /**
     * Initialize the FTP connection.
     *
     * @param string $host FTP server hostname or IP address.
     * @param string $username Username for FTP login.
     * @param string $password Password for FTP login.
     * @param int $port FTP server port (default: 21).
     * @throws Exception If the connection or login fails.
     */
    public function initialize(string $host, string $username, string $password, int $port = 21): void
    {
        if (!$this->ftp->connect($host, $port)) {
            throw new Exception("Could not connect to FTP server.");
        }
        if (!$this->ftp->login($username, $password)) {
            throw new Exception("FTP login failed.");
        }
        $this->ftp->pasv(true);
    }

    /**
     * Retrieve the file structure of a given directory.
     *
     * @param string $location Directory path to scan.
     * @return FileStructure The file structure of the directory.
     * @throws Exception If the directory listing cannot be retrieved.
     */
    public function getFileStructure(string $location): FileStructure
    {
        $objects = ['directories' => [], 'files' => []];
        $structureBuilder = new FileStructureBuilder();
        $location = $this->normalizePath($location);

        $entries = $this->ftp->nlist($location);
        if ($entries === false) {
            throw new Exception("Could not retrieve file list from FTP server.");
        }

        foreach ($entries as $file) {
            if ($file === '..' || $file === '.') continue;
            $filePath = $location . '/' . $file;
            $this->isDirectory($filePath)
                ? $objects['directories'][] = $filePath
                : $objects['files'][$filePath] = $filePath;
        }

        foreach ($objects['directories'] as $dir) {
            $structureBuilder->addDirectory(new DirectoryAttribute(
                name: basename($dir),
                path: $this->getNormalizedDirname($dir),
                lastModified: $this->ftp->mdtm($dir) !== -1 ? $this->ftp->mdtm($dir) : null
            ));
        }

        foreach ($objects['files'] as $file) {
            $structureBuilder->addFile(new FileAttribute(
                directoryPath: $this->getNormalizedDirname($file),
                filename: basename($file),
                extension: pathinfo($file, PATHINFO_EXTENSION),
                byteSize: $this->ftp->size($file),
                lastModified: $this->ftp->mdtm($file) !== -1 ? $this->ftp->mdtm($file) : null
            ));
        }

        return $structureBuilder->sortByAZ()->build();
    }

    /**
     * Check if a file or directory exists.
     *
     * @param string $location Path to check.
     * @return bool True if the file or directory exists, false otherwise.
     */
    public function fileOrDirectoryExists(string $location): bool
    {
        $location = $this->rootDir . $location;
        return $this->ftp->chdir($location) || $this->ftp->size($location) !== -1;
    }

    /**
     * Download a file from the FTP server.
     *
     * @param string $filePath Path of the file to download.
     * @throws Exception If the download fails.
     */
    public function downloadFile(string $filePath): void
    {
        $localFile = tempnam(sys_get_temp_dir(), 'ftp_download_');
        if (!$this->ftp->get($localFile, $filePath)) {
            throw new Exception("Could not download the file from FTP server.");
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($localFile));
        readfile($localFile);
        unlink($localFile);
    }

    /**
     * Destructor to close the FTP connection.
     */
    public function __destruct()
    {
        $this->ftp->close();
    }

    /**
     * Check if a given path is a directory.
     *
     * @param string $path Path to check.
     * @return bool True if the path is a directory, false otherwise.
     */
    private function isDirectory(string $path): bool
    {
        $originalDir = $this->ftp->pwd();
        if (@$this->ftp->chdir($path)) {
            $this->ftp->chdir($originalDir);
            return true;
        }
        return false;
    }

    /**
     * Normalize a given path by appending it to the root directory.
     *
     * @param string $path Path to normalize.
     * @return string Normalized path.
     */
    private function normalizePath(string $path): string
    {
        return rtrim($this->rootDir, '/') . '/' . ltrim($path, '/');
    }
}
