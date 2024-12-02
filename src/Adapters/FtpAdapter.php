<?php

namespace Marktaborosi\StorageBrowser\Adapters;

use Exception;
use Marktaborosi\StorageBrowser\Adapters\FTP\FtpConnection;
use Marktaborosi\StorageBrowser\Builders\FileStructureBuilder;
use Marktaborosi\StorageBrowser\Entities\DirectoryAttribute;
use Marktaborosi\StorageBrowser\Entities\FileAttribute;
use Marktaborosi\StorageBrowser\Entities\FileStructure;
use Marktaborosi\StorageBrowser\Interfaces\StorageBrowserAdapterInterface;
use Marktaborosi\StorageBrowser\Traits\PathHelperTrait;

class FtpAdapter implements StorageBrowserAdapterInterface
{
    use PathHelperTrait;

    private FtpConnection $ftp;
    private string $rootDir;

    public function __construct(
        FtpConnection $ftpConnection,
        string        $rootDir = '/'
    )
    {
        $this->ftp = $ftpConnection;
        $this->rootDir = $rootDir;
    }

    /**
     * @throws Exception
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
     * @throws Exception
     */
    public function getFileStructure(string $location): FileStructure
    {
        $objects = [];
        $objects['directories'] = [];
        $objects['files'] = [];

        $structureBuilder = new FileStructureBuilder();
        $location = $this->normalizePath($location);

        $entries = $this->ftp->nlist($location);

        if ($entries === false) {
            throw new Exception("Could not retrieve file list from FTP server.");
        }

        foreach ($entries as $file) {
            if ($file == ".." || $file == ".") continue;

            $filePath = $location . "/" . $file;

            if ($this->isDirectory($filePath)) {
                $objects['directories'][] = $filePath;
            } else {
                $objects['files'][$filePath] = $filePath;
            }
        }

        foreach ($objects['directories'] as $dir) {
            $lastModified = $this->ftp->mdtm($dir);
            $structureBuilder->addDirectory(
                new DirectoryAttribute(
                    name: basename($dir),
                    path: $this->getNormalizedDirname($dir),
                    lastModified: $lastModified !== -1 ? $lastModified : null
                )
            );
        }

        foreach ($objects['files'] as $file) {
            $size = $this->ftp->size($file);
            $lastModified = $this->ftp->mdtm($file);
            $structureBuilder->addFile(
                new FileAttribute(
                    directoryPath: $this->getNormalizedDirname($file),
                    filename: basename($file),
                    extension: pathinfo($file, PATHINFO_EXTENSION),
                    byteSize: $size,
                    lastModified: $lastModified !== -1 ? $lastModified : null
                )
            );
        }

        return $structureBuilder->sortBy()->build();
    }

    public function fileOrDirectoryExists(string $location): bool
    {
        $location = $this->rootDir . $location;

        if ($this->ftp->chdir($location)) {
            $this->ftp->chdir($this->rootDir);
            return true;
        }

        $size = $this->ftp->size($location);
        return $size != -1;
    }

    /**
     * @throws Exception
     */
    public function downloadFile(string $filePath): void
    {
        $localFile = tempnam(sys_get_temp_dir(), 'ftp_download_');

        if ($this->ftp->get($localFile, $filePath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($localFile));
            readfile($localFile);

            unlink($localFile);
        } else {
            throw new Exception("Could not download the file from FTP server.");
        }
    }

    public function __destruct()
    {
        $this->ftp->close();
    }

    private function isDirectory(string $path): bool
    {
        $originalDir = $this->ftp->pwd();

        if (@$this->ftp->chdir($path)) {
            $this->ftp->chdir($originalDir);
            return true;
        }

        return false;
    }

    private function normalizePath(string $path): string
    {
        // Remove trailing slashes, then ensure there's exactly one slash between rootDir and the location
        return rtrim($this->rootDir, '/') . '/' . ltrim($path, '/');
    }
}
