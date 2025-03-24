<?php

namespace TN\TN_Core\Model\Email\Template\Subscription\GiftSubscription\Redeemed;

class Recipient extends \TN\TN_Core\Model\Email\Template\Template
{
    protected string $key = 'subscription/giftsubscription/redeemed/recipient';
    protected string $name = 'Recipient: Gift Redeemed';
    protected string $subject = 'Gift Subscription Redeemed at {$SITE_NAME}!';
    protected string $defaultTemplateFile = 'TN_Core/Model/Email/Template/Subscription/GiftSubscription/Redeemed/Recipient.tpl';
    protected array $sampleData = [
        'gifterEmail' => 'gifterEmail@placeholder.com',
        'username' => 'someUserName',
        'planName' => 'planName',
        'SITE_NAME' => '{$SITE_NAME}.com',
    ];
}
