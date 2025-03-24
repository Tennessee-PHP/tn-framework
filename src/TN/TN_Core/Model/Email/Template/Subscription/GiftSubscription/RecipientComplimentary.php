<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\GiftSubscription;

class RecipientComplimentary extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/giftsubscription/recipientcomplimentary';
    protected string $name = 'Complimentary Gift Received';
    protected string $subject = 'You\'re Awesome - FREE {$SITE_NAME} Gift Subscription Inside!';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Subscription/GiftSubscription/RecipientComplimentary.tpl';
    protected array $sampleData = [
        'giftSubscriptionKey' => 'somelongkey',
        'planName' => 'planName',
        'billingCycleNumMonths' => 12,
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}
