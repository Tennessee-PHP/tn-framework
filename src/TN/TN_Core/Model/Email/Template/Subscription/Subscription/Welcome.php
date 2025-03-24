<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\Subscription;

class Welcome extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/subscription/welcome';
    protected string $name = 'Subscription Welcome (through our website)';
    protected string $subject = 'The {$SITE_NAME} Subscription You Purchased';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Subscription/Subscription/Welcome.tpl';
    protected array $sampleData = [
        'nextTransactionTs' => 1653575466,
        'username' => 'someUserName',
        'planName' => 'planName',
        'billingCycle' => 'billingCycleName',
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}
