<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\Subscription;

class Cancelled extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/subscription/cancelled';
    protected string $name = 'Subscription Cancelled';
    protected string $subject = 'Subscription Cancelled at {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN/Model/Email/Template/Subscription/Subscription/Cancelled.tpl';
    protected array $sampleData = [
        'planName' => 'planName',
        'username' => 'someUserName',
        'endTs' => 1653575466,
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}