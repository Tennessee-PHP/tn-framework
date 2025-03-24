<?php

namespace TN\TN_Billing\Component\GiftSubscription\ListGiftSubscriptions;

use TN\TN_Billing\Model\Subscription\GiftSubscription;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Package\Stack;

class AddGiftSubscriptions extends JSON
{
    public function prepare(): void
    {
        $plan = $_POST['plan'] ?? null;
        $billing = $_POST['billing'] ?? null;
        $duration = $_POST['duration'] ?? null;
        $emails = $_POST['emails'] ?? null;

        $reason = array_search($_POST['comment'] ?? '', GiftSubscription::getReasonOptions());
        $giftSubscriptionClass = Stack::resolveClassName(GiftSubscription::class);

        if (empty($emails)) {
            throw new ValidationException('No emails provided');
        }

        $giftSubscriptionClass::createComplimentarySubscriptions($plan, $billing, $duration, $reason, $emails);
        $this->data = [
            'result' => 'success',
            'message' => 'Gift subscriptions created successfully'
        ];
    }
}
