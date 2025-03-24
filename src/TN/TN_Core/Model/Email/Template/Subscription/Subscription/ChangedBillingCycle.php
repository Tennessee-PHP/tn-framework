<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\Subscription;

class ChangedBillingCycle extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/subscription/changedbillingcycle';
    protected string $name = 'Subscription Changed Billing Cycle';
    protected string $subject = 'Billing Period Changed at {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Subscription/Subscription/ChangedBillingCycle.tpl';
    protected array $sampleData = [
        'planName' => 'planname',
        'billingCycleName' => 12,
        'username' => 'someUserName',
        'nextTransactionTs' => 1653575466,
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}
