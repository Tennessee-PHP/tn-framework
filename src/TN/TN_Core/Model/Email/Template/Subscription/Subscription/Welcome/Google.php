<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\Subscription\Welcome;

class Google extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/subscription/welcome/google';
    protected string $name = 'Subscription Welcome (from Google Play)';
    protected string $subject = 'The {$SITE_NAME} Subscription You Purchased';
    protected string $defaultTemplateFile = 'TN/Model/Email/Template/Subscription/Subscription/Welcome/Google.tpl';
    protected array $sampleData = [
        'username' => 'someUserName',
        'planName' => 'planName',
        'billingCycle' => 'billingCycleName',
        'SITE_NAME' => '{$SITE_NAME}.com'
    ];
}