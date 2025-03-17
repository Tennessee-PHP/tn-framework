<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\Subscription;

class UpcomingRenewal extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/subscription/upcomingrenewal';
    protected string $name = 'Subscription Renewal';
    protected string $subject = 'Your {$SITE_NAME} Subscription Is About To Renew';
    protected string $defaultTemplateFile = 'TN/Model/Email/Template/Subscription/Subscription/UpcomingRenewal.tpl';
    protected array $sampleData = [
        'subscription' => 'Subscription',
        'username' => 'someUserName',
        'planName' => 'planName',
        'billingCycleName' => 'billingCycleName',
        'price' => 20,
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}