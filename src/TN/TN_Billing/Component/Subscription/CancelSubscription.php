<?php

namespace TN\TN_Billing\Component\Subscription;

use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Model\User\User;

class CancelSubscription extends JSON
{
    public function prepare(): void
    {
        $user = User::readFromId($_REQUEST['id']);
        $subscription = Subscription::getUserActiveSubscription($user);
        $subscription->cancel();
        $this->data = [
            'result' => 'success',
            'message' => 'Your subscription has been cancelled. Your access will expire on ' . date('m-d-Y', $subscription->endTs),
            'endTs' => date('m-d-Y', $subscription->endTs)
        ];
    }
}
