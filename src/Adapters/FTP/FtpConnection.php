<?php

namespace Marktaborosi\StorageBrowser\Adapters\FTP;

use FTP\Connection;

/**
 * Class FtpConnection
 *
 * A wrapper class for FTP operations using PHP's FTP extension.
 * Provides an abstraction over native FTP functions to manage connections, file transfers,
 * directory operations, and more.
 *
 * @package Marktaborosi\StorageBrowser\Adapters\FTP
 */
class FtpConnection
{
    /**
     * @var Connection|false $connection The FTP connection resource or false if not connected.
     */
    private Connection|false $connection;

    /**
     * Establishes a connection to an FTP server.
     *
     * @param string $host The hostname or IP address of the FTP server.
     * @param int $port The port number for the FTP connection (default: 21).
     * @return bool True if the connection is successful, false otherwise.
     */
    public function connect(string $host, int $port = 21): bool
    {
        $this->connection = ftp_connect($host, $port);
        return $this->connection !== false;
    }

    /**
     * Logs in to the connected FTP server.
     *
     * @param string $username The FTP username.
     * @param string $password The FTP password.
     * @return bool True if login is successful, false otherwise.
     */
    public function login(string $username, string $password): bool
    {
        return ftp_login($this->connection, $username, $password);
    }

    /**
     * Sets passive mode for the FTP connection.
     *
     * @param bool $pasv True to enable passive mode, false to disable.
     * @return bool True on success, false otherwise.
     */
    public function pasv(bool $pasv): bool
    {
        return ftp_pasv($this->connection, $pasv);
    }

    /**
     * Retrieves the current FTP connection resource.
     *
     * @return Connection|false The FTP connection resource or false if not connected.
     */
    public function getConnection(): false|Connection
    {
        return $this->connection;
    }

    /**
     * Retrieves a list of files in the specified directory.
     *
     * @param string $directory The directory to list.
     * @return array|false An array of file names on success, or false on failure.
     */
    public function nlist(string $directory): array|false
    {
        return ftp_nlist($this->connection, $directory);
    }

    /**
     * Retrieves the size of the specified file.
     *
     * @param string $file The file path.
     * @return int The size of the file in bytes, or -1 on error.
     */
    public function size(string $file): int
    {
        return ftp_size($this->connection, $file);
    }

    /**
     * Retrieves the last modified time of the specified file.
     *
     * @param string $file The file path.
     * @return int The last modified time as a Unix timestamp, or -1 on error.
     */
    public function mdtm(string $file): int
    {
        return ftp_mdtm($this->connection, $file);
    }

    /**
     * Changes the current directory on the FTP server.
     *
     * @param string $directory The target directory.
     * @return bool True on success, false otherwise.
     */
    public function chdir(string $directory): bool
    {
        return ftp_chdir($this->connection, $directory);
    }

    /**
     * Retrieves the current directory on the FTP server.
     *
     * @return string|false The current directory as a string, or false on error.
     */
    public function pwd(): string|false
    {
        return ftp_pwd($this->connection);
    }

    /**
     * Downloads a file from the FTP server.
     *
     * @param string $localFile The local file path to save the downloaded file.
     * @param string $remoteFile The remote file path on the FTP server.
     * @param int $mode The transfer mode (default: FTP_BINARY).
     * @return bool True on success, false otherwise.
     */
    public function get(string $localFile, string $remoteFile, int $mode = FTP_BINARY): bool
    {
        return ftp_get($this->connection, $localFile, $remoteFile, $mode);
    }

    /**
     * Closes the FTP connection.
     *
     * @return bool True on success, false otherwise.
     */
    public function close(): bool
    {
        return ftp_close($this->connection);
    }
}
