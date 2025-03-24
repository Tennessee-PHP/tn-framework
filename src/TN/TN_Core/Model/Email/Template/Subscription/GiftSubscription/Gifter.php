<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\GiftSubscription;

class Gifter extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/giftsubscription/gifter';
    protected string $name = 'Gift Subscription Purchase';
    protected string $subject = 'Thank You! Your {$SITE_NAME} Gift Subscription Info';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Subscription/GiftSubscription/Gifter.tpl';
    protected array $sampleData = [
        'giftSubscriptionKey' => 'somelongkey',
        'recipientEmail' => 'recipientEmail@placeholder.com',
        'planName' => 'planName',
        'billingCycleNumMonths' => 12,
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}
