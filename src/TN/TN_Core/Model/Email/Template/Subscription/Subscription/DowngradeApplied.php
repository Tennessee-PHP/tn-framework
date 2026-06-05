<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\Subscription;

class DowngradeApplied extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/subscription/downgradeapplied';
    protected string $name = 'Subscription Downgrade Applied';
    protected string $subject = 'Your {$SITE_NAME} plan change is complete';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Subscription/Subscription/DowngradeApplied.tpl';
    protected array $sampleData = [
        'username' => 'someUserName',
        'planName' => 'ELITE',
        'billingCycleName' => 'Annually',
        'amount' => 89.88,
        'nextTransactionTs' => 1653575466,
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}
