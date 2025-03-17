<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\Subscription;

class Expired extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/subscription/expired';
    protected string $name = 'Subscription Expired';
    protected string $subject = 'Your {$SITE_NAME} Gift/Complimentary Subscription Expired';
    protected string $defaultTemplateFile = 'TN/Model/Email/Template/Subscription/Subscription/Expired.tpl';
    protected array $sampleData = [
        'username' => 'someUserName',
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}