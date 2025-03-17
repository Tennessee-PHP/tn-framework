<?php

namespace TN\TN_S3Download\Model;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use TN\TN_Core\Trait\Getter;

/**
 * a file in an S3 bucket
 *
 */
class Bucket
{
    use Getter;

    /** @var string the s3 bucket name */
    protected string $name;

    /** @var string s3 region */
    protected string $region;

    /** @var string s3 key */
    protected string $key;

    /** @var string s3 secret */
    protected string $secret;

    /** @var S3Client the s3 client */
    protected S3Client $client;

    /**
     * construct
     * @param string $name
     * @param string $region
     * @param string $key
     * @param string $secret
     */
    public function __construct(string $name, string $region, string $key, string $secret)
    {
        $this->name = $name;
        $this->region = $region;
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * is the key from the bucket an actual file to download?
     * @param string $key
     * @return bool
     */
    protected function keyIsFile(string $key): bool
    {
        $parts = explode('/', $key);
        $last = array_pop($parts);
        if (!str_contains($last, '.')) {
            return false;
        }
        $parts = explode('.', $last);
        $last = array_pop($parts);
        return $last !== 'htaccess';
    }

    public function getClient(): S3Client
    {
        if (!isset($this->client)) {
            $options = [
                'region' => $this->region,
                'version' => 'latest',
                'credentials' => [
                    'key' => $this->key,
                    'secret' => $this->secret
                ]
            ];
            $this->client = new S3Client($options);
        }
        return $this->client;
    }

    /**
     * since listObjectsV2 only returns max 1000 keys at a time, this method is iteratively called to collect them all
     * @param array $files
     * @param string $prefix
     * @param string|bool $startAfter
     * @param string|bool $startAfterNext
     */
    protected function addToFilesFromListObjects(array &$files, string $prefix, string|bool $startAfter, string|bool &$startAfterNext) {
        $client = $this->getClient();
        $query = [
            'Bucket' => $this->name
        ];
        if (is_string($startAfter)) {
            $query['StartAfter'] = $startAfter;
        }
        if (!empty($prefix)) {
            $query['Prefix'] = $prefix;
        }
        $result = $client->listObjectsV2($query);
        $lastKey = '';
        foreach ($result['Contents'] as $item) {
            if ($this->keyIsFile($item['Key'])) {
                $files[] = File::getInstanceFromProperties($this, $item['Key'], strtotime($item['LastModified']));
            }
            $lastKey = $item['Key'];
        }
        if ($result['IsTruncated']) {
            $startAfterNext = $lastKey;
        } else {
            $startAfterNext = false;
        }
    }

    /**
     * returns an array of File objects in this bucket
     * @param string $sort
     * @param string $sortDir
     * @param string $prefix
     * @return array
     */
    public function listFiles(string $sort = 'lastModified', string $sortDir = 'DESC', string $prefix = ''): array
    {
        try {
            $files = [];
            $startAfterNext = '';
            $startAfter = false;
            while (is_string($startAfterNext)) {
                $this->addToFilesFromListObjects($files, $prefix, $startAfter, $startAfterNext);
                $startAfter = $startAfterNext;
            }
            $sorters = [];
            foreach ($files as $file) {
                $sorters[] = match ($sort) {
                    'file' => $file->file,
                    default => $file->lastModifiedTs,
                };
            }
            array_multisort($sorters, $sortDir === 'ASC' ? SORT_ASC : SORT_DESC, $files);
            return $files;
        } catch (AwsException $e) {
            return [
                'error' => 'An error occurred while contacting the download server: ' . $e->getMessage()
            ];
        }
    }


}