<?php

namespace TN\TN_Billing\Component\GiftSubscription\GetGiftSubscriptionStatus;

use TN\TN_Billing\Model\Subscription\GiftSubscription;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Error\ValidationException;

#[Page('Gift Subscription Status', '', false)]
#[Route('TN_Billing:GiftSubscription:status')]
class GetGiftSubscriptionStatus extends HTMLComponent
{
    public string $key;
    public ?GiftSubscription $giftSubscription;
    public Plan $plan;
    public function prepare(): void
    {
        $this->giftSubscription = GiftSubscription::readFromKey($this->key);
        if (!$this->giftSubscription) {
            throw new ValidationException('Gift subscription not found');
        }
        $this->plan = Plan::getInstanceByKey($this->giftSubscription->planKey);
        if (!$this->plan) {
            throw new ValidationException('Plan not found');
        }
    }
}
