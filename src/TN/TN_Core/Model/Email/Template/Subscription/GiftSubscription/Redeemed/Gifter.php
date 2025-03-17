<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\GiftSubscription\Redeemed;

class Gifter extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/giftsubscription/redeemed/gifter';
    protected string $name = 'Gifter: Gift Redeemed';
    protected string $subject = 'Gift Subscription You Gifted Has Been Redeemed at {$SITE_NAME}';
    protected string $defaultTemplateFile = 'TN/Model/Email/Template/Subscription/GiftSubscription/Redeemed/Gifter.tpl';
    protected array $sampleData = [
        'recipient' => 'recipientUserName',
        'planName' => 'planName',
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}