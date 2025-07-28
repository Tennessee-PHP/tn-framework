<?php

namespace TN\TN_Core\Model\Email\Template\Export;

class SendToUser extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'export/sendtouser';
    protected string $name = 'Exported File Sent To User';
    protected string $subject = 'Your Exported {$fileDescriptionShort}';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Export/SendToUser.tpl';
    protected array $sampleData = [
        'fileDescriptionShort' => 'a short description',
        'url' => 'fake/url',
        'fileDescription' => 'description of whatever file we sent you',
    ];
}
