<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\Subscription\Welcome;

class RotoPass extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/subscription/welcome/rotopass';
    protected string $name = 'Subscription Welcome (from RotoPass)';
    protected string $subject = 'Your RotoPass Subscription at {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN/Model/Email/Template/Subscription/Subscription/Welcome/RotoPass.tpl';
    protected array $sampleData = [
        'username' => 'someUserName',
        'planName' => 'planName',
        'billingCycle' => 'billingCycleName',
        'SITE_NAME' => '{$SITE_NAME}.com'
    ];
}