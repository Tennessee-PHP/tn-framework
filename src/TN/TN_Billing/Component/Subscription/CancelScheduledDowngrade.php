<?php

namespace TN\TN_Billing\Component\Subscription;

use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Service\PlanChangeService;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\User;

class CancelScheduledDowngrade extends JSON
{
    public function prepare(): void
    {
        $user = User::readFromId((int)($_REQUEST['id'] ?? 0));
        $subscription = Subscription::getUserActiveSubscription($user);
        if (!$subscription instanceof Subscription) {
            throw new ValidationException('No active subscription found');
        }

        PlanChangeService::cancelScheduledDowngrade($subscription);

        $this->data = [
            'result' => 'success',
            'message' => 'Your scheduled plan change has been cancelled.',
        ];
    }
}
