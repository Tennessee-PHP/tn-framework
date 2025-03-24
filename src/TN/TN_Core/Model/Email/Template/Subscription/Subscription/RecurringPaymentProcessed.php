<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\Subscription;

class RecurringPaymentProcessed extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/subscription/recurringpaymentprocessed';
    protected string $name = 'Subscription Payment Processed';
    protected string $subject = 'Subscription Payment Processed at {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Subscription/Subscription/RecurringPaymentProcessed.tpl';
    protected array $sampleData = [
        'nextTransactionTs' => 1653575466,
        'amount' => 20,
        'username' => 'someUserName',
        'plan' => 'planName',
        'billingCycle' => 'billingCycleName',
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}
