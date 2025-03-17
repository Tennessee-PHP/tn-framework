<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\Subscription;

class PaymentFailedAndGracePeriodExpired extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/subscription/paymentfailedandgraceperiodexpired';
    protected string $name = 'Payment Failed and Grace Period Expired';
    protected string $subject = 'Subscription at {$SITE_NAME} - Payment Failed';
    protected string $defaultTemplateFile = 'TN/Model/Email/Template/Subscription/Subscription/PaymentFailedAndGracePeriodExpired.tpl';
    protected array $sampleData = [
        'planName' => 'planName',
        'username' => 'someUserName',
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}