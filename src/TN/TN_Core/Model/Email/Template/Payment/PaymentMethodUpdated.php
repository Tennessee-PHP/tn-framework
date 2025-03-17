<?php

namespace TN\TN_Core\Model\Email\Template\Payment;

class PaymentMethodUpdated extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'payment/paymentmethodupdated';
    protected string $name = 'Payment Method Updated';
    protected string $subject = 'Payment Updated at {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN/Model/Email/Template/Payment/PaymentMethodUpdated.tpl';
    protected array $sampleData = [
        'nextTransactionTs' => 1653572630,
        'username' => 'someUserName',
        'planName' => 'somePlanName',
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}