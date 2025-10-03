<?php

namespace TN\TN_CMS\Model\Media;

use TN\TN_Core\Error\ValidationException;

/**
 * an image that has been uploaded for use in the CMS
 *
 * 
 */
class Image
{
    /** @var string  */
    public string $src;

    /** @var string  */
    public string $data;

    /**
     * @param string $src
     * @return Image
     * @throws ValidationException
     */
    public static function getFromSrc(string $src): Image
    {
        if (empty($src)) {
            throw new ValidationException('Image source not provided');
        }
        $image = new Image();
        $image->src = $src;
        $image->data = file_get_contents($src);
        return $image;
    }

    public function getAlt(): string
    {
        $filename = $_ENV['TN_FILES_ROOT'] . "images/tmp";
        file_put_contents($filename, $this->data);
        $exif = exif_read_data($filename);
        return '';
    }
}
