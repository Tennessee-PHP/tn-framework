<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\Subscription;

class ScheduledDowngrade extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/subscription/scheduleddowngrade';
    protected string $name = 'Subscription Scheduled Downgrade';
    protected string $subject = 'Your {$SITE_NAME} plan change is scheduled';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Subscription/Subscription/ScheduledDowngrade.tpl';
    protected array $sampleData = [
        'username' => 'someUserName',
        'fromPlanName' => 'HALL OF FAME',
        'toPlanName' => 'ELITE',
        'billingCycleName' => 'Annually',
        'effectiveTs' => 1653575466,
        'renewalAmount' => 89.88,
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}
