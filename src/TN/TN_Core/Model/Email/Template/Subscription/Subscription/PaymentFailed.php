<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\Subscription;

class PaymentFailed extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/subscription/paymentfailed';
    protected string $name = 'Payment Failed';
    protected string $subject = 'Subscription Payment Failed at {$SITE_NAME} - Action Needed!';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Subscription/Subscription/PaymentFailed.tpl';
    protected array $sampleData = [
        'username' => 'someUserName',
        'planName' => 'planName',
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}
