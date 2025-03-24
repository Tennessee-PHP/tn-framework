<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\GiftSubscription;

class Recipient extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/giftsubscription/recipient';
    protected string $name = 'Gift Received';
    protected string $subject = 'Somebody Must Love You - FREE {$SITE_NAME} Gift Subscription Inside!';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Subscription/GiftSubscription/Recipient.tpl';
    protected array $sampleData = [
        'giftSubscriptionKey' => 'somelongkey',
        'gifterEmail' => 'gifterEmail@placeholder.com',
        'planName' => 'planName',
        'billingCycleNumMonths' => 12,
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}
