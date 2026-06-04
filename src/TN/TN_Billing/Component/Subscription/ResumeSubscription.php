<?php

namespace TN\TN_Billing\Component\Subscription;

use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\User;

class ResumeSubscription extends JSON
{
    public function prepare(): void
    {
        $user = User::readFromId($_REQUEST['id']);
        $subscription = Subscription::getUserActiveSubscription($user);
        if (!$subscription) {
            throw new ValidationException('No active subscription found');
        }
        $subscription->resumeAutoRenew();
        $this->data = [
            'result' => 'success',
            'message' => 'Auto-renew has been turned back on. Your next bill date is ' . date('m-d-Y', $subscription->nextTransactionTs),
            'nextTransactionTs' => date('m-d-Y', $subscription->nextTransactionTs)
        ];
    }
}
