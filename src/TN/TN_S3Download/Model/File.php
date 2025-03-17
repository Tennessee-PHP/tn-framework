<?php

namespace TN\TN_S3Download\Model;

use Aws\Exception\AwsException;
use TN\TN_S3Download\Model\File\FileDownloadCount;
use TN\TN_Billing\Model\Subscription\Content\Content;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Trait\Getter;

/**
 * a file in an S3 bucket
 *
 */
class File
{
    use Getter;

    const int MAX_FILE_SIZE = 100 * 1024 * 1024;

    /** @var Bucket the s3 bucket */
    protected Bucket $bucket;

    /** @var string the path to the file in that bucket */
    public string $file;

    /** @var int when the file was last modified */
    protected int $lastModifiedTs;

    /**
     * construct
     * @param Bucket $bucket
     * @param string $file
     * @param int $lastModifiedTs
     */
    private function __construct(Bucket $bucket, string $file, int $lastModifiedTs = 0)
    {
        $this->bucket = $bucket;
        $this->file = $file;
        $this->lastModifiedTs = $lastModifiedTs;
    }

    /**
     * @param Bucket $bucket
     * @param string $file
     * @param int $lastModifiedTs
     * @return File
     */
    public static function getInstanceFromProperties(Bucket $bucket, string $file, int $lastModifiedTs = 0): File
    {
        return new self($bucket, $file, $lastModifiedTs);
    }

    /**
     * @param string $localPath
     * @param string $size
     * @param Bucket $bucket
     * @param string $file
     * @param string $contentKey
     * @return File|array
     */
    public static function getInstanceFromUpload(string $localPath, string $size, Bucket $bucket, string $file,
                                                 string $contentKey = ''): File|array
    {
        if ($size > self::MAX_FILE_SIZE) {
            return [
                'error' => 'The file exceeds the maximum size of ' . (self::MAX_FILE_SIZE / (1024 * 1024)) . ' mb'
            ];
        }

        if (!file_exists($localPath)) {
            return [
                'error' => 'Local file to upload does not exist'
            ];
        }

        // trim the file from whitespace and wrapping slashes
        $file = trim($file, " \n\r\t\v\x00\\\/");


        // get rid of spaces
        $file = str_replace(' ', '-', $file);

        if (empty($contentKey)) {
            $key = $file;
        } else {
            $key = $contentKey . '/' . $file;
        }


        $openFirst = fopen($localPath, 'r');
        try {
            $client = $bucket->getClient();
            $client->putObject([
                'Bucket' => $bucket->name,
                'Key' => $key,
                'Body' => $openFirst,
                'ContentType' => self::getContentType($key)
            ]);
            return self::getInstanceFromProperties($bucket, $key, time());
        } catch (AwsException $e) {
            return [
                'error' => 'An error occurred while attempting to upload the file to S3: ' . $e->getMessage()
            ];
        }

    }

    public static function getExtension(string $filename): string
    {
        $parts = explode('.', $filename);
        return array_pop($parts);
    }

    public static function getContentType(string $filename): string
    {
        $ext = strtolower(self::getExtension($filename));
        $mime_types = [
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'css' => 'text/css',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            'hqx' => 'application/mac-binhex40',
            'cpt' => 'application/mac-compactpro',
            'csv' => ['text/csv', 'text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'],
            'bin' => 'application/macbinary',
            'dms' => 'application/octet-stream',
            'lha' => 'application/octet-stream',
            'lzh' => 'application/octet-stream',
            'exe' => ['application/octet-stream', 'application/x-msdownload'],
            'class' => 'application/octet-stream',
            'so' => 'application/octet-stream',
            'sea' => 'application/octet-stream',
            'dll' => 'application/octet-stream',
            'oda' => 'application/oda',
            'smi' => 'application/smil',
            'smil' => 'application/smil',
            'mif' => 'application/vnd.mif',
            'wbxml' => 'application/wbxml',
            'wmlc' => 'application/wmlc',
            'dcr' => 'application/x-director',
            'dir' => 'application/x-director',
            'dxr' => 'application/x-director',
            'dvi' => 'application/x-dvi',
            'gtar' => 'application/x-gtar',
            'gz' => 'application/x-gzip',
            'php' => 'application/x-httpd-php',
            'php4' => 'application/x-httpd-php',
            'php3' => 'application/x-httpd-php',
            'phtml' => 'application/x-httpd-php',
            'phps' => 'application/x-httpd-php-source',
            'js' => ['application/javascript', 'application/x-javascript'],
            'sit' => 'application/x-stuffit',
            'tar' => 'application/x-tar',
            'tgz' => ['application/x-tar', 'application/x-gzip-compressed'],
            'xhtml' => 'application/xhtml+xml',
            'xht' => 'application/xhtml+xml',
            'shtml' => 'text/html',
            'text' => 'text/plain',
            'log' => ['text/plain', 'text/x-log'],
            'rtx' => 'text/richtext',
            'xsl' => 'text/xml',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'word' => ['application/msword', 'application/octet-stream'],
            'xl' => 'application/excel',
            'eml' => 'message/rfc822',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => ['application/x-zip', 'application/zip', 'application/x-zip-compressed'],
            'rar' => 'application/x-rar-compressed',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mpga' => 'audio/mpeg',
            'mp2' => 'audio/mpeg',
            'mp3' => ['audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'],
            'aif' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'ram' => 'audio/x-pn-realaudio',
            'rm' => 'audio/x-pn-realaudio',
            'rpm' => 'audio/x-pn-realaudio-plugin',
            'ra' => 'audio/x-realaudio',
            'rv' => 'video/vnd.rn-realvideo',
            'wav' => ['audio/x-wav', 'audio/wave', 'audio/wav'],
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpe' => 'video/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'movie' => 'video/x-sgi-movie',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => ['image/vnd.adobe.photoshop', 'application/x-photoshop'],
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => ['application/excel', 'application/vnd.ms-excel', 'application/msexcel'],
            'ppt' => ['application/powerpoint', 'application/vnd.ms-powerpoint'],

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        ];

        if (array_key_exists($ext, $mime_types)) {
            return (is_array($mime_types[$ext])) ? $mime_types[$ext][0] : $mime_types[$ext];
        }

        return 'application/octet-stream';
    }

