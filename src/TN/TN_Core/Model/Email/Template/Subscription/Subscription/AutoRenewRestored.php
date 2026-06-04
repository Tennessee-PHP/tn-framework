<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\Subscription;

class AutoRenewRestored extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/subscription/autorenewrestored';
    protected string $name = 'Subscription Auto-Renew Restored';
    protected string $subject = 'Auto-Renew Restored at {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Subscription/Subscription/AutoRenewRestored.tpl';
    protected array $sampleData = [
        'planName' => 'planName',
        'username' => 'someUserName',
        'nextTransactionTs' => 1653575466,
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}
