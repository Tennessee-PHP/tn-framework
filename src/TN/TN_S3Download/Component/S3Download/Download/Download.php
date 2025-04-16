<?php

namespace TN\TN_S3Download\Component\S3Download\Download;

use TN\TN_Billing\Component\Roadblock\Roadblock\Roadblock;
use TN\TN_Core\Attribute\Route\Access\Restrictions\ContentOwnersOnly;
use \TN\TN_Core\Component\HTMLComponent;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use \TN\TN_Core\Attribute\Components\HTMLComponent\Breadcrumb;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\User\User;
use TN\TN_S3Download\Model\Bucket;
use TN\TN_S3Download\Model\File;

#[Page('Download File', 'You do not have permission to download this file', false)]
#[Route('TN_S3Download:S3Download:download')]
#[Breadcrumb('Download')]
class Download extends HTMLComponent
{
    public Roadblock $roadblock;
    public ?string $file = null;

    public function prepare(): void
    {
        $bucket = new Bucket($_ENV['DOWNLOADS_AWS_S3_BUCKET'], $_ENV['DOWNLOADS_AWS_S3_REGION'], $_ENV['DOWNLOADS_AWS_S3_KEY'], $_ENV['DOWNLOADS_AWS_S3_SECRET']);
        $file = File::getInstanceFromProperties($bucket, $this->file ?? $_GET['file']);
        $user = User::getActive();

        echo 'here';
        exit;
        $res = $file->exists();
        if (!$res) {
            throw new ValidationException('File not found');
        }
        if (is_array($res)) {
            throw new ValidationException($res);
        }

        // control based on the individual file
        $contentKey = $file->getContentKey();

        $request = HTTPRequest::get();
        if ($contentKey !== false) {
            $request->setAccess(new ContentOwnersOnly($contentKey));
        }

        $this->roadblock = new Roadblock();
        $this->roadblock->prepare();

        if ($request->roadblocked) {
            return;
        }

        $res = $file->getPresignedUrl();
        if (is_array($res)) {
            throw new ValidationException($res);
        }

        // since we're about to forward them over, let's now record the download
        $file->recordDownload($user);

        $request->redirect($res);
    }
}