    /**
     * write the content of the file
     */
    public function write(string $content): bool|array
    {
        try {
            $client = $this->bucket->getClient();
            $result = $client->putObject([
                'Bucket' => $this->bucket->name,
                'Key' => $this->file,
                'Body' => $content,
                'ContentType' => self::getContentType($this->file)
            ]);
            return true;
        } catch (AwsException $e) {
            return [
                'error' => 'An error occurred while attempting to upload the file to S3: ' . $e->getMessage()
            ];
        }
    }

    /**
     * read the body of the file
     */
    public function read(): string|array
    {
        try {
            $client = $this->bucket->getClient();
            $result = $client->getObject([
                'Bucket' => $this->bucket->name,
                'Key' => $this->file
            ]);
            $this->lastModifiedTs = strtotime($result['LastModified']);
            return $result['Body'];
        } catch (AwsException $e) {
            return [
                'error' => 'An error occurred while contacting the download server: ' . $e->getMessage()
            ];
        }
    }

    /**
     * does the file exist?
     * @return bool|array
     */
    public function exists(): bool|array
    {
        try {
            $client = $this->bucket->getClient();
            $query = [
                'Bucket' => $this->bucket->name,
                'Prefix' => $this->file
            ];
            $result = $client->listObjectsV2($query);
            $fileList = [];
            foreach ($result->toArray()['Contents'] ?? [] as $file) {
                $fileList[] = $file['Key'];
            }
            return in_array($this->file, $fileList);
        } catch (AwsException $e) {
            return [
                'error' => 'An error occurred while contacting the download server: ' . $e->getMessage()
            ];
        }
    }

    public function erase(): bool|array
    {
        try {
            $client = $this->bucket->getClient();
            $query = [
                'Bucket' => $this->bucket->name,
                'Key' => $this->file
            ];
            $client->deleteObject($query);
            return true;
        } catch (AwsException $e) {
            return [
                'error' => 'An error occurred while contacting the download server: ' . $e->getMessage()
            ];
        }
    }

    /**
     * if a content key is associated with this file, get it; else, false
     * @return string|bool
     */
    public function getContentKey(): string|bool
    {
        $parts = explode('/', $this->file);
        $contentKey = array_shift($parts);

        $content = Content::getInstanceByKey($contentKey);
        return $content instanceof Content ? $content->key : false;
    }

    /**
     * gets an array of plans that can download this file
     * @return Content|bool
     */
    public function getContent(): Content|bool
    {
        $parts = explode('/', $this->file);
        $contentKey = array_shift($parts);

        $content = Content::getInstanceByKey($contentKey);
        return $content instanceof Content ? $content : false;
    }

    /**
     * gets an array of plans that can download this file
     * @return array
     */
    public function getPlans(): array
    {
        $content = $this->getContent();
        if ($content === false) {
            return [];
        }
        $plans = [];
        foreach (Plan::getInstances() as $plan) {
            if ($plan->level >= $content->level) {
                $plans[] = $plan;
            }
        }
        return $plans;
    }

    /**
     * get a pre-signed url to send the user to for downloading
     * @return string|array
     */
    public function getPresignedUrl(): string|array
    {
        try {
            $client = $this->bucket->getClient();
            $cmd = $client->getCommand('GetObject', [
                'Bucket' => $this->bucket->name,
                'Key' => $this->file
            ]);
            $request = $client->createPresignedRequest($cmd, '+2 minutes');
            return (string)$request->getUri();
        } catch (AwsException $e) {
            return [
                'error' => 'An error occurred while contacting the download server: ' . $e->getMessage()
            ];
        }
    }

    /**
     * @return void record a download of this file
     * @throws ValidationException
     */
    public function recordDownload(User $user): void
    {
        $downloadCount = FileDownloadCount::getInstance();
        $downloadCount->update([
            'file' => $this->file,
            'userIdentifier' => $user->getUniversalIdentifier(),
            'ts' => Time::getNow(),
            'premium' => $user->isPaidSubscriber()
        ]);
    }
}