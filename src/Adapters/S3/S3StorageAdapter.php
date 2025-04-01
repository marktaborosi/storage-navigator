<?php

namespace Marktaborosi\StorageNavigator\Adapters\S3;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Marktaborosi\StorageNavigator\Builders\FileStructureBuilder;
use Marktaborosi\StorageNavigator\Entities\DirectoryAttribute;
use Marktaborosi\StorageNavigator\Entities\FileAttribute;
use Marktaborosi\StorageNavigator\Entities\FileStructure;
use Marktaborosi\StorageNavigator\Interfaces\StorageNavigatorAdapterInterface;
use Marktaborosi\StorageNavigator\Traits\PathHelperTrait;

/**
 * S3StorageAdapter interacts with S3-compatible object storage services.
 *
 * This includes Google Cloud Storage, MinIO, and AWS S3.
 */
class S3StorageAdapter implements StorageNavigatorAdapterInterface
{
    use PathHelperTrait;

    /**
     * @var S3Client
     */
    private S3Client $s3Client;

    /**
     * @var string The name of the bucket to interact with.
     */
    private string $bucketName;

    /**
     * Constructor for the S3StorageAdapter class.
     *
     * @param string $bucketName The name of the S3-compatible bucket.
     * @param S3Client $s3Client S3 Client
     */
    public function __construct(string $bucketName, S3Client $s3Client)
    {
        $this->bucketName = $bucketName;
        $this->s3Client = $s3Client;
    }

    /**
     * Get the structure of the files and directories in the S3 bucket.
     *
     * @param string $location The directory or folder location to scan within the bucket.
     * @return FileStructure The file structure of the specified location.
     * @throws AwsException If there's an error while accessing the S3 bucket.
     */
    public function getFileStructure(string $location): FileStructure
    {
        $structureBuilder = new FileStructureBuilder();

        // Normalize location to include trailing slash
        $location = rtrim($location, '/') . '/';
        if ($location === '/') {
            $location = "";
        }
        // List objects in the bucket under the specified location
        $result = $this->s3Client->listObjectsV2([
            'Bucket' => $this->bucketName,
            'Prefix' => $location,
            'Delimiter' => '/',
        ]);

        // Process directories
        if (isset($result['CommonPrefixes'])) {
            foreach ($result['CommonPrefixes'] as $prefix) {
                $dirPath = $prefix['Prefix'];

                $structureBuilder->addDirectory(new DirectoryAttribute(
                    name: basename($dirPath),
                    path: $this->getNormalizedDirname($dirPath),
                    lastModified: null  // S3 does not store directory metadata
                ));
            }
        }

        // Process files
        if (isset($result['Contents'])) {
            foreach ($result['Contents'] as $object) {
                $filePath = $object['Key'];
                $fileSize = $object['Size'];
                $lastModified = $object['LastModified']->format('U');  // Convert to Unix timestamp

                // Add file to structure
                $structureBuilder->addFile(new FileAttribute(
                    directoryPath: $this->getNormalizedDirname($filePath),
                    filename: basename($filePath),
                    extension: pathinfo($filePath, PATHINFO_EXTENSION),
                    byteSize: $fileSize,
                    lastModified: $lastModified
                ));
            }
        }

        return $structureBuilder->sortByAZ()->build();
    }

    /**
     * Check if a file or directory exists in the S3 bucket.
     *
     * @param string $location The location (file or directory) to check for existence.
     * @return bool True if the location exists, false otherwise.
     */
    public function fileOrDirectoryExists(string $location): bool
    {
        // Handle the root directory check: it should be an empty string or "/"
        if (empty($location) || $location === '/') {
            // Use listObjectsV2 to check for any objects in the root directory
            $result = $this->s3Client->listObjectsV2([
                'Bucket' => $this->bucketName,
                'Prefix' => '',  // Root directory, prefix is empty
                'Delimiter' => '/',  // Delimiter to treat it as a directory
            ]);

            // If any object exists in the root, it's considered as a valid directory
            return isset($result['Contents']) && count($result['Contents']) > 0;
        }

        try {
            // Check if the location is a file
            $this->s3Client->headObject([
                'Bucket' => $this->bucketName,
                'Key' => $location,
            ]);
            return true;  // If the file exists, return true
        } catch (AwsException $e) {
            // If the object is not found (404), check if it's a directory by listing objects
            if ($e->getStatusCode() == 404) {
                // Use listObjectsV2 to check if there are objects (files) in the directory
                $result = $this->s3Client->listObjectsV2([
                    'Bucket' => $this->bucketName,
                    'Prefix' => rtrim($location, '/') . '/',
                    'Delimiter' => '/',  // Delimiter separates directories (common prefixes)
                ]);

                // If there are files in the "directory", it's considered an existing directory
                return isset($result['Contents']) && count($result['Contents']) > 0;
            }
            return false;  // If we get another error, it's safe to assume the location doesn't exist
        }
    }


    /**
     * Download a file from the S3 bucket.
     *
     * @param string $filePath The file path to download.
     * @throws AwsException If the download fails.
     */
    public function downloadFile(string $filePath): void
    {
        try {
            // Download the file
            $result = $this->s3Client->getObject([
                'Bucket' => $this->bucketName,
                'Key' => $filePath,
            ]);

            // Set headers for the file download
            header('Content-Type: ' . $result['ContentType']);
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . $result['ContentLength']);

            // Output the file content
            echo $result['Body'];

        } catch (AwsException $e) {
            echo 'Error: ' . $e->getMessage();
        }
    }

}
