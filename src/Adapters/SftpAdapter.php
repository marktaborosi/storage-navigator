<?php

namespace Marktaborosi\StorageBrowser\Adapters;

use Exception;
use Marktaborosi\StorageBrowser\Builders\FileStructureBuilder;
use Marktaborosi\StorageBrowser\Entities\DirectoryAttribute;
use Marktaborosi\StorageBrowser\Entities\FileAttribute;
use Marktaborosi\StorageBrowser\Entities\FileStructure;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserAdapterInterface;
use Marktaborosi\StorageBrowser\Traits\PathHelperTrait;
use phpseclib3\Net\SFTP;

/**
 * SFTP adapter using php sec lib for browsing files and directories over SFTP.
 *
 * The SFTPAdapter class implements the FileBrowserAdapterInterface and provides
 * methods to retrieve the structure of a location on an SFTP server and to
 * download files.
 *
 * @package Marktaborosi\StorageBrowser\Adapters
 * @pattern Adapter / Facade
 *
 */
class SftpAdapter implements StorageBrowserAdapterInterface
{
    use PathHelperTrait;

    private SFTP $sftp;
    private string $rootDir;

    /**
     * Constructor for the SFTPAdapter.
     *
     * @param string $host SFTP server hostname or IP address.
     * @param string $username SFTP username.
     * @param string $password SFTP password.
     * @param int $port SFTP port (default: 22).
     * @param string $rootDir Root directory on the SFTP server.
     * @throws Exception
     */
    public function __construct(
        string $host,
        string $username,
        string $password,
        int $port = 22,
        string $rootDir = '/'
    ) {
        $this->sftp = new SFTP($host, $port);

        if (!$this->sftp->login($username, $password)) {
            throw new Exception("SFTP login failed.");
        }

        $this->rootDir = rtrim($rootDir, '/') . '/';
    }

    /**
     * Get the structure of a given location (directory) on the SFTP server.
     *
     * @param string $location The directory location to scan on the SFTP server.
     * @return FileStructure The structure of files and directories in the given location.
     * @throws Exception
     */
    public function getFileStructure(string $location): FileStructure
    {
        $location = $this->rootDir . ltrim($location, '/');
        $entries = $this->sftp->nlist($location);

        if ($entries === false) {
            throw new Exception("Could not retrieve directory listing from SFTP server.");
        }

        $structureBuilder = new FileStructureBuilder();

        foreach ($entries as $entry) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            $filePath = $location . '/' . $entry;
            $stat = $this->sftp->stat($filePath);

            if ($stat === false) {
                continue;
            }

            if ($this->isDirectory($stat)) {
                $lastModified = $stat['mtime'] ?? false;
                $structureBuilder->addDirectory(
                    directory: new DirectoryAttribute(
                        name: basename($filePath),
                        path: $this->getNormalizedDirname($filePath),
                        lastModified: $lastModified !== false ? $lastModified : null
                    )
                );
            } else {
                $size = $stat['size'] ?? 0;
                $lastModified = $stat['mtime'] ?? false;

                $structureBuilder->addFile(
                    file: new FileAttribute(
                        directoryPath: $this->getNormalizedDirname($filePath),
                        filename: basename($filePath),
                        extension: pathinfo($filePath, PATHINFO_EXTENSION),
                        byteSize: $size,
                        lastModified: $lastModified !== false
                            ? $lastModified : null
                    )
                );
            }
        }

        return $structureBuilder->sortBy()->build();
    }

    /**
     * Check if the specified location exists on the SFTP server.
     *
     * @param string $location The directory or file location to check on the SFTP server.
     * @return bool True if the location exists, false otherwise.
     */
    public function fileOrDirectoryExists(string $location): bool
    {
        $location = $this->rootDir . ltrim($location, '/');
        return $this->sftp->stat($location) !== false;
    }

    /**
     * Download a file from the SFTP server.
     *
     * @param string $filePath The path of the file to download from the SFTP server.
     * @throws Exception
     */
    public function downloadFile(string $filePath): void
    {
        $filePath = $this->rootDir . ltrim($filePath, '/');
        $localFile = tempnam(sys_get_temp_dir(), 'sftp_download_');

        if (!$this->sftp->get($filePath, $localFile)) {
            throw new Exception("Could not download the file from SFTP server.");
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($localFile));
        readfile($localFile);

        unlink($localFile);
    }

    /**
     * Helper method to check if a stat entry corresponds to a directory.
     *
     * @param array $stat The stat array from the SFTP server.
     * @return bool True if it's a directory, false otherwise.
     */
    private function isDirectory(array $stat): bool
    {
        return isset($stat['mode']) && ($stat['mode'] & 040000) === 040000;
    }
}
